<?php
/**
 * Section: 최근 공지사항 / 소식 (최신 게시글 3개)
 *
 * @package moondental-child
 */
$recent = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 3,
	'ignore_sticky_posts' => true,
) );

if ( ! $recent->have_posts() ) {
	return;
}
?>

<section class="md-section" id="notices" aria-label="공지사항">
	<div class="md-container">
		<header class="md-section-head" style="text-align:left; max-width:none; display:flex; justify-content:space-between; align-items:end; gap:16px; flex-wrap:wrap;">
			<div>
				<span class="md-section-head__eyebrow">News</span>
				<h2 class="md-section-head__title" style="margin:0;">공지사항</h2>
			</div>
			<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ?: home_url( '/notices/' ) ); ?>">
				전체보기 →
			</a>
		</header>

		<div class="md-service-grid">
			<?php while ( $recent->have_posts() ) : $recent->the_post(); ?>
				<article class="md-card" style="text-align:left;">
					<div style="font-size:var(--fs-caption); color:var(--color-text-mute); margin-bottom:8px;">
						<?php echo esc_html( get_the_date() ); ?>
					</div>
					<h3 style="font-size:var(--fs-h4); margin:0 0 12px;">
						<a href="<?php the_permalink(); ?>" style="color:var(--color-text);"><?php the_title(); ?></a>
					</h3>
					<p style="font-size:var(--fs-small); color:var(--color-text-sub); margin:0;">
						<?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?>
					</p>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</div>
</section>
