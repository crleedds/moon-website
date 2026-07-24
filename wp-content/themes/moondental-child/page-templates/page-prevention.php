<?php
/**
 * Template Name: 예방클리닉 · 덴탈SPA
 * Template Post Type: page
 *
 * /예방클리닉/ 페이지 — 덴탈SPA·스케일링·에어플로우·불소도포·실란트 등
 * 충치·치주염 발생 전에 막는 예방 진료를 종합.
 *
 * v3.32.2: 모든 텍스트를 Customizer에서 편집 가능하게 이관.
 *
 * @package moondental-child
 */

get_header();

/* v3.33.8 · WP 페이지 본문 오버라이드 우선 */
if ( function_exists( 'moondental_render_page_body_override' ) && moondental_render_page_body_override() ) {
	get_footer();
	return;
}

/* 카드 파서 · 2 파트 (title | body) 또는 3 파트 (stage | title | body) */
$parse_cards = function( $text ) {
	$out = array();
	foreach ( md_parse_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) === 2 ) {
			$out[] = array( 'stage' => '', 'title' => $parts[0], 'body' => $parts[1] );
		} elseif ( count( $parts ) >= 3 ) {
			$out[] = array( 'stage' => $parts[0], 'title' => $parts[1], 'body' => $parts[2] );
		}
	}
	return $out;
};
$parse_nav = function( $text ) {
	$out = array();
	foreach ( md_parse_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 3 ) {
			$out[] = array( 'icon' => $parts[0], 'label' => $parts[1], 'anchor' => $parts[2] );
		}
	}
	return $out;
};

$render_grid_section = function( $slug ) use ( $parse_cards ) {
	$eyebrow = md_content( "prevention_{$slug}_eyebrow", '' );
	$title   = md_content( "prevention_{$slug}_title", '' );
	$lead    = md_content( "prevention_{$slug}_lead", '' );
	$cards   = $parse_cards( md_content( "prevention_{$slug}_cards", '' ) );
	$ct      = md_content( "prevention_{$slug}_callout_title", '' );
	$cb      = md_content( "prevention_{$slug}_callout_body", '' );
	?>
	<header class="md-section-head">
		<?php if ( $eyebrow ) : ?><span class="md-section-head__eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
		<h2 class="md-section-head__title"><?php echo esc_html( $title ); ?></h2>
		<?php if ( $lead ) : ?><p class="md-section-head__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
	</header>
	<?php if ( $cards ) : ?>
	<div class="md-preservation-grid">
		<?php foreach ( $cards as $c ) : ?>
		<article class="md-preservation-card">
			<?php if ( $c['stage'] !== '' ) : ?><span class="md-preservation-card__stage"><?php echo esc_html( $c['stage'] ); ?></span><?php endif; ?>
			<h3><?php echo esc_html( $c['title'] ); ?></h3>
			<p><?php echo wp_kses_post( $c['body'] ); ?></p>
		</article>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	<?php if ( $ct || $cb ) : ?>
	<aside class="md-preservation-callout">
		<?php if ( $ct ) : ?><strong><?php echo esc_html( $ct ); ?></strong><?php endif; ?>
		<?php if ( $cb ) : ?><p><?php echo wp_kses_post( $cb ); ?></p><?php endif; ?>
	</aside>
	<?php endif; ?>
	<?php
};

$nav_items = $parse_nav( md_content( 'prevention_nav_items', '' ) );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--prevention">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸ <span><?php echo esc_html( get_the_title() ?: '예방클리닉' ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow"><?php echo esc_html( md_content( 'prevention_hero_eyebrow', '' ) ); ?></span>
		<h1 class="md-page-hero__title">
			<?php echo esc_html( md_content( 'prevention_hero_title_a', '' ) ); ?><br>
			<em><?php echo esc_html( md_content( 'prevention_hero_title_b', '' ) ); ?></em>
		</h1>
		<p class="md-page-hero__lead"><?php echo nl2br( esc_html( md_content( 'prevention_hero_lead', '' ) ) ); ?></p>
	</div>
</section>

<!-- ============ 앵커 네비 ============ -->
<?php if ( $nav_items ) : ?>
<nav class="md-preservation-nav" aria-label="예방 진료 섹션">
	<div class="md-container">
		<ul>
			<?php foreach ( $nav_items as $n ) : ?>
				<li><a href="<?php echo esc_attr( $n['anchor'] ); ?>"><span aria-hidden="true"><?php echo esc_html( $n['icon'] ); ?></span> <?php echo esc_html( $n['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>
<?php endif; ?>

<!-- ============ 01 덴탈 SPA (steps 형식) · v3.40.1 · 대폭 확장 ============ -->
<section class="md-section md-section--surface" id="dental-spa">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<?php if ( md_content( 'prevention_spa_eyebrow', '' ) ) : ?><span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'prevention_spa_eyebrow', '' ) ); ?></span><?php endif; ?>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'prevention_spa_title', '' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo nl2br( esc_html( md_content( 'prevention_spa_lead', '' ) ) ); ?></p>
		</header>

		<?php /* v3.40.1 · '왜 덴탈SPA?' — 스켈링만으로 부족한 이유 */
		$why_q = md_content( 'prevention_spa_why_question', '' );
		$why_body = md_content( 'prevention_spa_why_body', '' );
		if ( $why_q || $why_body ) : ?>
		<div class="md-spa-why">
			<?php if ( $why_q ) : ?>
				<h3 class="md-spa-why__q">❓ <?php echo esc_html( $why_q ); ?></h3>
			<?php endif; ?>
			<?php if ( $why_body ) : ?>
				<div class="md-spa-why__body"><?php echo wp_kses_post( wpautop( $why_body ) ); ?></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php /* v3.40.1 · 구강세척 3단계 다이어그램 */
		$oral_title = md_content( 'prevention_spa_oral_title', '' );
		$oral_steps = $parse_cards( md_content( 'prevention_spa_oral_steps', '' ) );
		if ( $oral_steps ) : ?>
		<div class="md-spa-oral">
			<?php if ( $oral_title ) : ?><h3 class="md-preservation-h3"><?php echo esc_html( $oral_title ); ?></h3><?php endif; ?>
			<ol class="md-spa-oral__list">
				<?php $ns = count( $oral_steps ); foreach ( $oral_steps as $i => $s ) : ?>
				<li class="md-spa-oral__item">
					<span class="md-spa-oral__num"><?php echo (int) ( $i + 1 ); ?></span>
					<strong class="md-spa-oral__name"><?php echo esc_html( $s['title'] ); ?></strong>
					<?php if ( $s['body'] ) : ?><span class="md-spa-oral__desc"><?php echo esc_html( $s['body'] ); ?></span><?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php endif; ?>

		<?php /* 덴탈SPA 상세 절차 (기존 steps 유지) */
		$steps_title = md_content( 'prevention_spa_steps_title', '' );
		$steps = $parse_cards( md_content( 'prevention_spa_steps', '' ) );
		if ( $steps ) : ?>
			<h3 class="md-preservation-h3"><?php echo esc_html( $steps_title ); ?></h3>
			<ol class="md-preservation-steps">
				<?php foreach ( $steps as $s ) : ?>
				<li>
					<strong><?php echo esc_html( $s['title'] ); ?></strong>
					<?php if ( $s['body'] ) : ?><p><?php echo wp_kses_post( $s['body'] ); ?></p><?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<?php /* v3.40.1 · 이런 분께 필요합니다 (candidate list) */
		$who_title = md_content( 'prevention_spa_who_title', '' );
		$who_lead  = md_content( 'prevention_spa_who_lead', '' );
		$who_items = md_parse_lines( md_content( 'prevention_spa_who_items', '' ) );
		if ( $who_items ) : ?>
		<div class="md-spa-who">
			<?php if ( $who_title ) : ?><h3 class="md-preservation-h3"><?php echo esc_html( $who_title ); ?></h3><?php endif; ?>
			<?php if ( $who_lead ) : ?><p class="md-spa-who__lead"><?php echo esc_html( $who_lead ); ?></p><?php endif; ?>
			<ul class="md-spa-who__list">
				<?php foreach ( $who_items as $item ) : ?>
					<li><span aria-hidden="true">✓</span> <?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php $who_cta = md_content( 'prevention_spa_who_cta', '' );
			if ( $who_cta ) : ?>
				<p class="md-spa-who__cta"><?php echo esc_html( $who_cta ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php /* v3.40.1 · 치료 후 관리 · 임플란트/잇몸치료 이후 SPA */
		$after_title = md_content( 'prevention_spa_after_title', '' );
		$after_body  = md_content( 'prevention_spa_after_body', '' );
		$after_items = md_parse_lines( md_content( 'prevention_spa_after_items', '' ) );
		if ( $after_title || $after_body || $after_items ) : ?>
		<div class="md-spa-after">
			<?php if ( $after_title ) : ?><h3 class="md-preservation-h3"><?php echo esc_html( $after_title ); ?></h3><?php endif; ?>
			<?php if ( $after_body ) : ?><p class="md-spa-after__body"><?php echo nl2br( esc_html( $after_body ) ); ?></p><?php endif; ?>
			<?php if ( $after_items ) : ?>
			<ul class="md-spa-after__list">
				<?php foreach ( $after_items as $ai ) : ?><li>▪ <?php echo esc_html( $ai ); ?></li><?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<?php $after_cta = md_content( 'prevention_spa_after_cta', '' );
			if ( $after_cta ) : ?>
				<p class="md-spa-after__cta"><?php echo esc_html( $after_cta ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php /* v3.40.1 · 진료실 ↔ 예방클리닉 이동 흐름 */
		$flow_title = md_content( 'prevention_spa_flow_title', '' );
		$flow_lead  = md_content( 'prevention_spa_flow_lead', '' );
		$flow_steps = $parse_cards( md_content( 'prevention_spa_flow_steps', '' ) );
		if ( $flow_steps ) : ?>
		<div class="md-spa-flow">
			<?php if ( $flow_title ) : ?><h3 class="md-preservation-h3"><?php echo esc_html( $flow_title ); ?></h3><?php endif; ?>
			<?php if ( $flow_lead ) : ?><p class="md-spa-flow__lead"><?php echo esc_html( $flow_lead ); ?></p><?php endif; ?>
			<ol class="md-spa-flow__list">
				<?php foreach ( $flow_steps as $fs ) :
					$loc = trim( $fs['stage'] );
					$loc_class = ( strpos( $loc, '예방' ) !== false ) ? ' md-spa-flow__item--spa' : ' md-spa-flow__item--clinic';
				?>
				<li class="md-spa-flow__item<?php echo esc_attr( $loc_class ); ?>">
					<span class="md-spa-flow__loc"><?php echo esc_html( $loc ); ?></span>
					<strong class="md-spa-flow__name"><?php echo esc_html( $fs['title'] ); ?></strong>
					<?php if ( $fs['body'] ) : ?><span class="md-spa-flow__desc"><?php echo esc_html( $fs['body'] ); ?></span><?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php endif; ?>

		<?php /* v3.40.1 · 환자 후기 */
		$test_title = md_content( 'prevention_spa_test_title', '' );
		$test_items = md_parse_lines( md_content( 'prevention_spa_testimonials', '' ) );
		if ( $test_items ) : ?>
		<div class="md-spa-test">
			<?php if ( $test_title ) : ?><h3 class="md-preservation-h3"><?php echo esc_html( $test_title ); ?></h3><?php endif; ?>
			<ul class="md-spa-test__list">
				<?php foreach ( $test_items as $t ) : ?>
					<li class="md-spa-test__item">
						<span class="md-spa-test__quote" aria-hidden="true">“</span>
						<p><?php echo esc_html( $t ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>

		<?php /* 기존 callout · 유지 */
		$sct = md_content( 'prevention_spa_callout_title', '' );
		$scb = md_content( 'prevention_spa_callout_body', '' );
		if ( $sct || $scb ) : ?>
		<aside class="md-preservation-callout">
			<?php if ( $sct ) : ?><strong><?php echo esc_html( $sct ); ?></strong><?php endif; ?>
			<?php if ( $scb ) : ?><p><?php echo wp_kses_post( $scb ); ?></p><?php endif; ?>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 02 스케일링 ============ -->
<section class="md-section" id="scaling">
	<div class="md-container md-container--narrow"><?php $render_grid_section( 'scaling' ); ?></div>
</section>

<!-- ============ 03 에어플로우 ============ -->
<section class="md-section md-section--surface" id="airflow">
	<div class="md-container md-container--narrow"><?php $render_grid_section( 'airflow' ); ?></div>
</section>

<!-- ============ 04 불소도포 ============ -->
<section class="md-section" id="fluoride">
	<div class="md-container md-container--narrow"><?php $render_grid_section( 'fluoride' ); ?></div>
</section>

<!-- ============ 05 실란트 ============ -->
<section class="md-section md-section--surface" id="sealant">
	<div class="md-container md-container--narrow"><?php $render_grid_section( 'sealant' ); ?></div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
