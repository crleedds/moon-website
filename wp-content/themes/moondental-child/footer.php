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
			// v3.27.9 — 비디치과의원 스타일 참고 · 중앙 정렬 컴팩트 구조.
			// 정책 링크(위) → 의료 면책 → 저작권 → 사업자 정보 3줄 → 사업자등록증 링크(아래).
			$inst_name = $mc( 'footer_legal_inst_name', '한아의료재단 문치과병원' );
			$inst_type = $mc( 'footer_legal_inst_type', '치과병원 (의료법인·병원급)' );
			$rep       = $mc( 'footer_legal_rep',       '문은수 이사장' );
			$med_no    = $mc( 'footer_legal_med_no',    '34400117' );
			$ad_no     = $mc( 'footer_legal_ad_no',     '' );
			$privacy_o = $mc( 'footer_legal_privacy_officer', '문은수' );
			$biz_no    = $mc( 'footer_legal_biz_no',    '' ); // v3.27.9 사업자등록번호
			$open_date = $mc( 'footer_legal_open_date', '' ); // v3.27.9 개업일 (예: 1995.04)
			$biz_cert_url = $mc( 'footer_legal_biz_cert_url', '' ); // v3.27.9 사업자등록증 파일/링크
			$disclaimer = $mc( 'footer_legal_disclaimer',
				'* 본 홈페이지의 모든 의료 정보는 의료법 및 보건복지부 의료광고 가이드라인을 준수하여 제공하고 있으며, 특정 개인의 결과는 개인에 따라 달라질 수 있습니다.' );
			$copyright_start = $mc( 'footer_legal_copyright_start', '2018' ); // v3.27.9 저작권 시작 연도

			// v3.27.3: 라벨(dt)과 값(dd)에서 접두어 중복 제거를 위한 정리
			$strip_prefix = function( $val, $prefixes ) {
				$val = trim( (string) $val );
				foreach ( $prefixes as $p ) {
					if ( stripos( $val, $p ) === 0 ) {
						$val = ltrim( substr( $val, strlen( $p ) ), " :·" );
						break;
					}
				}
				return $val;
			};
			$rep       = $strip_prefix( $rep,       array( '대표자:', '대표자' ) );
			$med_no    = $strip_prefix( $med_no,    array( '의료기관 고유번호:', '의료기관 고유번호', '의료기관번호:', '의료기관번호' ) );
			$privacy_o = $strip_prefix( $privacy_o, array( '개인정보 보호책임자:', '개인정보 보호책임자', '개인정보:' ) );
			$biz_no    = $strip_prefix( $biz_no,    array( '사업자등록번호:', '사업자등록번호' ) );

			// 정책 링크 (Customizer에서 "라벨|URL" 형식)
			$policy_keys = array(
				'footer_link_privacy' => '개인정보처리방침|/개인정보처리방침/',
				'footer_link_terms'   => '이용약관|/이용약관/',
				'footer_link_pricing' => '비급여 진료비|/비용-안내/',
				'footer_link_sitemap' => '',
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

			$curr_year   = date_i18n( 'Y' );
			$year_range  = ( $copyright_start && $copyright_start !== $curr_year )
				? esc_html( $copyright_start ) . '-' . esc_html( $curr_year )
				: esc_html( $curr_year );

			// 사업자 정보 행 · 파이프로 자연스럽게 연결
			$row1 = array();
			if ( $inst_name ) $row1[] = '상호: <strong>' . esc_html( $inst_name ) . '</strong>';
			if ( $rep )       $row1[] = '대표자: ' . esc_html( $rep );
			if ( $biz_no )    $row1[] = '사업자등록번호: ' . esc_html( $biz_no );

			$row2 = array();
			$addr = $info['address_road'] ?: $info['address'];
			if ( $addr )      $row2[] = '주소: ' . esc_html( $addr );
			if ( $open_date ) $row2[] = '개업일: ' . esc_html( $open_date );

			$row3 = array();
			if ( ! empty( $info['phone'] ) ) {
				$row3[] = '전화: <a href="tel:' . esc_attr( $phone_link ) . '">' . esc_html( $info['phone'] ) . '</a>';
			}
			if ( $med_no )    $row3[] = '의료기관 고유번호: ' . esc_html( $med_no );
			if ( $privacy_o ) $row3[] = '개인정보 보호책임자: ' . esc_html( $privacy_o );
			if ( $ad_no )     $row3[] = '광고심의: ' . esc_html( $ad_no );
		?>
		<aside class="md-footer__legal md-footer__legal--v2" aria-label="의료기관 법적 표시">

			<?php if ( $policy_items ) : ?>
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

			<?php if ( $disclaimer ) : ?>
				<p class="md-footer__disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
			<?php endif; ?>

			<p class="md-footer__copyright">
				&copy; <?php echo $year_range; ?> <?php echo esc_html( $info['name_short'] ?: $inst_name ); ?>. All rights reserved.
			</p>

			<div class="md-footer__biz-info">
				<?php if ( $row1 ) : ?><p><?php echo implode( ' <span class="md-footer__sep">|</span> ', $row1 ); ?></p><?php endif; ?>
				<?php if ( $row2 ) : ?><p><?php echo implode( ' <span class="md-footer__sep">|</span> ', $row2 ); ?></p><?php endif; ?>
				<?php if ( $row3 ) : ?><p><?php echo implode( ' <span class="md-footer__sep">|</span> ', $row3 ); ?></p><?php endif; ?>
				<?php if ( $biz_cert_url ) : ?>
					<p><a class="md-footer__biz-cert" href="<?php echo esc_url( $biz_cert_url ); ?>" target="_blank" rel="noopener">사업자등록증 보기</a></p>
				<?php endif; ?>
			</div>

			<?php $extra = $mc( 'footer_legal_extra', '' ); ?>
			<?php if ( $extra ) : ?>
				<p class="md-footer__legal-extra"><?php echo esc_html( $extra ); ?></p>
			<?php endif; ?>
		</aside>
		<?php endif; ?>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
