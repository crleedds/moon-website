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
 */
function md_content( $key, $default = '' ) {
	return get_theme_mod( 'md_content_' . $key, $default );
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
				'trust_1_sub'   => array( 'default' => '한자리에서 이어온 신뢰', 'label' => '①번 — 부제', 'type' => 'text' ),

				'trust_2_value' => array( 'default' => '10',    'label' => '②번 — 숫자',     'type' => 'text' ),
				'trust_2_unit'  => array( 'default' => '명',     'label' => '②번 — 단위',     'type' => 'text' ),
				'trust_2_label' => array( 'default' => '각 분야 전문 의료진', 'label' => '②번 — 라벨', 'type' => 'text' ),
				'trust_2_sub'   => array( 'default' => '보철 · 교정 · 보존 · 외과', 'label' => '②번 — 부제', 'type' => 'text' ),

				'trust_3_value' => array( 'default' => '3',     'label' => '③번 — 숫자',     'type' => 'text' ),
				'trust_3_unit'  => array( 'default' => '개층',   'label' => '③번 — 단위',     'type' => 'text' ),
				'trust_3_label' => array( 'default' => '통합 진료센터', 'label' => '③번 — 라벨', 'type' => 'text' ),
				'trust_3_sub'   => array( 'default' => '9F 종합 · 10F 임플란트 · 11F 교정', 'label' => '③번 — 부제', 'type' => 'text' ),

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
				'why_eyebrow' => array( 'default' => 'Why Moon Dental', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'why_title'   => array( 'default' => '왜 문치과병원을 찾으시나요?', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'why_lead'    => array( 'default' => '30년 동안 환자분들이 선택해온 이유 — 4가지로 정리해드립니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

				'why_1_icon'  => array( 'default' => '🏥', 'label' => '①번 — 아이콘(이모지)', 'type' => 'text' ),
				'why_1_title' => array( 'default' => '30년, 한자리에서', 'label' => '①번 — 제목', 'type' => 'text' ),
				'why_1_desc'  => array( 'default' => '1995년부터 천안 만남로 한자리에서 진료해온 동네 치과. 환자 한 분의 평생 치아를 길게 봅니다.', 'label' => '①번 — 설명', 'type' => 'textarea' ),

				'why_2_icon'  => array( 'default' => '🏢', 'label' => '②번 — 아이콘', 'type' => 'text' ),
				'why_2_title' => array( 'default' => '통합 진료센터', 'label' => '②번 — 제목', 'type' => 'text' ),
				'why_2_desc'  => array( 'default' => '9F 종합·10F 임플란트·11F 교정 — 분야별 전문 의료진의 협진을 한 곳에서 받으실 수 있습니다.', 'label' => '②번 — 설명', 'type' => 'textarea' ),

				'why_3_icon'  => array( 'default' => '❤️', 'label' => '③번 — 아이콘', 'type' => 'text' ),
				'why_3_title' => array( 'default' => '전신질환 안심 진료', 'label' => '③번 — 제목', 'type' => 'text' ),
				'why_3_desc'  => array( 'default' => '혈압기·당검사·심전도·산소포화도 상시 보유. 고혈압·당뇨·심장질환자도 안전하게 진료합니다.', 'label' => '③번 — 설명', 'type' => 'textarea' ),

				'why_4_icon'  => array( 'default' => '🛡️', 'label' => '④번 — 아이콘', 'type' => 'text' ),
				'why_4_title' => array( 'default' => '평생 A/S 시스템', 'label' => '④번 — 제목', 'type' => 'text' ),
				'why_4_desc'  => array( 'default' => '시술 후 정기 검진과 문제 발생 시 책임 대응. 비용은 시술 시점에 한 번, 관리는 평생.', 'label' => '④번 — 설명', 'type' => 'textarea' ),
			),
		),

		/* ─── Services 섹션 head ───────────────────────────── */
		'services' => array(
			'title'  => '홈 — 진료안내 섹션 head',
			'fields' => array(
				'services_eyebrow' => array( 'default' => 'Services', 'label' => 'eyebrow', 'type' => 'text' ),
				'services_title'   => array( 'default' => '한 곳에서, 평생 치아 건강을', 'label' => '제목', 'type' => 'text' ),
				'services_lead'    => array( 'default' => '일반진료부터 임플란트·교정·심미·소아예방까지 — 한 분의 환자를 오래 보는 동네 치과의 마음으로 진료합니다.', 'label' => '설명', 'type' => 'textarea' ),
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
				'facility_title'   => array( 'default' => '정확한 진단과 안전한 진료를 위한 인프라', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'facility_lead'    => array( 'default' => '9F~13F 통합 진료센터 — 디지털 진단·수술 시스템과 응급 의료 장비를 갖추고 있습니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

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

		/* ─── CTA 배너 ─────────────────────────────────────── */
		'cta' => array(
			'title'  => '홈 — 하단 CTA 배너',
			'fields' => array(
				'cta_eyebrow' => array( 'default' => '상담 예약', 'label' => 'eyebrow', 'type' => 'text' ),
				'cta_title'   => array( 'default' => '치아 때문에 망설이고 계신가요?', 'label' => '제목', 'type' => 'text' ),
				'cta_lead'    => array( 'default' => "환자분의 상황을 먼저 듣고, 꼭 필요한 치료만 권합니다.\n지금 상담을 신청하시면 진료시간 내 빠르게 연락드릴게요.", 'label' => '설명 (줄바꿈 가능)', 'type' => 'textarea' ),
				'cta_btn1'    => array( 'default' => '📅 상담 예약하기', 'label' => '버튼 1 라벨', 'type' => 'text' ),
				'cta_btn2'    => array( 'default' => '카카오톡', 'label' => '버튼 2 라벨', 'type' => 'text' ),
				'cta_hint'    => array( 'default' => '진료시간: 평일 09:00–20:30 · 토 09:00–14:00 · 일/공휴일 휴진', 'label' => '하단 진료시간 안내', 'type' => 'text' ),
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
