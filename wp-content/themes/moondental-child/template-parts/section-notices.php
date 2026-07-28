<?php
/**
 * Section: 홈 — 병원 소식 페이지 전체 (공지사항 + 치아이야기)
 *
 *  v3.34.1 · 홈 하단에 소식 페이지 통째로 노출.
 *  사용자 요청: 홈에서 소식·치아이야기 모든 글을 카드 그리드로 표시.
 *
 * @package moondental-child
 */

// 공지사항 카테고리 ID (필터용)
$notice_cats = array();
foreach ( array( 'notice', '공지사항', 'announcement' ) as $slug ) {
	$c = get_category_by_slug( $slug );
	if ( $c ) $notice_cats[] = $c->term_id;
}

// 공지사항 · 전체 (최대 12개)
$notice_q = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 6,
	'category_name'  => 'notice,공지사항,announcement',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
) );

// 치아이야기 · 전체 (최대 12개)
$story_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => 12,
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

// 카드 썸네일 헬퍼 (page-news 와 동일 로직)
if ( ! function_exists( 'md_home_news_thumb' ) ) {
	function md_home_news_thumb( $post_id ) {
		$meta = get_post_meta( $post_id, 'moondental_naver_thumb_url', true );
		if ( $meta ) return $meta;
		if ( has_post_thumbnail( $post_id ) ) {
			$src = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'medium_large' );
			if ( $src ) return $src;
		}
		$content = get_post_field( 'post_content', $post_id );
		if ( $content && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m ) ) {
			return $m[1];
		}
		return '';
	}
}
?>

<section class="md-section md-section--surface" id="notices" aria-label="<?php echo esc_attr( md_content( 'aria_sec_notices', '병원 소식' ) ); ?>">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'notices_eyebrow', 'NEWS · 병원 소식' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'notices_title', '천안·아산 문치과병원 소식' ) ); ?></h2>
		</header>

		<!-- 공지사항 -->
		<?php if ( $notice_q->have_posts() ) : ?>
			<h3 class="md-notice-subhead">
				<?php echo esc_html( md_content( 'notices_notice_subhead', '📢 문치과병원 소식' ) ); ?>
			</h3>
			<div class="md-news-grid md-u-mt-16">
				<?php while ( $notice_q->have_posts() ) : $notice_q->the_post();
					$thumb_url = md_home_news_thumb( get_the_ID() ); ?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">📢</span>
								<?php endif; ?>
								<span class="md-news-card__category md-news-card__category--notice"><?php echo esc_html( md_content( 'notice_tag_notice', '소식' ) ); ?></span>
							</div>
							<div class="md-news-card__body">
								<time class="md-news-card__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
									<?php echo esc_html( get_the_date( md_content( 'date_format', 'Y년 n월 j일' ) ) ); ?>
								</time>
								<h3 class="md-news-card__title"><?php the_title(); ?></h3>
								<p class="md-news-card__excerpt">
									<?php
									$ex = get_the_excerpt();
									echo esc_html( mb_strimwidth( wp_strip_all_tags( $ex ), 0, 140, '…', 'UTF-8' ) );
									?>
								</p>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>

		<!-- 치아이야기 -->
		<?php if ( $story_q->have_posts() ) : ?>
			<h3 class="md-notice-subhead md-u-mt-40">
				<?php echo esc_html( md_content( 'notices_story_subhead', '🦷 문치과병원 치아이야기' ) ); ?>
			</h3>
			<div class="md-news-grid md-u-mt-16">
				<?php while ( $story_q->have_posts() ) : $story_q->the_post();
					$thumb_url = md_home_news_thumb( get_the_ID() );
				?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">🦷</span>
								<?php endif; ?>
								<span class="md-news-card__category"><?php echo esc_html( md_content( 'notice_tag_story', '치아이야기' ) ); ?></span>
							</div>
							<div class="md-news-card__body">
								<time class="md-news-card__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
									<?php echo esc_html( get_the_date( md_content( 'date_format', 'Y년 n월 j일' ) ) ); ?>
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
		<?php endif; ?>

		<div class="md-section-tail md-u-center md-u-mt-32">
			<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( $news_page_url ); ?>">
				<?php echo esc_html( md_content( 'notices_all_label', '전체 보기 →' ) ); ?>
			</a>
		</div>
	</div>
</section>
