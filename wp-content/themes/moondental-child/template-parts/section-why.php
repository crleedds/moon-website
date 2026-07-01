<?php
/**
 * Section: Why 문치과 — 4가지 차별점
 *
 * 모든 텍스트는 사용자 정의하기 → 홈 콘텐츠 → Why 차별점 4 카드 에서 편집 가능.
 *
 * @package moondental-child
 */
$points = array(
	array(
		'icon'  => md_content( 'why_1_icon',  '🏥' ),
		'title' => md_content( 'why_1_title', '30여년, 한자리에서' ),
		'desc'  => md_content( 'why_1_desc',  '1995년부터 천안 만남로 한자리에서 진료해온 동네 치과. 환자 한 분의 평생 치아를 길게 봅니다.' ),
	),
	array(
		'icon'  => md_content( 'why_2_icon',  '🏢' ),
		'title' => md_content( 'why_2_title', '통합 진료센터' ),
		'desc'  => md_content( 'why_2_desc',  '9F 종합·10F 임플란트·11F 교정 — 분야별 전문 의료진의 협진을 한 곳에서 받으실 수 있습니다.' ),
	),
	array(
		'icon'  => md_content( 'why_3_icon',  '❤️' ),
		'title' => md_content( 'why_3_title', '전신질환 안심 진료' ),
		'desc'  => md_content( 'why_3_desc',  '혈압기·당검사·심전도·산소포화도 상시 보유. 고혈압·당뇨·심장질환자도 안전하게 진료합니다.' ),
	),
	array(
		'icon'  => md_content( 'why_4_icon',  '🛡️' ),
		'title' => md_content( 'why_4_title', '평생 A/S 시스템' ),
		'desc'  => md_content( 'why_4_desc',  '시술 후 정기 검진과 문제 발생 시 책임 대응. 비용은 시술 시점에 한 번, 관리는 평생.' ),
	),
);
?>

<section class="md-section" id="why" aria-label="문치과병원의 차별점">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'why_eyebrow', 'Why Moon Dental' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'why_title', '천안·아산에서 왜 문치과병원을 찾으시나요?' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'why_lead', '천안 만남로에서 30여년 — 천안·아산 환자분들이 선택해온 이유 4가지로 정리해드립니다.' ) ) ); ?>
			</p>
		</header>

		<div class="md-why-grid">
			<?php foreach ( $points as $p ) : ?>
				<article class="md-why">
					<div class="md-why__icon" aria-hidden="true"><?php echo $p['icon']; ?></div>
					<h3 class="md-why__title"><?php echo esc_html( $p['title'] ); ?></h3>
					<p class="md-why__desc"><?php echo esc_html( $p['desc'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
