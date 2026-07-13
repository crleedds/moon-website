<?php
/**
 * Section: CTA Banner — 페이지 하단 예약/상담 유도
 *
 * 모든 텍스트는 사용자 정의하기 → 홈 콘텐츠 → 하단 CTA 배너 에서 편집 가능.
 *
 * @package moondental-child
 */
$info = moondental_get_info();
?>

<section class="md-section md-section--sm" aria-label="예약 안내">
	<div class="md-container">
		<div class="md-cta-banner md-reveal">
			<span class="md-cta-banner__eyebrow"><?php echo esc_html( md_content( 'cta_eyebrow', '상담 예약' ) ); ?></span>
			<h2 class="md-cta-banner__title"><?php echo esc_html( md_content( 'cta_title', '30년 임상 · 정직한 견적을 지금 확인하세요' ) ); ?></h2>
			<p class="md-cta-banner__lead">
				<?php echo nl2br( esc_html( md_content( 'cta_lead', "환자분의 상황을 먼저 듣고, 꼭 필요한 치료만 권합니다.\n지금 상담을 신청하시면 진료시간 내 빠르게 연락드릴게요." ) ) ); ?>
			</p>
			<?php echo md_render_reservation_ctas( array( 'track' => 'cta-banner', 'size' => 'lg', 'align' => 'center' ) ); ?>
			<p class="md-cta-banner__hint"><?php echo esc_html( md_content( 'cta_hint', '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00 · 일/공휴일 휴진' ) ); ?></p>
		</div>
	</div>
</section>
