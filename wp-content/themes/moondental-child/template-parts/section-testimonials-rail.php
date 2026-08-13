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
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
