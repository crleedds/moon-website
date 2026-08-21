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
// v3.44.9 · flocation_address 우선 · 없으면 info_address (번역 가능) · 최종 fallback moondental_get_info
$address = function_exists( 'md_content' ) ? md_content( 'flocation_address', '' ) : '';
if ( $address === '' ) {
	$address = function_exists( 'md_content' )
		? md_content( 'info_address', $info['address'] ?? '' )
		: ( $info['address'] ?? '' );
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

$park_walk  = function_exists( 'md_content' ) ? md_content( 'loc_park_walk',  "🚌 천안시외버스터미널에서 도보 5분\n🚌 천안고속버스터미널에서 도보 5분" ) : "🚌 천안시외버스터미널에서 도보 5분\n🚌 천안고속버스터미널에서 도보 5분";
$park_train = function_exists( 'md_content' ) ? md_content( 'loc_park_train', '🚆 천안역·두정역에서 버스로 약 10분' )                : '🚆 천안역·두정역에서 버스로 약 10분';
$park_ktx   = function_exists( 'md_content' ) ? md_content( 'loc_park_ktx',   '🚄 천안아산역에서 버스로 약 25분' )              : '🚄 천안아산역에서 버스로 약 25분';
?>
<section class="md-flocation" aria-label="<?php echo esc_attr( md_content( 'aria_sec_location', '오시는 길' ) ); ?>">
	<div class="md-container">
		<header class="md-flocation__head">
			<h2 class="md-flocation__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $address ) : ?>
				<p class="md-flocation__addr">
					<a href="<?php echo esc_url( $map_naver ); ?>"
					   target="_blank" rel="noopener"
					   class="md-flocation__addr-link"
					   data-track="cta-flocation-addr"
					   aria-label="<?php echo esc_attr( md_content( 'aria_sec_naver_map', '네이버 지도에서 위치 보기' ) ); ?>">
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
			   aria-label="<?php echo esc_attr( md_content( 'aria_sec_naver_map', '네이버 지도에서 위치 보기' ) ); ?>"
			   <?php echo $map_image ? 'style="background-image:url(' . esc_url( $map_image ) . ');"' : ''; ?>>
				<?php if ( ! $map_image ) : ?>
					<div class="md-locmap__pattern" aria-hidden="true"></div>
					<span class="md-locmap__fallback-label"><?php echo esc_html( md_content( 'loc_map_fallback', '🗺️ 지도 이미지 열기 →' ) ); ?></span>
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

		<?php
		/* v3.44.17 · 진료시간 + 주차 pair 는 · 오시는 길 페이지 · 상담예약 페이지에서만 노출.
		 * 다른 페이지 (푸터 include 경로) 에서는 지도·주소·맵 버튼만 표시. */
		$_show_hp_pair = false;
		if ( is_page() ) {
			$_pid   = get_queried_object_id();
			$_tpl_s = get_page_template_slug( $_pid );
			$_slug_s = urldecode( (string) get_post_field( 'post_name', $_pid ) );
			if (
				in_array( $_tpl_s, array(
					'page-templates/page-location.php',
					'page-templates/page-reservation.php',
				), true )
				|| in_array( $_slug_s, array( '오시는-길', '오시는길', '상담예약', 'reservation', 'location' ), true )
			) {
				$_show_hp_pair = true;
			}
		}
		if ( $_show_hp_pair ) :
		?>
		<!-- v3.44.192 · 진료시간 + 층별 안내 + 주차 안내 (3-col) · 층별안내 신규 추가 -->
		<div class="md-info-pair md-info-pair--3col md-flocation__pair">

			<?php
			// v3.44.11 · 진료시간 카드 전체를 네이버 플레이스 링크로 감쌈
			$hours_naver_url = $info['naver_map_url'] ?? '';
			$_hours_open  = $hours_naver_url
				? '<a class="md-hours md-hours--link" href="' . esc_url( $hours_naver_url ) . '" target="_blank" rel="noopener" data-track="cta-location-hours" aria-label="' . esc_attr( md_content( 'loc_hours_aria', '네이버 플레이스에서 최신 진료시간 확인하기' ) ) . '">'
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
				<?php
				$_hours_note = md_content( 'loc_hours_naver_note', '🔔 공휴일 진료 및 휴진 등 변동 사항은 네이버에서 최종 확인해주세요' );
				if ( $_hours_note ) :
				?>
					<p class="md-hours__note-naver"><?php echo esc_html( $_hours_note ); ?></p>
				<?php endif; ?>
			<?php echo $_hours_close; ?>

			<!-- v3.44.192 · 층별 안내 카드 (진료시간과 주차 안내 사이) -->
			<?php if ( function_exists( 'moondental_render_floor_guide' ) ) : ?>
				<?php echo moondental_render_floor_guide( 'card', array(
					'title' => md_content( 'floor_guide_title', '층별 안내' ),
					'lead'  => md_content( 'floor_guide_lead', '문타워 9·10·11·13층 · 각 층 전용 전문 진료실 운영' ),
				) ); ?>
			<?php endif; ?>

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
				<?php if ( $park_walk || $park_train || $park_ktx ) : ?>
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
		<?php endif; /* $_show_hp_pair */ ?>
	</div>
</section>
