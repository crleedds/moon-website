<?php
/**
 * 지역별 오시는 길 상세 페이지 — /오시는-길/{slug}/ URL에 의해 로드됨.
 *  moondental_region_intercept() (functions.php) 가 직접 include 하여 호출.
 *
 *  데이터 소스: moondental_get_region_by_slug() (inc/regions.php)
 *  v3.32.4: 모든 텍스트를 Customizer에서 편집 가능하게 이관.
 *   {region}·{region_long}·{province}·{duration}·{duration_label}·{distance}·{highway}·{ktx}·{bus}·{note} 토큰 지원.
 *
 * @package moondental-child
 */

get_header();

$slug = get_query_var( 'region_slug' );
if ( ! $slug && isset( $_GET['region'] ) ) {
	$slug = sanitize_text_field( wp_unslash( $_GET['region'] ) );
}

$region = function_exists( 'moondental_get_region_by_slug' ) ? moondental_get_region_by_slug( $slug ) : null;

if ( ! $region ) {
	?>
	<section class="md-section">
		<div class="md-container md-container--narrow md-u-center">
			<h1><?php echo esc_html( md_content( 'region_not_found_title', '지역 정보를 찾을 수 없습니다' ) ); ?></h1>
			<p class="md-u-mt-24">
				<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/오시는-길/' ) ); ?>">
					<?php echo esc_html( md_content( 'region_back_label', '← 오시는 길로 돌아가기' ) ); ?>
				</a>
			</p>
		</div>
	</section>
	<?php
	get_footer();
	return;
}

$info       = moondental_get_info();
$phone      = $info['phone'];
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $phone );

$region_name    = $region['name'];
$region_long    = $region['name_long'];
$province       = $region['province'];
$distance       = $region['distance_km'];
$duration       = $region['duration_min'];
$duration_label = ! empty( $region['duration_label'] ) ? $region['duration_label'] : ( $duration . '분' );
$is_walking     = ! empty( $region['duration_label'] ) && strpos( $region['duration_label'], '도보' ) !== false;
$highway        = $region['highway'] ?? '';
$ktx            = $region['ktx'] ?? '';
$bus            = $region['bus'] ?? '';
$note           = $region['note'] ?? '';

/* 토큰 치환 헬퍼 */
$tokens = array(
	'{region}'         => $region_name,
	'{region_long}'    => $region_long,
	'{province}'       => $province,
	'{duration}'       => (string) $duration,
	'{duration_label}' => $duration_label,
	'{distance}'       => (string) $distance,
	'{highway}'        => $highway,
	'{ktx}'            => $ktx,
	'{bus}'            => $bus,
	'{note}'           => $note,
);
$replace = function( $text ) use ( $tokens ) {
	return strtr( (string) $text, $tokens );
};

/* 파서 */
$parse_pair = function( $text ) {
	$out = array();
	foreach ( md_parse_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) >= 2 ) $out[] = array( 'title' => $parts[0], 'body' => $parts[1] );
	}
	return $out;
};
$parse_popular = function( $text ) {
	$out = array();
	foreach ( md_parse_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 4 ) {
			$out[] = array( 'slug' => $parts[0], 'icon' => $parts[1], 'title' => $parts[2], 'desc' => $parts[3] );
		}
	}
	return $out;
};

$reason_cards  = $parse_pair( md_content( 'region_reasons_cards', '' ) );
$popular_items = $parse_popular( md_content( 'region_popular_items', '' ) );
$faq_items     = $parse_pair( md_content( 'region_faq_items', '' ) );
?>

<!-- ============ Hero ============ -->
<section class="md-region-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸
			<a href="<?php echo esc_url( home_url( '/오시는-길/' ) ); ?>">오시는 길</a> ▸
			<span><?php echo esc_html( $region_name ); ?></span>
		</nav>
		<span class="md-region-hero__eyebrow"><?php echo esc_html( $replace( md_content( 'region_hero_eyebrow', '' ) ) ); ?></span>
		<h1 class="md-region-hero__title">
			<?php echo esc_html( $replace( md_content( 'region_hero_title_a', '' ) ) ); ?><br>
			<em><?php echo esc_html( $replace( md_content( 'region_hero_title_b', '' ) ) ); ?></em>
		</h1>
		<p class="md-region-hero__lead">
			<?php
			$lead = $is_walking
				? md_content( 'region_hero_lead_walking', '' )
				: md_content( 'region_hero_lead_drive', '' );
			echo nl2br( wp_kses_post( $replace( $lead ) ) );
			?>
		</p>
		<div class="md-region-hero__badges">
			<?php if ( $is_walking ) : ?>
				<span><?php echo esc_html( $replace( md_content( 'region_hero_badge_walk', '' ) ) ); ?></span>
				<span><?php echo esc_html( $replace( md_content( 'region_hero_badge_walk_bus', '' ) ) ); ?></span>
			<?php else : ?>
				<span><?php echo esc_html( $replace( md_content( 'region_hero_badge_drive', '' ) ) ); ?></span>
				<span><?php echo esc_html( $replace( md_content( 'region_hero_badge_bus', '' ) ) ); ?></span>
			<?php endif; ?>
			<span><?php echo esc_html( $replace( md_content( 'region_hero_badge_night', '' ) ) ); ?></span>
		</div>
	</div>
</section>

<!-- ============ 0. 치료 키워드 SEO 섹션 (v3.44.103 · v3.44.104 재편) ============ -->
<section class="md-section md-section--sm md-region-keywords">
	<div class="md-container">
		<header class="md-section-head">
			<h2 class="md-section-head__title"><?php echo esc_html( $region_name ); ?>에서 임플란트·충치·잇몸치료 잘하는 치과</h2>
			<p class="md-section-head__lead">
				<?php echo esc_html( $region_name ); ?> 환자분들이 <?php echo esc_html( $duration ); ?>분 거리 천안 문치과병원을 선택하시는 이유입니다.
				<?php echo esc_html( $province ); ?> <?php echo esc_html( $region_long ); ?>에서 임플란트·충치·신경치료·잇몸치료·사랑니·라미네이트·소아치과·턱관절 전문 진료과 협진이 필요할 때 30년 진료 문치과병원을 찾아주세요.
			</p>
		</header>
		<div class="md-region-tk-grid">
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 임플란트 잘하는 치과</h3>
				<p>CBCT 3D 정밀 진단·네비게이션 가이드 임플란트로 <?php echo esc_html( $region_name ); ?>에서 오시는 환자분께 정확·안전한 식립. 30년 임상 노하우 임플란트 센터 운영.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/임플란트-센터/' ) ); ?>"><?php echo esc_html( $region_name ); ?> 임플란트 자세히 →</a>
			</article>
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 충치치료·신경치료 잘하는 치과</h3>
				<p>미세 현미경 정밀 신경치료·치수복조술로 최대한 발치 없이 자연치아 보존. <?php echo esc_html( $region_name ); ?> 환자분 재신경치료·크라운까지 원스톱.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/자연치아-살리기/' ) ); ?>">충치·신경치료 자세히 →</a>
			</article>
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 잇몸치료·치주치료 잘하는 치과</h3>
				<p>스케일링·잇몸 소파술·치주 수술까지 잇몸 상태에 따른 단계별 치료. <?php echo esc_html( $region_name ); ?>에서 정기 검진·치주 관리 원스톱.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/자연치아-살리기/' ) ); ?>">잇몸·치주치료 자세히 →</a>
			</article>
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 사랑니 발치 잘하는 치과</h3>
				<p>CT 3D로 신경·매복 정밀 확인 후 안전한 사랑니 발치. <?php echo esc_html( $region_name ); ?> 환자분 매복 사랑니·부분 마취·진정 마취 옵션 제공.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/사랑니-발치/' ) ); ?>">사랑니 발치 자세히 →</a>
			</article>
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 라미네이트·치아미백 잘하는 치과</h3>
				<p>스마일 디자인 센터 (10F) 라미네이트·전문가 치아미백·심미 크라운·세라믹. <?php echo esc_html( $region_name ); ?>에서 오시는 성인·직장인 환자 자연스러운 미소 설계.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/심미치료/' ) ); ?>">라미네이트·미백 자세히 →</a>
			</article>
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 소아치과·어린이 치과</h3>
				<p>어린이 눈높이 진료·실란트·불소도포·소아 교정 상담. <?php echo esc_html( $region_name ); ?> 부모님과 아이 모두 안심하고 진료받는 소아치과.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/예방클리닉/' ) ); ?>">소아치과 자세히 →</a>
			</article>
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 턱관절·이갈이 클리닉</h3>
				<p>턱관절 통증·이갈이·안면 근막통·스플린트 치료. 방사선 검사와 근전도 진단으로 <?php echo esc_html( $region_name ); ?> 환자분 정밀 치료.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/턱관절-클리닉/' ) ); ?>">턱관절·이갈이 자세히 →</a>
			</article>
			<article class="md-region-tk-card">
				<h3><?php echo esc_html( $region_name ); ?> 치아교정·투명교정 잘하는 치과</h3>
				<p>슈어스마일 투명교정 중부권 센터·브라켓 교정·설측 교정. <?php echo esc_html( $region_name ); ?> 성인·직장인·소아 환자 맞춤 교정 계획.</p>
				<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/투명교정-센터/' ) ); ?>">치아교정 자세히 →</a>
			</article>
		</div>
	</div>
</section>

<!-- ============ 1. 교통 안내 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'region_traffic_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $replace( md_content( 'region_traffic_title', '' ) ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'region_traffic_lead', '' ) ); ?></p>
		</header>

		<div class="md-region-routes">
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚗</span>
					<h3><?php echo esc_html( md_content( 'region_traffic_car_title', '' ) ); ?></h3>
					<span class="md-region-route__time"><?php echo esc_html( $duration ); ?>분</span>
				</div>
				<p><?php echo wp_kses_post( $replace( md_content( 'region_traffic_car_body', '' ) ) ); ?></p>
				<p class="md-region-route__detail"><?php echo wp_kses_post( $replace( md_content( 'region_traffic_car_route', '' ) ) ); ?></p>
				<p class="md-region-route__detail"><?php echo wp_kses_post( $replace( md_content( 'region_traffic_car_park', '' ) ) ); ?></p>
			</article>

			<?php if ( ! empty( $ktx ) ) : ?>
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚆</span>
					<h3><?php echo esc_html( md_content( 'region_traffic_ktx_title', '' ) ); ?></h3>
					<span class="md-region-route__time md-region-route__time--alt">기차</span>
				</div>
				<p><?php echo esc_html( $ktx ); ?>.</p>
				<p class="md-region-route__detail"><?php echo esc_html( md_content( 'region_traffic_ktx_detail', '' ) ); ?></p>
			</article>
			<?php endif; ?>

			<?php if ( ! empty( $bus ) ) : ?>
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚌</span>
					<h3><?php echo esc_html( md_content( 'region_traffic_bus_title', '' ) ); ?></h3>
					<span class="md-region-route__time md-region-route__time--alt">버스</span>
				</div>
				<p><?php echo esc_html( $bus ); ?>.</p>
				<p class="md-region-route__detail"><?php echo esc_html( md_content( 'region_traffic_bus_detail', '' ) ); ?></p>
			</article>
			<?php endif; ?>
		</div>

		<aside class="md-region-callout">
			<strong><?php echo esc_html( $replace( md_content( 'region_callout_title', '' ) ) ); ?></strong>
			<p><?php echo wp_kses_post( $replace( md_content( 'region_callout_body', '' ) ) ); ?></p>
		</aside>
	</div>
</section>

<!-- ============ 2. 선택 이유 ============ -->
<?php if ( $reason_cards ) : ?>
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'region_reasons_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $replace( md_content( 'region_reasons_title', '' ) ) ); ?></h2>
		</header>

		<div class="md-region-reasons">
			<?php $i = 1; foreach ( $reason_cards as $r ) : ?>
			<article class="md-region-reason">
				<div class="md-region-reason__num"><?php echo esc_html( sprintf( '%02d', $i ) ); ?></div>
				<h3><?php echo esc_html( $r['title'] ); ?></h3>
				<p><?php echo wp_kses_post( $replace( $r['body'] ) ); ?></p>
			</article>
			<?php $i++; endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ============ 3. 인기 진료 ============ -->
<?php if ( $popular_items ) : ?>
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'region_popular_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $replace( md_content( 'region_popular_title', '' ) ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( $replace( md_content( 'region_popular_lead', '' ) ) ); ?></p>
		</header>

		<div class="md-service-grid">
			<?php foreach ( $popular_items as $idx => $svc ) :
				$page = get_page_by_path( $svc['slug'] );
				$url  = $page ? get_permalink( $page ) : home_url( '/' . rawurlencode( $svc['slug'] ) . '/' );
				$num  = sprintf( '%02d', $idx + 1 );
				$title = $replace( $svc['title'] );
			?>
				<article class="md-service-card">
					<span class="md-service-card__num" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
					<div class="md-service-card__icon" aria-hidden="true"><?php echo moondental_render_icon( $svc['icon'] ); ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $title ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $replace( $svc['desc'] ) ); ?></p>
					<span class="md-service-card__more" aria-hidden="true">자세히 보기 <span class="md-service-card__arrow">→</span></span>
					<a class="md-service-card__link" href="<?php echo esc_url( $url ); ?>">
						<span class="md-screen-reader-text"><?php echo esc_html( $title ); ?> 자세히 보기</span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php /* v3.37.2 · 지역 페이지 중간 CTA 배너 제거 (하단 CTA 배너에 이미 있음) */ ?>

<!-- ============ 5. 지역 FAQ ============ -->
<?php if ( $faq_items ) : ?>
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'region_faq_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( $replace( md_content( 'region_faq_title', '' ) ) ); ?></h2>
		</header>

		<div class="md-faq">
			<?php $first = true; foreach ( $faq_items as $q ) : ?>
			<details class="md-faq__item"<?php echo $first ? ' open' : ''; ?>>
				<summary><?php echo esc_html( $replace( $q['title'] ) ); ?></summary>
				<p><?php echo wp_kses_post( $replace( $q['body'] ) ); ?></p>
			</details>
			<?php $first = false; endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ============ 6. 다른 지역 ============ -->
<?php if ( function_exists( 'moondental_get_regions_by_province' ) ) : ?>
<section class="md-section md-section--surface md-section--sm">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'region_other_eyebrow', '' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'region_other_title', '' ) ); ?></h2>
		</header>
		<div class="md-region-grid">
			<?php
			$all_regions = moondental_get_regions_by_province();
			foreach ( $all_regions as $prov => $list ) :
				foreach ( $list as $r ) :
					if ( $r['slug'] === $slug ) continue; ?>
					<a class="md-region-pill" href="<?php echo esc_url( home_url( '/오시는-길/' . $r['slug'] . '/' ) ); ?>">
						<span class="md-region-pill__icon" aria-hidden="true"><?php echo esc_html( ! empty( $r['icon'] ) ? $r['icon'] : '🚗' ); ?></span>
						<span class="md-region-pill__name"><?php echo esc_html( $r['name'] ); ?></span>
						<span class="md-region-pill__time"><?php echo esc_html( ! empty( $r['duration_label'] ) ? $r['duration_label'] : ( $r['duration_min'] . '분' ) ); ?></span>
					</a>
			<?php endforeach; endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
