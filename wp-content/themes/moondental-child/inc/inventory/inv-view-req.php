<?php
/**
 * 재고관리 v5 — 요청 · 내역 (직원과 관리자가 함께 쓰는 화면)
 *
 * 요청 화면은 품목 목록을 한 번에 실어 보내고 고르기·검색·장바구니는 브라우저에서 한다.
 * 600개 가까운 품목을 고를 때마다 서버를 오가면 진료실 와이파이에서 답답하다.
 * 보내기만 서버로 간다 (일회용 표로 두 번 들어가지 않게).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_inv_view_req() {
	$admin = md_inv_is_admin();
	$S     = md_inv_settings();
	$see_stock = $admin || $S['staff_see_stock'];
	$see_price = $admin || $S['staff_see_price'];

	$cats = array();
	foreach ( md_inv_cats( true ) as $c ) { $cats[] = array( 'id' => (int) $c->id, 'p' => (int) $c->parent_id, 'l' => (int) $c->level, 'n' => $c->name ); }
	$vend = array();
	foreach ( md_inv_vendors( false ) as $v ) { $vend[ (int) $v->id ] = array( 'n' => $v->name, 'pp' => (int) $v->prepaid ); }
	$items = array();
	foreach ( md_inv_items() as $it ) {
		$items[] = array(
			'i'  => (int) $it->id,
			'n'  => $it->name,
			'v'  => (int) $it->vendor_id,
			'u'  => $it->unit,
			'c1' => (int) $it->cat1, 'c2' => (int) $it->cat2, 'c3' => (int) $it->cat3,
			's'  => $see_stock ? $it->stock : null,
			'm'  => $see_stock ? $it->min_stock : null,
			'p'  => $see_price ? $it->price : null,
			'b'  => $it->barcode,
			'l'  => (string) $it->location,
			'o'  => $it->onord > 0 ? 1 : 0,
		);
	}
	$teams = array();
	foreach ( md_inv_teams() as $tm ) { $teams[] = array( 'id' => (int) $tm->id, 'n' => $tm->name ); }

	global $wpdb;
	$pend = array();
	foreach ( $wpdb->get_results( 'SELECT team_id, item_id, SUM(qty) AS q FROM ' . md_inv_t( 'req' ) . " WHERE status = 'pending' AND item_id > 0 GROUP BY team_id, item_id" ) as $r ) {
		$pend[ (int) $r->team_id ][ (int) $r->item_id ] = (int) $r->q;
	}

	$cfg = array(
		'items' => $items, 'cats' => $cats, 'vendors' => $vend, 'teams' => $teams,
		'favs' => md_inv_fav_map(), 'favNonce' => wp_create_nonce( 'md_inv_post' ),
		'recent' => md_inv_recent_by_team( (int) $S['recent_days'] ), 'pending' => $pend, 'recentDays' => (int) $S['recent_days'],
		'L' => array( 'c1' => $S['label_cat1'], 'c2' => $S['label_cat2'], 'c3' => $S['label_cat3'] ),
		'opt' => array(
			'needName' => (int) $S['req_need_name'], 'custom' => (int) $S['req_allow_custom'], 'over' => (int) $S['req_allow_over'],
			'remember' => (int) $S['req_remember'], 'max' => (int) $S['req_max_qty'], 'stock' => $see_stock ? 1 : 0, 'price' => $see_price ? 1 : 0,
			'admin' => $admin ? 1 : 0,
		),
		'vendorNames' => array_values( array_map( function ( $v ) { return $v['n']; }, $vend ) ),
		/* v5.8 · 개인 계정 — 이름은 계정 이름으로 고정, 팀은 지난번에 고른 팀 */
		'me' => md_inv_is_personal() ? md_inv_me() : '',
		'myTeam' => md_inv_is_personal() ? (int) get_user_meta( get_current_user_id(), 'md_inv_team', true ) : 0,
	);
	?>
	<div id="iv-req" class="iv-req">
		<ol class="iv-steps" aria-label="신청 순서">
			<li id="iv-step1"><b>1</b> 팀 고르기</li>
			<li id="iv-step2"><b>2</b> 품목 담기</li>
			<li id="iv-step3"><b>3</b> 신청하기</li>
		</ol>
		<div class="iv-req__bar">
			<button type="button" class="iv-teambtn" id="iv-teambtn" data-dlg="iv-team-dlg" aria-haspopup="dialog">
				<span class="iv-teambtn__k">우리 팀</span>
				<b class="iv-teambtn__v" id="iv-team-label">팀을 골라 주세요</b>
				<?php echo md_inv_icon( 'dn', 18 ); // phpcs:ignore ?>
			</button>
			<input type="hidden" id="iv-team" value="">
			<div class="iv-search">
				<?php echo md_inv_icon( 'search', 18 ); // phpcs:ignore ?>
				<input type="search" id="iv-q" class="iv-input" placeholder="품목 이름 · 업체 · 바코드로 찾기" autocomplete="off" enterkeyhint="search" aria-label="품목 찾기">
				<button type="button" class="iv-btn iv-btn--icon" id="iv-scan" aria-label="바코드 스캔" title="바코드 스캔"><?php echo md_inv_icon( 'scan', 20 ); // phpcs:ignore ?></button>
			</div>
		</div>
		<div class="iv-chips" id="iv-chips" role="tablist" aria-label="<?php echo esc_attr( $S['label_cat2'] ); ?>"></div>
		<div id="iv-list" class="iv-list" aria-live="polite"><p class="iv-loading">품목을 불러오는 중…</p></div>
		<?php if ( $S['req_allow_custom'] ) : ?>
			<div class="iv-custom-cta">
				<p>찾는 품목이 목록에 없나요?</p>
				<button type="button" class="iv-btn iv-btn--ghost" data-dlg="iv-custom"><?php echo md_inv_icon( 'plus', 18 ); // phpcs:ignore ?>목록에 없는 품목 신청</button>
			</div>
		<?php endif; ?>

		<div class="iv-cartbar" id="iv-cartbar" hidden>
			<button type="button" class="iv-cartbar__btn" data-dlg="iv-cart">
				<span class="iv-cartbar__ico"><?php echo md_inv_icon( 'cart', 22 ); // phpcs:ignore ?><b id="iv-cart-n">0</b></span>
				<span class="iv-cartbar__txt" id="iv-cart-txt">담은 품목</span>
				<span class="iv-cartbar__go">신청하기 →</span>
			</button>
		</div>
	</div>

	<dialog class="iv-dlg iv-dlg--sheet" id="iv-team-dlg" aria-labelledby="iv-team-h">
		<div class="iv-dlg__head"><b id="iv-team-h">어느 팀에서 신청하나요?</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<div class="iv-teamgrid" id="iv-teamgrid">
			<?php foreach ( $teams as $tm ) : ?><button type="button" class="iv-teamgrid__b" data-team="<?php echo (int) $tm['id']; ?>"><?php echo esc_html( $tm['n'] ); ?></button><?php endforeach; ?>
		</div>
	</dialog>

	<dialog class="iv-dlg iv-dlg--sheet iv-dlg--wide" id="iv-cart" aria-labelledby="iv-cart-h">
		<form method="post" id="iv-cart-form" novalidate>
			<?php md_inv_hidden( 'req_send', md_inv_url( array( 'iv' => 'req' ) ) ); ?>
			<input type="hidden" name="cart" id="iv-cart-json" value="">
			<input type="hidden" name="tok" id="iv-cart-tok" value="">
			<div class="iv-dlg__head"><b id="iv-cart-h">담은 품목 확인하고 신청하기</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
			<div id="iv-cart-lines" class="iv-cart-lines"></div>
			<div class="iv-grid2">
				<label class="iv-f"><span>팀 <em>*</em></span>
					<select class="iv-input" name="team_id" id="iv-cart-team" required>
						<option value="">팀을 고르세요</option>
						<?php foreach ( $teams as $tm ) : ?><option value="<?php echo (int) $tm['id']; ?>"><?php echo esc_html( $tm['n'] ); ?></option><?php endforeach; ?>
					</select>
				</label>
				<label class="iv-f"><span>신청자 이름<?php echo $S['req_need_name'] ? ' <em>*</em>' : ''; ?></span>
					<?php if ( md_inv_is_personal() ) : ?>
						<input class="iv-input iv-input--me" name="requester" id="iv-cart-name" value="<?php echo esc_attr( md_inv_me() ); ?>" readonly aria-readonly="true" title="내 계정 이름으로 신청합니다">
					<?php else : ?>
					<input class="iv-input" name="requester" id="iv-cart-name" maxlength="40" autocomplete="name" placeholder="이름"<?php echo $S['req_need_name'] ? ' required' : ''; ?>>
					<?php endif; ?>
				</label>
			</div>
			<label class="iv-f"><span>메모 (선택)</span><input class="iv-input" name="note" maxlength="300" placeholder="예: 내일 오전 수술용"></label>
			<label class="iv-check"><input type="checkbox" name="urgent" value="1"> 급해요 (먼저 처리)</label>
			<p class="iv-err" id="iv-cart-err" role="alert" hidden></p>
			<div class="iv-dlg__foot">
				<button type="button" class="iv-btn iv-btn--ghost" id="iv-cart-clear">모두 비우기</button>
				<button type="submit" class="iv-btn iv-btn--primary iv-btn--lg" id="iv-cart-send">신청하기</button>
			</div>
		</form>
	</dialog>

	<?php if ( $S['req_allow_custom'] ) : ?>
	<dialog class="iv-dlg" id="iv-custom" aria-labelledby="iv-custom-h">
		<form id="iv-custom-form">
			<div class="iv-dlg__head"><b id="iv-custom-h">목록에 없는 품목 신청</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
			<p class="iv-help">적어 주신 내용을 보고 관리자가 구매하거나 품목으로 등록합니다.</p>
			<label class="iv-f"><span>품목 이름 <em>*</em></span><input class="iv-input" name="name" maxlength="200" required placeholder="예: 3M 필텍 Z350 A2"></label>
			<div class="iv-grid2">
				<label class="iv-f"><span>업체 (아는 경우)</span><input class="iv-input" name="vendor" maxlength="100" list="iv-vendor-dl"></label>
				<label class="iv-f"><span>수량 <em>*</em></span><input class="iv-input" name="qty" type="number" inputmode="numeric" min="1" max="<?php echo (int) $S['req_max_qty']; ?>" value="1" required></label>
				<label class="iv-f"><span>단위</span><input class="iv-input" name="unit" maxlength="20" placeholder="ea · box · 갑"></label>
				<label class="iv-f"><span>가격 (아는 경우)</span><input class="iv-input" name="price" inputmode="numeric" placeholder="원"></label>
			</div>
			<label class="iv-f"><span>구매 링크 · 온라인몰</span><input class="iv-input" name="link" type="url" maxlength="400" placeholder="https://"></label>
			<label class="iv-f"><span>설명</span><input class="iv-input" name="note" maxlength="300" placeholder="용도 · 규격 등"></label>
			<datalist id="iv-vendor-dl"><?php foreach ( $vend as $v ) : ?><option value="<?php echo esc_attr( $v['n'] ); ?>"></option><?php endforeach; ?></datalist>
			<div class="iv-dlg__foot"><button type="submit" class="iv-btn iv-btn--primary">장바구니에 담기</button></div>
		</form>
	</dialog>
	<?php endif; ?>

	<dialog class="iv-dlg" id="iv-scan-dlg" aria-labelledby="iv-scan-h">
		<div class="iv-dlg__head"><b id="iv-scan-h">바코드 스캔</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<div class="iv-scan"><video id="iv-scan-video" playsinline muted></video><div class="iv-scan__frame"></div></div>
		<p class="iv-help" id="iv-scan-msg">바코드를 네모 안에 맞춰 주세요. 카메라 권한을 물으면 「허용」을 눌러 주세요.</p>
		<div class="iv-scan-manual"><label class="iv-f"><span>직접 입력</span><input class="iv-input" id="iv-scan-manual" inputmode="numeric" enterkeyhint="done" placeholder="바코드 숫자"></label><button type="button" class="iv-btn iv-btn--primary" id="iv-scan-ok">확인</button></div>
	</dialog>

	<?php md_inv_install_help(); ?>
	<script type="application/json" id="iv-req-data"><?php echo wp_json_encode( $cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
	<?php
}

/* ============================================================
 * 내역 — 보낸 요청이 어떻게 됐나
 * ============================================================ */

function md_inv_view_mine() {
	$admin = md_inv_is_admin();
	$S     = md_inv_settings();
	$team  = (int) md_inv_get( 'it', 0 );
	$st    = md_inv_get( 'ist', '' );
	$days  = (int) md_inv_get( 'idays', (string) $S['history_days'] );
	$q     = md_inv_get( 'iq' );
	if ( ! $admin && ! $S['staff_see_all_teams'] && ! $team ) { $team = -1; }
	$days  = max( 1, min( 3650, $days ) );
	$args  = array( 'team_id' => max( 0, $team ), 'days' => $days, 'search' => $q, 'limit' => 300 );
	if ( in_array( $st, array( 'pending', 'done', 'rejected', 'cancelled' ), true ) ) { $args['status'] = $st; }
	$rows  = $team < 0 ? array() : md_inv_reqs( $args );
	$sent  = md_inv_get( 'sent' );
	$nb    = preg_replace( '/[^a-z0-9]/', '', md_inv_get( 'nb' ) );
	?>
	<?php if ( '' !== $sent ) : ?><span hidden id="iv-sent-ok" data-tok="<?php echo esc_attr( $sent ); ?>"></span><?php endif; ?>
	<form class="iv-filter" method="get" data-autosubmit>
		<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="mine">
		<label class="iv-f"><span>팀</span><?php md_inv_team_select( 'it', max( 0, $team ), false, $admin || $S['staff_see_all_teams'] ? '모든 팀' : '우리 팀을 고르세요' ); ?></label>
		<label class="iv-f"><span>상태</span><select class="iv-input" name="ist">
			<option value="">전체</option>
			<?php foreach ( array( 'pending', 'done', 'rejected', 'cancelled' ) as $s ) : ?><option value="<?php echo esc_attr( $s ); ?>"<?php selected( $st, $s ); ?>><?php echo esc_html( md_inv_req_status_label( $s ) ); ?></option><?php endforeach; ?>
		</select></label>
		<label class="iv-f"><span>기간</span><select class="iv-input" name="idays">
			<?php foreach ( array( 7 => '1주', 30 => '1개월', 90 => '3개월', 180 => '6개월', 365 => '1년' ) as $d => $lb ) : ?><option value="<?php echo (int) $d; ?>"<?php selected( $days, $d ); ?>><?php echo esc_html( $lb ); ?></option><?php endforeach; ?>
			<?php if ( ! in_array( $days, array( 7, 30, 90, 180, 365 ), true ) ) : ?><option value="<?php echo (int) $days; ?>" selected><?php echo (int) $days; ?>일</option><?php endif; ?>
		</select></label>
		<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>" placeholder="품목 · 이름"></label>
		<button class="iv-btn iv-btn--ghost">보기</button>
	</form>

	<?php if ( '' !== $sent ) : $nsent = 0; foreach ( $rows as $r0 ) { if ( '' !== $nb && $r0->batch === $nb ) { $nsent++; } } ?>
		<div class="iv-sent" role="status"><b>✓ 신청<?php echo $nsent ? ' ' . (int) $nsent . '건' : ''; ?>을 보냈습니다.</b> 아래에서 처리 상태(대기 → 출고 완료)를 볼 수 있습니다. <a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'req' ) ) ); ?>">더 신청하기</a></div>
	<?php endif; ?>

	<?php if ( $team < 0 ) : ?>
		<?php md_inv_empty( '위에서 우리 팀을 고르면 신청 내역이 보입니다.' ); ?>
		<?php return; ?>
	<?php endif; ?>

	<div class="iv-toolbar">
		<span class="iv-muted"><?php echo count( $rows ); ?>건<?php echo count( $rows ) >= 300 ? ' (최근 300건)' : ''; ?></span>
		<?php if ( $admin || $S['staff_see_all_teams'] ) : md_inv_dl_buttons( 'requests', array( 'df' => date( 'Y-m-d', current_time( 'timestamp' ) - $days * DAY_IN_SECONDS ), 'dt' => current_time( 'Y-m-d' ) ) ); endif; ?>
	</div>

	<?php if ( ! $rows ) { md_inv_empty( '이 조건의 신청이 없습니다.' ); return; } ?>
	<div class="iv-cards">
		<?php $day = ''; foreach ( $rows as $r ) :
			$d = substr( $r->created_at, 0, 10 );
			if ( $d !== $day ) { $day = $d; echo '<h3 class="iv-day">' . esc_html( date( 'n월 j일', strtotime( $d ) ) . ' (' . array( '일', '월', '화', '수', '목', '금', '토' )[ (int) date( 'w', strtotime( $d ) ) ] . ')' ) . '</h3>'; }
			?>
			<article class="iv-card iv-card--req is-<?php echo esc_attr( $r->status ); ?><?php echo ( '' !== $nb && $r->batch === $nb ) ? ' is-new' : ''; ?>" data-team="<?php echo (int) $r->team_id; ?>" data-done="<?php echo esc_attr( (string) $r->done_at ); ?>">
				<div class="iv-card__main">
					<div class="iv-card__title"><?php echo esc_html( $r->name ); ?><?php if ( ! $r->item_id ) : ?> <span class="iv-tag">목록에 없음</span><?php endif; ?><?php if ( $r->urgent ) : ?> <span class="iv-tag iv-tag--hot">급함</span><?php endif; ?></div>
					<div class="iv-card__sub"><?php echo esc_html( md_inv_team_name( $r->team_id ) . ' · ' . $r->requester . ' · ' . md_inv_date( $r->created_at, 'H:i' ) ); ?></div>
					<?php if ( '' !== $r->note ) : ?><div class="iv-card__note">“<?php echo esc_html( $r->note ); ?>”</div><?php endif; ?>
					<?php if ( 'rejected' === $r->status && '' !== $r->admin_note ) : ?><div class="iv-card__why">반려 사유: <?php echo esc_html( $r->admin_note ); ?></div><?php endif; ?>
					<?php if ( 'done' === $r->status ) : ?><div class="iv-card__sub">✓ <?php echo esc_html( md_inv_ago( $r->done_at ) . ' · ' . $r->done_by . ( '' !== (string) $r->receiver ? ' → 받은 사람 ' . $r->receiver : '' ) ); ?><?php echo '' !== $r->admin_note ? ' · ' . esc_html( $r->admin_note ) : ''; ?></div><?php endif; ?>
					<?php if ( 'pending' === $r->status && (int) $r->ord_id ) : ?><div class="iv-card__sub iv-accent">주문해 두었습니다 — 들어오면 출고됩니다</div><?php endif; ?>
					<?php if ( 'pending' === $r->status && '' !== $r->admin_note ) : ?><div class="iv-card__sub"><?php echo esc_html( $r->admin_note ); ?></div><?php endif; ?>
				</div>
				<div class="iv-card__side">
					<div class="iv-qty"><?php echo (int) $r->qty; ?><small><?php echo esc_html( $r->unit ); ?></small></div>
					<?php echo md_inv_req_badge( $r ); // phpcs:ignore ?>
					<?php if ( 'pending' !== $r->status && $r->item_id && (int) $r->item_active ) : ?>
						<button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-readd="<?php echo esc_attr( wp_json_encode( array( 'id' => (int) $r->item_id, 'qty' => (int) $r->qty ) ) ); ?>" data-href="<?php echo esc_url( md_inv_url( array( 'iv' => 'req' ) ) ); ?>">다시 담기</button>
					<?php endif; ?>
					<?php if ( 'pending' === $r->status && ( $admin || $S['staff_cancel'] ) ) : ?>
						<form method="post" data-confirm="이 신청을 취소할까요?">
							<?php md_inv_hidden( 'req_cancel' ); ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
							<button class="iv-btn iv-btn--ghost iv-btn--sm">취소</button>
						</form>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
}
