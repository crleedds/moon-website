<?php
/**
 * 재고관리 v5 — 설정 (관리자)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_inv_settings_tabs() {
	$t = array(
		'ops'      => '운영 설정',
		'teams'    => '팀',
		'vendors'  => '업체',
		'cats'     => '분류',
		'accounts' => '계정',
		'mail'     => '보고서 메일',
		'backup'   => '백업',
		'export'   => '내보내기',
		'import'   => '가져오기',
		'log'      => '작업 기록',
	);
	if ( ! md_inv_can_accounts() ) { unset( $t['accounts'] ); }
	if ( ! md_inv_is_owner() ) { unset( $t['import'] ); } /* 전부 지우고 다시 — 원장만 */
	return $t;
}

function md_inv_view_settings() {
	$tabs = md_inv_settings_tabs();
	$cur  = md_inv_get( 'is', 'ops' );
	if ( ! isset( $tabs[ $cur ] ) ) { $cur = 'ops'; }
	echo '<nav class="iv-subnav" aria-label="설정 메뉴">';
	foreach ( $tabs as $k => $lb ) {
		echo '<a class="iv-subnav__a' . ( $cur === $k ? ' is-on' : '' ) . '" href="' . esc_url( md_inv_url( array( 'iv' => 'settings', 'is' => $k ) ) ) . '">' . esc_html( $lb ) . '</a>';
	}
	echo '</nav>';
	call_user_func( 'md_inv_settings_' . $cur );
}

/** 체크 칸 · 숫자 칸 · 글자 칸 */
function md_inv_s_check( $k, $label, $help = '' ) {
	echo '<label class="iv-switch"><input type="checkbox" name="s_' . esc_attr( $k ) . '" value="1"' . checked( (int) md_inv_set( $k ), 1, false ) . '><input type="hidden" name="checkboxes[]" value="' . esc_attr( $k ) . '"><span class="iv-switch__ui" aria-hidden="true"></span><span class="iv-switch__t"><b>' . esc_html( $label ) . '</b>' . ( $help ? '<small>' . esc_html( $help ) . '</small>' : '' ) . '</span></label>';
}

function md_inv_s_num( $k, $label, $suffix = '', $min = 0, $max = 99999 ) {
	echo '<label class="iv-f iv-f--num"><span>' . esc_html( $label ) . '</span><span class="iv-inline"><input class="iv-input iv-input--num" type="number" inputmode="numeric" name="s_' . esc_attr( $k ) . '" min="' . (int) $min . '" max="' . (int) $max . '" value="' . (int) md_inv_set( $k ) . '">' . ( $suffix ? '<em>' . esc_html( $suffix ) . '</em>' : '' ) . '</span></label>';
}

function md_inv_s_text( $k, $label, $ph = '' ) {
	echo '<label class="iv-f"><span>' . esc_html( $label ) . '</span><input class="iv-input" name="s_' . esc_attr( $k ) . '" value="' . esc_attr( md_inv_set( $k ) ) . '" placeholder="' . esc_attr( $ph ) . '"></label>';
}

/* ---- 운영 설정 --------------------------------------------- */

function md_inv_settings_ops() {
	?>
	<form method="post" class="iv-settings" data-dirtywarn>
		<?php md_inv_hidden( 'settings' ); ?>
		<section class="iv-panel">
			<h3 class="iv-h3">요청 (직원 화면)</h3>
			<?php
			md_inv_s_check( 'req_need_name', '요청자 이름 필수', '공용 계정이라 누가 요청했는지 이름으로 남깁니다.' );
			md_inv_s_check( 'req_remember', '이 기기에서 마지막 팀 · 이름 기억', '끄면 매번 새로 고릅니다. 공용 태블릿에서 다른 팀으로 잘못 잡히는 일이 잦으면 끄세요.' );
			md_inv_s_check( 'req_allow_custom', '「목록에 없는 품목」 요청 받기' );
			md_inv_s_check( 'req_allow_over', '재고보다 많이 요청해도 받기', '켜 두면 경고만 보여 주고 받습니다. 관리자가 있는 만큼 출고하거나 주문합니다.' );
			md_inv_s_check( 'staff_see_stock', '직원에게 재고 수량 보이기' );
			md_inv_s_check( 'staff_see_price', '직원에게 단가 보이기' );
			md_inv_s_check( 'staff_see_all_teams', '직원이 다른 팀 요청 내역도 보기' );
			md_inv_s_check( 'staff_cancel', '직원이 대기 중인 요청 취소' );
			md_inv_s_check( 'staff_stats', '직원에게 통계 탭 보이기' );
			md_inv_s_check( 'staff_stock_tab', '직원에게 재고 조회 탭 보이기', '읽기 전용입니다. 입고·출고·수정은 관리자만 합니다.' );
			echo '<div class="iv-grid3">';
			md_inv_s_num( 'req_max_qty', '한 품목 최대 요청 수량', '개', 1, 99999 );
			md_inv_s_num( 'recent_days', '「우리 팀이 최근 요청한 품목」 기준', '일', 1, 365 );
			md_inv_s_num( 'history_days', '내역 기본 기간', '일', 7, 3650 );
			echo '</div>';
			?>
		</section>
		<section class="iv-panel">
			<h3 class="iv-h3">재고 · 주문</h3>
			<?php
			md_inv_s_check( 'out_allow_negative', '재고보다 많이 출고 허용', '꺼 두기를 권합니다. 켜면 재고가 음수가 될 수 있어 실사로 맞춰야 합니다.' );
			md_inv_s_check( 'adjust_need_note', '실사 조정에 사유 필수' );
			md_inv_s_check( 'out_need_receiver', '출고할 때 받은 사람 확인', '출고 버튼을 누르면 받은 사람 칸이 뜹니다(신청자 이름이 미리 채워져 있어 맞으면 그대로 출고). 끄면 버튼 한 번에 바로 출고합니다.' );
			md_inv_s_check( 'price_follow_in', '입고 단가가 다르면 품목 단가도 바꾸기', '켜 두면 새 단가가 다음 주문 · 통계에 쓰입니다. 어느 쪽이든 단가 변동은 기록됩니다.' );
			echo '<div class="iv-grid3">';
			md_inv_s_num( 'order_need_recent', '「주문 필요」는 최근 몇 주 안에 출고된 품목만', '주 (0 = 전체)', 0, 104 );
			md_inv_s_num( 'min_cover_weeks', '안전재고 제안: 몇 주 쓸 만큼', '주', 1, 26 );
			echo '</div>';
			?>
		</section>
		<section class="iv-panel">
			<h3 class="iv-h3">통계</h3>
			<?php
			md_inv_s_check( 'stats_include_prepaid', '사용금액에 선납 업체 품목 포함', '임플란트처럼 선납으로 사는 품목을 팀별 사용금액에 넣을지 정합니다.' );
			echo '<div class="iv-grid3">';
			md_inv_s_num( 'stats_months', '월별 그래프', '개월', 3, 36 );
			md_inv_s_num( 'stats_weeks', '주별 그래프', '주', 4, 52 );
			echo '</div>';
			?>
		</section>
		<section class="iv-panel">
			<h3 class="iv-h3">이름 · 표시</h3>
			<div class="iv-grid3">
				<?php md_inv_s_text( 'label_cat1', '분류 1단계 이름', '결제 방식' ); md_inv_s_text( 'label_cat2', '분류 2단계 이름', '품목군' ); md_inv_s_text( 'label_cat3', '분류 3단계 이름', '세부 분류' ); ?>
			</div>
			<?php md_inv_s_text( 'me_default', '관리자 기본 이름 (기기마다 이름을 안 정했을 때 기록에 남는 이름)', '관리자' ); ?>
		</section>
		<section class="iv-panel">
			<h3 class="iv-h3">새 요청 알림 메일</h3>
			<?php md_inv_s_check( 'notify_new', '새 요청이 오면 메일 보내기', '기본은 꺼짐 — 할 일 탭의 숫자로 확인합니다.' ); md_inv_s_text( 'notify_to', '받는 주소 (여럿이면 쉼표)', 'moondentaldigital@gmail.com' ); ?>
		</section>
		<div class="iv-stickyfoot"><button class="iv-btn iv-btn--primary iv-btn--lg">설정 저장</button></div>
	</form>
	<?php
}

/* ---- 팀 ---------------------------------------------------- */

function md_inv_settings_teams() {
	global $wpdb;
	$cnt = array();
	foreach ( $wpdb->get_results( 'SELECT team_id, COUNT(*) AS n FROM ' . md_inv_t( 'req' ) . ' GROUP BY team_id' ) as $r ) { $cnt[ (int) $r->team_id ] = (int) $r->n; }
	?>
	<div class="iv-toolbar iv-toolbar--actions">
		<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-team" data-set='{"id":"","name":"","in_stats":1,"active":1,"title":"팀 추가"}'><?php echo md_inv_icon( 'plus', 18 ); // phpcs:ignore ?>팀 추가</button>
		<?php md_inv_dl_buttons( 'teams' ); ?>
	</div>
	<p class="iv-help">요청할 때 고르는 팀입니다. 순서는 화살표로 바꿉니다. 기록이 있는 팀은 지우면 「사용 안 함」이 됩니다(지난 통계 보존).</p>
	<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>순서</th><th>팀</th><th>통계</th><th>사용</th><th class="r">요청</th><th></th></tr></thead><tbody>
	<?php foreach ( md_inv_teams( false ) as $i => $tm ) : ?>
		<tr class="<?php echo $tm->active ? '' : 'is-off'; ?>">
			<td data-l="순서" class="iv-td-move"><?php md_inv_move_buttons( 'team_move', $tm->id ); ?></td>
			<td data-l="팀"><b><?php echo esc_html( $tm->name ); ?></b></td>
			<td data-l="통계"><?php echo $tm->in_stats ? '표시' : '<span class="iv-muted">숨김</span>'; ?></td>
			<td data-l="사용"><?php echo $tm->active ? '사용' : '<span class="iv-muted">사용 안 함</span>'; ?></td>
			<td data-l="요청" class="r"><?php echo isset( $cnt[ (int) $tm->id ] ) ? (int) $cnt[ (int) $tm->id ] : 0; ?></td>
			<td class="iv-td-act">
				<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-team" data-set="<?php echo esc_attr( wp_json_encode( array( 'id' => (int) $tm->id, 'name' => $tm->name, 'in_stats' => (int) $tm->in_stats, 'active' => (int) $tm->active, 'title' => '팀 고치기' ) ) ); ?>">고치기</button>
				<form method="post" class="iv-inline-form" data-confirm="<?php echo esc_attr( '「' . $tm->name . '」 팀을 지울까요? 기록이 있으면 「사용 안 함」으로 바뀝니다.' ); ?>"><?php md_inv_hidden( 'team_delete' ); ?><input type="hidden" name="id" value="<?php echo (int) $tm->id; ?>"><button class="iv-btn iv-btn--ghost iv-btn--xs">삭제</button></form>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody></table></div>
	<dialog class="iv-dlg" id="dlg-team"><form method="post">
		<?php md_inv_hidden( 'team_save' ); ?><input type="hidden" name="id" value="">
		<div class="iv-dlg__head"><b data-t="title">팀</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<label class="iv-f"><span>팀 이름 <em>*</em></span><input class="iv-input" name="name" maxlength="60" required></label>
		<label class="iv-check"><input type="checkbox" name="in_stats" value="1"> 통계에 표시</label>
		<input type="hidden" name="active" value="0" data-unchecked-for="active">
		<label class="iv-check"><input type="checkbox" name="active" value="1"> 사용 (요청 화면에 보임)</label>
		<?php md_inv_dlg_close(); ?>
	<?php
}

function md_inv_move_buttons( $action, $id ) {
	foreach ( array( 'up' => '위로', 'down' => '아래로' ) as $dir => $lb ) {
		echo '<form method="post" class="iv-inline-form">';
		md_inv_hidden( $action );
		echo '<input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="dir" value="' . esc_attr( $dir ) . '">';
		echo '<button class="iv-btn iv-btn--icon iv-btn--xs" aria-label="' . esc_attr( $lb ) . '" title="' . esc_attr( $lb ) . '">' . md_inv_icon( 'up' === $dir ? 'up' : 'dn', 16 ) . '</button></form>';
	}
}

/* ---- 업체 -------------------------------------------------- */

function md_inv_settings_vendors() {
	global $wpdb;
	$cnt = array();
	foreach ( $wpdb->get_results( 'SELECT vendor_id, COUNT(*) AS n FROM ' . md_inv_t( 'item' ) . ' WHERE active = 1 GROUP BY vendor_id' ) as $r ) { $cnt[ (int) $r->vendor_id ] = (int) $r->n; }
	$q = md_inv_get( 'iq' );
	?>
	<div class="iv-toolbar iv-toolbar--actions">
		<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-vendor" data-set='{"id":"","title":"업체 추가","active":1}'><?php echo md_inv_icon( 'plus', 18 ); // phpcs:ignore ?>업체 추가</button>
		<?php md_inv_dl_buttons( 'vendors' ); ?>
		<form method="get" class="iv-inline-form iv-grow"><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="settings"><input type="hidden" name="is" value="vendors"><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>" placeholder="업체 찾기"></form>
	</div>
	<div class="iv-cards iv-cards--grid">
	<?php foreach ( md_inv_vendors( false ) as $v ) :
		if ( '' !== $q && false === mb_stripos( $v->name . ' ' . $v->goods . ' ' . $v->contact, $q ) ) { continue; }
		$set = array( 'id' => (int) $v->id, 'title' => '업체 고치기', 'name' => $v->name, 'contact' => $v->contact, 'phone' => $v->phone, 'email' => $v->email, 'shop_info' => (string) $v->shop_info, 'goods' => (string) $v->goods, 'note' => (string) $v->note, 'prepaid' => (int) $v->prepaid, 'active' => (int) $v->active, 'pp_bonus' => (float) $v->pp_bonus ? rtrim( rtrim( number_format( (float) $v->pp_bonus, 2, '.', '' ), '0' ), '.' ) : '', 'pp_alert' => (int) $v->pp_alert ? (int) $v->pp_alert : '' );
		?>
		<article class="iv-card iv-card--vendor<?php echo $v->active ? '' : ' is-off'; ?>">
			<div class="iv-card__main">
				<div class="iv-card__title"><?php echo esc_html( $v->name ); ?><?php if ( $v->prepaid ) : ?> <span class="iv-tag iv-tag--pp">선납</span><?php endif; ?><?php if ( ! $v->active ) : ?> <span class="iv-tag">사용 안 함</span><?php endif; ?></div>
				<div class="iv-card__sub"><?php echo esc_html( trim( $v->contact . ' ' . $v->phone ) ); ?><?php echo $v->email ? ' · ' . esc_html( $v->email ) : ''; ?></div>
				<?php if ( $v->goods ) : ?><div class="iv-card__sub">취급: <?php echo esc_html( $v->goods ); ?></div><?php endif; ?>
				<?php if ( $v->shop_info ) : ?><div class="iv-card__sub"><?php echo esc_html( $v->shop_info ); ?></div><?php endif; ?>
				<?php if ( $v->note ) : ?><details class="iv-card__more"><summary>비고</summary><p><?php echo nl2br( esc_html( $v->note ) ); ?></p></details><?php endif; ?>
				<div class="iv-card__sub iv-muted">품목 <?php echo isset( $cnt[ (int) $v->id ] ) ? (int) $cnt[ (int) $v->id ] : 0; ?>개 · <a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock', 'ivd' => $v->id ) ) ); ?>">품목 보기</a></div>
			</div>
			<div class="iv-actions">
				<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-vendor" data-set="<?php echo esc_attr( wp_json_encode( $set ) ); ?>">고치기</button>
				<form method="post" class="iv-inline-form" data-confirm="<?php echo esc_attr( '「' . $v->name . '」을(를) 지울까요? 품목이나 기록이 있으면 「사용 안 함」으로 바뀝니다.' ); ?>"><?php md_inv_hidden( 'vendor_delete' ); ?><input type="hidden" name="id" value="<?php echo (int) $v->id; ?>"><button class="iv-btn iv-btn--ghost iv-btn--xs">삭제</button></form>
			</div>
		</article>
	<?php endforeach; ?>
	</div>
	<dialog class="iv-dlg iv-dlg--wide" id="dlg-vendor"><form method="post">
		<?php md_inv_hidden( 'vendor_save' ); ?><input type="hidden" name="id" value="">
		<div class="iv-dlg__head"><b data-t="title">업체</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<label class="iv-f"><span>업체 이름 <em>*</em></span><input class="iv-input" name="name" maxlength="100" required></label>
		<div class="iv-grid2">
			<label class="iv-f"><span>담당자</span><textarea class="iv-input" name="contact" rows="1"></textarea></label>
			<label class="iv-f"><span>연락처</span><textarea class="iv-input" name="phone" rows="1"></textarea></label>
			<label class="iv-f"><span>이메일</span><textarea class="iv-input" name="email" rows="1"></textarea></label>
			<label class="iv-f"><span>온라인몰 · 주문 방법</span><textarea class="iv-input" name="shop_info" rows="1"></textarea></label>
		</div>
		<label class="iv-f"><span>취급 품목</span><textarea class="iv-input" name="goods" rows="2"></textarea></label>
		<label class="iv-f"><span>비고 (발주 · 정산 · 결제 방법 등)</span><textarea class="iv-input" name="note" rows="4"></textarea></label>
		<label class="iv-check"><input type="checkbox" name="prepaid" value="1"> 선납 업체 (미리 낸 돈에서 입고 금액을 차감)</label>
		<div class="iv-grid2">
			<label class="iv-f"><span>선납 적립률 (%) <small>예: 1,000만 원에 1,100만 원어치면 10</small></span><input class="iv-input" name="pp_bonus" inputmode="decimal" placeholder="없으면 비움"></label>
			<label class="iv-f"><span>잔액 알림 기준 (원) <small>쓸 수 있는 잔액이 이보다 적으면 알림</small></span><input class="iv-input" name="pp_alert" inputmode="numeric" placeholder="예: 3000000"></label>
		</div>
		<input type="hidden" name="active" value="0" data-unchecked-for="active">
		<label class="iv-check"><input type="checkbox" name="active" value="1"> 사용</label>
		<?php md_inv_dlg_close(); ?>
	<?php
}

/* ---- 분류 -------------------------------------------------- */

function md_inv_settings_cats() {
	$L   = md_inv_settings();
	$use = md_inv_cat_usage();
	?>
	<div class="iv-toolbar iv-toolbar--actions">
		<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-cat" data-set="<?php echo esc_attr( wp_json_encode( array( 'id' => '', 'level' => 1, 'parent_id' => 0, 'title' => $L['label_cat1'] . ' 추가', 'active' => 1 ) ) ); ?>"><?php echo md_inv_icon( 'plus', 18 ); // phpcs:ignore ?><?php echo esc_html( $L['label_cat1'] ); ?> 추가</button>
		<?php md_inv_dl_buttons( 'cats' ); ?>
	</div>
	<p class="iv-help"><?php echo esc_html( $L['label_cat1'] . ' › ' . $L['label_cat2'] . ' › ' . $L['label_cat3'] ); ?> 세 단계입니다. 각 단계 이름은 운영 설정에서 바꿀 수 있습니다. 분류를 지우면 그 아래 분류도 함께 지워지고, 그 분류를 쓰던 품목은 분류 칸이 비워집니다.</p>
	<div class="iv-tree">
	<?php foreach ( md_inv_cats_of( 1, null, false ) as $c1 ) : ?>
		<div class="iv-tree__n iv-tree__n--1<?php echo $c1->active ? '' : ' is-off'; ?>">
			<?php md_inv_cat_row( $c1, $use, 2 ); ?>
			<?php foreach ( md_inv_cats_of( 2, $c1->id, false ) as $c2 ) : ?>
				<div class="iv-tree__n iv-tree__n--2<?php echo $c2->active ? '' : ' is-off'; ?>">
					<?php md_inv_cat_row( $c2, $use, 3 ); ?>
					<?php foreach ( md_inv_cats_of( 3, $c2->id, false ) as $c3 ) : ?>
						<div class="iv-tree__n iv-tree__n--3<?php echo $c3->active ? '' : ' is-off'; ?>"><?php md_inv_cat_row( $c3, $use, 0 ); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
	</div>
	<dialog class="iv-dlg" id="dlg-cat"><form method="post">
		<?php md_inv_hidden( 'cat_save' ); ?><input type="hidden" name="id" value=""><input type="hidden" name="level" value=""><input type="hidden" name="parent_id" value="">
		<div class="iv-dlg__head"><b data-t="title">분류</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<p class="iv-dlg__what" data-t="what"></p>
		<label class="iv-f"><span>이름 <em>*</em></span><input class="iv-input" name="name" maxlength="60" required></label>
		<label class="iv-f"><span>비고</span><input class="iv-input" name="note" maxlength="200"></label>
		<input type="hidden" name="active" value="0" data-unchecked-for="active">
		<label class="iv-check"><input type="checkbox" name="active" value="1"> 사용 (고르는 칸에 보임)</label>
		<?php md_inv_dlg_close(); ?>
	<?php
}

function md_inv_cat_row( $c, $use, $child_level ) {
	$L  = md_inv_settings();
	$n  = isset( $use[ (int) $c->id ] ) ? (int) $use[ (int) $c->id ] : 0;
	echo '<div class="iv-tree__row">';
	md_inv_move_buttons( 'cat_move', $c->id );
	echo '<b>' . esc_html( $c->name ) . '</b>' . ( $c->active ? '' : ' <span class="iv-tag">사용 안 함</span>' ) . ( $c->note ? ' <small class="iv-muted">' . esc_html( $c->note ) . '</small>' : '' );
	echo '<span class="iv-muted iv-tree__cnt">품목 ' . $n . '</span>';
	echo '<span class="iv-tree__act">';
	if ( $child_level ) {
		echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-cat" data-set="' . esc_attr( wp_json_encode( array( 'id' => '', 'level' => $child_level, 'parent_id' => (int) $c->id, 'title' => $L[ 'label_cat' . $child_level ] . ' 추가', 'what' => $c->name . ' 아래', 'name' => '', 'note' => '', 'active' => 1 ) ) ) . '">+ ' . esc_html( $L[ 'label_cat' . $child_level ] ) . '</button>';
	}
	echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-cat" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $c->id, 'level' => (int) $c->level, 'parent_id' => (int) $c->parent_id, 'title' => '고치기', 'what' => '', 'name' => $c->name, 'note' => $c->note, 'active' => (int) $c->active ) ) ) . '">고치기</button>';
	echo '<form method="post" class="iv-inline-form" data-confirm="' . esc_attr( '「' . $c->name . '」을(를) 지울까요? 아래 분류도 함께 지워지고, 품목 ' . $n . '개의 분류 칸이 비워집니다.' ) . '">';
	md_inv_hidden( 'cat_delete' );
	echo '<input type="hidden" name="id" value="' . (int) $c->id . '"><button class="iv-btn iv-btn--ghost iv-btn--xs">삭제</button></form>';
	echo '</span></div>';
}

/* ---- 계정 -------------------------------------------------- */

function md_inv_settings_accounts() {
	/* v5.8 · 개인 계정 만들기 · 권한은 라운지 「직원 정보」로 옮겼다 (회원가입 신청 → 승인) */
	$staff_url = function_exists( 'md_sup_url' ) ? md_sup_url( array( 'app' => 'staff' ) ) : home_url( '/직원/?app=staff' );
	$shared    = array();
	foreach ( md_inv_accounts() as $a ) {
		if ( 'administrator' !== $a->role && in_array( $a->login, array( defined( 'MD_SUP_STAFF_LOGIN' ) ? MD_SUP_STAFF_LOGIN : 'moondentalhospital' ), true ) ) { $shared[] = $a; }
	}
	?>
	<div class="iv-sent">👥 계정은 이제 <b>직원 라운지 › 직원 정보</b>에서 관리합니다. 직원이 로그인 화면의 「회원가입 신청」으로 신청하면, 직원 정보 맨 위에서 명단과 연결하고 권한(직원 · <b>재료실 관리</b> · 라운지 관리자)을 골라 승인합니다. 개인 계정으로 신청하면 신청자 이름이 자동으로 들어갑니다.</div>
	<p><a class="iv-btn iv-btn--primary" href="<?php echo esc_url( $staff_url ); ?>">직원 정보 · 계정 관리로 가기 →</a></p>
	<div class="iv-panel">
		<h3 class="iv-h3">권한 차이</h3>
		<div class="iv-table-wrap"><table class="iv-table iv-table--perm"><thead><tr><th></th><th>직원</th><th>재료실 관리</th></tr></thead><tbody>
			<tr><td>품목 요청 · 내 요청 취소</td><td>○</td><td>○</td></tr>
			<tr><td>요청 내역 보기</td><td><?php echo md_inv_set( 'staff_see_all_teams' ) ? '○ (모든 팀)' : '○ (고른 팀)'; ?></td><td>○</td></tr>
			<tr><td>통계</td><td><?php echo md_inv_set( 'staff_stats' ) ? '○' : '—'; ?></td><td>○</td></tr>
			<tr><td>재고 조회</td><td><?php echo md_inv_set( 'staff_stock_tab' ) ? '○ (읽기만)' : '—'; ?></td><td>○</td></tr>
			<tr><td>출고 · 반려 · 입고 · 실사 · 주문 · 선납</td><td>—</td><td>○</td></tr>
			<tr><td>품목 · 팀 · 업체 · 분류 · 설정 · 백업</td><td>—</td><td>○</td></tr>
		</tbody></table></div>
		<p class="iv-help">「라운지 관리자」는 재료실 관리에 더해 직원 정보 · 계정 승인 등 라운지 전체를 관리합니다. 직원 화면에서 보이는 범위는 「운영 설정」에서 바꿉니다.</p>
	</div>
	<?php if ( $shared && md_inv_can_accounts() ) : ?>
	<div class="iv-panel">
		<h3 class="iv-h3">병원 공용 계정</h3>
		<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>아이디</th><th>이름</th><th></th></tr></thead><tbody>
		<?php foreach ( $shared as $a ) : ?>
			<tr>
				<td data-l="아이디"><code><?php echo esc_html( $a->login ); ?></code></td>
				<td data-l="이름"><?php echo esc_html( $a->name ); ?></td>
				<td class="iv-td-act"><button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-accpass" data-set="<?php echo esc_attr( wp_json_encode( array( 'id' => (int) $a->id, 'what' => $a->login ) ) ); ?>">비밀번호</button></td>
			</tr>
		<?php endforeach; ?>
		</tbody></table></div>
	</div>
	<dialog class="iv-dlg" id="dlg-accpass"><form method="post" autocomplete="off">
		<?php md_inv_hidden( 'acc_pass' ); ?><input type="hidden" name="id" value="">
		<div class="iv-dlg__head"><b>비밀번호 바꾸기</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<p class="iv-dlg__what" data-t="what"></p>
		<label class="iv-f"><span>새 비밀번호 (8자 이상)</span><input class="iv-input" name="pass" type="text" minlength="8" required autocomplete="new-password"></label>
		<p class="iv-help">공용 계정 비밀번호를 바꾸면 그 계정으로 로그인한 모든 기기가 다시 로그인해야 합니다.</p>
		<?php md_inv_dlg_close( '바꾸기' ); ?>
	<?php endif; ?>
	<?php
}

/* ---- 보고서 메일 -------------------------------------------- */

function md_inv_settings_mail() {
	$last = get_option( 'md_inv_last_report' );
	$freq = md_inv_set( 'report_freq' );
	$days = array( 1 => '월', 2 => '화', 3 => '수', 4 => '목', 5 => '금', 6 => '토', 7 => '일' );
	?>
	<form method="post" class="iv-settings">
		<?php md_inv_hidden( 'settings' ); ?>
		<section class="iv-panel">
			<h3 class="iv-h3">정기 엑셀 보고서</h3>
			<p class="iv-help">재고 현황 · 부족 품목 · 입출고 · 요청 · 주문 · 사용금액 · 월별 · 선납 현황 · 업체를 시트로 나눈 엑셀 파일 하나를 보냅니다.</p>
			<?php md_inv_s_check( 'report_on', '정기 보고서 보내기' ); ?>
			<?php md_inv_s_text( 'report_to', '받는 주소 (여럿이면 쉼표로)', 'moondentaldigital@gmail.com' ); ?>
			<div class="iv-grid3">
				<label class="iv-f"><span>주기</span><select class="iv-input" name="s_report_freq">
					<?php foreach ( array( 'daily' => '매일', 'weekly' => '매주', 'monthly' => '매월' ) as $k => $lb ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $freq, $k ); ?>><?php echo esc_html( $lb ); ?></option><?php endforeach; ?>
				</select></label>
				<label class="iv-f"><span>요일 (매주일 때)</span><select class="iv-input" name="s_report_dow"><?php foreach ( $days as $k => $lb ) : ?><option value="<?php echo (int) $k; ?>"<?php selected( (int) md_inv_set( 'report_dow' ), $k ); ?>><?php echo esc_html( $lb ); ?>요일</option><?php endforeach; ?></select></label>
				<label class="iv-f"><span>날짜 (매월일 때)</span><select class="iv-input" name="s_report_dom"><?php for ( $d = 1; $d <= 28; $d++ ) : ?><option value="<?php echo (int) $d; ?>"<?php selected( (int) md_inv_set( 'report_dom' ), $d ); ?>><?php echo (int) $d; ?>일</option><?php endfor; ?></select></label>
				<label class="iv-f"><span>시각</span><select class="iv-input" name="s_report_hour"><?php for ( $h = 0; $h <= 23; $h++ ) : ?><option value="<?php echo (int) $h; ?>"<?php selected( (int) md_inv_set( 'report_hour' ), $h ); ?>><?php echo (int) $h; ?>시 무렵</option><?php endfor; ?></select></label>
			</div>
			<?php md_inv_s_check( 'report_backup', '백업 파일도 함께 첨부', '메일함에 그날의 전체 백업이 쌓입니다. 서버에 문제가 생겨도 메일에서 되살릴 수 있습니다.' ); ?>
			<p class="iv-help">보내는 시각은 그 시각 이후 첫 방문 때입니다(홈페이지 예약 작업 방식). 같은 주기에 두 번 보내지 않습니다. 바뀐 것이 없는 주에도 보냅니다.</p>
			<div class="iv-dlg__foot"><button class="iv-btn iv-btn--primary">저장</button></div>
		</section>
	</form>
	<section class="iv-panel">
		<h3 class="iv-h3">지금 보내 보기</h3>
		<?php if ( is_array( $last ) ) : ?>
			<p class="iv-help">마지막 발송: <?php echo esc_html( $last['at'] . ' · ' . ( 'skip' === $last['why'] ? '바뀐 것이 없어 보내지 않음' : ( $last['ok'] ? '성공' : '실패' ) ) . ' · ' . $last['to'] ); ?></p>
		<?php endif; ?>
		<form method="post" class="iv-inline-form iv-grow">
			<?php md_inv_hidden( 'report_now' ); ?><input type="hidden" name="test" value="1">
			<input class="iv-input" name="to" value="<?php echo esc_attr( md_inv_set( 'report_to' ) ); ?>" aria-label="받는 주소">
			<button class="iv-btn iv-btn--ghost">시험 메일 보내기</button>
		</form>
		<p class="iv-help"><a class="iv-link" href="<?php echo esc_url( md_inv_dl_url( 'xlsx' ) ); ?>">메일에 붙는 엑셀 파일 미리 내려받기</a></p>
	</section>
	<?php
}

/* ---- 백업 -------------------------------------------------- */

function md_inv_settings_backup() {
	$kinds = array( 'auto' => '자동', 'manual' => '수동', 'restore' => '되돌리기 전' );
	?>
	<section class="iv-panel">
		<h3 class="iv-h3">백업</h3>
		<p class="iv-help">재료실의 모든 표(품목 · 입출고 · 요청 · 주문 · 선납 · 팀 · 업체 · 분류 · 작업 기록)와 설정을 한 파일로 묶습니다. 매일 한 번 자동으로 뜨고, 보고서 메일에도 첨부됩니다.</p>
		<form method="post" class="iv-settings">
			<?php md_inv_hidden( 'settings' ); ?>
			<?php md_inv_s_check( 'backup_on', '매일 자동 백업', '마지막 백업 뒤로 바뀐 것이 없으면 그날은 건너뜁니다.' ); ?>
			<div class="iv-grid3"><?php md_inv_s_num( 'backup_keep', '자동 백업 보관 개수', '개', 3, 365 ); ?></div>
			<button class="iv-btn iv-btn--ghost">저장</button>
		</form>
		<div class="iv-toolbar iv-toolbar--actions">
			<form method="post" class="iv-inline-form"><?php md_inv_hidden( 'backup_now' ); ?><input class="iv-input" name="note" maxlength="100" placeholder="메모 (선택)"><button class="iv-btn iv-btn--primary">지금 백업</button></form>
			<a class="iv-btn iv-btn--ghost" href="<?php echo esc_url( md_inv_dl_url( 'backup' ) ); ?>"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?>지금 상태 내려받기</a>
		</div>
	</section>
	<section class="iv-panel">
		<h3 class="iv-h3">보관된 백업</h3>
		<?php $list = md_inv_backups(); if ( ! $list ) : md_inv_empty( '아직 백업이 없습니다.' ); else : ?>
		<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>일시</th><th>종류</th><th>메모</th><th class="r">줄</th><th class="r">크기</th><th></th></tr></thead><tbody>
			<?php foreach ( $list as $b ) : ?>
				<tr>
					<td data-l="일시"><?php echo esc_html( md_inv_date( $b->created_at, 'Y.n.j H:i' ) ); ?></td>
					<td data-l="종류"><?php echo esc_html( isset( $kinds[ $b->kind ] ) ? $kinds[ $b->kind ] : $b->kind ); ?></td>
					<td data-l="메모"><?php echo esc_html( $b->note ); ?></td>
					<td data-l="줄" class="r"><?php echo esc_html( md_inv_num( $b->rows_n ) ); ?></td>
					<td data-l="크기" class="r"><?php echo esc_html( size_format( (int) $b->size, 1 ) ); ?></td>
					<td class="iv-td-act">
						<a class="iv-btn iv-btn--ghost iv-btn--xs" href="<?php echo esc_url( md_inv_dl_url( 'backup', array( 'id' => $b->id ) ) ); ?>">내려받기</a>
						<?php if ( md_inv_is_owner() ) : ?><button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-restore" data-set="<?php echo esc_attr( wp_json_encode( array( 'id' => (int) $b->id, 'what' => md_inv_date( $b->created_at, 'Y.n.j H:i' ) . ' 백업으로 되돌립니다.' ) ) ); ?>">되돌리기</button><?php endif; ?>
						<form method="post" class="iv-inline-form" data-confirm="이 백업을 지울까요?"><?php md_inv_hidden( 'backup_delete' ); ?><input type="hidden" name="id" value="<?php echo (int) $b->id; ?>"><button class="iv-btn iv-btn--ghost iv-btn--xs">삭제</button></form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody></table></div>
		<?php endif; ?>
	</section>
	<?php if ( ! md_inv_is_owner() ) : ?>
	<p class="iv-help">백업으로 되돌리기는 원장 계정만 할 수 있습니다. 백업 만들기 · 내려받기는 그대로 됩니다.</p>
	<?php else : ?>
	<section class="iv-panel">
		<h3 class="iv-h3">파일에서 되돌리기</h3>
		<p class="iv-help">내려받았거나 메일로 받은 백업 파일(.json.gz)을 올려 그 시점으로 되돌립니다. 지금 상태는 자동으로 먼저 백업됩니다.</p>
		<form method="post" enctype="multipart/form-data" class="iv-settings" data-confirm="정말 되돌릴까요? 지금 재료실 데이터가 백업 시점으로 바뀝니다.">
			<?php md_inv_hidden( 'backup_restore' ); ?>
			<label class="iv-f"><span>백업 파일</span><input class="iv-input" type="file" name="file" accept=".gz,.json,application/gzip,application/json" required></label>
			<label class="iv-f"><span>확인: 「되돌리기」라고 적어 주세요</span><input class="iv-input" name="confirm" required autocomplete="off"></label>
			<button class="iv-btn iv-btn--danger">파일로 되돌리기</button>
		</form>
	</section>
	<?php endif; ?>
	<dialog class="iv-dlg" id="dlg-restore"><form method="post">
		<?php md_inv_hidden( 'backup_restore' ); ?><input type="hidden" name="id" value="">
		<div class="iv-dlg__head"><b>백업으로 되돌리기</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<p class="iv-dlg__what" data-t="what"></p>
		<p class="iv-help">지금 데이터가 그 시점으로 바뀝니다. 지금 상태는 먼저 자동으로 백업됩니다.</p>
		<label class="iv-f"><span>확인: 「되돌리기」라고 적어 주세요</span><input class="iv-input" name="confirm" required autocomplete="off"></label>
		<?php md_inv_dlg_close( '되돌리기', 'danger' ); ?>
	<?php
}

/* ---- 내보내기 ---------------------------------------------- */

function md_inv_settings_export() {
	$df = md_inv_get_date( 'df', date( 'Y-m-d', current_time( 'timestamp' ) - 30 * DAY_IN_SECONDS ) );
	$dt = md_inv_get_date( 'dt', current_time( 'Y-m-d' ) );
	?>
	<section class="iv-panel">
		<h3 class="iv-h3">엑셀 한 파일로 (시트별)</h3>
		<form method="get" class="iv-filter">
			<input type="hidden" name="app" value="stock"><input type="hidden" name="md_inv_dl" value="xlsx"><input type="hidden" name="_mdinv" value="<?php echo esc_attr( wp_create_nonce( 'md_inv_dl' ) ); ?>">
			<label class="iv-f"><span>기간 부터</span><input class="iv-input" type="date" name="df" value="<?php echo esc_attr( $df ); ?>"></label>
			<label class="iv-f"><span>까지</span><input class="iv-input" type="date" name="dt" value="<?php echo esc_attr( $dt ); ?>"></label>
			<button class="iv-btn iv-btn--primary"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?>엑셀 내려받기</button>
		</form>
		<p class="iv-help">기간은 입출고 · 요청 · 주문 · 사용금액 시트에 적용됩니다. 재고 현황 · 업체 · 선납은 지금 기준입니다.</p>
	</section>
	<section class="iv-panel">
		<h3 class="iv-h3">표 하나씩 (엑셀)</h3>
		<div class="iv-pills">
			<?php foreach ( md_inv_dataset_names() as $k => $lb ) : ?>
				<a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_dl_url( $k, array( 'df' => $df, 'dt' => $dt ) ) ); ?>"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?><?php echo esc_html( $lb ); ?></a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/* ---- 가져오기 ---------------------------------------------- */

function md_inv_settings_import() {
	global $wpdb;
	$has = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . md_inv_t( 'item' ) );
	$imp = get_option( 'md_inv_imported' );
	?>
	<section class="iv-panel">
		<h3 class="iv-h3">AppSheet 에서 가져오기</h3>
		<p class="iv-help">구글 드라이브 「문치과병원 재고관리 v2 (AppSheet)」 폴더의 시트를 각각 <b>파일 › 다운로드 › Microsoft Excel (.xlsx)</b>로 받아 올려 주세요.
			팀 · 분류 · 업체 · 선납 입금 · 품목을 옮기고, 품목마다 AppSheet 와 같은 식으로 계산한 <b>지금 재고</b>를 「기초」로 넣습니다.</p>
		<?php if ( $has ) : ?><p class="iv-flash iv-flash--warn">지금 품목 <?php echo (int) $has; ?>개가 있습니다. 가져오기를 실행하면 재고관리 데이터를 모두 지우고 새로 넣습니다(바로 전에 자동 백업).<?php echo $imp ? ' 마지막 가져오기: ' . esc_html( $imp ) : ''; ?></p><?php endif; ?>
		<form method="post" enctype="multipart/form-data" class="iv-settings">
			<?php md_inv_hidden( 'import' ); ?>
			<div class="iv-grid2">
				<label class="iv-f"><span>팀 스프레드시트 (팀 · 분류 · 중분류 · 세부분류 탭) <em>*</em></span><input class="iv-input" type="file" name="team" accept=".xlsx" required></label>
				<label class="iv-f"><span>업체 스프레드시트 (업체 · 선납입금 탭) <em>*</em></span><input class="iv-input" type="file" name="vendor" accept=".xlsx" required></label>
				<label class="iv-f"><span>품목 스프레드시트 <em>*</em></span><input class="iv-input" type="file" name="item" accept=".xlsx" required></label>
				<label class="iv-f"><span>입출고 스프레드시트 (현재고 계산용)</span><input class="iv-input" type="file" name="io" accept=".xlsx"></label>
			</div>
			<label class="iv-check"><input type="checkbox" name="history" value="1"> 입출고 기록 줄도 하나하나 옮기기 (AppSheet 시험 기록이 섞여 있으면 끄세요)</label>
			<div class="iv-toolbar iv-toolbar--actions">
				<button class="iv-btn iv-btn--ghost" name="dry" value="1">미리보기 (바꾸지 않음)</button>
			</div>
			<label class="iv-f"><span>실제로 가져오려면 「가져오기」라고 적고 아래 버튼</span><input class="iv-input" name="confirm" autocomplete="off"></label>
			<button class="iv-btn iv-btn--danger" name="commit" value="1">가져오기 실행</button>
		</form>
	</section>
	<?php
}

/* ---- 작업 기록 --------------------------------------------- */

function md_inv_settings_log() {
	global $wpdb;
	$pg  = max( 1, (int) md_inv_get( 'pg', 1 ) );
	$per = 100;
	$q   = md_inv_get( 'iq' );
	$w   = '';
	if ( '' !== $q ) { $like = '%' . $wpdb->esc_like( $q ) . '%'; $w = $wpdb->prepare( ' WHERE action LIKE %s OR detail LIKE %s OR person LIKE %s', $like, $like, $like ); }
	$n    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . md_inv_t( 'log' ) . $w );
	$rows = $wpdb->get_results( 'SELECT * FROM ' . md_inv_t( 'log' ) . $w . ' ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . ( ( $pg - 1 ) * $per ) );
	?>
	<p class="iv-help">누가 언제 무엇을 했는지 모두 남습니다. 지워지지 않습니다.</p>
	<form method="get" class="iv-filter"><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="settings"><input type="hidden" name="is" value="log">
		<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>"></label><button class="iv-btn iv-btn--ghost">보기</button></form>
	<?php if ( ! $rows ) { md_inv_empty( '기록이 없습니다.' ); return; } ?>
	<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>일시</th><th>누가</th><th>무엇</th><th>내용</th></tr></thead><tbody>
	<?php foreach ( $rows as $r ) : $u = get_userdata( (int) $r->user_id ); ?>
		<tr><td data-l="일시"><?php echo esc_html( md_inv_date( $r->created_at, 'y.n.j H:i' ) ); ?></td><td data-l="누가"><?php echo esc_html( $r->person . ( $u ? ' (' . $u->user_login . ')' : '' ) ); ?></td><td data-l="무엇"><b><?php echo esc_html( $r->action ); ?></b></td><td data-l="내용"><?php echo esc_html( $r->detail ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table></div>
	<?php
	md_inv_pager( $n, $per, $pg );
}
