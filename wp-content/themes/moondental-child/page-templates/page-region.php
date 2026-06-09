<?php
/**
 * 지역별 오시는 길 상세 페이지 — /오시는-길/{slug}/ URL에 의해 로드됨.
 *  moondental_region_intercept() (functions.php) 가 직접 include 하여 호출.
 *
 *  데이터 소스: moondental_get_region_by_slug() (inc/regions.php)
 *  핵심 SEO: 지역명을 hero·H1·H2·본문·meta에 자연스럽게 배치 → 지역 키워드 검색 노출.
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
		<div class="md-container md-container--narrow" style="text-align:center;">
			<h1>지역 정보를 찾을 수 없습니다</h1>
			<p style="margin-top:24px;">
				<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/오시는-길/' ) ); ?>">
					← 오시는 길로 돌아가기
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
$kakao      = $info['kakao_url'] ?? '';
$naver_book = $info['naver_place'] ?? '';
$naver_map  = $info['naver_map_url'] ?: ( 'https://map.naver.com/p/search/' . rawurlencode( '한아의료재단 문치과병원' ) );

$region_name    = $region['name'];          // 예: '아산'
$region_long    = $region['name_long'];     // 예: '아산시'
$province       = $region['province'];      // 예: '충남'
$distance       = $region['distance_km'];
$duration       = $region['duration_min'];
$duration_label = ! empty( $region['duration_label'] ) ? $region['duration_label'] : ( $duration . '분' );
$is_walking     = ! empty( $region['duration_label'] ) && strpos( $region['duration_label'], '도보' ) !== false;
?>

<!-- ============ Hero ============ -->
<section class="md-region-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸
			<a href="<?php echo esc_url( home_url( '/오시는-길/' ) ); ?>">오시는 길</a> ▸
			<span><?php echo esc_html( $region_name ); ?></span>
		</nav>
		<span class="md-region-hero__eyebrow">📍 <?php echo esc_html( $province ); ?> · <?php echo esc_html( $region_long ); ?>에서 오시는 길</span>
		<h1 class="md-region-hero__title">
			<?php echo esc_html( $region_name ); ?>에서 찾는<br>
			<em>임플란트·교정 잘하는 천안 치과</em>
		</h1>
		<p class="md-region-hero__lead">
			<?php if ( $is_walking ) : ?>
				<?php echo esc_html( $region_name ); ?>에서 천안 만남로 <strong>한아의료재단 문치과병원</strong>까지
				<strong><?php echo esc_html( $duration_label ); ?></strong> 거리.<br>
				1995년부터 30여년 한자리 진료 — 분야별 전문 의료진 협진으로 통합 진료해드립니다.
			<?php else : ?>
				<?php echo esc_html( $region_name ); ?>에서 천안 만남로 <strong>한아의료재단 문치과병원</strong>까지
				자동차로 약 <strong><?php echo esc_html( $duration ); ?>분</strong> (<?php echo esc_html( $distance ); ?>km).<br>
				1995년부터 30여년 한자리 진료 — 분야별 전문 의료진 협진으로 통합 진료해드립니다.
			<?php endif; ?>
		</p>
		<div class="md-region-hero__badges">
			<?php if ( $is_walking ) : ?>
				<span>🚶 <?php echo esc_html( $duration_label ); ?></span>
				<span>🚌 시내버스·터미널 근접</span>
			<?php else : ?>
				<span>🚗 자동차 <?php echo esc_html( $duration ); ?>분</span>
				<span>🚌 시외버스 가능</span>
			<?php endif; ?>
			<span>🌙 월·화·수·금 야간진료 20:30까지</span>
		</div>
	</div>
</section>

<!-- ============ 1. 교통 안내 (자가용 + 대중교통) ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">🗺️ 교통 안내</span>
			<h2 class="md-section-head__title"><?php echo esc_html( $region_name ); ?>에서 천안 만남로 문치과병원까지</h2>
			<p class="md-section-head__lead">
				자동차·시외버스·KTX 중 가장 편한 방법으로 오실 수 있습니다.
			</p>
		</header>

		<div class="md-region-routes">
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚗</span>
					<h3>자가용으로 오시는 길</h3>
					<span class="md-region-route__time"><?php echo esc_html( $duration ); ?>분</span>
				</div>
				<p><strong><?php echo esc_html( $region_name ); ?></strong>에서 천안 만남로 문치과병원까지 자동차로 약 <strong><?php echo esc_html( $duration ); ?>분</strong>, 거리 약 <?php echo esc_html( $distance ); ?>km.</p>
				<p class="md-region-route__detail"><strong>주요 경로</strong>: <?php echo esc_html( $region['highway'] ); ?> 이용</p>
				<p class="md-region-route__detail"><strong>주차</strong>: 본원 지하 기계식 주차장 무료 / SUV·대형차는 신부 제5공영주차장(동남구 먹거리1길 10) 무료 등록</p>
			</article>

			<?php if ( ! empty( $region['ktx'] ) ) : ?>
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚆</span>
					<h3>KTX·기차로 오시는 길</h3>
					<span class="md-region-route__time md-region-route__time--alt">기차</span>
				</div>
				<p><?php echo esc_html( $region['ktx'] ); ?>.</p>
				<p class="md-region-route__detail">천안역 또는 천안아산역 도착 후 시내버스 또는 택시로 신부동 문타워까지 약 10~15분.</p>
			</article>
			<?php endif; ?>

			<?php if ( ! empty( $region['bus'] ) ) : ?>
			<article class="md-region-route">
				<div class="md-region-route__head">
					<span class="md-region-route__icon" aria-hidden="true">🚌</span>
					<h3>시외버스로 오시는 길</h3>
					<span class="md-region-route__time md-region-route__time--alt">버스</span>
				</div>
				<p><?php echo esc_html( $region['bus'] ); ?>.</p>
				<p class="md-region-route__detail">천안종합·고속버스터미널에서 문치과병원까지 도보 약 5분.</p>
			</article>
			<?php endif; ?>
		</div>

		<aside class="md-region-callout">
			<strong>📍 <?php echo esc_html( $region_name ); ?> 환자분께</strong>
			<p><?php echo esc_html( $region['note'] ); ?>. 천안 만남로 문타워 9·10·11·13층, 4개 층 통합 진료센터에서 분야별 전문 의료진의 협진을 받으실 수 있습니다.</p>
		</aside>
	</div>
</section>

<!-- ============ 2. <지역명>에서 문치과병원을 선택하는 이유 ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">✨ 우리 병원을 선택하는 이유</span>
			<h2 class="md-section-head__title"><?php echo esc_html( $region_name ); ?>에서 문치과병원을 선택하시는 이유</h2>
		</header>

		<div class="md-region-reasons">
			<article class="md-region-reason">
				<div class="md-region-reason__num">01</div>
				<h3>🦷 30여년 임상 경험</h3>
				<p><?php echo esc_html( $region_name ); ?>에서 천안까지 오시는 데는 이유가 있습니다. 1995년 개원부터 30여년 한자리 진료로 누적된 임상 경험.</p>
			</article>
			<article class="md-region-reason">
				<div class="md-region-reason__num">02</div>
				<h3>👨‍⚕️ 분야별 전문 의료진 협진</h3>
				<p>보철·교정·보존·치주·소아·외과 전 분야 의료진이 한 케이스를 함께 보는 협진 시스템. <?php echo esc_html( $region_name ); ?>에서 따로따로 다닐 필요 없습니다.</p>
			</article>
			<article class="md-region-reason">
				<div class="md-region-reason__num">03</div>
				<h3>🔬 CBCT 디지털 진단</h3>
				<p>3D CBCT·디지털 가이드 수술·구강 스캐너 — 정확한 진단과 안전한 수술. <?php echo esc_html( $region_name ); ?>에서 정밀 진단이 필요한 케이스에 추천.</p>
			</article>
			<article class="md-region-reason">
				<div class="md-region-reason__num">04</div>
				<h3>⚙️ 자체 보철 제작</h3>
				<p>13층 한아 임플란트 보철연구소 원내 직접 제작. 빠른 수정·정확한 의사소통·품질 일관성 — <?php echo esc_html( $region_name ); ?>에서 오신 분들도 한 번에 끝.</p>
			</article>
			<article class="md-region-reason">
				<div class="md-region-reason__num">05</div>
				<h3>❤️ 전신질환 안심 진료</h3>
				<p>혈압·당검사·심전도·산소포화도 상시 측정. 고혈압·당뇨·심장질환자도 <?php echo esc_html( $region_name ); ?>에서 오셔서 안심하고 진료받으실 수 있습니다.</p>
			</article>
			<article class="md-region-reason">
				<div class="md-region-reason__num">06</div>
				<h3>🌙 평일 야간진료</h3>
				<p>월·화·수·금 09:00~20:30 점심시간 없이 진료 · 목 09:00~18:00 · 토 09:00~14:00. <?php echo esc_html( $region_name ); ?>에서 퇴근 후 출발하셔도 충분한 진료 시간.</p>
			</article>
		</div>
	</div>
</section>

<!-- ============ 3. 인기 진료 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">🦷 인기 진료</span>
			<h2 class="md-section-head__title"><?php echo esc_html( $region_name ); ?>에서 오시는 환자분들의 인기 진료</h2>
			<p class="md-section-head__lead">
				<?php echo esc_html( $region_name ); ?>에서 천안까지 오시는 분들이 자주 받으시는 진료입니다.
			</p>
		</header>

		<div class="md-service-grid">
			<?php
			$popular = array(
				array( 'slug' => '임플란트-센터', 'title' => $region_name . ' 임플란트', 'icon' => '🦷', 'desc' => $region_name . '에서 정밀 임플란트 — CBCT 디지털 가이드·자체 보철 제작·30여년 임상.' ),
				array( 'slug' => '투명교정-센터', 'title' => $region_name . ' 투명교정', 'icon' => '✨', 'desc' => $region_name . '에서 슈어스마일 SureSmile 투명교정 — Dentsply Sirona AI 시뮬레이션.' ),
				array( 'slug' => '심미치료',     'title' => $region_name . ' 라미네이트', 'icon' => '💎', 'desc' => $region_name . '에서 자연스러운 미소 — 최소 삭제 라미네이트·미백·심미 보철.' ),
				array( 'slug' => '자연치아-살리기', 'title' => $region_name . ' 자연치아 살리기', 'icon' => '🌿', 'desc' => $region_name . '에서 신경치료·재근관치료 — 발치보다 보존 우선.' ),
				array( 'slug' => '사랑니-발치',   'title' => $region_name . ' 사랑니 발치', 'icon' => '🦴', 'desc' => $region_name . '에서 매복 사랑니까지 — CBCT 안전 진단 + 진정요법.' ),
			);
			foreach ( $popular as $idx => $svc ) :
				$page = get_page_by_path( $svc['slug'] );
				$url  = $page ? get_permalink( $page ) : home_url( '/' . rawurlencode( $svc['slug'] ) . '/' );
				$num  = sprintf( '%02d', $idx + 1 );
			?>
				<article class="md-service-card">
					<span class="md-service-card__num" aria-hidden="true"><?php echo esc_html( $num ); ?></span>
					<div class="md-service-card__icon" aria-hidden="true"><?php echo $svc['icon']; ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $svc['title'] ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $svc['desc'] ); ?></p>
					<span class="md-service-card__more" aria-hidden="true">자세히 보기 <span class="md-service-card__arrow">→</span></span>
					<a class="md-service-card__link" href="<?php echo esc_url( $url ); ?>">
						<span class="md-screen-reader-text"><?php echo esc_html( $svc['title'] ); ?> 자세히 보기</span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ============ 4. 예약 CTA ============ -->
<section class="md-section md-section--sm" id="region-cta">
	<div class="md-container md-container--narrow">
		<div class="md-region-cta">
			<span class="md-region-cta__chip">📅 365일 24시간 온라인 예약 가능</span>
			<h2 class="md-region-cta__title">
				<?php echo esc_html( $region_name ); ?>에서 천안 문치과병원까지<br>
				지금 바로 상담 받아보세요
			</h2>
			<p class="md-region-cta__lead">
				네이버 예약 24시간 자동 / 전화·카카오톡 상담
			</p>
			<?php echo md_render_reservation_ctas( array( 'track' => 'cta-region-' . $slug, 'size' => 'lg', 'align' => 'center' ) ); ?>
			<p class="md-region-cta__hint">진료시간: 월·화·수·금 09:00–20:30 · 목 09:00–18:00 · 토 09:00–14:00 · 일/공휴일 휴진</p>
			<p class="md-region-cta__hint" style="opacity:0.85; margin-top:6px;">📍 천안 만남로 52 문타워 9·10·11·13층</p>
		</div>
	</div>
</section>

<!-- ============ 5. 지역 FAQ ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">❓ 자주 묻는 질문</span>
			<h2 class="md-section-head__title"><?php echo esc_html( $region_name ); ?> 환자분들이 자주 물어보시는 질문</h2>
		</header>

		<div class="md-faq">
			<details class="md-faq__item" open>
				<summary><?php echo esc_html( $region_name ); ?>에서 천안 문치과병원까지 얼마나 걸리나요?</summary>
				<p>자동차로 약 <strong><?php echo esc_html( $duration ); ?>분</strong>, 거리 약 <?php echo esc_html( $distance ); ?>km입니다. 주요 경로는 <?php echo esc_html( $region['highway'] ); ?> 이용. 시외버스·KTX로도 천안종합터미널 또는 천안역 도착 후 도보 5분 거리입니다.</p>
			</details>

			<details class="md-faq__item">
				<summary><?php echo esc_html( $region_name ); ?>에서 갈 만한 임플란트 잘하는 치과인가요?</summary>
				<p>네, <?php echo esc_html( $region_name ); ?>에서 임플란트 진료받으러 오시는 환자분이 많습니다. 1995년 개원 30여년 임상, CBCT 디지털 가이드 수술, 13층 자체 한아 임플란트 보철연구소에서 보철 직접 제작 — 다른 지역에서 오셔도 한 번 방문으로 진단부터 보철까지 진행할 수 있도록 시스템이 갖춰져 있습니다.</p>
			</details>

			<details class="md-faq__item">
				<summary><?php echo esc_html( $region_name ); ?>에서 주차가 가능한가요?</summary>
				<p>네, 본원 지하 기계식 주차장을 <strong>무료</strong>로 이용하실 수 있습니다. SUV·대형차는 인근 <a href="https://map.naver.com/p/search/%EC%8B%A0%EB%B6%80%20%EC%A0%9C5%EA%B3%B5%EC%98%81%EC%A3%BC%EC%B0%A8%EC%9E%A5" target="_blank" rel="noopener" class="md-addr-link">신부 제5공영주차장</a>(동남구 먹거리1길 10)에 주차하시고 데스크에 접수하시면 무료 등록을 도와드립니다.</p>
			</details>

			<details class="md-faq__item">
				<summary><?php echo esc_html( $region_name ); ?>에서 야간이나 주말에도 진료 가능한가요?</summary>
				<p>네, 평일(월·화·수·금)은 <strong>09:00~20:30</strong>까지 점심시간 없이 진료합니다. <?php echo esc_html( $region_name ); ?>에서 퇴근 후 출발하셔도 충분한 시간입니다. 토요일은 09:00~14:00, 일요일·공휴일은 휴진입니다.</p>
			</details>

			<details class="md-faq__item">
				<summary><?php echo esc_html( $region_name ); ?>에서 첫 진료 시 무엇이 필요한가요?</summary>
				<p>신분증(또는 건강보험증)을 지참해주세요. 복용 중인 약이 있다면 약 정보, 타원 X-ray 파일(USB·이메일)이 있으면 진단 시간이 단축됩니다. 사전 예약은 네이버 예약, 전화(<?php echo md_phone_link(); ?>), 카카오톡 채널로 가능합니다.</p>
			</details>
		</div>
	</div>
</section>

<!-- ============ 6. 다른 지역에서 오시는 길 ============ -->
<?php if ( function_exists( 'moondental_get_regions_by_province' ) ) : ?>
<section class="md-section md-section--surface md-section--sm">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">🌐 다른 지역에서 오시는 길</span>
			<h2 class="md-section-head__title">다른 지역에서 천안 문치과병원까지</h2>
		</header>
		<div class="md-region-grid">
			<?php
			$all_regions = moondental_get_regions_by_province();
			foreach ( $all_regions as $prov => $list ) :
				foreach ( $list as $r ) :
					if ( $r['slug'] === $slug ) continue; ?>
					<a class="md-region-pill" href="<?php echo esc_url( home_url( '/오시는-길/' . $r['slug'] . '/' ) ); ?>">
						<span class="md-region-pill__icon" aria-hidden="true">🚗</span>
						<span class="md-region-pill__name"><?php echo esc_html( $r['name'] ); ?></span>
						<span class="md-region-pill__time"><?php echo esc_html( ! empty( $r['duration_label'] ) ? $r['duration_label'] : ( $r['duration_min'] . '분' ) ); ?></span>
					</a>
			<?php endforeach; endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
