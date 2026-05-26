<?php
/**
 * Section: 의료진 팀 그리드 (10명)
 *
 * @package moondental-child
 */
$groups = moondental_get_team();
?>

<section class="md-section md-section--surface" id="team" aria-label="의료진">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Our Team</span>
			<h2 class="md-section-head__title">10명의 의료진이 함께합니다</h2>
			<p class="md-section-head__lead">
				구강악안면외과·보철·교정·소아·치주·예방 — 각 분야 전문 의료진이 한 자리에서<br>
				환자 한 분의 평생 치아 건강을 책임집니다.
			</p>
		</header>

		<?php foreach ( $groups as $group ) : ?>
			<div class="md-team-group">
				<h3 class="md-team-group__title"><?php echo esc_html( $group['group'] ); ?></h3>
				<div class="md-team-grid">
					<?php foreach ( $group['members'] as $doc ) : ?>
						<article class="md-team-card">
							<div class="md-team-card__avatar" aria-hidden="true">
								<span><?php echo esc_html( mb_substr( $doc['name'], -2 ) ); ?></span>
							</div>
							<div class="md-team-card__body">
								<div class="md-team-card__role"><?php echo esc_html( $doc['role'] ); ?></div>
								<h4 class="md-team-card__name"><?php echo esc_html( $doc['name'] ); ?></h4>
								<?php if ( ! empty( $doc['specialty'] ) ) : ?>
									<div class="md-team-card__spec"><?php echo esc_html( $doc['specialty'] ); ?></div>
								<?php endif; ?>
								<?php if ( ! empty( $doc['bio'] ) ) : ?>
									<p class="md-team-card__bio"><?php echo esc_html( $doc['bio'] ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div style="text-align:center; margin-top:32px;">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( home_url( '/doctors/' ) ); ?>">
				의료진 자세히 보기 →
			</a>
		</div>
	</div>
</section>
