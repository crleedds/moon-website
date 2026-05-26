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

		<?php if ( ! empty( $history ) ) :
			// 연도별로 그룹화 (디자인상 연도 헤더가 한 번씩 들어가도록)
			$by_year = array();
			foreach ( $history as $row ) {
				$y = isset( $row['year'] ) ? $row['year'] : '';
				if ( ! isset( $by_year[ $y ] ) ) $by_year[ $y ] = array();
				$by_year[ $y ][] = $row;
			}
		?>
			<div class="md-timeline" aria-label="문치과병원 연혁">
				<?php foreach ( $by_year as $year => $rows ) : ?>
					<section class="md-timeline-group">
						<h2 class="md-timeline-group__year"><?php echo esc_html( $year ); ?></h2>
						<ol class="md-timeline-group__items">
							<?php foreach ( $rows as $row ) :
								$month = isset( $row['month'] ) ? $row['month'] : '';
								$title = isset( $row['title'] ) ? $row['title'] : '';
								$desc  = isset( $row['desc'] )  ? $row['desc']  : '';
								$photo = isset( $row['photo'] ) ? moondental_history_photo_url( $row['photo'] ) : false;
							?>
								<li class="md-timeline__item<?php echo $photo ? ' has-photo' : ''; ?>">
									<div class="md-timeline__month"><?php echo $month ? esc_html( ltrim( $month, '0' ) ) . '월' : ''; ?></div>
									<div class="md-timeline__dot" aria-hidden="true"></div>
									<div class="md-timeline__body">
										<?php if ( $photo ) : ?>
											<div class="md-timeline__photo">
												<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
											</div>
										<?php endif; ?>
										<?php if ( $title ) : ?><h3 class="md-timeline__title"><?php echo esc_html( $title ); ?></h3><?php endif; ?>
										<?php if ( $desc ) : ?><p class="md-timeline__desc"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
