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
		<?php /* v3.99.1 · 주소 + 복사 버튼 + 전화·이메일 (이메일은 클릭 복사) */
		$loc_email = $info['email'] ?: 'moondental1995@naver.com'; ?>
		<div class="md-page-hero__lead md-page-hero__lead--big md-loc-addr">
			<a href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" style="color:inherit; border-bottom:1px dashed var(--color-border);"><?php echo esc_html( $info['address'] ); ?></a>
			<button type="button" class="md-copybtn" data-copy="<?php echo esc_attr( $info['address'] ); ?>" data-track="cta-locpage-copy-addr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><span><?php echo esc_html( md_content( 'loc_copy_addr', '주소 복사' ) ); ?></span><span hidden data-copy-msg><?php echo esc_html( md_content( 'loc_copied_addr', '주소가 복사되었습니다' ) ); ?></span></button>
		</div>
		<div class="md-loc-contact">
			<a class="md-loc-contact__item" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-locpage-call"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.7.7a2 2 0 0 1 1.8 2z"/></svg><span><?php echo esc_html( $info['phone'] ); ?></span></a>
			<button type="button" class="md-loc-contact__item" data-copy="<?php echo esc_attr( $loc_email ); ?>" data-track="cta-locpage-copy-email" title="<?php echo esc_attr( md_content( 'loc_copy_email', '이메일 복사' ) ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="M3 6.5l9 6 9-6"/></svg><span><?php echo esc_html( $loc_email ); ?></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><span hidden data-copy-msg><?php echo esc_html( md_content( 'loc_copied_email', '이메일 주소가 복사되었습니다' ) ); ?></span></button>
		</div>
	</div>
</section>

<!-- ============ 1. 지도 + 3 맵 버튼 ============ -->
<section class="md-section md-section--tight">
	<div class="md-container">
		<?php /* v3.98.3 · 상단 지도 = 구글 지도 임베드 (API 키·결제 불필요). Customizer URL 을 비우면 예전 네이버 이미지 타일 */
		$gmap_src = function_exists( 'md_content' ) ? md_content( 'loc_gmap_embed_url', 'https://www.google.com/maps?q=%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90%20%EC%B2%9C%EC%95%88%20%EB%A7%8C%EB%82%A8%EB%A1%9C%2052&z=16&output=embed&hl=ko' ) : '';
		if ( $gmap_src ) : ?>
		<div class="md-locmap md-locmap--gmap">
			<iframe src="<?php echo esc_url( $gmap_src ); ?>" title="<?php echo esc_attr( md_content( 'loc_gmap_iframe_title', '문치과병원 구글 지도' ) ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
		</div>
		<?php else : ?>
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
		<?php endif; ?>

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

<?php /* v3.99 · 주변 랜드마크 · 편의 시설 — Customizer 「오시는 길」에서 편집 (한 줄에 하나 · 아이콘|이름|소요시간|네이버 검색어 / 아이콘|이름|설명) */
$md_lines  = function ( $raw ) { $out = array(); foreach ( preg_split( '/\r?\n/', (string) $raw ) as $l ) { $l = trim( $l ); if ( $l === '' ) continue; $out[] = array_map( 'trim', explode( '|', $l ) ); } return $out; };
$landmarks = $md_lines( md_content( 'loc_landmarks', "🚌|천안종합터미널 (고속·시외)|도보 5분|천안종합버스터미널\n🏬|신세계백화점 천안아산점|도보 5분|신세계백화점 천안아산점\n🌳|신부문화공원|도보 3분|신부문화공원\n🚆|천안역|버스 10분|천안역\n🚄|천안아산역 (KTX)|버스 25분|천안아산역" ) );
$amenities = $md_lines( md_content( 'loc_amenities', "🚗|무료 주차|건물 지하 기계식 주차장 · 진료 시간 중 무료\n🏢|엘리베이터|9·10·11·13층 진료 · 엘리베이터로 이동\n🌙|야간진료|월·화·수·금 20:30까지 진료\n♿|휠체어 접근|건물 입구·엘리베이터·진료실 휠체어 이동 가능
📶|무료 Wi-Fi|대기실 전체 무료 와이파이
🌐|외국어 안내|영어·러시아어·몽골어·베트남어·중국어 통역\n🦷|원내 기공실|보철물 색 맞춤·즉시 수정 (13층)\n👶|소아치과|어린이 전용 진료 공간 (11층)\n💬|카카오톡·네이버 예약|24시간 예약 접수" ) );
if ( $landmarks ) : ?>
<section class="md-section md-section--tight" id="landmarks">
	<div class="md-container">
		<header class="md-locx__head">
			<span class="md-locx__eyebrow"><span aria-hidden="true">📍</span> <span><?php echo esc_html( md_content( 'loc_lm_eyebrow', '주변 정보' ) ); ?></span></span>
			<h2 class="md-locx__title"><?php echo esc_html( md_content( 'loc_lm_title', '주변 랜드마크' ) ); ?></h2>
			<p class="md-locx__sub"><?php echo esc_html( md_content( 'loc_lm_sub', '찾아오실 때 참고하세요 · 누르면 네이버 지도로 이동' ) ); ?></p>
		</header>
		<div class="md-lm-grid">
		<?php foreach ( $landmarks as $lm ) : if ( count( $lm ) < 2 ) continue; $q = ! empty( $lm[3] ) ? $lm[3] : $lm[1]; ?>
			<a class="md-lm" href="https://map.naver.com/p/search/<?php echo rawurlencode( $q ); ?>" target="_blank" rel="noopener" data-track="cta-locpage-landmark">
				<span class="md-lm__icon" aria-hidden="true"><?php echo esc_html( $lm[0] ); ?></span>
				<span class="md-lm__name"><?php echo esc_html( $lm[1] ); ?></span>
				<?php if ( ! empty( $lm[2] ) ) : ?><span class="md-lm__time"><?php echo esc_html( $lm[2] ); ?></span><?php endif; ?>
			</a>
		<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; if ( $amenities ) : ?>
<section class="md-section md-section--surface" id="amenities">
	<div class="md-container">
		<header class="md-locx__head">
			<span class="md-locx__eyebrow"><span aria-hidden="true">🏥</span> <span><?php echo esc_html( md_content( 'loc_amen_eyebrow', '시설 안내' ) ); ?></span></span>
			<h2 class="md-locx__title"><?php echo esc_html( md_content( 'loc_amen_title', '편의 시설' ) ); ?></h2>
			<p class="md-locx__sub"><?php echo esc_html( md_content( 'loc_amen_sub', '편안한 방문을 위해' ) ); ?></p>
		</header>
		<div class="md-amen-grid">
		<?php foreach ( $amenities as $am ) : if ( count( $am ) < 2 ) continue; ?>
			<div class="md-amen">
				<div class="md-amen__icon" aria-hidden="true"><?php echo esc_html( $am[0] ); ?></div>
				<div class="md-amen__name"><?php echo esc_html( $am[1] ); ?></div>
				<?php if ( ! empty( $am[2] ) ) : ?><div class="md-amen__desc"><?php echo esc_html( $am[2] ); ?></div><?php endif; ?>
			</div>
		<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ============ 2. 진료시간 + 층별 안내 + 주차 안내 (3-col 그리드 · v3.44.167) ============ -->
<section class="md-section" id="hours">
	<div class="md-container">
		<div class="md-info-pair md-info-pair--3col">

			<!-- 진료시간 카드 · v3.44.11 · 네이버 플레이스 링크 -->
			<?php
			$hours_naver_url = $info['naver_map_url'] ?? '';
			$_hours_open  = $hours_naver_url
				? '<a class="md-hours md-hours--link" href="' . esc_url( $hours_naver_url ) . '" target="_blank" rel="noopener" data-track="cta-locpage-hours" aria-label="' . esc_attr( md_content( 'loc_hours_aria', '네이버 플레이스에서 최신 진료시간 확인하기' ) ) . '">'
				: '<aside class="md-hours">';
			$_hours_close = $hours_naver_url ? '</a>' : '</aside>';
			?>
			<?php echo $_hours_open; ?>
				<header class="md-hours__head">
					<span class="md-hours__badge"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_hours_badge', '🕐 진료시간' ) : '🕐 진료시간' ); ?></span>
					<?php /* v3.44.15 · '진료 가능 시간' 제목 제거 (사용자 요청) */ ?>
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
						<span class="md-hours__day"><?php echo esc_html( md_content( 'loc_day_sun', '일요일' ) ); ?></span>
						<span class="md-hours__time"><?php echo esc_html( md_content( 'loc_day_closed', '휴진' ) ); ?></span>
					</li>
				</ul>
				<?php /* v3.44.15 · '평일 점심시간 없이 진료 · 야간진료 운영' 하단 안내 제거 (사용자 요청) */ ?>
				<?php
				$_hours_note = md_content( 'loc_hours_naver_note', '🔔 공휴일 진료 및 휴진 등 변동 사항은 네이버에서 최종 확인해주세요' );
				if ( $_hours_note ) :
				?>
					<p class="md-hours__note-naver"><?php echo esc_html( $_hours_note ); ?></p>
				<?php endif; ?>
			<?php echo $_hours_close; ?>

			<!-- FLOOR-GUIDE-INJECTION-CHECK-V195 · 이 텍스트가 view-source 에 있는지 확인 -->
			<aside class="md-floor-guide md-floor-guide--card" aria-label="층별 안내" data-md-version="v3.44.195" style="display:block !important;visibility:visible !important;opacity:1 !important;">
				<header class="md-hours__head">
					<span class="md-hours__badge">🏥 층별 안내</span>
				</header>
				<?php /* v3.85 · 하드코딩 제거 · moondental_floor_guide_data() 단일 진실원에서 렌더 (푸터·사이드바와 동일 데이터) */ ?>
				<ul class="md-floor-guide__list">
					<?php foreach ( ( function_exists( 'moondental_floor_guide_data' ) ? moondental_floor_guide_data() : array() ) as $_fl ) : ?>
					<li class="md-floor-guide__row">
						<span class="md-floor-guide__floor"><?php echo esc_html( $_fl['floor'] ); ?></span>
						<span class="md-floor-guide__centers">
							<?php
							$_fp = array();
							foreach ( $_fl['centers'] as $_c ) {
								$_nm = esc_html( $_c['name'] );
								$_cc = ! empty( $_c['center'] ) ? ' md-center-color--' . $_c['center'] : '';
								if ( ! empty( $_c['center'] ) && ! empty( $_c['slug'] ) ) {
									$_u = ( $_c['slug'] === '스마일디자인센터' ) ? home_url( '/스마일디자인센터/' ) : home_url( '/' . $_c['slug'] . '/' );
									$_fp[] = '<a class="md-floor-guide__center md-floor-guide__center--link md-floor-guide__center--highlight' . $_cc . '" href="' . esc_url( $_u ) . '">' . $_nm . '</a>';
								} else {
									$_fp[] = '<span class="md-floor-guide__center">' . $_nm . '</span>';
								}
							}
							echo implode( "\n\t\t\t\t\t\t\t" . '<span class="md-floor-guide__sep" aria-hidden="true">·</span>' . "\n\t\t\t\t\t\t\t", $_fp );
							?>
						</span>
					</li>
					<?php endforeach; ?>
				</ul>
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
					<span class="md-park__badge"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_park_badge', '🅿️ 무료 주차 안내' ) : '🅿️ 무료 주차 안내' ); ?></span>
					<?php /* v3.44.4 · '병원 지하 기계식 무료' 타이틀 제거 (사용자 요청) · 배지·01·02 카드 유지 */ ?>
				</header>
				<ul class="md-park__list">
					<li>
						<span class="md-park__num">01</span>
						<div>
							<strong><?php echo wp_kses_post( $mdf( 'loc_park_1_title', '병원 지하 기계식 주차장 (SUV 불가)' ) ); ?></strong>
							<span><?php echo wp_kses_post( $mdf( 'loc_park_1_desc', '천안시 동남구 만남로 52 문타워' ) ); ?></span>
						</div>
					</li>
					<li>
						<span class="md-park__num">02</span>
						<div>
							<strong><?php echo wp_kses_post( $mdf( 'loc_park_2_title', '신부 제5공영주차장 (SUV 가능)' ) ); ?></strong>
							<span><?php echo wp_kses_post( $mdf( 'loc_park_2_desc', '천안시 동남구 먹거리1길 10 (도보 5분)' ) ); ?></span>
						</div>
					</li>
				</ul>
				<?php
				/* v3.44.232 · 푸터와 같은 안내문을 이 카드에도 노출.
				 * loc_park_lead 는 Customizer 에 정의만 되어 있고 어디서도 출력되지 않았다. */
				$park_lead = function_exists( 'md_content' )
					? md_content( 'loc_park_lead', '🎫 주차 후 병원 접수처에서 주차도장/주차권을 받아가세요' )
					: '🎫 주차 후 병원 접수처에서 주차도장/주차권을 받아가세요';
				if ( $park_lead ) : ?>
					<p class="md-park__lead md-park__lead--note"><?php echo esc_html( $park_lead ); ?></p>
				<?php endif; ?>
				<?php
				$park_walk  = function_exists( 'md_content' ) ? md_content( 'loc_park_walk',  "🚌 천안시외버스터미널에서 도보 5분\n🚌 천안고속버스터미널에서 도보 5분" ) : "🚌 천안시외버스터미널에서 도보 5분\n🚌 천안고속버스터미널에서 도보 5분";
				$park_train = function_exists( 'md_content' ) ? md_content( 'loc_park_train', '🚆 천안역·두정역에서 버스로 약 10분' )                : '🚆 천안역·두정역에서 버스로 약 10분';
				$park_ktx   = function_exists( 'md_content' ) ? md_content( 'loc_park_ktx',   '🚄 천안아산역에서 버스로 약 25분' )              : '🚄 천안아산역에서 버스로 약 25분';
				if ( $park_walk || $park_train || $park_ktx ) :
				?>
				<p class="md-park__walk">
					<?php
					// v3.44.122 · 개행(\n)으로 분리된 라인 각각 별도 span
					$md_park_lines = array();
					foreach ( array( $park_walk, $park_train, $park_ktx ) as $_pl ) {
						if ( ! $_pl ) continue;
						foreach ( preg_split( "/\r\n|\r|\n/", (string) $_pl ) as $_line ) {
							$_line = trim( $_line );
							if ( $_line !== '' ) $md_park_lines[] = $_line;
						}
					}
					foreach ( $md_park_lines as $_line ) : ?>
						<span><?php echo wp_kses_post( moondental_auto_link_stations( $_line ) ); ?></span>
					<?php endforeach; ?>
				</p>
				<?php endif; ?>
			</aside>

		</div>
	</div>
</section>

<?php /* v3.44.167 · 층별 안내는 이제 진료시간·주차 사이 3열 그리드 내부로 이동 (아래 별도 섹션 제거) */ ?>

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
