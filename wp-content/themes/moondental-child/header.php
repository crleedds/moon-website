<?php
/**
 * Header template — Moon Dental Child
 *
 * 단일 행 헤더: 로고 / 메뉴 / 전화·시간 정보 / 오시는길 CTA.
 * sticky로 스크롤 시에도 계속 따라옴.
 *
 * @package moondental-child
 */
$phone      = moondental_get_info( 'phone' );
$phone_link = moondental_get_info( 'phone_link' );
$hours_wd   = function_exists( 'moondental_get_today_hours_label' )
	? moondental_get_today_hours_label()
	: moondental_get_info( 'hours_wd' );
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
	<div class="md-header">
		<div class="md-container">
			<div class="md-header__inner">

				<a class="md-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( moondental_get_info( 'name_full' ) ); ?>">
					<?php if ( has_custom_logo() ) : ?>
						<?php the_custom_logo(); ?>
					<?php else :
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

				<div class="md-header__aside">
					<div class="md-header__info" aria-label="진료시간 및 전화">
						<?php if ( $phone ) : ?>
							<a class="md-header__phone" href="tel:<?php echo esc_attr( $phone_link ?: preg_replace( '/[^0-9]/', '', $phone ) ); ?>" data-track="cta-header-call">
								<span class="md-header__phone-icon" aria-hidden="true">📞</span>
								<span class="md-header__phone-num"><?php echo esc_html( $phone ); ?></span>
							</a>
						<?php endif; ?>
						<?php if ( $hours_wd ) : ?>
							<span class="md-header__hours"><?php echo esc_html( $hours_wd ); ?></span>
						<?php endif; ?>
					</div>

					<?php
					$header_cta_url   = function_exists( 'md_content' ) ? md_content( 'header_cta_url',   '/오시는-길/' ) : '/오시는-길/';
					$header_cta_label = function_exists( 'md_content' ) ? md_content( 'header_cta_label', '오시는 길' ) : '오시는 길';
					$header_cta_icon  = function_exists( 'md_content' ) ? md_content( 'header_cta_icon',  '📍' ) : '📍';
					// 사이트 내 상대경로(/...)는 home_url로 절대화, 그 외(https?://...)는 그대로 사용
					$header_cta_href  = ( $header_cta_url && $header_cta_url[0] === '/' ) ? home_url( $header_cta_url ) : $header_cta_url;
					?>
					<div class="md-header__cta">
						<a class="md-btn md-btn-primary md-btn--sm md-header__cta-btn"
						   href="<?php echo esc_url( $header_cta_href ); ?>"
						   data-track="cta-header-location">
							<?php if ( $header_cta_icon ) : ?><span aria-hidden="true"><?php echo esc_html( $header_cta_icon ); ?></span><?php endif; ?>
							<?php echo esc_html( $header_cta_label ); ?>
						</a>
					</div>
				</div>

			</div>
		</div>
	</div>
</header>

<main id="md-main" class="md-main" role="main">
