<?php
/**
 * Section: Services Overview (5 종합 진료)
 *
 * @package moondental-child
 */
$services = moondental_get_services();
?>

<section class="md-section md-section--surface" id="services" aria-label="<?php echo esc_attr( md_content( 'aria_sec_services', '진료안내' ) ); ?>">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'services_eyebrow', 'CLINICAL SERVICES · 천안·아산 진료항목' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'services_title', '천안·아산에서 한 곳에서, 평생 치아 건강을' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'services_lead', '천안·아산 임플란트·투명교정·라미네이트·자연치아 살리기·사랑니 발치까지 — 한 분의 환자를 오래 보는 천안 만남로 치과의 마음으로 진료합니다.' ) ) ); ?>
			</p>
		</header>

		<?php
		// v3.44.191 · 센터 카드만 컬러 (센터 아닌 항목은 색 안 넣음)
		$svc_color_map = array(
			'임플란트-센터'   => 'md-service-card--implant',   // 임플란트센터
			'투명교정-센터'   => 'md-service-card--suresmile', // 교정센터
			'스마일디자인센터' => 'md-service-card--laminate',  // 스마일디자인센터
		);
		?>
		<div class="md-service-grid">
			<?php foreach ( $services as $idx => $svc ) :
				// v3.44.44 · hidden=true 카드는 홈 그리드에서 스킵
				if ( ! empty( $svc['hidden'] ) ) continue;
				// 슬러그가 한글이어도 get_page_by_path 가 그대로 받아들임.
				$page = get_page_by_path( $svc['slug'] );
				$url  = $page ? get_permalink( $page ) : home_url( '/' . rawurlencode( $svc['slug'] ) . '/' );
				$num  = sprintf( '%02d', $idx + 1 );
				$svc_color_cls = isset( $svc_color_map[ $svc['slug'] ] ) ? ' ' . $svc_color_map[ $svc['slug'] ] : '';
			?>
				<article class="md-service-card md-reveal<?php echo esc_attr( $svc_color_cls ); ?>">
					<span class="md-service-card__num" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
					<div class="md-service-card__icon" aria-hidden="true"><?php echo moondental_render_icon( $svc['icon'] ); ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $svc['title'] ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $svc['desc'] ); ?></p>
					<span class="md-service-card__more" aria-hidden="true"><?php echo esc_html( md_content( 'micro_more_label', '자세히 보기 →' ) ); ?></span>
					<a class="md-service-card__link" href="<?php echo esc_url( $url ); ?>">
						<span class="md-screen-reader-text"><?php echo esc_html( $svc['title'] ); ?> 자세히 보기</span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
