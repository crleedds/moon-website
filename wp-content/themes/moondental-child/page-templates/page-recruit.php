<?php
/**
 * Template Name: 상시채용 (치과위생사 채용 안내)
 * Template Post Type: page
 *
 * /상시채용/ 페이지 — 치과위생사 선생님 채용 광고.
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$kakao      = $info['kakao_url'] ?? '';

// v3.27.3: 인사팀 전용 연락처 (설정 없으면 대표 연락처 사용)
$hr_phone_display = function_exists( 'md_content' ) ? md_content( 'recruit_hr_phone',   '' ) : '';
$hr_phone_link    = function_exists( 'md_content' ) ? md_content( 'recruit_hr_phone_link', '' ) : '';
$hr_email         = function_exists( 'md_content' ) ? md_content( 'recruit_hr_email',   '' ) : '';
$hr_contact_name  = function_exists( 'md_content' ) ? md_content( 'recruit_hr_contact_name', '' ) : '';

$show_phone     = $hr_phone_display ?: $info['phone'];
$show_phone_lnk = $hr_phone_link    ?: $phone_link;
$show_email     = $hr_email         ?: ( $info['email'] ?: 'moondental1995@naver.com' );
$phone_note     = $hr_phone_display ? '인사팀 직접 통화' : ( '인사팀 직접 통화 (대표번호 · 인사팀 연결 요청)' );
?>

<!-- ============ Hero ============ -->
<section class="md-page-hero md-page-hero--recruit">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>상시채용</span>
		</nav>
		<span class="md-page-hero__eyebrow">RECRUIT · 한아의료재단 문치과병원</span>
		<h1 class="md-page-hero__title">
			함께 일할 동료를 찾습니다<br>
			<em>치과위생사 선생님 상시 채용</em>
		</h1>
		<p class="md-page-hero__lead">
			천안 만남로 30여년 임상, 분야별 전문 의료진 협진 시스템 — <br>
			환자 한 분을 가족처럼 보는 마음으로 함께해 주실 분을 모십니다.
		</p>
		<div class="md-btn-group md-page-hero__actions">
			<a class="md-btn md-btn-primary md-btn--lg" href="tel:<?php echo esc_attr( $show_phone_lnk ); ?>" data-track="cta-recruit-call">
				📞 인사팀 문의
				<span class="md-btn__sub"><?php echo esc_html( $show_phone ); ?></span>
			</a>
			<?php if ( $kakao ) : ?>
				<a class="md-btn md-btn--kakao md-btn--lg" href="<?php echo esc_url( $kakao ); ?>" target="_blank" rel="noopener" data-track="cta-recruit-kakao">
					💬 카카오톡으로 지원
				</a>
			<?php endif; ?>
		</div>
		<?php if ( ! $hr_phone_display ) : ?>
			<p class="md-page-hero__note md-page-hero__note--caption">
				※ 대표번호로 전화 시 "<strong>인사팀 담당자 연결 부탁드립니다</strong>"라고 말씀해주세요.
			</p>
		<?php endif; ?>
		<?php if ( $hr_contact_name ) : ?>
			<p class="md-page-hero__note">
				· 인사 담당: <strong><?php echo esc_html( $hr_contact_name ); ?></strong>
				<?php if ( $hr_email ) : ?>
					· <a href="mailto:<?php echo esc_attr( $hr_email ); ?>"><?php echo esc_html( $hr_email ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
</section>

<!-- ============ 모집 공고 카드 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">📋 모집 공고</span>
			<h2 class="md-section-head__title">치과위생사 · 진료 코디네이터</h2>
		</header>

		<!-- v3.27.3: 공통 근무 조건·복리후생 (모든 직군 공통) -->
		<article class="md-recruit-card md-recruit-card--common">
			<div class="md-recruit-card__head">
				<span class="md-recruit-card__badge md-recruit-card__badge--common">모든 직군 공통</span>
				<h3>💼 공통 근무 조건 · 복리후생</h3>
				<p class="md-recruit-card__lead">아래 조건·복리후생은 치과위생사·진료 코디네이터 모든 채용에 동일하게 적용됩니다.</p>
			</div>
			<div class="md-recruit-grid">
				<div class="md-recruit-block">
					<h4>💼 근무 조건 <span class="md-recruit-block__caption">(직원 시프트 · 병원 진료시간과 다름)</span></h4>
					<ul>
						<li>주 5일 근무 (평일 + 토요일 격주)</li>
						<li>평일 9:00~19:30 (점심시간 1시간 포함)</li>
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
						<li>경조사 휴가·경조금 · 장기근속 포상</li>
					</ul>
				</div>
			</div>
		</article>

		<article class="md-recruit-card" style="margin-top: clamp(24px, 3vw, 36px);">
			<div class="md-recruit-card__head">
				<span class="md-recruit-card__badge">상시채용</span>
				<h3>치과위생사 (신입 · 경력)</h3>
				<p class="md-recruit-card__lead">9F 종합진료센터 · 10F 임플란트센터 · 11F 교정과 — 부서 선택 가능</p>
			</div>

			<div class="md-recruit-grid">
				<div class="md-recruit-block">
					<h4>👥 지원 자격</h4>
					<ul>
						<li>치위생(학)과 졸업 · 치과위생사 면허 소지자</li>
						<li>신입 · 경력 모두 환영 (신입은 3개월 OJT 트레이닝)</li>
						<li>환자분께 따뜻한 마음으로 응대할 수 있는 분</li>
						<li>꼼꼼하고 책임감 있는 분</li>
					</ul>
				</div>
				<div class="md-recruit-block">
					<h4>📝 주요 업무</h4>
					<ul>
						<li>진료 보조 · 환자 응대 · 진료 차트 관리</li>
						<li>스케일링 · 불소도포 · 실란트 등 예방 진료</li>
						<li>임플란트·교정 보조 (희망 부서 배치)</li>
						<li>환자 상담 · 사후 관리 케어</li>
					</ul>
				</div>
			</div>
		</article>

		<article class="md-recruit-card" style="margin-top: clamp(24px, 3vw, 36px);">
			<div class="md-recruit-card__head">
				<span class="md-recruit-card__badge md-recruit-card__badge--alt">상시채용</span>
				<h3>진료 코디네이터 (상담 실장)</h3>
				<p class="md-recruit-card__lead">임플란트·교정·심미 치료 상담 — 환자분의 치료 계획·비용을 안내</p>
			</div>

			<div class="md-recruit-grid">
				<div class="md-recruit-block">
					<h4>👥 지원 자격</h4>
					<ul>
						<li>치과 진료 코디네이터 경력 2년 이상 우대</li>
						<li>임플란트·교정·심미 상담 경험 우대</li>
						<li>치과위생사 면허 우대 (필수 아님)</li>
						<li>커뮤니케이션 능력·신뢰감 있는 분</li>
					</ul>
				</div>
				<div class="md-recruit-block">
					<h4>📝 주요 업무</h4>
					<ul>
						<li>초진 상담 · 견적서 작성 · 치료 계획 안내</li>
						<li>네이버 예약·카카오톡 상담 응대</li>
						<li>사후 관리·정기 검진 안내</li>
						<li>환자 만족도 관리</li>
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
				<h3>🦷 30여년 임상 노하우</h3>
				<p>1995년 개원 30여년 — 다양한 케이스를 직접 경험하며 임상 실력을 키울 수 있습니다.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🏥 통합 진료센터 (4개 층)</h3>
				<p>9F 종합·10F 임플란트·11F 교정·13F 기공실 — 모든 진료를 한 건물에서 경험.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🔬 디지털 진료 시스템</h3>
				<p>CBCT·디지털 가이드·구강 스캐너 등 최신 장비. 디지털 치과 실무 경험 축적.</p>
			</article>
			<article class="md-preservation-card">
				<h3>👨‍⚕️ 분야별 전문 의료진 협진</h3>
				<p>보철·교정·보존·치주·소아·외과 전문 의료진과 함께 — 다각도로 배울 수 있는 환경.</p>
			</article>
			<article class="md-preservation-card">
				<h3>📚 교육·세미나 지원</h3>
				<p>학회·세미나 참석 지원, 사내 임상 교육 — 성장하고 싶은 분께 적극 추천.</p>
			</article>
			<article class="md-preservation-card">
				<h3>🤝 안정적인 근무 환경</h3>
				<p>한아의료재단 비영리 법인 운영 — 장기 근무 가능한 안정적인 환경.</p>
			</article>
		</div>
	</div>
</section>

<!-- ============ 지원 방법 ============ -->
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">📩 지원 방법</span>
			<h2 class="md-section-head__title">지원 방법 · 전형 절차</h2>
		</header>

		<ol class="md-preservation-steps">
			<li>
				<strong>1단계 · 서류 지원</strong>
				<p>이력서·자기소개서를 이메일(<a href="mailto:<?php echo esc_attr( $show_email ); ?>"><?php echo esc_html( $show_email ); ?></a>) 또는 카카오톡 채널로 제출.</p>
			</li>
			<li>
				<strong>2단계 · 서류 심사</strong>
				<p>약 3~5일 이내 검토 후 면접 대상자에게 개별 연락.</p>
			</li>
			<li>
				<strong>3단계 · 1차 면접</strong>
				<p>인사 담당자·실장 면접. 근무 환경·업무 내용·복리후생을 자세히 안내드립니다.</p>
			</li>
			<li>
				<strong>4단계 · 2차 면접</strong>
				<p>대표 원장님 면접. 환자분께 응대하는 자세·진료 철학 등을 함께 나눕니다.</p>
			</li>
			<li>
				<strong>5단계 · 채용 확정 · 입사</strong>
				<p>합격 통보 후 상호 협의된 입사일에 출근. 3개월 수습 후 정규직 전환.</p>
			</li>
		</ol>
	</div>
</section>

<!-- ============ CTA ============ -->
<section class="md-section md-section--sm">
	<div class="md-container md-container--narrow">
		<div class="md-region-cta">
			<span class="md-region-cta__chip">💼 지원 문의</span>
			<h2 class="md-region-cta__title">
				문치과병원 인사팀에<br>
				편하게 문의해주세요
			</h2>
			<p class="md-region-cta__lead">
				궁금한 점이 있으시면 전화·카카오톡으로 부담 없이 문의 주세요. 비밀 보장.
			</p>
			<div class="md-btn-group md-rcta" style="justify-content:center; gap:12px;">
				<a class="md-btn md-btn-primary md-btn--lg" href="tel:<?php echo esc_attr( $show_phone_lnk ); ?>" data-track="cta-recruit-final-call">
					📞 <?php echo esc_html( $show_phone ); ?>
				</a>
				<?php if ( $kakao ) : ?>
					<a class="md-btn md-btn--kakao md-btn--lg" href="<?php echo esc_url( $kakao ); ?>" target="_blank" rel="noopener" data-track="cta-recruit-final-kakao">
						💬 카카오톡 채널
					</a>
				<?php endif; ?>
				<a class="md-btn md-btn-ghost md-btn--lg" href="mailto:<?php echo esc_attr( $show_email ); ?>">
					📧 이메일 지원
				</a>
			</div>
			<?php if ( ! $hr_phone_display ) : ?>
				<p style="margin-top:14px;font-size:0.85rem;opacity:0.85;text-align:center;">
					※ 대표번호로 문의하실 때는 <strong>"인사팀 담당자 연결 부탁드립니다"</strong>라고 말씀해주세요.
				</p>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
get_footer();
