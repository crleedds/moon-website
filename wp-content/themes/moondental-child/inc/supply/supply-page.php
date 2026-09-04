<?php
/**
 * 재고관리 — 화면과 폼 처리
 *
 * 서버에서 그려 보낸다. 자바스크립트가 없어도 전부 동작한다.
 * 진료실 PC·태블릿 사정이 제각각이라, 화면이 안 뜨는 것보다 느린 편이 낫다.
 *
 * 폼은 POST → 처리 → 리다이렉트(PRG). 새로고침해도 두 번 신청되지 않는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 탭 · URL
 * ============================================================ */

function md_sup_tabs() {
	return array(
		'request'   => array( 'label' => '요청',      'icon' => '📤', 'manage' => false ),
		'stats'     => array( 'label' => '통계',      'icon' => '📊', 'manage' => false ),
		'manage'    => array( 'label' => '반출관리',  'icon' => '📋', 'manage' => true ),
		'inbound'   => array( 'label' => '입고',      'icon' => '📥', 'manage' => true ),
		'inventory' => array( 'label' => '재고',      'icon' => '📦', 'manage' => true ),
	);
}

function md_sup_current_tab() {
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'request';
	$tabs = md_sup_tabs();
	if ( ! isset( $tabs[ $tab ] ) ) { $tab = 'request'; }
	if ( $tabs[ $tab ]['manage'] && ! md_sup_can_manage() ) { $tab = 'request'; }
	return $tab;
}

/**
 * 직원 전용 페이지는 여러 도구의 허브다.
 * 들어오면 먼저 무엇을 할지 고르고, 고른 뒤에 그 도구로 들어간다.
 */
function md_sup_apps() {
	return array(
		'stock' => array(
			'label' => '재고관리',
			'icon'  => '📦',
			'desc'  => '재료 신청 · 사용량과 비용 확인 · 입출고 관리',
		),
		'notice' => array(
			'label' => '공지사항',
			'icon'  => '📢',
			'desc'  => '병원 내부 공지와 안내',
		),
	);
}

function md_sup_current_app() {
	$app  = isset( $_GET['app'] ) ? sanitize_key( wp_unslash( $_GET['app'] ) ) : '';
	$apps = md_sup_apps();
	return isset( $apps[ $app ] ) ? $app : '';
}

/**
 * 페이지 주소를 만든다.
 *
 * app 을 넘기지 않으면 지금 보고 있는 도구를 그대로 유지한다.
 * 화면마다 'app' => 'stock' 을 일일이 붙이지 않아도 되게 하기 위함이다.
 * 허브로 나가려면 'app' => '' 을 명시한다.
 */
function md_sup_url( $args = array() ) {
	$base = get_permalink();
	if ( ! $base ) { $base = home_url( '/직원/' ); }

	if ( ! array_key_exists( 'app', $args ) ) {
		$cur = md_sup_current_app();
		if ( $cur ) { $args['app'] = $cur; }
	} elseif ( '' === $args['app'] ) {
		unset( $args['app'] );
	}

	return add_query_arg( $args, $base );
}

/** 재고관리 페이지인가 — 슬러그 또는 템플릿 지정 둘 다 인정 */
function md_sup_is_page() {
	if ( ! is_page() ) { return false; }
	if ( is_page_template( 'page-templates/page-supply.php' ) ) { return true; }
	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	return in_array( $slug, array( '직원', 'staff', 'supply', '재고관리' ), true );
}

/** 이 페이지에서만 CSS·JS 를 싣는다 */
function md_sup_enqueue() {
	if ( ! md_sup_is_page() ) { return; }

	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();

	$css = '/assets/css/supply.css';
	if ( file_exists( $dir . $css ) ) {
		wp_enqueue_style( 'moondental-supply', $uri . $css, array( 'moondental-child-style' ), filemtime( $dir . $css ) );
	}
	$js = '/assets/js/supply.js';
	if ( file_exists( $dir . $js ) ) {
		wp_enqueue_script( 'moondental-supply', $uri . $js, array(), filemtime( $dir . $js ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'md_sup_enqueue', 30 );

/**
 * 환자용 떠다니는 버튼을 이 페이지에서만 걷어낸다.
 *
 * 오시는 길·전화 상담·네이버 예약·카카오톡·언어 선택은 환자분을 위한 것이라
 * 직원 재고 화면에서는 쓸 일이 없고, 화면 아래 합계 바를 가려서 방해가 된다.
 */
function md_sup_strip_patient_chrome() {
	if ( ! md_sup_is_page() ) { return; }
	remove_action( 'wp_footer', 'moondental_floating_actions', 5 );
}
add_action( 'wp', 'md_sup_strip_patient_chrome', 20 );

/** body 에 표시를 남겨, 헤더·푸터의 환자용 요소를 CSS 로 감춘다 */
function md_sup_body_class( $classes ) {
	if ( md_sup_is_page() ) { $classes[] = 'mds-page-body'; }
	return $classes;
}
add_filter( 'body_class', 'md_sup_body_class' );

/* ============================================================
 * 폼 처리 — template_redirect 에서 먼저 받는다
 * ============================================================ */

function md_sup_handle_post() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { return; }
	if ( ! isset( $_POST['md_sup_action'] ) ) { return; }
	if ( ! md_sup_can_use() ) { return; }

	$action = sanitize_key( wp_unslash( $_POST['md_sup_action'] ) );
	if ( ! isset( $_POST['md_sup_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_sup_nonce'] ), 'md_sup_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}

	$redirect = md_sup_url( array( 'tab' => md_sup_current_tab() ) );

	switch ( $action ) {

		/* 재료 신청 */
		case 'request':
			$team_id = isset( $_POST['team_id'] ) ? (int) $_POST['team_id'] : 0;
			$qtys    = isset( $_POST['qty'] ) && is_array( $_POST['qty'] ) ? wp_unslash( $_POST['qty'] ) : array();
			$reasons = isset( $_POST['reason'] ) && is_array( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : array();

			$lines = array();
			foreach ( $qtys as $item_id => $q ) {
				$q = (int) $q;
				if ( $q <= 0 ) { continue; }
				$lines[ (int) $item_id ] = array(
					'qty'         => $q,
					'over_reason' => isset( $reasons[ $item_id ] ) ? sanitize_text_field( $reasons[ $item_id ] ) : '',
				);
			}

			if ( empty( $lines ) ) {
				$redirect = add_query_arg( 'msg', 'empty', $redirect );
				break;
			}

			$res = md_sup_create_request(
				$team_id,
				$lines,
				isset( $_POST['urgent'] ) ? 1 : 0,
				isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : ''
			);
			$redirect = add_query_arg( array( 'team' => $team_id, 'msg' => is_wp_error( $res ) ? 'error' : 'sent' ), $redirect );
			break;

		/* 출고 처리 */
		case 'release':
			if ( ! md_sup_can_manage() ) { break; }
			$req_id  = isset( $_POST['req_id'] ) ? (int) $_POST['req_id'] : 0;
			$outs    = isset( $_POST['out'] ) && is_array( $_POST['out'] ) ? wp_unslash( $_POST['out'] ) : array();
			$qty_map = array();
			foreach ( $outs as $line_id => $q ) { $qty_map[ (int) $line_id ] = (int) $q; }
			md_sup_release_request( $req_id, $qty_map );
			$redirect = add_query_arg( array( 'tab' => 'manage', 'msg' => 'released' ), md_sup_url() );
			break;

		/* 반려 */
		case 'reject':
			if ( ! md_sup_can_manage() ) { break; }
			md_sup_reject_request(
				isset( $_POST['req_id'] ) ? (int) $_POST['req_id'] : 0,
				isset( $_POST['reject_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reject_reason'] ) ) : ''
			);
			$redirect = add_query_arg( array( 'tab' => 'manage', 'msg' => 'rejected' ), md_sup_url() );
			break;

		/* 입고 등록 */
		case 'inbound':
			if ( ! md_sup_can_manage() ) { break; }
			$qtys = isset( $_POST['inqty'] ) && is_array( $_POST['inqty'] ) ? wp_unslash( $_POST['inqty'] ) : array();
			$n    = 0;
			foreach ( $qtys as $item_id => $q ) {
				$q = (int) $q;
				if ( 0 === $q ) { continue; }
				md_sup_move( (int) $item_id, abs( $q ), 'in', 0, 0, '입고' );
				$n++;
			}
			$redirect = add_query_arg( array( 'tab' => 'inbound', 'msg' => $n ? 'inbound' : 'empty' ), md_sup_url() );
			break;

		/* 실사 조정 — 센 수량과 장부가 다를 때 그 차이를 원장에 남긴다 */
		case 'adjust':
			if ( ! md_sup_can_manage() ) { break; }
			$counts = isset( $_POST['count'] ) && is_array( $_POST['count'] ) ? wp_unslash( $_POST['count'] ) : array();
			$n      = 0;
			foreach ( $counts as $item_id => $c ) {
				if ( '' === trim( (string) $c ) ) { continue; }
				$item_id = (int) $item_id;
				$diff    = (int) $c - md_sup_stock( $item_id );
				if ( 0 === $diff ) { continue; }
				md_sup_move( $item_id, $diff, 'adjust', 0, 0, '실사 조정' );
				$n++;
			}
			$redirect = add_query_arg( array( 'tab' => 'inventory', 'msg' => $n ? 'adjusted' : 'empty' ), md_sup_url() );
			break;

		/* 공지 쓰기 · 수정 */
		case 'notice':
			if ( ! md_sup_can_manage() ) { break; }
			$res = md_sup_notice_save(
				isset( $_POST['notice_id'] ) ? (int) $_POST['notice_id'] : 0,
				isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
				isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '',
				isset( $_POST['pinned'] ) ? 1 : 0
			);
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'app' => 'notice', 'msg' => 'error' ), md_sup_url( array( 'app' => 'notice' ) ) )
				: add_query_arg( array( 'app' => 'notice', 'n' => (int) $res, 'msg' => 'noticed' ), md_sup_url( array( 'app' => 'notice' ) ) );
			break;

		/* 공지 삭제 */
		case 'notice_del':
			if ( ! md_sup_can_manage() ) { break; }
			md_sup_notice_delete( isset( $_POST['notice_id'] ) ? (int) $_POST['notice_id'] : 0 );
			$redirect = add_query_arg( array( 'app' => 'notice', 'msg' => 'notice_del' ), md_sup_url( array( 'app' => 'notice' ) ) );
			break;
	}

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'template_redirect', 'md_sup_handle_post', 1 );

/**
 * 고른 팀을 기억한다. 공용 계정이라 소속 팀이 없어서,
 * 매번 다시 고르게 하면 실수로 다른 팀을 누를 확률이 올라간다.
 * 출력 전에 실행되어야 하므로 template_redirect 에서 처리한다.
 */
function md_sup_remember_team() {
	if ( ! md_sup_is_page() || ! isset( $_GET['team'] ) ) { return; }
	if ( headers_sent() ) { return; }
	$team = (int) $_GET['team'];
	if ( $team <= 0 ) { return; }
	setcookie( 'md_sup_team', (string) $team, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
}
add_action( 'template_redirect', 'md_sup_remember_team', 2 );

/* ============================================================
 * 보조
 * ============================================================ */

/** 이 팀이 최근에 받아간 품목 — 신청 화면 기본 목록 */
function md_sup_frequent_items( $team_id, $months = 3, $limit = 40 ) {
	global $wpdb;
	$t     = md_sup_tables();
	$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $months . ' months', current_time( 'timestamp' ) ) );

	return $wpdb->get_results( $wpdb->prepare(
		"SELECT i.*, COALESCE(s.stock,0) AS stock, SUM(-l.qty) AS taken
		 FROM {$t['ledger']} l
		 INNER JOIN {$t['items']} i ON i.id = l.item_id
		 LEFT JOIN (SELECT item_id, SUM(qty) AS stock FROM {$t['ledger']} GROUP BY item_id) s ON s.item_id = i.id
		 WHERE l.team_id = %d AND l.reason = 'out' AND l.created_at >= %s AND i.active = 1
		 GROUP BY i.id
		 ORDER BY taken DESC LIMIT %d",
		$team_id, $since, $limit
	) );
}

function md_sup_notice( $code ) {
	$map = array(
		'sent'     => array( 'ok',   '신청이 접수되었습니다.' ),
		'empty'    => array( 'warn', '수량을 입력한 품목이 없습니다.' ),
		'error'    => array( 'warn', '저장하지 못했습니다. 팀이 지정되어 있는지 확인해 주세요.' ),
		'released' => array( 'ok',   '출고 처리했습니다. 재고에 반영되었습니다.' ),
		'rejected' => array( 'ok',   '반려 처리했습니다.' ),
		'inbound'  => array( 'ok',   '입고를 기록했습니다.' ),
		'adjusted'   => array( 'ok',   '실사 결과를 반영했습니다.' ),
		'noticed'    => array( 'ok',   '공지를 저장했습니다.' ),
		'notice_del' => array( 'ok',   '공지를 삭제했습니다.' ),
	);
	if ( ! isset( $map[ $code ] ) ) { return ''; }
	list( $type, $text ) = $map[ $code ];
	return '<div class="mds-notice mds-notice--' . esc_attr( $type ) . '">' . esc_html( $text ) . '</div>';
}

/* ============================================================
 * 화면
 * ============================================================ */

/** 로그인 안 된 경우 */
function md_sup_render_login() {
	?>
	<div class="mds-gate">
		<div class="mds-gate__box">
			<span class="mds-gate__eyebrow">문치과병원 직원 전용</span>
			<h1>재고관리</h1>
			<p>병원에서 발급받은 계정으로 로그인해 주세요.</p>
			<?php
			wp_login_form( array(
				'redirect'       => md_sup_url(),
				'label_username' => '아이디',
				'label_password' => '비밀번호',
				'label_log_in'   => '로그인',
				'remember'       => true,
				'label_remember' => '로그인 상태 유지',
			) );
			?>
			<?php /* 「비밀번호를 잊으셨나요」 링크는 두지 않는다.
			         이 두 계정은 재설정 메일을 받을 수 없게 막아 두었으므로
			         눌러도 아무 일도 일어나지 않는다. 안내 문구로 대신한다. */ ?>
			<p class="mds-gate__help">
				아이디나 비밀번호를 모르시면 경영지원실에 문의해 주세요.
			</p>
		</div>
	</div>
	<?php
}

/** 로그인은 됐지만 재고 권한이 없는 경우 */
function md_sup_render_denied() {
	?>
	<div class="mds-gate">
		<div class="mds-gate__box">
			<span class="mds-gate__eyebrow">접근 권한 없음</span>
			<h1>재고관리 권한이 없습니다</h1>
			<p>
				<?php echo esc_html( wp_get_current_user()->display_name ); ?> 님 계정에는 재고관리 권한이 없습니다.
				경영지원실에 권한 부여를 요청해 주세요.
			</p>
			<p class="mds-gate__help"><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a></p>
		</div>
	</div>
	<?php
}

/** 헤더 — 허브면 제목만, 도구 안이면 돌아가기와 탭까지 */
function md_sup_render_header( $app, $tab ) {
	$user = wp_get_current_user();
	$apps = md_sup_apps();
	$name = $app ? $apps[ $app ]['label'] : '직원 전용';
	?>
	<div class="mds-head">
		<div class="mds-head__top">
			<div>
				<span class="mds-head__eyebrow">
					<?php if ( $app ) : ?>
						<a class="mds-head__back" href="<?php echo esc_url( md_sup_url( array( "app" => "" ) ) ); ?>">← 직원 전용</a>
					<?php else : ?>
						문치과병원
					<?php endif; ?>
				</span>
				<h1><?php echo esc_html( $name ); ?></h1>
			</div>
			<div class="mds-head__me">
				<span class="mds-head__name"><?php echo esc_html( $user->display_name ); ?></span>
				<a class="mds-head__out" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a>
			</div>
		</div>

		<?php if ( 'stock' === $app ) : ?>
			<nav class="mds-tabs" aria-label="재고관리 메뉴">
				<?php foreach ( md_sup_tabs() as $key => $t ) :
					if ( $t['manage'] && ! md_sup_can_manage() ) { continue; } ?>
					<a class="mds-tab<?php echo $tab === $key ? ' is-on' : ''; ?>"
					   href="<?php echo esc_url( md_sup_url( array( 'app' => 'stock', 'tab' => $key ) ) ); ?>"
					   <?php echo $tab === $key ? 'aria-current="page"' : ''; ?>>
						<span aria-hidden="true"><?php echo esc_html( $t['icon'] ); ?></span><?php echo esc_html( $t['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
	<?php
}

/** 허브 — 무엇을 할지 고르는 첫 화면 */
function md_sup_render_hub() {
	$pending = md_sup_can_manage() ? count( md_sup_requests( array( 'status' => 'pending', 'limit' => 99 ) ) ) : 0;
	$recent  = md_sup_notice_recent( 3 );
	?>
	<div class="mds-apps">
		<?php foreach ( md_sup_apps() as $key => $a ) : ?>
			<a class="mds-app" href="<?php echo esc_url( md_sup_url( array( 'app' => $key ) ) ); ?>">
				<span class="mds-app__icon" aria-hidden="true"><?php echo esc_html( $a['icon'] ); ?></span>
				<span class="mds-app__body">
					<span class="mds-app__label">
						<?php echo esc_html( $a['label'] ); ?>
						<?php if ( 'stock' === $key && $pending ) : ?>
							<span class="mds-app__badge"><?php echo (int) $pending; ?>건 대기</span>
						<?php endif; ?>
					</span>
					<span class="mds-app__desc"><?php echo esc_html( $a['desc'] ); ?></span>
				</span>
				<span class="mds-app__go" aria-hidden="true">→</span>
			</a>
		<?php endforeach; ?>
	</div>

	<?php if ( $recent ) : ?>
		<h2 class="mds-h2">최근 공지</h2>
		<ul class="mds-notices">
			<?php foreach ( $recent as $n ) : ?>
				<li class="mds-noticeitem<?php echo $n->pinned ? ' is-pinned' : ''; ?>">
					<a href="<?php echo esc_url( md_sup_url( array( 'app' => 'notice', 'n' => (int) $n->id ) ) ); ?>">
						<span class="mds-noticeitem__title">
							<?php if ( $n->pinned ) : ?><span class="mds-pin">고정</span><?php endif; ?>
							<?php echo esc_html( $n->title ); ?>
						</span>
						<span class="mds-noticeitem__meta"><?php echo esc_html( mysql2date( 'Y-m-d', $n->created_at ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif;
}

/**
 * 팀 선택 — 3행 × 5열. 병원 층 배치를 그대로 옮긴 것이라
 * 직원분들이 자기 자리를 눈으로 찾는다.
 *
 * @param array $teams   팀 목록
 * @param int   $current 지금 고른 팀 (0이면 아직 안 고름)
 */
function md_sup_render_team_picker( $teams, $current ) {
	/* 팀을 고른 뒤에는 접어 둔다. 15칸이 계속 자리를 차지하면
	 * 정작 봐야 할 품목표가 화면 밖으로 밀린다.
	 * <details> 라 자바스크립트 없이도 펼쳐진다. */
	?>
	<details class="mds-teams" <?php echo $current ? '' : 'open'; ?>>
		<summary class="mds-teams__sum">
			<?php if ( $current ) : ?>
				<span class="mds-teams__label">신청 팀</span>
				<span class="mds-teams__now"><?php echo esc_html( md_sup_team_name( $current ) ); ?></span>
				<span class="mds-teams__change">팀 바꾸기</span>
			<?php else : ?>
				<span class="mds-teams__ask">어느 팀에서 신청하시나요?</span>
			<?php endif; ?>
		</summary>
		<?php if ( ! $current ) : ?>
			<p class="mds-hint" style="margin-top:10px">한 번 고르면 다음부터 기억합니다.</p>
		<?php endif; ?>
		<div class="mds-teamgrid">
			<?php foreach ( $teams as $tm ) : ?>
				<a class="mds-teambtn<?php echo (int) $tm->id === $current ? ' is-on' : ''; ?>"
				   href="<?php echo esc_url( md_sup_url( array( 'tab' => 'request', 'team' => (int) $tm->id ) ) ); ?>">
					<?php echo esc_html( $tm->name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</details>
	<?php
}

/** ① 요청 */
function md_sup_render_request() {
	$my_team = md_sup_my_team_id();
	$team_id = isset( $_GET['team'] ) ? (int) $_GET['team'] : $my_team;
	$search  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$cat     = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
	$teams   = md_sup_teams();

	$vendor = isset( $_GET['vendor'] ) ? sanitize_text_field( wp_unslash( $_GET['vendor'] ) ) : '';

	/* 공용 계정이라 소속 팀이 없다. 주소에 없으면 지난번에 고른 팀을 쓴다. */
	if ( ! $team_id && isset( $_COOKIE['md_sup_team'] ) ) { $team_id = (int) $_COOKIE['md_sup_team']; }

	/* 존재하는 팀인지 확인 — 쿠키가 낡았거나 팀이 지워졌을 수 있다 */
	$valid = false;
	foreach ( $teams as $tm ) { if ( (int) $tm->id === $team_id ) { $valid = true; break; } }
	if ( ! $valid ) { $team_id = 0; }

	/* 팀을 아직 안 골랐으면 팀 선택만 보여준다. 잘못된 팀으로 신청하면
	 * 그 팀 사용량으로 잡히므로, 고르기 전에는 품목을 띄우지 않는다. */
	if ( ! $team_id ) {
		md_sup_render_team_picker( $teams, 0 );
		return;
	}
	md_sup_render_team_picker( $teams, $team_id );

	/* 필터에 걸린 전 품목을 다 보여준다. 평균·최근수령은 품목마다 조회하지 않고
	 * 팀 단위로 한 번에 받아 온다 — 그러지 않으면 568개 × 2질의가 된다. */
	$items    = md_sup_items( array( 'search' => $search, 'category' => $cat, 'vendor' => $vendor ) );
	$avg_map  = md_sup_avg_map( $team_id );
	$last_map = md_sup_last_map( $team_id );

	/* 우리 팀이 실제로 쓰는 품목을 맨 위로. 568개 중 우리가 만지는 건
	 * 보통 수십 개뿐이라, 이 정렬 하나로 찾는 시간이 확 줄어든다. */
	$used = array();
	$rest = array();
	foreach ( $items as $it ) {
		if ( isset( $avg_map[ $it->id ] ) && $avg_map[ $it->id ] > 0 ) { $used[] = $it; } else { $rest[] = $it; }
	}
	usort( $used, function ( $a, $b ) use ( $avg_map ) {
		return $avg_map[ $b->id ] <=> $avg_map[ $a->id ];
	} );
	$items      = array_merge( $used, $rest );
	$used_count = count( $used );

	$hint = '총 ' . number_format( count( $items ) ) . '개 품목';
	$hint .= $used_count ? ' · 우리 팀이 쓰는 ' . $used_count . '개를 맨 위로 모았습니다.' : '.';
	$hint .= ' 필요한 것만 수량을 적으면 아래에 합계가 뜹니다.';
	?>
	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="request">
		<input type="hidden" name="team" value="<?php echo (int) $team_id; ?>">
		<label class="mds-field">
			<span>분류</span>
			<select name="cat">
				<option value="">전체</option>
				<?php foreach ( md_sup_categories() as $c ) : ?>
					<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $cat, $c ); ?>><?php echo esc_html( $c ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field">
			<span>거래처</span>
			<select name="vendor">
				<option value="">전체</option>
				<?php foreach ( md_sup_vendors() as $v ) : ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $vendor, $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field mds-field--grow">
			<span>품목 검색</span>
			<input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="품목명 · 코드 · 거래처">
		</label>
		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
		<?php if ( $search || $cat || $vendor ) : ?>
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'request', 'team' => $team_id ) ) ); ?>">필터 해제</a>
		<?php endif; ?>
	</form>

	<p class="mds-hint"><?php echo esc_html( $hint ); ?></p>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'request' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_request', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="request">
		<input type="hidden" name="team_id" value="<?php echo (int) $team_id; ?>">

		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead>
					<tr>
						<th>품목</th><th class="num">단가</th><th class="num">창고 재고</th>
						<th class="num">우리 팀 월평균</th><th>최근 수령</th><th class="num">신청 수량</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="6" class="mds-empty">해당하는 품목이 없습니다.</td></tr>
				<?php else : $i_row = 0; foreach ( $items as $it ) :
					$i_row++;
					$avg  = isset( $avg_map[ $it->id ] ) ? $avg_map[ $it->id ] : 0;
					$last = isset( $last_map[ $it->id ] ) ? $last_map[ $it->id ] : null;
					$low  = ( $it->min_stock > 0 && $it->stock <= $it->min_stock );
					?>
					<tr>
						<td class="mds-item">
							<b><?php echo esc_html( $it->name ); ?></b>
							<span class="mds-item__meta"><?php echo esc_html( $it->code . ' · ' . $it->vendor . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span>
						</td>
						<td class="num"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
						<td class="num<?php echo $low ? ' is-low' : ''; ?>">
							<?php echo esc_html( number_format( (int) $it->stock ) ); ?>
							<?php if ( $low ) : ?><span class="mds-flag">부족</span><?php endif; ?>
						</td>
						<td class="num mds-avg" data-avg="<?php echo esc_attr( $avg ); ?>"><?php echo esc_html( $avg > 0 ? $avg : '—' ); ?></td>
						<td class="mds-last">
							<?php if ( $last ) : ?>
								<?php echo esc_html( mysql2date( 'n/j', $last->created_at ) . ' · ' . (int) $last->qty ); ?>
							<?php else : ?>—<?php endif; ?>
						</td>
						<td class="num">
							<span class="mds-stepper">
								<button type="button" class="mds-step" data-step="-1" aria-label="수량 줄이기" tabindex="-1">−</button>
								<input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
								       name="qty[<?php echo (int) $it->id; ?>]" value=""
								       data-avg="<?php echo esc_attr( $avg ); ?>"
								       data-price="<?php echo (int) $it->price; ?>"
								       aria-label="<?php echo esc_attr( $it->name . ' 신청 수량' ); ?>">
								<button type="button" class="mds-step" data-step="1" aria-label="수량 늘리기" tabindex="-1">+</button>
							</span>
							<input class="mds-reason" type="text" name="reason[<?php echo (int) $it->id; ?>]"
							       placeholder="평균보다 많은 이유" hidden
							       aria-label="<?php echo esc_attr( $it->name . ' 초과 신청 사유' ); ?>">
						</td>
					</tr>
					<?php if ( $used_count && $i_row === $used_count ) : ?>
						<tr class="mds-divider"><td colspan="6">여기부터는 우리 팀이 최근 3개월간 받아가지 않은 품목입니다</td></tr>
					<?php endif; ?>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>

		<div class="mds-submit" id="mds-submit">
			<label class="mds-check"><input type="checkbox" name="urgent" value="1"> 긴급 (오늘 필요)</label>
			<input class="mds-note" type="text" name="note" placeholder="담당자에게 전할 메모 (선택)" maxlength="200">
			<button type="submit" class="mds-btn mds-btn--fill">
				<?php echo esc_html( md_sup_team_name( $team_id ) ); ?> 이름으로 신청
			</button>
		</div>

		<?php /* 고정 바 — 수량을 하나라도 적으면 화면 아래에 붙어 따라온다.
		         568줄을 스크롤해 내려가 제출 버튼을 찾지 않아도 되게. */ ?>
		<div class="mds-bar" id="mds-bar" hidden>
			<div class="mds-bar__inner">
				<span class="mds-bar__team"><?php echo esc_html( md_sup_team_name( $team_id ) ); ?></span>
				<span class="mds-bar__sum">
					<b id="mds-bar-count">0</b>개 품목
					<span class="mds-bar__won">약 <b id="mds-bar-total">0</b>원</span>
				</span>
				<button type="submit" class="mds-btn mds-btn--fill">신청하기</button>
			</div>
		</div>
	</form>

	<?php
	$mine = md_sup_requests( array( 'team_id' => $team_id, 'limit' => 8 ) );
	if ( $mine ) : ?>
		<h2 class="mds-h2">우리 팀 최근 신청</h2>
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>신청일</th><th>품목 수</th><th>상태</th><th>메모</th></tr></thead>
				<tbody>
				<?php foreach ( $mine as $r ) : ?>
					<tr>
						<td><?php echo esc_html( mysql2date( 'Y-m-d H:i', $r->created_at ) ); ?><?php echo $r->urgent ? ' <span class="mds-flag">긴급</span>' : ''; ?></td>
						<td><?php echo (int) $r->line_count; ?>건</td>
						<td><span class="mds-status is-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( md_sup_status_label( $r->status ) ); ?></span></td>
						<td class="mds-memo"><?php echo esc_html( $r->note ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif;
}

/** ② 통계 */
function md_sup_render_stats() {
	$ym      = isset( $_GET['ym'] ) ? sanitize_text_field( wp_unslash( $_GET['ym'] ) ) : current_time( 'Y-m' );
	$my_team = md_sup_my_team_id();
	/* 볼 팀 — 지정 안 했으면 내 소속 팀. 전체를 보려면 0 */
	$view    = isset( $_GET['team'] ) ? (int) $_GET['team'] : $my_team;
	$teams   = md_sup_teams();

	$usage = md_sup_team_usage( $ym );
	$trend = md_sup_monthly_trend( $view, 6 );
	$top   = md_sup_top_items( $ym, $view, 15 );

	$total = 0;
	$max   = 1;
	$mine  = 0;
	$rank  = 0;
	$i     = 0;
	foreach ( $usage as $u ) {
		$i++;
		$total += (int) $u->amount;
		$max    = max( $max, (int) $u->amount );
		if ( $view && (int) $u->team_id === $view ) { $mine = (int) $u->amount; $rank = $i; }
	}
	$tmax = 1;
	foreach ( $trend as $v ) { $tmax = max( $tmax, (int) $v ); }

	/* 전월 대비 — 추이 배열의 마지막 두 달을 쓴다 */
	$tvals = array_values( $trend );
	$prev  = count( $tvals ) >= 2 ? (int) $tvals[ count( $tvals ) - 2 ] : 0;
	$curr  = count( $tvals ) >= 1 ? (int) $tvals[ count( $tvals ) - 1 ] : 0;
	$delta = ( $prev > 0 ) ? round( ( $curr - $prev ) / $prev * 100 ) : null;

	$view_name = $view ? md_sup_team_name( $view ) : '전체';
	?>
	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="stats">
		<label class="mds-field">
			<span>기준 월</span>
			<select name="ym" onchange="this.form.submit()">
				<?php for ( $i = 0; $i < 12; $i++ ) :
					$v = gmdate( 'Y-m', strtotime( "-$i months", current_time( 'timestamp' ) ) ); ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $ym, $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endfor; ?>
			</select>
		</label>
		<label class="mds-field">
			<span>팀</span>
			<select name="team" onchange="this.form.submit()">
				<option value="0" <?php selected( $view, 0 ); ?>>전체 팀</option>
				<?php foreach ( $teams as $tm ) : ?>
					<option value="<?php echo (int) $tm->id; ?>" <?php selected( $view, $tm->id ); ?>><?php echo esc_html( $tm->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<noscript><button type="submit" class="mds-btn mds-btn--ghost">보기</button></noscript>
	</form>

	<div class="mds-kpis">
		<div class="mds-kpi">
			<span class="n"><?php echo esc_html( number_format( $view ? $mine : $total ) ); ?></span>
			<span class="k"><?php echo esc_html( $view_name . ' · ' . $ym . ' 사용액 (원)' ); ?></span>
		</div>
		<div class="mds-kpi">
			<span class="n"><?php echo null === $delta ? '—' : esc_html( ( $delta > 0 ? '+' : '' ) . $delta . '%' ); ?></span>
			<span class="k">전월 대비</span>
		</div>
		<?php if ( $view && $rank ) : ?>
			<div class="mds-kpi"><span class="n"><?php echo (int) $rank; ?>위</span><span class="k">전체 <?php echo count( $usage ); ?>개 팀 중 사용액 순위</span></div>
		<?php else : ?>
			<div class="mds-kpi"><span class="n"><?php echo esc_html( count( $usage ) ); ?></span><span class="k">사용 팀 수</span></div>
		<?php endif; ?>
		<div class="mds-kpi">
			<span class="n"><?php echo esc_html( number_format( $total ) ); ?></span>
			<span class="k">전체 팀 합계 (원)</span>
		</div>
	</div>

	<h2 class="mds-h2">팀별 사용액</h2>
	<p class="mds-hint">모든 팀이 서로의 숫자를 봅니다. 팀마다 진료 건수가 다르니 절대액만으로 판단하지 마세요.</p>
	<?php if ( empty( $usage ) ) : ?>
		<p class="mds-empty">이 달에 출고된 기록이 아직 없습니다.</p>
	<?php else : ?>
		<div class="mds-bars">
			<?php foreach ( $usage as $u ) :
				$w = round( (int) $u->amount / $max * 100 );
				$is_me = ( $view && (int) $u->team_id === $view ); ?>
				<div class="mds-bar<?php echo $is_me ? ' is-me' : ''; ?>">
					<span class="mds-bar__label"><?php echo esc_html( $u->team_name ? $u->team_name : '(팀 없음)' ); ?></span>
					<span class="mds-bar__track"><span class="mds-bar__fill" style="width:<?php echo (int) $w; ?>%"></span></span>
					<span class="mds-bar__val"><?php echo esc_html( number_format( (int) $u->amount ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<h2 class="mds-h2"><?php echo esc_html( $view_name ); ?> · 6개월 추이</h2>
	<div class="mds-trend">
		<?php foreach ( $trend as $m => $v ) :
			$h = max( 3, round( (int) $v / $tmax * 100 ) ); ?>
			<div class="mds-trend__col">
				<span class="mds-trend__bar" style="height:<?php echo (int) $h; ?>%" title="<?php echo esc_attr( number_format( (int) $v ) . '원' ); ?>"></span>
				<span class="mds-trend__v"><?php echo esc_html( $v > 0 ? round( $v / 10000 ) . '만' : '0' ); ?></span>
				<span class="mds-trend__m"><?php echo esc_html( substr( $m, 5, 2 ) ); ?>월</span>
			</div>
		<?php endforeach; ?>
	</div>

	<h2 class="mds-h2"><?php echo esc_html( $view_name . ' · ' . $ym ); ?> 품목별 사용 내역</h2>
	<p class="mds-hint">금액이 큰 순서입니다. 무엇에 비용이 쓰이는지 여기서 확인하세요.</p>
	<div class="mds-tablewrap">
		<table class="mds-table">
			<thead><tr><th>품목</th><th class="num">단가</th><th class="num">수량</th><th class="num">금액</th><th class="num">비중</th></tr></thead>
			<tbody>
			<?php if ( empty( $top ) ) : ?>
				<tr><td colspan="5" class="mds-empty">이 기간에 출고된 기록이 없습니다.</td></tr>
			<?php else :
				$base = $view ? max( 1, $mine ) : max( 1, $total );
				foreach ( $top as $r ) : ?>
				<tr>
					<td class="mds-item"><b><?php echo esc_html( $r->name ); ?></b></td>
					<td class="num"><?php echo esc_html( number_format( (int) $r->price ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format( (int) $r->qty ) . ( $r->unit ? ' ' . $r->unit : '' ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format( (int) $r->amount ) ); ?></td>
					<td class="num"><?php echo esc_html( round( (int) $r->amount / $base * 100 ) . '%' ); ?></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/** ③ 반출관리 */
function md_sup_render_manage() {
	$pending = md_sup_requests( array( 'status' => 'pending', 'limit' => 30 ) );
	?>
	<h2 class="mds-h2">처리 대기 신청 <span class="mds-count"><?php echo count( $pending ); ?></span></h2>
	<?php if ( empty( $pending ) ) : ?>
		<p class="mds-empty">대기 중인 신청이 없습니다.</p>
	<?php else : foreach ( $pending as $r ) :
		$lines = md_sup_request_lines( $r->id ); ?>
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
							$stock = md_sup_stock( $ln->item_id ); ?>
							<tr>
								<td class="mds-item"><b><?php echo esc_html( $ln->name ); ?></b><span class="mds-item__meta"><?php echo esc_html( $ln->code ); ?></span></td>
								<td class="num<?php echo $stock < $ln->qty_req ? ' is-low' : ''; ?>"><?php echo esc_html( number_format( $stock ) ); ?></td>
								<td class="num"><?php echo (int) $ln->qty_req; ?></td>
								<td class="mds-memo"><?php echo esc_html( $ln->over_reason ); ?></td>
								<td class="num"><input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
									name="out[<?php echo (int) $ln->id; ?>]" value="<?php echo (int) $ln->qty_req; ?>"
									aria-label="<?php echo esc_attr( $ln->name . ' 출고 수량' ); ?>"></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="mds-submit">
					<button type="submit" class="mds-btn mds-btn--fill">출고 확정</button>
				</div>
			</form>
			<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'manage' ) ) ); ?>" class="mds-reject">
				<?php wp_nonce_field( 'md_sup_reject', 'md_sup_nonce' ); ?>
				<input type="hidden" name="md_sup_action" value="reject">
				<input type="hidden" name="req_id" value="<?php echo (int) $r->id; ?>">
				<input type="text" name="reject_reason" placeholder="반려 사유" maxlength="200" required>
				<button type="submit" class="mds-btn mds-btn--ghost">반려</button>
			</form>
		</article>
	<?php endforeach; endif;
}

/** ④ 입고 */
function md_sup_render_inbound() {
	$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$vendor = isset( $_GET['vendor'] ) ? sanitize_text_field( wp_unslash( $_GET['vendor'] ) ) : '';
	$items  = ( '' === $search && '' === $vendor )
		? md_sup_items( array( 'low_only' => true ) )
		: md_sup_items( array( 'search' => $search, 'vendor' => $vendor ) );

	$vendors = md_sup_vendors();
	?>
	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="inbound">
		<label class="mds-field">
			<span>거래처</span>
			<select name="vendor">
				<option value="">전체</option>
				<?php foreach ( $vendors as $v ) : ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $vendor, $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field mds-field--grow">
			<span>품목 검색</span>
			<input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="품목명 · 코드">
		</label>
		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
	</form>

	<p class="mds-hint">
		<?php echo ( '' === $search && '' === $vendor )
			? '적정재고 이하로 떨어진 품목입니다. 들어온 수량을 적고 저장하세요.'
			: esc_html( '검색 결과 ' . count( $items ) . '건입니다.' ); ?>
	</p>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'inbound' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_inbound', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="inbound">
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>품목</th><th>거래처</th><th class="num">단가</th><th class="num">현재고</th><th class="num">적정</th><th class="num">입고 수량</th></tr></thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="6" class="mds-empty">부족한 품목이 없습니다.</td></tr>
				<?php else : foreach ( $items as $it ) : ?>
					<tr>
						<td class="mds-item"><b><?php echo esc_html( $it->name ); ?></b><span class="mds-item__meta"><?php echo esc_html( $it->code . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span></td>
						<td><?php echo esc_html( $it->vendor ); ?></td>
						<td class="num"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
						<td class="num<?php echo ( $it->min_stock > 0 && $it->stock <= $it->min_stock ) ? ' is-low' : ''; ?>"><?php echo esc_html( number_format( (int) $it->stock ) ); ?></td>
						<td class="num"><?php echo (int) $it->min_stock; ?></td>
						<td class="num"><input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
							name="inqty[<?php echo (int) $it->id; ?>]" value=""
							aria-label="<?php echo esc_attr( $it->name . ' 입고 수량' ); ?>"></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<div class="mds-submit"><button type="submit" class="mds-btn mds-btn--fill">입고 저장</button></div>
	</form>
	<?php
}

/** ⑤ 재고 · 실사 */
function md_sup_render_inventory() {
	$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$cat    = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
	$low    = isset( $_GET['low'] ) && '1' === $_GET['low'];
	$items  = md_sup_items( array( 'search' => $search, 'category' => $cat, 'low_only' => $low ) );
	?>
	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="inventory">
		<label class="mds-field">
			<span>분류</span>
			<select name="cat">
				<option value="">전체</option>
				<?php foreach ( md_sup_categories() as $c ) : ?>
					<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $cat, $c ); ?>><?php echo esc_html( $c ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field mds-field--grow">
			<span>품목 검색</span>
			<input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="품목명 · 코드 · 거래처">
		</label>
		<label class="mds-check"><input type="checkbox" name="low" value="1" <?php checked( $low ); ?>> 부족한 것만</label>
		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
	</form>

	<p class="mds-hint">
		센 수량을 「실사」 칸에 적고 저장하면 장부와의 차이가 조정 기록으로 남습니다.
		처음 시작할 때 여기에 현재 재고를 적어 넣으시면 됩니다.
	</p>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'inventory' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_adjust', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="adjust">
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>품목</th><th>거래처</th><th class="num">단가</th><th class="num">장부</th><th class="num">적정</th><th class="num">실사</th></tr></thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="6" class="mds-empty">해당하는 품목이 없습니다.</td></tr>
				<?php else : foreach ( $items as $it ) : ?>
					<tr>
						<td class="mds-item"><b><?php echo esc_html( $it->name ); ?></b><span class="mds-item__meta"><?php echo esc_html( $it->code . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span></td>
						<td><?php echo esc_html( $it->vendor ); ?></td>
						<td class="num"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
						<td class="num<?php echo ( $it->min_stock > 0 && $it->stock <= $it->min_stock ) ? ' is-low' : ''; ?>"><?php echo esc_html( number_format( (int) $it->stock ) ); ?></td>
						<td class="num"><?php echo (int) $it->min_stock; ?></td>
						<td class="num"><input class="mds-qty" type="number" step="1" inputmode="numeric"
							name="count[<?php echo (int) $it->id; ?>]" value=""
							aria-label="<?php echo esc_attr( $it->name . ' 실사 수량' ); ?>"></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<div class="mds-submit"><button type="submit" class="mds-btn mds-btn--fill">실사 반영</button></div>
	</form>
	<?php
}

/** 전체 페이지 진입점 */
function md_sup_render_page() {
	if ( ! is_user_logged_in() ) { md_sup_render_login(); return; }
	if ( ! md_sup_can_use() )    { md_sup_render_denied(); return; }

	$app = md_sup_current_app();
	$tab = md_sup_current_tab();
	md_sup_render_header( $app, $tab );

	if ( isset( $_GET['msg'] ) ) {
		echo md_sup_notice( sanitize_key( wp_unslash( $_GET['msg'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	if ( 'notice' === $app ) {
		md_sup_render_notice();
	} elseif ( 'stock' === $app ) {
		switch ( $tab ) {
			case 'stats':     md_sup_render_stats();     break;
			case 'manage':    md_sup_render_manage();    break;
			case 'inbound':   md_sup_render_inbound();   break;
			case 'inventory': md_sup_render_inventory(); break;
			default:          md_sup_render_request();   break;
		}
	} else {
		md_sup_render_hub();
	}

	/* 사이트 푸터를 감췄으니 필요한 안내만 여기서 */
	?>
	<div class="mds-foot">
		<span>한아의료재단 문치과병원 · 직원 전용</span>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">병원 홈페이지</a>
		<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a>
	</div>
	<?php
}
