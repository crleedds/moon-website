<?php
/**
 * 재료실 · 신청에 사진 붙이기 (v9.3 · 원장 지시 2026-10-07 「기타 소모품은 상세 사진 첨부해 요청」)
 *
 *   크기 — 폰에서 긴 변 1600px JPEG 로 줄여 올리고(보통 200~400KB), 서버가 한 번 더 1600px · 품질 80 으로 다시 저장한다
 *          (위치 정보 같은 사진 속 정보도 이때 빠진다). 원본은 8MB 까지만 받는다. 한 품목에 3장까지.
 *   보관 — uploads/md-inv-photo/ (바로 열기 막음 · 라운지 로그인한 사람만 이 화면 주소로 본다)
 *          신청이 끝나고(출고 · 반려 · 취소) 90일이 지나면 사진 파일은 저절로 지운다. 신청 기록은 남는다.
 *          장바구니에만 담고 보내지 않은 사진은 이틀 뒤 지운다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_INV_PHOTO_KEEP_DAYS', 90 );
define( 'MD_INV_PHOTO_MAX_UP', 8 * 1048576 );
define( 'MD_INV_PHOTO_PER_LINE', 3 );

function md_inv_photo_dir() {
	$up  = wp_upload_dir( null, false );
	$dir = trailingslashit( $up['basedir'] ) . 'md-inv-photo';
	if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
	if ( ! file_exists( $dir . '/.htaccess' ) ) { @file_put_contents( $dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); }
	if ( ! file_exists( $dir . '/index.php' ) ) { @file_put_contents( $dir . '/index.php', "<?php // Silence\n" ); }
	return $dir;
}

function md_inv_photo_valid( $n ) { return is_string( $n ) && preg_match( '/^\d{8}-[a-z0-9]{16}\.jpg$/', $n ); }

function md_inv_photo_url( $n ) { return add_query_arg( 'md_inv_photo', rawurlencode( $n ), home_url( '/직원/' ) ); }

/** 신청 한 건의 사진 이름들 */
function md_inv_photo_list( $r ) {
	if ( empty( $r->photos ) ) { return array(); }
	$a = json_decode( (string) $r->photos, true );
	return is_array( $a ) ? array_values( array_filter( $a, 'md_inv_photo_valid' ) ) : array();
}

/** 카드에 붙는 작은 사진들 — 누르면 크게 (새 창) */
function md_inv_photo_thumbs( $r ) {
	$ph = md_inv_photo_list( $r );
	if ( ! $ph ) { return ''; }
	$dir = md_inv_photo_dir();
	$h = '<div class="iv-photos">';
	foreach ( $ph as $n ) {
		if ( ! file_exists( $dir . '/' . $n ) ) { $h .= '<span class="iv-photos__gone" title="보관 기간이 지나 지운 사진">사진 지움</span>'; continue; }
		$u = md_inv_photo_url( $n );
		$h .= '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $u ) . '" alt="첨부 사진" loading="lazy" width="64" height="64"></a>';
	}
	return $h . '</div>';
}

/* ---------- 올리기 (장바구니에서 📷) ---------- */
function md_inv_ajax_photo_up() {
	if ( ! is_user_logged_in() || ! md_inv_can_use() || ! check_ajax_referer( 'md_inv_photo', 'nonce', false ) ) { wp_send_json( array( 'ok' => false, 'msg' => '다시 로그인해 주세요.' ), 403 ); }
	$f = isset( $_FILES['photo'] ) ? $_FILES['photo'] : null;
	if ( ! $f || UPLOAD_ERR_OK !== (int) $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) { wp_send_json( array( 'ok' => false, 'msg' => '사진을 올리지 못했습니다.' ) ); }
	if ( (int) $f['size'] > MD_INV_PHOTO_MAX_UP ) { wp_send_json( array( 'ok' => false, 'msg' => '사진이 너무 큽니다 (8MB 까지).' ) ); }
	$info = @getimagesize( $f['tmp_name'] );
	if ( ! $info || ! in_array( $info[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF ), true ) ) { wp_send_json( array( 'ok' => false, 'msg' => '사진 파일(JPG · PNG · WEBP)만 올릴 수 있습니다.' ) ); }
	$ed = wp_get_image_editor( $f['tmp_name'] );
	if ( is_wp_error( $ed ) ) { wp_send_json( array( 'ok' => false, 'msg' => '사진을 읽지 못했습니다.' ) ); }
	if ( method_exists( $ed, 'maybe_exif_rotate' ) ) { $ed->maybe_exif_rotate(); }
	$sz = $ed->get_size();
	if ( $sz['width'] > 1600 || $sz['height'] > 1600 ) { $ed->resize( 1600, 1600, false ); }
	$ed->set_quality( 80 );
	$name = current_time( 'Ymd' ) . '-' . strtolower( wp_generate_password( 16, false, false ) ) . '.jpg';
	$res  = $ed->save( md_inv_photo_dir() . '/' . $name, 'image/jpeg' );
	if ( is_wp_error( $res ) ) { wp_send_json( array( 'ok' => false, 'msg' => '사진을 저장하지 못했습니다.' ) ); }
	set_transient( 'md_inv_ph_' . substr( $name, 0, -4 ), get_current_user_id(), 2 * DAY_IN_SECONDS );
	wp_send_json( array( 'ok' => true, 'n' => $name, 'u' => md_inv_photo_url( $name ), 'kb' => (int) round( filesize( md_inv_photo_dir() . '/' . $name ) / 1024 ) ) );
}
add_action( 'wp_ajax_md_inv_photo_up', 'md_inv_ajax_photo_up' );

/* ---------- 장바구니에서 빼기 (보내기 전 · 올린 사람만) ---------- */
function md_inv_ajax_photo_del() {
	if ( ! is_user_logged_in() || ! check_ajax_referer( 'md_inv_photo', 'nonce', false ) ) { wp_die( '0', '', array( 'response' => 403 ) ); }
	$n = sanitize_file_name( wp_unslash( $_POST['n'] ?? '' ) );
	if ( md_inv_photo_valid( $n ) && (int) get_transient( 'md_inv_ph_' . substr( $n, 0, -4 ) ) === get_current_user_id() ) {
		@unlink( md_inv_photo_dir() . '/' . $n );
		delete_transient( 'md_inv_ph_' . substr( $n, 0, -4 ) );
	}
	wp_die( '1' );
}
add_action( 'wp_ajax_md_inv_photo_del', 'md_inv_ajax_photo_del' );

/** 신청을 보낼 때 — 이 사람이 올린 사진만 붙인다 */
function md_inv_photo_claim( $names ) {
	$out = array();
	foreach ( array_slice( (array) $names, 0, MD_INV_PHOTO_PER_LINE ) as $n ) {
		$n = (string) $n;
		if ( ! md_inv_photo_valid( $n ) || ! file_exists( md_inv_photo_dir() . '/' . $n ) ) { continue; }
		$k = 'md_inv_ph_' . substr( $n, 0, -4 );
		if ( (int) get_transient( $k ) !== get_current_user_id() ) { continue; }
		delete_transient( $k );
		$out[] = $n;
	}
	return $out;
}

/* ---------- 보기 (라운지에 로그인한 사람만) ---------- */
function md_inv_photo_serve() {
	if ( ! isset( $_GET['md_inv_photo'] ) ) { return; }
	$n = sanitize_file_name( wp_unslash( $_GET['md_inv_photo'] ) );
	if ( ! md_inv_can_use() ) { wp_die( '직원 라운지에 로그인한 뒤 볼 수 있습니다.', '권한 없음', array( 'response' => 403 ) ); }
	$p = md_inv_photo_dir() . '/' . $n;
	if ( ! md_inv_photo_valid( $n ) || ! is_readable( $p ) ) { status_header( 404 ); exit; }
	while ( ob_get_level() ) { ob_end_clean(); }
	header( 'Content-Type: image/jpeg' );
	header( 'Content-Length: ' . filesize( $p ) );
	header( 'Cache-Control: private, max-age=604800' );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $p );
	exit;
}
add_action( 'template_redirect', 'md_inv_photo_serve', 0 );

/* ---------- 정리 (하루 한 번 · 매시간 작업에 붙어서) ---------- */
function md_inv_photo_gc( $force = false ) {
	if ( ! $force && (int) get_option( 'md_inv_photo_gc_at', 0 ) > time() - DAY_IN_SECONDS ) { return array(); }
	update_option( 'md_inv_photo_gc_at', time(), false );
	global $wpdb;
	$t   = md_inv_t( 'req' );
	$dir = md_inv_photo_dir();
	$cut = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - MD_INV_PHOTO_KEEP_DAYS * DAY_IN_SECONDS );
	$res = array( 'old' => 0, 'orphan' => 0 );
	/* 끝난 지 90일 지난 신청의 사진 */
	$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, photos FROM $t WHERE photos <> '' AND status <> 'pending' AND COALESCE(done_at, created_at) < %s", $cut ) );
	foreach ( $rows as $r ) {
		foreach ( md_inv_photo_list( $r ) as $n ) { if ( @unlink( $dir . '/' . $n ) ) { $res['old']++; } }
		$wpdb->update( $t, array( 'photos' => '' ), array( 'id' => (int) $r->id ) );
	}
	/* 보내지 않은 장바구니 사진 (이틀 지난 것 중 어느 신청에도 없는 것) */
	foreach ( (array) glob( $dir . '/*.jpg' ) as $p ) {
		if ( filemtime( $p ) > time() - 2 * DAY_IN_SECONDS ) { continue; }
		$n = basename( $p );
		if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE photos LIKE %s LIMIT 1", '%' . $wpdb->esc_like( $n ) . '%' ) ) ) { if ( @unlink( $p ) ) { $res['orphan']++; } }
	}
	return $res;
}
add_action( 'md_inv_hourly', 'md_inv_photo_gc' );
