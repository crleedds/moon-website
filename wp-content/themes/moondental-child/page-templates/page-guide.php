<?php
/**
 * Template: 종합안내서 (Guide)
 *
 * v3.44.184 · /guide/{slug}/ 라우팅 · 안전한 데이터 접근
 *
 * @package moondental-child
 */
get_header();

$data = isset( $GLOBALS['md_guide_data'] ) ? $GLOBALS['md_guide_data'] : null;

// 폴백: 글로벌이 비었으면 URL 에서 직접 로드 (필터 순서 이슈 대응)
if ( ! $data && function_exists( 'md_guide_load' ) ) {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? rawurldecode( $_SERVER['REQUEST_URI'] ) : '';
	if ( preg_match( '#/(guide|가이드)/([^/]+)/?$#u', $uri, $mm ) ) {
		$data = md_guide_load( $mm[2] );
	}
}

echo "\n<!-- md-guide-template v3.44.184 · data=" . ( $data ? esc_html( $data['slug'] ?? '?' ) : 'null' ) . " -->\n";

if ( ! $data ) {
	echo '<div class="md-container" style="padding:80px 20px;text-align:center;"><h1>종합안내서를 찾을 수 없습니다</h1><p>요청하신 안내서를 불러올 수 없습니다.</p><p><a href="' . esc_url( home_url( '/' ) ) . '">홈으로 돌아가기</a></p></div>';
	get_footer();
	return;
}

// 각 필드 기본값
$data = array_merge( array(
	'slug' => '', 'code' => '', 'icon' => '📖', 'eyebrow' => '',
	'center' => '', 'title' => '종합안내서', 'subtitle' => '',
	'reading' => '', 'updated' => '', 'tags' => array(),
	'toc' => array(), 'sections' => array(), 'related' => array(),
	'cta_page' => '', 'cta_label' => '',
), $data );
?>

<article class="md-guide" itemscope itemtype="https://schema.org/Article">

	<!-- 히어로 -->
	<section class="md-guide__hero">
		<div class="md-container">
			<div class="md-guide__hero-inner">
				<div class="md-guide__hero-code" aria-hidden="true">
					<span class="md-guide__hero-icon"><?php echo esc_html( $data['icon'] ); ?></span>
					<span class="md-guide__hero-pill"><?php echo esc_html( $data['code'] ); ?></span>
				</div>
				<?php if ( ! empty( $data['eyebrow'] ) ) : ?>
					<div class="md-guide__hero-eyebrow"><?php echo esc_html( $data['eyebrow'] ); ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $data['center'] ) ) : ?>
					<h1 class="md-guide__hero-center" itemprop="headline"><?php echo esc_html( $data['center'] ); ?></h1>
					<p class="md-guide__hero-title"><?php echo esc_html( $data['title'] ); ?></p>
				<?php else : ?>
					<h1 class="md-guide__hero-title" itemprop="headline"><?php echo esc_html( $data['title'] ); ?></h1>
				<?php endif; ?>
				<?php if ( ! empty( $data['subtitle'] ) ) : ?>
					<p class="md-guide__hero-sub"><?php echo esc_html( $data['subtitle'] ); ?></p>
				<?php endif; ?>
				<div class="md-guide__hero-meta">
					<?php if ( ! empty( $data['reading'] ) ) : ?>
						<span>⏱ 읽는 시간 <?php echo esc_html( $data['reading'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $data['updated'] ) ) : ?>
						<span>📅 <?php echo esc_html( $data['updated'] ); ?> 업데이트</span>
					<?php endif; ?>
					<span>🏥 <?php echo esc_html( function_exists( 'moondental_get_info' ) ? moondental_get_info( 'name_short' ) : '문치과병원' ); ?></span>
				</div>
				<?php if ( ! empty( $data['tags'] ) ) : ?>
					<div class="md-guide__hero-tags">
						<?php foreach ( $data['tags'] as $t ) : ?>
							<span class="md-guide__tag"><?php echo esc_html( $t ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="md-guide__body">
		<div class="md-container">
			<div class="md-guide__layout">

				<!-- 목차 (모바일: 상단 접힘 / 데스크탑: sticky 사이드바) -->
				<aside class="md-guide__toc" aria-label="목차">
					<details class="md-guide__toc-collapse" open>
						<summary class="md-guide__toc-title">📖 목차</summary>
						<ol class="md-guide__toc-list">
							<?php foreach ( $data['toc'] as $t ) : ?>
								<li><a href="#<?php echo esc_attr( $t['id'] ); ?>"><?php echo esc_html( $t['label'] ); ?></a></li>
							<?php endforeach; ?>
						</ol>
					</details>
				</aside>

				<!-- 본문 -->
				<main class="md-guide__main" id="md-guide-main">
					<?php foreach ( $data['sections'] as $sec ) : ?>
						<section class="md-guide__section" id="<?php echo esc_attr( $sec['id'] ); ?>">
							<h2 class="md-guide__section-title"><?php echo esc_html( $sec['title'] ); ?></h2>
							<div class="md-guide__section-body">
								<?php echo wp_kses_post( $sec['body'] ); ?>
							</div>
						</section>
					<?php endforeach; ?>

					<!-- CTA -->
					<section class="md-guide__cta">
						<div class="md-guide__cta-inner">
							<?php $_cta_head = $data['center'] ?: $data['title']; ?>
							<h3>천안·아산 <?php echo esc_html( $_cta_head ); ?> 상담이 필요하신가요?</h3>
							<p>30여년 임상 경험과 다학제 협진으로 <strong>1:1 충분한 사전 상담</strong>부터 시작합니다.</p>
							<div class="md-guide__cta-btns">
								<a class="md-btn md-btn-primary" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>">📅 상담 예약하기</a>
								<?php if ( ! empty( $data['cta_page'] ) ) : ?>
									<a class="md-btn md-btn-secondary" href="<?php echo esc_url( home_url( $data['cta_page'] ) ); ?>"><?php echo esc_html( $data['cta_label'] ?: '진료 페이지 보기' ); ?> →</a>
								<?php endif; ?>
							</div>
						</div>
					</section>

					<!-- 관련 안내서 -->
					<?php if ( ! empty( $data['related'] ) ) : ?>
						<section class="md-guide__related">
							<h3>📚 함께 보면 좋은 안내서</h3>
							<div class="md-guide__related-grid">
								<?php foreach ( $data['related'] as $r ) : ?>
									<a class="md-guide__related-card" href="<?php echo esc_url( home_url( $r['href'] ) ); ?>">
										<span class="md-guide__related-icon" aria-hidden="true"><?php echo esc_html( $r['icon'] ); ?></span>
										<span class="md-guide__related-label"><?php echo esc_html( $r['label'] ); ?></span>
										<span class="md-guide__related-arrow" aria-hidden="true">→</span>
									</a>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>

				</main>
			</div>
		</div>
	</div>

</article>

<?php
get_footer();
