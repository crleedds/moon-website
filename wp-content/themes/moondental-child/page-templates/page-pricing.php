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
 * 탭별 가격표 — v3.27.0 자유 편집 방식.
 *  Customizer > 비용 안내 페이지 > 전체 가격표 (탭별) 텍스트영역에서
 *  '== 탭 이름 ==' + '이름 | 가격 | 비고' 형식으로 자유롭게 편집.
 *  탭 추가·삭제·순서 변경 모두 텍스트에서 직접 가능.
 */
$default_all_tabs = "== 임플란트 ==\n" .
	"스테리오스 임플란트 | 85만~95만 원 | 국산 · 즉시 하중\n" .
	"오스템 임플란트 | 90만~100만 원 | 국산 · 대중 보급\n" .
	"포인트 임플란트 (UV 활성) | 95만~105만 원 | 국산 · UV 활성 표면\n" .
	"메가젠 임플란트 (프리미엄) | 100만~110만 원 | 국산 · 프리미엄\n" .
	"임플란트 크라운 (PFM) | 치아당 45만 원 | 대중 보급형\n" .
	"임플란트 크라운 (지르코니아·구치) | 치아당 55만 원 | 심미·강도 균형\n" .
	"임플란트 크라운 (지르코니아·전치) | 치아당 60만 원 | 심미 우선\n" .
	"임플란트 크라운 (골드) | 치아당 105만 원 | 교합 안정\n" .
	"임플란트 임시치아 (고정형) | 치아당 15만 원 | 골 유합 대기 중\n" .
	"뼈이식 (간단·PRP·PRF 포함) | 30만 원 | 뼈가 부족할 때\n" .
	"뼈이식 (상악동 거상술 등) | 50만 원 | 윗턱 골량 부족\n" .
	"네비게이션(가이드) 수술 | 사분면당 10만 원 | 정밀 수술 옵션\n" .
	"임플란트 PDRN 주사 | 부위당 5만 원 | 골 재생 촉진\n\n" .

	"== 교정 ==\n" .
	"교정 진단비 (브라켓·투명) | 회당 20만 원 | 정밀 진단·시뮬레이션\n" .
	"소아 교정 (브라켓, 1차) | 150만~200만 원 | 만 7~10세 골든타임\n" .
	"부분 교정 (앞니 등) | 250만~300만 원 | 국소 부정만\n" .
	"투명 교정 · SureSmile 부분 | 190만 원 | 양악 합산 8 stage 이하\n" .
	"투명 교정 · SureSmile 일반 | 320만 원 | 양악 합산 9~16 stage\n" .
	"투명 교정 · SureSmile 전체 | 550만 원 | 양악 합산 17 stage 이상\n" .
	"금속(Metal) 브라켓 교정 | 420만 원 | 전통 교정\n" .
	"심미(레진) 브라켓 교정 | 450만 원 | 브라켓 색 자연스럽게\n" .
	"자가결찰 교정 (A-Line) | 500만 원 | 와이어 결찰 자동\n" .
	"악궁 확장장치 | 악당 50만 원 | 성장기 상악·하악 확장\n" .
	"페이스 마스크 | 200만 원 | 성장기 골격 교정\n" .
	"미니 스크류 | 개당 5만 원 | 고정원 보강\n" .
	"유지장치 · 설측 LFR (상하악) | 악당 15만 원 | 필수 착용\n" .
	"유지장치 · 투명 리테이너 | 악당 15만 원 | 야간 착용\n" .
	"유지장치 재제작 (본원) | 15만 원 | \n" .
	"교정 후 발치 (본원 교정) | 치아당 5만 원 | \n" .
	"재교정 | 50만 원 | \n\n" .

	"== 크라운·틀니 ==\n" .
	"일반 크라운 · PFM (도자기+금속) | 치아당 45만 원 | 대중 보급형\n" .
	"지르코니아 크라운 (어금니) | 치아당 55만 원 | 강도·심미 균형\n" .
	"지르코니아 크라운 (앞니) | 치아당 60만 원 | 심미 우선\n" .
	"골드 크라운 | 치아당 95만 원 | 교합 안정·내구성\n" .
	"임시 크라운 | 치아당 5만 원 | 보철 대기 중\n" .
	"세라믹 인레이 (1면) | 치아당 35만 원 | 심미 충전\n" .
	"세라믹 인레이 (2면 이상) | 치아당 40만 원 | \n" .
	"골드 인레이 (1면) | 치아당 55만 원 | 내구성 우선\n" .
	"골드 인레이 (2면) | 치아당 65만 원 | \n" .
	"골드 인레이 (3면) | 치아당 75만 원 | \n" .
	"포스트 · 스크류(다이렉트) | 20만 원 | \n" .
	"포스트 · DT post(세라믹) | 30만 원 | \n" .
	"캐스트 포스트 (전치·소구치) | 30만 원 | \n" .
	"캐스트 포스트 (구치·기둥 2개) | 35만 원 | \n" .
	"캐스트 포스트 (구치·기둥 3개) | 40만 원 | \n" .
	"틀니 (부분/전체) | 150만 원 | 진단 후 크라운 별도\n\n" .

	"== 충치·레진 ==\n" .
	"레진 (1면) | 치아당 10만 원 | 교두당 5만 가산\n" .
	"레진 (1면·광범위) | 치아당 15만 원 | 교두당 5만 가산\n" .
	"레진 (2면) | 치아당 15만 원 | 교두당 5만 가산\n" .
	"레진 (2면·MO/DO 포함) | 치아당 30만 원 | 교두당 5만 가산\n" .
	"레진 (3면 이상) | 치아당 30만 원 | 교두당 5만 가산\n" .
	"레진 (3면 이상·MO/DO 포함) | 치아당 35만 원 | 교두당 5만 가산\n" .
	"레진 (3면 이상·MOD 포함) | 치아당 50만 원 | 최대 범위\n" .
	"레진 (앞니 사이 틈 메우기) | 치아당 25만 원 | 정중이개\n" .
	"레진 (반점치) | 치아당 20만 원 | 심미 보완\n" .
	"레진 (치경부 마모증) | 치아당 8만 원 | 잇몸 경계\n" .
	"레진 비니어 (앞니 심미) | 치아당 35만 원 | 변색 보완\n" .
	"레진 코어 (크라운 토대) | 치아당 8만 원 | 보철 전 기둥\n" .
	"신경치료 후 레진 충전 | 8만 원 | 신경치료 마감\n" .
	"신경치료 후 럭사코어 | 8만 원 | 신경치료 마감\n" .
	"유치 레진 (1면) | 치아당 8만 원 | 소아용\n" .
	"유치 레진 (2면 이상) | 치아당 10만 원 | 소아용\n\n" .

	"== 심미·미백 ==\n" .
	"라미네이트 (앞니 심미) | 치아당 66만 원 | 부가세 포함\n" .
	"자가 치아미백 · Omnivac 4주분 | 33만 원 | 집에서 사용, 부가세 포함\n" .
	"자가미백 · Omnivac 장치 추가 | 5만 원 | \n" .
	"자가미백 · 약제 (1주치) | 5만 원 | \n" .
	"자가미백 · 약제 (4주치) | 15만 원 | \n" .
	"전문가 치아미백 · 1-day (2회) | 33만 원 | 부가세 포함\n" .
	"전문가 치아미백 · 1-day (3회) | 44만 원 | 부가세 포함\n" .
	"전문가 치아미백 · 2-day (총 3회) | 44만 원 | 부가세 포함\n" .
	"전문가 치아미백 · 2-day (총 4회) | 55만 원 | 부가세 포함\n" .
	"잇몸 미백 | 악당 20만 원 | 잇몸 색 개선·부가세 별도\n" .
	"거미스마일 (레이저 잇몸 성형) | 치아당 20만 원 | 잇몸 라인 조정\n\n" .

	"== 소아·예방 ==\n" .
	"실란트 (보험·만 18세 이하 대구치) | 본인부담 21,700원~ | 우식 없는 제1,2 대구치\n" .
	"실란트 (비급여·작은어금니) | 치아당 3만 원 | 예방 시술\n" .
	"불소 도포 (전악) | 3만 원 | 치아 강화·충치 예방\n" .
	"지각과민 처치 (6치당) | 본인부담 8,700~13,500원 | 시린 이 완화\n" .
	"덴탈스파 (전문가 칫솔질·잇몸마사지) | 5만 원 | \n" .
	"공간유지장치 Crown&loop | 공간당 20만 원 | 유치 조기 상실 시\n" .
	"공간유지장치 Band&loop | 공간당 15만 원 | \n" .
	"SS 크라운 · 유치 (본떠서 제작) | 치아당 20만 원 | \n" .
	"SS 크라운 · 영구치 (본떠서 제작) | 치아당 25만 원 | \n" .
	"SP 크라운 · 유치 (기성) | 치아당 15만 원 | \n" .
	"SP 크라운 · 영구치 (기성) | 치아당 20만 원 | \n\n" .

	"== 턱관절 ==\n" .
	"턱관절 보톡스 | 20만 원 | 이갈이·교근 통증\n" .
	"턱관절강 PDRN 주사 | 20만 원 | 관절 염증 완화\n" .
	"턱관절 스플린트 (하드) | 100만 원 | 야간 착용\n" .
	"나이트가드 (이갈이 방지·소프트) | 30만 원 | 야간 착용";

$price_tabs = md_parse_price_tabs( md_content( 'price_tabs_all', $default_all_tabs ) );

/* v3.42.3 · 렌더러 안전장치
 * 저장된 price_tabs_all의 특정 탭이 비어있으면 (rows < 3개) 코드 default로 자동 대체.
 * 관리자 접속 없이도 프론트에서 즉시 폴백. Customizer 저장값은 건드리지 않음. */
$tab_fallback_rows = array(
	'충치·레진' => array(
		array( '치경부 마모증 (잇몸 경계 시린 부위)', '8만 원', '' ),
		array( '레진 · 한 면 충전',                 '10만 원', '' ),
		array( '레진 · 한 면 (넓은 범위)',           '15만 원', '' ),
		array( '레진 · 앞니 사이 틈 메우기 (정중이개)', '25만 원', '' ),
		array( '레진 · 두 면 충전',                 '15만 원', '' ),
		array( '레진 · 두 면 (씹는 면 포함)',        '30만 원', '' ),
		array( '레진 · 세 면 이상',                 '30만 원', '' ),
		array( '레진 · 세 면 이상 (씹는 면 포함)',   '35만 원', '' ),
		array( '레진 · 세 면 이상 (씹는 면·양쪽 옆면 모두)', '50만 원', '' ),
		array( '레진 · 치아 변색 (반점치) 부위',     '20만 원', '' ),
		array( '레진 비니어 (앞니 코팅)',           '35만 원', '' ),
	),
);
foreach ( $price_tabs as $key => $tab ) {
	$label = $tab['label'] ?? '';
	if ( isset( $tab_fallback_rows[ $label ] ) ) {
		$rows = $tab['rows'] ?? array();
		// v3.42.4 · 제거 대상 항목 포함 시에도 fallback 강제
		$has_deprecated = false;
		foreach ( $rows as $r ) {
			$name = $r[0] ?? '';
			if ( strpos( $name, '레진 코어' ) !== false
				|| strpos( $name, '신경치료 후' ) !== false
				|| strpos( $name, '유치 레진' ) !== false
				|| strpos( $name, '럭사코어' ) !== false ) {
				$has_deprecated = true;
				break;
			}
		}
		if ( count( $rows ) < 3 || $has_deprecated ) {
			$price_tabs[ $key ]['rows'] = $tab_fallback_rows[ $label ];
		}
	}
}

$first_tab_key = $price_tabs ? array_key_first( $price_tabs ) : '';

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
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸ <span>비용 안내</span>
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
				<?php /* v3.37.5 · 비용안내 히어로 CTA 버튼 제거 (하단 통일 CTA와 중복) */ ?>
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

<?php /* v3.37.8 · 비용안내 중간 CTA (md-priceX-cta) 제거 · 하단 통일 CTA와 중복 */ ?>
<?php /* 위치 섹션은 푸터 위 section-location과 중복되어 제거됨 — v3.12.3 */ ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
