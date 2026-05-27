<?php
/**
 * Section: Trust Band — 신뢰 stat 4개 (히어로 직후)
 *
 * 30년 / 10명 / 3개층 / 1:1 — 가로 띠 형태로 핵심 지표 노출.
 *
 * @package moondental-child
 */
$stats = array(
	array(
		'value'  => '30',
		'unit'   => '년',
		'label'  => '1995년 개원',
		'sub'    => '한자리에서 이어온 신뢰',
		'icon'   => '🏥',
	),
	array(
		'value'  => '10',
		'unit'   => '명',
		'label'  => '각 분야 전문 의료진',
		'sub'    => '보철 · 교정 · 보존 · 외과',
		'icon'   => '👥',
	),
	array(
		'value'  => '3',
		'unit'   => '개층',
		'label'  => '통합 진료센터',
		'sub'    => '9F 종합 · 10F 임플란트 · 11F 교정',
		'icon'   => '🏢',
	),
	array(
		'value'  => '1:1',
		'unit'   => '',
		'label'  => '충분한 사전 상담',
		'sub'    => '들어보고, 꼭 필요한 치료만',
		'icon'   => '🤝',
	),
);
?>

<section class="md-trust" aria-label="문치과병원의 약속">
	<div class="md-container">
		<div class="md-trust__grid">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="md-trust__item md-reveal">
					<span class="md-trust__icon" aria-hidden="true"><?php echo $stat['icon']; ?></span>
					<div class="md-trust__num">
						<span class="md-trust__value" data-count-to="<?php echo esc_attr( $stat['value'] ); ?>"><?php echo esc_html( $stat['value'] ); ?></span>
						<?php if ( $stat['unit'] ) : ?>
							<span class="md-trust__unit"><?php echo esc_html( $stat['unit'] ); ?></span>
						<?php endif; ?>
					</div>
					<div class="md-trust__label"><?php echo esc_html( $stat['label'] ); ?></div>
					<div class="md-trust__sub"><?php echo esc_html( $stat['sub'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
