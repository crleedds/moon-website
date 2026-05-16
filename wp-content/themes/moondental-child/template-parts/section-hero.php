<?php
/**
 * Section: Hero
 *
 * 홈 첫 화면 — 슬로건 / CTA / 메인 이미지
 *
 * @package moondental-child
 */
$info = moondental_get_info();

// Customizer에 hero_eyebrow / hero_title / hero_lead 가 있으면 사용,
// 없으면 기본 카피. (Customizer에 추후 추가 가능)
$eyebrow = get_theme_mod( 'moondental_hero_eyebrow', '천안 · 가족 치과' );
$title_a = get_theme_mod( 'moondental_hero_title_a', '내 가족이 와도 안심할 수 있는' );
$title_b = get_theme_mod( 'moondental_hero_title_b', '따뜻한 치과' );
$lead    = get_theme_mod( 'moondental_hero_lead',
	$info['tagline'] . '. 충분한 상담과 정확한 진단으로,' . "\n" .
	'환자분이 이해하시고 동의하시는 치료만 진행합니다.'
);

// Hero 이미지 — 외관/진료실/원장님 사진 중 택일 (Customizer로 차후 분리)
$hero_image_id = get_theme_mod( 'moondental_hero_image', 0 );
?>

<section class="md-hero" aria-label="문치과병원 소개">
	<div class="md-container">
		<div class="md-hero__inner">

			<div class="md-hero__text">
				<span class="md-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
				<h1 class="md-hero__title">
					<?php echo esc_html( $title_a ); ?><br>
					<em><?php echo esc_html( $title_b ); ?></em>
				</h1>
				<p class="md-hero__lead"><?php echo nl2br( esc_html( $lead ) ); ?></p>

				<div class="md-btn-group">
					<?php $kakao = $info['kakao_url']; ?>
					<?php if ( $kakao ) : ?>
						<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( $kakao ); ?>" target="_blank" rel="noopener">
							카카오톡으로 상담받기
						</a>
					<?php else : ?>
						<a class="md-btn md-btn-primary md-btn--lg" href="tel:<?php echo esc_attr( $info['phone_link'] ?: preg_replace('/[^0-9]/', '', $info['phone']) ); ?>">
							📞 <?php echo esc_html( $info['phone'] ); ?>
						</a>
					<?php endif; ?>

					<a class="md-btn md-btn-secondary md-btn--lg" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">
						진료안내 보기
					</a>
				</div>
			</div>

			<div class="md-hero__media">
				<?php if ( $hero_image_id ) : ?>
					<?php echo wp_get_attachment_image( $hero_image_id, 'moondental-hero', false, array( 'alt' => esc_attr( $info['name_short'] . ' 메인 이미지' ) ) ); ?>
				<?php else : ?>
					<div class="md-hero__media-placeholder">
						<span>메인 이미지 (Customizer에서 등록)</span>
					</div>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
