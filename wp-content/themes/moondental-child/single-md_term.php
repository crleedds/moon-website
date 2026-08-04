<?php
/**
 * 치과 백과사전 단일 용어 · /치과사전/{slug}/
 * v3.44.87 · 비디치과 스타일 · 풍부한 구성 + CTA + FAQ + 관련 진료
 *
 * @package moondental-child
 */
get_header();

// 카테고리 → 관련 진료 페이지 매핑
function moondental_md_term_related_service( $cat_slug ) {
	$map = array(
		'implant'      => array( 'name' => '임플란트 센터', 'url' => '/임플란트-센터/', 'icon' => '🦷' ),
		'ortho'        => array( 'name' => '슈어스마일 투명교정', 'url' => '/슈어스마일-투명교정/', 'icon' => '✨' ),
		'preserve'     => array( 'name' => '자연치아 살리기', 'url' => '/자연치아-살리기/', 'icon' => '🌿' ),
		'periodontics' => array( 'name' => '자연치아 살리기 (치주치료)', 'url' => '/자연치아-살리기/', 'icon' => '💧' ),
		'aesthetic'    => array( 'name' => '스마일 디자인 센터', 'url' => '/스마일디자인센터/', 'icon' => '✨' ),
		'surgery'      => array( 'name' => '사랑니 발치', 'url' => '/사랑니-발치/', 'icon' => '🦷' ),
		'pediatric'    => array( 'name' => '예방클리닉', 'url' => '/예방클리닉/', 'icon' => '👶' ),
		'jaw'          => array( 'name' => '턱관절 클리닉', 'url' => '/턱관절-클리닉/', 'icon' => '😬' ),
		'general'      => array( 'name' => '예방클리닉', 'url' => '/예방클리닉/', 'icon' => '🛡️' ),
	);
	return $map[ $cat_slug ] ?? null;
}

/**
 * v3.44.91 · 용어별 FAQ 자동 생성 · 본문 h3 섹션에서 파싱
 *   각 h3 는 하나의 FAQ Q/A 쌍이 됨. Q는 h3 텍스트 기반으로 자연스러운 질문화.
 */
function moondental_md_term_generated_faq( $title, $body ) {
	$faqs = array();
	// h3 (제목) 다음의 콘텐츠를 다음 h3 전까지 추출
	if ( ! preg_match_all( '#<h3[^>]*>(.*?)</h3>(.*?)(?=<h3|$)#is', $body, $matches ) ) {
		return $faqs;
	}
	for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
		$section_title = trim( wp_strip_all_tags( $matches[1][ $i ] ) );
		$section_body  = trim( $matches[2][ $i ] );
		if ( ! $section_title || ! $section_body ) continue;
		// section_title 을 자연 질문화
		$q = moondental_md_term_faq_question( $title, $section_title );
		// 답변은 HTML 유지 · 길면 첫 500자로 축약
		$a_plain = trim( wp_strip_all_tags( $section_body ) );
		if ( mb_strlen( $a_plain ) > 500 ) {
			$a_plain = mb_substr( $a_plain, 0, 480 ) . '…';
		}
		$faqs[] = array( $q, $a_plain );
	}
	// FAQ 가 3개 미만이면 공통 질문 하나 추가
	if ( count( $faqs ) < 3 ) {
		$faqs[] = array(
			$title . ' 관련 상담은 어디서 받을 수 있나요?',
			'문치과병원에서 ' . $title . ' 관련 정밀 진단과 상담을 받으실 수 있습니다. 온라인 상담 예약 · 카카오톡 채널 · 전화 041-563-2875 로 편하게 문의 가능합니다.'
		);
	}
	return $faqs;
}

/**
 * v3.44.91 · h3 섹션 제목 → 자연 질문화
 */
function moondental_md_term_faq_question( $title, $section ) {
	$section = trim( $section );
	// 알려진 섹션 패턴 매핑
	$patterns = array(
		'#^정의$#u'                => $title . '(이)란 무엇인가요?',
		'#^개념$#u'                => $title . '(이)란 무엇인가요?',
		'#^원인#u'                 => $title . '의 원인은 무엇인가요?',
		'#^병인#u'                 => $title . '의 병인은 무엇인가요?',
		'#^증상#u'                 => $title . '이(가) 있으면 어떤 증상이 나타나나요?',
		'#^진단#u'                 => $title . '은(는) 어떻게 진단하나요?',
		'#^검사#u'                 => $title . '은(는) 어떻게 검사하나요?',
		'#^치료#u'                 => $title . '은(는) 어떻게 치료하나요?',
		'#^수술#u'                 => $title . ' 수술은 어떻게 진행되나요?',
		'#^관리#u'                 => $title . ' 후 관리는 어떻게 해야 하나요?',
		'#^예방#u'                 => $title . '은(는) 어떻게 예방하나요?',
		'#^주의사항#u'             => $title . ' 관련 주의사항은 무엇인가요?',
		'#^적응증#u'               => $title . '의 적응증은 무엇인가요?',
		'#^종류#u'                 => $title . '의 종류에는 어떤 것이 있나요?',
		'#^분류#u'                 => $title . '은(는) 어떻게 분류되나요?',
		'#^특징#u'                 => $title . '의 특징은 무엇인가요?',
		'#^구성#u'                 => $title . '은(는) 무엇으로 구성되어 있나요?',
		'#^구조#u'                 => $title . '의 구조는 어떻게 되어 있나요?',
		'#^임상 적용#u'            => $title . '의 임상 적용은 어떻게 되나요?',
		'#^임상적 의의#u'          => $title . '의 임상적 의의는 무엇인가요?',
		'#^시술 과정#u'            => $title . ' 시술은 어떻게 진행되나요?',
		'#^시술#u'                 => $title . ' 시술은 어떻게 진행되나요?',
		'#^장점#u'                 => $title . '의 장점은 무엇인가요?',
		'#^단점#u'                 => $title . '의 단점은 무엇인가요?',
		'#^기능#u'                 => $title . '은(는) 어떤 기능을 하나요?',
		'#^위치#u'                 => $title . '은(는) 어디에 위치하나요?',
		'#^역할#u'                 => $title . '의 역할은 무엇인가요?',
		'#^발달#u'                 => $title . '은(는) 어떻게 발달하나요?',
		'#^발생#u'                 => $title . '은(는) 왜 발생하나요?',
		'#^등급#u'                 => $title . '의 등급 분류는 어떻게 되나요?',
	);
	foreach ( $patterns as $regex => $q ) {
		if ( preg_match( $regex, $section ) ) return $q;
	}
	// fallback: 섹션 제목 그대로 질문화
	return $title . ' — ' . $section . '?';
}

// v3.44.92 · 병원 정보 (네이버 예약 URL 등)
$info = function_exists( 'moondental_get_info' ) ? moondental_get_info() : array();

while ( have_posts() ) : the_post();
	$cats = get_the_terms( get_the_ID(), 'md_term_category' );
	if ( is_wp_error( $cats ) ) $cats = array();

	$first_cat_slug = ! empty( $cats ) ? $cats[0]->slug : 'general';
	$related_service = moondental_md_term_related_service( $first_cat_slug );
	// v3.44.91 · 용어별 FAQ 자동 생성 (본문 h3 파싱)
	$common_faqs = moondental_md_term_generated_faq( get_the_title(), get_the_content() );

	// 관련 용어 (같은 카테고리 · 6개)
	$related = array();
	if ( ! empty( $cats ) ) {
		$cat_ids = wp_list_pluck( $cats, 'term_id' );
		$rel_q = new WP_Query( array(
			'post_type'      => 'md_term',
			'posts_per_page' => 6,
			'post__not_in'   => array( get_the_ID() ),
			'orderby'        => 'rand',
			'no_found_rows'  => true,
			'tax_query'      => array( array(
				'taxonomy' => 'md_term_category',
				'field'    => 'id',
				'terms'    => $cat_ids,
			) ),
		) );
		if ( $rel_q->have_posts() ) {
			while ( $rel_q->have_posts() ) {
				$rel_q->the_post();
				$related[] = array(
					'title'   => get_the_title(),
					'url'     => get_permalink(),
					'excerpt' => wp_trim_words( get_the_excerpt(), 15, '…' ),
				);
			}
			wp_reset_postdata();
		}
	}
	$post = get_queried_object();
	setup_postdata( $post );
?>

<section class="md-page-hero md-page-hero--term">
	<div class="md-container md-container--narrow">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸
			<a href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_encyclopedia', '치과 백과사전' ) ); ?></a>
			<?php if ( ! empty( $cats ) ) : $first_cat = $cats[0]; ?>
				▸ <a href="<?php echo esc_url( add_query_arg( array( 'cat' => $first_cat->slug ), get_post_type_archive_link( 'md_term' ) ) ); ?>"><?php echo esc_html( $first_cat->name ); ?></a>
			<?php endif; ?>
			 ▸ <span><?php the_title(); ?></span>
		</nav>
		<?php if ( ! empty( $cats ) ) : ?>
			<div class="md-term-hero__cats">
				<?php foreach ( $cats as $c ) : ?>
					<a class="md-term-hero__cat" href="<?php echo esc_url( add_query_arg( array( 'cat' => $c->slug ), get_post_type_archive_link( 'md_term' ) ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<?php $ex = get_the_excerpt(); if ( $ex ) : ?>
			<p class="md-page-hero__lead"><?php echo esc_html( $ex ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="md-section">
	<div class="md-container md-container--narrow">

		<!-- 1. 본문 -->
		<article class="md-page-content md-term-content">
			<h2 class="md-term-section-title"><span class="md-term-section-title__num">1</span> 상세 설명</h2>
			<?php the_content(); ?>
		</article>

		<!-- 2. 인라인 CTA 카드 -->
		<aside class="md-term-inline-cta">
			<div class="md-term-inline-cta__body">
				<h3 class="md-term-inline-cta__title">
					🔍 <?php the_title(); ?>, 이제 아셨죠?<br>
					그럼 내 치아는 어떤 상태일까요?
				</h3>
				<p class="md-term-inline-cta__lead">
					용어를 아는 것보다 중요한 건 <strong>내 치아의 실제 상태</strong>입니다.<br>
					문치과병원 진료팀이 <strong>정밀 검진</strong>으로 직접 확인해드립니다.
				</p>
				<div class="md-term-inline-cta__actions">
					<?php $_naver_book = $info['naver_place'] ?? ''; ?>
					<a class="md-btn md-btn-primary" href="<?php echo esc_url( $_naver_book ?: home_url( '/상담예약/' ) ); ?>" target="_blank" rel="noopener">
						🗓️ 내 치아 검진 예약하기
					</a>
					<a class="md-btn md-btn-ghost" href="tel:041-563-2875">
						📞 전화 문의
					</a>
				</div>
				<p class="md-term-inline-cta__note">🕐 진료 시간 · 당일 상담 가능</p>
			</div>
		</aside>

		<?php if ( $related_service ) : ?>
		<!-- 3. 관련 진료과 링크 -->
		<aside class="md-term-related-service">
			<div class="md-term-related-service__label">
				<span class="md-term-related-service__icon" aria-hidden="true"><?php echo esc_html( $related_service['icon'] ); ?></span>
				<span>관련 진료과 · 상담 받기</span>
				<strong><?php the_title(); ?> 진료 상세 보기</strong>
			</div>
			<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( home_url( $related_service['url'] ) ); ?>">
				→ 진료 안내 바로가기
			</a>
		</aside>
		<?php endif; ?>

		<!-- 4. FAQ · 카테고리별 공통 질문 -->
		<?php if ( $common_faqs ) : ?>
		<section class="md-term-faq">
			<h2 class="md-term-section-title"><span class="md-term-section-title__num">2</span> <?php the_title(); ?> 자주 묻는 질문</h2>
			<div class="md-term-faq__list">
				<?php foreach ( $common_faqs as $faq ) : ?>
					<details class="md-term-faq__item">
						<summary class="md-term-faq__q">Q. <?php echo esc_html( $faq[0] ); ?></summary>
						<div class="md-term-faq__a"><?php echo esc_html( $faq[1] ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php /* v3.44.93 · 큰 CTA 배너 제거 (인라인 CTA 와 중복) */ ?>

		<div class="md-term-actions">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">
				← 전체 치과 백과사전으로
			</a>
		</div>
	</div>
</section>

<?php
// v3.44.92 · 이전/다음 용어 (같은 카테고리 내 · 알파벳 순)
$prev_next = array( 'prev' => null, 'next' => null );
if ( ! empty( $cats ) ) {
	$cat_ids = wp_list_pluck( $cats, 'term_id' );
	$all_in_cat = get_posts( array(
		'post_type'      => 'md_term',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'fields'         => 'ids',
		'tax_query'      => array( array(
			'taxonomy' => 'md_term_category',
			'field'    => 'id',
			'terms'    => $cat_ids,
		) ),
	) );
	$curr_id = get_the_ID();
	$idx = array_search( $curr_id, $all_in_cat );
	if ( $idx !== false ) {
		if ( $idx > 0 && isset( $all_in_cat[ $idx - 1 ] ) ) {
			$prev_next['prev'] = array( 'title' => get_the_title( $all_in_cat[ $idx - 1 ] ), 'url' => get_permalink( $all_in_cat[ $idx - 1 ] ) );
		}
		if ( isset( $all_in_cat[ $idx + 1 ] ) ) {
			$prev_next['next'] = array( 'title' => get_the_title( $all_in_cat[ $idx + 1 ] ), 'url' => get_permalink( $all_in_cat[ $idx + 1 ] ) );
		}
	}
}
?>

<?php if ( $related ) : ?>
<!-- 함께 알면 좋은 용어 (pill list) -->
<section class="md-section md-section--sm">
	<div class="md-container md-container--narrow">
		<h2 class="md-term-related-title">🔗 함께 알면 좋은 용어</h2>
		<div class="md-term-related-pills">
			<?php foreach ( array_slice( $related, 0, 6 ) as $r ) : ?>
				<a class="md-term-related-pill" href="<?php echo esc_url( $r['url'] ); ?>">
					<span class="md-term-related-pill__name"><?php echo esc_html( $r['title'] ); ?></span>
					<?php if ( ! empty( $cats ) ) : ?>
						<span class="md-term-related-pill__cat"><?php echo esc_html( $cats[0]->name ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- 같은 카테고리 카드 그리드 -->
<section class="md-section md-section--surface md-section--sm">
	<div class="md-container md-container--narrow">
		<h2 class="md-term-samecat-title">📂 같은 카테고리<?php if ( ! empty( $cats ) ) echo ': ' . esc_html( $cats[0]->name ); ?></h2>
		<div class="md-term-samecat-grid">
			<?php foreach ( array_slice( $related, 0, 8 ) as $r ) : ?>
				<a class="md-term-samecat-card" href="<?php echo esc_url( $r['url'] ); ?>">
					<strong><?php echo esc_html( $r['title'] ); ?></strong>
					<span><?php echo esc_html( $r['excerpt'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $prev_next['prev'] || $prev_next['next'] ) : ?>
<!-- 이전 / 전체 / 다음 네비게이션 -->
<section class="md-section md-section--sm">
	<div class="md-container md-container--narrow">
		<nav class="md-term-nav" aria-label="이전 다음 용어">
			<?php if ( $prev_next['prev'] ) : ?>
				<a class="md-term-nav__prev" href="<?php echo esc_url( $prev_next['prev']['url'] ); ?>">
					<span aria-hidden="true">←</span> <span><?php echo esc_html( $prev_next['prev']['title'] ); ?></span>
				</a>
			<?php else : ?><span></span><?php endif; ?>
			<a class="md-term-nav__all" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">📖 전체 보기</a>
			<?php if ( $prev_next['next'] ) : ?>
				<a class="md-term-nav__next" href="<?php echo esc_url( $prev_next['next']['url'] ); ?>">
					<span><?php echo esc_html( $prev_next['next']['title'] ); ?></span> <span aria-hidden="true">→</span>
				</a>
			<?php else : ?><span></span><?php endif; ?>
		</nav>
	</div>
</section>
<?php endif; ?>

<?php endwhile; get_footer(); ?>
