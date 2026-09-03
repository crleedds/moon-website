<?php
/**
 * Template Name: 진료 영역 페이지
 * Template Post Type: page
 *
 * 일반진료/임플란트/교정/심미/소아예방 등 진료 페이지에 할당.
 * 페이지 슬러그(general, implant, ortho, aesthetic, pediatric)에 따라
 * 자동으로 아이콘/요약을 매칭. WP 에디터 본문이 상세 설명으로 들어간다.
 *
 * @package moondental-child
 */

get_header();

$services    = moondental_get_services();
$slug        = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
$current_svc = null;
foreach ( $services as $svc ) {
	if ( $svc['slug'] === $slug ) {
		$current_svc = $svc;
		break;
	}
}
?>

<section class="md-page-hero" aria-label="<?php the_title_attribute(); ?>">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( md_content( 'breadcrumb_home', '홈' ) ); ?></a>
			 ▸ <a href="<?php echo esc_url( home_url( '/진료항목/' ) ); ?>">진료안내</a>
			 ▸ <span><?php the_title(); ?></span>
		</nav>
		<?php if ( $current_svc ) : ?>
			<div class="md-page-hero__icon" aria-hidden="true"><?php echo moondental_render_icon( $current_svc['icon'] ); ?></div>
		<?php endif; ?>
		<?php
		// v3.44.68 · 층 배지 (해당 서비스가 몇 층에 있는지)
		$_floor = function_exists( 'moondental_slug_floor' ) ? moondental_slug_floor( $slug ) : '';
		if ( $_floor ) :
		?>
			<span class="md-service-floor-badge" aria-label="위치"><span aria-hidden="true">📍</span> 문타워 <?php echo esc_html( $_floor ); ?></span>
		<?php endif; ?>
		<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		<?php if ( $current_svc ) : ?>
			<p class="md-page-hero__lead"><?php echo esc_html( $current_svc['desc'] ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php
/* v3.44.30 · 강제 히어로 이미지 + 임팩트 스탯 (페이지 콘텐츠와 무관하게 항상 노출) */
$_visual = function_exists( 'moondental_service_visual' ) ? moondental_service_visual( $slug ) : null;
if ( $_visual ) :
?>
<section class="md-svc-hero" aria-label="<?php echo esc_attr( $_visual['headline'] ); ?>">
	<div class="md-container">
		<?php if ( ! empty( $_visual['image'] ) ) : ?>
			<figure class="md-svc-hero__figure">
				<img src="<?php echo esc_url( $_visual['image'] ); ?>" alt="<?php echo esc_attr( $_visual['alt'] ); ?>" loading="eager" fetchpriority="high" decoding="async">
			</figure>
		<?php endif; ?>
		<div class="md-svc-hero__body">
			<h2 class="md-svc-hero__headline"><?php echo esc_html( $_visual['headline'] ); ?></h2>
			<p class="md-svc-hero__sub"><?php echo esc_html( $_visual['sub'] ); ?></p>
			<?php if ( ! empty( $_visual['stats'] ) ) : ?>
				<ul class="md-svc-hero__stats" aria-label="핵심 지표">
					<?php foreach ( $_visual['stats'] as $s ) : ?>
						<li>
							<span class="md-svc-hero__stat-value"><?php echo esc_html( $s['value'] ); ?></span>
							<?php if ( ! empty( $s['unit'] ) ) : ?>
								<span class="md-svc-hero__stat-unit"><?php echo esc_html( $s['unit'] ); ?></span>
							<?php endif; ?>
							<span class="md-svc-hero__stat-label"><?php echo esc_html( $s['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="md-section">
	<div class="md-container md-container--narrow">
		<article class="md-page-content">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				} else {
					echo moondental_default_service_content( $slug ); // 안전한 정적 HTML
				}
			endwhile;
			?>
		</article>
	</div>
</section>

<?php
/* v3.46 · 임플란트-센터 페이지 전용 · 30여년 임플란트 노하우 / 재수복 섹션
 *   1990년대부터 식립해 온 이력 → 오래된·타원·실패 임플란트 재수복 강점을 전면화.
 *   목록은 서버에서 전부 렌더하고 JS 는 필터만 담당한다(무JS·SEO 대응). */
if ( $slug === '임플란트-센터' ) :

	/* 연결부 형상 — 도면 심볼 키 · 라벨 */
	$md_impl_conn = array(
		'ext'   => '외부육각',
		'int'   => '내부육각·팔각',
		'taper' => '모스 테이퍼',
		'lock'  => '락킹 테이퍼',
	);

	/* 다뤄 온 대표 임플란트 계열 (대표 예시 · 전수 아님) */
	$md_impl_systems = array(
		array( 'ko' => '브레네막', 'en' => 'Brånemark System', 'origin' => '수입', 'country' => '스웨덴', 'conn' => 'ext', 'eras' => '90 00', 'span' => '1990년대~',
			'note' => '골유착 임플란트의 원형. 외부육각 연결과 매끈한 기계가공 표면이 특징입니다. <strong>국내에서 가장 오래된 임플란트 상당수가 이 계열</strong>이며, 부품 수급이 어려워 맞춤 지대주 제작이 필요한 경우가 많습니다.' ),
		array( 'ko' => '스트라우만 티슈레벨', 'en' => 'Straumann Tissue Level', 'origin' => '수입', 'country' => '스위스', 'conn' => 'int', 'eras' => '90 00 10', 'span' => '1990년대~',
			'note' => '잇몸 높이까지 올라오는 목 구조와 내부 팔각(synOcta) 연결. 오래 유지된 케이스가 많고, 보철만 교체하는 재수복이 비교적 수월한 편입니다.' ),
		array( 'ko' => '스트라우만 본레벨', 'en' => 'Straumann Bone Level', 'origin' => '수입', 'country' => '스위스', 'conn' => 'taper', 'eras' => '10 20', 'span' => '2010년대~',
			'note' => '원뿔형 내부 연결(CrossFit)로 변연골 보존을 노린 설계. 같은 회사 제품이어도 티슈레벨과 부품이 호환되지 않아 세대 구분이 반드시 필요합니다.' ),
		array( 'ko' => '노벨 리플레이스 · 액티브', 'en' => 'Nobel Replace / NobelActive', 'origin' => '수입', 'country' => '스웨덴', 'conn' => 'int', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '삼각 홈 형태의 내부 연결로, 지대주를 끼우는 방향이 정해져 있는 것이 특징입니다. 라인별 규격 차이가 커 정확한 판독이 중요합니다.' ),
		array( 'ko' => '아스트라텍', 'en' => 'Astra Tech / Dentsply', 'origin' => '수입', 'country' => '스웨덴', 'conn' => 'taper', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '코니컬 씰 방식의 원뿔 결합으로 미세 틈이 적고 변연골 유지가 좋은 계열. 결합이 단단해 분리 시 전용 기구가 필요합니다.' ),
		array( 'ko' => '쓰리아이', 'en' => 'Biomet 3i (Osseotite)', 'origin' => '수입', 'country' => '미국', 'conn' => 'ext', 'eras' => '90 00', 'span' => '1990년대~',
			'note' => '산 부식 표면 처리를 대중화한 계열. 외부육각 구조로 장기 사용 시 지대주 나사 풀림이 나타나는 경우가 있습니다.' ),
		array( 'ko' => '짐머', 'en' => 'Zimmer Screw-Vent', 'origin' => '수입', 'country' => '미국', 'conn' => 'int', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '내부 육각과 마찰 결합을 함께 쓰는 구조. 국내 식립 수는 많지 않지만 해외에서 치료받고 귀국한 환자분에게서 종종 확인됩니다.' ),
		array( 'ko' => '앙키로스', 'en' => 'Ankylos', 'origin' => '수입', 'country' => '독일', 'conn' => 'taper', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '깊은 모스 테이퍼 결합으로 지대주를 원하는 각도로 돌려 끼울 수 있는 계열. 보철 재제작 시 각도 재현이 관건입니다.' ),
		array( 'ko' => '캄로그 · 자이브', 'en' => 'Camlog / Xive', 'origin' => '수입', 'country' => '독일', 'conn' => 'int', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '튜브 인 튜브 형태의 내부 연결. 전용 드라이버가 아니면 체결이 어려워, 부품 보유 여부가 재수복 가능성을 좌우합니다.' ),
		array( 'ko' => '바이콘', 'en' => 'Bicon', 'origin' => '수입', 'country' => '미국', 'conn' => 'lock', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '<strong>나사를 쓰지 않고 원뿔 마찰만으로 결합</strong>하는 독특한 계열. 구조를 모르면 지대주를 분리하는 것부터 막히기 때문에 사전 판독이 특히 중요합니다.' ),
		array( 'ko' => '엠아이에스 · 알파바이오', 'en' => 'MIS / Alpha-Bio', 'origin' => '수입', 'country' => '이스라엘', 'conn' => 'int', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '보급형으로 널리 쓰인 내부육각 계열. 규격이 유사한 제품이 많아 육안만으로는 구분이 어려워 영상 대조가 필요합니다.' ),
		array( 'ko' => '미니 임플란트', 'en' => 'Mini Implant (MDI 계열)', 'origin' => '수입', 'country' => '미국', 'conn' => 'lock', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '픽스처와 지대주가 한 몸인 일체형 소구경 임플란트. 주로 틀니 고정에 쓰였으며, 파절이나 골소실 시 별도의 접근이 필요합니다.' ),
		array( 'ko' => '오스템', 'en' => 'Osstem US · GS · TS · KS', 'origin' => '국산', 'country' => '대한민국', 'conn' => 'int', 'eras' => '00 10 20', 'span' => '2000년대~',
			'note' => '국내 식립 수가 가장 많은 계열로, 세대별 라인 차이가 큽니다. 문치과병원은 현재 식립에 <strong>최상위 KS 라인</strong>만 사용합니다.' ),
		array( 'ko' => '덴티움', 'en' => 'Dentium Implantium · SuperLine', 'origin' => '국산', 'country' => '대한민국', 'conn' => 'int', 'eras' => '00 10 20', 'span' => '2000년대~',
			'note' => '내부육각 기반의 대표적인 국산 계열. 2000년대 중반 이후 식립분이 지금 재수복 시기에 접어들고 있습니다.' ),
		array( 'ko' => '메가젠', 'en' => 'MegaGen AnyRidge · Blue Diamond', 'origin' => '국산', 'country' => '대한민국', 'conn' => 'int', 'eras' => '10 20', 'span' => '2010년대~',
			'note' => '깊은 나사산 구조로 골질이 약한 경우에 강점이 있는 계열. 칼슘 이온 표면처리 라인은 현재 식립에도 사용합니다.' ),
		array( 'ko' => '네오바이오텍', 'en' => 'Neobiotech IS-II · IS-III', 'origin' => '국산', 'country' => '대한민국', 'conn' => 'int', 'eras' => '00 10 20', 'span' => '2000년대~',
			'note' => '국내에서 널리 쓰인 계열이며, 같은 회사의 <strong>제거용 리무버 키트</strong>는 실패한 임플란트를 뼈 손상 없이 빼낼 때 표준처럼 쓰입니다.' ),
		array( 'ko' => '디오', 'en' => 'DIO (UF · SM)', 'origin' => '국산', 'country' => '대한민국', 'conn' => 'int', 'eras' => '00 10 20', 'span' => '2000년대~',
			'note' => '디지털 가이드 수술과 함께 보급된 계열. 가이드 전용 부품 체계가 별도로 있어 판독 시 함께 확인합니다.' ),
		array( 'ko' => '워랜텍 · 중소 제조사 계열', 'en' => 'Warantec 외', 'origin' => '국산', 'country' => '대한민국', 'conn' => 'int', 'eras' => '00 10', 'span' => '2000년대~',
			'note' => '중소 제조사 계열은 라인 단종이 잦아 기성 지대주를 구하기 어려운 경우가 많습니다. <strong>원내 기공소의 맞춤 지대주 제작</strong>이 해법이 됩니다.' ),
		array( 'ko' => '스테리오스', 'en' => 'Stereos', 'origin' => '국산', 'country' => '현재 식립 라인', 'conn' => 'taper', 'eras' => '10 20', 'span' => '2010년대~',
			'note' => '테이퍼드 디자인으로 초기 고정력이 우수하며, 잔존골이 3mm만 있어도 식립을 시도할 수 있어 상악동 인접 케이스에 유리합니다.' ),
		array( 'ko' => '포인트', 'en' => 'Point', 'origin' => '국산', 'country' => '현재 식립 라인', 'conn' => 'int', 'eras' => '10 20', 'span' => '2010년대~',
			'note' => 'UV 표면처리로 초친수성을 활성화해 골유착 속도를 높인 계열. 조기 안정화가 필요한 케이스에 선택합니다.' ),
	);
	$md_impl_total = count( $md_impl_systems );
	?>
<section class="md-section md-implant-legacy" aria-label="30여년 임플란트 노하우와 재수복">
	<div class="md-container md-container--narrow">

		<header class="md-section-head">
			<span class="md-section-head__eyebrow md-eyebrow--star">⭐ 문치과병원 특화 · 1990년대부터의 임플란트</span>
			<h2 class="md-section-head__title">1995년에 심은 임플란트도, 여기서는 기록이 남아 있습니다</h2>
			<p class="md-section-head__lead">
				문치과병원은 1990년대부터 임플란트를 식립해 왔습니다. 그 30여 년 동안 국내외 거의 모든 계열의 임플란트를 다뤘고,
				그래서 지금은 <strong>오래된 임플란트, 어디서 심었는지 모르는 임플란트, 실패했다고 들은 임플란트</strong>를
				다시 살리는 진료가 임플란트센터의 가장 큰 강점이 되었습니다.
			</p>
		</header>

		<!-- 도면 심볼 정의 (연결부 형상) -->
		<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
			<defs>
				<g id="md-impl-body">
					<path d="M5.6 8.4h12.8l-1.5 12a2.2 2.2 0 0 1-2.2 1.9H9.3a2.2 2.2 0 0 1-2.2-1.9z"/>
					<path d="M6.5 12.4h11M6.9 15.9h10.2M7.3 19.4h9.4"/>
				</g>
				<symbol id="md-impl-ext" viewBox="0 0 24 24"><rect x="9.6" y="3.2" width="4.8" height="5.2" rx=".6"/><use href="#md-impl-body"/></symbol>
				<symbol id="md-impl-int" viewBox="0 0 24 24"><path d="M9.6 8.4v4.4h4.8V8.4"/><use href="#md-impl-body"/></symbol>
				<symbol id="md-impl-taper" viewBox="0 0 24 24"><path d="M9.5 8.4l1.4 4.9h2.2l1.4-4.9"/><use href="#md-impl-body"/></symbol>
				<symbol id="md-impl-lock" viewBox="0 0 24 24"><path d="M9.1 8.4l1.7 7.2h2.4l1.7-7.2"/><use href="#md-impl-body"/></symbol>
			</defs>
		</svg>

		<!-- 핵심 지표 -->
		<div class="md-implant-stats">
			<div class="md-implant-stat">
				<span class="md-implant-stat__value">1995<span class="md-implant-stat__unit">년</span></span>
				<span class="md-implant-stat__label">개원 · 천안 만남로</span>
			</div>
			<div class="md-implant-stat">
				<span class="md-implant-stat__value">30<span class="md-implant-stat__unit">여 년</span></span>
				<span class="md-implant-stat__label">임플란트 임상 경험</span>
			</div>
			<div class="md-implant-stat">
				<span class="md-implant-stat__value"><?php echo (int) $md_impl_total; ?><sup>+</sup></span>
				<span class="md-implant-stat__label">다뤄 온 국내외 임플란트 계열</span>
			</div>
			<div class="md-implant-stat">
				<span class="md-implant-stat__value">±0.5<span class="md-implant-stat__unit">mm</span></span>
				<span class="md-implant-stat__label">네비게이션 가이드 정확도</span>
			</div>
		</div>

		<!-- ── 임플란트 연대기 ───────────────────────────────── -->
		<h3 class="md-implant-h3">임플란트에도 세대가 있습니다</h3>
		<p class="md-implant-sub">
			20년 전에 심은 임플란트와 작년에 심은 임플란트는 나사 규격도, 표면 처리도, 잇몸에 앉는 방식도 전혀 다릅니다.
			그 차이를 아는 것이 오래된 임플란트를 다루는 출발점입니다. 문치과병원은 아래 네 시기를 모두 현장에서 지나왔습니다.
		</p>

		<div class="md-implant-eras">
			<article class="md-implant-era">
				<span class="md-implant-era__yr">1990s</span>
				<h4>외부 연결의 시대</h4>
				<dl>
					<dt>그때의 표준</dt>
					<dd>브레네막 계열의 외부육각(External Hex) 연결, 매끈한 기계가공 표면, 뼈가 붙기를 기다렸다가 잇몸을 한 번 더 여는 2회법 수술.</dd>
					<dt>지금의 과제</dt>
					<dd class="is-now">연결부가 짧아 지대주 나사가 자주 풀리고, 부품이 단종되어 크라운만 새로 만들려 해도 맞는 지대주를 구하기 어렵습니다.</dd>
					<dt>문치과의 대응</dt>
					<dd class="is-ours">규격을 판독해 원내 기공소에서 맞춤 지대주를 직접 제작합니다. 픽스처가 건강하면 뽑지 않고 위쪽만 새로 만듭니다.</dd>
				</dl>
			</article>
			<article class="md-implant-era">
				<span class="md-implant-era__yr">2000s</span>
				<h4>내부 연결과 국산화</h4>
				<dl>
					<dt>그때의 표준</dt>
					<dd>내부육각·팔각 연결이 자리 잡고 SLA·RBM 등 거친 표면이 보편화됩니다. 국산 임플란트가 본격 보급되며 식립 건수가 급증한 시기입니다.</dd>
					<dt>지금의 과제</dt>
					<dd class="is-now">식립 후 15~25년이 지나며 임플란트 주위염과 나사 파절이 가장 많이 나타나는 세대입니다.</dd>
					<dt>문치과의 대응</dt>
					<dd class="is-ours">Flap으로 뼈 상태를 직접 확인하고 물방울 레이저로 표면을 소독한 뒤 골이식으로 보강합니다. 뽑지 않고 살리는 것을 먼저 검토합니다.</dd>
				</dl>
			</article>
			<article class="md-implant-era">
				<span class="md-implant-era__yr">2010s</span>
				<h4>골 관리의 시대</h4>
				<dl>
					<dt>그때의 표준</dt>
					<dd>모스 테이퍼 연결과 플랫폼 스위칭으로 변연골 보존을 노리고, CBCT 진단·상악동 거상술·GBR이 일상 술식이 됩니다.</dd>
					<dt>지금의 과제</dt>
					<dd class="is-now">식립 자체는 안정적이지만, 관리가 늦으면 뼈가 녹아 내려앉는 골소실이 서서히 진행됩니다.</dd>
					<dt>문치과의 대응</dt>
					<dd class="is-ours">정기 파노라마로 뼈 높이를 추적하고, 소실이 확인되면 차폐막·PRF를 이용한 재생술로 되돌립니다.</dd>
				</dl>
			</article>
			<article class="md-implant-era">
				<span class="md-implant-era__yr">2020s</span>
				<h4>디지털과 즉시성</h4>
				<dl>
					<dt>지금의 표준</dt>
					<dd>R2GATE 네비게이션 가이드 수술, 초친수 표면(UV·칼슘이온) 처리, 구강 스캐너 기반 맞춤 지대주, 발치·뼈이식·식립을 하루에 끝내는 당일 임플란트.</dd>
					<dt>지금의 과제</dt>
					<dd class="is-now">빠르고 편해진 만큼, 뼈와 전신 상태에 맞는 케이스인지 가려내는 판단이 더 중요해졌습니다.</dd>
					<dt>문치과의 대응</dt>
					<dd class="is-ours">앞선 세 세대에서 무엇이 오래 버티고 무엇이 탈이 났는지 직접 봐 온 경험으로, 당일 임플란트가 맞는 경우와 아닌 경우를 먼저 구분해 드립니다.</dd>
				</dl>
			</article>
		</div>

		<!-- ── 시스템 색인 ──────────────────────────────────── -->
		<h3 class="md-implant-h3">국내외 임플란트, 계열별로 다뤄왔습니다</h3>
		<p class="md-implant-sub">
			임플란트는 회사마다 픽스처와 지대주가 맞물리는 연결부 형상이 다릅니다. 이 형상을 알아야 드라이버를 고르고,
			나사를 풀고, 보철을 다시 만들 수 있습니다. 아래는 문치과병원이 식립하거나 재수복해 온 대표 계열입니다.
		</p>

		<div class="md-implant-archive" data-md-implant-archive>
			<div class="md-implant-filters" data-md-implant-filters hidden>
				<div class="md-implant-filter-group" role="group" aria-label="출처로 거르기">
					<span class="md-implant-filter-label">출처</span>
					<button type="button" class="md-implant-chip" data-md-origin="all" aria-pressed="true">전체</button>
					<button type="button" class="md-implant-chip" data-md-origin="국산" aria-pressed="false">국산</button>
					<button type="button" class="md-implant-chip" data-md-origin="수입" aria-pressed="false">수입</button>
				</div>
				<div class="md-implant-filter-group" role="group" aria-label="시기로 거르기">
					<span class="md-implant-filter-label">국내 사용 시기</span>
					<button type="button" class="md-implant-chip" data-md-era="all" aria-pressed="true">전체</button>
					<button type="button" class="md-implant-chip" data-md-era="90" aria-pressed="false">1990년대</button>
					<button type="button" class="md-implant-chip" data-md-era="00" aria-pressed="false">2000년대</button>
					<button type="button" class="md-implant-chip" data-md-era="10" aria-pressed="false">2010년대</button>
					<button type="button" class="md-implant-chip" data-md-era="20" aria-pressed="false">2020년대</button>
				</div>
			</div>

			<p class="md-implant-count" data-md-implant-count aria-live="polite" hidden></p>

			<ul class="md-implant-sys-list">
				<?php foreach ( $md_impl_systems as $sys ) : ?>
					<li class="md-implant-sys"
						data-origin="<?php echo esc_attr( $sys['origin'] ); ?>"
						data-eras="<?php echo esc_attr( $sys['eras'] ); ?>">
						<span class="md-implant-sys__ico" title="<?php echo esc_attr( $md_impl_conn[ $sys['conn'] ] ); ?>">
							<svg width="24" height="24" aria-hidden="true" focusable="false"><use href="#md-impl-<?php echo esc_attr( $sys['conn'] ); ?>"/></svg>
						</span>
						<span class="md-implant-sys__name">
							<?php echo esc_html( $sys['ko'] ); ?>
							<span class="md-implant-sys__en"><?php echo esc_html( $sys['en'] ); ?></span>
						</span>
						<span class="md-implant-sys__meta">
							<span class="md-implant-tag"><?php echo esc_html( $sys['origin'] ); ?></span>
							<span><?php echo esc_html( $sys['country'] ); ?></span>
							<span><?php echo esc_html( $sys['span'] ); ?></span>
						</span>
						<span class="md-implant-sys__note"><?php echo wp_kses( $sys['note'], array( 'strong' => array() ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="md-implant-empty" data-md-implant-empty hidden>
				<strong>목록에 없어도 진료는 가능합니다.</strong>
				이 색인은 대표 계열만 추린 것입니다. 브랜드명을 모르시거나 여기에 없는 제품이어도 괜찮습니다.
				파노라마·CBCT 영상에서 연결부 형태를 판독해 시스템을 특정한 뒤, 필요하면 원내 기공소에서 맞는 부품을 직접 제작합니다.
			</p>

			<ul class="md-implant-legend" aria-label="연결부 형상 범례">
				<?php foreach ( $md_impl_conn as $k => $label ) : ?>
					<li>
						<svg width="20" height="20" aria-hidden="true" focusable="false"><use href="#md-impl-<?php echo esc_attr( $k ); ?>"/></svg>
						<span><?php echo esc_html( $label ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<p class="md-implant-foot">
			이 목록은 진료 현장에서 자주 마주치는 <strong>대표 계열을 정리한 것</strong>이며, 실제로 다뤄 온 시스템은 이보다 훨씬 많습니다.
			같은 회사 제품이라도 출시 연도와 라인에 따라 규격이 달라지므로, 최종 확인은 영상 판독과 구강 내 확인을 거쳐 이루어집니다.
		</p>

		<!-- ── 재수복 클리닉 ────────────────────────────────── -->
		<h3 class="md-implant-h3">실패했다고 들은 임플란트, 대부분은 아직 방법이 있습니다</h3>
		<p class="md-implant-sub">
			흔들린다고 해서 모두 빼야 하는 것은 아닙니다. 나사가 풀린 것인지, 뼈가 녹은 것인지, 골유착 자체가 실패한 것인지에 따라
			처치는 완전히 달라집니다. 문치과병원은 먼저 원인을 가르고, 살릴 수 있는 것부터 살립니다.
		</p>

		<div class="md-implant-revs">
			<article class="md-implant-rev">
				<p class="md-implant-rev__said">“임플란트가 흔들려요”</p>
				<p class="md-implant-rev__check"><b>무엇을 확인하나</b> 지대주 나사만 풀린 것인지, 픽스처와 뼈 사이의 골유착이 풀린 것인지를 영상과 토크 값으로 구분합니다. 겉으로 보이는 증상은 같아도 원인은 정반대입니다.</p>
				<p class="md-implant-rev__plan"><b>처치</b> 나사 풀림이면 지대주 재체결과 교합 조정으로 끝나는 경우가 많습니다. 골유착 실패라면 <strong>리무버 키트로 뼈 손상을 최소화하며 제거</strong>한 뒤 재식립을 계획합니다.</p>
				<p class="md-implant-rev__stat">진단 우선 · 제거는 마지막 선택</p>
			</article>
			<article class="md-implant-rev">
				<p class="md-implant-rev__said">“잇몸이 붓고 피가 나요”</p>
				<p class="md-implant-rev__check"><b>무엇을 확인하나</b> 임플란트 주위염입니다. 픽스처 표면에 쌓인 세균막이 주변 뼈를 녹이며 진행되고, 통증이 뚜렷하지 않아 늦게 발견되는 경우가 많습니다.</p>
				<p class="md-implant-rev__plan"><b>처치</b> 잇몸을 안전하게 열어(Flap) 뼈 상태를 직접 확인하고, 물방울 레이저로 오염된 표면을 소독한 뒤 부족한 부위를 <strong>골이식으로 보강</strong>합니다. 필요하면 차폐막으로 재생을 유도합니다.</p>
				<p class="md-implant-rev__stat">약 30~60분 · 회복 2~3주</p>
			</article>
			<article class="md-implant-rev">
				<p class="md-implant-rev__said">“주변 뼈가 녹았다고 들었어요”</p>
				<p class="md-implant-rev__check"><b>무엇을 확인하나</b> CBCT로 골소실의 범위와 형태를 파악합니다. 벽이 남아 있는 결손인지 아닌지에 따라 재생 가능성이 크게 달라집니다.</p>
				<p class="md-implant-rev__plan"><b>처치</b> <strong>GBR(골유도재생술)</strong>로 뼈를 되살립니다. 자가혈에서 뽑은 PRF·PRP와 조직 재생을 돕는 PDRN을 함께 써서 회복 속도를 높이고, 결손 크기에 따라 흡수성·비흡수성 차폐막을 나눠 선택합니다.</p>
				<p class="md-implant-rev__stat">골소실이 있어도 뽑지 않고 살릴 수 있습니다</p>
			</article>
			<article class="md-implant-rev">
				<p class="md-implant-rev__said">“부품이 단종됐다고 하네요”</p>
				<p class="md-implant-rev__check"><b>무엇을 확인하나</b> 1990~2000년대에 식립된 임플란트에서 가장 흔한 상황입니다. 회사가 사라졌거나 라인이 단종되어 기성 지대주를 구할 수 없는 경우입니다.</p>
				<p class="md-implant-rev__plan"><b>처치</b> 연결부 규격을 판독한 뒤, 병원 안 한아 임플란트 보철연구소에서 그 임플란트에만 맞는 <strong>맞춤형 지대주를 직접 제작</strong>합니다. 멀쩡한 픽스처를 부품 때문에 뽑지 않아도 됩니다.</p>
				<p class="md-implant-rev__stat">원내 기공소 · 기공사 상주</p>
			</article>
			<article class="md-implant-rev">
				<p class="md-implant-rev__said">“크라운이 자꾸 빠져요”</p>
				<p class="md-implant-rev__check"><b>무엇을 확인하나</b> 기성 지대주가 잇몸 라인과 맞지 않아 적합도가 떨어지거나, 교합이 한쪽으로 몰려 특정 임플란트에 힘이 집중되는 경우가 많습니다.</p>
				<p class="md-implant-rev__plan"><b>처치</b> 환자분의 잇몸 형태에 맞춘 <strong>맞춤형 지대주</strong>로 바꾸고, 전체 치열의 교합을 다시 잡습니다. 음식물 끼임과 반복적인 탈락이 함께 줄어듭니다.</p>
				<p class="md-implant-rev__stat">시적 시 원내 즉시 조정</p>
			</article>
			<article class="md-implant-rev">
				<p class="md-implant-rev__said">“전신질환이 있어 거절당했어요”</p>
				<p class="md-implant-rev__check"><b>무엇을 확인하나</b> 당뇨·고혈압·심혈관 질환·투석·골다공증 약 복용 여부를 사전에 확인합니다. 혈전용해제와 골다공증 약은 특히 중요한 점검 항목입니다.</p>
				<p class="md-implant-rev__plan"><b>처치</b> 혈압·혈당·심전도·산소포화도를 상시 측정하며 진행하고, 무바늘 마취기와 자가진통조절기(PCA)로 부담을 줄입니다. 진행 여부는 충분한 상담 후 함께 결정합니다.</p>
				<p class="md-implant-rev__stat">전신질환 안심 토탈 케어</p>
			</article>
		</div>

		<!-- ── 시스템 판독 흐름 ─────────────────────────────── -->
		<div class="md-implant-flow">
			<h3 class="md-implant-flow__title">🔎 어디서 무슨 임플란트를 심었는지 모르셔도 됩니다</h3>
			<p class="md-implant-flow__lead">
				오래전에 다른 병원에서 치료받았거나, 그 병원이 문을 닫아 기록이 남아 있지 않은 경우가 많습니다.
				진료를 시작하는 데 브랜드명은 필요하지 않습니다. 영상에 이미 답이 들어 있기 때문입니다.
			</p>
			<ol class="md-implant-flow__list">
				<li><strong>파노라마 + CBCT 3D 촬영</strong> — 기존 임플란트의 개수·위치·깊이와 주변 뼈 높이를 3차원으로 확인합니다. 신경과의 거리, 상악동 상태도 함께 봅니다.</li>
				<li><strong>연결부 형상 판독</strong> — 영상에서 픽스처의 실루엣과 지대주가 맞물린 단면을 읽어 외부육각·내부육각·모스 테이퍼 등 계열을 좁힙니다.</li>
				<li><strong>보유 부품·기록과 대조</strong> — 30여 년간 축적된 원내 진료 기록과 보유 드라이버·부품에 맞춰 실제로 체결해 보며 규격을 확정합니다.</li>
				<li><strong>맞춤 지대주 제작 또는 재식립 결정</strong> — 픽스처를 살릴 수 있으면 원내 기공소에서 맞춤 지대주와 보철을 제작하고, 살리기 어려우면 골 손실을 최소화한 제거 후 재식립 계획을 세웁니다.</li>
			</ol>
		</div>

		<aside class="md-preservation-callout md-preservation-callout--star md-implant-callout">
			<strong>⚡ 왜 오래된 임플란트는 문치과병원인가?</strong>
			<p><strong>1) 1990년대부터의 임상</strong> · 지금 문제가 생기고 있는 1990~2000년대 임플란트를 그 시절에 직접 심어 본 병원입니다. <strong>2) 국내외 전 계열 경험</strong> · 수입·국산을 가리지 않고 다뤄 와, 브랜드를 몰라도 영상으로 시스템을 특정합니다. <strong>3) 원내 기공소</strong> · 부품이 단종돼도 한아 임플란트 보철연구소에서 맞춤 지대주를 직접 제작합니다. <strong>4) 살리는 것이 먼저</strong> · 골소실이 있어도 GBR·PRF·PDRN으로 되살리는 방법을 먼저 검토하고, 제거는 마지막에 판단합니다.</p>
			<p style="margin-top:10px;font-size:0.85rem;opacity:0.85;">* 임플란트 시스템 목록은 대표 예시이며, 제조사·라인·출시 연도에 따라 연결부 규격과 부품 호환성이 달라집니다. 치료 결과와 유지 기간은 뼈 상태·잇몸 건강·전신 질환·흡연 여부·사후 관리에 따라 개인차가 있으며, 재수복 가능 여부는 반드시 영상 판독과 구강 내 검사를 거쳐 결정됩니다.</p>
		</aside>

	</div>
</section>
<?php endif; ?>

<?php
/* v3.44.112 · 사랑니-발치 페이지 전용 · 매복 사랑니 전문 진료 섹션 */
if ( $slug === '사랑니-발치' ) : ?>
<section class="md-section md-wisdom-expert" aria-label="매복 사랑니 전문 진료">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow md-eyebrow--star">⭐ 문치과병원 특화 · 매복 사랑니 전문</span>
			<h2 class="md-section-head__title">어려운 매복 사랑니, 문치과병원이 다릅니다</h2>
			<p class="md-section-head__lead">
				완전 매복·수평 매복·신경 근접 사랑니 등 <strong>일반 치과에서 어렵다고 하는 매복 사랑니</strong>를 30여년 임상 노하우와 CBCT 3D 정밀 진단으로 안전하게 발치합니다. 천안·아산은 물론 충남·충북·경기 남부 환자분들이 찾아오시는 이유입니다.
			</p>
		</header>

		<!-- 매복 사랑니 진료 실적 스탯 -->
		<div class="md-wisdom-stats">
			<div class="md-wisdom-stat">
				<span class="md-wisdom-stat__value">15,000<sup>+</sup></span>
				<span class="md-wisdom-stat__label">매복 사랑니 시술 경험</span>
			</div>
			<div class="md-wisdom-stat">
				<span class="md-wisdom-stat__value">30~45<span class="md-wisdom-stat__unit">분</span></span>
				<span class="md-wisdom-stat__label">평균 매복 사랑니 시술 시간</span>
			</div>
			<div class="md-wisdom-stat">
				<span class="md-wisdom-stat__value">99.9<span class="md-wisdom-stat__unit">%</span></span>
				<span class="md-wisdom-stat__label">신경 손상 없이 안전 발치</span>
			</div>
			<div class="md-wisdom-stat">
				<span class="md-wisdom-stat__value">당일<sup>*</sup></span>
				<span class="md-wisdom-stat__label">90% 이상 당일 발치 가능</span>
			</div>
		</div>

		<!-- 완전 매복 · 수평 매복 · 신경 근접 3케이스 -->
		<div class="md-wisdom-cases">
			<article class="md-wisdom-case">
				<div class="md-wisdom-case__badge">Case 1</div>
				<h3>완전 매복 사랑니 (Fully Impacted)</h3>
				<p class="md-wisdom-case__desc"><strong>잇몸과 뼈 속에 완전히 파묻힌 상태</strong>. 일반 발치로는 접근이 불가능하고, 잇몸 절개와 소량의 골 삭제가 필요한 고난도 케이스입니다.</p>
				<ul class="md-wisdom-case__list">
					<li>CBCT 3D로 사랑니 위치·신경관 거리 정밀 확인</li>
					<li>최소 절개 · 최소 골 삭제 원칙</li>
					<li>치아 분리(Sectioning)로 발치 창구 최소화</li>
					<li>봉합 후 부종·통증 최소화 관리</li>
				</ul>
			</article>
			<article class="md-wisdom-case">
				<div class="md-wisdom-case__badge">Case 2</div>
				<h3>수평 매복 사랑니 (Horizontal Impaction)</h3>
				<p class="md-wisdom-case__desc"><strong>사랑니가 옆으로 누워 있어 앞 어금니(제2대구치)를 밀고 있는 상태</strong>. 방치 시 앞 어금니가 손상되므로 조기 발치가 필요합니다.</p>
				<ol class="md-wisdom-case__flow">
					<li>CBCT 3D 진단 · 앞 어금니 손상 여부 확인</li>
					<li>사랑니 치관을 세로로 분할(Sectioning)</li>
					<li>치관 → 치근 순서로 단계별 제거</li>
					<li>앞 어금니 손상 방지 · 안전 발치</li>
					<li>봉합 · 7~10일 후 실밥 제거</li>
				</ol>
			</article>
			<article class="md-wisdom-case">
				<div class="md-wisdom-case__badge">Case 3</div>
				<h3>신경 근접 사랑니 · CBCT 진단 사례</h3>
				<p class="md-wisdom-case__desc">사랑니 치근이 <strong>하치조신경관에 매우 가깝게 접해있는 케이스</strong>. 일반 파노라마 X-ray로는 정확한 거리 판단이 불가능하고, <strong>CBCT 3D 촬영이 필수</strong>입니다.</p>
				<ul class="md-wisdom-case__list">
					<li>CBCT 3D로 신경관 · 치근 간 실제 거리 mm 단위 측정</li>
					<li>3D 시뮬레이션 후 안전한 발치 경로 계획</li>
					<li>필요 시 치근 상단 일부만 발치(Coronectomy) 옵션 제안</li>
					<li>신경 손상 위험 사전 설명 · 환자 동의 후 진행</li>
					<li>시술 후 하치조신경 감각 이상 없는지 정기 확인</li>
				</ul>
			</article>
		</div>

		<!-- 매복 사랑니 진료 흐름 (7단계) -->
		<div class="md-wisdom-flow">
			<h3 class="md-wisdom-flow__title">📋 매복 사랑니 진료 흐름 (문치과병원 프로토콜)</h3>
			<ol class="md-wisdom-flow__list">
				<li><strong>초진 상담·문진</strong> — 사랑니 관련 통증·불편함 확인 · 전신 병력·복용 약물 체크</li>
				<li><strong>파노라마 + CBCT 3D 정밀 촬영</strong> — 매복 형태·신경 거리·인접 치아 관계 3D 확인</li>
				<li><strong>시술 계획 상담</strong> — 발치 난이도·소요 시간·예상 회복 기간·비용 사전 설명</li>
				<li><strong>진정 마취 선택 (옵션)</strong> — 극도의 공포·긴 시술 예상 시 진정 마취 옵션 제공</li>
				<li><strong>구강악안면외과 정밀 발치</strong> — 최소 침습·최소 절개로 30~45분 내 시술 완료</li>
				<li><strong>봉합·회복 지도</strong> — 얼음찜질·식이 지도·처방약 안내 · 24시간 통증 관리</li>
				<li><strong>3일·7일 경과 관찰</strong> — 부기·통증 확인 · 7~10일 후 실밥 제거 · 필요 시 CT 재확인</li>
			</ol>
		</div>

		<aside class="md-preservation-callout md-preservation-callout--star md-wisdom-callout">
			<strong>⚡ 왜 매복 사랑니는 문치과병원인가?</strong>
			<p><strong>1) 30여년 임상 노하우</strong> · 15,000건 이상의 매복 사랑니 시술 경험으로 어떤 케이스도 침착하게 대응합니다. <strong>2) CBCT 3D 필수 진단</strong> · 파노라마 X-ray만으로 판단하지 않고, 신경 근접 케이스는 반드시 3D로 정밀 확인합니다. <strong>3) 구강악안면외과 진료</strong> · 매복 사랑니는 구강외과 전문 영역 · 문치과병원 매복 사랑니 발치는 구강악안면외과 프로토콜로 진행됩니다. <strong>4) 진정 마취 옵션</strong> · 극도로 두려운 환자분에게는 진정 마취 옵션을 제공하여 편안한 시술을 도와드립니다.</p>
			<p style="margin-top:10px;font-size:0.85rem;opacity:0.85;">* 시술 시간·성공률은 케이스별로 다를 수 있습니다. CBCT 3D 진단 결과에 따라 정확한 계획이 결정됩니다.</p>
		</aside>
	</div>
</section>
<?php endif; ?>

<?php
/* === 환자 고민 / 솔루션 6쌍 — bdbddc.com 패턴 참고 === */
if ( function_exists( 'moondental_service_pain_points' ) ) {
	$pp_map = moondental_service_pain_points();
	if ( isset( $pp_map[ $slug ] ) && ! empty( $pp_map[ $slug ] ) ) :
?>
<section class="md-section md-section--surface" aria-label="환자분 고민·솔루션">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_eyebrow', '환자분의 마음' ) : '환자분의 마음' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_title', '혹시 이런 고민 하고 계시죠?' ) : '혹시 이런 고민 하고 계시죠?' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'service_pain_lead', '많은 환자분이 같은 걱정을 안고 오십니다. 문치과병원이 어떻게 답해드리는지 확인하세요.' ) : '많은 환자분이 같은 걱정을 안고 오십니다.' ) ); ?>
			</p>
		</header>
		<?php
		$pain_tag_q = function_exists( 'md_content' ) ? md_content( 'service_pain_tag_q', '고민' ) : '고민';
		$pain_tag_a = function_exists( 'md_content' ) ? md_content( 'service_pain_tag_a', '문치과의 답' ) : '문치과의 답';
		?>
		<ul class="md-pain">
			<?php foreach ( $pp_map[ $slug ] as $pp ) : ?>
				<li class="md-pain__pair">
					<div class="md-pain__concern">
						<span class="md-pain__tag"><?php echo esc_html( $pain_tag_q ); ?></span>
						<p>"<?php echo esc_html( $pp['concern'] ); ?>"</p>
					</div>
					<div class="md-pain__solution">
						<span class="md-pain__tag md-pain__tag--alt"><?php echo esc_html( $pain_tag_a ); ?></span>
						<p><?php echo esc_html( $pp['solution'] ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; }
?>

<?php /* 강점 카드 섹션은 /기술력-시설/ 페이지로 이동됨 (v3.12.0) */ ?>

<?php
/* === 이런 분께 추천합니다 === */
if ( function_exists( 'moondental_service_ideal_candidates' ) ) {
	$cand_map = moondental_service_ideal_candidates();
	if ( isset( $cand_map[ $slug ] ) && ! empty( $cand_map[ $slug ] ) ) :
?>
<section class="md-section md-section--surface md-section--sm" aria-label="추천 대상">
	<div class="md-container md-container--narrow">
		<div class="md-ideal">
			<header class="md-ideal__head">
				<span class="md-ideal__chip"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_chip', 'For You' ) : 'For You' ); ?></span>
				<h2 class="md-ideal__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_title', '이런 분께 추천합니다' ) : '이런 분께 추천합니다' ); ?></h2>
				<p class="md-ideal__lead">
					<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'service_ideal_lead', '해당하시는 항목이 있으시면 부담 없이 상담받으세요.' ) : '해당하시는 항목이 있으시면 부담 없이 상담받으세요.' ) ); ?>
				</p>
			</header>
			<ul class="md-ideal__list">
				<?php foreach ( $cand_map[ $slug ] as $item ) : ?>
					<li>
						<span class="md-ideal__check" aria-hidden="true">✓</span>
						<span><?php echo esc_html( $item ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php /* v3.37.2 · 중간 CTA 제거 (하단 CTA 배너에 이미 있음) */ ?>
		</div>
	</div>
</section>
<?php endif; }
?>

<?php
/* v3.44.212 · 해당 센터의 종합안내서 배너
 *   홈 사이드바에만 있던 안내서를 각 센터 페이지에서도 바로 닿게 한다.
 *   3개 센터에만 안내서가 있으므로 매핑에 없는 진료항목은 출력되지 않는다. */
$md_guide_for_service = array(
	'임플란트-센터'       => 'implant',
	'투명교정-센터'       => 'suresmile',
	'슈어스마일-투명교정' => 'suresmile',
	'브라켓-치아교정'     => 'suresmile',
	'심미치료'            => 'laminate',
);
if ( isset( $md_guide_for_service[ $slug ] ) && function_exists( 'md_guide_load' ) ) {
	get_template_part( 'template-parts/section', 'guide-cta', array( 'slug' => $md_guide_for_service[ $slug ] ) );
}
?>

<?php
/* 해당 service slug의 자동 FAQ 출력 */
if ( function_exists( 'moondental_get_faqs_by_service' ) ) {
	$faqs_map = moondental_get_faqs_by_service();
	if ( isset( $faqs_map[ $slug ] ) && ! empty( $faqs_map[ $slug ] ) ) :
?>
<section class="md-section md-section--sm" aria-label="자주 묻는 질문">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">FAQ</span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'svc_faq_title', '자주 묻는 질문' ) ); ?></h2>
		</header>
		<div class="md-faq">
			<?php foreach ( $faqs_map[ $slug ] as $i => $item ) : ?>
				<details class="md-faq__item"<?php echo $i === 0 ? ' open' : ''; ?>>
					<summary><?php echo esc_html( $item['q'] ); ?></summary>
					<p><?php echo wp_kses_post( md_autolink_addresses( $item['a'] ) ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; }
?>

<section class="md-section md-section--surface md-section--sm" aria-label="다른 진료 영역">
	<div class="md-container">
		<header class="md-section-head">
			<h2 class="md-section-head__title md-section-head__title--sm"><?php echo esc_html( md_content( 'svc_other_title', '다른 진료 영역 보기' ) ); ?></h2>
		</header>
		<div class="md-service-grid">
			<?php
			// 주 메뉴 구조 기반 5개 상위 카테고리 (임플란트/교정/스마일디자인/자연치아/진료과)
			$service_areas   = function_exists( 'moondental_get_service_areas' ) ? moondental_get_service_areas() : array();
			$current_area    = function_exists( 'moondental_service_slug_to_area' ) ? moondental_service_slug_to_area( $slug ) : $slug;
			foreach ( $service_areas as $area ) :
				if ( $area['slug'] === $current_area ) continue;
			?>
				<article class="md-service-card">
					<div class="md-service-card__icon" aria-hidden="true"><?php echo moondental_render_icon( $area['icon'] ); ?></div>
					<h3 class="md-service-card__title"><?php echo esc_html( $area['title'] ); ?></h3>
					<p class="md-service-card__desc"><?php echo esc_html( $area['desc'] ); ?></p>
					<a class="md-service-card__link" href="<?php echo esc_url( $area['url'] ); ?>"><?php echo esc_html( $area['title'] ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
