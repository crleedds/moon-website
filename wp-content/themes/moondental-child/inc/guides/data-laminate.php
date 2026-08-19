<?php
/**
 * Guide Data — 천안 라미네이트 종합 안내서
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

return array(
	'slug'         => 'laminate',
	'code'         => 'GUIDE 03',
	'icon'         => '✨',
	'eyebrow'      => '천안·아산 대표 치과병원 · 30여년 임상',
	'title'        => '스마일디자인센터 라미네이트 종합안내서',
	'subtitle'     => 'emax·empress·지르코니아 비교부터 무삭제 라미네이트의 진실, 디지털 정밀 라미네이트까지 · 문치과병원 스마일디자인센터',
	'reading'      => '약 20분',
	'updated'      => '2026.08',
	'tags'         => array( '30+ FAQ', '16 섹션', '디지털 정밀' ),
	'summary'      => 'emax·empress·지르코니아·하이브리드·디지털 정밀 5가지 종류의 강도·수명·비용을 한눈에. 무삭제 라미네이트의 진짜 의미도 정리했습니다.',
	'cta_page'     => '/라미네이트/',
	'cta_label'    => '문치과병원 라미네이트 진료 페이지',
	'related'      => array(
		array( 'label' => '임플란트센터 종합안내서',    'href' => '/guide/implant/',   'icon' => '🦷' ),
		array( 'label' => '교정센터 투명교정 종합안내서', 'href' => '/guide/suresmile/', 'icon' => '😁' ),
	),
	'toc' => array(
		array( 'id' => 'what',       'label' => '라미네이트란' ),
		array( 'id' => 'history',    'label' => '역사와 진화' ),
		array( 'id' => 'types',      'label' => '5가지 종류 비교' ),
		array( 'id' => 'material',   'label' => '상황별 재료 추천' ),
		array( 'id' => 'cost',       'label' => '비용 총정리 (2026)' ),
		array( 'id' => 'indication', 'label' => '적응증과 비적응증' ),
		array( 'id' => 'process',    'label' => '시술 과정 7단계' ),
		array( 'id' => 'nocut',      'label' => '무삭제 라미네이트의 진실' ),
		array( 'id' => 'risks',      'label' => '부작용과 합병증' ),
		array( 'id' => 'lifespan',   'label' => '수명과 장기 관리' ),
		array( 'id' => 'vs',         'label' => '라미네이트 vs 미백 vs 교정' ),
		array( 'id' => 'vs-crown',   'label' => '라미네이트 vs 크라운' ),
		array( 'id' => 'digital',    'label' => '디지털 정밀 라미네이트' ),
		array( 'id' => 'age',        'label' => '연령별·직업별 가이드' ),
		array( 'id' => 'daily',      'label' => '일상 관리·주의사항' ),
		array( 'id' => 'clinic',     'label' => '좋은 치과 고르는 법' ),
		array( 'id' => 'moon',       'label' => '문치과병원 스마일디자인센터' ),
		array( 'id' => 'faq',        'label' => '자주 묻는 질문 30선' ),
	),
	'sections' => array(

		array(
			'id'    => 'what',
			'title' => '01 · 라미네이트란 무엇인가',
			'body'  => '<p><strong>라미네이트</strong>는 치아의 앞면을 얇게 삭제한 후 그 위에 얇은 도재(세라믹) 판을 접착하는 <strong>심미 치과 시술</strong>입니다. 두께는 <strong>0.3~0.7mm</strong> (손톱보다 얇음). 디지털 정밀 라미네이트는 0.2~0.4mm까지 얇아졌습니다.</p>
			<h3>해결 가능한 문제</h3>
			<ul>
				<li>깊은 변색 (테트라사이클린·신경치료 후)</li>
				<li>치아 모양 변형 (작거나 깨진 치아)</li>
				<li>치아 사이 틈 (1~3mm 정중이개)</li>
				<li>약간의 비뚤어짐 (1~2mm)</li>
				<li>마모된 치아 (길이 회복)</li>
				<li>변색된 기존 보철물 교체</li>
			</ul>',
		),

		array(
			'id'    => 'history',
			'title' => '02 · 라미네이트의 5세대 진화',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>세대</th><th>연도</th><th>주요 혁신</th><th>임상 의미</th></tr></thead>
				<tbody>
					<tr><td>1세대</td><td>1928~1950</td><td>임시 접착 도재 베니어</td><td>임시 사용만 가능</td></tr>
					<tr><td>2세대</td><td>1950~1980</td><td>에나멜 산부식 + 레진 접착</td><td>영구 접착 가능</td></tr>
					<tr><td>3세대</td><td>1980~2000</td><td>경질 도재·IPS Empress</td><td>강도 향상</td></tr>
					<tr><td>4세대</td><td>2000~2015</td><td>emax·CAD/CAM</td><td>강도·정밀도 비약</td></tr>
					<tr><td>5세대</td><td>2015~현재</td><td>DSD·AI 색상분석·정밀가공</td><td>예측 가능성 극대화</td></tr>
				</tbody>
			</table></div>',
		),

		array(
			'id'    => 'types',
			'title' => '03 · 라미네이트 5가지 종류 완벽 비교',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>종류</th><th>강도</th><th>두께</th><th>심미</th><th>수명</th><th>가격/개</th></tr></thead>
				<tbody>
					<tr><td>emax (리튬 디실리케이트)</td><td>400 MPa</td><td>0.3~0.5mm</td><td>★★★★★</td><td>15~20년</td><td>70~100만</td></tr>
					<tr><td>empress (로이사이트 강화)</td><td>160 MPa</td><td>0.5~0.7mm</td><td>★★★★</td><td>10~15년</td><td>60~85만</td></tr>
					<tr><td>지르코니아</td><td>1,000~1,200 MPa</td><td>0.5~0.8mm</td><td>★★★★</td><td>20년+</td><td>50~70만</td></tr>
					<tr><td>하이브리드 (레진+세라믹)</td><td>100~150 MPa</td><td>0.5~1.0mm</td><td>★★★</td><td>7~10년</td><td>35~55만</td></tr>
					<tr><td>디지털 정밀 (DSD+CAD/CAM)</td><td>400~500 MPa</td><td>0.2~0.4mm</td><td>★★★★★+</td><td>15~20년</td><td>80~150만</td></tr>
				</tbody>
			</table></div>
			<div class="md-guide-callout">
				<strong>💡 어떤 재료가 나에게 맞을까?</strong><br>
				앞니 심미 최우선 → <strong>emax · 디지털 정밀</strong> · 어금니·이갈이 → <strong>지르코니아</strong> · 예산 우선 → <strong>empress · 하이브리드</strong>. 최종 결정은 진료 시 얼굴형·직업·저작 습관을 종합해 결정합니다.
			</div>',
		),

		array(
			'id'    => 'material',
			'title' => '04 · 상황별 재료 추천',
			'body'  => '<h3>상황별 최적 선택</h3>
			<ul>
				<li><strong>방송·연예·고화질 카메라 노출</strong> · 디지털 정밀 라미네이트 (다층 광학 구조로 자연스러움 최대)</li>
				<li><strong>영업·서비스직 첫인상 중요</strong> · emax + DSD</li>
				<li><strong>테트라사이클린·심한 변색</strong> · emax (차폐력 좋음)</li>
				<li><strong>이갈이 병력</strong> · 지르코니아 + 야간 마우스가드</li>
				<li><strong>예산 우선·경미한 변색</strong> · empress 또는 하이브리드</li>
				<li><strong>운동선수·외상 위험</strong> · 지르코니아 + 스포츠 마우스가드</li>
				<li><strong>결혼식 준비 (2~3개월)</strong> · CAD/CAM 원내 제작 · 당일~3일</li>
			</ul>',
		),

		array(
			'id'    => 'cost',
			'title' => '05 · 라미네이트 비용 총정리 (2026)',
			'body'  => '<h3>개수별 전국 평균</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>개수</th><th>emax</th><th>empress</th><th>지르코니아</th><th>하이브리드</th><th>디지털 정밀</th></tr></thead>
				<tbody>
					<tr><td>1개</td><td>70~100</td><td>60~85</td><td>50~70</td><td>35~55</td><td>80~150</td></tr>
					<tr><td>6개 (앞니)</td><td>420~600</td><td>360~510</td><td>300~420</td><td>210~330</td><td>480~900</td></tr>
					<tr><td>8개 (전치부)</td><td>560~800</td><td>480~680</td><td>400~560</td><td>280~440</td><td>640~1,200</td></tr>
					<tr><td>10개</td><td>700~1,000</td><td>600~850</td><td>500~700</td><td>350~550</td><td>800~1,500</td></tr>
				</tbody>
			</table></div>
			<p class="md-guide-note">(단위: 만원)</p>
			<h3>비용 구성 요소</h3>
			<ul>
				<li>도재 재료비 · 1개당 5~15만</li>
				<li>의료진 진료비 · 1개당 20~40만</li>
				<li>기공료 · 1개당 10~25만</li>
				<li>디지털 스캔/DSD · 10~30만</li>
				<li>임시 라미네이트 · 1개당 3~5만</li>
				<li>야간 마우스가드 · 10~20만</li>
			</ul>
			<h3>비용 절감 팁</h3>
			<ul>
				<li>꼭 필요한 개수만 시술 (모든 치아 X)</li>
				<li>패키지 할인 (6개 이상 5~10%)</li>
				<li>카드 무이자 할부 (12개월)</li>
				<li>외상 후 실비 보험 확인</li>
				<li>연말정산 의료비 세액공제</li>
			</ul>
			<div class="md-guide-callout md-guide-callout--warn">
				<strong>⚠️ 30만원 라미네이트의 함정</strong><br>
				① 정품 emax가 아닌 일반 도재 ② 디지털 스캔 미사용 ③ 시술 시간 30분 미만 ④ 보증 1년 미만 ⑤ 재시술 별도 청구 → 5년 내 재시술 포함 시 정상가보다 비쌈.
			</div>',
		),

		array(
			'id'    => 'indication',
			'title' => '06 · 적응증과 비적응증',
			'body'  => '<h3>좋은 적응증</h3>
			<ul>
				<li>변색된 앞니 (미백 불가)</li>
				<li>치아 사이 작은 틈 (1~3mm)</li>
				<li>모양·크기 변형</li>
				<li>약간의 비뚤어짐 (1~2mm)</li>
				<li>마모된 치아</li>
				<li>변색된 기존 보철물</li>
				<li>외상으로 가장자리 결손</li>
				<li>구강 위생 양호·현실적 기대</li>
			</ul>
			<h3>상대적 비적응증</h3>
			<ul>
				<li>심한 이갈이 (마우스가드 조건부 가능)</li>
				<li>잇몸 질환 진행 중 (선행 치료 필수)</li>
				<li>심한 부정교합 (교정 우선)</li>
				<li>큰 충치·결손 (크라운 더 적합)</li>
				<li>역교합 (파절 위험)</li>
			</ul>
			<h3>절대 비적응증</h3>
			<ul>
				<li>심한 치주염 (골 흡수 50% 이상)</li>
				<li>방치된 다발성 충치</li>
				<li>흔들리는 치아</li>
				<li>구강 위생 관리 불가</li>
				<li>아동·청소년 (성장기) — <strong>만 20세 이상</strong> 권장</li>
			</ul>',
		),

		array(
			'id'    => 'process',
			'title' => '07 · 시술 과정 7단계',
			'body'  => '<ol class="md-guide-steps">
				<li><strong>초기 상담·진단</strong> · 사진 촬영(정면·측면·미소), X-ray, 구강 스캔. 적응증 판단·재료 추천·비용 안내.</li>
				<li><strong>디지털 미소 디자인(DSD)</strong> · 얼굴형·입꼬리·잇몸 라인 분석. 3D 시뮬레이션으로 최종 결과 미리 확인. 환자 수정 요청 가능.</li>
				<li><strong>색상 결정 및 사진</strong> · 분광 분석(VITA Easyshade) + AI 색상 매칭. 자연광·인공광 환경 비교. 인접 치아·잇몸·피부톤 고려.</li>
				<li><strong>치아 삭제 (Tooth Preparation)</strong> · 0.3~0.7mm 삭제 (디지털은 0.2~0.4mm). 깊이 가이드 버 사용. 잇몸 자극 최소화.</li>
				<li><strong>인상 채득·임시 라미네이트</strong> · 아날로그(인상재) 또는 디지털(iTero, 3Shape). 1~2주간 임시 부착.</li>
				<li><strong>도재 가공·제작</strong> · 일반은 기공사 수작업(1~2주), 디지털은 CAD/CAM 밀링(당일~3일). 분광 색상 분석으로 매칭 표준화.</li>
				<li><strong>최종 접착·마무리</strong> · 임시 시멘트로 가접착 → 색·모양·교합 확인 → 영구 접착(에나멜 산부식 + 레진 시멘트) → 광중합·다듬음.</li>
			</ol>',
		),

		array(
			'id'    => 'nocut',
			'title' => '08 · 무삭제 라미네이트의 진실',
			'body'  => '<h3>"무삭제"의 정확한 의미</h3>
			<ul>
				<li><strong>완전 무삭제</strong> · 전혀 깎지 않음 (가능한 케이스 5% 미만)</li>
				<li><strong>최소 삭제</strong> · 0.1~0.3mm 정도 (가장 현실적)</li>
				<li><strong>일반 삭제</strong> · 0.3~0.7mm (대부분의 경우)</li>
			</ul>
			<h3>완전 무삭제 가능 케이스</h3>
			<ol>
				<li>치아가 안쪽으로 들어가 있음</li>
				<li>치아 사이에 큰 틈</li>
				<li>치아가 작거나 마모되어 작음</li>
				<li>변색만 가리는 목적</li>
			</ol>
			<div class="md-guide-callout md-guide-callout--warn">
				<strong>⚠️ 무리한 무삭제의 위험</strong><br>
				치아가 두꺼워 보임("말 같은 이") · 잇몸 자극·염증 · 발음 변화 · 구강 위생 어려움 · 탈락 위험 증가. "100% 무삭제" 광고를 무조건 신뢰하지 마세요.
			</div>',
		),

		array(
			'id'    => 'risks',
			'title' => '09 · 부작용과 합병증',
			'body'  => '<h3>흔한 부작용 (일시적)</h3>
			<ul>
				<li><strong>시린이</strong> · 30~50% 발생, 1~2주~1~3개월. 최소 삭제 시스템은 10~20%.</li>
				<li><strong>임시 라미네이트 탈락</strong> · 10~20%, 끈적한 음식·강한 저작. 탈락 시 보관 후 즉시 방문.</li>
				<li><strong>잇몸 자극·염증</strong> · 20~30%, 1~2주. 소금물 가글·부드러운 칫솔.</li>
			</ul>
			<h3>중요한 부작용 (관리·예방 필수)</h3>
			<ol>
				<li><strong>라미네이트 탈락</strong> · 5년 내 5~10%. 원인: 접착 불량·강한 외력·이갈이·위생 불량.</li>
				<li><strong>파절·균열</strong> · 5년 내 3~7%. 예방: 야간 마우스가드·단단한 음식 자제.</li>
				<li><strong>변색</strong> · 접착면 누수(가장 흔함)·주변 자연 치아 변색·흡연.</li>
				<li><strong>잇몸 퇴축</strong> · 10년 내 15~20%. 노화·잇몸 질환·부적합·강한 양치.</li>
				<li><strong>접착면 충치</strong> · 10년 내 10%. 예방: 식후 양치·치실·불소 가글.</li>
				<li><strong>신경 손상</strong> · 1% 미만. 1개월 이상 시린이·변색 시 신경치료 + 재제작.</li>
			</ol>',
		),

		array(
			'id'    => 'lifespan',
			'title' => '10 · 수명과 장기 관리',
			'body'  => '<h3>종류별 생존율·평균 수명</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>종류</th><th>5년 생존</th><th>10년 생존</th><th>평균 수명</th></tr></thead>
				<tbody>
					<tr><td>emax</td><td>95%</td><td>85%</td><td>15~20년</td></tr>
					<tr><td>empress</td><td>92%</td><td>78%</td><td>10~15년</td></tr>
					<tr><td>지르코니아</td><td>97%</td><td>90%</td><td>20년+</td></tr>
					<tr><td>하이브리드</td><td>85%</td><td>60%</td><td>7~10년</td></tr>
					<tr><td>디지털 정밀</td><td>95%</td><td>85%</td><td>15~20년</td></tr>
				</tbody>
			</table></div>
			<h3>수명 결정 7가지 요인</h3>
			<ol>
				<li>의료진 시술 정밀도 (약 30% 영향)</li>
				<li>소재 품질 (정품 vs 모방품 30~50% 차이)</li>
				<li>접착 과정의 정밀도</li>
				<li>이갈이 여부 (있으면 30~50% 단축)</li>
				<li>식습관 (단단·산성 음식)</li>
				<li>구강 위생 관리 (양호 시 20~30% 증가)</li>
				<li>정기 검진 (6개월~1년)</li>
			</ol>
			<h3>장기 관리 체크리스트</h3>
			<ul>
				<li>☑ 매 식사 후 양치 (특히 색진 음식 후)</li>
				<li>☑ 매일 치실 사용 (가장자리 위주)</li>
				<li>☑ 부드러운 칫솔 + 비연마성 치약</li>
				<li>☑ 야간 마우스가드 (이갈이 시)</li>
				<li>☑ 6개월마다 정기 검진 + 1년마다 스케일링</li>
				<li>☑ 단단한 음식 자제 + 흡연 중단</li>
				<li>☑ 격렬한 스포츠 시 마우스가드</li>
			</ul>',
		),

		array(
			'id'    => 'vs',
			'title' => '11 · 라미네이트 vs 미백 vs 교정',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>미백</th><th>인비절라인 교정</th><th>라미네이트</th></tr></thead>
				<tbody>
					<tr><td>해결 범위</td><td>치아 색만</td><td>치아 위치만</td><td>색+모양+크기+위치</td></tr>
					<tr><td>치아 삭제</td><td>없음</td><td>없음 (IPR 0.1~0.3mm)</td><td>0.2~0.7mm (영구)</td></tr>
					<tr><td>비용</td><td>30~50만</td><td>400~900만</td><td>300~800만</td></tr>
					<tr><td>기간</td><td>1~4주</td><td>6~24개월</td><td>2~3주</td></tr>
					<tr><td>가역성</td><td>완전 가역</td><td>가역</td><td>비가역</td></tr>
					<tr><td>수명·유지</td><td>1~3년 후 재시술</td><td>리테이너 평생</td><td>10~20년</td></tr>
				</tbody>
			</table></div>
			<h3>케이스별 추천</h3>
			<ul>
				<li>"색만 어두움" → <strong>미백</strong></li>
				<li>"약간 비뚤어짐" → <strong>인비절라인 Express</strong></li>
				<li>"색+모양+크기+비뚤어짐" → <strong>라미네이트</strong></li>
				<li>"심한 부정교합 + 변색" → 교정 우선, 후 미백/라미네이트</li>
				<li>"결혼식 6개월 후" → 라미네이트 (빠른 완성)</li>
				<li>"테트라사이클린 변색" → 라미네이트 (미백 한계)</li>
				<li>"노화로 마모된 앞니" → 라미네이트 (길이 회복)</li>
			</ul>',
		),

		array(
			'id'    => 'vs-crown',
			'title' => '12 · 라미네이트 vs 크라운',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>라미네이트</th><th>크라운</th></tr></thead>
				<tbody>
					<tr><td>덮는 범위</td><td>치아 앞면만</td><td>치아 전체</td></tr>
					<tr><td>치아 삭제량</td><td>0.3~0.7mm</td><td>1.5~2.0mm</td></tr>
					<tr><td>적용 부위</td><td>앞니 위주</td><td>모든 치아</td></tr>
					<tr><td>적응증</td><td>경미한 미관 문제</td><td>큰 충치·신경치료·파절</td></tr>
					<tr><td>심미성</td><td>★★★★★</td><td>★★★★</td></tr>
					<tr><td>강도</td><td>★★★</td><td>★★★★★</td></tr>
					<tr><td>수명</td><td>10~20년</td><td>15~25년</td></tr>
					<tr><td>비용/개</td><td>50~100만</td><td>40~150만</td></tr>
					<tr><td>치아 보존성</td><td>★★★★★</td><td>★★</td></tr>
				</tbody>
			</table></div>
			<p><strong>라미네이트 적합</strong> · 80% 이상 건강한 앞니·심미 우선. <strong>크라운 적합</strong> · 큰 충치·신경치료·파절·어금니·심한 변색.</p>',
		),

		array(
			'id'    => 'digital',
			'title' => '13 · 디지털 정밀 라미네이트',
			'body'  => '<h3>전통 vs 디지털 5가지 본질 차이</h3>
			<ol>
				<li><strong>치아 삭제량 30% 감소</strong> · 전통 0.3~0.7mm → 디지털 0.2~0.4mm. 시린이 발생률 저하.</li>
				<li><strong>디지털 미소 디자인 (DSD)</strong> · 얼굴·입꼬리·잇몸 라인 분석. 3D 시뮬레이션으로 미리 확인.</li>
				<li><strong>분광 색상 분석 + AI 매칭</strong> · VITA Easyshade + AI. 자연광·인공광 환경 반영.</li>
				<li><strong>CAD/CAM 정밀 가공</strong> · 0.05mm 단위 정밀도. 두께 균일성·마진 적합도 향상.</li>
				<li><strong>다층 광학 구조</strong> · 안쪽(진한 색) + 가장자리(투명) 다층. 자연 치아 광학 특성 재현.</li>
			</ol>
			<h3>전통 vs 디지털 비교</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>전통</th><th>디지털 정밀</th></tr></thead>
				<tbody>
					<tr><td>치아 삭제량</td><td>0.3~0.7mm</td><td>0.2~0.4mm</td></tr>
					<tr><td>DSD</td><td>일부만</td><td>기본 포함</td></tr>
					<tr><td>색상 매칭</td><td>쉐이드 가이드 (육안)</td><td>분광 + AI</td></tr>
					<tr><td>제작 방식</td><td>기공사 수작업</td><td>CAD/CAM 밀링</td></tr>
					<tr><td>두께 균일성</td><td>~0.1mm 오차</td><td>~0.05mm 정밀</td></tr>
					<tr><td>광학 구조</td><td>단일 색상</td><td>다층 구조</td></tr>
					<tr><td>시린이 발생률</td><td>30~50%</td><td>10~20%</td></tr>
					<tr><td>가격/개</td><td>50~100만</td><td>80~150만</td></tr>
				</tbody>
			</table></div>',
		),

		array(
			'id'    => 'age',
			'title' => '14 · 연령별·직업별 가이드',
			'body'  => '<h3>연령별</h3>
			<ul>
				<li><strong>20대 (사회 진출)</strong> · 20세 미만 성장 미완료 주의. emax 또는 디지털 정밀 추천.</li>
				<li><strong>30~40대 (커리어 절정)</strong> · 가장 큰 환자 비중. 직업 노출 시 디지털 정밀.</li>
				<li><strong>50대 이상 (노후 관리)</strong> · 잇몸 퇴축·치주 평가 필수. emax 또는 지르코니아 (강도·수명).</li>
			</ul>
			<h3>직업별</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>직업</th><th>추천</th><th>이유</th></tr></thead>
				<tbody>
					<tr><td>방송인·연예인</td><td>디지털 정밀</td><td>고화질 카메라 대응</td></tr>
					<tr><td>영업·서비스직</td><td>emax·디지털 정밀</td><td>첫인상·신뢰감</td></tr>
					<tr><td>의료진·교사</td><td>자연스러운 톤</td><td>전문성·신뢰성</td></tr>
					<tr><td>예술가·디자이너</td><td>개성 표현 가능</td><td>충분한 상담</td></tr>
					<tr><td>운동선수</td><td>지르코니아 + 마우스가드</td><td>외상 위험 대비</td></tr>
				</tbody>
			</table></div>',
		),

		array(
			'id'    => 'daily',
			'title' => '15 · 일상 관리와 주의사항',
			'body'  => '<h3>매일 관리</h3>
			<ol>
				<li>아침 양치 (부드러운 칫솔, 비연마성 치약)</li>
				<li>매 식사 후 양치 (가장자리 충치 예방)</li>
				<li>치실 사용 (라미네이트 가장자리 위주)</li>
				<li>저녁 양치 + 불소 가글</li>
				<li>야간 마우스가드 (이갈이 시)</li>
			</ol>
			<h3>먹어도 되는 것</h3>
			<ul>
				<li>일반 모든 음식 (부드럽고 잘 익힌 것)</li>
				<li>물·차·흰우유</li>
				<li>흰살 생선·익힌 채소·부드러운 빵</li>
			</ul>
			<h3>주의가 필요한 음식</h3>
			<ul>
				<li><strong>단단</strong> · 견과류·얼음·갈비뼈 (파절 위험)</li>
				<li><strong>끈적</strong> · 캐러멜·떡·곶감 (탈락 위험)</li>
				<li><strong>색진 음료</strong> · 커피·홍차·콜라·와인 (가장자리 착색)</li>
				<li><strong>산성</strong> · 레몬·식초·탄산 (접착면 약화)</li>
				<li><strong>색진 음식</strong> · 카레·김치찌개 (양치 후 가글)</li>
			</ul>
			<h3>피해야 할 행동</h3>
			<ul>
				<li>딱딱한 것 깨물기</li>
				<li>이로 끈 풀기·병뚜껑 따기</li>
				<li>강한 마찰 양치 (잇몸 퇴축)</li>
				<li>연마성 치약 (도재 광택 손상)</li>
				<li>흡연 (착색·잇몸 손상)</li>
				<li>이갈이 방치 (마우스가드 미사용)</li>
			</ul>
			<h3>정기 검진</h3>
			<ul>
				<li>6개월마다 정기 검진 + 스케일링</li>
				<li>1년마다 X-ray로 가장자리 상태 확인</li>
				<li>이상 증상 시 즉시 방문</li>
			</ul>
			<h3>구강 위생 도구</h3>
			<ul>
				<li>칫솔 · Soft~Medium, 작은 헤드</li>
				<li>치약 · 비연마성, 불소 함유 (Sensodyne ProEnamel)</li>
				<li>치실 · Glide 종류 (미끄러움)</li>
				<li>가글 · 알코올 무함유, 불소 함유</li>
				<li>전동 칫솔 · 음파식(Sonic) 권장</li>
			</ul>',
		),

		array(
			'id'    => 'clinic',
			'title' => '16 · 좋은 라미네이트 치과 고르는 법',
			'body'  => '<h3>반드시 확인할 8가지</h3>
			<ol>
				<li>경험 많은 의료진 시술</li>
				<li>디지털 스캐너 보유 (iTero·3Shape)</li>
				<li>DSD 제공 (시술 전 미리 확인)</li>
				<li>정품 인증 도재 (emax·empress 인증서)</li>
				<li>원내 기공소·CAD/CAM 보유</li>
				<li>색상 분석 시스템 (VITA Easyshade)</li>
				<li>보증 기간 (최소 3~5년)</li>
				<li>이전 시술 사례 다수 (Before/After)</li>
			</ol>
			<h3>피해야 할 Red Flag</h3>
			<ul>
				<li>비정상적 저가 광고 (30만원 이하)</li>
				<li>시술 전 시뮬레이션 미제공</li>
				<li>치아 삭제량 명확 안내 X</li>
				<li>"무삭제 100% 가능" 과장</li>
				<li>의료진 자주 바뀜</li>
				<li>1차 상담에 즉시 시술 권유</li>
				<li>보증 기간 1년 이하</li>
				<li>의료진 학력·경력 비공개</li>
			</ul>',
		),

		array(
			'id'    => 'moon',
			'title' => '17 · 문치과병원 스마일디자인센터',
			'body'  => '<h3>천안·아산 30여년 심미 진료</h3>
			<p>한아의료재단 <strong>문치과병원</strong>은 통합 진료센터와 다학제 협진 시스템을 갖춘 천안·아산 대표 치과병원입니다. 라미네이트는 <strong>보철과·구강외과 협진</strong>으로 잇몸·교합·심미를 종합 판단합니다.</p>
			<h3>진료 강점</h3>
			<ul>
				<li><strong>디지털 구강 스캐너</strong> · 정밀 인상, 시린이 최소화</li>
				<li><strong>DSD (Digital Smile Design)</strong> · 얼굴형·미소 라인 반영</li>
				<li><strong>정품 인증 도재</strong> · emax·empress·지르코니아 정품</li>
				<li><strong>충분한 사전 상담</strong> · 케이스별 재료·기간·비용 투명 안내</li>
				<li><strong>보증·정기 검진</strong> · 시술 후 장기 유지 관리</li>
			</ul>
			<h3>진료 예약</h3>
			<p>라미네이트 상담을 원하시면 <a href="/상담예약/">📅 상담 예약</a> 또는 <a href="tel:0415612275">전화 041-561-2275</a>로 연락 주세요. 자세한 안내는 <a href="/라미네이트/">라미네이트 진료 페이지</a>에서 확인하실 수 있습니다.</p>',
		),

		array(
			'id'    => 'faq',
			'title' => '18 · 자주 묻는 질문 30선',
			'body'  => '<div class="md-guide-faq" itemscope itemtype="https://schema.org/FAQPage">' .
				md_guide_faq_html( array(
					array( '몇 살부터 라미네이트가 가능한가요?', '만 20세 이상 권장. 잇몸·치아 성장이 완료되어야 합니다.' ),
					array( '라미네이트가 자연 치아처럼 보이나요?', 'emax·디지털 정밀은 95% 이상 자연 치아와 유사. 다층 광학 구조로 자연스러움 극대화.' ),
					array( '전체 시술 시간은 얼마나 걸리나요?', '전체 2~3주, 2~3회 방문. 1차 상담·삭제(2~3시간) → 2차 색상·접착 확인 → 3차 최종 접착(1~2시간).' ),
					array( '몇 개 정도 하는 게 좋나요?', '4개(보존적) / 6개(보편적) / 8개(종합) / 10개(와이드 스마일). 얼굴형·미소 라인·예산 종합.' ),
					array( '시린이가 나오나요?', '30~50% 발생, 1~2주 내 회복. 최소 삭제 시스템(디지털 정밀)은 10~20%.' ),
					array( '떨어지면 어떻게 하나요?', '즉시 방문. 깨끗이 보관 후 재접착 또는 재제작. 보증 기간 내 무상.' ),
					array( '색깔 변경이 가능한가요?', '영구 접착 전(2차 방문)에는 가능. 접착 후에는 재제작만 가능.' ),
					array( '계속 씹어도 되나요?', '기본적으로 가능. 견과류·얼음·캐러멜·갈비뼈 등은 주의.' ),
					array( '미백을 추가할 수 있나요?', '라미네이트는 미백이 안 됩니다. <strong>시술 전 미백 → 색상 선택</strong> 순서.' ),
					array( '라미네이트 후 교정 가능한가요?', '가능하나 라미네이트 위치가 변할 수 있어 재제작 필요 가능성. <strong>교정 먼저</strong> 권장.' ),
					array( '크라운과 차이는?', '라미네이트는 앞면만 0.3~0.7mm, 크라운은 전체 1.5~2.0mm. 건강한 치아는 라미네이트 권장.' ),
					array( '임신 중 시술 가능한가요?', '시술은 안전하나 X-ray 어려움. 임신 중기(4~6개월) 또는 출산 후 권장.' ),
					array( '결혼식까지 몇 주 전에 시작해야 하나요?', '최소 6~8주 전 (충치·잇몸 회복 포함). 디지털 시스템은 1개월 전 가능.' ),
					array( '이갈이가 있는데 가능한가요?', '가능하나 <strong>야간 마우스가드 필수</strong>. 지르코니아 재료 우선 고려.' ),
					array( '흡연자도 가능한가요?', '가능하나 착색·잇몸 손상 위험. 시술 전후 최소 2주 금연 권장.' ),
					array( '커피는 마셔도 되나요?', '마셔도 됨. 다만 가장자리 착색 방지 위해 <strong>섭취 후 양치·가글</strong>.' ),
					array( '만약 라미네이트가 깨졌다면?', '깨진 조각 보관 후 즉시 방문. 재접착 또는 재제작. 보증 기간 확인.' ),
					array( '치아 삭제가 무섭습니다.', '디지털 정밀 라미네이트는 0.2~0.4mm만 삭제 (에나멜 두께의 30~40%). 최소 삭제 시스템 상담.' ),
					array( '무삭제 라미네이트를 강조하는 곳은?', '완전 무삭제 가능 케이스는 5% 미만. "100% 무삭제" 광고는 신중하게 확인하세요.' ),
					array( '어금니에도 라미네이트 가능한가요?', '가능은 하나 저작압이 커서 파절 위험. 어금니는 <strong>크라운·지르코니아</strong> 권장.' ),
					array( '한 번에 여러 개 해도 되나요?', '가능. 앞니 6~10개는 하루에 삭제·인상 후 2주 뒤 접착. 케이스별 판단.' ),
					array( '보증 기간은 얼마나 되나요?', '치과별 상이. 정품 emax 기준 <strong>3~5년 무상 재시술</strong> 권장. 서면 확인 필수.' ),
					array( 'CAD/CAM 라미네이트란?', '컴퓨터 디자인 + 밀링 머신 제작. 정밀도 0.05mm, 두께 균일성 확보. 원내 제작 시 당일~3일.' ),
					array( 'DSD (Digital Smile Design)란?', '얼굴·입꼬리·잇몸 라인을 3D로 분석해 미소 디자인 후 시술 전 미리 시뮬레이션. 기대와 실제 결과 불일치 예방.' ),
					array( '스포츠 시 마우스가드 필요한가요?', '격투기·럭비 등 접촉 스포츠는 반드시. 일반 운동은 필요 없음.' ),
					array( '전동 칫솔 사용해도 되나요?', '<strong>음파식(Sonic) 전동 칫솔</strong> 권장. 회전식은 마모 위험.' ),
					array( '라미네이트 후 미백 가능한가요?', '라미네이트 자체는 미백이 안 되므로 시술 전에 미백 완료 → 색상 결정 순서.' ),
					array( '알레르기가 있어요.', '도재는 알레르기 반응 거의 없음. 레진 시멘트 알레르기 병력 시 패치 테스트 가능.' ),
					array( '재시술이 필요한 경우는?', '① 탈락 ② 파절 ③ 잇몸 퇴축으로 가장자리 노출 ④ 색상 변화 ⑤ 접착면 충치. 6개월~1년마다 검진.' ),
					array( '천안·아산에서 라미네이트 잘하는 치과는?', '① 디지털 스캐너·DSD ② 정품 인증 도재 ③ 원내 기공소 ④ 3~5년 보증 ⑤ 다학제 협진 ⑥ 30여년 경력. 문치과병원은 이 모든 기준을 갖춘 천안·아산 대표 치과병원입니다.' ),
				) )
				. '</div>',
		),

	),
);
