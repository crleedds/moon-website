<?php
/**
 * Section: 30년 이상 한자리에서 + 6개 진료 영역 카드
 *  사용자 제공 콘텐츠 — 메뉴 구조에 맞춰 6 카드로 정렬.
 *  순서: 임플란트센터 · 교정센터 · 스마일디자인센터 · 자연치아살리기 · 진료과 · 기술력/시설
 *
 * @package moondental-child
 */
?>
<section class="md-section md-clinic-intro" aria-label="문치과병원 진료 시스템 소개">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">CLINIC SYSTEM · 진료 시스템</span>
			<h2 class="md-section-head__title">30년 이상 한자리에서, 문치과병원</h2>
			<p class="md-section-head__lead">
				문치과병원은 각 분과의 원장님들이 다양한 임상경험을 바탕으로<br>
				<strong>대학병원식 협진 시스템</strong>을 통해 30년 이상 한자리에서 전문적이고 정직하게 진료합니다.
			</p>
		</header>

		<div class="md-clinic-intro__grid">

			<!-- 01. 임플란트센터 -->
			<article class="md-clinic-card">
				<header>
					<span class="md-clinic-card__num">01</span>
					<span class="md-clinic-card__icon" aria-hidden="true">🦷</span>
					<h3>임플란트센터</h3>
				</header>
				<p class="md-clinic-card__lead">정밀한 임플란트 시술은 물론, 정기 검진을 통한 사후관리까지 철저히 진행합니다.</p>
				<ul class="md-clinic-card__list">
					<li>고난도 임플란트</li>
					<li>앞니 상실로 불편을 겪는 분들을 위한 즉시 치아 회복</li>
					<li>실패한 임플란트 재수술</li>
					<li>통증을 줄이는 비절개 임플란트</li>
					<li>상악동 거상술</li>
					<li>전악 임플란트</li>
					<li>디지털 장비를 활용한 정밀 네비게이션 임플란트</li>
				</ul>
				<a class="md-clinic-card__more" href="<?php echo esc_url( home_url( '/임플란트-센터/' ) ); ?>">자세히 보기 →</a>
			</article>

			<!-- 02. 교정센터 -->
			<article class="md-clinic-card">
				<header>
					<span class="md-clinic-card__num">02</span>
					<span class="md-clinic-card__icon" aria-hidden="true">✨</span>
					<h3>교정센터</h3>
				</header>
				<p class="md-clinic-card__lead"><strong>AI 기반 투명교정 진단 시스템</strong>을 도입해 정밀 분석이 가능하며, 환자별 최적의 교정 계획을 제안합니다.</p>
				<ul class="md-clinic-card__list">
					<li>고난도 교정</li>
					<li>투명교정 (슈어스마일)</li>
					<li>소아 교정</li>
					<li>재교정</li>
					<li>앞니 부분 교정</li>
				</ul>
				<a class="md-clinic-card__more" href="<?php echo esc_url( home_url( '/투명교정-센터/' ) ); ?>">자세히 보기 →</a>
			</article>

			<!-- 03. 스마일디자인센터 -->
			<article class="md-clinic-card">
				<header>
					<span class="md-clinic-card__num">03</span>
					<span class="md-clinic-card__icon" aria-hidden="true">💎</span>
					<h3>스마일디자인센터</h3>
				</header>
				<p class="md-clinic-card__lead">반점치(화이트스팟) 제거·치아 성형·잇몸 미백·최소침습 라미네이트·벌어진 앞니 레진 수복·왜소치 치료 등 다양한 심미적 고민에 <strong>맞춤 진단</strong>으로 개인별 최적 치료를 제안합니다.</p>
				<ul class="md-clinic-card__list">
					<li>반점치(화이트스팟) 제거</li>
					<li>치아 성형 · 잇몸 미백</li>
					<li>최소침습 라미네이트</li>
					<li>벌어진 앞니 레진 수복</li>
					<li>왜소치 치료</li>
					<li><strong>최소 침습 치료 원칙</strong> — 불필요한 치아 삭제 최소화</li>
				</ul>
				<a class="md-clinic-card__more" href="<?php echo esc_url( home_url( '/스마일디자인센터/' ) ); ?>">자세히 보기 →</a>
			</article>

			<!-- 04. 자연치아 살리기 -->
			<article class="md-clinic-card">
				<header>
					<span class="md-clinic-card__num">04</span>
					<span class="md-clinic-card__icon" aria-hidden="true">🌿</span>
					<h3>자연치아 살리기</h3>
				</header>
				<p class="md-clinic-card__lead">문치과병원은 <strong>발치 대신 자연치아를 최대한 보존</strong>하는 치료를 우선합니다.</p>
				<ul class="md-clinic-card__list">
					<li><strong>충치치료</strong> — 초기 충치부터 정밀하게 진단·치료</li>
					<li><strong>신경치료</strong> — 손상된 치수를 살려 자연치아 보존</li>
					<li><strong>잇몸치료</strong> — 치주 질환 관리로 치아 수명 연장</li>
				</ul>
				<a class="md-clinic-card__more" href="<?php echo esc_url( home_url( '/자연치아-살리기/' ) ); ?>">자세히 보기 →</a>
			</article>

			<!-- 05. 진료과 -->
			<article class="md-clinic-card">
				<header>
					<span class="md-clinic-card__num">05</span>
					<span class="md-clinic-card__icon" aria-hidden="true">🏥</span>
					<h3>진료과</h3>
				</header>
				<p class="md-clinic-card__lead">전 분과 전문 의료진이 분야별 진료를 한 자리에서 협진합니다.</p>
				<ul class="md-clinic-card__list">
					<li><strong>턱관절</strong> — 통증·기능 장애 진료</li>
					<li><strong>이갈이 · 이악물기</strong></li>
					<li><strong>매복 사랑니 발치</strong></li>
					<li><strong>소아치과</strong></li>
					<li><strong>예방클리닉</strong> — 전문예방치료실 · 덴탈 스파 프로그램</li>
				</ul>
				<a class="md-clinic-card__more" href="<?php echo esc_url( home_url( '/예방클리닉/' ) ); ?>">예방클리닉 자세히 →</a>
			</article>

			<!-- 06. 기술력/시설 -->
			<article class="md-clinic-card">
				<header>
					<span class="md-clinic-card__num">06</span>
					<span class="md-clinic-card__icon" aria-hidden="true">🔬</span>
					<h3>기술력 / 시설</h3>
				</header>
				<p class="md-clinic-card__lead">자체 <strong>디지털센터·기공소</strong> 운영. <strong>물방울 레이저 5대</strong> 보유 — 통증·출혈 적고 빠른 회복.</p>
				<ul class="md-clinic-card__list">
					<li>One Day 보철 치료까지 가능 (구강 정밀 스캔)</li>
					<li>의료진·기공사 긴밀 소통으로 맞춤형 보철</li>
					<li>오차 최소화 — 높은 정확도 · 내원 횟수 단축</li>
					<li>원내 기공소 신속 수정·A/S</li>
					<li>물방울 레이저 — 임플란트 주위염·잇몸 성형·시린이·신경치료·구내염·점액낭종</li>
				</ul>
				<a class="md-clinic-card__more" href="<?php echo esc_url( home_url( '/기술력-시설/' ) ); ?>">자세히 보기 →</a>
			</article>

		</div>

		<!-- 야간진료 강조 박스 -->
		<aside class="md-clinic-night">
			<span aria-hidden="true">🌙</span>
			<div>
				<strong>야간 진료 운영</strong>
				<p>천안시 신부동에 위치한 문치과병원은 바쁜 일상 속에서도 원하는 시간에 진료받으실 수 있도록 <strong>월·화·수·금요일 저녁 8시 30분(20:30)까지</strong> 야간진료를 운영합니다.</p>
			</div>
		</aside>

		<!-- 마무리 메시지 -->
		<p class="md-clinic-closer">
			앞으로도 문치과병원은 <strong>봉사와 지역의료의 책임</strong>을 감당해 나가겠습니다.
		</p>
	</div>
</section>
