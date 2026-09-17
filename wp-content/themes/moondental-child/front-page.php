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
		<?php /* v3.85 · 홈 사이드바의 종합안내서 카드 4개 제거 (v3.44.182 에 추가했던 것).
		 * 종합안내서는 각 센터 페이지의 배너(template-parts/section-guide-cta.php)에서
		 * 센터 색에 맞춰 보여주는 것으로 통일 — 임플란트·교정·스마일디자인은 page-service.php /
		 * page-smile-design.php, 자연치아보존센터는 page-preservation.php.
		 * template-parts/section-guide-rail.php 파일은 그대로 두었다 — 되살리려면 이 자리에
		 *   get_template_part( 'template-parts/section', 'guide-rail', array( 'slug' => 'implant' ) );
		 * 형태로 slug 별로 한 줄씩 다시 넣으면 된다 (implant · suresmile · laminate · preservation). */ ?>
		<?php /* v3.44.186 · 환자분들의 이야기 */ ?>
		<?php get_template_part( 'template-parts/section', 'testimonials-rail' ); ?>
	</aside>
	<main class="md-home-2col__main">
		<?php get_template_part( 'template-parts/section', 'why' ); ?>
		<?php get_template_part( 'template-parts/section', 'clinic-intro' ); ?>
		<?php /* v3.86 · 홈에서 CLINICAL SERVICES(진료항목 7카드) 섹션 제거.
		 * 4개 전문센터 카드(clinic-intro)와 내용이 겹치고, 진료항목은 상단 메뉴·푸터에서 닿는다.
		 * template-parts/section-services.php 는 그대로 두었다 — 되살리려면 아래 한 줄을 다시 넣으면 된다.
		 *   get_template_part( 'template-parts/section', 'services' ); */ ?>
		<?php get_template_part( 'template-parts/section', 'facility' ); ?>
		<?php /* v3.44.164 · 후기·소식·발자취는 좌측 사이드바로 이동 · 우측 중복 제거 */ ?>
		<?php get_template_part( 'template-parts/section', 'faq-home' ); ?>
		<?php /* v3.84 · 홈에서 「문치과병원을 둘러보세요」(quicknav) 제거.
		 * 같은 링크들이 푸터 · 상단 메뉴에 전 페이지 공통으로 있어 홈 하단이
		 * 링크 카드로만 길어졌다. 층별 안내(v3.44.233) 때와 같은 판단이다.
		 * template-parts/section-quicknav.php 파일은 그대로 두었다 —
		 * 되살리려면 이 자리에 아래 한 줄을 다시 넣으면 된다.
		 *   get_template_part( 'template-parts/section', 'quicknav' );
		 * 검색엔진용 SiteNavigationElement 구조화 데이터(inc/seo-boost.php)는
		 * 이 섹션과 별개로 동작하므로 그대로 둔다. */ ?>
		<?php /* v3.85.2 · 예약 CTA 를 2단 영역 안(우측 본문 칸)으로 이동.
		 * 전체 폭으로 두면 바로 위 FAQ(본문 칸 폭)와 폭이 달라져 어색했다.
		 * 좌측 사이드바 공간을 그대로 두고 FAQ → CTA 가 같은 폭으로 이어지게 한다. */ ?>
		<?php get_template_part( 'template-parts/section', 'cta' ); ?>
		<?php /* v3.85.3 · 오시는 길도 본문 칸 안으로 (footer.php 에서는 홈일 때 건너뜀) */ ?>
		<?php get_template_part( 'template-parts/section-location' ); ?>
	</main>
</div>

<?php
get_footer();
