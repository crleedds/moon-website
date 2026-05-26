<?php
/**
 * Template Name: 의료진 페이지
 * Template Post Type: page
 *
 * 9명 의료진 — 그룹별로 사진/약력 상세 카드 (홈의 요약과 달리 약력 전체 표시).
 *
 * @package moondental-child
 */

get_header();
$groups = moondental_get_team();
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php the_title(); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<p class="md-page-hero__lead">
			9F 종합진료센터 · 10F 임플란트센터 · 11F 교정과<br>
			각 분야 전문 의료진이 한 자리에서 환자 한 분의 평생 치아 건강을 책임집니다.
		</p>
	</div>
</section>

<section class="md-section">
	<div class="md-container">

		<div class="md-page-content" style="max-width:760px; margin:0 auto clamp(32px, 5vw, 64px);">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				} else {
					echo moondental_default_doctors_content();
				}
			endwhile;
			?>
		</div>

		<?php foreach ( $groups as $group ) : ?>
			<div class="md-team-group">
				<h3 class="md-team-group__title"><?php echo esc_html( $group['group'] ); ?></h3>
				<div class="md-doc-list">
					<?php foreach ( $group['members'] as $doc ) :
						$photo_url = moondental_doctor_photo_url( $doc['photo'] ?? '' );
					?>
						<article class="md-doc-row">
							<div class="md-doc-row__media<?php echo $photo_url ? ' has-photo' : ''; ?>">
								<?php if ( $photo_url ) : ?>
									<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $doc['name'] ); ?>" loading="lazy">
								<?php else : ?>
									<span class="md-doc-row__initial"><?php echo esc_html( mb_substr( $doc['name'], -2 ) ); ?></span>
								<?php endif; ?>
							</div>
							<div class="md-doc-row__body">
								<div class="md-doc-row__role"><?php echo esc_html( $doc['role'] ); ?></div>
								<h4 class="md-doc-row__name"><?php echo esc_html( $doc['name'] ); ?></h4>

								<?php if ( ! empty( $doc['philosophy'] ) ) : ?>
									<blockquote class="md-doc-row__quote">
										<?php echo esc_html( $doc['philosophy'] ); ?>
									</blockquote>
								<?php endif; ?>

								<?php
								$bio = $doc['bio'] ?? array();
								if ( is_string( $bio ) ) { $bio = array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", $bio ) ) ); }
								if ( ! empty( $bio ) ) :
								?>
									<ul class="md-doc-row__bio">
										<?php foreach ( $bio as $line ) : ?>
											<li><?php echo esc_html( $line ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
