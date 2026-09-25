<?php
/**
 * v4.0 · Moon Dental Care — 직원 전용 · 환자 설명용 자료
 *
 *  직원 전용(/직원/) 허브의 두 번째 도구. 진료실·상담실에서 환자에게 치료를 설명할 때
 *  태블릿·모니터로 띄워 놓고 쓰는 화면이다. 주제별 데이터는 inc/care/data/{slug}.php 에 두며
 *  파일 하나 = 주제 하나. 새 주제는 파일만 추가하면 목록에 자동으로 뜬다.
 *
 *  화면: 허브(주제 카드) → 주제 페이지(한눈에 · 설명 섹션 · 치료 과정 · 비교 · 주의 · FAQ)
 *  설명 모드: 섹션 하나가 화면 하나가 되는 큰 글씨 슬라이드 (← → 키, 터치 스와이프)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** 주제 목록 — data 폴더의 파일을 읽어 'order' 순으로 정렬 */
function md_care_topics() {
	static $topics = null;
	if ( $topics !== null ) return $topics;
	$topics = array();
	foreach ( (array) glob( MOONDENTAL_DIR . '/inc/care/data/*.php' ) as $file ) {
		$d = include $file;
		if ( ! is_array( $d ) || empty( $d['slug'] ) ) continue;
		$topics[ $d['slug'] ] = $d;
	}
	uasort( $topics, function ( $a, $b ) { return ( $a['order'] ?? 99 ) <=> ( $b['order'] ?? 99 ); } );
	return $topics;
}

function md_care_current_topic() {
	$t = isset( $_GET['topic'] ) ? sanitize_key( wp_unslash( $_GET['topic'] ) ) : '';
	$topics = md_care_topics();
	return isset( $topics[ $t ] ) ? $t : '';
}

/** 이 앱 화면에서만 CSS·JS */
function md_care_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) return;
	if ( ! function_exists( 'md_sup_current_app' ) || 'care' !== md_sup_current_app() ) return;
	$dir = get_stylesheet_directory(); $uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/care.css' ) ) {
		wp_enqueue_style( 'moondental-care', $uri . '/assets/css/care.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/care.css' ) );
	}
	if ( file_exists( $dir . '/assets/js/care.js' ) ) {
		wp_enqueue_script( 'moondental-care', $uri . '/assets/js/care.js', array(), filemtime( $dir . '/assets/js/care.js' ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'md_care_enqueue', 31 );

/** 허브 — 주제 카드 */
function md_care_render_hub() {
	$topics = md_care_topics();
	$groups = array();
	foreach ( $topics as $slug => $t ) { $groups[ $t['group'] ?? '기타' ][ $slug ] = $t; }
	?>
	<div class="mdc-hub">
		<div class="mdc-hub__top">
			<p class="mdc-hub__lead">환자분께 치료를 설명할 때 띄워 놓는 화면입니다. 주제를 고르면 한눈에 요약 → 설명 → 과정 → 자주 묻는 질문 순서로 이어지고, <strong>설명 모드</strong>를 켜면 큰 글씨 슬라이드로 바뀝니다.</p>
			<label class="mdc-search"><span class="md-sr-only">주제 찾기</span><input type="search" placeholder="주제 찾기 (예: 임플란트, 신경치료)" data-care-filter autocomplete="off"></label>
		</div>
		<?php foreach ( $groups as $g => $items ) : ?>
			<h2 class="mdc-hub__group"><?php echo esc_html( $g ); ?></h2>
			<div class="mdc-cards">
				<?php foreach ( $items as $slug => $t ) : ?>
					<a class="mdc-card" href="<?php echo esc_url( md_sup_url( array( 'app' => 'care', 'topic' => $slug ) ) ); ?>" data-care-name="<?php echo esc_attr( $t['title'] . ' ' . ( $t['keywords'] ?? '' ) ); ?>">
						<span class="mdc-card__icon" aria-hidden="true"><?php echo esc_html( $t['icon'] ?? '🦷' ); ?></span>
						<span class="mdc-card__body">
							<span class="mdc-card__title"><?php echo esc_html( $t['title'] ); ?></span>
							<span class="mdc-card__tag"><?php echo esc_html( $t['tagline'] ?? '' ); ?></span>
							<?php if ( ! empty( $t['center'] ) ) : ?><span class="mdc-card__center"><?php echo esc_html( $t['center'] ); ?></span><?php endif; ?>
						</span>
						<span class="mdc-card__go" aria-hidden="true">→</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
		<?php if ( ! $topics ) : ?><p class="mds-notice">아직 등록된 주제가 없습니다.</p><?php endif; ?>
	</div>
	<?php
}

/** 주제 페이지 */
function md_care_render_topic( $slug ) {
	$topics = md_care_topics(); $t = $topics[ $slug ]; $all = array_keys( $topics ); $i = array_search( $slug, $all, true );
	$prev = $i > 0 ? $all[ $i - 1 ] : ''; $next = $i < count( $all ) - 1 ? $all[ $i + 1 ] : '';
	$n = 0;
	?>
	<article class="mdc-topic" data-care-topic>
		<header class="mdc-topic__head">
			<a class="mdc-topic__back" href="<?php echo esc_url( md_sup_url( array( 'app' => 'care', 'topic' => '' ) ) ); ?>">← 주제 목록</a>
			<div class="mdc-topic__title-row">
				<span class="mdc-topic__icon" aria-hidden="true"><?php echo esc_html( $t['icon'] ?? '🦷' ); ?></span>
				<div>
					<h2 class="mdc-topic__title"><?php echo esc_html( $t['title'] ); ?></h2>
					<p class="mdc-topic__tag"><?php echo esc_html( $t['tagline'] ?? '' ); ?></p>
				</div>
				<div class="mdc-topic__tools">
					<?php if ( ! empty( $t['center'] ) ) : ?><span class="mdc-badge"><?php echo esc_html( $t['center'] ); ?></span><?php endif; ?>
					<button type="button" class="mdc-btn mdc-btn--primary" data-care-present>▶ 설명 모드</button>
					<button type="button" class="mdc-btn" onclick="window.print()">🖨 인쇄</button>
				</div>
			</div>
		</header>

		<?php if ( ! empty( $t['summary'] ) ) : ?>
		<section class="mdc-sec mdc-sec--summary" data-care-slide>
			<h3 class="mdc-sec__title">한눈에</h3>
			<ul class="mdc-summary">
				<?php foreach ( (array) $t['summary'] as $s ) : ?><li><?php echo wp_kses_post( $s ); ?></li><?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<?php foreach ( (array) ( $t['sections'] ?? array() ) as $s ) : $n++; ?>
		<section class="mdc-sec" id="<?php echo esc_attr( 'care-' . ( $s['id'] ?? $n ) ); ?>" data-care-slide>
			<h3 class="mdc-sec__title"><span class="mdc-sec__num"><?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $s['title'] ); ?></h3>
			<div class="mdc-sec__body"><?php echo wp_kses_post( $s['body'] ); ?></div>
			<?php if ( ! empty( $s['tip'] ) ) : ?><aside class="mdc-tip"><strong>설명 포인트</strong> <?php echo wp_kses_post( $s['tip'] ); ?></aside><?php endif; ?>
		</section>
		<?php endforeach; ?>

		<?php if ( ! empty( $t['steps'] ) ) : $n++; ?>
		<section class="mdc-sec" id="care-steps" data-care-slide>
			<h3 class="mdc-sec__title"><span class="mdc-sec__num"><?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $t['steps_title'] ?? '치료 과정' ); ?></h3>
			<ol class="mdc-steps">
				<?php foreach ( (array) $t['steps'] as $k => $st ) : ?>
					<li class="mdc-step">
						<span class="mdc-step__n"><?php echo (int) $k + 1; ?></span>
						<div class="mdc-step__body">
							<strong class="mdc-step__title"><?php echo esc_html( $st['title'] ); ?></strong>
							<?php if ( ! empty( $st['time'] ) ) : ?><span class="mdc-step__time"><?php echo esc_html( $st['time'] ); ?></span><?php endif; ?>
							<p><?php echo wp_kses_post( $st['desc'] ?? '' ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $t['compare'] ) && ! empty( $t['compare']['rows'] ) ) : $n++; ?>
		<section class="mdc-sec" id="care-compare" data-care-slide>
			<h3 class="mdc-sec__title"><span class="mdc-sec__num"><?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $t['compare']['title'] ?? '비교' ); ?></h3>
			<div class="mdc-table-wrap"><table class="mdc-table">
				<?php if ( ! empty( $t['compare']['head'] ) ) : ?><thead><tr><?php foreach ( $t['compare']['head'] as $h ) : ?><th><?php echo esc_html( $h ); ?></th><?php endforeach; ?></tr></thead><?php endif; ?>
				<tbody><?php foreach ( $t['compare']['rows'] as $r ) : ?><tr><?php foreach ( $r as $c ) : ?><td><?php echo wp_kses_post( $c ); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
			</table></div>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $t['caution'] ) ) : $n++; ?>
		<section class="mdc-sec mdc-sec--caution" id="care-caution" data-care-slide>
			<h3 class="mdc-sec__title"><span class="mdc-sec__num"><?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $t['caution_title'] ?? '치료 후 주의사항' ); ?></h3>
			<ul class="mdc-caution">
				<?php foreach ( (array) $t['caution'] as $c ) : ?><li><?php echo wp_kses_post( $c ); ?></li><?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $t['faq'] ) ) : $n++; ?>
		<section class="mdc-sec" id="care-faq" data-care-slide>
			<h3 class="mdc-sec__title"><span class="mdc-sec__num"><?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span>자주 묻는 질문</h3>
			<div class="mdc-faq">
				<?php foreach ( (array) $t['faq'] as $f ) : ?>
					<details class="mdc-faq__item"><summary><?php echo esc_html( $f['q'] ); ?></summary><div class="mdc-faq__a"><?php echo wp_kses_post( $f['a'] ); ?></div></details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<footer class="mdc-topic__foot">
			<?php if ( ! empty( $t['links'] ) ) : ?>
				<div class="mdc-links">
					<?php foreach ( (array) $t['links'] as $l ) : ?>
						<a class="mdc-link" href="<?php echo esc_url( home_url( $l['url'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $l['label'] ); ?> ↗</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<nav class="mdc-pager" aria-label="다른 주제">
				<?php if ( $prev ) : ?><a class="mdc-btn" href="<?php echo esc_url( md_sup_url( array( 'app' => 'care', 'topic' => $prev ) ) ); ?>">← <?php echo esc_html( $topics[ $prev ]['title'] ); ?></a><?php endif; ?>
				<?php if ( $next ) : ?><a class="mdc-btn" href="<?php echo esc_url( md_sup_url( array( 'app' => 'care', 'topic' => $next ) ) ); ?>"><?php echo esc_html( $topics[ $next ]['title'] ); ?> →</a><?php endif; ?>
			</nav>
			<?php if ( ! empty( $t['updated'] ) ) : ?><p class="mdc-updated">내용 기준: <?php echo esc_html( $t['updated'] ); ?> · 비용·보험은 상담 시 최신 기준으로 안내</p><?php endif; ?>
		</footer>
	</article>

	<!-- 설명 모드 (슬라이드) -->
	<div class="mdc-present" data-care-stage hidden>
		<div class="mdc-present__bar">
			<span class="mdc-present__title"><?php echo esc_html( $t['title'] ); ?></span>
			<span class="mdc-present__count" data-care-count></span>
			<button type="button" class="mdc-btn" data-care-prev>← 이전</button>
			<button type="button" class="mdc-btn" data-care-next>다음 →</button>
			<button type="button" class="mdc-btn mdc-btn--ghost" data-care-exit>✕ 닫기</button>
		</div>
		<div class="mdc-present__stage" data-care-stage-body></div>
	</div>
	<?php
}

/** 앱 진입점 — supply-page.php 의 md_sup_render_page() 에서 부른다 */
function md_care_render() {
	$topic = md_care_current_topic();
	if ( $topic ) { md_care_render_topic( $topic ); } else { md_care_render_hub(); }
}
