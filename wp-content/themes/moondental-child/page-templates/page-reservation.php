<?php
/**
 * Template Name: 상담예약 페이지
 * Template Post Type: page
 *
 * 3-step 위자드 폼 + 우측 사이드바 + FAQ.
 *
 * @package moondental-child
 */

get_header();
$info     = moondental_get_info();
$services = moondental_reservation_services();

// 시간 옵션 (요일별 JS에서 필터)
$time_slots = array();
for ( $h = 9; $h <= 20; $h++ ) {
	$time_slots[] = sprintf( '%02d:00', $h );
	$time_slots[] = sprintf( '%02d:30', $h );
}
?>

<section class="md-page-hero md-page-hero--reservation">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>상담예약</span>
		</nav>
		<span class="md-page-hero__eyebrow">RESERVATION</span>
		<h1 class="md-page-hero__title">편리한 예약 · 상담</h1>
		<p class="md-page-hero__lead">
			온라인 · 전화 · 카카오톡 · 네이버 예약 — 편하신 방법으로 신청해주세요.<br>
			양식 작성 후 담당자가 확인 후 빠른 시간 내에 연락드립니다.
		</p>
	</div>
</section>

<section class="md-section">
	<div class="md-container">
		<div class="md-reservation">

			<!-- ============ 왼쪽: 위자드 폼 ============ -->
			<div class="md-reservation__main">
				<form class="md-reservation-form" id="md-reservation-form" novalidate>

					<!-- Step indicator -->
					<ol class="md-steps" aria-label="진행 단계">
						<li class="md-step is-active" data-step="1"><span class="md-step__no">1</span><span class="md-step__label">진료항목</span></li>
						<li class="md-step" data-step="2"><span class="md-step__no">2</span><span class="md-step__label">날짜·시간</span></li>
						<li class="md-step" data-step="3"><span class="md-step__no">3</span><span class="md-step__label">연락처</span></li>
					</ol>

					<!-- ── Step 1: 진료항목 ── -->
					<fieldset class="md-step-panel is-active" data-panel="1">
						<legend class="md-step-panel__title">어떤 진료를 원하시나요?</legend>
						<p class="md-step-panel__desc">진료 항목을 선택해주세요. 정확하지 않으셔도 괜찮습니다 — 상담 시 함께 정해드립니다.</p>

						<div class="md-service-pick">
							<?php foreach ( $services as $svc ) : ?>
								<label class="md-service-pick__item">
									<input type="radio" name="service" value="<?php echo esc_attr( $svc['value'] ); ?>" required>
									<span class="md-service-pick__title"><?php echo esc_html( $svc['title'] ); ?></span>
									<span class="md-service-pick__desc"><?php echo esc_html( $svc['desc'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>

						<div class="md-step-nav">
							<span></span>
							<button type="button" class="md-btn md-btn-primary md-step-next">다음 단계 →</button>
						</div>
					</fieldset>

					<!-- ── Step 2: 날짜·시간 ── -->
					<fieldset class="md-step-panel" data-panel="2">
						<legend class="md-step-panel__title">원하시는 방문 일정을 선택해주세요</legend>
						<p class="md-step-panel__desc">예약 시간은 담당자 확인 후 조정될 수 있습니다. 일·공휴일은 휴진입니다.</p>

						<div class="md-field">
							<label class="md-field__label" for="md-res-date">희망 날짜 <span class="md-required">*</span></label>
							<input class="md-field__input" type="date" id="md-res-date" name="date"
								min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
								max="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+90 days' ) ) ); ?>" required>
							<small class="md-field__hint" id="md-res-date-hint">선택하신 요일에 따라 가능한 시간이 표시됩니다.</small>
						</div>

						<div class="md-field">
							<label class="md-field__label">희망 시간 <span class="md-required">*</span></label>
							<div class="md-time-grid" id="md-res-time-grid" role="radiogroup" aria-label="희망 시간">
								<?php foreach ( $time_slots as $t ) : ?>
									<label class="md-time-grid__item" data-time="<?php echo esc_attr( $t ); ?>">
										<input type="radio" name="time" value="<?php echo esc_attr( $t ); ?>" required>
										<span><?php echo esc_html( $t ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<small class="md-field__hint">평일 09:00–20:30, 목요일 ~18:00, 토요일 ~14:00.</small>
						</div>

						<div class="md-step-nav">
							<button type="button" class="md-btn md-btn-ghost md-step-prev">← 이전</button>
							<button type="button" class="md-btn md-btn-primary md-step-next">다음 단계 →</button>
						</div>
					</fieldset>

					<!-- ── Step 3: 연락처 ── -->
					<fieldset class="md-step-panel" data-panel="3">
						<legend class="md-step-panel__title">확인 후 연락드릴 수 있도록 정보를 입력해주세요</legend>
						<p class="md-step-panel__desc">담당자가 확인 후 <strong>빠른 시간 내에 연락</strong>드립니다.</p>

						<div class="md-field">
							<label class="md-field__label" for="md-res-name">성함 <span class="md-required">*</span></label>
							<input class="md-field__input" type="text" id="md-res-name" name="name" required autocomplete="name">
						</div>

						<div class="md-field">
							<label class="md-field__label" for="md-res-phone">연락처 <span class="md-required">*</span></label>
							<input class="md-field__input" type="tel" id="md-res-phone" name="phone" placeholder="010-0000-0000" required autocomplete="tel">
						</div>

						<div class="md-field">
							<label class="md-field__label" for="md-res-note">증상 또는 문의사항 <span class="md-optional">(선택)</span></label>
							<textarea class="md-field__input md-field__textarea" id="md-res-note" name="note" rows="4" placeholder="아픈 부위·치료 받고 싶은 부분·궁금한 점을 자유롭게 적어주세요."></textarea>
						</div>

						<div class="md-agree">
							<label class="md-agree__item">
								<input type="checkbox" name="agree_privacy" value="1" required>
								<span><a href="<?php echo esc_url( home_url( '/개인정보처리방침/' ) ); ?>" target="_blank" rel="noopener">개인정보 처리방침</a>에 동의합니다. <span class="md-required">*</span></span>
							</label>
							<label class="md-agree__item">
								<input type="checkbox" name="agree_marketing" value="1">
								<span>마케팅 정보 수신에 동의합니다. <span class="md-optional">(선택)</span></span>
							</label>
						</div>

						<div class="md-step-nav">
							<button type="button" class="md-btn md-btn-ghost md-step-prev">← 이전</button>
							<button type="submit" class="md-btn md-btn-primary md-btn--lg" id="md-res-submit">예약 신청</button>
						</div>
					</fieldset>

					<!-- 결과 영역 -->
					<div class="md-res-result" id="md-res-result" hidden></div>
				</form>
			</div>

			<!-- ============ 오른쪽: 사이드바 (전화/카톡/네이버/시간/주소) ============ -->
			<aside class="md-reservation__side" aria-label="다른 예약 방법">

				<div class="md-side-card md-side-card--accent">
					<h3 class="md-side-card__title">365일 예약 가능</h3>
					<p class="md-side-card__lead">온라인 · 전화 · 카카오톡 · 네이버 예약 — 가장 편하신 방법으로.</p>
				</div>

				<div class="md-side-card">
					<h4 class="md-side-card__h">📞 전화 문의</h4>
					<p class="md-side-card__big">
						<a href="tel:<?php echo esc_attr( $info['phone_link'] ); ?>"><?php echo esc_html( $info['phone'] ); ?></a>
					</p>
					<p class="md-side-card__sub">진료시간 내 응답 가능</p>
				</div>

				<?php if ( ! empty( $info['kakao_url'] ) ) : ?>
				<div class="md-side-card">
					<h4 class="md-side-card__h">💬 카카오톡 상담</h4>
					<a class="md-btn md-btn-primary md-btn--block" href="<?php echo esc_url( $info['kakao_url'] ); ?>" target="_blank" rel="noopener">카카오톡 채널 열기</a>
					<p class="md-side-card__sub">24시간 문의 가능 (응답은 진료시간 내)</p>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $info['naver_place'] ) ) : ?>
				<div class="md-side-card">
					<h4 class="md-side-card__h">🟢 네이버 예약</h4>
					<a class="md-btn md-btn-secondary md-btn--block" href="<?php echo esc_url( $info['naver_place'] ); ?>" target="_blank" rel="noopener">네이버에서 예약하기</a>
					<p class="md-side-card__sub">24시간 자동 예약 가능</p>
				</div>
				<?php endif; ?>

				<div class="md-side-card">
					<h4 class="md-side-card__h">🕐 진료시간</h4>
					<ul class="md-side-card__hours">
						<li><strong>평일</strong><span>09:00 – 20:30</span></li>
						<li><strong>목요일</strong><span>09:00 – 18:00</span></li>
						<li><strong>토요일</strong><span>09:00 – 14:00</span></li>
						<li class="md-side-card__off">일요일·공휴일 휴진</li>
					</ul>
					<p class="md-side-card__sub">평일 점심시간 없이 진료 (야간진료 운영)</p>
				</div>

				<div class="md-side-card">
					<h4 class="md-side-card__h">📍 오시는 길</h4>
					<p class="md-side-card__addr"><?php echo esc_html( $info['address'] ); ?></p>
					<a class="md-btn md-btn-ghost md-btn--block md-btn--sm" href="<?php echo esc_url( home_url( '/오시는-길/' ) ); ?>">위치·교통 자세히 보기 →</a>
				</div>
			</aside>

		</div>
	</div>
</section>

<!-- ============ FAQ ============ -->
<section class="md-section md-section--surface" id="reservation-faq">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">FAQ</span>
			<h2 class="md-section-head__title">예약 관련 자주 묻는 질문</h2>
		</header>

		<div class="md-faq">
			<details class="md-faq__item" open>
				<summary>당일 예약도 가능한가요?</summary>
				<p>네, 당일 예약도 가능합니다. 다만 예약 상황에 따라 대기 시간이 발생할 수 있으니 가급적 전화(<a href="tel:<?php echo esc_attr( $info['phone_link'] ); ?>"><?php echo esc_html( $info['phone'] ); ?></a>)로 먼저 확인 후 방문해주시기 바랍니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>예약 변경이나 취소는 어떻게 하나요?</summary>
				<p>전화 또는 카카오톡 채널로 문의해주세요. 예약일 전날까지 변경·취소가 가능합니다. 무단 미방문이 반복되면 다음 예약에 제한이 있을 수 있으니 양해 부탁드립니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>초진 시 준비물이 있나요?</summary>
				<p>신분증(또는 건강보험증)을 지참해주세요. 복용 중인 약이 있다면 약 정보를 함께 알려주시면 진료에 도움이 됩니다. 타원 X-ray 파일(USB·이메일)이 있으면 가져오시면 진단 시간이 단축됩니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>주차가 가능한가요?</summary>
				<p>본원 지하 기계식 주차장을 무료로 이용하실 수 있습니다. 기계식 주차가 어려운 SUV 차량은 인근 <strong>신부 제5공영주차장</strong>(동남구 먹거리1길 10)에 주차하고 방문해주시면 무료 주차 등록을 도와드립니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>전신질환(고혈압·당뇨·심장질환)이 있어도 진료 가능한가요?</summary>
				<p>네, 안심하셔도 됩니다. 문치과병원은 혈압기·당검사·심전도·산소포화도 측정 장비를 상시 보유하고 있으며, 복용 중인 약(혈전용해제·골다공증 주사 등)을 사전에 체크해 안전하게 진료합니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>예약 신청 후 어떻게 진행되나요?</summary>
				<p>입력하신 연락처로 담당자가 영업시간 내 연락드려 진료 가능 시간을 확정합니다. 보통 30분~2시간 이내 회신드리며, 야간·휴일에 신청하신 경우 다음 영업일 오전에 연락드립니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>비용·견적은 미리 알 수 있나요?</summary>
				<p>임플란트·교정·심미치료 등 비급여 진료는 환자분의 구강 상태(CT·X-ray)를 보고 정확한 견적을 산정합니다. 초진 상담 시 옵션별 비용·기간을 모두 안내드리며, 시작 전에 충분히 검토하실 수 있도록 합니다.</p>
			</details>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
