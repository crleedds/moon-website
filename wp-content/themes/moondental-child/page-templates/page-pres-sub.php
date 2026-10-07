<?php
/**
 * Template Name: 자연치아보존센터 세부 진료
 * Template Post Type: page
 *
 * v8 · 원장 지시 · 자연치아보존센터 메뉴 → 각각 다른 페이지
 *  /자연치아-살리기/{충치치료|부분신경치료|신경치료|스케일링-잇몸치료|덴탈spa}/
 *  머리(본 페이지와 같은 모양) → 센터 안 이동 막대 → 본문(template-parts/pres-v5/{key}.php) → 다른 보존 치료 → 안내서 · 예약
 *  외국어 · 예전 디자인에서는 본 페이지의 해당 앵커로 보낸다.
 *
 * @package moondental-child
 */
$key   = function_exists( 'md_pres_sub_current' ) ? md_pres_sub_current() : '';
$pages = function_exists( 'md_pres_sub_pages' ) ? md_pres_sub_pages() : array();
if ( ! $key || ! md_pres_sub_on() ) {
	$anchor = ( $key && ! empty( $pages[ $key ]['anchor'] ) ) ? '#' . $pages[ $key ]['anchor'] : '';
	wp_safe_redirect( home_url( '/' . MD_PRES_PARENT . '/' ) . $anchor, 302 );
	exit;
}
$p = $pages[ $key ];

get_header();
$parent_url = home_url( '/' . MD_PRES_PARENT . '/' );
$_floor     = function_exists( 'moondental_slug_floor' ) ? moondental_slug_floor( MD_PRES_PARENT ) : '';
?>

<section class="md-page-hero md-page-hero--preservation">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸ <a href="<?php echo esc_url( $parent_url ); ?>">자연치아보존센터</a> ▸ <span><?php echo esc_html( $p['title'] ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow">자연치아보존센터</span>
		<?php if ( $_floor ) : ?>
			<span class="md-service-floor-badge" aria-label="위치"><span aria-hidden="true">📍</span> 문타워 <?php echo esc_html( $_floor ); ?> · 자연치아보존센터</span>
		<?php endif; ?>
		<h1 class="md-page-hero__title"><?php echo esc_html( $p['h1a'] ); ?><br><em><?php echo esc_html( $p['h1b'] ); ?></em></h1>
		<p class="md-page-hero__lead"><?php echo esc_html( $p['lead'] ); ?></p>
	</div>
</section>

<nav class="md-preservation-nav" aria-label="자연치아보존센터 진료 이동">
	<div class="md-container">
		<ul>
			<li><a href="<?php echo esc_url( $parent_url ); ?>">센터 안내</a></li>
			<?php foreach ( $pages as $k => $q ) : ?>
				<li><a href="<?php echo esc_url( md_pres_sub_url( $k ) ); ?>"<?php echo $k === $key ? ' aria-current="page" class="is-current"' : ''; ?>><?php echo esc_html( $q['menu'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>

<div class="v5-impl">
<?php get_template_part( 'template-parts/pres-v5/' . $key ); ?>

<section class="v5-blk v5-paper" id="pres-more" aria-label="다른 보존 치료">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">More</span>
			<div><h2>자연치아보존센터의 다른 진료</h2><p>가장 작은 개입부터 차례로 — 충치 · 신경 · 잇몸 치료를 단계별로 이어 가고, 끝나면 덴탈SPA로 관리합니다.</p></div>
		</div>
		<ol class="v5-impl__flow v5-impl__flow--5">
			<?php foreach ( $pages as $k => $q ) : if ( $k === $key ) { continue; } ?>
				<li><a href="<?php echo esc_url( md_pres_sub_url( $k ) ); ?>"><b><?php echo esc_html( $q['menu'] ); ?></b><span><?php echo esc_html( $q['card'] ); ?></span></a></li>
			<?php endforeach; ?>
			<li><a href="<?php echo esc_url( $parent_url ); ?>"><b>센터 안내</b><span>증상으로 내게 맞는 치료 찾기</span></a></li>
		</ol>
	</div>
</section>
</div>

<?php get_template_part( 'template-parts/section', 'guide-cta', array( 'slug' => 'preservation' ) ); ?>
<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
