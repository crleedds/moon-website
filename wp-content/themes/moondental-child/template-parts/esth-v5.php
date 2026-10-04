<?php
/**
 * v6.7 · 원장 지시 · 심미치료센터 본문 — 기승전결로 정리 (한국어)
 *  01 원칙 → 02 치료 다섯 가지 → 03 앞니 · 전체 보철 → 04 치료 기간 → 자주 묻는 질문
 *  「라미네이트」는 화면에서 「라미네이트 비니어」로 자동 표기된다 (md_v5_veneer) — 여기서는 「라미네이트」로만 쓴다.
 *  메뉴 앵커(#laminate #aesthetic-resin #whitening #gum-whitening #gummy)는 그대로.
 *
 * @package moondental-child
 */
$faqs = array();
if ( function_exists( 'moondental_get_faqs_by_service' ) ) {
	$m = moondental_get_faqs_by_service();
	$faqs = ! empty( $m['심미치료'] ) ? $m['심미치료'] : array();
}
?>
<div class="v5-impl">

<section class="v5-blk" id="esth-why" aria-label="심미치료 원칙">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">01 · Principle</span>
			<div>
				<h2>깎기 전에, 덜 깎는 방법부터</h2>
				<p>심미치료는 한 번 시작하면 되돌리기 어려운 경우가 많습니다. 미백이나 교정으로 해결되면 그쪽을 먼저 권하고, 필요할 때만 가장 보존적인 방법을 씁니다.</p>
			</div>
		</div>
		<div class="v5-why v5-impl__pillars">
			<div><span class="v5-why__no">0.3mm</span><h3>최소 삭제</h3><p>0.3~0.5mm의 얇은 세라믹으로 자연치아를 최대한 남깁니다.</p></div>
			<div><span class="v5-why__no">3D</span><h3>미리 보는 결과</h3><p>구강 스캔으로 얼굴 · 잇몸 · 치아 비율을 분석해 시작 전에 결과를 시뮬레이션합니다.</p></div>
			<div><span class="v5-why__no">13F</span><h3>원내 기공실 제작</h3><p>병원 안에서 직접 만들어 색 · 형태를 그 자리에서 맞춥니다.</p></div>
		</div>
	</div>
</section>

<section class="v5-blk v5-paper" id="esth-treat" aria-label="심미 치료">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">02 · Treatments</span>
			<div><h2>고민에 맞는 다섯 가지 치료</h2></div>
		</div>
		<dl class="v5-fac v5-esth__list">
			<div id="laminate"><dt>최소삭제 라미네이트</dt><dd>앞니의 색 · 모양 · 길이 · 작은 틈을 함께 고칩니다. e.max · Empress 세라믹을 쓰고, 2~3주에 2회 내원합니다.</dd></div>
			<div id="aesthetic-resin"><dt>심미 레진</dt><dd>거의 깎지 않고 레진을 쌓아 틈 · 깨진 끝 · 잇몸 쪽 패인 곳을 다듬습니다. 1회 내원으로 끝납니다.</dd></div>
			<div id="whitening"><dt>치아 미백</dt><dd>집에서 하는 4주 미백, 병원 1일 · 2일 미백, 두 가지를 함께 하는 복합 미백 중에서 고릅니다.</dd></div>
			<div id="gum-whitening"><dt>잇몸 미백</dt><dd>어두운 잇몸의 색소를 레이저로 지워 분홍빛으로 되돌립니다. 1회 약 30~60분.</dd></div>
			<div id="gummy"><dt>거미스마일</dt><dd>웃을 때 잇몸이 많이 보이는 원인(잇몸 · 치아 길이 · 입술 근육 · 위턱 뼈)을 먼저 찾고, 잇몸 성형 · 보톡스 · 교정 등 원인별로 치료합니다.</dd></div>
			<div><dt>라미네이트와 레진, 무엇이 좋을까</dt><dd>레진은 1회 내원 · 저렴 · 자연치아 보존이 장점이고, 라미네이트는 색이 변하지 않고 더 오래갑니다. 경우와 예산에 맞춰 권해 드립니다.</dd></div>
		</dl>
		<p class="v5-impl__lead">미백 뒤 1주일은 커피 · 홍차 · 와인 · 카레처럼 색이 진한 음식을 피해 주세요. 크라운 · 라미네이트 같은 보철물은 미백으로 밝아지지 않습니다.</p>
	</div>
</section>

<section class="v5-blk" id="esthetic-more" aria-label="앞니 · 전체 보철">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">03 · Prosthetics</span>
			<div><h2>앞니 보철부터 전체 보철 · 틀니까지</h2><p>치아가 빠졌거나 많이 상했을 때는 보철과 진료팀이 가장 보존적인 방법부터 함께 정합니다.</p></div>
		</div>
		<dl class="v5-fac">
			<div><dt>앞니 브릿지</dt><dd>빠진 앞니 양옆 치아에 걸어 빠르게 회복합니다. 1~2주. 양옆 치아를 깎아야 해 임플란트와 비교해 정합니다.</dd></div>
			<div><dt>전악 보철</dt><dd>여러 치아가 닳거나 무너졌을 때 씹기 · 교합 · 턱관절까지 보며 전체를 재건합니다. 수개월~1년.</dd></div>
			<div><dt>임플란트 틀니</dt><dd>임플란트 몇 개로 틀니를 고정해 흔들림을 잡습니다. 일반 틀니보다 잘 씹히고 뼈가 덜 줄어듭니다.</dd></div>
			<div><dt>전체 틀니</dt><dd>치아가 모두 없을 때 수술 없이 씹기 · 발음 · 모양을 회복합니다. 1~2개월.</dd></div>
			<div><dt>부분 틀니</dt><dd>남은 치아에 걸어 쓰는 틀니로, 비용 부담이 적고 수술이 필요 없습니다. 2~3주.</dd></div>
			<div><dt>올세라믹 크라운</dt><dd>자연치아와 비슷한 투명감의 세라믹 크라운입니다. 1~2주.</dd></div>
		</dl>
	</div>
</section>

<?php if ( $faqs ) : ?>
<section class="v5-blk v5-paper" id="esth-faq" aria-label="자주 묻는 질문">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">FAQ</span>
			<div><h2>자주 묻는 질문</h2></div>
		</div>
		<div class="v5-faq">
			<?php foreach ( $faqs as $i => $f ) : ?>
				<details<?php echo 0 === $i ? ' open' : ''; ?>><summary><?php echo esc_html( $f['q'] ); ?></summary><div class="v5-faq__a"><p><?php echo wp_kses_post( $f['a'] ); ?></p></div></details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

</div>
