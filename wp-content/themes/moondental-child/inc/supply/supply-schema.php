<?php
/**
 * 재고관리 — 테이블 정의와 초기 데이터
 *
 * 왜 커스텀 테이블인가
 *   입출고 원장(ledger)은 수만 행까지 쌓이고, 「팀별·월별 사용량」처럼
 *   합계를 내는 질의가 핵심이다. 이걸 wp_posts/postmeta 로 만들면
 *   조인이 폭발해 통계 화면이 못 쓸 만큼 느려진다. 그래서 인덱스를 건
 *   전용 테이블을 쓴다.
 *
 * 재고는 저장하지 않는다
 *   현재고 = 해당 품목 원장 수량의 합. 값을 직접 고치는 칸은 없다.
 *   덕분에 「왜 줄었는지」가 항상 추적된다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_SUP_DB_VERSION', '1.0.0' );
define( 'MD_SUP_DB2_VERSION', '1.0.0' );
define( 'MD_SUP_DB3_VERSION', '1.0.0' );

/** 테이블 이름 모음 */
function md_sup_tables() {
	global $wpdb;
	$p = $wpdb->prefix . 'md_sup_';
	return array(
		'items'  => $p . 'items',
		'teams'  => $p . 'teams',
		'ledger' => $p . 'ledger',
		'req'    => $p . 'req',
		'line'   => $p . 'req_line',
		'notice' => $p . 'notice',
		'fav'    => $p . 'fav',
	);
}

/**
 * 테이블 생성 / 스키마 갱신.
 * dbDelta 는 있으면 두고 없으면 만드는 방식이라 여러 번 불러도 안전하다.
 */
function md_sup_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$t       = md_sup_tables();
	$charset = $wpdb->get_charset_collate();

	$sql = array();

	$sql[] = "CREATE TABLE {$t['items']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		code VARCHAR(20) NOT NULL DEFAULT '',
		name VARCHAR(255) NOT NULL DEFAULT '',
		vendor VARCHAR(100) NOT NULL DEFAULT '',
		unit VARCHAR(20) NOT NULL DEFAULT '',
		category VARCHAR(60) NOT NULL DEFAULT '',
		price INT NOT NULL DEFAULT 0,
		min_stock INT NOT NULL DEFAULT 0,
		active TINYINT(1) NOT NULL DEFAULT 1,
		sort_no INT NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY code (code),
		KEY category (category),
		KEY vendor (vendor),
		KEY active_sort (active, sort_no)
	) $charset;";

	$sql[] = "CREATE TABLE {$t['teams']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(100) NOT NULL DEFAULT '',
		sort_no INT NOT NULL DEFAULT 0,
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY (id),
		UNIQUE KEY name (name),
		KEY active_sort (active, sort_no)
	) $charset;";

	/* 입출고 원장 — 이 시스템의 심장.
	 * qty 는 부호로 방향을 표현한다. 입고 +, 출고 −, 폐기 −, 실사조정 ±. */
	$sql[] = "CREATE TABLE {$t['ledger']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		team_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		qty INT NOT NULL DEFAULT 0,
		reason VARCHAR(20) NOT NULL DEFAULT 'out',
		ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		note VARCHAR(255) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		ym CHAR(7) NOT NULL DEFAULT '',
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY (id),
		KEY item_id (item_id),
		KEY team_ym (team_id, ym),
		KEY reason_ym (reason, ym),
		KEY created_at (created_at)
	) $charset;";

	$sql[] = "CREATE TABLE {$t['req']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		team_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		status VARCHAR(20) NOT NULL DEFAULT 'pending',
		urgent TINYINT(1) NOT NULL DEFAULT 0,
		note VARCHAR(500) NOT NULL DEFAULT '',
		created_at DATETIME NULL DEFAULT NULL,
		done_at DATETIME NULL DEFAULT NULL,
		done_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		KEY status (status),
		KEY team_id (team_id),
		KEY created_at (created_at)
	) $charset;";

	$sql[] = "CREATE TABLE {$t['line']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		req_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		qty_req INT NOT NULL DEFAULT 0,
		qty_out INT NOT NULL DEFAULT 0,
		over_reason VARCHAR(255) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		KEY req_id (req_id),
		KEY item_id (item_id)
	) $charset;";

	foreach ( $sql as $q ) { dbDelta( $q ); }

	md_sup_seed_teams();
	md_sup_seed_items();

	update_option( 'md_sup_db_version', MD_SUP_DB_VERSION );
}

/**
 * 팀 15개. 기존 재고관리 앱( moondentalstock )의 팀 구성을 그대로 옮겼다.
 * 표시 순서 = 3행 × 5열 (층별 데스크·공통 + 원장팀 + 기공실).
 */
function md_sup_seed_teams() {
	global $wpdb;
	$t = md_sup_tables();

	$teams = array(
		'9층 데스크', '9층 공통', 'Dr. 이승주팀', 'Dr. 권혜진팀', 'Dr. 이수연팀',
		'10층 데스크', '10층 공통', 'Dr. 병원장팀', 'Dr. 이창률팀', '기공실',
		'11층 데스크', '11층 공통', 'Dr. 이영일팀', 'Dr. 정석형팀', 'Dr. 김세일팀',
	);

	foreach ( $teams as $i => $name ) {
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['teams']} WHERE name = %s", $name ) );
		if ( $exists ) { continue; }
		$wpdb->insert(
			$t['teams'],
			array( 'name' => $name, 'sort_no' => ( $i + 1 ) * 10, 'active' => 1 ),
			array( '%s', '%d', '%d' )
		);
	}
}

/**
 * 품목 568개. inc/supply/items-seed.json 에서 읽어 넣는다.
 * 이미 있는 코드는 건너뛰므로 여러 번 실행해도 중복되지 않는다.
 *
 * 시드의 stock 값은 넣지 않는다 — 기존 앱의 지난 스냅샷이라 지금과 다르다.
 * 시작 재고는 화면에서 실사(초기 이월)로 입력한다.
 */
function md_sup_seed_items() {
	global $wpdb;
	$t    = md_sup_tables();
	$file = get_stylesheet_directory() . '/inc/supply/items-seed.json';
	if ( ! file_exists( $file ) ) { return; }

	$raw  = file_get_contents( $file );
	$rows = json_decode( $raw, true );
	if ( ! is_array( $rows ) ) { return; }

	$have = $wpdb->get_col( "SELECT code FROM {$t['items']}" );
	$have = is_array( $have ) ? array_flip( $have ) : array();

	/* 568건을 한 줄씩 INSERT 하면 공유 호스팅에서 수십 초가 걸려 타임아웃 위험이 있다.
	 * 100건씩 묶어 한 번에 넣는다. */
	$chunk = array();
	$i     = 0;

	foreach ( $rows as $r ) {
		$i++;
		$code = isset( $r['id'] ) ? sanitize_text_field( (string) $r['id'] ) : '';
		if ( '' === $code || isset( $have[ $code ] ) ) { continue; }

		$chunk[] = $wpdb->prepare(
			'(%s,%s,%s,%s,%s,%d,%d,1,%d)',
			$code,
			isset( $r['name'] ) ? sanitize_text_field( (string) $r['name'] ) : '',
			isset( $r['vendor'] ) ? sanitize_text_field( (string) $r['vendor'] ) : '',
			isset( $r['unit'] ) ? sanitize_text_field( (string) $r['unit'] ) : '',
			isset( $r['category'] ) ? sanitize_text_field( (string) $r['category'] ) : '',
			isset( $r['price'] ) ? (int) $r['price'] : 0,
			isset( $r['minStock'] ) ? (int) $r['minStock'] : 0,
			$i
		);

		if ( count( $chunk ) >= 100 ) { md_sup_flush_items( $chunk ); $chunk = array(); }
	}
	if ( $chunk ) { md_sup_flush_items( $chunk ); }
}

/** 묶음 INSERT 실행 (md_sup_seed_items 전용) */
function md_sup_flush_items( $chunk ) {
	global $wpdb;
	$t = md_sup_tables();
	$wpdb->query(
		"INSERT INTO {$t['items']} (code,name,vendor,unit,category,price,min_stock,active,sort_no) VALUES "
		. implode( ',', $chunk )
	);
}

/**
 * 스키마 버전이 다르면 설치를 다시 돌린다.
 * 관리자가 접속할 때만 확인해 일반 요청에는 부담을 주지 않는다.
 */
function md_sup_maybe_install() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
	if ( get_option( 'md_sup_db_version' ) === MD_SUP_DB_VERSION ) { return; }
	md_sup_install();
}
add_action( 'admin_init', 'md_sup_maybe_install' );

/**
 * 「직원」 페이지가 없으면 만든다.
 * 푸터 링크가 /직원/ 을 가리키는데 페이지가 없으면 404 가 뜬다.
 * 테마의 다른 자동 생성(inc/reservation.php)과 같은 방식.
 */
function md_sup_ensure_page() {
	/* v3.61 · 「직원 전용」 → 「재료실」 로 이름을 바꾼다.
	 * 푸터 링크가 /재료실/ 을 가리키므로 기존 페이지 슬러그도 옮겨야 한다.
	 * 한 번만 실행되고, 이미 바꿨거나 사람이 손댄 경우엔 건드리지 않는다. */
	$pid = (int) get_option( 'md_sup_page_id' );
	if ( $pid && ! get_option( 'md_sup_renamed_v361' ) ) {
		$p = get_post( $pid );
		if ( $p && 'page' === $p->post_type && '직원' === urldecode( $p->post_name ) ) {
			wp_update_post( array(
				'ID'         => $pid,
				'post_title' => '재료실',
				'post_name'  => '재료실',
			) );
		}
		update_option( 'md_sup_renamed_v361', '1' );
	}

	if ( $pid && get_post( $pid ) ) { return; }

	$existing = get_page_by_path( '재료실' );
	if ( ! $existing ) { $existing = get_page_by_path( '직원' ); }
	if ( ! $existing ) { $existing = get_page_by_path( 'staff' ); }

	if ( $existing ) {
		update_option( 'md_sup_page_id', (int) $existing->ID );
		return;
	}

	$id = wp_insert_post( array(
		'post_title'     => '재료실',
		'post_name'      => '재료실',
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'post_content'   => '',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	) );

	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_page_template', 'page-templates/page-supply.php' );
		/* 사이트맵·검색에서 빼둔다. 템플릿에서도 noindex 를 걸지만 여기서 한 번 더. */
		update_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', '1' );
		update_post_meta( $id, '_yoast_wpseo_sitemap-include', 'never' );
		update_option( 'md_sup_page_id', (int) $id );
	}
}
add_action( 'admin_init', 'md_sup_ensure_page', 20 );

/**
 * 재고 담당자 역할과 권한.
 * 홈페이지 글을 쓸 권한은 주지 않는다 — 재고 기능만 가진 역할이다.
 */
function md_sup_add_roles() {
	if ( ! get_role( 'md_stock_staff' ) ) {
		add_role( 'md_stock_staff', '재고 · 직원', array(
			'read'          => true,
			'md_supply_use' => true,
		) );
	}
	if ( ! get_role( 'md_stock_manager' ) ) {
		add_role( 'md_stock_manager', '재고 · 담당자', array(
			'read'             => true,
			'md_supply_use'    => true,
			'md_supply_manage' => true,
		) );
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'md_supply_use' );
		$admin->add_cap( 'md_supply_manage' );
	}
}
add_action( 'admin_init', 'md_sup_add_roles', 5 );

/**
 * 사용자 프로필에 「소속 팀」 칸을 붙인다.
 * 이게 없으면 누가 어느 팀인지 알 수 없어 사용량 집계가 무의미해진다.
 * 관리자만 고칠 수 있게 한다 — 직원이 스스로 팀을 바꾸면 통계가 흔들린다.
 */
function md_sup_user_team_field( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) { return; }
	if ( ! function_exists( 'md_sup_teams' ) ) { return; }

	$current = (int) get_user_meta( $user->ID, 'md_sup_team_id', true );
	?>
	<h2>재고관리 · 소속 팀</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="md_sup_team_id">소속 팀</label></th>
			<td>
				<select name="md_sup_team_id" id="md_sup_team_id">
					<option value="0">— 지정 안 함 —</option>
					<?php foreach ( md_sup_teams() as $tm ) : ?>
						<option value="<?php echo (int) $tm->id; ?>" <?php selected( $current, $tm->id ); ?>>
							<?php echo esc_html( $tm->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					재고 신청 화면에서 기본으로 선택되는 팀입니다. 사용량도 이 팀으로 집계됩니다.
				</p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'md_sup_user_team_field' );
add_action( 'edit_user_profile', 'md_sup_user_team_field' );

function md_sup_save_user_team( $user_id ) {
	if ( ! current_user_can( 'edit_users' ) ) { return; }
	if ( ! isset( $_POST['md_sup_team_id'] ) ) { return; }
	check_admin_referer( 'update-user_' . $user_id );
	update_user_meta( $user_id, 'md_sup_team_id', (int) $_POST['md_sup_team_id'] );
}
add_action( 'personal_options_update', 'md_sup_save_user_team' );
add_action( 'edit_user_profile_update', 'md_sup_save_user_team' );

/** 사용자 목록에 소속 팀 열 추가 — 누가 어느 팀인지 한눈에 */
function md_sup_user_column( $cols ) {
	$cols['md_sup_team'] = '재고 팀';
	return $cols;
}
add_filter( 'manage_users_columns', 'md_sup_user_column' );

function md_sup_user_column_value( $val, $col, $user_id ) {
	if ( 'md_sup_team' !== $col ) { return $val; }
	$id = (int) get_user_meta( $user_id, 'md_sup_team_id', true );
	return $id && function_exists( 'md_sup_team_name' ) ? esc_html( md_sup_team_name( $id ) ) : '—';
}
add_filter( 'manage_users_custom_column', 'md_sup_user_column_value', 10, 3 );

/* ============================================================
 * v3.59 · 공지사항 · 즐겨찾기
 * ============================================================ */

/** 추가 테이블 (공지 · 즐겨찾기) */
function md_sup_install_v2() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$t       = md_sup_tables();
	$charset = $wpdb->get_charset_collate();

	/* 공지사항 — 직원 전용. 본문은 서식 없는 글로 받고 출력할 때 줄바꿈만 살린다.
	 * 에디터를 붙이지 않는 이유 · 재고 계정은 wp-admin 에 못 들어가므로
	 * 프론트에서 써야 하는데, 리치 에디터는 HTML 이 섞여 들어와 위험하다. */
	dbDelta( "CREATE TABLE {$t['notice']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL DEFAULT '',
		body TEXT NULL,
		pinned TINYINT(1) NOT NULL DEFAULT 0,
		author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		author_name VARCHAR(100) NOT NULL DEFAULT '',
		created_at DATETIME NULL DEFAULT NULL,
		updated_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY (id),
		KEY pinned_created (pinned, created_at)
	) $charset;" );

	/* 팀별 즐겨찾기 품목 */
	dbDelta( "CREATE TABLE {$t['fav']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		team_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY team_item (team_id, item_id)
	) $charset;" );

	update_option( 'md_sup_db2_version', MD_SUP_DB2_VERSION );
}

function md_sup_maybe_install_v2() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
	if ( get_option( 'md_sup_db2_version' ) === MD_SUP_DB2_VERSION ) { return; }
	md_sup_install_v2();
}
add_action( 'admin_init', 'md_sup_maybe_install_v2', 11 );

/* ============================================================
 * v3.61 · 목록에 없는 품목을 직접 적어 신청
 * ============================================================ */

/**
 * 신청 줄에 custom_name 을 더한다.
 * item_id 가 0 이고 custom_name 이 있으면 "직접 적은 품목" 이다.
 * 담당자가 나중에 실제 품목에 연결하거나 새 품목으로 등록한다.
 */
function md_sup_install_v3() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = md_sup_tables();

	$has = $wpdb->get_var( "SHOW COLUMNS FROM {$t['line']} LIKE 'custom_name'" );
	if ( ! $has ) {
		$wpdb->query( "ALTER TABLE {$t['line']} ADD COLUMN custom_name VARCHAR(255) NOT NULL DEFAULT '' AFTER item_id" );
	}
	/* item_id 0 이 들어갈 수 있으므로 인덱스는 그대로 두고 제약만 없다 */

	update_option( 'md_sup_db3_version', MD_SUP_DB3_VERSION );
}

function md_sup_maybe_install_v3() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
	if ( get_option( 'md_sup_db3_version' ) === MD_SUP_DB3_VERSION ) { return; }
	md_sup_install_v3();
}
add_action( 'admin_init', 'md_sup_maybe_install_v3', 12 );
