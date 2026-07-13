<?php
/**
 * Template Name: 자주 묻는 질문 (FAQ)
 * Template Post Type: page
 *
 * @package moondental-child
 */

get_header();
$faqs = moondental_get_faqs();
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>자주 묻는 질문</span>
		</nav>
		<h1 class="md-page-hero__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'faq_page_title', get_the_title() ) : get_the_title() ); ?></h1>
		<p class="md-page-hero__lead">
			<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'faq_page_lead', '환자분들이 가장 많이 문의하시는 질문을 카테고리별로 정리했습니다.' ) : '환자분들이 가장 많이 문의하시는 질문을 카테고리별로 정리했습니다.' ) ); ?>
		</p>
	</div>
</section>

<section class="md-section">
	<div class="md-container md-container--narrow">

		<article class="md-page-content md-faq-intro">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					echo apply_filters( 'the_content', $body );
				} elseif ( function_exists( 'moondental_default_faq_intro' ) ) {
					echo moondental_default_faq_intro();
				}
			endwhile;
			?>
		</article>

		<?php foreach ( $faqs as $category => $items ) : ?>
			<section class="md-faq-group">
				<h2 class="md-faq-group__title"><?php echo esc_html( $category ); ?></h2>
				<div class="md-faq">
					<?php $_faq_idx = 0; foreach ( $items as $item ) :
						$_is_first = ( $_faq_idx === 0 ); ?>
						<details class="md-faq__item"<?php echo $_is_first ? ' open' : ''; ?>>
							<summary><?php echo esc_html( $item['q'] ); ?></summary>
							<p><?php echo wp_kses_post( md_autolink_addresses( $item['a'] ) ); ?></p>
						</details>
					<?php $_faq_idx++; endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
