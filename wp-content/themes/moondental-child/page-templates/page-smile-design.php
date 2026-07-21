<?php
/**
 * Template Name: 스마일디자인센터 (라미네이트·미백·심미)
 * Template Post Type: page
 *
 * /스마일디자인센터/ 페이지 — 라미네이트·심미레진·치아미백·잇몸미백·거미스마일 등
 * 심미치과 진료를 한 페이지에 종합.
 *
 *  앵커: #laminate / #aesthetic-resin / #whitening / #gum-whitening / #gummy
 *
 *  v3.32.1: 모든 텍스트를 Customizer에서 편집 가능하게 이관.
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

$render_section = function( $slug, $has_h3 = false ) use ( $parse_cards ) {
	$eyebrow = md_content( "smile_{$slug}_eyebrow", '' );
	$title   = md_content( "smile_{$slug}_title", '' );
	$lead    = md_content( "smile_{$slug}_lead", '' );
	$cards   = $parse_cards( md_content( "smile_{$slug}_cards", '' ) );
	$h3      = $has_h3 ? md_content( "smile_{$slug}_h3", '' ) : '';
	$ct      = md_content( "smile_{$slug}_callout_title", '' );
	$cb      = md_content( "smile_{$slug}_callout_body", '' );
	?>
	<header class="md-section-head">
		<?php if ( $eyebrow ) : ?><span class="md-section-head__eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
		<h2 class="md-section-head__title"><?php echo esc_html( $title ); ?></h2>
		<?php if ( $lead ) : ?><p class="md-section-head__lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
	</header>
	<?php if ( $has_h3 && $h3 ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( $h3 ); ?></h3>
	<?php endif; ?>
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

$nav_items = $parse_nav( md_content( 'smile_nav_items', '' ) );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--smile">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php echo esc_html( get_the_title() ?: '스마일디자인센터' ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow"><?php echo esc_html( md_content( 'smile_hero_eyebrow', '' ) ); ?></span>
		<h1 class="md-page-hero__title">
			<?php echo esc_html( md_content( 'smile_hero_title_a', '' ) ); ?><br>
			<em><?php echo esc_html( md_content( 'smile_hero_title_b', '' ) ); ?></em>
		</h1>
		<p class="md-page-hero__lead"><?php echo nl2br( esc_html( md_content( 'smile_hero_lead', '' ) ) ); ?></p>
	</div>
</section>

<!-- ============ 앵커 네비 ============ -->
<?php if ( $nav_items ) : ?>
<nav class="md-preservation-nav" aria-label="스마일디자인 섹션 이동">
	<div class="md-container">
		<ul>
			<?php foreach ( $nav_items as $n ) : ?>
				<li><a href="<?php echo esc_attr( $n['anchor'] ); ?>"><span aria-hidden="true"><?php echo esc_html( $n['icon'] ); ?></span> <?php echo esc_html( $n['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>
<?php endif; ?>

<!-- ============ 01 라미네이트 ============ -->
<section class="md-section md-section--surface" id="laminate">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<?php if ( md_content( 'smile_laminate_eyebrow', '' ) ) : ?><span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'smile_laminate_eyebrow', '' ) ); ?></span><?php endif; ?>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'smile_laminate_title', '' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'smile_laminate_lead', '' ) ); ?></p>
		</header>

		<?php $lam_cards = $parse_cards( md_content( 'smile_laminate_cards', '' ) ); ?>
		<?php if ( $lam_cards ) : ?>
		<div class="md-preservation-grid">
			<?php foreach ( $lam_cards as $c ) : ?>
			<article class="md-preservation-card">
				<h3><?php echo esc_html( $c['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $c['body'] ); ?></p>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php
		$reco_title = md_content( 'smile_laminate_reco_title', '' );
		$reco_list  = md_parse_lines( md_content( 'smile_laminate_reco_list', '' ) );
		if ( $reco_list ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( $reco_title ); ?></h3>
		<ul class="md-preservation-list">
			<?php foreach ( $reco_list as $item ) : ?>
				<li><?php echo wp_kses_post( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<?php
		$lct = md_content( 'smile_laminate_callout_title', '' );
		$lcb = md_content( 'smile_laminate_callout_body', '' );
		if ( $lct || $lcb ) : ?>
		<aside class="md-preservation-callout">
			<?php if ( $lct ) : ?><strong><?php echo esc_html( $lct ); ?></strong><?php endif; ?>
			<?php if ( $lcb ) : ?><p><?php echo wp_kses_post( $lcb ); ?></p><?php endif; ?>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 02 심미레진 ============ -->
<section class="md-section" id="aesthetic-resin">
	<div class="md-container md-container--narrow">
		<?php $render_section( 'resin' ); ?>
	</div>
</section>

<!-- ============ 03 치아미백 ============ -->
<section class="md-section md-section--surface" id="whitening">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<?php if ( md_content( 'smile_white_eyebrow', '' ) ) : ?><span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'smile_white_eyebrow', '' ) ); ?></span><?php endif; ?>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'smile_white_title', '' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'smile_white_lead', '' ) ); ?></p>
		</header>

		<?php $white_cards = $parse_cards( md_content( 'smile_white_cards', '' ) ); ?>
		<?php if ( $white_cards ) : ?>
		<div class="md-preservation-grid">
			<?php foreach ( $white_cards as $c ) : ?>
			<article class="md-preservation-card">
				<?php if ( $c['stage'] !== '' ) : ?><span class="md-preservation-card__stage"><?php echo esc_html( $c['stage'] ); ?></span><?php endif; ?>
				<h3><?php echo esc_html( $c['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $c['body'] ); ?></p>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php
		$note_title = md_content( 'smile_white_note_title', '' );
		$note_list  = md_parse_lines( md_content( 'smile_white_note_list', '' ) );
		if ( $note_list ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( $note_title ); ?></h3>
		<ul class="md-preservation-list">
			<?php foreach ( $note_list as $item ) : ?>
				<li><?php echo wp_kses_post( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 04 잇몸미백 ============ -->
<section class="md-section" id="gum-whitening">
	<div class="md-container md-container--narrow">
		<?php $render_section( 'gum' ); ?>
	</div>
</section>

<!-- ============ 05 거미스마일 ============ -->
<section class="md-section md-section--surface" id="gummy">
	<div class="md-container md-container--narrow">
		<?php $render_section( 'gummy', true ); ?>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
