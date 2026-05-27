<?php
/**
 * Section: Hero
 *
 * 홈 첫 화면 — 슬로건 / CTA / 메인 이미지
 *
 * @package moondental-child
 */
$info = moondental_get_info();

$eyebrow = get_theme_mod( 'moondental_hero_eyebrow', '천안 만남로 · 1995년부터' );
$title_a = get_theme_mod( 'moondental_hero_title_a', '실력과 품격있는 진료,' );
$title_b = get_theme_mod( 'moondental_hero_title_b', '가족처럼 오래 곁에' );
$lead    = get_theme_mod( 'moondental_hero_lead',
	"10명의 의료진이 한 자리에서, 일반진료부터 임플란트·교정·심미·소아예방까지.\n" .
	"충분히 듣고, 꼭 필요한 치료만 권합니다."
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
					<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>" data-track="cta-hero-reservation">
						📅 상담 예약하기
					</a>
					<?php if ( ! empty( $info['kakao_url'] ) ) : ?>
						<a class="md-btn md-btn-secondary md-btn--lg" href="<?php echo esc_url( $info['kakao_url'] ); ?>" target="_blank" rel="noopener" data-track="cta-hero-kakao">
							카카오톡 상담
						</a>
					<?php else : ?>
						<a class="md-btn md-btn-secondary md-btn--lg" href="tel:<?php echo esc_attr( $info['phone_link'] ?: preg_replace('/[^0-9]/', '', $info['phone']) ); ?>">
							📞 <?php echo esc_html( $info['phone'] ); ?>
						</a>
					<?php endif; ?>
				</div>

				<ul class="md-hero__badges" aria-label="문치과병원의 특징">
					<li><span aria-hidden="true">✓</span> 1995년 개원 · 30년 임상</li>
					<li><span aria-hidden="true">✓</span> 의료진 10명 · 통합진료</li>
					<li><span aria-hidden="true">✓</span> 의료기관번호 34400117</li>
				</ul>
			</div>

			<div class="md-hero__media">
				<?php if ( $hero_image_id ) : ?>
					<?php echo wp_get_attachment_image( $hero_image_id, 'moondental-hero', false, array( 'alt' => esc_attr( $info['name_short'] . ' 메인 이미지' ) ) ); ?>
				<?php else : ?>
					<div class="md-hero__media-placeholder" aria-hidden="true">
						<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
							<span style="background:rgba(255,255,255,0.7); padding:6px 14px; border-radius:99px;">관리자에게만 보임 · Customizer에서 메인 이미지 등록 가능</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
