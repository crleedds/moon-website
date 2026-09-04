<?php
/**
 * Template Name: 재료실 (직원 전용)
 * Template Post Type: page
 *
 * 로그인한 직원만 볼 수 있는 재료실 화면.
 * 검색엔진에서는 완전히 제외한다.
 *
 * 사이트 헤더·푸터를 부르지 않는다 (v3.66)
 *   v3.65 까지는 get_header() / get_footer() 로 환자용 헤더와 푸터를 통째로
 *   그린 다음 CSS 로 감췄다. 눈에는 안 보여도 만드는 값은 다 치렀다 —
 *   메뉴 질의, 층별 진료센터 목록, 주차 안내, 진료시간 계산까지.
 *   재료실은 하루에도 몇 번씩 열고 닫는 업무 화면이라 그 값이 그대로 체감된다.
 *   그래서 여기서는 문서 뼈대만 직접 짜고 wp_head() / wp_footer() 만 부른다.
 *   플러그인·스타일·스크립트는 그 두 곳에서 붙으므로 빠지는 것이 없다.
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

/* 검색엔진용 구조화 데이터(JSON-LD)도 끈다 (v3.67).
 * noindex 페이지라 아무도 읽지 않는데 14KB 를 매번 실어 보내고 있었다. */
add_filter( 'wpseo_json_ld_output', '__return_false', 99 );

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="color-scheme" content="light dark">
	<meta name="supported-color-schemes" content="light dark">
	<meta name="theme-color" content="#FFFAF4" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="#1B1310" media="(prefers-color-scheme: dark)">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<main class="mds-page" id="md-main">
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

<?php wp_footer(); ?>
</body>
</html>
