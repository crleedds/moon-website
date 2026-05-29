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
 * 비용 안내 페이지 콘텐츠 필드 정의.
 */
function moondental_pricing_content_fields() {
	$default_implant = "일반 임플란트 (식립 1개) | 85만 원부터 | 국산 픽스처\n프리미엄 임플란트 (식립 1개) | 95만 원부터 | 메가젠·해외 픽스처\n임플란트 보철 (지르코니아) | 55만 원부터 | 심미·강도 우수\n임플란트 보철 (금 / 골드) | 105만 원부터 | 내구성·교합 안정\n뼈이식 (간단·소량) | 30만 원부터 | 뼈가 부족할 때\n뼈이식 (상악동 거상술 등) | 50만 원부터 | 윗턱 골량 부족\n디지털 가이드 수술 | +10만 원/치아 | 정밀 수술 옵션";

	$default_ortho = "소아 교정 (1차) | 150만 원부터 | 만 7~10세 골든타임\n부분 교정 (앞니 등) | 250만 원부터 | 국소 부정만 교정\n투명 교정 (간단 케이스) | 190만 원부터 | SureSmile Lite\n투명 교정 (표준 케이스) | 320만 원부터 | SureSmile Standard\n투명 교정 (전체 교정) | 470만 원부터 | 2026 할인가\n메탈(금속) 브라켓 교정 | 420만 원 | 전통 교정\n심미(레진) 브라켓 교정 | 450만 원 | 브라켓 색 자연스럽게\n자가결찰 교정 (A-Line) | 500만 원 | 와이어 결찰 자동";

	$default_crown = "도자기 + 금속 크라운 (PFM) | 45만 원부터 | 대중 보급형\n지르코니아 크라운 (어금니) | 55만 원부터 | 강도·심미 균형\n지르코니아 크라운 (앞니) | 60만 원부터 | 심미 우선\n금 (골드) 크라운 | 95만 원부터 | 교합 안정\n세라믹 인레이 (부분 수복) | 35만 원부터 | 심미적 충전\n금 (골드) 인레이 | 55만 원부터 | 내구성 우선\n틀니 (부분 / 전체) | 150만 원부터 | 진단 후 견적";

	$default_decay = "레진 충전 (작은 충치, 1면) | 10만 원부터 | 치아 색 자연스럽게\n레진 충전 (중간 충치, 2면) | 15만 원부터 | 인접면 포함\n레진 충전 (큰 충치, 3면 이상) | 30만 원부터 | 광범위 수복\n깊은 충치 레진 (신경 근접) | 15만 원부터 | 심부 충치\n어금니 광범위 충치 (MO/DO 포함) | 30만 원부터 | 교두당 추가 5만\n어금니 광범위 충치 (MOD 포함) | 50만 원부터 | 최대 범위\n앞니 사이 틈 메우기 (디아스테마) | 20만 원부터 | 심미 보완\n치경부 마모증 (잇몸 라인 충전) | 7만 원부터 | 잇몸 경계\n레진 비니어 (앞니 심미) | 35만 원부터 | 변색 보완\n레진 코어 (크라운 토대) | 8만 원 | 보철 전 기둥\n신경치료 후 레진 충전 | 8만 원 | 신경치료 마감\n유치 레진 충전 (1면) | 8만 원 | 소아용\n유치 레진 충전 (2면 이상) | 10만 원 | 소아용";

	$default_gum = "스케일링 (보험, 연 1회) | 25,100 원 | 만 19세 이상\n스케일링 (추가) | 6만 원 | 비급여\n잇몸치료 (간단·보험) | 21,300 원부터 | 치주염 초기\n잇몸치료 (복잡·보험) | 25,000 원부터 | 치주염 진행\n잇몸 수술 + 뼈이식 | 치아당 30만 원 | 잇몸뼈 회복\n잇몸 PDRN 주사 | 5만 원 | 염증 완화\n신경치료 (전치) | 보험 적용 | 건강보험\n신경치료 (구치) | 보험 적용 | 건강보험";

	$default_aesthetic = "라미네이트 (앞니 심미) | 치아당 66만 원 | 부가세 포함\n치아 미백 (자가, 4주 키트) | 33만 원부터 | 집에서 사용\n치아 미백 (전문가, 당일 시술) | 33만 원부터 | 1-day 33~44만\n치아 미백 (전문가, 2일 시술) | 44만 원부터 | 2-day 44~55만\n잇몸 미백 | 악당 20만 원부터 | 잇몸 색 개선\n거미스마일 (잇몸 성형) | 치아당 20만 원 | 레이저 시술";

	$default_kids = "실란트 (보험, 어금니) | 본인부담 21,700원~ | 만 18세 이하\n실란트 (비급여, 작은어금니) | 치아당 3만 원 | 예방 시술\n불소 도포 (충치 예방) | 전악 3만 원 | 치아 강화\n소아 SS 크라운 (유치) | 15~20만 원 | 유치 보존\n소아 SS 크라운 (영구치) | 20~25만 원 | 영구치 보호";

	$default_tmj = "턱관절 보톡스 | 20만 원 | 이갈이·교근 통증\n턱관절 PDRN 주사 | 20만 원 | 관절 염증 완화\n턱관절 보호 장치 (하드 스플린트) | 100만 원 | 야간 착용";

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
				'price_hero_lead'    => array( 'default' => '문치과병원은 30년 동안 정직한 진료비를 약속해왔습니다. 불필요한 치료를 권하지 않고, 시작 후 추가 비용이 발생하지 않습니다.', 'label' => '설명', 'type' => 'textarea' ),
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

		/* ─── 치료별 예상 비용 — 7 카테고리 텍스트 ─── */
		'pricing_tables' => array(
			'title'  => '비용 페이지 — 치료별 비용 표 (7 카테고리)',
			'fields' => array(
				'price_tables_eyebrow' => array( 'default' => 'Estimated Cost', 'label' => '섹션 — eyebrow', 'type' => 'text' ),
				'price_tables_title'   => array( 'default' => '치료별 예상 비용', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'price_tables_lead'    => array( 'default' => '아래 표는 표준 기준입니다. 정확한 비용은 정밀 진단 후 견적서로 안내드립니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),
				'price_tables_hint'    => array( 'default' => '환자분의 구강 상태·재료 선택·치료 난이도에 따라 조정될 수 있습니다. 최종 비용은 정밀 진단 후 견적서로 확정해드립니다.', 'label' => '하단 안내문구', 'type' => 'textarea' ),

				'price_tab_implant_label' => array( 'default' => '임플란트', 'label' => '탭 1 — 라벨', 'type' => 'text' ),
				'price_tab_implant_rows'  => array( 'default' => $default_implant, 'label' => '탭 1 — 항목 (한 줄당 1개, "이름 | 가격 | 비고" 파이프 구분)', 'type' => 'textarea' ),

				'price_tab_ortho_label' => array( 'default' => '교정', 'label' => '탭 2 — 라벨', 'type' => 'text' ),
				'price_tab_ortho_rows'  => array( 'default' => $default_ortho, 'label' => '탭 2 — 항목', 'type' => 'textarea' ),

				'price_tab_crown_label' => array( 'default' => '크라운·틀니', 'label' => '탭 3 — 라벨', 'type' => 'text' ),
				'price_tab_crown_rows'  => array( 'default' => $default_crown, 'label' => '탭 3 — 항목', 'type' => 'textarea' ),

				'price_tab_decay_label' => array( 'default' => '충치·레진', 'label' => '탭 4 — 라벨', 'type' => 'text' ),
				'price_tab_decay_rows'  => array( 'default' => $default_decay, 'label' => '탭 4 — 항목', 'type' => 'textarea' ),

				'price_tab_gum_label' => array( 'default' => '잇몸·자연치아', 'label' => '탭 5 — 라벨', 'type' => 'text' ),
				'price_tab_gum_rows'  => array( 'default' => $default_gum, 'label' => '탭 5 — 항목', 'type' => 'textarea' ),

				'price_tab_aesthetic_label' => array( 'default' => '심미·미백', 'label' => '탭 6 — 라벨', 'type' => 'text' ),
				'price_tab_aesthetic_rows'  => array( 'default' => $default_aesthetic, 'label' => '탭 6 — 항목', 'type' => 'textarea' ),

				'price_tab_kids_label' => array( 'default' => '소아·예방', 'label' => '탭 7 — 라벨', 'type' => 'text' ),
				'price_tab_kids_rows'  => array( 'default' => $default_kids, 'label' => '탭 7 — 항목', 'type' => 'textarea' ),

				'price_tab_tmj_label' => array( 'default' => '턱관절', 'label' => '탭 8 — 라벨', 'type' => 'text' ),
				'price_tab_tmj_rows'  => array( 'default' => $default_tmj, 'label' => '탭 8 — 항목', 'type' => 'textarea' ),
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
				'price_cta_meta_1_value' => array( 'default' => '평일 09:00–20:30 · 목 ~18:00 · 토 ~14:00', 'label' => '메타 ①번 — 값', 'type' => 'text' ),

				'price_cta_meta_2_label' => array( 'default' => '예약 채널', 'label' => '메타 ②번 — 라벨', 'type' => 'text' ),
				'price_cta_meta_2_value' => array( 'default' => '전화 · 카카오톡 · 네이버 예약 (24시간)', 'label' => '메타 ②번 — 값', 'type' => 'text' ),

				'price_cta_meta_3_label' => array( 'default' => '위치', 'label' => '메타 ③번 — 라벨', 'type' => 'text' ),
				'price_cta_meta_3_value' => array( 'default' => '충남 천안시 동남구 만남로 52, 문타워 9~13층', 'label' => '메타 ③번 — 값', 'type' => 'text' ),
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
 * 헤더·푸터 콘텐츠 필드 정의.
 */
function moondental_chrome_content_fields() {
	return array(
		'header' => array(
			'title'  => '헤더 — CTA 버튼',
			'fields' => array(
				'header_cta_label' => array( 'default' => '오시는 길', 'label' => 'CTA 버튼 라벨', 'type' => 'text' ),
				'header_cta_icon'  => array( 'default' => '📍', 'label' => 'CTA 버튼 아이콘', 'type' => 'text' ),
				'header_cta_url'   => array( 'default' => '/오시는-길/', 'label' => 'CTA 버튼 링크 (사이트 내 경로 또는 전체 URL)', 'type' => 'text' ),
			),
		),
		'footer' => array(
			'title'  => '푸터 — 텍스트',
			'fields' => array(
				'footer_brand_tagline' => array( 'default' => '실력과 품격있는 진료', 'label' => '브랜드 슬로건 (로고 아래)', 'type' => 'text' ),
				'footer_col_hours_title'    => array( 'default' => '진료시간', 'label' => '컬럼 ①번 제목', 'type' => 'text' ),
				'footer_col_services_title' => array( 'default' => '진료안내', 'label' => '컬럼 ②번 제목', 'type' => 'text' ),
				'footer_col_about_title'    => array( 'default' => '병원안내', 'label' => '컬럼 ③번 제목', 'type' => 'text' ),
				'footer_copyright'    => array( 'default' => 'All rights reserved.', 'label' => '저작권 문구 (© 연도 회사명 뒤 텍스트)', 'type' => 'text' ),
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
