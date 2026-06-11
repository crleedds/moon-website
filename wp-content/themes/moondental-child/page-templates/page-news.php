<?php
/**
 * Template Name: 병원소식 (공지사항 + 치아이야기)
 * Template Post Type: page
 *
 * 사용자 요청: 공지사항이 먼저 나오고 그 아래 '치아이야기'로 구강 관련 정보.
 *  공지사항 카테고리(slug: notice / 공지사항) 글 우선 노출
 *  그 외 글은 치아이야기 섹션에 표시.
 *
 * @package moondental-child
 */

get_header();

$paged = max( 1, (int) get_query_var( 'paged' ) );
if ( ! $paged ) $paged = max( 1, (int) get_query_var( 'page' ) );

// 공지사항 카테고리 글 (최대 6)
$notice_q = new WP_Query( array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 6,
	'category_name'  => 'notice,공지사항,announcement',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
) );

// 치아이야기 글 (페이지네이션 포함, 공지사항 제외)
$story_args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 9,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
);
// 공지사항 카테고리 ID 찾아서 제외
$notice_cats = array();
foreach ( array( 'notice', '공지사항', 'announcement' ) as $slug ) {
	$c = get_category_by_slug( $slug );
	if ( $c ) $notice_cats[] = $c->term_id;
}
if ( $notice_cats ) {
	$story_args['category__not_in'] = $notice_cats;
}
$story_q = new WP_Query( $story_args );
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>병원 소식</span>
		</nav>
		<h1 class="md-page-hero__title">병원 소식</h1>
		<p class="md-page-hero__lead">
			천안 만남로 문치과병원의 공지사항과 치과 정보를 모았습니다.
		</p>
	</div>
</section>

<!-- ============ 1. 공지사항 ============ -->
<section class="md-section md-section--surface" id="notice">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">📢 NOTICE</span>
			<h2 class="md-section-head__title">문치과병원 소식</h2>
			<p class="md-section-head__lead">진료시간 변경·휴진 안내·이벤트·운영 소식을 가장 먼저 안내드립니다.</p>
		</header>

		<?php if ( $notice_q->have_posts() ) : ?>
			<ul class="md-notice-list">
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
		<?php else : ?>
			<div class="md-news-empty" style="text-align:center; padding: clamp(24px, 3vw, 40px); background: var(--color-white); border-radius: var(--radius-md);">
				<p style="margin:0; color: var(--color-text-sub);">아직 등록된 공지사항이 없습니다.</p>
				<p style="margin: 8px 0 0; font-size: var(--fs-small); color: var(--color-text-mute);">
					관리자: <code>wp-admin → 글 → 카테고리</code>에서 '문치과병원 소식' 카테고리를 만들고 글을 작성해주세요.
				</p>
			</div>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 2. 치아이야기 ============ -->
<section class="md-section" id="dental-stories">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">🦷 DENTAL STORIES · 치아이야기</span>
			<h2 class="md-section-head__title">문치과병원 치아이야기</h2>
			<p class="md-section-head__lead">
				임플란트·교정·자연치아 살리기·라미네이트·예방 등<br>
				환자분께 도움이 되는 구강 건강 정보를 모았습니다.
			</p>
		</header>

		<?php if ( $story_q->have_posts() ) : ?>
			<div class="md-news-grid">
				<?php while ( $story_q->have_posts() ) : $story_q->the_post();
					$thumb_url = get_post_meta( get_the_ID(), 'moondental_naver_thumb_url', true );
					$category  = get_post_meta( get_the_ID(), 'moondental_naver_category',  true );
					if ( ! $category ) {
						$cats = get_the_category();
						$category = $cats ? $cats[0]->name : '치아이야기';
					}
				?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
								<?php elseif ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">🦷</span>
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
			$total = (int) $story_q->max_num_pages;
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
			$items = function_exists( 'moondental_fetch_naver_blog' ) ? moondental_fetch_naver_blog( 9 ) : array();
			if ( ! empty( $items ) ) : ?>
				<div class="md-news-empty">
					<p>아직 사이트에 가져온 글이 없습니다. 네이버 블로그 최신 글 미리보기:</p>
				</div>
				<div class="md-news-grid" style="margin-top:24px;">
					<?php foreach ( $items as $item ) : ?>
						<article class="md-news-card">
							<a class="md-news-card__link" href="<?php echo esc_url( $item['link'] ); ?>" target="_blank" rel="noopener">
								<div class="md-news-card__media">
									<?php if ( $item['thumb'] ) : ?>
										<img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
									<?php else : ?>
										<span class="md-news-card__media-fallback">🦷</span>
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
				<div class="md-news-empty" style="text-align:center; padding: clamp(24px, 3vw, 40px); background: var(--color-surface); border-radius: var(--radius-md);">
					<p style="margin:0; color: var(--color-text-sub);">아직 등록된 치아이야기가 없습니다.</p>
					<p style="margin: 8px 0 0; font-size: var(--fs-small); color: var(--color-text-mute);">
						관리자: <code>wp-admin → 글 → 새 글</code>에서 치과 관련 정보를 게시해주세요.
					</p>
				</div>
			<?php endif;
		endif; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
