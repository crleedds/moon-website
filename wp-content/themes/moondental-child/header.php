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
	<meta name="color-scheme" content="light only">
	<meta name="supported-color-schemes" content="light">
	<meta name="theme-color" content="#FFFAF4">
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
						// 페이지가 항상 라이트 테마(color-scheme: light only)이므로 컬러 로고 하나만 사용
						$theme_logo = '';
						foreach ( array( 'logo-wide-noreg.png', 'logo-wide.png' ) as $cand ) {
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
							<a class="md-header__hours" href="<?php echo esc_url( home_url( '/오시는-길/' ) ); ?>#hours" data-track="cta-header-hours" aria-label="오시는 길과 진료시간 보기">
								<?php echo esc_html( $hours_wd ); ?>
							</a>
						<?php endif; ?>
					</div>

					<?php
					$header_cta_url   = function_exists( 'md_content' ) ? md_content( 'header_cta_url',   '/상담예약/' ) : '/상담예약/';
					$header_cta_label = function_exists( 'md_content' ) ? md_content( 'header_cta_label', '📅 상담 예약하기' ) : '📅 상담 예약하기';
					// 사이트 내 상대경로(/...)는 home_url로 절대화, 그 외(https?://...)는 그대로 사용
					$header_cta_href  = ( $header_cta_url && $header_cta_url[0] === '/' ) ? home_url( $header_cta_url ) : $header_cta_url;

					// 스크롤에 따라 라벨·색이 바뀌는 변형 변수 (라벨 | 배경 | 글자 | 그림자 RGBA)
					$cta_cycle_default = "✨ 편리한 상담 | #5C8B82 | #FFFFFF | 92,139,130\n"
						. "🦷 내 구강상태 진단받기 | #E37B5C | #FFFFFF | 227,123,92\n"
						. "💬 지금 카톡 상담 | #FEE500 | #181600 | 254,229,0\n"
						. "📅 상담 예약하기 | #D88062 | #FFFFFF | 216,128,98";
					$cta_cycle_raw = function_exists( 'md_content' )
						? md_content( 'header_cta_cycle', $cta_cycle_default )
						: $cta_cycle_default;

					$cta_variants = array();
					foreach ( preg_split( "/\r\n|\r|\n/", trim( (string) $cta_cycle_raw ) ) as $line ) {
						$line = trim( $line );
						if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
						$parts = array_map( 'trim', explode( '|', $line ) );
						if ( count( $parts ) < 2 ) continue;
						$cta_variants[] = array(
							'label'  => $parts[0],
							'bg'     => $parts[1] ?? '',
							'fg'     => $parts[2] ?? '#FFFFFF',
							'shadow' => $parts[3] ?? '',
						);
					}
					$cta_data_attr = $cta_variants ? wp_json_encode( array_values( $cta_variants ) ) : '';
					$first = $cta_variants[0] ?? array( 'label' => $header_cta_label, 'bg' => '', 'fg' => '', 'shadow' => '' );

					$style_bits = array();
					if ( ! empty( $first['bg'] ) )     $style_bits[] = '--cta-bg:' . esc_attr( $first['bg'] );
					if ( ! empty( $first['fg'] ) )     $style_bits[] = '--cta-fg:' . esc_attr( $first['fg'] );
					if ( ! empty( $first['shadow'] ) ) $style_bits[] = '--cta-shadow:' . esc_attr( $first['shadow'] );
					?>
					<div class="md-header__cta">
						<a class="md-btn md-btn-primary md-btn--sm md-header__cta-btn md-header__cta-btn--cycle"
						   href="<?php echo esc_url( $header_cta_href ); ?>"
						   data-track="cta-header-reservation"
						   <?php if ( $cta_data_attr ) : ?>data-md-cta-cycle="<?php echo esc_attr( $cta_data_attr ); ?>"<?php endif; ?>
						   <?php if ( $style_bits ) : ?>style="<?php echo esc_attr( implode( '; ', $style_bits ) ); ?>"<?php endif; ?>>
							<span class="md-header__cta-label" data-md-cta-text><?php echo esc_html( $first['label'] ?: $header_cta_label ); ?></span>
						</a>
					</div>
				</div>

			</div>
		</div>
	</div>
</header>

<main id="md-main" class="md-main" role="main">
