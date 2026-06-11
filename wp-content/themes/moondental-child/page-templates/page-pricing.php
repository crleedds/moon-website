<?php
/**
 * Template Name: 비용 안내
 * Template Post Type: page
 *
 * 비용 안내 페이지 — 약속 카드 hero / 4단계 프로세스 / 정책 / 예상비용 탭 /
 * 보험 적용 비교 / 결제 안내 / CTA / 위치.
 *
 * 슬러그: /비용-안내/ /비용안내/ /비급여-진료비/ /진료비안내/ /pricing/
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$kakao_url  = $info['kakao_url'] ?? '';
$naver_book = $info['naver_place'] ?? '';
$naver_map  = $info['naver_map_url'] ?? '';

// 3-Map URLs
$q_full  = rawurlencode( '한아의료재단 문치과병원 천안 만남로 52' );
$q_short = rawurlencode( '한아의료재단 문치과병원' );
$map_google = $info['google_map_url'] ?: 'https://maps.app.goo.gl/MNt59kcxeKL92nCU9';
$map_naver  = $naver_map ?: 'https://map.naver.com/p/search/' . $q_short;
$map_kakao  = 'https://map.kakao.com/?q=' . $q_short;

/**
 * 환자가 검색하는 용어 기반 — 시작가 위주, 알기 쉬운 명칭으로 정리.
 * (출처: 2026 진료비표 — 정확한 비용은 진단 후 견적서로 확정)
 *
 * 각 탭의 라벨·항목은 사용자 정의하기 → 비용 안내 페이지 → 치료별 비용 표 에서 편집 가능.
 */
$price_tabs = array(
	'implant' => array(
		'label' => md_content( 'price_tab_implant_label', '임플란트' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_implant_rows', "일반 임플란트 (식립 1개) | 85만 원부터 | 국산 픽스처\n프리미엄 임플란트 (식립 1개) | 95만 원부터 | 메가젠·해외 픽스처\n임플란트 보철 (지르코니아) | 55만 원부터 | 심미·강도 우수\n임플란트 보철 (금 / 골드) | 105만 원부터 | 내구성·교합 안정\n뼈이식 (간단·소량) | 30만 원부터 | 뼈가 부족할 때\n뼈이식 (상악동 거상술 등) | 50만 원부터 | 윗턱 골량 부족\n디지털 가이드 수술 | +10만 원/치아 | 정밀 수술 옵션" ) ),
	),
	'ortho' => array(
		'label' => md_content( 'price_tab_ortho_label', '교정' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_ortho_rows', "소아 교정 (1차) | 150만 원부터 | 만 7~10세 골든타임\n부분 교정 (앞니 등) | 250만 원부터 | 국소 부정만 교정\n투명 교정 (간단 케이스) | 190만 원부터 | SureSmile Lite\n투명 교정 (표준 케이스) | 320만 원부터 | SureSmile Standard\n투명 교정 (전체 교정) | 470만 원부터 | 2026 할인가\n메탈(금속) 브라켓 교정 | 420만 원 | 전통 교정\n심미(레진) 브라켓 교정 | 450만 원 | 브라켓 색 자연스럽게\n자가결찰 교정 (A-Line) | 500만 원 | 와이어 결찰 자동" ) ),
	),
	'crown' => array(
		'label' => md_content( 'price_tab_crown_label', '크라운·틀니' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_crown_rows', "도자기 + 금속 크라운 (PFM) | 45만 원부터 | 대중 보급형\n지르코니아 크라운 (어금니) | 55만 원부터 | 강도·심미 균형\n지르코니아 크라운 (앞니) | 60만 원부터 | 심미 우선\n금 (골드) 크라운 | 95만 원부터 | 교합 안정\n세라믹 인레이 (부분 수복) | 35만 원부터 | 심미적 충전\n금 (골드) 인레이 | 55만 원부터 | 내구성 우선\n틀니 (부분 / 전체) | 150만 원부터 | 진단 후 견적" ) ),
	),
	'decay' => array(
		'label' => md_content( 'price_tab_decay_label', '충치·레진' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_decay_rows', "레진 충전 (작은 충치, 1면) | 10만 원부터 | 치아 색 자연스럽게\n레진 충전 (중간 충치, 2면) | 15만 원부터 | 인접면 포함\n레진 충전 (큰 충치, 3면 이상) | 30만 원부터 | 광범위 수복\n깊은 충치 레진 (신경 근접) | 15만 원부터 | 심부 충치\n어금니 광범위 충치 (MO/DO 포함) | 30만 원부터 | 교두당 추가 5만\n어금니 광범위 충치 (MOD 포함) | 50만 원부터 | 최대 범위\n앞니 사이 틈 메우기 (디아스테마) | 20만 원부터 | 심미 보완\n치경부 마모증 (잇몸 라인 충전) | 7만 원부터 | 잇몸 경계\n레진 비니어 (앞니 심미) | 35만 원부터 | 변색 보완\n레진 코어 (크라운 토대) | 8만 원 | 보철 전 기둥\n신경치료 후 레진 충전 | 8만 원 | 신경치료 마감\n유치 레진 충전 (1면) | 8만 원 | 소아용\n유치 레진 충전 (2면 이상) | 10만 원 | 소아용" ) ),
	),
	'gum' => array(
		'label' => md_content( 'price_tab_gum_label', '잇몸·자연치아' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_gum_rows', "스케일링 (보험, 연 1회) | 25,100 원 | 만 19세 이상\n스케일링 (추가) | 6만 원 | 비급여\n잇몸치료 (간단·보험) | 21,300 원부터 | 치주염 초기\n잇몸치료 (복잡·보험) | 25,000 원부터 | 치주염 진행\n잇몸 수술 + 뼈이식 | 치아당 30만 원 | 잇몸뼈 회복\n잇몸 PDRN 주사 | 5만 원 | 염증 완화\n신경치료 (전치) | 보험 적용 | 건강보험\n신경치료 (구치) | 보험 적용 | 건강보험" ) ),
	),
	'aesthetic' => array(
		'label' => md_content( 'price_tab_aesthetic_label', '심미·미백' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_aesthetic_rows', "라미네이트 (앞니 심미) | 치아당 66만 원 | 부가세 포함\n치아 미백 (자가, 4주 키트) | 33만 원부터 | 집에서 사용\n치아 미백 (전문가, 당일 시술) | 33만 원부터 | 1-day 33~44만\n치아 미백 (전문가, 2일 시술) | 44만 원부터 | 2-day 44~55만\n잇몸 미백 | 악당 20만 원부터 | 잇몸 색 개선\n거미스마일 (잇몸 성형) | 치아당 20만 원 | 레이저 시술" ) ),
	),
	'kids' => array(
		'label' => md_content( 'price_tab_kids_label', '소아·예방' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_kids_rows', "실란트 (보험, 어금니) | 본인부담 21,700원~ | 만 18세 이하\n실란트 (비급여, 작은어금니) | 치아당 3만 원 | 예방 시술\n불소 도포 (충치 예방) | 전악 3만 원 | 치아 강화\n소아 SS 크라운 (유치) | 15~20만 원 | 유치 보존\n소아 SS 크라운 (영구치) | 20~25만 원 | 영구치 보호" ) ),
	),
	'tmj' => array(
		'label' => md_content( 'price_tab_tmj_label', '턱관절' ),
		'rows'  => md_parse_price_rows( md_content( 'price_tab_tmj_rows', "턱관절 보톡스 | 20만 원 | 이갈이·교근 통증\n턱관절 PDRN 주사 | 20만 원 | 관절 염증 완화\n턱관절 보호 장치 (하드 스플린트) | 100만 원 | 야간 착용" ) ),
	),
);
$first_tab_key = array_key_first( $price_tabs );

// 비용 확정까지 4단계 — Customizer 연동 (기본값은 Customizer 정의와 동일)
$step_defaults = array(
	1 => array( 'icon' => '💬', 'title' => '편안한 상담',  'desc' => '증상·예산·일정·우려를 충분히 듣습니다. 전화·카톡·내원 모두 가능.' ),
	2 => array( 'icon' => '🔬', 'title' => '정밀 진단',    'desc' => 'X-ray · CT · 구강 검사로 정확한 상태를 파악합니다.' ),
	3 => array( 'icon' => '📄', 'title' => '상세 견적서',   'desc' => '치료 옵션별 비용·기간·과정을 문서로 안내드립니다.' ),
	4 => array( 'icon' => '✅', 'title' => '동의 후 치료', 'desc' => '충분히 검토하시고 동의하신 항목만 진행. 추가 비용 0원.' ),
);
$steps = array();
for ( $i = 1; $i <= 4; $i++ ) {
	$d = $step_defaults[ $i ];
	$steps[] = array(
		'num'   => sprintf( '%02d', $i ),
		'title' => md_content( "price_step_{$i}_title", $d['title'] ),
		'desc'  => md_content( "price_step_{$i}_desc",  $d['desc'] ),
		'icon'  => md_content( "price_step_{$i}_icon",  $d['icon'] ),
	);
}

// 가격 정책 4 — Customizer 연동 (기본값은 Customizer 정의와 동일)
$policy_defaults = array(
	1 => array( 'title' => '환자 중심 결정',   'desc' => '비용보다 환자분의 치아 보존이 먼저입니다. 발치보다 보존, 임플란트보다 신경치료를 우선 검토합니다.' ),
	2 => array( 'title' => '사전 견적서 제공', 'desc' => '치료 시작 전에 옵션별 비용·기간을 문서로 안내드립니다. 시작 후 추가 비용이 발생하지 않습니다.' ),
	3 => array( 'title' => '난이도 단계 안내', 'desc' => '임플란트·교정 등은 케이스 난이도에 따라 가격대가 명확히 다릅니다. 어느 단계인지 사전에 설명드립니다.' ),
	4 => array( 'title' => '평생 A/S 시스템',  'desc' => '시술 후 정기 검진·문제 발생 시 대응까지 함께 봅니다. 비용은 시술 시점에만 발생하지 않습니다.' ),
);
$policies = array();
for ( $i = 1; $i <= 4; $i++ ) {
	$d = $policy_defaults[ $i ];
	$policies[] = array(
		'num'   => sprintf( '%02d', $i ),
		'title' => md_content( "price_policy_{$i}_title", $d['title'] ),
		'desc'  => md_content( "price_policy_{$i}_desc",  $d['desc'] ),
	);
}

// 건강보험 적용 / 비적용 비교 — Customizer 연동 (한 줄에 하나씩 입력)
$ins_covered   = md_parse_lines( md_content( 'price_ins_yes_items', "스케일링 (만 19세 이상, 연 1회)\n레진 충전 (만 12세 이하 영구치)\n신경치료 · 치주치료 · 발치\n사랑니 발치 (매복 포함)\nX-ray · 파노라마 · CBCT\n틀니 (만 65세 이상, 7년 1회)\n임플란트 (만 65세 이상, 평생 2개, 본인부담 30%)" ) );
$ins_uncovered = md_parse_lines( md_content( 'price_ins_no_items',  "임플란트 (만 65세 미만 전체)\n교정 (메탈·세라믹·투명·설측)\n심미 라미네이트 · 미백\n심미 보철 (지르코니아·올세라믹)\n심미 인레이 / 온레이\n치아 성형 (레진 심미)\n턱관절 보톡스 · 스플린트 일부" ) );

// 결제 안내 4 — Customizer 연동 (기본값은 Customizer 정의와 동일)
$pay_defaults = array(
	1 => array( 'icon' => '💳', 'title' => '신용·체크카드', 'desc' => '모든 카드사 결제 가능' ),
	2 => array( 'icon' => '📊', 'title' => '무이자 할부',   'desc' => '고액 진료 시 카드사 2~12개월' ),
	3 => array( 'icon' => '📱', 'title' => '간편결제',     'desc' => '삼성페이·카카오페이 등' ),
	4 => array( 'icon' => '💵', 'title' => '현금 결제',     'desc' => '현금영수증 발급' ),
);
$payment_methods = array();
for ( $i = 1; $i <= 4; $i++ ) {
	$d = $pay_defaults[ $i ];
	$payment_methods[] = array(
		'icon'  => md_content( "price_pay_{$i}_icon",  $d['icon'] ),
		'title' => md_content( "price_pay_{$i}_title", $d['title'] ),
		'desc'  => md_content( "price_pay_{$i}_desc",  $d['desc'] ),
	);
}
?>

<!-- ============ Hero (2-column: 본문 + 약속 카드) ============ -->
<section class="md-priceX-hero">
	<div class="md-container">
		<nav class="md-priceX-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>비용 안내</span>
		</nav>

		<div class="md-priceX-hero__inner">
			<div class="md-priceX-hero__text">
				<span class="md-priceX-hero__chip"><?php echo esc_html( md_content( 'price_hero_chip', 'BILLING TRANSPARENCY · 비용 안내' ) ); ?></span>
				<h1 class="md-priceX-hero__title">
					<?php echo esc_html( md_content( 'price_hero_title_a', '처음 들으신 견적,' ) ); ?><br>
					<em><?php echo esc_html( md_content( 'price_hero_title_b', '치료가 끝날 때까지' ) ); ?></em> <?php echo esc_html( md_content( 'price_hero_title_c', '그대로.' ) ); ?>
				</h1>
				<p class="md-priceX-hero__lead">
					<?php echo nl2br( esc_html( md_content( 'price_hero_lead', '문치과병원은 30여년 동안 정직한 진료비를 약속해왔습니다. 불필요한 치료를 권하지 않고, 시작 후 추가 비용이 발생하지 않습니다.' ) ) ); ?>
				</p>
				<div class="md-btn-group">
					<a class="md-btn md-btn-primary md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-priceX-hero-call">
						<?php echo esc_html( md_content( 'price_hero_btn1', '📞 무료 비용 상담' ) ); ?> <?php echo esc_html( $info['phone'] ); ?>
					</a>
					<?php if ( $naver_book ) : ?>
						<a class="md-btn md-btn-secondary md-btn--lg" href="<?php echo esc_url( $naver_book ); ?>" target="_blank" rel="noopener" data-track="cta-priceX-hero-naver">
							<?php echo esc_html( md_content( 'price_hero_btn2', '🟢 네이버 예약' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<aside class="md-priceX-promise" aria-label="문치과 비용 약속">
				<header class="md-priceX-promise__head">
					<span class="md-priceX-promise__year"><?php echo esc_html( md_content( 'price_promise_year', 'SINCE 1995' ) ); ?></span>
					<span class="md-priceX-promise__title"><?php echo esc_html( md_content( 'price_promise_title', '문치과의 3가지 약속' ) ); ?></span>
				</header>
				<?php
				$promise_defaults = array(
					1 => array( 'title' => '견적 그대로',          'desc' => '치료 시작 후 추가 비용 0원' ),
					2 => array( 'title' => '모든 비급여 사전 안내', 'desc' => '한 항목도 빠뜨리지 않고 미리' ),
					3 => array( 'title' => '치아 보존이 우선',     'desc' => '발치보다 살리기를 먼저 고민' ),
				);
				?>
				<ul class="md-priceX-promise__list">
					<?php for ( $i = 1; $i <= 3; $i++ ) :
						$pd = $promise_defaults[ $i ]; ?>
						<li>
							<span class="md-priceX-promise__no"><?php echo sprintf( '%02d', $i ); ?></span>
							<div>
								<strong><?php echo esc_html( md_content( "price_promise_{$i}_title", $pd['title'] ) ); ?></strong>
								<span><?php echo esc_html( md_content( "price_promise_{$i}_desc",  $pd['desc']  ) ); ?></span>
							</div>
						</li>
					<?php endfor; ?>
				</ul>
			</aside>
		</div>
	</div>
</section>

<!-- ============ 비용 확정 4단계 ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'price_steps_eyebrow', 'Process' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'price_steps_title', '비용이 확정되는 4단계' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'price_steps_lead', '상담 → 진단 → 견적 → 동의 후 치료. 각 단계마다 환자분이 충분히 검토하실 수 있습니다.' ) ) ); ?>
			</p>
		</header>

		<ol class="md-priceX-steps">
			<?php foreach ( $steps as $step ) : ?>
				<li class="md-priceX-step">
					<div class="md-priceX-step__num">
						<span class="md-priceX-step__icon" aria-hidden="true"><?php echo $step['icon']; ?></span>
						<span class="md-priceX-step__no"><?php echo esc_html( $step['num'] ); ?></span>
					</div>
					<h3 class="md-priceX-step__title"><?php echo esc_html( $step['title'] ); ?></h3>
					<p class="md-priceX-step__desc"><?php echo esc_html( $step['desc'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<!-- ============ 가격 정책 4 (numbered, left-aligned 2x2) ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'price_policy_eyebrow', 'Our Policy' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'price_policy_title', '비용 결정의 4가지 원칙' ) ); ?></h2>
		</header>

		<div class="md-priceX-policy">
			<?php foreach ( $policies as $p ) : ?>
				<article class="md-priceX-policy__item">
					<span class="md-priceX-policy__num" aria-hidden="true"><?php echo esc_html( $p['num'] ); ?></span>
					<div class="md-priceX-policy__body">
						<h3 class="md-priceX-policy__title"><?php echo esc_html( $p['title'] ); ?></h3>
						<p class="md-priceX-policy__desc"><?php echo esc_html( $p['desc'] ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ============ 치료별 예상 비용 (underline tabs) ============ -->
<section class="md-section" id="pricing-tabs">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'price_tables_eyebrow', 'Estimated Cost' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'price_tables_title', '치료별 예상 비용' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'price_tables_lead', '아래 표는 표준 기준입니다. 정확한 비용은 정밀 진단 후 견적서로 안내드립니다.' ) ) ); ?>
			</p>
		</header>

		<div class="md-priceX-tab" data-pricetab>
			<div class="md-priceX-tab__nav" role="tablist" aria-label="치료별 비용 탭">
				<?php foreach ( $price_tabs as $key => $tab ) : ?>
					<button class="md-priceX-tab__btn<?php echo $key === $first_tab_key ? ' is-active' : ''; ?>"
						type="button"
						role="tab"
						id="priceX-tab-<?php echo esc_attr( $key ); ?>"
						aria-controls="priceX-panel-<?php echo esc_attr( $key ); ?>"
						aria-selected="<?php echo $key === $first_tab_key ? 'true' : 'false'; ?>"
						data-pricetab-target="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $price_tabs as $key => $tab ) : ?>
				<div class="md-priceX-tab__panel<?php echo $key === $first_tab_key ? ' is-active' : ''; ?>"
					id="priceX-panel-<?php echo esc_attr( $key ); ?>"
					role="tabpanel"
					aria-labelledby="priceX-tab-<?php echo esc_attr( $key ); ?>"
					data-pricetab-panel="<?php echo esc_attr( $key ); ?>">
					<dl class="md-priceX-list">
						<?php foreach ( $tab['rows'] as $row ) : ?>
							<div class="md-priceX-row">
								<dt class="md-priceX-row__name">
									<?php echo esc_html( $row[0] ); ?>
									<?php if ( ! empty( $row[2] ) ) : ?>
										<span class="md-priceX-row__tag"><?php echo esc_html( $row[2] ); ?></span>
									<?php endif; ?>
								</dt>
								<dd class="md-priceX-row__price"><?php echo esc_html( $row[1] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="md-priceX-tab__hint">
			ⓘ <?php echo nl2br( esc_html( md_content( 'price_tables_hint', '환자분의 구강 상태·재료 선택·치료 난이도에 따라 조정될 수 있습니다. 최종 비용은 정밀 진단 후 견적서로 확정해드립니다.' ) ) ); ?>
		</p>
	</div>
</section>

<!-- ============ 건강보험 적용 / 비적용 2-column ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'price_ins_eyebrow', 'Insurance' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'price_ins_title', '건강보험 적용 비교' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'price_ins_lead', '무엇이 적용되고 무엇이 그렇지 않은지 — 미리 확인하세요.' ) ) ); ?>
			</p>
		</header>

		<div class="md-priceX-ins">
			<div class="md-priceX-ins__col md-priceX-ins__col--yes">
				<header>
					<span class="md-priceX-ins__badge">✓ 건강보험 적용</span>
					<h3><?php echo esc_html( md_content( 'price_ins_yes_title', '본인부담 일부 / 급여 진료' ) ); ?></h3>
				</header>
				<ul>
					<?php foreach ( $ins_covered as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="md-priceX-ins__col md-priceX-ins__col--no">
				<header>
					<span class="md-priceX-ins__badge md-priceX-ins__badge--alt">✗ 비급여</span>
					<h3><?php echo esc_html( md_content( 'price_ins_no_title', '본인부담 100% / 사전 견적' ) ); ?></h3>
				</header>
				<ul>
					<?php foreach ( $ins_uncovered as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>

<!-- ============ 결제 안내 (2-col tile with descriptions) ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'price_pay_eyebrow', 'Payment' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'price_pay_title', '결제 안내' ) ); ?></h2>
		</header>

		<div class="md-priceX-pay">
			<?php foreach ( $payment_methods as $pm ) : ?>
				<div class="md-priceX-pay__item">
					<span class="md-priceX-pay__icon" aria-hidden="true"><?php echo $pm['icon']; ?></span>
					<div>
						<strong><?php echo esc_html( $pm['title'] ); ?></strong>
						<span><?php echo esc_html( $pm['desc'] ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<aside class="md-priceX-pay__notice">
			<span class="md-priceX-pay__notice-tag"><?php echo esc_html( md_content( 'price_pay_notice_tag', '실손보험 안내' ) ); ?></span>
			<p><?php echo wp_kses_post( wpautop( md_content( 'price_pay_notice_text', '치과 진료는 대부분 실손보험 대상이 아닙니다. 사고로 인한 외상 치료·턱관절 일부는 보장 가능할 수 있으니 가입 보험사에 사전 확인해주세요. 문치과병원은 진단서·소견서 발급으로 보험 청구를 도와드립니다.' ) ) ); ?></p>
		</aside>
	</div>
</section>

<!-- ============ CTA (코럴 — 스크린샷의 다크브라운과 차별) ============ -->
<section class="md-section md-section--sm">
	<div class="md-container">
		<div class="md-priceX-cta">
			<div class="md-priceX-cta__text">
				<span class="md-priceX-cta__chip"><?php echo esc_html( md_content( 'price_cta_chip', '무료 비용 상담' ) ); ?></span>
				<h2 class="md-priceX-cta__title"><?php echo esc_html( md_content( 'price_cta_title', '내 진료 비용이 궁금하신가요?' ) ); ?></h2>
				<p class="md-priceX-cta__lead">
					<?php echo nl2br( esc_html( md_content( 'price_cta_lead', '정확한 진단 후 맞춤 견적서를 안내드립니다. 부담 없이 먼저 들어보세요.' ) ) ); ?>
				</p>
				<div class="md-btn-group">
					<a class="md-btn md-btn-primary md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-priceX-banner-call">
						📞 <?php echo esc_html( $info['phone'] ); ?>
					</a>
					<?php if ( $kakao_url ) : ?>
						<a class="md-btn md-btn-ghost md-btn--lg" href="<?php echo esc_url( $kakao_url ); ?>" target="_blank" rel="noopener" data-track="cta-priceX-banner-kakao">
							💬 카카오톡 상담
						</a>
					<?php endif; ?>
				</div>
			</div>
			<div class="md-priceX-cta__meta">
				<dl>
					<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
						<div>
							<dt><?php echo esc_html( md_content( "price_cta_meta_{$i}_label", '' ) ); ?></dt>
							<dd><?php echo esc_html( md_content( "price_cta_meta_{$i}_value", '' ) ); ?></dd>
						</div>
					<?php endfor; ?>
				</dl>
			</div>
		</div>
	</div>
</section>

<?php /* 위치 섹션은 푸터 위 section-location과 중복되어 제거됨 — v3.12.3 */ ?>

<?php
get_footer();
