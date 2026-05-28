<?php
/**
 * Header template — Moon Dental Child
 *
 * Astra 헤더를 완전히 대체. 유틸리티 바 + 메인 헤더(로고/메뉴/CTA) 구조.
 *
 * @package moondental-child
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="md-sr-only" href="#md-main">본문 바로가기</a>

<header class="md-site-header" role="banner">

	<?php /* ── 1. Utility bar ─────────────────────────────────────── */ ?>
	<div class="md-utilbar">
		<div class="md-container">
			<div class="md-utilbar__inner">
				<div class="md-utilbar__left">
					<?php
					$phone      = moondental_get_info( 'phone' );
					$phone_link = moondental_get_info( 'phone_link' );
					if ( $phone ) :
					?>
						<a class="md-utilbar__phone" href="tel:<?php echo esc_attr( $phone_link ?: preg_replace( '/[^0-9]/', '', $phone ) ); ?>">
							📞 <?php echo esc_html( $phone ); ?>
						</a>
					<?php endif; ?>

					<span class="md-utilbar__hours">
						<?php echo esc_html( moondental_get_info( 'hours_wd' ) ); ?>
					</span>
				</div>

				<?php /* utilbar 우측 외부 채널 링크 — 헤더 CTA·FAB 스택과 중복되어 제거 */ ?>
			</div>
		</div>
	</div>

	<?php /* ── 2. Main header ─────────────────────────────────────── */ ?>
	<div class="md-header">
		<div class="md-container">
			<div class="md-header__inner">

				<a class="md-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( moondental_get_info( 'name_full' ) ); ?>">
					<?php if ( has_custom_logo() ) : ?>
						<?php the_custom_logo(); ?>
					<?php else :
						// 등록번호 없는 가로형이 있으면 그것 우선, 없으면 등록번호 포함 가로형
						$logo_candidates = array( 'logo-wide-noreg.png', 'logo-wide.png' );
						$theme_logo = '';
						foreach ( $logo_candidates as $cand ) {
							if ( file_exists( MOONDENTAL_DIR . '/assets/images/logo/' . $cand ) ) {
								$theme_logo = MOONDENTAL_URI . '/assets/images/logo/' . $cand;
								break;
							}
						}
						if ( $theme_logo ) : ?>
							<img class="md-header__brand-img" src="<?php echo esc_url( $theme_logo ); ?>"
								 alt="<?php echo esc_attr( moondental_get_info( 'name_full' ) ); ?>">
						<?php else : ?>
							<span class="md-header__brand-name">
								<?php echo esc_html( moondental_get_info( 'name_short' ) ); ?>
							</span>
							<span class="md-header__brand-en">
								<?php echo esc_html( moondental_get_info( 'name_en' ) ); ?>
							</span>
						<?php endif; ?>
					<?php endif; ?>
				</a>

				<button class="md-header__nav-toggle" aria-expanded="false" aria-controls="md-primary-menu" aria-label="메뉴 열기">
					<span></span><span></span><span></span>
				</button>

				<nav class="md-header__nav" id="md-primary-menu" aria-label="주 메뉴">
					<?php
					wp_nav_menu( array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'md-nav',
						'fallback_cb'    => 'moondental_nav_fallback',
					) );
					?>
				</nav>

				<div class="md-header__cta">
					<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>" data-track="cta-header-reservation">예약·상담</a>
				</div>

			</div>
		</div>
	</div>
</header>

<main id="md-main" class="md-main" role="main">
