<?php
/**
 * 재료실 — 반출관리 탭 (재고 담당자 전용)
 *
 * 대기 중인 신청을 보고 실제로 나간 수량을 적어 출고 확정한다.
 *
 * 질의를 한 번에 모아 받는다
 *   v3.63 까지는 신청 줄마다 md_sup_stock() 을 불렀다. 대기 30건에
 *   한 건이 20줄이면 600질의가 나가 화면이 눈에 띄게 느렸다.
 *   이제 줄도 재고도 한 번씩만 받아 온다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ③ 반출관리 */
function md_sup_render_manage() {
	$pending = md_sup_requests( array( 'status' => 'pending', 'limit' => 30 ) );

	/* 대기 신청 전부의 줄과 재고를 한 번에 */
	$req_ids = array();
	foreach ( $pending as $r ) { $req_ids[] = (int) $r->id; }
	$all_lines = md_sup_lines_for_requests( $req_ids );

	$item_ids = array();
	foreach ( $all_lines as $lines ) {
		foreach ( $lines as $ln ) {
			if ( (int) $ln->item_id > 0 ) { $item_ids[] = (int) $ln->item_id; }
		}
	}
	$stock_map = md_sup_stock_map( $item_ids );
	?>
	<h2 class="mds-h2" style="margin-top:0">처리 대기 신청 <span class="mds-count"><?php echo count( $pending ); ?></span></h2>
	<?php if ( empty( $pending ) ) : ?>
		<p class="mds-empty">대기 중인 신청이 없습니다.</p>
	<?php else : foreach ( $pending as $r ) :
		$lines = isset( $all_lines[ (int) $r->id ] ) ? $all_lines[ (int) $r->id ] : array(); ?>
		<article class="mds-req">
			<header class="mds-req__head">
				<div>
					<b><?php echo esc_html( $r->team_name ); ?></b>
					<?php if ( $r->urgent ) : ?><span class="mds-flag">긴급</span><?php endif; ?>
					<span class="mds-req__when"><?php echo esc_html( mysql2date( 'Y-m-d H:i', $r->created_at ) ); ?></span>
				</div>
				<?php if ( $r->note ) : ?><p class="mds-req__note"><?php echo esc_html( $r->note ); ?></p><?php endif; ?>
			</header>
			<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'manage' ) ) ); ?>">
				<?php wp_nonce_field( 'md_sup_release', 'md_sup_nonce' ); ?>
				<input type="hidden" name="md_sup_action" value="release">
				<input type="hidden" name="req_id" value="<?php echo (int) $r->id; ?>">
				<div class="mds-tablewrap">
					<table class="mds-table">
						<thead><tr><th>품목</th><th class="num">창고 재고</th><th class="num">신청</th><th>초과 사유</th><th class="num">출고</th></tr></thead>
						<tbody>
						<?php foreach ( $lines as $ln ) :
							$is_custom = ( 0 === (int) $ln->item_id );
							$stock     = $is_custom ? 0 : ( isset( $stock_map[ (int) $ln->item_id ] ) ? (int) $stock_map[ (int) $ln->item_id ] : 0 ); ?>
							<tr class="<?php echo $is_custom ? 'is-custom' : ''; ?>">
								<td class="mds-item" data-label="품목">
									<?php if ( $is_custom ) : ?>
										<b><?php echo esc_html( $ln->name ); ?></b>
										<span class="mds-item__meta">
											<span class="mds-flag">직접 적은 품목</span> 등록되지 않은 품목입니다
										</span>
									<?php else : ?>
										<b><a class="mds-plain" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'history', 'item' => (int) $ln->item_id ) ) ); ?>"><?php echo esc_html( $ln->name ); ?></a></b>
										<span class="mds-item__meta"><?php echo esc_html( $ln->code ); ?></span>
									<?php endif; ?>
								</td>
								<td class="num<?php echo ( ! $is_custom && $stock < $ln->qty_req ) ? ' is-low' : ''; ?>" data-label="창고 재고">
									<?php echo $is_custom ? '—' : esc_html( number_format( $stock ) ); ?>
								</td>
								<td class="num" data-label="신청"><?php echo (int) $ln->qty_req; ?></td>
								<td class="mds-memo" data-label="비고"><?php echo esc_html( $ln->over_reason ); ?></td>
								<td class="num" data-label="출고">
									<?php if ( $is_custom ) : ?>
										<a class="mds-mini" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'items' ) ) ); ?>">품목 등록하기</a>
									<?php else : ?>
										<input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
											name="out[<?php echo (int) $ln->id; ?>]" value="<?php echo (int) $ln->qty_req; ?>"
											aria-label="<?php echo esc_attr( $ln->name . ' 출고 수량' ); ?>">
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="mds-submit">
					<button type="submit" class="mds-btn mds-btn--fill">출고 확정</button>
					<span class="mds-hint" style="margin:0">
						창고 재고보다 많이 적어도 막지 않습니다 — 실제로 건넨 수량을 그대로 적으시고,
						모자란 품목은 알려 드리니 「재고」에서 실사로 맞춰 주세요.
					</span>
				</div>
			</form>
			<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'manage' ) ) ); ?>" class="mds-reject">
				<?php wp_nonce_field( 'md_sup_reject', 'md_sup_nonce' ); ?>
				<input type="hidden" name="md_sup_action" value="reject">
				<input type="hidden" name="req_id" value="<?php echo (int) $r->id; ?>">
				<input type="text" name="reject_reason" placeholder="반려 사유 — 신청한 팀에게 그대로 보입니다" maxlength="200" required>
				<button type="submit" class="mds-btn mds-btn--ghost">반려</button>
			</form>
		</article>
	<?php endforeach; endif;
}
