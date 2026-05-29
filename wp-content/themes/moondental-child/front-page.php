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

<?php get_template_part( 'template-parts/section', 'hero' ); ?>

<?php get_template_part( 'template-parts/section', 'trust' ); ?>

<?php get_template_part( 'template-parts/section', 'why' ); ?>

<?php get_template_part( 'template-parts/section', 'services' ); ?>

<?php get_template_part( 'template-parts/section', 'process' ); ?>

<?php get_template_part( 'template-parts/section', 'facility' ); ?>

<?php get_template_part( 'template-parts/section', 'testimonials' ); ?>

<?php get_template_part( 'template-parts/section', 'faq-home' ); ?>

<?php get_template_part( 'template-parts/section', 'info' ); ?>

<?php get_template_part( 'template-parts/section', 'notices' ); ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
