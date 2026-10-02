<?php
/**
 * v4.20 · 사랑니 발치 페이지 · 「천안에서 사랑니 치과 고를 때 보는 기준」
 *  검색어 「천안 사랑니 전문 치과」로 들어오는 분이 바로 판단할 수 있게 짧게. 디자인은 지역 페이지의 카드와 같다.
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$items = array(
	array( 'CT로 신경 위치를 보는가', '아래 사랑니는 턱 신경관과 가까운 경우가 많습니다. 파노라마 사진만으로는 거리를 알 수 없어, 매복 사랑니는 3D CT를 찍고 뽑는 곳이 안전합니다.' ),
	array( '구강외과 진료가 있는가', '누워 있거나 뼈에 묻힌 사랑니, 신경에 붙은 사랑니는 구강외과 영역입니다. 어려운 경우를 다른 병원으로 보내지 않고 직접 하는지 확인하세요.' ),
	array( '발치 뒤 관리까지 한곳에서', '실밥 제거, 출혈 · 부기 대처, 드라이 소켓(발치 자리 통증) 치료까지 같은 병원에서 받을 수 있어야 합니다. 저녁 · 토요일 진료가 있으면 직장인에게 편합니다.' ),
	array( '비용을 미리 알려 주는가', '사랑니 발치는 건강보험이 적용되며 매복 정도에 따라 본인부담이 달라집니다. CT 비용과 함께 상담 때 총액을 알려 주는지 보세요.' ),
);
?>
<section class="md-section md-section--surface md-section--sm" aria-label="사랑니 치과 고르는 기준">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">선택 기준</span>
			<h2 class="md-section-head__title">천안에서 사랑니 치과를 고를 때 보는 것 4가지</h2>
			<p class="md-section-head__lead">어느 치과에나 똑같이 적용되는 기준입니다. 문치과병원은 구강외과가 있어 CT 진단부터 발치 · 사후관리까지 한곳에서 합니다.</p>
		</header>
		<div class="md-region-reasons">
			<?php foreach ( $items as $i => $it ) : ?>
				<article class="md-region-reason">
					<div class="md-region-reason__num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></div>
					<h3><?php echo esc_html( $it[0] ); ?></h3>
					<p><?php echo esc_html( $it[1] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
