<?php
/**
 * Front Page — Moon Dental Child
 *
 * 홈페이지 전체 섹션 조합. 각 섹션은 template-parts/ 에서 가져온다.
 *
 * 흐름 (시선 ↓ 의사결정 ↓):
 *  Hero → Trust → Why → Services → Process → Facility → Testimonials → FAQ → Info → Notices → CTA
 *
 * @package moondental-child
 */

get_header();
?>

<?php /* v3.34.6 · 히어로+사명+지표 3개 섹션을 통합 · 한 화면 첫 임팩트 */ ?>
<?php get_template_part( 'template-parts/section', 'hero-combined' ); ?>

<?php /* v3.44.159 · 히어로 아래부터 2컬럼 (좌측: 30여년 사이드바 · 우측: 나머지 섹션) */ ?>
<div class="md-home-2col">
	<aside class="md-home-2col__side">
		<?php get_template_part( 'template-parts/section', 'history-rail' ); ?>
	</aside>
	<main class="md-home-2col__main">
		<?php get_template_part( 'template-parts/section', 'why' ); ?>
		<?php get_template_part( 'template-parts/section', 'clinic-intro' ); ?>
		<?php get_template_part( 'template-parts/section', 'services' ); ?>
		<?php get_template_part( 'template-parts/section', 'facility' ); ?>
		<?php get_template_part( 'template-parts/section', 'testimonials' ); ?>
		<?php get_template_part( 'template-parts/section', 'faq-home' ); ?>
		<?php get_template_part( 'template-parts/section', 'notices' ); ?>
		<?php get_template_part( 'template-parts/section', 'quicknav' ); ?>
	</main>
</div>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
