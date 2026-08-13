<?php
/**
 * Home Sidebar · 병원 소식 + 치아이야기 (세로 컴팩트 리스트)
 *  · 홈 좌측 사이드바용 · 30여년 발자취 아래 표시
 *
 * @package moondental-child
 */

// 공지사항 카테고리 ID
$notice_cats = array();
foreach ( array( 'notice', '공지사항', 'announcement' ) as $slug ) {
	$c = get_category_by_slug( $slug );
	if ( $c ) $notice_cats[] = $c->term_id;
}

// 공지사항 (최대 7)
$notice_q = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 7,
	'category_name'  => 'notice,공지사항,announcement',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
) );

// 치아이야기 (최대 3)
$story_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => 3,
	'orderby'             => 'date',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
if ( $notice_cats ) $story_args['category__not_in'] = $notice_cats;
$story_q = new WP_Query( $story_args );

if ( ! $notice_q->have_posts() && ! $story_q->have_posts() ) return;

$news_page_url = get_post_type_archive_link( 'post' ) ?: home_url( '/소식/' );

if ( ! function_exists( 'md_rail_news_thumb' ) ) {
	function md_rail_news_thumb( $post_id ) {
		$meta = get_post_meta( $post_id, 'moondental_naver_thumb_url', true );
		if ( $meta ) return $meta;
		if ( has_post_thumbnail( $post_id ) ) {
			$src = wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'thumbnail' );
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

<?php if ( $notice_q->have_posts() ) : ?>
<div class="md-news-rail" aria-label="병원 소식">
	<header class="md-news-rail__head">
		<span class="md-news-rail__eyebrow">NEWS</span>
		<h3 class="md-news-rail__title">📢 문치과병원 소식</h3>
	</header>
	<ol class="md-news-rail__list">
		<?php while ( $notice_q->have_posts() ) : $notice_q->the_post();
			$pid   = get_the_ID();
			$thumb = md_rail_news_thumb( $pid );
			$date  = get_the_date( 'Y.m.d' );
		?>
			<li class="md-news-rail__item">
				<a class="md-news-rail__link" href="<?php the_permalink(); ?>">
					<?php if ( $thumb ) : ?>
						<div class="md-news-rail__thumb">
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy">
						</div>
					<?php else : ?>
						<div class="md-news-rail__thumb md-news-rail__thumb--empty" aria-hidden="true">📢</div>
					<?php endif; ?>
					<div class="md-news-rail__meta">
						<span class="md-news-rail__date"><?php echo esc_html( $date ); ?></span>
						<span class="md-news-rail__title-txt"><?php the_title(); ?></span>
					</div>
				</a>
			</li>
		<?php endwhile; wp_reset_postdata(); ?>
	</ol>
	<p class="md-news-rail__more">
		<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( $news_page_url ); ?>">전체 소식 →</a>
	</p>
</div>
<?php endif; ?>

<?php if ( $story_q->have_posts() ) : ?>
<div class="md-news-rail" aria-label="치아이야기">
	<header class="md-news-rail__head">
		<span class="md-news-rail__eyebrow">STORIES</span>
		<h3 class="md-news-rail__title">🦷 치아이야기</h3>
	</header>
	<ol class="md-news-rail__list">
		<?php while ( $story_q->have_posts() ) : $story_q->the_post();
			$pid   = get_the_ID();
			$thumb = md_rail_news_thumb( $pid );
			$date  = get_the_date( 'Y.m.d' );
		?>
			<li class="md-news-rail__item">
				<a class="md-news-rail__link" href="<?php the_permalink(); ?>">
					<?php if ( $thumb ) : ?>
						<div class="md-news-rail__thumb">
							<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy">
						</div>
					<?php else : ?>
						<div class="md-news-rail__thumb md-news-rail__thumb--empty" aria-hidden="true">🦷</div>
					<?php endif; ?>
					<div class="md-news-rail__meta">
						<span class="md-news-rail__date"><?php echo esc_html( $date ); ?></span>
						<span class="md-news-rail__title-txt"><?php the_title(); ?></span>
					</div>
				</a>
			</li>
		<?php endwhile; wp_reset_postdata(); ?>
	</ol>
	<p class="md-news-rail__more">
		<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( $news_page_url ); ?>">전체 이야기 →</a>
	</p>
</div>
<?php endif; ?>
