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
		<div class="md-cta-banner md-reveal">
			<span class="md-cta-banner__eyebrow">상담 예약</span>
			<h2 class="md-cta-banner__title">치아 때문에 망설이고 계신가요?</h2>
			<p class="md-cta-banner__lead">
				환자분의 상황을 먼저 듣고, 꼭 필요한 치료만 권합니다.<br>
				지금 상담을 신청하시면 진료시간 내 빠르게 연락드릴게요.
			</p>
			<div class="md-btn-group" style="justify-content:center; display:flex;">
				<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>" data-track="cta-banner-reservation">
					📅 상담 예약하기
				</a>
				<a class="md-btn md-btn-secondary md-btn--lg" href="tel:<?php echo esc_attr( $info['phone_link'] ?: preg_replace('/[^0-9]/', '', $info['phone']) ); ?>" data-track="cta-banner-call">
					📞 <?php echo esc_html( $info['phone'] ); ?>
				</a>
				<?php if ( $info['kakao_url'] ) : ?>
					<a class="md-btn md-btn-ghost md-btn--lg" href="<?php echo esc_url( $info['kakao_url'] ); ?>" target="_blank" rel="noopener" data-track="cta-banner-kakao">
						💬 카카오톡
					</a>
				<?php endif; ?>
			</div>
			<p class="md-cta-banner__hint">진료시간: 평일 09:00–20:30 · 토 09:00–14:00 · 일/공휴일 휴진</p>
		</div>
	</div>
</section>
