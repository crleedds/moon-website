<?php
/**
 * Template Name: 의료진 페이지
 * Template Post Type: page
 *
 * 9명 의료진을 그룹별 카드 그리드로 표시. WP 편집기 본문이 있으면
 * 그리드 위에 도입글로 들어가고, 없으면 기본 도입글 사용.
 *
 * @package moondental-child
 */

get_header();
$groups = moondental_get_team();
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php the_title(); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<p class="md-page-hero__lead">
			구강악안면외과·보철·교정·소아·치주 — 각 분야 전문 의료진이 한 자리에서.
		</p>
	</div>
</section>

<section class="md-section">
	<div class="md-container">

		<div class="md-page-content" style="max-width:760px; margin:0 auto clamp(32px, 5vw, 64px);">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				} else {
					echo moondental_default_doctors_content();
				}
			endwhile;
			?>
		</div>

		<?php foreach ( $groups as $group ) : ?>
			<div class="md-team-group">
				<h3 class="md-team-group__title"><?php echo esc_html( $group['group'] ); ?></h3>
				<div class="md-team-grid md-team-grid--lg">
					<?php foreach ( $group['members'] as $doc ) :
						$photo_url = moondental_doctor_photo_url( $doc['photo'] ?? '' );
					?>
						<article class="md-team-card">
							<div class="md-team-card__avatar md-team-card__avatar--lg<?php echo $photo_url ? ' md-team-card__avatar--photo' : ''; ?>" aria-hidden="true">
								<?php if ( $photo_url ) : ?>
									<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $doc['name'] ); ?>" loading="lazy">
								<?php else : ?>
									<span><?php echo esc_html( mb_substr( $doc['name'], -2 ) ); ?></span>
								<?php endif; ?>
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

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
