<?php
/**
 * Section: 구강상태 자가진단 봇.
 *
 *  Yes/No 15 질문 → 가중치 점수 → 추천 진료과 1~3개 + /진료항목 페이지 링크.
 *  본 진단은 참고용 — 정확한 진단은 내원 후 진료가 필요함을 명시.
 *
 *  진료과 매핑은 사이트의 실제 service slug와 일치:
 *    /자연치아-살리기/  · /임플란트-센터/  · /투명교정-센터/
 *    /턱관절-클리닉/    · /사랑니-발치/    · /심미치료/
 *
 * @package moondental-child
 */

// ─── 질문 정의 (30 항목 — 통증·잇몸·치아 손실·교정·턱관절·심미·예방·전신) ────
$questions = array(
	// 통증 (4)
	array( 'id'=>'q1',  'cat'=>'심한 통증',  'q'=>'가만히 있어도 욱신거리거나 잠을 못 잘 정도로 아픈 치아가 있나요?',                  'yes'=>array( '자연치아-살리기'=>3 ) ),
	array( 'id'=>'q2',  'cat'=>'시림',       'q'=>'차거나 뜨거운 음식·찬바람에 시린 치아가 있나요?',                                       'yes'=>array( '자연치아-살리기'=>2 ) ),
	array( 'id'=>'q3',  'cat'=>'단음식 통증','q'=>'단 음식(초콜릿·사탕 등)을 먹을 때 특정 치아가 시큰거리나요?',                            'yes'=>array( '자연치아-살리기'=>2 ) ),
	array( 'id'=>'q4',  'cat'=>'씹기 통증',  'q'=>'음식을 씹을 때 특정 부위가 아프거나 불편한가요?',                                       'yes'=>array( '자연치아-살리기'=>2, '사랑니-발치'=>1 ) ),

	// 잇몸 (5)
	array( 'id'=>'q5',  'cat'=>'잇몸 출혈',  'q'=>'양치할 때 잇몸에서 자주 피가 나거나, 잇몸이 자주 붓나요?',                              'yes'=>array( '자연치아-살리기'=>3 ) ),
	array( 'id'=>'q6',  'cat'=>'잇몸 퇴축',  'q'=>'잇몸이 내려앉아 치아 뿌리가 보이거나 치아가 길어 보이나요?',                              'yes'=>array( '자연치아-살리기'=>3 ) ),
	array( 'id'=>'q7',  'cat'=>'입냄새',     'q'=>'입냄새가 신경 쓰이거나, 입에서 쓴맛·고름맛이 나나요?',                                   'yes'=>array( '자연치아-살리기'=>2, '예방클리닉'=>2 ) ),
	array( 'id'=>'q8',  'cat'=>'스케일링',   'q'=>'최근 6개월~1년 이상 스케일링을 받지 않으셨나요?',                                       'yes'=>array( '예방클리닉'=>3, '자연치아-살리기'=>1 ) ),
	array( 'id'=>'q9',  'cat'=>'음식 끼임',  'q'=>'치아 사이에 음식물이 자주 끼이거나 빠지지 않는 곳이 있나요?',                            'yes'=>array( '자연치아-살리기'=>2 ) ),

	// 치아 손실·흔들림 (3)
	array( 'id'=>'q10', 'cat'=>'치아 손실',  'q'=>'빠진 치아가 있거나, 발치 후 비어있는 자리가 있나요?',                                    'yes'=>array( '임플란트-센터'=>3 ) ),
	array( 'id'=>'q11', 'cat'=>'치아 흔들림','q'=>'흔들리는 치아가 있거나 치아가 들뜨는 느낌이 있나요?',                                    'yes'=>array( '자연치아-살리기'=>2, '임플란트-센터'=>1 ) ),
	array( 'id'=>'q12', 'cat'=>'보철물 문제','q'=>'기존 보철물(크라운·브릿지·틀니·임플란트)이 빠지거나 흔들리거나 불편한가요?',              'yes'=>array( '임플란트-센터'=>2 ) ),

	// 교정 (3)
	array( 'id'=>'q13', 'cat'=>'치열',       'q'=>'치아가 비뚤어져 있거나, 치아 사이가 벌어져 있나요?',                                    'yes'=>array( '투명교정-센터'=>3 ) ),
	array( 'id'=>'q14', 'cat'=>'교합·돌출',  'q'=>'위아래 치아가 잘 맞물리지 않거나, 앞니가 튀어나와 보이나요?',                            'yes'=>array( '투명교정-센터'=>3 ) ),
	array( 'id'=>'q15', 'cat'=>'잘 안 다물리는 입','q'=>'평소 입이 잘 안 다물리거나 입술이 자연스럽게 닫히지 않나요?',                       'yes'=>array( '투명교정-센터'=>2 ) ),

	// 턱관절·이갈이 (3)
	array( 'id'=>'q16', 'cat'=>'턱 소리',    'q'=>'입을 벌리거나 닫을 때 턱에서 소리(딸깍·뚝)가 나거나, 턱 주변에 통증이 있나요?',          'yes'=>array( '턱관절-클리닉'=>3 ) ),
	array( 'id'=>'q17', 'cat'=>'개구 장애',  'q'=>'아침에 일어났을 때 턱이 뻐근하거나, 입이 잘 벌어지지 않는 경우가 있나요?',               'yes'=>array( '턱관절-클리닉'=>3 ) ),
	array( 'id'=>'q18', 'cat'=>'이갈이',     'q'=>'잘 때 이를 갈거나 꽉 무는 습관이 있다는 말을 들으신 적이 있나요?',                       'yes'=>array( '턱관절-클리닉'=>3, '심미치료'=>1 ) ),

	// 사랑니 (1)
	array( 'id'=>'q19', 'cat'=>'사랑니',     'q'=>'어금니 가장 안쪽(사랑니 부위)에 통증·부종이 있거나, 사랑니 발치를 권유받은 적 있나요?',  'yes'=>array( '사랑니-발치'=>3 ) ),

	// 심미 (5)
	array( 'id'=>'q20', 'cat'=>'치아 변색',  'q'=>'치아 색이 어둡거나 변색이 있어 미백을 고려하시나요?',                                    'yes'=>array( '심미치료'=>2 ) ),
	array( 'id'=>'q21', 'cat'=>'잇몸 색',    'q'=>'잇몸이 검거나 어두워 잇몸 미백을 고려하시나요?',                                          'yes'=>array( '심미치료'=>2 ) ),
	array( 'id'=>'q22', 'cat'=>'앞니 모양',  'q'=>'앞니의 모양·길이·잇몸 라인이 마음에 들지 않아 자연스럽게 다듬고 싶으신가요?',              'yes'=>array( '심미치료'=>3 ) ),
	array( 'id'=>'q23', 'cat'=>'거미스마일', 'q'=>'웃을 때 잇몸이 많이 보여서(거미스마일) 신경 쓰이나요?',                                   'yes'=>array( '심미치료'=>3 ) ),
	array( 'id'=>'q24', 'cat'=>'심미 보철',  'q'=>'기존 크라운·앞니 보철이 자연스럽지 않거나 색이 차이나 보이나요?',                         'yes'=>array( '심미치료'=>2, '임플란트-센터'=>1 ) ),

	// 어린이·예방 (2)
	array( 'id'=>'q25', 'cat'=>'어린이',     'q'=>'어린이·청소년 환자이신가요? (만 18세 이하)',                                            'yes'=>array( '예방클리닉'=>3 ) ),
	array( 'id'=>'q26', 'cat'=>'예방 검진',  'q'=>'특별한 증상은 없지만 정기 검진·스케일링·불소도포 등 예방 진료를 원하시나요?',             'yes'=>array( '예방클리닉'=>3, '일반-검진'=>1 ) ),

	// 전신·습관 (2)
	array( 'id'=>'q27', 'cat'=>'전신질환',   'q'=>'고혈압·당뇨·심장질환·골다공증 등 전신질환이 있어 진료가 망설여지나요?',                  'yes'=>array( '예방클리닉'=>2, '임플란트-센터'=>1 ) ),
	array( 'id'=>'q28', 'cat'=>'임신·수유',  'q'=>'임신·수유 중이시거나 임신을 준비 중이신가요? (안전 진료 필요)',                          'yes'=>array( '예방클리닉'=>2 ) ),

	// 보철 검토 (1)
	array( 'id'=>'q29', 'cat'=>'임플란트 검토','q'=>'임플란트·뼈이식·발치 후 보철에 대해 견적·계획을 알아보고 싶으신가요?',                  'yes'=>array( '임플란트-센터'=>3 ) ),

	// 응급·기타 (1)
	array( 'id'=>'q30', 'cat'=>'응급 통증',  'q'=>'심한 부종·고름·삼키기 어려운 통증 등 응급 상황으로 느껴지나요?',                          'yes'=>array( '자연치아-살리기'=>3, '사랑니-발치'=>2 ) ),
);

// ─── 진료과 정보 ─────────────────────────────────────────────
$depts = array(
	'자연치아-살리기' => array(
		'name'    => '자연치아 살리기',
		'sub'     => '보존 · 신경치료 · 잇몸치료',
		'url'     => home_url( '/자연치아-살리기/' ),
		'summary' => '신경치료·재근관치료·치주치료로 자연치아를 최대한 살립니다. 통증·시림·잇몸 트러블은 조기 진료가 중요합니다.',
	),
	'임플란트-센터' => array(
		'name'    => '임플란트 센터',
		'sub'     => '발치 후 회복 · 보철',
		'url'     => home_url( '/임플란트-센터/' ),
		'summary' => '빠진 치아·흔들리는 치아를 인공치근으로 회복합니다. 디지털 가이드 · 골 이식 케이스 포함.',
	),
	'투명교정-센터' => array(
		'name'    => '투명교정 센터',
		'sub'     => '슈어스마일 · 일반교정',
		'url'     => home_url( '/투명교정-센터/' ),
		'summary' => '비뚤어진 치아·돌출 입·맞물림 문제를 자연스럽게 개선합니다. 시뮬레이션으로 결과 미리 확인.',
	),
	'턱관절-클리닉' => array(
		'name'    => '턱관절 클리닉',
		'sub'     => '소리 · 통증 · 개구장애',
		'url'     => home_url( '/턱관절-클리닉/' ),
		'summary' => '턱관절 소리·통증·턱 주변 두통 진단 및 치료. 보존적 치료 우선.',
	),
	'사랑니-발치' => array(
		'name'    => '사랑니 발치',
		'sub'     => '안전한 CBCT 진단',
		'url'     => home_url( '/사랑니-발치/' ),
		'summary' => 'CBCT 3D 진단으로 신경 손상 위험을 최소화한 안전한 발치. 진정요법 가능.',
	),
	'심미치료' => array(
		'name'    => '심미치료',
		'sub'     => '미백 · 라미네이트',
		'url'     => home_url( '/심미치료/' ),
		'summary' => '미백·라미네이트·심미 보철로 자연스러운 미소를 만듭니다. 최소 삭제 보존적 접근.',
	),
	'일반-검진' => array(
		'name'    => '정기 검진 · 스케일링',
		'sub'     => '예방 · 조기 발견',
		'url'     => home_url( '/상담예약/' ),
		'summary' => '6개월~1년 주기 정기 검진은 잇몸 건강과 자연치아 보존의 첫 걸음입니다. 보험 적용.',
	),
	'예방클리닉' => array(
		'name'    => '예방 클리닉 · 덴탈 SPA',
		'sub'     => '스케일링 · 불소도포 · 에어플로우 · 실란트',
		'url'     => home_url( '/예방클리닉/' ),
		'summary' => '덴탈 SPA — 스케일링·에어플로우·불소도포·실란트 등 예방 진료. 충치·치주염 발생 전에 막는 가장 경제적인 치료.',
	),
);

$bot_data = array(
	'questions' => array_values( $questions ),
	'depts'     => $depts,
);

$bot_eyebrow  = function_exists( 'md_content' ) ? md_content( 'bot_eyebrow',  'SELF-CHECK' ) : 'SELF-CHECK';
$bot_title    = function_exists( 'md_content' ) ? md_content( 'bot_title',    '🦷 내 구강상태 진단받기' ) : '🦷 내 구강상태 진단받기';
$bot_lead     = function_exists( 'md_content' ) ? md_content( 'bot_lead',     "몇 가지 질문에 답해주시면 가장 적합한 진료과를 추천해드립니다.\n※ 본 진단은 참고용이며, 정확한 진단은 내원 진료가 필요합니다." ) : "몇 가지 질문에 답해주시면 가장 적합한 진료과를 추천해드립니다.\n※ 본 진단은 참고용이며, 정확한 진단은 내원 진료가 필요합니다.";
$bot_start    = function_exists( 'md_content' ) ? md_content( 'bot_start_label', '진단 시작 →' ) : '진단 시작 →';
$bot_count_label = sprintf( '%d개의 Yes/No 질문 · 약 2-3분 소요 · 모든 진료영역 망라', count( $questions ) );
?>
<?php
// 사이드 예약 버튼용 데이터
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$kakao_url  = $info['kakao_url'] ?? '';
$naver_book = $info['naver_place'] ?? '';
?>
<section class="md-section md-section--surface md-dentalbot" id="dental-bot" aria-label="구강 자가진단">
	<div class="md-container">

		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( $bot_eyebrow ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $bot_title ); ?></h2>
			<p class="md-section-head__lead"><?php echo nl2br( esc_html( $bot_lead ) ); ?></p>
		</header>

		<div class="md-dentalbot__layout">

		<div class="md-bot" data-md-bot data-md-bot-json="<?php echo esc_attr( wp_json_encode( $bot_data ) ); ?>">

			<!-- 1) Intro -->
			<div class="md-bot__panel md-bot__panel--intro" data-md-bot-screen="intro">
				<div class="md-bot__intro-icon" aria-hidden="true">🦷</div>
				<h3 class="md-bot__intro-title">간단한 자가진단 시작하기</h3>
				<p class="md-bot__intro-lead"><?php echo esc_html( $bot_count_label ); ?></p>
				<button type="button" class="md-btn md-btn-primary md-btn--lg md-bot__start" data-md-bot-start>
					<?php echo esc_html( $bot_start ); ?>
				</button>
				<p class="md-bot__intro-note">개인정보는 수집·저장되지 않습니다. 답변은 브라우저 안에서만 처리됩니다.</p>
			</div>

			<!-- 2) Quiz -->
			<div class="md-bot__panel md-bot__panel--quiz" data-md-bot-screen="quiz" hidden>
				<div class="md-bot__progress">
					<div class="md-bot__progress-bar"><div class="md-bot__progress-fill" data-md-bot-fill style="width:0%"></div></div>
					<div class="md-bot__progress-text" aria-live="polite" aria-atomic="true">
						<span class="md-sr-only">진행:</span>
						<span data-md-bot-idx>1</span> <span aria-hidden="true">/</span> <span data-md-bot-total><?php echo count( $questions ); ?></span>
					</div>
				</div>
				<span class="md-bot__cat" data-md-bot-cat>—</span>
				<h3 class="md-bot__question" data-md-bot-q>—</h3>
				<div class="md-bot__answers">
					<button type="button" class="md-btn md-btn-primary md-btn--lg md-bot__answer md-bot__answer--yes" data-md-bot-answer="yes">
						<span aria-hidden="true">✓</span> 예
					</button>
					<button type="button" class="md-btn md-btn-ghost md-btn--lg md-bot__answer md-bot__answer--no" data-md-bot-answer="no">
						<span aria-hidden="true">✗</span> 아니요
					</button>
				</div>
				<button type="button" class="md-bot__back" data-md-bot-back hidden>← 이전 질문</button>
			</div>

			<!-- 3) Result -->
			<div class="md-bot__panel md-bot__panel--result" data-md-bot-screen="result" hidden>
				<div class="md-bot__result-head">
					<div class="md-bot__result-icon" aria-hidden="true">✨</div>
					<h3 class="md-bot__result-title">진단 결과 — 추천 진료과</h3>
					<p class="md-bot__result-lead" data-md-bot-result-lead>증상에 가장 부합하는 진료과를 추천해드립니다.</p>
				</div>

				<div class="md-bot__result-list" data-md-bot-results role="list">
					<!-- JS 동적 생성 -->
				</div>

				<div class="md-bot__result-cta">
					<a href="#reservation-ctas" class="md-btn md-btn-primary md-btn--lg" data-track="cta-bot-result-book">📅 지금 예약 상담하기</a>
					<button type="button" class="md-btn md-btn-ghost md-btn--lg" data-md-bot-restart>↺ 다시 진단</button>
				</div>

				<p class="md-bot__disclaimer">
					⚠️ 본 결과는 자가진단 참고용입니다. 정확한 진단·치료를 위해서는 의료진의 직접 진료가 필요합니다.
				</p>
			</div>

		</div>

		<!-- 우측 예약·상담 3 버튼 (모바일에선 하단 스택) -->
		<aside class="md-dentalbot__cta" aria-label="예약·상담 채널">
			<h3 class="md-dentalbot__cta-title">진단 안 받고 바로 상담</h3>
			<p class="md-dentalbot__cta-lead">증상이 명확하다면 곧장 예약·상담하세요.</p>

			<?php if ( $naver_book ) : ?>
				<a class="md-btn md-btn--naver md-btn--lg md-dentalbot__cta-btn"
				   href="<?php echo esc_url( $naver_book ); ?>"
				   target="_blank" rel="noopener"
				   data-track="cta-dentalbot-naver">
					<svg class="md-rcta__icon" viewBox="0 0 24 24" aria-hidden="true">
						<circle cx="12" cy="12" r="12" fill="#ffffff"/>
						<path d="M9 8h2.2l3.6 5.1V8H17v8h-2.2l-3.6-5.1V16H9V8z" fill="#03C75A"/>
					</svg>
					<span class="md-rcta__label">네이버 예약</span>
				</a>
			<?php endif; ?>

			<?php if ( $kakao_url ) : ?>
				<a class="md-btn md-btn--kakao md-btn--lg md-dentalbot__cta-btn"
				   href="<?php echo esc_url( $kakao_url ); ?>"
				   target="_blank" rel="noopener"
				   data-track="cta-dentalbot-kakao">
					<svg class="md-rcta__icon" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M12 6c-3.7 0-6.7 2.4-6.7 5.4 0 1.9 1.3 3.6 3.2 4.5l-.7 2.6c-.06.23.18.4.38.27l3.05-2c.25.03.51.04.78.04 3.7 0 6.7-2.4 6.7-5.4S15.7 6 12 6z" fill="#3C1E1E"/>
					</svg>
					<span class="md-rcta__label">카카오톡 상담</span>
				</a>
			<?php endif; ?>

			<a class="md-btn md-btn-ghost md-btn--lg md-dentalbot__cta-btn"
			   href="tel:<?php echo esc_attr( $phone_link ); ?>"
			   data-track="cta-dentalbot-call">
				<svg class="md-rcta__icon md-rcta__icon--stroke" viewBox="0 0 24 24" aria-hidden="true">
					<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<span class="md-rcta__label">전화 상담 <?php echo esc_html( $info['phone'] ); ?></span>
			</a>

			<p class="md-dentalbot__cta-hint">진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00</p>
		</aside>

		</div><!-- /.md-dentalbot__layout -->
	</div>
</section>
