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

<?php get_template_part( 'template-parts/section', 'why' ); ?>

<?php get_template_part( 'template-parts/section', 'clinic-intro' ); ?>

<?php get_template_part( 'template-parts/section', 'services' ); ?>

<?php /* section-process(첫 방문부터 사후관리까지)는 홈에서 제거 — v3.12.2 */ ?>

<?php get_template_part( 'template-parts/section', 'facility' ); ?>

<?php /* v3.44.158 · 30여년 발자취 사진 스트립 · 클릭 시 /역사/#앵커 로 이동 */ ?>
<?php get_template_part( 'template-parts/section', 'history-strip' ); ?>

<?php get_template_part( 'template-parts/section', 'testimonials' ); ?>

<?php get_template_part( 'template-parts/section', 'faq-home' ); ?>

<?php /* section-info(진료시간 & 오시는 길 3컬럼)는 푸터 위 section-location과 중복되어 제거됨 — v3.12.1 */ ?>

<?php get_template_part( 'template-parts/section', 'notices' ); ?>

<?php /* v3.44.79 · 핵심 페이지 바로가기 · 사이트링크 유도 콘텐츠 링크 카드 */ ?>
<?php get_template_part( 'template-parts/section', 'quicknav' ); ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
