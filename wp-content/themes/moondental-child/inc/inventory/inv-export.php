<?php
/**
 * 품목신청 (재고관리 v5) — 내보내기 (엑셀)
 *
 * 엑셀(.xlsx)은 라이브러리 없이 직접 만든다 — 서버에 ZipArchive 가 없어도 되게
 * zip 도 손으로 짠다 (저장 방식 deflate). 표마다 첫 줄 고정 · 필터 · 숫자 서식.
 *
 * 표 정의는 md_inv_dataset() 한 곳에 있고, 화면 내려받기 · 메일이 모두 이것을 쓴다.
 * CSV 는 v4.23 에서 없앴다 — 엑셀 하나가 시트 · 서식 · 필터까지 다 해서 (원장 지시).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 표 정의
 * ============================================================ */

function md_inv_dataset_names() {
	return array(
		'items'    => '재고 현황',
		'low'      => '부족 품목',
		'ledger'   => '입출고 기록',
		'requests' => '요청 내역',
		'orders'   => '주문 내역',
		'usage'    => '사용금액 (팀별)',
		'monthly'  => '월별 사용금액',
		'prepaid'  => '선납 현황',
		'deposits' => '선납 입금',
		'vendors'  => '업체',
		'teams'    => '팀',
		'cats'     => '분류',
		'prices'   => '단가 변동',
		'passbook' => '선납 거래 내역',
		'lots'     => 'LOT · 차트번호',
		'adjs'     => '환불 · 정정',
		'log'      => '활동 기록',
	);
}

/**
 * @param string $key  위 이름 중 하나
 * @param array  $a    from, to (Y-m-d)
 * @return array [ 'title' =>, 'head' => [...], 'rows' => [[...]], 'num' => [열 번호 => 'n'|'won'], 'width' => [...] ]
 */
function md_inv_dataset( $key, $a = array() ) {
	$a = wp_parse_args( $a, array( 'from' => date( 'Y-m-d', current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS ), 'to' => current_time( 'Y-m-d' ) ) );
	$L = md_inv_settings();
	$names = md_inv_dataset_names();
	$out = array( 'title' => isset( $names[ $key ] ) ? $names[ $key ] : $key, 'head' => array(), 'rows' => array(), 'num' => array(), 'width' => array() );

	switch ( $key ) {
		case 'items':
		case 'low':
			$out['head']  = array( '코드', '품목명', '업체', $L['label_cat1'], $L['label_cat2'], $L['label_cat3'], '단위', '단가', '현재고', '안전재고', '상태', '대기 요청', '주문 중', '재고 금액', '바코드', '비고', '숨김', '보관 위치' );
			$out['num']   = array( 7 => 'won', 8 => 'n', 9 => 'n', 11 => 'n', 12 => 'n', 13 => 'won' );
			$out['width'] = array( 9, 40, 16, 12, 14, 12, 7, 11, 8, 8, 7, 8, 8, 12, 14, 30, 6, 18 );
			$items = 'low' === $key ? md_inv_need_order( true ) : md_inv_items( array( 'active' => -1 ) );
			$st = array( 'out' => '품절', 'low' => '부족', 'ok' => '' );
			foreach ( $items as $it ) {
				$out['rows'][] = array( $it->code, $it->name, md_inv_vendor_name( $it->vendor_id ), md_inv_cat_name( $it->cat1 ), md_inv_cat_name( $it->cat2 ), md_inv_cat_name( $it->cat3 ),
					$it->unit, $it->price, $it->stock, $it->min_stock, $st[ md_inv_stock_state( $it ) ], $it->pend, $it->onord, max( 0, $it->stock ) * $it->price,
					$it->barcode, (string) $it->note, $it->active ? '' : '숨김', (string) $it->location );
			}
			break;

		case 'ledger':
			/* v6.5 · 금액 = 입고는 실제로 낸 금액(배송비 포함), 반품은 돌려받은 금액(−), 출고 · 실사는 수량 × 단가(참고) */
			$out['head']  = array( '일시', '구분', '코드', '품목', '수량', '단위', '단가', '금액', '팀', '업체', '무상 수량', '처리자', '비고', '요청 번호', '주문 번호', '실사 수량', '취소됨', '받은 사람', 'LOT', '차트번호', '배송비 등' );
			$out['num']   = array( 4 => 'n', 6 => 'won', 7 => 'won', 10 => 'n', 15 => 'n', 20 => 'won' );
			$out['width'] = array( 16, 6, 9, 36, 7, 6, 10, 12, 14, 14, 8, 10, 30, 8, 8, 8, 14, 10, 14, 10, 10 );
			foreach ( md_inv_ledger( array( 'from' => $a['from'], 'to' => $a['to'], 'limit' => 0 ) ) as $l ) {
				$amt = 'in' === $l->type ? (int) $l->amount : ( 'return' === $l->type ? -(int) $l->amount : (int) $l->qty * (int) $l->price );
				$out['rows'][] = array( substr( $l->created_at, 0, 16 ), md_inv_type_label( $l->type ), $l->item_code, $l->item_name, (int) $l->qty, $l->unit, (int) $l->price, $amt,
					md_inv_team_name( $l->team_id ), md_inv_vendor_name( $l->vendor_id ), 'in' === $l->type && (int) $l->free_qty ? (int) $l->free_qty : '', $l->person, $l->note,
					$l->req_id ? $l->req_id : '', $l->ord_id ? $l->ord_id : '', null === $l->counted ? '' : (int) $l->counted, $l->voided ? '취소 · ' . $l->void_note : '', (string) $l->receiver, (string) $l->lot, (string) $l->chart, 'in' === $l->type && (int) $l->extra ? (int) $l->extra : '' );
			}
			break;

		case 'requests':
			$out['head']  = array( '번호', '요청 일시', '팀', '요청자', '품목', '목록에 없음', '업체(직접)', '요청 수량', '출고 수량', '단위', '상태', '처리 일시', '처리자', '요청 메모', '처리 메모', '긴급', '받은 사람' );
			$out['num']   = array( 7 => 'n', 8 => 'n' );
			$out['width'] = array( 7, 16, 14, 10, 36, 8, 14, 8, 8, 6, 9, 16, 10, 24, 24, 5 );
			foreach ( md_inv_reqs( array( 'from' => $a['from'], 'to' => $a['to'], 'limit' => 0 ) ) as $r ) {
				$out['rows'][] = array( (int) $r->id, substr( $r->created_at, 0, 16 ), md_inv_team_name( $r->team_id ), $r->requester, $r->name, $r->item_id ? '' : '목록에 없음', $r->custom_vendor,
					$r->qty, $r->qty_out, $r->unit, md_inv_req_status_label( $r->status ), $r->done_at ? substr( $r->done_at, 0, 16 ) : '', $r->done_by, $r->note, $r->admin_note, $r->urgent ? '긴급' : '', (string) $r->receiver );
			}
			break;

		case 'orders':
			$out['head']  = array( '번호', '주문일', '품목', '업체', '주문 수량', '입고 수량', '단가', '금액', '상태', '입고일', '요청 번호', '메모', '취소 사유', '주문자' );
			$out['num']   = array( 4 => 'n', 5 => 'n', 6 => 'won', 7 => 'won' );
			$out['width'] = array( 7, 11, 36, 16, 8, 8, 10, 12, 9, 11, 8, 24, 16, 10 );
			foreach ( md_inv_ords( array( 'from' => $a['from'], 'to' => $a['to'], 'limit' => 0 ) ) as $o ) {
				$out['rows'][] = array( (int) $o->id, substr( $o->created_at, 0, 10 ), $o->item_name, md_inv_vendor_name( $o->vendor_id ), (int) $o->qty, (int) $o->recv_qty, (int) $o->price, (int) $o->amount,
					md_inv_ord_status_label( $o->status ), $o->received_at ? substr( $o->received_at, 0, 10 ) : '', $o->req_id ? $o->req_id : '', $o->note, $o->cancel_note, $o->person );
			}
			/* 아직 들어오지 않은 주문은 기간과 상관없이 모두 */
			$have = array();
			foreach ( $out['rows'] as $r ) { $have[ $r[0] ] = 1; }
			foreach ( md_inv_ords( array( 'status' => 'ordered', 'limit' => 0 ) ) as $o ) {
				if ( isset( $have[ (int) $o->id ] ) ) { continue; }
				$out['rows'][] = array( (int) $o->id, substr( $o->created_at, 0, 10 ), $o->item_name, md_inv_vendor_name( $o->vendor_id ), (int) $o->qty, (int) $o->recv_qty, (int) $o->price, (int) $o->amount,
					md_inv_ord_status_label( $o->status ), '', $o->req_id ? $o->req_id : '', $o->note, $o->cancel_note, $o->person );
			}
			break;

		case 'prices':
			$out['head']  = array( '일시', '품목', '업체', '전 단가', '새 단가', '변동(%)', '어디서', '품목 단가 반영', '누가' );
			$out['num']   = array( 3 => 'won', 4 => 'won' );
			$out['width'] = array( 16, 36, 16, 11, 11, 9, 8, 10, 10 );
			foreach ( md_inv_price_history( 0, 5000 ) as $p ) {
				$out['rows'][] = array( substr( $p->created_at, 0, 16 ), $p->item_name, md_inv_vendor_name( $p->vendor_id ), (int) $p->old_price, (int) $p->new_price, $p->old_price ? round( ( $p->new_price - $p->old_price ) / $p->old_price * 100, 1 ) : '', $p->source, $p->applied ? 'O' : '', $p->person );
			}
			break;

		case 'po':
			$v = md_inv_vendor( isset( $a['vendor'] ) ? (int) $a['vendor'] : 0 );
			$out['title'] = '발주서 ' . ( $v ? $v->name : '' );
			$out['head']  = array( '품목', '수량', '단위', '단가', '금액', '주문일', '메모' );
			$out['num']   = array( 1 => 'n', 3 => 'won', 4 => 'won' );
			$out['width'] = array( 40, 8, 7, 11, 13, 11, 24 );
			$sum = 0;
			$vid_want = isset( $a['vendor'] ) ? (int) $a['vendor'] : 0;
			foreach ( md_inv_ords( array( 'status' => 'ordered', 'limit' => 0 ) ) as $o ) {
				if ( (int) $o->vendor_id !== $vid_want ) { continue; }
				$left = (int) $o->qty - (int) $o->recv_qty;
				$amt  = md_inv_ord_amount_left( $o ); /* v6.5 · 주문 합계(배송비 포함) − 받은 금액 */
				$sum += $amt;
				$out['rows'][] = array( $o->item_name, $left, $o->unit, (int) $o->price, $amt, substr( $o->created_at, 0, 10 ), $o->note );
			}
			$out['rows'][] = array( '합계', '', '', '', $sum, '', '' );
			if ( $v ) {
				$out['rows'][] = array( '', '', '', '', '', '', '' );
				$out['rows'][] = array( '받는 곳: ' . $v->name . ' ' . trim( $v->contact . ' ' . $v->phone ), '', '', '', '', '', '' );
				$out['rows'][] = array( '보내는 곳: 문치과병원 · ' . current_time( 'Y-m-d' ), '', '', '', '', '', '' );
			}
			break;

		case 'usage':
			$out['title'] = '사용금액 ' . $a['from'] . '~' . $a['to'];
			$out['head']  = array( '팀', '출고 건수', '수량', '사용금액' );
			$out['num']   = array( 1 => 'n', 2 => 'n', 3 => 'won' );
			$out['width'] = array( 18, 10, 10, 14 );
			$sum = 0;
			foreach ( md_inv_usage( 'team', $a['from'], $a['to'] ) as $r ) {
				$out['rows'][] = array( md_inv_team_name( $r->k ), (int) $r->n, (int) $r->qty, (int) $r->amount );
				$sum += (int) $r->amount;
			}
			$out['rows'][] = array( '합계', '', '', $sum );
			$out['rows'][] = array( '', '', '', '' );
			$out['rows'][] = array( $L['label_cat2'], '출고 건수', '수량', '사용금액' );
			foreach ( md_inv_usage( 'cat2', $a['from'], $a['to'] ) as $r ) {
				$out['rows'][] = array( $r->k ? md_inv_cat_name( $r->k ) : '(미지정)', (int) $r->n, (int) $r->qty, (int) $r->amount );
			}
			$out['rows'][] = array( '', '', '', '' );
			$out['rows'][] = array( '품목 (상위 100)', '출고 건수', '수량', '사용금액' );
			foreach ( md_inv_usage( 'item', $a['from'], $a['to'], 0, 100 ) as $r ) {
				$it = md_inv_item( $r->k );
				$out['rows'][] = array( $it ? $it->name : '#' . $r->k, (int) $r->n, (int) $r->qty, (int) $r->amount );
			}
			break;

		case 'monthly':
			$months = (int) md_inv_set( 'stats_months' );
			$keys   = array_keys( md_inv_usage_monthly( $months ) );
			$out['head'] = array_merge( array( '팀' ), $keys, array( '합계' ) );
			$out['width'] = array_merge( array( 18 ), array_fill( 0, count( $keys ) + 1, 12 ) );
			foreach ( range( 1, count( $keys ) + 1 ) as $i ) { $out['num'][ $i ] = 'won'; }
			$col = array_fill_keys( $keys, 0 );
			foreach ( md_inv_teams( false ) as $tm ) {
				$m = md_inv_usage_monthly( $months, $tm->id );
				if ( ! array_sum( $m ) ) { continue; }
				$out['rows'][] = array_merge( array( $tm->name ), array_values( $m ), array( array_sum( $m ) ) );
				foreach ( $m as $k => $v ) { $col[ $k ] += $v; }
			}
			$out['rows'][] = array_merge( array( '합계' ), array_values( $col ), array( array_sum( $col ) ) );
			break;

		case 'prepaid':
			/* v6.5 · 「환불 · 정정」 칸 — 잔액 = 쓸 수 있는 금액 합계 − 차감 + 반품 + 환불 · 정정 (칸만 더해도 맞게) */
			$out['head']  = array( '업체', '입금 (낸 돈)', '적립', '조정', '쓸 수 있는 금액 합계', '차감 (입고)', '환원 (반품)', '환불 · 정정 (잔액으로)', '잔액', '주문 중', '쓸 수 있는 잔액', '마지막 입고', '한 달 평균 차감', '소진 예상', '알림 기준', '알림' );
			$out['num']   = array( 1 => 'won', 2 => 'won', 3 => 'won', 4 => 'won', 5 => 'won', 6 => 'won', 7 => 'won', 8 => 'won', 9 => 'won', 10 => 'won', 12 => 'won', 14 => 'won' );
			$out['width'] = array( 18, 14, 12, 12, 16, 14, 14, 14, 14, 12, 16, 12, 14, 12, 12, 8 );
			foreach ( md_inv_prepaid_summary() as $p ) {
				$out['rows'][] = array( $p->vendor->name, $p->paid, $p->bonus, $p->adjust, $p->deposit, $p->spent, $p->returned, $p->refund, $p->balance, $p->pending, $p->available, $p->last_in ? substr( $p->last_in, 0, 10 ) : '',
					$p->burn, null === $p->months_left ? '' : md_inv_months_txt( $p->months_left ), (int) $p->vendor->pp_alert, $p->alert ? '잔액 부족' : '' );
			}
			break;

		case 'deposits':
			$out['head']  = array( '입금일', '업체', '종류', '입금액', '쓸 수 있는 금액', '메모', '입력자', '입력 일시' );
			$out['num']   = array( 3 => 'won', 4 => 'won' );
			$out['width'] = array( 11, 18, 8, 14, 16, 30, 10, 16 );
			foreach ( md_inv_deposits() as $d ) {
				$out['rows'][] = array( $d->paid_on, md_inv_vendor_name( $d->vendor_id ), 'adjust' === $d->kind ? '조정' : ( 'credit' === $d->kind ? '보상 · 리베이트' : ( (int) $d->amount < 0 ? '돌려받음' : '입금' ) ), (int) $d->amount, (int) $d->credit, $d->note, $d->person, substr( $d->created_at, 0, 16 ) );
			}
			break;

		case 'passbook':
			$vids = ! empty( $a['vendor'] ) ? array( (int) $a['vendor'] ) : md_inv_prepaid_vendor_ids();
			if ( ! empty( $a['vendor'] ) ) { $out['title'] = '선납 거래 내역 ' . md_inv_vendor_name( $a['vendor'] ); }
			$out['head']  = array( '업체', '날짜', '내용', '품목', '수량', 'LOT', '들어옴', '나감', '잔액', '메모', '처리자' );
			$out['num']   = array( 4 => 'n', 6 => 'won', 7 => 'won', 8 => 'won' );
			$out['width'] = array( 16, 11, 10, 34, 6, 14, 13, 13, 14, 34, 10 );
			$from = isset( $a['from'] ) ? (string) $a['from'] : '';
			$to   = isset( $a['to'] ) ? (string) $a['to'] : '';
			foreach ( $vids as $vid ) {
				$pb = md_inv_passbook( $vid, $from, $to );
				if ( '' !== $from ) { $out['rows'][] = array( md_inv_vendor_name( $vid ), $from, '이전 잔액', '', '', '', '', '', $pb['open'], '', '' ); }
				foreach ( $pb['rows'] as $e ) {
					$out['rows'][] = array( md_inv_vendor_name( $vid ), $e->date, $e->label, $e->item, $e->qty ? $e->qty : '', $e->lot, $e->plus ? $e->plus : '', $e->minus ? $e->minus : '', $e->bal, $e->note, $e->person );
				}
			}
			break;

		case 'adjs':
			$out['head']  = array( '일시', '업체', '품목', '종류', '입고 일시', '입고 수량', '입고 단가', '금액 (+돌려받음 · −더 냄)', '무상 개수', '돌려받은 곳', '메모', '처리자' );
			$out['num']   = array( 5 => 'n', 6 => 'won', 7 => 'won', 8 => 'n' );
			$out['width'] = array( 16, 16, 34, 10, 16, 8, 11, 16, 7, 12, 30, 10 );
			foreach ( md_inv_adjs( array( 'from' => $a['from'], 'to' => $a['to'] ) ) as $x ) {
				$out['rows'][] = array( substr( $x->created_at, 0, 16 ), md_inv_vendor_name( $x->vendor_id ), $x->item_name, md_inv_adj_kinds()[ $x->kind ], substr( (string) $x->in_at, 0, 16 ), abs( (int) $x->in_qty ), (int) $x->in_price, (int) $x->amount, (int) $x->qty ? (int) $x->qty : '', 'balance' === $x->dest ? '선납 잔액' : '돈', $x->note, $x->person );
			}
			break;

		case 'lots':
			$out['head']  = array( '일시', '구분', '품목', '수량', 'LOT', '차트번호', '팀', '처리자', '받은 사람' );
			$out['num']   = array( 3 => 'n' );
			$out['width'] = array( 16, 6, 36, 6, 16, 12, 14, 10, 12 );
			foreach ( md_inv_lot_search( isset( $a['q'] ) ? $a['q'] : '', 5000 ) as $l ) {
				$out['rows'][] = array( substr( $l->created_at, 0, 16 ), md_inv_type_label( $l->type ), $l->item_name, (int) $l->qty, (string) $l->lot, (string) $l->chart, md_inv_team_name( $l->team_id ), $l->person, (string) $l->receiver );
			}
			break;

		case 'vendors':
			$out['head']  = array( '업체', '담당자', '연락처', '이메일', '온라인몰 · 주문 방법', '취급 품목', '비고', '선납', '사용' );
			$out['width'] = array( 18, 12, 16, 20, 24, 20, 40, 6, 6 );
			foreach ( md_inv_vendors( false ) as $v ) {
				$out['rows'][] = array( $v->name, $v->contact, $v->phone, $v->email, (string) $v->shop_info, (string) $v->goods, (string) $v->note, $v->prepaid ? '선납' : '', $v->active ? '' : '사용 안 함' );
			}
			break;

		case 'teams':
			$out['head']  = array( '순서', '팀', '통계 표시', '사용' );
			$out['width'] = array( 6, 18, 9, 9 );
			foreach ( md_inv_teams( false ) as $i => $tm ) {
				$out['rows'][] = array( $i + 1, $tm->name, $tm->in_stats ? 'O' : '', $tm->active ? '' : '사용 안 함' );
			}
			break;

		case 'log':
			$out['head']  = array( '일시', '누가', '무엇을', '내용' );
			$out['width'] = array( 16, 12, 14, 80 );
			global $wpdb;
			foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . md_inv_t( 'log' ) . ' WHERE created_at >= %s AND created_at < %s ORDER BY id DESC LIMIT 50000', $a['from'] . ' 00:00:00', date( 'Y-m-d', strtotime( $a['to'] . ' +1 day' ) ) . ' 00:00:00' ) ) as $g ) {
				$out['rows'][] = array( substr( (string) $g->created_at, 0, 16 ), $g->person, $g->action, $g->detail );
			}
			break;

		case 'cats':
			$out['head']  = array( $L['label_cat1'], $L['label_cat2'], $L['label_cat3'], '비고', '사용' );
			$out['width'] = array( 16, 18, 16, 24, 9 );
			foreach ( md_inv_cats_of( 1, null, false ) as $c1 ) {
				$out['rows'][] = array( $c1->name, '', '', $c1->note, $c1->active ? '' : '사용 안 함' );
				foreach ( md_inv_cats_of( 2, $c1->id, false ) as $c2 ) {
					$out['rows'][] = array( $c1->name, $c2->name, '', $c2->note, $c2->active ? '' : '사용 안 함' );
					foreach ( md_inv_cats_of( 3, $c2->id, false ) as $c3 ) {
						$out['rows'][] = array( $c1->name, $c2->name, $c3->name, $c3->note, $c3->active ? '' : '사용 안 함' );
					}
				}
			}
			break;
	}
	return $out;
}

/* ============================================================
 * 엑셀 (.xlsx)
 * ============================================================ */

/** zip 만들기 — [ 경로 => 내용 ] */
function md_inv_zip( $files ) {
	$data = '';
	$cd   = '';
	$n    = 0;
	$t    = getdate( current_time( 'timestamp' ) );
	$dtime = ( ( $t['hours'] << 11 ) | ( $t['minutes'] << 5 ) | ( $t['seconds'] >> 1 ) ) & 0xFFFF;
	$ddate = ( ( ( $t['year'] - 1980 ) << 9 ) | ( $t['mon'] << 5 ) | $t['mday'] ) & 0xFFFF;
	foreach ( $files as $path => $content ) {
		$crc  = crc32( $content );
		$comp = gzdeflate( $content, 6 );
		$method = 8;
		if ( false === $comp ) { $comp = $content; $method = 0; }
		$off  = strlen( $data );
		$data .= pack( 'VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $dtime, $ddate, $crc, strlen( $comp ), strlen( $content ), strlen( $path ), 0 ) . $path . $comp;
		$cd   .= pack( 'VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, $method, $dtime, $ddate, $crc, strlen( $comp ), strlen( $content ), strlen( $path ), 0, 0, 0, 0, 32, $off ) . $path;
		$n++;
	}
	return $data . $cd . pack( 'VvvvvVVv', 0x06054b50, 0, 0, $n, $n, strlen( $cd ), strlen( $data ), 0 );
}

function md_inv_xml( $s ) {
	$s = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $s );
	return htmlspecialchars( $s, ENT_QUOTES | ENT_XML1, 'UTF-8' );
}

function md_inv_col_letter( $i ) {
	$s = '';
	$i++;
	while ( $i > 0 ) { $m = ( $i - 1 ) % 26; $s = chr( 65 + $m ) . $s; $i = (int) ( ( $i - $m ) / 26 ); }
	return $s;
}

/** 시트 이름 — 엑셀 규칙(31자, 금지 문자 없음, 중복 없음) */
function md_inv_sheet_name( $name, &$used ) {
	$n = trim( preg_replace( '/[\[\]\*\?\/\\\\:]/u', ' ', (string) $name ) );
	if ( '' === $n ) { $n = '시트'; }
	$n = mb_substr( $n, 0, 28 );
	$base = $n; $i = 2;
	while ( isset( $used[ mb_strtolower( $n ) ] ) ) { $n = mb_substr( $base, 0, 26 ) . ' ' . $i++; }
	$used[ mb_strtolower( $n ) ] = 1;
	return $n;
}

/**
 * 여러 표를 한 엑셀 파일로.
 * @param array $sheets md_inv_dataset() 결과 목록
 * @return string xlsx 바이너리
 */
function md_inv_xlsx( $sheets ) {
	$used  = array();
	$files = array();
	$wbs   = '';
	$rels  = '';
	$ct    = '';
	foreach ( array_values( $sheets ) as $i => $ds ) {
		$n    = $i + 1;
		$name = md_inv_sheet_name( $ds['title'], $used );
		$wbs .= '<sheet name="' . md_inv_xml( $name ) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
		$rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
		$ct  .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
		$files[ 'xl/worksheets/sheet' . $n . '.xml' ] = md_inv_xlsx_sheet( $ds );
	}
	$k = count( $sheets );
	$rels .= '<Relationship Id="rId' . ( $k + 1 ) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

	$files['[Content_Types].xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
		. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
		. '<Default Extension="xml" ContentType="application/xml"/>'
		. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
		. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
		. '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
		. $ct . '</Types>';
	$files['_rels/.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
		. '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
		. '</Relationships>';
	$files['docProps/core.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
		. '<dc:title>문치과병원 재료실</dc:title><dc:creator>문치과병원 직원 라운지</dc:creator>'
		. '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate( 'Y-m-d\TH:i:s\Z' ) . '</dcterms:created></cp:coreProperties>';
	$files['xl/workbook.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
		. '<bookViews><workbookView/></bookViews><sheets>' . $wbs . '</sheets></workbook>';
	$files['xl/_rels/workbook.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
	/* 서식: 0 기본 · 1 머리글(굵게, 옅은 배경, 테두리) · 2 수량 #,##0 · 3 금액 #,##0 */
	$files['xl/styles.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
		. '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
		. '<fonts count="2"><font><sz val="11"/><name val="맑은 고딕"/><family val="2"/></font><font><b/><sz val="11"/><name val="맑은 고딕"/><family val="2"/></font></fonts>'
		. '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF4F2"/><bgColor indexed="64"/></patternFill></fill></fills>'
		. '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top/><bottom style="thin"><color rgb="FF9DB8B3"/></bottom><diagonal/></border></borders>'
		. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
		. '<cellXfs count="4">'
		. '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
		. '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
		. '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
		. '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
		. '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

	return md_inv_zip( $files );
}

function md_inv_xlsx_sheet( $ds ) {
	$cols = max( 1, count( $ds['head'] ) );
	$xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
		. '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
		. '<sheetFormatPr defaultRowHeight="16.5"/>';
	if ( ! empty( $ds['width'] ) ) {
		$xml .= '<cols>';
		foreach ( $ds['width'] as $i => $w ) { $xml .= '<col min="' . ( $i + 1 ) . '" max="' . ( $i + 1 ) . '" width="' . ( (float) $w + 2 ) . '" customWidth="1"/>'; }
		$xml .= '</cols>';
	}
	$xml .= '<sheetData>';
	$all = array_merge( array( $ds['head'] ), $ds['rows'] );
	foreach ( $all as $ri => $row ) {
		$rn = $ri + 1;
		$xml .= '<row r="' . $rn . '">';
		foreach ( array_values( $row ) as $ci => $v ) {
			$ref = md_inv_col_letter( $ci ) . $rn;
			if ( 0 === $ri ) {
				$xml .= '<c r="' . $ref . '" s="1" t="inlineStr"><is><t>' . md_inv_xml( $v ) . '</t></is></c>';
				continue;
			}
			if ( null === $v || '' === $v ) { continue; }
			$is_num = ( is_int( $v ) || is_float( $v ) ) && isset( $ds['num'][ $ci ] );
			if ( $is_num ) {
				$s = 'won' === $ds['num'][ $ci ] ? 3 : 2;
				$xml .= '<c r="' . $ref . '" s="' . $s . '"><v>' . ( 0 + $v ) . '</v></c>';
			} elseif ( is_int( $v ) || is_float( $v ) ) {
				$xml .= '<c r="' . $ref . '"><v>' . ( 0 + $v ) . '</v></c>';
			} else {
				$xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . md_inv_xml( $v ) . '</t></is></c>';
			}
		}
		$xml .= '</row>';
	}
	$xml .= '</sheetData>';
	if ( count( $all ) > 1 ) {
		$xml .= '<autoFilter ref="A1:' . md_inv_col_letter( $cols - 1 ) . count( $all ) . '"/>';
	}
	$xml .= '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/></worksheet>';
	return $xml;
}

/** 보고서 엑셀 — 표 여러 개를 한 파일로 */
function md_inv_report_xlsx( $from, $to, $keys = null ) {
	if ( null === $keys ) { $keys = array( 'items', 'low', 'ledger', 'requests', 'orders', 'usage', 'monthly', 'prepaid', 'deposits', 'passbook', 'adjs', 'vendors' ); }
	$sheets = array();
	foreach ( $keys as $k ) { $sheets[] = md_inv_dataset( $k, array( 'from' => $from, 'to' => $to ) ); }
	return md_inv_xlsx( $sheets );
}

/**
 * v6.8 · 재료실 전체 엑셀 — 처음부터 오늘까지 모든 표를 한 파일로 (원장 지시 2026-10-06)
 * 주소 md_inv_all_url() 은 즐겨찾기에 넣어 두고 쓸 수 있다 (관리자 로그인 필요).
 */
function md_inv_all_xlsx() {
	$keys = array( 'items', 'low', 'ledger', 'requests', 'orders', 'prices', 'usage', 'monthly', 'prepaid', 'deposits', 'passbook', 'adjs', 'lots', 'vendors', 'teams', 'cats', 'log' );
	return md_inv_report_xlsx( '2000-01-01', current_time( 'Y-m-d' ), $keys );
}

function md_inv_all_url() {
	return md_inv_url( array( 'md_inv_dl' => 'all' ) );
}

function md_inv_send_xlsx( $bin, $filename ) {
	nocache_headers();
	header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	header( 'Content-Disposition: attachment; filename="moondental-inventory.xlsx"; filename*=UTF-8\'\'' . rawurlencode( $filename ) );
	header( 'Content-Length: ' . strlen( $bin ) );
	echo $bin; // phpcs:ignore
	exit;
}
