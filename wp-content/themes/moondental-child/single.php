<?php
/**
 * 단일 글 (소식) 페이지
 *
 * 네이버 블로그에서 import 된 본문을 표시.
 * 본문 끝에 원문 링크 + 카테고리 + 태그.
 *
 * @package moondental-child
 */

get_header();
$info = moondental_get_info();

if ( have_posts() ) :
	the_post();
	$category = get_post_meta( get_the_ID(), 'moondental_naver_category',  true );
	$src_url  = get_post_meta( get_the_ID(), 'moondental_naver_source_url', true );
?>

<section class="md-page-hero md-page-hero--news">
	<div class="md-container md-container--narrow">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸
			<a href="<?php echo esc_url( home_url( '/소식/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_news', '소식' ) ); ?></a> ▸
			<span><?php the_title(); ?></span>
		</nav>
		<?php if ( $category ) : ?>
			<div class="md-single__category"><?php echo esc_html( $category ); ?></div>
		<?php endif; ?>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<time class="md-single__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
			<?php echo esc_html( get_the_date( md_content( 'date_format', 'Y년 n월 j일' ) ) ); ?>
		</time>
	</div>
</section>

<article class="md-section">
	<div class="md-container md-container--narrow">
		<div class="md-single__content">
			<?php the_content(); ?>
		</div>

		<footer class="md-single__footer">
			<?php $tags = get_the_tags(); if ( $tags ) : ?>
				<div class="md-single__tags">
					<?php foreach ( $tags as $tag ) : ?>
						<span class="md-tag">#<?php echo esc_html( $tag->name ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $src_url ) : ?>
				<p class="md-single__source">
					<?php
					$link_html = '<a href="' . esc_url( $src_url ) . '" target="_blank" rel="noopener">' . esc_html( md_content( 'ui_original_naver_link', '네이버 블로그' ) ) . '</a>';
					echo wp_kses( str_replace( '{link}', $link_html, md_content( 'ui_original_naver_tpl', '이 글의 원문은 {link} 에서도 보실 수 있습니다.' ) ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) );
					?>
				</p>
			<?php endif; ?>

			<div class="md-single__nav">
				<a class="md-btn md-btn-ghost" href="<?php echo esc_url( home_url( '/소식/' ) ); ?>"><?php echo esc_html( md_content( 'ui_back_to_news', '← 소식 목록으로' ) ); ?></a>
			</div>
		</footer>
	</div>
</article>

<?php
	/* 관련 글 (같은 카테고리 최근 3개) */
	$cats = wp_get_post_categories( get_the_ID() );
	if ( ! empty( $cats ) ) :
		$related = new WP_Query( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'category__in'   => $cats,
			'post__not_in'   => array( get_the_ID() ),
			'meta_key'       => 'moondental_naver_log_no',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( $related->have_posts() ) :
?>
	<section class="md-section md-section--surface md-section--sm">
		<div class="md-container">
			<header class="md-section-head" style="margin-bottom:32px;">
				<h2 class="md-section-head__title" style="font-size:var(--fs-h3);"><?php echo esc_html( md_content( 'ui_related_posts', '관련 글' ) ); ?></h2>
			</header>
			<div class="md-news-grid">
				<?php while ( $related->have_posts() ) : $related->the_post();
					$thumb_url = get_post_meta( get_the_ID(), 'moondental_naver_thumb_url', true );
					$rcat      = get_post_meta( get_the_ID(), 'moondental_naver_category',  true );
				?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php the_permalink(); ?>">
							<div class="md-news-card__media">
								<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="md-news-card__media-fallback">📝</span>
								<?php endif; ?>
								<?php if ( $rcat ) : ?><span class="md-news-card__category"><?php echo esc_html( $rcat ); ?></span><?php endif; ?>
							</div>
							<div class="md-news-card__body">
								<time class="md-news-card__date"><?php echo esc_html( get_the_date( md_content( 'date_format', 'Y년 n월 j일' ) ) ); ?></time>
								<h3 class="md-news-card__title"><?php the_title(); ?></h3>
							</div>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
<?php
		endif;
	endif;
endif;

get_template_part( 'template-parts/section', 'cta' );
get_footer();
