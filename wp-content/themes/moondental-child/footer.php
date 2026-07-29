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

				<?php
				$footer_email = $info['email'] ?: 'moondental1995@naver.com';
				if ( $footer_email ) : ?>
					<p class="md-footer__email">
						<a href="mailto:<?php echo esc_attr( $footer_email ); ?>" data-track="cta-footer-email">
							✉️ <?php echo esc_html( $footer_email ); ?>
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
						array( $mc( 'footer_hour_wd_label',    '평일' ),   $info['hours_wd']    ?? '' ),
						array( $mc( 'footer_hour_thu_label',   '목요일' ), $info['hours_thu']   ?? '' ),
						array( $mc( 'footer_hour_sat_label',   '토요일' ), $info['hours_sat']   ?? '' ),
						array( $mc( 'footer_hour_lunch_label', '점심' ),   $info['hours_lunch'] ?? '' ),
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

			<?php
			// v3.44.10 · 이용안내 정책 링크 수집만 · 표시는 아래 법적 표시 라인에서
			$policy_col_items = array();
			$policy_col_keys = array(
				'footer_link_privacy' => '개인정보취급방침|/개인정보처리방침/',
				'footer_link_terms'   => '이용약관|/이용약관/',
				'footer_link_email'   => '이메일 무단수집거부|/이메일-무단수집거부/',
			);
			foreach ( $policy_col_keys as $k => $d ) {
				$raw = trim( $mc( $k, $d ) );
				if ( ! $raw ) continue;
				$parts = explode( '|', $raw, 2 );
				$label = trim( $parts[0] );
				$url   = isset( $parts[1] ) ? trim( $parts[1] ) : '';
				if ( ! $label ) continue;
				if ( $url && $url[0] === '/' ) $url = home_url( $url );
				$policy_col_items[] = array( 'label' => $label, 'url' => $url );
			}

			$col_rep_raw = trim( (string) $mc( 'footer_legal_rep', '문은수 이사장' ) );
			foreach ( array( '대표자:', '대표자' ) as $p ) {
				if ( stripos( $col_rep_raw, $p ) === 0 ) {
					$col_rep_raw = ltrim( substr( $col_rep_raw, strlen( $p ) ), " :·" );
					break;
				}
			}
			if ( ! $col_rep_raw ) $col_rep_raw = '문은수 이사장';
			?>

		</div>

		<?php
		// v3.30.0 — 법적 표시 라인 (저작권 바 위) · 대표자·개업일·요양기관번호
		$legal_open_date = $mc( 'footer_legal_open_date', '1995.04' );
		$legal_med_no    = $mc( 'footer_legal_med_no',    '34400117' );
		$legal_ad_no     = $mc( 'footer_legal_ad_no',     '' );
		// v3.28.1 fallback + prefix strip
		foreach ( array( '요양기관번호:', '요양기관번호', '의료기관 고유번호:', '의료기관 고유번호' ) as $p ) {
			if ( stripos( $legal_med_no, $p ) === 0 ) {
				$legal_med_no = ltrim( substr( $legal_med_no, strlen( $p ) ), " :·" );
				break;
			}
		}
		if ( ! $legal_med_no )    $legal_med_no    = '34400117';
		if ( ! $legal_open_date ) $legal_open_date = '1995.04';

		$legal_items = array();
		if ( $col_rep_raw )    $legal_items[] = esc_html( $mc( 'footer_prefix_rep',  '대표자: ' ) ) . esc_html( $col_rep_raw );
		if ( $legal_open_date ) $legal_items[] = esc_html( $mc( 'footer_prefix_open', '개업일: ' ) ) . esc_html( $legal_open_date );
		if ( $legal_med_no )    $legal_items[] = esc_html( $mc( 'footer_prefix_med',  '요양기관번호: ' ) ) . esc_html( $legal_med_no );
		if ( $legal_ad_no )     $legal_items[] = esc_html( $mc( 'footer_prefix_ad',   '광고심의: ' ) ) . esc_html( $legal_ad_no );
		?>
		<?php
		// v3.28.4 · v3.30.6 — 법적 표시 + 저작권 · 단일 문단으로 통합
		$copyright_tpl = $mc( 'footer_copyright_bar', 'Copyright {year} {name}  All Rights Reserved.' );
		$copyright_text = strtr( $copyright_tpl, array(
			'{year}' => date_i18n( 'Y' ),
			'{name}' => $mc( 'footer_name_token', '한아의료재단 문치과병원' ),
		) );
		?>
		<?php
		// v3.44.10 · 정책 링크 (개인정보처리방침 · 이용약관 · 이메일무단수집거부) 를 법적 표시 라인에 통합
		$policy_line_items = array();
		foreach ( $policy_col_items as $p ) {
			if ( $p['url'] ) {
				$policy_line_items[] = '<a href="' . esc_url( $p['url'] ) . '">' . esc_html( $p['label'] ) . '</a>';
			} else {
				$policy_line_items[] = esc_html( $p['label'] );
			}
		}
		?>
		<?php if ( $policy_line_items || $legal_items || $copyright_text ) : ?>
			<div class="md-footer__legal-line">
				<?php if ( $policy_line_items ) : ?>
					<p class="md-footer__policy-line">
						<?php echo implode( ' <span class="md-footer__sep">|</span> ', $policy_line_items ); ?>
					</p>
				<?php endif; ?>
				<p>
					<?php if ( $legal_items ) : ?>
						<?php echo implode( ' <span class="md-footer__sep">|</span> ', $legal_items ); ?>
					<?php endif; ?>
					<?php if ( $copyright_text ) : ?>
						<br><small><?php echo esc_html( $copyright_text ); ?></small>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
