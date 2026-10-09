<?php
/**
 * v8 · 원장 지시 · 자연치아보존센터 › 스케일링 & 잇몸치료 (세부 페이지 본문)
 *  01 스케일링 → 02 스케일링 뒤 · 주기 → 03 잇몸치료(단계) → 자주 묻는 질문(+ FAQPage 구조화 데이터)
 *  사실 기준: 보험 「만 19세 이상 연 1회」, 치근활택술 · 치주소파술 보험, PDRN 비급여, 에어플로우 비급여 — 기존 사이트 문구 그대로.
 *  야간진료는 월 · 화 · 수 · 금 20:30 까지 (푸터 진료시간과 같게).
 */
$md_perio_faq = array(
	array( '스케일링 아픈가요?', '대부분 시큰한 정도로 끝납니다. 잇몸 염증이 심하거나 시린 증상이 강한 분은 부분 마취를 하고 편하게 진행할 수 있으니 미리 말씀해 주세요.' ),
	array( '보험 스케일링은 누구나 받을 수 있나요?', '만 19세 이상이면 1년에 한 번 건강보험이 적용됩니다. 올해 이미 받으셨는지는 접수에서 바로 확인해 드립니다.' ),
	array( '스케일링을 하면 이가 깎이거나 벌어지나요?', '아니요. 치아는 깎지 않고 치아에 붙은 치석만 떼어 냅니다. 이 사이가 벌어진 느낌은 치석이 메우고 있던 자리가 드러난 것이고, 잇몸 염증이 가라앉으면서 점점 줄어듭니다.' ),
	array( '양치할 때 피가 나는데, 살살 닦아야 하나요?', '피가 나는 것은 잇몸 염증의 신호입니다. 부드러운 칫솔로 잇몸 경계까지 꼼꼼히 닦고 치실 · 치간칫솔을 함께 쓰는 것이 좋습니다. 일주일 넘게 계속되면 잇몸 검진을 받아 보세요.' ),
	array( '스케일링과 잇몸치료는 무엇이 다른가요?', '스케일링은 잇몸 위쪽과 경계의 치석을 떼는 기본 관리이고, 잇몸치료(치근활택술 · 치주소파술)는 잇몸 속 깊은 뿌리 표면의 치석과 염증 조직까지 정리하는 치료입니다. 둘 다 대부분 건강보험이 적용됩니다.' ),
	array( '에어플로우는 무엇인가요?', '아주 고운 파우더와 물을 뿜어 세균막과 커피 · 차 · 담배 착색을 지우는 방법입니다. 스케일링 뒤 표면을 매끈하게 마무리해 착색이 덜 붙게 돕습니다(비급여, 미리 안내).' ),
	array( '임신 중에도 스케일링을 받을 수 있나요?', '임신 중에는 잇몸이 붓고 피가 나기 쉬워 관리가 더 중요합니다. 비교적 편한 시기는 임신 4~6개월(중기)이며, 진료 전에 임신 사실과 주수를 꼭 알려 주세요.' ),
);
?>
<section class="v5-blk" id="scaling" aria-label="스케일링">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">Scaling</span>
			<div>
				<h2>스케일링 — 1년에 한 번, 건강보험으로</h2>
				<p>치석은 양치로는 떨어지지 않습니다. 치아와 잇몸 경계에 굳은 치석과 세균막을 떼어 내는 스케일링은 잇몸병을 막는 가장 기본 치료입니다. 만 19세 이상은 1년에 한 번 건강보험이 적용됩니다.</p>
			</div>
		</div>
		<div class="v5-why v5-impl__pillars">
			<div><span class="v5-why__no">연 1회</span><h3>건강보험 적용</h3><p>만 19세 이상이면 1년에 한 번 보험이 됩니다. 올해 받으셨는지 접수에서 바로 확인해 드립니다.</p></div>
			<div><span class="v5-why__no">검진</span><h3>잇몸까지 함께 봅니다</h3><p>치석만 떼고 끝내지 않고, 잇몸 주머니 · 충치 · 기존 치료 부위를 함께 점검합니다.</p></div>
			<div><span class="v5-why__no">20:30</span><h3>퇴근 후에도</h3><p>월 · 화 · 수 · 금은 저녁 8시 30분까지 진료해 직장인도 부담 없이 오실 수 있습니다.</p></div>
		</div>

		<h3 class="v5-impl__sub">스케일링은 이렇게 진행합니다</h3>
		<ol class="v5-impl__flow v5-impl__flow--5">
			<li><b>구강 검진</b><span>치석 · 잇몸 출혈 · 충치를 먼저 확인합니다.</span></li>
			<li><b>초음파 스케일링</b><span>미세한 진동과 물로 치석을 부수어 떼어 냅니다.</span></li>
			<li><b>잇몸 경계 정리</b><span>잇몸 바로 아래 남은 치석을 손 기구로 꼼꼼히 정리합니다.</span></li>
			<li><b>에어플로우 (선택)</b><span>세균막 · 착색을 지워 표면을 매끈하게 마무리합니다.</span></li>
			<li><b>관리법 안내</b><span>내 잇몸에 맞는 칫솔질 · 치실 · 치간칫솔 사용법을 알려 드립니다.</span></li>
		</ol>

		<div class="v5-impl__twocol">
			<div>
				<h3 class="v5-impl__sub">스케일링 뒤, 이런 느낌은 정상입니다</h3>
				<ul class="v5-impl__dots">
					<li><b>이 사이가 벌어진 느낌</b> — 치석이 메우던 자리가 드러난 것으로, 잇몸이 가라앉으며 줄어듭니다</li>
					<li><b>하루 이틀 시림</b> — 치석에 덮여 있던 면이 드러나서입니다. 대개 며칠 안에 줄어듭니다</li>
					<li><b>약간의 피</b> — 염증이 있던 잇몸입니다. 당일은 맵고 뜨거운 음식을 피해 주세요</li>
					<li><b>치아는 깎이지 않습니다</b> — 치아에 붙은 치석만 떼어 냅니다</li>
				</ul>
			</div>
			<div>
				<h3 class="v5-impl__sub">얼마나 자주 받아야 할까요</h3>
				<ul class="v5-impl__dots">
					<li>잇몸이 건강하면 6개월 ~ 1년마다</li>
					<li>잇몸병 치료를 받았거나 당뇨 · 흡연이 있으면 3 ~ 4개월마다</li>
					<li>임플란트 · 교정 중이면 담당 원장님이 정해 드린 주기로</li>
					<li>임신 중이면 비교적 편한 4 ~ 6개월(중기)에</li>
				</ul>
			</div>
		</div>
	</div>
</section>

<section class="v5-blk v5-paper" id="perio" aria-label="잇몸치료">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">Periodontics</span>
			<div><h2>잇몸치료 — 스케일링으로 부족할 때</h2><p>잇몸 출혈 · 붓기 · 입냄새가 계속되면 치석이 잇몸 속 뿌리까지 내려간 치주염일 수 있습니다. 잇몸 주머니 깊이와 엑스레이로 진행 정도를 확인하고, 단계에 맞춰 치료한 뒤 정기적으로 관리합니다.</p></div>
		</div>
		<ol class="v5-impl__flow">
			<li><b>스케일링</b><span>치은염 단계. 연 1회 건강보험 적용.</span></li>
			<li><b>치근활택술</b><span>잇몸 주머니가 깊어지기 시작할 때 뿌리 표면을 매끈하게. 보험 적용.</span></li>
			<li><b>치주소파술</b><span>주머니 5mm 이상 · 깊은 염증 조직 제거. 보험 적용.</span></li>
			<li><b>치주 수술 · 골 이식</b><span>뼈가 녹은 중증 단계에서 치아를 살리는 시도.</span></li>
			<li><b>유지관리(SPT)</b><span>치료 뒤 3~6개월마다 점검 · 스케일링.</span></li>
			<li><b>PDRN 주사</b><span>잇몸 염증 완화 · 재생을 돕는 보조 치료(비급여).</span></li>
		</ol>
		<div class="v5-impl__twocol">
			<div>
				<h3 class="v5-impl__sub">두 가지 이상이면 잇몸 검진을 권합니다</h3>
				<ul class="v5-impl__dots">
					<li>양치할 때 피가 난다</li>
					<li>잇몸이 붓고 붉다</li>
					<li>입냄새가 늘었다</li>
					<li>치아가 길어 보이거나 사이에 음식이 자주 낀다</li>
					<li>씹을 때 치아가 흔들리거나 들뜬 느낌이 있다</li>
				</ul>
			</div>
			<div>
				<h3 class="v5-impl__sub">잇몸은 몸 건강과도 이어집니다</h3>
				<ul class="v5-impl__dots">
					<li>잇몸병은 당뇨 · 심혈관 질환과 서로 영향을 주는 것으로 알려져 있습니다</li>
					<li>잇몸이 무너지면 치아를 잃고, 임플란트도 같은 이유로 실패할 수 있습니다</li>
					<li>전신질환이 있으면 내과 협진으로 안전하게 진행합니다</li>
				</ul>
			</div>
		</div>
	</div>
</section>

<section class="v5-blk" id="perio-faq" aria-label="스케일링 · 잇몸치료 자주 묻는 질문">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">FAQ</span>
			<div><h2>스케일링 · 잇몸치료, 자주 묻는 질문</h2></div>
		</div>
		<div class="v5-faq">
			<?php foreach ( $md_perio_faq as $i => $f ) : ?>
				<details<?php echo 0 === $i ? ' open' : ''; ?>><summary><?php echo esc_html( $f[0] ); ?></summary><div class="v5-faq__a"><p><?php echo esc_html( $f[1] ); ?></p></div></details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<script type="application/ld+json"><?php
echo wp_json_encode( array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map( function ( $f ) {
		return array( '@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ) );
	}, $md_perio_faq ),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
?></script>
