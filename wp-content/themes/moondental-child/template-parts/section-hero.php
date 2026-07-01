<?php
/**
 * Section: Hero
 *
 * 홈 첫 화면 — 슬로건 / CTA / 메인 이미지
 *
 * @package moondental-child
 */
$info = moondental_get_info();

$eyebrow = get_theme_mod( 'moondental_hero_eyebrow', '천안 만남로 · 1995년부터 한자리에서' );
$title_a = get_theme_mod( 'moondental_hero_title_a', '천안·아산에서 30여년,' );
$title_b = get_theme_mod( 'moondental_hero_title_b', '환자 한 분의 평생 치아를' );
$lead    = get_theme_mod( 'moondental_hero_lead',
	"천안·아산 임플란트·투명교정·라미네이트·자연치아 살리기까지.\n" .
	"분야별 전문 의료진이 한 자리에서 — 충분히 듣고, 꼭 필요한 치료만 권합니다."
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

				<?php echo md_render_reservation_ctas( array( 'track' => 'cta-hero', 'size' => 'lg', 'align' => 'start' ) ); ?>

				<ul class="md-hero__badges" aria-label="천안 만남로 문치과병원의 특징">
					<li><span aria-hidden="true">✓</span> 천안 만남로 1995년 개원 · 30여년 임상</li>
					<li><span aria-hidden="true">✓</span> 분야별 전문 의료진 협진</li>
					<li><span aria-hidden="true">✓</span> 의료기관번호 34400117 · 한아의료재단</li>
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
