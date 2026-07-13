<?php
/**
 * Template Name: 상시채용 (치과위생사 채용 안내)
 * Template Post Type: page
 *
 * /상시채용/ 페이지 — 진료실·상담실 치과위생사 선생님 채용.
 * v3.30.5 · 지원 방법 이메일 단일화 · 부담 없이 지원 강조.
 *
 * @package moondental-child
 */

get_header();
$info = moondental_get_info();

// v3.27.3: 인사팀 전용 이메일 (설정 없으면 대표 이메일)
$hr_email = function_exists( 'md_content' ) ? md_content( 'recruit_hr_email', '' ) : '';
$show_email = $hr_email ?: ( $info['email'] ?: 'moondental1995@naver.com' );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--recruit">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>상시채용</span>
		</nav>
		<span class="md-page-hero__eyebrow">RECRUIT · 한아의료재단 문치과병원</span>
		<h1 class="md-page-hero__title">
			<?php echo esc_html( md_content( 'recruit_hero_title_a', '오래 함께 갈 동료를 찾습니다' ) ); ?><br>
			<em><?php echo esc_html( md_content( 'recruit_hero_title_b', '진료실 · 상담실 치과위생사 선생님' ) ); ?></em>
		</h1>
		<p class="md-page-hero__lead">
			<?php echo nl2br( esc_html( md_content( 'recruit_hero_lead',
				"천안 만남로 1995년 개원 30여년.\n20년 넘게 근무하고 계신 선생님들도 많은 병원입니다.\n짧게 스쳐가는 자리가 아니라, 길게 보고 함께할 분을 모십니다." ) ) ); ?>
		</p>
		<div class="md-btn-group md-page-hero__actions">
			<a class="md-btn md-btn-primary md-btn--lg" href="mailto:<?php echo esc_attr( $show_email ); ?>?subject=<?php echo rawurlencode( '문치과병원 채용 지원' ); ?>" data-track="cta-recruit-email-hero">
				📧 이메일로 지원하기
				<span class="md-btn__sub"><?php echo esc_html( $show_email ); ?></span>
			</a>
		</div>
	</div>
</section>

<!-- ============ 모집 대상 · 공통 근무 조건 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">📋 모집 대상</span>
			<h2 class="md-section-head__title">진료실 · 상담실 치과위생사</h2>
			<p class="md-section-head__lead">
				<?php echo esc_html( md_content( 'recruit_target_lead',
					'신입·경력 모두 환영합니다. 부서(진료실·상담실)는 지원 후 상담을 통해 정합니다.' ) ); ?>
			</p>
		</header>

		<!-- 공통 근무 조건·복리후생 -->
		<article class="md-recruit-card md-recruit-card--common">
			<div class="md-recruit-card__head">
				<span class="md-recruit-card__badge md-recruit-card__badge--common">상시채용</span>
				<h3>💼 근무 조건 · 복리후생</h3>
				<p class="md-recruit-card__lead">진료실·상담실 공통으로 적용됩니다.</p>
			</div>
			<div class="md-recruit-grid">
				<div class="md-recruit-block">
					<h4>💼 근무 조건 <span class="md-recruit-block__caption">(직원 시프트 · 병원 진료시간과 다름)</span></h4>
					<ul>
						<li>주 5일 근무 (평일 + 토요일 격주)</li>
						<li>평일 9:00~19:30 (점심시간 1시간 포함)</li>
						<li>월·화·수·금 야간진료는 오후 시프트 로테이션 (~20:30)</li>
						<li>목요일 9:00~18:30 · 토요일 9:00~14:00</li>
						<li>일요일·공휴일 휴무</li>
						<li>4대 보험 · 퇴직금 · 연차 (법정 필수)</li>
					</ul>
				</div>
				<div class="md-recruit-block">
					<h4>🎁 복리후생</h4>
					<ul>
						<li>인센티브 · 명절 상여 · 우수사원 포상</li>
						<li>본인·가족 치과 진료 할인</li>
						<li>학회·세미나 참여 지원 (교육비)</li>
						<li>유니폼·식대·간식 제공</li>
						<li>경조사 휴가·경조금 · <strong>장기근속 포상</strong></li>
					</ul>
				</div>
			</div>
		</article>
	</div>
</section>

<!-- ============ 왜 문치과인가 ============ -->
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">✨ WHY MOON DENTAL</span>
			<h2 class="md-section-head__title">문치과병원에서 일하면 좋은 점</h2>
		</header>

		<div class="md-preservation-grid">
			<article class="md-preservation-card">
				<h3>🕰️ 20년 넘게 함께한 동료들</h3>
				<p>1995년 개원 후 20년 넘게 근무하고 계신 선생님들이 여러 분 계십니다. 신뢰가 쌓인 동료와 오래 함께 일할 수 있는 환경입니다.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🦷 30여년 임상 노하우</h3>
				<p>다양한 케이스를 직접 경험하며 임상 실력을 키울 수 있습니다.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🏥 통합 진료센터 (4개 층)</h3>
				<p>9F 보철·보존·예방 · 10F 임플란트·외과·턱관절 · 11F 교정·소아·치주·디지털 · 13F 기공 — 모든 진료를 한 건물에서 경험.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🔬 디지털 진료 시스템</h3>
				<p>CBCT·디지털 가이드·구강 스캐너 등 최신 장비. 디지털 치과 실무 경험 축적.</p>
			</article>
			<article class="md-preservation-card">
				<h3>👨‍⚕️ 분야별 전문 의료진 협진</h3>
				<p>보철·보존·예방·임플란트·스마일디자인·구강외과·구강내과·턱관절·교정·소아·치주 전문 의료진과 함께 — 다각도로 배울 수 있는 환경.</p>
			</article>
			<article class="md-preservation-card">
				<h3>📚 교육·세미나 지원</h3>
				<p>학회·세미나 참석 지원, 사내 임상 교육 — 성장하고 싶은 분께 적극 추천.</p>
			</article>
		</div>
	</div>
</section>

<!-- ============ 지원 방법 · 이메일만 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">📩 지원 방법</span>
			<h2 class="md-section-head__title">이메일 한 통이면 충분합니다</h2>
		</header>

		<article class="md-recruit-callout">
			<h3>이렇게 보내주세요</h3>
			<ul class="md-recruit-callout__list">
				<li><strong>이력서가 완벽하지 않아도 괜찮습니다.</strong> 형식보다 함께 오래 갈 마음이 더 중요합니다.</li>
				<li><strong>자기소개서가 길지 않아도 괜찮습니다.</strong> "문치과병원에서 일하는 것에 관심 있습니다"라는 한 줄만으로도 충분합니다.</li>
				<li>가지고 계신 이력서와 간단한 소개를 <a href="mailto:<?php echo esc_attr( $show_email ); ?>?subject=<?php echo rawurlencode( '문치과병원 채용 지원' ); ?>"><?php echo esc_html( $show_email ); ?></a>로 보내주시면 저희가 확인 후 연락드립니다.</li>
			</ul>
			<p class="md-recruit-callout__hint">서류 검토는 보통 3~5일 소요됩니다. 지원자분의 상황·경력에 맞춰 이후 면접 일정을 개별 안내드립니다.</p>
		</article>

		<ol class="md-preservation-steps md-u-mt-cards">
			<li>
				<strong>1단계 · 이메일 접수</strong>
				<p>이력서와 간단한 소개를 <a href="mailto:<?php echo esc_attr( $show_email ); ?>"><?php echo esc_html( $show_email ); ?></a>로 보내주세요.</p>
			</li>
			<li>
				<strong>2단계 · 서류 검토</strong>
				<p>3~5일 이내 검토 후 면접 대상자에게 개별 연락드립니다.</p>
			</li>
			<li>
				<strong>3단계 · 면접</strong>
				<p>인사 담당자·실장 면접, 이후 대표 원장님 면접. 근무 환경·업무 내용을 자세히 안내드립니다.</p>
			</li>
			<li>
				<strong>4단계 · 채용 확정 · 입사</strong>
				<p>합격 통보 후 상호 협의된 입사일에 출근. 3개월 수습 후 정규직 전환.</p>
			</li>
		</ol>
	</div>
</section>

<!-- ============ CTA ============ -->
<section class="md-section md-section--sm">
	<div class="md-container md-container--narrow">
		<div class="md-region-cta">
			<span class="md-region-cta__chip">📧 지원 접수</span>
			<h2 class="md-region-cta__title">
				지금 이메일로<br>
				편하게 보내주세요
			</h2>
			<p class="md-region-cta__lead">
				길지 않아도, 완벽하지 않아도 괜찮습니다. 함께 오래 갈 분을 기다리고 있습니다.
			</p>
			<div class="md-btn-group md-btn-group--center md-rcta">
				<a class="md-btn md-btn-primary md-btn--lg" href="mailto:<?php echo esc_attr( $show_email ); ?>?subject=<?php echo rawurlencode( '문치과병원 채용 지원' ); ?>" data-track="cta-recruit-final-email">
					📧 <?php echo esc_html( $show_email ); ?>
				</a>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
