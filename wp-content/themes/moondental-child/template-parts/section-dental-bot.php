<?php
/**
 * Section: 구강상태 자가진단 봇.
 *
 *  30개 Yes/No 질문 → 가중치 점수 → 추천 진료과 1~3개 + /진료항목 페이지 링크.
 *  v3.32.6: 질문·진료과·UI 문구 전부 Customizer에서 편집 가능.
 *
 *  질문 형식:  카테고리 | 질문 | 진료과키:가중치, 진료과키:가중치
 *  진료과 형식: 슬러그 | 이름 | 부제 | URL 경로 | 요약
 *
 * @package moondental-child
 */

/* 진료과 파싱 */
$depts_raw = md_content( 'bot_depts', '' );
$depts = array();
foreach ( md_parse_lines( $depts_raw ) as $line ) {
	$parts = array_map( 'trim', explode( '|', $line ) );
	if ( count( $parts ) < 5 ) continue;
	$slug = $parts[0];
	$url  = $parts[3];
	$depts[ $slug ] = array(
		'name'    => $parts[1],
		'sub'     => $parts[2],
		'url'     => ( strpos( $url, 'http' ) === 0 ) ? $url : home_url( $url ),
		'summary' => $parts[4],
	);
}

/* v3.40.0 · 확장 스키마 · 5개 컬럼
 *  카테고리 | 질문 | 진료과키:가중치, ... | chief 태그(콤마) | 특수 태그(콤마)
 *  chief 태그: 치아·잇몸·턱관절·심미·예방·all·(빈칸=all)
 *  특수 태그: urgent · contra=diabetes · contra=anticoag · contra=bisphos · contra=pregnancy · contra=heart · contra=allergy · 여러 개는 콤마
 *  하위 호환: 3개 컬럼만 있어도 동작 (chief=all, 태그 없음) */
$questions_raw = md_content( 'bot_questions', '' );
$questions = array();
$idx = 1;
foreach ( md_parse_lines( $questions_raw ) as $line ) {
	$parts = array_map( 'trim', explode( '|', $line ) );
	if ( count( $parts ) < 3 ) continue;
	$cat  = $parts[0];
	$q    = $parts[1];
	$yes  = array();
	foreach ( explode( ',', $parts[2] ) as $pair ) {
		$pp = array_map( 'trim', explode( ':', $pair ) );
		if ( count( $pp ) === 2 && $pp[0] !== '' && is_numeric( $pp[1] ) ) {
			$yes[ $pp[0] ] = (int) $pp[1];
		}
	}
	// chief 태그
	$chief = array();
	if ( isset( $parts[3] ) && trim( $parts[3] ) !== '' ) {
		foreach ( explode( ',', $parts[3] ) as $c ) {
			$c = trim( $c );
			if ( $c !== '' ) $chief[] = $c;
		}
	}
	if ( empty( $chief ) ) $chief = array( 'all' );
	// 특수 태그
	$urgent  = false;
	$contras = array();
	if ( isset( $parts[4] ) && trim( $parts[4] ) !== '' ) {
		foreach ( explode( ',', $parts[4] ) as $t ) {
			$t = trim( $t );
			if ( $t === '' ) continue;
			if ( strtolower( $t ) === 'urgent' ) { $urgent = true; continue; }
			if ( strpos( $t, 'contra=' ) === 0 ) {
				$contras[] = substr( $t, 7 );
			}
		}
	}
	$questions[] = array(
		'id'      => 'q' . $idx,
		'cat'     => $cat,
		'q'       => $q,
		'yes'     => $yes,
		'chief'   => $chief,
		'urgent'  => $urgent,
		'contras' => $contras,
	);
	$idx++;
}

/* chief 선택지 파싱 · '키|아이콘|라벨|설명' */
$chief_raw = md_content( 'bot_chief_options',
	"치아|🦷|치아 문제|통증·충치·시림·부러짐·발치\n잇몸|💧|잇몸·구강|붓기·출혈·구취·시림\n턱관절|🦴|턱관절·얼굴|턱 소리·통증·개구장애·이갈이\n심미|✨|심미·미소|색·모양·라미네이트·미백\n예방|🛡️|예방·검진|스케일링·정기 검진\nall|📋|잘 모르겠음|전체 질문 진행"
);
$chief_options = array();
foreach ( md_parse_lines( $chief_raw ) as $line ) {
	$p = array_map( 'trim', explode( '|', $line ) );
	if ( count( $p ) < 3 ) continue;
	$chief_options[] = array(
		'key'   => $p[0],
		'icon'  => $p[1] ?? '',
		'label' => $p[2] ?? '',
		'desc'  => $p[3] ?? '',
	);
}

/* 금기 · contra 안내문 매핑 */
$contra_messages = array(
	'diabetes'  => md_content( 'bot_contra_diabetes',  '당뇨병이 있으시면 임플란트·발치 전 최근 혈당 조절 상태(HbA1c 등)를 알려주세요. 감염·상처 회복에 영향을 줄 수 있습니다.' ),
	'anticoag'  => md_content( 'bot_contra_anticoag',  '항응고제(아스피린·와파린·NOAC 등) 복용 중이시면 발치·수술 전 처방 내과와 조율이 필요할 수 있습니다.' ),
	'bisphos'   => md_content( 'bot_contra_bisphos',   '골다공증 약(비스포스포네이트 계열) 복용 이력이 있으시면 반드시 알려주세요. 임플란트·발치 시 턱뼈괴사(MRONJ) 위험을 사전에 평가해야 합니다.' ),
	'pregnancy' => md_content( 'bot_contra_pregnancy', '임신·수유 중이시면 X-ray·특정 마취제·항생제 사용을 조절합니다. 방문 시 반드시 알려주세요.' ),
	'heart'     => md_content( 'bot_contra_heart',     '심장 판막 이상·인공 판막이 있으시면 발치·수술 전 예방적 항생제가 필요할 수 있습니다.' ),
	'allergy'   => md_content( 'bot_contra_allergy',   '치과 마취제·페니실린 계열 항생제 등 알러지가 있으시면 반드시 사전에 알려주세요.' ),
);

$bot_data = array(
	'questions'    => array_values( $questions ),
	'depts'        => $depts,
	'chiefOptions' => $chief_options,
	'contraMsgs'   => $contra_messages,
);

$bot_eyebrow  = md_content( 'bot_eyebrow',  'SELF-CHECK' );
$bot_title    = md_content( 'bot_title',    '🦷 내 구강상태 진단받기' );
$bot_lead     = md_content( 'bot_lead',     "몇 가지 질문에 답해주시면 가장 적합한 진료과를 추천해드립니다.\n※ 본 진단은 참고용이며, 정확한 진단은 내원 진료가 필요합니다." );
$bot_start    = md_content( 'bot_start_label', '진단 시작 →' );
$bot_count_label = str_replace( '{count}', (string) count( $questions ), md_content( 'bot_count_template', '{count}개의 Yes/No 질문 · 약 2-3분 소요 · 모든 진료영역 망라' ) );

$intro_title = md_content( 'bot_intro_title', '간단한 자가진단 시작하기' );
$intro_note  = md_content( 'bot_intro_note', '개인정보는 수집·저장되지 않습니다. 답변은 브라우저 안에서만 처리됩니다.' );
$ans_yes     = md_content( 'bot_answer_yes', '✓ 예' );
$ans_no      = md_content( 'bot_answer_no', '✗ 아니요' );
$back_label  = md_content( 'bot_back_label', '← 이전 질문' );
$res_title   = md_content( 'bot_result_title', '진단 결과 — 추천 진료과' );
$res_lead    = md_content( 'bot_result_lead', '증상에 가장 부합하는 진료과를 추천해드립니다.' );
$res_book    = md_content( 'bot_result_book_label', '📅 지금 예약 상담하기' );
$res_restart = md_content( 'bot_result_restart', '↺ 다시 진단' );
$disclaimer  = md_content( 'bot_disclaimer', '⚠️ 본 결과는 자가진단 참고용입니다. 정확한 진단·치료를 위해서는 의료진의 직접 진료가 필요합니다.' );

$aside_title = md_content( 'bot_aside_title', '진단 안 받고 바로 상담' );
$aside_lead  = md_content( 'bot_aside_lead', '증상이 명확하다면 곧장 예약·상담하세요.' );
$aside_naver = md_content( 'bot_aside_btn_naver', '네이버 예약' );
$aside_kakao = md_content( 'bot_aside_btn_kakao', '카카오톡 상담' );
$aside_call_tpl = md_content( 'bot_aside_btn_call', '전화 상담' );
$aside_hint  = md_content( 'bot_aside_hint', '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00' );

$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$kakao_url  = $info['kakao_url'] ?? '';
$naver_book = $info['naver_place'] ?? '';
$aside_call = str_replace( '{phone}', $info['phone'], $aside_call_tpl );
?>
<section class="md-section md-section--surface md-dentalbot" id="dental-bot" aria-label="<?php echo esc_attr( md_content( 'aria_sec_dentalbot', '구강 자가진단' ) ); ?>">
	<div class="md-container">

		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( $bot_eyebrow ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $bot_title ); ?></h2>
			<p class="md-section-head__lead"><?php echo nl2br( esc_html( $bot_lead ) ); ?></p>
		</header>

		<div class="md-dentalbot__layout">

		<div class="md-bot" data-md-bot data-md-bot-json="<?php echo esc_attr( wp_json_encode( $bot_data ) ); ?>">

			<!-- 1) Intro · v3.39.0 · 3단계 프로세스 + 진료영역 chip + 신뢰 지표 -->
			<div class="md-bot__panel md-bot__panel--intro" data-md-bot-screen="intro">
				<div class="md-bot__intro-head">
					<div class="md-bot__intro-icon" aria-hidden="true">🦷</div>
					<h3 class="md-bot__intro-title"><?php echo esc_html( $intro_title ); ?></h3>
					<p class="md-bot__intro-lead"><?php echo esc_html( $bot_count_label ); ?></p>
				</div>

				<!-- 3단계 진단 프로세스 시각화 -->
				<ol class="md-bot__intro-steps" aria-label="<?php echo esc_attr( md_content( 'bot_intro_steps_aria', '진단 진행 방식' ) ); ?>">
					<li class="md-bot__intro-step">
						<span class="md-bot__intro-step-num">1</span>
						<div class="md-bot__intro-step-body">
							<strong><?php echo esc_html( md_content( 'bot_intro_step_1_title', '증상 답변' ) ); ?></strong>
							<span><?php echo esc_html( md_content( 'bot_intro_step_1_desc', '예/아니오 두 버튼으로 간단히' ) ); ?></span>
						</div>
					</li>
					<li class="md-bot__intro-step">
						<span class="md-bot__intro-step-num">2</span>
						<div class="md-bot__intro-step-body">
							<strong><?php echo esc_html( md_content( 'bot_intro_step_2_title', '가중치 분석' ) ); ?></strong>
							<span><?php echo esc_html( md_content( 'bot_intro_step_2_desc', '증상별 관련 진료과 자동 매칭' ) ); ?></span>
						</div>
					</li>
					<li class="md-bot__intro-step">
						<span class="md-bot__intro-step-num">3</span>
						<div class="md-bot__intro-step-body">
							<strong><?php echo esc_html( md_content( 'bot_intro_step_3_title', '진료과 추천' ) ); ?></strong>
							<span><?php echo esc_html( md_content( 'bot_intro_step_3_desc', '적합도순 최대 3개 안내' ) ); ?></span>
						</div>
					</li>
				</ol>

				<!-- 커버하는 진료영역 chip -->
				<?php if ( ! empty( $depts ) ) : ?>
				<div class="md-bot__intro-chips" aria-label="<?php echo esc_attr( md_content( 'bot_intro_chips_aria', '커버하는 진료 영역' ) ); ?>">
					<span class="md-bot__intro-chips-label"><?php echo esc_html( md_content( 'bot_intro_chips_label', '커버 진료영역' ) ); ?></span>
					<ul class="md-bot__intro-chip-list">
						<?php
						/* v3.44.23 · chip 이름 · 슬러그 기반 번역 지원 · bot_chip_{slug} */
						$_chip_slug_map = array(
							'자연치아-살리기' => 'preserve',
							'임플란트-센터'   => 'implant',
							'투명교정-센터'   => 'ortho',
							'턱관절-클리닉'   => 'tmj',
							'사랑니-발치'     => 'wisdom',
							'심미치료'        => 'aesthetic',
							'일반-검진'       => 'checkup',
							'예방클리닉'      => 'prevention',
						);
						foreach ( array_slice( $depts, 0, 8, true ) as $slug => $d ) :
							$_chip_key = 'bot_chip_' . ( $_chip_slug_map[ $slug ] ?? md5( $slug ) );
							$_chip_label = md_content( $_chip_key, $d['name'] );
						?>
							<li class="md-bot__intro-chip"><?php echo esc_html( $_chip_label ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>

				<button type="button" class="md-btn md-btn-primary md-btn--lg md-bot__start" data-md-bot-start>
					<?php echo esc_html( $bot_start ); ?>
				</button>

				<!-- 신뢰 지표 3개 -->
				<ul class="md-bot__intro-trust" aria-label="<?php echo esc_attr( md_content( 'bot_intro_trust_aria', '자가진단 봇 특징' ) ); ?>">
					<li>
						<span aria-hidden="true">🔒</span>
						<?php echo esc_html( md_content( 'bot_intro_trust_1', '답변 저장 안 함' ) ); ?>
					</li>
					<li>
						<span aria-hidden="true">⚡</span>
						<?php echo esc_html( md_content( 'bot_intro_trust_2', '30초 안에 결과' ) ); ?>
					</li>
					<li>
						<span aria-hidden="true">🆓</span>
						<?php echo esc_html( md_content( 'bot_intro_trust_3', '완전 무료' ) ); ?>
					</li>
				</ul>

				<p class="md-bot__intro-note"><?php echo esc_html( $intro_note ); ?></p>
			</div>

			<!-- 1.5) Chief Complaint · v3.40.0 · 어디가 불편하세요? -->
			<div class="md-bot__panel md-bot__panel--chief" data-md-bot-screen="chief" hidden>
				<h3 class="md-bot__chief-title"><?php echo esc_html( md_content( 'bot_chief_title', '어디가 가장 불편하신가요?' ) ); ?></h3>
				<p class="md-bot__chief-lead"><?php echo esc_html( md_content( 'bot_chief_lead', '주 증상 부위를 선택하시면 관련 질문만 보여드립니다. 잘 모르시겠으면 "잘 모르겠음"을 선택하세요.' ) ); ?></p>
				<div class="md-bot__chief-grid" role="group">
					<?php foreach ( $chief_options as $c ) : ?>
						<button type="button" class="md-bot__chief-btn" data-md-bot-chief="<?php echo esc_attr( $c['key'] ); ?>">
							<span class="md-bot__chief-icon" aria-hidden="true"><?php echo esc_html( $c['icon'] ); ?></span>
							<span class="md-bot__chief-label"><?php echo esc_html( $c['label'] ); ?></span>
							<?php if ( ! empty( $c['desc'] ) ) : ?>
								<span class="md-bot__chief-desc"><?php echo esc_html( $c['desc'] ); ?></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>
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
						<?php echo esc_html( $ans_yes ); ?>
					</button>
					<button type="button" class="md-btn md-btn-ghost md-btn--lg md-bot__answer md-bot__answer--no" data-md-bot-answer="no">
						<?php echo esc_html( $ans_no ); ?>
					</button>
				</div>
				<button type="button" class="md-bot__back" data-md-bot-back hidden><?php echo esc_html( $back_label ); ?></button>
			</div>

			<!-- 2.5) Safety Notice · v3.40.0 · 금기·주의 사항 감지 시 결과 전 안내 -->
			<div class="md-bot__panel md-bot__panel--safety" data-md-bot-screen="safety" hidden>
				<div class="md-bot__safety-head">
					<div class="md-bot__safety-icon" aria-hidden="true">⚠️</div>
					<h3 class="md-bot__safety-title"><?php echo esc_html( md_content( 'bot_safety_title', '내원 상담 시 꼭 알려주세요' ) ); ?></h3>
					<p class="md-bot__safety-lead"><?php echo esc_html( md_content( 'bot_safety_lead', '아래 항목이 안전한 진료를 위해 확인이 필요합니다.' ) ); ?></p>
				</div>
				<ul class="md-bot__safety-list" data-md-bot-safety-list role="list">
					<!-- JS 동적 생성 -->
				</ul>
				<button type="button" class="md-btn md-btn-primary md-btn--lg md-bot__safety-continue" data-md-bot-safety-continue>
					<?php echo esc_html( md_content( 'bot_safety_continue', '결과 보기 →' ) ); ?>
				</button>
			</div>

			<!-- 3) Result -->
			<div class="md-bot__panel md-bot__panel--result" data-md-bot-screen="result" hidden>
				<div class="md-bot__result-head">
					<div class="md-bot__result-icon" aria-hidden="true">✨</div>
					<h3 class="md-bot__result-title"><?php echo esc_html( $res_title ); ?></h3>
					<p class="md-bot__result-lead" data-md-bot-result-lead><?php echo esc_html( $res_lead ); ?></p>
				</div>

				<div class="md-bot__result-list" data-md-bot-results role="list">
					<!-- JS 동적 생성 -->
				</div>

				<div class="md-bot__result-cta">
					<a href="#reservation-ctas" class="md-btn md-btn-primary md-btn--lg" data-track="cta-bot-result-book"><?php echo esc_html( $res_book ); ?></a>
					<button type="button" class="md-btn md-btn-ghost md-btn--lg" data-md-bot-restart><?php echo esc_html( $res_restart ); ?></button>
				</div>

				<p class="md-bot__disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
			</div>

		</div>

		<?php /* v3.38.8 · 자가진단 aside CTA 제거 (하단 통일 CTA 배너와 중복) */ ?>

		</div><!-- /.md-dentalbot__layout -->
	</div>
</section>
