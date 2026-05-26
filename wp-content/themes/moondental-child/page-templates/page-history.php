<?php
/**
 * Template Name: 역사 (연표)
 * Template Post Type: page
 *
 * 병원소개 → 역사 페이지. moondental_get_history() 의 항목을 세로 타임라인으로 표시.
 * WP 편집기 본문이 있으면 도입부 자리에 우선 출력, 없으면 기본 도입글.
 *
 * @package moondental-child
 */

get_header();
$history = moondental_get_history();
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸
			<a href="<?php echo esc_url( home_url( '/병원소개/' ) ); ?>">병원소개</a> ▸
			<span>역사</span>
		</nav>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
	</div>
</section>

<section class="md-section">
	<div class="md-container md-container--narrow">

		<article class="md-page-content" style="margin-bottom: clamp(40px, 5vw, 64px);">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				} else {
					echo moondental_default_history_intro();
				}
			endwhile;
			?>
		</article>

		<?php if ( ! empty( $history ) ) : ?>
			<ol class="md-timeline" aria-label="문치과병원 연혁">
				<?php foreach ( $history as $row ) :
					$year  = isset( $row['year'] )  ? $row['year']  : '';
					$month = isset( $row['month'] ) ? $row['month'] : '';
					$title = isset( $row['title'] ) ? $row['title'] : '';
					$desc  = isset( $row['desc'] )  ? $row['desc']  : '';
				?>
					<li class="md-timeline__item">
						<div class="md-timeline__year">
							<?php echo esc_html( $year ); ?>
							<?php if ( $month ) : ?><span class="md-timeline__month"><?php echo esc_html( $month ); ?>월</span><?php endif; ?>
						</div>
						<div class="md-timeline__dot" aria-hidden="true"></div>
						<div class="md-timeline__body">
							<?php if ( $title ) : ?><h3 class="md-timeline__title"><?php echo esc_html( $title ); ?></h3><?php endif; ?>
							<?php if ( $desc ) : ?><p class="md-timeline__desc"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
