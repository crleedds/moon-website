<?php
/**
 * Guide Data — 천안 투명교정 (인비절라인) 종합 안내서
 *
 * @package moondental-child
 */
if ( ! defined( 'ABSPATH' ) ) exit;

return array(
	'slug'         => 'invisalign',
	'code'         => 'GUIDE 02',
	'icon'         => '😁',
	'eyebrow'      => '천안·아산 대표 치과병원 · 30여년 임상',
	'title'        => '천안 투명교정 종합 안내서',
	'subtitle'     => '인비절라인 7가지 패키지·비용·기간·SmartTrack 원리·ClinCheck 정확도를 학술 근거로 정리',
	'reading'      => '약 22분',
	'updated'      => '2026.08',
	'tags'         => array( '35+ FAQ', '19 섹션', '패키지 비교' ),
	'summary'      => 'SmartTrack 소재과학부터 Express·Lite·Comprehensive·Teen까지 7가지 패키지 비교. ClinCheck·어트랙먼트·IPR 원리를 모두 설명합니다.',
	'cta_page'     => '/인비절라인/',
	'cta_label'    => '문치과병원 교정 진료 페이지',
	'related'      => array(
		array( 'label' => '천안 임플란트 종합 안내서', 'href' => '/가이드/임플란트/', 'icon' => '🦷' ),
		array( 'label' => '천안 라미네이트 종합 안내서', 'href' => '/가이드/라미네이트/', 'icon' => '✨' ),
	),
	'toc' => array(
		array( 'id' => 'what',       'label' => '인비절라인이란' ),
		array( 'id' => 'history',    'label' => '역사와 진화' ),
		array( 'id' => 'material',   'label' => 'SmartTrack 소재과학' ),
		array( 'id' => 'packages',   'label' => '7가지 패키지 비교' ),
		array( 'id' => 'cost',       'label' => '비용 총정리 (2026)' ),
		array( 'id' => 'duration',   'label' => '치료 기간·단계' ),
		array( 'id' => 'process',    'label' => '치료 과정 9단계' ),
		array( 'id' => 'clincheck',  'label' => 'ClinCheck 원리' ),
		array( 'id' => 'attachipr',  'label' => '어트랙먼트와 IPR' ),
		array( 'id' => 'indication', 'label' => '적응증·비적응증' ),
		array( 'id' => 'vs-wire',    'label' => '인비절라인 vs 와이어' ),
		array( 'id' => 'vs-other',   'label' => '인비절라인 vs 타 투명교정' ),
		array( 'id' => 'risks',      'label' => '부작용과 합병증' ),
		array( 'id' => 'retainer',   'label' => '리테이너·평생 유지' ),
		array( 'id' => 'age',        'label' => '연령별 가이드' ),
		array( 'id' => 'daily',      'label' => '일상 관리·주의사항' ),
		array( 'id' => 'clinic',     'label' => '좋은 치과 고르는 법' ),
		array( 'id' => 'moon',       'label' => '문치과병원 교정' ),
		array( 'id' => 'faq',        'label' => '자주 묻는 질문 30선' ),
	),
	'sections' => array(

		array(
			'id'    => 'what',
			'title' => '01 · 인비절라인이란 무엇인가',
			'body'  => '<p><strong>인비절라인(Invisalign)</strong>은 미국 얼라인 테크놀로지가 1997년 개발한 <strong>세계 최초의 디지털 투명 교정 시스템</strong>입니다. 얇고 투명한 얼라이너를 1~2주마다 교체하며 치아를 이동시킵니다.</p>
			<h3>4가지 핵심 기술</h3>
			<ol>
				<li><strong>iTero 3D 디지털 스캔</strong> · 0.025mm 정밀도</li>
				<li><strong>ClinCheck 시뮬레이션</strong> · AI 기반 치료 계획</li>
				<li><strong>SmartTrack 소재</strong> · 특허 다층 열가소성 폴리우레탄</li>
				<li><strong>SmartForce 어트랙먼트</strong> · 치아별 맞춤 부착물</li>
			</ol>
			<h3>왜 선호되는가</h3>
			<ul>
				<li>식사·양치 시 <strong>탈착 가능</strong></li>
				<li>응급 내원 <strong>30% 감소</strong> (와이어 대비)</li>
				<li>통증 강도 <strong>50% 저하</strong> (Journal of Clinical Orthodontics, 2019)</li>
				<li>구내염 발생률 <strong>1/10</strong> 수준</li>
				<li>환자 동의율 90% 이상</li>
			</ul>',
		),

		array(
			'id'    => 'history',
			'title' => '02 · 인비절라인의 역사와 진화',
			'body'  => '<h3>세대별 발전</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>세대</th><th>연도</th><th>혁신</th><th>의미</th></tr></thead>
				<tbody>
					<tr><td>1세대</td><td>1999~2007</td><td>EX30 단일층, 평균 30개 얼라이너</td><td>심미 우수, 복잡 케이스 한계</td></tr>
					<tr><td>2세대</td><td>2008~2013</td><td>SmartForce 어트랙먼트, IPR 프로토콜</td><td>회전·압하 등 복잡 이동 가능</td></tr>
					<tr><td>3세대</td><td>2013~2018</td><td>SmartTrack 다층 소재, G5~G7</td><td>발치 케이스 적용 가능</td></tr>
					<tr><td>4세대</td><td>2019~현재</td><td>iTero 5D, AI ClinCheck, 하악전진</td><td>턱교정·청소년 성장기 활용</td></tr>
				</tbody>
			</table></div>
			<h3>한국 도입 현황</h3>
			<ul>
				<li>2005년 도입, 2014년 이후 본격 확산</li>
				<li>2026년 현재 누적 약 <strong>65만 건</strong></li>
				<li>성인 교정 시장의 약 <strong>35%</strong> 차지</li>
			</ul>',
		),

		array(
			'id'    => 'material',
			'title' => '03 · SmartTrack 소재과학',
			'body'  => '<h3>3대 특성</h3>
			<ol>
				<li><strong>일정한 힘 전달</strong> · 일반 PETG는 24시간 후 힘 50% 감소, SmartTrack은 1주간 거의 일정. 예측 가능성 30% 향상.</li>
				<li><strong>유연성·강도 균형</strong> · 외부는 단단, 내부는 부드러움. 어트랙먼트 밀착성 75% 향상.</li>
				<li><strong>광학적 투명성</strong> · 굴절률 1.49 (치아 에나멜 1.63과 유사).</li>
			</ol>
			<h3>소재 비교</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>특성</th><th>SmartTrack</th><th>PETG</th><th>PVC</th></tr></thead>
				<tbody>
					<tr><td>1주 후 힘 유지</td><td>~65%</td><td>~35%</td><td>~15%</td></tr>
					<tr><td>어트랙먼트 밀착</td><td>★★★★★</td><td>★★★</td><td>★★</td></tr>
					<tr><td>이동 정확도</td><td>~85%</td><td>50~60%</td><td>~40%</td></tr>
					<tr><td>BPA Free</td><td>O</td><td>제품별</td><td>X</td></tr>
				</tbody>
			</table></div>
			<p><strong>인증</strong> · FDA Class II · CE · KFDA · BPA/BPS/프탈레이트 모두 검출 안 됨.</p>',
		),

		array(
			'id'    => 'packages',
			'title' => '04 · 인비절라인 7가지 패키지 완벽 비교',
			'body'  => '<h3>성인용 4종</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>패키지</th><th>얼라이너</th><th>기간</th><th>적응증</th><th>비용</th></tr></thead>
				<tbody>
					<tr><td>Express</td><td>7장 이하</td><td>3~6개월</td><td>경미 재발·부분 정렬</td><td>300~400만</td></tr>
					<tr><td>Lite</td><td>14장 이하</td><td>6~9개월</td><td>경도 부정교합·앞니</td><td>400~500만</td></tr>
					<tr><td>Moderate</td><td>26장 이하</td><td>9~15개월</td><td>중등도·비발치</td><td>500~650만</td></tr>
					<tr><td>Comprehensive</td><td>무제한</td><td>12~24개월</td><td>복잡·발치·모든 케이스</td><td>650~900만</td></tr>
				</tbody>
			</table></div>
			<h3>청소년·어린이용 3종</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>패키지</th><th>대상</th><th>특징</th><th>비용</th></tr></thead>
				<tbody>
					<tr><td>Invisalign First</td><td>7~10세</td><td>악궁 확장·공간 확보</td><td>500~700만</td></tr>
					<tr><td>Invisalign Teen</td><td>11~17세</td><td>컴플라이언스 표시·분실 보증</td><td>600~800만</td></tr>
					<tr><td>Teen Moderate</td><td>11~17세</td><td>경도 케이스</td><td>500~650만</td></tr>
				</tbody>
			</table></div>',
		),

		array(
			'id'    => 'cost',
			'title' => '05 · 인비절라인 비용 총정리 (2026)',
			'body'  => '<h3>패키지별 전국 평균</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>패키지</th><th>전국 평균</th><th>수도권</th><th>지방 광역시</th></tr></thead>
				<tbody>
					<tr><td>Express</td><td>~350만</td><td>380~450만</td><td>300~380만</td></tr>
					<tr><td>Lite</td><td>~450만</td><td>480~550만</td><td>400~480만</td></tr>
					<tr><td>Moderate</td><td>~580만</td><td>620~700만</td><td>520~600만</td></tr>
					<tr><td>Comprehensive</td><td>~750만</td><td>800~900만</td><td>650~780만</td></tr>
				</tbody>
			</table></div>
			<h3>비용 구성 항목</h3>
			<ul>
				<li>얼라인 테크놀로지 라이센스 (약 100~250만)</li>
				<li>의료진 진료비 (ClinCheck 설계·정기 점검)</li>
				<li>iTero 스캔 (1회당 5~10만)</li>
				<li>X-ray·CT (10~30만)</li>
				<li>Vivera 리테이너 2~4세트 (15~60만)</li>
				<li>Refinement (Lite 이상 무제한 포함)</li>
			</ul>
			<h3>추가 비용</h3>
			<ul>
				<li>일반 발치 · 5~10만</li>
				<li>매복 사랑니 발치 · 15~30만</li>
				<li>임플란트 앵커리지 · 1개당 15~25만 (평균 2~4개)</li>
				<li>리테이너 재제작 · 1세트 15~30만</li>
				<li>패키지 업그레이드 · 150~200만</li>
			</ul>
			<h3>절약 팁</h3>
			<ul>
				<li>가족 동시 교정 · 10~15% 할인</li>
				<li>기능적 부정교합 시 실비 보험 확인</li>
				<li>연말정산 의료비 세액공제 활용</li>
				<li>카드 무이자 할부 (12개월)</li>
			</ul>',
		),

		array(
			'id'    => 'duration',
			'title' => '06 · 치료 기간과 단계별 진행',
			'body'  => '<h3>케이스별 평균 기간</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>케이스</th><th>얼라이너 수</th><th>기간</th><th>난이도</th></tr></thead>
				<tbody>
					<tr><td>앞니 재발</td><td>5~7장</td><td>3~4개월</td><td>★</td></tr>
					<tr><td>경미 정렬·공간</td><td>10~14장</td><td>6~9개월</td><td>★★</td></tr>
					<tr><td>중등도 (비발치)</td><td>20~30장</td><td>10~15개월</td><td>★★★</td></tr>
					<tr><td>발치 케이스</td><td>40~60장</td><td>18~24개월</td><td>★★★★</td></tr>
					<tr><td>복잡 (양악수술)</td><td>60~80장+</td><td>24~36개월</td><td>★★★★★</td></tr>
				</tbody>
			</table></div>
			<h3>기간 결정 변수</h3>
			<ul>
				<li><strong>착용 시간</strong> · 22시간 이상 = 1주 교체</li>
				<li><strong>이동 거리</strong> · 1mm당 약 2~3개월</li>
				<li><strong>이동 종류</strong> · 단순 경사(빠름) vs 회전·압하(오래)</li>
				<li><strong>어트랙먼트</strong> · 적절 활용 시 30% 단축</li>
				<li><strong>골밀도·연령</strong> · 청소년 30~40% 빠름, 50대+ 10~20% 느림</li>
				<li><strong>IPR 활용</strong> · 발치 회피·기간 단축</li>
			</ul>',
		),

		array(
			'id'    => 'process',
			'title' => '07 · 치료 과정 9단계',
			'body'  => '<ol class="md-guide-steps">
				<li><strong>초기 상담·진단</strong> · 교정 동기·주소·전신 병력 확인</li>
				<li><strong>정밀 검사</strong> · X-ray·CT·iTero 스캔 (5~10분)</li>
				<li><strong>ClinCheck 설계</strong> · 의료진이 이동 순서·각도·어트랙먼트 설계 (1~2주)</li>
				<li><strong>ClinCheck 환자 확인</strong> · 3D 결과 확인·동의</li>
				<li><strong>얼라이너 제작·배송</strong> · 미국/멕시코 제작 3~4주 후 배송</li>
				<li><strong>1차 얼라이너 장착·어트랙먼트 부착</strong> · 템플릿 정확 부착, IPR 진행</li>
				<li><strong>정기 점검</strong> · 6~8주마다 진행 상황 확인</li>
				<li><strong>Refinement</strong> · 초기 시리즈 종료 후 추가 얼라이너 (평균 1~2회)</li>
				<li><strong>리테이너 장착·유지</strong> · Vivera 2~4세트, 단계적 착용 감량</li>
			</ol>',
		),

		array(
			'id'    => 'clincheck',
			'title' => '08 · ClinCheck 시뮬레이션 원리',
			'body'  => '<h3>ClinCheck 구성</h3>
			<ul>
				<li>3D 모델 (0.025mm 정밀 디지털)</li>
				<li>단계별 시뮬레이션 (매 얼라이너 0.25~0.3mm)</li>
				<li>치근 시뮬레이션 (CT 통합 시)</li>
				<li>이동 그래프 (X/Y/Z축·회전각·토크)</li>
				<li>충돌 감지·예상 종료 시기</li>
			</ul>
			<h3>이동 유형별 정확도</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>이동 유형</th><th>정확도</th><th>대응 전략</th></tr></thead>
				<tbody>
					<tr><td>경사 이동</td><td>~85%</td><td>오버컬렉션 5~10%</td></tr>
					<tr><td>치체 이동</td><td>~70%</td><td>어트랙먼트 + 오버컬렉션</td></tr>
					<tr><td>회전</td><td>50~70%</td><td>강력 어트랙먼트, 오버 20%</td></tr>
					<tr><td>압하</td><td>30~40%</td><td>미니스크류 보조 권장</td></tr>
					<tr><td>정출</td><td>~30%</td><td>버튼 + 엘라스틱 보조</td></tr>
					<tr><td>토크 조절</td><td>40~50%</td><td>오버컬렉션 30% + 마무리 와이어</td></tr>
				</tbody>
			</table></div>
			<p>Kravitz (2009) 초기 정확도 41% → 최신 SmartTrack + 최적화 어트랙먼트 <strong>85%</strong>까지 향상.</p>',
		),

		array(
			'id'    => 'attachipr',
			'title' => '09 · 어트랙먼트와 IPR',
			'body'  => '<h3>어트랙먼트 종류</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>종류</th><th>역할</th><th>주 적용 부위</th></tr></thead>
				<tbody>
					<tr><td>Beveled</td><td>경사 이동 강화</td><td>모든 치아</td></tr>
					<tr><td>Rectangular</td><td>치체 이동·회전</td><td>견치·소구치</td></tr>
					<tr><td>Optimized Rotation</td><td>치아 회전</td><td>견치·측절치</td></tr>
					<tr><td>Optimized Extrusion</td><td>치아 정출</td><td>전치부</td></tr>
					<tr><td>Optimized Root Control</td><td>치근 위치 조절</td><td>전치부 토크</td></tr>
					<tr><td>Power Ridge</td><td>전치부 치근 제어</td><td>상악 절치</td></tr>
				</tbody>
			</table></div>
			<p>평균 1인당 <strong>8~15개</strong> 어트랙먼트 부착. 광중합 레진 사용. 제거 시 표면 손상 0.05mm 이하.</p>
			<h3>IPR (Interproximal Reduction)</h3>
			<ul>
				<li><strong>정의</strong> · 치아 측면 에나멜 0.1~0.5mm 미세 삭제</li>
				<li><strong>목표</strong> · 공간 확보 (발치 없이 1~6mm)·접촉점 개선·재발 방지</li>
				<li><strong>안전성</strong> · 에나멜 두께의 30~50%만 삭제. 30년 임상에서 충치·시린이 거의 미보고.</li>
			</ul>',
		),

		array(
			'id'    => 'indication',
			'title' => '10 · 적응증과 비적응증',
			'body'  => '<h3>잘 되는 케이스</h3>
			<ul>
				<li>경도~중등도 정렬 문제</li>
				<li>경도~중등도 공간 문제</li>
				<li>경도 과개교합 (3mm 이내)</li>
				<li>경도 개방교합 (3mm 이내)</li>
				<li>약간의 비대칭 (2~3mm 이내)</li>
				<li>교정 후 재발</li>
				<li>경도 돌출입</li>
			</ul>
			<h3>까다로운 케이스 (상대적 비적응)</h3>
			<ul>
				<li>심한 골격성 부정교합</li>
				<li>심한 회전 (60° 이상)</li>
				<li>심한 압하·정출</li>
				<li>매복치 견인</li>
				<li>임플란트 보철 존재</li>
				<li>잇몸 질환 진행 중</li>
			</ul>
			<h3>불가능한 케이스 (절대 비적응)</h3>
			<ul>
				<li>유치 단독 (7세 미만)</li>
				<li>심한 치주염 (골 흡수 50% 이상)</li>
				<li>혈액 응고 장애·면역 저하</li>
				<li>방치된 다발성 충치</li>
				<li>환자 협조 불가능</li>
			</ul>',
		),

		array(
			'id'    => 'vs-wire',
			'title' => '11 · 인비절라인 vs 와이어 교정',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>항목</th><th>인비절라인</th><th>금속 와이어</th><th>세라믹 와이어</th><th>설측</th></tr></thead>
				<tbody>
					<tr><td>심미성</td><td>★★★★★</td><td>★</td><td>★★★</td><td>★★★★★</td></tr>
					<tr><td>비용</td><td>400~900만</td><td>150~300만</td><td>250~450만</td><td>500~900만</td></tr>
					<tr><td>기간</td><td>6~24개월</td><td>12~30개월</td><td>12~30개월</td><td>18~36개월</td></tr>
					<tr><td>탈착 가능</td><td>O</td><td>X</td><td>X</td><td>X</td></tr>
					<tr><td>구강 위생</td><td>★★★★★</td><td>★★</td><td>★★</td><td>★</td></tr>
					<tr><td>통증</td><td>약함</td><td>중등도</td><td>중등도</td><td>중등도~심함</td></tr>
					<tr><td>응급 내원</td><td>거의 없음</td><td>월 1~2회</td><td>월 1~2회</td><td>월 1~2회</td></tr>
				</tbody>
			</table></div>
			<h3>인비절라인이 특히 좋은 대상</h3>
			<ul>
				<li>직업상 외모 노출 많음 (방송·서비스·영업)</li>
				<li>구강 위생 관리 자신 없음</li>
				<li>스포츠·악기 사용</li>
				<li>치과 공포증</li>
				<li>출장·행사 예정</li>
			</ul>',
		),

		array(
			'id'    => 'vs-other',
			'title' => '12 · 인비절라인 vs 다른 투명교정',
			'body'  => '<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>시스템</th><th>제조사</th><th>임상 역사</th><th>특징</th></tr></thead>
				<tbody>
					<tr><td>Invisalign</td><td>Align Technology</td><td>27년</td><td>전세계 1,800만+, ClinCheck</td></tr>
					<tr><td>ClearCorrect</td><td>Straumann</td><td>17년</td><td>30~40% 저렴, 단순 케이스</td></tr>
					<tr><td>Spark Aligner</td><td>Ormco</td><td>5년</td><td>2020년 출시, 데이터 축적 중</td></tr>
					<tr><td>Suresmile</td><td>Dentsply Sirona</td><td>10년</td><td>미국·유럽 중심</td></tr>
					<tr><td>국내 투명교정</td><td>국내 기공소</td><td>5~10년</td><td>200~400만, 단순만 권장</td></tr>
				</tbody>
			</table></div>
			<h3>인비절라인의 강점</h3>
			<ol>
				<li>임상 데이터 <strong>1,800만+ 케이스</strong></li>
				<li>SmartTrack 소재 특허</li>
				<li>SmartForce 어트랙먼트 다양성</li>
				<li>iTero 통합 워크플로우</li>
				<li>Invisalign Academy 표준화 교육</li>
			</ol>
			<div class="md-guide-callout md-guide-callout--warn">
				<strong>국내 저가 투명교정의 한계</strong> · PETG 소재 (힘 유지율 50% 이하)·어트랙먼트 부재·기공소 대행 설계·임상 데이터 부족·Refinement 별도 청구.
			</div>',
		),

		array(
			'id'    => 'risks',
			'title' => '13 · 부작용과 합병증',
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
				<li><strong>어트랙먼트 충치</strong> · 5~10% (관리 시 1% 이하). 매 식사 후 양치 + 불소 가글로 예방.</li>
				<li><strong>치근 흡수</strong> · ~5%, 평균 0.5~1mm. 정기 X-ray 모니터링.</li>
				<li><strong>잇몸 퇴축</strong> · 3~7%, 흡연자·잇몸 얇은 분 고위험. ClinCheck 설계 시 치근 위치 조절.</li>
				<li><strong>TMJ 증상</strong> · 10~15%, 대부분 종료 후 회복. 기존 TMJ 환자는 악화 가능.</li>
				<li><strong>알레르기</strong> · &lt;0.01% (폴리우레탄 알레르기 병력 시 패치 테스트).</li>
			</ol>',
		),

		array(
			'id'    => 'retainer',
			'title' => '14 · 리테이너와 평생 유지',
			'body'  => '<h3>리테이너 종류</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>종류</th><th>장점</th><th>단점</th><th>비용</th></tr></thead>
				<tbody>
					<tr><td>Vivera</td><td>정밀, 1세트 4매</td><td>분실·파손 위험</td><td>30~60만</td></tr>
					<tr><td>Essix</td><td>저렴·빠른 제작</td><td>수명 짧음 (1~2년)</td><td>10~15만</td></tr>
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
				<strong>재발률</strong> · 리테이너 미착용 시 1년 내 <strong>30% 이상</strong>이 재배열됩니다 (Little, 1988).
			</div>',
		),

		array(
			'id'    => 'age',
			'title' => '15 · 인비절라인 연령별 가이드',
			'body'  => '<h3>7~10세 (혼합치열기, Invisalign First)</h3>
			<ul><li>목표 · 악궁 확장·미맹출 공간 확보·습관 교정·성장 가이드</li></ul>
			<h3>11~17세 (영구치열기, Invisalign Teen)</h3>
			<ul>
				<li>컴플라이언스 인디케이터 (착용 시간 표시)</li>
				<li>6세트 무료 분실 보증</li>
				<li>미맹출 영구치 컴펜세이션</li>
				<li>맨디뷸러 어드밴스먼트 (하악 전방)</li>
			</ul>
			<h3>18~30대 (청장년)</h3>
			<ul><li>인비절라인 환자 약 <strong>60%</strong>. 심미·업무 편의성으로 선호.</li></ul>
			<h3>40~50대 (중장년)</h3>
			<ul>
				<li>환자 약 25% (증가 추세)</li>
				<li>치주 상태 사전 점검 필수</li>
				<li>기존 보철물 고려</li>
				<li>이동 속도 20~30% 느림</li>
			</ul>
			<h3>60대 이상</h3>
			<ul>
				<li>완전히 가능 (잇몸 건강 시)</li>
				<li>비스포스포네이트 복용 시 상담</li>
				<li>2도 이상 흔들림은 금기</li>
				<li>기간 30~40% 더 길게 예상</li>
			</ul>',
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
			<h3>음료·음식 가이드</h3>
			<ul>
				<li>착용 중 · 물만 가능</li>
				<li>주의 · 커피·홍차·콜라 (착색)·탄산·산성 음료·껌·캐러멜</li>
			</ul>
			<h3>여행·운동·특수 상황</h3>
			<ul>
				<li>여행 시 다음 1~2세트 챙기기 + 케이스 + 담당 치과 연락처</li>
				<li>일반 운동·수영 · 착용 가능</li>
				<li>격투기·럭비 · 별도 마우스가드 권장</li>
				<li>악기·가창 · 1~2주 발음 적응</li>
				<li>흡연 · 얼라이너 변색·잇몸 혈류 감소·재발률 증가로 강력 비권장</li>
			</ul>',
		),

		array(
			'id'    => 'clinic',
			'title' => '17 · 좋은 인비절라인 치과 고르는 법',
			'body'  => '<h3>반드시 확인할 8가지</h3>
			<ol>
				<li>의료진 <strong>인비절라인 등급</strong> (다이아몬드 이상 권장)</li>
				<li>iTero 5D 스캐너 보유</li>
				<li>치과의사 본인이 ClinCheck 설계 (기공소 대행 X)</li>
				<li>CBCT 보유</li>
				<li>전체 패키지 가격 공개</li>
				<li>정기 점검 주기 (6~8주)</li>
				<li>Refinement 정책 (Comprehensive 5년 무제한)</li>
				<li>중간 변경 정책 공지</li>
			</ol>
			<h3>피해야 할 Red Flag</h3>
			<ul>
				<li>"패키지 무관 일률 가격"</li>
				<li>ClinCheck 미리 보여주지 않음</li>
				<li>의료진 자주 변경</li>
				<li>"3개월 끝낸다" 과장</li>
				<li>리테이너 별도 청구 미명시</li>
				<li>iTero 없이 인상재만 사용</li>
			</ul>
			<h3>의료진 등급 시스템 (2026)</h3>
			<div class="md-guide-tablewrap"><table class="md-guide-table">
				<thead><tr><th>등급</th><th>연간 케이스</th><th>의미</th></tr></thead>
				<tbody>
					<tr><td>Bronze</td><td>10~20</td><td>입문</td></tr>
					<tr><td>Silver</td><td>20~50</td><td>기본 처리</td></tr>
					<tr><td>Gold</td><td>50~100</td><td>중등도 케이스</td></tr>
					<tr><td>Platinum</td><td>100~200</td><td>대부분 케이스</td></tr>
					<tr><td>Diamond</td><td>200+</td><td>국내 상위 5%</td></tr>
					<tr><td>Diamond Apex</td><td>500+</td><td>세계 상위 1%</td></tr>
				</tbody>
			</table></div>',
		),

		array(
			'id'    => 'moon',
			'title' => '18 · 문치과병원 인비절라인',
			'body'  => '<h3>천안·아산 30여년 교정 진료</h3>
			<p>한아의료재단 <strong>문치과병원</strong>은 통합 진료센터 4개층을 갖춘 천안·아산 대표 치과병원입니다. 교정과·구강외과·보철과 <strong>다학제 협진</strong>으로 인비절라인·와이어·설측·양악 병행 케이스까지 정밀 계획이 가능합니다.</p>
			<h3>진료 강점</h3>
			<ul>
				<li><strong>디지털 구강 스캐너</strong> 도입 · iTero 계열 정밀 스캔</li>
				<li><strong>CBCT</strong> 기반 3D 치근·골격 평가</li>
				<li><strong>ClinCheck 원장 직접 설계</strong> · 기공소 대행 없음</li>
				<li>Refinement·리테이너·정기 점검 포함 <strong>투명한 패키지 안내</strong></li>
				<li>어린이 First·청소년 Teen·성인 Comprehensive 등 <strong>연령별 대응</strong></li>
			</ul>
			<h3>진료 예약</h3>
			<p>교정 상담을 원하시면 <a href="/상담예약/">📅 상담 예약</a> 또는 <a href="tel:0415612275">전화 041-561-2275</a>로 연락 주세요. 상세 정보는 <a href="/인비절라인/">교정 진료 페이지</a>에서 확인하실 수 있습니다.</p>',
		),

		array(
			'id'    => 'faq',
			'title' => '19 · 자주 묻는 질문 30선',
			'body'  => '<div class="md-guide-faq" itemscope itemtype="https://schema.org/FAQPage">' .
				md_guide_faq_html( array(
					array( '인비절라인 도중 충치가 생기면 어떻게 하나요?', '작은 충치는 어트랙먼트 영향 없는 부위면 착용 유지하며 치료 가능. 크라운이 필요하면 일시 중단 후 재시작합니다.' ),
					array( '초기 상담 시 사전 처치가 필요한가요?', '① 충치·잇몸 치료 ② 매복 사랑니 발치 ③ 노후 보철물 평가 ④ 치주 스케일링. 평균 1~3개월 소요.' ),
					array( '임신 중에도 안전한가요?', 'SmartTrack은 BPA-free, FDA Class II 인증. 임신 초기 잇몸 변화로 일시 중단 가능. 산부인과와 상의 후 결정.' ),
					array( '얼라이너를 잃어버렸을 때는?', '즉시 치과 연락. 다음 세트 있으면 즉시 교체, 없으면 이전 세트 임시 착용 후 새 세트 응급 주문 (1~2주). 24시간 이상 방치 금지.' ),
					array( '얼라이너가 깨졌어요.', '가장자리 균열은 사용 가능. 중간 균열은 조기 교체. 완전 파손은 응급 주문.' ),
					array( '얼라이너 색이 변했어요.', '세정제(Retainer Brite, Polident)로 침지 후 솔질. 회복 안 되면 다음 세트로 조기 교체.' ),
					array( '커피는 마셔도 되나요?', '얼라이너 제거 후만 가능. 뜨거운 음료는 변형 위험. 섭취 후 양치 또는 가글 필수.' ),
					array( '얼라이너 끼고 자니 통증이 있어요.', '새 얼라이너 첫 2~3일 압박감은 정상. 이부프로펜 복용 가능. 1주 이상 지속되면 상담.' ),
					array( '침이 너무 많이 나와요.', '초기 1주는 정상. 침샘이 이물질 감지로 분비 증가. 적응 후 정상화.' ),
					array( '발음이 어색해요.', '특히 "s", "th"에 영향. 1~2주 안에 적응. 책 읽기·발음 연습이 도움. 1개월 지속 시 상담.' ),
					array( '여행 갈 때 어떻게 챙기나요?', '현재 + 다음 1~2세트, 케이스, 칫솔·치약·치실, 담당 치과 연락처. 장기 여행은 이전 세트도 백업.' ),
					array( '비행기 기압에 영향이 있나요?', '완전히 없음. 안전합니다. 케이스만 휴대.' ),
					array( '격투기·럭비할 때는?', '별도의 마우스가드 권장. 얼라이너는 충격 완충용이 아닙니다.' ),
					array( '결혼식 당일은 어떻게 하나요?', '제거 후 진행 가능 (최대 24시간). 어트랙먼트는 약간 보이나 멀리서 거의 안 보임. 식 1~2주 전 임시 제거도 가능.' ),
					array( '인비절라인 중 다른 치과 진료는?', '스케일링·단순 충치는 무관. 크라운·발치·임플란트·신경치료는 담당 의료진과 상의 필수.' ),
					array( '양악수술도 병행 가능한가요?', '가능. 수술 전 교정 → 수술 → 수술 후 마무리 교정 순. 다이아몬드급 + 경험 많은 의료진 필수.' ),
					array( '교정 종료 후 다시 비뚤어졌어요.', '리테이너 소홀 시 흔함. 경미 → Express 재정렬, 중등도 → Lite/Moderate 재시작.' ),
					array( '리테이너를 안 끼면 어떻게 되나요?', '1년 내 30% 이상 재배열 발생. 특히 아래 앞니. 평생 야간 착용이 가장 저렴한 보험.' ),
					array( '나이가 많은데 가능할까요?', '70~80대도 가능 (잇몸 건강 시). 잇몸 흡수 50% 이상은 금기. 이동 속도 30~40% 느림.' ),
					array( '흡연 중인데 가능한가요?', '가능하나 강력 비권장. 얼라이너 변색·잇몸 혈류 30% 감소·재발률 증가·치주염 악화.' ),
					array( '음주는요?', '얼라이너 제거 후 가능. 진한 색 음료(레드와인)는 양치 후 재장착. 산성음료(샴페인)는 치아 약화.' ),
					array( '얼라이너가 잘 안 들어가요.', '새 얼라이너 첫 1~2일은 정상. 천천히 양손 균일 압박, Chewies (실리콘 패드) 사용. 따뜻한 물 금지. 1일 이상 불가 시 상담.' ),
					array( '체중이 빠졌어요.', '일부 환자 1~3kg 감소 보고. 간식·야식 감소로 인한 부수 효과.' ),
					array( '임플란트와 병용 가능한가요?', '가능. 임플란트는 고정되고 자연치만 이동. ClinCheck에서 고정치 처리.' ),
					array( '크라운·브릿지가 있어요.', '가능하나 어트랙먼트 위치 조정. 교정 종료 후 재제작 가능성.' ),
					array( '혀에 상처가 나요.', '초기 1~2주 정상. 왁스·가글·다듬기로 해결.' ),
					array( '시림이 있어요.', '일시적 (1~2개월). 시림 방지 치약 사용.' ),
					array( '키스할 때 상대방이 인지하나요?', '거의 인지 못함. 필요 시 1~2시간 제거 가능.' ),
					array( '치아 이동이 안 느껴져요.', '정상. SmartTrack은 지속적 저부하로 통증 적음. ClinCheck 예측과 실제 확인은 정기 점검에서.' ),
					array( '천안·아산에서 교정 잘하는 치과는?', '① 인비절라인 등급 ② iTero + CBCT ③ 원장 직접 ClinCheck ④ 다학제 협진 ⑤ 30여년 경력. 문치과병원은 이 모든 기준을 갖춘 천안·아산 대표 치과병원입니다.' ),
				) )
				. '</div>',
		),

	),
);
