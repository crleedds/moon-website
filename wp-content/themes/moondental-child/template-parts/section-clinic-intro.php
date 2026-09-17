<?php
/**
 * Section: 30여년 이상 한자리에서 + 4개 전문센터 카드 (v3.86)
 *  순서: 임플란트센터 · 교정센터 · 스마일디자인센터 · 자연치아보존센터 — 각 카드에 종합안내서 링크
 *
 *  v3.27.7: 헤더·카드 제목·리드·야간진료·마무리 문구 모두 Customizer 편집 가능.
 *
 * @package moondental-child
 */
$mc = function ( $k, $d = '' ) { return function_exists( 'md_content' ) ? md_content( $k, $d ) : $d; };

// 카드 리스트 항목은 텍스트영역 하나로 관리 (한 줄에 한 항목, 빈 줄·# 주석 무시)
$md_intro_list = function( $key, $default_lines ) {
	$raw = function_exists( 'md_content' ) ? md_content( $key, $default_lines ) : $default_lines;
	$out = array();
	foreach ( preg_split( "/\r\n|\r|\n/", (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( $line === '' || strpos( $line, '#' ) === 0 ) continue;
		$out[] = $line;
	}
	return $out;
};

/* v3.86 · 4개 전문센터만 남김 (진료과·기술력/시설 카드 제거 · 기술력/시설은 아래 Facility 섹션으로 통합)
 *  각 카드에 해당 센터의 종합안내서 링크 표시 (guide 키 = inc/guides 슬러그) */
$cards = array(
	array(
		'key'  => 'implant',
		'guide'=> 'implant',
		'num'  => '01',
		'icon' => $mc( 'clinic_intro_implant_icon', '🦷' ),
		'title'=> $mc( 'clinic_intro_implant_title', '임플란트센터' ),
		'lead' => $mc( 'clinic_intro_implant_lead', '정밀한 임플란트 시술은 물론, 정기 검진을 통한 사후관리까지 철저히 진행합니다.' ),
		'list' => $md_intro_list( 'clinic_intro_implant_list',
			"고난도 임플란트\n앞니 상실로 불편을 겪는 분들을 위한 즉시 치아 회복\n실패한 임플란트 재수술\n통증을 줄이는 비절개 임플란트\n상악동 거상술\n전악 임플란트\n디지털 장비를 활용한 정밀 네비게이션 임플란트" ),
		'more_label' => $mc( 'clinic_intro_implant_more', '자세히 보기 →' ),
		'more_url'   => home_url( '/임플란트-센터/' ),
	),
	array(
		'key'  => 'ortho',
		'guide'=> 'suresmile',
		'num'  => '02',
		'icon' => $mc( 'clinic_intro_ortho_icon', '✨' ),
		'title'=> $mc( 'clinic_intro_ortho_title', '교정센터' ),
		'lead' => $mc( 'clinic_intro_ortho_lead', 'AI 기반 투명교정 진단 시스템을 도입해 정밀 분석이 가능하며, 환자별 최적의 교정 계획을 제안합니다.' ),
		'list' => $md_intro_list( 'clinic_intro_ortho_list',
			"고난도 교정\n투명교정 (슈어스마일)\n소아 교정\n재교정\n앞니 부분 교정" ),
		'more_label' => $mc( 'clinic_intro_ortho_more', '자세히 보기 →' ),
		'more_url'   => home_url( '/투명교정-센터/' ),
	),
	array(
		'key'  => 'smile',
		'guide'=> 'laminate',
		'num'  => '03',
		'icon' => $mc( 'clinic_intro_smile_icon', '💎' ),
		'title'=> $mc( 'clinic_intro_smile_title', '스마일디자인센터' ),
		'lead' => $mc( 'clinic_intro_smile_lead', '반점치(화이트스팟) 제거·치아 성형·잇몸 미백·최소침습 라미네이트·벌어진 앞니 레진 수복·왜소치 치료 등 다양한 심미적 고민에 맞춤 진단으로 개인별 최적 치료를 제안합니다.' ),
		'list' => $md_intro_list( 'clinic_intro_smile_list',
			"반점치(화이트스팟) 제거\n치아 성형 · 잇몸 미백\n최소침습 라미네이트\n벌어진 앞니 레진 수복\n왜소치 치료\n최소 침습 치료 원칙 — 불필요한 치아 삭제 최소화" ),
		'more_label' => $mc( 'clinic_intro_smile_more', '자세히 보기 →' ),
		'more_url'   => home_url( '/스마일디자인센터/' ),
	),
	array(
		'key'  => 'preserve',
		'guide'=> 'preservation',
		'num'  => '04',
		'icon' => $mc( 'clinic_intro_preserve_icon', '🌿' ),
		// v3.85 · 자연치아 살리기 → 자연치아보존센터 · 부분신경치료·덴탈SPA 추가 (예방클리닉 통합)
		'title'=> $mc( 'clinic_intro_preserve_title', '자연치아보존센터' ),
		'lead' => $mc( 'clinic_intro_preserve_lead', '발치 대신 자연치아를 최대한 보존합니다. 가장 작은 개입부터 차례로 — 충치·신경·잇몸 치료와 치료 뒤를 지키는 덴탈SPA까지 한 곳에서 이어집니다.' ),
		'list' => $md_intro_list( 'clinic_intro_preserve_list',
			"충치치료 — 초기 충치부터 최소 삭제로 정밀 치료\n부분신경치료(치수보존술) — 신경을 전부 제거하지 않고 건강한 치수를 보존\n신경치료 — 미세현미경·CBCT 정밀 근관치료 · 재근관치료\n잇몸치료 — 치주 질환 단계별 치료와 유지관리\n덴탈SPA — 스케일링·에어플로우·불소도포·양치 코칭 60~90분 예방 프로그램" ),
		'more_label' => $mc( 'clinic_intro_preserve_more', '자세히 보기 →' ),
		'more_url'   => home_url( '/자연치아-살리기/' ),
	),
	/* v3.86 · 진료과(05)·기술력/시설(06) 카드 제거 — 진료과는 상단 메뉴·푸터에서, 기술력/시설은 홈 Facility 섹션과 /기술력-시설/ 페이지에서 안내 */
);

$section_eyebrow = $mc( 'clinic_intro_eyebrow', 'CLINIC SYSTEM · 진료 시스템' );
$section_title   = $mc( 'clinic_intro_title',   '30여년 이상 한자리에서, 문치과병원' );
$section_lead    = $mc( 'clinic_intro_lead',    "문치과병원은 각 분과의 원장님들이 다양한 임상경험을 바탕으로\n대학병원식 협진 시스템을 통해 30여년 이상 한자리에서 전문적이고 정직하게 진료합니다." );

$night_title = $mc( 'clinic_intro_night_title', '야간 진료 운영' );
$night_desc  = $mc( 'clinic_intro_night_desc',  '천안시 신부동에 위치한 문치과병원은 바쁜 일상 속에서도 원하는 시간에 진료받으실 수 있도록 월·화·수·금요일 저녁 8시 30분까지 야간진료를 운영합니다.' );

$closer = $mc( 'clinic_intro_closer', '앞으로도 문치과병원은 봉사와 지역의료의 책임을 감당해 나가겠습니다.' );
?>
<section class="md-section md-clinic-intro" aria-label="<?php echo esc_attr( md_content( 'aria_sec_clinic_intro', '문치과병원 진료 시스템 소개' ) ); ?>">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( $section_eyebrow ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $section_title ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( $section_lead ) ); ?>
			</p>
		</header>

		<div class="md-clinic-intro__grid">

			<?php foreach ( $cards as $c ) :
				// v3.86 · 센터별 종합안내서 (inc/guides 데이터에서 코드·제목을 읽음)
				$_g = ( ! empty( $c['guide'] ) && function_exists( 'md_guide_load' ) ) ? md_guide_load( $c['guide'] ) : null;
				$_g_href_map = array( 'implant' => '/guide/implant/', 'suresmile' => '/guide/suresmile/', 'laminate' => '/guide/laminate/', 'preservation' => '/guide/preservation/' );
				$_g_href = ( $_g && isset( $_g_href_map[ $c['guide'] ] ) ) ? home_url( $_g_href_map[ $c['guide'] ] ) : '';
			?>
				<article class="md-clinic-card<?php echo ! empty( $c['guide'] ) ? ' md-clinic-card--' . esc_attr( $c['guide'] ) : ''; ?>">
					<header>
						<span class="md-clinic-card__num"><?php echo esc_html( $c['num'] ); ?></span>
						<span class="md-clinic-card__icon" aria-hidden="true"><?php echo moondental_render_icon( $c['icon'] ); ?></span>
						<h3><?php echo esc_html( $c['title'] ); ?></h3>
					</header>
					<p class="md-clinic-card__lead"><?php echo esc_html( $c['lead'] ); ?></p>
					<?php if ( ! empty( $c['list'] ) ) : ?>
						<ul class="md-clinic-card__list">
							<?php foreach ( $c['list'] as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( $_g && $_g_href ) : ?>
						<a class="md-clinic-card__guide md-center-color--<?php echo esc_attr( $c['guide'] ); ?>" href="<?php echo esc_url( $_g_href ); ?>">
							<span class="md-clinic-card__guide-icon" aria-hidden="true"><?php echo esc_html( $_g['icon'] ?? '📖' ); ?></span>
							<span class="md-clinic-card__guide-code"><?php echo esc_html( $_g['code'] ?? '' ); ?></span>
							<span class="md-clinic-card__guide-title"><?php echo esc_html( $_g['title'] ?? '종합안내서' ); ?></span>
							<span aria-hidden="true">→</span>
						</a>
					<?php endif; ?>
					<a class="md-clinic-card__more" href="<?php echo esc_url( $c['more_url'] ); ?>"><?php echo esc_html( $c['more_label'] ); ?></a>
				</article>
			<?php endforeach; ?>

		</div>

		<?php /* v3.86 · 야간진료 강조 박스·마무리 문구 제거 — 야간 진료는 Facility 섹션 카드와 푸터 진료시간에 있어 중복.
		 * 되살리려면 아래 마크업을 다시 넣으면 된다 ($night_title·$night_desc·$closer 변수는 그대로 둠).
		 *   <aside class="md-clinic-night"><span aria-hidden="true">🌙</span><div><strong>…</strong><p>…</p></div></aside>
		 *   <p class="md-clinic-closer">…</p> */ ?>
	</div>
</section>
