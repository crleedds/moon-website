<?php
/**
 * SEO Boost — 키워드 기반 title / meta description 강화
 *  Yoast SEO가 활성이면 wpseo_title / wpseo_metadesc 필터로 오버라이드
 *  Yoast가 없으면 document_title_parts 필터로 대체
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 페이지 슬러그 → 최적화된 title / description 맵
 *  - title 50~60자 · description 140~160자 권장
 *  - '천안' · '문치과병원' · 서비스 키워드 앞배치
 */
function moondental_seo_page_map() {
	return array(
		// 홈 (front page)
		'_home' => array(
			'title' => '천안·아산 치과 문치과병원 · 임플란트·매복 사랑니·충치·교정 30여년',
			'desc'  => '천안·아산 문치과병원 30여년 전통. 임플란트·매복 사랑니 잘 뽑는 치과·충치·신경치료·잇몸치료·라미네이트·소아치과·턱관절·교정 진료과 협진. 카카오톡·네이버 24시간 예약, 041-563-2875.',
		),
		// 진료 페이지
		'임플란트-센터' => array(
			'title' => '천안·아산 임플란트 · 네비게이션 임플란트 정밀 식립 - 문치과병원 임플란트센터',
			'desc'  => '천안·아산 임플란트 문치과병원. 3D CT 진단·네비게이션 가이드로 정확·안전·빠른 회복. 임플란트 상담 041-563-2875 · 카카오톡·네이버 예약.',
		),
		'투명교정-센터' => array(
			'title' => '천안·아산 투명교정 · 슈어스마일 · 브라켓 교정 - 문치과병원 교정센터',
			'desc'  => '천안·아산 치아교정 문치과병원 교정센터. 슈어스마일 투명교정 중부권 센터 · 브라켓 교정 · 정밀 진단 후 맞춤 계획. 교정 상담 041-563-2875.',
		),
		'슈어스마일-투명교정' => array(
			'title' => '천안·아산 슈어스마일 투명교정 · 0.1mm 정밀 중부권 센터 - 문치과병원',
			'desc'  => '슈어스마일 중부권 센터 · 0.1mm 정밀도 · PrimeScan 구강스캐너 · 3D 시뮬레이션. 성인·직장인 투명교정 문치과병원. 상담 041-563-2875.',
		),
		'브라켓-치아교정' => array(
			'title' => '천안·아산 브라켓 치아교정 · 설측·세라믹·자가결찰 - 문치과병원 교정센터',
			'desc'  => '천안·아산 치아교정 문치과병원. 브라켓·설측·세라믹·자가결찰·소아·부분·양악 교정. 정밀 진단 후 맞춤 계획. 교정 상담 041-563-2875.',
		),
		'자연치아-살리기' => array(
			'title' => '자연치아 살리기 · 신경치료 · 치주치료 · 재접합 - 천안·아산 문치과병원',
			'desc'  => '천안·아산 문치과병원 자연치아 살리기 프로그램. 정밀 신경치료·치주치료·치아 재접합·미세 현미경 진료. 발치 전 마지막 상담 041-563-2875.',
		),
		'턱관절-클리닉' => array(
			'title' => '천안·아산 턱관절 · 이갈이 · 안면 통증 클리닉 - 문치과병원',
			'desc'  => '천안·아산 턱관절 클리닉 문치과병원. 턱관절 통증·이갈이·안면 근막통·스플린트 치료. 방사선 검사와 근전도 진단으로 정밀 치료. 상담 041-563-2875.',
		),
		'사랑니-발치' => array(
			'title' => '천안·아산 매복 사랑니 잘 뽑는 치과 · 어려운 사랑니 전문 - 문치과병원',
			'desc'  => '천안·아산 매복 사랑니 잘 뽑는 치과 문치과병원. 완전 매복·수평 매복·신경 근접 등 어려운 사랑니 CBCT 3D 정밀 진단·구강악안면외과 안전 발치. 진정 마취 가능. 상담 041-563-2875.',
		),
		'심미치료' => array(
			'title' => '천안·아산 라미네이트 · 미백 · 심미치료 - 문치과병원 스마일 디자인 센터 (10F)',
			'desc'  => '천안·아산 심미치료 문치과병원 스마일 디자인 센터(10F). 라미네이트·전문가 미백·세라믹 크라운·심미 보철 · 자연스러운 미소 디자인. 심미 상담 041-563-2875.',
		),
		'예방클리닉' => array(
			'title' => '천안·아산 치과 예방 · 정기검진 · 스케일링 · 실란트 - 문치과병원',
			'desc'  => '천안·아산 문치과병원 예방클리닉. 정기 검진·스케일링·불소도포·홈케어 교육·소아 실란트로 충치·잇몸 질환 예방. 검진 예약 041-563-2875.',
		),
		'스마일디자인센터' => array(
			'title' => '천안·아산 스마일 디자인 · 라미네이트·미백·치아성형 - 문치과병원',
			'desc'  => '천안·아산 스마일 디자인 문치과병원. 얼굴·입술·잇몸 조화 기반 자연스러운 미소 설계. 라미네이트·미백·심미 보철. 상담 041-563-2875.',
		),
		// 병원 소개
		'의료진' => array(
			'title' => '문치과병원 의료진 · 원장 · 진료과 소개 - 천안·아산 치과',
			'desc'  => '천안·아산 문치과병원 의료진 소개. 원장 · 보존·보철·교정·구강외과·소아 등 진료과 협진 시스템. 환자별 맞춤 진료 팀 문치과병원.',
		),
		'역사' => array(
			'title' => '문치과병원 30여년 역사 · 천안 만남로 진료 - 한아의료재단',
			'desc'  => '1995년 개원, 천안 만남로에서 30여년간 한자리 진료. 한아의료재단 문치과병원 걸어온 길과 지역 사회 진료 역사.',
		),
		'기술력-시설' => array(
			'title' => '문치과병원 시설·기술력 · CT·구강스캐너·수술실 - 천안·아산 치과',
			'desc'  => '천안·아산 문치과병원 시설 소개. 3D CT · PrimeScan 구강스캐너 · 무통 마취 · 감염 관리 · 수술실 · 다국어 응대. 400평 규모 대형 병원.',
		),
		'임상-케이스' => array(
			'title' => '문치과병원 임상 케이스 · Before After · 실제 치료 사진',
			'desc'  => '천안·아산 문치과병원 실제 치료 케이스. 임플란트·투명교정·라미네이트·자연치아 살리기 Before/After 사진과 치료 계획.',
		),
		'상시채용' => array(
			'title' => '문치과병원 상시채용 · 치과의사·위생사·행정 - 천안·아산 치과',
			'desc'  => '천안·아산 문치과병원 상시 채용 안내. 치과의사·위생사·간호조무사·행정직 모집. 근무 조건·복리후생·지원 방법 안내.',
		),
		// 상단 네비게이션 페이지
		'오시는-길' => array(
			'title' => '문치과병원 오시는 길 · 천안 만남로 문타워 · 주차·대중교통 - 천안·아산 치과',
			'desc'  => '천안·아산 문치과병원 위치·오시는 길. 만남로 52 문타워. 지하 주차장·천안IC 15분·천안아산역 10분·시내버스 노선 안내. 예약 041-563-2875.',
		),
		'상담예약' => array(
			'title' => '문치과병원 상담 예약 · 카카오톡·네이버·전화 예약 - 천안·아산 치과',
			'desc'  => '천안·아산 문치과병원 상담 예약 페이지. 카카오톡 채널·네이버 예약·직통 전화 041-563-2875 · 다국어 응대. 편한 시간·희망 진료 선택.',
		),
		'비용-안내' => array(
			'title' => '문치과병원 진료 비용 안내 · 임플란트·교정·라미네이트 가격 - 천안·아산',
			'desc'  => '천안·아산 문치과병원 비급여 진료비 안내. 임플란트·투명교정·라미네이트·크라운·심미치료 표준 가격표. 정확한 비용은 상담 후 확정.',
		),
		'faq' => array(
			'title' => '문치과병원 자주 묻는 질문 (FAQ) · 진료·비용·예약 - 천안·아산 치과',
			'desc'  => '천안·아산 문치과병원 자주 묻는 질문. 진료 시간·비용·주차·다국어·응급 진료·예약 방법 등 환자분들이 자주 문의하시는 내용 정리.',
		),
		'치과 백과사전' => array(
			'title' => '치과 백과사전 · 치과 용어·치료·질환 총정리 - 문치과병원',
			'desc'  => '치과 백과사전 · 문치과병원. 임플란트·교정·신경치료·치주·심미 등 치과 용어 · 치료 방법 · 질환 · 예방까지 환자용 종합 사전.',
		),
	);
}

/**
 * 현재 페이지의 SEO 키 (슬러그 or 특수 키) 반환
 */
function moondental_seo_current_key() {
	if ( is_front_page() || is_home() ) return '_home';
	// v3.44.76 · 지역 페이지 · region_slug 우선 처리
	$region_slug = get_query_var( 'region_slug' );
	if ( $region_slug ) return '_region:' . $region_slug;
	if ( is_page() ) {
		$slug_raw = get_post_field( 'post_name', get_queried_object_id() );
		if ( ! $slug_raw ) return '';
		$slug = urldecode( (string) $slug_raw );
		return $slug;
	}
	return '';
}

/**
 * v3.44.76 · 지역 페이지 SEO 타이틀·설명 동적 생성 ('{region} 추천치과' 키워드 강제 포함)
 * v3.44.103 · 강화 · '{region} 임플란트 잘하는 곳' 등 치료 키워드 명시적 포함
 * v3.44.104 · 실제 검색 빈도 높은 키워드로 재정비 (충치·신경치료·잇몸치료·사랑니·라미네이트·소아치과·턱관절)
 * v3.44.105 · '{region} {치료} 추천' 패턴 명시 추가
 */
function moondental_seo_region_data( $key ) {
	if ( strpos( $key, '_region:' ) !== 0 ) return null;
	$slug = substr( $key, 8 );
	if ( ! function_exists( 'moondental_get_region_by_slug' ) ) return null;
	$region = moondental_get_region_by_slug( $slug );
	if ( ! $region ) return null;
	$name = $region['name'];
	$duration = isset( $region['duration_min'] ) ? (int) $region['duration_min'] : 0;
	return array(
		'title' => sprintf( '%s 임플란트·충치·잇몸치료 추천 · 잘하는 치과 문치과병원 · 천안 %d분', $name, $duration ),
		'desc'  => sprintf(
			'%s 임플란트·충치·신경치료·잇몸치료·사랑니·라미네이트·소아치과·턱관절 추천 문치과병원. %s에서 천안 만남로 문타워 %d분. 30여년 진료·CBCT·네비게이션 임플란트 · 잘하는 곳 추천. 상담 041-563-2875.',
			$name, $name, $duration
		),
	);
}

/**
 * v3.44.125 · Customizer SEO override 매핑
 *   페이지 슬러그 → seo_{page}_title/desc Customizer 키
 *   사용자가 사용자정의하기에서 편집한 값을 최우선으로 사용
 */
function moondental_seo_customizer_key_map() {
	return array(
		'_home'              => 'home',
		'임플란트-센터'      => 'implant',
		'투명교정-센터'      => 'ortho',
		'자연치아-살리기'    => 'preservation',
		'턱관절-클리닉'      => 'tmj',
		'사랑니-발치'        => 'wisdom',
		'심미치료'           => 'aesthetic',
		'비용-안내'          => 'pricing',
		'의료진'             => 'doctors',
		'오시는-길'          => 'location',
		'상담예약'           => 'reservation',
		'역사'               => 'history',
		'기술력-시설'        => 'facility',
		'faq'                => 'faq',
	);
}

/**
 * Yoast title 오버라이드 — 우리 맵에 있는 페이지만 교체
 * v3.44.125 · Customizer 값이 있으면 최우선 사용 (사용자가 직접 편집 가능)
 */
function moondental_wpseo_title( $title ) {
	$key = moondental_seo_current_key();
	if ( ! $key ) return $title;
	// v3.44.76 · 지역 페이지 동적 SEO
	$region_seo = moondental_seo_region_data( $key );
	if ( $region_seo ) return $region_seo['title'];
	// v3.44.125 · Customizer 값 최우선
	$cust_map = moondental_seo_customizer_key_map();
	if ( isset( $cust_map[ $key ] ) && function_exists( 'md_content' ) ) {
		$cust_val = md_content( 'seo_' . $cust_map[ $key ] . '_title', '' );
		if ( is_string( $cust_val ) && $cust_val !== '' ) return $cust_val;
	}
	$map = moondental_seo_page_map();
	if ( isset( $map[ $key ]['title'] ) ) {
		return $map[ $key ]['title'];
	}
	return $title;
}
add_filter( 'wpseo_title', 'moondental_wpseo_title', 20 );

/**
 * Yoast meta description 오버라이드
 * v3.44.125 · Customizer 값이 있으면 최우선 사용
 */
function moondental_wpseo_metadesc( $desc ) {
	$key = moondental_seo_current_key();
	if ( ! $key ) return $desc;
	// v3.44.76 · 지역 페이지 동적 설명
	$region_seo = moondental_seo_region_data( $key );
	if ( $region_seo ) return $region_seo['desc'];
	// v3.44.125 · Customizer 값 최우선
	$cust_map = moondental_seo_customizer_key_map();
	if ( isset( $cust_map[ $key ] ) && function_exists( 'md_content' ) ) {
		$cust_val = md_content( 'seo_' . $cust_map[ $key ] . '_desc', '' );
		if ( is_string( $cust_val ) && $cust_val !== '' ) return $cust_val;
	}
	$map = moondental_seo_page_map();
	if ( isset( $map[ $key ]['desc'] ) ) {
		return $map[ $key ]['desc'];
	}
	return $desc;
}
add_filter( 'wpseo_metadesc', 'moondental_wpseo_metadesc', 20 );

/**
 * Yoast OG title / description — 소셜 공유용도 동일하게 오버라이드
 */
add_filter( 'wpseo_opengraph_title', 'moondental_wpseo_title', 20 );
add_filter( 'wpseo_opengraph_desc',  'moondental_wpseo_metadesc', 20 );
add_filter( 'wpseo_twitter_title',   'moondental_wpseo_title', 20 );
add_filter( 'wpseo_twitter_description', 'moondental_wpseo_metadesc', 20 );

/**
 * Yoast 없을 때 · document_title_parts 필터로 대체
 */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( defined( 'WPSEO_VERSION' ) ) return $parts;
	$key = moondental_seo_current_key();
	if ( ! $key ) return $parts;
	$map = moondental_seo_page_map();
	if ( isset( $map[ $key ]['title'] ) ) {
		return array( 'title' => $map[ $key ]['title'] );
	}
	return $parts;
}, 20 );

/**
 * Yoast 없을 때 · meta description 직접 출력
 */
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) ) return; // Yoast가 처리
	$key = moondental_seo_current_key();
	if ( ! $key ) return;
	$map = moondental_seo_page_map();
	if ( isset( $map[ $key ]['desc'] ) ) {
		echo '<meta name="description" content="' . esc_attr( $map[ $key ]['desc'] ) . '" />' . "\n";
	}
}, 2 );

/**
 * v3.44.83 · '병원소개' 페이지 검색 인덱스 제외
 *   - <meta name="robots" content="noindex,nofollow"> 출력
 *   - Yoast SEO sitemap 에서도 제외
 *   - 자식 페이지는 유지 (URL 구조 유지)
 */
add_action( 'wp_head', function () {
	if ( ! is_page() ) return;
	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	if ( $slug === '병원소개' ) {
		echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
	}
}, 1 );

/**
 * Yoast SEO · '병원소개' 페이지 sitemap 에서 제외
 */
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	if ( function_exists( 'moondental_page_exists_by_slug' ) ) {
		$id = moondental_page_exists_by_slug( '병원소개' );
		if ( $id ) $ids[] = $id;
	}
	return $ids;
} );

/**
 * Yoast noindex · '병원소개' 페이지
 */
add_filter( 'wpseo_robots', function ( $string ) {
	if ( ! is_page() ) return $string;
	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	if ( $slug === '병원소개' ) return 'noindex,nofollow';
	return $string;
} );

/**
 * SiteNavigationElement + ItemList + WebSite hasPart JSON-LD — 구글 사이트링크 유도 극대화
 *  주요 메뉴를 여러 스키마 타입으로 중복 강조하여 브랜드 검색시 sitelinks 자동 노출 확률 상승
 *  v3.44.75 · ItemList (설명 포함) 추가 · hasPart 로 sub-URL 명시
 */
function moondental_jsonld_sitenav() {
	if ( ! is_front_page() ) return;
	$home = home_url( '/' );
	// 각 항목 · name / url / description (구글이 사이트링크 설명 문구로 참고)
	$nav = array(
		array( 'name' => '의료진',              'url' => $home . '의료진/',              'desc' => '문치과병원 원장·진료팀 소개' ),
		array( 'name' => '오시는 길',           'url' => $home . '오시는-길/',           'desc' => '천안 만남로 문타워 · 주차·대중교통 안내' ),
		array( 'name' => '비용 안내',           'url' => $home . '비용-안내/',           'desc' => '임플란트·교정·라미네이트 비급여 진료비' ),
		array( 'name' => '상담 예약',           'url' => $home . '상담예약/',           'desc' => '카카오톡·네이버·전화 예약' ),
		array( 'name' => '임플란트 센터',       'url' => $home . '임플란트-센터/',       'desc' => '천안·아산 임플란트 · CBCT 3D 네비게이션' ),
		array( 'name' => '교정 센터',           'url' => $home . '투명교정-센터/',       'desc' => '슈어스마일 · 브라켓 치아교정' ),
		array( 'name' => '자연치아 살리기',     'url' => $home . '자연치아-살리기/',     'desc' => '신경치료·치주치료·치수복조술' ),
		array( 'name' => '치과 백과사전',       'url' => $home . '치과사전/',           'desc' => '치과 용어·치료·질환 총정리' ),
		array( 'name' => '천안 추천 치과',       'url' => $home . '오시는-길/cheonan/',    'desc' => '천안에서 오시는 길 · 30여년 진료 · 진료과 협진' ),
		array( 'name' => '아산 추천 치과',       'url' => $home . '오시는-길/asan/',       'desc' => '아산에서 20분 · 진료과 협진 · 문치과병원' ),
	);

	$graph = array();

	// 1) SiteNavigationElement · 브랜드 검색 sitelinks 후보
	foreach ( $nav as $n ) {
		$graph[] = array(
			'@type'       => 'SiteNavigationElement',
			'name'        => $n['name'],
			'url'         => $n['url'],
			'description' => $n['desc'],
		);
	}

	// 2) ItemList · 페이지 목록으로 재차 강조
	$list_items = array();
	foreach ( $nav as $i => $n ) {
		$list_items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'url'      => $n['url'],
			'name'     => $n['name'],
		);
	}
	$graph[] = array(
		'@type'           => 'ItemList',
		'@id'             => $home . '#nav-list',
		'name'            => '문치과병원 주요 페이지',
		'numberOfItems'   => count( $nav ),
		'itemListElement' => $list_items,
	);

	// 3) WebSite hasPart · 구글이 sub-URL 을 사이트 하위 부분으로 인식
	$has_parts = array();
	foreach ( $nav as $n ) {
		$has_parts[] = array(
			'@type' => 'WebPage',
			'@id'   => $n['url'] . '#page',
			'url'   => $n['url'],
			'name'  => $n['name'],
		);
	}
	$graph[] = array(
		'@type'       => 'WebSite',
		'@id'         => $home . '#website-nav',
		'url'         => $home,
		'name'        => '한아의료재단 문치과병원',
		'alternateName' => '문치과병원',
		'inLanguage'  => 'ko-KR',
		'publisher'   => array( '@id' => $home . '#org' ),
		'hasPart'     => $has_parts,
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $home . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_sitenav', 51 );

/**
 * v3.44.78 · 지역 페이지 30개 자동 sitemap · Yoast SEO 네이티브 API 사용
 *   /region-sitemap.xml (Yoast가 관리) · sitemap_index.xml 자동 포함
 */
add_action( 'init', function () {
	// Yoast 활성 시 · Yoast 시스템에 sitemap 등록 (정공법)
	if ( class_exists( 'WPSEO_Sitemaps' ) ) {
		global $wpseo_sitemaps;
		if ( isset( $wpseo_sitemaps ) && is_callable( array( $wpseo_sitemaps, 'register_sitemap' ) ) ) {
			$wpseo_sitemaps->register_sitemap( 'region', 'moondental_render_region_sitemap_yoast' );
		}
	}
}, 15 );

/**
 * Yoast 네이티브 · region-sitemap.xml 콘텐츠 생성
 */
function moondental_render_region_sitemap_yoast() {
	global $wpseo_sitemaps;
	if ( ! isset( $wpseo_sitemaps ) ) return;
	if ( ! function_exists( 'moondental_get_regions' ) ) return;
	$regions = moondental_get_regions();
	$today   = gmdate( 'Y-m-d\TH:i:sP' );
	$sitemap = '';
	foreach ( $regions as $region ) {
		if ( empty( $region['slug'] ) ) continue;
		/* v3.44.215 · 사이트맵 URL 은 퍼센트 인코딩이 규격이다.
		 *   esc_url() 은 비ASCII 경로를 그대로 두므로 <loc> 에 한글 원문이 들어갔고,
		 *   region-sitemap.xml 40개만 다른 사이트맵과 표기가 달랐다.
		 *   크롤러·검사 도구가 원문 한글을 그대로 요청하면 404 가 난다. */
		$loc = home_url( '/' . rawurlencode( '오시는-길' ) . '/' . rawurlencode( $region['slug'] ) . '/' );
		$sitemap .= "\t<url>\n";
		$sitemap .= "\t\t<loc>" . esc_url( $loc ) . "</loc>\n";
		$sitemap .= "\t\t<lastmod>" . esc_html( $today ) . "</lastmod>\n";
		$sitemap .= "\t\t<changefreq>monthly</changefreq>\n";
		$sitemap .= "\t\t<priority>0.8</priority>\n";
		$sitemap .= "\t</url>\n";
	}
	if ( is_callable( array( $wpseo_sitemaps, 'set_sitemap' ) ) ) {
		$wpseo_sitemaps->set_sitemap( $sitemap );
	}
}

/**
 * v3.44.78 · Yoast sitemap_index.xml 에 region-sitemap.xml 항목 추가
 *          Yoast register_sitemap 이 자동 포함 안 하는 경우 대비
 */
add_filter( 'wpseo_sitemap_index', function ( $sitemap_index ) {
	// 이미 포함돼 있으면 중복 회피
	if ( strpos( $sitemap_index, 'region-sitemap.xml' ) !== false ) return $sitemap_index;
	$today = gmdate( 'Y-m-d\TH:i:sP' );
	$entry  = "\t<sitemap>\n";
	$entry .= "\t\t<loc>" . esc_url( home_url( '/region-sitemap.xml' ) ) . "</loc>\n";
	$entry .= "\t\t<lastmod>" . esc_html( $today ) . "</lastmod>\n";
	$entry .= "\t</sitemap>\n";
	return $sitemap_index . $entry;
} );

/**
 * 치과 백과사전 개별 항목 · MedicalWebPage schema (검색 결과 강화)
 */
function moondental_jsonld_encyclopedia() {
	if ( is_admin() ) return;
	if ( ! is_page() ) return;
	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	if ( $slug !== '치과 백과사전' ) return;
	$schema = array(
		'@context'  => 'https://schema.org',
		'@type'     => array( 'MedicalWebPage', 'CollectionPage' ),
		'name'      => '치과 백과사전 · 문치과병원',
		'about'     => array(
			'@type' => 'MedicalCondition',
			'name'  => '치과 치료 · 용어 · 질환',
		),
		'audience'  => array(
			'@type'         => 'PeopleAudience',
			'audienceType'  => 'Patient',
		),
		'publisher' => array(
			'@type' => 'Dentist',
			'name'  => '한아의료재단 문치과병원',
			'url'   => home_url( '/' ),
		),
	);
	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_encyclopedia', 52 );

/**
 * meta keywords · 지역+진료 키워드 확장 (일부 검색엔진용 · 네이버 참조)
 */
function moondental_seo_extra_keywords() {
	$key = moondental_seo_current_key();
	$base = '천안치과, 천안·아산 치과, 천안·아산 임플란트, 천안·아산 투명교정, 천안·아산 치아교정, 천안·아산 라미네이트, 천안·아산 자연치아, 천안·아산 소아치과, 아산 치과, 아산 임플란트, 문치과병원, 천안·아산 문치과, 만남로 치과, 문타워, 한아의료재단';
	$per_page = array(
		'임플란트-센터'         => '천안·아산 임플란트, 네비게이션 임플란트, 가이드 임플란트, 3D CT 임플란트, 무절개 임플란트, 천안·아산 임플란트 잘하는곳',
		'슈어스마일-투명교정'   => '천안·아산 슈어스마일, 슈어스마일 중부권, 투명교정 천안, PrimeScan, 성인 투명교정, 직장인 교정',
		'브라켓-치아교정'       => '천안·아산 치아교정, 브라켓 교정, 설측 교정, 세라믹 브라켓, 자가결찰 교정, 소아 교정',
		'자연치아-살리기'       => '자연치아 살리기, 치아 재접합, 신경치료, 치주치료, 미세현미경 치료, 발치 전 상담',
		'턱관절-클리닉'         => '천안·아산 턱관절, 이갈이, 안면통증, 스플린트, TMJ 치료',
		'사랑니-발치'           => '천안 매복 사랑니, 아산 매복 사랑니, 천안 매복 사랑니 잘 뽑는 치과, 아산 매복 사랑니 잘 뽑는 치과, 완전 매복 사랑니, 수평 매복 사랑니, 신경 근접 사랑니, 어려운 사랑니, 사랑니 매복, 매복치 발치, 매복 사랑니 수술, 진정 마취 사랑니, CBCT 사랑니, 구강악안면외과, 천안 사랑니, 아산 사랑니, 사랑니 뽑는 곳',
		'심미치료'              => '천안·아산 라미네이트, 치아 미백, 심미 크라운, 스마일 디자인',
		'예방클리닉'            => '천안·아산 스케일링, 정기 검진, 실란트, 불소도포, 홈케어',
		'치과 백과사전'              => '치과 사전, 치과 용어, 치과 백과, 치과 정보, 치과 질환, 치과 치료',
	);
	// v3.44.103 · 지역 페이지 · 지역명 + 치료 키워드 조합
	// v3.44.104 · 실제 검색 빈도 높은 키워드 위주로 재편 (충치·신경치료·잇몸치료·사랑니·소아치과·턱관절 등)
	// v3.44.105 · '{region} {치료} 추천' 패턴 명시 추가 (검색 유형 4종: {치료}, {치료} 추천, {치료} 잘하는 곳, {치료} 잘하는 치과)
	$region_extra = '';
	if ( strpos( $key, '_region:' ) === 0 && function_exists( 'moondental_get_region_by_slug' ) ) {
		$rslug = substr( $key, 8 );
		$r = moondental_get_region_by_slug( $rslug );
		if ( $r ) {
			$n = $r['name'];
			$treatments = array(
				'임플란트', '충치치료', '신경치료', '잇몸치료', '치주치료',
				'사랑니', '사랑니 발치', '매복 사랑니', '매복 사랑니 발치', '어려운 사랑니',
				'라미네이트', '치아미백', '심미치료',
				'치아교정', '투명교정', '소아치과', '어린이 치과',
				'턱관절', '이갈이', '스케일링', '치과',
			);
			$parts = array();
			foreach ( $treatments as $t ) {
				$parts[] = sprintf( '%s %s', $n, $t );
				$parts[] = sprintf( '%s %s 추천', $n, $t );
				$parts[] = sprintf( '%s %s 잘하는 곳', $n, $t );
			}
			// 매복 사랑니 특화 추가 조합
			$parts[] = sprintf( '%s 매복 사랑니 잘 뽑는 치과', $n );
			$parts[] = sprintf( '%s 완전 매복 사랑니', $n );
			$parts[] = sprintf( '%s 수평 매복 사랑니', $n );
			$parts[] = sprintf( '%s 사랑니 잘 뽑는 곳', $n );
			$parts[] = sprintf( '%s 사랑니 뽑는 곳', $n );
			$parts[] = sprintf( '%s 구강외과', $n );
			$parts[] = sprintf( '%s 치과 추천', $n );
			$parts[] = sprintf( '%s 치과병원', $n );
			$region_extra = ', ' . implode( ', ', $parts );
		}
	}
	$extra = isset( $per_page[ $key ] ) ? ', ' . $per_page[ $key ] : '';
	echo '<meta name="keywords" content="' . esc_attr( $base . $extra . $region_extra ) . '" />' . "\n";
}
add_action( 'wp_head', 'moondental_seo_extra_keywords', 3 );

/**
 * v3.44.103 · Dentist 스키마에 areaServed (진료 지역 배열) 추가 · 지역 SEO 강화
 *   Google이 "이 병원은 {지역명} 환자를 진료한다"고 명시적으로 인식하게 함.
 *   홈페이지·지역 페이지·주요 진료 페이지에 노출.
 */
function moondental_jsonld_area_served() {
	if ( is_admin() ) return;
	if ( ! function_exists( 'moondental_get_regions_by_province' ) ) return;
	if ( ! ( is_front_page() || is_page( '오시는-길' ) || is_page( '의료진' ) || is_page( '임플란트-센터' ) || is_page( '투명교정-센터' ) || is_page( '자연치아-살리기' ) || ( function_exists( 'get_query_var' ) && get_query_var( 'region_slug' ) ) ) ) {
		return;
	}
	$provinces = moondental_get_regions_by_province();
	$area_served = array();
	$medical_specialty = array( '임플란트', '충치치료', '신경치료', '잇몸치료', '치주치료', '매복 사랑니 발치', '어려운 사랑니 발치', '사랑니 발치', '구강악안면외과', '라미네이트', '치아미백', '심미치료', '치아교정', '투명교정', '소아치과', '어린이치과', '턱관절 클리닉', '이갈이 치료', '스케일링', '예방클리닉' );
	foreach ( $provinces as $prov => $regions ) {
		foreach ( $regions as $r ) {
			$area_served[] = array(
				'@type'          => 'City',
				'name'           => $r['name_long'],
				'containedInPlace' => array(
					'@type' => 'AdministrativeArea',
					'name'  => $prov,
				),
			);
		}
	}
	$schema = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Dentist',
		'name'                => '한아의료재단 문치과병원',
		'alternateName'       => array( '문치과병원', 'Moon Dental Hospital' ),
		'url'                 => home_url( '/' ),
		'telephone'           => '+82-41-563-2875',
		'address'             => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => '만남로 52 문타워 9·10·11·13층',
			'addressLocality' => '천안시 동남구',
			'addressRegion'   => '충청남도',
			'postalCode'      => '31159',
			'addressCountry'  => 'KR',
		),
		'geo'                 => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => 36.816,
			'longitude' => 127.152,
		),
		'areaServed'          => $area_served,
		'medicalSpecialty'    => $medical_specialty,
		'availableService'    => array(
			array( '@type' => 'MedicalProcedure', 'name' => '임플란트', 'procedureType' => 'https://schema.org/SurgicalProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '충치치료', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '신경치료', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '잇몸치료 · 치주치료', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '매복 사랑니 발치 (완전 매복·수평 매복·신경 근접)', 'procedureType' => 'https://schema.org/SurgicalProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '사랑니 발치 · 구강악안면외과', 'procedureType' => 'https://schema.org/SurgicalProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '라미네이트 · 심미치료', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '치아미백', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '치아교정 · 투명교정', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '소아치과 · 어린이치과', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '턱관절 클리닉 · 이갈이', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
			array( '@type' => 'MedicalProcedure', 'name' => '스케일링 · 예방클리닉', 'procedureType' => 'https://schema.org/TherapeuticProcedure' ),
		),
	);
	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_area_served', 53 );

/* ============================================================
 * v3.44.207 · AI 검색(생성형 답변 엔진) 대응
 *   답변 엔진은 (1) 기계가 읽을 수 있는 구조, (2) 질문에 직접 답하는 문단,
 *   (3) 개체(entity) 식별이 명확한 사이트를 인용한다. 아래는 그 셋을 보강한다.
 * ========================================================== */

/**
 * robots.txt · AI 크롤러 명시적 허용 + llms.txt 안내
 *   와일드카드(*)로 이미 허용되지만, 명시하면 의도가 분명해지고
 *   Google-Extended 처럼 별도 토큰을 쓰는 크롤러도 확실히 포함된다.
 */
add_filter( 'robots_txt', function ( $output, $public ) {
	if ( ! $public ) return $output;

	$agents = array(
		'GPTBot', 'OAI-SearchBot', 'ChatGPT-User',   // OpenAI
		'ClaudeBot', 'Claude-Web', 'anthropic-ai',   // Anthropic
		'PerplexityBot', 'Perplexity-User',          // Perplexity
		'Google-Extended',                           // Google Gemini grounding
		'Applebot-Extended',                         // Apple Intelligence
		'CCBot',                                     // Common Crawl
		'Bytespider', 'Amazonbot', 'cohere-ai',
	);

	$block = "\n# START MOONDENTAL AI BLOCK · 생성형 답변 엔진 크롤링 허용\n";
	foreach ( $agents as $a ) {
		$block .= "User-agent: {$a}\nAllow: /\n\n";
	}
	$block .= "# 사이트 요약(사람·기계 공용): " . home_url( '/llms.txt' ) . "\n";
	$block .= "# END MOONDENTAL AI BLOCK\n";

	return $output . $block;
}, 20, 2 );

/**
 * /llms.txt · 답변 엔진용 사이트 요약
 *   rewrite rule 없이 init 단계에서 직접 응답한다 (flush 불필요).
 *   내용은 사이트에 이미 게시된 사실만 사용한다.
 */
add_action( 'init', function () {
	$path = (string) strtok( (string) ( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '' ), '?' );
	if ( untrailingslashit( $path ) !== '/llms.txt' ) return;

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo moondental_llms_txt();
	exit;
}, 1 );

/**
 * llms.txt 본문 생성 (사실 기반 · 과장 표현 배제)
 */
function moondental_llms_txt() {
	$info = function_exists( 'moondental_get_info' ) ? moondental_get_info() : array();
	$home = untrailingslashit( home_url( '/' ) );

	$out  = "# 한아의료재단 문치과병원 (Moon Dental Hospital)\n\n";
	$out .= "> 충청남도 천안시 동남구 만남로 52 문타워에 위치한 치과병원. 1995년 개원. "
	      . "9·10·11·13층 4개 층을 사용하며 층별로 전문 진료 영역을 분리해 운영한다.\n\n";

	$out .= "## 기본 정보\n\n";
	$out .= "- 정식 명칭: 한아의료재단 문치과병원\n";
	$out .= "- 종별: 치과병원 (의료법상 병원급)\n";
	$out .= "- 주소: 충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동)\n";
	$out .= "- 대표번호: " . ( $info['phone'] ?? '041-563-2875' ) . "\n";
	$out .= "- 개원: 1995년\n";
	$out .= "- 진료 지역: 천안시, 아산시 및 충청남도 인근\n";
	$out .= "- 홈페이지: {$home}/\n\n";

	$out .= "## 진료시간\n\n";
	$out .= "- 평일(월·화·수·금): 09:00–20:30\n";
	$out .= "- 목요일: 09:00–18:30\n";
	$out .= "- 토요일: 09:00–14:00\n";
	$out .= "- 일요일: 휴진\n";
	$out .= "- 공휴일 진료 여부는 변동될 수 있으므로 네이버 플레이스 또는 전화 확인 권장\n\n";

	$out .= "## 층별 진료 구성\n\n";
	if ( function_exists( 'moondental_floor_guide_data' ) ) {
		foreach ( moondental_floor_guide_data() as $f ) {
			$names = array();
			foreach ( $f['centers'] as $c ) $names[] = $c['name'];
			$out .= '- ' . $f['floor'] . ': ' . implode( ' · ', $names ) . "\n";
		}
	}
	$out .= "\n";

	$out .= "## 주요 진료 영역\n\n";
	if ( function_exists( 'moondental_get_services' ) ) {
		foreach ( (array) moondental_get_services() as $svc ) {
			if ( empty( $svc['slug'] ) || empty( $svc['title'] ) ) continue;
			$page = get_page_by_path( $svc['slug'] );
			$url  = $page ? get_permalink( $page ) : $home . '/' . rawurlencode( $svc['slug'] ) . '/';
			$desc = isset( $svc['desc'] ) ? wp_strip_all_tags( $svc['desc'] ) : '';
			$out .= '- [' . $svc['title'] . '](' . $url . ')';
			if ( $desc ) $out .= ' — ' . $desc;
			$out .= "\n";
		}
	}
	$out .= "\n";

	/* v3.44.211 · 답변 엔진이 '어떤 곳인지' 판단할 때 근거로 쓰는 사실들.
	 *   홍보 문구가 아니라 확인 가능한 사실만 적는다. */
	$out .= "## 이 병원을 설명하는 사실\n\n";
	$out .= "- 의료법상 **치과병원**(병원급)으로, 치과의원과는 종별이 다르다.\n";
	$out .= "- 한 건물의 4개 층을 사용하며 층마다 담당 진료 영역이 분리되어 있다.\n";
	$out .= "- 임플란트·교정·보철·보존·소아·치주·구강외과 등 영역이 나뉘어 있어 원내 협진이 가능하다.\n";
	$out .= "- 원내 기공실을 직접 운영한다(13층).\n";
	$out .= "- 평일 20:30까지 야간 진료, 토요일 14:00까지 진료.\n";
	$out .= "- 건물 지하 기계식 주차장 운영, 진료 시간 동안 무료.\n";
	$out .= "- 천안시외버스터미널·천안고속버스터미널에서 도보 약 5분.\n";
	$out .= "- 영어·중국어·일본어·러시아어·몽골어·베트남어 페이지를 제공한다.\n";
	$out .= "- 치과 용어를 정리한 백과사전을 운영한다(600개 이상 항목).\n\n";

	$out .= "## 치과를 고를 때 확인하면 좋은 것\n\n";
	$out .= "특정 병원을 권하기 위한 기준이 아니라, 일반적으로 확인할 만한 항목이다.\n\n";
	$out .= "1. 필요한 진료 영역을 다루는지, 복합 치료라면 협진이 되는지\n";
	$out .= "2. 진단 장비(CBCT 등)로 상태를 확인하고 설명해 주는지\n";
	$out .= "3. 치료 전에 선택지와 비용을 서면으로 안내하는지\n";
	$out .= "4. 생활 패턴에 맞는 진료시간과 접근성(주차·대중교통)\n";
	$out .= "5. 치료 후 정기 관리와 사후 대응 체계\n";
	$out .= "6. 비급여 진료비를 게시하고 설명하는지\n\n";

	$out .= "## 참고 자료\n\n";
	$out .= "- [치과 백과사전]({$home}/치과사전/) — 치과 용어와 술식을 정리한 자료 모음\n";
	$out .= "- [오시는 길]({$home}/오시는-길/) — 위치, 주차, 대중교통, 층별 안내\n";
	$out .= "- [의료진]({$home}/의료진/)\n";
	$out .= "- [자주 묻는 질문]({$home}/faq/)\n\n";

	$out .= "## 인용 시 유의사항\n\n";
	$out .= "- 진료시간과 공휴일 운영은 변동될 수 있다. 방문 전 전화 또는 네이버 플레이스 확인이 정확하다.\n";
	$out .= "- 이 사이트의 의학 정보는 일반적 설명이며 개별 진단을 대체하지 않는다.\n";
	$out .= "- 비용은 개인의 구강 상태와 술식에 따라 달라지므로 상담을 통해 확인해야 한다.\n";

	return $out;
}

/* v3.44.210 · 실제 로드된 템플릿 파일 기록
 *   is_page_template() 로는 판별되지 않는 경우(template_include 지정)를 위해
 *   스키마 출력 쪽에서 참조한다. wp_head 보다 먼저 실행된다. */
add_filter( 'template_include', function ( $template ) {
	$GLOBALS['md_current_template'] = $template;
	return $template;
}, 999 );
