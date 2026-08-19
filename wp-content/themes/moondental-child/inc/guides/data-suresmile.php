<?php
/**
 * Guide Data — 교정센터 투명교정 종합안내서 (SureSmile Aligners)
 *
 * 문치과병원은 슈어스마일(Dentsply Sirona) 을 도입한 교정 진료 시스템 운영.
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

return array(
	'slug'         => 'suresmile',
	'code'         => 'NO. 02',
	'icon'         => '😁',
	'eyebrow'      => '천안·아산 대표 치과병원 · 30여년 임상',
	'center'       => '교정센터',
	'title'        => '투명교정 종합안내서',
	'subtitle'     => '슈어스마일(SureSmile) 소재·치료 흐름·비용·기간·부작용을 학술 근거와 문치과병원 교정센터 임상 경험으로 정리',
	'reading'      => '약 22분',
	'updated'      => '2026.08',
	'tags'         => array( '30+ FAQ', '18 섹션', '슈어스마일' ),
	'summary'      => 'Dentsply Sirona 슈어스마일 · TruGEN XR 소재 · CEREC 디지털 워크플로우 · 아날로그 대비 오차 대폭 축소. 성인 교정의 심미·기능·경제성을 균형 있게 다룹니다.',
	'cta_page'     => '/투명교정/',
	'cta_label'    => '문치과병원 교정 진료 페이지',
	'related'      => array(
		array( 'label' => '임플란트센터 종합안내서',         'href' => '/guide/implant/',  'icon' => '🦷' ),
		array( 'label' => '스마일디자인센터 라미네이트 종합안내서', 'href' => '/guide/laminate/', 'icon' => '✨' ),
	),
	'toc' => array(
		array( 'id' => 'what',       'label' => '슈어스마일이란' ),
		array( 'id' => 'history',    'label' => '역사와 진화' ),
		array( 'id' => 'material',   'label' => 'TruGEN XR 소재 과학' ),
		array( 'id' => 'workflow',   'label' => 'CEREC 디지털 워크플로우' ),
		array( 'id' => 'packages',   'label' => '패키지 옵션' ),
		array( 'id' => 'cost',       'label' => '비용 총정리 (2026)' ),
		array( 'id' => 'duration',   'label' => '치료 기간·단계' ),
		array( 'id' => 'process',    'label' => '치료 과정 9단계' ),
		array( 'id' => 'planning',   'label' => 'SureSmile Aligner Studio' ),
		array( 'id' => 'attachipr',  'label' => '어트랙먼트와 IPR' ),
		array( 'id' => 'indication', 'label' => '적응증·비적응증' ),
		array( 'id' => 'vs-wire',    'label' => '슈어스마일 vs 와이어 교정' ),
		array( 'id' => 'vs-other',   'label' => '슈어스마일 vs 인비절라인' ),
		array( 'id' => 'risks',      'label' => '부작용과 합병증' ),
		array( 'id' => 'retainer',   'label' => '리테이너·평생 유지' ),
		array( 'id' => 'daily',      'label' => '일상 관리·주의사항' ),
		array( 'id' => 'clinic',     'label' => '좋은 치과 고르는 법' ),
		array( 'id' => 'moon',       'label' => '문치과병원 교정센터' ),
		array( 'id' => 'faq',        'label' => '자주 묻는 질문 30선' ),
	),
	'sections' => array(

		array(
			'id'    => 'what',
			'title' => '01 · 슈어스마일이란 무엇인가',
			'body'  => '<p><strong>슈어스마일(SureSmile)</strong>은 <strong>Dentsply Sirona</strong>가 제공하는 디지털 교정 시스템입니다. 원래 와이어 교정을 로봇으로 정밀 벤딩하는 기술로 시작되어, <strong>SureSmile Aligner</strong> 라는 이름의 투명 교정 시스템으로 확장되었습니다.</p>
			<h3>4가지 핵심 기술</h3>
			<ol>
				<li><strong>CEREC Primescan</strong> · 정밀 3D 디지털 구강 스캔 (약 20µm 정밀도)</li>
				<li><strong>SureSmile Aligner Studio</strong> · 3D 치료 계획 및 시뮬레이션 소프트웨어</li>
				<li><strong>TruGEN XR</strong> · Dentsply Sirona 독자 다층 열가소성 소재 (지속적 저부하 전달)</li>
				<li><strong>SureSmile Attachments / IPR 프로토콜</strong> · 복잡 치아 이동을 위한 부착물·미세 삭제</li>
			</ol>
			<h3>임상적 강점</h3>
			<ul>
				<li>Dentsply Sirona 의 <strong>CEREC 생태계 완전 통합</strong> (스캔·기공·아날라이저 원-스톱)</li>
				<li>지속적 힘 전달로 <strong>통증·구내염 감소</strong></li>
				<li>탈착 가능 · 식사·양치 시 제거</li>
				<li>투명 · 심미성 우수 (약 1m 거리에서 거의 인지 불가)</li>
			</ul>',
		),

		array(
			'id'    => 'history',
			'title' => '02 · 슈어스마일의 역사와 진화',
			'body'  => '<h3>세대별 발전</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>연도</th><th>이벤트</th><th>의미</th></tr></thead>
				<tbody>
					<tr><td>1998</td><td>OraMetrix, 슈어스마일 개발 시작</td><td>와이어 로봇 벤딩 기술 원조</td></tr>
					<tr><td>2004</td><td>슈어스마일 와이어 시스템 상용화</td><td>맞춤형 와이어 · 치료 기간 단축</td></tr>
					<tr><td>2016</td><td>Dentsply Sirona, OraMetrix 인수</td><td>CEREC 생태계 통합 시작</td></tr>
					<tr><td>2019</td><td>SureSmile Aligner 출시</td><td>투명 교정 시장 진입</td></tr>
					<tr><td>2021~</td><td>TruGEN XR 소재 · Aligner Studio 업그레이드</td><td>임상 데이터 축적 · 예측 정확도 향상</td></tr>
					<tr><td>2024~</td><td>AI 기반 치료 계획·리파인먼트 자동화</td><td>의료진 워크플로우 효율 극대화</td></tr>
				</tbody>
			</table></div>
			<h3>한국 도입</h3>
			<ul>
				<li>Dentsply Sirona Korea 를 통해 국내 확산</li>
				<li>CEREC 시스템 사용 치과 중심으로 채택</li>
				<li>인비절라인 대비 후발주자이나 <strong>디지털 정밀도·CEREC 통합·합리적 비용</strong>으로 성장</li>
			</ul>',
		),

		array(
			'id'    => 'material',
			'title' => '03 · TruGEN XR 소재 과학',
			'body'  => '<h3>3대 특성</h3>
			<ol>
				<li><strong>지속적 힘 전달 (Sustained Force)</strong><br>다층 열가소성 폴리머 구조로 착용 1주간 힘 유지율이 일반 PETG(약 35%) 대비 <strong>60% 이상</strong> 수준을 유지. 치아 이동의 예측 가능성 향상.</li>
				<li><strong>유연·강도 균형</strong><br>내부 층은 부드러워 착용감 우수, 외부 층은 단단해 어트랙먼트 밀착 유지. 착용 중 파절률 감소.</li>
				<li><strong>광학 투명성</strong><br>굴절률이 치아 에나멜에 근접 · 착용 시 눈에 잘 띄지 않음. 색진 저항성도 개선.</li>
			</ol>
			<h3>주요 소재 비교</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>특성</th><th>TruGEN XR</th><th>SmartTrack</th><th>PETG</th></tr></thead>
				<tbody>
					<tr><td>1주 후 힘 유지</td><td>~60%</td><td>~65%</td><td>~35%</td></tr>
					<tr><td>어트랙먼트 밀착</td><td>★★★★★</td><td>★★★★★</td><td>★★★</td></tr>
					<tr><td>이동 정확도 (평균)</td><td>~82%</td><td>~85%</td><td>50~60%</td></tr>
					<tr><td>BPA Free</td><td>O</td><td>O</td><td>제품별</td></tr>
				</tbody>
			</table></div>
			<p><strong>인증</strong> · FDA · CE · KFDA 인증. BPA·BPS·프탈레이트 검출 안 됨.</p>',
		),

		array(
			'id'    => 'workflow',
			'title' => '04 · CEREC 디지털 워크플로우',
			'body'  => '<p>슈어스마일의 가장 큰 차별점은 <strong>Dentsply Sirona 의 CEREC 시스템과 완전 통합</strong>된다는 점입니다. 스캔·분석·계획·기공·정기 점검이 하나의 디지털 파이프라인 안에서 이뤄집니다.</p>
			<h3>전통 vs CEREC + 슈어스마일 워크플로우</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>단계</th><th>전통 (아날로그)</th><th>슈어스마일 (CEREC)</th></tr></thead>
				<tbody>
					<tr><td>인상</td><td>인상재 · 구역감 · 재시도 잦음</td><td>Primescan 3D 스캔 · 5~10분</td></tr>
					<tr><td>분석</td><td>석고 모형 · 평면 계측</td><td>3D 소프트웨어 · 자동 계측</td></tr>
					<tr><td>치료 계획</td><td>의료진 수기 · 예측 어려움</td><td>SureSmile Aligner Studio · 3D 시뮬레이션</td></tr>
					<tr><td>기공</td><td>외부 기공소 · 1~2주</td><td>Dentsply Sirona 밀링·3D 프린팅 · 짧아진 리드타임</td></tr>
					<tr><td>정기 점검</td><td>육안 비교</td><td>디지털 스캔 오버레이 · 이동 정량 평가</td></tr>
				</tbody>
			</table></div>
			<h3>Primescan 정밀도</h3>
			<ul>
				<li>표면 정밀도 · 약 <strong>20µm</strong> (인상재 대비 4~5배 정밀)</li>
				<li>전악 스캔 시간 · 약 60~90초</li>
				<li>실시간 오버레이 · 스캔 누락·왜곡 즉시 확인</li>
			</ul>',
		),

		array(
			'id'    => 'packages',
			'title' => '05 · 슈어스마일 패키지 옵션',
			'body'  => '<p>슈어스마일 얼라이너는 인비절라인처럼 여러 티어(7단계)로 세분화되어 있지 않습니다. 대신 <strong>케이스 복잡도에 따라 얼라이너 수량과 리파인먼트 정책</strong>이 결정됩니다.</p>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>구분</th><th>얼라이너 수</th><th>기간</th><th>적응증</th><th>비용 (참고)</th></tr></thead>
				<tbody>
					<tr><td>경도 (Simple)</td><td>10~20장</td><td>4~9개월</td><td>앞니 재발·경도 정렬</td><td>250~400만</td></tr>
					<tr><td>중등도 (Moderate)</td><td>20~40장</td><td>9~15개월</td><td>비발치 중등도 부정교합</td><td>400~600만</td></tr>
					<tr><td>복잡 (Complex)</td><td>40장 이상</td><td>15~24개월</td><td>발치·복잡 케이스</td><td>550~800만</td></tr>
					<tr><td>Full / 무제한</td><td>무제한 + 리파인먼트</td><td>18~30개월</td><td>모든 케이스</td><td>700~950만</td></tr>
				</tbody>
			</table></div>
			<p class="md-guide-note">* 비용은 지역·의료진·부가 시술에 따라 상이. 문치과병원 상담에서 정확한 견적을 제공합니다.</p>',
		),

		array(
			'id'    => 'cost',
			'title' => '06 · 슈어스마일 비용 총정리 (2026)',
			'body'  => '<h3>기본 비용 구조</h3>
			<ul>
				<li>Dentsply Sirona 라이센스 · 케이스별 (비공개)</li>
				<li>의료진 진료비 · Aligner Studio 계획·정기 점검</li>
				<li>Primescan 스캔 · 1회당 5~10만</li>
				<li>X-ray·CT · 10~30만</li>
				<li>리테이너 (2~4세트) · 20~50만</li>
				<li>리파인먼트 (Full 패키지에 포함)</li>
			</ul>
			<h3>슈어스마일 vs 인비절라인 · 비용 감각</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>범위</th><th>슈어스마일</th><th>인비절라인</th></tr></thead>
				<tbody>
					<tr><td>경도 케이스</td><td>250~400만</td><td>300~450만</td></tr>
					<tr><td>중등도 케이스</td><td>400~600만</td><td>500~650만</td></tr>
					<tr><td>Full · 복잡 케이스</td><td>700~950만</td><td>800~1,100만</td></tr>
				</tbody>
			</table></div>
			<p class="md-guide-note">* 슈어스마일은 대체로 인비절라인 대비 <strong>10~20% 저렴</strong>한 편이나 지역·병원별 차이 큼.</p>
			<h3>추가 비용 (필요시)</h3>
			<ul>
				<li>일반 발치 · 5~10만</li>
				<li>매복 사랑니 발치 · 15~30만</li>
				<li>임플란트 앵커리지 · 1개당 15~25만 (평균 2~4개)</li>
				<li>리테이너 재제작 · 1세트 15~30만</li>
			</ul>
			<h3>절약 팁</h3>
			<ul>
				<li>가족 동시 교정 · 할인 문의</li>
				<li>연말정산 의료비 세액공제</li>
				<li>카드 무이자 할부</li>
				<li>실비 보험 (기능적 부정교합 시 일부 청구 가능)</li>
			</ul>',
		),

		array(
			'id'    => 'duration',
			'title' => '07 · 치료 기간과 단계',
			'body'  => '<h3>케이스별 평균 기간</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>케이스</th><th>얼라이너 수</th><th>기간</th></tr></thead>
				<tbody>
					<tr><td>앞니 재발</td><td>10~15장</td><td>4~6개월</td></tr>
					<tr><td>경미 정렬·공간</td><td>15~25장</td><td>6~10개월</td></tr>
					<tr><td>중등도 (비발치)</td><td>25~40장</td><td>10~16개월</td></tr>
					<tr><td>발치 케이스</td><td>40~60장</td><td>18~24개월</td></tr>
					<tr><td>복잡 (양악수술)</td><td>60~80장+</td><td>24~36개월</td></tr>
				</tbody>
			</table></div>
			<h3>기간 결정 변수</h3>
			<ul>
				<li>착용 시간 · 하루 <strong>22시간 이상</strong> 권장</li>
				<li>얼라이너 교체 주기 · 1주 또는 10일 (계획에 따라)</li>
				<li>이동 종류 · 단순 경사 vs 회전·압하(더 오래 걸림)</li>
				<li>어트랙먼트 · 적절 활용 시 정확도 향상</li>
				<li>골밀도·연령 · 청소년 빠름, 50대+ 느림</li>
				<li>IPR 활용 · 발치 회피·기간 조율</li>
			</ul>',
		),

		array(
			'id'    => 'process',
			'title' => '08 · 치료 과정 9단계',
			'body'  => '<ol class="md-guide-steps">
				<li><strong>초기 상담·진단</strong> · 교정 동기·주소·병력·구강 검사</li>
				<li><strong>정밀 검사</strong> · CEREC Primescan · X-ray · CBCT (5~10분)</li>
				<li><strong>Aligner Studio 계획</strong> · 원장 직접 이동 순서·각도·어트랙먼트·IPR 설계 (1~2주)</li>
				<li><strong>환자 3D 시뮬레이션 확인</strong> · 예상 결과 시각 확인 · 동의</li>
				<li><strong>얼라이너 제작·배송</strong> · Dentsply Sirona 제작 후 배송</li>
				<li><strong>1차 얼라이너 장착·어트랙먼트·IPR</strong> · 템플릿 사용 정확 부착</li>
				<li><strong>정기 점검</strong> · 6~8주마다 · 디지털 스캔 오버레이로 이동 정량 평가</li>
				<li><strong>리파인먼트</strong> · 필요 시 추가 얼라이너 (Full 패키지 포함)</li>
				<li><strong>리테이너 장착·유지</strong> · 초기 22시간 → 야간 → 평생 야간 주 2~3일</li>
			</ol>',
		),

		array(
			'id'    => 'planning',
			'title' => '09 · SureSmile Aligner Studio',
			'body'  => '<p>SureSmile Aligner Studio 는 슈어스마일의 <strong>치료 계획 시뮬레이션 소프트웨어</strong>입니다. 인비절라인의 ClinCheck 과 유사한 역할이지만, <strong>CEREC 스캔 데이터와 완전 통합</strong>된 것이 특징입니다.</p>
			<h3>Studio 의 주요 기능</h3>
			<ul>
				<li>3D 모델 (Primescan 정밀 데이터)</li>
				<li>단계별 시뮬레이션 (얼라이너별 이동량 시각화)</li>
				<li>치근 시뮬레이션 (CBCT 통합 시)</li>
				<li>이동 그래프 (X/Y/Z축·회전·토크)</li>
				<li>충돌 감지·예상 종료 시기</li>
				<li>AI 기반 이동 최적화 제안 (2024 이후)</li>
			</ul>
			<h3>좋은 Studio 설계의 특징</h3>
			<ul>
				<li>스테이징 전략 (어떤 치아부터 이동)</li>
				<li>어트랙먼트 최적화 (필요 최소한 · 심미 고려)</li>
				<li>오버컬렉션 (예상 부족분 보상)</li>
				<li>IPR 분산 (전체 케이스에 걸쳐 균등)</li>
				<li>치근 평행도 유지</li>
			</ul>
			<h3>이동 유형별 정확도 (임상 평균)</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>이동 유형</th><th>정확도</th><th>대응 전략</th></tr></thead>
				<tbody>
					<tr><td>경사 이동</td><td>~85%</td><td>오버컬렉션 5~10%</td></tr>
					<tr><td>치체 이동</td><td>~70%</td><td>어트랙먼트 + 오버컬렉션</td></tr>
					<tr><td>회전</td><td>50~70%</td><td>강력 어트랙먼트, 오버 20%</td></tr>
					<tr><td>압하</td><td>30~40%</td><td>미니스크류 병행 권장</td></tr>
					<tr><td>정출</td><td>~30%</td><td>버튼 + 엘라스틱 보조</td></tr>
					<tr><td>토크 조절</td><td>40~50%</td><td>오버컬렉션 + 마무리 와이어</td></tr>
				</tbody>
			</table></div>',
		),

		array(
			'id'    => 'attachipr',
			'title' => '10 · 어트랙먼트와 IPR',
			'body'  => '<h3>어트랙먼트 (Attachments)</h3>
			<p>치아 표면에 부착하는 작은 광중합 레진 부착물. 얼라이너가 치아를 정확히 잡고 이동시키도록 돕습니다.</p>
			<ul>
				<li>평균 1인당 <strong>8~15개</strong> 부착</li>
				<li>슈어스마일 표준 어트랙먼트 + 최적화 어트랙먼트 (회전·정출 등 목적별)</li>
				<li>광중합 레진 사용 · 제거 시 표면 손상 0.05mm 이하</li>
			</ul>
			<h3>IPR (Interproximal Reduction)</h3>
			<ul>
				<li><strong>정의</strong> · 치아 측면 에나멜 0.1~0.5mm 미세 삭제</li>
				<li><strong>목표</strong> · 공간 확보 (발치 없이 1~6mm)·접촉점 개선·재발 방지</li>
				<li><strong>안전성</strong> · 에나멜 두께의 30~50%만 삭제 · 장기 임상에서 충치·시린이 거의 미보고</li>
			</ul>
			<div class="md-guide-callout">
				<strong>💡 왜 발치 없이 가능한가?</strong><br>
				IPR + 악궁 확장으로 <strong>2~4mm 공간을 만들 수 있어</strong> 경도~중등도 케이스는 발치 회피 가능. 심한 총생·돌출입은 여전히 발치가 필요할 수 있습니다.
			</div>',
		),

		array(
			'id'    => 'indication',
			'title' => '11 · 적응증과 비적응증',
			'body'  => '<h3>잘 되는 케이스</h3>
			<ul>
				<li>경도~중등도 정렬 문제</li>
				<li>경도~중등도 공간 문제 (정중이개 포함)</li>
				<li>경도 과개교합 (3mm 이내)</li>
				<li>경도 개방교합 (3mm 이내)</li>
				<li>약간의 비대칭 (2~3mm 이내)</li>
				<li>교정 후 재발</li>
				<li>경도 돌출입</li>
			</ul>
			<h3>까다로운 케이스 (상대적 비적응)</h3>
			<ul>
				<li>심한 골격성 부정교합 (양악수술 병행 시 가능)</li>
				<li>심한 회전 (60° 이상)</li>
				<li>심한 압하·정출</li>
				<li>매복치 견인</li>
				<li>임플란트 보철 존재</li>
				<li>잇몸 질환 진행 중</li>
			</ul>
			<h3>불가능한 케이스 (절대 비적응)</h3>
			<ul>
				<li>유치 단독</li>
				<li>심한 치주염 (골 흡수 50% 이상)</li>
				<li>혈액 응고 장애·면역 저하</li>
				<li>방치된 다발성 충치</li>
				<li>환자 협조 불가능</li>
			</ul>',
		),

		array(
			'id'    => 'vs-wire',
			'title' => '12 · 슈어스마일 vs 와이어 교정',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>슈어스마일</th><th>금속 와이어</th><th>세라믹 와이어</th><th>설측</th></tr></thead>
				<tbody>
					<tr><td>심미성</td><td>★★★★★</td><td>★</td><td>★★★</td><td>★★★★★</td></tr>
					<tr><td>비용 참고</td><td>250~950만</td><td>150~300만</td><td>250~450만</td><td>500~900만</td></tr>
					<tr><td>기간</td><td>4~30개월</td><td>12~30개월</td><td>12~30개월</td><td>18~36개월</td></tr>
					<tr><td>탈착 가능</td><td>O</td><td>X</td><td>X</td><td>X</td></tr>
					<tr><td>구강 위생</td><td>★★★★★</td><td>★★</td><td>★★</td><td>★</td></tr>
					<tr><td>통증</td><td>약함</td><td>중등도</td><td>중등도</td><td>중등도~심함</td></tr>
					<tr><td>응급 내원</td><td>거의 없음</td><td>월 1~2회</td><td>월 1~2회</td><td>월 1~2회</td></tr>
				</tbody>
			</table></div>
			<h3>슈어스마일이 특히 좋은 대상</h3>
			<ul>
				<li>직업상 외모 노출 많음 (방송·서비스·영업)</li>
				<li>구강 위생 관리 우선</li>
				<li>스포츠·악기 사용</li>
				<li>치과 공포증</li>
				<li>출장·행사 예정</li>
			</ul>',
		),

		array(
			'id'    => 'vs-other',
			'title' => '13 · 슈어스마일 vs 인비절라인',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>슈어스마일</th><th>인비절라인</th></tr></thead>
				<tbody>
					<tr><td>제조사</td><td>Dentsply Sirona</td><td>Align Technology</td></tr>
					<tr><td>임상 역사 (얼라이너)</td><td>~7년</td><td>~27년</td></tr>
					<tr><td>계획 소프트웨어</td><td>SureSmile Aligner Studio</td><td>ClinCheck</td></tr>
					<tr><td>스캐너</td><td>CEREC Primescan</td><td>iTero 5D</td></tr>
					<tr><td>소재</td><td>TruGEN XR</td><td>SmartTrack</td></tr>
					<tr><td>패키지 구조</td><td>복잡도 기반 유연</td><td>7단계 티어</td></tr>
					<tr><td>비용 감각</td><td>대체로 10~20% 저렴</td><td>프리미엄</td></tr>
					<tr><td>강점</td><td>CEREC 생태계 통합·합리적 비용</td><td>임상 데이터·표준화</td></tr>
					<tr><td>약점</td><td>글로벌 임상 데이터 인비절라인 대비 적음</td><td>가격 · 커스터마이징 여지 상대적 적음</td></tr>
				</tbody>
			</table></div>
			<div class="md-guide-callout">
				<strong>💡 어느 쪽이 낫나?</strong><br>
				대부분의 성인 교정 케이스에서 <strong>임상 결과는 유사</strong>합니다. 차이는 <strong>가격·워크플로우·의료진 경험</strong>에 있습니다. CEREC 시스템을 잘 활용하는 병원에서는 슈어스마일이 <strong>더 정밀하고 효율적</strong>일 수 있습니다.
			</div>',
		),

		array(
			'id'    => 'risks',
			'title' => '14 · 부작용과 합병증',
			'body'  => '<h3>흔한 부작용 (일시적)</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>부작용</th><th>발생률</th><th>지속</th><th>대처</th></tr></thead>
				<tbody>
					<tr><td>초기 통증·이물감</td><td>~100%</td><td>2~3일</td><td>이부프로펜·시간</td></tr>
					<tr><td>발음 변화 (s·th)</td><td>~80%</td><td>1~2주</td><td>읽기 연습</td></tr>
					<tr><td>침 분비 증가</td><td>~60%</td><td>1주</td><td>적응</td></tr>
					<tr><td>입술·뺨 자극</td><td>~30%</td><td>1~3일</td><td>왁스</td></tr>
					<tr><td>잇몸 압박감</td><td>~50%</td><td>1~2일</td><td>시간 경과</td></tr>
				</tbody>
			</table></div>
			<h3>중요한 부작용 (관리 필요)</h3>
			<ol>
				<li><strong>어트랙먼트 충치</strong> · 관리 시 1% 이하. 매 식사 후 양치 + 불소 가글로 예방.</li>
				<li><strong>치근 흡수</strong> · ~5%, 평균 0.5~1mm. 정기 CBCT 모니터링.</li>
				<li><strong>잇몸 퇴축</strong> · 흡연자·잇몸 얇은 분 고위험. Aligner Studio 설계 시 치근 위치 조절.</li>
				<li><strong>TMJ 증상</strong> · 대부분 종료 후 회복. 기존 TMJ 환자는 악화 가능.</li>
				<li><strong>알레르기</strong> · 매우 드묾. 폴리우레탄 알레르기 병력 시 패치 테스트.</li>
			</ol>',
		),

		array(
			'id'    => 'retainer',
			'title' => '15 · 리테이너와 평생 유지',
			'body'  => '<h3>리테이너 종류</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>종류</th><th>장점</th><th>단점</th><th>비용</th></tr></thead>
				<tbody>
					<tr><td>투명 리테이너 (Essix/Vivera류)</td><td>정밀·심미</td><td>수명 1~2년 · 분실 위험</td><td>15~40만</td></tr>
					<tr><td>Hawley</td><td>내구성·미세 조정</td><td>발음 영향·보임</td><td>10~20만</td></tr>
					<tr><td>고정식 (Lingual)</td><td>탈착 불필요·평생</td><td>치석 관리 어려움</td><td>20~40만</td></tr>
				</tbody>
			</table></div>
			<h3>착용 프로토콜</h3>
			<ul>
				<li>0~6개월 · 22시간 (식사·양치 시만 제거)</li>
				<li>6~12개월 · 12~14시간 (취침 + 저녁)</li>
				<li>1~3년 · 야간 8~10시간</li>
				<li>3년 이후 · 주 2~3일 야간 (평생)</li>
			</ul>
			<div class="md-guide-callout md-guide-callout--warn">
				<strong>재발률</strong> · 리테이너 미착용 시 1년 내 <strong>30% 이상</strong>이 재배열됩니다 (Little, 1988). 특히 아래 앞니. 평생 야간 착용이 가장 저렴한 보험.
			</div>',
		),

		array(
			'id'    => 'daily',
			'title' => '16 · 일상 관리와 주의사항',
			'body'  => '<h3>매일 관리 루틴</h3>
			<ol>
				<li>아침 · 제거 → 양치 → 세척 → 재장착</li>
				<li>식사 시 · 제거 후 케이스 보관</li>
				<li>식사 후 · 양치 → 세척 → 재장착</li>
				<li>간식·음료 · 물만 착용 가능</li>
				<li>취침 · 양치 + 치실 + 불소 가글 → 재장착</li>
			</ol>
			<h3>얼라이너 세척법</h3>
			<ul>
				<li>매일 · 미온수 + 부드러운 칫솔</li>
				<li>주 2~3회 · 세정제 침지 (10~15분)</li>
				<li>금지 · 뜨거운 물·치약·알코올</li>
			</ul>
			<h3>음료·음식 안내</h3>
			<ul>
				<li>착용 중 · 물만 가능</li>
				<li>주의 · 커피·홍차·콜라 (착색)·탄산·산성·껌·캐러멜</li>
			</ul>
			<h3>여행·운동·특수</h3>
			<ul>
				<li>여행 시 다음 1~2세트 + 케이스 + 담당 치과 연락처</li>
				<li>일반 운동·수영 착용 가능</li>
				<li>격투기·럭비 · 별도 마우스가드 권장</li>
				<li>흡연 · 얼라이너 변색·잇몸 혈류 감소·재발률 증가로 강력 비권장</li>
			</ul>',
		),

		array(
			'id'    => 'clinic',
			'title' => '17 · 좋은 슈어스마일 치과 고르는 법',
			'body'  => '<h3>반드시 확인할 항목</h3>
			<ol>
				<li>CEREC Primescan (또는 동급) 보유</li>
				<li>Dentsply Sirona 슈어스마일 <strong>공식 인증</strong> 여부</li>
				<li>치과의사 본인이 <strong>Aligner Studio 직접 설계</strong> (기공소 대행 X)</li>
				<li>CBCT 보유 (심층 치근 평가)</li>
				<li>전체 패키지 가격 사전 공개</li>
				<li>정기 점검 주기 (6~8주)</li>
				<li>리파인먼트 정책 (Full 패키지 무제한)</li>
				<li>실제 케이스 사례 (Before/After)</li>
			</ol>
			<h3>피해야 할 Red Flag</h3>
			<ul>
				<li>"패키지 무관 일률 가격"</li>
				<li>3D 시뮬레이션 미리 보여주지 않음</li>
				<li>스캐너 없이 인상재만 사용</li>
				<li>의료진 자주 변경</li>
				<li>"3개월 끝낸다" 과장</li>
				<li>리테이너 별도 청구 미명시</li>
			</ul>',
		),

		array(
			'id'    => 'moon',
			'title' => '18 · 문치과병원 교정센터',
			'body'  => '<h3>천안·아산 30여년 교정 진료</h3>
			<p>한아의료재단 <strong>문치과병원 교정센터</strong>는 통합 진료센터 4개층 중 한 층을 교정 전문 공간으로 운영합니다. 교정과·구강외과·보철과 <strong>다학제 협진</strong>으로 슈어스마일·와이어·설측·양악 병행 케이스까지 정밀 계획이 가능합니다.</p>
			<h3>진료 강점</h3>
			<ul>
				<li><strong>Dentsply Sirona 슈어스마일</strong> 도입 · CEREC 생태계 통합</li>
				<li><strong>디지털 구강 스캐너</strong> · 정밀 인상·구역감 없음</li>
				<li><strong>CBCT</strong> 기반 3D 치근·골격 평가</li>
				<li><strong>Aligner Studio 원장 직접 설계</strong> · 기공소 대행 없음</li>
				<li>리파인먼트·리테이너·정기 점검 포함 <strong>투명한 패키지 안내</strong></li>
				<li>어린이·청소년·성인 <strong>연령별 대응</strong></li>
			</ul>
			<h3>진료 예약</h3>
			<p>교정 상담을 원하시면 <a href="/상담예약/">📅 상담 예약</a> 또는 <a href="tel:0415612275">전화 041-561-2275</a>로 연락 주세요. 상세 정보는 <a href="/투명교정/">교정 진료 페이지</a>에서 확인하실 수 있습니다.</p>',
		),

		array(
			'id'    => 'faq',
			'title' => '19 · 자주 묻는 질문 30선',
			'body'  => '<div class="md-guide-faq" itemscope itemtype="https://schema.org/FAQPage">' .
				md_guide_faq_html( array(
					array( '슈어스마일과 인비절라인의 차이는?', '기술 원리는 유사하나 슈어스마일은 <strong>Dentsply Sirona 의 CEREC 생태계와 통합</strong>되어 있습니다. 대부분 성인 케이스에서 임상 결과는 유사하며, 슈어스마일이 <strong>10~20% 저렴</strong>한 경우가 많습니다.' ),
					array( '문치과병원은 왜 슈어스마일을 하나요?', 'CEREC 통합 워크플로우로 <strong>스캔·계획·기공·점검</strong>이 하나의 파이프라인 안에서 이뤄져 정밀도와 효율이 우수합니다. 원장이 Aligner Studio 를 직접 설계할 수 있어 커스터마이징 여지가 큽니다.' ),
					array( '치료 도중 충치가 생기면?', '작은 충치는 어트랙먼트 영향 없는 부위면 착용 유지하며 치료. 크라운이 필요하면 일시 중단 후 재시작.' ),
					array( '초기 상담 시 사전 처치가 필요한가요?', '① 충치·잇몸 치료 ② 매복 사랑니 발치 ③ 노후 보철물 평가 ④ 치주 스케일링. 평균 1~3개월 소요.' ),
					array( '임신 중에도 안전한가요?', 'TruGEN XR 은 BPA-free, 각종 인증 통과. 임신 초기 잇몸 변화로 일시 중단 가능. 산부인과와 상의 후 결정.' ),
					array( '얼라이너를 잃어버렸을 때는?', '즉시 치과 연락. 다음 세트 있으면 즉시 교체, 없으면 이전 세트 임시 착용 후 재제작.' ),
					array( '얼라이너가 깨졌어요.', '가장자리 균열은 사용 가능. 중간 균열은 조기 교체. 완전 파손은 응급 주문.' ),
					array( '색이 변했어요.', '세정제로 침지 후 솔질. 회복 안 되면 다음 세트로 조기 교체.' ),
					array( '커피는 마셔도 되나요?', '얼라이너 제거 후만 가능. 뜨거운 음료는 변형 위험. 섭취 후 양치·가글 필수.' ),
					array( '얼라이너 끼고 자면 통증이 있어요.', '새 얼라이너 첫 2~3일 압박감은 정상. 이부프로펜 복용 가능. 1주 이상 지속되면 상담.' ),
					array( '침이 너무 많이 나와요.', '초기 1주는 정상. 침샘이 이물질 감지로 분비 증가. 적응 후 정상화.' ),
					array( '발음이 어색해요.', '특히 "s", "th". 1~2주 안에 적응. 책 읽기·발음 연습이 도움. 1개월 지속 시 상담.' ),
					array( '여행 갈 때는?', '현재 + 다음 1~2세트, 케이스, 세척용품, 담당 치과 연락처. 장기 여행은 이전 세트도 백업.' ),
					array( '비행기 기압 영향은?', '완전히 없음. 안전. 케이스만 휴대.' ),
					array( '격투기·럭비는?', '별도의 마우스가드 권장. 얼라이너는 충격 완충용이 아님.' ),
					array( '결혼식 당일은?', '제거 후 진행 가능 (최대 24시간). 어트랙먼트는 멀리서 거의 안 보임. 식 1~2주 전 임시 제거도 가능.' ),
					array( '치료 중 다른 치과 진료는?', '스케일링·단순 충치는 무관. 크라운·발치·임플란트·신경치료는 담당 의료진과 상의 필수.' ),
					array( '양악수술도 병행 가능한가요?', '가능. 수술 전 교정 → 수술 → 수술 후 마무리 교정 순.' ),
					array( '교정 종료 후 다시 비뚤어졌어요.', '리테이너 소홀 시 흔함. 경미 → 짧은 재정렬 코스, 중등도 → 부분 재시작.' ),
					array( '리테이너를 안 끼면?', '1년 내 30% 이상 재배열. 특히 아래 앞니. 평생 야간 착용이 가장 저렴한 보험.' ),
					array( '나이가 많은데 가능할까요?', '70~80대도 가능 (잇몸 건강 시). 이동 속도 30~40% 느림.' ),
					array( '흡연 중인데 가능한가요?', '가능하나 강력 비권장. 얼라이너 변색·잇몸 혈류 감소·재발률 증가.' ),
					array( '음주는요?', '얼라이너 제거 후 가능. 진한 색 음료는 양치 후 재장착. 산성 음료는 치아 약화.' ),
					array( '얼라이너가 잘 안 들어가요.', '새 얼라이너 첫 1~2일은 정상. 천천히 양손 균일 압박, Chewies 사용. 따뜻한 물 금지.' ),
					array( '체중 변화가 있나요?', '일부 환자 1~3kg 감소 (간식·야식 감소로 인한 부수 효과).' ),
					array( '임플란트와 병용 가능?', '가능. 임플란트는 고정되고 자연치만 이동. Studio 에서 고정치 처리.' ),
					array( '크라운·브릿지 있어요.', '가능하나 어트랙먼트 위치 조정. 종료 후 재제작 가능성.' ),
					array( '혀에 상처가 나요.', '초기 1~2주 정상. 왁스·가글·다듬기로 해결.' ),
					array( '키스할 때 상대방이 인지하나요?', '거의 인지 못함. 필요 시 1~2시간 제거 가능.' ),
					array( '천안·아산에서 교정 잘하는 치과는?', '① 슈어스마일 공식 도입 ② CEREC + CBCT ③ 원장 직접 Aligner Studio 설계 ④ 다학제 협진 ⑤ 30여년 경력. 문치과병원 교정센터는 이 모든 기준을 갖춘 천안·아산 대표 교정 진료 시스템입니다.' ),
				) )
				. '</div>',
		),

	),
);
