<?php
/**
 * Section: 홈 · 종합 안내서 3종 카드
 *
 * v3.44.175 · 스크린샷 참고 디자인 (dark card + guide code pill + tag chips).
 *
 * @package moondental-child
 */
if ( ! function_exists( 'md_guide_index' ) ) return;
$guides = md_guide_index();
if ( ! $guides ) return;
?>
<section class="md-section md-guides-home" aria-label="종합 안내서">
	<div class="md-container">
		<div class="md-guides-home__head">
			<span class="md-guides-home__eyebrow">📖 문치과병원 종합 안내서</span>
			<h2 class="md-guides-home__title">천안·아산 <em>임플란트·투명교정·라미네이트</em> 완벽 정리</h2>
			<p class="md-guides-home__lead">학술 근거와 30여년 임상 경험을 담아 <strong>비용·과정·수명·부작용</strong>까지 A to Z로 안내합니다. 진료 전 꼭 확인해보세요.</p>
		</div>
		<div class="md-guides-home__grid">
			<?php foreach ( $guides as $g ) : ?>
				<a class="md-guide-card" href="<?php echo esc_url( home_url( $g['href'] ) ); ?>">
					<div class="md-guide-card__top">
						<span class="md-guide-card__icon" aria-hidden="true"><?php echo esc_html( $g['icon'] ); ?></span>
						<span class="md-guide-card__code"><?php echo esc_html( $g['code'] ); ?></span>
					</div>
					<div class="md-guide-card__title-block">
						<h3 class="md-guide-card__title"><?php echo esc_html( $g['title'] ); ?></h3>
						<?php if ( ! empty( $g['subtitle'] ) ) : ?>
							<p class="md-guide-card__subtitle"><?php echo esc_html( $g['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
					<div class="md-guide-card__divider" aria-hidden="true"></div>
					<p class="md-guide-card__summary"><?php echo esc_html( $g['summary'] ); ?></p>
					<?php if ( ! empty( $g['tags'] ) ) : ?>
						<div class="md-guide-card__tags">
							<?php foreach ( $g['tags'] as $t ) : ?>
								<span class="md-guide-card__tag"><?php echo esc_html( $t ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<span class="md-guide-card__read">가이드 읽기 <span aria-hidden="true">→</span></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
