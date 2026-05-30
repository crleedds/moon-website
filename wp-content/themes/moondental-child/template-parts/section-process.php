<?php
/**
 * Section: 진료 흐름 6단계
 *
 * 모든 텍스트는 사용자 정의하기 → 홈 콘텐츠 → 진료 흐름 6단계 에서 편집 가능.
 *
 * @package moondental-child
 */
$steps = array();
for ( $i = 1; $i <= 6; $i++ ) {
	$steps[] = array(
		'num'   => sprintf( '%02d', $i ),
		'icon'  => md_content( "process_{$i}_icon",  '' ),
		'title' => md_content( "process_{$i}_title", '' ),
		'desc'  => md_content( "process_{$i}_desc",  '' ),
	);
}
?>

<section class="md-section md-section--surface" id="process" aria-label="문치과 진료 흐름 6단계">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'process_eyebrow', 'Treatment Flow' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'process_title', '첫 방문부터 사후관리까지' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'process_lead', '환자분이 어느 단계에 계신지 항상 알 수 있도록 — 6단계로 안내드립니다.' ) ) ); ?>
			</p>
		</header>

		<ol class="md-process">
			<?php foreach ( $steps as $step ) : if ( ! $step['title'] ) continue; ?>
				<li class="md-process__item">
					<div class="md-process__head">
						<span class="md-process__icon" aria-hidden="true"><?php echo $step['icon']; ?></span>
						<span class="md-process__no"><?php echo esc_html( $step['num'] ); ?></span>
					</div>
					<h3 class="md-process__title"><?php echo esc_html( $step['title'] ); ?></h3>
					<p class="md-process__desc"><?php echo esc_html( $step['desc'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>

		<div style="text-align:center; margin-top: clamp(28px, 3.5vw, 40px);">
			<?php echo md_render_reservation_ctas( array( 'track' => 'cta-process', 'size' => 'lg', 'align' => 'center' ) ); ?>
		</div>
	</div>
</section>
