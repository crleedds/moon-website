<?php
/**
 * 임플란트센터 본문 — 문치과병원
 *
 * 기존 본문(Customizer md_content_service_body_임플란트-센터)의 내용을
 * 그대로 살리되, 중복된 블록만 합치고 배치·디자인을 다시 잡았다.
 *
 *  합친 것
 *   · "전국에서 찾아주시는 이유" + 재수복 케이스        → ① 어려운 케이스
 *   · "당일 임플란트" + "원데이" + "전악·피개틀니"        → ④ 치료 방식
 *   · "맞춤형 지대주" + "원내 기공소"                    → ⑥ 보철을 직접 만듭니다
 *   · "전신질환 안심 토탈 케어" + "수술 후 환자 케어"     → ⑧ 안심 케어
 *   · 본문 FAQ 6개  → 템플릿 자동 FAQ 와 질문이 같아 제거 (아래 md-faq 가 출력)
 *   · md-pain 6고민 → ① 과 3개 겹쳐 page-service.php 에서 제외
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$md_impl_img = get_stylesheet_directory_uri() . '/assets/images/services/';
?>

<!-- ① 어려운 케이스 ─────────────────────────────────────────── -->
<section class="md-section md-impl-hard" aria-label="다른 병원에서 어렵다고 하신 경우">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow md-eyebrow--star">⭐ 임플란트센터가 가장 많이 받는 의뢰</span>
			<h2 class="md-section-head__title">다른 병원에서 어렵다고 하셨나요?</h2>
			<p class="md-section-head__lead">
				문치과병원은 1990년대부터 임플란트를 심어 왔습니다. 그래서 <strong>오래된 임플란트, 실패했다고 들은 임플란트,
				뼈가 부족해 거절당한 경우</strong>가 임플란트센터에 가장 많이 오는 의뢰입니다. 전국에서 찾아주시는 이유이기도 합니다.
			</p>
		</header>

		<div class="md-impl-cards">
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">임플란트가 흔들려요</h3>
				<p>나사만 풀린 것인지, 뼈가 녹은 것인지에 따라 처치가 완전히 다릅니다. 원인부터 가려내고, <strong>살릴 수 있으면 뽑지 않습니다.</strong> 제거가 필요하면 리무버 키트로 골 손실을 최소화합니다.</p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">부품이 단종됐다고 하네요</h3>
				<p>20~30년 전 임플란트에서 가장 흔한 일입니다. 병원 안 기공소에서 <strong>그 임플란트에 맞는 부품을 직접 만듭니다.</strong> 멀쩡한 픽스처를 부품 때문에 뽑지 않아도 됩니다.</p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">뼈가 부족해서 안 된대요</h3>
				<p>뼈를 만들어서 심습니다. 골이식과 상악동 거상술로 <strong>식립할 수 있는 조건을 먼저 만듭니다.</strong> 잔존골이 3mm만 있어도 시도할 수 있는 시스템을 갖추고 있습니다.</p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">잇몸이 붓고 피가 나요</h3>
				<p>임플란트 주위염입니다. 잇몸을 열어 소독하고 뼈를 보강해 <strong>뽑지 않고 살리는 방법을 먼저 봅니다.</strong> 시술 30~60분, 회복 2~3주입니다.</p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">당뇨·고혈압이 있어 거절당했어요</h3>
				<p>혈압·혈당·심전도·산소포화도를 재면서 진행합니다. 드시는 약까지 확인한 뒤 <strong>안전한 방법을 함께 정합니다.</strong> 심혈관 질환·투석 환자도 상담 후 가능합니다.</p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">어디서 심었는지 기억이 안 나요</h3>
				<p>괜찮습니다. <strong>CT 한 장이면 어느 회사 제품인지 찾아냅니다.</strong> 국내외 20여 종을 다뤄 온 경험으로 브랜드를 몰라도 진료를 시작할 수 있습니다.</p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">앞니가 빠져 사람 만나기가 불편해요</h3>
				<p>임시치아나 임시틀니로 <strong>그날 바로 심미성을 회복</strong>해 드립니다. 원내 기공소가 있어 보철 제작이 빠릅니다.</p>
			</article>
			<article class="md-impl-card">
				<h3 class="md-impl-card__q">수술이 무섭고 아플까 걱정돼요</h3>
				<p>무바늘 마취기와 물방울 레이저로 통증을 줄이고, <strong>자가진통조절기(PCA)</strong>로 아플 때 직접 진통제를 조절하실 수 있습니다. 잇몸을 째지 않는 비절개 식립도 가능합니다.</p>
			</article>
		</div>
	</div>
</section>

<!-- ② 30년이 만든 차이 ──────────────────────────────────────── -->
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

<!-- ③ 치료 방식 ────────────────────────────────────────────── -->
<section class="md-section" aria-label="임플란트 치료 방식">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">치료 방식</span>
			<h2 class="md-section-head__title">상태에 맞는 방법을 고릅니다</h2>
			<p class="md-section-head__lead">
				빠진 치아가 하나인지 전체인지, 뼈가 얼마나 남았는지에 따라 방법이 달라집니다.
				진단 결과와 <strong>투명한 비용 설명</strong>을 먼저 드린 뒤에 함께 정합니다.
			</p>
		</header>

		<h3 class="md-impl-h3">당일 임플란트 — 발치·뼈이식·식립을 하루에</h3>
		<p class="md-impl-lead">여러 번의 마취와 수술 부담을 줄이고 치료 기간을 크게 단축하는 고난도 시술법입니다. 30여년 경력의 진료팀이 직접 시술하며, 보건복지부 인증 자가치아뼈이식술 우수 협력병원입니다.</p>

		<div class="md-impl-compare">
			<article class="md-impl-compare__col">
				<span class="md-impl-compare__tag">기존 방식</span>
				<p class="md-impl-compare__flow">발치 → 회복 3~6개월 → 1차 식립 → 회복 3~6개월 → 2차 잇몸 수술 → 최종 보철</p>
				<p class="md-impl-compare__sum">치료 기간 <b>8~12개월</b><br>치아 없는 기간이 길어 부담이 큽니다</p>
			</article>
			<article class="md-impl-compare__col is-ours">
				<span class="md-impl-compare__tag">당일 임플란트</span>
				<p class="md-impl-compare__flow">발치 · 뼈이식 · 식립을 <b>하루에 동시 진행</b></p>
				<p class="md-impl-compare__sum">치료 기간과 수술 횟수 최소화<br>치아 없는 기간·심리적 부담 감소</p>
			</article>
		</div>

		<aside class="md-impl-note">
			<strong>당일 임플란트가 어려운 경우도 있습니다.</strong>
			뼈 상태가 부족해 안정적인 식립이 어려운 경우, 발치 부위에 염증이나 잇몸 질환이 있는 경우,
			심혈관·당뇨·면역 질환 등 전신 건강 문제가 있는 경우, 신경·혈관과 너무 가까운 경우입니다.
			안전과 성공률을 위해 뼈·잇몸·전신 상태를 반드시 확인한 뒤 진행 여부를 판단합니다.
		</aside>

		<div class="md-impl-options">
			<article class="md-impl-option">
				<h4>원데이 임플란트 <span>앞니·어금니</span></h4>
				<p>임시치아 또는 임시틀니로 심미성을 즉시 회복해 일상으로 빠르게 복귀하실 수 있습니다. 프라임 스캐너와 원내 기공소로 보철물을 신속하게 제작합니다.</p>
			</article>
			<article class="md-impl-option">
				<h4>전악 임플란트 <span>다수·전체 상실</span></h4>
				<p>All-on-4 등 전악 보철 시스템으로 전체 치아를 회복합니다. 구강 상태·골량·전신 건강·경제적 여건을 함께 보고 결정합니다.</p>
			</article>
			<article class="md-impl-option">
				<h4>피개틀니 · 부분틀니 <span>오버덴처 · RPD</span></h4>
				<p>임플란트로 틀니를 고정해 흔들림을 잡는 방식입니다. 어떤 방식이 맞는지는 보철 전문 의료진이 협진해 정합니다.</p>
			</article>
		</div>
	</div>
</section>

<!-- ④ 프리미엄 임플란트 시스템 ──────────────────────────────── -->
<section class="md-section md-section--surface" aria-label="사용하는 임플란트 시스템">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">사용하는 임플란트</span>
			<h2 class="md-section-head__title">각 브랜드의 최상위 라인만 씁니다</h2>
			<p class="md-section-head__lead">
				같은 회사 제품이라도 등급이 나뉩니다. 문치과병원은 <strong>중저가 라인을 쓰지 않고</strong>
				각 회사의 프리미엄 라인만 선별해, 환자분의 골 상태·연령·심미 요구에 맞는 것을 고릅니다.
			</p>
		</header>

		<div class="md-impl-brands">
			<article class="md-impl-brand">
				<h3>스테리오스 <span>Stereos</span></h3>
				<ul>
					<li><strong>테이퍼드 디자인</strong> — 뼈에 파고들며 조여져 초기 고정력이 우수합니다</li>
					<li><strong>잔존골 3mm</strong>만 있어도 식립 가능 — 골량 부족·상악동 인접 케이스에 유리</li>
				</ul>
			</article>
			<article class="md-impl-brand">
				<h3>오스템 KS <span>Osstem KS Line</span></h3>
				<ul>
					<li>널리 쓰이는 중저가 TS 라인이 아닌 <strong>최상위 KS 라인</strong>만 사용합니다</li>
					<li>내면 접촉 면적·결합 깊이·벽 두께 강화 — <strong>오래 써도 나사 풀림·파절이 적습니다</strong></li>
				</ul>
			</article>
			<article class="md-impl-brand">
				<h3>포인트 <span>Point</span></h3>
				<ul>
					<li><strong>UV 표면처리</strong>로 초친수성을 활성화해 골유착 속도를 높입니다</li>
					<li>수술 후 조기 안정화 · 회복 기간 단축</li>
				</ul>
			</article>
			<article class="md-impl-brand">
				<h3>메가젠 블루다이아몬드 <span>MegaGen Blue Diamond</span></h3>
				<ul>
					<li><strong>칼슘 이온 표면처리</strong> — 뼈와 화학적으로 결합해 골유착이 빠릅니다</li>
					<li><strong>Deep Thread 설계</strong> — 골질이 약한 시니어 환자분께 안정적입니다</li>
				</ul>
			</article>
		</div>
	</div>
</section>

<!-- ⑤ 보철을 병원 안에서 직접 ───────────────────────────────── -->
<section class="md-section" aria-label="맞춤형 지대주와 원내 기공소">
	<div class="md-container md-container--narrow">
		<div class="md-impl-media">
			<figure class="md-impl-media__fig">
				<img src="<?php echo esc_url( $md_impl_img . 'implant-digital-prosthetic.png' ); ?>"
					alt="디지털 보철 · 3D 스캔 기반 맞춤형 지대주·크라운 설계" loading="lazy" decoding="async">
				<figcaption>3D 스캔 데이터로 환자 개별 맞춤 지대주·크라운을 설계합니다</figcaption>
			</figure>
			<div class="md-impl-media__body">
				<span class="md-section-head__eyebrow">보철</span>
				<h2>기성 부품을 쓰지 않습니다</h2>
				<p>
					지대주는 임플란트 뿌리와 최종 크라운을 잇는 핵심 부품입니다. 이 부분이 얼마나 정밀하냐가
					<strong>보철물의 수명과 심미성을 결정</strong>합니다. 그래서 공장에서 나온 기성품 대신
					환자분마다 맞춤 제작합니다.
				</p>
				<ul class="md-impl-ticks">
					<li>잇몸 형태에 맞춘 개별 설계로 <strong>라인이 자연스럽고 음식물이 덜 낍니다</strong></li>
					<li>기성 지대주보다 마감 정확도가 높아 적합도·심미성이 좋습니다</li>
					<li>장기적으로 잇몸 염증·나사 풀림 같은 트러블이 줄어듭니다</li>
				</ul>

				<h3>원내 기공소 · 기공사 상주</h3>
				<p>병원 안에 <strong>한아 임플란트 보철연구소</strong>가 있고 기공사가 상주합니다. 외부로 보내지 않기 때문에</p>
				<ul class="md-impl-ticks">
					<li>환자분의 자연치 색을 <strong>눈으로 확인하며</strong> 색과 형태를 맞춥니다</li>
					<li>끼워보고 안 맞으면 <strong>그 자리에서 바로 수정</strong>합니다. 다시 오실 일이 줄어듭니다</li>
					<li>담당 기공사가 처음부터 끝까지 책임져 품질이 일정합니다</li>
					<li>맞춤 지대주 · 세라믹 크라운 · 지르코니아 · e.max 모두 원내 제작</li>
				</ul>
			</div>
		</div>
	</div>
</section>

<!-- ⑥ 임플란트를 살리기 위한 노력 ───────────────────────────── -->
<section class="md-section md-section--surface" aria-label="임플란트를 살리기 위한 술식">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow md-eyebrow--star">⭐ 뽑지 않고 살리는 것이 먼저</span>
			<h2 class="md-section-head__title">임플란트를 살리기 위한 문치과의 노력</h2>
			<p class="md-section-head__lead">
				뼈가 녹았다고, 흔들린다고 바로 빼지 않습니다. 되살릴 수 있는 방법을 먼저 검토하고,
				그러기 위해 필요한 술식과 장비를 갖추고 있습니다.
			</p>
		</header>

		<div class="md-impl-saves">
			<article class="md-impl-save">
				<h3>PDRN <span>조직 재생 촉진</span></h3>
				<p>연어에서 추출한 DNA 성분으로 인체에 안전하며 조직 재생을 촉진합니다. 약물이 아니어서 거부반응·부작용이 없습니다.</p>
				<ul>
					<li>붓기·통증 감소, 회복 속도 향상</li>
					<li>초기 고정력이 좋아져 치료 기간 단축</li>
					<li>잇몸 염증 완화, 발치 부위 빠른 회복</li>
				</ul>
			</article>
			<article class="md-impl-save">
				<h3>Flap &amp; Bone Graft <span>골소실 임플란트 재건</span></h3>
				<p>임플란트 주위 뼈가 녹아 흔들리거나 잇몸이 부을 때, 잇몸을 안전하게 열고 부족한 뼈를 보강해 수명을 늘립니다.</p>
				<ul>
					<li><strong>Flap</strong> — 잇몸을 열어 뼈 상태를 정확히 확인</li>
					<li><strong>Bone Graft</strong> — 부족한 부위를 골이식으로 보강</li>
					<li><strong>Membrane</strong> — 필요 시 차폐막으로 뼈 재생 유도</li>
				</ul>
				<p class="md-impl-save__stat">약 30~60분 · 회복 2~3주 · <b>골소실이 있어도 뽑지 않고 살릴 수 있습니다</b></p>
			</article>
			<article class="md-impl-save">
				<h3>GBR <span>골유도재생술</span></h3>
				<p>발치와 동시에 뼈이식을 진행해 추가 수술을 줄이는 술식입니다. 자가혈 성분을 함께 써서 골재생을 앞당깁니다.</p>
				<ul>
					<li><strong>PRF</strong> 자가혈 응고막 — 뼈 재생 촉진, 이식재 고정·보호</li>
					<li><strong>PRP</strong> 혈소판 농축액 — 성장인자 풍부, 면역 거부 반응 없음</li>
					<li><strong>차폐막</strong> — 흡수성·비흡수성을 결손 크기에 맞춰 선택</li>
				</ul>
			</article>
			<article class="md-impl-save">
				<h3>상악동 거상술 <span>윗턱 뼈 부족</span></h3>
				<p>위턱 어금니 부위는 상악동(코 옆 공기주머니) 때문에 뼈가 얇습니다. 그 바닥을 들어 올려 뼈를 이식합니다. 오래 치아가 없어 뼈가 흡수된 경우 꼭 필요한 시술입니다.</p>
				<ul>
					<li><strong>치조정 접근법</strong> — 뼈가 조금만 부족할 때, 비교적 간단</li>
					<li><strong>측방 접근법</strong> — 뼈 부족이 심할 때, 다량의 골이식재 사용</li>
				</ul>
			</article>
			<article class="md-impl-save">
				<h3>물방울 레이저 <span>Waterlase</span></h3>
				<p>물과 레이저로 잇몸을 다루기 때문에 열손상·진동·소음이 적습니다. 임플란트 주위염 소독과 염증 조직 제거에 씁니다.</p>
				<ul>
					<li>마취량 감소 — 소아·치과 공포증 환자분께 특히 유리</li>
					<li>살균·소독 효과가 함께 있어 출혈·통증 감소</li>
				</ul>
			</article>
			<article class="md-impl-save md-impl-save--wide">
				<figure class="md-impl-save__fig">
					<img src="<?php echo esc_url( $md_impl_img . 'implant-navigation-3d.png' ); ?>"
						alt="R2GATE 네비게이션 임플란트 3D 가이드 · CBCT + 구강 스캔 정합" loading="lazy" decoding="async">
				</figure>
				<div>
					<h3>네비게이션 임플란트 <span>R2GATE</span></h3>
					<p>CBCT와 구강 스캔 데이터를 겹쳐 컴퓨터로 미리 수술해 본 뒤, 그대로 심을 수 있는 가이드 장치를 만들어 사용합니다.</p>
					<ul>
						<li>위치·각도·깊이를 <strong>±0.5mm 정확도</strong>로 식립, 신경 손상 위험 최소화</li>
						<li>수술 시간 단축 — 출혈·붓기·통증 감소</li>
						<li>전신질환자·고연령 환자분께 특히 유리합니다</li>
					</ul>
				</div>
			</article>
		</div>
	</div>
</section>

<!-- ⑦ 수술 7단계 ───────────────────────────────────────────── -->
<section class="md-section" aria-label="임플란트 수술 과정">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">치료 과정</span>
			<h2 class="md-section-head__title">이렇게 진행됩니다</h2>
		</header>

		<ol class="md-impl-timeline">
			<li>
				<h3>정밀 진단과 상담</h3>
				<p>X-ray와 3D CT로 잇몸뼈·신경 상태를 분석해 치료 계획을 세우고, 비용을 이 단계에서 모두 설명드립니다.</p>
			</li>
			<li>
				<h3>전신 건강 체크와 수술 준비</h3>
				<p>혈압·당뇨 등 전신질환을 확인합니다. 채혈로 PRF·PRP를 준비하고, 뼈이식이 필요하면 PDRN과 골이식재를 함께 준비합니다.</p>
			</li>
			<li>
				<h3>발치 · 뼈이식 · 임플란트 식립</h3>
				<p>물방울 레이저로 출혈과 통증을 줄입니다. 발치 부위에 PDRN을 넣고 임플란트 표면에도 분사해 골유착을 앞당깁니다.</p>
			</li>
			<li>
				<h3>수술 직후 관리</h3>
				<p>항생제·진통제를 투여하고 원하시면 자가진통조절기(PCA)를 사용합니다. 바로 X-ray로 위치를 확인하고 균형 영양식을 드립니다.</p>
			</li>
			<li>
				<h3>회복과 사후 관리</h3>
				<p>2~3일간 소독하고 10일쯤 실밥을 제거합니다. 이후 3~4개월간 정기 X-ray로 뼈가 잘 붙는지 확인합니다.</p>
			</li>
			<li>
				<h3>최종 보철물 장착</h3>
				<p>원내 기공소에서 만든 CAD/CAM 맞춤 보철을 끼우고, 씹는 힘이 고르게 퍼지도록 교합을 조정합니다.</p>
			</li>
			<li>
				<h3>평생 관리 프로그램</h3>
				<p>1년 4회 정기검진과 스케일링, 임플란트 전용 관리 교육(양치·치실·워터픽)으로 <strong>10~20년 이상</strong> 쓰시도록 관리합니다.</p>
			</li>
		</ol>
	</div>
</section>

<!-- ⑧ 안심 케어 ────────────────────────────────────────────── -->
<section class="md-section md-section--surface" aria-label="전신질환 안심 케어와 수술 후 케어">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">안심 케어</span>
			<h2 class="md-section-head__title">수술 전부터 집에 가실 때까지</h2>
		</header>

		<div class="md-impl-care">
			<article class="md-impl-care__col">
				<h3>전신질환이 있어도</h3>
				<ul class="md-impl-ticks">
					<li>심혈관 질환·신장(투석)·당뇨·고혈압이 <strong>있더라도</strong> 충분한 상담 후 안전하게 수술합니다</li>
					<li>혈전용해제·골다공증 약 등 치과 금기 약물을 미리 확인하고 내복약을 점검합니다</li>
					<li>혈압기·당검사·심전도·산소포화도 측정기를 상시 갖춰 응급 상황에 대비합니다</li>
					<li>가글 마취제·도포 마취제·무바늘 마취기(쏘덴)로 통증 공포를 줄입니다</li>
					<li>자가진통조절기(PCA) — 아플 때 환자분이 직접 버튼을 눌러 진통제를 조절합니다</li>
				</ul>
			</article>
			<article class="md-impl-care__col">
				<h3>수술 후에도</h3>
				<ul class="md-impl-ticks">
					<li><strong>균형 영양식</strong> 제공 — 당뇨가 있으신 분께는 당뇨식으로 드립니다</li>
					<li><strong>귀가 서비스</strong> — 거동이 불편하신 분은 댁까지 안전하게 모셔다 드립니다</li>
					<li><strong>해피콜</strong> — 수술 후 상태를 전화로 확인하고 응급 상황에 대응합니다</li>
					<li><strong>수액요법</strong> — 영양제·생리식염수로 회복을 돕습니다</li>
					<li><strong>야간 진료</strong> — 월·화·수·금 저녁 8시 30분까지 진료합니다</li>
				</ul>
			</article>
		</div>
	</div>
</section>

<!-- ⑨ 건강보험 임플란트 ────────────────────────────────────── -->
<section class="md-section md-section--sm" aria-label="건강보험 임플란트">
	<div class="md-container md-container--narrow">
		<aside class="md-impl-insurance">
			<div class="md-impl-insurance__head">
				<h2>만 65세 이상이시라면</h2>
				<p>건강보험으로 임플란트를 <strong>평생 2개까지</strong> 하실 수 있습니다. 대상이 되시는지 상담 때 확인해 드리고, 가입하신 개인 치과보험도 함께 봐 드립니다.</p>
			</div>
			<dl class="md-impl-insurance__facts">
				<div><dt>대상</dt><dd>만 65세 이상 건강보험 가입자<br>(피부양자·의료급여자 포함)</dd></div>
				<div><dt>개수</dt><dd>평생 2개까지</dd></div>
				<div><dt>본인 부담</dt><dd>치료비의 30%<br>1개당 약 36만~45만 원</dd></div>
				<div><dt>치아 상태</dt><dd>부분 무치악<br>남은 치아가 하나만 있어도 가능</dd></div>
				<div><dt>보철 재료</dt><dd>PFM 크라운<br>지르코니아 크라운</dd></div>
				<div><dt>적용 부위</dt><dd>앞니·어금니 구분 없이<br>위·아래턱 모두 가능</dd></div>
			</dl>
		</aside>
	</div>
</section>
