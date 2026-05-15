<?php
/**
 * Moon Dental Child Theme — functions.php
 *
 * 한아의료재단 문치과병원
 * Astra 자식 테마 기능 정의
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 부모 테마(Astra) + Pretendard 폰트 + 자식 테마 스타일 enqueue
 * 로딩 순서: Astra → Pretendard → Child (Child가 마지막이어야 오버라이드 가능)
 */
function moondental_enqueue_styles() {

	// 1. 부모 테마 스타일
	wp_enqueue_style(
		'astra-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( 'astra' )->get( 'Version' )
	);

	// 2. Pretendard Variable (CDN, dynamic-subset 한글 최적화)
	wp_enqueue_style(
		'pretendard-variable',
		'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/variable/pretendardvariable-dynamic-subset.css',
		array(),
		'1.3.9'
	);

	// 3. 자식 테마 스타일 (가장 마지막)
	wp_enqueue_style(
		'moondental-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'astra-parent-style', 'pretendard-variable' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'moondental_enqueue_styles', 15 );


/**
 * 워드프레스 기본 이모지 스크립트 제거 (속도 최적화, 한국 사용자 거의 사용 안 함)
 */
function moondental_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'moondental_disable_emojis' );


/**
 * 외부 oEmbed 비활성화 (보안 + 속도)
 */
function moondental_remove_oembed() {
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'moondental_remove_oembed' );


/**
 * 댓글 기능 전역 비활성화 (의료 사이트 기본값, 필요 시 제거)
 */
function moondental_disable_comments_post_types_support() {
	$post_types = get_post_types();
	foreach ( $post_types as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}
add_action( 'admin_init', 'moondental_disable_comments_post_types_support' );


/**
 * 관리자 바에서 댓글 메뉴 제거
 */
function moondental_remove_comments_admin_bar() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'comments' );
}
add_action( 'wp_before_admin_bar_render', 'moondental_remove_comments_admin_bar' );


/**
 * 한글 슬러그 자동 영문 변환 권장 — 운영자에게 admin notice
 * (실제 변환은 Permalink Manager Lite 플러그인 권장)
 */
function moondental_admin_slug_notice() {
	$screen = get_current_screen();
	if ( $screen && $screen->base === 'post' ) {
		echo '<div class="notice notice-info is-dismissible"><p><strong>안내:</strong> SEO를 위해 URL 슬러그는 영문/숫자만 사용하세요. (예: implant, ortho-event)</p></div>';
	}
}
add_action( 'admin_notices', 'moondental_admin_slug_notice' );


/**
 * 진료시간/대표전화/주소 — 사이트 전역에서 사용할 메타 정보
 * 사용 예: echo moondental_get_info( 'phone' );
 */
function moondental_get_info( $key = '' ) {
	$info = array(
		'name_kr'    => '한아의료재단 문치과병원',
		'name_short' => '문치과병원',
		'phone'      => '041-000-0000',           // ← 실제 번호로 교체
		'address'    => '충남 천안시 ...',          // ← 실제 주소로 교체
		'hours_wd'   => '평일 09:30 — 18:30',
		'hours_sat'  => '토요일 09:30 — 13:30',
		'hours_off'  => '일요일·공휴일 휴진',
		'biz_no'     => '000-00-00000',
		'rep'        => '문은수',
	);
	if ( empty( $key ) ) {
		return $info;
	}
	return isset( $info[ $key ] ) ? $info[ $key ] : '';
}


/**
 * 단축코드 [moondental_info key="phone"] 으로 어디서나 사용 가능
 */
function moondental_info_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'key' => 'phone' ), $atts );
	return esc_html( moondental_get_info( $atts['key'] ) );
}
add_shortcode( 'moondental_info', 'moondental_info_shortcode' );


/**
 * Elementor에 자식 테마 위치 알림 (Elementor가 자식 테마 인식)
 */
function moondental_elementor_locations( $elementor_theme_manager ) {
	$elementor_theme_manager->register_all_core_location();
}
add_action( 'elementor/theme/register_locations', 'moondental_elementor_locations' );
