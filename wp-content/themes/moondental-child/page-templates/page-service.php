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
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a>
			 ▸ <a href="<?php echo esc_url( home_url( '/services/' ) ); ?>">진료안내</a>
			 ▸ <span><?php the_title(); ?></span>
		</nav>
		<?php if ( $current_svc ) : ?>
			<div style="font-size:3rem; margin-bottom:12px;" aria-hidden="true"><?php echo $current_svc['icon']; ?></div>
		<?php endif; ?>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<?php if ( $current_svc ) : ?>
			<p class="md-page-hero__lead"><?php echo esc_html( $current_svc['desc'] ); ?></p>
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

<?php
/* === 종합치과병원 vs 일반 치과의원 비교표 — 객관적 차이 === */
if ( function_exists( 'moondental_clinic_comparison' ) ) {
	$cmp = moondental_clinic_comparison();
?>
<section class="md-section" aria-label="치과병원과 일반 치과의 차이">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_compare_eyebrow', 'Difference' ) : 'Difference' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_compare_title', '치과병원과 일반 치과의 차이' ) : '치과병원과 일반 치과의 차이' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'service_compare_lead', '의료기관 종별·시설·운영의 객관적 차이입니다.' ) : '의료기관 종별·시설·운영의 객관적 차이입니다.' ) ); ?>
			</p>
		</header>
		<div class="md-compare">
			<div class="md-compare__head">
				<div class="md-compare__cell md-compare__cell--label">구분</div>
				<div class="md-compare__cell md-compare__cell--us">문치과병원 (치과병원)</div>
				<div class="md-compare__cell md-compare__cell--them">일반 치과의원</div>
			</div>
			<?php foreach ( $cmp as $row ) : ?>
				<div class="md-compare__row">
					<div class="md-compare__cell md-compare__cell--label"><?php echo esc_html( $row['label'] ); ?></div>
					<div class="md-compare__cell md-compare__cell--us"><strong><?php echo esc_html( $row['hospital'] ); ?></strong></div>
					<div class="md-compare__cell md-compare__cell--them"><?php echo esc_html( $row['clinic'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="md-compare__note">
			ⓘ <?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_compare_note', '일반 치과의원의 시설·운영은 의원별로 다를 수 있으며, 위 비교는 일반적 기준입니다.' ) : '일반 치과의원의 시설·운영은 의원별로 다를 수 있으며, 위 비교는 일반적 기준입니다.' ); ?>
		</p>
	</div>
</section>
<?php }
?>

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
			<div class="md-ideal__cta">
				<?php echo md_render_reservation_ctas( array( 'track' => 'cta-service-ideal', 'size' => '', 'align' => 'start' ) ); ?>
			</div>
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
			<h2 class="md-section-head__title">자주 묻는 질문</h2>
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
			<h2 class="md-section-head__title" style="font-size:var(--fs-h3);">다른 진료 영역 보기</h2>
		</header>
		<div class="md-service-grid">
			<?php foreach ( $services as $svc ) :
				if ( $svc['slug'] === $slug ) continue;
				$page = get_page_by_path( $svc['slug'] );
				$url  = $page ? get_permalink( $page ) : home_url( '/services/#' . $svc['slug'] );
			?>
				<article class="md-service-card">
					<div class="md-service-card__icon" aria-hidden="true"><?php echo $svc['icon']; ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $svc['title'] ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $svc['desc'] ); ?></p>
					<a class="md-service-card__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $svc['title'] ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
