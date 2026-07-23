<?php
/**
 * Section: Hero
 *
 * 홈 첫 화면 — 슬로건 · CTA · 핵심 지표
 * v3.34.0: 이미지 placeholder 영역 제거 · 텍스트 중심 · 단일 컬럼 · 강력한 CTA
 *
 * @package moondental-child
 */
$info = moondental_get_info();

// v3.38.5 · md_content 우선, 기존 theme_mod 폴백 (마이그레이션 안전)
$eyebrow = md_content( 'hero_eyebrow', get_theme_mod( 'moondental_hero_eyebrow', '천안 만남로 · 1995년부터 한자리에서' ) );
$title_a = md_content( 'hero_title_a', get_theme_mod( 'moondental_hero_title_a', '천안·아산에서 30여년,' ) );
$title_b = md_content( 'hero_title_b', get_theme_mod( 'moondental_hero_title_b', '환자 한 분의 평생 치아를' ) );
$lead    = md_content( 'hero_lead', get_theme_mod( 'moondental_hero_lead',
	"천안·아산 임플란트·투명교정·라미네이트·자연치아 살리기까지.\n분야별 전문 의료진이 한 자리에서 — 충분히 듣고, 꼭 필요한 치료만 권합니다."
) );

$phone      = $info['phone'] ?? '';
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $phone );
$naver_book = $info['naver_place'] ?? '';

$hero_cta_primary_url   = md_content( 'hero_cta_url',   get_theme_mod( 'moondental_hero_cta_primary_url',   '/상담예약/' ) );
$hero_cta_primary_label = md_content( 'hero_cta_label', get_theme_mod( 'moondental_hero_cta_primary_label', '📅 상담 예약하기' ) );
?>

<section class="md-hero md-hero--centered" aria-label="<?php echo esc_attr( md_content( 'aria_sec_hero', '문치과병원 소개' ) ); ?>">
	<div class="md-container">
		<div class="md-hero__center">

			<span class="md-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>

			<h1 class="md-hero__title">
				<?php echo esc_html( $title_a ); ?><br>
				<em><?php echo esc_html( $title_b ); ?></em>
			</h1>

			<p class="md-hero__lead"><?php echo nl2br( esc_html( $lead ) ); ?></p>

			<?php /* v3.34.5 · 히어로 배지 3개 제거 (사용자 요청 · 미니멀 히어로) · Customizer 필드는 유지되니 언제든 복원 가능 */ ?>

			<div class="md-hero__actions">
				<?php
				$hero_url = $hero_cta_primary_url;
				if ( is_string( $hero_url ) && $hero_url !== '' && $hero_url[0] === '/' ) {
					$hero_url = home_url( $hero_url );
				}
				?>
				<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( $hero_url ); ?>" data-track="cta-hero-primary">
					<?php echo esc_html( $hero_cta_primary_label ); ?>
				</a>
				<?php if ( $naver_book ) : ?>
					<a class="md-btn md-btn-ghost md-btn--lg" href="<?php echo esc_url( $naver_book ); ?>" target="_blank" rel="noopener" data-track="cta-hero-naver">
						<svg class="md-btn__icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
							<path d="M9 8h2.2l3.6 5.1V8H17v8h-2.2l-3.6-5.1V16H9V8z" fill="currentColor"/>
						</svg>
						네이버 예약
					</a>
				<?php endif; ?>
				<?php if ( $phone_link ) : ?>
					<a class="md-btn md-btn-ghost md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-hero-call">
						📞 <?php echo esc_html( $phone ); ?>
					</a>
				<?php endif; ?>
			</div>

		</div>
	</div>
	<div class="md-hero__scroll" aria-hidden="true">
		<span></span>
	</div>
</section>
