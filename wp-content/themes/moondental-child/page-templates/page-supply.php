<?php
/**
 * Template Name: 재료실 (직원 전용)
 * Template Post Type: page
 *
 * 로그인한 직원만 볼 수 있는 재료실 화면.
 * 검색엔진에서는 완전히 제외한다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* 검색엔진 · 미리보기에서 제외. Yoast 가 있으면 그쪽 값도 덮어쓴다. */
add_filter( 'wp_robots', function ( $robots ) {
	$robots['noindex']   = true;
	$robots['nofollow']  = true;
	$robots['noarchive'] = true;
	unset( $robots['index'], $robots['follow'] );
	return $robots;
}, 99 );
add_filter( 'wpseo_robots', '__return_false', 99 );
add_filter( 'wpseo_canonical', '__return_false', 99 );

get_header();
?>

<main class="mds-page">
	<div class="mds-wrap">
		<?php
		if ( function_exists( 'md_sup_render_page' ) ) {
			md_sup_render_page();
		} else {
			echo '<p>재료실 모듈을 불러오지 못했습니다. 관리자에게 문의해 주세요.</p>';
		}
		?>
	</div>
</main>

<?php
get_footer();
