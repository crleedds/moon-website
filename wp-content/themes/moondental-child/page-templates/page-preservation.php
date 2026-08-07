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

/* v3.33.8 · WP 페이지 편집기 본문 있으면 그것을 우선 렌더 (Customizer 무시)
 *  wp-admin → 페이지 → 자연치아 살리기 → 본문 입력 후 저장하면 여기 표시. */
if ( function_exists( 'moondental_render_page_body_override' ) && moondental_render_page_body_override() ) {
	get_footer();
	return;
}

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
$pulpcap_when_list  = md_parse_lines( md_content( 'preservation_pulpcap_when_list', '' ) );
$pulpcap_strength_pair = $parse_pair( md_content( 'preservation_pulpcap_strength_cards', '' ) );
$endo_when_list     = md_parse_lines( md_content( 'preservation_endo_when_list', '' ) );
$endo_strength_pair = $parse_pair( md_content( 'preservation_endo_strength_cards', '' ) );
$perio_cards        = $parse_cards( md_content( 'preservation_perio_cards', '' ) );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--preservation">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸ <span><?php echo esc_html( get_the_title() ?: '자연치아 살리기' ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow"><?php echo esc_html( md_content( 'preservation_hero_eyebrow', '' ) ); ?></span>
		<?php
		$_pres_floor = function_exists( 'moondental_slug_floor' ) ? moondental_slug_floor( '자연치아-살리기' ) : '';
		if ( $_pres_floor ) :
		?>
			<span class="md-service-floor-badge" aria-label="위치"><span aria-hidden="true">📍</span> 문타워 <?php echo esc_html( $_pres_floor ); ?> · 보존과</span>
		<?php endif; ?>
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
			<?php foreach ( $cavity_cards as $c ) :
				// v3.44.70 · ★ 로 시작하는 stage 는 문치과병원 특별 술식 강조 카드
				$_is_star = ( isset( $c['stage'] ) && strpos( trim( $c['stage'] ), '★' ) === 0 );
				$_card_class = 'md-preservation-card' . ( $_is_star ? ' is-star' : '' );
			?>
			<article class="<?php echo esc_attr( $_card_class ); ?>">
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
			<?php if ( $cvb ) :
				// v3.44.72 · 본문이 이미 <p>/<ul> 등으로 시작하면 outer <p> 래핑 생략
				$cvb_trim = ltrim( $cvb );
				$has_block = ( strpos( $cvb_trim, '<p' ) === 0 || strpos( $cvb_trim, '<ul' ) === 0 || strpos( $cvb_trim, '<ol' ) === 0 || strpos( $cvb_trim, '<div' ) === 0 );
			?>
				<?php if ( $has_block ) : ?>
					<div class="md-preservation-callout__body"><?php echo wp_kses_post( $cvb ); ?></div>
				<?php else : ?>
					<p><?php echo wp_kses_post( $cvb ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 1.5 치수복조술 · v3.44.107 ============ -->
<?php
$pcp_eb   = md_content( 'preservation_pulpcap_eyebrow', '' );
$pcp_ttl  = md_content( 'preservation_pulpcap_title', '' );
$pcp_lead = md_content( 'preservation_pulpcap_lead', '' );
if ( $pcp_eb || $pcp_ttl || $pcp_lead || $pulpcap_when_list || $pulpcap_strength_pair ) :
?>
<section class="md-section md-section--pulpcap" id="pulpcap">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<?php if ( $pcp_eb )   : ?><span class="md-section-head__eyebrow md-eyebrow--star"><?php echo esc_html( $pcp_eb ); ?></span><?php endif; ?>
			<?php if ( $pcp_ttl )  : ?><h2 class="md-section-head__title"><?php echo esc_html( $pcp_ttl ); ?></h2><?php endif; ?>
			<?php if ( $pcp_lead ) : ?><p class="md-section-head__lead"><?php echo esc_html( $pcp_lead ); ?></p><?php endif; ?>
		</header>

		<?php if ( $pulpcap_when_list ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( md_content( 'preservation_pulpcap_when_title', '' ) ); ?></h3>
		<ul class="md-preservation-list">
			<?php foreach ( $pulpcap_when_list as $item ) : ?>
				<li><?php echo wp_kses_post( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<?php if ( $pulpcap_strength_pair ) : ?>
		<h3 class="md-preservation-h3"><?php echo esc_html( md_content( 'preservation_pulpcap_strength_title', '' ) ); ?></h3>
		<div class="md-preservation-grid">
			<?php foreach ( $pulpcap_strength_pair as $s ) : ?>
			<article class="md-preservation-card is-star">
				<h3><?php echo esc_html( $s['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $s['body'] ); ?></p>
			</article>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php
		$pcct = md_content( 'preservation_pulpcap_callout_title', '' );
		$pccb = md_content( 'preservation_pulpcap_callout_body', '' );
		if ( $pcct || $pccb ) : ?>
		<aside class="md-preservation-callout md-preservation-callout--star">
			<?php if ( $pcct ) : ?><strong><?php echo esc_html( $pcct ); ?></strong><?php endif; ?>
			<?php if ( $pccb ) : ?><p><?php echo wp_kses_post( $pccb ); ?></p><?php endif; ?>
		</aside>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

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

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
