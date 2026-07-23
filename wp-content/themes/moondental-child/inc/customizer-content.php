<?php
/**
 * 홈페이지 콘텐츠 — 모든 섹션 텍스트를 사용자 정의하기에서 편집 가능하게.
 *
 * 헬퍼 함수 md_content( $key, $default ) 가 Customizer 값을 우선,
 * 없으면 default (현재 박혀있던 텍스트) 반환.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 콘텐츠 값 반환 헬퍼. setting id = "md_content_{key}".
 *
 *  우선순위:
 *   1. Customizer에 저장된 값 (사용자 편집)
 *   2. 함수 호출 시 전달한 $default
 *   3. moondental_*_content_fields() 에 등록된 default (v3.33.4)
 *
 *  WP의 get_theme_mod() 는 프론트엔드에서 Customizer add_setting default를
 *  자동으로 참조하지 않기 때문에, 필드 정의에 있는 default를 별도 조회한다.
 */
function md_content( $key, $default = '' ) {
	$stored = get_theme_mod( 'md_content_' . $key, null );
	if ( is_string( $stored ) && $stored !== '' ) return $stored;
	if ( ! is_null( $stored ) && ! is_string( $stored ) ) return $stored;
	if ( $default !== '' && $default !== null ) return $default;
	// 등록된 필드 default fallback
	$defaults = md_all_content_field_defaults();
	return $defaults[ $key ] ?? '';
}

/**
 * 모든 *_content_fields() 함수에서 등록된 default 값을 key로 flat 매핑.
 *  프론트엔드 로드 시 한 번 캐시.
 */
function md_all_content_field_defaults() {
	static $cache = null;
	if ( $cache !== null ) return $cache;
	$cache = array();
	$fn_names = array(
		'moondental_home_content_fields',
		'moondental_pricing_content_fields',
		'moondental_service_content_fields',
		'moondental_doctor_content_fields',
		'moondental_subpage_content_fields',
		'moondental_chrome_content_fields',
		'moondental_testimonials_content_fields',
		'moondental_compare_content_fields',
		'moondental_service_faq_content_fields',
		'moondental_doctor_meta_content_fields',
		'moondental_doctor_single_content_fields',
		'moondental_history_content_fields',
		'moondental_preservation_content_fields',
		'moondental_smile_content_fields',
		'moondental_prevention_content_fields',
		'moondental_recruit_page_content_fields',
		'moondental_region_content_fields',
		'moondental_misc_pages_content_fields',
		'moondental_bot_content_fields',
		'moondental_finish_content_fields',
		'moondental_final_content_fields',
		'moondental_service_body_content_fields',
	);
	foreach ( $fn_names as $fn ) {
		if ( ! function_exists( $fn ) ) continue;
		$groups = call_user_func( $fn );
		if ( ! is_array( $groups ) ) continue;
		foreach ( $groups as $group ) {
			if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) continue;
			foreach ( $group['fields'] as $key => $field ) {
				if ( isset( $field['default'] ) ) {
					$cache[ $key ] = $field['default'];
				}
			}
		}
	}
	return $cache;
}

/**
 * 텍스트 영역 → 가격표 행 배열 파싱.
 *  한 줄당 한 행. 파이프(|)로 항목 분리: 이름 | 가격 | 비고
 *  '#' 으로 시작하는 줄은 주석으로 무시.
 *  빈 줄은 무시.
 *
 * @param string $text
 * @return array[] [ [name, price, note], ... ]
 */
function md_parse_price_rows( $text ) {
	$rows = array();
	$lines = preg_split( "/\r\n|\r|\n/", trim( (string) $text ) );
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 2 ) {
			$rows[] = array(
				$parts[0],
				$parts[1],
				isset( $parts[2] ) ? $parts[2] : '',
			);
		}
	}
	return $rows;
}

/**
 * 텍스트 영역 → 탭별 가격표 배열 파싱 (자유 편집 형식).
 *
 *  형식:
 *    == 탭 이름 ==
 *    항목 이름 | 가격 | 비고
 *    항목 이름 | 가격 | 비고
 *
 *    == 다른 탭 이름 ==
 *    항목 이름 | 가격 | 비고
 *
 *  규칙:
 *    - '== ... ==' 로 시작하는 줄은 새 탭 시작
 *    - '|' 로 나눈 3열 (이름·가격·비고)
 *    - 빈 줄 무시
 *    - '#' 시작 줄은 주석 (무시)
 *
 *  사용자가 텍스트영역에서 탭 추가·삭제·순서 변경 자유롭게 가능.
 *
 * @param string $text
 * @return array [ 'tab_slug' => [ 'label' => '탭 이름', 'rows' => [ [name, price, note], ... ] ], ... ]
 */
function md_parse_price_tabs( $text ) {
	$tabs = array();
	$current_key = null;
	$idx = 0;
	// v3.28.7: 같은 이름 탭이 여러 번 나와도 하나로 합치기 (중복 탭 방지)
	$label_to_key = array();
	$lines = preg_split( "/\r\n|\r|\n/", trim( (string) $text ) );
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( ! $line || strpos( $line, '#' ) === 0 ) continue;

		// 탭 헤더: == 탭 이름 ==
		if ( preg_match( '/^==\s*(.+?)\s*==$/u', $line, $m ) ) {
			$label = $m[1];
			// 같은 이름 탭이 이미 있으면 그 탭에 이어서 항목 추가
			if ( isset( $label_to_key[ $label ] ) ) {
				$current_key = $label_to_key[ $label ];
			} else {
				$current_key = 'tab_' . $idx++;
				$tabs[ $current_key ] = array( 'label' => $label, 'rows' => array() );
				$label_to_key[ $label ] = $current_key;
			}
			continue;
		}

		// 탭 헤더 전 항목은 무시
		if ( ! $current_key ) continue;

		// 항목: 이름 | 가격 | 비고
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 2 ) {
			$tabs[ $current_key ]['rows'][] = array(
				$parts[0],
				$parts[1],
				isset( $parts[2] ) ? $parts[2] : '',
			);
		}
	}
	return $tabs;
}

/**
 * 텍스트 영역 → 라인별 항목 배열 파싱.
 */
function md_parse_lines( $text ) {
	$out = array();
	foreach ( preg_split( "/\r\n|\r|\n/", trim( (string) $text ) ) as $line ) {
		$line = trim( $line );
		if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
		$out[] = $line;
	}
	return $out;
}

/**
 * 홈 섹션별 콘텐츠 필드 정의 — 한 곳에서 관리.
 * 각 항목: key => [ default, label, type(text|textarea), description ]
 */
function moondental_home_content_fields() {
	return array(

		/* ─── 신뢰 stat 4개 ─────────────────────────────────── */
		'trust' => array(
			'title'  => '홈 — 신뢰 stat 4개',
			'fields' => array(
				'trust_1_value' => array( 'default' => '30',    'label' => '①번 — 숫자',     'type' => 'text' ),
				'trust_1_unit'  => array( 'default' => '년',     'label' => '①번 — 단위',     'type' => 'text' ),
				'trust_1_label' => array( 'default' => '1995년 개원', 'label' => '①번 — 라벨', 'type' => 'text' ),
				'trust_1_sub'   => array( 'default' => '전국에서 찾아오는 30여년 신뢰', 'label' => '①번 — 부제', 'type' => 'text' ),

				'trust_2_value' => array( 'default' => '11',    'label' => '②번 — 숫자',     'type' => 'text' ),
				'trust_2_unit'  => array( 'default' => '개',    'label' => '②번 — 단위',     'type' => 'text' ),
				'trust_2_label' => array( 'default' => '전문 진료 영역', 'label' => '②번 — 라벨', 'type' => 'text' ),
				'trust_2_sub'   => array( 'default' => '보철·보존·예방·임플란트·스마일디자인·구강외과·구강내과·턱관절·교정·소아·치주', 'label' => '②번 — 부제', 'type' => 'text' ),

				'trust_3_value' => array( 'default' => '4',     'label' => '③번 — 숫자',     'type' => 'text' ),
				'trust_3_unit'  => array( 'default' => '개층',   'label' => '③번 — 단위',     'type' => 'text' ),
				'trust_3_label' => array( 'default' => '통합 진료센터', 'label' => '③번 — 라벨', 'type' => 'text' ),
				'trust_3_sub'   => array( 'default' => '9F 보철·보존 · 10F 임플란트·외과 · 11F 교정·소아 · 13F 기공', 'label' => '③번 — 부제', 'type' => 'text' ),

				'trust_4_value' => array( 'default' => '1:1',   'label' => '④번 — 숫자',     'type' => 'text' ),
				'trust_4_unit'  => array( 'default' => '',       'label' => '④번 — 단위',     'type' => 'text' ),
				'trust_4_label' => array( 'default' => '충분한 사전 상담', 'label' => '④번 — 라벨', 'type' => 'text' ),
				'trust_4_sub'   => array( 'default' => '들어보고, 꼭 필요한 치료만', 'label' => '④번 — 부제', 'type' => 'text' ),
			),
		),

		/* ─── Why 4 카드 ───────────────────────────────────── */
		'why' => array(
			'title'  => '홈 — Why 차별점 4 카드',
			'fields' => array(
				'why_eyebrow' => array( 'default' => 'WHY MOON DENTAL', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'why_title'   => array( 'default' => '천안·아산 대표 치과병원 · 전국에서 찾아오시는 이유', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'why_lead'    => array( 'default' => '1995년 개원 30여년 · 천안 만남로에서 전국 환자분들이 문치과병원을 선택하시는 4가지 이유', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

				'why_1_icon'  => array( 'default' => '🏥', 'label' => '①번 — 아이콘(이모지)', 'type' => 'text' ),
				'why_1_title' => array( 'default' => '전국에서 찾아오는 30여년', 'label' => '①번 — 제목', 'type' => 'text' ),
				'why_1_desc'  => array( 'default' => '1995년부터 천안 만남로 한자리에서 진료해온 동네 치과. 지역 환자분과 함께 전국에서 찾아오시는 분들의 평생 치아를 길게 봅니다.', 'label' => '①번 — 설명', 'type' => 'textarea' ),

				'why_2_icon'  => array( 'default' => '🏢', 'label' => '②번 — 아이콘', 'type' => 'text' ),
				'why_2_title' => array( 'default' => '통합 진료센터', 'label' => '②번 — 제목', 'type' => 'text' ),
				'why_2_desc'  => array( 'default' => '9F 보철·보존 · 10F 임플란트·외과 · 11F 교정·소아 · 13F 기공 — 분야별 전문 의료진의 협진을 한 곳에서 받으실 수 있습니다.', 'label' => '②번 — 설명', 'type' => 'textarea' ),

				'why_3_icon'  => array( 'default' => '❤️', 'label' => '③번 — 아이콘', 'type' => 'text' ),
				'why_3_title' => array( 'default' => '전신질환 안심 진료', 'label' => '③번 — 제목', 'type' => 'text' ),
				'why_3_desc'  => array( 'default' => '혈압기·당검사·심전도·산소포화도 상시 보유. 고혈압·당뇨·심장질환자도 안전하게 진료합니다.', 'label' => '③번 — 설명', 'type' => 'textarea' ),

				'why_4_icon'  => array( 'default' => '🛡️', 'label' => '④번 — 아이콘', 'type' => 'text' ),
				'why_4_title' => array( 'default' => '평생 A/S 시스템', 'label' => '④번 — 제목', 'type' => 'text' ),
				'why_4_desc'  => array( 'default' => '시술 후 정기 검진과 문제 발생 시 책임 대응. 비용은 시술 시점에 한 번, 관리는 평생.', 'label' => '④번 — 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 진료 시스템 소개 (6 카드) v3.27.7 ─────────────── */
		'clinic_intro' => array(
			'title'  => '홈 — 진료 시스템 소개 (6 카드)',
			'fields' => array(
				'clinic_intro_eyebrow' => array( 'default' => 'CLINIC SYSTEM · 진료 시스템', 'label' => '섹션 eyebrow', 'type' => 'text' ),
				'clinic_intro_title'   => array( 'default' => '천안·아산 대표 치과병원 · 전국에서 찾아오는 통합 진료', 'label' => '섹션 제목', 'type' => 'text' ),
				'clinic_intro_lead'    => array( 'default' => "천안 만남로 1995년 개원 · 30여년 임상. 각 분과 전문 의료진의 대학병원식 협진 시스템으로\n지역 환자분과 전국에서 찾아오시는 환자분 모두를 한 자리에서 진료합니다.", 'label' => '섹션 설명 (줄바꿈으로 두 줄)', 'type' => 'textarea' ),

				/* 01 임플란트센터 */
				'clinic_intro_implant_icon'  => array( 'default' => '🦷', 'label' => '① 임플란트센터 · 아이콘', 'type' => 'text' ),
				'clinic_intro_implant_title' => array( 'default' => '임플란트센터', 'label' => '① 임플란트센터 · 제목', 'type' => 'text' ),
				'clinic_intro_implant_lead'  => array( 'default' => '정밀한 임플란트 시술은 물론, 정기 검진을 통한 사후관리까지 철저히 진행합니다.', 'label' => '① 임플란트센터 · 리드', 'type' => 'textarea' ),
				'clinic_intro_implant_list'  => array( 'default' => "고난도 임플란트\n앞니 상실로 불편을 겪는 분들을 위한 즉시 치아 회복\n실패한 임플란트 재수술\n통증을 줄이는 비절개 임플란트\n상악동 거상술\n전악 임플란트\n디지털 장비를 활용한 정밀 네비게이션 임플란트", 'label' => '① 임플란트센터 · 리스트 (한 줄에 한 항목)', 'type' => 'textarea' ),
				'clinic_intro_implant_more'  => array( 'default' => '자세히 보기 →', 'label' => '① 임플란트센터 · 링크 라벨', 'type' => 'text' ),

				/* 02 교정센터 */
				'clinic_intro_ortho_icon'  => array( 'default' => '✨', 'label' => '② 교정센터 · 아이콘', 'type' => 'text' ),
				'clinic_intro_ortho_title' => array( 'default' => '교정센터', 'label' => '② 교정센터 · 제목', 'type' => 'text' ),
				'clinic_intro_ortho_lead'  => array( 'default' => 'AI 기반 투명교정 진단 시스템을 도입해 정밀 분석이 가능하며, 환자별 최적의 교정 계획을 제안합니다.', 'label' => '② 교정센터 · 리드', 'type' => 'textarea' ),
				'clinic_intro_ortho_list'  => array( 'default' => "고난도 교정\n투명교정 (슈어스마일)\n소아 교정\n재교정\n앞니 부분 교정", 'label' => '② 교정센터 · 리스트', 'type' => 'textarea' ),
				'clinic_intro_ortho_more'  => array( 'default' => '자세히 보기 →', 'label' => '② 교정센터 · 링크 라벨', 'type' => 'text' ),

				/* 03 스마일디자인센터 */
				'clinic_intro_smile_icon'  => array( 'default' => '💎', 'label' => '③ 스마일디자인 · 아이콘', 'type' => 'text' ),
				'clinic_intro_smile_title' => array( 'default' => '스마일디자인센터', 'label' => '③ 스마일디자인 · 제목', 'type' => 'text' ),
				'clinic_intro_smile_lead'  => array( 'default' => '반점치(화이트스팟) 제거·치아 성형·잇몸 미백·최소침습 라미네이트·벌어진 앞니 레진 수복·왜소치 치료 등 다양한 심미적 고민에 맞춤 진단으로 개인별 최적 치료를 제안합니다.', 'label' => '③ 스마일디자인 · 리드', 'type' => 'textarea' ),
				'clinic_intro_smile_list'  => array( 'default' => "반점치(화이트스팟) 제거\n치아 성형 · 잇몸 미백\n최소침습 라미네이트\n벌어진 앞니 레진 수복\n왜소치 치료\n최소 침습 치료 원칙 — 불필요한 치아 삭제 최소화", 'label' => '③ 스마일디자인 · 리스트', 'type' => 'textarea' ),
				'clinic_intro_smile_more'  => array( 'default' => '자세히 보기 →', 'label' => '③ 스마일디자인 · 링크 라벨', 'type' => 'text' ),

				/* 04 자연치아 살리기 */
				'clinic_intro_preserve_icon'  => array( 'default' => '🌿', 'label' => '④ 자연치아 살리기 · 아이콘', 'type' => 'text' ),
				'clinic_intro_preserve_title' => array( 'default' => '자연치아 살리기', 'label' => '④ 자연치아 살리기 · 제목', 'type' => 'text' ),
				'clinic_intro_preserve_lead'  => array( 'default' => '문치과병원은 발치 대신 자연치아를 최대한 보존하는 치료를 우선합니다.', 'label' => '④ 자연치아 살리기 · 리드', 'type' => 'textarea' ),
				'clinic_intro_preserve_list'  => array( 'default' => "충치치료 — 초기 충치부터 정밀하게 진단·치료\n신경치료 — 손상된 치수를 살려 자연치아 보존\n잇몸치료 — 치주 질환 관리로 치아 수명 연장", 'label' => '④ 자연치아 살리기 · 리스트', 'type' => 'textarea' ),
				'clinic_intro_preserve_more'  => array( 'default' => '자세히 보기 →', 'label' => '④ 자연치아 살리기 · 링크 라벨', 'type' => 'text' ),

				/* 05 진료과 */
				'clinic_intro_dept_icon'  => array( 'default' => '🏥', 'label' => '⑤ 진료과 · 아이콘', 'type' => 'text' ),
				'clinic_intro_dept_title' => array( 'default' => '진료과', 'label' => '⑤ 진료과 · 제목', 'type' => 'text' ),
				'clinic_intro_dept_lead'  => array( 'default' => '전 분과 전문 의료진이 분야별 진료를 한 자리에서 협진합니다.', 'label' => '⑤ 진료과 · 리드', 'type' => 'textarea' ),
				'clinic_intro_dept_list'  => array( 'default' => "턱관절 — 통증·기능 장애 진료\n이갈이 · 이악물기\n매복 사랑니 발치\n소아치과\n예방클리닉 — 전문예방치료실 · 덴탈 스파 프로그램", 'label' => '⑤ 진료과 · 리스트', 'type' => 'textarea' ),
				'clinic_intro_dept_more'  => array( 'default' => '예방클리닉 자세히 →', 'label' => '⑤ 진료과 · 링크 라벨', 'type' => 'text' ),

				/* 06 기술력/시설 */
				'clinic_intro_facility_icon'  => array( 'default' => '🔬', 'label' => '⑥ 기술력/시설 · 아이콘', 'type' => 'text' ),
				'clinic_intro_facility_title' => array( 'default' => '기술력 / 시설', 'label' => '⑥ 기술력/시설 · 제목', 'type' => 'text' ),
				'clinic_intro_facility_lead'  => array( 'default' => '자체 디지털센터·기공소 운영. 물방울 레이저 5대 보유 — 통증·출혈 적고 빠른 회복.', 'label' => '⑥ 기술력/시설 · 리드', 'type' => 'textarea' ),
				'clinic_intro_facility_list'  => array( 'default' => "One Day 보철 치료까지 가능 (구강 정밀 스캔)\n의료진·기공사 긴밀 소통으로 맞춤형 보철\n오차 최소화 — 높은 정확도 · 내원 횟수 단축\n원내 기공소 신속 수정·A/S\n물방울 레이저 — 임플란트 주위염·잇몸 성형·시린이·신경치료·구내염·점액낭종", 'label' => '⑥ 기술력/시설 · 리스트', 'type' => 'textarea' ),
				'clinic_intro_facility_more'  => array( 'default' => '자세히 보기 →', 'label' => '⑥ 기술력/시설 · 링크 라벨', 'type' => 'text' ),

				/* 야간진료 강조 박스 */
				'clinic_intro_night_title' => array( 'default' => '야간 진료 운영', 'label' => '야간진료 박스 · 제목', 'type' => 'text' ),
				'clinic_intro_night_desc'  => array( 'default' => '천안시 신부동에 위치한 문치과병원은 바쁜 일상 속에서도 원하는 시간에 진료받으실 수 있도록 월·화·수·금요일 저녁 8시 30분까지 야간진료를 운영합니다.', 'label' => '야간진료 박스 · 설명', 'type' => 'textarea' ),

				/* 마무리 문구 */
				'clinic_intro_closer' => array( 'default' => '앞으로도 문치과병원은 봉사와 지역의료의 책임을 감당해 나가겠습니다.', 'label' => '섹션 마무리 문구', 'type' => 'textarea' ),
			),
		),

		/* ─── Hero 배지 & 사명 협력기관 (v3.30.0) ──────────── */
		'hero_and_mission' => array(
			'title'  => '홈 — Hero 배지 · 사명 협력기관',
			'fields' => array(
				'hero_badges'   => array(
					'default' => "천안 만남로 1995년 개원 · 30여년 임상\n분야별 전문 의료진 협진\n요양기관번호 34400117 · 한아의료재단",
					'label' => 'Hero 배지 (한 줄에 하나 · 3개 권장)',
					'type' => 'textarea',
				),
				'mission_certs' => array(
					'default' => "🏥|국가지정 구강검진 치과\n🌐|외국인환자 유치 의료기관\n🪖|미군 및 가족 치료기관\n🦷|천안시 치아사랑사업 협력병원\n🔗|삼성서울병원 협력병원\n➕|대한적십자사 협력병원",
					'label' => '사명 · 협력·지정 의료기관 (한 줄에 "이모지|라벨")',
					'type' => 'textarea',
				),
			),
		),

		/* ─── Services 섹션 head ───────────────────────────── */
		'services' => array(
			'title'  => '홈 — 진료안내 섹션 head',
			'fields' => array(
				'services_eyebrow' => array( 'default' => 'CLINICAL SERVICES · 천안·아산 진료항목', 'label' => 'eyebrow', 'type' => 'text' ),
				'services_title'   => array( 'default' => '천안·아산에서 한 곳에서, 평생 치아 건강을', 'label' => '제목', 'type' => 'text' ),
				'services_lead'    => array( 'default' => '천안·아산 임플란트·투명교정·라미네이트·자연치아 살리기·사랑니 발치까지 — 한 분의 환자를 오래 보는 천안 만남로 치과의 마음으로 진료합니다.', 'label' => '설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 진료 흐름 6단계 ─────────────────────────────── */
		'process' => array(
			'title'  => '홈 — 진료 흐름 6단계',
			'fields' => array(
				'process_eyebrow' => array( 'default' => 'Treatment Flow', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'process_title'   => array( 'default' => '첫 방문부터 사후관리까지', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'process_lead'    => array( 'default' => '환자분이 어느 단계에 계신지 항상 알 수 있도록 — 6단계로 안내드립니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),
				'process_cta'     => array( 'default' => '📅 첫 단계 시작하기', 'label' => '하단 CTA 버튼 라벨', 'type' => 'text' ),

				'process_1_icon'  => array( 'default' => '📞', 'label' => '01 — 아이콘', 'type' => 'text' ),
				'process_1_title' => array( 'default' => '예약 / 문의', 'label' => '01 — 제목', 'type' => 'text' ),
				'process_1_desc'  => array( 'default' => '전화 · 네이버 예약 · 카카오톡 — 편하신 방법으로 연락', 'label' => '01 — 설명', 'type' => 'textarea' ),

				'process_2_icon'  => array( 'default' => '👂', 'label' => '02 — 아이콘', 'type' => 'text' ),
				'process_2_title' => array( 'default' => '첫 방문 · 상담', 'label' => '02 — 제목', 'type' => 'text' ),
				'process_2_desc'  => array( 'default' => '증상과 우려를 충분히 듣고 환자분의 상황을 파악', 'label' => '02 — 설명', 'type' => 'textarea' ),

				'process_3_icon'  => array( 'default' => '🔬', 'label' => '03 — 아이콘', 'type' => 'text' ),
				'process_3_title' => array( 'default' => '정밀 진단', 'label' => '03 — 제목', 'type' => 'text' ),
				'process_3_desc'  => array( 'default' => 'CT · 파노라마 · 구강 검사로 정확한 상태 진단', 'label' => '03 — 설명', 'type' => 'textarea' ),

				'process_4_icon'  => array( 'default' => '📄', 'label' => '04 — 아이콘', 'type' => 'text' ),
				'process_4_title' => array( 'default' => '견적 · 치료 계획', 'label' => '04 — 제목', 'type' => 'text' ),
				'process_4_desc'  => array( 'default' => '옵션별 비용 · 기간 · 과정을 문서로 안내', 'label' => '04 — 설명', 'type' => 'textarea' ),

				'process_5_icon'  => array( 'default' => '🦷', 'label' => '05 — 아이콘', 'type' => 'text' ),
				'process_5_title' => array( 'default' => '동의 후 치료', 'label' => '05 — 제목', 'type' => 'text' ),
				'process_5_desc'  => array( 'default' => '충분히 검토하시고 시작 · 추가 비용 없음', 'label' => '05 — 설명', 'type' => 'textarea' ),

				'process_6_icon'  => array( 'default' => '🌿', 'label' => '06 — 아이콘', 'type' => 'text' ),
				'process_6_title' => array( 'default' => '정기 관리 / A/S', 'label' => '06 — 제목', 'type' => 'text' ),
				'process_6_desc'  => array( 'default' => '시술 후 정기 검진과 평생 사후 관리', 'label' => '06 — 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 시설·장비 6 카드 ──────────────────────────────── */
		'facility' => array(
			'title'  => '홈 — 시설·장비 6 카드',
			'fields' => array(
				'facility_eyebrow' => array( 'default' => 'Facility & Equipment', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'facility_title'   => array( 'default' => '천안 만남로 — 정확한 진단과 안전한 진료를 위한 인프라', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'facility_lead'    => array( 'default' => '9·10·11·13F 통합 진료센터 — 디지털 진단·수술 시스템과 응급 의료 장비를 갖추고 있습니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

				'facility_1_icon'  => array( 'default' => '🩻', 'label' => '①번 — 아이콘', 'type' => 'text' ),
				'facility_1_title' => array( 'default' => '디지털 CBCT 3D 진단', 'label' => '①번 — 제목', 'type' => 'text' ),
				'facility_1_desc'  => array( 'default' => '저선량 콘빔 CT로 신경·혈관·골 두께까지 3차원으로 정밀 분석합니다.', 'label' => '①번 — 설명', 'type' => 'textarea' ),

				'facility_2_icon'  => array( 'default' => '🎯', 'label' => '②번 — 아이콘', 'type' => 'text' ),
				'facility_2_title' => array( 'default' => '디지털 가이드 수술', 'label' => '②번 — 제목', 'type' => 'text' ),
				'facility_2_desc'  => array( 'default' => '컴퓨터 시뮬레이션으로 임플란트 식립 위치·각도를 사전 설계 — 안전과 정확도를 동시에.', 'label' => '②번 — 설명', 'type' => 'textarea' ),

				'facility_3_icon'  => array( 'default' => '🏛️', 'label' => '③번 — 아이콘', 'type' => 'text' ),
				'facility_3_title' => array( 'default' => '자체 보철 연구소', 'label' => '③번 — 제목', 'type' => 'text' ),
				'facility_3_desc'  => array( 'default' => '한아 임플란트 보철연구소 — 인사이드 워크플로우로 정밀하고 빠른 보철 제작.', 'label' => '③번 — 설명', 'type' => 'textarea' ),

				'facility_4_icon'  => array( 'default' => '🧴', 'label' => '④번 — 아이콘', 'type' => 'text' ),
				'facility_4_title' => array( 'default' => '멸균 · 감염 관리', 'label' => '④번 — 제목', 'type' => 'text' ),
				'facility_4_desc'  => array( 'default' => '의료기관 표준 멸균 프로세스 — 핸드피스·기구 모두 환자 단위로 멸균 관리.', 'label' => '④번 — 설명', 'type' => 'textarea' ),

				'facility_5_icon'  => array( 'default' => '❤️', 'label' => '⑤번 — 아이콘', 'type' => 'text' ),
				'facility_5_title' => array( 'default' => '응급 의료 장비 상시', 'label' => '⑤번 — 제목', 'type' => 'text' ),
				'facility_5_desc'  => array( 'default' => '혈압기 · 혈당검사 · 심전도 · 산소포화도 — 전신질환자도 안전한 진료 인프라.', 'label' => '⑤번 — 설명', 'type' => 'textarea' ),

				'facility_6_icon'  => array( 'default' => '🌙', 'label' => '⑥번 — 아이콘', 'type' => 'text' ),
				'facility_6_title' => array( 'default' => '평일 야간 진료', 'label' => '⑥번 — 제목', 'type' => 'text' ),
				'facility_6_desc'  => array( 'default' => '월·화·수·금 ~ 20:30 — 직장인 · 학생도 부담 없이 방문하실 수 있도록.', 'label' => '⑥번 — 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 환자후기 섹션 head ──────────────────────────── */
		'testimonials' => array(
			'title'  => '홈 — 환자 후기 섹션 head',
			'fields' => array(
				'testimonials_eyebrow' => array( 'default' => 'Reviews', 'label' => 'eyebrow', 'type' => 'text' ),
				'testimonials_title'   => array( 'default' => '환자분들의 이야기', 'label' => '제목', 'type' => 'text' ),
				'testimonials_lead'    => array( 'default' => '문치과병원을 찾아주신 환자분들이 직접 남겨주신 후기입니다.', 'label' => '설명', 'type' => 'textarea' ),
			),
		),

		/* ─── FAQ 6 ────────────────────────────────────────── */
		'faq' => array(
			'title'  => '홈 — 자주 묻는 질문 6',
			'fields' => array(
				'faq_eyebrow' => array( 'default' => 'FAQ', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'faq_title'   => array( 'default' => '예약 전 자주 묻는 질문', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'faq_lead'    => array( 'default' => '환자분들이 가장 많이 궁금해하시는 6가지 — 미리 확인하세요.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

				'faq_1_q' => array( 'default' => '당일 예약도 가능한가요?', 'label' => '①번 — 질문', 'type' => 'text' ),
				'faq_1_a' => array( 'default' => '네, 가능합니다. 다만 예약 상황에 따라 대기 시간이 발생할 수 있으니 가급적 전화 또는 카카오톡으로 먼저 확인 후 방문해주시기 바랍니다.', 'label' => '①번 — 답변', 'type' => 'textarea' ),

				'faq_2_q' => array( 'default' => '주차는 어떻게 하나요?', 'label' => '②번 — 질문', 'type' => 'text' ),
				'faq_2_a' => array( 'default' => '본원 지하 기계식 주차장을 무료로 이용하실 수 있습니다. 기계식이 어려운 SUV는 인근 신부 제5공영주차장(동남구 먹거리1길 10)에 주차 후 방문해주시면 무료 주차 등록을 도와드립니다.', 'label' => '②번 — 답변', 'type' => 'textarea' ),

				'faq_3_q' => array( 'default' => '전신질환(고혈압·당뇨·심장)이 있어도 진료 가능한가요?', 'label' => '③번 — 질문', 'type' => 'text' ),
				'faq_3_a' => array( 'default' => '안심하셔도 됩니다. 혈압기·당검사·심전도·산소포화도 측정 장비를 상시 보유하고 있으며, 복용 중인 약물(혈전용해제·골다공증 약 등)을 사전에 체크해 안전하게 진료합니다.', 'label' => '③번 — 답변', 'type' => 'textarea' ),

				'faq_4_q' => array( 'default' => '치료 비용은 미리 알 수 있나요?', 'label' => '④번 — 질문', 'type' => 'text' ),
				'faq_4_a' => array( 'default' => '비급여 진료(임플란트·교정·심미)는 정밀 진단 후 옵션별 비용·기간을 문서로 안내드립니다. 시작 후 추가 비용은 발생하지 않습니다.', 'label' => '④번 — 답변', 'type' => 'textarea' ),

				'faq_5_q' => array( 'default' => '임플란트 건강보험이 적용되나요?', 'label' => '⑤번 — 질문', 'type' => 'text' ),
				'faq_5_a' => array( 'default' => '만 65세 이상 건강보험 가입자는 평생 2개까지 본인부담 30%로 적용됩니다. 부분 무치악이 대상이며, 잔존치 하나만 있어도 가능합니다.', 'label' => '⑤번 — 답변', 'type' => 'textarea' ),

				'faq_6_q' => array( 'default' => '예약 변경 · 취소는 어떻게 하나요?', 'label' => '⑥번 — 질문', 'type' => 'text' ),
				'faq_6_a' => array( 'default' => '네이버 예약은 예약 페이지에서 직접 변경·취소가 가능하고, 그 외에는 전화 또는 카카오톡 채널로 문의해주세요. 예약일 전날까지 변경·취소가 가능합니다.', 'label' => '⑥번 — 답변', 'type' => 'textarea' ),
			),
		),

		/* ─── 홈 — Info 섹션 (진료시간 / 전화 / 위치) ─── */
		'info' => array(
			'title'  => '홈 — Info 섹션 (진료시간/전화/위치)',
			'fields' => array(
				'info_eyebrow' => array( 'default' => 'Information',          'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'info_title'   => array( 'default' => '진료시간 & 오시는 길', 'label' => '섹션 — 제목',    'type' => 'text' ),

				'info_block_hours_label'    => array( 'default' => '진료시간',     'label' => '①번 카드 — 라벨', 'type' => 'text' ),
				'info_block_phone_label'    => array( 'default' => '전화 문의',    'label' => '②번 카드 — 라벨', 'type' => 'text' ),
				'info_block_phone_sub'      => array( 'default' => "전화 예약 / 진료 문의\n진료시간 내에만 응답 가능합니다.", 'label' => '②번 카드 — 설명 (줄바꿈)', 'type' => 'textarea' ),
				'info_block_phone_btn'      => array( 'default' => '카카오톡 상담', 'label' => '②번 카드 — 버튼 라벨', 'type' => 'text' ),

				'info_block_location_label' => array( 'default' => '오시는 길',    'label' => '③번 카드 — 라벨', 'type' => 'text' ),
				'info_block_location_btn1'  => array( 'default' => '🟢 네이버 플레이스', 'label' => '③번 카드 — 버튼 1', 'type' => 'text' ),
				'info_block_location_btn2'  => array( 'default' => '지도·교통 자세히 →', 'label' => '③번 카드 — 버튼 2', 'type' => 'text' ),
				'info_block_location_sub'   => array( 'default' => '주차·대중교통 안내는 예약·상담 페이지에서 확인하실 수 있습니다.', 'label' => '③번 카드 — 하단 안내', 'type' => 'textarea' ),
			),
		),

		/* ─── 홈 — 소식 섹션 ─── */
		'notices' => array(
			'title'  => '홈 — 소식 섹션',
			'fields' => array(
				'notices_eyebrow'        => array( 'default' => 'News',         'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'notices_title'          => array( 'default' => '천안·아산 대표 치과병원 · 문치과병원 소식',     'label' => '섹션 — 제목',    'type' => 'text' ),
				'notices_all_link_label' => array( 'default' => '전체보기 →',  'label' => '전체보기 링크 라벨', 'type' => 'text' ),
			),
		),

		/* ─── CTA 배너 (기본 · 홈 및 나머지 페이지) ─────────── */
		'cta' => array(
			'title'  => '홈 — 하단 CTA 배너',
			'fields' => array(
				'cta_eyebrow' => array( 'default' => '상담 예약', 'label' => 'eyebrow (홈 등 기본 문구)', 'type' => 'text' ),
				'cta_title'   => array( 'default' => '천안·아산 대표 치과병원 · 전국에서 찾아오시는 병원', 'label' => '제목', 'type' => 'text' ),
				'cta_lead'    => array( 'default' => "환자분의 상황을 먼저 듣고, 꼭 필요한 치료만 권합니다.\n지금 상담을 신청하시면 진료시간 내 빠르게 연락드릴게요.", 'label' => '설명 (줄바꿈 가능)', 'type' => 'textarea' ),
				'cta_btn1'    => array( 'default' => '📅 상담 예약하기', 'label' => '버튼 1 라벨', 'type' => 'text' ),
				'cta_btn2'    => array( 'default' => '카카오톡', 'label' => '버튼 2 라벨', 'type' => 'text' ),
				'cta_hint'    => array( 'default' => '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00 · 일/공휴일 휴진', 'label' => '하단 진료시간 안내 (모든 페이지 공용)', 'type' => 'text' ),
			),
		),

		/* ─── v3.37.9 · 페이지별 하단 CTA 오버라이드 ────────
		 *  기존 페이지 섹션에 이미 필드가 있는 경우(doctors_cta_*, price_cta_*,
		 *  preservation_cta_*, prevention_cta_*, smile_cta_*, region_cta_*,
		 *  recruit_page_cta_*, doc_single_cta_*)는 그대로 재사용.
		 *  여기는 그 외 페이지들만 추가. */
		'cta_extra' => array(
			'title'  => '하단 CTA — 페이지별 문구 (기타)',
			'fields' => array(
				'cta_location_title' => array( 'default' => "오시는 길 확인하셨다면\n지금 예약해주세요", 'label' => '오시는 길 · 제목 (줄바꿈 가능)', 'type' => 'textarea' ),
				'cta_location_lead'  => array( 'default' => '문타워 9·10·11·13층 · 자체 주차장 · 편하신 방법으로 연락주세요.', 'label' => '오시는 길 · 설명', 'type' => 'textarea' ),
				'cta_facility_title' => array( 'default' => '시설을 직접 보고 결정하세요', 'label' => '기술력·시설 · 제목', 'type' => 'text' ),
				'cta_facility_lead'  => array( 'default' => '편하신 시간에 방문 상담이 가능합니다. 미리 예약해주시면 대기 없이 안내드립니다.', 'label' => '기술력·시설 · 설명', 'type' => 'textarea' ),
				'cta_faq_title'      => array( 'default' => '여전히 궁금한 점이 있으신가요?', 'label' => 'FAQ · 제목', 'type' => 'text' ),
				'cta_faq_lead'       => array( 'default' => 'FAQ에서 답을 찾지 못하셨다면 언제든 편하게 상담해주세요.', 'label' => 'FAQ · 설명', 'type' => 'textarea' ),
				'cta_news_title'     => array( 'default' => '궁금한 진료가 있으신가요?', 'label' => '병원 소식 · 제목', 'type' => 'text' ),
				'cta_news_lead'      => array( 'default' => '관련 상담을 원하시면 부담 없이 연락주세요. 진료시간 내 빠르게 답변드립니다.', 'label' => '병원 소식 · 설명', 'type' => 'textarea' ),
				'cta_enc_title'      => array( 'default' => '이 증상, 나에게 해당할까요?', 'label' => '치과사전 · 제목', 'type' => 'text' ),
				'cta_enc_lead'       => array( 'default' => '치과사전은 참고용입니다. 정확한 진단·치료 계획은 의료진 상담이 필요합니다.', 'label' => '치과사전 · 설명', 'type' => 'textarea' ),
				'cta_history_title'  => array( 'default' => '30년 임상, 지금 만나보세요', 'label' => '30년 발자취 · 제목', 'type' => 'text' ),
				'cta_history_lead'   => array( 'default' => '오랜 시간 축적된 진료 노하우로 정직하게 상담드립니다.', 'label' => '30년 발자취 · 설명', 'type' => 'textarea' ),
				'cta_service_title'  => array( 'default' => '나에게 맞는지 상담받아보세요', 'label' => '진료 영역 페이지 · 제목 (앞에 진료명 자동 붙음)', 'type' => 'text' ),
				'cta_service_lead'   => array( 'default' => '진단부터 치료 계획까지 부담 없이 안내드립니다. 시작 전에 궁금한 점을 다 여쭤보세요.', 'label' => '진료 영역 페이지 · 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── v3.38.2 · 크롬(chrome) · aria-label · 브레드크럼 · 메뉴 라벨 재작성 ─── */
		'chrome' => array(
			'title'  => '헤더·푸터·플로팅 · aria·라벨 문구',
			'fields' => array(
				// 브레드크럼
				'breadcrumb_home'          => array( 'default' => '홈', 'label' => '브레드크럼 · 홈 링크 라벨', 'type' => 'text' ),
				'breadcrumb_news'          => array( 'default' => '소식', 'label' => '브레드크럼 · 소식 라벨', 'type' => 'text' ),
				'breadcrumb_encyclopedia'  => array( 'default' => '치과사전', 'label' => '브레드크럼 · 치과사전 라벨', 'type' => 'text' ),
				// 헤더 aria
				'aria_skip_link'          => array( 'default' => '본문 바로가기', 'label' => '헤더 · 본문 바로가기 (스킵 링크)', 'type' => 'text' ),
				'aria_menu_open'          => array( 'default' => '메뉴 열기', 'label' => '헤더 · 메뉴 열기 aria', 'type' => 'text' ),
				'aria_menu_close'         => array( 'default' => '메뉴 닫기', 'label' => '헤더 · 메뉴 닫기 aria', 'type' => 'text' ),
				'aria_primary_menu'       => array( 'default' => '주 메뉴', 'label' => '헤더 · 주 메뉴 aria', 'type' => 'text' ),
				'aria_hours_call'         => array( 'default' => '진료시간 및 전화', 'label' => '헤더 · 진료시간/전화 aria', 'type' => 'text' ),
				'aria_hours_location'     => array( 'default' => '오시는 길과 진료시간 보기', 'label' => '헤더 · 위치/시간 보기 aria', 'type' => 'text' ),
				// 푸터 aria
				'aria_footer_channels'    => array( 'default' => '문치과 채널', 'label' => '푸터 · 소셜 채널 리스트 aria', 'type' => 'text' ),
				'aria_social_naver_blog'  => array( 'default' => '네이버 블로그', 'label' => '푸터 · 네이버 블로그 aria', 'type' => 'text' ),
				'aria_social_facebook'    => array( 'default' => '페이스북', 'label' => '푸터 · 페이스북 aria', 'type' => 'text' ),
				// 모바일 CTA (하단 고정바) aria
				'aria_mobile_call'        => array( 'default' => '전화로 예약·상담', 'label' => '모바일 CTA · 전화 aria', 'type' => 'text' ),
				'aria_mobile_kakao'       => array( 'default' => '카카오톡으로 상담', 'label' => '모바일 CTA · 카톡 aria', 'type' => 'text' ),
				'aria_mobile_naver'       => array( 'default' => '네이버로 예약', 'label' => '모바일 CTA · 네이버 aria', 'type' => 'text' ),
				// 데스크탑 FAB aria
				'aria_fab_call_tpl'       => array( 'default' => '전화 상담 — {phone}', 'label' => 'FAB · 전화 aria ({phone} 토큰)', 'type' => 'text' ),
				'aria_fab_naver'          => array( 'default' => '네이버 예약 열기', 'label' => 'FAB · 네이버 aria', 'type' => 'text' ),
				'aria_fab_kakao'          => array( 'default' => '카카오톡 상담 열기', 'label' => 'FAB · 카톡 aria', 'type' => 'text' ),
				'aria_totop'              => array( 'default' => '페이지 맨 위로 이동', 'label' => 'FAB · 맨 위로 aria', 'type' => 'text' ),
				// 메뉴 라벨 재작성 (자동 변환)
				'menu_history_rewrite'    => array( 'default' => '30여년의 발자취', 'label' => "메뉴 · '역사' 자동 재작성 (빈칸 시 재작성 안 함)", 'type' => 'text' ),
				'menu_promote_doctors'    => array( 'default' => '의료진', 'label' => '메뉴 · 톱레벨 승격 의료진 라벨', 'type' => 'text' ),
				'menu_promote_pricing'    => array( 'default' => '비용안내', 'label' => '메뉴 · 톱레벨 승격 비용안내 라벨', 'type' => 'text' ),
				// 공용 CTA (단일글·사전 · 소식으로 돌아가기 등)
				'ui_related_posts'        => array( 'default' => '관련 글', 'label' => 'UI · 관련 글 섹션 제목', 'type' => 'text' ),
				'ui_related_terms'        => array( 'default' => '관련 용어', 'label' => 'UI · 관련 용어 섹션 제목', 'type' => 'text' ),
				'ui_back_to_news'         => array( 'default' => '← 소식 목록으로', 'label' => 'UI · 소식 목록으로 버튼', 'type' => 'text' ),
				'ui_back_to_enc'          => array( 'default' => '← 전체 치과사전으로', 'label' => 'UI · 사전으로 돌아가기 버튼', 'type' => 'text' ),
				'ui_book_now'             => array( 'default' => '📅 상담 예약하기', 'label' => 'UI · 상담 예약 버튼 (사전 단일글 등)', 'type' => 'text' ),
				'ui_original_naver_tpl'   => array( 'default' => '이 글의 원문은 {link} 에서도 보실 수 있습니다.', 'label' => 'UI · 네이버 원문 안내 ({link} 토큰)', 'type' => 'text' ),
				'ui_original_naver_link'  => array( 'default' => '네이버 블로그', 'label' => 'UI · 네이버 원문 링크 텍스트', 'type' => 'text' ),
				'ui_empty_content'        => array( 'default' => '콘텐츠 준비 중입니다.', 'label' => 'UI · 빈 페이지 fallback', 'type' => 'text' ),
				'ui_see_more'             => array( 'default' => '자세히 보기', 'label' => 'UI · 카드 자세히 보기 링크', 'type' => 'text' ),
			),
		),

		/* ─── v3.38.1 · SEO 메타 태그 · 페이지별 title / description / keywords ─── */
		'seo' => array(
			'title'  => 'SEO 메타 태그 (검색·SNS 공유)',
			'fields' => array(
				// 홈
				'seo_home_title' => array( 'default' => '천안·아산 치과 | 천안 임플란트·투명교정·자연치아살리기', 'label' => '홈 · 제목 (뒤에 병원명 자동)', 'type' => 'text' ),
				'seo_home_desc'  => array( 'default' => '천안 만남로 1995년 개원 30여년 한자리 진료. 천안·아산 임플란트·투명교정·라미네이트·사랑니 발치·턱관절 치료. 분야별 전문 의료진 협진·CBCT 디지털 가이드·월·화·수·금 야간진료(20:30).', 'label' => '홈 · 설명', 'type' => 'textarea' ),
				'seo_home_kw'    => array( 'default' => '천안 치과, 아산 치과, 천안치과, 아산치과, 천안 임플란트, 아산 임플란트, 천안 투명교정, 아산 투명교정, 천안 라미네이트, 아산 라미네이트, 천안 자연치아 살리기, 아산 자연치아 살리기, 천안 사랑니 발치, 아산 사랑니 발치, 천안 턱관절, 아산 턱관절, 천안 신경치료, 아산 신경치료, 천안 미백, 아산 미백, 천안 치과병원, 아산 치과병원, 천안 만남로 치과, 천안 신부동 치과, 천안 동남구 치과, 한아의료재단, 문치과병원, 슈어스마일 투명교정', 'label' => '홈 · 키워드', 'type' => 'textarea' ),
				// 임플란트
				'seo_implant_title' => array( 'default' => '천안·아산 임플란트 | CBCT 디지털 가이드 수술', 'label' => '임플란트 센터 · 제목', 'type' => 'text' ),
				'seo_implant_desc'  => array( 'default' => '천안·아산 임플란트 시작가 85만원~. 천안 만남로 30여년 임상, 분야별 전문 의료진 협진, CBCT 디지털 가이드 수술, 전신질환 안심 진료. 자체 한아 임플란트 보철연구소.', 'label' => '임플란트 · 설명', 'type' => 'textarea' ),
				'seo_implant_kw'    => array( 'default' => '천안 임플란트, 아산 임플란트, 천안 임플란트 가격, 아산 임플란트 가격, 천안 임플란트 전문, 아산 임플란트 전문, 천안 디지털 임플란트, 아산 디지털 임플란트, 천안 골이식 임플란트, 아산 골이식 임플란트, 천안 노인 임플란트, 아산 노인 임플란트, 천안 만남로 임플란트', 'label' => '임플란트 · 키워드', 'type' => 'textarea' ),
				// 투명교정
				'seo_ortho_title' => array( 'default' => '천안·아산 투명교정 | 슈어스마일 SureSmile', 'label' => '투명교정 센터 · 제목', 'type' => 'text' ),
				'seo_ortho_desc'  => array( 'default' => '천안·아산 투명교정 슈어스마일 (Dentsply Sirona). 천안 만남로 치과교정과 전문의 진료, AI 3D 시뮬레이션, Lite·Standard·Advanced 단계별 합리적 가격(190만원~).', 'label' => '투명교정 · 설명', 'type' => 'textarea' ),
				'seo_ortho_kw'    => array( 'default' => '천안 투명교정, 아산 투명교정, 천안 슈어스마일, 아산 슈어스마일, 천안 교정, 아산 교정, 천안 치아교정, 아산 치아교정, 천안 성인교정, 아산 성인교정, 천안 부분교정, 아산 부분교정, 천안 투명교정 가격', 'label' => '투명교정 · 키워드', 'type' => 'textarea' ),
				// 자연치아 살리기
				'seo_preservation_title' => array( 'default' => '천안·아산 자연치아 살리기 | 신경치료·재근관치료·치주치료', 'label' => '자연치아 · 제목', 'type' => 'text' ),
				'seo_preservation_desc'  => array( 'default' => '천안·아산 자연치아 살리기. 발치보다 보존 우선 — 신경치료·재근관치료·치주치료로 자연치아 최대한 살리는 천안 만남로 치과병원.', 'label' => '자연치아 · 설명', 'type' => 'textarea' ),
				'seo_preservation_kw'    => array( 'default' => '천안 신경치료, 아산 신경치료, 천안 자연치아 살리기, 아산 자연치아 살리기, 천안 치주치료, 아산 치주치료, 천안 잇몸치료, 아산 잇몸치료, 천안 재근관치료, 천안 충치치료, 아산 충치치료', 'label' => '자연치아 · 키워드', 'type' => 'textarea' ),
				// 턱관절
				'seo_tmj_title' => array( 'default' => '천안·아산 턱관절 치료 | 통증·소리·개구장애', 'label' => '턱관절 · 제목', 'type' => 'text' ),
				'seo_tmj_desc'  => array( 'default' => '천안·아산 턱관절 클리닉. 턱 소리·통증·개구장애 진단 및 치료. 천안 만남로 치과병원에서 보존적 치료 우선, 11F 교정과 협진으로 교합 안정화.', 'label' => '턱관절 · 설명', 'type' => 'textarea' ),
				'seo_tmj_kw'    => array( 'default' => '천안 턱관절, 아산 턱관절, 천안 턱관절 치료, 아산 턱관절 치료, 천안 턱 소리, 천안 이갈이, 아산 이갈이, 천안 턱관절 보톡스, 천안 스플린트', 'label' => '턱관절 · 키워드', 'type' => 'textarea' ),
				// 사랑니 발치
				'seo_wisdom_title' => array( 'default' => '천안·아산 사랑니 발치 | CBCT 안전 진단', 'label' => '사랑니 · 제목', 'type' => 'text' ),
				'seo_wisdom_desc'  => array( 'default' => '천안·아산 사랑니 발치. CBCT 3D 진단으로 신경 손상 위험 최소화, 매복 사랑니까지 천안 만남로 구강악안면외과 진료. 진정요법 가능.', 'label' => '사랑니 · 설명', 'type' => 'textarea' ),
				'seo_wisdom_kw'    => array( 'default' => '천안 사랑니 발치, 아산 사랑니 발치, 천안 매복 사랑니, 아산 매복 사랑니, 천안 사랑니, 아산 사랑니, 천안 구강외과, 아산 구강외과', 'label' => '사랑니 · 키워드', 'type' => 'textarea' ),
				// 심미치료
				'seo_aesthetic_title' => array( 'default' => '천안·아산 라미네이트·미백 | 자연스러운 미소', 'label' => '심미치료 · 제목', 'type' => 'text' ),
				'seo_aesthetic_desc'  => array( 'default' => '천안·아산 라미네이트·치아미백·심미보철. 최소 삭제 보존적 접근, 자연스러운 미소를 만드는 천안 만남로 심미치료 전문.', 'label' => '심미치료 · 설명', 'type' => 'textarea' ),
				'seo_aesthetic_kw'    => array( 'default' => '천안 라미네이트, 아산 라미네이트, 천안 미백, 아산 미백, 천안 치아미백, 아산 치아미백, 천안 심미치료, 아산 심미치료, 천안 라미네이트 가격, 천안 앞니 라미네이트', 'label' => '심미치료 · 키워드', 'type' => 'textarea' ),
				// 비용 안내
				'seo_pricing_title' => array( 'default' => '천안·아산 치과 비용 안내 | 정직한 진료비', 'label' => '비용 안내 · 제목', 'type' => 'text' ),
				'seo_pricing_desc'  => array( 'default' => '천안·아산 치과 비용 — 임플란트·투명교정·라미네이트·사랑니 발치 비용 안내. 사전 견적서 제공, 시작 후 추가 비용 0원.', 'label' => '비용 안내 · 설명', 'type' => 'textarea' ),
				'seo_pricing_kw'    => array( 'default' => '천안 치과 비용, 아산 치과 비용, 천안 임플란트 비용, 아산 임플란트 비용, 천안 투명교정 비용, 아산 투명교정 비용, 천안 라미네이트 비용, 천안 사랑니 비용, 천안 치과 가격, 아산 치과 가격', 'label' => '비용 안내 · 키워드', 'type' => 'textarea' ),
				// 의료진
				'seo_doctors_title' => array( 'default' => '천안·아산 치과 의료진 | 분야별 전문 의료진 협진', 'label' => '의료진 · 제목', 'type' => 'text' ),
				'seo_doctors_desc'  => array( 'default' => '천안 만남로 문치과병원 의료진 — 보철·보존·예방·임플란트·스마일디자인·구강외과·구강내과·턱관절·교정·소아·치주 분야별 전문 의료진이 한 케이스를 함께 봅니다.', 'label' => '의료진 · 설명', 'type' => 'textarea' ),
				'seo_doctors_kw'    => array( 'default' => '천안 치과 의사, 아산 치과 의사, 천안 치과 의료진, 아산 치과 의료진, 천안 임플란트 전문의, 아산 임플란트 전문의, 천안 교정 전문의, 아산 교정 전문의, 문치과병원 원장', 'label' => '의료진 · 키워드', 'type' => 'textarea' ),
				// 오시는 길
				'seo_location_title' => array( 'default' => '천안 만남로 치과 — 오시는 길 · 주차 · 진료시간', 'label' => '오시는 길 · 제목', 'type' => 'text' ),
				'seo_location_desc'  => array( 'default' => '천안 동남구 만남로 52 문타워 9·10·11·13층. 천안종합·고속버스터미널 도보 5분, 천안역 버스 10분. 본원 지하 기계식 주차장 무료.', 'label' => '오시는 길 · 설명', 'type' => 'textarea' ),
				'seo_location_kw'    => array( 'default' => '천안 만남로 치과, 천안 신부동 치과, 천안 동남구 치과, 천안 버스터미널 치과, 문치과병원 위치', 'label' => '오시는 길 · 키워드', 'type' => 'textarea' ),
				// 상담예약
				'seo_reservation_title' => array( 'default' => '천안·아산 치과 예약 — 네이버 예약·카카오톡 상담', 'label' => '상담예약 · 제목', 'type' => 'text' ),
				'seo_reservation_desc'  => array( 'default' => '천안 만남로 문치과병원 예약. 네이버 예약 24시간, 전화·카카오톡 상담. 월·화·수·금 야간진료 20:30까지 (목 18:30·토 14:00).', 'label' => '상담예약 · 설명', 'type' => 'textarea' ),
				'seo_reservation_kw'    => array( 'default' => '천안 치과 예약, 아산 치과 예약, 천안 치과 상담, 아산 치과 상담, 천안 만남로 치과 예약, 문치과병원 예약', 'label' => '상담예약 · 키워드', 'type' => 'textarea' ),
				// 30년 발자취
				'seo_history_title' => array( 'default' => '문치과병원 30여년의 발자취 | 천안 만남로 1995년 개원', 'label' => '30년 발자취 · 제목', 'type' => 'text' ),
				'seo_history_desc'  => array( 'default' => '천안 만남로에서 1995년부터 30여년 한자리 진료. 한아의료재단 비영리 법인의 30여년 발자취와 핵심 가치.', 'label' => '30년 발자취 · 설명', 'type' => 'textarea' ),
				'seo_history_kw'    => array( 'default' => '문치과병원 역사, 한아의료재단, 천안 30년 치과, 아산 30년 치과, 천안 만남로 치과 1995', 'label' => '30년 발자취 · 키워드', 'type' => 'textarea' ),
				// 기술력·시설
				'seo_facility_title' => array( 'default' => '천안·아산 치과 시설 | CBCT·디지털 가이드·원내 기공실', 'label' => '기술력·시설 · 제목', 'type' => 'text' ),
				'seo_facility_desc'  => array( 'default' => '천안 만남로 문치과병원 기술력·시설 — 의료기관 종별, 9·10·11·13F 4개 층 통합 진료센터, 디지털 진단·자체 보철 제작·전신질환 대응.', 'label' => '기술력·시설 · 설명', 'type' => 'textarea' ),
				'seo_facility_kw'    => array( 'default' => '천안 치과 시설, 아산 치과 시설, 천안 디지털 치과, 아산 디지털 치과, 천안 CBCT, 아산 CBCT, 천안 임플란트 가이드, 천안 치과 장비', 'label' => '기술력·시설 · 키워드', 'type' => 'textarea' ),
				// FAQ
				'seo_faq_title' => array( 'default' => '천안·아산 치과 자주 묻는 질문 — 예약·비용·진료 안내', 'label' => 'FAQ · 제목', 'type' => 'text' ),
				'seo_faq_desc'  => array( 'default' => '천안 만남로 문치과병원 자주 묻는 질문 — 예약·비용·진료·전신질환 대응·주차·진료시간 등.', 'label' => 'FAQ · 설명', 'type' => 'textarea' ),
				'seo_faq_kw'    => array( 'default' => '천안 치과 FAQ, 아산 치과 FAQ, 천안 치과 문의, 아산 치과 문의, 문치과병원 FAQ', 'label' => 'FAQ · 키워드', 'type' => 'textarea' ),
				// 지역 페이지 템플릿 (토큰: {region} {minutes} {km})
				'seo_region_title_tpl' => array( 'default' => '{region}에서 천안·아산 치과 | {region} 임플란트·{region} 교정', 'label' => '지역 · 제목 템플릿 ({region})', 'type' => 'text' ),
				'seo_region_desc_tpl'  => array( 'default' => '{region}에서 천안 만남로 문치과병원까지 자동차 약 {minutes}분 ({km}km). {region} 환자분께 천안·아산 임플란트·투명교정·라미네이트 진료. 1995년부터 30여년 한자리.', 'label' => '지역 · 설명 템플릿 ({region}·{minutes}·{km})', 'type' => 'textarea' ),
				'seo_region_kw_tpl'    => array( 'default' => '{region} 치과, {region} 임플란트, {region} 교정, {region} 투명교정, {region} 라미네이트, {region} 사랑니 발치, {region} 신경치료, {region} 치과 추천, 천안 치과, 천안 임플란트, 문치과병원', 'label' => '지역 · 키워드 템플릿 ({region})', 'type' => 'textarea' ),
				// 일반 페이지 fallback ({title} 토큰)
				'seo_page_title_tpl' => array( 'default' => '{title} — {site} (천안·아산 치과)', 'label' => '기타 페이지 · 제목 템플릿 ({title}·{site})', 'type' => 'text' ),
				'seo_page_desc_tpl'  => array( 'default' => '천안 만남로 {site} — {title}. 1995년부터 천안·아산에서 진료해온 종합 치과병원.', 'label' => '기타 페이지 · 설명 템플릿', 'type' => 'textarea' ),
				'seo_page_kw_tpl'    => array( 'default' => '천안 치과, 아산 치과, 천안 {title}, 아산 {title}, {site}', 'label' => '기타 페이지 · 키워드 템플릿', 'type' => 'textarea' ),
				// 단일 글(single post) fallback
				'seo_single_title_tpl' => array( 'default' => '{title} — {site} (천안·아산 치과)', 'label' => '소식 글 · 제목 템플릿 ({title}·{site})', 'type' => 'text' ),
				'seo_single_desc_tpl'  => array( 'default' => '천안 만남로 {site} — {title}', 'label' => '소식 글 · 설명 fallback (요약 없을 때)', 'type' => 'text' ),
				'seo_single_kw_tpl'    => array( 'default' => '천안 치과, 아산 치과, 천안 치과 소식, 아산 치과 소식, {site}', 'label' => '소식 글 · 키워드 템플릿', 'type' => 'textarea' ),
				// 제목 접미어 (모든 페이지 뒤에 자동 추가)
				'seo_title_suffix'   => array( 'default' => ' — {site}', 'label' => '제목 접미어 (모든 페이지에 자동, {site} 토큰)', 'type' => 'text' ),
			),
		),

		/* ─── v3.38.0 · 예약 폼 · 알림·에러·이메일·성공화면 문구 ─── */
		'reservation_form' => array(
			'title'  => '예약 폼 — 알림·에러·이메일·성공화면 문구',
			'fields' => array(
				// 서비스(진료항목) 옵션 · pipe 3개 (value|title|desc)
				'res_services' => array(
					'default' => "임플란트|임플란트|치아 식립·뼈이식·재식립\n투명교정|투명교정|슈어스마일·설측·일반교정\n자연치아 살리기|자연치아 살리기|신경·근관·치근단 보존\n턱관절 클리닉|턱관절 클리닉|통증·소리·이갈이\n사랑니 발치|사랑니 발치|매복 사랑니 안전 발치\n심미치료|심미치료|라미네이트·미백·보철\n소아·예방진료|소아·예방진료|아이 치아·정기 검진\n일반/기타|일반/기타 상담|충치·잇몸·스케일링 등",
					'label'   => '진료항목 옵션 (한 줄에 하나 · value|표시명|설명)',
					'type'    => 'textarea',
				),
				// 서버측 응답 메시지
				'res_msg_throttle'      => array( 'default' => '잠시 후 다시 시도해주세요.', 'label' => '서버 · 봇/과다 요청 차단 메시지', 'type' => 'text' ),
				'res_msg_required'      => array( 'default' => '필수 항목이 비어 있습니다: {fields}', 'label' => '서버 · 필수 누락 안내 ({fields} 자동 치환)', 'type' => 'text' ),
				'res_msg_phone_invalid' => array( 'default' => '연락처 형식이 올바르지 않습니다.', 'label' => '서버 · 전화번호 형식 오류', 'type' => 'text' ),
				'res_field_service'     => array( 'default' => '진료항목', 'label' => '필수 항목명 · 진료항목', 'type' => 'text' ),
				'res_field_date'        => array( 'default' => '희망 날짜', 'label' => '필수 항목명 · 희망 날짜', 'type' => 'text' ),
				'res_field_time'        => array( 'default' => '희망 시간', 'label' => '필수 항목명 · 희망 시간', 'type' => 'text' ),
				'res_field_name'        => array( 'default' => '성함', 'label' => '필수 항목명 · 성함', 'type' => 'text' ),
				'res_field_phone'       => array( 'default' => '연락처', 'label' => '필수 항목명 · 연락처', 'type' => 'text' ),
				'res_field_privacy'     => array( 'default' => '개인정보 동의', 'label' => '필수 항목명 · 개인정보 동의', 'type' => 'text' ),
				// 관리자 이메일 (병원이 받는 알림)
				'res_email_subject_tpl' => array( 'default' => '[문치과 상담예약] {name} · {date} {time}', 'label' => '관리자 이메일 · 제목 ({name}·{date}·{time})', 'type' => 'text' ),
				'res_email_intro'       => array( 'default' => '새 상담예약이 접수되었습니다.', 'label' => '관리자 이메일 · 인트로', 'type' => 'text' ),
				'res_email_lbl_no'      => array( 'default' => '예약번호', 'label' => '이메일 라벨 · 예약번호', 'type' => 'text' ),
				'res_email_lbl_name'    => array( 'default' => '성함',     'label' => '이메일 라벨 · 성함', 'type' => 'text' ),
				'res_email_lbl_phone'   => array( 'default' => '연락처',   'label' => '이메일 라벨 · 연락처', 'type' => 'text' ),
				'res_email_lbl_service' => array( 'default' => '진료항목', 'label' => '이메일 라벨 · 진료항목', 'type' => 'text' ),
				'res_email_lbl_dt'      => array( 'default' => '희망일시', 'label' => '이메일 라벨 · 희망일시', 'type' => 'text' ),
				'res_email_lbl_mkt'     => array( 'default' => '마케팅 수신 동의', 'label' => '이메일 라벨 · 마케팅', 'type' => 'text' ),
				'res_email_yes'         => array( 'default' => '예',       'label' => '이메일 · 예',    'type' => 'text' ),
				'res_email_no'          => array( 'default' => '아니오',   'label' => '이메일 · 아니오', 'type' => 'text' ),
				'res_email_note_head'   => array( 'default' => '--- 증상/문의 ---', 'label' => '이메일 · 증상 섹션 헤더', 'type' => 'text' ),
				'res_email_note_empty'  => array( 'default' => '(없음)',   'label' => '이메일 · 증상 없음 표시', 'type' => 'text' ),
				'res_email_received'    => array( 'default' => '접수시각', 'label' => '이메일 · 접수시각 라벨', 'type' => 'text' ),
				// 클라이언트 alert 메시지 (JS)
				'res_alert_svc'         => array( 'default' => '진료항목을 선택해주세요.', 'label' => 'JS · 진료항목 미선택 alert', 'type' => 'text' ),
				'res_alert_date'        => array( 'default' => '희망 날짜를 선택해주세요.', 'label' => 'JS · 날짜 미선택 alert', 'type' => 'text' ),
				'res_alert_time'        => array( 'default' => '희망 시간을 선택해주세요.', 'label' => 'JS · 시간 미선택 alert', 'type' => 'text' ),
				'res_alert_name'        => array( 'default' => '성함을 입력해주세요.', 'label' => 'JS · 성함 미입력 alert', 'type' => 'text' ),
				'res_alert_phone'       => array( 'default' => '연락처를 입력해주세요.', 'label' => 'JS · 연락처 미입력 alert', 'type' => 'text' ),
				'res_alert_phone_fmt'   => array( 'default' => '연락처 형식이 올바르지 않습니다. (예: 010-1234-5678)', 'label' => 'JS · 연락처 형식 alert', 'type' => 'text' ),
				'res_alert_privacy'     => array( 'default' => '개인정보 처리방침 동의가 필요합니다.', 'label' => 'JS · 개인정보 동의 alert', 'type' => 'text' ),
				'res_hint_date_default' => array( 'default' => '선택하신 요일에 따라 가능한 시간이 표시됩니다.', 'label' => 'JS · 날짜 힌트 (초기)', 'type' => 'text' ),
				'res_hint_date_closed'  => array( 'default' => '⚠️ 선택하신 날짜는 휴진일입니다. 다른 날짜를 선택해주세요.', 'label' => 'JS · 휴진일 안내', 'type' => 'text' ),
				'res_hint_date_open'    => array( 'default' => '✓ {day}요일 진료 가능 시간: 09:00 – {until}', 'label' => 'JS · 날짜 힌트 ({day}·{until})', 'type' => 'text' ),
				'res_btn_sending'       => array( 'default' => '전송 중...', 'label' => 'JS · 전송 중 버튼 라벨', 'type' => 'text' ),
				'res_btn_submit'        => array( 'default' => '예약 신청',  'label' => 'JS · 기본 버튼 라벨', 'type' => 'text' ),
				'res_alert_fail'        => array( 'default' => '예약 전송에 실패했습니다.', 'label' => 'JS · 실패 alert', 'type' => 'text' ),
				'res_alert_network'     => array( 'default' => '네트워크 오류 — 잠시 후 다시 시도해주세요.', 'label' => 'JS · 네트워크 오류', 'type' => 'text' ),
				// 성공화면
				'res_success_title'     => array( 'default' => '예약 신청이 완료되었습니다!', 'label' => '성공화면 · 제목', 'type' => 'text' ),
				'res_success_lead'      => array( 'default' => '담당자가 확인 후 빠른 시간 내에 연락드리겠습니다.', 'label' => '성공화면 · 설명', 'type' => 'textarea' ),
				'res_success_lbl_no'    => array( 'default' => '예약번호',  'label' => '성공화면 라벨 · 예약번호', 'type' => 'text' ),
				'res_success_lbl_svc'   => array( 'default' => '진료항목',  'label' => '성공화면 라벨 · 진료항목', 'type' => 'text' ),
				'res_success_lbl_dt'    => array( 'default' => '희망일시',  'label' => '성공화면 라벨 · 희망일시', 'type' => 'text' ),
				'res_success_lbl_name'  => array( 'default' => '예약자',    'label' => '성공화면 라벨 · 예약자', 'type' => 'text' ),
				'res_success_hint'      => array( 'default' => '예약 확정 전 변경이 필요하시면 전화 또는 카카오톡으로 연락주세요.', 'label' => '성공화면 · 하단 힌트', 'type' => 'textarea' ),
				'res_success_btn_kakao' => array( 'default' => '💬 카카오톡 친구 추가', 'label' => '성공화면 · 카카오 버튼', 'type' => 'text' ),
				'res_success_btn_home'  => array( 'default' => '홈으로',    'label' => '성공화면 · 홈 버튼', 'type' => 'text' ),
			),
		),

	);
}

/**
 * 비용 안내 페이지 콘텐츠 필드 정의.
 */
function moondental_pricing_content_fields() {
	$default_implant = "스테리오스 임플란트 | 85만~95만 원 | 국산 픽스처\n오스템 임플란트 | 90만~100만 원 | 국산 픽스처\n포인트 임플란트 (UV 활성) | 95만~105만 원 | 국산 픽스처\n메가젠 임플란트 (프리미엄) | 100만~110만 원 | 국산 픽스처\n임플란트 크라운 (PFM) | 치아당 45만 원 | 대중 보급형\n임플란트 크라운 (지르코니아·구치) | 치아당 55만 원 | 심미·강도 균형\n임플란트 크라운 (지르코니아·전치) | 치아당 60만 원 | 심미 우선\n임플란트 크라운 (골드) | 치아당 105만 원 | 교합 안정\n임플란트 임시치아 (고정형) | 치아당 15만 원 | 골 유합 대기 중\n뼈이식 (간단·PRP·PRF 포함) | 30만 원 | 뼈가 부족할 때\n뼈이식 (상악동 거상술 등) | 50만 원 | 윗턱 골량 부족\n네비게이션(가이드) 수술 | 사분면당 10만 원 | 정밀 수술 옵션\n임플란트 PDRN 주사 | 부위당 5만 원 | 골 재생 촉진";

	$default_ortho = "교정 진단비 (브라켓·투명) | 회당 20만 원 | 정밀 진단·시뮬레이션\n소아 교정 (브라켓, 1차) | 150만~200만 원 | 만 7~10세 골든타임\n부분 교정 (앞니 등) | 250만~300만 원 | 국소 부정만\n투명 교정 · SureSmile 부분 | 190만 원 | 양악 합산 8 stage 이하\n투명 교정 · SureSmile 일반 | 320만 원 | 양악 합산 9~16 stage\n투명 교정 · SureSmile 전체 | 550만 원 | 양악 합산 17 stage 이상\n금속(Metal) 브라켓 교정 | 420만 원 | 전통 교정\n심미(레진) 브라켓 교정 | 450만 원 | 브라켓 색 자연스럽게\n자가결찰 교정 (A-Line) | 500만 원 | 와이어 결찰 자동\n악궁 확장장치 | 악당 50만 원 | 성장기 상악·하악 확장\n페이스 마스크 | 200만 원 | 성장기 골격 교정\n미니 스크류 | 개당 5만 원 | 고정원 보강\n유지장치 · 설측 LFR (상하악) | 악당 15만 원 | 필수 착용\n유지장치 · 투명 리테이너 | 악당 15만 원 | 야간 착용\n유지장치 재제작 (본원) | 15만 원 | \n교정 후 발치 (본원 교정) | 치아당 5만 원 | \n재교정 | 50만 원 | ";

	$default_crown = "PFM 크라운 (도자기+금속) | 치아당 45만 원 | 대중 보급형\n지르코니아 크라운 (어금니) | 치아당 55만 원 | 강도·심미 균형\n지르코니아 크라운 (앞니) | 치아당 60만 원 | 심미 우선\n골드 크라운 | 치아당 95만 원 | 교합 안정·내구성\n임시 크라운 | 치아당 5만 원 | 보철 대기 중\n세라믹 인레이 (1면) | 치아당 35만 원 | 심미 충전\n세라믹 인레이 (2면 이상) | 치아당 40만 원 | \n골드 인레이 (1면) | 치아당 55만 원 | 내구성 우선\n골드 인레이 (2면) | 치아당 65만 원 | \n골드 인레이 (3면) | 치아당 75만 원 | \n포스트 · 스크류(다이렉트) | 20만 원 | \n포스트 · DT post(세라믹) | 30만 원 | \n캐스트 포스트 (전치·소구치) | 30만 원 | \n캐스트 포스트 (구치·기둥 2개) | 35만 원 | \n캐스트 포스트 (구치·기둥 3개) | 40만 원 | \n틀니 (부분/전체) | 150만 원 | 진단 후 크라운 별도";

	$default_decay = "레진 (1면) | 치아당 10만 원 | 교두당 5만 가산\n레진 (1면·광범위) | 치아당 15만 원 | 교두당 5만 가산\n레진 (2면) | 치아당 15만 원 | 교두당 5만 가산\n레진 (2면·MO/DO 포함) | 치아당 30만 원 | 교두당 5만 가산\n레진 (3면 이상) | 치아당 30만 원 | 교두당 5만 가산\n레진 (3면 이상·MO/DO 포함) | 치아당 35만 원 | 교두당 5만 가산\n레진 (3면 이상·MOD 포함) | 치아당 50만 원 | 최대 범위\n레진 (앞니 사이 틈 메우기) | 치아당 25만 원 | 정중이개\n레진 (반점치) | 치아당 20만 원 | 심미 보완\n레진 (치경부 마모증) | 치아당 8만 원 | 잇몸 경계\n레진 비니어 (앞니 심미) | 치아당 35만 원 | 변색 보완\n레진 코어 (크라운 토대) | 치아당 8만 원 | 보철 전 기둥\n신경치료 후 레진 충전 | 8만 원 | 신경치료 마감\n신경치료 후 럭사코어 | 8만 원 | 신경치료 마감\n유치 레진 (1면) | 치아당 8만 원 | 소아용\n유치 레진 (2면 이상) | 치아당 10만 원 | 소아용";

	$default_aesthetic = "라미네이트 (앞니 심미) | 치아당 66만 원 | 부가세 포함\n자가 치아미백 · Omnivac 4주분 | 33만 원 | 집에서 사용, 부가세 포함\n자가미백 · Omnivac 장치 추가 | 5만 원 | \n자가미백 · 약제 (1주치) | 5만 원 | \n자가미백 · 약제 (4주치) | 15만 원 | \n전문가 치아미백 · 1-day (2회) | 33만 원 | 부가세 포함\n전문가 치아미백 · 1-day (3회) | 44만 원 | 부가세 포함\n전문가 치아미백 · 2-day (총 3회) | 44만 원 | 부가세 포함\n전문가 치아미백 · 2-day (총 4회) | 55만 원 | 부가세 포함\n잇몸 미백 | 악당 20만 원 | 잇몸 색 개선·부가세 별도\n거미스마일 (레이저 잇몸 성형) | 치아당 20만 원 | 잇몸 라인 조정";

	$default_kids = "실란트 (보험·만 18세 이하 대구치) | 본인부담 21,700 원~ | 우식 없는 제1,2 대구치\n실란트 (비급여·작은어금니) | 치아당 3만 원 | 예방 시술\n불소 도포 (전악) | 3만 원 | 치아 강화·충치 예방\n지각과민 처치 (6치당) | 본인부담 8,700~13,500 원 | 시린 이 완화\n덴탈스파 (전문가 칫솔질·잇몸마사지) | 5만 원 | \n공간유지장치 Crown&loop | 공간당 20만 원 | 유치 조기 상실 시\n공간유지장치 Band&loop | 공간당 15만 원 | \nSS 크라운 · 유치 (본떠서 제작) | 치아당 20만 원 | \nSS 크라운 · 영구치 (본떠서 제작) | 치아당 25만 원 | \nSP 크라운 · 유치 (기성) | 치아당 15만 원 | \nSP 크라운 · 영구치 (기성) | 치아당 20만 원 | ";

	$default_tmj = "턱관절 보톡스 | 20만 원 | 이갈이·교근 통증\n턱관절강 PDRN 주사 | 20만 원 | 관절 염증 완화\n턱관절 스플린트 (하드) | 100만 원 | 야간 착용\n나이트가드 (이갈이 방지·소프트) | 30만 원 | 야간 착용";

	$default_covered = "스케일링 (만 19세 이상, 연 1회)\n레진 충전 (만 12세 이하 영구치)\n신경치료 · 치주치료 · 발치\n사랑니 발치 (매복 포함)\nX-ray · 파노라마 · CBCT\n틀니 (만 65세 이상, 7년 1회)\n임플란트 (만 65세 이상, 평생 2개, 본인부담 30%)";

	$default_uncovered = "임플란트 (만 65세 미만 전체)\n교정 (메탈·세라믹·투명·설측)\n심미 라미네이트 · 미백\n심미 보철 (지르코니아·올세라믹)\n심미 인레이 / 온레이\n치아 성형 (레진 심미)\n턱관절 보톡스 · 스플린트 일부";

	return array(

		/* ─── Hero ─── */
		'pricing_hero' => array(
			'title'  => '비용 페이지 — Hero',
			'fields' => array(
				'price_hero_chip'    => array( 'default' => 'BILLING TRANSPARENCY · 비용 안내', 'label' => '상단 chip', 'type' => 'text' ),
				'price_hero_title_a' => array( 'default' => '처음 들으신 견적,', 'label' => '제목 1행', 'type' => 'text' ),
				'price_hero_title_b' => array( 'default' => '치료가 끝날 때까지', 'label' => '제목 2행 (강조)', 'type' => 'text' ),
				'price_hero_title_c' => array( 'default' => '그대로.', 'label' => '제목 2행 끝부분', 'type' => 'text' ),
				'price_hero_lead'    => array( 'default' => '문치과병원은 30여년 동안 정직한 진료비를 약속해왔습니다. 불필요한 치료를 권하지 않고, 시작 후 추가 비용이 발생하지 않습니다.', 'label' => '설명', 'type' => 'textarea' ),
				'price_hero_btn1'    => array( 'default' => '📞 무료 비용 상담', 'label' => 'CTA 1 라벨 (전화번호 자동 추가)', 'type' => 'text' ),
				'price_hero_btn2'    => array( 'default' => '🟢 네이버 예약', 'label' => 'CTA 2 라벨', 'type' => 'text' ),
			),
		),

		/* ─── 약속 카드 ─── */
		'pricing_promise' => array(
			'title'  => '비용 페이지 — 3가지 약속 카드',
			'fields' => array(
				'price_promise_year'  => array( 'default' => 'SINCE 1995', 'label' => '연도 라벨', 'type' => 'text' ),
				'price_promise_title' => array( 'default' => '문치과의 3가지 약속', 'label' => '카드 제목', 'type' => 'text' ),

				'price_promise_1_title' => array( 'default' => '견적 그대로', 'label' => '①번 — 제목', 'type' => 'text' ),
				'price_promise_1_desc'  => array( 'default' => '치료 시작 후 추가 비용 0원', 'label' => '①번 — 설명', 'type' => 'text' ),

				'price_promise_2_title' => array( 'default' => '모든 비급여 사전 안내', 'label' => '②번 — 제목', 'type' => 'text' ),
				'price_promise_2_desc'  => array( 'default' => '한 항목도 빠뜨리지 않고 미리', 'label' => '②번 — 설명', 'type' => 'text' ),

				'price_promise_3_title' => array( 'default' => '치아 보존이 우선', 'label' => '③번 — 제목', 'type' => 'text' ),
				'price_promise_3_desc'  => array( 'default' => '발치보다 살리기를 먼저 고민', 'label' => '③번 — 설명', 'type' => 'text' ),
			),
		),

		/* ─── 비용 확정 4단계 ─── */
		'pricing_steps' => array(
			'title'  => '비용 페이지 — 비용 확정 4단계',
			'fields' => array(
				'price_steps_eyebrow' => array( 'default' => 'Process', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'price_steps_title'   => array( 'default' => '비용이 확정되는 4단계', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'price_steps_lead'    => array( 'default' => '상담 → 진단 → 견적 → 동의 후 치료. 각 단계마다 환자분이 충분히 검토하실 수 있습니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

				'price_step_1_icon'  => array( 'default' => '💬', 'label' => '01 — 아이콘', 'type' => 'text' ),
				'price_step_1_title' => array( 'default' => '편안한 상담', 'label' => '01 — 제목', 'type' => 'text' ),
				'price_step_1_desc'  => array( 'default' => '증상·예산·일정·우려를 충분히 듣습니다. 전화·카톡·내원 모두 가능.', 'label' => '01 — 설명', 'type' => 'textarea' ),

				'price_step_2_icon'  => array( 'default' => '🔬', 'label' => '02 — 아이콘', 'type' => 'text' ),
				'price_step_2_title' => array( 'default' => '정밀 진단', 'label' => '02 — 제목', 'type' => 'text' ),
				'price_step_2_desc'  => array( 'default' => 'X-ray · CT · 구강 검사로 정확한 상태를 파악합니다.', 'label' => '02 — 설명', 'type' => 'textarea' ),

				'price_step_3_icon'  => array( 'default' => '📄', 'label' => '03 — 아이콘', 'type' => 'text' ),
				'price_step_3_title' => array( 'default' => '상세 견적서', 'label' => '03 — 제목', 'type' => 'text' ),
				'price_step_3_desc'  => array( 'default' => '치료 옵션별 비용·기간·과정을 문서로 안내드립니다.', 'label' => '03 — 설명', 'type' => 'textarea' ),

				'price_step_4_icon'  => array( 'default' => '✅', 'label' => '04 — 아이콘', 'type' => 'text' ),
				'price_step_4_title' => array( 'default' => '동의 후 치료', 'label' => '04 — 제목', 'type' => 'text' ),
				'price_step_4_desc'  => array( 'default' => '충분히 검토하시고 동의하신 항목만 진행. 추가 비용 0원.', 'label' => '04 — 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 가격 정책 4 ─── */
		'pricing_policy' => array(
			'title'  => '비용 페이지 — 가격 정책 4 카드',
			'fields' => array(
				'price_policy_eyebrow' => array( 'default' => 'Our Policy', 'label' => 'eyebrow', 'type' => 'text' ),
				'price_policy_title'   => array( 'default' => '비용 결정의 4가지 원칙', 'label' => '제목', 'type' => 'text' ),

				'price_policy_1_title' => array( 'default' => '환자 중심 결정', 'label' => '01 — 제목', 'type' => 'text' ),
				'price_policy_1_desc'  => array( 'default' => '비용보다 환자분의 치아 보존이 먼저입니다. 발치보다 보존, 임플란트보다 신경치료를 우선 검토합니다.', 'label' => '01 — 설명', 'type' => 'textarea' ),

				'price_policy_2_title' => array( 'default' => '사전 견적서 제공', 'label' => '02 — 제목', 'type' => 'text' ),
				'price_policy_2_desc'  => array( 'default' => '치료 시작 전에 옵션별 비용·기간을 문서로 안내드립니다. 시작 후 추가 비용이 발생하지 않습니다.', 'label' => '02 — 설명', 'type' => 'textarea' ),

				'price_policy_3_title' => array( 'default' => '난이도 단계 안내', 'label' => '03 — 제목', 'type' => 'text' ),
				'price_policy_3_desc'  => array( 'default' => '임플란트·교정 등은 케이스 난이도에 따라 가격대가 명확히 다릅니다. 어느 단계인지 사전에 설명드립니다.', 'label' => '03 — 설명', 'type' => 'textarea' ),

				'price_policy_4_title' => array( 'default' => '평생 A/S 시스템', 'label' => '04 — 제목', 'type' => 'text' ),
				'price_policy_4_desc'  => array( 'default' => '시술 후 정기 검진·문제 발생 시 대응까지 함께 봅니다. 비용은 시술 시점에만 발생하지 않습니다.', 'label' => '04 — 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 치료별 예상 비용 — 자유 편집 (v3.27.0) ─── */
		'pricing_tables' => array(
			'title'  => '비용 페이지 — 전체 가격표 (탭별 · 자유 편집)',
			'fields' => array(
				'price_tables_eyebrow' => array( 'default' => 'Estimated Cost', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'price_tables_title'   => array( 'default' => '치료별 예상 비용', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'price_tables_lead'    => array( 'default' => '아래 표는 표준 기준입니다. 정확한 비용은 정밀 진단 후 견적서로 안내드립니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),
				'price_tables_hint'    => array( 'default' => '환자분의 구강 상태·재료 선택·치료 난이도에 따라 조정될 수 있습니다. 최종 비용은 정밀 진단 후 견적서로 확정해드립니다.', 'label' => '하단 안내문구', 'type' => 'textarea' ),

				'price_tabs_all' => array(
					'default' => "== 임플란트 ==\n" . $default_implant . "\n\n" .
						"== 교정 ==\n" . $default_ortho . "\n\n" .
						"== 크라운·틀니 ==\n" . $default_crown . "\n\n" .
						"== 충치·레진 ==\n" . $default_decay . "\n\n" .
						"== 심미·미백 ==\n" . $default_aesthetic . "\n\n" .
						"== 소아·예방 ==\n" . $default_kids . "\n\n" .
						"== 턱관절 ==\n" . $default_tmj,
					'label' => "전체 가격표 (탭별 자유 편집)\n\n" .
						"편집 방법:\n" .
						"  • 탭 시작: == 탭 이름 == (== 로 감싼 줄)\n" .
						"  • 항목:    이름 | 가격 | 비고 (파이프 3열)\n" .
						"  • 빈 줄:   무시\n" .
						"  • 주석:    # 로 시작하는 줄은 안 보임\n\n" .
						"탭 추가·삭제·순서 변경: 그냥 텍스트에서 자유롭게",
					'type'  => 'textarea',
				),
			),
		),

		/* ─── 건강보험 적용 비교 ─── */
		'pricing_insurance' => array(
			'title'  => '비용 페이지 — 건강보험 적용 비교',
			'fields' => array(
				'price_ins_eyebrow' => array( 'default' => 'Insurance', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'price_ins_title'   => array( 'default' => '건강보험 적용 비교', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'price_ins_lead'    => array( 'default' => '무엇이 적용되고 무엇이 그렇지 않은지 — 미리 확인하세요.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

				'price_ins_yes_title' => array( 'default' => '본인부담 일부 / 급여 진료', 'label' => '왼쪽 카드 (적용) — 제목', 'type' => 'text' ),
				'price_ins_yes_items' => array( 'default' => $default_covered, 'label' => '왼쪽 카드 — 항목 (한 줄당 1개)', 'type' => 'textarea' ),

				'price_ins_no_title' => array( 'default' => '본인부담 100% / 사전 견적', 'label' => '오른쪽 카드 (비급여) — 제목', 'type' => 'text' ),
				'price_ins_no_items' => array( 'default' => $default_uncovered, 'label' => '오른쪽 카드 — 항목 (한 줄당 1개)', 'type' => 'textarea' ),
			),
		),

		/* ─── 결제 안내 ─── */
		'pricing_payment' => array(
			'title'  => '비용 페이지 — 결제 안내',
			'fields' => array(
				'price_pay_eyebrow' => array( 'default' => 'Payment', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'price_pay_title'   => array( 'default' => '결제 안내', 'label' => '섹션 — 제목', 'type' => 'text' ),

				'price_pay_1_icon'  => array( 'default' => '💳', 'label' => '①번 — 아이콘', 'type' => 'text' ),
				'price_pay_1_title' => array( 'default' => '신용·체크카드', 'label' => '①번 — 제목', 'type' => 'text' ),
				'price_pay_1_desc'  => array( 'default' => '모든 카드사 결제 가능', 'label' => '①번 — 설명', 'type' => 'text' ),

				'price_pay_2_icon'  => array( 'default' => '📊', 'label' => '②번 — 아이콘', 'type' => 'text' ),
				'price_pay_2_title' => array( 'default' => '무이자 할부', 'label' => '②번 — 제목', 'type' => 'text' ),
				'price_pay_2_desc'  => array( 'default' => '고액 진료 시 카드사 2~12개월', 'label' => '②번 — 설명', 'type' => 'text' ),

				'price_pay_3_icon'  => array( 'default' => '📱', 'label' => '③번 — 아이콘', 'type' => 'text' ),
				'price_pay_3_title' => array( 'default' => '간편결제', 'label' => '③번 — 제목', 'type' => 'text' ),
				'price_pay_3_desc'  => array( 'default' => '삼성페이·카카오페이 등', 'label' => '③번 — 설명', 'type' => 'text' ),

				'price_pay_4_icon'  => array( 'default' => '💵', 'label' => '④번 — 아이콘', 'type' => 'text' ),
				'price_pay_4_title' => array( 'default' => '현금 결제', 'label' => '④번 — 제목', 'type' => 'text' ),
				'price_pay_4_desc'  => array( 'default' => '현금영수증 발급', 'label' => '④번 — 설명', 'type' => 'text' ),

				'price_pay_notice_tag'  => array( 'default' => '실손보험 안내', 'label' => '하단 안내 — 태그', 'type' => 'text' ),
				'price_pay_notice_text' => array( 'default' => '치과 진료는 대부분 실손보험 대상이 아닙니다. 사고로 인한 외상 치료·턱관절 일부는 보장 가능할 수 있으니 가입 보험사에 사전 확인해주세요. 문치과병원은 진단서·소견서 발급으로 보험 청구를 도와드립니다.', 'label' => '하단 안내 — 본문', 'type' => 'textarea' ),
			),
		),

		/* ─── CTA 배너 + 메타 ─── */
		'pricing_cta' => array(
			'title'  => '비용 페이지 — CTA 배너',
			'fields' => array(
				'price_cta_chip'  => array( 'default' => '무료 비용 상담', 'label' => 'chip', 'type' => 'text' ),
				'price_cta_title' => array( 'default' => '내 진료 비용이 궁금하신가요?', 'label' => '제목', 'type' => 'text' ),
				'price_cta_lead'  => array( 'default' => '정확한 진단 후 맞춤 견적서를 안내드립니다. 부담 없이 먼저 들어보세요.', 'label' => '설명', 'type' => 'textarea' ),

				'price_cta_meta_1_label' => array( 'default' => '진료시간', 'label' => '메타 ①번 — 라벨', 'type' => 'text' ),
				'price_cta_meta_1_value' => array( 'default' => '월·화·수·금 9:00–20:30 · 목 ~18:30 · 토 ~14:00', 'label' => '메타 ①번 — 값', 'type' => 'text' ),

				'price_cta_meta_2_label' => array( 'default' => '예약 채널', 'label' => '메타 ②번 — 라벨', 'type' => 'text' ),
				'price_cta_meta_2_value' => array( 'default' => '전화 · 카카오톡 · 네이버 예약 (24시간)', 'label' => '메타 ②번 — 값', 'type' => 'text' ),

				'price_cta_meta_3_label' => array( 'default' => '위치', 'label' => '메타 ③번 — 라벨', 'type' => 'text' ),
				'price_cta_meta_3_value' => array( 'default' => '충남 천안시 동남구 만남로 52, 문타워 9·10·11·13층', 'label' => '메타 ③번 — 값', 'type' => 'text' ),
			),
		),

		/* ─── 위치 섹션 head ─── */
		'pricing_location' => array(
			'title'  => '비용 페이지 — 위치 섹션',
			'fields' => array(
				'price_loc_eyebrow' => array( 'default' => 'Location', 'label' => 'eyebrow', 'type' => 'text' ),
				'price_loc_title'   => array( 'default' => '문치과병원 위치', 'label' => '제목', 'type' => 'text' ),
			),
		),

	);
}

/**
 * 진료 페이지 7개 콘텐츠 필드 정의 (공통 + 진료별 고민·추천 대상).
 */
function moondental_service_content_fields() {
	/* 슬러그 → key 매핑 (Customizer key 짧게) */
	$services = array(
		'implant'   => array( 'label' => '임플란트 센터',   'slug' => '임플란트-센터' ),
		'ortho'     => array( 'label' => '투명교정 센터',   'slug' => '투명교정-센터' ),
		'endo'      => array( 'label' => '자연치아 살리기', 'slug' => '자연치아-살리기' ),
		'tmj'       => array( 'label' => '턱관절 클리닉',   'slug' => '턱관절-클리닉' ),
		'wisdom'    => array( 'label' => '사랑니 발치',     'slug' => '사랑니-발치' ),
		'aesthetic' => array( 'label' => '심미치료',        'slug' => '심미치료' ),
		'pediatric' => array( 'label' => '소아치과',        'slug' => '소아치과' ),
	);

	$defaults_pains = array(
		'implant'   => "수술이 무서워요 | 디지털 가이드 수술 + PCA 자가진통조절기로 통증과 불안을 최소화합니다.\n뼈가 부족하다고 들었어요 | CBCT 정밀 분석 후 GBR·상악동 거상술 등 환자 골 상태에 맞춘 옵션을 제시합니다.\n비용이 부담돼요 | 사전 견적서 제공 + 카드 무이자 할부 가능. 만 65세 이상은 건강보험 적용도 안내드립니다.\n전신질환이 있어 거절당했어요 | 혈압·당검사·심전도·산소포화도 상시 측정으로 안전하게 진행합니다.\n다른 치과 임플란트가 흔들려요 | 리무버 키트로 안전 제거 후 골손실을 최소화하며 재식립합니다.\n오래 사용할 수 있나요? | 정기 검진 + 평생 A/S 시스템으로 10~20년 이상 유지를 목표합니다.",
		'ortho'     => "성인인데 교정이 가능한가요? | 잇몸·치주가 건강하면 50~60대도 충분히 가능합니다.\n발치 없이 안 되나요? | 정밀 진단 후 비발치를 우선 검토합니다. 부분교정만 필요한 경우도 많습니다.\n비용·기간이 길어 부담돼요 | 난이도별 옵션과 부분교정(150만 원~)도 안내드리며, 카드 무이자 할부 지원합니다.\n투명교정 vs 일반교정 뭐가 좋나요? | 라이프스타일·난이도에 맞춰 교정 전문의가 최적안을 추천드립니다.\n교정 후 다시 돌아간다고 들었어요 | 유지장치(retainer) 평생 야간 착용으로 안정 유지가 가능합니다.\n통증이 심한가요? | 디지털 정밀 진단으로 와이어 힘을 정확히 조절해 통증을 최소화합니다.",
		'endo'      => "신경치료 vs 발치 후 임플란트? | 자연치를 살릴 수 있다면 보존이 우선입니다. 비용·신체 부담이 모두 적습니다.\n재근관치료 가능한가요? | 현미경 정밀 진료로 약 70~80% 성공률을 보입니다. 보존과 전문의가 직접 진료합니다.\n통증이 심한가요? | 국소마취 + 진통제로 충분히 조절 가능합니다. 시술 중 통증은 거의 없습니다.\n치아 색이 변했어요 | 워킹 블리치(내부 미백)로 신경치료 치아도 색상 개선이 가능합니다.\n신경치료 후 꼭 씌워야 하나요? | 신경치료 치아는 약해지므로 크라운으로 잘 보호해야 평생 사용할 수 있습니다.\n다른 치과 신경치료가 실패했어요 | 보존과 전문의의 재근관치료 + 치근단 수술로 단계적 접근이 가능합니다.",
		'tmj'       => "턱에서 소리만 나고 통증은 없는데? | 디스크 변위 가능성이 있어 정기 검진을 권합니다. 조기 진단이 중요합니다.\n보톡스로 정말 좋아지나요? | 교근 과긴장이 통증 원인이면 효과적입니다. 단, 습관 교정·스플린트와 병행을 권합니다.\n두통이 턱관절 때문일까요? | 측두근·교근 과긴장이 두통으로 이어지는 경우가 많습니다. 만성 두통 환자 다수가 호전됩니다.\n스플린트는 평생 착용해야 하나요? | 대개 3~6개월 야간 착용 후 증상 호전 시 사용 빈도를 줄입니다.\n수술까지 가는 경우는? | 극히 드뭅니다. 대부분 비수술 보존 치료로 호전됩니다.\n임플란트 후 턱관절이 아파요 | 교합 변화가 원인일 수 있습니다. 보철 후에도 턱관절까지 함께 관리합니다.",
		'wisdom'    => "신경 마비가 걱정돼요 | 3D CT 사전 분석으로 신경 위치를 파악해 위험을 최소화합니다. 위험 시 부분 발치(Coronectomy) 옵션도 있습니다.\n임신 중 발치가 가능한가요? | 가급적 출산 후를 권하지만, 급성 염증 시 임신 중기에 최소 침습으로 진행 가능합니다.\n발치 후 통증이 얼마나 가나요? | 일반은 2~3일, 매복 사랑니는 3~5일 통증 피크. 1주일 후 거의 사라집니다.\n4개를 한 번에 빼도 되나요? | 건강한 성인은 가능하나, 회복 부담 고려해 좌/우 또는 위/아래 나눠 진행을 권합니다.\n발치 후 운동은 언제부터? | 가벼운 산책은 다음 날부터, 격렬한 운동은 1주일 후부터 권장합니다.\n수면 마취로 받고 싶어요 | 국소마취 + 물방울 레이저 + PCA로 충분히 편안하게 진행 가능합니다.",
		'aesthetic' => "라미네이트 vs 미백 뭐가 좋나요? | 단순 변색은 미백 우선, 형태·배열까지 함께 개선하려면 라미네이트가 적합합니다.\n미백 후 다시 어두워지나요? | 1~2년 후 자연스러운 재착색이 있습니다. 보강 미백(touch-up)으로 유지합니다.\n신경치료 치아도 미백 가능한가요? | 워킹 블리치라는 내부 미백법으로 가능합니다. 상담 시 적합 여부를 확인합니다.\n라미네이트로 치아가 약해지지 않나요? | 최소 삭제 라미네이트로 자연치를 최대한 보존합니다. 충분한 상담 후 진행합니다.\n잇몸 라인이 신경 쓰여요 | 거미스마일·잇몸 미백 등 잇몸 라인 개선 시술이 가능합니다.\n비용이 어느 정도인가요? | 라미네이트 치아당 66만원~, 전문가 미백 33만원~. 정확한 견적은 진단 후 안내드립니다.",
		'pediatric' => "아이가 치과를 무서워해요 | 거부 시 절대 강제 진료하지 않습니다. 첫 방문은 진료실 구경부터 시작할 수 있습니다.\n몇 살부터 치과 진료가 필요한가요? | 첫 어금니가 나오는 만 1~2세부터 첫 방문을 권합니다. 이후 6개월 단위 정기 검진.\n실란트(홈 메우기)는 꼭 필요한가요? | 필수는 아니나 만 6~8세 영구치 어금니에 50% 이상 충치 예방 효과. 건강보험도 적용됩니다.\n소아 교정은 언제 시작해야 하나요? | 골격성 부정교합은 만 7~10세가 골든타임. 일반 부정교합은 영구치 다 나온 후(만 12세+).\n아이 충치 치료는 어떻게 하나요? | 유치는 글래스아이오노머·콤포지트로 짧은 시간에 치료. 아이가 협조 어려우면 분할 진행합니다.\n건강보험은 어디까지 되나요? | 만 12세 이하 영구치 레진 충전, 실란트, 발치, X-ray 등이 적용됩니다.",
	);

	$defaults_candidates = array(
		'implant'   => "치아가 빠지거나 발치 예정인 분\n틀니가 불편해 식사가 어려운 분\n인접 치아를 갈지 않고 복원하고 싶은 분\n잇몸·전신질환이 있어 다른 곳에서 거절당한 분\n만 65세 이상 건강보험 임플란트 대상자",
		'ortho'     => "치아 배열이 고르지 않은 분\n외모 콤플렉스가 있는 분\n발음·저작 기능에 영향이 있는 부정교합\n골격성 부정교합 의심 청소년 (만 7~10세)\n타치과에서 발치 교정을 권유받았으나 비발치 검토를 원하는 분",
		'endo'      => "신경치료 후 다시 통증이 있는 분\n충치가 깊어 발치를 권유받은 분\n자연치를 최대한 살리고 싶은 분\n치아 변색·외상이 있는 분\n재근관치료가 필요한 분",
		'tmj'       => "턱관절에서 소리가 나거나 통증이 있는 분\n입을 크게 벌리기 어려운 분\n이갈이·이악물기 습관이 있는 분\n만성 두통·이명이 있는 분\n임플란트·보철 치료 후 교합이 불편한 분",
		'wisdom'    => "매복 사랑니로 통증·잇몸 부음이 있는 분\n사랑니 주변 충치·잇몸염이 반복되는 분\n교정 치료를 앞두고 사랑니 발치가 필요한 분\n타치과에서 발치 위험으로 거절당한 매복 사랑니\n급성 염증이 있어 빠른 처치가 필요한 분",
		'aesthetic' => "앞니 변색·결손으로 자신감이 떨어진 분\n미백으로 안 되는 심한 변색이 있는 분\n잇몸이 많이 보이는 거미스마일\n특별한 행사 전 단기간 심미 개선을 원하는 분\n전치부 작은 결손·반점을 자연스럽게 보완하고 싶은 분",
		'pediatric' => "만 1~2세 첫 치과 방문이 필요한 아이\n유치 충치가 생긴 아이\n영구치 어금니가 나오는 만 6~8세 (실란트 적기)\n골격성 부정교합 의심 청소년 (1차 교정 골든타임)\n치과를 무서워해 진료가 어려운 아이",
	);

	$groups = array(

		/* ─── 공통 섹션 head ─── */
		'service_common' => array(
			'title'  => '진료 페이지 — 공통 섹션 head',
			'fields' => array(
				'service_pain_eyebrow'    => array( 'default' => '환자분의 마음', 'label' => '환자 고민 — eyebrow', 'type' => 'text' ),
				'service_pain_title'      => array( 'default' => '혹시 이런 고민 하고 계시죠?', 'label' => '환자 고민 — 제목', 'type' => 'text' ),
				'service_pain_lead'       => array( 'default' => '많은 환자분이 같은 걱정을 안고 오십니다. 문치과병원이 어떻게 답해드리는지 확인하세요.', 'label' => '환자 고민 — 설명', 'type' => 'textarea' ),
				'service_pain_tag_q'      => array( 'default' => '고민', 'label' => '카드 — 왼쪽 태그 ("고민")', 'type' => 'text' ),
				'service_pain_tag_a'      => array( 'default' => '문치과의 답', 'label' => '카드 — 오른쪽 태그 ("문치과의 답")', 'type' => 'text' ),

				'service_compare_eyebrow' => array( 'default' => 'Difference', 'label' => '비교표 — eyebrow', 'type' => 'text' ),
				'service_compare_title'   => array( 'default' => '치과병원과 일반 치과의 차이', 'label' => '비교표 — 제목', 'type' => 'text' ),
				'service_compare_lead'    => array( 'default' => '의료기관 종별·시설·운영의 객관적 차이입니다.', 'label' => '비교표 — 설명', 'type' => 'textarea' ),
				'service_compare_note'    => array( 'default' => '일반 치과의원의 시설·운영은 의원별로 다를 수 있으며, 위 비교는 일반적 기준입니다.', 'label' => '비교표 — 하단 주석', 'type' => 'textarea' ),

				'service_ideal_chip'  => array( 'default' => 'For You', 'label' => '추천 대상 — chip', 'type' => 'text' ),
				'service_ideal_title' => array( 'default' => '이런 분께 추천합니다', 'label' => '추천 대상 — 제목', 'type' => 'text' ),
				'service_ideal_lead'  => array( 'default' => '해당하시는 항목이 있으시면 부담 없이 상담받으세요.', 'label' => '추천 대상 — 설명', 'type' => 'textarea' ),
			),
		),
	);

	/* 7 진료별 그룹 — 고민/추천 각 1 텍스트영역 + 본문 도입 텍스트 */
	foreach ( $services as $key => $svc ) {
		$groups[ 'svc_' . $key ] = array(
			'title'  => '진료 — ' . $svc['label'],
			'fields' => array(
				"service_{$key}_pains" => array(
					'default' => $defaults_pains[ $key ] ?? '',
					'label'   => '환자 고민 6쌍 (한 줄당 1개, "고민 | 솔루션" 파이프 구분)',
					'type'    => 'textarea',
				),
				"service_{$key}_candidates" => array(
					'default' => $defaults_candidates[ $key ] ?? '',
					'label'   => '추천 대상 (한 줄당 1개)',
					'type'    => 'textarea',
				),
			),
		);
	}

	return $groups;
}

/**
 * 의료진 콘텐츠 (철학 + 약력 override).
 *  사진/이름/직책은 functions.php 데이터 기준, 여기서는 자주 바뀌는 철학·약력만 편집.
 */
function moondental_doctor_content_fields() {
	$doctors = array(
		'munes'  => array( 'name' => '문은수', 'role' => '대표 병원장' ),
		'leesj'  => array( 'name' => '이승주', 'role' => '종합진료센터' ),
		'leesu'  => array( 'name' => '이수연', 'role' => '종합진료센터' ),
		'kwon'   => array( 'name' => '권혜진', 'role' => '종합진료센터' ),
		'munji'  => array( 'name' => '문지현', 'role' => '임플란트센터' ),
		'leech'  => array( 'name' => '이창률', 'role' => '임플란트센터' ),
		'leeyi'  => array( 'name' => '이영일', 'role' => '교정과' ),
		'kimsi'  => array( 'name' => '김세일', 'role' => '종합진료센터' ),
		'jeong'  => array( 'name' => '정석형', 'role' => '종합진료센터' ),
	);

	$groups = array();
	foreach ( $doctors as $key => $doc ) {
		$groups[ 'doc_' . $key ] = array(
			'title'  => '의료진 — ' . $doc['name'] . ' (' . $doc['role'] . ')',
			'fields' => array(
				"doctor_{$key}_philosophy" => array(
					'default' => '',
					'label'   => '진료 철학 (빈칸 = 기본값)',
					'type'    => 'textarea',
				),
				"doctor_{$key}_bio" => array(
					'default' => '',
					'label'   => '약력 (한 줄당 1개, 빈칸 = 기본값)',
					'type'    => 'textarea',
				),
			),
		);
	}

	return $groups;
}

/**
 * 오시는 길 / 사명·역사 / 의료진 / 예약 페이지 텍스트.
 */
function moondental_subpage_content_fields() {
	return array(

		/* ─── 오시는 길 — 주차 안내 ─── */
		'location_parking' => array(
			'title'  => '오시는 길 — 주차 안내',
			'fields' => array(
				'loc_park_badge'     => array( 'default' => '🅿️ 주차 안내', 'label' => '배지', 'type' => 'text' ),
				'loc_park_title'     => array( 'default' => '본원 지하 기계식 무료', 'label' => '주차 카드 — 제목 (강조는 자동)', 'type' => 'text' ),
				'loc_park_lead'      => array( 'default' => '방문 시 데스크에 주차권을 제출하시면 등록해드립니다.', 'label' => '주차 카드 — 설명', 'type' => 'textarea' ),

				'loc_park_1_title' => array( 'default' => '본원 지하 기계식 주차장', 'label' => '①번 항목 — 제목', 'type' => 'text' ),
				'loc_park_1_desc'  => array( 'default' => '진료 시간 동안 무료 이용', 'label' => '①번 항목 — 설명', 'type' => 'textarea' ),

				'loc_park_2_title'   => array( 'default' => 'SUV·대형차 — 신부 제5공영주차장', 'label' => '②번 항목 — 제목', 'type' => 'text' ),
				'loc_park_2_desc'   => array( 'default' => '인근 신부 제5공영주차장(동남구 먹거리1길 10) 주차 후 데스크에 접수 → 무료 등록', 'label' => '②번 항목 — 설명', 'type' => 'textarea' ),
				'loc_park_2_addr'     => array( 'default' => '동남구 먹거리1길 10', 'label' => '②번 항목 — 클릭 가능한 주소 문구 (위 설명 안의 일치 문자열이 자동으로 링크 처리됨)', 'type' => 'text' ),
				'loc_park_2_addr_url' => array( 'default' => 'https://map.naver.com/p/search/%EC%8B%A0%EB%B6%80%20%EC%A0%9C5%EA%B3%B5%EC%98%81%EC%A3%BC%EC%B0%A8%EC%9E%A5', 'label' => '②번 항목 — 주소 클릭 시 이동할 URL (기본: 네이버 지도 검색)', 'type' => 'text' ),

				'loc_park_walk'    => array( 'default' => '🚌 천안종합·고속버스터미널에서 도보 약 5분', 'label' => '하단 도보 안내 ①', 'type' => 'text' ),
				'loc_park_train'   => array( 'default' => '🚆 천안역에서 버스로 약 10분', 'label' => '하단 도보 안내 ② (비우면 숨김)', 'type' => 'text' ),

				'loc_hours_badge'  => array( 'default' => '🕐 진료시간', 'label' => '진료시간 카드 — 배지', 'type' => 'text' ),
				'loc_hours_title'  => array( 'default' => '진료 가능 시간', 'label' => '진료시간 카드 — 제목', 'type' => 'text' ),
				'loc_hours_note'   => array( 'default' => '평일 점심시간 없이 진료 · 야간진료 운영', 'label' => '진료시간 카드 — 하단 안내', 'type' => 'text' ),

				'loc_channels_eyebrow' => array( 'default' => '예약·문의', 'label' => '연락 채널 섹션 — eyebrow', 'type' => 'text' ),
				'loc_channels_title'   => array( 'default' => '편하신 방법으로 연락주세요', 'label' => '연락 채널 섹션 — 제목', 'type' => 'text' ),
			),
		),

		/* ─── 30여년의 발자취 ─── */
		'mission' => array(
			'title'  => '30여년의 발자취 페이지',
			'fields' => array(
				'mission_chip'        => array( 'default' => 'OUR MISSION · 사명', 'label' => '사명 — chip', 'type' => 'text' ),
				'mission_title_a'     => array( 'default' => '환자를 가족처럼 생각하는 마음,', 'label' => '사명 — 제목 1행', 'type' => 'text' ),
				'mission_title_b'     => array( 'default' => '그것이 문치과의 진료 철학입니다.', 'label' => '사명 — 제목 2행 (강조)', 'type' => 'text' ),
				'mission_lead'        => array( 'default' => '1995년부터 한자리에서, 한 분의 환자를 가족처럼 오래 보아왔습니다. 진료실 밖에서도 지역사회와 함께 가는 치과를 꿈꿉니다.', 'label' => '사명 — 설명', 'type' => 'textarea' ),

				'mission_v_1_icon'  => array( 'default' => '🤝', 'label' => '핵심가치 ①번 — 아이콘', 'type' => 'text' ),
				'mission_v_1_title' => array( 'default' => '정직', 'label' => '핵심가치 ①번 — 제목', 'type' => 'text' ),
				'mission_v_1_desc'  => array( 'default' => '환자분께 필요한 진료만 권합니다. 시작 전 모든 비용을 안내합니다.', 'label' => '핵심가치 ①번 — 설명', 'type' => 'textarea' ),

				'mission_v_2_icon'  => array( 'default' => '🛡️', 'label' => '핵심가치 ②번 — 아이콘', 'type' => 'text' ),
				'mission_v_2_title' => array( 'default' => '신뢰', 'label' => '핵심가치 ②번 — 제목', 'type' => 'text' ),
				'mission_v_2_desc'  => array( 'default' => '30여년 동안 한자리에서 — 환자 한 분의 평생 치아를 길게 봅니다.', 'label' => '핵심가치 ②번 — 설명', 'type' => 'textarea' ),

				'mission_v_3_icon'  => array( 'default' => '🌱', 'label' => '핵심가치 ③번 — 아이콘', 'type' => 'text' ),
				'mission_v_3_title' => array( 'default' => '책임', 'label' => '핵심가치 ③번 — 제목', 'type' => 'text' ),
				'mission_v_3_desc'  => array( 'default' => '시술 시점뿐 아니라 정기 검진·사후 관리까지 평생 함께합니다.', 'label' => '핵심가치 ③번 — 설명', 'type' => 'textarea' ),

				'mission_v_4_icon'  => array( 'default' => '❤️', 'label' => '핵심가치 ④번 — 아이콘', 'type' => 'text' ),
				'mission_v_4_title' => array( 'default' => '헌신', 'label' => '핵심가치 ④번 — 제목', 'type' => 'text' ),
				'mission_v_4_desc'  => array( 'default' => '지역사회와 함께 — 의료재단으로서 장학·기부를 이어가고 있습니다.', 'label' => '핵심가치 ④번 — 설명', 'type' => 'textarea' ),

				'history_eyebrow'  => array( 'default' => 'Our History', 'label' => '역사 섹션 — eyebrow', 'type' => 'text' ),
				'history_title'    => array( 'default' => '30여년의 발자취', 'label' => '역사 섹션 — 제목', 'type' => 'text' ),
				'history_lead'     => array( 'default' => '1995년 개원 현재까지 — 환자분과 함께 걸어온 길.', 'label' => '역사 섹션 — 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 예약·상담 페이지 ─── */
		'reservation' => array(
			'title'  => '예약·상담 페이지',
			'fields' => array(
				'res_hero_eyebrow' => array( 'default' => 'RESERVATION',                 'label' => 'Hero — eyebrow', 'type' => 'text' ),
				'res_hero_title'   => array( 'default' => '예약·상담 그리고 오시는 길',  'label' => 'Hero — 제목',    'type' => 'text' ),
				'res_hero_lead'    => array( 'default' => "네이버 예약 · 전화 · 카카오톡 — 가장 편하신 방법으로 예약해주세요.\n아래에서 진료시간·찾아오시는 길도 함께 확인하실 수 있습니다.", 'label' => 'Hero — 설명 (줄바꿈)', 'type' => 'textarea' ),

				'res_channels_eyebrow' => array( 'default' => '예약 채널',                 'label' => '예약 채널 섹션 — eyebrow', 'type' => 'text' ),
				'res_channels_title'   => array( 'default' => '편하신 방법으로 예약해주세요', 'label' => '예약 채널 섹션 — 제목', 'type' => 'text' ),
				'res_channels_lead'    => array( 'default' => '문치과병원은 네이버 예약으로 24시간 자동 예약을 받고 있으며, 전화·카카오톡 상담도 함께 운영합니다.', 'label' => '예약 채널 섹션 — 설명', 'type' => 'textarea' ),

				'res_naver_title' => array( 'default' => '네이버 예약',                       'label' => '네이버 카드 — 제목',  'type' => 'text' ),
				'res_naver_desc'  => array( 'default' => '24시간 자동 예약 · 가장 빠른 일정 확인', 'label' => '네이버 카드 — 설명',  'type' => 'text' ),
				'res_naver_cta'   => array( 'default' => '예약하러 가기 →',                  'label' => '네이버 카드 — CTA',   'type' => 'text' ),

				'res_call_title'  => array( 'default' => '전화 예약',                         'label' => '전화 카드 — 제목',    'type' => 'text' ),
				'res_call_desc'   => array( 'default' => '진료시간 내 응답',                  'label' => '전화 카드 — 설명 (전화번호 자동 표시)', 'type' => 'text' ),
				'res_call_cta'    => array( 'default' => '바로 전화 →',                       'label' => '전화 카드 — CTA',     'type' => 'text' ),

				'res_kakao_title' => array( 'default' => '카카오톡 상담',                     'label' => '카카오 카드 — 제목',  'type' => 'text' ),
				'res_kakao_desc'  => array( 'default' => '24시간 메시지 · 진료시간 내 답변',  'label' => '카카오 카드 — 설명',  'type' => 'text' ),
				'res_kakao_cta'   => array( 'default' => '카카오톡 채널 →',                  'label' => '카카오 카드 — CTA',   'type' => 'text' ),
			),
		),

		/* ─── FAQ 페이지 Hero ─── */
		'faq_page' => array(
			'title'  => 'FAQ 페이지 — Hero',
			'fields' => array(
				'faq_page_eyebrow' => array( 'default' => 'FAQ',                     'label' => 'eyebrow', 'type' => 'text' ),
				'faq_page_title'   => array( 'default' => '자주 묻는 질문',           'label' => '제목',    'type' => 'text' ),
				'faq_page_lead'    => array( 'default' => '환자분들이 가장 많이 궁금해하시는 질문과 답변을 정리했습니다.', 'label' => '설명', 'type' => 'textarea' ),
			),
		),

		/* ─── 의료진 페이지 Hero·CTA ─── */
		'doctors_page' => array(
			'title'  => '의료진 페이지 — Hero / CTA',
			'fields' => array(
				'doctors_chip'       => array( 'default' => 'MOON DENTAL HOSPITAL · OUR DOCTORS', 'label' => 'Hero — chip', 'type' => 'text' ),
				'doctors_title_a'    => array( 'default' => '30여년 임상,', 'label' => 'Hero — 제목 1행', 'type' => 'text' ),
				'doctors_title_b'    => array( 'default' => '전 분야 의료진 협진', 'label' => 'Hero — 제목 2행', 'type' => 'text' ),
				'doctors_lead'       => array( 'default' => "보철·교정·보존·외과 — 각 분야 전문 의료진이 한 자리에서\n환자 한 분의 치아를 함께 봅니다.", 'label' => 'Hero — 설명 (줄바꿈 가능)', 'type' => 'textarea' ),

				'doctors_stat_1_label' => array( 'default' => '전문 의료진', 'label' => 'stat ①번 라벨', 'type' => 'text' ),
				'doctors_stat_3_value' => array( 'default' => '30여년', 'label' => 'stat ③번 숫자', 'type' => 'text' ),
				'doctors_stat_3_label' => array( 'default' => '1995년 개원', 'label' => 'stat ③번 라벨', 'type' => 'text' ),

				'doctors_list_eyebrow' => array( 'default' => 'Our Doctors', 'label' => '의료진 그리드 — eyebrow', 'type' => 'text' ),
				'doctors_list_title'   => array( 'default' => '전체 의료진', 'label' => '의료진 그리드 — 제목', 'type' => 'text' ),
				'doctors_list_lead'    => array( 'default' => '각 분야 전문 의료진의 진료를 받으실 수 있습니다.', 'label' => '의료진 그리드 — 설명', 'type' => 'textarea' ),
				'doctors_grid_hint'    => array( 'default' => '진료 예약 시 원하시는 의료진을 지정하실 수 있습니다.', 'label' => '의료진 그리드 — 하단 안내', 'type' => 'text' ),

				'doctors_cta_chip'  => array( 'default' => '상담 예약', 'label' => 'CTA 배너 — chip', 'type' => 'text' ),
				'doctors_cta_title' => array( 'default' => '어떤 원장님께 진료받고 싶으신가요?', 'label' => 'CTA 배너 — 제목', 'type' => 'text' ),
				'doctors_cta_lead'  => array( 'default' => '부담 없이 상담받으세요. 환자분께 맞는 의료진을 안내드립니다.', 'label' => 'CTA 배너 — 설명', 'type' => 'textarea' ),

				/* ── 전체 직원 섹션 (의료진 외 스태프) ── */
				'staff_section_eyebrow' => array( 'default' => 'Our Staff', 'label' => '전체 직원 섹션 — eyebrow', 'type' => 'text' ),
				'staff_section_title'   => array( 'default' => '전체 직원', 'label' => '전체 직원 섹션 — 제목', 'type' => 'text' ),
				'staff_section_lead'    => array( 'default' => '한아의료재단 문치과병원에서 환자분과 함께하는 모든 의료진입니다.', 'label' => '전체 직원 섹션 — 설명', 'type' => 'textarea' ),
				'staff_list' => array(
					'default' => "진료실|이사|이순민\n진료실|팀장|박지선\n진료실|실장|이희남\n진료실|실장|임은혜\n진료실|실장|한경순\n진료실|책임|주경심\n진료실|책임|윤경옥\n진료실|책임|노금란\n진료실|책임|김정애\n진료실|과장|남소영\n진료실|선임|김인애\n진료실|선임|박미선\n진료실|선임|김윤미\n진료실|선임|유현영\n진료실|주임|서채빈\n진료실|주임|박명자\n진료실|주임|금민주\n진료실|주임|전서혜\n진료실|주임|유혜정\n진료실|주임|서소리\n진료실|주임|장유정\n진료실|주임|이아연\n진료실|주임|김경하\n진료실|주임|이다윤\n진료실|주임|이하은\n진료실|주임|김하늘\n진료실|주임|김우정\n진료실|주임|최로미\n진료실|주임|권민지\n기공실|이사|조항수\n기공실|실장|맹의재\n기공실|과장|장순복\n기공실|과장|박진옥\n기공실|대리|노재형\n서비스지원실|이사|강미해\n서비스지원실|실장|이선양\n서비스지원실|수석코디|김다경\n서비스지원실|수석코디|공미희\n서비스지원실|책임코디|정소리\n서비스지원실|책임코디|황진아\n서비스지원실|책임코디|박혜령\n경영지원본부|행정원장|양병욱\n경영지원본부|실장|김동현\n경영지원본부|차장|이충현\n경영지원본부|과장|민종기\n경영지원본부|대리|이슬기\n경영지원본부|대리|김하진\n경영지원본부|대리|카밀라\n경영지원본부|주임|게를레\n경영지원본부|주임|오혜정\n관리사무소|소장|강성하",
					'label' => '전체 직원 명단 (한 줄에 한 명, "부서|직책|이름" 형식)',
					'type'  => 'textarea',
				),
			),
		),

	);
}

/**
 * 헤더·푸터 콘텐츠 필드 정의.
 */
function moondental_chrome_content_fields() {
	return array(
		'header' => array(
			'title'  => '헤더 — CTA 버튼',
			'fields' => array(
				'header_cta_label' => array( 'default' => '📅 상담 예약하기', 'label' => 'CTA 버튼 — 기본 라벨 (스크롤 시 아래 변형으로 자동 변경됨)', 'type' => 'text' ),
				'header_cta_url'   => array( 'default' => '/상담예약/', 'label' => 'CTA 버튼 — 링크 (사이트 내 경로 또는 전체 URL)', 'type' => 'text' ),
				'header_cta_cycle' => array(
					'default' => "✨ 편리한 상담 | #5C8B82 | #FFFFFF | 92,139,130\n🦷 내 구강상태 진단받기 | #E37B5C | #FFFFFF | 227,123,92\n💬 지금 카톡 상담 | #FEE500 | #181600 | 254,229,0\n📅 상담 예약하기 | #D88062 | #FFFFFF | 216,128,98",
					'label'   => 'CTA 버튼 — 스크롤 시 자동 변경 변형 (한 줄당 1 변형, 파이프(|)로 구분: 라벨 | 배경 #hex | 글자 #hex | 그림자 R,G,B)',
					'type'    => 'textarea',
				),
			),
		),
		'footer' => array(
			'title'  => '푸터 — 텍스트',
			'fields' => array(
				'footer_brand_tagline'      => array( 'default' => '', 'label' => '브랜드 슬로건 (로고 아래) — 비우면 미표시', 'type' => 'text' ),
				'footer_col_hours_title'    => array( 'default' => '진료시간', 'label' => '진료시간 컬럼 제목', 'type' => 'text' ),
				'footer_col_policy_title'   => array( 'default' => '이용안내', 'label' => '이용안내 컬럼 제목 (v3.28.4)', 'type' => 'text' ),
				'footer_copyright_bar'      => array(
					'default' => 'Copyright {year} {name}  All Rights Reserved.',
					'label' => '하단 저작권 바 (사용 가능 토큰: {year}, {name})',
					'type' => 'text',
				),
			),
		),
		'footer_legal' => array(
			'title'  => '푸터 — 의료기관 법적 표시 (필수)',
			'fields' => array(
				'footer_legal_show'      => array( 'default' => 'yes', 'label' => '법적 정보 섹션 표시 (yes/no)', 'type' => 'text' ),
				'footer_legal_inst_name' => array( 'default' => '한아의료재단 문치과병원', 'label' => '의료기관 명칭 (병원명)', 'type' => 'text' ),
				'footer_legal_rep'       => array( 'default' => '문은수 이사장', 'label' => '대표자 (값만, "대표자:" 라벨은 자동)', 'type' => 'text' ),
				'footer_legal_open_date' => array( 'default' => '1995.04', 'label' => '개업일 (예: 1995.04)', 'type' => 'text' ),
				'footer_legal_med_no'    => array( 'default' => '34400117', 'label' => '요양기관번호 (값만, 예: 34400117)', 'type' => 'text' ),
				'footer_legal_ad_no'     => array( 'default' => '', 'label' => '의료광고심의 번호 (비우면 미표시)', 'type' => 'text' ),
				'footer_legal_extra' => array(
					'default' => '',
					'label' => '추가 안내문 (선택, 정보 아래 표시)',
					'type' => 'textarea',
				),
			),
		),
		'footer_links' => array(
			'title'  => '푸터 — 이용안내 컬럼 링크 (진료시간 우측)',
			'fields' => array(
				'footer_link_privacy'    => array( 'default' => '개인정보취급방침|/개인정보처리방침/', 'label' => '개인정보취급방침 (형식: 라벨|URL — 비우면 숨김)', 'type' => 'text' ),
				'footer_link_terms'      => array( 'default' => '이용약관|/이용약관/',                 'label' => '이용약관', 'type' => 'text' ),
				'footer_link_email'      => array( 'default' => '이메일 무단수집거부|/이메일-무단수집거부/', 'label' => '이메일 무단수집거부', 'type' => 'text' ),
			),
		),
		'cta_buttons' => array(
			'title'  => '전역 예약 CTA 버튼 (네이버·카카오·전화)',
			'fields' => array(
				'cta_btn_naver_label' => array( 'default' => '📅 네이버 예약', 'label' => '네이버 예약 버튼 라벨 (비우면 해당 버튼 숨김)', 'type' => 'text' ),
				'cta_btn_naver_url'   => array( 'default' => '', 'label' => '네이버 예약 URL (비우면 SNS 섹션의 네이버 예약 URL 사용)', 'type' => 'text' ),
				'cta_btn_kakao_label' => array( 'default' => '💬 카카오톡 상담', 'label' => '카카오톡 상담 버튼 라벨 (비우면 해당 버튼 숨김)', 'type' => 'text' ),
				'cta_btn_kakao_url'   => array( 'default' => '', 'label' => '카카오톡 채널 URL (비우면 SNS 섹션의 카카오톡 URL 사용)', 'type' => 'text' ),
				'cta_btn_call_label'  => array( 'default' => '📞 전화 상담', 'label' => '전화 버튼 라벨 (비우면 버튼 숨김. 전화번호는 자동 표시)', 'type' => 'text' ),
				'cta_btn_show_phone'  => array( 'default' => 'no', 'label' => '전화 버튼에 전화번호 같이 표시 (yes/no · 기본 no · 가로 3버튼 균형 유지)', 'type' => 'text' ),
			),
		),
		'flocation' => array(
			'title'  => '모든 페이지 — 오시는 길 섹션 (푸터 위)',
			'fields' => array(
				'flocation_title'        => array( 'default' => '오시는 길', 'label' => '섹션 제목', 'type' => 'text' ),
				'flocation_address'      => array( 'default' => '', 'label' => '주소 (비우면 병원 기본 정보의 주소 사용)', 'type' => 'text' ),
				'flocation_btn_naver'     => array( 'default' => '네이버 지도', 'label' => '버튼 1 — 라벨', 'type' => 'text' ),
				'flocation_btn_naver_sub' => array( 'default' => '길찾기 · 대중교통', 'label' => '버튼 1 — 부제', 'type' => 'text' ),
				'flocation_btn_kakao'     => array( 'default' => '카카오맵', 'label' => '버튼 2 — 라벨', 'type' => 'text' ),
				'flocation_btn_kakao_sub' => array( 'default' => '길찾기 · 로드뷰', 'label' => '버튼 2 — 부제', 'type' => 'text' ),
				'flocation_btn_google'    => array( 'default' => 'Google Maps', 'label' => '버튼 3 — 라벨', 'type' => 'text' ),
				'flocation_btn_google_sub'=> array( 'default' => 'Directions · Street View', 'label' => '버튼 3 — 부제', 'type' => 'text' ),
			),
		),
		'recruit_hr' => array(
			'title'  => '상시채용 — 인사팀 전용 연락처',
			'fields' => array(
				'recruit_hr_phone'        => array( 'default' => '', 'label' => '인사팀 전용 전화 (표시용, 예: 041-563-2876) — 비우면 대표번호 사용', 'type' => 'text' ),
				'recruit_hr_phone_link'   => array( 'default' => '', 'label' => '인사팀 전용 전화 링크 (tel: 용 숫자만, 예: 0415632876)', 'type' => 'text' ),
				'recruit_hr_email'        => array( 'default' => '', 'label' => '인사팀 전용 이메일 — 비우면 대표 이메일 사용', 'type' => 'text' ),
				'recruit_hr_contact_name' => array( 'default' => '', 'label' => '인사 담당자 이름 (선택, 채용 히어로에 표시)', 'type' => 'text' ),
			),
		),
		'seo_verification' => array(
			'title'  => 'SEO — 검색엔진 등록 인증',
			'fields' => array(
				'seo_naver_verify'  => array( 'default' => '', 'label' => '네이버 웹마스터도구 인증 코드 (content 값만)', 'type' => 'text' ),
				'seo_google_verify' => array( 'default' => '', 'label' => 'Google Search Console 인증 코드 (content 값만)', 'type' => 'text' ),
			),
		),
	);
}

/**
 * 환자 후기 6개 콘텐츠 필드.
 *  6 reviews × 6 fields = 36 fields
 */
function moondental_testimonials_content_fields() {
	$defaults = array(
		1 => array( 'name' => '김○○', 'gender' => '여성', 'age' => '40대', 'service' => '임플란트', 'rating' => '5', 'text' => '오랫동안 미루던 임플란트를 30여년 경력 원장님께 받았습니다. 수술 당일 통증이 거의 없었고, 자가혈을 함께 사용한다는 점이 안심됐어요. 평일 야간 진료가 있어 직장인에게 정말 편합니다.' ),
		2 => array( 'name' => '박○○', 'gender' => '남성', 'age' => '50대', 'service' => '전악 보철', 'rating' => '5', 'text' => '여러 치과를 다녀봤지만 이렇게 충분히 설명해주시는 곳은 처음입니다. 전악 보철까지 진행했는데 의료진 협진이 정말 체계적이에요. 비용도 시작 전에 명확히 알려주셔서 신뢰가 갔습니다.' ),
		3 => array( 'name' => '이○○', 'gender' => '여성', 'age' => '30대', 'service' => '투명교정', 'rating' => '5', 'text' => '슈어스마일 투명교정 받았는데 처음에 걱정했던 것보다 훨씬 편했어요. 교정과 원장님이 사진 시뮬레이션으로 결과를 미리 보여주셨고, 6개월 만에 만족스러운 결과를 얻었습니다.' ),
		4 => array( 'name' => '최○○', 'gender' => '남성', 'age' => '60대', 'service' => '임플란트 + 보철', 'rating' => '5', 'text' => '고혈압이 있어서 다른 곳에서는 거절당했는데, 여기는 혈압 체크부터 약물까지 세심하게 봐주셨습니다. 수술 후 귀가 서비스까지 챙겨주셔서 감동이었어요.' ),
		5 => array( 'name' => '정○○', 'gender' => '여성', 'age' => '40대', 'service' => '자연치아 살리기', 'rating' => '5', 'text' => '발치하고 임플란트 하라던 치아를 보존과 전문의 원장님이 살려주셨어요. 재근관치료로 자연치를 지킬 수 있어서 정말 감사합니다.' ),
		6 => array( 'name' => '한○○', 'gender' => '여성', 'age' => '50대', 'service' => '심미 라미네이트', 'rating' => '5', 'text' => '앞니 라미네이트를 했는데 자연스럽게 잘 나왔어요. 무리한 치아 삭제 없이 보존적으로 해주신다는 점이 마음에 들었고, 결과도 만족합니다.' ),
	);

	$groups = array();
	for ( $i = 1; $i <= 6; $i++ ) {
		$d = $defaults[ $i ];
		$groups[ 'review_' . $i ] = array(
			'title'  => '환자 후기 #' . $i,
			'fields' => array(
				"review_{$i}_name"    => array( 'default' => $d['name'],    'label' => '이름 (예: 김○○, 비우면 카드 자동 숨김)', 'type' => 'text' ),
				"review_{$i}_gender"  => array( 'default' => $d['gender'],  'label' => '성별', 'type' => 'text' ),
				"review_{$i}_age"     => array( 'default' => $d['age'],     'label' => '연령대', 'type' => 'text' ),
				"review_{$i}_service" => array( 'default' => $d['service'], 'label' => '받은 진료', 'type' => 'text' ),
				"review_{$i}_rating"  => array( 'default' => $d['rating'],  'label' => '별점 (1-5)', 'type' => 'text' ),
				"review_{$i}_text"    => array( 'default' => $d['text'],    'label' => '후기 내용', 'type' => 'textarea' ),
			),
		);
	}
	return $groups;
}

/**
 * 문치과병원의 강점 9 카드 콘텐츠 필드.
 *  (구) 비교표를 강점 카드로 전환 — 비교 어휘 없이 우리 병원의 사실만 명시.
 *  데이터 키는 호환을 위해 compare_* 유지.
 */
function moondental_compare_content_fields() {
	$defaults = array(
		1 => array( 'label' => '의료기관 종별',     'value' => '치과병원 (병원급)',                            'icon' => '🏥' ),
		2 => array( 'label' => '의료진 협진',       'value' => '분야별 전문 의료진 협진',                       'icon' => '👨‍⚕️' ),
		3 => array( 'label' => '전문 진료 영역',   'value' => '보철·보존·예방·임플란트·스마일디자인·구강외과·구강내과·턱관절·교정·소아·치주',                'icon' => '🦷' ),
		4 => array( 'label' => '통합 진료센터',     'value' => '9·10·11·13F 4개 층 운영',                      'icon' => '🏢' ),
		5 => array( 'label' => '디지털 진단 장비',  'value' => 'CBCT · 디지털 가이드 · 구강스캐너',            'icon' => '🔬' ),
		6 => array( 'label' => '자체 보철 제작',    'value' => '한아 임플란트 보철연구소 · 원내 기공실 (13F)', 'icon' => '⚙️' ),
		7 => array( 'label' => '전신질환 대응',     'value' => '혈압 · 당검사 · 심전도 · 산소포화도 상시',     'icon' => '❤️' ),
		8 => array( 'label' => '평일 야간진료',     'value' => '평일 ~ 20:30 운영',                             'icon' => '🌙' ),
		9 => array( 'label' => '임상 경력',         'value' => '1995년부터 30여년 한자리 진료',                'icon' => '⏳' ),
	);

	$groups = array();
	for ( $i = 1; $i <= 9; $i++ ) {
		$d = $defaults[ $i ];
		$groups[ 'compare_row_' . $i ] = array(
			'title'  => '강점 카드 — ' . $i . ' (' . $d['label'] . ')',
			'fields' => array(
				"compare_{$i}_label"    => array( 'default' => $d['label'], 'label' => '카드 라벨 (비우면 카드 자동 숨김)', 'type' => 'text' ),
				"compare_{$i}_hospital" => array( 'default' => $d['value'], 'label' => '카드 본문 (우리 병원 사실)',    'type' => 'text' ),
				"compare_{$i}_icon"     => array( 'default' => $d['icon'],  'label' => '카드 아이콘 (이모지)',          'type' => 'text' ),
			),
		);
	}
	return $groups;
}

/**
 * 진료영역별 FAQ 콘텐츠 필드 (7 서비스 × 1 텍스트영역).
 *  형식: "Q | A" 한 줄에 하나씩 (HTML 가능)
 */
function moondental_service_faq_content_fields() {
	$services = array(
		'implant'   => '임플란트 센터',
		'ortho'     => '투명교정 센터',
		'endo'      => '자연치아 살리기',
		'tmj'       => '턱관절 클리닉',
		'wisdom'    => '사랑니 발치',
		'aesthetic' => '심미치료',
		'pediatric' => '소아치과',
	);

	$defaults = array(
		'implant' => "임플란트 수술 후 통증이 많이 있나요? | 국소마취 하에 진행되며 수술 자체는 거의 통증이 없습니다. PCA 자가진통조절기와 물방울 레이저로 통증을 최대한 줄입니다.\n임플란트는 얼마나 오래 사용할 수 있나요? | 관리에 따라 10~20년 이상 유지가 가능합니다. 1년 4회 정기 검진과 매일 구강위생 관리가 핵심이며, 평생 A/S 시스템을 운영합니다.\n뼈가 부족하다고 들었는데 임플란트가 가능할까요? | CT 정밀 분석 후 골이식·상악동 거상술·M-GBR 등 환자 골 상태에 맞춘 다양한 옵션이 있습니다.\n당일 임플란트는 누구나 가능한가요? | 발치+뼈이식+식립을 하루에 진행하는 시술입니다. 뼈 상태·잇몸 건강·전신 상태에 따라 가능 여부가 결정됩니다.\n건강보험 임플란트 혜택이 있나요? | 만 65세 이상 건강보험 가입자는 평생 2개까지 본인부담 30%로 적용 가능합니다.\n타치과에서 한 임플란트가 흔들리는데 다시 가능한가요? | 리무버 키트로 안전하게 제거 후 재식립 가능합니다. 골손실을 최소화하며 필요 시 골이식을 병행합니다.",

		'ortho' => "성인도 교정이 가능한가요? | 잇몸·치주 상태가 건강하다면 50~60대도 교정이 가능합니다.\n투명교정과 일반 교정 중 어느 것이 좋나요? | 케이스 난이도·라이프스타일·예산에 따라 다릅니다. 정밀 진단 후 추천드립니다.\n교정 중 통증은 얼마나 심한가요? | 장치 부착 직후·조정 후 2~3일간 둔한 통증이 있을 수 있습니다. 진통제로 조절 가능하며 1주일 이내 적응됩니다.\n교정 후 다시 돌아간다고 들었는데? | 유지장치(retainer)를 권장대로 착용하면 거의 재발하지 않습니다.\n발치를 해야만 교정이 되나요? | 케이스마다 다릅니다. 정밀 진단 후 비발치도 가능합니다.\n비용은 얼마나 드나요? | 메탈 브라켓 400~600만 원, 투명교정 600~1,000만 원 선입니다.",

		'endo' => "신경치료한 치아는 얼마나 오래 사용할 수 있나요? | 크라운 보철로 잘 보호하면 평생 사용도 가능합니다.\n신경치료가 많이 아픈가요? | 국소마취 하에 진행되어 시술 중 통증은 거의 없습니다.\n신경치료보다 발치하고 임플란트 하는 게 낫지 않나요? | 자연치를 살릴 수 있다면 무조건 살리는 것이 우선입니다.\n재근관치료는 성공률이 어느 정도인가요? | 1차 신경치료보다는 낮지만 약 70~80% 성공률을 보입니다.\n신경이 죽은 치아를 그냥 두면 어떻게 되나요? | 치근 끝에 농양·낭종이 생기고, 결국 뼈를 녹이며 인접치까지 영향을 줍니다.",

		'tmj' => "턱에서 소리만 나고 통증은 없는데 치료가 필요한가요? | 디스크 변위 가능성이 있어 정기 검진을 권합니다.\n보톡스로 정말 턱관절 통증이 좋아지나요? | 교근의 긴장이 통증의 주된 원인인 경우 효과가 빠릅니다.\n스플린트는 평생 착용해야 하나요? | 통상 3~6개월 야간 착용 후 증상이 호전되면 사용 빈도를 줄입니다.\n턱관절 수술까지 가는 경우는? | 거의 드뭅니다. 대부분 비수술적 치료로 호전됩니다.\n두통이 턱관절 때문일 수 있나요? | 네, 매우 흔합니다. 측두근·교근의 과긴장이 두통으로 이어집니다.\n임플란트 후 턱관절이 아픈데요? | 전악 임플란트나 다수 보철 후 교합이 변하면서 턱관절에 무리가 갈 수 있습니다.",

		'wisdom' => "사랑니 발치 시 신경 마비 위험은 얼마나 되나요? | 3D CT 사전 분석으로 위험을 최대한 줄입니다.\n임신 중 사랑니 발치가 가능한가요? | 가급적 출산 후로 미루는 것이 좋습니다.\n발치 후 통증은 얼마나 오래 가나요? | 일반 발치는 2~3일, 매복 사랑니는 3~5일이 통증 피크. 1주일 후엔 거의 사라집니다.\n사랑니 4개를 한 번에 빼도 되나요? | 건강한 성인의 경우 가능합니다. 보통 나눠서 진행을 권장합니다.\n발치 후 운동은 언제부터 가능한가요? | 가벼운 산책은 다음 날부터 가능합니다. 격렬한 운동은 1주일 후부터 권장합니다.\n통증이 무서운데 수면 마취도 가능한가요? | 국소마취 + 물방울 레이저 + PCA 자가진통조절기로 충분히 편안하게 진행 가능합니다.",

		'aesthetic' => "라미네이트와 미백 중 무엇이 좋나요? | 미백으로 해결 가능한 단순 변색은 미백을 우선 권합니다.\n미백 후 색이 다시 어두워지나요? | 시간이 지나며 자연스럽게 다시 착색됩니다. 보통 1~2년 후 보강 미백을 권장합니다.\n임플란트 틀니와 일반 틀니 중 무엇이 좋나요? | 임플란트 틀니가 훨씬 안정적입니다.\n전악 보철은 한 번에 모두 진행되나요? | 환자 상태에 따라 수개월~1년 이상 단계적으로 진행됩니다.\n신경치료한 치아도 미백 가능한가요? | 워킹 블리치라는 내부 미백법으로 개선 가능합니다.\n라미네이트 시술하면 치아가 약해지지 않나요? | 치아 삭제를 최소화하는 라미네이트는 자연치를 최대한 보존합니다.",

		'pediatric' => "아이는 몇 살부터 치과 진료를 받아야 하나요? | 첫 어금니가 나오는 만 1~2세부터 치과 첫 방문을 권합니다.\n아이가 치과를 너무 무서워하는데 어떻게 해야 하나요? | 거부 시 절대 강제 진료하지 않습니다. 첫 방문은 진료실 구경부터 시작할 수 있습니다.\n실란트는 꼭 해야 하나요? | 필수는 아니지만 만 6~8세 영구치 어금니가 막 나왔을 때 충치 예방 효과가 50% 이상입니다.\n소아 교정은 언제 시작해야 하나요? | 골격성 부정교합은 만 7~10세가 1차 교정 골든타임입니다.\n아이 충치 치료는 어떻게 진행되나요? | 유치 충치는 글래스아이오노머·콤포지트로 가능한 한 짧은 시간에 치료합니다.",
	);

	$groups = array();
	foreach ( $services as $key => $label ) {
		$groups[ 'svc_faq_' . $key ] = array(
			'title'  => '진료 FAQ — ' . $label,
			'fields' => array(
				"service_{$key}_faqs" => array(
					'default' => $defaults[ $key ] ?? '',
					'label'   => 'FAQ (한 줄당 1개, "질문 | 답변" 파이프 구분, HTML 가능)',
					'type'    => 'textarea',
				),
			),
		);
	}
	return $groups;
}

/**
 * 의료진 그룹명·직책 콘텐츠 필드.
 *  - 4 그룹 라벨
 *  - 9 의료진 직책 (예: "원장")
 */
function moondental_doctor_meta_content_fields() {
	$role_defaults = array(
		'munes' => array( 'name' => '문은수', 'role' => '대표 병원장' ),
		'leesj' => array( 'name' => '이승주', 'role' => '원장' ),
		'leesu' => array( 'name' => '이수연', 'role' => '원장' ),
		'kwon'  => array( 'name' => '권혜진', 'role' => '원장' ),
		'munji' => array( 'name' => '문지현', 'role' => '원장' ),
		'leech' => array( 'name' => '이창률', 'role' => '원장' ),
		'leeyi' => array( 'name' => '이영일', 'role' => '원장' ),
		'kimsi' => array( 'name' => '김세일', 'role' => '원장' ),
		'jeong' => array( 'name' => '정석형', 'role' => '원장' ),
	);

	$groups = array(
		'doctor_groups' => array(
			'title'  => '의료진 — 진료센터 그룹명 (4개)',
			'fields' => array(
				'doctor_group_1' => array( 'default' => '대표 병원장',                       'label' => '그룹 1 — 라벨', 'type' => 'text' ),
				'doctor_group_2' => array( 'default' => '보철·보존',                            'label' => '그룹 2 — 라벨', 'type' => 'text' ),
				'doctor_group_3' => array( 'default' => '임플란트·외과',                       'label' => '그룹 3 — 라벨', 'type' => 'text' ),
				'doctor_group_4' => array( 'default' => '교정·치주',                            'label' => '그룹 4 — 라벨', 'type' => 'text' ),
			),
		),
		'doctor_roles' => array(
			'title'  => '의료진 — 9명 직책 (역할 라벨)',
			'fields' => array(),
		),
	);

	foreach ( $role_defaults as $key => $d ) {
		$groups['doctor_roles']['fields'][ "doctor_{$key}_role" ] = array(
			'default' => $d['role'],
			'label'   => $d['name'] . ' — 직책',
			'type'    => 'text',
		);
	}

	return $groups;
}

/**
 * 의료진 상세 페이지 — 공통 라벨 + 9명 개별 콘텐츠.
 */
function moondental_doctor_single_content_fields() {
	$doctors = array(
		'munes' => '문은수',
		'leesj' => '이승주',
		'leesu' => '이수연',
		'kwon'  => '권혜진',
		'munji' => '문지현',
		'leech' => '이창률',
		'leeyi' => '이영일',
		'kimsi' => '김세일',
		'jeong' => '정석형',
	);

	$groups = array(

		/* ─── 공통 라벨·CTA ─── */
		'doc_single_common' => array(
			'title'  => '의료진 상세 — 공통 라벨',
			'fields' => array(
				'doc_single_intro_eyebrow' => array( 'default' => 'DOCTOR PROFILE', 'label' => 'Hero — eyebrow', 'type' => 'text' ),
				'doc_single_qa_eyebrow'    => array( 'default' => '원장 인터뷰',      'label' => 'Q&A 섹션 — eyebrow', 'type' => 'text' ),
				'doc_single_qa_title'      => array( 'default' => '원장님께 직접 들어봅니다', 'label' => 'Q&A 섹션 — 제목', 'type' => 'text' ),
				'doc_single_qa_lead'       => array( 'default' => '환자분이 가장 궁금해하시는 질문에 원장님이 직접 답변드립니다.', 'label' => 'Q&A 섹션 — 설명', 'type' => 'textarea' ),

				'doc_single_edu_eyebrow' => array( 'default' => 'Education & Career', 'label' => '학력/경력 섹션 — eyebrow', 'type' => 'text' ),
				'doc_single_edu_title'   => array( 'default' => '학력 · 경력',         'label' => '학력/경력 섹션 — 제목', 'type' => 'text' ),

				'doc_single_others_title' => array( 'default' => '다른 의료진',  'label' => '다른 의료진 섹션 — 제목', 'type' => 'text' ),

				'doc_single_cta_chip'  => array( 'default' => '상담 예약',                        'label' => '하단 CTA — chip', 'type' => 'text' ),
				'doc_single_cta_title' => array( 'default' => '원장님께 진료받고 싶으시면',         'label' => '하단 CTA — 제목 (원장님 이름은 앞에 자동)', 'type' => 'text' ),
				'doc_single_cta_lead'  => array( 'default' => '원하시는 일정에 맞춰 진료 예약을 도와드립니다.', 'label' => '하단 CTA — 설명', 'type' => 'textarea' ),
				'doc_single_cta_btn1'  => array( 'default' => '📅 상담 예약하기',                  'label' => '하단 CTA — 버튼 1', 'type' => 'text' ),

				'doc_single_back_label' => array( 'default' => '← 의료진 전체 보기', 'label' => '의료진 전체 보기 링크 라벨', 'type' => 'text' ),
			),
		),
	);

	/* v3.31.1 · 원장별 Q&A + 관심 분야 프리필 (사용자님이 편집·삭제 가능) */
	$default_qa_common = "진료 시 가장 중요하게 생각하는 원칙은 무엇인가요? | 환자분의 상황을 먼저 충분히 듣고, 꼭 필요한 치료만 권합니다. 시작 전 모든 비용을 안내드리며, 시작 후 추가 비용은 발생하지 않습니다.\n"
		. "초진 시 어떻게 진행되나요? | 상담·정밀 진단(X-ray, CBCT, 구강 검사)·치료 계획 설명 순으로 진행되며, 환자분이 충분히 검토하신 뒤 동의하신 항목만 시작합니다.\n"
		. "치료 후 사후 관리는 어떻게 이뤄지나요? | 정기 검진과 A/S 시스템으로 시술 후에도 계속 상태를 확인해드립니다. 문제가 생기면 언제든 편하게 문의해주세요.";

	$prefill_qa = array(
		'munes' => "대표 병원장으로서 문치과병원의 진료 철학을 한 마디로 하면? | 환자를 가족처럼 생각하는 마음. 1995년 개원 이래 30여년 동안 이 원칙을 지켜왔습니다.\n"
			. "30여년 동안 한자리에서 진료해오신 이유는? | 한 분의 환자를 오래 보는 것이 좋은 치과의 핵심이라고 생각합니다. 환자와 병원이 서로 믿고 오래 갈 수 있어야 진짜 진료가 됩니다.\n"
			. "몽골·중국 등 해외 봉사에 오래 참여하신 계기는? | 치과 진료가 필요한데 못 받는 분들이 있다면, 저희가 갈 수 있는 곳까지는 가야 한다고 생각합니다. 한아의료재단의 사명이기도 합니다.\n"
			. $default_qa_common,
		'jeong' => "치주치료가 왜 중요한가요? | 잇몸은 자연치아의 뿌리를 지탱하는 기초입니다. 잇몸이 무너지면 아무리 좋은 치아라도 흔들립니다. 치주는 평생 관리 대상입니다.\n"
			. "정기 스케일링은 얼마나 자주 받아야 하나요? | 대부분 6~12개월 주기가 적당합니다. 잇몸 상태에 따라 3~4개월 주기가 필요한 분도 계시니 검진 시 안내드립니다.\n"
			. $default_qa_common,
		'leesj' => "종합 진료 원장으로서 가장 자주 보시는 케이스는? | 충치·잇몸·보철이 복합된 케이스가 많습니다. 한 곳에서 전 과정을 해결하는 것이 환자분께 가장 편리합니다.\n"
			. "타원에서 치료 중이신 분도 오실 수 있나요? | 물론입니다. 진행 상황을 함께 검토해서 이어갈 부분과 다시 볼 부분을 나눠서 안내드립니다.\n"
			. $default_qa_common,
		'kimsi' => "교정을 늦게 시작해도 괜찮을까요? | 성인 교정은 언제 시작하셔도 좋은 결과를 낼 수 있습니다. 다만 잇몸·치주 상태가 준비되어 있어야 합니다.\n"
			. "투명교정과 일반 교정 중 어떤 걸 권하시나요? | 케이스에 따라 다릅니다. 정밀 진단 후 환자분의 생활 패턴·직업·기대치를 함께 고려해 최선의 방법을 제안합니다.\n"
			. $default_qa_common,
		'leesu' => "보철과 전문의로서 가장 중요하게 여기시는 부분은? | 정확한 진단과 정밀한 치료입니다. 보철은 오래 써야 하기 때문에 처음 만들 때 잘 만드는 것이 무엇보다 중요합니다.\n"
			. "크라운·틀니 재료는 어떻게 선택하나요? | 저작 압력·심미·비용을 종합적으로 고려해 환자분과 상담 후 결정합니다. 자체 기공실에서 직접 제작해 정밀도가 높습니다.\n"
			. $default_qa_common,
		'kwon'  => "보존과 전문의로서 자연치아 살리기의 원칙은? | 발치는 최후의 선택입니다. 신경치료·재근관치료로 살릴 수 있는 치아를 최대한 지키는 것이 저희 진료 철학입니다.\n"
			. "신경치료 후 통증은 얼마나 지속되나요? | 대부분 1~3일 내 사라지지만 개인차가 있습니다. 통증이 심하거나 오래 지속되면 반드시 연락 주세요.\n"
			. $default_qa_common,
		'munji' => "임플란트 수술이 두려운데 안전한가요? | 국소마취 하 진행되며 CBCT 3D 진단으로 신경·혈관을 정확히 파악한 후 시술합니다. PCA 자가진통조절기·레이저로 통증도 최소화합니다.\n"
			. "골이식이 필요한 경우가 많나요? | 골밀도가 부족하면 필요하지만, 저희는 가능한 골이식을 최소화하는 방향으로 계획을 짭니다. CT 정밀 분석 후 판단합니다.\n"
			. "당뇨·고혈압이 있는데 임플란트 가능한가요? | 전신 상태를 확인 후 안전 범위에서 진행합니다. 응급 대응 장비도 상시 준비돼 있으니 안심하셔도 됩니다.\n"
			. $default_qa_common,
		'leech' => "미국·한국 양쪽에서 진료 경험을 하셨는데 우리 병원의 강점은? | 미국식 정밀 진단·계획 수립과 한국식 세심한 사후 관리를 결합한 시스템입니다. 각 장점을 접목해왔습니다.\n"
			. "For a lifelong smile — 좌우명의 뜻은? | 평생 웃음을 위한 진료. 지금 당장 예뻐 보이는 게 아니라 10년·20년 뒤에도 편하게 웃을 수 있게 만드는 것이 목표입니다.\n"
			. $default_qa_common,
		'leeyi' => "치과교정과 전문의·인정의로서 교정 진료 방침은? | 얼굴 전체 균형과 저작·발음·턱관절까지 종합적으로 봅니다. 치아만 가지런히 하는 게 목적이 아닙니다.\n"
			. "교정 기간을 단축할 수 있는 방법이 있나요? | 정밀 진단으로 최적 계획을 세우면 불필요한 리트리트먼트를 줄일 수 있습니다. 슈어스마일 AI 시뮬레이션도 활용합니다.\n"
			. $default_qa_common,
	);

	$prefill_interests = array(
		'munes' => "종합 임상\n임플란트\n보철·심미\n국제 의료봉사\n지역사회 공헌",
		'jeong' => "치주과\n치주 수술\n스케일링·치주 관리\n임플란트 주위염\n치주보철",
		'leesj' => "종합 진료\n보존·보철\n치주 관리\n1차 진단\n정기 검진",
		'kimsi' => "치과 교정\n성인 교정\n투명교정\n소아 1차 교정\n부정교합 진단",
		'leesu' => "치과 보철\n크라운·틀니\n통합치의학\n심미 보철\n보철 재료 선택",
		'kwon'  => "치과 보존\n신경치료·재근관치료\n충치치료\n자연치아 살리기\n근관 미세 수술",
		'munji' => "임플란트\n디지털 가이드 수술\n골이식·상악동 거상술\n턱관절 진료\n임플란트 전신질환 대응",
		'leech' => "임플란트\n미국·한국 협진\n교정 임상\n투명교정\n턱관절 협진",
		'leeyi' => "치과 교정\n슈어스마일 투명교정\n일반 교정 (메탈·세라믹)\n소아·성인 교정\n교합 개선",
	);

	/* 9명 개별 — intro / 자격사항 4개 / Q&A / 관심 분야 (v3.31.1 프리필 defaults 적용) */
	foreach ( $doctors as $key => $name ) {
		$groups[ 'doc_single_' . $key ] = array(
			'title'  => '의료진 상세 — ' . $name,
			'fields' => array(
				"doc_{$key}_intro" => array(
					'default' => '',
					'label'   => $name . ' — Hero 인트로 (2~3 문장, 빈칸 시 자동 생성)',
					'type'    => 'textarea',
				),
				"doc_{$key}_credentials" => array(
					'default' => '',
					'label'   => $name . ' — 자격 체크리스트 (한 줄당 1개, 빈칸 시 약력 전체 자동 사용)',
					'type'    => 'textarea',
				),
				"doc_{$key}_qa" => array(
					'default' => $prefill_qa[ $key ] ?? $default_qa_common,
					'label'   => $name . ' — Q&A (한 줄당 1개, "질문 | 답변" 파이프 구분)',
					'type'    => 'textarea',
				),
				"doc_{$key}_interests" => array(
					'default' => $prefill_interests[ $key ] ?? '',
					'label'   => $name . ' — 관심 분야 (한 줄당 1개)',
					'type'    => 'textarea',
				),
			),
		);
	}

	return $groups;
}

/**
 * 역사 타임라인 콘텐츠 필드 (단일 텍스트영역, 한 줄당 1 항목).
 *  형식: "연도 | 월 | 제목 | 설명 | 사진파일명(선택)"
 */
function moondental_history_content_fields() {
	return array(
		'history' => array(
			'title'  => '역사 타임라인',
			'fields' => array(
				'history_timeline' => array(
					'default' => '',
					'label'   => '역사 항목 (한 줄당 1개, "연도 | 월 | 제목 | 설명 | 사진파일명" 파이프 구분, 빈 줄/#=주석. 비워두면 functions.php 기본값 사용)',
					'type'    => 'textarea',
				),
			),
		),
	);
}

/**
 * 모든 홈 콘텐츠 필드를 Customizer에 일괄 등록.
 */
function moondental_register_home_content_customizer( $wp_customize ) {
	// 부모 패널 — 기존 "문치과병원 설정" 패널 안에 새 섹션들 묶음
	$panel_id = 'md_panel_home_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '홈 콘텐츠 (전체 텍스트)',
		'description' => '홈페이지의 모든 텍스트를 여기에서 수정할 수 있습니다. 빈칸으로 두면 기본값이 표시됩니다.',
		'priority'    => 25,
	) );

	$groups = moondental_home_content_fields();
	$prio = 10;
	foreach ( $groups as $group_key => $group ) {
		$section_id = 'md_section_content_' . $group_key;
		$wp_customize->add_section( $section_id, array(
			'title'    => $group['title'],
			'panel'    => $panel_id,
			'priority' => $prio,
		) );
		$prio += 10;

		foreach ( $group['fields'] as $key => $field ) {
			$setting_id = 'md_content_' . $key;
			$default    = $field['default'];
			$type       = $field['type'] ?? 'text';

			$wp_customize->add_setting( $setting_id, array(
				'default'           => $default,
				'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'transport'         => 'refresh',
			) );
			$wp_customize->add_control( $setting_id, array(
				'label'       => $field['label'],
				'description' => isset( $field['description'] ) ? $field['description'] : '',
				'section'     => $section_id,
				'type'        => $type,
			) );
		}
	}
}
add_action( 'customize_register', 'moondental_register_home_content_customizer', 30 );

/**
 * 비용 안내 페이지 콘텐츠를 Customizer에 등록.
 */
function moondental_register_pricing_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_pricing_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '비용 안내 페이지',
		'description' => '비용 안내 페이지(/비용-안내/)의 모든 텍스트와 가격표를 편집할 수 있습니다. 가격표는 "이름 | 가격 | 비고" 형식으로 한 줄에 하나씩 입력하세요.',
		'priority'    => 27,
	) );

	$groups = moondental_pricing_content_fields();
	$prio = 10;
	foreach ( $groups as $group_key => $group ) {
		$section_id = 'md_section_pricing_' . $group_key;
		$wp_customize->add_section( $section_id, array(
			'title'    => $group['title'],
			'panel'    => $panel_id,
			'priority' => $prio,
		) );
		$prio += 10;

		foreach ( $group['fields'] as $key => $field ) {
			$setting_id = 'md_content_' . $key;
			$default    = $field['default'];
			$type       = $field['type'] ?? 'text';

			$wp_customize->add_setting( $setting_id, array(
				'default'           => $default,
				'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'transport'         => 'refresh',
			) );
			$wp_customize->add_control( $setting_id, array(
				'label'   => $field['label'],
				'section' => $section_id,
				'type'    => $type,
			) );
		}
	}
}
add_action( 'customize_register', 'moondental_register_pricing_content_customizer', 31 );

/**
 * 헤더·푸터 콘텐츠를 Customizer에 등록 (기존 패널 안에 섹션 추가).
 */
function moondental_register_chrome_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_chrome';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '헤더 · 푸터 텍스트',
		'description' => '사이트 전체에 표시되는 헤더 CTA 버튼과 푸터 텍스트를 수정합니다.',
		'priority'    => 29,
	) );

	$groups = moondental_chrome_content_fields();
	$prio = 10;
	foreach ( $groups as $group_key => $group ) {
		$section_id = 'md_section_chrome_' . $group_key;
		$wp_customize->add_section( $section_id, array(
			'title'    => $group['title'],
			'panel'    => $panel_id,
			'priority' => $prio,
		) );
		$prio += 10;

		foreach ( $group['fields'] as $key => $field ) {
			$setting_id = 'md_content_' . $key;
			$default    = $field['default'];
			$type       = $field['type'] ?? 'text';

			$wp_customize->add_setting( $setting_id, array(
				'default'           => $default,
				'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'transport'         => 'refresh',
			) );
			$wp_customize->add_control( $setting_id, array(
				'label'   => $field['label'],
				'section' => $section_id,
				'type'    => $type,
			) );
		}
	}
}
add_action( 'customize_register', 'moondental_register_chrome_content_customizer', 32 );

/**
 * 진료 페이지 7개 콘텐츠 등록.
 */
function moondental_register_service_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_service_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '진료 페이지 (7 진료)',
		'description' => '7개 진료 페이지의 환자 고민·추천 대상과 공통 섹션 텍스트를 편집합니다. 환자 고민은 한 줄에 "고민 | 솔루션" 형식.',
		'priority'    => 33,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_service_content_fields(), 'md_section_service_' );
}
add_action( 'customize_register', 'moondental_register_service_content_customizer', 33 );

/**
 * 의료진 콘텐츠 (철학·약력 override) 등록.
 */
function moondental_register_doctor_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_doctor_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '의료진 — 철학·약력 (9명)',
		'description' => '각 의료진의 진료 철학·약력을 편집합니다. 빈칸으로 두면 기본값 표시.',
		'priority'    => 34,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_doctor_content_fields(), 'md_section_doctor_' );
}
add_action( 'customize_register', 'moondental_register_doctor_content_customizer', 34 );

/**
 * 서브 페이지 (오시는 길·사명&역사·의료진 페이지·예약) 콘텐츠 등록.
 */
function moondental_register_subpage_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_subpage_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '서브 페이지 텍스트',
		'description' => '오시는 길·30여년의 발자취·의료진 페이지 등의 텍스트를 편집합니다.',
		'priority'    => 35,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_subpage_content_fields(), 'md_section_subpage_' );
}
add_action( 'customize_register', 'moondental_register_subpage_content_customizer', 35 );

/**
 * 환자 후기 콘텐츠 등록.
 */
function moondental_register_testimonials_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_testimonials_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '환자 후기 (6개)',
		'description' => '홈페이지 환자 후기 6개를 편집합니다. 이름을 비우면 해당 후기 카드가 자동으로 숨겨집니다.',
		'priority'    => 36,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_testimonials_content_fields(), 'md_section_testi_' );
}
add_action( 'customize_register', 'moondental_register_testimonials_content_customizer', 36 );

/**
 * 비교표 콘텐츠 등록.
 */
function moondental_register_compare_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_compare_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '비교표 (치과병원 vs 일반치과)',
		'description' => '진료 페이지 비교표의 9개 행을 편집합니다. 행 항목명을 비우면 해당 행이 숨겨집니다.',
		'priority'    => 37,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_compare_content_fields(), 'md_section_compare_' );
}
add_action( 'customize_register', 'moondental_register_compare_content_customizer', 37 );

/**
 * 진료별 FAQ 콘텐츠 등록.
 */
function moondental_register_service_faq_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_service_faq_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '진료영역별 FAQ (7 진료)',
		'description' => '각 진료 페이지 하단 FAQ를 편집합니다. 한 줄에 "질문 | 답변" 파이프 구분으로 입력하세요.',
		'priority'    => 38,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_service_faq_content_fields(), 'md_section_svc_faq_' );
}
add_action( 'customize_register', 'moondental_register_service_faq_content_customizer', 38 );

/**
 * 의료진 직책·그룹명 콘텐츠 등록.
 */
function moondental_register_doctor_meta_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_doctor_meta_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '의료진 — 그룹명·직책',
		'description' => '의료진 진료센터 그룹명 4개와 9명 직책(역할 라벨)을 편집합니다.',
		'priority'    => 39,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_doctor_meta_content_fields(), 'md_section_doc_meta_' );
}
add_action( 'customize_register', 'moondental_register_doctor_meta_content_customizer', 39 );

/**
 * 의료진 상세 페이지 콘텐츠 등록.
 */
function moondental_register_doctor_single_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_doctor_single_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '의료진 상세 페이지 (9명)',
		'description' => '/의료진/{이름}/ 개별 의료진 상세 페이지의 텍스트를 편집합니다. 모든 필드를 비우면 적절한 기본값으로 자동 표시.',
		'priority'    => 41,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_doctor_single_content_fields(), 'md_section_doc_single_' );
}
add_action( 'customize_register', 'moondental_register_doctor_single_content_customizer', 41 );

/**
 * 역사 타임라인 콘텐츠 등록.
 */
function moondental_register_history_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_history_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '역사 타임라인',
		'description' => '역사 페이지 연표 항목을 텍스트로 편집합니다. 한 줄당 1 항목, 파이프(|)로 필드 구분.',
		'priority'    => 40,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_history_content_fields(), 'md_section_history_' );
}
add_action( 'customize_register', 'moondental_register_history_content_customizer', 40 );

/**
 * 자연치아 살리기 페이지 콘텐츠 등록.
 */
function moondental_register_preservation_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_preservation_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '자연치아 살리기 · 콘텐츠',
		'description' => '/자연치아-살리기/ 페이지의 히어로·앵커 네비·3개 섹션(충치·신경·잇몸)·CTA 텍스트를 편집합니다. 카드/리스트는 한 줄당 1항목, 파이프(|)로 필드를 구분합니다.',
		'priority'    => 42,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_preservation_content_fields(), 'md_section_preservation_' );
}
add_action( 'customize_register', 'moondental_register_preservation_content_customizer', 42 );

/**
 * 스마일디자인 페이지 콘텐츠 등록.
 */
function moondental_register_smile_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_smile_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '스마일디자인 · 콘텐츠',
		'description' => '/스마일디자인센터/ 페이지의 히어로·5개 섹션(라미네이트·심미레진·미백·잇몸미백·거미스마일)·CTA를 편집합니다.',
		'priority'    => 43,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_smile_content_fields(), 'md_section_smile_' );
}
add_action( 'customize_register', 'moondental_register_smile_content_customizer', 43 );

/**
 * 예방클리닉 페이지 콘텐츠 등록.
 */
function moondental_register_prevention_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_prevention_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '예방클리닉 · 콘텐츠',
		'description' => '/예방클리닉/ 페이지의 히어로·5개 섹션(덴탈SPA·스케일링·에어플로우·불소·실란트)·CTA를 편집합니다.',
		'priority'    => 44,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_prevention_content_fields(), 'md_section_prevention_' );
}
add_action( 'customize_register', 'moondental_register_prevention_content_customizer', 44 );

/**
 * 상시채용 페이지 콘텐츠 등록.
 */
function moondental_register_recruit_page_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_recruit_page_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '상시채용 · 콘텐츠',
		'description' => '/상시채용/ 페이지의 히어로·모집 대상·복리후생 6카테고리·WHY 카드·지원 방법·CTA를 편집합니다. (인사팀 이메일 설정은 별도 "채용 · HR 연락처" 패널에)',
		'priority'    => 45,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_recruit_page_content_fields(), 'md_section_recruit_page_' );
}
add_action( 'customize_register', 'moondental_register_recruit_page_content_customizer', 45 );

/**
 * 지역별 페이지 콘텐츠 등록 (28개 URL 공통 템플릿).
 */
function moondental_register_region_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_region_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '지역 페이지 · 공통 콘텐츠',
		'description' => '/오시는-길/{지역}/ URL에서 사용되는 공통 템플릿. 아래 필드에서 {region}·{province}·{duration}·{distance}·{highway} 등의 토큰이 각 지역 값으로 자동 치환됩니다.',
		'priority'    => 46,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_region_content_fields(), 'md_section_region_' );
}
add_action( 'customize_register', 'moondental_register_region_content_customizer', 46 );

/**
 * 예약·소식·오시는 길·후기 나머지 텍스트 등록.
 */
function moondental_register_misc_pages_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_misc_pages_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '예약·소식·오시는 길 · 나머지 문구',
		'description' => '예약 페이지 FAQ, 소식 페이지 히어로·섹션, 오시는 길 요일 라벨, 홈 후기 하단 문구 등을 편집합니다.',
		'priority'    => 47,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_misc_pages_content_fields(), 'md_section_misc_' );
}
add_action( 'customize_register', 'moondental_register_misc_pages_content_customizer', 47 );

/**
 * 셀프진단봇 콘텐츠 등록.
 */
function moondental_register_bot_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_bot_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '셀프진단봇 · 콘텐츠',
		'description' => '홈·예약 페이지의 구강상태 자가진단봇. UI 문구·30개 질문·8개 추천 진료과·우측 예약 aside를 편집합니다. 질문 형식: 카테고리 | 질문 | 진료과키:가중치, 진료과키:가중치',
		'priority'    => 48,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_bot_content_fields(), 'md_section_bot_' );
}
add_action( 'customize_register', 'moondental_register_bot_content_customizer', 48 );

/**
 * 마무리 · 잔여 콘텐츠 등록.
 */
function moondental_register_finish_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_finish_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '나머지 · 의료진·오시는길·홈소식',
		'description' => '의료진 페이지 진료과·마이크로카피, 오시는 길 페이지 히어로·지역·채널 카드, 홈 소식 섹션 서브 헤딩·라벨.',
		'priority'    => 49,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_finish_content_fields(), 'md_section_finish_' );
}
add_action( 'customize_register', 'moondental_register_finish_content_customizer', 49 );

/**
 * 진짜 최종 · 잔여 마이크로카피 등록.
 */
function moondental_register_final_content_customizer( $wp_customize ) {
	$panel_id = 'md_panel_final_content';
	$wp_customize->add_panel( $panel_id, array(
		'title'       => '진짜 마지막 · 마이크로카피·플로팅 CTA·푸터 라벨',
		'description' => '역사 페이지 리드, 모바일 하단·데스크탑 플로팅 CTA 라벨, 강점·서비스 페이지 헤딩, 지역 404 텍스트, 푸터 요일/법적 표시 프리픽스 등 나머지 텍스트.',
		'priority'    => 50,
	) );
	moondental_register_panel_groups( $wp_customize, $panel_id, moondental_final_content_fields(), 'md_section_final_' );
}
add_action( 'customize_register', 'moondental_register_final_content_customizer', 50 );

/* v3.33.7 · Customizer "진료 페이지 본문 HTML" 패널 제거.
 *  진료 페이지 본문은 wp-admin → 페이지 → (임플란트 센터 등) 편집에서
 *  블록 에디터로 자유롭게 편집하는 것이 표준입니다.
 *  마이그레이션이 WP 페이지 본문에 기본 HTML을 자동 시드해줍니다.
 *  아래 필드 정의 함수는 md_content 캐시 호환용으로만 남깁니다.
 */

/**
 * 자연치아 살리기 페이지 (충치·신경·잇몸 3섹션) 콘텐츠.
 */
function moondental_preservation_content_fields() {
	return array(
		'hero' => array(
			'title'  => '자연치아 살리기 · 히어로',
			'fields' => array(
				'preservation_hero_eyebrow' => array( 'default' => 'PRESERVATION · 자연치아 살리기', 'label' => '히어로 · eyebrow', 'type' => 'text' ),
				'preservation_hero_title_a' => array( 'default' => '천안·아산 자연치아 살리기', 'label' => '히어로 · 제목 첫 줄', 'type' => 'text' ),
				'preservation_hero_title_b' => array( 'default' => '발치보다 보존이 먼저입니다', 'label' => '히어로 · 제목 강조 (em)', 'type' => 'text' ),
				'preservation_hero_lead'    => array( 'default' => "충치치료·신경치료·잇몸치료 — 보존과·치주과 전문 진료로 환자분의 자연치아를 최대한 살립니다.\n천안 만남로 1995년 개원 30여년 임상.", 'label' => '히어로 · 리드 (줄바꿈 유지)', 'type' => 'textarea' ),
				'preservation_nav_items'    => array( 'default' => "🦷 | 충치치료 | #cavity\n⚡ | 신경치료 | #endo\n🌿 | 잇몸치료 | #perio", 'label' => '앵커 네비 항목 (한 줄에 1개, 형식: 아이콘 | 라벨 | 앵커)', 'type' => 'textarea' ),
			),
		),
		'cavity' => array(
			'title'  => '01 · 충치치료',
			'fields' => array(
				'preservation_cavity_eyebrow' => array( 'default' => '01 · CAVITY TREATMENT', 'label' => 'eyebrow', 'type' => 'text' ),
				'preservation_cavity_title'   => array( 'default' => '천안·아산 충치치료 — 보존적 접근으로 자연치아 최대한 살리기', 'label' => '섹션 제목', 'type' => 'text' ),
				'preservation_cavity_lead'    => array( 'default' => '충치는 조기 발견·조기 치료가 핵심입니다. 진행 단계에 따라 가장 보존적인 방법을 선택합니다.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'preservation_cavity_cards'   => array(
					'default' => "초기 | 충치 초기 — 불소도포 · 실란트 | 치아 표면에 미세한 변색·법랑질 손상이 시작된 단계. 삭제 없이 <strong>고농도 불소도포</strong>로 재광화를 유도합니다. 어금니는 <strong>실란트(홈메우기)</strong>로 추가 충치를 예방.\n중기 | 중기 충치 — 심미 레진 충전 | 법랑질을 지나 상아질에 충치가 진행된 단계. <strong>최소 삭제 + 심미 레진 충전</strong>으로 자연치아 형태와 색을 그대로 복원. 1~2회 내원으로 완료.\n진행 | 진행 충치 — 세라믹 인레이·온레이 | 충치 범위가 넓어 레진만으로 부족한 경우 <strong>세라믹 인레이/온레이</strong>로 정밀 복원. 강도·심미·내구성 모두 우수. 13층 자체 기공실 직접 제작.\n심부 | 심부 충치 — 신경 보존 직접치수복조 | 충치가 신경에 근접했지만 살아있는 경우 <strong>직접치수복조(direct pulp capping)</strong>으로 신경을 살리는 시도. 신경치료 없이 자연치아 보존 가능성.\n광범위 | 광범위 충치 — 크라운 (지르코니아·금) | 충치로 치아 구조가 크게 손상된 경우 신경치료 후 <strong>크라운(지르코니아·금)</strong>으로 강도 회복. 자체 기공실 보철로 정밀한 적합도.\n예방 | 충치 재발 예방 | 치료 후 6개월~1년 정기 검진, 스케일링, 에어플로우, 불소도포로 재발 예방. 양치 습관과 식이 관리도 함께 안내.",
					'label' => '카드 (한 줄에 1개, 형식: 스테이지 | 제목 | 본문)',
					'type'  => 'textarea',
				),
				'preservation_cavity_callout_title' => array( 'default' => '💡 충치치료 비용 안내', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'preservation_cavity_callout_body'  => array( 'default' => '레진 충전·세라믹 인레이·지르코니아 크라운 등 재료별 비용은 정확한 진단 후 산정합니다. <a href="/비용-안내/">비용 안내 자세히 보기 →</a>', 'label' => '콜아웃 · 본문 (HTML 허용)', 'type' => 'textarea' ),
			),
		),
		'endo' => array(
			'title'  => '02 · 신경치료',
			'fields' => array(
				'preservation_endo_eyebrow' => array( 'default' => '02 · ENDODONTICS', 'label' => 'eyebrow', 'type' => 'text' ),
				'preservation_endo_title'   => array( 'default' => '천안·아산 신경치료 — 치아 보존의 마지막 기회', 'label' => '섹션 제목', 'type' => 'text' ),
				'preservation_endo_lead'    => array( 'default' => '충치가 신경까지 도달한 경우, 신경치료로 발치를 막고 자연치아를 살립니다. 보존과 전문의의 정밀 근관치료.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'preservation_endo_when_title' => array( 'default' => '언제 신경치료가 필요한가요?', 'label' => '증상 리스트 · 소제목', 'type' => 'text' ),
				'preservation_endo_when_list'  => array(
					'default' => "<strong>가만히 있어도 욱신거리는 통증</strong> — 신경 염증의 대표 증상\n<strong>잠을 못 잘 정도의 야간 통증</strong> — 화농성 염증 의심\n<strong>차거나 뜨거운 자극에 통증이 오래 지속</strong>\n<strong>치아 색이 어둡게 변색</strong> — 신경 괴사 가능성\n<strong>잇몸에 고름·물집 (치근단 농양)</strong> — 신경 손상 후 염증 확산\n<strong>외상으로 치아가 파절</strong> — 신경 노출",
					'label' => '증상 리스트 (한 줄에 1개, HTML 허용)',
					'type'  => 'textarea',
				),
				'preservation_endo_strength_title' => array( 'default' => '문치과병원 신경치료의 강점', 'label' => '강점 카드 · 소제목', 'type' => 'text' ),
				'preservation_endo_strength_cards' => array(
					'default' => "🔬 CBCT 3D 진단 | 일반 X-ray로 보이지 않는 신경관의 분지·곡률·세부 구조를 3D로 정확히 파악. 누락 없는 근관치료.\n⚡ NiTi 회전 파일 | 최신 NiTi 회전 파일 시스템으로 신경관 내부를 정밀 세척·확대. 천공·분리 위험 최소화.\n🔄 재근관치료 가능 | 다른 곳에서 신경치료가 실패한 케이스도 재근관치료로 발치 없이 살리는 시도. 30여년 임상 경력.\n🦷 치근단수술 (Apicoectomy) | 일반 근관치료로 해결 안 되는 치근단 염증을 외과적으로 제거. 구강악안면외과 협진.",
					'label' => '강점 카드 (한 줄에 1개, 형식: 아이콘 + 제목 | 본문)',
					'type'  => 'textarea',
				),
				'preservation_endo_callout_title' => array( 'default' => '⏱️ 신경치료 진행 흐름', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'preservation_endo_callout_body'  => array( 'default' => '1차 — 신경 제거 + 임시 충전 / 2차 — 신경관 세척·소독 / 3차 — 영구 충전 + 코어 / 4차 — 크라운 마무리. 통상 2~4회 내원, 케이스에 따라 다름.', 'label' => '콜아웃 · 본문', 'type' => 'textarea' ),
			),
		),
		'perio' => array(
			'title'  => '03 · 잇몸치료',
			'fields' => array(
				'preservation_perio_eyebrow' => array( 'default' => '03 · PERIODONTICS', 'label' => 'eyebrow', 'type' => 'text' ),
				'preservation_perio_title'   => array( 'default' => '천안·아산 잇몸치료 — 치주염 진행 막기', 'label' => '섹션 제목', 'type' => 'text' ),
				'preservation_perio_lead'    => array( 'default' => '잇몸 출혈·붓기·입냄새는 치주염의 신호. 자연치아 평생 건강의 핵심은 잇몸 관리입니다.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'preservation_perio_h3'      => array( 'default' => '치주질환 단계별 치료', 'label' => '단계별 치료 · 소제목', 'type' => 'text' ),
				'preservation_perio_cards'   => array(
					'default' => "단계 1 | 치은염 — 스케일링 | 잇몸이 붉고 잘 붓는 단계. <strong>스케일링(보험 적용, 연 1회)</strong>으로 치석·치태 제거. 양치 습관 교정으로 회복 가능.\n단계 2 | 경증 치주염 — 치근활택술 | 치주 포켓이 깊어지기 시작. <strong>치근활택술(SRP)</strong>로 치근 표면을 매끄럽게 다듬어 치태 부착 방지. 보험 적용.\n단계 3 | 중등도 치주염 — 치주소파술 | 치주 포켓 5mm 이상. <strong>치주소파술</strong>로 깊이 있는 염증 조직 제거. 보험 적용.\n단계 4 | 중증 치주염 — 치주 수술 | 치조골 손실 진행. <strong>치주 판막 수술 + 골 이식</strong>으로 골 재생 시도. 치아를 살리는 마지막 시도.\n유지 | 치주 유지관리 (SPT) | 치료 후 3~6개월 간격 정기 점검·스케일링. <strong>치주 유지관리 프로그램</strong>으로 재발 방지.\n보조 | 잇몸 PDRN 주사 | 잇몸 염증 완화·재생 촉진을 위한 <strong>PDRN(DNA 단편) 주사</strong>. 시술당 비급여.",
					'label' => '카드 (한 줄에 1개, 형식: 스테이지 | 제목 | 본문)',
					'type'  => 'textarea',
				),
				'preservation_perio_callout_title' => array( 'default' => '📌 잇몸 건강 자가 체크', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'preservation_perio_callout_body'  => array( 'default' => '✓ 양치 시 자주 피가 난다 / ✓ 잇몸이 부어 보인다 / ✓ 치아가 길어 보인다 / ✓ 입냄새가 심해졌다 / ✓ 음식 끼임이 잦아졌다 → 2가지 이상 해당되면 천안 만남로 문치과병원 잇몸 검진을 권해드립니다.', 'label' => '콜아웃 · 본문', 'type' => 'textarea' ),
			),
		),
		'cta' => array(
			'title'  => '자연치아 살리기 · CTA',
			'fields' => array(
				'preservation_cta_chip'  => array( 'default' => '🦷 자연치아 살리기 상담', 'label' => 'CTA 칩', 'type' => 'text' ),
				'preservation_cta_title' => array( 'default' => "발치 권유받으셨나요?\n천안·아산 문치과병원에서 한 번 더 살펴보세요", 'label' => 'CTA 제목 (줄바꿈 유지)', 'type' => 'textarea' ),
				'preservation_cta_lead'  => array( 'default' => '보존과·치주과 전문 의료진의 정밀 진단으로 자연치아를 살릴 수 있는지 검토해드립니다.', 'label' => 'CTA 리드', 'type' => 'textarea' ),
			),
		),
	);
}

/**
 * 스마일디자인센터 페이지 (라미네이트·심미레진·미백·잇몸미백·거미스마일) 콘텐츠.
 */
function moondental_smile_content_fields() {
	return array(
		'hero' => array(
			'title'  => '스마일디자인 · 히어로',
			'fields' => array(
				'smile_hero_eyebrow' => array( 'default' => 'SMILE DESIGN CENTER · 천안·아산 심미치과', 'label' => '히어로 · eyebrow', 'type' => 'text' ),
				'smile_hero_title_a' => array( 'default' => '천안·아산 스마일디자인센터', 'label' => '히어로 · 제목 첫 줄', 'type' => 'text' ),
				'smile_hero_title_b' => array( 'default' => '자연스러운 미소를 디자인합니다', 'label' => '히어로 · 제목 강조 (em)', 'type' => 'text' ),
				'smile_hero_lead'    => array( 'default' => "최소 침습 라미네이트·심미 레진·전문가 치아미백·잇몸미백·거미스마일 —\n환자분의 얼굴·치아·잇몸 라인을 종합적으로 분석해 맞춤 스마일을 설계합니다.", 'label' => '히어로 · 리드 (줄바꿈 유지)', 'type' => 'textarea' ),
				'smile_nav_items'    => array( 'default' => "💎 | 라미네이트 | #laminate\n🎨 | 심미레진 | #aesthetic-resin\n✨ | 치아미백 | #whitening\n🌸 | 잇몸미백 | #gum-whitening\n😊 | 거미스마일 | #gummy", 'label' => '앵커 네비 (한 줄에 1개, 형식: 아이콘 | 라벨 | 앵커)', 'type' => 'textarea' ),
			),
		),
		'laminate' => array(
			'title'  => '01 · 라미네이트',
			'fields' => array(
				'smile_laminate_eyebrow' => array( 'default' => '01 · LAMINATE', 'label' => 'eyebrow', 'type' => 'text' ),
				'smile_laminate_title'   => array( 'default' => '천안·아산 최소침습 라미네이트', 'label' => '섹션 제목', 'type' => 'text' ),
				'smile_laminate_lead'    => array( 'default' => '자연치아 삭제를 최소화하면서 앞니의 색·모양·길이를 자연스럽게 개선합니다.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'smile_laminate_cards'   => array(
					'default' => "🔬 최소 삭제 (No-prep/Minimal) | 전통 라미네이트 대비 치아 삭제량을 최소화. 얇은 세라믹 쉘(0.3~0.5mm)을 자연치아 표면에 부착해 변색·균열·작은 틈을 자연스럽게 가립니다.\n🎨 디지털 스마일 디자인 | 구강 스캐너로 정밀 본을 뜨고, 환자 얼굴·잇몸·치아 비율을 분석해 <strong>맞춤 시뮬레이션</strong>. 시작 전 결과를 미리 확인.\n⚙️ 원내 기공실 직접 제작 | 13층 한아 임플란트 보철연구소에서 <strong>자체 제작</strong>. 시적 시 미세 수정 즉시 가능, 색·형태 정확도 우수.\n🦷 e.max·Empress 세라믹 | 심미성·강도 균형이 우수한 e.max·Empress 등 글로벌 세라믹 사용. 자연치아와 구분이 어려운 투명도.",
					'label' => '강점 카드 (한 줄에 1개, 형식: 아이콘 + 제목 | 본문)',
					'type'  => 'textarea',
				),
				'smile_laminate_reco_title' => array( 'default' => '이런 분께 라미네이트를 권합니다', 'label' => '추천 대상 · 소제목', 'type' => 'text' ),
				'smile_laminate_reco_list'  => array(
					'default' => "앞니가 변색되어 미백만으론 한계가 있는 경우\n앞니 길이·모양·잇몸 라인을 동시에 개선하고 싶은 경우\n앞니 사이가 약간 벌어져 있는 경우 (디아스테마)\n외상으로 앞니가 살짝 깨지거나 마모된 경우\n결혼·면접·중요 행사를 앞두고 단기간에 미소를 개선하고 싶은 경우",
					'label' => '추천 대상 리스트 (한 줄에 1개)',
					'type'  => 'textarea',
				),
				'smile_laminate_callout_title' => array( 'default' => '💎 라미네이트 비용', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'smile_laminate_callout_body'  => array( 'default' => '정확한 견적은 진단 후 산정. <a href="/비용-안내/">비용 안내 →</a>', 'label' => '콜아웃 · 본문 (HTML 허용)', 'type' => 'textarea' ),
			),
		),
		'aesthetic_resin' => array(
			'title'  => '02 · 심미레진',
			'fields' => array(
				'smile_resin_eyebrow' => array( 'default' => '02 · AESTHETIC RESIN', 'label' => 'eyebrow', 'type' => 'text' ),
				'smile_resin_title'   => array( 'default' => '천안·아산 심미레진 — 자연치아 손상 없이 모양 개선', 'label' => '섹션 제목', 'type' => 'text' ),
				'smile_resin_lead'    => array( 'default' => '치아를 거의 깎지 않고 레진(복합 재료)을 직접 쌓아 모양·색을 다듬는 시술. 1회 내원으로 완료.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'smile_resin_cards'   => array(
					'default' => "치아 사이 틈 메우기 (디아스테마) | 앞니 사이 벌어진 틈을 자연스러운 색의 레진으로 메워 균형 잡힌 치열 만들기. 치아 삭제 없음.\n치아 끝 마모·결손 복원 | 앞니 끝이 닳거나 작게 깨진 부분을 레진으로 자연스럽게 복원. 발치·신경치료 불필요.\n치경부 마모증 (잇몸 라인) | 잘못된 양치질로 잇몸 경계가 패인 경우, 시린 증상도 함께 해결하는 보존적 치료.\n레진 비니어 (앞니 심미) | 변색·작은 형태 개선에 적합. 라미네이트보다 보존적·저렴하지만 내구성·심미성은 낮음.",
					'label' => '카드 (한 줄에 1개, 형식: 제목 | 본문)',
					'type'  => 'textarea',
				),
				'smile_resin_callout_title' => array( 'default' => '🎨 심미레진 vs 라미네이트', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'smile_resin_callout_body'  => array( 'default' => '심미레진은 <strong>1회 내원·저렴·자연치아 보존</strong>이 강점. 라미네이트는 <strong>심미성·내구성·색 안정성</strong>이 우수. 환자분의 케이스와 예산에 맞춰 추천드립니다.', 'label' => '콜아웃 · 본문', 'type' => 'textarea' ),
			),
		),
		'whitening' => array(
			'title'  => '03 · 치아미백',
			'fields' => array(
				'smile_white_eyebrow' => array( 'default' => '03 · WHITENING', 'label' => 'eyebrow', 'type' => 'text' ),
				'smile_white_title'   => array( 'default' => '천안·아산 전문가 치아미백', 'label' => '섹션 제목', 'type' => 'text' ),
				'smile_white_lead'    => array( 'default' => '자연치아를 손상 없이 밝게 — 환자 상태에 맞춘 자가·전문가·복합 미백 프로그램.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'smile_white_cards'   => array(
					'default' => "자가 | 홈 화이트닝 (4주 키트) | 맞춤 트레이 + 미백제를 받아 집에서 매일 1시간씩 4주 진행. 점진적이라 시린 증상 적음.\n1일 | 1-Day 전문가 미백 | 병원에서 1회 방문(약 60~90분)으로 미백 완료. 빠른 결과 원하시는 분 추천.\n2일 | 2-Day 전문가 미백 | 이틀에 걸쳐 두 번 시술로 효과·지속성 강화. 변색이 심하거나 더 밝은 톤 원하시는 분.\n복합 | 전문가 + 홈 복합 미백 | 병원 전문가 미백 + 홈 화이트닝 병행으로 효과 극대화·지속성 강화. 보철물 색에 맞춘 자연치아 미백에 추천.",
					'label' => '카드 (한 줄에 1개, 형식: 스테이지 | 제목 | 본문)',
					'type'  => 'textarea',
				),
				'smile_white_note_title' => array( 'default' => '미백 시 주의사항', 'label' => '주의사항 · 소제목', 'type' => 'text' ),
				'smile_white_note_list'  => array(
					'default' => "미백 후 1주일 색소 음식(커피·홍차·와인·카레 등) 피하기\n임신·수유 중에는 미백 권장하지 않음 (안전성 미확인)\n크라운·보철물·라미네이트는 미백 안 됨 — 자연치아 톤 맞춰 보철 재제작 검토\n심한 시린 증상이 있는 경우 시술 전 알려주세요",
					'label' => '주의사항 리스트 (한 줄에 1개)',
					'type'  => 'textarea',
				),
			),
		),
		'gum_whitening' => array(
			'title'  => '04 · 잇몸미백',
			'fields' => array(
				'smile_gum_eyebrow' => array( 'default' => '04 · GUM WHITENING', 'label' => 'eyebrow', 'type' => 'text' ),
				'smile_gum_title'   => array( 'default' => '천안·아산 잇몸미백 (레이저)', 'label' => '섹션 제목', 'type' => 'text' ),
				'smile_gum_lead'    => array( 'default' => '검거나 어두운 잇몸을 자연스러운 핑크 톤으로 — 레이저로 안전하게.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'smile_gum_cards'   => array(
					'default' => "🔆 레이저 잇몸 미백 원리 | 잇몸 표면의 멜라닌 색소를 <strong>레이저로 제거</strong>해 자연스러운 핑크 톤 회복. 절개 없이 표면 처리로 통증·출혈 최소.\n⏱️ 시술 시간 · 회복 | 1회 시술 약 30~60분. 시술 후 1~2주 색소 음식 피하기. 새 잇몸이 자라면서 점진적으로 밝아짐.\n이런 분께 추천 | 웃을 때 잇몸이 어둡게 보여 신경 쓰이는 분 / 라미네이트·미백 후 잇몸 색과 부조화 해결 / 멜라닌 색소가 강한 분.\n💰 비용 안내 | 잇몸 색소 정도에 따라 차이. 사전 무료 상담 가능.",
					'label' => '카드 (한 줄에 1개, 형식: 제목 | 본문)',
					'type'  => 'textarea',
				),
			),
		),
		'gummy' => array(
			'title'  => '05 · 거미스마일',
			'fields' => array(
				'smile_gummy_eyebrow' => array( 'default' => '05 · GUMMY SMILE', 'label' => 'eyebrow', 'type' => 'text' ),
				'smile_gummy_title'   => array( 'default' => '천안·아산 거미스마일 치료', 'label' => '섹션 제목', 'type' => 'text' ),
				'smile_gummy_lead'    => array( 'default' => '웃을 때 잇몸이 많이 보이는 거미스마일 — 원인별 맞춤 치료로 자연스러운 미소 디자인.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'smile_gummy_h3'      => array( 'default' => '거미스마일의 4가지 원인', 'label' => '카드 · 소제목', 'type' => 'text' ),
				'smile_gummy_cards'   => array(
					'default' => "원인 1 | 잇몸 라인이 낮음 | 치아 길이는 정상이지만 잇몸이 치아를 많이 덮고 있음. <strong>잇몸 성형술(레이저)</strong>로 잇몸 라인 조정.\n원인 2 | 치아 길이가 짧음 | 자연치아가 짧아 잇몸 비중이 큼. <strong>크라운 연장술 + 라미네이트</strong>로 치아 길이 회복 + 비율 조정.\n원인 3 | 윗입술 근육 과활동 | 입술 거상근이 과도하게 작용해 입술이 많이 올라감. <strong>보톡스</strong>로 근육 활동 조절 — 3~6개월 효과.\n원인 4 | 상악골 과성장 | 위턱 자체가 과성장한 골격성 원인. <strong>교정 + 양악 수술 협진</strong> 필요. 11F 교정과 + 외부 외과 협진.",
					'label' => '카드 (한 줄에 1개, 형식: 스테이지 | 제목 | 본문)',
					'type'  => 'textarea',
				),
				'smile_gummy_callout_title' => array( 'default' => '📐 거미스마일 진단 흐름', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'smile_gummy_callout_body'  => array( 'default' => '① 안모 분석 — 사진·동영상으로 미소선 측정 / ② CBCT로 잇몸·치아 비율 확인 / ③ 4가지 원인 중 어디에 해당하는지 진단 / ④ 환자에게 맞는 단일·복합 치료 계획 제시.', 'label' => '콜아웃 · 본문', 'type' => 'textarea' ),
			),
		),
		'cta' => array(
			'title'  => '스마일디자인 · CTA',
			'fields' => array(
				'smile_cta_chip'  => array( 'default' => '✨ 스마일디자인 무료 상담', 'label' => 'CTA 칩', 'type' => 'text' ),
				'smile_cta_title' => array( 'default' => "지금 내 미소,\n천안·아산 문치과병원에서 디자인해보세요", 'label' => 'CTA 제목 (줄바꿈 유지)', 'type' => 'textarea' ),
				'smile_cta_lead'  => array( 'default' => '디지털 스마일 시뮬레이션으로 결과를 미리 확인하고 시작할 수 있습니다.', 'label' => 'CTA 리드', 'type' => 'textarea' ),
			),
		),
	);
}

/**
 * 예방클리닉 페이지 (덴탈SPA·스케일링·에어플로우·불소·실란트) 콘텐츠.
 */
function moondental_prevention_content_fields() {
	return array(
		'hero' => array(
			'title'  => '예방클리닉 · 히어로',
			'fields' => array(
				'prevention_hero_eyebrow' => array( 'default' => 'PREVENTION · 천안·아산 예방클리닉', 'label' => '히어로 · eyebrow', 'type' => 'text' ),
				'prevention_hero_title_a' => array( 'default' => '천안·아산 예방클리닉 · 덴탈 SPA', 'label' => '히어로 · 제목 첫 줄', 'type' => 'text' ),
				'prevention_hero_title_b' => array( 'default' => '치료보다 예방이 먼저입니다', 'label' => '히어로 · 제목 강조 (em)', 'type' => 'text' ),
				'prevention_hero_lead'    => array( 'default' => "충치·치주염이 시작되기 전에 막는 것이 가장 경제적이고 보존적인 치료입니다.\n천안 만남로 문치과병원 예방클리닉의 덴탈 SPA·에어플로우·불소도포·실란트.", 'label' => '히어로 · 리드', 'type' => 'textarea' ),
				'prevention_nav_items'    => array( 'default' => "💆 | 덴탈 SPA | #dental-spa\n🦷 | 스케일링 | #scaling\n💨 | 에어플로우 | #airflow\n✨ | 불소도포 | #fluoride\n🛡️ | 실란트 | #sealant", 'label' => '앵커 네비 (아이콘 | 라벨 | 앵커)', 'type' => 'textarea' ),
			),
		),
		'spa' => array(
			'title'  => '01 · 덴탈 SPA',
			'fields' => array(
				'prevention_spa_eyebrow' => array( 'default' => '01 · DENTAL SPA', 'label' => 'eyebrow', 'type' => 'text' ),
				'prevention_spa_title'   => array( 'default' => '천안·아산 덴탈 SPA — 종합 예방 프로그램', 'label' => '섹션 제목', 'type' => 'text' ),
				'prevention_spa_lead'    => array( 'default' => '스케일링 + 에어플로우 + 불소도포를 하나의 코스로 — 한 번 방문으로 구강 전체를 깨끗하게.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'prevention_spa_steps_title' => array( 'default' => '덴탈 SPA 진행 순서 (약 60~90분)', 'label' => '단계 · 소제목', 'type' => 'text' ),
				'prevention_spa_steps'   => array(
					'default' => "1단계 · 구강 진단 | 치아·잇몸 상태 점검 + 사진·X-ray 촬영. 충치 초기·치주 상태·치석 정도 파악.\n2단계 · 정밀 스케일링 | 초음파 스케일러로 치석·치태 제거. 보험 적용(연 1회) 가능.\n3단계 · 에어플로우 (Air Flow) | 고운 미세 분말을 분사해 색소 침착·잔여 치태를 깔끔하게 제거. 커피·홍차·담배 착색 효과적.\n4단계 · 잇몸 마사지·관리 | 잇몸 라인 정리, PDRN·콜라겐 부스터 등 옵션. 잇몸 혈류 개선·재생 촉진.\n5단계 · 불소도포 | 고농도 불소로 치아 재광화·충치 예방. 시린 증상 완화에도 도움.\n6단계 · 맞춤 양치 코칭 | 환자별 양치 습관·도구 추천. 치실·치간칫솔 사용법 안내.",
					'label' => '단계 (한 줄에 1개, 형식: 제목 | 본문)',
					'type'  => 'textarea',
				),
				'prevention_spa_callout_title' => array( 'default' => '💆 누구에게 추천?', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'prevention_spa_callout_body'  => array( 'default' => '✓ 잇몸 출혈·붓기가 자주 있는 분 / ✓ 커피·차·담배 착색이 신경 쓰이는 분 / ✓ 정기 검진을 더 깊이 받고 싶은 분 / ✓ 임플란트·교정·라미네이트 시작 전 구강 환경 정비 / ✓ 양치 후에도 입냄새가 신경 쓰이는 분.', 'label' => '콜아웃 · 본문', 'type' => 'textarea' ),
			),
		),
		'scaling' => array(
			'title'  => '02 · 스케일링',
			'fields' => array(
				'prevention_scaling_eyebrow' => array( 'default' => '02 · SCALING', 'label' => 'eyebrow', 'type' => 'text' ),
				'prevention_scaling_title'   => array( 'default' => '천안·아산 스케일링 — 치주염 예방의 시작', 'label' => '섹션 제목', 'type' => 'text' ),
				'prevention_scaling_lead'    => array( 'default' => '치석은 양치만으로 제거되지 않습니다. 6~12개월 주기 정기 스케일링이 자연치아 평생 보존의 핵심.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'prevention_scaling_cards'   => array(
					'default' => "💰 보험 스케일링 (연 1회) | 만 19세 이상 누구나 <strong>연 1회 보험 적용</strong>. 1월 1일 갱신.\n💰 비급여 스케일링 (추가) | 연 1회를 초과하거나 비급여 정밀 스케일링 원하시는 경우. 6개월 주기 권장.\n⏱️ 시술 시간 | 일반 스케일링 약 <strong>30~40분</strong>. 치석이 많은 경우 2회로 나누어 진행. 임산부도 안전.\n🦷 시술 후 관리 | 시술 후 1~2일 시린 증상 가능 — 자연스러운 회복. 양치 시 부드러운 칫솔 사용 권장.",
					'label' => '카드 (제목 | 본문)',
					'type'  => 'textarea',
				),
			),
		),
		'airflow' => array(
			'title'  => '03 · 에어플로우',
			'fields' => array(
				'prevention_airflow_eyebrow' => array( 'default' => '03 · AIR FLOW', 'label' => 'eyebrow', 'type' => 'text' ),
				'prevention_airflow_title'   => array( 'default' => '천안·아산 에어플로우 — 색소·바이오필름 정밀 제거', 'label' => '섹션 제목', 'type' => 'text' ),
				'prevention_airflow_lead'    => array( 'default' => '고운 미세 분말과 물을 동시 분사해 치아 표면·잇몸 라인의 색소 침착과 바이오필름을 깔끔히 제거.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'prevention_airflow_cards'   => array(
					'default' => "커피·홍차·와인 착색 | 일반 스케일링으로 안 빠지는 색소 침착을 부드럽게 제거. 자연치아 톤 회복.\n담배·니코틴 착색 | 흡연자분들에게 특히 추천 — 누런 착색 제거 효과 큼.\n치아 사이·잇몸 라인 정밀 청소 | 스케일러가 닿기 어려운 부위까지 미세 분말로 정밀 청소. 치주 포켓 내부에도 효과.\n임플란트 주변 관리 | 임플란트 주변 청소에 특화된 글리신 분말 사용 — 임플란트 표면 손상 없이 안전.",
					'label' => '카드 (제목 | 본문)',
					'type'  => 'textarea',
				),
				'prevention_airflow_callout_title' => array( 'default' => '💨 에어플로우 vs 스케일링', 'label' => '콜아웃 · 제목', 'type' => 'text' ),
				'prevention_airflow_callout_body'  => array( 'default' => '<strong>스케일링</strong>은 치석(딱딱한 침착물) 제거, <strong>에어플로우</strong>는 색소·바이오필름(부드러운 침착물) 제거. 둘을 함께 받으시면 효과가 극대화 — 덴탈 SPA 프로그램 추천.', 'label' => '콜아웃 · 본문', 'type' => 'textarea' ),
			),
		),
		'fluoride' => array(
			'title'  => '04 · 불소도포',
			'fields' => array(
				'prevention_fluoride_eyebrow' => array( 'default' => '04 · FLUORIDE', 'label' => 'eyebrow', 'type' => 'text' ),
				'prevention_fluoride_title'   => array( 'default' => '천안·아산 불소도포 — 충치 예방의 핵심', 'label' => '섹션 제목', 'type' => 'text' ),
				'prevention_fluoride_lead'    => array( 'default' => '고농도 불소를 치아 표면에 도포해 법랑질 강화·재광화·충치균 억제.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'prevention_fluoride_cards'   => array(
					'default' => "🧒 어린이 불소도포 | 유치·영구치 모두에 효과적. 3개월~1년 주기 정기 도포 권장. 충치 발생률 30~40% 감소.\n🦷 성인 불소도포 | 잇몸 퇴축·치경부 마모로 시린 증상 있는 분, 충치 재발이 잦은 분에게 권장. 시린 증상 완화.\n👵 노인 불소도포 | 구강 건조·잇몸 퇴축으로 치근 노출된 분에게 치근 충치 예방 효과 큼. 보철 주변 관리에도 도움.\n💰 비용 · 시간 | 약 10분 시술. 도포 후 30분간 음식·음수 자제. 정기 검진과 함께 받으시면 효율적.",
					'label' => '카드 (제목 | 본문)',
					'type'  => 'textarea',
				),
			),
		),
		'sealant' => array(
			'title'  => '05 · 실란트',
			'fields' => array(
				'prevention_sealant_eyebrow' => array( 'default' => '05 · SEALANT', 'label' => 'eyebrow', 'type' => 'text' ),
				'prevention_sealant_title'   => array( 'default' => '천안·아산 실란트 (홈메우기)', 'label' => '섹션 제목', 'type' => 'text' ),
				'prevention_sealant_lead'    => array( 'default' => '어금니 씹는 면의 깊은 홈을 메워 음식물 끼임·충치 시작을 차단하는 보존적 예방 시술.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'prevention_sealant_cards'   => array(
					'default' => "🛡️ 실란트가 필요한 이유 | 어금니 씹는 면에는 미세한 홈(fissure)이 있어 칫솔모가 닿지 않습니다. 그 안에 음식물·치태가 쌓여 충치가 시작 — 실란트로 미리 메우면 차단.\n🧒 만 18세 이하 보험 적용 | 제1·제2 큰어금니에 보험 적용. 부모님들이 자녀에게 꼭 챙겨주시면 좋은 시술.\n💎 성인·작은 어금니 (비급여) | 작은 어금니까지 보호 가능. 충치 재발이 잦은 성인에게 추천.\n⏱️ 시술 시간 · 지속 | 치아당 약 <strong>10분</strong>. 통증 없음. 3~5년 지속 — 마모되면 재시술.",
					'label' => '카드 (제목 | 본문)',
					'type'  => 'textarea',
				),
			),
		),
		'cta' => array(
			'title'  => '예방클리닉 · CTA',
			'fields' => array(
				'prevention_cta_chip'  => array( 'default' => '💆 덴탈 SPA 예약', 'label' => 'CTA 칩', 'type' => 'text' ),
				'prevention_cta_title' => array( 'default' => "치료 전에 예방을 시작하세요\n천안·아산 문치과병원 덴탈 SPA", 'label' => 'CTA 제목', 'type' => 'textarea' ),
				'prevention_cta_lead'  => array( 'default' => '6개월 주기 정기 SPA로 자연치아를 평생 건강하게 — 가장 경제적인 투자입니다.', 'label' => 'CTA 리드', 'type' => 'textarea' ),
			),
		),
	);
}

/**
 * 상시채용 페이지 콘텐츠 (복리후생·WHY·apply flow).
 */
function moondental_recruit_page_content_fields() {
	return array(
		'hero' => array(
			'title'  => '상시채용 · 히어로',
			'fields' => array(
				'recruit_hero_eyebrow' => array( 'default' => 'RECRUIT · 한아의료재단 문치과병원', 'label' => '히어로 · eyebrow', 'type' => 'text' ),
				'recruit_hero_email_btn' => array( 'default' => '📧 이메일로 지원하기', 'label' => '히어로 이메일 버튼 라벨', 'type' => 'text' ),
			),
		),
		'target' => array(
			'title'  => '모집 대상 · 근무 조건',
			'fields' => array(
				'recruit_target_eyebrow' => array( 'default' => '📋 모집 대상', 'label' => '섹션 eyebrow', 'type' => 'text' ),
				'recruit_target_title'   => array( 'default' => '진료실 · 상담실 치과위생사', 'label' => '섹션 제목', 'type' => 'text' ),
				'recruit_cond_badge'    => array( 'default' => '근무 조건', 'label' => '카드 배지', 'type' => 'text' ),
				'recruit_cond_title'    => array( 'default' => '💼 진료실 · 상담실 공통 근무 조건', 'label' => '카드 제목', 'type' => 'text' ),
				'recruit_cond_lead'     => array( 'default' => '직원 시프트 · 병원 진료시간과 다릅니다.', 'label' => '카드 리드', 'type' => 'text' ),
				'recruit_cond_hours_title' => array( 'default' => '🕐 근무 시간', 'label' => '왼쪽 블록 · 제목', 'type' => 'text' ),
				'recruit_cond_hours_list'  => array(
					'default' => "주 5일 근무 (평일 + 토요일 격주)\n평일 9:00~19:30 (점심시간 1시간 포함)\n월·화·수·금 야간진료는 오후 시프트 로테이션 (~20:30)\n목요일 9:00~18:30 · 토요일 9:00~14:00\n일요일·공휴일 휴무",
					'label' => '왼쪽 블록 · 리스트 (한 줄에 1개)',
					'type'  => 'textarea',
				),
				'recruit_cond_leg_title' => array( 'default' => '📅 연차 · 법정 보장', 'label' => '오른쪽 블록 · 제목', 'type' => 'text' ),
				'recruit_cond_leg_list'  => array(
					'default' => "4대 보험 (건강·국민연금·고용·산재)\n퇴직연금 운영\n연차 (1년차 · 2년차 이상 확대 적용)\n출산·육아휴가 (법정 + 확장 운영)",
					'label' => '오른쪽 블록 · 리스트 (한 줄에 1개)',
					'type'  => 'textarea',
				),
			),
		),
		'benefits' => array(
			'title'  => '복리후생 · 6개 카테고리',
			'fields' => array(
				'recruit_benefits_badge' => array( 'default' => '복리후생', 'label' => '카드 배지', 'type' => 'text' ),
				'recruit_benefits_title' => array( 'default' => '🎁 함께 성장하고 오래 일할 수 있도록', 'label' => '카드 제목', 'type' => 'text' ),
				'recruit_benefits_lead'  => array( 'default' => '문치과병원은 20여년 함께한 선생님들이 계신 이유가 있습니다. 아래는 병원이 운영 중인 복리후생 프로그램입니다.', 'label' => '카드 리드', 'type' => 'textarea' ),
				'recruit_benefits_items' => array(
					'default' => "💰 급여 · 인센티브 | 급여테이블 기준 월 급여 · 다양한 인센티브 (매출·소개·근태 등) · 명절·여름휴가 상여금 · 진료 관련 자격증 보유자 자격수당\n📚 교육 · 성장 지원 | 보수교육·세미나 등록비 지원 · 법정교육 + 사내 직무교육 프로그램 · 학회 참여 지원\n👨‍👩‍👧 가족 · 생활 지원 | 자녀 학자금 지원 (유아~대학) · 본인·가족 의료비 지원 · 본인·가족 치과 진료 할인 · 결혼축하금 · 생일 수당 · 명절 선물\n🏠 근무 환경 | 기숙사 지원 (원거리 신규 직원) · 중식·석식 제공 · 유니폼 지급\n🌳 문화 · 복지 | 직원 리조트 회원가 지원 (리솜·한화) · 동아리 활동 지원 · 봄·가을 체육대회 (상반기 1회 · 하반기 1회) · 주말농장 운영 (희망자) · 워크샵·회식비 지원\n🕰️ 장기근속 · 경조사 | 상조회 운영 (경사·애사 지원) · 근속 연수별 결혼축하금 확대 · 장기근속 포상",
					'label' => '카테고리 (한 줄에 1개, 형식: 아이콘+제목 | 항목1 · 항목2 · 항목3 · ...) — 항목은 " · "로 구분',
					'type'  => 'textarea',
				),
				'recruit_benefits_hint'  => array( 'default' => '※ 구체적인 금액·요건은 입사 후 안내드립니다. 세부 내용은 면접 시 궁금하신 부분 언제든 질문해주세요.', 'label' => '카드 하단 힌트', 'type' => 'textarea' ),
			),
		),
		'why' => array(
			'title'  => 'WHY MOON DENTAL · 강점 카드',
			'fields' => array(
				'recruit_why_eyebrow' => array( 'default' => '✨ WHY MOON DENTAL', 'label' => '섹션 eyebrow', 'type' => 'text' ),
				'recruit_why_title'   => array( 'default' => '문치과병원에서 일하면 좋은 점', 'label' => '섹션 제목', 'type' => 'text' ),
				'recruit_why_cards'   => array(
					'default' => "🕰️ 20년 넘게 함께한 동료들 | 1995년 개원 후 20년 넘게 근무하고 계신 선생님들이 여러 분 계십니다. 신뢰가 쌓인 동료와 오래 함께 일할 수 있는 환경입니다.\n🦷 30여년 임상 노하우 | 다양한 케이스를 직접 경험하며 임상 실력을 키울 수 있습니다.\n🏥 통합 진료센터 (4개 층) | 9F 보철·보존·예방 · 10F 임플란트·외과·턱관절 · 11F 교정·소아·치주·디지털 · 13F 기공 — 모든 진료를 한 건물에서 경험.\n🔬 디지털 진료 시스템 | CBCT·디지털 가이드·구강 스캐너 등 최신 장비. 디지털 치과 실무 경험 축적.\n👨‍⚕️ 분야별 전문 의료진 협진 | 보철·보존·예방·임플란트·스마일디자인·구강외과·구강내과·턱관절·교정·소아·치주 전문 의료진과 함께 — 다각도로 배울 수 있는 환경.\n📚 교육·세미나 지원 | 학회·세미나 참석 지원, 사내 임상 교육 — 성장하고 싶은 분께 적극 추천.",
					'label' => '강점 카드 (한 줄에 1개, 형식: 아이콘+제목 | 본문)',
					'type'  => 'textarea',
				),
			),
		),
		'apply' => array(
			'title'  => '지원 방법 · 이메일 카드',
			'fields' => array(
				'recruit_apply_eyebrow' => array( 'default' => '📩 지원 방법', 'label' => '섹션 eyebrow', 'type' => 'text' ),
				'recruit_apply_title'   => array( 'default' => '이메일 한 통이면 충분합니다', 'label' => '섹션 제목', 'type' => 'text' ),
				'recruit_apply_card_title' => array( 'default' => '이렇게 보내주세요', 'label' => '카드 제목', 'type' => 'text' ),
				'recruit_apply_bullets' => array(
					'default' => "<strong>이력서가 완벽하지 않아도 괜찮습니다.</strong> 형식보다 함께 오래 갈 마음이 더 중요합니다.\n<strong>자기소개서가 길지 않아도 괜찮습니다.</strong> \"문치과병원에서 일하는 것에 관심 있습니다\"라는 한 줄만으로도 충분합니다.\n가지고 계신 이력서와 간단한 소개를 아래 이메일로 보내주시면 저희가 확인 후 연락드립니다.",
					'label' => '체크 항목 (한 줄에 1개, HTML 허용)',
					'type'  => 'textarea',
				),
				'recruit_apply_btn_label' => array( 'default' => '이력서 보내기', 'label' => '이메일 버튼 라벨', 'type' => 'text' ),
				'recruit_apply_flow_title' => array( 'default' => '지원 후 진행 과정', 'label' => '진행 과정 · 소제목', 'type' => 'text' ),
				'recruit_apply_flow_steps' => array(
					'default' => "서류 검토 | 3~5일 이내\n면접 | 실장 · 대표원장\n채용 확정 · 입사 | 3개월 수습 후 정규직",
					'label' => '진행 과정 단계 (한 줄에 1개, 형식: 제목 | 설명)',
					'type'  => 'textarea',
				),
			),
		),
		'cta' => array(
			'title'  => '상시채용 · CTA',
			'fields' => array(
				'recruit_page_cta_chip'  => array( 'default' => '📧 지원 접수', 'label' => 'CTA 칩', 'type' => 'text' ),
				'recruit_page_cta_title' => array( 'default' => "지금 이메일로\n편하게 보내주세요", 'label' => 'CTA 제목 (줄바꿈 유지)', 'type' => 'textarea' ),
				'recruit_page_cta_lead'  => array( 'default' => '길지 않아도, 완벽하지 않아도 괜찮습니다. 함께 오래 갈 분을 기다리고 있습니다.', 'label' => 'CTA 리드', 'type' => 'textarea' ),
			),
		),
	);
}

/**
 * 지역별 오시는 길 페이지 콘텐츠 — 28개 URL 공통 템플릿.
 * {region}·{region_long}·{province}·{duration}·{distance}·{duration_label}·{highway}·{ktx}·{bus}·{note} 토큰이
 * 페이지 로딩 시 각 지역 값으로 자동 치환됩니다.
 */
function moondental_region_content_fields() {
	return array(
		'hero' => array(
			'title'  => '지역 페이지 · 히어로',
			'fields' => array(
				'region_hero_eyebrow'      => array( 'default' => '📍 {province} · {region_long}에서 오시는 길', 'label' => 'eyebrow (토큰: {province}, {region_long})', 'type' => 'text' ),
				'region_hero_title_a'      => array( 'default' => '{region}에서 찾는', 'label' => '제목 첫 줄 (토큰: {region})', 'type' => 'text' ),
				'region_hero_title_b'      => array( 'default' => '임플란트·교정 잘하는 천안·아산 치과', 'label' => '제목 강조 (em)', 'type' => 'text' ),
				'region_hero_lead_walking' => array(
					'default' => "{region}에서 천안 만남로 <strong>한아의료재단 문치과병원</strong>까지 <strong>{duration_label}</strong> 거리.\n1995년부터 30여년 한자리 진료 — 분야별 전문 의료진 협진으로 통합 진료해드립니다.",
					'label'   => '리드 (도보 지역용)',
					'type'    => 'textarea',
				),
				'region_hero_lead_drive'   => array(
					'default' => "{region}에서 천안 만남로 <strong>한아의료재단 문치과병원</strong>까지 자동차로 약 <strong>{duration}분</strong> ({distance}km).\n1995년부터 30여년 한자리 진료 — 분야별 전문 의료진 협진으로 통합 진료해드립니다.",
					'label'   => '리드 (자동차 지역용)',
					'type'    => 'textarea',
				),
				'region_hero_badge_walk'   => array( 'default' => '🚶 {duration_label}', 'label' => '배지 (도보) — 예: 🚶 도보 15분', 'type' => 'text' ),
				'region_hero_badge_drive'  => array( 'default' => '🚗 자동차 {duration}분', 'label' => '배지 (자동차)', 'type' => 'text' ),
				'region_hero_badge_bus'    => array( 'default' => '🚌 시외버스 가능', 'label' => '배지 (버스, 자동차 지역용)', 'type' => 'text' ),
				'region_hero_badge_walk_bus' => array( 'default' => '🚌 시내버스·터미널 근접', 'label' => '배지 (버스, 도보 지역용)', 'type' => 'text' ),
				'region_hero_badge_night'  => array( 'default' => '🌙 월·화·수·금 야간진료 20:30까지', 'label' => '배지 (야간진료)', 'type' => 'text' ),
			),
		),
		'traffic' => array(
			'title'  => '교통 안내 (자가용·KTX·버스)',
			'fields' => array(
				'region_traffic_eyebrow' => array( 'default' => '🗺️ 교통 안내', 'label' => 'eyebrow', 'type' => 'text' ),
				'region_traffic_title'   => array( 'default' => '{region}에서 천안 만남로 문치과병원까지', 'label' => '섹션 제목 (토큰: {region})', 'type' => 'text' ),
				'region_traffic_lead'    => array( 'default' => '자동차·시외버스·KTX 중 가장 편한 방법으로 오실 수 있습니다.', 'label' => '섹션 리드', 'type' => 'textarea' ),
				'region_traffic_car_title'  => array( 'default' => '자가용으로 오시는 길', 'label' => '자가용 카드 · 제목', 'type' => 'text' ),
				'region_traffic_car_body'   => array( 'default' => '<strong>{region}</strong>에서 천안 만남로 문치과병원까지 자동차로 약 <strong>{duration}분</strong>, 거리 약 {distance}km.', 'label' => '자가용 카드 · 본문', 'type' => 'textarea' ),
				'region_traffic_car_route'  => array( 'default' => '<strong>주요 경로</strong>: {highway} 이용', 'label' => '자가용 카드 · 경로 라인', 'type' => 'text' ),
				'region_traffic_car_park'   => array( 'default' => '<strong>주차</strong>: 본원 지하 기계식 주차장 무료 / SUV·대형차는 신부 제5공영주차장(동남구 먹거리1길 10) 무료 등록', 'label' => '자가용 카드 · 주차 라인', 'type' => 'textarea' ),
				'region_traffic_ktx_title'  => array( 'default' => 'KTX·기차로 오시는 길', 'label' => 'KTX 카드 · 제목', 'type' => 'text' ),
				'region_traffic_ktx_detail' => array( 'default' => '천안역 또는 천안아산역 도착 후 시내버스 또는 택시로 신부동 문타워까지 약 10~15분.', 'label' => 'KTX 카드 · 부가 안내', 'type' => 'textarea' ),
				'region_traffic_bus_title'  => array( 'default' => '시외버스로 오시는 길', 'label' => '버스 카드 · 제목', 'type' => 'text' ),
				'region_traffic_bus_detail' => array( 'default' => '천안종합·고속버스터미널에서 문치과병원까지 도보 약 5분.', 'label' => '버스 카드 · 부가 안내', 'type' => 'textarea' ),
				'region_callout_title'  => array( 'default' => '📍 {region} 환자분께', 'label' => '지역 콜아웃 · 제목 (토큰: {region})', 'type' => 'text' ),
				'region_callout_body'   => array( 'default' => '{note}. 천안 만남로 문타워 9·10·11·13층, 4개 층 통합 진료센터에서 분야별 전문 의료진의 협진을 받으실 수 있습니다.', 'label' => '지역 콜아웃 · 본문 (토큰: {note})', 'type' => 'textarea' ),
			),
		),
		'reasons' => array(
			'title'  => '선택 이유 6가지',
			'fields' => array(
				'region_reasons_eyebrow' => array( 'default' => '✨ 우리 병원을 선택하는 이유', 'label' => 'eyebrow', 'type' => 'text' ),
				'region_reasons_title'   => array( 'default' => '{region}에서 문치과병원을 선택하시는 이유', 'label' => '섹션 제목 (토큰: {region})', 'type' => 'text' ),
				'region_reasons_cards'   => array(
					'default' => "🦷 30여년 임상 경험 | {region}에서 천안까지 오시는 데는 이유가 있습니다. 1995년 개원부터 30여년 한자리 진료로 누적된 임상 경험.\n👨‍⚕️ 분야별 전문 의료진 협진 | 보철·보존·예방·임플란트·스마일디자인·구강외과·구강내과·턱관절·교정·소아·치주 전 분야 의료진이 한 케이스를 함께 보는 협진 시스템. {region}에서 따로따로 다닐 필요 없습니다.\n🔬 CBCT 디지털 진단 | 3D CBCT·디지털 가이드 수술·구강 스캐너 — 정확한 진단과 안전한 수술. {region}에서 정밀 진단이 필요한 케이스에 추천.\n⚙️ 자체 보철 제작 | 13층 한아 임플란트 보철연구소 원내 직접 제작. 빠른 수정·정확한 의사소통·품질 일관성 — {region}에서 오신 분들도 한 번에 끝.\n❤️ 전신질환 안심 진료 | 혈압·당검사·심전도·산소포화도 상시 측정. 고혈압·당뇨·심장질환자도 {region}에서 오셔서 안심하고 진료받으실 수 있습니다.\n🌙 평일 야간진료 | 월·화·수·금 9:00~20:30 점심시간 없이 진료 · 목 9:00~18:30 · 토 9:00~14:00. {region}에서 퇴근 후 출발하셔도 충분한 진료 시간.",
					'label' => '이유 카드 (한 줄에 1개, 형식: 아이콘+제목 | 본문) · 토큰: {region}',
					'type'  => 'textarea',
				),
			),
		),
		'popular' => array(
			'title'  => '인기 진료 5개',
			'fields' => array(
				'region_popular_eyebrow' => array( 'default' => '🦷 인기 진료', 'label' => 'eyebrow', 'type' => 'text' ),
				'region_popular_title'   => array( 'default' => '{region}에서 오시는 환자분들의 인기 진료', 'label' => '섹션 제목 (토큰: {region})', 'type' => 'text' ),
				'region_popular_lead'    => array( 'default' => '{region}에서 천안까지 오시는 분들이 자주 받으시는 진료입니다.', 'label' => '섹션 리드 (토큰: {region})', 'type' => 'textarea' ),
				'region_popular_items'   => array(
					'default' => "임플란트-센터 | icon:implant | {region} 임플란트 | {region}에서 정밀 임플란트 — CBCT 디지털 가이드·자체 보철 제작·30여년 임상.\n투명교정-센터 | icon:ortho | {region} 투명교정 | {region}에서 슈어스마일 SureSmile 투명교정 — Dentsply Sirona AI 시뮬레이션.\n심미치료 | icon:aesthetic | {region} 라미네이트 | {region}에서 자연스러운 미소 — 최소 삭제 라미네이트·미백·심미 보철.\n자연치아-살리기 | icon:preserve | {region} 자연치아 살리기 | {region}에서 신경치료·재근관치료 — 발치보다 보존 우선.\n사랑니-발치 | icon:wisdom | {region} 사랑니 발치 | {region}에서 매복 사랑니까지 — CBCT 안전 진단 + 진정요법.",
					'label' => '인기 진료 (한 줄에 1개, 형식: 페이지 슬러그 | 아이콘 | 제목 | 설명) · 아이콘은 이모지 또는 icon:implant 형식 · 토큰: {region}',
					'type'  => 'textarea',
				),
			),
		),
		'cta' => array(
			'title'  => '지역 페이지 · CTA',
			'fields' => array(
				'region_cta_chip'      => array( 'default' => '📅 365일 24시간 온라인 예약 가능', 'label' => 'CTA 칩', 'type' => 'text' ),
				'region_cta_title'     => array( 'default' => "{region}에서 천안·아산 문치과병원까지\n지금 바로 상담 받아보세요", 'label' => 'CTA 제목 (토큰: {region})', 'type' => 'textarea' ),
				'region_cta_lead'      => array( 'default' => '네이버 예약 24시간 자동 / 전화·카카오톡 상담', 'label' => 'CTA 리드', 'type' => 'textarea' ),
				'region_cta_hint'      => array( 'default' => '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00 · 일/공휴일 휴진', 'label' => 'CTA 힌트 (진료시간)', 'type' => 'text' ),
				'region_cta_hint_addr' => array( 'default' => '📍 천안 만남로 52 문타워 9·10·11·13층', 'label' => 'CTA 힌트 (주소)', 'type' => 'text' ),
			),
		),
		'faq' => array(
			'title'  => '지역 FAQ 5개',
			'fields' => array(
				'region_faq_eyebrow' => array( 'default' => '❓ 자주 묻는 질문', 'label' => 'eyebrow', 'type' => 'text' ),
				'region_faq_title'   => array( 'default' => '{region} 환자분들이 자주 물어보시는 질문', 'label' => '섹션 제목 (토큰: {region})', 'type' => 'text' ),
				'region_faq_items'   => array(
					'default' => "{region}에서 천안·아산 문치과병원까지 얼마나 걸리나요? | 자동차로 약 <strong>{duration}분</strong>, 거리 약 {distance}km입니다. 주요 경로는 {highway} 이용. 시외버스·KTX로도 천안종합터미널 또는 천안역 도착 후 도보 5분 거리입니다.\n{region}에서 갈 만한 임플란트 잘하는 치과인가요? | 네, {region}에서 임플란트 진료받으러 오시는 환자분이 많습니다. 1995년 개원 30여년 임상, CBCT 디지털 가이드 수술, 13층 자체 한아 임플란트 보철연구소에서 보철 직접 제작 — 다른 지역에서 오셔도 한 번 방문으로 진단부터 보철까지 진행할 수 있도록 시스템이 갖춰져 있습니다.\n{region}에서 주차가 가능한가요? | 네, 본원 지하 기계식 주차장을 <strong>무료</strong>로 이용하실 수 있습니다. SUV·대형차는 인근 신부 제5공영주차장(동남구 먹거리1길 10)에 주차하시고 데스크에 접수하시면 무료 등록을 도와드립니다.\n{region}에서 야간이나 주말에도 진료 가능한가요? | 네, 평일(월·화·수·금)은 <strong>9:00~20:30</strong>까지 점심시간 없이 진료합니다. {region}에서 퇴근 후 출발하셔도 충분한 시간입니다. 토요일은 9:00~14:00, 일요일·공휴일은 휴진입니다.\n{region}에서 첫 진료 시 무엇이 필요한가요? | 신분증(또는 건강보험증)을 지참해주세요. 복용 중인 약이 있다면 약 정보, 타원 X-ray 파일(USB·이메일)이 있으면 진단 시간이 단축됩니다. 사전 예약은 네이버 예약, 전화, 카카오톡 채널로 가능합니다.",
					'label' => 'FAQ (한 줄에 1개, 형식: 질문 | 답변) · 토큰 다 사용 가능',
					'type'  => 'textarea',
				),
			),
		),
		'other' => array(
			'title'  => '다른 지역 안내',
			'fields' => array(
				'region_other_eyebrow' => array( 'default' => '🌐 다른 지역에서 오시는 길', 'label' => 'eyebrow', 'type' => 'text' ),
				'region_other_title'   => array( 'default' => '다른 지역에서 천안·아산 문치과병원까지', 'label' => '섹션 제목', 'type' => 'text' ),
			),
		),
	);
}

/**
 * 예약 페이지 · 소식 페이지 · 오시는 길 섹션 나머지 텍스트.
 */
function moondental_misc_pages_content_fields() {
	return array(
		'reservation_extra' => array(
			'title'  => '예약 페이지 · 진료시간 힌트·FAQ',
			'fields' => array(
				'res_hint'         => array( 'default' => '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00 · 일/공휴일 휴진', 'label' => 'CTA 하단 진료시간 힌트', 'type' => 'text' ),
				'res_faq_eyebrow'  => array( 'default' => 'FAQ', 'label' => 'FAQ 섹션 · eyebrow', 'type' => 'text' ),
				'res_faq_title'    => array( 'default' => '예약 관련 자주 묻는 질문', 'label' => 'FAQ 섹션 · 제목', 'type' => 'text' ),
				'res_faq_items'    => array(
					'default' => "당일 예약도 가능한가요? | 네, 당일 예약도 가능합니다. 다만 예약 상황에 따라 대기 시간이 발생할 수 있으니 가급적 전화로 먼저 확인 후 방문해주시기 바랍니다.\n예약 변경이나 취소는 어떻게 하나요? | 네이버 예약은 예약 페이지에서 직접 변경·취소가 가능하며, 그 외에는 전화 또는 카카오톡 채널로 문의해주세요. 예약일 전날까지 변경·취소가 가능합니다.\n초진 시 준비물이 있나요? | 신분증(또는 건강보험증)을 지참해주세요. 복용 중인 약이 있다면 약 정보를 함께 알려주시면 진료에 도움이 됩니다. 타원 X-ray 파일(USB·이메일)이 있으면 진단 시간이 단축됩니다.\n전신질환(고혈압·당뇨·심장질환)이 있어도 진료 가능한가요? | 네, 안심하셔도 됩니다. 문치과병원은 혈압기·당검사·심전도·산소포화도 측정 장비를 상시 보유하고 있으며, 복용 중인 약(혈전용해제·골다공증 주사 등)을 사전에 체크해 안전하게 진료합니다.\n비용·견적은 미리 알 수 있나요? | 임플란트·교정·심미치료 등 비급여 진료는 환자분의 구강 상태(CT·X-ray)를 보고 정확한 견적을 산정합니다. 초진 상담 시 옵션별 비용·기간을 모두 안내드리며, 시작 전에 충분히 검토하실 수 있도록 합니다.\n자가진단 결과만 보고 와도 되나요? | 자가진단은 참고용으로 도움이 됩니다. 하지만 정확한 진단·치료 계획은 의료진의 직접 진료(시진·X-ray·구강검사)가 필요합니다. 자가진단 결과를 보여주시면 상담이 더 빨라집니다.",
					'label' => 'FAQ 항목 (한 줄에 1개, 형식: 질문 | 답변)',
					'type'  => 'textarea',
				),
			),
		),
		'news' => array(
			'title'  => '소식 페이지 · 히어로·섹션',
			'fields' => array(
				'news_hero_title'    => array( 'default' => '병원 소식', 'label' => '히어로 · 제목', 'type' => 'text' ),
				'news_hero_lead'     => array( 'default' => '천안 만남로 문치과병원의 공지사항과 치과 정보를 모았습니다.', 'label' => '히어로 · 리드', 'type' => 'textarea' ),
				'news_notice_eyebrow' => array( 'default' => '📢 NOTICE', 'label' => '소식 섹션 · eyebrow', 'type' => 'text' ),
				'news_notice_title'   => array( 'default' => '문치과병원 소식', 'label' => '소식 섹션 · 제목', 'type' => 'text' ),
				'news_notice_lead'    => array( 'default' => '진료시간 변경·휴진 안내·이벤트·운영 소식을 가장 먼저 안내드립니다.', 'label' => '소식 섹션 · 리드', 'type' => 'textarea' ),
				'news_notice_empty'   => array( 'default' => '아직 등록된 소식이 없습니다.', 'label' => '소식 · 빈 상태 메시지', 'type' => 'text' ),
				'news_notice_empty_sub' => array( 'default' => '곧 새로운 소식으로 찾아뵙겠습니다.', 'label' => '소식 · 빈 상태 부제', 'type' => 'text' ),
				'news_stories_eyebrow' => array( 'default' => '🦷 DENTAL STORIES · 치아이야기', 'label' => '치아이야기 섹션 · eyebrow', 'type' => 'text' ),
				'news_stories_title'   => array( 'default' => '문치과병원 치아이야기', 'label' => '치아이야기 섹션 · 제목', 'type' => 'text' ),
				'news_stories_lead'    => array( 'default' => "임플란트·교정·자연치아 살리기·라미네이트·예방 등\n환자분께 도움이 되는 구강 건강 정보를 모았습니다.", 'label' => '치아이야기 섹션 · 리드 (줄바꿈 유지)', 'type' => 'textarea' ),
				'news_stories_empty'   => array( 'default' => '아직 등록된 치아이야기가 없습니다.', 'label' => '치아이야기 · 빈 상태 메시지', 'type' => 'text' ),
				'news_stories_empty_sub' => array( 'default' => '곧 환자분께 도움이 되는 정보로 찾아뵙겠습니다.', 'label' => '치아이야기 · 빈 상태 부제', 'type' => 'text' ),
			),
		),
		'section_labels' => array(
			'title'  => '섹션 라벨 · 오시는 길 요일',
			'fields' => array(
				'loc_day_weekday' => array( 'default' => '평일 (월·화·수·금)', 'label' => '오시는 길 · 요일 라벨 (평일)', 'type' => 'text' ),
				'loc_day_thu'     => array( 'default' => '목요일', 'label' => '오시는 길 · 요일 라벨 (목)', 'type' => 'text' ),
				'loc_day_sat'     => array( 'default' => '토요일', 'label' => '오시는 길 · 요일 라벨 (토)', 'type' => 'text' ),
				'loc_day_sun'     => array( 'default' => '일요일 · 공휴일', 'label' => '오시는 길 · 요일 라벨 (일/공휴일)', 'type' => 'text' ),
				'loc_day_closed'  => array( 'default' => '휴진', 'label' => '오시는 길 · 휴진 표시', 'type' => 'text' ),
				'loc_map_fallback' => array( 'default' => '🗺️ 지도 이미지 열기 →', 'label' => '오시는 길 · 지도 대체 라벨', 'type' => 'text' ),
			),
		),
		'testimonials_extra' => array(
			'title'  => '홈 후기 섹션 · 추가 문구',
			'fields' => array(
				'testimonials_more_label' => array( 'default' => '네이버 플레이스에서 더 많은 후기 보기 →', 'label' => '더 보기 링크 라벨', 'type' => 'text' ),
				'testimonials_disclaimer' => array( 'default' => '※ 후기는 환자분 동의 하에 게재되었으며 케이스별로 결과와 소요기간이 다를 수 있습니다.', 'label' => '하단 면책 문구', 'type' => 'textarea' ),
			),
		),
	);
}

/**
 * 셀프진단봇 · 30개 질문 + 8개 추천 진료과 + UI 문구.
 */
function moondental_bot_content_fields() {
	return array(
		'ui' => array(
			'title'  => '셀프진단봇 · UI 문구',
			'fields' => array(
				'bot_count_template'    => array( 'default' => '{count}개의 Yes/No 질문 · 약 2-3분 소요 · 모든 진료영역 망라', 'label' => '질문 개수 안내 (토큰 {count})', 'type' => 'text' ),
				'bot_intro_title'       => array( 'default' => '간단한 자가진단 시작하기', 'label' => '시작 화면 · 제목', 'type' => 'text' ),
				'bot_intro_note'        => array( 'default' => '개인정보는 수집·저장되지 않습니다. 답변은 브라우저 안에서만 처리됩니다.', 'label' => '시작 화면 · 하단 안내', 'type' => 'textarea' ),
				'bot_answer_yes'        => array( 'default' => '✓ 예', 'label' => '답변 버튼 · 예', 'type' => 'text' ),
				'bot_answer_no'         => array( 'default' => '✗ 아니요', 'label' => '답변 버튼 · 아니요', 'type' => 'text' ),
				'bot_back_label'        => array( 'default' => '← 이전 질문', 'label' => '이전 질문 버튼', 'type' => 'text' ),
				'bot_result_title'      => array( 'default' => '진단 결과 — 추천 진료과', 'label' => '결과 화면 · 제목', 'type' => 'text' ),
				'bot_result_lead'       => array( 'default' => '증상에 가장 부합하는 진료과를 추천해드립니다.', 'label' => '결과 화면 · 리드', 'type' => 'textarea' ),
				'bot_result_book_label' => array( 'default' => '📅 지금 예약 상담하기', 'label' => '결과 · 예약 버튼', 'type' => 'text' ),
				'bot_result_restart'    => array( 'default' => '↺ 다시 진단', 'label' => '결과 · 다시 진단 버튼', 'type' => 'text' ),
				'bot_disclaimer'        => array( 'default' => '⚠️ 본 결과는 자가진단 참고용입니다. 정확한 진단·치료를 위해서는 의료진의 직접 진료가 필요합니다.', 'label' => '결과 · 면책 문구', 'type' => 'textarea' ),
			),
		),
		'aside' => array(
			'title'  => '셀프진단봇 · 우측 예약 aside',
			'fields' => array(
				'bot_aside_title'      => array( 'default' => '진단 안 받고 바로 상담', 'label' => 'aside 제목', 'type' => 'text' ),
				'bot_aside_lead'       => array( 'default' => '증상이 명확하다면 곧장 예약·상담하세요.', 'label' => 'aside 리드', 'type' => 'textarea' ),
				'bot_aside_btn_naver'  => array( 'default' => '네이버 예약', 'label' => '네이버 예약 버튼 라벨', 'type' => 'text' ),
				'bot_aside_btn_kakao'  => array( 'default' => '카카오톡 상담', 'label' => '카카오톡 버튼 라벨', 'type' => 'text' ),
				'bot_aside_btn_call'   => array( 'default' => '전화 상담', 'label' => '전화 버튼 라벨 (짧게 유지 · 가로 3버튼 배치)', 'type' => 'text' ),
				'bot_aside_hint'       => array( 'default' => '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00', 'label' => 'aside 하단 진료시간 힌트', 'type' => 'text' ),
			),
		),
		'questions' => array(
			'title'  => '셀프진단봇 · 30개 질문',
			'fields' => array(
				'bot_questions' => array(
					'default' =>
						"심한 통증 | 가만히 있어도 욱신거리거나 잠을 못 잘 정도로 아픈 치아가 있나요? | 자연치아-살리기:3\n" .
						"시림 | 차거나 뜨거운 음식·찬바람에 시린 치아가 있나요? | 자연치아-살리기:2\n" .
						"단음식 통증 | 단 음식(초콜릿·사탕 등)을 먹을 때 특정 치아가 시큰거리나요? | 자연치아-살리기:2\n" .
						"씹기 통증 | 음식을 씹을 때 특정 부위가 아프거나 불편한가요? | 자연치아-살리기:2, 사랑니-발치:1\n" .
						"잇몸 출혈 | 양치할 때 잇몸에서 자주 피가 나거나, 잇몸이 자주 붓나요? | 자연치아-살리기:3\n" .
						"잇몸 퇴축 | 잇몸이 내려앉아 치아 뿌리가 보이거나 치아가 길어 보이나요? | 자연치아-살리기:3\n" .
						"입냄새 | 입냄새가 신경 쓰이거나, 입에서 쓴맛·고름맛이 나나요? | 자연치아-살리기:2, 예방클리닉:2\n" .
						"스케일링 | 최근 6개월~1년 이상 스케일링을 받지 않으셨나요? | 예방클리닉:3, 자연치아-살리기:1\n" .
						"음식 끼임 | 치아 사이에 음식물이 자주 끼이거나 빠지지 않는 곳이 있나요? | 자연치아-살리기:2\n" .
						"치아 손실 | 빠진 치아가 있거나, 발치 후 비어있는 자리가 있나요? | 임플란트-센터:3\n" .
						"치아 흔들림 | 흔들리는 치아가 있거나 치아가 들뜨는 느낌이 있나요? | 자연치아-살리기:2, 임플란트-센터:1\n" .
						"보철물 문제 | 기존 보철물(크라운·브릿지·틀니·임플란트)이 빠지거나 흔들리거나 불편한가요? | 임플란트-센터:2\n" .
						"치열 | 치아가 비뚤어져 있거나, 치아 사이가 벌어져 있나요? | 투명교정-센터:3\n" .
						"교합·돌출 | 위아래 치아가 잘 맞물리지 않거나, 앞니가 튀어나와 보이나요? | 투명교정-센터:3\n" .
						"잘 안 다물리는 입 | 평소 입이 잘 안 다물리거나 입술이 자연스럽게 닫히지 않나요? | 투명교정-센터:2\n" .
						"턱 소리 | 입을 벌리거나 닫을 때 턱에서 소리(딸깍·뚝)가 나거나, 턱 주변에 통증이 있나요? | 턱관절-클리닉:3\n" .
						"개구 장애 | 아침에 일어났을 때 턱이 뻐근하거나, 입이 잘 벌어지지 않는 경우가 있나요? | 턱관절-클리닉:3\n" .
						"이갈이 | 잘 때 이를 갈거나 꽉 무는 습관이 있다는 말을 들으신 적이 있나요? | 턱관절-클리닉:3, 심미치료:1\n" .
						"사랑니 | 어금니 가장 안쪽(사랑니 부위)에 통증·부종이 있거나, 사랑니 발치를 권유받은 적 있나요? | 사랑니-발치:3\n" .
						"치아 변색 | 치아 색이 어둡거나 변색이 있어 미백을 고려하시나요? | 심미치료:2\n" .
						"잇몸 색 | 잇몸이 검거나 어두워 잇몸 미백을 고려하시나요? | 심미치료:2\n" .
						"앞니 모양 | 앞니의 모양·길이·잇몸 라인이 마음에 들지 않아 자연스럽게 다듬고 싶으신가요? | 심미치료:3\n" .
						"거미스마일 | 웃을 때 잇몸이 많이 보여서(거미스마일) 신경 쓰이나요? | 심미치료:3\n" .
						"심미 보철 | 기존 크라운·앞니 보철이 자연스럽지 않거나 색이 차이나 보이나요? | 심미치료:2, 임플란트-센터:1\n" .
						"어린이 | 어린이·청소년 환자이신가요? (만 18세 이하) | 예방클리닉:3\n" .
						"예방 검진 | 특별한 증상은 없지만 정기 검진·스케일링·불소도포 등 예방 진료를 원하시나요? | 예방클리닉:3, 일반-검진:1\n" .
						"전신질환 | 고혈압·당뇨·심장질환·골다공증 등 전신질환이 있어 진료가 망설여지나요? | 예방클리닉:2, 임플란트-센터:1\n" .
						"임신·수유 | 임신·수유 중이시거나 임신을 준비 중이신가요? (안전 진료 필요) | 예방클리닉:2\n" .
						"임플란트 검토 | 임플란트·뼈이식·발치 후 보철에 대해 견적·계획을 알아보고 싶으신가요? | 임플란트-센터:3\n" .
						"응급 통증 | 심한 부종·고름·삼키기 어려운 통증 등 응급 상황으로 느껴지나요? | 자연치아-살리기:3, 사랑니-발치:2",
					'label' => '30개 질문 (한 줄에 1개, 형식: 카테고리 | 질문 | 진료과키:가중치, 진료과키:가중치)',
					'type'  => 'textarea',
				),
			),
		),
		'depts' => array(
			'title'  => '셀프진단봇 · 8개 추천 진료과',
			'fields' => array(
				'bot_depts' => array(
					'default' =>
						"자연치아-살리기 | 자연치아 살리기 | 보존 · 신경치료 · 잇몸치료 | /자연치아-살리기/ | 신경치료·재근관치료·치주치료로 자연치아를 최대한 살립니다. 통증·시림·잇몸 트러블은 조기 진료가 중요합니다.\n" .
						"임플란트-센터 | 임플란트 센터 | 발치 후 회복 · 보철 | /임플란트-센터/ | 빠진 치아·흔들리는 치아를 인공치근으로 회복합니다. 디지털 가이드 · 골 이식 케이스 포함.\n" .
						"투명교정-센터 | 투명교정 센터 | 슈어스마일 · 일반교정 | /투명교정-센터/ | 비뚤어진 치아·돌출 입·맞물림 문제를 자연스럽게 개선합니다. 시뮬레이션으로 결과 미리 확인.\n" .
						"턱관절-클리닉 | 턱관절 클리닉 | 소리 · 통증 · 개구장애 | /턱관절-클리닉/ | 턱관절 소리·통증·턱 주변 두통 진단 및 치료. 보존적 치료 우선.\n" .
						"사랑니-발치 | 사랑니 발치 | 안전한 CBCT 진단 | /사랑니-발치/ | CBCT 3D 진단으로 신경 손상 위험을 최소화한 안전한 발치. 진정요법 가능.\n" .
						"심미치료 | 심미치료 | 미백 · 라미네이트 | /심미치료/ | 미백·라미네이트·심미 보철로 자연스러운 미소를 만듭니다. 최소 삭제 보존적 접근.\n" .
						"일반-검진 | 정기 검진 · 스케일링 | 예방 · 조기 발견 | /상담예약/ | 6개월~1년 주기 정기 검진은 잇몸 건강과 자연치아 보존의 첫 걸음입니다. 보험 적용.\n" .
						"예방클리닉 | 예방 클리닉 · 덴탈 SPA | 스케일링 · 불소도포 · 에어플로우 · 실란트 | /예방클리닉/ | 덴탈 SPA — 스케일링·에어플로우·불소도포·실란트 등 예방 진료. 충치·치주염 발생 전에 막는 가장 경제적인 치료.",
					'label' => '8개 진료과 (한 줄에 1개, 형식: 슬러그(질문에서 참조하는 키) | 이름 | 부제 | URL 경로 | 요약)',
					'type'  => 'textarea',
				),
			),
		),
	);
}

/**
 * 마무리 · 잔여 하드코딩 편집 가능화.
 *  - 의료진 페이지: 6개 진료과 + 상세보기 라벨 + CTA 진료시간
 *  - 오시는 길 페이지: 히어로·요일 라벨·지역 안내·3개 채널 카드
 *  - 홈 소식 섹션: 부제목·전체 보기·공지 태그
 */
function moondental_finish_content_fields() {
	return array(
		'doctors_more' => array(
			'title'  => '의료진 페이지 · 진료과·마무리',
			'fields' => array(
				'doctors_view_label' => array( 'default' => '상세 프로필 보기 →', 'label' => '카드 · 상세 프로필 링크 라벨', 'type' => 'text' ),
				'doctors_cta_hint'   => array( 'default' => '🕐 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00 · 일/공휴일 휴진', 'label' => 'CTA 하단 진료시간 힌트', 'type' => 'text' ),
				'doctors_specialties' => array(
					'default' => "icon:implant | 치과보철과 | 임플란트·크라운·틀니 등 손상된 치아 복원 전문\nicon:ortho | 치과교정과 | 부정교합 · 투명교정 · 부분교정 등 치아 배열 전문\nicon:preserve | 치과보존과 | 신경치료 · 충치 치료 등 자연치 보존 전문\nicon:leaf | 치주과 | 잇몸 질환 · 잇몸 수술 · 치주 관리 전문\nicon:pediatric | 소아치과 | 아이의 첫 치과 진료부터 청소년 교정까지\nicon:wisdom | 구강악안면외과 | 사랑니 · 매복치 · 임플란트 외과 진료",
					'label' => '진료과 카드 (한 줄에 1개, 형식: 아이콘 | 제목 | 설명) · 아이콘은 이모지 또는 icon:implant 형식',
					'type'  => 'textarea',
				),
			),
		),
		'location_page' => array(
			'title'  => '오시는 길 페이지 · 히어로·지역·채널',
			'fields' => array(
				'locpage_hero_title' => array( 'default' => '오시는 길', 'label' => '히어로 · 제목', 'type' => 'text' ),
				'locpage_region_eyebrow' => array( 'default' => '🌐 지역별 오시는 길', 'label' => '지역 그리드 · eyebrow', 'type' => 'text' ),
				'locpage_region_title'   => array( 'default' => '각 지역에서 문치과병원까지', 'label' => '지역 그리드 · 제목', 'type' => 'text' ),
				'locpage_region_lead'    => array( 'default' => "충남·충북·세종·대전·경기 중부권 28개 지역별 상세 교통 안내.\n지역명을 클릭하시면 해당 지역에서 천안 만남로까지의 상세 경로와 진료 안내를 보실 수 있습니다.", 'label' => '지역 그리드 · 리드 (줄바꿈 유지)', 'type' => 'textarea' ),
				'locpage_region_note'    => array( 'default' => 'ⓘ 이동 시간은 자동차 기준 대략적인 값입니다. 실제 교통 상황에 따라 달라질 수 있습니다.', 'label' => '지역 그리드 · 하단 안내', 'type' => 'textarea' ),
				'locpage_ch_phone_title' => array( 'default' => '전화 상담', 'label' => '전화 카드 · 제목', 'type' => 'text' ),
				'locpage_ch_phone_desc'  => array( 'default' => '{phone} · 진료시간 내 응답', 'label' => '전화 카드 · 설명 (토큰 {phone})', 'type' => 'text' ),
				'locpage_ch_phone_cta'   => array( 'default' => '바로 전화 →', 'label' => '전화 카드 · CTA', 'type' => 'text' ),
				'locpage_ch_kakao_title' => array( 'default' => '카카오톡 상담', 'label' => '카카오 카드 · 제목', 'type' => 'text' ),
				'locpage_ch_kakao_desc'  => array( 'default' => '24시간 메시지 · 진료시간 내 답변', 'label' => '카카오 카드 · 설명', 'type' => 'text' ),
				'locpage_ch_kakao_cta'   => array( 'default' => '카카오톡 채널 →', 'label' => '카카오 카드 · CTA', 'type' => 'text' ),
				'locpage_ch_naver_title' => array( 'default' => '네이버 예약', 'label' => '네이버 카드 · 제목', 'type' => 'text' ),
				'locpage_ch_naver_desc'  => array( 'default' => '24시간 자동 예약 · 일정 즉시 확정', 'label' => '네이버 카드 · 설명', 'type' => 'text' ),
				'locpage_ch_naver_cta'   => array( 'default' => '예약하러 가기 →', 'label' => '네이버 카드 · CTA', 'type' => 'text' ),
			),
		),
		'home_notices' => array(
			'title'  => '홈 · 소식 섹션 마이크로카피',
			'fields' => array(
				'notices_all_label'     => array( 'default' => '전체 보기 →', 'label' => '전체 보기 링크 라벨', 'type' => 'text' ),
				'notices_notice_subhead' => array( 'default' => '📢 문치과병원 소식', 'label' => '소식 서브 헤딩', 'type' => 'text' ),
				'notices_story_subhead'  => array( 'default' => '🦷 문치과병원 치아이야기', 'label' => '치아이야기 서브 헤딩', 'type' => 'text' ),
				'notices_notice_tag'    => array( 'default' => '공지', 'label' => '공지 태그 라벨', 'type' => 'text' ),
			),
		),
	);
}

/**
 * 최종 마무리 · 진짜 마지막 잔여 하드코딩.
 *  히스토리 히어로 리드, 플로팅 CTA, 크로스링크 헤딩, 각종 마이크로카피, 푸터 라벨.
 */
function moondental_final_content_fields() {
	return array(
		'history_hero' => array(
			'title'  => '역사 페이지 · 히어로 리드',
			'fields' => array(
				'history_hero_lead' => array( 'default' => '1995년부터 천안·아산에서 — 환자 한 분의 평생 치아 건강을 책임지는 마음으로 진료해온 30여년의 기록.', 'label' => '히어로 · 리드 문장', 'type' => 'textarea' ),
			),
		),
		'floating_cta' => array(
			'title'  => '플로팅 CTA (모바일 하단·데스크탑 오른쪽)',
			'fields' => array(
				'fcta_mobile_call_label'  => array( 'default' => '전화 예약', 'label' => '모바일 하단 · 전화 라벨', 'type' => 'text' ),
				'fcta_mobile_kakao_label' => array( 'default' => '카카오톡', 'label' => '모바일 하단 · 카카오톡 라벨', 'type' => 'text' ),
				'fcta_mobile_naver_label' => array( 'default' => '네이버 예약', 'label' => '모바일 하단 · 네이버 라벨', 'type' => 'text' ),
				'fcta_desk_call_label'    => array( 'default' => '전화 상담', 'label' => '데스크탑 FAB · 전화 라벨', 'type' => 'text' ),
				'fcta_desk_kakao_label'   => array( 'default' => '카카오톡 상담', 'label' => '데스크탑 FAB · 카카오톡 라벨', 'type' => 'text' ),
				'fcta_desk_naver_label'   => array( 'default' => '네이버 예약', 'label' => '데스크탑 FAB · 네이버 라벨', 'type' => 'text' ),
			),
		),
		'microcopy' => array(
			'title'  => '공통 마이크로카피 · 헤딩',
			'fields' => array(
				'micro_more_label'         => array( 'default' => '자세히 보기 →', 'label' => '홈 서비스 카드 · "자세히 보기" 라벨', 'type' => 'text' ),
				'micro_faq_all_label'      => array( 'default' => '전체 FAQ 보기 →', 'label' => '홈 FAQ · "전체 FAQ 보기" 버튼', 'type' => 'text' ),
				'svc_faq_title'            => array( 'default' => '자주 묻는 질문', 'label' => '진료 상세 페이지 · FAQ 섹션 제목', 'type' => 'text' ),
				'svc_other_title'          => array( 'default' => '다른 진료 영역 보기', 'label' => '진료 상세 페이지 · 다른 진료 영역 제목', 'type' => 'text' ),
				'strength_related_title'   => array( 'default' => '관련 페이지', 'label' => '강점 페이지 · 관련 페이지 헤딩', 'type' => 'text' ),
				'strength_more_eyebrow'    => array( 'default' => 'EXPLORE MORE', 'label' => '강점 페이지 · 다른 강점 eyebrow', 'type' => 'text' ),
				'strength_more_title'      => array( 'default' => '다른 강점도 확인하세요', 'label' => '강점 페이지 · 다른 강점 제목', 'type' => 'text' ),
				'strength_back_label'      => array( 'default' => '← 강점 목록으로', 'label' => '강점 404 · 목록으로 돌아가기 버튼', 'type' => 'text' ),
				'region_back_label'        => array( 'default' => '← 오시는 길로 돌아가기', 'label' => '지역 404 · 돌아가기 버튼', 'type' => 'text' ),
				'region_not_found_title'   => array( 'default' => '지역 정보를 찾을 수 없습니다', 'label' => '지역 404 · 제목', 'type' => 'text' ),
				'notice_tag_notice'        => array( 'default' => '소식', 'label' => '소식 페이지 · 카드 태그 (소식)', 'type' => 'text' ),
				'notice_tag_story'         => array( 'default' => '치아이야기', 'label' => '소식 페이지 · 카드 태그 (치아이야기)', 'type' => 'text' ),
			),
		),
		'footer_labels' => array(
			'title'  => '푸터 라벨 프리픽스',
			'fields' => array(
				'footer_hour_wd_label'    => array( 'default' => '평일', 'label' => '진료시간 · 평일 라벨', 'type' => 'text' ),
				'footer_hour_thu_label'   => array( 'default' => '목요일', 'label' => '진료시간 · 목요일 라벨', 'type' => 'text' ),
				'footer_hour_sat_label'   => array( 'default' => '토요일', 'label' => '진료시간 · 토요일 라벨', 'type' => 'text' ),
				'footer_hour_lunch_label' => array( 'default' => '점심', 'label' => '진료시간 · 점심 라벨', 'type' => 'text' ),
				'footer_prefix_rep'       => array( 'default' => '대표자: ', 'label' => '법적 표시 · 대표자 프리픽스', 'type' => 'text' ),
				'footer_prefix_open'      => array( 'default' => '개업일: ', 'label' => '법적 표시 · 개업일 프리픽스', 'type' => 'text' ),
				'footer_prefix_med'       => array( 'default' => '요양기관번호: ', 'label' => '법적 표시 · 요양기관번호 프리픽스', 'type' => 'text' ),
				'footer_prefix_ad'        => array( 'default' => '광고심의: ', 'label' => '법적 표시 · 광고심의 프리픽스', 'type' => 'text' ),
				'footer_name_token'       => array( 'default' => '한아의료재단 문치과병원', 'label' => '{name} 토큰 값 (저작권/각종 표기)', 'type' => 'text' ),
			),
		),
	);
}

/**
 * 진료 페이지 본문 · 각 서비스별 HTML 편집 가능.
 *  비어있으면 inc/content-defaults.php 의 기본 HTML이 사용됨.
 *  HTML 태그 (h2, h3, h4, p, ul, li, strong, em, blockquote, br, a) 그대로 입력 가능.
 */
function moondental_service_body_content_fields() {
	$service_bodies = array(
		'임플란트-센터'    => '임플란트 센터 본문',
		'투명교정-센터'    => '투명교정 센터 본문',
		'턱관절-클리닉'    => '턱관절 클리닉 본문',
		'사랑니-발치'      => '사랑니 발치 본문',
		'소아치과'         => '소아치과 본문',
		'심미치료'         => '심미치료 본문',
	);
	$fields = array();
	foreach ( $service_bodies as $slug => $label ) {
		$fields[ 'service_body_' . $slug ] = array(
			'default'     => '',
			'label'       => $label,
			'description' => '아래 [현재 기본 HTML 불러오기] 버튼을 눌러 편집 시작 · 비우고 저장하면 기본 내용 자동 복구',
			'type'        => 'html_textarea',
			'load_default_slug' => $slug,
		);
	}
	return array(
		'bodies' => array(
			'title'       => '진료 페이지 본문 HTML',
			'description' => '/임플란트-센터/ 등 진료 페이지 본문을 여기서 편집합니다. 각 필드에서 "현재 기본 HTML 불러오기" 버튼을 눌러 시작하세요.',
			'fields'      => $fields,
		),
	);
}

/**
 * 공통 헬퍼 — 그룹 정의를 받아 섹션·세팅·컨트롤을 등록.
 */
function moondental_register_panel_groups( $wp_customize, $panel_id, $groups, $section_id_prefix ) {
	$prio = 10;
	foreach ( $groups as $group_key => $group ) {
		$section_id = $section_id_prefix . $group_key;
		$wp_customize->add_section( $section_id, array(
			'title'       => $group['title'],
			'panel'       => $panel_id,
			'priority'    => $prio,
			'description' => $group['description'] ?? '',
		) );
		$prio += 10;
		foreach ( $group['fields'] as $key => $field ) {
			$setting_id = 'md_content_' . $key;
			$default    = $field['default'];
			$type       = $field['type'] ?? 'text';

			// v3.33.5 · html_textarea 타입 · HTML 허용 큰 textarea (진료 페이지 본문용)
			$is_html = ( $type === 'html_textarea' );
			$sanitize = 'sanitize_text_field';
			if ( $is_html ) {
				$sanitize = 'wp_kses_post';
			} elseif ( $type === 'textarea' ) {
				$sanitize = 'sanitize_textarea_field';
			}

			$wp_customize->add_setting( $setting_id, array(
				'default'           => $default,
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			) );
			$wp_customize->add_control( $setting_id, array(
				'label'       => $field['label'],
				'description' => $field['description'] ?? '',
				'section'     => $section_id,
				'type'        => $is_html ? 'textarea' : $type,
				'input_attrs' => $is_html ? array( 'rows' => 20, 'style' => 'font-family: monospace; font-size: 12px;' ) : array(),
			) );
		}
	}
}
