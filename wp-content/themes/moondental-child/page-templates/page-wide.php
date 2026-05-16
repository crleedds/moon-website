<?php
/**
 * Template Name: 풀 너비 페이지 (사이드바 없음)
 * Template Post Type: page
 *
 * 오시는 길 / 시설 / 연락처 등 풀 너비 레이아웃이 필요한 페이지에 할당.
 * WP 에디터 본문이 컨테이너 max 폭으로 펼쳐진다.
 *
 * @package moondental-child
 */

get_header();
?>

<section class="md-page-hero" aria-label="<?php the_title_attribute(); ?>">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php the_title(); ?></span>
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
	<div class="md-container">
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
