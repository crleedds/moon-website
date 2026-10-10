<?php
/**
 * v7.0 · 미니차트 ← 덴트웹 자동 연동 (원장 지시 2026-10-06 「읽기 전용 DB 계정으로 완전 자동」)
 *
 *   병원 PC 의 mc-sync.ps1 이 덴트웹 읽기 전용 계정(dwpublic)으로 공개 뷰만 읽어 보낸다:
 *     PUB_V환자정보(성별 · 생년월일 · 주소 → 지역만 · 담당의사 · 등록일 · 최종 내원일)
 *     PUB_V진료비내역(진료일 · 담당의사 · 치료내용 — 금액은 보내지 않음)
 *     PUB_V예약정보(다음 예약) · PUB_P접수목록 · PUB_V직원정보(의사 번호 → 이름)
 *   덴트웹에는 병력 · 치료계획 뷰가 없어 그 칸은 지금처럼 손으로 적는다.
 *
 *   REST (헤더 X-MD-Survey-Key = 만족도 조사 연동 키 — 같은 프로그램 설정을 쓴다)
 *     GET  /wp-json/md-mc/v1/charts   미니차트에 있는 차트번호
 *     POST /wp-json/md-mc/v1/dw       { "scope": "full"|"upcoming", "patients": [ … ] }
 *   표 wp_md_mc_dw (차트번호마다 JSON 한 줄). 미니차트에 없는 환자(오늘 접수 · 7일 안 예약)는
 *   새 차트 만들 때 자동 채움용으로만 두고 14일 지나면 지운다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_MC_DW_SCHEMA', 1 );

function md_mc_dw_install() {
	if ( (int) get_option( 'md_mc_dw_schema', 0 ) >= MD_MC_DW_SCHEMA ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	dbDelta( 'CREATE TABLE ' . md_mc_t( 'dw' ) . " (
		chart_key VARCHAR(60) NOT NULL DEFAULT '',
		chart_no VARCHAR(60) NOT NULL DEFAULT '',
		data MEDIUMTEXT NULL,
		in_mc TINYINT NOT NULL DEFAULT 0,
		synced_at DATETIME NULL,
		PRIMARY KEY  (chart_key),
		KEY synced_at (synced_at)
	) $c;" );
	update_option( 'md_mc_dw_schema', MD_MC_DW_SCHEMA, false );
}
add_action( 'init', 'md_mc_dw_install', 21 );

/** 차트번호 비교용 — 앞 0 · 공백 무시 */
function md_mc_dw_key( $c ) {
	$c = preg_replace( '/\s+/', '', (string) $c );
	$k = ltrim( $c, '0' );
	return '' === $k ? $c : $k;
}

function md_mc_dw_get( $chart_no ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_mc_t( 'dw' ) . ' WHERE chart_key = %s', md_mc_dw_key( $chart_no ) ) );
	if ( ! $row ) { return null; }
	$d = json_decode( (string) $row->data, true );
	if ( ! is_array( $d ) ) { return null; }
	$d['_synced'] = $row->synced_at;
	return $d;
}

/* ============================================================
 * REST — 병원 PC 프로그램
 * ============================================================ */

function md_mc_dw_permission( $request ) {
	$key  = (string) $request->get_header( 'x-md-survey-key' );
	$want = (string) get_option( 'md_survey_api_key' );
	return '' !== $want && '' !== $key && hash_equals( $want, $key );
}

/** 미니차트 환자 차트번호 (앞 0 뺀 것) => [환자 id …] */
function md_mc_dw_mc_keys() {
	global $wpdb;
	$out = array();
	foreach ( (array) $wpdb->get_results( 'SELECT id, chart_no FROM ' . md_mc_t() . " WHERE kind = 'patient' AND deleted_at IS NULL AND chart_no <> ''" ) as $r ) {
		$out[ md_mc_dw_key( $r->chart_no ) ][] = (int) $r->id;
	}
	return $out;
}

function md_mc_dw_rest_charts() {
	return rest_ensure_response( array( 'charts' => array_map( 'strval', array_keys( md_mc_dw_mc_keys() ) ) ) );
}

function md_mc_dw_txt( $v, $n ) { return mb_substr( trim( wp_strip_all_tags( (string) $v ) ), 0, $n ); }
function md_mc_dw_date( $v ) {
	$d = preg_replace( '/\D/', '', (string) $v );
	if ( strlen( $d ) < 8 ) { return ''; }
	$y = (int) substr( $d, 0, 4 ); $m = (int) substr( $d, 4, 2 ); $dd = (int) substr( $d, 6, 2 );
	return checkdate( $m, $dd, $y ) ? sprintf( '%04d-%02d-%02d', $y, $m, $dd ) : '';
}

/** v9.8 · 주소는 동 · 읍 · 면까지만 (원장 지시 — 번지 · 도로명 번호 · 아파트 · 호수 · 리는 보이지 않게) */
function md_mc_addr_short( $s ) {
	$s = trim( preg_replace( '/\s+/u', ' ', (string) $s ) );
	if ( '' === $s ) { return ''; }
	$paren = '';
	if ( preg_match( '/[\(（]\s*([^,，\)）]+)/u', $s, $m ) ) { $paren = trim( $m[1] ); }
	$s   = trim( preg_replace( '/[\(（][^\)）]*[\)）]?/u', ' ', $s ) );
	$out = array();
	$adm = '/^(서울|부산|대구|인천|광주|대전|울산|세종|경기|강원|충북|충남|전북|전남|경북|경남|제주)$|(특별시|광역시|특별자치시|특별자치도|도|시|군|구)$/u';
	foreach ( preg_split( '/[\s,]+/u', $s ) as $i => $tk ) {
		if ( '' === $tk ) { continue; }
		if ( preg_match( '/^[가-힣]+[0-9]*[가-힣]*(동|읍|면|가)$/u', $tk ) && $out && ! preg_match( '/(로|길)[0-9]*(번길)?$/u', $tk ) ) { $out[] = $tk; return implode( ' ', $out ); }
		if ( preg_match( $adm, $tk ) && ! preg_match( '/[0-9]/', $tk ) ) { $out[] = $tk; continue; }
		break;
	}
	if ( $out && '' !== $paren && preg_match( '/^[가-힣]+[0-9]*[가-힣]*(동|읍|면|가)$/u', $paren ) ) { $out[] = $paren; }
	return implode( ' ', $out );
}

/** 덴트웹 지역 — 리는 빼고 동 · 읍 · 면까지 */
function md_mc_dw_region( $s ) { $s = md_mc_dw_txt( $s, 60 ); $t = md_mc_addr_short( $s ); return '' !== $t ? $t : $s; }

/** 「지역」 메모에 번지 · 호수 같은 세부 주소가 적혀 있으면 동 · 읍 · 면까지만 */
function md_mc_addr_memo( $s ) {
	$s = (string) $s;
	if ( ! preg_match( '/\d+\s*-\s*\d+|\d+\s*(번지|호|층)|(로|길)\s*\d|아파트|빌라|오피스텔|맨션/u', $s ) ) { return $s; }
	$short = md_mc_addr_short( $s );
	return '' !== $short ? $short : $s;
}

/** 받은 한 명을 정리 — 정해진 칸만, 길이 제한 */
function md_mc_dw_clean( $p ) {
	$o = array(
		'name'   => md_mc_dw_txt( $p['name'] ?? '', 60 ),
		'sex'    => in_array( $p['sex'] ?? '', array( 'M', 'F' ), true ) ? $p['sex'] : '',
		'birth'  => md_mc_dw_date( $p['birth'] ?? '' ),
		'region' => md_mc_dw_region( $p['region'] ?? '' ),
		'phone'  => '', /* v9.20 · 연락처는 받아도 두지 않는다 (원장 지시 2026-10-08 — 미니차트에서 없앰) */
		'addr'   => md_mc_addr_short( md_mc_dw_txt( $p['addr'] ?? '', 150 ) ), /* v9.8 · 세부 주소는 받지도 두지도 않는다 */
		'doctor' => md_mc_dw_txt( $p['doctor'] ?? '', 30 ),
		'first'  => md_mc_dw_date( $p['first'] ?? '' ),
		'last'   => md_mc_dw_date( $p['last'] ?? '' ),
		'next'   => null,
		'visits' => array(),
	);
	if ( ! empty( $p['next'] ) && is_array( $p['next'] ) ) {
		$at = preg_replace( '/\D/', '', (string) ( $p['next']['at'] ?? '' ) );
		if ( strlen( $at ) >= 8 && md_mc_dw_date( $at ) ) {
			$o['next'] = array(
				'at'     => md_mc_dw_date( $at ) . ( strlen( $at ) >= 12 ? ' ' . substr( $at, 8, 2 ) . ':' . substr( $at, 10, 2 ) : '' ),
				'doctor' => md_mc_dw_txt( $p['next']['doctor'] ?? '', 30 ),
				'what'   => md_mc_dw_txt( $p['next']['what'] ?? '', 200 ),
				'memo'   => md_mc_dw_txt( $p['next']['memo'] ?? '', 300 ),
			);
		}
	}
	foreach ( array_slice( (array) ( $p['visits'] ?? array() ), 0, 60 ) as $v ) {
		if ( ! is_array( $v ) ) { continue; }
		$d = md_mc_dw_date( $v['d'] ?? '' );
		if ( ! $d ) { continue; }
		$o['visits'][] = array( 'd' => $d, 'dr' => md_mc_dw_txt( $v['dr'] ?? '', 30 ), 'tx' => md_mc_dw_txt( $v['tx'] ?? '', 500 ) );
	}
	/* v9.50 · 그날 무슨 예약으로 왔는지 (원장 지시) — 오늘 예약(접수 전 · 후) + 지난날 이행된 예약. appt_from 이 있으면 그날부터의 목록을 새로 받은 것 */
	$o['appts'] = array(); $o['appt_from'] = md_mc_dw_date( $p['appt_from'] ?? '' );
	foreach ( array_slice( (array) ( $p['appts'] ?? array() ), 0, 120 ) as $a ) {
		if ( ! is_array( $a ) ) { continue; }
		$at = preg_replace( '/\D/', '', (string) ( $a['at'] ?? '' ) );
		$d  = md_mc_dw_date( $at );
		if ( ! $d ) { continue; }
		$o['appts'][] = array( 'd' => $d, 't' => strlen( $at ) >= 12 ? substr( $at, 8, 2 ) . ':' . substr( $at, 10, 2 ) : '', 'dr' => md_mc_dw_txt( $a['doctor'] ?? '', 30 ), 'what' => md_mc_dw_txt( $a['what'] ?? '', 200 ), 'memo' => md_mc_dw_txt( $a['memo'] ?? '', 300 ) );
	}
	/* 최근 내원 = 덴트웹 최종 내원일과 진료 기록의 가장 늦은 날 중 늦은 것 (오늘 이후는 버림) */
	$today = current_time( 'Y-m-d' );
	foreach ( $o['visits'] as $v ) { if ( $v['d'] <= $today && $v['d'] > $o['last'] ) { $o['last'] = $v['d']; } }
	if ( $o['last'] > $today ) { $o['last'] = ''; }
	return $o;
}

/** v9.50 · 예전에 받아 둔 예약 + 이번에 받은 예약 — appt_from 날부터는 이번 것으로 바꾸고(고침 · 취소 반영), 그 전 것은 그대로 둔다 */
/** v9.51 · 치료계획 칸 아래 「다음 예약」 목록 — 내일부터의 덴트웹 예약 (원장 지시) */
function md_mc_dw_next_list_html( $d ) {
	$today = current_time( 'Y-m-d' ); $list = array();
	foreach ( ( $d && ! empty( $d['appts'] ) ) ? (array) $d['appts'] : array() as $a ) { if ( is_array( $a ) && ( $a['d'] ?? '' ) > $today ) { $list[] = $a; } }
	if ( ! $list ) { return ''; }
	usort( $list, function ( $x, $y ) { return strcmp( $x['d'] . $x['t'], $y['d'] . $y['t'] ); } );
	$wd = array( '일', '월', '화', '수', '목', '금', '토' );
	$h = '<div class="mc-nextlist"><b class="mc-nextlist__h">📅 다음 예약</b><ul>';
	foreach ( $list as $i => $a ) {
		$ts = strtotime( $a['d'] );
		$bits = array_filter( array( $a['t'], $a['dr'], $a['what'] ), 'strlen' );
		$h .= '<li' . ( $i >= 5 ? ' class="mc-nextlist__more" hidden' : '' ) . '><span class="mc-nextlist__d">' . esc_html( date( 'n/j', $ts ) . '(' . $wd[ (int) date( 'w', $ts ) ] . ')' ) . '</span> ' . esc_html( implode( ' · ', $bits ) ) . ( '' !== $a['memo'] ? ' <small>(' . esc_html( $a['memo'] ) . ')</small>' : '' ) . '</li>';
	}
	$h .= '</ul>' . ( count( $list ) > 5 ? '<button type="button" class="mc-nextlist__btn" onclick="this.previousElementSibling.querySelectorAll(\'.mc-nextlist__more\').forEach(function(x){x.hidden=false});this.remove()">' . ( count( $list ) - 5 ) . '건 더 보기</button>' : '' ) . '</div>';
	return $h;
}

function md_mc_dw_merge_appts( $old_json, $data ) {
	$old  = json_decode( (string) $old_json, true );
	$keep = is_array( $old ) && ! empty( $old['appts'] ) && is_array( $old['appts'] ) ? $old['appts'] : array();
	$from = (string) ( $data['appt_from'] ?? '' );
	if ( '' === $from ) { return $keep; } /* 예약을 보내지 않은 묶음 — 예전 것 그대로 */
	$out = array(); $today = current_time( 'Y-m-d' );
	/* v9.51 · 예약은 오늘 · 앞으로만 둔다 — 지난 예약은 오늘이 지나면 없어짐 (원장 지시) */
	foreach ( $keep as $a ) { if ( is_array( $a ) && ( $a['d'] ?? '' ) < $from && ( $a['d'] ?? '' ) >= $today ) { $out[] = $a; } }
	foreach ( (array) $data['appts'] as $a ) { if ( ( $a['d'] ?? '' ) >= $today ) { $out[] = $a; } }
	usort( $out, function ( $x, $y ) { return strcmp( $x['d'] . $x['t'], $y['d'] . $y['t'] ); } );
	return array_slice( $out, 0, 100 );
}

function md_mc_dw_rest_save( $request ) {
	global $wpdb;
	md_mc_dw_install();
	$body  = $request->get_json_params();
	/* v8.5 · 호스팅 방화벽이 진료 문구를 막아(502) 병원 PC 는 묶음을 base64 로 감싸 보낸다 */
	if ( isset( $body['b64'] ) && is_string( $body['b64'] ) ) {
		$dec  = json_decode( (string) base64_decode( $body['b64'], true ), true );
		$body = is_array( $dec ) ? $dec : array();
	}
	$rows  = isset( $body['patients'] ) && is_array( $body['patients'] ) ? $body['patients'] : array();
	$scope = ( $body['scope'] ?? '' ) === 'full' ? 'full' : 'upcoming';
	$mc    = md_mc_dw_mc_keys();
	$reqd  = (array) get_option( 'md_mc_dw_req', array() ); /* v8.4 · 새 차트에서 찾은 환자는 진료기록도 */
	$now   = current_time( 'mysql' );
	$t     = md_mc_t( 'dw' );
	$n = 0; $lv = 0;
	foreach ( $rows as $p ) {
		if ( ! is_array( $p ) ) { continue; }
		$chart = md_mc_dw_txt( $p['chart_no'] ?? '', 60 );
		if ( '' === $chart ) { continue; }
		$key  = md_mc_dw_key( $chart );
		$in   = isset( $mc[ $key ] ) ? 1 : 0;
		$data = md_mc_dw_clean( $p );
		if ( ! $in && ! isset( $reqd[ $key ] ) ) { $data['visits'] = array(); $data['next'] = null; $data['appts'] = array(); $data['appt_from'] = ''; } /* 미니차트에 없는 환자는 기본 정보만 (새 차트에서 찾은 환자 빼고) */
		else { $data['appts'] = md_mc_dw_merge_appts( $wpdb->get_var( $wpdb->prepare( "SELECT data FROM $t WHERE chart_key = %s", $key ) ), $data ); } /* v9.50 · 덴트웹 예약표는 한 달 남짓만 보이므로 지난 예약은 여기 쌓아 둔다 */
		unset( $data['appt_from'] );
		$wpdb->replace( $t, array( 'chart_key' => $key, 'chart_no' => $chart, 'data' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ), 'in_mc' => $in, 'synced_at' => $now ) );
		$n++;
		/* 미니차트 「최근 내원순」 — 덴트웹 최종 내원일이 더 늦으면 */
		if ( $in && $data['last'] ) {
			$ids = implode( ',', array_map( 'intval', $mc[ $key ] ) );
			$lv += (int) $wpdb->query( $wpdb->prepare( 'UPDATE ' . md_mc_t() . " SET last_visit = %s WHERE id IN ($ids) AND ( last_visit IS NULL OR last_visit < %s )", $data['last'], $data['last'] ) );
		}
	}
	/* 정리 — 미니차트에 없는 사람은 14일, 전체 동기화 때 미니차트에서 빠진 사람은 바로 */
	$gone = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $t WHERE in_mc = 0 AND synced_at < %s", date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 14 * DAY_IN_SECONDS ) ) );
	/* 여러 번에 나눠 보낼 때는 마지막 묶음(final)에서만 「미니차트에서 빠진 사람」 정리 */
	if ( 'full' === $scope && ( ! isset( $body['final'] ) || ! empty( $body['final'] ) ) ) {
		foreach ( (array) $wpdb->get_col( "SELECT chart_key FROM $t WHERE in_mc = 1" ) as $k ) {
			if ( ! isset( $mc[ $k ] ) ) { $wpdb->delete( $t, array( 'chart_key' => $k ) ); $gone++; }
		}
	}
	update_option( 'md_mc_dw_last', array( 'at' => $now, 'scope' => $scope, 'n' => $n ), false );
	/* 찾아 달라고 한 차트번호 중 답이 온 것은 지운다 */
	$req = (array) get_option( 'md_mc_dw_req', array() );
	foreach ( $rows as $p ) { if ( is_array( $p ) && isset( $p['chart_no'] ) ) { unset( $req[ md_mc_dw_key( $p['chart_no'] ) ] ); } }
	foreach ( $req as $k => $t ) { if ( $t < time() - 10 * MINUTE_IN_SECONDS ) { unset( $req[ $k ] ); } }
	update_option( 'md_mc_dw_req', $req, false );
	return rest_ensure_response( array( 'saved' => $n, 'last_visit_updated' => $lv, 'removed' => $gone ) );
}

/** v8.1 · 새 차트에서 찾아 달라고 한 차트번호 (병원 PC 가 1분마다 가져가 덴트웹에서 찾아 보낸다) */
function md_mc_dw_rest_requests() {
	update_option( 'md_mc_dw_poll', time(), false );
	$req = (array) get_option( 'md_mc_dw_req', array() );
	$out = array();
	foreach ( $req as $k => $t ) { if ( $t >= time() - 10 * MINUTE_IN_SECONDS ) { $out[] = (string) $k; } }
	return rest_ensure_response( array( 'charts' => $out, 'schema' => (int) get_option( 'md_mc_schema', 0 ) ) );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-mc/v1', '/requests', array( 'methods' => 'GET', 'callback' => 'md_mc_dw_rest_requests', 'permission_callback' => 'md_mc_dw_permission' ) );
	register_rest_route( 'md-mc/v1', '/charts', array( 'methods' => 'GET', 'callback' => 'md_mc_dw_rest_charts', 'permission_callback' => 'md_mc_dw_permission' ) );
	register_rest_route( 'md-mc/v1', '/dw', array( 'methods' => 'POST', 'callback' => 'md_mc_dw_rest_save', 'permission_callback' => 'md_mc_dw_permission' ) );
} );

function md_mc_dw_enqueue( $chart ) {
	$k = md_mc_dw_key( $chart );
	if ( '' === $k || ! preg_match( '/^[0-9A-Za-z\-]{1,20}$/', $k ) ) { return; }
	$req = (array) get_option( 'md_mc_dw_req', array() );
	if ( count( $req ) < 50 || isset( $req[ $k ] ) ) { $req[ $k ] = time(); update_option( 'md_mc_dw_req', $req, false ); }
}
/* v8.4 · 새 환자를 저장하면 그 차트의 덴트웹 진료기록 · 다음 예약을 1분 안에 받아 오게 */
add_action( 'md_mc_created', function ( $id ) { $r = md_mc_get( $id ); if ( $r ) { md_mc_dw_enqueue( $r->chart_no ); } } );

/* ============================================================
 * 새 차트 — 차트번호를 넣으면 덴트웹 기본 정보로 채움 (GET ?app=minichart&md_mc_dw=차트번호 · JSON)
 * ============================================================ */

function md_mc_dw_lookup() {
	if ( ! isset( $_GET['md_mc_dw'] ) ) { return; }
	if ( ! md_mc_can_use() ) { wp_send_json( array( 'ok' => false ), 403 ); }
	$c = sanitize_text_field( wp_unslash( $_GET['md_mc_dw'] ) );
	/* v8.4 · 같은 차트번호가 미니차트에 이미 있으면 저장 전에 알린다 */
	$dup = null;
	$mc  = md_mc_dw_mc_keys();
	$k   = md_mc_dw_key( $c );
	if ( isset( $mc[ $k ] ) ) {
		$e = md_mc_get( $mc[ $k ][0] );
		if ( $e ) { $dup = array( 'id' => (int) $e->id, 'chart' => $e->chart_no, 'name' => $e->pname, 'url' => md_mc_url( array( 'mv' => 'p', 'mid' => $e->id ) ) ); }
	}
	$d = md_mc_dw_get( $c );
	if ( ! $d ) {
		/* 아직 받아 둔 게 없으면 병원 PC 에 찾아 달라고 줄 세운다 (1분 안에 답) */
		if ( ! $dup ) { md_mc_dw_enqueue( $c ); }
		$last = get_option( 'md_mc_dw_poll' );
		wp_send_json( array( 'ok' => false, 'dup' => $dup, 'pending' => ! $dup && $last && ( time() - (int) $last ) < 10 * MINUTE_IN_SECONDS ) );
	}
	/* 기본 정보만 있으면(진료기록 없음) 지금 진료기록도 받아 오게 줄 세움 — 저장하면 바로 보이도록 */
	if ( ! $dup && empty( $d['visits'] ) ) { md_mc_dw_enqueue( $c ); }
	wp_send_json( array( 'ok' => true, 'dup' => $dup, 'visits' => count( (array) $d['visits'] ), 'name' => $d['name'], 'region' => md_mc_dw_region( $d['region'] ), 'doctor' => $d['doctor'], 'age' => md_mc_dw_age( $d['birth'] ), 'sex' => $d['sex'],
		'addr' => md_mc_addr_short( $d['addr'] ?? '' ), 'first' => $d['first'], 'last' => $d['last'] ) );
}
add_action( 'template_redirect', 'md_mc_dw_lookup', 2 );

/* ============================================================
 * 차트 화면 — 「덴트웹」 칸
 * ============================================================ */

function md_mc_dw_age( $birth ) {
	if ( ! $birth ) { return ''; }
	$b = strtotime( $birth ); $n = current_time( 'timestamp' );
	$a = (int) date( 'Y', $n ) - (int) date( 'Y', $b );
	if ( date( 'md', $n ) < date( 'md', $b ) ) { $a--; }
	return $a >= 0 && $a < 130 ? $a : '';
}

/** v8.1 · 진료기록 — 덴트웹 진료비 내역의 치료내용 + 미니차트에 직접 적은 「YYMMDD: …」 를 날짜별로 합친다 */
function md_mc_dw_timeline( $r, $d ) {
	$rows = array();
	$hide = json_decode( (string) ( $r->dw_hide ?? '' ), true );
	$hide = is_array( $hide ) ? $hide : array();
	if ( $d && ! empty( $d['visits'] ) ) {
		foreach ( $d['visits'] as $v ) {
			$k = $v['d'] . ':' . substr( md5( (string) $v['tx'] ), 0, 8 ); /* v8.7 · 미니차트에서 지우거나 고친 덴트웹 줄 — v9.43 부터는 그냥 지운 것으로 다룬다(숨긴 기록 칸 없음). 덴트웹 원본은 읽기 전용이라 이 표식으로 다시 들어오지 않게 한다 */
			if ( isset( $hide[ $k ] ) ) { continue; }
			$rows[ $v['d'] ] = array( 'dr' => $v['dr'], 'dw' => $v['tx'], 'dwk' => $k, 'own' => array() );
		}
	}
	/* v9.50 · 그날 무슨 예약으로 왔는지 — 오늘과 지난날만 (앞으로의 예약은 머리의 「다음 예약」) */
	$appts = array(); $today = current_time( 'Y-m-d' );
	foreach ( ( $d && ! empty( $d['appts'] ) ) ? (array) $d['appts'] : array() as $a ) {
		if ( ! is_array( $a ) || empty( $a['d'] ) || $a['d'] !== $today ) { continue; } /* v9.51 · 진료기록에는 오늘 예약만 (원장 지시) */
		$appts[ $a['d'] ][] = $a;
		if ( ! isset( $rows[ $a['d'] ] ) ) { $rows[ $a['d'] ] = array( 'dr' => '', 'dw' => '', 'own' => array() ); }
	}
	foreach ( $appts as $ad => $list ) { usort( $appts[ $ad ], function ( $x, $y ) { return strcmp( $x['t'], $y['t'] ); } ); }
	$undated = array(); $cur = null;
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $r->tx_hist ) as $li => $ln ) { /* v9.22 · 줄 번호($li)를 들고 가서 그 줄만 고치거나 지운다 */
		if ( '' === trim( $ln ) ) { continue; }
		/* v9.13 · 「000000:」처럼 날짜 자리가 있지만 날짜가 아닌 줄(AppSheet 때 날짜 모름)은 「날짜 없는 기록」으로 — 바로 위 날짜 줄에 딸려 들어가지 않게 (원장 지적: 오늘 줄을 넣자 옛 기록이 오늘로 보임) */
		/* v9.28 · 앞머리는 「2026-04-22:」 또는 옛 「260422:」. 날짜가 아닌 것(000000:)은 날짜 없는 기록 */
		if ( preg_match( '/^\s*' . MD_MC_DATE_RE . '\s*:\s*(.*)$/u', $ln, $m ) && null === md_mc_date_parse( $m[1] ) ) {
			$undated[] = array( 't' => '' !== trim( $m[2] ) ? trim( $m[2] ) : trim( $ln ), 'i' => $li );
			$cur = null; /* v9.22 · 그 아래 이어지는 줄도 날짜 없는 기록으로 */
			continue;
		}
		if ( preg_match( '/^\s*' . MD_MC_DATE_RE . '\s*:\s*(.*)$/u', $ln, $m ) && null !== md_mc_date_parse( $m[1] ) ) {
			$cur = md_mc_date_parse( $m[1] );
			if ( ! isset( $rows[ $cur ] ) ) { $rows[ $cur ] = array( 'dr' => '', 'dw' => '', 'own' => array() ); }
			$t = trim( $m[2] );
			$same = '' !== $t && '' !== $rows[ $cur ]['dw'] && preg_replace( '/\s+/u', '', $t ) === preg_replace( '/\s+/u', '', $rows[ $cur ]['dw'] );
			if ( '' !== $t && ! $same ) { $rows[ $cur ]['own'][] = array( 't' => $t, 'i' => $li ); }
			continue;
		}
		if ( null !== $cur ) { $rows[ $cur ]['own'][] = array( 't' => trim( $ln ), 'i' => $li ); } else { $undated[] = array( 't' => trim( $ln ), 'i' => $li ); }
	}
	krsort( $rows );
	$h = '<ol class="mc-tl">'; $i = 0; $n = count( $rows );
	foreach ( $rows as $date => $x ) {
		if ( 10 === $i ) { $h .= '</ol><details class="mc-tl__more"><summary>이전 기록 ' . ( $n - 10 ) . '건 더 보기</summary><ol class="mc-tl">'; }
		$h .= '<li><span class="mc-tl__d">' . esc_html( substr( str_replace( '-', '.', $date ), 2 ) ) . '</span><span class="mc-tl__dr">' . esc_html( $x['dr'] ) . '</span><span class="mc-tl__tx">';
		foreach ( $appts[ $date ] ?? array() as $a ) {
			$bits = array_filter( array( $a['t'], $a['dr'], $a['what'] ), 'strlen' );
			$h .= '<span class="mc-tl__appt' . ( $date === $today ? ' is-today' : '' ) . '" title="덴트웹 예약">📅 ' . ( $date === $today ? '오늘 예약' : '예약' ) . ' ' . esc_html( implode( ' · ', $bits ) ) . ( '' !== $a['memo'] ? ' <small>(' . esc_html( $a['memo'] ) . ')</small>' : '' ) . '</span>';
		}
		if ( '' !== $x['dw'] ) {
			$nf = function ( $act ) use ( $r ) { return '<input type="hidden" name="md_mc_action" value="' . $act . '"><input type="hidden" name="md_mc_nonce" value="' . esc_attr( wp_create_nonce( 'md_mc_' . $act ) ) . '"><input type="hidden" name="mid" value="' . (int) $r->id . '">'; };
			$h .= '<span class="mc-tl__dw">' . esc_html( $x['dw'] )
				. ' <span class="mc-tl__acts"><button type="button" class="mc-tl__btn" data-mc-dwedit title="이 줄 고치기">✎</button>'
				. '<form method="post" class="mc-inline" action="' . esc_url( md_mc_url() ) . '" data-mc-fast="dwhide">' . $nf( 'dwhide' ) . '<input type="hidden" name="dwkey" value="' . esc_attr( $x['dwk'] ) . '"><button class="mc-tl__btn" title="이 줄 지우기">✕</button></form></span></span>'
				. '<form method="post" class="mc-tl__edit" action="' . esc_url( md_mc_url() ) . '" data-mc-fast="dwedit" hidden>' . $nf( 'dwedit' ) . '<input type="hidden" name="dwkey" value="' . esc_attr( $x['dwk'] ) . '"><textarea name="text" rows="2">' . esc_textarea( $x['dw'] ) . '</textarea><span><button class="mds-btn mds-btn--fill">저장</button> <button type="button" class="mds-btn mds-btn--ghost" data-mc-dwedit-cancel>취소</button></span></form>';
		}
		foreach ( $x['own'] as $o ) { $h .= md_mc_own_line_html( $r, $o ); }
		/* v9.14 · 덴트웹에 내원은 잡혔는데 치료내용이 아직 비어 있는 날 (진료 중 · 입력 전) — 빈 줄 대신 안내 (원장 지적) */
		if ( '' === $x['dw'] && ! $x['own'] && isset( $x['dwk'] ) ) { $h .= '<span class="mc-tl__wait">치료내용 아직 없음 — 덴트웹에 입력되면 5분 안에 들어옵니다 (오늘 접수 환자)</span>'; }
		$h .= '</span></li>';
		$i++;
	}
	$h .= '</ol>' . ( $n > 10 ? '</details>' : '' );
	/* v9.43 · 「숨긴 덴트웹 기록」 칸 없앰 (원장 지시 — ✕ 로 지우면 그냥 지워진 것) */
	if ( $undated ) {
		$h .= '<div class="mc-tl__undated"><b>날짜 없는 기록</b>';
		foreach ( $undated as $o ) { $h .= md_mc_own_line_html( $r, $o, true ); }
		$h .= '</div>';
	}
	if ( ! $n && ! $undated ) { $h = '<p class="mc-none">아직 진료기록이 없습니다.</p>'; }
	return $h;
}

/** v9.22 · 직접 적은 진료기록 한 줄 — ✎ 고치기 · ✕ 지우기 (원장 지시 「날짜 없는 기록도 바로 수정」). $o = ['t' => 글, 'i' => tx_hist 줄 번호] */
function md_mc_own_line_html( $r, $o, $undated = false ) {
	$nf = function ( $act ) use ( $r, $o ) {
		return '<input type="hidden" name="md_mc_action" value="' . $act . '"><input type="hidden" name="md_mc_nonce" value="' . esc_attr( wp_create_nonce( 'md_mc_' . $act ) ) . '"><input type="hidden" name="mid" value="' . (int) $r->id . '">'
			. '<input type="hidden" name="oi" value="' . (int) $o['i'] . '"><input type="hidden" name="otext" value="' . esc_attr( $o['t'] ) . '">';
	};
	return '<span class="mc-tl__ownw' . ( $undated ? ' mc-tl__ownw--undated' : '' ) . '"><span class="mc-tl__own" title="미니차트에 직접 적은 기록">' . ( $undated ? '' : '✎ ' ) . esc_html( $o['t'] )
		. ' <span class="mc-tl__acts"><button type="button" class="mc-tl__btn" data-mc-dwedit title="이 줄 고치기">✎</button>'
		. '<form method="post" class="mc-inline" action="' . esc_url( md_mc_url() ) . '" data-mc-fast="owndel">' . $nf( 'owndel' ) . '<button class="mc-tl__btn" title="이 줄 지우기">✕</button></form></span></span>'
		. '<form method="post" class="mc-tl__edit" action="' . esc_url( md_mc_url() ) . '" data-mc-fast="ownedit" hidden>' . $nf( 'ownedit' ) . '<textarea name="text" rows="2">' . esc_textarea( $o['t'] ) . '</textarea><span><button class="mds-btn mds-btn--fill">저장</button> <button type="button" class="mds-btn mds-btn--ghost" data-mc-dwedit-cancel>취소</button></span></form></span>';
}

/** v8.1 · 담당의사 — 미니차트 기본 담당의사가 비었으면 덴트웹 담당의사, 다르면 둘 다 */
function md_mc_dw_dr_html( $r, $d ) {
	list( $main, $pairs, $extra ) = md_mc_dr_parse( $r->dr );
	$dw  = $d ? (string) $d['doctor'] : '';
	$out = array();
	$names = array_merge( array( $main ), array_column( $pairs, 1 ) );
	if ( '' === $main && ! $pairs && '' !== $dw ) { $main = $dw; }
	if ( '' !== $main ) { $out[] = '<span class="mc-dr__main"><b>' . esc_html( $main ) . '</b></span>'; }
	foreach ( $pairs as $pr ) { $out[] = '<span class="mc-dr__pairv"><small>' . esc_html( $pr[0] ) . '</small> <b>' . esc_html( $pr[1] ) . '</b></span>'; }
	$hide = json_decode( (string) ( $r->dw_hide ?? '' ), true );
	if ( '' !== $dw && ! in_array( $dw, $names, true ) && $dw !== $main && empty( $hide['dr'] ) ) {
		/* v9.25 · ✎ 는 담당의사 칸 고치기를 열고, ✕ 는 이 줄을 미니차트에서 숨긴다 (원장 지시) */
		/* 담당의사 칸 자체가 폼이라 안에 폼을 또 넣을 수 없다 — ✕ 는 단추만 두고 화면(JS)이 직접 보낸다 */
		$out[] = '<span class="mc-dr__pairv mc-dr__dw"><small>덴트웹</small> ' . esc_html( $dw )
			. ' <span class="mc-tl__acts"><button type="button" class="mc-tl__btn" data-mc-open-dr title="담당의사 고치기">✎</button>'
			. '<button type="button" class="mc-tl__btn" data-mc-dwhidedr="' . esc_attr( wp_create_nonce( 'md_mc_dwhidedr' ) ) . '" data-mid="' . (int) $r->id . '" data-url="' . esc_url( md_mc_url() ) . '" title="덴트웹 담당의사 표시 지우기">✕</button></span></span>';
	}
	if ( '' !== $extra ) { $out[] = md_mc_text( $extra ); }
	return $out ? implode( '<br>', $out ) : '<span class="mc-none">—</span>';
}
