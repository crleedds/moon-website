<?php
/**
 * Template Name: 오시는 길
 * Template Post Type: page
 *
 * 흐름: Hero(주소 강조) → 지도(여백 최소) → 3 맵 버튼 → 주차 → 3 연락 채널.
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$kakao_url  = $info['kakao_url'] ?? '';
$naver_book = $info['naver_place'] ?? '';
$naver_map  = $info['naver_map_url'] ?? '';

$q_full  = rawurlencode( '한아의료재단 문치과병원 천안 만남로 52' );
$q_short = rawurlencode( '한아의료재단 문치과병원' );
$map_google = 'https://www.google.com/maps/search/?api=1&query=' . $q_full;
$map_naver  = $naver_map ?: 'https://map.naver.com/p/search/' . $q_short;
$map_kakao  = 'https://map.kakao.com/?q=' . $q_short;

// Naver Map 스크린샷 (있으면 사용, 없으면 그라데이션 카드)
$map_image = '';
foreach ( array( 'naver-map.png', 'naver-map.jpg', 'naver-map.jpeg', 'naver-map.webp' ) as $f ) {
	if ( file_exists( MOONDENTAL_DIR . '/assets/images/map/' . $f ) ) {
		$map_image = MOONDENTAL_URI . '/assets/images/map/' . $f;
		break;
	}
}
?>

<section class="md-page-hero md-page-hero--location">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>오시는 길</span>
		</nav>
		<h1 class="md-page-hero__title">오시는 길</h1>
		<p class="md-page-hero__lead md-page-hero__lead--big">
			<a href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" style="color:inherit; border-bottom:1px dashed var(--color-border);">
				<?php echo esc_html( $info['address'] ); ?>
			</a>
		</p>
	</div>
</section>

<!-- ============ 1. 네이버 지도 (직사각형) — 히어로 바로 아래, 여백 최소 ============ -->
<section class="md-section md-section--tight">
	<div class="md-container">
		<a class="md-locmap<?php echo $map_image ? ' md-locmap--has-image' : ''; ?>"
		   href="<?php echo esc_url( $map_naver ); ?>"
		   target="_blank" rel="noopener"
		   data-track="cta-location-mainmap"
		   aria-label="네이버 지도에서 문치과병원 위치 보기"
		   <?php echo $map_image ? 'style="background-image:url(' . esc_url( $map_image ) . ');"' : ''; ?>>
			<?php if ( ! $map_image ) : ?>
				<div class="md-locmap__pattern" aria-hidden="true"></div>
			<?php endif; ?>
		</a>

		<!-- 지도 사진 바로 아래 3 맵 버튼 -->
		<div class="md-mapbtn-grid" style="margin-top: clamp(16px, 2vw, 24px);">
			<a class="md-mapbtn md-mapbtn--naver" href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" data-track="cta-location-map-naver">
				<span class="md-mapbtn__logo" aria-hidden="true">N</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">네이버 지도</span>
					<span class="md-mapbtn__sub">길찾기 · 대중교통</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--kakao" href="<?php echo esc_url( $map_kakao ); ?>" target="_blank" rel="noopener" data-track="cta-location-map-kakao">
				<span class="md-mapbtn__logo" aria-hidden="true">k</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">카카오맵</span>
					<span class="md-mapbtn__sub">길찾기 · 로드뷰</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--google" href="<?php echo esc_url( $map_google ); ?>" target="_blank" rel="noopener" data-track="cta-location-map-google">
				<span class="md-mapbtn__logo" aria-hidden="true">G</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">Google Maps</span>
					<span class="md-mapbtn__sub">Directions · Street View</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
		</div>
	</div>
</section>

<!-- ============ 2. 주차·도보 안내 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<div class="md-park">
			<div class="md-park__head">
				<span class="md-park__badge">🅿️ 주차 안내</span>
				<h3 class="md-park__title">본원 지하 기계식 주차장 <strong>무료</strong></h3>
				<p class="md-park__lead">방문 시 데스크에 주차권을 제출하시면 등록해드립니다.</p>
			</div>

			<ul class="md-park__list">
				<li>
					<span class="md-park__num">01</span>
					<div>
						<strong>본원 지하 기계식 주차장</strong>
						<span>주차요원 안내에 따라 입차 — 진료 시간 동안 무료 이용 가능합니다.</span>
					</div>
				</li>
				<li>
					<span class="md-park__num">02</span>
					<div>
						<strong>SUV·대형차 — 신부 제5공영주차장</strong>
						<span>기계식 주차가 어려우신 차량은 인근 <strong>신부 제5공영주차장</strong>(충남 천안시 동남구 먹거리1길 10)에 주차하신 후, 데스크에 주차권을 제출해주세요. <strong>무료 주차 등록</strong>을 도와드립니다.</span>
					</div>
				</li>
			</ul>

			<p class="md-park__walk">
				🚌 <strong>천안종합버스터미널 · 천안고속버스터미널</strong>에서 도보 약 5분 거리.
			</p>
		</div>
	</div>
</section>

<!-- ============ 3. 편하신 방법으로 연락주세요 (전화/카톡/네이버 예약) ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head" style="margin-bottom: clamp(24px, 3vw, 32px);">
			<span class="md-section-head__eyebrow">예약·문의</span>
			<h2 class="md-section-head__title">편하신 방법으로 연락주세요</h2>
		</header>

		<div class="md-channel-grid">
			<a class="md-channel-card" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-location-call">
				<span class="md-channel-card__icon" aria-hidden="true">📞</span>
				<span class="md-channel-card__title">전화 상담</span>
				<span class="md-channel-card__desc"><?php echo esc_html( $info['phone'] ); ?> · 진료시간 내 응답</span>
				<span class="md-channel-card__cta">바로 전화 →</span>
			</a>

			<?php if ( $kakao_url ) : ?>
			<a class="md-channel-card" href="<?php echo esc_url( $kakao_url ); ?>" target="_blank" rel="noopener" data-track="cta-location-kakao">
				<span class="md-channel-card__icon" aria-hidden="true">💬</span>
				<span class="md-channel-card__title">카카오톡 상담</span>
				<span class="md-channel-card__desc">24시간 메시지 · 진료시간 내 답변</span>
				<span class="md-channel-card__cta">카카오톡 채널 →</span>
			</a>
			<?php endif; ?>

			<?php if ( $naver_book ) : ?>
			<a class="md-channel-card md-channel-card--primary" href="<?php echo esc_url( $naver_book ); ?>" target="_blank" rel="noopener" data-track="cta-location-naver-book">
				<span class="md-channel-card__icon" aria-hidden="true">🟢</span>
				<span class="md-channel-card__title">네이버 예약</span>
				<span class="md-channel-card__desc">24시간 자동 예약 · 일정 즉시 확정</span>
				<span class="md-channel-card__cta">예약하러 가기 →</span>
			</a>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
get_footer();
