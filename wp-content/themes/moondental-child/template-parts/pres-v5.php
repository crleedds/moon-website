<?php
/**
 * v6.7 · 원장 지시 · 자연치아보존센터 본문 — 기승전결로 정리 (한국어)
 * v8 · 원장 지시 · 진료별 본문은 각각 다른 페이지로 (inc/pres-pages.php · template-parts/pres-v5/{key}.php)
 *  본 페이지: 01 원칙(가장 작은 개입부터) + 진료 다섯 가지 바로가기 → 02 증상으로 찾기
 *  예전 앵커(#cavity #vpt #endo #perio #spa)로 들어오면 세부 페이지로 넘어간다 (pres-pages.php).
 *
 * @package moondental-child
 */
$u = function ( $k ) { return esc_url( md_pres_sub_url( $k ) ); };
$pages = md_pres_sub_pages();
?>
<div class="v5-impl">

<section class="v5-blk" id="pres-why" aria-label="자연치아보존 원칙">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">01 · Principle</span>
			<div>
				<h2>가장 작은 개입부터, 차례로</h2>
				<p>뽑기 전에 살리고, 깎기 전에 덜 깎는 방법부터 봅니다. 충치 · 신경 · 잇몸 치료를 단계별로 이어 가고, 치료가 끝나면 덴탈SPA로 다시 생기지 않게 관리합니다.</p>
			</div>
		</div>
		<ol class="v5-impl__flow v5-impl__flow--5">
			<?php foreach ( $pages as $k => $p ) : ?>
				<li><a href="<?php echo $u( $k ); ?>"><b><?php echo esc_html( $p['menu'] ); ?></b><span><?php echo esc_html( $p['card'] ); ?></span></a></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="v5-blk v5-paper" id="pres-find" aria-label="증상으로 찾기">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">02 · Find</span>
			<div><h2>지금 불편한 곳으로 찾아보세요</h2><p>정확한 치료는 진단 뒤에 정하지만, 증상으로 어떤 진료가 필요할지 먼저 살펴보실 수 있습니다.</p></div>
		</div>
		<dl class="v5-fac">
			<div><dt>찬물에 시리고, 검게 변한 곳이 보여요</dt><dd>초기 · 중기 충치일 수 있습니다. <a href="<?php echo $u( 'cavity' ); ?>">충치치료 →</a></dd></div>
			<div><dt>충치가 깊어 신경치료를 하자고 들었어요</dt><dd>신경을 일부라도 살릴 수 있는지 먼저 봅니다. <a href="<?php echo $u( 'vpt' ); ?>">부분신경치료 →</a></dd></div>
			<div><dt>가만히 있어도 욱신거리고, 밤에 더 아파요</dt><dd>신경까지 염증이 번졌을 수 있습니다. <a href="<?php echo $u( 'endo' ); ?>">신경치료 →</a></dd></div>
			<div><dt>양치할 때 피가 나고, 잇몸이 부어요</dt><dd>치석 · 잇몸 염증의 신호입니다. 1년에 한 번 보험 스케일링부터. <a href="<?php echo $u( 'perio' ); ?>">스케일링 &amp; 잇몸치료 →</a></dd></div>
			<div><dt>치료는 끝났는데 오래 지키고 싶어요</dt><dd>정기 관리 프로그램으로 이어 갑니다. <a href="<?php echo $u( 'spa' ); ?>">덴탈SPA →</a></dd></div>
			<div><dt>다른 곳에서 발치를 권유받았어요</dt><dd>살릴 수 있는 치아인지 CBCT · 현미경으로 한 번 더 살펴봐 드립니다. <a href="<?php echo $u( 'endo' ); ?>">신경치료 · 재신경치료 →</a></dd></div>
		</dl>
	</div>
</section>

</div>
