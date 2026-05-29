<?php
/**
 * Footer template — Moon Dental Child
 *
 * @package moondental-child
 */
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$place_url  = $info['naver_map_url'] ?: ( $info['naver_place'] ?? '' );
?>
</main><?php /* /#md-main */ ?>

<footer class="md-footer" role="contentinfo">
	<div class="md-container">

		<div class="md-footer__grid">

			<div class="md-footer__brand">
				<?php
				$footer_logo_path = MOONDENTAL_DIR . '/assets/images/logo/logo-wide-white.png';
				if ( file_exists( $footer_logo_path ) ) :
				?>
					<a class="md-footer__brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $info['name_full'] ); ?>">
						<img class="md-footer__brand-img" src="<?php echo esc_url( MOONDENTAL_URI . '/assets/images/logo/logo-wide-white.png' ); ?>" alt="<?php echo esc_attr( $info['name_full'] ); ?>">
					</a>
				<?php else : ?>
					<h3><?php echo esc_html( $info['name_full'] ); ?></h3>
				<?php endif; ?>
				<p><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'footer_brand_tagline', $info['tagline'] ) : $info['tagline'] ); ?></p>

				<?php if ( ! empty( $info['phone'] ) ) : ?>
					<p class="md-footer__phone">
						<a href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-footer-call">
							📞 <?php echo esc_html( $info['phone'] ); ?>
						</a>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $info['address'] ) ) : ?>
					<p class="md-footer__addr">
						<?php if ( $place_url ) : ?>
							<a href="<?php echo esc_url( $place_url ); ?>" target="_blank" rel="noopener" data-track="cta-footer-address">
								<?php echo esc_html( $info['address'] ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( $info['address'] ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<?php
				/* ── 소셜·예약 채널 가로 아이콘 행 ── */
				$socials = array();
				if ( ! empty( $info['naver_place'] ) ) {
					$socials[] = array(
						'href'  => $info['naver_place'],
						'label' => '네이버 예약',
						'track' => 'cta-footer-naver-book',
						'cls'   => 'md-fsoc--naver',
						'svg'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#03C75A"/><path d="M9 8h2.2l3.6 5.1V8H17v8h-2.2l-3.6-5.1V16H9V8z" fill="#fff"/></svg>',
					);
				}
				if ( ! empty( $info['kakao_url'] ) ) {
					$socials[] = array(
						'href'  => $info['kakao_url'],
						'label' => '카카오톡 상담',
						'track' => 'cta-footer-kakao',
						'cls'   => 'md-fsoc--kakao',
						'svg'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#FEE500"/><path d="M12 6.4c-3.7 0-6.7 2.4-6.7 5.4 0 1.9 1.3 3.6 3.2 4.5l-.7 2.6c-.06.23.18.4.38.27l3.05-2c.25.03.51.04.78.04 3.7 0 6.7-2.4 6.7-5.4S15.7 6.4 12 6.4z" fill="#3C1E1E"/></svg>',
					);
				}
				$socials[] = array(
					'href'  => 'tel:' . esc_attr( $phone_link ),
					'label' => '전화 상담',
					'track' => 'cta-footer-call-icon',
					'cls'   => 'md-fsoc--phone',
					'target'=> '',
					'svg'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#D88062"/><path d="M8.6 9.4c.6 1.6 1.9 2.9 3.5 3.5l1.2-1.2c.2-.2.4-.2.7-.2 1 .3 2.1.5 3.2.5.4 0 .8.4.8.8v1.9c0 .4-.4.8-.8.8C9.4 15.5 6.5 12.6 6.5 6.8c0-.4.4-.8.8-.8h1.9c.4 0 .8.4.8.8 0 1.1.2 2.2.5 3.2.1.2 0 .5-.2.7L8.6 9.4z" fill="#fff"/></svg>',
				);
				if ( ! empty( $info['instagram'] ) ) {
					$socials[] = array(
						'href'  => $info['instagram'],
						'label' => '인스타그램',
						'track' => 'cta-footer-instagram',
						'cls'   => 'md-fsoc--insta',
						'svg'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="url(#md-insta-grad)"/><defs><linearGradient id="md-insta-grad" x1="0" y1="0" x2="24" y2="24"><stop offset="0%" stop-color="#F58529"/><stop offset="50%" stop-color="#DD2A7B"/><stop offset="100%" stop-color="#8134AF"/></linearGradient></defs><rect x="6" y="6" width="12" height="12" rx="3.5" fill="none" stroke="#fff" stroke-width="1.6"/><circle cx="12" cy="12" r="2.6" fill="none" stroke="#fff" stroke-width="1.6"/><circle cx="15.6" cy="8.4" r="0.9" fill="#fff"/></svg>',
					);
				}
				if ( ! empty( $info['blog_url'] ) ) {
					$socials[] = array(
						'href'  => $info['blog_url'],
						'label' => '네이버 블로그',
						'track' => 'cta-footer-blog',
						'cls'   => 'md-fsoc--blog',
						'svg'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#03C75A"/><path d="M8 7.5h3.5c1.4 0 2.3.7 2.3 1.9 0 .8-.5 1.4-1.2 1.6.9.2 1.5.9 1.5 1.9 0 1.4-1 2.1-2.6 2.1H8V7.5zm1.7 3.1h1.5c.7 0 1.1-.3 1.1-.9 0-.5-.4-.8-1.1-.8H9.7v1.7zm0 3.1h1.7c.8 0 1.2-.3 1.2-.9s-.4-.9-1.2-.9H9.7v1.8z" fill="#fff"/></svg>',
					);
				}
				if ( ! empty( $info['facebook_url'] ) ) {
					$socials[] = array(
						'href'  => $info['facebook_url'],
						'label' => '페이스북',
						'track' => 'cta-footer-facebook',
						'cls'   => 'md-fsoc--fb',
						'svg'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#1877F2"/><path d="M14.5 12.7h-1.7V18h-2.2v-5.3H9.4v-2h1.2V9.2c0-1.5 1-2.6 2.5-2.6h1.5v2.1h-1c-.5 0-.8.3-.8.8v1.3h1.8l-.1 2z" fill="#fff"/></svg>',
					);
				}
				if ( ! empty( $socials ) ) :
				?>
					<ul class="md-footer__social" aria-label="문치과 채널">
						<?php foreach ( $socials as $s ) : ?>
							<li>
								<a href="<?php echo esc_url( $s['href'] ); ?>"
								   <?php echo ( isset( $s['target'] ) && $s['target'] === '' ) ? '' : 'target="_blank" rel="noopener"'; ?>
								   data-track="<?php echo esc_attr( $s['track'] ); ?>"
								   class="md-fsoc <?php echo esc_attr( $s['cls'] ); ?>"
								   aria-label="<?php echo esc_attr( $s['label'] ); ?>"
								   title="<?php echo esc_attr( $s['label'] ); ?>">
									<?php echo $s['svg']; // safe — hardcoded SVG ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="md-footer__col">
				<h4><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'footer_col_hours_title', '진료시간' ) : '진료시간' ); ?></h4>
				<ul>
					<?php
					$hour_rows = array(
						array( '평일',    $info['hours_wd']    ?? '' ),
						array( '목요일',  $info['hours_thu']   ?? '' ),
						array( '토요일',  $info['hours_sat']   ?? '' ),
						array( '점심',    $info['hours_lunch'] ?? '' ),
					);
					foreach ( $hour_rows as $row ) :
						list( $label, $value ) = $row;
						if ( ! $value ) continue;
						$value_clean = preg_replace( '/^' . preg_quote( $label, '/' ) . '\s*/u', '', $value );
					?>
						<li class="md-footer__hours"><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $value_clean ); ?></span></li>
					<?php endforeach; ?>
					<?php if ( $info['hours_off'] ) : ?>
						<li style="margin-top:8px; color:rgba(255,255,255,0.55);"><?php echo esc_html( $info['hours_off'] ); ?></li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="md-footer__col">
				<h4><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'footer_col_services_title', '진료안내' ) : '진료안내' ); ?></h4>
				<ul>
					<?php foreach ( moondental_get_services() as $svc ) :
						$page    = get_page_by_path( $svc['slug'] );
						$svc_url = $page ? get_permalink( $page ) : home_url( '/' . rawurlencode( $svc['slug'] ) . '/' );
						?>
						<li><a href="<?php echo esc_url( $svc_url ); ?>"><?php echo esc_html( $svc['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="md-footer__col">
				<h4><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'footer_col_about_title', '병원안내' ) : '병원안내' ); ?></h4>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => '',
						'depth'          => 1,
						'fallback_cb'    => 'moondental_footer_menu_fallback',
					) );
				} else {
					moondental_footer_menu_fallback();
				}
				?>
			</div>

		</div>

		<div class="md-footer__bottom">
			<div><?php echo esc_html( $info['name_full'] ); ?></div>
			<div>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $info['name_short'] ); ?>. <?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'footer_copyright', 'All rights reserved.' ) : 'All rights reserved.' ); ?></div>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
