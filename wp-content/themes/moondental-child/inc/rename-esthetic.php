<?php
/**
 * v4.23 · 원장 지시 · 「스마일디자인센터」 → 「심미치료센터」 (이름 · 주소 변경)
 *
 *  1) 옛 주소(/스마일디자인센터/ · /en/스마일디자인센터/ …) → 새 주소 301
 *  2) 한 번만 · 페이지 주소 · 제목, 메뉴, 사용자 정의 문구, 글 본문 링크를 새 이름으로
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* 1) 옛 주소 → 새 주소 (영구 이동) */
add_action( 'init', function () {
	if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) ) return;
	$uri  = (string) $_SERVER['REQUEST_URI'];
	$path = rawurldecode( (string) parse_url( $uri, PHP_URL_PATH ) );
	/* v5.1 · 예전 /심미치료/ 페이지도 심미치료센터로 합침 */
	if ( preg_match( '#^/((?:en|ja|zh|vi|ru|mn)/)?심미치료/?$#u', $path, $mm ) ) {
		$q = parse_url( $uri, PHP_URL_QUERY );
		wp_redirect( home_url( '/' . ( $mm[1] ?? '' ) . rawurlencode( '심미치료센터' ) . '/' ) . ( $q ? '?' . $q : '' ), 301 );
		exit;
	}
	if ( strpos( $path, '스마일디자인센터' ) === false ) return;
	if ( ! preg_match( '#^/((?:en|ja|zh|vi|ru|mn)/)?스마일디자인센터(/.*)?$#u', $path, $m ) ) return;
	$rest = ( isset( $m[2] ) && $m[2] !== '' ) ? $m[2] : '/';
	$new  = '/' . ( $m[1] ?? '' ) . '심미치료센터' . $rest;
	$new  = implode( '/', array_map( 'rawurlencode', explode( '/', $new ) ) );
	$q    = parse_url( $uri, PHP_URL_QUERY );
	wp_redirect( home_url( $new ) . ( $q ? '?' . $q : '' ), 301 );
	exit;
}, 0 );

/* 2) 데이터 이전 · 한 번만 */
add_action( 'init', function () {
	if ( get_option( 'md_rename_esthetic_v423' ) === 'done' ) return;
	global $wpdb;

	$old    = '스마일디자인센터';
	$new    = '심미치료센터';
	$old_lo = strtolower( rawurlencode( $old ) );
	$old_up = strtoupper( rawurlencode( $old ) );
	$new_en = rawurlencode( $new );
	$pairs  = array(
		$old_lo        => strtolower( $new_en ),
		$old_up        => $new_en,
		$old           => $new,
		'스마일디자인' => '심미치료',
	);

	// 페이지 주소 · 제목
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_name = %s", $old_lo ) );
	foreach ( $ids as $pid ) {
		$title = (string) $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $pid ) );
		wp_update_post( array(
			'ID'         => (int) $pid,
			'post_name'  => sanitize_title( $new ),
			'post_title' => str_replace( '스마일디자인', '심미치료', $title ),
		) );
		add_post_meta( (int) $pid, '_wp_old_slug', $old_lo );
	}

	// 메뉴 이름 · 메뉴 링크 · 글 본문
	foreach ( $pairs as $a => $b ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_title = REPLACE( post_title, %s, %s ) WHERE post_type = 'nav_menu_item'", $a, $b ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, %s, %s ) WHERE meta_key = '_menu_item_url'", $a, $b ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s ) WHERE post_type NOT IN ( 'revision', 'nav_menu_item' ) AND post_content LIKE %s", $a, $b, '%' . $wpdb->esc_like( $a ) . '%' ) );
	}

	// 사용자 정의하기 문구 (md_content_*)
	$mods = get_theme_mods();
	if ( is_array( $mods ) ) {
		$changed = false;
		foreach ( $mods as $k => $v ) {
			if ( ! is_string( $v ) ) continue;
			$nv = str_replace( array_keys( $pairs ), array_values( $pairs ), $v );
			if ( $nv !== $v ) { $mods[ $k ] = $nv; $changed = true; }
		}
		if ( $changed ) update_option( 'theme_mods_' . get_option( 'stylesheet' ), $mods );
	}

	// 캐시 비우기
	wp_cache_flush();
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_pcache\_%' OR option_name LIKE '\_transient\_timeout\_md\_pcache\_%'" );
	if ( function_exists( 'wp_cache_clear_cache' ) ) wp_cache_clear_cache();

	update_option( 'md_rename_esthetic_v423', 'done' );
}, 5 );

/* v4.24 · 영문 라벨 「SMILE DESIGN CENTER」 도 바꾼다 (한 번) */
add_action( 'init', function () {
	if ( get_option( 'md_rename_esthetic_v424' ) === 'done' ) return;
	$mods = get_theme_mods();
	if ( is_array( $mods ) ) {
		$changed = false;
		foreach ( $mods as $k => $v ) {
			if ( ! is_string( $v ) ) continue;
			$nv = str_ireplace( array( 'SMILE DESIGN CENTER', 'Smile Design Center' ), 'AESTHETIC DENTISTRY CENTER', $v );
			if ( $nv !== $v ) { $mods[ $k ] = $nv; $changed = true; }
		}
		if ( $changed ) update_option( 'theme_mods_' . get_option( 'stylesheet' ), $mods );
	}
	update_option( 'md_rename_esthetic_v424', 'done' );
}, 6 );

/* v5.1 · 합친 /심미치료/ 페이지는 사이트맵에서 뺀다 (주소는 심미치료센터로 301) */
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	$p = get_page_by_path( '심미치료' );
	if ( $p ) $ids[] = (int) $p->ID;
	return $ids;
} );

/* v6.6 · 내용 없는 /장치교정/ → /브라켓-치아교정/ (301) · 사이트맵에서 빼기 */
add_action( 'init', function () {
	if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) ) return;
	$path = rawurldecode( (string) parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
	if ( ! preg_match( '#^/((?:en|ja|zh|vi|ru|mn)/)?장치교정/?$#u', $path, $m ) ) return;
	wp_redirect( home_url( '/' . ( $m[1] ?? '' ) . rawurlencode( '브라켓-치아교정' ) . '/' ), 301 );
	exit;
}, 0 );
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	$p = get_page_by_path( '장치교정' );
	if ( $p ) $ids[] = (int) $p->ID;
	return $ids;
} );
