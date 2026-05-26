<?php
/**
 * 기본 페이지 콘텐츠 정의
 *
 * 각 페이지 템플릿이 WP 편집기 본문이 비어 있을 때 자동으로 출력하는
 * 기본 본문. 사용자가 만든 6개 진료 페이지 슬러그에 정확히 매칭.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * 진료영역별 기본 본문 콘텐츠.
 *
 * @param string $slug 사용자가 만든 페이지 슬러그 (한글 OK)
 * @return string HTML
 */
function moondental_default_service_content( $slug ) {
	$map = array(

		/* ─────────────────────────────  임플란트 센터 (10F)  ───────────────────────────── */
		'임플란트-센터' => '
<h2>10F 임플란트센터 — 30년 임상의 안정감</h2>
<p class="lead">문치과병원은 1995년 개원 이래 천안 지역에서 가장 많은 임플란트 임상 경험을 쌓아왔습니다. 단일 임플란트부터 전악(全顎) 무치악 환자까지, 환자분의 골 상태와 라이프스타일에 맞춘 식립 계획을 제시합니다.</p>

<h3>전담 의료진</h3>
<ul>
<li><strong>문지현 원장</strong> — 서울대 치의학대학원, 포스텍 신소재공학 학·석사, 미국 UCSF·UPENN 연수</li>
<li><strong>이창률 원장</strong> — 미국 UCLA·UCSF 출신, 미국·한국 치과의사 면허, CDA·ADA 정회원</li>
<li><strong>문은수 대표 병원장</strong> — 한아임플란트 보철연구소장 · 대한구강악안면임플란트학회 이사</li>
</ul>

<h3>진료 분야</h3>
<ul>
<li><strong>단일 임플란트</strong> — 1개 치아 상실 시 인접치 손상 없이 회복</li>
<li><strong>다수·구치부 임플란트</strong> — 어금니 다수 상실 시 저작 기능 회복</li>
<li><strong>전악 임플란트 / All-on-4·6</strong> — 무치악·반무치악 환자의 고정성 회복</li>
<li><strong>네비게이션 수술</strong> — 디지털 가이드로 식립 정확도·예측성 향상</li>
<li><strong>골이식·상악동 거상술</strong> — 골이 부족한 경우 단계적 골재건</li>
<li><strong>임플란트 재수술</strong> — 타원에서 식립 후 문제 발생 시 케이스 검토</li>
</ul>

<h3>치료 흐름</h3>
<ol>
<li><strong>1차 상담 & CT 촬영</strong> — 골량·신경위치·치조골 상태 정밀 분석</li>
<li><strong>치료 계획 수립</strong> — 식립 위치·개수·기간·비용 사전 안내</li>
<li><strong>식립 수술</strong> — 구강악안면외과 전문의 집도, 멸균 시스템 하에 진행</li>
<li><strong>골유착 기간</strong> — 통상 3~6개월, 정기 검진 동반</li>
<li><strong>최종 보철</strong> — 디지털 스캔으로 제작한 크라운 장착</li>
<li><strong>유지·관리</strong> — 정기적인 전문 클리닉으로 평생 관리</li>
</ol>

<h3>자주 묻는 질문</h3>
<p><strong>Q. 통증이 많이 있나요?</strong><br>국소마취 하에 진행되며 수술 자체는 거의 통증이 없습니다. 수술 후 1~2일 정도의 둔한 통증은 진통제로 충분히 조절됩니다.</p>
<p><strong>Q. 임플란트는 평생 가나요?</strong><br>관리에 따라 10~20년 이상 유지가 가능하며, 정기적인 검진과 구강위생 관리가 핵심입니다.</p>
<p><strong>Q. 골이식이 꼭 필요한가요?</strong><br>환자분의 골량에 따라 다릅니다. CT 분석으로 정확히 판단드리며, 가능하면 골이식을 최소화하는 방향으로 계획합니다.</p>',

		/* ─────────────────────────────  투명교정 센터 (11F)  ───────────────────────────── */
		'투명교정-센터' => '
<h2>11F 교정과 — 보이지 않는 교정, 분명한 결과</h2>
<p class="lead">교정은 평생의 저작·발음·턱관절 건강을 좌우합니다. 문치과병원 교정과는 환자분의 직업·생활 패턴·기대치를 충분히 듣고, 가장 적합한 장치와 기간을 제안합니다.</p>

<h3>전담 의료진</h3>
<ul>
<li><strong>이영일 원장</strong> — 단국대 치의학 박사, <strong>치과교정과 전문의·인정의</strong></li>
<li><strong>김세일 원장</strong> — 이화여대 교정과 석사, 대한치과교정학회 정회원</li>
<li><strong>이창률 원장</strong> — UCSF 교정과 임상연수, 투명교정 과정 수료</li>
<li><strong>문지현 원장</strong> — UCSF 교정과 임상연수, 투명교정 과정 수료</li>
</ul>

<h3>진료 분야</h3>
<ul>
<li><strong>투명교정 (인비절라인 / 클리어얼라이너)</strong> — 직장인·성인 환자에게 부담 적은 옵션</li>
<li><strong>설측교정</strong> — 치아 안쪽에 부착, 외관상 드러나지 않음</li>
<li><strong>일반 교정 (메탈·세라믹 브라켓)</strong> — 가장 검증된 표준 방식</li>
<li><strong>소아·청소년 교정</strong> — 성장기 골격 교정과 영구치 배열</li>
<li><strong>부분 교정</strong> — 앞니 일부만 빠르게 정리하는 경우</li>
<li><strong>턱교정 (양악 등)</strong> — 외과적 접근이 필요한 케이스 협진</li>
</ul>

<h3>교정 흐름</h3>
<ol>
<li><strong>정밀 진단</strong> — 디지털 사진·세팔로 분석·CT(필요 시)</li>
<li><strong>치료 시뮬레이션</strong> — 예상되는 최종 치열을 미리 확인</li>
<li><strong>장치 부착</strong> — 환자분이 선택한 방식으로 시작</li>
<li><strong>주기적 조정</strong> — 통상 4~6주 간격 내원</li>
<li><strong>장치 제거 & 유지장치</strong> — 평생 유지관리 안내</li>
</ol>

<h3>치료 기간 안내</h3>
<ul>
<li>부분 교정: 6개월 ~ 1년</li>
<li>전체 교정: 1.5년 ~ 2.5년</li>
<li>외과 동반: 사전 협진 후 결정</li>
</ul>',

		/* ─────────────────────────────  자연치아 살리기  ───────────────────────────── */
		'자연치아-살리기' => '
<h2>발치보다 보존을 먼저 — 보존과 전문의 진료</h2>
<p class="lead">한 번 잃은 자연치아는 어떤 보철물보다도 좋은 치료가 될 수 없습니다. 문치과병원은 보건복지부 인증 <strong>보존과 전문의</strong>가 정밀 근관치료로 자연치를 최대한 살려냅니다.</p>

<h3>전담 의료진</h3>
<ul>
<li><strong>권혜진 원장</strong> — 보건복지부 인증 보존과 전문의, 단국대 치과대학 보존과 박사, 대한 근관학회 · 대한 보존학회 정회원</li>
</ul>

<h3>진료 분야</h3>
<ul>
<li><strong>근관치료(신경치료)</strong> — 치아 내부 염증·통증을 제거하고 치근을 살려냅니다</li>
<li><strong>치근단 수술</strong> — 일반 근관치료로 해결 안 되는 만성 염증 케이스</li>
<li><strong>크라운 전 치아 보강</strong> — 약해진 자연치를 보강하여 보철 수명 연장</li>
<li><strong>크랙(균열치) 치료</strong> — 균열 깊이에 따라 보존 가능 여부 판단</li>
<li><strong>외상 치아 보존</strong> — 부딪힘·넘어짐으로 손상된 치아 응급 처치</li>
<li><strong>심한 충치의 보존적 치료</strong> — 신경 노출 직전 케이스의 간접 치수 복개</li>
</ul>

<h3>이런 경우에 자연치아 살리기 진료가 필요합니다</h3>
<ul>
<li>찬물·뜨거운 음식에 지속적으로 시린 경우</li>
<li>씹을 때 한 부위에서 날카로운 통증이 느껴지는 경우</li>
<li>잇몸에 작은 농(고름) 주머니가 생기는 경우</li>
<li>타 치과에서 "발치 후 임플란트"를 권유받았지만 보존 가능성을 확인하고 싶은 경우</li>
</ul>

<blockquote>
<p>"치아를 살릴 수 있다면 살리는 것이 가장 보수적이고 안전한 선택입니다. 발치는 마지막 옵션이어야 합니다."</p>
</blockquote>',

		/* ─────────────────────────────  턱관절 클리닉  ───────────────────────────── */
		'턱관절-클리닉' => '
<h2>턱관절 통증·소리·교합 — 전문 진단부터</h2>
<p class="lead">아침에 입을 벌리기 힘들거나, 음식 씹을 때 턱에서 소리가 나거나, 두통이 잦으시다면 턱관절 문제일 수 있습니다. 문치과병원은 <strong>대한턱관절교합학회 이사</strong>진의 전문 진료로 통증의 원인을 정확히 찾아냅니다.</p>

<h3>전담 의료진</h3>
<ul>
<li><strong>문지현 원장</strong> — 대한턱관절교합학회 이사, 턱관절장애교육연구회 고급과정 수료</li>
<li><strong>이창률 원장</strong> — 대한턱관절교합학회 이사, 턱관절장애교육연구회 고급과정 수료</li>
</ul>

<h3>이런 증상이 있다면</h3>
<ul>
<li>입을 크게 벌릴 때 소리(딸각·뚝)가 나거나 통증이 있다</li>
<li>아침에 입이 잘 안 벌어진다 / 한쪽으로만 씹는다</li>
<li>두통·귀통증·어깨결림이 만성이다</li>
<li>이갈이·이악물기 습관이 있다 (수면 중 포함)</li>
<li>턱이 비뚤어져 보인다 / 좌우 비대칭이 신경 쓰인다</li>
</ul>

<h3>진단 흐름</h3>
<ol>
<li><strong>1차 면담</strong> — 증상 패턴·생활 습관·스트레스 등 종합 문진</li>
<li><strong>임상 검사</strong> — 개구량 측정, 관절 촉진, 교합 평가</li>
<li><strong>영상 진단</strong> — 파노라마, 필요 시 CT/MRI 의뢰</li>
<li><strong>치료 계획 수립</strong> — 보존적 치료부터 단계적 접근</li>
</ol>

<h3>치료 방법</h3>
<ul>
<li><strong>스플린트 치료</strong> — 야간 착용 장치로 관절 부담 감소</li>
<li><strong>물리치료·운동요법</strong> — 턱관절 주변 근육 이완</li>
<li><strong>교합 조정</strong> — 비정상 교합이 원인일 때</li>
<li><strong>약물 치료</strong> — 급성 통증·염증 조절</li>
<li><strong>생활 습관 코칭</strong> — 이악물기·자세 교정</li>
</ul>',

		/* ─────────────────────────────  사랑니 발치  ───────────────────────────── */
		'사랑니-발치' => '
<h2>매복 사랑니까지 — 안전한 사랑니 발치</h2>
<p class="lead">사랑니는 위치·각도·신경과의 거리에 따라 발치 난이도가 천차만별입니다. 문치과병원은 CT 정밀 분석과 구강악안면외과 진료로 완전 매복 사랑니까지 안전하게 발치합니다.</p>

<h3>왜 문치과에서 사랑니 발치를?</h3>
<ul>
<li><strong>구강악안면외과 진료</strong> — 단순 발치부터 완전 매복까지 한 곳에서</li>
<li><strong>CT 사전 분석</strong> — 하치조신경과의 거리를 미리 측정해 마비 위험 최소화</li>
<li><strong>병원급 응급 대응</strong> — 출혈·이상 반응 시 즉시 처치 가능한 시설</li>
<li><strong>야간진료 가능</strong> — 평일 20:30까지 (목요일·토요일 제외)</li>
</ul>

<h3>발치를 권하는 경우</h3>
<ul>
<li>사랑니 주변에 반복적인 염증·통증이 있는 경우</li>
<li>사랑니가 옆 어금니를 밀어 충치·잇몸병을 일으키는 경우</li>
<li>치아 위생 관리가 어려운 위치에 있는 경우</li>
<li>교정 치료를 위해 공간 확보가 필요한 경우</li>
<li>낭종(물혹) 등 병변이 발견된 경우</li>
</ul>

<h3>발치 후 주의사항</h3>
<ul>
<li>거즈는 1시간 이상 단단히 물어 지혈</li>
<li>당일 양치 금지, 다음 날부터 부드러운 칫솔질</li>
<li>뜨거운 음식·술·담배 3일간 자제</li>
<li>1주일 정도 미세한 출혈·붓기는 정상 — 심하면 즉시 내원</li>
<li>처방받은 약은 빠뜨리지 않고 복용</li>
</ul>',

		/* ─────────────────────────────  심미치료  ───────────────────────────── */
		'심미치료' => '
<h2>자연스러우면서 오래 가는 미소</h2>
<p class="lead">앞니의 작은 변화 하나가 인상 전체를 바꿉니다. 다만 심미치료는 "예뻐 보이게"가 전부가 아니라, <strong>치아 구조를 최대한 보존하면서</strong> 자연스러운 결과를 내는 것이 가장 중요합니다. 무리한 삭제·과한 보철은 권하지 않습니다.</p>

<h3>전담 의료진</h3>
<ul>
<li><strong>이수연 원장</strong> — 치과 보철과 전문의 · 통합치의학 전문의, Harvard School advanced education (치주·보철)</li>
<li><strong>이승주 원장</strong> — 대한치주과학회 · 대한보철학회 정회원</li>
</ul>

<h3>진료 분야</h3>
<ul>
<li><strong>라미네이트</strong> — 앞니 색·모양·작은 비뚤어짐을 한 번에 개선</li>
<li><strong>치아 미백</strong> — 자가미백·전문가미백 (지투·줌 등 시스템)</li>
<li><strong>올세라믹 크라운</strong> — 자연치 색감의 풀세라믹 보철</li>
<li><strong>심미 보철 재치료</strong> — 기존 보철물 색·잇몸선 불만족 시</li>
<li><strong>치은 성형술</strong> — 잇몸선 비대칭·"치아가 짧아 보이는" 경우</li>
</ul>

<h3>상담 전 알아두실 점</h3>
<ul>
<li>심미치료는 한 번 시작하면 되돌리기 어려운 경우가 많습니다 — 충분한 상담이 가장 중요합니다</li>
<li>치아 구조 보존을 위해 미백·교정으로 해결 가능한 경우는 그쪽을 먼저 권합니다</li>
<li>최종 결과는 사진·시뮬레이션으로 미리 확인해드립니다</li>
</ul>

<h3>치료 기간</h3>
<ul>
<li>치아 미백: 1~2회 내원 (전문가 미백 기준)</li>
<li>라미네이트: 2~3주 (2회 내원)</li>
<li>올세라믹 크라운: 1~2주 (1~2회 내원)</li>
</ul>',
	);

	return $map[ $slug ] ?? '';
}


/**
 * 병원소개 (about) 페이지 기본 본문
 */
function moondental_default_about_content() {
	return '
<h2>1995년부터, 천안에서</h2>
<p class="lead">한아의료재단 문치과병원은 1995년 개원 이래 천안 시민과 함께 자라온 종합 치과병원입니다. 만남로 문타워 9~13층, 5개 층 전체를 사용하며 9명의 의료진이 임플란트·교정·심미·자연치아 살리기·턱관절·구강외과까지 한 자리에서 진료합니다.</p>

<h3>저희가 약속드리는 것</h3>
<ul>
<li><strong>충분한 상담</strong> — 진료실에 들어가시기 전에, 환자분의 상황을 먼저 듣습니다</li>
<li><strong>꼭 필요한 치료만</strong> — 보존 가능한 치아는 먼저 살리는 방향으로 계획합니다</li>
<li><strong>투명한 비용</strong> — 시작 전에 옵션·기간·비용을 모두 안내드립니다</li>
<li><strong>지속적인 관리</strong> — 치료 후에도 정기 검진으로 평생 같이 갑니다</li>
</ul>

<h3>지역사회와 함께</h3>
<p>한아의료재단은 진료실 밖에서도 천안 시민과 함께해왔습니다. 대한적십자사 무료 진료봉사, 지산장학회 장학금 기부, 지역 학교 구강보건 교육 등 — 치과는 단순한 사업장이 아니라 지역사회의 일원이라는 마음으로 운영하고 있습니다.</p>

<h3>찾아오시는 길</h3>
<p>충청남도 천안시 동남구 만남로 52, 문타워 9~13층 (신부동, 문타워빌딩)<br>
대표전화 041-563-2875</p>

<p>자세한 교통·주차 안내는 <a href="/오시는-길/">오시는 길</a> 페이지를 참고해주세요.</p>';
}


/**
 * 역사 (history) 페이지용 연표 데이터.
 * Notion 페이지에서 정리한 내용을 여기에 채워주세요.
 * 가장 최신부터 과거 순으로 정렬됨 (역연대순).
 *
 * 각 항목: ['year' => '2026', 'month' => '01', 'title' => '...', 'desc' => '...']
 * month 는 선택 (비워두면 연도만 표시).
 */
function moondental_get_history() {
	// 출처: Notion 페이지 (사용자 제공). 역연대 순(최신 → 과거).
	// 'photo' 필드는 선택. assets/images/history/<파일명> 파일이 있으면 자동 표시.
	return array(

		/* ─── 2025 ─── */
		array( 'year' => '2025', 'month' => '10', 'title' => 'OKTA 글로벌 스타트업 대회', 'desc' => '청년들의 글로벌 네트워크 확장을 위한 블루문드림 홍보 활동.', 'photo' => 'history-slide-30.png' ),
		array( 'year' => '2025', 'month' => '10', 'title' => '충청남도 희망나눔 걷기대회', 'desc' => '안립청소년 꿈 지원을 위해 30여 명에게 아이패드 지원.', 'photo' => 'history-slide-29.png' ),
		array( 'year' => '2025', 'month' => '08', 'title' => '몽골 의료봉사 (IN INEED BAYSAL DENTAL CLINIC)', 'desc' => '국제 의료봉사의 가치를 되살리는 현장의 시간. 2025.08.12 – 08.15.', 'photo' => 'history-slide-28.png' ),

		/* ─── 2024 ─── */
		array( 'year' => '2024', 'month' => '09', 'title' => '실리콘밸리 직무체험단 미팅', 'desc' => '청년들의 글로벌 도전과 미래 디지털 리더 성장의 발판. 제2기 천안형 스타 디지털 인재육성 프로그램.', 'photo' => 'history-slide-27.png' ),

		/* ─── 2023 ─── */
		array( 'year' => '2023', 'month' => '10', 'title' => '제21차 세계한인비즈니스대회', 'desc' => '미국 애너하임 컨벤션센터, 한인 경제인들과 네트워킹 및 교류 활동.', 'photo' => 'history-slide-26.png' ),
		array( 'year' => '2023', 'month' => '03', 'title' => '충남학 학술대회', 'desc' => '상명대학교 천안캠퍼스 충남학 주관, 충남 발전에 대한 따뜻한 응원과 통찰의 나눔 시간.', 'photo' => 'history-slide-25.png' ),

		/* ─── 2021 ─── */
		array( 'year' => '2021', 'month' => '06', 'title' => '블루문드림과 한인상공회의소 MOU', 'desc' => '문치과병원이 지원하는 청년들을 위한 블루문드림과 한인상공회의소가 글로벌 기업 교류·협력 기반 확장.', 'photo' => 'history-slide-24.png' ),

		/* ─── 2020 ─── */
		array( 'year' => '2020', 'month' => '03', 'title' => '(사)블루문드림 청년희망의 출발점', 'desc' => '시카고 국제로타리 이사회를 마친 귀국길, 오순 UN대사·한국글로벌포스 윤상현 이사장과 함께 블루문드림 결성.', 'photo' => 'history-slide-23.png' ),

		/* ─── 2019 ─── */
		array( 'year' => '2019', 'month' => '08', 'title' => 'KBS 아침마당 출연', 'desc' => '나눔의 추억을 시작하며 시민들과 소통한 시간.', 'photo' => 'history-slide-22.png' ),
		array( 'year' => '2019', 'month' => '05', 'title' => '중국 치과의사 3개월 연수', 'desc' => '정직 임플란트 기술 실습 및 교육 — 중국 치과의사 방문.', 'photo' => 'history-slide-21.png' ),

		/* ─── 2017 ─── */
		array( 'year' => '2017', 'month' => '12', 'title' => 'TJB 열린토론 출연', 'desc' => '기부문화·나눔문화 활성화를 위한 열린토론 패널 참여.', 'photo' => 'history-slide-20.png' ),

		/* ─── 2016 ─── */
		array( 'year' => '2016', 'month' => '02', 'title' => '몽골 의료봉사활동', 'desc' => '치과 임플란트 정직기술 전수.', 'photo' => 'history-slide-19.png' ),

		/* ─── 2015 ─── */
		array( 'year' => '2015', 'month' => '05', 'title' => '몽골 대통령 우호 훈장 수상', 'desc' => '30여 년간 몽골 의료봉사 공헌으로 몽골 "나이르달 메달" 훈장 수상. 2015년 5월 4일 대통령령 제63호 명령.', 'photo' => 'history-slide-18.png' ),
		array( 'year' => '2015', 'month' => '05', 'title' => '몽골 의료봉사활동', 'desc' => '오지마을 어린이 구강진료 및 치료.', 'photo' => 'history-slide-17.png' ),

		/* ─── 2014 ─── */
		array( 'year' => '2014', 'month' => '10', 'title' => 'T-broad 방송 〈힐링코치〉', 'desc' => '임플란트에 대한 정확한 이해와 전달을 돕는 치과 멘토 역할.', 'photo' => 'history-slide-16.png' ),
		array( 'year' => '2014', 'month' => '07', 'title' => '대전 MBC 의료포커스 〈닥터인〉 자문병원 선정', 'desc' => '', 'photo' => 'history-slide-15.png' ),
		array( 'year' => '2014', 'month' => '04', 'title' => '필리핀 치과의사 연수', 'desc' => '국제로타리 지원연수회 방문 및 임플란트 연수 진행.', 'photo' => 'history-slide-14.png' ),

		/* ─── 2013 ─── */
		array( 'year' => '2013', 'month' => '11', 'title' => '대한병원장 법무부담임자협회 대통령상 수상', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2013', 'month' => '10', 'title' => 'T-broad 방송 〈힐링코치〉', 'desc' => '치아 관련 환자 정보를 알기 쉽게 전달하는 구강코치 역할.', 'photo' => 'history-slide-13.png' ),
		array( 'year' => '2013', 'month' => '06', 'title' => '기공실 CAD-CAM 장비 도입', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2013', 'month' => '03', 'title' => '충청남도 사회복지함정사 협약 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2013', 'month' => '02', 'title' => '치과의사 보수교육 기관 지정', 'desc' => '몽골·중국 해외연수 의료원.', 'photo' => '' ),

		/* ─── 2012 ─── */
		array( 'year' => '2012', 'month' => '11', 'title' => '일본 고바타 치과병원 자매결연', 'desc' => '한아의료재단 문치과병원과 일본 의료법인 소반치과의원 MOU 체결 (2012.10.27).', 'photo' => 'history-slide-12.png' ),
		array( 'year' => '2012', 'month' => '09', 'title' => '새사랑 어린이집 정직 건강증진 협약체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2012', 'month' => '07', 'title' => '한국로타리 천안서지부 자매결연', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2012', 'month' => '06', 'title' => '예방치과 개설', 'desc' => '문치과병원과 단국대학교가 공동 개발한 환자 구강관리 시스템.', 'photo' => 'history-slide-11.png' ),
		array( 'year' => '2012', 'month' => '05', 'title' => '충청대학교·선문대학교 산학협력 협약', 'desc' => '', 'photo' => '' ),

		/* ─── 2011 ─── */
		array( 'year' => '2011', 'month' => '11', 'title' => '천안 초등학생 자매결연', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2011', 'month' => '03', 'title' => '마취통증의학과 개설', 'desc' => '', 'photo' => '' ),

		/* ─── 2010 ─── */
		array( 'year' => '2010', 'month' => '12', 'title' => '문치과병원 병관 신년 카드 가수 청, 충원종합관리 자매결연 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2010', 'month' => '09', 'title' => '자역시 점핑풀그리/롭스 자매결연 협약', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2010', 'month' => '08', 'title' => '치과의사 전공의 인턴·레지던트 수련병원 지정', 'desc' => '자전왈량증세 의사장·일성알 자매결연 체결.', 'photo' => '' ),
		array( 'year' => '2010', 'month' => '06', 'title' => '(주)글로벌 자매결연', 'desc' => '', 'photo' => '' ),

		/* ─── 2009 ─── */
		array( 'year' => '2009', 'month' => '12', 'title' => '동서대학교 자매결연 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2009', 'month' => '09', 'title' => '단국대학교 정치경영대학원 고위관리자과정 이둥창학 자매결연', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2009', 'month' => '08', 'title' => '치과의사 전공의 인턴·레지던트 수련병원 지정', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2009', 'month' => '02', 'title' => '단국대학교 천안한방병원 한방 자문의사 위촉', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2009', 'month' => '01', 'title' => '어린성지병원 자문의사 위촉', 'desc' => '', 'photo' => '' ),

		/* ─── 2008 ─── */
		array( 'year' => '2008', 'month' => '11', 'title' => '문치과병원 & 삼성서울병원 치과부 위장 학술세미나 주최', 'desc' => '한양대 문화의료실에서 진행.', 'photo' => '' ),
		array( 'year' => '2008', 'month' => '10', 'title' => '2009 치과의사 전공의 인턴·레지던트 수련병원 지정 (보건복지부)', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2008', 'month' => '08', 'title' => '해양경찰학교 자매결연 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2008', 'month' => '07', 'title' => '대표병원장 국제로타리 3620지구 이사 취임', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2008', 'month' => '04', 'title' => '인사정 자매결연 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2008', 'month' => '03', 'title' => '장애인 의료봉사 (사랑의 집) 시작', 'desc' => '', 'photo' => '' ),

		/* ─── 2007 ─── */
		array( 'year' => '2007', 'month' => '10', 'title' => '2008 치과의사 전공의 인턴 수련기관 지정 (보건복지부)', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2007', 'month' => '09', 'title' => '의료봉사 차량 이행 (25인치 버스)', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2007', 'month' => '06', 'title' => '외국인 노동자 무료 진료 시작 · 공주대학교 자매결연 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2007', 'month' => '01', 'title' => '문치과 사기 화재 이전', 'desc' => '', 'photo' => '' ),

		/* ─── 2006 ─── */
		array( 'year' => '2006', 'month' => '05', 'title' => '남서울대 자매결연 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2006', 'month' => '04', 'title' => '서울 삼성서울병원 협진병원 지정', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2006', 'month' => '01', 'title' => '치과의사 전공의 인턴 수련기관 지정 (보건복지부)', 'desc' => '치과의사 전공의 및 인턴 수련기관 인증.', 'photo' => 'history-slide-10.png' ),

		/* ─── 2005 ─── */
		array( 'year' => '2005', 'month' => '11', 'title' => '갤러리아 백화점 천안점과 전략적 제휴 체결', 'desc' => '〈한방의 통〉 갤러리아 백화점 천안점 전략 제휴.', 'photo' => '' ),
		array( 'year' => '2005', 'month' => '05', 'title' => '호서대학교·청양대학교·백석대학 산학협력 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2005', 'month' => '04', 'title' => '단원 보건대학 산학협력 체결', 'desc' => '', 'photo' => '' ),

		/* ─── 2004 ─── */
		array( 'year' => '2004', 'month' => '11', 'title' => '의료법인 한아의료재단 문치과병원 본원 신축사옥 준공·이전', 'desc' => '신부동 문타워 5개 층, UNIT CHAIR 42대 규모.', 'photo' => 'history-slide-09.png' ),
		array( 'year' => '2004', 'month' => '09', 'title' => '연암대 비서학과 산학협력 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2004', 'month' => '08', 'title' => '한양대학교 치위생과 산학협력 체결', 'desc' => '', 'photo' => '' ),
		array( 'year' => '2004', 'month' => '04', 'title' => '중국 연길 의사 치과의사 2차 연수교육 (1명)', 'desc' => '', 'photo' => '' ),

		/* ─── 2003 ─── */
		array( 'year' => '2003', 'month' => '07', 'title' => '중국 두만 구강병원·정공치과 병원 자매결연', 'desc' => '무료 진료 실시 — 임플란트 매식 1차 46개, 2차 30개.', 'photo' => '' ),
		array( 'year' => '2003', 'month' => '05', 'title' => '중국 연길 의사 치과의사 1차 연수교육 (6명)', 'desc' => '', 'photo' => '' ),

		/* ─── 2002 ─── */
		array( 'year' => '2002', 'month' => '11', 'title' => '의료법인 한아의료재단 설립 · 제2분원 양용문치과 개원', 'desc' => 'UNIT CHAIR 15대 의사 12명, 직원 50명.', 'photo' => '' ),
		array( 'year' => '2002', 'month' => '08', 'title' => '성정 정신 병원 무료 진료 시작', 'desc' => '', 'photo' => '' ),

		/* ─── 2001 ─── */
		array( 'year' => '2001', 'month' => '10', 'title' => '문치과 병원으로 승급', 'desc' => 'UNIT CHAIR 9대 의사 6명, 직원 33명.', 'photo' => '' ),

		/* ─── 2000 ─── */
		array( 'year' => '2000', 'month' => '08', 'title' => '단국대학교 몽골 의료봉사 체어 기증', 'desc' => '몽골 의료봉사 기반 마련을 위한 진료체어 기증. Dental Service in Mongolia / Dental College of Dankook University.', 'photo' => 'history-slide-07.png' ),
		array( 'year' => '2000', 'month' => '07', 'title' => '연길 둔문 구강병원·정공치과 병원 자매결연', 'desc' => '무료 진료 및 무료 임플란트 시술.', 'photo' => 'history-slide-08.png' ),
		array( 'year' => '2000', 'month' => '03', 'title' => '제2기 한아임플란트·보철연구소 수료식', 'desc' => '전문 임상 역량 토대를 구축한 연구과정.', 'photo' => 'history-slide-06.png' ),
		array( 'year' => '2000', 'month' => '02', 'title' => '임플란트 심포지엄 개최 및 제2기 연수회', 'desc' => 'John Beumer, Thomas J. Han 교수 초청 · 800명 참석. Intercontinental Hotel Grand Ballroom.', 'photo' => 'history-slide-05.png' ),

		/* ─── 1999 ─── */
		array( 'year' => '1999', 'month' => '10', 'title' => '제2기 한아임플란트·보철연구소 연수생 15명', 'desc' => '', 'photo' => '' ),
		array( 'year' => '1999', 'month' => '08', 'title' => 'NEW MILLENIUM 심포지엄 개최', 'desc' => '고려인사 기념관, 600명 참석.', 'photo' => '' ),
		array( 'year' => '1999', 'month' => '08', 'title' => '미국 LA, WPDI 자매결연', 'desc' => '대표 THOMAS J. HAN. Thomas J. Han 교수 참석.', 'photo' => 'history-slide-04.png' ),
		array( 'year' => '1999', 'month' => '02', 'title' => '임플란트 임상 세미나 개최 및 제1기 연수생 수료식', 'desc' => '대전과 서울에서 진행.', 'photo' => '' ),

		/* ─── 1998 ─── */
		array( 'year' => '1998', 'month' => '04', 'title' => '한아 임플란트 보철 연구소 설립', 'desc' => '연구소 설립 및 임플란트 임상 세미나 개최 (300명 참석). 제1기 사미·보철 연수 16명, 임플란트 보철 연수 16명.', 'photo' => 'history-slide-03.png' ),
		array( 'year' => '1998', 'month' => '04', 'title' => '성정 정신 병원 무료 진료 시작', 'desc' => '', 'photo' => '' ),
		array( 'year' => '1998', 'month' => '01', 'title' => '문은수 치과의원 확장', 'desc' => 'UNIT CHAIR 9대, 의사 3명, 직원 10명.', 'photo' => 'history-slide-02.png' ),

		/* ─── 1997 ─── */
		array( 'year' => '1997', 'month' => '11', 'title' => '천안 개방 교도소 무료 진료 시작', 'desc' => '', 'photo' => '' ),

		/* ─── 1996 ─── */
		array( 'year' => '1996', 'month' => '01', 'title' => '스웨덴 NOBEL BIOCARE 자매결연', 'desc' => '', 'photo' => '' ),

		/* ─── 1995 ─── */
		array( 'year' => '1995', 'month' => '04', 'title' => '문은수 치과의원 개원', 'desc' => '신부동 461-5 정진빌딩 3F, 의사 1명, 직원 4명.', 'photo' => 'history-slide-01.png' ),
	);
}


/**
 * 역사 페이지 도입부 (연표 위 인사말)
 */
function moondental_default_history_intro() {
	return '
<h2>1995년부터, 천안과 함께</h2>
<p class="lead">한아의료재단 문치과병원은 1995년 천안에서 첫 진료를 시작한 이래, 한 분의 환자를 가족처럼 오래 보아오며 지역사회와 함께 자라왔습니다. 진료실 안의 임상 노력만큼이나 진료실 밖의 봉사와 나눔도 저희 정체성의 한 부분입니다.</p>';
}


/**
 * 의료진 (doctors) 페이지 기본 본문
 */
function moondental_default_doctors_content() {
	return '
<h2>9명의 의료진이 함께합니다</h2>
<p class="lead">한 분의 환자를 평생 보기 위해서는, 한 분의 원장만으로는 부족합니다. 문치과병원에는 <strong>구강악안면외과·보철·교정·치주·보존·턱관절·통합치의학</strong> 등 각 분야의 전문 의료진이 함께 있어 어떤 진료가 필요하더라도 한 곳에서 일관된 진료를 받으실 수 있습니다.</p>

<h3>층별 전문 센터</h3>
<ul>
<li><strong>9F 종합진료센터</strong> — 보존과 전문의, 보철과·통합치의학 전문의, 치주·보철 진료</li>
<li><strong>10F 임플란트센터</strong> — UCSF/UPENN 연수, 디지털 임플란트 식립과 보철 통합 진료</li>
<li><strong>11F 교정과·종합진료센터</strong> — 치과교정과 전문의·인정의, 투명교정 전담</li>
</ul>

<h3>주요 학위·자격</h3>
<ul>
<li>치과 보존과 전문의 (보건복지부 인증)</li>
<li>치과 보철과 전문의 · 통합치의학 전문의</li>
<li>치과 교정과 전문의·인정의</li>
<li>대한치주과학회 인정의</li>
<li>미국 UCLA·UCSF·UPENN·Harvard 정규 과정 수료</li>
<li>미국 캘리포니아 치과의사 협회(CDA), 미국 치과의사 협회(ADA) 정회원</li>
<li>대한구강악안면임플란트학회·대한턱관절교합학회 이사진</li>
</ul>';
}


/**
 * 기술력 / 시설 페이지 기본 본문
 */
function moondental_default_facility_content() {
	return '
<h2>정밀한 진단, 신뢰할 수 있는 진료를 위한 시설</h2>
<p class="lead">한아의료재단 문치과병원은 만남로 문타워 9~13층, 총 <strong>5개 층 전체</strong>를 사용하는 종합 치과병원입니다. 진단·임플란트·교정·종합진료 영역을 층별 전문 센터로 분리해 환자분이 필요한 진료를 가장 깊이 있게 받으실 수 있도록 설계했습니다.</p>

<h3>층별 전문 센터</h3>
<ul>
<li><strong>9F 종합진료센터</strong> — 보존·치주·보철 통합 진료, 상담실</li>
<li><strong>10F 임플란트센터</strong> — CT·디지털 가이드 식립 수술실, 멸균실</li>
<li><strong>11F 교정과 · 종합진료센터</strong> — 투명교정 전담, 일반 교정, 상담실</li>
<li><strong>12~13F</strong> — 데스크·라운지·환자 휴게 공간, 보철 기공 협진실</li>
</ul>

<h3>디지털 진단 장비</h3>
<ul>
<li><strong>3D CT (Cone-Beam CT)</strong> — 임플란트 식립 전 골밀도·신경 위치를 mm 단위로 분석</li>
<li><strong>디지털 파노라마 X-ray</strong> — 저선량 촬영, 즉시 판독</li>
<li><strong>구강 내 디지털 스캐너</strong> — 본뜨기 없이 보철·교정 모형 디지털화</li>
<li><strong>세팔로 분석 (교정용)</strong> — 골격 분석을 통한 정밀 교정 계획</li>
</ul>

<h3>임플란트 디지털 가이드 시스템</h3>
<ul>
<li>CT 데이터 기반 컴퓨터 시뮬레이션 식립</li>
<li>3D 프린팅 가이드를 이용한 정확도 ±0.5mm 식립</li>
<li>즉시 임플란트 / 즉시 부하 케이스 대응</li>
<li>네비게이션 가이드 수술로 신경 손상 위험 최소화</li>
</ul>

<h3>멸균 · 감염관리</h3>
<ul>
<li><strong>핸드피스 1인 1세트</strong> — 환자마다 멸균된 새 핸드피스 사용</li>
<li><strong>고압증기 멸균 (Autoclave)</strong> — 진료 기구는 모두 멸균 후 사용</li>
<li><strong>일회용 기구 적극 사용</strong> — 주사기·바늘·석션팁 등</li>
<li><strong>진료실 공기 관리</strong> — 헤파 필터 환기, 진료실간 격리</li>
<li><strong>의료폐기물 별도 처리</strong> — 의료법 기준 엄격 준수</li>
</ul>

<h3>환자 편의시설</h3>
<ul>
<li>건물 자체 주차장 (진료 시 무료)</li>
<li>전용 엘리베이터로 각 층 진료센터 접근</li>
<li>어린이 동반 환자를 위한 라운지</li>
<li>휠체어·유모차 접근 가능 (배리어프리)</li>
<li>야간진료 운영 (평일 ~20:30, 목·토 제외)</li>
</ul>';
}


/**
 * 임상 케이스 페이지 기본 본문
 */
function moondental_default_cases_content() {
	$info = moondental_get_info();
	return '
<h2>30년 임상 경험으로 쌓아온 케이스</h2>
<p class="lead">문치과병원은 1995년 개원 이래 임플란트·교정·심미·보존 등 다양한 임상 케이스를 축적해왔습니다. 각 분야 전문 의료진의 협진으로 단일 치료를 넘어 환자분의 평생 구강 건강을 설계합니다.</p>

<h3>주요 진료 영역별 케이스</h3>
<ul>
<li><strong>임플란트 케이스</strong> — 단일·다수·전악 식립, 골이식 동반 케이스, All-on-4/6</li>
<li><strong>교정 케이스</strong> — 투명교정·설측·소아·성인 종합 교정</li>
<li><strong>심미 케이스</strong> — 라미네이트·올세라믹·미백 통합 디자인</li>
<li><strong>보존 (자연치 살리기) 케이스</strong> — 재근관 치료, 치근단 수술 후 보존 사례</li>
<li><strong>턱관절 케이스</strong> — 스플린트·교합 조정 통합 치료</li>
<li><strong>전악 통합 치료</strong> — 발치·임플란트·교정·보철을 통합 계획한 풀마우스 케이스</li>
</ul>

<blockquote>
<p>실제 환자분의 진료 전·후 사진은 <strong>환자분의 명시적 동의</strong>를 받은 경우에 한해 공개합니다. 의료법상 비교 사진은 정확한 정보 전달과 동시에 환자분의 사생활을 함께 보호해야 하므로, 상세 케이스 사진은 내원하시어 직접 상담 시 보여드리는 것을 원칙으로 합니다.</p>
</blockquote>

<h3>임상 사례 미리 보기</h3>
<p>저희 병원의 일상과 진료 모습은 다음 채널에서 확인하실 수 있습니다:</p>
<ul>
' . ( $info['instagram'] ? '<li><a href="' . esc_url( $info['instagram'] ) . '" target="_blank" rel="noopener">인스타그램</a> — 진료실 일상, 직원 소식</li>' : '' ) . '
' . ( $info['blog_url']  ? '<li><a href="' . esc_url( $info['blog_url']  ) . '" target="_blank" rel="noopener">네이버 블로그</a> — 진료 후기, 치과 상식</li>' : '' ) . '
' . ( $info['facebook_url'] ? '<li><a href="' . esc_url( $info['facebook_url'] ) . '" target="_blank" rel="noopener">페이스북</a> — 병원 소식</li>' : '' ) . '
</ul>

<h3>상담을 원하신다면</h3>
<p>본인 케이스와 유사한 임상 사례를 직접 보고 싶으시다면 부담 없이 내원해주세요. 사전 상담 시 비슷한 케이스의 치료 과정·결과를 자세히 설명드립니다.</p>
<p>📞 <a href="tel:' . esc_attr( $info['phone_link'] ) . '"><strong>' . esc_html( $info['phone'] ) . '</strong></a>로 전화 주시거나, <a href="' . esc_url( $info['kakao_url'] ) . '" target="_blank" rel="noopener">카카오톡 상담</a>으로 편하게 문의 가능합니다.</p>';
}


/**
 * 진료항목 부모 페이지 (6개 진료 영역 개요)
 */
function moondental_default_services_overview_content() {
	$services = moondental_get_services();

	$html  = '<h2>한 곳에서, 평생 치아 건강을</h2>';
	$html .= '<p class="lead">문치과병원은 1995년부터 천안에서, 한 분의 환자를 평생 보아온 종합 치과병원입니다. 임플란트·교정부터 자연치 보존·심미·턱관절까지 — 어떤 진료가 필요하시더라도 한 자리에서 일관된 진료를 받으실 수 있습니다.</p>';

	$html .= '<h3>진료 영역</h3>';
	$html .= '<ul>';
	foreach ( $services as $svc ) {
		$page = get_page_by_path( $svc['slug'] );
		$url  = $page ? get_permalink( $page ) : home_url( '/' . rawurlencode( $svc['slug'] ) . '/' );
		$html .= sprintf(
			'<li>%s <strong><a href="%s">%s</a></strong> — %s</li>',
			$svc['icon'],
			esc_url( $url ),
			esc_html( $svc['title'] ),
			esc_html( $svc['desc'] )
		);
	}
	$html .= '</ul>';

	$html .= '<h3>층별 전문 센터</h3>';
	$html .= '<ul>
<li><strong>9F 종합진료센터</strong> — 일반진료·자연치아 살리기·심미 통합 진료</li>
<li><strong>10F 임플란트센터</strong> — 단일·다수·전악 임플란트, 디지털 가이드 식립</li>
<li><strong>11F 교정과 · 종합진료센터</strong> — 투명교정 전담, 사랑니 발치, 턱관절 클리닉</li>
</ul>';

	$html .= '<h3>저희가 가장 신경 쓰는 것</h3>';
	$html .= '<ul>
<li><strong>충분한 상담</strong> — 진료실에 들어가시기 전에 환자분의 상황을 먼저 듣습니다</li>
<li><strong>가장 보존적인 치료부터</strong> — 발치보다 자연치 보존, 임플란트보다 자연치 살리기를 먼저 검토합니다</li>
<li><strong>투명한 비용 안내</strong> — 시작 전에 옵션·기간·비용을 모두 안내드립니다</li>
<li><strong>의료진 협진</strong> — 9명의 전문 의료진이 한 케이스를 다각도로 검토합니다</li>
</ul>';

	return $html;
}


/**
 * 오시는 길 (location) 페이지 기본 본문
 */
function moondental_default_location_content() {
	$info = moondental_get_info();
	$map_embed = $info['map_embed'] ?? '';
	$map_url   = ! empty( $info['naver_map_url'] ) ? $info['naver_map_url'] : ( $info['naver_place'] ?? '' );
	$addr      = $info['address_road'] ?: $info['address'];

	$html  = '<h2>천안 만남로 문타워 9~13층</h2>';
	$html .= '<p class="lead">' . esc_html( $info['address'] ) . '<br>대표전화 <a href="tel:' . esc_attr( $info['phone_link'] ) . '">' . esc_html( $info['phone'] ) . '</a></p>';

	if ( $map_embed ) {
		// 관리자가 직접 임베드 코드를 넣은 경우 (Naver/Kakao API 키로 받은 iframe)
		$html .= '<div class="md-map-embed">' . $map_embed . '</div>';
	} elseif ( $map_url ) {
		// 클릭 가능한 지도 미리보기 카드 — 네이버 지도/플레이스로 새 탭 연결
		$html .= sprintf(
			'<a class="md-map-card" href="%1$s" target="_blank" rel="noopener" aria-label="네이버 지도에서 위치 보기">
				<div class="md-map-card__pattern" aria-hidden="true"></div>
				<div class="md-map-card__badge">NAVER MAP</div>
				<div class="md-map-card__pin" aria-hidden="true">
					<svg viewBox="0 0 32 44" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M16 0C7.16 0 0 7.16 0 16c0 12 16 28 16 28s16-16 16-28C32 7.16 24.84 0 16 0z" fill="currentColor"/>
						<circle cx="16" cy="16" r="6" fill="#fff"/>
					</svg>
				</div>
				<div class="md-map-card__info">
					<h3 class="md-map-card__title">%2$s</h3>
					<p class="md-map-card__addr">%3$s</p>
					<span class="md-map-card__cta">네이버 지도에서 보기 <span aria-hidden="true">→</span></span>
				</div>
			</a>',
			esc_url( $map_url ),
			esc_html( $info['name_short'] ),
			esc_html( $addr )
		);
	}

	$html .= '
<h3>🚗 자가용</h3>
<ul>
<li>경부고속도로 천안IC에서 약 10분</li>
<li>건물 자체 주차장 이용 가능 (진료시 무료)</li>
</ul>

<h3>🚌 대중교통</h3>
<ul>
<li>천안종합버스터미널 / 천안고속버스터미널에서 도보 약 5분</li>
<li>신부동 일대 시내버스 다수 정차</li>
</ul>

<h3>🚆 KTX/SRT</h3>
<ul>
<li>천안아산역에서 천안시내 방향 시내버스 또는 택시 약 15분</li>
<li>천안역에서 시내버스 또는 택시 약 10분</li>
</ul>

<h3>진료시간 안내</h3>
<ul>
<li><strong>평일</strong> 09:00 – 20:30 (점심시간 없음, 야간진료 운영)</li>
<li><strong>목요일</strong> 09:00 – 18:00 (야간진료 없음)</li>
<li><strong>토요일</strong> 09:00 – 14:00</li>
<li><strong>일요일·공휴일</strong> 휴진</li>
</ul>';

	return $html;
}
