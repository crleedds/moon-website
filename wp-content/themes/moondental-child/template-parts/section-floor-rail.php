<?php
/**
 * Home Sidebar · 층별 안내 (컴팩트)
 *
 * @package moondental-child
 */

if ( ! function_exists( 'moondental_floor_guide_data' ) ) return;
$data = moondental_floor_guide_data();
if ( empty( $data ) ) return;

$title = function_exists( 'md_content' ) ? md_content( 'floor_guide_title', '층별 안내' ) : '층별 안내';

// v3.44.191 · 색상은 'center' 필드가 있는 항목만 (센터 아닌 항목은 색 안 넣음)
?>

<div class="md-floor-rail" aria-label="층별 안내">
	<header class="md-floor-rail__head">
		<span class="md-floor-rail__eyebrow">FLOOR</span>
		<h3 class="md-floor-rail__title">🏥 <?php echo esc_html( $title ); ?></h3>
	</header>
	<ul class="md-floor-rail__list">
		<?php foreach ( $data as $f ) : ?>
			<li class="md-floor-rail__row">
				<span class="md-floor-rail__floor"><?php echo esc_html( $f['floor'] ); ?></span>
				<span class="md-floor-rail__centers">
					<?php
					$parts = array();
					foreach ( $f['centers'] as $c ) {
						$name = esc_html( $c['name'] );
						$color_cls = ! empty( $c['center'] ) ? ' md-center-color--' . $c['center'] : '';
						// v3.44.211 · 링크는 3개 센터만 ('center' 키 보유 항목)
						if ( ! empty( $c['center'] ) && ! empty( $c['slug'] ) ) {
							$url = home_url( '/진료항목/' . $c['slug'] . '/' );
							if ( $c['slug'] === '스마일디자인센터' ) $url = home_url( '/스마일디자인센터/' );
							$cls = 'md-floor-rail__center md-floor-rail__center--link' . $color_cls;
							if ( $color_cls ) $cls .= ' md-floor-rail__center--highlight';
							$parts[] = '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . $name . '</a>';
						} else {
							$parts[] = '<span class="md-floor-rail__center' . $color_cls . '">' . $name . '</span>';
						}
					}
					echo implode( ' <span class="md-floor-rail__sep" aria-hidden="true">·</span> ', $parts );
					?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
