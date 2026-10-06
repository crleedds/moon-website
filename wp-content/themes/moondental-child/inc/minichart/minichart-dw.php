<?php
/**
 * v7.0 · 미니차트 ← 덴트웹 자동 연동 (원장 지시 2026-10-06 「읽기 전용 DB 계정으로 완전 자동」)
 *
 *   병원 PC 의 mc-sync.ps1 이 덴트웹 읽기 전용 계정(dwpublic)으로 공개 뷰만 읽어 보낸다:
 *     PUB_V환자정보(성별 · 생년월일 · 주소 → 지역만 · 담당의 · 등록일 · 최종 내원일)
 *     PUB_V진료비내역(진료일 · 담당의 · 치료내용 — 금액은 보내지 않음)
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

/** 받은 한 명을 정리 — 정해진 칸만, 길이 제한 */
function md_mc_dw_clean( $p ) {
	$o = array(
		'name'   => md_mc_dw_txt( $p['name'] ?? '', 60 ),
		'sex'    => in_array( $p['sex'] ?? '', array( 'M', 'F' ), true ) ? $p['sex'] : '',
		'birth'  => md_mc_dw_date( $p['birth'] ?? '' ),
		'region' => md_mc_dw_txt( $p['region'] ?? '', 60 ),
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
	/* 최근 내원 = 덴트웹 최종 내원일과 진료 기록의 가장 늦은 날 중 늦은 것 (오늘 이후는 버림) */
	$today = current_time( 'Y-m-d' );
	foreach ( $o['visits'] as $v ) { if ( $v['d'] <= $today && $v['d'] > $o['last'] ) { $o['last'] = $v['d']; } }
	if ( $o['last'] > $today ) { $o['last'] = ''; }
	return $o;
}

function md_mc_dw_rest_save( $request ) {
	global $wpdb;
	md_mc_dw_install();
	$body  = $request->get_json_params();
	$rows  = isset( $body['patients'] ) && is_array( $body['patients'] ) ? $body['patients'] : array();
	$scope = ( $body['scope'] ?? '' ) === 'full' ? 'full' : 'upcoming';
	$mc    = md_mc_dw_mc_keys();
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
		if ( ! $in ) { $data['visits'] = array(); $data['next'] = null; } /* 미니차트에 없는 환자는 기본 정보만 */
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
	return rest_ensure_response( array( 'saved' => $n, 'last_visit_updated' => $lv, 'removed' => $gone ) );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-mc/v1', '/charts', array( 'methods' => 'GET', 'callback' => 'md_mc_dw_rest_charts', 'permission_callback' => 'md_mc_dw_permission' ) );
	register_rest_route( 'md-mc/v1', '/dw', array( 'methods' => 'POST', 'callback' => 'md_mc_dw_rest_save', 'permission_callback' => 'md_mc_dw_permission' ) );
} );

/* ============================================================
 * 새 차트 — 차트번호를 넣으면 덴트웹 기본 정보로 채움 (GET ?app=minichart&md_mc_dw=차트번호 · JSON)
 * ============================================================ */

function md_mc_dw_lookup() {
	if ( ! isset( $_GET['md_mc_dw'] ) ) { return; }
	if ( ! md_mc_can_use() ) { wp_send_json( array( 'ok' => false ), 403 ); }
	$d = md_mc_dw_get( sanitize_text_field( wp_unslash( $_GET['md_mc_dw'] ) ) );
	if ( ! $d ) { wp_send_json( array( 'ok' => false ) ); }
	wp_send_json( array( 'ok' => true, 'name' => $d['name'], 'region' => $d['region'], 'doctor' => $d['doctor'], 'age' => md_mc_dw_age( $d['birth'] ), 'sex' => $d['sex'] ) );
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

function md_mc_dw_card( $r ) {
	$d = md_mc_dw_get( $r->chart_no );
	$last = get_option( 'md_mc_dw_last' );
	if ( ! $d ) {
		if ( ! $last ) { return ''; }
		return '<section class="mds-card mc-dw mc-dw--none"><h3 class="mc-block__h">덴트웹</h3><p class="mds-hint">덴트웹에서 이 차트번호를 찾지 못했습니다. 차트번호를 확인해 주세요.</p></section>';
	}
	$age = md_mc_dw_age( $d['birth'] );
	$sex = 'M' === $d['sex'] ? '남' : ( 'F' === $d['sex'] ? '여' : '' );
	$h   = '<section class="mds-card mc-dw"><h3 class="mc-block__h">덴트웹 <small>' . esc_html( md_mc_short_date( $d['_synced'] ) . ' ' . date( 'H:i', strtotime( $d['_synced'] ) ) ) . ' 자동</small></h3>';
	/* 이름이 다르면 차트번호가 잘못 붙었을 수 있다 */
	$nm = preg_replace( '/\s+/u', '', (string) $d['name'] );
	if ( '' !== $nm && false === mb_strpos( preg_replace( '/\s+/u', '', (string) $r->pname ), $nm ) ) {
		$h .= '<p class="mds-notice mds-notice--warn">덴트웹 이름은 「' . esc_html( $d['name'] ) . '」입니다 — 차트번호를 확인해 주세요.</p>';
	}
	$items = array();
	if ( $sex || '' !== $age ) { $items[] = array( '성별 · 나이', trim( $sex . ( '' !== $age ? ' ' . $age . '세' : '' ) ) ); }
	if ( '' !== $d['region'] ) { $items[] = array( '지역', $d['region'] ); }
	if ( '' !== $d['doctor'] ) { $items[] = array( '덴트웹 담당의', $d['doctor'] ); }
	if ( $d['first'] ) { $items[] = array( '첫 등록', $d['first'] ); }
	if ( $d['last'] ) { $items[] = array( '최근 내원', $d['last'] ); }
	$h .= '<dl class="mc-dw__facts">';
	foreach ( $items as $it ) { $h .= '<div><dt>' . esc_html( $it[0] ) . '</dt><dd>' . esc_html( $it[1] ) . '</dd></div>'; }
	$h .= '</dl>';
	if ( $d['next'] ) {
		$n = $d['next'];
		$h .= '<p class="mc-dw__next"><b>다음 예약</b> ' . esc_html( $n['at'] ) . ( '' !== $n['doctor'] ? ' · ' . esc_html( $n['doctor'] ) : '' ) . ( '' !== $n['what'] ? ' · ' . esc_html( $n['what'] ) : '' ) . ( '' !== $n['memo'] ? '<br><small>' . esc_html( $n['memo'] ) . '</small>' : '' ) . '</p>';
	}
	if ( $d['visits'] ) {
		$h .= '<h4 class="mc-dw__h">진료 기록 <small>(덴트웹 진료비 내역의 치료내용)</small></h4><ol class="mc-dw__visits">';
		foreach ( $d['visits'] as $i => $v ) {
			if ( 8 === $i ) { $h .= '</ol><details class="mc-dw__more"><summary>이전 기록 ' . ( count( $d['visits'] ) - 8 ) . '건 더 보기</summary><ol class="mc-dw__visits">'; }
			$h .= '<li><span class="mc-dw__d">' . esc_html( substr( $v['d'], 2 ) ) . '</span>' . ( '' !== $v['dr'] ? '<span class="mc-dw__dr">' . esc_html( $v['dr'] ) . '</span>' : '' ) . '<span class="mc-dw__tx">' . esc_html( '' !== $v['tx'] ? $v['tx'] : '—' ) . '</span></li>';
		}
		$h .= '</ol>' . ( count( $d['visits'] ) > 8 ? '</details>' : '' );
	}
	return $h . '</section>';
}
