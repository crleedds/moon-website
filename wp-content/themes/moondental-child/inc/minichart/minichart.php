<?php
/**
 * v6.2 · 미니차트 — 직원 라운지(/직원/?app=minichart)
 *
 *  AppSheet 「Mini Chart」(구글 시트 Mini Chart · Sheet1)를 라운지로 옮긴 것.
 *  한 줄 = 환자 한 명(차트번호 · 성명 · 주소 · 병력 · 소개 · 담당의 · 치료계획 · 치료이력 · 참고사항)
 *  또는 팀 노트 한 장(팀 피드 · 팀 수칙 · 프로토콜 · 처방전 · 개인노트 …).
 *
 *  AppSheet 에서 달라진 점
 *    - 차트번호 · 이름 · 초성으로 바로 찾기, 「내용까지 찾기」는 병력 · 치료이력 · 참고사항까지
 *    - 치료이력 · 참고사항은 날짜와 한 줄만 적으면 맨 위에 「YYMMDD: 내용」으로 붙는다
 *      (AppSheet 에서 날짜가 두 번 붙거나 숫자 46143.5… 가 남던 문제가 없다)
 *    - 별표항목은 차트번호 · 성명만 반드시, 나머지는 비어 있으면 「미입력」 표시 (「.」 로 때우지 않게)
 *    - 두 사람이 같은 차트를 동시에 고치면 뒤에 저장한 사람에게 알린다 (덮어쓰지 않는다)
 *    - 고칠 때마다 이전 내용을 남긴다 → 「변경 기록」에서 되돌리기, 지운 차트는 휴지통에서 되살리기
 *    - 태블릿 보기(큰 글씨) — 담당의 호출 전 체어 태블릿에 띄우는 화면
 *
 *  환자 정보는 공개 저장소에 넣지 않는다 — 처음 데이터는 관리자가 「가져오기」에서
 *  구글 시트를 엑셀로 받아 올린다.
 *
 *  누가 무엇을: 직원 공용 계정 = 보기 · 추가 · 수정 · 삭제(휴지통) · 되돌리기,
 *               관리자 = 가져오기 · 엑셀 내려받기 · 휴지통 비우기.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_MC_SCHEMA', 4 ); /* v6.4 · 2 = 팀 노트 고정 해제 · v6.5 · 3 = 최근 본 시각 · 최근 내원일 열 · v8.7 · 4 = 덴트웹 진료기록 숨김 · 고침 (dw_hide) */

/* ============================================================
 * 테이블
 * ============================================================ */

function md_mc_t( $k = 'rec' ) {
	global $wpdb;
	return $wpdb->prefix . 'md_mc_' . $k;
}

/**
 * 환자 칸 — 키 => [ 이름, 별표(필수), 짧은 이름 ]
 * v6.4 · 이름은 AppSheet 폼 그대로 (원장 지시)
 */
function md_mc_fields() {
	return array(
		'chart_no' => array( '차트번호', true, '차트번호' ),
		'pname'    => array( '성명 / 호칭 / 호명', true, '성명 / 호칭 / 호명' ),
		'addr'     => array( '지역', true, '지역' ),
		'mhx'      => array( '병력 (상세)', false, '병력' ), /* v9.9 · 병력 · 내원경로는 필수 아님 (원장 지시) */
		'referral' => array( '내원경로 / 가족 / 협력기관', false, '내원경로 / 가족 / 협력기관' ),
		'dr'       => array( '담당의', true, '담당의' ),
		'tx_plan'  => array( '치료계획', false, '치료계획' ),
		'tx_hist'  => array( '주요치과치료이력', false, '주요치과치료이력' ),
		'memo'     => array( '참고사항', false, '참고사항' ),
	);
}

/** 주요치과치료이력에서 가장 늦은 「YYMMDD:」 날짜 → Y-m-d (없으면 null) — 「최근 내원순」 정렬용 */
function md_mc_last_visit( $hist ) {
	$best = '';
	$max  = date( 'Y-m-d', current_time( 'timestamp' ) + DAY_IN_SECONDS );
	if ( preg_match_all( '/^\s*(\d{2})(\d{2})(\d{2})\s*:/m', (string) $hist, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $d ) {
			if ( ! checkdate( (int) $d[2], (int) $d[3], 2000 + (int) $d[1] ) ) { continue; }
			$ymd = sprintf( '20%s-%s-%s', $d[1], $d[2], $d[3] );
			if ( $ymd <= $max && $ymd > $best ) { $best = $ymd; }
		}
	}
	return '' === $best ? null : $best;
}

function md_mc_blank( $v ) {
	$v = trim( (string) $v );
	return '' === $v || md_mc_is_na( $v );
}

/** v6.8 · 「해당없음」 — 예전 AppSheet 에서 마침표로 적던 것도 같은 뜻 (원장 지시) */
define( 'MD_MC_NA', '해당없음' );
function md_mc_is_na( $v ) {
	return in_array( trim( (string) $v ), array( '.', '-', MD_MC_NA, '해당 없음' ), true );
}

function md_mc_maybe_install() {
	if ( (int) get_option( 'md_mc_schema', 0 ) >= MD_MC_SCHEMA ) { return; }
	if ( ! add_option( 'md_mc_installing', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'md_mc_installing' ) < 300 ) { return; }
		delete_option( 'md_mc_installing' );
		if ( ! add_option( 'md_mc_installing', time(), '', 'no' ) ) { return; }
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	dbDelta( 'CREATE TABLE ' . md_mc_t() . " (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		uid VARCHAR(40) NOT NULL DEFAULT '',
		kind VARCHAR(10) NOT NULL DEFAULT 'patient',
		chart_no VARCHAR(60) NOT NULL DEFAULT '',
		pname VARCHAR(255) NOT NULL DEFAULT '',
		cho VARCHAR(255) NOT NULL DEFAULT '',
		addr TEXT NULL,
		mhx TEXT NULL,
		referral TEXT NULL,
		dr TEXT NULL,
		tx_plan TEXT NULL,
		tx_hist MEDIUMTEXT NULL,
		memo MEDIUMTEXT NULL,
		title VARCHAR(255) NOT NULL DEFAULT '',
		body MEDIUMTEXT NULL,
		pin TINYINT NOT NULL DEFAULT 0,
		rev INT NOT NULL DEFAULT 1,
		created_at DATETIME NULL,
		updated_at DATETIME NULL,
		updated_by VARCHAR(60) NOT NULL DEFAULT '',
		deleted_at DATETIME NULL,
		viewed_at DATETIME NULL,
		last_visit DATE NULL,
		dw_hide TEXT NULL,
		PRIMARY KEY  (id),
		KEY kind (kind),
		KEY chart_no (chart_no),
		KEY updated_at (updated_at)
	) $c;" );
	dbDelta( 'CREATE TABLE ' . md_mc_t( 'log' ) . " (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		rec_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		act VARCHAR(20) NOT NULL DEFAULT '',
		who VARCHAR(60) NOT NULL DEFAULT '',
		at DATETIME NULL,
		snap MEDIUMTEXT NULL,
		PRIMARY KEY  (id),
		KEY rec_id (rec_id)
	) $c;" );
	/* v6.4 · 팀 노트는 환자 목록 위에 고정하지 않는다 — 팀 노트 탭에만 */
	$wpdb->query( 'UPDATE ' . md_mc_t() . " SET pin = 0 WHERE kind = 'note'" );
	/* v6.5 · 최근 내원일 = 주요치과치료이력에서 가장 늦은 「YYMMDD:」 날짜 */
	foreach ( (array) $wpdb->get_results( 'SELECT id, tx_hist FROM ' . md_mc_t() . " WHERE kind = 'patient' AND last_visit IS NULL" ) as $r ) {
		$lv = md_mc_last_visit( $r->tx_hist );
		if ( $lv ) { $wpdb->update( md_mc_t(), array( 'last_visit' => $lv ), array( 'id' => (int) $r->id ) ); }
	}
	/* v6.5 · 담당의 칸에 교정 (원장 지시) — 이미 칸을 저장해 둔 경우에도 넣는다 */
	$st = get_option( 'md_mc_settings', array() );
	if ( is_array( $st ) && ! empty( $st['roles'] ) && is_array( $st['roles'] ) && ! in_array( '교정', $st['roles'], true ) ) {
		$st['roles'][] = '교정';
		update_option( 'md_mc_settings', $st, false );
	}
	update_option( 'md_mc_schema', MD_MC_SCHEMA );
	delete_option( 'md_mc_installing' );
}
add_action( 'init', 'md_mc_maybe_install', 20 );

/* ============================================================
 * 도움 함수
 * ============================================================ */

/* v8.0 · 누가 쓸지는 라운지 탭 권한(inc/security, 기본 라운지 관리자만)이 정한다 — 여기서는 라운지 사용자면 된다 */
function md_mc_can_use()    { return function_exists( 'md_sup_can_use' ) && md_sup_can_use() && ( ! function_exists( 'md_sec_can_tab' ) || md_sec_can_tab( 'minichart' ) ); }
function md_mc_can_manage() { return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage(); }
/**
 * v6.8 · 권한 세 단계 (원장 지시)
 *   직원           보기 · 환자/노트 추가 · 수정 · 한 줄 추가 · 상단고정
 *   라운지 관리자   + 삭제 · 휴지통 되살리기 · 변경 기록 되돌리기 · 설정
 *   원장 계정       + Import/Export · 휴지통 비우기 · 영구 삭제
 */
function md_mc_is_owner() {
	return function_exists( 'md_sup_is_owner' ) ? md_sup_is_owner() : current_user_can( 'manage_options' );
}

/** 기록에 남길 이름 — 품목신청에서 고른 이름이 있으면 그것, 없으면 계정 */
function md_mc_me() {
	if ( function_exists( 'md_inv_me' ) ) { return mb_substr( (string) md_inv_me(), 0, 60 ); }
	return mb_substr( wp_get_current_user()->user_login, 0, 60 );
}

function md_mc_cho( $s ) {
	static $cho = array( 'ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ' );
	$out = '';
	foreach ( preg_split( '//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY ) as $ch ) {
		$c = mb_ord( $ch, 'UTF-8' );
		if ( $c >= 0xAC00 && $c <= 0xD7A3 ) { $out .= $cho[ intdiv( $c - 0xAC00, 588 ) ]; }
		elseif ( ! preg_match( '/\s/u', $ch ) ) { $out .= mb_strtolower( $ch, 'UTF-8' ); }
	}
	return mb_substr( $out, 0, 250 );
}

function md_mc_is_cho( $q ) {
	return (bool) preg_match( '/^[\x{3131}-\x{314E}\s]+$/u', (string) $q );
}

function md_mc_get( $id, $with_deleted = false ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_mc_t() . ' WHERE id = %d', (int) $id ) );
	if ( $r && ! $with_deleted && $r->deleted_at ) { return null; }
	return $r;
}

/** 이전 내용을 남긴다 (되돌리기용) */
function md_mc_log( $rec_id, $act, $before = null ) {
	global $wpdb;
	$wpdb->insert( md_mc_t( 'log' ), array(
		'rec_id' => (int) $rec_id,
		'act'    => mb_substr( $act, 0, 20 ),
		'who'    => md_mc_me(),
		'at'     => current_time( 'mysql' ),
		'snap'   => $before ? wp_json_encode( $before, JSON_UNESCAPED_UNICODE ) : '',
	) );
}

function md_mc_url( $args = array() ) {
	$args = array_merge( array( 'app' => 'minichart' ), $args );
	$args = array_filter( $args, function ( $v ) { return '' !== $v && null !== $v; } );
	return md_sup_url( $args );
}

/** YYMMDD — 입력 칸 type=date 값(Y-m-d) → 260422 */
function md_mc_yymmdd( $ymd = '' ) {
	$ts = $ymd ? strtotime( $ymd ) : false;
	return $ts ? date( 'ymd', $ts ) : current_time( 'ymd' );
}

/** 260422 / 2026-04-22 09:00 → 26.04.22 */
function md_mc_short_date( $dt ) {
	if ( ! $dt ) { return ''; }
	$ts = strtotime( $dt );
	return $ts ? date( 'y.m.d', $ts ) : '';
}

/** 글 → HTML: 주소는 링크로, 줄 맨 앞 「260422:」 날짜는 굵게 */
function md_mc_text( $s ) {
	$s = (string) $s;
	if ( md_mc_is_na( $s ) ) { return '<span class="mc-na">' . MD_MC_NA . '</span>'; }
	if ( '' === trim( $s ) ) { return '<span class="mc-none">—</span>'; }
	$lines = preg_split( '/\r\n|\r|\n/', $s );
	$out   = array();
	foreach ( $lines as $ln ) {
		$h = esc_html( $ln );
		$h = preg_replace_callback( '#https?://[^\s<>"]+#u', function ( $m ) {
			$u = html_entity_decode( $m[0] );
			$label = mb_strlen( $u ) > 48 ? mb_substr( $u, 0, 46 ) . '…' : $u;
			return '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
		}, $h );
		$h = preg_replace( '/^(\s*)(\d{6})(\s*:)/u', '$1<b class="mc-date">$2</b>$3', $h );
		$h = preg_replace( '/^(\s*)(\[[^\]]{1,40}\])/u', '$1<b class="mc-h">$2</b>', $h );
		$out[] = $h;
	}
	return implode( '<br>', $out );
}

/** 치료이력 맨 위 한 줄 (목록에 보이는 최근 진료) */
function md_mc_last_line( $s ) {
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $s ) as $ln ) {
		if ( preg_match( '/^\s*\d{6}\s*:/', $ln ) ) { return trim( $ln ); }
	}
	return '';
}

/* ============================================================
 * v6.3 · 병력 주의 단어 — 진료 전에 놓치면 안 되는 것만 붉게
 *   (항혈전·항응고 → 출혈, 골흡수억제제 → 턱뼈 괴사, 알러지, 임신, 투석, 스텐트·판막, 항암·방사선)
 * ============================================================ */

/** 기본 단어 — 설정 탭에서 관리자가 바꿀 수 있다 (v6.5) */
function md_mc_alert_words_default() {
	return array(
		'항혈전', '항응고', '아스피린', '와파린', '쿠마딘', '플라빅스', '클로피도그렐', '엘리퀴스', '자렐토', '프라닥사', '릭시아나', '헤파린',
		'골흡수억제', '비스포스포네이트', 'BP제제', '포사맥스', '프롤리아', '본비바', '악토넬', '졸레드론', '데노수맙', '골다공증 주사', '골다공증주사',
		'알러지', '알레르기', '페니실린',
		'임신', '수유',
		'투석', '스텐트', '인공판막', '판막',
		'항암', '방사선',
	);
}

function md_mc_alert_words() {
	$s = md_mc_settings();
	$w = isset( $s['alert_words'] ) && is_array( $s['alert_words'] ) ? $s['alert_words'] : md_mc_alert_words_default();
	return (array) apply_filters( 'md_mc_alert_words', $w );
}

/** 병력 글에서 주의 단어를 찾는다 — 「아스피린x」 「아스피린 X」 처럼 「안 먹음」 표시는 뺀다 */
function md_mc_alerts( $mhx ) {
	$mhx = (string) $mhx;
	if ( '' === trim( $mhx ) ) { return array(); }
	$hit = array();
	foreach ( md_mc_alert_words() as $w ) {
		if ( ! preg_match_all( '/' . preg_quote( $w, '/' ) . '(.{0,4})/u', $mhx, $m, PREG_SET_ORDER ) ) { continue; }
		foreach ( $m as $one ) {
			if ( preg_match( '/^[\s:：\-(]*([xX×✕]|없|무|안|[Nn][Oo]?(?![A-Za-z]))/u', $one[1] ) ) { continue; }
			$hit[ $w ] = true;
			break;
		}
	}
	return array_keys( $hit );
}

/** 읽기 화면용 — 주의 단어를 붉게 */
function md_mc_mark_alerts( $html, $words ) {
	foreach ( $words as $w ) {
		$html = preg_replace( '/' . preg_quote( esc_html( $w ), '/' ) . '/u', '<mark class="mc-alert">$0</mark>', $html );
	}
	return $html;
}

/* ============================================================
 * v6.4 · 담당의 — 원장님 이름은 목록에서 고른다. 목록과 칸(임플란트 · 보철 …)은 관리자가 고친다.
 *   저장은 AppSheet 그대로 글 한 칸: 「임플란트: 문은수\n보철: 이창률」
 * ============================================================ */

function md_mc_settings() {
	$s = get_option( 'md_mc_settings', array() );
	return is_array( $s ) ? $s : array();
}

/** 원장님 목록 — 저장된 것이 없으면 경영지원실 요청의 「Dr. ○○○팀」에서 처음 목록을 만든다 */
function md_mc_doctors() {
	$s = md_mc_settings();
	if ( isset( $s['doctors'] ) && is_array( $s['doctors'] ) ) { return $s['doctors']; }
	$out = array();
	if ( function_exists( 'md_support_teams' ) ) {
		foreach ( md_support_teams() as $t ) {
			if ( preg_match( '/^Dr\.\s*(\S+?)팀$/u', $t, $m ) ) { $out[] = $m[1]; }
		}
	}
	return $out ? $out : array( '문은수', '이창률' );
}

/** 담당의 칸 이름 — AppSheet 처음 값 「임플란트: / 보철:」 + v6.5 교정 */
function md_mc_dr_roles() {
	$s = md_mc_settings();
	return isset( $s['roles'] ) && is_array( $s['roles'] ) && $s['roles'] ? $s['roles'] : array( '임플란트', '보철', '교정' );
}

/**
 * v6.8 · 담당의 글 → [ 기본 담당의, [ [과, 이름] … ], 나머지 줄 ]
 *   「이창률」처럼 과 없이 이름만 있는 줄 = 기본 담당의 (예전 자료 247명이 이 모양)
 *   「임플란트: 문은수」 = 과 · 담당의. 「보철: 」처럼 이름이 빈 줄(AppSheet 처음 값)은 버린다.
 */
function md_mc_dr_parse( $dr ) {
	$main  = '';
	$pairs = array();
	$extra = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $dr ) as $ln ) {
		if ( '' === trim( $ln ) || md_mc_is_na( $ln ) ) { continue; }
		if ( preg_match( '/^\s*([^:：]+?)\s*[:：]\s*(.*)$/u', $ln, $m ) ) {
			$role = trim( $m[1] );
			$name = trim( $m[2] );
			if ( '' === $name ) { continue; }
			if ( in_array( $role, array( '기본', '기본 담당의', '담당', '담당의' ), true ) && '' === $main ) { $main = $name; continue; }
			$pairs[] = array( $role, $name );
			continue;
		}
		if ( '' === $main && mb_strlen( trim( $ln ) ) <= 20 ) { $main = trim( $ln ); continue; }
		$extra[] = rtrim( $ln );
	}
	return array( $main, $pairs, implode( "\n", $extra ) );
}

function md_mc_dr_compose( $main, $depts, $docs, $extra, $main_dept = '' ) {
	$lines = array();
	$main  = trim( sanitize_text_field( (string) $main ) );
	$md    = trim( sanitize_text_field( (string) $main_dept ) );
	if ( '' !== $main ) { $lines[] = '' !== $md ? $md . ': ' . $main : $main; } /* v8.3 · 첫 담당의도 과를 고를 수 있다 */
	foreach ( array_values( (array) $depts ) as $i => $dept ) {
		$dept = trim( sanitize_text_field( (string) $dept ) );
		$doc  = trim( sanitize_text_field( (string) ( array_values( (array) $docs )[ $i ] ?? '' ) ) );
		if ( '' === $doc ) { continue; }
		$lines[] = ( '' !== $dept ? $dept : '담당' ) . ': ' . $doc;
	}
	$extra = trim( sanitize_textarea_field( (string) $extra ) );
	if ( '' !== $extra ) { $lines[] = $extra; }
	return implode( "\n", $lines );
}

/** 읽기 화면용 — 기본 담당의는 굵게 */
function md_mc_dr_html( $dr ) {
	if ( '' === trim( (string) $dr ) ) { return '<span class="mc-none">—</span>'; }
	list( $main, $pairs, $extra ) = md_mc_dr_parse( $dr );
	$out = array();
	if ( '' !== $main ) { $out[] = '<span class="mc-dr__main"><b>' . esc_html( $main ) . '</b></span>'; }
	foreach ( $pairs as $pr ) { $out[] = '<span class="mc-dr__pairv"><small>' . esc_html( $pr[0] ) . '</small> <b>' . esc_html( $pr[1] ) . '</b></span>'; }
	if ( '' !== $extra ) { $out[] = md_mc_text( $extra ); }
	return implode( '<br>', $out );
}

/* ============================================================
 * 쓰기
 * ============================================================ */

/** 저장 — $id 0 이면 새로. $rev 가 지금 값과 다르면 다른 사람이 먼저 고친 것. */
function md_mc_save( $id, $kind, $data, $rev = 0 ) {
	global $wpdb;
	$t    = md_mc_t();
	$now  = current_time( 'mysql' );
	$kind = 'note' === $kind ? 'note' : 'patient';
	$row  = array( 'kind' => $kind );

	if ( 'note' === $kind ) {
		$row['title'] = mb_substr( trim( sanitize_text_field( (string) ( $data['title'] ?? '' ) ) ), 0, 250 );
		$row['body']  = trim( sanitize_textarea_field( (string) ( $data['body'] ?? '' ) ), "\r\n" );
		if ( '' === $row['title'] ) { return new WP_Error( 'mc', '노트 제목을 적어 주세요.' ); }
		$row['cho'] = md_mc_cho( $row['title'] );
	} else {
		foreach ( md_mc_fields() as $k => $f ) {
			$v = (string) ( $data[ $k ] ?? '' );
			$row[ $k ] = in_array( $k, array( 'chart_no', 'pname' ), true )
				? trim( sanitize_text_field( $v ) )
				: rtrim( sanitize_textarea_field( $v ) );
		}
		$row['chart_no'] = mb_substr( preg_replace( '/\s+/u', '', $row['chart_no'] ), 0, 60 );
		$row['pname']    = mb_substr( $row['pname'], 0, 250 );
		/* v6.5 · 별표항목은 모두 채워야 저장된다 (AppSheet 와 같이 · 원장 지시). 해당 없으면 마침표 */
		foreach ( md_mc_fields() as $k => $fd ) {
			if ( $fd[1] && '' === trim( (string) $row[ $k ] ) ) {
				return new WP_Error( 'mc', '「' . $fd[0] . '」을(를) 적어 주세요.' . ( in_array( $k, array( 'chart_no', 'pname', 'dr' ), true ) ? '' : ' 해당사항이 없으면 「해당없음」을 체크합니다.' ) );
			}
		}
		$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE kind = 'patient' AND chart_no = %s AND deleted_at IS NULL AND id <> %d LIMIT 1", $row['chart_no'], (int) $id ) );
		if ( $dup ) {
			return new WP_Error( 'mc_dup', '차트번호 ' . $row['chart_no'] . ' 은(는) 이미 있습니다.', (int) $dup );
		}
		$row['cho']        = md_mc_cho( $row['pname'] );
		$row['last_visit'] = md_mc_last_visit( $row['tx_hist'] );
	}
	if ( isset( $data['pin'] ) ) { $row['pin'] = $data['pin'] && 'patient' === $kind ? 1 : 0; }
	$row['updated_at'] = $now;
	$row['updated_by'] = md_mc_me();

	if ( ! $id ) {
		$row['created_at'] = $now;
		$row['rev']        = 1;
		if ( ! $wpdb->insert( $t, $row ) ) { return new WP_Error( 'mc', '저장하지 못했습니다. 잠시 후 다시 시도해 주세요.' ); }
		$nid = (int) $wpdb->insert_id;
		md_mc_log( $nid, 'new' );
		return $nid;
	}

	$cur = md_mc_get( $id );
	if ( ! $cur ) { return new WP_Error( 'mc', '그 차트가 없습니다. 지워졌을 수 있습니다.' ); }
	if ( $rev && (int) $cur->rev !== (int) $rev ) {
		return new WP_Error( 'mc_conflict', '다른 분(' . $cur->updated_by . ', ' . md_mc_short_date( $cur->updated_at ) . ' ' . date( 'H:i', strtotime( $cur->updated_at ) ) . ')이 먼저 고쳤습니다.' );
	}
	$row['rev'] = (int) $cur->rev + 1;
	/* rev 가 그대로일 때만 바뀐다 — 읽은 뒤 저장 사이에 다른 사람이 끼어들면 0 줄 */
	$n = $wpdb->update( $t, $row, array( 'id' => (int) $id, 'rev' => (int) $cur->rev ) );
	if ( false === $n ) { return new WP_Error( 'mc', '저장하지 못했습니다.' ); }
	if ( 0 === (int) $n ) { return new WP_Error( 'mc_conflict', '다른 분이 방금 고쳤습니다.' ); }
	md_mc_log( $id, 'edit', $cur );
	return (int) $id;
}

/**
 * 치료이력 · 참고사항(노트는 본문) 맨 위에 한 줄을 붙인다.
 * 다른 사람이 그사이 고쳐도 잃지 않게 rev 를 확인하며 세 번까지 다시 시도한다.
 */
function md_mc_add_line( $id, $field, $text, $ymd = '' ) {
	global $wpdb;
	$text = trim( sanitize_textarea_field( (string) $text ) );
	if ( '' === $text ) { return new WP_Error( 'mc', '내용을 적어 주세요.' ); }
	$date = md_mc_yymmdd( $ymd );
	for ( $i = 0; $i < 3; $i++ ) {
		$cur = md_mc_get( $id );
		if ( ! $cur ) { return new WP_Error( 'mc', '그 차트가 없습니다.' ); }
		if ( 'note' === $cur->kind ) { $field = 'body'; $line = $date . "\n" . $text; }
		else {
			if ( ! in_array( $field, array( 'tx_hist', 'memo' ), true ) ) { return new WP_Error( 'mc', '잘못된 칸입니다.' ); }
			$line = $date . ': ' . $text;
		}
		$old = (string) $cur->$field;
		$new = '' === trim( $old ) ? $line : $line . "\n" . ( 'body' === $field ? "\n" : '' ) . $old;
		$upd = array(
			$field       => $new,
			'rev'        => (int) $cur->rev + 1,
			'updated_at' => current_time( 'mysql' ),
			'updated_by' => md_mc_me(),
		);
		if ( 'tx_hist' === $field ) { $upd['last_visit'] = md_mc_last_visit( $new ); }
		$ok  = $wpdb->update( md_mc_t(), $upd, array( 'id' => (int) $id, 'rev' => (int) $cur->rev ) );
		if ( $ok ) { md_mc_log( $id, 'add', $cur ); return true; }
	}
	return new WP_Error( 'mc', '다른 분이 동시에 고치는 중입니다. 다시 눌러 주세요.' );
}

function md_mc_set_pin( $id, $on ) {
	global $wpdb;
	$cur = md_mc_get( $id );
	if ( ! $cur ) { return; }
	$wpdb->update( md_mc_t(), array( 'pin' => $on ? 1 : 0, 'rev' => (int) $cur->rev + 1 ), array( 'id' => (int) $id ) );
	md_mc_log( $id, $on ? 'pin' : 'unpin', $cur );
}

function md_mc_trash( $id ) {
	global $wpdb;
	$cur = md_mc_get( $id );
	if ( ! $cur ) { return; }
	$wpdb->update( md_mc_t(), array( 'deleted_at' => current_time( 'mysql' ), 'updated_by' => md_mc_me() ), array( 'id' => (int) $id ) );
	md_mc_log( $id, 'delete', $cur );
}

function md_mc_untrash( $id ) {
	global $wpdb;
	$cur = md_mc_get( $id, true );
	if ( ! $cur ) { return new WP_Error( 'mc', '그 차트가 없습니다.' ); }
	if ( 'patient' === $cur->kind ) {
		$dup = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . md_mc_t() . " WHERE kind = 'patient' AND chart_no = %s AND deleted_at IS NULL AND id <> %d LIMIT 1", $cur->chart_no, (int) $id ) );
		if ( $dup ) { return new WP_Error( 'mc', '같은 차트번호(' . $cur->chart_no . ')가 이미 있어 되살리지 않았습니다. 그 차트에 내용을 옮겨 적어 주세요.' ); }
	}
	$wpdb->update( md_mc_t(), array( 'deleted_at' => null, 'rev' => (int) $cur->rev + 1 ), array( 'id' => (int) $id ) );
	md_mc_log( $id, 'restore', $cur );
	return true;
}

/** 변경 기록의 한 시점으로 되돌린다 (되돌리기 전 내용도 기록에 남으므로 다시 되돌릴 수 있다) */
function md_mc_revert( $log_id ) {
	global $wpdb;
	$lg = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_mc_t( 'log' ) . ' WHERE id = %d', (int) $log_id ) );
	if ( ! $lg || '' === (string) $lg->snap ) { return new WP_Error( 'mc', '되돌릴 내용이 없습니다.' ); }
	$snap = json_decode( $lg->snap, true );
	$cur  = md_mc_get( $lg->rec_id, true );
	if ( ! is_array( $snap ) || ! $cur ) { return new WP_Error( 'mc', '되돌릴 수 없습니다.' ); }
	$keep = array( 'chart_no', 'pname', 'cho', 'addr', 'mhx', 'referral', 'dr', 'tx_plan', 'tx_hist', 'memo', 'title', 'body', 'pin' );
	$upd  = array_intersect_key( $snap, array_flip( $keep ) );
	$upd['rev']        = (int) $cur->rev + 1;
	$upd['updated_at'] = current_time( 'mysql' );
	$upd['updated_by'] = md_mc_me();
	$upd['deleted_at'] = null;
	if ( 'patient' === $cur->kind ) { $upd['last_visit'] = md_mc_last_visit( $upd['tx_hist'] ?? $cur->tx_hist ); }
	$wpdb->update( md_mc_t(), $upd, array( 'id' => (int) $cur->id ) );
	md_mc_log( $cur->id, 'revert', $cur );
	return (int) $cur->id;
}

/* ============================================================
 * 읽기
 * ============================================================ */

/** 목록 — 환자. $q 가 있으면 차트번호 · 이름(초성) · 내용까지 */
function md_mc_patients( $q = '', $filter = '', $doc = '', $sort = '' ) {
	global $wpdb;
	$t     = md_mc_t();
	$where = array( "kind = 'patient'", 'deleted_at IS NULL' );
	$args  = array();
	if ( 'pin' === $filter ) { $where[] = 'pin = 1'; }
	if ( '' !== $doc ) { $where[] = 'dr LIKE %s'; $args[] = '%' . $wpdb->esc_like( $doc ) . '%'; }
	if ( '' !== $q ) {
		if ( md_mc_is_cho( $q ) ) {
			$where[] = 'cho LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( preg_replace( '/\s+/u', '', $q ) ) . '%';
		} else {
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			$cols = array( 'chart_no', 'pname', 'addr', 'mhx', 'referral', 'dr', 'tx_plan', 'tx_hist', 'memo' );
			$where[] = '(' . implode( ' OR ', array_map( function ( $c ) { return "$c LIKE %s"; }, $cols ) ) . ')';
			$args = array_merge( $args, array_fill( 0, count( $cols ), $like ) );
		}
	}
	/* v6.5 · 정렬 — 기본은 최근에 연 차트가 맨 위 (원장 지시). 📌 상단고정은 늘 먼저 */
	$orders = array(
		'viewed' => 'COALESCE(viewed_at, updated_at) IS NULL, COALESCE(viewed_at, updated_at) DESC, id DESC',
		'visit'  => 'last_visit IS NULL, last_visit DESC, COALESCE(viewed_at, updated_at) DESC, id DESC',
		'chart'  => '(chart_no + 0) ASC, chart_no ASC',
		'name'   => 'pname ASC, (chart_no + 0) ASC',
	);
	$order = 'pin DESC, ' . ( $orders[ $sort ] ?? $orders['viewed'] );
	$sql = "SELECT id, chart_no, pname, cho, pin, updated_at, updated_by, viewed_at, last_visit, addr, mhx, referral, dr, tx_hist FROM $t WHERE " . implode( ' AND ', $where ) . " ORDER BY $order";
	$rows = (array) $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql );
	if ( 'name' === $sort ) {
		/* 이름순은 가나다 먼저, 외국 이름은 그 뒤 ABC (DB 정렬은 영문이 앞에 온다) */
		usort( $rows, function ( $a, $b ) {
			if ( (int) $a->pin !== (int) $b->pin ) { return (int) $b->pin - (int) $a->pin; }
			$ka = preg_match( '/^\s*[가-힣]/u', $a->pname ) ? 0 : 1;
			$kb = preg_match( '/^\s*[가-힣]/u', $b->pname ) ? 0 : 1;
			if ( $ka !== $kb ) { return $ka - $kb; }
			return strcmp( mb_strtolower( trim( $a->pname ) ), mb_strtolower( trim( $b->pname ) ) );
		} );
	}
	return $rows;
}

function md_mc_notes( $q = '' ) {
	global $wpdb;
	$t   = md_mc_t();
	$sql = "SELECT id, title, pin, updated_at, updated_by, body FROM $t WHERE kind = 'note' AND deleted_at IS NULL";
	if ( '' !== $q ) {
		$like = '%' . $wpdb->esc_like( $q ) . '%';
		$sql  = $wpdb->prepare( $sql . ' AND (title LIKE %s OR body LIKE %s)', $like, $like );
	}
	return (array) $wpdb->get_results( $sql . ' ORDER BY updated_at IS NULL, updated_at DESC, id ASC' ); /* v6.4 · 고정 없이 최근 고친 순 */
}

function md_mc_counts() {
	global $wpdb;
	$t = md_mc_t();
	return array(
		'patient' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE kind = 'patient' AND deleted_at IS NULL" ),
		'pin'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE kind = 'patient' AND deleted_at IS NULL AND pin = 1" ),
		'note'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE kind = 'note' AND deleted_at IS NULL" ),
		'trash'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE deleted_at IS NOT NULL" ),
	);
}

/* ============================================================
 * AppSheet 엑셀 가져오기 · 엑셀 내려받기 (관리자)
 * ============================================================ */

/**
 * 구글 시트 「Mini Chart」를 「파일 › 다운로드 › Microsoft Excel」로 받은 파일.
 * 열: id · chart · name · location · mhx · referral · dr · plan · new_treatment · treatment · new_note · note · pin · updated · show_note
 *
 * @param string $mode replace = 전부 지우고 다시 (지운 내용은 기록에 남김) · merge = id 가 같은 것은 덮어쓰고 없는 것은 추가
 */
function md_mc_import( $path, $mode = 'replace' ) {
	global $wpdb;
	if ( ! function_exists( 'md_inv_xlsx_read' ) ) { return new WP_Error( 'mc', '엑셀 읽기 모듈(품목신청)이 꺼져 있습니다.' ); }
	$book = md_inv_xlsx_read( $path );
	if ( is_wp_error( $book ) ) { return $book; }
	$rows = null;
	foreach ( $book as $sheet ) {
		$head = isset( $sheet[0] ) ? array_map( 'trim', $sheet[0] ) : array();
		if ( in_array( 'chart', $head, true ) && in_array( 'name', $head, true ) ) { $rows = md_inv_sheet_assoc( $sheet ); break; }
	}
	if ( null === $rows ) { return new WP_Error( 'mc', '「chart · name」 열이 있는 시트를 찾지 못했습니다. 미니차트 시트를 엑셀로 받은 파일인지 확인해 주세요.' ); }

	$t   = md_mc_t();
	$now = current_time( 'mysql' );
	$res = array( 'patient' => 0, 'note' => 0, 'updated' => 0, 'skipped' => 0 );

	if ( 'replace' === $mode ) {
		$all = $wpdb->get_results( "SELECT * FROM $t", ARRAY_A );
		if ( $all ) {
			$wpdb->insert( md_mc_t( 'log' ), array( 'rec_id' => 0, 'act' => 'backup', 'who' => md_mc_me(), 'at' => $now, 'snap' => wp_json_encode( $all, JSON_UNESCAPED_UNICODE ) ) );
		}
		$wpdb->query( "DELETE FROM $t" );
	}

	foreach ( $rows as $a ) {
		$g = function ( $k ) use ( $a ) { return isset( $a[ $k ] ) ? str_replace( "\r\n", "\n", (string) $a[ $k ] ) : ''; };
		$uid   = trim( $g( 'id' ) );
		$chart = trim( $g( 'chart' ) );
		$name  = trim( $g( 'name' ) );
		if ( '' === $chart && '' === $name ) { $res['skipped']++; continue; }
		/* 이름 칸과 차트번호 칸이 뒤바뀐 줄 (차트번호 칸에 이름, 이름 칸에 번호) */
		if ( ! preg_match( '/^\d+$/', $chart ) && preg_match( '/^\d{3,}$/', $name ) ) { list( $chart, $name ) = array( $name, $chart ); }
		$pin   = '' !== trim( $g( 'pin' ) ) && ! in_array( strtoupper( trim( $g( 'pin' ) ) ), array( 'FALSE', '0', 'N' ), true );
		$upd   = function_exists( 'md_inv_xl_date' ) ? md_inv_xl_date( $g( 'updated' ) ) : '';
		$upd   = $upd ? $upd : null;

		/* 입력용 칸(new_treatment · new_note)에 남아 있던 글은 본 칸 맨 위로 */
		$hist = $g( 'treatment' );
		$memo = $g( 'note' );
		$nt   = trim( $g( 'new_treatment' ) );
		$nn   = trim( $g( 'new_note' ) );
		if ( '' !== $nt && false === mb_strpos( $hist, $nt ) ) { $hist = $nt . ( '' !== trim( $hist ) ? "\n" . $hist : '' ); }
		if ( '' !== $nn && false === mb_strpos( $memo, $nn ) ) { $memo = $nn . ( '' !== trim( $memo ) ? "\n" . $memo : '' ); }

		$mhx = $g( 'mhx' );
		if ( preg_match( '/^\d{5}(\.\d+)?$/', trim( $mhx ) ) && function_exists( 'md_inv_xl_date' ) ) { $mhx = substr( md_inv_xl_date( $mhx ), 0, 10 ); }

		if ( preg_match( '/^\d+$/', $chart ) ) {
			$row = array(
				'kind' => 'patient', 'chart_no' => $chart, 'pname' => mb_substr( $name, 0, 250 ), 'cho' => md_mc_cho( $name ),
				'addr' => $g( 'location' ), 'mhx' => $mhx, 'referral' => $g( 'referral' ), 'dr' => $g( 'dr' ),
				'tx_plan' => $g( 'plan' ), 'tx_hist' => $hist, 'memo' => $memo, 'title' => '', 'body' => '', 'last_visit' => md_mc_last_visit( $hist ),
			);
		} else {
			/* 팀 노트 — 칸마다 나눠 적던 것을 한 본문으로 (칸 사이는 빈 줄) */
			$title = $chart;
			$parts = array();
			foreach ( array( 'name', 'location', 'mhx', 'referral', 'dr', 'plan', 'treatment', 'note' ) as $k ) {
				$v = trim( $g( $k ) );
				if ( 'name' === $k && '개인노트' === $v ) { $title .= ' · 개인노트'; continue; }
				if ( md_mc_blank( $v ) || '/' === $v ) { continue; }
				$parts[] = $v;
			}
			$row = array( 'kind' => 'note', 'title' => mb_substr( $title, 0, 250 ), 'cho' => md_mc_cho( $title ), 'body' => implode( "\n\n", $parts ),
				'chart_no' => '', 'pname' => '', 'addr' => '', 'mhx' => '', 'referral' => '', 'dr' => '', 'tx_plan' => '', 'tx_hist' => '', 'memo' => '' );
		}
		$row['uid']        = mb_substr( $uid, 0, 40 );
		$row['pin']        = $pin && 'patient' === $row['kind'] ? 1 : 0; /* v6.4 · 노트는 고정하지 않는다 */
		$row['updated_at'] = $upd;
		$row['updated_by'] = $upd ? 'AppSheet' : '';
		$row['deleted_at'] = null;

		$ex = ( 'merge' === $mode && '' !== $uid ) ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE uid = %s LIMIT 1", $uid ) ) : null;
		if ( $ex ) {
			$row['rev'] = (int) $ex->rev + 1;
			$wpdb->update( $t, $row, array( 'id' => (int) $ex->id ) );
			md_mc_log( $ex->id, 'import', $ex );
			$res['updated']++;
		} else {
			$row['created_at'] = $now;
			$row['rev']        = 1;
			$wpdb->insert( $t, $row );
			$res[ $row['kind'] ]++;
		}
	}
	update_option( 'md_mc_imported', array( 'at' => $now, 'who' => md_mc_me(), 'mode' => $mode, 'res' => $res ), false );
	return $res;
}

function md_mc_export_xlsx() {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . md_mc_t() . " WHERE deleted_at IS NULL ORDER BY kind DESC, pin DESC, updated_at DESC, id ASC" );
	$f    = md_mc_fields();
	$head = array( '구분', '차트번호 · 노트 제목', '성명' );
	foreach ( array( 'addr', 'mhx', 'referral', 'dr', 'tx_plan', 'tx_hist', 'memo' ) as $k ) { $head[] = $f[ $k ][0]; }
	array_push( $head, '노트 본문', '상단고정', '마지막 수정', '수정한 사람', 'AppSheet id' );
	$data = array();
	foreach ( $rows as $r ) {
		$note   = 'note' === $r->kind;
		$data[] = array(
			$note ? '노트' : '환자', $note ? $r->title : $r->chart_no, $r->pname,
			(string) $r->addr, (string) $r->mhx, (string) $r->referral, (string) $r->dr, (string) $r->tx_plan, (string) $r->tx_hist, (string) $r->memo,
			(string) $r->body, $r->pin ? '📌' : '', (string) $r->updated_at, (string) $r->updated_by, (string) $r->uid,
		);
	}
	return md_inv_xlsx( array( array(
		'title' => '미니차트',
		'head'  => $head,
		'rows'  => $data,
		'width' => array( 6, 14, 12, 20, 30, 24, 18, 30, 50, 50, 50, 6, 18, 12, 10 ),
	) ) );
}

function md_mc_handle_download() {
	if ( empty( $_GET['md_mc_dl'] ) ) { return; }
	if ( ! md_mc_is_owner() ) { wp_die( '원장 계정만 내려받을 수 있습니다.', '권한 없음', array( 'response' => 403 ) ); }
	if ( ! isset( $_GET['_n'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_n'] ), 'md_mc_dl' ) ) { wp_die( '링크가 만료되었습니다. 다시 눌러 주세요.' ); }
	if ( ! function_exists( 'md_inv_xlsx' ) ) { wp_die( '엑셀 모듈(품목신청)이 꺼져 있습니다.' ); }
	$bin  = md_mc_export_xlsx();
	$name = '미니차트_' . current_time( 'Ymd_Hi' ) . '.xlsx';
	md_mc_log( 0, 'download' );
	nocache_headers();
	header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	header( 'Content-Disposition: attachment; filename="minichart.xlsx"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
	header( 'Content-Length: ' . strlen( $bin ) );
	echo $bin; // phpcs:ignore
	exit;
}
add_action( 'template_redirect', 'md_mc_handle_download', 2 );

/* ============================================================
 * 폼 처리
 * ============================================================ */

function md_mc_handle_post() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['md_mc_action'] ) ) { return; }
	if ( ! md_mc_can_use() ) { return; }
	$action = sanitize_key( wp_unslash( $_POST['md_mc_action'] ) );
	if ( ! isset( $_POST['md_mc_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_mc_nonce'] ), 'md_mc_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 새로고침한 뒤 다시 시도해 주세요.' );
	}
	$id   = isset( $_POST['mid'] ) ? (int) $_POST['mid'] : 0;
	$post = wp_unslash( $_POST );
	$err  = function ( $msg, $args ) { return md_mc_url( array_merge( $args, array( 'mcerr' => $msg ) ) ); };

	/* v9.2 · 빠른 저장 — 화면이 fetch 로 보내면(md_fast) 페이지를 다시 그리지 않고 바뀐 부분만 돌려준다 (원장 지시: 라운지 속도) */
	$fast = ! empty( $_POST['md_fast'] ) && in_array( $action, array( 'dwhide', 'dwedit', 'dwunhide', 'plan', 'add', 'field' ), true );

	switch ( $action ) {
		case 'save':
			$kind = 'note' === ( $post['kind'] ?? '' ) ? 'note' : 'patient';
			$data = $post;
			$data['pin'] = ! empty( $post['pin'] );
			/* v6.4 · 담당의는 칸마다 고른 원장님 + 그 밖의 줄 */
			if ( 'patient' === $kind && isset( $post['dr_main'] ) ) {
				$data['dr'] = md_mc_dr_compose( $post['dr_main'], $post['dr_dept'] ?? array(), $post['dr_doc'] ?? array(), $post['dr_extra'] ?? '', $post['dr_main_dept'] ?? '' );
			}
			/* v6.8 · 「해당없음」 체크 → 그 칸은 「해당없음」 */
			if ( 'patient' === $kind ) {
				foreach ( array( 'addr', 'mhx', 'referral' ) as $nk ) {
					if ( ! empty( $post['na'][ $nk ] ) ) { $data[ $nk ] = MD_MC_NA; }
				}
			}
			/* v6.4 · 「치료입력 · 참고사항입력 (날짜없이)」 — 오늘 날짜를 붙여 맨 위로 (AppSheet 와 같은 방식) */
			if ( 'patient' === $kind ) {
				foreach ( array( 'tx_new' => 'tx_hist', 'memo_new' => 'memo' ) as $in => $to ) {
					$line = trim( sanitize_textarea_field( (string) ( $post[ $in ] ?? '' ) ) );
					if ( '' === $line ) { continue; }
					$old = (string) ( $data[ $to ] ?? '' );
					$data[ $to ] = md_mc_yymmdd() . ': ' . $line . ( '' !== trim( $old ) ? "\n" . $old : '' );
					$data[ $in ] = ''; /* 저장이 막혀 폼으로 돌아가도 두 번 붙지 않게 */
				}
			}
			$res  = md_mc_save( $id, $kind, $data, (int) ( $post['rev'] ?? 0 ) );
			if ( is_wp_error( $res ) ) {
				/* 적은 내용은 잃지 않게 잠깐 보관했다가 폼에 다시 채운다 */
				set_transient( 'md_mc_draft_' . get_current_user_id() . '_' . $id, $data, HOUR_IN_SECONDS );
				$args = array( 'mv' => 'edit', 'mid' => $id ?: '', 'mk' => $kind, 'draft' => 1 );
				if ( 'mc_dup' === $res->get_error_code() ) { $args['dup'] = (int) $res->get_error_data(); }
				if ( 'mc_conflict' === $res->get_error_code() ) { $args['conflict'] = 1; }
				$back = $err( $res->get_error_message(), $args );
			} else {
				delete_transient( 'md_mc_draft_' . get_current_user_id() . '_' . $id );
				if ( ! $id && 'patient' === $kind ) { do_action( 'md_mc_created', (int) $res ); } /* v8.4 · 덴트웹 진료기록을 1분 안에 받아 오게 */
				$back = md_mc_url( array( 'mv' => 'note' === $kind ? 'note' : 'p', 'mid' => $res, 'saved' => 1 ) );
			}
			break;

		case 'add':
			$field = sanitize_key( $post['field'] ?? '' );
			$res   = md_mc_add_line( $id, $field, $post['text'] ?? '', sanitize_text_field( $post['mcday'] ?? '' ) );
			$rec   = md_mc_get( $id );
			$view  = $rec && 'note' === $rec->kind ? 'note' : 'p';
			$back  = is_wp_error( $res ) ? $err( $res->get_error_message(), array( 'mv' => $view, 'mid' => $id ) ) : md_mc_url( array( 'mv' => $view, 'mid' => $id ) ) . '#f-' . $field;
			break;

		case 'dwhide':   /* v8.7 · 덴트웹에서 온 진료기록 한 줄 — 미니차트에서만 지움(숨김) */
		case 'dwedit':   /* v8.7 · 고침 = 그 줄은 숨기고 고친 내용을 직접 적은 기록으로 */
		case 'dwunhide': /* v8.7 · 숨긴 덴트웹 기록 모두 다시 보이기 */
			$cur = md_mc_get( $id );
			if ( ! $cur || 'patient' !== $cur->kind ) { $back = md_mc_url(); break; }
			$hide = json_decode( (string) $cur->dw_hide, true );
			$hide = is_array( $hide ) ? $hide : array();
			$key  = sanitize_text_field( (string) ( $post['dwkey'] ?? '' ) );
			if ( 'dwunhide' === $action ) {
				$pick = isset( $post['dwkeys'] ) && empty( $post['all'] ) ? array_map( 'sanitize_text_field', (array) $post['dwkeys'] ) : null; /* v9.2 · 고른 것만 */
				if ( null === $pick ) { $hide = array(); } else { foreach ( $pick as $pk ) { unset( $hide[ $pk ] ); } }
			}
			elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}:[0-9a-f]{8}$/', $key ) ) { $hide[ $key ] = current_time( 'mysql' ); }
			global $wpdb;
			$wpdb->update( md_mc_t(), array( 'dw_hide' => $hide ? wp_json_encode( $hide ) : null, 'rev' => (int) $cur->rev + 1, 'updated_at' => current_time( 'mysql' ), 'updated_by' => md_mc_me() ), array( 'id' => (int) $id ) );
			md_mc_log( $id, 'edit', $cur );
			if ( 'dwedit' === $action && '' !== trim( (string) ( $post['text'] ?? '' ) ) ) {
				md_mc_add_line( $id, 'tx_hist', $post['text'], substr( $key, 0, 10 ) );
			}
			$back = md_mc_url( array( 'mv' => 'p', 'mid' => $id ) ) . '#f-tx_hist';
			break;

		case 'field': /* v9.19 · 차트 화면에서 칸 하나(병력 · 지역 · 담당의 · 내원경로)만 바로 고치기 */
			$cur = md_mc_get( $id );
			$fld = sanitize_key( (string) ( $post['field'] ?? '' ) );
			if ( ! $cur || 'patient' !== $cur->kind || ! in_array( $fld, array( 'mhx', 'addr', 'referral', 'dr', 'memo' ), true ) ) { $back = md_mc_url(); break; }
			if ( (int) ( $post['rev'] ?? 0 ) !== (int) $cur->rev ) { $back = $err( '그 사이 다른 분이 이 차트를 고쳤습니다. 최신 내용을 보고 다시 고쳐 주세요.', array( 'mv' => 'p', 'mid' => $id ) ); break; }
			if ( 'dr' === $fld ) {
				$val = md_mc_dr_compose( $post['dr_main'] ?? '', $post['dr_dept'] ?? array(), $post['dr_doc'] ?? array(), $post['dr_extra'] ?? '', $post['dr_main_dept'] ?? '' );
				if ( '' === trim( (string) $val ) ) { $back = $err( '담당의를 한 분 이상 골라 주세요.', array( 'mv' => 'p', 'mid' => $id ) ); break; }
			} else {
				$val = ! empty( $post['na'][ $fld ] ) ? MD_MC_NA : ( 'memo' === $fld ? trim( sanitize_textarea_field( (string) ( $post['memo'] ?? '' ) ) ) : sanitize_textarea_field( (string) ( $post[ $fld ] ?? '' ) ) );
				if ( 'addr' === $fld && '' === trim( $val ) ) { $back = $err( '지역을 적어 주세요.', array( 'mv' => 'p', 'mid' => $id ) ); break; }
			}
			if ( (string) $val !== (string) $cur->$fld ) {
				global $wpdb;
				$ok = $wpdb->update( md_mc_t(), array( $fld => $val, 'rev' => (int) $cur->rev + 1, 'updated_at' => current_time( 'mysql' ), 'updated_by' => md_mc_me() ), array( 'id' => (int) $id, 'rev' => (int) $cur->rev ) );
				if ( $ok ) { md_mc_log( $id, 'edit', $cur ); }
			}
			$back = md_mc_url( array( 'mv' => 'p', 'mid' => $id, 'saved' => 1 ) ) . '#f-' . $fld;
			break;

		case 'plan': /* v8.6 · 차트 화면에서 치료계획만 바로 고치기 */
			$cur = md_mc_get( $id );
			if ( ! $cur || 'patient' !== $cur->kind ) { $back = md_mc_url(); break; }
			if ( (int) ( $post['rev'] ?? 0 ) !== (int) $cur->rev ) { $back = $err( '그 사이 다른 분이 이 차트를 고쳤습니다. 최신 내용을 보고 다시 고쳐 주세요.', array( 'mv' => 'p', 'mid' => $id ) ); break; }
			$plan = sanitize_textarea_field( (string) ( $post['tx_plan'] ?? '' ) );
			if ( $plan !== (string) $cur->tx_plan ) {
				global $wpdb;
				$ok = $wpdb->update( md_mc_t(), array( 'tx_plan' => $plan, 'rev' => (int) $cur->rev + 1, 'updated_at' => current_time( 'mysql' ), 'updated_by' => md_mc_me() ), array( 'id' => (int) $id, 'rev' => (int) $cur->rev ) );
				if ( $ok ) { md_mc_log( $id, 'edit', $cur ); }
			}
			$back = md_mc_url( array( 'mv' => 'p', 'mid' => $id, 'saved' => 1 ) ) . '#f-tx_plan';
			break;

		case 'pin':
			md_mc_set_pin( $id, ! empty( $post['on'] ) );
			$rec  = md_mc_get( $id );
			$back = md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'note' : 'p', 'mid' => $id ) );
			break;

		case 'delete':
			if ( ! md_mc_can_manage() ) { $back = $err( '삭제는 라운지 관리자가 합니다.', array( 'mv' => 'p', 'mid' => $id ) ); break; }
			$rec = md_mc_get( $id );
			md_mc_trash( $id );
			$back = md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'notes' : '', 'gone' => $id ) );
			break;

		case 'restore':
			if ( ! md_mc_can_manage() ) { $back = md_mc_url(); break; }
			$res  = md_mc_untrash( $id );
			$rec  = md_mc_get( $id, true );
			$back = is_wp_error( $res ) ? $err( $res->get_error_message(), array( 'mv' => 'trash' ) ) : md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'note' : 'p', 'mid' => $id ) );
			break;

		case 'revert':
			if ( ! md_mc_can_manage() ) { $back = $err( '되돌리기는 라운지 관리자가 합니다.', array( 'mv' => 'log', 'mid' => $id ) ); break; }
			$res  = md_mc_revert( (int) ( $post['lid'] ?? 0 ) );
			$rec  = is_wp_error( $res ) ? null : md_mc_get( $res );
			$back = is_wp_error( $res ) ? $err( $res->get_error_message(), array( 'mv' => 'log', 'mid' => $id ) ) : md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'note' : 'p', 'mid' => $res, 'reverted' => 1 ) );
			break;

		case 'settings': /* v6.4 · 담당의 목록 · 칸 (관리자) */
			if ( md_mc_can_manage() ) {
				$clean = function ( $txt ) {
					$out = array();
					foreach ( preg_split( '/\r\n|\r|\n|,/', (string) $txt ) as $v ) {
						$v = mb_substr( trim( sanitize_text_field( $v ) ), 0, 30 );
						if ( '' !== $v && ! in_array( $v, $out, true ) ) { $out[] = $v; }
					}
					return $out;
				};
				$s            = md_mc_settings();
				$s['doctors'] = $clean( $post['doctors'] ?? '' );
				$roles        = $clean( $post['roles'] ?? '' );
				$s['roles']   = $roles ? $roles : array( '임플란트', '보철', '교정' );
				/* v8.4 · 병력 · 내원경로 눌러서 넣는 목록 (원장 지시) */
				foreach ( array( 'mhx_chips', 'ref_chips' ) as $ck ) {
					if ( isset( $post[ $ck ] ) ) { $v = $clean( $post[ $ck ] ); if ( $v ) { $s[ $ck ] = $v; } else { unset( $s[ $ck ] ); } }
				}
				if ( ! empty( $post['alert_reset'] ) ) { unset( $s['alert_words'] ); }
				elseif ( isset( $post['alert_words'] ) ) { $s['alert_words'] = $clean( $post['alert_words'] ); }
				update_option( 'md_mc_settings', $s, false );
			}
			$back = md_mc_url( array( 'mv' => 'settings', 'saved' => 1 ) );
			break;

		case 'purge':
			if ( md_mc_is_owner() ) {
				global $wpdb;
				$wpdb->query( 'DELETE FROM ' . md_mc_t() . ' WHERE deleted_at IS NOT NULL' );
				md_mc_log( 0, 'purge' );
			}
			$back = md_mc_url( array( 'mv' => 'settings' === ( $post['from'] ?? '' ) ? 'settings' : 'trash', 'purged' => 1 ) );
			break;

		case 'purge_one': /* v6.5 · 휴지통에서 한 건만 영구 삭제 (v6.8 · 원장 계정) */
			if ( md_mc_is_owner() ) {
				global $wpdb;
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_mc_t() . ' WHERE id = %d AND deleted_at IS NOT NULL', $id ) );
			}
			$back = md_mc_url( array( 'mv' => 'trash' ) );
			break;

		case 'import':
			if ( ! md_mc_is_owner() ) { $back = md_mc_url(); break; }
			$f = $_FILES['file'] ?? null;
			if ( ! $f || UPLOAD_ERR_OK !== (int) $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) {
				$back = $err( '파일을 고르지 않았거나 올리지 못했습니다.', array( 'mv' => 'admin' ) );
				break;
			}
			$mode = 'merge' === ( $post['mode'] ?? '' ) ? 'merge' : 'replace';
			$res  = md_mc_import( $f['tmp_name'], $mode );
			$back = is_wp_error( $res ) ? $err( $res->get_error_message(), array( 'mv' => 'admin' ) ) : md_mc_url( array( 'mv' => 'admin', 'imported' => 1 ) );
			break;

		default:
			$back = md_mc_url();
	}
	if ( $fast ) { md_mc_fast_reply( $action, $id, $back ); }
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_mc_handle_post', 1 );

/** v9.2 · 빠른 저장의 대답 — 오류면 ok=false + 메시지, 아니면 바뀐 칸의 HTML */
function md_mc_fast_reply( $action, $id, $back ) {
	$q = array();
	wp_parse_str( (string) wp_parse_url( $back, PHP_URL_QUERY ), $q );
	if ( ! empty( $q['mcerr'] ) ) { wp_send_json( array( 'ok' => false, 'msg' => (string) $q['mcerr'] ) ); }
	$r = md_mc_get( $id );
	if ( ! $r ) { wp_send_json( array( 'ok' => false, 'msg' => '차트를 찾을 수 없습니다.' ) ); }
	$out = array( 'ok' => true, 'rev' => (int) $r->rev );
	$field = in_array( $action, array( 'add', 'field' ), true ) ? sanitize_key( wp_unslash( $_POST['field'] ?? '' ) ) : '';
	if ( 'field' === $action ) {
		/* v9.19 · 그 칸의 보기 부분만 — 담당의는 고르는 칸을 다시 그려야 해서 화면이 새로 불러온다 */
		$d  = function_exists( 'md_mc_dw_get' ) ? md_mc_dw_get( $r->chart_no ) : null;
		$vw = md_mc_inline_view( $r, $d, $field );
		$out += array( 'target' => $field, 'html' => $vw['html'], 'head' => $vw['head'], 'cls' => $vw['cls'], 'value' => md_mc_is_na( $r->$field ) ? '' : (string) $r->$field, 'na' => md_mc_is_na( $r->$field ), 'reload' => 'dr' === $field );
		wp_send_json( $out );
	}
	if ( in_array( $action, array( 'dwhide', 'dwedit', 'dwunhide' ), true ) || 'tx_hist' === $field ) {
		$d = 'patient' === $r->kind && function_exists( 'md_mc_dw_get' ) ? md_mc_dw_get( $r->chart_no ) : null;
		$out['target'] = 'tx_hist';
		$out['html'] = function_exists( 'md_mc_dw_timeline' ) && 'patient' === $r->kind ? md_mc_dw_timeline( $r, $d ) : md_mc_text( $r->tx_hist );
	} elseif ( 'add' === $action && 'note' === $r->kind ) {
		/* v9.10 · 팀 노트(팀 피드 등)에 한 줄 추가 — 본문을 바로 돌려준다. (전에는 reload 로 답해 화면이 폼을 다시 보내 두 번 들어갔다) */
		$out['target'] = 'body';
		$out['html'] = md_mc_text( $r->body );
	} elseif ( 'add' === $action && in_array( $field, array_keys( md_mc_fields() ), true ) ) {
		$out['target'] = $field;
		$out['html'] = md_mc_text( $r->$field );
		$out['value'] = (string) $r->$field; /* v9.20 · 같은 칸의 「눌러서 고치기」 폼도 최신으로 */
	} elseif ( 'add' === $action ) {
		/* 이미 저장됐다 — 화면은 그 주소로 옮겨 가기만 한다 (다시 보내면 두 번 저장됨) */
		wp_send_json( array( 'ok' => true, 'redirect' => $back ) );
	}
	wp_send_json( $out );
}

/* ============================================================
 * 화면
 * ============================================================ */

function md_mc_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'minichart' !== md_sup_current_app() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/minichart.css' ) ) {
		wp_enqueue_style( 'md-minichart', $uri . '/assets/css/minichart.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/minichart.css' ) );
	}
	if ( file_exists( $dir . '/assets/js/minichart.js' ) ) {
		wp_enqueue_script( 'md-minichart', $uri . '/assets/js/minichart.js', array(), filemtime( $dir . '/assets/js/minichart.js' ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'md_mc_enqueue', 40 );

/** v9.8 · 차트 화면 위 찾기 칸 — 차트번호 · 이름 · 초성으로 바로 찾기 (GET ?app=minichart&md_mc_find=검색어 · JSON, 12명까지) */
function md_mc_find_json() {
	if ( ! isset( $_GET['md_mc_find'] ) ) { return; }
	if ( ! md_mc_can_use() ) { wp_send_json( array( 'ok' => false ), 403 ); }
	global $wpdb;
	$q = trim( sanitize_text_field( wp_unslash( $_GET['md_mc_find'] ) ) );
	$k = preg_replace( '/\s+/u', '', $q );
	if ( '' === $k ) { wp_send_json( array( 'ok' => true, 'rows' => array() ) ); }
	$t    = md_mc_t();
	$like = '%' . $wpdb->esc_like( $k ) . '%';
	if ( ! empty( $_GET['deep'] ) && ! md_mc_is_cho( $q ) && mb_strlen( $k ) >= 2 ) { md_mc_find_deep_json( $q, $k ); }
	if ( md_mc_is_cho( $q ) ) {
		$cond = $wpdb->prepare( 'cho LIKE %s', $like );
	} else {
		$cond = $wpdb->prepare( "(chart_no LIKE %s OR REPLACE(pname, ' ', '') LIKE %s OR cho LIKE %s)", $like, $like, '%' . $wpdb->esc_like( mb_strtolower( $k ) ) . '%' );
	}
	/* 차트번호가 딱 맞거나 이름이 그 글자로 시작하면 먼저 */
	$rows = (array) $wpdb->get_results( $wpdb->prepare(
		"SELECT id, chart_no, pname, pin, mhx, last_visit FROM $t WHERE kind = 'patient' AND deleted_at IS NULL AND $cond
		 ORDER BY (chart_no = %s) DESC, (pname LIKE %s) DESC, pin DESC, COALESCE(viewed_at, updated_at) DESC, id DESC LIMIT 12",
		$k, $wpdb->esc_like( $k ) . '%'
	) );
	$out = array();
	foreach ( $rows as $r ) {
		$al    = md_mc_alerts( $r->mhx );
		$out[] = array(
			'chart' => $r->chart_no,
			'name'  => $r->pname,
			'pin'   => (int) $r->pin,
			'alert' => $al ? $al[0] : '',
			'last'  => $r->last_visit ? md_mc_short_date( $r->last_visit ) : '',
			'url'   => md_mc_url( array( 'mv' => 'p', 'mid' => $r->id ) ),
		);
	}
	wp_send_json( array( 'ok' => true, 'rows' => $out ) );
}
add_action( 'template_redirect', 'md_mc_find_json', 2 );

/** v9.10 · 목록에서 글자를 치면 내용까지 같이 찾는다 (원장 지시 — 「점액낭종」을 치면 진료기록에 있는 환자도).
 *  차트번호 · 이름으로 이미 맞는 환자는 화면이 바로 보여 주므로 빼고, 내용에서만 맞는 환자를 맞은 줄과 함께 30명까지 */
function md_mc_find_deep_json( $q, $k ) {
	global $wpdb;
	$t    = md_mc_t();
	$cols = array( 'tx_hist' => '진료기록', 'memo' => '참고사항', 'tx_plan' => '치료계획', 'mhx' => '병력', 'referral' => '내원경로', 'addr' => '지역', 'dr' => '담당의' );
	$likeQ = '%' . $wpdb->esc_like( $q ) . '%';
	$likeK = '%' . $wpdb->esc_like( $k ) . '%';
	$or   = array(); $args = array();
	foreach ( array_keys( $cols ) as $c ) { $or[] = "$c LIKE %s"; $args[] = $likeQ; }
	$sql  = "SELECT id, chart_no, pname, pin, last_visit, " . implode( ', ', array_keys( $cols ) ) . " FROM $t WHERE kind = 'patient' AND deleted_at IS NULL AND (" . implode( ' OR ', $or ) . ")
		 AND NOT (chart_no LIKE %s OR REPLACE(pname, ' ', '') LIKE %s) ORDER BY pin DESC, COALESCE(viewed_at, updated_at) DESC, id DESC LIMIT 30";
	$args[] = $likeK; $args[] = $likeK;
	$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	$out  = array();
	foreach ( $rows as $r ) {
		$hit = '';
		foreach ( $cols as $c => $label ) {
			$v = (string) $r->$c;
			if ( '' === $v || false === mb_stripos( $v, $q ) ) { continue; }
			$line = '';
			foreach ( preg_split( '/
||
/', $v ) as $ln ) { if ( false !== mb_stripos( $ln, $q ) ) { $line = trim( $ln ); break; } }
			$pos = mb_stripos( $line, $q );
			if ( false !== $pos && $pos > 24 ) { $line = '…' . mb_substr( $line, $pos - 20 ); }
			if ( mb_strlen( $line ) > 70 ) { $line = mb_substr( $line, 0, 68 ) . '…'; }
			$hit = $label . ' · ' . $line;
			break;
		}
		$out[] = array( 'chart' => $r->chart_no, 'name' => $r->pname, 'pin' => (int) $r->pin, 'hit' => $hit, 'last' => $r->last_visit ? md_mc_short_date( $r->last_visit ) : '', 'url' => md_mc_url( array( 'mv' => 'p', 'mid' => $r->id ) ) );
	}
	wp_send_json( array( 'ok' => true, 'rows' => $out, 'q' => $q ) );
}

/* v10.0 · 휴대폰 홈 화면에 「미니차트」 아이콘 (재료실과 같은 방식 · 원장 지시) */
function md_mc_manifest() {
	if ( empty( $_GET['md_mc_manifest'] ) ) { return; }
	$icon = get_stylesheet_directory_uri() . '/assets/img/';
	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=UTF-8' );
	echo wp_json_encode( array(
		'id'               => '/?md_app=minichart',
		'name'             => '문치과병원 미니차트',
		'short_name'       => '미니차트',
		'start_url'        => md_mc_url(),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'background_color' => '#F6F1EA',
		'theme_color'      => '#8B6A4E',
		'lang'             => 'ko',
		'icons'            => array(
			array( 'src' => $icon . 'mc-icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable' ),
			array( 'src' => $icon . 'mc-icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable' ),
		),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}
add_action( 'init', 'md_mc_manifest', 1 );

function md_mc_head_app() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'minichart' !== md_sup_current_app() ) { return; }
	echo '<link rel="manifest" href="' . esc_url( add_query_arg( 'md_mc_manifest', '1', home_url( '/' ) ) ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( get_stylesheet_directory_uri() . '/assets/img/mc-icon-180.png' ) . '">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes"><meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="미니차트">' . "\n";
}
add_action( 'wp_head', 'md_mc_head_app', 2 );

/** 환자 목록 위 「홈 화면에 추가」 — 휴대폰이거나 브라우저가 설치를 받아 줄 때만, 앱으로 열었으면 안 보임 (✕ 로 닫으면 그 기기에서는 안 나옴) */
function md_mc_install_help() {
	?>
	<div class="mc-install" id="mc-install" hidden>
		<b>📱 홈 화면에 「미니차트」 아이콘 만들기</b>
		<button type="button" class="mds-btn mds-btn--fill mc-install__btn" id="mc-install-btn" hidden>홈 화면에 추가</button>
		<span class="mc-install__ios" hidden>아이폰: 아래 <b>공유 버튼 ⬆︎</b> → <b>「홈 화면에 추가」</b></span>
		<span class="mc-install__and" hidden>안드로이드: 오른쪽 위 <b>⋮</b> → <b>「홈 화면에 추가」</b></span>
		<button type="button" class="mc-install__x" id="mc-install-x" aria-label="닫기">×</button>
	</div>
	<?php
}

/** v9.8 · 차트를 보다가 다른 환자 찾기 — 글자를 치면 아래에 바로 뜨고, Enter 는 목록에서 찾기(내용까지) */
function md_mc_render_jump() {
	?>
	<div class="mc-findbar mc-jump" data-mc-jump="<?php echo esc_url( md_mc_url() ); ?>">
		<form method="get" class="mc-search" action="<?php echo esc_url( md_mc_url() ); ?>" role="search">
			<?php md_sup_app_field(); ?>
			<input type="search" name="mq" id="mc-q" placeholder="다른 환자 찾기 — 차트번호 · 이름 · 초성" autocomplete="off" enterkeyhint="search" aria-label="다른 환자 찾기" aria-controls="mc-jump-list" aria-autocomplete="list">
			<button type="submit" class="mds-btn mc-search__btn" title="병력 · 치료이력 · 참고사항까지 찾기">내용까지</button>
		</form>
		<ul class="mc-jump__list" id="mc-jump-list" role="listbox" hidden></ul>
	</div>
	<?php
}


function md_mc_nonce_fields( $action, $id = 0 ) {
	echo '<input type="hidden" name="md_mc_action" value="' . esc_attr( $action ) . '">';
	echo '<input type="hidden" name="md_mc_nonce" value="' . esc_attr( wp_create_nonce( 'md_mc_' . $action ) ) . '">';
	if ( $id ) { echo '<input type="hidden" name="mid" value="' . (int) $id . '">'; }
}

function md_mc_render() {
	if ( ! md_mc_can_use() ) { echo '<div class="mds-notice mds-notice--warn">미니차트는 지금 관리자만 볼 수 있습니다.</div>'; return; } /* v6.9 */
	if ( (int) get_option( 'md_mc_schema', 0 ) < MD_MC_SCHEMA ) { md_mc_maybe_install(); }
	$mv  = isset( $_GET['mv'] ) ? sanitize_key( wp_unslash( $_GET['mv'] ) ) : '';
	$mid = isset( $_GET['mid'] ) ? (int) $_GET['mid'] : 0;

	echo '<div class="mc">';
	if ( isset( $_GET['mcerr'] ) ) {
		echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['mcerr'] ) ) . '</div>';
	}
	if ( 'tablet' === $mv ) { $mv = 'p'; } /* v6.5 · 태블릿 보기 없앰 — 옛 주소는 차트로 */
	if ( 'doctors' === $mv ) { $mv = 'settings'; }
	md_mc_render_nav( $mv );

	switch ( $mv ) {
		case 'p':       md_mc_render_patient( $mid ); break;
		case 'edit':    md_mc_render_edit( $mid, isset( $_GET['mk'] ) && 'note' === $_GET['mk'] ? 'note' : 'patient' ); break;
		case 'notes':   md_mc_render_notes(); break;
		case 'note':    md_mc_render_note( $mid ); break;
		case 'log':     md_mc_render_log( $mid ); break;
		case 'trash':   md_mc_can_manage() ? md_mc_render_trash() : md_mc_render_list(); break;
		case 'admin':   md_mc_is_owner() ? md_mc_render_admin() : md_mc_render_list(); break;
		case 'settings': md_mc_can_manage() ? md_mc_render_settings() : md_mc_render_list(); break;
		default:        md_mc_render_list();
	}
	echo '</div>';
}

/** 메뉴 — 휴대폰에서는 옆으로 밀고, 「＋」 는 오른쪽 아래 둥근 버튼 */
function md_mc_render_nav( $mv ) {
	$c    = md_mc_counts();
	$tabs = array(
		''      => array( '환자', $c['patient'] ),
		'notes' => array( '팀 노트', $c['note'] ),
	);
	if ( md_mc_can_manage() ) {
		$tabs['trash']    = array( '휴지통', $c['trash'] ); /* v6.8 · 라운지 관리자 이상 */
		$tabs['settings'] = array( '설정', null );
	}
	if ( md_mc_is_owner() ) { $tabs['admin'] = array( 'Import&Export', null ); } /* v6.8 · 원장 계정만 */
	$on = in_array( $mv, array( 'p', 'edit', 'log' ), true ) ? '' : ( 'note' === $mv ? 'notes' : $mv );
	if ( 'edit' === $mv && isset( $_GET['mk'] ) && 'note' === $_GET['mk'] ) { $on = 'notes'; }
	$is_note = 'notes' === $on;
	$new_url = md_mc_url( array( 'mv' => 'edit', 'mk' => $is_note ? 'note' : '' ) );
	?>
	<nav class="mc-nav" aria-label="미니차트 메뉴">
		<div class="mc-nav__tabs">
			<?php foreach ( $tabs as $k => $tb ) : ?>
				<a class="mc-nav__a<?php echo $on === $k ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_mc_url( array( 'mv' => $k ) ) ); ?>"><?php echo esc_html( $tb[0] ); ?><?php if ( null !== $tb[1] ) : ?> <b><?php echo (int) $tb[1]; ?></b><?php endif; ?></a>
			<?php endforeach; ?>
		</div>
		<?php if ( in_array( $mv, array( '', 'notes' ), true ) ) : /* 목록 · 팀 노트에서만 (차트 화면에서 내용을 가리지 않게) */ ?>
			<a class="mds-btn mds-btn--fill mc-nav__new" href="<?php echo esc_url( $new_url ); ?>" aria-label="<?php echo $is_note ? '새 노트' : '새 환자'; ?>"><span aria-hidden="true">＋</span><em><?php echo $is_note ? ' 새 노트' : ' 새 환자'; ?></em></a>
		<?php endif; ?>
	</nav>
	<?php
}

function md_mc_chip( $label, $url, $on ) {
	echo '<a class="mc-chip' . ( $on ? ' is-on' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
}

function md_mc_render_list() {
	$q      = isset( $_GET['mq'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['mq'] ) ) ) : '';
	$filter = isset( $_GET['mf'] ) ? sanitize_key( wp_unslash( $_GET['mf'] ) ) : '';
	if ( ! in_array( $filter, array( '', 'pin' ), true ) ) { $filter = ''; }
	$doc    = isset( $_GET['md'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['md'] ) ) ) : '';
	$sorts  = array( 'viewed' => '최근 본 순', 'visit' => '최근 내원순', 'chart' => '차트번호순', 'name' => '이름순' );
	$sort   = isset( $_GET['ms'] ) ? sanitize_key( wp_unslash( $_GET['ms'] ) ) : '';
	if ( ! isset( $sorts[ $sort ] ) ) { $sort = 'viewed'; }
	$ms     = 'viewed' === $sort ? '' : $sort;
	$rows   = md_mc_patients( $q, $filter, $doc, $sort );
	$c      = md_mc_counts();

	if ( isset( $_GET['gone'] ) ) {
		echo '<div class="mds-notice mds-notice--ok">휴지통으로 옮겼습니다. <a href="' . esc_url( md_mc_url( array( 'mv' => 'trash' ) ) ) . '">휴지통에서 되살리기</a></div>';
	}
	if ( ! $c['patient'] && ! $c['note'] ) {
		echo '<div class="mds-card mc-empty-start"><p>아직 미니차트 자료가 없습니다.</p>'
			. ( md_mc_is_owner() ? '<p><a class="mds-btn mds-btn--fill" href="' . esc_url( md_mc_url( array( 'mv' => 'admin' ) ) ) . '">Import (AppSheet 자료 가져오기)</a></p>' : '<p>원장 계정으로 AppSheet 자료를 가져오면 여기에 보입니다.</p>' )
			. '</div>';
	}
	?>
	<div class="mc-findbar">
		<form method="get" class="mc-search" action="<?php echo esc_url( md_mc_url() ); ?>" role="search">
			<?php md_sup_app_field(); ?>
			<?php if ( $filter ) : ?><input type="hidden" name="mf" value="<?php echo esc_attr( $filter ); ?>"><?php endif; ?>
			<?php if ( $doc ) : ?><input type="hidden" name="md" value="<?php echo esc_attr( $doc ); ?>"><?php endif; ?>
			<?php if ( $ms ) : ?><input type="hidden" name="ms" value="<?php echo esc_attr( $ms ); ?>"><?php endif; ?>
			<input type="search" name="mq" id="mc-q" value="<?php echo esc_attr( $q ); ?>" placeholder="차트번호 · 이름 · 초성" autocomplete="off" enterkeyhint="search" aria-label="환자 찾기">
			<button type="submit" class="mds-btn mc-search__btn" title="병력 · 치료이력 · 참고사항까지 찾기">내용까지</button>
		</form>
		<div class="mc-chips" aria-label="보기">
			<?php
			md_mc_chip( '전체 ' . $c['patient'], md_mc_url( array( 'mq' => $q, 'md' => $doc, 'ms' => $ms ) ), '' === $filter );
			md_mc_chip( '📌 Follow-up ' . $c['pin'], md_mc_url( array( 'mf' => 'pin', 'mq' => $q, 'md' => $doc, 'ms' => $ms ) ), 'pin' === $filter );
			?>
		</div>
		<div class="mc-chips mc-chips--sort" aria-label="정렬">
			<span class="mc-chips__label">정렬</span>
			<?php foreach ( $sorts as $k => $label ) { md_mc_chip( $label, md_mc_url( array( 'mf' => $filter, 'mq' => $q, 'md' => $doc, 'ms' => 'viewed' === $k ? '' : $k ) ), $sort === $k ); } ?>
		</div>
		<?php $docs = md_mc_doctors(); if ( $docs ) : ?>
			<div class="mc-chips mc-chips--doc" aria-label="담당의">
				<span class="mc-chips__label">담당의</span>
				<?php foreach ( $docs as $dn ) { md_mc_chip( $dn, md_mc_url( array( 'mf' => $filter, 'mq' => $q, 'md' => $doc === $dn ? '' : $dn, 'ms' => $ms ) ), $doc === $dn ); } ?>
			</div>
		<?php endif; ?>
	</div>
	<?php md_mc_install_help(); /* v10.0 · 홈 화면에 추가 */ ?>
	<?php if ( '' !== $q || '' !== $doc ) : ?>
		<p class="mc-found"><?php echo '' !== $q ? '「' . esc_html( $q ) . '」 ' : ''; ?><?php echo '' !== $doc ? '담당의 ' . esc_html( $doc ) . ' · ' : ''; ?><?php echo count( $rows ); ?>명 · <a href="<?php echo esc_url( md_mc_url( array( 'mf' => $filter, 'ms' => $ms ) ) ); ?>">지우기</a></p>
	<?php endif; ?>
	<?php if ( ! $rows ) : ?>
		<div class="mds-card"><div class="mds-empty"><?php echo '' !== $q ? '찾는 환자가 없습니다.' : '해당하는 환자가 없습니다.'; ?></div></div>
		<?php if ( '' !== $q && preg_match( '/^\d+$/', $q ) ) : ?>
			<p><a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'chart' => $q ) ) ); ?>">＋ 차트번호 <?php echo esc_html( $q ); ?> 새 환자로 만들기</a></p>
		<?php endif; ?>
	<?php else : ?>
		<ul class="mc-list" id="mc-list">
			<?php foreach ( $rows as $r ) :
				$alrt = md_mc_alerts( $r->mhx );
				$last = md_mc_last_line( $r->tx_hist );
				$key  = $r->chart_no . ' ' . preg_replace( '/\s+/u', '', $r->pname ) . ' ' . $r->cho;
				?>
				<li data-k="<?php echo esc_attr( mb_strtolower( $key ) ); ?>">
					<a class="mc-row" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'p', 'mid' => $r->id ) ) ); ?>">
						<span class="mc-row__no"><?php echo esc_html( $r->chart_no ); ?></span>
						<span class="mc-row__name"><?php echo $r->pin ? '<span class="mc-row__pin" title="Follow-up">📌</span>' : ''; ?><span class="mc-row__nm"><?php echo esc_html( $r->pname ); ?></span><?php if ( $alrt ) : ?><span class="mc-warn" title="병력 주의: <?php echo esc_attr( implode( ', ', $alrt ) ); ?>">⚠ <?php echo esc_html( $alrt[0] ); ?></span><?php endif; ?></span>
						<span class="mc-row__last"><?php echo esc_html( $last ); ?></span>
						<span class="mc-row__upd" title="최근 내원"><?php echo esc_html( $r->last_visit ? md_mc_short_date( $r->last_visit ) : '' ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="mc-more-wrap" hidden><button type="button" class="mds-btn mc-more">더 보기</button></p>
		<p class="mc-nohit mds-hint" hidden>이름·차트번호로는 없습니다.</p>
		<section class="mc-deep" id="mc-deep" hidden><h3 class="mc-deep__h"></h3><ul class="mc-list mc-list--deep"></ul></section>
	<?php endif; ?>
	<?php
}

/** 칸 하나 (읽기) */
function md_mc_block( $label, $html, $cls = '', $id = '' ) {
	echo '<section class="mc-block ' . esc_attr( $cls ) . '"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '><h3 class="mc-block__h">' . esc_html( $label ) . '</h3><div class="mc-block__b">' . $html . '</div></section>'; // phpcs:ignore
}

/** v9.19 · 보기 부분 — 머리말과 본문 HTML (빠른 저장 뒤 그 자리만 바꾼다) */
function md_mc_inline_view( $r, $d, $field ) {
	$f = md_mc_fields();
	if ( 'mhx' === $field ) {
		$alrt = md_mc_alerts( $r->mhx );
		return array( 'head' => $f['mhx'][2] . ( $alrt ? ' — 주의: ' . implode( ', ', $alrt ) : '' ), 'html' => md_mc_mark_alerts( md_mc_text( $r->mhx ), $alrt ), 'cls' => md_mc_mhx_real( $r->mhx ) ? 'mc-block--alert' : '' );
	}
	if ( 'addr' === $field ) {
		/* 주소 — 덴트웹 주소가 있으면 그것, 미니차트 「지역」이 그 안에 이미 들어 있으면 한 번만 */
		$addr = $d && function_exists( 'md_mc_addr_short' ) ? md_mc_addr_short( (string) ( $d['addr'] ?? '' ) ) : ''; /* v9.8 · 동 · 읍 · 면까지만 */
		if ( '' === $addr && $d && function_exists( 'md_mc_dw_region' ) ) { $addr = md_mc_dw_region( (string) ( $d['region'] ?? '' ) ); }
		$reg  = trim( function_exists( 'md_mc_addr_memo' ) ? md_mc_addr_memo( (string) $r->addr ) : (string) $r->addr );
		if ( '' !== $addr ) {
			$dup = md_mc_blank( $reg ) || false !== mb_strpos( preg_replace( '/\s+/u', '', $addr ), preg_replace( '/\s+/u', '', $reg ) ) || ( $d && preg_replace( '/\s+/u', '', $reg ) === preg_replace( '/\s+/u', '', (string) $d['region'] ) );
			return array( 'head' => '주소', 'html' => esc_html( $addr ) . ( $dup ? '' : '<br><small class="mc-sub">지역 메모: ' . md_mc_text( $reg ) . '</small>' ), 'cls' => '' );
		}
		return array( 'head' => $f['addr'][0], 'html' => md_mc_text( $reg ), 'cls' => '' );
	}
	if ( 'dr' === $field ) {
		return array( 'head' => $f['dr'][0], 'html' => function_exists( 'md_mc_dw_dr_html' ) ? md_mc_dw_dr_html( $r, $d ) : md_mc_dr_html( $r->dr ), 'cls' => '' );
	}
	return array( 'head' => $f[ $field ][0], 'html' => md_mc_text( $r->$field ), 'cls' => '' );
}

/** v9.19 · 눌러서 바로 고치는 칸 — 보기(누르면 열림) + 고치기 폼(저장 · 취소). 저장은 빠른 저장(md_fast) 으로 그 자리만 바뀐다 */
function md_mc_inline_block( $r, $d, $field, $bare = false ) {
	$v = md_mc_inline_view( $r, $d, $field );
	$f = md_mc_fields();
	?>
	<form method="post" class="<?php echo $bare ? 'mc-edit mc-edit--bare' : 'mc-block mc-edit ' . esc_attr( $v['cls'] ); ?>"<?php echo $bare ? '' : ' id="f-' . esc_attr( $field ) . '"'; ?> action="<?php echo esc_url( md_mc_url() ); ?>" data-mc-fast="field" data-mc-field="<?php echo esc_attr( $field ); ?>">
		<?php md_mc_nonce_fields( 'field', $r->id ); ?><input type="hidden" name="field" value="<?php echo esc_attr( $field ); ?>"><input type="hidden" name="rev" value="<?php echo (int) $r->rev; ?>">
		<?php if ( $bare ) : ?><p class="mc-edit__hint mc-sub">아래 내용을 누르면 예전 것까지 바로 고칠 수 있습니다</p><?php else : ?>
		<h3 class="mc-block__h"><span class="mc-edit__head"><?php echo esc_html( $v['head'] ); ?></span> <small class="mc-sub">눌러서 바로 고치기</small></h3>
		<?php endif; ?>
		<div class="mc-block__b mc-edit__view" data-mc-edit-open title="눌러서 고치기"><?php echo $v['html']; // phpcs:ignore -- 안에서 이스케이프 ?></div>
		<div class="mc-edit__form" hidden>
			<?php if ( 'memo' === $field ) : ?>
				<textarea name="memo" rows="5" data-grow aria-label="참고사항 전체"><?php echo esc_textarea( (string) $r->memo ); ?></textarea>
			<?php elseif ( 'dr' === $field ) : ?>
				<?php md_mc_dr_field( array( 'dr' => (string) $r->dr ) ); ?>
			<?php else :
				$na  = '' !== (string) $r->$field && md_mc_is_na( $r->$field );
				$lab = 'addr' === $field ? '지역 메모' : $f[ $field ][0];
				md_mc_field( $field, $lab, $na ? '' : (string) $r->$field, array( 'rows' => 'mhx' === $field ? 2 : 1, 'na' => $na, 'attrs' => 'autocomplete="off"' ) );
				if ( 'mhx' === $field ) { echo md_mc_mhx_chips(); } // phpcs:ignore
				if ( 'referral' === $field ) { echo md_mc_chips( 'referral', 'ref', '여러 개 고를 수 있습니다 · 「가족 …」 「협력기관 …」을 누르면 이름을 바로 이어 적습니다' ); } // phpcs:ignore
			endif; ?>
			<div class="mc-edit__bar"><button type="submit" class="mds-btn mds-btn--fill">저장</button><button type="button" class="mds-btn mds-btn--ghost" data-mc-edit-cancel>취소</button></div>
		</div>
	</form>
	<?php
}

/** 한 줄 추가 폼 — 날짜(오늘) + 내용 */
function md_mc_addform( $r, $field, $label, $from = '' ) {
	?>
	<form method="post" class="mc-add" action="<?php echo esc_url( md_mc_url() ); ?>">
		<?php md_mc_nonce_fields( 'add', $r->id ); ?>
		<input type="hidden" name="field" value="<?php echo esc_attr( $field ); ?>">
		<?php if ( $from ) : ?><input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>"><?php endif; ?>
		<textarea name="text" rows="1" required placeholder="<?php echo esc_attr( $label ); ?>" class="mc-add__text" enterkeyhint="done"></textarea>
		<input type="date" name="mcday" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" aria-label="날짜 (기본 오늘)" class="mc-add__day">
		<button type="submit" class="mds-btn mds-btn--fill mc-add__btn">추가</button>
	</form>
	<?php
}

function md_mc_mhx_real( $mhx ) {
	return ! md_mc_blank( $mhx ) && ! in_array( strtolower( trim( (string) $mhx ) ), array( 'x', '없음', 'n/a' ), true );
}

function md_mc_render_patient( $id ) {
	$r = md_mc_get( $id );
	if ( ! $r || 'patient' !== $r->kind ) { md_mc_render_gone( $id ); return; }
	$f    = md_mc_fields();
	$alrt = md_mc_alerts( $r->mhx );
	/* v6.5 · 연 차트는 목록 맨 위로 (최근 본 순) — rev 는 바꾸지 않는다 */
	global $wpdb;
	$wpdb->update( md_mc_t(), array( 'viewed_at' => current_time( 'mysql' ) ), array( 'id' => (int) $r->id ) );
	do_action( 'md_mc_viewed', $r ); /* v8.0 · 열람 기록 (2년) */
	md_mc_render_jump(); /* v9.8 · 차트 보면서 다른 환자 찾기 (원장 지시) */
	if ( isset( $_GET['saved'] ) )    { echo '<div class="mds-notice mds-notice--ok">저장했습니다.</div>'; }
	if ( isset( $_GET['reverted'] ) ) { echo '<div class="mds-notice mds-notice--ok">이전 내용으로 되돌렸습니다.</div>'; }
	?>
	<?php
	/* v8.1 · 덴트웹 자료와 합쳐 한 화면으로 (원장 지시) — 같은 내용은 한 번만, 순서: 병력 → 기본 정보 → 치료계획 → 진료기록 → 참고사항 */
	$d   = function_exists( 'md_mc_dw_get' ) ? md_mc_dw_get( $r->chart_no ) : null;
	$age = $d && function_exists( 'md_mc_dw_age' ) ? md_mc_dw_age( $d['birth'] ) : '';
	$sex = $d ? ( 'M' === $d['sex'] ? '남' : ( 'F' === $d['sex'] ? '여' : '' ) ) : '';
	$lv  = $r->last_visit;
	if ( $d && $d['last'] && $d['last'] > (string) $lv ) { $lv = $d['last']; }
	$nm  = $d ? preg_replace( '/\s+/u', '', (string) $d['name'] ) : '';
	?>
	<article class="mc-chart" data-mc-id="<?php echo (int) $r->id; ?>" data-mc-label="<?php echo esc_attr( $r->chart_no . ' ' . $r->pname ); ?>">
		<header class="mds-card mc-chart__head">
			<div class="mc-chart__id">
				<button type="button" class="mc-copy" data-copy="<?php echo esc_attr( $r->chart_no ); ?>" title="차트번호 복사"><?php echo esc_html( $r->chart_no ); ?></button>
				<h2 class="mc-chart__name"><?php echo esc_html( $r->pname ); ?></h2>
				<?php if ( $sex || '' !== $age ) : ?><span class="mc-tag"><?php echo esc_html( trim( $sex . ( '' !== $age ? ' ' . $age . '세' : '' ) ) ); ?></span><?php endif; ?>
				<?php if ( $r->pin ) : ?><span class="mc-tag mc-tag--pin">📌 상단고정</span><?php endif; ?>
			</div>
			<div class="mc-chart__acts">
				<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'mid' => $r->id ) ) ); ?>">수정</a>
				<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>">
					<?php md_mc_nonce_fields( 'pin', $r->id ); ?><input type="hidden" name="on" value="<?php echo $r->pin ? '' : '1'; ?>">
					<button type="submit" class="mds-btn"><?php echo $r->pin ? '상단고정 해제' : '📌 상단고정'; ?></button>
				</form>
			</div>
			<?php if ( $d && $d['next'] ) : $nx = $d['next']; ?>
				<p class="mc-next"><b>다음 예약</b> <?php echo esc_html( $nx['at'] . ( '' !== $nx['doctor'] ? ' · ' . $nx['doctor'] : '' ) . ( '' !== $nx['what'] ? ' · ' . $nx['what'] : '' ) ); ?><?php echo '' !== $nx['memo'] ? '<small> — ' . esc_html( $nx['memo'] ) . '</small>' : ''; ?></p>
			<?php endif; ?>
			<p class="mc-chart__meta"><?php if ( $lv ) : ?>최근 내원 <?php echo esc_html( md_mc_short_date( $lv ) ); ?> · <?php endif; ?><?php if ( $d && $d['first'] ) : ?>첫 등록 <?php echo esc_html( md_mc_short_date( $d['first'] ) ); ?> · <?php endif; ?>마지막 수정 <?php echo esc_html( $r->updated_at ? md_mc_short_date( $r->updated_at ) . ' ' . date( 'H:i', strtotime( $r->updated_at ) ) : '—' ); ?><?php echo $r->updated_by ? ' · ' . esc_html( $r->updated_by ) : ''; ?> · <a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'log', 'mid' => $r->id ) ) ); ?>">변경 기록</a></p>
			<?php if ( $d && '' !== $nm && false === mb_strpos( preg_replace( '/\s+/u', '', (string) $r->pname ), $nm ) ) : ?>
				<p class="mds-notice mds-notice--warn">덴트웹 이름은 「<?php echo esc_html( $d['name'] ); ?>」입니다 — 차트번호를 확인해 주세요.</p>
			<?php elseif ( ! $d && get_option( 'md_mc_dw_last' ) ) : ?>
				<p class="mc-chart__meta">덴트웹에서 이 차트번호를 찾지 못했습니다 — 차트번호를 확인해 주세요.</p>
			<?php endif; ?>
		</header>

		<?php /* v9.19 · 병력 · 주소(지역 메모) · 담당의 · 내원경로도 눌러서 바로 고친다 (원장 지시) */ md_mc_inline_block( $r, $d, 'mhx' ); ?>

		<div class="mc-grid">
			<?php
			/* v9.20 · 연락처는 미니차트에 두지 않는다 (원장 지시 — 덴트웹에서 보면 된다) */
			md_mc_inline_block( $r, $d, 'addr' );
			md_mc_inline_block( $r, $d, 'dr' );
			md_mc_inline_block( $r, $d, 'referral' );
			?>
		</div>
		<?php /* v8.6 · 치료계획은 차트 화면에서 바로 고친다 (원장 지시 — 수정 버튼 없이) */ ?>
		<form method="post" class="mc-block mc-block--plan mc-plan" id="f-tx_plan" action="<?php echo esc_url( md_mc_url() ); ?>">
			<?php md_mc_nonce_fields( 'plan', $r->id ); ?><input type="hidden" name="rev" value="<?php echo (int) $r->rev; ?>">
			<h3 class="mc-block__h"><?php echo esc_html( $f['tx_plan'][0] ); ?> <small class="mc-sub">눌러서 바로 고치기</small></h3>
			<textarea name="tx_plan" rows="2" data-grow data-mc-plan placeholder="치료계획을 적으세요"><?php echo esc_textarea( (string) $r->tx_plan ); ?></textarea>
			<div class="mc-plan__bar" hidden><button type="submit" class="mds-btn mds-btn--fill">저장</button><button type="button" class="mds-btn mds-btn--ghost" data-mc-plan-undo>되돌리기</button></div>
		</form>

		<section class="mc-block mc-block--log" id="f-tx_hist">
			<h3 class="mc-block__h">진료기록 <small class="mc-sub"><?php echo $d ? '덴트웹 치료내용 + ✎ 직접 적은 기록' : '✎ 직접 적은 기록'; ?></small></h3>
			<?php md_mc_addform( $r, 'tx_hist', '진료기록 직접 입력 (날짜 고를 수 있음)' ); ?>
			<div class="mc-block__b"><?php echo function_exists( 'md_mc_dw_timeline' ) ? md_mc_dw_timeline( $r, $d ) : md_mc_text( $r->tx_hist ); // phpcs:ignore ?></div>
		</section>
		<?php $mn = count( array_filter( preg_split( '/\r\n|\r|\n/', (string) $r->memo ), 'strlen' ) ); /* v8.3 · 환자 보는 화면에서 바로 보이지 않게 — 눌러야 펼쳐짐 (원장 지시) */ ?>
		<details class="mc-block mc-block--log mc-memo" id="f-memo"<?php echo isset( $_GET['memo'] ) ? ' open' : ''; ?>>
			<summary class="mc-block__h"><?php echo esc_html( $f['memo'][0] ); ?> <small class="mc-sub"><?php echo $mn ? $mn . '줄 · ' : ''; ?>눌러서 보기</small></summary>
			<?php md_mc_addform( $r, 'memo', '참고사항입력 (날짜없이)' ); ?>
			<?php md_mc_inline_block( $r, $d, 'memo', true ); /* v9.20 · 예전 참고사항도 눌러서 고치기 (원장 지시) */ ?>
		</details>
		<?php if ( $d ) : ?><p class="mc-chart__meta mc-dwnote">덴트웹 자료 <?php echo esc_html( md_mc_short_date( $d['_synced'] ) . ' ' . date( 'H:i', strtotime( $d['_synced'] ) ) ); ?> 기준 (30분마다 새로 받음)</p><?php endif; ?>

		<div class="mc-chart__foot">
			<a href="<?php echo esc_url( md_mc_url() ); ?>">← 환자 목록</a>
			<?php if ( md_mc_can_manage() ) : ?>
			<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('<?php echo esc_js( $r->pname . ' (' . $r->chart_no . ')' ); ?> 차트를 휴지통으로 옮길까요? 휴지통에서 되살릴 수 있습니다.');">
				<?php md_mc_nonce_fields( 'delete', $r->id ); ?>
				<button type="submit" class="mc-del">🗑 삭제</button>
			</form>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

function md_mc_render_gone( $id ) {
	$r = $id ? md_mc_get( $id, true ) : null;
	echo '<div class="mds-card"><div class="mds-empty">';
	if ( $r && $r->deleted_at ) {
		echo '이 차트는 휴지통에 있습니다. <a href="' . esc_url( md_mc_url( array( 'mv' => 'trash' ) ) ) . '">휴지통 열기</a>';
	} else {
		echo '찾는 차트가 없습니다. <a href="' . esc_url( md_mc_url() ) . '">목록으로</a>';
	}
	echo '</div></div>';
}

/** 글 칸 하나 (폼) */
function md_mc_field( $name, $label, $value, $opt = array() ) {
	$opt  = wp_parse_args( $opt, array( 'req' => false, 'rows' => 2, 'hint' => '', 'input' => false, 'attrs' => '', 'na' => null ) );
	$id   = 'mcf-' . $name;
	echo '<div class="mc-field' . ( null !== $opt['na'] ? ' mc-field--na' : '' ) . '">';
	echo '<div class="mc-field__top"><label class="mc-field__l" for="' . esc_attr( $id ) . '">' . ( $opt['req'] ? '<em class="mc-req">*</em>' : '' ) . esc_html( $label ) . ( $opt['hint'] ? ' <small>' . esc_html( $opt['hint'] ) . '</small>' : '' ) . '</label>';
	if ( null !== $opt['na'] ) {
		/* v6.8 · 해당사항 없으면 체크 — 체크하면 글 칸은 잠긴다 */
		echo '<label class="mc-na-chk"><input type="checkbox" name="na[' . esc_attr( $name ) . ']" value="1" data-na-for="' . esc_attr( $id ) . '"' . ( $opt['na'] ? ' checked' : '' ) . '> ' . MD_MC_NA . '</label>';
		if ( $opt['na'] ) { $value = ''; $opt['attrs'] .= ' disabled'; }
	}
	echo '</div>';
	if ( $opt['input'] ) {
		echo '<input id="' . esc_attr( $id ) . '" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" ' . $opt['attrs'] . '>'; // phpcs:ignore
	} else {
		echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . (int) $opt['rows'] . '" data-grow ' . $opt['attrs'] . '>' . esc_textarea( $value ) . '</textarea>'; // phpcs:ignore
	}
	echo '</div>';
}

/** 선택 상자 하나 */
function md_mc_select( $name, $opts, $cur, $empty ) {
	if ( '' !== $cur && ! in_array( $cur, $opts, true ) ) { array_unshift( $opts, $cur ); }
	$h = '<select name="' . esc_attr( $name ) . '"><option value="">' . esc_html( $empty ) . '</option>';
	foreach ( $opts as $o ) { $h .= '<option value="' . esc_attr( $o ) . '"' . selected( $o, $cur, false ) . '>' . esc_html( $o ) . '</option>'; }
	return $h . '</select>';
}

/** 과 · 담당의 한 줄 */
function md_mc_dr_pair_html( $dept, $doc ) {
	return '<div class="mc-dr__pair">'
		. md_mc_select( 'dr_dept[]', md_mc_dr_roles(), $dept, '과 선택' )
		. md_mc_select( 'dr_doc[]', md_mc_doctors(), $doc, '담당의 선택' )
		. '<button type="button" class="mc-dr__rm" data-mc-dr-rm aria-label="이 줄 빼기">✕</button></div>';
}

/** v6.8 · 담당의 — 기본 담당의 한 분 + 「담당의 추가」로 과 · 담당의 줄 (원장 지시) */
function md_mc_dr_field( $vals ) {
	$main_dept = '';
	if ( isset( $vals['dr_main'] ) ) {
		$main  = (string) $vals['dr_main'];
		$main_dept = (string) ( $vals['dr_main_dept'] ?? '' );
		$pairs = array();
		foreach ( array_values( (array) ( $vals['dr_dept'] ?? array() ) ) as $i => $d ) {
			$doc = (string) ( array_values( (array) ( $vals['dr_doc'] ?? array() ) )[ $i ] ?? '' );
			if ( '' !== $d || '' !== $doc ) { $pairs[] = array( (string) $d, $doc ); }
		}
		$extra = (string) ( $vals['dr_extra'] ?? '' );
	} else {
		list( $main, $pairs, $extra ) = md_mc_dr_parse( $vals['dr'] ?? '' );
		/* v8.3 · 과가 붙은 첫 줄도 첫 담당의로 */
		if ( '' === $main && $pairs ) { list( $main_dept, $main ) = array_shift( $pairs ); }
	}
	?>
	<div class="mc-field mc-dr" data-mc-dr>
		<span class="mc-field__l"><em class="mc-req">*</em>담당의</span>
		<div class="mc-dr__pair mc-dr__first">
			<?php echo md_mc_select( 'dr_main_dept', md_mc_dr_roles(), $main_dept, '과 선택' ); // phpcs:ignore ?>
			<?php echo md_mc_select( 'dr_main', md_mc_doctors(), $main, '담당의 선택' ); // phpcs:ignore ?>
		</div>
		<div class="mc-dr__list" data-mc-dr-list>
			<?php foreach ( $pairs as $pr ) { echo md_mc_dr_pair_html( $pr[0], $pr[1] ); } // phpcs:ignore ?>
		</div>
		<template data-mc-dr-tpl><?php echo md_mc_dr_pair_html( '', '' ); // phpcs:ignore ?></template>
		<button type="button" class="mds-btn mc-dr__add" data-mc-dr-add>＋ 담당의 추가</button>
		<?php if ( '' !== trim( $extra ) ) : ?>
			<textarea name="dr_extra" rows="1" data-grow aria-label="담당의 그 밖의 내용"><?php echo esc_textarea( $extra ); ?></textarea>
		<?php endif; ?>
	</div>
	<?php
}

/** v8.4 · 눌러서 넣는 목록 — 설정에서 바꾼다 (병력 mhx · 내원경로 ref) */
function md_mc_chip_list( $kind ) {
	$st = md_mc_settings();
	$k  = 'mhx' === $kind ? 'mhx_chips' : 'ref_chips';
	if ( ! empty( $st[ $k ] ) && is_array( $st[ $k ] ) ) { return $st[ $k ]; }
	return 'mhx' === $kind
		? array( '고혈압', '당뇨', '심장질환', '부정맥', '뇌졸중', '갑상선', '간질환', '신장질환 · 투석', '골다공증 약', '항응고제 · 아스피린', '스텐트', '항암 · 방사선', '알레르기', '임신', '흡연', '천식', 'B형간염' )
		: array( '인터넷 검색', '네이버 지도 · 플레이스', '블로그 · 카페', '인스타그램 · 유튜브', '지인 소개', '간판 · 지나가다', '광고 · 이벤트', '이전 내원', '가족:', '소개자:', '협력기관:' );
}

/** v8.1 · 병력 / v8.4 · 내원경로 — 눌러서 넣는 칸 (다시 누르면 빠짐) + 직접 더 적기 */
function md_mc_chips( $field, $kind, $hint ) {
	$h = '<div class="mc-chips" data-mc-chips="' . esc_attr( $field ) . '" aria-label="눌러서 넣기">';
	foreach ( md_mc_chip_list( $kind ) as $c ) {
		$pre = ':' === mb_substr( $c, -1 );
		$h  .= '<button type="button" class="mc-chip' . ( $pre ? ' mc-chip--pre' : '' ) . '" data-chip="' . esc_attr( $pre ? rtrim( $c, ':' ) . ':' : $c ) . '">' . esc_html( $pre ? rtrim( $c, ':' ) . ' …' : $c ) . '</button>';
	}
	return $h . '<span class="mc-chips__hint">' . esc_html( $hint ) . '</span></div>';
}
function md_mc_mhx_chips() { return md_mc_chips( 'mhx', 'mhx', '눌러서 넣고, 위 칸에 더 적어도 됩니다 (예: 당뇨 — 인슐린, 공복 혈당 140)' ); }

/** 수정 · 새로 만들기 — 칸 순서와 이름은 AppSheet 폼 그대로 */
function md_mc_render_edit( $id, $kind ) {
	$r = $id ? md_mc_get( $id ) : null;
	if ( $id && ! $r ) { md_mc_render_gone( $id ); return; }
	if ( $r ) { $kind = $r->kind; }
	$vals = $r ? (array) $r : array();
	if ( ! $r && isset( $_GET['chart'] ) ) { $vals['chart_no'] = sanitize_text_field( wp_unslash( $_GET['chart'] ) ); }
	$draft = isset( $_GET['draft'] ) ? get_transient( 'md_mc_draft_' . get_current_user_id() . '_' . $id ) : false;
	if ( is_array( $draft ) ) { $vals = array_merge( $vals, $draft ); }
	$conflict = isset( $_GET['conflict'] ) && $r;
	if ( isset( $_GET['dup'] ) ) {
		$d = md_mc_get( (int) $_GET['dup'] );
		if ( $d ) { echo '<div class="mds-notice mds-notice--warn">같은 차트번호의 환자: <a href="' . esc_url( md_mc_url( array( 'mv' => 'p', 'mid' => $d->id ) ) ) . '">' . esc_html( $d->chart_no . ' ' . $d->pname ) . ' 차트 열기</a></div>'; }
	}
	if ( $conflict ) {
		echo '<div class="mds-notice mds-notice--warn">아래는 방금 적으신 내용입니다. 다른 분이 저장한 최신 내용은 각 칸 아래 「저장된 최신 내용」에서 확인하고, 합쳐서 다시 저장해 주세요.</div>';
	}
	/* v8.1 · 담당의가 비었으면 덴트웹 담당의를 기본으로 */
	if ( $r && 'patient' === $kind && ! isset( $vals['dr_main'] ) && function_exists( 'md_mc_dw_get' ) ) {
		$dwd = md_mc_dw_get( $r->chart_no );
		if ( $dwd && '' !== $dwd['doctor'] ) { list( $m0, $p0 ) = md_mc_dr_parse( $vals['dr'] ?? '' ); if ( '' === $m0 && ! $p0 ) { $vals['dr'] = trim( $dwd['doctor'] . "\n" . (string) ( $vals['dr'] ?? '' ) ); } }
	}
	$v    = function ( $k ) use ( $vals ) { return isset( $vals[ $k ] ) ? (string) $vals[ $k ] : ''; };
	$back = $r ? md_mc_url( array( 'mv' => 'note' === $kind ? 'note' : 'p', 'mid' => $r->id ) ) : md_mc_url( array( 'mv' => 'note' === $kind ? 'notes' : '' ) );
	$latest = function ( $k ) use ( $conflict, $r, $v ) {
		if ( $conflict && (string) $r->$k !== $v( $k ) ) {
			echo '<details class="mc-latest"><summary>저장된 최신 내용 (다름)</summary><div>' . md_mc_text( $r->$k ) . '</div></details>'; // phpcs:ignore
		}
	};
	$f = md_mc_fields();
	?>
	<form method="post" class="mds-card mc-form" action="<?php echo esc_url( md_mc_url() ); ?>"<?php echo ( ! $r && 'note' !== $kind && function_exists( 'md_mc_dw_get' ) ) ? ' data-dw="' . esc_url( md_mc_url() ) . '"' : ''; /* v7.0 · 새 환자 — 덴트웹으로 채우기 */ ?>>
		<?php md_mc_nonce_fields( 'save', $r ? $r->id : 0 ); ?>
		<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
		<input type="hidden" name="rev" value="<?php echo $r ? (int) $r->rev : 0; ?>">
		<h2 class="mc-form__h"><?php echo $r ? ( 'note' === $kind ? '노트 수정' : esc_html( $r->pname ) . ' 수정' ) : ( 'note' === $kind ? '새 팀 노트' : '새 환자' ); ?></h2>

		<?php if ( 'note' === $kind ) : ?>
			<?php md_mc_field( 'title', '제목', $v( 'title' ), array( 'req' => true, 'input' => true, 'attrs' => 'required maxlength="250"' ) ); ?>
			<?php md_mc_field( 'body', '본문', $v( 'body' ), array( 'rows' => 14 ) ); ?>
			<?php $latest( 'body' ); ?>
		<?php else : ?>
			<?php
			md_mc_field( 'chart_no', $f['chart_no'][0], $v( 'chart_no' ), array( 'req' => true, 'input' => true, 'attrs' => 'required inputmode="numeric" maxlength="60" autocomplete="off"' . ( $r ? '' : ' autofocus' ) ) );
			md_mc_field( 'pname', $f['pname'][0], $v( 'pname' ), array( 'req' => true, 'input' => true, 'attrs' => 'required maxlength="250" autocomplete="off"' ) );
			if ( ! $r ) { echo '<div class="mc-dw-prev" hidden aria-live="polite"></div>'; } /* v8.1 · 새 환자 — 덴트웹에서 가져온 것 미리 보기 */
			foreach ( array( 'addr', 'mhx', 'referral' ) as $k ) {
				$na = ! empty( $vals['na'][ $k ] ) || ( '' !== $v( $k ) && md_mc_is_na( $v( $k ) ) );
				$req = $f[ $k ][1]; /* v9.9 · 별표는 md_mc_fields 대로 (지역만) */
				md_mc_field( $k, $f[ $k ][0], $v( $k ), array( 'req' => $req, 'rows' => 'mhx' === $k ? 2 : 1, 'attrs' => $req ? 'required' : '', 'na' => $na ) );
				if ( 'mhx' === $k ) { echo md_mc_mhx_chips(); } // phpcs:ignore
				if ( 'referral' === $k ) { echo md_mc_chips( 'referral', 'ref', '여러 개 고를 수 있습니다 · 「가족 …」 「협력기관 …」을 누르면 이름을 바로 이어 적습니다 (예: 가족: 홍길동 #12345)' ); } // phpcs:ignore
				$latest( $k );
			}
			md_mc_dr_field( $vals );
			if ( $conflict ) { $latest( 'dr' ); }
			md_mc_field( 'tx_plan', $f['tx_plan'][0], $v( 'tx_plan' ), array( 'rows' => 2 ) );
			$latest( 'tx_plan' );
			md_mc_field( 'tx_new', '진료기록 직접 입력 (날짜없이)', '', array( 'rows' => 1, 'hint' => '저장하면 오늘 날짜를 붙여 진료기록에 (덴트웹 치료내용은 자동으로 들어옵니다)' ) );
			md_mc_field( 'tx_hist', '진료기록 — 직접 적은 것 전체', $v( 'tx_hist' ), array( 'rows' => 3 ) );
			$latest( 'tx_hist' );
			md_mc_field( 'memo_new', '참고사항입력 (날짜없이)', '', array( 'rows' => 1, 'hint' => '저장하면 오늘 날짜를 붙여 참고사항 맨 위에' ) );
			?>
			<details class="mc-more-field"<?php echo $conflict ? ' open' : ''; ?>>
				<summary>참고사항 전체 고치기</summary>
				<?php md_mc_field( 'memo', $f['memo'][0], $v( 'memo' ), array( 'rows' => 3 ) ); $latest( 'memo' ); ?>
			</details>
			<div class="mc-field">
				<span class="mc-field__l">Follow-up</span>
				<label class="mc-check"><input type="checkbox" name="pin" value="1" <?php checked( (bool) ( $vals['pin'] ?? 0 ) ); ?>> 📌 상단고정</label>
			</div>
		<?php endif; ?>

		<div class="mc-form__foot">
			<a class="mds-btn mds-btn--ghost mc-form__cancel" href="<?php echo esc_url( $back ); ?>">취소</a>
			<button type="submit" class="mds-btn mds-btn--fill mc-form__save">저장</button>
		</div>
	</form>
	<?php
}

function md_mc_render_notes() {
	$q     = isset( $_GET['mq'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['mq'] ) ) ) : '';
	$notes = md_mc_notes( $q );
	if ( isset( $_GET['gone'] ) ) {
		echo '<div class="mds-notice mds-notice--ok">휴지통으로 옮겼습니다. <a href="' . esc_url( md_mc_url( array( 'mv' => 'trash' ) ) ) . '">되살리기</a></div>';
	}
	?>
	<form method="get" class="mc-search" action="<?php echo esc_url( md_mc_url() ); ?>" role="search">
		<?php md_sup_app_field(); ?><input type="hidden" name="mv" value="notes">
		<input type="search" name="mq" value="<?php echo esc_attr( $q ); ?>" placeholder="노트에서 찾기" enterkeyhint="search" aria-label="노트 찾기">
		<button type="submit" class="mds-btn mc-search__btn">찾기</button>
	</form>
	<?php
	if ( ! $notes ) { echo '<div class="mds-card"><div class="mds-empty">노트가 없습니다.</div></div>'; return; }
	echo '<ul class="mc-notes">';
	foreach ( $notes as $n ) {
		$first = trim( (string) strtok( (string) $n->body, "\n" ) );
		echo '<li><a class="mc-note-row" href="' . esc_url( md_mc_url( array( 'mv' => 'note', 'mid' => $n->id ) ) ) . '"><b>' . esc_html( $n->title ) . '</b><span>' . esc_html( mb_substr( $first, 0, 80 ) ) . '</span><time>' . esc_html( md_mc_short_date( $n->updated_at ) ) . '</time></a></li>';
	}
	echo '</ul>';
}

function md_mc_render_note( $id ) {
	$r = md_mc_get( $id );
	if ( ! $r || 'note' !== $r->kind ) { md_mc_render_gone( $id ); return; }
	if ( isset( $_GET['saved'] ) )    { echo '<div class="mds-notice mds-notice--ok">저장했습니다.</div>'; }
	if ( isset( $_GET['reverted'] ) ) { echo '<div class="mds-notice mds-notice--ok">이전 내용으로 되돌렸습니다.</div>'; }
	?>
	<article class="mds-card mc-note">
		<header class="mc-note__head">
			<h2><?php echo esc_html( $r->title ); ?></h2>
			<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'mid' => $r->id ) ) ); ?>">수정</a>
		</header>
		<p class="mc-chart__meta">마지막 수정 <?php echo esc_html( $r->updated_at ? md_mc_short_date( $r->updated_at ) . ' ' . date( 'H:i', strtotime( $r->updated_at ) ) : '—' ); ?><?php echo $r->updated_by ? ' · ' . esc_html( $r->updated_by ) : ''; ?> · <a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'log', 'mid' => $r->id ) ) ); ?>">변경 기록</a></p>
		<div id="f-body"><?php md_mc_addform( $r, 'body', '오늘 날짜로 맨 위에 추가' ); ?></div>
		<div class="mc-note__body"><?php echo md_mc_text( $r->body ); // phpcs:ignore ?></div>
		<div class="mc-chart__foot">
			<a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'notes' ) ) ); ?>">← 팀 노트</a>
			<?php if ( md_mc_can_manage() ) : ?>
			<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('이 노트를 휴지통으로 옮길까요?');">
				<?php md_mc_nonce_fields( 'delete', $r->id ); ?><button type="submit" class="mc-del">🗑 삭제</button>
			</form>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/** 변경 기록 — 고치기 전 내용들, 그 시점으로 되돌리기 */
function md_mc_render_log( $id ) {
	global $wpdb;
	$r = md_mc_get( $id, true );
	if ( ! $r ) { md_mc_render_gone( $id ); return; }
	$logs  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . md_mc_t( 'log' ) . ' WHERE rec_id = %d ORDER BY id DESC LIMIT 100', (int) $id ) );
	$acts  = array( 'new' => '새로 만듦', 'edit' => '수정', 'add' => '한 줄 추가', 'pin' => '상단고정', 'unpin' => '상단고정 해제', 'delete' => '삭제', 'restore' => '되살림', 'revert' => '되돌림', 'import' => '가져오기' );
	$title = 'note' === $r->kind ? $r->title : $r->chart_no . ' ' . $r->pname;
	$keys  = 'note' === $r->kind ? array( 'title' => '제목', 'body' => '본문' ) : array_map( function ( $f ) { return $f[2]; }, md_mc_fields() );
	?>
	<h2 class="mc-group">변경 기록 · <?php echo esc_html( $title ); ?></h2>
	<p class="mds-hint">각 줄은 그때 <b>고치기 전</b> 내용입니다.<?php echo md_mc_can_manage() ? ' 「이 내용으로 되돌리기」를 누르면 그 내용으로 돌아가고, 지금 내용도 기록에 남습니다.' : ' 되돌리기는 라운지 관리자가 합니다.'; ?></p>
	<?php if ( ! $logs ) : ?><div class="mds-card"><div class="mds-empty">기록이 없습니다.</div></div><?php endif; ?>
	<?php foreach ( $logs as $lg ) :
		$snap = $lg->snap ? json_decode( $lg->snap, true ) : null;
		?>
		<details class="mds-card mc-log">
			<summary><b><?php echo esc_html( $acts[ $lg->act ] ?? $lg->act ); ?></b> · <?php echo esc_html( md_mc_short_date( $lg->at ) . ' ' . date( 'H:i', strtotime( $lg->at ) ) ); ?> · <?php echo esc_html( $lg->who ); ?></summary>
			<?php if ( is_array( $snap ) ) : ?>
				<dl class="mc-log__dl">
					<?php foreach ( $keys as $k => $label ) :
						$old = (string) ( $snap[ $k ] ?? '' );
						$now = (string) ( $r->$k ?? '' );
						if ( $old === $now ) { continue; } ?>
						<dt><?php echo esc_html( $label ); ?></dt><dd><?php echo md_mc_text( $old ); // phpcs:ignore ?></dd>
					<?php endforeach; ?>
				</dl>
				<?php if ( md_mc_can_manage() ) : ?>
				<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('이 시점 내용으로 되돌릴까요?');">
					<?php md_mc_nonce_fields( 'revert', $r->id ); ?><input type="hidden" name="lid" value="<?php echo (int) $lg->id; ?>">
					<button type="submit" class="mds-btn">이 내용으로 되돌리기</button>
				</form>
				<?php endif; ?>
			<?php else : ?>
				<p class="mds-hint">이전 내용 없음</p>
			<?php endif; ?>
		</details>
	<?php endforeach; ?>
	<p><a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'note' === $r->kind ? 'note' : 'p', 'mid' => $r->id ) ) ); ?>">← 돌아가기</a></p>
	<?php
}

function md_mc_render_admin() {
	$last = get_option( 'md_mc_imported' );
	$c    = md_mc_counts();
	if ( isset( $_GET['imported'] ) && is_array( $last ) ) {
		$r = $last['res'];
		echo '<div class="mds-notice mds-notice--ok">가져왔습니다 — 환자 ' . (int) $r['patient'] . '명 · 노트 ' . (int) $r['note'] . '장' . ( $r['updated'] ? ' · 덮어씀 ' . (int) $r['updated'] . '건' : '' ) . ( $r['skipped'] ? ' · 빈 줄 ' . (int) $r['skipped'] : '' ) . '</div>';
	}
	?>
	<section class="mds-card mc-admin">
		<h2 class="mc-form__h">Import <small>AppSheet 구글 시트에서 가져오기</small></h2>
		<ol class="mc-steps">
			<li>구글 시트 「Mini Chart」를 엽니다 (moondentaldigital 계정).</li>
			<li>파일 › 다운로드 › <b>Microsoft Excel (.xlsx)</b></li>
			<li>받은 파일을 아래에서 고르고 「Import」</li>
		</ol>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return this.mode.value!=='replace' || confirm('지금 있는 미니차트 <?php echo (int) ( $c['patient'] + $c['note'] ); ?>건을 지우고 파일 내용으로 바꿀까요? (지운 내용은 백업으로 남습니다)');">
			<?php md_mc_nonce_fields( 'import' ); ?>
			<input type="file" name="file" accept=".xlsx" required class="mc-file">
			<fieldset class="mc-modes">
				<label><input type="radio" name="mode" value="replace" checked> <span><b>전부 바꾸기</b> — 라운지 내용을 지우고 파일대로</span></label>
				<label><input type="radio" name="mode" value="merge"> <span><b>합치기</b> — 같은 AppSheet id 는 파일 내용으로 덮어쓰고, 없는 것은 추가</span></label>
			</fieldset>
			<button type="submit" class="mds-btn mds-btn--fill">Import</button>
		</form>
		<?php if ( is_array( $last ) ) : ?>
			<p class="mds-hint">마지막 Import: <?php echo esc_html( $last['at'] . ' · ' . $last['who'] . ' · ' . ( 'merge' === $last['mode'] ? '합치기' : '전부 바꾸기' ) ); ?></p>
		<?php endif; ?>
	</section>
	<section class="mds-card mc-admin">
		<h2 class="mc-form__h">Export <small>엑셀로 내려받기</small></h2>
		<p>환자 <?php echo (int) $c['patient']; ?>명 · 노트 <?php echo (int) $c['note']; ?>장 — 환자 개인정보가 들어 있으니 병원 PC 에만 보관하세요.</p>
		<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( add_query_arg( array( 'md_mc_dl' => 1, '_n' => wp_create_nonce( 'md_mc_dl' ) ), md_mc_url() ) ); ?>">Export (.xlsx)</a>
	</section>
	<?php
}

/** 휴지통 — 되살리기는 누구나, 비우기 · 영구 삭제는 관리자 (v6.5 · 비우기 버튼을 위로) */
function md_mc_render_trash() {
	global $wpdb;
	$rows   = $wpdb->get_results( 'SELECT id, kind, chart_no, pname, title, deleted_at, updated_by FROM ' . md_mc_t() . ' WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT 300' );
	$manage = md_mc_is_owner(); /* v6.8 · 비우기 · 영구 삭제는 원장 계정만 */
	if ( isset( $_GET['purged'] ) ) { echo '<div class="mds-notice mds-notice--ok">휴지통을 비웠습니다.</div>'; }
	?>
	<div class="mc-trash-head">
		<h2 class="mc-group">휴지통 <small>지운 차트 · 노트 <?php echo count( $rows ); ?>건 — 되살릴 수 있습니다</small></h2>
		<?php if ( $manage && $rows ) : ?>
			<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('휴지통의 <?php echo count( $rows ); ?>건을 영구히 지울까요? 되돌릴 수 없습니다.');">
				<?php md_mc_nonce_fields( 'purge' ); ?><button type="submit" class="mds-btn mc-btn-danger">🗑 휴지통 비우기</button>
			</form>
		<?php endif; ?>
	</div>
	<?php if ( ! $rows ) : ?>
		<div class="mds-card"><div class="mds-empty">비어 있습니다.</div></div>
	<?php else : ?>
		<ul class="mc-notes">
			<?php foreach ( $rows as $r ) : ?>
				<li class="mc-trash-row">
					<span><b><?php echo esc_html( 'note' === $r->kind ? $r->title : $r->chart_no . ' ' . $r->pname ); ?></b> <small><?php echo esc_html( md_mc_short_date( $r->deleted_at ) . ' · ' . $r->updated_by ); ?></small></span>
					<span class="mc-trash-row__acts">
						<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>">
							<?php md_mc_nonce_fields( 'restore', $r->id ); ?><button type="submit" class="mds-btn">되살리기</button>
						</form>
						<?php if ( $manage ) : ?>
							<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('이 항목을 영구히 지울까요?');">
								<?php md_mc_nonce_fields( 'purge_one', $r->id ); ?><button type="submit" class="mc-del">영구 삭제</button>
							</form>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( ! $manage ) : ?><p class="mds-hint">휴지통 비우기 · 영구 삭제는 총괄 관리자가 합니다.</p><?php endif;
}

/** v6.5 · 설정 (관리자만) — 담당의 목록 · 담당의 칸 · 병력 주의 단어 · 휴지통 */
function md_mc_render_settings() {
	if ( isset( $_GET['saved'] ) )  { echo '<div class="mds-notice mds-notice--ok">저장했습니다.</div>'; }
	if ( isset( $_GET['purged'] ) ) { echo '<div class="mds-notice mds-notice--ok">휴지통을 비웠습니다.</div>'; }
	$c = md_mc_counts();
	?>
	<form method="post" class="mds-card mc-form mc-settings" action="<?php echo esc_url( md_mc_url() ); ?>">
		<?php md_mc_nonce_fields( 'settings' ); ?>
		<h2 class="mc-form__h">설정 <small>라운지 관리자 · 원장 계정만 볼 수 있습니다</small></h2>
		<?php md_mc_field( 'doctors', '담당의 목록 (원장님 이름)', implode( "\n", md_mc_doctors() ), array( 'rows' => 8, 'hint' => '한 줄에 한 분 · 이 순서대로 고르기 · 목록 위 담당의 버튼에 나옵니다' ) ); ?>
		<?php md_mc_field( 'roles', '과 목록', implode( "\n", md_mc_dr_roles() ), array( 'rows' => 4, 'hint' => '담당의 추가할 때 고르는 과 · 한 줄에 하나 · 예) 임플란트 · 보철 · 교정' ) ); ?>
		<?php md_mc_field( 'alert_words', '병력 주의 단어', implode( "\n", md_mc_alert_words() ), array( 'rows' => 8, 'hint' => '병력에 이 단어가 있으면 붉게 표시 · 한 줄에 하나 · 뒤에 x · 없음이 붙으면 표시하지 않음' ) ); ?>
		<label class="mc-check"><input type="checkbox" name="alert_reset" value="1"> 병력 주의 단어를 처음 값으로 되돌리기</label>
		<?php md_mc_field( 'mhx_chips', '병력 — 눌러서 넣는 목록', implode( "\n", md_mc_chip_list( 'mhx' ) ), array( 'rows' => 8, 'hint' => '새 환자 · 수정 화면의 병력 버튼 · 한 줄에 하나 · 비우면 처음 값' ) ); ?>
		<?php md_mc_field( 'ref_chips', '내원경로 · 가족 · 협력기관 — 눌러서 넣는 목록', implode( "\n", md_mc_chip_list( 'ref' ) ), array( 'rows' => 8, 'hint' => '한 줄에 하나 · 끝을 「:」으로 쓰면(예: 가족:) 누를 때 「가족: 」을 넣고 이름을 바로 적게 됩니다 · 비우면 처음 값' ) ); ?>
		<div class="mc-form__foot">
			<a class="mds-btn mds-btn--ghost mc-form__cancel" href="<?php echo esc_url( md_mc_url() ); ?>">취소</a>
			<button type="submit" class="mds-btn mds-btn--fill mc-form__save">저장</button>
		</div>
	</form>
	<section class="mds-card mc-admin">
		<h2 class="mc-form__h">휴지통</h2>
		<p>지운 차트 · 노트 <?php echo (int) $c['trash']; ?>건</p>
		<?php if ( $c['trash'] && md_mc_is_owner() ) : ?>
			<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('휴지통의 <?php echo (int) $c['trash']; ?>건을 영구히 지울까요? 되돌릴 수 없습니다.');">
				<?php md_mc_nonce_fields( 'purge' ); ?><input type="hidden" name="from" value="settings"><button type="submit" class="mds-btn mc-btn-danger">🗑 휴지통 비우기</button>
			</form>
			<a class="mds-btn" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'trash' ) ) ); ?>">휴지통 열기</a>
		<?php endif; ?>
	</section>
	<?php
}

/* v7.0 · 덴트웹 자동 연동 (병원 PC mc-sync.ps1 → REST md-mc/v1) */
if ( file_exists( __DIR__ . '/minichart-dw.php' ) ) { require_once __DIR__ . '/minichart-dw.php'; }
