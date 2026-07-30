<?php
/**
 * Template Name: 진료 영역 페이지
 * Template Post Type: page
 *
 * 일반진료/임플란트/교정/심미/소아예방 등 진료 페이지에 할당.
 * 페이지 슬러그(general, implant, ortho, aesthetic, pediatric)에 따라
 * 자동으로 아이콘/요약을 매칭. WP 에디터 본문이 상세 설명으로 들어간다.
 *
 * @package moondental-child
 */

get_header();

$services    = moondental_get_services();
$slug        = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
$current_svc = null;
foreach ( $services as $svc ) {
	if ( $svc['slug'] === $slug ) {
		$current_svc = $svc;
		break;
	}
}
?>

<section class="md-page-hero" aria-label="<?php the_title_attribute(); ?>">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a>
			 ▸ <a href="<?php echo esc_url( home_url( '/진료항목/' ) ); ?>">진료안내</a>
			 ▸ <span><?php the_title(); ?></span>
		</nav>
		<?php if ( $current_svc ) : ?>
			<div class="md-page-hero__icon" aria-hidden="true"><?php echo moondental_render_icon( $current_svc['icon'] ); ?></div>
		<?php endif; ?>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<?php if ( $current_svc ) : ?>
			<p class="md-page-hero__lead"><?php echo esc_html( $current_svc['desc'] ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
/* v3.44.30 · 강제 히어로 이미지 + 임팩트 스탯 (페이지 콘텐츠와 무관하게 항상 노출) */
$_visual = function_exists( 'moondental_service_visual' ) ? moondental_service_visual( $slug ) : null;
if ( $_visual ) :
?>
<section class="md-svc-hero" aria-label="<?php echo esc_attr( $_visual['headline'] ); ?>">
	<div class="md-container">
		<?php if ( ! empty( $_visual['image'] ) ) : ?>
			<figure class="md-svc-hero__figure">
				<img src="<?php echo esc_url( $_visual['image'] ); ?>" alt="<?php echo esc_attr( $_visual['alt'] ); ?>" loading="eager">
			</figure>
		<?php endif; ?>
		<div class="md-svc-hero__body">
			<h2 class="md-svc-hero__headline"><?php echo esc_html( $_visual['headline'] ); ?></h2>
			<p class="md-svc-hero__sub"><?php echo esc_html( $_visual['sub'] ); ?></p>
			<?php if ( ! empty( $_visual['stats'] ) ) : ?>
				<ul class="md-svc-hero__stats" aria-label="핵심 지표">
					<?php foreach ( $_visual['stats'] as $s ) : ?>
						<li>
							<span class="md-svc-hero__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
							<?php if ( ! empty( $s['unit'] ) ) : ?>
								<span class="md-svc-hero__stat-unit"><?php echo esc_html( $s['unit'] ); ?></span>
							<?php endif; ?>
							<span class="md-svc-hero__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>

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
					echo moondental_default_service_content( $slug ); // 안전한 정적 HTML
				}
			endwhile;
			?>
		</article>
	</div>
</section>

<?php
/* === 환자 고민 / 솔루션 6쌍 — bdbddc.com 패턴 참고 === */
if ( function_exists( 'moondental_service_pain_points' ) ) {
	$pp_map = moondental_service_pain_points();
	if ( isset( $pp_map[ $slug ] ) && ! empty( $pp_map[ $slug ] ) ) :
?>
<section class="md-section md-section--surface" aria-label="환자분 고민·솔루션">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_eyebrow', '환자분의 마음' ) : '환자분의 마음' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_title', '혹시 이런 고민 하고 계시죠?' ) : '혹시 이런 고민 하고 계시죠?' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_lead', '많은 환자분이 같은 걱정을 안고 오십니다. 문치과병원이 어떻게 답해드리는지 확인하세요.' ) : '많은 환자분이 같은 걱정을 안고 오십니다.' ) ); ?>
			</p>
		</header>
		<?php
		$pain_tag_q = function_exists( 'md_content' ) ? md_content( 'service_pain_tag_q', '고민' ) : '고민';
		$pain_tag_a = function_exists( 'md_content' ) ? md_content( 'service_pain_tag_a', '문치과의 답' ) : '문치과의 답';
		?>
		<ul class="md-pain">
			<?php foreach ( $pp_map[ $slug ] as $pp ) : ?>
				<li class="md-pain__pair">
					<div class="md-pain__concern">
						<span class="md-pain__tag"><?php echo esc_html( $pain_tag_q ); ?></span>
						<p>"<?php echo esc_html( $pp['concern'] ); ?>"</p>
					</div>
					<div class="md-pain__solution">
						<span class="md-pain__tag md-pain__tag--alt"><?php echo esc_html( $pain_tag_a ); ?></span>
						<p><?php echo esc_html( $pp['solution'] ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; }
?>

<?php /* 강점 카드 섹션은 /기술력-시설/ 페이지로 이동됨 (v3.12.0) */ ?>

<?php
/* === 이런 분께 추천합니다 === */
if ( function_exists( 'moondental_service_ideal_candidates' ) ) {
	$cand_map = moondental_service_ideal_candidates();
	if ( isset( $cand_map[ $slug ] ) && ! empty( $cand_map[ $slug ] ) ) :
?>
<section class="md-section md-section--surface md-section--sm" aria-label="추천 대상">
	<div class="md-container md-container--narrow">
		<div class="md-ideal">
			<header class="md-ideal__head">
				<span class="md-ideal__chip"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_chip', 'For You' ) : 'For You' ); ?></span>
				<h2 class="md-ideal__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_title', '이런 분께 추천합니다' ) : '이런 분께 추천합니다' ); ?></h2>
				<p class="md-ideal__lead">
					<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_lead', '해당하시는 항목이 있으시면 부담 없이 상담받으세요.' ) : '해당하시는 항목이 있으시면 부담 없이 상담받으세요.' ) ); ?>
				</p>
			</header>
			<ul class="md-ideal__list">
				<?php foreach ( $cand_map[ $slug ] as $item ) : ?>
					<li>
						<span class="md-ideal__check" aria-hidden="true">✓</span>
						<span><?php echo esc_html( $item ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php /* v3.37.2 · 중간 CTA 제거 (하단 CTA 배너에 이미 있음) */ ?>
		</div>
	</div>
</section>
<?php endif; }
?>

<?php
/* 해당 service slug의 자동 FAQ 출력 */
if ( function_exists( 'moondental_get_faqs_by_service' ) ) {
	$faqs_map = moondental_get_faqs_by_service();
	if ( isset( $faqs_map[ $slug ] ) && ! empty( $faqs_map[ $slug ] ) ) :
?>
<section class="md-section md-section--sm" aria-label="자주 묻는 질문">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">FAQ</span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'svc_faq_title', '자주 묻는 질문' ) ); ?></h2>
		</header>
		<div class="md-faq">
			<?php foreach ( $faqs_map[ $slug ] as $i => $item ) : ?>
				<details class="md-faq__item"<?php echo $i === 0 ? ' open' : ''; ?>>
					<summary><?php echo esc_html( $item['q'] ); ?></summary>
					<p><?php echo wp_kses_post( md_autolink_addresses( $item['a'] ) ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; }
?>

<section class="md-section md-section--surface md-section--sm" aria-label="다른 진료 영역">
	<div class="md-container">
		<header class="md-section-head">
			<h2 class="md-section-head__title md-section-head__title--sm"><?php echo esc_html( md_content( 'svc_other_title', '다른 진료 영역 보기' ) ); ?></h2>
		</header>
		<div class="md-service-grid">
			<?php
			// 주 메뉴 구조 기반 5개 상위 카테고리 (임플란트/교정/스마일디자인/자연치아/진료과)
			$service_areas   = function_exists( 'moondental_get_service_areas' ) ? moondental_get_service_areas() : array();
			$current_area    = function_exists( 'moondental_service_slug_to_area' ) ? moondental_service_slug_to_area( $slug ) : $slug;
			foreach ( $service_areas as $area ) :
				if ( $area['slug'] === $current_area ) continue;
			?>
				<article class="md-service-card">
					<div class="md-service-card__icon" aria-hidden="true"><?php echo moondental_render_icon( $area['icon'] ); ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $area['title'] ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $area['desc'] ); ?></p>
					<a class="md-service-card__link" href="<?php echo esc_url( $area['url'] ); ?>"><?php echo esc_html( $area['title'] ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
