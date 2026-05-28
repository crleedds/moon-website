<?php
/**
 * Template Name: 비용 안내
 * Template Post Type: page
 *
 * 투명한 비용 안내 페이지 — 가격 정책 / 치료별 예상비용 탭 / 건강보험 안내 /
 * 결제 안내 / 무료상담 배너 / 위치 안내.
 *
 * 슬러그: /비용-안내/ /비급여-진료비/ /진료비안내/ /pricing/
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

// 치료별 예상 비용 — 탭별 표 데이터
$price_tabs = array(
	'implant' => array(
		'label' => '🦷 임플란트',
		'rows'  => array(
			array( '스트라우만 BLX',        '180~230만 원', '프리미엄' ),
			array( '오스템 (Osstem)',       '120~150만 원', '대중 보급' ),
			array( '디오 (DIO)',            '110~140만 원', '국산' ),
			array( '뼈이식 / GBR 동시 수술', '20~50만 원',  '추가 비용' ),
			array( 'M-GBR · Moon Magic 당일 임플란트', '+30~80만 원', '난이도 추가' ),
		),
	),
	'crown' => array(
		'label' => '👑 보철 (크라운/브릿지)',
		'rows'  => array(
			array( 'PFM (금속 도재관)',     '35~45만 원',  '' ),
			array( '지르코니아 크라운',     '50~70만 원',  '심미·강도' ),
			array( '올세라믹 크라운',       '60~80만 원',  '전치부 권장' ),
			array( '인레이/온레이 (세라믹)', '35~55만 원',  '부분 수복' ),
			array( '브릿지',                 '단위당 35~70만 원', '치아 개수×단위' ),
		),
	),
	'ortho' => array(
		'label' => '✨ 교정',
		'rows'  => array(
			array( '메탈 브라켓',           '400~600만 원', '전통 교정' ),
			array( '세라믹 브라켓',         '500~700만 원', '심미' ),
			array( '인비절라인 투명교정',   '600~1,000만 원', '난이도별 차등' ),
			array( '설측(혀쪽) 교정',       '1,000~1,500만 원', '고난이도' ),
			array( '부분교정 / MTA',        '150~400만 원', '국소 부정' ),
		),
	),
	'endo' => array(
		'label' => '🌿 자연치아 살리기',
		'rows'  => array(
			array( '신경치료 (전치)',       '15~25만 원',  '건강보험 적용' ),
			array( '신경치료 (구치)',       '20~35만 원',  '건강보험 적용' ),
			array( '재근관 치료',           '30~50만 원',  '난이도 추가' ),
			array( '치근단 수술',           '40~70만 원',  '외과적 치료' ),
		),
	),
	'aesthetic' => array(
		'label' => '💎 심미치료',
		'rows'  => array(
			array( '치아 미백 (전문가)',    '30~50만 원',  '1회 시술' ),
			array( '라미네이트',            '70~100만 원/개', '심미 보철' ),
			array( '워킹 블리치 (내부 미백)', '15~25만 원/개', '신경치료 치아' ),
			array( '치아 성형 (레진)',      '5~15만 원/개', '간단 형태 보정' ),
		),
	),
	'kids' => array(
		'label' => '🧒 소아·예방',
		'rows'  => array(
			array( '실란트 (치아 홈 메우기)', '건강보험 적용', '만 18세 이하' ),
			array( '불소 도포',              '2~5만 원',     '1회' ),
			array( '소아 충치 치료 (레진)',   '건강보험 적용', '만 12세 이하 영구치' ),
			array( '소아 부분교정 (MTA)',    '100~300만 원', '골든타임 진료' ),
		),
	),
);
$first_tab_key = array_key_first( $price_tabs );

// 가격 정책 4가지
$policies = array(
	array( 'icon' => '🔍', 'title' => '정확한 진단 후 견적', 'desc' => '환자분의 상태를 정밀하게 진단한 후 모든 치료 계획과 견적을 안내드립니다.' ),
	array( 'icon' => '📄', 'title' => '사전 견적서 제공',     'desc' => '치료 시작 전 비용·기간을 명시한 견적서를 제공드리며, 시작 후 추가 비용은 없습니다.' ),
	array( 'icon' => '🤝', 'title' => '불필요한 치료 지양',   'desc' => '필요하지 않은 치료를 권하지 않습니다. 환자 중심의 정직한 진료가 원칙입니다.' ),
	array( 'icon' => '💬', 'title' => '충분한 상담',          'desc' => '비용에 대한 모든 궁금증을 충분히 들어드립니다. 부담 없이 질문해주세요.' ),
);

// 건강보험 적용 항목
$insurance_items = array(
	'스케일링 (만 19세 이상, 연 1회)',
	'레진 충전 (만 12세 이하 영구치)',
	'신경치료 / 치주치료 / 발치',
	'치과 X-ray · 파노라마 · CBCT',
	'사랑니 발치 (매복 포함)',
	'틀니 (만 65세 이상, 7년 1회)',
	'임플란트 (만 65세 이상, 평생 2개까지 본인부담 30%)',
);

$payment_methods = array(
	array( 'icon' => '💳', 'label' => '신용카드' ),
	array( 'icon' => '🏦', 'label' => '체크카드' ),
	array( 'icon' => '📱', 'label' => '간편결제' ),
	array( 'icon' => '📊', 'label' => '무이자 할부' ),
	array( 'icon' => '💵', 'label' => '현금' ),
);
?>

<!-- ============ Hero ============ -->
<section class="md-pricing-hero">
	<div class="md-container">
		<nav class="md-pricing-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>비용 안내</span>
		</nav>
		<span class="md-pricing-hero__eyebrow">MOON DENTAL HOSPITAL · 비용 안내</span>
		<h1 class="md-pricing-hero__title">
			투명한 <em>비용 안내</em>
		</h1>
		<p class="md-pricing-hero__lead">
			추가 비용 없는 정직한 견적서 제공.<br>
			문치과병원은 불필요한 치료를 권하지 않습니다.
		</p>
		<ul class="md-pricing-hero__stats" aria-label="문치과병원 신뢰 지표">
			<li><span aria-hidden="true">🏥</span> 30년 임상 경험</li>
			<li><span aria-hidden="true">👥</span> 의료진 10명</li>
			<li><span aria-hidden="true">🌙</span> 평일 야간 ~20:30</li>
			<li><span aria-hidden="true">📞</span> <?php echo esc_html( $info['phone'] ); ?></li>
		</ul>
		<div class="md-btn-group">
			<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>" data-track="cta-pricing-hero-reservation">
				📅 상담 예약하기
			</a>
			<a class="md-btn md-btn-secondary md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-pricing-hero-call">
				📞 <?php echo esc_html( $info['phone'] ); ?>
			</a>
		</div>
	</div>
</section>

<!-- ============ 가격 정책 4 ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">가격 정책</span>
			<h2 class="md-section-head__title">문치과병원의 비용 원칙</h2>
			<p class="md-section-head__lead">
				환자분께 투명하고 정직한 비용 정보를 제공해 드립니다.
			</p>
		</header>

		<div class="md-policy-grid">
			<?php foreach ( $policies as $p ) : ?>
				<article class="md-policy">
					<div class="md-policy__icon" aria-hidden="true"><?php echo $p['icon']; ?></div>
					<h3 class="md-policy__title"><?php echo esc_html( $p['title'] ); ?></h3>
					<p class="md-policy__desc"><?php echo esc_html( $p['desc'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ============ 치료별 예상 비용 (탭) ============ -->
<section class="md-section md-section--surface" id="pricing-tabs">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">예상 비용</span>
			<h2 class="md-section-head__title">치료별 예상 비용</h2>
			<p class="md-section-head__lead">
				아래 비용은 표준 기준이며, 정확한 비용은 진단 후 안내드립니다.
			</p>
		</header>

		<div class="md-pricetab" data-pricetab>
			<div class="md-pricetab__nav" role="tablist" aria-label="치료별 비용 탭">
				<?php foreach ( $price_tabs as $key => $tab ) : ?>
					<button class="md-pricetab__btn<?php echo $key === $first_tab_key ? ' is-active' : ''; ?>"
						type="button"
						role="tab"
						id="pricetab-tab-<?php echo esc_attr( $key ); ?>"
						aria-controls="pricetab-panel-<?php echo esc_attr( $key ); ?>"
						aria-selected="<?php echo $key === $first_tab_key ? 'true' : 'false'; ?>"
						data-pricetab-target="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $price_tabs as $key => $tab ) : ?>
				<div class="md-pricetab__panel<?php echo $key === $first_tab_key ? ' is-active' : ''; ?>"
					id="pricetab-panel-<?php echo esc_attr( $key ); ?>"
					role="tabpanel"
					aria-labelledby="pricetab-tab-<?php echo esc_attr( $key ); ?>"
					data-pricetab-panel="<?php echo esc_attr( $key ); ?>">
					<table class="md-pricetable">
						<thead>
							<tr>
								<th scope="col">치료 항목</th>
								<th scope="col" class="md-pricetable__price">예상 비용</th>
								<th scope="col" class="md-pricetable__note">비고</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $tab['rows'] as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row[0] ); ?></td>
									<td class="md-pricetable__price"><strong><?php echo esc_html( $row[1] ); ?></strong></td>
									<td class="md-pricetable__note"><?php echo esc_html( $row[2] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="md-pricetab__hint">
			ⓘ 위 비용은 환자분의 구강 상태·재료 선택·치료 난이도에 따라 조정될 수 있습니다.
			<strong>최종 비용은 정밀 진단 후 상담 시 확정해드립니다.</strong>
		</p>
	</div>
</section>

<!-- ============ 건강보험 적용 안내 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">건강보험</span>
			<h2 class="md-section-head__title">건강보험 적용 안내</h2>
			<p class="md-section-head__lead">
				국민건강보험이 적용되는 진료를 안내해 드립니다.
			</p>
		</header>

		<div class="md-insurance">
			<h3 class="md-insurance__title">✅ 건강보험 적용 항목</h3>
			<ul class="md-insurance__list">
				<?php foreach ( $insurance_items as $item ) : ?>
					<li><?php echo wp_kses_post( $item ); ?></li>
				<?php endforeach; ?>
			</ul>

			<div class="md-insurance__notice">
				<span class="md-insurance__notice-tag">비급여 항목 안내</span>
				<p>
					임플란트(만 65세 미만)·교정·심미치료·라미네이트·심미보철 등은 비급여로,
					건강보험이 적용되지 않습니다. 위 표의 비용은 비급여 기준입니다.
				</p>
			</div>
		</div>
	</div>
</section>

<!-- ============ 결제 안내 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">결제 방법</span>
			<h2 class="md-section-head__title">편리한 결제 안내</h2>
			<p class="md-section-head__lead">
				다양한 결제 방법으로 편리하게 이용하실 수 있습니다.
			</p>
		</header>

		<div class="md-payment-grid">
			<?php foreach ( $payment_methods as $pm ) : ?>
				<div class="md-payment">
					<span class="md-payment__icon" aria-hidden="true"><?php echo $pm['icon']; ?></span>
					<span class="md-payment__label"><?php echo esc_html( $pm['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="md-payment-notice">
			<span class="md-payment-notice__tag">⚠️ 결제 관련 주의사항</span>
			<p>
				고액 진료(임플란트·교정·심미)는 카드사 <strong>무이자 할부 2~12개월</strong> 가능합니다.
				실손보험은 치과 진료 대부분 보장 대상이 아닙니다 — 사고로 인한 외상·턱관절 일부만 적용될 수 있으니 가입 보험사에 사전 확인해주세요.
				치료 시작 전 견적서·동의서를 받으시면 추가 비용은 발생하지 않습니다.
			</p>
		</div>
	</div>
</section>

<!-- ============ 무료 상담 배너 ============ -->
<section class="md-section md-section--sm">
	<div class="md-container">
		<div class="md-pricing-banner">
			<span class="md-pricing-banner__tag">💬 무료 상담</span>
			<h2 class="md-pricing-banner__title">비용이 걱정되시나요?</h2>
			<p class="md-pricing-banner__lead">
				부담 없이 상담받으세요. 정확한 진단 후 맞춤 견적과 치료 계획을 안내드립니다.
			</p>
			<div class="md-btn-group" style="justify-content:center; display:flex;">
				<a class="md-btn md-btn-primary md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-pricing-banner-call">
					📞 무료 상담 <?php echo esc_html( $info['phone'] ); ?>
				</a>
				<?php if ( $naver_book ) : ?>
					<a class="md-btn md-btn-ghost md-btn--lg" href="<?php echo esc_url( $naver_book ); ?>" target="_blank" rel="noopener" data-track="cta-pricing-banner-naver">
						🟢 네이버 예약
					</a>
				<?php endif; ?>
			</div>
			<p class="md-pricing-banner__hours">
				🕐 평일 09:00–20:30 · 목 09:00–18:00 · 토 09:00–14:00 · 일/공휴일 휴진
			</p>
		</div>
	</div>
</section>

<!-- ============ 위치 안내 ============ -->
<section class="md-section">
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
			<a class="md-mapbtn md-mapbtn--naver" href="<?php echo esc_url( $map_naver ); ?>" target="_blank" rel="noopener" data-track="cta-pricing-map-naver">
				<span class="md-mapbtn__logo" aria-hidden="true">N</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">네이버 지도</span>
					<span class="md-mapbtn__sub">길찾기 · 대중교통</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--kakao" href="<?php echo esc_url( $map_kakao ); ?>" target="_blank" rel="noopener" data-track="cta-pricing-map-kakao">
				<span class="md-mapbtn__logo" aria-hidden="true">k</span>
				<span class="md-mapbtn__body">
					<span class="md-mapbtn__name">카카오맵</span>
					<span class="md-mapbtn__sub">길찾기 · 로드뷰</span>
				</span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--google" href="<?php echo esc_url( $map_google ); ?>" target="_blank" rel="noopener" data-track="cta-pricing-map-google">
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
