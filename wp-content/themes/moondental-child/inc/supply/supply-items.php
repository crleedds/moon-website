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
		/* 무엇을 지우려던 것인가에 따라 이름·주소·경고문이 달라진다.
		 * v3.65 에 분류·거래처가 더해지면서 네 갈래가 됐다. */
		$is_taxo = in_array( $confirm, array( 'category', 'vendor' ), true );

		if ( $is_taxo ) {
			$m       = md_sup_taxo_meta( $confirm );
			$c_title = $m['label'];
			$c_rows  = md_sup_taxo_list( $confirm );
			$label   = '';
			foreach ( $c_rows as $c_r ) { if ( (int) $c_r->id === $cid ) { $label = $c_r->name; break; } }
			$force = wp_nonce_url(
				md_sup_url( array( 'tab' => 'items', 'deltaxo' => $cid, 'taxokind' => $confirm, 'force' => 1 ) ),
				'md_sup_deltaxo_' . $confirm . '_' . $cid
			);
		} else {
			$is_item = ( 'item' === $confirm );
			$c_title = $is_item ? '품목' : '팀';
			$label   = $is_item ? ( ( $o = md_sup_item( $cid ) ) ? $o->name : '' ) : md_sup_team_name( $cid );
			$force   = wp_nonce_url(
				md_sup_url( array(
					'tab'                             => 'items',
					$is_item ? 'delitem' : 'delteam'  => $cid,
					'force'                           => 1,
				) ),
				'md_sup_' . ( $is_item ? 'delitem_' : 'delteam_' ) . $cid
			);
		}
		?>
		<div class="mds-card mds-confirm">
			<h2><?php echo esc_html( $c_title . ' 삭제 확인' ); ?></h2>
			<p><b><?php echo esc_html( $label ); ?></b> — <?php echo esc_html( $cmsg ); ?></p>
			<p class="mds-hint" style="margin:0 0 16px">
				<?php if ( $is_taxo ) : ?>
					지우면 그 품목들의 <b><?php echo esc_html( $c_title ); ?> 칸이 빈 칸이 됩니다</b> — 품목 자체는 그대로 남습니다.
					이름만 잘못된 것이라면 지우지 마시고 <b>이름을 고쳐 저장</b>하세요. 그 이름을 쓰던 품목이 모두 함께 바뀝니다.
					고르는 칸에서만 치우실 거라면 <b>「사용」</b>을 꺼두시면 됩니다.
				<?php elseif ( 'item' === $confirm ) : ?>
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
		<?php md_sup_app_field(); ?>
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
						<b><a class="mds-plain" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'history', 'item' => (int) $it->id ) ) ); ?>"><?php echo esc_html( $it->name ); ?></a></b>
						<span class="mds-item__meta">
							<?php echo esc_html( $it->code . ( $it->unit ? ' · ' . $it->unit : '' ) . ( $it->category ? ' · ' . $it->category : '' ) ); ?>
							<?php echo $it->active ? '' : ' · 감춤'; ?>
							<?php /* 직원이 신청하다가 등록한 품목 — 단가·적정재고가 비어 있으니 채워 주셔야 합니다 */
							if ( ! empty( $it->created_by ) && ( 0 === (int) $it->price || 0 === (int) $it->min_stock ) ) : ?>
								<span class="mds-flag mds-flag--new">직원 등록 · 확인 필요</span>
							<?php endif; ?>
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

		<p class="mds-hint">
			「순서」는 신청 화면의 팀 고르기 칸이 놓이는 차례입니다 — 작은 수가 앞입니다.
			병원 층 배치를 그대로 옮긴 3행 × 5열이라, 직원분들이 자기 자리를 눈으로 찾습니다.
			비워 두면 지금 순서를 그대로 둡니다.
		</p>

		<div class="mds-teamedit">
			<?php foreach ( md_sup_teams( false ) as $tm ) : ?>
				<div class="mds-teamedit__row<?php echo $tm->active ? '' : ' is-off'; ?>">
					<input type="text" name="team_name[<?php echo (int) $tm->id; ?>]"
					       value="<?php echo esc_attr( $tm->name ); ?>" maxlength="80"
					       aria-label="<?php echo esc_attr( $tm->name . ' 이름' ); ?>">
					<input type="number" class="mds-teamedit__sort" name="team_sort[<?php echo (int) $tm->id; ?>]"
					       value="<?php echo (int) $tm->sort_no; ?>" step="10" min="0"
					       aria-label="<?php echo esc_attr( $tm->name . ' 표시 순서' ); ?>" title="표시 순서">
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
	md_sup_render_taxo_settings();
	md_sup_render_notify_settings();
}

/**
 * 대기 신청 알림 설정 (v3.64).
 *
 * 담당자가 화면에 들어와야만 대기 건을 알 수 있던 문제를 메일로 메운다.
 * 주소를 비워 두면 사이트 관리자 메일로 가고, off 라고 적으면 보내지 않는다.
 */
function md_sup_render_notify_settings() {
	$raw  = (string) get_option( 'md_sup_notify_emails', '' );
	$now  = md_sup_notify_emails();
	?>
	<h2 class="mds-h2">신청 알림</h2>
	<form class="mds-card" method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'items' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_notify', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="notify">

		<label class="mds-formrow">
			<span>알림 받을 메일 주소</span>
			<input type="text" name="emails" maxlength="500" value="<?php echo esc_attr( $raw ); ?>"
			       placeholder="담당자@example.com, 경영지원실@example.com">
		</label>

		<p class="mds-hint" style="margin:12px 0 0">
			새 신청이 들어오면 이 주소로 품목 목록과 함께 메일이 갑니다. 긴급 신청은 제목에 <b>[긴급]</b>이 붙습니다.
			여러 곳이면 쉼표로 나눠 적으세요.
			<br>비워 두면 사이트 관리자 메일<b><?php echo esc_html( $now ? ' (' . implode( ', ', $now ) . ')' : '' ); ?></b>로 갑니다.
			알림을 끄려면 <b>off</b> 라고만 적으세요.
			<br>대기 건수는 메일과 별개로 「반출관리」 탭과 브라우저 제목에도 표시됩니다.
		</p>

		<div class="mds-formbtns" style="margin-top:14px">
			<button type="submit" class="mds-btn mds-btn--fill">알림 설정 저장</button>
		</div>
	</form>
	<?php
}

/**
 * 분류 · 거래처 관리 (v3.65).
 *
 * 예전에는 품목마다 적힌 글자를 DISTINCT 로 긁어 고르는 칸을 만들었다.
 * 그래서 이름을 고치려면 그 값을 쓰는 품목을 하나씩 다 열어야 했고,
 * 품목이 없는 새 거래처를 미리 등록해 둘 수도 없었다.
 * 여기서 이름을 바꾸면 그 값을 쓰던 품목까지 한 번에 따라 바뀐다.
 */
function md_sup_render_taxo_settings() {
	?>
	<h2 class="mds-h2">분류 · 거래처</h2>
	<p class="mds-hint">
		신청·재고·품목 화면의 고르는 칸에 나오는 이름들입니다.
		<b>이름을 바꾸면 그 이름을 쓰던 품목이 모두 함께 바뀝니다</b> — 오타를 한 번에 고칠 수 있습니다.
		「사용」을 끄면 고르는 칸에서만 빠지고 품목에 적힌 값은 그대로 남습니다.
		「순서」는 작은 수가 앞이며, 비워 두면 지금 순서를 유지합니다.
	</p>

	<form class="mds-card" method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'items' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_taxo', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="taxo">

		<div class="mds-taxo">
			<?php foreach ( array( 'category', 'vendor' ) as $kind ) :
				$m    = md_sup_taxo_meta( $kind );
				$rows = md_sup_taxo_list( $kind ); ?>

				<div class="mds-taxo__col">
					<h3 class="mds-h3" style="margin-top:0">
						<?php echo esc_html( $m['label'] ); ?>
						<span class="mds-count"><?php echo count( $rows ); ?></span>
					</h3>

					<?php if ( empty( $rows ) ) : ?>
						<p class="mds-empty" style="padding:14px 4px">아직 등록된 <?php echo esc_html( $m['label'] ); ?>가 없습니다.</p>
					<?php endif; ?>

					<?php foreach ( $rows as $r ) : ?>
						<div class="mds-taxo__row<?php echo $r->active ? '' : ' is-off'; ?>">
							<input type="text" name="<?php echo esc_attr( $kind ); ?>_name[<?php echo (int) $r->id; ?>]"
							       value="<?php echo esc_attr( $r->name ); ?>" maxlength="<?php echo (int) $m['max']; ?>"
							       aria-label="<?php echo esc_attr( $r->name . ' 이름' ); ?>">
							<input type="number" class="mds-taxo__sort" name="<?php echo esc_attr( $kind ); ?>_sort[<?php echo (int) $r->id; ?>]"
							       value="<?php echo (int) $r->sort_no; ?>" step="10" min="0"
							       aria-label="<?php echo esc_attr( $r->name . ' 표시 순서' ); ?>" title="표시 순서">
							<label class="mds-check">
								<input type="checkbox" name="<?php echo esc_attr( $kind ); ?>_on[<?php echo (int) $r->id; ?>]" value="1" <?php checked( (int) $r->active, 1 ); ?>> 사용
							</label>
							<span class="mds-taxo__uses" title="이 이름을 쓰는 품목 수"><?php echo (int) $r->uses; ?>건</span>
							<a class="mds-mini mds-mini--warn"
							   href="<?php echo esc_url( wp_nonce_url(
								   md_sup_url( array( 'tab' => 'items', 'deltaxo' => (int) $r->id, 'taxokind' => $kind ) ),
								   'md_sup_deltaxo_' . $kind . '_' . (int) $r->id
							   ) ); ?>"
							   onclick="return confirm('<?php echo esc_js( $r->name ); ?> — 지울까요?');">삭제</a>
						</div>
					<?php endforeach; ?>

					<div class="mds-taxo__row">
						<input type="text" name="<?php echo esc_attr( $kind ); ?>_new" value="" maxlength="<?php echo (int) $m['max']; ?>"
						       placeholder="새 <?php echo esc_attr( $m['label'] ); ?> 이름을 적으면 추가됩니다"
						       aria-label="<?php echo esc_attr( '새 ' . $m['label'] . ' 이름' ); ?>">
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="mds-formbtns" style="margin-top:16px">
			<button type="submit" class="mds-btn mds-btn--fill">분류 · 거래처 저장</button>
		</div>
	</form>
	<?php
}
