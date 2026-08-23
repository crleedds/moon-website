<?php
/**
 * English phrase dictionary · v3.44.217
 *
 *  구조: '한국어 원문' => 'English translation'
 *  - 페이지에서 실제로 추출한 문구를 담는다.
 *  - 없는 문구는 한국어 원문 그대로 출력된다 (안전한 fallback).
 *  - 긴 문구가 먼저 치환되므로 부분 문자열 충돌은 자동 처리된다.
 *  - 추가할 때는 화면에 보이는 그대로, 앞뒤 공백 없이 적는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return array(

	/* ── 병원 · 센터 이름 ───────────────────────────── */
	'한아의료재단 문치과병원' => 'Hanah Medical Foundation Moon Dental Hospital',
	'문치과병원'             => 'Moon Dental Hospital',
	'임플란트센터'           => 'Implant Center',
	'교정센터'               => 'Orthodontic Center',
	'스마일디자인센터'       => 'Smile Design Center',
	'한아문화센터'           => 'Hanah Culture Center',
	'한아 임플란트 보철연구소' => 'Hanah Implant Prosthetic Laboratory',
	'한아 임플란트 보철 연구소' => 'Hanah Implant Prosthetic Laboratory',
	'원내기공실'             => 'In-house Dental Laboratory',
	'디지털센터'             => 'Digital Center',

	/* ── 진료과 ────────────────────────────────────── */
	'소아치과'   => 'Pediatric Dentistry',
	'치주과'     => 'Periodontics',
	'보철과'     => 'Prosthodontics',
	'보존과'     => 'Conservative Dentistry',
	'구강외과'   => 'Oral Surgery',
	'구강내과'   => 'Oral Medicine',
	'턱관절 클리닉' => 'TMJ Clinic',
	'예방클리닉' => 'Preventive Clinic',
	'신환접수'   => 'New Patient Reception',
	'경영지원실' => 'Administration Office',

	/* ── 진료 항목 ─────────────────────────────────── */
	'자연치아 살리기'   => 'Natural Tooth Preservation',
	'슈어스마일 투명교정' => 'SureSmile Clear Aligner',
	'투명교정'          => 'Clear Aligner',
	'브라켓 치아교정'   => 'Bracket Orthodontics',
	'사랑니 발치'       => 'Wisdom Tooth Extraction',
	'심미치료'          => 'Cosmetic Dentistry',
	'라미네이트'        => 'Laminate Veneer',
	'벌어진 앞니 레진 수복' => 'Composite Repair for Gapped Front Teeth',
	'왜소치 치료'       => 'Microdontia Treatment',
	'치아 성형 · 잇몸 미백' => 'Tooth Contouring · Gum Whitening',
	'반점치(화이트스팟) 제거' => 'White Spot Removal',
	'최소침습 라미네이트' => 'Minimally Invasive Laminate',

	/* ── 공통 UI ───────────────────────────────────── */
	'자세히 보기 →' => 'Learn more →',
	'자세히 보기'   => 'Learn more',
	'더 보기'       => 'See more',
	'전체 보기'     => 'View all',
	'바로가기'      => 'Go',
	'오시는 길'     => 'Directions',
	'전화 상담'     => 'Call Us',
	'네이버 예약'   => 'Naver Booking',
	'카카오톡 상담' => 'KakaoTalk',
	'편리한 상담'   => 'Easy Consultation',
	'상담예약'      => 'Book a Consultation',
	'비용안내'      => 'Fees',
	'비용 안내'     => 'Fees',
	'의료진'        => 'Our Doctors',
	'병원안내'      => 'About Us',
	'진료과'        => 'Departments',
	'층별 안내'     => 'Floor Guide',
	'진료시간'      => 'Office Hours',
	'주차 안내'     => 'Parking',

	/* ── 섹션 제목 ─────────────────────────────────── */
	'문치과병원 주요 페이지' => 'Key Pages',
	'문치과병원 소식'       => 'News',
	'환자분들의 이야기'     => 'Patient Stories',
	'치아이야기'            => 'Dental Stories',
	'30여년의 발자취'       => 'Our 30-Year Journey',
	'사진 클릭 시 자세한 이야기' => 'Click a photo for the full story',
	'종합 안내서'           => 'Complete Guide',
	'종합안내서'            => 'Complete Guide',
	'자주 묻는 질문'        => 'Frequently Asked Questions',
	'진료 시스템'           => 'Clinical System',

	/* ── 히어로 · 인증 배지 ────────────────────────── */
	'1990년대부터 임플란트를 식립해온 병원' => 'Placing implants since the 1990s',
	'국가지정 구강검진 치과'   => 'Government-Designated Oral Screening Clinic',
	'외국인환자 유치 의료기관' => 'Registered for International Patient Care',
	'미군 및 가족 치료기관'    => 'Care Provider for U.S. Forces and Families',
	'천안시 치아사랑사업 협력병원' => 'Cheonan City Dental Care Program Partner',
	'삼성서울병원 협력병원'    => 'Samsung Medical Center Partner Hospital',
	'대한적십자사 협력병원'    => 'Korean Red Cross Partner Hospital',

	/* ── 지표 ──────────────────────────────────────── */
	'1995년 개원'     => 'Established 1995',
	'전문 진료 영역'  => 'Specialty Areas',
	'통합 진료센터'   => 'Integrated Care Floors',
	'충분한 사전 상담' => 'One-on-One Consultation',

	/* ── 진료시간 ──────────────────────────────────── */
	'평일'       => 'Weekdays',
	'목요일'     => 'Thursday',
	'토요일'     => 'Saturday',
	'일요일 휴진' => 'Closed on Sundays',
	'공휴일 진료 및 휴진 등 변동 사항은 네이버에서 최종 확인해주세요'
		=> 'Please check Naver for holiday hours and schedule changes.',

	/* ── 주차 ──────────────────────────────────────── */
	'본원 지하 기계식 주차장' => 'On-site Underground Parking',
	'주차 후 데스크에 접수 → 무료 등록' => 'Register at the front desk for free parking',
	'SUV·대형차 — 신부 제5공영주차장' => 'SUV / Large Vehicles — Sinbu Public Parking Lot No. 5',
	'천안시외버스터미널'  => 'Cheonan Intercity Bus Terminal',
	'천안고속버스터미널'  => 'Cheonan Express Bus Terminal',
	'천안역·두정역'       => 'Cheonan Stn. · Dujeong Stn.',
	'천안아산역'          => 'Cheonan-Asan Stn.',
	'에서 도보 5분'       => ' — 5 min walk',
	'에서 버스로 약 10분' => ' — about 10 min by bus',
	'에서 버스로 약 25분' => ' — about 25 min by bus',

	/* ── 백과사전 ──────────────────────────────────── */
	'치과사전'   => 'Dental Encyclopedia',
	'치과 백과사전' => 'Dental Encyclopedia',
	'학술 근거'  => 'Evidence-based',

	/* ── 안내 문구 ─────────────────────────────────── */
	'혈압기 · 혈당검사 · 심전도 · 산소포화도 — 전신질환자도 안전한 진료 인프라'
		=> 'Blood pressure, glucose, ECG and oxygen saturation monitoring — safe care for patients with systemic conditions.',
	'분야별 전문 의료진의 협진을 한 곳에서 받으실 수 있습니다.'
		=> 'Receive coordinated care from specialists across every field in one place.',
	'천안 만남로에서 30여년 — 천안·아산 환자분들이 선택해온 이유 4가지로 정리해드립니다.'
		=> 'Over 30 years on Mannam-ro, Cheonan — four reasons patients in Cheonan and Asan choose us.',

	/* ── 푸터 ──────────────────────────────────────── */
	'개인정보처리방침'   => 'Privacy Policy',
	'이용약관'           => 'Terms of Use',
	'이메일 무단수집거부' => 'No Unauthorized Email Collection',
	'대표자'             => 'Representative',
	'개업일'             => 'Established',
	'요양기관번호'       => 'Provider No.',
	'문은수 이사장'      => 'Chairman Moon Eun-soo',

	/* ── 지역 ──────────────────────────────────────── */
	'충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동)'
		=> '52 Mannam-ro, Dongnam-gu, Cheonan-si, Chungcheongnam-do — Moon Tower, Floors 9/10/11/13 (Sinbu-dong)',
	'천안·아산' => 'Cheonan · Asan',
	'천안시'    => 'Cheonan',
	'아산시'    => 'Asan',
	/* ── v3.44.217 · 2차 확장 · 실제 텍스트 노드에서 추출 ── */
	'임플란트'              => 'Implant',
	'천안·아산 임플란트'    => 'Cheonan · Asan Implants',
	'천안·아산 소아치과'    => 'Cheonan · Asan Pediatric Dentistry',
	'전악 임플란트'         => 'Full-Arch Implants',
	'고난도 임플란트'       => 'Complex Implant Cases',
	'매복 사랑니 발치'      => 'Impacted Wisdom Tooth Extraction',
	'상악동 거상술'         => 'Sinus Lift',
	'디지털 가이드 수술'    => 'Digital Guided Surgery',
	'임플란트 + 보철'       => 'Implant + Prosthetics',
	'전악 보철'             => 'Full-Mouth Prosthetics',
	'심미 라미네이트'       => 'Cosmetic Laminate Veneers',
	'소아 교정'             => 'Pediatric Orthodontics',
	'고난도 교정'           => 'Complex Orthodontic Cases',
	'앞니 부분 교정'        => 'Partial Front-Tooth Alignment',
	'재교정'                => 'Orthodontic Retreatment',
	'치수복조술'            => 'Pulp Capping',
	'이갈이 · 이악물기'     => 'Bruxism · Clenching',
	'디지털 정밀'           => 'Digital Precision',
	'멸균 · 감염 관리'      => 'Sterilization · Infection Control',
	'평일 야간 진료'        => 'Weekday Evening Hours',
	'야간 진료 운영'        => 'Evening Hours Available',
	'자체 보철 연구소'      => 'In-house Prosthetic Laboratory',
	'예방치과 개설'         => 'Preventive Dentistry Established',
	'의료진 소개'           => 'Meet Our Doctors',
	'기술력 / 시설'         => 'Technology & Facilities',
	'전화 예약'             => 'Call to Book',
	'상담 예약'             => 'Book a Consultation',
	'카카오톡'              => 'KakaoTalk',
	'한국어'                => 'Korean',
	'전체 소식 →'          => 'All News →',
	'전체 발자취 →'        => 'Full History →',
	'전체 이야기 →'        => 'All Stories →',
	'전체 비급여 진료비'    => 'All Non-covered Fees',
	'교정센터 자세히 보기'  => 'About the Orthodontic Center',
	'🦷 치아이야기'         => '🦷 Dental Stories',
	'🏥 층별 안내'          => '🏥 Floor Guide',
	'📢 문치과병원 소식'    => '📢 News from Moon Dental Hospital',
	'💬 환자분들의 이야기'  => '💬 Patient Stories',
	'임플란트 종합안내서'   => 'Complete Implant Guide',
	'투명교정 종합안내서'   => 'Complete Clear Aligner Guide',
	'최소삭제 라미네이트 종합안내서' => 'Complete Minimal-Prep Laminate Guide',
	'아산 추천 치과'        => 'Dental Care in Asan',
	'천안 추천 치과'        => 'Dental Care in Cheonan',
);
