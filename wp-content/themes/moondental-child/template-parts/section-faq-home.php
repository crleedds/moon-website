<?php
/**
 * Section: 홈 핵심 FAQ 6개
 *
 * 모든 텍스트는 사용자 정의하기 → 홈 콘텐츠 → 자주 묻는 질문 6 에서 편집 가능.
 *
 * @package moondental-child
 */
$faqs = array();
for ( $i = 1; $i <= 6; $i++ ) {
	$q = md_content( "faq_{$i}_q", '' );
	$a = md_content( "faq_{$i}_a", '' );
	if ( ! $q ) continue;
	$faqs[] = array( 'q' => $q, 'a' => $a );
}
?>

<section class="md-section md-section--surface" id="faq-home" aria-label="자주 묻는 질문">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'faq_eyebrow', 'FAQ' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'faq_title', '예약 전 자주 묻는 질문' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'faq_lead', '환자분들이 가장 많이 궁금해하시는 6가지 — 미리 확인하세요.' ) ) ); ?>
			</p>
		</header>

		<div class="md-home-faq">
			<?php foreach ( $faqs as $idx => $faq ) : ?>
				<details class="md-home-faq__item"<?php echo $idx === 0 ? ' open' : ''; ?>>
					<summary>
						<span class="md-home-faq__q"><?php echo esc_html( $faq['q'] ); ?></span>
						<span class="md-home-faq__chev" aria-hidden="true">+</span>
					</summary>
					<div class="md-home-faq__a"><?php echo wp_kses_post( wpautop( $faq['a'] ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>

		<div style="text-align:center; margin-top: clamp(24px, 3vw, 32px);">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">
				전체 FAQ 보기 →
			</a>
		</div>
	</div>
</section>
