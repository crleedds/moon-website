<?php
/**
 * Section: 한아의료재단 문치과병원 사명 + 협력 의료기관 6곳
 *
 *  사용자 요청 — 사명 statement + 6개 지정·협력 의료기관 노출.
 *
 * @package moondental-child
 */
$mission_eyebrow = function_exists( 'md_content' ) ? md_content( 'mission_band_eyebrow', 'OUR MISSION · 한아의료재단 문치과병원의 사명' ) : 'OUR MISSION · 한아의료재단 문치과병원의 사명';
$mission_text    = function_exists( 'md_content' ) ? md_content( 'mission_band_text',
	'한아의료재단 문치과병원의 사명은 품격 있는 진료와 서비스로 환자의 신뢰를 받으며, 나눔과 봉사를 통해 사회에 공헌하는 가장 인정받는 병원이 되는 것입니다.'
) : '한아의료재단 문치과병원의 사명은 품격 있는 진료와 서비스로 환자의 신뢰를 받으며, 나눔과 봉사를 통해 사회에 공헌하는 가장 인정받는 병원이 되는 것입니다.';

$certifications = array(
	array( 'icon' => '🏥', 'label' => '국가지정 구강검진 치과' ),
	array( 'icon' => '🌐', 'label' => '외국인환자 유치 의료기관' ),
	array( 'icon' => '🪖', 'label' => '미군 및 가족 치료기관' ),
	array( 'icon' => '🦷', 'label' => '천안시 치아사랑사업 협력병원' ),
	array( 'icon' => '🏥', 'label' => '삼성서울병원 협력병원' ),
	array( 'icon' => '➕', 'label' => '대한적십자사 협력병원' ),
);
?>

<section class="md-section md-mission-band" aria-label="병원 사명 · 협력 기관">
	<div class="md-container md-container--narrow">
		<div class="md-mission-band__inner">
			<span class="md-mission-band__eyebrow"><?php echo esc_html( $mission_eyebrow ); ?></span>
			<blockquote class="md-mission-band__quote">
				<p>"<?php echo esc_html( $mission_text ); ?>"</p>
			</blockquote>

			<ul class="md-mission-band__certs" aria-label="국가지정·협력 의료기관">
				<?php foreach ( $certifications as $c ) : ?>
					<li>
						<span aria-hidden="true"><?php echo $c['icon']; ?></span>
						<span><?php echo esc_html( $c['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
