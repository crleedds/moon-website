<?php
/**
 * Section: 센터 페이지 · 종합 안내서 CTA 배너
 *
 * v3.44.212 · 홈 사이드바에만 있던 종합안내서를 각 센터 페이지에서도 바로 닿게 한다.
 *
 * 사용:
 *   get_template_part( 'template-parts/section', 'guide-cta', array( 'slug' => 'implant' ) );
 *
 * @package moondental-child
 */
if ( ! function_exists( 'md_guide_load' ) ) return;

$args = wp_parse_args( isset( $args ) ? $args : array(), array( 'slug' => '' ) );
$slug = $args['slug'];
if ( ! $slug ) return;

$data = md_guide_load( $slug );
if ( ! $data ) return;

$path_map = array(
	'implant'   => '/guide/implant/',
	'suresmile' => '/guide/suresmile/',
	'laminate'  => '/guide/laminate/',
);
if ( ! isset( $path_map[ $slug ] ) ) return;
$href = home_url( $path_map[ $slug ] );

$num = '';
if ( ! empty( $data['code'] ) && preg_match( '/(\d+)/', $data['code'], $mm ) ) {
	$num = str_pad( $mm[1], 2, '0', STR_PAD_LEFT );
}
?>
<section class="md-section md-section--sm md-guide-cta-sec" aria-label="<?php echo esc_attr( ( $data['center'] ?? '' ) . ' 종합 안내서' ); ?>">
	<div class="md-container md-container--narrow">
		<a class="md-guide-cta md-guide-cta--<?php echo esc_attr( $slug ); ?>"
		   href="<?php echo esc_url( $href ); ?>"
		   data-num="<?php echo esc_attr( $num ); ?>"
		   data-track="cta-service-guide">

			<span class="md-guide-cta__icon" aria-hidden="true"><?php echo esc_html( $data['icon'] ?? '📖' ); ?></span>

			<span class="md-guide-cta__body">
				<span class="md-guide-cta__code"><?php echo esc_html( $data['code'] ?? '종합 안내서' ); ?></span>
				<strong class="md-guide-cta__title"><?php echo esc_html( $data['title'] ?? '' ); ?></strong>
				<?php if ( ! empty( $data['subtitle'] ) ) : ?>
					<span class="md-guide-cta__subtitle"><?php echo esc_html( $data['subtitle'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $data['tags'] ) ) : ?>
					<span class="md-guide-cta__tags">
						<?php foreach ( array_slice( (array) $data['tags'], 0, 4 ) as $t ) : ?>
							<span class="md-guide-cta__tag"><?php echo esc_html( $t ); ?></span>
						<?php endforeach; ?>
					</span>
				<?php endif; ?>
			</span>

			<span class="md-guide-cta__read">
				<?php echo esc_html( md_content( 'guide_cta_read', '안내서 전체 보기' ) ); ?>
				<span aria-hidden="true">→</span>
			</span>
		</a>
	</div>
</section>
