<?php
/**
 * v3.94 · 치과 백과사전(md_term) 검색 노출 강화
 *
 *  진단(2026-09-19)
 *   - 구글은 851개 용어를 색인하고 있었지만(site: 검색 확인) 용어 페이지에
 *     meta description 이 없고, <title> 은 '용어 - 한아의료재단 문치과병원' 뿐이라
 *     지역·진료 문맥이 전혀 없었다. 용어별 구조화 데이터도 없었다.
 *   - 일반 용어('법랑질')는 위키·대형 포털과 경쟁하므로 정의 자체로 1위는 어렵다.
 *     현실적인 목표는 ① 증상·상황형 롱테일 검색 ② '천안/아산 + 진료' 검색
 *     ③ 답변 엔진(AI)이 인용할 수 있는 명확한 개체·출처 정보다.
 *
 *  이 파일이 하는 일
 *   1. 용어 페이지 <title> · meta description · robots(max-snippet) 정비
 *   2. 용어별 JSON-LD: MedicalWebPage + DefinedTerm + BreadcrumbList + FAQPage
 *      (publisher 는 조직 스키마 #org 를 참조해 병원 개체와 연결)
 *   3. 분야(카테고리) → 전문센터 페이지 연결 정보 (템플릿과 llms.txt 가 공용)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 백과사전 분야 → 전문센터 연결표 (단일 진실원)
 *   name  : 화면·앵커 텍스트에 쓰는 센터 이름
 *   url   : 실제 페이지 경로 (리다이렉트 없이 바로 열리는 주소)
 *   region: 앵커에 붙일 지역 문구
 */
function moondental_enc_center_map() {
	return array(
		'implant'     => array( 'name' => '임플란트센터',     'url' => '/임플란트-센터/',     'icon' => '🦷', 'treat' => '임플란트·뼈이식·상악동 거상술·임플란트 틀니' ),
		'ortho'       => array( 'name' => '교정센터',         'url' => '/투명교정-센터/',     'icon' => '✨', 'treat' => '슈어스마일 투명교정·브라켓 교정·성장기 교정' ),
		'esthetic'    => array( 'name' => '스마일디자인센터', 'url' => '/스마일디자인센터/', 'icon' => '💎', 'treat' => '라미네이트·치아미백·심미보철·크라운' ),
		'general'     => array( 'name' => '자연치아보존센터', 'url' => '/자연치아-살리기/',   'icon' => '🌿', 'treat' => '충치치료·부분신경치료·신경치료·잇몸치료·덴탈SPA' ),
		'surgery'     => array( 'name' => '구강외과·턱관절 클리닉', 'url' => '/사랑니-발치/', 'icon' => '🩺', 'treat' => '사랑니 발치·턱관절 치료·구강 질환' ),
		'pediatric'   => array( 'name' => '소아치과',         'url' => '/소아치과/',         'icon' => '👶', 'treat' => '어린이 충치·실란트·불소·유치 관리' ),
		'dental-info' => array( 'name' => '비용·보험 안내',   'url' => '/비용-안내/',         'icon' => '🛡️', 'treat' => '진료비·건강보험 적용·상담' ),
	);
}

/** 현재 요청이 백과사전 개별 항목인가 */
function moondental_enc_is_term() {
	return is_singular( 'md_term' );
}

/** 용어의 첫 분야 slug */
function moondental_enc_term_cat( $post_id ) {
	$cats = get_the_terms( $post_id, 'md_term_category' );
	if ( is_wp_error( $cats ) || empty( $cats ) ) return 'general';
	return $cats[0]->slug;
}

/** 용어 설명문 (excerpt 우선 · 없으면 본문 앞부분) */
function moondental_enc_term_desc( $post, $len = 150 ) {
	$ex = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
	if ( $ex === '' ) {
		$ex = trim( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) );
		$ex = preg_replace( '/\s+/u', ' ', $ex );
	}
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $ex ) > $len ) $ex = mb_substr( $ex, 0, $len - 1 ) . '…';
	return $ex;
}

/* ------------------------------------------------------------------
 * 1. <title> · description · robots
 * ---------------------------------------------------------------- */
function moondental_enc_title( $title ) {
	if ( ! moondental_enc_is_term() ) return $title;
	$t = get_the_title();
	return $t . ' | 치과 백과사전 · 천안·아산 문치과병원';
}
add_filter( 'wpseo_title', 'moondental_enc_title', 30 );
add_filter( 'wpseo_opengraph_title', 'moondental_enc_title', 30 );
add_filter( 'wpseo_twitter_title', 'moondental_enc_title', 30 );
add_filter( 'document_title_parts', function ( $parts ) {
	if ( defined( 'WPSEO_VERSION' ) || ! moondental_enc_is_term() ) return $parts;
	return array( 'title' => moondental_enc_title( '' ) );
}, 30 );

function moondental_enc_metadesc( $desc ) {
	if ( ! moondental_enc_is_term() ) return $desc;
	$post = get_post();
	if ( ! $post ) return $desc;
	$cat  = moondental_enc_term_cat( $post->ID );
	$map  = moondental_enc_center_map();
	$tail = isset( $map[ $cat ] ) ? ' 천안·아산 문치과병원 ' . $map[ $cat ]['name'] . ' 안내.' : ' 천안·아산 문치과병원 치과 백과사전.';
	return moondental_enc_term_desc( $post, 130 ) . $tail;
}
add_filter( 'wpseo_metadesc', 'moondental_enc_metadesc', 30 );
add_filter( 'wpseo_opengraph_desc', 'moondental_enc_metadesc', 30 );
add_filter( 'wpseo_twitter_description', 'moondental_enc_metadesc', 30 );
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || ! moondental_enc_is_term() ) return;
	echo '<meta name="description" content="' . esc_attr( moondental_enc_metadesc( '' ) ) . '" />' . "\n";
}, 2 );

/* 답변 엔진·AI 오버뷰가 본문을 길게 인용할 수 있도록 스니펫 제한 해제 */
add_filter( 'wpseo_robots', function ( $robots ) {
	if ( ! moondental_enc_is_term() ) return $robots;
	if ( strpos( $robots, 'noindex' ) !== false ) return $robots;
	return 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
}, 30 );
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || ! moondental_enc_is_term() ) return;
	echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />' . "\n";
}, 2 );

/* ------------------------------------------------------------------
 * 2. 용어별 JSON-LD
 * ---------------------------------------------------------------- */
function moondental_enc_jsonld() {
	if ( is_admin() || ! moondental_enc_is_term() ) return;
	$post = get_post();
	if ( ! $post ) return;

	$site  = home_url( '/' );
	$url   = get_permalink( $post );
	$title = get_the_title( $post );
	$desc  = moondental_enc_term_desc( $post, 300 );
	$cat   = moondental_enc_term_cat( $post->ID );
	$map   = moondental_enc_center_map();
	$cats  = get_the_terms( $post->ID, 'md_term_category' );
	$cat_name = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0]->name : '';
	$enc_url = get_post_type_archive_link( 'md_term' ) ?: $site . '치과사전/';

	$graph = array();

	// (a) 웹페이지
	$page = array(
		'@type'         => array( 'MedicalWebPage', 'WebPage' ),
		'@id'           => $url . '#webpage',
		'url'           => $url,
		'name'          => $title . ' | 치과 백과사전 · 천안·아산 문치과병원',
		'headline'      => $title,
		'description'   => $desc,
		'inLanguage'    => 'ko-KR',
		'datePublished' => get_the_date( 'c', $post ),
		'dateModified'  => get_the_modified_date( 'c', $post ),
		'isPartOf'      => array( '@id' => $site . '#website' ),
		'about'         => array( '@id' => $url . '#term' ),
		'publisher'     => array( '@id' => $site . '#org' ),
		'author'        => array( '@id' => $site . '#org' ),
		'audience'      => array( '@type' => 'PeopleAudience', 'audienceType' => 'Patient' ),
		'medicalAudience' => array( '@type' => 'MedicalAudience', 'audienceType' => 'Patient' ),
		'breadcrumb'    => array( '@id' => $url . '#breadcrumb' ),
		'mainEntity'    => array( '@id' => $url . '#term' ),
	);
	if ( $cat_name ) $page['keywords'] = $cat_name . ', 치과 백과사전, 천안 치과, 아산 치과';
	if ( isset( $map[ $cat ] ) ) {
		$page['relatedLink'] = home_url( $map[ $cat ]['url'] );
	}
	$graph[] = $page;

	// (b) 용어 개체
	$graph[] = array(
		'@type'            => 'DefinedTerm',
		'@id'              => $url . '#term',
		'name'             => $title,
		'description'      => $desc,
		'url'              => $url,
		'inDefinedTermSet' => array(
			'@type' => 'DefinedTermSet',
			'@id'   => $enc_url . '#set',
			'name'  => '치과 백과사전 · 문치과병원',
			'url'   => $enc_url,
		),
	);

	// (c) 빵부스러기
	$crumbs = array(
		array( '@type' => 'ListItem', 'position' => 1, 'name' => '홈', 'item' => $site ),
		array( '@type' => 'ListItem', 'position' => 2, 'name' => '치과 백과사전', 'item' => $enc_url ),
	);
	if ( $cat_name && ! is_wp_error( $cats ) ) {
		$cat_link = get_term_link( $cats[0] );
		if ( ! is_wp_error( $cat_link ) ) {
			$crumbs[] = array( '@type' => 'ListItem', 'position' => 3, 'name' => $cat_name, 'item' => $cat_link );
		}
	}
	$crumbs[] = array( '@type' => 'ListItem', 'position' => count( $crumbs ) + 1, 'name' => $title, 'item' => $url );
	$graph[] = array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $url . '#breadcrumb',
		'itemListElement' => $crumbs,
	);

	// (d) FAQ — 템플릿이 본문 h3 에서 만든 것과 같은 질문·답변
	if ( function_exists( 'moondental_md_term_generated_faq' ) ) {
		$faqs = moondental_md_term_generated_faq( $title, $post->post_content );
		if ( $faqs ) {
			$items = array();
			foreach ( array_slice( $faqs, 0, 8 ) as $f ) {
				if ( empty( $f[0] ) || empty( $f[1] ) ) continue;
				$items[] = array(
					'@type'          => 'Question',
					'name'           => $f[0],
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ),
				);
			}
			if ( $items ) {
				$graph[] = array(
					'@type'      => 'FAQPage',
					'@id'        => $url . '#faq',
					'mainEntity' => $items,
				);
			}
		}
	}

	// (e) 웹사이트 개체 (다른 스키마의 #website 참조 대상)
	$graph[] = array(
		'@type'     => 'WebSite',
		'@id'       => $site . '#website',
		'url'       => $site,
		'name'      => '한아의료재단 문치과병원',
		'publisher' => array( '@id' => $site . '#org' ),
		'inLanguage' => 'ko-KR',
	);

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_enc_jsonld', 54 );

/* ------------------------------------------------------------------
 * 3. 백과사전 목록·분야 페이지 title/description
 * ---------------------------------------------------------------- */
add_filter( 'wpseo_title', function ( $title ) {
	if ( is_post_type_archive( 'md_term' ) ) return '치과 백과사전 · 치과 용어 850+ 해설 | 천안·아산 문치과병원';
	if ( is_tax( 'md_term_category' ) ) {
		$t = get_queried_object();
		return ( $t ? $t->name . ' 용어 ' : '' ) . '| 치과 백과사전 · 천안·아산 문치과병원';
	}
	return $title;
}, 30 );
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( is_post_type_archive( 'md_term' ) ) return '충치·신경치료·임플란트·교정·잇몸·턱관절·소아치과·보험까지, 환자 눈높이로 정리한 치과 용어 백과사전. 천안·아산 문치과병원이 운영합니다.';
	if ( is_tax( 'md_term_category' ) ) {
		$t = get_queried_object();
		return ( $t && $t->description ? $t->description . ' ' : '' ) . '천안·아산 문치과병원 치과 백과사전.';
	}
	return $desc;
}, 30 );
