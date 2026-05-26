<?php
/**
 * Template Name: 소식 (가져온 블로그 글 목록)
 * Template Post Type: page
 *
 * 네이버 블로그에서 import된 WP 글 (post 타입)을 카드 그리드로 표시.
 * 카드 클릭 → 같은 사이트의 single.php 로 풀 본문 표시.
 * import된 글이 0개면 RSS 기반 카드 (네이버로 이동)로 fallback.
 *
 * @package moondental-child
 */

get_header();
$info = moondental_get_info();

// page-for-posts paged 처리
$paged = max( 1, (int) get_query_var( 'paged' ) );
if ( ! $paged ) $paged = max( 1, (int) get_query_var( 'page' ) );

$q = new WP_Query( array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'meta_key'       => 'moondental_naver_log_no',
	'orderby'        => 'date',
	'order'          => 'DESC',
) );
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>소식</span>
		</nav>
		<h1 class="md-page-hero__title">소식</h1>
		<p class="md-page-hero__lead">
			문치과병원의 진료 안내·치아 상식·병원 소식을 모았습니다.
		</p>
	</div>
</section>

<section class="md-section">
	<div class="md-container">

		<?php if ( $q->have_posts() ) : ?>
			<div class="md-news-grid">
				<?php while ( $q->have_posts() ) : $q->the_post();
					$thumb_url = get_post_meta( get_the_ID(), 'moondental_naver_thumb_url', true );
					$category  = get_post_meta( get_the_ID(), 'moondental_naver_category',  true );
				?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">📝</span>
								<?php endif; ?>
								<?php if ( $category ) : ?>
									<span class="md-news-card__category"><?php echo esc_html( $category ); ?></span>
								<?php endif; ?>
							</div>
							<div class="md-news-card__body">
								<time class="md-news-card__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
									<?php echo esc_html( get_the_date( 'Y년 n월 j일' ) ); ?>
								</time>
								<h3 class="md-news-card__title"><?php the_title(); ?></h3>
								<p class="md-news-card__excerpt">
									<?php
									$ex = get_the_excerpt();
									echo esc_html( mb_strimwidth( wp_strip_all_tags( $ex ), 0, 160, '…', 'UTF-8' ) );
									?>
								</p>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>

			<?php
			// 페이지네이션
			$total = (int) $q->max_num_pages;
			if ( $total > 1 ) :
				$big = 999999999;
				echo '<nav class="md-pagination" aria-label="페이지">';
				echo paginate_links( array(
					'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'    => '?paged=%#%',
					'current'   => $paged,
					'total'     => $total,
					'prev_text' => '←',
					'next_text' => '→',
					'mid_size'  => 2,
				) );
				echo '</nav>';
			endif;
			?>

		<?php else :
			/* import 한 글이 없으면 RSS 카드로 fallback (홍보 효과 0이지만 빈 화면 방지) */
			$items = moondental_fetch_naver_blog( 20 );
			if ( ! empty( $items ) ) : ?>
				<div class="md-news-empty">
					<p>아직 사이트에 글을 가져오지 않았습니다. 관리자에서 <strong>외모 → 문치과 사이트 도구 → 네이버 블로그 동기화</strong>를 실행해주세요.</p>
					<p>지금은 네이버 블로그의 최신 글 목록만 표시됩니다.</p>
				</div>
				<div class="md-news-grid" style="margin-top:32px;">
					<?php foreach ( $items as $item ) : ?>
						<article class="md-news-card">
							<a class="md-news-card__link" href="<?php echo esc_url( $item['link'] ); ?>" target="_blank" rel="noopener">
								<div class="md-news-card__media">
									<?php if ( $item['thumb'] ) : ?>
										<img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
									<?php else : ?>
										<span class="md-news-card__media-fallback">📝</span>
									<?php endif; ?>
									<?php if ( $item['category'] ) : ?>
										<span class="md-news-card__category"><?php echo esc_html( $item['category'] ); ?></span>
									<?php endif; ?>
								</div>
								<div class="md-news-card__body">
									<time class="md-news-card__date"><?php echo esc_html( date_i18n( 'Y년 n월 j일', $item['date'] ) ); ?></time>
									<h3 class="md-news-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
									<p class="md-news-card__excerpt"><?php echo esc_html( $item['excerpt'] ); ?></p>
								</div>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="md-news-empty">
					<p>아직 게시된 소식이 없습니다.</p>
				</div>
			<?php endif;
		endif; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
