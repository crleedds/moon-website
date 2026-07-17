<?php
/**
 * 치과사전 아카이브 · /치과사전/
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
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>치과사전</span>
		</nav>
		<span class="md-page-hero__eyebrow">DENTAL ENCYCLOPEDIA · 치과사전</span>
		<h1 class="md-page-hero__title">천안·아산 문치과병원 · 치과사전</h1>
		<p class="md-page-hero__lead">
			치과 진료·시술·질환에 대한 용어를 알기 쉽게 정리했습니다. 궁금한 용어를 검색하거나 카테고리·초성으로 찾아보세요.
		</p>
	</div>
</section>

<section class="md-section md-enc">
	<div class="md-container">

		<!-- 검색 -->
		<form class="md-enc-search" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">
			<label for="md-enc-q" class="md-sr-only">용어 검색</label>
			<input type="search" id="md-enc-q" name="q" value="<?php echo esc_attr( $search_q ); ?>" placeholder="🔍 용어 검색 (예: 임플란트, 스케일링, 라미네이트…)" autocomplete="off">
			<?php if ( $active_cat ) : ?><input type="hidden" name="cat" value="<?php echo esc_attr( $active_cat ); ?>"><?php endif; ?>
			<button type="submit" class="md-btn md-btn-primary">검색</button>
			<?php if ( $search_q || $active_cat || $active_init ) : ?>
				<a class="md-enc-clear" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">전체 보기</a>
			<?php endif; ?>
		</form>

		<!-- 카테고리 탭 -->
		<div class="md-enc-cats" role="tablist" aria-label="분야">
			<a class="md-enc-cat<?php echo $active_cat ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">전체</a>
			<?php foreach ( $categories as $cat ) : $url = add_query_arg( array( 'cat' => $cat->slug ), get_post_type_archive_link( 'md_term' ) ); ?>
				<a class="md-enc-cat<?php echo $active_cat === $cat->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $cat->name ); ?><span class="md-enc-cat__n"><?php echo (int) $cat->count; ?></span></a>
			<?php endforeach; ?>
		</div>

		<!-- 초성 필터 -->
		<div class="md-enc-initials" role="tablist" aria-label="초성 필터">
			<?php
			$base_args = array();
			if ( $active_cat ) $base_args['cat'] = $active_cat;
			if ( $search_q )   $base_args['q']   = $search_q;
			?>
			<a class="md-enc-initial<?php echo $active_init ? '' : ' is-active'; ?>" href="<?php echo esc_url( add_query_arg( $base_args, get_post_type_archive_link( 'md_term' ) ) ); ?>">전체</a>
			<?php foreach ( moondental_initial_groups() as $g ) :
				$url = add_query_arg( array_merge( $base_args, array( 'ㅊ' => $g ) ), get_post_type_archive_link( 'md_term' ) ); ?>
				<a class="md-enc-initial<?php echo $active_init === $g ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $g ); ?></a>
			<?php endforeach; ?>
		</div>

		<!-- 결과 카운트 -->
		<p class="md-enc-count"><strong><?php echo (int) $total_count; ?></strong>개 용어<?php if ( $search_q ) : ?> · 검색어: <em>"<?php echo esc_html( $search_q ); ?>"</em><?php endif; ?></p>

		<!-- 초성별 그룹 결과 -->
		<?php if ( $total_count === 0 ) : ?>
			<div class="md-enc-empty">
				<p>😔 검색 결과가 없습니다.</p>
				<p>다른 검색어를 시도하거나 <a href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">전체 용어</a>를 확인해보세요.</p>
			</div>
		<?php else : ?>
			<?php foreach ( $visible_groups as $group => $items ) : ?>
				<div class="md-enc-group" id="ㅊ-<?php echo esc_attr( $group ); ?>">
					<h2 class="md-enc-group__title"><?php echo esc_html( $group ); ?></h2>
					<div class="md-enc-grid">
						<?php foreach ( $items as $item ) : ?>
							<a class="md-enc-card" href="<?php echo esc_url( $item['url'] ); ?>">
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

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php get_footer(); ?>
