<?php
/**
 * Template Name: 소식 (네이버 블로그 RSS)
 * Template Post Type: page
 *
 * 네이버 블로그의 글을 RSS로 가져와 카드 그리드로 표시.
 * 카드 클릭 시 새 탭으로 네이버 블로그 원문이 열린다.
 * 캐싱 1시간 — 새 글은 최대 1시간 이내에 사이트에 반영.
 *
 * @package moondental-child
 */

get_header();
$items = moondental_fetch_naver_blog( 20 );
$info  = moondental_get_info();
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>소식</span>
		</nav>
		<h1 class="md-page-hero__title">소식</h1>
		<p class="md-page-hero__lead">
			문치과병원의 진료 안내·치아 상식·병원 소식을 모았습니다.<br>
			모든 글은 <a href="<?php echo esc_url( $info['blog_url'] ); ?>" target="_blank" rel="noopener">네이버 블로그</a>에서 실시간으로 가져옵니다.
		</p>
	</div>
</section>

<section class="md-section">
	<div class="md-container">

		<?php if ( empty( $items ) ) : ?>
			<div class="md-news-empty">
				<p>블로그 글을 불러오지 못했습니다.</p>
				<p><a class="md-btn md-btn-secondary" href="<?php echo esc_url( $info['blog_url'] ); ?>" target="_blank" rel="noopener">네이버 블로그에서 직접 보기 →</a></p>
			</div>
		<?php else : ?>
			<div class="md-news-grid">
				<?php foreach ( $items as $post ) : ?>
					<article class="md-news-card">
						<a class="md-news-card__link" href="<?php echo esc_url( $post['link'] ); ?>" target="_blank" rel="noopener">
							<div class="md-news-card__media">
								<?php if ( $post['thumb'] ) : ?>
									<img src="<?php echo esc_url( $post['thumb'] ); ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
								<?php else : ?>
									<span class="md-news-card__media-fallback" aria-hidden="true">📝</span>
								<?php endif; ?>
								<?php if ( $post['category'] ) : ?>
									<span class="md-news-card__category"><?php echo esc_html( $post['category'] ); ?></span>
								<?php endif; ?>
							</div>
							<div class="md-news-card__body">
								<time class="md-news-card__date" datetime="<?php echo esc_attr( date_i18n( 'Y-m-d', $post['date'] ) ); ?>">
									<?php echo esc_html( date_i18n( 'Y년 n월 j일', $post['date'] ) ); ?>
								</time>
								<h3 class="md-news-card__title"><?php echo esc_html( $post['title'] ); ?></h3>
								<p class="md-news-card__excerpt"><?php echo esc_html( $post['excerpt'] ); ?></p>
							</div>
						</a>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="md-news-footer">
				<p>전체 글 보기는 네이버 블로그에서 이어집니다.</p>
				<a class="md-btn md-btn-ghost" href="<?php echo esc_url( $info['blog_url'] ); ?>" target="_blank" rel="noopener">
					네이버 블로그 →
				</a>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
