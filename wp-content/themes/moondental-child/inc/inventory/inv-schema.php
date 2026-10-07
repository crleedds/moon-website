<?php
/**
 * 재고관리 v5 — 테이블
 *
 * AppSheet 판(2026-09-21 ~ 10-03)에서 정해진 요구를 그대로 옮긴다.
 *   · 재고는 저장하지 않는다. 현재고 = 그 품목 원장(ledger) 수량의 합.
 *     AppSheet 에서 「액션으로 만든 행에 수식이 안 돌아 재고가 거꾸로 늘던」 버그가
 *     구조적으로 생길 수 없게, 부호는 기록할 때 한 곳(md_inv_ledger_add)에서만 정한다.
 *   · 요청 1건 = 품목 1개 (AppSheet 2판). 장바구니로 여러 개를 한 번에 보내면 여러 건이 생긴다.
 *   · 주문 1건 = 품목 1개. 입고하면 원장에 '입고' 한 줄.
 *   · 선납(선불) 업체: 입금 장부 − 입고 금액(무상 제외) + 반품 금액 = 잔액.
 *
 * 옛 재료실(wp_md_sup_*) 테이블은 건드리지 않는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_INV_SCHEMA', 6 ); /* v9.3 · 6 = 신청에 사진 (req.photos) */ // /* v6.5 · 5 = 입고 · 주문 금액을 그대로 (amount · extra · free_qty), 입금 기록 지우지 않고 취소 */

/** 테이블 이름 */
function md_inv_t( $key = '' ) {
	global $wpdb;
	$p = $wpdb->prefix . 'md_inv_';
	$t = array(
		'cat'     => $p . 'cat',      // 분류 3단계 (결제 방식 › 품목군 › 세부 분류)
		'team'    => $p . 'team',     // 팀
		'vendor'  => $p . 'vendor',   // 업체
		'item'    => $p . 'item',     // 품목
		'ledger'  => $p . 'ledger',   // 입출고 원장
		'req'     => $p . 'req',      // 요청 (1건 = 품목 1개)
		'ord'     => $p . 'ord',      // 주문 (1건 = 품목 1개)
		'deposit' => $p . 'deposit',  // 선납 입금 장부
		'backup'  => $p . 'backup',   // 백업 보관함
		'log'     => $p . 'log',      // 작업 기록 (누가 언제 무엇을)
		'fav'     => $p . 'fav',      // 팀 즐겨찾기 (v5.7)
		'price'   => $p . 'price',    // 단가 변동 기록 (v5.7)
		'recon'   => $p . 'recon',    // 선납 업체 잔액 대조 (v6.0)
		'adj'     => $p . 'adj',      // 입고 뒤 환불 · 정정 (v6.3)
	);
	return '' === $key ? $t : $t[ $key ];
}

/** 밀린 설치를 한다. 각 단계는 두 번 돌아도 안전하다. */
function md_inv_migrate() {
	$cur = (int) get_option( 'md_inv_schema', 0 );
	if ( $cur >= MD_INV_SCHEMA ) { return; }
	if ( get_transient( 'md_inv_migrating' ) ) { return; }
	set_transient( 'md_inv_migrating', 1, MINUTE_IN_SECONDS );

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	if ( $cur < 1 ) {
		md_inv_schema_1();
		/* 설치한 그 주기에는 보고서를 보내지 않는다 — 데이터를 가져오기 전 빈 보고서가 나가지 않게 */
		if ( function_exists( 'md_inv_report_period_key' ) && ! get_option( 'md_inv_last_report_key' ) ) {
			$k = md_inv_report_period_key( current_time( 'timestamp' ) );
			update_option( 'md_inv_last_report_key', null === $k ? 'install' : $k, false );
		}
	}
	if ( $cur < 2 ) { md_inv_schema_2(); }
	if ( $cur < 3 ) { md_inv_schema_3(); }
	if ( $cur < 4 ) { md_inv_schema_4(); }
	if ( $cur < 5 ) { md_inv_schema_5(); }
	if ( $cur < 6 ) { md_inv_schema_6(); }

	update_option( 'md_inv_schema', MD_INV_SCHEMA );
	delete_transient( 'md_inv_migrating' );
}
add_action( 'admin_init', 'md_inv_migrate', 4 );
add_action( 'template_redirect', function () {
	if ( function_exists( 'md_sup_is_page' ) && md_sup_is_page() && is_user_logged_in() ) { md_inv_migrate(); }
}, 0 );

function md_inv_schema_1() {
	global $wpdb;
	$t = md_inv_t();
	$c = $wpdb->get_charset_collate();

	dbDelta( "CREATE TABLE {$t['cat']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		level TINYINT UNSIGNED NOT NULL DEFAULT 1,
		name VARCHAR(100) NOT NULL DEFAULT '',
		note VARCHAR(255) NOT NULL DEFAULT '',
		sort_no INT NOT NULL DEFAULT 0,
		active TINYINT(1) NOT NULL DEFAULT 1,
		legacy VARCHAR(40) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY parent_id (parent_id),
		KEY level_sort (level, sort_no)
	) $c;" );

	dbDelta( "CREATE TABLE {$t['team']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(100) NOT NULL DEFAULT '',
		sort_no INT NOT NULL DEFAULT 0,
		active TINYINT(1) NOT NULL DEFAULT 1,
		in_stats TINYINT(1) NOT NULL DEFAULT 1,
		legacy VARCHAR(40) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY sort_no (sort_no)
	) $c;" );

	dbDelta( "CREATE TABLE {$t['vendor']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(150) NOT NULL DEFAULT '',
		contact VARCHAR(255) NOT NULL DEFAULT '',
		phone VARCHAR(255) NOT NULL DEFAULT '',
		email VARCHAR(255) NOT NULL DEFAULT '',
		shop_info TEXT NULL,
		goods TEXT NULL,
		note TEXT NULL,
		prepaid TINYINT(1) NOT NULL DEFAULT 0,
		sort_no INT NOT NULL DEFAULT 0,
		active TINYINT(1) NOT NULL DEFAULT 1,
		legacy VARCHAR(40) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY name (name),
		KEY prepaid (prepaid)
	) $c;" );

	dbDelta( "CREATE TABLE {$t['item']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		code VARCHAR(40) NOT NULL DEFAULT '',
		name VARCHAR(255) NOT NULL DEFAULT '',
		vendor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		unit VARCHAR(30) NOT NULL DEFAULT '',
		price BIGINT NOT NULL DEFAULT 0,
		cat1 BIGINT UNSIGNED NOT NULL DEFAULT 0,
		cat2 BIGINT UNSIGNED NOT NULL DEFAULT 0,
		cat3 BIGINT UNSIGNED NOT NULL DEFAULT 0,
		min_stock INT NOT NULL DEFAULT 0,
		barcode VARCHAR(100) NOT NULL DEFAULT '',
		note TEXT NULL,
		active TINYINT(1) NOT NULL DEFAULT 1,
		created_at DATETIME NULL DEFAULT NULL,
		created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
		updated_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY code (code),
		KEY vendor_id (vendor_id),
		KEY cat2 (cat2),
		KEY barcode (barcode),
		KEY active (active)
	) $c;" );

	/* 원장 — 수량은 부호로 방향을 표현한다.
	 *   open 이관/기초 +, in 입고 +, out 출고 −, return 반품(업체로) −, adjust 실사 ±
	 * price·vendor_id 는 기록하는 순간의 값을 남긴다 — 나중에 품목 단가나 업체를
	 * 바꿔도 지난달 사용금액·선납 차감액이 따라 바뀌지 않게. */
	dbDelta( "CREATE TABLE {$t['ledger']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		type VARCHAR(12) NOT NULL DEFAULT 'out',
		qty INT NOT NULL DEFAULT 0,
		price BIGINT NOT NULL DEFAULT 0,
		vendor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		team_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		req_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		ord_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		counted INT NULL DEFAULT NULL,
		free TINYINT(1) NOT NULL DEFAULT 0,
		person VARCHAR(100) NOT NULL DEFAULT '',
		note VARCHAR(500) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		voided TINYINT(1) NOT NULL DEFAULT 0,
		void_note VARCHAR(255) NOT NULL DEFAULT '',
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY item_id (item_id),
		KEY type_date (type, created_at),
		KEY team_id (team_id),
		KEY vendor_id (vendor_id),
		KEY req_id (req_id),
		KEY ord_id (ord_id),
		KEY created_at (created_at)
	) $c;" );

	/* 요청 — 1건 = 품목 1개.
	 * item_id 0 이면 「목록에 없는 품목」: custom_* 에 직원이 적은 값이 있다.
	 * status: pending 대기 · done 출고 완료 · rejected 반려 · cancelled 취소 */
	dbDelta( "CREATE TABLE {$t['req']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		batch VARCHAR(20) NOT NULL DEFAULT '',
		team_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		requester VARCHAR(100) NOT NULL DEFAULT '',
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		custom_name VARCHAR(255) NOT NULL DEFAULT '',
		custom_vendor VARCHAR(150) NOT NULL DEFAULT '',
		custom_unit VARCHAR(30) NOT NULL DEFAULT '',
		custom_price BIGINT NOT NULL DEFAULT 0,
		custom_link VARCHAR(500) NOT NULL DEFAULT '',
		qty INT NOT NULL DEFAULT 0,
		qty_out INT NOT NULL DEFAULT 0,
		status VARCHAR(12) NOT NULL DEFAULT 'pending',
		urgent TINYINT(1) NOT NULL DEFAULT 0,
		note VARCHAR(500) NOT NULL DEFAULT '',
		admin_note VARCHAR(500) NOT NULL DEFAULT '',
		parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NULL DEFAULT NULL,
		done_at DATETIME NULL DEFAULT NULL,
		done_by VARCHAR(100) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY status (status),
		KEY team_id (team_id),
		KEY item_id (item_id),
		KEY created_at (created_at),
		KEY batch (batch)
	) $c;" );

	/* 주문 — 1건 = 품목 1개. status: ordered 주문함 · received 입고 완료 · cancelled 취소
	 * 일부만 들어오면 recv_qty 가 늘고 ordered 로 남는다. */
	dbDelta( "CREATE TABLE {$t['ord']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		vendor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		qty INT NOT NULL DEFAULT 0,
		recv_qty INT NOT NULL DEFAULT 0,
		price BIGINT NOT NULL DEFAULT 0,
		amount BIGINT NOT NULL DEFAULT 0,
		status VARCHAR(12) NOT NULL DEFAULT 'ordered',
		req_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		note VARCHAR(500) NOT NULL DEFAULT '',
		cancel_note VARCHAR(255) NOT NULL DEFAULT '',
		person VARCHAR(100) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NULL DEFAULT NULL,
		received_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY status (status),
		KEY item_id (item_id),
		KEY vendor_id (vendor_id),
		KEY req_id (req_id)
	) $c;" );

	dbDelta( "CREATE TABLE {$t['deposit']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		vendor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		paid_on DATE NULL DEFAULT NULL,
		amount BIGINT NOT NULL DEFAULT 0,
		note VARCHAR(255) NOT NULL DEFAULT '',
		person VARCHAR(100) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY vendor_id (vendor_id)
	) $c;" );

	dbDelta( "CREATE TABLE {$t['backup']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		kind VARCHAR(12) NOT NULL DEFAULT 'auto',
		note VARCHAR(255) NOT NULL DEFAULT '',
		rows_n INT NOT NULL DEFAULT 0,
		size INT NOT NULL DEFAULT 0,
		data LONGBLOB NULL,
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at)
	) $c;" );

	dbDelta( "CREATE TABLE {$t['log']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		person VARCHAR(100) NOT NULL DEFAULT '',
		action VARCHAR(40) NOT NULL DEFAULT '',
		detail VARCHAR(1000) NOT NULL DEFAULT '',
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at)
	) $c;" );

	md_inv_seed_defaults();
}

/**
 * 2단계 (v5.7) · 보관 위치 · 받은 사람 · 팀 즐겨찾기 · 단가 변동 기록
 */
function md_inv_schema_2() {
	global $wpdb;
	$t = md_inv_t();
	$c = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE {$t['fav']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		team_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY team_item (team_id, item_id)
	) $c;" );
	dbDelta( "CREATE TABLE {$t['price']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		vendor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		old_price BIGINT NOT NULL DEFAULT 0,
		new_price BIGINT NOT NULL DEFAULT 0,
		source VARCHAR(12) NOT NULL DEFAULT '',
		applied TINYINT(1) NOT NULL DEFAULT 1,
		person VARCHAR(100) NOT NULL DEFAULT '',
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY item_id (item_id),
		KEY created_at (created_at)
	) $c;" );
	md_inv_add_col( $t['item'], 'location', "VARCHAR(100) NOT NULL DEFAULT ''" );
	md_inv_add_col( $t['ledger'], 'receiver', "VARCHAR(100) NOT NULL DEFAULT ''" );
	md_inv_add_col( $t['req'], 'receiver', "VARCHAR(100) NOT NULL DEFAULT ''" );
}

/**
 * 3단계 (v6.0) · 선납 개선 — 입금 적립(credit) · 조정(kind) · 업체 적립률 · 잔액 알림 기준 · LOT · 차트번호 · 잔액 대조
 */
function md_inv_schema_3() {
	global $wpdb;
	$t = md_inv_t();
	$c = $wpdb->get_charset_collate();
	md_inv_add_col( $t['deposit'], 'credit', 'BIGINT NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['deposit'], 'kind', "VARCHAR(10) NOT NULL DEFAULT 'pay'" );
	md_inv_add_col( $t['vendor'], 'pp_bonus', 'DECIMAL(6,2) NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['vendor'], 'pp_alert', 'BIGINT NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['item'], 'track_lot', 'TINYINT(1) NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['ledger'], 'lot', "VARCHAR(80) NOT NULL DEFAULT ''" );
	md_inv_add_col( $t['ledger'], 'chart', "VARCHAR(40) NOT NULL DEFAULT ''" );
	dbDelta( "CREATE TABLE {$t['recon']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		vendor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		as_of DATE NULL DEFAULT NULL,
		vendor_bal BIGINT NOT NULL DEFAULT 0,
		our_bal BIGINT NOT NULL DEFAULT 0,
		adj_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		note VARCHAR(255) NOT NULL DEFAULT '',
		person VARCHAR(100) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY vendor_id (vendor_id)
	) $c;" );
	/* 지금까지의 입금은 낸 돈 = 쓸 수 있는 돈 */
	$wpdb->query( "UPDATE {$t['deposit']} SET credit = amount WHERE credit = 0 AND amount <> 0" );
}

/** 4단계 (v6.3) · 입고 뒤 환불 · 정정 (물건은 그대로) */
function md_inv_schema_4() {
	global $wpdb;
	$t = md_inv_t();
	$c = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE {$t['adj']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		ledger_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		vendor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		kind VARCHAR(10) NOT NULL DEFAULT 'refund',
		amount BIGINT NOT NULL DEFAULT 0,
		qty INT NOT NULL DEFAULT 0,
		dest VARCHAR(10) NOT NULL DEFAULT 'balance',
		note VARCHAR(300) NOT NULL DEFAULT '',
		person VARCHAR(100) NOT NULL DEFAULT '',
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		voided TINYINT(1) NOT NULL DEFAULT 0,
		void_note VARCHAR(200) NOT NULL DEFAULT '',
		created_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY ledger_id (ledger_id),
		KEY vendor_id (vendor_id)
	) $c;" );
}

/**
 * 5단계 (v6.5) · 금액을 빈틈없이 — 입고 한 줄에 실제로 낸 금액(배송비 포함) · 무상 수량, 주문에 무상 · 배송비,
 * 입금 기록은 지우지 않고 「취소」로
 */
function md_inv_schema_5() {
	$t = md_inv_t();
	md_inv_add_col( $t['ledger'], 'amount', 'BIGINT NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['ledger'], 'extra', 'BIGINT NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['ledger'], 'free_qty', 'INT NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['ledger'], 'money_set', 'TINYINT(1) NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['ord'], 'free_qty', 'INT NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['ord'], 'extra', 'BIGINT NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['deposit'], 'voided', 'TINYINT(1) NOT NULL DEFAULT 0' );
	md_inv_add_col( $t['deposit'], 'void_note', "VARCHAR(255) NOT NULL DEFAULT ''" );
	update_option( 'md_inv_schema', 4 ); /* md_inv_money_fix 가 4 이상에서만 돈다 */
	if ( function_exists( 'md_inv_money_fix' ) ) { md_inv_money_fix(); }
}

/** 열이 없을 때만 더한다 */
function md_inv_add_col( $table, $col, $def ) {
	global $wpdb;
	$cols = $wpdb->get_col( "SHOW COLUMNS FROM $table" );
	if ( is_array( $cols ) && in_array( $col, $cols, true ) ) { return; }
	$wpdb->query( "ALTER TABLE $table ADD COLUMN $col $def" );
}

/**
 * 비어 있는 설치에 최소한의 기준 정보를 넣는다.
 * 실제 데이터는 「설정 › 가져오기」에서 AppSheet 엑셀로 들여온다.
 */
function md_inv_seed_defaults() {
	global $wpdb;
	$t = md_inv_t();
	if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['team']}" ) > 0 ) { return; }

	$teams = array( '서비스지원실', '9층 공통', '10층 공통', '11층 공통', 'Dr. 이승주팀', 'Dr. 권혜진팀', 'Dr. 이수연팀',
		'Dr. 문은수팀', 'Dr. 이창률팀', 'Dr. 이영일팀', 'Dr. 정석형팀', 'Dr. 김세일팀', '예방과', '기공실', '경영지원실', '기타 팀' );
	foreach ( $teams as $i => $n ) {
		$wpdb->insert( $t['team'], array( 'name' => $n, 'sort_no' => ( $i + 1 ) * 10, 'active' => 1, 'in_stats' => 1 ) );
	}
	if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['cat']}" ) === 0 ) {
		$wpdb->insert( $t['cat'], array( 'level' => 1, 'name' => '건별결제품목', 'sort_no' => 10, 'legacy' => 'C1' ) );
		$c1 = (int) $wpdb->insert_id;
		$wpdb->insert( $t['cat'], array( 'level' => 1, 'name' => '선납차감품목', 'sort_no' => 20, 'legacy' => 'C5' ) );
		foreach ( array( '치과재료', '구강위생용품', '소모품', '기타 건별결제품목' ) as $i => $n ) {
			$wpdb->insert( $t['cat'], array( 'level' => 2, 'parent_id' => $c1, 'name' => $n, 'sort_no' => ( $i + 1 ) * 10 ) );
		}
	}
}

/** v9.3 · 신청에 붙인 사진 이름들 (JSON) */
function md_inv_schema_6() {
	md_inv_add_col( md_inv_t( 'req' ), 'photos', "VARCHAR(500) NOT NULL DEFAULT ''" );
}
