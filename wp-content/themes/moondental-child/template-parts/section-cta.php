<?php
/**
 * Section: CTA Banner — 페이지 하단 예약/상담 유도
 *
 * v3.37.9 · 페이지 컨텍스트 자동 감지 → 페이지별 맞춤 문구.
 *  기존 페이지별 사용자 정의 필드(doctors_cta_*, price_cta_*, preservation_cta_* 등) 재사용.
 *  누락 컨텍스트는 코드 default. 명시 override는 args['context']로.
 *
 * @package moondental-child
 */

$args = wp_parse_args( $args ?? array(), array( 'context' => null ) );
$copy = moondental_cta_copy( $args['context'] );
?>

<section class="md-section md-section--sm" aria-label="<?php echo esc_attr( md_content( 'aria_sec_cta_banner', '예약 안내' ) ); ?>">
	<div class="md-container">
		<div class="md-cta-banner md-reveal">
			<?php if ( ! empty( $copy['eyebrow'] ) ) : ?>
				<span class="md-cta-banner__eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></span>
			<?php endif; ?>
			<h2 class="md-cta-banner__title"><?php echo nl2br( esc_html( $copy['title'] ) ); ?></h2>
			<?php if ( ! empty( $copy['lead'] ) ) : ?>
				<p class="md-cta-banner__lead">
					<?php echo nl2br( esc_html( $copy['lead'] ) ); ?>
				</p>
			<?php endif; ?>
			<?php echo md_render_reservation_ctas( array( 'track' => 'cta-banner-' . moondental_cta_context(), 'size' => 'lg', 'align' => 'center' ) ); ?>
			<p class="md-cta-banner__hint"><?php echo esc_html( md_content( 'cta_hint', '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00 · 일/공휴일 휴진' ) ); ?></p>
		</div>
	</div>
</section>
