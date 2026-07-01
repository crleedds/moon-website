<?php
/**
 * Template Name: 자연치아 살리기 (충치·신경·잇몸 3섹션 앵커)
 * Template Post Type: page
 *
 * /자연치아-살리기/ 페이지 — 충치치료·신경치료·잇몸치료 3개 섹션을
 * 한 페이지에서 스크롤하며 볼 수 있는 종합 페이지.
 *
 *  앵커: #cavity (충치), #endo (신경), #perio (잇몸)
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--preservation">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>자연치아 살리기</span>
		</nav>
		<span class="md-page-hero__eyebrow">PRESERVATION · 자연치아 살리기</span>
		<h1 class="md-page-hero__title">
			천안·아산 자연치아 살리기<br>
			<em>발치보다 보존이 먼저입니다</em>
		</h1>
		<p class="md-page-hero__lead">
			충치치료·신경치료·잇몸치료 — 보존과·치주과 전문 진료로 환자분의 자연치아를 최대한 살립니다.<br>
			천안 만남로 1995년 개원 30여년 임상.
		</p>
	</div>
</section>

<!-- ============ 앵커 네비게이션 ============ -->
<nav class="md-preservation-nav" aria-label="자연치아 살리기 섹션 이동">
	<div class="md-container">
		<ul>
			<li><a href="#cavity"><span aria-hidden="true">🦷</span> 충치치료</a></li>
			<li><a href="#endo"><span aria-hidden="true">⚡</span> 신경치료</a></li>
			<li><a href="#perio"><span aria-hidden="true">🌿</span> 잇몸치료</a></li>
		</ul>
	</div>
</nav>

<!-- ============ 1. 충치치료 ============ -->
<section class="md-section md-section--surface" id="cavity">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">01 · CAVITY TREATMENT</span>
			<h2 class="md-section-head__title">천안 충치치료 — 보존적 접근으로 자연치아 최대한 살리기</h2>
			<p class="md-section-head__lead">충치는 조기 발견·조기 치료가 핵심입니다. 진행 단계에 따라 가장 보존적인 방법을 선택합니다.</p>
		</header>

		<div class="md-preservation-grid">
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">초기</span>
				<h3>충치 초기 — 불소도포 · 실란트</h3>
				<p>치아 표면에 미세한 변색·법랑질 손상이 시작된 단계. 삭제 없이 <strong>고농도 불소도포</strong>로 재광화를 유도합니다. 어금니는 <strong>실란트(홈메우기)</strong>로 추가 충치를 예방.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">중기</span>
				<h3>중기 충치 — 심미 레진 충전</h3>
				<p>법랑질을 지나 상아질에 충치가 진행된 단계. <strong>최소 삭제 + 심미 레진 충전</strong>으로 자연치아 형태와 색을 그대로 복원. 1~2회 내원으로 완료.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">진행</span>
				<h3>진행 충치 — 세라믹 인레이·온레이</h3>
				<p>충치 범위가 넓어 레진만으로 부족한 경우 <strong>세라믹 인레이/온레이</strong>로 정밀 복원. 강도·심미·내구성 모두 우수. 13층 자체 기공실 직접 제작.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">심부</span>
				<h3>심부 충치 — 신경 보존 직접복강</h3>
				<p>충치가 신경에 근접했지만 살아있는 경우 <strong>직접복강(direct pulp capping)</strong>으로 신경을 살리는 시도. 신경치료 없이 자연치아 보존 가능성.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">광범위</span>
				<h3>광범위 충치 — 크라운 (지르코니아·금)</h3>
				<p>충치로 치아 구조가 크게 손상된 경우 신경치료 후 <strong>크라운(지르코니아·금)</strong>으로 강도 회복. 자체 기공실 보철로 정밀한 적합도.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">예방</span>
				<h3>충치 재발 예방</h3>
				<p>치료 후 6개월~1년 정기 검진, 스케일링, 에어플로우, 불소도포로 재발 예방. 양치 습관과 식이 관리도 함께 안내.</p>
			</article>
		</div>

		<aside class="md-preservation-callout">
			<strong>💡 충치치료 비용 안내</strong>
			<p>레진 충전 10만원부터 / 세라믹 인레이 35만원부터 / 지르코니아 크라운 55만원부터 — 정확한 견적은 진단 후 산정. <a href="<?php echo esc_url( home_url( '/비용-안내/' ) ); ?>">비용 안내 자세히 보기 →</a></p>
		</aside>
	</div>
</section>

<!-- ============ 2. 신경치료 ============ -->
<section class="md-section" id="endo">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">02 · ENDODONTICS</span>
			<h2 class="md-section-head__title">천안 신경치료 — 치아 보존의 마지막 기회</h2>
			<p class="md-section-head__lead">충치가 신경까지 도달한 경우, 신경치료로 발치를 막고 자연치아를 살립니다. 보존과 전문의의 정밀 근관치료.</p>
		</header>

		<h3 class="md-preservation-h3">언제 신경치료가 필요한가요?</h3>
		<ul class="md-preservation-list">
			<li><strong>가만히 있어도 욱신거리는 통증</strong> — 신경 염증의 대표 증상</li>
			<li><strong>잠을 못 잘 정도의 야간 통증</strong> — 화농성 염증 의심</li>
			<li><strong>차거나 뜨거운 자극에 통증이 오래 지속</strong></li>
			<li><strong>치아 색이 어둡게 변색</strong> — 신경 괴사 가능성</li>
			<li><strong>잇몸에 고름·물집 (치근단 농양)</strong> — 신경 손상 후 염증 확산</li>
			<li><strong>외상으로 치아가 파절</strong> — 신경 노출</li>
		</ul>

		<h3 class="md-preservation-h3">문치과병원 신경치료의 강점</h3>
		<div class="md-preservation-grid">
			<article class="md-preservation-card">
				<h3>🔬 CBCT 3D 진단</h3>
				<p>일반 X-ray로 보이지 않는 신경관의 분지·곡률·세부 구조를 3D로 정확히 파악. 누락 없는 근관치료.</p>
			</article>
			<article class="md-preservation-card">
				<h3>⚡ NiTi 회전 파일</h3>
				<p>최신 NiTi 회전 파일 시스템으로 신경관 내부를 정밀 세척·확대. 천공·분리 위험 최소화.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🔄 재근관치료 가능</h3>
				<p>다른 곳에서 신경치료가 실패한 케이스도 재근관치료로 발치 없이 살리는 시도. 30여년 임상 경력.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🦷 치근단수술 (Apicoectomy)</h3>
				<p>일반 근관치료로 해결 안 되는 치근단 염증을 외과적으로 제거. 구강악안면외과 협진.</p>
			</article>
		</div>

		<aside class="md-preservation-callout">
			<strong>⏱️ 신경치료 진행 흐름</strong>
			<p>1차 — 신경 제거 + 임시 충전 / 2차 — 신경관 세척·소독 / 3차 — 영구 충전 + 코어 / 4차 — 크라운 마무리. 통상 2~4회 내원, 케이스에 따라 다름.</p>
		</aside>
	</div>
</section>

<!-- ============ 3. 잇몸치료 ============ -->
<section class="md-section md-section--surface" id="perio">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">03 · PERIODONTICS</span>
			<h2 class="md-section-head__title">천안 잇몸치료 — 치주염 진행 막기</h2>
			<p class="md-section-head__lead">잇몸 출혈·붓기·입냄새는 치주염의 신호. 자연치아 평생 건강의 핵심은 잇몸 관리입니다.</p>
		</header>

		<h3 class="md-preservation-h3">치주질환 단계별 치료</h3>
		<div class="md-preservation-grid">
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">단계 1</span>
				<h3>치은염 — 스케일링</h3>
				<p>잇몸이 붉고 잘 붓는 단계. <strong>스케일링(보험 적용, 연 1회)</strong>으로 치석·치태 제거. 양치 습관 교정으로 회복 가능.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">단계 2</span>
				<h3>경증 치주염 — 치근활택술</h3>
				<p>치주 포켓이 깊어지기 시작. <strong>치근활택술(SRP)</strong>로 치근 표면을 매끄럽게 다듬어 치태 부착 방지. 보험 적용.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">단계 3</span>
				<h3>중등도 치주염 — 치주소파술</h3>
				<p>치주 포켓 5mm 이상. <strong>치주소파술</strong>로 깊이 있는 염증 조직 제거. 보험 적용.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">단계 4</span>
				<h3>중증 치주염 — 치주 수술</h3>
				<p>치조골 손실 진행. <strong>치주 판막 수술 + 골 이식</strong>으로 골 재생 시도. 치아를 살리는 마지막 시도.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">유지</span>
				<h3>치주 유지관리 (SPT)</h3>
				<p>치료 후 3~6개월 간격 정기 점검·스케일링. <strong>치주 유지관리 프로그램</strong>으로 재발 방지.</p>
			</article>
			<article class="md-preservation-card">
				<span class="md-preservation-card__stage">보조</span>
				<h3>잇몸 PDRN 주사</h3>
				<p>잇몸 염증 완화·재생 촉진을 위한 <strong>PDRN(DNA 단편) 주사</strong>. 시술당 5만원, 비급여.</p>
			</article>
		</div>

		<aside class="md-preservation-callout">
			<strong>📌 잇몸 건강 자가 체크</strong>
			<p>✓ 양치 시 자주 피가 난다 / ✓ 잇몸이 부어 보인다 / ✓ 치아가 길어 보인다 / ✓ 입냄새가 심해졌다 / ✓ 음식 끼임이 잦아졌다 → 2가지 이상 해당되면 천안 만남로 문치과병원 잇몸 검진을 권해드립니다.</p>
		</aside>
	</div>
</section>

<!-- ============ CTA ============ -->
<section class="md-section md-section--sm">
	<div class="md-container md-container--narrow">
		<div class="md-region-cta">
			<span class="md-region-cta__chip">🦷 자연치아 살리기 상담</span>
			<h2 class="md-region-cta__title">
				발치 권유받으셨나요?<br>
				천안 문치과병원에서 한 번 더 살펴보세요
			</h2>
			<p class="md-region-cta__lead">
				보존과·치주과 전문 의료진의 정밀 진단으로 자연치아를 살릴 수 있는지 검토해드립니다.
			</p>
			<?php echo md_render_reservation_ctas( array( 'track' => 'cta-preservation', 'size' => 'lg', 'align' => 'center' ) ); ?>
		</div>
	</div>
</section>

<?php
get_footer();
