<?php
/**
 * Section: 홈 첫 화면 통합 (Hero + Mission + Trust Stats)
 *
 * v3.34.6 · 사용자 요청 · 3개 섹션을 한 화면에.
 *   히어로 리드 문단·CTA 버튼 제거 · 사명 quote · 지정기관 · trust 4stat 결합.
 *
 * @package moondental-child
 */
$info = moondental_get_info();

/* Hero title · v3.34.8 · SEO 강도 강 (지역+전국 신뢰)
 * v3.38.5 · md_content 우선, 기존 theme_mod 폴백 (사용자 정의값 안전 보존) */
$eyebrow = md_content( 'hero_eyebrow', get_theme_mod( 'moondental_hero_eyebrow', '천안·아산 대표 치과병원 · 전국에서 찾아오는' ) );
$title_a = md_content( 'hero_title_a', get_theme_mod( 'moondental_hero_title_a', '천안·아산에서 30여년,' ) );
$title_b = md_content( 'hero_title_b', get_theme_mod( 'moondental_hero_title_b', '전국 환자가 신뢰하는 통합 진료' ) );

/* Mission */
$mission_eyebrow = md_content( 'mission_band_eyebrow', 'OUR MISSION · 한아의료재단 문치과병원의 사명' );
$mission_text    = md_content( 'mission_band_text',
	'한아의료재단 문치과병원의 사명은 품격 있는 진료와 서비스로 환자의 신뢰를 받으며, 나눔과 봉사를 통해 사회에 공헌하는 가장 인정받는 병원이 되는 것입니다.'
);
$certs_raw = md_content( 'mission_certs',
	"🏥|국가지정 구강검진 치과\n🌐|외국인환자 유치 의료기관\n🪖|미군 및 가족 치료기관\n🦷|천안시 치아사랑사업 협력병원\n🔗|삼성서울병원 협력병원\n➕|대한적십자사 협력병원"
);
$certifications = array();
foreach ( preg_split( "/\r\n|\r|\n/", (string) $certs_raw ) as $line ) {
	$line = trim( $line );
	if ( $line === '' || strpos( $line, '#' ) === 0 ) continue;
	$parts = array_map( 'trim', explode( '|', $line, 2 ) );
	if ( count( $parts ) < 2 || $parts[1] === '' ) continue;
	$certifications[] = array( 'icon' => $parts[0], 'label' => $parts[1] );
}

/* Trust stats */
$stats = array(
	array(
		'value' => md_content( 'trust_1_value', '30' ),
		'unit'  => md_content( 'trust_1_unit',  '년' ),
		'label' => md_content( 'trust_1_label', '1995년 개원' ),
	),
	array(
		'value' => md_content( 'trust_2_value', '11' ),
		'unit'  => md_content( 'trust_2_unit',  '개' ),
		'label' => md_content( 'trust_2_label', '전문 진료 영역' ),
	),
	array(
		'value' => md_content( 'trust_3_value', '4' ),
		'unit'  => md_content( 'trust_3_unit',  '개층' ),
		'label' => md_content( 'trust_3_label', '통합 진료센터' ),
	),
	array(
		'value' => md_content( 'trust_4_value', '1:1' ),
		'unit'  => md_content( 'trust_4_unit',  '' ),
		'label' => md_content( 'trust_4_label', '충분한 사전 상담' ),
	),
);
?>

<?php
/* v3.44.170 · Customizer 배경 이미지 · 한글 URL 자동 인코딩 (404 방지) */
$_hero_bg      = get_theme_mod( 'moondental_home_hero_bg', '' );
$_hero_opacity = (int) get_theme_mod( 'moondental_home_hero_bg_opacity', 35 );
if ( $_hero_opacity < 0 ) $_hero_opacity = 0;
if ( $_hero_opacity > 100 ) $_hero_opacity = 100;

// 한글 등 non-ASCII 문자 URL 인코딩 (WordPress esc_url 이 처리 못 함)
$_hero_bg_encoded = '';
if ( $_hero_bg ) {
	$_hero_bg_encoded = preg_replace_callback(
		'/[^\x21-\x7E]/u',
		function ( $m ) { return rawurlencode( $m[0] ); },
		$_hero_bg
	);
}
$_hero_style = $_hero_bg_encoded
	? sprintf( '--md-hero-bg:url("%s"); --md-hero-overlay:%.2f;', esc_url( $_hero_bg_encoded ), $_hero_opacity / 100 )
	: '';
?>
<section class="md-hero-combined<?php echo $_hero_bg ? ' md-hero-combined--has-bg' : ''; ?>" style="<?php echo esc_attr( $_hero_style ); ?>" aria-label="<?php echo esc_attr( md_content( 'aria_sec_hero_combined', '문치과병원 첫 화면' ) ); ?>">
	<div class="md-container">

		<!-- 1) 제목 -->
		<div class="md-hero-combined__title-block">
			<span class="md-hero-combined__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
			<h1 class="md-hero-combined__title">
				<?php echo esc_html( $title_a ); ?><br>
				<em><?php echo esc_html( $title_b ); ?></em>
			</h1>
		</div>

		<!-- 2) 사명 quote -->
		<blockquote class="md-hero-combined__quote">
			<p>"<?php echo esc_html( $mission_text ); ?>"</p>
		</blockquote>

		<!-- 3) 지정·협력 의료기관 -->
		<?php if ( $certifications ) : ?>
			<ul class="md-hero-combined__certs" aria-label="<?php echo esc_attr( md_content( 'aria_sec_hero_certs', '국가지정·협력 의료기관' ) ); ?>">
				<?php foreach ( $certifications as $c ) : ?>
					<li>
						<span aria-hidden="true"><?php echo $c['icon']; ?></span>
						<span><?php echo esc_html( $c['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<!-- 4) 신뢰 지표 4개 -->
		<div class="md-hero-combined__stats">
			<?php foreach ( $stats as $stat ) : ?>
				<div class="md-hero-combined__stat">
					<div class="md-hero-combined__stat-num">
						<?php
						$_val = $stat['value'];
						$_count_attr = is_numeric( $_val ) ? ' data-count-to="' . esc_attr( $_val ) . '"' : '';
						?>
						<span class="md-hero-combined__stat-value"<?php echo $_count_attr; ?>><?php echo esc_html( $_val ); ?></span>
						<?php if ( $stat['unit'] ) : ?>
							<span class="md-hero-combined__stat-unit"><?php echo esc_html( $stat['unit'] ); ?></span>
						<?php endif; ?>
					</div>
					<div class="md-hero-combined__stat-label"><?php echo esc_html( $stat['label'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>

	</div>
</section>
