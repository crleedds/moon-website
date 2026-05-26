<?php
/**
 * Template Name: 오시는 길
 * Template Post Type: page
 *
 * @package moondental-child
 */

get_header();
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php the_title(); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
	</div>
</section>

<section class="md-section">
	<div class="md-container">
		<article class="md-page-content">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				} else {
					echo moondental_default_location_content();
				}
			endwhile;
			?>
		</article>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
