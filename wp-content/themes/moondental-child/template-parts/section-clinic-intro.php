<?php
/**
 * Section: 30년 이상 한자리에서 + 6개 진료 영역 카드
 *  사용자 제공 콘텐츠 — 메뉴 구조에 맞춰 6 카드로 정렬.
 *  순서: 임플란트센터 · 교정센터 · 스마일디자인센터 · 자연치아살리기 · 진료과 · 기술력/시설
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

$cards = array(
	array(
		'key'  => 'implant',
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
		'num'  => '04',
		'icon' => $mc( 'clinic_intro_preserve_icon', '🌿' ),
		'title'=> $mc( 'clinic_intro_preserve_title', '자연치아 살리기' ),
		'lead' => $mc( 'clinic_intro_preserve_lead', '문치과병원은 발치 대신 자연치아를 최대한 보존하는 치료를 우선합니다.' ),
		'list' => $md_intro_list( 'clinic_intro_preserve_list',
			"충치치료 — 초기 충치부터 정밀하게 진단·치료\n신경치료 — 손상된 치수를 살려 자연치아 보존\n잇몸치료 — 치주 질환 관리로 치아 수명 연장" ),
		'more_label' => $mc( 'clinic_intro_preserve_more', '자세히 보기 →' ),
		'more_url'   => home_url( '/자연치아-살리기/' ),
	),
	array(
		'key'  => 'dept',
		'num'  => '05',
		'icon' => $mc( 'clinic_intro_dept_icon', '🏥' ),
		'title'=> $mc( 'clinic_intro_dept_title', '진료과' ),
		'lead' => $mc( 'clinic_intro_dept_lead', '전 분과 전문 의료진이 분야별 진료를 한 자리에서 협진합니다.' ),
		'list' => $md_intro_list( 'clinic_intro_dept_list',
			"턱관절 클리닉 — 통증·기능 장애 진료\n이갈이 · 이악물기\n매복 사랑니 발치\n소아치과\n예방클리닉 — 전문예방치료실 · 덴탈 스파 프로그램" ),
		'more_label' => $mc( 'clinic_intro_dept_more', '예방클리닉 자세히 →' ),
		'more_url'   => home_url( '/예방클리닉/' ),
	),
	array(
		'key'  => 'facility',
		'num'  => '06',
		'icon' => $mc( 'clinic_intro_facility_icon', '🔬' ),
		'title'=> $mc( 'clinic_intro_facility_title', '기술력 / 시설' ),
		'lead' => $mc( 'clinic_intro_facility_lead', '자체 디지털센터·기공소 운영. 물방울 레이저 5대 보유 — 통증·출혈 적고 빠른 회복.' ),
		'list' => $md_intro_list( 'clinic_intro_facility_list',
			"One Day 보철 치료까지 가능 (구강 정밀 스캔)\n의료진·기공사 긴밀 소통으로 맞춤형 보철\n오차 최소화 — 높은 정확도 · 내원 횟수 단축\n원내 기공소 신속 수정·A/S\n물방울 레이저 — 임플란트 주위염·잇몸 성형·시린이·신경치료·구내염·점액낭종" ),
		'more_label' => $mc( 'clinic_intro_facility_more', '자세히 보기 →' ),
		'more_url'   => home_url( '/기술력-시설/' ),
	),
);

$section_eyebrow = $mc( 'clinic_intro_eyebrow', 'CLINIC SYSTEM · 진료 시스템' );
$section_title   = $mc( 'clinic_intro_title',   '30년 이상 한자리에서, 문치과병원' );
$section_lead    = $mc( 'clinic_intro_lead',    "문치과병원은 각 분과의 원장님들이 다양한 임상경험을 바탕으로\n대학병원식 협진 시스템을 통해 30년 이상 한자리에서 전문적이고 정직하게 진료합니다." );

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

			<?php foreach ( $cards as $c ) : ?>
				<article class="md-clinic-card">
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
					<a class="md-clinic-card__more" href="<?php echo esc_url( $c['more_url'] ); ?>"><?php echo esc_html( $c['more_label'] ); ?></a>
				</article>
			<?php endforeach; ?>

		</div>

		<!-- 야간진료 강조 박스 -->
		<aside class="md-clinic-night">
			<span aria-hidden="true">🌙</span>
			<div>
				<strong><?php echo esc_html( $night_title ); ?></strong>
				<p><?php echo esc_html( $night_desc ); ?></p>
			</div>
		</aside>

		<!-- 마무리 메시지 -->
		<p class="md-clinic-closer">
			<?php echo esc_html( $closer ); ?>
		</p>
	</div>
</section>
