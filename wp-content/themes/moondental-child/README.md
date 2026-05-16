# Moon Dental Child Theme

한아의료재단 **문치과병원** Astra 자식 테마. 따뜻한 동네 치과 디자인 시스템 + Pretendard 한글 폰트.

## 폴더 구조

```
moondental-child/
├── style.css                    # 디자인 토큰 + 모든 컴포넌트 CSS
├── functions.php                # 메뉴/Customizer/병원정보/단축코드
├── header.php                   # 유틸리티 바 + 메인 헤더 + 모바일 메뉴
├── footer.php                   # 푸터(병원정보·진료시간·메뉴·SNS)
├── front-page.php               # 홈페이지 (섹션 조립)
├── page.php                     # 일반 페이지 기본 템플릿
├── page-templates/
│   ├── page-service.php         # 진료영역(임플란트/교정 등) 페이지용
│   └── page-wide.php            # 풀너비 페이지(오시는길/시설 등)
├── template-parts/
│   ├── section-hero.php         # 홈 hero
│   ├── section-services.php     # 5개 진료영역 그리드
│   ├── section-doctor.php       # 대표원장 소개
│   ├── section-info.php         # 진료시간/전화/위치 3컬럼
│   ├── section-notices.php      # 최근 공지 3개
│   └── section-cta.php          # 하단 예약 CTA
└── assets/
    └── js/main.js               # 모바일 메뉴 + 스크롤 인터랙션
```

## 활성화 순서 (WP 관리자)

1. **외모 → 테마 → Moon Dental Child** 활성화
2. **외모 → 메뉴**
   - "주 메뉴" 만들기 → 위치를 **주 메뉴 (헤더)** 로 지정
   - 항목 예시: 병원소개 / 진료안내 / 의료진 / 시설 / 공지사항 / 오시는 길
3. **설정 → 읽기**
   - "홈페이지 표시"를 **정적 페이지**로 전환
   - 홈페이지: "홈" 페이지 새로 만들어 지정 (front-page.php가 자동 사용됨)
   - 글 페이지: "공지사항" 페이지 지정
4. **외모 → 사용자 정의하기 → 문치과병원 설정** 패널에서:
   - 병원 기본 정보 (병원명/전화/주소/대표/사업자번호)
   - 진료시간
   - SNS / 외부 링크 (카카오톡 채널, 네이버 플레이스, 인스타, 블로그)
   - 홈 — Hero 섹션 (메인 카피, 이미지)
   - 홈 — 의료진 섹션 (약력, 사진)
5. 진료 페이지 5개 생성 (슬러그 매칭):
   - `general` (일반진료) · `implant` (임플란트) · `ortho` (교정) · `aesthetic` (심미) · `pediatric` (소아·예방)
   - 각 페이지 → 우측 패널 "페이지 속성 → 템플릿"에서 **진료 영역 페이지** 선택
6. `오시는 길` / `시설` 페이지는 템플릿 **풀 너비 페이지** 선택

## 단축코드

- `[moondental_info key="phone"]` — 병원 정보 출력 (phone, address, rep, hours_wd, name_short 등)

## 디자인 토큰 (style.css 상단)

- Primary: `#D88062` (warm coral)
- Accent : `#8FAE92` (soft sage)
- BG     : `#FFFAF4` / Surface: `#FBF2E8` / Alt: `#F5E8D8`
- Text   : `#3D2F26` (warm brown)
- 폰트   : Pretendard Variable

## 알려진 의존

- 부모 테마: **Astra** (free)
- 폰트 CDN: jsDelivr Pretendard (외부)

## 백업

이 폴더는 `c:\Users\user\Local Sites\moon-dental-hospital\app\public`에 git이 초기화되어 있고
`https://github.com/moonden93/moon-website` 로 푸시됩니다.
`wp-config.php` 와 워드프레스 코어, 서드파티 플러그인은 `.gitignore`로 제외됩니다.
