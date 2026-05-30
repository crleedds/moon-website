<?php
/**
 * Section: 오시는 길 (footer-wide, every page).
 *  footer.php에서 자동 include — /오시는-길/ 페이지에서는 중복 방지 위해 skip.
 *  텍스트는 사용자 정의하기 → 사이트 공통 콘텐츠 → 푸터 오시는 길 섹션 에서 편집.
 *
 * @package moondental-child
 */
$info       = moondental_get_info();
$naver_map  = $info['naver_map_url'] ?? '';

$q_full  = rawurlencode( ( $info['name_full'] ?? '문치과병원' ) . ' ' . ( $info['address_road'] ?? '' ) );
$q_short = rawurlencode( $info['name_full'] ?? '문치과병원' );
$map_google = 'https://www.google.com/maps/search/?api=1&query=' . $q_full;
$map_naver  = $naver_map ?: 'https://map.naver.com/p/search/' . $q_short;
$map_kakao  = 'https://map.kakao.com/?q=' . $q_short;

$map_image = '';
foreach ( array( 'naver-map.png', 'naver-map.jpg', 'naver-map.jpeg', 'naver-map.webp' ) as $f ) {
	if ( file_exists( MOONDENTAL_DIR . '/assets/images/map/' . $f ) ) {
		$map_image = MOONDENTAL_URI . '/assets/images/map/' . $f;
		break;
	}
}

$title   = function_exists( 'md_content' ) ? md_content( 'flocation_title', '오시는 길' ) : '오시는 길';
$address = function_exists( 'md_content' ) ? md_content( 'flocation_address', $info['address'] ?? '' ) : ( $info['address'] ?? '' );
?>
<section class="md-flocation" aria-label="오시는 길">
	<div class="md-container">
		<header class="md-flocation__head">
			<h2 class="md-flocation__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( $address ) : ?>
				<p class="md-flocation__addr"><?php echo esc_html( $address ); ?></p>
			<?php endif; ?>
		</header>

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
	</div>
</section>
