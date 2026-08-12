<?php
/**
 * Home Section · 30여년의 발자취 · 사진 스트립
 *  · 홈 화면에서 히스토리 사진을 스크롤하며 한눈에 · 클릭 시 /역사/#앵커 로 이동
 *
 * @package moondental-child
 */

if ( ! function_exists( 'moondental_get_history' ) || ! function_exists( 'moondental_history_photo_url' ) ) return;

$history = moondental_get_history();
if ( empty( $history ) ) return;

// 사진 있는 항목만 필터
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

$history_url  = home_url( '/역사/' );
$eyebrow      = function_exists( 'md_content' ) ? md_content( 'home_history_eyebrow', 'HISTORY · 30여년의 발자취' ) : 'HISTORY · 30여년의 발자취';
$title        = function_exists( 'md_content' ) ? md_content( 'home_history_title',   '천안·아산에서 30여년, 걸어온 길' ) : '천안·아산에서 30여년, 걸어온 길';
$lead         = function_exists( 'md_content' ) ? md_content( 'home_history_lead',    '1995년 개원 이래 지역 사회와 함께 걸어온 순간들. 사진을 클릭하면 자세한 이야기를 볼 수 있습니다.' ) : '1995년 개원 이래 지역 사회와 함께 걸어온 순간들.';
$view_all     = function_exists( 'md_content' ) ? md_content( 'home_history_view_all','전체 발자취 보기 →' ) : '전체 발자취 보기 →';
?>

<section class="md-section md-section--surface md-home-history" aria-label="문치과병원 30여년 발자취">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $title ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( $lead ); ?></p>
		</header>

		<div class="md-home-history__scroll">
			<ol class="md-home-history__grid" aria-label="연혁 사진 목록">
				<?php foreach ( $items as $it ) :
					$label = $it['year'] . ( $it['month'] ? '.' . str_pad( $it['month'], 2, '0', STR_PAD_LEFT ) : '' );
					$link  = $history_url . '#' . $it['anchor'];
				?>
					<li class="md-home-history__item">
						<a class="md-home-history__link" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( $label . ' · ' . $it['title'] ); ?>">
							<div class="md-home-history__photo">
								<img src="<?php echo esc_url( $it['photo'] ); ?>" alt="<?php echo esc_attr( $it['title'] ); ?>" loading="lazy">
							</div>
							<div class="md-home-history__meta">
								<span class="md-home-history__date"><?php echo esc_html( $label ); ?></span>
								<span class="md-home-history__title"><?php echo esc_html( $it['title'] ); ?></span>
							</div>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<p class="md-home-history__more">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( $history_url ); ?>"><?php echo esc_html( $view_all ); ?></a>
		</p>
	</div>
</section>
