<?php
/**
 * Section: 시설·장비 6 카드
 *
 * 모든 텍스트는 사용자 정의하기 → 홈 콘텐츠 → 시설·장비 6 카드 에서 편집 가능.
 *
 * @package moondental-child
 */
$facility = array();
for ( $i = 1; $i <= 6; $i++ ) {
	$facility[] = array(
		'icon'  => md_content( "facility_{$i}_icon",  '' ),
		'title' => md_content( "facility_{$i}_title", '' ),
		'desc'  => md_content( "facility_{$i}_desc",  '' ),
	);
}
?>

<section class="md-section" id="facility" aria-label="문치과병원 시설·장비">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'facility_eyebrow', 'Facility & Equipment' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'facility_title', '천안 만남로 — 정확한 진단과 안전한 진료를 위한 인프라' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'facility_lead', '9·10·11·13F 통합 진료센터 — 디지털 진단·수술 시스템과 응급 의료 장비를 갖추고 있습니다.' ) ) ); ?>
			</p>
		</header>

		<div class="md-facility-grid">
			<?php foreach ( $facility as $f ) : if ( ! $f['title'] ) continue; ?>
				<article class="md-facility">
					<div class="md-facility__icon" aria-hidden="true"><?php echo $f['icon']; ?></div>
					<h3 class="md-facility__title"><?php echo esc_html( $f['title'] ); ?></h3>
					<p class="md-facility__desc"><?php echo esc_html( $f['desc'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
