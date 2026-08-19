<?php
/**
 * Section: 홈 사이드바 · 종합 안내서 단일 카드
 *
 * v3.44.181 · 사이드바 세로 컴팩트 카드 · slug 인자로 하나씩 렌더
 *
 * 사용:
 *   get_template_part( 'template-parts/section', 'guide-rail', array( 'slug' => 'implant' ) );
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
	'implant'    => '/가이드/임플란트/',
	'invisalign' => '/가이드/투명교정/',
	'laminate'   => '/가이드/라미네이트/',
);
$href = isset( $path_map[ $slug ] ) ? $path_map[ $slug ] : '/';
?>
<section class="md-guide-rail md-guide-rail--<?php echo esc_attr( $slug ); ?>" aria-label="<?php echo esc_attr( $data['title'] ?? '종합 안내서' ); ?>">
	<a class="md-guide-rail__card" href="<?php echo esc_url( home_url( $href ) ); ?>">
		<div class="md-guide-rail__head">
			<span class="md-guide-rail__icon" aria-hidden="true"><?php echo esc_html( $data['icon'] ?? '📖' ); ?></span>
			<span class="md-guide-rail__code"><?php echo esc_html( $data['code'] ?? '' ); ?></span>
		</div>
		<h3 class="md-guide-rail__title"><?php echo esc_html( $data['title'] ?? '' ); ?></h3>
		<?php if ( ! empty( $data['subtitle'] ) ) : ?>
			<p class="md-guide-rail__subtitle"><?php echo esc_html( $data['subtitle'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $data['tags'] ) ) : ?>
			<div class="md-guide-rail__tags">
				<?php foreach ( $data['tags'] as $t ) : ?>
					<span class="md-guide-rail__tag"><?php echo esc_html( $t ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<span class="md-guide-rail__read">가이드 읽기 <span aria-hidden="true">→</span></span>
	</a>
</section>
