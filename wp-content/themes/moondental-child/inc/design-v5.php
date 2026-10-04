<?php
/**
 * v5.0 · 원장 지시 · 사이트 디자인 v5 (시안 3 기준)
 *
 *  · 켜기: 옵션 md_design_v5 = 'on'  (미리보기: 주소 뒤 ?v5=1 · 끄고 보기: ?v5=0)
 *  · style.css 를 그대로 두고, 색(코랄 → 브론즈 · 세이지 → 웜그레이) · 모서리 · 그림자만 바꾼
 *    assets/css/style-v5.css 를 style.css 가 바뀔 때마다 자동으로 다시 만든다.
 *  · 그 위에 assets/css/v5.css (글꼴 · 머리 · 푸터 · 버튼 · 홈 화면)를 얹는다.
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function md_v5() {
	static $on = null;
	if ( null !== $on ) return $on;
	if ( isset( $_GET['v5'] ) ) return $on = ( '0' !== (string) $_GET['v5'] );
	return $on = ( 'on' === get_option( 'md_design_v5', '' ) );
}

/* 색 · 모서리 · 그림자 바꾸기 표 (style.css → style-v5.css) */
function md_v5_css_map() {
	return array(
		// 코랄 → 브론즈
		'#D88062' => '#8C6B4F', '#d88062' => '#8C6B4F',
		'#E8A48A' => '#B39478', '#e8a48a' => '#B39478',
		'#B86347' => '#6F543D', '#b86347' => '#6F543D',
		'#E37B5C' => '#8C6B4F', '#e37b5c' => '#8C6B4F',
		'#C9714F' => '#8C6B4F', '#c9714f' => '#8C6B4F',
		'216, 128, 98' => '140, 107, 79', '216,128,98' => '140,107,79',
		'227, 123, 92' => '140, 107, 79', '227,123,92' => '140,107,79',
		'%23D88062' => '%238C6B4F', '%23d88062' => '%238C6B4F',
		// 다크 모드의 밝은 코랄 · 피치 → 밝은 브론즈
		'#F2A878' => '#C9A27F', '#f2a878' => '#C9A27F', '%23f2a878' => '%23C9A27F', '%23F2A878' => '%23C9A27F',
		'#F4B195' => '#C9A27F', '#f4b195' => '#C9A27F',
		'242, 168, 120' => '201, 162, 127', '242,168,120' => '201,162,127',
		'244, 177, 149' => '201, 162, 127', '244,177,149' => '201,162,127',
		'232, 164, 138' => '179, 148, 120', '232,164,138' => '179,148,120',
		'182, 206, 183' => '207, 196, 184', '182,206,183' => '207,196,184',
		'92, 139, 130'  => '110, 98, 88',   '92,139,130'  => '110,98,88',
		// 세이지 → 웜그레이
		'#8FAE92' => '#9A8B7C', '#8fae92' => '#9A8B7C',
		'#B6CEB7' => '#CFC4B8', '#b6ceb7' => '#CFC4B8',
		'#6B8F72' => '#6E6258', '#6b8f72' => '#6E6258',
		'#7A9E7E' => '#85776A', '#7a9e7e' => '#85776A',
		'#5C8B82' => '#6E6258', '#5c8b82' => '#6E6258',
		'143, 174, 146' => '154, 139, 124', '143,174,146' => '154,139,124',
		// 바탕 · 글자
		'#FFFAF4' => '#F6F3EE', '#fffaf4' => '#F6F3EE',
		'#FBF2E8' => '#FBF9F6', '#fbf2e8' => '#FBF9F6',
		'#F5E8D8' => '#EFE9E1', '#f5e8d8' => '#EFE9E1',
		'#EDDFD0' => '#DDD5CC', '#eddfd0' => '#DDD5CC',
		'#3D2F26' => '#1E1A17', '#3d2f26' => '#1E1A17',
		'#7A6B5F' => '#5F5650', '#7a6b5f' => '#5F5650',
		'#A89685' => '#9A9089', '#a89685' => '#9A9089',
		'61, 47, 38' => '30, 26, 23', '61,47,38' => '30,26,23',
		// 모서리 · 그림자 토큰
		'--radius-sm: 8px'  => '--radius-sm: 2px',
		'--radius-md: 16px' => '--radius-md: 3px',
		'--radius-lg: 24px' => '--radius-lg: 4px',
		'--radius-xl: 32px' => '--radius-xl: 4px',
		'--card-radius: 14px' => '--card-radius: 2px',
	);
}

function md_v5_base_css_path() { return MOONDENTAL_DIR . '/assets/css/style-v5.css'; }

/* style.css 가 더 새로우면 style-v5.css 를 다시 만든다 */
function md_v5_build_base_css() {
	$src = MOONDENTAL_DIR . '/style.css';
	$dst = md_v5_base_css_path();
	if ( ! file_exists( $src ) ) return false;
	if ( file_exists( $dst ) && filemtime( $dst ) >= filemtime( $src ) && filemtime( $dst ) >= filemtime( __FILE__ ) && filesize( $dst ) > 1000 ) return true;
	$css = file_get_contents( $src );
	if ( ! $css ) return false;
	$map = md_v5_css_map();
	$css = str_replace( array_keys( $map ), array_values( $map ), $css );
	// v5.2 · 둥근 모서리(6~40px · 999px 알약) → 거의 각지게 (원형 50% 는 그대로)
	$css = preg_replace_callback( '/border-radius:\s*([0-9.]+)px/', function ( $m ) {
		$v = (float) $m[1];
		if ( $v >= 6 && $v <= 40 ) return 'border-radius: 3px';
		if ( $v >= 400 ) return 'border-radius: 2px';
		return $m[0];
	}, $css );
	$css = str_replace( '--radius-pill: 999px', '--radius-pill: 2px', $css );
	// 상대 경로(이미지 · 글꼴)는 assets/css 기준으로 맞춘다
	$css = preg_replace( '#url\(\s*([\'"]?)(?!data:|https?:|/|\.\./)([^\'")]+)\1\s*\)#', 'url($1../../$2$1)', $css );
	$css = "/* 자동 생성 · style.css → v5 색 · 직접 고치지 마세요 (inc/design-v5.php) */\n" . $css;
	$tmp = $dst . '.tmp';
	if ( false === @file_put_contents( $tmp, $css ) ) return false;
	return @rename( $tmp, $dst );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() || ! md_v5() ) return;
	if ( md_v5_build_base_css() ) {
		wp_dequeue_style( 'moondental-child-style' );
		wp_deregister_style( 'moondental-child-style' );
		wp_enqueue_style( 'moondental-child-style', MOONDENTAL_URI . '/assets/css/style-v5.css', array( 'astra-parent-style', 'pretendard-variable' ), MOONDENTAL_VERSION . '.' . filemtime( md_v5_base_css_path() ) );
	}
	wp_enqueue_style( 'moondental-v5-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Jost:wght@400;500&family=Noto+Serif+KR:wght@300;400;500;600&display=swap', array(), null );
	$p = MOONDENTAL_DIR . '/assets/css/v5.css';
	if ( file_exists( $p ) ) {
		wp_enqueue_style( 'moondental-v5', MOONDENTAL_URI . '/assets/css/v5.css', array( 'moondental-child-style' ), MOONDENTAL_VERSION . '.' . filemtime( $p ) );
	}
	$j = MOONDENTAL_DIR . '/assets/js/v5.js';
	if ( file_exists( $j ) ) {
		wp_enqueue_script( 'moondental-v5', MOONDENTAL_URI . '/assets/js/v5.js', array(), MOONDENTAL_VERSION . '.' . filemtime( $j ), true );
	}
}, 30 );

add_filter( 'body_class', function ( $c ) {
	if ( md_v5() ) $c[] = 'md-v5';
	return $c;
} );

/* 홈 화면 · 의료진 사진 — 얼굴 크기를 맞춘 사진이 있으면 그것을 쓴다 */
function md_v5_doctor_photo( $filename ) {
	/* 사진 칸 값이 「doctor-04」 · 「doctor-04.png」 · 전체 주소 등 여러 모양이라 이름만 뽑아 .jpg 로 찾는다 */
	$stem = pathinfo( (string) parse_url( (string) $filename, PHP_URL_PATH ), PATHINFO_FILENAME );
	$base = $stem ? $stem . '.jpg' : '';
	if ( $base && file_exists( MOONDENTAL_DIR . '/assets/images/doctors/v5/' . $base ) ) {
		return MOONDENTAL_URI . '/assets/images/doctors/v5/' . $base . '?v=' . filemtime( MOONDENTAL_DIR . '/assets/images/doctors/v5/' . $base );
	}
	return function_exists( 'moondental_doctor_photo_url' ) ? moondental_doctor_photo_url( $filename ) : '';
}

/* 홈 화면 · 소식 사진 (큰 크기) 과 세로 사진 여부 */
function md_v5_post_image( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		$tid = get_post_thumbnail_id( $post_id );
		$src = wp_get_attachment_image_src( $tid, 'medium_large' );
		if ( $src ) return array( 'url' => $src[0], 'tall' => ( $src[2] > $src[1] ) );
	}
	$meta = get_post_meta( $post_id, 'moondental_naver_thumb_url', true );
	if ( $meta ) return array( 'url' => $meta, 'tall' => false );
	$content = get_post_field( 'post_content', $post_id );
	if ( $content && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m ) ) {
		return array( 'url' => $m[1], 'tall' => false );
	}
	return null;
}

/* v5.0 · 원장 지시 · 새 디자인을 모두에게 켠다 (한 번) — 되돌리려면 옵션 md_design_v5 를 'off' 로 */
add_action( 'init', function () {
	if ( get_option( 'md_design_v5_init' ) === 'done' ) return;
	update_option( 'md_design_v5', 'on' );
	global $wpdb;
	wp_cache_flush();
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_pcache\_%' OR option_name LIKE '\_transient\_timeout\_md\_pcache\_%'" );
	if ( function_exists( 'wp_cache_clear_cache' ) ) wp_cache_clear_cache();
	update_option( 'md_design_v5_init', 'done' );
}, 7 );

/* v5.1 · 외국어 홈도 새 구성으로 (6개 언어 문구 사전 · languages/md_phrases_*_v5.php) — 한 번 */
add_action( 'init', function () {
	if ( get_option( 'md_design_v5_i18n_init' ) === 'done' ) return;
	update_option( 'md_design_v5_i18n', 'on' );
	update_option( 'md_design_v5_i18n_init', 'done' );
}, 8 );

/* v5.2 · 화면 글자 속 이모지 아이콘 빼기 (✓ ★ 는 남김) — 직원 라운지 · 만족도 조사는 그대로 */
function md_v5_strip_emoji( $html ) {
	$re = '/(?:[\x{1F000}-\x{1FAFF}\x{2600}-\x{2604}\x{2607}-\x{2712}\x{2715}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}\x{20E3}])+\s?/u';
	$parts = preg_split( '#(<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>|<[^>]+>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) ) return $html;
	foreach ( $parts as $i => $p ) {
		if ( $p === '' || $p[0] === '<' ) continue;
		$n = preg_replace( $re, '', $p );
		if ( is_string( $n ) ) $parts[ $i ] = $n;
	}
	return implode( '', $parts );
}
add_action( 'template_redirect', function () {
	if ( is_admin() || ! md_v5() || is_feed() ) return;
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) return;
	if ( function_exists( 'md_sup_is_page' ) && md_sup_is_page() ) return;
	if ( function_exists( 'md_survey_is_public_path' ) && md_survey_is_public_path() ) return;
	ob_start( function ( $html ) {
		if ( ! is_string( $html ) || stripos( $html, '<html' ) === false ) return $html;
		return md_v5_strip_emoji( $html );
	} );
}, 1 );

/* v5.3 · 원장 지시 · 「라미네이트」 → 「비니어(최소삭제 라미네이트)」 (한국어 화면 글자만)
 *   첫 번째 → 비니어(최소삭제 라미네이트), 그 뒤 → 비니어 (머리 · 본문 따로 셈)
 *   「최소침습 / 최소삭제 라미네이트」 · 「라미네이트(Laminate)」 는 한 덩어리로 바꾸고
 *   「무삭제 라미네이트」 는 「무삭제 비니어」 (최소삭제와 뜻이 어긋나지 않게)
 *   검색 설명(meta) · 주소 · 속성값은 그대로 → 「라미네이트」 검색어는 유지 */
function md_v5_veneer_text( $text, &$first ) {
	return preg_replace_callback(
		'/(무삭제\s?)?(최소\s?(?:삭제|침습)\s?(?:·\s?무삭제\s?)?)?라미네이트(?:\s?\((?:[Ll]aminate|LAMINATE)[^)]*\))?/u',
		function ( $m ) use ( &$first ) {
			if ( ! empty( $m[1] ) ) return $m[1] . '비니어';
			if ( $first ) { $first = false; return '비니어(최소삭제 라미네이트)'; }
			return ! empty( $m[2] ) ? '최소삭제 비니어' : '비니어';
		},
		$text
	);
}
function md_v5_veneer( $html ) {
	if ( strpos( $html, '라미네이트' ) === false ) return $html;
	$parts = preg_split( '#(<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>|<[^>]+>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) ) return $html;
	$first_head = true; $first_body = true; $in_body = false;
	foreach ( $parts as $i => $p ) {
		if ( $p === '' ) continue;
		if ( $p[0] === '<' ) { if ( ! $in_body && stripos( $p, '<body' ) === 0 ) $in_body = true; continue; }
		if ( strpos( $p, '라미네이트' ) === false ) continue;
		if ( $in_body ) $parts[ $i ] = md_v5_veneer_text( $p, $first_body );
		else            $parts[ $i ] = md_v5_veneer_text( $p, $first_head );
	}
	return implode( '', $parts );
}
add_action( 'template_redirect', function () {
	if ( is_admin() || ! md_v5() || is_feed() ) return;
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) return;
	if ( function_exists( 'moondental_current_language' ) && 'ko' !== moondental_current_language() ) return;
	if ( function_exists( 'md_sup_is_page' ) && md_sup_is_page() ) return;
	ob_start( function ( $html ) {
		if ( ! is_string( $html ) || stripos( $html, '<html' ) === false ) return $html;
		return md_v5_veneer( $html );
	} );
}, 2 );
