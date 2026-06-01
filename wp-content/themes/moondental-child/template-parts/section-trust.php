<?php
/**
 * Section: Trust Band — 신뢰 stat 4개
 *
 * 모든 텍스트는 사용자 정의하기 → 홈 콘텐츠 → 신뢰 stat 4개 에서 편집 가능.
 *
 * @package moondental-child
 */
$stats = array(
	array(
		'value' => md_content( 'trust_1_value', '30' ),
		'unit'  => md_content( 'trust_1_unit',  '년' ),
		'label' => md_content( 'trust_1_label', '1995년 개원' ),
		'sub'   => md_content( 'trust_1_sub',   '한자리에서 이어온 신뢰' ),
		'icon'  => '🏥',
	),
	array(
		'value' => md_content( 'trust_2_value', '6' ),
		'unit'  => md_content( 'trust_2_unit',  '과' ),
		'label' => md_content( 'trust_2_label', '전문 진료과' ),
		'sub'   => md_content( 'trust_2_sub',   '보철·교정·보존·치주·소아·외과' ),
		'icon'  => '🦷',
	),
	array(
		'value' => md_content( 'trust_3_value', '3' ),
		'unit'  => md_content( 'trust_3_unit',  '개층' ),
		'label' => md_content( 'trust_3_label', '통합 진료센터' ),
		'sub'   => md_content( 'trust_3_sub',   '9F 종합 · 10F 임플란트 · 11F 교정' ),
		'icon'  => '🏢',
	),
	array(
		'value' => md_content( 'trust_4_value', '1:1' ),
		'unit'  => md_content( 'trust_4_unit',  '' ),
		'label' => md_content( 'trust_4_label', '충분한 사전 상담' ),
		'sub'   => md_content( 'trust_4_sub',   '들어보고, 꼭 필요한 치료만' ),
		'icon'  => '🤝',
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
