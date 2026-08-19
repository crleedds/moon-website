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

<?php /* v3.44.159/161 · 히어로 아래부터 2컬럼 (좌측 사이드바 · 우측 메인) */ ?>
<div class="md-home-2col">
	<aside class="md-home-2col__side">
		<?php get_template_part( 'template-parts/section', 'history-rail' ); ?>
		<?php /* v3.44.161 · 30여년 발자취 아래에 소식·치아이야기 세로 리스트 */ ?>
		<?php get_template_part( 'template-parts/section', 'news-rail' ); ?>
		<?php /* v3.44.166 · 소식 아래 층별 안내 */ ?>
		<?php get_template_part( 'template-parts/section', 'floor-rail' ); ?>
		<?php /* v3.44.182 · 층별안내 아래 · 종합안내서 3개 별도 섹션 (임플란트센터·교정센터·스마일디자인센터) */ ?>
		<?php get_template_part( 'template-parts/section', 'guide-rail', array( 'slug' => 'implant'   ) ); ?>
		<?php get_template_part( 'template-parts/section', 'guide-rail', array( 'slug' => 'suresmile' ) ); ?>
		<?php get_template_part( 'template-parts/section', 'guide-rail', array( 'slug' => 'laminate'  ) ); ?>
		<?php /* v3.44.186 · 종합안내서 아래 · 환자분들의 이야기 (기존 위치에서 이동) */ ?>
		<?php get_template_part( 'template-parts/section', 'testimonials-rail' ); ?>
	</aside>
	<main class="md-home-2col__main">
		<?php get_template_part( 'template-parts/section', 'why' ); ?>
		<?php get_template_part( 'template-parts/section', 'clinic-intro' ); ?>
		<?php get_template_part( 'template-parts/section', 'services' ); ?>
		<?php get_template_part( 'template-parts/section', 'facility' ); ?>
		<?php /* v3.44.164 · 후기·소식·발자취는 좌측 사이드바로 이동 · 우측 중복 제거 */ ?>
		<?php get_template_part( 'template-parts/section', 'faq-home' ); ?>
		<?php get_template_part( 'template-parts/section', 'quicknav' ); ?>
	</main>
</div>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
