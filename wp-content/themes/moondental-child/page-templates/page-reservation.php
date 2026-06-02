<?php
/**
 * Template Name: 상담예약 페이지
 * Template Post Type: page
 *
 * 흐름:
 *  Hero → 구강 자가진단 봇 → 3 예약 CTA → 오시는 길 (section-location 재사용) → 예약 FAQ
 *
 *  주의: footer.php가 이 템플릿을 감지하여 푸터 위 오시는 길 섹션을 중복 출력하지 않음.
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--reservation">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>상담 예약</span>
		</nav>
		<span class="md-page-hero__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'res_hero_eyebrow', 'RESERVATION' ) : 'RESERVATION' ); ?></span>
		<h1 class="md-page-hero__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'res_hero_title', '상담 예약 & 오시는 길' ) : '상담 예약 & 오시는 길' ); ?></h1>
		<p class="md-page-hero__lead">
			<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'res_hero_lead', "내 증상을 빠르게 자가진단하고 가장 적합한 진료과를 추천받으세요.\n전화 · 카카오톡 · 네이버 예약으로 편하게 예약하실 수 있습니다." ) : "내 증상을 빠르게 자가진단하고 가장 적합한 진료과를 추천받으세요.\n전화 · 카카오톡 · 네이버 예약으로 편하게 예약하실 수 있습니다." ) ); ?>
		</p>
	</div>
</section>

<!-- ============ 1. 구강 자가진단 봇 ============ -->
<?php get_template_part( 'template-parts/section-dental-bot' ); ?>

<!-- ============ 2. 예약 CTA — 전화 / 카카오톡 / 네이버 ============ -->
<section class="md-section md-section--sm" id="reservation-ctas">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'res_channels_eyebrow', 'BOOK NOW' ) : 'BOOK NOW' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'res_channels_title', '편하신 방법으로 예약해주세요' ) : '편하신 방법으로 예약해주세요' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo wp_kses_post( function_exists( 'md_content' ) ? md_content( 'res_channels_lead', '<strong>네이버 예약</strong>은 24시간 자동, <strong>전화·카카오톡</strong>은 진료시간 내 빠르게 응답해드립니다.' ) : '<strong>네이버 예약</strong>은 24시간 자동, <strong>전화·카카오톡</strong>은 진료시간 내 빠르게 응답해드립니다.' ); ?>
			</p>
		</header>

		<?php echo md_render_reservation_ctas( array(
			'track' => 'cta-reservation-page',
			'size'  => 'lg',
			'align' => 'center',
		) ); ?>

		<p class="md-channel-grid__hint" style="text-align:center; margin-top:18px;">
			🕐 진료시간: 평일(월·화·수·금) 09:00–20:30 · 목 09:00–18:00 · 토 09:00–14:00 · 일/공휴일 휴진
		</p>
	</div>
</section>

<!-- ============ 3. 오시는 길 (모든 페이지 푸터 위와 동일 컴포넌트 재사용) ============ -->
<?php get_template_part( 'template-parts/section-location' ); ?>

<!-- ============ 3b. 각 지역에서 문치과병원까지 (28개 지역 SEO 그리드) ============ -->
<?php if ( function_exists( 'moondental_get_regions_by_province' ) ) : ?>
<section class="md-section md-section--sm" aria-label="지역별 오시는 길">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">🌐 지역별 오시는 길</span>
			<h2 class="md-section-head__title">각 지역에서 문치과병원까지</h2>
			<p class="md-section-head__lead">
				충남·충북·세종·대전·경기 중부권 28개 지역별 상세 교통 안내.<br>
				지역명을 클릭하시면 해당 지역에서 천안 만남로까지의 상세 경로와 진료 안내를 보실 수 있습니다.
			</p>
		</header>

		<?php foreach ( moondental_get_regions_by_province() as $prov => $list ) :
			if ( empty( $list ) ) continue;
			$prov_emoji = array(
				'충남' => '🌊', '충북' => '🏔️', '세종' => '🏛️',
				'대전' => '🏙️', '경기' => '🌆',
			);
			$emoji = $prov_emoji[ $prov ] ?? '📍'; ?>
			<div class="md-region-province">
				<h3 class="md-region-province__title">
					<span aria-hidden="true"><?php echo esc_html( $emoji ); ?></span>
					<?php echo esc_html( $prov ); ?>
					<small>(<?php echo count( $list ); ?>개 지역)</small>
				</h3>
				<div class="md-region-grid">
					<?php foreach ( $list as $r ) : ?>
						<a class="md-region-pill" href="<?php echo esc_url( home_url( '/오시는-길/' . $r['slug'] . '/' ) ); ?>" data-track="cta-region-<?php echo esc_attr( $r['slug'] ); ?>">
							<span class="md-region-pill__icon" aria-hidden="true">🚗</span>
							<span class="md-region-pill__name"><?php echo esc_html( $r['name'] ); ?></span>
							<span class="md-region-pill__time"><?php echo esc_html( $r['duration_min'] ); ?>분</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<p class="md-region-note">
			ⓘ 이동 시간은 자동차 기준 대략적인 값입니다. 실제 교통 상황에 따라 달라질 수 있습니다.
		</p>
	</div>
</section>
<?php endif; ?>

<!-- ============ 4. 예약 FAQ ============ -->
<section class="md-section" id="reservation-faq">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">FAQ</span>
			<h2 class="md-section-head__title">예약 관련 자주 묻는 질문</h2>
		</header>

		<div class="md-faq">
			<details class="md-faq__item" open>
				<summary>당일 예약도 가능한가요?</summary>
				<p>네, 당일 예약도 가능합니다. 다만 예약 상황에 따라 대기 시간이 발생할 수 있으니 가급적 전화(<?php echo md_phone_link(); ?>)로 먼저 확인 후 방문해주시기 바랍니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>예약 변경이나 취소는 어떻게 하나요?</summary>
				<p>네이버 예약은 예약 페이지에서 직접 변경·취소가 가능하며, 그 외에는 전화 또는 카카오톡 채널로 문의해주세요. 예약일 전날까지 변경·취소가 가능합니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>초진 시 준비물이 있나요?</summary>
				<p>신분증(또는 건강보험증)을 지참해주세요. 복용 중인 약이 있다면 약 정보를 함께 알려주시면 진료에 도움이 됩니다. 타원 X-ray 파일(USB·이메일)이 있으면 진단 시간이 단축됩니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>전신질환(고혈압·당뇨·심장질환)이 있어도 진료 가능한가요?</summary>
				<p>네, 안심하셔도 됩니다. 문치과병원은 혈압기·당검사·심전도·산소포화도 측정 장비를 상시 보유하고 있으며, 복용 중인 약(혈전용해제·골다공증 주사 등)을 사전에 체크해 안전하게 진료합니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>비용·견적은 미리 알 수 있나요?</summary>
				<p>임플란트·교정·심미치료 등 비급여 진료는 환자분의 구강 상태(CT·X-ray)를 보고 정확한 견적을 산정합니다. 초진 상담 시 옵션별 비용·기간을 모두 안내드리며, 시작 전에 충분히 검토하실 수 있도록 합니다.</p>
			</details>

			<details class="md-faq__item">
				<summary>자가진단 결과만 보고 와도 되나요?</summary>
				<p>자가진단은 참고용으로 도움이 됩니다. 하지만 정확한 진단·치료 계획은 의료진의 직접 진료(시진·X-ray·구강검사)가 필요합니다. 자가진단 결과를 보여주시면 상담이 더 빨라집니다.</p>
			</details>
		</div>
	</div>
</section>

<?php
get_footer();
