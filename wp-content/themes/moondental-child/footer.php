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
				<h3><?php echo esc_html( $info['name_full'] ); ?></h3>
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
					<?php if ( $info['hours_wd'] ) : ?>
						<li class="md-footer__hours"><strong>평일</strong><span><?php echo esc_html( preg_replace( '/^평일\s*/u', '', $info['hours_wd'] ) ); ?></span></li>
					<?php endif; ?>
					<?php if ( $info['hours_sat'] ) : ?>
						<li class="md-footer__hours"><strong>토요일</strong><span><?php echo esc_html( preg_replace( '/^토요일\s*/u', '', $info['hours_sat'] ) ); ?></span></li>
					<?php endif; ?>
					<?php if ( $info['hours_lunch'] ) : ?>
						<li class="md-footer__hours"><strong>점심</strong><span><?php echo esc_html( preg_replace( '/^점심\s*/u', '', $info['hours_lunch'] ) ); ?></span></li>
					<?php endif; ?>
					<?php if ( $info['hours_off'] ) : ?>
						<li style="margin-top:8px; color:rgba(255,255,255,0.55);"><?php echo esc_html( $info['hours_off'] ); ?></li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="md-footer__col">
				<h4>진료안내</h4>
				<ul>
					<?php foreach ( moondental_get_services() as $svc ) :
						$svc_url = get_permalink( get_page_by_path( $svc['slug'] ) );
						?>
						<li><a href="<?php echo esc_url( $svc_url ?: '#' ); ?>"><?php echo esc_html( $svc['title'] ); ?></a></li>
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

				<?php if ( $info['kakao_url'] || $info['naver_place'] || $info['instagram'] || $info['blog_url'] ) : ?>
					<h4 style="margin-top:24px;">SNS</h4>
					<ul>
						<?php if ( $info['kakao_url'] ) : ?><li><a href="<?php echo esc_url( $info['kakao_url'] ); ?>" target="_blank" rel="noopener">카카오톡 채널</a></li><?php endif; ?>
						<?php if ( $info['naver_place'] ) : ?><li><a href="<?php echo esc_url( $info['naver_place'] ); ?>" target="_blank" rel="noopener">네이버 플레이스</a></li><?php endif; ?>
						<?php if ( $info['instagram'] ) : ?><li><a href="<?php echo esc_url( $info['instagram'] ); ?>" target="_blank" rel="noopener">인스타그램</a></li><?php endif; ?>
						<?php if ( $info['blog_url'] ) : ?><li><a href="<?php echo esc_url( $info['blog_url'] ); ?>" target="_blank" rel="noopener">블로그</a></li><?php endif; ?>
					</ul>
				<?php endif; ?>
			</div>

		</div>

		<div class="md-footer__bottom">
			<div>
				<?php echo esc_html( $info['name_full'] ); ?>
				<?php if ( $info['rep'] ) : ?> · 대표원장 <?php echo esc_html( $info['rep'] ); ?><?php endif; ?>
				<?php if ( $info['biz_no'] ) : ?> · 사업자번호 <?php echo esc_html( $info['biz_no'] ); ?><?php endif; ?>
				<br>
				<?php if ( $info['address'] ) : ?><?php echo esc_html( $info['address'] ); ?><?php endif; ?>
			</div>
			<div>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $info['name_short'] ); ?>. All rights reserved.</div>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
