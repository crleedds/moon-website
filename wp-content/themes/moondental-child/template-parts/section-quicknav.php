<?php
/**
 * Section: 핵심 페이지 바로가기 (v3.44.79)
 *  홈페이지 하단에 콘텐츠 링크 카드 배치 · 구글 사이트링크 후보 시그널 강화
 *  각 카드는 명확한 앵커 텍스트 + 설명 문구를 갖춰 sitelinks 노출 확률 상승 유도
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$home = home_url( '/' );
$items = array(
	array(
		'title' => md_content( 'quicknav_recommend_cheonan_title', '천안 추천 치과' ),
		'desc'  => md_content( 'quicknav_recommend_cheonan_desc', '천안 전 지역에서 오시는 길 · 30년 진료 · 진료과 협진' ),
		'url'   => $home . '오시는-길/cheonan/',
		'icon'  => '🏆',
	),
	array(
		'title' => md_content( 'quicknav_recommend_asan_title', '아산 추천 치과' ),
		'desc'  => md_content( 'quicknav_recommend_asan_desc', '아산에서 20~22분 · 진료과 협진 · 안심 진료' ),
		'url'   => $home . '오시는-길/asan/',
		'icon'  => '🏆',
	),
	array(
		'title' => md_content( 'quicknav_doctors_title', '의료진 소개' ),
		'desc'  => md_content( 'quicknav_doctors_desc',  '천안·아산 30년 진료 · 원장·진료과 협진 시스템 · 4개 층 통합 진료' ),
		'url'   => $home . '의료진/',
		'icon'  => '👨‍⚕️',
	),
	array(
		'title' => md_content( 'quicknav_location_title', '오시는 길' ),
		'desc'  => md_content( 'quicknav_location_desc',  '천안 만남로 52 문타워 · 지하 주차장 · 천안IC 15분 · 시내버스 안내' ),
		'url'   => $home . '오시는-길/',
		'icon'  => '📍',
	),
	array(
		'title' => md_content( 'quicknav_pricing_title', '전체 비급여 진료비' ),
		'desc'  => md_content( 'quicknav_pricing_desc',  '임플란트·투명교정·라미네이트·크라운·심미치료 표준 가격표' ),
		'url'   => $home . '비용-안내/',
		'icon'  => '💰',
	),
	array(
		'title' => md_content( 'quicknav_implant_title', '천안·아산 임플란트' ),
		'desc'  => md_content( 'quicknav_implant_desc',  'CBCT 3D 진단 · 네비게이션 가이드 · 단일·다수·전악 임플란트' ),
		'url'   => $home . '진료항목/임플란트-센터/',
		'icon'  => '🦷',
	),
	array(
		'title' => md_content( 'quicknav_ortho_title', '천안·아산 투명교정 · 슈어스마일' ),
		'desc'  => md_content( 'quicknav_ortho_desc',  '중부권 슈어스마일 센터 · 0.1mm 정밀도 · 성인·직장인 투명교정' ),
		'url'   => $home . '진료항목/슈어스마일-투명교정/',
		'icon'  => '✨',
	),
	array(
		'title' => md_content( 'quicknav_preserve_title', '자연치아 살리기' ),
		'desc'  => md_content( 'quicknav_preserve_desc',  '신경치료·치주치료·치수복조술 · 발치 권유받으신 치아 다시 검토' ),
		'url'   => $home . '진료항목/자연치아-살리기/',
		'icon'  => '🌿',
	),
	array(
		'title' => md_content( 'quicknav_encyclopedia_title', '치과 백과사전' ),
		'desc'  => md_content( 'quicknav_encyclopedia_desc',  '치과 용어·치료·질환·예방 총정리 · 환자용 종합 사전' ),
		'url'   => $home . '치과사전/',
		'icon'  => '📚',
	),
	array(
		'title' => md_content( 'quicknav_reservation_title', '상담 예약' ),
		'desc'  => md_content( 'quicknav_reservation_desc',  '카카오톡·네이버·전화 예약 · 다국어 응대 · 편한 시간 선택' ),
		'url'   => $home . '상담예약/',
		'icon'  => '📅',
	),
);
?>
<section class="md-section md-section--surface md-quicknav" aria-labelledby="quicknav-title">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'quicknav_eyebrow', '🔗 문치과병원 주요 페이지' ) ); ?></span>
			<h2 id="quicknav-title" class="md-section-head__title"><?php echo esc_html( md_content( 'quicknav_title', '문치과병원을 둘러보세요' ) ); ?></h2>
			<p class="md-section-head__lead"><?php echo esc_html( md_content( 'quicknav_lead', '천안 만남로 문타워 문치과병원 · 의료진·오시는 길·비용·주요 진료 안내를 한 번에.' ) ); ?></p>
		</header>
		<div class="md-quicknav__grid">
			<?php foreach ( $items as $item ) : ?>
				<a class="md-quicknav__card" href="<?php echo esc_url( $item['url'] ); ?>">
					<span class="md-quicknav__icon" aria-hidden="true"><?php echo esc_html( $item['icon'] ); ?></span>
					<span class="md-quicknav__body">
						<span class="md-quicknav__title"><?php echo esc_html( $item['title'] ); ?></span>
						<span class="md-quicknav__desc"><?php echo esc_html( $item['desc'] ); ?></span>
					</span>
					<span class="md-quicknav__arrow" aria-hidden="true">→</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
