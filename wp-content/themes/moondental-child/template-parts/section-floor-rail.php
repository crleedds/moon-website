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
$lead  = function_exists( 'md_content' ) ? md_content( 'floor_guide_lead',  '문타워 9·10·11·13층 · 각 층 전용 전문 진료실 운영' ) : '문타워 9·10·11·13층 · 각 층 전용 전문 진료실 운영';

// v3.44.190 · 센터별 컬러 클래스 매핑 (홈 안내서 카드·상단 메뉴와 통일)
$center_color_map = array(
	'임플란트-센터'   => 'md-center-color--implant',   // 세이지 그린
	'투명교정-센터'   => 'md-center-color--suresmile', // 브랜드 살구
	'스마일디자인센터' => 'md-center-color--laminate',  // 소프트 퍼플
);
?>

<div class="md-floor-rail" aria-label="층별 안내">
	<header class="md-floor-rail__head">
		<span class="md-floor-rail__eyebrow">FLOOR</span>
		<h3 class="md-floor-rail__title">🏥 <?php echo esc_html( $title ); ?></h3>
		<p class="md-floor-rail__lead"><?php echo esc_html( $lead ); ?></p>
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
						$color_cls = ! empty( $c['slug'] ) && isset( $center_color_map[ $c['slug'] ] ) ? ' ' . $center_color_map[ $c['slug'] ] : '';
						if ( ! empty( $c['slug'] ) ) {
							$url = home_url( '/진료항목/' . $c['slug'] . '/' );
							if ( $c['slug'] === '스마일디자인센터' ) $url = home_url( '/스마일디자인센터/' );
							$cls = 'md-floor-rail__center md-floor-rail__center--link' . $color_cls;
							if ( $color_cls ) $cls .= ' md-floor-rail__center--highlight';
							$parts[] = '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . $name . '</a>';
						} else {
							$parts[] = '<span class="md-floor-rail__center">' . $name . '</span>';
						}
					}
					echo implode( ' <span class="md-floor-rail__sep" aria-hidden="true">·</span> ', $parts );
					?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
