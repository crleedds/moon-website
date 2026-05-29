<?php
/**
 * Section: 시설·장비 하이라이트 (홈)
 *
 * 6 카드 — 디지털 진단·수술·자체 연구소·멸균·응급 장비·야간진료
 *
 * @package moondental-child
 */
$facility = array(
	array(
		'icon'  => '🩻',
		'title' => '디지털 CBCT 3D 진단',
		'desc'  => '저선량 콘빔 CT로 신경·혈관·골 두께까지 3차원으로 정밀 분석합니다.',
	),
	array(
		'icon'  => '🎯',
		'title' => '디지털 가이드 수술',
		'desc'  => '컴퓨터 시뮬레이션으로 임플란트 식립 위치·각도를 사전 설계 — 안전과 정확도를 동시에.',
	),
	array(
		'icon'  => '🏛️',
		'title' => '자체 보철 연구소',
		'desc'  => '한아 임플란트 보철연구소 — 인사이드 워크플로우로 정밀하고 빠른 보철 제작.',
	),
	array(
		'icon'  => '🧴',
		'title' => '멸균 · 감염 관리',
		'desc'  => '의료기관 표준 멸균 프로세스 — 핸드피스·기구 모두 환자 단위로 멸균 관리.',
	),
	array(
		'icon'  => '❤️',
		'title' => '응급 의료 장비 상시',
		'desc'  => '혈압기 · 혈당검사 · 심전도 · 산소포화도 — 전신질환자도 안전한 진료 인프라.',
	),
	array(
		'icon'  => '🌙',
		'title' => '평일 야간 진료',
		'desc'  => '월·화·수·금 ~ 20:30 — 직장인 · 학생도 부담 없이 방문하실 수 있도록.',
	),
);
?>

<section class="md-section" id="facility" aria-label="문치과병원 시설·장비">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Facility & Equipment</span>
			<h2 class="md-section-head__title">정확한 진단과 안전한 진료를 위한 인프라</h2>
			<p class="md-section-head__lead">
				9F~13F 통합 진료센터 — 디지털 진단·수술 시스템과 응급 의료 장비를 갖추고 있습니다.
			</p>
		</header>

		<div class="md-facility-grid">
			<?php foreach ( $facility as $f ) : ?>
				<article class="md-facility">
					<div class="md-facility__icon" aria-hidden="true"><?php echo $f['icon']; ?></div>
					<h3 class="md-facility__title"><?php echo esc_html( $f['title'] ); ?></h3>
					<p class="md-facility__desc"><?php echo esc_html( $f['desc'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
