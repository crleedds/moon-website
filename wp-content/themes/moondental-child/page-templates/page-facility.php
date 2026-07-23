<?php
/**
 * Template Name: 기술력·시설 (강점 카드 + 시설 본문)
 * Template Post Type: page
 *
 * /기술력-시설/ 페이지 전용 템플릿.
 *  흐름: Hero → 강점 카드 9 (클릭 → /강점/{slug}/) → 본문(시설·장비 상세) → CTA
 *
 * @package moondental-child
 */

get_header();
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸
			<a href="<?php echo esc_url( home_url( '/병원소개/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_about', '병원안내' ) ); ?></a> ▸
			<span><?php the_title(); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<?php
		$subtitle = get_post_meta( get_queried_object_id(), '_md_page_subtitle', true );
		if ( ! $subtitle ) {
			$subtitle = function_exists( 'md_content' )
				? md_content( 'facility_hero_lead', '문치과병원이 갖춘 의료기관 종별·시설·장비·운영 측면의 강점과 진료 인프라를 한 곳에서 확인하세요.' )
				: '문치과병원이 갖춘 의료기관 종별·시설·장비·운영 측면의 강점과 진료 인프라를 한 곳에서 확인하세요.';
		}
		if ( $subtitle ) : ?>
			<p class="md-page-hero__lead"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 1. 강점 9 카드 ============ -->
<?php get_template_part( 'template-parts/section', 'strengths' ); ?>

<!-- ============ 2. 시설·장비 상세 본문 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<article class="md-page-content">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				} elseif ( function_exists( 'moondental_default_facility_content' ) ) {
					echo moondental_default_facility_content();
				}
			endwhile;
			?>
		</article>
	</div>
</section>

<!-- ============ 3. CTA ============ -->
<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
