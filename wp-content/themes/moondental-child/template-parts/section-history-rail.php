<?php
/**
 * Home Sidebar · 30여년의 발자취 · 세로 사이드바
 *  · 홈 화면 좌측 sticky 사이드바로 사용
 *  · 각 사진 클릭 시 /역사/#앵커 로 이동
 *
 * @package moondental-child
 */

if ( ! function_exists( 'moondental_get_history' ) || ! function_exists( 'moondental_history_photo_url' ) ) return;

$history = moondental_get_history();
if ( empty( $history ) ) return;

$items = array();
foreach ( $history as $row ) {
	if ( empty( $row['photo'] ) ) continue;
	$url = moondental_history_photo_url( $row['photo'] );
	if ( ! $url ) continue;
	$items[] = array(
		'year'  => $row['year']  ?? '',
		'month' => $row['month'] ?? '',
		'title' => $row['title'] ?? '',
		'photo' => $url,
		'anchor'=> 'h-' . sanitize_title( pathinfo( $row['photo'], PATHINFO_FILENAME ) ),
	);
}
if ( empty( $items ) ) return;

$history_url = home_url( '/역사/' );
?>

<div class="md-history-rail" aria-label="30여년의 발자취 · 사진 스트립">
	<header class="md-history-rail__head">
		<span class="md-history-rail__eyebrow">HISTORY</span>
		<h3 class="md-history-rail__title">30여년의 발자취</h3>
		<p class="md-history-rail__lead">사진 클릭 시 자세한 이야기</p>
	</header>
	<ol class="md-history-rail__list">
		<?php foreach ( $items as $it ) :
			$label = $it['year'] . ( $it['month'] ? '.' . str_pad( $it['month'], 2, '0', STR_PAD_LEFT ) : '' );
			$link  = $history_url . '#' . $it['anchor'];
		?>
			<li class="md-history-rail__item">
				<a class="md-history-rail__link" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( $label . ' · ' . $it['title'] ); ?>">
					<div class="md-history-rail__photo">
						<img src="<?php echo esc_url( $it['photo'] ); ?>" alt="<?php echo esc_attr( $it['title'] ); ?>" loading="lazy">
					</div>
					<div class="md-history-rail__meta">
						<span class="md-history-rail__date"><?php echo esc_html( $label ); ?></span>
						<span class="md-history-rail__title-txt"><?php echo esc_html( $it['title'] ); ?></span>
					</div>
				</a>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="md-history-rail__more">
		<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( $history_url ); ?>">전체 발자취 →</a>
	</p>
</div>
