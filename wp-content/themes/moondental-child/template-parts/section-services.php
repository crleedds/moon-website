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
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'services_eyebrow', 'CLINICAL SERVICES · 천안 진료항목' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'services_title', '천안에서 한 곳에서, 평생 치아 건강을' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'services_lead', '천안 임플란트·천안 투명교정·천안 라미네이트·천안 자연치아 살리기·천안 사랑니 발치까지 — 한 분의 환자를 오래 보는 천안 만남로 치과의 마음으로 진료합니다.' ) ) ); ?>
			</p>
		</header>

		<div class="md-service-grid">
			<?php foreach ( $services as $idx => $svc ) :
				// 슬러그가 한글이어도 get_page_by_path 가 그대로 받아들임.
				$page = get_page_by_path( $svc['slug'] );
				$url  = $page ? get_permalink( $page ) : home_url( '/' . rawurlencode( $svc['slug'] ) . '/' );
				$num  = sprintf( '%02d', $idx + 1 );
			?>
				<article class="md-service-card md-reveal">
					<span class="md-service-card__num" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
					<div class="md-service-card__icon" aria-hidden="true"><?php echo $svc['icon']; ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $svc['title'] ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $svc['desc'] ); ?></p>
					<span class="md-service-card__more" aria-hidden="true">자세히 보기 <span class="md-service-card__arrow">→</span></span>
					<a class="md-service-card__link" href="<?php echo esc_url( $url ); ?>">
						<span class="md-screen-reader-text"><?php echo esc_html( $svc['title'] ); ?> 자세히 보기</span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
