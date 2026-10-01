<?php
/**
 * 지역별 오시는 길 상세 페이지 — /오시는-길/{slug}/
 *  moondental_region_intercept() (functions.php) 가 직접 include 하여 호출.
 *
 *  v4.14 · 단순화 (원장 지시 · "복잡하고 검색용으로 만든 티가 난다")
 *   - 키워드 카드 8개 · 선택 이유 6개 · 인기 진료 5개 섹션 제거
 *   - 남긴 것: 오는 길(차·기차·버스) · 도착하면 · 자주 묻는 것 · 치과 고르는 기준 안내서 · 같은 권역 다른 지역 · 상담
 *   - 문구는 검색어가 아니라 사람에게 말하듯 쓴다. 검색 제목·설명은 seo-boost.php 가 따로 담당.
 *
 *  데이터 소스: moondental_get_region_by_slug() (inc/regions.php)
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
$duration       = (int) $region['duration_min'];
$duration_label = ! empty( $region['duration_label'] ) ? $region['duration_label'] : ( $duration . '분' );
$is_walking     = ! empty( $region['duration_label'] ) && strpos( $region['duration_label'], '도보' ) !== false;
$highway        = $region['highway'] ?? '';
$ktx            = $region['ktx'] ?? '';
$bus            = $region['bus'] ?? '';
$note           = $region['note'] ?? '';

$hours_wd  = $info['hours_wd']  ?? '';
$hours_thu = $info['hours_thu'] ?? '';
$hours_sat = $info['hours_sat'] ?? '';
$hours_off = $info['hours_off'] ?? '';
$hours_line = implode( ' · ', array_filter( array(
	$hours_wd  ? '월·화·수·금 ' . $hours_wd : '',
	$hours_thu ? '목 ' . $hours_thu : '',
	$hours_sat ? '토 ' . $hours_sat : '',
	$hours_off ? $hours_off : '',
) ) );

/* 자주 묻는 것 — 검색용 문장이 아니라 실제로 묻는 것 3가지 */
$faq_items = array(
	array( '주차는 어디에 하나요?', '문타워 지하 주차장을 이용하시면 됩니다. 진료 후 데스크에서 주차 등록을 해 드립니다. 만차일 때는 천안고속버스터미널 주차장이 바로 옆에 있습니다.' ),
	array( '진료 시간이 어떻게 되나요?', $hours_line ? $hours_line . '. 평일 저녁 진료가 있어 퇴근 후 출발해도 됩니다.' : '평일 저녁 진료가 있어 퇴근 후 출발해도 됩니다. 요일별 시간은 아래 상담 안내에 있습니다.' ),
	array( '처음 갈 때 무엇을 챙기나요?', '신분증(건강보험증)과 드시는 약 정보를 챙겨 주세요. 다른 치과에서 찍은 X-ray가 있으면 USB나 이메일로 가져오시면 진단이 빨라집니다. 전화 · 네이버 · 카카오톡으로 미리 예약하면 기다리지 않습니다.' ),
);

/* 같은 권역의 다른 지역 (최대 12) */
$siblings = array();
if ( function_exists( 'moondental_get_regions_by_province' ) ) {
	$all = moondental_get_regions_by_province();
	foreach ( ( $all[ $province ] ?? array() ) as $r ) {
		if ( $r['slug'] !== $slug ) $siblings[] = $r;
	}
	$siblings = array_slice( $siblings, 0, 12 );
}
?>

<!-- ============ Hero ============ -->
<section class="md-region-hero md-region-hero--simple">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a> ▸
			<a href="<?php echo esc_url( home_url( '/오시는-길/' ) ); ?>">오시는 길</a> ▸
			<span><?php echo esc_html( $region_name ); ?></span>
		</nav>
		<span class="md-region-hero__eyebrow"><?php echo esc_html( $province ); ?> · 오시는 길</span>
		<h1 class="md-region-hero__title">
			<?php echo esc_html( $region_long ); ?>에서<br>
			<em>문치과병원까지 <?php echo esc_html( $duration_label ); ?></em>
		</h1>
		<p class="md-region-hero__lead">
			천안고속버스터미널 옆 문타워 9~13층입니다. <?php echo esc_html( $is_walking ? '걸어서 오실 수 있는 거리입니다.' : '자동차 기준이며, 기차와 버스로도 올 수 있습니다.' ); ?>
		</p>
		<div class="md-region-hero__badges">
			<span><?php echo $is_walking ? '🚶 ' : '🚗 '; ?><?php echo esc_html( $duration_label ); ?></span>
			<span>🅿️ 문타워 주차장</span>
			<?php if ( $hours_wd ) : ?><span>🌙 평일 <?php echo esc_html( $hours_wd ); ?></span><?php endif; ?>
		</div>
	</div>
</section>

<!-- ============ 1. 오는 길 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<h2 class="md-section-head__title"><?php echo esc_html( $region_name ); ?>에서 오는 길</h2>
		</header>

		<div class="md-region-routes">
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true"><?php echo $is_walking ? '🚶' : '🚗'; ?></span>
					<h3><?php echo $is_walking ? '걸어서' : '자동차로'; ?></h3>
					<span class="md-region-route__time"><?php echo esc_html( $duration_label ); ?></span>
				</div>
				<?php if ( $highway ) : ?><p><?php echo esc_html( $highway ); ?></p><?php endif; ?>
				<p class="md-region-route__detail">내비게이션에 「문치과병원」 또는 「천안시 동남구 만남로 52」를 넣으세요. 문타워 지하 주차장에 세우고 엘리베이터로 9층 데스크로 올라오시면 됩니다.</p>
			</article>

			<?php if ( $ktx ) : ?>
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚆</span>
					<h3>기차로</h3>
					<span class="md-region-route__time md-region-route__time--alt">KTX · 전철</span>
				</div>
				<p><?php echo esc_html( rtrim( $ktx, '.' ) ); ?>.</p>
				<p class="md-region-route__detail">천안역 · 천안아산역에서 병원까지는 버스나 택시로 10~15분입니다.</p>
			</article>
			<?php endif; ?>

			<?php if ( $bus ) : ?>
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚌</span>
					<h3>버스로</h3>
					<span class="md-region-route__time md-region-route__time--alt">시외 · 고속</span>
				</div>
				<p><?php echo esc_html( rtrim( $bus, '.' ) ); ?>.</p>
				<p class="md-region-route__detail">천안고속버스터미널 · 종합터미널에서 내리면 바로 옆 건물이 문타워입니다. 걸어서 5분이 안 걸립니다.</p>
			</article>
			<?php endif; ?>
		</div>

		<?php if ( $note ) : ?>
		<aside class="md-region-callout">
			<strong>📍 <?php echo esc_html( $region_name ); ?>에서 오시는 분께</strong>
			<p><?php echo esc_html( $note ); ?></p>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 2. 자주 묻는 것 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<h2 class="md-section-head__title">오기 전에 자주 묻는 것</h2>
		</header>
		<div class="md-faq">
			<?php $first = true; foreach ( $faq_items as $q ) : ?>
			<details class="md-faq__item"<?php echo $first ? ' open' : ''; ?>>
				<summary><?php echo esc_html( $q[0] ); ?></summary>
				<p><?php echo esc_html( $q[1] ); ?></p>
			</details>
			<?php $first = false; endforeach; ?>
		</div>
	</div>
</section>

<?php
/* 3. 치과 고르는 기준 안내서 — 도시별 안내서가 있는 곳은 그것으로, 천안 동 단위는 천안, 나머지는 통합 */
$md_guide_for_region = function_exists( 'md_guide_for_region' ) ? md_guide_for_region( $region ) : '';
if ( $md_guide_for_region ) {
	get_template_part( 'template-parts/section', 'guide-cta', array(
		'slug'     => $md_guide_for_region,
		'title'    => $region_name . '에서 치과를 고를 때 보는 기준',
		'subtitle' => '진료과 · 장비 · 비용 고지 · 사후관리 · 거리 — 가까운 곳에서 할 치료와 천안까지 올 치료를 나누는 법',
	) );
}
?>

<!-- ============ 4. 같은 권역 다른 지역 ============ -->
<?php if ( $siblings ) : ?>
<section class="md-section md-section--surface md-section--sm">
	<div class="md-container">
		<header class="md-section-head md-section-head--row">
			<h2 class="md-section-head__title"><?php echo esc_html( $province ); ?>의 다른 지역</h2>
			<a class="md-link-more" href="<?php echo esc_url( home_url( '/오시는-길/#regions' ) ); ?>">전체 지역 보기 →</a>
		</header>
		<div class="md-region-chips">
			<?php foreach ( $siblings as $r ) : ?>
				<a class="md-region-chip" href="<?php echo esc_url( home_url( '/오시는-길/' . $r['slug'] . '/' ) ); ?>">
					<span class="md-region-chip__name"><?php echo esc_html( $r['name'] ); ?></span>
					<span class="md-region-chip__time"><?php echo esc_html( ! empty( $r['duration_label'] ) ? $r['duration_label'] : ( $r['duration_min'] . '분' ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
