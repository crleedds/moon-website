<?php
/**
 * 강점 상세 페이지 — /강점/{slug}/ URL에 의해 로드됨.
 *  moondental_strength_intercept() (functions.php) 가 직접 include 하여 호출.
 *
 *  데이터 소스: moondental_get_strength_by_slug() (inc/strengths.php)
 *  Customizer override: strength_{slug}_body (textarea, HTML 가능)
 *
 * @package moondental-child
 */

get_header();

$slug = get_query_var( 'strength_slug' );
if ( ! $slug && isset( $_GET['strength'] ) ) {
	$slug = sanitize_text_field( wp_unslash( $_GET['strength'] ) );
}

$data = function_exists( 'moondental_get_strength_by_slug' ) ? moondental_get_strength_by_slug( $slug ) : null;

if ( ! $data ) {
	// 잘못된 슬러그 — 강점 목록 페이지로 안내
	?>
	<section class="md-section">
		<div class="md-container md-container--narrow md-u-center">
			<h1><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'strength_404_title', '강점 상세 정보를 찾을 수 없습니다' ) : '강점 상세 정보를 찾을 수 없습니다' ); ?></h1>
			<p><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'strength_404_desc', '요청하신 강점 항목이 존재하지 않습니다.' ) : '요청하신 강점 항목이 존재하지 않습니다.' ); ?></p>
			<p class="md-u-mt-24">
				<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/기술력-시설/' ) ); ?>">
					<?php echo esc_html( md_content( 'strength_back_label', '← 강점 목록으로' ) ); ?>
				</a>
			</p>
		</div>
	</section>
	<?php
	get_footer();
	return;
}

// Customizer override 가능 — strength_{slug}_body
$body = function_exists( 'md_content' )
	? md_content( 'strength_' . $slug . '_body', $data['body'] )
	: $data['body'];
?>

<!-- ============ Hero ============ -->
<section class="md-strength-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸
			<a href="<?php echo esc_url( home_url( '/기술력-시설/' ) ); ?>">기술력·시설</a> ▸
			<span><?php echo esc_html( $data['label'] ); ?></span>
		</nav>
		<div class="md-strength-hero__inner">
			<div class="md-strength-hero__icon" aria-hidden="true"><?php echo moondental_render_icon( $data['icon'] ); ?></div>
			<span class="md-strength-hero__label"><?php echo esc_html( $data['label'] ); ?></span>
			<h1 class="md-strength-hero__title"><?php echo esc_html( $data['value'] ); ?></h1>
			<p class="md-strength-hero__lead"><?php echo esc_html( $data['summary'] ); ?></p>
		</div>
	</div>
</section>

<!-- ============ 본문 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<article class="md-page-content md-strength-body">
			<?php echo wp_kses_post( $body ); ?>
		</article>

		<?php if ( ! empty( $data['related'] ) ) : ?>
			<aside class="md-strength-related" aria-label="관련 페이지">
				<h2 class="md-strength-related__title"><?php echo esc_html( md_content( 'strength_related_title', '관련 페이지' ) ); ?></h2>
				<ul class="md-strength-related__list">
					<?php foreach ( $data['related'] as $r ) : ?>
						<li>
							<a href="<?php echo esc_url( $r['url'] ); ?>" data-track="cta-strength-related">
								<?php echo esc_html( $r['label'] ); ?>
								<span aria-hidden="true">→</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 다른 강점 9 카드 (Cross-link) ============ -->
<?php if ( function_exists( 'moondental_get_strengths' ) ) :
	$all = moondental_get_strengths(); ?>
	<section class="md-section md-section--surface md-section--sm" aria-label="다른 강점 보기">
		<div class="md-container">
			<header class="md-section-head">
				<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'strength_more_eyebrow', 'EXPLORE MORE' ) ); ?></span>
				<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'strength_more_title', '다른 강점도 확인하세요' ) ); ?></h2>
			</header>
			<div class="md-strengths">
				<?php foreach ( $all as $key => $s ) :
					if ( $key === $slug ) continue;
					$url = home_url( '/강점/' . $s['slug'] . '/' ); ?>
					<a class="md-strength md-strength--link" href="<?php echo esc_url( $url ); ?>">
						<div class="md-strength__icon" aria-hidden="true"><?php echo $s['icon']; ?></div>
						<div class="md-strength__body">
							<h3 class="md-strength__label"><?php echo esc_html( $s['label'] ); ?></h3>
							<p class="md-strength__value"><?php echo esc_html( $s['value'] ); ?></p>
						</div>
						<span class="md-strength__arrow" aria-hidden="true">→</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- ============ 공통 CTA ============ -->
<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
