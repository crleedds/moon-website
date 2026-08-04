<?php
/**
 * 치과 백과사전 아카이브 · /치과사전/
 *  카테고리 탭 · 초성 필터 · 검색 · 카드 그리드
 *
 * @package moondental-child
 */
get_header();

$active_cat  = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
$active_init = isset( $_GET['ㅊ'] ) ? sanitize_text_field( wp_unslash( $_GET['ㅊ'] ) ) : '';
$search_q    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

// 카테고리 목록
$categories = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false ) );
if ( is_wp_error( $categories ) ) $categories = array();

// 전체 용어 (필터 서버측 · 제한 없음)
$args = array(
	'post_type'      => 'md_term',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
	'no_found_rows'  => true,
);
if ( $active_cat ) {
	$args['tax_query'] = array( array(
		'taxonomy' => 'md_term_category',
		'field'    => 'slug',
		'terms'    => $active_cat,
	) );
}
if ( $search_q ) {
	$args['s'] = $search_q;
}
$q = new WP_Query( $args );

// 초성별 그룹핑 (필터 후)
$grouped = array();
$all_initials = moondental_initial_groups();
foreach ( $all_initials as $g ) $grouped[ $g ] = array();
$grouped['#'] = array(); // 기타 (숫자·영문)

if ( $q->have_posts() ) {
	while ( $q->have_posts() ) {
		$q->the_post();
		$title = get_the_title();
		$initial = moondental_hangul_initial( $title );
		$group = moondental_initial_group( $initial );
		if ( ! in_array( $group, $all_initials, true ) ) $group = '#';
		if ( $active_init && $active_init !== $group ) continue;
		$grouped[ $group ][] = array(
			'id'      => get_the_ID(),
			'title'   => $title,
			'url'     => get_permalink(),
			'excerpt' => wp_trim_words( get_the_excerpt(), 20, '…' ),
			'cats'    => wp_get_post_terms( get_the_ID(), 'md_term_category' ),
		);
	}
	wp_reset_postdata();
}

// 표시할 그룹만 (빈 그룹 제외)
$visible_groups = array_filter( $grouped, function( $items ) { return ! empty( $items ); } );
$total_count = array_sum( array_map( 'count', $visible_groups ) );
?>

<section class="md-page-hero md-page-hero--enc">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸ <span><?php echo esc_html( md_content( 'breadcrumb_encyclopedia', '치과 백과사전' ) ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow"><?php echo esc_html( md_content( 'enc_hero_eyebrow', 'DENTAL ENCYCLOPEDIA · 치과 백과사전' ) ); ?></span>
		<h1 class="md-page-hero__title"><?php echo esc_html( md_content( 'enc_hero_title', '천안·아산 문치과병원 · 치과 백과사전' ) ); ?></h1>
		<p class="md-page-hero__lead">
			<?php echo esc_html( md_content( 'enc_hero_lead', '치과 진료·시술·질환에 대한 용어를 알기 쉽게 정리했습니다. 궁금한 용어를 검색하거나 카테고리·초성으로 찾아보세요.' ) ); ?>
		</p>
	</div>
</section>

<section class="md-section md-enc">
	<div class="md-container">

		<!-- 검색 -->
		<form class="md-enc-search" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">
			<label for="md-enc-q" class="md-sr-only"><?php echo esc_html( md_content( 'enc_search_label', '용어 검색' ) ); ?></label>
			<input type="search" id="md-enc-q" name="q" value="<?php echo esc_attr( $search_q ); ?>" placeholder="<?php echo esc_attr( md_content( 'enc_search_placeholder', '🔍 용어 검색 (예: 임플란트, 스케일링, 라미네이트…)' ) ); ?>" autocomplete="off">
			<?php if ( $active_cat ) : ?><input type="hidden" name="cat" value="<?php echo esc_attr( $active_cat ); ?>"><?php endif; ?>
			<button type="submit" class="md-btn md-btn-primary"><?php echo esc_html( md_content( 'enc_search_btn', '검색' ) ); ?></button>
			<?php if ( $search_q || $active_cat || $active_init ) : ?>
				<a class="md-enc-clear" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>"><?php echo esc_html( md_content( 'enc_clear', '전체 보기' ) ); ?></a>
			<?php endif; ?>
		</form>

		<!-- 카테고리 탭 -->
		<div class="md-enc-cats" role="tablist" aria-label="<?php echo esc_attr( md_content( 'enc_cats_aria', '분야' ) ); ?>">
			<a class="md-enc-cat<?php echo $active_cat ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>"><?php echo esc_html( md_content( 'enc_cats_all', '전체' ) ); ?></a>
			<?php foreach ( $categories as $cat ) : $url = add_query_arg( array( 'cat' => $cat->slug ), get_post_type_archive_link( 'md_term' ) ); ?>
				<a class="md-enc-cat<?php echo $active_cat === $cat->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $cat->name ); ?><span class="md-enc-cat__n"><?php echo (int) $cat->count; ?></span></a>
			<?php endforeach; ?>
		</div>

		<!-- 초성 필터 -->
		<div class="md-enc-initials" role="tablist" aria-label="<?php echo esc_attr( md_content( 'enc_initials_aria', '초성 필터' ) ); ?>">
			<?php
			$base_args = array();
			if ( $active_cat ) $base_args['cat'] = $active_cat;
			if ( $search_q )   $base_args['q']   = $search_q;
			?>
			<a class="md-enc-initial<?php echo $active_init ? '' : ' is-active'; ?>" href="<?php echo esc_url( add_query_arg( $base_args, get_post_type_archive_link( 'md_term' ) ) ); ?>"><?php echo esc_html( md_content( 'enc_cats_all', '전체' ) ); ?></a>
			<?php foreach ( moondental_initial_groups() as $g ) :
				$url = add_query_arg( array_merge( $base_args, array( 'ㅊ' => $g ) ), get_post_type_archive_link( 'md_term' ) ); ?>
				<a class="md-enc-initial<?php echo $active_init === $g ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $g ); ?></a>
			<?php endforeach; ?>
		</div>

		<!-- 결과 카운트 -->
		<p class="md-enc-count"><strong><?php echo (int) $total_count; ?></strong><?php echo esc_html( md_content( 'enc_count_unit', '개 용어' ) ); ?><?php if ( $search_q ) : ?> · <?php echo esc_html( md_content( 'enc_search_label_prefix', '검색어:' ) ); ?> <em>"<?php echo esc_html( $search_q ); ?>"</em><?php endif; ?></p>

		<!-- 초성별 그룹 결과 -->
		<?php if ( $total_count === 0 ) : ?>
			<div class="md-enc-empty">
				<p><?php echo esc_html( md_content( 'enc_empty_title', '😔 검색 결과가 없습니다.' ) ); ?></p>
				<p>
					<?php
					$link_html = '<a href="' . esc_url( get_post_type_archive_link( 'md_term' ) ) . '">' . esc_html( md_content( 'enc_empty_hint_link', '전체 용어' ) ) . '</a>';
					echo wp_kses( str_replace( '{link}', $link_html, md_content( 'enc_empty_hint_tpl', '다른 검색어를 시도하거나 {link}를 확인해보세요.' ) ), array( 'a' => array( 'href' => array() ) ) );
					?>
				</p>
			</div>
		<?php else : ?>
			<?php foreach ( $visible_groups as $group => $items ) : ?>
				<div class="md-enc-group" id="ㅊ-<?php echo esc_attr( $group ); ?>">
					<h2 class="md-enc-group__title"><?php echo esc_html( $group ); ?></h2>
					<div class="md-enc-grid">
						<?php foreach ( $items as $item ) : ?>
							<a class="md-enc-card"
							   href="<?php echo esc_url( $item['url'] ); ?>"
							   data-md-enc-modal="1"
							   data-md-enc-id="<?php echo esc_attr( $item['id'] ); ?>"
							   data-md-enc-url="<?php echo esc_url( $item['url'] ); ?>">
								<h3 class="md-enc-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
								<p class="md-enc-card__excerpt"><?php echo esc_html( $item['excerpt'] ); ?></p>
								<?php if ( ! empty( $item['cats'] ) && ! is_wp_error( $item['cats'] ) ) : ?>
									<div class="md-enc-card__cats">
										<?php foreach ( $item['cats'] as $c ) : ?>
											<span class="md-enc-card__cat"><?php echo esc_html( $c->name ); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

	</div>
</section>

<?php
$_naver_book = '';
if ( function_exists( 'moondental_get_info' ) ) {
	$_info = moondental_get_info();
	$_naver_book = $_info['naver_place'] ?? '';
}
?>
<!-- v3.44.92 · 백과사전 모달 팝업 · 카드 클릭 시 열림 -->
<div class="md-enc-modal" id="md-enc-modal" role="dialog" aria-modal="true" aria-labelledby="md-enc-modal-title" hidden>
	<div class="md-enc-modal__backdrop" data-md-enc-close></div>
	<div class="md-enc-modal__panel" role="document">
		<button type="button" class="md-enc-modal__close" data-md-enc-close aria-label="닫기">×</button>
		<div class="md-enc-modal__body">
			<div class="md-enc-modal__loading" data-md-enc-loading>
				<p>불러오는 중...</p>
			</div>
			<div class="md-enc-modal__content" data-md-enc-content hidden>
				<div class="md-enc-modal__cats" data-md-enc-cats></div>
				<h2 class="md-enc-modal__title" id="md-enc-modal-title" data-md-enc-title></h2>
				<div class="md-enc-modal__excerpt" data-md-enc-excerpt></div>
				<div class="md-enc-modal__article" data-md-enc-article></div>

				<!-- v3.44.92 · 함께 알면 좋은 용어 -->
				<div class="md-enc-modal__related" data-md-enc-related-wrap hidden>
					<h4 class="md-enc-modal__related-title">🔗 함께 알면 좋은 용어</h4>
					<div class="md-enc-modal__related-list" data-md-enc-related></div>
				</div>

				<!-- v3.44.92 · 같은 카테고리 -->
				<div class="md-enc-modal__same-cat" data-md-enc-samecat-wrap hidden>
					<h4 class="md-enc-modal__samecat-title" data-md-enc-samecat-title>📂 같은 카테고리</h4>
					<div class="md-enc-modal__samecat-grid" data-md-enc-samecat></div>
				</div>

				<div class="md-enc-modal__link-full">
					<a class="md-enc-modal__link-full-btn" data-md-enc-fullpage href="#">🔗 이 용어 전용 페이지 보기 →</a>
				</div>
			</div>
		</div>
		<div class="md-enc-modal__actions">
			<a class="md-btn md-btn-primary"
			   href="<?php echo esc_url( $_naver_book ?: home_url( '/상담예약/' ) ); ?>"
			   target="_blank" rel="noopener">🗓️ 상담 예약</a>
			<a class="md-btn md-btn-ghost" href="tel:041-563-2875">📞 전화 문의</a>
		</div>
	</div>
</div>

<script>
(function(){
	var AJAX = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var modal = document.getElementById('md-enc-modal');
	if ( ! modal ) return;
	var loading = modal.querySelector('[data-md-enc-loading]');
	var content = modal.querySelector('[data-md-enc-content]');
	var titleEl = modal.querySelector('[data-md-enc-title]');
	var excerptEl = modal.querySelector('[data-md-enc-excerpt]');
	var articleEl = modal.querySelector('[data-md-enc-article]');
	var catsEl = modal.querySelector('[data-md-enc-cats]');
	var fullpageBtn = modal.querySelector('[data-md-enc-fullpage]');
	var cache = {};

	function openModal(id, url) {
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		loading.hidden = false;
		content.hidden = true;
		fullpageBtn.href = url || '#';
		if ( cache[id] ) { render(cache[id]); return; }
		fetch( AJAX + '?action=md_term_get&id=' + encodeURIComponent(id) )
			.then(function(r){ return r.json(); })
			.then(function(res){
				if ( res && res.success && res.data ) {
					cache[id] = res.data;
					render(res.data);
				} else {
					articleEl.innerHTML = '<p>정보를 불러오지 못했습니다. 전용 페이지를 확인해주세요.</p>';
					loading.hidden = true;
					content.hidden = false;
				}
			})
			.catch(function(){
				articleEl.innerHTML = '<p>정보를 불러오지 못했습니다.</p>';
				loading.hidden = true;
				content.hidden = false;
			});
	}
	var relatedWrap = modal.querySelector('[data-md-enc-related-wrap]');
	var relatedEl = modal.querySelector('[data-md-enc-related]');
	var samecatWrap = modal.querySelector('[data-md-enc-samecat-wrap]');
	var samecatEl = modal.querySelector('[data-md-enc-samecat]');
	var samecatTitleEl = modal.querySelector('[data-md-enc-samecat-title]');

	function render(data) {
		titleEl.textContent = data.title || '';
		excerptEl.textContent = data.excerpt || '';
		articleEl.innerHTML = data.body || '';
		catsEl.innerHTML = '';
		if ( data.cats && data.cats.length ) {
			data.cats.forEach(function(c){
				var s = document.createElement('span');
				s.className = 'md-enc-modal__cat';
				s.textContent = c.name;
				catsEl.appendChild(s);
			});
			if ( samecatTitleEl && data.cats[0] ) {
				samecatTitleEl.textContent = '📂 같은 카테고리 · ' + data.cats[0].name;
			}
		}
		// 함께 알면 좋은 용어 (pill list · 최대 4개)
		if ( relatedEl && data.related && data.related.length ) {
			relatedEl.innerHTML = '';
			data.related.slice(0, 4).forEach(function(r){
				var a = document.createElement('a');
				a.className = 'md-enc-modal__related-item';
				a.setAttribute('data-md-enc-modal', '1');
				a.setAttribute('data-md-enc-id', r.id);
				a.setAttribute('data-md-enc-url', r.url);
				a.href = r.url;
				a.textContent = r.title;
				relatedEl.appendChild(a);
			});
			relatedWrap.hidden = false;
		} else if ( relatedWrap ) {
			relatedWrap.hidden = true;
		}
		// 같은 카테고리 카드 (동일 데이터, 그리드 스타일)
		if ( samecatEl && data.related && data.related.length ) {
			samecatEl.innerHTML = '';
			data.related.slice(0, 6).forEach(function(r){
				var a = document.createElement('a');
				a.className = 'md-enc-modal__samecat-card';
				a.setAttribute('data-md-enc-modal', '1');
				a.setAttribute('data-md-enc-id', r.id);
				a.setAttribute('data-md-enc-url', r.url);
				a.href = r.url;
				a.innerHTML = '<strong>' + escapeHtml(r.title) + '</strong>';
				samecatEl.appendChild(a);
			});
			samecatWrap.hidden = false;
		} else if ( samecatWrap ) {
			samecatWrap.hidden = true;
		}
		fullpageBtn.href = data.url || '#';
		loading.hidden = true;
		content.hidden = false;
		// 모달 스크롤 최상단
		var body = modal.querySelector('.md-enc-modal__body');
		if ( body ) body.scrollTop = 0;
	}
	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function(c){
			return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
		});
	}
	function closeModal() {
		modal.hidden = true;
		document.body.style.overflow = '';
	}

	document.addEventListener('click', function(e){
		var card = e.target.closest('[data-md-enc-modal]');
		if ( card ) {
			e.preventDefault();
			var id = card.getAttribute('data-md-enc-id');
			var url = card.getAttribute('data-md-enc-url');
			openModal(id, url);
			return;
		}
		if ( e.target.closest('[data-md-enc-close]') ) {
			e.preventDefault();
			closeModal();
		}
	});
	document.addEventListener('keydown', function(e){
		if ( e.key === 'Escape' && ! modal.hidden ) closeModal();
	});
})();
</script>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php get_footer(); ?>
