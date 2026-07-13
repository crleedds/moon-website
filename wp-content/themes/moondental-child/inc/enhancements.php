<?php
/**
 * 사이트 전반 강화 — SEO 구조화 데이터, 모바일 CTA, 카카오톡 플로팅, GA4 클릭 트래킹.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


/* ============================================================
 * 1. JSON-LD 구조화 데이터 (Dentist / LocalBusiness)
 *    네이버·구글 검색 결과에 평점·주소·시간·전화 풍부 표시
 * ========================================================== */
/**
 * v3.27.6: Yoast SEO 활성화 감지 — Yoast가 title/OG/Twitter/description을 처리하면 중복 방지 위해 스킵.
 * 지역 SEO(geo)와 verification 태그만 계속 출력 (Yoast가 다루지 않는 영역).
 */
function moondental_yoast_active() {
	return defined( 'WPSEO_VERSION' );
}

/**
 * SEO 메타 태그 — description / keywords / Open Graph / Twitter Card.
 *  네이버·구글·카카오·SNS 공유 시 풍부한 정보 노출.
 *  '천안' 지역 키워드를 모든 페이지에 자동 삽입.
 *  Yoast SEO가 활성이면 중복 방지를 위해 title/desc/OG/Twitter는 스킵, geo/verification만 출력.
 */
function moondental_seo_meta_tags() {
	$info = moondental_get_info();
	$site_name = $info['name_full'] ?: '문치과병원';
	$site_url  = home_url( '/' );
	$default_image = MOONDENTAL_URI . '/assets/images/logo/logo-square.png';

	// 페이지별 컨텍스트 결정
	$meta_title = '';
	$meta_desc  = '';
	$meta_keywords = '';
	$og_type    = 'website';

	// 지역별 랜딩 페이지 — /오시는-길/{slug}/ 가 가장 우선 매칭
	$region_slug = get_query_var( 'region_slug' );
	if ( $region_slug && function_exists( 'moondental_get_region_by_slug' ) ) {
		$region = moondental_get_region_by_slug( $region_slug );
		if ( $region ) {
			$rn = $region['name'];
			$meta_title    = $rn . '에서 천안 치과 | ' . $rn . ' 임플란트·' . $rn . ' 교정 — ' . $site_name;
			$meta_desc     = $rn . '에서 천안 만남로 문치과병원까지 자동차 약 ' . $region['duration_min'] . '분 (' . $region['distance_km'] . 'km). ' . $rn . ' 환자분께 천안 임플란트·천안 투명교정·천안 라미네이트 진료. 1995년부터 30여년 한자리.';
			$meta_keywords = $rn . ' 치과, ' . $rn . ' 임플란트, ' . $rn . ' 교정, ' . $rn . ' 투명교정, ' . $rn . ' 라미네이트, ' . $rn . ' 사랑니 발치, ' . $rn . ' 신경치료, ' . $rn . ' 치과 추천, 천안 치과, 천안 임플란트, 문치과병원';
			$og_type = 'article';
		}
	}

	if ( ! $meta_title && is_front_page() ) {
		$meta_title    = '천안·아산 치과 | 천안 임플란트·투명교정·자연치아살리기 — ' . $site_name;
		$meta_desc     = '천안 만남로 1995년 개원 30여년 한자리 진료. 천안·아산 임플란트·투명교정·라미네이트·사랑니 발치·턱관절 치료. 분야별 전문 의료진 협진·CBCT 디지털 가이드·월·화·수·금 야간진료(20:30).';
		$meta_keywords = '천안 치과, 아산 치과, 천안치과, 아산치과, 천안 임플란트, 아산 임플란트, 천안 투명교정, 아산 투명교정, 천안 라미네이트, 아산 라미네이트, 천안 자연치아 살리기, 아산 자연치아 살리기, 천안 사랑니 발치, 아산 사랑니 발치, 천안 턱관절, 아산 턱관절, 천안 신경치료, 아산 신경치료, 천안 미백, 아산 미백, 천안 치과병원, 아산 치과병원, 천안 만남로 치과, 천안 신부동 치과, 천안 동남구 치과, 한아의료재단, 문치과병원, 슈어스마일 투명교정';
	} elseif ( ! $meta_title && is_page() ) {
		$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
		$page_title = wp_strip_all_tags( get_the_title() );

		$service_map = array(
			'임플란트-센터'     => array(
				'title' => '천안·아산 임플란트 | CBCT 디지털 가이드 수술 — ' . $site_name,
				'desc'  => '천안·아산 임플란트 시작가 85만원~. 천안 만남로 30여년 임상, 분야별 전문 의료진 협진, CBCT 디지털 가이드 수술, 전신질환 안심 진료. 자체 한아 임플란트 보철연구소.',
				'kw'    => '천안 임플란트, 아산 임플란트, 천안 임플란트 가격, 아산 임플란트 가격, 천안 임플란트 전문, 아산 임플란트 전문, 천안 디지털 임플란트, 아산 디지털 임플란트, 천안 골이식 임플란트, 아산 골이식 임플란트, 천안 노인 임플란트, 아산 노인 임플란트, 천안 만남로 임플란트',
			),
			'투명교정-센터'     => array(
				'title' => '천안·아산 투명교정 | 슈어스마일 SureSmile — ' . $site_name,
				'desc'  => '천안·아산 투명교정 슈어스마일 (Dentsply Sirona). 천안 만남로 치과교정과 전문의 진료, AI 3D 시뮬레이션, Lite·Standard·Advanced 단계별 합리적 가격(190만원~).',
				'kw'    => '천안 투명교정, 아산 투명교정, 천안 슈어스마일, 아산 슈어스마일, 천안 교정, 아산 교정, 천안 치아교정, 아산 치아교정, 천안 성인교정, 아산 성인교정, 천안 부분교정, 아산 부분교정, 천안 투명교정 가격',
			),
			'자연치아-살리기'   => array(
				'title' => '천안·아산 자연치아 살리기 | 신경치료·재근관치료·치주치료 — ' . $site_name,
				'desc'  => '천안·아산 자연치아 살리기. 발치보다 보존 우선 — 신경치료·재근관치료·치주치료로 자연치아 최대한 살리는 천안 만남로 치과병원.',
				'kw'    => '천안 신경치료, 아산 신경치료, 천안 자연치아 살리기, 아산 자연치아 살리기, 천안 치주치료, 아산 치주치료, 천안 잇몸치료, 아산 잇몸치료, 천안 재근관치료, 천안 충치치료, 아산 충치치료',
			),
			'턱관절-클리닉'     => array(
				'title' => '천안·아산 턱관절 치료 | 통증·소리·개구장애 — ' . $site_name,
				'desc'  => '천안·아산 턱관절 클리닉. 턱 소리·통증·개구장애 진단 및 치료. 천안 만남로 치과병원에서 보존적 치료 우선, 11F 교정과 협진으로 교합 안정화.',
				'kw'    => '천안 턱관절, 아산 턱관절, 천안 턱관절 치료, 아산 턱관절 치료, 천안 턱 소리, 천안 이갈이, 아산 이갈이, 천안 턱관절 보톡스, 천안 스플린트',
			),
			'사랑니-발치'       => array(
				'title' => '천안·아산 사랑니 발치 | CBCT 안전 진단 — ' . $site_name,
				'desc'  => '천안·아산 사랑니 발치. CBCT 3D 진단으로 신경 손상 위험 최소화, 매복 사랑니까지 천안 만남로 구강악안면외과 진료. 진정요법 가능.',
				'kw'    => '천안 사랑니 발치, 아산 사랑니 발치, 천안 매복 사랑니, 아산 매복 사랑니, 천안 사랑니, 아산 사랑니, 천안 구강외과, 아산 구강외과',
			),
			'심미치료'         => array(
				'title' => '천안·아산 라미네이트·미백 | 자연스러운 미소 — ' . $site_name,
				'desc'  => '천안·아산 라미네이트·치아미백·심미보철. 최소 삭제 보존적 접근, 자연스러운 미소를 만드는 천안 만남로 심미치료 전문.',
				'kw'    => '천안 라미네이트, 아산 라미네이트, 천안 미백, 아산 미백, 천안 치아미백, 아산 치아미백, 천안 심미치료, 아산 심미치료, 천안 라미네이트 가격, 천안 앞니 라미네이트',
			),
			'비용-안내'         => array(
				'title' => '천안·아산 치과 비용 안내 | 정직한 진료비 — ' . $site_name,
				'desc'  => '천안·아산 치과 비용 — 임플란트·투명교정·라미네이트·사랑니 발치 비용 안내. 사전 견적서 제공, 시작 후 추가 비용 0원.',
				'kw'    => '천안 치과 비용, 아산 치과 비용, 천안 임플란트 비용, 아산 임플란트 비용, 천안 투명교정 비용, 아산 투명교정 비용, 천안 라미네이트 비용, 천안 사랑니 비용, 천안 치과 가격, 아산 치과 가격',
			),
			'의료진'           => array(
				'title' => '천안·아산 치과 의료진 | 분야별 전문 의료진 협진 — ' . $site_name,
				'desc'  => '천안 만남로 문치과병원 의료진 — 보철·교정·보존·치주·소아·외과 분야별 전문 의료진이 한 케이스를 함께 봅니다.',
				'kw'    => '천안 치과 의사, 아산 치과 의사, 천안 치과 의료진, 아산 치과 의료진, 천안 임플란트 전문의, 아산 임플란트 전문의, 천안 교정 전문의, 아산 교정 전문의, 문치과병원 원장',
			),
			'오시는-길'         => array(
				'title' => '천안 만남로 치과 — 오시는 길 · 주차 · 진료시간 — ' . $site_name,
				'desc'  => '천안 동남구 만남로 52 문타워 9·10·11·13층. 천안종합·고속버스터미널 도보 5분, 천안역 버스 10분. 본원 지하 기계식 주차장 무료.',
				'kw'    => '천안 만남로 치과, 천안 신부동 치과, 천안 동남구 치과, 천안 버스터미널 치과, 문치과병원 위치',
			),
			'상담예약'         => array(
				'title' => '천안·아산 치과 예약 — 네이버 예약·카카오톡 상담 — ' . $site_name,
				'desc'  => '천안 만남로 문치과병원 예약. 네이버 예약 24시간, 전화·카카오톡 상담. 월·화·수·금 야간진료 20:30까지 (목 18:30·토 14:00).',
				'kw'    => '천안 치과 예약, 아산 치과 예약, 천안 치과 상담, 아산 치과 상담, 천안 만남로 치과 예약, 문치과병원 예약',
			),
			'역사'             => array(
				'title' => '문치과병원 30여년의 발자취 | 천안 만남로 1995년 개원 — ' . $site_name,
				'desc'  => '천안 만남로에서 1995년부터 30여년 한자리 진료. 한아의료재단 비영리 법인의 30여년 발자취와 핵심 가치.',
				'kw'    => '문치과병원 역사, 한아의료재단, 천안 30년 치과, 아산 30년 치과, 천안 만남로 치과 1995',
			),
			'기술력-시설'       => array(
				'title' => '천안·아산 치과 시설 | CBCT·디지털 가이드·원내 기공실 — ' . $site_name,
				'desc'  => '천안 만남로 문치과병원 기술력·시설 — 의료기관 종별, 9·10·11·13F 4개 층 통합 진료센터, 디지털 진단·자체 보철 제작·전신질환 대응.',
				'kw'    => '천안 치과 시설, 아산 치과 시설, 천안 디지털 치과, 아산 디지털 치과, 천안 CBCT, 아산 CBCT, 천안 임플란트 가이드, 천안 치과 장비',
			),
			'faq'              => array(
				'title' => '천안·아산 치과 자주 묻는 질문 — 예약·비용·진료 안내 — ' . $site_name,
				'desc'  => '천안 만남로 문치과병원 자주 묻는 질문 — 예약·비용·진료·전신질환 대응·주차·진료시간 등.',
				'kw'    => '천안 치과 FAQ, 아산 치과 FAQ, 천안 치과 문의, 아산 치과 문의, 문치과병원 FAQ',
			),
		);

		if ( isset( $service_map[ $slug ] ) ) {
			$meta_title    = $service_map[ $slug ]['title'];
			$meta_desc     = $service_map[ $slug ]['desc'];
			$meta_keywords = $service_map[ $slug ]['kw'];
		} else {
			$meta_title = $page_title . ' — ' . $site_name . ' (천안·아산 치과)';
			$meta_desc  = '천안 만남로 ' . $site_name . ' — ' . $page_title . '. 1995년부터 천안·아산에서 진료해온 종합 치과병원.';
			$meta_keywords = '천안 치과, 아산 치과, 천안 ' . $page_title . ', 아산 ' . $page_title . ', ' . $site_name;
		}
		$og_type = 'article';
	} elseif ( is_single() ) {
		$post_title = wp_strip_all_tags( get_the_title() );
		$excerpt    = wp_strip_all_tags( get_the_excerpt() );
		$meta_title = $post_title . ' — ' . $site_name . ' (천안·아산 치과)';
		$meta_desc  = $excerpt ?: ( '천안 만남로 ' . $site_name . ' — ' . $post_title );
		$meta_keywords = '천안 치과, 아산 치과, 천안 치과 소식, 아산 치과 소식, ' . $site_name;
		$og_type    = 'article';
	}

	if ( ! $meta_title ) return;

	// Featured image 또는 hero image 또는 기본 로고
	$og_image = $default_image;
	if ( is_singular() && has_post_thumbnail() ) {
		$og_image = get_the_post_thumbnail_url( null, 'full' );
	} else {
		$hero_id = (int) get_theme_mod( 'moondental_hero_image', 0 );
		if ( $hero_id ) {
			$src = wp_get_attachment_image_url( $hero_id, 'full' );
			if ( $src ) $og_image = $src;
		}
	}

	$current_url = is_singular() ? get_permalink() : home_url( add_query_arg( null, null ) );
	$yoast       = moondental_yoast_active();

	// v3.27.6: verification 코드 Customizer에서 편집 가능
	$naver_verify  = function_exists( 'md_content' ) ? md_content( 'seo_naver_verify', '' )  : '';
	$google_verify = function_exists( 'md_content' ) ? md_content( 'seo_google_verify', '' ) : '';

	echo "\n<!-- moondental SEO meta -->\n";

	if ( ! $yoast ) {
		echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
		if ( $meta_keywords ) {
			echo '<meta name="keywords" content="' . esc_attr( $meta_keywords ) . '">' . "\n";
		}
	}

	// Verification 코드 (Yoast와 무관 · 각각 등록됨)
	if ( $naver_verify ) {
		echo '<meta name="naver-site-verification" content="' . esc_attr( $naver_verify ) . '">' . "\n";
	}
	if ( $google_verify ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $google_verify ) . '">' . "\n";
	}

	if ( ! $yoast ) {
		// Open Graph
		echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $meta_title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $current_url ) . '">' . "\n";
		echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
		echo '<meta property="og:locale" content="ko_KR">' . "\n";

		// Twitter Card
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $meta_title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '">' . "\n";
	}

	// 지역 SEO (Yoast Local이 없으면 항상 출력 — 무해)
	echo '<meta name="geo.region" content="KR-44">' . "\n"; // 충청남도
	echo '<meta name="geo.placename" content="천안시 동남구 신부동">' . "\n";
	echo '<meta name="geo.position" content="36.8210;127.1572">' . "\n";
	echo '<meta name="ICBM" content="36.8210, 127.1572">' . "\n";
	echo '<!-- /moondental SEO meta -->' . "\n\n";
}
add_action( 'wp_head', 'moondental_seo_meta_tags', 5 );

/**
 * <title> 태그도 SEO 친화적으로 재정의.
 */
function moondental_document_title_parts( $parts ) {
	if ( is_front_page() ) {
		$parts['title'] = '천안 치과 | 천안 임플란트·천안 투명교정·천안 라미네이트';
		$parts['tagline'] = '한아의료재단 문치과병원 (천안 만남로)';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'moondental_document_title_parts', 10 );

function moondental_jsonld_schema() {
	$info = moondental_get_info();
	$site = home_url( '/' );

	// 영업시간 표준 포맷 (Schema.org openingHoursSpecification)
	$hours = array(
		array(
			'@type'      => 'OpeningHoursSpecification',
			'dayOfWeek'  => array( 'Monday', 'Tuesday', 'Wednesday', 'Friday' ),
			'opens'      => '09:00',
			'closes'     => '20:30',
		),
		array(
			'@type'      => 'OpeningHoursSpecification',
			'dayOfWeek'  => 'Thursday',
			'opens'      => '09:00',
			'closes'     => '18:30',
		),
		array(
			'@type'      => 'OpeningHoursSpecification',
			'dayOfWeek'  => 'Saturday',
			'opens'      => '09:00',
			'closes'     => '14:00',
		),
	);

	$sns = array();
	if ( ! empty( $info['kakao_url'] ) )   $sns[] = $info['kakao_url'];
	if ( ! empty( $info['naver_place'] ) ) $sns[] = $info['naver_place'];
	if ( ! empty( $info['instagram'] ) )   $sns[] = $info['instagram'];
	if ( ! empty( $info['blog_url'] ) )    $sns[] = $info['blog_url'];
	if ( ! empty( $info['facebook_url'] ) )$sns[] = $info['facebook_url'];

	$logo = MOONDENTAL_URI . '/assets/images/logo/logo-wide-noreg.png';

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => array( 'Dentist', 'MedicalClinic', 'LocalBusiness' ),
		'@id'             => $site . '#org',
		'name'            => $info['name_full'],
		'alternateName'   => $info['name_short'],
		'description'     => $info['tagline'] . ' — 1995년부터 천안 만남로에서 진료해온 종합 치과병원.',
		'url'             => $site,
		'logo'            => $logo,
		'image'           => $logo,
		'telephone'       => $info['phone'],
		'email'           => $info['email'] ?: 'moondental1995@naver.com',
		'priceRange'      => '₩₩',
		'address'         => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $info['address_road'] ?: $info['address'],
			'addressLocality' => '천안시',
			'addressRegion'   => '충청남도',
			'postalCode'      => '31136',
			'addressCountry'  => 'KR',
		),
		'geo'             => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => 36.8210,
			'longitude' => 127.1572,
		),
		'openingHoursSpecification' => $hours,
		'medicalSpecialty'          => array(
			'Dentistry',
			'OralAndMaxillofacialSurgery',
			'Prosthodontics',
			'Orthodontics',
			'Endodontics',
			'PediatricDentistry',
			'Periodontics',
		),
		'sameAs'          => array_values( array_unique( array_filter( $sns ) ) ),
		'areaServed'      => array(
			array( '@type' => 'City', 'name' => '천안시' ),
			array( '@type' => 'City', 'name' => '아산시' ),
			array( '@type' => 'AdministrativeArea', 'name' => '충청남도' ),
		),
	);

	/* v3.30.0 · aggregateRating + review (홈 후기 데이터 machine-readable 노출) */
	if ( function_exists( 'moondental_get_testimonials' ) ) {
		$testimonials = moondental_get_testimonials();
		if ( ! empty( $testimonials ) ) {
			$count = 0;
			$sum   = 0;
			$reviews = array();
			foreach ( $testimonials as $t ) {
				$r = isset( $t['rating'] ) ? (int) $t['rating'] : 0;
				if ( $r < 1 || $r > 5 ) continue;
				$count++;
				$sum += $r;
				$reviews[] = array(
					'@type'         => 'Review',
					'reviewRating'  => array(
						'@type'       => 'Rating',
						'ratingValue' => $r,
						'bestRating'  => 5,
					),
					'author'        => array(
						'@type' => 'Person',
						'name'  => isset( $t['name'] ) ? $t['name'] : '',
					),
					'reviewBody'    => isset( $t['text'] ) ? wp_strip_all_tags( $t['text'] ) : '',
				);
			}
			if ( $count > 0 ) {
				$schema['aggregateRating'] = array(
					'@type'       => 'AggregateRating',
					'ratingValue' => round( $sum / $count, 1 ),
					'reviewCount' => $count,
					'bestRating'  => 5,
					'worstRating' => 1,
				);
				$schema['review'] = $reviews;
			}
		}
	}

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_schema', 50 );


/**
 * v3.27.6: BreadcrumbList JSON-LD — 각 페이지의 계층 구조를 검색엔진에 알림.
 * 홈에서는 출력 안 함. 페이지 부모/조상까지 추적해서 계층 생성.
 */
function moondental_jsonld_breadcrumb() {
	if ( is_front_page() || is_admin() ) return;

	$home = home_url( '/' );
	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => '홈',
			'item'     => $home,
		),
	);

	if ( is_page() ) {
		$page_id = get_queried_object_id();
		$ancestors = array_reverse( get_post_ancestors( $page_id ) );
		$pos = 2;
		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => wp_strip_all_tags( get_the_title( $ancestor_id ) ),
				'item'     => get_permalink( $ancestor_id ),
			);
		}
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos,
			'name'     => wp_strip_all_tags( get_the_title( $page_id ) ),
			'item'     => get_permalink( $page_id ),
		);
	} elseif ( is_singular( 'post' ) ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => '소식',
			'item'     => $home . '소식/',
		);
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 3,
			'name'     => wp_strip_all_tags( get_the_title() ),
			'item'     => get_permalink(),
		);
	} else {
		return; // 카테고리·검색·404 등은 스킵
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_breadcrumb', 55 );


/**
 * v3.27.6: FAQPage JSON-LD — /자주-묻는-질문/ 페이지에서만 출력.
 * moondental_get_faqs() 데이터를 스키마화 → 구글 리치 결과 (아코디언).
 */
function moondental_jsonld_faq() {
	if ( ! is_page_template( 'page-templates/page-faq.php' ) ) return;
	if ( ! function_exists( 'moondental_get_faqs' ) ) return;

	$faqs = moondental_get_faqs();
	if ( empty( $faqs ) ) return;

	$questions = array();
	foreach ( $faqs as $items ) {
		if ( ! is_array( $items ) ) continue;
		foreach ( $items as $item ) {
			$q = isset( $item['q'] ) ? trim( wp_strip_all_tags( $item['q'] ) ) : '';
			$a = isset( $item['a'] ) ? trim( wp_strip_all_tags( $item['a'] ) ) : '';
			if ( ! $q || ! $a ) continue;
			$questions[] = array(
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $a,
				),
			);
		}
	}

	if ( empty( $questions ) ) return;

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $questions,
	);

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_faq', 60 );


/**
 * v3.30.0 · Person/Physician JSON-LD · 원장 상세 페이지에서 개별 의료진 스키마 방출.
 * page-doctor-single 템플릿에서 $doctor 변수를 통해 접근.
 */
function moondental_jsonld_doctor() {
	if ( ! is_page_template( 'page-templates/page-doctor-single.php' ) ) return;
	if ( ! function_exists( 'moondental_get_team' ) ) return;

	$slug = get_query_var( 'name' ) ?: get_post_field( 'post_name', get_queried_object_id() );
	if ( ! $slug ) return;

	$found = null;
	foreach ( moondental_get_team() as $group ) {
		foreach ( ( $group['members'] ?? array() ) as $doc ) {
			if ( function_exists( 'moondental_doctor_name_to_slug' )
				&& moondental_doctor_name_to_slug( $doc['name'] ) === $slug ) {
				$found = $doc;
				$found['_group'] = $group['group'] ?? '';
				break 2;
			}
		}
	}
	if ( ! $found ) return;

	$info = moondental_get_info();
	$hospital_url = home_url( '/' );

	$schema = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'Physician',
		'name'      => $found['name'],
		'jobTitle'  => isset( $found['role'] ) ? $found['role'] : '원장',
		'worksFor'  => array(
			'@type' => 'MedicalOrganization',
			'name'  => $info['name_full'],
			'url'   => $hospital_url,
		),
		'medicalSpecialty' => 'Dentistry',
		'url'       => get_permalink(),
	);

	if ( ! empty( $found['bio'] ) && is_array( $found['bio'] ) ) {
		$schema['description'] = implode( ' · ', array_slice( $found['bio'], 0, 5 ) );
	}

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_doctor', 65 );


/**
 * v3.30.0 · 지역 페이지 areaServed 스키마 · /오시는-길/{지역}/ 랜딩용
 */
function moondental_jsonld_region() {
	$region_slug = get_query_var( 'region_slug' );
	if ( ! $region_slug || ! function_exists( 'moondental_get_region_by_slug' ) ) return;
	$region = moondental_get_region_by_slug( $region_slug );
	if ( ! $region ) return;

	$info = moondental_get_info();
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => array( 'Dentist', 'LocalBusiness' ),
		'name'       => $info['name_full'],
		'url'        => home_url( '/' ),
		'areaServed' => array(
			'@type' => 'City',
			'name'  => $region['name'],
		),
	);

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_region', 66 );


/**
 * v3.30.0 · 서비스 페이지 FAQPage 스키마 · 슬러그별 FAQ 방출
 * page-templates/page-service.php 에서만 노출.
 */
function moondental_jsonld_service_faq() {
	if ( ! is_page_template( 'page-templates/page-service.php' ) ) return;
	if ( ! function_exists( 'moondental_get_faqs_by_service' ) ) return;

	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	$all_faqs = moondental_get_faqs_by_service();
	if ( empty( $all_faqs[ $slug ] ) ) return;

	$questions = array();
	foreach ( $all_faqs[ $slug ] as $item ) {
		$q = isset( $item['q'] ) ? trim( wp_strip_all_tags( $item['q'] ) ) : '';
		$a = isset( $item['a'] ) ? trim( wp_strip_all_tags( $item['a'] ) ) : '';
		if ( ! $q || ! $a ) continue;
		$questions[] = array(
			'@type'          => 'Question',
			'name'           => $q,
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $a ),
		);
	}
	if ( empty( $questions ) ) return;

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $questions,
	);

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_service_faq', 67 );


/* ============================================================
 * 2. 모바일 하단 고정 CTA 바 + 데스크탑 카카오톡 플로팅 버튼
 * ========================================================== */
function moondental_floating_actions() {
	if ( is_admin() ) return;
	$info = moondental_get_info();
	$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
	$kakao      = $info['kakao_url'] ?: '#';
	$naver      = $info['naver_place'] ?: '#';
	?>
	<!-- 모바일 하단 고정 CTA 바 (모바일에서만) -->
	<div class="md-mobile-cta" role="navigation" aria-label="빠른 예약·문의">
		<a class="md-mobile-cta__item md-mobile-cta__item--call"
		   href="tel:<?php echo esc_attr( $phone_link ); ?>"
		   data-track="cta-call-mobile"
		   aria-label="전화로 예약·상담">
			<span class="md-mobile-cta__icon" aria-hidden="true">📞</span>
			<span class="md-mobile-cta__label">전화 예약</span>
		</a>
		<?php if ( $kakao && $kakao !== '#' ) : ?>
		<a class="md-mobile-cta__item md-mobile-cta__item--kakao"
		   href="<?php echo esc_url( $kakao ); ?>"
		   target="_blank" rel="noopener"
		   data-track="cta-kakao-mobile"
		   aria-label="카카오톡으로 상담">
			<span class="md-mobile-cta__icon" aria-hidden="true">💬</span>
			<span class="md-mobile-cta__label">카카오톡</span>
		</a>
		<?php endif; ?>
		<?php if ( $naver && $naver !== '#' ) : ?>
		<a class="md-mobile-cta__item md-mobile-cta__item--naver"
		   href="<?php echo esc_url( $naver ); ?>"
		   target="_blank" rel="noopener"
		   data-track="cta-naver-mobile"
		   aria-label="네이버로 예약">
			<span class="md-mobile-cta__icon" aria-hidden="true">📅</span>
			<span class="md-mobile-cta__label">네이버 예약</span>
		</a>
		<?php endif; ?>
	</div>

	<!-- 데스크탑 우측 하단 플로팅 버튼 스택 (전화 + 네이버 + 카카오, 데스크탑에서만) -->
	<div class="md-fab-stack" aria-hidden="false">
		<?php if ( $phone_link ) : ?>
		<a class="md-fab md-fab--phone"
		   href="tel:<?php echo esc_attr( $phone_link ); ?>"
		   data-track="cta-phone-fab"
		   aria-label="전화 상담 — <?php echo esc_attr( $info['phone'] ); ?>">
			<span class="md-fab__icon" aria-hidden="true">📞</span>
			<span class="md-fab__label">전화 상담</span>
		</a>
		<?php endif; ?>
		<?php if ( $naver && $naver !== '#' ) : ?>
		<a class="md-fab md-fab--naver"
		   href="<?php echo esc_url( $naver ); ?>"
		   target="_blank" rel="noopener"
		   data-track="cta-naver-fab"
		   aria-label="네이버 예약 열기">
			<span class="md-fab__icon" aria-hidden="true">N</span>
			<span class="md-fab__label">네이버 예약</span>
		</a>
		<?php endif; ?>
		<?php if ( $kakao && $kakao !== '#' ) : ?>
		<a class="md-fab md-fab--kakao"
		   href="<?php echo esc_url( $kakao ); ?>"
		   target="_blank" rel="noopener"
		   data-track="cta-kakao-fab"
		   aria-label="카카오톡 상담 열기">
			<svg class="md-fab__icon md-fab__icon--svg" viewBox="0 0 36 36" aria-hidden="true">
				<path fill="#3C1E1E" d="M18 6C10.27 6 4 10.93 4 17c0 3.97 2.69 7.46 6.72 9.4l-1.43 5.24c-.13.47.39.85.79.58l6.36-4.2c.51.05 1.02.08 1.56.08 7.73 0 14-4.93 14-11s-6.27-11-14-11z"/>
			</svg>
			<span class="md-fab__label">카카오톡 상담</span>
		</a>
		<?php endif; ?>

		<!-- 맨 위로 스크롤 버튼 (FAB 스택의 맨 아래 — 카카오톡 밑) -->
		<button class="md-totop" type="button" aria-label="페이지 맨 위로 이동" data-track="cta-scroll-top" hidden>
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<path d="M12 5l-7 7 1.41 1.41L12 7.83l5.59 5.58L19 12z" fill="currentColor"/>
			</svg>
		</button>
	</div>
	<?php
}
add_action( 'wp_footer', 'moondental_floating_actions', 5 );


/* ============================================================
 * 2b. 기본 메뉴에서 특정 항목 자동 숨기기 (홈/소식/오시는 길)
 *     — WP 관리자에서 메뉴를 따로 정리하지 않아도 화면에서는 제거됨.
 *     primary 메뉴 위치에만 적용. 로고 클릭으로 홈 이동은 그대로 유지.
 * ========================================================== */
function moondental_filter_nav_items( $items, $args ) {
	if ( empty( $args->theme_location ) || $args->theme_location !== 'primary' ) {
		return $items;
	}

	// "역사" 타이틀 항목을 "30여년의 발자취"로 자동 표기 변환
	foreach ( $items as $item ) {
		$t = trim( wp_strip_all_tags( $item->title ) );
		if ( $t === '역사' || $t === '사명 & 역사' ) {
			$item->title = '30여년의 발자취';
		}
	}
	$hide_titles = array(
		'홈', 'home', 'Home',
		'소식', '공지사항', '공지', 'news', 'News',
		'오시는 길', '오시는길', '위치', 'location', 'Location',
	);
	$hide_url_patterns = array( '/오시는-길/', '/location/', '/notices/', '/news/' );
	$site_home         = trailingslashit( home_url( '/' ) );

	/**
	 * 톱레벨로 승격할 항목 정의.
	 * 서브메뉴에서 발견되면 (1) 서브에서 숨기고 (2) 톱레벨에 같은 URL로 자동 노출.
	 */
	$promote_defs = array(
		'doctors' => array(
			'title'           => '의료진',
			'titles'          => array( '의료진', 'doctors', 'Doctors' ),
			'url_substrings'  => array( '/의료진/', '/doctors/' ),
			'fallback_url'    => home_url( '/의료진/' ),
			'class_suffix'    => 'doctors',
			'menu_order'      => 999,
		),
		'pricing' => array(
			'title'           => '비용안내',
			'titles'          => array( '비용안내', '비용 안내', '비급여진료비', '비급여 진료비', '비급여 진료비 안내', '진료비안내', '진료비 안내', '진료비', 'pricing', 'Pricing' ),
			'url_substrings'  => array( '/pricing/', '/비용-안내/', '/비용안내/', '/비급여-진료비/', '/비급여진료비/', '/진료비안내/', '/진료비/' ),
			'fallback_url'    => home_url( '/비용-안내/' ),
			'class_suffix'    => 'pricing',
			'menu_order'      => 1000,
		),
	);

	$is_match = function( $item, $def ) {
		$title       = trim( wp_strip_all_tags( $item->title ) );
		$url         = trailingslashit( (string) $item->url );
		$url_decoded = urldecode( $url );
		if ( in_array( $title, $def['titles'], true ) ) return true;
		foreach ( $def['url_substrings'] as $sub ) {
			if ( strpos( $url, $sub ) !== false || strpos( $url_decoded, $sub ) !== false ) return true;
		}
		return false;
	};

	// 1) 사전 스캔: 톱레벨 존재 여부 + 서브메뉴 URL 캡처
	$state = array();
	foreach ( $promote_defs as $key => $def ) {
		$state[ $key ] = array( 'has_top' => false, 'sub_url' => '' );
	}
	foreach ( $items as $item ) {
		$is_top = empty( $item->menu_item_parent ) || (int) $item->menu_item_parent === 0;
		foreach ( $promote_defs as $key => $def ) {
			if ( ! $is_match( $item, $def ) ) continue;
			if ( $is_top ) {
				$state[ $key ]['has_top'] = true;
			} elseif ( empty( $state[ $key ]['sub_url'] ) && ! empty( $item->url ) ) {
				$state[ $key ]['sub_url'] = $item->url;
			}
		}
	}

	// 2) 숨김 처리: 기본 hide + 승격 대상 서브메뉴 항목
	$items = array_values( array_filter( $items, function( $item ) use ( $hide_titles, $hide_url_patterns, $site_home, $promote_defs, $is_match ) {
		$title  = trim( wp_strip_all_tags( $item->title ) );
		$url    = trailingslashit( (string) $item->url );
		$is_top = empty( $item->menu_item_parent ) || (int) $item->menu_item_parent === 0;

		if ( in_array( $title, $hide_titles, true ) ) return false;
		if ( $url === $site_home ) return false;
		foreach ( $hide_url_patterns as $p ) {
			if ( strpos( $url, $p ) !== false ) return false;
		}
		// 승격 대상 서브메뉴는 제거
		if ( ! $is_top ) {
			foreach ( $promote_defs as $def ) {
				if ( $is_match( $item, $def ) ) return false;
			}
		}
		return true;
	} ) );

	// 3) 톱레벨 부재 시 삽입 — 진료안내 뒤(없으면 끝)
	$find_insert_after = function( $items ) {
		$ia = -1;
		foreach ( $items as $idx => $it ) {
			$t   = trim( wp_strip_all_tags( $it->title ) );
			$top = empty( $it->menu_item_parent ) || (int) $it->menu_item_parent === 0;
			if ( $top && in_array( $t, array( '진료안내', '진료항목', '진료', 'services', 'Services' ), true ) ) {
				$ia = $idx;
			}
		}
		return $ia;
	};

	$max_id = 90000;
	foreach ( $items as $it ) {
		if ( ! empty( $it->ID ) && $it->ID > $max_id ) $max_id = (int) $it->ID;
	}

	foreach ( $promote_defs as $key => $def ) {
		if ( $state[ $key ]['has_top'] ) continue;
		$max_id += 1;
		$new_item = (object) array(
			'ID'                    => $max_id,
			'db_id'                 => $max_id,
			'object_id'             => $max_id,
			'object'                => 'custom',
			'type'                  => 'custom',
			'type_label'            => 'Custom Link',
			'title'                 => $def['title'],
			'url'                   => $state[ $key ]['sub_url'] ?: $def['fallback_url'],
			'menu_item_parent'      => 0,
			'menu_order'            => $def['menu_order'],
			'classes'               => array( 'menu-item', 'menu-item-type-custom', 'menu-item-' . $def['class_suffix'] ),
			'attr_title'            => '',
			'description'           => '',
			'target'                => '',
			'xfn'                   => '',
			'current'               => false,
			'current_item_ancestor' => false,
			'current_item_parent'   => false,
		);
		$ia = $find_insert_after( $items );
		if ( $ia >= 0 ) {
			array_splice( $items, $ia + 1, 0, array( $new_item ) );
		} else {
			$items[] = $new_item;
		}
		$items = array_values( $items );
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'moondental_filter_nav_items', 10, 2 );


/**
 * 독립 탭 페이지(비용안내·의료진 등)에서 부모 메뉴 항목의 ancestor 하이라이트 제거.
 *
 *  문제: 비용안내·의료진 페이지가 WP 페이지 계층상 '병원소개'의 하위 페이지로
 *        설정되어 있어, 해당 페이지 방문 시 병원안내 메뉴 항목에
 *        current_page_parent / current_page_ancestor / current-menu-ancestor
 *        클래스가 자동 추가되어 잘못 하이라이트됨.
 *  해결: 페이지 슬러그가 '독립 탭' 목록에 있을 때 모든 메뉴 항목의
 *        parent/ancestor 류 클래스를 제거. 정확한 현재 항목(current-menu-item)만 유지.
 */
function moondental_strip_ancestor_classes_on_standalone( $classes, $item ) {
	if ( ! is_page() ) return $classes;

	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	$standalone = array(
		'비용-안내', '비용안내', '비급여-진료비', '진료비안내', 'pricing',
		'의료진', 'doctors',
	);
	if ( ! in_array( $slug, $standalone, true ) ) return $classes;

	$strip = array(
		'current_page_parent',
		'current_page_ancestor',
		'current-menu-parent',
		'current-menu-ancestor',
		'current-page-parent',
		'current-page-ancestor',
	);
	return array_values( array_diff( (array) $classes, $strip ) );
}
add_filter( 'nav_menu_css_class', 'moondental_strip_ancestor_classes_on_standalone', 20, 2 );


/* ============================================================
 * 3. GA4 클릭 이벤트 트래킹 (전화·카톡·네이버 예약·예약폼 제출)
 *    GA4 측정 ID가 설정돼있을 때만 gtag 호출. 없으면 dataLayer만.
 * ========================================================== */
function moondental_click_tracking() {
	?>
	<script>
	(function(){
		window.dataLayer = window.dataLayer || [];
		function track(name, params){
			window.dataLayer.push({event: name, ...(params||{})});
			if (typeof gtag === 'function') gtag('event', name, params||{});
		}
		// 모든 tel: 링크 자동 트래킹
		document.addEventListener('click', function(e){
			var a = e.target.closest('a'); if (!a) return;
			if (a.href && a.href.indexOf('tel:') === 0) {
				track('click_phone', {phone: a.href.replace('tel:','')});
			} else if (a.dataset && a.dataset.track) {
				track(a.dataset.track, {href: a.href||''});
			}
		}, true);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'moondental_click_tracking', 99 );


/* ============================================================
 * 3b. GA4 & Google Search Console — Customizer 필드 + head 자동 출력
 * ========================================================== */
function moondental_analytics_customize( $wp_customize ) {
	$wp_customize->add_section( 'moondental_analytics', array(
		'title'    => '분석·검색 도구',
		'panel'    => 'moondental_panel',
		'priority' => 30,
	) );
	$wp_customize->add_setting( 'moondental_ga4_id', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'moondental_ga4_id', array(
		'label'       => 'GA4 측정 ID',
		'description' => 'Google Analytics 4 측정 ID (예: G-XXXXXXXXXX). 입력 시 자동으로 추적 시작.',
		'section'     => 'moondental_analytics',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'moondental_gsc_verify', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'moondental_gsc_verify', array(
		'label'       => 'Google Search Console 인증 코드',
		'description' => 'google-site-verification 메타 태그 값 (HTML 태그 방식으로 인증 시).',
		'section'     => 'moondental_analytics',
		'type'        => 'text',
	) );
	$wp_customize->add_setting( 'moondental_naver_verify', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'moondental_naver_verify', array(
		'label'       => '네이버 서치어드바이저 인증 코드',
		'description' => 'naver-site-verification 메타 태그 값.',
		'section'     => 'moondental_analytics',
		'type'        => 'text',
	) );
}
add_action( 'customize_register', 'moondental_analytics_customize', 20 );

function moondental_analytics_head() {
	$ga = trim( (string) get_theme_mod( 'moondental_ga4_id', '' ) );
	$gv = trim( (string) get_theme_mod( 'moondental_gsc_verify', '' ) );
	$nv = trim( (string) get_theme_mod( 'moondental_naver_verify', '' ) );
	if ( $gv ) echo "\n<meta name=\"google-site-verification\" content=\"" . esc_attr( $gv ) . "\">\n";
	if ( $nv ) echo "<meta name=\"naver-site-verification\" content=\"" . esc_attr( $nv ) . "\">\n";
	if ( $ga && preg_match( '/^G-[A-Z0-9]+$/i', $ga ) ) {
		$ga_esc = esc_js( $ga );
		echo "<!-- GA4 -->\n";
		echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$ga_esc}\"></script>\n";
		echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$ga_esc}');</script>\n";
	}
}
add_action( 'wp_head', 'moondental_analytics_head', 2 );

/**
 * 모바일 브라우저 주소창·앱 스위처 색상을 브랜드 코럴로 통일.
 */
function moondental_theme_color_meta() {
	echo "\n<meta name=\"theme-color\" content=\"#D88062\">\n";
	echo "<meta name=\"msapplication-TileColor\" content=\"#D88062\">\n";
}
add_action( 'wp_head', 'moondental_theme_color_meta', 1 );


/* ============================================================
 * 3c. 모든 이미지에 자동 loading="lazy" + decoding="async" 부착
 * ========================================================== */
function moondental_lazy_images( $content ) {
	if ( is_admin() || is_feed() ) return $content;
	// 이미 loading 속성이 있는 img는 건드리지 않음
	return preg_replace_callback( '#<img\b([^>]*)>#i', function( $m ) {
		$attrs = $m[1];
		if ( stripos( $attrs, 'loading=' ) === false )  $attrs .= ' loading="lazy"';
		if ( stripos( $attrs, 'decoding=' ) === false ) $attrs .= ' decoding="async"';
		return '<img' . $attrs . '>';
	}, $content );
}
add_filter( 'the_content', 'moondental_lazy_images', 99 );
add_filter( 'post_thumbnail_html', 'moondental_lazy_images', 99 );


/* ============================================================
 * 4. 환자 후기 데이터 (홈 섹션용)
 * ========================================================== */
function moondental_get_testimonials() {
	$defaults = array(
		array(
			'name'    => '김○○',
			'gender'  => '여성',
			'age'     => '40대',
			'service' => '임플란트',
			'rating'  => 5,
			'text'    => '오랫동안 미루던 임플란트를 30여년 경력 원장님께 받았습니다. 수술 당일 통증이 거의 없었고, 자가혈을 함께 사용한다는 점이 안심됐어요. 평일 야간 진료가 있어 직장인에게 정말 편합니다.',
		),
		array(
			'name'    => '박○○',
			'gender'  => '남성',
			'age'     => '50대',
			'service' => '전악 보철',
			'rating'  => 5,
			'text'    => '여러 치과를 다녀봤지만 이렇게 충분히 설명해주시는 곳은 처음입니다. 전악 보철까지 진행했는데 의료진 협진이 정말 체계적이에요. 비용도 시작 전에 명확히 알려주셔서 신뢰가 갔습니다.',
		),
		array(
			'name'    => '이○○',
			'gender'  => '여성',
			'age'     => '30대',
			'service' => '투명교정',
			'rating'  => 5,
			'text'    => '슈어스마일 투명교정 받았는데 처음에 걱정했던 것보다 훨씬 편했어요. 11F 교정과 원장님이 사진 시뮬레이션으로 결과를 미리 보여주셨고, 6개월 만에 만족스러운 결과를 얻었습니다.',
		),
		array(
			'name'    => '최○○',
			'gender'  => '남성',
			'age'     => '60대',
			'service' => '임플란트 + 보철',
			'rating'  => 5,
			'text'    => '고혈압이 있어서 다른 곳에서는 거절당했는데, 여기는 혈압 체크부터 약물까지 세심하게 봐주셨습니다. 수술 후 귀가 서비스까지 챙겨주셔서 감동이었어요.',
		),
		array(
			'name'    => '정○○',
			'gender'  => '여성',
			'age'     => '40대',
			'service' => '자연치아 살리기',
			'rating'  => 5,
			'text'    => '발치하고 임플란트 하라던 치아를 보존과 전문의 원장님이 살려주셨어요. 재근관치료로 자연치를 지킬 수 있어서 정말 감사합니다.',
		),
		array(
			'name'    => '한○○',
			'gender'  => '여성',
			'age'     => '50대',
			'service' => '심미 라미네이트',
			'rating'  => 5,
			'text'    => '앞니 라미네이트를 했는데 자연스럽게 잘 나왔어요. 무리한 치아 삭제 없이 보존적으로 해주신다는 점이 마음에 들었고, 결과도 만족합니다.',
		),
	);

	/* Customizer override — 6 reviews. 이름이 비어있으면 카드 자동 숨김. */
	if ( ! function_exists( 'md_content' ) ) return $defaults;
	$result = array();
	for ( $i = 1; $i <= 6; $i++ ) {
		$d = $defaults[ $i - 1 ] ?? array();
		$name = md_content( "review_{$i}_name", $d['name'] ?? '' );
		if ( ! $name ) continue; // 이름 비우면 숨김
		$result[] = array(
			'name'    => $name,
			'gender'  => md_content( "review_{$i}_gender",  $d['gender']  ?? '' ),
			'age'     => md_content( "review_{$i}_age",     $d['age']     ?? '' ),
			'service' => md_content( "review_{$i}_service", $d['service'] ?? '' ),
			'rating'  => (int) md_content( "review_{$i}_rating", $d['rating'] ?? 5 ),
			'text'    => md_content( "review_{$i}_text",    $d['text']    ?? '' ),
		);
	}
	return $result;
}


/* ============================================================
 * 5. FAQ 데이터 — service slug별 자동 매핑
 *    각 진료영역 페이지 하단에 자동 출력
 * ========================================================== */

/**
 * service slug → FAQ Q/A 배열.
 * page-service.php가 자동으로 해당 service의 FAQ를 출력.
 */
function moondental_get_faqs_by_service() {
	$defaults = array(
		'임플란트-센터' => array(
			array( 'q' => '임플란트 수술 후 통증이 많이 있나요?',
				   'a' => '국소마취 하에 진행되며 수술 자체는 거의 통증이 없습니다. PCA 자가진통조절기와 물방울 레이저로 통증을 최대한 줄입니다. 1~2일간의 둔한 통증은 진통제로 충분히 조절됩니다.' ),
			array( 'q' => '임플란트는 얼마나 오래 사용할 수 있나요?',
				   'a' => '관리에 따라 10~20년 이상 유지가 가능합니다. 1년 4회 정기 검진과 매일 구강위생 관리가 핵심이며, 평생 A/S 시스템을 운영합니다.' ),
			array( 'q' => '뼈가 부족하다고 들었는데 임플란트가 가능할까요?',
				   'a' => 'CT 정밀 분석 후 골이식·상악동 거상술·GBR(골유도재생술) 등 환자 골 상태에 맞춘 다양한 옵션이 있습니다. 가능한 한 골이식을 최소화하는 방향으로 계획합니다.' ),
			array( 'q' => '당일 임플란트는 누구나 가능한가요?',
				   'a' => '당일 임플란트는 발치+뼈이식+식립을 하루에 진행하는 시술입니다. 다만 뼈 상태·잇몸 건강·전신 상태에 따라 가능 여부가 결정됩니다. 사전 정밀 진단으로 안전을 확인 후 진행합니다.' ),
			array( 'q' => '건강보험 임플란트 혜택이 있나요?',
				   'a' => '만 65세 이상 건강보험 가입자는 평생 2개까지 본인부담 30%로 적용 가능합니다. 부분 무치악이 대상이며, 잔존치 하나만 있어도 가능합니다.' ),
			array( 'q' => '타치과에서 한 임플란트가 흔들리는데 다시 가능한가요?',
				   'a' => '네, 리무버 키트로 안전하게 제거 후 재식립 가능합니다. 골손실을 최소화하며 필요 시 골이식을 병행합니다. 충분한 상담 후 진행합니다.' ),
			array( 'q' => '당뇨·고혈압이 있는데 가능한가요?',
				   'a' => '전신질환자도 안심 토탈 케어 시스템으로 안전하게 진행 가능합니다. 사전 약물 체크·혈압·당검사·심전도 측정 후 진행합니다.' ),
			array( 'q' => '비용이 어느 정도 들까요?',
				   'a' => '일반 임플란트 120~150만 원, 프리미엄(메가젠 블루다이아몬드) 180~230만 원 선입니다. 정확한 비용은 정밀 진단 후 안내드리며, ' .
				   	  '<a href="' . home_url( '/병원소개/비급여-진료비/' ) . '">비급여 진료비 안내</a>에서 전체 가격대를 확인하실 수 있습니다.' ),
		),

		'투명교정-센터' => array(
			array( 'q' => '성인도 교정이 가능한가요?',
				   'a' => '잇몸·치주 상태가 건강하다면 50~60대도 교정이 가능합니다. 다만 청소년보다 치료 기간이 다소 길어질 수 있습니다.' ),
			array( 'q' => '투명교정과 일반 교정 중 어느 것이 좋나요?',
				   'a' => '케이스 난이도·라이프스타일·예산에 따라 다릅니다. 단순한 케이스는 투명교정이 효율적이고, 복잡한 케이스는 일반 교정이 더 정밀합니다. 정밀 진단 후 추천드립니다.' ),
			array( 'q' => '교정 중 통증은 얼마나 심한가요?',
				   'a' => '장치 부착 직후·조정 후 2~3일간 둔한 통증이 있을 수 있습니다. 진통제로 조절 가능하며 1주일 이내 적응됩니다.' ),
			array( 'q' => '교정 후 다시 돌아간다고 들었는데?',
				   'a' => '유지장치(retainer)를 권장대로 착용하면 거의 재발하지 않습니다. 처음 1년은 24시간, 이후 야간 착용을 평생 권장합니다.' ),
			array( 'q' => '발치를 해야만 교정이 되나요?',
				   'a' => '케이스마다 다릅니다. 정밀 진단 후 발치 없이도 가능한 경우는 비발치로, 공간이 부족해 치아가 들어갈 자리가 없는 경우엔 발치 교정이 필요합니다.' ),
			array( 'q' => '교정 중 식사·양치는 어떻게 하나요?',
				   'a' => '일반 교정: 딱딱하거나 끈적한 음식은 피하고, 치간칫솔·워터픽으로 꼼꼼히 양치. 투명교정: 식사·양치 시 장치를 빼고 보관하므로 식이 제한 없음.' ),
			array( 'q' => '비용은 얼마나 드나요?',
				   'a' => '메탈 브라켓 400~600만 원, 투명교정 600~1,000만 원, 설측 1,000~1,500만 원 선입니다. ' .
				   	  '<a href="' . home_url( '/병원소개/비급여-진료비/' ) . '">비급여 진료비 안내</a>에서 자세히 보실 수 있습니다.' ),
		),

		'자연치아-살리기' => array(
			array( 'q' => '신경치료한 치아는 얼마나 오래 사용할 수 있나요?',
				   'a' => '크라운 보철로 잘 보호하면 평생 사용도 가능합니다. 다만 신경이 없어 통증을 못 느끼므로 정기 검진이 더욱 중요합니다.' ),
			array( 'q' => '신경치료가 많이 아픈가요?',
				   'a' => '국소마취 하에 진행되어 시술 중 통증은 거의 없습니다. 치료 후 2~3일간 둔한 통증이 있을 수 있으나 진통제로 조절 가능합니다.' ),
			array( 'q' => '신경치료보다 발치하고 임플란트 하는 게 낫지 않나요?',
				   'a' => '자연치를 살릴 수 있다면 무조건 살리는 것이 우선입니다. 임플란트는 자연치를 대체할 수 없으며, 신경치료가 비용·기간·신체 부담 면에서 훨씬 효율적입니다.' ),
			array( 'q' => '재근관치료는 성공률이 어느 정도인가요?',
				   'a' => '1차 신경치료보다는 낮지만 약 70~80% 성공률을 보입니다. 현미경 정밀 진료로 성공률을 최대한 끌어올립니다. 실패 시 치근단 수술이나 발치 후 임플란트로 단계적 진행 가능합니다.' ),
			array( 'q' => '신경이 죽은 치아를 그냥 두면 어떻게 되나요?',
				   'a' => '치근 끝에 농양·낭종이 생기고, 결국 뼈를 녹이며 인접치까지 영향을 줍니다. 통증이 없어도 반드시 치료해야 합니다.' ),
		),

		'턱관절-클리닉' => array(
			array( 'q' => '턱에서 소리만 나고 통증은 없는데 치료가 필요한가요?',
				   'a' => '당장 통증이 없어도 디스크가 변위되었을 가능성이 있어 정기 검진을 권합니다. 진행되면 통증·개구 제한으로 발전할 수 있습니다.' ),
			array( 'q' => '보톡스로 정말 턱관절 통증이 좋아지나요?',
				   'a' => '네. 교근의 긴장이 통증의 주된 원인인 경우 효과가 빠릅니다. 다만 보톡스는 근원적 치료가 아니라 보조적 도구이며, 습관 교정·스플린트와 병행해야 장기 효과가 있습니다.' ),
			array( 'q' => '스플린트는 평생 착용해야 하나요?',
				   'a' => '아닙니다. 통상 3~6개월 야간 착용 후 증상이 호전되면 사용 빈도를 줄입니다. 이갈이가 심한 경우 야간 유지장치 형태로 평생 권장될 수 있습니다.' ),
			array( 'q' => '턱관절 수술까지 가는 경우는?',
				   'a' => '거의 드뭅니다. 대부분 비수술적 치료로 호전되며, 수술은 보존 치료에 반응하지 않는 극히 일부 케이스(관절 강직, 심한 디스크 천공)에만 검토됩니다.' ),
			array( 'q' => '두통이 턱관절 때문일 수 있나요?',
				   'a' => '네, 매우 흔합니다. 측두근·교근의 과긴장이 측두부·후두부 두통으로 이어집니다. 만성 두통 환자의 상당수가 턱관절 치료로 호전됩니다.' ),
			array( 'q' => '임플란트 후 턱관절이 아픈데요?',
				   'a' => '전악 임플란트나 다수 보철 후 교합이 변하면서 턱관절에 무리가 갈 수 있습니다. 문치과는 임플란트·보철 치료 후에도 턱관절까지 함께 관리합니다.' ),
		),

		'사랑니-발치' => array(
			array( 'q' => '사랑니 발치 시 신경 마비 위험은 얼마나 되나요?',
				   'a' => '매복 사랑니의 경우 하치조신경과 매우 가까운 경우가 있어 일시적 마비 가능성이 있습니다. 3D CT 사전 분석으로 위험을 최대한 줄이며, 신경에 너무 가까운 경우 안전한 부분 발치(Coronectomy) 옵션도 고려합니다.' ),
			array( 'q' => '임신 중 사랑니 발치가 가능한가요?',
				   'a' => '가급적 출산 후로 미루는 것이 좋습니다. 급성 염증으로 발치가 불가피한 경우 산모·태아 안전을 고려해 임신 중기(4~6개월)에 최소 침습으로 진행 가능합니다.' ),
			array( 'q' => '발치 후 통증은 얼마나 오래 가나요?',
				   'a' => '일반 발치는 2~3일, 매복 사랑니는 3~5일이 통증 피크. 1주일 후엔 거의 사라집니다. 통증이 점점 심해지면 Dry Socket(드라이 소켓) 의심하고 즉시 내원하세요.' ),
			array( 'q' => '사랑니 4개를 한 번에 빼도 되나요?',
				   'a' => '건강한 성인의 경우 가능합니다. 다만 회복 부담이 커서 보통 좌/우 또는 위/아래로 나눠서 진행하는 것을 권장합니다.' ),
			array( 'q' => '발치 후 운동은 언제부터 가능한가요?',
				   'a' => '가벼운 산책은 다음 날부터 가능합니다. 헬스·러닝·구기 종목 등 격렬한 운동은 1주일 후부터 권장합니다.' ),
			array( 'q' => '통증이 무서운데 수면 마취도 가능한가요?',
				   'a' => '전신마취·수면 진정은 별도 협진이 필요하며, 보통 국소마취 + 물방울 레이저 + PCA 자가진통조절기로 충분히 편안하게 진행 가능합니다.' ),
		),

		'심미치료' => array(
			array( 'q' => '라미네이트와 미백 중 무엇이 좋나요?',
				   'a' => '미백으로 해결 가능한 단순 변색은 미백을 우선 권합니다. 미백으로 안 되는 심한 변색이나 형태·배열 문제까지 동반된 경우 라미네이트가 적합합니다.' ),
			array( 'q' => '미백 후 색이 다시 어두워지나요?',
				   'a' => '네, 시간이 지나며 자연스럽게 다시 착색됩니다. 보통 1~2년 후 보강 미백(touch-up)을 권장하며, 가정 미백 트레이로 유지하면 효과가 길어집니다.' ),
			array( 'q' => '임플란트 틀니와 일반 틀니 중 무엇이 좋나요?',
				   'a' => '경제적 여유가 되시면 임플란트 틀니가 훨씬 안정적입니다. 일반 틀니는 약한 고정력·뼈 흡수가 진행되는 반면, 임플란트 틀니는 잇몸뼈를 유지시켜 줍니다.' ),
			array( 'q' => '전악 보철은 한 번에 모두 진행되나요?',
				   'a' => '아닙니다. 환자 상태에 따라 수개월~1년 이상 단계적으로 진행됩니다. 임시 보철로 일상생활을 유지하며 최종 보철 완성까지 진행합니다.' ),
			array( 'q' => '신경치료한 치아도 미백 가능한가요?',
				   'a' => '일반 미백은 효과가 떨어지지만, 워킹 블리치(walking bleach)라는 내부 미백법으로 개선 가능합니다. 상담 시 적합 여부 확인 후 진행합니다.' ),
			array( 'q' => '라미네이트 시술하면 치아가 약해지지 않나요?',
				   'a' => '치아 삭제를 최소화하는 라미네이트는 자연치를 최대한 보존합니다. 다만 한 번 시술하면 되돌리기 어려우므로 충분한 상담 후 진행합니다.' ),
		),

		'소아치과' => array(
			array( 'q' => '아이는 몇 살부터 치과 진료를 받아야 하나요?',
				   'a' => '첫 어금니가 나오는 만 1~2세부터 치과 첫 방문을 권합니다. 검진은 6개월 단위 정기 권장. 만 12세 이하는 영구치 광중합 레진 충전이 건강보험 적용됩니다.' ),
			array( 'q' => '아이가 치과를 너무 무서워하는데 어떻게 해야 하나요?',
				   'a' => '문치과는 아이가 거부하면 절대 강제 진료하지 않습니다. 첫 방문은 진료실 구경·의자 타보기로 끝나기도 합니다. 예약 시 "처음 오는 아이"라고 알려주시면 충분한 시간을 배정해드립니다.' ),
			array( 'q' => '실란트(치아홈 메우기)는 꼭 해야 하나요?',
				   'a' => '필수는 아니지만 만 6~8세 영구치 어금니가 막 나왔을 때 충치 예방 효과가 50% 이상으로 매우 권장됩니다. 건강보험도 적용됩니다.' ),
			array( 'q' => '소아 교정은 언제 시작해야 하나요?',
				   'a' => '골격성 부정교합(주걱턱·무턱)이라면 만 7~10세가 1차 교정 골든타임입니다. 일반 부정교합은 영구치가 다 나온 후(만 12세 이상) 시작합니다.' ),
			array( 'q' => '아이 충치 치료는 어떻게 진행되나요?',
				   'a' => '유치 충치는 글래스아이오노머·콤포지트로 가능한 한 짧은 시간에 치료합니다. 아이가 협조 어려우면 여러 차례 나눠 진행하기도 합니다. 강제 진료는 하지 않습니다.' ),
		),
	);

	/* Customizer override — 각 슬러그에 대해 "질문 | 답변" 파이프 텍스트 파싱 */
	if ( function_exists( 'md_content' ) && function_exists( 'moondental_service_slug_to_key' ) ) {
		foreach ( $defaults as $slug => $_ ) {
			$key = moondental_service_slug_to_key( $slug );
			if ( ! $key ) continue;
			$text = md_content( "service_{$key}_faqs", '' );
			if ( ! $text ) continue;
			$parsed = array();
			foreach ( preg_split( "/\r\n|\r|\n/", $text ) as $line ) {
				$line = trim( $line );
				if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
				$parts = array_map( 'trim', explode( '|', $line, 2 ) );
				if ( count( $parts ) >= 2 ) {
					$parsed[] = array( 'q' => $parts[0], 'a' => $parts[1] );
				}
			}
			if ( $parsed ) $defaults[ $slug ] = $parsed;
		}
	}
	return $defaults;
}


/**
 * 슬러그 → Customizer key 매핑.
 */
function moondental_service_slug_to_key( $slug ) {
	$map = array(
		'임플란트-센터'   => 'implant',
		'투명교정-센터'   => 'ortho',
		'자연치아-살리기' => 'endo',
		'턱관절-클리닉'   => 'tmj',
		'사랑니-발치'     => 'wisdom',
		'심미치료'        => 'aesthetic',
		'소아치과'        => 'pediatric',
	);
	return $map[ $slug ] ?? null;
}

/**
 * 진료 영역별 환자 고민 / 솔루션 6쌍 — bdbddc.com 모델 참고.
 * Customizer 값이 입력되어 있으면 우선, 없으면 아래 하드코딩 기본값.
 */
function moondental_service_pain_points() {
	$result = array(
		'임플란트-센터' => array(
			array( 'concern' => '수술이 무서워요',                       'solution' => '디지털 가이드 수술 + PCA 자가진통조절기로 통증과 불안을 최소화합니다.' ),
			array( 'concern' => '뼈가 부족하다고 들었어요',              'solution' => 'CBCT 정밀 분석 후 GBR·상악동 거상술 등 환자 골 상태에 맞춘 옵션을 제시합니다.' ),
			array( 'concern' => '비용이 부담돼요',                       'solution' => '사전 견적서 제공 + 카드 무이자 할부 가능. 만 65세 이상은 건강보험 적용도 안내드립니다.' ),
			array( 'concern' => '전신질환이 있어 거절당했어요',           'solution' => '혈압·당검사·심전도·산소포화도 상시 측정으로 안전하게 진행합니다.' ),
			array( 'concern' => '다른 치과 임플란트가 흔들려요',          'solution' => '리무버 키트로 안전 제거 후 골손실을 최소화하며 재식립합니다.' ),
			array( 'concern' => '오래 사용할 수 있나요?',                'solution' => '정기 검진 + 평생 A/S 시스템으로 10~20년 이상 유지를 목표합니다.' ),
		),
		'투명교정-센터' => array(
			array( 'concern' => '성인인데 교정이 가능한가요?',           'solution' => '잇몸·치주가 건강하면 50~60대도 충분히 가능합니다.' ),
			array( 'concern' => '발치 없이 안 되나요?',                 'solution' => '정밀 진단 후 비발치를 우선 검토합니다. 부분교정만 필요한 경우도 많습니다.' ),
			array( 'concern' => '비용·기간이 길어 부담돼요',             'solution' => '난이도별 옵션과 부분교정(150만 원~)도 안내드리며, 카드 무이자 할부 지원합니다.' ),
			array( 'concern' => '투명교정 vs 일반교정 뭐가 좋나요?',     'solution' => '라이프스타일·난이도에 맞춰 교정 전문의가 최적안을 추천드립니다.' ),
			array( 'concern' => '교정 후 다시 돌아간다고 들었어요',      'solution' => '유지장치(retainer) 평생 야간 착용으로 안정 유지가 가능합니다.' ),
			array( 'concern' => '통증이 심한가요?',                     'solution' => '디지털 정밀 진단으로 와이어 힘을 정확히 조절해 통증을 최소화합니다.' ),
		),
		'자연치아-살리기' => array(
			array( 'concern' => '신경치료 vs 발치 후 임플란트?',         'solution' => '자연치를 살릴 수 있다면 보존이 우선입니다. 비용·신체 부담이 모두 적습니다.' ),
			array( 'concern' => '재근관치료 가능한가요?',                'solution' => '현미경 정밀 진료로 약 70~80% 성공률을 보입니다. 보존과 전문의가 직접 진료합니다.' ),
			array( 'concern' => '통증이 심한가요?',                      'solution' => '국소마취 + 진통제로 충분히 조절 가능합니다. 시술 중 통증은 거의 없습니다.' ),
			array( 'concern' => '치아 색이 변했어요',                    'solution' => '워킹 블리치(내부 미백)로 신경치료 치아도 색상 개선이 가능합니다.' ),
			array( 'concern' => '신경치료 후 꼭 씌워야 하나요?',         'solution' => '신경치료 치아는 약해지므로 크라운으로 잘 보호해야 평생 사용할 수 있습니다.' ),
			array( 'concern' => '다른 치과 신경치료가 실패했어요',       'solution' => '보존과 전문의의 재근관치료 + 치근단 수술로 단계적 접근이 가능합니다.' ),
		),
		'턱관절-클리닉' => array(
			array( 'concern' => '턱에서 소리만 나고 통증은 없는데?',     'solution' => '디스크 변위 가능성이 있어 정기 검진을 권합니다. 조기 진단이 중요합니다.' ),
			array( 'concern' => '보톡스로 정말 좋아지나요?',             'solution' => '교근 과긴장이 통증 원인이면 효과적입니다. 단, 습관 교정·스플린트와 병행을 권합니다.' ),
			array( 'concern' => '두통이 턱관절 때문일까요?',             'solution' => '측두근·교근 과긴장이 두통으로 이어지는 경우가 많습니다. 만성 두통 환자 다수가 호전됩니다.' ),
			array( 'concern' => '스플린트는 평생 착용해야 하나요?',      'solution' => '대개 3~6개월 야간 착용 후 증상 호전 시 사용 빈도를 줄입니다.' ),
			array( 'concern' => '수술까지 가는 경우는?',                'solution' => '극히 드뭅니다. 대부분 비수술 보존 치료로 호전됩니다.' ),
			array( 'concern' => '임플란트 후 턱관절이 아파요',           'solution' => '교합 변화가 원인일 수 있습니다. 보철 후에도 턱관절까지 함께 관리합니다.' ),
		),
		'사랑니-발치' => array(
			array( 'concern' => '신경 마비가 걱정돼요',                  'solution' => '3D CT 사전 분석으로 신경 위치를 파악해 위험을 최소화합니다. 위험 시 부분 발치(Coronectomy) 옵션도 있습니다.' ),
			array( 'concern' => '임신 중 발치가 가능한가요?',            'solution' => '가급적 출산 후를 권하지만, 급성 염증 시 임신 중기에 최소 침습으로 진행 가능합니다.' ),
			array( 'concern' => '발치 후 통증이 얼마나 가나요?',         'solution' => '일반은 2~3일, 매복 사랑니는 3~5일 통증 피크. 1주일 후 거의 사라집니다.' ),
			array( 'concern' => '4개를 한 번에 빼도 되나요?',            'solution' => '건강한 성인은 가능하나, 회복 부담 고려해 좌/우 또는 위/아래 나눠 진행을 권합니다.' ),
			array( 'concern' => '발치 후 운동은 언제부터?',              'solution' => '가벼운 산책은 다음 날부터, 격렬한 운동은 1주일 후부터 권장합니다.' ),
			array( 'concern' => '수면 마취로 받고 싶어요',               'solution' => '국소마취 + 물방울 레이저 + PCA로 충분히 편안하게 진행 가능합니다.' ),
		),
		'심미치료' => array(
			array( 'concern' => '라미네이트 vs 미백 뭐가 좋나요?',       'solution' => '단순 변색은 미백 우선, 형태·배열까지 함께 개선하려면 라미네이트가 적합합니다.' ),
			array( 'concern' => '미백 후 다시 어두워지나요?',            'solution' => '1~2년 후 자연스러운 재착색이 있습니다. 보강 미백(touch-up)으로 유지합니다.' ),
			array( 'concern' => '신경치료 치아도 미백 가능한가요?',      'solution' => '워킹 블리치라는 내부 미백법으로 가능합니다. 상담 시 적합 여부를 확인합니다.' ),
			array( 'concern' => '라미네이트로 치아가 약해지지 않나요?',  'solution' => '최소 삭제 라미네이트로 자연치를 최대한 보존합니다. 충분한 상담 후 진행합니다.' ),
			array( 'concern' => '잇몸 라인이 신경 쓰여요',                'solution' => '거미스마일·잇몸 미백 등 잇몸 라인 개선 시술이 가능합니다.' ),
			array( 'concern' => '비용이 어느 정도인가요?',                'solution' => '라미네이트 치아당 66만원~, 전문가 미백 33만원~. 정확한 견적은 진단 후 안내드립니다.' ),
		),
		'소아치과' => array(
			array( 'concern' => '아이가 치과를 무서워해요',              'solution' => '거부 시 절대 강제 진료하지 않습니다. 첫 방문은 진료실 구경부터 시작할 수 있습니다.' ),
			array( 'concern' => '몇 살부터 치과 진료가 필요한가요?',     'solution' => '첫 어금니가 나오는 만 1~2세부터 첫 방문을 권합니다. 이후 6개월 단위 정기 검진.' ),
			array( 'concern' => '실란트(홈 메우기)는 꼭 필요한가요?',    'solution' => '필수는 아니나 만 6~8세 영구치 어금니에 50% 이상 충치 예방 효과. 건강보험도 적용됩니다.' ),
			array( 'concern' => '소아 교정은 언제 시작해야 하나요?',     'solution' => '골격성 부정교합은 만 7~10세가 골든타임. 일반 부정교합은 영구치 다 나온 후(만 12세+).' ),
			array( 'concern' => '아이 충치 치료는 어떻게 하나요?',       'solution' => '유치는 글래스아이오노머·콤포지트로 짧은 시간에 치료. 아이가 협조 어려우면 분할 진행합니다.' ),
			array( 'concern' => '건강보험은 어디까지 되나요?',           'solution' => '만 12세 이하 영구치 레진 충전, 실란트, 발치, X-ray 등이 적용됩니다.' ),
		),
	);

	/* Customizer 값으로 덮어쓰기 — 각 슬러그별 "고민 | 솔루션" 파이프 텍스트 파싱 */
	if ( function_exists( 'md_content' ) && function_exists( 'moondental_service_slug_to_key' ) ) {
		foreach ( $result as $slug => $_ ) {
			$key = moondental_service_slug_to_key( $slug );
			if ( ! $key ) continue;
			$text = md_content( "service_{$key}_pains", '' );
			if ( ! $text ) continue;
			$parsed = array();
			foreach ( preg_split( "/\r\n|\r|\n/", $text ) as $line ) {
				$line = trim( $line );
				if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
				$parts = array_map( 'trim', explode( '|', $line ) );
				if ( count( $parts ) >= 2 ) {
					$parsed[] = array( 'concern' => $parts[0], 'solution' => $parts[1] );
				}
			}
			if ( $parsed ) $result[ $slug ] = $parsed;
		}
	}
	return $result;
}

/**
 * 진료 영역별 "이런 분께 추천합니다" 5~6개.
 * Customizer 값이 있으면 우선, 없으면 기본값.
 */
function moondental_service_ideal_candidates() {
	$result = array(
		'임플란트-센터' => array(
			'치아가 빠지거나 발치 예정인 분',
			'틀니가 불편해 식사가 어려운 분',
			'인접 치아를 갈지 않고 복원하고 싶은 분',
			'잇몸·전신질환이 있어 다른 곳에서 거절당한 분',
			'만 65세 이상 건강보험 임플란트 대상자',
		),
		'투명교정-센터' => array(
			'치아 배열이 고르지 않은 분',
			'외모 콤플렉스가 있는 분',
			'발음·저작 기능에 영향이 있는 부정교합',
			'골격성 부정교합 의심 청소년 (만 7~10세)',
			'타치과에서 발치 교정을 권유받았으나 비발치 검토를 원하는 분',
		),
		'자연치아-살리기' => array(
			'신경치료 후 다시 통증이 있는 분',
			'충치가 깊어 발치를 권유받은 분',
			'자연치를 최대한 살리고 싶은 분',
			'치아 변색·외상이 있는 분',
			'재근관치료가 필요한 분',
		),
		'턱관절-클리닉' => array(
			'턱관절에서 소리가 나거나 통증이 있는 분',
			'입을 크게 벌리기 어려운 분',
			'이갈이·이악물기 습관이 있는 분',
			'만성 두통·이명이 있는 분',
			'임플란트·보철 치료 후 교합이 불편한 분',
		),
		'사랑니-발치' => array(
			'매복 사랑니로 통증·잇몸 부음이 있는 분',
			'사랑니 주변 충치·잇몸염이 반복되는 분',
			'교정 치료를 앞두고 사랑니 발치가 필요한 분',
			'타치과에서 발치 위험으로 거절당한 매복 사랑니',
			'급성 염증이 있어 빠른 처치가 필요한 분',
		),
		'심미치료' => array(
			'앞니 변색·결손으로 자신감이 떨어진 분',
			'미백으로 안 되는 심한 변색이 있는 분',
			'잇몸이 많이 보이는 거미스마일',
			'특별한 행사 전 단기간 심미 개선을 원하는 분',
			'전치부 작은 결손·반점을 자연스럽게 보완하고 싶은 분',
		),
		'소아치과' => array(
			'만 1~2세 첫 치과 방문이 필요한 아이',
			'유치 충치가 생긴 아이',
			'영구치 어금니가 나오는 만 6~8세 (실란트 적기)',
			'골격성 부정교합 의심 청소년 (1차 교정 골든타임)',
			'치과를 무서워해 진료가 어려운 아이',
		),
	);

	/* Customizer 값으로 덮어쓰기 — 한 줄당 1 항목 */
	if ( function_exists( 'md_content' ) && function_exists( 'moondental_service_slug_to_key' ) ) {
		foreach ( $result as $slug => $_ ) {
			$key = moondental_service_slug_to_key( $slug );
			if ( ! $key ) continue;
			$text = md_content( "service_{$key}_candidates", '' );
			if ( ! $text ) continue;
			$parsed = array();
			foreach ( preg_split( "/\r\n|\r|\n/", $text ) as $line ) {
				$line = trim( $line );
				if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
				$parsed[] = $line;
			}
			if ( $parsed ) $result[ $slug ] = $parsed;
		}
	}
	return $result;
}

/**
 * 문치과병원의 강점 — 의료기관 종별·시설·운영 측면의 객관적 특징.
 *   비교 어휘 없이 우리 병원의 사실만 명시.
 *
 * @return array [ ['label'=>'', 'value'=>'…', 'icon'=>'…'], … ]
 */
function moondental_clinic_comparison() {
	$defaults = array(
		1 => array( 'label' => '의료기관 종별',     'value' => '치과병원 (병원급)',                            'icon' => '🏥' ),
		2 => array( 'label' => '의료진 협진',       'value' => '분야별 전문 의료진 협진',                       'icon' => '👨‍⚕️' ),
		3 => array( 'label' => '전문 진료과 6과',   'value' => '보철·교정·보존·치주·소아·외과',                'icon' => '🦷' ),
		4 => array( 'label' => '통합 진료센터',     'value' => '9·10·11·13F 4개 층 운영',                      'icon' => '🏢' ),
		5 => array( 'label' => '디지털 진단 장비',  'value' => 'CBCT · 디지털 가이드 · 구강스캐너',            'icon' => '🔬' ),
		6 => array( 'label' => '자체 보철 제작',    'value' => '한아 임플란트 보철연구소 · 원내 기공실 (13F)', 'icon' => '⚙️' ),
		7 => array( 'label' => '전신질환 대응',     'value' => '혈압 · 당검사 · 심전도 · 산소포화도 상시',     'icon' => '❤️' ),
		8 => array( 'label' => '평일 야간진료',     'value' => '평일 ~ 20:30 운영',                             'icon' => '🌙' ),
		9 => array( 'label' => '임상 경력',         'value' => '1995년부터 30여년 한자리 진료',                'icon' => '⏳' ),
	);

	if ( ! function_exists( 'md_content' ) ) return array_values( $defaults );

	$result = array();
	for ( $i = 1; $i <= 9; $i++ ) {
		$d = $defaults[ $i ];
		$label = md_content( "compare_{$i}_label", $d['label'] );
		if ( ! $label ) continue; // 라벨 비우면 항목 숨김
		$result[] = array(
			'label' => $label,
			'value' => md_content( "compare_{$i}_hospital", $d['value'] ),
			'icon'  => md_content( "compare_{$i}_icon",     $d['icon'] ),
		);
	}
	return $result;
}


/**
 * 통합 FAQ 페이지(/faq/)용 — 위 데이터를 카테고리별로 묶어서 반환.
 * 기존 코드와의 호환을 위해 함수명 유지.
 */
function moondental_get_faqs() {
	return array(
		'예약·내원' => array(
			array( 'q' => '예약 없이 방문해도 진료가 가능한가요?',
				   'a' => '가능하지만 대기 시간이 발생할 수 있습니다. 가급적 전화(<a href="tel:0415632875">041-563-2875</a>) 또는 카카오톡으로 사전 예약 후 방문해주세요.' ),
			array( 'q' => '초진 시 어떤 것을 준비해야 하나요?',
				   'a' => '신분증(또는 건강보험증), 복용 중인 약 목록, 타원 X-ray 파일(USB·이메일) 등을 가져오시면 진단이 더 빠르고 정확합니다.' ),
			array( 'q' => '주차가 가능한가요?',
				   'a' => '본원 지하 기계식 주차장 무료 이용 가능. SUV는 인근 신부 제5공영주차장(동남구 먹거리1길 10)에 주차 후 방문하시면 무료 주차 등록을 도와드립니다.' ),
			array( 'q' => '진료 시간이 어떻게 되나요?',
				   'a' => '평일(월·화·수·금) 9:00~20:30, 목요일 9:00~18:30, 토요일 9:00~14:00, 일요일·공휴일 휴진. 평일은 점심시간 없이 진료합니다.' ),
		),
		'임플란트' => array(
			array( 'q' => '임플란트 수술 후 통증이 많이 있나요?',
				   'a' => '국소마취 하에 진행되며 수술 자체는 거의 통증이 없습니다. PCA 자가진통조절기와 물방울 레이저로 통증을 최대한 줄입니다. 1~2일간의 둔한 통증은 진통제로 충분히 조절됩니다.' ),
			array( 'q' => '임플란트는 얼마나 오래 사용할 수 있나요?',
				   'a' => '관리에 따라 10~20년 이상 유지가 가능합니다. 1년 4회 정기 검진과 매일 구강위생 관리가 핵심이며, 평생 A/S 시스템을 운영합니다.' ),
			array( 'q' => '뼈가 부족하다고 들었는데 임플란트가 가능할까요?',
				   'a' => 'CT 정밀 분석 후 골이식·상악동 거상술·GBR(골유도재생술) 등 환자 골 상태에 맞춘 다양한 옵션이 있습니다. 가능한 한 골이식을 최소화하는 방향으로 계획합니다.' ),
			array( 'q' => '당일 임플란트는 누구나 가능한가요?',
				   'a' => '당일 임플란트는 발치+뼈이식+식립을 하루에 진행하는 시술입니다. 다만 뼈 상태·잇몸 건강·전신 상태에 따라 가능 여부가 결정됩니다. 사전 정밀 진단으로 안전을 확인 후 진행합니다.' ),
			array( 'q' => '건강보험 임플란트 혜택이 있나요?',
				   'a' => '만 65세 이상 건강보험 가입자는 평생 2개까지 본인부담 30%로 적용 가능합니다. 부분 무치악이 대상이며, 잔존치 하나만 있어도 가능합니다.' ),
		),
		'교정' => array(
			array( 'q' => '성인도 교정이 가능한가요?',
				   'a' => '잇몸·치주 상태가 건강하다면 50~60대도 교정이 가능합니다. 다만 청소년보다 치료 기간이 다소 길어질 수 있습니다.' ),
			array( 'q' => '투명교정과 일반 교정 중 어느 것이 좋나요?',
				   'a' => '케이스 난이도·라이프스타일·예산에 따라 다릅니다. 단순한 케이스는 투명교정이 효율적이고, 복잡한 케이스는 일반 교정이 더 정밀합니다. 정밀 진단 후 추천드립니다.' ),
			array( 'q' => '교정 중 통증은 얼마나 심한가요?',
				   'a' => '장치 부착 직후·조정 후 2~3일간 둔한 통증이 있을 수 있습니다. 진통제로 조절 가능하며 1주일 이내 적응됩니다.' ),
			array( 'q' => '교정 후 다시 돌아간다고 들었는데?',
				   'a' => '유지장치(retainer)를 권장대로 착용하면 거의 재발하지 않습니다. 처음 1년은 24시간, 이후 야간 착용을 평생 권장합니다.' ),
		),
		'심미·미백' => array(
			array( 'q' => '미백 효과가 영구적인가요?',
				   'a' => '아닙니다. 음식·음주·흡연 습관에 따라 6개월~2년 후 자연스럽게 다시 착색됩니다. 가정 미백 트레이로 주기적으로 유지하면 효과가 길어집니다.' ),
			array( 'q' => '라미네이트 시술하면 치아가 약해지지 않나요?',
				   'a' => '치아 삭제를 최소화하는 라미네이트는 자연치를 최대한 보존합니다. 다만 한 번 시술하면 되돌리기 어려우므로 충분한 상담 후 진행합니다.' ),
			array( 'q' => '신경치료한 치아도 미백 가능한가요?',
				   'a' => '일반 미백은 효과가 떨어지지만, 워킹 블리치(walking bleach)라는 내부 미백법으로 개선 가능합니다. 상담 시 적합 여부 확인 후 진행합니다.' ),
		),
		'턱관절·기타' => array(
			array( 'q' => '턱에서 소리만 나고 통증은 없는데 진료가 필요한가요?',
				   'a' => '디스크 변위 가능성이 있어 정기 검진을 권합니다. 진행되면 통증·개구 제한으로 발전할 수 있어 조기 진단이 중요합니다.' ),
			array( 'q' => '사랑니는 무조건 빼야 하나요?',
				   'a' => '아닙니다. 똑바로 잘 자리잡고 양치가 잘 되며 인접치에 영향을 주지 않으면 두고 봐도 됩니다. 매복·통증·인접치 손상이 있을 때만 발치를 권합니다.' ),
			array( 'q' => '아이는 몇 살부터 치과 진료를 받아야 하나요?',
				   'a' => '첫 어금니가 나오는 만 1~2세부터 치과 첫 방문을 권합니다. 검진은 6개월 단위 정기 권장. 만 12세 이하는 영구치 광중합 레진 충전이 건강보험 적용됩니다.' ),
			array( 'q' => '전신질환(고혈압·당뇨·심장)이 있어도 진료 가능한가요?',
				   'a' => '네, 안심하고 진료받으실 수 있습니다. 혈압기·당검사·심전도·산소포화도 측정기를 상시 보유하며, 복용 약물(혈전용해제·골다공증 약 등)을 사전에 체크 후 안전하게 진행합니다.' ),
		),
		'비용·결제' => array(
			array( 'q' => '비용은 미리 알 수 있나요?',
				   'a' => '비급여 진료(임플란트·교정·심미)는 CT·X-ray 진단 후 정확한 견적을 산정합니다. 초진 상담 시 옵션별 비용·기간을 모두 안내드립니다.' ),
			array( 'q' => '카드 결제, 할부 가능한가요?',
				   'a' => '모든 신용카드 결제 가능하며, 임플란트·교정 등 고액 진료는 카드사 무이자 할부도 가능합니다.' ),
			array( 'q' => '실손보험으로 보장이 되나요?',
				   'a' => '치과는 대부분 실손보험 대상이 아닙니다. 다만 사고로 인한 외상 치료·턱관절 일부는 보장 가능할 수 있으니 사전에 가입 보험사에 확인하세요.' ),
		),
	);
}
