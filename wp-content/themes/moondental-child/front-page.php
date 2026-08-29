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
		<?php /* v3.44.233 · 홈 사이드바의 층별 안내 제거 (v3.44.166 에 추가했던 것).
		 * 같은 내용이 푸터에 전 페이지 공통으로 있고 오시는 길·상담예약 페이지에도
		 * 카드로 들어가 있어, 홈에서는 사이드바만 길어졌다.
		 * template-parts/section-floor-rail.php 파일은 그대로 두었다.
		 * 되살리려면 이 자리에 floor-rail 을 부르는 get_template_part 한 줄을
		 * 다시 넣으면 된다 (바로 아래 guide-rail 호출과 같은 형태). */ ?>
		<?php /* v3.44.182 · 종합안내서 3개 별도 섹션 (임플란트센터·교정센터·스마일디자인센터) */ ?>
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
