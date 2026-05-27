<?php
/**
 * Front Page — Moon Dental Child
 *
 * 홈페이지 전체 섹션 조합. 각 섹션은 template-parts/ 에서 가져온다.
 *
 * @package moondental-child
 */

get_header();
?>

<?php get_template_part( 'template-parts/section', 'hero' ); ?>

<?php get_template_part( 'template-parts/section', 'services' ); ?>

<?php get_template_part( 'template-parts/section', 'doctor' ); ?>

<?php get_template_part( 'template-parts/section', 'team' ); ?>

<?php get_template_part( 'template-parts/section', 'testimonials' ); ?>

<?php get_template_part( 'template-parts/section', 'info' ); ?>

<?php get_template_part( 'template-parts/section', 'notices' ); ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
