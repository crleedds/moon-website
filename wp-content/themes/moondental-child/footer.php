<?php
/**
 * Footer template — Moon Dental Child
 *
 * @package moondental-child
 */
$info = moondental_get_info();
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
				<p><?php echo esc_html( $info['tagline'] ); ?></p>
				<?php if ( ! empty( $info['phone'] ) ) : ?>
					<p style="color:#fff; font-size:1.25rem; font-weight:700; margin-top:8px;">
						<a href="tel:<?php echo esc_attr( $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] ) ); ?>" style="color:#fff;">
							📞 <?php echo esc_html( $info['phone'] ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<div class="md-footer__col">
				<h4>진료시간</h4>
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
				<h4>진료안내</h4>
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
				<h4>병원안내</h4>
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

				<?php if ( $info['kakao_url'] || $info['naver_place'] || $info['instagram'] || $info['blog_url'] || ! empty( $info['facebook_url'] ) ) : ?>
					<h4 style="margin-top:24px;">SNS · 예약</h4>
					<ul>
						<?php if ( $info['naver_place'] ) : ?><li><a href="<?php echo esc_url( $info['naver_place'] ); ?>" target="_blank" rel="noopener">네이버 예약</a></li><?php endif; ?>
						<?php if ( $info['kakao_url'] ) : ?><li><a href="<?php echo esc_url( $info['kakao_url'] ); ?>" target="_blank" rel="noopener">카카오톡 상담</a></li><?php endif; ?>
						<?php if ( $info['instagram'] ) : ?><li><a href="<?php echo esc_url( $info['instagram'] ); ?>" target="_blank" rel="noopener">인스타그램</a></li><?php endif; ?>
						<?php if ( $info['blog_url'] ) : ?><li><a href="<?php echo esc_url( $info['blog_url'] ); ?>" target="_blank" rel="noopener">네이버 블로그</a></li><?php endif; ?>
						<?php if ( ! empty( $info['facebook_url'] ) ) : ?><li><a href="<?php echo esc_url( $info['facebook_url'] ); ?>" target="_blank" rel="noopener">페이스북</a></li><?php endif; ?>
					</ul>
				<?php endif; ?>
			</div>

		</div>

		<div class="md-footer__bottom">
			<div>
				<?php echo esc_html( $info['name_full'] ); ?>
				<?php if ( $info['rep'] ) : ?> · 이사장 <?php echo esc_html( $info['rep'] ); ?><?php endif; ?>
				<?php if ( ! empty( $info['med_inst_no'] ) ) : ?> · 의료기관번호 <?php echo esc_html( $info['med_inst_no'] ); ?><?php endif; ?>
				<?php if ( ! empty( $info['biz_no'] ) ) : ?> · 사업자번호 <?php echo esc_html( $info['biz_no'] ); ?><?php endif; ?>
				<br>
				<?php if ( $info['address'] ) :
					$place_url = $info['naver_map_url'] ?: ( $info['naver_place'] ?? '' );
					if ( $place_url ) : ?>
						<a href="<?php echo esc_url( $place_url ); ?>" target="_blank" rel="noopener" data-track="cta-footer-address" style="border-bottom:1px dashed rgba(255,255,255,0.25);">
							<?php echo esc_html( $info['address'] ); ?>
						</a>
					<?php else : ?>
						<?php echo esc_html( $info['address'] ); ?>
					<?php endif;
				endif; ?>
			</div>
			<div>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $info['name_short'] ); ?>. All rights reserved.</div>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
