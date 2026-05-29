<?php
/**
 * Section: 진료 흐름 6단계 (홈)
 *
 * 첫 방문부터 사후관리까지 — 새 환자 불안 해소.
 *
 * @package moondental-child
 */
$steps = array(
	array( 'num' => '01', 'icon' => '📞', 'title' => '예약 / 문의',     'desc' => '전화 · 네이버 예약 · 카카오톡 — 편하신 방법으로 연락' ),
	array( 'num' => '02', 'icon' => '👂', 'title' => '첫 방문 · 상담',  'desc' => '증상과 우려를 충분히 듣고 환자분의 상황을 파악' ),
	array( 'num' => '03', 'icon' => '🔬', 'title' => '정밀 진단',       'desc' => 'CT · 파노라마 · 구강 검사로 정확한 상태 진단' ),
	array( 'num' => '04', 'icon' => '📄', 'title' => '견적 · 치료 계획', 'desc' => '옵션별 비용 · 기간 · 과정을 문서로 안내' ),
	array( 'num' => '05', 'icon' => '🦷', 'title' => '동의 후 치료',    'desc' => '충분히 검토하시고 시작 · 추가 비용 없음' ),
	array( 'num' => '06', 'icon' => '🌿', 'title' => '정기 관리 / A/S', 'desc' => '시술 후 정기 검진과 평생 사후 관리' ),
);
?>

<section class="md-section md-section--surface" id="process" aria-label="문치과 진료 흐름 6단계">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Treatment Flow</span>
			<h2 class="md-section-head__title">첫 방문부터 사후관리까지</h2>
			<p class="md-section-head__lead">
				환자분이 어느 단계에 계신지 항상 알 수 있도록 — 6단계로 안내드립니다.
			</p>
		</header>

		<ol class="md-process">
			<?php foreach ( $steps as $step ) : ?>
				<li class="md-process__item">
					<div class="md-process__head">
						<span class="md-process__icon" aria-hidden="true"><?php echo $step['icon']; ?></span>
						<span class="md-process__no"><?php echo esc_html( $step['num'] ); ?></span>
					</div>
					<h3 class="md-process__title"><?php echo esc_html( $step['title'] ); ?></h3>
					<p class="md-process__desc"><?php echo esc_html( $step['desc'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>

		<div style="text-align:center; margin-top: clamp(28px, 3.5vw, 40px);">
			<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>" data-track="cta-process-reservation">
				📅 첫 단계 시작하기
			</a>
		</div>
	</div>
</section>
