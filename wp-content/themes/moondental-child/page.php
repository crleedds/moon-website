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
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				} else {
					/* 슬러그별 자동 기본 콘텐츠 — 본문이 비어 있을 때만.
					 * URL-encoded 형태로 저장된 슬러그(%ec%9d%98...)도 정규화. */
					$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
					$slug = urldecode( $slug );
					switch ( $slug ) {
						case 'about':
						case 'hospital':
						case '병원소개':
							echo moondental_default_about_content();
							break;
						case 'doctors':
						case 'team':
						case '의료진':
							echo moondental_default_doctors_content();
							break;
						case 'location':
						case 'directions':
						case '오시는-길':
							echo moondental_default_location_content();
							break;
						case 'facility':
						case '기술력-시설':
							echo moondental_default_facility_content();
							break;
						case 'cases':
						case '임상-케이스':
							echo moondental_default_cases_content();
							break;
						case 'services':
						case '진료항목':
							echo moondental_default_services_overview_content();
							break;
						default:
							/* 진료항목 자식 페이지면 service content 시도 */
							$svc_html = moondental_default_service_content( $slug );
							if ( $svc_html ) {
								echo $svc_html;
							} else {
								echo '<p style="color:var(--color-text-sub);">콘텐츠 준비 중입니다.</p>';
							}
					}
				}
			endwhile;
			?>
		</article>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
