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
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php echo esc_html( get_the_title() ?: '예방클리닉' ); ?></span>
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

<!-- ============ 01 덴탈 SPA (steps 형식) ============ -->
<section class="md-section md-section--surface" id="dental-spa">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<?php if ( md_content( 'prevention_spa_eyebrow', '' ) ) : ?><span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'prevention_spa_eyebrow', '' ) ); ?></span><?php endif; ?>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'prevention_spa_title', '' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'prevention_spa_lead', '' ) ); ?></p>
		</header>

		<?php
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

		<?php
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
