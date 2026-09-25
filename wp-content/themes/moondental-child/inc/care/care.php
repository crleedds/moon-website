<?php
/**
 * v4.1 · Moon Dental Care — 직원 전용 · 환자 설명용 자료 (단순화)
 *
 *  직원 전용(/직원/) 허브의 도구. 진료실·상담실에서 환자에게 치료를 설명할 때 태블릿·모니터로
 *  띄워 놓고 쓰는 화면이다. 화면은 세 가지뿐이다.
 *    ① 주제 목록  — 큰 타일
 *    ② 주제 화면  — 「사진」「영상」「설명」 세 탭. 사진은 격자, 영상은 격자(병원 서버 mp4 · 없으면 드라이브)
 *    ③ 설명 모드  — 사진·영상을 한 화면에 하나씩, ← → 로 넘김
 *
 *  주제 데이터: inc/care/data/{slug}.php (파일 하나 = 주제 하나)
 *    'photos' => [ ['src'=>'implant/01.jpg','caption'=>'…'], … ]        uploads/care/ 기준
 *    'videos' => [ ['file'=>'video/implant/01.mp4','poster'=>'video/implant/01.jpg','title'=>'…','drive'=>'ID'], … ]
 *    'embeds' => [ ['title'=>'…','src'=>'https://docs.google.com/…'], … ]
 *    'summary' => [ '…', … ]  ·  'text' => [ ['title'=>'…','body'=>HTML,'tip'=>'…'], … ]  ·  steps/compare/caution/faq (선택)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/care-edit.php'; // v4.2 · 관리자 편집(업로드·삭제·순서·제목)

function md_care_media_url( $rel ) {
	if ( preg_match( '#^https?://#', $rel ) ) return $rel;
	$up = wp_upload_dir();
	return rtrim( $up['baseurl'], '/' ) . '/care/' . ltrim( $rel, '/' );
}

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

function md_care_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) return;
	if ( ! function_exists( 'md_sup_current_app' ) || 'care' !== md_sup_current_app() ) return;
	$dir = get_stylesheet_directory(); $uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/care.css' ) ) wp_enqueue_style( 'moondental-care', $uri . '/assets/css/care.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/care.css' ) );
	if ( file_exists( $dir . '/assets/js/care.js' ) )   wp_enqueue_script( 'moondental-care', $uri . '/assets/js/care.js', array(), filemtime( $dir . '/assets/js/care.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'md_care_enqueue', 31 );

/** ① 주제 목록 */
function md_care_render_hub() {
	$topics = md_care_topics();
	?>
	<div class="mdc-hub">
		<label class="mdc-search"><span class="md-sr-only">주제 찾기</span><input type="search" placeholder="🔍 주제 찾기" data-care-filter autocomplete="off"></label>
		<div class="mdc-tiles">
			<?php foreach ( $topics as $slug => $t ) : $it = md_care_items( $slug, $t ); $np = count( $it['photos'] ); $nv = count( $it['videos'] ); $ne = count( $t['embeds'] ?? array() ); ?>
				<a class="mdc-tile" href="<?php echo esc_url( md_sup_url( array( 'app' => 'care', 'topic' => $slug ) ) ); ?>" data-care-name="<?php echo esc_attr( $t['title'] . ' ' . ( $t['keywords'] ?? '' ) ); ?>">
					<span class="mdc-tile__icon" aria-hidden="true"><?php echo esc_html( $t['icon'] ?? '🦷' ); ?></span>
					<span class="mdc-tile__title"><?php echo esc_html( $t['title'] ); ?></span>
					<span class="mdc-tile__meta"><?php
						$m = array(); if ( $np ) $m[] = "사진 {$np}"; if ( $nv ) $m[] = "영상 {$nv}"; if ( $ne ) $m[] = '자료 ' . $ne;
						echo esc_html( implode( ' · ', $m ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/** 영상 한 장 (카드·슬라이드 공용) */
function md_care_video_html( $v, $big = false ) {
	$title = $v['title'] ?? '영상';
	if ( ! empty( $v['file'] ) ) {
		$poster = ! empty( $v['poster'] ) ? ' poster="' . esc_url( md_care_media_url( $v['poster'] ) ) . '"' : '';
		return '<video controls playsinline preload="' . ( $big ? 'auto' : 'metadata' ) . '"' . $poster . ' src="' . esc_url( md_care_media_url( $v['file'] ) ) . '" title="' . esc_attr( $title ) . '"></video>';
	}
	if ( ! empty( $v['drive'] ) ) {
		return '<iframe src="https://drive.google.com/file/d/' . esc_attr( $v['drive'] ) . '/preview" loading="lazy" allow="autoplay; fullscreen" allowfullscreen title="' . esc_attr( $title ) . '"></iframe>';
	}
	return '';
}

/** ② 주제 화면 */
function md_care_render_topic( $slug ) {
	$topics = md_care_topics(); $t = $topics[ $slug ];
	$it = md_care_items( $slug, $t ); $photos = $it['photos']; $videos = $it['videos']; $embeds = (array) ( $t['embeds'] ?? array() );
	$can_edit = md_care_can_edit(); $max_up = size_format( wp_max_upload_size() );
	$has_text = ! empty( $t['summary'] ) || ! empty( $t['text'] ) || ! empty( $t['steps'] ) || ! empty( $t['faq'] ) || ! empty( $t['caution'] ) || ! empty( $t['compare'] );
	$first = $photos ? 'photos' : ( $videos ? 'videos' : ( $embeds ? 'embeds' : 'text' ) );
	?>
	<article class="mdc-topic" data-care-topic data-slug="<?php echo esc_attr( $slug ); ?>"<?php if ( $can_edit ) : ?> data-care-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-care-nonce="<?php echo esc_attr( wp_create_nonce( 'md_care_edit' ) ); ?>"<?php endif; ?>>
		<header class="mdc-topic__head">
			<a class="mdc-topic__back" href="<?php echo esc_url( md_sup_url( array( 'app' => 'care', 'topic' => '' ) ) ); ?>">← 주제 목록</a>
			<div class="mdc-topic__row">
				<span class="mdc-topic__icon" aria-hidden="true"><?php echo esc_html( $t['icon'] ?? '🦷' ); ?></span>
				<div class="mdc-topic__titles">
					<h2 class="mdc-topic__title"><?php echo esc_html( $t['title'] ); ?></h2>
					<?php if ( ! empty( $t['tagline'] ) ) : ?><p class="mdc-topic__tag"><?php echo esc_html( $t['tagline'] ); ?></p><?php endif; ?>
				</div>
				<div class="mdc-topic__tools">
					<?php if ( $photos || $videos ) : ?><button type="button" class="mdc-btn mdc-btn--primary" data-care-present>▶ 설명 모드</button><?php endif; ?>
					<button type="button" class="mdc-btn" onclick="window.print()">🖨 인쇄</button>
					<?php if ( $can_edit ) : ?><button type="button" class="mdc-btn mdc-btn--edit" data-care-edit-toggle aria-pressed="false">✏️ 편집</button><?php endif; ?>
				</div>
			</div>
			<?php if ( $can_edit ) : ?>
			<div class="mdc-editbar" data-care-editbar hidden>
				<span>편집 중 — 카드의 <b>✕</b>로 삭제, <b>‹ ›</b>로 순서, 제목을 눌러 고칩니다. 파일 하나 최대 <b><?php echo esc_html( $max_up ); ?></b></span>
				<button type="button" class="mdc-btn mdc-btn--ghost" data-care-reset>원래 목록으로 되돌리기</button>
			</div>
			<?php endif; ?>
			<nav class="mdc-tabs" aria-label="자료 종류">
				<?php if ( $photos || $can_edit ) : ?><button type="button" class="mdc-tab" data-care-tab="photos">📷 사진 <b data-care-count="photo"><?php echo count( $photos ); ?></b></button><?php endif; ?>
				<?php if ( $videos || $can_edit ) : ?><button type="button" class="mdc-tab" data-care-tab="videos">🎬 영상 <b data-care-count="video"><?php echo count( $videos ); ?></b></button><?php endif; ?>
				<?php if ( $embeds ) : ?><button type="button" class="mdc-tab" data-care-tab="embeds">📊 자료 <b><?php echo count( $embeds ); ?></b></button><?php endif; ?>
				<?php if ( $has_text ) : ?><button type="button" class="mdc-tab" data-care-tab="text">📝 설명</button><?php endif; ?>
			</nav>
		</header>

		<?php if ( $photos || $can_edit ) : ?>
		<section class="mdc-pane" data-care-pane="photos">
			<?php if ( $can_edit ) : ?>
			<label class="mdc-drop" data-care-drop="photo" hidden>
				<input type="file" accept="image/jpeg,image/png,image/webp" multiple data-care-file="photo">
				<span class="mdc-drop__icon">📷</span><span class="mdc-drop__text"><b>사진 추가</b> — 여기를 누르거나 파일을 끌어다 놓으세요 (JPG·PNG, 여러 장 가능)</span>
				<span class="mdc-drop__prog" data-care-prog hidden></span>
			</label>
			<?php endif; ?>
			<div class="mdc-grid" data-care-grid="photo">
				<?php foreach ( $photos as $i => $p ) : $src = md_care_media_url( $p['src'] ); $cap = $p['caption'] ?? ''; ?>
					<figure class="mdc-card" data-care-item="photo" data-index="<?php echo (int) $i; ?>" data-src="<?php echo esc_url( $src ); ?>" data-caption="<?php echo esc_attr( $cap ); ?>">
						<a href="<?php echo esc_url( $src ); ?>" data-care-zoom="<?php echo (int) $i; ?>"><img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $cap ); ?>" loading="lazy"></a>
						<figcaption data-care-caption><?php echo esc_html( $cap ); ?></figcaption>
						<?php if ( $can_edit ) : ?><span class="mdc-card__tools"><button type="button" data-care-move="-1" title="앞으로">‹</button><button type="button" data-care-move="1" title="뒤로">›</button><button type="button" class="mdc-card__del" data-care-del title="삭제">✕</button></span><?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $videos || $can_edit ) : ?>
		<section class="mdc-pane" data-care-pane="videos">
			<?php if ( $can_edit ) : ?>
			<label class="mdc-drop" data-care-drop="video" hidden>
				<input type="file" accept="video/mp4,video/webm" data-care-file="video">
				<span class="mdc-drop__icon">🎬</span><span class="mdc-drop__text"><b>영상 추가</b> — MP4 파일을 누르거나 끌어다 놓으세요 (썸네일은 자동 생성)</span>
				<span class="mdc-drop__prog" data-care-prog hidden></span>
			</label>
			<?php endif; ?>
			<div class="mdc-grid mdc-grid--video" data-care-grid="video">
				<?php foreach ( $videos as $i => $v ) : ?>
					<figure class="mdc-card mdc-card--video" data-care-item="video" data-index="<?php echo (int) $i; ?>">
						<div class="mdc-card__player"><?php echo md_care_video_html( $v ); // phpcs:ignore ?></div>
						<figcaption data-care-caption><?php echo esc_html( $v['title'] ?? '' ); ?></figcaption>
						<?php if ( $can_edit ) : ?><span class="mdc-card__tools"><button type="button" data-care-move="-1" title="앞으로">‹</button><button type="button" data-care-move="1" title="뒤로">›</button><button type="button" class="mdc-card__del" data-care-del title="삭제">✕</button></span><?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $embeds ) : ?>
		<section class="mdc-pane" data-care-pane="embeds">
			<?php foreach ( $embeds as $e ) : ?>
				<div class="mdc-embed"><h3><?php echo esc_html( $e['title'] ?? '' ); ?></h3><div class="mdc-embed__frame"><iframe src="<?php echo esc_url( $e['src'] ); ?>" loading="lazy" allowfullscreen title="<?php echo esc_attr( $e['title'] ?? '' ); ?>"></iframe></div></div>
			<?php endforeach; ?>
		</section>
		<?php endif; ?>

		<?php if ( $has_text ) : ?>
		<section class="mdc-pane mdc-pane--text" data-care-pane="text">
			<?php if ( ! empty( $t['summary'] ) ) : ?>
				<div class="mdc-box"><h3>한눈에</h3><ul class="mdc-summary"><?php foreach ( (array) $t['summary'] as $s ) : ?><li><?php echo wp_kses_post( $s ); ?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
			<?php foreach ( (array) ( $t['text'] ?? array() ) as $s ) : ?>
				<div class="mdc-box"><h3><?php echo esc_html( $s['title'] ); ?></h3><div class="mdc-prose"><?php echo wp_kses_post( $s['body'] ?? '' ); ?></div>
				<?php if ( ! empty( $s['tip'] ) ) : ?><aside class="mdc-tip"><strong>설명 포인트</strong> <?php echo wp_kses_post( $s['tip'] ); ?></aside><?php endif; ?></div>
			<?php endforeach; ?>
			<?php if ( ! empty( $t['steps'] ) ) : ?>
				<div class="mdc-box"><h3><?php echo esc_html( $t['steps_title'] ?? '치료 과정' ); ?></h3><ol class="mdc-steps"><?php foreach ( (array) $t['steps'] as $k => $st ) : ?><li><span class="mdc-steps__n"><?php echo (int) $k + 1; ?></span><div><strong><?php echo esc_html( $st['title'] ); ?></strong><?php if ( ! empty( $st['time'] ) ) : ?> <em><?php echo esc_html( $st['time'] ); ?></em><?php endif; ?><p><?php echo wp_kses_post( $st['desc'] ?? '' ); ?></p></div></li><?php endforeach; ?></ol></div>
			<?php endif; ?>
			<?php if ( ! empty( $t['compare']['rows'] ) ) : ?>
				<div class="mdc-box"><h3><?php echo esc_html( $t['compare']['title'] ?? '비교' ); ?></h3><div class="mdc-table-wrap"><table class="mdc-table"><?php if ( ! empty( $t['compare']['head'] ) ) : ?><thead><tr><?php foreach ( $t['compare']['head'] as $h ) : ?><th><?php echo esc_html( $h ); ?></th><?php endforeach; ?></tr></thead><?php endif; ?><tbody><?php foreach ( $t['compare']['rows'] as $r ) : ?><tr><?php foreach ( $r as $c ) : ?><td><?php echo wp_kses_post( $c ); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></div>
			<?php endif; ?>
			<?php if ( ! empty( $t['caution'] ) ) : ?>
				<div class="mdc-box mdc-box--warn"><h3><?php echo esc_html( $t['caution_title'] ?? '주의사항' ); ?></h3><ul class="mdc-caution"><?php foreach ( (array) $t['caution'] as $c ) : ?><li><?php echo wp_kses_post( $c ); ?></li><?php endforeach; ?></ul></div>
			<?php endif; ?>
			<?php if ( ! empty( $t['faq'] ) ) : ?>
				<div class="mdc-box"><h3>자주 묻는 질문</h3><div class="mdc-faq"><?php foreach ( (array) $t['faq'] as $f ) : ?><details><summary><?php echo esc_html( $f['q'] ); ?></summary><div><?php echo wp_kses_post( $f['a'] ); ?></div></details><?php endforeach; ?></div></div>
			<?php endif; ?>
		</section>
		<?php endif; ?>

		<?php if ( ! empty( $t['links'] ) ) : ?>
		<footer class="mdc-topic__foot">
			<?php foreach ( (array) $t['links'] as $l ) : ?>
				<a class="mdc-link" href="<?php echo esc_url( preg_match( '#^https?://#', $l['url'] ) ? $l['url'] : home_url( $l['url'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $l['label'] ); ?> ↗</a>
			<?php endforeach; ?>
		</footer>
		<?php endif; ?>
	</article>

	<!-- ③ 설명 모드 · 사진·영상 한 화면에 하나씩 -->
	<div class="mdc-present" data-care-stage data-first="<?php echo esc_attr( $first ); ?>" hidden>
		<div class="mdc-present__bar">
			<span class="mdc-present__title"><?php echo esc_html( $t['title'] ); ?></span>
			<span class="mdc-present__count" data-care-count></span>
			<button type="button" class="mdc-btn" data-care-prev>← 이전</button>
			<button type="button" class="mdc-btn" data-care-next>다음 →</button>
			<button type="button" class="mdc-btn mdc-btn--ghost" data-care-exit>✕ 닫기</button>
		</div>
		<div class="mdc-present__stage" data-care-stage-body></div>
		<p class="mdc-present__cap" data-care-cap></p>
	</div>
	<!-- 사진 크게 보기 -->
	<div class="mdc-zoom" data-care-lightbox hidden>
		<button type="button" class="mdc-zoom__nav mdc-zoom__nav--prev" data-care-lightbox-prev aria-label="이전">‹</button>
		<img alt="">
		<button type="button" class="mdc-zoom__nav mdc-zoom__nav--next" data-care-lightbox-next aria-label="다음">›</button>
		<p class="mdc-zoom__cap"></p>
		<button type="button" class="mdc-zoom__close" data-care-lightbox-close aria-label="닫기">✕</button>
	</div>
	<?php
}

function md_care_render() {
	$topic = md_care_current_topic();
	if ( $topic ) { md_care_render_topic( $topic ); } else { md_care_render_hub(); }
}
