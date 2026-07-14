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

// 별칭 카테고리 마이그레이션 미완료 케이스 대비 — 정식 + 별칭 모든 term_id 수집
$notice_term_ids = array();
$story_term_ids  = array();

// 정식 슬러그
foreach ( array( 'notice' ) as $s ) {
	$t = get_category_by_slug( $s );
	if ( $t ) $notice_term_ids[] = $t->term_id;
}
foreach ( array( 'dental-stories' ) as $s ) {
	$t = get_category_by_slug( $s );
	if ( $t ) $story_term_ids[] = $t->term_id;
}
// 한글 별칭 이름들
foreach ( array( '문치과병원 소식', '공지사항', '소식', '뉴스', '공지', 'announcement', 'news' ) as $n ) {
	$t = get_term_by( 'name', $n, 'category' );
	if ( $t && ! in_array( $t->term_id, $notice_term_ids, true ) ) $notice_term_ids[] = $t->term_id;
}
foreach ( array( '문치과병원 치아이야기', '치아이야기', '치과이야기', '치아 이야기', '치과 이야기' ) as $n ) {
	$t = get_term_by( 'name', $n, 'category' );
	if ( $t && ! in_array( $t->term_id, $story_term_ids, true ) ) $story_term_ids[] = $t->term_id;
}

// 소식 글 (최대 9, 페이지네이션 없음)
$notice_args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 9,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
);
if ( ! empty( $notice_term_ids ) ) {
	$notice_args['category__in'] = $notice_term_ids;
}
$notice_q = new WP_Query( $notice_args );

// 치아이야기 글 (페이지네이션 포함, 소식 제외)
$story_args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 9,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
);
if ( ! empty( $notice_term_ids ) ) {
	$story_args['category__not_in'] = $notice_term_ids;
}
$story_q = new WP_Query( $story_args );

// 카드 썸네일 헬퍼 — 본문 첫 이미지 추출 (네이버 메타 → 첨부 → 본문 first img 순)
if ( ! function_exists( 'md_post_card_thumb' ) ) {
	function md_post_card_thumb( $post_id ) {
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

<?php
$news_hero_title = md_content( 'news_hero_title', '병원 소식' );
$news_hero_lead  = md_content( 'news_hero_lead', '천안 만남로 문치과병원의 공지사항과 치과 정보를 모았습니다.' );
?>
<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php echo esc_html( $news_hero_title ); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php echo esc_html( $news_hero_title ); ?></h1>
		<p class="md-page-hero__lead"><?php echo esc_html( $news_hero_lead ); ?></p>
	</div>
</section>

<?php
// 관리자 빠른 글쓰기 URL
$notice_cat_obj = get_category_by_slug( 'notice' );
$story_cat_obj  = get_category_by_slug( 'dental-stories' );
$notice_new_url = $notice_cat_obj ? add_query_arg( array( 'post_category' => array( $notice_cat_obj->term_id ) ), admin_url( 'post-new.php' ) ) : admin_url( 'post-new.php' );
$story_new_url  = $story_cat_obj  ? add_query_arg( array( 'post_category' => array( $story_cat_obj->term_id ) ),  admin_url( 'post-new.php' ) ) : admin_url( 'post-new.php' );
$is_editor = is_user_logged_in() && current_user_can( 'publish_posts' );
?>

<!-- ============ 1. 문치과병원 소식 ============ -->
<section class="md-section md-section--surface" id="notice">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'news_notice_eyebrow', '📢 NOTICE' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'news_notice_title', '문치과병원 소식' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'news_notice_lead', '진료시간 변경·휴진 안내·이벤트·운영 소식을 가장 먼저 안내드립니다.' ) ); ?></p>
			<?php if ( $is_editor ) : ?>
				<div class="md-admin-quickpost">
					<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( $notice_new_url ); ?>">＋ 새 소식 글쓰기</a>
					<?php if ( $notice_cat_obj ) : ?>
						<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( admin_url( 'edit.php?category_name=notice' ) ); ?>">소식 글 관리</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ( $notice_q->have_posts() ) : ?>
			<div class="md-news-grid">
				<?php while ( $notice_q->have_posts() ) : $notice_q->the_post();
					$thumb_url = md_post_card_thumb( get_the_ID() ); ?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">📢</span>
								<?php endif; ?>
								<span class="md-news-card__category md-news-card__category--notice">소식</span>
							</div>
							<div class="md-news-card__body">
								<time class="md-news-card__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
									<?php echo esc_html( get_the_date( 'Y년 n월 j일' ) ); ?>
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
		<?php else : ?>
			<div class="md-news-empty">
				<p class="md-news-empty__msg"><?php echo esc_html( md_content( 'news_notice_empty', '아직 등록된 소식이 없습니다.' ) ); ?></p>
				<?php if ( $is_editor ) : ?>
					<p style="margin: 16px 0 0;">
						<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( $notice_new_url ); ?>">＋ 첫 소식 작성하기</a>
					</p>
				<?php else : ?>
					<p style="margin: 8px 0 0; font-size: var(--fs-small); color: var(--color-text-mute);"><?php echo esc_html( md_content( 'news_notice_empty_sub', '곧 새로운 소식으로 찾아뵙겠습니다.' ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 2. 문치과병원 치아이야기 ============ -->
<section class="md-section" id="dental-stories">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'news_stories_eyebrow', '🦷 DENTAL STORIES · 치아이야기' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'news_stories_title', '문치과병원 치아이야기' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo nl2br( esc_html( md_content( 'news_stories_lead', "임플란트·교정·자연치아 살리기·라미네이트·예방 등\n환자분께 도움이 되는 구강 건강 정보를 모았습니다." ) ) ); ?></p>
			<?php if ( $is_editor ) : ?>
				<div class="md-admin-quickpost">
					<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( $story_new_url ); ?>">＋ 새 치아이야기 글쓰기</a>
					<?php if ( $story_cat_obj ) : ?>
						<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( admin_url( 'edit.php?category_name=dental-stories' ) ); ?>">치아이야기 글 관리</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ( $story_q->have_posts() ) : ?>
			<div class="md-news-grid">
				<?php while ( $story_q->have_posts() ) : $story_q->the_post();
					$thumb_url = md_post_card_thumb( get_the_ID() );
				?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">🦷</span>
								<?php endif; ?>
								<span class="md-news-card__category">치아이야기</span>
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

		<?php else : ?>
			<div class="md-news-empty md-news-empty--surface">
				<p class="md-news-empty__msg"><?php echo esc_html( md_content( 'news_stories_empty', '아직 등록된 치아이야기가 없습니다.' ) ); ?></p>
				<?php if ( $is_editor ) : ?>
					<p style="margin: 16px 0 0;">
						<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( $story_new_url ); ?>">＋ 첫 치아이야기 작성하기</a>
					</p>
				<?php else : ?>
					<p style="margin: 8px 0 0; font-size: var(--fs-small); color: var(--color-text-mute);"><?php echo esc_html( md_content( 'news_stories_empty_sub', '곧 환자분께 도움이 되는 정보로 찾아뵙겠습니다.' ) ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
