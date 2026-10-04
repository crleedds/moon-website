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

define( 'MD_MC_SCHEMA', 1 );

/* ============================================================
 * 테이블
 * ============================================================ */

function md_mc_t( $k = 'rec' ) {
	global $wpdb;
	return $wpdb->prefix . 'md_mc_' . $k;
}

/** 환자 칸 — 키 => [ 이름, 별표, 안내 ] (안내는 AppSheet 「미니차트 사용법」의 입력 예시) */
function md_mc_fields() {
	return array(
		'chart_no' => array( '차트번호', true, '' ),
		'pname'    => array( '성명 · 호칭', true, '호칭이 있으면 함께 (작가님 · 회장님 …) · 외국인은 부르는 이름' ),
		'addr'     => array( '주소', true, '도시 혹은 주소' ),
		'mhx'      => array( '병력', true, "현재 치료 중인 질환 · 복용 중인 약 · 항혈전제 · 주사제(골흡수억제제) · 수술·입원 병력 · 알러지 · 임신·수유 · 흡연 · 인공판막·스텐트·인공관절 · 지혈 지연 · 최근 검사 수치(혈압·혈당·HbA1c·INR)" ),
		'referral' => array( '가족 · 소개 · VIP', true, '가족 정보 · 소개자와의 관계 · 협력기관 · VIP/GOLD/SILVER' ),
		'dr'       => array( '담당의', true, "예) 임플란트: 문은수\n보철: 이창률" ),
		'tx_plan'  => array( '치료계획', false, '' ),
		'tx_hist'  => array( '치료이력', false, '' ),
		'memo'     => array( '참고사항', false, '환자 근황 · 직함·소속 · 참고할 점' ),
	);
}

/** 별표항목 중 비어 있는 것 (「.」 은 「없음」으로 적은 것이라 채운 것으로 본다) */
function md_mc_missing( $r ) {
	$out = array();
	foreach ( md_mc_fields() as $k => $f ) {
		if ( ! $f[1] || in_array( $k, array( 'chart_no', 'pname' ), true ) ) { continue; }
		if ( '' === trim( (string) ( isset( $r->$k ) ? $r->$k : '' ) ) ) { $out[] = $f[0]; }
	}
	return $out;
}

function md_mc_blank( $v ) {
	$v = trim( (string) $v );
	return '' === $v || '.' === $v || '-' === $v;
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
	update_option( 'md_mc_schema', MD_MC_SCHEMA );
	delete_option( 'md_mc_installing' );
}
add_action( 'init', 'md_mc_maybe_install', 20 );

/* ============================================================
 * 도움 함수
 * ============================================================ */

function md_mc_can_use()    { return function_exists( 'md_sup_can_use' ) && md_sup_can_use(); }
function md_mc_can_manage() { return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage(); }

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
	if ( md_mc_blank( $s ) ) { return '<span class="mc-none">—</span>'; }
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
		if ( '' === $row['chart_no'] ) { return new WP_Error( 'mc', '차트번호를 적어 주세요.' ); }
		if ( '' === $row['pname'] )    { return new WP_Error( 'mc', '성명을 적어 주세요.' ); }
		$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE kind = 'patient' AND chart_no = %s AND deleted_at IS NULL AND id <> %d LIMIT 1", $row['chart_no'], (int) $id ) );
		if ( $dup ) {
			return new WP_Error( 'mc_dup', '차트번호 ' . $row['chart_no'] . ' 은(는) 이미 있습니다.', (int) $dup );
		}
		$row['cho'] = md_mc_cho( $row['pname'] );
	}
	if ( isset( $data['pin'] ) ) { $row['pin'] = $data['pin'] ? 1 : 0; }
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
		$ok  = $wpdb->update( md_mc_t(), array(
			$field       => $new,
			'rev'        => (int) $cur->rev + 1,
			'updated_at' => current_time( 'mysql' ),
			'updated_by' => md_mc_me(),
		), array( 'id' => (int) $id, 'rev' => (int) $cur->rev ) );
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
	$wpdb->update( md_mc_t(), $upd, array( 'id' => (int) $cur->id ) );
	md_mc_log( $cur->id, 'revert', $cur );
	return (int) $cur->id;
}

/* ============================================================
 * 읽기
 * ============================================================ */

/** 목록 — 환자. $q 가 있으면 차트번호 · 이름(초성) · 내용까지 */
function md_mc_patients( $q = '', $filter = '' ) {
	global $wpdb;
	$t     = md_mc_t();
	$where = array( "kind = 'patient'", 'deleted_at IS NULL' );
	$args  = array();
	if ( 'pin' === $filter ) { $where[] = 'pin = 1'; }
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
	$order = 'recent' === $filter ? 'updated_at IS NULL, updated_at DESC, id DESC' : 'pin DESC, updated_at IS NULL, updated_at DESC, id DESC';
	$sql = "SELECT id, chart_no, pname, cho, pin, updated_at, updated_by, addr, mhx, referral, dr, tx_hist FROM $t WHERE " . implode( ' AND ', $where ) . " ORDER BY $order";
	return (array) $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql );
}

function md_mc_notes( $q = '' ) {
	global $wpdb;
	$t   = md_mc_t();
	$sql = "SELECT id, title, pin, updated_at, updated_by, body FROM $t WHERE kind = 'note' AND deleted_at IS NULL";
	if ( '' !== $q ) {
		$like = '%' . $wpdb->esc_like( $q ) . '%';
		$sql  = $wpdb->prepare( $sql . ' AND (title LIKE %s OR body LIKE %s)', $like, $like );
	}
	return (array) $wpdb->get_results( $sql . ' ORDER BY pin DESC, updated_at DESC, id ASC' );
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
				'tx_plan' => $g( 'plan' ), 'tx_hist' => $hist, 'memo' => $memo, 'title' => '', 'body' => '',
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
		$row['pin']        = $pin ? 1 : 0;
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
	if ( ! md_mc_can_manage() ) { wp_die( '관리자만 내려받을 수 있습니다.' ); }
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

	switch ( $action ) {
		case 'save':
			$kind = 'note' === ( $post['kind'] ?? '' ) ? 'note' : 'patient';
			$data = $post;
			$data['pin'] = ! empty( $post['pin'] );
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
				$back = md_mc_url( array( 'mv' => 'note' === $kind ? 'note' : 'p', 'mid' => $res, 'saved' => 1 ) );
			}
			break;

		case 'add':
			$field = sanitize_key( $post['field'] ?? '' );
			$res   = md_mc_add_line( $id, $field, $post['text'] ?? '', sanitize_text_field( $post['mcday'] ?? '' ) );
			$rec   = md_mc_get( $id );
			$view  = $rec && 'note' === $rec->kind ? 'note' : ( 'tablet' === ( $post['from'] ?? '' ) ? 'tablet' : 'p' );
			$back  = is_wp_error( $res ) ? $err( $res->get_error_message(), array( 'mv' => $view, 'mid' => $id ) ) : md_mc_url( array( 'mv' => $view, 'mid' => $id ) ) . '#f-' . $field;
			break;

		case 'pin':
			md_mc_set_pin( $id, ! empty( $post['on'] ) );
			$rec  = md_mc_get( $id );
			$back = md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'note' : 'p', 'mid' => $id ) );
			break;

		case 'delete':
			$rec = md_mc_get( $id );
			md_mc_trash( $id );
			$back = md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'notes' : '', 'gone' => $id ) );
			break;

		case 'restore':
			$res  = md_mc_untrash( $id );
			$rec  = md_mc_get( $id, true );
			$back = is_wp_error( $res ) ? $err( $res->get_error_message(), array( 'mv' => 'trash' ) ) : md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'note' : 'p', 'mid' => $id ) );
			break;

		case 'revert':
			$res  = md_mc_revert( (int) ( $post['lid'] ?? 0 ) );
			$rec  = is_wp_error( $res ) ? null : md_mc_get( $res );
			$back = is_wp_error( $res ) ? $err( $res->get_error_message(), array( 'mv' => 'log', 'mid' => $id ) ) : md_mc_url( array( 'mv' => $rec && 'note' === $rec->kind ? 'note' : 'p', 'mid' => $res, 'reverted' => 1 ) );
			break;

		case 'purge':
			if ( md_mc_can_manage() ) {
				global $wpdb;
				$wpdb->query( 'DELETE FROM ' . md_mc_t() . ' WHERE deleted_at IS NOT NULL' );
			}
			$back = md_mc_url( array( 'mv' => 'trash' ) );
			break;

		case 'import':
			if ( ! md_mc_can_manage() ) { $back = md_mc_url(); break; }
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
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_mc_handle_post', 1 );

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

/** 태블릿 보기에서는 라운지 머리·꼬리를 감춘다 */
function md_mc_body_class( $c ) {
	if ( function_exists( 'md_sup_current_app' ) && 'minichart' === md_sup_current_app() && 'tablet' === ( $_GET['mv'] ?? '' ) ) { $c[] = 'mc-tablet-mode'; }
	return $c;
}
add_filter( 'body_class', 'md_mc_body_class' );

function md_mc_nonce_fields( $action, $id = 0 ) {
	echo '<input type="hidden" name="md_mc_action" value="' . esc_attr( $action ) . '">';
	echo '<input type="hidden" name="md_mc_nonce" value="' . esc_attr( wp_create_nonce( 'md_mc_' . $action ) ) . '">';
	if ( $id ) { echo '<input type="hidden" name="mid" value="' . (int) $id . '">'; }
}

function md_mc_render() {
	if ( (int) get_option( 'md_mc_schema', 0 ) < MD_MC_SCHEMA ) { md_mc_maybe_install(); }
	$mv  = isset( $_GET['mv'] ) ? sanitize_key( wp_unslash( $_GET['mv'] ) ) : '';
	$mid = isset( $_GET['mid'] ) ? (int) $_GET['mid'] : 0;

	echo '<div class="mc">';
	if ( isset( $_GET['mcerr'] ) ) {
		echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['mcerr'] ) ) . '</div>';
	}
	if ( 'tablet' !== $mv ) { md_mc_render_nav( $mv ); }

	switch ( $mv ) {
		case 'p':      md_mc_render_patient( $mid ); break;
		case 'tablet': md_mc_render_tablet( $mid ); break;
		case 'edit':   md_mc_render_edit( $mid, isset( $_GET['mk'] ) && 'note' === $_GET['mk'] ? 'note' : 'patient' ); break;
		case 'notes':  md_mc_render_notes(); break;
		case 'note':   md_mc_render_note( $mid ); break;
		case 'log':    md_mc_render_log( $mid ); break;
		case 'trash':  md_mc_render_trash(); break;
		case 'admin':  md_mc_can_manage() ? md_mc_render_admin() : md_mc_render_list(); break;
		default:       md_mc_render_list();
	}
	echo '</div>';
}

function md_mc_render_nav( $mv ) {
	$c    = md_mc_counts();
	$tabs = array(
		''      => array( '환자', $c['patient'] ),
		'notes' => array( '팀 노트', $c['note'] ),
		'trash' => array( '휴지통', $c['trash'] ),
	);
	if ( md_mc_can_manage() ) { $tabs['admin'] = array( '가져오기 · 엑셀', null ); }
	$on = in_array( $mv, array( 'p', 'edit', 'log', 'tablet' ), true ) ? '' : ( 'note' === $mv ? 'notes' : $mv );
	if ( 'edit' === $mv && isset( $_GET['mk'] ) && 'note' === $_GET['mk'] ) { $on = 'notes'; }
	?>
	<nav class="mc-nav" aria-label="미니차트 메뉴">
		<?php foreach ( $tabs as $k => $tb ) : ?>
			<a class="mc-nav__a<?php echo $on === $k ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_mc_url( array( 'mv' => $k ) ) ); ?>"><?php echo esc_html( $tb[0] ); ?><?php if ( null !== $tb[1] ) : ?> <b><?php echo (int) $tb[1]; ?></b><?php endif; ?></a>
		<?php endforeach; ?>
		<a class="mds-btn mds-btn--fill mc-nav__new" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'mk' => 'notes' === $on ? 'note' : '' ) ) ); ?>">＋ <?php echo 'notes' === $on ? '새 노트' : '새 환자'; ?></a>
	</nav>
	<?php
}

/** 맨 위에 고정된 팀 노트 — 환자 목록 위에 띠로 */
function md_mc_render_pinned_notes() {
	$notes = array_filter( md_mc_notes(), function ( $n ) { return (int) $n->pin; } );
	if ( ! $notes ) { return; }
	echo '<div class="mc-pins" aria-label="고정한 팀 노트">';
	foreach ( $notes as $n ) {
		$fresh = $n->updated_at && strtotime( $n->updated_at ) > current_time( 'timestamp' ) - 3 * DAY_IN_SECONDS;
		echo '<a class="mc-pin' . ( $fresh ? ' is-fresh' : '' ) . '" href="' . esc_url( md_mc_url( array( 'mv' => 'note', 'mid' => $n->id ) ) ) . '">' . esc_html( $n->title ) . ( $fresh ? ' <i>new</i>' : '' ) . '</a>';
	}
	echo '</div>';
}

function md_mc_render_list() {
	$q      = isset( $_GET['mq'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['mq'] ) ) ) : '';
	$filter = isset( $_GET['mf'] ) ? sanitize_key( wp_unslash( $_GET['mf'] ) ) : '';
	if ( ! in_array( $filter, array( '', 'pin', 'recent', 'todo' ), true ) ) { $filter = ''; }
	$rows   = md_mc_patients( $q, $filter );
	if ( 'todo' === $filter ) { $rows = array_values( array_filter( $rows, function ( $r ) { return (bool) md_mc_missing( $r ); } ) ); }
	$c      = md_mc_counts();

	if ( isset( $_GET['gone'] ) ) {
		echo '<div class="mds-notice mds-notice--ok">휴지통으로 옮겼습니다. <a href="' . esc_url( md_mc_url( array( 'mv' => 'trash' ) ) ) . '">휴지통에서 되살리기</a></div>';
	}
	if ( ! $c['patient'] && ! $c['note'] ) {
		echo '<div class="mds-card mc-empty-start"><p>아직 미니차트 자료가 없습니다.</p>'
			. ( md_mc_can_manage() ? '<p><a class="mds-btn mds-btn--fill" href="' . esc_url( md_mc_url( array( 'mv' => 'admin' ) ) ) . '">AppSheet 자료 가져오기</a></p>' : '<p>관리자가 AppSheet 자료를 가져오면 여기에 보입니다.</p>' )
			. '</div>';
	}
	md_mc_render_pinned_notes();
	?>
	<form method="get" class="mc-search" action="<?php echo esc_url( md_mc_url() ); ?>" role="search">
		<?php md_sup_app_field(); ?>
		<?php if ( $filter ) : ?><input type="hidden" name="mf" value="<?php echo esc_attr( $filter ); ?>"><?php endif; ?>
		<input type="search" name="mq" id="mc-q" value="<?php echo esc_attr( $q ); ?>" placeholder="차트번호 · 이름 · 초성(ㄱㅁㅅ)" autocomplete="off" aria-label="환자 찾기" <?php echo '' === $q ? 'autofocus' : ''; ?>>
		<button type="submit" class="mds-btn mc-search__btn" title="병력 · 치료이력 · 참고사항까지 찾기">내용까지 찾기</button>
	</form>
	<nav class="mc-chips" aria-label="보기">
		<?php
		$chips = array( '' => '전체 ' . $c['patient'], 'pin' => '📌 팔로우업 ' . $c['pin'], 'recent' => '최근 수정', 'todo' => '별표항목 미입력' );
		foreach ( $chips as $k => $label ) {
			echo '<a class="mdsp-chip' . ( $filter === $k ? ' is-on' : '' ) . '" href="' . esc_url( md_mc_url( array( 'mf' => $k, 'mq' => $q ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		?>
	</nav>
	<?php if ( '' !== $q ) : ?>
		<p class="mc-found">「<?php echo esc_html( $q ); ?>」 <?php echo count( $rows ); ?>명 · <a href="<?php echo esc_url( md_mc_url( array( 'mf' => $filter ) ) ); ?>">지우기</a></p>
	<?php endif; ?>
	<?php if ( ! $rows ) : ?>
		<div class="mds-card"><div class="mds-empty"><?php echo '' !== $q ? '찾는 환자가 없습니다.' : '해당하는 환자가 없습니다.'; ?></div></div>
		<?php if ( '' !== $q && preg_match( '/^\d+$/', $q ) ) : ?>
			<p><a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'chart' => $q ) ) ); ?>">＋ 차트번호 <?php echo esc_html( $q ); ?> 새 환자로 만들기</a></p>
		<?php endif; ?>
	<?php else : ?>
		<ul class="mc-list" id="mc-list">
			<?php foreach ( $rows as $r ) :
				$miss = md_mc_missing( $r );
				$last = md_mc_last_line( $r->tx_hist );
				$key  = $r->chart_no . ' ' . preg_replace( '/\s+/u', '', $r->pname ) . ' ' . $r->cho;
				?>
				<li data-k="<?php echo esc_attr( mb_strtolower( $key ) ); ?>">
					<a class="mc-row" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'p', 'mid' => $r->id ) ) ); ?>">
						<span class="mc-row__no"><?php echo esc_html( $r->chart_no ); ?></span>
						<span class="mc-row__name"><?php echo $r->pin ? '<span class="mc-row__pin" title="팔로우업">📌</span>' : ''; ?><?php echo esc_html( $r->pname ); ?><?php if ( $miss ) : ?><span class="mc-miss" title="미입력: <?php echo esc_attr( implode( ', ', $miss ) ); ?>">★<?php echo count( $miss ); ?></span><?php endif; ?></span>
						<span class="mc-row__last"><?php echo esc_html( $last ); ?></span>
						<span class="mc-row__upd"><?php echo esc_html( md_mc_short_date( $r->updated_at ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="mc-more-wrap" hidden><button type="button" class="mds-btn mc-more">더 보기</button></p>
		<p class="mc-nohit mds-hint" hidden>이름·차트번호로는 없습니다. 「내용까지 찾기」를 눌러 병력·치료이력·참고사항에서 찾아보세요.</p>
	<?php endif; ?>
	<?php
}

/** 칸 하나 (읽기) */
function md_mc_block( $label, $html, $cls = '', $id = '' ) {
	echo '<section class="mc-block ' . esc_attr( $cls ) . '"' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '><h3 class="mc-block__h">' . esc_html( $label ) . '</h3><div class="mc-block__b">' . $html . '</div></section>'; // phpcs:ignore
}

/** 한 줄 추가 폼 — 날짜(오늘) + 내용 */
function md_mc_addform( $r, $field, $label, $from = '' ) {
	?>
	<form method="post" class="mc-add" action="<?php echo esc_url( md_mc_url() ); ?>">
		<?php md_mc_nonce_fields( 'add', $r->id ); ?>
		<input type="hidden" name="field" value="<?php echo esc_attr( $field ); ?>">
		<?php if ( $from ) : ?><input type="hidden" name="from" value="<?php echo esc_attr( $from ); ?>"><?php endif; ?>
		<input type="date" name="mcday" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" aria-label="날짜" class="mc-add__day">
		<textarea name="text" rows="1" required placeholder="<?php echo esc_attr( $label ); ?> 추가 — 날짜와 함께 맨 위에 붙습니다" class="mc-add__text"></textarea>
		<button type="submit" class="mds-btn mds-btn--fill mc-add__btn">추가</button>
	</form>
	<?php
}

function md_mc_render_patient( $id ) {
	$r = md_mc_get( $id );
	if ( ! $r || 'patient' !== $r->kind ) { md_mc_render_gone( $id ); return; }
	$f    = md_mc_fields();
	$miss = md_mc_missing( $r );
	if ( isset( $_GET['saved'] ) )    { echo '<div class="mds-notice mds-notice--ok">저장했습니다.</div>'; }
	if ( isset( $_GET['reverted'] ) ) { echo '<div class="mds-notice mds-notice--ok">이전 내용으로 되돌렸습니다.</div>'; }
	?>
	<article class="mc-chart">
		<header class="mds-card mc-chart__head">
			<div class="mc-chart__id">
				<button type="button" class="mc-copy" data-copy="<?php echo esc_attr( $r->chart_no ); ?>" title="차트번호 복사"><?php echo esc_html( $r->chart_no ); ?></button>
				<h2 class="mc-chart__name"><?php echo esc_html( $r->pname ); ?></h2>
				<?php if ( $r->pin ) : ?><span class="mc-tag mc-tag--pin">📌 팔로우업</span><?php endif; ?>
			</div>
			<div class="mc-chart__acts">
				<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'tablet', 'mid' => $r->id ) ) ); ?>">태블릿 보기</a>
				<a class="mds-btn" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'mid' => $r->id ) ) ); ?>">수정</a>
				<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>">
					<?php md_mc_nonce_fields( 'pin', $r->id ); ?><input type="hidden" name="on" value="<?php echo $r->pin ? '' : '1'; ?>">
					<button type="submit" class="mds-btn"><?php echo $r->pin ? '팔로우업 해제' : '📌 팔로우업'; ?></button>
				</form>
			</div>
			<p class="mc-chart__meta">마지막 수정 <?php echo esc_html( $r->updated_at ? md_mc_short_date( $r->updated_at ) . ' ' . date( 'H:i', strtotime( $r->updated_at ) ) : '—' ); ?><?php echo $r->updated_by ? ' · ' . esc_html( $r->updated_by ) : ''; ?> · <a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'log', 'mid' => $r->id ) ) ); ?>">변경 기록</a></p>
		</header>

		<?php if ( $miss ) : ?>
			<div class="mds-notice mds-notice--warn mc-missnote">별표항목 미입력: <b><?php echo esc_html( implode( ' · ', $miss ) ); ?></b> — <a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'mid' => $r->id ) ) ); ?>">채우기</a></div>
		<?php endif; ?>

		<div class="mc-grid">
			<?php
			$mhx_real = ! md_mc_blank( $r->mhx ) && ! in_array( strtolower( trim( (string) $r->mhx ) ), array( 'x', '없음', 'n/a' ), true );
			md_mc_block( $f['mhx'][0], md_mc_text( $r->mhx ), $mhx_real ? 'mc-block--alert' : '' );
			md_mc_block( $f['dr'][0], md_mc_text( $r->dr ) );
			md_mc_block( $f['referral'][0], md_mc_text( $r->referral ) );
			md_mc_block( $f['addr'][0], md_mc_text( $r->addr ) );
			?>
		</div>
		<?php md_mc_block( $f['tx_plan'][0], md_mc_text( $r->tx_plan ), 'mc-block--plan' ); ?>

		<section class="mc-block mc-block--log" id="f-tx_hist">
			<h3 class="mc-block__h"><?php echo esc_html( $f['tx_hist'][0] ); ?></h3>
			<?php md_mc_addform( $r, 'tx_hist', $f['tx_hist'][0] ); ?>
			<div class="mc-block__b"><?php echo md_mc_text( $r->tx_hist ); // phpcs:ignore ?></div>
		</section>
		<section class="mc-block mc-block--log" id="f-memo">
			<h3 class="mc-block__h"><?php echo esc_html( $f['memo'][0] ); ?></h3>
			<?php md_mc_addform( $r, 'memo', $f['memo'][0] ); ?>
			<div class="mc-block__b"><?php echo md_mc_text( $r->memo ); // phpcs:ignore ?></div>
		</section>

		<div class="mc-chart__foot">
			<a href="<?php echo esc_url( md_mc_url() ); ?>">← 환자 목록</a>
			<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('<?php echo esc_js( $r->pname . ' (' . $r->chart_no . ')' ); ?> 차트를 휴지통으로 옮길까요? 휴지통에서 되살릴 수 있습니다.');">
				<?php md_mc_nonce_fields( 'delete', $r->id ); ?>
				<button type="submit" class="mc-del">🗑 삭제</button>
			</form>
		</div>
	</article>
	<?php
}

/** 태블릿 보기 — 체어 옆에서 담당의가 보는 큰 글씨 화면 */
function md_mc_render_tablet( $id ) {
	$r = md_mc_get( $id );
	if ( ! $r || 'patient' !== $r->kind ) { md_mc_render_gone( $id ); return; }
	$f = md_mc_fields();
	$mhx_real = ! md_mc_blank( $r->mhx ) && ! in_array( strtolower( trim( (string) $r->mhx ) ), array( 'x', '없음', 'n/a' ), true );
	?>
	<article class="mc-tab">
		<header class="mc-tab__head">
			<a class="mc-tab__close" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'p', 'mid' => $r->id ) ) ); ?>">✕ 닫기</a>
			<div>
				<span class="mc-tab__no"><?php echo esc_html( $r->chart_no ); ?></span>
				<h2 class="mc-tab__name"><?php echo esc_html( $r->pname ); ?></h2>
			</div>
			<button type="button" class="mc-tab__size" data-size title="글자 크기">가<b>가</b></button>
		</header>
		<div class="mc-tab__grid">
			<?php md_mc_block( $f['mhx'][0], md_mc_text( $r->mhx ), $mhx_real ? 'mc-block--alert' : '' ); ?>
			<?php md_mc_block( $f['tx_plan'][0], md_mc_text( $r->tx_plan ), 'mc-block--plan' ); ?>
			<?php md_mc_block( $f['tx_hist'][0], md_mc_text( $r->tx_hist ), 'mc-block--hist' ); ?>
			<?php md_mc_block( $f['memo'][0], md_mc_text( $r->memo ) ); ?>
			<?php md_mc_block( $f['referral'][0], md_mc_text( $r->referral ) ); ?>
			<?php md_mc_block( $f['dr'][0], md_mc_text( $r->dr ) ); ?>
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

/** 수정 · 새로 만들기 */
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
	$v = function ( $k ) use ( $vals ) { return isset( $vals[ $k ] ) ? (string) $vals[ $k ] : ''; };
	$back = $r ? md_mc_url( array( 'mv' => 'note' === $kind ? 'note' : 'p', 'mid' => $r->id ) ) : md_mc_url( array( 'mv' => 'note' === $kind ? 'notes' : '' ) );
	?>
	<form method="post" class="mds-card mc-form" action="<?php echo esc_url( md_mc_url() ); ?>">
		<?php md_mc_nonce_fields( 'save', $r ? $r->id : 0 ); ?>
		<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
		<input type="hidden" name="rev" value="<?php echo $r ? (int) $r->rev : 0; ?>">
		<h2 class="mc-form__h"><?php echo $r ? ( 'note' === $kind ? '노트 수정' : esc_html( $r->pname ) . ' 차트 수정' ) : ( 'note' === $kind ? '새 팀 노트' : '새 환자' ); ?></h2>

		<?php if ( 'note' === $kind ) : ?>
			<label class="mdsp-field"><span>제목 <em class="mdsp-req">*</em></span><input type="text" name="title" required maxlength="250" value="<?php echo esc_attr( $v( 'title' ) ); ?>" placeholder="예) 팀 피드 ★★ · 임플란트 프로토콜 ★ · 홍길동 · 개인노트"></label>
			<label class="mdsp-field"><span>본문</span><textarea name="body" rows="16" data-grow><?php echo esc_textarea( $v( 'body' ) ); ?></textarea></label>
			<?php if ( $conflict ) : ?><details class="mc-latest"><summary>저장된 최신 내용</summary><div><?php echo md_mc_text( $r->body ); // phpcs:ignore ?></div></details><?php endif; ?>
			<label class="mc-check"><input type="checkbox" name="pin" value="1" <?php checked( (bool) ( $vals['pin'] ?? 0 ) ); ?>> 📌 환자 목록 위에 고정</label>
		<?php else : ?>
			<div class="mc-form__row">
				<label class="mdsp-field"><span>차트번호 <em class="mdsp-req">*</em></span><input type="text" name="chart_no" required inputmode="numeric" maxlength="60" value="<?php echo esc_attr( $v( 'chart_no' ) ); ?>" <?php echo $r ? '' : 'autofocus'; ?>></label>
				<label class="mdsp-field"><span>성명 · 호칭 <em class="mdsp-req">*</em></span><input type="text" name="pname" required maxlength="250" value="<?php echo esc_attr( $v( 'pname' ) ); ?>" placeholder="홍길동 · 홍길동 작가님"></label>
			</div>
			<?php foreach ( md_mc_fields() as $k => $fd ) :
				if ( in_array( $k, array( 'chart_no', 'pname' ), true ) ) { continue; }
				$rows = in_array( $k, array( 'tx_hist', 'memo' ), true ) ? 6 : ( 'addr' === $k ? 1 : 3 );
				?>
				<label class="mdsp-field"><span><?php echo esc_html( $fd[0] ); ?><?php if ( $fd[1] ) : ?> <em class="mc-star" title="별표항목 — 진료팀 필수 입력">★</em><?php endif; ?><?php if ( in_array( $k, array( 'tx_hist', 'memo' ), true ) ) : ?> <small>한 줄씩 「260422: 내용」 · 최근 것이 위</small><?php endif; ?></span>
					<textarea name="<?php echo esc_attr( $k ); ?>" rows="<?php echo (int) $rows; ?>" data-grow placeholder="<?php echo esc_attr( $fd[2] ); ?>"><?php echo esc_textarea( $v( $k ) ); ?></textarea></label>
				<?php if ( $conflict && (string) $r->$k !== $v( $k ) ) : ?><details class="mc-latest"><summary>저장된 최신 내용 (다름)</summary><div><?php echo md_mc_text( $r->$k ); // phpcs:ignore ?></div></details><?php endif; ?>
			<?php endforeach; ?>
			<label class="mc-check"><input type="checkbox" name="pin" value="1" <?php checked( (bool) ( $vals['pin'] ?? 0 ) ); ?>> 📌 팔로우업 (목록 맨 위에 고정)</label>
			<p class="mds-hint">★ 별표항목은 진료팀이 담당의 호출 전에 채웁니다. 비어 있으면 목록과 차트에 「미입력」으로 표시됩니다.</p>
		<?php endif; ?>

		<div class="mc-form__foot">
			<button type="submit" class="mds-btn mds-btn--fill mc-form__save">저장</button>
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( $back ); ?>">취소</a>
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
		<input type="search" name="mq" value="<?php echo esc_attr( $q ); ?>" placeholder="노트에서 찾기 (제목 · 본문)" aria-label="노트 찾기">
		<button type="submit" class="mds-btn mc-search__btn">찾기</button>
	</form>
	<?php
	if ( ! $notes ) { echo '<div class="mds-card"><div class="mds-empty">노트가 없습니다.</div></div>'; return; }
	$groups = array( '📌 고정한 노트' => array(), '그 밖의 노트' => array() );
	foreach ( $notes as $n ) { $groups[ $n->pin ? '📌 고정한 노트' : '그 밖의 노트' ][] = $n; }
	foreach ( $groups as $g => $list ) {
		if ( ! $list ) { continue; }
		echo '<h2 class="mdsp-group">' . esc_html( $g ) . '</h2><ul class="mc-notes">';
		foreach ( $list as $n ) {
			$first = trim( (string) strtok( (string) $n->body, "\n" ) );
			echo '<li><a class="mc-note-row" href="' . esc_url( md_mc_url( array( 'mv' => 'note', 'mid' => $n->id ) ) ) . '"><b>' . esc_html( $n->title ) . '</b><span>' . esc_html( mb_substr( $first, 0, 80 ) ) . '</span><time>' . esc_html( md_mc_short_date( $n->updated_at ) ) . '</time></a></li>';
		}
		echo '</ul>';
	}
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
			<div class="mc-chart__acts">
				<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_mc_url( array( 'mv' => 'edit', 'mid' => $r->id ) ) ); ?>">수정</a>
				<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>">
					<?php md_mc_nonce_fields( 'pin', $r->id ); ?><input type="hidden" name="on" value="<?php echo $r->pin ? '' : '1'; ?>">
					<button type="submit" class="mds-btn"><?php echo $r->pin ? '고정 해제' : '📌 고정'; ?></button>
				</form>
			</div>
		</header>
		<p class="mc-chart__meta">마지막 수정 <?php echo esc_html( $r->updated_at ? md_mc_short_date( $r->updated_at ) . ' ' . date( 'H:i', strtotime( $r->updated_at ) ) : '—' ); ?><?php echo $r->updated_by ? ' · ' . esc_html( $r->updated_by ) : ''; ?> · <a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'log', 'mid' => $r->id ) ) ); ?>">변경 기록</a></p>
		<div id="f-body"><?php md_mc_addform( $r, 'body', '오늘 날짜로 맨 위에' ); ?></div>
		<div class="mc-note__body"><?php echo md_mc_text( $r->body ); // phpcs:ignore ?></div>
		<div class="mc-chart__foot">
			<a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'notes' ) ) ); ?>">← 팀 노트</a>
			<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('이 노트를 휴지통으로 옮길까요?');">
				<?php md_mc_nonce_fields( 'delete', $r->id ); ?><button type="submit" class="mc-del">🗑 삭제</button>
			</form>
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
	$acts  = array( 'new' => '새로 만듦', 'edit' => '수정', 'add' => '한 줄 추가', 'pin' => '고정', 'unpin' => '고정 해제', 'delete' => '삭제', 'restore' => '되살림', 'revert' => '되돌림', 'import' => '가져오기' );
	$title = 'note' === $r->kind ? $r->title : $r->chart_no . ' ' . $r->pname;
	$keys  = 'note' === $r->kind ? array( 'title' => '제목', 'body' => '본문' ) : array_map( function ( $f ) { return $f[0]; }, md_mc_fields() );
	?>
	<h2 class="mdsp-group">변경 기록 · <?php echo esc_html( $title ); ?></h2>
	<p class="mds-hint">각 줄은 그때 <b>고치기 전</b> 내용입니다. 「이 내용으로 되돌리기」를 누르면 그 내용으로 돌아가고, 지금 내용도 기록에 남습니다.</p>
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
				<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('이 시점 내용으로 되돌릴까요?');">
					<?php md_mc_nonce_fields( 'revert', $r->id ); ?><input type="hidden" name="lid" value="<?php echo (int) $lg->id; ?>">
					<button type="submit" class="mds-btn">이 내용으로 되돌리기</button>
				</form>
			<?php else : ?>
				<p class="mds-hint">이전 내용 없음</p>
			<?php endif; ?>
		</details>
	<?php endforeach; ?>
	<p><a href="<?php echo esc_url( md_mc_url( array( 'mv' => 'note' === $r->kind ? 'note' : 'p', 'mid' => $r->id ) ) ); ?>">← 돌아가기</a></p>
	<?php
}

function md_mc_render_trash() {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT id, kind, chart_no, pname, title, deleted_at, updated_by FROM ' . md_mc_t() . ' WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT 300' );
	?>
	<h2 class="mdsp-group">휴지통 <small>지운 차트 · 노트 — 되살릴 수 있습니다</small></h2>
	<?php if ( ! $rows ) : ?>
		<div class="mds-card"><div class="mds-empty">비어 있습니다.</div></div>
	<?php else : ?>
		<ul class="mc-notes">
			<?php foreach ( $rows as $r ) : ?>
				<li class="mc-trash-row">
					<span><b><?php echo esc_html( 'note' === $r->kind ? $r->title : $r->chart_no . ' ' . $r->pname ); ?></b> <small><?php echo esc_html( md_mc_short_date( $r->deleted_at ) . ' · ' . $r->updated_by ); ?></small></span>
					<form method="post" class="mc-inline" action="<?php echo esc_url( md_mc_url() ); ?>">
						<?php md_mc_nonce_fields( 'restore', $r->id ); ?><button type="submit" class="mds-btn">되살리기</button>
					</form>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( md_mc_can_manage() ) : ?>
			<form method="post" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return confirm('휴지통의 <?php echo count( $rows ); ?>건을 영구히 지울까요? 되돌릴 수 없습니다.');">
				<?php md_mc_nonce_fields( 'purge' ); ?><button type="submit" class="mc-del">휴지통 비우기 (관리자)</button>
			</form>
		<?php endif; ?>
	<?php endif;
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
		<h2 class="mc-form__h">AppSheet 자료 가져오기</h2>
		<ol class="mc-steps">
			<li>구글 시트 「Mini Chart」를 엽니다 (moondentaldigital 계정).</li>
			<li>파일 › 다운로드 › <b>Microsoft Excel (.xlsx)</b></li>
			<li>받은 파일을 아래에서 고르고 「가져오기」</li>
		</ol>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( md_mc_url() ); ?>" onsubmit="return this.mode.value!=='replace' || confirm('지금 있는 미니차트 <?php echo (int) ( $c['patient'] + $c['note'] ); ?>건을 지우고 파일 내용으로 바꿀까요? (지운 내용은 백업으로 남습니다)');">
			<?php md_mc_nonce_fields( 'import' ); ?>
			<input type="file" name="file" accept=".xlsx" required>
			<fieldset class="mc-modes">
				<label><input type="radio" name="mode" value="replace" checked> <b>전부 바꾸기</b> — 라운지 내용을 지우고 파일대로 (AppSheet 에서 라운지로 옮기는 날)</label>
				<label><input type="radio" name="mode" value="merge"> <b>합치기</b> — 같은 AppSheet id 는 파일 내용으로 덮어쓰고, 없는 것은 추가</label>
			</fieldset>
			<button type="submit" class="mds-btn mds-btn--fill">가져오기</button>
		</form>
		<?php if ( is_array( $last ) ) : ?>
			<p class="mds-hint">마지막 가져오기: <?php echo esc_html( $last['at'] . ' · ' . $last['who'] . ' · ' . ( 'merge' === $last['mode'] ? '합치기' : '전부 바꾸기' ) ); ?></p>
		<?php endif; ?>
	</section>
	<section class="mds-card mc-admin">
		<h2 class="mc-form__h">엑셀로 내려받기</h2>
		<p>환자 <?php echo (int) $c['patient']; ?>명 · 노트 <?php echo (int) $c['note']; ?>장 — 환자 개인정보가 들어 있으니 병원 PC 에만 보관하세요.</p>
		<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( add_query_arg( array( 'md_mc_dl' => 1, '_n' => wp_create_nonce( 'md_mc_dl' ) ), md_mc_url() ) ); ?>">엑셀 내려받기</a>
	</section>
	<?php
}
