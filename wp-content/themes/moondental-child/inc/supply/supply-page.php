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
		'items'     => array( 'label' => '품목·팀',  'icon' => '⚙️', 'manage' => true ),
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
 * 직원 전용은 여러 도구가 들어설 자리다.
 * 들어오면 무엇을 할지 먼저 고르고, 고른 뒤 그 도구로 들어간다.
 * 도구가 늘면 여기에 한 줄만 더하면 된다.
 */
function md_sup_apps() {
	return array(
		'stock' => array(
			'label' => '재료실',
			'icon'  => '📦',
			'desc'  => '재료 신청 · 우리 팀 사용량과 비용 · 입출고 관리',
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
 * 첫 화면으로 나가려면 'app' => '' 을 명시한다.
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
	return in_array( $slug, array( '재료실', '직원', 'staff', 'supply', '재고관리' ), true );
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

			/* 목록에 없어 직접 적은 품목 */
			$customs = array();
			$cn      = isset( $_POST['custom_name'] ) && is_array( $_POST['custom_name'] ) ? wp_unslash( $_POST['custom_name'] ) : array();
			$cq      = isset( $_POST['custom_qty'] ) && is_array( $_POST['custom_qty'] ) ? wp_unslash( $_POST['custom_qty'] ) : array();
			foreach ( $cn as $k => $nm ) {
				$nm = trim( (string) $nm );
				if ( '' === $nm ) { continue; }
				$q         = isset( $cq[ $k ] ) ? (int) $cq[ $k ] : 0;
				$customs[] = array( 'name' => $nm, 'qty' => $q > 0 ? $q : 1 );
			}

			if ( empty( $lines ) && empty( $customs ) ) {
				$redirect = add_query_arg( 'msg', 'empty', $redirect );
				break;
			}

			$res = md_sup_create_request(
				$team_id,
				$lines,
				isset( $_POST['urgent'] ) ? 1 : 0,
				isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '',
				$customs
			);
			$redirect = add_query_arg( array( 'team' => $team_id, 'msg' => is_wp_error( $res ) ? 'error' : 'sent', 'req' => is_wp_error( $res ) ? 0 : (int) $res ), $redirect );
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

		/* 품목 등록 · 수정 */
		case 'item':
			if ( ! md_sup_can_manage() ) { break; }
			$res = md_sup_item_save(
				isset( $_POST['item_id'] ) ? (int) $_POST['item_id'] : 0,
				array(
					'name'      => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
					'vendor'    => isset( $_POST['vendor'] ) ? wp_unslash( $_POST['vendor'] ) : '',
					'unit'      => isset( $_POST['unit'] ) ? wp_unslash( $_POST['unit'] ) : '',
					'category'  => isset( $_POST['category'] ) ? wp_unslash( $_POST['category'] ) : '',
					'price'     => isset( $_POST['price'] ) ? $_POST['price'] : 0,
					'min_stock' => isset( $_POST['min_stock'] ) ? $_POST['min_stock'] : 0,
				)
			);
			$redirect = is_wp_error( $res )
				? add_query_arg( array( 'tab' => 'items', 'err' => $res->get_error_message() ), md_sup_url() )
				: add_query_arg( array( 'tab' => 'items', 'msg' => 'item_saved' ), md_sup_url() );
			break;

		/* 팀 이름·사용 여부 저장 + 새 팀 추가 */
		case 'team':
			if ( ! md_sup_can_manage() ) { break; }
			$names = isset( $_POST['team_name'] ) && is_array( $_POST['team_name'] ) ? wp_unslash( $_POST['team_name'] ) : array();
			$ons   = isset( $_POST['team_on'] ) && is_array( $_POST['team_on'] ) ? wp_unslash( $_POST['team_on'] ) : array();

			foreach ( $names as $tid => $nm ) {
				$tid = (int) $tid;
				md_sup_team_save( $tid, $nm );
				md_sup_team_archive( $tid, isset( $ons[ $tid ] ) );
			}
			$new = isset( $_POST['team_new'] ) ? trim( (string) wp_unslash( $_POST['team_new'] ) ) : '';
			if ( '' !== $new ) { md_sup_team_save( 0, $new, 9999 ); }

			$redirect = add_query_arg( array( 'tab' => 'items', 'msg' => 'team_saved' ), md_sup_url() );
			break;
	}

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'template_redirect', 'md_sup_handle_post', 1 );

/**
 * 즐겨찾기 토글 · CSV 내보내기 — 화면을 그리기 전에 처리한다.
 * 둘 다 GET 이라 여기서 받는다. 토글은 nonce 로 위조를 막는다.
 */
function md_sup_handle_get() {
	if ( ! md_sup_is_page() || ! md_sup_can_use() ) { return; }

	/* 즐겨찾기 */
	if ( isset( $_GET['fav'] ) && isset( $_GET['_wpnonce'] ) ) {
		$item = (int) $_GET['fav'];
		$team = isset( $_GET['team'] ) ? (int) $_GET['team'] : md_sup_my_team_id();
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'md_sup_fav_' . $item ) ) {
			md_sup_fav_toggle( $team, $item );
		}
		$back = remove_query_arg( array( 'fav', '_wpnonce' ) );
		wp_safe_redirect( $back . '#i' . $item );
		exit;
	}

	/* 품목 감추기 · 되살리기 */
	if ( isset( $_GET['toggle'] ) && isset( $_GET['_wpnonce'] ) && md_sup_can_manage() ) {
		$id = (int) $_GET['toggle'];
		if ( wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'md_sup_toggle_' . $id ) ) {
			$it = md_sup_item( $id );
			if ( $it ) { md_sup_item_archive( $id, ! (int) $it->active ); }
		}
		wp_safe_redirect( remove_query_arg( array( 'toggle', '_wpnonce' ) ) );
		exit;
	}

	/* CSV 내보내기 */
	if ( isset( $_GET['export'] ) ) {
		$what = sanitize_key( wp_unslash( $_GET['export'] ) );
		if ( 'usage' === $what ) { md_sup_export_usage(); }
		if ( 'stock' === $what && md_sup_can_manage() ) { md_sup_export_stock(); }
	}
}
add_action( 'template_redirect', 'md_sup_handle_get', 2 );

/** CSV 한 줄 — 엑셀이 한글을 깨뜨리지 않게 BOM 을 앞에 붙인다 */
function md_sup_csv_start( $filename ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	echo "\xEF\xBB\xBF"; // UTF-8 BOM · 없으면 엑셀에서 한글이 깨진다
}

function md_sup_csv_row( $cols ) {
	$out = array();
	foreach ( $cols as $c ) {
		$c = (string) $c;
		$out[] = '"' . str_replace( '"', '""', $c ) . '"';
	}
	echo implode( ',', $out ) . "\r\n";
}

/** 사용량 CSV — 통계 화면에서 내려받는다 */
function md_sup_export_usage() {
	$ym   = isset( $_GET['ym'] ) ? sanitize_text_field( wp_unslash( $_GET['ym'] ) ) : current_time( 'Y-m' );
	$view = isset( $_GET['team'] ) ? (int) $_GET['team'] : 0;

	md_sup_csv_start( 'moondental-usage-' . $ym . '.csv' );
	md_sup_csv_row( array( '문치과병원 재료 사용 내역', $ym, $view ? md_sup_team_name( $view ) : '전체 팀' ) );
	md_sup_csv_row( array() );

	md_sup_csv_row( array( '팀', '수량', '금액(원)' ) );
	foreach ( md_sup_team_usage( $ym ) as $u ) {
		md_sup_csv_row( array( $u->team_name, (int) $u->qty, (int) $u->amount ) );
	}

	md_sup_csv_row( array() );
	md_sup_csv_row( array( '품목', '단위', '단가', '수량', '금액(원)' ) );
	foreach ( md_sup_top_items( $ym, $view, 500 ) as $r ) {
		md_sup_csv_row( array( $r->name, $r->unit, (int) $r->price, (int) $r->qty, (int) $r->amount ) );
	}
	exit;
}

/** 재고 CSV — 실사용 인쇄나 엑셀 정리에 쓴다 */
function md_sup_export_stock() {
	md_sup_csv_start( 'moondental-stock-' . current_time( 'Y-m-d' ) . '.csv' );
	md_sup_csv_row( array( '품목코드', '품목명', '거래처', '단위', '단가', '현재고', '적정재고', '부족' ) );
	foreach ( md_sup_items() as $it ) {
		$low = ( $it->min_stock > 0 && $it->stock <= $it->min_stock ) ? 'O' : '';
		md_sup_csv_row( array(
			$it->code, $it->name, $it->vendor, $it->unit,
			(int) $it->price, (int) $it->stock, (int) $it->min_stock, $low,
		) );
	}
	exit;
}

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
		'item_saved' => array( 'ok',   '품목을 저장했습니다.' ),
		'team_saved' => array( 'ok',   '팀을 저장했습니다.' ),
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
			<span class="mds-gate__eyebrow">한아의료재단 문치과병원</span>
			<h1>직원 전용</h1>
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
			<h1>직원 전용 페이지 이용 권한이 없습니다</h1>
			<p>
				<?php echo esc_html( wp_get_current_user()->display_name ); ?> 님 계정에는 이 페이지 이용 권한이 없습니다.
				경영지원실에 권한 부여를 요청해 주세요.
			</p>
			<p class="mds-gate__help"><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a></p>
		</div>
	</div>
	<?php
}

/** 헤더 — 첫 화면이면 제목만, 도구 안이면 돌아가기와 탭까지 */
function md_sup_render_header( $app, $tab ) {
	$user = wp_get_current_user();
	$apps = md_sup_apps();
	?>
	<div class="mds-head">
		<div class="mds-head__top">
			<div>
				<span class="mds-head__eyebrow">
					<?php if ( $app ) : ?>
						<a class="mds-head__back" href="<?php echo esc_url( md_sup_url( array( 'app' => '' ) ) ); ?>">← 직원 전용</a>
					<?php else : ?>
						한아의료재단 문치과병원
					<?php endif; ?>
				</span>
				<h1><?php echo esc_html( $app ? $apps[ $app ]['label'] : '직원 전용' ); ?></h1>
			</div>
			<div class="mds-head__me">
				<span class="mds-head__name"><?php echo esc_html( $user->display_name ); ?></span>
				<a class="mds-head__out" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">로그아웃</a>
			</div>
		</div>

		<?php if ( 'stock' === $app ) : ?>
			<nav class="mds-tabs" aria-label="재료실 메뉴">
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

/** 첫 화면 — 어떤 도구로 들어갈지 고른다 */
function md_sup_render_hub() {
	$pending = md_sup_can_manage() ? count( md_sup_requests( array( 'status' => 'pending', 'limit' => 99 ) ) ) : 0;
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
	<?php
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

	/* 방금 신청했다면 무엇을 넣었는지 먼저 보여준다 */
	if ( isset( $_GET['req'] ) && (int) $_GET['req'] > 0 ) { md_sup_render_sent_summary( (int) $_GET['req'] ); }

	/* 필터에 걸린 전 품목을 다 보여준다. 평균·최근수령은 품목마다 조회하지 않고
	 * 팀 단위로 한 번에 받아 온다 — 그러지 않으면 568개 × 2질의가 된다. */
	$items    = md_sup_items( array( 'search' => $search, 'category' => $cat, 'vendor' => $vendor ) );
	$avg_map  = md_sup_avg_map( $team_id );
	$last_map = md_sup_last_map( $team_id );

	/* 지난 신청 그대로 다시 담기 — 수량 칸을 미리 채운다 */
	$prefill = array();
	if ( isset( $_GET['repeat'] ) ) {
		$prefill = md_sup_request_qty_map( (int) $_GET['repeat'] );
	}

	$favs = array_flip( md_sup_fav_ids( $team_id ) );

	/* 정렬 · 즐겨찾기 → 우리 팀이 쓰는 것 → 나머지.
	 * 568개 중 실제로 만지는 건 보통 수십 개뿐이라, 이 정렬이 찾는 시간을 좌우한다. */
	$fav = array();
	$used = array();
	$rest = array();
	foreach ( $items as $it ) {
		if ( isset( $favs[ $it->id ] ) )                                   { $fav[]  = $it; }
		elseif ( isset( $avg_map[ $it->id ] ) && $avg_map[ $it->id ] > 0 )  { $used[] = $it; }
		else                                                               { $rest[] = $it; }
	}
	usort( $used, function ( $a, $b ) use ( $avg_map ) {
		return $avg_map[ $b->id ] <=> $avg_map[ $a->id ];
	} );
	$items      = array_merge( $fav, $used, $rest );
	$fav_count  = count( $fav );
	$used_count = count( $used );

	$hint = '총 ' . number_format( count( $items ) ) . '개 품목';
	if ( $fav_count )  { $hint .= ' · 즐겨찾기 ' . $fav_count . '개'; }
	if ( $used_count ) { $hint .= ' · 우리 팀이 쓰는 ' . $used_count . '개'; }
	$hint .= '를 맨 위로 모았습니다. 별표를 눌러 즐겨찾기에 넣을 수 있습니다.';
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

	<?php /* 즉시 검색 — 이미 화면에 있는 줄을 타이핑하는 대로 걸러낸다.
	         서버 검색(위 「찾기」)과 달리 새로 고치지 않는다. JS 가 없으면 감춰진다. */ ?>
	<div class="mds-quick" hidden>
		<input type="search" id="mds-quick" placeholder="빠른 검색 — 품목명을 입력하면 바로 걸러집니다" aria-label="화면 안에서 품목 빠르게 찾기">
		<span class="mds-quick__count" id="mds-quick-count"></span>
	</div>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'request' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_request', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="request">
		<input type="hidden" name="team_id" value="<?php echo (int) $team_id; ?>">

		<div class="mds-tablewrap">
			<table class="mds-table mds-table--items">
				<thead>
					<tr>
						<th class="mds-th-fav"><span class="mds-sr">즐겨찾기</span></th>
						<th>품목</th><th class="num">단가</th><th class="num">창고 재고</th>
						<th class="num">우리 팀 월평균</th><th>최근 수령</th><th class="num">신청 수량</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="7" class="mds-empty">해당하는 품목이 없습니다.</td></tr>
				<?php else : $i_row = 0; foreach ( $items as $it ) :
					$i_row++;
					$avg    = isset( $avg_map[ $it->id ] ) ? $avg_map[ $it->id ] : 0;
					$last   = isset( $last_map[ $it->id ] ) ? $last_map[ $it->id ] : null;
					$low    = ( $it->min_stock > 0 && $it->stock <= $it->min_stock );
					$is_fav = isset( $favs[ $it->id ] );
					$pre    = isset( $prefill[ $it->id ] ) ? (int) $prefill[ $it->id ] : '';
					/* 즉시 검색이 훑을 문자열 — 소문자로 미리 만들어 둔다 */
					$hay    = mb_strtolower( $it->name . ' ' . $it->code . ' ' . $it->vendor . ' ' . $it->category );
					?>
					<tr id="i<?php echo (int) $it->id; ?>" data-search="<?php echo esc_attr( $hay ); ?>">
						<td class="mds-td-fav">
							<a class="mds-fav<?php echo $is_fav ? ' is-on' : ''; ?>"
							   href="<?php echo esc_url( wp_nonce_url( md_sup_url( array( 'tab' => 'request', 'team' => $team_id, 'fav' => (int) $it->id ) ), 'md_sup_fav_' . (int) $it->id ) ); ?>"
							   title="<?php echo $is_fav ? '즐겨찾기에서 빼기' : '즐겨찾기에 넣기'; ?>"
							   aria-label="<?php echo esc_attr( $it->name . ( $is_fav ? ' 즐겨찾기 해제' : ' 즐겨찾기' ) ); ?>">
								<?php echo $is_fav ? '★' : '☆'; ?>
							</a>
						</td>
						<td class="mds-item" data-label="품목">
							<b><?php echo esc_html( $it->name ); ?></b>
							<span class="mds-item__meta"><?php echo esc_html( $it->code . ' · ' . $it->vendor . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span>
						</td>
						<td class="num" data-label="단가"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
						<td class="num<?php echo $low ? ' is-low' : ''; ?>" data-label="창고 재고">
							<?php echo esc_html( number_format( (int) $it->stock ) ); ?>
							<?php if ( $low ) : ?><span class="mds-flag">부족</span><?php endif; ?>
						</td>
						<td class="num mds-avg" data-label="우리 팀 월평균" data-avg="<?php echo esc_attr( $avg ); ?>"><?php echo esc_html( $avg > 0 ? $avg : '—' ); ?></td>
						<td class="mds-last" data-label="최근 수령">
							<?php if ( $last ) : ?>
								<?php echo esc_html( mysql2date( 'n/j', $last->created_at ) . ' · ' . (int) $last->qty ); ?>
							<?php else : ?>—<?php endif; ?>
						</td>
						<td class="num" data-label="신청 수량">
							<span class="mds-stepper">
								<button type="button" class="mds-step" data-step="-1" aria-label="수량 줄이기" tabindex="-1">−</button>
								<input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
								       name="qty[<?php echo (int) $it->id; ?>]" value="<?php echo esc_attr( $pre ); ?>"
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
					<?php if ( ( $fav_count + $used_count ) && $i_row === ( $fav_count + $used_count ) ) : ?>
						<tr class="mds-divider"><td colspan="7">여기부터는 우리 팀이 최근 3개월간 받아가지 않은 품목입니다</td></tr>
					<?php endif; ?>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>

		<?php /* 목록에 없는 품목을 직접 적어 신청.
		         담당자가 나중에 품목으로 등록하거나 대체품을 알려 준다. */ ?>
		<details class="mds-custom">
			<summary class="mds-custom__sum">목록에 없는 품목 신청하기</summary>
			<p class="mds-hint" style="margin:10px 0 12px">
				찾으시는 품목이 위 목록에 없으면 여기에 직접 적어 주세요.
				이름과 규격을 되도록 자세히 적어 주시면 담당자가 확인해 주문합니다.
			</p>
			<?php for ( $c = 1; $c <= 3; $c++ ) : ?>
				<div class="mds-custom__row">
					<input type="text" name="custom_name[]" maxlength="200"
					       placeholder="품목명 · 규격 · 브랜드 (예: 오스템 KS 픽스처 4.0×10)"
					       aria-label="직접 적는 품목명 <?php echo (int) $c; ?>">
					<input type="number" name="custom_qty[]" min="0" step="1" inputmode="numeric"
					       class="mds-qty" placeholder="수량" aria-label="직접 적는 품목 수량 <?php echo (int) $c; ?>">
				</div>
			<?php endfor; ?>
		</details>

		<div class="mds-submit" id="mds-submit">
			<label class="mds-check"><input type="checkbox" name="urgent" value="1"> 긴급 (오늘 필요)</label>
			<input class="mds-note" type="text" name="note" placeholder="담당자에게 전할 메모 (선택)" maxlength="200">
			<button type="submit" class="mds-btn mds-btn--fill">
				<?php echo esc_html( md_sup_team_name( $team_id ) ); ?> 이름으로 신청
			</button>
		</div>

		<?php /* 고정 바 — 수량을 하나라도 적으면 화면 아래에 붙어 따라온다.
		         568줄을 스크롤해 내려가 제출 버튼을 찾지 않아도 되게. */ ?>
		<div class="mds-cart" id="mds-cart" hidden>
			<div class="mds-cart__inner">
				<span class="mds-cart__team"><?php echo esc_html( md_sup_team_name( $team_id ) ); ?></span>
				<span class="mds-cart__sum">
					<b id="mds-cart-count">0</b>개 품목
					<span class="mds-cart__won">약 <b id="mds-cart-total">0</b>원</span>
				</span>
				<button type="submit" class="mds-btn mds-btn--fill">신청하기</button>
			</div>
		</div>
	</form>

	<?php
	$mine = md_sup_requests( array( 'team_id' => $team_id, 'limit' => 8 ) );
	if ( $mine ) : ?>
		<h2 class="mds-h2">우리 팀 최근 신청</h2>
		<p class="mds-hint">매달 비슷한 것을 신청하신다면 「이대로 다시 담기」를 누르세요. 수량이 그대로 채워집니다.</p>
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>신청일</th><th class="num">품목 수</th><th>상태</th><th>메모</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $mine as $r ) : ?>
					<tr>
						<td data-label="신청일"><?php echo esc_html( mysql2date( 'Y-m-d H:i', $r->created_at ) ); ?><?php echo $r->urgent ? ' <span class="mds-flag">긴급</span>' : ''; ?></td>
						<td class="num" data-label="품목 수"><?php echo (int) $r->line_count; ?>건</td>
						<td data-label="상태"><span class="mds-status is-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( md_sup_status_label( $r->status ) ); ?></span></td>
						<td class="mds-memo" data-label="메모"><?php echo esc_html( $r->note ); ?></td>
						<td class="num">
							<a class="mds-mini" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'request', 'team' => $team_id, 'repeat' => (int) $r->id ) ) ); ?>">이대로 다시 담기</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif;
}

/** 신청 직후 요약 — 무엇을 몇 개 넣었는지 확인시켜 준다 */
function md_sup_render_sent_summary( $req_id ) {
	$r = md_sup_request_summary( $req_id );
	if ( ! $r ) { return; }
	$lines = md_sup_request_lines( $req_id );
	?>
	<section class="mds-card mds-sent">
		<h2 class="mds-h2" style="margin-top:0">신청이 접수되었습니다</h2>
		<p class="mds-sent__meta">
			<b><?php echo esc_html( $r->team_name ); ?></b> ·
			<?php echo esc_html( mysql2date( 'Y-m-d H:i', $r->created_at ) ); ?> ·
			<?php echo (int) $r->line_count; ?>개 품목 ·
			약 <b><?php echo esc_html( number_format( (int) $r->amount ) ); ?></b>원
			<?php if ( $r->urgent ) : ?><span class="mds-flag">긴급</span><?php endif; ?>
		</p>
		<ul class="mds-sent__list">
			<?php foreach ( $lines as $ln ) : ?>
				<li>
					<span><?php echo esc_html( $ln->name ); ?></span>
					<b><?php echo (int) $ln->qty_req; ?><?php echo $ln->unit ? esc_html( ' ' . $ln->unit ) : ''; ?></b>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="mds-hint" style="margin:14px 0 0">
			재고 담당자가 확인 후 출고합니다. 진행 상태는 아래 「우리 팀 최근 신청」에서 보실 수 있습니다.
		</p>
	</section>
	<?php
}

/** ② 통계 */
function md_sup_render_stats() {
	$ym      = isset( $_GET['ym'] ) ? sanitize_text_field( wp_unslash( $_GET['ym'] ) ) : current_time( 'Y-m' );
	$my_team = md_sup_my_team_id();
	/* 볼 팀 — 지정 안 했으면 내 소속 팀. 전체를 보려면 0 */
	$view    = isset( $_GET['team'] ) ? (int) $_GET['team'] : $my_team;
	$teams   = md_sup_teams();

	$usage = md_sup_team_usage( $ym );
	$delta_map = md_sup_team_delta( $ym );
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

	<div class="mds-tools">
		<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( "tab" => "stats", "ym" => $ym, "team" => $view, "export" => "usage" ) ) ); ?>">엑셀(CSV) 내려받기</a>
		<button type="button" class="mds-btn mds-btn--ghost" onclick="window.print()">인쇄</button>
	</div>

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
					<?php $d = isset( $delta_map[ (int) $u->team_id ] ) ? $delta_map[ (int) $u->team_id ] : null; ?>
					<span class="mds-bar__delta<?php echo ( null !== $d && $d > 20 ) ? " is-up" : ( ( null !== $d && $d < -10 ) ? " is-down" : "" ); ?>"><?php echo null === $d ? "—" : esc_html( ( $d > 0 ? "+" : "" ) . $d . "%" ); ?></span>
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
							$is_custom = ( 0 === (int) $ln->item_id );
							$stock     = $is_custom ? 0 : md_sup_stock( $ln->item_id ); ?>
							<tr class="<?php echo $is_custom ? 'is-custom' : ''; ?>">
								<td class="mds-item" data-label="품목">
									<b><?php echo esc_html( $ln->name ); ?></b>
									<span class="mds-item__meta">
										<?php if ( $is_custom ) : ?>
											<span class="mds-flag">직접 적은 품목</span> 등록되지 않은 품목입니다
										<?php else : ?>
											<?php echo esc_html( $ln->code ); ?>
										<?php endif; ?>
									</span>
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

	<div class="mds-tools">
		<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( "tab" => "inventory", "export" => "stock" ) ) ); ?>">엑셀(CSV) 내려받기</a>
		<button type="button" class="mds-btn mds-btn--ghost" onclick="window.print()">실사표 인쇄</button>
	</div>

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
	if ( isset( $_GET['err'] ) ) {
		echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['err'] ) ) . '</div>';
	}

	if ( 'stock' !== $app ) {
		md_sup_render_hub();
	} else {
		switch ( $tab ) {
			case 'stats':     md_sup_render_stats();     break;
			case 'manage':    md_sup_render_manage();    break;
			case 'inbound':   md_sup_render_inbound();   break;
			case 'inventory': md_sup_render_inventory(); break;
			case 'items':     md_sup_render_items();     break;
			default:          md_sup_render_request();   break;
		}
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
