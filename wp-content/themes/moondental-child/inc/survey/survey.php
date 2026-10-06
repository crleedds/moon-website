<?php
/**
 * v4.10 · 환자 만족도 조사 — 환자용 설문 페이지(/만족도/) + 직원 전용(/직원/) 허브의 관리 도구
 *
 *  덴트웹 진료 후 알림톡에 공통 링크(https://moondental.co.kr/만족도/)를 넣어 보낸다.
 *  덴트웹은 담당 스탭 이름이나 환자별 링크를 자동으로 채우지 못하므로, 그 둘은
 *  이 페이지가 맡는다.
 *
 *    1. 데스크가 그날 접수 명단(차트번호 · 이름 · 휴대폰 · 담당 원장 · 담당 스탭)을
 *       직원 허브에 올린다 (덴트웹 기간별 접수환자 목록 엑셀 붙여넣기, 한 명씩 입력, 또는 REST 로 자동 전송).
 *    2. 환자가 링크를 열면 링크에 실린 이름(?n=#환자명#)과 환자가 넣은 휴대폰 마지막 4자리를 명단과 대조한다.
 *       이름이 링크에 없으면 환자가 이름을 적는다. 맞으면 그날 담당 원장 · 담당 스탭 이름이 보이고 설문이 열린다.
 *    3. 제출하면 그 진료(명단 한 줄)는 응답 완료로 잠긴다 — 같은 진료에 두 번 쓸 수 없다.
 *
 *  누가 무엇을 하는가
 *    직원 공용 계정   명단 올리기 · 그날 명단 보기
 *    관리자           응답 열람 · 스탭별 집계 · CSV · 설정 (원장·스탭 목록 · 응답 허용 일수 · 연동 키)
 *
 *  개인정보
 *    명단의 휴대폰 마지막 4자리는 비밀키로 HMAC 해시해 저장한다 (원문은 남기지 않는다). 생년월일은 받지 않는다 (v4.10.8).
 *    환자가 입력한 값도 저장하지 않는다 — 해시해서 비교만 한다.
 *    본인 확인이 5번 틀리면 그 접속은 30분 동안 막는다.
 *    명단 · 응답 · 올린 엑셀 원본은 모두 진료일로부터 2년이 지나면 지운다 (v4.21.38).
 *
 *  화면은 서버에서 그린다. 자바스크립트가 없어도 전부 동작한다.
 *  폼은 POST → 처리 → 리다이렉트(PRG). 새로고침해도 두 번 올라가지 않는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_SURVEY_SCHEMA', 10 ); /* v4.21.33 · ip (응답한 인터넷 주소 — 대리 작성 식별용) — 9: dev_hash · flags */

/* ============================================================
 * 테이블 · 설치
 * ============================================================ */

function md_survey_table_visit()    { global $wpdb; return $wpdb->prefix . 'md_survey_visit'; }
function md_survey_table_response() { global $wpdb; return $wpdb->prefix . 'md_survey_response'; }
function md_survey_table_file()     { global $wpdb; return $wpdb->prefix . 'md_survey_file'; }

/** 없으면 만든다. add_option 으로 잠가 한 번만 돈다 (지원 요청과 같은 방식). */
function md_survey_maybe_install() {
	if ( (int) get_option( 'md_survey_schema', 0 ) >= MD_SURVEY_SCHEMA ) { return; }
	if ( ! add_option( 'md_survey_installing', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'md_survey_installing' ) < 300 ) { return; }
		delete_option( 'md_survey_installing' );
		if ( ! add_option( 'md_survey_installing', time(), '', 'no' ) ) { return; }
	}

	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	$tv = md_survey_table_visit();
	$tr = md_survey_table_response();

	dbDelta( "CREATE TABLE $tv (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		visit_date DATE NOT NULL,
		chart_no VARCHAR(20) NOT NULL,
		patient_name VARCHAR(40) NOT NULL DEFAULT '',
		ident_hash CHAR(64) NOT NULL,
		phone_hash CHAR(64) NOT NULL DEFAULT '',
		doctor VARCHAR(120) NOT NULL DEFAULT '',
		staff VARCHAR(40) NOT NULL DEFAULT '',
		token CHAR(24) NOT NULL,
		source VARCHAR(12) NOT NULL DEFAULT 'manual',
		responded_at DATETIME NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY visit_chart (visit_date, chart_no),
		UNIQUE KEY token (token),
		KEY ident (ident_hash, visit_date),
		KEY phone (phone_hash, visit_date)
	) $charset;" );

	dbDelta( "CREATE TABLE $tr (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		visit_id BIGINT UNSIGNED NOT NULL,
		visit_date DATE NOT NULL,
		chart_no VARCHAR(20) NOT NULL,
		patient_name VARCHAR(40) NOT NULL DEFAULT '',
		doctor VARCHAR(120) NOT NULL DEFAULT '',
		staff VARCHAR(40) NOT NULL DEFAULT '',
		staff_orig VARCHAR(40) NOT NULL DEFAULT '',
		staff_changed TINYINT UNSIGNED NOT NULL DEFAULT 0,
		q_doctor TINYINT UNSIGNED NOT NULL DEFAULT 0,
		want_call TINYINT UNSIGNED NOT NULL DEFAULT 0,
		dev_hash CHAR(32) NOT NULL DEFAULT '',
		flags VARCHAR(80) NOT NULL DEFAULT '',
		ip VARCHAR(45) NOT NULL DEFAULT '',
		q_service TINYINT UNSIGNED NOT NULL,
		q_explain TINYINT UNSIGNED NOT NULL,
		q_recommend TINYINT UNSIGNED NOT NULL,
		comment TEXT NULL,
		ip_hash CHAR(32) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY visit (visit_id),
		KEY staff_date (staff, visit_date),
		KEY visit_date (visit_date)
	) $charset;" );

	/* v4.19.1 · 올린 엑셀 파일 자체를 보관 — 내려받기·삭제·바꿔 올리기. 로그인한 직원만 내려받을 수 있게 DB 에 둔다 */
	$tf = md_survey_table_file();
	dbDelta( "CREATE TABLE $tf (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		visit_date DATE NOT NULL,
		file_name VARCHAR(190) NOT NULL DEFAULT '',
		file_size INT UNSIGNED NOT NULL DEFAULT 0,
		content LONGTEXT NULL,
		uploaded_by VARCHAR(60) NOT NULL DEFAULT '',
		uploaded_at DATETIME NOT NULL,
		added INT UNSIGNED NOT NULL DEFAULT 0,
		updated INT UNSIGNED NOT NULL DEFAULT 0,
		skipped INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		UNIQUE KEY visit_date (visit_date)
	) $charset;" );

	if ( ! get_option( 'md_survey_secret' ) ) {
		add_option( 'md_survey_secret', wp_generate_password( 64, true, true ), '', 'no' );
	}
	if ( ! get_option( 'md_survey_api_key' ) ) {
		add_option( 'md_survey_api_key', wp_generate_password( 40, false ), '', 'no' );
	}

	update_option( 'md_survey_schema', MD_SURVEY_SCHEMA );
	delete_option( 'md_survey_installing' );
}
add_action( 'init', 'md_survey_maybe_install', 20 );

/** 진료일로부터 2년이 지난 명단 · 엑셀 원본 · 응답을 지운다 (매일 한 번) */
function md_survey_cleanup() {
	global $wpdb;
	/* v4.21.38 · 명단 · 응답 · 엑셀 원본 모두 진료일로부터 2년 뒤 삭제 (원장 지시) */
	$cut = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -2 years' ) );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_survey_table_visit() . ' WHERE visit_date < %s', $cut ) );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_survey_table_file() . ' WHERE visit_date < %s', $cut ) );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_survey_table_response() . ' WHERE visit_date < %s', $cut ) );
}
add_action( 'md_survey_cleanup', 'md_survey_cleanup' );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'md_survey_cleanup' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'md_survey_cleanup' ); }
}, 30 );

/* ============================================================
 * 설정
 * ============================================================ */

function md_survey_settings() {
	$d = array(
		'window_days' => 3,   /* 진료일 이후 며칠까지 응답할 수 있나 */
		'notify_email' => 'moondentaldigital@gmail.com', /* v4.21.36 · 새 응답 알림 받을 메일 (원장 지시) */
		'doctors'     => '',  /* 한 줄에 한 명 */
		'staff'       => '',
		'review_url'  => '',  /* v4.12 · 구글 리뷰 바로가기 (비우면 구글 지도에서 병원을 찾는 링크) */
	);
	$s = get_option( 'md_survey_settings', array() );
	return is_array( $s ) ? array_merge( $d, $s ) : $d;
}

function md_survey_setting( $key ) {
	$s = md_survey_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/** 설정의 한 줄짜리 목록 → 배열 (빈 줄 · 중복 제거) */
function md_survey_name_list( $key ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) md_survey_setting( $key ) );
	$out   = array();
	foreach ( $lines as $l ) {
		$l = trim( $l );
		if ( '' !== $l && ! in_array( $l, $out, true ) ) { $out[] = $l; }
	}
	return $out;
}

function md_survey_public_url() {
	return home_url( '/만족도/' );
}

/** v4.12 · 구글 리뷰 링크 — 설정에 없으면 구글 지도에서 병원을 찾는 주소 */
function md_survey_review_url() {
	$u = (string) md_survey_setting( 'review_url' );
	if ( '' !== $u ) return $u;
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( '문치과병원 천안 만남로 52' );
}

/* ============================================================
 * 정규화 · 해시
 * ============================================================ */

/**
 * (v4.14.3 ~ v6.3.1 에는 가운데 4자리를 썼다 — 서명 없이 저장한 엑셀은 뒷자리가 가려졌기 때문.)
 *  덴트웹 DB 연동(dwpublic)은 번호 전체를 주므로 v6.3.2 부터 마지막 4자리로 바꿨다.
 *  엑셀로 올릴 때는 덴트웹 엑셀저장에서 인증서로 서명해야 번호 전체가 나온다.
 *  입력이 딱 4자리면 그대로, 전체 번호면 끝 4자리. 뒷자리에 * 가 있으면 쓸 수 없어 빈 값.
 *  11자리(가려진 자리는 * 로 세어서)면 4~7번째, 그 밖에는 ''.
 */
function md_survey_norm_phone4( $v ) {
	/* v6.3.2 · 본인 확인 숫자를 휴대폰 「마지막 4자리」로 (원장 지시 — 덴트웹 DB 연동은 번호를 가리지 않고 준다).
	 * 입력이 딱 4자리면 그대로, 전체 번호면 끝 4자리. 뒷자리가 * 로 가려진 번호(서명 없이 저장한 엑셀)는 쓸 수 없어 '' */
	$raw = (string) $v;
	if ( false !== strpos( $raw, '*' ) ) { return ''; }
	$s = preg_replace( '/\D+/', '', $raw );
	return strlen( $s ) >= 4 ? substr( $s, -4 ) : '';
}

/**
 * 생년월일 → YYMMDD 6자리.
 *  8자리(19800101) · 6자리(800101) · 주민번호(800101-1234567 → 앞 6) · 1980-01-01 모두 받는다.
 */
function md_survey_norm_birth6( $v ) {
	$d = preg_replace( '/\D+/', '', (string) $v );
	if ( 8 === strlen( $d ) ) { $d = substr( $d, 2 ); }
	elseif ( 13 === strlen( $d ) ) { $d = substr( $d, 0, 6 ); }
	if ( 6 !== strlen( $d ) ) { return ''; }
	$m = (int) substr( $d, 2, 2 );
	$dd = (int) substr( $d, 4, 2 );
	if ( $m < 1 || $m > 12 || $dd < 1 || $dd > 31 ) { return ''; }
	return $d;
}

function md_survey_secret() {
	$s = (string) get_option( 'md_survey_secret' );
	if ( '' === $s ) { $s = wp_generate_password( 64, true, true ); update_option( 'md_survey_secret', $s, 'no' ); }
	return $s;
}

/** 본인 확인 값의 해시 — 원문 대신 이것만 저장한다 */
function md_survey_ident_hash( $phone4, $birth6 ) {
	return hash_hmac( 'sha256', $phone4 . '|' . $birth6, md_survey_secret() );
}

/** 휴대폰 마지막 4자리의 해시 (v6.3.2 전에는 가운데 4자리) — 링크에 이름이 실려 온 환자는 이것과 이름으로 확인한다 (v4.10.1) */
function md_survey_phone_hash( $phone4 ) {
	return hash_hmac( 'sha256', 'p|' . $phone4, md_survey_secret() );
}

/**
 * v4.21.2 · 설문 화면의 사람 사진.
 *   원장 — 직원 정보에 올린 사진이 있으면 그것, 없으면 홈페이지 의료진 사진
 *   스탭 — 직원 정보(라운지)에 관리자가 올린 사진
 * 없으면 '' (화면에는 이름 첫 글자).
 */
function md_survey_person_photo( $name, $kind = 'staff' ) {
	$name = trim( preg_replace( '/\s*(원장|대표원장|병원장|선생님)\s*$/u', '', (string) $name ) );
	if ( '' === $name ) { return ''; }
	$key = preg_replace( '/\s+/u', '', $name );
	/* 원장은 홈페이지 의료진 사진을 먼저 (v4.21.3 · 원장 지시 — 이미 있는 사진을 끌어 쓴다) */
	if ( 'doctor' === $kind && function_exists( 'moondental_get_team' ) ) {
		foreach ( (array) moondental_get_team() as $m ) {
			if ( isset( $m['name'] ) && preg_replace( '/\s+/u', '', $m['name'] ) === $key && ! empty( $m['photo'] ) ) {
				$base = pathinfo( $m['photo'], PATHINFO_FILENAME );
				foreach ( array( 'jpg', 'png', 'jpeg', 'webp' ) as $ext ) {
					if ( file_exists( MOONDENTAL_DIR . '/assets/images/doctors/' . $base . '.' . $ext ) ) { return MOONDENTAL_URI . '/assets/images/doctors/' . $base . '.' . $ext; }
				}
			}
		}
	}
	/* 직원 정보에 관리자가 올린 사진 — 파일이 실제로 있을 때만 */
	if ( function_exists( 'md_staff_all' ) && function_exists( 'md_staff_photo_dir' ) ) {
		$dir = md_staff_photo_dir();
		foreach ( md_staff_all( true ) as $r ) {
			if ( preg_replace( '/\s+/u', '', $r->name ) !== $key || empty( $r->photo ) ) { continue; }
			if ( file_exists( $dir['dir'] . '/' . basename( $r->photo ) ) ) { return md_staff_photo_url( $r ); }
		}
	}
	return '';
}

/** 원장 호칭 — 문은수는 「병원장님」, 나머지는 「원장님」 (v4.21.4 · 원장 지시) */
function md_survey_doctor_title( $name ) {
	$n = trim( preg_replace( '/\s*(대표\s*)?(병원장|원장)(님)?\s*$/u', '', (string) $name ) );
	if ( '' === $n ) { return ''; }
	return $n . ' 원장님'; /* v4.21.32 · 문은수도 「원장」 (원장 지시 — 병원장 호칭 철회) */
}

/** 담당직원 표시 — 직원 정보(라운지)의 직책을 이름 옆에 (예: 홍길동 → 홍길동 실장 · 원장 지시) */
function md_survey_staff_title( $name ) {
	$name = trim( (string) $name );
	if ( '' === $name || ! function_exists( 'md_staff_all' ) ) { return $name; }
	$key = md_survey_name_key( $name );
	foreach ( md_staff_all( true ) as $r ) {
		if ( md_survey_name_key( $r->name ) !== $key ) { continue; }
		$pos = trim( (string) ( $r->position ?? '' ) );
		return '' !== $pos && false === mb_strpos( $name, $pos ) ? trim( $r->name ) . ' ' . $pos : $name;
	}
	return $name;
}

/** 「다른 선생님이었어요」 고르기 목록 — 직원 정보의 재직 직원(의료진 제외) 중 명단의 담당 스탭이 아닌 사람 */
function md_survey_staff_choices( $current = '' ) {
	if ( ! function_exists( 'md_staff_all' ) ) { return array(); }
	$out = array();
	foreach ( md_staff_all( true ) as $r ) {
		$n = trim( (string) $r->name );
		if ( '' === $n || '의료진' === $r->dept || $n === trim( (string) $current ) ) { continue; }
		if ( ! in_array( $r->dept, array( '진료실', '예방과', '서비스지원실' ), true ) && '' !== (string) $r->dept ) { continue; } /* 환자를 직접 응대하는 부서만 */
		$out[] = array( 'name' => $n, 'photo' => md_survey_person_photo( $n, 'staff' ) );
	}
	return $out;
}

/** 이름 비교용 — 공백 제거 */
function md_survey_norm_name( $v ) {
	/* v4.21.31 · 공백과 치환문 껍데기(#{ } #)를 뗀다 — 덴트웹이 치환을 못 하고 #{이름} 이 그대로 와도 이름만 남게 */
	return preg_replace( '/[\s#{}]+/u', '', trim( sanitize_text_field( (string) $v ) ) );
}

/** 이름 비교 키 — 공백 제거 + 한글 이름 끝의 덴트웹 접미사(B, 2 …) 제거 (v4.21.27) */
function md_survey_name_key( $v ) {
	$n = md_survey_norm_name( $v );
	$s = preg_replace( '/[A-Za-z0-9]+$/u', '', $n );
	return ( '' !== $s && preg_match( '/\p{Hangul}/u', $s ) ) ? $s : $n;
}

function md_survey_ip_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return substr( hash_hmac( 'md5', $ip, md_survey_secret() ), 0, 32 );
}

/* ============================================================
 * v4.21.8 · 대리 작성 감지 (원장 지시 — 당사자만, 직원이 대신 쓰지 못하게)
 *   덴트웹 알림톡에는 환자별 비밀 링크를 넣을 수 없어, 환자 정보를 아는 직원이 마음먹으면
 *   막을 수는 없다. 대신 흔적을 남기고 집계에서 뺀다.
 *   ① 기기 표시 — 설문을 연 브라우저에 1년짜리 무작위 쿠키. 한 기기에서 다른 환자 응답이 또 오면 둘 다 표시
 *   ② 병원 인터넷 — 직원 라운지에 로그인해 쓰는 인터넷(IP)을 기억해 두고, 같은 곳에서 온 응답을 표시
 *   표시된 응답은 스탭·원장 집계에서 빠지고, 응답 목록에 ⚠ 로 보인다.
 * ============================================================ */

function md_survey_device_cookie() {
	if ( ! empty( $_COOKIE['md_sv_dev'] ) && preg_match( '/^[A-Za-z0-9]{20,40}$/', (string) $_COOKIE['md_sv_dev'] ) ) { return; }
	$id = wp_generate_password( 32, false );
	setcookie( 'md_sv_dev', $id, time() + YEAR_IN_SECONDS, '/', '', is_ssl(), true );
	$_COOKIE['md_sv_dev'] = $id;
}

function md_survey_dev_hash() {
	$id = isset( $_COOKIE['md_sv_dev'] ) ? (string) $_COOKIE['md_sv_dev'] : '';
	return '' === $id ? '' : substr( hash_hmac( 'md5', 'dev|' . $id, md_survey_secret() ), 0, 32 );
}

/** 직원 라운지를 쓰는 인터넷(IP 해시)을 60일간 기억 */
function md_survey_note_staff_ip() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() || ! is_user_logged_in() ) { return; }
	$h    = md_survey_ip_hash();
	$list = get_option( 'md_survey_staff_ips', array() );
	if ( ! is_array( $list ) ) { $list = array(); }
	if ( isset( $list[ $h ] ) && $list[ $h ] > time() - DAY_IN_SECONDS ) { return; } /* 하루 한 번만 갱신 */
	$list[ $h ] = time();
	foreach ( $list as $k => $t ) { if ( $t < time() - 60 * DAY_IN_SECONDS ) { unset( $list[ $k ] ); } }
	arsort( $list );
	update_option( 'md_survey_staff_ips', array_slice( $list, 0, 100, true ), false );
}
add_action( 'template_redirect', 'md_survey_note_staff_ip', 5 );

/** 지금 응답의 의심 표시 — 'dev'(같은 기기에서 다른 환자) · 'net'(병원 인터넷) */
function md_survey_flags_for( $chart_no ) {
	global $wpdb;
	$flags = array();
	$dev   = md_survey_dev_hash();
	if ( '' !== $dev ) {
		$other = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . md_survey_table_response() . ' WHERE dev_hash = %s AND chart_no <> %s', $dev, (string) $chart_no ) );
		if ( $other > 0 ) {
			$flags[] = 'dev';
			/* 먼저 들어온 같은 기기 응답에도 표시 */
			$wpdb->query( $wpdb->prepare( "UPDATE " . md_survey_table_response() . " SET flags = TRIM(BOTH ',' FROM CONCAT(flags, ',dev')) WHERE dev_hash = %s AND FIND_IN_SET('dev', flags) = 0", $dev ) );
		}
	}
	$ips = get_option( 'md_survey_staff_ips', array() );
	if ( is_array( $ips ) && isset( $ips[ md_survey_ip_hash() ] ) ) { $flags[] = 'net'; }
	return implode( ',', $flags );
}

/** 표시 → 사람이 읽는 말 */
function md_survey_flag_labels( $flags ) {
	$map = array( 'dev' => '같은 기기에서 다른 환자 응답', 'net' => '병원 인터넷에서 작성' );
	$out = array();
	foreach ( array_filter( explode( ',', (string) $flags ) ) as $f ) { if ( isset( $map[ $f ] ) ) { $out[] = $map[ $f ]; } }
	return $out;
}

/** 설문 화면 ↔ 제출 사이를 잇는 서명 (명단 id · 만료 시각). 쿠키를 쓰지 않는다. */
function md_survey_sign( $visit_id, $exp ) {
	$visit_id = (int) $visit_id; $exp = (int) $exp;
	return $visit_id . '.' . $exp . '.' . substr( hash_hmac( 'sha256', $visit_id . '|' . $exp, md_survey_secret() ), 0, 32 );
}

function md_survey_verify_sign( $tok ) {
	$p = explode( '.', (string) $tok );
	if ( 3 !== count( $p ) ) { return 0; }
	$visit_id = (int) $p[0]; $exp = (int) $p[1];
	if ( $exp < time() ) { return 0; }
	$want = substr( hash_hmac( 'sha256', $visit_id . '|' . $exp, md_survey_secret() ), 0, 32 );
	return hash_equals( $want, (string) $p[2] ) ? $visit_id : 0;
}

/* ============================================================
 * 명단 (visit)
 * ============================================================ */

function md_survey_visit( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_survey_table_visit() . ' WHERE id = %d', (int) $id ) );
}

function md_survey_visit_by_token( $token ) {
	global $wpdb;
	$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
	if ( '' === $token ) { return null; }
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_survey_table_visit() . ' WHERE token = %s', $token ) );
}

/**
 * 명단 한 줄 넣기 — 같은 날 같은 차트번호가 있으면 덮어쓴다 (담당자가 바뀌었을 때).
 * 이미 응답한 줄은 담당자를 바꾸지 않는다 (응답이 그 담당자 앞으로 기록됐으므로).
 */
function md_survey_visit_upsert( $d, $source = 'manual' ) {
	global $wpdb;
	$date   = isset( $d['date'] ) ? sanitize_text_field( $d['date'] ) : current_time( 'Y-m-d' );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { return new WP_Error( 'md_survey', '날짜 형식이 맞지 않습니다.' ); }
	$chart  = mb_substr( trim( sanitize_text_field( isset( $d['chart_no'] ) ? $d['chart_no'] : '' ) ), 0, 20 );
	$name   = mb_substr( trim( sanitize_text_field( isset( $d['name'] ) ? $d['name'] : '' ) ), 0, 40 );
	$phone4 = md_survey_norm_phone4( isset( $d['phone'] ) ? $d['phone'] : '' );
	$birth6 = md_survey_norm_birth6( isset( $d['birth'] ) ? $d['birth'] : '' );
	$doctor = mb_substr( trim( sanitize_text_field( isset( $d['doctor'] ) ? $d['doctor'] : '' ) ), 0, 120 );
	$staff  = mb_substr( trim( sanitize_text_field( isset( $d['staff'] ) ? $d['staff'] : '' ) ), 0, 40 );

	if ( '' === $chart )  { return new WP_Error( 'md_survey', '차트번호가 없습니다.' ); }
	if ( '' === $name )   { return new WP_Error( 'md_survey', '이름이 없습니다.' ); }
	if ( '' === $phone4 ) { return new WP_Error( 'md_survey', '휴대폰 번호(마지막 4자리)를 읽지 못했습니다 — 뒷자리가 가려진 번호일 수 있습니다 (' . $name . ').' ); }
	/* v4.21.13 · 담당직원이 비어도 명단에 넣는다 — 그 환자는 담당직원 문항 없이 설문 (원장 질문에 따라 변경) */

	$t   = md_survey_table_visit();
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, responded_at FROM $t WHERE visit_date = %s AND chart_no = %s", $date, $chart ) );
	$data = array(
		'patient_name' => $name,
		'ident_hash'   => '' !== $birth6 ? md_survey_ident_hash( $phone4, $birth6 ) : '', /* 생년월일은 있으면 저장(v4.10.8부터 선택) */
		'phone_hash'   => md_survey_phone_hash( $phone4 ),
		'source'       => $source,
	);
	if ( $row ) {
		if ( ! $row->responded_at ) { $data['doctor'] = $doctor; $data['staff'] = $staff; }
		$wpdb->update( $t, $data, array( 'id' => (int) $row->id ) );
		return array( 'id' => (int) $row->id, 'updated' => true );
	}
	$data['visit_date'] = $date;
	$data['chart_no']   = $chart;
	$data['doctor']     = $doctor;
	$data['staff']      = $staff;
	$data['token']      = wp_generate_password( 24, false );
	$data['created_at'] = current_time( 'mysql' );
	if ( ! $wpdb->insert( $t, $data ) ) { return new WP_Error( 'md_survey', '저장하지 못했습니다.' ); }
	return array( 'id' => (int) $wpdb->insert_id, 'updated' => false );
}

/* ---- 올린 파일 (v4.19.1) ---- */

/** 그날 올린 파일 한 건 (내용 제외). 없으면 null */
function md_survey_file_get( $date ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT id, visit_date, file_name, file_size, uploaded_by, uploaded_at, added, updated, skipped FROM ' . md_survey_table_file() . ' WHERE visit_date = %s', $date ) );
}

/** 파일 내용까지 */
function md_survey_file_get_full( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_survey_table_file() . ' WHERE id = %d', (int) $id ) );
}

/** 그날 파일을 바꿔 넣는다 (있으면 덮어씀) */
function md_survey_file_store( $date, $name, $content, $r, $uploader = '' ) {
	$uploader = mb_substr( trim( sanitize_text_field( (string) $uploader ) ), 0, 60 ); /* v4.19.5 · 올린 담당자 이름 (폼 입력) */
	global $wpdb;
	$t = md_survey_table_file();
	$wpdb->delete( $t, array( 'visit_date' => $date ) );
	return (bool) $wpdb->insert( $t, array(
		'visit_date'  => $date,
		'file_name'   => mb_substr( sanitize_file_name( (string) $name ), 0, 190 ),
		'file_size'   => strlen( (string) $content ),
		'content'     => base64_encode( (string) $content ), /* v4.19.2 · 이진 데이터를 wpdb 가 손대지 않게 base64 로 */
		'uploaded_by' => '' !== $uploader ? $uploader : mb_substr( wp_get_current_user()->display_name, 0, 60 ),
		'uploaded_at' => current_time( 'mysql' ),
		'added'       => (int) $r['added'],
		'updated'     => (int) $r['updated'],
		'skipped'     => (int) $r['skipped'],
	), array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d' ) );
}

/** 그날 파일과 그 명단을 지운다 (응답은 남는다) */
function md_survey_file_delete( $date ) {
	global $wpdb;
	$wpdb->delete( md_survey_table_file(), array( 'visit_date' => $date ) );
	return md_survey_visits_delete_day( $date );
}

/** 그날 명단 전체 삭제 — 응답(별도 표)은 그대로 둔다. 지운 줄 수를 돌려준다 (v4.18.9) */
function md_survey_visits_delete_day( $date ) {
	global $wpdb;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $date ) ) { return 0; }
	return (int) $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_survey_table_visit() . ' WHERE visit_date = %s', $date ) );
}

function md_survey_visit_delete( $id ) {
	global $wpdb;
	$v = md_survey_visit( $id );
	if ( ! $v ) { return false; }
	if ( $v->responded_at ) { return new WP_Error( 'md_survey', '응답이 있는 줄은 지울 수 없습니다.' ); }
	return (bool) $wpdb->delete( md_survey_table_visit(), array( 'id' => (int) $id ) );
}

/** 그날 명단 (응답 여부 포함) */
function md_survey_visits_on( $date ) {
	global $wpdb;
	$tv = md_survey_table_visit(); $tr = md_survey_table_response();
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT v.*, r.id AS response_id, r.created_at AS answered_at FROM $tv v LEFT JOIN $tr r ON r.visit_id = v.id WHERE v.visit_date = %s ORDER BY v.id ASC", $date ) );
}

/**
 * 붙여넣은 표 → 명단. 탭 · 쉼표 구분 모두 받는다.
 * 첫 줄에 「차트」「이름」 같은 머리글이 있으면 그걸로 열을 찾고 (덴트웹 「기간별 접수환자 목록」
 * 엑셀을 통째로 붙여 넣으면 이 경우), 없으면 차트번호 · 이름 · 휴대폰 · 담당의사 · 담당직원 순서로 본다.
 * 담당직원이 빈 줄은 오류가 아니라 「건너뜀」으로 센다 — 접수 때 지정하지 않은 환자는 설문 대상이 아니다.
 */
function md_survey_import_text( $text, $date ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	$rows  = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) { continue; }
		$cols   = ( false !== strpos( $line, "\t" ) ) ? explode( "\t", $line ) : str_getcsv( $line );
		$rows[] = array_map( 'trim', $cols );
	}
	return md_survey_import_rows( $rows, $date );
}

/**
 * v4.14.4 · 엑셀 파일(.xlsx) → 명단. 덴트웹 「기간별 접수환자 목록」 엑셀저장 파일을 그대로 올린다.
 * 접수시각 열이 있으면 그 날짜를 쓰므로 여러 날이 섞인 파일도 된다.
 */
function md_survey_import_xlsx( $path, $date ) {
	require_once __DIR__ . '/xlsx-reader.php';
	$rows = md_xlsx_rows( $path );
	if ( ! is_array( $rows ) ) { return array( 'added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array( $rows ) ); }
	/* 열 번호 키 배열 → 빈 칸을 채운 순서 배열 */
	$max = 0;
	foreach ( $rows as $r ) { $max = max( $max, empty( $r ) ? 0 : max( array_keys( $r ) ) + 1 ); }
	$flat = array();
	foreach ( $rows as $r ) {
		$line = array_fill( 0, $max, '' );
		foreach ( $r as $i => $v ) { $line[ $i ] = $v; }
		$flat[] = $line;
	}
	return md_survey_import_rows( $flat, $date );
}

/** 공통 — 행 배열(첫 줄이 제목이면 제목으로 열을 찾음) → 명단 */
function md_survey_import_rows( $rows, $date ) {
	$ok = 0; $upd = 0; $skip = 0; $errs = array(); $dates = array(); $charts = array();
	$map = null;
	$aliases = array(
		'date'     => array( '접수시각', '접수일', '진료일', '날짜', 'date' ),
		'chart_no' => array( '차트번호', '차트', '등록번호', '차트no', 'chart' ),
		'name'     => array( '이름', '성명', '환자명', '환자', 'name' ),
		'phone'    => array( '휴대폰', '휴대전화', '핸드폰', '전화', '연락처', 'phone', '가운데4자리', '뒷자리' ),
		'birth'    => array( '생년월일', '생일', '주민번호', '주민', 'birth' ),
		'doctor'   => array( '담당의사', '담당원장', '의사', '원장', 'doctor' ),
		'doctors'  => array( '진료의사' ), /* v4.19 · 덴트웹 엑셀의 진료의사 열 — 여러 명이 쉼표로 들어 있다. 있으면 담당의사보다 우선 */
		'staff'    => array( '담당직원', '담당스탭', '스탭', '직원', '위생사', '어시스트', 'staff' ),
	);
	$n = 0;
	foreach ( $rows as $cols ) {
		$n++;
		if ( null === $map ) {
			/* 머리글인가 */
			$found = array();
			foreach ( $cols as $i => $c ) {
				$k = strtolower( preg_replace( '/[\s_\-\(\)]+/u', '', $c ) );
				foreach ( $aliases as $field => $als ) {
					if ( isset( $found[ $field ] ) ) { continue; }
					foreach ( $als as $a ) {
						if ( '' !== $k && false !== mb_strpos( $k, strtolower( $a ) ) ) { $found[ $field ] = $i; break; }
					}
				}
			}
			if ( isset( $found['chart_no'] ) && isset( $found['name'] ) ) { $map = $found; continue; }
			$map = array( 'chart_no' => 0, 'name' => 1, 'phone' => 2, 'doctor' => 3, 'staff' => 4 );
		}
		$d = array( 'date' => $date );
		foreach ( $map as $field => $i ) { $d[ $field ] = isset( $cols[ $i ] ) ? $cols[ $i ] : ''; }
		/* v4.21.5 · 담당 원장은 엑셀의 「담당의사」 한 명 (원장 지시). 비어 있을 때만 「진료의사」의 첫 사람 */
		if ( '' === trim( (string) ( isset( $d['doctor'] ) ? $d['doctor'] : '' ) ) && isset( $d['doctors'] ) && '' !== trim( (string) $d['doctors'] ) ) {
			$names = array_values( array_filter( array_map( 'trim', preg_split( '/[,\/·;]+/u', (string) $d['doctors'] ) ) ) );
			if ( $names ) { $d['doctor'] = $names[0]; }
		}
		unset( $d['doctors'] );
		/* 접수시각 열이 있으면 그 날짜로 (2026-10-01 11:40:32 · 20261001114032 · 엑셀 일련번호 모두 받음) */
		if ( isset( $map['date'] ) ) {
			$dv = trim( (string) $d['date'] );
			if ( preg_match( '/^(\d{4})[-.\/]?(\d{2})[-.\/]?(\d{2})/', $dv, $m ) ) { $d['date'] = $m[1] . '-' . $m[2] . '-' . $m[3]; }
			elseif ( is_numeric( $dv ) && (float) $dv > 30000 && function_exists( 'md_xlsx_serial_to_date' ) ) { $d['date'] = md_xlsx_serial_to_date( $dv ); }
			else { $d['date'] = $date; }
		}
		if ( ! isset( $d['staff'] ) || '' === trim( (string) $d['staff'] ) ) { $skip++; $d['staff'] = ''; } /* 미입력도 넣고, 세기만 한다 */
		$dates[ $d['date'] ] = true;
		$r = md_survey_visit_upsert( $d, 'import' );
		if ( ! is_wp_error( $r ) ) { $charts[ $d['date'] ][] = (string) trim( $d['chart_no'] ); } /* v4.21.39 · 다시 올릴 때 빠진 환자 정리용 */
		if ( is_wp_error( $r ) ) { $errs[] = $n . '줄: ' . $r->get_error_message(); }
		elseif ( $r['updated'] ) { $upd++; }
		else { $ok++; }
	}
	return array( 'added' => $ok, 'updated' => $upd, 'skipped' => $skip, 'errors' => $errs, 'dates' => array_keys( $dates ), 'charts' => $charts );
}

/* ============================================================
 * 응답
 * ============================================================ */

function md_survey_response_by_visit( $visit_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_survey_table_response() . ' WHERE visit_id = %d', (int) $visit_id ) );
}

/**
 * @param string $staff   실제로 평가받는 직원 (환자가 바로잡았으면 그 이름, 모르면 '')
 * @param int    $changed 0 명단 그대로 · 1 환자가 다른 직원으로 바꿈 · 2 모르겠다
 */
function md_survey_response_insert( $visit, $q1, $q2, $q3, $comment, $staff = null, $changed = 0, $qd = 0, $call = 0 ) {
	global $wpdb;
	$tr = md_survey_table_response();
	if ( null === $staff ) { $staff = $visit->staff; }
	$ok = $wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO $tr (visit_id, visit_date, chart_no, patient_name, doctor, staff, staff_orig, staff_changed, q_doctor, q_service, q_explain, q_recommend, comment, want_call, dev_hash, flags, ip, ip_hash, created_at)
		 VALUES (%d, %s, %s, %s, %s, %s, %s, %d, %d, %d, %d, %d, %s, %d, %s, %s, %s, %s, %s)",
		(int) $visit->id, $visit->visit_date, $visit->chart_no, $visit->patient_name, $visit->doctor, $staff, $visit->staff, (int) $changed,
		(int) $qd, (int) $q1, (int) $q2, (int) $q3, $comment, (int) $call, md_survey_dev_hash(), md_survey_flags_for( $visit->chart_no ), substr( (string) ( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' ), 0, 45 ), md_survey_ip_hash(), current_time( 'mysql' )
	) );
	if ( ! $ok ) { return false; } /* 0 = 이미 있음 (UNIQUE visit) */
	$wpdb->update( md_survey_table_visit(), array( 'responded_at' => current_time( 'mysql' ) ), array( 'id' => (int) $visit->id ) );
	md_survey_notify_new( (int) $wpdb->insert_id ); /* v4.21.36 · 새 응답 메일 */
	return true;
}

/**
 * v4.21.36 · 새 응답이 오면 메일 (원장 지시 — moondentaldigital@gmail.com, 설정에서 바꿀 수 있음)
 * 낮은 점수(1~2점)나 연락 요청이 있으면 제목 앞에 표시해 먼저 보이게 한다.
 */
function md_survey_notify_new( $rid ) {
	$to = trim( (string) md_survey_setting( 'notify_email' ) );
	if ( '' === $to || ! $rid ) { return; }
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_survey_table_response() . ' WHERE id = %d', (int) $rid ) );
	if ( ! $r ) { return; }
	$sc  = function ( $v ) { return (int) $v > 0 ? (int) $v . '점' : '기억나지 않음'; };
	$low = ( (int) $r->q_doctor >= 1 && (int) $r->q_doctor <= 2 ) || ( (int) $r->q_service >= 1 && (int) $r->q_service <= 2 ) || ( (int) $r->q_recommend >= 1 && (int) $r->q_recommend <= 2 );
	$tag = ( $low ? '[낮은 점수] ' : '' ) . ( ! empty( $r->want_call ) ? '[연락 원함] ' : '' );
	$subject = '[문치과병원 만족도] ' . $tag . $r->patient_name . ' · 병원 ' . $sc( $r->q_recommend );
	$body  = "새 만족도 응답이 들어왔습니다.\n\n";
	$body .= '진료일: ' . $r->visit_date . "\n";
	$body .= '환자: ' . $r->patient_name . ' (차트 ' . $r->chart_no . ")\n";
	$body .= '담당의사: ' . ( '' !== $r->doctor ? $r->doctor : '-' ) . ' — ' . $sc( $r->q_doctor ) . "\n";
	$body .= '담당직원: ' . ( '' !== $r->staff ? $r->staff : '-' ) . ' — ' . $sc( $r->q_service ) . "\n";
	$body .= '병원: ' . $sc( $r->q_recommend ) . "\n";
	$body .= '연락: ' . ( ! empty( $r->want_call ) ? '연락드려도 괜찮다고 함' : '-' ) . "\n";
	if ( '' !== trim( (string) $r->comment ) ) { $body .= "\n의견:\n" . $r->comment . "\n"; }
	$body .= "\n모든 응답 보기: " . home_url( '/직원/?app=survey_result' ) . "\n";
	wp_mail( array_map( 'trim', explode( ',', $to ) ), $subject, $body );
}

/** 응답 한 건 삭제 — 그 진료는 다시 응답할 수 있게 된다 (v4.21.36) */
function md_survey_response_delete( $rid ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT id, visit_id FROM ' . md_survey_table_response() . ' WHERE id = %d', (int) $rid ) );
	if ( ! $r ) { return false; }
	$wpdb->delete( md_survey_table_response(), array( 'id' => (int) $r->id ) );
	if ( $r->visit_id ) { $wpdb->update( md_survey_table_visit(), array( 'responded_at' => null ), array( 'id' => (int) $r->visit_id ) ); }
	return true;
}

/** v4.21.36 · 원장이 시험으로 쓴 응답 정리 (1회) — 2026-10-02 · 차트 171500(이창률) · 77840(문지현) */
add_action( 'init', function () {
	if ( get_option( 'md_survey_cleanup_v42136' ) ) { return; }
	if ( ! add_option( 'md_survey_cleanup_v42136', time(), '', 'no' ) ) { return; }
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT id FROM " . md_survey_table_response() . " WHERE visit_date = '2026-10-02' AND chart_no IN ('171500','77840')" );
	foreach ( (array) $ids as $id ) { md_survey_response_delete( (int) $id ); }
}, 40 );

function md_survey_responses( $from, $to, $staff = '', $doctor = '' ) {
	global $wpdb;
	$tr  = md_survey_table_response();
	$sql = "SELECT * FROM $tr WHERE visit_date BETWEEN %s AND %s";
	$args = array( $from, $to );
	if ( '' !== $staff ) { $sql .= ' AND staff = %s'; $args[] = $staff; }
	if ( '' !== $doctor ) { $sql .= ' AND ( doctor = %s OR doctor LIKE %s )'; $args[] = $doctor; $args[] = '%' . $wpdb->esc_like( $doctor ) . '%'; } /* v4.21.30 · 담당의사로 보기 */
	$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 2000';
	return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
}

/** 스탭별 집계 — 응답 수 · 평균 · 5점 비율 · 낮은 점수 건수 */
function md_survey_stats( $from, $to ) {
	global $wpdb;
	$tr = md_survey_table_response();
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT staff,
			SUM(q_service > 0) AS n,
			AVG(NULLIF(q_service,0)) AS avg_service, AVG(NULLIF(q_recommend,0)) AS avg_recommend,
			SUM(q_service = 5) AS top_service,
			SUM(q_service BETWEEN 1 AND 2) AS low_n,
			SUM(comment IS NOT NULL AND comment <> '') AS comment_n,
			SUM(staff_changed = 1) AS changed_n
		 FROM $tr WHERE visit_date BETWEEN %s AND %s AND staff <> '' AND q_service > 0 GROUP BY staff ORDER BY n DESC, avg_service DESC", $from, $to ) );
}

/** 응답에 나온 담당의사 이름 목록 (보기 필터용) */
function md_survey_doctor_names() {
	global $wpdb;
	$out = array();
	foreach ( (array) $wpdb->get_col( 'SELECT DISTINCT doctor FROM ' . md_survey_table_response() . " WHERE doctor <> ''" ) as $d ) {
		foreach ( array_filter( array_map( 'trim', explode( '·', (string) $d ) ) ) as $n ) { $out[ $n ] = true; }
	}
	$out = array_keys( $out );
	sort( $out );
	return $out;
}

/** v4.21.4 · 원장별 — 그날 진료한 원장이 여럿이면 각자에게 같은 점수로 센다 */
function md_survey_stats_doctor( $from, $to ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT doctor, q_doctor FROM ' . md_survey_table_response() . ' WHERE visit_date BETWEEN %s AND %s AND q_doctor > 0', $from, $to ) );
	$by = array();
	foreach ( (array) $rows as $r ) {
		foreach ( array_filter( array_map( 'trim', explode( '·', (string) $r->doctor ) ) ) as $d ) {
			if ( ! isset( $by[ $d ] ) ) { $by[ $d ] = array( 'n' => 0, 'sum' => 0, 'top' => 0, 'low' => 0 ); }
			$by[ $d ]['n']++; $by[ $d ]['sum'] += (int) $r->q_doctor;
			if ( 5 === (int) $r->q_doctor ) { $by[ $d ]['top']++; }
			if ( (int) $r->q_doctor <= 2 ) { $by[ $d ]['low']++; }
		}
	}
	uasort( $by, function ( $a, $b ) { return $b['n'] - $a['n']; } );
	return $by;
}

/* ============================================================
 * 환자용 화면 — /만족도/ · /survey/
 * ============================================================ */

function md_survey_is_public_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	if ( ! $path ) { return false; }
	$path = trim( urldecode( $path ), '/' );
	return (bool) preg_match( '#^(만족도|survey)$#u', $path );
}

function md_survey_public_intercept() {
	if ( ! md_survey_is_public_path() ) { return; }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	status_header( 200 );
	md_survey_device_cookie(); /* v4.21.8 · 같은 기기에서 여러 환자 응답을 잡기 위한 기기 표시 */
	md_survey_public_render();
	exit;
}
add_action( 'template_redirect', 'md_survey_public_intercept', 1 );

/** 본인 확인 실패 횟수 — 5번이면 30분 잠금 */
function md_survey_fail_key() { return 'md_sv_fail_' . md_survey_ip_hash(); }
function md_survey_is_locked() { return (int) get_transient( md_survey_fail_key() ) >= 5; }
function md_survey_note_fail() {
	$k = md_survey_fail_key();
	$n = (int) get_transient( $k ) + 1;
	set_transient( $k, $n, 30 * MINUTE_IN_SECONDS );
	return $n;
}

/**
 * 환자 화면의 상태 기계
 *   identify  본인 확인 폼 (기본)
 *   form      설문 폼 (확인됨 · 서명된 토큰을 지님)
 *   done      제출 완료
 *   already   이미 응답한 진료
 *   locked    확인 실패 5회
 */
function md_survey_public_render() {
	$step  = 'identify';
	$err   = '';
	$visit = null;
	$win   = max( 1, (int) md_survey_setting( 'window_days' ) );
	$today = current_time( 'Y-m-d' );
	$since = gmdate( 'Y-m-d', strtotime( $today ) - $win * DAY_IN_SECONDS );

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';

	/* v4.10.1 · 알림톡 링크에 이름이 실려 오면(?n=#환자명#) 휴대폰 마지막 4자리만 묻는다.
	 * 이름은 GET 으로 받아 폼에 숨겨 두고, POST 때 그대로 돌려받는다. */
	$pname = '';
	if ( isset( $_GET['n'] ) )  { $pname = md_survey_norm_name( wp_unslash( $_GET['n'] ) ); }
	if ( isset( $_POST['pn'] ) ) { $pname = md_survey_norm_name( wp_unslash( $_POST['pn'] ) ); }
	$pname = mb_substr( $pname, 0, 40 );

	/* v4.21.2 · 관리자 미리보기 — /survey/?preview=1&doc=문은수&staff=김정애 (저장되지 않음) */
	if ( 'GET' === $method && isset( $_GET['preview'] ) && md_survey_can_manage() ) {
		$visit = (object) array(
			'id' => 0, 'visit_date' => $today, 'chart_no' => '', 'responded_at' => null,
			'patient_name' => '홍길동',
			'doctor'       => isset( $_GET['doc'] ) ? sanitize_text_field( wp_unslash( $_GET['doc'] ) ) : '○○○', /* v4.21.12 · 예시 화면은 이름 대신 ○○○ */
			'staff'        => isset( $_GET['staff'] ) ? sanitize_text_field( wp_unslash( $_GET['staff'] ) ) : '○○○',
		);
		$step = 'form';
		$err  = '미리보기 화면입니다. 제출해도 저장되지 않습니다.';
		if ( 'done' === $_GET['preview'] ) { $step = 'done'; $err = ''; } /* v4.21.19 · 감사 화면 미리보기 ?preview=done */
	}

	/* 환자별 전용 링크 (?t=…) — 본인 확인 없이 바로 설문 */
	if ( 'GET' === $method && isset( $_GET['t'] ) ) {
		$v = md_survey_visit_by_token( wp_unslash( $_GET['t'] ) );
		if ( $v && $v->visit_date >= $since ) {
			$visit = $v;
			$step  = $v->responded_at ? 'already' : 'form';
		} else {
			$err = '링크가 만료되었거나 올바르지 않습니다.';
		}
	}

	if ( 'POST' === $method && isset( $_POST['md_sv'] ) ) {
		$action = sanitize_key( wp_unslash( $_POST['md_sv'] ) );

		if ( 'identify' === $action ) {
			if ( md_survey_is_locked() ) {
				$step = 'locked';
			} else {
				/* v4.10.8 · 본인 확인은 이름 + 휴대폰 마지막 4자리. 이름은 링크(?n=)에서 오거나, 없으면 환자가 적는다. */
				$phone4 = md_survey_norm_phone4( isset( $_POST['phone4'] ) ? wp_unslash( $_POST['phone4'] ) : '' );
				$typed  = isset( $_POST['name'] ) ? mb_substr( md_survey_norm_name( wp_unslash( $_POST['name'] ) ), 0, 40 ) : '';
				$from_link = '' !== $pname;
				if ( ! $from_link ) { $pname = $typed; }
				$agree  = true; /* v4.21.8 · 「동의하고 설문 시작」 버튼을 누르는 것이 동의 (체크칸 제거 — 입력 최소화) */
				if ( ! $agree ) {
					$err = '개인정보 수집·이용에 동의해 주세요.';
				} elseif ( '' === $pname || '' === $phone4 ) {
					$err = '이름과 휴대전화 마지막 4자리를 확인해 주세요.';
				} else {
					global $wpdb;
					$tv   = md_survey_table_visit();
					/* v4.21.27 · 덴트웹은 동명이인에게 「김기관B」처럼 접미사를 붙인다. 알림톡의 #환자명# 에는
					 * 접미사가 빠질 수 있어, 전화 해시로 먼저 찾고 이름은 접미사를 뗀 채로 비교한다 */
					$cand = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM $tv WHERE phone_hash = %s AND visit_date BETWEEN %s AND %s ORDER BY visit_date DESC, id DESC",
						md_survey_phone_hash( $phone4 ), $since, $today ) );
					$want = md_survey_name_key( $pname );
					$rows = array();
					foreach ( (array) $cand as $c ) { if ( md_survey_name_key( $c->patient_name ) === $want ) { $rows[] = $c; } }
					if ( empty( $rows ) ) {
						$n = md_survey_note_fail();
						$step = $n >= 5 ? 'locked' : 'identify';
						if ( 'identify' === $step ) {
							$err = '최근 ' . $win . '일 안의 진료 기록에서 일치하는 분을 찾지 못했습니다. 휴대전화 번호를 다시 확인해 주세요. 진료 직후라면 잠시 후 다시 시도해 주세요.';
							if ( ! $from_link ) { $pname = ''; } /* 링크로 이름이 왔으면 이름 칸을 열지 않는다 (v4.21.27) */
						}
					} else {
						delete_transient( md_survey_fail_key() );
						$open = null;
						foreach ( $rows as $r ) { if ( ! $r->responded_at ) { $open = $r; break; } }
						if ( $open ) { $visit = $open; $step = 'form'; }
						else { $visit = $rows[0]; $step = 'already'; }
					}
				}
			}
		} elseif ( 'submit' === $action ) {
			$vid = md_survey_verify_sign( isset( $_POST['tok'] ) ? wp_unslash( $_POST['tok'] ) : '' );
			$v   = $vid ? md_survey_visit( $vid ) : null;
			if ( ! $v ) {
				$err = '설문 화면이 만료되었습니다. 처음부터 다시 진행해 주세요.';
			} else {
				$visit = $v;
				/* v4.21.4 · 원장(q_doctor) · 선생님(q_staff → q_service) · 병원(q_hospital → q_recommend), 모두 1~5 */
				/* v4.21.7 · 원장·담당직원은 선택(0 = 잘 기억나지 않아요 · 무응답), 병원은 필수 */
				$clip = function ( $k ) { $x = isset( $_POST[ $k ] ) ? (int) $_POST[ $k ] : 0; return ( $x >= 1 && $x <= 5 ) ? $x : 0; };
				$qd = $clip( 'q_doctor' );
				$q1 = $clip( 'q_staff' );
				$q2 = 0;
				$q3 = isset( $_POST['q_hospital'] ) ? (int) $_POST['q_hospital'] : 0;
				$has_doc = '' !== trim( (string) $v->doctor );
				$cm = isset( $_POST['comment'] ) ? mb_substr( trim( sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) ), 0, 1000 ) : '';
				$call = ! empty( $_POST['want_call'] ) ? 1 : 0; /* v4.21.17 · 다시 「연락드려도 괜찮아요」 체크 방식 (원장 지시) */
				if ( $v->responded_at || md_survey_response_by_visit( $v->id ) ) {
					$step = 'already';
				} elseif ( $q3 < 1 || $q3 > 5 ) {
					$step = 'form';
					$err  = '「오늘 하루는 어떠셨나요?」 문항을 골라 주세요.';
				} else {
					$step = md_survey_response_insert( $v, $q1, $q2, $q3, $cm, $v->staff, 0, $qd, $call ) ? 'done' : 'already';
				}
			}
		}
	}

	md_survey_public_html( $step, $visit, $err, $win, $pname );
}

function md_survey_public_html( $step, $visit, $err, $win, $pname = '' ) {
	$name = $visit ? $visit->patient_name : '';
	$date_label = $visit ? date_i18n( 'n월 j일', strtotime( $visit->visit_date ) ) : '';
	?><!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#FFFAF4" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1B1310" media="(prefers-color-scheme: dark)">
<title>진료 만족도 조사 · 한아의료재단 문치과병원</title>
<style>
:root{--bg:#FFFAF4;--card:#fff;--text:#3D2F26;--sub:#7A6B5F;--mute:#A89685;--line:#EDDFD0;--primary:#D88062;--primary-dk:#C06A4C;--accent:#6B8F72;--danger:#C66B5E;--soft:#FBF2E8;--radius:16px}
@media (prefers-color-scheme:dark){:root{--bg:#1B1310;--card:#261C17;--text:#F3E9DF;--sub:#CDBBAD;--mute:#8F7F72;--line:#3D2E27;--soft:#2F231D}}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--text);font:16px/1.6 -apple-system,BlinkMacSystemFont,"Apple SD Gothic Neo","Pretendard","Noto Sans KR","Malgun Gothic",sans-serif;word-break:keep-all}
.sv{max-width:560px;margin:0 auto;padding:20px 16px 48px}
.sv-brand{display:flex;flex-direction:column;gap:2px;margin:6px 0 18px}
.sv-brand small{font-size:.74rem;font-weight:800;letter-spacing:.1em;color:var(--mute)}
.sv-brand b{font-size:1.15rem;font-weight:800}
.sv-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:22px 20px;margin-bottom:14px}
.sv-card h1{margin:0 0 8px;font-size:1.3rem;line-height:1.35}
.sv-card p{margin:0 0 10px;color:var(--sub)}
.sv-who{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;margin:14px 0 4px;padding:14px 16px;border-radius:12px;background:var(--soft);font-size:.98rem}
.sv-who span{color:var(--mute);font-size:.8rem;font-weight:800;letter-spacing:.06em;align-self:center}
.sv-who b{font-weight:800}
.sv-who b.staff{font-size:1.15rem;color:var(--primary-dk)}
.sv-date{margin:12px 0 0 !important;font-size:.88rem;font-weight:700;color:var(--mute) !important}
/* v4.21.3 · 상반신이 보이는 세로 사진 카드 */
.sv-people{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:10px 0 6px}
.sv-person{margin:0;display:flex;flex-direction:column;gap:8px;text-align:center}
.sv-person__ph{position:relative;display:block;aspect-ratio:3/4;border-radius:16px;overflow:hidden;background:var(--soft)}
.sv-person__ph img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:50% 12%}
.sv-person__ph em{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-style:normal;font-size:2.6rem;font-weight:800;color:var(--mute)}
.sv-person__ph img~em{display:none}
.sv-person__ph.is-noimg em{display:flex}
.sv-person figcaption b{display:block;font-size:1.05rem;font-weight:800}
.sv-person figcaption small{display:block;margin-top:2px;font-size:.8rem;color:var(--mute)}
.sv-fix{margin:6px 0 2px;padding:10px 14px;border-radius:12px;border:1px dashed var(--line);font-size:.92rem}
.sv-fix summary{cursor:pointer;color:var(--sub);font-weight:700}
.sv-fix p{margin:8px 0 10px;font-size:.85rem}
.sv-pick{display:grid;grid-template-columns:repeat(auto-fill,minmax(84px,1fr));gap:8px}
.sv-pick label{position:relative;display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px 4px;border:1px solid var(--line);border-radius:12px;background:var(--card);cursor:pointer;text-align:center}
.sv-pick input{position:absolute;opacity:0;width:1px;height:1px}
.sv-pick label:has(input:checked){border-color:var(--primary);box-shadow:0 0 0 2px var(--primary) inset}
.sv-pick__ph{display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;overflow:hidden;background:var(--soft)}
.sv-pick__ph img{width:100%;height:100%;object-fit:cover;object-position:50% 15%}
.sv-pick__ph em{font-style:normal;font-weight:800;color:var(--mute)}
.sv-pick b{font-size:.88rem}
.sv-pick small{font-size:.7rem;color:var(--mute)}
.sv-hint{margin:6px 0 0 !important;font-size:.8rem;color:var(--mute) !important}
.sv-field{display:block;margin:14px 0}
.sv-field>span{display:block;margin-bottom:6px;font-size:.86rem;font-weight:700;color:var(--sub)}
.sv-field input[type=tel]{width:100%;font:inherit;font-size:1.25rem;letter-spacing:.12em;min-height:54px;padding:10px 16px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--text)}
.sv-phone{display:flex;align-items:center;gap:8px;font-size:1.35rem;font-weight:800;letter-spacing:.06em;color:var(--sub)}
.sv-phone__fix.is-hidden{color:var(--mute);letter-spacing:.1em}
.sv-phone__dash{color:var(--mute)}
.sv-phone__br{font-size:1.9rem;font-weight:400;color:var(--primary);margin:0 -4px;line-height:1}
.sv-phone input[type=tel]{flex:0 0 6.6em;width:6.6em;min-width:0;text-align:left;font-weight:800;font-size:1.35rem;letter-spacing:.2em;padding:10px 6px 10px 10px;border:0;border-bottom:2px solid var(--primary);border-radius:0;background:transparent}
.sv-phone input::placeholder{color:var(--mute);opacity:.45;letter-spacing:.25em}
.sv-field>small{display:block;margin-top:6px;font-size:.8rem;color:var(--mute)}
.sv-field input:focus{outline:2px solid var(--primary);outline-offset:0;border-color:var(--primary)}
.sv-field small{display:block;margin-top:5px;font-size:.8rem;color:var(--mute)}
.sv-agree{display:flex;gap:10px;align-items:flex-start;margin:16px 0 6px;font-size:.9rem;color:var(--sub)}
.sv-agree input{width:22px;height:22px;margin:2px 0 0;flex:none;accent-color:var(--primary)}
.sv-consent{margin:8px 0 0;padding:12px 14px;border-radius:10px;background:var(--soft);font-size:.8rem;line-height:1.6;color:var(--sub)}
.sv-consent-d{margin-top:12px;font-size:.82rem;color:var(--mute)}
.sv-consent-d summary{cursor:pointer;text-align:center}
.sv-consent b{color:var(--text)}
.sv-btn{display:block;width:100%;min-height:56px;margin-top:18px;border:0;border-radius:14px;background:var(--primary);color:#fff;font:inherit;font-size:1.1rem;font-weight:800;cursor:pointer}
.sv-btn:active{background:var(--primary-dk)}
.sv-err{margin:0 0 14px;padding:12px 14px;border-radius:10px;background:rgba(198,107,94,.1);border:1px solid var(--danger);color:var(--danger);font-size:.92rem}
.sv-q{margin:22px 0 0}
.sv-q:first-of-type{margin-top:14px}
.sv-q h2{margin:0 0 4px;font-size:1.05rem;line-height:1.45}
.sv-q h2 em{font-style:normal;color:var(--primary-dk)}
.sv-q p{margin:0 0 10px;font-size:.84rem;color:var(--mute)}
.sv-scale{display:grid;gap:8px}
.sv-scale--5{grid-template-columns:repeat(5,1fr)}
.sv-scale--11{grid-template-columns:repeat(11,1fr);gap:4px}
.sv-scale label{position:relative;display:block}
.sv-scale input{position:absolute;opacity:0;width:1px;height:1px}
.sv-scale span{display:flex;align-items:center;justify-content:center;min-height:52px;border:1px solid var(--line);border-radius:12px;background:var(--card);font-weight:800;font-size:1.05rem;color:var(--sub);cursor:pointer;user-select:none}
.sv-scale--11 span{min-height:44px;border-radius:9px;font-size:.92rem}
.sv-scale input:checked+span{background:var(--primary);border-color:var(--primary);color:#fff}
.sv-scale input:focus-visible+span{outline:2px solid var(--primary);outline-offset:2px}
.sv-q h2 small.sv-opt,.sv-q h2 small.sv-req{margin-left:4px;padding:1px 8px;border-radius:999px;font-size:.72rem;font-weight:700;vertical-align:2px}
.sv-q h2 small.sv-opt{background:var(--soft);color:var(--mute)}
.sv-q h2 small.sv-req{background:rgba(216,128,98,.14);color:var(--primary-dk)}
.sv-unsure{display:block;margin-top:8px}
.sv-unsure input{position:absolute;opacity:0;width:1px;height:1px}
.sv-unsure span{display:flex;align-items:center;justify-content:center;min-height:44px;border:1px dashed var(--line);border-radius:12px;background:var(--card);font-size:.92rem;font-weight:700;color:var(--mute);cursor:pointer}
.sv-unsure input:checked+span{border-style:solid;border-color:var(--sub);background:var(--soft);color:var(--text)}
.sv-unsure input:focus-visible+span{outline:2px solid var(--primary);outline-offset:2px}
.sv-call{display:flex;gap:8px;align-items:flex-start;margin-top:12px;font-size:.88rem;color:var(--sub);cursor:pointer}
.sv-call input{width:20px;height:20px;margin:1px 0 0;flex:none;accent-color:var(--primary)}
.sv-ends{display:flex;justify-content:space-between;margin-top:6px;font-size:.76rem;color:var(--mute)}
.sv-q textarea{width:100%;font:inherit;min-height:110px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--text);resize:vertical}
.sv-q textarea:focus{outline:2px solid var(--primary);outline-offset:0;border-color:var(--primary)}
.sv-done{text-align:center;padding:36px 20px}
.sv-chat{color:var(--primary-dk);font-weight:700;word-break:break-all}
.sv-time{display:inline-block;margin:2px 0 10px !important;padding:4px 12px;border-radius:999px;background:var(--soft);font-size:.86rem;font-weight:700;color:var(--primary-dk) !important}
.sv-done p{word-break:keep-all;text-wrap:balance;line-height:1.75}
.sv-done__sign{margin-top:18px !important;padding-top:16px;border-top:1px solid var(--line);font-weight:700;color:var(--text) !important}
.sv-done .mark{width:64px;height:64px;margin:0 auto 14px;border-radius:50%;background:rgba(107,143,114,.15);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:2rem}
.sv-done h1{font-size:1.35rem}
.sv-review{margin-top:18px;padding-top:16px;border-top:1px dashed rgba(0,0,0,.12)}
.sv-review p{font-size:.92rem;margin:0 0 12px}
.sv-review__btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:0 24px;border-radius:999px;background:#4285F4;color:#fff;font-weight:700;text-decoration:none}
.sv-review__btn:hover{background:#3367D6;color:#fff}
.sv-foot{margin-top:18px;text-align:center;font-size:.78rem;color:var(--mute)}
.sv-foot a{color:inherit}
</style>
</head>
<body>
<main class="sv">
	<div class="sv-brand"><small>한아의료재단</small><b>문치과병원 진료 만족도 조사</b></div>
<?php if ( '' !== $err ) : ?>
	<div class="sv-err" role="alert"><?php echo esc_html( $err ); ?></div>
<?php endif; ?>

<?php if ( 'locked' === $step ) : ?>
	<section class="sv-card sv-done">
		<h1>잠시 후 다시 시도해 주세요</h1>
		<p>본인 확인이 여러 번 맞지 않아 30분 동안 참여를 멈췄습니다.<br>입력 정보가 확실한데도 계속 실패하면 병원 데스크에 말씀해 주세요.</p>
	</section>

<?php elseif ( 'done' === $step ) : /* v4.21.20 · 감사 화면 문구 (원장 지정) · 구글 리뷰 버튼 제거 */ ?>
	<section class="sv-card sv-done">
		<div class="mark" aria-hidden="true">✓</div>
		<h1>소중한 말씀 감사합니다</h1>
		<p><?php echo esc_html( $name ); ?>님이 남겨 주신 이야기는<br>더 편안한 진료를 만드는 데 소중히 쓰겠습니다.</p>
		<p>궁금한 점이 있으시면<br>언제든 병원으로 연락 주세요.<br><a class="sv-chat" href="http://pf.kakao.com/_VTcgE/chat" target="_blank" rel="noopener">http://pf.kakao.com/_VTcgE/chat</a></p>
		<p class="sv-done__sign">한아의료재단 문치과병원은<br>최고의 진료를 위해 최선의 노력을 다하겠습니다.</p>
	</section>

<?php elseif ( 'already' === $step ) : ?>
	<section class="sv-card sv-done">
		<h1>이미 응답하셨습니다</h1>
		<p><?php echo esc_html( $date_label ); ?> 진료에 대한 만족도 조사는 한 번만 참여할 수 있습니다.<br>소중한 의견 감사합니다.</p>
	</section>

<?php elseif ( 'form' === $step && $visit ) :
	$tok   = md_survey_sign( $visit->id, time() + 2 * HOUR_IN_SECONDS );
	$staff = $visit->staff;
	?>
	<?php /* v4.21.4 · 원장·선생님·병원 각 1문항(1~5) + 주관식. 사진은 아직 쓰지 않는다. 문항은 평가보다 「환자의 경험」을 묻는 말투로 (원장 지시) */
	$docs   = '' !== $visit->doctor ? array_slice( array_values( array_filter( array_map( 'trim', explode( '·', $visit->doctor ) ) ) ), 0, 1 ) : array(); /* v4.21.5 · 담당의사는 한 명 */
	$others = array(); /* v4.21.6 · 「담당직원이 다른 분이셨나요?」 고르기 제거 (원장 지시) */
	$sel    = isset( $_POST['staff_pick'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_pick'] ) ) : '';
	$doc_titles = array_map( 'md_survey_doctor_title', $docs );
	/* v4.21.7 · 원장·담당직원 문항은 선택 + 「잘 기억나지 않아요」, 병원 문항은 필수 (원장 지시) */
	$pv = function ( $k ) { return isset( $_POST[ $k ] ) && '' !== $_POST[ $k ] ? (int) $_POST[ $k ] : -1; };
	$scale = function ( $field, $label, $lo, $hi, $req = true, $unsure = false ) use ( $pv ) { ?>
		<div class="sv-scale sv-scale--5" role="radiogroup" aria-label="<?php echo esc_attr( $label ); ?>">
			<?php for ( $i = 1; $i <= 5; $i++ ) : ?><label><input type="radio" name="<?php echo esc_attr( $field ); ?>" value="<?php echo $i; ?>"<?php echo $req ? ' required' : ''; ?> <?php checked( $i, $pv( $field ) ); ?>><span><?php echo $i; ?></span></label><?php endfor; ?>
		</div>
		<div class="sv-ends"><span><?php echo esc_html( $lo ); ?></span><span><?php echo esc_html( $hi ); ?></span></div>
		<?php if ( $unsure ) : ?>
			<label class="sv-unsure"><input type="radio" name="<?php echo esc_attr( $field ); ?>" value="0" <?php checked( 0, $pv( $field ) ); ?>><span>잘 기억나지 않아요</span></label>
		<?php endif; ?>
	<?php };
	$qn = 0;
	?>
	<form method="post" class="sv-card" action="<?php echo esc_url( md_survey_public_url() ); ?>">
		<input type="hidden" name="md_sv" value="submit">
		<input type="hidden" name="tok" value="<?php echo esc_attr( $tok ); ?>">
		<h1><?php echo esc_html( $name ); ?>님, 오늘 진료 잘 받으셨나요?</h1>
		<p>오늘 하루가 어떠셨는지 편하게 들려주세요. 30초면 됩니다.</p>

		<div class="sv-who">
			<span>진료일</span><b><?php echo esc_html( date_i18n( 'Y년 n월 j일 (D)', strtotime( $visit->visit_date ) ) ); ?></b>
			<?php if ( $doc_titles ) : ?><span>담당의사</span><b><?php echo esc_html( preg_replace( '/님$/u', '', $doc_titles[0] ) ); /* v4.21.15 · 「○○○ 원장」 */ ?></b><?php endif; ?>
			<?php if ( '' !== trim( (string) $staff ) ) : ?><span>담당직원</span><b><?php echo esc_html( md_survey_staff_title( $staff ) ); ?></b><?php endif; ?>
		</div>

		<?php $qn++; /* v4.21.18 · 담당의사가 엑셀에 없어도 문항은 항상 */ ?>
		<div class="sv-q">
			<h2><?php echo $qn; ?>. 오늘 <?php echo ( $doc_titles && false !== strpos( $doc_titles[0], '병원장님' ) ) ? '병원장님' : '원장님'; ?>께 진료받으시는 동안 마음이 편안하셨나요? <small class="sv-opt">선택</small></h2>
			<?php $scale( 'q_doctor', '담당의사', '조금 불편했어요', '아주 편안했어요', false, true ); ?>
		</div>

		<?php $qn++; /* v4.21.18 · 담당직원이 엑셀에 없어도 문항은 항상 (원장 지시) */ ?>
		<div class="sv-q">
			<h2><?php echo $qn; ?>. 곁에서 도와드린 담당직원 덕분에 진료가 수월하셨나요? <small class="sv-opt">선택</small></h2>
			<?php $scale( 'q_staff', '담당직원', '조금 아쉬웠어요', '아주 든든했어요', false, true ); ?>
		</div>

		<?php $qn++; ?>
		<div class="sv-q">
			<h2><?php echo $qn; ?>. 접수부터 귀가까지, 문치과병원에서의 오늘 하루는 어떠셨나요? <small class="sv-req">필수</small></h2>
			<?php $scale( 'q_hospital', '병원', '아쉬웠어요', '아주 좋았어요', true, false ); ?>
		</div>

		<?php $qn++; ?>
		<div class="sv-q">
			<h2><?php echo $qn; ?>. 고마웠던 점이나 바라는 점을 들려주세요 <small class="sv-opt">선택</small></h2>
			<textarea name="comment" maxlength="1000" placeholder="예) 아프지 않게 살펴 주셔서 고마웠어요 · 대기 시간이 조금 길었어요"><?php echo isset( $_POST['comment'] ) ? esc_textarea( wp_unslash( $_POST['comment'] ) ) : ''; ?></textarea>
			<p class="sv-hint">고마운 마음은 해당 분께 꼭 전해 드릴게요.</p>
			<label class="sv-call"><input type="checkbox" name="want_call" value="1" <?php checked( ! empty( $_POST['want_call'] ) ); ?>><span>이 내용으로 병원에서 연락드려도 괜찮아요</span></label>
		</div>

		<button type="submit" class="sv-btn">보내기</button>
		<p class="sv-foot">응답은 병원 관리자만 봅니다 · 이 진료에 대한 응답은 한 번만 보낼 수 있어요.</p>
	</form>

<?php else : ?>
	<form method="post" class="sv-card" action="<?php echo esc_url( md_survey_public_url() ); ?>" autocomplete="off">
		<input type="hidden" name="md_sv" value="identify">
		<?php if ( '' !== $pname ) : ?>
		<input type="hidden" name="pn" value="<?php echo esc_attr( $pname ); ?>">
		<h1><?php echo esc_html( $pname ); ?>님, 본인 확인</h1>
		<p class="sv-time">⏱ 약 30초면 끝나요</p>
		<p>진료받으신 분만 참여할 수 있습니다.</p>
		<?php else : ?>
		<h1>진료받으신 본인 확인</h1>
		<p class="sv-time">⏱ 약 30초면 끝나요</p>
		<p>진료받으신 분만 참여할 수 있습니다. 이름과 휴대전화 마지막 4자리를 확인합니다.</p>
		<label class="sv-field">
			<span>이름</span>
			<input type="text" name="name" maxlength="40" required placeholder="이름을 적어 주세요" id="sv-name" autocomplete="off" autofocus>
			<small>병원에 등록된 이름</small>
		</label>
		<?php endif; ?>
		<?php /* v4.21.9 · 전화번호 모양 그대로 — 010 - [○○○○] - ●●●● (원장 제안) */ ?>
		<div class="sv-field">
			<span id="sv-ph-l">휴대전화 마지막 4자리</span>
			<div class="sv-phone" aria-hidden="false">
				<span class="sv-phone__fix">010</span><span class="sv-phone__dash">-</span><span class="sv-phone__fix is-hidden">●●●●</span><span class="sv-phone__dash">-</span>
				<span class="sv-phone__br" aria-hidden="true">[</span><input type="tel" name="phone4" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required placeholder="○○○○" autocomplete="off" aria-labelledby="sv-ph-l" <?php echo '' !== $pname ? 'autofocus' : ''; ?>><span class="sv-phone__br" aria-hidden="true">]</span>
			</div>
			<small>○ 자리에 휴대전화 번호 마지막 4자리를 넣어 주세요</small>
		</div>
		<?php /* v4.21.8 · 체크칸 없이 버튼으로 동의 — 입력 최소화. 링크로 이름이 왔으면 4자리를 다 치는 순간 넘어간다 */ ?>
		<button type="submit" class="sv-btn">동의하고 설문 시작</button>
		<details class="sv-consent-d">
			<summary>개인정보 수집·이용 안내</summary>
			<div class="sv-consent">
				<b>수집 항목</b> 이름 · 휴대전화 마지막 4자리(본인 확인에만 사용, 저장하지 않음), 설문 응답<br>
				<b>이용 목적</b> 진료 만족도 조사와 서비스 개선<br>
				<b>보유 기간</b> 진료일로부터 2년<br>
				동의하지 않으시면 이 화면을 닫으시면 됩니다. 응답은 병원 관리자만 열람합니다.
			</div>
		</details>
	</form>
	<?php if ( '' === $pname ) : /* v4.21.31 · 주소가 ?n=#{이름} 처럼 # 로 시작하면 브라우저가 # 뒤를 서버에 보내지 않는다 — 화면에서 꺼내 이름 칸을 채운다 */ ?>
	<script>
	(function(){var h=location.hash;if(!h)return;var v='';try{v=decodeURIComponent(h)}catch(e){v=h}v=v.replace(/[#{}\s]/g,'');var i=document.getElementById('sv-name');if(i&&v&&/[가-힣]/.test(v)){i.value=v;var p=document.querySelector('input[name="phone4"]');if(p)p.focus();}})();
	</script>
	<?php endif; ?>
	<?php if ( '' !== $pname ) : ?>
	<script>
	(function(){var i=document.querySelector('input[name="phone4"]');if(!i)return;i.addEventListener('input',function(){var v=i.value.replace(/\D/g,'');if(v!==i.value)i.value=v;if(v.length===4){i.blur();i.form.submit();}});})();
	</script>
	<?php endif; ?>
	<p class="sv-foot">진료일로부터 <?php echo (int) $win; ?>일 안에만 참여할 수 있습니다.</p>
<?php endif; ?>
	<p class="sv-foot">한아의료재단 문치과병원 · <a href="<?php echo esc_url( home_url( '/' ) ); ?>">moondental.co.kr</a></p>
</main>
</body>
</html><?php
}

/* ============================================================
 * 연동 — 덴트웹 접수 명단을 프로그램이 자동으로 올릴 때 쓰는 REST
 *   POST /wp-json/md-survey/v1/visits   헤더 X-MD-Survey-Key: <설정의 연동 키>
 *   본문 { "visits": [ { "date":"2026-09-30", "chart_no":"12345", "name":"홍길동", "phone":"01012345678", "birth":"19800101", "doctor":"이창률", "staff":"김○○" }, … ] }
 * ============================================================ */

function md_survey_rest_permission( $request ) {
	$key  = (string) $request->get_header( 'x-md-survey-key' );
	$want = (string) get_option( 'md_survey_api_key' );
	return '' !== $want && '' !== $key && hash_equals( $want, $key );
}

function md_survey_rest_visits( $request ) {
	$body = $request->get_json_params();
	$rows = isset( $body['visits'] ) && is_array( $body['visits'] ) ? $body['visits'] : array();
	$ok = 0; $upd = 0; $errs = array();
	foreach ( $rows as $i => $r ) {
		if ( ! is_array( $r ) ) { continue; }
		$res = md_survey_visit_upsert( $r, 'api' );
		if ( is_wp_error( $res ) ) { $errs[] = array( 'index' => $i, 'error' => $res->get_error_message() ); }
		elseif ( $res['updated'] ) { $upd++; } else { $ok++; }
	}
	return rest_ensure_response( array( 'added' => $ok, 'updated' => $upd, 'errors' => $errs ) );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-survey/v1', '/visits', array(
		'methods'             => 'POST',
		'callback'            => 'md_survey_rest_visits',
		'permission_callback' => 'md_survey_rest_permission',
	) );
} );

/* ============================================================
 * 직원 허브 — 폼 처리
 * ============================================================ */

function md_survey_can_view() { return function_exists( 'md_sup_can_use' ) && md_sup_can_use(); }
function md_survey_can_manage() { return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage(); }

/**
 * 직원 허브 안 주소. v4.19.7 부터 도구가 둘로 나뉜다 (원장 지시 — 데스크 화면에서 탭 제거)
 *   survey         접수수납목록 (파일 올리기) — 직원 누구나
 *   survey_result  만족도 응답 · 스탭별 집계 · 설정 — 관리자
 */
function md_survey_admin_url( $args = array() ) {
	$sv  = isset( $args['sv'] ) ? $args['sv'] : '';
	$app = in_array( $sv, array( 'responses', 'stats', 'settings' ), true ) ? 'survey_result' : 'survey';
	return md_sup_url( array_merge( array( 'app' => $app ), $args ) );
}

function md_survey_handle_post() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { return; }
	if ( ! isset( $_POST['md_survey_action'] ) ) { return; }
	if ( ! md_survey_can_view() ) { return; }

	$action = sanitize_key( wp_unslash( $_POST['md_survey_action'] ) );
	if ( ! isset( $_POST['md_survey_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_survey_nonce'] ), 'md_survey_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}

	$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : current_time( 'Y-m-d' );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { $date = current_time( 'Y-m-d' ); }
	$back = md_survey_admin_url( array( 'sv' => 'roster', 'd' => $date ) );

	switch ( $action ) {
		case 'add':
			$res = md_survey_visit_upsert( array(
				'date'     => $date,
				'chart_no' => isset( $_POST['chart_no'] ) ? wp_unslash( $_POST['chart_no'] ) : '',
				'name'     => isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : '',
				'phone'    => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
				'birth'    => isset( $_POST['birth'] ) ? wp_unslash( $_POST['birth'] ) : '',
				'doctor'   => isset( $_POST['doctor'] ) ? wp_unslash( $_POST['doctor'] ) : '',
				'staff'    => isset( $_POST['staff'] ) ? wp_unslash( $_POST['staff'] ) : '',
			) );
			if ( is_wp_error( $res ) ) { $back = add_query_arg( 'err', $res->get_error_message(), $back ); }
			else { $back = add_query_arg( 'msg', $res['updated'] ? 'updated' : 'added', $back ); }
			/* 같은 원장·스탭을 연달아 넣을 때 다시 고르지 않게 기억 */
			setcookie( 'md_survey_doctor', isset( $_POST['doctor'] ) ? sanitize_text_field( wp_unslash( $_POST['doctor'] ) ) : '', time() + DAY_IN_SECONDS, '/', '', is_ssl(), true );
			setcookie( 'md_survey_staff',  isset( $_POST['staff'] ) ? sanitize_text_field( wp_unslash( $_POST['staff'] ) ) : '',  time() + DAY_IN_SECONDS, '/', '', is_ssl(), true );
			break;

		case 'import':
			$r = md_survey_import_text( isset( $_POST['rows'] ) ? wp_unslash( $_POST['rows'] ) : '', $date );
			$back = add_query_arg( array( 'msg' => 'imported', 'a' => $r['added'], 'u' => $r['updated'], 'sk' => $r['skipped'] ), $back );
			if ( $r['errors'] ) { $back = add_query_arg( 'err', implode( ' / ', array_slice( $r['errors'], 0, 5 ) ), $back ); }
			break;

		case 'upload': /* v4.14.4 · 엑셀 파일 그대로 올리기. 파일은 읽고 바로 버린다 — 서버에 남기지 않는다 */
			$f = isset( $_FILES['xlsx'] ) ? $_FILES['xlsx'] : null;
			if ( ! $f || ! isset( $f['error'] ) || UPLOAD_ERR_OK !== (int) $f['error'] || empty( $f['tmp_name'] ) ) {
				/* v4.18.7 · 무엇이 막혔는지 그대로 보여 준다 */
				$codes = array( 1 => '서버 최대 크기 초과(upload_max_filesize)', 2 => '폼 최대 크기 초과', 3 => '일부만 전송됨', 4 => '파일이 선택되지 않음', 6 => '임시 폴더 없음', 7 => '디스크 쓰기 실패', 8 => '확장 모듈이 차단' );
				$why = ! $f ? '브라우저가 파일을 보내지 않음(폼 enctype 또는 서버 설정)' : ( isset( $codes[ (int) $f['error'] ] ) ? $codes[ (int) $f['error'] ] : '알 수 없음(' . (int) $f['error'] . ')' );
				$back = add_query_arg( 'err', '파일이 올라오지 않았습니다 — ' . $why . '. 서버 file_uploads=' . ( ini_get( 'file_uploads' ) ? 'on' : 'off' ) . ', 최대 ' . ini_get( 'upload_max_filesize' ), $back );
			} elseif ( (int) $f['size'] > 8 * 1024 * 1024 ) {
				$back = add_query_arg( 'err', '파일이 너무 큽니다 (8MB 이하).', $back );
			} elseif ( ! preg_match( '/\.xlsx$/i', (string) $f['name'] ) ) {
				$back = add_query_arg( 'err', '.xlsx 파일만 올릴 수 있습니다. 덴트웹 「엑셀저장」으로 만든 파일을 그대로 올려 주세요.', $back );
			} else {
				/* v4.21.39 · 다시 올려도 이미 응답한 환자의 기록은 지우지 않는다 — 같은 날 같은 차트번호는 고치고(응답 여부 유지), 새 파일에 없는 환자 중 응답 안 한 사람만 뺀다 */
				/* (예전에는 그날 명단을 통째로 지우고 다시 넣어, 이미 응답한 환자가 또 응답할 수 있었다) */
				$content = (string) file_get_contents( $f['tmp_name'] );
				try {
					$r = md_survey_import_xlsx( $f['tmp_name'], $date );
				} catch ( Throwable $e ) {
					error_log( 'md_survey upload: ' . $e->getMessage() );
					$r = array( 'added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array( '엑셀을 읽는 중 오류: ' . $e->getMessage() ), 'dates' => array() );
				}
				@unlink( $f['tmp_name'] );
				/* 파일 안의 접수일이 고른 날짜와 다르면 그 날짜 화면으로 보내고 알린다. 파일도 그 날짜에 보관한다 */
				$fd = ! empty( $r['dates'] ) ? $r['dates'] : array();
				$uploader = isset( $_POST['uploader'] ) ? sanitize_text_field( wp_unslash( $_POST['uploader'] ) ) : '';
				/* 새 파일에 없는 환자 중 아직 응답하지 않은 사람만 명단에서 뺀다 */
				if ( ! empty( $r['charts'] ) ) { global $wpdb; foreach ( $r['charts'] as $cd => $list ) { $list = array_values( array_unique( array_filter( $list, 'strlen' ) ) ); if ( ! $list ) { continue; } $ph = implode( ',', array_fill( 0, count( $list ), '%s' ) ); $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_survey_table_visit() . " WHERE visit_date = %s AND responded_at IS NULL AND chart_no NOT IN ($ph)", array_merge( array( $cd ), $list ) ) ); } }
				if ( ! md_survey_file_store( 1 === count( $fd ) ? $fd[0] : $date, $f['name'], $content, $r, $uploader ) ) { global $wpdb; $r['errors'][] = '파일 보관 실패: ' . ( $wpdb->last_error ? $wpdb->last_error : '원인 미상' ); }
				/* 다음에 올릴 때 이름을 다시 안 적어도 되게 기억 (30일) */
				if ( '' !== $uploader ) { setcookie( 'md_survey_uploader', $uploader, time() + 30 * DAY_IN_SECONDS, '/', '', is_ssl(), true ); }
				if ( 1 === count( $fd ) && $fd[0] !== $date ) {
					$back = md_survey_admin_url( array( 'sv' => 'roster', 'd' => $fd[0], 'fd' => $fd[0] ) );
				} elseif ( count( $fd ) > 1 ) {
					$back = add_query_arg( 'fd', implode( ',', $fd ), $back );
				}
				$back = add_query_arg( array( 'msg' => 'imported', 'a' => $r['added'], 'u' => $r['updated'], 'sk' => $r['skipped'] ), $back );
				if ( $r['errors'] ) { $back = add_query_arg( 'err', implode( ' / ', array_slice( $r['errors'], 0, 5 ) ), $back ); }
			}
			break;

		case 'delete':
			$res = md_survey_visit_delete( isset( $_POST['vid'] ) ? (int) $_POST['vid'] : 0 );
			if ( is_wp_error( $res ) ) { $back = add_query_arg( 'err', $res->get_error_message(), $back ); }
			break;

		case 'delete_day': /* v4.18.9 · 그날 올린 명단 전체 삭제 (응답은 남는다) · v4.19.1 부터 파일도 함께 */
			$n = md_survey_file_delete( $date );
			$back = add_query_arg( array( 'msg' => 'daydeleted', 'n' => (int) $n ), $back );
			break;

		case 'test_mail': /* v4.21.36 · 알림 메일 시험 */
			if ( ! md_survey_can_manage() ) { wp_die( '권한이 없습니다.' ); }
			$to = trim( (string) md_survey_setting( 'notify_email' ) );
			$ok = '' !== $to && wp_mail( array_map( 'trim', explode( ',', $to ) ), '[문치과병원 만족도] 알림 메일 시험', "새 응답 알림 메일이 이 주소로 잘 오는지 확인하는 시험 메일입니다.

" . home_url( '/직원/?app=survey_result' ) );
			$back = md_survey_admin_url( array( 'sv' => 'settings', 'msg' => $ok ? 'mailok' : 'mailfail' ) );
			break;

		case 'delete_resp': /* v4.21.36 · 응답 한 건 삭제 (관리자) */
			if ( ! md_survey_can_manage() ) { wp_die( '권한이 없습니다.' ); }
			md_survey_response_delete( isset( $_POST['rid'] ) ? (int) $_POST['rid'] : 0 );
			$back = md_survey_admin_url( array( 'sv' => 'responses', 'from' => isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '', 'to' => isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '' ) );
			break;

		case 'download': /* v4.19.1 · 올린 파일 내려받기 — 로그인한 직원만 (이 핸들러는 can_view 를 이미 지났다) */
			$file = md_survey_file_get_full( isset( $_POST['fid'] ) ? (int) $_POST['fid'] : 0 );
			if ( ! $file || null === $file->content || '' === $file->content ) { wp_die( '파일이 없습니다.' ); }
			$bin = base64_decode( $file->content, true ); if ( false === $bin ) { $bin = (string) $file->content; }
			nocache_headers();
			header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
			header( 'Content-Length: ' . strlen( $bin ) );
			header( "Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode( $file->file_name ? $file->file_name : $file->visit_date . '.xlsx' ) );
			echo $bin; // phpcs:ignore
			exit;

		case 'settings':
			if ( ! md_survey_can_manage() ) { wp_die( '권한이 없습니다.' ); }
			$s = md_survey_settings();
			$s['window_days'] = max( 1, min( 30, isset( $_POST['window_days'] ) ? (int) $_POST['window_days'] : 3 ) );
			if ( isset( $_POST['notify_email'] ) ) { $em = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', wp_unslash( $_POST['notify_email'] ) ) ) ) ); $s['notify_email'] = implode( ', ', $em ); }
			/* v4.21.24 · 화면에서 뺀 값(원장·스탭 목록, 리뷰 링크)은 건드리지 않는다 */
			update_option( 'md_survey_settings', $s, 'no' );
			if ( ! empty( $_POST['regen_key'] ) ) { update_option( 'md_survey_api_key', wp_generate_password( 40, false ), 'no' ); }
			$back = md_survey_admin_url( array( 'sv' => 'settings', 'msg' => 'saved' ) );
			break;

		case 'export':
			if ( ! md_survey_can_manage() ) { wp_die( '권한이 없습니다.' ); }
			md_survey_export_csv(
				isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '',
				isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '',
				isset( $_POST['staff'] ) ? sanitize_text_field( wp_unslash( $_POST['staff'] ) ) : '',
				isset( $_POST['doctor'] ) ? sanitize_text_field( wp_unslash( $_POST['doctor'] ) ) : ''
			);
			exit;
	}

	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_survey_handle_post', 1 );

function md_survey_export_csv( $from, $to, $staff, $doctor = '' ) {
	list( $from, $to ) = md_survey_range( $from, $to );
	$rows = md_survey_responses( $from, $to, $staff, $doctor );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="만족도조사_' . $from . '_' . $to . '.csv"' );
	echo "\xEF\xBB\xBF"; /* 엑셀이 한글을 제대로 읽도록 BOM */
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( '진료일', '차트번호', '이름', '담당의사', '담당직원', '명단상 담당직원', '담당의사 점수', '담당직원 점수', '병원 점수', '의견', '작성시각', 'IP' ) );
	foreach ( $rows as $r ) {
		fputcsv( $out, array( $r->visit_date, $r->chart_no, $r->patient_name, $r->doctor, $r->staff, isset( $r->staff_orig ) ? $r->staff_orig : '', isset( $r->q_doctor ) ? $r->q_doctor : '', $r->q_service, $r->q_recommend, (string) $r->comment, $r->created_at, isset( $r->ip ) ? $r->ip : '' ) );
	}
	fclose( $out );
}

/** 기간 인자 정리 — 비어 있으면 이번 달 */
function md_survey_range( $from, $to ) {
	$ok = function ( $d ) { return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $d ); };
	if ( ! $ok( $from ) ) { $from = current_time( 'Y-m-01' ); }
	if ( ! $ok( $to ) )   { $to = current_time( 'Y-m-d' ); }
	if ( $from > $to ) { list( $from, $to ) = array( $to, $from ); }
	return array( $from, $to );
}

/* ============================================================
 * 직원 허브 — 화면
 * ============================================================ */

function md_survey_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || ! in_array( md_sup_current_app(), array( 'survey', 'survey_result' ), true ) ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/survey.css' ) ) {
		wp_enqueue_style( 'moondental-survey', $uri . '/assets/css/survey.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/survey.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'md_survey_enqueue', 31 );

/** 결과 도구(관리자)의 탭 — 접수수납목록 화면에는 탭이 없다 (v4.19.7) */
function md_survey_tabs() {
	return array(
		'responses' => array( 'label' => '응답',        'icon' => '💬' ),
		'stats'     => array( 'label' => '개별 집계', 'icon' => '📊' ),
		'settings'  => array( 'label' => '설정',        'icon' => '⚙️' ),
	);
}

function md_survey_current_tab() {
	$t = isset( $_GET['sv'] ) ? sanitize_key( wp_unslash( $_GET['sv'] ) ) : 'responses';
	$tabs = md_survey_tabs();
	return isset( $tabs[ $t ] ) ? $t : 'responses';
}

function md_survey_notice() {
	/* 오류(err)는 직원 허브 머리글(md_sup_render_page)이 이미 보여 준다 — 두 번 나오지 않게 여기선 생략 (v4.21.26) */
	if ( ! isset( $_GET['msg'] ) ) { return; }
	$m = sanitize_key( wp_unslash( $_GET['msg'] ) );
	$map = array(
		'added'    => '명단에 넣었습니다.',
		'updated'  => '같은 날 같은 차트번호가 있어 그 줄을 고쳤습니다.',
		'saved'    => '설정을 저장했습니다.',
		'mailok'   => '시험 메일을 보냈습니다. 메일함(스팸함 포함)을 확인해 주세요.',
		'mailfail' => '메일을 보내지 못했습니다. 주소를 확인하거나, 서버 메일 설정이 필요할 수 있습니다.',
		'imported' => sprintf( '파일을 올렸습니다 — 총 %d명 (담당직원 입력 %d명 · 미입력 %d명).', ( isset( $_GET['a'] ) ? (int) $_GET['a'] : 0 ) + ( isset( $_GET['u'] ) ? (int) $_GET['u'] : 0 ), max( 0, ( isset( $_GET['a'] ) ? (int) $_GET['a'] : 0 ) + ( isset( $_GET['u'] ) ? (int) $_GET['u'] : 0 ) - ( isset( $_GET['sk'] ) ? (int) $_GET['sk'] : 0 ) ), isset( $_GET['sk'] ) ? (int) $_GET['sk'] : 0 ),
	);
	if ( isset( $map[ $m ] ) ) {
		$text = $map[ $m ];
		if ( 'imported' === $m && isset( $_GET['fd'] ) ) {
			$fd = array_filter( array_map( 'sanitize_text_field', explode( ',', wp_unslash( $_GET['fd'] ) ) ) );
			$labels = array();
			foreach ( $fd as $x ) { if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $x ) ) { $labels[] = date_i18n( 'n월 j일', strtotime( $x ) ); } }
			if ( $labels ) { $text .= ' 파일의 접수일에 따라 ' . implode( ' · ', $labels ) . ' 명단에 들어갔습니다.'; }
		}
		echo '<div class="mds-notice mds-notice--ok">' . esc_html( $text ) . '</div>';
	}
}

/** 접수수납목록 도구 — 파일 올리기 화면 하나뿐 (v4.19.7 · 탭 없음) */
function md_survey_render() {
	md_survey_notice();
	md_survey_render_roster();
}

/** 만족도 결과 도구 — 관리자. 응답 · 스탭별 집계 · 설정 */
function md_survey_render_result() {
	if ( ! md_survey_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	$tab = md_survey_current_tab();
	?>
	<nav class="mds-tabs" aria-label="만족도 결과 메뉴">
		<?php foreach ( md_survey_tabs() as $key => $t ) : ?>
			<a class="mds-tab<?php echo $tab === $key ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => $key ) ) ); ?>"<?php echo $tab === $key ? ' aria-current="page"' : ''; ?>>
				<span aria-hidden="true"><?php echo esc_html( $t['icon'] ); ?></span><?php echo esc_html( $t['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
	md_survey_notice();
	switch ( $tab ) {
		case 'stats':    md_survey_render_stats();     break;
		case 'settings': md_survey_render_settings();  break;
		default:         md_survey_render_responses(); break;
	}
}

function md_survey_remembered( $key ) {
	return isset( $_COOKIE[ 'md_survey_' . $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ 'md_survey_' . $key ] ) ) : '';
}

/** 이름 고르기 — 설정 목록이 있으면 select + 직접 입력, 없으면 입력칸 */
function md_survey_name_field( $field, $label, $list_key, $value ) {
	$list = md_survey_name_list( $list_key );
	$id   = 'dl_' . $field;
	?>
	<label class="mds-field mds-field--grow">
		<span><?php echo esc_html( $label ); ?></span>
		<input type="text" name="<?php echo esc_attr( $field ); ?>" list="<?php echo esc_attr( $id ); ?>" maxlength="40" value="<?php echo esc_attr( $value ); ?>" placeholder="이름" <?php echo 'staff' === $field ? 'required' : ''; ?>>
		<?php if ( $list ) : ?><datalist id="<?php echo esc_attr( $id ); ?>"><?php foreach ( $list as $n ) : ?><option value="<?php echo esc_attr( $n ); ?>"><?php endforeach; ?></datalist><?php endif; ?>
	</label>
	<?php
}

/** 오늘 명단 — 한 명씩 넣기 · 붙여넣기 · 그날 목록 */
/** 날짜별 명단 수 · 응답 수 · 마지막으로 올린 시각 (v4.18.5 · 날짜 띠용) */
function md_survey_day_counts( $from, $to ) {
	global $wpdb;
	$tv  = md_survey_table_visit();
	$out = array();
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT visit_date, SUM(staff <> '') AS n, SUM(staff = '') AS miss, SUM(responded_at IS NOT NULL) AS a, MAX(created_at) AS last_at FROM $tv WHERE visit_date BETWEEN %s AND %s GROUP BY visit_date", $from, $to ) );
	foreach ( (array) $rows as $r ) { $r->skipped = (int) $r->miss; $r->total = (int) $r->n + (int) $r->miss; $out[ $r->visit_date ] = $r; }
	/* v4.19.4 · 올린 파일의 「담당직원 없음」 수를 더해 총 접수 환자 수를 만든다 */
	$files = $wpdb->get_results( $wpdb->prepare(
		'SELECT visit_date, skipped, uploaded_at FROM ' . md_survey_table_file() . ' WHERE visit_date BETWEEN %s AND %s', $from, $to ) );
	foreach ( (array) $files as $f ) {
		if ( ! isset( $out[ $f->visit_date ] ) ) {
			$out[ $f->visit_date ] = (object) array( 'visit_date' => $f->visit_date, 'n' => 0, 'a' => 0, 'last_at' => $f->uploaded_at, 'skipped' => 0, 'total' => 0 );
		}
		if ( 0 === (int) $out[ $f->visit_date ]->skipped ) { $out[ $f->visit_date ]->skipped = (int) $f->skipped; } /* v4.21.13 · 옛 날짜는 파일의 건너뜀 수 */
		$out[ $f->visit_date ]->total   = (int) $out[ $f->visit_date ]->n + (int) $out[ $f->visit_date ]->skipped;
		if ( $f->uploaded_at > $out[ $f->visit_date ]->last_at ) { $out[ $f->visit_date ]->last_at = $f->uploaded_at; }
	}
	return $out;
}

/**
 * 오늘 명단 (v4.18.5 · 데스크가 매일 한 번 엑셀을 올리는 흐름에 맞춰 정리)
 *   1. 최근 2주 날짜 띠 — 올린 날(명단 수)과 안 올린 날이 한눈에 보인다
 *   2. 고른 날짜의 엑셀 올리기
 *   3. 그날 명단
 *   (한 명 넣기·붙여넣기 화면은 v4.18.6 에서 뺐다 — 원장 지시. 처리 함수는 REST·호환을 위해 남겨 둔다)
 */
function md_survey_render_roster() {
	$today = current_time( 'Y-m-d' );
	$date  = isset( $_GET['d'] ) ? sanitize_text_field( wp_unslash( $_GET['d'] ) ) : $today;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { $date = $today; }
	$rows     = md_survey_visits_on( $date );
	$answered = 0;
	foreach ( $rows as $r ) { if ( $r->response_id ) { $answered++; } }

	/* 날짜 띠 — 열흘 전부터 엿새 뒤까지 (v4.18.9 · 오늘 이후도 보이게). 고른 날짜가 그 밖이면 거기까지 */
	$start_ts = min( strtotime( $today ) - 10 * DAY_IN_SECONDS, strtotime( $date ) );
	$end_ts   = max( strtotime( $today ) + 6 * DAY_IN_SECONDS, strtotime( $date ) );
	$start    = gmdate( 'Y-m-d', $start_ts );
	$end      = gmdate( 'Y-m-d', $end_ts );
	$counts  = md_survey_day_counts( $start, $end );
	$missing = array();
	$wd = array( '일', '월', '화', '수', '목', '금', '토' );
	?>
	<div class="mds-card mdsv-days-card">
		<div class="mdsv-days">
			<?php for ( $ts = $start_ts; $ts <= $end_ts; $ts += DAY_IN_SECONDS ) :
				$d   = gmdate( 'Y-m-d', $ts );
				$w   = (int) gmdate( 'w', $ts );
				$c   = isset( $counts[ $d ] ) ? $counts[ $d ] : null;
				if ( $c )                 { $cls = 'is-ok';     $label = '총 ' . (int) $c->total . '명'; $title = sprintf( '입력 %d명 · 미입력 %d명', (int) $c->n, (int) $c->skipped ); }

				elseif ( $d > $today )    { $cls = 'is-future'; $label = '예정'; $title = '아직 오지 않은 날'; }
				elseif ( $d === $today )  { $cls = 'is-today';  $label = '아직'; $title = '오늘 — 아직 올리지 않음'; }
				else                      { $cls = 'is-none';   $label = '없음'; $title = '올리지 않음'; $missing[] = $d; }
				?>
				<a class="mdsv-day <?php echo esc_attr( $cls ); ?><?php echo $d === $date ? ' is-sel' : ''; ?><?php echo $d === $today ? ' is-now' : ''; ?>" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'roster', 'd' => $d ) ) ); ?>" title="<?php echo esc_attr( $title ); ?>">
					<span class="mdsv-day__d"><?php echo esc_html( gmdate( 'n/j', $ts ) ); ?> <small><?php echo esc_html( $wd[ $w ] ); ?></small></span>
					<span class="mdsv-day__n"><?php echo esc_html( $label ); ?></span>
				</a>
			<?php endfor; ?>
		</div>
		<div class="mdsv-days__foot">
			<form method="get" class="mdsv-datepick" action="<?php echo esc_url( md_survey_admin_url() ); ?>">
				<?php md_sup_app_field(); ?><input type="hidden" name="sv" value="roster">
				<label>다른 날짜 <input type="date" name="d" value="<?php echo esc_attr( $date ); ?>" onchange="this.form.submit()"></label>
			</form>
			<?php if ( $missing ) : ?>
				<span class="mdsv-missing">올리지 않은 날: <?php echo esc_html( implode( ' · ', array_map( function ( $x ) { return date_i18n( 'n/j', strtotime( $x ) ); }, $missing ) ) ); ?></span>
			<?php else : ?>
				<span class="mdsv-missing is-clear">최근 열흘 모두 올렸습니다.</span>
			<?php endif; ?>
		</div>
	</div>

	<?php $file = md_survey_file_get( $date ); ?>
	<form method="post" enctype="multipart/form-data" class="mds-card mdsv-upload" action="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'roster', 'd' => $date ) ) ); ?>">
		<input type="hidden" name="md_survey_action" value="upload">
		<input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_upload' ) ); ?>">
		<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
		<h2 class="mdsv-h"><?php echo esc_html( date_i18n( 'n월 j일 (D)', strtotime( $date ) ) ); ?> <small><?php echo $file ? '파일 올림' : '아직 올리지 않음'; ?></small></h2>
		<p class="mds-hint">덴트웹 접수목록 → <b>기간별 목록</b> → 오늘 → <b>엑셀저장</b>(사유: 만족도 조사 명단 · <b>인증서로 서명</b> — 서명하지 않으면 전화번호 뒷자리가 가려져 환자가 본인 확인을 못 합니다) 한 파일을 골라 올리기. 다시 올리면 새 파일로 바뀝니다.</p>
		<?php if ( md_survey_can_manage() ) : /* v4.18.7 · 관리자에게만 서버 상태 — 안 될 때 원인 찾기용 */
			$pcl = file_exists( ABSPATH . 'wp-admin/includes/class-pclzip.php' ); ?>
			<p class="mds-hint mdsv-diag">서버: 파일 업로드 <?php echo ini_get( 'file_uploads' ) ? '켜짐' : '꺼짐'; ?> · 최대 <?php echo esc_html( ini_get( 'upload_max_filesize' ) ); ?> · ZipArchive <?php echo class_exists( 'ZipArchive' ) ? '있음' : '없음'; ?> · PclZip <?php echo $pcl ? '있음' : '없음'; ?> · SimpleXML <?php echo function_exists( 'simplexml_load_string' ) ? '있음' : '없음'; ?> · PHP <?php echo esc_html( PHP_VERSION ); ?></p>
		<?php endif; ?>
		<div class="mdsv-upload__row">
			<input type="text" name="uploader" class="mdsv-upload__who" maxlength="60" required placeholder="담당자 이름" value="<?php echo esc_attr( md_survey_remembered( 'uploader' ) ); ?>" aria-label="올리는 담당자 이름">
			<input type="file" name="xlsx" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
			<button type="submit" class="mds-btn mds-btn--fill"><?php echo $file ? '새 파일로 바꾸기' : '올리기'; ?></button>
		</div>
	</form>

	<?php if ( $file || ! empty( $rows ) ) :
		/* v4.19.1 · 올린 파일 한 장 — 내려받기 · 삭제. 환자 명단은 보여 주지 않는다 (원장 지시) */
		$fname = $file && $file->file_name ? $file->file_name : date_i18n( 'n월 j일', strtotime( $date ) ) . ' 명단';
		$fsize = $file ? ( $file->file_size >= 1024 * 1024 ? number_format( $file->file_size / 1048576, 1 ) . 'MB' : number_format( $file->file_size / 1024 ) . 'KB' ) : '';
		?>
		<div class="mds-card mdsv-file">
			<div class="mdsv-file__body">
				<span class="mdsv-file__icon" aria-hidden="true">📄</span>
				<div>
					<b><?php echo esc_html( $fname ); ?><?php if ( $fsize ) : ?> <small><?php echo esc_html( $fsize ); ?></small><?php endif; ?></b>
					<?php if ( $file ) : ?><span class="mdsv-file__meta"><?php echo esc_html( date_i18n( 'n/j H:i', strtotime( $file->uploaded_at ) ) ); ?> · <?php echo esc_html( $file->uploaded_by ); ?></span><?php endif; ?>
				</div>
			</div>
			<div class="mdsv-file__btns">
				<?php if ( $file ) : ?>
				<form method="post" class="mds-inline">
					<input type="hidden" name="md_survey_action" value="download"><input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_download' ) ); ?>">
					<input type="hidden" name="fid" value="<?php echo (int) $file->id; ?>"><input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
					<button type="submit" class="mds-btn mds-btn--ghost">내려받기</button>
				</form>
				<?php endif; ?>
				<form method="post" class="mds-inline" onsubmit="return confirm('<?php echo esc_js( date_i18n( 'n월 j일', strtotime( $date ) ) ); ?> 파일과 명단 <?php echo count( $rows ); ?>명을 지울까요? 이미 받은 응답은 남습니다.');">
					<input type="hidden" name="md_survey_action" value="delete_day"><input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_delete_day' ) ); ?>">
					<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
					<button type="submit" class="mds-btn mds-btn--ghost">삭제</button>
				</form>
			</div>
		</div>
	<?php endif; ?>

	<?php
}

/** 응답 목록 (관리자) */
function md_survey_render_responses() {
	if ( ! md_survey_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	list( $from, $to ) = md_survey_range( isset( $_GET['from'] ) ? wp_unslash( $_GET['from'] ) : '', isset( $_GET['to'] ) ? wp_unslash( $_GET['to'] ) : '' );
	$staff = isset( $_GET['staff'] ) ? sanitize_text_field( wp_unslash( $_GET['staff'] ) ) : '';
	$doctor = isset( $_GET['doctor'] ) ? sanitize_text_field( wp_unslash( $_GET['doctor'] ) ) : '';
	$rows  = md_survey_responses( $from, $to, $staff, $doctor );
	global $wpdb;
	$staffs = $wpdb->get_col( 'SELECT DISTINCT staff FROM ' . md_survey_table_response() . ' ORDER BY staff' );
	?>
	<div class="mdsv-bar">
		<form method="get" class="mdsv-range" action="<?php echo esc_url( md_survey_admin_url() ); ?>">
			<?php md_sup_app_field(); ?><input type="hidden" name="sv" value="responses">
			<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"> ~ <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>">
			<select name="doctor"><option value="">모든 담당의사</option><?php foreach ( md_survey_doctor_names() as $s ) : ?><option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $doctor ); ?>><?php echo esc_html( $s ); ?></option><?php endforeach; ?></select>
			<select name="staff"><option value="">모든 담당직원</option><?php foreach ( $staffs as $s ) : if ( '' === (string) $s ) { continue; } ?><option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $staff ); ?>><?php echo esc_html( $s ); ?></option><?php endforeach; ?></select>
			<button type="submit" class="mds-btn mds-btn--ghost">보기</button>
		</form>
		<form method="post" class="mds-inline">
			<input type="hidden" name="md_survey_action" value="export"><input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_export' ) ); ?>">
			<input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>"><input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>"><input type="hidden" name="staff" value="<?php echo esc_attr( $staff ); ?>"><input type="hidden" name="doctor" value="<?php echo esc_attr( $doctor ); ?>">
			<button type="submit" class="mds-btn mds-btn--ghost">CSV 내려받기</button>
		</form>
	</div>
	<?php if ( '' !== $staff || '' !== $doctor ) : /* v4.21.30 · 집계에서 이름을 눌러 들어온 경우 */ ?>
		<p class="mdsv-filterbar"><b><?php echo esc_html( trim( $doctor . ( $doctor && $staff ? ' · ' : '' ) . $staff ) ); ?></b> 응답 <?php echo count( $rows ); ?>건 · <a href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'responses', 'from' => $from, 'to' => $to ) ) ); ?>">전체 보기</a> · <a href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'stats', 'from' => $from, 'to' => $to ) ) ); ?>">집계로 돌아가기</a></p>
	<?php endif; ?>
	<?php if ( empty( $rows ) ) : ?>
		<div class="mds-card"><div class="mds-empty">이 기간에 응답이 없습니다.</div></div>
	<?php else : ?>
		<div class="mds-tablewrap">
			<table class="mds-table mdsv-table">
				<thead><tr><th>진료일</th><th>환자</th><th>담당의사</th><th>담당직원</th><th class="num">담당의사</th><th class="num">담당직원</th><th class="num">병원</th><th>의견</th><th>작성</th><th>IP</th><th></th></tr></thead>
				<tbody>
				<?php $ipcount = array(); foreach ( $rows as $x ) { if ( ! empty( $x->ip ) ) { $ipcount[ $x->ip ] = ( isset( $ipcount[ $x->ip ] ) ? $ipcount[ $x->ip ] : 0 ) + 1; } } /* v4.21.33 · 같은 IP 응답 수 */
				$lowf = function ( $v ) { return ( (int) $v >= 1 && (int) $v <= 2 ) ? 'is-low' : ''; };
				foreach ( $rows as $r ) : $qd = isset( $r->q_doctor ) ? (int) $r->q_doctor : 0; $low = $lowf( $qd ) || $lowf( $r->q_service ) || $lowf( $r->q_recommend ); ?>
					<tr class="<?php echo $low ? 'is-lowrow' : ''; ?>">
						<td><?php echo esc_html( date_i18n( 'm.d', strtotime( $r->visit_date ) ) ); ?></td>
						<td><b><?php echo esc_html( $r->patient_name ); ?></b><span class="mds-item__meta"><?php echo esc_html( $r->chart_no ); ?></span></td>
						<td><?php echo esc_html( $r->doctor ); ?></td>
						<td><b><?php echo esc_html( '' !== $r->staff ? $r->staff : '(모름)' ); ?></b><?php if ( ! empty( $r->staff_changed ) && '' !== (string) $r->staff_orig ) : ?><span class="mds-item__meta">명단: <?php echo esc_html( $r->staff_orig ); ?> → 환자가 바로잡음</span><?php endif; ?></td>
						<td class="num <?php echo $lowf( $qd ); ?>"><?php echo $qd ? $qd : '–'; ?></td>
						<td class="num <?php echo $lowf( $r->q_service ); ?>"><?php echo $r->q_service ? (int) $r->q_service : '–'; ?></td>
						<td class="num <?php echo $lowf( $r->q_recommend ); ?>"><?php echo (int) $r->q_recommend; ?></td>
						<td class="mdsv-comment"><?php echo nl2br( esc_html( (string) $r->comment ) ); ?><?php if ( ! empty( $r->want_call ) ) : ?><span class="mds-flag">📞 연락 원함</span><?php endif; ?></td>
						<td class="mds-last"><?php echo esc_html( date_i18n( 'm.d H:i', strtotime( $r->created_at ) ) ); ?></td>
						<td class="mds-last mdsv-ip"><?php $ip = isset( $r->ip ) ? (string) $r->ip : ''; echo '' !== $ip ? esc_html( $ip ) : '–'; if ( '' !== $ip && ! empty( $ipcount[ $ip ] ) && $ipcount[ $ip ] > 1 ) : ?><span class="mds-item__meta">같은 IP <?php echo (int) $ipcount[ $ip ]; ?>건</span><?php endif; ?></td>
						<td><form method="post" class="mds-inline" onsubmit="return confirm('이 응답을 지울까요? 되돌릴 수 없고, 이 진료는 다시 응답할 수 있게 됩니다.');"><input type="hidden" name="md_survey_action" value="delete_resp"><input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_delete_resp' ) ); ?>"><input type="hidden" name="rid" value="<?php echo (int) $r->id; ?>"><input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>"><input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>"><button type="submit" class="mds-btn mds-btn--ghost mds-btn--sm">삭제</button></form></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="mds-hint">모든 문항은 1~5점입니다. 1~2점은 붉게 표시됩니다. IP는 응답한 인터넷 주소로, 같은 IP에서 여러 환자의 응답이 오면 「같은 IP n건」이 붙습니다.</p>
	<?php endif; ?>
	<?php
}

/** 스탭별 집계 (관리자) */
function md_survey_render_stats() {
	if ( ! md_survey_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	list( $from, $to ) = md_survey_range( isset( $_GET['from'] ) ? wp_unslash( $_GET['from'] ) : '', isset( $_GET['to'] ) ? wp_unslash( $_GET['to'] ) : '' );
	$rows  = md_survey_stats( $from, $to );
	$total = 0; foreach ( $rows as $r ) { $total += (int) $r->n; }
	$q_start = current_time( 'Y' ) . '-' . str_pad( ( floor( ( (int) current_time( 'n' ) - 1 ) / 3 ) * 3 + 1 ), 2, '0', STR_PAD_LEFT ) . '-01';
	$presets = array(
		'이번 달' => array( current_time( 'Y-m-01' ), current_time( 'Y-m-d' ) ),
		'지난 달' => array( gmdate( 'Y-m-01', strtotime( current_time( 'Y-m-01' ) . ' -1 month' ) ), gmdate( 'Y-m-t', strtotime( current_time( 'Y-m-01' ) . ' -1 month' ) ) ),
		'이번 분기' => array( $q_start, current_time( 'Y-m-d' ) ),
	);
	?>
	<div class="mdsv-bar">
		<form method="get" class="mdsv-range" action="<?php echo esc_url( md_survey_admin_url() ); ?>">
			<?php md_sup_app_field(); ?><input type="hidden" name="sv" value="stats">
			<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"> ~ <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>">
			<button type="submit" class="mds-btn mds-btn--ghost">보기</button>
		</form>
		<nav class="mdsv-presets">
			<?php foreach ( $presets as $label => $p ) : ?><a class="mds-btn mds-btn--ghost mds-btn--sm<?php echo ( $p[0] === $from && $p[1] === $to ) ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'stats', 'from' => $p[0], 'to' => $p[1] ) ) ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?>
		</nav>
	</div>
	<?php /* v4.21.34 · 담당의사별 → 담당직원별 순서 (원장 지시). 평균만, 이름을 누르면 그 사람 응답 목록 */
	$docs = md_survey_stats_doctor( $from, $to ); ?>
	<?php if ( empty( $rows ) && empty( $docs ) ) : ?>
		<div class="mds-card"><div class="mds-empty">이 기간에 응답이 없습니다.</div></div>
	<?php else : ?>
		<h2 class="mdsv-h">담당의사별</h2>
		<div class="mds-tablewrap">
			<table class="mds-table mdsv-table">
				<thead><tr><th>담당의사</th><th class="num">평균</th></tr></thead>
				<tbody>
				<?php if ( ! $docs ) : ?><tr><td colspan="2" class="mds-empty">응답 없음</td></tr><?php endif; ?>
				<?php foreach ( $docs as $dn => $d ) : $n = max( 1, $d['n'] ); ?>
					<tr><td><a class="mdsv-name" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'responses', 'from' => $from, 'to' => $to, 'doctor' => $dn ) ) ); ?>"><b><?php echo esc_html( $dn ); ?></b></a></td><td class="num"><b><?php echo number_format( $d['sum'] / $n, 2 ); ?></b></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h2 class="mdsv-h" style="margin-top:22px">담당직원별</h2>
		<div class="mds-tablewrap">
			<table class="mds-table mdsv-table">
				<thead><tr><th>담당직원</th><th class="num">평균</th></tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?><tr><td colspan="2" class="mds-empty">응답 없음</td></tr><?php endif; ?>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><a class="mdsv-name" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'responses', 'from' => $from, 'to' => $to, 'staff' => $r->staff ) ) ); ?>"><b><?php echo esc_html( $r->staff ); ?></b></a></td>
						<td class="num"><b><?php echo number_format( (float) $r->avg_service, 2 ); ?></b></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="mds-hint">이름을 누르면 그 사람의 응답을 모두 볼 수 있습니다. 평균은 1~5점입니다.</p>
	<?php endif; ?>
	<?php
}

/** 설정 (관리자) */
function md_survey_render_settings() {
	if ( ! md_survey_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	$s = md_survey_settings();
	/* v4.21.24 · 필요한 것만 (원장 지시) — 알림톡 링크 · 응답 허용 기간.
	 * 원장·스탭 목록(직접 넣기 화면이 없어져 안 씀) · 구글 리뷰(감사 화면에서 뺌) · 연동 키(엑셀 올리기로 운영)는 화면에서 뺐다.
	 * 저장할 때 이 값들은 그대로 둔다 (settings 처리기에서 keep_extra). */
	?>
	<form method="post" class="mds-card mdsv-settings">
		<input type="hidden" name="md_survey_action" value="settings">
		<input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_settings' ) ); ?>">
		<input type="hidden" name="keep_extra" value="1">
		<?php /* v4.21.35 · 알림톡과 문자의 치환문 형식이 달라 두 줄로 */ ?>
		<h2 class="mdsv-h">덴트웹 진료 후 메시지에 넣을 링크</h2>
		<p class="mds-hint" style="margin-bottom:4px"><b>카카오 알림톡</b>으로 보낼 때</p>
		<p class="mdsv-url"><code><?php echo esc_html( home_url( '/survey/?n=#{환자명}' ) ); ?></code></p>
		<p class="mds-hint" style="margin:10px 0 4px"><b>문자(SMS·LMS)</b>로 보낼 때</p>
		<p class="mdsv-url"><code><?php echo esc_html( home_url( '/survey/?n=#환자명#' ) ); ?></code></p>
		<p class="mds-hint">덴트웹 진료 후 템플릿 본문에 넣습니다. 알림톡은 <code>#{환자명}</code>, 문자(SMS·LMS)는 <code>#환자명#</code> 형식입니다.</p>

		<h2 class="mdsv-h" style="margin-top:20px">새 응답 알림 메일</h2>
		<div class="mds-formrow">
			<label class="mds-field mds-field--grow"><span>새 응답이 오면 이 주소로 메일을 보냅니다 (여러 개는 쉼표로)</span><input type="text" name="notify_email" value="<?php echo esc_attr( (string) $s['notify_email'] ); ?>" placeholder="moondentaldigital@gmail.com" style="width:100%;font:inherit;padding:10px 12px;min-height:44px;border:1px solid var(--color-border, #EDDFD0);border-radius:10px"></label>
		</div>
		<p class="mds-hint">비우면 메일을 보내지 않습니다. 낮은 점수(1~2점)나 연락 요청이 있으면 제목 앞에 [낮은 점수] · [연락 원함]이 붙습니다.</p>
		<p><button type="submit" form="md-sv-testmail" class="mds-btn mds-btn--ghost mds-btn--sm">시험 메일 보내기</button></p>

		<?php /* v6.3.1 · 덴트웹 DB 자동 연동 프로그램(config.json 의 ApiKey)에 넣을 키 — 관리자만 보는 화면 */ ?>
		<h2 class="mdsv-h" style="margin-top:20px">덴트웹 자동 연동 키</h2>
		<p class="mdsv-url"><code><?php echo esc_html( (string) get_option( 'md_survey_api_key' ) ); ?></code></p>
		<p class="mds-hint">「덴트웹 자동연동」 프로그램의 config.json 에서 ApiKey 자리에 넣습니다. 다른 곳에 알려지지 않게 주의하세요.</p>

		<h2 class="mdsv-h" style="margin-top:20px">응답 받는 기간</h2>
		<div class="mds-formrow">
			<label class="mds-field"><span>진료일부터 며칠까지 응답을 받을까요</span><input type="number" name="window_days" min="1" max="30" value="<?php echo (int) $s['window_days']; ?>"></label>
		</div>

		<div class="mds-formbtns"><button type="submit" class="mds-btn mds-btn--fill">저장</button></div>
	</form>
	<form method="post" id="md-sv-testmail"><input type="hidden" name="md_survey_action" value="test_mail"><input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_test_mail' ) ); ?>"></form>
	<?php
}
