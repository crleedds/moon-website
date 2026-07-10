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

				'trust_2_value' => array( 'default' => '6',     'label' => '②번 — 숫자',     'type' => 'text' ),
				'trust_2_unit'  => array( 'default' => '과',    'label' => '②번 — 단위',     'type' => 'text' ),
				'trust_2_label' => array( 'default' => '전문 진료과', 'label' => '②번 — 라벨', 'type' => 'text' ),
				'trust_2_sub'   => array( 'default' => '보철·교정·보존·치주·소아·외과', 'label' => '②번 — 부제', 'type' => 'text' ),

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
				'why_title'   => array( 'default' => '천안·아산에서 왜 문치과병원을 찾으시나요?', 'label' => '섹션 — 제목', 'type' => 'text' ),
				'why_lead'    => array( 'default' => '천안 만남로에서 30여년 — 환자분들이 선택해온 이유 4가지로 정리해드립니다.', 'label' => '섹션 — 설명', 'type' => 'textarea' ),

				'why_1_icon'  => array( 'default' => '🏥', 'label' => '①번 — 아이콘(이모지)', 'type' => 'text' ),
				'why_1_title' => array( 'default' => '30여년, 한자리에서', 'label' => '①번 — 제목', 'type' => 'text' ),
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
				'services_eyebrow' => array( 'default' => 'CLINICAL SERVICES · 천안 진료항목', 'label' => 'eyebrow', 'type' => 'text' ),
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
				'notices_title'          => array( 'default' => '공지사항',     'label' => '섹션 — 제목',    'type' => 'text' ),
				'notices_all_link_label' => array( 'default' => '전체보기 →',  'label' => '전체보기 링크 라벨', 'type' => 'text' ),
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
				'cta_hint'    => array( 'default' => '진료시간: 월·화·수·금 09:00–20:30 · 목 09:00–18:00 · 토 09:00–14:00 · 일/공휴일 휴진', 'label' => '하단 진료시간 안내', 'type' => 'text' ),
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

				'price_tab_aesthetic_label' => array( 'default' => '심미·미백', 'label' => '탭 5 — 라벨', 'type' => 'text' ),
				'price_tab_aesthetic_rows'  => array( 'default' => $default_aesthetic, 'label' => '탭 5 — 항목', 'type' => 'textarea' ),

				'price_tab_kids_label' => array( 'default' => '소아·예방', 'label' => '탭 6 — 라벨', 'type' => 'text' ),
				'price_tab_kids_rows'  => array( 'default' => $default_kids, 'label' => '탭 6 — 항목', 'type' => 'textarea' ),

				'price_tab_tmj_label' => array( 'default' => '턱관절', 'label' => '탭 7 — 라벨', 'type' => 'text' ),
				'price_tab_tmj_rows'  => array( 'default' => $default_tmj, 'label' => '탭 7 — 항목', 'type' => 'textarea' ),
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
				'price_cta_meta_1_value' => array( 'default' => '월·화·수·금 09:00–20:30 · 목 ~18:00 · 토 ~14:00', 'label' => '메타 ①번 — 값', 'type' => 'text' ),

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
		'leesj'  => array( 'name' => '이승주', 'role' => '9F 종합진료센터' ),
		'leesu'  => array( 'name' => '이수연', 'role' => '9F 종합진료센터' ),
		'kwon'   => array( 'name' => '권혜진', 'role' => '9F 종합진료센터' ),
		'munji'  => array( 'name' => '문지현', 'role' => '10F 임플란트센터' ),
		'leech'  => array( 'name' => '이창률', 'role' => '10F 임플란트센터' ),
		'leeyi'  => array( 'name' => '이영일', 'role' => '11F 교정과' ),
		'kimsi'  => array( 'name' => '김세일', 'role' => '11F 종합진료센터' ),
		'jeong'  => array( 'name' => '정석형', 'role' => '11F 종합진료센터' ),
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
				'doctors_stat_2_value' => array( 'default' => '3개층', 'label' => 'stat ②번 숫자', 'type' => 'text' ),
				'doctors_stat_2_label' => array( 'default' => '9F · 10F · 11F', 'label' => 'stat ②번 라벨', 'type' => 'text' ),
				'doctors_stat_3_value' => array( 'default' => '30여년', 'label' => 'stat ③번 숫자', 'type' => 'text' ),
				'doctors_stat_3_label' => array( 'default' => '1995년 개원', 'label' => 'stat ③번 라벨', 'type' => 'text' ),

				'doctors_list_eyebrow' => array( 'default' => 'Our Doctors', 'label' => '의료진 그리드 — eyebrow', 'type' => 'text' ),
				'doctors_list_title'   => array( 'default' => '전체 의료진', 'label' => '의료진 그리드 — 제목', 'type' => 'text' ),
				'doctors_list_lead'    => array( 'default' => '각 분야 전문의의 정성스러운 진료를 받으실 수 있습니다.', 'label' => '의료진 그리드 — 설명', 'type' => 'textarea' ),
				'doctors_grid_hint'    => array( 'default' => '진료 예약 시 원하시는 의료진을 지정하실 수 있습니다.', 'label' => '의료진 그리드 — 하단 안내', 'type' => 'text' ),

				'doctors_cta_chip'  => array( 'default' => '상담 예약', 'label' => 'CTA 배너 — chip', 'type' => 'text' ),
				'doctors_cta_title' => array( 'default' => '어떤 원장님께 진료받고 싶으신가요?', 'label' => 'CTA 배너 — 제목', 'type' => 'text' ),
				'doctors_cta_lead'  => array( 'default' => '부담 없이 상담받으세요. 환자분께 맞는 의료진을 안내드립니다.', 'label' => 'CTA 배너 — 설명', 'type' => 'textarea' ),

				/* ── 전체 직원 섹션 (의료진 외 스태프) ── */
				'staff_section_eyebrow' => array( 'default' => 'Our Staff', 'label' => '전체 직원 섹션 — eyebrow', 'type' => 'text' ),
				'staff_section_title'   => array( 'default' => '전체 직원', 'label' => '전체 직원 섹션 — 제목', 'type' => 'text' ),
				'staff_section_lead'    => array( 'default' => '한아의료재단 문치과병원에서 환자분과 함께하는 모든 의료진입니다.', 'label' => '전체 직원 섹션 — 설명', 'type' => 'textarea' ),
				'staff_list' => array(
					'default' => "진료실|이사|이순민\n진료실|팀장|박지선\n진료실|실장|이희남\n진료실|실장|임은혜\n진료실|실장|지선미\n진료실|실장|한경순\n진료실|책임|주경심\n진료실|책임|윤경옥\n진료실|책임|노금란\n진료실|책임|김정애\n진료실|과장|남소영\n진료실|선임|김인애\n진료실|선임|박미선\n진료실|선임|김윤미\n진료실|선임|유현영\n진료실|주임|서채빈\n진료실|주임|박명자\n진료실|주임|금민주\n진료실|주임|전서혜\n진료실|주임|유혜정\n진료실|주임|서소리\n진료실|주임|장유정\n진료실|주임|이아연\n진료실|주임|김경하\n진료실|주임|이다윤\n진료실|주임|이하은\n진료실|주임|김하늘\n진료실|주임|김우정\n진료실|주임|최로미\n진료실|주임|권민지\n기공실|이사|조항수\n기공실|차장|맹의재\n기공실|과장|장순복\n기공실|기사|박진옥\n기공실|기사|노재형\n서비스지원실|이사|강미해\n서비스지원실|실장|이선양\n서비스지원실|책임코디|김다경\n서비스지원실|책임코디|공미희\n서비스지원실|선임코디|정소리\n서비스지원실|선임코디|황진아\n서비스지원실|선임코디|박혜령\n경영지원실|행정원장|양병욱\n경영지원실|과장|이충현\n경영지원실|사원|김하진\n경영지원실|사원|카밀라\n경영지원실|사원|게를레\n관리사무소|소장|강성하\n비서실|과장|김동현\n비서실|과장|민종기\n비서실|대리|이슬기",
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
				'footer_col_hours_title'    => array( 'default' => '진료시간', 'label' => '컬럼 ①번 제목', 'type' => 'text' ),
				'footer_col_services_title' => array( 'default' => '진료안내', 'label' => '컬럼 ②번 제목', 'type' => 'text' ),
				'footer_col_about_title'    => array( 'default' => '병원안내', 'label' => '컬럼 ③번 제목', 'type' => 'text' ),
				'footer_copyright'          => array( 'default' => 'All rights reserved.', 'label' => '저작권 문구', 'type' => 'text' ),
			),
		),
		'footer_legal' => array(
			'title'  => '푸터 — 의료기관 법적 표시 (필수)',
			'fields' => array(
				'footer_legal_show'      => array( 'default' => 'yes', 'label' => '법적 정보 섹션 표시 (yes/no)', 'type' => 'text' ),
				'footer_legal_inst_name' => array( 'default' => '한아의료재단 문치과병원', 'label' => '의료기관 명칭', 'type' => 'text' ),
				'footer_legal_inst_type' => array( 'default' => '치과병원 (의료법인·병원급)', 'label' => '의료기관 종별', 'type' => 'text' ),
				'footer_legal_rep'       => array( 'default' => '대표자: 문은수 이사장', 'label' => '대표자', 'type' => 'text' ),
				'footer_legal_biz_no'    => array( 'default' => '사업자등록번호: 312-82-00000', 'label' => '사업자등록번호', 'type' => 'text' ),
				'footer_legal_med_no'    => array( 'default' => '의료기관 고유번호: 34400117', 'label' => '의료기관 고유번호', 'type' => 'text' ),
				'footer_legal_ad_no'     => array( 'default' => '의료광고심의필: 신청 중', 'label' => '의료광고심의 번호 (비우면 미표시)', 'type' => 'text' ),
				'footer_legal_privacy_officer' => array( 'default' => '개인정보 보호책임자: 문은수 (moondental1995@naver.com)', 'label' => '개인정보 보호책임자', 'type' => 'text' ),
				'footer_legal_extra' => array(
					'default' => '본 사이트의 모든 의료 정보는 환자분의 이해를 돕기 위한 참고용이며, 진단·치료는 반드시 의료진의 상담을 통해 결정됩니다. 의료광고법에 따라 환자의 치료 경험담·전후 사진 등은 별도 동의 하에만 게시됩니다.',
					'label' => '추가 안내문 (의료광고법·면책 문구)',
					'type' => 'textarea',
				),
			),
		),
		'footer_links' => array(
			'title'  => '푸터 — 하단 정책 링크',
			'fields' => array(
				'footer_link_privacy'    => array( 'default' => '개인정보처리방침|/개인정보처리방침/', 'label' => '링크 ①  (형식: 라벨|URL — 비우면 숨김)', 'type' => 'text' ),
				'footer_link_terms'      => array( 'default' => '이용약관|/이용약관/',           'label' => '링크 ②', 'type' => 'text' ),
				'footer_link_pricing'    => array( 'default' => '비급여 진료비|/비용-안내/',     'label' => '링크 ③', 'type' => 'text' ),
				'footer_link_email'      => array( 'default' => '이메일 무단수집거부|',           'label' => '링크 ④ (URL 비우면 클릭 불가)', 'type' => 'text' ),
				'footer_link_sitemap'    => array( 'default' => '',                                 'label' => '링크 ⑤ (선택)', 'type' => 'text' ),
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
				'cta_btn_show_phone'  => array( 'default' => 'yes', 'label' => '전화 버튼에 전화번호 같이 표시 (yes/no)', 'type' => 'text' ),
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
		3 => array( 'name' => '이○○', 'gender' => '여성', 'age' => '30대', 'service' => '투명교정', 'rating' => '5', 'text' => '슈어스마일 투명교정 받았는데 처음에 걱정했던 것보다 훨씬 편했어요. 11F 교정과 원장님이 사진 시뮬레이션으로 결과를 미리 보여주셨고, 6개월 만에 만족스러운 결과를 얻었습니다.' ),
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
		3 => array( 'label' => '전문 진료과 6과',   'value' => '보철·교정·보존·치주·소아·외과',                'icon' => '🦷' ),
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
 *  - 9 의료진 직책 (예: "원장 · 9F 종합진료센터")
 */
function moondental_doctor_meta_content_fields() {
	$role_defaults = array(
		'munes' => array( 'name' => '문은수', 'role' => '대표 병원장' ),
		'leesj' => array( 'name' => '이승주', 'role' => '원장 · 9F 종합진료센터' ),
		'leesu' => array( 'name' => '이수연', 'role' => '원장 · 9F 종합진료센터' ),
		'kwon'  => array( 'name' => '권혜진', 'role' => '원장 · 9F 종합진료센터' ),
		'munji' => array( 'name' => '문지현', 'role' => '원장 · 10F 임플란트센터' ),
		'leech' => array( 'name' => '이창률', 'role' => '원장 · 10F 임플란트센터' ),
		'leeyi' => array( 'name' => '이영일', 'role' => '원장 · 11F 교정과' ),
		'kimsi' => array( 'name' => '김세일', 'role' => '원장 · 11F 종합진료센터' ),
		'jeong' => array( 'name' => '정석형', 'role' => '원장 · 11F 종합진료센터' ),
	);

	$groups = array(
		'doctor_groups' => array(
			'title'  => '의료진 — 진료센터 그룹명 (4개)',
			'fields' => array(
				'doctor_group_1' => array( 'default' => '대표 병원장',                       'label' => '그룹 1 — 라벨', 'type' => 'text' ),
				'doctor_group_2' => array( 'default' => '9F 종합진료센터',                   'label' => '그룹 2 — 라벨', 'type' => 'text' ),
				'doctor_group_3' => array( 'default' => '10F 임플란트센터',                  'label' => '그룹 3 — 라벨', 'type' => 'text' ),
				'doctor_group_4' => array( 'default' => '11F 교정과 · 종합진료센터',         'label' => '그룹 4 — 라벨', 'type' => 'text' ),
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

	/* 9명 개별 — intro / 자격사항 4개 / Q&A 텍스트영역 */
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
					'label'   => $name . ' — 자격 체크리스트 (한 줄당 1개, 4개 권장, 빈칸 시 약력 앞부분 자동 사용)',
					'type'    => 'textarea',
				),
				"doc_{$key}_qa" => array(
					'default' => '',
					'label'   => $name . ' — Q&A (한 줄당 1개, "질문 | 답변" 파이프 구분)',
					'type'    => 'textarea',
				),
				"doc_{$key}_interests" => array(
					'default' => '',
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
 * 공통 헬퍼 — 그룹 정의를 받아 섹션·세팅·컨트롤을 등록.
 */
function moondental_register_panel_groups( $wp_customize, $panel_id, $groups, $section_id_prefix ) {
	$prio = 10;
	foreach ( $groups as $group_key => $group ) {
		$section_id = $section_id_prefix . $group_key;
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
