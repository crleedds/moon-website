<?php
/**
 * 치과사전 단일 용어 · /치과사전/{slug}/
 *
 * @package moondental-child
 */
get_header();
while ( have_posts() ) : the_post();
	$cats = get_the_terms( get_the_ID(), 'md_term_category' );
	if ( is_wp_error( $cats ) ) $cats = array();

	// 관련 용어 (같은 카테고리 · 6개)
	$related = array();
	if ( ! empty( $cats ) ) {
		$cat_ids = wp_list_pluck( $cats, 'term_id' );
		$rel_q = new WP_Query( array(
			'post_type'      => 'md_term',
			'posts_per_page' => 6,
			'post__not_in'   => array( get_the_ID() ),
			'orderby'        => 'rand',
			'no_found_rows'  => true,
			'tax_query'      => array( array(
				'taxonomy' => 'md_term_category',
				'field'    => 'id',
				'terms'    => $cat_ids,
			) ),
		) );
		if ( $rel_q->have_posts() ) {
			while ( $rel_q->have_posts() ) {
				$rel_q->the_post();
				$related[] = array(
					'title'   => get_the_title(),
					'url'     => get_permalink(),
					'excerpt' => wp_trim_words( get_the_excerpt(), 15, '…' ),
				);
			}
			wp_reset_postdata();
		}
	}
	// $post 원복
	$post = get_queried_object();
	setup_postdata( $post );
?>

<section class="md-page-hero md-page-hero--term">
	<div class="md-container md-container--narrow">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸
			<a href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">치과사전</a>
			<?php if ( ! empty( $cats ) ) : $first_cat = $cats[0]; ?>
				▸ <a href="<?php echo esc_url( add_query_arg( array( 'cat' => $first_cat->slug ), get_post_type_archive_link( 'md_term' ) ) ); ?>"><?php echo esc_html( $first_cat->name ); ?></a>
			<?php endif; ?>
			 ▸ <span><?php the_title(); ?></span>
		</nav>
		<?php if ( ! empty( $cats ) ) : ?>
			<div class="md-term-hero__cats">
				<?php foreach ( $cats as $c ) : ?>
					<a class="md-term-hero__cat" href="<?php echo esc_url( add_query_arg( array( 'cat' => $c->slug ), get_post_type_archive_link( 'md_term' ) ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<?php $ex = get_the_excerpt(); if ( $ex ) : ?>
			<p class="md-page-hero__lead"><?php echo esc_html( $ex ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="md-section">
	<div class="md-container md-container--narrow">
		<article class="md-page-content md-term-content">
			<?php the_content(); ?>
		</article>

		<div class="md-term-actions">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">← 전체 치과사전으로</a>
			<a class="md-btn md-btn-primary" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>">📅 상담 예약하기</a>
		</div>
	</div>
</section>

<?php if ( $related ) : ?>
<section class="md-section md-section--surface md-section--sm">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">RELATED</span>
			<h2 class="md-section-head__title">관련 용어</h2>
		</header>
		<div class="md-enc-grid">
			<?php foreach ( $related as $r ) : ?>
				<a class="md-enc-card" href="<?php echo esc_url( $r['url'] ); ?>">
					<h3 class="md-enc-card__title"><?php echo esc_html( $r['title'] ); ?></h3>
					<p class="md-enc-card__excerpt"><?php echo esc_html( $r['excerpt'] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php endwhile; get_footer(); ?>
