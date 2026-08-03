<?php
/**
 * Template Name: 지역 추천 치과 랜딩
 * Template Post Type: page
 *
 * '천안 추천 치과' · '아산 추천 치과' 등 정확 매칭 검색 유도 랜딩 페이지.
 * 페이지 슬러그에 따라 자동으로 지역 대상·인트로·CTA 문구를 결정.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$info  = moondental_get_info();
$home  = home_url( '/' );
$phone = $info['phone'];
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $phone );

// 슬러그에서 지역 판별
$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
$is_asan = ( strpos( $slug, '아산' ) === 0 );
$area    = $is_asan ? '아산' : '천안';
$counter = $is_asan ? '천안' : '아산';

$page_title = $area . ' 추천 치과';
?>

<section class="md-page-hero md-page-hero--recommend">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( $home ); ?>">홈</a> ▸ <span><?php echo esc_html( $page_title ); ?></span>
		</nav>
		<span class="md-page-hero__eyebrow">📍 <?php echo esc_html( $area ); ?> 시 지역 안내</span>
		<h1 class="md-page-hero__title">
			<?php echo esc_html( $page_title ); ?><br>
			<em>왜 문치과병원인가</em>
		</h1>
		<p class="md-page-hero__lead">
			<?php echo esc_html( $area ); ?> 시내에서 문치과병원을 <?php echo esc_html( $area ); ?> 추천 치과로 찾아주시는 이유 —
			1995년부터 <strong>30여년 한자리 진료</strong>, 진료과별 협진, 4개 층 통합 진료실.
		</p>
	</div>
</section>

<section class="md-section">
	<div class="md-container md-container--narrow">
		<h2>왜 <?php echo esc_html( $area ); ?> 추천 치과인가</h2>
		<ul class="md-recommend-reasons">
			<li>
				<h3>1. 30여년 <?php echo esc_html( $area ); ?> 지역 진료</h3>
				<p>한아의료재단 문치과병원은 1995년 개원 이래 <strong>천안·아산 시민과 함께 30여년</strong> 한자리에서 진료해온 종합 치과병원입니다.
				<?php echo esc_html( $area ); ?> 시내와 인근 지역 환자분들이 오래도록 신뢰하고 찾아주시는 곳.</p>
			</li>
			<li>
				<h3>2. 진료과 협진 시스템 · 4개 층 전문 진료실</h3>
				<p>보철·보존·교정·구강외과·구강내과·소아·치주 등 분야별 진료팀이 만남로 문타워 <strong>9·10·11·13층</strong> 4개 층에서 협진.
				<?php echo esc_html( $area ); ?>에서 여러 치과 옮겨 다니지 않고 <strong>한 곳에서 통합 진료</strong>.</p>
			</li>
			<li>
				<h3>3. 디지털 정밀 진단 · 안전한 시술</h3>
				<p>CBCT 3D 진단·PrimeScan 구강스캐너·네비게이션 임플란트 가이드 수술.
				<?php echo esc_html( $area ); ?>에서 정밀 진단이 필요하신 케이스에 특히 추천.</p>
			</li>
			<li>
				<h3>4. 자체 기공실 · 원내 보철 제작</h3>
				<p>13층 원내기공실에서 크라운·인레이·틀니를 직접 제작.
				<?php echo esc_html( $area ); ?>에서 오시는 분들도 빠른 수정·정확한 의사소통·품질 일관성.</p>
			</li>
			<li>
				<h3>5. 전신질환 안심 진료</h3>
				<p>혈압·당·심전도·산소포화도 상시 측정. 고혈압·당뇨·심장질환·항응고제 복용 환자분도
				<?php echo esc_html( $area ); ?>에서 오셔서 안심하고 진료 가능.</p>
			</li>
			<li>
				<h3>6. 평일 야간·토요일 진료</h3>
				<p>월·화·수·금 09:00–20:30 (점심시간 없이 진료) · 목 09:00–18:30 · 토 09:00–14:00.
				<?php echo esc_html( $area ); ?>에서 퇴근 후 출발해도 충분한 진료 시간.</p>
			</li>
			<li>
				<h3>7. 다국어 응대 · 국제 환자</h3>
				<p>한국어·영어·중국어·일본어·베트남어·러시아어·몽골어 안내.
				<?php echo esc_html( $area ); ?> 지역 외국인 환자분들도 편안하게 진료.</p>
			</li>
		</ul>
	</div>
</section>

<section class="md-section md-section--surface">
	<div class="md-container md-container--narrow">
		<h2><?php echo esc_html( $area ); ?>에서 오는 길 · 주요 진료</h2>
		<div class="md-recommend-links">
			<a class="md-recommend-link-card" href="<?php echo esc_url( $home . '오시는-길/' . ( $is_asan ? 'asan' : 'cheonan' ) . '/' ); ?>">
				<strong>📍 <?php echo esc_html( $area ); ?>에서 오시는 길</strong>
				<span>주요 경로 · 대중교통 · 주차 안내</span>
			</a>
			<a class="md-recommend-link-card" href="<?php echo esc_url( $home . '진료항목/임플란트-센터/' ); ?>">
				<strong>🦷 <?php echo esc_html( $area ); ?> 임플란트</strong>
				<span>CBCT 3D · 네비게이션 가이드 · 원내 보철 제작</span>
			</a>
			<a class="md-recommend-link-card" href="<?php echo esc_url( $home . '진료항목/슈어스마일-투명교정/' ); ?>">
				<strong>✨ <?php echo esc_html( $area ); ?> 투명교정</strong>
				<span>슈어스마일 중부권 센터 · 성인·직장인 교정</span>
			</a>
			<a class="md-recommend-link-card" href="<?php echo esc_url( $home . '진료항목/자연치아-살리기/' ); ?>">
				<strong>🌿 <?php echo esc_html( $area ); ?> 자연치아 살리기</strong>
				<span>신경치료·재근관·치수복조술 · 발치 권유 재검토</span>
			</a>
			<a class="md-recommend-link-card" href="<?php echo esc_url( $home . '의료진/' ); ?>">
				<strong>👨‍⚕️ 의료진 소개</strong>
				<span>원장 · 진료과별 협진 진료팀</span>
			</a>
			<a class="md-recommend-link-card" href="<?php echo esc_url( $home . '비용-안내/' ); ?>">
				<strong>💰 전체 비급여 진료비</strong>
				<span>임플란트·교정·라미네이트·크라운 표준 가격표</span>
			</a>
		</div>
	</div>
</section>

<section class="md-section">
	<div class="md-container md-container--narrow">
		<h2><?php echo esc_html( $area ); ?> 추천 치과 · 자주 묻는 질문</h2>
		<div class="md-recommend-faq">
			<details>
				<summary><?php echo esc_html( $area ); ?>에서 문치과병원까지 얼마나 걸리나요?</summary>
				<p><?php if ( $is_asan ) : ?>
					아산 배방·탕정 지역에서 자동차로 약 <strong>20~22분</strong>. 천안아산역에서 시내버스 15분.
					아산 시내 전 지역에서 안정적으로 접근 가능합니다.
				<?php else : ?>
					천안 시내 각 동에서 자동차 <strong>5~20분</strong> 이내.
					천안종합·고속버스터미널 도보 5분 · 천안IC 15분.
					동남구·서북구 어디서든 편리한 접근.
				<?php endif; ?></p>
			</details>
			<details>
				<summary>주차는 편한가요?</summary>
				<p>본원 지하 기계식 무료 주차장 이용 가능. SUV·대형차는 인근 신부 제5공영주차장(동남구 먹거리1길 10) 주차 후
				데스크에 접수하시면 무료 등록됩니다.</p>
			</details>
			<details>
				<summary>다른 진료과 협진이 필요한 케이스도 한 곳에서 되나요?</summary>
				<p>네. 임플란트·교정·구강외과·보존·치주 등 <strong>진료과별 팀이 한 병원에</strong> 있어
				복합 케이스도 한 곳에서 통합 진료. <?php echo esc_html( $area ); ?>에서 여러 치과를 다니실 필요가 없습니다.</p>
			</details>
			<details>
				<summary>야간·주말 진료 되나요?</summary>
				<p>월·화·수·금 <strong>야간 진료 20:30까지</strong> · 목요일 18:30까지 · 토요일 14:00까지.
				<?php echo esc_html( $area ); ?>에서 퇴근 후 방문 충분히 가능.</p>
			</details>
			<details>
				<summary>다국어 응대 가능한가요?</summary>
				<p>한국어·영어·중국어·일본어·베트남어·러시아어·몽골어 안내 가능. 외국인 환자 진료 경험 풍부.</p>
			</details>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php get_footer();
