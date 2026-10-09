<?php
/**
 * v4.18 · 직원 라운지 · 직원 정보 (관리자)
 *
 *  이름 · 부서 · 직책 · 생일 · 입사일을 관리자가 넣고 고치고 뺀다. 달력은 이 표에서
 *  생일(🎂)과 입사 기념일(🎉 N주년)을 자동으로 읽는다 — 따로 일정을 넣을 필요가 없다.
 *  처음 한 번은 홈페이지 의료진 페이지의 명단(원장 + 직원)을 그대로 가져와 채운다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_STAFF_SCHEMA', 3 ); /* v4.18.2 · phone · email · photo / 3 · dw_id (덴트웹 직원 번호) */

function md_staff_table() { global $wpdb; return $wpdb->prefix . 'md_staff'; }

function md_staff_can_manage() { return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage(); }

/* ============================================================
 * 테이블 · 첫 명단 가져오기
 * ============================================================ */
function md_staff_maybe_install() {
	if ( (int) get_option( 'md_staff_schema', 0 ) >= MD_STAFF_SCHEMA ) { return; }
	if ( ! add_option( 'md_staff_installing', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'md_staff_installing' ) < 300 ) { return; }
		delete_option( 'md_staff_installing' );
		if ( ! add_option( 'md_staff_installing', time(), '', 'no' ) ) { return; }
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = md_staff_table();
	dbDelta( "CREATE TABLE $t (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(60) NOT NULL DEFAULT '',
		dept VARCHAR(60) NOT NULL DEFAULT '',
		position VARCHAR(60) NOT NULL DEFAULT '',
		birthday DATE NULL,
		hired DATE NULL,
		active TINYINT(1) NOT NULL DEFAULT 1,
		sort INT NOT NULL DEFAULT 0,
		note VARCHAR(200) NOT NULL DEFAULT '',
		phone VARCHAR(40) NOT NULL DEFAULT '',
		email VARCHAR(120) NOT NULL DEFAULT '',
		photo VARCHAR(255) NOT NULL DEFAULT '',
		dw_id INT NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY active (active),
		KEY birthday (birthday),
		KEY hired (hired)
	) " . $wpdb->get_charset_collate() . ';' );
	if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ) ) { md_staff_import_from_site(); }
	update_option( 'md_staff_schema', MD_STAFF_SCHEMA );
	delete_option( 'md_staff_installing' );
}
add_action( 'init', 'md_staff_maybe_install', 21 );

/* ============================================================
 * v10.0 · 직원 공용 계정(직원공용)도 명단에 한 줄 (원장 지시)
 *  홈페이지 의료진 페이지 · 만족도 담당자 · 가입 승인 「명단과 연결」에는 넣지 않는다.
 * ============================================================ */
define( 'MD_STAFF_SHARED_MAIL', 'moondental1995@naver.com' );

function md_staff_is_shared( $r ) {
	$n = defined( 'MD_SUP_STAFF_NAME' ) ? MD_SUP_STAFF_NAME : '직원공용';
	return $r && preg_replace( '/\s+/u', '', (string) $r->name ) === preg_replace( '/\s+/u', '', $n );
}

function md_staff_add_shared_once() {
	if ( get_option( 'md_staff_shared_v1' ) || (int) get_option( 'md_staff_schema', 0 ) < MD_STAFF_SCHEMA ) { return; }
	global $wpdb;
	$t = md_staff_table();
	$have = false;
	foreach ( md_staff_all() as $r ) { if ( md_staff_is_shared( $r ) ) { $have = (int) $r->id; break; } }
	if ( ! $have ) {
		$wpdb->insert( $t, array(
			'name'       => defined( 'MD_SUP_STAFF_NAME' ) ? MD_SUP_STAFF_NAME : '직원공용',
			'dept'       => '기타',
			'position'   => '공용 계정',
			'email'      => MD_STAFF_SHARED_MAIL,
			'note'       => '직원 공용 로그인 계정',
			'sort'       => (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort),0) FROM $t" ) + 1,
			'created_at' => current_time( 'mysql' ),
		) );
		$have = (int) $wpdb->insert_id;
	} else {
		$wpdb->update( $t, array( 'email' => MD_STAFF_SHARED_MAIL, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $have ) );
	}
	/* 계정 이메일도 같은 주소로 (v8.8 에서 한 번 맞췄지만 다시 확인) */
	$u = defined( 'MD_SUP_STAFF_LOGIN' ) ? get_user_by( 'login', MD_SUP_STAFF_LOGIN ) : null;
	if ( $u ) {
		update_user_meta( $u->ID, 'md_staff_id', $have );
		$other = email_exists( MD_STAFF_SHARED_MAIL );
		if ( $u->user_email !== MD_STAFF_SHARED_MAIL && ( ! $other || (int) $other === (int) $u->ID ) ) { wp_update_user( array( 'ID' => $u->ID, 'user_email' => MD_STAFF_SHARED_MAIL ) ); }
	}
	update_option( 'md_staff_shared_v1', $have ? 'sid ' . $have : 'fail', false );
}
add_action( 'init', 'md_staff_add_shared_once', 23 );

/** 홈페이지 명단(원장 + 직원)에서 아직 없는 사람만 추가. 돌려주는 값: 추가된 수 */
function md_staff_import_from_site() {
	global $wpdb;
	$t     = md_staff_table();
	$have  = array();
	foreach ( (array) $wpdb->get_results( "SELECT name, dept FROM $t" ) as $r ) { $have[ $r->name . '|' . $r->dept ] = 1; }
	$added = 0; $sort = (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort),0) FROM $t" );

	/* 원장 — 의료진 데이터 */
	$docs = function_exists( 'moondental_get_team_with_customizer' ) ? moondental_get_team_with_customizer() : ( function_exists( 'moondental_get_team' ) ? moondental_get_team() : array() );
	foreach ( (array) $docs as $d ) {
		$name = trim( (string) ( $d['name'] ?? '' ) );
		if ( '' === $name || isset( $have[ $name . '|의료진' ] ) ) { continue; }
		$wpdb->insert( $t, array( 'name' => $name, 'dept' => '의료진', 'position' => mb_substr( trim( (string) ( $d['role'] ?? '원장' ) ), 0, 60 ), 'sort' => ++$sort, 'created_at' => current_time( 'mysql' ) ) );
		$have[ $name . '|의료진' ] = 1; $added++;
	}
	/* 직원 — 의료진 페이지의 직원 명단 (부서|직책|이름) */
	$raw = (string) get_theme_mod( 'md_content_staff_list', '' );
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || '#' === $line[0] ) { continue; }
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( count( $p ) < 3 || '' === $p[2] || false !== strpos( $p[2], 'OOO' ) ) { continue; }
		$dept = '경영지원본부' === $p[0] ? '경영지원실' : $p[0];
		if ( isset( $have[ $p[2] . '|' . $dept ] ) ) { continue; }
		$wpdb->insert( $t, array( 'name' => mb_substr( $p[2], 0, 60 ), 'dept' => mb_substr( $dept, 0, 60 ), 'position' => mb_substr( $p[1], 0, 60 ), 'sort' => ++$sort, 'created_at' => current_time( 'mysql' ) ) );
		$have[ $p[2] . '|' . $dept ] = 1; $added++;
	}
	return $added;
}

/**
 * v4.18.1 · 직원 정보 → 홈페이지 의료진 페이지의 직원 명단(부서|직책|이름) 자동 반영.
 *  원장(의료진)은 사진 · 약력과 함께 코드에서 관리하므로 여기서는 건드리지 않는다.
 */
function md_staff_sync_site() {
	$order = array( '진료실', '기공실', '서비스지원실', '경영지원실', '관리사무소', '예방과', '기타' );
	$by    = array();
	foreach ( md_staff_all( true ) as $r ) {
		if ( '의료진' === $r->dept || '' === trim( $r->name ) || md_staff_is_shared( $r ) ) { continue; } /* v10.0 · 공용 계정은 홈페이지에 안 나감 */
		$by[ $r->dept ?: '기타' ][] = $r;
	}
	$lines = array();
	foreach ( array_values( array_unique( array_merge( $order, array_keys( $by ) ) ) ) as $d ) {
		foreach ( $by[ $d ] ?? array() as $r ) {
			$lines[] = str_replace( '|', ' ', $r->dept ?: '기타' ) . '|' . str_replace( '|', ' ', $r->position ?: '사원' ) . '|' . str_replace( '|', ' ', $r->name );
		}
	}
	set_theme_mod( 'md_content_staff_list', implode( "\n", $lines ) );
	if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }
	if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); } /* WP Super Cache */
	/* v4.21.1 · 테마 자체 페이지 캐시(md_pcache_ 트랜지언트, 6시간)도 비운다 — 이걸 빠뜨려 의료진 페이지에 옛 명단이 남았다 */
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_pcache\_%' OR option_name LIKE '\_transient\_timeout\_md\_pcache\_%'" );
}

/* ============================================================
 * 데이터
 * ============================================================ */
function md_staff_all( $active_only = false ) {
	global $wpdb;
	$t = md_staff_table();
	return (array) $wpdb->get_results( 'SELECT * FROM ' . $t . ( $active_only ? ' WHERE active = 1' : '' ) . ' ORDER BY active DESC, sort, id' );
}

function md_staff_depts() {
	$d = array( '의료진', '진료실', '기공실', '서비스지원실', '경영지원실', '관리사무소', '예방과', '기타' );
	foreach ( md_staff_all() as $r ) { if ( '' !== $r->dept && ! in_array( $r->dept, $d, true ) ) { $d[] = $r->dept; } }
	return $d;
}

function md_staff_norm_date( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) return null;
	if ( preg_match( '/^(\d{4})[.\-\/](\d{1,2})[.\-\/](\d{1,2})$/', $v, $m ) ) { $v = sprintf( '%04d-%02d-%02d', $m[1], $m[2], $m[3] ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) return null;
	$p = explode( '-', $v );
	return checkdate( (int) $p[1], (int) $p[2], (int) $p[0] ) ? $v : null;
}

function md_staff_save( $data, $id = 0 ) {
	global $wpdb;
	$name = mb_substr( trim( sanitize_text_field( $data['name'] ?? '' ) ), 0, 60 );
	if ( '' === $name ) { return new WP_Error( 'md_staff', '이름을 적어 주세요.' ); }
	$row = array(
		'name'       => $name,
		'dept'       => mb_substr( trim( sanitize_text_field( $data['dept'] ?? '' ) ), 0, 60 ),
		'position'   => mb_substr( trim( sanitize_text_field( $data['position'] ?? '' ) ), 0, 60 ),
		'birthday'   => md_staff_norm_date( $data['birthday'] ?? '' ),
		'hired'      => md_staff_norm_date( $data['hired'] ?? '' ),
		'note'       => mb_substr( trim( sanitize_text_field( $data['note'] ?? '' ) ), 0, 200 ),
		'phone'      => mb_substr( trim( sanitize_text_field( $data['phone'] ?? '' ) ), 0, 40 ),
		'email'      => ( '' !== trim( (string) ( $data['email'] ?? '' ) ) && is_email( trim( $data['email'] ) ) ) ? sanitize_email( trim( $data['email'] ) ) : '',
		'updated_at' => current_time( 'mysql' ),
	);
	if ( $id ) { $wpdb->update( md_staff_table(), $row, array( 'id' => (int) $id ) ); return (int) $id; }
	$row['sort']       = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(sort),0) FROM ' . md_staff_table() ) + 1;
	$row['created_at'] = current_time( 'mysql' );
	$ok = $wpdb->insert( md_staff_table(), $row );
	return $ok ? (int) $wpdb->insert_id : new WP_Error( 'md_staff', '저장하지 못했습니다.' );
}

function md_staff_delete( $id ) {
	global $wpdb;
	/* 덴트웹과 이어진 직원을 지우면 다음 연동 때 다시 추가하지 않도록 기억해 둔다 */
	$dw = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT dw_id FROM ' . md_staff_table() . ' WHERE id = %d', (int) $id ) );
	if ( $dw ) { $ig = (array) get_option( 'md_staff_dw_ignore', array() ); $ig[ $dw ] = 1; update_option( 'md_staff_dw_ignore', $ig, false ); }
	md_staff_photo_remove( $id );
	return (bool) $wpdb->delete( md_staff_table(), array( 'id' => (int) $id ) );
}

/** 사진 보관 폴더 — uploads/staff/ */
function md_staff_photo_dir() {
	$u = wp_upload_dir();
	$dir = trailingslashit( $u['basedir'] ) . 'staff';
	if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); @file_put_contents( $dir . '/index.php', "<?php // silence" ); }
	return array( 'dir' => $dir, 'url' => trailingslashit( $u['baseurl'] ) . 'staff' );
}

function md_staff_photo_url( $r ) {
	/* v4.21.2 · 원장은 올린 사진이 없으면 홈페이지 의료진 사진을 쓴다 */
	if ( empty( $r->photo ) && isset( $r->dept ) && '의료진' === $r->dept && function_exists( 'moondental_doctor_photo_url' ) ) {
		$team = function_exists( 'moondental_get_team_with_customizer' ) ? moondental_get_team_with_customizer() : ( function_exists( 'moondental_get_team' ) ? moondental_get_team() : array() );
		foreach ( (array) $team as $m ) {
			if ( isset( $m['name'] ) && trim( $m['name'] ) === trim( $r->name ) && ! empty( $m['photo'] ) ) { $u = moondental_doctor_photo_url( $m['photo'] ); return $u ? $u : ''; }
		}
	}
	if ( empty( $r->photo ) ) return '';
	$p = md_staff_photo_dir();
	return $p['url'] . '/' . rawurlencode( $r->photo ) . '?v=' . ( $r->updated_at ? strtotime( $r->updated_at ) : 0 );
}

function md_staff_photo_remove( $id ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT photo FROM ' . md_staff_table() . ' WHERE id = %d', (int) $id ) );
	if ( $r && $r->photo ) {
		$p = md_staff_photo_dir();
		$f = $p['dir'] . '/' . basename( $r->photo );
		if ( is_file( $f ) ) { @unlink( $f ); }
		$wpdb->update( md_staff_table(), array( 'photo' => '' ), array( 'id' => (int) $id ) );
	}
}

/** 올린 사진을 480px 정사각 JPG 로 줄여 보관 */
function md_staff_photo_save( $id, $file ) {
	if ( empty( $file['name'] ) || ! empty( $file['error'] ) ) { return ''; }
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$mimes = array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic' );
	$up = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $mimes ) );
	if ( ! $up || ! empty( $up['error'] ) ) { return new WP_Error( 'md_staff', '사진을 올리지 못했습니다: ' . ( $up['error'] ?? '' ) ); }
	$p    = md_staff_photo_dir();
	$name = 's' . (int) $id . '-' . substr( md5( (string) microtime( true ) ), 0, 8 ) . '.jpg';
	$dest = $p['dir'] . '/' . $name;
	$ed   = wp_get_image_editor( $up['file'] );
	if ( is_wp_error( $ed ) ) { @unlink( $up['file'] ); return new WP_Error( 'md_staff', '이미지 파일이 아니거나 열 수 없습니다.' ); }
	$ed->resize( 480, 480, true );
	$ed->set_quality( 86 );
	$ok = $ed->save( $dest, 'image/jpeg' );
	@unlink( $up['file'] );
	if ( is_wp_error( $ok ) ) { return new WP_Error( 'md_staff', '사진을 저장하지 못했습니다.' ); }
	md_staff_photo_remove( $id );
	global $wpdb;
	$wpdb->update( md_staff_table(), array( 'photo' => $name, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	return $name;
}

/* ============================================================
 * 덴트웹 연동 — 서버 PC 의 sync.ps1 이 덴트웹 직원정보(PUB_V직원정보)를 올린다
 *   POST /wp-json/md-staff/v1/sync   헤더 X-MD-Survey-Key: <만족도 설정의 연동 키>
 *   본문 { "staff": [ { "id":12, "name":"홍길동", "job":4, "birthday":"19900101", "hired":"20200301", "retired":"" }, … ] }
 *   퇴사자는 id · retired 만 온다 (이름 · 생일은 보내지 않음).
 *
 *  - 처음엔 근무 중인 덴트웹 직원과 라운지 직원을 이름으로 짝지어 dw_id 로 잇는다 (같은 이름이 둘이면 잇지 않음).
 *  - 이어진 직원: 생일 · 입사일을 덴트웹 값으로, 이름도 덴트웹 값으로(원장은 홈페이지에서 관리하므로 이름 제외).
 *  - 덴트웹에 퇴사일이 들어가면 라운지에서도 퇴사 처리(계정 중지 — 라운지 관리자 계정은 원장이 직접).
 *    덴트웹 연동이 퇴사시킨 사람만 퇴사일이 지워지면 복직시킨다 (라운지에서 손으로 퇴사시킨 사람은 그대로).
 *  - 덴트웹에만 있는 근무 직원은 추가(부서는 직종으로 추정). 원장(직종 1)은 가상 의사(「예방과」 등)가 있어 추가하지 않는다.
 *  - 라운지에서 지운 직원은 다시 추가하지 않는다 (md_staff_dw_ignore).
 * ============================================================ */
function md_staff_dw_date( $v ) {
	$v = preg_replace( '/\D+/', '', (string) $v );
	if ( 8 !== strlen( $v ) ) { return null; }
	return md_staff_norm_date( substr( $v, 0, 4 ) . '-' . substr( $v, 4, 2 ) . '-' . substr( $v, 6, 2 ) );
}

function md_staff_dw_dept( $job ) {
	$m = array( 1 => '의료진', 3 => '경영지원실', 4 => '진료실', 5 => '진료실', 6 => '서비스지원실', 8 => '기공실', 11 => '경영지원실' );
	return $m[ (int) $job ] ?? '기타';
}

function md_staff_dw_sync( $list ) {
	global $wpdb;
	$t      = md_staff_table();
	$today  = current_time( 'Y-m-d' );
	$ignore = (array) get_option( 'md_staff_dw_ignore', array() );
	$left   = (array) get_option( 'md_staff_left', array() );
	$byDw   = (array) get_option( 'md_staff_dw_retired', array() ); /* 연동이 퇴사시킨 직원 sid => 1 */
	$rows   = (array) $wpdb->get_results( "SELECT * FROM $t" );
	$linked = array(); $byName = array();
	foreach ( $rows as $r ) {
		if ( (int) $r->dw_id ) { $linked[ (int) $r->dw_id ] = $r; }
		else { $byName[ trim( $r->name ) ][] = $r; }
	}
	/* 근무 중인 덴트웹 이름 — 같은 이름이 둘이면 이름으로 잇지 않는다 */
	$dwCount = array();
	foreach ( $list as $s ) { $n = trim( (string) ( $s['name'] ?? '' ) ); if ( '' !== $n ) { $dwCount[ $n ] = ( $dwCount[ $n ] ?? 0 ) + 1; } }

	$out  = array( 'linked' => 0, 'updated' => 0, 'added' => 0, 'retired' => 0, 'rehired' => 0, 'skipped' => array() );
	$site = false;
	$acc  = function_exists( 'md_acc_staff_user_map' ) ? md_acc_staff_user_map() : array();
	foreach ( $list as $s ) {
		$id = (int) ( $s['id'] ?? 0 );
		if ( $id <= 0 ) { continue; }
		$ret  = md_staff_dw_date( $s['retired'] ?? '' );
		$gone = $ret && $ret <= $today;
		$name = mb_substr( trim( sanitize_text_field( (string) ( $s['name'] ?? '' ) ) ), 0, 60 );
		$r    = $linked[ $id ] ?? null;

		if ( ! $r && ! $gone && '' !== $name && 1 === ( $dwCount[ $name ] ?? 0 ) && 1 === count( $byName[ $name ] ?? array() ) ) {
			$r = $byName[ $name ][0];
			if ( (int) $r->active ) { /* 라운지에서 퇴사 처리된 사람은 잇지 않는다 */
				$wpdb->update( $t, array( 'dw_id' => $id ), array( 'id' => (int) $r->id ) );
				$r->dw_id = $id; $linked[ $id ] = $r; unset( $byName[ $name ] ); $out['linked']++;
			} else { $r = null; }
		}

		if ( $r ) {
			$sid = (int) $r->id;
			if ( $gone ) {
				if ( (int) $r->active ) {
					$u = $acc[ $sid ] ?? null;
					if ( $u && user_can( $u, 'md_supply_manage' ) ) { $out['skipped'][] = $r->name . ' (라운지 관리자 계정 — 원장님이 직접 퇴사 처리)'; continue; }
					$wpdb->update( $t, array( 'active' => 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $sid ) );
					$left[ $sid ] = $ret; $byDw[ $sid ] = 1;
					if ( $u && function_exists( 'md_acc_status' ) && 'active' === md_acc_status( $u ) && function_exists( 'md_acc_turn_off' ) ) { md_acc_turn_off( $u ); }
					if ( function_exists( 'md_acc_log' ) ) { md_acc_log( '퇴사 처리 (덴트웹)', $r->name . ' · ' . $ret . ( $u ? ' · 계정 중지' : '' ) ); }
					$out['retired']++; $site = true;
				}
				continue;
			}
			$set = array();
			if ( ! (int) $r->active && ! empty( $byDw[ $sid ] ) ) { /* 덴트웹에서 퇴사일을 지웠다 → 복직 */
				$set['active'] = 1; unset( $left[ $sid ], $byDw[ $sid ] );
				$u = $acc[ $sid ] ?? null;
				if ( $u && function_exists( 'md_acc_status' ) && 'off' === md_acc_status( $u ) && function_exists( 'md_acc_turn_on' ) ) { md_acc_turn_on( $u ); }
				if ( function_exists( 'md_acc_log' ) ) { md_acc_log( '복직 (덴트웹)', $r->name ); }
				$out['rehired']++; $site = true;
			}
			foreach ( array( 'birthday', 'hired' ) as $k ) {
				$v = md_staff_dw_date( $s[ $k ] ?? '' );
				if ( $v && $v !== (string) $r->$k ) { $set[ $k ] = $v; }
			}
			if ( '' !== $name && $name !== $r->name && '의료진' !== $r->dept ) { $set['name'] = $name; $site = true; }
			if ( $set ) {
				$set['updated_at'] = current_time( 'mysql' );
				$wpdb->update( $t, $set, array( 'id' => $sid ) );
				$out['updated']++;
			}
			continue;
		}

		/* 라운지에 없는 근무 직원 → 추가 */
		if ( $gone || '' === $name || ! empty( $ignore[ $id ] ) || 1 === (int) ( $s['job'] ?? 0 ) ) { continue; }
		$wpdb->insert( $t, array(
			'name'       => $name,
			'dept'       => md_staff_dw_dept( $s['job'] ?? 0 ),
			'birthday'   => md_staff_dw_date( $s['birthday'] ?? '' ),
			'hired'      => md_staff_dw_date( $s['hired'] ?? '' ),
			'dw_id'      => $id,
			'sort'       => (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort),0) FROM $t" ) + 1,
			'created_at' => current_time( 'mysql' ),
		) );
		$out['added']++; $site = true;
	}
	update_option( 'md_staff_left', $left, false );
	update_option( 'md_staff_dw_retired', $byDw, false );
	if ( $site ) { md_staff_sync_site(); }
	update_option( 'md_staff_dw_last', array( 'at' => current_time( 'mysql' ) ) + $out, false );
	return $out;
}

/**
 * 덴트웹 개인별 휴무일(TB_개인별휴무일) → 라운지 달력 🌴 (v9.37 · 2026-10-09 원장 지시로 다시 켬 — 10/6 에 한 번 뺐던 것)
 *  병원 PC 의 sync.ps1 이 걸러서(종일 · 2시간 이상, 점심 · 외출 · 상시 일정 · 예약 막기 제외) 오늘 -1달 ~ +1년 치를
 *  1분마다 확인해 바뀌었을 때(와 한 시간에 한 번) 통째로 보낸다. 옵션 md_staff_dw_dayoff 에 두고, 달력(inc/calendar)이 직원 명단(dw_id)과 맞춰 표시.
 *   항목: { sid: 덴트웹 직원 번호, d1, d2: 'Y-m-d', t1, t2: 'HHmm'(종일이면 ''), memo }
 */
function md_staff_dw_dayoffs() {
	$o = get_option( 'md_staff_dw_dayoff' );
	return is_array( $o ) && isset( $o['items'] ) ? (array) $o['items'] : array();
}

/** 휴무 자료를 마지막으로 받은 시각 (없으면 '') */
function md_staff_dw_dayoff_at() {
	$o = get_option( 'md_staff_dw_dayoff' );
	return is_array( $o ) ? (string) ( $o['at'] ?? '' ) : '';
}

/**
 * 덴트웹 직원 번호 → 달력에 쓸 이름 · 라운지 직원 id · 정렬 순서. 재직 중이고 명단과 이어진 사람만(퇴사자 · 가상 의사 · 공용 계정 제외)
 *  의료진 이름은 직책에 따라 「이름 병원장님」 · 「이름 원장님」(원장 지시 2026-10-09), 그 밖의 직책은 그대로 붙인다. 직원은 이름만.
 */
function md_staff_dw_who( $dw ) {
	static $map = null;
	if ( null === $map ) {
		global $wpdb; $map = array();
		foreach ( (array) $wpdb->get_results( 'SELECT id, name, dept, position, dw_id, sort FROM ' . md_staff_table() . ' WHERE dw_id > 0 AND active = 1' ) as $r ) {
			if ( md_staff_is_shared( $r ) ) { continue; }
			$pos    = trim( (string) $r->position );
			$doctor = '의료진' === $r->dept;
			$rank   = 3; $name = $r->name;
			if ( $doctor ) {
				if ( false !== mb_strpos( $pos, '병원장' ) )          { $rank = 0; $name .= ' 병원장님'; }
				elseif ( '' === $pos || false !== mb_strpos( $pos, '원장' ) ) { $rank = 1; $name .= ' 원장님'; }
				else                                                  { $rank = 2; $name .= ' ' . $pos; }
			}
			$map[ (int) $r->dw_id ] = array( 'sid' => (int) $r->id, 'name' => $name, 'doctor' => $doctor, 'order' => sprintf( '%d-%06d-%s', $rank, (int) $r->sort, $r->name ) );
		}
	}
	return $map[ (int) $dw ] ?? null;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-staff/v1', '/dayoff', array(
		'methods'             => 'POST',
		'callback'            => function ( $request ) {
			$body  = $request->get_json_params();
			$items = array();
			foreach ( (array) ( $body['items'] ?? array() ) as $it ) {
				if ( ! is_array( $it ) ) { continue; }
				$d1 = md_staff_norm_date( $it['d1'] ?? '' ); $d2 = md_staff_norm_date( $it['d2'] ?? '' ) ?: $d1;
				if ( ! $d1 || $d2 < $d1 ) { continue; }
				$tm = function ( $v ) { $v = preg_replace( '/[^0-9]+/', '', (string) $v ); return 4 === strlen( $v ) ? $v : ''; };
				$items[] = array( 'sid' => (int) ( $it['sid'] ?? 0 ), 'd1' => $d1, 'd2' => $d2, 't1' => $tm( $it['t1'] ?? '' ), 't2' => $tm( $it['t2'] ?? '' ), 'memo' => mb_substr( sanitize_text_field( (string) ( $it['memo'] ?? '' ) ), 0, 120 ) );
				if ( count( $items ) >= 3000 ) { break; }
			}
			update_option( 'md_staff_dw_dayoff', array( 'at' => current_time( 'mysql' ), 'items' => $items ), false );
			return rest_ensure_response( array( 'saved' => count( $items ) ) );
		},
		'permission_callback' => 'md_staff_dw_permission',
	) );
} );

function md_staff_dw_permission( $request ) {
	$key  = (string) $request->get_header( 'x-md-survey-key' );
	$want = (string) get_option( 'md_survey_api_key' );
	return '' !== $want && '' !== $key && hash_equals( $want, $key );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-staff/v1', '/sync', array(
		'methods'             => 'POST',
		'callback'            => function ( $request ) {
			$body = $request->get_json_params();
			$list = isset( $body['staff'] ) && is_array( $body['staff'] ) ? array_values( array_filter( $body['staff'], 'is_array' ) ) : array();
			if ( ! $list ) { return new WP_Error( 'md_staff', '직원 목록이 비어 있습니다.', array( 'status' => 400 ) ); }
			return rest_ensure_response( md_staff_dw_sync( $list ) );
		},
		'permission_callback' => 'md_staff_dw_permission',
	) );
} );

/* ============================================================
 * 폼 처리 (관리자만 · PRG)
 * ============================================================ */
function md_staff_handle_post() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['md_staff_action'] ) ) { return; }
	if ( ! md_staff_can_manage() ) { return; }
	$action = sanitize_key( wp_unslash( $_POST['md_staff_action'] ) );
	if ( ! isset( $_POST['md_staff_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_staff_nonce'] ), 'md_staff_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}
	$back = md_sup_url( array( 'app' => 'staff' ) );
	$id   = isset( $_POST['sid'] ) ? (int) $_POST['sid'] : 0;
	$data = array();
	foreach ( array( 'name', 'dept', 'position', 'birthday', 'hired', 'note', 'phone', 'email' ) as $k ) { $data[ $k ] = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : ''; }
	if ( '' === $data['dept'] && ! empty( $_POST['dept_new'] ) ) { $data['dept'] = sanitize_text_field( wp_unslash( $_POST['dept_new'] ) ); }
	switch ( $action ) {
		case 'add':
		case 'edit':
			$res = 'add' === $action ? md_staff_save( $data ) : md_staff_save( $data, $id );
			if ( is_wp_error( $res ) ) { $back = add_query_arg( 'err', $res->get_error_message(), $back ); break; }
			$sid = (int) $res;
			if ( ! empty( $_POST['photo_remove'] ) ) { md_staff_photo_remove( $sid ); }
			if ( ! empty( $_FILES['photo']['name'] ) ) {
				$ph = md_staff_photo_save( $sid, $_FILES['photo'] );
				if ( is_wp_error( $ph ) ) { $back = add_query_arg( 'err', $ph->get_error_message(), $back ); }
			}
			md_staff_sync_site();
			$back .= '#s' . $sid;
			break;
		case 'delete':
			/* v9.32 · 「퇴사 처리」 단추를 없앰 (원장: 휴지통이면 충분) — 지울 때 라운지 계정이 있으면 같이 사용 중지, 보호 규칙은 퇴사 처리 때와 같게 */
			global $wpdb;
			$acc = null;
			if ( function_exists( 'md_acc_staff_user_map' ) ) { $um = md_acc_staff_user_map(); $acc = isset( $um[ $id ] ) ? $um[ $id ] : null; }
			if ( $acc && (int) $acc->ID === get_current_user_id() ) { $back = add_query_arg( 'err', '내 줄은 다른 관리자가 지워야 합니다.', $back ); break; }
			if ( $acc && user_can( $acc, 'md_supply_manage' ) && function_exists( 'md_acc_can_grant_admin' ) && ! md_acc_can_grant_admin() ) { $back = add_query_arg( 'err', '라운지 관리자 계정이 있는 직원은 원장님만 지울 수 있습니다.', $back ); break; }
			$nm = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . md_staff_table() . ' WHERE id = %d', $id ) );
			if ( $acc && function_exists( 'md_acc_turn_off' ) && 'active' === md_acc_status( $acc ) ) { md_acc_turn_off( $acc ); }
			md_staff_delete( $id );
			md_staff_sync_site();
			if ( function_exists( 'md_acc_log' ) ) { md_acc_log( '명단에서 삭제', $nm . ( $acc ? ' · 계정 사용 중지' : '' ) ); }
			break;
	}
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_staff_handle_post', 1 );

/* ============================================================
 * 화면 (관리자)
 * ============================================================ */
function md_staff_render_row_form( $r, $depts ) {
	$edit = $r && ! empty( $r->id );
	$act  = $edit ? 'edit' : 'add';
	?>
	<?php $photo = $edit ? md_staff_photo_url( $r ) : ''; ?>
	<form method="post" enctype="multipart/form-data" class="mdst-row<?php echo $edit ? '' : ' mdst-row--new'; ?>" id="<?php echo $edit ? 's' . (int) $r->id : 'new'; ?>">
		<label class="mdst-f mdst-f--photo" title="사진 올리기 (JPG · PNG · HEIC)">
			<span>사진</span>
			<span class="mdst-photo<?php echo $photo ? ' has-photo' : ''; ?>"><?php if ( $photo ) : ?><img src="<?php echo esc_url( $photo ); ?>" alt=""><?php else : ?><em>＋</em><?php endif; ?></span>
			<input type="file" name="photo" accept="image/*" class="mdst-photo__input">
		</label>
		<input type="hidden" name="md_staff_action" value="<?php echo esc_attr( $act ); ?>">
		<input type="hidden" name="md_staff_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_staff_' . $act ) ); ?>">
		<?php if ( $edit ) : ?><input type="hidden" name="sid" value="<?php echo (int) $r->id; ?>"><?php endif; ?>
		<label class="mdst-f mdst-f--name"><span>이름</span><input type="text" name="name" required maxlength="60" value="<?php echo esc_attr( $r->name ?? '' ); ?>" placeholder="이름"></label>
		<label class="mdst-f mdst-f--dept"><span>부서</span>
			<select name="dept">
				<option value="">선택</option>
				<?php foreach ( $depts as $d ) : ?><option value="<?php echo esc_attr( $d ); ?>" <?php selected( $d, $r->dept ?? '' ); ?>><?php echo esc_html( $d ); ?></option><?php endforeach; ?>
			</select>
		</label>
		<label class="mdst-f mdst-f--pos"><span>직책</span><input type="text" name="position" maxlength="60" value="<?php echo esc_attr( $r->position ?? '' ); ?>" placeholder="예) 실장"></label>
		<label class="mdst-f mdst-f--date"><span>🎂 생일</span><input type="date" name="birthday" value="<?php echo esc_attr( $r->birthday ?? '' ); ?>"></label>
		<label class="mdst-f mdst-f--date"><span>🎉 입사일</span><input type="date" name="hired" value="<?php echo esc_attr( $r->hired ?? '' ); ?>"></label>
		<label class="mdst-f mdst-f--phone"><span>📞 전화</span><input type="tel" name="phone" maxlength="40" value="<?php echo esc_attr( $r->phone ?? '' ); ?>" placeholder="010-0000-0000"></label>
		<label class="mdst-f mdst-f--email"><span>✉️ 이메일</span><input type="email" name="email" maxlength="120" value="<?php echo esc_attr( $r->email ?? '' ); ?>" placeholder="name@example.com"></label>
		<label class="mdst-f mdst-f--note"><span>메모</span><input type="text" name="note" maxlength="200" value="<?php echo esc_attr( $r->note ?? '' ); ?>" placeholder="선택"></label>
		<?php if ( $photo ) : ?><label class="mdst-f mdst-f--chk mds-check"><input type="checkbox" name="photo_remove" value="1"> 사진 지움</label><?php endif; ?>
		<button type="submit" class="mds-btn mds-btn--fill mdst-save"><?php echo $edit ? '저장' : '추가'; ?></button>
	</form>
	<?php if ( $edit ) : ?>
	<form method="post" class="mdst-del" onsubmit="return confirm('<?php echo esc_js( $r->name ); ?> 님을 명단에서 지울까요? 홈페이지 의료진 페이지와 달력에서도 함께 빠지고, 라운지 계정이 있으면 사용 중지됩니다.');">
		<input type="hidden" name="md_staff_action" value="delete"><input type="hidden" name="md_staff_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_staff_delete' ) ); ?>"><input type="hidden" name="sid" value="<?php echo (int) $r->id; ?>">
		<button type="submit" class="mdst-delbtn" title="명단에서 삭제">🗑</button>
	</form>
	<?php endif;
}

function md_staff_render() {
	if ( ! md_staff_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	$rows  = md_staff_all();
	$depts = md_staff_depts();
	if ( isset( $_GET['err'] ) ) { echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['err'] ) ) . '</div>'; }
	$n_b = 0; $n_h = 0; $n_a = 0;
	foreach ( $rows as $r ) { if ( (int) $r->active && ! md_staff_is_shared( $r ) ) { $n_a++; if ( $r->birthday ) $n_b++; if ( $r->hired ) $n_h++; } } /* v10.0 · 공용 계정은 인원에서 뺌 */
	?>
	<div class="mdst">
		<div class="mds-card mdst-head">
			<p class="mds-hint" style="margin:0">
				<strong><?php echo (int) $n_a; ?>명</strong> · 생일 입력 <strong><?php echo (int) $n_b; ?></strong> · 입사일 입력 <strong><?php echo (int) $n_h; ?></strong>.
				여기서 추가 · 수정 · 삭제하면 홈페이지 <a href="<?php echo esc_url( home_url( '/의료진/' ) ); ?>" target="_blank" rel="noopener">의료진 페이지</a>의 직원 명단이 바로 바뀌고, 생일 · 입사일은 라운지 달력에 🎂 🎉 로 표시됩니다(생일 연도는 표시하지 않음).
				원장(의료진)은 사진 · 약력과 함께 관리되므로 이름 · 직책은 홈페이지 설정에서, 생일 · 입사일만 여기서 넣습니다.
			</p>
			<?php $dw = get_option( 'md_staff_dw_last' ); if ( is_array( $dw ) && ! empty( $dw['at'] ) ) : ?>
			<p class="mds-hint" style="margin:.5em 0 0">
				🔗 <strong>덴트웹 연동</strong> · 마지막 <?php echo esc_html( date_i18n( 'm.d H:i', strtotime( $dw['at'] ) ) ); ?> —
				이름 · 생일 · 입사일 · 퇴사는 <strong>덴트웹 직원정보에서</strong> 고쳐 주세요(여기서 고쳐도 한 시간 안에 덴트웹 값으로 돌아갑니다). 덴트웹에 새 직원을 넣으면 여기에 자동으로 추가됩니다 — 부서만 확인해 주세요.
				<?php if ( ! empty( $dw['skipped'] ) ) : ?><br>⚠ <?php echo esc_html( implode( ', ', (array) $dw['skipped'] ) ); ?><?php endif; ?>
			</p>
			<?php endif; ?>
		</div>

		<?php if ( function_exists( 'md_acc_render_staff_panel' ) ) { md_acc_render_staff_panel(); } /* v5.8 · 가입 신청 · 계정 (inc/accounts) */ ?>

		<section class="mds-card mdst-new">
			<h2 class="mdst-title">직원 추가</h2>
			<?php md_staff_render_row_form( null, $depts ); ?>
		</section>

		<?php
		$by = array(); $gone = array();
		foreach ( $rows as $r ) {
			if ( ! (int) $r->active && function_exists( 'md_acc_render_retired' ) ) { $gone[] = $r; continue; } /* v5.9 · 퇴사자는 맨 아래 따로 (inc/accounts) */
			$by[ $r->dept ?: '기타' ][] = $r;
		}
		$order = array_values( array_unique( array_merge( $depts, array_keys( $by ) ) ) );
		foreach ( $order as $d ) : if ( empty( $by[ $d ] ) ) continue; ?>
			<section class="mds-card mdst-dept">
				<h2 class="mdst-title"><?php echo esc_html( $d ); ?> <small><?php echo count( $by[ $d ] ); ?>명<?php echo '의료진' === $d ? ' · 생일 · 입사일만 여기서' : ''; ?></small></h2>
				<div class="mdst-rows">
					<?php foreach ( $by[ $d ] as $r ) : ?>
						<div class="mdst-item"><?php md_staff_render_row_form( $r, $depts ); if ( function_exists( 'md_acc_render_row' ) ) { md_acc_render_row( $r ); } /* v5.8 · 계정 칸 */ ?></div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
		<?php if ( $gone ) { md_acc_render_retired( $gone ); } ?>
	</div>
	<?php
}
