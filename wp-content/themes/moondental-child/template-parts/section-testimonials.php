<?php
/**
 * Section: 환자 후기 (홈)
 *
 * @package moondental-child
 */
$testimonials = moondental_get_testimonials();
if ( empty( $testimonials ) ) return;
$info = moondental_get_info();
?>

<section class="md-section" id="testimonials" aria-label="환자 후기">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Reviews</span>
			<h2 class="md-section-head__title">환자분들의 이야기</h2>
			<p class="md-section-head__lead">
				문치과병원을 찾아주신 환자분들이 직접 남겨주신 후기입니다.
			</p>
		</header>

		<div class="md-testimonials">
			<?php foreach ( $testimonials as $t ) : ?>
				<article class="md-testimonial">
					<div class="md-testimonial__stars" aria-label="별점 <?php echo (int) $t['rating']; ?>점">
						<?php echo str_repeat( '★', (int) $t['rating'] ); ?><?php echo str_repeat( '☆', 5 - (int) $t['rating'] ); ?>
					</div>
					<p class="md-testimonial__text">"<?php echo esc_html( $t['text'] ); ?>"</p>
					<footer class="md-testimonial__meta">
						<span class="md-testimonial__name"><?php echo esc_html( $t['name'] ); ?></span>
						<span class="md-testimonial__sub"><?php echo esc_html( $t['age'] ); ?> · <?php echo esc_html( $t['service'] ); ?></span>
					</footer>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $info['naver_place'] ) ) : ?>
		<div style="text-align:center; margin-top:32px;">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( $info['naver_place'] ); ?>" target="_blank" rel="noopener" data-track="cta-naver-reviews">
				네이버 플레이스에서 더 많은 후기 보기 →
			</a>
		</div>
		<?php endif; ?>

		<p style="text-align:center; margin-top:16px; font-size:0.8125rem; color:var(--color-text-mute);">
			※ 후기는 환자분 동의 하에 게재되었으며, 진료 결과는 개인차가 있을 수 있습니다.
		</p>
	</div>
</section>
