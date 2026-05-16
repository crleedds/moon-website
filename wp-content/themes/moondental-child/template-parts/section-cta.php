<?php
/**
 * Section: CTA Banner — 페이지 하단 예약/상담 유도
 *
 * @package moondental-child
 */
$info = moondental_get_info();
?>

<section class="md-section md-section--sm" aria-label="예약 안내">
	<div class="md-container">
		<div class="md-cta-banner">
			<h2 class="md-cta-banner__title">진료가 처음이신가요?</h2>
			<p class="md-cta-banner__lead">
				전화 한 통이면 됩니다. 환자분의 상황을 먼저 듣고, 무리한 치료를 권하지 않습니다.
			</p>
			<div class="md-btn-group" style="justify-content:center; display:flex;">
				<a class="md-btn md-btn-secondary md-btn--lg" href="tel:<?php echo esc_attr( $info['phone_link'] ?: preg_replace('/[^0-9]/', '', $info['phone']) ); ?>">
					📞 <?php echo esc_html( $info['phone'] ); ?>
				</a>
				<?php if ( $info['kakao_url'] ) : ?>
					<a class="md-btn md-btn-secondary md-btn--lg" href="<?php echo esc_url( $info['kakao_url'] ); ?>" target="_blank" rel="noopener">
						카카오톡 상담
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
