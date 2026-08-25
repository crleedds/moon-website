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

// v3.30.0 · 협력·지정 의료기관 · Customizer 텍스트영역 (한 줄에 하나 "이모지|라벨")
$certs_raw = function_exists( 'md_content' )
	? md_content( 'mission_certs', "🏥|국가지정 구강검진 병원\n🌐|외국인환자 유치 의료기관\n🪖|미군 및 가족 치료기관\n🦷|천안시 치아사랑사업 협력병원\n🔗|삼성서울병원 협력병원\n➕|대한적십자사 협력병원" )
	: "🏥|국가지정 구강검진 병원\n🌐|외국인환자 유치 의료기관\n🪖|미군 및 가족 치료기관\n🦷|천안시 치아사랑사업 협력병원\n🔗|삼성서울병원 협력병원\n➕|대한적십자사 협력병원";
$certifications = array();
foreach ( preg_split( "/\r\n|\r|\n/", (string) $certs_raw ) as $line ) {
	$line = trim( $line );
	if ( $line === '' || strpos( $line, '#' ) === 0 ) continue;
	$parts = array_map( 'trim', explode( '|', $line, 2 ) );
	if ( count( $parts ) < 2 || $parts[1] === '' ) continue;
	$certifications[] = array( 'icon' => $parts[0], 'label' => $parts[1] );
}
?>

<section class="md-section md-mission-band" aria-label="<?php echo esc_attr( md_content( 'aria_sec_mission', '병원 사명 · 협력 기관' ) ); ?>">
	<div class="md-container md-container--narrow">
		<div class="md-mission-band__inner">
			<span class="md-mission-band__eyebrow"><?php echo esc_html( $mission_eyebrow ); ?></span>
			<blockquote class="md-mission-band__quote">
				<p>"<?php echo esc_html( $mission_text ); ?>"</p>
			</blockquote>

			<ul class="md-mission-band__certs" aria-label="<?php echo esc_attr( md_content( 'aria_sec_hero_certs', '국가지정·협력 의료기관' ) ); ?>">
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
