<?php
/**
 * Section: 홈 핵심 FAQ 6개
 *
 * 환자가 예약 전 가장 자주 묻는 6가지 — 의사결정 직전 의문 해소.
 *
 * @package moondental-child
 */
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );

$faqs = array(
	array(
		'q' => '당일 예약도 가능한가요?',
		'a' => '네, 가능합니다. 다만 예약 상황에 따라 대기 시간이 발생할 수 있으니 가급적 전화(<a href="tel:' . esc_attr( $phone_link ) . '">' . esc_html( $info['phone'] ) . '</a>) 또는 카카오톡으로 먼저 확인 후 방문해주시기 바랍니다.',
	),
	array(
		'q' => '주차는 어떻게 하나요?',
		'a' => '본원 지하 기계식 주차장을 무료로 이용하실 수 있습니다. 기계식이 어려운 SUV는 인근 <strong>신부 제5공영주차장</strong>(동남구 먹거리1길 10)에 주차 후 방문해주시면 무료 주차 등록을 도와드립니다.',
	),
	array(
		'q' => '전신질환(고혈압·당뇨·심장)이 있어도 진료 가능한가요?',
		'a' => '안심하셔도 됩니다. 혈압기·당검사·심전도·산소포화도 측정 장비를 상시 보유하고 있으며, 복용 중인 약물(혈전용해제·골다공증 약 등)을 사전에 체크해 안전하게 진료합니다.',
	),
	array(
		'q' => '치료 비용은 미리 알 수 있나요?',
		'a' => '비급여 진료(임플란트·교정·심미)는 정밀 진단 후 옵션별 비용·기간을 문서로 안내드립니다. 시작 후 추가 비용은 발생하지 않습니다. <a href="' . esc_url( home_url( '/비용-안내/' ) ) . '">비용 안내 페이지</a>에서 표준 가격대를 미리 확인하실 수 있습니다.',
	),
	array(
		'q' => '임플란트 건강보험이 적용되나요?',
		'a' => '만 <strong>65세 이상</strong> 건강보험 가입자는 평생 <strong>2개까지</strong> 본인부담 30%로 적용됩니다. 부분 무치악이 대상이며, 잔존치 하나만 있어도 가능합니다.',
	),
	array(
		'q' => '예약 변경 · 취소는 어떻게 하나요?',
		'a' => '네이버 예약은 예약 페이지에서 직접 변경·취소가 가능하고, 그 외에는 전화 또는 카카오톡 채널로 문의해주세요. 예약일 전날까지 변경·취소가 가능합니다.',
	),
);
?>

<section class="md-section md-section--surface" id="faq-home" aria-label="자주 묻는 질문">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">FAQ</span>
			<h2 class="md-section-head__title">예약 전 자주 묻는 질문</h2>
			<p class="md-section-head__lead">
				환자분들이 가장 많이 궁금해하시는 6가지 — 미리 확인하세요.
			</p>
		</header>

		<div class="md-home-faq">
			<?php foreach ( $faqs as $idx => $faq ) : ?>
				<details class="md-home-faq__item"<?php echo $idx === 0 ? ' open' : ''; ?>>
					<summary>
						<span class="md-home-faq__q"><?php echo esc_html( $faq['q'] ); ?></span>
						<span class="md-home-faq__chev" aria-hidden="true">+</span>
					</summary>
					<div class="md-home-faq__a"><?php echo wp_kses_post( $faq['a'] ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>

		<div style="text-align:center; margin-top: clamp(24px, 3vw, 32px);">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">
				전체 FAQ 보기 →
			</a>
		</div>
	</div>
</section>
