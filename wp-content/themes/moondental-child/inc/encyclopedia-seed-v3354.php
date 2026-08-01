<?php
/**
 * Moon Dental · 치과사전 4차 확장 시드 (500+ 개 추가)
 *
 *  v3.35.4 · 사용자 확인 · 500개만 있어서 500+ 추가 → 총 1000+ 목표
 *  이전 시드와 중복되지 않는 새 용어 위주.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function moondental_encyclopedia_seed_data_v3354() {
	return array(

		/* ==================== 근관치료 기구·기법 (신규) ==================== */
		array( 'K-file 8호', 'preserve', '가장 얇은 근관 파일 (0.08mm)', '<p>K-File #08. <strong>가장 얇은 수동 파일</strong>. 좁은 신경관 초기 탐색.</p>' ),
		array( 'K-file 10호', 'preserve', '얇은 근관 파일 (0.10mm)', '<p>K-File #10. 좁은 근관 <strong>초기 세척용</strong>.</p>' ),
		array( 'K-file 15호', 'preserve', '표준 초기 파일 (0.15mm)', '<p>K-File #15. <strong>ISO 표준 시작 파일</strong>. 대부분 근관에 적합.</p>' ),
		array( 'K-file 25호', 'preserve', '중간 크기 근관 파일', '<p>K-File #25. <strong>확대 단계</strong>. 이후 30·35·40번으로 확대.</p>' ),
		array( 'K-file 40호', 'preserve', '표준 마스터 파일 크기', '<p>K-File #40. <strong>마스터 파일 표준</strong>. 대부분 근관 완성 크기.</p>' ),
		array( 'H-file', 'preserve', '헤드스트롬 파일 · 절삭력 우수', '<p>Hedstrom File. <strong>당김 절삭</strong>. 근관 벽 매끄럽게 정리.</p>' ),
		array( 'ProTaper', 'preserve', 'Dentsply의 대표 회전 파일 시스템', '<p>ProTaper Gold·Universal·Next. <strong>가변 테이퍼</strong>로 안전한 근관 성형.</p>' ),
		array( 'WaveOne', 'preserve', '왕복 운동 근관 파일 시스템', '<p>WaveOne Gold. <strong>왕복 회전</strong>으로 파절 위험 최소화.</p>' ),
		array( 'Reciproc', 'preserve', '왕복 근관 파일 시스템', '<p>VDW Reciproc Blue. 단일 파일 · <strong>왕복 운동</strong> 시스템.</p>' ),
		array( 'HyFlex CM', 'preserve', '컨트롤드 메모리 NiTi 파일', '<p>HyFlex CM. <strong>형상기억 조절</strong> NiTi. 곡선 근관에 강함.</p>' ),
		array( 'XP-endo', 'preserve', '팽창형 신개념 근관 파일', '<p>XP-endo Finisher·Shaper. <strong>체온에서 팽창</strong>해 근관 벽 접촉 최적.</p>' ),
		array( 'ProGlider', 'preserve', '패스파인더 · 초기 근관 확보', '<p>ProGlider. NiTi 재질 <strong>패스파인더</strong>. 안전한 근관 초기 확대.</p>' ),
		array( '스카우팅', 'preserve', '근관 초기 탐색 · 수동 파일 사용', '<p>Scouting. <strong>#08·#10 수동 파일</strong>로 근관 입구 탐색.</p>' ),
		array( '글라이드 패스', 'preserve', 'NiTi 회전 파일 전 필수 확보', '<p>Glide Path. NiTi 회전 파일 사용 전 <strong>수동 파일로 활주로 확보</strong>.</p>' ),
		array( '치수강 개방', 'preserve', '신경치료 첫 단계 · 치수강 접근', '<p>Access Cavity. 치수강 <strong>지붕 제거</strong>. 근관 입구 노출.</p>' ),
		array( '치수 지붕', 'preserve', '치수강을 덮고 있는 상아질', '<p>Pulp Chamber Roof. 치수강 <strong>천장 부분</strong>. 완전 제거 필요.</p>' ),
		array( '근관 입구', 'preserve', '치수강에서 근관으로 이어지는 부위', '<p>Canal Orifice. 근관 <strong>입구 위치 확인</strong>이 신경치료의 시작.</p>' ),

		/* ==================== 임플란트 브랜드·모델 (신규) ==================== */
		array( 'BioHorizons', 'implant', '미국 임플란트 브랜드', '<p>BioHorizons. <strong>Laser-Lok 미세 표면 처리</strong>. 마모형 골 재생 유도.</p>' ),
		array( 'MIS 임플란트', 'implant', '이스라엘 임플란트 브랜드', '<p>MIS. <strong>C1·V3·M4</strong> 시스템. 유럽·중동 인기.</p>' ),
		array( 'BEGO Semados', 'implant', '독일 임플란트 브랜드', '<p>BEGO Semados. <strong>독일산 정밀 임플란트</strong>. Chairside 시스템.</p>' ),
		array( 'Camlog', 'implant', '스위스·독일 임플란트 브랜드', '<p>Camlog. <strong>Tube-in-tube 연결</strong>. 유럽 시장 강세.</p>' ),
		array( 'Astra Tech (Dentsply)', 'implant', 'OsseoSpeed 표면 임플란트', '<p>Astra Tech. Dentsply 소속. <strong>OsseoSpeed·MicroThread</strong> 표면 처리.</p>' ),
		array( 'Nobel Speedy', 'implant', '노벨 즉시 하중 시스템', '<p>Nobel Speedy Groovy·Replace. <strong>즉시 하중</strong> 최적화 시스템.</p>' ),
		array( 'MegaESSET', 'implant', '메가젠의 확장형 임플란트', '<p>MegaESSET. <strong>확장 나사산</strong>. 상악동 없이 상악에 사용.</p>' ),
		array( 'Nobel Active NP', 'implant', '노벨 액티브 좁은 지름 라인', '<p>Nobel Active Narrow Platform. 지름 3.5mm 이하. <strong>좁은 부위</strong>에 사용.</p>' ),
		array( 'ITI SP 라인', 'implant', '스트라우만 표준 시리즈', '<p>ITI Standard Plus. <strong>Tissue Level 표준</strong>. 30+ 년 임상.</p>' ),
		array( 'Straumann BL', 'implant', '스트라우만 본레벨 시리즈', '<p>Straumann Bone Level. 임플란트 상부가 <strong>골 수준에 위치</strong>. 심미부 유리.</p>' ),
		array( 'Straumann TL', 'implant', '스트라우만 티슈레벨 시리즈', '<p>Straumann Tissue Level. 임플란트 상부가 <strong>잇몸 수준에 위치</strong>. 관리 편의.</p>' ),
		array( 'Osseotite 표면', 'implant', 'BioHorizons의 표면 처리', '<p>Osseotite. <strong>이중 산부식</strong> 처리. 골 접촉 면적 극대화.</p>' ),
		array( 'TiUnite 표면', 'implant', '노벨 바이오케어 표면 처리', '<p>TiUnite. <strong>양극 산화 표면</strong>. 뼈세포 부착 촉진.</p>' ),
		array( 'PhosphoCer', 'implant', '메가젠의 표면 처리', '<p>Nano-CaP 코팅. <strong>칼슘 인산 나노 코팅</strong>. 초기 골유착 촉진.</p>' ),
		array( 'SLActive 활성 기간', 'implant', 'SLActive는 2주 내 사용 필수', '<p>SLActive는 <strong>제조 후 2주 내</strong> 사용해야 초친수성 유지.</p>' ),
		array( '나사산 각도', 'implant', '나사산의 절삭 각도', '<p>Thread Angle. <strong>절삭·유지력·응력 분산</strong>에 영향.</p>' ),
		array( '나사산 피치', 'implant', '나사산 간격', '<p>Thread Pitch. 좁은 피치 = <strong>골 접촉 면적↑</strong>·초기 고정력↑.</p>' ),
		array( '나사산 깊이', 'implant', '나사산 돌출 깊이', '<p>Thread Depth. 깊을수록 <strong>부드러운 뼈에 유리</strong>.</p>' ),
		array( '임플란트 리무버 키트', 'implant', '실패 임플란트 제거 도구', '<p>Removal Kit. <strong>파절·주위염 임플란트</strong> 손상 최소로 제거.</p>' ),
		array( '트레핀 버', 'implant', '골 원통 채취 · 임플란트 제거', '<p>Trephine Bur. <strong>원통형 삭제</strong>. 실패 임플란트 제거·자가골 채취.</p>' ),
		array( '오스테오톰', 'implant', '골 확장 · 상악동 거상 도구', '<p>Osteotome. <strong>골을 옆으로 밀어냄</strong>. 상악동 치조정 접근법.</p>' ),
		array( '임플란트 픽스처 마운트', 'implant', '임플란트 이송 도구', '<p>Fixture Mount. 임플란트 <strong>패키지에서 구강으로 이송</strong>.</p>' ),
		array( '임플란트 안장 (Saddle)', 'implant', '임플란트 상부 형태', '<p>Saddle Type. Ridge-lap·<strong>Modified saddle</strong>. 부위별 형태.</p>' ),
		array( 'ROI (Return on Investment)', 'implant', '임플란트 시술의 장기 경제성', '<p>임플란트 초기 비용 대비 <strong>10~20년 사용</strong> 시 저렴. 브릿지·틀니 대비 우수.</p>' ),
		array( '임플란트 vs 브릿지', 'implant', '단일 결손 시 선택 기준', '<p>임플란트: <strong>인접 치아 보존</strong>·수명 길다. 브릿지: 저렴·빠름·인접치 삭제 필요.</p>' ),
		array( '임플란트 vs 틀니', 'implant', '다수 결손 시 선택 기준', '<p>임플란트: <strong>안정성·저작력</strong> 우수. 틀니: 저렴·기간 짧음·잇몸뼈 흡수.</p>' ),

		/* ==================== 세라믹·보철 재료 (신규) ==================== */
		array( 'Feldspathic Porcelain', 'prosthetics', '전통 도재 · 최고 심미성', '<p>Feldspar 기반 <strong>가장 오래된 세라믹</strong>. 심미성 최고, 강도 낮음.</p>' ),
		array( 'Lithium Disilicate', 'prosthetics', 'e.max의 핵심 소재', '<p>Lithium Disilicate. <strong>고강도 유리 세라믹</strong>. e.max Press·CAD.</p>' ),
		array( 'Leucite Ceramic', 'prosthetics', 'Empress의 핵심 소재', '<p>Leucite-reinforced. IPS Empress. <strong>심미성 우수</strong>·중간 강도.</p>' ),
		array( 'Vita Enamic', 'prosthetics', '수지-세라믹 하이브리드', '<p>Vita Enamic. <strong>PICN (Polymer-Infiltrated Ceramic Network)</strong>. 유연·심미.</p>' ),
		array( 'Cerasmart', 'prosthetics', 'GC의 하이브리드 세라믹', '<p>GC Cerasmart. <strong>레진-세라믹 하이브리드</strong>. 밀링 우수.</p>' ),
		array( 'Katana Zirconia', 'prosthetics', 'Kuraray Noritake 지르코니아', '<p>Katana. <strong>Multi-layer 지르코니아</strong>. 자연스러운 색상 그라디언트.</p>' ),
		array( 'e.max Press', 'prosthetics', '주조식 e.max', '<p>e.max Press. <strong>왁스 소실 주조</strong>. 라미네이트에 이상적.</p>' ),
		array( 'e.max CAD', 'prosthetics', 'CAD/CAM 밀링식 e.max', '<p>e.max CAD. <strong>밀링용 블록</strong>. CEREC·chairside 제작.</p>' ),
		array( 'PMMA 블록', 'prosthetics', 'CAD/CAM 밀링용 아크릴', '<p>PMMA Block. <strong>임시 크라운·트라이인</strong> 밀링용.</p>' ),
		array( '왁스 블록', 'prosthetics', 'Waxup 밀링용', '<p>Wax Block. <strong>디지털 왁스업</strong> 밀링. 주조·프레스 준비.</p>' ),
		array( 'Bulk-fill Composite', 'prosthetics', '한 번에 4mm까지 광경화', '<p>Bulk-fill. <strong>4mm까지 광경화</strong>. 층별 쌓기 불필요. 시간 단축.</p>' ),
		array( 'Flowable Composite', 'prosthetics', '유동성 있는 레진', '<p>Flowable. <strong>낮은 점도·주입 편리</strong>. 미세한 부위·언더컷.</p>' ),
		array( 'ORMOCER', 'prosthetics', '유기-무기 하이브리드 레진', '<p>Organically Modified Ceramic. Admira. <strong>낮은 수축·생체친화성</strong>.</p>' ),
		array( 'Compomer', 'prosthetics', '컴포짓 + GIC 하이브리드', '<p>Compomer. <strong>불소 방출 + 레진 특성</strong>. 소아·치경부.</p>' ),
		array( 'Ceram X', 'prosthetics', 'Dentsply의 나노 레진', '<p>Ceram X. <strong>나노 세라믹</strong> 강화 레진.</p>' ),
		array( 'Filtek', 'prosthetics', '3M의 대표 레진', '<p>3M Filtek. <strong>Z350·Universal·Bulk Fill</strong> 시리즈.</p>' ),
		array( 'Tetric', 'prosthetics', '이보클라의 대표 레진', '<p>Ivoclar Tetric. <strong>EvoCeram·Bulk Fill·N-Ceram</strong> 시리즈.</p>' ),
		array( 'Gradia Direct', 'prosthetics', 'GC의 심미 레진', '<p>GC Gradia. <strong>Direct·Plus 시리즈</strong>. 다양한 색상.</p>' ),
		array( 'Estelite', 'prosthetics', 'Tokuyama의 초구형 필러 레진', '<p>Estelite Sigma Quick. <strong>초구형 필러</strong>. 광택·색 안정성.</p>' ),
		array( 'A2 셰이드', 'prosthetics', '가장 흔한 자연치 색상', '<p>A2. <strong>가장 자연스러운 성인 앞니 색</strong>. 표준 색상.</p>' ),
		array( 'Vita Classical', 'prosthetics', '전통 셰이드 가이드', '<p>Vita Classical. A1~D4. <strong>전 세계 표준</strong> 치아색 가이드.</p>' ),
		array( 'Vita 3D-Master', 'prosthetics', '최신 셰이드 시스템', '<p>Vita 3D-Master. <strong>3차원 색 매트릭스</strong>. 정밀 매칭.</p>' ),

		/* ==================== 시멘트 브랜드 (신규) ==================== */
		array( 'RelyX Unicem', 'prosthetics', '3M의 셀프에칭 레진 시멘트', '<p>3M RelyX Unicem. <strong>Self-adhesive resin cement</strong>. 지르코니아·PFM 접착.</p>' ),
		array( 'Multilink Automix', 'prosthetics', 'Ivoclar의 자동 혼합 시멘트', '<p>Ivoclar Multilink. <strong>자동 혼합·듀얼 큐어</strong>. 심미부 크라운.</p>' ),
		array( 'Panavia', 'prosthetics', 'Kuraray의 대표 접착 시멘트', '<p>Panavia V5·SA. <strong>MDP 모노머</strong>·지르코니아 강력 접착.</p>' ),
		array( 'GC Fuji II LC', 'prosthetics', '레진 강화 GIC · 접착 시멘트', '<p>GC Fuji II LC. <strong>레진 강화 GIC</strong>. 불소 방출·크라운 시멘팅.</p>' ),
		array( 'Ketac Cem', 'prosthetics', '전통 GIC 접착 시멘트', '<p>3M Ketac Cem. <strong>전통 GIC</strong>. 저렴·불소 방출.</p>' ),
		array( 'Zinc Phosphate', 'prosthetics', '전통 시멘트 · PFM 크라운', '<p>Zinc Phosphate. <strong>가장 오래된 시멘트</strong>. 이제는 GIC·레진으로 대체.</p>' ),
		array( 'Vitremer', 'prosthetics', '레진 강화 GIC', '<p>3M Vitremer. <strong>레진 강화 GIC</strong>. 소아·치경부 충전.</p>' ),
		array( 'IRM', 'prosthetics', '중간 수복재 · 임시 충전', '<p>Intermediate Restorative Material. <strong>ZOE 강화</strong>. 임시 충전·라이너.</p>' ),
		array( 'ZOE 시멘트', 'prosthetics', 'Zinc Oxide Eugenol', '<p>ZOE. <strong>진정 효과</strong>. 임시 크라운·근관 임시 충전.</p>' ),

		/* ==================== 치과 장비 브랜드 (신규) ==================== */
		array( 'KaVo', 'general', '독일 프리미엄 치과 장비', '<p>KaVo Dental. 독일. <strong>유닛체어·핸드피스·이미징</strong> 세계 최고.</p>' ),
		array( 'NSK', 'general', '일본 대표 치과 장비', '<p>NSK Nakanishi. 일본. <strong>고속 핸드피스·초음파</strong> 강세.</p>' ),
		array( 'W&H', 'general', '오스트리아 치과 장비', '<p>W&H. 오스트리아. <strong>Piezomed·핸드피스</strong>.</p>' ),
		array( 'Bien-Air', 'general', '스위스 정밀 핸드피스', '<p>Bien-Air. 스위스. <strong>정밀 고속·초음파</strong>.</p>' ),
		array( 'Sirona (Dentsply Sirona)', 'general', '독일 이미징·CAD/CAM 리더', '<p>Dentsply Sirona. <strong>CEREC·Orthophos·Primescan</strong>.</p>' ),
		array( 'i-CAT', 'general', '미국 CBCT 브랜드', '<p>Imaging Sciences. <strong>CBCT의 원조</strong>. 정밀 3D 이미징.</p>' ),
		array( 'Vatech', 'general', '국산 이미징 장비 대표', '<p>바텍. 국산 <strong>CBCT·파노라마</strong>. 세계 시장 점유.</p>' ),
		array( 'Dentsply Sirona Primescan', 'general', '고속 정밀 구강 스캐너', '<p>Primescan. <strong>파우더 없이 고속 스캔</strong>. 정밀도 최고.</p>' ),
		array( '3Shape TRIOS', 'general', '덴마크의 대표 구강 스캐너', '<p>3Shape TRIOS. <strong>실시간 컬러 스캔</strong>. 교정·보철.</p>' ),
		array( 'Medit i500', 'general', '한국 구강 스캐너', '<p>Medit i500·i700. <strong>국산 스캐너</strong>. 가성비 우수.</p>' ),
		array( 'iTero', 'general', 'Align Technology의 스캐너', '<p>iTero Element. <strong>Invisalign 통합</strong>. 시뮬레이션 즉시.</p>' ),
		array( 'Cerec Primemill', 'general', 'Sirona 밀링 머신', '<p>Primemill. Chairside 밀링. <strong>당일 크라운 제작</strong>.</p>' ),
		array( '3D 프린터 SLA', 'general', '광경화 3D 프린터', '<p>Stereolithography. <strong>UV 광경화</strong>. Formlabs·SprintRay.</p>' ),
		array( '3D 프린터 DLP', 'general', '디지털 광 프로세서 프린터', '<p>DLP. <strong>SLA보다 빠름</strong>. 임시 크라운·가이드 인기.</p>' ),

		/* ==================== 세부 병리·해부 (신규) ==================== */
		array( 'Papilloma', 'surgery', '구강 유두종 · 양성 종양', '<p>Papilloma. HPV 관련. <strong>돌기 모양 병변</strong>. 외과적 절제.</p>' ),
		array( 'Fibroma', 'surgery', '섬유종 · 흔한 양성 종양', '<p>Fibroma. 자극성 <strong>섬유 조직 증식</strong>. 볼·혀에 흔함.</p>' ),
		array( 'Torus Palatinus', 'general', '상구개 정중선 골 융기', '<p>Torus Palatinus. <strong>구개 중앙 골 융기</strong>. 양성. 틀니 방해 시 절제.</p>' ),
		array( 'Torus Mandibularis', 'general', '하악 안쪽 골 융기', '<p>Torus Mandibularis. <strong>하악 안쪽 골 융기</strong>. 양성·좌우 대칭.</p>' ),
		array( 'Exostosis', 'general', '골 돌기 · 양성 골 증식', '<p>Exostosis. <strong>양성 골 증식</strong>. 필요 시 절제.</p>' ),
		array( 'Ranula', 'surgery', '설하선 낭종 · 혀 아래 물주머니', '<p>Ranula. <strong>혀 아래 파란색 낭</strong>. 설하선 관 손상·낭종화.</p>' ),
		array( 'Cellulitis', 'surgery', '연조직 급성 세균 감염', '<p>Cellulitis. <strong>얼굴 부기·발열</strong>. 즉시 항생제·필요 시 배농.</p>' ),
		array( 'Ludwig Angina', 'surgery', '구강저 연조직의 심각한 감염', '<p>Ludwig\'s Angina. <strong>구강저 광범위 감염</strong>. 기도 폐쇄 위험. 응급.</p>' ),
		array( 'Osteitis', 'surgery', '골염 · 뼈 염증', '<p>Osteitis. 뼈 표면 염증. <strong>Dry socket도 alveolar osteitis</strong>.</p>' ),
		array( '카포시 육종', 'surgery', 'AIDS 관련 혈관 종양', '<p>Kaposi Sarcoma. <strong>AIDS 환자</strong>에 흔한 구강 종양.</p>' ),
		array( '흑색종', 'surgery', '구강 악성 흑색 종양', '<p>Oral Melanoma. <strong>드물지만 매우 공격적</strong>. 흑색 병변 즉시 검사.</p>' ),
		array( '립카섬', 'surgery', '입술의 편평상피암', '<p>Lip Cancer. <strong>햇빛·흡연</strong> 위험. 아랫입술 흔함.</p>' ),
		array( '설암', 'surgery', '혀 편평상피암', '<p>Tongue Cancer. <strong>혀 옆면 흔함</strong>. 흡연·음주 위험.</p>' ),
		array( '구강저암', 'surgery', '구강저 편평상피암', '<p>Floor of Mouth Cancer. <strong>흡연·음주 강한 관련</strong>. 조기 발견 어려움.</p>' ),
		array( '치은암', 'surgery', '잇몸 편평상피암', '<p>Gingival Cancer. <strong>발치 후 안 아무는 상처</strong>가 신호.</p>' ),
		array( '경부 림프절 전이', 'surgery', '구강암의 림프절 전이', '<p>Cervical Lymph Node Metastasis. <strong>구강암 예후</strong>의 핵심 지표.</p>' ),
		array( 'HPV 관련 구인두암', 'surgery', 'HPV 감염으로 인한 편도암', '<p>HPV-related Oropharyngeal Cancer. <strong>편도·설근</strong> 흔함. 예후 상대적 양호.</p>' ),

		/* ==================== 해부학 세부 (신규) ==================== */
		array( '상악골', 'general', 'Maxilla · 위턱뼈', '<p>Maxilla. <strong>얼굴 중앙의 큰 뼈</strong>. 상악동·상악 치아 지지.</p>' ),
		array( '하악골', 'general', 'Mandible · 아래턱뼈', '<p>Mandible. <strong>얼굴에서 유일하게 움직이는 뼈</strong>. 하악 치아·저작 담당.</p>' ),
		array( '광대뼈', 'general', 'Zygomatic Bone · 관골', '<p>Zygomatic Bone. <strong>얼굴 옆·앞 광대</strong>. Zygomatic 임플란트 지지.</p>' ),
		array( '이부', 'general', 'Chin · 턱 아래 부위', '<p>Chin. <strong>하악골 앞부분</strong>. 이부성형술 대상.</p>' ),
		array( '이공', 'general', 'Mental Foramen · 이신경 통로', '<p>Mental Foramen. <strong>하악 소구치 근처</strong>. 이신경이 나오는 구멍.</p>' ),
		array( '이돌기', 'general', 'Mental Protuberance · 턱 앞 돌출', '<p>Mental Protuberance. <strong>턱 끝 앞쪽 돌출</strong>. 인간 고유.</p>' ),
		array( '하악 관절돌기', 'general', 'Condyle · 턱관절 골 부분', '<p>Mandibular Condyle. <strong>측두골 관절와와 만남</strong>. TMJ 형성.</p>' ),
		array( '관상돌기', 'general', 'Coronoid Process · 근육 부착 부위', '<p>Coronoid Process. <strong>측두근 부착</strong>. 하악 앞부분 상방.</p>' ),
		array( '하악지', 'general', 'Ramus · 하악의 세로 부분', '<p>Ramus. 하악골의 <strong>세로로 올라가는 부분</strong>. 저작근 부착.</p>' ),
		array( '하악체', 'general', 'Body · 하악의 수평 부분', '<p>Mandibular Body. 하악골 <strong>가로 부분</strong>. 치아 지지.</p>' ),
		array( '치조공', 'general', 'Alveolar Foramen · 하치조 신경 입구', '<p>Alveolar Foramen. <strong>하악지 안쪽</strong>. 하치조 신경·혈관 진입.</p>' ),
		array( '설공', 'general', 'Lingual Foramen · 설하 혈관 통로', '<p>Lingual Foramen. <strong>하악 정중 안쪽 미세 구멍</strong>. 설하 동맥.</p>' ),
		array( '경돌', 'general', 'Styloid Process · 측두골 돌기', '<p>Styloid Process. <strong>측두골에서 아래로 뻗은 돌기</strong>. 근육 부착.</p>' ),
		array( '측두골', 'general', 'Temporal Bone · 관자 뼈', '<p>Temporal Bone. <strong>관자놀이 뼈</strong>. 턱관절 상단 관절와.</p>' ),
		array( '접형골', 'general', 'Sphenoid Bone · 두개저 중앙', '<p>Sphenoid Bone. <strong>두개저 중앙</strong>. 여러 신경 통과.</p>' ),
		array( '경구개', 'general', 'Hard Palate · 딱딱한 입천장', '<p>Hard Palate. <strong>입천장 앞부분</strong>. 상악골·구개골로 형성.</p>' ),
		array( '연구개', 'general', 'Soft Palate · 부드러운 입천장', '<p>Soft Palate. <strong>입천장 뒷부분</strong>. 근육·점막. 삼킴·발음.</p>' ),
		array( '구개수', 'general', 'Uvula · 목젖', '<p>Uvula. <strong>목젖</strong>. 연구개 끝. 발음·삼킴 보조.</p>' ),
		array( '설소대', 'general', 'Lingual Frenum · 혀 아래 주름', '<p>Lingual Frenulum. <strong>혀와 구강저 연결 막</strong>. 짧으면 설소대 절제술.</p>' ),
		array( '순소대', 'general', 'Labial Frenum · 입술 안쪽 주름', '<p>Labial Frenulum. <strong>입술과 잇몸 연결 막</strong>. 앞니 사이 벌어짐 원인 가능.</p>' ),
		array( '구협 (편도 부위)', 'general', 'Isthmus of Fauces · 입에서 인두 경계', '<p>Isthmus of Fauces. <strong>구강과 인두 경계</strong>. 편도 위치.</p>' ),
		array( '설근', 'general', 'Base of Tongue · 혀 뿌리', '<p>Base of Tongue. <strong>혀 뒤 1/3</strong>. HPV 관련 암 호발 부위.</p>' ),
		array( '설유두', 'general', 'Lingual Papillae · 혀 표면 돌기', '<p>Papillae. <strong>4종 (Filiform·Fungiform·Foliate·Circumvallate)</strong>. 미각·촉각.</p>' ),
		array( '유곽유두', 'general', 'Circumvallate Papillae · 혀 뒤 큰 유두', '<p>Circumvallate. <strong>V자 배열·큰 유두</strong>. 쓴맛 감지.</p>' ),
		array( '엽상유두', 'general', 'Foliate Papillae · 혀 옆 주름', '<p>Foliate. <strong>혀 옆 주름</strong>. 신맛 감지.</p>' ),
		array( '심상유두', 'general', 'Fungiform Papillae · 버섯 모양', '<p>Fungiform. <strong>혀 앞·측면 버섯 모양</strong>. 단맛·짠맛.</p>' ),
		array( '사상유두', 'general', 'Filiform Papillae · 실 모양', '<p>Filiform. <strong>가장 흔한 유두</strong>. 촉각·마찰 기능. 미뢰 없음.</p>' ),
		array( '미뢰', 'general', 'Taste Bud · 맛봉오리', '<p>Taste Bud. <strong>유두 안 미각 수용체</strong>. 단·짠·신·쓴·감칠맛.</p>' ),

		/* ==================== 방사선 진단 세부 (신규) ==================== */
		array( '평행 촬영 기법', 'prevention', '치근단 X-ray 표준 방법', '<p>Paralleling Technique. 필름·<strong>치아 평행</strong> 배치. 정확한 이미지.</p>' ),
		array( '이등분 촬영 기법', 'prevention', '어린이 등에 사용', '<p>Bisecting Technique. 각도 조정 촬영. <strong>어린이·소구강</strong>.</p>' ),
		array( 'CBCT 절단면', 'prevention', 'Axial·Sagittal·Coronal', '<p>Axial(수평)·Sagittal(옆)·Coronal(앞). <strong>3방향 관찰</strong>.</p>' ),
		array( '판독 소프트웨어', 'prevention', 'CBCT 3D 분석 프로그램', '<p>Software. Simplant·R2GATE·<strong>OnDemand 3D</strong>.</p>' ),
		array( 'X-ray 필름 크기', 'prevention', '0·1·2·3·4 사이즈', '<p>Film Size. <strong>2번(성인 표준)</strong>·0번(소아). Universal 규격.</p>' ),
		array( 'CBCT FOV', 'prevention', 'Field of View · 촬영 범위', '<p>Field of View. <strong>부분·상하악·전체</strong>. 목적별 선택.</p>' ),
		array( '방사선 노출 시간', 'prevention', 'X-ray 촬영 지속 시간', '<p>Exposure Time. <strong>디지털은 밀리초 단위</strong>. 최소 노출.</p>' ),
		array( 'kVp (관전압)', 'prevention', 'X-ray 관전압 · 이미지 대비', '<p>Kilovoltage Peak. <strong>60~90kVp</strong>. 대비도 조절.</p>' ),
		array( 'mA (관전류)', 'prevention', 'X-ray 관전류 · 이미지 밀도', '<p>Milliampere. <strong>7~15mA</strong>. 이미지 밀도.</p>' ),
		array( '디지털 X-ray 센서', 'prevention', 'CCD·CMOS 센서 필름 대체', '<p>Digital Sensor. <strong>즉시 결과·저방사선·저장 편리</strong>.</p>' ),
		array( '인상 필름 (전통)', 'prevention', '전통 필름 · 현상 필요', '<p>Traditional Film. 현상액·인화지. <strong>디지털로 거의 대체</strong>.</p>' ),

		/* ==================== 치료 세부 절차 (신규) ==================== */
		array( '초진 상담 시간', 'general', '평균 30~60분', '<p>First Consultation. 문진·<strong>X-ray·구강 검사·치료 계획</strong>. 30~60분.</p>' ),
		array( '재진 예약', 'general', '2회차 이후 방문', '<p>Follow-up. <strong>단계별 진료</strong>. 계획된 치료 진행.</p>' ),
		array( '응급 방문', 'general', '심한 통증·외상 즉시 방문', '<p>Emergency. <strong>진통제 처방·응급 처치·정식 치료 예약</strong>.</p>' ),
		array( '진료 시간', 'general', '평균 30~90분 · 시술별 차이', '<p>발치 15~30분·크라운 60분·<strong>임플란트 30~120분</strong>.</p>' ),
		array( '치료 순서', 'general', '급성→만성→예방', '<p>Treatment Sequence. <strong>급성(통증) → 만성(치주) → 보철·예방</strong>.</p>' ),
		array( '치료 후 관찰 기간', 'general', '즉시 결과·3개월·6개월', '<p>Post-op Observation. <strong>즉시·1주·3개월·6개월</strong> 확인.</p>' ),
		array( '보증 기간', 'general', '보철 2~5년·임플란트 5~10년', '<p>Warranty. 병원별 다름. <strong>정기 검진 조건</strong>이 흔함.</p>' ),
		array( '재수술 조건', 'general', '보증 내 무료·이외 유료', '<p>Re-treatment. 병원 보증 기간 내 무료. <strong>환자 관리 소홀은 예외</strong>.</p>' ),

		/* ==================== 병원 서비스·운영 (신규) ==================== */
		array( '외국인 진료', 'general', '영어·일본어·중국어 지원', '<p>International Patient. <strong>다국어 상담원·영문 진료기록</strong>.</p>' ),
		array( '의료관광 코디네이터', 'general', '해외 환자 종합 지원', '<p>Medical Tourism Coordinator. <strong>진료·숙박·통역·관광 종합</strong>.</p>' ),
		array( '항공권 지원', 'general', '해외 환자 유치 프로그램', '<p>일부 병원 <strong>고액 시술 시 항공권 부분 지원</strong>. 사전 문의.</p>' ),
		array( '기업 진료', 'general', '단체 계약 진료', '<p>Corporate Dentistry. <strong>회사 단위 계약</strong>·직원 할인·정기 검진.</p>' ),
		array( '단체 검진', 'general', '학교·회사 단체 구강 검진', '<p>Group Screening. <strong>학교·회사 정기 검진</strong>. 예방·조기 발견.</p>' ),
		array( '방문 진료', 'general', '거동 불편 환자 가정 방문', '<p>Home Visit. <strong>거동 불편 노인</strong>·중증 환자. 제한적 진료.</p>' ),
		array( '요양원 협진', 'general', '요양원 정기 방문 진료', '<p>Nursing Home. <strong>요양원 협약·정기 방문</strong>. 노인 구강 관리.</p>' ),
		array( '치과 이송 서비스', 'general', '거동 불편 환자 이송', '<p>Transportation. 일부 병원 <strong>차량 픽업</strong>. 사전 예약.</p>' ),
		array( '진료실 규모', 'general', '유닛체어 수·의료진 수', '<p>병원 규모. <strong>10 유닛·의사 5명</strong> = 대형. 중형 3~5 유닛.</p>' ),
		array( '진료 대기 시간', 'general', '평균 15분 이내', '<p>예약 시 <strong>10~15분 이내</strong>. 응급·초진 다소 길 수 있음.</p>' ),
		array( '전화 예약', 'general', '가장 흔한 예약 방법', '<p>Phone Booking. <strong>진료 시간 내</strong> 전화. 즉시 확답.</p>' ),
		array( '카카오톡 예약', 'general', '카톡 채널로 24시간 문의', '<p>KakaoTalk. <strong>24시간 문의</strong>. 진료시간 내 답변.</p>' ),
		array( '네이버 예약', 'general', '네이버로 24시간 자동 예약', '<p>Naver Reservation. <strong>24시간 자동</strong>. 즉시 확정.</p>' ),
		array( '온라인 상담', 'general', '홈페이지·챗봇 문의', '<p>Online Consultation. <strong>비대면 문의</strong>. 병원별 답변 시간.</p>' ),
		array( '카카오톡 상담', 'general', '카톡 채널로 실시간 상담', '<p>KakaoTalk Channel. <strong>실시간 문의</strong>. 사진 첨부 가능.</p>' ),
		array( '진료 확인서', 'general', '보험 청구·회사 제출용 서류', '<p>Medical Certificate. <strong>보험 청구·병가 신청</strong>. 요청 시 발급.</p>' ),
		array( '진단서', 'general', '의사 소견 공식 서류', '<p>Diagnosis Certificate. <strong>법적 효력 서류</strong>. 발급 유료.</p>' ),
		array( '소견서', 'general', '타 병원 의뢰용 서류', '<p>Referral Letter. <strong>다른 병원 협진</strong>·의뢰 시 제공.</p>' ),
		array( '영수증', 'general', '진료비 결제 증빙', '<p>Receipt. 실비보험·<strong>세액공제 필수</strong>. 연말정산 자료.</p>' ),
		array( '카드 결제', 'general', '병원 카드 결제 표준', '<p>Card Payment. <strong>대부분 병원</strong>. 실비 카드 사용 가능.</p>' ),
		array( '할부', 'general', '고액 진료 무이자 할부', '<p>Installment. <strong>3~24개월 무이자</strong>. 임플란트·교정 인기.</p>' ),
		array( '진료비 견적', 'general', '치료 전 상세 견적서', '<p>Estimate. <strong>단계별 비용·기간</strong> 서면 안내. 비급여 동의 필수.</p>' ),

		/* ==================== 진료 유형별 세부 (신규) ==================== */
		array( '심미 임플란트', 'aesthetic', '앞니 임플란트 · 심미 최우선', '<p>Aesthetic Implant. <strong>잇몸 형태·크라운 색상</strong>이 성패 좌우.</p>' ),
		array( '앞니 즉시 임플란트', 'implant', '앞니 발치 즉시 임플란트', '<p>Anterior Immediate Implant. <strong>심미 유지</strong>·소켓 실드 테크닉 활용.</p>' ),
		array( '소켓 실드 테크닉', 'implant', '치근 얇게 남겨 협측 골 보호', '<p>Socket Shield Technique. <strong>치근 협측 얇은 조각</strong> 남겨 골 유지.</p>' ),
		array( '즉시 임플란트 조건', 'implant', '무염증·충분한 골·1차 안정성', '<p>발치와 <strong>염증 없음·1차 안정성·주변 골 4벽</strong> 조건.</p>' ),
		array( '한 달 임플란트', 'implant', '조기 임플란트 · 발치 후 4~8주', '<p>Early Implant. 발치 후 <strong>4~8주</strong> 잇몸 회복 후 식립.</p>' ),
		array( '늦은 임플란트', 'implant', '발치 후 3~6개월 골 회복 후', '<p>Delayed Implant. <strong>완전한 골 회복 후</strong> 식립. 안전.</p>' ),
		array( '얇은 잇몸 표현형', 'implant', '심미 임플란트 주의 조건', '<p>Thin Biotype. <strong>퇴축·심미 문제</strong> 위험. 결합조직 이식 병행.</p>' ),
		array( '두꺼운 잇몸 표현형', 'implant', '심미 임플란트에 유리', '<p>Thick Biotype. <strong>퇴축 위험 낮음</strong>. 심미 결과 안정적.</p>' ),

		/* ==================== 소아치과 세부 (신규) ==================== */
		array( '유치 우식 진행 속도', 'pediatric', '영구치보다 3배 빠름', '<p>유치는 <strong>법랑질·상아질 얇음</strong>. 우식 진행 매우 빠름.</p>' ),
		array( '초기 유치 우식', 'pediatric', '흰 반점 단계', '<p>Early Childhood Caries. <strong>흰 반점</strong> 단계. 불소·식이 조절로 회복.</p>' ),
		array( '베이비 보틀 신드롬', 'pediatric', '우유병 우식과 동일', '<p>Baby Bottle Syndrome. <strong>수면 중 병 사용</strong>이 원인.</p>' ),
		array( '유아 첫 치과 방문', 'pediatric', '첫 이가 나온 후 6개월', '<p>First Dental Visit. <strong>첫 유치 맹출 후 6개월 이내</strong>. 예방·습관 형성.</p>' ),
		array( '어린이 마취 안전', 'pediatric', '체중별 용량·모니터링', '<p>Pediatric Anesthesia Safety. <strong>체중별 정확한 용량</strong>·산소포화도 모니터.</p>' ),
		array( '어린이 진정 종류', 'pediatric', 'N₂O·경구·IV·전신마취', '<p>Sedation Levels. <strong>Minimal·Moderate·Deep·GA</strong>. 상태별 선택.</p>' ),
		array( '유치 색상', 'pediatric', '영구치보다 흰색', '<p>Primary Tooth Color. <strong>영구치보다 흼</strong>. 영구치 나올 때 색 차이 정상.</p>' ),
		array( '어린이 X-ray 안전', 'pediatric', '납 앞치마·최소 노출', '<p>Pediatric X-ray. <strong>납 앞치마·갑상선 보호대·최소 촬영</strong>.</p>' ),
		array( '학교 검진', 'pediatric', '학교 단위 정기 검진', '<p>School Screening. <strong>초·중·고 정기 검진</strong>. 예방 교육 병행.</p>' ),
		array( '어린이 교정 시기', 'pediatric', '1단계 6~10세·2단계 12세+', '<p>Timing. <strong>1단계는 골격·습관·2단계는 배열</strong>.</p>' ),
		array( '유치 조기 상실 원인', 'pediatric', '우식·외상·유전', '<p>Causes. <strong>진행된 우식·외상·유치 결손</strong>.</p>' ),
		array( '유치 잔존', 'pediatric', '영구치 결손으로 유치 오래 유지', '<p>Retained Primary Tooth. <strong>영구치 결손</strong>으로 유치 오래 사용.</p>' ),
		array( '융합치 (유치)', 'pediatric', '유치 두 개가 합쳐진 상태', '<p>Fused Primary Teeth. <strong>영구치도 결손·중복</strong> 가능성.</p>' ),
		array( '이관성 치아', 'pediatric', '치아 안에 또 다른 치아', '<p>Dens in Dente. <strong>치아 발달 이상</strong>. 신경치료 어려움.</p>' ),

		/* ==================== 예방 세부 (신규) ==================== */
		array( '치과 예방 4대 요소', 'prevention', '식이·양치·불소·정기 검진', '<p>4 Pillars. <strong>식이 조절·올바른 양치·불소·정기 검진</strong>.</p>' ),
		array( '설탕 섭취와 우식', 'prevention', '설탕 빈도가 양보다 중요', '<p>Frequency > Amount. <strong>설탕 섭취 빈도</strong>가 우식과 강한 관련.</p>' ),
		array( '탄산음료와 치아', 'prevention', '산성·당 = 치아 손상 최고', '<p>Soda. <strong>산 부식 + 설탕</strong> = 이중 위험. 빨대·즉시 물 헹굼.</p>' ),
		array( '스포츠음료와 치아', 'prevention', '탄산음료 이상의 위험', '<p>Sports Drinks. <strong>산성 pH·당·자주 섭취</strong>. 매우 위험.</p>' ),
		array( '커피와 치아 착색', 'prevention', '매일 커피는 착색 원인', '<p>Coffee Staining. <strong>탄닌</strong>. 물로 헹굼·정기 스케일링.</p>' ),
		array( '와인과 치아', 'prevention', '레드 와인 착색·산 부식', '<p>Red Wine. <strong>탄닌 착색·산 부식</strong>. 마신 후 물 헹굼.</p>' ),
		array( '녹차와 치아 건강', 'prevention', '폴리페놀 = 항우식 효과', '<p>Green Tea. <strong>폴리페놀 = 세균 억제</strong>. 착색은 주의.</p>' ),
		array( '치즈와 치아 건강', 'prevention', '유제품 = 재광화 촉진', '<p>Cheese. <strong>칼슘·인·pH 상승</strong>. 식후 소량 섭취 좋음.</p>' ),
		array( '자일리톨 껌', 'prevention', '뮤탄스균이 대사 못하는 당', '<p>Xylitol Gum. <strong>식후 껌</strong>이 침 자극·재광화·항우식.</p>' ),
		array( '치실 vs 워터픽', 'prevention', '치실 = 인접면·워터픽 = 잇몸', '<p>치실은 <strong>인접면 플라크</strong>·워터픽은 <strong>잇몸 라인·큰 부위</strong>.</p>' ),
		array( '치실 사용 빈도', 'prevention', '하루 1회 이상', '<p>Frequency. <strong>하루 1회 이상</strong>. 자기 전 양치 전 사용.</p>' ),
		array( '치실 사용 시기', 'prevention', '양치 전 사용이 효과적', '<p>Timing. <strong>양치 전</strong> 사용해 잔여물 제거 후 양치.</p>' ),
		array( '전동 칫솔 사용법', 'prevention', '치아 하나씩 3초 정지', '<p>Electric Brush. <strong>치아마다 3초씩 정지</strong>·문지르지 않음.</p>' ),
		array( '3-3-3 양치 원칙', 'prevention', '하루 3회·식후 3분·3분 이상', '<p>3-3-3. <strong>하루 3회·식후 3분 이내·3분 이상</strong>.</p>' ),
		array( '2-2-2 양치 원칙', 'prevention', '하루 2회·2분 이상·2분 후 물 안 삼킴', '<p>2-2-2. 최소 <strong>하루 2회·2분 이상</strong>. 미국치과의사협회 권장.</p>' ),
		array( '수돗물 불소화', 'prevention', '충치 예방 공중보건 프로그램', '<p>Water Fluoridation. <strong>0.7~1.2 ppm</strong>. 한국은 시행 안 함.</p>' ),
		array( '식품 속 불소', 'prevention', '차·해산물에 자연 함유', '<p>차·해산물·<strong>일부 채소</strong>에 자연 불소 함유.</p>' ),
		array( '불소 도포 빈도', 'prevention', '3~12개월 주기', '<p>어린이 <strong>3~6개월</strong>·성인 6~12개월. 개인 위험도별.</p>' ),
		array( '불소 과다', 'prevention', '치아 형광증·불소증', '<p>Fluorosis. <strong>어릴 때 과도한 불소</strong>. 흰 반점·갈색 얼룩.</p>' ),
		array( '어린이 치약 사용량', 'prevention', '쌀알 크기~완두콩 크기', '<p>3세 미만 <strong>쌀알</strong>·3~6세 <strong>완두콩</strong>·6세+ 완전 사용.</p>' ),

		/* ==================== 치주 세부 (신규) ==================== */
		array( '치주 검사 6점', 'periodontics', '치아당 6개 부위 프로빙', '<p>6-Point Probing. <strong>협·중앙·설 x 근·원심</strong> = 6점. 종합 진단.</p>' ),
		array( '치주 유지관리 프로토콜', 'periodontics', '스케일링 + SRP + 광범위 관리', '<p>SPT. <strong>스케일링·SRP·광범위 폴리싱·불소</strong>. 3~6개월.</p>' ),
		array( '치주염 재발률', 'periodontics', '유지관리 없으면 5년 60%+', '<p>Recurrence. 유지관리 미시행 <strong>5년 60%+ 재발</strong>.</p>' ),
		array( '치주염과 심장병', 'periodontics', '치주 세균이 심혈관 영향', '<p>Perio-Cardio Link. <strong>P.gingivalis</strong> 등 세균이 혈관·심장 영향.</p>' ),
		array( '치주염과 당뇨', 'periodontics', '양방향 관련', '<p>Perio-Diabetes. <strong>치주염이 혈당 악화·당뇨가 치주 악화</strong>.</p>' ),
		array( '치주염과 임신', 'periodontics', '조산·저체중아 위험', '<p>Perio-Pregnancy. <strong>조산·저체중아 위험 증가</strong>. 임신 전 치주치료.</p>' ),
		array( '치주염과 알츠하이머', 'periodontics', '최근 연구 관련성 발견', '<p>Perio-Alzheimer. <strong>P.gingivalis가 뇌 발견</strong>. 관련성 연구 중.</p>' ),
		array( '치주 감염과 폐렴', 'periodontics', '흡인성 폐렴 원인 가능', '<p>Perio-Pneumonia. <strong>구강 세균 흡인</strong>이 노인 폐렴 원인.</p>' ),

		/* ==================== 심미 세부 (신규) ==================== */
		array( '스마일 유형별 잇몸 노출', 'aesthetic', 'Low·Medium·High Smile', '<p>Low(잇몸 안 보임)·<strong>Medium(1~2mm)</strong>·High(3mm+).</p>' ),
		array( 'e.max Ceram Layering', 'aesthetic', '지르코니아 코어에 e.max 도재', '<p>Layering. <strong>지르코니아 코어 + e.max 도재</strong>. 심미성·강도 균형.</p>' ),
		array( '심미 임플란트 크라운', 'aesthetic', '지르코니아 지대주 + 올세라믹', '<p>Aesthetic Implant Crown. <strong>지르코니아 지대주·올세라믹 크라운</strong>. 자연치 재현.</p>' ),
		array( 'BL0 셰이드', 'aesthetic', '가장 밝은 미백 셰이드', '<p>BL0. <strong>Vita Bleach Shade</strong>. 미백 후 이상적 밝기.</p>' ),
		array( 'BL1 셰이드', 'aesthetic', '두 번째로 밝은 미백 셰이드', '<p>BL1. 밝은 미백 후 색상.</p>' ),
		array( 'Zoom Whitening', 'aesthetic', 'Philips Zoom · 오피스 미백', '<p>Zoom!. <strong>Philips 오피스 미백</strong>. 광 활성 시스템.</p>' ),
		array( 'Kor Whitening', 'aesthetic', '심한 착색 미백 프로토콜', '<p>Kör Whitening. <strong>테트라사이클린 착색 미백</strong>. 최고 효과.</p>' ),
		array( 'Deep Bleaching', 'aesthetic', '심한 착색 심층 미백', '<p>Deep Bleaching. <strong>2주 이상 홈 + 오피스</strong>. 완전한 미백.</p>' ),
		array( '미백 후 유지', 'aesthetic', '커피·와인·담배 자제', '<p>Post-whitening Care. <strong>1주간 착색 음식 자제</strong>·홈 트레이 유지.</p>' ),
		array( '심미 지대주 색', 'aesthetic', '지르코니아 지대주로 자연스러움', '<p>Zirconia Abutment. <strong>금속 지대주 회색 비침 방지</strong>.</p>' ),

		/* ==================== 사이드바 SEO 검색어 (신규) ==================== */
		array( '천안 임플란트 잘하는 치과', 'implant', '천안 임플란트 전문 병원 찾기', '<p>천안에서 <strong>임플란트 전문 병원</strong> 선택 기준: 30여년 임상·CBCT·기공소·A/S.</p>' ),
		array( '천안 교정 잘하는 치과', 'ortho', '천안 교정 있는 병원', '<p>교정 <strong>인정의</strong> 확인·상담 후 결정.</p>' ),
		array( '천안 라미네이트 잘하는 치과', 'aesthetic', '천안 심미치과 선택', '<p>라미네이트 실적·<strong>최소 삭제 기술·기공소</strong> 확인.</p>' ),
		array( '아산 임플란트 잘하는 치과', 'implant', '아산 인근 임플란트 병원', '<p>아산 인근 <strong>임플란트 전문·차로 15~20분</strong> 거리 병원.</p>' ),
		array( '천안 야간 치과', 'general', '평일 야간 진료 치과', '<p>Night Clinic. <strong>평일 20:30까지 진료</strong> 병원. 직장인·학생 편리.</p>' ),
		array( '천안 응급 치과', 'general', '주말·공휴일 응급 치과', '<p>Emergency Dental. <strong>주말·공휴일 응급</strong>. 사전 문의 필수.</p>' ),
		array( '어린이 치과 천안', 'pediatric', '천안 소아치과 전문', '<p>Pediatric Dentist. <strong>소아치과</strong>. 어린이 친화 환경.</p>' ),
		array( '천안 사랑니 발치 잘하는 치과', 'surgery', '매복 사랑니 정밀 발치', '<p>Wisdom Tooth. <strong>CBCT·전문 의료진</strong>. 신경 손상 없는 안전 발치.</p>' ),
		array( '천안 신경치료 잘하는 치과', 'preserve', '보존과 전문 병원', '<p>Endodontics. <strong>보존과</strong>·CBCT·마이크로스코프.</p>' ),
		array( '천안 잇몸 치료', 'periodontics', '치주과 병원', '<p>Periodontics. <strong>치주과</strong>·SRP·수술적 치료.</p>' ),

		/* ==================== 특수 술식 (신규) ==================== */
		array( '즉시 부하 임플란트', 'implant', '식립 당일 임시 크라운', '<p>Immediate Loading. <strong>1차 안정성 35Ncm+</strong> 필수.</p>' ),
		array( '즉시 심미 임플란트', 'implant', '앞니 발치+식립+임시치아 당일', '<p>Immediate Aesthetic. <strong>발치·식립·임시 크라운 당일</strong>.</p>' ),
		array( '자가치아 이식술', 'implant', '자신의 사랑니를 어금니 부위로 이식', '<p>Auto-Transplantation. <strong>사랑니 → 어금니 결손 부위</strong>. 임플란트 대안.</p>' ),
		array( '치아 재식술', 'implant', '외상 탈구 치아 재이식', '<p>Tooth Replantation. <strong>1시간 내 재삽입·스플린트 고정</strong>.</p>' ),
		array( '치조제 신장술', 'implant', '수직 골 증대 · 골이식 조합', '<p>Vertical Ridge Augmentation. <strong>블록골 이식·GBR</strong> 조합.</p>' ),
		array( '수평 골 증대술', 'implant', '치조제 폭 확장', '<p>Horizontal Ridge Augmentation. <strong>블록골·리지 스플릿</strong>.</p>' ),
		array( '샌드위치 골이식', 'implant', '샌드위치 형태 골이식', '<p>Sandwich Osteotomy. <strong>부분 절골 후 골이식</strong>. 수직 골 증대.</p>' ),
		array( 'Le Fort I 골절제술', 'implant', '상악 골절제 · 재위치', '<p>Le Fort I. <strong>상악 골절제·재위치</strong>. 양악 수술의 일부.</p>' ),
		array( '하악지 시상 분할술', 'implant', 'BSSO · 하악 재위치 수술', '<p>Bilateral Sagittal Split Osteotomy. <strong>하악 후방·전방 이동</strong>. 양악의 일부.</p>' ),
		array( '악교정 수술 시기', 'ortho', '성장 완료 후 (18세+)', '<p>Orthognathic Timing. <strong>남 18세·여 16세 이후</strong>. 성장 완료 확인.</p>' ),
		array( '술 전 교정', 'ortho', '악교정 수술 전 교정', '<p>Pre-surgical Ortho. <strong>수술 전 6~18개월</strong> 교정으로 치아 정렬.</p>' ),
		array( '술 후 교정', 'ortho', '악교정 수술 후 마무리', '<p>Post-surgical Ortho. <strong>수술 후 3~6개월</strong> 교합 세밀 조정.</p>' ),
		array( '양악 수술 회복', 'ortho', '1~2주 부기·2개월 정상', '<p>Recovery. <strong>1~2주 부기 절정</strong>·2개월 정상 활동. 6개월 완전.</p>' ),

		/* ==================== 마취·통증 관리 세부 (신규) ==================== */
		array( '침 마취 부위', 'surgery', '치아 뿌리 근처 잇몸', '<p>Injection Site. <strong>협측 이행부·경사진 각도</strong>. 근단공 근처 도달.</p>' ),
		array( '마취 성공률', 'surgery', '침윤 95%+·전달 85%+', '<p>Success Rate. 침윤 마취 95%+, <strong>하치조 전달 마취 85%+</strong>.</p>' ),
		array( '마취 실패 원인', 'surgery', '해부학·급성 염증·불안', '<p>Failure Causes. <strong>급성 염증(산성 환경)·해부학 변이·환자 불안</strong>.</p>' ),
		array( '재주사 시점', 'surgery', '초기 마취 부족 시 5분 후', '<p>Re-injection. <strong>5분 후 무감각 확인</strong>·재주사 결정.</p>' ),
		array( '마취 지속 시간', 'surgery', '리도카인 60~90분·부피바카인 6~8시간', '<p>Duration. 리도카인 <strong>60~90분</strong>·아르티카인 90~180분·부피바카인 6~8시간.</p>' ),
		array( '마취 후 자극 조심', 'surgery', '입술·볼 씹지 않기', '<p>Post-anesthesia Care. <strong>완전 회복까지 뜨거운 음식·씹기 주의</strong>.</p>' ),
		array( '아나필락시스', 'surgery', '중증 알레르기 반응', '<p>Anaphylaxis. <strong>극히 드묾</strong>. 에피네프린·응급 처치.</p>' ),
		array( '마취제 vs 방부제', 'surgery', '알레르기는 방부제가 더 흔함', '<p>대부분 <strong>마취제가 아닌 방부제(메칠파라벤·나트륨 메타비설파이트)</strong> 알레르기.</p>' ),

		/* ==================== 자주 묻는 질문 (신규 · SEO 강화) ==================== */
		array( '임플란트 아프나요', 'implant', '수술 시 무통·수술 후 2~3일 통증', '<p>수술 자체는 <strong>무통 (국소마취)</strong>. 수술 후 2~3일 정상 통증.</p>' ),
		array( '임플란트 며칠 걸리나요', 'implant', '보통 3~6개월', '<p>골유착 <strong>3~6개월</strong>. 즉시 하중은 당일 임시치아·최종 3개월.</p>' ),
		array( '임플란트 몇 살까지 되나요', 'implant', '나이 제한 없음 · 건강 상태 중요', '<p>No Age Limit. <strong>90세도 가능</strong>. 골밀도·전신 건강 중요.</p>' ),
		array( '임플란트 평생 가나요', 'implant', '관리에 따라 10~20년+', '<p>Longevity. <strong>10~20년 이상</strong>. 정기 검진·구강위생 필수.</p>' ),
		array( '교정 몇 살에 시작하나요', 'ortho', '조기 6~10세·전체 12세+', '<p>Timing. <strong>1단계 6~10세·2단계 12세+</strong>. 상황별.</p>' ),
		array( '교정 아프나요', 'ortho', '조정 후 2~3일 압박감', '<p>Discomfort. <strong>초기·조정 후 2~3일 압박감</strong>. 대부분 적응.</p>' ),
		array( '교정 얼마나 걸리나요', 'ortho', '평균 18~30개월', '<p>Duration. <strong>부분 6개월~1년·전체 18~30개월</strong>. 케이스별.</p>' ),
		array( '투명교정 vs 브라켓', 'ortho', '심미·편의 vs 강도·비용', '<p>투명교정: <strong>심미·탈부착</strong>. 브라켓: <strong>정밀 이동·저렴</strong>.</p>' ),
		array( '치아 미백 아프나요', 'aesthetic', '일시적 시린 증상', '<p>Sensitivity. <strong>1~3일 시린이</strong>. 대부분 회복. 시린이 치약 도움.</p>' ),
		array( '치아 미백 유지 기간', 'aesthetic', '1~2년 후 재착색', '<p>Retention. <strong>1~2년</strong> 후 커피·와인·담배로 재착색.</p>' ),
		array( '스케일링 자주 해도 되나요', 'periodontics', '6~12개월 주기 안전', '<p>Frequency. <strong>6~12개월 안전</strong>. 치석 축적 정도에 따라.</p>' ),
		array( '스케일링 후 치아 벌어지나요', 'periodontics', '기존 치석 자리 노출', '<p>Post-scaling Gap. <strong>치석에 가려졌던 공간</strong>이 노출·정상.</p>' ),
		array( '사랑니 꼭 뽑아야 하나요', 'surgery', '문제 있을 때만 발치', '<p>Wisdom Tooth. <strong>매복·통증·낭종 위험</strong> 시 발치. 정상 맹출은 유지.</p>' ),
		array( '사랑니 몇 살에 뽑나요', 'surgery', '20대 초반 권장', '<p>Timing. <strong>18~25세</strong> 뿌리 완성 전 발치 회복 빠름.</p>' ),
		array( '치과 예약 없이 가도 되나요', 'general', '가능하지만 예약 권장', '<p>Walk-in. <strong>가능하지만 대기 오래</strong>. 예약 시 정시 진료.</p>' ),
		array( '치과 진료 후 밥 언제 먹나요', 'general', '마취 완전 회복 후', '<p>Post-treatment Eating. <strong>마취 회복 2~4시간 후</strong>. 부드러운 음식.</p>' ),
		array( '치과 진료 후 양치 언제 하나요', 'general', '발치·수술 후 24시간 자제', '<p>Post-op Brushing. <strong>발치 부위 24시간 자제</strong>·나머지는 정상.</p>' ),

		/* ==================== 특수 환자 진료 (신규) ==================== */
		array( '고혈압 환자 치과', 'general', '혈압 조절 후 진료', '<p>Hypertension. <strong>140/90 이하 조절 후</strong> 진료. 에피네프린 주의.</p>' ),
		array( '당뇨 환자 치과 주의', 'general', '혈당 조절·감염 예방', '<p>Diabetes. <strong>HbA1c 7% 이하</strong>·아침 식사 후·항생제 예방.</p>' ),
		array( '심장 판막 환자', 'general', '예방적 항생제 필수', '<p>Heart Valve. <strong>발치·잇몸 수술 전 항생제</strong> 예방.</p>' ),
		array( '인공 관절 환자', 'general', '수술 후 2년간 예방 항생제', '<p>Joint Replacement. <strong>수술 후 2년간</strong> 발치 시 예방 항생제.</p>' ),
		array( '혈액 투석 환자', 'general', '투석 다음 날 진료', '<p>Dialysis. <strong>투석 다음 날 진료</strong>. 항응고제 영향 고려.</p>' ),
		array( '항응고제 (와파린) 환자', 'general', 'INR 확인·유지 진료', '<p>Warfarin. <strong>INR 2~3 유지</strong>·임의 중단 X. 지혈 준비.</p>' ),
		array( '항혈소판제 (아스피린) 환자', 'general', '대부분 유지 · 지혈 준비', '<p>Aspirin. <strong>대부분 중단 X</strong>. 지혈제·거즈 준비.</p>' ),
		array( 'HIV 환자 치과', 'general', 'CD4 확인·정상 진료', '<p>HIV. <strong>CD4 200 이상</strong>이면 정상 진료. 무증상 대부분 문제 X.</p>' ),
		array( 'B형 간염 환자 치과', 'general', '표준 감염관리로 안전', '<p>Hepatitis B. <strong>표준 감염관리</strong>. 백신·글로브·오토클레이브.</p>' ),
		array( '항암치료 중 치과', 'general', '항암 시작 전 치아 정리', '<p>Cancer Treatment. <strong>항암 전 감염 치아 정리</strong>. 항암 중 응급만.</p>' ),
		array( '방사선 치료 후 치과', 'general', '골괴사 예방·발치 신중', '<p>Post-radiation. <strong>턱뼈 방사선 후 발치는 골괴사 위험</strong>. 신중.</p>' ),
		array( '골다공증 약물 (비스포스포네이트)', 'general', 'BRONJ 위험·발치 신중', '<p>BP Users. <strong>발치 전 사전 상담</strong>·BRONJ 위험 평가.</p>' ),
		array( '스테로이드 환자', 'general', '용량·기간 확인 · 감염 주의', '<p>Steroid Users. <strong>면역 억제·감염 위험</strong>. 항생제 예방 고려.</p>' ),
		array( '임산부 치과 치료', 'general', '2삼분기 가장 안전', '<p>Pregnancy. <strong>4~6개월 (2삼분기)</strong> 가장 안전. 응급은 어느 때든.</p>' ),
		array( '임산부 X-ray', 'general', '납 앞치마 후 필수 시 촬영', '<p>Pregnancy X-ray. <strong>납 앞치마 + 갑상선 보호</strong> 후 필요 시 안전.</p>' ),
		array( '임산부 마취', 'general', '리도카인·아르티카인 안전', '<p>Pregnancy Anesthesia. <strong>리도카인·아르티카인 카테고리 B</strong>. 안전.</p>' ),
		array( '수유 중 치과', 'general', '대부분 안전 · 항생제 주의', '<p>Breastfeeding. <strong>일반 진료 안전</strong>. 항생제·진통제 종류 주의.</p>' ),
		array( '자폐 스펙트럼 환자', 'general', '점진 노출·시각 지원', '<p>Autism. <strong>구조화·시각 지원·짧은 시간</strong> 진료. 소아치과.</p>' ),
		array( '치매 환자 치과', 'general', '보호자 동반·간단 진료', '<p>Dementia. <strong>보호자 동반·짧은 시간</strong>·중요 진료만.</p>' ),

		/* ==================== 노인 치과 세부 (신규) ==================== */
		array( '노인 치아 마모', 'general', '평생 사용으로 자연 마모', '<p>Attrition in Elderly. <strong>씹는 면 편평화·시린이</strong>. 크라운·보호.</p>' ),
		array( '노인 뿌리 우식', 'general', '잇몸 퇴축으로 치근 노출·우식', '<p>Root Caries. <strong>노인 흔한 우식</strong>. 불소도포·정기 관리.</p>' ),
		array( '노인 구강 건조증', 'general', '침 분비 감소·약물 부작용', '<p>Xerostomia. <strong>수분·타액 대체제·구강 보습제</strong>.</p>' ),
		array( '노인 임플란트 성공률', 'general', '건강 상태 좋으면 성인과 유사', '<p>Success Rate. <strong>전신 건강 좋으면 청년과 유사</strong>. 골질만 확인.</p>' ),
		array( '노인 틀니 관리', 'general', '매일 세척·야간 제거', '<p>Denture Care. <strong>매일 세척·야간 제거·물에 보관</strong>.</p>' ),
		array( '노인 연하 곤란', 'general', '삼킴 어려움 · 재활 필요', '<p>Dysphagia. <strong>치과·재활의학 협진</strong>. 저작 재활.</p>' ),
		array( '노인 흡인성 폐렴', 'general', '구강 세균 흡인 원인', '<p>Aspiration Pneumonia. <strong>구강 관리로 예방</strong>. 요양원 정기 관리.</p>' ),
		array( '노인 저작 능력', 'general', '치아 상실로 저작력 30% 감소', '<p>Chewing Ability. <strong>치아 20개 이상</strong>·저작력 유지 = 건강한 노년.</p>' ),
		array( '노인 영양과 구강', 'general', '치아 없으면 영양 불량 위험', '<p>Nutrition. <strong>저작 능력이 영양 상태</strong>와 직결.</p>' ),

		/* ==================== 응급 상황 (신규) ==================== */
		array( '치아 완전 탈구 응급', 'general', '1시간 내 재식이 관건', '<p>Tooth Avulsion. <strong>우유·생리식염수·침 보관 → 1시간 내 재식</strong>.</p>' ),
		array( '치아 파절 응급', 'general', '파편 보관·응급 방문', '<p>Tooth Fracture. <strong>파편 보관·즉시 응급 치과</strong>.</p>' ),
		array( '얼굴 부기 응급', 'general', '치성 감염 · 즉시 항생제', '<p>Facial Swelling. <strong>치성 감염 확산</strong>. 즉시 항생제·배농.</p>' ),
		array( '지속 출혈 응급', 'general', '거즈 압박 · 지속 시 방문', '<p>Persistent Bleeding. <strong>거즈 1시간 압박</strong>·지속 시 응급 방문.</p>' ),
		array( '심한 치통 응급', 'general', '진통제 후 응급 방문', '<p>Severe Pain. <strong>이부프로펜·타이레놀·응급 방문</strong>. 진통제만으론 원인 해결 X.</p>' ),
		array( '봉합사 이탈', 'general', '재봉합 or 경과 관찰', '<p>Suture Loss. <strong>담당의 확인</strong>·필요 시 재봉합.</p>' ),
		array( '크라운 이탈', 'general', '보관 후 즉시 방문 · 재접착', '<p>Crown Loss. <strong>크라운 보관·즉시 방문 → 재접착</strong>.</p>' ),
		array( '충전물 이탈', 'general', '단기간 방문 예약', '<p>Filling Loss. <strong>수일 내 방문</strong>. 노출 부위 청소만 유의.</p>' ),
		array( '드라이 소켓 응급', 'general', '발치 후 3~5일 심한 통증', '<p>Dry Socket. <strong>발치 3~5일 후 극심 통증</strong>. 즉시 방문·처치.</p>' ),
		array( '턱관절 급성 통증', 'general', '이완·냉찜질·응급 방문', '<p>Acute TMJ. <strong>부드러운 음식·냉찜질·이부프로펜</strong>·응급 진료.</p>' ),
		array( '입 안 벌어짐 응급', 'general', '즉시 방문 · 진정 하 재위치', '<p>TMJ Lock. <strong>즉시 진료·진정 하 재위치</strong>.</p>' ),
		array( '알레르기 반응 응급', 'general', '두드러기·부기·호흡곤란', '<p>Allergic Reaction. <strong>119·에피네프린</strong>. 즉시 응급.</p>' ),

		/* ==================== 성형·심미 세부 (신규) ==================== */
		array( '보톡스 사각턱', 'aesthetic', '교근에 보톡스 · 얼굴 슬림화', '<p>Masseter Botox. <strong>사각턱 축소</strong>. 3~6개월 효과.</p>' ),
		array( '보톡스 잇몸 미소', 'aesthetic', '윗입술 근육에 보톡스', '<p>Gummy Smile Botox. <strong>거상근에 보톡스</strong>. 잇몸 노출 감소.</p>' ),
		array( '필러 잇몸 재건', 'aesthetic', '블랙 트라이앵글 필러', '<p>Gingival Filler. <strong>필러로 잇몸 유두 재건</strong>. 심미.</p>' ),
		array( '입술 필러', 'aesthetic', '입술 두께·모양 개선', '<p>Lip Filler. <strong>히알루론산 필러</strong>. 6~12개월 효과.</p>' ),
		array( '스마일 리프트', 'aesthetic', '보톡스+필러 종합 미소 개선', '<p>Smile Lift. <strong>보톡스·필러·치과 종합</strong>. 젊은 미소 회복.</p>' ),

		/* ==================== 세부 진료 이슈 (신규) ==================== */
		array( '입 냄새 원인', 'general', '80% 이상 구강 원인', '<p>Halitosis Causes. <strong>치주염·설태·충치·구강 건조 = 80%</strong>·소화기 20%.</p>' ),
		array( '설태 제거 방법', 'general', '혀 클리너·부드러운 칫솔', '<p>Tongue Cleaning. <strong>혀 클리너·부드러운 칫솔·매일</strong>.</p>' ),
		array( '침샘 마사지', 'general', '침 분비 촉진', '<p>Salivary Gland Massage. <strong>이하선·악하선 마사지</strong>·침 분비 촉진.</p>' ),
		array( '구강 건조증 약물', 'general', 'Pilocarpine·Cevimeline', '<p>Sialagogue. <strong>필로카핀·세비메린</strong>·타액 분비 자극.</p>' ),
		array( '인공 타액', 'general', '구강 건조증용 대체제', '<p>Artificial Saliva. <strong>Biotene·구강 스프레이</strong>·즉각 완화.</p>' ),
		array( '입술 관리', 'general', '립 밤·자외선 차단', '<p>Lip Care. <strong>립 밤·SPF 립스틱</strong>·입술암 예방.</p>' ),
		array( '입술 헤르페스 관리', 'general', '아시클로버 크림', '<p>Cold Sore. <strong>아시클로버 크림·구강 항바이러스제</strong>. 재발 억제.</p>' ),

		/* ==================== 재료 세부 (신규) ==================== */
		array( '금 함량', 'prosthetics', '18K·22K·순금', '<p>Gold Content. <strong>18K·22K·순금</strong>. 강도·순도 균형.</p>' ),
		array( '골드 크라운 종류', 'prosthetics', 'Type II·III·IV', '<p>Gold Types. <strong>Type IV (경도)</strong>가 크라운 표준.</p>' ),
		array( '알루미나 크라운', 'prosthetics', 'In-Ceram · 심미 세라믹', '<p>Alumina. <strong>In-Ceram 계열</strong>. 지르코니아 이전 심미 크라운.</p>' ),
		array( '실릴레이트 시멘트', 'prosthetics', '전통 심층 라이너', '<p>Silicate Cement. <strong>구식 라이너</strong>·불소 방출. 이제 GIC로 대체.</p>' ),
		array( 'CAD 소프트웨어', 'prosthetics', 'inLab·Exocad·3Shape Design', '<p>Sirona inLab·<strong>Exocad·3Shape Design</strong>. CAD 설계 대표.</p>' ),
		array( 'CAM 소프트웨어', 'prosthetics', '밀링 머신 제어 소프트웨어', '<p>hyperDENT·<strong>Millbox</strong>. CAD 결과를 밀링 지시.</p>' ),
		array( '밀링 시간', 'prosthetics', '단일 크라운 15~30분', '<p>Milling Time. <strong>15~30분/개</strong>. 재료·복잡도별.</p>' ),
		array( '시적 시 조정', 'prosthetics', '적합도·교합·색 확인', '<p>Try-in. <strong>변연 적합·교합·색 확인</strong>·미세 수정.</p>' ),
		array( '보철 접착 순서', 'prosthetics', '시적·에칭·본딩·시멘팅', '<p>Bonding Sequence. <strong>시적 → 부식 → 본딩 → 시멘팅 → 광경화</strong>.</p>' ),

		/* ==================== 새로운 기술 (신규) ==================== */
		array( '3D 프린팅 임시 크라운', 'general', '당일 임시 크라운 제작', '<p>3D Printed Provisional. <strong>SprintRay·Formlabs</strong>. 당일 제작.</p>' ),
		array( '3D 프린팅 서지컬 가이드', 'general', '임플란트 수술 가이드', '<p>Surgical Guide. <strong>CBCT + CAD → 3D 프린팅</strong>. 정밀 수술.</p>' ),
		array( '3D 프린팅 리테이너', 'general', '투명 리테이너 3D 제작', '<p>3D Retainer. <strong>디지털 인상 → 3D 프린팅</strong>. 정밀·빠름.</p>' ),
		array( '3D 프린팅 얼라이너', 'general', '직접 3D 프린팅 얼라이너', '<p>Direct 3D Aligner. <strong>최신 기술</strong>. 아직 상용화 초기.</p>' ),
		array( 'AI 판독 임플란트', 'general', '임플란트 위치·주위염 AI 감지', '<p>AI Implant Analysis. <strong>실패 예측·조기 감지</strong>.</p>' ),
		array( 'AI 판독 우식', 'general', 'X-ray에서 AI 우식 감지', '<p>AI Caries Detection. <strong>초기 우식 발견율↑</strong>.</p>' ),
		array( 'AI 스마일 시뮬레이션', 'general', 'AI 기반 심미 결과 예측', '<p>AI Smile Simulation. <strong>사진 업로드 → 결과 예측</strong>.</p>' ),
		array( '레이저 근관치료', 'general', '레이저 세척·소독', '<p>Laser Endo. <strong>Er:YAG·Nd:YAG</strong>. 근관 소독 강화.</p>' ),
		array( '레이저 잇몸 미백', 'general', '멜라닌 색소 레이저 제거', '<p>Laser Gum Whitening. <strong>Er:YAG·Diode</strong>. 즉시 효과.</p>' ),
		array( '레이저 잇몸 성형', 'general', '레이저로 잇몸 라인 조정', '<p>Laser Gingivectomy. <strong>절제·지혈 동시</strong>. 최소 침습.</p>' ),
		array( '레이저 구내염 치료', 'general', '레이저로 궤양 치유 촉진', '<p>Laser Aphthous Ulcer. <strong>즉시 통증 감소·치유 촉진</strong>.</p>' ),
		array( 'GBT (Guided Biofilm Therapy)', 'general', '가이드형 바이오필름 치료', '<p>GBT. EMS 프로토콜. <strong>바이오필름 시각화 → 에어폴리싱 → 스케일링</strong>.</p>' ),
		array( '광역동 치료', 'general', 'PDT · 광감각제 + 레이저', '<p>Photodynamic Therapy. <strong>메틸렌 블루 + 레이저</strong>. 항균·주위염 치료.</p>' ),
	);
}

/**
 * v3.35.4 · 4차 대량 확장 시드 마이그레이션.
 */
// v3.37.0 · admin_init로 이동
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_encyclopedia_v3354_expand' ) === 'done' ) return;
	if ( ! post_type_exists( 'md_term' ) ) return;

	$data = moondental_encyclopedia_seed_data_v3354();

	foreach ( $data as $t ) {
		list( $title, $cat, $excerpt, $body ) = $t;

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
		}
	}

	update_option( 'moondental_encyclopedia_v3354_expand', 'done' );
}, 50 );
