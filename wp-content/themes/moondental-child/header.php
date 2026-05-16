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

				<div class="md-utilbar__right">
					<?php
					$kakao = moondental_get_info( 'kakao_url' );
					$blog  = moondental_get_info( 'blog_url' );
					$insta = moondental_get_info( 'instagram' );
					$place = moondental_get_info( 'naver_place' );
					?>
					<?php if ( $kakao ) : ?><a href="<?php echo esc_url( $kakao ); ?>" target="_blank" rel="noopener">카카오톡 상담</a><?php endif; ?>
					<?php if ( $place ) : ?><a href="<?php echo esc_url( $place ); ?>" target="_blank" rel="noopener">네이버 예약</a><?php endif; ?>
					<?php if ( $blog ) : ?><a href="<?php echo esc_url( $blog ); ?>" target="_blank" rel="noopener">블로그</a><?php endif; ?>
					<?php if ( $insta ) : ?><a href="<?php echo esc_url( $insta ); ?>" target="_blank" rel="noopener">인스타그램</a><?php endif; ?>

					<?php
					if ( has_nav_menu( 'utility' ) ) {
						wp_nav_menu( array(
							'theme_location' => 'utility',
							'container'      => false,
							'menu_class'     => 'md-utilbar__menu',
							'depth'          => 1,
							'fallback_cb'    => '__return_empty_string',
						) );
					}
					?>
				</div>
			</div>
		</div>
	</div>

	<?php /* ── 2. Main header ─────────────────────────────────────── */ ?>
	<div class="md-header">
		<div class="md-container">
			<div class="md-header__inner">

				<a class="md-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php if ( has_custom_logo() ) : ?>
						<?php the_custom_logo(); ?>
					<?php else : ?>
						<span class="md-header__brand-name">
							<?php echo esc_html( moondental_get_info( 'name_short' ) ); ?>
						</span>
						<span class="md-header__brand-en">
							<?php echo esc_html( moondental_get_info( 'name_en' ) ); ?>
						</span>
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
					<?php
					$kakao_cta = moondental_get_info( 'kakao_url' );
					if ( $kakao_cta ) : ?>
						<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( $kakao_cta ); ?>" target="_blank" rel="noopener">예약·상담</a>
					<?php else : ?>
						<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">예약·상담</a>
					<?php endif; ?>
				</div>

			</div>
		</div>
	</div>
</header>

<main id="md-main" class="md-main" role="main">
