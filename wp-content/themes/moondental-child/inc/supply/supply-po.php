<?php
/**
 * 재료실 — 발주 (재고 담당자 전용)
 *
 * 왜 필요한가
 *   v3.63 까지 흐름은 「부족 → (빈칸) → 입고」였다. 그 빈칸 때문에
 *   주문을 넣었는데 아직 안 온 것인지, 아예 주문을 안 한 것인지
 *   화면만 봐서는 구분되지 않았다. 그래서 같은 품목을 두 번 주문하거나,
 *   아무도 주문하지 않은 채로 재고가 바닥나는 일이 생긴다.
 *
 * 상태
 *   draft(작성 중) → ordered(주문함) → received(입고 완료)
 *   작성 중에는 수량을 고칠 수 있고, 주문한 뒤에는 입고만 받는다.
 *
 * 입고는 여전히 원장을 지난다
 *   발주로 받아도 md_sup_move( +수량, 'in' ) 로 기록한다.
 *   발주는 「무엇을 주문했나」를 적어 두는 장부일 뿐, 재고의 근거는 아니다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 데이터
 * ============================================================ */

/**
 * 발주서를 만든다.
 *
 * @param string $vendor  거래처
 * @param array  $qty_map [ item_id => 주문 수량 ]
 * @return int|WP_Error 발주 id
 */
function md_sup_po_create( $vendor, $qty_map, $note = '' ) {
	global $wpdb;
	$t = md_sup_tables();

	$lines = array();
	foreach ( (array) $qty_map as $item_id => $q ) {
		$q = (int) $q;
		if ( $q > 0 ) { $lines[ (int) $item_id ] = $q; }
	}
	if ( empty( $lines ) ) { return new WP_Error( 'empty', '주문할 품목이 없습니다.' ); }

	$ok = $wpdb->insert( $t['po'], array(
		'vendor'     => mb_substr( sanitize_text_field( (string) $vendor ), 0, 100 ),
		'status'     => 'draft',
		'note'       => mb_substr( sanitize_text_field( (string) $note ), 0, 500 ),
		'user_id'    => get_current_user_id(),
		'created_at' => current_time( 'mysql' ),
	), array( '%s', '%s', '%s', '%d', '%s' ) );

	if ( ! $ok ) { return new WP_Error( 'db', '발주서를 저장하지 못했습니다.' ); }
	$po_id = (int) $wpdb->insert_id;

	/* 단가는 만들 때 값으로 굳혀 둔다 — 나중에 품목 단가가 바뀌어도
	 * 그때 얼마에 주문했는지가 남아야 한다. */
	foreach ( $lines as $item_id => $q ) {
		$it = md_sup_item( $item_id );
		$wpdb->insert( $t['po_line'], array(
			'po_id'     => $po_id,
			'item_id'   => (int) $item_id,
			'qty_order' => $q,
			'qty_recv'  => 0,
			'price'     => $it ? (int) $it->price : 0,
		), array( '%d', '%d', '%d', '%d', '%d' ) );
	}

	return $po_id;
}

/** 발주 한 건 */
function md_sup_po( $po_id ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['po']} WHERE id = %d", (int) $po_id ) );
}

/**
 * 발주 목록. 줄 수와 금액을 함께 붙인다.
 *
 * @param array $args status(문자열 또는 배열) · limit
 */
function md_sup_po_list( $args = array() ) {
	global $wpdb;
	$t = md_sup_tables();
	$a = wp_parse_args( $args, array( 'status' => '', 'limit' => 30 ) );

	$where  = array( '1=1' );
	$params = array();

	if ( is_array( $a['status'] ) && $a['status'] ) {
		$in       = implode( ',', array_fill( 0, count( $a['status'] ), '%s' ) );
		$where[]  = "p.status IN ($in)";
		$params   = array_merge( $params, array_values( $a['status'] ) );
	} elseif ( is_string( $a['status'] ) && '' !== $a['status'] ) {
		$where[]  = 'p.status = %s';
		$params[] = $a['status'];
	}
	$params[] = (int) $a['limit'];

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT p.*,
		        (SELECT COUNT(*) FROM {$t['po_line']} WHERE po_id = p.id) AS line_count,
		        (SELECT COALESCE(SUM(qty_order * price),0) FROM {$t['po_line']} WHERE po_id = p.id) AS amount,
		        (SELECT COALESCE(SUM(qty_order - qty_recv),0) FROM {$t['po_line']} WHERE po_id = p.id) AS qty_open
		 FROM {$t['po']} p
		 WHERE " . implode( ' AND ', $where ) . '
		 ORDER BY p.id DESC LIMIT %d',
		$params
	) );
}

/** 발주서에 달린 품목 줄 (품목 정보와 현재고 포함) */
function md_sup_po_lines( $po_id ) {
	global $wpdb;
	$t = md_sup_tables();

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT pl.*,
		        COALESCE(i.name,'') AS name, COALESCE(i.code,'') AS code,
		        COALESCE(i.unit,'') AS unit, COALESCE(i.min_stock,0) AS min_stock,
		        COALESCE((SELECT SUM(qty) FROM {$t['ledger']} WHERE item_id = pl.item_id), 0) AS stock
		 FROM {$t['po_line']} pl LEFT JOIN {$t['items']} i ON i.id = pl.item_id
		 WHERE pl.po_id = %d ORDER BY pl.id ASC",
		(int) $po_id
	) );
}

/**
 * 아직 안 들어온 주문 수량 [ item_id => 수량 ].
 * 「이미 주문해 둔 것」을 재고·입고 화면에 띄워 두 번 주문하는 일을 막는다.
 */
function md_sup_po_open_map() {
	global $wpdb;
	$t = md_sup_tables();

	$rows = $wpdb->get_results(
		"SELECT pl.item_id, SUM(pl.qty_order - pl.qty_recv) AS qty
		 FROM {$t['po_line']} pl INNER JOIN {$t['po']} p ON p.id = pl.po_id
		 WHERE p.status = 'ordered' AND pl.qty_order > pl.qty_recv
		 GROUP BY pl.item_id"
	);

	$map = array();
	foreach ( $rows as $r ) { $map[ (int) $r->item_id ] = (int) $r->qty; }
	return $map;
}

/** 작성 중인 발주서의 수량을 고친다. 0 으로 두면 그 줄을 뺀다. */
function md_sup_po_save_lines( $po_id, $qty_map ) {
	global $wpdb;
	$t  = md_sup_tables();
	$po = md_sup_po( $po_id );
	if ( ! $po ) { return new WP_Error( 'not_found', '발주서를 찾을 수 없습니다.' ); }
	if ( 'draft' !== $po->status ) { return new WP_Error( 'locked', '주문한 뒤에는 수량을 고칠 수 없습니다.' ); }

	foreach ( (array) $qty_map as $line_id => $q ) {
		$line_id = (int) $line_id;
		$q       = (int) $q;
		if ( $q > 0 ) {
			$wpdb->update( $t['po_line'], array( 'qty_order' => $q ), array( 'id' => $line_id, 'po_id' => (int) $po_id ), array( '%d' ), array( '%d', '%d' ) );
		} else {
			$wpdb->delete( $t['po_line'], array( 'id' => $line_id, 'po_id' => (int) $po_id ), array( '%d', '%d' ) );
		}
	}
	return true;
}

/** 주문 확정 — 이제부터 수량을 고칠 수 없고 「주문 중」으로 잡힌다 */
function md_sup_po_mark_ordered( $po_id ) {
	global $wpdb;
	$t = md_sup_tables();

	/* 수량을 다 0 으로 지워 빈 발주서가 됐을 수 있다 —
	 * 빈 채로 확정하면 「주문 중」인데 올 물건이 없는 유령이 남는다. */
	$lines = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$t['po_line']} WHERE po_id = %d", (int) $po_id
	) );
	if ( $lines < 1 ) { return new WP_Error( 'empty', '품목이 없는 발주서는 주문 확정할 수 없습니다.' ); }

	$n = $wpdb->query( $wpdb->prepare(
		"UPDATE {$t['po']} SET status = 'ordered', ordered_at = %s WHERE id = %d AND status = 'draft'",
		current_time( 'mysql' ), (int) $po_id
	) );
	return $n ? true : new WP_Error( 'state', '작성 중인 발주서만 주문 확정할 수 있습니다.' );
}

/**
 * 발주 입고 처리.
 *
 * 받은 수량을 줄마다 적어 원장에 입고로 남긴다.
 * 전부 다 들어왔으면 「입고 완료」로 닫고, 일부만 왔으면 「주문함」에 남겨
 * 나머지가 언제 오는지 계속 보이게 한다.
 *
 * @param array $recv_map [ line_id => 이번에 받은 수량 ]
 * @return int|WP_Error 원장에 기록한 줄 수
 */
function md_sup_po_receive( $po_id, $recv_map ) {
	global $wpdb;
	$t  = md_sup_tables();
	$po = md_sup_po( $po_id );
	if ( ! $po ) { return new WP_Error( 'not_found', '발주서를 찾을 수 없습니다.' ); }
	if ( 'ordered' !== $po->status ) { return new WP_Error( 'state', '주문한 발주서만 입고 처리할 수 있습니다.' ); }

	$n = 0;
	foreach ( md_sup_po_lines( $po_id ) as $ln ) {
		$got = isset( $recv_map[ $ln->id ] ) ? (int) $recv_map[ $ln->id ] : 0;
		if ( $got <= 0 || (int) $ln->item_id <= 0 ) { continue; }

		md_sup_move( $ln->item_id, $got, 'in', 0, (int) $po_id, '발주 #' . (int) $po_id . ' 입고' );
		$wpdb->update(
			$t['po_line'],
			array( 'qty_recv' => (int) $ln->qty_recv + $got ),
			array( 'id' => (int) $ln->id ),
			array( '%d' ),
			array( '%d' )
		);
		$n++;
	}

	$open = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE(SUM(qty_order - qty_recv),0) FROM {$t['po_line']} WHERE po_id = %d AND qty_order > qty_recv",
		(int) $po_id
	) );

	if ( $open <= 0 ) {
		$wpdb->update(
			$t['po'],
			array( 'status' => 'received', 'received_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $po_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	return $n;
}

/**
 * 발주 취소.
 * 작성 중이면 통째로 지우고, 이미 주문한 것은 기록을 남기려 상태만 바꾼다.
 */
function md_sup_po_cancel( $po_id ) {
	global $wpdb;
	$t  = md_sup_tables();
	$po = md_sup_po( $po_id );
	if ( ! $po ) { return new WP_Error( 'not_found', '발주서를 찾을 수 없습니다.' ); }
	if ( 'received' === $po->status ) { return new WP_Error( 'state', '이미 입고된 발주서입니다.' ); }

	if ( 'draft' === $po->status ) {
		$wpdb->delete( $t['po_line'], array( 'po_id' => (int) $po_id ), array( '%d' ) );
		$wpdb->delete( $t['po'], array( 'id' => (int) $po_id ), array( '%d' ) );
		return true;
	}

	$wpdb->update( $t['po'], array( 'status' => 'cancelled' ), array( 'id' => (int) $po_id ), array( '%s' ), array( '%d' ) );
	return true;
}

/**
 * 부족한 품목을 거래처별로 묶어 준다 — 발주서 만들기의 재료.
 *
 * 주문 제안 수량 = (적정재고 − 현재고) + 월평균 한 달치
 *   적정선까지 채우고, 물건이 오는 동안 쓸 만큼을 더한다.
 *   이미 주문해 둔 수량은 빼서 두 번 주문하지 않게 한다.
 *
 * @return array [ 거래처 => 품목 목록(각 품목에 suggest 를 붙임) ]
 */
function md_sup_po_shortage_by_vendor() {
	$items = md_sup_items( array( 'low_only' => true ) );
	if ( empty( $items ) ) { return array(); }

	$avg  = md_sup_avg_map_all( 3 );
	$open = md_sup_po_open_map();

	$out = array();
	foreach ( $items as $it ) {
		$a       = isset( $avg[ $it->id ] ) ? (float) $avg[ $it->id ] : 0;
		$ordered = isset( $open[ $it->id ] ) ? (int) $open[ $it->id ] : 0;

		$want = (int) ceil( ( (int) $it->min_stock - (int) $it->stock ) + $a ) - $ordered;
		if ( $want < 1 ) { $want = 0; }

		$it->suggest = $want;
		$it->ordered = $ordered;

		$key = '' !== trim( (string) $it->vendor ) ? $it->vendor : '(거래처 미지정)';
		if ( ! isset( $out[ $key ] ) ) { $out[ $key ] = array(); }
		$out[ $key ][] = $it;
	}

	ksort( $out );
	return $out;
}

/* ============================================================
 * 화면
 * ============================================================ */

/** ⑥ 발주 */
function md_sup_render_order() {
	$po_id = isset( $_GET['po'] ) ? (int) $_GET['po'] : 0;
	if ( $po_id ) { md_sup_render_po_detail( $po_id ); return; }

	$shortage = md_sup_po_shortage_by_vendor();
	$open     = md_sup_po_list( array( 'status' => array( 'draft', 'ordered' ), 'limit' => 30 ) );
	$closed   = md_sup_po_list( array( 'status' => array( 'received', 'cancelled' ), 'limit' => 10 ) );
	?>

	<h2 class="mds-h2" style="margin-top:0">주문 중 · 작성 중 <span class="mds-count"><?php echo count( $open ); ?></span></h2>
	<?php if ( empty( $open ) ) : ?>
		<p class="mds-empty">진행 중인 발주가 없습니다.</p>
	<?php else : ?>
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>거래처</th><th>상태</th><th class="num">품목 수</th><th class="num">미입고</th><th class="num">금액</th><th>만든 날</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $open as $p ) : ?>
					<tr>
						<td class="mds-item" data-label="거래처"><b><?php echo esc_html( $p->vendor ); ?></b><span class="mds-item__meta">발주 #<?php echo (int) $p->id; ?></span></td>
						<td data-label="상태"><span class="mds-status is-po-<?php echo esc_attr( $p->status ); ?>"><?php echo esc_html( md_sup_po_status_label( $p->status ) ); ?></span></td>
						<td class="num" data-label="품목 수"><?php echo (int) $p->line_count; ?></td>
						<td class="num" data-label="미입고"><?php echo (int) $p->qty_open; ?></td>
						<td class="num" data-label="금액"><?php echo esc_html( number_format( (int) $p->amount ) ); ?></td>
						<td data-label="만든 날"><?php echo esc_html( mysql2date( 'Y-m-d', $p->created_at ) ); ?></td>
						<td class="num"><a class="mds-mini" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'order', 'po' => (int) $p->id ) ) ); ?>">열기</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<h2 class="mds-h2">부족한 품목으로 발주서 만들기</h2>
	<p class="mds-hint">
		적정재고 아래로 떨어진 품목을 거래처별로 묶었습니다.
		제안 수량은 <b>적정재고까지 채울 만큼 + 한 달 쓸 만큼</b>이며, 이미 주문해 둔 수량은 빼고 계산했습니다.
		수량을 고쳐도 되고 0 으로 두면 그 품목은 빠집니다.
	</p>

	<?php if ( empty( $shortage ) ) : ?>
		<p class="mds-empty">
			적정재고 아래로 떨어진 품목이 없습니다.
			적정재고가 0 인 품목은 부족 판정을 하지 않으니, 「재고」 화면에서 제안값을 한 번 넣어 두세요.
		</p>
	<?php else : foreach ( $shortage as $vendor => $items ) : ?>
		<form class="mds-card mds-po-form" method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'order' ) ) ); ?>">
			<?php wp_nonce_field( 'md_sup_po_create', 'md_sup_nonce' ); ?>
			<input type="hidden" name="md_sup_action" value="po_create">
			<input type="hidden" name="vendor" value="<?php echo esc_attr( $vendor ); ?>">

			<h3 class="mds-po-form__vendor"><?php echo esc_html( $vendor ); ?> <span class="mds-count"><?php echo count( $items ); ?></span></h3>

			<div class="mds-tablewrap">
				<table class="mds-table">
					<thead><tr><th>품목</th><th class="num">현재고</th><th class="num">적정</th><th class="num">주문 중</th><th class="num">단가</th><th class="num">주문 수량</th></tr></thead>
					<tbody>
					<?php foreach ( $items as $it ) : ?>
						<tr>
							<td class="mds-item" data-label="품목">
								<b><?php echo esc_html( $it->name ); ?></b>
								<span class="mds-item__meta"><?php echo esc_html( $it->code . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span>
							</td>
							<td class="num is-low" data-label="현재고"><?php echo esc_html( number_format( (int) $it->stock ) ); ?></td>
							<td class="num" data-label="적정"><?php echo (int) $it->min_stock; ?></td>
							<td class="num" data-label="주문 중"><?php echo $it->ordered ? esc_html( number_format( (int) $it->ordered ) ) : '—'; ?></td>
							<td class="num" data-label="단가"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
							<td class="num" data-label="주문 수량">
								<input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
								       name="poqty[<?php echo (int) $it->id; ?>]" value="<?php echo (int) $it->suggest; ?>"
								       aria-label="<?php echo esc_attr( $it->name . ' 주문 수량' ); ?>">
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="mds-submit">
				<input class="mds-note" type="text" name="note" maxlength="200" placeholder="발주 메모 (선택) — 예: 이번 주 금요일까지 필요">
				<button type="submit" class="mds-btn mds-btn--fill"><?php echo esc_html( $vendor ); ?> 발주서 만들기</button>
			</div>
		</form>
	<?php endforeach; endif; ?>

	<?php if ( $closed ) : ?>
		<h2 class="mds-h2">지난 발주</h2>
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>거래처</th><th>상태</th><th class="num">품목 수</th><th class="num">금액</th><th>마친 날</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $closed as $p ) : ?>
					<tr>
						<td class="mds-item" data-label="거래처"><b><?php echo esc_html( $p->vendor ); ?></b><span class="mds-item__meta">발주 #<?php echo (int) $p->id; ?></span></td>
						<td data-label="상태"><span class="mds-status is-po-<?php echo esc_attr( $p->status ); ?>"><?php echo esc_html( md_sup_po_status_label( $p->status ) ); ?></span></td>
						<td class="num" data-label="품목 수"><?php echo (int) $p->line_count; ?></td>
						<td class="num" data-label="금액"><?php echo esc_html( number_format( (int) $p->amount ) ); ?></td>
						<td data-label="마친 날"><?php echo esc_html( $p->received_at ? mysql2date( 'Y-m-d', $p->received_at ) : '—' ); ?></td>
						<td class="num"><a class="mds-mini" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'order', 'po' => (int) $p->id ) ) ); ?>">열기</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif;
}

/** 발주서 한 건 — 수량 편집 · 주문 확정 · 입고 처리 */
function md_sup_render_po_detail( $po_id ) {
	$po = md_sup_po( $po_id );
	if ( ! $po ) {
		echo '<p class="mds-empty">발주서를 찾을 수 없습니다.</p>';
		return;
	}

	$lines  = md_sup_po_lines( $po_id );
	$amount = 0;
	foreach ( $lines as $ln ) { $amount += (int) $ln->qty_order * (int) $ln->price; }

	$is_draft   = ( 'draft' === $po->status );
	$is_ordered = ( 'ordered' === $po->status );
	?>
	<p class="mds-back"><a href="<?php echo esc_url( md_sup_url( array( 'tab' => 'order' ) ) ); ?>">← 발주 목록</a></p>

	<div class="mds-card mds-posum">
		<h2 class="mds-h2" style="margin-top:0"><?php echo esc_html( $po->vendor ); ?> · 발주 #<?php echo (int) $po->id; ?></h2>
		<p class="mds-posum__meta">
			<span class="mds-status is-po-<?php echo esc_attr( $po->status ); ?>"><?php echo esc_html( md_sup_po_status_label( $po->status ) ); ?></span>
			<?php echo count( $lines ); ?>개 품목 ·
			합계 <b><?php echo esc_html( number_format( $amount ) ); ?></b>원 ·
			만든 날 <?php echo esc_html( mysql2date( 'Y-m-d H:i', $po->created_at ) ); ?>
			<?php if ( $po->ordered_at ) : ?> · 주문 <?php echo esc_html( mysql2date( 'Y-m-d', $po->ordered_at ) ); ?><?php endif; ?>
			<?php if ( $po->received_at ) : ?> · 입고 <?php echo esc_html( mysql2date( 'Y-m-d', $po->received_at ) ); ?><?php endif; ?>
		</p>
		<?php if ( '' !== trim( (string) $po->note ) ) : ?>
			<p class="mds-req__note"><?php echo esc_html( $po->note ); ?></p>
		<?php endif; ?>

		<div class="mds-tools" style="margin-bottom:0">
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'order', 'po' => (int) $po->id, 'export' => 'po' ) ) ); ?>">발주서 엑셀(CSV) 내려받기</a>
			<button type="button" class="mds-btn mds-btn--ghost" onclick="window.print()">인쇄</button>
		</div>
	</div>

	<?php if ( $is_draft ) : ?>
		<p class="mds-hint">수량을 고친 뒤 저장하세요. 0 으로 두면 그 품목이 빠집니다. 주문 확정을 누르면 더 이상 고칠 수 없습니다.</p>
	<?php elseif ( $is_ordered ) : ?>
		<p class="mds-hint">들어온 수량을 「이번 입고」에 적고 저장하세요. 나눠서 들어와도 됩니다 — 남은 수량은 계속 「주문 중」으로 보입니다.</p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'order', 'po' => (int) $po->id ) ) ); ?>">
		<?php wp_nonce_field( $is_draft ? 'md_sup_po_save' : 'md_sup_po_receive', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="<?php echo $is_draft ? 'po_save' : 'po_receive'; ?>">
		<input type="hidden" name="po_id" value="<?php echo (int) $po->id; ?>">

		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead>
					<tr>
						<th>품목</th><th class="num">현재고</th><th class="num">적정</th>
						<th class="num">단가</th><th class="num">주문</th><th class="num">받음</th>
						<?php if ( $is_draft ) : ?><th class="num">주문 수량</th>
						<?php elseif ( $is_ordered ) : ?><th class="num">이번 입고</th><?php endif; ?>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $lines ) ) : ?>
					<tr><td colspan="7" class="mds-empty">품목이 없습니다.</td></tr>
				<?php else : foreach ( $lines as $ln ) :
					$left = max( 0, (int) $ln->qty_order - (int) $ln->qty_recv ); ?>
					<tr>
						<td class="mds-item" data-label="품목">
							<b><?php echo esc_html( $ln->name ); ?></b>
							<span class="mds-item__meta"><?php echo esc_html( $ln->code . ( $ln->unit ? ' · ' . $ln->unit : '' ) ); ?></span>
						</td>
						<td class="num<?php echo ( $ln->min_stock > 0 && $ln->stock <= $ln->min_stock ) ? ' is-low' : ''; ?>" data-label="현재고"><?php echo esc_html( number_format( (int) $ln->stock ) ); ?></td>
						<td class="num" data-label="적정"><?php echo (int) $ln->min_stock; ?></td>
						<td class="num" data-label="단가"><?php echo esc_html( number_format( (int) $ln->price ) ); ?></td>
						<td class="num" data-label="주문"><?php echo (int) $ln->qty_order; ?></td>
						<td class="num" data-label="받음"><?php echo (int) $ln->qty_recv; ?></td>
						<?php if ( $is_draft ) : ?>
							<td class="num" data-label="주문 수량">
								<input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
								       name="poqty[<?php echo (int) $ln->id; ?>]" value="<?php echo (int) $ln->qty_order; ?>"
								       aria-label="<?php echo esc_attr( $ln->name . ' 주문 수량' ); ?>">
							</td>
						<?php elseif ( $is_ordered ) : ?>
							<td class="num" data-label="이번 입고">
								<input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
								       name="recv[<?php echo (int) $ln->id; ?>]" value="<?php echo (int) $left; ?>"
								       aria-label="<?php echo esc_attr( $ln->name . ' 이번 입고 수량' ); ?>">
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $is_draft || $is_ordered ) : ?>
			<div class="mds-submit">
				<button type="submit" class="mds-btn mds-btn--fill"><?php echo $is_draft ? '수량 저장' : '입고 처리'; ?></button>
			</div>
		<?php endif; ?>
	</form>

	<?php if ( $is_draft ) : ?>
		<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'order', 'po' => (int) $po->id ) ) ); ?>" class="mds-formbtns" style="margin-top:14px">
			<?php wp_nonce_field( 'md_sup_po_order', 'md_sup_nonce' ); ?>
			<input type="hidden" name="md_sup_action" value="po_order">
			<input type="hidden" name="po_id" value="<?php echo (int) $po->id; ?>">
			<button type="submit" class="mds-btn mds-btn--fill">주문 확정 — 거래처에 주문했습니다</button>
		</form>
	<?php endif; ?>

	<?php if ( 'received' !== $po->status && 'cancelled' !== $po->status ) : ?>
		<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'order' ) ) ); ?>" class="mds-formbtns" style="margin-top:10px">
			<?php wp_nonce_field( 'md_sup_po_cancel', 'md_sup_nonce' ); ?>
			<input type="hidden" name="md_sup_action" value="po_cancel">
			<input type="hidden" name="po_id" value="<?php echo (int) $po->id; ?>">
			<button type="submit" class="mds-btn mds-btn--ghost"
			        onclick="return confirm('<?php echo esc_js( $is_draft ? '이 발주서를 지울까요?' : '이 발주를 취소로 표시할까요?' ); ?>');">
				<?php echo $is_draft ? '발주서 지우기' : '발주 취소'; ?>
			</button>
			<span class="mds-hint" style="margin:0">
				<?php echo $is_draft
					? '작성 중인 발주서는 완전히 지워집니다.'
					: '이미 주문한 발주는 기록을 남기려 「취소함」으로만 바뀝니다.'; ?>
			</span>
		</form>
	<?php endif;
}

/** 발주서 CSV — 거래처에 그대로 보낼 수 있는 형태 */
function md_sup_export_po( $po_id ) {
	$po = md_sup_po( $po_id );
	if ( ! $po ) { return; }
	$lines = md_sup_po_lines( $po_id );

	md_sup_csv_start( 'moondental-po-' . (int) $po->id . '.csv' );
	md_sup_csv_row( array( '한아의료재단 문치과병원 · 발주서', '발주 #' . (int) $po->id ) );
	md_sup_csv_row( array( '거래처', $po->vendor ) );
	md_sup_csv_row( array( '작성일', mysql2date( 'Y-m-d', $po->created_at ) ) );
	if ( '' !== trim( (string) $po->note ) ) { md_sup_csv_row( array( '메모', $po->note ) ); }
	md_sup_csv_row( array() );

	md_sup_csv_row( array( '품목코드', '품목명', '단위', '주문 수량', '단가', '금액(원)' ) );
	$total = 0;
	foreach ( $lines as $ln ) {
		$amt    = (int) $ln->qty_order * (int) $ln->price;
		$total += $amt;
		md_sup_csv_row( array( $ln->code, $ln->name, $ln->unit, (int) $ln->qty_order, (int) $ln->price, $amt ) );
	}
	md_sup_csv_row( array() );
	md_sup_csv_row( array( '', '', '', '', '합계', $total ) );
	exit;
}
