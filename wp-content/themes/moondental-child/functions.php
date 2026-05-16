<?php
/**
 * Moon Dental Child Theme — functions.php
 *
 * 한아의료재단 문치과병원
 * Astra 자식 테마 기능 정의
 *
 * @package moondental-child
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MOONDENTAL_VERSION', '1.1.0' );
define( 'MOONDENTAL_DIR',     get_stylesheet_directory() );
define( 'MOONDENTAL_URI',     get_stylesheet_directory_uri() );


/* ============================================================
 * 1. Theme Setup
 * ========================================================== */

/**
 * 메뉴 위치, 이미지 사이즈, 테마 지원 기능 등록
 */
function moondental_theme_setup() {
	register_nav_menus( array(
		'primary'    => '주 메뉴 (헤더)',
		'utility'    => '유틸리티 메뉴 (헤더 상단 우측)',
		'footer'     => '푸터 메뉴',
	) );

	add_theme_support( 'post-thumbnails' );

	// 진료영역 카드용 이미지
	add_image_size( 'moondental-service',  640, 480, true );
	// 의료진 인물 사진
	add_image_size( 'moondental-doctor',   720, 900, true );
	// 히어로 이미지
	add_image_size( 'moondental-hero',    1280, 1600, true );
	// 시설 갤러리
	add_image_size( 'moondental-gallery', 1024,  768, true );
}
add_action( 'after_setup_theme', 'moondental_theme_setup', 11 );


/* ============================================================
 * 2. Asset Enqueue (CSS / JS)
 * ========================================================== */

/**
 * 부모 테마(Astra) + Pretendard + 자식 테마 스타일 enqueue.
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

	// 2. Pretendard Variable
	wp_enqueue_style(
		'pretendard-variable',
		'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/variable/pretendardvariable-dynamic-subset.css',
		array(),
		'1.3.9'
	);

	// 3. 자식 테마 스타일
	wp_enqueue_style(
		'moondental-child-style',
		MOONDENTAL_URI . '/style.css',
		array( 'astra-parent-style', 'pretendard-variable' ),
		MOONDENTAL_VERSION
	);

	// 4. 추가 인터랙션 JS (필요 시)
	$js_path = MOONDENTAL_DIR . '/assets/js/main.js';
	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'moondental-main',
			MOONDENTAL_URI . '/assets/js/main.js',
			array(),
			MOONDENTAL_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'moondental_enqueue_styles', 15 );


/* ============================================================
 * 3. 병원 정보 — Customizer 연동
 * ========================================================== */

/**
 * 사이트 전역에서 사용할 병원 메타 정보.
 * Customizer 값이 있으면 그 값을, 없으면 아래 기본값을 반환한다.
 *
 * 사용 예:
 *   echo esc_html( moondental_get_info( 'phone' ) );
 *   [moondental_info key="phone"]
 *
 * @param string $key 필드 키. 비어있으면 전체 배열 반환.
 * @return mixed
 */
function moondental_get_info( $key = '' ) {
	$defaults = array(
		'name_full'    => '한아의료재단 문치과병원',
		'name_short'   => '문치과병원',
		'name_en'      => 'Moon Dental Hospital',
		'tagline'      => '가족처럼 따뜻하게, 전문가답게 정확하게',
		'phone'        => '041-000-0000',
		'phone_link'   => '0410000000',
		'address'      => '충청남도 천안시 …',
		'address_road' => '',
		'hours_wd'     => '평일  09:30 – 18:30',
		'hours_sat'    => '토요일 09:30 – 13:30',
		'hours_lunch'  => '점심  13:00 – 14:00',
		'hours_off'    => '일요일·공휴일 휴진',
		'biz_no'       => '000-00-00000',
		'rep'          => '문은수',
		'email'        => '',
		'kakao_url'    => '',
		'instagram'    => '',
		'blog_url'     => '',
		'naver_place'  => '',
		'map_embed'    => '',
	);

	$info = array();
	foreach ( $defaults as $field_key => $default ) {
		$info[ $field_key ] = get_theme_mod( 'moondental_' . $field_key, $default );
	}

	if ( empty( $key ) ) {
		return $info;
	}
	return isset( $info[ $key ] ) ? $info[ $key ] : '';
}

/**
 * 단축코드 [moondental_info key="phone"]
 */
function moondental_info_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'key' => 'phone' ), $atts );
	return esc_html( moondental_get_info( $atts['key'] ) );
}
add_shortcode( 'moondental_info', 'moondental_info_shortcode' );


/* ============================================================
 * 4. WordPress Customizer (관리자에서 병원 정보 편집)
 * ========================================================== */

/**
 * 외모 > 사용자 정의하기 > "문치과병원 정보" 섹션에서
 * 비개발자도 병원 정보를 편집할 수 있도록 등록.
 */
function moondental_customize_register( $wp_customize ) {

	$wp_customize->add_panel( 'moondental_panel', array(
		'title'    => '문치과병원 설정',
		'priority' => 20,
	) );

	$wp_customize->add_section( 'moondental_section_info', array(
		'title'       => '병원 기본 정보',
		'panel'       => 'moondental_panel',
		'description' => '푸터·헤더·구조화데이터에 자동 반영됩니다.',
	) );

	$wp_customize->add_section( 'moondental_section_hours', array(
		'title' => '진료시간',
		'panel' => 'moondental_panel',
	) );

	$wp_customize->add_section( 'moondental_section_sns', array(
		'title' => 'SNS / 외부 링크',
		'panel' => 'moondental_panel',
	) );

	$wp_customize->add_section( 'moondental_section_home_hero', array(
		'title'       => '홈 — Hero 섹션',
		'panel'       => 'moondental_panel',
		'description' => '메인 화면 첫 영역의 문구·이미지.',
	) );

	$wp_customize->add_section( 'moondental_section_home_doctor', array(
		'title'       => '홈 — 의료진 섹션',
		'panel'       => 'moondental_panel',
		'description' => '대표원장 소개 영역의 약력·사진. 약력은 줄바꿈으로 구분합니다.',
	) );

	$fields = array(
		// section_info
		array( 'key' => 'name_full',    'label' => '병원명 (정식)',            'section' => 'moondental_section_info' ),
		array( 'key' => 'name_short',   'label' => '병원명 (짧은 표기)',        'section' => 'moondental_section_info' ),
		array( 'key' => 'name_en',      'label' => '병원명 (영문)',            'section' => 'moondental_section_info' ),
		array( 'key' => 'tagline',      'label' => '한 줄 슬로건',             'section' => 'moondental_section_info' ),
		array( 'key' => 'rep',          'label' => '대표원장',                'section' => 'moondental_section_info' ),
		array( 'key' => 'phone',        'label' => '대표 전화 (표시용)',        'section' => 'moondental_section_info' ),
		array( 'key' => 'phone_link',   'label' => '전화 링크 (tel: 용, 숫자만)','section' => 'moondental_section_info' ),
		array( 'key' => 'email',        'label' => '대표 이메일',              'section' => 'moondental_section_info' ),
		array( 'key' => 'address',      'label' => '주소 (지번 또는 표기용)',    'section' => 'moondental_section_info', 'type' => 'textarea' ),
		array( 'key' => 'address_road', 'label' => '도로명 주소',              'section' => 'moondental_section_info' ),
		array( 'key' => 'biz_no',       'label' => '사업자등록번호',           'section' => 'moondental_section_info' ),

		// section_hours
		array( 'key' => 'hours_wd',     'label' => '평일 진료시간',            'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_sat',    'label' => '토요일 진료시간',          'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_lunch',  'label' => '점심시간',                'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_off',    'label' => '휴진 안내',                'section' => 'moondental_section_hours' ),

		// section_sns
		array( 'key' => 'kakao_url',    'label' => '카카오톡 채널 URL',         'section' => 'moondental_section_sns' ),
		array( 'key' => 'instagram',    'label' => '인스타그램 URL',          'section' => 'moondental_section_sns' ),
		array( 'key' => 'blog_url',     'label' => '블로그 URL',              'section' => 'moondental_section_sns' ),
		array( 'key' => 'naver_place',  'label' => '네이버 플레이스 URL',       'section' => 'moondental_section_sns' ),
		array( 'key' => 'map_embed',    'label' => '지도 임베드 코드 (HTML iframe)', 'section' => 'moondental_section_sns', 'type' => 'textarea' ),
	);

	$defaults = moondental_get_info();
	foreach ( $fields as $field ) {
		$setting_id = 'moondental_' . $field['key'];
		$wp_customize->add_setting( $setting_id, array(
			'default'           => $defaults[ $field['key'] ] ?? '',
			'sanitize_callback' => ( ( $field['type'] ?? '' ) === 'textarea' ) ? 'sanitize_textarea_field' : 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $field['label'],
			'section' => $field['section'],
			'type'    => $field['type'] ?? 'text',
		) );
	}

	/* ── Home Hero section content ─────────────────────────────── */
	$hero_fields = array(
		'hero_eyebrow' => array( 'label' => '상단 작은 태그',   'type' => 'text',     'default' => '천안 · 가족 치과' ),
		'hero_title_a' => array( 'label' => '메인 카피 1행',     'type' => 'text',     'default' => '내 가족이 와도 안심할 수 있는' ),
		'hero_title_b' => array( 'label' => '메인 카피 2행 (강조)','type' => 'text',     'default' => '따뜻한 치과' ),
		'hero_lead'    => array( 'label' => '서브 카피',          'type' => 'textarea', 'default' => '' ),
	);
	foreach ( $hero_fields as $key => $f ) {
		$id = 'moondental_' . $key;
		$wp_customize->add_setting( $id, array(
			'default'           => $f['default'],
			'sanitize_callback' => $f['type'] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $id, array(
			'label'   => $f['label'],
			'section' => 'moondental_section_home_hero',
			'type'    => $f['type'],
		) );
	}
	$wp_customize->add_setting( 'moondental_hero_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'moondental_hero_image', array(
		'label'     => '메인 이미지 (세로 비율 권장)',
		'section'   => 'moondental_section_home_hero',
		'mime_type' => 'image',
	) ) );

	/* ── Home Doctor section content ───────────────────────────── */
	$doctor_fields = array(
		'doctor_role' => array( 'label' => '직책',                 'type' => 'text',     'default' => '대표원장' ),
		'doctor_lead' => array( 'label' => '한 줄 진료 철학',         'type' => 'textarea', 'default' => '환자의 이야기를 충분히 듣고, 가장 보존적인 치료부터 제안합니다.' ),
		'doctor_bio'  => array( 'label' => '약력 (줄바꿈으로 구분)', 'type' => 'textarea', 'default' => "서울대학교 치과대학 졸업\n대한치과의사협회 정회원\n대한구강악안면임플란트학회 정회원" ),
	);
	foreach ( $doctor_fields as $key => $f ) {
		$id = 'moondental_' . $key;
		$wp_customize->add_setting( $id, array(
			'default'           => $f['default'],
			'sanitize_callback' => $f['type'] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $id, array(
			'label'   => $f['label'],
			'section' => 'moondental_section_home_doctor',
			'type'    => $f['type'],
		) );
	}
	$wp_customize->add_setting( 'moondental_doctor_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'moondental_doctor_image', array(
		'label'     => '원장님 사진 (세로 비율 권장)',
		'section'   => 'moondental_section_home_doctor',
		'mime_type' => 'image',
	) ) );
}
add_action( 'customize_register', 'moondental_customize_register' );


/* ============================================================
 * 5. 사이트 최적화 / 보안 / UX
 * ========================================================== */

/**
 * 워드프레스 기본 이모지 스크립트 제거 (속도)
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
 * 댓글 기능 전역 비활성화 (의료 사이트 기본값)
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
	if ( isset( $GLOBALS['wp_admin_bar'] ) ) {
		$GLOBALS['wp_admin_bar']->remove_menu( 'comments' );
	}
}
add_action( 'wp_before_admin_bar_render', 'moondental_remove_comments_admin_bar' );

/**
 * 한글 슬러그 → 영문 사용 권장 안내 (post 화면)
 */
function moondental_admin_slug_notice() {
	$screen = get_current_screen();
	if ( $screen && $screen->base === 'post' ) {
		echo '<div class="notice notice-info is-dismissible"><p><strong>안내:</strong> SEO를 위해 URL 슬러그는 영문/숫자만 사용하세요. (예: implant, ortho-event)</p></div>';
	}
}
add_action( 'admin_notices', 'moondental_admin_slug_notice' );

/**
 * WP 버전 노출 제거 (보안 — 헤더/RSS)
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * XML-RPC 비활성화 (공격 표면 축소)
 */
add_filter( 'xmlrpc_enabled', '__return_false' );


/* ============================================================
 * 6. Elementor 통합 (자식 테마 위치 알림)
 * ========================================================== */
function moondental_elementor_locations( $elementor_theme_manager ) {
	$elementor_theme_manager->register_all_core_location();
}
add_action( 'elementor/theme/register_locations', 'moondental_elementor_locations' );


/* ============================================================
 * 7. Body Class
 * ========================================================== */
/**
 * body에 페이지 식별 클래스 추가 — CSS에서 페이지별 분기 시 편리.
 */
function moondental_body_class( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'md-page-home';
	}
	if ( is_page() ) {
		$classes[] = 'md-page-' . sanitize_html_class( get_post_field( 'post_name', get_queried_object_id() ) );
	}
	return $classes;
}
add_filter( 'body_class', 'moondental_body_class' );


/* ============================================================
 * 8. Nav Menu Fallbacks
 * ========================================================== */
/**
 * 주 메뉴가 미설정일 때 보여줄 임시 메뉴.
 */
function moondental_nav_fallback() {
	$items = array(
		array( 'label' => '병원소개', 'url' => home_url( '/about/' ) ),
		array( 'label' => '진료안내', 'url' => home_url( '/services/' ) ),
		array( 'label' => '의료진',   'url' => home_url( '/doctors/' ) ),
		array( 'label' => '시설',     'url' => home_url( '/facility/' ) ),
		array( 'label' => '공지사항', 'url' => home_url( '/notices/' ) ),
		array( 'label' => '오시는 길','url' => home_url( '/location/' ) ),
	);
	echo '<ul class="md-nav">';
	foreach ( $items as $item ) {
		printf(
			'<li class="menu-item"><a href="%s">%s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}
	echo '</ul>';
}

/**
 * 푸터 메뉴 fallback.
 */
function moondental_footer_menu_fallback() {
	$items = array(
		'병원소개'  => '/about/',
		'의료진'    => '/doctors/',
		'시설'      => '/facility/',
		'오시는 길' => '/location/',
		'공지사항'  => '/notices/',
		'개인정보처리방침' => '/privacy/',
	);
	echo '<ul>';
	foreach ( $items as $label => $path ) {
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( home_url( $path ) ),
			esc_html( $label )
		);
	}
	echo '</ul>';
}


/* ============================================================
 * 9. Helper: 진료영역 데이터 (5개 종합 진료)
 * ========================================================== */
/**
 * 홈/메뉴/구조에서 공통으로 사용하는 진료영역 정의.
 * slug → 페이지를 만들고 같은 slug로 두면 자동 링크 연결.
 */
function moondental_get_services() {
	return array(
		array(
			'slug'  => 'general',
			'title' => '일반진료',
			'icon'  => '🦷',
			'desc'  => '충치·잇몸치료·신경치료 등 기본 진료. 정확한 진단과 통증 최소화를 우선합니다.',
		),
		array(
			'slug'  => 'implant',
			'title' => '임플란트',
			'icon'  => '🪛',
			'desc'  => '풍부한 임상 경험을 바탕으로 단일·다수·전체 임플란트까지 안정적으로 식립합니다.',
		),
		array(
			'slug'  => 'ortho',
			'title' => '교정',
			'icon'  => '〰',
			'desc'  => '투명교정·설측·소아교정 — 라이프스타일과 연령에 맞춘 맞춤 교정 계획을 제시합니다.',
		),
		array(
			'slug'  => 'aesthetic',
			'title' => '심미치료',
			'icon'  => '✨',
			'desc'  => '라미네이트·미백·올세라믹 — 자연스러우면서도 오래 가는 미소를 디자인합니다.',
		),
		array(
			'slug'  => 'pediatric',
			'title' => '소아·예방',
			'icon'  => '🧒',
			'desc'  => '아이의 첫 치과 경험이 편안하도록. 불소도포·실란트·정기검진으로 평생 치아를 지킵니다.',
		),
	);
}
