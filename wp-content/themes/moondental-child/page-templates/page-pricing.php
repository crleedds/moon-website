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
$map_google = 'https://www.google.com/maps/search/?api=1&query=' . $q_full;
$map_naver  = $naver_map ?: 'https://map.naver.com/p/search/' . $q_short;
$map_kakao  = 'https://map.kakao.com/?q=' . $q_short;

/**
 * 치료별 예상 비용. price_min/max를 추가해서 시각화 바 그릴 수 있게.
 * 표시 텍스트는 별도(text) — "건강보험 적용" 같은 비숫자 케이스 대응.
 */
$price_tabs = array(
	'implant' => array(
		'label' => '임플란트',
		'rows'  => array(
			array( '스트라우만 BLX',                  '180~230만 원',  '프리미엄' ),
			array( '오스템 (Osstem)',                 '120~150만 원',  '대중 보급' ),
			array( '디오 (DIO)',                       '110~140만 원',  '국산' ),
			array( '뼈이식 / GBR 동시 수술',           '20~50만 원',    '추가 비용' ),
			array( 'M-GBR · Moon Magic 당일 임플란트', '+30~80만 원',   '난이도 추가' ),
		),
	),
	'crown' => array(
		'label' => '보철',
		'rows'  => array(
			array( 'PFM (금속 도재관)',         '35~45만 원',         '' ),
			array( '지르코니아 크라운',         '50~70만 원',         '심미·강도' ),
			array( '올세라믹 크라운',           '60~80만 원',         '전치부' ),
			array( '인레이 / 온레이 (세라믹)',  '35~55만 원',         '부분 수복' ),
			array( '브릿지',                     '단위당 35~70만 원', '치아 개수×단위' ),
		),
	),
	'ortho' => array(
		'label' => '교정',
		'rows'  => array(
			array( '메탈 브라켓',          '400~600만 원',     '전통 교정' ),
			array( '세라믹 브라켓',        '500~700만 원',     '심미' ),
			array( '인비절라인 투명교정',  '600~1,000만 원',   '난이도별 차등' ),
			array( '설측(혀쪽) 교정',      '1,000~1,500만 원', '고난이도' ),
			array( '부분교정 / MTA',       '150~400만 원',     '국소 부정' ),
		),
	),
	'endo' => array(
		'label' => '자연치아',
		'rows'  => array(
			array( '신경치료 (전치)',  '15~25만 원',  '건강보험' ),
			array( '신경치료 (구치)',  '20~35만 원',  '건강보험' ),
			array( '재근관 치료',      '30~50만 원',  '난이도 추가' ),
			array( '치근단 수술',      '40~70만 원',  '외과적' ),
		),
	),
	'aesthetic' => array(
		'label' => '심미',
		'rows'  => array(
			array( '치아 미백 (전문가)',      '30~50만 원',     '1회' ),
			array( '라미네이트',              '70~100만 원/개', '심미 보철' ),
			array( '워킹 블리치 (내부 미백)', '15~25만 원/개',  '신경치료 치아' ),
			array( '치아 성형 (레진)',        '5~15만 원/개',   '간단 보정' ),
		),
	),
	'kids' => array(
		'label' => '소아·예방',
		'rows'  => array(
			array( '실란트 (치아 홈 메우기)', '건강보험',     '만 18세 이하' ),
			array( '불소 도포',               '2~5만 원',     '1회' ),
			array( '소아 충치 치료 (레진)',   '건강보험',     '만 12세 이하' ),
			array( '소아 부분교정 (MTA)',     '100~300만 원', '골든타임' ),
		),
	),
);
$first_tab_key = array_key_first( $price_tabs );

// 비용 확정까지 4단계
$steps = array(
	array( 'num' => '01', 'title' => '편안한 상담',  'desc' => '증상·예산·일정·우려를 충분히 듣습니다. 전화·카톡·내원 모두 가능.', 'icon' => '💬' ),
	array( 'num' => '02', 'title' => '정밀 진단',    'desc' => 'X-ray · CT · 구강 검사로 정확한 상태를 파악합니다.',           'icon' => '🔬' ),
	array( 'num' => '03', 'title' => '상세 견적서',  'desc' => '치료 옵션별 비용·기간·과정을 문서로 안내드립니다.',          'icon' => '📄' ),
	array( 'num' => '04', 'title' => '동의 후 치료', 'desc' => '충분히 검토하시고 동의하신 항목만 진행. 추가 비용 0원.',     'icon' => '✅' ),
);

// 가격 정책 4
$policies = array(
	array( 'num' => '01', 'title' => '환자 중심 결정',     'desc' => '비용보다 환자분의 치아 보존이 먼저입니다. 발치보다 보존, 임플란트보다 신경치료를 우선 검토합니다.' ),
	array( 'num' => '02', 'title' => '사전 견적서 제공',   'desc' => '치료 시작 전에 옵션별 비용·기간을 문서로 안내드립니다. 시작 후 추가 비용이 발생하지 않습니다.' ),
	array( 'num' => '03', 'title' => '난이도 단계 안내',   'desc' => '임플란트·교정 등은 케이스 난이도에 따라 가격대가 명확히 다릅니다. 어느 단계인지 사전에 설명드립니다.' ),
	array( 'num' => '04', 'title' => '평생 A/S 시스템',    'desc' => '시술 후 정기 검진·문제 발생 시 대응까지 함께 봅니다. 비용은 시술 시점에만 발생하지 않습니다.' ),
);

// 건강보험 적용 / 비적용 비교
$ins_covered = array(
	'스케일링 (만 19세 이상, 연 1회)',
	'레진 충전 (만 12세 이하 영구치)',
	'신경치료 · 치주치료 · 발치',
	'사랑니 발치 (매복 포함)',
	'X-ray · 파노라마 · CBCT',
	'틀니 (만 65세 이상, 7년 1회)',
	'임플란트 (만 65세 이상, 평생 2개, 본인부담 30%)',
);
$ins_uncovered = array(
	'임플란트 (만 65세 미만 전체)',
	'교정 (메탈·세라믹·투명·설측)',
	'심미 라미네이트 · 미백',
	'심미 보철 (지르코니아·올세라믹)',
	'심미 인레이 / 온레이',
	'치아 성형 (레진 심미)',
	'턱관절 보톡스 · 스플린트 일부',
);

$payment_methods = array(
	array( 'icon' => '💳', 'title' => '신용·체크카드',  'desc' => '모든 카드사 결제 가능' ),
	array( 'icon' => '📊', 'title' => '무이자 할부',    'desc' => '고액 진료 시 카드사 2~12개월' ),
	array( 'icon' => '📱', 'title' => '간편결제',       'desc' => '삼성페이·카카오페이 등' ),
	array( 'icon' => '💵', 'title' => '현금 결제',      'desc' => '현금영수증 발급' ),
);
?>

<!-- ============ Hero (2-column: 본문 + 약속 카드) ============ -->
<section class="md-priceX-hero">
	<div class="md-container">
		<nav class="md-priceX-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>비용 안내</span>
		</nav>

		<div class="md-priceX-hero__inner">
			<div class="md-priceX-hero__text">
				<span class="md-priceX-hero__chip">BILLING TRANSPARENCY · 비용 안내</span>
				<h1 class="md-priceX-hero__title">
					처음 들으신 견적,<br>
					<em>치료가 끝날 때까지</em> 그대로.
				</h1>
				<p class="md-priceX-hero__lead">
					문치과병원은 30년 동안 정직한 진료비를 약속해왔습니다.
					불필요한 치료를 권하지 않고, 시작 후 추가 비용이 발생하지 않습니다.
				</p>
				<div class="md-btn-group">
					<a class="md-btn md-btn-primary md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-priceX-hero-call">
						📞 무료 비용 상담 <?php echo esc_html( $info['phone'] ); ?>
					</a>
					<?php if ( $naver_book ) : ?>
						<a class="md-btn md-btn-secondary md-btn--lg" href="<?php echo esc_url( $naver_book ); ?>" target="_blank" rel="noopener" data-track="cta-priceX-hero-naver">
							🟢 네이버 예약
						</a>
					<?php endif; ?>
				</div>
			</div>

			<aside class="md-priceX-promise" aria-label="문치과 비용 약속">
				<header class="md-priceX-promise__head">
					<span class="md-priceX-promise__year">SINCE 1995</span>
					<span class="md-priceX-promise__title">문치과의 3가지 약속</span>
				</header>
				<ul class="md-priceX-promise__list">
					<li>
						<span class="md-priceX-promise__no">01</span>
						<div>
							<strong>견적 그대로</strong>
							<span>치료 시작 후 추가 비용 0원</span>
						</div>
					</li>
					<li>
						<span class="md-priceX-promise__no">02</span>
						<div>
							<strong>모든 비급여 사전 안내</strong>
							<span>한 항목도 빠뜨리지 않고 미리</span>
						</div>
					</li>
					<li>
						<span class="md-priceX-promise__no">03</span>
						<div>
							<strong>치아 보존이 우선</strong>
							<span>발치보다 살리기를 먼저 고민</span>
						</div>
					</li>
				</ul>
			</aside>
		</div>
	</div>
</section>

<!-- ============ 비용 확정 4단계 ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Process</span>
			<h2 class="md-section-head__title">비용이 확정되는 4단계</h2>
			<p class="md-section-head__lead">
				상담 → 진단 → 견적 → 동의 후 치료. 각 단계마다 환자분이 충분히 검토하실 수 있습니다.
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
			<span class="md-section-head__eyebrow">Our Policy</span>
			<h2 class="md-section-head__title">비용 결정의 4가지 원칙</h2>
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
			<span class="md-section-head__eyebrow">Estimated Cost</span>
			<h2 class="md-section-head__title">치료별 예상 비용</h2>
			<p class="md-section-head__lead">
				아래 표는 표준 기준입니다. 정확한 비용은 정밀 진단 후 견적서로 안내드립니다.
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
			ⓘ 환자분의 구강 상태·재료 선택·치료 난이도에 따라 조정될 수 있습니다.
			<strong>최종 비용은 정밀 진단 후 견적서로 확정해드립니다.</strong>
		</p>
	</div>
</section>

<!-- ============ 건강보험 적용 / 비적용 2-column ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Insurance</span>
			<h2 class="md-section-head__title">건강보험 적용 비교</h2>
			<p class="md-section-head__lead">
				무엇이 적용되고 무엇이 그렇지 않은지 — 미리 확인하세요.
			</p>
		</header>

		<div class="md-priceX-ins">
			<div class="md-priceX-ins__col md-priceX-ins__col--yes">
				<header>
					<span class="md-priceX-ins__badge">✓ 건강보험 적용</span>
					<h3>본인부담 일부 / 급여 진료</h3>
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
					<h3>본인부담 100% / 사전 견적</h3>
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
			<span class="md-section-head__eyebrow">Payment</span>
			<h2 class="md-section-head__title">결제 안내</h2>
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
			<span class="md-priceX-pay__notice-tag">실손보험 안내</span>
			<p>
				치과 진료는 <strong>대부분 실손보험 대상이 아닙니다</strong>.
				사고로 인한 외상 치료·턱관절 일부는 보장 가능할 수 있으니 가입 보험사에 사전 확인해주세요.
				문치과병원은 진단서·소견서 발급으로 보험 청구를 도와드립니다.
			</p>
		</aside>
	</div>
</section>

<!-- ============ CTA (코럴 — 스크린샷의 다크브라운과 차별) ============ -->
<section class="md-section md-section--sm">
	<div class="md-container">
		<div class="md-priceX-cta">
			<div class="md-priceX-cta__text">
				<span class="md-priceX-cta__chip">무료 비용 상담</span>
				<h2 class="md-priceX-cta__title">내 진료 비용이 궁금하신가요?</h2>
				<p class="md-priceX-cta__lead">
					정확한 진단 후 맞춤 견적서를 안내드립니다. 부담 없이 먼저 들어보세요.
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
					<div>
						<dt>진료시간</dt>
						<dd>평일 09:00–20:30 · 목 ~18:00 · 토 ~14:00</dd>
					</div>
					<div>
						<dt>예약 채널</dt>
						<dd>전화 · 카카오톡 · 네이버 예약 (24시간)</dd>
					</div>
					<div>
						<dt>위치</dt>
						<dd>충남 천안시 동남구 만남로 52, 문타워 9~13층</dd>
					</div>
				</dl>
			</div>
		</div>
	</div>
</section>

<!-- ============ 위치 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Location</span>
			<h2 class="md-section-head__title">문치과병원 위치</h2>
			<p class="md-section-head__lead">
				<a href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" style="color:inherit; border-bottom:1px dashed var(--color-border);">
					<?php echo esc_html( $info['address'] ); ?>
				</a>
			</p>
		</header>

		<div class="md-mapbtn-grid">
			<a class="md-mapbtn md-mapbtn--naver" href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" data-track="cta-priceX-map-naver">
				<span class="md-mapbtn__logo" aria-hidden="true">N</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">네이버 지도</span>
					<span class="md-mapbtn__sub">길찾기 · 대중교통</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--kakao" href="<?php echo esc_url( $map_kakao ); ?>" target="_blank" rel="noopener" data-track="cta-priceX-map-kakao">
				<span class="md-mapbtn__logo" aria-hidden="true">k</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">카카오맵</span>
					<span class="md-mapbtn__sub">길찾기 · 로드뷰</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--google" href="<?php echo esc_url( $map_google ); ?>" target="_blank" rel="noopener" data-track="cta-priceX-map-google">
				<span class="md-mapbtn__logo" aria-hidden="true">G</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">Google Maps</span>
					<span class="md-mapbtn__sub">Directions · Street View</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
		</div>
	</div>
</section>

<?php
get_footer();
