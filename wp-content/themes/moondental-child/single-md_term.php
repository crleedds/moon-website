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

// 카테고리별 공통 FAQ (템플릿에서 자동 표시)
function moondental_md_term_common_faq( $cat_slug ) {
	$faqs = array(
		'implant' => array(
			array( '임플란트 수명은 얼마나 되나요?',
			       '평균 10~20년 이상 사용 가능하며, 정기 관리 시 평생 사용도 가능합니다. 흡연·잇몸 관리 소홀은 수명 단축 요인입니다.' ),
			array( '통증이 심한가요?',
			       '국소마취 후 진행되어 시술 중 통증은 거의 없습니다. 시술 후 2~3일 둔한 통증은 진통제로 조절 가능합니다.' ),
			array( '뼈가 부족해도 가능한가요?',
			       '뼈이식(GBR)·상악동 거상술 등으로 가능합니다. CBCT 3D 진단으로 정확히 판단합니다.' ),
			array( '당뇨·고혈압이 있어도 되나요?',
			       '조절된 상태라면 대부분 가능합니다. 혈당·혈압을 진료 전 확인 후 안전하게 진행합니다.' ),
			array( '수술 후 관리는 어떻게?',
			       '수술 당일 얼음찜질, 자극적 음식 회피, 처방 약 복용, 정기 소독·검진 필수.' ),
		),
		'ortho' => array(
			array( '성인도 교정 가능한가요?',
			       '잇몸이 건강하면 50~60대도 가능합니다. 뼈 이동은 나이와 무관.' ),
			array( '치료 기간은 얼마나?',
			       '단순 부분교정 6~12개월, 전체 교정 18~30개월. 케이스에 따라 다릅니다.' ),
			array( '발치 없이 가능한가요?',
			       '정밀 진단 후 비발치 우선 검토. 부분교정으로 해결 가능한 경우도 많습니다.' ),
			array( '통증이 심한가요?',
			       '초기 3~5일 압박감·둔통이 있으나 진통제로 조절됩니다. 이후 익숙해집니다.' ),
			array( '교정 후 유지장치는?',
			       '평생 야간 착용 권장. 착용하지 않으면 재발(회귀) 가능성.' ),
		),
		'preserve' => array(
			array( '신경치료 vs 발치 임플란트?',
			       '자연치아 살릴 수 있으면 무조건 보존이 우선. 신경치료 성공률 높으며 비용·시간 절약.' ),
			array( '통증이 심한가요?',
			       '국소마취 하에 진행. 시술 중 통증 거의 없음. 시술 후 2~3일 둔한 통증 가능.' ),
			array( '몇 번 방문해야 하나요?',
			       '통상 2~4회. 염증 정도와 근관 복잡도에 따라 달라집니다.' ),
			array( '치아 크라운 꼭 씌워야?',
			       '신경 치료 후 치아는 약해져 파절 위험. 크라운 보호 필수.' ),
			array( '재신경치료 가능한가요?',
			       '기존 신경치료 실패 케이스도 재근관치료로 자연치 보존 가능. 70~80% 성공률.' ),
		),
		'periodontics' => array(
			array( '스케일링 얼마나 자주?',
			       '6개월~1년 1회 권장. 잇몸 문제 있으면 3~4개월 1회.' ),
			array( '잇몸 수술은 언제?',
			       '중증 치주염(치주낭 5mm+·치조골 흡수)에서 시행. 조직 재생술 병행 가능.' ),
			array( '잇몸 재생 가능한가요?',
			       '치조골 흡수 부위 재생술(GBR·GTR) 로 일부 회복 가능.' ),
			array( '치석 제거 아프나요?',
			       '표층은 통증 거의 없음. 깊은 치주 SRP 는 마취 하 진행.' ),
			array( '잇몸 관리 습관은?',
			       '치실·치간칫솔 매일 사용. 부드러운 칫솔로 45° 각도 회전 브러싱.' ),
		),
		'aesthetic' => array(
			array( '라미네이트 몇 개면 되나요?',
			       '앞니 6~8개가 표준. 얼굴형·미소선 고려해 결정.' ),
			array( '치아를 얼마나 삭제하나요?',
			       '최소 삭제 (0.3~0.5mm). 무삭제 라미네이트도 가능.' ),
			array( '수명은 얼마나?',
			       '10~15년 이상. 관리 잘하면 20년+ 가능.' ),
			array( '변색되나요?',
			       '세라믹은 변색 거의 없음. 커피·와인은 여전히 주의.' ),
			array( '떨어질 수 있나요?',
			       '드물게 접착 실패 가능. 재접착으로 회복.' ),
		),
		'surgery' => array(
			array( '사랑니 꼭 뽑아야?',
			       '증상·매복 정도·인접치 영향에 따라. CBCT 진단 후 결정.' ),
			array( '수술 시간은?',
			       '단순 발치 5~10분, 매복 30~60분. 케이스별 차이.' ),
			array( '수술 후 관리는?',
			       '당일 얼음찜질·처방 약 복용. 격한 운동 3일 회피.' ),
			array( '신경 손상 위험은?',
			       'CBCT 3D 진단으로 신경 위치 확인 · 위험 최소화.' ),
			array( '진정 요법 가능한가요?',
			       '가능. 두려움이 큰 환자분 사전 상담 후 진행.' ),
		),
	);
	return $faqs[ $cat_slug ] ?? array(
		array( '진료 시간이 어떻게 되나요?',
		       '월·화·수·금 09:00-20:30 · 목 09:00-18:30 · 토 09:00-14:00 · 일/공휴일 휴진.' ),
		array( '예약은 어떻게?',
		       '카카오톡 채널·네이버 예약·전화 041-563-2875 · 홈페이지 상담 예약.' ),
		array( '주차 가능한가요?',
		       '본원 지하 기계식 무료 · SUV·대형차는 신부 제5공영주차장(무료 등록).' ),
	);
}

while ( have_posts() ) : the_post();
	$cats = get_the_terms( get_the_ID(), 'md_term_category' );
	if ( is_wp_error( $cats ) ) $cats = array();

	$first_cat_slug = ! empty( $cats ) ? $cats[0]->slug : 'general';
	$related_service = moondental_md_term_related_service( $first_cat_slug );
	$common_faqs = moondental_md_term_common_faq( $first_cat_slug );

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
					<a class="md-btn md-btn-primary" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>">
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

		<!-- 5. 큰 CTA 배너 -->
		<aside class="md-term-big-cta">
			<h3><?php the_title(); ?>에 대해 더 궁금하신가요?</h3>
			<p>문치과병원 진료팀이 직접 상담해 드립니다</p>
			<div class="md-term-big-cta__actions">
				<a class="md-term-big-cta__btn md-term-big-cta__btn--white" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>">
					🗓️ 예약하기
				</a>
				<?php if ( $related_service ) : ?>
				<a class="md-term-big-cta__btn md-term-big-cta__btn--white" href="<?php echo esc_url( home_url( $related_service['url'] ) ); ?>">
					🗓️ 진료 안내
				</a>
				<?php endif; ?>
				<a class="md-term-big-cta__btn md-term-big-cta__btn--white" href="tel:041-563-2875">
					📞 041-563-2875
				</a>
			</div>
		</aside>

		<div class="md-term-actions">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'md_term' ) ); ?>">
				← 전체 치과 백과사전으로
			</a>
		</div>
	</div>
</section>

<?php if ( $related ) : ?>
<section class="md-section md-section--surface md-section--sm">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">🧩 같은 카테고리<?php if ( ! empty( $cats ) ) echo ' · ' . esc_html( $cats[0]->name ); ?></span>
			<h2 class="md-section-head__title">함께 알면 좋은 용어</h2>
		</header>
		<div class="md-enc-grid">
			<?php foreach ( $related as $r ) : ?>
				<a class="md-enc-card" href="<?php echo esc_url( $r['url'] ); ?>">
					<h3 class="md-enc-card__title"><?php echo esc_html( $r['title'] ); ?></h3>
					<p class="md-enc-card__excerpt"><?php echo esc_html( $r['excerpt'] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php endwhile; get_footer(); ?>
