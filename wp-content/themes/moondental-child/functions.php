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

define( 'MOONDENTAL_VERSION', '3.14.4' );
define( 'MOONDENTAL_DIR',     get_stylesheet_directory() );
define( 'MOONDENTAL_URI',     get_stylesheet_directory_uri() );

require_once MOONDENTAL_DIR . '/inc/content-defaults.php';
require_once MOONDENTAL_DIR . '/inc/naver-importer.php';
require_once MOONDENTAL_DIR . '/inc/reservation.php';
require_once MOONDENTAL_DIR . '/inc/enhancements.php';
require_once MOONDENTAL_DIR . '/inc/customizer-content.php';
require_once MOONDENTAL_DIR . '/inc/strengths.php';
require_once MOONDENTAL_DIR . '/inc/regions.php';


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

	// 3. 자식 테마 스타일 — 파일 mtime을 cache-buster로 사용
	$css_path = MOONDENTAL_DIR . '/style.css';
	$css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : MOONDENTAL_VERSION;
	wp_enqueue_style(
		'moondental-child-style',
		MOONDENTAL_URI . '/style.css',
		array( 'astra-parent-style', 'pretendard-variable' ),
		$css_ver
	);

	// 4. 추가 인터랙션 JS (필요 시)
	$js_path = MOONDENTAL_DIR . '/assets/js/main.js';
	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'moondental-main',
			MOONDENTAL_URI . '/assets/js/main.js',
			array(),
			filemtime( $js_path ),
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
		'tagline'      => '실력과 품격있는 진료',
		'phone'        => '041-563-2875',
		'phone_link'   => '0415632875',
		'address'      => '충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동)',
		'address_road' => '충남 천안시 동남구 만남로 52, 문타워 9·10·11·13층',
		'hours_wd'     => '평일 09:00 – 20:30 (점심시간 없음)',
		'hours_thu'    => '목요일 09:00 – 18:00 (야간진료 없음)',
		'hours_sat'    => '토요일 09:00 – 14:00',
		'hours_lunch'  => '',
		'hours_off'    => '일요일·공휴일 휴진',
		'biz_no'       => '',
		'med_inst_no'  => '34400117',
		'rep'          => '문은수',
		'email'        => '',
		'kakao_url'    => 'http://pf.kakao.com/_VTcgE/chat',
		'instagram'    => 'https://www.instagram.com/moondentalhospital_official/',
		'blog_url'     => 'https://blog.naver.com/moondental1995',
		'facebook_url' => 'https://www.facebook.com/moondentist',
		'youtube_url'  => 'https://www.youtube.com/@%EC%B2%9C%EC%95%88%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90',
		'naver_place'  => 'https://booking.naver.com/booking/13/bizes/485314',
		'naver_map_url'=> 'https://map.naver.com/p/search/%ED%95%9C%EC%95%84%EC%9D%98%EB%A3%8C%EC%9E%AC%EB%8B%A8%20%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90',
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

/**
 * 진료시간 문자열에서 "09:00 – 18:00" 같은 시간 부분만 추출.
 */
function moondental_extract_time_range( $str ) {
	if ( preg_match( '/(\d{1,2}\s*:\s*\d{2}\s*[~\-–—]\s*\d{1,2}\s*:\s*\d{2})/u', (string) $str, $m ) ) {
		return trim( $m[1] );
	}
	return $str;
}

/**
 * 오늘 요일에 맞는 진료시간 라벨 반환. 예: "목요일 09:00 – 18:00", "일요일 휴진".
 * 한국 timezone(KST) 기준 — wp_date() 사용.
 */
function moondental_get_today_hours_label() {
	$info = moondental_get_info();
	$dow  = (int) wp_date( 'w' ); // 0=일 ~ 6=토
	$kor  = array( '일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일' );
	$today = $kor[ $dow ];

	if ( $dow === 0 ) {
		return $today . ' 휴진';
	}

	$source = '';
	if ( $dow === 4 ) {
		$source = $info['hours_thu'] ?? '';
	} elseif ( $dow === 6 ) {
		$source = $info['hours_sat'] ?? '';
	} else {
		$source = $info['hours_wd'] ?? '';
	}

	$time = moondental_extract_time_range( $source );
	if ( ! $time ) return $today;
	return $today . ' ' . $time;
}

/**
 * 병원 주소 → 네이버 지도 링크 헬퍼.
 *  비어있으면 빈 문자열 반환. 입력 주소가 없으면 moondental_get_info('address') 사용.
 *
 * @param string $text   화면에 표시할 텍스트 (비우면 주소 그대로 표시)
 * @param array  $attrs  추가 anchor 속성: class, data-track 등
 */
function md_address_link( $text = '', $attrs = array() ) {
	$info = moondental_get_info();
	$addr = $info['address'] ?? '';
	if ( ! $addr ) return '';

	$display = $text !== '' ? $text : $addr;
	$href = $info['naver_map_url'] ?: ( 'https://map.naver.com/p/search/' . rawurlencode( $info['name_full'] ?? '문치과병원' ) );

	$class = isset( $attrs['class'] ) ? $attrs['class'] : 'md-addr-link';
	$track = isset( $attrs['track'] ) ? $attrs['track'] : 'cta-address';

	return sprintf(
		'<a href="%s" target="_blank" rel="noopener" class="%s" data-track="%s" aria-label="네이버 지도에서 위치 보기">%s</a>',
		esc_url( $href ),
		esc_attr( $class ),
		esc_attr( $track ),
		esc_html( $display )
	);
}

/**
 * 전화번호 → tel: 링크 헬퍼.
 *  비어있으면 빈 문자열 반환.
 *
 * @param string $text   화면에 표시할 텍스트 (비우면 전화번호 그대로 표시)
 * @param array  $attrs  추가 anchor 속성: class, data-track 등
 */
function md_phone_link( $text = '', $attrs = array() ) {
	$info = moondental_get_info();
	$phone = $info['phone'] ?? '';
	if ( ! $phone ) return '';

	$link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $phone );
	$display = $text !== '' ? $text : $phone;

	$class = isset( $attrs['class'] ) ? $attrs['class'] : 'md-tel-link';
	$track = isset( $attrs['track'] ) ? $attrs['track'] : 'cta-tel';

	return sprintf(
		'<a href="tel:%s" class="%s" data-track="%s">%s</a>',
		esc_attr( $link ),
		esc_attr( $class ),
		esc_attr( $track ),
		esc_html( $display )
	);
}

/**
 * 사이트 전역 텍스트에서 알려진 주소 문자열을 네이버 지도 anchor로 자동 wrap.
 *  이미 anchor 안에 있는 텍스트는 건드리지 않음.
 *  지원 주소:
 *    - 신부 제5공영주차장 / (동남구 먹거리1길 10) — 5공영주차장
 *    - 충청남도 천안시 동남구 만남로 52 ... — 병원 본원
 *
 * @param string $html HTML or plain text
 * @return string
 */
function md_autolink_addresses( $html ) {
	if ( ! is_string( $html ) || $html === '' ) return $html;

	$info = moondental_get_info();
	$hospital_url = $info['naver_map_url']
		?: ( 'https://map.naver.com/p/search/' . rawurlencode( $info['name_full'] ?? '문치과병원' ) );
	$park5_url = 'https://map.naver.com/p/search/' . rawurlencode( '신부 제5공영주차장' );

	// 구체적 → 일반 순서: 긴 패턴이 먼저 매칭되도록
	$patterns = array(
		// 5공영주차장
		'신부 제5공영주차장(동남구 먹거리1길 10)'              => $park5_url,
		'신부 제5공영주차장 (동남구 먹거리1길 10)'             => $park5_url,
		'"신부 제5공영주차장"(동남구 먹거리1길 10)'            => $park5_url,
		'"신부 제5공영주차장" (동남구 먹거리1길 10)'           => $park5_url,
		'신부 제5공영주차장'                                  => $park5_url,
		'"신부 제5공영주차장"'                                => $park5_url,
		'동남구 먹거리1길 10'                                 => $park5_url,
		// 본원 (병원 네이버 지도로 연결)
		'본원 지하 기계식 주차장'                              => $hospital_url,
		'본원 지하 기계식'                                    => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동, 문타워빌딩)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층'      => $hospital_url,
		'충남 천안시 동남구 만남로 52, 문타워 9·10·11·13층'          => $hospital_url,
		// 구 표기 호환 — 사용자가 직접 입력한 옛 주소도 클릭 가능하게
		'충청남도 천안시 동남구 만남로 52, 문타워 9~13층 (신부동, 문타워빌딩)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9~13층 (신부동)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9~13층'      => $hospital_url,
		'충남 천안시 동남구 만남로 52, 문타워 9~13층'          => $hospital_url,
	);

	// 1) 기존 anchor 보호: <a ...>...</a> 를 토큰으로 임시 치환
	$tokens = array();
	$html = preg_replace_callback( '#<a\b[^>]*>.*?</a>#siu', function( $m ) use ( &$tokens ) {
		$tokens[] = $m[0];
		return '___MDA_' . ( count( $tokens ) - 1 ) . '___';
	}, $html );

	// 2) 주소 패턴 매칭 — 새로 만든 anchor도 토큰으로 보관해
	//    후속 패턴이 이미 wrap된 텍스트를 재차 wrap하는 nested anchor 버그 방지.
	foreach ( $patterns as $needle => $url ) {
		if ( strpos( $html, $needle ) === false ) continue;
		$anchor = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="md-addr-link" data-track="cta-autolink-addr">' . esc_html( $needle ) . '</a>';
		$tokens[] = $anchor;
		$token = '___MDA_' . ( count( $tokens ) - 1 ) . '___';
		$html = str_replace( $needle, $token, $html );
	}

	// 3) 토큰 → 원본/anchor 복원
	foreach ( $tokens as $i => $orig ) {
		$html = str_replace( '___MDA_' . $i . '___', $orig, $html );
	}

	return $html;
}

// 사이트 전역 the_content 필터로 자동 주소 링크 (priority 90 — wpautop 후 / lazy-image 전)
add_filter( 'the_content', 'md_autolink_addresses', 90 );

/**
 * Astra 부모 테마의 기본 scroll-to-top 버튼 끄기 — 테마 자체 .md-totop만 사용.
 *  Astra 4+ 옵션 키 'scroll-to-top-enable' 를 false로 강제.
 *  옵션 키가 다른 버전에서도 CSS .ast-scroll-top* 숨김으로 이중 안전.
 */
add_filter( 'astra_get_option_scroll-to-top-enable', '__return_false', 99 );
add_filter( 'astra_get_option_scroll-to-top-device', function() { return ''; }, 99 );

/**
 * 전역 예약 CTA 버튼 — 네이버 예약 + 카카오톡 상담 + 전화.
 *  URL/라벨은 사용자 정의하기 → 사이트 공통 콘텐츠 → 전역 예약 CTA 버튼 에서 편집.
 *  네이버/카카오 URL이 비어있으면 SNS 섹션의 값을 fallback으로 사용.
 *  라벨이 비어있으면 해당 버튼 자동 숨김.
 *
 * @param array $args {
 *   @type string $size   'lg' 또는 ''                — 버튼 크기 (기본 lg)
 *   @type string $align  'center' 또는 ''           — 가운데 정렬 여부 (기본 center)
 *   @type string $track  data-track prefix          — 분석용
 *   @type bool   $show_naver/$show_kakao/$show_call — 개별 버튼 강제 숨김
 * }
 * @return string HTML
 */
function md_render_reservation_ctas( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'size'       => 'lg',
		'align'      => 'center',
		'track'      => 'cta',
		'show_naver' => true,
		'show_kakao' => true,
		'show_call'  => true,
	) );

	$info = moondental_get_info();

	// URL fallback chain: cta_btn_*_url → 병원정보의 SNS URL
	$naver_url = function_exists( 'md_content' ) ? md_content( 'cta_btn_naver_url', '' ) : '';
	if ( empty( $naver_url ) ) $naver_url = $info['naver_place'] ?? '';

	$kakao_url = function_exists( 'md_content' ) ? md_content( 'cta_btn_kakao_url', '' ) : '';
	if ( empty( $kakao_url ) ) $kakao_url = $info['kakao_url'] ?? '';

	$naver_label = function_exists( 'md_content' ) ? md_content( 'cta_btn_naver_label', '📅 네이버 예약하기' ) : '📅 네이버 예약하기';
	$kakao_label = function_exists( 'md_content' ) ? md_content( 'cta_btn_kakao_label', '💬 카카오톡 상담하기' ) : '💬 카카오톡 상담하기';
	$call_label  = function_exists( 'md_content' ) ? md_content( 'cta_btn_call_label',  '📞 전화 상담' )       : '📞 전화 상담';
	$show_phone  = function_exists( 'md_content' ) ? md_content( 'cta_btn_show_phone',  'yes' )              : 'yes';

	$phone       = $info['phone'] ?? '';
	$phone_link  = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $phone );

	$btn_size_cls = $args['size'] === 'lg' ? ' md-btn--lg' : '';

	$group_style = 'display:flex; flex-wrap:wrap; gap:12px;';
	if ( $args['align'] === 'center' ) {
		$group_style .= ' justify-content:center;';
	}

	$out  = '<div class="md-btn-group md-rcta" style="' . esc_attr( $group_style ) . '">';

	// 네이버 예약 (primary)
	if ( $args['show_naver'] && $naver_label && $naver_url ) {
		$out .= '<a class="md-btn md-btn-primary' . esc_attr( $btn_size_cls ) . ' md-rcta__naver" '
		     . 'href="' . esc_url( $naver_url ) . '" target="_blank" rel="noopener" '
		     . 'data-track="' . esc_attr( $args['track'] ) . '-naver">'
		     . esc_html( $naver_label ) . '</a>';
	}

	// 카카오톡 상담 (secondary - yellow)
	if ( $args['show_kakao'] && $kakao_label && $kakao_url ) {
		$out .= '<a class="md-btn md-btn--kakao' . esc_attr( $btn_size_cls ) . ' md-rcta__kakao" '
		     . 'href="' . esc_url( $kakao_url ) . '" target="_blank" rel="noopener" '
		     . 'data-track="' . esc_attr( $args['track'] ) . '-kakao">'
		     . esc_html( $kakao_label ) . '</a>';
	}

	// 전화 (ghost)
	if ( $args['show_call'] && $call_label && $phone_link ) {
		$label = $call_label;
		if ( $show_phone !== 'no' && $phone ) {
			$label .= ' ' . $phone;
		}
		$out .= '<a class="md-btn md-btn-ghost' . esc_attr( $btn_size_cls ) . ' md-rcta__call" '
		     . 'href="tel:' . esc_attr( $phone_link ) . '" '
		     . 'data-track="' . esc_attr( $args['track'] ) . '-call">'
		     . esc_html( $label ) . '</a>';
	}

	$out .= '</div>';
	return $out;
}


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
		array( 'key' => 'med_inst_no',  'label' => '의료기관 고유번호',         'section' => 'moondental_section_info' ),

		// section_hours
		array( 'key' => 'hours_wd',     'label' => '평일 진료시간',            'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_thu',    'label' => '목요일 진료시간',          'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_sat',    'label' => '토요일 진료시간',          'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_lunch',  'label' => '점심시간 (선택)',          'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_off',    'label' => '휴진 안내',                'section' => 'moondental_section_hours' ),

		// section_sns
		array( 'key' => 'kakao_url',    'label' => '카카오톡 채널 URL',         'section' => 'moondental_section_sns' ),
		array( 'key' => 'naver_place',  'label' => '네이버 예약 URL',          'section' => 'moondental_section_sns' ),
		array( 'key' => 'naver_map_url','label' => '네이버 지도·플레이스 URL', 'section' => 'moondental_section_sns' ),
		array( 'key' => 'instagram',    'label' => '인스타그램 URL',          'section' => 'moondental_section_sns' ),
		array( 'key' => 'blog_url',     'label' => '네이버 블로그 URL',        'section' => 'moondental_section_sns' ),
		array( 'key' => 'facebook_url', 'label' => '페이스북 URL',            'section' => 'moondental_section_sns' ),
		array( 'key' => 'youtube_url',  'label' => '유튜브 채널 URL',          'section' => 'moondental_section_sns' ),
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
		'hero_eyebrow' => array( 'label' => '상단 작은 태그',     'type' => 'text',     'default' => '천안 만남로 · 1995년부터 한자리에서' ),
		'hero_title_a' => array( 'label' => '메인 카피 1행',       'type' => 'text',     'default' => '천안에서 30여년,' ),
		'hero_title_b' => array( 'label' => '메인 카피 2행 (강조)', 'type' => 'text',     'default' => '환자 한 분의 평생 치아를' ),
		'hero_lead'    => array( 'label' => '서브 카피',            'type' => 'textarea', 'default' => "천안 임플란트·천안 투명교정·천안 라미네이트·천안 자연치아 살리기까지.\n분야별 전문 의료진이 한 자리에서 — 충분히 듣고, 꼭 필요한 치료만 권합니다." ),
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

	/* ── 모든 페이지 — 오시는 길 섹션 (푸터 위) — 지도 이미지 / iframe 임베드 ───── */
	$wp_customize->add_section( 'moondental_section_flocation_map', array(
		'title'       => '오시는 길 — 지도 이미지/임베드',
		'panel'       => 'moondental_panel',
		'description' => '모든 페이지 푸터 위에 표시되는 지도. 우선순위: ①임베드 HTML > ②업로드 이미지 > ③/assets/images/map/naver-map.*. 네이버 지도는 보안상 iframe 임베드 불가 — 옵션 안내는 각 필드 설명 참고.',
	) );

	$wp_customize->add_setting( 'moondental_flocation_map_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'moondental_flocation_map_image', array(
		'label'       => '① 지도 이미지 업로드 (가장 쉬움)',
		'description' => '네이버 지도에서 화면 캡처한 후 여기에 업로드하세요. 가로/세로 비율 21:9 권장. 비워두면 /assets/images/map/naver-map.png(jpg/webp) 파일로 자동 fallback.',
		'section'     => 'moondental_section_flocation_map',
		'mime_type'   => 'image',
	) ) );

	$wp_customize->add_setting( 'moondental_flocation_map_embed', array(
		'default'           => '',
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'moondental_flocation_map_embed', array(
		'label'       => '② 지도 임베드 HTML (실시간 — 선택)',
		'description' => "비우면 위 이미지 사용. 옵션:\n• 구글 지도 — maps.google.com 에서 검색 → '공유' → '지도 임베드' → iframe 코드 복사·붙여넣기 (키 불필요)\n• 카카오 지도 — map.kakao.com 에서 검색 → '공유' → '지도 임베드'\n• 네이버 클라우드 플랫폼 Maps API — Client ID 발급 후 JS 코드 사용 (무료 60,000건/일)\n네이버 지도 자체는 보안상 iframe 임베드를 차단합니다.",
		'section'     => 'moondental_section_flocation_map',
		'type'        => 'textarea',
	) );

	/* ── Home Doctor section content ───────────────────────────── */
	$doctor_fields = array(
		'doctor_role' => array( 'label' => '직책',                 'type' => 'text',     'default' => '대표 병원장 · 한아의료재단 이사장' ),
		'doctor_lead' => array( 'label' => '한 줄 진료 철학',         'type' => 'textarea', 'default' => '1995년부터 천안에서, 한 분의 환자를 가족처럼 오래 보아왔습니다. 진료실 밖에서도 지역사회와 함께 가는 치과를 꿈꿉니다.' ),
		'doctor_bio'  => array( 'label' => '약력 (줄바꿈으로 구분)', 'type' => 'textarea', 'default' => "한아임플란트 보철연구소장\n단국대학교 치과대학 총동창회 학술이사\n대한 구강악안면 임플란트 학회 이사\n충남 치과의사회 학술이사\n단국치대 겸임교수\n이화여대 의과대학 외래교수" ),
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

	/* ── 의료진 사진 크기 조정 (개인별 zoom + translateY) ──────────── */
	$wp_customize->add_section( 'moondental_section_team_photos', array(
		'title'       => '의료진 — 사진 크기·위치',
		'panel'       => 'moondental_panel',
		'description' => '의료진 페이지(/의료진/)의 사진별 머리 크기와 위치를 조정합니다. ' .
		                 'Zoom: 1.00 = 원본 그대로 · 1.50 = 50% 확대 (머리 크기). ' .
		                 'TranslateY: 음수 = 위로 올림 · 양수 = 아래로 내림 (% 단위, 머리 최상단 위치).',
		'priority'    => 40,
	) );

	$team_zoom_defaults = moondental_team_zoom_defaults();
	foreach ( $team_zoom_defaults as $slug => $info ) {
		/* Zoom */
		$setting_id_z = 'moondental_team_zoom_' . $slug;
		$wp_customize->add_setting( $setting_id_z, array(
			'default'           => $info['default'],
			'sanitize_callback' => 'moondental_sanitize_zoom',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id_z, array(
			'label'       => $info['name'] . ' · 머리 크기 (Zoom)',
			'description' => '기본값 ' . number_format( $info['default'], 2 ) . ' · 범위 0.80~2.50',
			'section'     => 'moondental_section_team_photos',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0.80,
				'max'  => 2.50,
				'step' => 0.05,
			),
		) );

		/* TranslateY */
		$setting_id_t = 'moondental_team_ty_' . $slug;
		$wp_customize->add_setting( $setting_id_t, array(
			'default'           => $info['ty'],
			'sanitize_callback' => 'moondental_sanitize_translatey',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id_t, array(
			'label'       => $info['name'] . ' · 머리 위치 (TranslateY %)',
			'description' => '기본값 ' . (int) $info['ty'] . '% · 범위 -40 ~ 40 · 음수=위로 올림',
			'section'     => 'moondental_section_team_photos',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => -40,
				'max'  => 40,
				'step' => 1,
			),
		) );
	}
}
add_action( 'customize_register', 'moondental_customize_register' );

/**
 * 의료진별 사진 줌 기본값. Customizer 미설정 시 이 값이 적용됨.
 * 슬러그(sanitize_title 한 이름) → [name, role, default]
 */
function moondental_team_zoom_defaults() {
	/*
	 * 머리 최상단을 카드 상단에서 약 1cm(=카드 높이의 ~10%) 하단에 위치시키도록 조정.
	 * zoom은 원본 유지(1.00) — 사진 비율 자연스럽게.
	 * translateY는 양수=머리를 아래로, 음수=위로 이동 (단위: %).
	 *
	 * 추정한 원본 머리 최상단 위치(T_orig%):
	 *   문은수 10 / 이승주 5 / 이수연 7 / 권혜진 6 / 문지현 8 /
	 *   이창률 5 / 이영일 8 / 김세일 12 / 정석형 8
	 *
	 * translateY = 10 - T_orig
	 */
	return array(
		sanitize_title( '문은수' ) => array( 'name' => '문은수', 'role' => '대표 병원장',          'default' => 1.00, 'ty' =>  0 ),
		sanitize_title( '이승주' ) => array( 'name' => '이승주', 'role' => '9F 종합진료센터',      'default' => 1.00, 'ty' =>  5 ),
		sanitize_title( '이수연' ) => array( 'name' => '이수연', 'role' => '9F 종합진료센터',      'default' => 1.00, 'ty' =>  3 ),
		sanitize_title( '권혜진' ) => array( 'name' => '권혜진', 'role' => '9F 종합진료센터',      'default' => 1.00, 'ty' =>  4 ),
		sanitize_title( '문지현' ) => array( 'name' => '문지현', 'role' => '10F 임플란트센터',     'default' => 1.00, 'ty' =>  2 ),
		sanitize_title( '이창률' ) => array( 'name' => '이창률', 'role' => '10F 임플란트센터',     'default' => 1.00, 'ty' =>  5 ),
		sanitize_title( '이영일' ) => array( 'name' => '이영일', 'role' => '11F 교정과',           'default' => 1.00, 'ty' =>  2 ),
		sanitize_title( '김세일' ) => array( 'name' => '김세일', 'role' => '11F 종합진료센터',     'default' => 1.00, 'ty' => -2 ),
		sanitize_title( '정석형' ) => array( 'name' => '정석형', 'role' => '11F 종합진료센터',     'default' => 1.00, 'ty' =>  2 ),
	);
}

/**
 * 사진 줌 값을 0.80~2.50 범위로 정제.
 */
function moondental_sanitize_zoom( $value ) {
	$v = (float) $value;
	if ( $v < 0.80 ) return 0.80;
	if ( $v > 2.50 ) return 2.50;
	return round( $v, 2 );
}

/**
 * 머리 위치(translateY) -40 ~ 40% 범위로 정제.
 */
function moondental_sanitize_translatey( $value ) {
	$v = (float) $value;
	if ( $v < -40 ) return -40;
	if ( $v >  40 ) return  40;
	return round( $v, 1 );
}

/**
 * 의료진 이름으로 현재 translateY 값(%)을 반환.
 */
function moondental_get_doctor_ty( $name, $fallback = 0 ) {
	$slug    = sanitize_title( $name );
	$default = $fallback;
	$map     = moondental_team_zoom_defaults();
	if ( isset( $map[ $slug ] ) ) {
		$default = $map[ $slug ]['ty'];
	}
	$v = get_theme_mod( 'moondental_team_ty_' . $slug, $default );
	return moondental_sanitize_translatey( $v );
}

/**
 * 의료진 이름으로 현재 줌 값을 반환 — Customizer 값 우선, 없으면 default.
 */
function moondental_get_doctor_zoom( $name, $fallback = 1.00 ) {
	$slug    = sanitize_title( $name );
	$default = $fallback;
	$map     = moondental_team_zoom_defaults();
	if ( isset( $map[ $slug ] ) ) {
		$default = $map[ $slug ]['default'];
	}
	$v = get_theme_mod( 'moondental_team_zoom_' . $slug, $default );
	return moondental_sanitize_zoom( $v );
}


/* ============================================================
 * 4b. 기본 페이지 자동 생성 도구 (관리자)
 * ========================================================== */

/**
 * 사이트 운영에 필요한 페이지 정의. 슬러그·제목·템플릿·정렬을 한 곳에서 관리.
 */
function moondental_default_pages() {
	// 슬러그는 사용자가 만든 한글 슬러그와 일치 — 이미 있는 페이지는 건드리지 않음.
	return array(
		array( 'slug' => '홈',              'title' => '홈',           'template' => '',                                 'order' => 0,  'parent' => '' ),
		array( 'slug' => '병원소개',         'title' => '병원소개',      'template' => '',                                 'order' => 1,  'parent' => '' ),
		array( 'slug' => '의료진',           'title' => '의료진',        'template' => 'page-templates/page-doctors.php',  'order' => 1,  'parent' => '병원소개' ),
		array( 'slug' => '역사',             'title' => '역사',          'template' => 'page-templates/page-history.php',  'order' => 2,  'parent' => '병원소개' ),
		array( 'slug' => '기술력-시설',       'title' => '기술력/시설',   'template' => 'page-templates/page-wide.php',     'order' => 3,  'parent' => '병원소개' ),
		array( 'slug' => '임상-케이스',       'title' => '임상 케이스',    'template' => 'page-templates/page-wide.php',     'order' => 4,  'parent' => '병원소개' ),
		array( 'slug' => '진료항목',         'title' => '진료항목',      'template' => '',                                 'order' => 2,  'parent' => '' ),
		array( 'slug' => '임플란트-센터',     'title' => '임플란트 센터',  'template' => 'page-templates/page-service.php',  'order' => 1,  'parent' => '진료항목' ),
		array( 'slug' => '투명교정-센터',     'title' => '투명교정 센터',  'template' => 'page-templates/page-service.php',  'order' => 2,  'parent' => '진료항목' ),
		array( 'slug' => '자연치아-살리기',   'title' => '자연치아 살리기','template' => 'page-templates/page-service.php',  'order' => 3,  'parent' => '진료항목' ),
		array( 'slug' => '턱관절-클리닉',     'title' => '턱관절 클리닉',  'template' => 'page-templates/page-service.php',  'order' => 4,  'parent' => '진료항목' ),
		array( 'slug' => '사랑니-발치',       'title' => '사랑니 발치',   'template' => 'page-templates/page-service.php',  'order' => 5,  'parent' => '진료항목' ),
		array( 'slug' => '심미치료',         'title' => '심미치료',      'template' => 'page-templates/page-service.php',  'order' => 6,  'parent' => '진료항목' ),
		array( 'slug' => '소식',             'title' => '소식',         'template' => '',                                 'order' => 3,  'parent' => '' ),
		array( 'slug' => '오시는-길',         'title' => '오시는 길',     'template' => 'page-templates/page-location.php', 'order' => 4,  'parent' => '' ),
		array( 'slug' => '상담예약',         'title' => '상담 예약',     'template' => 'page-templates/page-reservation.php', 'order' => 5, 'parent' => '' ),
	);
}

/**
 * 헤더 CTA가 가리키는 /상담예약/ 페이지 자동 생성 — 테마 활성화 시 1회.
 *  이미 있으면 건드리지 않음.
 */
function moondental_ensure_reservation_page() {
	if ( get_page_by_path( '상담예약' ) ) return;
	wp_insert_post( array(
		'post_title'    => '상담 예약',
		'post_name'     => '상담예약',
		'post_status'   => 'publish',
		'post_type'     => 'page',
		'post_content'  => '',
		'page_template' => 'page-templates/page-reservation.php',
	) );
}
add_action( 'after_switch_theme', 'moondental_ensure_reservation_page' );
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_reservation_page_v1' ) === '1' ) return;
	moondental_ensure_reservation_page();
	update_option( 'moondental_reservation_page_v1', '1' );
} );

/**
 * 페이지 일괄 생성. 이미 있는 슬러그는 건드리지 않음.
 * Admin Toolbar에서 "기본 페이지 만들기" 누르면 실행.
 */
function moondental_create_default_pages() {
	if ( ! current_user_can( 'manage_options' ) ) return new WP_Error( 'forbidden', '권한이 없습니다.' );

	$created = array();
	$pages   = moondental_default_pages();

	// 1차: parent 없는 페이지 먼저
	foreach ( $pages as $page ) {
		if ( $page['parent'] ) continue;
		if ( get_page_by_path( $page['slug'] ) ) continue;
		$id = wp_insert_post( array(
			'post_title'    => $page['title'],
			'post_name'     => $page['slug'],
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_content'  => '',
			'menu_order'    => $page['order'],
			'page_template' => $page['template'] ?: 'default',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$created[] = $page['slug'];
		}
	}

	// 2차: parent 있는 페이지
	foreach ( $pages as $page ) {
		if ( ! $page['parent'] ) continue;
		if ( get_page_by_path( $page['parent'] . '/' . $page['slug'] ) ) continue;
		if ( get_page_by_path( $page['slug'] ) ) continue;
		$parent_obj = get_page_by_path( $page['parent'] );
		$id = wp_insert_post( array(
			'post_title'    => $page['title'],
			'post_name'     => $page['slug'],
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_content'  => '',
			'post_parent'   => $parent_obj ? $parent_obj->ID : 0,
			'menu_order'    => $page['order'],
			'page_template' => $page['template'] ?: 'default',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$created[] = $page['slug'];
		}
	}

	// 정적 홈 + 글 페이지 자동 설정 (해당 슬러그 페이지가 있을 때만)
	$home    = get_page_by_path( '홈' );
	$notices = get_page_by_path( '소식' );
	if ( $home ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
	}
	if ( $notices ) {
		update_option( 'page_for_posts', $notices->ID );
	}

	return $created;
}

/**
 * 관리자 페이지에 "기본 페이지 만들기" 액션 등록.
 * URL: /wp-admin/admin.php?page=moondental-tools
 */
function moondental_admin_menu() {
	add_theme_page(
		'문치과 사이트 도구',
		'문치과 사이트 도구',
		'manage_options',
		'moondental-tools',
		'moondental_admin_tools_page'
	);
}
add_action( 'admin_menu', 'moondental_admin_menu' );

function moondental_admin_tools_page() {
	$ran = false; $created = array();
	if ( isset( $_POST['moondental_seed_pages'] ) && check_admin_referer( 'moondental_seed' ) ) {
		$created = moondental_create_default_pages();
		$ran     = true;
	}
	$sync_ran = false; $sync_result = null;
	if ( isset( $_POST['moondental_sync_naver'] ) && check_admin_referer( 'moondental_sync' ) ) {
		$sync_result = moondental_naver_import_all( 20 );
		$sync_ran    = true;
	}
	?>
	<div class="wrap">
		<h1>문치과 사이트 도구</h1>

		<?php if ( $ran ) : ?>
			<div class="notice notice-success"><p>
				<?php if ( empty( $created ) || is_wp_error( $created ) ) : ?>
					이미 모든 기본 페이지가 존재합니다.
				<?php else : ?>
					다음 페이지를 새로 만들었습니다: <strong><?php echo esc_html( implode( ', ', $created ) ); ?></strong>
				<?php endif; ?>
			</p></div>
		<?php endif; ?>

		<div class="card" style="max-width:720px; padding:24px;">
			<h2>기본 페이지 일괄 생성</h2>
			<p>홈 · 병원소개 · 의료진 · 진료안내(5개 하위) · 오시는 길 · 공지사항 페이지를 한 번에 만듭니다.
			   각 페이지는 적절한 템플릿이 자동 할당되며, 본문은 비어 있을 때 테마에 정의된 기본 콘텐츠가 자동으로 출력됩니다.
			   <strong>이미 있는 페이지는 건드리지 않습니다.</strong></p>
			<form method="post">
				<?php wp_nonce_field( 'moondental_seed' ); ?>
				<p>
					<button type="submit" name="moondental_seed_pages" class="button button-primary button-large">기본 페이지 만들기</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width:720px; padding:24px; margin-top:16px;">
			<h2>네이버 블로그 글 가져오기</h2>
			<p>네이버 블로그(<code><?php echo esc_html( moondental_get_info( 'blog_url' ) ); ?></code>)의
			   <strong>최신 20개 글의 본문 전체</strong>를 사이트로 가져옵니다.
			   가져온 글은 <code>/소식/</code>에 카드로 나열되고, 카드 클릭 시 사이트 내 페이지로 열립니다.
			   같은 글은 다시 가져오지 않으므로 안전하게 여러 번 눌러도 됩니다.</p>
			<p style="font-size:13px; color:#666;">처음 실행 시 1~2분 정도 걸릴 수 있습니다 (글 사이 0.4초 간격, 네이버 부담 방지).</p>

			<?php if ( $sync_ran && $sync_result ) : ?>
				<div class="notice notice-info" style="margin:12px 0; padding:12px;">
					<p>
						새로 가져온 글: <strong><?php echo count( $sync_result['created'] ); ?>개</strong> ·
						이미 있어서 건너뜀: <strong><?php echo (int) $sync_result['skipped']; ?>개</strong>
						<?php if ( ! empty( $sync_result['errors'] ) ) : ?>
							· 오류: <strong><?php echo count( $sync_result['errors'] ); ?>건</strong>
						<?php endif; ?>
					</p>
					<?php if ( ! empty( $sync_result['errors'] ) ) : ?>
						<details style="margin-top:8px;"><summary>오류 상세</summary>
							<ul style="margin:8px 0 0 16px;">
								<?php foreach ( $sync_result['errors'] as $e ) : ?>
									<li style="font-size:12px; color:#a00;"><?php echo esc_html( $e ); ?></li>
								<?php endforeach; ?>
							</ul>
						</details>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'moondental_sync' ); ?>
				<p><button type="submit" name="moondental_sync_naver" class="button button-primary button-large">네이버 블로그 동기화</button></p>
			</form>
		</div>

		<div class="card" style="max-width:720px; padding:24px; margin-top:16px;">
			<h2>의료진 사진 업로드 위치</h2>
			<p>의료진 사진은 워드프레스 미디어가 아니라 테마 폴더에 직접 저장합니다:</p>
			<p><code style="display:block; padding:12px; background:#f0f0f0;"><?php echo esc_html( str_replace( ABSPATH, '', MOONDENTAL_DIR ) ); ?>/assets/images/doctors/</code></p>
			<p>이 폴더에 <code>doctor-01.jpg ~ doctor-09.jpg</code> 9장을 저장하면 의료진 그리드에 자동 표시됩니다.
			   파일명·매칭 규칙은 같은 폴더의 <code>README.md</code> 참고.</p>
		</div>

		<div class="card" style="max-width:720px; padding:24px; margin-top:16px;">
			<h2>병원 정보 / 콘텐츠 편집</h2>
			<p><a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="button">사용자 정의하기 열기</a> →
			   "문치과병원 설정" 패널에서 전화·주소·진료시간·SNS·홈 메인 카피·원장 약력 등 모두 편집 가능합니다.</p>
		</div>
	</div>
	<?php
}


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
 * 6b. Template Router — 슬러그 기반 자동 템플릿 할당
 * ========================================================== */
/**
 * 사용자가 페이지를 만들 때 "페이지 속성 → 템플릿"을 수동으로 선택하지 않아도
 * 슬러그가 일치하면 알맞은 템플릿을 자동으로 사용한다.
 *
 * 슬러그가 WP에 URL-encoded 형태로 저장된 경우와 raw UTF-8 한글 모두 처리.
 */
function moondental_template_router( $template ) {
	// 소식 페이지가 page_for_posts(글 페이지)로 설정된 경우 → page-news.php
	if ( is_home() && ! is_front_page() ) {
		$pfp_id = (int) get_option( 'page_for_posts' );
		$slug   = $pfp_id ? urldecode( (string) get_post_field( 'post_name', $pfp_id ) ) : '';
		if ( in_array( $slug, array( '소식', 'news' ), true ) ) {
			$custom = locate_template( 'page-templates/page-news.php' );
			if ( $custom ) return $custom;
		}
	}
	if ( ! is_page() ) return $template;

	// 사용자가 명시적으로 템플릿을 지정했으면 그 결정을 존중
	$current = get_page_template_slug( get_queried_object_id() );
	if ( $current && $current !== 'default' ) return $template;

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	$slug = urldecode( $slug ); // 한글 정규화

	$map = array(
		'의료진'         => 'page-templates/page-doctors.php',
		'오시는-길'       => 'page-templates/page-location.php',
		'오시는길'        => 'page-templates/page-location.php',
		'location'        => 'page-templates/page-location.php',
		'소식'           => 'page-templates/page-news.php',
		'역사'           => 'page-templates/page-history.php',
		'상담예약'       => 'page-templates/page-reservation.php',
		'reservation'    => 'page-templates/page-reservation.php',
		'faq'            => 'page-templates/page-faq.php',
		'자주-묻는-질문' => 'page-templates/page-faq.php',
		'임플란트-센터'   => 'page-templates/page-service.php',
		'소아치과'       => 'page-templates/page-service.php',
		'투명교정-센터'   => 'page-templates/page-service.php',
		'자연치아-살리기' => 'page-templates/page-service.php',
		'턱관절-클리닉'   => 'page-templates/page-service.php',
		'사랑니-발치'     => 'page-templates/page-service.php',
		'심미치료'       => 'page-templates/page-service.php',
		'비용-안내'      => 'page-templates/page-pricing.php',
		'비급여-진료비'   => 'page-templates/page-pricing.php',
		'진료비안내'      => 'page-templates/page-pricing.php',
		'pricing'        => 'page-templates/page-pricing.php',
		'기술력-시설'     => 'page-templates/page-facility.php',
		'기술력시설'      => 'page-templates/page-facility.php',
		'facility'       => 'page-templates/page-facility.php',
	);

	if ( isset( $map[ $slug ] ) ) {
		$custom = locate_template( $map[ $slug ] );
		if ( $custom ) return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'moondental_template_router', 99 );


/* ============================================================
 * 6b. 의료진 상세 페이지 — /의료진/{slug}/ 자동 라우팅
 * ========================================================== */
function moondental_doctor_rewrite_rules() {
	/* /의료진/{ascii-slug}/ → query var doctor_slug.
	 *  ASCII만 매치 — 한글 URL은 인코딩 변환 문제가 있어 영문 슬러그 사용.
	 *  Korean URL pattern은 fallback으로 함께 등록.
	 */
	add_rewrite_rule( '^의료진/([a-z0-9_-]+)/?$',                     'index.php?doctor_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^doctors/([a-z0-9_-]+)/?$',                    'index.php?doctor_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^%EC%9D%98%EB%A3%8C%EC%A7%84/([^/]+)/?$',      'index.php?doctor_slug=$matches[1]', 'top' );
}
add_action( 'init', 'moondental_doctor_rewrite_rules' );

function moondental_doctor_query_vars( $vars ) {
	$vars[] = 'doctor_slug';
	return $vars;
}
add_filter( 'query_vars', 'moondental_doctor_query_vars' );

/* 의료진 상세 페이지 라우팅 — doctor_slug query var가 있으면 단일 템플릿으로 */
function moondental_doctor_single_router( $template ) {
	$doctor_slug = get_query_var( 'doctor_slug' );
	if ( ! $doctor_slug ) return $template;
	$custom = locate_template( 'page-templates/page-doctor-single.php' );
	if ( $custom ) {
		// 404 방지 — 강제로 200 응답
		status_header( 200 );
		global $wp_query;
		$wp_query->is_404 = false;
		return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'moondental_doctor_single_router', 98 );

/**
 * URL 직접 가로채기 — rewrite rule이 동작하지 않는 환경(공지사항 fallback 등) 대비.
 *  template_redirect 최우선 hook으로 /의료진/{slug}/ 패턴 검출 시
 *  page-doctor-single.php 템플릿 직접 로드.
 */
function moondental_doctor_intercept() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	if ( ! $path ) return;
	$path = trim( urldecode( $path ), '/' );

	// 패턴: 의료진/{slug}/ 또는 doctors/{slug}/
	if ( ! preg_match( '#^(?:의료진|doctors)/([^/]+)/?$#u', $path, $m ) ) return;

	$slug = trim( $m[1] );
	if ( ! $slug ) return;

	$doctor = function_exists( 'moondental_get_doctor_by_slug' )
		? moondental_get_doctor_by_slug( $slug )
		: null;
	if ( ! $doctor ) return; // 의료진 없으면 WP 기본 라우팅에 맡김

	// 의료진 발견 — page-doctor-single.php 로 강제 라우팅
	set_query_var( 'doctor_slug', $slug );
	global $wp_query;
	$wp_query->is_404      = false;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_home     = false;
	$wp_query->is_archive  = false;
	status_header( 200 );
	moondental_intercept_setup_post( '의료진' );

	$tpl = locate_template( 'page-templates/page-doctor-single.php' );
	if ( $tpl ) {
		include $tpl;
		exit;
	}
}
add_action( 'template_redirect', 'moondental_doctor_intercept', 1 );

/**
 * 강제 라우팅된 페이지에서 body_class()·get_the_title() 등이 정상 동작하도록
 * 베이스 페이지를 queried object로 설정. PHP 경고 방지.
 *
 * @param string $base_slug 베이스 페이지 슬러그 (예: '의료진', '오시는-길', '기술력-시설')
 */
function moondental_intercept_setup_post( $base_slug ) {
	$base = get_page_by_path( $base_slug );
	if ( ! $base ) return;
	global $wp_query, $post;
	$post = $base;
	$wp_query->post              = $base;
	$wp_query->posts             = array( $base );
	$wp_query->queried_object    = $base;
	$wp_query->queried_object_id = $base->ID;
	$wp_query->post_count        = 1;
	$wp_query->current_post      = -1;
	$wp_query->found_posts       = 1;
}

/**
 * /강점/{slug}/ URL 가로채기 — 강점 상세 페이지 라우팅.
 *  template_redirect 우선순위 1로 패턴 매칭 → page-strength.php 강제 로드.
 */
function moondental_strength_intercept() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	if ( ! $path ) return;
	$path = trim( urldecode( $path ), '/' );

	if ( ! preg_match( '#^(?:강점|strengths?)/([a-z0-9_-]+)/?$#u', $path, $m ) ) return;

	$slug = trim( $m[1] );
	if ( ! $slug ) return;

	$data = function_exists( 'moondental_get_strength_by_slug' )
		? moondental_get_strength_by_slug( $slug )
		: null;
	if ( ! $data ) return; // 알 수 없는 슬러그면 WP 기본 라우팅에 맡김

	set_query_var( 'strength_slug', $slug );
	global $wp_query;
	$wp_query->is_404      = false;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_home     = false;
	$wp_query->is_archive  = false;
	status_header( 200 );
	moondental_intercept_setup_post( '기술력-시설' );

	$tpl = locate_template( 'page-templates/page-strength.php' );
	if ( $tpl ) {
		include $tpl;
		exit;
	}
}
add_action( 'template_redirect', 'moondental_strength_intercept', 1 );

/**
 * /오시는-길/{region-slug}/ URL 가로채기 — 지역별 랜딩 페이지 라우팅.
 *  template_redirect 우선순위 1로 패턴 매칭 → page-region.php 강제 로드.
 *  지역 SEO를 위한 28개 지역 페이지의 URL 라우팅 핵심.
 */
function moondental_region_intercept() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	if ( ! $path ) return;
	$path = trim( urldecode( $path ), '/' );

	// 패턴: 오시는-길/{ascii-slug}/ 또는 오시는길/{ascii-slug}/ 또는 location/{ascii-slug}/
	if ( ! preg_match( '#^(?:오시는-길|오시는길|location)/([a-z0-9_-]+)/?$#u', $path, $m ) ) return;

	$slug = trim( $m[1] );
	if ( ! $slug ) return;

	$region = function_exists( 'moondental_get_region_by_slug' )
		? moondental_get_region_by_slug( $slug )
		: null;
	if ( ! $region ) return;

	set_query_var( 'region_slug', $slug );
	global $wp_query;
	$wp_query->is_404      = false;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_home     = false;
	$wp_query->is_archive  = false;
	status_header( 200 );
	moondental_intercept_setup_post( '오시는-길' );

	$tpl = locate_template( 'page-templates/page-region.php' );
	if ( $tpl ) {
		include $tpl;
		exit;
	}
}
add_action( 'template_redirect', 'moondental_region_intercept', 1 );

/* 테마 활성화 시 rewrite rules flush — 새 rule 패턴 반영 위해 버전 키 증가 */
function moondental_flush_rewrites_once() {
	if ( get_option( 'moondental_rewrite_flushed_v5' ) !== '1' ) {
		moondental_doctor_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( 'moondental_rewrite_flushed_v5', '1' );
	}
}
add_action( 'init', 'moondental_flush_rewrites_once', 99 );

/**
 * 의료진 이름 → 영문 ASCII URL 슬러그 매핑.
 *  한글 URL은 인코딩 호환성 문제로 영문 슬러그 사용.
 */
function moondental_doctor_name_to_slug( $name ) {
	$map = array(
		'문은수' => 'munes',
		'이승주' => 'leesj',
		'이수연' => 'leesu',
		'권혜진' => 'kwon',
		'문지현' => 'munji',
		'이창률' => 'leech',
		'이영일' => 'leeyi',
		'김세일' => 'kimsi',
		'정석형' => 'jeong',
	);
	return $map[ $name ] ?? sanitize_title( $name );
}

/**
 * 영문 슬러그(또는 한글 이름) → 의료진 데이터 반환.
 */
function moondental_get_doctor_by_slug( $slug ) {
	$slug = urldecode( $slug );
	$groups = function_exists( 'moondental_get_team_with_customizer' )
		? moondental_get_team_with_customizer()
		: moondental_get_team();
	foreach ( $groups as $group ) {
		foreach ( $group['members'] as $m ) {
			// 직접 이름 매치 (한글 fallback)
			if ( $m['name'] === $slug ) return $m;
			// ASCII 슬러그 매치
			if ( moondental_doctor_name_to_slug( $m['name'] ) === $slug ) return $m;
		}
	}
	return null;
}


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
		array( 'label' => '병원안내', 'url' => home_url( '/about/' ) ),
		array( 'label' => '진료안내', 'url' => home_url( '/services/' ) ),
		array( 'label' => '의료진',   'url' => home_url( '/doctors/' ) ),
		array( 'label' => '시설',     'url' => home_url( '/facility/' ) ),
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
	// 슬러그는 사용자가 만든 실제 페이지의 한글 슬러그(URL의 한글 부분)와 일치.
	// 페이지가 없으면 service grid 카드 클릭 시 fallback URL로 이동.
	return array(
		array(
			'slug'  => '임플란트-센터',
			'title' => '천안 임플란트',
			'icon'  => '🦷',
			'desc'  => '천안 만남로 10F 임플란트센터 — 단일·다수·전악 임플란트까지, CBCT 디지털 가이드 수술과 보철 전문의 협진.',
		),
		array(
			'slug'  => '투명교정-센터',
			'title' => '천안 투명교정',
			'icon'  => '✨',
			'desc'  => '천안 만남로 11F 교정과 — 슈어스마일(SureSmile) AI 투명교정 + 치과교정과 전문의·인정의 라이프스타일 맞춤 진료.',
		),
		array(
			'slug'  => '자연치아-살리기',
			'title' => '천안 자연치아 살리기',
			'icon'  => '🌿',
			'desc'  => '천안 신경치료·재근관치료·치주치료. 보존과 전문의의 정밀 진료 — 발치보다 보존을 먼저 고민합니다.',
		),
		array(
			'slug'  => '턱관절-클리닉',
			'title' => '천안 턱관절 클리닉',
			'icon'  => '🔄',
			'desc'  => '천안 턱관절 통증·소리·개구장애 — 대한턱관절교합학회 이사진의 전문 진료. 보존적 치료 우선.',
		),
		array(
			'slug'  => '사랑니-발치',
			'title' => '천안 사랑니 발치',
			'icon'  => '🦴',
			'desc'  => '천안 매복 사랑니까지 — CBCT 3D 정밀 진단으로 구강악안면외과 전문 의료진이 안전하게 발치합니다.',
		),
		array(
			'slug'  => '심미치료',
			'title' => '천안 라미네이트·미백',
			'icon'  => '💎',
			'desc'  => '천안 라미네이트·치아미백·올세라믹 — 최소 삭제 보존적 접근으로 자연스러운 미소를 디자인합니다.',
		),
		array(
			'slug'  => '소아치과',
			'title' => '천안 소아치과',
			'icon'  => '🧒',
			'desc'  => '천안 어린이 첫 치과 경험부터 정기 검진·예방·1차 교정까지. 평생 구강 건강의 시작.',
		),
	);
}


/* ============================================================
 * 10. Helper: 의료진 팀 (10명 — 이사장 + 원장 + 교수)
 * ========================================================== */
/**
 * 의료진 데이터. 추후 Custom Post Type "doctor"로 이관 가능.
 *
 * @return array[] role 그룹별 [ 'group' => label, 'members' => [name, …] ]
 */
function moondental_get_team() {
	/*
	 * 사진은 assets/images/doctors/doctor-01.jpg ~ doctor-09.jpg.
	 * 약력은 moondental.co.kr/hospital/p_4_*.php 의 의료진 소개 이미지에서 OCR.
	 *
	 * bio 는 줄 단위 배열 (각 항목이 한 줄의 학력/경력).
	 * philosophy 는 진료 철학 한 문장 (선택).
	 */
	return array(
		/* ─── 대표 병원장 ─── */
		array(
			'group'   => '대표 병원장',
			'members' => array(
				array(
					'name'       => '문은수',
					'role'       => '대표 병원장',
					'photo'      => 'doctor-04.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 0,
					'philosophy' => '환자를 가족처럼 생각하는 마음, 그것이 문치과의 진료 철학입니다.',
					'bio'        => array(
						'한아의료재단 이사장',
						'한아임플란트 보철연구소장',
						'단국대학교 치과대학 총동창회 학술이사',
						'대한 구강악안면 임플란트 학회 이사',
						'충남 치과의사회 학술이사',
						'단국치대 겸임교수',
						'이화여대 의과대학 외래교수',
					),
				),
			),
		),

		/* ─── 9F 종합진료센터 ─── */
		array(
			'group'   => '9F 종합진료센터',
			'members' => array(
				array(
					'name'       => '이승주',
					'role'       => '원장 · 9F 종합진료센터',
					'photo'      => 'doctor-07.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 5,
					'philosophy' => '최선을 다하여 환자를 가족처럼 생각하며 진료에 임하겠습니다.',
					'bio'        => array(
						'단국대학교치과대학 치의학과 졸업',
						'대한치주과학회 정회원',
						'대한보철학회 정회원',
						'한아임플란트보철연구소 연구위원',
					),
				),
				array(
					'name'       => '이수연',
					'role'       => '원장 · 9F 종합진료센터',
					'photo'      => 'doctor-08.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 3,
					'philosophy' => '진실된 마음으로 환자분들과 함께하는 의료서비스를 제공하겠습니다.',
					'bio'        => array(
						'치과 보철과 전문의 · 통합치의학 전문의',
						'조선대학교 치의학전문대학원 석사',
						'조선대학교 치의학전문대학원 박사',
						'조선대학교 치과병원 인턴',
						'조선대학교 치과병원 레지던트',
						'Harvard School advanced education in periodontics and prosthodontics 수료',
					),
				),
				array(
					'name'       => '권혜진',
					'role'       => '원장 · 9F 종합진료센터',
					'photo'      => 'doctor-02.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 4,
					'philosophy' => '기본에 충실하되 새로운 변화에 맞춰가며, 환자분을 가족처럼 생각하는 따뜻한 마음으로 진료에 임하겠습니다.',
					'bio'        => array(
						'보건복지부 인증 보존과 전문의',
						'단국대학교 치과대학 졸업',
						'단국대학교 치과대학 보존과 석사',
						'단국대학교 치과대학 보존과 레지던트 수료',
						'대한 근관학회 정회원',
						'대한 보존학회 정회원',
					),
				),
			),
		),

		/* ─── 10F 임플란트센터 ─── */
		array(
			'group'   => '10F 임플란트센터',
			'members' => array(
				array(
					'name'       => '문지현',
					'role'       => '원장 · 10F 임플란트센터',
					'photo'      => 'doctor-05.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 2,
					'philosophy' => '구강건강 증진을 통해 환자분들의 삶이 회복되는 과정을 함께 하고 싶습니다. 최선을 다해 진료하겠습니다.',
					'bio'        => array(
						'서울대학교 치의학대학원 졸업',
						'단국대학교 구강악안면외과 박사 과정',
						'포항공과대학교 신소재공학과 석사 졸업',
						'포항공과대학교 신소재공학과 학사 졸업',
						'대한턱관절교합학회 이사',
						'대한구강악안면임플란트학회 정회원',
						'미국 UCSF 치과대학 교정과 임상연수',
						'미국 UCSF 치과대학 투명교정 과정 수료',
						'미국 UPENN 치과대학 근관치료학 연수',
						'턱관절장애교육연구회 고급과정 수료',
						'대한치과이식임플란트학회아카데미 수료',
						'대한여성치과의사회 미래여성인재상 수상',
						'한국금속재료학회 우수논문상 수상',
						'한아임플란트보철연구소 연구위원',
					),
				),
				array(
					'name'       => '이창률',
					'role'       => '원장 · 10F 임플란트센터',
					'photo'      => 'doctor-01.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 5,
					'philosophy' => 'For a lifelong smile. 환자 한 분 한 분을 가족처럼 생각하며, 밝고 편안한 웃음을 위한 진료에 최선을 다하겠습니다.',
					'bio'        => array(
						'미국 UCLA 생화학 학사 졸업',
						'미국 UCLA 구강생물학 석사 졸업',
						'미국 UCLA 구강암센터 연구원',
						'미국 UCSF 치과대학 졸업',
						'미국 UCSF 치과대학 교정과 임상연수',
						'미국 UCSF 치과대학 투명교정 과정 수료',
						'미국 UCSF 치과대학 레이저 과정 수료',
						'미국 & 한국 치과의사 면허 취득',
						'미국 캘리포니아 치과의사 협회(CDA) 정회원',
						'미국 치과의사 협회(ADA) 정회원',
						'대한턱관절교합학회 이사',
						'턱관절장애교육연구회 고급과정 수료',
						'대한국제치과의사회 학술위원',
						'한아임플란트보철연구소 연구위원',
					),
				),
			),
		),

		/* ─── 11F 교정과 / 종합진료센터 ─── */
		array(
			'group'   => '11F 교정과 · 종합진료센터',
			'members' => array(
				array(
					'name'       => '이영일',
					'role'       => '원장 · 11F 교정과',
					'photo'      => 'doctor-09.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 2,
					'philosophy' => '환자를 가족처럼 생각하는 마음, 그것이 문치과의 진료 철학입니다.',
					'bio'        => array(
						'단국대학교 치과대학 졸업',
						'단국대 치과대학원 치의학과 석사',
						'단국대 치과대학원 치의학과 박사',
						'단국대 치과부속병원 교정과 인턴, 레지던트 수료',
						'치과교정과 전문의',
						'치과교정과 인정의',
						'대한치과교정학회 정회원',
					),
				),
				array(
					'name'       => '김세일',
					'role'       => '원장 · 11F 종합진료센터',
					'photo'      => 'doctor-03.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => -2,
					'philosophy' => '건강한 치아는 건강한 일상의 시작입니다. 세밀한 진단과 진료로 환자분들의 건강한 하루를 책임지겠습니다.',
					'bio'        => array(
						'단국대학교 치과대학 졸업',
						'이화여자대학교 교정과 석사 수료',
						'대한 치과 교정학회 정회원',
					),
				),
				array(
					'name'       => '정석형',
					'role'       => '원장 · 11F 종합진료센터',
					'photo'      => 'doctor-06.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 2,
					'philosophy' => '저희 문치과를 방문하는 모든 분들이 밝고 건강한 웃음의 주인이 되시길 바라며 항상 최선을 다하겠습니다.',
					'bio'        => array(
						'단국대학교 치과대학 치주과 석사',
						'서울위생치과병원 치주과 인턴, 레지던트 수료',
						'대한치주과학회 인정의',
						'한아임플란트보철연구소 연구위원',
						'대한치주과학회 정회원',
					),
				),
			),
		),
	);
}

/**
 * 의료진 그룹 데이터에 Customizer 값(개인별 줌)을 덧입혀 반환.
 *
 * page-doctors.php 등 모든 호출처는 그대로 moondental_get_team() 만 사용해도
 * Customizer 값이 자동 반영되도록, 이 wrapper를 통해 후처리한다.
 */
function moondental_get_team_with_customizer() {
	$groups = moondental_get_team();
	if ( ! function_exists( 'get_theme_mod' ) ) return $groups;

	/* 의료진 이름 → Customizer key 매핑 */
	$name_to_key = array(
		'문은수' => 'munes',
		'이승주' => 'leesj',
		'이수연' => 'leesu',
		'권혜진' => 'kwon',
		'문지현' => 'munji',
		'이창률' => 'leech',
		'이영일' => 'leeyi',
		'김세일' => 'kimsi',
		'정석형' => 'jeong',
	);

	/* 그룹명 Customizer override (4개) */
	if ( function_exists( 'md_content' ) ) {
		foreach ( $groups as $gi => $group ) {
			$grp_idx = $gi + 1; // 1~4
			$grp_override = md_content( "doctor_group_{$grp_idx}", '' );
			if ( $grp_override ) {
				$groups[ $gi ]['group'] = $grp_override;
			}
		}
	}

	foreach ( $groups as $gi => $group ) {
		foreach ( $group['members'] as $mi => $m ) {
			/* 사진 zoom·translateY */
			$z_fallback = isset( $m['photo_zoom'] ) ? (float) $m['photo_zoom'] : 1.00;
			$t_fallback = isset( $m['photo_ty'] )   ? (float) $m['photo_ty']   : 0.00;
			$groups[ $gi ]['members'][ $mi ]['photo_zoom'] = moondental_get_doctor_zoom( $m['name'], $z_fallback );
			$groups[ $gi ]['members'][ $mi ]['photo_ty']   = moondental_get_doctor_ty(   $m['name'], $t_fallback );

			/* 철학 + 약력 + 직책 Customizer override */
			$key = $name_to_key[ $m['name'] ] ?? null;
			if ( $key && function_exists( 'md_content' ) ) {
				$phil_override = md_content( "doctor_{$key}_philosophy", '' );
				if ( $phil_override ) {
					$groups[ $gi ]['members'][ $mi ]['philosophy'] = $phil_override;
				}
				$bio_override = md_content( "doctor_{$key}_bio", '' );
				if ( $bio_override ) {
					$bio_lines = array();
					foreach ( preg_split( "/\r\n|\r|\n/", $bio_override ) as $line ) {
						$line = trim( $line );
						if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
						$bio_lines[] = $line;
					}
					if ( $bio_lines ) {
						$groups[ $gi ]['members'][ $mi ]['bio'] = $bio_lines;
					}
				}
				$role_override = md_content( "doctor_{$key}_role", '' );
				if ( $role_override ) {
					$groups[ $gi ]['members'][ $mi ]['role'] = $role_override;
				}
			}
		}
	}
	return $groups;
}

/**
 * moondental_get_team() 호출 결과를 Customizer 값으로 덮어쓰는 필터 wrapper.
 * 외부에서 add_filter( 'moondental_team', 'moondental_get_team_with_customizer' ) 형태로 후킹 가능.
 */
add_filter( 'moondental_team_data', 'moondental_apply_team_zoom_customizer', 10, 1 );
function moondental_apply_team_zoom_customizer( $groups ) {
	foreach ( $groups as $gi => $group ) {
		foreach ( $group['members'] as $mi => $m ) {
			$fallback = isset( $m['photo_zoom'] ) ? (float) $m['photo_zoom'] : 1.00;
			$groups[ $gi ]['members'][ $mi ]['photo_zoom'] = moondental_get_doctor_zoom( $m['name'], $fallback );
		}
	}
	return $groups;
}

/**
 * 역사 페이지 사진 파일 URL 반환. 파일 없으면 false.
 *
 * @param string $filename 예: 'history-2025-08-mongol.jpg'
 * @return string|false
 */
function moondental_history_photo_url( $filename ) {
	if ( ! $filename ) return false;
	$path = MOONDENTAL_DIR . '/assets/images/history/' . $filename;
	return file_exists( $path ) ? MOONDENTAL_URI . '/assets/images/history/' . $filename : false;
}


/**
 * 의료진 사진 파일 URL 반환. 지정 확장자가 없으면 jpg/png/jpeg/webp 순으로 시도.
 *
 * @param string $filename 예: 'doctor-01.png'
 * @return string|false   URL 또는 false (실제 파일이 없을 때)
 */
function moondental_doctor_photo_url( $filename ) {
	if ( ! $filename ) return false;
	$base = pathinfo( $filename, PATHINFO_FILENAME ); // 확장자 제외
	$exts = array( pathinfo( $filename, PATHINFO_EXTENSION ), 'png', 'jpg', 'jpeg', 'webp' );
	$exts = array_filter( array_unique( $exts ) );
	foreach ( $exts as $ext ) {
		$try_file = $base . '.' . $ext;
		$path     = MOONDENTAL_DIR . '/assets/images/doctors/' . $try_file;
		if ( file_exists( $path ) ) {
			return MOONDENTAL_URI . '/assets/images/doctors/' . $try_file;
		}
	}
	return false;
}

/**
 * 네이버 블로그 RSS 피드를 가져와 정리된 배열로 반환.
 * 1시간 transient 캐싱 (네이버에 부담 X + 새 글 1시간 이내 반영).
 *
 * @param int  $limit     반환할 최대 개수 (기본 20)
 * @param bool $no_cache  true면 캐시 무시하고 새로 fetch
 * @return array[] [ ['title','link','date','category','thumb','excerpt','tags'] , … ]
 */
function moondental_fetch_naver_blog( $limit = 20, $no_cache = false ) {
	$info    = moondental_get_info();
	$blog_url = isset( $info['blog_url'] ) ? $info['blog_url'] : '';
	if ( ! $blog_url ) return array();

	// blog.naver.com/<id> → rss.blog.naver.com/<id>.xml 로 변환
	if ( preg_match( '#blog\.naver\.com/([A-Za-z0-9_-]+)#', $blog_url, $m ) ) {
		$rss_url = 'https://rss.blog.naver.com/' . $m[1] . '.xml';
	} else {
		return array();
	}

	$cache_key = 'moondental_naver_rss_' . md5( $rss_url );
	if ( ! $no_cache ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) return array_slice( $cached, 0, $limit );
	}

	$resp = wp_remote_get( $rss_url, array(
		'timeout'    => 8,
		'user-agent' => 'Mozilla/5.0 MoonDental/1.0',
	) );
	if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
		return array();
	}
	$xml_text = wp_remote_retrieve_body( $resp );

	// CDATA 살리며 파싱
	libxml_use_internal_errors( true );
	$xml = simplexml_load_string( $xml_text, 'SimpleXMLElement', LIBXML_NOCDATA );
	libxml_clear_errors();
	if ( ! $xml || ! isset( $xml->channel->item ) ) return array();

	$items = array();
	foreach ( $xml->channel->item as $it ) {
		$desc_html = (string) $it->description;
		// 첫 <img>를 썸네일로
		$thumb = '';
		if ( preg_match( '#<img[^>]+src=["\']([^"\']+)["\']#i', $desc_html, $im ) ) {
			$thumb = $im[1];
		}
		// 본문에서 HTML 제거, 200자 요약
		$plain = trim( html_entity_decode( wp_strip_all_tags( $desc_html ), ENT_QUOTES, 'UTF-8' ) );
		$plain = preg_replace( '/\s+/u', ' ', $plain );
		$excerpt = mb_strimwidth( $plain, 0, 160, '…', 'UTF-8' );

		// 카테고리 "문치과병원[치아 이야기]" → "치아 이야기"
		$cat_raw = (string) $it->category;
		$category = '';
		if ( preg_match( '#\[([^\]]+)\]#u', $cat_raw, $cm ) ) {
			$category = trim( $cm[1] );
		} elseif ( $cat_raw ) {
			$category = trim( $cat_raw );
		}

		// 링크에서 RSS 추적 파라미터 제거
		$link = (string) $it->link;
		$link = preg_replace( '#[?&]fromRss=true[^&]*#', '', $link );
		$link = preg_replace( '#[?&]trackingCode=rss#', '', $link );
		$link = preg_replace( '#\?$#', '', $link );

		// 태그 (콤마 구분 문자열 → 배열)
		$tag_raw = (string) $it->tag;
		$tags    = $tag_raw ? array_map( 'trim', explode( ',', $tag_raw ) ) : array();

		$items[] = array(
			'title'    => trim( (string) $it->title ),
			'link'     => $link,
			'date'     => strtotime( (string) $it->pubDate ),
			'category' => $category,
			'thumb'    => $thumb,
			'excerpt'  => $excerpt,
			'tags'     => array_slice( $tags, 0, 6 ),
		);
	}

	set_transient( $cache_key, $items, HOUR_IN_SECONDS );
	return array_slice( $items, 0, $limit );
}


/**
 * Flatten team for simple grid rendering.
 */
function moondental_get_team_flat() {
	$flat = array();
	foreach ( moondental_get_team() as $group ) {
		foreach ( $group['members'] as $m ) {
			$m['group'] = $group['group'];
			$flat[]     = $m;
		}
	}
	return $flat;
}
