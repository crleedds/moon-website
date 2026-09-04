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

/**
 * 스키마 단계 번호.
 *
 * v3.63 까지는 MD_SUP_DB_VERSION / DB2 / DB3 세 상수와 maybe_install 세 개가
 * 따로 돌았다. 단계가 늘 때마다 상수와 훅을 한 벌씩 더 만들어야 했고,
 * 어느 단계까지 적용됐는지 한눈에 보이지 않았다.
 * 이제는 번호 하나(md_sup_schema)와 단계 목록 하나로 끝난다.
 */
define( 'MD_SUP_SCHEMA', 6 );

/** 테이블 이름 모음 */
function md_sup_tables() {
	global $wpdb;
	$p = $wpdb->prefix . 'md_sup_';
	return array(
		'items'   => $p . 'items',
		'teams'   => $p . 'teams',
		'ledger'  => $p . 'ledger',
		'req'     => $p . 'req',
		'line'    => $p . 'req_line',
		'fav'     => $p . 'fav',
		'po'      => $p . 'po',
		'po_line' => $p . 'po_line',
		'cat'     => $p . 'cat',
		'vendor'  => $p . 'vendor',
	);
	/* 'notice' 는 뺐다 — v3.59 에 넣고 v3.61 에 화면을 지운 뒤로
	 * 읽지도 쓰지도 않는다. 다만 그 사이에 쓴 글이 남아 있을 수 있어
	 * 테이블 자체는 DROP 하지 않는다. 목록에서만 뺀다. */
}

/* ============================================================
 * 마이그레이션 — 번호 하나로 관리한다
 * ============================================================ */

/** 단계 번호 → 실행할 함수 */
function md_sup_schema_steps() {
	return array(
		1 => 'md_sup_schema_1',  // 기본 테이블 + 팀·품목 시드
		2 => 'md_sup_schema_2',  // 즐겨찾기
		3 => 'md_sup_schema_3',  // 신청 줄에 직접 적은 품목명
		4 => 'md_sup_schema_4',  // 반려 사유 · 발주
		5 => 'md_sup_schema_5',  // 분류 · 거래처 목록 · 직원 등록 표시
		6 => 'md_sup_schema_6',  // 예방과 팀 추가
	);
}

/**
 * 지금 몇 단계까지 적용돼 있는가.
 * 새 옵션이 없으면 예전 세 옵션을 보고 단계를 유추한다 —
 * 이미 돌고 있는 설치에서 1~3단계가 다시 실행되지 않게 하기 위함이다.
 */
function md_sup_current_schema() {
	$v = (int) get_option( 'md_sup_schema', 0 );
	if ( $v > 0 ) { return $v; }

	if ( get_option( 'md_sup_db3_version' ) ) { return 3; }
	if ( get_option( 'md_sup_db2_version' ) ) { return 2; }
	if ( get_option( 'md_sup_db_version' ) )  { return 1; }
	return 0;
}

/**
 * 밀린 단계를 순서대로 실행한다.
 * 각 단계는 dbDelta 나 「없으면 만든다」 방식이라 두 번 돌아도 안전하다.
 */
function md_sup_migrate() {
	$from = md_sup_current_schema();
	if ( $from >= MD_SUP_SCHEMA ) { return; }

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	foreach ( md_sup_schema_steps() as $n => $fn ) {
		if ( $n <= $from ) { continue; }
		if ( ! function_exists( $fn ) ) { continue; }
		call_user_func( $fn );
		update_option( 'md_sup_schema', $n );
	}
}

/** 관리자가 wp-admin 에 들어올 때 확인한다 */
function md_sup_maybe_migrate() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) { return; }
	md_sup_migrate();
}
add_action( 'admin_init', 'md_sup_maybe_migrate', 3 );

/**
 * 재료실 화면에서도 확인한다.
 *
 * 재고 담당자 계정은 wp-admin 에 들어가지 않는다. 관리자가 한동안
 * 로그인하지 않으면 배포는 됐는데 테이블만 옛 모양인 상태가 이어져,
 * 담당자 화면이 「알 수 없는 열」 오류로 죽는다. 그 구멍을 막는다.
 * 동시 접속이 겹쳐 두 번 돌지 않게 짧은 자물쇠를 건다.
 */
function md_sup_maybe_migrate_front() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! md_sup_can_manage() ) { return; }
	if ( md_sup_current_schema() >= MD_SUP_SCHEMA ) { return; }
	if ( get_transient( 'md_sup_migrating' ) ) { return; }

	set_transient( 'md_sup_migrating', 1, MINUTE_IN_SECONDS );
	md_sup_migrate();
	delete_transient( 'md_sup_migrating' );
}
add_action( 'template_redirect', 'md_sup_maybe_migrate_front', 0 );

/**
 * 1단계 · 기본 테이블 생성과 초기 데이터.
 * dbDelta 는 있으면 두고 없으면 만드는 방식이라 여러 번 불러도 안전하다.
 */
function md_sup_schema_1() {
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
		'예방과',
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
 * 「직원」 페이지가 없으면 만든다.
 * 푸터 링크가 /직원/ 을 가리키는데 페이지가 없으면 404 가 뜬다.
 * 테마의 다른 자동 생성(inc/reservation.php)과 같은 방식.
 */
function md_sup_ensure_page() {
	/* v3.62 · 페이지는 「직원 전용」이고, 그 안에서 「재료실」을 골라 들어간다.
	 * v3.61 에서 잠깐 페이지째 재료실로 바꿨다가 되돌린다.
	 * 한 번만 실행되고, 사람이 슬러그를 손댄 경우엔 건드리지 않는다. */
	$pid = (int) get_option( 'md_sup_page_id' );
	if ( $pid && ! get_option( 'md_sup_renamed_v362' ) ) {
		$p = get_post( $pid );
		if ( $p && 'page' === $p->post_type && in_array( urldecode( $p->post_name ), array( '재료실', '직원' ), true ) ) {
			wp_update_post( array(
				'ID'         => $pid,
				'post_title' => '직원 전용',
				'post_name'  => '직원',
			) );
		}
		update_option( 'md_sup_renamed_v362', '1' );
	}

	if ( $pid && get_post( $pid ) ) { return; }

	$existing = get_page_by_path( '직원' );
	if ( ! $existing ) { $existing = get_page_by_path( '재료실' ); }
	if ( ! $existing ) { $existing = get_page_by_path( 'staff' ); }

	if ( $existing ) {
		update_option( 'md_sup_page_id', (int) $existing->ID );
		return;
	}

	$id = wp_insert_post( array(
		'post_title'     => '직원 전용',
		'post_name'      => '직원',
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
 * 2단계 · 즐겨찾기 (v3.59)
 * ============================================================ */

function md_sup_schema_2() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$t       = md_sup_tables();
	$charset = $wpdb->get_charset_collate();

	/* 팀별 즐겨찾기 품목 */
	dbDelta( "CREATE TABLE {$t['fav']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		team_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY team_item (team_id, item_id)
	) $charset;" );

	/* 같은 단계에 있던 공지사항 테이블은 만들지 않는다 — v3.61 에 화면이 사라졌다.
	 * 이미 만들어진 곳에서는 그대로 두고 쓰지 않을 뿐이다. */
}

/* ============================================================
 * 3단계 · 목록에 없는 품목을 직접 적어 신청 (v3.61)
 *
 * item_id 가 0 이고 custom_name 이 있으면 "직접 적은 품목" 이다.
 * 담당자가 나중에 실제 품목으로 등록한다.
 * ============================================================ */

function md_sup_schema_3() {
	global $wpdb;
	$t = md_sup_tables();
	md_sup_add_column( $t['line'], 'custom_name', "VARCHAR(255) NOT NULL DEFAULT '' AFTER item_id" );
}

/* ============================================================
 * 4단계 · 반려 사유 분리 · 발주 (v3.64)
 * ============================================================ */

/**
 * 열이 없을 때만 더한다.
 *
 * dbDelta 로도 되지만, dbDelta 는 CREATE TABLE 전문을 그대로 다시 적어야 해서
 * 열 하나 붙이려고 표 정의를 통째로 복사하다 실수하기 쉽다.
 */
function md_sup_add_column( $table, $column, $definition ) {
	global $wpdb;
	$has = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `$table` LIKE %s", $column ) );
	if ( $has ) { return false; }
	$wpdb->query( "ALTER TABLE `$table` ADD COLUMN `$column` $definition" );
	return true;
}

function md_sup_schema_4() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$t       = md_sup_tables();
	$charset = $wpdb->get_charset_collate();

	/* 반려 사유를 따로 담는다.
	 * v3.63 까지는 반려하면 신청자가 적은 note 를 덮어써 버려,
	 * 나중에 「무엇을 왜 요청했는데 왜 반려됐나」를 맞춰볼 수 없었다. */
	md_sup_add_column( $t['req'], 'reject_reason', "VARCHAR(500) NOT NULL DEFAULT '' AFTER note" );

	/* 발주서 — 「부족 → 주문 → 입고」 사이의 빈칸을 메운다.
	 * 이게 없으면 주문을 넣었는데 안 온 건지 아직 안 넣은 건지 알 수 없다. */
	dbDelta( "CREATE TABLE {$t['po']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		vendor VARCHAR(100) NOT NULL DEFAULT '',
		status VARCHAR(20) NOT NULL DEFAULT 'draft',
		note VARCHAR(500) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NULL DEFAULT NULL,
		ordered_at DATETIME NULL DEFAULT NULL,
		received_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY (id),
		KEY status (status),
		KEY vendor (vendor),
		KEY created_at (created_at)
	) $charset;" );

	dbDelta( "CREATE TABLE {$t['po_line']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		po_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		qty_order INT NOT NULL DEFAULT 0,
		qty_recv INT NOT NULL DEFAULT 0,
		price INT NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		KEY po_id (po_id),
		KEY item_id (item_id)
	) $charset;" );

	/* 대기 신청 알림을 받을 주소 — 비어 있으면 사이트 관리자 메일로 간다 */
	if ( false === get_option( 'md_sup_notify_emails', false ) ) {
		add_option( 'md_sup_notify_emails', '' );
	}
}

/* ============================================================
 * 5단계 · 분류 · 거래처를 담당자가 관리하는 목록으로 (v3.65)
 *
 * 왜 표를 따로 두는가
 *   지금까지 분류·거래처는 품목마다 적힌 글자였고, 화면의 고르는 칸은
 *   그 글자들을 DISTINCT 로 긁어 만들었다. 그래서
 *     · 품목이 하나도 없는 새 거래처를 미리 만들어 둘 수 없고
 *     · 「새한치재」와 「새한치재 」(끝에 공백)가 서로 다른 항목이 되고
 *     · 이름을 고치려면 그 값을 쓰는 품목을 하나씩 다 열어야 했다.
 *   이름 목록을 따로 두면 담당자가 추가·수정·삭제할 수 있고,
 *   이름을 바꾸면 그 값을 쓰던 품목까지 한 번에 따라 바뀐다.
 *
 * 품목에는 여전히 이름을 적어 둔다 (id 가 아니라)
 *   568개 품목의 값을 id 로 바꾸는 이사는 위험한 데 비해 얻는 게 적다.
 *   지난 CSV·기록과도 그대로 맞는다. 목록은 「고르는 칸과 표기를 관리하는 곳」이다.
 * ============================================================ */

function md_sup_schema_5() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$t       = md_sup_tables();
	$charset = $wpdb->get_charset_collate();

	dbDelta( "CREATE TABLE {$t['cat']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(60) NOT NULL DEFAULT '',
		sort_no INT NOT NULL DEFAULT 0,
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY (id),
		UNIQUE KEY name (name),
		KEY active_sort (active, sort_no)
	) $charset;" );

	dbDelta( "CREATE TABLE {$t['vendor']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(100) NOT NULL DEFAULT '',
		sort_no INT NOT NULL DEFAULT 0,
		active TINYINT(1) NOT NULL DEFAULT 1,
		PRIMARY KEY (id),
		UNIQUE KEY name (name),
		KEY active_sort (active, sort_no)
	) $charset;" );

	/* 누가 만든 품목인가. 직원이 신청하다가 직접 등록한 품목은
	 * 단가·적정재고가 비어 있으니 담당자가 채워야 한다 — 그걸 알아보려고 남긴다. */
	md_sup_add_column( $t['items'], 'created_by', 'BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER sort_no' );

	md_sup_seed_taxo();
}

/**
 * 지금 품목이 쓰고 있는 분류·거래처로 목록을 채운다.
 * INSERT IGNORE 라 여러 번 돌아도 늘어나지 않는다.
 */
function md_sup_seed_taxo() {
	global $wpdb;
	$t = md_sup_tables();

	$pairs = array(
		'category' => $t['cat'],
		'vendor'   => $t['vendor'],
	);

	foreach ( $pairs as $col => $table ) {
		$vals = $wpdb->get_col( "SELECT DISTINCT `$col` FROM {$t['items']} WHERE `$col` <> '' ORDER BY `$col`" );
		if ( ! is_array( $vals ) ) { continue; }

		$i = 0;
		foreach ( $vals as $v ) {
			$v = trim( (string) $v );
			if ( '' === $v ) { continue; }
			$i += 10;
			$wpdb->query( $wpdb->prepare(
				"INSERT IGNORE INTO `$table` (name, sort_no, active) VALUES (%s, %d, 1)",
				$v,
				$i
			) );
		}
	}
}

/* ============================================================
 * 6단계 · 예방과 (v3.66)
 *
 * 처음 팀 15개는 예전 재고관리 앱의 구성을 그대로 옮긴 것이라
 * 예방과가 빠져 있었다. 이미 돌고 있는 설치에도 넣어 준다.
 *
 * 이름이 이미 있으면 아무것도 하지 않는다 — 담당자가 직접 만들어 뒀을 수 있다.
 * 순서는 맨 뒤에 붙인다. 층 배치에 맞춰 앞으로 옮기려면
 * 「품목·팀」 탭의 팀 목록에서 순서 숫자를 고치면 된다.
 * ============================================================ */

function md_sup_schema_6() {
	md_sup_ensure_team( '예방과' );
}

/** 그 이름의 팀이 없으면 맨 뒤에 만든다 */
function md_sup_ensure_team( $name ) {
	global $wpdb;
	$t    = md_sup_tables();
	$name = trim( sanitize_text_field( (string) $name ) );
	if ( '' === $name ) { return false; }

	$has = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['teams']} WHERE name = %s", $name ) );
	if ( $has ) { return (int) $has; }

	$max = (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort_no), 0) FROM {$t['teams']}" );
	$wpdb->insert(
		$t['teams'],
		array( 'name' => $name, 'sort_no' => $max + 10, 'active' => 1 ),
		array( '%s', '%d', '%d' )
	);
	return (int) $wpdb->insert_id;
}
