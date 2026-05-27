<?php
/**
 * Template Name: 상담예약 페이지
 * Template Post Type: page
 *
 * 외부 예약 채널(네이버/전화/카톡) + 3-맵 길찾기 + 오시는 길 통합.
 * 사이트 자체 예약 폼은 더 이상 운영하지 않는다 (사용자 정책).
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$naver_book = $info['naver_place'] ?? '';
$naver_map  = $info['naver_map_url'] ?? '';
$kakao_url  = $info['kakao_url'] ?? '';
$addr_full  = $info['address'];
$addr_road  = $info['address_road'] ?: $info['address'];

// 3-Map URLs — 검색어 기반(좌표 미공개 환경에서도 동작)
$q_full  = rawurlencode( '한아의료재단 문치과병원 천안 만남로 52' );
$q_short = rawurlencode( '한아의료재단 문치과병원' );

$map_google = 'https://www.google.com/maps/search/?api=1&query=' . $q_full;
$map_naver  = $naver_map ?: 'https://map.naver.com/p/search/' . $q_short;
$map_kakao  = 'https://map.kakao.com/?q=' . $q_short;
?>

<section class="md-page-hero md-page-hero--reservation">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>예약·상담 / 오시는 길</span>
		</nav>
		<span class="md-page-hero__eyebrow">RESERVATION</span>
		<h1 class="md-page-hero__title">예약·상담 그리고 오시는 길</h1>
		<p class="md-page-hero__lead">
			네이버 예약 · 전화 · 카카오톡 — 가장 편하신 방법으로 예약해주세요.<br>
			아래에서 진료시간·찾아오시는 길도 함께 확인하실 수 있습니다.
		</p>
	</div>
</section>

<!-- ============ 1. 예약 채널 ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">예약 채널</span>
			<h2 class="md-section-head__title">편하신 방법으로 예약해주세요</h2>
			<p class="md-section-head__lead">
				문치과병원은 <strong>네이버 예약</strong>으로 24시간 자동 예약을 받고 있으며, 전화·카카오톡 상담도 함께 운영합니다.
			</p>
		</header>

		<div class="md-channel-grid">
			<?php if ( $naver_book ) : ?>
			<a class="md-channel-card md-channel-card--primary" href="<?php echo esc_url( $naver_book ); ?>" target="_blank" rel="noopener" data-track="cta-reservation-naver">
				<span class="md-channel-card__icon" aria-hidden="true">🟢</span>
				<span class="md-channel-card__title">네이버 예약</span>
				<span class="md-channel-card__desc">24시간 자동 예약 · 가장 빠른 일정 확인</span>
				<span class="md-channel-card__cta">예약하러 가기 →</span>
			</a>
			<?php endif; ?>

			<a class="md-channel-card" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-reservation-call">
				<span class="md-channel-card__icon" aria-hidden="true">📞</span>
				<span class="md-channel-card__title">전화 예약</span>
				<span class="md-channel-card__desc"><?php echo esc_html( $info['phone'] ); ?> · 진료시간 내 응답</span>
				<span class="md-channel-card__cta">바로 전화 →</span>
			</a>

			<?php if ( $kakao_url ) : ?>
			<a class="md-channel-card" href="<?php echo esc_url( $kakao_url ); ?>" target="_blank" rel="noopener" data-track="cta-reservation-kakao">
				<span class="md-channel-card__icon" aria-hidden="true">💬</span>
				<span class="md-channel-card__title">카카오톡 상담</span>
				<span class="md-channel-card__desc">24시간 메시지 · 진료시간 내 답변</span>
				<span class="md-channel-card__cta">카카오톡 채널 →</span>
			</a>
			<?php endif; ?>
		</div>

		<p class="md-channel-grid__hint">
			🕐 진료시간: 평일(월·화·수·금) 09:00–20:30 · 목 09:00–18:00 · 토 09:00–14:00 · 일/공휴일 휴진
		</p>
	</div>
</section>

<!-- ============ 2. 오시는 길 — 3-맵 ============ -->
<section class="md-section md-section--surface" id="location">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Location</span>
			<h2 class="md-section-head__title">오시는 길</h2>
			<p class="md-section-head__lead">
				<a href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" style="color:inherit; border-bottom:1px dashed var(--color-border);">
					<?php echo esc_html( $addr_full ); ?>
				</a>
			</p>
		</header>

		<div class="md-mapbtn-grid">
			<a class="md-mapbtn md-mapbtn--naver" href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" data-track="cta-map-naver">
				<span class="md-mapbtn__logo" aria-hidden="true">N</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">네이버 지도</span>
					<span class="md-mapbtn__sub">길찾기 · 대중교통</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--kakao" href="<?php echo esc_url( $map_kakao ); ?>" target="_blank" rel="noopener" data-track="cta-map-kakao">
				<span class="md-mapbtn__logo" aria-hidden="true">k</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">카카오맵</span>
					<span class="md-mapbtn__sub">길찾기 · 로드뷰</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--google" href="<?php echo esc_url( $map_google ); ?>" target="_blank" rel="noopener" data-track="cta-map-google">
				<span class="md-mapbtn__logo" aria-hidden="true">G</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">Google Maps</span>
					<span class="md-mapbtn__sub">Directions · Street View</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
		</div>

		<div class="md-location-grid">
			<div class="md-location-card">
				<h3>🚗 자가용</h3>
				<ul>
					<li>경부고속도로 천안IC에서 약 10분</li>
					<li>천안종합버스터미널·천안고속버스터미널 일대</li>
				</ul>
			</div>
			<div class="md-location-card">
				<h3>🅿️ 주차</h3>
				<ul>
					<li><strong>본원 지하 기계식 주차장</strong> 무료</li>
					<li>SUV는 <strong>신부 제5공영주차장</strong>(동남구 먹거리1길 10) — 무료 주차 등록 도와드림</li>
				</ul>
			</div>
			<div class="md-location-card">
				<h3>🚌 대중교통</h3>
				<ul>
					<li>천안종합버스터미널·천안고속버스터미널에서 도보 5분</li>
					<li>신부동 일대 시내버스 다수 정차</li>
				</ul>
			</div>
			<div class="md-location-card">
				<h3>🚆 KTX / SRT</h3>
				<ul>
					<li>천안아산역에서 시내버스·택시 약 15분</li>
					<li>천안역에서 시내버스·택시 약 10분</li>
				</ul>
			</div>
		</div>

		<div class="md-location-floors">
			<h3 class="md-location-floors__title">통합 진료센터 안내</h3>
			<ul>
				<li><strong>9F</strong> 종합진료센터 · 진단 · 보존 · 보철</li>
				<li><strong>10F</strong> 임플란트센터 · 수술 · 디지털 가이드</li>
				<li><strong>11F</strong> 교정과 · 투명교정 · 일반교정</li>
				<li><strong>12~13F</strong> 부속 시설 · 의료재단 사무국</li>
			</ul>
		</div>
	</div>
</section>

<!-- ============ 3. FAQ ============ -->
<section class="md-section" id="reservation-faq">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">FAQ</span>
			<h2 class="md-section-head__title">예약 관련 자주 묻는 질문</h2>
		</header>

		<div class="md-faq">
			<details class="md-faq__item" open>
				<summary>당일 예약도 가능한가요?</summary>
				<p>네, 당일 예약도 가능합니다. 다만 예약 상황에 따라 대기 시간이 발생할 수 있으니 가급적 전화(<a href="tel:<?php echo esc_attr( $phone_link ); ?>"><?php echo esc_html( $info['phone'] ); ?></a>)로 먼저 확인 후 방문해주시기 바랍니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>예약 변경이나 취소는 어떻게 하나요?</summary>
				<p>네이버 예약은 예약 페이지에서 직접 변경·취소가 가능하며, 그 외에는 전화 또는 카카오톡 채널로 문의해주세요. 예약일 전날까지 변경·취소가 가능합니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>초진 시 준비물이 있나요?</summary>
				<p>신분증(또는 건강보험증)을 지참해주세요. 복용 중인 약이 있다면 약 정보를 함께 알려주시면 진료에 도움이 됩니다. 타원 X-ray 파일(USB·이메일)이 있으면 진단 시간이 단축됩니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>주차가 가능한가요?</summary>
				<p>본원 지하 기계식 주차장을 무료로 이용하실 수 있습니다. 기계식 주차가 어려운 SUV 차량은 인근 <strong>신부 제5공영주차장</strong>(동남구 먹거리1길 10)에 주차하고 방문해주시면 무료 주차 등록을 도와드립니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>전신질환(고혈압·당뇨·심장질환)이 있어도 진료 가능한가요?</summary>
				<p>네, 안심하셔도 됩니다. 문치과병원은 혈압기·당검사·심전도·산소포화도 측정 장비를 상시 보유하고 있으며, 복용 중인 약(혈전용해제·골다공증 주사 등)을 사전에 체크해 안전하게 진료합니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>비용·견적은 미리 알 수 있나요?</summary>
				<p>임플란트·교정·심미치료 등 비급여 진료는 환자분의 구강 상태(CT·X-ray)를 보고 정확한 견적을 산정합니다. 초진 상담 시 옵션별 비용·기간을 모두 안내드리며, 시작 전에 충분히 검토하실 수 있도록 합니다.</p>
			</details>
		</div>
	</div>
</section>

<?php
get_footer();
