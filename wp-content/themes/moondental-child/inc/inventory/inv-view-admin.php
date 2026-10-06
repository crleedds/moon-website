<?php
/**
 * 재고관리 v5 — 관리자 화면
 *   할 일 · 재고 · 품목 상세 · 실사 · 주문 · 선납 · 입출고 기록
 *
 * 버튼마다 대화상자(dialog) 하나씩을 화면 아래에 두고, 버튼의 data-set 값으로 칸을 채운다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 공통 대화상자
 * ============================================================ */

function md_inv_dlg_open( $id, $title, $action, $extra = '' ) {
	echo '<dialog class="iv-dlg" id="' . esc_attr( $id ) . '"><form method="post"' . $extra . '>';
	md_inv_hidden( $action );
	echo '<input type="hidden" name="id" value="">';
	echo '<div class="iv-dlg__head"><b>' . esc_html( $title ) . '</b><button type="button" class="iv-x" data-close aria-label="닫기">' . md_inv_icon( 'x' ) . '</button></div>';
	echo '<p class="iv-dlg__what" data-t="what"></p>';
}

function md_inv_dlg_close( $button = '저장', $cls = 'primary' ) {
	echo '<div class="iv-dlg__foot"><button type="button" class="iv-btn iv-btn--ghost" data-close>닫기</button><button class="iv-btn iv-btn--' . esc_attr( $cls ) . '">' . esc_html( $button ) . '</button></div></form></dialog>';
}

/** 화면에서 쓰는 대화상자들 — 한 번만 */
function md_inv_admin_dialogs( $which ) {
	$which = array_flip( $which );
	if ( array_intersect_key( $which, array_flip( array( 'release', 'receive', 'in', 'out' ) ) ) ) { md_inv_track_map_script(); md_inv_bc_map_script(); md_inv_scan_dialog(); }
	if ( isset( $which['return'] ) ) { md_inv_adj_dialogs(); } /* 입출고 표가 있는 화면 — 입고 뒤 환불 · 정정 · 교환 */
	if ( array_intersect_key( $which, array_flip( array( 'order', 'in' ) ) ) ) { md_inv_price_map_script(); } /* v6.4 · 품목 고르면 단가 자동 */
	if ( isset( $which['release'] ) ) {
		md_inv_dlg_open( 'dlg-release', '출고', 'req_release' );
		echo '<label class="iv-f"><span>내줄 수량</span><input class="iv-input" type="number" inputmode="numeric" name="qty" min="1" required></label>';
		echo '<label class="iv-f"><span>받은 사람' . ( md_inv_set( 'out_need_receiver' ) ? ' <em>*</em>' : '' ) . '</span><input class="iv-input" name="receiver" maxlength="40" placeholder="실제로 받아 간 사람"' . ( md_inv_set( 'out_need_receiver' ) ? ' required' : '' ) . '></label>';
		echo '<input type="hidden" name="track_item" value="" data-fill><input type="hidden" name="item_ref" value="" data-fill>';
		md_inv_verify_fields();
		md_inv_lot_fields( 'out' );
		echo '<p class="iv-help" data-t="hint"></p>';
		echo '<label class="iv-f"><span>메모 (요청한 팀에 보임)</span><input class="iv-input" name="note" maxlength="300"></label>';
		md_inv_dlg_close( '출고' );
	}
	if ( isset( $which['reject'] ) ) {
		md_inv_dlg_open( 'dlg-reject', '반려', 'req_reject' );
		echo '<label class="iv-f"><span>반려 사유 <em>*</em> (요청한 팀에 보임)</span><input class="iv-input" name="reason" maxlength="300" required placeholder="예: 재고 충분 · 같은 요청 중복 · 다른 품목으로 대체"></label>';
		md_inv_dlg_close( '반려', 'danger' );
	}
	if ( isset( $which['reqedit'] ) ) {
		md_inv_dlg_open( 'dlg-reqedit', '요청 고치기', 'req_update' );
		echo '<div class="iv-grid2"><label class="iv-f"><span>요청 수량</span><input class="iv-input" type="number" inputmode="numeric" name="qty" min="1" required></label>';
		echo '<label class="iv-f"><span>팀</span>'; md_inv_team_select( 'team_id', 0, false, '그대로' ); echo '</label></div>';
		echo '<label class="iv-f"><span>다른 품목으로 바꾸기 (선택)</span>'; md_inv_item_picker( 'item_id', false ); echo '</label>';
		echo '<label class="iv-f"><span>처리 메모 (요청한 팀에 보임)</span><input class="iv-input" name="admin_note" maxlength="300"></label>';
		md_inv_dlg_close();
	}
	if ( isset( $which['reqdone'] ) ) {
		md_inv_dlg_open( 'dlg-reqdone', '처리 완료 (재고 기록 없이)', 'req_complete' );
		echo '<p class="iv-help">목록에 없는 품목을 바로 구매해 전달한 경우처럼, 재고를 거치지 않고 끝낼 때 씁니다.</p>';
		echo '<label class="iv-f"><span>메모 (요청한 팀에 보임)</span><input class="iv-input" name="note" maxlength="300" placeholder="예: 10/5 구매해 전달"></label>';
		md_inv_dlg_close( '처리 완료' );
	}
	if ( isset( $which['reqreg'] ) ) {
		md_inv_dlg_open( 'dlg-reqreg', '품목으로 등록하고 연결', 'req_register' );
		md_inv_item_form_fields( null );
		md_inv_dlg_close( '등록' );
	}
	if ( isset( $which['reqorder'] ) ) {
		md_inv_dlg_open( 'dlg-reqorder', '이 요청으로 주문', 'req_order' );
		md_inv_order_fields();
		md_inv_dlg_close( '주문 넣기' );
	}
	if ( isset( $which['order'] ) ) {
		echo '<dialog class="iv-dlg" id="dlg-order"><form method="post">';
		md_inv_hidden( 'ord_create' );
		echo '<div class="iv-dlg__head"><b>주문 넣기</b><button type="button" class="iv-x" data-close aria-label="닫기">' . md_inv_icon( 'x' ) . '</button></div>';
		echo '<p class="iv-dlg__what" data-t="what"></p>';
		echo '<label class="iv-f"><span>품목 <em>*</em></span>'; md_inv_item_picker( 'item_id', true ); echo '</label>';
		md_inv_order_fields();
		md_inv_dlg_close( '주문 넣기' );
	}
	if ( isset( $which['receive'] ) ) {
		md_inv_dlg_open( 'dlg-receive', '입고', 'ord_receive' );
		/* v6.5 · 거래명세서처럼 — 총 수량(무상 포함) · 무상 수량 · 개당 단가 · 실제로 낸 금액(배송비 포함), 배송비 등 · 실제 개당 금액은 자동 */
		md_inv_receipt_fields( array( 'mode' => 'receive' ) );
		echo '<input type="hidden" name="track_item" value="" data-fill><input type="hidden" name="item_ref" value="" data-fill>';
		echo '<input type="hidden" name="allow_more" value=""><input type="hidden" name="ord_left" value="" data-fill><input type="hidden" name="ord_price" value="" data-fill><input type="hidden" name="ord_amt_left" value="" data-fill>';
		md_inv_verify_fields();
		md_inv_lot_fields( 'in' );
		/* v6.4 · 원장 지시 — 「덜 왔지만 여기서 마감」(주문 목록의 「여기서 마감」 버튼이 같은 일) · 「주문보다 많이 들어옴」(입고 누를 때 확인 창) 체크 없앰
		   v6.5 · 「전부 무상 제공」 체크도 없앰 — 무상 수량 = 총 수량이면 전부 무상 */
		echo '<p class="iv-help" data-t="hint"></p>';
		echo '<label class="iv-f"><span>메모 <small>(선택 — 거래명세서 번호 · 무상 사유 등)</small></span><input class="iv-input" name="note" maxlength="300" placeholder="예: 거래명세서 번호"></label>';
		md_inv_dlg_close( '입고' );
	}
	if ( isset( $which['ordedit'] ) ) {
		md_inv_dlg_open( 'dlg-ordedit', '주문 고치기', 'ord_update' );
		md_inv_order_fields( false );
		md_inv_dlg_close();
	}
	if ( isset( $which['ordcancel'] ) ) {
		md_inv_dlg_open( 'dlg-ordcancel', '주문 취소', 'ord_cancel' );
		echo '<p class="iv-help">일부라도 들어온 주문은 취소 대신 「여기서 마감」이 됩니다.</p>';
		echo '<label class="iv-f"><span>사유</span><input class="iv-input" name="why" maxlength="200" placeholder="예: 품절 · 다른 업체로 주문"></label>';
		md_inv_dlg_close( '주문 취소', 'danger' );
	}
	if ( isset( $which['in'] ) ) {
		echo '<dialog class="iv-dlg" id="dlg-in"><form method="post">';
		md_inv_hidden( 'stock_in' );
		echo '<div class="iv-dlg__head"><b>입고 (주문 없이)</b><button type="button" class="iv-x" data-close aria-label="닫기">' . md_inv_icon( 'x' ) . '</button></div>';
		echo '<p class="iv-help">주문해 둔 것이 들어왔으면 「할 일 › 입고 대기」에서 입고를 눌러야 주문이 닫힙니다.</p>';
		echo '<label class="iv-f"><span>품목 <em>*</em></span>'; md_inv_item_picker( 'item_id', true ); echo '</label>';
		md_inv_receipt_fields( array( 'mode' => 'in' ) ); /* v6.5 · 총 수량 · 무상 · 단가 · 실제로 낸 금액 */
		md_inv_lot_fields( 'in' );
		echo '<label class="iv-f"><span>메모 <small>(선택 — 거래명세서 번호 · 무상 사유 등)</small></span><input class="iv-input" name="note" maxlength="300"></label>';
		md_inv_dlg_close( '입고' );
	}
	if ( isset( $which['out'] ) ) {
		echo '<dialog class="iv-dlg" id="dlg-out"><form method="post">';
		md_inv_hidden( 'stock_out' );
		echo '<div class="iv-dlg__head"><b>바로 출고 (요청 없이)</b><button type="button" class="iv-x" data-close aria-label="닫기">' . md_inv_icon( 'x' ) . '</button></div>';
		echo '<label class="iv-f"><span>품목 <em>*</em></span>'; md_inv_item_picker( 'item_id', true ); echo '</label>';
		echo '<div class="iv-grid2"><label class="iv-f"><span>수량 <em>*</em></span><input class="iv-input" type="number" inputmode="numeric" name="qty" min="1" required></label>';
		echo '<label class="iv-f"><span>팀 <em>*</em></span>'; md_inv_team_select( 'team_id', 0, true ); echo '</label></div>';
		echo '<label class="iv-f"><span>받은 사람' . ( md_inv_set( 'out_need_receiver' ) ? ' <em>*</em>' : '' ) . '</span><input class="iv-input" name="receiver" maxlength="40"' . ( md_inv_set( 'out_need_receiver' ) ? ' required' : '' ) . '></label>';
		md_inv_verify_fields();
		md_inv_lot_fields( 'out' );
		echo '<label class="iv-f"><span>메모</span><input class="iv-input" name="note" maxlength="300"></label>';
		md_inv_dlg_close( '출고' );
	}
	if ( isset( $which['adjust'] ) ) {
		echo '<dialog class="iv-dlg" id="dlg-adjust"><form method="post">';
		md_inv_hidden( 'stock_adjust' );
		echo '<div class="iv-dlg__head"><b>실사 (센 수량으로 맞추기)</b><button type="button" class="iv-x" data-close aria-label="닫기">' . md_inv_icon( 'x' ) . '</button></div>';
		echo '<label class="iv-f"><span>품목 <em>*</em></span>'; md_inv_item_picker( 'item_id', true ); echo '</label>';
		echo '<p class="iv-help" data-t="hint"></p>';
		echo '<label class="iv-f"><span>실제로 센 수량 <em>*</em></span><input class="iv-input" type="number" inputmode="numeric" name="counted" min="0" required></label>';
		echo '<label class="iv-f"><span>사유' . ( md_inv_set( 'adjust_need_note' ) ? ' <em>*</em>' : '' ) . '</span><input class="iv-input" name="note" maxlength="300" placeholder="예: 정기 실사 · 파손 · 유효기간 만료"' . ( md_inv_set( 'adjust_need_note' ) ? ' required' : '' ) . '></label>';
		md_inv_dlg_close( '맞추기' );
	}
	if ( isset( $which['return'] ) ) {
		echo '<dialog class="iv-dlg" id="dlg-return"><form method="post">';
		md_inv_hidden( 'stock_return' );
		echo '<input type="hidden" name="in_id" value="">';
		echo '<div class="iv-dlg__head"><b>반품 (업체로 돌려보냄)</b><button type="button" class="iv-x" data-close aria-label="닫기">' . md_inv_icon( 'x' ) . '</button></div>';
		echo '<p class="iv-dlg__what" data-t="what"></p><p class="iv-help" data-t="hint"></p>';
		echo '<div class="iv-grid2"><label class="iv-f"><span>반품 수량 <em>*</em></span><input class="iv-input" type="number" inputmode="numeric" name="qty" min="1" required data-ret="qty"></label>';
		/* v6.5 · 돌려받는 금액 — 기본은 그 입고에서 실제로 낸 금액(배송비 포함)에서 아직 돌려받지 않은 몫. 업체가 다르게 돌려주면 고친다 */
		echo '<label class="iv-f"><span>돌려받는 금액 (원)</span><input class="iv-input" name="amount" inputmode="numeric" autocomplete="off" data-ret="amount"></label></div>';
		echo '<input type="hidden" name="ret_net" value="" data-fill><input type="hidden" name="ret_units" value="" data-fill>';
		echo '<label class="iv-f"><span>사유 <em>*</em></span><input class="iv-input" name="note" maxlength="300" required placeholder="예: 불량 · 오배송"></label>';
		md_inv_dlg_close( '반품 기록', 'danger' );
	}
	if ( isset( $which['void'] ) ) {
		md_inv_dlg_open( 'dlg-void', '기록 취소', 'ledger_void' );
		echo '<p class="iv-help">잘못 넣은 기록을 재고·통계에서 뺍니다. 지우지 않고 「취소됨」으로 남습니다. 출고 기록을 취소하면 그 요청은 다시 대기로 돌아갑니다.</p>';
		echo '<label class="iv-f"><span>사유</span><input class="iv-input" name="why" maxlength="200" placeholder="예: 수량 잘못 입력"></label>';
		md_inv_dlg_close( '기록 취소', 'danger' );
	}
	if ( isset( $which['item'] ) ) {
		echo '<dialog class="iv-dlg iv-dlg--wide" id="dlg-item"><form method="post">';
		md_inv_hidden( 'item_save' );
		echo '<input type="hidden" name="id" value="">';
		echo '<div class="iv-dlg__head"><b data-t="title">품목 등록</b><button type="button" class="iv-x" data-close aria-label="닫기">' . md_inv_icon( 'x' ) . '</button></div>';
		md_inv_item_form_fields( null, true );
		md_inv_dlg_close();
	}
}

/** 품목 입력칸 (등록 · 고치기 · 요청에서 등록) */
function md_inv_item_form_fields( $it, $with_open = false ) {
	echo '<label class="iv-f"><span>품목명 <em>*</em></span><input class="iv-input" name="name" maxlength="200" required value="' . esc_attr( $it ? $it->name : '' ) . '"></label>';
	echo '<div class="iv-grid2">';
	echo '<label class="iv-f"><span>업체</span>'; md_inv_vendor_select( 'vendor_id', $it ? $it->vendor_id : 0 ); echo '</label>';
	echo '<label class="iv-f"><span>단위</span><input class="iv-input" name="unit" maxlength="20" list="iv-unit-dl" value="' . esc_attr( $it ? $it->unit : '' ) . '" placeholder="ea · box · 갑"></label>';
	echo '<label class="iv-f"><span>단가 (원)</span><input class="iv-input" name="price" inputmode="numeric" value="' . esc_attr( $it ? $it->price : '' ) . '"></label>';
	echo '<label class="iv-f"><span>안전재고 (이보다 적으면 부족)</span><input class="iv-input" name="min_stock" type="number" inputmode="numeric" min="0" value="' . esc_attr( $it ? $it->min_stock : '' ) . '"></label>';
	echo '</div>';
	md_inv_cat_selects( $it ? $it->cat1 : 0, $it ? $it->cat2 : 0, $it ? $it->cat3 : 0 );
	echo '<div class="iv-grid2">';
	echo '<label class="iv-f"><span>보관 위치</span><input class="iv-input" name="location" maxlength="100" list="iv-loc-dl" value="' . esc_attr( $it ? (string) $it->location : '' ) . '" placeholder="예: 9층 재료실 2번 선반"></label>';
	static $locs = false;
	if ( ! $locs ) { $locs = true; echo '<datalist id="iv-loc-dl">'; foreach ( md_inv_locations() as $lc ) { echo '<option value="' . esc_attr( $lc ) . '"></option>'; } echo '</datalist>'; }
	echo '<label class="iv-f"><span>바코드</span><span class="iv-inline"><input class="iv-input" name="barcode" maxlength="100" value="' . esc_attr( $it ? $it->barcode : '' ) . '"><button type="button" class="iv-btn iv-btn--icon" data-scanto="barcode" aria-label="바코드 스캔">' . md_inv_icon( 'scan', 18 ) . '</button></span></label>';
	if ( $with_open ) {
		echo '<label class="iv-f" data-newonly><span>처음 수량 (새 품목일 때만)</span><input class="iv-input" name="open_qty" type="number" inputmode="numeric" min="0"></label>';
	}
	echo '</div>';
	echo '<label class="iv-f"><span>비고</span><textarea class="iv-input" name="note" rows="2" maxlength="2000">' . esc_textarea( $it ? (string) $it->note : '' ) . '</textarea></label>';
	echo '<input type="hidden" name="track_lot" value="0"><label class="iv-check"><input type="checkbox" name="track_lot" value="1"' . checked( $it && (int) $it->track_lot, true, false ) . '> LOT · 환자 차트번호 추적 (임플란트처럼 몸에 들어가는 품목)</label>';
	static $units = false;
	if ( ! $units ) {
		$units = true;
		echo '<datalist id="iv-unit-dl">';
		foreach ( array( 'ea', 'box', '갑', '팩', '봉', '병', '개', '각', '롤', '세트', '통', '대' ) as $u ) { echo '<option value="' . esc_attr( $u ) . '"></option>'; }
		echo '</datalist>';
	}
}

function md_inv_order_fields( $with_qty_req = true ) {
	/* v6.5 · 수량 · 받기로 한 무상 · 단가 · 주문 합계(배송비 · 할인 포함) — 입고 창과 같은 칸 */
	md_inv_receipt_fields( array( 'mode' => 'order' ) );
	echo '<p class="iv-help" data-t="hint"></p>';
	echo '<label class="iv-f"><span>메모</span><input class="iv-input" name="note" maxlength="300" placeholder="예: 온라인몰 주문 · 전화 주문"></label>';
}

/** 요청 한 건의 버튼 묶음 (할 일) */
function md_inv_req_actions( $r ) {
	$what = $r->name . ' · ' . md_inv_team_name( $r->team_id ) . ' ' . $r->requester . ' · ' . $r->qty . $r->unit;
	$base = array( 'id' => (int) $r->id, 'what' => $what );
	echo '<div class="iv-actions">';
	if ( ! $r->item_id ) {
		$set = array_merge( $base, array( 'name' => $r->custom_name, 'unit' => $r->custom_unit, 'price' => $r->custom_price ? $r->custom_price : '' ) );
		echo '<button type="button" class="iv-btn iv-btn--primary iv-btn--sm" data-dlg="dlg-reqreg" data-set="' . esc_attr( wp_json_encode( $set ) ) . '">품목 등록</button>';
		echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-dlg="dlg-reqdone" data-set="' . esc_attr( wp_json_encode( $base ) ) . '">처리 완료</button>';
	} else {
		$stock = (int) $r->stock;
		$need_rcv = (bool) md_inv_set( 'out_need_receiver' ) || (int) $r->track_lot; /* v6.0 · LOT 추적 품목은 차트번호를 받으려고 창으로 */
		$base['track_item'] = (int) $r->track_lot ? (int) $r->item_id : '';
		$base['item_ref']   = (int) $r->item_id;
		if ( $need_rcv && ( $stock > 0 || md_inv_set( 'out_allow_negative' ) ) ) {
			$q1 = ( $stock >= $r->qty || md_inv_set( 'out_allow_negative' ) ) ? $r->qty : $stock;
			echo '<button type="button" class="iv-btn iv-btn--primary iv-btn--sm" data-dlg="dlg-release" data-set="' . esc_attr( wp_json_encode( array_merge( $base, array( 'qty' => $q1, 'qty@max' => $r->qty, 'receiver' => $r->requester, 'hint' => $q1 < $r->qty ? '재고가 ' . $stock . '개라 있는 만큼 먼저 내주고, 남은 ' . ( $r->qty - $q1 ) . '개는 대기로 남깁니다.' : '' ) ) ) ) . '">' . ( $q1 < $r->qty ? '있는 만큼 ' . (int) $q1 . '개 출고' : (int) $r->qty . '개 출고' ) . '</button>';
		} elseif ( $stock >= $r->qty || md_inv_set( 'out_allow_negative' ) ) {
			echo '<form method="post" class="iv-inline-form">';
			md_inv_hidden( 'req_release' );
			echo '<input type="hidden" name="id" value="' . (int) $r->id . '"><input type="hidden" name="qty" value="' . (int) $r->qty . '">';
			echo '<button class="iv-btn iv-btn--primary iv-btn--sm">' . (int) $r->qty . '개 출고</button></form>';
		} elseif ( $stock > 0 && ! $need_rcv ) {
			echo '<form method="post" class="iv-inline-form" data-confirm="' . esc_attr( '재고 ' . $stock . '개만 먼저 내주고, 남은 ' . ( $r->qty - $stock ) . '개는 대기로 남깁니다.' ) . '">';
			md_inv_hidden( 'req_release' );
			echo '<input type="hidden" name="id" value="' . (int) $r->id . '"><input type="hidden" name="qty" value="' . $stock . '">';
			echo '<button class="iv-btn iv-btn--primary iv-btn--sm">있는 만큼 ' . $stock . '개 출고</button></form>';
		}
		if ( $stock < $r->qty && ! (int) $r->ord_id ) {
			$need = max( 1, $r->qty - max( 0, $stock ) );
			$pp   = md_inv_prepaid_after_order( $r->item_id, $need * (int) $r->price );
			$set  = array_merge( $base, array( 'qty' => $need, 'price' => (int) $r->price, 'total' => '', 'free_qty' => '', 'hint' => '재고 ' . $stock . ' · 요청 ' . $r->qty . ' — 부족한 ' . $need . '개를 채웁니다. 업체: ' . md_inv_vendor_name( $r->vendor_id ) . ( $pp ? ' · 선납 쓸 수 있는 잔액 ' . md_inv_won( $pp->available ) . ' → 이 주문 뒤 ' . md_inv_won( $pp->after ) . ( $pp->after < 0 ? ' (모자람!)' : '' ) : '' ) ) );
			echo '<button type="button" class="iv-btn iv-btn--' . ( $stock > 0 ? 'ghost' : 'primary' ) . ' iv-btn--sm" data-dlg="dlg-reqorder" data-set="' . esc_attr( wp_json_encode( $set ) ) . '">주문</button>';
		}
		if ( $stock > 0 || md_inv_set( 'out_allow_negative' ) ) :
		$set = array_merge( $base, array( 'qty' => min( $r->qty, max( 1, $stock ) ), 'qty@max' => $r->qty, 'receiver' => $r->requester, 'hint' => '요청 ' . $r->qty . '개 · 지금 재고 ' . $stock . '개. 요청보다 적게 내주면 남은 수량은 대기로 남습니다.' ) );
		echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-dlg="dlg-release" data-set="' . esc_attr( wp_json_encode( $set ) ) . '">수량 정해 출고</button>';
		endif;
	}
	echo '<details class="iv-more-menu"><summary class="iv-btn iv-btn--ghost iv-btn--sm" aria-label="더보기">' . md_inv_icon( 'more', 16 ) . '</summary><div>';
	echo '<button type="button" class="iv-menu-a" data-dlg="dlg-reqedit" data-set="' . esc_attr( wp_json_encode( array_merge( $base, array( 'qty' => $r->qty, 'admin_note' => $r->admin_note ) ) ) ) . '">고치기 (수량 · 팀 · 품목)</button>';
	echo '<button type="button" class="iv-menu-a iv-danger" data-dlg="dlg-reject" data-set="' . esc_attr( wp_json_encode( $base ) ) . '">반려</button>';
	echo '<form method="post" data-confirm="이 요청을 취소할까요? (요청한 사람이 잘못 보낸 경우)">';
	md_inv_hidden( 'req_admin_cancel' );
	echo '<input type="hidden" name="id" value="' . (int) $r->id . '"><button class="iv-menu-a">요청 취소</button></form>';
	if ( $r->item_id ) { echo '<a class="iv-menu-a" href="' . esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $r->item_id ) ) ) . '">품목 보기</a>'; }
	echo '</div></details>';
	echo '</div>';
}

/* ============================================================
 * 할 일
 * ============================================================ */

function md_inv_view_todo() {
	$reqs = md_inv_reqs( array( 'status' => 'pending', 'limit' => 500, 'order' => 'old' ) );
	$ords = md_inv_ords( array( 'status' => 'ordered', 'limit' => 300 ) );
	$need = md_inv_need_order();
	$sec  = md_inv_get( 'sec', '' );
	?>
	<div class="iv-sum">
		<a class="iv-sum__c<?php echo $reqs ? ' is-hot' : ''; ?>" href="#iv-sec-req"><b><?php echo count( $reqs ); ?></b><span>출고 대기</span></a>
		<a class="iv-sum__c" href="#iv-sec-ord"><b><?php echo count( $ords ); ?></b><span>입고 대기</span></a>
		<a class="iv-sum__c" href="#iv-sec-need"><b><?php echo count( $need ); ?></b><span>주문 필요</span></a>
	</div>

	<section id="iv-sec-req" class="iv-sec">
		<h2 class="iv-h2">출고 대기 <span class="iv-n"><?php echo count( $reqs ); ?></span><?php if ( $reqs ) : ?> <a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'pick' ) ) ); ?>">출고 준비 목록 · 인쇄</a><?php endif; ?></h2>
		<?php if ( ! $reqs ) : md_inv_empty( '기다리는 요청이 없습니다.' ); else : ?>
			<form method="post" id="iv-bulk-release" data-confirm="선택한 요청을 요청 수량대로 모두 출고할까요?">
				<?php md_inv_hidden( 'req_release_many' ); ?>
				<div class="iv-bulkbar"><label class="iv-check"><input type="checkbox" data-checkall="ids[]"> 모두 고르기</label><button class="iv-btn iv-btn--primary iv-btn--sm" data-needcheck="ids[]">선택한 것 출고</button></div>
			</form>
			<div class="iv-cards">
			<?php
			$groups = array();
			foreach ( $reqs as $r ) { $groups[ $r->team_id . '|' . $r->requester . '|' . substr( $r->created_at, 0, 16 ) ][] = $r; }
			foreach ( $groups as $g ) :
				$r0 = $g[0];
				?>
				<div class="iv-group">
					<div class="iv-group__head"><b><?php echo esc_html( md_inv_team_name( $r0->team_id ) ); ?></b> · <?php echo esc_html( $r0->requester ); ?><?php echo md_inv_req_by_account( $r0 ) ? ' <span class="iv-tag iv-tag--acc" title="본인 계정으로 로그인해 보낸 신청">본인 계정</span>' : ''; ?> <span class="iv-muted"><?php echo esc_html( md_inv_ago( $r0->created_at ) ); ?></span></div>
					<?php foreach ( $g as $r ) :
						$short = $r->item_id && $r->stock < $r->qty;
						?>
						<article class="iv-card iv-card--todo<?php echo $r->urgent ? ' is-urgent' : ''; ?>">
							<?php if ( $r->item_id && ( $r->stock >= $r->qty || md_inv_set( 'out_allow_negative' ) ) ) : ?>
								<label class="iv-card__chk"><input type="checkbox" name="ids[]" value="<?php echo (int) $r->id; ?>" form="iv-bulk-release" aria-label="선택"></label>
							<?php else : ?><span class="iv-card__chk"></span><?php endif; ?>
							<div class="iv-card__main">
								<div class="iv-card__title">
									<?php if ( $r->item_id ) : ?><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $r->item_id ) ) ); ?>"><?php echo esc_html( $r->name ); ?></a><?php else : ?><?php echo esc_html( $r->name ); ?> <span class="iv-tag">목록에 없음</span><?php endif; ?>
									<?php $vn = $r->item_id ? ( (int) $r->vendor_id ? md_inv_vendor_name( $r->vendor_id ) : '' ) : (string) $r->custom_vendor; /* v6.9 · 업체가 있으면 품목 옆에 (원장 지시) */ if ( '' !== trim( $vn ) ) : ?><span class="iv-card__vendor"><?php echo esc_html( $vn ); ?></span><?php endif; ?>
									<?php if ( $r->urgent ) : ?><span class="iv-tag iv-tag--hot">급함</span><?php endif; ?>
								</div>
								<div class="iv-card__sub">
									요청 <b><?php echo (int) $r->qty; ?></b><?php echo esc_html( $r->unit ); ?>
									<?php if ( $r->item_id ) : ?> · 재고 <b class="<?php echo $short ? 'iv-danger' : ''; ?>"><?php echo (int) $r->stock; ?></b><?php endif; ?>
									<?php if ( (int) $r->ord_id ) : ?> · <span class="iv-accent">주문 중 <?php echo (int) $r->onord; ?></span><?php endif; ?>
									<?php if ( ! $r->item_id && $r->custom_price ) : ?> · <?php echo esc_html( md_inv_won( $r->custom_price ) ); ?><?php endif; ?>
								</div>
								<?php if ( '' !== $r->note ) : ?><div class="iv-card__note">“<?php echo esc_html( $r->note ); ?>”</div><?php endif; ?>
								<?php if ( '' !== $r->admin_note ) : ?><div class="iv-card__sub"><?php echo esc_html( $r->admin_note ); ?></div><?php endif; ?>
								<?php if ( ! $r->item_id && $r->custom_link ) : ?><div class="iv-card__sub"><a href="<?php echo esc_url( $r->custom_link ); ?>" target="_blank" rel="noopener noreferrer">구매 링크 열기 ↗</a></div><?php endif; ?>
							</div>
							<?php md_inv_req_actions( $r ); ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<section id="iv-sec-ord" class="iv-sec">
		<h2 class="iv-h2">입고 대기 <span class="iv-n"><?php echo count( $ords ); ?></span> <a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'receive' ) ) ); ?>"><?php echo md_inv_icon( 'scan', 16 ); // phpcs:ignore ?>바코드로 입고</a> <a class="iv-link" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'orders' ) ) ); ?>">주문 전체 보기</a></h2>
		<?php if ( ! $ords ) : md_inv_empty( '들어오기를 기다리는 주문이 없습니다.' ); else : md_inv_order_cards( $ords ); endif; ?>
	</section>

	<section id="iv-sec-need" class="iv-sec">
		<h2 class="iv-h2">주문 필요 <span class="iv-n"><?php echo count( $need ); ?></span> <a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'po' ) ) ); ?>">업체별로 주문 · 발주서</a></h2>
		<p class="iv-help">재고가 안전재고보다 적고 주문해 둔 것이 없는 품목<?php echo (int) md_inv_set( 'order_need_recent' ) ? ' 중 최근 ' . (int) md_inv_set( 'order_need_recent' ) . '주 안에 출고된 것' : ''; ?>입니다. <a class="iv-link" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock', 'ist' => 'low' ) ) ); ?>">부족한 품목 전체 보기</a></p>
		<?php if ( ! $need ) : md_inv_empty( '지금 주문할 품목이 없습니다.' ); else : ?>
			<form method="post" data-confirm="선택한 품목을 적힌 수량대로 주문 목록에 넣을까요?">
				<?php md_inv_hidden( 'ord_many' ); ?>
				<div class="iv-bulkbar"><label class="iv-check"><input type="checkbox" data-checkall="ids[]"> 모두 고르기</label><button class="iv-btn iv-btn--primary iv-btn--sm" data-needcheck="ids[]">선택한 것 주문</button></div>
				<div class="iv-table-wrap"><table class="iv-table">
					<thead><tr><th></th><th>품목</th><th>업체</th><th class="r">재고</th><th class="r">안전재고</th><th class="r">주문 수량</th><th class="r">예상 금액</th></tr></thead>
					<tbody>
					<?php usort( $need, function ( $a, $b ) { return strcmp( md_inv_vendor_name( $a->vendor_id ), md_inv_vendor_name( $b->vendor_id ) ) ?: strcmp( $a->name, $b->name ); } ); $lastv = -1; ?>
					<?php foreach ( $need as $it ) : $q = max( 1, $it->min_stock - max( 0, $it->stock ) ); ?>
						<?php if ( (int) $it->vendor_id !== $lastv ) : $lastv = (int) $it->vendor_id; $vv = md_inv_vendor( $lastv ); ?>
							<tr class="iv-vrow"><td colspan="7"><b><?php echo esc_html( $vv ? $vv->name : '업체 없음' ); ?></b> <small class="iv-muted"><?php echo esc_html( $vv ? trim( $vv->phone . ' ' . preg_replace( '/\s+/', ' ', (string) $vv->shop_info ) ) : '' ); ?></small></td></tr>
						<?php endif; ?>
						<tr>
							<td data-l=""><input type="checkbox" name="ids[]" value="<?php echo (int) $it->id; ?>" aria-label="선택"></td>
							<td data-l="품목"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $it->id ) ) ); ?>"><?php echo esc_html( $it->name ); ?></a></td>
							<td data-l="업체"><?php echo esc_html( md_inv_vendor_name( $it->vendor_id ) ); ?></td>
							<td data-l="재고" class="r"><?php echo md_inv_stock_badge( $it ); // phpcs:ignore ?></td>
							<td data-l="안전재고" class="r"><?php echo (int) $it->min_stock; ?></td>
							<td data-l="주문 수량" class="r"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="1" name="q[<?php echo (int) $it->id; ?>]" value="<?php echo (int) $q; ?>"> <?php echo esc_html( $it->unit ); ?></td>
							<td data-l="예상 금액" class="r"><?php echo esc_html( md_inv_won( $q * $it->price ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
			</form>
		<?php endif; ?>
	</section>
	<?php
	md_inv_admin_dialogs( array( 'release', 'reject', 'reqedit', 'reqdone', 'reqreg', 'reqorder', 'receive', 'ordcancel', 'ordedit' ) );
}

/** 주문 카드 목록 */
function md_inv_order_cards( $ords ) {
	echo '<div class="iv-cards">';
	$recvm = md_inv_ord_received_amounts( wp_list_pluck( (array) $ords, 'id' ) ); /* v6.5 · 주문마다 이미 받은 금액 */
	foreach ( $ords as $o ) {
		$v    = md_inv_vendor( $o->vendor_id );
		$what = $o->item_name . ' · ' . ( $v ? $v->name : '' ) . ' · 주문 ' . (int) $o->qty . $o->unit;
		$left = (int) $o->qty - (int) $o->recv_qty;
		echo '<article class="iv-card iv-card--ord is-' . esc_attr( $o->status ) . '">';
		echo '<div class="iv-card__main"><div class="iv-card__title"><a href="' . esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $o->item_id ) ) ) . '">' . esc_html( $o->item_name ) . '</a> ' . md_inv_ord_badge( $o ) . '</div>';
		echo '<div class="iv-card__sub">' . esc_html( ( $v ? $v->name . ( $v->prepaid ? ' (선납)' : '' ) : '업체 없음' ) . ' · ' . md_inv_date( $o->created_at, 'n/j' ) . ' 주문 · ' . $o->person ) . '</div>';
		$o_extra = (int) $o->amount - (int) $o->qty * (int) $o->price;
		$o_left  = md_inv_ord_amount_left( $o, isset( $recvm[ (int) $o->id ] ) ? $recvm[ (int) $o->id ] : 0 );
		echo '<div class="iv-card__sub">수량 <b>' . (int) $o->qty . '</b>' . esc_html( $o->unit ) . ( (int) $o->free_qty ? ' + 무상 ' . (int) $o->free_qty : '' ) . ( (int) $o->recv_qty ? ' (입고 ' . (int) $o->recv_qty . ')' : '' ) . ' · 합계 ' . esc_html( md_inv_won( $o->amount ) ) . ( $o_extra ? ' <small>(' . esc_html( ( $o_extra > 0 ? '배송비 등 +' : '할인 −' ) . md_inv_num( abs( $o_extra ) ) ) . ')</small>' : '' ) . ( 'ordered' === $o->status && (int) $o->recv_qty ? ' · 남은 금액 ' . esc_html( md_inv_won( $o_left ) ) : '' ) . ' · 지금 재고 ' . (int) $o->stock . '</div>';
		if ( (int) $o->req_id ) {
			$rq = md_inv_req( $o->req_id );
			if ( $rq ) { echo '<div class="iv-card__sub iv-accent">요청: ' . esc_html( md_inv_team_name( $rq->team_id ) . ' ' . $rq->requester . ' ' . $rq->qty . '개 (' . md_inv_req_status_label( $rq->status ) . ')' ) . '</div>'; }
		}
		if ( $v && ( $v->phone || $v->shop_info ) ) { echo '<div class="iv-card__sub iv-muted">' . esc_html( trim( preg_replace( '/\s+/', ' ', $v->phone . ' ' . $v->shop_info ) ) ) . '</div>'; }
		if ( '' !== $o->note ) { echo '<div class="iv-card__note">' . esc_html( $o->note ) . '</div>'; }
		if ( '' !== $o->cancel_note ) { echo '<div class="iv-card__why">' . esc_html( $o->cancel_note ) . '</div>'; }
		echo '</div>';
		if ( 'ordered' === $o->status ) {
			echo '<div class="iv-actions">';
			echo '<button type="button" class="iv-btn iv-btn--primary iv-btn--sm" data-dlg="dlg-receive" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $o->id, 'what' => $what, 'qty' => $left + ( (int) $o->recv_qty ? 0 : (int) $o->free_qty ), 'price' => (int) $o->price, 'track_item' => (int) $o->track_lot ? (int) $o->item_id : '', 'item_ref' => (int) $o->item_id, 'ord_left' => $left, 'ord_price' => (int) $o->price, 'ord_amt_left' => $o_left, 'free_qty' => (int) $o->recv_qty ? '' : ( (int) $o->free_qty ? (int) $o->free_qty : '' ), 'total' => '', 'hint' => '남은 수량 ' . $left . '개 · 남은 금액 ' . md_inv_won( $o_left ) . ' (배송비 포함)' . ( $v && $v->prepaid ? ' · 선납 업체 — 입고 금액이 선납 잔액에서 빠집니다' : '' ) ) ) ) . '">입고</button>';
			echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-dlg="dlg-ordedit" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $o->id, 'what' => $what, 'qty' => (int) $o->qty, 'free_qty' => (int) $o->free_qty ? (int) $o->free_qty : '', 'price' => (int) $o->price, 'total' => (int) $o->amount, 'note' => $o->note ) ) ) . '">고치기</button>';
			echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-dlg="dlg-ordcancel" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $o->id, 'what' => $what ) ) ) . '">' . ( (int) $o->recv_qty > 0 ? '여기서 마감' : '취소' ) . '</button>'; /* v6.4 · 일부 들어온 주문은 취소가 아니라 마감 */
			echo '</div>';
		}
		echo '</article>';
	}
	echo '</div>';
}

/* ============================================================
 * 재고
 * ============================================================ */

function md_inv_view_stock() {
	$admin = md_inv_is_admin();
	$S  = md_inv_settings();
	$q  = md_inv_get( 'iq' );
	$st = md_inv_get( 'ist' );
	/* v6.6 · 분류는 단계 버튼 줄로 (결제 방식 › 품목군 › 세부 분류 · 마지막 경로 기억) */
	list( $c1, $c2, $c3 ) = md_inv_cat_path( 'stock' );
	$vd = (int) md_inv_get( 'ivd', 0 );
	$args = array( 'search' => $q, 'vendor' => $vd, 'active' => 'hidden' === $st ? 0 : 1 );
	$all  = md_inv_items( $args );
	$base = array();
	foreach ( $all as $it ) {
		$s = md_inv_stock_state( $it );
		if ( 'low' === $st && 'ok' === $s ) { continue; }
		if ( 'out' === $st && 'out' !== $s ) { continue; }
		if ( 'nobar' === $st && '' !== $it->barcode ) { continue; }
		if ( 'noprice' === $st && $it->price > 0 ) { continue; }
		$base[] = $it;
	}
	$rows = md_inv_sort_by_cat( $base ); /* v6.6.1 · 분류 밖 줄은 숨겨 두고 브라우저에서 바로 바꾼다 */
	$here = array();
	foreach ( $rows as $it ) { if ( md_inv_in_path( $it, $c1, $c2, $c3 ) ) { $here[] = $it; } }
	$value = md_inv_stock_value( $here );
	?>
	<?php if ( $admin ) : ?>
	<div class="iv-toolbar iv-toolbar--actions">
		<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-item" data-set='{"title":"품목 등록","id":""}'><?php echo md_inv_icon( 'plus', 18 ); // phpcs:ignore ?>품목 등록</button>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-in">입고</button>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-out">바로 출고</button>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-adjust">실사 한 품목</button>
		<a class="iv-btn iv-btn--ghost" data-catnav-link href="<?php echo esc_url( md_inv_url( array( 'iv' => 'count', 'ic1' => $c1, 'ic2' => $c2, 'ic3' => $c3, 'ivd' => $vd ?: '' ) ) ); ?>">실사 모드 (여러 품목)</a>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-order">주문</button>
	</div>
	<?php $fx = md_inv_fix_counts( md_inv_items() ); ?>
	<div class="iv-tools">
		<a class="iv-tool" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'qcount' ) ) ); ?>"><?php echo md_inv_icon( 'scan', 22 ); // phpcs:ignore ?><b>휴대폰 실사</b><small>찍고 센 수량만</small></a>
		<a class="iv-tool" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'barcode' ) ) ); ?>"><?php echo md_inv_icon( 'scan', 22 ); // phpcs:ignore ?><b>바코드 등록</b><small>없는 품목 <?php echo (int) $fx['nobar']; ?>개</small></a>
		<a class="iv-tool<?php echo ( $fx['noprice'] + $fx['nounit'] + $fx['neg'] ) ? ' is-hot' : ''; ?>" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'fix' ) ) ); ?>"><?php echo md_inv_icon( 'edit', 22 ); // phpcs:ignore ?><b>정리할 품목</b><small>단가 없음 <?php echo (int) $fx['noprice']; ?> · 단위 없음 <?php echo (int) $fx['nounit']; ?> · 음수 <?php echo (int) $fx['neg']; ?></small></a>
		<a class="iv-tool" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'po' ) ) ); ?>"><?php echo md_inv_icon( 'truck', 22 ); // phpcs:ignore ?><b>업체별 발주서</b><small>주문 · 엑셀 · 인쇄</small></a>
		<a class="iv-tool" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'receive' ) ) ); ?>"><?php echo md_inv_icon( 'scan', 22 ); // phpcs:ignore ?><b>바코드 입고</b><small>찍고 들어온 수량만</small></a>
		<a class="iv-tool" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'fix', 'ik' => 'minsug' ) ) ); ?>"><?php echo md_inv_icon( 'chart', 22 ); // phpcs:ignore ?><b>안전재고 제안</b><small>최근 사용량으로 계산</small></a>
		<?php $nc3 = 0; foreach ( md_inv_items() as $ci ) { if ( (int) $ci->cat2 && ! (int) $ci->cat3 ) { $nc3++; } } ?>
		<a class="iv-tool<?php echo $nc3 > 30 ? ' is-hot' : ''; ?>" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'catsort' ) ) ); ?>"><?php echo md_inv_icon( 'list', 22 ); // phpcs:ignore ?><b>분류 정리 도우미</b><small><?php echo esc_html( $S['label_cat3'] ); ?> 없는 품목 <?php echo (int) $nc3; ?>개</small></a>
	</div>
	<?php endif; ?>

	<form class="iv-filter" method="get" data-autosubmit>
		<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="stock">
		<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>" placeholder="품목명 · 코드 · 바코드"></label>
		<input type="hidden" name="ic1" value="<?php echo (int) $c1; ?>"><input type="hidden" name="ic2" value="<?php echo (int) $c2; ?>"><input type="hidden" name="ic3" value="<?php echo (int) $c3; ?>">
		<label class="iv-f"><span>업체</span><select class="iv-input" name="ivd"><option value="">전체</option><?php foreach ( md_inv_vendors() as $v ) : ?><option value="<?php echo (int) $v->id; ?>"<?php selected( $vd, (int) $v->id ); ?>><?php echo esc_html( $v->name ); ?></option><?php endforeach; ?></select></label>
		<label class="iv-f"><span>보기</span><select class="iv-input" name="ist">
			<?php foreach ( array( '' => '모든 품목', 'low' => '부족 · 품절', 'out' => '품절만', 'noprice' => '단가 없는 품목', 'nobar' => '바코드 없는 품목', 'hidden' => '숨긴 품목' ) as $k => $lb ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $st, $k ); ?>><?php echo esc_html( $lb ); ?></option><?php endforeach; ?>
		</select></label>
		<button class="iv-btn iv-btn--ghost">보기</button>
	</form>

	<?php echo md_inv_cat_bar( $base, $c1, $c2, $c3, array( 'iv' => 'stock', 'iq' => $q, 'ist' => $st, 'ivd' => $vd ?: '' ) ); // phpcs:ignore ?>
	<div class="iv-toolbar">
		<span class="iv-muted" id="iv-catnav-n"><?php echo count( $here ); ?>개 품목<?php echo $admin ? ' · 재고 금액 ' . esc_html( md_inv_won( $value ) ) : ''; ?></span>
		<?php if ( $admin ) : md_inv_dl_buttons( 'items', array(), '재고 현황 엑셀' ); ?><a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_dl_url( 'xlsx' ) ); ?>"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?>최근 30일 보고서 엑셀</a><a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_all_url() ); ?>"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?>전체 엑셀 (모든 기록)</a><?php endif; ?>
	</div>

	<?php
	echo md_inv_catnav_data( 'stock', $c1, $c2, $c3, array( 'cols' => $admin ? 9 : 8 ) ); // phpcs:ignore
	if ( ! $rows ) { md_inv_empty( '이 조건의 품목이 없습니다.' ); } else { ?>
	<p class="iv-help iv-catnav-empty"<?php echo $here ? ' hidden' : ''; ?>>이 분류에는 품목이 없습니다.</p>
	<div class="iv-table-wrap"><table class="iv-table iv-table--stock">
		<thead><tr><th>품목</th><th>업체</th><th><?php echo esc_html( $S['label_cat2'] ); ?></th><th class="r">단가</th><th class="r">재고</th><th class="r">안전</th><th class="r">대기</th><th class="r">주문 중</th><?php echo $admin ? '<th></th>' : ''; ?></tr></thead>
		<tbody>
		<?php foreach ( $rows as $it ) : ?>
			<tr class="is-<?php echo esc_attr( md_inv_stock_state( $it ) ); ?>"<?php echo md_inv_row_attr( $it, $c1, $c2, $c3 ); // phpcs:ignore ?>>
				<td data-l="품목" class="iv-td-name"><?php if ( $admin ) : ?><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $it->id ) ) ); ?>"><?php echo esc_html( $it->name ); ?></a><?php else : echo esc_html( $it->name ); endif; ?><small><?php echo esc_html( $it->unit ); ?><?php echo $it->barcode ? ' · ▮▮' : ''; ?><?php echo '' !== (string) $it->location ? ' · 📍' . esc_html( $it->location ) : ''; ?></small></td>
				<td data-l="업체"><?php echo esc_html( md_inv_vendor_name( $it->vendor_id ) ); ?></td>
				<td data-l="<?php echo esc_attr( $S['label_cat2'] ); ?>"><?php echo esc_html( md_inv_item_catpath( $it ) ); ?></td>
				<td data-l="단가" class="r"><?php echo $admin || $S['staff_see_price'] ? esc_html( md_inv_num( $it->price ) ) : '—'; ?></td>
				<td data-l="재고" class="r"><?php echo md_inv_stock_badge( $it ); // phpcs:ignore ?></td>
				<td data-l="안전재고" class="r"><?php echo (int) $it->min_stock; ?></td>
				<td data-l="대기 요청" class="r"><?php echo $it->pend ? (int) $it->pend : ''; ?></td>
				<td data-l="주문 중" class="r"><?php echo $it->onord ? (int) $it->onord : ''; ?></td>
				<?php if ( $admin ) : ?>
				<td class="iv-td-act">
					<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-in" data-set='{"item_id":<?php echo (int) $it->id; ?>}'>입고</button>
					<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-adjust" data-set='{"item_id":<?php echo (int) $it->id; ?>,"counted":<?php echo max( 0, (int) $it->stock ); ?>,"hint":"장부상 재고 <?php echo (int) $it->stock; ?>"}'>실사</button>
				</td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php }
	if ( $admin ) { md_inv_admin_dialogs( array( 'item', 'in', 'out', 'adjust', 'order' ) ); }
}

/* ============================================================
 * 품목 상세
 * ============================================================ */

function md_inv_view_item() {
	$id = (int) md_inv_get( 'id', 0 );
	$it = md_inv_item( $id );
	if ( ! $it ) { md_inv_empty( '품목을 찾을 수 없습니다.' ); return; }
	$S    = md_inv_settings();
	$v    = md_inv_vendor( $it->vendor_id );
	$pg   = max( 1, (int) md_inv_get( 'pg', 1 ) );
	$per  = 50;
	$led  = md_inv_ledger( array( 'item_id' => $id, 'limit' => $per, 'offset' => ( $pg - 1 ) * $per ) );
	$ledn = md_inv_ledger( array( 'item_id' => $id ), true );
	$reqs = md_inv_reqs( array( 'item_id' => $id, 'limit' => 30 ) );
	$ords = md_inv_ords( array( 'item_id' => $id, 'limit' => 30 ) );
	$pick = md_inv_pick_label( $it );
	$from = date( 'Y-m-d', current_time( 'timestamp' ) - 90 * DAY_IN_SECONDS );
	$use  = md_inv_usage( 'team', $from, current_time( 'Y-m-d' ) );
	global $wpdb;
	$by_team = $wpdb->get_results( $wpdb->prepare( 'SELECT team_id, SUM(-qty) AS q FROM ' . md_inv_t( 'ledger' ) . " WHERE item_id = %d AND type = 'out' AND voided = 0 AND created_at >= %s GROUP BY team_id ORDER BY q DESC", $id, $from . ' 00:00:00' ) );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock' ) ) ); ?>">← 재고</a></p>
	<div class="iv-item-head">
		<div>
			<h2 class="iv-item-title"><?php echo esc_html( $it->name ); ?><?php if ( ! $it->active ) : ?> <span class="iv-tag">숨김</span><?php endif; ?></h2>
			<p class="iv-muted"><?php echo esc_html( implode( ' · ', array_filter( array( $it->code, $v ? $v->name . ( $v->prepaid ? ' (선납)' : '' ) : '', md_inv_item_catpath( $it, true ), $it->unit, $it->barcode ? '바코드 ' . $it->barcode : '', '' !== (string) $it->location ? '📍 ' . $it->location : '' ) ) ) ); ?></p>
		</div>
		<div class="iv-kpis">
			<div class="iv-kpi"><span>재고</span><b><?php echo md_inv_stock_badge( $it ); // phpcs:ignore ?></b></div>
			<div class="iv-kpi"><span>안전재고</span><b><?php echo (int) $it->min_stock; ?></b></div>
			<div class="iv-kpi"><span>단가</span><b><?php echo esc_html( md_inv_won( $it->price ) ); ?></b></div>
			<div class="iv-kpi"><span>대기 요청</span><b><?php echo (int) $it->pend; ?></b></div>
			<div class="iv-kpi"><span>주문 중</span><b><?php echo (int) $it->onord; ?></b></div>
		</div>
	</div>
	<?php if ( '' !== trim( (string) $it->note ) ) : ?><p class="iv-note"><?php echo nl2br( esc_html( $it->note ) ); ?></p><?php endif; ?>

	<div class="iv-toolbar iv-toolbar--actions">
		<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-in" data-set="<?php echo esc_attr( wp_json_encode( array( 'item_id' => $id, 'item_id_pick' => $pick ) ) ); ?>">입고</button>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-out" data-set="<?php echo esc_attr( wp_json_encode( array( 'item_id' => $id, 'item_id_pick' => $pick ) ) ); ?>">바로 출고</button>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-adjust" data-set="<?php echo esc_attr( wp_json_encode( array( 'item_id' => $id, 'item_id_pick' => $pick, 'counted' => max( 0, $it->stock ), 'hint' => '장부상 재고 ' . $it->stock . $it->unit ) ) ); ?>">실사</button>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-order" data-set="<?php echo esc_attr( wp_json_encode( array( 'item_id' => $id, 'item_id_pick' => $pick, 'qty' => max( 1, $it->min_stock - max( 0, $it->stock ) ), 'price' => $it->price ) ) ); ?>">주문</button>
		<button type="button" class="iv-btn iv-btn--ghost" data-dlg="dlg-item" data-set="<?php echo esc_attr( wp_json_encode( array(
			'title' => '품목 고치기', 'id' => $id, 'name' => $it->name, 'vendor_id' => (int) $it->vendor_id, 'unit' => $it->unit, 'price' => $it->price,
			'min_stock' => $it->min_stock, 'barcode' => $it->barcode, 'note' => (string) $it->note, 'cat1' => (int) $it->cat1, 'cat2' => (int) $it->cat2, 'cat3' => (int) $it->cat3, 'location' => (string) $it->location, 'track_lot' => (int) $it->track_lot,
		) ) ); ?>"><?php echo md_inv_icon( 'edit', 16 ); // phpcs:ignore ?>고치기</button>
		<form method="post" class="iv-inline-form" data-confirm="<?php echo esc_attr( $it->active ? '이 품목을 숨길까요? 요청 화면과 재고 목록에서 빠지고, 기록은 그대로 남습니다.' : '이 품목을 다시 보이게 할까요?' ); ?>">
			<?php md_inv_hidden( 'item_active' ); ?><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><input type="hidden" name="on" value="<?php echo $it->active ? 0 : 1; ?>">
			<button class="iv-btn iv-btn--ghost"><?php echo $it->active ? '숨기기' : '다시 보이기'; ?></button>
		</form>
		<?php if ( 0 === md_inv_item_refs( $id ) ) : ?>
			<form method="post" class="iv-inline-form" data-confirm="이 품목을 완전히 지울까요? 되돌릴 수 없습니다.">
				<?php md_inv_hidden( 'item_delete', md_inv_url( array( 'iv' => 'stock' ) ) ); ?><input type="hidden" name="id" value="<?php echo (int) $id; ?>">
				<button class="iv-btn iv-btn--ghost iv-danger">삭제</button>
			</form>
		<?php endif; ?>
	</div>

	<?php if ( $by_team ) : ?>
		<h3 class="iv-h3">최근 3개월 팀별 출고</h3>
		<div class="iv-pills"><?php foreach ( $by_team as $b ) : ?><span class="iv-pill"><?php echo esc_html( md_inv_team_name( $b->team_id ) ); ?> <b><?php echo (int) $b->q; ?></b></span><?php endforeach; ?></div>
	<?php endif; ?>

	<?php md_inv_item_lot_panel( $it ); ?>

	<?php $ph = md_inv_price_history( $id, 20 ); if ( $ph ) : ?>
		<h3 class="iv-h3">단가 변동</h3>
		<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>일시</th><th class="r">전</th><th class="r">후</th><th class="r">변동</th><th>어디서</th><th>누가</th></tr></thead><tbody>
		<?php foreach ( $ph as $p ) : $pct = $p->old_price ? round( ( $p->new_price - $p->old_price ) / $p->old_price * 100, 1 ) : null; ?>
			<tr><td data-l="일시"><?php echo esc_html( md_inv_date( $p->created_at, 'y.n.j' ) ); ?></td><td data-l="전" class="r"><?php echo esc_html( md_inv_num( $p->old_price ) ); ?></td><td data-l="후" class="r"><b><?php echo esc_html( md_inv_num( $p->new_price ) ); ?></b></td><td data-l="변동" class="r <?php echo $p->new_price > $p->old_price ? 'iv-danger' : 'iv-accent'; ?>"><?php echo null === $pct ? '—' : esc_html( ( $pct > 0 ? '+' : '' ) . $pct . '%' ); ?></td><td data-l="어디서"><?php echo esc_html( $p->source . ( $p->applied ? '' : ' (품목 단가는 그대로)' ) ); ?></td><td data-l="누가"><?php echo esc_html( $p->person ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table></div>
	<?php endif; ?>

	<h3 class="iv-h3">입출고 기록 <span class="iv-n"><?php echo (int) $ledn; ?></span></h3>
	<?php md_inv_ledger_table( $led, false ); md_inv_pager( $ledn, $per, $pg ); ?>

	<?php if ( $ords ) : ?>
		<h3 class="iv-h3">주문</h3>
		<?php md_inv_order_cards( $ords ); ?>
	<?php endif; ?>

	<?php if ( $reqs ) : ?>
		<h3 class="iv-h3">요청</h3>
		<div class="iv-table-wrap"><table class="iv-table">
			<thead><tr><th>일시</th><th>팀</th><th>요청자</th><th class="r">수량</th><th>상태</th><th>메모</th></tr></thead>
			<tbody><?php foreach ( $reqs as $r ) : ?>
				<tr><td data-l="일시"><?php echo esc_html( md_inv_date( $r->created_at, 'y.n.j H:i' ) ); ?></td><td data-l="팀"><?php echo esc_html( md_inv_team_name( $r->team_id ) ); ?></td><td data-l="요청자"><?php echo esc_html( $r->requester ); ?></td><td data-l="수량" class="r"><?php echo (int) $r->qty; ?></td><td data-l="상태"><?php echo md_inv_req_badge( $r ); // phpcs:ignore ?></td><td data-l="메모"><?php echo esc_html( trim( $r->note . ' ' . $r->admin_note ) ); ?></td></tr>
			<?php endforeach; ?></tbody>
		</table></div>
	<?php endif; ?>
	<?php
	md_inv_admin_dialogs( array( 'item', 'in', 'out', 'adjust', 'order', 'return', 'void', 'receive', 'ordedit', 'ordcancel' ) );
}

/** 원장 표 (품목 상세 · 입출고 기록 화면) */
function md_inv_ledger_table( $rows, $with_item = true ) {
	if ( ! $rows ) { md_inv_empty( '기록이 없습니다.' ); return; }
	$adjm = function_exists( 'md_inv_adj_map' ) ? md_inv_adj_map( array_map( function ( $l ) { return 'in' === $l->type ? $l->id : 0; }, $rows ) ) : array();
	echo '<div class="iv-table-wrap"><table class="iv-table iv-table--ledger"><thead><tr><th>일시</th><th>구분</th>' . ( $with_item ? '<th>품목</th>' : '' ) . '<th class="r">수량</th><th class="r">금액</th><th>팀 · 업체</th><th>처리 · 메모</th><th></th></tr></thead><tbody>';
	foreach ( $rows as $l ) {
		/* v6.5 · 입고 · 반품은 저장된 금액(실제로 낸 · 돌려받은 금액), 출고 · 실사는 수량 × 단가(참고) */
		$amt = in_array( $l->type, array( 'in', 'return' ), true ) ? ( 'return' === $l->type ? -(int) $l->amount : (int) $l->amount ) : (int) $l->qty * (int) $l->price;
		$mny = '';
		if ( 'in' === $l->type ) {
			$fq = (int) $l->free_qty; $ex = (int) $l->extra;
			$mny = ( $fq && $fq < abs( (int) $l->qty ) ? '무상 ' . $fq . ' 포함 · ' : '' ) . ( abs( (int) $l->qty ) > $fq ? md_inv_num( $l->price ) . '원 × ' . ( abs( (int) $l->qty ) - $fq ) : '' ) . ( $ex ? ( $ex > 0 ? ' + 배송비 등 ' : ' − 할인 ' ) . md_inv_num( abs( $ex ) ) : '' );
		}
		echo '<tr class="iv-lt iv-lt--' . esc_attr( $l->type ) . ( $l->voided ? ' is-void' : '' ) . '">';
		echo '<td data-l="일시">' . esc_html( md_inv_date( $l->created_at, 'y.n.j H:i' ) ) . '</td>';
		echo '<td data-l="구분"><span class="iv-type iv-type--' . esc_attr( $l->type ) . '">' . esc_html( md_inv_type_label( $l->type ) ) . '</span>' . ( $l->free ? ' <span class="iv-tag">무상</span>' : ( 'in' === $l->type && (int) $l->free_qty ? ' <span class="iv-tag">무상 ' . (int) $l->free_qty . '</span>' : '' ) ) . '</td>';
		if ( $with_item ) { echo '<td data-l="품목"><a href="' . esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $l->item_id ) ) ) . '">' . esc_html( $l->item_name ) . '</a></td>'; }
		echo '<td data-l="수량" class="r"><b>' . ( $l->qty > 0 ? '+' : '' ) . (int) $l->qty . '</b>' . ( null !== $l->counted ? ' <small>(센 수량 ' . (int) $l->counted . ')</small>' : '' ) . '</td>';
		echo '<td data-l="금액" class="r">' . esc_html( md_inv_num( $amt ) ) . ( '' !== $mny ? '<br><small class="iv-muted">' . esc_html( $mny ) . '</small>' : '' ) . '</td>';
		echo '<td data-l="팀 · 업체">' . esc_html( trim( md_inv_team_name( $l->team_id ) . ( $l->team_id && $l->vendor_id ? ' · ' : '' ) . ( in_array( $l->type, array( 'in', 'return' ), true ) ? md_inv_vendor_name( $l->vendor_id ) : '' ) ) ) . '</td>';
		echo '<td data-l="처리 · 메모">' . esc_html( trim( $l->person . ' ' . $l->note ) ) . ( '' !== (string) $l->receiver ? '<br><small>받은 사람 ' . esc_html( $l->receiver ) . '</small>' : '' ) . ( '' !== (string) $l->lot || '' !== (string) $l->chart ? '<br><small class="iv-lotline">' . esc_html( trim( ( '' !== (string) $l->lot ? 'LOT ' . $l->lot : '' ) . ( '' !== (string) $l->chart ? ' · 차트 ' . $l->chart : '' ), ' ·' ) ) . '</small>' : '' ) . ( $l->voided ? '<br><small class="iv-danger">취소됨 · ' . esc_html( $l->void_note ) . '</small>' : '' ) . ( isset( $adjm[ (int) $l->id ] ) ? md_inv_adj_lines_html( $adjm[ (int) $l->id ] ) : '' ) . '</td>';
		echo '<td class="iv-td-act">';
		if ( ! $l->voided ) {
			$what = md_inv_type_label( $l->type ) . ' · ' . $l->item_name . ' ' . ( $l->qty > 0 ? '+' : '' ) . $l->qty . ' · ' . md_inv_date( $l->created_at, 'n/j H:i' );
			if ( 'in' === $l->type ) {
				$can = md_inv_returnable( $l->id );
				$mm  = md_inv_in_money( $l );
				if ( $can > 0 ) { echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-return" data-set="' . esc_attr( wp_json_encode( array( 'in_id' => (int) $l->id, 'what' => $what, 'qty' => 1, 'qty@max' => $can, 'ret_net' => max( 0, $mm['net'] ), 'ret_units' => $can, 'amount' => md_inv_return_value( $l, 1 ), 'hint' => '이 입고에서 반품할 수 있는 수량 ' . $can . '개 · 아직 돌려받지 않은 금액 ' . md_inv_won( max( 0, $mm['net'] ) ) . ( $mm['net'] <= 0 ? ' — 돌려받을 금액이 없어 선납 잔액은 그대로' : '' ) ) ) ) . '">반품</button>'; }
				if ( $can > 0 ) { echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-exch" data-set="' . esc_attr( wp_json_encode( array( 'in_id' => (int) $l->id, 'what' => $what, 'qty' => 1, 'qty@max' => $can ) ) ) . '">교환</button>'; }
				if ( (int) $l->amount > 0 ) {
					/* v6.5 · 실제로 낸 금액 기준 — 이미 돌려받은 것(환불 · 정정 · 반품)을 빼고 남은 금액 안에서 */
					echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-adj" data-set="' . esc_attr( wp_json_encode( array( 'in_id' => (int) $l->id, 'what' => $what, 'pp' => md_inv_is_prepaid_vendor( $l->vendor_id ) ? 1 : '', 'price' => '', 'bqty@max' => max( 1, $mm['paid_units'] ), 'hint' => '이 입고에서 낸 금액 ' . md_inv_won( $mm['amount'] ) . ( $mm['adj'] || $mm['back'] ? ' · 이미 돌려받은 ' . md_inv_won( $mm['adj'] + $mm['back'] ) : '' ) . ' · 남은 금액 ' . md_inv_won( max( 0, $mm['net'] ) ) . ' · 유상 ' . $mm['paid_units'] . '개 · 재고는 바뀌지 않습니다.' ) ) ) . '">환불 · 정정</button>';
				}
			}
			echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-void" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $l->id, 'what' => $what ) ) ) . '">취소</button>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/* ============================================================
 * 실사 모드 — 여러 품목을 한 번에 세어 맞춘다
 * ============================================================ */

function md_inv_view_count() {
	$S  = md_inv_settings();
	list( $c1, $c2, $c3 ) = md_inv_cat_path( 'count' );
	$vd = (int) md_inv_get( 'ivd', 0 );
	$q  = md_inv_get( 'iq' );
	$base = md_inv_items( array( 'vendor' => $vd, 'search' => $q ) );
	$rows = md_inv_sort_by_cat( $base ); /* v6.6.1 · 분류 밖 줄은 숨김 — 다른 분류에 적어 둔 수량도 같이 저장된다 */
	$n_here = 0;
	foreach ( $rows as $it ) { if ( md_inv_in_path( $it, $c1, $c2, $c3 ) ) { $n_here++; } }
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock' ) ) ); ?>">← 재고</a></p>
	<h2 class="iv-h2">실사 모드</h2>
	<p class="iv-help">창고에서 센 수량을 적으세요. <b>빈 칸은 건드리지 않습니다.</b> 장부와 같은 수량은 기록하지 않고, 다른 것만 차이를 「실사」로 남깁니다. 많으면 아래 분류 버튼으로 나눠서 하세요 (예: 선납차감품목 › 메가젠임플란트 › 픽스처).</p>
	<form class="iv-filter" method="get" data-autosubmit>
		<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="count">
		<input type="hidden" name="ic1" value="<?php echo (int) $c1; ?>"><input type="hidden" name="ic2" value="<?php echo (int) $c2; ?>"><input type="hidden" name="ic3" value="<?php echo (int) $c3; ?>">
		<label class="iv-f"><span>업체</span><select class="iv-input" name="ivd"><option value="">전체</option><?php foreach ( md_inv_vendors() as $v ) : ?><option value="<?php echo (int) $v->id; ?>"<?php selected( $vd, (int) $v->id ); ?>><?php echo esc_html( $v->name ); ?></option><?php endforeach; ?></select></label>
		<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>"></label>
		<button class="iv-btn iv-btn--ghost">보기</button>
	</form>
	<?php echo md_inv_cat_bar( $base, $c1, $c2, $c3, array( 'iv' => 'count', 'iq' => $q, 'ivd' => $vd ?: '' ) ); // phpcs:ignore ?>
	<?php echo md_inv_catnav_data( 'count', $c1, $c2, $c3, array( 'cols' => 4 ) ); // phpcs:ignore ?>
	<?php if ( ! $rows ) { md_inv_empty( '이 조건의 품목이 없습니다.' ); return; } ?>
	<p class="iv-help iv-catnav-empty"<?php echo $n_here ? ' hidden' : ''; ?>>이 분류에는 품목이 없습니다.</p>
	<form method="post" data-confirm="적은 수량으로 재고를 맞출까요?" data-dirtywarn>
		<?php md_inv_hidden( 'stock_count' ); ?>
		<div class="iv-toolbar"><label class="iv-f iv-f--grow"><span>사유</span><input class="iv-input" name="note" maxlength="200" value="<?php echo esc_attr( '정기 실사 ' . current_time( 'Y-m-d' ) ); ?>"></label></div>
		<div class="iv-table-wrap"><table class="iv-table iv-table--count">
			<thead><tr><th>품목</th><th>업체</th><th class="r">장부 재고</th><th class="r">센 수량</th></tr></thead>
			<tbody><?php foreach ( $rows as $it ) : ?>
				<tr<?php echo md_inv_row_attr( $it, $c1, $c2, $c3 ); // phpcs:ignore ?>><td data-l="품목"><?php echo esc_html( $it->name ); ?> <small><?php echo esc_html( $it->unit ); ?></small></td><td data-l="업체"><?php echo esc_html( md_inv_vendor_name( $it->vendor_id ) ); ?></td><td data-l="장부" class="r"><?php echo (int) $it->stock; ?></td>
				<td data-l="센 수량" class="r"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="0" name="cnt[<?php echo (int) $it->id; ?>]" data-book="<?php echo (int) $it->stock; ?>" aria-label="<?php echo esc_attr( $it->name . ' 센 수량' ); ?>"></td></tr>
			<?php endforeach; ?></tbody>
		</table></div>
		<div class="iv-stickyfoot"><span id="iv-count-sum" class="iv-muted"></span><button class="iv-btn iv-btn--primary iv-btn--lg">재고 맞추기</button></div>
	</form>
	<?php
}

/* ============================================================
 * 주문
 * ============================================================ */

function md_inv_view_orders() {
	$st = md_inv_get( 'ist', 'ordered' );
	$vd = (int) md_inv_get( 'ivd', 0 );
	$q  = md_inv_get( 'iq' );
	$pg = max( 1, (int) md_inv_get( 'pg', 1 ) );
	$per = 60;
	$args = array( 'status' => 'all' === $st ? '' : $st, 'vendor_id' => $vd, 'search' => $q, 'limit' => $per, 'offset' => ( $pg - 1 ) * $per );
	$rows = md_inv_ords( $args );
	$n    = md_inv_ords( $args, true );
	?>
	<div class="iv-toolbar iv-toolbar--actions">
		<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-order"><?php echo md_inv_icon( 'plus', 18 ); // phpcs:ignore ?>주문 넣기</button>
		<a class="iv-btn iv-btn--ghost" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'po' ) ) ); ?>"><?php echo md_inv_icon( 'truck', 18 ); // phpcs:ignore ?>업체별 발주서</a>
		<a class="iv-btn iv-btn--ghost" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'receive' ) ) ); ?>"><?php echo md_inv_icon( 'scan', 18 ); // phpcs:ignore ?>바코드 입고</a>
		<a class="iv-btn iv-btn--ghost" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'settle' ) ) ); ?>"><?php echo md_inv_icon( 'list', 18 ); // phpcs:ignore ?>월말 업체 정산</a>
		<?php md_inv_dl_buttons( 'orders', array( 'df' => date( 'Y-m-d', current_time( 'timestamp' ) - 365 * DAY_IN_SECONDS ) ) ); ?>
	</div>
	<form class="iv-filter" method="get" data-autosubmit>
		<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="orders">
		<label class="iv-f"><span>상태</span><select class="iv-input" name="ist">
			<?php foreach ( array( 'ordered' => '입고 대기', 'received' => '입고 완료', 'cancelled' => '취소', 'all' => '전체' ) as $k => $lb ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $st, $k ); ?>><?php echo esc_html( $lb ); ?></option><?php endforeach; ?>
		</select></label>
		<label class="iv-f"><span>업체</span><?php md_inv_vendor_select( 'ivd', $vd, false, '전체' ); ?></label>
		<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>"></label>
		<button class="iv-btn iv-btn--ghost">보기</button>
	</form>
	<p class="iv-muted"><?php echo (int) $n; ?>건</p>
	<?php
	if ( ! $rows ) { md_inv_empty( '주문이 없습니다.' ); } else { md_inv_order_cards( $rows ); md_inv_pager( $n, $per, $pg ); }
	md_inv_admin_dialogs( array( 'order', 'receive', 'ordedit', 'ordcancel' ) );
}

/* ============================================================
 * 선납
 * ============================================================ */

function md_inv_view_prepaid() {
	$sum = md_inv_prepaid_summary();
	$vid = (int) md_inv_get( 'ivd', 0 );
	if ( ! $sum ) {
		md_inv_empty( '선납 업체가 없습니다. 설정 › 업체에서 업체를 열고 「선납 업체」를 켜 주세요.' );
		return;
	}
	?>
	<?php md_inv_prepaid_warnings( $sum ); ?>
	<p class="iv-help">쓸 수 있는 금액(입금 + 적립 + 조정) − 입고 금액(무상 제외) + 반품 금액 = 잔액. 주문해 두고 아직 안 들어온 금액을 빼면 「쓸 수 있는 잔액」입니다.</p>
	<div class="iv-toolbar"><?php md_inv_dl_buttons( 'prepaid', array(), '선납 현황 엑셀' ); md_inv_dl_buttons( 'deposits', array(), '입금 내역 엑셀' ); md_inv_dl_buttons( 'passbook', array(), '전체 거래 내역 엑셀' ); ?><a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'lot' ) ) ); ?>">LOT · 차트번호 찾기</a></div>
	<div class="iv-ppgrid">
		<?php foreach ( $sum as $p ) : ?>
			<a class="iv-pp<?php echo $vid === (int) $p->vendor->id ? ' is-on' : ''; ?><?php echo $p->available < 0 ? ' is-neg' : ''; ?><?php echo $p->alert ? ' is-alert' : ''; ?>" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'prepaid', 'ivd' => $p->vendor->id ) ) ); ?>">
				<span class="iv-pp__name"><?php echo esc_html( $p->vendor->name ); ?></span>
				<b class="iv-pp__amt"><?php echo esc_html( md_inv_won( $p->available ) ); ?></b>
				<span class="iv-pp__sub">쓸 수 있는 잔액</span>
				<dl class="iv-pp__dl">
					<div><dt>입금</dt><dd><?php echo esc_html( md_inv_num( $p->paid ) ); ?></dd></div>
					<?php if ( $p->bonus ) : ?><div><dt>적립</dt><dd><?php echo esc_html( ( $p->bonus > 0 ? '+' : '' ) . md_inv_num( $p->bonus ) ); ?></dd></div><?php endif; ?>
					<?php if ( $p->adjust ) : ?><div><dt>조정</dt><dd><?php echo esc_html( ( $p->adjust > 0 ? '+' : '' ) . md_inv_num( $p->adjust ) ); ?></dd></div><?php endif; ?>
					<div><dt>입고 차감</dt><dd><?php echo esc_html( ( $p->spent ? '−' : '' ) . md_inv_num( $p->spent ) ); ?></dd></div>
					<div><dt>반품 환원</dt><dd><?php echo esc_html( ( $p->returned ? '+' : '' ) . md_inv_num( $p->returned ) ); ?></dd></div>
					<?php if ( $p->refund ) : ?><div><dt>환불 · 정정</dt><dd><?php echo esc_html( ( $p->refund > 0 ? '+' : '' ) . md_inv_num( $p->refund ) ); ?></dd></div><?php endif; ?>
					<div><dt>잔액</dt><dd><?php echo esc_html( md_inv_num( $p->balance ) ); ?></dd></div>
					<div><dt>주문 중</dt><dd><?php echo esc_html( ( $p->pending ? '−' : '' ) . md_inv_num( $p->pending ) ); ?></dd></div>
				</dl>
				<span class="iv-pp__fc"><?php echo $p->burn ? '한 달 평균 차감 ' . esc_html( md_inv_num( $p->burn ) ) . ' · 약 <b>' . esc_html( md_inv_months_txt( $p->months_left ) ) . '</b>분' : '최근 90일 차감 없음'; ?><?php echo $p->alert ? ' · <b class="iv-danger">잔액 부족</b>' : ''; ?></span>
			</a>
		<?php endforeach; ?>
	</div>

	<?php if ( $vid && isset( $sum[ $vid ] ) ) :
		$p = $sum[ $vid ];
		$deps = md_inv_deposits( $vid );
		$led  = md_inv_ledger( array( 'vendor_id' => $vid, 'type' => 'in,return', 'limit' => 200 ) );
		$ords = md_inv_ords( array( 'vendor_id' => $vid, 'status' => 'ordered', 'limit' => 100 ) );
		?>
		<h2 class="iv-h2"><?php echo esc_html( $p->vendor->name ); ?></h2>
		<div class="iv-toolbar iv-toolbar--actions">
			<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-dep" data-set="<?php echo esc_attr( wp_json_encode( array( 'vendor_id' => $vid, 'what' => $p->vendor->name, 'bhint' => (float) $p->vendor->pp_bonus > 0 ? '이 업체 기본 적립률 ' . rtrim( rtrim( number_format( (float) $p->vendor->pp_bonus, 2 ), '0' ), '.' ) . '% — 비우면 입금액 × ' . ( 1 + (float) $p->vendor->pp_bonus / 100 ) . '로 계산합니다.' : '적립이 없으면 비워 두세요 (= 입금액). 기본 적립률은 설정 › 업체에서 정합니다.' ) ) ); ?>">입금 기록</button>
			<a class="iv-btn iv-btn--ghost" href="#iv-passbook">통장식 내역</a><a class="iv-btn iv-btn--ghost" href="#iv-recon">잔액 대조</a><a class="iv-btn iv-btn--ghost" href="#iv-stmt">명세서 대조</a><a class="iv-btn iv-btn--ghost" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'pricelist', 'ivd' => $vid ) ) ); ?>">단가표 올리기</a>
		</div>
		<?php $zero = md_inv_prepaid_zero_items( $vid ); if ( $zero ) : ?><p class="iv-help iv-danger">단가 0원 품목 <?php echo count( $zero ); ?>개: <?php echo esc_html( implode( ', ', array_map( function ( $z ) { return $z->name; }, $zero ) ) ); ?></p><?php endif; ?>
		<h3 class="iv-h3">입금 내역</h3>
		<?php if ( ! $deps ) : md_inv_empty( '입금 기록이 없습니다.' ); else : ?>
		<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>입금일</th><th class="r">입금액</th><th class="r">쓸 수 있는 금액</th><th>메모</th><th>입력</th><th></th></tr></thead><tbody>
			<?php foreach ( $deps as $d ) : ?>
				<tr<?php echo 'adjust' === $d->kind ? ' class="iv-dep--adj"' : ''; ?>><td data-l="입금일"><?php echo esc_html( $d->paid_on ); ?><?php echo 'adjust' === $d->kind ? ' <span class="iv-tag">조정</span>' : ( 'credit' === $d->kind ? ' <span class="iv-tag">보상</span>' : '' ); ?></td><td data-l="입금액" class="r"><b><?php echo in_array( $d->kind, array( 'adjust', 'credit' ), true ) ? '—' : esc_html( md_inv_num( $d->amount ) ); ?></b></td><td data-l="쓸 수 있는 금액" class="r"><?php echo esc_html( md_inv_num( $d->credit ) ); ?><?php echo 'pay' === $d->kind && (int) $d->credit !== (int) $d->amount ? ' <small>(적립 ' . esc_html( md_inv_num( $d->credit - $d->amount ) ) . ')</small>' : ''; ?></td><td data-l="메모"><?php echo esc_html( $d->note ); ?></td><td data-l="입력"><?php echo esc_html( $d->person ); ?></td>
				<td class="iv-td-act"><form method="post" data-confirm="이 입금 기록을 지울까요? 잔액이 바뀝니다."><?php md_inv_hidden( 'dep_delete' ); ?><input type="hidden" name="id" value="<?php echo (int) $d->id; ?>"><button class="iv-btn iv-btn--ghost iv-btn--xs">삭제</button></form></td></tr>
			<?php endforeach; ?>
		</tbody></table></div>
		<?php endif; ?>
		<?php if ( $ords ) : ?><h3 class="iv-h3">주문 중</h3><?php md_inv_order_cards( $ords ); endif; ?>
		<?php md_inv_prepaid_passbook_section( $vid ); ?>
		<?php md_inv_prepaid_recon_section( $vid ); ?>
		<?php md_inv_prepaid_statement_section( $vid ); ?>
		<?php md_inv_prepaid_lot_section( $vid ); ?>
		<details class="iv-panel"><summary class="iv-h3">입고 · 반품 기록 (반품 · 취소는 여기서)</summary>
		<?php md_inv_ledger_table( $led ); ?>
		</details>
	<?php endif; ?>

	<dialog class="iv-dlg" id="dlg-dep"><form method="post">
		<?php md_inv_hidden( 'dep_add' ); ?>
		<input type="hidden" name="vendor_id" value="<?php echo (int) $vid; ?>">
		<div class="iv-dlg__head"><b>입금 기록</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<p class="iv-dlg__what" data-t="what"></p>
		<fieldset class="iv-f iv-depkind"><span>종류</span>
			<label class="iv-check"><input type="radio" name="dkind" value="pay" checked> 입금 (우리가 돈을 냄 · 돌려받으면 −)</label>
			<label class="iv-check"><input type="radio" name="dkind" value="credit"> 업체 보상 · 리베이트 (돈은 안 냈고 잔액만 넣어 줌)</label>
		</fieldset>
		<div class="iv-grid2">
			<label class="iv-f"><span>입금일</span><input class="iv-input" type="date" name="paid_on" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required></label>
			<label class="iv-f"><span>입금액 (실제 낸 돈 · 원) <em>*</em></span><input class="iv-input" name="amount" inputmode="numeric" required placeholder="돌려받으면 −"></label>
		</div>
		<label class="iv-f"><span>쓸 수 있는 금액 (적립 포함 · 원)</span><input class="iv-input" name="credit" inputmode="numeric" placeholder="비우면 업체 적립률로 계산"></label>
		<p class="iv-help" data-t="bhint"></p>
		<label class="iv-f"><span>메모</span><input class="iv-input" name="note" maxlength="200"></label>
		<?php md_inv_dlg_close( '기록' ); ?>
	<?php
	md_inv_admin_dialogs( array( 'receive', 'ordedit', 'ordcancel', 'return', 'void' ) );
}

/* ============================================================
 * 입출고 기록
 * ============================================================ */

function md_inv_view_ledger() {
	$ty = md_inv_get( 'ity' );
	$tm = (int) md_inv_get( 'it', 0 );
	$vd = (int) md_inv_get( 'ivd', 0 );
	$q  = md_inv_get( 'iq' );
	$df = md_inv_get_date( 'df', date( 'Y-m-d', current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS ) );
	$dt = md_inv_get_date( 'dt', current_time( 'Y-m-d' ) );
	$pg = max( 1, (int) md_inv_get( 'pg', 1 ) );
	$per = 100;
	$args = array( 'type' => $ty, 'team_id' => $tm, 'vendor_id' => $vd, 'search' => $q, 'from' => $df, 'to' => $dt, 'limit' => $per, 'offset' => ( $pg - 1 ) * $per );
	$rows = md_inv_ledger( $args );
	$n    = md_inv_ledger( $args, true );
	?>
	<form class="iv-filter" method="get" data-autosubmit>
		<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="ledger">
		<label class="iv-f"><span>구분</span><select class="iv-input" name="ity"><option value="">전체</option><?php foreach ( array( 'in', 'out', 'return', 'adjust', 'open' ) as $k ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $ty, $k ); ?>><?php echo esc_html( md_inv_type_label( $k ) ); ?></option><?php endforeach; ?></select></label>
		<label class="iv-f"><span>팀</span><?php md_inv_team_select( 'it', $tm, false, '전체', true ); ?></label>
		<label class="iv-f"><span>업체</span><?php md_inv_vendor_select( 'ivd', $vd, false, '전체' ); ?></label>
		<label class="iv-f"><span>부터</span><input class="iv-input" type="date" name="df" value="<?php echo esc_attr( $df ); ?>"></label>
		<label class="iv-f"><span>까지</span><input class="iv-input" type="date" name="dt" value="<?php echo esc_attr( $dt ); ?>"></label>
		<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>" placeholder="품목 · 메모 · 이름"></label>
		<button class="iv-btn iv-btn--ghost">보기</button>
	</form>
	<div class="iv-toolbar"><span class="iv-muted"><?php echo (int) $n; ?>건</span><?php md_inv_dl_buttons( 'ledger', array( 'df' => $df, 'dt' => $dt ) ); ?></div>
	<?php
	md_inv_ledger_table( $rows );
	md_inv_pager( $n, $per, $pg );
	md_inv_admin_dialogs( array( 'return', 'void' ) );
}
