<?php
/**
 * Template Name: 오시는 길
 * Template Post Type: page
 *
 * 흐름: Hero → 지도 → 3 맵 버튼 → [진료시간 + 주차] 2-col → 3 연락 채널.
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
$map_google = $info['google_map_url'] ?: 'https://maps.app.goo.gl/MNt59kcxeKL92nCU9';
$map_naver  = $naver_map ?: 'https://map.naver.com/p/search/' . $q_short;
$map_kakao  = 'https://map.kakao.com/?q=' . $q_short;

// Naver Map 스크린샷
$map_image = '';
foreach ( array( 'naver-map.png', 'naver-map.jpg', 'naver-map.jpeg', 'naver-map.webp' ) as $f ) {
	if ( file_exists( MOONDENTAL_DIR . '/assets/images/map/' . $f ) ) {
		$map_image = MOONDENTAL_URI . '/assets/images/map/' . $f;
		break;
	}
}

// 오늘 요일 — 진료시간 행 강조용
$today_dow = (int) wp_date( 'w' ); // 0=일, 4=목, 6=토

// 진료시간 — Customizer 값에서 시간 부분만 추출 (extract_time_range 헬퍼)
$time_wd  = function_exists( 'moondental_extract_time_range' )
	? moondental_extract_time_range( $info['hours_wd']  ?? '' )
	: ( $info['hours_wd']  ?? '' );
$time_thu = function_exists( 'moondental_extract_time_range' )
	? moondental_extract_time_range( $info['hours_thu'] ?? '' )
	: ( $info['hours_thu'] ?? '' );
$time_sat = function_exists( 'moondental_extract_time_range' )
	? moondental_extract_time_range( $info['hours_sat'] ?? '' )
	: ( $info['hours_sat'] ?? '' );
$off_text = $info['hours_off'] ?: '휴진';
?>

<section class="md-page-hero md-page-hero--location">
	<div class="md-container">
		<?php $locpage_title = md_content( 'locpage_hero_title', '오시는 길' ); ?>
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸ <span><?php echo esc_html( $locpage_title ); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php echo esc_html( $locpage_title ); ?></h1>
		<p class="md-page-hero__lead md-page-hero__lead--big">
			<a href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" style="color:inherit; border-bottom:1px dashed var(--color-border);">
				<?php echo esc_html( $info['address'] ); ?>
			</a>
		</p>
	</div>
</section>

<!-- ============ 1. 지도 + 3 맵 버튼 ============ -->
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

		<div class="md-mapbtn-grid md-mapbtn-grid--top">
			<a class="md-mapbtn md-mapbtn--naver" href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" data-track="cta-location-map-naver">
				<span class="md-mapbtn__logo" aria-hidden="true">N</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name"><?php echo esc_html( md_content( 'flocation_btn_naver', '네이버 지도' ) ); ?></span>
					<span class="md-mapbtn__sub"><?php echo esc_html( md_content( 'flocation_btn_naver_sub', '길찾기 · 대중교통' ) ); ?></span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--kakao" href="<?php echo esc_url( $map_kakao ); ?>" target="_blank" rel="noopener" data-track="cta-location-map-kakao">
				<span class="md-mapbtn__logo" aria-hidden="true">k</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name"><?php echo esc_html( md_content( 'flocation_btn_kakao', '카카오맵' ) ); ?></span>
					<span class="md-mapbtn__sub"><?php echo esc_html( md_content( 'flocation_btn_kakao_sub', '길찾기 · 로드뷰' ) ); ?></span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--google" href="<?php echo esc_url( $map_google ); ?>" target="_blank" rel="noopener" data-track="cta-location-map-google">
				<span class="md-mapbtn__logo" aria-hidden="true">G</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name"><?php echo esc_html( md_content( 'flocation_btn_google', 'Google Maps' ) ); ?></span>
					<span class="md-mapbtn__sub"><?php echo esc_html( md_content( 'flocation_btn_google_sub', 'Directions · Street View' ) ); ?></span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
		</div>
	</div>
</section>

<!-- ============ 2. 진료시간 + 주차 안내 (2-col 그리드) ============ -->
<section class="md-section" id="hours">
	<div class="md-container">
		<div class="md-info-pair">

			<!-- 진료시간 카드 -->
			<aside class="md-hours">
				<header class="md-hours__head">
					<span class="md-hours__badge"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_hours_badge', '🕐 진료시간' ) : '🕐 진료시간' ); ?></span>
					<h3 class="md-hours__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_hours_title', '진료 가능 시간' ) : '진료 가능 시간' ); ?></h3>
				</header>
				<ul class="md-hours__list">
					<li<?php echo in_array( $today_dow, array(1,2,3,5), true ) ? ' class="is-today"' : ''; ?>>
						<span class="md-hours__day"><?php echo esc_html( md_content( 'loc_day_weekday', '평일 (월·화·수·금)' ) ); ?></span>
						<span class="md-hours__time"><?php echo esc_html( $time_wd ); ?></span>
					</li>
					<li<?php echo $today_dow === 4 ? ' class="is-today"' : ''; ?>>
						<span class="md-hours__day"><?php echo esc_html( md_content( 'loc_day_thu', '목요일' ) ); ?></span>
						<span class="md-hours__time"><?php echo esc_html( $time_thu ); ?></span>
					</li>
					<li<?php echo $today_dow === 6 ? ' class="is-today"' : ''; ?>>
						<span class="md-hours__day"><?php echo esc_html( md_content( 'loc_day_sat', '토요일' ) ); ?></span>
						<span class="md-hours__time"><?php echo esc_html( $time_sat ); ?></span>
					</li>
					<li class="md-hours__off<?php echo $today_dow === 0 ? ' is-today' : ''; ?>">
						<span class="md-hours__day"><?php echo esc_html( md_content( 'loc_day_sun', '일요일 · 공휴일' ) ); ?></span>
						<span class="md-hours__time"><?php echo esc_html( md_content( 'loc_day_closed', '휴진' ) ); ?></span>
					</li>
				</ul>
				<p class="md-hours__note">
					<?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_hours_note', '평일 점심시간 없이 진료 · 야간진료 운영' ) : '평일 점심시간 없이 진료 · 야간진료 운영' ); ?>
				</p>
			</aside>

			<!-- 주차 안내 카드 -->
			<aside class="md-park md-park--compact">
				<?php
				// 헬퍼: Customizer 텍스트 → esc_html → md_autolink_addresses
				$mdf = function( $key, $default ) {
					$raw = function_exists( 'md_content' ) ? md_content( $key, $default ) : $default;
					$out = esc_html( $raw );
					return function_exists( 'md_autolink_addresses' ) ? md_autolink_addresses( $out ) : $out;
				};
				?>
				<header class="md-park__head">
					<span class="md-park__badge"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_park_badge', '🅿️ 주차 안내' ) : '🅿️ 주차 안내' ); ?></span>
					<?php /* v3.44.4 · '본원 지하 기계식 무료' 타이틀 제거 (사용자 요청) · 배지·01·02 카드 유지 */ ?>
				</header>
				<ul class="md-park__list">
					<li>
						<span class="md-park__num">01</span>
						<div>
							<strong><?php echo wp_kses_post( $mdf( 'loc_park_1_title', '본원 지하 기계식 주차장' ) ); ?></strong>
							<span><?php echo wp_kses_post( $mdf( 'loc_park_1_desc', '진료 시간 동안 무료 이용' ) ); ?></span>
						</div>
					</li>
					<li>
						<span class="md-park__num">02</span>
						<div>
							<strong><?php echo wp_kses_post( $mdf( 'loc_park_2_title', 'SUV·대형차 — 신부 제5공영주차장' ) ); ?></strong>
							<span><?php echo wp_kses_post( $mdf( 'loc_park_2_desc', '인근 신부 제5공영주차장(동남구 먹거리1길 10) 주차 후 데스크에 접수 → 무료 등록' ) ); ?></span>
						</div>
					</li>
				</ul>
				<?php
				$park_walk  = function_exists( 'md_content' ) ? md_content( 'loc_park_walk',  '🚌 천안종합·고속버스터미널에서 도보 약 5분' ) : '🚌 천안종합·고속버스터미널에서 도보 약 5분';
				$park_train = function_exists( 'md_content' ) ? md_content( 'loc_park_train', '🚆 천안역에서 버스로 약 10분' )                : '🚆 천안역에서 버스로 약 10분';
				if ( $park_walk || $park_train ) :
				?>
				<p class="md-park__walk">
					<?php if ( $park_walk ) : ?>
						<span><?php echo esc_html( $park_walk ); ?></span>
					<?php endif; ?>
					<?php if ( $park_train ) : ?>
						<span><?php echo esc_html( $park_train ); ?></span>
					<?php endif; ?>
				</p>
				<?php endif; ?>
			</aside>

		</div>
	</div>
</section>

<!-- ============ 3. 각 지역에서 문치과병원까지 (28개 지역 SEO 그리드) ============ -->
<?php if ( function_exists( 'moondental_get_regions_by_province' ) ) : ?>
<section class="md-section md-section--surface" aria-label="지역별 오시는 길">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'locpage_region_eyebrow', '🌐 지역별 오시는 길' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'locpage_region_title', '각 지역에서 문치과병원까지' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo nl2br( esc_html( md_content( 'locpage_region_lead', "충남·충북·세종·대전·경기 중부권 28개 지역별 상세 교통 안내.\n지역명을 클릭하시면 해당 지역에서 천안 만남로까지의 상세 경로와 진료 안내를 보실 수 있습니다." ) ) ); ?></p>
		</header>

		<?php foreach ( moondental_get_regions_by_province() as $prov => $list ) :
			if ( empty( $list ) ) continue;
			$prov_emoji = array(
				'천안·아산 시내' => '🏙️',
				'충남' => '🌊', '충북' => '🏔️', '세종' => '🏛️',
				'대전' => '🌆', '경기' => '🌇',
			);
			$emoji = $prov_emoji[ $prov ] ?? '📍'; ?>
			<div class="md-region-province">
				<h3 class="md-region-province__title">
					<span aria-hidden="true"><?php echo esc_html( $emoji ); ?></span>
					<?php echo esc_html( $prov ); ?>
					<small>(<?php echo count( $list ); ?>개 지역)</small>
				</h3>
				<div class="md-region-grid">
					<?php foreach ( $list as $r ) :
						$icon = ! empty( $r['icon'] ) ? $r['icon'] : '🚗'; ?>
						<a class="md-region-pill" href="<?php echo esc_url( home_url( '/오시는-길/' . $r['slug'] . '/' ) ); ?>" data-track="cta-region-<?php echo esc_attr( $r['slug'] ); ?>">
							<span class="md-region-pill__icon" aria-hidden="true"><?php echo $icon; ?></span>
							<span class="md-region-pill__name"><?php echo esc_html( $r['name'] ); ?></span>
							<span class="md-region-pill__time"><?php echo esc_html( ! empty( $r['duration_label'] ) ? $r['duration_label'] : ( $r['duration_min'] . '분' ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<p class="md-region-note">
			<?php echo esc_html( md_content( 'locpage_region_note', 'ⓘ 이동 시간은 자동차 기준 대략적인 값입니다. 실제 교통 상황에 따라 달라질 수 있습니다.' ) ); ?>
		</p>
	</div>
</section>
<?php endif; ?>

<?php /* v3.37.3 · 오시는 길 페이지 하단 CTA · 통일된 section-cta 사용
 *  기존 md-channel-grid 3카드는 하단 공용 CTA 배너와 중복되어 제거 */ ?>
<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
