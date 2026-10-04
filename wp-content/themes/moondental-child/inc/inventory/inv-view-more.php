<?php
/**
 * 품목신청 — v5.6 (원장 지시)
 *   바코드 입고 · 월말 업체 정산 · 안전재고 제안 · 중복 품목 합치기 · 홈 화면 아이콘 · 처리된 신청 표시
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 처리
 * ============================================================ */

/** 안전재고 제안 적용 — 고른 품목만 */
function md_inv_act_min_apply() {
	global $wpdb;
	$ids  = array_filter( array_map( 'intval', (array) md_inv_p( 'ids', array() ) ) );
	$vals = (array) md_inv_p( 'v', array() );
	if ( ! $ids ) { md_inv_go( 'err', '적용할 품목을 골라 주세요.' ); }
	$n = 0;
	foreach ( $ids as $id ) {
		$v = isset( $vals[ $id ] ) ? max( 0, md_inv_int( $vals[ $id ] ) ) : null;
		if ( null === $v || ! md_inv_item( $id ) ) { continue; }
		$wpdb->update( md_inv_t( 'item' ), array( 'min_stock' => $v, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
		$n++;
	}
	md_inv_log( '안전재고 제안 적용', $n . '개 품목' );
	md_inv_go( 'ok', $n . '개 품목의 안전재고를 바꿨습니다. 「할 일 › 주문 필요」가 이 값으로 다시 계산됩니다.' );
}

function md_inv_act_item_merge() {
	md_inv_done( md_inv_item_merge( (int) md_inv_p( 'keep' ), (int) md_inv_p( 'drop' ) ), '두 품목을 하나로 합쳤습니다. 기록과 재고도 합쳐졌습니다.' );
}

/** 바코드 입고 — 주문이 있으면 그 주문으로, 없으면 그냥 입고 */
function md_inv_act_recv_scan() {
	$item = (int) md_inv_p( 'item_id' );
	$qty  = (int) md_inv_p( 'qty' );
	$oid  = (int) md_inv_p( 'ord_id' );
	$back = md_inv_url( array( 'iv' => 'receive' ) );
	$it   = md_inv_item( $item );
	if ( ! $it ) { md_inv_go( 'err', '품목을 찾을 수 없습니다.', $back ); }
	if ( $oid ) {
		$o = md_inv_ord( $oid );
		if ( ! $o || (int) $o->item_id !== $item ) { md_inv_go( 'err', '주문을 다시 골라 주세요.', $back ); }
		$left = (int) $o->qty - (int) $o->recv_qty;
		$r = md_inv_ord_receive( $oid, $qty, array( 'free' => md_inv_p( 'free' ) ? 1 : 0, 'allow_more' => $qty > $left ? 1 : 0, 'note' => md_inv_p( 'note' ) ) );
	} else {
		$r = md_inv_do_in( $item, $qty, array( 'price' => md_inv_p( 'price' ), 'free' => md_inv_p( 'free' ) ? 1 : 0, 'note' => md_inv_txt( md_inv_p( 'note' ), 300 ) ) );
	}
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message(), add_query_arg( 'id', $item, $back ) ); }
	md_inv_go( 'ok', '「' . $it->name . '」 ' . $qty . ( $it->unit ? $it->unit : '개' ) . ' 입고 · 지금 재고 ' . md_inv_stock( $item ), $back );
}

/* ============================================================
 * 화면 · 바코드 입고
 * ============================================================ */

function md_inv_view_receive() {
	$id = (int) md_inv_get( 'id', 0 );
	if ( ! $id && preg_match( '/#(\d+)\s*$/', md_inv_get( 'id_pick' ), $m ) ) { $id = (int) $m[1]; }
	$bc   = md_inv_txt( md_inv_get( 'bc' ), 100 );
	$it   = $id ? md_inv_item( $id ) : null;
	$miss = '';
	if ( ! $it && '' !== $bc ) { $it = md_inv_item_by_barcode( $bc ); if ( ! $it ) { $miss = $bc; } }
	$here = md_inv_url( array( 'iv' => 'receive' ) );
	$open = md_inv_ords( array( 'status' => 'ordered', 'limit' => 0 ) );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'todo' ) ) ); ?>">← 할 일</a></p>
	<h2 class="iv-h2">바코드 입고</h2>
	<p class="iv-help">들어온 물건을 찍거나 찾으면, 주문해 둔 것이 있으면 그 주문으로 입고합니다(주문이 자동으로 닫힘). 주문 없이 들어온 것도 그대로 입고할 수 있습니다.</p>
	<div class="iv-qc-find">
		<button type="button" class="iv-btn iv-btn--primary iv-btn--lg" data-scan-go="<?php echo esc_attr( add_query_arg( 'bc', '', $here ) ); ?>"><?php echo md_inv_icon( 'scan', 20 ); // phpcs:ignore ?>바코드 찍기</button>
		<form method="get" class="iv-qc-pick"><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="receive">
			<?php md_inv_item_picker( 'id', true, 0, '또는 품목 이름으로 찾기' ); ?>
			<button class="iv-btn iv-btn--ghost">열기</button>
		</form>
	</div>
	<?php if ( '' !== $miss ) : ?>
		<div class="iv-flash iv-flash--warn">「<?php echo esc_html( $miss ); ?>」 바코드로 등록된 품목이 없습니다. 이름으로 찾거나 <a class="iv-link" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'barcode', 'bc' => $miss ) ) ); ?>">이 바코드를 품목에 등록</a>하세요.</div>
	<?php endif; ?>
	<?php if ( $it ) :
		$mine = array_values( array_filter( $open, function ( $o ) use ( $it ) { return (int) $o->item_id === (int) $it->id; } ) );
		$v    = md_inv_vendor( $it->vendor_id ); ?>
		<form method="post" class="iv-qc-card">
			<?php md_inv_hidden( 'recv_scan', $here ); ?>
			<input type="hidden" name="item_id" value="<?php echo (int) $it->id; ?>">
			<div class="iv-qc-card__name"><?php echo esc_html( $it->name ); ?></div>
			<div class="iv-qc-card__sub"><?php echo esc_html( trim( ( $v ? $v->name . ( $v->prepaid ? ' (선납)' : '' ) : '' ) . ' · ' . $it->unit, ' ·' ) ); ?> · 지금 재고 <?php echo (int) $it->stock; ?><?php echo '' !== (string) $it->location ? ' · 📍' . esc_html( $it->location ) : ''; ?></div>
			<?php if ( $mine ) : ?>
				<fieldset class="iv-ordpick"><legend>어느 주문이 들어왔나요?</legend>
					<?php foreach ( $mine as $i => $o ) : $left = (int) $o->qty - (int) $o->recv_qty; ?>
						<label class="iv-check"><input type="radio" name="ord_id" value="<?php echo (int) $o->id; ?>" data-left="<?php echo (int) $left; ?>"<?php checked( 0, $i ); ?>> <?php echo esc_html( md_inv_date( $o->created_at, 'n/j' ) . ' 주문 · 남은 ' . $left . $it->unit . ( $o->note ? ' · ' . $o->note : '' ) ); ?></label>
					<?php endforeach; ?>
					<label class="iv-check"><input type="radio" name="ord_id" value="0"> 주문 없이 들어옴</label>
				</fieldset>
			<?php else : ?>
				<input type="hidden" name="ord_id" value="0">
				<p class="iv-help">이 품목은 주문해 둔 것이 없습니다 — 그냥 입고합니다.</p>
			<?php endif; ?>
			<label class="iv-f"><span>들어온 수량</span><input class="iv-input iv-qc-card__num" type="number" inputmode="numeric" min="1" name="qty" required value="<?php echo $mine ? (int) $mine[0]->qty - (int) $mine[0]->recv_qty : ''; ?>"></label>
			<label class="iv-check"><input type="checkbox" name="free" value="1"> 무상 제공<?php echo $v && $v->prepaid ? ' (선납 잔액에서 빼지 않음)' : ''; ?></label>
			<label class="iv-f"><span>메모 (선택)</span><input class="iv-input" name="note" maxlength="200" placeholder="예: 거래명세서 번호"></label>
			<button class="iv-btn iv-btn--primary iv-btn--lg iv-qc-card__save">입고</button>
		</form>
	<?php endif; ?>
	<?php
	$today = md_inv_ledger( array( 'type' => 'in', 'from' => current_time( 'Y-m-d' ), 'to' => current_time( 'Y-m-d' ), 'limit' => 50, 'with_void' => 0 ) );
	if ( $today ) {
		echo '<h3 class="iv-h3">오늘 입고 <span class="iv-n">' . count( $today ) . '</span></h3><div class="iv-cards">';
		foreach ( $today as $l ) {
			echo '<div class="iv-card iv-card--slim"><div class="iv-card__main"><div class="iv-card__title">' . esc_html( $l->item_name ) . '</div><div class="iv-card__sub">' . esc_html( md_inv_date( $l->created_at, 'H:i' ) . ' · ' . $l->person . ( $l->ord_id ? ' · 주문 #' . $l->ord_id : '' ) . ( $l->free ? ' · 무상' : '' ) ) . '</div></div><div class="iv-card__side"><b>+' . (int) $l->qty . '</b></div></div>';
		}
		echo '</div>';
	}
	if ( $open ) {
		echo '<h3 class="iv-h3">들어오기를 기다리는 주문 <span class="iv-n">' . count( $open ) . '</span></h3><div class="iv-pills">';
		foreach ( $open as $o ) {
			echo '<a class="iv-pill" href="' . esc_url( md_inv_url( array( 'iv' => 'receive', 'id' => $o->item_id ) ) ) . '">' . esc_html( $o->item_name . ' ' . ( (int) $o->qty - (int) $o->recv_qty ) ) . '</a>';
		}
		echo '</div>';
	}
	md_inv_scan_dialog();
}

/* ============================================================
 * 화면 · 월말 업체 정산
 * ============================================================ */

function md_inv_settle_months() {
	$out = array();
	$ts  = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
	for ( $i = 0; $i < 13; $i++ ) { $out[] = date( 'Y-m', strtotime( "-$i months", $ts ) ); }
	return $out;
}

/** 업체 하나의 합계 */
function md_inv_settle_sum( $lines ) {
	$s = array( 'n' => 0, 'buy' => 0, 'ret' => 0, 'free' => 0 );
	foreach ( $lines as $l ) {
		$amt = abs( (int) $l->qty ) * (int) $l->price;
		$s['n']++;
		if ( $l->free ) { $s['free'] += 'in' === $l->type ? $amt : 0; continue; }
		if ( 'in' === $l->type ) { $s['buy'] += $amt; } else { $s['ret'] += $amt; }
	}
	$s['net'] = $s['buy'] - $s['ret'];
	return $s;
}

function md_inv_view_settle() {
	$months = md_inv_settle_months();
	$ym     = md_inv_get( 'im', $months[1] );
	if ( ! in_array( $ym, $months, true ) ) { $ym = $months[1]; }
	$data   = md_inv_settlement( $ym );
	$vd     = (int) md_inv_get( 'ivd', 0 );
	$total  = 0;
	?>
	<p class="iv-crumb iv-no-print"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'orders' ) ) ); ?>">← 주문</a></p>
	<h2 class="iv-h2">월말 업체 정산</h2>
	<p class="iv-help iv-no-print">한 달 동안 업체별로 들어온 것(입고)과 돌려보낸 것(반품)입니다. 거래명세서 · 세금계산서와 맞춰 보세요. 무상 제공은 금액에서 빠집니다.</p>
	<form class="iv-filter iv-no-print" method="get" data-autosubmit><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="settle">
		<label class="iv-f"><span>달</span><select class="iv-input" name="im"><?php foreach ( $months as $m ) : ?><option value="<?php echo esc_attr( $m ); ?>"<?php selected( $ym, $m ); ?>><?php echo esc_html( (int) substr( $m, 0, 4 ) . '년 ' . (int) substr( $m, 5 ) . '월' ); ?></option><?php endforeach; ?></select></label>
		<label class="iv-f"><span>업체</span><select class="iv-input" name="ivd"><option value="">모든 업체</option><?php foreach ( array_keys( $data ) as $vid ) : ?><option value="<?php echo (int) $vid; ?>"<?php selected( $vd, (int) $vid ); ?>><?php echo esc_html( md_inv_vendor_name( $vid ) ? md_inv_vendor_name( $vid ) : '업체 없음' ); ?></option><?php endforeach; ?></select></label>
		<button class="iv-btn iv-btn--ghost">보기</button>
		<a class="iv-btn iv-btn--primary" href="<?php echo esc_url( md_inv_dl_url( 'settle', array( 'im' => $ym ) ) ); ?>"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?>정산 엑셀</a>
		<button type="button" class="iv-btn iv-btn--ghost" data-print>인쇄</button>
	</form>
	<p class="iv-print-head">문치과병원 업체 정산 · <?php echo esc_html( $ym ); ?></p>
	<?php if ( ! $data ) { md_inv_empty( '이 달에 입고 · 반품이 없습니다.' ); return; } ?>
	<div class="iv-table-wrap"><table class="iv-table">
		<thead><tr><th>업체</th><th class="r">건수</th><th class="r">입고</th><th class="r">반품</th><th class="r">무상</th><th class="r">정산 금액</th></tr></thead>
		<tbody>
		<?php foreach ( $data as $vid => $lines ) : $s = md_inv_settle_sum( $lines ); $total += $s['net']; ?>
			<tr><td data-l="업체"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'settle', 'im' => $ym, 'ivd' => $vid ) ) ); ?>"><?php echo esc_html( md_inv_vendor_name( $vid ) ? md_inv_vendor_name( $vid ) : '업체 없음' ); ?></a><?php $vv = md_inv_vendor( $vid ); echo $vv && $vv->prepaid ? ' <span class="iv-tag iv-tag--pp">선납</span>' : ''; ?></td>
				<td data-l="건수" class="r"><?php echo (int) $s['n']; ?></td><td data-l="입고" class="r"><?php echo esc_html( md_inv_num( $s['buy'] ) ); ?></td><td data-l="반품" class="r"><?php echo $s['ret'] ? '−' . esc_html( md_inv_num( $s['ret'] ) ) : ''; ?></td><td data-l="무상" class="r"><?php echo $s['free'] ? esc_html( md_inv_num( $s['free'] ) ) : ''; ?></td><td data-l="정산 금액" class="r"><b><?php echo esc_html( md_inv_num( $s['net'] ) ); ?></b></td></tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot><tr><td colspan="5" class="r"><b>합계</b></td><td class="r"><b><?php echo esc_html( md_inv_won( $total ) ); ?></b></td></tr></tfoot>
	</table></div>
	<?php foreach ( $data as $vid => $lines ) :
		if ( $vd && $vd !== (int) $vid ) { continue; }
		if ( ! $vd && count( $data ) > 1 ) { continue; } /* 업체를 고르거나 업체가 하나일 때만 상세 */
		?>
		<h3 class="iv-h3"><?php echo esc_html( md_inv_vendor_name( $vid ) ); ?> 상세</h3>
		<div class="iv-table-wrap"><table class="iv-table">
			<thead><tr><th>일자</th><th>구분</th><th>품목</th><th class="r">수량</th><th class="r">단가</th><th class="r">금액</th><th>비고</th></tr></thead>
			<tbody><?php foreach ( $lines as $l ) : $amt = abs( (int) $l->qty ) * (int) $l->price; ?>
				<tr><td data-l="일자"><?php echo esc_html( md_inv_date( $l->created_at, 'n/j' ) ); ?></td><td data-l="구분"><?php echo esc_html( $l->free ? '무상' : md_inv_type_label( $l->type ) ); ?></td><td data-l="품목"><?php echo esc_html( $l->item_name ); ?></td><td data-l="수량" class="r"><?php echo abs( (int) $l->qty ); ?></td><td data-l="단가" class="r"><?php echo esc_html( md_inv_num( $l->price ) ); ?></td><td data-l="금액" class="r"><?php echo esc_html( ( 'return' === $l->type ? '−' : '' ) . md_inv_num( $l->free ? 0 : $amt ) ); ?></td><td data-l="비고"><?php echo esc_html( $l->note ); ?></td></tr>
			<?php endforeach; ?></tbody>
		</table></div>
	<?php endforeach; ?>
	<?php if ( ! $vd && count( $data ) > 1 ) : ?><p class="iv-help iv-no-print">업체 이름을 누르면 그 업체의 입고 · 반품 상세가 나옵니다. 엑셀에는 전 업체 상세가 함께 들어갑니다.</p><?php endif; ?>
	<?php
}

/** 정산 엑셀 — 요약 시트 + 상세 시트 */
function md_inv_settle_xlsx( $ym ) {
	$data = md_inv_settlement( $ym );
	$sum  = array( 'title' => $ym . ' 업체 정산', 'head' => array( '업체', '건수', '입고', '반품', '무상', '정산 금액', '선납' ), 'rows' => array(), 'num' => array( 1 => 'n', 2 => 'won', 3 => 'won', 4 => 'won', 5 => 'won' ), 'width' => array( 22, 8, 14, 14, 12, 15, 6 ) );
	$det  = array( 'title' => $ym . ' 상세', 'head' => array( '업체', '일자', '구분', '품목', '수량', '단위', '단가', '금액', '비고' ), 'rows' => array(), 'num' => array( 4 => 'n', 6 => 'won', 7 => 'won' ), 'width' => array( 20, 11, 6, 38, 7, 6, 11, 13, 26 ) );
	$tot  = 0;
	foreach ( $data as $vid => $lines ) {
		$s = md_inv_settle_sum( $lines );
		$v = md_inv_vendor( $vid );
		$tot += $s['net'];
		$sum['rows'][] = array( $v ? $v->name : '업체 없음', $s['n'], $s['buy'], $s['ret'], $s['free'], $s['net'], $v && $v->prepaid ? '선납' : '' );
		foreach ( $lines as $l ) {
			$amt = abs( (int) $l->qty ) * (int) $l->price;
			$det['rows'][] = array( $v ? $v->name : '', substr( $l->created_at, 0, 10 ), $l->free ? '무상' : md_inv_type_label( $l->type ), $l->item_name, abs( (int) $l->qty ), $l->unit, (int) $l->price, $l->free ? 0 : ( 'return' === $l->type ? -$amt : $amt ), $l->note );
		}
	}
	$sum['rows'][] = array( '합계', '', '', '', '', $tot, '' );
	return md_inv_xlsx( array( $sum, $det ) );
}

/* ============================================================
 * 4 · 휴대폰 홈 화면 아이콘 (웹 앱 정보)
 * ============================================================ */

function md_inv_manifest() {
	if ( empty( $_GET['md_inv_manifest'] ) ) { return; }
	$icon = get_stylesheet_directory_uri() . '/assets/img/';
	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=UTF-8' );
	echo wp_json_encode( array(
		'name'             => '문치과병원 재료실',
		'short_name'       => '재료실',
		'start_url'        => home_url( '/직원/?app=stock' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'background_color' => '#F6F1EA',
		'theme_color'      => '#8B6A4E',
		'lang'             => 'ko',
		'icons'            => array(
			array( 'src' => $icon . 'inv-icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable' ),
			array( 'src' => $icon . 'inv-icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable' ),
		),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}
add_action( 'init', 'md_inv_manifest', 1 );

function md_inv_head_app() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'stock' !== md_sup_current_app() ) { return; }
	$icon = get_stylesheet_directory_uri() . '/assets/img/inv-icon-180.png';
	echo '<link rel="manifest" href="' . esc_url( add_query_arg( 'md_inv_manifest', '1', home_url( '/' ) ) ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $icon ) . '">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes"><meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="재료실">' . "\n";
}
add_action( 'wp_head', 'md_inv_head_app', 2 );

/** 신청 화면 아래 「홈 화면에 추가」 안내 */
function md_inv_install_help() {
	?>
	<div class="iv-install" id="iv-install" hidden>
		<b>📱 휴대폰 홈 화면에 「재료실」 아이콘 만들기</b>
		<button type="button" class="iv-btn iv-btn--primary iv-btn--sm" id="iv-install-btn" hidden>홈 화면에 추가</button>
		<span class="iv-install__ios" hidden>아이폰: 아래 <b>공유 버튼 ⬆︎</b> → <b>「홈 화면에 추가」</b></span>
		<span class="iv-install__and" hidden>안드로이드: 오른쪽 위 <b>⋮</b> → <b>「홈 화면에 추가」</b></span>
		<button type="button" class="iv-x" id="iv-install-x" aria-label="닫기">×</button>
	</div>
	<?php
}

/* ============================================================
 * 3 · 처리된 신청 표시 — 최근 14일 처리된 신청 (팀 · 처리 시각)
 * ============================================================ */

function md_inv_done_feed() {
	global $wpdb;
	$since = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 14 * DAY_IN_SECONDS );
	$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT id, team_id, done_at, status FROM ' . md_inv_t( 'req' ) . " WHERE status IN ('done','rejected') AND done_at >= %s ORDER BY done_at DESC LIMIT 500", $since ) );
	$out = array();
	foreach ( $rows as $r ) { $out[] = array( 'i' => (int) $r->id, 't' => (int) $r->team_id, 'a' => $r->done_at, 's' => 'done' === $r->status ? 1 : 0 ); }
	return array( 'now' => current_time( 'mysql' ), 'list' => $out );
}
