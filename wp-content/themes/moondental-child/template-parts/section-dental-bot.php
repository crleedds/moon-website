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

// ─── 질문 정의 ────────────────────────────────────────────────
// 각 질문: id, cat(카테고리), q(질문), yes(부서별 가중치)
$questions = array(
	array(
		'id'  => 'q1',
		'cat' => '치아 통증',
		'q'   => '가만히 있어도 욱신거리거나 잠을 못 잘 정도로 아픈 치아가 있나요?',
		'yes' => array( '자연치아-살리기' => 3 ),
	),
	array(
		'id'  => 'q2',
		'cat' => '치아 시림',
		'q'   => '차거나 뜨거운 음식·찬바람에 시린 치아가 있나요?',
		'yes' => array( '자연치아-살리기' => 2 ),
	),
	array(
		'id'  => 'q3',
		'cat' => '씹기 통증',
		'q'   => '음식을 씹을 때 특정 부위가 아프거나 불편한가요?',
		'yes' => array( '자연치아-살리기' => 2, '사랑니-발치' => 1 ),
	),
	array(
		'id'  => 'q4',
		'cat' => '잇몸 출혈',
		'q'   => '양치할 때 잇몸에서 자주 피가 나거나, 잇몸이 자주 붓나요?',
		'yes' => array( '자연치아-살리기' => 3 ),
	),
	array(
		'id'  => 'q5',
		'cat' => '잇몸 퇴축',
		'q'   => '잇몸이 내려앉아 치아가 길어 보이거나, 입냄새가 신경 쓰이나요?',
		'yes' => array( '자연치아-살리기' => 3 ),
	),
	array(
		'id'  => 'q6',
		'cat' => '치아 손실',
		'q'   => '빠진 치아가 있거나, 발치 후 비어있는 자리가 있나요?',
		'yes' => array( '임플란트-센터' => 3 ),
	),
	array(
		'id'  => 'q7',
		'cat' => '치아 흔들림',
		'q'   => '흔들리는 치아가 있나요?',
		'yes' => array( '자연치아-살리기' => 2, '임플란트-센터' => 1 ),
	),
	array(
		'id'  => 'q8',
		'cat' => '치열',
		'q'   => '치아가 비뚤어져 있거나, 치아 사이가 벌어져 있나요?',
		'yes' => array( '투명교정-센터' => 3 ),
	),
	array(
		'id'  => 'q9',
		'cat' => '교합',
		'q'   => '위아래 치아가 잘 맞물리지 않거나, 앞니가 튀어나와 보이나요?',
		'yes' => array( '투명교정-센터' => 3 ),
	),
	array(
		'id'  => 'q10',
		'cat' => '턱관절',
		'q'   => '입을 벌리거나 닫을 때 턱에서 소리(딸깍·뚝)가 나거나, 턱 주변에 통증이 있나요?',
		'yes' => array( '턱관절-클리닉' => 3 ),
	),
	array(
		'id'  => 'q11',
		'cat' => '사랑니',
		'q'   => '어금니 가장 안쪽(사랑니 부위)에 통증·부종이 있거나, 사랑니 발치를 권유받은 적 있나요?',
		'yes' => array( '사랑니-발치' => 3 ),
	),
	array(
		'id'  => 'q12',
		'cat' => '치아 변색',
		'q'   => '치아 색이 어둡거나 변색이 있어 미백을 고려하시나요?',
		'yes' => array( '심미치료' => 2 ),
	),
	array(
		'id'  => 'q13',
		'cat' => '앞니 심미',
		'q'   => '앞니의 모양·잇몸 라인을 더 자연스럽게 다듬고 싶으신가요?',
		'yes' => array( '심미치료' => 3 ),
	),
	array(
		'id'  => 'q14',
		'cat' => '보철물',
		'q'   => '기존 보철물(크라운·브릿지·틀니)이 빠지거나 불편한가요?',
		'yes' => array( '임플란트-센터' => 2 ),
	),
	array(
		'id'  => 'q15',
		'cat' => '정기 검진',
		'q'   => '특별한 증상은 없지만 정기 검진이나 스케일링을 받고 싶으신가요?',
		'yes' => array( '일반-검진' => 2 ),
	),
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
		'sub'     => '인비절라인 · 일반교정',
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
);

$bot_data = array(
	'questions' => array_values( $questions ),
	'depts'     => $depts,
);

$bot_eyebrow  = function_exists( 'md_content' ) ? md_content( 'bot_eyebrow',  'SELF-CHECK' ) : 'SELF-CHECK';
$bot_title    = function_exists( 'md_content' ) ? md_content( 'bot_title',    '🦷 내 구강상태 진단받기' ) : '🦷 내 구강상태 진단받기';
$bot_lead     = function_exists( 'md_content' ) ? md_content( 'bot_lead',     "몇 가지 질문에 답해주시면 가장 적합한 진료과를 추천해드립니다.\n※ 본 진단은 참고용이며, 정확한 진단은 내원 진료가 필요합니다." ) : "몇 가지 질문에 답해주시면 가장 적합한 진료과를 추천해드립니다.\n※ 본 진단은 참고용이며, 정확한 진단은 내원 진료가 필요합니다.";
$bot_start    = function_exists( 'md_content' ) ? md_content( 'bot_start_label', '진단 시작 →' ) : '진단 시작 →';
$bot_count_label = sprintf( '%d개의 Yes/No 질문 · 약 1-2분 소요', count( $questions ) );
?>
<section class="md-section md-section--surface md-dentalbot" id="dental-bot" aria-label="구강 자가진단">
	<div class="md-container md-container--narrow">

		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( $bot_eyebrow ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $bot_title ); ?></h2>
			<p class="md-section-head__lead"><?php echo nl2br( esc_html( $bot_lead ) ); ?></p>
		</header>

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
					<div class="md-bot__progress-text">
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
	</div>
</section>
