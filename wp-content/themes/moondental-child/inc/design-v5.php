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
	if ( file_exists( $dst ) && filemtime( $dst ) >= filemtime( $src ) && filesize( $dst ) > 1000 ) return true;
	$css = file_get_contents( $src );
	if ( ! $css ) return false;
	$map = md_v5_css_map();
	$css = str_replace( array_keys( $map ), array_values( $map ), $css );
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
	$base = basename( (string) $filename );
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
