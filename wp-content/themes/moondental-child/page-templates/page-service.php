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

<?php
/* v3.47 · 임플란트-센터는 본문을 이 템플릿에서 직접 구성한다.
 *   기존 Customizer 본문( md_content_service_body_임플란트-센터 )은 DB 에 그대로 두고
 *   렌더에서만 제외한다. 되돌리려면 아래 조건 두 개를 지우면 원래대로 출력된다.
 *   이유 · 기존 본문이 소제목 23개 분량이라 환자가 읽어야 할 핵심이 묻혔다. */
$md_impl_own = ( $slug === '임플란트-센터' );
?>

<?php if ( ! $md_impl_own ) : ?>
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
<?php endif; ?>

<?php if ( $md_impl_own ) : ?>

<!-- ── 다른 병원에서 어렵다고 들으신 분들 ───────────────────── -->
<section class="md-section md-impl-hard" aria-label="다른 병원에서 어렵다고 하신 경우">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow md-eyebrow--star">⭐ 임플란트센터가 가장 많이 받는 의뢰</span>
			<h2 class="md-section-head__title">다른 병원에서 어렵다고 하셨나요?</h2>
			<p class="md-section-head__lead">
				문치과병원은 1990년대부터 임플란트를 심어 왔습니다. 그래서 <strong>오래된 임플란트, 실패했다고 들은 임플란트,
				뼈가 부족해 거절당한 경우</strong>가 임플란트센터에 가장 많이 오는 의뢰입니다.
			</p>
		</header>

		<div class="md-impl-cards">
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">임플란트가 흔들려요</h3>
				<p>나사만 풀린 것인지, 뼈가 녹은 것인지에 따라 처치가 완전히 다릅니다. 원인부터 가려내고, <strong>살릴 수 있으면 뽑지 않습니다.</strong></p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">부품이 단종됐다고 하네요</h3>
				<p>20~30년 전 임플란트에서 가장 흔한 일입니다. 병원 안 기공소에서 <strong>그 임플란트에 맞는 부품을 직접 만듭니다.</strong></p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">뼈가 부족해서 안 된대요</h3>
				<p>뼈를 만들어서 심습니다. 골이식과 상악동 거상술로 <strong>식립할 수 있는 조건을 먼저 만듭니다.</strong></p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">잇몸이 붓고 피가 나요</h3>
				<p>임플란트 주위염입니다. 잇몸을 열어 소독하고 뼈를 보강해 <strong>뽑지 않고 살리는 방법을 먼저 봅니다.</strong></p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">당뇨·고혈압이 있어 거절당했어요</h3>
				<p>혈압·혈당·심전도·산소포화도를 재면서 진행합니다. 드시는 약까지 확인한 뒤 <strong>안전한 방법을 함께 정합니다.</strong></p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">어디서 심었는지 기억이 안 나요</h3>
				<p>괜찮습니다. <strong>CT 한 장이면 어느 회사 제품인지 찾아냅니다.</strong> 브랜드를 모르셔도 진료를 시작할 수 있습니다.</p>
			</article>
		</div>
	</div>
</section>

<!-- ── 30년이 만든 차이 ─────────────────────────────────────── -->
<section class="md-section md-section--surface" aria-label="30여년 임플란트 임상이 만든 차이">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">30여년이 만든 차이</span>
			<h2 class="md-section-head__title">오래된 임플란트를 다룰 수 있는 이유</h2>
		</header>

		<div class="md-impl-diffs">
			<article class="md-impl-diff">
				<span class="md-impl-diff__n">1995<span>년부터</span></span>
				<h3>그 시절에 직접 심었습니다</h3>
				<p>지금 문제가 생기고 있는 20~30년 전 임플란트를, 저희가 그때 현장에서 직접 심고 관리해 왔습니다. 세대마다 어디가 탈이 나는지 알고 있습니다.</p>
			</article>
			<article class="md-impl-diff">
				<span class="md-impl-diff__n">20<span>여 종</span></span>
				<h3>국내외 브랜드를 가리지 않습니다</h3>
				<p>수입 임플란트와 국산 임플란트를 두루 다뤄 왔습니다. 브랜드마다 부품이 맞물리는 방식이 다른데, 그 차이를 영상만 보고 구분합니다.</p>
			</article>
			<article class="md-impl-diff">
				<span class="md-impl-diff__n">병원 안<span>에 기공소</span></span>
				<h3>없는 부품은 직접 만듭니다</h3>
				<p>한아 임플란트 보철연구소가 병원 안에 있고 기공사가 상주합니다. 단종된 부품도 만들 수 있고, 맞지 않으면 그 자리에서 고칩니다.</p>
			</article>
		</div>
	</div>
</section>

<!-- ── 치료 과정 ────────────────────────────────────────────── -->
<section class="md-section" aria-label="임플란트 치료 과정">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">치료 과정</span>
			<h2 class="md-section-head__title">이렇게 진행됩니다</h2>
			<p class="md-section-head__lead">
				진단 결과와 <strong>투명한 비용 설명</strong>을 먼저 드린 뒤에 치료를 시작합니다. 기간은 뼈 상태에 따라 달라지며,
				상담 때 예상 일정을 함께 안내드립니다.
			</p>
		</header>

		<ol class="md-impl-steps">
			<li>
				<h3>정밀 진단과 상담</h3>
				<p>CT로 잇몸뼈 양과 신경 위치를 확인합니다. 치료 계획과 비용을 이 단계에서 모두 설명드립니다.</p>
			</li>
			<li>
				<h3>수술 준비</h3>
				<p>뼈가 부족하면 골이식을 함께 계획하고, 전신질환이 있으면 복용 약까지 미리 점검합니다.</p>
			</li>
			<li>
				<h3>임플란트 식립</h3>
				<p>네비게이션 가이드로 계획한 위치에 정확히 심습니다. 무바늘 마취기와 자가진통조절기로 통증 부담을 줄입니다.</p>
			</li>
			<li>
				<h3>보철과 평생 관리</h3>
				<p>원내 기공소에서 맞춤 제작한 보철을 끼웁니다. 이후 연 4회 정기검진으로 10~20년 이상 쓰시도록 관리합니다.</p>
			</li>
		</ol>
	</div>
</section>

<!-- ── 건강보험 임플란트 ────────────────────────────────────── -->
<section class="md-section md-section--sm" aria-label="건강보험 임플란트">
	<div class="md-container md-container--narrow">
		<aside class="md-impl-insurance">
			<div class="md-impl-insurance__head">
				<h2>만 65세 이상이시라면</h2>
				<p>건강보험으로 임플란트를 <strong>평생 2개까지</strong> 하실 수 있습니다. 대상이 되시는지 상담 때 확인해 드립니다.</p>
			</div>
			<dl class="md-impl-insurance__facts">
				<div><dt>대상</dt><dd>만 65세 이상 건강보험 가입자<br>(피부양자·의료급여자 포함)</dd></div>
				<div><dt>개수</dt><dd>평생 2개까지</dd></div>
				<div><dt>본인 부담</dt><dd>치료비의 30%</dd></div>
				<div><dt>조건</dt><dd>부분 무치악<br>남은 치아가 하나만 있어도 가능</dd></div>
			</dl>
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
/* === 환자 고민 / 솔루션 6쌍 — bdbddc.com 패턴 참고 ===
 * v3.47 · 임플란트-센터는 제외. 위 "다른 병원에서 어렵다고 하셨나요" 6케이스와
 *   뼈 부족·전신질환·흔들림 3개가 겹쳐 같은 말을 두 번 하게 된다.
 *   해당 페이지에서는 그쪽 한 블록으로 합쳤다. */
if ( function_exists( 'moondental_service_pain_points' ) && ! $md_impl_own ) {
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
