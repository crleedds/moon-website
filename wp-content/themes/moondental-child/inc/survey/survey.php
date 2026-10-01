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
 *    2. 환자가 링크를 열면 링크에 실린 이름(?n=#환자명#)과 환자가 넣은 휴대폰 가운데 4자리를 명단과 대조한다.
 *       이름이 링크에 없으면 환자가 이름을 적는다. 맞으면 그날 담당 원장 · 담당 스탭 이름이 보이고 설문이 열린다.
 *    3. 제출하면 그 진료(명단 한 줄)는 응답 완료로 잠긴다 — 같은 진료에 두 번 쓸 수 없다.
 *
 *  누가 무엇을 하는가
 *    직원 공용 계정   명단 올리기 · 그날 명단 보기
 *    관리자           응답 열람 · 스탭별 집계 · CSV · 설정 (원장·스탭 목록 · 응답 허용 일수 · 연동 키)
 *
 *  개인정보
 *    명단의 휴대폰 가운데 4자리는 비밀키로 HMAC 해시해 저장한다 (원문은 남기지 않는다). 생년월일은 받지 않는다 (v4.10.8).
 *    환자가 입력한 값도 저장하지 않는다 — 해시해서 비교만 한다.
 *    본인 확인이 5번 틀리면 그 접속은 30분 동안 막는다.
 *    명단은 90일이 지나면 지운다. 응답은 남긴다 (차트번호 · 이름 · 담당자 · 점수 · 의견).
 *
 *  화면은 서버에서 그린다. 자바스크립트가 없어도 전부 동작한다.
 *  폼은 POST → 처리 → 리다이렉트(PRG). 새로고침해도 두 번 올라가지 않는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_SURVEY_SCHEMA', 2 ); /* v4.10.1 · phone_hash 추가 — 링크에 이름이 실려 오면 휴대폰 가운데 4자리만 묻는다 */

/* ============================================================
 * 테이블 · 설치
 * ============================================================ */

function md_survey_table_visit()    { global $wpdb; return $wpdb->prefix . 'md_survey_visit'; }
function md_survey_table_response() { global $wpdb; return $wpdb->prefix . 'md_survey_response'; }

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
		doctor VARCHAR(40) NOT NULL DEFAULT '',
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
		doctor VARCHAR(40) NOT NULL DEFAULT '',
		staff VARCHAR(40) NOT NULL DEFAULT '',
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

/** 명단은 본인 확인에만 쓰므로 90일이 지나면 지운다 (응답은 남긴다) */
function md_survey_cleanup() {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_survey_table_visit() . ' WHERE visit_date < %s', gmdate( 'Y-m-d', time() - 90 * DAY_IN_SECONDS ) ) );
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
 * 휴대폰 → 가운데 4자리 (010-XXXX-0000 의 XXXX). (v4.14.3)
 *  덴트웹이 전자서명 없이 저장한 엑셀은 뒷자리를 「010-1234-56**」처럼 가리므로 뒷 4자리는 쓸 수 없다.
 *  가운데 4자리는 그대로 남아 있어 이것을 본인 확인 값으로 쓴다.
 *  입력이 딱 4자리면 그 값을 가운데 4자리로 본다 (데스크가 손으로 넣을 때).
 *  11자리(가려진 자리는 * 로 세어서)면 4~7번째, 그 밖에는 ''.
 */
function md_survey_norm_phone4( $v ) {
	$s = preg_replace( '/[^0-9*]+/', '', (string) $v );
	if ( preg_match( '/^\d{4}$/', $s ) ) { return $s; }
	if ( 11 === strlen( $s ) && preg_match( '/^\d{7}/', $s ) ) { return substr( $s, 3, 4 ); }
	if ( 10 === strlen( $s ) && preg_match( '/^\d{6}/', $s ) ) { return substr( $s, 3, 3 ) . '0'; } /* 옛 10자리 번호 — 거의 없음 */
	return '';
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

/** 휴대폰 가운데 4자리만의 해시 — 링크에 이름이 실려 온 환자는 이것과 이름으로 확인한다 (v4.10.1) */
function md_survey_phone_hash( $phone4 ) {
	return hash_hmac( 'sha256', 'p|' . $phone4, md_survey_secret() );
}

/** 이름 비교용 — 공백 제거 */
function md_survey_norm_name( $v ) {
	return preg_replace( '/\s+/u', '', trim( sanitize_text_field( (string) $v ) ) );
}

function md_survey_ip_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return substr( hash_hmac( 'md5', $ip, md_survey_secret() ), 0, 32 );
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
	$doctor = mb_substr( trim( sanitize_text_field( isset( $d['doctor'] ) ? $d['doctor'] : '' ) ), 0, 40 );
	$staff  = mb_substr( trim( sanitize_text_field( isset( $d['staff'] ) ? $d['staff'] : '' ) ), 0, 40 );

	if ( '' === $chart )  { return new WP_Error( 'md_survey', '차트번호가 없습니다.' ); }
	if ( '' === $name )   { return new WP_Error( 'md_survey', '이름이 없습니다.' ); }
	if ( '' === $phone4 ) { return new WP_Error( 'md_survey', '휴대폰 번호(가운데 4자리)를 읽지 못했습니다 (' . $name . ').' ); }
	if ( '' === $staff )  { return new WP_Error( 'md_survey', '담당 스탭이 없습니다.' ); }

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
	$ok = 0; $upd = 0; $skip = 0; $errs = array();
	$map = null;
	$aliases = array(
		'chart_no' => array( '차트번호', '차트', '등록번호', '차트no', 'chart' ),
		'name'     => array( '이름', '성명', '환자명', '환자', 'name' ),
		'phone'    => array( '휴대폰', '휴대전화', '핸드폰', '전화', '연락처', 'phone', '가운데4자리', '뒷자리' ),
		'birth'    => array( '생년월일', '생일', '주민번호', '주민', 'birth' ),
		'doctor'   => array( '담당의사', '담당원장', '의사', '원장', '진료의', 'doctor' ),
		'staff'    => array( '담당직원', '담당스탭', '스탭', '직원', '위생사', '어시스트', 'staff' ),
	);
	$n = 0;
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) { continue; }
		$cols = ( false !== strpos( $line, "\t" ) ) ? explode( "\t", $line ) : str_getcsv( $line );
		$cols = array_map( 'trim', $cols );
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
		if ( ! isset( $d['staff'] ) || '' === trim( (string) $d['staff'] ) ) { $skip++; continue; }
		$r = md_survey_visit_upsert( $d, 'import' );
		if ( is_wp_error( $r ) ) { $errs[] = $n . '줄: ' . $r->get_error_message(); }
		elseif ( $r['updated'] ) { $upd++; }
		else { $ok++; }
	}
	return array( 'added' => $ok, 'updated' => $upd, 'skipped' => $skip, 'errors' => $errs );
}

/* ============================================================
 * 응답
 * ============================================================ */

function md_survey_response_by_visit( $visit_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_survey_table_response() . ' WHERE visit_id = %d', (int) $visit_id ) );
}

function md_survey_response_insert( $visit, $q1, $q2, $q3, $comment ) {
	global $wpdb;
	$tr = md_survey_table_response();
	$ok = $wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO $tr (visit_id, visit_date, chart_no, patient_name, doctor, staff, q_service, q_explain, q_recommend, comment, ip_hash, created_at)
		 VALUES (%d, %s, %s, %s, %s, %s, %d, %d, %d, %s, %s, %s)",
		(int) $visit->id, $visit->visit_date, $visit->chart_no, $visit->patient_name, $visit->doctor, $visit->staff,
		(int) $q1, (int) $q2, (int) $q3, $comment, md_survey_ip_hash(), current_time( 'mysql' )
	) );
	if ( ! $ok ) { return false; } /* 0 = 이미 있음 (UNIQUE visit) */
	$wpdb->update( md_survey_table_visit(), array( 'responded_at' => current_time( 'mysql' ) ), array( 'id' => (int) $visit->id ) );
	return true;
}

function md_survey_responses( $from, $to, $staff = '' ) {
	global $wpdb;
	$tr  = md_survey_table_response();
	$sql = "SELECT * FROM $tr WHERE visit_date BETWEEN %s AND %s";
	$args = array( $from, $to );
	if ( '' !== $staff ) { $sql .= ' AND staff = %s'; $args[] = $staff; }
	$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 2000';
	return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
}

/** 스탭별 집계 — 응답 수 · 평균 · 5점 비율 · 낮은 점수 건수 */
function md_survey_stats( $from, $to ) {
	global $wpdb;
	$tr = md_survey_table_response();
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT staff,
			COUNT(*) AS n,
			AVG(q_service) AS avg_service, AVG(q_explain) AS avg_explain, AVG(q_recommend) AS avg_recommend,
			SUM(q_service = 5) AS top_service, SUM(q_explain = 5) AS top_explain,
			SUM(q_service <= 2 OR q_explain <= 2) AS low_n,
			SUM(comment IS NOT NULL AND comment <> '') AS comment_n
		 FROM $tr WHERE visit_date BETWEEN %s AND %s GROUP BY staff ORDER BY n DESC, avg_service DESC", $from, $to ) );
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

	/* v4.10.1 · 알림톡 링크에 이름이 실려 오면(?n=#환자명#) 휴대폰 가운데 4자리만 묻는다.
	 * 이름은 GET 으로 받아 폼에 숨겨 두고, POST 때 그대로 돌려받는다. */
	$pname = '';
	if ( isset( $_GET['n'] ) )  { $pname = md_survey_norm_name( wp_unslash( $_GET['n'] ) ); }
	if ( isset( $_POST['pn'] ) ) { $pname = md_survey_norm_name( wp_unslash( $_POST['pn'] ) ); }
	$pname = mb_substr( $pname, 0, 40 );

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
				/* v4.10.8 · 본인 확인은 이름 + 휴대폰 가운데 4자리. 이름은 링크(?n=)에서 오거나, 없으면 환자가 적는다. */
				$phone4 = md_survey_norm_phone4( isset( $_POST['phone4'] ) ? wp_unslash( $_POST['phone4'] ) : '' );
				$typed  = isset( $_POST['name'] ) ? mb_substr( md_survey_norm_name( wp_unslash( $_POST['name'] ) ), 0, 40 ) : '';
				$from_link = '' !== $pname;
				if ( ! $from_link ) { $pname = $typed; }
				$agree  = ! empty( $_POST['agree'] );
				if ( ! $agree ) {
					$err = '개인정보 수집·이용에 동의해 주세요.';
				} elseif ( '' === $pname || '' === $phone4 ) {
					$err = '이름과 휴대전화 가운데 4자리를 확인해 주세요.';
				} else {
					global $wpdb;
					$tv   = md_survey_table_visit();
					$rows = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM $tv WHERE phone_hash = %s AND REPLACE(patient_name, ' ', '') = %s AND visit_date BETWEEN %s AND %s ORDER BY visit_date DESC, id DESC",
						md_survey_phone_hash( $phone4 ), $pname, $since, $today ) );
					if ( empty( $rows ) ) {
						$n = md_survey_note_fail();
						$step = $n >= 5 ? 'locked' : 'identify';
						if ( 'identify' === $step ) {
							$err = '최근 ' . $win . '일 안의 진료 기록에서 일치하는 분을 찾지 못했습니다. 이름과 숫자를 다시 확인해 주세요. 진료 직후라면 잠시 후 다시 시도해 주세요.';
							$pname = ''; /* 링크의 이름이 틀렸을 수도 있으니 이름 칸을 열어 준다 */
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
				$q1 = isset( $_POST['q_service'] ) ? (int) $_POST['q_service'] : 0;
				$q2 = isset( $_POST['q_explain'] ) ? (int) $_POST['q_explain'] : 0;
				$q3 = isset( $_POST['q_recommend'] ) ? (int) $_POST['q_recommend'] : -1;
				$cm = isset( $_POST['comment'] ) ? mb_substr( trim( sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) ), 0, 1000 ) : '';
				if ( $v->responded_at || md_survey_response_by_visit( $v->id ) ) {
					$step = 'already';
				} elseif ( $q1 < 1 || $q1 > 5 || $q2 < 1 || $q2 > 5 || $q3 < 0 || $q3 > 10 ) {
					$step = 'form';
					$err  = '세 문항 모두 골라 주세요.';
				} else {
					$step = md_survey_response_insert( $v, $q1, $q2, $q3, $cm ) ? 'done' : 'already';
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
.sv-field{display:block;margin:14px 0}
.sv-field>span{display:block;margin-bottom:6px;font-size:.86rem;font-weight:700;color:var(--sub)}
.sv-field input[type=tel]{width:100%;font:inherit;font-size:1.25rem;letter-spacing:.12em;min-height:54px;padding:10px 16px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--text)}
.sv-field input:focus{outline:2px solid var(--primary);outline-offset:0;border-color:var(--primary)}
.sv-field small{display:block;margin-top:5px;font-size:.8rem;color:var(--mute)}
.sv-agree{display:flex;gap:10px;align-items:flex-start;margin:16px 0 6px;font-size:.9rem;color:var(--sub)}
.sv-agree input{width:22px;height:22px;margin:2px 0 0;flex:none;accent-color:var(--primary)}
.sv-consent{margin:8px 0 0;padding:12px 14px;border-radius:10px;background:var(--soft);font-size:.8rem;line-height:1.6;color:var(--sub)}
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
.sv-ends{display:flex;justify-content:space-between;margin-top:6px;font-size:.76rem;color:var(--mute)}
.sv-q textarea{width:100%;font:inherit;min-height:110px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--text);resize:vertical}
.sv-q textarea:focus{outline:2px solid var(--primary);outline-offset:0;border-color:var(--primary)}
.sv-done{text-align:center;padding:36px 20px}
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

<?php elseif ( 'done' === $step ) : ?>
	<section class="sv-card sv-done">
		<div class="mark" aria-hidden="true">✓</div>
		<h1>응답이 저장되었습니다</h1>
		<p><?php echo esc_html( $name ); ?>님의 의견은 병원 관리자에게만 전달되어 더 나은 진료를 만드는 데 쓰입니다.</p>
		<div class="sv-review">
			<p>진료가 만족스러우셨다면 구글에도 한 줄 남겨 주세요. 다른 분들이 치과를 고를 때 큰 도움이 됩니다.</p>
			<a class="sv-review__btn" href="<?php echo esc_url( md_survey_review_url() ); ?>" target="_blank" rel="noopener">구글 리뷰 남기기</a>
		</div>
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
	<form method="post" class="sv-card" action="<?php echo esc_url( md_survey_public_url() ); ?>">
		<input type="hidden" name="md_sv" value="submit">
		<input type="hidden" name="tok" value="<?php echo esc_attr( $tok ); ?>">
		<h1><?php echo esc_html( $name ); ?>님, <?php echo esc_html( $date_label ); ?> 진료는 어떠셨나요?</h1>
		<p>치료 결과가 아닌, 담당 스탭의 안내와 응대에 대해 여쭙습니다. 30초면 됩니다.</p>
		<div class="sv-who">
			<span>진료일</span><b><?php echo esc_html( date_i18n( 'Y년 n월 j일', strtotime( $visit->visit_date ) ) ); ?></b>
			<?php if ( '' !== $visit->doctor ) : ?><span>담당 원장</span><b><?php echo esc_html( $visit->doctor ); ?></b><?php endif; ?>
			<span>담당 스탭</span><b class="staff"><?php echo esc_html( $staff ); ?></b>
		</div>

		<div class="sv-q">
			<h2>1. <em><?php echo esc_html( $staff ); ?></em> 스탭의 응대는 어떠셨나요?</h2>
			<p>친절함 · 배려 · 불편한 점을 살펴 주었는지</p>
			<div class="sv-scale sv-scale--5" role="radiogroup" aria-label="응대 만족도">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?><label><input type="radio" name="q_service" value="<?php echo $i; ?>" required><span><?php echo $i; ?></span></label><?php endfor; ?>
			</div>
			<div class="sv-ends"><span>매우 불만족</span><span>매우 만족</span></div>
		</div>

		<div class="sv-q">
			<h2>2. <em><?php echo esc_html( $staff ); ?></em> 스탭의 설명은 이해하기 쉬웠나요?</h2>
			<p>진료 안내 · 주의사항 · 다음 진료 설명</p>
			<div class="sv-scale sv-scale--5" role="radiogroup" aria-label="설명 만족도">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?><label><input type="radio" name="q_explain" value="<?php echo $i; ?>" required><span><?php echo $i; ?></span></label><?php endfor; ?>
			</div>
			<div class="sv-ends"><span>매우 불만족</span><span>매우 만족</span></div>
		</div>

		<div class="sv-q">
			<h2>3. 문치과병원을 가족이나 지인에게 추천하시겠습니까?</h2>
			<div class="sv-scale sv-scale--11" role="radiogroup" aria-label="추천 의향">
				<?php for ( $i = 0; $i <= 10; $i++ ) : ?><label><input type="radio" name="q_recommend" value="<?php echo $i; ?>" required><span><?php echo $i; ?></span></label><?php endfor; ?>
			</div>
			<div class="sv-ends"><span>전혀 아니다</span><span>꼭 추천한다</span></div>
		</div>

		<div class="sv-q">
			<h2>4. 칭찬하거나 개선할 점이 있다면 적어 주세요 <small style="font-weight:500;color:var(--mute)">(선택)</small></h2>
			<textarea name="comment" maxlength="1000" placeholder="예) 설명이 자세해서 안심이 됐어요 · 대기 시간이 길었어요"></textarea>
		</div>

		<button type="submit" class="sv-btn">제출하기</button>
		<p class="sv-foot">이 진료에 대한 응답은 한 번만 저장됩니다.</p>
	</form>

<?php else : ?>
	<form method="post" class="sv-card" action="<?php echo esc_url( md_survey_public_url() ); ?>" autocomplete="off">
		<input type="hidden" name="md_sv" value="identify">
		<?php if ( '' !== $pname ) : ?>
		<input type="hidden" name="pn" value="<?php echo esc_attr( $pname ); ?>">
		<h1><?php echo esc_html( $pname ); ?>님, 본인 확인</h1>
		<p>진료받으신 분만 참여할 수 있습니다. 휴대전화 가운데 4자리만 확인합니다.</p>
		<?php else : ?>
		<h1>진료받으신 본인 확인</h1>
		<p>진료받으신 분만 참여할 수 있습니다. 이름과 휴대전화 가운데 4자리를 확인합니다.</p>
		<label class="sv-field">
			<span>이름</span>
			<input type="text" name="name" maxlength="40" required placeholder="홍길동" autocomplete="off" autofocus>
			<small>병원에 등록된 이름</small>
		</label>
		<?php endif; ?>
		<label class="sv-field">
			<span>휴대전화 가운데 4자리</span>
			<input type="tel" name="phone4" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required placeholder="1234" autocomplete="off" <?php echo '' !== $pname ? 'autofocus' : ''; ?>>
			<small>010-<b>1234</b>-5678 이라면 <b>1234</b></small>
		</label>
		<label class="sv-agree">
			<input type="checkbox" name="agree" value="1" required>
			<span>개인정보 수집·이용에 동의합니다 (필수)</span>
		</label>
		<div class="sv-consent">
			<b>수집 항목</b> 이름 · 휴대전화 가운데 4자리(본인 확인에만 사용, 저장하지 않음), 설문 응답<br>
			<b>이용 목적</b> 진료 만족도 조사와 서비스 개선 · 담당 직원 평가<br>
			<b>보유 기간</b> 응답일로부터 2년<br>
			동의하지 않으면 설문에 참여할 수 없습니다. 응답은 병원 관리자만 열람합니다.
		</div>
		<button type="submit" class="sv-btn">확인하고 설문 시작</button>
	</form>
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

function md_survey_admin_url( $args = array() ) {
	return md_sup_url( array_merge( array( 'app' => 'survey' ), $args ) );
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
			$back = add_query_arg( array( 'msg' => 'imported', 'a' => $r['added'], 'u' => $r['updated'], 's' => $r['skipped'] ), $back );
			if ( $r['errors'] ) { $back = add_query_arg( 'err', implode( ' / ', array_slice( $r['errors'], 0, 5 ) ), $back ); }
			break;

		case 'delete':
			$res = md_survey_visit_delete( isset( $_POST['vid'] ) ? (int) $_POST['vid'] : 0 );
			if ( is_wp_error( $res ) ) { $back = add_query_arg( 'err', $res->get_error_message(), $back ); }
			break;

		case 'settings':
			if ( ! md_survey_can_manage() ) { wp_die( '권한이 없습니다.' ); }
			$s = md_survey_settings();
			$s['window_days'] = max( 1, min( 30, isset( $_POST['window_days'] ) ? (int) $_POST['window_days'] : 3 ) );
			$s['doctors']     = isset( $_POST['doctors'] ) ? sanitize_textarea_field( wp_unslash( $_POST['doctors'] ) ) : '';
			$s['staff']       = isset( $_POST['staff'] ) ? sanitize_textarea_field( wp_unslash( $_POST['staff'] ) ) : '';
			$ru = isset( $_POST['review_url'] ) ? esc_url_raw( trim( wp_unslash( $_POST['review_url'] ) ) ) : '';
			$s['review_url'] = ( '' !== $ru && preg_match( '#^https://(search\.google\.com|g\.page|maps\.app\.goo\.gl|www\.google\.com|maps\.google\.com|goo\.gl)/#', $ru ) ) ? $ru : '';
			update_option( 'md_survey_settings', $s, 'no' );
			if ( ! empty( $_POST['regen_key'] ) ) { update_option( 'md_survey_api_key', wp_generate_password( 40, false ), 'no' ); }
			$back = md_survey_admin_url( array( 'sv' => 'settings', 'msg' => 'saved' ) );
			break;

		case 'export':
			if ( ! md_survey_can_manage() ) { wp_die( '권한이 없습니다.' ); }
			md_survey_export_csv(
				isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '',
				isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '',
				isset( $_POST['staff'] ) ? sanitize_text_field( wp_unslash( $_POST['staff'] ) ) : ''
			);
			exit;
	}

	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_survey_handle_post', 1 );

function md_survey_export_csv( $from, $to, $staff ) {
	list( $from, $to ) = md_survey_range( $from, $to );
	$rows = md_survey_responses( $from, $to, $staff );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="만족도조사_' . $from . '_' . $to . '.csv"' );
	echo "\xEF\xBB\xBF"; /* 엑셀이 한글을 제대로 읽도록 BOM */
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( '진료일', '차트번호', '이름', '담당원장', '담당스탭', '응대', '설명', '추천', '의견', '작성시각' ) );
	foreach ( $rows as $r ) {
		fputcsv( $out, array( $r->visit_date, $r->chart_no, $r->patient_name, $r->doctor, $r->staff, $r->q_service, $r->q_explain, $r->q_recommend, (string) $r->comment, $r->created_at ) );
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
	if ( ! function_exists( 'md_sup_current_app' ) || 'survey' !== md_sup_current_app() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/survey.css' ) ) {
		wp_enqueue_style( 'moondental-survey', $uri . '/assets/css/survey.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/survey.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'md_survey_enqueue', 31 );

function md_survey_tabs() {
	$tabs = array( 'roster' => array( 'label' => '오늘 명단', 'icon' => '📋', 'manage' => false ) );
	if ( md_survey_can_manage() ) {
		$tabs['responses'] = array( 'label' => '응답',   'icon' => '💬', 'manage' => true );
		$tabs['stats']     = array( 'label' => '스탭별 집계', 'icon' => '📊', 'manage' => true );
		$tabs['settings']  = array( 'label' => '설정',   'icon' => '⚙️', 'manage' => true );
	}
	return $tabs;
}

function md_survey_current_tab() {
	$t = isset( $_GET['sv'] ) ? sanitize_key( wp_unslash( $_GET['sv'] ) ) : 'roster';
	$tabs = md_survey_tabs();
	return isset( $tabs[ $t ] ) ? $t : 'roster';
}

function md_survey_notice() {
	if ( isset( $_GET['err'] ) ) { echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['err'] ) ) . '</div>'; }
	if ( ! isset( $_GET['msg'] ) ) { return; }
	$m = sanitize_key( wp_unslash( $_GET['msg'] ) );
	$map = array(
		'added'    => '명단에 넣었습니다.',
		'updated'  => '같은 날 같은 차트번호가 있어 그 줄을 고쳤습니다.',
		'saved'    => '설정을 저장했습니다.',
		'imported' => sprintf( '붙여넣기 완료 — 새로 %d명, 고침 %d명, 담당직원이 없어 건너뜀 %d명.', isset( $_GET['a'] ) ? (int) $_GET['a'] : 0, isset( $_GET['u'] ) ? (int) $_GET['u'] : 0, isset( $_GET['s'] ) ? (int) $_GET['s'] : 0 ),
	);
	if ( isset( $map[ $m ] ) ) { echo '<div class="mds-notice mds-notice--ok">' . esc_html( $map[ $m ] ) . '</div>'; }
}

function md_survey_render() {
	$tab = md_survey_current_tab();
	?>
	<nav class="mds-tabs" aria-label="만족도 조사 메뉴">
		<?php foreach ( md_survey_tabs() as $key => $t ) : ?>
			<a class="mds-tab<?php echo $tab === $key ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => $key ) ) ); ?>"<?php echo $tab === $key ? ' aria-current="page"' : ''; ?>>
				<span aria-hidden="true"><?php echo esc_html( $t['icon'] ); ?></span><?php echo esc_html( $t['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
	md_survey_notice();
	switch ( $tab ) {
		case 'responses': md_survey_render_responses(); break;
		case 'stats':     md_survey_render_stats();     break;
		case 'settings':  md_survey_render_settings();  break;
		default:          md_survey_render_roster();    break;
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
function md_survey_render_roster() {
	$date = isset( $_GET['d'] ) ? sanitize_text_field( wp_unslash( $_GET['d'] ) ) : current_time( 'Y-m-d' );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { $date = current_time( 'Y-m-d' ); }
	$rows   = md_survey_visits_on( $date );
	$answered = 0;
	foreach ( $rows as $r ) { if ( $r->response_id ) { $answered++; } }
	$manage = md_survey_can_manage();
	?>
	<div class="mdsv-bar">
		<form method="get" class="mdsv-datepick" action="<?php echo esc_url( md_survey_admin_url() ); ?>">
			<?php md_sup_app_field(); ?><input type="hidden" name="sv" value="roster">
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'roster', 'd' => gmdate( 'Y-m-d', strtotime( $date ) - DAY_IN_SECONDS ) ) ) ); ?>" aria-label="전날">‹</a>
			<input type="date" name="d" value="<?php echo esc_attr( $date ); ?>" onchange="this.form.submit()">
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'roster', 'd' => gmdate( 'Y-m-d', strtotime( $date ) + DAY_IN_SECONDS ) ) ) ); ?>" aria-label="다음날">›</a>
			<?php if ( $date !== current_time( 'Y-m-d' ) ) : ?><a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_survey_admin_url( array( 'sv' => 'roster' ) ) ); ?>">오늘</a><?php endif; ?>
		</form>
		<div class="mdsv-count"><b><?php echo count( $rows ); ?>명</b> 명단 · <b><?php echo (int) $answered; ?>명</b> 응답</div>
	</div>

	<form method="post" class="mds-card mdsv-add" id="add">
		<input type="hidden" name="md_survey_action" value="add">
		<input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_add' ) ); ?>">
		<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
		<h2 class="mdsv-h">명단에 한 명 넣기 <small><?php echo esc_html( date_i18n( 'n월 j일 (D)', strtotime( $date ) ) ); ?></small></h2>
		<div class="mds-formrow">
			<label class="mds-field"><span>차트번호</span><input type="text" name="chart_no" inputmode="numeric" maxlength="20" required placeholder="12345"></label>
			<label class="mds-field"><span>이름</span><input type="text" name="name" maxlength="40" required placeholder="홍길동"></label>
			<label class="mds-field"><span>휴대폰 (전체 또는 가운데 4자리)</span><input type="tel" name="phone" inputmode="numeric" maxlength="20" required placeholder="010-1234-5678"></label>
		</div>
		<div class="mds-formrow">
			<?php md_survey_name_field( 'doctor', '담당 원장', 'doctors', md_survey_remembered( 'doctor' ) ); ?>
			<?php md_survey_name_field( 'staff', '담당 스탭 (평가 대상)', 'staff', md_survey_remembered( 'staff' ) ); ?>
			<div class="mds-field"><span>&nbsp;</span><button type="submit" class="mds-btn mds-btn--fill">넣기</button></div>
		</div>
		<p class="mds-hint">이름과 휴대폰 가운데 4자리는 환자가 본인 확인에 쓰는 값입니다. 휴대폰은 저장할 때 암호화되어 이 화면에서도 다시 볼 수 없습니다. 같은 날 같은 차트번호를 다시 넣으면 그 줄을 고칩니다.</p>
	</form>

	<details class="mds-card mdsv-import">
		<summary>여러 명 한꺼번에 붙여넣기</summary>
		<form method="post">
			<input type="hidden" name="md_survey_action" value="import">
			<input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_import' ) ); ?>">
			<input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
			<p class="mds-hint">덴트웹 <b>접수목록 → 기간별 목록 → 엑셀저장</b>으로 받은 파일을 열어 전체 선택(Ctrl+A) · 복사(Ctrl+C)한 뒤 여기에 붙여 넣습니다. 제목 줄(차트번호 · 이름 · 담당의사 · 담당직원 · 전화번호)로 열을 알아서 찾습니다. 담당직원이 빈 환자는 건너뜁니다. 제목 없이 붙일 때의 열 순서는 차트번호 · 이름 · 휴대폰 · 담당원장 · 담당스탭.</p>
			<textarea name="rows" rows="6" placeholder="접수시각	상태	차트번호	이름	…	담당의사	담당직원	체어	전화번호	…"></textarea>
			<div class="mds-formbtns"><button type="submit" class="mds-btn mds-btn--fill"><?php echo esc_html( date_i18n( 'n월 j일', strtotime( $date ) ) ); ?> 명단에 넣기</button></div>
		</form>
	</details>

	<?php if ( empty( $rows ) ) : ?>
		<div class="mds-card"><div class="mds-empty">이 날 명단이 없습니다.</div></div>
	<?php else : ?>
		<div class="mds-tablewrap">
			<table class="mds-table mdsv-table">
				<thead><tr><th>차트번호</th><th>이름</th><th>담당 원장</th><th>담당 스탭</th><th>응답</th><th>전용 링크</th><th></th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr class="<?php echo $r->response_id ? 'is-answered' : ''; ?>">
						<td class="num"><?php echo esc_html( $r->chart_no ); ?></td>
						<td><b><?php echo esc_html( $r->patient_name ); ?></b></td>
						<td><?php echo esc_html( $r->doctor ); ?></td>
						<td><b><?php echo esc_html( $r->staff ); ?></b></td>
						<td><?php if ( $r->response_id ) : ?><span class="mds-status is-done">응답 <?php echo esc_html( date_i18n( 'H:i', strtotime( $r->answered_at ) ) ); ?></span><?php else : ?><span class="mds-status is-pending">대기</span><?php endif; ?></td>
						<td><input type="text" class="mdsv-link" readonly value="<?php echo esc_attr( add_query_arg( 't', $r->token, md_survey_public_url() ) ); ?>" onclick="this.select()" aria-label="전용 링크"></td>
						<td>
							<?php if ( ! $r->response_id ) : ?>
							<form method="post" class="mds-inline" onsubmit="return confirm('명단에서 뺄까요?');">
								<input type="hidden" name="md_survey_action" value="delete"><input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_delete' ) ); ?>">
								<input type="hidden" name="vid" value="<?php echo (int) $r->id; ?>"><input type="hidden" name="date" value="<?php echo esc_attr( $date ); ?>">
								<button type="submit" class="mds-btn mds-btn--ghost mds-btn--sm">빼기</button>
							</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="mds-hint">전용 링크는 본인 확인 없이 바로 설문이 열리는 환자별 주소입니다. 알림톡에 공통 링크(<?php echo esc_html( md_survey_public_url() ); ?>)를 쓰면 필요 없습니다.</p>
	<?php endif; ?>
	<?php
}

/** 응답 목록 (관리자) */
function md_survey_render_responses() {
	if ( ! md_survey_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	list( $from, $to ) = md_survey_range( isset( $_GET['from'] ) ? wp_unslash( $_GET['from'] ) : '', isset( $_GET['to'] ) ? wp_unslash( $_GET['to'] ) : '' );
	$staff = isset( $_GET['staff'] ) ? sanitize_text_field( wp_unslash( $_GET['staff'] ) ) : '';
	$rows  = md_survey_responses( $from, $to, $staff );
	global $wpdb;
	$staffs = $wpdb->get_col( 'SELECT DISTINCT staff FROM ' . md_survey_table_response() . ' ORDER BY staff' );
	?>
	<div class="mdsv-bar">
		<form method="get" class="mdsv-range" action="<?php echo esc_url( md_survey_admin_url() ); ?>">
			<?php md_sup_app_field(); ?><input type="hidden" name="sv" value="responses">
			<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"> ~ <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>">
			<select name="staff"><option value="">모든 스탭</option><?php foreach ( $staffs as $s ) : ?><option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $staff ); ?>><?php echo esc_html( $s ); ?></option><?php endforeach; ?></select>
			<button type="submit" class="mds-btn mds-btn--ghost">보기</button>
		</form>
		<form method="post" class="mds-inline">
			<input type="hidden" name="md_survey_action" value="export"><input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_export' ) ); ?>">
			<input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>"><input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>"><input type="hidden" name="staff" value="<?php echo esc_attr( $staff ); ?>">
			<button type="submit" class="mds-btn mds-btn--ghost">CSV 내려받기</button>
		</form>
	</div>
	<?php if ( empty( $rows ) ) : ?>
		<div class="mds-card"><div class="mds-empty">이 기간에 응답이 없습니다.</div></div>
	<?php else : ?>
		<div class="mds-tablewrap">
			<table class="mds-table mdsv-table">
				<thead><tr><th>진료일</th><th>환자</th><th>담당 원장</th><th>담당 스탭</th><th class="num">응대</th><th class="num">설명</th><th class="num">추천</th><th>의견</th><th>작성</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : $low = ( $r->q_service <= 2 || $r->q_explain <= 2 ); ?>
					<tr class="<?php echo $low ? 'is-lowrow' : ''; ?>">
						<td><?php echo esc_html( date_i18n( 'm.d', strtotime( $r->visit_date ) ) ); ?></td>
						<td><b><?php echo esc_html( $r->patient_name ); ?></b><span class="mds-item__meta"><?php echo esc_html( $r->chart_no ); ?></span></td>
						<td><?php echo esc_html( $r->doctor ); ?></td>
						<td><b><?php echo esc_html( $r->staff ); ?></b></td>
						<td class="num <?php echo $r->q_service <= 2 ? 'is-low' : ''; ?>"><?php echo (int) $r->q_service; ?></td>
						<td class="num <?php echo $r->q_explain <= 2 ? 'is-low' : ''; ?>"><?php echo (int) $r->q_explain; ?></td>
						<td class="num <?php echo $r->q_recommend <= 6 ? 'is-low' : ''; ?>"><?php echo (int) $r->q_recommend; ?></td>
						<td class="mdsv-comment"><?php echo nl2br( esc_html( (string) $r->comment ) ); ?></td>
						<td class="mds-last"><?php echo esc_html( date_i18n( 'm.d H:i', strtotime( $r->created_at ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="mds-hint">응대·설명 1~2점, 추천 6점 이하는 붉게 표시됩니다. 낮은 점수는 당일 실장이 전화로 확인하는 것을 권합니다.</p>
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
	<?php if ( empty( $rows ) ) : ?>
		<div class="mds-card"><div class="mds-empty">이 기간에 응답이 없습니다.</div></div>
	<?php else : ?>
		<div class="mds-tablewrap">
			<table class="mds-table mdsv-table">
				<thead><tr><th>담당 스탭</th><th class="num">응답 수</th><th class="num">응대 평균</th><th class="num">응대 5점 비율</th><th class="num">설명 평균</th><th class="num">설명 5점 비율</th><th class="num">추천 평균</th><th class="num">1~2점</th><th class="num">의견</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : $n = (int) $r->n; ?>
					<tr>
						<td><b><?php echo esc_html( $r->staff ); ?></b><?php if ( $n < 30 ) : ?><span class="mds-flag">표본 <?php echo $n; ?></span><?php endif; ?></td>
						<td class="num"><?php echo $n; ?></td>
						<td class="num"><?php echo number_format( (float) $r->avg_service, 2 ); ?></td>
						<td class="num"><b><?php echo round( 100 * (int) $r->top_service / $n ); ?>%</b></td>
						<td class="num"><?php echo number_format( (float) $r->avg_explain, 2 ); ?></td>
						<td class="num"><b><?php echo round( 100 * (int) $r->top_explain / $n ); ?>%</b></td>
						<td class="num"><?php echo number_format( (float) $r->avg_recommend, 1 ); ?></td>
						<td class="num <?php echo (int) $r->low_n ? 'is-low' : ''; ?>"><?php echo (int) $r->low_n; ?></td>
						<td class="num"><?php echo (int) $r->comment_n; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="mds-hint">기간 전체 <?php echo (int) $total; ?>건. 평균보다 <b>5점 비율</b>로 비교하세요 — 대부분 4~5점을 주므로 평균은 차이가 잘 나지 않습니다. 응답 30건 미만(표본 표시)은 우연에 좌우되니 비교에 쓰지 마세요. 발치·신경치료처럼 힘든 진료는 점수가 낮게 나오는 경향이 있습니다.</p>
	<?php endif; ?>
	<?php
}

/** 설정 (관리자) */
function md_survey_render_settings() {
	if ( ! md_survey_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	$s = md_survey_settings();
	?>
	<form method="post" class="mds-card mdsv-settings">
		<input type="hidden" name="md_survey_action" value="settings">
		<input type="hidden" name="md_survey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_survey_settings' ) ); ?>">
		<h2 class="mdsv-h">알림톡에 넣을 링크</h2>
		<p class="mdsv-url"><code><?php echo esc_html( home_url( '/survey/?n=#환자명#' ) ); ?></code></p>
		<p class="mds-hint">덴트웹 진료 후 알림톡 템플릿 본문에 이 주소를 그대로 넣습니다. 덴트웹이 <code>#환자명#</code>을 환자 이름으로 바꿔 보내므로, 환자는 휴대전화 가운데 4자리만 넣고 설문에 들어옵니다. 이름이 링크에 실리지 않은 경우(<code><?php echo esc_html( home_url( '/survey/' ) ); ?></code>)에는 환자가 이름을 직접 적습니다.</p>

		<div class="mds-formrow">
			<label class="mds-field"><span>응답 허용 기간 (진료일부터 며칠)</span><input type="number" name="window_days" min="1" max="30" value="<?php echo (int) $s['window_days']; ?>"></label>
		</div>
		<div class="mds-formrow">
			<label class="mds-field mds-field--grow"><span>원장 목록 (한 줄에 한 명)</span><textarea name="doctors" rows="8"><?php echo esc_textarea( $s['doctors'] ); ?></textarea></label>
			<label class="mds-field mds-field--grow"><span>스탭 목록 (한 줄에 한 명)</span><textarea name="staff" rows="8"><?php echo esc_textarea( $s['staff'] ); ?></textarea></label>
		</div>
		<p class="mds-hint">명단을 넣을 때 이름을 고르는 목록입니다. 목록에 없는 이름도 직접 쓸 수 있습니다.</p>

		<h2 class="mdsv-h">구글 리뷰 바로가기</h2>
		<div class="mds-formrow">
			<label class="mds-field mds-field--grow"><span>리뷰 링크 (비우면 구글 지도에서 병원을 찾는 링크를 씁니다)</span><input type="url" name="review_url" value="<?php echo esc_attr( (string) $s['review_url'] ); ?>" placeholder="https://g.page/r/…/review"></label>
		</div>
		<p class="mds-hint">응답을 마친 환자에게 「구글 리뷰 남기기」 버튼으로 보여 줍니다. 구글 비즈니스 프로필 → 「리뷰 받기」에서 복사한 짧은 링크(g.page/r/…/review)를 넣으면 리뷰 창이 바로 열립니다.</p>

		<h2 class="mdsv-h">덴트웹 자동 연동 키</h2>
		<p class="mdsv-url"><code><?php echo esc_html( (string) get_option( 'md_survey_api_key' ) ); ?></code></p>
		<p class="mds-hint">덴트웹 접수 명단을 프로그램이 자동으로 올릴 때 쓰는 키입니다. 주소 <code><?php echo esc_html( rest_url( 'md-survey/v1/visits' ) ); ?></code>에 헤더 <code>X-MD-Survey-Key</code>로 보냅니다. 외부에 알려지면 아래에서 새로 만드세요.</p>
		<label class="mds-check"><input type="checkbox" name="regen_key" value="1"> 연동 키를 새로 만든다 (기존 연동은 끊김)</label>

		<div class="mds-formbtns"><button type="submit" class="mds-btn mds-btn--fill">저장</button></div>
	</form>
	<?php
}
