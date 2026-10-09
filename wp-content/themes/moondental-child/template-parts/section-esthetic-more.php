<?php
/**
 * v5.1 · 원장 지시 · 예전 /심미치료/ 페이지 내용을 심미치료센터 안으로 (본문 · 고민 · 추천 대상 · FAQ)
 *  page-service.php 의 같은 블록을 slug '심미치료' 로 그린다. /심미치료/ 는 /심미치료센터/ 로 301.
 *
 * @package moondental-child
 */
$slug        = '심미치료';
$md_impl_own = false;
$md_old_page = get_page_by_path( '심미치료' );
$md_old_body = $md_old_page ? trim( (string) $md_old_page->post_content ) : '';
?>
<section class="md-section" id="esthetic-more" aria-label="심미 · 보철 진료 자세히">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">MORE</span>
			<h2 class="md-section-head__title">심미 · 보철 진료 자세히</h2>
		</header>
		<article class="md-page-content">
			<?php
			if ( $md_old_body ) {
				echo apply_filters( 'the_content', $md_old_body );
			} elseif ( function_exists( 'moondental_default_service_content' ) ) {
				echo moondental_default_service_content( $slug ); // 안전한 정적 HTML
			}
			?>
		</article>
	</div>
</section>

<?php
/* === 환자 고민 / 솔루션 6쌍 — bdbddc.com 패턴 참고 ===
 * v3.47 · 임플란트-센터는 제외. 위 "다른 병원에서 어렵다고 하셨나요" 6케이스와
 *   뼈 부족·전신질환·흔들림 3개가 겹쳐 같은 말을 두 번 하게 된다.
 *   해당 페이지에서는 그쪽 한 블록으로 합쳤다. */
if ( function_exists( 'moondental_service_pain_points' ) && ! $md_impl_own ) {
	$pp_map = moondental_service_pain_points();
	if ( isset( $pp_map[ $slug ] ) && ! empty( $pp_map[ $slug ] ) ) :
?>
<section class="md-section md-section--surface" aria-label="환자분 고민·솔루션">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_eyebrow', '환자분의 마음' ) : '환자분의 마음' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_title', '혹시 이런 고민 하고 계시죠?' ) : '혹시 이런 고민 하고 계시죠?' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_lead', '많은 환자분이 같은 걱정을 안고 오십니다. 문치과병원이 어떻게 답해드리는지 확인하세요.' ) : '많은 환자분이 같은 걱정을 안고 오십니다.' ) ); ?>
			</p>
		</header>
		<?php
		$pain_tag_q = function_exists( 'md_content' ) ? md_content( 'service_pain_tag_q', '고민' ) : '고민';
		$pain_tag_a = function_exists( 'md_content' ) ? md_content( 'service_pain_tag_a', '문치과의 답' ) : '문치과의 답';
		?>
		<ul class="md-pain">
			<?php foreach ( $pp_map[ $slug ] as $pp ) : ?>
				<li class="md-pain__pair">
					<div class="md-pain__concern">
						<span class="md-pain__tag"><?php echo esc_html( $pain_tag_q ); ?></span>
						<p>"<?php echo esc_html( $pp['concern'] ); ?>"</p>
					</div>
					<div class="md-pain__solution">
						<span class="md-pain__tag md-pain__tag--alt"><?php echo esc_html( $pain_tag_a ); ?></span>
						<p><?php echo esc_html( $pp['solution'] ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; }
?>

<?php /* 강점 카드 섹션은 /기술력-시설/ 페이지로 이동됨 (v3.12.0) */ ?>

<?php
/* === 이런 분께 추천합니다 === */
if ( function_exists( 'moondental_service_ideal_candidates' ) ) {
	$cand_map = moondental_service_ideal_candidates();
	if ( isset( $cand_map[ $slug ] ) && ! empty( $cand_map[ $slug ] ) ) :
?>
<section class="md-section md-section--surface md-section--sm" aria-label="추천 대상">
	<div class="md-container md-container--narrow">
		<div class="md-ideal">
			<header class="md-ideal__head">
				<span class="md-ideal__chip"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_chip', 'For You' ) : 'For You' ); ?></span>
				<h2 class="md-ideal__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_title', '이런 분께 추천합니다' ) : '이런 분께 추천합니다' ); ?></h2>
				<p class="md-ideal__lead">
					<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_lead', '해당하시는 항목이 있으시면 부담 없이 상담받으세요.' ) : '해당하시는 항목이 있으시면 부담 없이 상담받으세요.' ) ); ?>
				</p>
			</header>
			<ul class="md-ideal__list">
				<?php foreach ( $cand_map[ $slug ] as $item ) : ?>
					<li>
						<span class="md-ideal__check" aria-hidden="true">✓</span>
						<span><?php echo esc_html( $item ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php /* v3.37.2 · 중간 CTA 제거 (하단 CTA 배너에 이미 있음) */ ?>
		</div>
	</div>
</section>
<?php endif; }
?>


<?php
/* 해당 service slug의 자동 FAQ 출력 */
if ( function_exists( 'moondental_get_faqs_by_service' ) ) {
	$faqs_map = moondental_get_faqs_by_service();
	if ( isset( $faqs_map[ $slug ] ) && ! empty( $faqs_map[ $slug ] ) ) :
?>
<section class="md-section md-section--sm" aria-label="자주 묻는 질문">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">FAQ</span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'svc_faq_title', '자주 묻는 질문' ) ); ?></h2>
		</header>
		<div class="md-faq">
			<?php foreach ( $faqs_map[ $slug ] as $i => $item ) : ?>
				<details class="md-faq__item"<?php echo $i === 0 ? ' open' : ''; ?>>
					<summary><?php echo esc_html( $item['q'] ); ?></summary>
					<p><?php echo wp_kses_post( md_autolink_addresses( $item['a'] ) ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; }
?>
