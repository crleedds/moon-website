<?php
/**
 * Section: 오시는 길 (footer-wide, every page).
 *  footer.php에서 자동 include — /오시는-길/ 페이지에서는 중복 방지 위해 skip.
 *  텍스트는 사용자 정의하기 → 사이트 공통 콘텐츠 → 모든 페이지 — 오시는 길 섹션 + 오시는 길 콘텐츠 에서 편집.
 *
 * @package moondental-child
 */
$info       = moondental_get_info();
$naver_map  = $info['naver_map_url'] ?? '';

$q_full  = rawurlencode( ( $info['name_full'] ?? '문치과병원' ) . ' ' . ( $info['address_road'] ?? '' ) );
$q_short = rawurlencode( $info['name_full'] ?? '문치과병원' );
$map_google = $info['google_map_url'] ?: 'https://maps.app.goo.gl/MNt59kcxeKL92nCU9';
$map_naver  = $naver_map ?: 'https://map.naver.com/p/search/' . $q_short;
$map_kakao  = 'https://map.kakao.com/?q=' . $q_short;

// 지도 소스 우선순위: 임베드 HTML > Customizer 업로드 이미지 > 로컬 assets 스크린샷
$map_embed_html = trim( (string) get_theme_mod( 'moondental_flocation_map_embed', '' ) );
$map_image      = '';
$map_image_id   = (int) get_theme_mod( 'moondental_flocation_map_image', 0 );
if ( $map_image_id ) {
	$src = wp_get_attachment_image_url( $map_image_id, 'full' );
	if ( $src ) { $map_image = $src; }
}
if ( ! $map_image ) {
	foreach ( array( 'naver-map.png', 'naver-map.jpg', 'naver-map.jpeg', 'naver-map.webp' ) as $f ) {
		if ( file_exists( MOONDENTAL_DIR . '/assets/images/map/' . $f ) ) {
			$map_image = MOONDENTAL_URI . '/assets/images/map/' . $f;
			break;
		}
	}
}

$title   = function_exists( 'md_content' ) ? md_content( 'flocation_title', '오시는 길' ) : '오시는 길';
// flocation_address: Customizer 값이 비어있으면 병원 기본 주소로 자동 fallback
$address = function_exists( 'md_content' ) ? md_content( 'flocation_address', '' ) : '';
if ( $address === '' ) {
	$address = $info['address'] ?? '';
}

// 진료시간 — 오늘 요일 강조용
$today_dow = (int) wp_date( 'w' ); // 0=일, 4=목, 6=토

$time_wd  = function_exists( 'moondental_extract_time_range' )
	? moondental_extract_time_range( $info['hours_wd']  ?? '' )
	: ( $info['hours_wd']  ?? '' );
$time_thu = function_exists( 'moondental_extract_time_range' )
	? moondental_extract_time_range( $info['hours_thu'] ?? '' )
	: ( $info['hours_thu'] ?? '' );
$time_sat = function_exists( 'moondental_extract_time_range' )
	? moondental_extract_time_range( $info['hours_sat'] ?? '' )
	: ( $info['hours_sat'] ?? '' );

$park_walk  = function_exists( 'md_content' ) ? md_content( 'loc_park_walk',  '🚌 천안종합·고속버스터미널에서 도보 약 5분' ) : '🚌 천안종합·고속버스터미널에서 도보 약 5분';
$park_train = function_exists( 'md_content' ) ? md_content( 'loc_park_train', '🚆 천안역에서 버스로 약 10분' )                : '🚆 천안역에서 버스로 약 10분';
?>
<section class="md-flocation" aria-label="오시는 길">
	<div class="md-container">
		<header class="md-flocation__head">
			<h2 class="md-flocation__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $address ) : ?>
				<p class="md-flocation__addr">
					<a href="<?php echo esc_url( $map_naver ); ?>"
					   target="_blank" rel="noopener"
					   class="md-flocation__addr-link"
					   data-track="cta-flocation-addr"
					   aria-label="네이버 지도에서 위치 보기">
						<?php echo esc_html( $address ); ?>
					</a>
				</p>
			<?php endif; ?>
		</header>

		<?php if ( $map_embed_html ) : ?>
			<div class="md-flocation__map md-flocation__map--embed">
				<?php
				echo wp_kses(
					$map_embed_html,
					array(
						'iframe' => array(
							'src'             => true, 'width' => true, 'height' => true, 'frameborder' => true,
							'style'           => true, 'allow' => true, 'allowfullscreen' => true,
							'loading'         => true, 'referrerpolicy' => true, 'title' => true,
							'sandbox'         => true,
						),
						'div'    => array( 'class' => true, 'id' => true, 'style' => true ),
						'script' => array( 'src' => true, 'async' => true, 'defer' => true, 'type' => true ),
					)
				);
				?>
			</div>
		<?php else : ?>
			<a class="md-locmap md-flocation__map<?php echo $map_image ? ' md-locmap--has-image' : ''; ?>"
			   href="<?php echo esc_url( $map_naver ); ?>"
			   target="_blank" rel="noopener"
			   data-track="cta-flocation-mainmap"
			   aria-label="네이버 지도에서 위치 보기"
			   <?php echo $map_image ? 'style="background-image:url(' . esc_url( $map_image ) . ');"' : ''; ?>>
				<?php if ( ! $map_image ) : ?>
					<div class="md-locmap__pattern" aria-hidden="true"></div>
				<?php endif; ?>
			</a>
		<?php endif; ?>

		<div class="md-mapbtn-grid md-flocation__btns">
			<a class="md-mapbtn md-mapbtn--naver" href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" data-track="cta-flocation-naver">
				<span class="md-mapbtn__logo" aria-hidden="true">N</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'flocation_btn_naver', '네이버 지도' ) : '네이버 지도' ); ?></span>
					<span class="md-mapbtn__sub"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'flocation_btn_naver_sub', '길찾기 · 대중교통' ) : '길찾기 · 대중교통' ); ?></span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--kakao" href="<?php echo esc_url( $map_kakao ); ?>" target="_blank" rel="noopener" data-track="cta-flocation-kakao">
				<span class="md-mapbtn__logo" aria-hidden="true">k</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'flocation_btn_kakao', '카카오맵' ) : '카카오맵' ); ?></span>
					<span class="md-mapbtn__sub"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'flocation_btn_kakao_sub', '길찾기 · 로드뷰' ) : '길찾기 · 로드뷰' ); ?></span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--google" href="<?php echo esc_url( $map_google ); ?>" target="_blank" rel="noopener" data-track="cta-flocation-google">
				<span class="md-mapbtn__logo" aria-hidden="true">G</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'flocation_btn_google', 'Google Maps' ) : 'Google Maps' ); ?></span>
					<span class="md-mapbtn__sub"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'flocation_btn_google_sub', 'Directions · Street View' ) : 'Directions · Street View' ); ?></span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
		</div>

		<!-- 진료시간 + 주차 안내 (2-col) -->
		<div class="md-info-pair md-flocation__pair">

			<aside class="md-hours">
				<header class="md-hours__head">
					<span class="md-hours__badge"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_hours_badge', '🕐 진료시간' ) : '🕐 진료시간' ); ?></span>
					<h3 class="md-hours__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_hours_title', '진료 가능 시간' ) : '진료 가능 시간' ); ?></h3>
				</header>
				<ul class="md-hours__list">
					<li<?php echo in_array( $today_dow, array(1,2,3,5), true ) ? ' class="is-today"' : ''; ?>>
						<span class="md-hours__day">평일 <small>(월·화·수·금)</small></span>
						<span class="md-hours__time"><?php echo esc_html( $time_wd ); ?></span>
					</li>
					<li<?php echo $today_dow === 4 ? ' class="is-today"' : ''; ?>>
						<span class="md-hours__day">목요일</span>
						<span class="md-hours__time"><?php echo esc_html( $time_thu ); ?></span>
					</li>
					<li<?php echo $today_dow === 6 ? ' class="is-today"' : ''; ?>>
						<span class="md-hours__day">토요일</span>
						<span class="md-hours__time"><?php echo esc_html( $time_sat ); ?></span>
					</li>
					<li class="md-hours__off<?php echo $today_dow === 0 ? ' is-today' : ''; ?>">
						<span class="md-hours__day">일요일 · 공휴일</span>
						<span class="md-hours__time">휴진</span>
					</li>
				</ul>
			</aside>

			<aside class="md-park md-park--compact">
				<?php
				// 헬퍼: Customizer 텍스트를 esc_html → md_autolink_addresses 처리
				$mdf = function( $key, $default ) {
					$raw = function_exists( 'md_content' ) ? md_content( $key, $default ) : $default;
					$out = esc_html( $raw );
					return function_exists( 'md_autolink_addresses' ) ? md_autolink_addresses( $out ) : $out;
				};
				?>
				<header class="md-park__head">
					<span class="md-park__badge"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'loc_park_badge', '🅿️ 주차 안내' ) : '🅿️ 주차 안내' ); ?></span>
					<h3 class="md-park__title"><?php echo wp_kses_post( str_replace( '무료', '<strong>무료</strong>', $mdf( 'loc_park_title', '본원 지하 기계식 무료' ) ) ); ?></h3>
					<p class="md-park__lead"><?php echo wp_kses_post( $mdf( 'loc_park_lead', '방문 시 데스크에 주차권을 제출하시면 등록해드립니다.' ) ); ?></p>
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
				<?php if ( $park_walk || $park_train ) : ?>
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
