# Moon Dental Child Theme — 설치 가이드

## 1. 압축 해제 위치

이 폴더 전체(`moondental-child/`)를 다음 경로에 복사하세요:

```
C:\Users\<사용자명>\Local Sites\moondentalhospital\app\public\wp-content\themes\
```

복사 후 구조:
```
wp-content/themes/
├── astra/                  (부모 테마, 이미 설치됨)
└── moondental-child/       (← 이것이 새로 들어감)
    ├── style.css
    ├── functions.php
    └── README.md
```

## 2. WordPress 관리자에서 활성화

1. WP 관리자(`http://moondentalhospital.local/wp-admin`) 접속
2. **외모(Appearance) → 테마(Themes)**
3. **Moon Dental Child** 카드 찾기 → **활성화(Activate)** 클릭

## 3. 활성화 후 확인

- 페이지 열어서 폰트가 **Pretendard**로 표시되는지 확인
  (개발자 도구 → Elements → body에서 `font-family` 확인)
- 페이지 소스(Ctrl+U)에 다음 줄이 있는지 확인:
  ```
  https://cdn.jsdelivr.net/gh/orioncactus/pretendard/...
  ```

## 4. 다음 단계

1. **수정 필수**: `functions.php` 하단 `moondental_get_info()` 함수의 전화번호/주소/사업자번호를 실제 값으로 변경
2. **Elementor 글로벌 설정**: `SITE_SPEC.md`의 Phase 2 참고
3. **플러그인 설치**: KBoard, Contact Form 7, Yoast SEO, Smush, UpdraftPlus, Loginizer

## 트러블슈팅

**Q. 자식 테마 활성화 후 사이트가 깨졌어요**
→ functions.php의 PHP 문법 오류 가능성. 파일 첫 줄이 정확히 `<?php`인지, 마지막에 닫는 태그 없이 끝나는지 확인.

**Q. 폰트가 적용 안 돼요**
→ 브라우저 강력 새로고침(Ctrl+Shift+R). 그래도 안 되면 캐시 플러그인 비활성화 후 재시도.

**Q. Elementor에서 색상이 자동 반영 안 돼요**
→ Elementor 색상은 별도로 글로벌 설정에 입력해야 합니다(`SITE_SPEC.md` Phase 2-2 참고). CSS 변수와 Elementor 글로벌은 독립적으로 관리됩니다.
