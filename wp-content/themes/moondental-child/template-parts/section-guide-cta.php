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

$args = wp_parse_args( isset( $args ) ? $args : array(), array( 'slug' => '', 'title' => '', 'subtitle' => '' ) ); // v4.13 · title/subtitle 덮어쓰기
$slug = $args['slug'];
if ( ! $slug ) return;

$data = md_guide_load( $slug );
if ( ! $data ) return;

$path_map = array(
	'implant'   => '/guide/implant/',
	'suresmile' => '/guide/suresmile/',
	'laminate'  => '/guide/laminate/',
	'preservation' => '/guide/preservation/', // v3.85
	'cheonan'   => '/guide/cheonan/', // v4.12.1 · 천안 치과 추천, 무엇을 보고 고를까
	'asan'      => '/guide/asan/',    // v4.12.1 · 아산 치과 추천, 무엇을 보고 고를까
);
$href = home_url( isset( $path_map[ $slug ] ) ? $path_map[ $slug ] : '/guide/' . $slug . '/' ); // v4.13 · 지역 안내서 등
if ( '' !== $args['title'] )    { $data['title'] = $args['title']; }
if ( '' !== $args['subtitle'] ) { $data['subtitle'] = $args['subtitle']; }

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
