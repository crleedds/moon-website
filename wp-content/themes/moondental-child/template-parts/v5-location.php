<?php
/**
 * v5.3 · 원장 지시 · 「Location 카드 + 구글 지도」 — 홈 · 모든 페이지 하단 · 오시는 길 페이지 공통
 *
 * @package moondental-child
 */
$info  = function_exists( 'moondental_get_info' ) ? moondental_get_info() : array();
$mc    = function ( $k, $d = '' ) { return function_exists( 'md_content' ) ? md_content( $k, $d ) : $d; };
$place = 'https://map.naver.com/p/entry/place/12772165';
$kakao = 'https://map.kakao.com/?q=%ED%95%9C%EC%95%84%EC%9D%98%EB%A3%8C%EC%9E%AC%EB%8B%A8%20%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90';
$goog  = 'https://maps.app.goo.gl/MNt59kcxeKL92nCU9';
$gmap  = $mc( 'loc_gmap_embed_url', 'https://www.google.com/maps?q=%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90%20%EC%B2%9C%EC%95%88%20%EB%A7%8C%EB%82%A8%EB%A1%9C%2052&z=16&output=embed&hl=ko' );
$addr  = $mc( 'info_address', $info['address'] ?? '' );
if ( ! $addr ) $addr = '충청남도 천안시 동남구 만남로 52, 문타워 9~13층';
$id    = isset( $args['id'] ) ? (string) $args['id'] : '';
?>
<section class="v5-mapsec"<?php echo $id ? ' id="' . esc_attr( $id ) . '"' : ''; ?> aria-label="<?php echo esc_attr( $mc( 'aria_sec_location', '오시는 길' ) ); ?>">
	<div class="v5-wrap v5-mapgrid">
		<div class="v5-mapcard">
			<span class="v5-label">Location</span>
			<p class="v5-mapcard__addr"><a href="<?php echo esc_url( $place ); ?>" target="_blank" rel="noopener" data-track="cta-v5-location-addr"><?php echo esc_html( $addr ); ?></a></p>
			<p class="v5-mapcard__note">천안고속 · 시외버스터미널 맞은편 터미널사거리</p>
			<div class="v5-maps">
				<a href="<?php echo esc_url( $place ); ?>" target="_blank" rel="noopener" data-track="cta-v5-location-naver"><?php echo esc_html( $mc( 'flocation_btn_naver', '네이버 지도' ) ); ?></a>
				<a href="<?php echo esc_url( $kakao ); ?>" target="_blank" rel="noopener" data-track="cta-v5-location-kakao"><?php echo esc_html( $mc( 'flocation_btn_kakao', '카카오맵' ) ); ?></a>
				<a href="<?php echo esc_url( $goog ); ?>" target="_blank" rel="noopener" data-track="cta-v5-location-google"><?php echo esc_html( $mc( 'flocation_btn_google', 'Google Maps' ) ); ?></a>
			</div>
		</div>
		<div class="v5-mapbox">
			<iframe src="<?php echo esc_url( $gmap ); ?>" title="<?php echo esc_attr( $mc( 'loc_gmap_iframe_title', '문치과병원 구글 지도' ) ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
		</div>
	</div>
</section>
