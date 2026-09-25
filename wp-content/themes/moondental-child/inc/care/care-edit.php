<?php
/**
 * v4.2 · Moon Dental Care — 사진·영상 편집 (관리자 전용)
 *
 *  관리자(manage_options)가 주제 화면에서 「✏️ 편집」을 켜면 사진·영상을 올리고 지우고 순서를 바꾸고
 *  제목을 고칠 수 있다. 결과는 옵션 md_care_items_{slug} 에 저장되며, 옵션이 있으면 data 파일의
 *  목록 대신 그것을 쓴다(처음 편집할 때 파일 목록을 복사해 시작).
 *
 *  파일 저장 위치: uploads/care/{slug}/u*.jpg · uploads/care/video/{slug}/u*.mp4 (+ 같은 이름 .jpg 포스터)
 *  삭제 시 uploads/care/ 아래의 파일만 지운다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function md_care_can_edit() {
	return is_user_logged_in() && current_user_can( 'manage_options' );
}

/** 주제의 사진·영상 목록 — 옵션(편집본)이 있으면 그것, 없으면 data 파일 */
/** 편집본 저장 파일 · uploads/care/_data/{slug}.json */
function md_care_store_path( $slug ) {
	$up = wp_upload_dir(); $dir = rtrim( $up['basedir'], '/' ) . '/care/_data';
	if ( ! is_dir( $dir ) ) wp_mkdir_p( $dir );
	return $dir . '/' . sanitize_key( $slug ) . '.json';
}

function md_care_items( $slug, $t = null ) {
	$file = md_care_store_path( $slug );
	if ( is_file( $file ) ) {
		$saved = json_decode( (string) file_get_contents( $file ), true );
		if ( is_array( $saved ) && isset( $saved['photos'], $saved['videos'] ) ) return $saved;
	}
	$saved = get_option( 'md_care_items_' . $slug, null );
	if ( is_array( $saved ) && isset( $saved['photos'], $saved['videos'] ) ) return $saved;
	if ( $t === null ) { $topics = md_care_topics(); $t = $topics[ $slug ] ?? array(); }
	return array( 'photos' => array_values( (array) ( $t['photos'] ?? array() ) ), 'videos' => array_values( (array) ( $t['videos'] ?? array() ) ) );
}

function md_care_save_items( $slug, $items ) {
	$data = array( 'photos' => array_values( $items['photos'] ), 'videos' => array_values( $items['videos'] ), 'updated' => current_time( 'mysql' ) );
	$ok_file = (bool) file_put_contents( md_care_store_path( $slug ), wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
	$ok_opt  = update_option( 'md_care_items_' . $slug, $data, 'no' );
	return array( 'file' => $ok_file, 'option' => (bool) $ok_opt );
}

/** uploads/care/ 아래의 상대 경로만 실제 파일로 바꿔 지운다 */
function md_care_unlink_rel( $rel ) {
	if ( ! $rel || preg_match( '#^https?://#', $rel ) ) return;
	$rel = ltrim( str_replace( '..', '', $rel ), '/' );
	$up  = wp_upload_dir(); $path = rtrim( $up['basedir'], '/' ) . '/care/' . $rel;
	if ( is_file( $path ) ) @unlink( $path );
}

/** 공통 · 권한·논스·주제 확인 */
function md_care_ajax_guard() {
	if ( ! md_care_can_edit() ) wp_send_json_error( array( 'message' => '관리자만 편집할 수 있습니다.' ), 403 );
	check_ajax_referer( 'md_care_edit', 'nonce' );
	$slug = sanitize_key( $_POST['slug'] ?? '' );
	$topics = md_care_topics();
	if ( ! isset( $topics[ $slug ] ) ) wp_send_json_error( array( 'message' => '주제를 찾을 수 없습니다.' ), 400 );
	return array( $slug, $topics[ $slug ] );
}

/** 업로드 · 사진(jpg/png/webp, 여러 장) 또는 영상(mp4/webm, 포스터 선택) */
function md_care_ajax_upload() {
	list( $slug, $t ) = md_care_ajax_guard();
	$kind  = ( $_POST['kind'] ?? 'photo' ) === 'video' ? 'video' : 'photo';
	$items = md_care_items( $slug, $t );
	if ( empty( $_FILES['file'] ) ) wp_send_json_error( array( 'message' => '파일이 없습니다.' ), 400 );
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$subdir = $kind === 'video' ? 'video/' . $slug : $slug;
	$dir_filter = function ( $d ) use ( $subdir ) {
		$d['subdir'] = '/care/' . $subdir; $d['path'] = $d['basedir'] . $d['subdir']; $d['url'] = $d['baseurl'] . $d['subdir'];
		return $d;
	};
	$name_filter = function ( $name ) { $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ); return 'u' . time() . '-' . wp_generate_password( 6, false ) . '.' . $ext; };
	add_filter( 'upload_dir', $dir_filter ); add_filter( 'sanitize_file_name', $name_filter, 99 );

	$mimes = $kind === 'video' ? array( 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'm4v' => 'video/mp4' ) : array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
	$added = array(); $errors = array();
	$files = $_FILES['file'];
	$n = is_array( $files['name'] ) ? count( $files['name'] ) : 1;
	for ( $i = 0; $i < $n; $i++ ) {
		$f = is_array( $files['name'] ) ? array( 'name' => $files['name'][ $i ], 'type' => $files['type'][ $i ], 'tmp_name' => $files['tmp_name'][ $i ], 'error' => $files['error'][ $i ], 'size' => $files['size'][ $i ] ) : $files;
		$orig = $f['name'];
		$r = wp_handle_upload( $f, array( 'test_form' => false, 'mimes' => $mimes ) );
		if ( isset( $r['error'] ) ) { $errors[] = $orig . ': ' . $r['error']; continue; }
		$rel = $subdir . '/' . basename( $r['file'] );
		$caption = preg_replace( '/\.[a-z0-9]+$/i', '', $orig );
		if ( $kind === 'photo' ) {
			$item = array( 'src' => $rel, 'caption' => $caption );
			// 큰 사진은 1600px 로 줄인다
			$ed = wp_get_image_editor( $r['file'] );
			if ( ! is_wp_error( $ed ) ) { $s = $ed->get_size(); if ( max( $s['width'], $s['height'] ) > 1600 ) { $ed->resize( 1600, 1600, false ); $ed->save( $r['file'] ); } }
			$items['photos'][] = $item;
		} else {
			$item = array( 'title' => $caption, 'file' => $rel, 'poster' => '' );
			if ( ! empty( $_FILES['poster']['tmp_name'] ) && ( ! is_array( $_FILES['poster']['tmp_name'] ) ) ) {
				$pf = $_FILES['poster']; $pf['name'] = 'poster.jpg';
				$pr = wp_handle_upload( $pf, array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg' => 'image/jpeg' ) ) );
				if ( ! isset( $pr['error'] ) ) {
					// 영상과 같은 이름의 .jpg 로 맞춘다
					$target = preg_replace( '/\.[a-z0-9]+$/i', '.jpg', $r['file'] );
					if ( @rename( $pr['file'], $target ) ) $item['poster'] = $subdir . '/' . basename( $target );
					else $item['poster'] = $subdir . '/' . basename( $pr['file'] );
				}
			}
			$items['videos'][] = $item;
		}
		$added[] = $item;
	}
	remove_filter( 'upload_dir', $dir_filter ); remove_filter( 'sanitize_file_name', $name_filter, 99 );
	$saved = $added ? md_care_save_items( $slug, $items ) : null;
	$check = md_care_items( $slug, $t );
	wp_send_json_success( array( 'added' => $added, 'errors' => $errors, 'items' => $items, 'saved' => $saved, 'persisted' => array( 'photos' => count( $check['photos'] ), 'videos' => count( $check['videos'] ) ) ) );
}
add_action( 'wp_ajax_md_care_upload', 'md_care_ajax_upload' );

/** 삭제 · 목록에서 빼고 파일도 지운다 */
function md_care_ajax_delete() {
	list( $slug, $t ) = md_care_ajax_guard();
	$kind = ( $_POST['kind'] ?? 'photo' ) === 'video' ? 'videos' : 'photos';
	$i = (int) ( $_POST['index'] ?? -1 );
	$items = md_care_items( $slug, $t );
	if ( ! isset( $items[ $kind ][ $i ] ) ) wp_send_json_error( array( 'message' => '항목이 없습니다.' ), 400 );
	$it = $items[ $kind ][ $i ];
	array_splice( $items[ $kind ], $i, 1 );
	md_care_save_items( $slug, $items );
	// 같은 파일을 다른 항목이 쓰지 않을 때만 지운다
	$still = wp_json_encode( $items );
	foreach ( array( $it['src'] ?? '', $it['file'] ?? '', $it['poster'] ?? '' ) as $rel ) {
		if ( $rel && strpos( $still, $rel ) === false ) md_care_unlink_rel( $rel );
	}
	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_md_care_delete', 'md_care_ajax_delete' );

/** 제목(캡션) 수정 */
function md_care_ajax_caption() {
	list( $slug, $t ) = md_care_ajax_guard();
	$kind = ( $_POST['kind'] ?? 'photo' ) === 'video' ? 'videos' : 'photos';
	$i = (int) ( $_POST['index'] ?? -1 ); $text = sanitize_text_field( wp_unslash( $_POST['text'] ?? '' ) );
	$items = md_care_items( $slug, $t );
	if ( ! isset( $items[ $kind ][ $i ] ) ) wp_send_json_error( array( 'message' => '항목이 없습니다.' ), 400 );
	$items[ $kind ][ $i ][ $kind === 'videos' ? 'title' : 'caption' ] = $text;
	md_care_save_items( $slug, $items );
	wp_send_json_success( array( 'text' => $text ) );
}
add_action( 'wp_ajax_md_care_caption', 'md_care_ajax_caption' );

/** 순서 이동 · from → to */
function md_care_ajax_move() {
	list( $slug, $t ) = md_care_ajax_guard();
	$kind = ( $_POST['kind'] ?? 'photo' ) === 'video' ? 'videos' : 'photos';
	$from = (int) ( $_POST['from'] ?? -1 ); $to = (int) ( $_POST['to'] ?? -1 );
	$items = md_care_items( $slug, $t ); $n = count( $items[ $kind ] );
	if ( $from < 0 || $from >= $n || $to < 0 || $to >= $n ) wp_send_json_error( array( 'message' => '범위 밖' ), 400 );
	$it = $items[ $kind ][ $from ]; array_splice( $items[ $kind ], $from, 1 ); array_splice( $items[ $kind ], $to, 0, array( $it ) );
	md_care_save_items( $slug, $items );
	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_md_care_move', 'md_care_ajax_move' );

/** 편집본 초기화 · data 파일 목록으로 되돌린다 (올린 파일은 지우지 않음) */
function md_care_ajax_reset() {
	list( $slug, $t ) = md_care_ajax_guard();
	delete_option( 'md_care_items_' . $slug );
	$f = md_care_store_path( $slug ); if ( is_file( $f ) ) @unlink( $f );
	wp_send_json_success( array( 'items' => md_care_items( $slug, $t ) ) );
}
add_action( 'wp_ajax_md_care_reset', 'md_care_ajax_reset' );
