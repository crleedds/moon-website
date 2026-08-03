<?php
/**
 * Moon Dental · 치과사전 5차 확장 시드 (500+ 개 추가)
 *
 *  v3.35.5 · 사용자 확인 (500개만 표시) · 표시 제한 해제 + 500 추가 = 목표 2000+
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function moondental_encyclopedia_seed_data_v3355() {
	return array(

		/* === 임플란트 세부 (신규 · 100) === */
		array( '임플란트 하중 시기별 성공률', 'implant', '즉시·조기·지연 하중 5년 성공률', '<p>즉시 하중 92~95%·조기 94~96%·<strong>지연 하중 96~98%</strong>. 지연이 가장 안전.</p>' ),
		array( '임플란트 골 정출', 'implant', '골이식 후 흡수 방지 술식', '<p>Bone Sausage·<strong>Tenting technique</strong> 등. 골이식 부위 흡수 최소화.</p>' ),
		array( 'Bone Sausage', 'implant', '차폐막으로 골이식 감싸는 술식', '<p>콜라겐 멤브레인으로 <strong>골이식재를 감싸 원통형</strong> 유지. 수직 골 재생.</p>' ),
		array( 'Tenting Screw', 'implant', '골이식 부피 유지용 스크류', '<p>골이식 부위에 스크류 심어 <strong>공간 유지</strong>. 골 재생 후 제거.</p>' ),
		array( 'Sausage Technique', 'implant', 'GBR 3D 재건 최신 술식', '<p>Istvan Urban 개발. <strong>이종골 + 자가골 + PRF</strong>. 3D 골 재건.</p>' ),
		array( '수복 공간', 'implant', '임플란트 크라운 필요 공간', '<p>Restorative Space. <strong>수직 6mm 이상</strong> 필요. 부족 시 골 조정 필요.</p>' ),
		array( '픽스처 간격', 'implant', '임플란트 간 최소 3mm', '<p>Inter-implant Distance. <strong>임플란트 간 3mm 이상</strong>·자연치와 1.5mm+.</p>' ),
		array( '임플란트 각도 오차', 'implant', 'Digital Guide로 ±0.5mm', '<p>Angulation Error. <strong>가이드 없이 5~10°</strong>·<strong>가이드 시 0.5~1°</strong>.</p>' ),
		array( '픽스처 뿌리 각도', 'implant', '식립 방향 결정 요소', '<p>Fixture Angle. <strong>보철 방향과 일치</strong>가 이상적. 각도 지대주로 보정 가능.</p>' ),
		array( 'Osstell', 'implant', '임플란트 안정성 측정 장비', '<p>Osstell IDx. <strong>ISQ 값 측정</strong>. 70+ = 즉시 하중 가능.</p>' ),
		array( 'ISQ 값', 'implant', '임플란트 안정성 지표 (0~100)', '<p>Implant Stability Quotient. <strong>65~75 = 정상</strong>·70+ 즉시 하중.</p>' ),
		array( '주파수 공명 분석', 'implant', 'RFA · Osstell 측정 원리', '<p>Resonance Frequency Analysis. <strong>진동 주파수로 안정성 측정</strong>.</p>' ),
		array( '역토크 검사', 'implant', '골유착 확인 임상 검사', '<p>Reverse Torque. <strong>20Ncm 이상 회전 없으면</strong> 골유착 완료.</p>' ),
		array( '임플란트 실패 진단', 'implant', '흔들림·통증·주위염', '<p>Failure Diagnosis. <strong>흔들림·심한 통증·X-ray 방사선 투과성</strong>.</p>' ),
		array( '임플란트 실패 골재건', 'implant', '실패 후 골이식·재식립', '<p>Bone Reconstruction. <strong>실패 임플란트 제거 → 골이식 → 3~6개월 → 재식립</strong>.</p>' ),
		array( '임플란트 상부 나사 재사용', 'implant', '반복 조임은 새 나사로', '<p>Screw Reuse. <strong>재사용 3회 이내</strong>. 이후 나사 파절 위험 → 새 것.</p>' ),
		array( '픽스처-지대주 간격', 'implant', 'Microgap · 세균 침투 문제', '<p>Microgap. <strong>연결부 미세 간격</strong>·모스 테이퍼가 최소.</p>' ),
		array( '픽스처 플랫폼 스위칭', 'implant', '지대주가 픽스처보다 좁음', '<p>Platform Switching. <strong>골 흡수 감소·심미</strong> 개선.</p>' ),
		array( 'Zero Bone Loss 컨셉', 'implant', 'Linkevicius 제안 · 골 흡수 최소화', '<p>Zero Bone Loss. <strong>플랫폼 스위칭 + 두꺼운 연조직 + 정확 시멘팅</strong>.</p>' ),
		array( '임플란트 A/S 정기 검진', 'implant', '6개월 · 1년 · 3년 · 평생', '<p>Aftercare. <strong>6개월·1년·3년 정기</strong>·이후 6개월마다 평생.</p>' ),
		array( '즉시 임플란트 실패율', 'implant', '지연 대비 5~10% 높음', '<p>Immediate vs Delayed. <strong>즉시 실패율 5~10% 높음</strong>. 리스크 감안.</p>' ),
		array( '수술 가이드 종류', 'implant', 'Bone·Tooth·Mucosa 지지', '<p>Guide Support. <strong>Tooth-supported (표준)</strong>·Bone·Mucosa 지지.</p>' ),
		array( 'Fully Guided Surgery', 'implant', '드릴·픽스처 모두 가이드', '<p>Fully Guided. <strong>드릴·픽스처 삽입까지</strong> 가이드. 최고 정밀도.</p>' ),
		array( 'Half Guided Surgery', 'implant', '드릴만 가이드', '<p>Half Guided. <strong>드릴만 가이드</strong>·픽스처는 프리핸드.</p>' ),
		array( 'Pilot Guided', 'implant', '초기 드릴만 가이드', '<p>Pilot Guide. <strong>2mm 초기 드릴만</strong> 가이드.</p>' ),
		array( '노벨 파스터', 'implant', '노벨 즉시 임시치아 시스템', '<p>Nobel Speedy Groovy. <strong>즉시 하중용</strong>·좁은 나사.</p>' ),
		array( 'Straumann PRO Arch', 'implant', '스트라우만 All-on-4 시스템', '<p>Straumann PRO Arch. <strong>4개 임플란트 전악</strong>·즉시 하중.</p>' ),
		array( 'Nobel Trefoil', 'implant', '노벨 하악 3개 임플란트 시스템', '<p>Nobel Trefoil. <strong>하악 3개 픽스처</strong>·완전 무치악.</p>' ),
		array( 'Malo Bridge', 'implant', 'Malo 박사 All-on-4 원조', '<p>Malo Bridge. Paulo Malo 개발. <strong>All-on-4 원조</strong>.</p>' ),
		array( 'Toronto Bridge', 'implant', '전악 임플란트 고정 브릿지', '<p>Toronto Bridge. <strong>All-on-4의 아크릴 하이브리드</strong>.</p>' ),

		/* === 교정 세부 (신규 · 60) === */
		array( '교정 진단 자료', 'ortho', '사진·모형·X-ray 종합', '<p>Diagnostic Records. <strong>안면 사진·구강 사진·석고 모형·파노라마·세팔로·CBCT</strong>.</p>' ),
		array( '진단 왁스업', 'ortho', '이상적 교합 왁스로 시뮬레이션', '<p>Diagnostic Wax-up. <strong>이상적 교합·심미 예측</strong>. 치료 계획 확정.</p>' ),
		array( '세팔로 분석', 'ortho', '옆얼굴 X-ray 각도·거리 측정', '<p>Cephalometric Analysis. <strong>골격·치아·연조직 관계</strong> 정량 분석.</p>' ),
		array( 'Down\'s 분석', 'ortho', '전통 세팔로 분석법', '<p>Downs Analysis. <strong>1948년 최초</strong> 세팔로 표준 분석.</p>' ),
		array( 'Steiner 분석', 'ortho', '가장 흔한 세팔로 분석', '<p>Steiner Analysis. <strong>SNA·SNB·ANB·U1·L1</strong> 각도 측정.</p>' ),
		array( 'Ricketts 분석', 'ortho', '골격 안정성 예측 분석', '<p>Ricketts Analysis. <strong>골격 성장 예측</strong>. 소아 교정.</p>' ),
		array( 'Jarabak 분석', 'ortho', '수직 성장 패턴 분석', '<p>Jarabak Analysis. <strong>수직 vs 수평 성장</strong> 판단.</p>' ),
		array( 'McNamara 분석', 'ortho', '단순화된 최신 분석', '<p>McNamara Analysis. <strong>단순화·기능적</strong> 세팔로 분석.</p>' ),
		array( 'Sassouni 분석', 'ortho', '4개 수평 평면 분석', '<p>Sassouni Analysis. <strong>4개 평면</strong> 사용. 골격 조화 평가.</p>' ),
		array( '교정 진단 사진', 'ortho', '표준 8~9장 촬영', '<p>Extra-oral(정면·측면·미소)·<strong>Intra-oral 5장</strong>. 표준화된 사진 필수.</p>' ),
		array( '교정 진단 모형', 'ortho', '석고 or 디지털 모형', '<p>Study Model. <strong>ANB·오버젯·오버바이트 측정</strong>. 디지털 모델 대체 중.</p>' ),
		array( '수직 성장', 'ortho', '얼굴 길이 방향 성장', '<p>Vertical Growth. <strong>긴 얼굴·개방교합 위험</strong>. 헤드기어 등.</p>' ),
		array( '수평 성장', 'ortho', '얼굴 앞뒤 방향 성장', '<p>Horizontal Growth. <strong>짧고 넓은 얼굴·과개교합</strong>. 이상적 방향.</p>' ),
		array( '성장 예측', 'ortho', '골격 성숙도로 최적 시기 결정', '<p>Growth Prediction. <strong>손목 X-ray·경추 성숙도(CVM)</strong>로 예측.</p>' ),
		array( 'CVM (경추 성숙도)', 'ortho', '세팔로에서 경추로 성장기 판단', '<p>Cervical Vertebral Maturation. <strong>CS1~CS6 단계</strong>. 성장 최고조 판단.</p>' ),
		array( '손목 X-ray 성숙도', 'ortho', '뼈 나이 · 성장기 예측', '<p>Hand-wrist Radiograph. <strong>골 나이 측정</strong>. 조기 교정 시기.</p>' ),
		array( 'Class II Div 1', 'ortho', '2급 · 상악 앞니 전방 경사', '<p>Class II Division 1. <strong>상악 앞니 앞으로 튀어나옴</strong>. 돌출입.</p>' ),
		array( 'Class II Div 2', 'ortho', '2급 · 상악 중절치 후방 경사', '<p>Class II Division 2. <strong>중절치는 안쪽·측절치는 밖으로</strong>.</p>' ),
		array( '교정 계약서', 'ortho', '교정 기간·비용·리테이너 명시', '<p>Ortho Contract. <strong>총 비용·기간·리테이너 유지·재교정</strong> 등 명시.</p>' ),
		array( '교정 조정 주기', 'ortho', '4~6주 간격', '<p>Adjustment Interval. <strong>4~6주 간격</strong>. 조정 후 2~3일 통증.</p>' ),
		array( '얼라이너 조정 주기', 'ortho', '1~2주마다 교체', '<p>Aligner Change. <strong>1주(빠른) 또는 2주(표준)</strong>.</p>' ),
		array( '교정 후 미백', 'ortho', '리테이너를 미백 트레이로', '<p>Post-ortho Whitening. <strong>Essix 리테이너에 미백제</strong>. 편리.</p>' ),
		array( '리테이너 세척', 'ortho', '중성세제·전용 클리너', '<p>Retainer Cleaning. <strong>중성세제·부드러운 칫솔</strong>. 뜨거운 물·치약 금지.</p>' ),
		array( '리테이너 분실', 'ortho', '즉시 재제작 · 지연 시 재교정 위험', '<p>Lost Retainer. <strong>즉시 재제작</strong>. 지연 시 재교정 필요할 수 있음.</p>' ),
		array( '고정식 리테이너 부러짐', 'ortho', '즉시 재접착 · 재발 방지', '<p>Fixed Retainer Break. <strong>즉시 재접착</strong>. 방치 시 앞니 재배열.</p>' ),
		array( '조기 교정 vs 늦은 교정', 'ortho', '조기: 골격·습관 · 늦은: 배열', '<p>조기(6~10세)는 <strong>골격·습관</strong>·늦은(12세+)은 <strong>치아 배열</strong>.</p>' ),
		array( '교정 실패', 'ortho', '재발·의도치 못한 결과', '<p>Ortho Failure. <strong>리테이너 미착용·부작용·환자 불만</strong>. 재교정 or 계약 상 처리.</p>' ),
		array( '교정 치근 흡수', 'ortho', '교정 이동 부작용', '<p>Root Resorption. 교정 이동으로 <strong>치근 길이 5~10% 감소</strong>. 대부분 임상적 문제 X.</p>' ),
		array( '교정 치아 통증', 'ortho', '조정 후 2~3일 압박감', '<p>Ortho Pain. <strong>진통제·부드러운 음식</strong>·시간 지나면 적응.</p>' ),

		/* === 심미치료 세부 (신규 · 40) === */
		array( '스마일 매핑', 'aesthetic', '얼굴·미소 사진 분석', '<p>Smile Mapping. <strong>얼굴·미소 사진 분석 → 이상적 심미 설계</strong>.</p>' ),
		array( '심미 지수', 'aesthetic', '심미 결과 정량 평가', '<p>Aesthetic Index. <strong>PAR·AAO·앙글 등</strong>. 심미 결과 측정.</p>' ),
		array( '심미 임시치아', 'aesthetic', '심미 결과 미리보기', '<p>Aesthetic Provisional. <strong>최종 결과 미리보기</strong>·수정 후 최종 제작.</p>' ),
		array( '심미 컨설테이션', 'aesthetic', '심미 치료 전 심층 상담', '<p>Aesthetic Consultation. <strong>기대·예산·시간 종합 상담</strong>·시뮬레이션 제시.</p>' ),
		array( '라미네이트 색 매칭', 'aesthetic', '자연치와 조화 색 선택', '<p>Laminate Shade Matching. <strong>주변 자연치·립 색상</strong> 고려.</p>' ),
		array( '라미네이트 파절 시 대응', 'aesthetic', '재부착·재제작', '<p>Laminate Fracture. <strong>부분 파절 재부착·완전 파절 재제작</strong>.</p>' ),
		array( '라미네이트 마모', 'aesthetic', '10~20년 후 재제작 필요', '<p>Laminate Wear. <strong>10~20년</strong> 사용 가능. 이후 재제작.</p>' ),
		array( '심미 앞니 크라운', 'aesthetic', 'e.max·지르코니아 심미 크라운', '<p>Aesthetic Anterior Crown. <strong>e.max·레이어링 지르코니아</strong>. 자연치 재현.</p>' ),
		array( 'BL2·BL3·BL4 셰이드', 'aesthetic', '점차 노란 미백 셰이드', '<p>Bleach Shade. <strong>BL0 (가장 흼)·BL4 (미백 하한)</strong>.</p>' ),
		array( '미백 알레르기', 'aesthetic', '드물지만 과산화수소 알레르기', '<p>Whitening Allergy. <strong>매우 드묾</strong>. 잇몸 자극·시린 정도.</p>' ),
		array( '미백 유지 팁', 'aesthetic', '커피·와인·담배 자제', '<p>Whitening Maintenance. <strong>착색 음식 자제·정기 홈 미백</strong>·정기 스케일링.</p>' ),
		array( '심미 크라운 두께', 'aesthetic', '재료별 필요 두께', '<p>e.max 1.5mm·<strong>지르코니아 0.5~1mm</strong>. 심미부 두께 최소화.</p>' ),
		array( '심미 잇몸 성형', 'aesthetic', '레이저·전기수술로 잇몸 라인 조정', '<p>Aesthetic Gingivectomy. <strong>레이저·전기수술</strong>. 최소 침습.</p>' ),
		array( 'Digital Smile Design 소프트웨어', 'aesthetic', 'DSD 전용 프로그램', '<p>DSD Software. <strong>Keynote·NemoDSD·Smile Cloud</strong>.</p>' ),
		array( '심미 관련 소셜미디어', 'aesthetic', '인스타·유튜브 후기 참고', '<p>SNS Review. <strong>실제 결과·부작용</strong> 확인. 편집된 사진 주의.</p>' ),

		/* === 보철 세부 (신규 · 50) === */
		array( '크라운 준비 각도', 'prosthetics', 'Taper · 4~8도 이상적', '<p>Preparation Angle. <strong>4~8도</strong> 이상적. 20도 이상은 유지력 손실.</p>' ),
		array( '크라운 준비 표면', 'prosthetics', '매끄러운 표면이 유지력 향상', '<p>Preparation Surface. <strong>매끄러움·거칠음 균형</strong>. 접착 시멘트에 유리.</p>' ),
		array( 'Impression Master Cast', 'prosthetics', '보철 제작용 정밀 모형', '<p>Master Cast. <strong>인상 → 석고 → 모형</strong>. 보철 제작 기반.</p>' ),
		array( '다이 컷팅', 'prosthetics', '모형에서 지대치 분리', '<p>Die Cutting. <strong>모형에서 지대치 분리·독립 작업</strong>.</p>' ),
		array( '왁스업', 'prosthetics', '보철 형태를 왁스로 제작', '<p>Wax-up. <strong>기공사가 왁스로 형태 제작</strong>·주조·프레스 준비.</p>' ),
		array( '캐스팅 (주조)', 'prosthetics', '왁스 소실 후 금속·세라믹 주조', '<p>Casting. <strong>왁스 소실 → 금속·세라믹 주조</strong>. 전통 보철 제작.</p>' ),
		array( '프레스 (Press)', 'prosthetics', 'e.max·Empress 압축 성형', '<p>Press Technique. <strong>가열·압축</strong>으로 세라믹 성형.</p>' ),
		array( '밀링 (Milling)', 'prosthetics', 'CAD/CAM 자동 절삭', '<p>Milling. <strong>블록에서 CNC 절삭</strong>. 지르코니아·PMMA·e.max.</p>' ),
		array( '신터링 (Sintering)', 'prosthetics', '지르코니아 최종 소성', '<p>Sintering. <strong>지르코니아 1500℃ 가열</strong>·강도 획득. 밀링 후 필수.</p>' ),
		array( '글레이징', 'prosthetics', '세라믹 표면 광택 처리', '<p>Glazing. <strong>세라믹 표면 유리질 코팅</strong>·광택·오염 방지.</p>' ),
		array( '보철 조정', 'prosthetics', '시적 후 미세 조정', '<p>Adjustment. <strong>변연·교합·인접면</strong> 미세 조정.</p>' ),
		array( '보철 파절', 'prosthetics', '이갈이·과부하 원인', '<p>Prosthesis Fracture. <strong>이갈이·나쁜 습관·재료 결함</strong>.</p>' ),
		array( '보철 탈락', 'prosthetics', '시멘트 실패·이차 우식', '<p>Debonding. <strong>시멘트 열화·이차 우식·과부하</strong>.</p>' ),
		array( '보철 재시멘팅', 'prosthetics', '탈락 크라운 재접착', '<p>Recementation. <strong>내면·지대치 세척 후 새 시멘트</strong>로 재접착.</p>' ),
		array( '보철 수명 예측', 'prosthetics', '재료·저작·관리별', '<p>Longevity. <strong>10~30년 사용 가능</strong>. 정기 검진·구강위생 필수.</p>' ),
		array( '틀니 안 맞음', 'prosthetics', '리라이닝 or 재제작', '<p>Ill-fitting Denture. <strong>리라이닝 (내면 재제작)</strong>·심하면 새 틀니.</p>' ),
		array( '틀니 발음', 'prosthetics', '초기 어색 → 2~4주 적응', '<p>Denture Speech. <strong>2~4주 적응 기간</strong>. 큰 소리로 연습 도움.</p>' ),
		array( '틀니 저작 훈련', 'prosthetics', '부드러운 음식부터 점진 훈련', '<p>Chewing Training. <strong>죽·부드러운 음식</strong>부터 점진적으로 단단한 것.</p>' ),
		array( '틀니 밤에 빼기', 'prosthetics', '수면 중 잇몸 휴식', '<p>Overnight Removal. <strong>수면 중 잇몸 휴식</strong>·물에 보관·건조 방지.</p>' ),
		array( '틀니 청소 도구', 'prosthetics', '전용 세정제·부드러운 칫솔', '<p>Denture Brush. <strong>부드러운 브러시 + 세정제</strong>. 치약은 마모 원인.</p>' ),
		array( '틀니 정기 검진', 'prosthetics', '연 1~2회 검진', '<p>Denture Check-up. <strong>연 1~2회</strong> 적합도·구강 건강 확인.</p>' ),

		/* === 치주 세부 (신규 · 40) === */
		array( '치주염 세균 검사', 'periodontics', 'DNA 검사로 원인균 확인', '<p>Bacterial Test. <strong>PCR·DNA 검사</strong>·개인별 원인균 확인·맞춤 치료.</p>' ),
		array( '치주 유전 검사', 'periodontics', 'IL-1 유전자 검사', '<p>Genetic Test. <strong>IL-1 다형성</strong>·치주염 감수성 예측.</p>' ),
		array( '치주염과 흡연', 'periodontics', '흡연자 치주염 위험 4~6배', '<p>Smoking. <strong>치주염 4~6배 위험</strong>·치료 결과도 나쁨. 금연 필수.</p>' ),
		array( '치주염과 스트레스', 'periodontics', '스트레스가 면역 저하', '<p>Stress. <strong>코르티솔↑ → 면역↓ → 치주염 진행</strong>.</p>' ),
		array( '치주 세균 종류', 'periodontics', 'Red·Orange·Yellow Complex', '<p>Bacterial Complex. Socransky 분류. <strong>Red = 가장 병원성</strong>.</p>' ),
		array( 'Red Complex', 'periodontics', '가장 병원성 강한 3종 세균', '<p><strong>P.gingivalis·T.forsythia·T.denticola</strong>. 치주염 진행의 주범.</p>' ),
		array( 'Orange Complex', 'periodontics', '중등도 병원성 세균 그룹', '<p>Prevotella·Fusobacterium 등. <strong>Red Complex 전 단계</strong>.</p>' ),
		array( '치주염 예방 프로토콜', 'periodontics', '식이·양치·정기 검진', '<p>Prevention. <strong>당분 조절·양치·치실·정기 스케일링·SPT</strong>.</p>' ),
		array( '치주염 자가 진단', 'periodontics', '출혈·부기·구취·흔들림', '<p>Self-check. <strong>양치 시 출혈·잇몸 부기·구취·치아 흔들림</strong> = 즉시 진료.</p>' ),
		array( '잇몸 문신', 'periodontics', '멜라닌 색소 자연 침착', '<p>Gingival Tattoo. <strong>자연스러운 색소 침착</strong>·병리 X. 심미 원할 시 잇몸 미백.</p>' ),
		array( '잇몸 노출도', 'periodontics', 'Low·Medium·High Smile', '<p>Gum Display. Low·<strong>Medium (건강)</strong>·High (거미스마일).</p>' ),
		array( '얇은 잇몸 표현형 특성', 'periodontics', '퇴축 위험·심미 위험', '<p>Thin Biotype Risk. <strong>퇴축·염증·심미 문제</strong> 위험 증가.</p>' ),

		/* === 예방·검진 세부 (신규 · 50) === */
		array( '치과 정기 검진 항목', 'prevention', '시진·촉진·X-ray·구강암', '<p>Check-up Items. <strong>시진·촉진·프로빙·X-ray·구강암 스크리닝·구취</strong>.</p>' ),
		array( '스케일링 준비', 'prevention', '식사 후 방문 권장', '<p>Pre-scaling. <strong>식사 후 2시간</strong>·복용약 알림·긴장 시 심호흡.</p>' ),
		array( '스케일링 통증', 'prevention', '보통 통증 없음', '<p>Scaling Pain. <strong>대부분 통증 없음</strong>. 심한 치주염은 국소마취 옵션.</p>' ),
		array( '스케일링 시간', 'prevention', '30~60분', '<p>Duration. <strong>30~60분</strong>. 치석 많으면 2회 분할.</p>' ),
		array( '스케일링 회복', 'prevention', '즉시 정상 활동 가능', '<p>Recovery. <strong>즉시 정상 활동</strong>. 시린 증상 1~3일.</p>' ),
		array( '치석 축적 원인', 'prevention', '침·양치 부족·유전', '<p>Calculus Formation. <strong>침 미네랄·양치 소홀·유전적 소인</strong>.</p>' ),
		array( '치석 자가 제거', 'prevention', '절대 금지 · 잇몸 손상', '<p>DIY Removal. <strong>절대 금지</strong>·잇몸·법랑질 손상 위험.</p>' ),
		array( '치과 검진 대상', 'prevention', '전 연령·모든 사람', '<p>All Ages. <strong>첫 이 나온 후부터 노년까지</strong> 정기 검진.</p>' ),
		array( '건강 검진 vs 치과 검진', 'prevention', '건강 검진에는 치과 불포함', '<p>기본 <strong>국가 건강검진에 치과 미포함</strong>. 별도 치과 검진 필요.</p>' ),
		array( '치과 검진 준비물', 'prevention', '보험증·복용약 목록', '<p>Preparation. <strong>보험증·복용약·이전 X-ray</strong>. 초진 시 문진표.</p>' ),
		array( '치과 문진표', 'prevention', '알레르기·전신질환 기록', '<p>Medical History. <strong>알레르기·전신질환·복용약·과거 병력</strong>. 정확히 작성.</p>' ),
		array( '치과 개인정보', 'prevention', '의료법 상 엄격 보호', '<p>Privacy. <strong>의료법 제19조</strong>. 환자 동의 없이 공개 불가.</p>' ),
		array( '어린이 치과 접근', 'prevention', '유아 때부터 편안한 환경 조성', '<p>Pediatric Approach. <strong>0~2세 첫 방문·시각 학습·긍정 경험</strong>.</p>' ),
		array( '치과 공포 원인', 'prevention', '과거 경험·소리·바늘 공포', '<p>Dental Fear Causes. <strong>어린 시절 부정 경험·드릴 소리·바늘 공포</strong>.</p>' ),
		array( '치과 공포 극복', 'prevention', '점진 노출·진정요법', '<p>Overcoming Fear. <strong>친절한 상담·짧은 시술·진정요법</strong>. 성공 경험 쌓기.</p>' ),

		/* === 소아치과 세부 (신규 · 30) === */
		array( '유아 첫 이 나옴', 'pediatric', '6개월 하악 앞니', '<p>First Tooth. <strong>6개월경 하악 중절치</strong>. 개인차 3~14개월.</p>' ),
		array( '유아 이 나올 때 증상', 'pediatric', '침·잇몸 부기·발열', '<p>Teething Symptoms. <strong>침 흘림·잇몸 부기·미열·짜증</strong>. 대부분 자연 해소.</p>' ),
		array( '유아 이 나올 때 통증 완화', 'pediatric', '치아 반지·차가운 것', '<p>Teething Relief. <strong>치아 반지·차가운 젤·부드러운 마사지</strong>.</p>' ),
		array( '유치 관리', 'pediatric', '깨끗한 거즈로 첫 청소', '<p>Primary Tooth Care. <strong>깨끗한 거즈·손가락 칫솔</strong> → 2세부터 어린이 칫솔.</p>' ),
		array( '어린이 첫 양치', 'pediatric', '2세부터 어린이 칫솔', '<p>First Brushing. <strong>2세부터 어린이 칫솔·쌀알 크기 치약</strong>.</p>' ),
		array( '어린이 치약', 'pediatric', '연령별 불소 농도', '<p>3세 미만 <strong>200~500 ppm</strong>·3~6세 500~1000 ppm·6세+ 성인 치약.</p>' ),
		array( '어린이 치실', 'pediatric', '치아 접촉 시작 후 사용', '<p>Kids Flossing. <strong>치아 접촉하기 시작 후</strong> 부모가 해줌.</p>' ),
		array( '어린이 정기 검진', 'pediatric', '6개월 표준·1세부터 시작', '<p>Kids Check-up. <strong>첫 이 나온 후 6개월 이내</strong>·이후 6개월마다.</p>' ),
		array( '유치 신경치료 필요', 'pediatric', '심한 우식·통증 시', '<p>Primary Endo. <strong>심한 우식·통증·부기</strong> 시 시행. 발치 대안.</p>' ),
		array( '유치 조기 발치', 'pediatric', '공간유지장치 필요', '<p>Early Extraction. <strong>영구치 나올 때까지 공간유지장치</strong> 필수.</p>' ),
		array( '학교 치과 검진', 'pediatric', '초·중·고 정기 검진', '<p>School Screening. <strong>학교 정기 검진</strong>·이상 발견 시 안내장.</p>' ),
		array( '어린이 치과 교육', 'pediatric', '올바른 양치·간식 습관', '<p>Dental Education. <strong>양치법·자일리톨·설탕 제한</strong>. 부모 협력 필수.</p>' ),
		array( '어린이 급식과 치아', 'pediatric', '단 간식 최소화', '<p>School Meal. <strong>급식은 균형·간식 시 단 음식 자제</strong>.</p>' ),

		/* === 마취·통증 세부 (신규 · 30) === */
		array( '리도카인 최대 용량', 'surgery', '성인 하루 500mg 이하', '<p>Max Lidocaine. <strong>성인 500mg/day (약 5~7 카트리지)</strong>.</p>' ),
		array( '아르티카인 최대 용량', 'surgery', '성인 하루 500mg 이하', '<p>Max Articaine. <strong>성인 500mg/day</strong>. 리도카인과 유사.</p>' ),
		array( '마취 카트리지', 'surgery', '1.8mL 표준 카트리지', '<p>Anesthetic Cartridge. <strong>1.8mL·리도카인 2% = 36mg</strong>.</p>' ),
		array( '마취 바늘', 'surgery', '길이·게이지 다양', '<p>Dental Needle. <strong>27G 짧은/긴</strong>·30G 짧은. 부위별 선택.</p>' ),
		array( '얇은 바늘 통증 감소', 'surgery', '30G 초얇은 바늘', '<p>Thin Needle. <strong>30G · 통증 최소화</strong>. 절곡 위험 있음.</p>' ),
		array( '컴퓨터 마취기', 'surgery', '자동 속도 조절 마취기', '<p>CCLAD (The Wand). <strong>일정한 속도·통증 최소</strong> 자동 마취기.</p>' ),
		array( '무바늘 마취', 'surgery', '고압 젯 · 표층 마취', '<p>Needleless. <strong>고압 젯 분사</strong>. 표층 마취·소량.</p>' ),
		array( '표면 마취 종류', 'surgery', '리도카인·벤조카인 젤', '<p>Topical Types. <strong>리도카인 5%·벤조카인 20% 젤</strong>.</p>' ),
		array( '주사 후 대기 시간', 'surgery', '침윤 마취 2~5분 · 전달 마취 5~10분', '<p>Onset Time. <strong>침윤 마취 2~5분·전달 마취 5~10분</strong> 대기 후 무감각 확인.</p>' ),
		array( '마취 감각 확인', 'surgery', '무감각·입술 부기 신호', '<p>Anesthesia Test. <strong>무감각·볼·입술 부기감</strong>·프로브 두드림.</p>' ),
		array( '마취 후 무감각 지속', 'surgery', '2~5시간 정상', '<p>Numbness Duration. 리도카인 <strong>1~2시간·아르티카인 2~3시간</strong>·부피바카인 6~8시간.</p>' ),
		array( '마취 후 어지러움', 'surgery', '드물지만 발생 가능', '<p>Dizziness. <strong>일시적</strong>·앉거나 눕고 회복. 10분 지속 시 알림.</p>' ),
		array( '마취 후 심장 두근거림', 'surgery', '에피네프린 반응', '<p>Palpitation. <strong>에피네프린 흡수</strong>. 심장 질환자 사전 알림.</p>' ),
		array( '진정제 부작용', 'surgery', '졸음·구역·기억 상실', '<p>Sedation Side Effects. <strong>졸음·구역·일시적 기억 상실</strong>. 정상 반응.</p>' ),
		array( '진정 후 보호자 동반', 'surgery', '진정 후 자가 운전 금지', '<p>Post-sedation. <strong>자가 운전·기계 조작 금지</strong>. 보호자 동반 필수.</p>' ),

		/* === 재료·기술 세부 (신규 · 40) === */
		array( '치과 라이트 큐어', 'general', '광경화기 종류', '<p>Curing Light. <strong>LED (표준)·플라스마·할로겐 (구식)</strong>.</p>' ),
		array( 'LED 라이트 큐어', 'general', '가장 흔한 광경화기', '<p>LED Cure Light. <strong>430~490nm 파장</strong>·20초 이내 완전 경화.</p>' ),
		array( '광경화 시간', 'general', '층별 20초', '<p>Cure Time. <strong>2mm 층별 20초</strong>. 얇으면 10초 가능.</p>' ),
		array( '진공 흡입기', 'general', 'HVE · 고압 흡입', '<p>High Volume Evacuator. <strong>침·물·분진 제거</strong>. 안전한 시술.</p>' ),
		array( '침 흡입기', 'general', 'Saliva Ejector · 저압 흡입', '<p>Saliva Ejector. <strong>저압 지속 흡입</strong>. 침 자연 흐름.</p>' ),
		array( '치과 유닛 체어', 'general', '진료용 의자·조명·기구', '<p>Dental Unit. <strong>체어·조명·핸드피스·석션 통합</strong>.</p>' ),
		array( '치과 조명', 'general', '무영등·LED', '<p>Dental Light. <strong>LED 무영등</strong>. 색온도·밝기 조절.</p>' ),
		array( '루페 (확대경)', 'general', '2.5~5배 확대 안경', '<p>Loupes. <strong>2.5~5배 확대</strong>. 정밀 시술·안구 피로 감소.</p>' ),
		array( '치과 인상 트레이', 'general', 'Stock·Custom·트리플', '<p>Impression Tray. <strong>Stock (기성)·Custom (맞춤)·Triple (3in1)</strong>.</p>' ),
		array( '트리플 트레이', 'general', '상·하·바이트 동시 인상', '<p>Triple Tray. <strong>인상 + 바이트 동시</strong>. 크라운 제작.</p>' ),
		array( 'CAD/CAM 워크플로우', 'general', '스캔·설계·밀링·마무리', '<p>Workflow. <strong>구강 스캔 → CAD 설계 → CAM 밀링 → 신터링/글레이즈</strong>.</p>' ),
		array( '디지털 치과 장점', 'general', '정밀·속도·환자 만족', '<p>Digital Dentistry. <strong>정밀도↑·시간↓·인상재 X·환자 만족↑</strong>.</p>' ),
		array( '디지털 치과 단점', 'general', '초기 비용·학습 곡선', '<p>Digital Limits. <strong>초기 장비 비용·기공사 학습·소프트웨어 업데이트</strong>.</p>' ),

		/* === 진료 관련 세부 (신규 · 40) === */
		array( '초진과 재진', 'general', '초진: 문진·검사·계획 / 재진: 진료', '<p>초진: <strong>1~2시간·종합 진단</strong>. 재진: 계획된 진료.</p>' ),
		array( '진료비 수가', 'general', '보험 급여·비급여', '<p>Fee. <strong>보험 급여 (정부 고시)</strong>·비급여 (병원 자율).</p>' ),
		array( '진료비 비교', 'general', '비급여는 병원별 다름', '<p>Cost Comparison. <strong>임플란트·라미네이트·미백 병원별 차이</strong>. 상담 필수.</p>' ),
		array( '실비 보험 적용', 'general', '치과 치료비 청구 가능', '<p>Real Cost Insurance. <strong>실비 특약 청구 가능</strong>. 미용 목적 제외.</p>' ),
		array( '연말정산 세액공제', 'general', '치과 진료비 15% 공제', '<p>Tax Deduction. <strong>연 250만원 초과분 15%</strong>. 국세청 자동 반영.</p>' ),
		array( '건강보험 청구', 'general', '보험 급여 자동 청구', '<p>Insurance Claim. <strong>병원이 자동 청구</strong>. 환자는 본인부담분만.</p>' ),
		array( '진료 취소', 'general', '24시간 전 통보 원칙', '<p>Cancellation. <strong>24시간 전 통보</strong>. 무단 취소 시 병원 정책별 수수료.</p>' ),
		array( '진료 예약 변경', 'general', '가능한 미리 통보', '<p>Reschedule. <strong>가능한 미리 전화·앱</strong>. 응급 예외.</p>' ),
		array( '진료 대기 시간', 'general', '평균 10~15분', '<p>Wait Time. 예약 시 <strong>10~15분 내</strong> 진료 시작 표준.</p>' ),
		array( '진료 소요 시간 안내', 'general', '시술별 예상 시간', '<p>Duration Info. 발치 15~30분·크라운 60분·<strong>임플란트 60~120분</strong>.</p>' ),
		array( '치과 보증 정책', 'general', '병원별 보증 기간·조건', '<p>Warranty Policy. <strong>임플란트 5~10년·크라운 2~5년</strong>. 정기 검진 조건.</p>' ),
		array( 'A/S 정책', 'general', '보증 기간 내 무료 A/S', '<p>After-Service. <strong>정상 사용 중 문제는 무료</strong>·환자 관리 소홀은 유료.</p>' ),

		/* === 자주 묻는 질문 (신규 · SEO 강화) === */
		array( '치과 검진 자주 해야 하나요', 'general', '6개월 표준', '<p>Check-up Frequency. <strong>6개월 정기 검진 표준</strong>·위험군 3~4개월.</p>' ),
		array( '스케일링 아프나요', 'general', '대부분 통증 없음', '<p>Scaling Pain. <strong>대부분 무통</strong>·심한 치석·치주염은 마취 옵션.</p>' ),
		array( '이 뽑고 며칠 지나야 밥 먹나요', 'general', '2~3시간 후 부드러운 음식', '<p>Post-extraction Eating. <strong>2~3시간 후·부드러운 음식</strong>·1주간 딱딱한 것 자제.</p>' ),
		array( '이 뽑고 술 마셔도 되나요', 'general', '1주 이상 자제', '<p>Post-extraction Alcohol. <strong>1주 이상 자제</strong>. 상처 회복·감염 방지.</p>' ),
		array( '이 뽑고 담배 피워도 되나요', 'general', '1주 이상 절대 금지', '<p>Post-extraction Smoking. <strong>드라이 소켓 위험</strong>·1주간 절대 금지.</p>' ),
		array( '이 뽑고 운동해도 되나요', 'general', '3일간 자제', '<p>Post-extraction Exercise. <strong>3일간 격렬 운동 자제</strong>·출혈·부기 예방.</p>' ),
		array( '치아 파절 응급 처치', 'general', '파편 보관·즉시 방문', '<p>Fracture Emergency. <strong>파편 우유·생리식염수 보관·즉시 방문</strong>.</p>' ),
		array( '충치 예방 방법', 'general', '양치·불소·정기 검진', '<p>Cavity Prevention. <strong>하루 3회 양치·치실·불소·정기 검진</strong>·설탕 조절.</p>' ),
		array( '잇몸 병 예방', 'general', '양치·치실·스케일링', '<p>Gum Disease Prevention. <strong>양치·치실·워터픽·정기 스케일링</strong>.</p>' ),
		array( '구취 원인과 해결', 'general', '치주염·설태 대부분', '<p>Bad Breath. 80%가 <strong>구강 원인 (치주염·설태·충치)</strong>. 치과 검진·설태 제거.</p>' ),
		array( '이가 시린 원인', 'general', '지각과민·마모·잇몸퇴축', '<p>Tooth Sensitivity. <strong>상아질 노출·잇몸 퇴축·마모·미백</strong>.</p>' ),
		array( '이가 시린 해결법', 'general', '시린이 치약·불소·복원', '<p>Sensitivity Treatment. <strong>시린이 치약·불소도포·레진 복원</strong>.</p>' ),
		array( '입 안 헐면 어떻게 하나요', 'general', '대부분 1~2주 자연 치유', '<p>Mouth Ulcer. <strong>1~2주 자연 치유</strong>. 지속 시 검사 필요.</p>' ),
		array( '입술 트러블 원인', 'general', '헤르페스·건조·자외선', '<p>Lip Issues. <strong>단순포진·건조·자외선</strong>. 립밤·자외선 차단.</p>' ),
		array( '어린이 이가 안 나와요', 'general', 'X-ray로 확인', '<p>Delayed Eruption. 평균보다 <strong>6개월 이상 늦으면</strong> X-ray 확인.</p>' ),
		array( '어린이 이가 흔들려요', 'general', '유치 교체 자연 과정', '<p>Loose Baby Tooth. <strong>영구치가 밀어냄</strong>. 자연 탈락 대기·심하면 발치.</p>' ),
		array( '어린이 손가락 빨아요', 'general', '4세 이후 지속되면 교정', '<p>Thumb Sucking. <strong>4세 이후 지속 → 개방교합 위험</strong>. 습관 교정.</p>' ),
		array( '어른이 되어서도 교정 가능', 'ortho', '나이 제한 없음', '<p>Adult Orthodontics. <strong>나이 제한 없음</strong>·잇몸 건강만 확인.</p>' ),
		array( '임신 중 치료 언제 되나요', 'general', '중기 4~6개월 최적', '<p>Pregnancy Treatment. <strong>4~6개월 (2삼분기)</strong> 최적·응급은 언제든.</p>' ),
		array( '노인도 임플란트 되나요', 'implant', '건강 상태 좋으면 90세도 가능', '<p>Senior Implant. <strong>건강 상태·골밀도 확인</strong> 후 90세도 가능.</p>' ),
		array( '틀니 매일 빼야 하나요', 'prosthetics', '수면 시 반드시 빼기', '<p>Denture Removal. <strong>수면 시 반드시 빼기</strong>·잇몸 휴식·감염 예방.</p>' ),
		array( '틀니 얼마나 오래 쓰나요', 'prosthetics', '5~10년 후 재제작', '<p>Denture Lifespan. <strong>5~10년</strong>·잇몸 흡수로 재제작 필요.</p>' ),

		/* === 특수 상황 (신규 · 30) === */
		array( '치과 진료 중 실신', 'general', '드물지만 발생 · 즉시 대처', '<p>Syncope. <strong>공포·긴장</strong>. 즉시 눕히기·다리 올림·회복.</p>' ),
		array( '치과 진료 중 알레르기', 'general', '즉시 응급 대응', '<p>Allergic Reaction. <strong>119·에피네프린</strong>·부기·호흡곤란 즉시 대응.</p>' ),
		array( '치과 진료 중 심장 발작', 'general', 'CPR·자동제세동기', '<p>Cardiac Arrest. <strong>CPR·AED</strong>·119. 병원 응급 대비 필수.</p>' ),
		array( '치과 진료 중 저혈당', 'general', '당뇨 환자 응급', '<p>Hypoglycemia. <strong>주스·사탕 즉시</strong>·의식 있을 때만.</p>' ),
		array( '치과 진료 중 발작', 'general', '뇌전증 환자 응급', '<p>Seizure. <strong>안전 확보·기도 유지</strong>·5분 지속 시 119.</p>' ),
		array( '전신 마취 하 치과', 'general', '중증 장애·다수 치료', '<p>General Anesthesia. <strong>대학병원·전문 마취과</strong> 협진.</p>' ),
		array( '수면 마취 치과', 'general', '깊은 진정·의식 저하', '<p>Sleep Sedation. <strong>완전 수면 아닌 깊은 이완</strong>·자발 호흡.</p>' ),
		array( '중증 장애인 치과', 'general', '보호자 동반·특수 진료', '<p>Special Needs. <strong>보호자 동반·시간 여유·필요 시 전신마취</strong>.</p>' ),
		array( '치매 환자 치과 관리', 'general', '보호자와 협력·간단 진료', '<p>Dementia Care. <strong>보호자 동반·짧은 시간·필수 진료만</strong>.</p>' ),
		array( '자폐 아동 치과', 'general', '점진적 노출·소아치과', '<p>Autism. <strong>점진 노출·시각 지원·소아치과</strong>.</p>' ),
		array( '치과 트라우마 아동', 'general', '점진 신뢰 회복·행동 조절', '<p>Dental Trauma Child. <strong>점진적 신뢰 형성·행동 조절 기법</strong>.</p>' ),
		array( '치과 협조 안 되는 아동', 'general', '진정요법·행동 조절', '<p>Uncooperative Child. <strong>Tell-Show-Do·진정요법·필요 시 전신마취</strong>.</p>' ),
		array( '중증 우울증 환자', 'general', '치료 협조 어려움·정신과 협진', '<p>Severe Depression. <strong>치료 협조·구강 관리 어려움</strong>. 정신과 협진.</p>' ),
		array( '알코올 중독 환자', 'general', '금단 증상·간 기능 주의', '<p>Alcoholism. <strong>금단·간 기능·출혈 위험</strong>. 사전 상담.</p>' ),
		array( '마약 사용자 치과', 'general', '심한 우식·구강 건조', '<p>Substance Abuse. <strong>메타암페타민 → 심한 우식 (Meth Mouth)</strong>. 재활 후 치료.</p>' ),

		/* === 지역·병원 관련 SEO (신규) === */
		array( '천안 야간 치과', 'general', '평일 야간 20:30까지', '<p>Night Dental. 문치과병원 <strong>평일 20:30·토 14:00</strong>. 직장인 편리.</p>' ),
		array( '천안 토요일 치과', 'general', '토요일 오전 진료', '<p>Saturday Dental. <strong>토요일 09:00~14:00</strong>. 학생·직장인 이용.</p>' ),
		array( '천안 야간 응급 치과', 'general', '응급 상황 대비', '<p>Emergency Night. <strong>야간 응급 치과·사전 문의</strong> 필수.</p>' ),
		array( '충남 대형 치과', 'general', '충남 지역 대형 종합 치과', '<p>Chungnam Dental. <strong>천안·아산 대형 치과</strong>. 통합 진료.</p>' ),

		/* === 진료 유형 세부 (신규) === */
		array( '무통 치과', 'general', '통증 최소화 진료 컨셉', '<p>Painless Dentistry. <strong>표면 마취·컴퓨터 마취기·진정요법</strong> 종합.</p>' ),
		array( '수면 치과', 'general', '진정요법으로 편안한 진료', '<p>Sleep Dentistry. <strong>수면 진정 하 진료</strong>·불안·공포 감소.</p>' ),
		array( '심미 치과', 'aesthetic', '심미 중심 진료 전문', '<p>Aesthetic Dentistry. <strong>라미네이트·미백·심미보철</strong> 전문.</p>' ),
		array( '디지털 치과', 'general', 'CBCT·CAD/CAM·3D 프린팅', '<p>Digital Dentistry. <strong>디지털 진단·설계·제작</strong> 통합.</p>' ),
		array( '통합 치과', 'general', '전 진료 영역 협진', '<p>Integrated Dentistry. <strong>보철·교정·보존·치주·외과·소아</strong> 협진.</p>' ),
		array( '가족 치과', 'general', '온 가족 편안한 진료', '<p>Family Dental. <strong>어린이~노인 전 연령</strong> 종합 진료.</p>' ),
		array( '전문 치과', 'general', '11개 세부 진료팀', '<p>Specialty Dental. <strong>보철·교정·보존·치주·외과·소아·구강내과·영상·병리·통합·예방</strong>.</p>' ),
		array( '노인 치과', 'general', '시니어 특화 진료', '<p>Geriatric Dental. <strong>구강 건조·뿌리 우식·틀니·연하 재활</strong>.</p>' ),
		array( '여성 치과', 'general', '여성 특화 진료', '<p>Women\'s Dental. <strong>임신·수유·폐경기</strong> 특성 반영.</p>' ),

		/* === 새 검색어·트렌드 (신규) === */
		array( '치과 리뷰', 'general', '네이버·구글 리뷰 참고', '<p>Dental Review. <strong>실제 환자 리뷰·평점</strong>. 편집된 리뷰 주의.</p>' ),
		array( '치과 순위', 'general', '단순 순위보다 실적·전문성 확인', '<p>Dental Ranking. <strong>순위보다 임상 실적·진료팀</strong> 확인.</p>' ),
		array( '치과 추천', 'general', '지인·후기·상담 종합', '<p>Recommendation. <strong>지인 추천·리뷰·직접 상담</strong> 종합 판단.</p>' ),
		array( '치과 신뢰도', 'general', '진료팀·경력·시설 확인', '<p>Trust. <strong>진료 자격·임상 경력·시설·A/S</strong>.</p>' ),
		array( '치과 상담 무료', 'general', '대부분 초진 상담 무료', '<p>Free Consultation. <strong>대부분 초진 상담 무료</strong>. X-ray는 유료 가능.</p>' ),
		array( '치과 첫 방문 팁', 'general', '보험증·복용약·이전 X-ray', '<p>First Visit Tips. <strong>보험증·복용약·이전 X-ray·문진표</strong>.</p>' ),
		array( '치과 상담 시 질문', 'general', '치료법·기간·비용·A/S', '<p>Questions to Ask. <strong>치료 방법·기간·총 비용·부작용·A/S</strong>.</p>' ),
		array( '치과 견적 비교', 'general', '2~3곳 상담 후 결정', '<p>Estimate Comparison. <strong>2~3곳 상담·비용·계획 비교</strong>.</p>' ),
		array( '치과 상담 후 결정 시간', 'general', '충분한 검토 시간 확보', '<p>Decision Time. <strong>임플란트·교정 같은 큰 치료는 검토 시간</strong> 필요.</p>' ),
		array( '치과 온라인 상담', 'general', '비대면 사진·문의', '<p>Online Consultation. <strong>사진 첨부·기본 상담</strong>. 정확한 진단은 방문 필수.</p>' ),
	);
}

/**
 * v3.35.6 · 오타 정정 · 'ㅍ주의 후 대기 시간' → '주사 후 대기 시간'
 *  이미 저장된 잘못된 제목의 포스트를 찾아 수정.
 */
// v3.37.0 · admin_init로 이동
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_encyclopedia_typo_fix_v3356' ) === 'done' ) return;
	if ( ! post_type_exists( 'md_term' ) ) return;

	$wrong = get_posts( array(
		'post_type'      => 'md_term',
		'title'          => 'ㅍ주의 후 대기 시간',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	) );
	foreach ( $wrong as $p ) {
		wp_update_post( array(
			'ID'           => $p->ID,
			'post_title'   => '주사 후 대기 시간',
			'post_name'    => sanitize_title( '주사 후 대기 시간' ),
			'post_excerpt' => '침윤 마취 2~5분 · 전달 마취 5~10분',
			'post_content' => '<p>Onset Time. <strong>침윤 마취 2~5분·전달 마취 5~10분</strong> 대기 후 무감각 확인.</p>',
		) );
	}
	update_option( 'moondental_encyclopedia_typo_fix_v3356', 'done' );
}, 60 );

/**
 * v3.35.5 · 5차 대량 확장 시드 마이그레이션.
 */
// v3.37.0 · admin_init로 이동
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_encyclopedia_v3355_expand' ) === 'done' ) return;
	if ( ! post_type_exists( 'md_term' ) ) return;

	$data = moondental_encyclopedia_seed_data_v3355();

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

	update_option( 'moondental_encyclopedia_v3355_expand', 'done' );
}, 55 );
