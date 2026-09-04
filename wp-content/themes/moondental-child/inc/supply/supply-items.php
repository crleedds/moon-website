<?php
/**
 * 재료실 — 품목 · 팀 관리 (재고 담당자 전용)
 *
 * 담당자가 품목을 직접 넣고 고치고 감출 수 있게 한다.
 *
 * 지우지 않고 감추는 이유
 *   입출고 기록이 품목을 가리키고 있어서, 진짜로 지우면 지난 사용량 통계가
 *   "이름 없는 숫자" 가 되어 버린다. active 를 내리면 목록에서만 사라지고
 *   기록은 그대로 남는다. 기록이 하나도 없는 품목만 완전히 지울 수 있다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_sup_render_items() {
	$edit   = isset( $_GET['item'] ) ? (int) $_GET['item'] : 0;
	$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$hidden = isset( $_GET['hidden'] ) && '1' === $_GET['hidden'];
	$row    = $edit ? md_sup_item( $edit ) : null;
	$items  = md_sup_items_all( $search, $hidden );
	$units  = array( 'ea', 'box', '갑', '팩', '봉', '병', '통', '롤', '각' );

	/* 삭제를 한 번 막았을 때 — 무엇이 걸렸는지 보여주고 다시 물어본다.
	 * 자바스크립트가 없어도 확인 절차가 그대로 작동한다. */
	$confirm = isset( $_GET['confirm'] ) ? sanitize_key( wp_unslash( $_GET['confirm'] ) ) : '';
	$cid     = isset( $_GET['cid'] ) ? (int) $_GET['cid'] : 0;
	$cmsg    = isset( $_GET['cmsg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['cmsg'] ) ) ) : '';

	if ( $confirm && $cid ) :
		$is_item = ( 'item' === $confirm );
		$label   = $is_item ? ( ( $o = md_sup_item( $cid ) ) ? $o->name : '' ) : md_sup_team_name( $cid );
		$force   = wp_nonce_url(
			md_sup_url( array(
				'tab'                             => 'items',
				$is_item ? 'delitem' : 'delteam'  => $cid,
				'force'                           => 1,
			) ),
			'md_sup_' . ( $is_item ? 'delitem_' : 'delteam_' ) . $cid
		);
		?>
		<div class="mds-card mds-confirm">
			<h2><?php echo esc_html( ( $is_item ? '품목' : '팀' ) . ' 삭제 확인' ); ?></h2>
			<p><b><?php echo esc_html( $label ); ?></b> — <?php echo esc_html( $cmsg ); ?></p>
			<p class="mds-hint" style="margin:0 0 16px">
				<?php if ( $is_item ) : ?>
					지우면 그 기록들이 <b>이름 없는 품목</b>으로 남아 과거 사용량 통계를 읽기 어려워집니다.
					목록에서만 치우실 거라면 <b>「감추기」</b>를 쓰세요 — 기록은 그대로 두고 신청 목록에서만 사라집니다.
				<?php else : ?>
					지워도 입출고 기록 자체는 남습니다. 다만 통계에서 <b>「(팀 없음)」</b>으로 표시됩니다.
					목록에서만 치우실 거라면 팀 편집에서 <b>「사용」</b>을 꺼두시면 됩니다.
				<?php endif; ?>
			</p>
			<div class="mds-formbtns">
				<a class="mds-btn mds-btn--danger" href="<?php echo esc_url( $force ); ?>">그래도 삭제합니다</a>
				<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'items' ) ) ); ?>">취소</a>
			</div>
		</div>
	<?php endif; ?>

	<h2 class="mds-h2" style="margin-top:0"><?php echo $edit ? '품목 수정' : '새 품목 등록'; ?></h2>

	<form class="mds-card" method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'items' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_item', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="item">
		<input type="hidden" name="item_id" value="<?php echo (int) $edit; ?>">

		<div class="mds-grid2">
			<label class="mds-formrow">
				<span>품목명 (필수)</span>
				<input type="text" name="name" required maxlength="200"
				       value="<?php echo $row ? esc_attr( $row->name ) : ''; ?>"
				       placeholder="예) 봉합사 4-0 블랙실크">
			</label>
			<label class="mds-formrow">
				<span>거래처</span>
				<input type="text" name="vendor" list="mds-vendors" maxlength="80"
				       value="<?php echo $row ? esc_attr( $row->vendor ) : ''; ?>" placeholder="예) 새한치재">
			</label>
			<label class="mds-formrow">
				<span>단위</span>
				<input type="text" name="unit" list="mds-units" maxlength="20"
				       value="<?php echo $row ? esc_attr( $row->unit ) : ''; ?>" placeholder="ea · box · 갑">
			</label>
			<label class="mds-formrow">
				<span>분류</span>
				<input type="text" name="category" list="mds-cats" maxlength="50"
				       value="<?php echo $row ? esc_attr( $row->category ) : ''; ?>" placeholder="치과재료">
			</label>
			<label class="mds-formrow">
				<span>단가 (원)</span>
				<input type="number" name="price" min="0" step="10" inputmode="numeric"
				       value="<?php echo $row ? (int) $row->price : ''; ?>" placeholder="0">
			</label>
			<label class="mds-formrow">
				<span>적정재고 — 이 아래로 떨어지면 부족 표시</span>
				<input type="number" name="min_stock" min="0" step="1" inputmode="numeric"
				       value="<?php echo $row ? (int) $row->min_stock : ''; ?>" placeholder="0">
			</label>
		</div>

		<datalist id="mds-vendors"><?php foreach ( md_sup_vendors() as $v ) : ?><option value="<?php echo esc_attr( $v ); ?>"></option><?php endforeach; ?></datalist>
		<datalist id="mds-cats"><?php foreach ( md_sup_categories() as $c ) : ?><option value="<?php echo esc_attr( $c ); ?>"></option><?php endforeach; ?></datalist>
		<datalist id="mds-units"><?php foreach ( $units as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>"></option><?php endforeach; ?></datalist>

		<div class="mds-formbtns">
			<button type="submit" class="mds-btn mds-btn--fill"><?php echo $edit ? '수정 저장' : '품목 추가'; ?></button>
			<?php if ( $edit && $row ) : ?>
				<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'items' ) ) ); ?>">취소</a>
				<span class="mds-hint" style="margin:0">
					코드 <b><?php echo esc_html( $row->code ); ?></b> · 현재고 <b><?php echo esc_html( number_format( (int) $row->stock ) ); ?></b>
				</span>
			<?php else : ?>
				<span class="mds-hint" style="margin:0">품목코드는 자동으로 매겨집니다 — 다음 번호 <b><?php echo esc_html( md_sup_next_code() ); ?></b></span>
			<?php endif; ?>
		</div>
	</form>

	<h2 class="mds-h2">등록된 품목 <span class="mds-count"><?php echo count( $items ); ?></span></h2>

	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="items">
		<label class="mds-field mds-field--grow">
			<span>품목 검색</span>
			<input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="품목명 · 코드 · 거래처">
		</label>
		<label class="mds-check"><input type="checkbox" name="hidden" value="1" <?php checked( $hidden ); ?>> 감춘 품목도 보기</label>
		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
	</form>

	<div class="mds-tablewrap">
		<table class="mds-table">
			<thead>
				<tr><th>품목</th><th>거래처</th><th class="num">단가</th><th class="num">현재고</th><th class="num">적정</th><th>관리</th></tr>
			</thead>
			<tbody>
			<?php if ( empty( $items ) ) : ?>
				<tr><td colspan="6" class="mds-empty">해당하는 품목이 없습니다.</td></tr>
			<?php else : foreach ( $items as $it ) : ?>
				<tr class="<?php echo $it->active ? '' : 'is-off'; ?>">
					<td class="mds-item" data-label="품목">
						<b><?php echo esc_html( $it->name ); ?></b>
						<span class="mds-item__meta">
							<?php echo esc_html( $it->code . ( $it->unit ? ' · ' . $it->unit : '' ) . ( $it->category ? ' · ' . $it->category : '' ) ); ?>
							<?php echo $it->active ? '' : ' · 감춤'; ?>
						</span>
					</td>
					<td data-label="거래처"><?php echo esc_html( $it->vendor ); ?></td>
					<td class="num" data-label="단가"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
					<td class="num" data-label="현재고"><?php echo esc_html( number_format( (int) $it->stock ) ); ?></td>
					<td class="num" data-label="적정"><?php echo (int) $it->min_stock; ?></td>
					<td data-label="관리">
						<span class="mds-rowbtns">
							<a class="mds-mini" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'items', 'item' => (int) $it->id ) ) ); ?>">수정</a>
							<a class="mds-mini"
							   href="<?php echo esc_url( wp_nonce_url( md_sup_url( array( 'tab' => 'items', 'toggle' => (int) $it->id, 'hidden' => $hidden ? 1 : 0, 'q' => $search ) ), 'md_sup_toggle_' . (int) $it->id ) ); ?>">
								<?php echo $it->active ? '감추기' : '되살리기'; ?>
							</a>
							<a class="mds-mini mds-mini--warn"
							   href="<?php echo esc_url( wp_nonce_url( md_sup_url( array( 'tab' => 'items', 'delitem' => (int) $it->id, 'hidden' => $hidden ? 1 : 0, 'q' => $search ) ), 'md_sup_delitem_' . (int) $it->id ) ); ?>"
							   onclick="return confirm('<?php echo esc_js( $it->name ); ?> 품목을 지울까요?');">삭제</a>
						</span>
					</td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>

	<p class="mds-hint">
		품목을 <b>감추면</b> 신청·재고 목록에서 사라지고 지난 기록은 그대로 남습니다.
		입출고 기록이 하나도 없는 품목만 완전히 지울 수 있습니다 —
		기록이 있는 품목을 지우면 과거 사용량 통계가 이름 없는 숫자가 되어 버리기 때문입니다.
	</p>

	<h2 class="mds-h2">팀</h2>
	<form class="mds-card" method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'items' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_team', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="team">

		<div class="mds-teamedit">
			<?php foreach ( md_sup_teams( false ) as $tm ) : ?>
				<div class="mds-teamedit__row<?php echo $tm->active ? '' : ' is-off'; ?>">
					<input type="text" name="team_name[<?php echo (int) $tm->id; ?>]"
					       value="<?php echo esc_attr( $tm->name ); ?>" maxlength="80"
					       aria-label="<?php echo esc_attr( $tm->name . ' 이름' ); ?>">
					<label class="mds-check">
						<input type="checkbox" name="team_on[<?php echo (int) $tm->id; ?>]" value="1" <?php checked( (int) $tm->active, 1 ); ?>> 사용
					</label>
					<a class="mds-mini mds-mini--warn"
					   href="<?php echo esc_url( wp_nonce_url( md_sup_url( array( 'tab' => 'items', 'delteam' => (int) $tm->id ) ), 'md_sup_delteam_' . (int) $tm->id ) ); ?>"
					   onclick="return confirm('<?php echo esc_js( $tm->name ); ?> 팀을 지울까요?');">삭제</a>
				</div>
			<?php endforeach; ?>
			<div class="mds-teamedit__row">
				<input type="text" name="team_new" value="" maxlength="80"
				       placeholder="새 팀 이름을 적으면 추가됩니다" aria-label="새 팀 이름">
			</div>
		</div>

		<div class="mds-formbtns" style="margin-top:14px">
			<button type="submit" class="mds-btn mds-btn--fill">팀 저장</button>
		</div>
	</form>
	<p class="mds-hint">「사용」을 끄면 신청 화면의 팀 선택에서 빠집니다. 지난 사용량 기록은 그대로 남습니다.</p>
	<?php
}
