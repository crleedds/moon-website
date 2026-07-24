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

<?php /* v3.40.2 · 예약 페이지 히어로(RESERVATION · 상담 예약 & 오시는 길) 제거
 *  자가진단 봇으로 바로 진입하도록 화면 정리 */ ?>

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

		<p class="md-channel-grid__hint md-u-center md-u-mt-18">
			<?php echo esc_html( md_content( 'res_hint', '진료시간: 월·화·수·금 9:00–20:30 · 목 9:00–18:30 · 토 9:00–14:00 · 일/공휴일 휴진' ) ); ?>
		</p>
	</div>
</section>

<!-- ============ 3. 오시는 길 (모든 페이지 푸터 위와 동일 컴포넌트 재사용) ============ -->
<?php get_template_part( 'template-parts/section-location' ); ?>

<!-- ============ 4. 예약 FAQ ============ -->
<?php
$res_faq_items = array();
foreach ( md_parse_lines( md_content( 'res_faq_items', '' ) ) as $line ) {
	$parts = array_map( 'trim', explode( '|', $line, 2 ) );
	if ( count( $parts ) >= 2 ) {
		$res_faq_items[] = array( 'q' => $parts[0], 'a' => $parts[1] );
	}
}
if ( $res_faq_items ) : ?>
<section class="md-section" id="reservation-faq">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'res_faq_eyebrow', 'FAQ' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'res_faq_title', '예약 관련 자주 묻는 질문' ) ); ?></h2>
		</header>

		<div class="md-faq">
			<?php $first = true; foreach ( $res_faq_items as $q ) : ?>
			<details class="md-faq__item"<?php echo $first ? ' open' : ''; ?>>
				<summary><?php echo esc_html( $q['q'] ); ?></summary>
				<p><?php echo wp_kses_post( $q['a'] ); ?></p>
			</details>
			<?php $first = false; endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
