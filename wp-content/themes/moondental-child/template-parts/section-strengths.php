<?php
/**
 * Section: 문치과병원의 강점 9 카드 (클릭 가능 → 상세 페이지로 연결).
 *  /기술력-시설/ 페이지 등에서 사용.
 *  데이터: moondental_get_strengths() (inc/strengths.php)
 *
 * @package moondental-child
 */
if ( ! function_exists( 'moondental_get_strengths' ) ) return;

$strengths = moondental_get_strengths();
$eyebrow   = function_exists( 'md_content' ) ? md_content( 'strengths_eyebrow', 'STRENGTHS' ) : 'STRENGTHS';
$title     = function_exists( 'md_content' ) ? md_content( 'strengths_title',   '문치과병원의 강점' ) : '문치과병원의 강점';
$lead      = function_exists( 'md_content' ) ? md_content( 'strengths_lead',    '의료기관 종별·시설·운영·임상 측면에서 갖추고 있는 9가지 강점입니다. 각 항목을 클릭하시면 자세한 설명을 보실 수 있습니다.' ) : '의료기관 종별·시설·운영·임상 측면에서 갖추고 있는 9가지 강점입니다. 각 항목을 클릭하시면 자세한 설명을 보실 수 있습니다.';
?>
<section class="md-section" aria-label="문치과병원의 강점">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $title ); ?></h2>
			<p class="md-section-head__lead"><?php echo nl2br( esc_html( $lead ) ); ?></p>
		</header>

		<div class="md-strengths">
			<?php foreach ( $strengths as $s ) :
				$url = home_url( '/강점/' . $s['slug'] . '/' ); ?>
				<a class="md-strength md-strength--link" href="<?php echo esc_url( $url ); ?>" data-track="cta-strength-<?php echo esc_attr( $s['slug'] ); ?>">
					<div class="md-strength__icon" aria-hidden="true"><?php echo $s['icon']; ?></div>
					<div class="md-strength__body">
						<h3 class="md-strength__label"><?php echo esc_html( $s['label'] ); ?></h3>
						<p class="md-strength__value"><?php echo esc_html( $s['value'] ); ?></p>
					</div>
					<span class="md-strength__arrow" aria-hidden="true">→</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
