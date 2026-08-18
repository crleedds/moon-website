<?php
/**
 * Moon Dental — 종합 안내서 (Guides) 라우팅·헬퍼
 *
 * v3.44.175 · 임플란트·투명교정·라미네이트 3개 가이드.
 * URL: /가이드/임플란트/, /가이드/투명교정/, /가이드/라미네이트/
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * URL 슬러그 → 데이터 파일 매핑.
 */
function md_guide_slug_map() {
	return array(
		'임플란트' => 'implant',
		'implant'  => 'implant',
		'투명교정' => 'invisalign',
		'인비절라인' => 'invisalign',
		'invisalign' => 'invisalign',
		'라미네이트' => 'laminate',
		'laminate'   => 'laminate',
	);
}

/**
 * 슬러그로부터 가이드 데이터 로드.
 *
 * @param string $slug URL segment (한글 또는 영문).
 * @return array|null
 */
function md_guide_load( $slug ) {
	$map = md_guide_slug_map();
	$key = isset( $map[ $slug ] ) ? $map[ $slug ] : null;
	if ( ! $key ) return null;
	$file = get_stylesheet_directory() . '/inc/guides/data-' . $key . '.php';
	if ( ! file_exists( $file ) ) return null;
	return require $file;
}

/**
 * FAQ 배열 → schema.org JSON-LD + HTML 마크업.
 *
 * @param array $items 각 항목: [ 질문, 답변(HTML) ].
 */
function md_guide_faq_html( $items ) {
	$html = '';
	$json = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array(),
	);
	foreach ( $items as $it ) {
		$q = isset( $it[0] ) ? $it[0] : '';
		$a = isset( $it[1] ) ? $it[1] : '';
		if ( ! $q || ! $a ) continue;
		$html .= '<details class="md-guide-faq__item" itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">';
		$html .= '<summary itemprop="name">' . esc_html( $q ) . '</summary>';
		$html .= '<div class="md-guide-faq__answer" itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">';
		$html .= '<div itemprop="text">' . wp_kses_post( $a ) . '</div>';
		$html .= '</div></details>';

		$json['mainEntity'][] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $q ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $a ),
			),
		);
	}
	$html .= '<script type="application/ld+json">' . wp_json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
	return $html;
}

/**
 * 3개 가이드 카드 요약 (홈·다른 가이드 하단 링크 등에서 재사용).
 *
 * @return array
 */
function md_guide_index() {
	static $cache = null;
	if ( $cache !== null ) return $cache;
	$cache = array();
	foreach ( array( 'implant', 'invisalign', 'laminate' ) as $key ) {
		$data = md_guide_load( $key );
		if ( ! $data ) continue;
		$path_map = array(
			'implant'    => '/가이드/임플란트/',
			'invisalign' => '/가이드/투명교정/',
			'laminate'   => '/가이드/라미네이트/',
		);
		$cache[] = array(
			'slug'     => $key,
			'code'     => $data['code']    ?? '',
			'icon'     => $data['icon']    ?? '',
			'title'    => $data['title']   ?? '',
			'subtitle' => $data['subtitle'] ?? '',
			'summary'  => $data['summary'] ?? '',
			'tags'     => $data['tags']    ?? array(),
			'href'     => $path_map[ $key ] ?? '/',
			'reading'  => $data['reading'] ?? '',
		);
	}
	return $cache;
}

/**
 * Rewrite rule 등록 · /가이드/{slug}/ 를 캐치.
 */
add_action( 'init', function () {
	add_rewrite_rule(
		'^가이드/([^/]+)/?$',
		'index.php?md_guide=$matches[1]',
		'top'
	);
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'md_guide';
	return $vars;
} );

/**
 * template_include · 가이드 요청 시 템플릿 로드.
 */
add_filter( 'template_include', function ( $template ) {
	$slug = get_query_var( 'md_guide' );
	if ( ! $slug ) return $template;

	// 한글 슬러그가 URL 디코딩된 상태로 전달됨
	$slug = rawurldecode( $slug );
	$data = md_guide_load( $slug );
	if ( ! $data ) return $template;

	$GLOBALS['md_guide_data'] = $data;

	// 404 방지: WP 에 200 응답이라고 알려주기
	global $wp_query;
	$wp_query->is_404 = false;
	$wp_query->is_page = true;
	status_header( 200 );

	$custom = get_stylesheet_directory() . '/page-templates/page-guide.php';
	if ( file_exists( $custom ) ) return $custom;
	return $template;
} );

/**
 * 문서 타이틀 · Yoast/기본 title 대응.
 */
add_filter( 'pre_get_document_title', function ( $title ) {
	if ( empty( $GLOBALS['md_guide_data'] ) ) return $title;
	$d = $GLOBALS['md_guide_data'];
	$hospital = function_exists( 'moondental_get_info' ) ? moondental_get_info( 'name_short' ) : '문치과병원';
	return ( $d['title'] ?? '가이드' ) . ' | ' . $hospital;
}, 20 );

/**
 * body_class 추가 · 스타일 스코프.
 */
add_filter( 'body_class', function ( $classes ) {
	if ( ! empty( $GLOBALS['md_guide_data'] ) ) {
		$classes[] = 'md-guide-page';
		$classes[] = 'md-guide-page--' . ( $GLOBALS['md_guide_data']['slug'] ?? '' );
	}
	return $classes;
} );

/**
 * 활성화 시 rewrite flush · MOONDENTAL_VERSION 옵션과 함께 관리.
 */
add_action( 'init', function () {
	$key = 'md_guide_rewrites_v';
	$now = defined( 'MOONDENTAL_VERSION' ) ? MOONDENTAL_VERSION : '1';
	if ( get_option( $key ) !== $now ) {
		flush_rewrite_rules( false );
		update_option( $key, $now );
	}
}, 20 );
