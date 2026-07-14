<?php
/**
 * Template Name: 자연치아 살리기 (충치·신경·잇몸 3섹션 앵커)
 * Template Post Type: page
 *
 * /자연치아-살리기/ 페이지 — 충치치료·신경치료·잇몸치료 3개 섹션을
 * 한 페이지에서 스크롤하며 볼 수 있는 종합 페이지.
 *
 *  앵커: #cavity (충치), #endo (신경), #perio (잇몸)
 *
 *  v3.32.0: 모든 텍스트를 Customizer에서 편집 가능하게 이관.
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );

/* 카드 파서 · 한 줄에 "stage | title | body" 형식 (HTML 허용) */
$parse_cards = function( $text ) {
	$out = array();
	foreach ( md_parse_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 2 ) {
			$out[] = array(
				'stage' => $parts[0] ?? '',
				'title' => $parts[1] ?? '',
				'body'  => $parts[2] ?? '',
			);
		}
	}
	return $out;
};
/* 강점 카드 파서 · "icon+title | body" */
$parse_pair = function( $text ) {
	$out = array();
	foreach ( md_parse_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 2 ) {
			$out[] = array( 'title' => $parts[0], 'body' => $parts[1] );
		}
	}
	return $out;
};
/* 앵커 네비 파서 · "icon | label | anchor" */
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

$nav_items          = $parse_nav( md_content( 'preservation_nav_items', '' ) );
$cavity_cards       = $parse_cards( md_content( 'preservation_cavity_cards', '' ) );
$endo_when_list     = md_parse_lines( md_content( 'preservation_endo_when_list', '' ) );
$endo_strength_pair = $parse_pair( md_content( 'preservation_endo_strength_cards', '' ) );
$perio_cards        = $parse_cards( md_content( 'preservation_perio_cards', '' ) );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--preservation">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php echo esc_html( get_the_title() ?: '자연치아 살리기' ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow"><?php echo esc_html( md_content( 'preservation_hero_eyebrow', '' ) ); ?></span>
		<h1 class="md-page-hero__title">
			<?php echo esc_html( md_content( 'preservation_hero_title_a', '' ) ); ?><br>
			<em><?php echo esc_html( md_content( 'preservation_hero_title_b', '' ) ); ?></em>
		</h1>
		<p class="md-page-hero__lead"><?php echo nl2br( esc_html( md_content( 'preservation_hero_lead', '' ) ) ); ?></p>
	</div>
</section>

<!-- ============ 앵커 네비게이션 ============ -->
<?php if ( $nav_items ) : ?>
<nav class="md-preservation-nav" aria-label="자연치아 살리기 섹션 이동">
	<div class="md-container">
		<ul>
			<?php foreach ( $nav_items as $n ) : ?>
				<li><a href="<?php echo esc_attr( $n['anchor'] ); ?>"><span aria-hidden="true"><?php echo esc_html( $n['icon'] ); ?></span> <?php echo esc_html( $n['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>
<?php endif; ?>

<!-- ============ 1. 충치치료 ============ -->
<section class="md-section md-section--surface" id="cavity">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'preservation_cavity_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'preservation_cavity_title', '' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'preservation_cavity_lead', '' ) ); ?></p>
		</header>

		<?php if ( $cavity_cards ) : ?>
		<div class="md-preservation-grid">
			<?php foreach ( $cavity_cards as $c ) : ?>
			<article class="md-preservation-card">
				<?php if ( $c['stage'] !== '' ) : ?><span class="md-preservation-card__stage"><?php echo esc_html( $c['stage'] ); ?></span><?php endif; ?>
				<h3><?php echo esc_html( $c['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $c['body'] ); ?></p>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php
		$cvt = md_content( 'preservation_cavity_callout_title', '' );
		$cvb = md_content( 'preservation_cavity_callout_body', '' );
		if ( $cvt || $cvb ) : ?>
		<aside class="md-preservation-callout">
			<?php if ( $cvt ) : ?><strong><?php echo esc_html( $cvt ); ?></strong><?php endif; ?>
			<?php if ( $cvb ) : ?><p><?php echo wp_kses_post( $cvb ); ?></p><?php endif; ?>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 2. 신경치료 ============ -->
<section class="md-section" id="endo">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'preservation_endo_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'preservation_endo_title', '' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'preservation_endo_lead', '' ) ); ?></p>
		</header>

		<?php if ( $endo_when_list ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( md_content( 'preservation_endo_when_title', '' ) ); ?></h3>
		<ul class="md-preservation-list">
			<?php foreach ( $endo_when_list as $item ) : ?>
				<li><?php echo wp_kses_post( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<?php if ( $endo_strength_pair ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( md_content( 'preservation_endo_strength_title', '' ) ); ?></h3>
		<div class="md-preservation-grid">
			<?php foreach ( $endo_strength_pair as $s ) : ?>
			<article class="md-preservation-card">
				<h3><?php echo esc_html( $s['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $s['body'] ); ?></p>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php
		$et = md_content( 'preservation_endo_callout_title', '' );
		$eb = md_content( 'preservation_endo_callout_body', '' );
		if ( $et || $eb ) : ?>
		<aside class="md-preservation-callout">
			<?php if ( $et ) : ?><strong><?php echo esc_html( $et ); ?></strong><?php endif; ?>
			<?php if ( $eb ) : ?><p><?php echo wp_kses_post( $eb ); ?></p><?php endif; ?>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 3. 잇몸치료 ============ -->
<section class="md-section md-section--surface" id="perio">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'preservation_perio_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'preservation_perio_title', '' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'preservation_perio_lead', '' ) ); ?></p>
		</header>

		<?php if ( $perio_cards ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( md_content( 'preservation_perio_h3', '' ) ); ?></h3>
		<div class="md-preservation-grid">
			<?php foreach ( $perio_cards as $c ) : ?>
			<article class="md-preservation-card">
				<?php if ( $c['stage'] !== '' ) : ?><span class="md-preservation-card__stage"><?php echo esc_html( $c['stage'] ); ?></span><?php endif; ?>
				<h3><?php echo esc_html( $c['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $c['body'] ); ?></p>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php
		$pt = md_content( 'preservation_perio_callout_title', '' );
		$pb = md_content( 'preservation_perio_callout_body', '' );
		if ( $pt || $pb ) : ?>
		<aside class="md-preservation-callout">
			<?php if ( $pt ) : ?><strong><?php echo esc_html( $pt ); ?></strong><?php endif; ?>
			<?php if ( $pb ) : ?><p><?php echo wp_kses_post( $pb ); ?></p><?php endif; ?>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ CTA ============ -->
<section class="md-section md-section--sm">
	<div class="md-container md-container--narrow">
		<div class="md-region-cta">
			<?php $chip = md_content( 'preservation_cta_chip', '' ); if ( $chip ) : ?>
				<span class="md-region-cta__chip"><?php echo esc_html( $chip ); ?></span>
			<?php endif; ?>
			<h2 class="md-region-cta__title"><?php echo nl2br( esc_html( md_content( 'preservation_cta_title', '' ) ) ); ?></h2>
			<p class="md-region-cta__lead"><?php echo esc_html( md_content( 'preservation_cta_lead', '' ) ); ?></p>
			<?php echo md_render_reservation_ctas( array( 'track' => 'cta-preservation', 'size' => 'lg', 'align' => 'center' ) ); ?>
		</div>
	</div>
</section>

<?php
get_footer();
