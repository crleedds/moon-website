<?php
/**
 * 재료실 — v9.36 한꺼번에 입고 (원장 지시)
 *   거래명세서 한 장을 보고 여러 줄을 한 번에: 기다리는 주문은 「이번에 들어온 수량」을 줄마다 적고(적게 적으면 일부 입고 — 남은 수량은 계속 기다림),
 *   주문 없이 들어온 품목도 같은 화면에서 줄을 더해 함께 입고한다. 처리는 기존 md_inv_ord_receive · md_inv_do_in 그대로.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 주문 한 줄의 기본값 — 처음 입고면 받기로 한 무상까지 (카드의 「입고」 버튼과 같은 규칙) */
function md_inv_inmany_defaults( $o ) {
	$left  = max( 0, (int) $o->qty - (int) $o->recv_qty );
	$first = ! (int) $o->recv_qty;
	return array(
		'left'  => $left,
		'qty'   => $left + ( $first ? (int) $o->free_qty : 0 ),
		'free'  => $first && (int) $o->free_qty ? (int) $o->free_qty : '',
		'price' => (int) $o->price,
	);
}

/* ============================================================
 * 처리
 * ============================================================ */

function md_inv_act_in_many() {
	$ids  = array_values( array_unique( array_filter( array_map( 'intval', (array) md_inv_p( 'rids', array() ) ) ) ) );
	$rq   = (array) md_inv_p( 'rq', array() );
	$rf   = (array) md_inv_p( 'rf', array() );
	$rp   = (array) md_inv_p( 'rp', array() );
	$rc   = (array) md_inv_p( 'rc', array() );
	$note = md_inv_txt( md_inv_p( 'note' ), 300 );
	$tag  = '한꺼번에 입고' . ( '' !== $note ? ' · ' . $note : '' );

	$ok = 0; $partial = 0; $fail = array(); $names = array();

	/* 1) 기다리는 주문 — 줄마다 적은 수량으로 (주문보다 적으면 일부 입고) */
	foreach ( $ids as $id ) {
		$o = md_inv_ord( $id );
		if ( ! $o || 'ordered' !== $o->status ) { continue; }
		$d   = md_inv_inmany_defaults( $o );
		$qty = isset( $rq[ $id ] ) && '' !== trim( (string) $rq[ $id ] ) ? md_inv_int( $rq[ $id ] ) : (int) $d['qty'];
		if ( $qty < 1 ) { $fail[] = $o->item_name . ': 들어온 수량이 없습니다'; continue; }
		$r = md_inv_ord_receive( $id, $qty, array(
			'free_qty' => isset( $rf[ $id ] ) ? $rf[ $id ] : '',
			'price'    => isset( $rp[ $id ] ) ? $rp[ $id ] : '',
			'close'    => ! empty( $rc[ $id ] ) ? 1 : 0,
			'note'     => $tag,
		) );
		if ( is_wp_error( $r ) ) {
			$fail[] = $o->item_name . ': ' . ( 'more' === $r->get_error_code() ? '주문보다 많습니다 (남은 ' . $d['left'] . '개) — 할 일의 「입고」 단추로 처리해 주세요' : $r->get_error_message() );
			continue;
		}
		$ok++; $names[] = $o->item_name;
		$o2 = md_inv_ord( $id );
		if ( $o2 && 'ordered' === $o2->status ) { $partial++; }
	}

	/* 2) 주문 없이 들어온 품목 — nitem1 … nitemN (품목 고르기 칸은 「이름 · 업체 #번호」 → 번호는 inv-ui 의 _pick 처리가 채운다) */
	$nq = (array) md_inv_p( 'nq', array() );
	$nf = (array) md_inv_p( 'nf', array() );
	$np = (array) md_inv_p( 'np', array() );
	for ( $i = 1; $i <= 40; $i++ ) {
		$item = (int) md_inv_p( 'nitem' . $i );
		$pick = trim( (string) md_inv_p( 'nitem' . $i . '_pick' ) );
		if ( ! $item && '' === $pick && '' === trim( (string) ( isset( $nq[ $i ] ) ? $nq[ $i ] : '' ) ) ) { continue; }
		if ( ! $item ) { $fail[] = ( '' !== $pick ? '「' . mb_substr( $pick, 0, 20 ) . '」' : $i . '번째 줄' ) . ': 품목을 목록에서 골라 주세요'; continue; }
		$it = md_inv_item( $item );
		if ( ! $it ) { $fail[] = $i . '번째 줄: 품목을 찾을 수 없습니다'; continue; }
		$qty = isset( $nq[ $i ] ) ? md_inv_int( $nq[ $i ] ) : 0;
		if ( $qty < 1 ) { $fail[] = $it->name . ': 들어온 수량을 적어 주세요'; continue; }
		$r = md_inv_do_in( $item, $qty, array(
			'free_qty' => isset( $nf[ $i ] ) ? $nf[ $i ] : '',
			'price'    => isset( $np[ $i ] ) ? $np[ $i ] : '',
			'note'     => $tag,
		) );
		if ( is_wp_error( $r ) ) { $fail[] = $it->name . ': ' . $r->get_error_message(); continue; }
		$ok++; $names[] = $it->name;
	}

	if ( ! $ok && ! $fail ) { md_inv_go( 'err', '입고할 줄을 고르거나 적어 주세요.' ); }
	$msg = $ok . '건을 입고했습니다. 재고에 더했습니다.';
	if ( $partial ) { $msg .= ' 일부만 들어온 주문 ' . $partial . '건은 남은 수량을 계속 기다립니다.'; }
	if ( $fail ) {
		md_inv_go( $ok ? 'warn' : 'err', ( $ok ? $msg . ' ' : '' ) . '못 한 것 ' . count( $fail ) . '건: ' . implode( ' / ', array_slice( $fail, 0, 4 ) ) . ( count( $fail ) > 4 ? ' …' : '' ) );
	}
	md_inv_go( 'ok', $msg );
}

/* ============================================================
 * 화면
 * ============================================================ */

function md_inv_view_inmany() {
	$ords = md_inv_ords( array( 'status' => 'ordered', 'limit' => 0 ) );
	usort( $ords, function ( $a, $b ) { return strcmp( md_inv_vendor_name( $a->vendor_id ), md_inv_vendor_name( $b->vendor_id ) ) ?: strcmp( $a->item_name, $b->item_name ); } );
	$vendors = array();
	foreach ( $ords as $o ) { $vendors[ (int) $o->vendor_id ] = md_inv_vendor_name( $o->vendor_id ) ?: '업체 없음'; }
	$pre = (int) md_inv_get( 'ivd', 0 );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'todo' ) ) ); ?>">← 할 일</a></p>
	<h2 class="iv-h2">한꺼번에 입고</h2>
	<p class="iv-help">거래명세서 한 장을 보고 여러 줄을 한 번에 적습니다. 주문해 둔 것은 아래 표에서 골라 <b>이번에 들어온 수량</b>을 적으세요 — 주문보다 적게 적으면 <b>일부 입고</b>로 남은 수량을 계속 기다립니다(나머지가 오면 다시 이 화면에서 입고). 주문 없이 들어온 품목은 아래 「주문 없이 들어온 품목」에 적습니다. 수량 칸을 고치면 그 줄이 자동으로 골라집니다.</p>

	<form method="post" class="iv-inmany" data-inmany data-confirm="적은 대로 입고할까요? 주문보다 적게 적은 줄은 남은 수량을 계속 기다립니다.">
		<?php md_inv_hidden( 'in_many', md_inv_url( array( 'iv' => 'inmany' ) ) ); ?>

		<h3 class="iv-h3">기다리는 주문 <span class="iv-n"><?php echo count( $ords ); ?></span></h3>
		<?php if ( ! $ords ) : md_inv_empty( '들어오기를 기다리는 주문이 없습니다.' ); else : ?>
			<div class="iv-bulkbar">
				<label class="iv-check"><input type="checkbox" data-inmany-all> 보이는 줄 모두 고르기</label>
				<label class="iv-f iv-f--inline"><span>업체</span>
					<select class="iv-input iv-input--sm" data-inmany-vendor>
						<option value="">모든 업체</option>
						<?php foreach ( $vendors as $vid => $vn ) : ?><option value="<?php echo (int) $vid; ?>"<?php selected( $pre, (int) $vid ); ?>><?php echo esc_html( $vn ); ?></option><?php endforeach; ?>
					</select>
				</label>
				<span class="iv-muted">LOT · 배송비(실제로 낸 금액)를 따로 적어야 하면 할 일의 카드 「입고」로</span>
			</div>
			<div class="iv-table-wrap"><table class="iv-table iv-table--inmany">
				<thead><tr><th></th><th>품목</th><th>업체</th><th class="r">주문</th><th class="r">이번에 들어온 수량</th><th class="r">그중 무상</th><th class="r">개당 단가 (원)</th><th>덜 와도 마감</th></tr></thead>
				<tbody>
				<?php foreach ( $ords as $o ) : $d = md_inv_inmany_defaults( $o ); $v = md_inv_vendor( $o->vendor_id ); ?>
					<tr data-v="<?php echo (int) $o->vendor_id; ?>" data-row>
						<td data-l=""><input type="checkbox" name="rids[]" value="<?php echo (int) $o->id; ?>" data-row-check aria-label="이 주문 입고"></td>
						<td data-l="품목" class="iv-td-name"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $o->item_id ) ) ); ?>"><?php echo esc_html( $o->item_name ); ?></a> <?php echo md_inv_ord_badge( $o ); // phpcs:ignore ?><small><?php echo esc_html( md_inv_date( $o->created_at, 'n/j' ) . ' 주문 · ' . $o->person ); ?></small></td>
						<td data-l="업체"><?php echo esc_html( ( $v ? $v->name . ( $v->prepaid ? ' (선납)' : '' ) : '업체 없음' ) ); ?></td>
						<td data-l="주문" class="r"><span><b><?php echo (int) $o->qty; ?></b><?php echo esc_html( $o->unit ); ?><?php echo (int) $o->free_qty ? ' + 무상 ' . (int) $o->free_qty : ''; ?><?php echo (int) $o->recv_qty ? ' <small>(입고 ' . (int) $o->recv_qty . ' · 남은 ' . (int) $d['left'] . ')</small>' : ''; ?></span></td>
						<td data-l="이번에 들어온 수량" class="r"><span class="iv-inline"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="0" name="rq[<?php echo (int) $o->id; ?>]" value="<?php echo (int) $d['qty']; ?>" data-left="<?php echo (int) $d['left']; ?>" aria-label="이번에 들어온 수량"> <?php echo esc_html( $o->unit ); ?></span></td>
						<td data-l="그중 무상" class="r"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="0" name="rf[<?php echo (int) $o->id; ?>]" value="<?php echo esc_attr( $d['free'] ); ?>" placeholder="0" aria-label="무상 수량"></td>
						<td data-l="개당 단가 (원)" class="r"><input class="iv-input iv-input--num iv-input--won" inputmode="numeric" autocomplete="off" name="rp[<?php echo (int) $o->id; ?>]" value="<?php echo (int) $d['price']; ?>" aria-label="개당 단가"></td>
						<td data-l="덜 와도 마감"><label class="iv-check"><input type="checkbox" name="rc[<?php echo (int) $o->id; ?>]" value="1"> 마감</label></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		<?php endif; ?>

		<h3 class="iv-h3">주문 없이 들어온 품목</h3>
		<p class="iv-help">품목을 목록에서 고르고 수량을 적으세요. 단가를 비우면 품목에 적힌 단가로 넣습니다.</p>
		<div class="iv-table-wrap"><table class="iv-table iv-table--inmany iv-table--inmany-new">
			<thead><tr><th>품목</th><th class="r">들어온 총 수량</th><th class="r">그중 무상</th><th class="r">개당 단가 (원)</th></tr></thead>
			<tbody data-inmany-rows>
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
				<tr data-new-row>
					<td data-l="품목" class="iv-td-name"><?php md_inv_item_picker( 'nitem' . $i, false, 0, '품목 이름을 입력해 고르세요' ); ?></td>
					<td data-l="들어온 총 수량" class="r"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="0" name="nq[<?php echo $i; ?>]" placeholder="0" aria-label="들어온 총 수량"></td>
					<td data-l="그중 무상" class="r"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="0" name="nf[<?php echo $i; ?>]" placeholder="0" aria-label="무상 수량"></td>
					<td data-l="개당 단가 (원)" class="r"><input class="iv-input iv-input--num iv-input--won" inputmode="numeric" autocomplete="off" name="np[<?php echo $i; ?>]" placeholder="품목 단가" aria-label="개당 단가"></td>
				</tr>
			<?php endfor; ?>
			</tbody>
		</table></div>
		<template id="iv-inmany-tpl"><tr data-new-row>
			<td data-l="품목" class="iv-td-name"><input type="text" class="iv-input" list="iv-items-dl" name="nitem__N___pick" data-pick="nitem__N__" autocomplete="off" placeholder="품목 이름을 입력해 고르세요"><input type="hidden" name="nitem__N__" value="0"></td>
			<td data-l="들어온 총 수량" class="r"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="0" name="nq[__N__]" placeholder="0" aria-label="들어온 총 수량"></td>
			<td data-l="그중 무상" class="r"><input class="iv-input iv-input--num" type="number" inputmode="numeric" min="0" name="nf[__N__]" placeholder="0" aria-label="무상 수량"></td>
			<td data-l="개당 단가 (원)" class="r"><input class="iv-input iv-input--num iv-input--won" inputmode="numeric" autocomplete="off" name="np[__N__]" placeholder="품목 단가" aria-label="개당 단가"></td>
		</tr></template>
		<p><button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-inmany-add>＋ 줄 더 추가</button></p>

		<label class="iv-f"><span>메모 <small>(선택 — 거래명세서 번호 등, 모든 줄에 같이 남습니다)</small></span><input class="iv-input" name="note" maxlength="300" placeholder="예: 거래명세서 2026-1009"></label>
		<div class="iv-stickyfoot iv-inmany__foot">
			<span class="iv-muted" data-inmany-sum>고른 주문 0건</span>
			<button class="iv-btn iv-btn--primary iv-btn--lg" data-inmany-submit>적은 대로 입고</button>
		</div>
	</form>
	<?php
}
