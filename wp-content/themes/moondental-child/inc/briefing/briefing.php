<?php
/**
 * v7.7 · 직원 라운지 · 경영 브리핑 (덴트웹 연동)
 *
 *  서버 PC 의 brief.ps1 이 매일 아침(07:30 뒤, 진료하는 날만) 덴트웹을 읽어
 *  POST /wp-json/md-brief/v1/daily (헤더 X-MD-Survey-Key — 만족도 연동 키) 로 보낸다.
 *  여기서는 저장하고, 받는 사람에게 브리핑 메일을 보낸다(숫자 · 인원수만, 환자 이름은 메일에 넣지 않음).
 *
 *  화면 (app=brief)
 *   - 브리핑 · 받는 사람  : 원장 계정만 (매출)
 *   - 진료 중단 · 리콜 · 노쇼 : 라운지 관리자도 (데스크가 연락하고 「연락함」 표시)
 *
 *  저장: wp_md_brief (진료일별 숫자 JSON), 옵션 md_brief_lists (가장 최근 명단 — 매일 바뀜),
 *        md_brief_to (받는 사람), md_brief_marks (연락함 표시, 90일 보관)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_BRIEF_SCHEMA', 1 );
define( 'MD_BRIEF_DEFAULT_TO', 'moondentaldigital@gmail.com' );

function md_brief_table() { global $wpdb; return $wpdb->prefix . 'md_brief'; }

function md_brief_maybe_install() {
	if ( (int) get_option( 'md_brief_schema', 0 ) >= MD_BRIEF_SCHEMA ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( 'CREATE TABLE ' . md_brief_table() . " (
		d DATE NOT NULL,
		data LONGTEXT NOT NULL,
		made_at DATETIME NOT NULL,
		mailed_at DATETIME NULL,
		PRIMARY KEY  (d)
	) " . $wpdb->get_charset_collate() . ';' );
	if ( false === get_option( 'md_brief_to' ) ) { add_option( 'md_brief_to', MD_BRIEF_DEFAULT_TO, '', 'no' ); }
	update_option( 'md_brief_schema', MD_BRIEF_SCHEMA );
}
add_action( 'init', 'md_brief_maybe_install', 22 );

function md_brief_can_money() { return function_exists( 'md_sup_is_owner' ) && md_sup_is_owner(); }
function md_brief_can_lists() { return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage(); }

/* ============================================================
 * 데이터
 * ============================================================ */
function md_brief_get( $d = '' ) {
	global $wpdb;
	$t   = md_brief_table();
	$row = $d ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE d = %s", $d ) ) : $wpdb->get_row( "SELECT * FROM $t ORDER BY d DESC LIMIT 1" );
	if ( ! $row ) { return null; }
	$data = json_decode( $row->data, true );
	if ( ! is_array( $data ) ) { return null; }
	$data['_mailed_at'] = $row->mailed_at;
	return $data;
}

function md_brief_dates( $n = 60 ) {
	global $wpdb;
	return (array) $wpdb->get_col( $wpdb->prepare( 'SELECT d FROM ' . md_brief_table() . ' ORDER BY d DESC LIMIT %d', $n ) );
}

function md_brief_recipients() {
	$raw = get_option( 'md_brief_to', MD_BRIEF_DEFAULT_TO );
	$out = array();
	foreach ( preg_split( '/[\s,;]+/', (string) $raw ) as $m ) { if ( is_email( $m ) ) { $out[] = strtolower( sanitize_email( $m ) ); } }
	return array_values( array_unique( $out ) );
}

/** 3,072만 · 1.2억 */
function md_brief_won( $v ) {
	$v = (float) $v;
	if ( abs( $v ) >= 100000000 ) { return number_format( $v / 100000000, 2 ) . '억'; }
	return number_format( round( $v / 10000 ) ) . '만';
}

/** 지난번 대비 — ▲12% / ▼3% (기준이 0 이면 빈칸) */
function md_brief_delta( $now, $base ) {
	$now = (float) $now; $base = (float) $base;
	if ( $base <= 0 ) { return ''; }
	$p = ( $now - $base ) / $base * 100;
	$c = $p >= 0 ? '#1a7f37' : '#c62828';
	return '<span style="color:' . $c . ';font-weight:600">' . ( $p >= 0 ? '▲' : '▼' ) . number_format( abs( $p ), 0 ) . '%</span>';
}

function md_brief_dow( $d ) { $w = array( '일', '월', '화', '수', '목', '금', '토' ); return $w[ (int) date( 'w', strtotime( $d ) ) ]; }

/* ============================================================
 * 받기 (서버 PC → 홈페이지)
 * ============================================================ */
function md_brief_permission( $request ) {
	$key  = (string) $request->get_header( 'x-md-survey-key' );
	$want = (string) get_option( 'md_survey_api_key' );
	return '' !== $want && '' !== $key && hash_equals( $want, $key );
}

function md_brief_receive( $request ) {
	global $wpdb;
	$b = $request->get_json_params();
	$d = isset( $b['date'] ) ? sanitize_text_field( $b['date'] ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) { return new WP_Error( 'md_brief', '날짜가 없습니다.', array( 'status' => 400 ) ); }

	/* 명단은 따로 (가장 최근 것만) — 이름 · 차트번호가 있으므로 숫자 기록에는 남기지 않는다 */
	$lists = isset( $b['lists'] ) && is_array( $b['lists'] ) ? $b['lists'] : array();
	unset( $b['lists'] );
	$clean = array();
	foreach ( array( 'dropout', 'recall', 'noshow', 'confirm' ) as $k ) { /* v9.9 · confirm = 오늘 확인 전화 */
		$clean[ $k ] = array();
		foreach ( (array) ( $lists[ $k ] ?? array() ) as $r ) {
			if ( ! is_array( $r ) ) { continue; }
			$row = array();
			foreach ( array( 'chart', 'name', 'doc', 'why', 'kinds', 'date', 'last' ) as $f ) { $row[ $f ] = mb_substr( sanitize_text_field( (string) ( $r[ $f ] ?? '' ) ), 0, 200 ); }
			if ( '' !== $row['chart'] ) { $clean[ $k ][] = $row; }
		}
	}
	update_option( 'md_brief_lists', array( 'date' => $d, 'at' => current_time( 'mysql' ) ) + $clean, false );

	/* v9.9 · 리콜 성과 (「연락함」 환자의 예약 · 내원) — 키별로 덮어씀 */
	if ( isset( $b['mark_results'] ) && is_array( $b['mark_results'] ) ) {
		$res = (array) get_option( 'md_brief_mark_res', array() );
		foreach ( $b['mark_results'] as $mr ) {
			if ( ! is_array( $mr ) || empty( $mr['k'] ) ) { continue; }
			$res[ mb_substr( sanitize_text_field( (string) $mr['k'] ), 0, 120 ) ] = array( 'came' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $mr['came'] ?? '' ) ) ? $mr['came'] : '', 'booked' => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $mr['booked'] ?? '' ) ) ? $mr['booked'] : '', 'at' => $d );
		}
		$marks = (array) get_option( 'md_brief_marks', array() );
		foreach ( array_keys( $res ) as $rk ) { if ( ! isset( $marks[ $rk ] ) && ( $res[ $rk ]['at'] ?? '' ) < date( 'Y-m-d', strtotime( $d . ' -120 days' ) ) ) { unset( $res[ $rk ] ); } }
		update_option( 'md_brief_mark_res', $res, false );
	}
	unset( $b['mark_results'] );
	/* v9.9 · 진료시간 · 체어 설정 표 구조 (빈 예약 시간 준비 · 환자 정보 아님) */
	if ( isset( $b['schema'] ) ) { update_option( 'md_brief_schema', array( 'at' => current_time( 'mysql' ), 'data' => $b['schema'] ), false ); }
	unset( $b['schema'] );

	$t   = md_brief_table();
	$old = $wpdb->get_row( $wpdb->prepare( "SELECT mailed_at FROM $t WHERE d = %s", $d ) );
	$b['counts'] = array( 'dropout' => count( $clean['dropout'] ), 'recall' => count( $clean['recall'] ), 'noshow' => count( $clean['noshow'] ), 'confirm' => count( $clean['confirm'] ) );
	$wpdb->replace( $t, array( 'd' => $d, 'data' => wp_json_encode( $b, JSON_UNESCAPED_UNICODE ), 'made_at' => current_time( 'mysql' ), 'mailed_at' => $old ? $old->mailed_at : null ) );

	/* 메일은 서버 PC 가 「send」를 붙였을 때만 — brief.ps1 이 하루 한 번(휴진일 포함) 붙인다 (원장 지시) */
	$mailed = 0;
	if ( ! empty( $b['send'] ) ) {
		$mailed = md_brief_send( $d );
		if ( $mailed ) { $wpdb->update( $t, array( 'mailed_at' => current_time( 'mysql' ) ), array( 'd' => $d ) ); }
		md_brief_period_mails( $b ); /* v9.9 · 월요일 주간 · 1일 월간 요약 */
	}
	return rest_ensure_response( array( 'saved' => $d, 'mailed' => $mailed ) );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-brief/v1', '/daily', array(
		'methods'             => 'POST',
		'callback'            => 'md_brief_receive',
		'permission_callback' => 'md_brief_permission',
	) );
	/* v9.9 · 서버 PC 가 「연락함」 표시를 받아 가서 그 뒤 예약 · 내원을 찾아 준다 (차트번호 · 날짜만) */
	register_rest_route( 'md-brief/v1', '/marks', array(
		'methods'             => 'GET',
		'permission_callback' => 'md_brief_permission',
		'callback'            => function () {
			$out = array(); $cut = date( 'Y-m-d', current_time( 'timestamp' ) - 90 * DAY_IN_SECONDS );
			foreach ( (array) get_option( 'md_brief_marks', array() ) as $k => $v ) {
				$parts = explode( '|', (string) $k );
				if ( count( $parts ) < 2 || ( $v['d'] ?? '' ) < $cut ) { continue; }
				$out[] = array( 'k' => (string) $k, 'tab' => $parts[0], 'chart' => $parts[1], 'd' => (string) $v['d'] );
			}
			return rest_ensure_response( array( 'marks' => $out ) );
		},
	) );
} );

/* ============================================================
 * 메일
 * ============================================================ */
function md_brief_send( $d, $to = null ) {
	$data = md_brief_get( $d );
	if ( ! $data ) { return 0; }
	$to = null === $to ? md_brief_recipients() : (array) $to;
	if ( ! $to ) { return 0; }
	$subj = sprintf( '[문치과병원 경영 브리핑] %s(%s) 진료비 %s · 내원 %d명 · 오늘 예약 %d건',
		date( 'n/j', strtotime( $d ) ), md_brief_dow( $d ), md_brief_won( $data['day']['total'] ?? 0 ), (int) ( $data['day']['visits'] ?? 0 ), (int) ( $data['today']['resv'] ?? 0 ) );
	$html = '<div style="font-family:-apple-system,\'Malgun Gothic\',sans-serif;max-width:640px;color:#222;line-height:1.5">'
		. md_brief_html( $data, true )
		. '<p style="margin-top:24px"><a href="' . esc_url( md_brief_lounge_url() ) . '" style="display:inline-block;background:#2e7d5b;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">직원 라운지에서 자세히 보기</a></p>'
		. '<p style="color:#888;font-size:12px">덴트웹 자료를 서버 PC 가 매일 아침 읽어 만든 브리핑입니다. 받는 사람은 직원 라운지 › 경영 브리핑 › 받는 사람에서 바꿀 수 있습니다.</p></div>';
	$n = 0;
	foreach ( $to as $m ) { if ( wp_mail( $m, $subj, $html, array( 'Content-Type: text/html; charset=UTF-8' ) ) ) { $n++; } }
	return $n;
}

function md_brief_lounge_url( $args = array() ) {
	$u = home_url( '/직원/' );
	return add_query_arg( array( 'app' => 'brief' ) + $args, $u );
}

/** 브리핑 본문 — 메일과 화면이 같이 쓴다 ($mail 이면 명단은 인원수만) */
function md_brief_html( $x, $mail = false ) {
	$day = $x['day'] ?? array(); $lw = $x['last_week'] ?? array();
	$m = $x['mtd'] ?? array(); $mp = $x['mtd_prev'] ?? array(); $ly = $x['mtd_ly'] ?? array(); $pm = $x['prev_month'] ?? array();
	$td = 'style="padding:6px 8px;border-bottom:1px solid #eee"'; $tdr = 'style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right;white-space:nowrap"';
	$tdg = 'style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right;white-space:nowrap;color:#888"';
	$th = 'style="padding:6px 8px;border-bottom:2px solid #ddd;text-align:left;font-weight:600;color:#555"'; $thr = 'style="padding:6px 8px;border-bottom:2px solid #ddd;text-align:right;font-weight:600;color:#555"';
	$h  = '<h2 style="margin:0 0 4px;font-size:20px">' . esc_html( date( 'Y년 n월 j일', strtotime( $x['date'] ) ) . ' (' . md_brief_dow( $x['date'] ) . ')' ) . ' 진료</h2>';
	$h .= '<p style="margin:0 0 16px;color:#666">직전 같은 요일 진료일(' . esc_html( date( 'n/j', strtotime( $lw['from'] ?? $x['date'] ) ) ) . ')과 비교</p>';

	$h .= '<table style="border-collapse:collapse;width:100%;margin-bottom:18px"><tr><th ' . $th . '></th><th ' . $thr . '>이날</th><th ' . $thr . '>' . esc_html( date( 'n/j', strtotime( $lw['from'] ?? $x['date'] ) ) ) . '</th><th ' . $th . '></th></tr>';
	$rows = array(
		array( '내원', $day['visits'] ?? 0, $lw['visits'] ?? 0, '명' ),
		array( '신환', $day['new'] ?? 0, $lw['new'] ?? 0, '명' ),
		array( '총진료비', $day['total'] ?? 0, $lw['total'] ?? 0, 'won' ),
		array( '　공단부담', $day['gong'] ?? 0, $lw['gong'] ?? 0, 'won' ),
		array( '　본인부담', $day['bon'] ?? 0, $lw['bon'] ?? 0, 'won' ),
		array( '　비급여', $day['bi'] ?? 0, $lw['bi'] ?? 0, 'won' ),
		array( '수납 (카드 · 현금 · 통장)', $day['paid'] ?? 0, $lw['paid'] ?? 0, 'won' ),
	);
	foreach ( $rows as $r ) {
		$f = 'won' === $r[3] ? 'md_brief_won' : function ( $v ) use ( $r ) { return number_format( (float) $v ) . $r[3]; };
		$h .= '<tr><td ' . $td . '>' . esc_html( $r[0] ) . '</td><td ' . $tdr . '><b>' . esc_html( $f( $r[1] ) ) . '</b></td><td ' . $tdg . '>' . esc_html( $f( $r[2] ) ) . '</td><td ' . $tdr . '>' . md_brief_delta( $r[1], $r[2] ) . '</td></tr>';
	}
	$h .= '</table>';

	if ( ! empty( $x['doctors'] ) ) {
		$h .= '<h3 style="font-size:16px;margin:18px 0 6px">원장별 (이날)</h3><table style="border-collapse:collapse;width:100%"><tr><th ' . $th . '>원장</th><th ' . $thr . '>환자</th><th ' . $thr . '>총진료비</th><th ' . $thr . '>비급여</th></tr>';
		foreach ( $x['doctors'] as $r ) { $h .= '<tr><td ' . $td . '>' . esc_html( $r['name'] ) . '</td><td ' . $tdr . '>' . (int) $r['visits'] . '명</td><td ' . $tdr . '>' . esc_html( md_brief_won( $r['total'] ) ) . '</td><td ' . $tdr . '>' . esc_html( md_brief_won( $r['bi'] ) ) . '</td></tr>'; }
		$h .= '</table>';
	}

	$h .= '<h3 style="font-size:16px;margin:22px 0 6px">이번 달 누적 (' . esc_html( date( 'n/j', strtotime( $m['from'] ?? $x['date'] ) ) . '~' . date( 'n/j', strtotime( $m['to'] ?? $x['date'] ) ) ) . ' · 진료 ' . (int) ( $m['days'] ?? 0 ) . '일)</h3>';
	$h .= '<table style="border-collapse:collapse;width:100%"><tr><th ' . $th . '></th><th ' . $thr . '>이번 달</th><th ' . $thr . '>지난달 같은 기간</th><th ' . $thr . '>작년 같은 기간</th></tr>';
	foreach ( array( array( '총진료비', 'total', true ), array( '비급여', 'bi', true ), array( '내원', 'visits', false ), array( '신환', 'new', false ) ) as $r ) {
		$f = $r[2] ? 'md_brief_won' : function ( $v ) { return number_format( (float) $v ) . '명'; };
		$h .= '<tr><td ' . $td . '>' . esc_html( $r[0] ) . '</td><td ' . $tdr . '><b>' . esc_html( $f( $m[ $r[1] ] ?? 0 ) ) . '</b></td><td ' . $tdr . '>' . esc_html( $f( $mp[ $r[1] ] ?? 0 ) ) . ' ' . md_brief_delta( $m[ $r[1] ] ?? 0, $mp[ $r[1] ] ?? 0 ) . '</td><td ' . $tdr . '>' . esc_html( $f( $ly[ $r[1] ] ?? 0 ) ) . ' ' . md_brief_delta( $m[ $r[1] ] ?? 0, $ly[ $r[1] ] ?? 0 ) . '</td></tr>';
	}
	$h .= '</table>';
	if ( ! empty( $pm['total'] ) ) { $h .= '<p style="color:#666;margin:6px 0 0">지난달 전체: 총진료비 ' . esc_html( md_brief_won( $pm['total'] ) ) . ' · 내원 ' . number_format( (int) $pm['visits'] ) . '명 · 신환 ' . number_format( (int) $pm['new'] ) . '명</p>'; }

	$rv = $x['resv_day'] ?? array(); $tdy = $x['today'] ?? array();
	$h .= '<h3 style="font-size:16px;margin:22px 0 6px">예약</h3>';
	$h .= '<p style="margin:0 0 4px">오늘(' . esc_html( date( 'n/j', strtotime( $tdy['date'] ?? 'now' ) ) ) . ') 예약 <b>' . (int) ( $tdy['resv'] ?? 0 ) . '건</b>';
	if ( ! empty( $tdy['by_doctor'] ) ) { $h .= ' — ' . esc_html( implode( ' · ', array_map( function ( $r ) { return ( $r['name'] ?: '미지정' ) . ' ' . (int) $r['n']; }, $tdy['by_doctor'] ) ) ); }
	$h .= '</p>';
	if ( ! empty( $rv['total'] ) ) {
		$h .= '<p style="margin:0">이날 예약 ' . (int) $rv['total'] . '건 → 내원 ' . (int) $rv['came'] . ' · 취소/변경 ' . (int) $rv['cancel'] . ' · <b>노쇼 ' . (int) $rv['noshow'] . '</b> (' . number_format( 100 * $rv['noshow'] / max( 1, $rv['total'] ), 1 ) . '%)</p>';
	}
	$ns = $x['noshow'] ?? array();
	if ( ! empty( $ns['by_doctor'] ) ) {
		$h .= '<p style="margin:6px 0 0;color:#555">최근 노쇼율 (' . esc_html( date( 'n/j', strtotime( $ns['from'] ) ) . '~' . date( 'n/j', strtotime( $ns['to'] ) ) ) . '): '
			. esc_html( implode( ' · ', array_map( function ( $r ) { return $r['key'] . ' ' . $r['rate'] . '%'; }, array_slice( $ns['by_doctor'], 0, 10 ) ) ) ) . '</p>';
	}

	/* v9.9 · 원장별 지표 — 이번 달 (지난달 같은 기간 · 작년 같은 기간 대비) */
	if ( ! empty( $x['doc_cmp'] ) ) {
		$tdr = 'style="padding:6px 6px;border-bottom:1px solid #eee;text-align:right;vertical-align:top"'; /* 좁은 화면에서 줄바꿈 */
		$h .= '<h3 style="font-size:16px;margin:22px 0 6px">원장별 이번 달</h3><table style="border-collapse:collapse;width:100%"><tr><th ' . $th . '>원장</th><th ' . $thr . '>내원</th><th ' . $thr . '>환자당 진료비</th><th ' . $thr . '>비급여 비율</th><th ' . $thr . '>신환</th></tr>';
		foreach ( $x['doc_cmp'] as $r ) {
			$c0 = (array) ( $r['cur'] ?? array() ); $cp = (array) ( $r['prev'] ?? array() ); $cl = (array) ( $r['ly'] ?? array() );
			$avg = function ( $a ) { return ! empty( $a['visits'] ) ? (float) $a['total'] / (float) $a['visits'] : 0; };
			$bir = function ( $a ) { return ! empty( $a['total'] ) ? 100 * (float) $a['bi'] / (float) $a['total'] : 0; };
			$sub = function ( $now, $p, $l, $fmt ) { $o = array(); if ( $p ) { $o[] = '지난달 ' . $fmt( $p ); } if ( $l ) { $o[] = '작년 ' . $fmt( $l ); } return $o ? '<br><small style="color:#888">' . esc_html( implode( ' · ', $o ) ) . '</small>' : ''; };
			$won = function ( $v ) { return number_format( round( $v / 1000 ) * 1000 ) . '원'; };
			$pct = function ( $v ) { return number_format( $v, 0 ) . '%'; };
			$cnt = function ( $v ) { return number_format( (float) $v ) . '명'; };
			if ( empty( $c0['visits'] ) && empty( $c0['new'] ) ) { continue; }
			$h .= '<tr><td ' . $td . '>' . esc_html( (string) $r['name'] ) . '</td>'
				. '<td ' . $tdr . '><b>' . esc_html( $cnt( $c0['visits'] ?? 0 ) ) . '</b> ' . md_brief_delta( $c0['visits'] ?? 0, $cp['visits'] ?? 0 ) . $sub( 0, $cp['visits'] ?? 0, $cl['visits'] ?? 0, $cnt ) . '</td>'
				. '<td ' . $tdr . '><b>' . esc_html( $won( $avg( $c0 ) ) ) . '</b> ' . md_brief_delta( $avg( $c0 ), $avg( $cp ) ) . $sub( 0, $avg( $cp ), $avg( $cl ), $won ) . '</td>'
				. '<td ' . $tdr . '><b>' . esc_html( $pct( $bir( $c0 ) ) ) . '</b>' . $sub( 0, $bir( $cp ), $bir( $cl ), $pct ) . '</td>'
				. '<td ' . $tdr . '><b>' . esc_html( $cnt( $c0['new'] ?? 0 ) ) . '</b>' . $sub( 0, $cp['new'] ?? 0, $cl['new'] ?? 0, $cnt ) . '</td></tr>';
		}
		$h .= '</table><p style="margin:4px 0 0;color:#888;font-size:12px">▲▼ 는 지난달 같은 기간 대비. 신환은 덴트웹 담당의사 기준.</p>';
	}

	$c = $x['counts'] ?? array();
	$h .= '<h3 style="font-size:16px;margin:22px 0 6px">연락할 환자</h3><p style="margin:0">' . ( isset( $c['confirm'] ) ? '오늘 확인 전화 <b>' . (int) $c['confirm'] . '명</b> · ' : '' ) . '진료 중단 <b>' . (int) ( $c['dropout'] ?? 0 ) . '명</b> · 리콜 <b>' . (int) ( $c['recall'] ?? 0 ) . '명</b> · 노쇼 <b>' . (int) ( $c['noshow'] ?? 0 ) . '명</b>'
		. ( $mail ? ' — 이름은 메일에 넣지 않습니다. 라운지에서 확인하세요.' : '' ) . '</p>';
	return $h;
}

/* ============================================================
 * 폼 처리 (받는 사람 · 시험 메일 · 연락함)
 * ============================================================ */
function md_brief_handle() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['md_brief'] ) ) { return; }
	$act = sanitize_key( wp_unslash( $_POST['md_brief'] ) );
	if ( ! isset( $_POST['md_brief_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_brief_nonce'] ), 'md_brief_' . $act ) ) { wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' ); }
	$tab  = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
	$back = md_brief_lounge_url( $tab ? array( 'bt' => $tab ) : array() );
	$msg  = '';

	if ( in_array( $act, array( 'add_to', 'del_to', 'test_mail' ), true ) ) {
		if ( ! md_brief_can_money() ) { wp_die( '원장 계정만 할 수 있습니다.' ); }
		$list = md_brief_recipients();
		if ( 'add_to' === $act ) {
			$m = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
			if ( ! is_email( $m ) ) { $msg = 'e:이메일 주소를 확인해 주세요.'; }
			else { $list[] = strtolower( $m ); $list = array_values( array_unique( $list ) ); update_option( 'md_brief_to', implode( ', ', $list ), false ); $msg = 'o:' . $m . ' 을(를) 받는 사람에 넣었습니다.'; }
		} elseif ( 'del_to' === $act ) {
			$m = strtolower( sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) );
			update_option( 'md_brief_to', implode( ', ', array_diff( $list, array( $m ) ) ), false );
			$msg = 'o:' . $m . ' 을(를) 뺐습니다.';
		} else {
			$d = md_brief_dates( 1 );
			$m = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
			$n = $d ? md_brief_send( $d[0], is_email( $m ) ? array( $m ) : md_brief_recipients() ) : 0;
			$msg = $n ? 'o:가장 최근 브리핑(' . $d[0] . ')을 ' . $n . '명에게 보냈습니다.' : 'e:보내지 못했습니다' . ( $d ? ' (받는 사람을 확인해 주세요).' : ' — 아직 받은 브리핑이 없습니다.' );
		}
	} elseif ( 'mark' === $act ) {
		if ( ! md_brief_can_lists() ) { wp_die( '라운지 관리자만 할 수 있습니다.' ); }
		$k = sanitize_text_field( wp_unslash( $_POST['k'] ?? '' ) );
		$marks = (array) get_option( 'md_brief_marks', array() );
		$cut   = date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -90 days' ) );
		foreach ( $marks as $kk => $v ) { if ( ( $v['d'] ?? '' ) < $cut ) { unset( $marks[ $kk ] ); } }
		if ( isset( $marks[ $k ] ) ) { unset( $marks[ $k ] ); }
		else { $marks[ $k ] = array( 'd' => current_time( 'Y-m-d' ), 'by' => function_exists( 'md_acc_me_name' ) ? md_acc_me_name() : wp_get_current_user()->display_name ); }
		update_option( 'md_brief_marks', $marks, false );
		$back .= '#b' . md5( $k );
	}
	if ( $msg ) { $back = add_query_arg( 'bm', rawurlencode( $msg ), $back ); }
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_brief_handle', 1 );

/* ============================================================
 * 화면 (app=brief)
 * ============================================================ */
function md_brief_render() {
	if ( ! md_brief_can_lists() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	$money = md_brief_can_money();
	$tabs  = array();
	if ( $money ) { $tabs['day'] = '📊 브리핑'; }
	$tabs += array( 'confirm' => '📞 확인 전화', 'dropout' => '🦷 진료 중단', 'recall' => '🔔 리콜', 'noshow' => '🚫 노쇼' );
	if ( $money ) { $tabs['to'] = '✉️ 받는 사람'; }
	$tab = isset( $_GET['bt'] ) ? sanitize_key( wp_unslash( $_GET['bt'] ) ) : '';
	if ( ! isset( $tabs[ $tab ] ) ) { $tab = array_key_first( $tabs ); }
	?>
	<style>
		.mdb-tabs{display:flex;gap:6px;flex-wrap:wrap;margin:0 0 12px}
		.mdb-tabs a{padding:8px 14px;border-radius:999px;background:#f1f3f2;color:#333;text-decoration:none;font-weight:600;font-size:14px}
		.mdb-tabs a.is-on{background:#2e7d5b;color:#fff}
		.mdb-wrap{overflow-x:auto}
		.mdb-table{border-collapse:collapse;width:100%;font-size:14px}
		.mdb-table th,.mdb-table td{padding:7px 8px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
		.mdb-table th{color:#666;font-weight:600;white-space:nowrap}
		.mdb-table tr.is-done td{color:#999;background:#fafafa}
		.mdb-mark{border:1px solid #ccc;background:#fff;border-radius:6px;padding:4px 8px;cursor:pointer;white-space:nowrap;font-size:13px}
		.mdb-mark.is-on{background:#e8f5ee;border-color:#2e7d5b;color:#2e7d5b}
		.mdb-filter{display:flex;gap:6px;flex-wrap:wrap;margin:0 0 10px}
		.mdb-filter a{font-size:13px;padding:4px 10px;border:1px solid #ddd;border-radius:999px;text-decoration:none;color:#444}
		.mdb-filter a.is-on{border-color:#2e7d5b;color:#2e7d5b;font-weight:600}
		.mdb-to{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:6px 0}
		.mdb-to input[type=email]{flex:1;min-width:220px;padding:8px;border:1px solid #ccc;border-radius:6px}
		.mdb-chart__h{font-size:14px;margin:14px 0 6px;color:#555}
		.mdb-charts{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
		.mdb-chart{margin:0;padding:10px 12px;border:1px solid #eee;border-radius:10px;background:#fff}
		.mdb-chart figcaption{font-size:13px;font-weight:600;color:#333;margin:0 0 4px}
		.mdb-chart__svg{display:block;width:100%;height:auto}
		.mdb-bar{cursor:pointer}.mdb-bar:hover path,.mdb-bar.is-on path{opacity:.75}
		.mdb-chart__tip{min-height:18px;font-size:12px;color:#555;margin-top:4px}
		.mdb-perf{margin:0 0 10px;padding:8px 12px;border-radius:8px;background:#f1f7f4;font-size:14px;color:#2b3a33}
		.mdb-perf small{color:#6b7c74}
	</style>
	<?php
	if ( isset( $_GET['bm'] ) ) {
		$m = (string) wp_unslash( $_GET['bm'] );
		echo '<div class="mds-notice' . ( 0 === strpos( $m, 'e:' ) ? ' mds-notice--warn' : '' ) . '">' . esc_html( substr( $m, 2 ) ) . '</div>';
	}
	echo '<nav class="mdb-tabs">';
	foreach ( $tabs as $k => $label ) { echo '<a class="' . ( $k === $tab ? 'is-on' : '' ) . '" href="' . esc_url( md_brief_lounge_url( array( 'bt' => $k ) ) ) . '">' . esc_html( $label ) . '</a>'; }
	echo '</nav>';

	if ( 'day' === $tab ) { md_brief_render_day(); }
	elseif ( 'to' === $tab ) { md_brief_render_to(); }
	else { md_brief_render_list( $tab ); }
}

function md_brief_render_day() {
	$dates = md_brief_dates();
	if ( ! $dates ) { echo '<div class="mds-card"><div class="mds-empty">아직 받은 브리핑이 없습니다. 서버 PC 가 매일 아침 7시 30분 뒤에 보냅니다.</div></div>'; return; }
	$d = isset( $_GET['bd'] ) && in_array( $_GET['bd'], $dates, true ) ? sanitize_text_field( wp_unslash( $_GET['bd'] ) ) : $dates[0];
	$x = md_brief_get( $d );
	?>
	<div class="mds-card">
		<form method="get" style="margin:0 0 12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
			<a class="mds-btn mds-btn--ghost" style="margin-left:auto;order:9" href="<?php echo esc_url( md_brief_dl_url( 'day' ) ); ?>" data-mdb-xl title="모든 진료일 숫자 · 원장별 · 노쇼율 · 이번 달 누적">📥 엑셀로 받기</a>
			<input type="hidden" name="app" value="brief"><input type="hidden" name="bt" value="day">
			<label>진료일 <select name="bd" onchange="this.form.submit()"><?php foreach ( $dates as $o ) : ?><option value="<?php echo esc_attr( $o ); ?>" <?php selected( $o, $d ); ?>><?php echo esc_html( $o . ' (' . md_brief_dow( $o ) . ')' ); ?></option><?php endforeach; ?></select></label>
			<small style="color:#888">만든 시각 <?php echo esc_html( $x['made_at'] ?? '' ); ?><?php echo ! empty( $x['_mailed_at'] ) ? ' · 메일 보냄 ' . esc_html( substr( $x['_mailed_at'], 11, 5 ) ) : ''; ?></small>
		</form>
		<div class="mdb-wrap"><?php echo md_brief_html( $x ); // phpcs:ignore -- 안에서 모두 이스케이프 ?></div>
		<?php echo md_brief_trend_html( $x['trend'] ?? null ); // phpcs:ignore -- 안에서 이스케이프 ?>
		<?php echo md_brief_mark_stats_html( '' ); // phpcs:ignore ?>
		<?php if ( ! empty( $x['noshow']['by_dow'] ) ) : ?>
			<h3 style="font-size:16px;margin:22px 0 6px">노쇼율 — 요일 · 시간대</h3>
			<div class="mdb-wrap"><table class="mdb-table"><tr><th>요일</th><?php foreach ( $x['noshow']['by_dow'] as $r ) : ?><td><?php echo esc_html( $r['key'] ); ?></td><?php endforeach; ?></tr>
				<tr><th>노쇼율</th><?php foreach ( $x['noshow']['by_dow'] as $r ) : ?><td><?php echo esc_html( $r['rate'] . '%' ); ?><br><small style="color:#888"><?php echo (int) $r['noshow'] . '/' . (int) $r['total']; ?></small></td><?php endforeach; ?></tr></table></div>
			<div class="mdb-wrap" style="margin-top:8px"><table class="mdb-table"><tr><th>시간</th><?php foreach ( $x['noshow']['by_hour'] as $r ) : ?><td><?php echo esc_html( (int) $r['key'] . '시' ); ?></td><?php endforeach; ?></tr>
				<tr><th>노쇼율</th><?php foreach ( $x['noshow']['by_hour'] as $r ) : ?><td><?php echo esc_html( $r['rate'] . '%' ); ?></td><?php endforeach; ?></tr></table></div>
		<?php endif; ?>
	</div>
	<?php
}

function md_brief_render_list( $tab ) {
	$L = get_option( 'md_brief_lists' );
	$rows = is_array( $L ) ? (array) ( $L[ $tab ] ?? array() ) : array();
	$marks = (array) get_option( 'md_brief_marks', array() );
	$intro = array(
		'dropout' => '치료가 중간에 멈춘 환자 — 신경치료를 시작하고 한 달 넘게 안 오심 · 신경치료(근관충전) 뒤 두 달 넘게 보철을 안 하심 · 임플란트 수술 1년이 지나도 보철을 안 하심. 앞으로 잡힌 예약이 있는 환자는 뺐습니다.',
		'recall'  => '스케일링한 지 1년~1년 반 된 환자 · 임플란트 환자 중 6개월~1년 동안 안 오신 환자. 앞으로 잡힌 예약이 있는 환자는 뺐습니다.',
		'noshow'  => '지난 진료일에 예약하고 오지 않은 환자 · 최근 한 달 동안 두 번 넘게 오지 않은 환자.',
		'confirm' => '오늘 예약한 환자 중 최근 한 달 안에 예약하고 오지 않았거나(노쇼) 예약을 취소 · 변경한 적이 있는 분 — 아침에 한 번 더 확인 전화를 드려 주세요.',
	);
	$filters = array(
		'dropout' => array( '' => '전체', 'endo' => '신경치료 중단', 'crown' => '보철 안 함', 'imp' => '임플란트' ),
		'recall'  => array( '' => '전체', 'sc' => '스케일링', 'imp' => '임플란트 정기검진' ),
		'noshow'  => array( '' => '전체', 'yday' => '지난 진료일', 'repeat' => '자주 노쇼' ),
		'confirm' => array( '' => '전체' ),
	);
	$f = isset( $_GET['bf'] ) ? sanitize_key( wp_unslash( $_GET['bf'] ) ) : '';
	if ( ! isset( $filters[ $tab ][ $f ] ) ) { $f = ''; }
	if ( '' !== $f ) { $rows = array_values( array_filter( $rows, function ( $r ) use ( $f ) { return in_array( $f, explode( ',', $r['kinds'] ), true ); } ) ); }
	?>
	<div class="mds-card">
		<p class="mds-hint" style="margin-top:0"><?php echo esc_html( $intro[ $tab ] ); ?>
			<?php if ( is_array( $L ) && ! empty( $L['at'] ) ) : ?><br><small>덴트웹에서 <?php echo esc_html( $L['at'] ); ?> 에 만든 명단 · 덴트웹에서 차트번호로 찾아 연락하고 「연락함」을 눌러 주세요(90일 동안 표시).</small><?php endif; ?></p>
		<div style="display:flex;justify-content:flex-end;margin:0 0 8px"><a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_brief_dl_url( 'lists' ) ); ?>" data-mdb-xl title="진료 중단 · 리콜 · 노쇼 명단과 연락함 표시를 한 파일로">📥 명단 엑셀로 받기</a></div>
		<?php echo md_brief_mark_stats_html( $tab ); // phpcs:ignore -- 안에서 이스케이프 ?>
		<div class="mdb-filter"><?php foreach ( $filters[ $tab ] as $k => $label ) : ?><a class="<?php echo $k === $f ? 'is-on' : ''; ?>" href="<?php echo esc_url( md_brief_lounge_url( array( 'bt' => $tab, 'bf' => $k ) ) ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?></div>
		<?php if ( ! $rows ) : ?><div class="mds-empty">해당하는 환자가 없습니다.</div><?php else : ?>
		<p style="margin:0 0 6px;color:#555"><?php echo count( $rows ); ?>명</p>
		<div class="mdb-wrap"><table class="mdb-table">
			<tr><th>차트</th><th>이름</th><th>담당</th><th>사유</th><th>기준일</th><th>마지막 내원</th><th></th></tr>
			<?php foreach ( $rows as $r ) :
				$k = $tab . '|' . $r['chart'] . '|' . $r['kinds']; $mk = $marks[ $k ] ?? null; ?>
				<tr id="b<?php echo esc_attr( md5( $k ) ); ?>" class="<?php echo $mk ? 'is-done' : ''; ?>">
					<td><?php echo esc_html( $r['chart'] ); ?></td>
					<td><?php echo esc_html( $r['name'] ); ?></td>
					<td><?php echo esc_html( $r['doc'] ); ?></td>
					<td><?php echo esc_html( $r['why'] ); ?></td>
					<td style="white-space:nowrap"><?php echo esc_html( $r['date'] ); ?></td>
					<td style="white-space:nowrap"><?php echo esc_html( $r['last'] ); ?></td>
					<td><form method="post" style="margin:0"><input type="hidden" name="md_brief" value="mark"><input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>"><input type="hidden" name="k" value="<?php echo esc_attr( $k ); ?>"><?php wp_nonce_field( 'md_brief_mark', 'md_brief_nonce' ); ?>
						<button type="submit" class="mdb-mark<?php echo $mk ? ' is-on' : ''; ?>" title="<?php echo $mk ? esc_attr( $mk['by'] . ' · 다시 누르면 지움' ) : '연락했으면 누르기'; ?>"><?php echo $mk ? '✓ 연락함 ' . esc_html( date( 'n/j', strtotime( $mk['d'] ) ) ) : '연락함'; ?></button></form></td>
				</tr>
			<?php endforeach; ?>
		</table></div>
		<?php endif; ?>
	</div>
	<?php
}

function md_brief_render_to() {
	$to = md_brief_recipients();
	?>
	<div class="mds-card">
		<h2 class="mdst-title" style="margin-top:0">브리핑 메일 받는 사람</h2>
		<p class="mds-hint">매일 아침 7시 30분쯤(휴진일 포함), 가장 최근 진료일 브리핑을 아래 주소로 보냅니다. 메일에는 숫자와 인원수만 들어갑니다(환자 이름 없음).</p>
		<?php if ( ! $to ) : ?><div class="mds-empty">받는 사람이 없습니다 — 메일을 보내지 않습니다.</div><?php endif; ?>
		<?php foreach ( $to as $m ) : ?>
			<form method="post" class="mdb-to" onsubmit="return confirm('<?php echo esc_js( $m ); ?> 을(를) 뺄까요?');">
				<input type="hidden" name="md_brief" value="del_to"><input type="hidden" name="tab" value="to"><input type="hidden" name="email" value="<?php echo esc_attr( $m ); ?>"><?php wp_nonce_field( 'md_brief_del_to', 'md_brief_nonce' ); ?>
				<span style="flex:1">✉️ <?php echo esc_html( $m ); ?></span><button type="submit" class="mds-btn">빼기</button>
			</form>
		<?php endforeach; ?>
		<form method="post" class="mdb-to" style="margin-top:14px">
			<input type="hidden" name="md_brief" value="add_to"><input type="hidden" name="tab" value="to"><?php wp_nonce_field( 'md_brief_add_to', 'md_brief_nonce' ); ?>
			<input type="email" name="email" required placeholder="추가할 이메일 주소"><button type="submit" class="mds-btn mds-btn--fill">추가</button>
		</form>
		<form method="post" class="mdb-to" style="margin-top:18px;border-top:1px solid #eee;padding-top:14px">
			<input type="hidden" name="md_brief" value="test_mail"><input type="hidden" name="tab" value="to"><?php wp_nonce_field( 'md_brief_test_mail', 'md_brief_nonce' ); ?>
			<span style="flex:1;color:#555">가장 최근 브리핑을 지금 다시 보내 보기 (주소를 비우면 받는 사람 모두에게)</span>
			<input type="email" name="email" placeholder="이 주소로만 (선택)" style="flex:0 1 240px"><button type="submit" class="mds-btn">시험 메일 보내기</button>
		</form>
		<?php echo function_exists( 'md_bridge_status_html' ) ? md_bridge_status_html() : ''; // phpcs:ignore -- 안에서 이스케이프 ?>
	</div>
	<?php
}

/* ============================================================
 * v9.1 · 엑셀로 받기 (원장 지시 2026-10-07 「export 할 수 있는 것은 export」)
 *   day   — 진료일별 숫자 · 원장별 · 이번 달 누적 · 노쇼율 (원장 계정만, 매출이 들어 있음)
 *   lists — 진료 중단 · 리콜 · 노쇼 명단 + 연락함 (라운지 관리자도)
 *   병원 밖에서는 이메일 인증을 먼저 (md_sec_guard 가 md_ 주소 값을 막음)
 * ============================================================ */
function md_brief_dl_url( $what ) {
	return md_brief_lounge_url( array( 'md_brief_dl' => $what, '_n' => wp_create_nonce( 'md_brief_dl' ) ) );
}

function md_brief_xl_rows() {
	global $wpdb;
	return (array) $wpdb->get_results( 'SELECT d, data, made_at, mailed_at FROM ' . md_brief_table() . ' ORDER BY d ASC' );
}

function md_brief_xlsx_day() {
	$num = function ( $v ) { return is_numeric( $v ) ? 0 + $v : $v; };
	$days = array(); $docs = array(); $mtd = array(); $last = null;
	foreach ( md_brief_xl_rows() as $row ) {
		$x = json_decode( $row->data, true );
		if ( ! is_array( $x ) ) { continue; }
		$last = $x;
		$d = $x['day'] ?? array(); $rv = $x['resv_day'] ?? array(); $c = $x['counts'] ?? array();
		$days[] = array(
			$row->d, md_brief_dow( $row->d ),
			(int) ( $d['visits'] ?? 0 ), (int) ( $d['new'] ?? 0 ),
			$num( $d['total'] ?? 0 ), $num( $d['gong'] ?? 0 ), $num( $d['bon'] ?? 0 ), $num( $d['bi'] ?? 0 ), $num( $d['paid'] ?? 0 ),
			(int) ( $rv['total'] ?? 0 ), (int) ( $rv['came'] ?? 0 ), (int) ( $rv['cancel'] ?? 0 ), (int) ( $rv['noshow'] ?? 0 ),
			! empty( $rv['total'] ) ? round( 100 * $rv['noshow'] / max( 1, $rv['total'] ), 1 ) : '',
			(int) ( $c['dropout'] ?? 0 ), (int) ( $c['recall'] ?? 0 ), (int) ( $c['noshow'] ?? 0 ),
			(string) $row->made_at, (string) $row->mailed_at,
		);
		foreach ( (array) ( $x['doctors'] ?? array() ) as $r ) {
			$docs[] = array( $row->d, md_brief_dow( $row->d ), (string) ( $r['name'] ?? '' ), (int) ( $r['visits'] ?? 0 ), $num( $r['total'] ?? 0 ), $num( $r['bi'] ?? 0 ) );
		}
		$m = $x['mtd'] ?? array(); $mp = $x['mtd_prev'] ?? array(); $ly = $x['mtd_ly'] ?? array();
		$mtd[] = array( $row->d, ( $m['from'] ?? '' ) . '~' . ( $m['to'] ?? '' ), (int) ( $m['days'] ?? 0 ),
			$num( $m['total'] ?? 0 ), $num( $mp['total'] ?? 0 ), $num( $ly['total'] ?? 0 ),
			$num( $m['bi'] ?? 0 ), $num( $mp['bi'] ?? 0 ), $num( $ly['bi'] ?? 0 ),
			(int) ( $m['visits'] ?? 0 ), (int) ( $mp['visits'] ?? 0 ), (int) ( $ly['visits'] ?? 0 ),
			(int) ( $m['new'] ?? 0 ), (int) ( $mp['new'] ?? 0 ), (int) ( $ly['new'] ?? 0 ) );
	}
	$ns = array();
	if ( $last && ! empty( $last['noshow'] ) ) {
		$n = $last['noshow'];
		foreach ( array( 'by_doctor' => '원장', 'by_dow' => '요일', 'by_hour' => '시간' ) as $k => $label ) {
			foreach ( (array) ( $n[ $k ] ?? array() ) as $r ) {
				$ns[] = array( $label, 'by_hour' === $k ? (int) $r['key'] . '시' : (string) $r['key'], isset( $r['rate'] ) ? 0 + $r['rate'] : '', isset( $r['noshow'] ) ? (int) $r['noshow'] : '', isset( $r['total'] ) ? (int) $r['total'] : '' );
			}
		}
	}
	$won = 'won';
	return md_inv_xlsx( array(
		array( 'title' => '진료일별', 'head' => array( '진료일', '요일', '내원', '신환', '총진료비', '공단부담', '본인부담', '비급여', '수납', '예약', '예약 내원', '취소/변경', '노쇼', '노쇼율(%)', '진료 중단 명단', '리콜 명단', '노쇼 명단', '만든 시각', '메일 보낸 시각' ),
			'rows' => $days, 'num' => array( 2 => 1, 3 => 1, 4 => $won, 5 => $won, 6 => $won, 7 => $won, 8 => $won, 9 => 1, 10 => 1, 11 => 1, 12 => 1 ),
			'width' => array( 11, 4, 6, 6, 13, 13, 13, 13, 13, 6, 8, 8, 6, 9, 10, 8, 8, 17, 17 ) ),
		array( 'title' => '원장별', 'head' => array( '진료일', '요일', '원장', '환자', '총진료비', '비급여' ), 'rows' => $docs, 'num' => array( 3 => 1, 4 => $won, 5 => $won ), 'width' => array( 11, 4, 10, 6, 13, 13 ) ),
		array( 'title' => '이번 달 누적', 'head' => array( '기준 진료일', '기간', '진료일수', '총진료비', '총진료비(지난달 같은 기간)', '총진료비(작년 같은 기간)', '비급여', '비급여(지난달)', '비급여(작년)', '내원', '내원(지난달)', '내원(작년)', '신환', '신환(지난달)', '신환(작년)' ),
			'rows' => $mtd, 'num' => array( 3 => $won, 4 => $won, 5 => $won, 6 => $won, 7 => $won, 8 => $won, 9 => 1, 10 => 1, 11 => 1, 12 => 1, 13 => 1, 14 => 1 ),
			'width' => array( 11, 22, 8, 13, 16, 16, 13, 13, 13, 7, 9, 9, 7, 9, 9 ) ),
		array( 'title' => '노쇼율 (최근)', 'head' => array( '구분', '항목', '노쇼율(%)', '노쇼', '예약' ), 'rows' => $ns, 'num' => array( 3 => 1, 4 => 1 ), 'width' => array( 6, 12, 9, 6, 6 ) ),
	) );
}

function md_brief_xlsx_lists() {
	$L = get_option( 'md_brief_lists' );
	$marks = (array) get_option( 'md_brief_marks', array() );
	$names = array( 'confirm' => '오늘 확인 전화', 'dropout' => '진료 중단', 'recall' => '리콜', 'noshow' => '노쇼' );
	$kinds = array( 'endo' => '신경치료 중단', 'crown' => '보철 안 함', 'imp' => '임플란트', 'sc' => '스케일링', 'yday' => '지난 진료일', 'repeat' => '자주 노쇼' );
	$sheets = array();
	foreach ( $names as $tab => $title ) {
		$rows = array();
		foreach ( is_array( $L ) ? (array) ( $L[ $tab ] ?? array() ) : array() as $r ) {
			$k  = $tab . '|' . $r['chart'] . '|' . $r['kinds'];
			$mk = $marks[ $k ] ?? null;
			$kk = array();
			foreach ( explode( ',', (string) $r['kinds'] ) as $x ) { if ( '' !== $x ) { $kk[] = $kinds[ $x ] ?? $x; } }
			$rows[] = array( $r['chart'], $r['name'], $r['doc'], implode( ' · ', $kk ), $r['why'], $r['date'], $r['last'], $mk ? (string) $mk['d'] : '', $mk ? (string) $mk['by'] : '' );
		}
		$sheets[] = array( 'title' => $title, 'head' => array( '차트번호', '이름', '담당', '분류', '사유', '기준일', '마지막 내원', '연락한 날', '연락한 사람' ), 'rows' => $rows, 'width' => array( 9, 9, 8, 14, 40, 11, 11, 10, 9 ) );
	}
	return md_inv_xlsx( $sheets );
}

function md_brief_handle_download() {
	if ( empty( $_GET['md_brief_dl'] ) ) { return; }
	$what = sanitize_key( wp_unslash( $_GET['md_brief_dl'] ) );
	if ( ! isset( $_GET['_n'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_n'] ), 'md_brief_dl' ) ) { wp_die( '링크가 만료되었습니다. 화면을 새로고침한 뒤 다시 눌러 주세요.' ); }
	if ( 'day' === $what ? ! md_brief_can_money() : ! md_brief_can_lists() ) { wp_die( 'day' === $what ? '원장 계정만 받을 수 있습니다.' : '라운지 관리자만 받을 수 있습니다.', '권한 없음', array( 'response' => 403 ) ); }
	if ( ! function_exists( 'md_inv_xlsx' ) ) { wp_die( '엑셀 모듈(재료실)이 꺼져 있습니다.' ); }
	if ( 'day' === $what ) {
		$bin = md_brief_xlsx_day();  $name = '경영브리핑_' . current_time( 'Ymd' ) . '.xlsx';
	} else {
		$bin = md_brief_xlsx_lists(); $name = '연락할환자명단_' . current_time( 'Ymd' ) . '.xlsx';
	}
	while ( ob_get_level() ) { ob_end_clean(); }
	nocache_headers();
	header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	header( 'Content-Disposition: attachment; filename="moondental-brief.xlsx"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
	header( 'Content-Length: ' . strlen( $bin ) );
	echo $bin; // phpcs:ignore
	exit;
}
add_action( 'template_redirect', 'md_brief_handle_download', 2 );

/* ============================================================
 * v9.9 · 리콜 성과 — 「연락함」 누른 환자가 그 뒤 예약했거나 30일 안에 왔는지 (서버 PC 가 다음 브리핑 때 알려 줌)
 * ============================================================ */
function md_brief_mark_stats( $tab = '' ) {
	$marks = (array) get_option( 'md_brief_marks', array() );
	$res   = (array) get_option( 'md_brief_mark_res', array() );
	$cut   = date( 'Y-m-d', current_time( 'timestamp' ) - 90 * DAY_IN_SECONDS );
	$o = array( 'n' => 0, 'checked' => 0, 'ok' => 0, 'came' => 0, 'booked' => 0 );
	foreach ( $marks as $k => $v ) {
		$t = explode( '|', (string) $k )[0];
		if ( ( $v['d'] ?? '' ) < $cut || ( '' !== $tab && $t !== $tab ) || 'confirm' === $t ) { continue; }
		$o['n']++;
		if ( ! isset( $res[ $k ] ) ) { continue; }
		$o['checked']++;
		$r = $res[ $k ];
		if ( '' !== $r['came'] ) { $o['came']++; }
		if ( '' !== $r['booked'] ) { $o['booked']++; }
		if ( '' !== $r['came'] || '' !== $r['booked'] ) { $o['ok']++; }
	}
	return $o;
}

function md_brief_mark_stats_html( $tab ) {
	if ( 'confirm' === $tab ) { return ''; }
	$s = md_brief_mark_stats( $tab );
	if ( ! $s['n'] ) { return '<p class="mds-hint" style="margin:0 0 10px">리콜 성과 — 「연락함」을 누르면, 다음 날 아침 브리핑부터 그 환자가 예약했는지 · 30일 안에 왔는지 여기에 모아 보여 드립니다.</p>'; }
	$rate = $s['checked'] ? round( 100 * $s['ok'] / $s['checked'] ) : 0;
	return '<div class="mdb-perf"><b>리콜 성과</b> 최근 90일 「연락함」 ' . (int) $s['n'] . '명'
		. ( $s['checked'] ? ' → 예약 또는 내원 <b>' . (int) $s['ok'] . '명 (' . (int) $rate . '%)</b> <small>예약 있음 ' . (int) $s['booked'] . ' · 30일 안 내원 ' . (int) $s['came'] . '</small>' : '' )
		. ( $s['n'] > $s['checked'] ? ' <small>· ' . (int) ( $s['n'] - $s['checked'] ) . '명은 다음 브리핑 때 확인</small>' : '' ) . '</div>';
}

/* ============================================================
 * v9.9 · 추이 그래프 — 최근 8주 · 12개월 (총진료비 · 내원 · 신환). 한 그래프에 한 가지(축 하나), 막대 위에 마우스 = 값
 * ============================================================ */
function md_brief_bars_svg( $pts, $fmt, $title ) {
	$n = count( $pts );
	if ( ! $n ) { return ''; }
	$max = 0; foreach ( $pts as $p ) { $max = max( $max, (float) $p['v'] ); }
	if ( $max <= 0 ) { $max = 1; }
	$W = 320; $H = 150; $top = 18; $bot = 22; $gap = 4;
	$bw = ( $W - $gap * ( $n - 1 ) ) / $n; $ph = $H - $top - $bot;
	$svg = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-label="' . esc_attr( $title ) . '" class="mdb-chart__svg">';
	$svg .= '<line x1="0" y1="' . ( $H - $bot ) . '" x2="' . $W . '" y2="' . ( $H - $bot ) . '" stroke="#d9d9d9" stroke-width="1"/>';
	foreach ( array_values( $pts ) as $i => $p ) {
		$v = (float) $p['v']; $h = $v > 0 ? max( 2, $ph * $v / $max ) : 0;
		$x = $i * ( $bw + $gap ); $y = $H - $bot - $h;
		$fill = ! empty( $p['partial'] ) ? '#a8d5bf' : '#2e7d5b';
		$r = min( 4, $bw / 2, $h );
		/* 위쪽만 둥글게 · 바닥은 기준선에 붙임 */
		$d = $h > 0 ? sprintf( 'M%.1f %.1f V%.1f Q%.1f %.1f %.1f %.1f H%.1f Q%.1f %.1f %.1f %.1f V%.1f Z', $x, $H - $bot, $y + $r, $x, $y, $x + $r, $y, $x + $bw - $r, $x + $bw, $y, $x + $bw, $y + $r, $H - $bot ) : '';
		$svg .= '<g class="mdb-bar"><rect x="' . round( $x - $gap / 2, 1 ) . '" y="0" width="' . round( $bw + $gap, 1 ) . '" height="' . $H . '" fill="transparent"/>'
			. ( $d ? '<path d="' . $d . '" fill="' . $fill . '"/>' : '' )
			. '<title>' . esc_html( $p['label'] . ' · ' . $fmt( $v ) . ( ! empty( $p['partial'] ) ? ' (진행 중)' : '' ) . ( isset( $p['days'] ) ? ' · 진료 ' . (int) $p['days'] . '일' : '' ) ) . '</title></g>';
		if ( 0 === $i || $n - 1 === $i || 0 === $i % max( 1, (int) ceil( $n / 4 ) ) ) {
			$svg .= '<text x="' . round( $x + $bw / 2, 1 ) . '" y="' . ( $H - 6 ) . '" text-anchor="middle" font-size="10" fill="#888">' . esc_html( $p['tick'] ) . '</text>';
		}
	}
	$last = end( $pts );
	$svg .= '<text x="' . $W . '" y="11" text-anchor="end" font-size="11" fill="#333" font-weight="600">' . esc_html( ( ! empty( $last['partial'] ) ? '진행 중 ' : '최근 ' ) . $fmt( (float) $last['v'] ) ) . '</text>';
	return $svg . '</svg>';
}

function md_brief_trend_html( $tr ) {
	if ( ! is_array( $tr ) || ( empty( $tr['weeks'] ) && empty( $tr['months'] ) ) ) {
		return '<p class="mds-hint" style="margin:18px 0 0">추이 그래프는 다음 아침 브리핑부터 나옵니다.</p>';
	}
	$won = function ( $v ) { return md_brief_won( $v ) . '원'; };
	$cnt = function ( $v ) { return number_format( $v ) . '명'; };
	$sets = array(
		'최근 8주' => array_map( function ( $w ) { return array( 'label' => date( 'n/j', strtotime( $w['from'] ) ) . '~' . date( 'n/j', strtotime( $w['to'] ) ), 'tick' => date( 'n/j', strtotime( $w['from'] ) ), 'partial' => ! empty( $w['partial'] ), 'days' => $w['days'] ?? null, 'r' => $w ); }, (array) ( $tr['weeks'] ?? array() ) ),
		'최근 12개월' => array_map( function ( $m ) { return array( 'label' => date( 'Y년 n월', strtotime( $m['month'] . '-01' ) ), 'tick' => date( 'n월', strtotime( $m['month'] . '-01' ) ), 'partial' => ! empty( $m['partial'] ), 'days' => $m['days'] ?? null, 'r' => $m ); }, array_slice( (array) ( $tr['months'] ?? array() ), -12 ) ),
	);
	$h = '<h3 style="font-size:16px;margin:22px 0 6px">추이</h3><p class="mds-hint" style="margin:0 0 8px">막대에 마우스를 올리거나 누르면 값이 나옵니다. 연한 막대 = 아직 끝나지 않은 주 · 달.</p>';
	foreach ( $sets as $title => $pts ) {
		if ( ! $pts ) { continue; }
		$h .= '<h4 class="mdb-chart__h">' . esc_html( $title ) . '</h4><div class="mdb-charts">';
		foreach ( array( array( 'total', '총진료비', $won ), array( 'visits', '내원', $cnt ), array( 'new', '신환', $cnt ) ) as $m ) {
			$p2 = array_map( function ( $p ) use ( $m ) { return $p + array( 'v' => (float) ( $p['r'][ $m[0] ] ?? 0 ) ); }, $pts );
			$h .= '<figure class="mdb-chart"><figcaption>' . esc_html( $m[1] ) . '</figcaption>' . md_brief_bars_svg( $p2, $m[2], $title . ' ' . $m[1] ) . '<div class="mdb-chart__tip" aria-live="polite"></div></figure>';
		}
		$h .= '</div>';
	}
	/* 막대 값 — 마우스를 올리거나(PC) 누르면(휴대폰) 그래프 아래에 */
	$h .= '<script>(function(){function show(b){var f=b.closest(".mdb-chart");if(!f)return;f.querySelectorAll(".mdb-bar.is-on").forEach(function(x){x.classList.remove("is-on")});b.classList.add("is-on");var t=b.querySelector("title");f.querySelector(".mdb-chart__tip").textContent=t?t.textContent:"";}document.addEventListener("mouseover",function(e){var b=e.target.closest&&e.target.closest(".mdb-bar");if(b)show(b);});document.addEventListener("click",function(e){var b=e.target.closest&&e.target.closest(".mdb-bar");if(b)show(b);});})();</script>';
	return $h;
}

/* ============================================================
 * v9.9 · 주간(월요일) · 월간(1일) 요약 메일 — 매일 브리핑을 보낸 뒤 한 번씩
 * ============================================================ */
function md_brief_period_mails( $b ) {
	$tr = $b['trend'] ?? null;
	$run = (string) ( $b['today']['date'] ?? '' );
	if ( ! is_array( $tr ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $run ) ) { return; }
	$to = md_brief_recipients();
	if ( ! $to ) { return; }
	$sent = (array) get_option( 'md_brief_period_sent', array() );
	$send = function ( $kind, $subj, $html ) use ( $to, &$sent, $run ) {
		$body = '<div style="font-family:-apple-system,\'Malgun Gothic\',sans-serif;max-width:640px;color:#222;line-height:1.5">' . $html
			. '<p style="margin-top:24px"><a href="' . esc_url( md_brief_lounge_url() ) . '" style="display:inline-block;background:#2e7d5b;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">직원 라운지에서 추이 보기</a></p></div>';
		foreach ( $to as $m ) { wp_mail( $m, $subj, $body, array( 'Content-Type: text/html; charset=UTF-8' ) ); }
		$sent[ $kind ] = $run;
	};
	$row = function ( $label, $a, $p, $p2, $fmt, $l1, $l2 ) {
		$td = 'style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right;white-space:nowrap"';
		return '<tr><td style="padding:6px 8px;border-bottom:1px solid #eee">' . esc_html( $label ) . '</td><td ' . $td . '><b>' . esc_html( $fmt( $a ) ) . '</b></td><td ' . $td . '>' . esc_html( $fmt( $p ) ) . ' ' . md_brief_delta( $a, $p ) . '</td><td ' . $td . '>' . esc_html( $fmt( $p2 ) ) . ' ' . md_brief_delta( $a, $p2 ) . '</td></tr>';
	};
	$table = function ( $cur, $c1, $c2, $h1, $h2, $h0 ) use ( $row ) {
		$won = function ( $v ) { return md_brief_won( $v ); };
		$cnt = function ( $v ) { return number_format( (float) $v ) . '명'; };
		$th = 'style="padding:6px 8px;border-bottom:2px solid #ddd;text-align:right;font-weight:600;color:#555"';
		$avg = function ( $a ) { return ! empty( $a['visits'] ) ? (float) $a['total'] / $a['visits'] : 0; };
		return '<table style="border-collapse:collapse;width:100%"><tr><th style="padding:6px 8px;border-bottom:2px solid #ddd"></th><th ' . $th . '>' . esc_html( $h0 ) . '</th><th ' . $th . '>' . esc_html( $h1 ) . '</th><th ' . $th . '>' . esc_html( $h2 ) . '</th></tr>'
			. $row( '총진료비', $cur['total'] ?? 0, $c1['total'] ?? 0, $c2['total'] ?? 0, $won, $h1, $h2 )
			. $row( '비급여', $cur['bi'] ?? 0, $c1['bi'] ?? 0, $c2['bi'] ?? 0, $won, $h1, $h2 )
			. $row( '수납', $cur['paid'] ?? 0, $c1['paid'] ?? 0, $c2['paid'] ?? 0, $won, $h1, $h2 )
			. $row( '내원', $cur['visits'] ?? 0, $c1['visits'] ?? 0, $c2['visits'] ?? 0, $cnt, $h1, $h2 )
			. $row( '신환', $cur['new'] ?? 0, $c1['new'] ?? 0, $c2['new'] ?? 0, $cnt, $h1, $h2 )
			. $row( '환자당 진료비', $avg( $cur ), $avg( $c1 ), $avg( $c2 ), function ( $v ) { return number_format( round( $v / 1000 ) * 1000 ) . '원'; }, $h1, $h2 )
			. '</table>';
	};
	/* 월요일 — 지난주 (월~일) */
	$weeks = array_values( (array) ( $tr['weeks'] ?? array() ) );
	if ( 1 === (int) date( 'N', strtotime( $run ) ) && ( $sent['week'] ?? '' ) !== $run && count( $weeks ) >= 6 ) {
		$cur = $weeks[ count( $weeks ) - 1 ]; $prev = $weeks[ count( $weeks ) - 2 ];
		$four = array( 'total' => 0, 'bi' => 0, 'paid' => 0, 'visits' => 0, 'new' => 0 );
		foreach ( array_slice( $weeks, -5, 4 ) as $w ) { foreach ( $four as $k => $v ) { $four[ $k ] += (float) ( $w[ $k ] ?? 0 ) / 4; } }
		$lab = date( 'n/j', strtotime( $cur['from'] ) ) . '~' . date( 'n/j', strtotime( $cur['to'] ) );
		$send( 'week', '[문치과병원 주간 브리핑] ' . $lab . ' 진료비 ' . md_brief_won( $cur['total'] ?? 0 ) . ' · 내원 ' . (int) ( $cur['visits'] ?? 0 ) . '명',
			'<h2 style="margin:0 0 4px;font-size:20px">지난주 (' . esc_html( $lab ) . ') 요약</h2><p style="margin:0 0 14px;color:#666">진료 ' . (int) ( $cur['days'] ?? 0 ) . '일 · 그 전 주와 최근 4주 평균 대비</p>'
			. $table( $cur, $prev, $four, '그 전 주', '4주 평균', '지난주' ) );
	}
	/* 1일 — 지난달 */
	$months = array_values( (array) ( $tr['months'] ?? array() ) );
	if ( '01' === substr( $run, 8, 2 ) && ( $sent['month'] ?? '' ) !== $run && count( $months ) >= 14 ) {
		$cur = $months[13]; $prev = $months[12]; $ly = $months[1];
		$lab = date( 'Y년 n월', strtotime( $cur['month'] . '-01' ) );
		$send( 'month', '[문치과병원 월간 브리핑] ' . $lab . ' 진료비 ' . md_brief_won( $cur['total'] ?? 0 ) . ' · 내원 ' . number_format( (float) ( $cur['visits'] ?? 0 ) ) . '명',
			'<h2 style="margin:0 0 4px;font-size:20px">' . esc_html( $lab ) . ' 요약</h2><p style="margin:0 0 14px;color:#666">진료 ' . (int) ( $cur['days'] ?? 0 ) . '일 · 그 전 달과 작년 같은 달 대비</p>'
			. $table( $cur, $prev, $ly, '그 전 달', '작년 같은 달', '지난달' ) );
	}
	update_option( 'md_brief_period_sent', $sent, false );
}
