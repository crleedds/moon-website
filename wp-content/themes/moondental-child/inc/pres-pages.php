<?php
/**
 * v8 · 원장 지시 · 자연치아보존센터 메뉴를 각각 다른 페이지로
 *  /자연치아-살리기/ 아래에 실제 워드프레스 하위 페이지 다섯 개를 한 번 만든다 (검색 사이트맵 · 제목 · 설명이 페이지마다 따로 잡히게).
 *   충치치료 · 부분신경치료 · 신경치료 · 스케일링-잇몸치료 · 덴탈spa
 *  본문은 template-parts/pres-v5/{key}.php, 틀은 page-templates/page-pres-sub.php.
 *  한국어 새 디자인(md_v5_ko)에서만 세부 페이지로 연결 — 외국어는 예전처럼 한 페이지 안 앵커.
 *  예전 앵커 링크(/자연치아-살리기/#perio 등)는 본 페이지에서 세부 페이지로 넘겨 준다.
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_PRES_PARENT', '자연치아-살리기' );

/** key => 세부 페이지 정보 (anchor 는 예전 한 페이지 시절 앵커) */
function md_pres_sub_pages() {
	return array(
		'cavity' => array(
			'slug' => '충치치료', 'anchor' => 'cavity', 'menu' => '충치치료', 'title' => '충치치료',
			'h1a' => '충치치료', 'h1b' => '덜 깎고, 오래 쓰게',
			'lead' => '충치는 일찍 발견할수록 덜 깎습니다. 불소 · 실란트부터 레진 · 세라믹 인레이 · 크라운까지, 진행 정도에 맞는 가장 보존적인 방법을 고릅니다.',
			'card' => '불소 · 레진부터 인레이 · 크라운까지 단계별로',
		),
		'vpt' => array(
			'slug' => '부분신경치료', 'anchor' => 'vpt', 'menu' => '부분신경치료(치수보존술)', 'title' => '부분신경치료(치수보존술)',
			'h1a' => '부분신경치료', 'h1b' => '신경을 다 빼지 않고 살립니다',
			'lead' => '염증이 생긴 신경만 처리하고 건강한 신경은 MTA · Biodentine으로 덮어 보존합니다. 신경치료나 발치를 권유받으셨다면 한 번 더 살펴봐 드립니다.',
			'card' => '신경을 다 빼지 않고 건강한 부분을 살림',
		),
		'endo' => array(
			'slug' => '신경치료', 'anchor' => 'endo', 'menu' => '신경치료', 'title' => '신경치료',
			'h1a' => '신경치료', 'h1b' => '치아를 지키는 마지막 기회',
			'lead' => '충치가 신경까지 닿았을 때 신경치료로 발치를 막고 자연치아를 살립니다. CBCT 3D 진단으로 다른 곳에서 실패한 치아도 다시 살리는 시도를 합니다.',
			'card' => '발치를 막는 마지막 보존 치료',
		),
		'perio' => array(
			'slug' => '스케일링-잇몸치료', 'anchor' => 'perio', 'menu' => '스케일링 & 잇몸치료', 'title' => '스케일링 & 잇몸치료',
			'h1a' => '스케일링 & 잇몸치료', 'h1b' => '잇몸이 건강해야 치아가 남습니다',
			'lead' => '1년에 한 번 건강보험 스케일링부터 치근활택술 · 치주소파술 같은 잇몸치료, 치료 뒤 유지관리까지. 잇몸 상태에 맞춰 단계별로 진행합니다.',
			'card' => '연 1회 보험 스케일링부터 치주염 치료 · 유지관리까지',
		),
		'spa' => array(
			'slug' => '덴탈spa', 'anchor' => 'spa', 'menu' => '덴탈SPA', 'title' => '덴탈SPA',
			'h1a' => '덴탈SPA', 'h1b' => '치료의 끝을 관리의 시작으로',
			'lead' => '스케일링 · 에어플로우 · 불소도포 · 양치 코칭을 하나로 묶은 60~90분 예방 프로그램입니다. 잇몸치료 · 임플란트 · 교정 뒤 유지관리도 이어집니다.',
			'card' => '치료 뒤를 지키는 60~90분 예방 프로그램',
		),
	);
}

/** 세부 페이지를 쓰는가 — 한국어 새 디자인일 때만 */
function md_pres_sub_on() {
	return function_exists( 'md_v5_ko' ) && md_v5_ko();
}

/** 세부 페이지 주소 (쓰지 않을 때는 예전 앵커) */
function md_pres_sub_url( $key ) {
	$p = md_pres_sub_pages();
	if ( empty( $p[ $key ] ) ) { return home_url( '/' . MD_PRES_PARENT . '/' ); }
	if ( ! md_pres_sub_on() ) { return home_url( '/' . MD_PRES_PARENT . '/#' . $p[ $key ]['anchor'] ); }
	return home_url( '/' . MD_PRES_PARENT . '/' . $p[ $key ]['slug'] . '/' );
}

/** 지금 페이지가 세부 페이지면 그 key */
function md_pres_sub_current() {
	if ( ! is_page() ) { return ''; }
	$post = get_queried_object();
	if ( ! $post || empty( $post->post_parent ) ) { return ''; }
	$parent = get_post( $post->post_parent );
	if ( ! $parent || urldecode( $parent->post_name ) !== MD_PRES_PARENT ) { return ''; }
	$slug = urldecode( $post->post_name );
	foreach ( md_pres_sub_pages() as $k => $p ) {
		if ( $p['slug'] === $slug ) { return $k; }
	}
	return '';
}

/* 하위 페이지 다섯 개를 한 번 만든다 (이미 있으면 건너뜀 · 모두 있으면 다시 확인하지 않음) */
add_action( 'init', function () {
	if ( get_option( 'md_pres_pages_v1' ) === 'done' ) { return; }
	$parent = get_page_by_path( MD_PRES_PARENT );
	if ( ! $parent ) { return; }
	$all = true;
	$order = 1;
	foreach ( md_pres_sub_pages() as $k => $p ) {
		$exist = get_page_by_path( MD_PRES_PARENT . '/' . $p['slug'] );
		if ( ! $exist ) {
			$id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_parent'  => $parent->ID,
				'post_title'   => $p['title'],
				'post_name'    => $p['slug'],
				'post_content' => '',
				'menu_order'   => $order,
				'post_author'  => (int) $parent->post_author,
			), true );
			if ( is_wp_error( $id ) || ! $id ) { $all = false; continue; }
			update_post_meta( $id, '_wp_page_template', 'page-templates/page-pres-sub.php' );
			if ( function_exists( 'pll_set_post_language' ) ) { pll_set_post_language( $id, 'ko' ); }
		}
		$order++;
	}
	if ( $all ) { update_option( 'md_pres_pages_v1', 'done', false ); }
}, 30 );

/* 틀 — 하위 페이지는 항상 page-pres-sub.php (관리자에서 틀 지정이 빠져도) */
add_filter( 'template_include', function ( $template ) {
	if ( md_pres_sub_current() ) {
		$t = get_stylesheet_directory() . '/page-templates/page-pres-sub.php';
		if ( file_exists( $t ) ) { return $t; }
	}
	return $template;
}, 998 );

/* 예전 앵커 링크 → 세부 페이지 (본 페이지에서 주소 끝 #perio 등을 보고 넘겨 줌) */
add_action( 'wp_footer', function () {
	if ( ! md_pres_sub_on() || ! is_page() ) { return; }
	$post = get_queried_object();
	if ( ! $post || urldecode( $post->post_name ) !== MD_PRES_PARENT || $post->post_parent ) { return; }
	$map = array();
	foreach ( md_pres_sub_pages() as $k => $p ) { $map[ $p['anchor'] ] = md_pres_sub_url( $k ); }
	$map['pulpcap'] = md_pres_sub_url( 'vpt' );
	echo '<script>(function(){var m=' . wp_json_encode( $map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';var h=(location.hash||"").slice(1);if(m[h]){location.replace(m[h]);}})();</script>';
}, 1 );
