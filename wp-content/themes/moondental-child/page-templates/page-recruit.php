<?php
/**
 * Template Name: 상시채용 (치과위생사 채용 안내)
 * Template Post Type: page
 *
 * /상시채용/ 페이지 — 진료실·상담실 치과위생사 선생님 채용.
 * v3.32.3 · 모든 텍스트를 Customizer에서 편집 가능하게 이관.
 *
 * @package moondental-child
 */

get_header();
$info = moondental_get_info();

$hr_email = function_exists( 'md_content' ) ? md_content( 'recruit_hr_email', '' ) : '';
$show_email = $hr_email ?: ( $info['email'] ?: 'moondental1995@naver.com' );

/* 파서 · 파이프 구분 */
$parse_pair = function( $text ) {
	$out = array();
	foreach ( md_parse_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) >= 2 ) {
			$out[] = array( 'title' => $parts[0], 'body' => $parts[1] );
		}
	}
	return $out;
};

$benefits_items    = $parse_pair( md_content( 'recruit_benefits_items', '' ) );
$why_cards         = $parse_pair( md_content( 'recruit_why_cards', '' ) );
$apply_bullets     = md_parse_lines( md_content( 'recruit_apply_bullets', '' ) );
$apply_flow_steps  = $parse_pair( md_content( 'recruit_apply_flow_steps', '' ) );
$cond_hours_list   = md_parse_lines( md_content( 'recruit_cond_hours_list', '' ) );
$cond_leg_list     = md_parse_lines( md_content( 'recruit_cond_leg_list', '' ) );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--recruit">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php echo esc_html( get_the_title() ?: '상시채용' ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow"><?php echo esc_html( md_content( 'recruit_hero_eyebrow', '' ) ); ?></span>
		<h1 class="md-page-hero__title">
			<?php echo esc_html( md_content( 'recruit_hero_title_a', '오래 함께 갈 동료를 찾습니다' ) ); ?><br>
			<em><?php echo esc_html( md_content( 'recruit_hero_title_b', '진료실 · 상담실 치과위생사 선생님' ) ); ?></em>
		</h1>
		<p class="md-page-hero__lead">
			<?php echo nl2br( esc_html( md_content( 'recruit_hero_lead',
				"천안 만남로 1995년 개원 30여년.\n20년 넘게 근무하고 계신 선생님들도 많은 병원입니다.\n짧게 스쳐가는 자리가 아니라, 길게 보고 함께할 분을 모십니다." ) ) ); ?>
		</p>
		<div class="md-btn-group md-page-hero__actions">
			<a class="md-btn md-btn-primary md-btn--lg" href="mailto:<?php echo esc_attr( $show_email ); ?>?subject=<?php echo rawurlencode( '문치과병원 채용 지원' ); ?>" data-track="cta-recruit-email-hero">
				<?php echo esc_html( md_content( 'recruit_hero_email_btn', '📧 이메일로 지원하기' ) ); ?>
				<span class="md-btn__sub"><?php echo esc_html( $show_email ); ?></span>
			</a>
		</div>
	</div>
</section>

<!-- ============ 모집 대상 · 공통 근무 조건 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'recruit_target_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'recruit_target_title', '' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo esc_html( md_content( 'recruit_target_lead',
					'신입·경력 모두 환영합니다. 부서(진료실·상담실)는 지원 후 상담을 통해 정합니다.' ) ); ?>
			</p>
		</header>

		<article class="md-recruit-card md-recruit-card--common">
			<div class="md-recruit-card__head">
				<span class="md-recruit-card__badge md-recruit-card__badge--common"><?php echo esc_html( md_content( 'recruit_cond_badge', '' ) ); ?></span>
				<h3><?php echo esc_html( md_content( 'recruit_cond_title', '' ) ); ?></h3>
				<p class="md-recruit-card__lead"><?php echo esc_html( md_content( 'recruit_cond_lead', '' ) ); ?></p>
			</div>
			<div class="md-recruit-grid">
				<div class="md-recruit-block">
					<h4><?php echo esc_html( md_content( 'recruit_cond_hours_title', '' ) ); ?></h4>
					<ul>
						<?php foreach ( $cond_hours_list as $item ) : ?>
							<li><?php echo wp_kses_post( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="md-recruit-block">
					<h4><?php echo esc_html( md_content( 'recruit_cond_leg_title', '' ) ); ?></h4>
					<ul>
						<?php foreach ( $cond_leg_list as $item ) : ?>
							<li><?php echo wp_kses_post( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</article>

		<!-- 복리후생 -->
		<?php if ( $benefits_items ) : ?>
		<article class="md-recruit-card md-u-mt-cards">
			<div class="md-recruit-card__head">
				<span class="md-recruit-card__badge"><?php echo esc_html( md_content( 'recruit_benefits_badge', '' ) ); ?></span>
				<h3><?php echo esc_html( md_content( 'recruit_benefits_title', '' ) ); ?></h3>
				<p class="md-recruit-card__lead"><?php echo esc_html( md_content( 'recruit_benefits_lead', '' ) ); ?></p>
			</div>

			<div class="md-recruit-benefits">
				<?php foreach ( $benefits_items as $b ) : ?>
					<div class="md-recruit-benefit">
						<h4><?php echo esc_html( $b['title'] ); ?></h4>
						<ul>
							<?php
							/* 항목 문자열을 " · " 로 분리해 <li>로 */
							$items = preg_split( '/\s*·\s*/u', $b['body'] );
							foreach ( $items as $it ) :
								$it = trim( $it );
								if ( $it === '' ) continue; ?>
								<li><?php echo wp_kses_post( $it ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>

			<?php $hint = md_content( 'recruit_benefits_hint', '' ); if ( $hint ) : ?>
				<p class="md-recruit-card__hint"><?php echo esc_html( $hint ); ?></p>
			<?php endif; ?>
		</article>
		<?php endif; ?>
	</div>
</section>

<!-- ============ WHY MOON DENTAL ============ -->
<?php if ( $why_cards ) : ?>
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'recruit_why_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'recruit_why_title', '' ) ); ?></h2>
		</header>

		<div class="md-preservation-grid">
			<?php foreach ( $why_cards as $w ) : ?>
			<article class="md-preservation-card">
				<h3><?php echo esc_html( $w['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $w['body'] ); ?></p>
			</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ============ 지원 방법 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'recruit_apply_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'recruit_apply_title', '' ) ); ?></h2>
		</header>

		<article class="md-apply-card">
			<div class="md-apply-card__body">
				<h3 class="md-apply-card__title"><?php echo esc_html( md_content( 'recruit_apply_card_title', '' ) ); ?></h3>
				<?php if ( $apply_bullets ) : ?>
				<ul class="md-apply-card__list">
					<?php foreach ( $apply_bullets as $b ) : ?>
						<li><?php echo wp_kses_post( $b ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
				<a class="md-apply-card__mail" href="mailto:<?php echo esc_attr( $show_email ); ?>?subject=<?php echo rawurlencode( '문치과병원 채용 지원' ); ?>">
					<span class="md-apply-card__mail-ic" aria-hidden="true">📧</span>
					<span class="md-apply-card__mail-txt">
						<span class="md-apply-card__mail-label"><?php echo esc_html( md_content( 'recruit_apply_btn_label', '이력서 보내기' ) ); ?></span>
						<span class="md-apply-card__mail-addr"><?php echo esc_html( $show_email ); ?></span>
					</span>
				</a>
			</div>

			<?php if ( $apply_flow_steps ) : ?>
			<div class="md-apply-card__flow" aria-label="<?php echo esc_attr( md_content( 'recruit_apply_flow_title', '지원 후 진행 과정' ) ); ?>">
				<h4 class="md-apply-card__flow-title"><?php echo esc_html( md_content( 'recruit_apply_flow_title', '' ) ); ?></h4>
				<ol class="md-apply-flow">
					<?php $i = 1; foreach ( $apply_flow_steps as $step ) : ?>
						<li>
							<span class="md-apply-flow__num"><?php echo (int) $i; ?></span>
							<div>
								<strong><?php echo esc_html( $step['title'] ); ?></strong>
								<?php if ( $step['body'] ) : ?><p><?php echo esc_html( $step['body'] ); ?></p><?php endif; ?>
							</div>
						</li>
					<?php $i++; endforeach; ?>
				</ol>
			</div>
			<?php endif; ?>
		</article>
	</div>
</section>

<!-- ============ CTA ============ -->
<section class="md-section md-section--sm">
	<div class="md-container md-container--narrow">
		<div class="md-region-cta">
			<?php $chip = md_content( 'recruit_page_cta_chip', '' ); if ( $chip ) : ?>
				<span class="md-region-cta__chip"><?php echo esc_html( $chip ); ?></span>
			<?php endif; ?>
			<h2 class="md-region-cta__title"><?php echo nl2br( esc_html( md_content( 'recruit_page_cta_title', '' ) ) ); ?></h2>
			<p class="md-region-cta__lead"><?php echo esc_html( md_content( 'recruit_page_cta_lead', '' ) ); ?></p>
			<div class="md-btn-group md-btn-group--center md-rcta">
				<a class="md-btn md-btn-primary md-btn--lg" href="mailto:<?php echo esc_attr( $show_email ); ?>?subject=<?php echo rawurlencode( '문치과병원 채용 지원' ); ?>" data-track="cta-recruit-final-email">
					📧 <?php echo esc_html( $show_email ); ?>
				</a>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
