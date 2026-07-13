<?php
/**
 * Section: 홈 — 공지사항 + 치아이야기 스니펫
 *
 *  공지사항(notice 카테고리) 2개 + 치아이야기(나머지) 3개 표시.
 *  사용자 요청: 홈에서 병원 소식이 보이도록.
 *
 * @package moondental-child
 */

// 공지사항 카테고리 ID
$notice_cats = array();
foreach ( array( 'notice', '공지사항', 'announcement' ) as $slug ) {
	$c = get_category_by_slug( $slug );
	if ( $c ) $notice_cats[] = $c->term_id;
}

// 공지사항 (최대 2개)
$notice_q = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 2,
	'category_name'  => 'notice,공지사항,announcement',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
) );

// 치아이야기 (공지사항 제외, 최대 3개)
$story_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => 3,
	'orderby'             => 'date',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
if ( $notice_cats ) {
	$story_args['category__not_in'] = $notice_cats;
}
$story_q = new WP_Query( $story_args );

// 둘 다 비어있으면 섹션 자체 미출력
if ( ! $notice_q->have_posts() && ! $story_q->have_posts() ) {
	return;
}

$news_page_url = get_post_type_archive_link( 'post' ) ?: home_url( '/소식/' );
?>

<section class="md-section md-section--surface" id="notices" aria-label="병원 소식">
	<div class="md-container">
		<header class="md-section-head md-section-head--split">
			<div>
				<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'notices_eyebrow', 'NEWS · 병원 소식' ) : 'NEWS · 병원 소식' ); ?></span>
				<h2 class="md-section-head__title md-section-head__title--flush"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'notices_title', '천안·아산 문치과병원 소식' ) : '천안·아산 문치과병원 소식' ); ?></h2>
			</div>
			<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( $news_page_url ); ?>">
				전체 보기 →
			</a>
		</header>

		<!-- 공지사항 (최대 2개) -->
		<?php if ( $notice_q->have_posts() ) : ?>
			<h3 class="md-notice-subhead">
				📢 문치과병원 소식
			</h3>
			<ul class="md-notice-list md-notice-list--spaced">
				<?php while ( $notice_q->have_posts() ) : $notice_q->the_post(); ?>
					<li class="md-notice-item">
						<a href="<?php the_permalink(); ?>">
							<span class="md-notice-item__tag">공지</span>
							<span class="md-notice-item__title"><?php the_title(); ?></span>
							<time class="md-notice-item__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
								<?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
							</time>
						</a>
					</li>
				<?php endwhile; wp_reset_postdata(); ?>
			</ul>
		<?php endif; ?>

		<!-- 치아이야기 (최대 3개) -->
		<?php if ( $story_q->have_posts() ) : ?>
			<h3 class="md-notice-subhead">
				🦷 문치과병원 치아이야기
			</h3>
			<div class="md-home-news-grid">
				<?php while ( $story_q->have_posts() ) : $story_q->the_post();
					$thumb_url = get_post_meta( get_the_ID(), 'moondental_naver_thumb_url', true ); ?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" referrerpolicy="no-referrer">
								<?php elseif ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">🦷</span>
								<?php endif; ?>
							</div>
							<div class="md-news-card__body">
								<time class="md-news-card__date"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time>
								<h3 class="md-news-card__title"><?php the_title(); ?></h3>
								<p class="md-news-card__excerpt">
									<?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ); ?>
								</p>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
