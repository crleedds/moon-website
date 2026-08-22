<?php
/**
 * Home Sidebar · 환자분들의 이야기 (컴팩트 세로 리스트)
 *
 * @package moondental-child
 */

if ( ! function_exists( 'moondental_get_testimonials' ) ) return;
$items = moondental_get_testimonials();
if ( empty( $items ) ) return;

$show = array_slice( $items, 0, 6 );

// v3.44.165 · 네이버 플레이스 리뷰 URL
$info = function_exists( 'moondental_get_info' ) ? moondental_get_info() : array();
$naver_review_url = ! empty( $info['naver_map_url'] )
	? $info['naver_map_url'] . '?placePath=/review'
	: 'https://map.naver.com/p/entry/place/12772165?placePath=/review';
?>

<div class="md-testi-rail" aria-label="환자분들의 이야기">
	<header class="md-testi-rail__head">
		<span class="md-testi-rail__eyebrow">REVIEWS</span>
		<h3 class="md-testi-rail__title">💬 환자분들의 이야기</h3>
	</header>
	<ol class="md-testi-rail__list">
		<?php foreach ( $show as $t ) :
			$name    = $t['name']    ?? '';
			$service = $t['service'] ?? '';
			$rating  = isset( $t['rating'] ) ? (int) $t['rating'] : 0;
			$text    = $t['text']    ?? '';
			if ( ! $text ) continue;
		?>
			<li class="md-testi-rail__item">
				<div class="md-testi-rail__stars" aria-label="별점 <?php echo esc_attr( $rating ); ?>점">
					<?php for ( $i = 0; $i < 5; $i++ ) : ?>
						<span class="md-testi-rail__star<?php echo $i < $rating ? ' is-filled' : ''; ?>">★</span>
					<?php endfor; ?>
				</div>
				<p class="md-testi-rail__text">"<?php echo esc_html( wp_trim_words( $text, 26, '…' ) ); ?>"</p>
				<div class="md-testi-rail__meta">
					<span class="md-testi-rail__name"><?php echo esc_html( $name ); ?></span>
					<?php if ( $service ) : ?>
						<span class="md-testi-rail__service"><?php echo esc_html( $service ); ?></span>
					<?php endif; ?>
					<?php
					/* v3.44.203 · 진료받은 층 배지 · 층별 안내 데이터에서 자동 도출 */
					$_rail_floor = function_exists( 'moondental_text_floor' ) ? moondental_text_floor( $service ) : '';
					if ( $_rail_floor && function_exists( 'moondental_floor_badge' ) ) {
						echo moondental_floor_badge( $_rail_floor, 'md-floor-badge md-floor-badge--rail' );
					}
					?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="md-testi-rail__more">
		<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( $naver_review_url ); ?>" target="_blank" rel="noopener noreferrer">
			<span aria-hidden="true">Ⓝ</span> 더 많은 후기 보기 →
		</a>
	</p>
</div>
