<?php
/**
 * Moon Dental · 치과사전 대량 용어 시드 (200+ 개)
 *
 *  v3.35.1 (사용자 요청 · SEO 롱테일 · 검색어 최대한 커버)
 *  마이그레이션은 중복 제목 스킵 · 새 용어만 추가.
 *  향후 wp-admin → 📖 치과사전에서 자유롭게 편집·확장.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 대량 시드용 용어 데이터.
 * 형식: title | cat_slug | excerpt(1문장) | body(HTML)
 */
function moondental_encyclopedia_seed_data() {
	return array(

		/* ==================== 임플란트 (50+) ==================== */
		array( '임플란트 픽스처', 'implant', '잇몸뼈에 식립되는 티타늄 인공 치근', '<p>임플란트 픽스처는 <strong>티타늄 소재의 인공 치근</strong>으로 잇몸뼈에 식립되어 자연 치아의 뿌리 역할을 합니다. 형태·표면처리·크기가 다양하며 환자의 골 상태에 맞춰 선택합니다.</p>' ),
		array( '임플란트 크라운', 'implant', '지대주 위에 장착되는 최종 인공 치아', '<p>임플란트 크라운은 지대주(어버트먼트) 위에 시멘트 접착 또는 나사 고정 방식으로 장착되는 <strong>최종 보철물</strong>입니다. 지르코니아·PFM·올세라믹·금 등 재료가 다양합니다.</p>' ),
		array( '즉시 임플란트', 'implant', '발치 당일 임플란트를 식립하는 시술', '<p>즉시 임플란트는 <strong>발치와 임플란트 식립을 하루에 진행</strong>하는 방식입니다. 치료 기간을 획기적으로 단축하지만 뼈·잇몸 상태가 안정적이어야 가능합니다.</p>' ),
		array( '발치즉시 임플란트', 'implant', '발치 부위에 곧바로 임플란트 식립', '<p>발치즉시 임플란트는 치아를 뽑은 자리에 <strong>바로 임플란트를 식립</strong>하는 술식입니다. 발치와·골 형태에 맞춰 특수 픽스처를 사용합니다.</p>' ),
		array( '2차 임플란트 수술', 'implant', '골유착 후 임시 지대주를 노출시키는 수술', '<p>2차 수술은 1차 식립 후 <strong>골유착이 완료된 임플란트 위 잇몸을 열어</strong> 힐링 어버트먼트를 연결하는 간단한 시술입니다.</p>' ),
		array( '네비게이션 임플란트', 'implant', '3D CBCT로 시뮬레이션한 가이드로 정밀 식립', '<p>3D CBCT 데이터를 바탕으로 <strong>시술 가이드(스텐트)</strong>를 미리 제작해 정확한 위치·각도·깊이로 식립합니다. R2GATE 등 시스템 사용, 오차 ±0.5mm.</p>' ),
		array( 'All-on-4', 'implant', '치아 전체를 잃은 경우 4개 임플란트로 전악 회복', '<p>All-on-4는 상악 또는 하악 전체 무치악 환자에게 <strong>4~6개 임플란트</strong>로 12~14개 인공 치아를 지탱하는 술식입니다. 골이식 최소화, 즉시 하중 가능.</p>' ),
		array( '임플란트 오버덴처', 'implant', '임플란트를 이용한 고정식 틀니', '<p>임플란트 2~4개를 심고 그 위에 <strong>탈부착 가능한 틀니</strong>를 장착하는 방식입니다. 일반 틀니보다 훨씬 안정적이며 치조골 흡수를 예방합니다.</p>' ),
		array( '미니 임플란트', 'implant', '지름 3mm 이하의 소형 임플란트', '<p>미니 임플란트는 지름 2~3mm의 소형 임플란트로 <strong>골량이 부족하거나 좁은 부위</strong>에 사용합니다. 주로 틀니 고정용으로 활용됩니다.</p>' ),
		array( '상악동 거상술', 'implant', '위턱 뼈가 부족할 때 상악동 바닥을 올려 골이식하는 수술', '<p>위턱 어금니 부위 임플란트 식립 시 <strong>상악동(공기주머니) 바닥을 들어올려</strong> 인공 뼈를 이식합니다. 치조정 접근법(오스테오톰)과 측방 접근법(윈도우 오프닝) 두 가지가 있습니다.</p>' ),
		array( '골이식술', 'implant', '부족한 잇몸뼈를 인공/자가 뼈로 보강', '<p>골이식은 임플란트 식립을 위해 부족한 뼈를 <strong>자가골·동종골·이종골·합성골</strong>로 보강하는 술식입니다. PDRN·PRF와 함께 사용하면 재생 속도가 빨라집니다.</p>' ),
		array( 'GBR (골유도재생술)', 'implant', '차폐막으로 골재생을 유도하는 술식', '<p>GBR은 골이식재 위에 <strong>차폐막(Membrane)</strong>을 덮어 연조직 침투를 막고 골세포만 자라도록 유도하는 술식입니다. 흡수성·비흡수성 차폐막 사용.</p>' ),
		array( 'PRF (자가혈 응고막)', 'implant', '환자 혈액으로 만든 재생 촉진 막', '<p>PRF(Platelet-Rich Fibrin)는 환자의 혈액을 원심분리해 얻은 <strong>혈소판 농축 피브린 막</strong>입니다. 골재생·상처 치유 속도를 향상시킵니다.</p>' ),
		array( 'PRP (혈소판 농축액)', 'implant', '성장인자 풍부한 자가혈 농축액', '<p>PRP는 자가혈에서 추출한 <strong>혈소판 농축액</strong>으로 성장인자가 풍부해 연조직·골재생을 촉진합니다. 알레르기·거부반응 없음.</p>' ),
		array( 'PDRN', 'implant', '연어 DNA 단편으로 조직 재생 촉진', '<p>PDRN(Polydeoxyribonucleotide)은 연어 정소 DNA를 분해한 <strong>조직 재생 촉진제</strong>로 임플란트 부위 붓기·통증 감소, 골유착 가속에 활용됩니다.</p>' ),
		array( '골유착 (Osseointegration)', 'implant', '임플란트와 잇몸뼈가 세포 수준에서 결합되는 과정', '<p>브레네막(Branemark)이 발견한 현상으로, 티타늄 표면과 뼈세포가 <strong>직접 결합</strong>하는 것을 말합니다. 3~6개월 소요.</p>' ),
		array( 'SLA 표면처리', 'implant', '샌드블라스팅 + 산부식으로 임플란트 표면 거칠기 확보', '<p>SLA(Sandblasted, Large-grit, Acid-etched)는 임플란트 표면에 <strong>미세한 요철</strong>을 만들어 골유착 표면적을 극대화하는 표준 처리법입니다.</p>' ),
		array( 'UV 표면처리', 'implant', '자외선 조사로 초친수성 활성화 → 골유착 가속', '<p>UV 처리 임플란트는 <strong>표면에 자외선을 조사</strong>해 초친수성(super-hydrophilic) 상태로 만듭니다. 뼈세포 부착이 빨라져 골유착 기간이 단축됩니다.</p>' ),
		array( '칼슘 이온 표면처리', 'implant', '표면에 칼슘 이온 흡착 → 뼈와 화학적 결합', '<p>MEGAGEN 블루다이아몬드 등에 적용된 기술로 임플란트 표면에 <strong>칼슘 이온</strong>을 융합시켜 뼈와 화학적으로 결합, 골유착 속도를 크게 향상시킵니다.</p>' ),
		array( '지르코니아 임플란트', 'implant', '금속 알레르기 환자용 세라믹 소재 임플란트', '<p>티타늄 대신 <strong>지르코니아(산화 지르코늄)</strong>로 만든 임플란트. 금속 알레르기 환자·앞니 심미 부위에 사용됩니다.</p>' ),
		array( '임플란트 주위염', 'implant', '임플란트 주변 잇몸·뼈에 생긴 염증', '<p>임플란트 주위염은 청결 관리 부족·과부하로 <strong>임플란트 주변 잇몸과 뼈에 염증</strong>이 진행되는 상태입니다. 조기 발견·치료가 임플란트 수명을 결정합니다.</p>' ),
		array( '임플란트 실패 원인', 'implant', '골유착 실패·감염·과부하·흡연 등', '<p>주요 원인: <strong>초기 골유착 실패</strong>·감염·과부하(교합)·흡연·당뇨 등 전신질환·구강위생 관리 부족. 정기 검진으로 조기 발견 가능.</p>' ),
		array( '나사 풀림', 'implant', '보철 지지 나사가 반복 저작으로 느슨해지는 현상', '<p>임플란트 크라운 지지 나사가 반복 저작 스트레스로 <strong>느슨해지는 현상</strong>. 재조임으로 간단히 해결되지만 반복되면 나사 파절 위험이 있습니다.</p>' ),
		array( '힐링 어버트먼트', 'implant', '2차 수술 시 잇몸 모양을 잡아주는 임시 지대주', '<p>2차 수술 후 최종 보철 전까지 <strong>잇몸 형태를 유지</strong>하는 원통형 부품. 1~2주 착용 후 최종 지대주로 교체.</p>' ),
		array( '커버 스크류', 'implant', '1차 수술 후 임플란트 상부를 덮는 나사', '<p>임플란트 식립 후 <strong>상부를 덮어 골유착 기간 동안 보호</strong>하는 부품입니다. 2차 수술 시 제거 후 힐링 어버트먼트로 교체.</p>' ),
		array( '스크류 타입', 'implant', '나사로 고정하는 임플란트 보철 방식', '<p>임플란트 크라운을 <strong>나사로 지대주에 고정</strong>. 시멘트 잔여물 우려 없고, 필요 시 크라운 탈착이 쉬움.</p>' ),
		array( '시멘트 타입', 'implant', '치과용 시멘트로 크라운을 고정하는 방식', '<p>지대주 위에 <strong>치과용 접착 시멘트</strong>로 크라운을 붙이는 방식. 앞니 심미부위에 적합하지만 시멘트 잔여물 관리에 주의 필요.</p>' ),
		array( '즉시 하중 임플란트', 'implant', '식립 당일 임시 보철을 장착하는 방식', '<p>Immediate Loading. 임플란트 식립 후 <strong>당일 또는 며칠 내</strong> 임시 크라운·틀니를 장착. 골질과 초기 고정력이 충분해야 가능.</p>' ),
		array( '치조제 보존술', 'implant', '발치 후 잇몸뼈 흡수를 최소화하는 술식', '<p>ARP(Alveolar Ridge Preservation). 발치 즉시 발치와에 <strong>골이식재를 채워</strong> 잇몸뼈 흡수를 최소화. 향후 임플란트를 위한 뼈 조건을 확보.</p>' ),
		array( '고정성 임플란트', 'implant', '제거할 수 없는 나사·접착 방식 보철', '<p>환자가 임의로 뺄 수 없는 <strong>영구 고정 방식</strong>. 저작 효율이 자연치에 근접합니다.</p>' ),
		array( '가철성 임플란트', 'implant', '착탈 가능한 임플란트 지지 틀니', '<p>임플란트 지지 오버덴처처럼 환자가 <strong>착탈 가능한</strong> 형태. 청소 편리, 다수 결손 시 경제적.</p>' ),
		array( 'ITI/스트라우만', 'implant', '스위스의 대표 임플란트 브랜드', '<p>Straumann/ITI는 스위스 제조 <strong>글로벌 최상위 임플란트</strong>. SLA·SLActive 표면 · 예측 가능한 골유착 · 30년+ 임상 데이터.</p>' ),
		array( '오스템 임플란트', 'implant', '국내 최대 규모 임플란트 제조사', '<p>오스템(Osstem)은 국내 대표 임플란트 브랜드. <strong>TS(중저가)·KS(프리미엄)</strong> 라인. 문치과병원은 KS 프리미엄 라인 사용.</p>' ),
		array( '메가젠 임플란트', 'implant', '국산 프리미엄 임플란트 · 블루다이아몬드', '<p>MEGAGEN은 <strong>블루다이아몬드·ARi·BD Cuff·AnyRidge</strong> 등 프리미엄 라인을 보유. 칼슘 이온 표면처리로 골유착 우수.</p>' ),
		array( '스테리오스 임플란트', 'implant', '테이퍼드 디자인의 프리미엄 임플란트', '<p>스테리오스(Stereos)는 <strong>테이퍼드 디자인</strong>으로 초기 고정력 우수, 잔존골 3mm에도 식립 가능. 골량 부족 케이스에 유리.</p>' ),
		array( '포인트 임플란트', 'implant', 'UV 표면처리로 초친수성 활성화', '<p>포인트(Point)는 <strong>UV 표면처리</strong>로 초친수성을 활성화해 골유착 속도를 향상시킨 국내 브랜드입니다.</p>' ),
		array( '맞춤형 지대주', 'implant', '환자별 개별 설계된 커스텀 어버트먼트', '<p>기성 지대주가 아닌 <strong>환자의 잇몸·치아 형태에 맞춘</strong> 개별 지대주. 적합도·심미성이 우수합니다. Custom Abutment.</p>' ),
		array( '기성 지대주', 'implant', '표준 규격으로 제작된 어버트먼트', '<p>공장에서 <strong>표준 규격으로 제작</strong>된 지대주. 저렴하고 즉시 사용 가능하지만 적합도가 커스텀보다 떨어짐.</p>' ),
		array( '앵글드 어버트먼트', 'implant', '각도 조절이 필요한 부위에 사용하는 지대주', '<p>임플란트 식립 각도와 <strong>최종 보철 방향이 다를 때</strong> 사용하는 각도 지대주. 15°·20°·25°·30° 등.</p>' ),
		array( '스트레이트 어버트먼트', 'implant', '수직 방향의 표준 지대주', '<p>임플란트 축과 동일한 방향의 <strong>직선형 지대주</strong>. 가장 흔한 형태.</p>' ),
		array( '디지털 인상', 'implant', '구강 스캐너로 정밀 3D 본을 뜨는 방식', '<p>구강 스캐너(iTero, TRIOS, PrimeScan)로 <strong>3D 디지털 본</strong>을 뜨는 방식. 인상재 없이 정확한 데이터 확보, 보철 제작 속도 향상.</p>' ),
		array( '전악 임플란트', 'implant', '위·아래턱 모두 잃은 경우 전체 회복', '<p>Full-arch implant. 상하악 전체 치아를 잃은 경우 <strong>양악 12~14개 이상의 임플란트</strong>로 완전한 저작 기능 회복.</p>' ),
		array( '임플란트 수명', 'implant', '평균 10~20년, 관리에 따라 평생 유지 가능', '<p>임플란트는 관리에 따라 <strong>10~20년 이상</strong> 유지 가능. 5년 성공률 95%+, 10년 성공률 90%+. 정기 검진·구강위생·금연이 핵심.</p>' ),
		array( '건강보험 임플란트', 'implant', '만 65세 이상 평생 2개 보험 적용', '<p>만 65세 이상 건강보험 가입자에게 <strong>평생 2개 임플란트</strong> 보험 적용(본인부담 약 30%). PFM·지르코니아 크라운, 부분 무치악만 대상.</p>' ),
		array( '임시치아 (Provisional)', 'implant', '최종 보철 전 임시로 사용하는 임시 크라운', '<p>임플란트 최종 크라운 완성 전 <strong>임시 크라운(PMMA)</strong> 착용. 저작·심미·잇몸 형성 유지.</p>' ),
		array( '치조골', 'implant', '치아를 지탱하는 턱뼈의 일부', '<p>치조골(Alveolar bone)은 <strong>치아 뿌리를 감싸는 턱뼈</strong>. 발치 후 시간이 지나면 자연스럽게 흡수됩니다.</p>' ),
		array( '치조골 흡수', 'implant', '발치 후 잇몸뼈가 점차 사라지는 현상', '<p>발치 후 <strong>6개월 내 폭 50%·높이 25%</strong> 흡수. 임플란트 식립을 늦출수록 골이식이 필요할 확률이 증가합니다.</p>' ),

		/* ==================== 교정 (40+) ==================== */
		array( '인비절라인', 'ortho', '미국 얼라인 테크놀로지의 대표 투명교정 브랜드', '<p>인비절라인(Invisalign)은 <strong>세계 최초·최대 규모의 투명교정 시스템</strong>. AI ClinCheck로 시뮬레이션, 개인 맞춤 얼라이너 30~60세트 제작.</p>' ),
		array( '슈어스마일', 'ortho', 'Dentsply Sirona의 AI 기반 투명교정', '<p>SureSmile은 Dentsply Sirona의 <strong>AI 기반 투명교정</strong> 시스템. 구강 스캔 → AI 시뮬레이션 → 개인 맞춤 얼라이너.</p>' ),
		array( '설측교정', 'ortho', '치아 안쪽(혀 쪽)에 브라켓을 부착하는 교정', '<p>Lingual Orthodontics. 치아의 <strong>혀 쪽 면</strong>에 브라켓을 붙여 밖에서 보이지 않음. 초기 발음·불편감 있으나 심미성 최고.</p>' ),
		array( '세라믹 교정', 'ortho', '치아색 세라믹 브라켓으로 눈에 덜 띄는 교정', '<p>금속 대신 <strong>치아색 세라믹 브라켓</strong> 사용. 심미성 향상되나 금속보다 부러지기 쉬움.</p>' ),
		array( '메탈 교정', 'ortho', '스테인리스 스틸 브라켓의 전통적 교정', '<p>가장 <strong>강도가 강하고 예측 가능</strong>한 교정 방식. 심미성은 떨어지지만 치료 기간이 짧고 비용 효율적.</p>' ),
		array( '자가결찰 브라켓', 'ortho', '와이어 고정 고무줄이 필요 없는 브라켓', '<p>Self-ligating bracket. 브라켓 자체에 <strong>슬라이딩 도어 형태 클립</strong>이 있어 고무줄(엘라스틱) 필요 없음. 데이몬(Damon)·클리피 등.</p>' ),
		array( '데이몬 브라켓', 'ortho', '자가결찰 방식 대표 브랜드', '<p>Damon System. 마찰이 적고 <strong>치아 이동 속도가 빠르며</strong>, 발치 없는 교정에 유리하다는 특징.</p>' ),
		array( '미니 스크류', 'ortho', '교정용 임시 앵커리지 장치 (TAD)', '<p>TAD(Temporary Anchorage Device). 잇몸뼈에 <strong>임시로 심는 소형 나사</strong>. 특정 치아를 강력하게 이동시키는 앵커리지 역할.</p>' ),
		array( '부분 교정', 'ortho', '앞니 등 일부만 정렬하는 교정', '<p>앞니 6~10개 정도만 교정하는 <strong>단기·저비용 교정</strong>. 3~9개월 완료. 심각한 부정교합에는 부적합.</p>' ),
		array( '발치 교정', 'ortho', '공간 확보를 위해 소구치 등을 발치하는 교정', '<p>돌출입·심한 총생 케이스에서 <strong>제1·제2 소구치</strong>를 발치해 공간을 확보 후 교정. 안모 변화 큼.</p>' ),
		array( '비발치 교정', 'ortho', '치아를 뽑지 않고 진행하는 교정', '<p>확장·IPR·후방견인 등으로 <strong>발치 없이</strong> 공간 확보. 성장기 환자·경증 부정교합에 적합.</p>' ),
		array( 'IPR (스트리핑)', 'ortho', '치아 사이 조금씩 삭제해 공간 확보', '<p>Interproximal Reduction. 치아 사이 <strong>법랑질을 0.2~0.5mm</strong> 삭제해 공간을 확보하는 기법. 발치 없이 교정 가능.</p>' ),
		array( '리테이너 (유지장치)', 'ortho', '교정 후 재발 방지를 위한 유지장치', '<p>Retainer. 교정 완료 후 <strong>치아 위치를 안정화</strong>하기 위해 착용. 가철식·고정식 두 가지, 최소 1년 이상 착용.</p>' ),
		array( '헤드기어', 'ortho', '성장기 아이의 상악 후방 견인 장치', '<p>성장기 어린이 교정용 <strong>외부 견인 장치</strong>. 상악골 성장 조절·앞니 후방 이동. 하루 12~14시간 착용.</p>' ),
		array( '페이스 마스크', 'ortho', '성장기 반대교합 교정 장치', '<p>반대교합(주걱턱) 성장기 어린이용. 상악을 <strong>전방으로 견인</strong>하는 얼굴 프레임 장치.</p>' ),
		array( '급속 확장 장치', 'ortho', '좁은 상악궁을 빠르게 넓히는 장치', '<p>RPE(Rapid Palatal Expander). 성장기에 <strong>상악 정중 봉합을 벌려</strong> 상악궁을 확장. 하루 1~2회 나사 조임.</p>' ),
		array( '부정교합', 'ortho', '치열이나 교합이 정상 범위를 벗어난 상태', '<p>치아 배열이 고르지 않거나 위·아래 맞물림이 어긋난 상태. <strong>Class I·II·III</strong> 세 가지로 분류.</p>' ),
		array( '총생', 'ortho', '치아가 삐뚤빼뚤 겹쳐진 상태', '<p>Crowding. 치아 크기와 턱뼈 크기 불균형으로 <strong>치아가 겹치거나 삐뚤빼뚤한 상태</strong>. 청소 어려움, 교정 필요.</p>' ),
		array( '공극 (치아 사이 벌어짐)', 'ortho', '치아 사이 간격이 벌어진 상태', '<p>Diastema. 앞니 사이가 <strong>벌어진 상태</strong>. 교정 또는 심미레진·라미네이트로 개선.</p>' ),
		array( '개방교합', 'ortho', '앞니가 다물어지지 않는 부정교합', '<p>Open Bite. 어금니는 닿아도 <strong>앞니가 벌어져 다물어지지 않는</strong> 부정교합. 손가락빨기·혀내밀기 습관이 원인.</p>' ),
		array( '과개교합', 'ortho', '위 앞니가 아래 앞니를 심하게 덮은 상태', '<p>Deep Bite. 위 앞니가 아래 앞니를 <strong>2/3 이상 덮은 상태</strong>. 심하면 잇몸 손상 유발.</p>' ),
		array( '반대교합', 'ortho', '위 앞니가 아래 앞니 뒤로 들어간 상태', '<p>Anterior Crossbite. 일명 <strong>주걱턱</strong>. 위 앞니가 아래 앞니 뒤로 들어감. 조기 치료가 중요.</p>' ),
		array( '돌출입', 'ortho', '앞니와 입술이 앞으로 튀어나온 안모', '<p>앞니 또는 상하악골이 <strong>전방으로 돌출</strong>된 상태. 발치 교정으로 개선.</p>' ),
		array( '두부방사선분석', 'ortho', '얼굴 옆면 X-ray로 골격·치아 분석', '<p>Cephalometric analysis. 교정 진단의 필수 검사. 얼굴 옆면 X-ray로 <strong>골격·치아·연조직 각도·거리</strong>를 측정.</p>' ),
		array( '교정 기간', 'ortho', '평균 1~3년, 케이스에 따라 다양', '<p>일반적으로 <strong>부분교정 6개월~1년, 전체교정 18~30개월</strong>. 리테이너 유지기간까지 포함하면 더 오래.</p>' ),
		array( '조기 교정', 'ortho', '유치·혼합치열기의 예방적 교정', '<p>만 6~10세에 시행하는 <strong>1단계 교정</strong>. 성장 이용해 골격 문제 해결. 반대교합·개방교합·좁은 상악에 유리.</p>' ),
		array( '앵커리지', 'ortho', '치아 이동에 필요한 고정원', '<p>Anchorage. 특정 치아를 이동시킬 때 <strong>기준이 되는 고정원</strong>. 미니 스크류·구외 장치 등으로 강화 가능.</p>' ),
		array( '교정용 밴드', 'ortho', '어금니에 감는 금속 링', '<p>대구치에 감아 <strong>브라켓·와이어를 고정</strong>. 미니 스크류로 대체되기도 함.</p>' ),
		array( 'H와이어 (Hawley Wire)', 'ortho', '가철식 리테이너의 대표 형태', '<p>가철식 리테이너로 <strong>플라스틱 판 + 금속 와이어</strong>. 착탈 편리, 세척 쉬움.</p>' ),
		array( '투명 리테이너', 'ortho', '얼라이너 형태의 유지장치', '<p>투명 플라스틱 얼라이너 형태의 유지장치. <strong>Essix retainer</strong>라고도 함. 심미성 우수.</p>' ),
		array( '고정식 리테이너', 'ortho', '치아 안쪽에 부착하는 영구 유지장치', '<p>앞니 안쪽에 <strong>얇은 와이어를 접착</strong>. 영구적 유지, 사용자 협조 불필요.</p>' ),
		array( '와이어 (아치 와이어)', 'ortho', '브라켓을 관통하며 치아를 이동시키는 금속선', '<p>브라켓 슬롯을 <strong>통과하며 치아에 힘을 전달</strong>. 니티놀·스테인리스·베타티타늄 등.</p>' ),
		array( '니티놀 (NiTi)', 'ortho', '형상기억합금 교정용 와이어', '<p>Nickel-Titanium alloy. 체온에서 <strong>원래 형태로 돌아가는 형상기억</strong> 특성으로 지속적 부드러운 힘을 발휘.</p>' ),
		array( '엘라스틱 (고무줄)', 'ortho', '치아 이동력을 만드는 교정용 고무줄', '<p>브라켓 간·상하악 간에 걸어 <strong>추가적 힘</strong>을 만드는 고무. 환자 협조 중요.</p>' ),

		/* ==================== 보존·신경치료 (30+) ==================== */
		array( 'MTA (미네랄 트리옥사이드)', 'preserve', '신경치료·치수보존에 사용하는 생체친화 재료', '<p>Mineral Trioxide Aggregate. <strong>치수보존·역행성 근관치료·치근천공 봉쇄</strong>에 사용하는 첨단 생체친화 재료. 조직 재생 촉진.</p>' ),
		array( '러버댐', 'preserve', '신경치료 시 치아를 격리하는 고무 시트', '<p>Rubber Dam. 신경치료 시 <strong>치아를 침·혀·볼로부터 격리</strong>하는 고무 시트. 무균 환경 유지 필수 장비.</p>' ),
		array( 'NiTi 회전 파일', 'preserve', '신경관 세척용 형상기억 회전 기구', '<p>니켈-티타늄 소재의 <strong>회전 근관 파일</strong>. 곡선 신경관도 안전하게 세척·확대. 천공·분리 위험 최소화.</p>' ),
		array( '어피컬 로케이터', 'preserve', '신경관 끝 위치를 정확히 측정하는 장비', '<p>Apex Locator. <strong>전기적 저항 측정</strong>으로 신경관 길이(근단공까지 거리)를 X-ray 없이 실시간 측정.</p>' ),
		array( '근관세척', 'preserve', '신경관 내부를 소독액으로 세척', '<p>Irrigation. 신경치료 중 <strong>차아염소산나트륨(NaOCl)·EDTA</strong> 등으로 신경관 내부를 소독·세척.</p>' ),
		array( '근관충전', 'preserve', '세척된 신경관을 재료로 밀봉', '<p>Obturation. 세척 후 <strong>거타퍼차·씰러</strong>로 신경관을 완전 밀봉. 재감염 방지의 마지막 단계.</p>' ),
		array( '거타퍼차', 'preserve', '신경관 충전에 사용하는 고무 재료', '<p>Gutta-percha. 신경관 최종 충전 재료. <strong>열가소성 천연 고무</strong>로 신경관 벽에 밀착 가능.</p>' ),
		array( '치수 (Pulp)', 'preserve', '치아 내부의 신경·혈관 조직', '<p>치아 내부 <strong>신경·혈관·결합조직</strong>이 있는 부위. 치수염이 진행되면 신경치료가 필요.</p>' ),
		array( '치수염', 'preserve', '치수에 발생한 염증 (가역·비가역)', '<p>Pulpitis. 충치가 신경에 근접해 생기는 염증. <strong>가역성(회복 가능)</strong>과 <strong>비가역성(신경치료 필요)</strong>으로 구분.</p>' ),
		array( '치수괴사', 'preserve', '치수가 죽어버린 상태', '<p>Pulp Necrosis. 치수염이 방치되어 <strong>신경 조직이 완전히 죽은 상태</strong>. 치아 변색·농양·발열 등 증상. 즉시 신경치료 필요.</p>' ),
		array( '치근단 농양', 'preserve', '치아 뿌리 끝에 생긴 고름주머니', '<p>치수 감염이 뿌리 끝으로 확산되어 <strong>고름 주머니(농양)</strong> 형성. 잇몸 부종·통증·전신 열. 신경치료 또는 배농.</p>' ),
		array( '직접 치수복조', 'preserve', '노출된 신경 위에 MTA를 도포해 신경 보존', '<p>Direct Pulp Capping. 충치 제거 중 <strong>신경이 노출</strong>되면 그 위에 MTA·수산화칼슘을 도포해 신경치료 없이 신경 보존 시도.</p>' ),
		array( '간접 치수복조', 'preserve', '신경 근접 충치 위에 재료를 두고 재광화 유도', '<p>Indirect Pulp Capping. 충치가 신경에 <strong>매우 근접</strong>했지만 노출은 안 된 경우, 남은 얇은 상아질 위에 재료를 도포.</p>' ),
		array( 'Apexification (치근단 폐쇄술)', 'preserve', '어린이 치아 미완성 치근에 뼈 형성 유도', '<p>미성숙 영구치의 신경이 죽었을 때 <strong>치근단 폐쇄를 유도</strong>하는 술식. MTA 사용.</p>' ),
		array( 'Apexogenesis (치근형성술)', 'preserve', '치근 발육이 지속되도록 유도', '<p>미성숙 영구치의 <strong>정상적 치근 발육</strong>을 유지시키는 치수 보존 술식.</p>' ),
		array( '세라믹 인레이', 'preserve', '큰 충치를 세라믹으로 정밀 복원', '<p>레진 충전만으론 부족한 <strong>큰 충치</strong>에 세라믹 블록으로 제작한 인레이 부착. 강도·심미·내구성 우수. 13층 자체 기공실 직접 제작.</p>' ),
		array( '세라믹 온레이', 'preserve', '치아 씹는 면 전체를 세라믹으로 덮는 복원', '<p>인레이보다 <strong>더 넓은 부위(교두 포함)</strong>를 덮는 세라믹 보철. 크라운보다 보존적.</p>' ),
		array( '레진 충전', 'preserve', '충치 부위를 광경화 레진으로 채우는 시술', '<p>Composite Filling. 광경화 <strong>복합레진</strong>으로 충치를 채움. 자연치와 색상이 비슷하고 삭제량 최소.</p>' ),
		array( '아말감', 'preserve', '수은 합금 충전재 (전통 재료)', '<p>수은과 은·주석·구리 합금. 강도 우수하지만 <strong>심미성이 떨어지고 수은 안전성 이슈</strong>. 현재 거의 사용하지 않음.</p>' ),
		array( '치아 재광화', 'preserve', '초기 충치 부위의 미네랄 재침착', '<p>Remineralization. 초기 충치(백색 반점 단계)에 <strong>불소·칼슘·인</strong>을 공급해 다시 단단해지도록 유도.</p>' ),
		array( '초기 충치', 'preserve', '법랑질 표면의 백색 반점 단계', '<p>Incipient Caries. 아직 <strong>구멍이 뚫리기 전의 흰 반점</strong> 상태. 삭제 없이 불소·실런트로 회복 가능.</p>' ),
		array( '치아 파절', 'preserve', '치아가 깨지거나 부러진 상태', '<p>Tooth Fracture. 외상·부정교합·이갈이로 <strong>치아가 깨진 상태</strong>. 파절 부위에 따라 레진·크라운·발치 결정.</p>' ),
		array( '치근 파절', 'preserve', '치아 뿌리가 부러진 상태', '<p>Root Fracture. 치아 뿌리 파절은 <strong>대부분 발치</strong>가 원칙. 극히 일부만 재접합 가능.</p>' ),
		array( '자가치아 이식', 'preserve', '자신의 다른 치아(주로 사랑니)를 발치 부위로 이식', '<p>Auto-transplantation. <strong>사랑니를 뽑아 어금니 발치 부위</strong>에 이식하는 술식. 임플란트 대안, 성공률 80%+.</p>' ),
		array( '치아 재식술', 'preserve', '외상 등으로 빠진 치아를 다시 심는 술식', '<p>Replantation. 외상으로 <strong>완전 탈구된 치아</strong>를 우유·생리식염수에 보관 후 30분~1시간 내 재삽입.</p>' ),
		array( '크라운 연장술', 'preserve', '잇몸 아래 남은 치아를 노출시키는 수술', '<p>Crown Lengthening. 크라운 부착을 위해 <strong>잇몸을 절제해 치아를 더 노출</strong>. 심미 목적으로도 사용.</p>' ),
		array( '이차 우식', 'preserve', '기존 충전물 주변에 생긴 새 충치', '<p>기존 보철·충전물과 치아 사이 <strong>미세 틈에 세균 침투</strong>로 생기는 재발성 충치. 정기 검진으로 조기 발견.</p>' ),
		array( '히포글리세미아 (지각과민)', 'preserve', '차가운 자극·바람에 시린 증상', '<p>Dentin Hypersensitivity. 잇몸퇴축·마모로 <strong>상아질이 노출</strong>되어 시린 증상. 불소도포·시린이 치약·레진 충전으로 완화.</p>' ),

		/* ==================== 치주·잇몸 (25+) ==================== */
		array( '치은염', 'periodontics', '잇몸에 국한된 초기 염증', '<p>Gingivitis. 치석·치태로 인한 <strong>잇몸 표면 염증</strong>. 붉음·부기·출혈. 스케일링·양치로 회복 가능한 초기 단계.</p>' ),
		array( '치주 포켓', 'periodontics', '치아와 잇몸 사이 공간이 깊어진 상태', '<p>Periodontal Pocket. 정상 잇몸틈(1~2mm)이 <strong>3mm 이상 깊어진 상태</strong>. 치석·세균이 쌓여 치주염 진행.</p>' ),
		array( '치근활택술', 'periodontics', '치주 포켓 안 치석 제거 · 보험 적용', '<p>SRP(Scaling & Root Planing). 치주 포켓 내부 <strong>치근 표면의 치석·염증조직 제거</strong> 후 매끄럽게 다듬는 술식. 보험 적용.</p>' ),
		array( '치주 소파술', 'periodontics', '깊이 있는 염증 조직 제거', '<p>깊은 치주 포켓의 <strong>염증성 육아조직</strong>을 큐렛으로 제거. 국소마취 하 시행.</p>' ),
		array( '치주 판막 수술', 'periodontics', '잇몸을 열어 뿌리·뼈를 정리하는 수술', '<p>Periodontal Flap Surgery. <strong>잇몸을 절개해 열고</strong> 치근 표면·잇몸뼈를 직접 확인·정리. 심한 치주염에 시행.</p>' ),
		array( 'GTR (조직유도재생술)', 'periodontics', '차폐막으로 치주조직 재생 유도', '<p>Guided Tissue Regeneration. 잇몸뼈 손실 부위에 <strong>차폐막</strong>을 넣어 골·치주인대 재생을 유도.</p>' ),
		array( '잇몸 이식', 'periodontics', '퇴축된 잇몸을 다른 부위 잇몸으로 보강', '<p>Gum Grafting. 잇몸 퇴축으로 <strong>치근이 노출</strong>된 부위에 상구개나 다른 부위 잇몸을 이식.</p>' ),
		array( '잇몸 성형술', 'periodontics', '심미 목적으로 잇몸 라인 조정', '<p>Gingivectomy/Gingivoplasty. 거미스마일·비대칭 잇몸을 <strong>레이저 또는 절제</strong>로 다듬어 심미적 미소 라인 완성.</p>' ),
		array( '잇몸 미백', 'periodontics', '멜라닌 색소 잇몸을 레이저로 밝게 만듦', '<p>어두운 잇몸의 <strong>멜라닌 색소를 레이저로 제거</strong>. 1회 시술 30~60분, 새 잇몸이 자라며 점진적 밝아짐.</p>' ),
		array( '잇몸 퇴축', 'periodontics', '치조골 흡수·잘못된 양치로 잇몸이 내려앉음', '<p>Gum Recession. 잇몸이 뒤로 물러나 <strong>치근이 노출</strong>되는 현상. 시린 증상·심미 저하·충치 위험 증가.</p>' ),
		array( '치조골 소실', 'periodontics', '치주염으로 잇몸뼈가 파괴되는 현상', '<p>치주염 진행으로 <strong>치조골이 흡수</strong>됨. X-ray로 확인, 재생 어렵고 예방이 중요.</p>' ),
		array( '치주 유지관리 (SPT)', 'periodontics', '치주치료 후 재발 방지 정기 관리', '<p>Supportive Periodontal Therapy. 치주치료 후 <strong>3~6개월 간격 정기 스케일링·검진</strong>. 재발 방지 필수.</p>' ),
		array( '잇몸 출혈', 'periodontics', '양치·씹기 시 잇몸에서 피가 나는 증상', '<p>치은염·치주염의 대표 증상. <strong>양치 시 피가 나면</strong> 치주치료가 필요한 신호. 무통증이라도 방치 금지.</p>' ),
		array( '입냄새 (구취)', 'periodontics', '잇몸 질환·구강 세균에 의한 냄새', '<p>주요 원인: <strong>치주염·설태·충치·구강 건조·소화기 문제</strong>. 스케일링·구강 관리로 대부분 개선.</p>' ),
		array( '치석', 'periodontics', '치태가 침 미네랄과 결합해 굳은 침착물', '<p>Calculus/Tartar. 치태가 <strong>침 속 미네랄</strong>과 결합해 딱딱해진 것. 양치로 제거 불가, 스케일링 필요.</p>' ),
		array( '치태', 'periodontics', '음식물·세균이 뭉친 부드러운 침착물', '<p>Plaque. 음식물·세균이 뭉친 <strong>부드러운 막</strong>. 양치로 제거 가능. 24시간 이상 방치 시 치석화.</p>' ),
		array( '바이오필름', 'periodontics', '구강 세균이 만든 보호막', '<p>Biofilm. 구강 세균이 만든 <strong>다당류 보호막</strong>. 스케일링·에어플로우로 제거.</p>' ),
		array( '치주인대', 'periodontics', '치아와 잇몸뼈를 연결하는 섬유 조직', '<p>Periodontal Ligament. 치아와 치조골을 연결하는 <strong>탄성 섬유 조직</strong>. 저작 시 충격 완화.</p>' ),
		array( '레이저 치주치료', 'periodontics', '레이저로 염증조직·세균 제거', '<p>물방울 레이저(Waterlase)·LANAP 등으로 <strong>염증 조직·세균만 선택적 제거</strong>. 통증·출혈 최소.</p>' ),
		array( '치조골 이식', 'periodontics', '치주염으로 소실된 뼈를 이식으로 재생', '<p>치주염으로 <strong>손실된 잇몸뼈</strong>를 인공 뼈로 재건. 치주 판막 수술과 함께 진행.</p>' ),
		array( '치주염 원인균', 'periodontics', 'P.gingivalis 등 그람음성 혐기성 세균', '<p>주요 원인균: <strong>Porphyromonas gingivalis, Treponema denticola, Tannerella forsythia</strong> (Red Complex). 스케일링·SRP로 제거.</p>' ),

		/* ==================== 심미치료 (20+) ==================== */
		array( '최소침습 라미네이트', 'aesthetic', '치아 삭제 최소화한 얇은 라미네이트', '<p>Minimal Prep Laminate. 치아 삭제 <strong>0.3mm 이하</strong>, 마취 없이 가능한 케이스도. 자연치 보존율 최고.</p>' ),
		array( 'No-prep 라미네이트', 'aesthetic', '치아 삭제 없이 부착하는 라미네이트', '<p>치아 삭제를 <strong>거의 하지 않고</strong> 부착. 원상 복구 가능하나 케이스 제한적. 두께감 있을 수 있음.</p>' ),
		array( 'e.max 라미네이트', 'aesthetic', '이보클라 e.max 소재의 프리미엄 라미네이트', '<p>Ivoclar Vivadent사의 <strong>리튬 디실리케이트 세라믹</strong>. 심미성·강도 균형이 우수한 대표 브랜드.</p>' ),
		array( 'Empress 라미네이트', 'aesthetic', '이보클라 Empress 소재 심미 라미네이트', '<p>IPS Empress. <strong>류사이트 강화 유리 세라믹</strong>. 자연치와 구분 어려운 투명도·심미성.</p>' ),
		array( '올세라믹 크라운', 'aesthetic', '금속 없이 세라믹만으로 제작한 크라운', '<p>All-Ceramic Crown. <strong>금속 프레임 없이</strong> 100% 세라믹. 앞니 심미 크라운의 표준.</p>' ),
		array( 'PFM 크라운', 'aesthetic', '금속 코어 + 세라믹 도포 크라운', '<p>Porcelain Fused to Metal. 금속 프레임에 <strong>세라믹을 도포</strong>. 어금니에 강도·심미 균형.</p>' ),
		array( '풀 지르코니아 크라운', 'aesthetic', '완전 지르코니아 소재 크라운', '<p>Full Zirconia. 최고 강도의 세라믹. <strong>파절 위험이 매우 낮음</strong>. 어금니에 이상적.</p>' ),
		array( '레이어링 지르코니아', 'aesthetic', '지르코니아 위에 세라믹 도포로 심미 향상', '<p>지르코니아 코어에 <strong>도재 도포</strong>로 심미성 크게 향상. 앞니에 적합.</p>' ),
		array( '홈 화이트닝', 'aesthetic', '집에서 4주간 진행하는 자가 미백', '<p>Home Whitening. 맞춤 트레이 + 저농도 미백제. <strong>매일 1시간·4주</strong> 진행. 시린 증상 적음.</p>' ),
		array( '오피스 화이트닝', 'aesthetic', '병원에서 1~2회로 완료하는 전문가 미백', '<p>병원에서 <strong>고농도 미백제 + 광조사</strong>. 1-Day 또는 2-Day 완성. 빠른 효과.</p>' ),
		array( '복합 미백', 'aesthetic', '오피스 + 홈 병행으로 효과 극대화', '<p>병원 미백 후 홈 미백으로 <strong>유지·효과 강화</strong>. 가장 효과적이고 지속력 좋음.</p>' ),
		array( '스마일 라인', 'aesthetic', '웃을 때 앞니 끝의 곡선', '<p>Smile Line. 웃을 때 <strong>앞니 절단연이 아랫입술과 만드는 곡선</strong>. 아래로 볼록한 곡선이 심미적으로 이상적.</p>' ),
		array( '치아 색상 (셰이드)', 'aesthetic', 'A1~D4까지 표준화된 치아색 등급', '<p>Vita Shade Guide. A(붉은-갈색계)·B(붉은-노란계)·C(회색계)·D(회색-붉은계) × 1~4. <strong>A1이 가장 밝고, D4가 가장 어둡습니다</strong>.</p>' ),
		array( '가슴메탈 (Cast Metal)', 'aesthetic', '금속만으로 제작한 크라운·인레이', '<p>금·비귀금속 합금 등 <strong>금속만으로 제작</strong>. 심미성 없으나 강도·수명 우수. 안 보이는 어금니에 적합.</p>' ),
		array( '치아 성형 (에스테틱 컨투어링)', 'aesthetic', '치아 모양·길이를 소량 삭제로 다듬음', '<p>Aesthetic Contouring. <strong>0.1~0.5mm 소량 삭제</strong>로 앞니 모양 조정. 시술 시간 짧고 통증 없음.</p>' ),
		array( '다이렉트 본딩', 'aesthetic', '레진을 직접 붙여 형태 개선', '<p>Direct Bonding. 앞니 <strong>레진을 직접 쌓아</strong> 모양·색 개선. 1회 완료, 라미네이트보다 저렴.</p>' ),
		array( '풀 마우스 리컨스트럭션', 'aesthetic', '전체 치아를 종합적으로 재건', '<p>Full Mouth Reconstruction. 다수 치아 손상·마모 시 <strong>전체 치아를 종합 재건</strong>. 교합 회복 + 심미 개선.</p>' ),
		array( '보톡스 (가늘어진 얼굴)', 'aesthetic', '저작근에 주사해 얼굴 라인 개선', '<p>사각턱 원인인 <strong>교근에 보톡스</strong> 주사. 3~6개월 효과. 이갈이 완화에도 활용.</p>' ),
		array( '치아색 매칭', 'aesthetic', '주변 치아와 어울리는 색 선택', '<p>Shade Matching. 라미네이트·크라운 제작 시 <strong>주변 자연치 색</strong>과 어울리도록 정밀 매칭.</p>' ),

		/* ==================== 보철 (25+) ==================== */
		array( '크라운', 'prosthetics', '손상된 치아 전체를 감싸는 보철', '<p>Crown. 손상 큰 치아를 <strong>전체적으로 씌우는 보철</strong>. 지르코니아·PFM·올세라믹·금 등 재료.</p>' ),
		array( '브릿지', 'prosthetics', '빠진 치아 양옆을 지지대로 인공 치아 걸침', '<p>Bridge. 빠진 치아 <strong>양옆 치아를 삭제해</strong> 지지대로 사용, 그 사이에 인공 치아. 임플란트 대안.</p>' ),
		array( '마릴랜드 브릿지', 'prosthetics', '옆 치아를 최소 삭제하는 접착식 브릿지', '<p>Maryland Bridge. 옆 치아 <strong>안쪽에만 얇은 날개</strong>를 붙여 인공치아 지지. 앞니 임시 대체용.</p>' ),
		array( '틀니 (총의치)', 'prosthetics', '전체 치아를 잃은 경우 착탈식 인공 치아', '<p>Complete Denture. 상악 또는 하악 전체 무치악에 <strong>착탈식 인공 치아</strong>. 65세 이상 건강보험 적용.</p>' ),
		array( '부분 틀니', 'prosthetics', '일부 치아를 잃은 경우의 착탈식 보철', '<p>RPD(Removable Partial Denture). <strong>남은 치아에 클라스프(고리)</strong>로 걸어 안정. 건강보험 적용.</p>' ),
		array( '임플란트 지지 부분틀니', 'prosthetics', '임플란트 + 부분틀니 조합', '<p>임플란트 몇 개를 심어 <strong>부분틀니 지지력을 크게 향상</strong>. 남은 자연치 부담 감소.</p>' ),
		array( '캔틸레버 브릿지', 'prosthetics', '한 쪽만 지지대가 있는 브릿지', '<p>Cantilever Bridge. 인공 치아를 <strong>한 쪽 치아만으로 지지</strong>. 강도 약해 특정 케이스에만 사용.</p>' ),
		array( 'PFM (도재-금속)', 'prosthetics', '금속 코어 + 도재의 조합', '<p>Porcelain Fused to Metal. 금속 프레임 위에 <strong>도재 도포</strong>. 강도·심미 균형.</p>' ),
		array( 'PFG (도재-금)', 'prosthetics', '금 코어 + 도재의 조합', '<p>Porcelain Fused to Gold. 금 코어 사용, <strong>적합도가 매우 우수</strong>. 알레르기 없음.</p>' ),
		array( '임시 크라운', 'prosthetics', '최종 크라운 제작 중 착용하는 임시 보철', '<p>Provisional Crown. PMMA 재료로 만든 <strong>1~4주 착용용 임시 크라운</strong>. 심미·저작·잇몸 형태 유지.</p>' ),
		array( 'PMMA', 'prosthetics', '치과용 아크릴 수지', '<p>Polymethyl Methacrylate. 임시 크라운·틀니 base·CAD/CAM 밀링에 <strong>널리 사용되는 치과용 수지</strong>.</p>' ),
		array( 'CAD/CAM', 'prosthetics', '컴퓨터 설계·자동 제작 시스템', '<p>Computer-Aided Design/Manufacturing. 구강 스캔 → PC 설계 → <strong>밀링 머신 자동 제작</strong>. 정밀도·재현성 우수.</p>' ),
		array( '치과용 시멘트', 'prosthetics', '크라운·인레이 접착용 시멘트', '<p>Dental Cement. 종류: <strong>RelyX(레진), GC Fuji(GIC), Ketac Cem, Zinc Phosphate</strong> 등. 케이스별 선택.</p>' ),
		array( '알지네이트', 'prosthetics', '해조류 기반 인상재', '<p>Alginate. 해조류 추출 <strong>다당류 인상재</strong>. 저렴·빠름·간단한 인상에 사용. 정밀도는 실리콘 대비 낮음.</p>' ),
		array( '실리콘 인상재', 'prosthetics', '고정밀 크라운·라미네이트용 인상재', '<p>PVS(Polyvinyl Siloxane) 또는 폴리에테르. <strong>매우 정밀한 인상</strong>. 크라운·라미네이트 제작에 필수.</p>' ),
		array( '구강 스캐너', 'prosthetics', '입안을 3D 디지털로 스캔하는 장비', '<p>Intraoral Scanner. <strong>iTero, TRIOS, PrimeScan</strong> 등. 인상재 없이 3D 데이터 확보.</p>' ),
		array( '바이트 등록', 'prosthetics', '위·아래 교합 관계를 기록', '<p>Bite Registration. 왁스·실리콘 등으로 <strong>환자의 정상 교합 위치</strong>를 기록해 보철 제작에 반영.</p>' ),
		array( '교합 조정', 'prosthetics', '보철 장착 후 교합 접촉을 조정', '<p>Occlusal Adjustment. 보철 부착 후 <strong>씹을 때 접촉 강도·위치</strong>를 조정. 편안한 저작·수명 연장.</p>' ),
		array( '보철 수명', 'prosthetics', '크라운·브릿지 평균 10~20년', '<p>일반적 크라운·브릿지 수명: <strong>10~20년</strong>. 재료·관리·저작 습관에 따라 다름. 정기 검진 필수.</p>' ),
		array( '틀니 재제작 주기', 'prosthetics', '평균 5~7년마다 새로 제작', '<p>잇몸뼈 흡수로 <strong>5~7년마다 재제작</strong> 권장. 매년 리라이닝(내면 조정)으로 수명 연장 가능.</p>' ),

		/* ==================== 구강외과·사랑니 (15+) ==================== */
		array( '단순 발치', 'surgery', '노출된 치아를 뽑는 기본 시술', '<p>Simple Extraction. <strong>잇몸 위로 나온 치아</strong>를 마취 후 뽑는 시술. 통상 15분 이내.</p>' ),
		array( '외과적 발치', 'surgery', '매복·부러진 치아 등 잇몸을 열어 발치', '<p>Surgical Extraction. <strong>잇몸을 절개하고 뼈를 다듬어</strong> 발치. 매복치·파절치·사랑니 등. CBCT 진단 필수.</p>' ),
		array( '수평 매복 사랑니', 'surgery', '옆으로 누운 방향의 사랑니', '<p>Horizontal Impaction. 사랑니가 <strong>옆으로 누운 상태</strong>. 앞 치아를 압박·낭종 위험. CBCT로 신경 확인 후 발치.</p>' ),
		array( '완전 매복 사랑니', 'surgery', '잇몸·뼈 속에 완전히 파묻힌 사랑니', '<p>Full Bony Impaction. <strong>잇몸 아래 완전히 파묻힌</strong> 사랑니. 외과적 발치, 회복 기간 길 수 있음.</p>' ),
		array( '반매복 사랑니', 'surgery', '부분적으로 잇몸을 뚫고 나온 사랑니', '<p>Partial Impaction. 사랑니 <strong>일부만 노출</strong>. 청소 어려워 잇몸염 잦음. 조기 발치 권장.</p>' ),
		array( '치조골 재건', 'surgery', '외상·낭종 후 잇몸뼈를 재건', '<p>Alveolar Reconstruction. 외상·낭종 절제 후 잇몸뼈를 <strong>골이식으로 재건</strong>.</p>' ),
		array( '치성 낭종', 'surgery', '치아 주변에 생긴 물주머니', '<p>Odontogenic Cyst. 치아 뿌리 주변에 생기는 <strong>액체가 찬 낭</strong>. 낭종 적출술로 제거.</p>' ),
		array( '낭종 적출술', 'surgery', '치성 낭종을 외과적으로 제거', '<p>Enucleation. 낭종 벽을 완전히 <strong>절제해 제거</strong>. 재발 방지가 목적.</p>' ),
		array( '상악동염', 'surgery', '상악동에 생긴 염증 · 치성 원인 가능', '<p>Sinusitis. 감기·알레르기 외에 <strong>위턱 어금니 감염</strong>이 상악동으로 확산되어 발생.</p>' ),
		array( '구내염', 'surgery', '입안 점막에 생긴 염증·궤양', '<p>Stomatitis. <strong>스트레스·비타민 부족·바이러스</strong> 등 다양한 원인. 대부분 1~2주 내 자연 치유.</p>' ),
		array( '아프타성 궤양', 'surgery', '입안에 반복 발생하는 작은 궤양', '<p>Aphthous Ulcer. 흔한 <strong>구내염 형태</strong>. 원형·타원형 흰 궤양 + 붉은 테두리.</p>' ),
		array( '헤르페스 구내염', 'surgery', 'HSV 바이러스에 의한 물집·궤양', '<p>Herpetic Stomatitis. 단순포진 바이러스 감염. 입술·잇몸에 <strong>물집 → 궤양</strong>. 항바이러스제 처방.</p>' ),
		array( '진정요법', 'surgery', '수면 유도로 편안한 시술 환경', '<p>Sedation. 정맥·경구·흡입 진정으로 <strong>불안·공포 감소</strong>. 사랑니 발치·임플란트 다수 식립 시 활용.</p>' ),
		array( 'BRONJ (골괴사)', 'surgery', '골다공증 약물 부작용으로 턱뼈 괴사', '<p>Bisphosphonate-Related Osteonecrosis of Jaw. <strong>골다공증 약제(비스포스포네이트)</strong> 복용자에게 발치 후 발생 위험.</p>' ),
		array( '항생제 예방적 투여', 'surgery', '수술 전 감염 예방 목적 투여', '<p>심장 판막·인공 관절 환자 등 특정 상태에서 <strong>수술 전 예방 항생제</strong>를 처방해 감염 위험 감소.</p>' ),

		/* ==================== 턱관절 (12+) ==================== */
		array( 'TMD (턱관절 장애)', 'tmj', '턱관절과 주변 근육의 통증·기능장애', '<p>Temporomandibular Disorder. 턱관절 소리·통증·개구제한·두통의 <strong>복합 증상군</strong>. 스플린트·물리치료 우선.</p>' ),
		array( '관절원판', 'tmj', '턱관절 안 쿠션 역할 디스크', '<p>Articular Disc. 턱관절 <strong>위·아래 뼈 사이의 섬유 디스크</strong>. 저작 시 완충 역할.</p>' ),
		array( '관절원판 변위', 'tmj', '디스크가 정상 위치를 벗어난 상태', '<p>Disc Displacement. 입 벌릴 때 <strong>딱딱 소리</strong>의 원인. 방치 시 개구제한·통증 심화.</p>' ),
		array( '개구제한', 'tmj', '입이 잘 벌어지지 않는 증상', '<p>정상 개구 40~50mm. <strong>35mm 이하로 벌어지지 않으면</strong> 개구제한. 관절원판·근육 문제 원인.</p>' ),
		array( '안정위 스플린트', 'tmj', '턱관절 안정을 위한 마우스가드', '<p>Stabilization Splint. <strong>맞춤 제작 스플린트</strong> 착용으로 근육 이완·이갈이 방지. 야간 착용.</p>' ),
		array( '재위치 스플린트', 'tmj', '디스크를 원위치로 유도하는 장치', '<p>Repositioning Splint. 관절원판이 <strong>정상 위치로 돌아가도록 유도</strong>. 6~12개월 착용.</p>' ),
		array( '마우스가드', 'tmj', '이갈이·스포츠 시 착용하는 보호 장치', '<p>Mouth Guard. 이갈이 · 스포츠 시 <strong>치아·잇몸·턱관절 보호</strong>. 맞춤 제작 필수.</p>' ),
		array( '측두근', 'tmj', '관자놀이 쪽 저작근', '<p>Temporalis Muscle. 관자놀이의 저작근. 과긴장 시 <strong>두통·측두 통증</strong> 유발.</p>' ),
		array( '교근 (Masseter)', 'tmj', '광대뼈 아래 강한 저작근', '<p>Masseter. 광대뼈 아래 <strong>가장 강력한 저작근</strong>. 이갈이·사각턱 원인. 보톡스로 축소 가능.</p>' ),
		array( '근막통증증후군', 'tmj', '만성 근육 통증·트리거 포인트', '<p>Myofascial Pain Syndrome. 저작근·목·어깨에 <strong>만성 통증·경직</strong>. 스트레스·자세와 관련.</p>' ),
		array( '이악물기 (Clenching)', 'tmj', '무의식적으로 이를 꽉 무는 습관', '<p>스트레스 등으로 <strong>이를 꽉 무는 습관</strong>. 치아 마모·턱관절 부담. 마우스가드 도움.</p>' ),

		/* ==================== 소아치과 (18+) ==================== */
		array( '유치', 'pediatric', '어린이 시기의 젖니 (총 20개)', '<p>Primary Teeth. 총 20개. 6개월~2.5세 맹출, <strong>6~12세에 영구치로 교체</strong>. 조기 상실 시 공간유지장치 필요.</p>' ),
		array( '혼합치열기', 'pediatric', '유치와 영구치가 함께 있는 시기 (6~12세)', '<p>Mixed Dentition. 6~12세, <strong>유치와 영구치가 공존</strong>. 조기 교정 판단이 중요한 시기.</p>' ),
		array( '유치 신경치료', 'pediatric', '유치의 치수를 부분 제거하는 치료', '<p>Pulpotomy(치관부 치수만 제거)·Pulpectomy(전체 치수 제거). <strong>영구치 유도</strong>를 위해 유치 유지 목적.</p>' ),
		array( '스테인리스 크라운 (SSC)', 'pediatric', '유치 어금니에 씌우는 은색 금속 크라운', '<p>Stainless Steel Crown. 큰 유치 우식 후 <strong>영구치가 나올 때까지 유지</strong>. 저렴·강도 우수.</p>' ),
		array( '지르코니아 어린이 크라운', 'pediatric', '유치용 심미 지르코니아 크라운', '<p>SSC 대신 <strong>치아색 지르코니아</strong> 크라운. 심미성 우수하지만 SSC보다 비쌈.</p>' ),
		array( '우유병 우식', 'pediatric', '수면 중 우유병 습관으로 인한 광범위 우식', '<p>Baby Bottle Tooth Decay. <strong>수면 중 우유·주스 병</strong> 지속 사용으로 앞니 광범위 우식. 조기 교정 필요.</p>' ),
		array( '조기 유치 상실', 'pediatric', '영구치 나오기 전 유치가 빠진 상태', '<p>공간이 부족해져 <strong>영구치가 올바로 못 나올 수 있음</strong>. 공간유지장치로 예방.</p>' ),
		array( '공간유지장치', 'pediatric', '유치 조기 상실 시 공간 확보용 장치', '<p>Space Maintainer. 영구치가 나올 <strong>공간을 미리 확보</strong>. 고정식·가철식.</p>' ),
		array( '유치 외상', 'pediatric', '넘어짐 등으로 유치가 손상된 경우', '<p>대부분 <strong>경과 관찰</strong>. 영구치 배아 손상 여부가 중요. X-ray 확인.</p>' ),
		array( '손가락 빨기', 'pediatric', '과도한 습관은 개방교합 원인', '<p>만 4세 이상 지속 시 <strong>개방교합·상악 돌출</strong> 유발. 습관 교정 장치·행동요법.</p>' ),
		array( '혀 내밀기', 'pediatric', '삼킴 시 혀가 앞으로 나오는 습관', '<p>Tongue Thrust. <strong>개방교합·상악 돌출</strong> 유발. 근기능 훈련(MFT)으로 교정.</p>' ),
		array( '어린이 실런트', 'pediatric', '유·영구 어금니 홈메우기', '<p>영구치 큰어금니가 나온 직후 홈메우기로 <strong>충치 예방률 90%+</strong>. 만 18세 이하 보험 적용.</p>' ),
		array( '어린이 불소도포', 'pediatric', '유·영구치 재광화 강화 시술', '<p>3~6개월 주기 정기 도포. 충치 발생률 <strong>30~40% 감소</strong>. 통증 없이 10분 이내.</p>' ),
		array( '조기 교정 (1단계)', 'pediatric', '6~10세 골격 문제 예방 교정', '<p>성장 이용해 <strong>골격 부조화·나쁜 습관 교정</strong>. 반대교합·좁은 상악·손가락 빨기 등 대상.</p>' ),
		array( '치아 맹출', 'pediatric', '치아가 잇몸을 뚫고 나오는 과정', '<p>Eruption. 유치: 6개월~2.5세, 영구치: 6~13세 (사랑니 17~25세). <strong>맹출 지연 시 X-ray 확인</strong>.</p>' ),
		array( '치아 형성 부전', 'pediatric', '치아 형성 과정의 이상', '<p>Enamel Hypoplasia 등. 유전·질환·약물로 <strong>법랑질·상아질이 부실</strong>하게 형성. 조기 관리 필요.</p>' ),

		/* ==================== 예방·검진 (18+) ==================== */
		array( '보험 스케일링', 'prevention', '만 19세 이상 연 1회 건강보험 적용', '<p>만 19세 이상 누구나 <strong>연 1회 건강보험 적용</strong>. 본인부담 약 25,000원. 1월 1일 갱신.</p>' ),
		array( '비급여 스케일링', 'prevention', '보험 초과·정밀 스케일링', '<p>연 1회 초과 또는 <strong>정밀 스케일링</strong>. 6개월 주기 권장.</p>' ),
		array( '파노라마 X-ray', 'prevention', '전체 치아·턱뼈를 한 장에 촬영', '<p>Panorama. 상하악 <strong>전체를 한 장의 필름</strong>에 촬영. 사랑니·매복치·전체 구조 확인.</p>' ),
		array( '치근단 X-ray', 'prevention', '특정 치아를 정밀 촬영', '<p>Periapical. 개별 치아를 <strong>정밀 촬영</strong>. 신경치료·충치·치주염 진단.</p>' ),
		array( '교익 X-ray', 'prevention', '어금니 사이 충치 진단용', '<p>Bitewing. 어금니 사이 <strong>인접면 충치</strong> 진단에 특화된 촬영.</p>' ),
		array( '방사선량', 'prevention', '치과 X-ray의 극소량 방사선', '<p>파노라마 1회 = <strong>비행기 3~5시간 우주선</strong> 수준의 극소량. 안전.</p>' ),
		array( 'CBCT (콘빔 CT)', 'prevention', '3D 정밀 진단의 표준', '<p>Cone Beam Computed Tomography. <strong>3D X-ray</strong>. 임플란트·매복치·근관치료에 필수. 일반 CT 대비 1/10 방사선량.</p>' ),
		array( '구강암 검진', 'prevention', '입안 조직을 시진·촉진으로 검사', '<p>정기 검진 시 <strong>혀·볼·잇몸·입천장</strong>의 이상 병변 조기 발견. 40세 이후 정기 검진 권장.</p>' ),
		array( '치실', 'prevention', '치아 사이 청소 도구', '<p>Dental Floss. 하루 1회 <strong>치아 사이 프라크·음식물 제거</strong>. 왁스·논왁스·플로스 스틱 등 다양.</p>' ),
		array( '치간 칫솔', 'prevention', '큰 치아 사이·교정 장치 청소', '<p>Interdental Brush. 치간이 넓거나 <strong>교정 장치·임플란트 주변</strong> 청소에 효과적.</p>' ),
		array( '워터픽', 'prevention', '수압으로 치간 청소하는 장비', '<p>Water Flosser. <strong>물줄기로 치간·잇몸 라인</strong> 청소. 교정·임플란트 환자에 특히 유용.</p>' ),
		array( '전동 칫솔', 'prevention', '진동·회전으로 청소하는 자동 칫솔', '<p>초음파·회전·진동 방식. 수동 대비 <strong>플라크 제거 효율 20%↑</strong>. 소니케어·오랄비 등.</p>' ),
		array( '올바른 양치법', 'prevention', '3·3·3 원칙과 회전법', '<p><strong>하루 3회·식후 3분 이내·3분 이상</strong>. 잇몸에서 치아 방향으로 회전법(로테이션).</p>' ),
		array( '설태 제거', 'prevention', '혀 표면 세균막 제거', '<p>혀 표면의 <strong>흰 막(설태)</strong>은 구취 주 원인. 혀 클리너·부드러운 칫솔로 매일 청소.</p>' ),
		array( '구강 건조증', 'prevention', '침 분비 부족으로 구강 질환 위험 증가', '<p>Xerostomia. 침 부족으로 <strong>충치·구내염·구취</strong> 위험. 원인: 노화·약물·전신질환. 수분 섭취·타액 대체제.</p>' ),
		array( '가글 (구강 세정제)', 'prevention', '살균·불소 함유 헹굼제', '<p>클로르헥시딘(항균)·불소·염화세틸피리디늄 등. <strong>양치를 대체하지 않고 보조</strong>.</p>' ),

		/* ==================== 일반 치의학 (30+) ==================== */
		array( '앞니 (절치)', 'general', '위·아래 앞쪽 4개씩 총 8개', '<p>Incisor. 음식 자르는 역할. <strong>중절치·측절치</strong> 각 2개씩 상하 총 8개.</p>' ),
		array( '송곳니 (견치)', 'general', '4개, 음식을 찢는 뾰족한 치아', '<p>Canine. 앞니 옆에 위치한 <strong>뾰족한 치아</strong>. 상하 좌우 각 1개, 총 4개. 안모 형성에 중요.</p>' ),
		array( '작은어금니 (소구치)', 'general', '송곳니 뒤 8개, 음식 부수는 역할', '<p>Premolar. 각 부위 <strong>2개씩 총 8개</strong>. 제1·2 소구치. 발치 교정 시 주로 뽑히는 치아.</p>' ),
		array( '큰어금니 (대구치)', 'general', '가장 안쪽 8~12개, 음식 씹는 주 역할', '<p>Molar. 제1·2 대구치 각 4개 = 총 8개. 사랑니(제3대구치) 포함 시 최대 12개.</p>' ),
		array( '법랑질', 'general', '치아 표면의 가장 단단한 조직', '<p>Enamel. 인체에서 <strong>가장 단단한 조직</strong>(모스 경도 5). 96% 무기질. 재생되지 않음.</p>' ),
		array( '상아질', 'general', '법랑질 아래 노란색 조직', '<p>Dentin. 법랑질 아래 <strong>연노란색 조직</strong>. 신경관과 통해 있어 자극 전달. 시린 원인.</p>' ),
		array( '치수 (신경)', 'general', '치아 중심의 신경·혈관 조직', '<p>Pulp. 치아 내부 신경·혈관·결합조직. <strong>치수염이 신경치료 대상</strong>.</p>' ),
		array( '치관', 'general', '잇몸 위 눈에 보이는 치아 부분', '<p>Crown. 잇몸 밖으로 <strong>노출된 치아</strong> 부분.</p>' ),
		array( '치근', 'general', '잇몸 속에 파묻힌 치아 뿌리', '<p>Root. <strong>잇몸 속 뿌리</strong>. 앞니 1개, 소구치 1~2개, 대구치 2~3개.</p>' ),
		array( '치경부', 'general', '치관과 치근의 경계', '<p>Cervical. 잇몸 라인 근처 <strong>치관과 치근이 만나는 경계</strong>. 마모·시린 증상 잘 생김.</p>' ),
		array( '시멘트질', 'general', '치근 표면을 덮는 얇은 조직', '<p>Cementum. 치근 표면의 얇은 조직. <strong>치주인대와 치조골을 연결</strong>.</p>' ),
		array( '뮤탄스균', 'general', '충치를 유발하는 대표 세균', '<p>Streptococcus mutans. 음식물 <strong>당을 산으로 바꿔</strong> 법랑질 부식. 대표 충치 원인균.</p>' ),
		array( '락토바실러스', 'general', '충치 진행에 관여하는 산 생성균', '<p>Lactobacillus. 뮤탄스균에 이어 <strong>충치 진행 단계</strong>에 관여.</p>' ),
		array( '침 (타액)', 'general', '구강 건강을 지키는 자연 방어물', '<p>Saliva. <strong>세척·완충·재광화</strong> 역할. 하루 1~1.5L 분비. 감소 시 충치 위험 증가.</p>' ),
		array( '교합', 'general', '위·아래 치아가 맞물리는 관계', '<p>Occlusion. 상하 치아가 <strong>맞물리는 상태·관계</strong>. 부정교합·정상교합으로 구분.</p>' ),
		array( '저작', 'general', '음식을 씹어 잘게 부수는 활동', '<p>Mastication. 치아·저작근·혀·침이 협동하여 <strong>음식을 삼킴 가능하게 부수는 과정</strong>.</p>' ),
		array( '악관절', 'general', '위턱뼈와 아래턱뼈의 관절', '<p>Temporomandibular Joint(TMJ). 귀 앞쪽 <strong>턱관절</strong>. 저작·발음·연하 시 사용.</p>' ),
		array( '치주 조직', 'general', '치아를 지지하는 4가지 조직', '<p>Periodontium. <strong>잇몸(치은)·치주인대·치조골·시멘트질</strong> 4가지가 치아를 안정적으로 지지.</p>' ),
		array( '유공 (Foramen)', 'general', '치아 뿌리 끝의 신경·혈관 통로', '<p>Apical Foramen. 치근 끝의 <strong>작은 구멍</strong>. 신경·혈관이 치수로 들어감.</p>' ),
		array( '치식', 'general', '치아 위치를 표기하는 국제 표기법', '<p>FDI Notation. 상악 우측부터 시계방향으로 <strong>사분면·번호</strong>로 표기(11~48).</p>' ),
		array( '유탈구', 'general', '외상으로 치아가 완전히 빠진 상태', '<p>Avulsion. 외상으로 <strong>치아가 완전 이탈</strong>. 우유·생리식염수 보관 → 1시간 내 재식술.</p>' ),
		array( '진정 요법 (수면 치료)', 'general', '불안·공포 환자용 이완 상태 유도', '<p>Sedation. 정맥·경구·흡입 <strong>진정제로 이완 상태</strong> 유도. 사랑니·임플란트에 활용.</p>' ),
		array( '전신마취 치과', 'general', '중증 환자·소아 광범위 치료용 전신마취', '<p>중증 장애·심한 공포·다수 치료 필요 시 <strong>전신마취 하 치과 진료</strong>.</p>' ),
		array( '치과 X-ray', 'general', '치아·잇몸뼈·신경을 보는 필수 진단 도구', '<p>Dental X-ray. <strong>파노라마·치근단·CBCT</strong> 등. 극소량 방사선으로 안전.</p>' ),
		array( '요양기관번호', 'general', '건강보험공단 등록 의료기관 고유번호', '<p>건강보험 청구를 위한 <strong>의료기관 고유 식별번호</strong>. 문치과병원: 34400117.</p>' ),
		array( '치과 위생사', 'general', '스케일링·예방 치과 전문 국가자격', '<p>Dental Hygienist. <strong>스케일링·구강 위생 교육·불소 도포·기록 관리</strong>. 4년제 국가자격.</p>' ),
		array( '치과 기공사', 'general', '보철물·교정 장치를 제작하는 전문가', '<p>Dental Technician. 치과의사 처방으로 <strong>크라운·틀니·교정 장치</strong> 등을 제작.</p>' ),
		array( '치과 의사', 'general', '치과 진료·수술 국가면허 전문의', '<p>Dentist. 치과대학 6년 + 국가시험. <strong>일반의·전문의(11개 세부과)</strong>로 구분.</p>' ),
	);
}

/**
 * 마이그레이션 · 대량 시드 · 한 번만 실행.
 */
add_action( 'init', function() {
	if ( get_option( 'moondental_encyclopedia_massive_v3351' ) === 'done' ) return;
	if ( ! post_type_exists( 'md_term' ) ) return;

	$data = moondental_encyclopedia_seed_data();
	$added = 0;

	foreach ( $data as $t ) {
		list( $title, $cat, $excerpt, $body ) = $t;

		// 중복 스킵
		$existing = get_posts( array(
			'post_type'      => 'md_term',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( ! empty( $existing ) ) continue;

		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $body,
			'post_name'    => sanitize_title( $title ),
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			$term = get_term_by( 'slug', $cat, 'md_term_category' );
			if ( $term ) {
				wp_set_object_terms( $post_id, array( $term->term_id ), 'md_term_category' );
			}
			$added++;
		}
	}

	update_option( 'moondental_encyclopedia_massive_v3351', 'done' );
}, 35 );
