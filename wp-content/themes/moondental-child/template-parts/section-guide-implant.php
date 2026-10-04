<?php
/**
 * v5.4 · 원장 지시 · 임플란트센터 · 가이드 임플란트 (홍보팀 발표 자료 「설계는 경험으로, 재현은 가이드로」 바탕)
 *  · 증례 사진: 발표 자료의 원내 증례 (환자 식별정보 가림 · OnePros 케이스 번호 가림) — assets/images/guide/
 *  · 문구는 languages/md_phrases_{lang}_v5.php 에 6개 언어 번역
 *
 * @package moondental-child
 */
$gimg = function ( $f ) { return MOONDENTAL_URI . '/assets/images/guide/' . $f . '.jpg'; };
$fig = function ( $f, $cap ) use ( $gimg ) {
	return '<figure class="v5-gcase__fig"><a href="' . esc_url( $gimg( $f ) ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $gimg( $f ) ) . '" alt="' . esc_attr( $cap ) . '" loading="lazy" decoding="async"></a><figcaption>' . esc_html( $cap ) . '</figcaption></figure>';
};
$case = function ( $title, $desc, $figs, $cls = '' ) use ( $fig ) {
	$h = '<article class="v5-gcase ' . esc_attr( $cls ) . '"><h4>' . esc_html( $title ) . '</h4>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '<div class="v5-gcase__figs v5-gcase__figs--' . count( $figs ) . '">';
	$main = array_slice( $figs, 0, 2 ); $more = array_slice( $figs, 2 );
	if ( $more && false === strpos( $cls, 'v5-gcase--plain' ) && count( $figs ) === 4 ) {
		foreach ( $main as $f ) $h .= $fig( $f[0], $f[1] );
		$h .= '</div><details class="v5-gcase__more"><summary>계획 · 수술 후 CT 보기</summary><div class="v5-gcase__figs v5-gcase__figs--2">';
		foreach ( $more as $f ) $h .= $fig( $f[0], $f[1] );
		return $h . '</div></details></article>';
	}
	foreach ( $figs as $f ) $h .= $fig( $f[0], $f[1] );
	return $h . '</div></article>';
};
$story_url = home_url( '/%ec%b9%98%ec%95%84%ec%9d%b4%ec%95%bc%ea%b8%b0%ea%b0%80%ec%9d%b4%eb%93%9c-%ec%9e%84%ed%94%8c%eb%9e%80%ed%8a%b8%eb%84%a4%eb%b9%84%ea%b2%8c%ec%9d%b4%ec%85%98-%ec%9e%84%ed%94%8c%eb%9e%80%ed%8a%b8/' );
?>
<section class="v5-blk v5-paper v5-guide" id="guide-implant" aria-label="가이드 임플란트">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label"><?php echo ! empty( $args['chapter'] ) ? esc_html( $args['chapter'] ) . ' · Planning' : 'Guided Surgery'; ?></span>
			<div>
				<h2>가이드 임플란트 — 설계는 경험으로, 재현은 가이드로</h2>
				<p>CT와 구강 스캔으로 임플란트를 심을 위치 · 각도 · 깊이를 미리 정하고, 그 계획을 원내 3D 프린터로 만든 가이드에 담아 수술 때 그대로 옮깁니다. 위치를 정하는 판단은 30여년 임상 경험이, 그 판단을 정확히 재현하는 일은 가이드가 맡습니다.</p>
			</div>
		</div>

		<ol class="v5-guide__steps">
			<li><span class="v5-guide__no">01</span><h3>보철부터 거꾸로 설계</h3><p>CBCT로 뼈의 두께 · 신경 · 상악동을 확인하고, 마지막에 올라갈 치아 모양에서 거꾸로 임플란트 위치를 정합니다. 보철 · 치주 전문 의료진이 함께 봅니다.</p></li>
			<li><span class="v5-guide__no">02</span><h3>원내에서 가이드 제작</h3><p>설계한 계획을 원내 3D 프린터로 출력해 환자분 치아와 잇몸에 꼭 맞는 수술 가이드를 만듭니다.</p></li>
			<li><span class="v5-guide__no">03</span><h3>계획한 그대로 식립</h3><p>가이드를 입안에 얹고 정해진 위치 · 각도 · 깊이로 심습니다. 수술 뒤 CT로 계획과 실제 위치를 다시 확인합니다.</p></li>
		</ol>

		<div class="v5-guide__sys">
			<h3>두 가지 가이드 시스템을 모두 씁니다</h3>
			<p>메가젠 R2GATE와 오스템 OneGuide를 함께 운용합니다. 두 시스템 모두 뼈의 단단한 정도(골질)를 색으로 보여 주며, 증례에 맞는 쪽을 골라 씁니다.</p>
			<?php echo $case( '', '', array( array( 'bone-r2gate', 'MEGAGEN R2GATE — 단면 전체를 색으로 보여 주는 골질 화면' ), array( 'bone-oneguide', 'OSSTEM OneGuide — 픽스처 표면의 골질을 네 방향으로' ) ), 'v5-gcase--plain' ); ?>
		</div>



		<h3 class="v5-guide__sub">원내 증례 — 계획과 실제</h3>
		<p class="v5-guide__caselead">문치과병원에서 가이드로 식립한 실제 증례입니다. 왼쪽은 수술 전, 오른쪽은 수술 후입니다. 사진을 누르면 크게 볼 수 있습니다.</p>
		<div class="v5-gcases">
			<?php
			echo $case( '윗어금니 상실 — 상악동 거상과 함께', '상악동을 함께 올리면서 가이드로 식립했습니다. 상악동까지의 거리를 미리 재 두어 계획한 그대로 들어갔습니다.', array( array( 'sinus-pre', '수술 전' ), array( 'sinus-post', '수술 후' ), array( 'sinus-plan', '계획 — 단면과 3D에 세운 임플란트' ), array( 'sinus-ct', '수술 후 CT — 실제 식립 위치' ) ) );
			echo $case( '이를 뺀 자리 (#15)', '옆 치아(#14) 자리를 남기려고 임플란트 축을 조정했습니다. 이런 판단이 설계에 그대로 담기고, 가이드 덕분에 뼈이식까지 여유 있게 진행했습니다.', array( array( 'extract-pre', '수술 전' ), array( 'extract-post', '수술 후' ), array( 'extract-plan', '계획 — R2GATE 리포트 (#15 Ø4.4 × 13.0)' ), array( 'extract-ct', '수술 후 CT — 실제 식립된 축' ) ) );
			echo $case( '여러 개를 한 번에 (#14 · #15 · #17)', '세 자리를 한 번의 계획으로 잡고 같은 가이드로 식립했습니다. 여러 개를 심어도 계획대로 재현됩니다.', array( array( 'multi-pre', '수술 전' ), array( 'multi-post', '수술 후' ), array( 'multi-plan', '계획 — 위에서부터 #14 · #15 · #17' ), array( 'multi-ct', '수술 후 CT — 같은 순서' ) ) );
			?>
		</div>

		<div class="v5-guide__flapless">
			<div>
				<span class="v5-label">Flapless</span>
				<h3>잇몸을 절개하지 않는 무절개 임플란트</h3>
				<p>위치와 깊이가 이미 정해져 있어 잇몸을 열어 확인할 필요가 없습니다. 임플란트 굵기만큼 작은 구멍만 내고 심습니다. 가이드가 있어야 가능한 방식입니다.</p>
			</div>
			<ul>
				<li><b>잇몸을 째지 않아요</b><span>CT로 정한 자리에 가이드를 얹고 그대로 심습니다.</span></li>
				<li><b>여러 개도 한 번에요</b><span>위 · 아래 여러 부위를 같은 날 같은 방식으로 심을 수 있습니다.</span></li>
				<li><b>붓기가 적어요</b><span>절개와 봉합이 없어 출혈 · 부종이 적고 실밥을 뺄 일도 없습니다.</span></li>
			</ul>
		</div>
		<div class="v5-gcases v5-gcases--flapless">
			<?php
			echo $case( '무절개 증례 ① — 위 · 아래 여러 개', '위 · 아래 여러 부위를 한 번의 계획으로 잡고, 잇몸을 절개하지 않고 같은 날 식립했습니다. 수술 직후에도 절개선과 봉합이 없습니다.', array( array( 'flapless1-pre', '수술 전' ), array( 'flapless1-post', '수술 후 — 절개 없이 식립' ) ) );
			echo $case( '무절개 증례 ② — 같은 방식으로', '계획한 위치가 그대로 재현되므로 절개가 필요 없습니다. 개수가 늘어도 같은 방식으로 진행합니다.', array( array( 'flapless2-pre', '수술 전' ), array( 'flapless2-post', '수술 후 — 절개 없이 식립' ), array( 'flapless2-oral', '수술 직후 입안 — 절개 없이 치유 지대주 연결' ) ) );
			?>
		</div>
		<p class="v5-guide__note">뼈와 잇몸 상태에 따라 절개나 뼈이식이 필요할 수 있습니다. CT 진단 뒤 가능한 방법을 설명드립니다.</p>

		<div class="v5-guide__sys">
			<h3>심는 날 임시치아까지 — 보철 연계</h3>
			<p>오스템 OnePros를 원내에 갖추어, 가이드로 정한 위치를 그대로 이어받아 기둥(어버트먼트)과 임시치아를 수술 전에 미리 설계합니다. 증례에 따라 심는 날 바로 임시치아를 끼워 드리는 것을 목표로 합니다.</p>
			<?php echo $case( '', '', array( array( 'onepros-case', 'OneGuide 계획을 불러온 화면 — 계획한 임플란트가 그대로 들어와 있습니다' ), array( 'onepros-abutment', '기성 기둥(어버트먼트) 선택 — 직경 · 높이 · 잇몸 높이' ) ), 'v5-gcase--plain' ); ?>
		</div>

		<blockquote class="v5-guide__quote">설계는 경험이 하고, 재현은 가이드가 합니다.</blockquote>

		<a class="v5-more" href="<?php echo esc_url( $story_url ); ?>">치아이야기 · 가이드 임플란트, 중요한 건 「설계」 →</a>
	</div>
</section>
