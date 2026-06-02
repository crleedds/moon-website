<?php
/**
 * 지역별 오시는 길 데이터 (28개 지역).
 *  /오시는-길/{slug}/ 개별 지역 랜딩 페이지에서 사용.
 *
 *  SEO 핵심: 각 지역명을 페이지 타이틀·설명·H1·본문에 자연스럽게 배치
 *  → 환자가 "{지역} 임플란트", "{지역} 치과" 검색 시 노출.
 *
 *  거리·시간은 천안 만남로 문치과병원 기준 대략적 값.
 *  주요 진료 항목·교통편 정보를 지역 특성에 맞춰 자연 배치.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 28개 지역 데이터 반환.
 *
 * @return array<string, array>
 */
function moondental_get_regions() {
	return array(

		/* ───────── 충청남도 (15) ───────── */
		'asan' => array(
			'slug' => 'asan', 'name' => '아산', 'name_long' => '아산시', 'province' => '충남',
			'distance_km' => 15, 'duration_min' => 25, 'highway' => '국도 21호선·39호선',
			'ktx' => '천안아산역에서 천안종합터미널까지 시내버스 15분',
			'bus' => '아산시외버스터미널 → 천안종합버스터미널 30분 간격 운행',
			'note' => '아산 시민에게 가장 가까운 천안 종합 치과병원',
		),
		'yesan' => array(
			'slug' => 'yesan', 'name' => '예산', 'name_long' => '예산군', 'province' => '충남',
			'distance_km' => 35, 'duration_min' => 35, 'highway' => '국도 21호선 (천안-예산 직결)',
			'ktx' => '예산역에서 천안역까지 KTX·무궁화호',
			'bus' => '예산시외버스터미널 → 천안 1시간 간격',
			'note' => '예산에서 천안까지 35분 — 종합 진료가 필요한 환자분께 추천',
		),
		'hongseong' => array(
			'slug' => 'hongseong', 'name' => '홍성', 'name_long' => '홍성군', 'province' => '충남',
			'distance_km' => 55, 'duration_min' => 55, 'highway' => '서해안고속도로 → 평택파주고속도로',
			'ktx' => '홍성역에서 천안역까지 KTX 25분',
			'bus' => '홍성시외버스터미널 → 천안 1시간 30분',
			'note' => '홍성 환자분들이 임플란트·교정 진료받으러 자주 오시는 곳',
		),
		'boryeong' => array(
			'slug' => 'boryeong', 'name' => '보령', 'name_long' => '보령시', 'province' => '충남',
			'distance_km' => 80, 'duration_min' => 70, 'highway' => '서해안고속도로 → 천안-논산고속도로',
			'ktx' => '대천역에서 천안역까지 무궁화호 1시간',
			'bus' => '보령시외버스터미널 → 천안 1시간 30분 간격',
			'note' => '보령에서 70분 — 종합 진료가 어려운 분께 천안 통합 진료센터 추천',
		),
		'seosan' => array(
			'slug' => 'seosan', 'name' => '서산', 'name_long' => '서산시', 'province' => '충남',
			'distance_km' => 65, 'duration_min' => 60, 'highway' => '서해안고속도로 (서산IC → 천안IC)',
			'ktx' => '천안역까지 직행 KTX 없음 — 시외버스 권장',
			'bus' => '서산공용터미널 → 천안종합터미널 1시간 간격',
			'note' => '서산에서 60분 — 디지털 임플란트 가이드 진료받으러 오시는 환자 많음',
		),
		'dangjin' => array(
			'slug' => 'dangjin', 'name' => '당진', 'name_long' => '당진시', 'province' => '충남',
			'distance_km' => 50, 'duration_min' => 55, 'highway' => '서해안고속도로 (당진IC → 천안IC)',
			'ktx' => '직행 KTX 없음 — 시외버스 권장',
			'bus' => '당진공용터미널 → 천안종합터미널 1시간',
			'note' => '당진에서 55분 — 통합 진료 원하시는 분들이 자주 찾는 천안 치과',
		),
		'gongju' => array(
			'slug' => 'gongju', 'name' => '공주', 'name_long' => '공주시', 'province' => '충남',
			'distance_km' => 45, 'duration_min' => 45, 'highway' => '천안-논산고속도로 (공주IC → 천안IC)',
			'ktx' => '공주역에서 천안아산역까지 KTX 20분',
			'bus' => '공주종합버스터미널 → 천안 1시간 간격',
			'note' => '공주에서 45분 — 임플란트·교정 종합 진료 받으러 추천',
		),
		'buyeo' => array(
			'slug' => 'buyeo', 'name' => '부여', 'name_long' => '부여군', 'province' => '충남',
			'distance_km' => 75, 'duration_min' => 70, 'highway' => '천안-논산고속도로',
			'ktx' => '천안아산역 KTX 환승',
			'bus' => '부여시외버스터미널 → 천안 1시간 30분',
			'note' => '부여에서 70분 — 정밀 진단·수술이 필요한 케이스에 추천',
		),
		'cheongyang' => array(
			'slug' => 'cheongyang', 'name' => '청양', 'name_long' => '청양군', 'province' => '충남',
			'distance_km' => 60, 'duration_min' => 60, 'highway' => '국도 36호선 → 천안-논산고속도로',
			'ktx' => '천안아산역 환승',
			'bus' => '청양시외버스터미널 → 천안 환승',
			'note' => '청양에서 60분 — CBCT 정밀 진단 받으러 오시는 분들',
		),
		'nonsan' => array(
			'slug' => 'nonsan', 'name' => '논산', 'name_long' => '논산시', 'province' => '충남',
			'distance_km' => 70, 'duration_min' => 60, 'highway' => '천안-논산고속도로 직결',
			'ktx' => '논산역에서 천안역까지 KTX·무궁화호',
			'bus' => '논산공용터미널 → 천안종합터미널 1시간 간격',
			'note' => '논산에서 60분 — 임플란트 보철 자체 제작이 강점',
		),
		'gyeryong' => array(
			'slug' => 'gyeryong', 'name' => '계룡', 'name_long' => '계룡시', 'province' => '충남',
			'distance_km' => 75, 'duration_min' => 65, 'highway' => '천안-논산고속도로',
			'ktx' => '계룡역에서 천안 무궁화호',
			'bus' => '계룡 → 대전 → 천안 환승',
			'note' => '계룡에서 65분 — 전신질환 대응 진료가 강점',
		),
		'geumsan' => array(
			'slug' => 'geumsan', 'name' => '금산', 'name_long' => '금산군', 'province' => '충남',
			'distance_km' => 95, 'duration_min' => 80, 'highway' => '대전-통영고속도로 → 천안-논산고속도로',
			'ktx' => '대전역 KTX 환승',
			'bus' => '금산버스터미널 → 대전 → 천안',
			'note' => '금산에서 80분 — 정밀 임플란트·교정 케이스에 추천',
		),
		'seocheon' => array(
			'slug' => 'seocheon', 'name' => '서천', 'name_long' => '서천군', 'province' => '충남',
			'distance_km' => 100, 'duration_min' => 80, 'highway' => '서해안고속도로',
			'ktx' => '서천역에서 천안역까지 무궁화호 1시간 30분',
			'bus' => '서천버스터미널 → 천안 직행',
			'note' => '서천에서 80분 — 종합 통합 진료 원하시는 분들께 추천',
		),
		'taean' => array(
			'slug' => 'taean', 'name' => '태안', 'name_long' => '태안군', 'province' => '충남',
			'distance_km' => 90, 'duration_min' => 80, 'highway' => '서해안고속도로 → 평택파주고속도로',
			'ktx' => '천안아산역 환승',
			'bus' => '태안공용버스터미널 → 천안 환승',
			'note' => '태안에서 80분 — 자체 한아 임플란트 보철연구소 강점',
		),

		/* ───────── 충청북도 (7) ───────── */
		'cheongju' => array(
			'slug' => 'cheongju', 'name' => '청주', 'name_long' => '청주시', 'province' => '충북',
			'distance_km' => 50, 'duration_min' => 50, 'highway' => '중부고속도로 → 천안-논산고속도로',
			'ktx' => '오송역에서 천안아산역까지 KTX 15분',
			'bus' => '청주시외버스터미널 → 천안종합터미널 30분 간격',
			'note' => '청주에서 50분 — KTX로도 빠르게 오실 수 있습니다',
		),
		'chungju' => array(
			'slug' => 'chungju', 'name' => '충주', 'name_long' => '충주시', 'province' => '충북',
			'distance_km' => 90, 'duration_min' => 80, 'highway' => '평택제천고속도로',
			'ktx' => '충주역에서 천안 무궁화호',
			'bus' => '충주공용터미널 → 천안종합터미널 1시간 간격',
			'note' => '충주에서 80분 — 슈어스마일 투명교정 받으러 오시는 분들',
		),
		'eumseong' => array(
			'slug' => 'eumseong', 'name' => '음성', 'name_long' => '음성군', 'province' => '충북',
			'distance_km' => 70, 'duration_min' => 65, 'highway' => '중부고속도로',
			'ktx' => '천안아산역 환승',
			'bus' => '음성공용버스터미널 → 천안 1시간 간격',
			'note' => '음성에서 65분 — 디지털 가이드 임플란트 추천',
		),
		'jincheon' => array(
			'slug' => 'jincheon', 'name' => '진천', 'name_long' => '진천군', 'province' => '충북',
			'distance_km' => 55, 'duration_min' => 55, 'highway' => '중부고속도로',
			'ktx' => '천안아산역 환승',
			'bus' => '진천버스터미널 → 천안 환승',
			'note' => '진천에서 55분 — 임플란트·교정 통합 진료 강점',
		),
		'okcheon' => array(
			'slug' => 'okcheon', 'name' => '옥천', 'name_long' => '옥천군', 'province' => '충북',
			'distance_km' => 85, 'duration_min' => 80, 'highway' => '경부고속도로',
			'ktx' => '옥천역에서 천안 무궁화호',
			'bus' => '옥천버스터미널 → 대전 → 천안',
			'note' => '옥천에서 80분 — 정밀 진단 필요한 케이스에 추천',
		),
		'yeongdong' => array(
			'slug' => 'yeongdong', 'name' => '영동', 'name_long' => '영동군', 'province' => '충북',
			'distance_km' => 110, 'duration_min' => 95, 'highway' => '경부고속도로',
			'ktx' => '영동역에서 천안 무궁화호',
			'bus' => '영동버스터미널 → 대전 → 천안',
			'note' => '영동에서 95분 — 종합 진료 원하시는 분들께 추천',
		),
		'boeun' => array(
			'slug' => 'boeun', 'name' => '보은', 'name_long' => '보은군', 'province' => '충북',
			'distance_km' => 100, 'duration_min' => 90, 'highway' => '청주-상주고속도로 → 경부',
			'ktx' => '천안아산역 환승',
			'bus' => '보은공용버스터미널 → 청주 → 천안',
			'note' => '보은에서 90분 — 30여년 임상 경력 강점',
		),

		/* ───────── 세종특별자치시 (1) ───────── */
		'sejong' => array(
			'slug' => 'sejong', 'name' => '세종', 'name_long' => '세종특별자치시', 'province' => '세종',
			'distance_km' => 35, 'duration_min' => 35, 'highway' => '천안-논산고속도로 (세종IC → 천안IC)',
			'ktx' => '오송역에서 천안아산역까지 KTX 15분',
			'bus' => '세종고속버스터미널 → 천안종합터미널 30분 간격',
			'note' => '세종 시민에게 추천 — 35분 만에 천안 종합 치과병원',
		),

		/* ───────── 대전광역시 (1) ───────── */
		'daejeon' => array(
			'slug' => 'daejeon', 'name' => '대전', 'name_long' => '대전광역시', 'province' => '대전',
			'distance_km' => 55, 'duration_min' => 50, 'highway' => '천안-논산고속도로 (대전IC → 천안IC)',
			'ktx' => '대전역에서 천안아산역까지 KTX 20분',
			'bus' => '대전복합터미널 → 천안종합터미널 15분 간격',
			'note' => '대전에서 KTX로 20분 — 가장 빠른 대도시 환승 경로',
		),

		/* ───────── 경기도 남부 (4) ───────── */
		'pyeongtaek' => array(
			'slug' => 'pyeongtaek', 'name' => '평택', 'name_long' => '평택시', 'province' => '경기',
			'distance_km' => 30, 'duration_min' => 30, 'highway' => '경부고속도로 (평택IC → 천안IC)',
			'ktx' => '지제역에서 천안아산역까지 SRT·KTX 10분',
			'bus' => '평택시외버스터미널 → 천안종합터미널 20분 간격',
			'note' => '평택에서 30분 — 가장 가까운 경기 남부 도시',
		),
		'anseong' => array(
			'slug' => 'anseong', 'name' => '안성', 'name_long' => '안성시', 'province' => '경기',
			'distance_km' => 40, 'duration_min' => 40, 'highway' => '경부고속도로',
			'ktx' => '평택지제역 환승',
			'bus' => '안성종합버스터미널 → 천안종합터미널 30분 간격',
			'note' => '안성에서 40분 — 통합 진료받으러 오시는 분들 많음',
		),
		'osan' => array(
			'slug' => 'osan', 'name' => '오산', 'name_long' => '오산시', 'province' => '경기',
			'distance_km' => 50, 'duration_min' => 45, 'highway' => '경부고속도로 (오산IC → 천안IC)',
			'ktx' => '오산역에서 천안역까지 무궁화호 40분',
			'bus' => '오산버스터미널 → 천안 30분 간격',
			'note' => '오산에서 45분 — 임플란트·교정 종합 진료 강점',
		),
		'hwaseong' => array(
			'slug' => 'hwaseong', 'name' => '화성', 'name_long' => '화성시', 'province' => '경기',
			'distance_km' => 55, 'duration_min' => 50, 'highway' => '서해안고속도로 → 경부',
			'ktx' => '평택지제역·천안아산역 환승',
			'bus' => '병점 → 천안 1시간 간격',
			'note' => '화성에서 50분 — 슈어스마일 투명교정 추천',
		),

	);
}

/**
 * 슬러그로 단일 지역 데이터 반환.
 *
 * @param string $slug
 * @return array|null
 */
function moondental_get_region_by_slug( $slug ) {
	$all = moondental_get_regions();
	return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
}

/**
 * 도(province)별로 지역 그룹화.
 *
 * @return array<string, array> [ '충남' => [region, ...], '충북' => [...], ... ]
 */
function moondental_get_regions_by_province() {
	$groups = array(
		'충남' => array(),
		'충북' => array(),
		'세종' => array(),
		'대전' => array(),
		'경기' => array(),
	);
	foreach ( moondental_get_regions() as $r ) {
		if ( isset( $groups[ $r['province'] ] ) ) {
			$groups[ $r['province'] ][] = $r;
		}
	}
	return $groups;
}
