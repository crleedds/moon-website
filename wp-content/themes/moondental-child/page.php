<?php
/**
 * Default Page Template — Moon Dental Child
 *
 * 홈을 제외한 일반 페이지 기본 레이아웃.
 * 페이지 헤로(타이틀 + 브레드크럼) + WP 에디터 컨텐츠 + CTA 배너.
 *
 * @package moondental-child
 */

get_header();
?>

<section class="md-page-hero" aria-label="<?php the_title_attribute(); ?>">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a>
			<?php
			$parent_id = wp_get_post_parent_id( get_queried_object_id() );
			if ( $parent_id ) :
			?>
				 ▸ <a href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>"><?php echo esc_html( get_the_title( $parent_id ) ); ?></a>
			<?php endif; ?>
			 ▸ <span><?php the_title(); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<?php
		$subtitle = get_post_meta( get_queried_object_id(), '_md_page_subtitle', true );
		if ( $subtitle ) : ?>
			<p class="md-page-hero__lead"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="md-section">
	<div class="md-container md-container--narrow">
		<article class="md-page-content">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</article>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
