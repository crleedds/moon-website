<?php
/**
 * Guide Data — 천안 임플란트 종합 안내서
 *
 * 학술 근거 + 2026 최신 기준 + 문치과병원 30여년 임상 관점.
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

return array(
	'slug'         => 'implant',
	'code'         => 'NO. 01',
	'icon'         => '🦷',
	'eyebrow'      => '천안·아산 대표 치과병원 · 30여년 임상',
	'center'       => '임플란트센터',
	'title'        => '임플란트 종합안내서',
	'subtitle'     => '1990년대부터 임플란트를 식립해온 문치과병원 임플란트센터 · 30여년 임상 노하우로 임플란트 종류·비용·과정·수명·부작용을 학술 근거와 함께 정리',
	'reading'      => '약 25분',
	'updated'      => '2026.08',
	'tags'         => array( '35+ FAQ', '17 섹션', '학술 근거' ),
	'summary'      => '1990년대부터 임플란트 식립을 이어온 30여년 임상 노하우 · Cochrane Review·Pjetursson 2007 등 학술 근거 기반. 골이식·상악동거상술·디지털 임플란트까지 한 페이지에.',
	'cta_page'     => '/임플란트/',
	'cta_label'    => '문치과병원 임플란트 진료 페이지',
	'related'      => array(
		array( 'label' => '교정센터 투명교정 종합안내서',      'href' => '/guide/suresmile/', 'icon' => '😁' ),
		array( 'label' => '스마일디자인센터 라미네이트 종합안내서', 'href' => '/guide/laminate/',  'icon' => '✨' ),
	),
	'toc' => array(
		array( 'id' => 'what',      'label' => '임플란트란 무엇인가' ),
		array( 'id' => 'structure', 'label' => '구조와 골유착 원리' ),
		array( 'id' => 'types',     'label' => '임플란트 종류 비교' ),
		array( 'id' => 'cost',      'label' => '비용 총정리 (2026)' ),
		array( 'id' => 'process',   'label' => '수술 과정 7단계' ),
		array( 'id' => 'lifespan',  'label' => '수명과 관리법' ),
		array( 'id' => 'risks',     'label' => '부작용과 실패 원인' ),
		array( 'id' => 'insurance', 'label' => '건강보험·실비보험' ),
		array( 'id' => 'compare',   'label' => '임플란트 vs 브릿지 vs 틀니' ),
		array( 'id' => 'special',   'label' => '특수 케이스별 안내' ),
		array( 'id' => 'graft',     'label' => '골이식·상악동거상술' ),
		array( 'id' => 'digital',   'label' => '디지털·네비게이션 임플란트' ),
		array( 'id' => 'evidence',  'label' => '임상 데이터·학술 근거' ),
		array( 'id' => 'clinic',    'label' => '좋은 치과 고르는 법' ),
		array( 'id' => 'moon',      'label' => '문치과병원 임플란트센터' ),
		array( 'id' => 'faq',       'label' => '자주 묻는 질문 30선' ),
	),
	'sections' => array(

		array(
			'id'    => 'what',
			'title' => '01 · 임플란트란 무엇인가',
			'body'  => '<p>임플란트는 상실된 치아를 대체하기 위해 <strong>턱뼈에 인공 치근(픽스처)을 심고 그 위에 인공 치아(크라운)를 장착</strong>하는 치료입니다. 1965년 스웨덴 브레네막(P-I Brånemark) 교수의 <em>골유착(osseointegration)</em> 발견 이후 60여 년간 4세대에 걸쳐 진화해 왔습니다.</p>
			<p>브레네막 교수의 첫 환자 <strong>Gösta Larsson은 임플란트를 41년간 사용</strong>하며 반영구적 수명을 입증했습니다. 오늘날 임플란트 5년 생존율은 95~98%, 10년 생존율은 92~95%에 이릅니다.</p>
			<div class="md-guide-callout md-guide-callout--info">
				<strong>왜 임플란트인가?</strong><br>
				치아가 상실되면 저작 자극이 사라져 <strong>1년 안에 치조골이 수평 25%·수직 4mm 흡수</strong>됩니다 (Schropp et al., 2003). 임플란트는 인공 치근이 저작력을 뼈에 전달하여 <strong>골 흡수를 방지하는 유일한 치아 대체 방법</strong>입니다.
			</div>
			<div class="md-guide-callout">
				<strong>🏥 문치과병원은 1990년대부터 임플란트를 식립해온 병원입니다</strong><br>
				한국에 임플란트가 본격 도입된 1990년대 초부터 30여년간 임플란트 진료를 이어오며, <strong>국내에서 가장 오래된 임플란트 임상 경험</strong>을 축적해 왔습니다. 초기 브랜드부터 최신 디지털 임플란트까지 <strong>세대별 특성·장기 예후·부작용 대처</strong>를 실제 환자 케이스로 검증해온 임상 노하우입니다.
			</div>',
		),

		array(
			'id'    => 'structure',
			'title' => '02 · 임플란트 구조와 골유착 원리',
			'body'  => '<h3>3가지 구성 요소</h3>
			<ol>
				<li><strong>픽스처 (인공 치근)</strong> · 티타늄 소재로 뼈와 분자 단위 결합. 표면 처리 방식(SLA·SLActive·RBM·TiUnite)에 따라 골유착 속도 차이.</li>
				<li><strong>지대주 (연결 기둥)</strong> · 티타늄·지르코니아·금합금 등. 픽스처와 크라운을 연결.</li>
				<li><strong>크라운 (인공 치아)</strong> · 지르코니아·PFM·올세라믹·금 등 심미·강도 요구에 따라 선택.</li>
			</ol>
			<h3>골유착 원리</h3>
			<p>골유착은 <strong>티타늄 표면 산화막에 조골세포가 부착·증식하여 뼈와 분자 단위로 결합하는 현상</strong>입니다. 골유착 기간은:</p>
			<ul>
				<li><strong>하악(아래턱)</strong> · 2~3개월 (골밀도 높음)</li>
				<li><strong>상악(위턱)</strong> · 4~6개월 (골밀도 낮음)</li>
				<li>SLActive 표면은 하악 <strong>3~4주</strong>까지 단축 가능</li>
			</ul>
			<p>현대 임플란트 성공률은 <strong>97~99%</strong> 수준입니다 (전문 술자·양호한 환자 조건).</p>',
		),

		array(
			'id'    => 'types',
			'title' => '03 · 임플란트 종류 완벽 비교',
			'body'  => '<h3>브랜드별 비교 (2026)</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>브랜드</th><th>국적</th><th>비용/개</th><th>특징</th></tr></thead>
				<tbody>
					<tr><td>스트라우만</td><td>스위스</td><td>150~250만</td><td>세계 1위, SLActive, 골유착 3~4주</td></tr>
					<tr><td>노벨바이오케어</td><td>스위스/스웨덴</td><td>150~250만</td><td>임플란트 원조, TiUnite 표면</td></tr>
					<tr><td>아스트라</td><td>스웨덴</td><td>130~200만</td><td>OsseoSpeed, 심미 영역 우수</td></tr>
					<tr><td>오스템</td><td>한국</td><td>80~120만</td><td>세계 3위, 가성비 우수</td></tr>
					<tr><td>덴티움</td><td>한국</td><td>80~120만</td><td>S.L.A 표면</td></tr>
					<tr><td>네오</td><td>한국</td><td>70~100만</td><td>경제적 선택</td></tr>
					<tr><td>메가젠</td><td>한국</td><td>80~120만</td><td>연질골·즉시 식립 강점</td></tr>
				</tbody>
			</table></div>
			<div class="md-guide-callout">
				<strong>💡 비싼 게 무조건 좋을까?</strong><br>
				브랜드보다 <strong>의사의 경험·술기·계획</strong>이 훨씬 중요합니다. 국산 임플란트도 10년 생존율 95% 이상 학술 데이터가 존재합니다. 예산에 맞게 <strong>믿을 만한 술자 → 검증된 브랜드</strong> 순서로 선택하세요.
			</div>
			<h3>식립 방식별 비교</h3>
			<ul>
				<li><strong>일반 임플란트</strong> · 표준 2회 수술, 3~6개월</li>
				<li><strong>즉시 임플란트</strong> · 발치 당일 식립 (골 조건 충족 시)</li>
				<li><strong>즉시 부하</strong> · 식립 당일 임시 치아 (초기 고정력 35Ncm+)</li>
				<li><strong>비절개 임플란트</strong> · 무절개, 출혈·부기 최소</li>
				<li><strong>네비게이션 임플란트</strong> · 실시간 3D 추적, 오차 0.5mm 이내</li>
				<li><strong>수면 임플란트</strong> · 의식하 진정, 공포·구역 반사 심한 분</li>
			</ul>
			<h3>적응증별 유형</h3>
			<ul>
				<li>단일 치아 (60% 비중)</li>
				<li>다수 치아 (2~5개)</li>
				<li>전악 임플란트 (All-on-4 / All-on-6)</li>
				<li>임플란트 틀니 (오버덴처)</li>
			</ul>',
		),

		array(
			'id'    => 'cost',
			'title' => '04 · 임플란트 비용 총정리 (2026)',
			'body'  => '<h3>기본 비용 구조</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>국산</th><th>해외 프리미엄</th></tr></thead>
				<tbody>
					<tr><td>픽스처 (재료)</td><td>20~35만</td><td>50~80만</td></tr>
					<tr><td>수술비</td><td>25~40만</td><td>30~50만</td></tr>
					<tr><td>크라운 (보철)</td><td>25~40만</td><td>40~70만</td></tr>
					<tr class="md-guide-table__total"><td>합계 (1개)</td><td>80~120만</td><td>150~250만</td></tr>
				</tbody>
			</table></div>
			<h3>추가 비용 (필요시)</h3>
			<ul>
				<li>뼈이식 · 30~80만</li>
				<li>상악동거상술 · 50~150만</li>
				<li>잇몸이식 · 20~50만</li>
				<li>CT (CBCT) · 5~10만</li>
				<li>수면 마취 · 15~30만</li>
				<li>네비게이션 가이드 · 10~30만</li>
				<li>임시 치아 · 5~15만</li>
			</ul>
			<h3>케이스별 예상 비용</h3>
			<ol>
				<li><strong>어금니 1개 · 국산</strong> · 약 80~120만원</li>
				<li><strong>앞니 1개 · 프리미엄 + 잇몸이식</strong> · 약 200~300만원</li>
				<li><strong>위턱 어금니 · 국산 + 뼈이식·상악동거상술</strong> · 약 150~250만원</li>
				<li><strong>전악 All-on-4</strong> · 약 800~1,500만원</li>
			</ol>
			<div class="md-guide-callout md-guide-callout--warn">
				<strong>⚠️ 극저가 광고 주의</strong><br>
				1개 <strong>50만원 이하</strong>는 미인증 저가 픽스처·저가 보철물·별도 부가 시술비 사후 청구 가능성이 높습니다. 총 비용·보증·의료진 경력을 반드시 확인하세요.
			</div>',
		),

		array(
			'id'    => 'process',
			'title' => '05 · 임플란트 수술 과정 7단계',
			'body'  => '<ol class="md-guide-steps">
				<li><strong>1단계 · 정밀 검사와 진단</strong><br>파노라마·CBCT·구강 검사·전신 건강 확인. CBCT로 턱뼈 높이·너비·밀도를 3D 분석하고 신경관·상악동과의 거리 정밀 측정. 30~60분 소요.</li>
				<li><strong>2단계 · 치료 계획 수립</strong><br>CT 데이터 기반으로 임플란트 위치·각도·깊이 결정. 디지털 계획 소프트웨어로 가상 배치, 역방향(Top-down) 설계로 최종 보철물까지 고려.</li>
				<li><strong>3단계 · 사전 처치</strong><br>발치·잔존 치근 제거·잇몸 치료. 필요 시 골이식 (4~6개월 후 식립). 조건 충족 시 발치+식립 동시 진행.</li>
				<li><strong>4단계 · 임플란트 식립 수술</strong><br>국소 또는 수면 마취 → 잇몸 절개 → 드릴링 → 픽스처 식립 → 봉합. 1개당 20~40분. 네비게이션 사용 시 0.5mm 오차 내 정밀 식립.</li>
				<li><strong>5단계 · 골유착 기간</strong><br>하악 2~3개월 · 상악 4~6개월. 수술 부위 과도한 힘 금지. SLActive 표면은 3~4주로 단축 가능.</li>
				<li><strong>6단계 · 2차 수술·보철 제작</strong><br>골유착 확인 후 잇몸 절개 → 지대주 연결 → 디지털 구강 스캐너로 정밀 인상 → 대합치 교합 분석. 원내 기공소 당일~3일, 외부 의뢰 1~2주.</li>
				<li><strong>7단계 · 최종 보철 장착·유지관리</strong><br>크라운 장착 → 교합 미세 조정. 1·3·6·12개월 정기 검진. 이후 연 1~2회 검진 + 3~6개월마다 전문 세정.</li>
			</ol>',
		),

		array(
			'id'    => 'lifespan',
			'title' => '06 · 임플란트 수명과 올바른 관리',
			'body'  => '<h3>구성별 수명</h3>
			<ul>
				<li><strong>픽스처 (인공 치근)</strong> · 반영구적. 30년 이상 사용 가능 (브레네막 첫 환자 41년 기록).</li>
				<li><strong>크라운 (상부 보철)</strong> · 10~20년 후 교체 필요할 수 있음.</li>
			</ul>
			<h3>수명 결정 5가지 요인</h3>
			<ol>
				<li><strong>구강 위생</strong> · 가장 중요. 임플란트 주위염이 최대 위협.</li>
				<li><strong>정기 검진</strong> · 6개월~1년마다. Heitz-Mayfield (2008): 정기 검진 시 주위염 4.1%, 미시행 시 39.5%.</li>
				<li><strong>흡연 여부</strong> · 흡연자 실패율 2~3배.</li>
				<li><strong>전신 건강</strong> · 당뇨(HbA1c ≤7%)·골다공증·면역억제제.</li>
				<li><strong>교합력</strong> · 이갈이·이악물기는 야간 마우스가드 필수.</li>
			</ol>
			<h3>매일 관리법</h3>
			<ul>
				<li>부드러운 칫솔로 하루 3회 이상 양치</li>
				<li>치간칫솔·워터픽으로 임플란트 주위 청소</li>
				<li>임플란트 전용 저연마제 치약</li>
				<li>알코올 무함유 불소 가글</li>
			</ul>
			<h3>피해야 할 것</h3>
			<ul>
				<li>얼음·사탕·오징어 등 딱딱한 것 깨물기</li>
				<li>수술 후 흡연 (골유착 실패 최대 원인)</li>
				<li>정기 검진 빠뜨리기</li>
				<li>이갈이 방치</li>
				<li>임플란트 주위 출혈 무시</li>
			</ul>',
		),

		array(
			'id'    => 'risks',
			'title' => '07 · 부작용과 실패 원인',
			'body'  => '<h3>수술 중·직후 합병증</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>합병증</th><th>발생률</th><th>증상</th><th>대처</th></tr></thead>
				<tbody>
					<tr><td>하치조신경 손상</td><td>0.5~5%</td><td>하순·턱 감각이상</td><td>대부분 3~6개월 회복</td></tr>
					<tr><td>상악동 천공</td><td>1~3%</td><td>코 불편감</td><td>상악동거상술로 보완</td></tr>
					<tr><td>수술 후 감염</td><td>1~2%</td><td>부기·발열·고름</td><td>항생제 투여</td></tr>
					<tr><td>과다 출혈</td><td>드묾</td><td>지속 출혈</td><td>압박 지혈</td></tr>
					<tr><td>인접 치아 손상</td><td>매우 드묾</td><td>옆 치아 뿌리 접촉</td><td>CT 정밀 계획으로 예방</td></tr>
				</tbody>
			</table></div>
			<h3>장기 합병증</h3>
			<ol>
				<li><strong>임플란트 주위염</strong> · 5~15% 발생, 골 흡수 위험. 원인: 위생 불량·흡연·당뇨·검진 미시행.</li>
				<li><strong>골유착 실패</strong> · 2~3% 발생, 초기 3개월 내 주로 발생. 원인: 초기 부하·감염·과열·흡연·당뇨.</li>
				<li><strong>보철 관련</strong> · 나사 풀림(재조임)·세라믹 파절(수리·교체)·시멘트 잔류(염증).</li>
			</ol>
			<div class="md-guide-callout">
				<strong>합병증 감소의 핵심</strong> · ① 경험 많은 전문의 ② CBCT 기반 정밀 수술 계획 ③ 독립 수술실 무균 수술 ④ 수술 후 정기 관리.
			</div>',
		),

		array(
			'id'    => 'insurance',
			'title' => '08 · 건강보험·실비보험·세액공제',
			'body'  => '<h3>국민건강보험 (만 65세 이상)</h3>
			<ul>
				<li>대상 · 만 <strong>65세 이상</strong> 건강보험 가입자</li>
				<li>개수 · 평생 <strong>2개까지</strong></li>
				<li>본인부담률 · 일반 30% / 차상위 20% / 기초수급자 10%</li>
				<li>예상 비용 · 1개당 약 <strong>30~50만원</strong> (본인부담)</li>
				<li>적용 범위 · 1차 수술(식립) + 2차 수술(지대주) + 보철(크라운)</li>
				<li>비적용 · 뼈이식·상악동거상술·발치·CT</li>
			</ul>
			<h3>실비보험</h3>
			<p>임플란트 자체는 대부분 비급여로 미보장. 수술 과정 중 일부 급여 항목(진단·발치·잇몸 치료)은 청구 가능한 경우가 있습니다. 약관 확인 필수.</p>
			<h3>연말정산 세액공제</h3>
			<p>임플란트 비용은 <strong>의료비 세액공제 대상</strong>입니다. 총 급여의 3%를 초과하는 의료비에 대해 15% 세액공제 (일반 근로자 기준).</p>',
		),

		array(
			'id'    => 'compare',
			'title' => '09 · 임플란트 vs 브릿지 vs 틀니',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>임플란트</th><th>브릿지</th><th>틀니</th></tr></thead>
				<tbody>
					<tr><td>비용 (1개)</td><td>80~250만</td><td>60~150만</td><td>30~100만</td></tr>
					<tr><td>수명</td><td>15~30년+</td><td>7~15년</td><td>5~10년</td></tr>
					<tr><td>저작력</td><td>80~90%</td><td>60~70%</td><td>20~40%</td></tr>
					<tr><td>인접 치아 영향</td><td>없음</td><td>양쪽 삭제 필요</td><td>없음~약간</td></tr>
					<tr><td>골 흡수 방지</td><td>O (유일)</td><td>X</td><td>X</td></tr>
					<tr><td>심미성</td><td>★★★★★</td><td>★★★★</td><td>★★~★★★</td></tr>
					<tr><td>치료 기간</td><td>3~6개월</td><td>2~3주</td><td>2~4주</td></tr>
					<tr><td>수술 필요</td><td>O</td><td>X</td><td>X</td></tr>
				</tbody>
			</table></div>
			<h3>선택 안내</h3>
			<ul>
				<li><strong>임플란트</strong> · 인접 치아 건강, 장기 경제성, 자연스러운 저작감 원할 때</li>
				<li><strong>브릿지</strong> · 수술 회피, 인접 치아 이미 크라운 있을 때, 빠른 치료</li>
				<li><strong>틀니</strong> · 비용 최우선, 다수 치아 대체, 전신 건강 문제로 수술 어려울 때</li>
			</ul>',
		),

		array(
			'id'    => 'special',
			'title' => '10 · 특수 케이스별 안내',
			'body'  => '<h3>당뇨 환자</h3>
			<ul>
				<li>혈당 조절 필수 · <strong>HbA1c 7% 이하</strong>가 안전</li>
				<li>8% 이상이면 감염 위험 증가, 골유착 1~2개월 지연</li>
				<li>수술 전후 항생제 관리 중요</li>
			</ul>
			<h3>골다공증 환자</h3>
			<ul>
				<li>경구 비스포스포네이트 → 대부분 안전 (복용 4년 미만)</li>
				<li>정맥주사 비스포스포네이트 → <strong>턱뼈 괴사(MRONJ) 위험</strong>, 반드시 주치의 상담</li>
			</ul>
			<h3>고혈압 환자</h3>
			<ul>
				<li>안정적 조절 시 문제 없음</li>
				<li>수술 당일 180/110mmHg 이상이면 연기</li>
				<li>항응고제(와파린·아스피린) 복용 시 주치의와 상의</li>
			</ul>
			<h3>흡연자</h3>
			<ul>
				<li>실패율 <strong>2~3배 증가</strong> (가장 큰 위험 요인)</li>
				<li>최소 수술 전 2주 ~ 후 8주 금연</li>
				<li>장기적 금연이 임플란트 수명에 결정적 영향</li>
			</ul>
			<h3>뼈 부족 환자</h3>
			<ul>
				<li>자가골 이식 · 최고 생체적합성</li>
				<li>동종골/이종골 이식 · 가장 보편적</li>
				<li>합성골 이식 · 감염 위험 최소</li>
				<li>GBR (골유도재생술) · 콜라겐막 사용</li>
				<li>상악동거상술 · 위턱 어금니 부위</li>
			</ul>',
		),

		array(
			'id'    => 'graft',
			'title' => '11 · 골이식·상악동거상술 완전 정리',
			'body'  => '<h3>골이식 재료 비교</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>종류</th><th>출처</th><th>장점</th><th>단점</th><th>비용</th></tr></thead>
				<tbody>
					<tr><td>자가골</td><td>본인 뼈</td><td>최고 생체적합성</td><td>2차 수술 필요</td><td>30~80만</td></tr>
					<tr><td>동종골</td><td>사람 시신</td><td>충분한 양</td><td>드물게 면역반응</td><td>20~50만</td></tr>
					<tr><td>이종골</td><td>소·말 뼈</td><td>널리 사용</td><td>흡수 느림</td><td>20~50만</td></tr>
					<tr><td>합성골</td><td>인공 합성</td><td>감염 위험 0</td><td>골유도성 낮음</td><td>15~40만</td></tr>
				</tbody>
			</table></div>
			<h3>골이식 방법</h3>
			<ul>
				<li><strong>동시 식립</strong> · 골 부족 1~3mm, 임플란트와 동시 시행</li>
				<li><strong>지연 식립</strong> · 골 부족 3mm+, 4~6개월 후 임플란트</li>
				<li><strong>GBR (골유도재생술)</strong> · 콜라겐막으로 골 형성 유도</li>
				<li><strong>블록 골이식</strong> · 큰 결손용, 자가골 블록 이식</li>
			</ul>
			<h3>상악동거상술 (Sinus Lift)</h3>
			<ul>
				<li><strong>측방 접근</strong> · 잔존 골 5mm 이하, 치아 옆 절개, 1~2주 회복, 50~150만</li>
				<li><strong>상방 접근</strong> · 잔존 골 5~8mm, 임플란트 위치에서, 동시 임플란트 가능, 30~80만</li>
				<li>성공률 · 95~98%</li>
			</ul>
			<p><strong>Lang et al. (2012)</strong>: 골이식 임플란트 5년 생존율 96.2% · 일반 임플란트 96.7% — 유의미한 차이 없음.</p>',
		),

		array(
			'id'    => 'digital',
			'title' => '12 · 디지털·네비게이션 임플란트',
			'body'  => '<h3>전통 vs 디지털 워크플로우</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>단계</th><th>전통</th><th>디지털</th></tr></thead>
				<tbody>
					<tr><td>진단</td><td>파노라마 + X-ray</td><td>CBCT 3D</td></tr>
					<tr><td>계획</td><td>머릿속/평면</td><td>3D 소프트웨어</td></tr>
					<tr><td>인상</td><td>인상재 (고무)</td><td>구강 스캐너</td></tr>
					<tr><td>가이드</td><td>없음</td><td>3D 프린팅</td></tr>
					<tr><td>오차</td><td>1~3mm</td><td>0.3~0.5mm</td></tr>
					<tr><td>보철</td><td>1~3주</td><td>당일~3일</td></tr>
				</tbody>
			</table></div>
			<h3>네비게이션 정밀도</h3>
			<ul>
				<li><strong>Freehand (전통)</strong> · 1.5~2.0mm, 각도 5~10°</li>
				<li><strong>정적 가이드</strong> · 0.7~1.0mm, 각도 2~4°</li>
				<li><strong>네비게이션 (Dynamic)</strong> · 0.3~0.5mm, 각도 1~2°</li>
			</ul>
			<h3>당일 임플란트</h3>
			<p>진단 → 수술 → 임시 보철 하루 완료. 조건: 골질 양호·단일 치아·초기 고정력 <strong>35Ncm 이상</strong>.</p>
			<h3>AI 진단의 미래</h3>
			<ul>
				<li>AI 영상 분석 (신경관·혈관·골밀도 자동 측정)</li>
				<li>AI 위치 추천 (최적 위치·각도·크기)</li>
				<li>AI 시뮬레이션 (5·10년 후 골 변화 예측)</li>
				<li>AI 보철 디자인 (자연스러운 형태 자동 설계)</li>
			</ul>',
		),

		array(
			'id'    => 'evidence',
			'title' => '13 · 임상 데이터·학술 근거',
			'body'  => '<h3>기간별 생존율·성공률</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>기간</th><th>생존율</th><th>성공률</th><th>출처</th></tr></thead>
				<tbody>
					<tr><td>5년</td><td>95~98%</td><td>92~96%</td><td>Pjetursson (2007)</td></tr>
					<tr><td>10년</td><td>92~95%</td><td>85~90%</td><td>Moraschini (2015)</td></tr>
					<tr><td>15년</td><td>89~93%</td><td>80~85%</td><td>Buser (2012)</td></tr>
					<tr><td>20년</td><td>85~90%</td><td>75~82%</td><td>Astrand (2008)</td></tr>
					<tr><td>30년+</td><td>80~87%</td><td>70~78%</td><td>Brånemark 첫 환자</td></tr>
				</tbody>
			</table></div>
			<h3>위험도 분석</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>위험 요인</th><th>실패 배수</th><th>대응</th></tr></thead>
				<tbody>
					<tr><td>흡연 (1갑/일)</td><td>2.5~3배</td><td>전후 금연</td></tr>
					<tr><td>조절 불량 당뇨</td><td>2~3배</td><td>혈당 안정화</td></tr>
					<tr><td>정맥주사 골다공증 약</td><td>10배+</td><td>주치의와 휴약 협의</td></tr>
					<tr><td>두경부 방사선 치료</td><td>3~5배</td><td>6개월 후 평가</td></tr>
					<tr><td>심한 이갈이</td><td>2~3배</td><td>야간 마우스가드</td></tr>
					<tr><td>치주염 이력</td><td>2~3배</td><td>치주 치료 선행</td></tr>
					<tr><td>구강 위생 불량</td><td>2~4배</td><td>위생 교육 + 정기 검진</td></tr>
				</tbody>
			</table></div>
			<h3>주요 학술 결론</h3>
			<ul>
				<li><strong>Cochrane Review (2014)</strong> · 즉시 vs 일반 임플란트 1년 생존율 차이 무의미</li>
				<li><strong>Esposito et al. (2007)</strong> · 초기 고정력 35Ncm+ 확보 시 즉시 부하 안전</li>
				<li><strong>Albrektsson & Donos (2012)</strong> · 성공 기준 = 무통·무감염, 매년 골 흡수 0.2mm 이하</li>
				<li><strong>Heitz-Mayfield (2008)</strong> · 정기 유지관리 환자 주위염 4.1% vs 미시행 39.5% — 약 <strong>10배 차이</strong></li>
			</ul>',
		),

		array(
			'id'    => 'clinic',
			'title' => '14 · 좋은 임플란트 치과 고르는 법',
			'body'  => '<h3>반드시 확인할 6가지</h3>
			<ol>
				<li><strong>전문의 여부</strong> · 구강악안면외과·치주과·보철과 등</li>
				<li><strong>CBCT 보유</strong> · 3D 진단 필수</li>
				<li><strong>독립 수술실</strong> · 감염 관리 핵심</li>
				<li><strong>사용 브랜드</strong> · 식약처 허가 여부 확인</li>
				<li><strong>보증 기간</strong> · 서면 확인 (최소 3~5년)</li>
				<li><strong>투명한 비용 안내</strong> · 모든 추가 비용 사전 공개</li>
			</ol>
			<h3>추가 고려사항</h3>
			<ul>
				<li>첨단 장비 (네비게이션·디지털 가이드·구강 스캐너)</li>
				<li>원내 기공소 (제작 정밀도·긴급 대응)</li>
				<li>수면 임플란트 가능 여부</li>
				<li>실제 리뷰 (최근 1년)</li>
				<li>다학제 협진 시스템 (치주·보철·교정)</li>
				<li>야간·주말 진료 가능성</li>
			</ul>',
		),

		array(
			'id'    => 'moon',
			'title' => '15 · 문치과병원 임플란트센터',
			'body'  => '<h3>🏥 1990년대부터 임플란트를 식립해온 병원</h3>
			<p>한국 치과계에 임플란트가 본격 도입된 <strong>1990년대 초</strong>부터 문치과병원은 임플란트 식립을 시작했습니다. 1995년 개원과 함께 임플란트 진료 시스템을 갖췄고, 이후 <strong>30여년 이어온 임상 경험</strong>은 천안·아산 지역에서 가장 오래된 임플란트 진료 이력 중 하나입니다.</p>
			<h3>왜 오래된 임플란트 경험이 중요한가</h3>
			<ul>
				<li><strong>세대별 브랜드 특성 파악</strong> · 초기 브레네막·노벨·아스트라부터 오스템·SLActive 까지 실제 사용 경험</li>
				<li><strong>10·20·30년 장기 예후 데이터</strong> · 초기 환자분들의 실제 임플란트 상태 추적 관찰</li>
				<li><strong>합병증 대처 노하우</strong> · 주위염·나사 풀림·크라운 파절 등 장기 관리 실무 경험</li>
				<li><strong>재수술·재보철 케이스</strong> · 노후 임플란트 교체·업그레이드 진료 경험</li>
				<li><strong>고령 환자 대응</strong> · 개원 초기 40~50대였던 환자분들의 70·80대 진료 이어감</li>
			</ul>
			<h3>공신력 있는 인증·협력</h3>
			<p>국가지정 구강검진 치과 · 외국인환자 유치 의료기관 · 미군 및 가족 치료기관 · 삼성서울병원 협력병원 · 천안시 치아사랑사업 협력병원 · 대한적십자사 협력병원.</p>
			<h3>진료 강점</h3>
			<ul>
				<li><strong>독립 임플란트 진료센터</strong> · 감염 관리·집중 진료</li>
				<li><strong>CBCT 3D 정밀 진단</strong> · 신경·상악동 안전 거리 계산</li>
				<li><strong>디지털 구강 스캐너</strong> · 정밀 인상·빠른 보철 제작</li>
				<li><strong>수면 임플란트</strong> · 공포·구역 반사 심한 분 대응</li>
				<li><strong>다학제 협진</strong> · 치주·보철·교정·구강외과 통합 판단</li>
				<li><strong>1:1 충분한 사전 상담</strong> · 케이스·비용·계획 투명 안내</li>
			</ul>
			<h3>진료 예약</h3>
			<p>진료 상담을 원하시면 <a href="/상담예약/">📅 상담 예약</a> 또는 <a href="tel:0415612275">전화 041-561-2275</a>로 연락 주세요. 자세한 진료 내용은 <a href="/임플란트/">임플란트 진료 페이지</a>를 참고하실 수 있습니다.</p>',
		),

		array(
			'id'    => 'faq',
			'title' => '16 · 자주 묻는 질문 30선',
			'body'  => '<div class="md-guide-faq" itemscope itemtype="https://schema.org/FAQPage">' .
				md_guide_faq_html( array(
					array( '임플란트 수술은 아프나요?', '국소 마취 하에 진행되어 <strong>수술 중 통증은 거의 없습니다</strong>. 수술 후 24~48시간 부종·통증은 진통제로 조절 가능합니다. 공포감이 크다면 수면 임플란트도 가능합니다.' ),
					array( '수술 후 언제부터 음식을 먹을 수 있나요?', '수술 당일은 <strong>미지근한 유동식</strong>만, 2~3일 후 부드러운 음식, 1~2주 후 일반 식사가 원칙입니다. 뜨거운 음식·빨대 사용·흡연·음주는 최소 3일간 금지.' ),
					array( '몇 살부터 임플란트가 가능한가요?', '뼈 성장이 완료된 <strong>만 18세 이후</strong>부터 가능합니다. 상한 연령은 없으며 80대 이상도 전신 건강만 양호하면 시술 가능합니다.' ),
					array( '치료 기간은 얼마나 걸리나요?', '뼈 조건 양호 시 <strong>3~6개월</strong>. 뼈이식 필요 시 6~10개월. 즉시 임플란트는 발치·식립·임시 치아가 하루에 끝날 수도 있습니다.' ),
					array( '임플란트가 실패하면 어떻게 되나요?', '주로 초기 3개월 내 골유착 실패로 나타납니다. 실패한 픽스처 제거 → 뼈 회복 (2~3개월) → 재식립. 대부분 재식립 성공률 90% 이상.' ),
					array( 'MRI 촬영 시 문제가 없나요?', '티타늄은 비자성 금속으로 <strong>MRI 안전</strong>. 3.0T 이하 대부분 문제 없음. 시술 병원에서 발급받은 안내서를 지참하세요.' ),
					array( '공항 금속탐지기에 걸리나요?', '반응하지 않습니다. 티타늄은 감지되지 않으며, 반응해도 시술 안내서로 설명 가능합니다.' ),
					array( '앞니와 어금니 임플란트의 차이는?', '앞니는 <strong>심미성</strong>이 최우선 (잇몸 라인·색조). 어금니는 <strong>저작력·강도</strong>가 최우선. 각각 다른 브랜드·크라운 재료가 권장됩니다.' ),
					array( '흡연이 왜 그렇게 나쁜가요?', '니코틴이 잇몸 혈류를 30% 이상 감소시켜 골유착을 방해하고 감염 위험을 높입니다. 흡연자 실패율은 비흡연자의 2~3배입니다.' ),
					array( '이갈이가 있어도 임플란트 가능한가요?', '가능합니다. 반드시 <strong>야간 마우스가드</strong>를 착용해야 하며, 그렇지 않으면 임플란트 파절·주위염 위험이 2~3배 증가합니다.' ),
					array( '수술 당일 운전해도 되나요?', '국소 마취만 받은 경우 가능. 수면 마취는 당일 운전 금지, 보호자 동반 필수.' ),
					array( '임시 치아를 사용하나요?', '앞니는 대부분 임시 치아 제작. 어금니는 케이스별 결정. 즉시 부하 조건 충족 시 수술 당일 임시 치아 장착 가능.' ),
					array( '한 번에 여러 개를 심을 수 있나요?', '가능합니다. All-on-4/6 방식으로 전악도 하루에 4~6개 식립 가능. 다만 골질·전신 건강에 따라 판단합니다.' ),
					array( '자연 치아처럼 느껴지나요?', '저작감의 80~90%가 자연 치아와 유사합니다. 다만 임플란트에는 치주인대가 없어 <strong>미세한 압력 감각은 없습니다</strong>.' ),
					array( '위턱과 아래턱의 차이는?', '아래턱은 골밀도 높아 <strong>2~3개월</strong> 골유착. 위턱은 골밀도 낮고 상악동 근접해 <strong>4~6개월</strong> + 상악동거상술 필요 확률 높음.' ),
					array( '교정 치료와 병행 가능한가요?', '가능하나 <strong>교정 완료 후 임플란트</strong>가 원칙. 임플란트는 이동 불가하므로 교정으로 공간·위치를 먼저 정리해야 합니다.' ),
					array( '수술 후 운동은 언제부터?', '3일간 격렬한 운동·사우나·음주 금지. 1주 후 가벼운 운동, 2주 후 일반 운동 가능.' ),
					array( '임플란트 주변이 검게 변하는 이유?', '잇몸이 얇거나 티타늄 지대주가 비칠 때 발생. <strong>지르코니아 지대주</strong>로 재제작하거나 잇몸이식으로 개선 가능.' ),
					array( '임플란트에 충치가 생기나요?', '티타늄·세라믹은 충치가 생기지 않습니다. 다만 <strong>임플란트 주위염(잇몸 염증)</strong>은 발생 가능하며, 방치 시 뼈 흡수로 이어질 수 있습니다.' ),
					array( '음식이 잘 끼나요?', '초기 적응 기간 동안은 살짝 낄 수 있습니다. 3~6개월 뒤 잇몸이 자리 잡으면 완화됩니다. 치실·치간칫솔 사용 권장.' ),
					array( '복용 약을 끊어야 하나요?', '항응고제(와파린·아스피린)·비스포스포네이트·면역억제제는 <strong>수술 전 반드시 알려야</strong> 합니다. 임의 중단 금지, 주치의와 협의.' ),
					array( '수술 후 술을 마셔도 되나요?', '최소 <strong>3일간 금주</strong>. 알코올은 항생제·진통제와 상호작용, 출혈 위험 증가. 첫 1주는 완전 금주 권장.' ),
					array( '임플란트 재료는 안전한가요?', '티타늄은 30년 이상 임상 검증된 <strong>생체 안전 금속</strong>. 알레르기는 0.1% 미만. 심박동기·인공관절 등에도 동일 재료 사용.' ),
					array( '수명이 다하면 어떻게 하나요?', '픽스처는 반영구적. 크라운만 10~20년마다 교체 가능 (30~50만원). 픽스처 이상 시 재수술 필요 (드묾).' ),
					array( '수술 공포증이 있어요.', '<strong>수면 임플란트</strong> 또는 웃음가스 진정으로 편안하게 진행 가능. 상담 시 미리 말씀해주세요.' ),
					array( '주변 다른 치아가 아플 수 있나요?', '초기 잇몸 부기로 인접 치아가 시릴 수 있으나 1~2주 내 사라집니다. 지속되면 재검진 필요.' ),
					array( '온라인의 "무삭제·무절개 임플란트"는?', '무절개는 골질 좋은 소수 케이스만 가능. 모든 케이스에 무절개를 강요하면 위험. 정밀 CBCT 진단 후 판단.' ),
					array( '지르코니아 임플란트란?', '금속 알레르기·심미 목적으로 개발. 아직 임상 데이터가 티타늄 대비 부족(10년+). 대부분 티타늄이 표준.' ),
					array( '비용을 아끼는 방법은?', '① 65세+ 건강보험 활용 ② 정품 국산 브랜드 선택 ③ 카드 무이자 할부 ④ 연말정산 세액공제 ⑤ 정기 검진으로 재수술 방지.' ),
					array( '천안·아산에서 임플란트 잘하는 치과는?', '30여년 경력·CBCT·독립 수술실·투명 비용 안내·다학제 협진 여부를 기준으로 선택하세요. 문치과병원은 이 모든 기준을 갖춘 천안·아산 대표 치과병원입니다.' ),
				) )
				. '</div>',
		),

	),
);
