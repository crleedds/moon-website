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
	array( '예약하고 가야 하나요?', '예약 없이 와도 진료는 받을 수 있지만, 멀리서 오시는 분은 전화(041-563-2875) · 네이버 예약 · 카카오톡으로 미리 시간을 잡으면 기다리지 않습니다. 예약할 때 「' . $region_name . '에서 간다」고 말씀하시면 한 번에 볼 수 있는 진료를 묶어 드립니다.' ),
	array( 'SUV · 대형차는 어디에 세우나요?', '병원 지하 주차장은 기계식이라 SUV · 대형차가 들어가지 못합니다. 걸어서 5분 거리의 신부 제5공영주차장(동남구 먹거리1길 10)에 세운 뒤 접수처에서 주차 도장 · 주차권을 받으면 무료입니다.' ),
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
		<?php $is_city_all = false !== strpos( $region_long, '전 지역' ); ?>
		<h1 class="md-region-hero__title">
			<?php echo esc_html( $is_city_all ? $region_name . ' 어디서나' : $region_long . '에서' ); ?><br>
			<em>문치과병원까지 <?php echo esc_html( $duration_label ); ?><?php echo $is_city_all ? ' 안팎' : ''; ?></em>
		</h1>
		<p class="md-region-hero__lead">
			천안고속버스터미널 옆 문타워 9~13층입니다. <?php echo esc_html( $is_walking ? '걸어서 오실 수 있는 거리입니다.' : '자동차 기준이며, 기차와 버스로도 올 수 있습니다.' ); ?>
		</p>
		<div class="md-region-hero__badges">
			<span><?php echo $is_walking ? '🚶 ' : '🚗 '; ?><?php echo esc_html( $duration_label ); ?></span>
			<span>🅿️ 무료 주차 · SUV는 제5공영주차장</span>
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
				<p class="md-region-route__detail">내비게이션에 「문치과병원」 또는 「천안시 동남구 만남로 52」를 넣으세요. 승용차는 병원 지하 기계식 주차장(무료 · 데스크 접수 시 등록), SUV · 대형차는 걸어서 5분 거리의 신부 제5공영주차장(먹거리1길 10 · 접수처에서 주차권)을 이용하세요.</p>
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

		<?php
		/* v4.15 · 길찾기 — 구글은 출발지를 이 지역으로 미리 넣는다 */
		$dest_q   = rawurlencode( '문치과병원 천안시 동남구 만남로 52' );
		$origin_q = rawurlencode( $is_city_all ? $region_name . '시' : $region_long ); /* '(전 지역)' 은 출발지로 못 쓴다 */
		$dir_google = 'https://www.google.com/maps/dir/?api=1&origin=' . $origin_q . '&destination=' . $dest_q . '&travelmode=' . ( $is_walking ? 'walking' : 'driving' );
		$dir_naver  = $info['naver_map_url'] ?: 'https://map.naver.com/p/search/' . rawurlencode( '문치과병원' );
		$dir_kakao  = 'https://map.kakao.com/?q=' . rawurlencode( '문치과병원 천안' );
		?>
		<div class="md-mapbtn-grid md-mapbtn-grid--top md-mapbtn-grid--region">
			<a class="md-mapbtn md-mapbtn--google" href="<?php echo esc_url( $dir_google ); ?>" target="_blank" rel="noopener" data-track="cta-region-dir-google">
				<span class="md-mapbtn__logo" aria-hidden="true">G</span>
				<span class="md-mapbtn__body"><span class="md-mapbtn__name"><?php echo esc_html( $region_name ); ?> → 문치과병원 길찾기</span><span class="md-mapbtn__sub">Google 지도 · 출발지 입력돼 있음</span></span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--naver" href="<?php echo esc_url( $dir_naver ); ?>" target="_blank" rel="noopener" data-track="cta-region-dir-naver">
				<span class="md-mapbtn__logo" aria-hidden="true">N</span>
				<span class="md-mapbtn__body"><span class="md-mapbtn__name">네이버 지도</span><span class="md-mapbtn__sub">길찾기 · 대중교통</span></span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
			<a class="md-mapbtn md-mapbtn--kakao" href="<?php echo esc_url( $dir_kakao ); ?>" target="_blank" rel="noopener" data-track="cta-region-dir-kakao">
				<span class="md-mapbtn__logo" aria-hidden="true">k</span>
				<span class="md-mapbtn__body"><span class="md-mapbtn__name">카카오맵</span><span class="md-mapbtn__sub">길찾기 · 로드뷰</span></span>
				<span class="md-mapbtn__arrow" aria-hidden="true">→</span>
			</a>
		</div>

		<?php if ( $note ) : ?>
		<aside class="md-region-callout">
			<strong>📍 <?php echo esc_html( $region_name ); ?>에서 오시는 분께</strong>
			<p><?php echo esc_html( $note ); ?></p>
		</aside>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 1-1. 진료시간 · 주차 (v4.15 · 오시는 길 페이지와 같은 카드) ============ -->
<section class="md-section md-section--sm">
	<div class="md-container">
		<div class="md-info-pair md-region-pair">
			<aside class="md-hours">
				<header class="md-hours__head"><span class="md-hours__badge">🕐 진료시간</span></header>
				<?php $today_dow = (int) wp_date( 'w' ); $tr = function_exists( 'moondental_extract_time_range' ) ? 'moondental_extract_time_range' : 'strval'; ?>
				<ul class="md-hours__list">
					<li<?php echo in_array( $today_dow, array( 1, 2, 3, 5 ), true ) ? ' class="is-today"' : ''; ?>><span class="md-hours__day">평일 (월·화·수·금)</span><span class="md-hours__time"><?php echo esc_html( $tr( $hours_wd ) ); ?></span></li>
					<li<?php echo 4 === $today_dow ? ' class="is-today"' : ''; ?>><span class="md-hours__day">목요일</span><span class="md-hours__time"><?php echo esc_html( $tr( $hours_thu ) ); ?></span></li>
					<li<?php echo 6 === $today_dow ? ' class="is-today"' : ''; ?>><span class="md-hours__day">토요일</span><span class="md-hours__time"><?php echo esc_html( $tr( $hours_sat ) ); ?></span></li>
					<li class="md-hours__off<?php echo 0 === $today_dow ? ' is-today' : ''; ?>"><span class="md-hours__day">일요일</span><span class="md-hours__time">휴진</span></li>
				</ul>
				<p class="md-hours__note-naver">공휴일 진료 · 휴진은 네이버 플레이스에서 최종 확인해 주세요. 멀리서 오실 때는 전화로 한 번 더 확인하면 안전합니다.</p>
			</aside>
			<aside class="md-park md-park--compact">
				<header class="md-park__head"><span class="md-park__badge">🅿️ 무료 주차 안내</span></header>
				<ul class="md-park__list">
					<li><span class="md-park__num">01</span><div><strong>병원 지하 기계식 주차장 (SUV 불가)</strong><span>천안시 동남구 만남로 52 문타워 · 데스크 접수 시 무료 등록</span></div></li>
					<li><span class="md-park__num">02</span><div><strong>신부 제5공영주차장 (SUV 가능)</strong><span>천안시 동남구 먹거리1길 10 · 도보 5분</span></div></li>
				</ul>
				<p class="md-park__lead md-park__lead--note">🎫 주차 후 병원 접수처에서 주차도장/주차권을 받아가세요</p>
			</aside>
		</div>
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
