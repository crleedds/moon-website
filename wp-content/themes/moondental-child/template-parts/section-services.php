<?php
/**
 * Section: Services Overview (5 종합 진료)
 *
 * @package moondental-child
 */
$services = moondental_get_services();
?>

<section class="md-section md-section--surface" id="services" aria-label="진료안내">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Services</span>
			<h2 class="md-section-head__title">한 곳에서, 평생 치아 건강을</h2>
			<p class="md-section-head__lead">
				일반진료부터 임플란트·교정·심미·소아예방까지 — 한 분의 환자를 오래 보는 동네 치과의 마음으로 진료합니다.
			</p>
		</header>

		<div class="md-service-grid">
			<?php foreach ( $services as $svc ) :
				// 슬러그가 한글이어도 get_page_by_path 가 그대로 받아들임.
				$page = get_page_by_path( $svc['slug'] );
				$url  = $page ? get_permalink( $page ) : home_url( '/' . rawurlencode( $svc['slug'] ) . '/' );
			?>
				<article class="md-service-card">
					<div class="md-service-card__icon" aria-hidden="true"><?php echo $svc['icon']; ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $svc['title'] ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $svc['desc'] ); ?></p>
					<a class="md-service-card__link" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $svc['title'] ); ?> 자세히 보기
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
