<?php
/**
 * Footer template — Moon Dental Child
 *
 * @package moondental-child
 */
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$place_url  = $info['naver_map_url'] ?: ( $info['naver_place'] ?? '' );

$md_skip_flocation = false;
if ( is_page() ) {
	$_pid  = get_queried_object_id();
	$_tpl  = get_page_template_slug( $_pid );
	$_slug = urldecode( (string) get_post_field( 'post_name', $_pid ) );
	if (
		in_array( $_tpl, array(
			'page-templates/page-location.php',
			'page-templates/page-reservation.php',
		), true )
		|| in_array( $_slug, array( '오시는-길', '오시는길', 'location', '상담예약', 'reservation' ), true )
	) {
		$md_skip_flocation = true;
	}
}

// Customizer helpers
$mc = function ( $k, $d = '' ) { return function_exists( 'md_content' ) ? md_content( $k, $d ) : $d; };

$tagline   = $mc( 'footer_brand_tagline', '' ); // 사용자 요청: 비우면 미표시
$col_hours = $mc( 'footer_col_hours_title', '진료시간' );
$copyright = $mc( 'footer_copyright', 'All rights reserved.' );

$legal_show = $mc( 'footer_legal_show', 'yes' );
?>
</main><?php /* /#md-main */ ?>

<?php if ( ! $md_skip_flocation ) {
	get_template_part( 'template-parts/section-location' );
} ?>

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

				<?php if ( $tagline ) : ?>
					<p class="md-footer__tag"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>

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
							<?php echo md_address_link( $info['address'] ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<?php
				$socials = array();
				if ( ! empty( $info['instagram'] ) ) $socials[] = array( 'href'=>$info['instagram'], 'label'=>'인스타그램', 'cls'=>'md-fsoc--insta', 'svg'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="url(#md-insta-grad)"/><defs><linearGradient id="md-insta-grad" x1="0" y1="0" x2="24" y2="24"><stop offset="0%" stop-color="#F58529"/><stop offset="50%" stop-color="#DD2A7B"/><stop offset="100%" stop-color="#8134AF"/></linearGradient></defs><rect x="6" y="6" width="12" height="12" rx="3.5" fill="none" stroke="#fff" stroke-width="1.6"/><circle cx="12" cy="12" r="2.6" fill="none" stroke="#fff" stroke-width="1.6"/><circle cx="15.6" cy="8.4" r="0.9" fill="#fff"/></svg>' );
				if ( ! empty( $info['blog_url'] ) ) $socials[] = array( 'href'=>$info['blog_url'], 'label'=>'네이버 블로그', 'cls'=>'md-fsoc--blog', 'svg'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#03C75A"/><path d="M8 7.5h3.5c1.4 0 2.3.7 2.3 1.9 0 .8-.5 1.4-1.2 1.6.9.2 1.5.9 1.5 1.9 0 1.4-1 2.1-2.6 2.1H8V7.5zm1.7 3.1h1.5c.7 0 1.1-.3 1.1-.9 0-.5-.4-.8-1.1-.8H9.7v1.7zm0 3.1h1.7c.8 0 1.2-.3 1.2-.9s-.4-.9-1.2-.9H9.7v1.8z" fill="#fff"/></svg>' );
				if ( ! empty( $info['facebook_url'] ) ) $socials[] = array( 'href'=>$info['facebook_url'], 'label'=>'페이스북', 'cls'=>'md-fsoc--fb', 'svg'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#1877F2"/><path d="M14.5 12.7h-1.7V18h-2.2v-5.3H9.4v-2h1.2V9.2c0-1.5 1-2.6 2.5-2.6h1.5v2.1h-1c-.5 0-.8.3-.8.8v1.3h1.8l-.1 2z" fill="#fff"/></svg>' );
				if ( ! empty( $info['youtube_url'] ) ) $socials[] = array( 'href'=>$info['youtube_url'], 'label'=>'유튜브', 'cls'=>'md-fsoc--yt', 'svg'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="24" height="24" rx="6" fill="#FF0000"/><path d="M16.8 8.4c-.1-.5-.5-.9-1-1C14.9 7.2 12 7.2 12 7.2s-2.9 0-3.8.2c-.5.1-.9.5-1 1C7 9.3 7 12 7 12s0 2.7.2 3.6c.1.5.5.9 1 1 .9.2 3.8.2 3.8.2s2.9 0 3.8-.2c.5-.1.9-.5 1-1 .2-.9.2-3.6.2-3.6s0-2.7-.2-3.6zM10.8 13.9V10.1l3.3 1.9-3.3 1.9z" fill="#fff"/></svg>' );
				if ( $socials ) : ?>
					<ul class="md-footer__social" aria-label="문치과 채널">
						<?php foreach ( $socials as $s ) : ?>
							<li>
								<a href="<?php echo esc_url( $s['href'] ); ?>" target="_blank" rel="noopener"
								   class="md-fsoc <?php echo esc_attr( $s['cls'] ); ?>"
								   aria-label="<?php echo esc_attr( $s['label'] ); ?>"
								   title="<?php echo esc_attr( $s['label'] ); ?>">
									<?php echo $s['svg']; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="md-footer__col">
				<h4><?php echo esc_html( $col_hours ); ?></h4>
				<ul>
					<?php
					$hour_rows = array(
						array( '평일',   $info['hours_wd']    ?? '' ),
						array( '목요일', $info['hours_thu']   ?? '' ),
						array( '토요일', $info['hours_sat']   ?? '' ),
						array( '점심',   $info['hours_lunch'] ?? '' ),
					);
					foreach ( $hour_rows as $row ) :
						list( $label, $value ) = $row;
						if ( ! $value ) continue;
						$value_clean = preg_replace( '/^' . preg_quote( $label, '/' ) . '\s*/u', '', $value );
					?>
						<li class="md-footer__hours"><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $value_clean ); ?></span></li>
					<?php endforeach; ?>
					<?php if ( $info['hours_off'] ) : ?>
						<li class="md-footer__hours-off"><?php echo esc_html( $info['hours_off'] ); ?></li>
					<?php endif; ?>
				</ul>
			</div>

			<?php /* 진료안내·병원안내 컬럼은 사용자 요청으로 v3.23.1에서 제거 */ ?>

		</div>

		<?php if ( $legal_show !== 'no' ) :
			// v3.23.2 — 법적 필수 항목만 남김:
			// 의료법 시행규칙 §42 (개설자·종별·대표자·의료기관 고유번호),
			// 개인정보보호법 §31 (개인정보 보호책임자), §30 (개인정보처리방침 링크),
			// 의료법 §45 (비급여 진료비 게시).
			// 사업자등록·이용약관·이메일 무단수집거부는 의료기관 웹사이트 법적 필수가 아니므로 제외.
			$inst_name = $mc( 'footer_legal_inst_name', '한아의료재단 문치과병원' );
			$inst_type = $mc( 'footer_legal_inst_type', '치과병원 (의료법인·병원급)' );
			$rep       = $mc( 'footer_legal_rep',       '대표자: 문은수 이사장' );
			$med_no    = $mc( 'footer_legal_med_no',    '의료기관 고유번호: 34400117' );
			$ad_no     = $mc( 'footer_legal_ad_no',     '' );
			$privacy_o = $mc( 'footer_legal_privacy_officer', '개인정보 보호책임자: 문은수' );
			$extra     = $mc( 'footer_legal_extra',     '' );
		?>
		<aside class="md-footer__legal" aria-label="의료기관 법적 표시">
			<dl class="md-footer__legal-grid">
				<?php
				$rows = array(
					array( '의료기관',     $inst_name ),
					array( '종별',         $inst_type ),
					array( '대표자',       $rep ),
					array( '의료기관번호', $med_no ),
				);
				if ( $ad_no )     $rows[] = array( '광고심의', $ad_no );
				if ( $privacy_o ) $rows[] = array( '개인정보', $privacy_o );

				foreach ( $rows as $r ) :
					if ( empty( $r[1] ) ) continue;
				?>
					<div class="md-footer__legal-row">
						<dt><?php echo esc_html( $r[0] ); ?></dt>
						<dd><?php echo esc_html( $r[1] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>

			<?php if ( $extra ) : ?>
				<p class="md-footer__legal-extra"><?php echo esc_html( $extra ); ?></p>
			<?php endif; ?>

			<?php
			// 정책 링크 (Customizer에서 "라벨|URL" 형식)
			$policy_keys = array(
				'footer_link_privacy' => '개인정보처리방침|/개인정보처리방침/',
				'footer_link_pricing' => '비급여 진료비|/비용-안내/',
			);
			$policy_items = array();
			foreach ( $policy_keys as $k => $d ) {
				$raw = trim( $mc( $k, $d ) );
				if ( ! $raw ) continue;
				$parts = explode( '|', $raw, 2 );
				$label = trim( $parts[0] );
				$url   = isset( $parts[1] ) ? trim( $parts[1] ) : '';
				if ( ! $label ) continue;
				if ( $url && $url[0] === '/' ) $url = home_url( $url );
				$policy_items[] = array( 'label' => $label, 'url' => $url );
			}
			if ( $policy_items ) : ?>
				<ul class="md-footer__policy-links">
					<?php foreach ( $policy_items as $p ) : ?>
						<li>
							<?php if ( $p['url'] ) : ?>
								<a href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['label'] ); ?></a>
							<?php else : ?>
								<span><?php echo esc_html( $p['label'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</aside>
		<?php endif; ?>

		<div class="md-footer__bottom">
			<div><?php echo esc_html( $info['name_full'] ); ?></div>
			<div>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( $info['name_short'] ); ?>. <?php echo esc_html( $copyright ); ?></div>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
