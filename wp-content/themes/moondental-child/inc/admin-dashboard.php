<?php
/**
 * Moon Dental · 콘텐츠 관리 대시보드 (wp-admin)
 *
 *  외모 → 문치과 콘텐츠 관리 메뉴 · 사이트의 모든 편집 지점을 한곳에.
 *  각 카드에 "무엇을·어디서 편집" 안내 + 직접 이동 링크.
 *
 *  v3.33.8: 신규 (사용자 요청 · 모든 콘텐츠를 wp-admin에서 찾을 수 있게)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 커스텀 템플릿에서 WP 페이지 본문이 있으면 그것을 렌더하고 true 반환.
 *  각 진료 페이지 템플릿(preservation·smile·prevention·recruit) 상단에서 호출.
 */
function moondental_render_page_body_override() {
	if ( ! is_page() ) return false;
	$body = trim( (string) get_post_field( 'post_content', get_queried_object_id() ) );
	if ( $body === '' ) return false;
	?>
	<section class="md-page-hero">
		<div class="md-container">
			<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span><?php the_title(); ?></span>
			</nav>
			<h1 class="md-page-hero__title"><?php the_title(); ?></h1>
		</div>
	</section>
	<section class="md-section">
		<div class="md-container md-container--narrow">
			<article class="md-page-content">
				<?php
				while ( have_posts() ) : the_post(); the_content(); endwhile;
				?>
			</article>
		</div>
	</section>
	<?php get_template_part( 'template-parts/section', 'cta' ); ?>
	<?php
	return true;
}

/**
 * 관리자 메뉴 등록.
 */
add_action( 'admin_menu', function() {
	add_theme_page(
		'문치과 콘텐츠 관리',            // 페이지 타이틀
		'📋 콘텐츠 관리',                 // 메뉴 라벨
		'edit_theme_options',            // 권한
		'moondental-content-map',        // 슬러그
		'moondental_render_content_dashboard'
	);
} );

/**
 * 대시보드 렌더러.
 */
function moondental_render_content_dashboard() {
	$customize = admin_url( 'customize.php' );
	$pages_url = admin_url( 'edit.php?post_type=page' );
	$posts_url = admin_url( 'edit.php' );

	// 서비스/진료 페이지 - Pages ID를 찾아서 직접 편집 링크 만들기
	$edit_link = function( $slug ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) return '#';
		return get_edit_post_link( $page->ID, 'admin' );
	};

	$panel_link = function( $panel_id ) use ( $customize ) {
		return add_query_arg( array(
			'autofocus' => array( 'panel' => $panel_id ),
		), $customize );
	};

	$section_link = function( $section_id ) use ( $customize ) {
		return add_query_arg( array(
			'autofocus' => array( 'section' => $section_id ),
		), $customize );
	};

	// 진료 페이지 목록
	$service_pages = array(
		'임플란트-센터'    => '임플란트 센터',
		'투명교정-센터'    => '투명교정 센터',
		'자연치아-살리기'   => '자연치아 살리기',
		'스마일디자인센터'  => '스마일디자인 센터',
		'예방클리닉'      => '예방 클리닉',
		'턱관절-클리닉'    => '턱관절 클리닉',
		'사랑니-발치'      => '사랑니 발치',
		'소아치과'         => '소아치과',
		'심미치료'         => '심미치료',
		'상시채용'        => '상시채용',
	);
	?>
	<div class="wrap">
		<h1>📋 문치과 콘텐츠 관리</h1>
		<p class="description" style="font-size:14px; max-width:900px;">
			사이트의 모든 편집 지점을 한 곳에서 안내합니다. 각 카드의 <strong>[편집]</strong> 버튼을 클릭하면
			해당 편집 화면으로 바로 이동합니다. 텍스트·이미지·설정 어떤 것이든 여기서 찾을 수 있습니다.
		</p>

		<style>
			.md-cm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-top: 24px; }
			.md-cm-card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
			.md-cm-card h2 { margin: 0 0 6px; font-size: 16px; }
			.md-cm-card .md-cm-desc { color: #50575e; font-size: 13px; margin: 0 0 12px; line-height: 1.5; min-height: 36px; }
			.md-cm-card .button { margin-right: 6px; }
			.md-cm-section-title { grid-column: 1 / -1; margin: 24px 0 4px; padding-top: 12px; border-top: 2px solid #2271b1; color: #2271b1; font-size: 18px; font-weight: 600; }
			.md-cm-badge { display: inline-block; padding: 2px 8px; font-size: 11px; border-radius: 3px; margin-left: 6px; vertical-align: middle; font-weight: 500; }
			.md-cm-badge--page { background: #dcf0e6; color: #1a6c3e; }
			.md-cm-badge--customizer { background: #fff3d4; color: #7a5c00; }
			.md-cm-badge--post { background: #e0e6f0; color: #2c3e5a; }
		</style>

		<div class="md-cm-grid">

			<!-- ─── 페이지 본문 (WordPress 페이지 편집기) ─── -->
			<div class="md-cm-section-title">🗂️ 페이지 본문 <span style="font-size:13px; font-weight:400; color:#50575e;">— WordPress 페이지 편집기 (블록/클래식 에디터)</span></div>

			<?php foreach ( $service_pages as $slug => $label ) : $url = $edit_link( $slug ); ?>
				<div class="md-cm-card">
					<h2><?php echo esc_html( $label ); ?><span class="md-cm-badge md-cm-badge--page">페이지</span></h2>
					<p class="md-cm-desc">/<?php echo esc_html( $slug ); ?>/ 페이지 본문. 텍스트·이미지·리스트 자유 편집.</p>
					<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">본문 편집 →</a>
				</div>
			<?php endforeach; ?>

			<div class="md-cm-card">
				<h2>모든 페이지 보기<span class="md-cm-badge md-cm-badge--page">페이지</span></h2>
				<p class="md-cm-desc">전체 페이지 목록 · 새 페이지 만들기.</p>
				<a class="button button-primary" href="<?php echo esc_url( $pages_url ); ?>">페이지 목록 →</a>
			</div>

			<div class="md-cm-card">
				<h2>병원 소식 · 치아이야기<span class="md-cm-badge md-cm-badge--post">글</span></h2>
				<p class="md-cm-desc">공지사항·치아 정보 글 작성·수정.</p>
				<a class="button button-primary" href="<?php echo esc_url( $posts_url ); ?>">글 목록 →</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">새 글 작성</a>
			</div>

			<div class="md-cm-card">
				<h2>📖 치과사전 (Encyclopedia)<span class="md-cm-badge md-cm-badge--post">CPT</span></h2>
				<p class="md-cm-desc">치과 용어 사전. 카테고리·초성 필터. SEO 롱테일 키워드 확보.</p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=md_term' ) ); ?>">용어 목록 →</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=md_term' ) ); ?>">새 용어 추가</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=md_term_category&post_type=md_term' ) ); ?>">분야 관리</a>
			</div>

			<!-- ─── 홈페이지 섹션 (Customizer) ─── -->
			<div class="md-cm-section-title">🏠 홈페이지 섹션 <span style="font-size:13px; font-weight:400; color:#50575e;">— 외모 → 사용자 정의하기</span></div>

			<div class="md-cm-card">
				<h2>홈 히어로 (첫 화면)<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">이 첫 화면의 제목·리드·배지·이미지 편집.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'moondental_hero' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 신뢰 stat 4개<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">30년·11개·4개층·1:1 등 숫자 표시 카드.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'md_section_trust' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 WHY MOON DENTAL<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">4가지 강점 카드.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'md_section_why' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 사명·미션 밴드<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">병원 사명·인증 문구.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'md_section_mission' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 진료 소개 그리드<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">임플란트·교정·스마일·자연치아 등 6개 카드.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'md_section_clinic_intro' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 시설·장비<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">시설·장비 6개 카드.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'md_section_facility' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 자주 묻는 질문 6개<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">홈에 노출되는 대표 FAQ.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'md_section_faq' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 후기<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">환자 후기 · 하단 문구.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_testimonials_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>홈 CTA 배너<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">홈 하단 예약 유도 배너.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'md_section_cta' ) ); ?>">편집 →</a>
			</div>

			<!-- ─── 의료진 & 스태프 ─── -->
			<div class="md-cm-section-title">👨‍⚕️ 의료진 · 스태프</div>

			<div class="md-cm-card">
				<h2>의료진 정보 (개별)<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">각 원장님 프로필·약력·인터뷰·관심분야.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_doctor_single_content' ) ); ?>">개별 편집 →</a>
				<a class="button" href="<?php echo esc_url( $panel_link( 'md_panel_doctor_content' ) ); ?>">목록 페이지 편집</a>
			</div>

			<div class="md-cm-card">
				<h2>전체 직원 명단<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">진료실·기공실·경영지원본부 등 전체 스태프.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_doctor_content' ) ); ?>">편집 →</a>
			</div>

			<!-- ─── 병원 정보 / 헤더 / 푸터 ─── -->
			<div class="md-cm-section-title">🏥 병원 정보 · 헤더 · 푸터</div>

			<div class="md-cm-card">
				<h2>병원 기본 정보<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">병원명·주소·전화·이메일·SNS 링크.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'moondental_info' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>헤더 CTA 버튼<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">헤더 우측 편리한 상담·예약 버튼 라벨·색·링크.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_chrome_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>푸터 텍스트·법적표시<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">푸터 하단 이용안내 링크·대표자·저작권.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_chrome_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>플로팅 CTA · 요일 라벨 · 나머지<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">모바일 하단 CTA · 데스크탑 FAB · 페이지 마이크로카피.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_final_content' ) ); ?>">편집 →</a>
			</div>

			<!-- ─── 가격·FAQ·상시채용 ─── -->
			<div class="md-cm-section-title">💰 가격 · FAQ · 상시채용</div>

			<div class="md-cm-card">
				<h2>비용 안내<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">진료비 표·정책·결제 방법 등 전체.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_pricing_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>진료 상세 페이지 공통<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">환자 고민·이상적 후보·FAQ 섹션 등.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_service_content' ) ); ?>">편집 →</a>
				<a class="button" href="<?php echo esc_url( $panel_link( 'md_panel_service_faq_content' ) ); ?>">서비스별 FAQ</a>
			</div>

			<div class="md-cm-card">
				<h2>상시채용 콘텐츠<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">복리후생·WHY·지원 방법 등.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_recruit_page_content' ) ); ?>">편집 →</a>
			</div>

			<!-- ─── 자연치아·스마일·예방 콘텐츠 ─── -->
			<div class="md-cm-section-title">🦷 진료 콘텐츠 (섹션형 편집)</div>

			<div class="md-cm-card">
				<h2>자연치아 살리기<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">충치·신경·잇몸 3섹션 카드/콜아웃.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_preservation_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>스마일디자인<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">라미네이트·심미레진·미백·잇몸미백·거미스마일 5섹션.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_smile_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>예방클리닉<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">덴탈SPA·스케일링·에어플로우·불소·실란트 5섹션.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_prevention_content' ) ); ?>">편집 →</a>
			</div>

			<!-- ─── 지역·예약·소식·오시는길 ─── -->
			<div class="md-cm-section-title">📍 지역 · 예약 · 소식 · 오시는 길</div>

			<div class="md-cm-card">
				<h2>지역 페이지 공통 (28개 URL)<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">/오시는-길/{지역}/ 공통 템플릿 문구 · 한 번 편집하면 28개 URL 모두 반영.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_region_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>예약·소식·오시는 길 나머지<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">예약 FAQ · 소식 히어로 · 오시는 길 요일 라벨.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_misc_pages_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>셀프진단봇 (홈·예약 페이지)<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">30개 자가진단 질문 · 8개 추천 진료과.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_bot_content' ) ); ?>">편집 →</a>
			</div>

			<!-- ─── 역사·기술력·의료진 상세 ─── -->
			<div class="md-cm-section-title">📜 병원 소개 · 역사 · 기술력</div>

			<div class="md-cm-card">
				<h2>병원 안내 (역사·연혁)<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">타임라인·비전·미션.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_history_content' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>기술력·강점<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">/강점/ 페이지 개별 강점 콘텐츠.</p>
				<a class="button button-primary" href="<?php echo esc_url( $panel_link( 'md_panel_subpage_content' ) ); ?>">편집 →</a>
			</div>

			<!-- ─── 나머지·기타 ─── -->
			<div class="md-cm-section-title">🛠️ 기타 편집 지점</div>

			<div class="md-cm-card">
				<h2>사이트 정보 (기본 워드프레스)<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">사이트 제목·태그라인·아이콘 등.</p>
				<a class="button button-primary" href="<?php echo esc_url( $section_link( 'title_tagline' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>메뉴 편집<span class="md-cm-badge md-cm-badge--customizer">WordPress</span></h2>
				<p class="md-cm-desc">헤더·모바일 메뉴 항목 편집.</p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>위젯<span class="md-cm-badge md-cm-badge--customizer">WordPress</span></h2>
				<p class="md-cm-desc">사이드바·푸터 위젯.</p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>">편집 →</a>
			</div>

			<div class="md-cm-card">
				<h2>사용자 정의하기 (전체)<span class="md-cm-badge md-cm-badge--customizer">Customizer</span></h2>
				<p class="md-cm-desc">모든 Customizer 패널 목록 열기.</p>
				<a class="button button-primary" href="<?php echo esc_url( $customize ); ?>">열기 →</a>
			</div>

		</div>

		<div style="margin-top: 40px; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
			<h3 style="margin-top:0;">💡 편집 가이드</h3>
			<ul style="margin:0; padding-left:20px; line-height:1.8;">
				<li><strong>페이지 본문</strong>: 각 진료 페이지의 본문(당일 임플란트 설명, 프리미엄 임플란트 시스템 등)은 <strong>페이지 편집기</strong>에서 블록 에디터로 자유롭게 편집.</li>
				<li><strong>홈·헤더·푸터·공통 섹션</strong>: 여러 페이지에서 공유되는 요소는 <strong>사용자 정의하기(Customizer)</strong>에서 편집. 실시간 미리보기 지원.</li>
				<li><strong>공지사항·치아이야기</strong>: <strong>글</strong> 메뉴에서 신규 작성/편집.</li>
				<li>편집 후 저장하면 즉시 사이트에 반영됩니다.</li>
			</ul>
		</div>
	</div>
	<?php
}
