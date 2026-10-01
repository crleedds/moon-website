<?php
/**
 * v4.13 · 지역별 「치과 추천, 무엇을 보고 고를까」 안내서 공통 조각
 *
 *  도시마다 같은 기준(진료과 · 장비 · 비용 고지 · 사후관리 · 거리)을 쓰되,
 *  거리 · 교통 · 그 거리에서 현실적인 치료 범위 · FAQ 는 도시별 데이터 파일에서 따로 적는다.
 *  (도시 이름만 바꾼 복제 페이지가 되지 않도록 공통 조각은 짧게, 도시 조각은 길게)
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** 이동 시간대별 — 그 거리에서 현실적인 치료 범위 */
function md_guide_tier( $min ) {
	$min = (int) $min;
	if ( $min <= 30 ) return array( 'label' => '가까움', 'text' => '임플란트 · 교정처럼 여러 달 다니는 치료까지 무리가 없습니다.' );
	if ( $min <= 50 ) return array( 'label' => '다닐 만함', 'text' => '임플란트 · 매복 사랑니 · 턱관절처럼 횟수가 정해진 치료에 알맞습니다. 교정은 월 1회 통원이 가능하면 고려할 수 있습니다.' );
	if ( $min <= 70 ) return array( 'label' => '선택적', 'text' => '뼈이식을 포함한 임플란트, 어려운 매복 사랑니, 턱관절처럼 협진이 필요한 치료에 한해 고려하세요. 정기검진 · 작은 충치는 가까운 곳이 낫습니다.' );
	return array( 'label' => '멀다', 'text' => '현지에서 해결이 어려운 경우(여러 과 협진, 전신질환 환자 임플란트 등)에만 권합니다. 1~2회로 끝나는 진단 · 수술 위주로 계획하세요.' );
}

/** 공통 · 치과 고르는 기준 5가지 (짧게) */
function md_guide_block_criteria( $city ) {
	return '<ol>
		<li><strong>내 증상을 보는 진료과가 있는가</strong> · 임플란트는 구강외과 · 보철과, 교정은 교정과, 신경치료는 보존과, 잇몸은 치주과입니다. 여러 과가 함께 봐야 하는 치료는 협진이 되는 곳이 유리합니다.</li>
		<li><strong>진단 장비</strong> · 임플란트 · 매복 사랑니 · 교정은 3D CT(CBCT)로 뼈와 신경을 보고 계획해야 합니다.</li>
		<li><strong>비급여 비용을 미리 알려 주는가</strong> · 홈페이지 고지와 상담 때 금액이 같은지, 총액 견적서를 주는지 보세요.</li>
		<li><strong>사후관리</strong> · 임플란트 보증, 교정 유지장치 관리, 정기검진 안내가 문서로 있는지 확인하세요.</li>
		<li><strong>거리와 시간</strong> · ' . esc_html( $city ) . '에서 해결되는 치료는 가까운 곳이 낫고, 왕복 시간과 치료 횟수를 곱해 현실적인지 따져 보세요.</li>
	</ol>';
}

/** 공통 · 문치과병원 소개 (사실만) */
function md_guide_block_moon( $city, $min ) {
	$dist = (int) $min > 0 ? ' ' . esc_html( $city ) . '에서 차로 ' . (int) $min . '분 안팎입니다.' : '';
	return '<p>한아의료재단 문치과병원은 <strong>1995년 천안 만남로에서 개원</strong>한 <strong>병원급 치과병원</strong>입니다.' . $dist . '</p>
	<ul>
		<li><strong>진료과</strong> · 임플란트센터 · 교정센터 · 스마일디자인센터 · 자연치아보존센터 4개 전문센터와 구강외과 · 턱관절 · 소아치과 · 치주과 · 보철과 · 보존과가 한 건물에서 협진합니다.</li>
		<li><strong>장비</strong> · CBCT(3D CT) · 네비게이션 임플란트 · 구강 스캐너.</li>
		<li><strong>기공실</strong> · 원내 기공실을 직접 운영합니다.</li>
		<li><strong>통역</strong> · 영어 · 러시아어 · 몽골어 · 베트남어 · 중국어.</li>
		<li><strong>위치</strong> · 충청남도 천안시 동남구 만남로 52 문타워 9~13층(천안고속버스터미널 옆) · 전화 041-563-2875 · <a href="/상담예약/">상담예약</a></li>
	</ul>
	<div class="md-guide-callout md-guide-callout--info">
		<strong>이 페이지는 광고가 아닙니다.</strong><br>
		위 기준은 어느 치과에나 똑같이 적용됩니다. 스케일링 · 작은 충치처럼 가까운 곳에서 해결되는 치료는 ' . esc_html( $city ) . ' 시내 치과가 더 편할 수 있습니다.
	</div>';
}

/** 공통 · 거리·교통 표 (regions.php 데이터로) */
function md_guide_block_route( $slug ) {
	if ( ! function_exists( 'moondental_get_region_by_slug' ) ) return '';
	$r = moondental_get_region_by_slug( $slug );
	if ( ! $r ) return '';
	$rows = array();
	if ( ! empty( $r['highway'] ) ) $rows[] = array( '🚗 자동차', (int) $r['duration_min'] . '분 · ' . $r['highway'] );
	if ( ! empty( $r['ktx'] ) )     $rows[] = array( '🚆 기차', $r['ktx'] );
	if ( ! empty( $r['bus'] ) )     $rows[] = array( '🚌 버스', $r['bus'] );
	$html = '<div class="md-guide-tablewrap"><table class="md-guide-table"><tbody>';
	foreach ( $rows as $row ) $html .= '<tr><th style="white-space:nowrap">' . esc_html( $row[0] ) . '</th><td>' . esc_html( $row[1] ) . '</td></tr>';
	$html .= '</tbody></table></div>';
	$html .= '<p>도착하면 천안고속버스터미널 옆 문타워입니다. 병원 지하 기계식 주차장은 무료이지만 기계식이라 SUV · 대형차는 들어가지 못합니다. SUV · 대형차는 걸어서 5분 거리의 신부 제5공영주차장(동남구 먹거리1길 10)에 세우고, 접수처에서 주차 도장 · 주차권을 받으면 무료입니다. 자세한 안내는 <a href="/오시는-길/' . esc_attr( $slug ) . '/">' . esc_html( $r['name'] ) . '에서 오시는 길</a>에 있습니다.</p>';
	return $html;
}

/** 통합 안내서용 · 모든 지역 표 (천안 · 아산 · 동 단위 제외) */
function md_guide_block_region_table() {
	if ( ! function_exists( 'moondental_get_regions_by_province' ) ) return '';
	$own = array( 'cheonan', 'asan', 'sejong', 'pyeongtaek', 'yesan', 'anseong', 'gongju' );
	$html = '';
	foreach ( moondental_get_regions_by_province() as $prov => $list ) {
		if ( '천안·아산 시내' === $prov ) continue;
		$html .= '<h3>' . esc_html( $prov ) . '</h3><div class="md-guide-tablewrap"><table class="md-guide-table">
			<thead><tr><th>도시</th><th>차로</th><th>대중교통</th><th>이 거리에서 권하는 범위</th></tr></thead><tbody>';
		foreach ( $list as $r ) {
			$tier = md_guide_tier( $r['duration_min'] );
			$pub  = trim( ( $r['ktx'] ?? '' ) . ( ! empty( $r['ktx'] ) && ! empty( $r['bus'] ) ? ' · ' : '' ) . ( $r['bus'] ?? '' ) );
			$name = in_array( $r['slug'], $own, true )
				? '<a href="/guide/' . esc_attr( $r['slug'] ) . '/">' . esc_html( $r['name'] ) . '</a>'
				: '<a href="/오시는-길/' . esc_attr( $r['slug'] ) . '/">' . esc_html( $r['name'] ) . '</a>';
			$html .= '<tr><td>' . $name . '</td><td style="white-space:nowrap">' . (int) $r['duration_min'] . '분</td><td>' . esc_html( $pub ) . '</td><td><strong>' . esc_html( $tier['label'] ) . '</strong> · ' . esc_html( $tier['text'] ) . '</td></tr>';
		}
		$html .= '</tbody></table></div>';
	}
	return $html;
}

/** 도시 안내서 묶기 — 데이터 파일은 도시 고유 조각만 넘긴다 */
function md_guide_city( $args ) {
	$slug = $args['slug'];
	$city = $args['city'];
	$r    = function_exists( 'moondental_get_region_by_slug' ) ? moondental_get_region_by_slug( $slug ) : null;
	$min  = $r ? (int) $r['duration_min'] : (int) ( $args['min'] ?? 40 );
	$tier = md_guide_tier( $min );

	$sections = array(
		array( 'id' => 'route',    'title' => '01 · ' . $city . '에서 천안 문치과병원까지', 'body' => '<p>' . esc_html( $args['route_intro'] ) . '</p>' . md_guide_block_route( $slug ) ),
		array( 'id' => 'where',    'title' => '02 · ' . $city . '에서 할 치료 · 천안까지 갈 치료', 'body' => '<p><strong>' . esc_html( $city ) . ' → 천안 ' . $min . '분 · ' . esc_html( $tier['label'] ) . '.</strong> ' . esc_html( $tier['text'] ) . '</p>' . $args['where_body'] ),
		array( 'id' => 'criteria', 'title' => '03 · 어디서든 통하는 치과 고르는 기준 5가지', 'body' => md_guide_block_criteria( $city ) ),
		array( 'id' => 'moon',     'title' => '04 · 문치과병원은 어떤 곳인가', 'body' => md_guide_block_moon( $city, $min ) ),
		array( 'id' => 'faq',      'title' => '05 · 자주 묻는 질문', 'body' => '<div class="md-guide-faq" itemscope itemtype="https://schema.org/FAQPage">' . md_guide_faq_html( $args['faq'] ) . '</div>' ),
	);
	$toc = array();
	foreach ( $sections as $s ) $toc[] = array( 'id' => $s['id'], 'label' => preg_replace( '/^\d+ · /', '', $s['title'] ) );

	return array(
		'slug'      => $slug,
		'code'      => 'GUIDE',
		'icon'      => $args['icon'] ?? '🚗',
		'eyebrow'   => $city . ' 치과 선택 안내',
		'center'    => '',
		'title'     => $city . ' 치과 추천, 무엇을 보고 고를까',
		'subtitle'  => $args['subtitle'],
		'reading'   => '약 6분',
		'updated'   => '2026.10',
		'tags'      => array( $city . '→천안 ' . $min . '분', $tier['label'], 'FAQ ' . count( $args['faq'] ) ),
		'summary'   => $args['summary'],
		'cta_head'  => '치과',
		'cta_page'  => '/오시는-길/' . $slug . '/',
		'cta_label' => $city . '에서 오시는 길',
		'related'   => array(
			array( 'label' => '천안 치과 추천, 무엇을 보고 고를까', 'href' => '/guide/cheonan/', 'icon' => '📍' ),
			array( 'label' => '충청 · 경기 남부에서 천안 치과 다니기', 'href' => '/guide/region/', 'icon' => '🗺️' ),
			array( 'label' => '임플란트 종합안내서', 'href' => '/guide/implant/', 'icon' => '🦷' ),
		),
		'toc'       => $toc,
		'sections'  => $sections,
	);
}

/** 지역 데이터 → 쓸 안내서 슬러그 */
function md_guide_for_region( $region ) {
	if ( ! is_array( $region ) || empty( $region['slug'] ) ) return '';
	$own = array( 'cheonan', 'asan', 'sejong', 'pyeongtaek', 'yesan', 'anseong', 'gongju' );
	if ( in_array( $region['slug'], $own, true ) ) return $region['slug'];
	if ( ( $region['province'] ?? '' ) === '천안·아산 시내' ) {
		return ( false !== mb_strpos( (string) ( $region['name_long'] ?? $region['name'] ), '아산' ) ) ? 'asan' : 'cheonan';
	}
	return 'region';
}
