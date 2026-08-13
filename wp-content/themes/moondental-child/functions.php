<?php
/**
 * Moon Dental Child Theme — functions.php
 *
 * 한아의료재단 문치과병원
 * Astra 자식 테마 기능 정의
 *
 * @package moondental-child
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MOONDENTAL_VERSION', '3.44.164' );

/* v3.43.2 · 다국어 URL 접두어 · Polylang 리다이렉트 루프 회피
 *
 * 문제: Polylang이 영어 홈페이지를 '홈-english' 슬러그로 자동 생성 →
 *       /en/ → /홈-english/ → /en/홈-english/ 무한 리다이렉트
 *
 * 해결: (a) Polylang canonical 리다이렉트 완전 비활성
 *       (b) 순수 rewrite 규칙으로 /en/ /zh/ /vi/ /ru/ /mn/ 를 root 페이지로 라우팅
 *       (c) 언어는 URL 접두어에서 감지 (moondental_current_language())
 *       (d) md_content 번역 레이어가 실제 번역 표시
 *
 * 결과: 하나의 WP 페이지가 6개 언어로 노출. Polylang 페이지 복제 불필요. */

/* (a) Polylang canonical 우회 · 무한 리다이렉트 원천 차단 */
add_filter( 'pll_check_canonical_url', '__return_false' );
/* Polylang의 홈 URL 변환도 우회 · language switcher 링크 그대로 유지 */
add_filter( 'pll_home_url_black_list', function ( $list ) {
	return array_merge( (array) $list, array( array( 'function' => 'moondental_current_language' ) ) );
} );

/* (b) 언어 접두어 URL rewrite · /en/ /zh/ 등을 wp에 인식시킴 */
add_action( 'init', function () {
	$langs = 'en|ja|zh|vi|ru|mn';
	add_rewrite_rule( "^($langs)/?$",              'index.php',                        'top' );
	add_rewrite_rule( "^($langs)/(.+?)/?$",        'index.php?pagename=$matches[2]',   'top' );
} );
/* rewrite 규칙 최초 1회 flush */
add_action( 'admin_init', function () {
	if ( get_option( 'md_lang_rewrite_v3449_ja' ) === 'done' ) return;
	flush_rewrite_rules( false );
	update_option( 'md_lang_rewrite_v3449_ja', 'done' );
} );
/* frontend에서도 최초 1회 flush (관리자 접속 안 해도 라이브 즉시 반영) */
add_action( 'init', function () {
	if ( get_option( 'md_lang_rewrite_frontend_v3449_ja' ) === 'done' ) return;
	flush_rewrite_rules( false );
	update_option( 'md_lang_rewrite_frontend_v3449_ja', 'done' );
}, 999 );

/* (c) HTML <html lang="..."> 속성 · 감지된 언어 반영 */
add_filter( 'language_attributes', function ( $attrs ) {
	if ( ! function_exists( 'moondental_current_language' ) ) return $attrs;
	$lang = moondental_current_language();
	$map  = array( 'ko' => 'ko-KR', 'en' => 'en-US', 'ja' => 'ja-JP', 'zh' => 'zh-CN', 'vi' => 'vi', 'ru' => 'ru-RU', 'mn' => 'mn' );
	$html_lang = $map[ $lang ] ?? 'ko-KR';
	return preg_replace( '/lang="[^"]*"/', 'lang="' . $html_lang . '"', $attrs );
} );

/* (d) v3.43.3 · WordPress core canonical redirect도 언어 접두어 URL에서는 skip
 *     그렇지 않으면 /en/ 이 /홈-english/ 로 URL 재작성됨. */
add_filter( 'redirect_canonical', function ( $redirect_url, $requested_url ) {
	$path = parse_url( $requested_url, PHP_URL_PATH ) ?? '';
	if ( preg_match( '#^/(en|ja|zh|vi|ru|mn)(/|$)#', $path ) ) {
		return false;
	}
	return $redirect_url;
}, 10, 2 );

/* (e) v3.43.3 · Polylang의 홈-english 등 자동 duplicate 페이지가 있으면 프론트 URL 오염.
 *     Polylang이 자동으로 만든 페이지의 슬러그가 홈-english/홈-中文 등이면 우리 rewrite로 안 잡힘 → skip. */
add_filter( 'wpml_pll_seo_permalink', '__return_null' );
define( 'MOONDENTAL_DIR',     get_stylesheet_directory() );
define( 'MOONDENTAL_URI',     get_stylesheet_directory_uri() );

/* v3.42.4 · 충치·레진 탭 강제 sync 마이그레이션 (init 훅 · 프론트에서도 실행)
 * 저장된 price_tabs_all 안 '충치·레진' 탭 rows가 3줄 미만이거나
 * '레진 코어'·'유치 레진' 등 제거 대상을 포함하면 → default_decay(11항목)로 교체.
 * 다른 탭은 사용자 편집값 그대로 유지. 한 번만 실행. */
add_action( 'init', function() {
	if ( get_option( 'moondental_pricing_decay_v3425' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_price_tabs_all', '' );
	if ( ! $stored ) {
		update_option( 'moondental_pricing_decay_v3425', 'done' );
		return;
	}
	// 저장값 파싱 (== 탭 == 단위)
	$lines = preg_split( "/\r\n|\r|\n/", $stored );
	$tabs  = array();       // [ label => rows text ]
	$order = array();
	$cur   = null;
	foreach ( $lines as $l ) {
		if ( preg_match( '/^\s*==\s*(.+?)\s*==\s*$/', $l, $m ) ) {
			$cur = trim( $m[1] );
			if ( ! isset( $tabs[ $cur ] ) ) { $tabs[ $cur ] = ''; $order[] = $cur; }
			continue;
		}
		if ( $cur === null ) continue;
		$tabs[ $cur ] .= $l . "\n";
	}
	// '충치·레진' 탭 rows가 없거나 3줄 미만이면 신규 default로 교체
	$decay_key = null;
	foreach ( array( '충치·레진', '충치 · 레진', '레진', '충치' ) as $cand ) {
		if ( isset( $tabs[ $cand ] ) ) { $decay_key = $cand; break; }
	}
	// 신규 default_decay 값 (customizer-content.php와 동일)
	$new_decay = "치경부 마모증 (잇몸 경계 시린 부위) | 8만 원 | \n"
	           . "레진 · 한 면 충전 | 10만 원 | \n"
	           . "레진 · 한 면 (넓은 범위) | 15만 원 | \n"
	           . "레진 · 앞니 사이 틈 메우기 (정중이개) | 25만 원 | \n"
	           . "레진 · 두 면 충전 | 15만 원 | \n"
	           . "레진 · 두 면 (인접면 포함) | 30만 원 | \n"
	           . "레진 · 세 면 이상 | 30만 원 | \n"
	           . "레진 · 세 면 이상 (인접면 포함) | 35만 원 | \n"
	           . "레진 · 세 면 이상 (인접면 양쪽 포함) | 50만 원 | \n"
	           . "레진 · 치아 변색 (반점치) 부위 | 20만 원 | \n"
	           . "레진 비니어 (앞니 코팅) | 35만 원 | ";

	if ( $decay_key === null ) {
		$tabs['충치·레진'] = $new_decay . "\n";
		$order[] = '충치·레진';
	} else {
		$count = 0;
		foreach ( preg_split( "/\r\n|\r|\n/", $tabs[ $decay_key ] ) as $r ) {
			$r = trim( $r );
			if ( $r && strpos( $r, '|' ) !== false && strpos( $r, '#' ) !== 0 ) $count++;
		}
		// v3.42.5 · 제거 대상 + '씹는 면 포함' 옛 문구 감지 시 sync
		$has_deprecated = ( strpos( $tabs[ $decay_key ], '레진 코어' ) !== false
			|| strpos( $tabs[ $decay_key ], '신경치료 후 레진' ) !== false
			|| strpos( $tabs[ $decay_key ], '유치 레진' ) !== false
			|| strpos( $tabs[ $decay_key ], '럭사코어' ) !== false
			|| strpos( $tabs[ $decay_key ], '씹는 면 포함' ) !== false
			|| strpos( $tabs[ $decay_key ], '양쪽 옆면 모두' ) !== false );
		if ( $count < 3 || $has_deprecated ) {
			$tabs[ $decay_key ] = $new_decay . "\n";
		}
	}
	// 재조립 후 저장
	$rebuilt = array();
	foreach ( $order as $label ) {
		$rebuilt[] = '== ' . $label . " ==\n" . rtrim( $tabs[ $label ] );
	}
	set_theme_mod( 'md_content_price_tabs_all', implode( "\n\n", $rebuilt ) );
	update_option( 'moondental_pricing_decay_v3425', 'done' );
} );

/* 일회성 마이그레이션: 가격표 자유 편집 형식으로 통합 (v3.27.0)
 * 옛 필드 14개 (탭 7개 × label/rows) → 새 필드 1개 (price_tabs_all)
 * 사용자가 편집한 값이 있으면 새 형식으로 자동 변환. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_pricing_v3270' ) === 'done' ) return;
	// 이미 새 필드 값 있으면 스킵
	if ( get_theme_mod( 'md_content_price_tabs_all' ) ) {
		update_option( 'moondental_pricing_v3270', 'done' );
		return;
	}
	$tab_slugs = array(
		'implant'   => '임플란트',
		'ortho'     => '교정',
		'crown'     => '크라운·틀니',
		'decay'     => '충치·레진',
		'aesthetic' => '심미·미백',
		'kids'      => '소아·예방',
		'tmj'       => '턱관절',
	);
	$parts = array();
	$has_any_custom = false;
	foreach ( $tab_slugs as $slug => $default_label ) {
		$label = get_theme_mod( 'md_content_price_tab_' . $slug . '_label' );
		$rows  = get_theme_mod( 'md_content_price_tab_' . $slug . '_rows' );
		if ( $label !== false || $rows !== false ) $has_any_custom = true;
		$parts[] = '== ' . ( $label ?: $default_label ) . " ==\n" . ( $rows ?: '' );
	}
	if ( $has_any_custom ) {
		set_theme_mod( 'md_content_price_tabs_all', implode( "\n\n", $parts ) );
	}
	update_option( 'moondental_pricing_v3270', 'done' );
}, 41 );

/* 일회성 마이그레이션: 2026 진료비 표 기반 가격표 전면 갱신 (v3.26.0)
 * PDF의 실제 진료비 기준으로 8개 탭 defaults 전면 교체.
 * 사용자가 커스터마이저에서 편집·저장한 값이 옛 defaults와 일치하면 remove_theme_mod
 * 하여 새 기본값이 적용되도록 함. 다른 값으로 저장돼 있으면 보존. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_pricing_v3260' ) === 'done' ) return;
	// 옛 v3.24.x defaults의 시작 부분을 시그니처로 사용 — 정확히 일치하면 옛 default라고 판단
	$old_signatures = array(
		'md_content_price_tab_implant_rows'   => '일반 임플란트 (식립 1개) | 85만 원',
		'md_content_price_tab_ortho_rows'     => '소아 교정 (1차) | 150만 원',
		'md_content_price_tab_crown_rows'     => '도자기 + 금속 크라운 (PFM) | 45만 원',
		'md_content_price_tab_decay_rows'     => '레진 충전 (작은 충치, 1면) | 10만 원',
		'md_content_price_tab_gum_rows'       => '스케일링 (보험, 연 1회) | 25,100 원',
		'md_content_price_tab_aesthetic_rows' => '라미네이트 (앞니 심미) | 치아당 66만 원 | 부가세 포함
치아 미백',
		'md_content_price_tab_kids_rows'      => '실란트 (보험, 어금니) | 본인부담 21,700원~',
		'md_content_price_tab_tmj_rows'       => '턱관절 보톡스 | 20만 원 | 이갈이·교근 통증
턱관절 PDRN 주사 | 20만 원 | 관절 염증 완화
턱관절 보호 장치 (하드 스플린트) | 100만 원',
	);
	foreach ( $old_signatures as $mod_key => $signature ) {
		$saved = get_theme_mod( $mod_key );
		if ( is_string( $saved ) && $saved !== '' && strpos( $saved, $signature ) === 0 ) {
			remove_theme_mod( $mod_key );
		}
	}
	update_option( 'moondental_pricing_v3260', 'done' );
}, 36 );

/* 일회성 마이그레이션: 목요일 진료시간 18:00 → 18:30 (v3.27.2)
 * 옛 default를 저장했던 사용자의 커스터마이저 값도 새 시간으로 갱신. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_hours_thu_v3272' ) === 'done' ) return;
	$saved = get_theme_mod( 'moondental_hours_thu' );
	if ( is_string( $saved ) && strpos( $saved, '18:00' ) !== false && strpos( $saved, '목요일' ) !== false ) {
		remove_theme_mod( 'moondental_hours_thu' );
	}
	update_option( 'moondental_hours_thu_v3272', 'done' );
}, 37 );

/* 일회성 마이그레이션: 목요일 진료시간에서 '(야간진료 없음)' 부기 제거 (v3.27.8)
 * 사용자 요청 — 사이드바 표시가 너무 길어 삭제. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_hours_thu_v3278' ) === 'done' ) return;
	$saved = get_theme_mod( 'moondental_hours_thu' );
	if ( is_string( $saved ) && strpos( $saved, '(야간진료 없음)' ) !== false ) {
		$new = trim( str_replace( '(야간진료 없음)', '', $saved ) );
		$new = preg_replace( '/\s+/', ' ', $new );
		set_theme_mod( 'moondental_hours_thu', $new );
	}
	update_option( 'moondental_hours_thu_v3278', 'done' );
}, 38 );

/* 일회성 마이그레이션: 푸터 의료 면책 문구·사업자등록증 URL 제거 (v3.28.0)
 * 사용자 요청 — 두 항목 표시 안 함. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_footer_v3280' ) === 'done' ) return;
	remove_theme_mod( 'md_content_footer_legal_disclaimer' );
	remove_theme_mod( 'md_content_footer_legal_biz_cert_url' );
	update_option( 'moondental_footer_v3280', 'done' );
}, 39 );

/* 일회성 마이그레이션: 푸터 법적표시 · 깨진 옛 값(라벨만 있고 실제 값 없는 케이스) 정리 (v3.28.1)
 * 옛 default가 "라벨: 값" 형태였다가 v3.27.3에서 "값만" 형태로 바뀌면서
 * 사용자 저장값에서 접두어 제거 후 빈 문자열이 남는 케이스 방어. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_footer_v3281' ) === 'done' ) return;

	$strip_prefixes = array(
		'md_content_footer_legal_rep' => array( '대표자:', '대표자' ),
		'md_content_footer_legal_med_no' => array( '요양기관번호:', '요양기관번호', '의료기관 고유번호:', '의료기관 고유번호', '의료기관번호:', '의료기관번호' ),
		'md_content_footer_legal_privacy_officer' => array( '개인정보 보호책임자:', '개인정보 보호책임자', '개인정보:', '개인정보' ),
		'md_content_footer_legal_biz_no' => array( '사업자등록번호:', '사업자등록번호' ),
		'md_content_footer_legal_inst_name' => array( '상호:', '상호', '의료기관:', '의료기관' ),
		'md_content_footer_legal_inst_type' => array( '종별:', '종별' ),
		'md_content_footer_legal_open_date' => array( '개업일:', '개업일' ),
	);

	foreach ( $strip_prefixes as $key => $prefixes ) {
		$saved = get_theme_mod( $key );
		if ( ! is_string( $saved ) ) continue;
		$stripped = trim( $saved );
		foreach ( $prefixes as $p ) {
			if ( stripos( $stripped, $p ) === 0 ) {
				$stripped = trim( ltrim( substr( $stripped, strlen( $p ) ), " :·" ) );
				break;
			}
		}
		if ( $stripped === '' ) {
			// 깨진 값 (접두어만 있고 실제 값 없음) — 새 default 사용하도록 삭제
			remove_theme_mod( $key );
		} elseif ( $stripped !== $saved ) {
			// 접두어가 붙어있던 값 — 접두어 제거해서 재저장
			set_theme_mod( $key, $stripped );
		}
	}

	update_option( 'moondental_footer_v3281', 'done' );
}, 40 );

/* 일회성 마이그레이션: 목요일 진료시간 '09:00' → '9:00' 앞 0 제거 (v3.28.3)
 * 사용자 요청 — 평일/토요일 표기와 일관성 통일. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_hours_thu_v3283' ) === 'done' ) return;
	$saved = get_theme_mod( 'moondental_hours_thu' );
	if ( is_string( $saved ) && preg_match( '/\b09:00\b/', $saved ) ) {
		$new = preg_replace( '/\b09:00\b/', '9:00', $saved );
		set_theme_mod( 'moondental_hours_thu', $new );
	}
	update_option( 'moondental_hours_thu_v3283', 'done' );
}, 42 );

/* 일회성 마이그레이션 v3.29.1 · 옛 default 실명 명단이 Customizer에 없으면 seed로 저장.
 * GitHub 공개 리포에 노출됐던 명단을 default에서 제거하기 위한 안전장치. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_v3291' ) === 'done' ) return;
	$saved = get_theme_mod( 'md_content_staff_list' );
	// Customizer에 값이 없으면 옛 default를 이관 (실사이트 명단 유지)
	if ( empty( $saved ) || ! is_string( $saved ) ) {
		$seed = "진료실|이사|이순민\n진료실|팀장|박지선\n진료실|실장|이희남\n진료실|실장|임은혜\n진료실|실장|지선미\n진료실|실장|한경순\n진료실|책임|주경심\n진료실|책임|윤경옥\n진료실|책임|노금란\n진료실|책임|김정애\n진료실|과장|남소영\n진료실|선임|김인애\n진료실|선임|박미선\n진료실|선임|김윤미\n진료실|선임|유현영\n진료실|주임|서채빈\n진료실|주임|박명자\n진료실|주임|금민주\n진료실|주임|전서혜\n진료실|주임|유혜정\n진료실|주임|서소리\n진료실|주임|장유정\n진료실|주임|이아연\n진료실|주임|김경하\n진료실|주임|이다윤\n진료실|주임|이하은\n진료실|주임|김하늘\n진료실|주임|김우정\n진료실|주임|최로미\n진료실|주임|권민지\n기공실|이사|조항수\n기공실|차장|맹의재\n기공실|과장|장순복\n기공실|기사|박진옥\n기공실|기사|노재형\n서비스지원실|이사|강미해\n서비스지원실|실장|이선양\n서비스지원실|책임코디|김다경\n서비스지원실|책임코디|공미희\n서비스지원실|선임코디|정소리\n서비스지원실|선임코디|황진아\n서비스지원실|선임코디|박혜령\n경영지원실|행정원장|양병욱\n경영지원실|과장|이충현\n경영지원실|사원|김하진\n경영지원실|사원|카밀라\n경영지원실|사원|게를레\n관리사무소|소장|강성하\n비서실|과장|김동현\n비서실|과장|민종기\n비서실|대리|이슬기";
		set_theme_mod( 'md_content_staff_list', $seed );
	}
	update_option( 'moondental_staff_v3291', 'done' );
}, 44 );

/* 일회성 마이그레이션 v3.44.87 · 치과 백과사전 · 마케팅 용어 md_term 휴지통 이동
 * '천안 임플란트 잘하는 치과' 같은 지역 마케팅 문구는 백과사전에 부적합 · 제거 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_enc_clean_marketing_v3487' ) === 'done' ) return;
	global $wpdb;
	$bad_patterns = array(
		'%잘하는 치과%',
		'%잘하는 곳%',
		'%치과 추천%',
		'%추천 치과%',
		'천안 임플란트%',
		'아산 임플란트%',
		'천안 치과%',
		'아산 치과%',
		'천안 소아치과',
		'천안·아산 치과병원',
		'천안·아산 종합 치과',
		'천안·아산 예방 치과',
		'천안·아산 소아치과',
		'천안·아산 사랑니 발치',
		'천안·아산 신경치료',
		'천안·아산 잇몸 치료',
		'천안·아산 어린이 치과',
		'어린이 치과 천안',
		'천안 대형%',
		'천안 큰 치과',
	);
	$trashed = 0;
	foreach ( $bad_patterns as $pattern ) {
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'md_term'
			   AND post_status = 'publish'
			   AND post_title LIKE %s",
			$pattern
		) );
		if ( $rows ) {
			foreach ( $rows as $r ) {
				wp_trash_post( (int) $r->ID );
				$trashed++;
			}
		}
	}
	update_option( 'moondental_enc_clean_marketing_v3487', 'done' );
	update_option( 'moondental_enc_marketing_trashed_count', $trashed, false );
}, 58 );

/* 일회성 마이그레이션 v3.44.86 · '치과사전' → '치과 백과사전' 표시 라벨 정리
 * Customizer 저장값의 '치과사전' 표시 텍스트를 '치과 백과사전' 으로 변환 (URL 등은 유지) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_enc_rename_v3486' ) === 'done' ) return;
	$all_mods = get_theme_mods();
	if ( is_array( $all_mods ) ) {
		foreach ( $all_mods as $key => $val ) {
			if ( ! is_string( $val ) || $val === '' ) continue;
			if ( strpos( $val, '치과사전' ) === false ) continue;
			// 앞뒤 슬래시/대시 없는 '치과사전' 만 치환
			$new = preg_replace( '/(?<![\/\-])치과사전(?![\/\-])/u', '치과 백과사전', $val );
			if ( $new !== $val ) set_theme_mod( $key, $new );
		}
	}
	update_option( 'moondental_enc_rename_v3486', 'done' );
}, 57 );

/* 일회성 마이그레이션 v3.44.85 · 스태프 명단에서 정시연 제거 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_remove_jsy_v3485' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_staff_list' );
	if ( is_string( $stored ) && strpos( $stored, '정시연' ) !== false ) {
		$new = preg_replace( '/^진료실\|주임\|정시연\r?\n?/mu', '', $stored );
		if ( $new !== $stored ) set_theme_mod( 'md_content_staff_list', $new );
	}
	update_option( 'moondental_staff_remove_jsy_v3485', 'done' );
}, 56 );

/* 일회성 마이그레이션 v3.44.84 · URL 평면화 · post_parent = 0 + 부모 페이지 휴지통
 * 모든 core 페이지의 계층 구조 제거 · 최상위 URL 로 변경 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_flatten_urls_v3484' ) === 'done' ) return;
	global $wpdb;

	// 1. 모든 child 페이지 post_parent = 0 으로 (최상위화)
	$child_slugs = array(
		'의료진', '역사', '기술력-시설', '임상-케이스', '상시채용',
		'임플란트-센터', '투명교정-센터', '자연치아-살리기', '턱관절-클리닉',
		'사랑니-발치', '심미치료', '예방클리닉',
		'슈어스마일-투명교정', '브라켓-치아교정',
	);
	foreach ( $child_slugs as $raw ) {
		$encoded = strtolower( urlencode( $raw ) );
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->posts}
			 SET post_parent = 0
			 WHERE post_type = 'page'
			   AND post_status = 'publish'
			   AND (post_name = %s OR post_name = %s)
			   AND post_parent > 0",
			$raw, $encoded
		) );
	}

	// 2. 빈 부모 페이지 (병원소개, 진료항목) 휴지통
	$parent_slugs = array( '병원소개', '진료항목' );
	foreach ( $parent_slugs as $raw ) {
		$encoded = strtolower( urlencode( $raw ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'page'
			   AND post_status IN ('publish','draft','pending','private')
			   AND (post_name = %s OR post_name = %s)",
			$raw, $encoded
		) );
		if ( $rows ) {
			foreach ( $rows as $r ) wp_trash_post( (int) $r->ID );
		}
	}

	// 3. rewrite rules 재생성
	flush_rewrite_rules( false );

	update_option( 'moondental_flatten_urls_v3484', 'done' );
}, 55 );

/**
 * v3.44.84 · 301 리디렉션 · 이전 계층 URL → 새 평면 URL (SEO 보존)
 */
add_action( 'template_redirect', function () {
	if ( is_admin() ) return;
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	// query string 분리
	$path = strtok( $uri, '?' );
	$path_decoded = urldecode( $path );

	// /진료항목/xxx/ → /xxx/
	if ( preg_match( '#^/진료항목/([^/]+)/?$#u', $path_decoded, $m ) ) {
		wp_safe_redirect( home_url( '/' . $m[1] . '/' ), 301 );
		exit;
	}
	// /병원소개/xxx/ → /xxx/
	if ( preg_match( '#^/병원소개/([^/]+)/?$#u', $path_decoded, $m ) ) {
		wp_safe_redirect( home_url( '/' . $m[1] . '/' ), 301 );
		exit;
	}
	// /진료항목/ (부모) → 홈
	if ( $path_decoded === '/진료항목/' || $path_decoded === '/진료항목' ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
	// /병원소개/ (부모) → /의료진/
	if ( $path_decoded === '/병원소개/' || $path_decoded === '/병원소개' ) {
		wp_safe_redirect( home_url( '/의료진/' ), 301 );
		exit;
	}

	// v3.44.101 · 삭제된 옛 백과사전 SEO 스팸 URL · 410 Gone 응답
	// v3.44.102 · X-Robots-Tag HTTP 헤더 추가 · 완전한 검색 제외 (강화)
	$dead_encyclopedia_slugs = array(
		'천안-치과병원', '천안치과병원', '천안-치과', '천안치과',
		'아산-치과병원', '아산치과병원', '아산-치과', '아산치과',
		'천안-임플란트', '천안임플란트', '아산-임플란트', '아산임플란트',
		'천안-교정', '아산-교정', '천안-소아치과', '아산-소아치과',
		'천안-치과-추천', '아산-치과-추천', '천안치과추천', '아산치과추천',
	);
	foreach ( $dead_encyclopedia_slugs as $dead_slug ) {
		if ( preg_match( '#^/치과사전/' . preg_quote( $dead_slug, '#' ) . '/?$#u', $path_decoded ) ) {
			status_header( 410 );
			nocache_headers();
			// v3.44.102 · HTTP 헤더 · noindex,nofollow (모든 검색엔진에 인덱싱 금지 신호)
			header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
			header( 'Content-Type: text/html; charset=UTF-8' );
			echo '<!doctype html><html lang="ko"><head><meta charset="UTF-8"><title>410 Gone · 삭제된 페이지</title><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"></head><body style="font-family:sans-serif;text-align:center;padding:60px 20px;"><h1>410 Gone</h1><p>이 페이지는 영구적으로 삭제되었습니다.</p><p><a href="' . esc_url( home_url( '/치과사전/' ) ) . '">치과 백과사전으로 이동</a> · <a href="' . esc_url( home_url( '/' ) ) . '">홈으로 이동</a></p></body></html>';
			exit;
		}
	}

	// v3.44.119 · Polylang 자동 생성 · 존재 안 하는 다국어 duplicate 슬러그 · 410 Gone
	// (/홈-english/, /홈-中文/, /홈-日本語/, /홈-Tiếng Việt/, /홈-Русский/, /홈-Монгол/ 등)
	if ( preg_match( '#^/(홈|home)-([a-zA-Z가-힣一-龥ぁ-んァ-ヶ]|Tiếng|Việt|Русский|Монгол|中文|日本語|English)#u', $path_decoded ) ) {
		status_header( 410 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo '<!doctype html><html lang="ko"><head><meta charset="UTF-8"><title>410 Gone · 삭제된 페이지</title><meta name="robots" content="noindex,nofollow,noarchive,nosnippet"></head><body style="font-family:sans-serif;text-align:center;padding:60px 20px;"><h1>410 Gone</h1><p>이 페이지는 영구적으로 삭제되었습니다.</p><p><a href="' . esc_url( home_url( '/' ) ) . '">홈으로 이동</a></p></body></html>';
		exit;
	}
}, 1 );

/* v3.44.119 · 404 페이지 · X-Robots-Tag noindex HTTP 헤더 강제 (WP는 meta tag만 세팅)
 * 존재하지 않는 페이지가 구글 인덱스에 남는 문제 해결 */
add_action( 'template_redirect', function () {
	if ( is_admin() ) return;
	if ( is_404() ) {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
		}
	}
}, 2 );

/* v3.44.102 · robots.txt Disallow · 삭제된 스팸 URL 크롤링 자체 차단 */
add_filter( 'robots_txt', function( $output, $public ) {
	if ( ! $public ) return $output;
	$dead_slugs = array(
		'천안-치과병원', '천안치과병원', '천안-치과', '천안치과',
		'아산-치과병원', '아산치과병원', '아산-치과', '아산치과',
		'천안-임플란트', '천안임플란트', '아산-임플란트', '아산임플란트',
		'천안-교정', '아산-교정', '천안-소아치과', '아산-소아치과',
		'천안-치과-추천', '아산-치과-추천', '천안치과추천', '아산치과추천',
	);
	$block = "\n# START MOONDENTAL BLOCK · 삭제된 스팸 URL 크롤링 차단\nUser-agent: *\n";
	foreach ( $dead_slugs as $s ) {
		$block .= "Disallow: /치과사전/" . $s . "/\n";
	}
	// v3.44.119 · Polylang 자동 생성 duplicate 슬러그 크롤링 차단
	$block .= "Disallow: /홈-english/\nDisallow: /홈-中文/\nDisallow: /홈-日本語/\nDisallow: /홈-Русский/\nDisallow: /홈-Монгол/\nDisallow: /home-english/\nDisallow: /*-english/\n";
	$block .= "# END MOONDENTAL BLOCK\n";
	return $output . $block;
}, 10, 2 );

/* 일회성 마이그레이션 v3.44.101 · 옛 백과사전 SEO 스팸 md_term 강제 삭제
 * (v3.44.88 개편 이전에 남아있을 수 있는 지역명 조합 슬러그 정리) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_purge_spam_terms_v34101' ) === 'done' ) return;
	global $wpdb;
	$spam_slugs = array(
		'천안-치과병원', '천안치과병원', '천안-치과', '천안치과',
		'아산-치과병원', '아산치과병원', '아산-치과', '아산치과',
		'천안-임플란트', '천안임플란트', '아산-임플란트', '아산임플란트',
		'천안-교정', '아산-교정', '천안-소아치과', '아산-소아치과',
		'천안-치과-추천', '아산-치과-추천', '천안치과추천', '아산치과추천',
	);
	foreach ( $spam_slugs as $raw ) {
		$encoded = strtolower( urlencode( $raw ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'md_term'
			   AND post_status IN ('publish','draft','pending','private','trash','auto-draft')
			   AND (post_name = %s OR post_name = %s)",
			$raw, $encoded
		) );
		if ( $rows ) {
			foreach ( $rows as $r ) {
				$id = (int) $r->ID;
				$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $id ) );
				$wpdb->delete( $wpdb->term_relationships, array( 'object_id' => $id ) );
				$wpdb->delete( $wpdb->posts, array( 'ID' => $id ) );
			}
		}
	}
	update_option( 'moondental_purge_spam_terms_v34101', 'done' );
}, 52 );

/* 일회성 마이그레이션 v3.44.107 · 자연치아 살리기 앵커 네비에 치수복조술 추가
 * 사용자가 커스터마이징 안 했거나 옛 기본값 그대로면 새 기본값으로 갱신 (직접 편집한 경우 유지) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_pulpcap_nav_v34107' ) === 'done' ) return;
	$old_default = "🦷 | 충치치료 | #cavity\n⚡ | 신경치료 | #endo\n🌿 | 잇몸치료 | #perio";
	$new_default = "🦷 | 충치치료 | #cavity\n⭐ | 치수복조술 | #pulpcap\n⚡ | 신경치료 | #endo\n🌿 | 잇몸치료 | #perio";
	$current = get_theme_mod( 'md_content_preservation_nav_items', null );
	if ( $current === null || trim( (string) $current ) === '' || trim( (string) $current ) === $old_default ) {
		set_theme_mod( 'md_content_preservation_nav_items', $new_default );
	}
	update_option( 'moondental_pulpcap_nav_v34107', 'done' );
}, 54 );

/* 일회성 마이그레이션 v3.44.116 · Customizer 값 갱신 (예방치과→예방클리닉, 턱관절→턱관절 클리닉, 터미널/역 링크)
 * 사용자가 편집하지 않았거나 옛 기본값 그대로면 새 기본값으로 안전 갱신 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_customizer_rename_v34157' ) === 'done' ) return;

	// clinic_intro_dept_list · 턱관절 → 턱관절 클리닉
	$old_dept = "턱관절 — 통증·기능 장애 진료\n이갈이 · 이악물기\n매복 사랑니 발치\n소아치과\n예방클리닉 — 전문예방치료실 · 덴탈 스파 프로그램";
	$new_dept = "턱관절 클리닉 — 통증·기능 장애 진료\n이갈이 · 이악물기\n매복 사랑니 발치\n소아치과\n예방클리닉 — 전문예방치료실 · 덴탈 스파 프로그램";
	$cur_dept = get_theme_mod( 'md_content_clinic_intro_dept_list', null );
	if ( $cur_dept === null || trim( (string) $cur_dept ) === '' || trim( (string) $cur_dept ) === $old_dept ) {
		set_theme_mod( 'md_content_clinic_intro_dept_list', $new_dept );
	} elseif ( strpos( (string) $cur_dept, '턱관절 클리닉' ) === false && strpos( (string) $cur_dept, '턱관절' ) !== false ) {
		// 사용자 수정본에도 턱관절만 있으면 안전 치환 (라인 첫머리에 정확히 매칭)
		$updated = preg_replace( '/(^|\n)턱관절(?! 클리닉)/', '$1턱관절 클리닉', (string) $cur_dept );
		if ( $updated !== null && $updated !== $cur_dept ) {
			set_theme_mod( 'md_content_clinic_intro_dept_list', $updated );
		}
	}

	// loc_park_walk · 옛 · 병합 → 분리 + 종합 → 시외 리네이밍 (v3.44.118)
	// v3.44.122 · 두 터미널을 2줄로 분리 (\n 구분)
	$old_walk_a = '🚌 천안종합·고속버스터미널에서 도보 약 5분';
	$old_walk_b = '🚌 천안종합버스터미널·천안고속버스터미널에서 도보 약 5분';
	$old_walk_c = '🚌 천안시외버스터미널·천안고속버스터미널에서 도보 약 5분';
	$new_walk   = "🚌 천안시외버스터미널에서 도보 5분\n🚌 천안고속버스터미널에서 도보 5분";
	$cur_walk = get_theme_mod( 'md_content_loc_park_walk', null );
	if ( $cur_walk === null || trim( (string) $cur_walk ) === '' || trim( (string) $cur_walk ) === $old_walk_a || trim( (string) $cur_walk ) === $old_walk_b || trim( (string) $cur_walk ) === $old_walk_c ) {
		set_theme_mod( 'md_content_loc_park_walk', $new_walk );
	}
	// 변수 재정의 · 아래 footer 마이그레이션도 같은 로직
	$old_walk = $old_walk_a; // for backward reference below

	// loc_park_ktx · 신규 필드 · 기본값 세팅 (기존 없으면 신규 저장)
	// v3.44.124 · 20분 → 25분 갱신 (옛 저장값 감지 시)
	$old_ktx = '🚄 천안아산역에서 버스로 약 20분';
	$new_ktx = '🚄 천안아산역에서 버스로 약 25분';
	$cur_ktx = get_theme_mod( 'md_content_loc_park_ktx', null );
	if ( $cur_ktx === null || trim( (string) $cur_ktx ) === '' || trim( (string) $cur_ktx ) === $old_ktx ) {
		set_theme_mod( 'md_content_loc_park_ktx', $new_ktx );
	}

	// v3.44.120 · loc_park_train · 천안역 → 천안역·두정역 (옛 저장값 감지 시 갱신)
	$old_train = '🚆 천안역에서 버스로 약 10분';
	$new_train = '🚆 천안역·두정역에서 버스로 약 10분';
	$cur_train = get_theme_mod( 'md_content_loc_park_train', null );
	if ( $cur_train === null || trim( (string) $cur_train ) === '' || trim( (string) $cur_train ) === $old_train ) {
		set_theme_mod( 'md_content_loc_park_train', $new_train );
	}
	$cur_ftrain = get_theme_mod( 'md_content_footer_park_train', null );
	if ( $cur_ftrain === null || trim( (string) $cur_ftrain ) === '' || trim( (string) $cur_ftrain ) === $old_train ) {
		set_theme_mod( 'md_content_footer_park_train', $new_train );
	}

	// footer_park_walk · 옛 값이면 새 값으로 갱신
	$cur_fw = get_theme_mod( 'md_content_footer_park_walk', null );
	if ( $cur_fw === null || trim( (string) $cur_fw ) === '' || trim( (string) $cur_fw ) === $old_walk_a || trim( (string) $cur_fw ) === $old_walk_b || trim( (string) $cur_fw ) === $old_walk_c ) {
		set_theme_mod( 'md_content_footer_park_walk', $new_walk );
	}
	// footer_park_ktx · 신규 필드 (v3.44.124 · 20분 → 25분 갱신)
	$cur_fk = get_theme_mod( 'md_content_footer_park_ktx', null );
	if ( $cur_fk === null || trim( (string) $cur_fk ) === '' || trim( (string) $cur_fk ) === $old_ktx ) {
		set_theme_mod( 'md_content_footer_park_ktx', $new_ktx );
	}

	// v3.44.157 · 라미네이트 비용 콜아웃 제거 (사용자 요청 · 페이지마다 비용 CTA 불필요)
	$old_lct = '💎 라미네이트 비용';
	$old_lcb = '정확한 견적은 진단 후 산정. <a href="/비용-안내/">비용 안내 →</a>';
	$cur_lct = get_theme_mod( 'md_content_smile_laminate_callout_title', null );
	if ( $cur_lct === null || trim( (string) $cur_lct ) === $old_lct ) {
		set_theme_mod( 'md_content_smile_laminate_callout_title', '' );
	}
	$cur_lcb = get_theme_mod( 'md_content_smile_laminate_callout_body', null );
	if ( $cur_lcb === null || trim( (string) $cur_lcb ) === $old_lcb ) {
		set_theme_mod( 'md_content_smile_laminate_callout_body', '' );
	}

	// v3.44.156 · 층별 안내 lead 에서 '만남로' 제거
	$old_lead_a = '만남로 문타워 9·10·11·13층 · 각 층 전용 전문 진료실 운영';
	$new_lead   = '문타워 9·10·11·13층 · 각 층 전용 전문 진료실 운영';
	$cur_lead = get_theme_mod( 'md_content_floor_guide_lead', null );
	if ( $cur_lead === null || trim( (string) $cur_lead ) === '' || trim( (string) $cur_lead ) === $old_lead_a ) {
		set_theme_mod( 'md_content_floor_guide_lead', $new_lead );
	}

	// v3.44.147 · CTA 사이클 라벨 축소 (긴 라벨 → 짧은 라벨)
	$old_cta = "✨ 편리한 상담 | #5C8B82 | #FFFFFF | 92,139,130\n🦷 내 구강상태 진단받기 | #E37B5C | #FFFFFF | 227,123,92\n💬 지금 카톡 상담 | #FEE500 | #181600 | 254,229,0\n📅 상담 예약하기 | #D88062 | #FFFFFF | 216,128,98";
	$new_cta = "✨ 편리한 상담 | #5C8B82 | #FFFFFF | 92,139,130\n🦷 구강상태 진단 | #E37B5C | #FFFFFF | 227,123,92\n💬 카톡 상담 | #FEE500 | #181600 | 254,229,0\n📅 상담 예약 | #D88062 | #FFFFFF | 216,128,98";
	$cur_cta = get_theme_mod( 'md_content_header_cta_cycle', null );
	if ( $cur_cta === null || trim( (string) $cur_cta ) === '' || trim( (string) $cur_cta ) === $old_cta ) {
		set_theme_mod( 'md_content_header_cta_cycle', $new_cta );
	}

	update_option( 'moondental_customizer_rename_v34157', 'done' );
}, 56 );

/* 일회성 마이그레이션 v3.44.149 · 상단 메뉴 · 항목 이름에 공백 추가 (wrap 자연스럽게) */
add_action( 'wp_loaded', function() {
	if ( get_option( 'moondental_menu_spaces_v34149' ) === 'done' ) return;
	if ( ! function_exists( 'wp_get_nav_menu_object' ) ) return;
	$menu_obj = wp_get_nav_menu_object( '주 메뉴 (자동 생성)' );
	if ( ! $menu_obj ) { update_option( 'moondental_menu_spaces_v34149', 'done' ); return; }
	$menu_id = (int) $menu_obj->term_id;
	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! $items ) { update_option( 'moondental_menu_spaces_v34149', 'done' ); return; }

	$rename_map = array(
		'임플란트센터'     => '임플란트 센터',
		'교정센터'         => '교정 센터',
		'스마일디자인센터' => '스마일디자인 센터',
		'자연치아살리기'   => '자연치아 살리기',
		'비용안내'         => '비용 안내',
		'병원안내'         => '병원 안내',
	);
	foreach ( $items as $it ) {
		$old = trim( (string) $it->title );
		if ( isset( $rename_map[ $old ] ) ) {
			wp_update_nav_menu_item( $menu_id, (int) $it->ID, array(
				'menu-item-title'     => $rename_map[ $old ],
				'menu-item-url'       => $it->url,
				'menu-item-type'      => $it->type,
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $it->menu_order,
				'menu-item-parent-id' => (int) $it->menu_item_parent,
			) );
		}
	}
	update_option( 'moondental_menu_spaces_v34149', 'done' );
}, 22 );

/* 일회성 마이그레이션 v3.44.108 · 상단 메뉴 · 자연치아살리기 하위에 '치수복조술' 추가
 * '주 메뉴 (자동 생성)' 메뉴에서 자연치아살리기 부모를 찾아 그 하위에 치수복조술을 삽입.
 * 이미 치수복조술이 있으면 건너뜀. */
add_action( 'wp_loaded', function() {
	if ( get_option( 'moondental_menu_pulpcap_v34108' ) === 'done' ) return;
	if ( ! function_exists( 'wp_get_nav_menu_object' ) ) return;
	$menu_obj = wp_get_nav_menu_object( '주 메뉴 (자동 생성)' );
	if ( ! $menu_obj ) { update_option( 'moondental_menu_pulpcap_v34108', 'done' ); return; }
	$menu_id = (int) $menu_obj->term_id;
	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! $items ) { update_option( 'moondental_menu_pulpcap_v34108', 'done' ); return; }

	// 자연치아살리기 부모 항목 찾기
	$parent_id = 0;
	$pulpcap_exists = false;
	$after_position = 0;
	foreach ( $items as $it ) {
		$t = trim( (string) $it->title );
		if ( $t === '자연치아살리기' || $t === '자연치아 살리기' ) {
			$parent_id = (int) $it->ID;
		}
		if ( $t === '치수복조술' ) {
			$pulpcap_exists = true;
		}
	}
	if ( $pulpcap_exists || ! $parent_id ) {
		update_option( 'moondental_menu_pulpcap_v34108', 'done' );
		return;
	}
	// 부모 아래의 '충치치료' 위치와 하위 항목 최대 position 계산
	$cavity_pos = 0; $max_child_pos = 0;
	foreach ( $items as $it ) {
		if ( (int) $it->menu_item_parent !== $parent_id ) continue;
		$max_child_pos = max( $max_child_pos, (int) $it->menu_order );
		if ( trim( (string) $it->title ) === '충치치료' ) {
			$cavity_pos = (int) $it->menu_order;
		}
	}
	$new_pos = $cavity_pos > 0 ? $cavity_pos + 5 : ( $max_child_pos + 10 );
	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'     => '치수복조술',
		'menu-item-url'       => home_url( '/자연치아-살리기/#pulpcap' ),
		'menu-item-type'      => 'custom',
		'menu-item-status'    => 'publish',
		'menu-item-position'  => $new_pos,
		'menu-item-parent-id' => $parent_id,
	) );
	update_option( 'moondental_menu_pulpcap_v34108', 'done' );
}, 20 );

/* 일회성 마이그레이션 v3.44.82 · v3.44.81에서 생성된 랜딩 페이지 2개 휴지통 이동
 * 기존 /오시는-길/cheonan/, /오시는-길/asan/ 로 대체 · 신설 페이지 불필요 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_remove_recommend_v3482' ) === 'done' ) return;
	global $wpdb;
	$slugs = array( '천안-추천-치과', '아산-추천-치과' );
	foreach ( $slugs as $raw ) {
		$encoded = strtolower( urlencode( $raw ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'page'
			   AND post_status IN ('publish','draft','pending','private')
			   AND (post_name = %s OR post_name = %s)",
			$raw, $encoded
		) );
		if ( $rows ) {
			foreach ( $rows as $r ) wp_trash_post( (int) $r->ID );
		}
	}
	update_option( 'moondental_remove_recommend_v3482', 'done' );
}, 53 );

/* 일회성 마이그레이션 v3.44.82 · 지역 페이지 섹션 제목 · '추천 치과' 키워드 추가 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_region_titles_v3482' ) === 'done' ) return;
	$old_defaults = array(
		'md_content_region_reasons_eyebrow' => '✨ 우리 병원을 선택하는 이유',
		'md_content_region_reasons_title'   => '{region}에서 문치과병원을 선택하시는 이유',
		'md_content_region_popular_title'   => '{region}에서 오시는 환자분들의 인기 진료',
		'md_content_region_faq_title'       => '{region} 환자분들이 자주 물어보시는 질문',
	);
	foreach ( $old_defaults as $key => $old ) {
		if ( get_theme_mod( $key ) === $old ) remove_theme_mod( $key );
	}
	update_option( 'moondental_region_titles_v3482', 'done' );
}, 54 );

/* 일회성 마이그레이션 v3.44.80 · Customizer 저장값 · '천안' → '천안·아산' 확장 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_cheonan_asan_v3480' ) === 'done' ) return;
	$rules = array(
		'천안 임플란트'  => '천안·아산 임플란트',
		'천안 투명교정'  => '천안·아산 투명교정',
		'천안 교정'      => '천안·아산 교정',
		'천안 치아교정'  => '천안·아산 치아교정',
		'천안 슈어스마일' => '천안·아산 슈어스마일',
		'천안 라미네이트' => '천안·아산 라미네이트',
		'천안 심미치료'   => '천안·아산 심미치료',
		'천안 스마일'    => '천안·아산 스마일',
		'천안 신경치료'  => '천안·아산 신경치료',
		'천안 잇몸치료'  => '천안·아산 잇몸치료',
		'천안 잇몸 치료' => '천안·아산 잇몸 치료',
		'천안 자연치아'  => '천안·아산 자연치아',
		'천안 사랑니'   => '천안·아산 사랑니',
		'천안 소아치과' => '천안·아산 소아치과',
		'천안 어린이'   => '천안·아산 어린이',
		'천안 턱관절'   => '천안·아산 턱관절',
		'천안 스케일링' => '천안·아산 스케일링',
		'천안 미백'     => '천안·아산 미백',
		'천안 크라운'   => '천안·아산 크라운',
		'천안 틀니'     => '천안·아산 틀니',
		'천안 예방'     => '천안·아산 예방',
		'천안 정기검진' => '천안·아산 정기검진',
		'천안 정기 검진' => '천안·아산 정기 검진',
		'천안 종합 치과' => '천안·아산 종합 치과',
		'천안 종합치과'  => '천안·아산 종합치과',
		'천안 대형 치과' => '천안·아산 대형 치과',
		'천안 대형치과'  => '천안·아산 대형치과',
		'천안 큰 치과'  => '천안·아산 큰 치과',
		'천안 치과 추천' => '천안·아산 치과 추천',
		'천안 추천 치과' => '천안·아산 추천 치과',
		'천안 치과 잘하는' => '천안·아산 치과 잘하는',
		'천안 잘하는 치과' => '천안·아산 잘하는 치과',
		'천안 치과병원' => '천안·아산 치과병원',
		'천안 치과'    => '천안·아산 치과',
		'천안 30여년' => '천안·아산 30여년',
		'천안 30여년'   => '천안·아산 30여년',
		'천안에서 30여년' => '천안·아산에서 30여년',
		'천안에서 30여년' => '천안·아산에서 30여년',
		'천안 지역'   => '천안·아산 지역',
		'천안·아산·아산' => '천안·아산',
	);
	$all_mods = get_theme_mods();
	if ( is_array( $all_mods ) ) {
		foreach ( $all_mods as $key => $val ) {
			if ( ! is_string( $val ) || $val === '' ) continue;
			if ( strpos( $val, '천안 ' ) === false ) continue;
			$new = strtr( $val, $rules );
			if ( $new !== $val ) set_theme_mod( $key, $new );
		}
	}
	update_option( 'moondental_cheonan_asan_v3480', 'done' );
}, 52 );

/* 일회성 마이그레이션 v3.44.76 · 지역 페이지 H1 · '추천 치과' 키워드 추가
 * 옛 default 값이면 remove_theme_mod → 새 default 자동 적용 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_region_recommend_v3476' ) === 'done' ) return;
	if ( get_theme_mod( 'md_content_region_hero_title_a' ) === '{region}에서 찾는' ) {
		remove_theme_mod( 'md_content_region_hero_title_a' );
	}
	if ( get_theme_mod( 'md_content_region_hero_title_b' ) === '임플란트·교정 잘하는 천안·아산 치과' ) {
		remove_theme_mod( 'md_content_region_hero_title_b' );
	}
	update_option( 'moondental_region_recommend_v3476', 'done' );
}, 51 );

/* 일회성 마이그레이션 v3.44.73 · '보건복지부 인증 보존과' 문구 정리
 * Customizer 저장값에서 어색한 표현 제거 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_no_mohw_cert_v3473' ) === 'done' ) return;
	$rules = array(
		'보건복지부 인증 보존과 직접 진행' => '보존과 진료팀 직접 진행',
		'보건복지부 인증 보존과 진료팀 직접 진행' => '보존과 진료팀 직접 진행',
		'보건복지부 인증 보존과' => '보존과 진료팀',
		'보건복지부 인증 프로그램' => '',
		'치과 보존과 진료 (보건복지부 인증)' => '치과 보존과',
	);
	$all_mods = get_theme_mods();
	if ( is_array( $all_mods ) ) {
		foreach ( $all_mods as $key => $val ) {
			if ( ! is_string( $val ) || $val === '' ) continue;
			if ( strpos( $val, '보건복지부' ) === false ) continue;
			$new = strtr( $val, $rules );
			if ( $new !== $val ) set_theme_mod( $key, $new );
		}
	}
	update_option( 'moondental_no_mohw_cert_v3473', 'done' );
}, 50 );

/* 일회성 마이그레이션 v3.44.72 · 치수복조술 콜아웃 · 구조화된 HTML 로 갱신
 * v3.44.70~71 default (br 기반) 이 저장돼 있으면 → 새 <p>·<ul> 기반 default 로 갱신 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_pulpcap_html_v3472' ) === 'done' ) return;
	$stored = get_theme_mod( 'preservation_cavity_callout_body' );
	if ( ! is_string( $stored ) || $stored === '' ) {
		$stored = get_theme_mod( 'md_content_preservation_cavity_callout_body' );
	}
	if ( is_string( $stored ) && $stored !== '' ) {
		// 옛 default 시그니처: 첫 문장 + <br><br> 사용
		if ( strpos( $stored, '충치가 신경까지 깊게 진행되면' ) === 0
		  && strpos( $stored, '<br><br>' ) !== false ) {
			remove_theme_mod( 'preservation_cavity_callout_body' );
			remove_theme_mod( 'md_content_preservation_cavity_callout_body' );
		}
	}
	update_option( 'moondental_pulpcap_html_v3472', 'done' );
}, 49 );

/* 일회성 마이그레이션 v3.44.71 · Customizer 저장값에 남은 '전문의' 문구 자동 치환
 * 파일 default 는 v3.44.71 에서 이미 정리됨. Customizer 에 사용자가 옛 default 를
 * 그대로 저장했거나 편집본에 '전문의' 가 남아있으면 동일 규칙으로 치환. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_no_specialist_v3471' ) === 'done' ) return;
	$rules = array(
		'치과 보존과 전문의 (보건복지부 인증)' => '치과 보존과 진료 (보건복지부 인증)',
		'보건복지부 인증 보존과 전문의' => '보존과 진료팀',
		'치과 보철과 전문의 · 통합치의학 전문의' => '치과 보철과·통합치의학 진료',
		'치과교정과 전문의·인정의' => '치과교정과·인정의',
		'치과 교정과 전문의·인정의' => '치과 교정과·인정의',
		'치과교정과 전문의' => '치과교정과',
		'전문의·인정의' => '인정의',
		'전문의 협진' => '진료과 협진',
		'전문의 정밀 진단' => '정밀 진단',
		'전문의의 정밀' => '진료팀의 정밀',
		'전문의의 재근관치료' => '진료팀의 재근관치료',
		'전문의가 직접' => '진료팀이 직접',
		'전문의가 최적안' => '진료팀이 최적안',
		'전문의 원장님' => '원장님',
		'전문의로서' => '원장으로서',
		'전문의 소개' => '진료팀 소개',
		'보존과 전문의' => '보존과',
		'보철과 전문의' => '보철과',
		'치주과 전문의' => '치주과',
		'구강외과 전문의' => '구강외과',
		'소아치과 전문의' => '소아치과',
		'교정 전문의' => '교정과',
		'임플란트 전문의' => '임플란트 진료팀',
		'전문의' => '진료팀',
		// 발치 위험 없음 문구
		'· 발치 위험 없음' => '',
		'치아를 잃을 위험은 없습니다' => '단계적 치료로 이어집니다',
	);
	$all_mods = get_theme_mods();
	if ( is_array( $all_mods ) ) {
		foreach ( $all_mods as $key => $val ) {
			if ( ! is_string( $val ) || $val === '' ) continue;
			if ( strpos( $val, '전문의' ) === false && strpos( $val, '발치 위험 없음' ) === false && strpos( $val, '치아를 잃을 위험은 없습니다' ) === false ) continue;
			$new = strtr( $val, $rules );
			if ( $new !== $val ) {
				set_theme_mod( $key, $new );
			}
		}
	}
	update_option( 'moondental_no_specialist_v3471', 'done' );
}, 48 );

/* 일회성 마이그레이션 v3.44.70 · 충치치료 섹션 · 치수복조술 강조 콘텐츠로 갱신
 * Customizer 저장값이 옛 기본값이면 remove_theme_mod → 새 기본값 자동 적용
 * 사용자가 편집한 값은 보존 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_cavity_pulpcap_v3470' ) === 'done' ) return;
	$old_title = '천안·아산 충치치료 — 보존적 접근으로 자연치아 최대한 살리기';
	if ( get_theme_mod( 'preservation_cavity_title' ) === $old_title
	  || get_theme_mod( 'md_content_preservation_cavity_title' ) === $old_title ) {
		remove_theme_mod( 'preservation_cavity_title' );
		remove_theme_mod( 'md_content_preservation_cavity_title' );
	}
	$old_lead = '충치는 조기 발견·조기 치료가 핵심입니다. 진행 단계에 따라 가장 보존적인 방법을 선택합니다.';
	if ( get_theme_mod( 'preservation_cavity_lead' ) === $old_lead
	  || get_theme_mod( 'md_content_preservation_cavity_lead' ) === $old_lead ) {
		remove_theme_mod( 'preservation_cavity_lead' );
		remove_theme_mod( 'md_content_preservation_cavity_lead' );
	}
	$stored_cards = get_theme_mod( 'preservation_cavity_cards' );
	if ( ! is_string( $stored_cards ) || $stored_cards === '' ) {
		$stored_cards = get_theme_mod( 'md_content_preservation_cavity_cards' );
	}
	if ( is_string( $stored_cards ) && strpos( $stored_cards, '심부 | 심부 충치 — 신경 보존 직접치수복조 |' ) !== false ) {
		remove_theme_mod( 'preservation_cavity_cards' );
		remove_theme_mod( 'md_content_preservation_cavity_cards' );
	}
	$old_cot = '💡 충치치료 비용 안내';
	if ( get_theme_mod( 'preservation_cavity_callout_title' ) === $old_cot
	  || get_theme_mod( 'md_content_preservation_cavity_callout_title' ) === $old_cot ) {
		remove_theme_mod( 'preservation_cavity_callout_title' );
		remove_theme_mod( 'md_content_preservation_cavity_callout_title' );
	}
	$stored_body = get_theme_mod( 'preservation_cavity_callout_body' );
	if ( ! is_string( $stored_body ) || $stored_body === '' ) {
		$stored_body = get_theme_mod( 'md_content_preservation_cavity_callout_body' );
	}
	if ( is_string( $stored_body ) && strpos( $stored_body, '레진 충전·세라믹 인레이·지르코니아 크라운 등 재료별 비용' ) !== false ) {
		remove_theme_mod( 'preservation_cavity_callout_body' );
		remove_theme_mod( 'md_content_preservation_cavity_callout_body' );
	}
	update_option( 'moondental_cavity_pulpcap_v3470', 'done' );
}, 47 );

/* 일회성 마이그레이션 v3.44.69 · 같은 부서·직급 내 이름 가나다순 정렬 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_sort_v3469' ) === 'done' ) return;
	$sorted_roster = "진료실|이사|이순민\n"
		. "진료실|실장|박지선\n"
		. "진료실|실장|이희남\n"
		. "진료실|실장|임은혜\n"
		. "진료실|실장|지선미\n"
		. "진료실|실장|한경순\n"
		. "진료실|팀장|김정애\n"
		. "진료실|팀장|남소영\n"
		. "진료실|팀장|노금란\n"
		. "진료실|팀장|윤경옥\n"
		. "진료실|팀장|주경심\n"
		. "진료실|책임|김윤미\n"
		. "진료실|책임|김인애\n"
		. "진료실|책임|박미선\n"
		. "진료실|선임|박명자\n"
		. "진료실|선임|서소리\n"
		. "진료실|선임|유현영\n"
		. "진료실|주임|권민지\n"
		. "진료실|주임|금민주\n"
		. "진료실|주임|김경하\n"
		. "진료실|주임|김우정\n"
		. "진료실|주임|김하늘\n"
		. "진료실|주임|서채빈\n"
		. "진료실|주임|유혜정\n"
		. "진료실|주임|이다윤\n"
		. "진료실|주임|이수경\n"
		. "진료실|주임|이아연\n"
		. "진료실|주임|이하은\n"
		. "진료실|주임|장유정\n"
		. "진료실|주임|전서혜\n"
		. "진료실|주임|최로미\n"
		. "기공실|이사|조항수\n"
		. "기공실|실장|맹의재\n"
		. "기공실|과장|박진옥\n"
		. "기공실|과장|장순복\n"
		. "기공실|대리|노재형\n"
		. "서비스지원실|이사|강미해\n"
		. "서비스지원실|실장|이선양\n"
		. "서비스지원실|수석코디|공미희\n"
		. "서비스지원실|수석코디|김다경\n"
		. "서비스지원실|책임코디|박혜령\n"
		. "서비스지원실|책임코디|정소리\n"
		. "서비스지원실|책임코디|황진아\n"
		. "경영지원본부|행정원장|양병욱\n"
		. "경영지원본부|실장|김동현\n"
		. "경영지원본부|차장|이충현\n"
		. "경영지원본부|과장|민종기\n"
		. "경영지원본부|대리|김하진\n"
		. "경영지원본부|대리|이슬기\n"
		. "경영지원본부|대리|카밀라\n"
		. "경영지원본부|주임|게를레\n"
		. "경영지원본부|주임|오혜정\n"
		. "관리사무소|소장|강성하";
	set_theme_mod( 'md_content_staff_list', $sorted_roster );
	update_option( 'moondental_staff_sort_v3469', 'done' );
}, 46 );

/* 일회성 마이그레이션 v3.29.2 · 26년 문치과병원 직급 변경 공고 반영.
 * 공고일 2026.07.13 · 기간 2026-07-13~07-31
 * 진료실 승급 대량 · 서비스지원실 승급 · 경영지원본부 통합 (비서실 흡수) · 기공실 승급
 * 신규 추가: 진료실 정시연·이수경, 경영지원본부 오혜정. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_v3292' ) === 'done' ) return;
	$new_roster = "진료실|이사|이순민\n"
		. "진료실|실장|박지선\n"
		. "진료실|실장|이희남\n"
		. "진료실|실장|임은혜\n"
		. "진료실|실장|지선미\n"
		. "진료실|실장|한경순\n"
		. "진료실|팀장|주경심\n"
		. "진료실|팀장|윤경옥\n"
		. "진료실|팀장|남소영\n"
		. "진료실|팀장|노금란\n"
		. "진료실|팀장|김정애\n"
		. "진료실|책임|김인애\n"
		. "진료실|책임|박미선\n"
		. "진료실|책임|김윤미\n"
		. "진료실|선임|서소리\n"
		. "진료실|선임|박명자\n"
		. "진료실|선임|유현영\n"
		. "진료실|주임|서채빈\n"
		. "진료실|주임|전서혜\n"
		. "진료실|주임|유혜정\n"
		. "진료실|주임|금민주\n"
		. "진료실|주임|장유정\n"
		. "진료실|주임|이아연\n"
		. "진료실|주임|김경하\n"
		. "진료실|주임|이다윤\n"
		. "진료실|주임|이하은\n"
		. "진료실|주임|김하늘\n"
		. "진료실|주임|김우정\n"
		. "진료실|주임|최로미\n"
		. "진료실|주임|권민지\n"
		. "진료실|주임|이수경\n"
		. "기공실|이사|조항수\n"
		. "기공실|실장|맹의재\n"
		. "기공실|과장|장순복\n"
		. "기공실|과장|박진옥\n"
		. "기공실|대리|노재형\n"
		. "서비스지원실|이사|강미해\n"
		. "서비스지원실|실장|이선양\n"
		. "서비스지원실|수석코디|김다경\n"
		. "서비스지원실|수석코디|공미희\n"
		. "서비스지원실|책임코디|정소리\n"
		. "서비스지원실|책임코디|황진아\n"
		. "서비스지원실|책임코디|박혜령\n"
		. "경영지원본부|행정원장|양병욱\n"
		. "경영지원본부|실장|김동현\n"
		. "경영지원본부|차장|이충현\n"
		. "경영지원본부|과장|민종기\n"
		. "경영지원본부|대리|김하진\n"
		. "경영지원본부|대리|이슬기\n"
		. "경영지원본부|대리|카밀라\n"
		. "경영지원본부|주임|게를레\n"
		. "경영지원본부|주임|오혜정\n"
		. "관리사무소|소장|강성하";
	set_theme_mod( 'md_content_staff_list', $new_roster );
	update_option( 'moondental_staff_v3292', 'done' );
}, 45 );

/* 일회성 마이그레이션 v3.30.4 · 전문 진료 영역 확장 (6과 → 11개)
 * 사용자 요청 · 구강외과·구강내과·턱관절·스마일디자인·임플란트·예방·교정센터 포함.
 * Customizer에 옛 6과 값 저장돼있으면 새 11개로 갱신. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_specialties_v3304' ) === 'done' ) return;
	$old_new = array(
		'md_content_trust_2_value' => array( '6', '11' ),
		'md_content_trust_2_unit'  => array( '과', '개' ),
		'md_content_trust_2_label' => array( '전문 진료과', '전문 진료 영역' ),
		'md_content_trust_2_sub'   => array( '보철·교정·보존·치주·소아·외과', '보철·보존·예방·임플란트·스마일디자인·구강외과·구강내과·턱관절·교정·소아·치주' ),
	);
	foreach ( $old_new as $key => $pair ) {
		$saved = get_theme_mod( $key );
		if ( is_string( $saved ) && $saved === $pair[0] ) {
			set_theme_mod( $key, $pair[1] );
		}
	}
	update_option( 'moondental_specialties_v3304', 'done' );
}, 47 );

/* 일회성 마이그레이션 v3.29.3 · 사용자 요청 · 비서실 복원.
 * 김동현(실장)·이슬기(대리)를 경영지원본부 → 비서실로 이동. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_v3293' ) === 'done' ) return;
	$saved = get_theme_mod( 'md_content_staff_list' );
	if ( is_string( $saved ) && $saved !== '' ) {
		// 경영지원본부의 김동현·이슬기를 비서실로 이관
		$new = str_replace(
			array(
				'경영지원본부|실장|김동현',
				'경영지원본부|대리|이슬기',
			),
			array(
				'비서실|실장|김동현',
				'비서실|대리|이슬기',
			),
			$saved
		);
		if ( $new !== $saved ) {
			set_theme_mod( 'md_content_staff_list', $new );
		}
	}
	update_option( 'moondental_staff_v3293', 'done' );
}, 46 );

/* 일회성 마이그레이션 v3.31.6 · 사용자 요청 · 비서실 → 경영지원본부 최종 통합.
 * PDF 26년 승급 공고 기준으로 경영지원본부 재구성:
 *   행정원장 → 실장 → 차장 → 과장 → 대리 → 주임 순 (대리가 주임보다 상급).
 *   비서실 인원(김동현 실장·민종기 과장·이슬기 대리)을 경영지원본부로 흡수하고
 *   김하진·카밀라 대리 승급, 오혜정 주임 신규 추가. 게를레 표기 유지.
 *   비서실은 완전히 제거. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_v3316' ) === 'done' ) return;
	$saved = get_theme_mod( 'md_content_staff_list' );
	if ( is_string( $saved ) && $saved !== '' ) {
		$lines = preg_split( "/\r\n|\r|\n/", $saved );
		// 경영지원본부·비서실 라인 전부 제거 (다른 부서는 보존)
		$lines = array_values( array_filter( $lines, function( $l ) {
			$l = trim( $l );
			if ( $l === '' ) return false;
			if ( strpos( $l, '경영지원본부|' ) === 0 ) return false;
			if ( strpos( $l, '경영지원실|' )   === 0 ) return false;
			if ( strpos( $l, '비서실|' )        === 0 ) return false;
			return true;
		} ) );
		// 경영지원본부 최종 명단 (직급 순: 행정원장 → 실장 → 차장 → 과장 → 대리 → 주임)
		$mgmt = array(
			'경영지원본부|행정원장|양병욱',
			'경영지원본부|실장|김동현',
			'경영지원본부|차장|이충현',
			'경영지원본부|과장|민종기',
			'경영지원본부|대리|이슬기',
			'경영지원본부|대리|김하진',
			'경영지원본부|대리|카밀라',
			'경영지원본부|주임|게를레',
			'경영지원본부|주임|오혜정',
		);
		$new = implode( "\n", array_merge( $lines, $mgmt ) );
		set_theme_mod( 'md_content_staff_list', $new );
	}
	update_option( 'moondental_staff_v3316', 'done' );
}, 48 );

/* 일회성 마이그레이션 v3.34.8 · SEO 강 강도 · 지역+전국 신뢰 워딩 통합.
 *  이전 default가 저장돼 있으면 새 default로 갱신.
 *  사용자가 직접 다른 값을 입력했다면 절대 덮어쓰지 않음. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_seo_strong_v3348' ) === 'done' ) return;
	$updates = array(
		'moondental_hero_eyebrow' => array(
			'old' => array( '천안 만남로 · 1995년부터 한자리에서', '천안 만남로에서 1995년부터', '30여년을 한결같이' ),
			'new' => '천안·아산 대표 치과병원 · 전국에서 찾아오는',
		),
		'moondental_hero_title_b' => array(
			'old' => array( '환자 한 분의 평생 치아를' ),
			'new' => '전국 환자가 신뢰하는 통합 진료',
		),
		'md_content_trust_1_sub' => array(
			'old' => array( '한자리에서 이어온 신뢰' ),
			'new' => '전국에서 찾아오는 30여년 신뢰',
		),
		'md_content_why_eyebrow' => array(
			'old' => array( 'Why Moon Dental' ),
			'new' => 'WHY MOON DENTAL',
		),
		'md_content_why_title' => array(
			'old' => array( '천안·아산에서 왜 문치과병원을 찾으시나요?' ),
			'new' => '천안·아산 대표 치과병원 · 전국에서 찾아오시는 이유',
		),
		'md_content_why_lead' => array(
			'old' => array( '천안 만남로에서 30여년 — 환자분들이 선택해온 이유 4가지로 정리해드립니다.', '천안 만남로에서 30여년 — 천안·아산 환자분들이 선택해온 이유 4가지로 정리해드립니다.' ),
			'new' => '1995년 개원 30여년 · 천안 만남로에서 전국 환자분들이 문치과병원을 선택하시는 4가지 이유',
		),
		'md_content_why_1_title' => array(
			'old' => array( '30여년, 한자리에서' ),
			'new' => '전국에서 찾아오는 30여년',
		),
		'md_content_clinic_intro_title' => array(
			'old' => array( '30여년 이상 한자리에서, 문치과병원' ),
			'new' => '천안·아산 대표 치과병원 · 전국에서 찾아오는 통합 진료',
		),
		'md_content_cta_title' => array(
			'old' => array( '치아 때문에 망설이고 계신가요?' ),
			'new' => '천안·아산 대표 치과병원 · 전국에서 찾아오시는 병원',
		),
		'md_content_notices_title' => array(
			'old' => array( '공지사항', '천안·아산 문치과병원 소식', '천안·아산 문치과병원 소식' ),
			'new' => '천안·아산 대표 치과병원 · 문치과병원 소식',
		),
	);
	foreach ( $updates as $key => $data ) {
		$saved = get_theme_mod( $key );
		if ( ! is_string( $saved ) ) continue;
		if ( in_array( $saved, $data['old'], true ) ) {
			set_theme_mod( $key, $data['new'] );
		}
	}
	update_option( 'moondental_seo_strong_v3348', 'done' );
}, 51 );

/* 일회성 마이그레이션 v3.31.7 · 사용자 정정 · 게레레 → 게를레 복구.
 * v3.31.6에서 PDF 표기를 따라 게레레로 저장했으나 실제 이름은 게를레. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_v3317' ) === 'done' ) return;
	$saved = get_theme_mod( 'md_content_staff_list' );
	if ( is_string( $saved ) && strpos( $saved, '게레레' ) !== false ) {
		set_theme_mod( 'md_content_staff_list', str_replace( '게레레', '게를레', $saved ) );
	}
	update_option( 'moondental_staff_v3317', 'done' );
}, 49 );

/* 일회성 마이그레이션 v3.33.7 · 진료 페이지 WP 본문 시드.
 *  content-defaults.php 의 기본 HTML을 각 진료 페이지(임플란트-센터 등)의
 *  WP post_content에 자동 복사.
 *  → 이후 사용자가 wp-admin → 페이지 → 임플란트 센터 편집으로
 *    블록 에디터 / 클래식 에디터에서 자유롭게 편집 가능.
 *
 *  안전장치: WP 페이지 본문이 이미 비어있지 않다면 (사용자가 편집한 흔적)
 *  절대 덮어쓰지 않음.
 *
 *  wp_update_post 는 post types 등록이 완료된 init 이후에 안전. */
// v3.37.0 · admin_init로 이동 (관리자 컨텍스트에서만 실행)
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_seed_service_content_v3337' ) === 'done' ) return;
	if ( ! function_exists( 'moondental_service_content_default_map' ) ) return;
	$map = moondental_service_content_default_map();
	foreach ( $map as $slug => $html ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) continue;
		$current = trim( (string) $page->post_content );
		if ( $current !== '' ) continue; // 이미 편집된 페이지는 스킵
		wp_update_post( array(
			'ID'           => $page->ID,
			'post_content' => trim( $html ),
		) );
	}
	update_option( 'moondental_seed_service_content_v3337', 'done' );
}, 20 );

/* v3.37.1 · 자가진단 aside 전화 버튼 라벨 마이그레이션
 *  기존 저장값이 '{phone}' 토큰을 포함하면 초기화 (짧은 '전화 상담'으로 재적용).
 *  이유: 3버튼 가로 배치로 바뀌면서 전화번호가 라벨에 붙으면 폭 초과. */
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_bot_call_label_v3371' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_bot_aside_btn_call', null );
	if ( is_string( $stored ) && strpos( $stored, '{phone}' ) !== false ) {
		remove_theme_mod( 'md_content_bot_aside_btn_call' );
	}
	update_option( 'moondental_bot_call_label_v3371', 'done' );
} );

/* v3.37.3 · 전역 예약 CTA 전화번호 표시 off 마이그레이션
 *  기존 저장값이 'yes'면 초기화 → 새 default 'no' 적용.
 *  이유: 3버튼 가로 통일로 전화번호가 붙으면 균형 깨짐. */
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_cta_show_phone_v3373' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_cta_btn_show_phone', null );
	if ( is_string( $stored ) && strtolower( trim( $stored ) ) === 'yes' ) {
		remove_theme_mod( 'md_content_cta_btn_show_phone' );
	}
	update_option( 'moondental_cta_show_phone_v3373', 'done' );
} );

/* 일회성 마이그레이션 v3.33.0 · 커스텀 SVG 아이콘 세트 이관.
 *  기존 저장된 값이 옛 이모지(📱 · 🔄 · 🦴 등)로 남아 있으면 새 SVG 키로 업그레이드.
 *  이미 icon: 형식으로 저장된 값은 건드리지 않음. 사용자가 직접 입력한 다른 이모지도 보존. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_icons_v3330' ) === 'done' ) return;

	$icon_maps = array(
		// 지역 인기 진료 (region_popular_items)
		'md_content_region_popular_items' => array(
			'임플란트-센터 | 🦷 |'       => '임플란트-센터 | icon:implant |',
			'투명교정-센터 | ✨ |'       => '투명교정-센터 | icon:ortho |',
			'심미치료 | 💎 |'            => '심미치료 | icon:aesthetic |',
			'자연치아-살리기 | 🌿 |'     => '자연치아-살리기 | icon:preserve |',
			'사랑니-발치 | 🦴 |'         => '사랑니-발치 | icon:wisdom |',
			'사랑니-발치 | 📱 |'         => '사랑니-발치 | icon:wisdom |',
		),
		// 의료진 진료과 (doctors_specialties)
		'md_content_doctors_specialties' => array(
			'🦷 | 치과보철과'    => 'icon:implant | 치과보철과',
			'✨ | 치과교정과'    => 'icon:ortho | 치과교정과',
			'🌿 | 치과보존과'    => 'icon:preserve | 치과보존과',
			'🩺 | 치주과'        => 'icon:leaf | 치주과',
			'🧒 | 소아치과'      => 'icon:pediatric | 소아치과',
			'🦴 | 구강악안면외과' => 'icon:wisdom | 구강악안면외과',
			'📱 | 구강악안면외과' => 'icon:wisdom | 구강악안면외과',
		),
	);
	foreach ( $icon_maps as $key => $swaps ) {
		$saved = get_theme_mod( $key );
		if ( ! is_string( $saved ) || $saved === '' ) continue;
		$new = strtr( $saved, $swaps );
		if ( $new !== $saved ) set_theme_mod( $key, $new );
	}
	update_option( 'moondental_icons_v3330', 'done' );
}, 50 );

/* 일회성 마이그레이션 v3.29.0 · 여러 진료시간 안내 텍스트 (cta_hint, price_cta_meta_1_value)
 * 옛 default를 저장했으면 → 목 18:00 → 18:30 자동 갱신 · 앞 0 제거. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_hours_v3290' ) === 'done' ) return;
	$keys = array(
		'md_content_cta_hint',
		'md_content_price_cta_meta_1_value',
	);
	foreach ( $keys as $key ) {
		$saved = get_theme_mod( $key );
		if ( ! is_string( $saved ) ) continue;
		$new = str_replace( '목 09:00–18:00', '목 9:00–18:30', $saved );
		$new = str_replace( '목 ~18:00', '목 ~18:30', $new );
		$new = preg_replace( '/\b09:00\b/', '9:00', $new );
		if ( $new !== $saved ) set_theme_mod( $key, $new );
	}
	update_option( 'moondental_hours_v3290', 'done' );
}, 43 );

/* 일회성 마이그레이션: 푸터 · 사용자 요청으로 제거된 필드들 DB에서 정리 (v3.28.2)
 * 주소·전화는 원래 info 배열에서 오므로 여기선 표시 로직만 제거함.
 * 필드 자체가 없어진 것들: privacy_officer, biz_no, disclaimer, copyright_start, inst_type, biz_cert_url. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_footer_v3282' ) === 'done' ) return;
	$to_remove = array(
		'md_content_footer_legal_privacy_officer',
		'md_content_footer_legal_biz_no',
		'md_content_footer_legal_disclaimer',
		'md_content_footer_legal_copyright_start',
		'md_content_footer_legal_inst_type',
		'md_content_footer_legal_biz_cert_url',
	);
	foreach ( $to_remove as $key ) remove_theme_mod( $key );
	update_option( 'moondental_footer_v3282', 'done' );
}, 41 );

/* 일회성 마이그레이션: 비용안내 가격표에서 '원부터' → '원' (v3.24.1)
 * 사용자가 커스터마이저에서 편집·저장한 가격표에도 옛 '부터' 표기가 남을 수 있으므로 정정. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_price_from_v3241' ) === 'done' ) return;
	$keys = array(
		'md_content_price_tab_implant_rows',
		'md_content_price_tab_ortho_rows',
		'md_content_price_tab_crown_rows',
		'md_content_price_tab_decay_rows',
		'md_content_price_tab_gum_rows',
		'md_content_price_tab_aesthetic_rows',
		'md_content_price_tab_kids_rows',
		'md_content_price_tab_tmj_rows',
	);
	foreach ( $keys as $k ) {
		$saved = get_theme_mod( $k, '' );
		if ( $saved && strpos( $saved, '원부터' ) !== false ) {
			set_theme_mod( $k, str_replace( '원부터', '원', $saved ) );
		}
	}
	update_option( 'moondental_price_from_v3241', 'done' );
}, 35 );

/* 일회성 마이그레이션: 턱관절 chief 아이콘 🦴 → 😬 (v3.44.65) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_bot_chief_bone_v3465' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_bot_chief_options' );
	if ( is_string( $stored ) && strpos( $stored, '턱관절|🦴|' ) !== false ) {
		$new = str_replace( '턱관절|🦴|', '턱관절|😬|', $stored );
		set_theme_mod( 'md_content_bot_chief_options', $new );
	}
	update_option( 'moondental_bot_chief_bone_v3465', 'done' );
}, 31 );

/* 일회성 마이그레이션: 셀프진단봇 · 임상 증상 기반 재설계 (v3.44.67)
 * v3.44.66 기본값을 임상용어 기반 신규 기본값으로 자동 갱신
 * 사용자 편집본은 보존 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_bot_clinical_v3467' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_bot_questions' );
	if ( is_string( $stored ) && $stored !== '' ) {
		// v3.44.66 시그니처: 옛 '단음식 통증 |' 있고 새 '온도 자극통 |' 없음
		if ( strpos( $stored, '단음식 통증 |' ) !== false
		  && strpos( $stored, '온도 자극통 |' ) === false ) {
			remove_theme_mod( 'md_content_bot_questions' );
		}
	}
	// 인트로 문구 마이그레이션
	if ( get_theme_mod( 'md_content_bot_intro_title' ) === '간단한 자가진단 시작하기' ) {
		remove_theme_mod( 'md_content_bot_intro_title' );
	}
	if ( get_theme_mod( 'md_content_bot_lead' ) === "몇 가지 질문에 답해주시면 가장 적합한 진료과를 추천해드립니다.\n※ 본 진단은 참고용이며, 정확한 진단은 내원 진료가 필요합니다." ) {
		remove_theme_mod( 'md_content_bot_lead' );
	}
	if ( get_theme_mod( 'md_content_bot_result_lead' ) === '증상에 가장 부합하는 진료과를 추천해드립니다.' ) {
		remove_theme_mod( 'md_content_bot_result_lead' );
	}
	update_option( 'moondental_bot_clinical_v3467', 'done' );
}, 29 );

/* 일회성 마이그레이션: 셀프진단봇 10개 → 50개 (카테고리별 10문항 · v3.44.66)
 * 사용자가 편집 안 한 v3.44.64 default 를 자동 리셋 → 새 기본값 적용
 * 사용자 편집본은 보존 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_bot_50q_v3466' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_bot_questions' );
	if ( is_string( $stored ) && $stored !== '' ) {
		// v3.44.64 10-질문 default 시그니처: '심한 치통·부종·고름' 있고 새 '자발통 |' 없음
		if ( strpos( $stored, '심한 치통·부종·고름·삼키기' ) !== false
		  && strpos( $stored, '자발통 |' ) === false ) {
			remove_theme_mod( 'md_content_bot_questions' );
		}
	}
	if ( get_theme_mod( 'md_content_bot_count_template' ) === '{count}개의 Yes/No 질문 · 약 1분 소요 · 모든 진료영역 망라' ) {
		remove_theme_mod( 'md_content_bot_count_template' );
	}
	update_option( 'moondental_bot_50q_v3466', 'done' );
}, 30 );

/* 일회성 마이그레이션: 셀프진단봇 · 30개 질문 → 10개 압축 (v3.44.64)
 * 사용자가 Customizer에서 편집하지 않은 옛 30-질문 기본값이면 remove_theme_mod
 * → 새 10개 질문 기본값 자동 적용. 사용자 편집본은 보존. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_bot_10q_v3464' ) === 'done' ) return;
	$stored = get_theme_mod( 'md_content_bot_questions' );
	if ( is_string( $stored ) && $stored !== '' ) {
		// 옛 30-질문 default 감지: 특유 문구 두 개가 모두 있으면 옛 기본값
		if ( strpos( $stored, '심한 통증 |' ) !== false
		  && strpos( $stored, '임신·수유 |' ) !== false
		  && strpos( $stored, 'urgent' ) === false
		  && strpos( $stored, 'contra=' ) === false ) {
			remove_theme_mod( 'md_content_bot_questions' );
		}
	}
	// bot_count_template 이 옛 '약 2-3분 소요' 값이면 리셋
	if ( get_theme_mod( 'md_content_bot_count_template' ) === '{count}개의 Yes/No 질문 · 약 2-3분 소요 · 모든 진료영역 망라' ) {
		remove_theme_mod( 'md_content_bot_count_template' );
	}
	update_option( 'moondental_bot_10q_v3464', 'done' );
}, 32 );

/* 일회성 마이그레이션: '천안·아산 문치과병원' → '천안·아산 문치과병원' notices_title 정리 (v3.23.3) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_cheonan_asan_brand_v3233' ) === 'done' ) return;
	if ( get_theme_mod( 'md_content_notices_title' ) === '천안·아산 문치과병원 소식' ) {
		remove_theme_mod( 'md_content_notices_title' );
	}
	update_option( 'moondental_cheonan_asan_brand_v3233', 'done' );
}, 34 );

/* 일회성 마이그레이션: 천안 → 천안·아산 포지셔닝 — 옛 기본값만 정리 (v3.23.0)
 * 사용자가 커스터마이저에서 안 만진 옛 기본값만 remove_theme_mod → 새 기본값 자동 적용
 * 사용자가 직접 다른 값을 입력해둔 경우는 보존 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_cheonan_asan_v3230' ) === 'done' ) return;
	$cleanups = array(
		'moondental_hero_title_a'  => '천안·아산에서 30여년,',
		'moondental_hero_lead'     => "천안·아산 임플란트·천안·아산 투명교정·천안·아산 라미네이트·천안·아산 자연치아 살리기까지.\n분야별 전문 의료진이 한 자리에서 — 충분히 듣고, 꼭 필요한 치료만 권합니다.",
		'moondental_doctor_lead'   => '1995년부터 천안에서, 한 분의 환자를 가족처럼 오래 보아왔습니다. 진료실 밖에서도 지역사회와 함께 가는 치과를 꿈꿉니다.',
		'md_content_why_title'         => '천안에서 왜 문치과병원을 찾으시나요?',
		'md_content_services_title'    => '천안에서 한 곳에서, 평생 치아 건강을',
		'md_content_services_eyebrow'  => 'CLINICAL SERVICES · 천안 진료항목',
		'md_content_services_lead'     => '천안·아산 임플란트·천안·아산 투명교정·천안·아산 라미네이트·천안·아산 자연치아 살리기·천안·아산 사랑니 발치까지 — 한 분의 환자를 오래 보는 천안 만남로 치과의 마음으로 진료합니다.',
	);
	foreach ( $cleanups as $mod_key => $old_default ) {
		if ( get_theme_mod( $mod_key ) === $old_default ) {
			remove_theme_mod( $mod_key );
		}
	}
	update_option( 'moondental_cheonan_asan_v3230', 'done' );
}, 33 );

/* 일회성 마이그레이션: 의료진 페이지 카운트·층 표기 제거 — 옛 기본값만 정리 (v3.22.4) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_doctors_strip_v3224' ) === 'done' ) return;
	$cleanups = array(
		'md_content_doctors_title_b'    => '인 의료진 협진',
		'md_content_staff_section_lead' => '의료진과 함께 환자분의 편안한 진료를 위해 일하는 전체 스태프입니다.',
	);
	foreach ( $cleanups as $mod_key => $old_default ) {
		if ( get_theme_mod( $mod_key ) === $old_default ) {
			remove_theme_mod( $mod_key );
		}
	}
	update_option( 'moondental_doctors_strip_v3224', 'done' );
}, 32 );

/* 일회성 마이그레이션: 직원 명단 오타 정정 — 게롤레 → 게를레 (v3.22.2) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_typo_fix_v3222' ) === 'done' ) return;
	$saved = get_theme_mod( 'md_content_staff_list', '' );
	if ( $saved && strpos( $saved, '게롤레' ) !== false ) {
		set_theme_mod( 'md_content_staff_list', str_replace( '게롤레', '게를레', $saved ) );
	}
	update_option( 'moondental_staff_typo_fix_v3222', 'done' );
}, 30 );

/* 일회성 마이그레이션: 직원 명단 갱신 (v3.22.5)
 *  - 명의재 → 맹의재 (기공실 차장)
 *  - 이중현 → 이충현 (경영지원실 과장)
 *  - 진료실 실장 지선미 복귀 (실장 임은혜 뒤에 삽입) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_update_v3225' ) === 'done' ) return;
	$saved = get_theme_mod( 'md_content_staff_list', '' );
	if ( $saved ) {
		$saved = str_replace( '기공실|차장|명의재', '기공실|차장|맹의재', $saved );
		$saved = str_replace( '경영지원실|과장|이중현', '경영지원실|과장|이충현', $saved );
		// 지선미 복귀 — 이미 있으면 skip
		if ( strpos( $saved, '진료실|실장|지선미' ) === false ) {
			$saved = str_replace(
				'진료실|실장|임은혜',
				"진료실|실장|임은혜\n진료실|실장|지선미",
				$saved
			);
		}
		set_theme_mod( 'md_content_staff_list', $saved );
	}
	update_option( 'moondental_staff_update_v3225', 'done' );
}, 32 );

/* 일회성 마이그레이션: 직원 명단 갱신 (v3.22.3)
 *  - 박진욱 → 박진옥
 *  - 박주영·엄혜빈·지선미 제거
 *  - 관리사무소|소장|강성하 추가 (없을 때만) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_staff_update_v3223' ) === 'done' ) return;
	$saved = get_theme_mod( 'md_content_staff_list', '' );
	if ( $saved ) {
		// 박진욱 → 박진옥
		$saved = str_replace( '기공실|기사|박진욱', '기공실|기사|박진옥', $saved );
		// 라인 제거
		$lines = explode( "\n", $saved );
		$remove = array( '진료실|주임|박주영', '진료실|주임|엄혜빈', '진료실|실장|지선미' );
		$lines = array_values( array_filter( $lines, function( $l ) use ( $remove ) {
			return ! in_array( trim( $l ), $remove, true );
		} ) );
		// 강성하 추가 (중복 방지)
		$new_line = '관리사무소|소장|강성하';
		$already = false;
		foreach ( $lines as $l ) { if ( trim( $l ) === $new_line ) { $already = true; break; } }
		if ( ! $already ) $lines[] = $new_line;
		set_theme_mod( 'md_content_staff_list', implode( "\n", $lines ) );
	}
	update_option( 'moondental_staff_update_v3223', 'done' );
}, 31 );

/* 일회성 마이그레이션: 시드 글 자동 생성 (v3.21.8)
 * /병원소식/ 페이지가 비어 있는 상태 해결 — 실제 병원 정보 기반 글을 자동 생성.
 * 모두 publish 상태 + 적절한 카테고리에 배정.
 * 어드민은 wp-admin에서 자유롭게 편집·삭제·사진 추가 가능. */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_seed_posts_v3218' ) === 'done' ) return;

	$notice_cat = get_term_by( 'slug', 'notice', 'category' );
	$story_cat  = get_term_by( 'slug', 'dental-stories', 'category' );
	if ( ! $notice_cat ) {
		$r = wp_insert_term( '문치과병원 소식', 'category', array( 'slug' => 'notice' ) );
		if ( ! is_wp_error( $r ) ) $notice_cat = get_term( $r['term_id'], 'category' );
	}
	if ( ! $story_cat ) {
		$r = wp_insert_term( '문치과병원 치아이야기', 'category', array( 'slug' => 'dental-stories' ) );
		if ( ! is_wp_error( $r ) ) $story_cat = get_term( $r['term_id'], 'category' );
	}
	if ( ! $notice_cat || ! $story_cat ) return;

	$seeds = array(
		// ── 소식 (5) ───────────────────────
		array( 'cat'=>'notice', 'title'=>'야간 진료 안내 — 월·화·수·금 20:30까지',
			'body'=>"<p>천안 만남로 문치과병원은 직장인·학생 환자분들을 위해 평일 야간 진료를 운영하고 있습니다.</p>
<ul>
<li>월·화·수·금: <strong>09:00 ~ 20:30</strong> (점심시간 없음)</li>
<li>목요일: 09:00 ~ 18:30 (야간 진료 없음)</li>
<li>토요일: 09:00 ~ 14:00</li>
<li>일요일·공휴일: 휴진</li>
</ul>
<p>야간 진료도 예약제로 운영되니, 방문 전 네이버 예약·전화(041-563-2875)·카카오톡 채널로 시간 확인 부탁드립니다.</p>" ),

		array( 'cat'=>'notice', 'title'=>'주차 안내 — 본원 지하 + SUV 신부 제5공영주차장',
			'body'=>"<p>문치과병원 방문 시 무료 주차를 이용하실 수 있습니다.</p>
<h3>본원 지하 기계식 주차장</h3>
<p>승용차 주차 가능. 도착 후 1층 데스크에서 차량 번호 접수해주시면 무료로 등록됩니다.</p>
<h3>SUV·대형차 — 신부 제5공영주차장</h3>
<p>주소: 동남구 먹거리1길 10 (병원에서 도보 약 2분). 데스크 접수 시 무료 등록됩니다.</p>
<p>대중교통: 천안종합고속버스터미널에서 도보 약 5분, 천안역에서 버스로 약 10분.</p>" ),

		array( 'cat'=>'notice', 'title'=>'한아의료재단 문치과병원 — 천안 만남로 30여년의 진료',
			'body'=>"<p>문치과병원은 1995년 천안에서 시작해 한아의료재단 산하 치과병원으로 자리잡아, 천안·아산 지역의 대표 치과병원으로 성장했습니다.</p>
<p>현재는 동남구 만남로 52 문타워 9·10·11·13층의 병원급 시설에서 임플란트·교정·자연치아 살리기·턱관절·소아치과 등 전 진료과 협진 체계로 운영하고 있습니다.</p>
<p>오랜 진료 경험과 디지털 진단 장비(CBCT·구강스캐너)를 바탕으로 환자분께 꼭 필요한 치료만 권해드립니다.</p>" ),

		array( 'cat'=>'notice', 'title'=>'예약 방법 — 네이버·전화·카카오톡 모두 가능',
			'body'=>"<p>편하신 방법으로 예약·상담 신청해주세요. 진료시간 내 빠르게 응답드립니다.</p>
<ul>
<li><strong>네이버 예약</strong> — 24시간 자동 예약. 원하시는 시간 직접 선택.</li>
<li><strong>전화 041-563-2875</strong> — 진료시간 내 빠른 응답.</li>
<li><strong>카카오톡 채널</strong> — 문진·증상 상담 + 예약 확정.</li>
</ul>
<p>예약 변경·취소: 네이버 예약은 예약 페이지에서 직접 가능, 그 외에는 전화·카카오톡으로 부탁드립니다.</p>" ),

		array( 'cat'=>'notice', 'title'=>'정기 검진 권장 — 6개월에 한 번',
			'body'=>"<p>치과 질환은 초기에 발견하면 치료 비용과 통증 모두 크게 줄일 수 있습니다.</p>
<p>충치·잇몸염·치주염은 자각 증상이 늦게 나타나므로, 6개월에 한 번 정기 검진을 권장드립니다. 스케일링은 1년에 한 번 건강보험 적용을 받으실 수 있습니다.</p>
<p>정기 검진 예약은 네이버 예약 또는 전화 041-563-2875로 부탁드립니다.</p>" ),

		// ── 치아이야기 (8) ───────────────────
		array( 'cat'=>'story', 'title'=>'임플란트, 어떤 종류가 있고 어떻게 선택할까요?',
			'body'=>"<p>임플란트는 자연치아를 대체할 수 있는 가장 효과적인 치료법 중 하나입니다. 종류와 선택 기준을 정리했습니다.</p>
<h3>임플란트 구성</h3>
<p>임플란트는 픽스처(뼈에 식립되는 부분), 어버트먼트(연결 부위), 크라운(보철물)으로 구성됩니다.</p>
<h3>선택 시 고려 사항</h3>
<ul>
<li>잔존 골 양과 골질 — CBCT로 정확히 진단</li>
<li>전신 건강 상태 (당뇨·골다공증 등)</li>
<li>심미적 요구도 — 앞니 vs 어금니</li>
<li>사후 관리 — 정기 검진과 칫솔질 관리</li>
</ul>
<p>식립 전 상담에서 본인 구강 상태에 맞는 옵션을 자세히 안내해드립니다.</p>" ),

		array( 'cat'=>'story', 'title'=>'자연치아 살리기 — 신경치료가 왜 중요한가',
			'body'=>"<p>충치나 외상으로 치아 신경(치수)이 손상되면 신경치료(근관치료)로 자연치아를 살릴 수 있습니다.</p>
<h3>신경치료가 필요한 신호</h3>
<ul>
<li>찬·뜨거운 자극에 오래 시린 통증</li>
<li>아무 자극 없이 욱신거리는 통증</li>
<li>씹을 때 특정 치아만 아픔</li>
<li>잇몸이 부어오르거나 고름 주머니</li>
</ul>
<p>방치 시 발치까지 이어질 수 있으므로 증상이 있으면 빠르게 진단받으시는 게 좋습니다. 디지털 치근단 X-ray와 CBCT로 정확한 진단을 진행합니다.</p>" ),

		array( 'cat'=>'story', 'title'=>'슈어스마일 투명교정 — 디지털 교정의 정확도',
			'body'=>"<p>문치과병원은 슈어스마일(SureSmile) 충부권 교정센터로 지정되어 정밀 디지털 교정을 운영하고 있습니다.</p>
<h3>슈어스마일의 특징</h3>
<ul>
<li>3D 스캐너로 구강 전체를 정밀 스캔</li>
<li>치아 이동 경로를 시뮬레이션 후 단계별 투명 장치 제작</li>
<li>식사·양치 시 분리 가능 — 위생 관리 용이</li>
<li>치료 기간 단축 가능 (개인차 있음)</li>
</ul>
<p>성인 환자분, 직장인 환자분, 결혼 준비 등 단기 심미 개선이 필요하신 분들께 적합합니다.</p>" ),

		array( 'cat'=>'story', 'title'=>'사랑니, 꼭 빼야 할까? 발치 기준 정리',
			'body'=>"<p>사랑니는 모두 빼야 하는 건 아닙니다. 다만 다음과 같은 경우에는 발치를 권장합니다.</p>
<h3>발치를 권장하는 경우</h3>
<ul>
<li>매복돼 있어 칫솔이 닿지 않고 충치·치주염을 일으킬 때</li>
<li>비스듬히 누워 앞 어금니를 누르거나 미는 경우</li>
<li>반복적으로 잇몸이 부어오르거나 통증이 있는 경우</li>
<li>교정 치료 시 공간 확보가 필요한 경우</li>
</ul>
<h3>유지해도 되는 경우</h3>
<p>완전히 맹출해서 칫솔이 잘 닿고, 위·아래 치아가 정상적으로 맞물리며 충치가 없는 경우는 유지하셔도 됩니다.</p>
<p>매복 사랑니는 CBCT로 신경 위치까지 정확히 파악한 뒤 발치를 진행합니다.</p>" ),

		array( 'cat'=>'story', 'title'=>'라미네이트로 앞니 모양·색 개선하기',
			'body'=>"<p>라미네이트는 앞니 표면에 얇은 도자기 막을 부착해 형태·색을 개선하는 심미 치료입니다.</p>
<h3>라미네이트 적합한 경우</h3>
<ul>
<li>치아 사이가 벌어진 경우</li>
<li>치아 색이 변색돼 미백으로 해결되지 않는 경우</li>
<li>치아 모양이 작거나 비대칭인 경우</li>
<li>가벼운 부정교합 (성인)</li>
</ul>
<p>치아 삭제량이 적은 무삭제·미세 삭제 라미네이트도 가능하며, 본인 치아 상태에 따라 최적 방식을 안내해드립니다.</p>" ),

		array( 'cat'=>'story', 'title'=>'잇몸 치료 — 스케일링부터 치주 수술까지',
			'body'=>"<p>잇몸 질환은 진행 단계에 따라 치료법이 달라집니다.</p>
<h3>단계별 치료</h3>
<ul>
<li><strong>치은염</strong> — 잇몸만 염증. 스케일링으로 치석 제거 → 회복 가능.</li>
<li><strong>경증 치주염</strong> — 치근 표면까지 염증. 치근활택술(SRP)로 치근 표면 세척.</li>
<li><strong>중등도 치주염</strong> — 치조골 흡수 시작. 치주 소파술 등으로 깊은 부위까지 청소.</li>
<li><strong>중증 치주염</strong> — 치아 흔들림. 치주 수술 또는 골 이식까지 고려.</li>
</ul>
<p>치주 질환은 자각 증상이 늦게 나타나기 때문에, 정기 검진과 1년 1회 보험 스케일링을 적극 권장드립니다.</p>" ),

		array( 'cat'=>'story', 'title'=>'턱관절 통증·이갈이 — 야간 마우스피스로 관리',
			'body'=>"<p>아침에 턱이 뻐근하거나 입을 크게 벌릴 때 '딱' 소리가 나면 턱관절 장애를 의심해볼 수 있습니다.</p>
<h3>주요 원인</h3>
<ul>
<li>야간 이갈이·이악물기</li>
<li>스트레스로 인한 턱 근육 긴장</li>
<li>한쪽으로만 음식 씹는 습관</li>
<li>외상이나 잘못된 자세</li>
</ul>
<h3>관리 방법</h3>
<p>야간용 마우스피스(스플린트)로 이갈이로 인한 치아 마모와 턱관절 부담을 줄일 수 있습니다. 증상이 심한 경우 물리치료·약물치료를 병행합니다.</p>
<p>턱관절 클리닉에서 정확한 진단 후 본인에게 맞는 관리법을 안내해드립니다.</p>" ),

		array( 'cat'=>'story', 'title'=>'정기 검진 + 스케일링 — 1년에 한 번 보험 적용',
			'body'=>"<p>건강한 구강 관리의 시작은 정기 검진과 스케일링입니다.</p>
<h3>스케일링 보험 적용</h3>
<p>만 19세 이상은 1년에 1회(7월 1일~다음 해 6월 30일 기준) 스케일링 건강보험 적용을 받으실 수 있습니다.</p>
<h3>정기 검진에서 확인하는 것</h3>
<ul>
<li>충치·잇몸염 조기 발견</li>
<li>치석·플라크 제거</li>
<li>임플란트·교정 장치 상태 점검</li>
<li>구강암 초기 변화 관찰</li>
</ul>
<p>치아는 한 번 망가지면 자연 회복이 어렵습니다. 작은 신호일 때 발견하는 게 시간·비용 모두 절약하는 길입니다.</p>" ),
	);

	$now = current_time( 'mysql' );
	$gmt = gmdate( 'Y-m-d H:i:s' );
	$created = 0;
	foreach ( $seeds as $i => $seed ) {
		// 중복 방지: 동일 제목 글이 이미 있으면 skip
		$exists = get_page_by_title( $seed['title'], OBJECT, 'post' );
		if ( $exists ) continue;
		$cat_id = ( $seed['cat'] === 'notice' ) ? (int) $notice_cat->term_id : (int) $story_cat->term_id;
		$pid = wp_insert_post( array(
			'post_title'    => $seed['title'],
			'post_content'  => $seed['body'],
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_author'   => 1,
			'post_date'     => $now,
			'post_date_gmt' => $gmt,
			'post_category' => array( $cat_id ),
			'meta_input'    => array(
				'moondental_seed_post' => '1',
				'moondental_seed_order'=> $i,
			),
		), true );
		if ( ! is_wp_error( $pid ) ) $created++;
	}

	update_option( 'moondental_seed_posts_v3218', 'done' );
	update_option( 'moondental_seed_posts_count_v3218', $created );
}, 35 );


/* 일회성 마이그레이션: 네이버 임포트 글 일괄 휴지통 (v3.21.5)
 * 네이버 CDN 이미지 referer 블록으로 본문 사진이 안 보이고, 분류도 어긋나서
 * 깔끔하게 정리. 휴지통에 보내므로 워드프레스에서 언제든 복원 가능.
 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_naver_purge_v3215' ) === 'done' ) return;
	$ids = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'numberposts'    => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array( 'key' => 'moondental_naver_log_no', 'compare' => 'EXISTS' ),
		),
	) );
	foreach ( $ids as $pid ) {
		wp_trash_post( $pid );
	}
	update_option( 'moondental_naver_purge_v3215', 'done' );
	update_option( 'moondental_naver_purge_count_v3215', count( $ids ) );
}, 28 );

/* 일회성 마이그레이션: Google Maps URL 강제 적용 (v3.21.6)
 * 사용자가 어떤 값을 저장했든 새 단축 링크로 강제 설정.
 * (theme_mod / 옛 maps/search 형식 / 이전 단축 링크 모두 MNt59kcxeKL92nCU9로 통일) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_gmap_url_migration_v3216' ) === 'done' ) return;
	set_theme_mod( 'moondental_google_map_url', 'https://maps.app.goo.gl/MNt59kcxeKL92nCU9' );
	update_option( 'moondental_gmap_url_migration_v3216', 'done' );
}, 30 );

/* 일회성 마이그레이션: 옛 길어진 CTA 라벨 → 짧은 라벨로 정리 (v3.20.2) */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_cta_label_migration_v3202' ) === 'done' ) return;
	$old_naver = '📅 네이버 예약하기';
	$old_kakao = '💬 카카오톡 상담하기';
	if ( get_theme_mod( 'cta_btn_naver_label' ) === $old_naver ) remove_theme_mod( 'cta_btn_naver_label' );
	if ( get_theme_mod( 'cta_btn_kakao_label' ) === $old_kakao ) remove_theme_mod( 'cta_btn_kakao_label' );
	update_option( 'moondental_cta_label_migration_v3202', 'done' );
}, 30 );

/* 일회성 마이그레이션: 카테고리 이름 한글 통일 (v3.20.3)
 * 공지사항 → 문치과병원 소식
 * 치아이야기 → 문치과병원 치아이야기
 * slug(notice, dental-stories)는 그대로 유지 → URL 변동 없음.
 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_cat_rename_migration_v3203' ) === 'done' ) return;
	$notice_target = '문치과병원 소식';
	$story_target  = '문치과병원 치아이야기';

	$notice = get_term_by( 'slug', 'notice', 'category' );
	if ( ! $notice ) $notice = get_term_by( 'name', '공지사항', 'category' );
	if ( $notice && $notice->name !== $notice_target ) {
		wp_update_term( $notice->term_id, 'category', array( 'name' => $notice_target ) );
	}

	$story = get_term_by( 'slug', 'dental-stories', 'category' );
	if ( ! $story ) $story = get_term_by( 'name', '치아이야기', 'category' );
	if ( ! $story ) $story = get_term_by( 'name', '치과이야기', 'category' );
	if ( $story && $story->name !== $story_target ) {
		wp_update_term( $story->term_id, 'category', array( 'name' => $story_target ) );
	}

	update_option( 'moondental_cat_rename_migration_v3203', 'done' );
}, 31 );

/* 일회성 마이그레이션 v3.21.0 — bulletproof 카테고리 통합 + 재분류 강제.
 *
 * 이전 v3.20.5 마이그레이션 버그: 정식 cat(slug 'notice'/'dental-stories')이 DB에 없으면
 * 일찍 return 해서 별칭 cat 통합이 안 됨. → 정식 cat이 항상 존재하도록 보장 후 진행.
 *
 * 동작 순서:
 *  1) 'notice' 정식 cat 확보:
 *     a) slug='notice' term이 있으면 사용
 *     b) 없으면 name in [문치과병원 소식/공지사항/소식/뉴스/공지/announcement/news] 중
 *        가장 먼저 찾은 term의 slug를 'notice'로 변경하고 이름을 '문치과병원 소식'으로 통일
 *     c) 그래도 없으면 새로 생성 (name='문치과병원 소식', slug='notice')
 *  2) 'dental-stories' 정식 cat 동일 로직
 *  3) 남은 별칭 cat들의 글을 정식 cat으로 이전 후 별칭 cat 삭제
 *  4) recategorize_posts() 호출 → 키워드 기반 재분류
 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_cat_consolidate_v3211' ) === 'done' ) return;
	// recategorize_posts() 정의가 아직 로드되지 않았을 수 있어 functions.php 끝까지 기다림
	if ( ! function_exists( 'moondental_recategorize_posts' ) ) return;

	$notice_target_name = '문치과병원 소식';
	$story_target_name  = '문치과병원 치아이야기';

	$notice_aliases = array( '문치과병원 소식', '공지사항', '소식', '뉴스', '공지', 'announcement', 'news' );
	$story_aliases  = array( '문치과병원 치아이야기', '치아이야기', '치과이야기', '치아 이야기', '치과 이야기', 'dental story', 'dental stories' );

	// ── 1) notice 정식 cat 확보 ─────────────────
	$notice_cat = get_term_by( 'slug', 'notice', 'category' );
	if ( ! $notice_cat ) {
		foreach ( $notice_aliases as $alias_name ) {
			$t = get_term_by( 'name', $alias_name, 'category' );
			if ( $t ) {
				wp_update_term( $t->term_id, 'category', array(
					'name' => $notice_target_name,
					'slug' => 'notice',
				) );
				$notice_cat = get_term( $t->term_id, 'category' );
				break;
			}
		}
	}
	if ( ! $notice_cat ) {
		$r = wp_insert_term( $notice_target_name, 'category', array( 'slug' => 'notice' ) );
		if ( ! is_wp_error( $r ) ) $notice_cat = get_term( $r['term_id'], 'category' );
	}
	if ( $notice_cat && $notice_cat->name !== $notice_target_name ) {
		wp_update_term( $notice_cat->term_id, 'category', array( 'name' => $notice_target_name ) );
	}

	// ── 2) dental-stories 정식 cat 확보 ─────────
	$story_cat = get_term_by( 'slug', 'dental-stories', 'category' );
	if ( ! $story_cat ) {
		foreach ( $story_aliases as $alias_name ) {
			$t = get_term_by( 'name', $alias_name, 'category' );
			if ( $t ) {
				wp_update_term( $t->term_id, 'category', array(
					'name' => $story_target_name,
					'slug' => 'dental-stories',
				) );
				$story_cat = get_term( $t->term_id, 'category' );
				break;
			}
		}
	}
	if ( ! $story_cat ) {
		$r = wp_insert_term( $story_target_name, 'category', array( 'slug' => 'dental-stories' ) );
		if ( ! is_wp_error( $r ) ) $story_cat = get_term( $r['term_id'], 'category' );
	}
	if ( $story_cat && $story_cat->name !== $story_target_name ) {
		wp_update_term( $story_cat->term_id, 'category', array( 'name' => $story_target_name ) );
	}

	if ( ! $notice_cat || ! $story_cat ) {
		// 어떤 이유로든 확보 실패 — 다음 요청에서 재시도 (옵션 저장 안 함)
		return;
	}

	// ── 3) 남은 별칭 cat 모두 정식으로 흡수 ─────
	$alias_to_target = array();
	foreach ( $notice_aliases as $n ) { $alias_to_target[ $n ] = $notice_cat->term_id; }
	foreach ( $story_aliases  as $s ) { $alias_to_target[ $s ] = $story_cat->term_id; }

	foreach ( $alias_to_target as $alias_name => $target_id ) {
		$alias = get_term_by( 'name', $alias_name, 'category' );
		if ( ! $alias ) continue;
		if ( (int) $alias->term_id === (int) $target_id ) continue;

		// 해당 별칭 cat의 모든 글을 정식 cat으로 이전
		$ids = get_posts( array(
			'post_type'   => 'post',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'numberposts' => -1,
			'fields'      => 'ids',
			'tax_query'   => array(
				array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $alias->term_id ),
			),
		) );
		foreach ( $ids as $pid ) {
			wp_set_post_categories( $pid, array( (int) $target_id ), false );
		}
		wp_delete_term( $alias->term_id, 'category' );
	}

	// ── 4) 키워드 기반 재분류 강제 실행 ────────
	moondental_recategorize_posts();

	update_option( 'moondental_cat_consolidate_v3211', 'done' );
}, 99 );

/* 네이버 블로그 연동 OFF — page-news.php empty-state 라이브 RSS는 제거됨.
 * 이미 가져온 글은 그대로 두고, 추가 자동 동기화는 안 함.
 * 어드민 도구의 '네이버 가져오기' 버튼은 일회성 복사 도구로만 사용.
 */
add_filter( 'moondental_naver_live_enabled', '__return_false' );

require_once MOONDENTAL_DIR . '/inc/content-defaults.php';
require_once MOONDENTAL_DIR . '/inc/naver-importer.php';
require_once MOONDENTAL_DIR . '/inc/reservation.php';
require_once MOONDENTAL_DIR . '/inc/enhancements.php';
require_once MOONDENTAL_DIR . '/inc/seo-boost.php';
require_once MOONDENTAL_DIR . '/inc/customizer-content.php';
require_once MOONDENTAL_DIR . '/inc/auto-translate.php'; // v3.44.0
require_once MOONDENTAL_DIR . '/inc/strengths.php';
require_once MOONDENTAL_DIR . '/inc/regions.php';
require_once MOONDENTAL_DIR . '/inc/icons.php';
require_once MOONDENTAL_DIR . '/inc/admin-dashboard.php';
require_once MOONDENTAL_DIR . '/inc/encyclopedia.php';
require_once MOONDENTAL_DIR . '/inc/encyclopedia-seed.php';
require_once MOONDENTAL_DIR . '/inc/encyclopedia-seed-v3352.php';
require_once MOONDENTAL_DIR . '/inc/encyclopedia-seed-v3353.php';
require_once MOONDENTAL_DIR . '/inc/encyclopedia-seed-v3354.php';
require_once MOONDENTAL_DIR . '/inc/encyclopedia-seed-v3355.php';


/* ============================================================
 * 1. Theme Setup
 * ========================================================== */

/**
 * 메뉴 위치, 이미지 사이즈, 테마 지원 기능 등록
 */
function moondental_theme_setup() {
	register_nav_menus( array(
		'primary'    => '주 메뉴 (헤더)',
		'utility'    => '유틸리티 메뉴 (헤더 상단 우측)',
		'footer'     => '푸터 메뉴',
	) );

	add_theme_support( 'post-thumbnails' );

	// 진료영역 카드용 이미지
	add_image_size( 'moondental-service',  640, 480, true );
	// 의료진 인물 사진
	add_image_size( 'moondental-doctor',   720, 900, true );
	// 히어로 이미지
	add_image_size( 'moondental-hero',    1280, 1600, true );
	// 시설 갤러리
	add_image_size( 'moondental-gallery', 1024,  768, true );
}
add_action( 'after_setup_theme', 'moondental_theme_setup', 11 );


/* ============================================================
 * 2. Asset Enqueue (CSS / JS)
 * ========================================================== */

/**
 * 부모 테마(Astra) + Pretendard + 자식 테마 스타일 enqueue.
 * 로딩 순서: Astra → Pretendard → Child (Child가 마지막이어야 오버라이드 가능)
 */
function moondental_enqueue_styles() {

	// 1. 부모 테마 스타일
	wp_enqueue_style(
		'astra-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( 'astra' )->get( 'Version' )
	);

	// 2. Pretendard Variable
	wp_enqueue_style(
		'pretendard-variable',
		'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/variable/pretendardvariable-dynamic-subset.css',
		array(),
		'1.3.9'
	);

	// 3. 자식 테마 스타일 — 프로덕션 미니파이 · 로그인/디버그 시 원본 (v3.44.31)
	$min_path  = MOONDENTAL_DIR . '/style.min.css';
	$full_path = MOONDENTAL_DIR . '/style.css';
	$use_min   = file_exists( $min_path )
		&& ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG )
		&& ! is_user_logged_in();
	$css_path  = $use_min ? $min_path : $full_path;
	$css_url   = $use_min ? MOONDENTAL_URI . '/style.min.css' : MOONDENTAL_URI . '/style.css';
	$css_ver   = file_exists( $css_path ) ? filemtime( $css_path ) : MOONDENTAL_VERSION;
	wp_enqueue_style(
		'moondental-child-style',
		$css_url,
		array( 'astra-parent-style', 'pretendard-variable' ),
		$css_ver
	);

	// 4. 추가 인터랙션 JS (필요 시)
	$js_path = MOONDENTAL_DIR . '/assets/js/main.js';
	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'moondental-main',
			MOONDENTAL_URI . '/assets/js/main.js',
			array(),
			filemtime( $js_path ),
			true
		);
		// v3.38.4 · 자가진단 봇 결과 문구를 Customizer에서 편집 가능하게
		wp_localize_script( 'moondental-main', 'MoondentalMain', array(
			'bot' => array(
				'noneMatch'   => md_content( 'bot_result_none', '특별한 증상은 없으신 것 같습니다. 정기 검진·스케일링을 권해드립니다.' ),
				'singleBest'  => md_content( 'bot_result_single', '아래 진료과가 가장 적합합니다.' ),
				'multipleTpl' => md_content( 'bot_result_multiple', '아래 {n}개 진료과를 우선순위로 추천드립니다.' ),
				'matchAria'   => md_content( 'bot_match_aria', '적합도' ),
				'matchTpl'    => md_content( 'bot_match_tpl',  '적합도 {pct}%' ),
				'urgentLabel' => md_content( 'bot_urgent_label', '⚡ 우선 상담 권장' ),
			),
			'lang' => array(
				'ko' => md_content( 'lang_ko', '한국어' ),
				'en' => md_content( 'lang_en', '영어' ),
				'ja' => md_content( 'lang_ja', '일본어' ),
				'zh' => md_content( 'lang_zh', '중국어' ),
				'ru' => md_content( 'lang_ru', '러시아어' ),
				'vi' => md_content( 'lang_vi', '베트남어' ),
			),
		) );
	}

	// 5. 언어 전환 — v3.25.7에서 커스텀 스위처 완전 제거. GTranslate 플러그인이 담당.
}
add_action( 'wp_enqueue_scripts', 'moondental_enqueue_styles', 15 );

/*
 * v3.25.7 — 언어 스위처 관련 훅 3개 완전 제거:
 *   1) wp_enqueue_script('moondental-lang-switcher') — JS 로드 안 함
 *   2) Google Translate CDN 직접 삽입 — GTranslate 플러그인이 처리
 *   3) wp_footer 커스텀 스위처 렌더 — 우측 하단 GTranslate 위젯과 겹치던 문제 해소
 * 이로써 GTranslate 플러그인의 플로팅 위젯만 우측 하단에 남고, 커스텀 UI는 DOM 자체에서 사라짐.
 */


/* ============================================================
 * 3. 병원 정보 — Customizer 연동
 * ========================================================== */

/**
 * 사이트 전역에서 사용할 병원 메타 정보.
 * Customizer 값이 있으면 그 값을, 없으면 아래 기본값을 반환한다.
 *
 * 사용 예:
 *   echo esc_html( moondental_get_info( 'phone' ) );
 *   [moondental_info key="phone"]
 *
 * @param string $key 필드 키. 비어있으면 전체 배열 반환.
 * @return mixed
 */
function moondental_get_info( $key = '' ) {
	$defaults = array(
		'name_full'    => '한아의료재단 문치과병원',
		'name_short'   => '문치과병원',
		'name_en'      => 'Moon Dental Hospital',
		'tagline'      => '실력과 품격있는 진료',
		'phone'        => '041-563-2875',
		'phone_link'   => '0415632875',
		'address'      => '충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동)',
		'address_road' => '충남 천안시 동남구 만남로 52, 문타워 9·10·11·13층',
		'hours_wd'     => '평일 09:00 – 20:30 (점심시간 없음)',
		'hours_thu'    => '목요일 9:00 – 18:30',
		'hours_sat'    => '토요일 09:00 – 14:00',
		'hours_lunch'  => '',
		'hours_off'    => '일요일 휴진',
		'biz_no'       => '',
		'med_inst_no'  => '34400117',
		'rep'          => '문은수',
		'email'        => 'moondental1995@naver.com',
		'kakao_url'    => 'http://pf.kakao.com/_VTcgE/chat',
		'instagram'    => 'https://www.instagram.com/moondentalhospital_official/',
		'blog_url'     => 'https://blog.naver.com/moondental1995',
		'facebook_url' => 'https://www.facebook.com/moondentist',
		'youtube_url'  => 'https://www.youtube.com/@%EC%B2%9C%EC%95%88%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90',
		'naver_place'  => 'https://booking.naver.com/booking/13/bizes/485314',
		'naver_review_url' => '', // v3.30.6 · 리뷰 전용 URL (예: https://m.place.naver.com/place/{id}/review)
		'naver_map_url'=> 'https://map.naver.com/p/entry/place/12772165', // v3.44.11 · 병원 네이버 플레이스 직접 링크
		'google_map_url'=> 'https://maps.app.goo.gl/MNt59kcxeKL92nCU9',
		'map_embed'    => '',
	);

	$info = array();
	foreach ( $defaults as $field_key => $default ) {
		$info[ $field_key ] = get_theme_mod( 'moondental_' . $field_key, $default );
	}

	if ( empty( $key ) ) {
		return $info;
	}
	return isset( $info[ $key ] ) ? $info[ $key ] : '';
}

/**
 * 단축코드 [moondental_info key="phone"]
 */
function moondental_info_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'key' => 'phone' ), $atts );
	return esc_html( moondental_get_info( $atts['key'] ) );
}
add_shortcode( 'moondental_info', 'moondental_info_shortcode' );

/**
 * 진료시간 문자열에서 "09:00 – 18:00" 같은 시간 부분만 추출.
 */
function moondental_extract_time_range( $str ) {
	if ( preg_match( '/(\d{1,2}\s*:\s*\d{2}\s*[~\-–—]\s*\d{1,2}\s*:\s*\d{2})/u', (string) $str, $m ) ) {
		return trim( $m[1] );
	}
	return $str;
}

/**
 * 오늘 요일에 맞는 진료시간 라벨 반환. 예: "목요일 09:00 – 18:00", "일요일 휴진".
 * 한국 timezone(KST) 기준 — wp_date() 사용.
 */
function moondental_get_today_hours_label() {
	$info = moondental_get_info();
	$dow  = (int) wp_date( 'w' ); // 0=일 ~ 6=토
	$kor  = array( '일요일', '월요일', '화요일', '수요일', '목요일', '금요일', '토요일' );
	$today = $kor[ $dow ];

	if ( $dow === 0 ) {
		return $today . ' 휴진';
	}

	$source = '';
	if ( $dow === 4 ) {
		$source = $info['hours_thu'] ?? '';
	} elseif ( $dow === 6 ) {
		$source = $info['hours_sat'] ?? '';
	} else {
		$source = $info['hours_wd'] ?? '';
	}

	$time = moondental_extract_time_range( $source );
	if ( ! $time ) return $today;
	return $today . ' ' . $time;
}

/**
 * 병원 주소 → 네이버 지도 링크 헬퍼.
 *  비어있으면 빈 문자열 반환. 입력 주소가 없으면 moondental_get_info('address') 사용.
 *
 * @param string $text   화면에 표시할 텍스트 (비우면 주소 그대로 표시)
 * @param array  $attrs  추가 anchor 속성: class, data-track 등
 */
function md_address_link( $text = '', $attrs = array() ) {
	$info = moondental_get_info();
	$addr = $info['address'] ?? '';
	if ( ! $addr ) return '';

	$display = $text !== '' ? $text : $addr;
	$href = $info['naver_map_url'] ?: ( 'https://map.naver.com/p/search/' . rawurlencode( $info['name_full'] ?? '문치과병원' ) );

	$class = isset( $attrs['class'] ) ? $attrs['class'] : 'md-addr-link';
	$track = isset( $attrs['track'] ) ? $attrs['track'] : 'cta-address';

	return sprintf(
		'<a href="%s" target="_blank" rel="noopener" class="%s" data-track="%s" aria-label="네이버 지도에서 위치 보기">%s</a>',
		esc_url( $href ),
		esc_attr( $class ),
		esc_attr( $track ),
		esc_html( $display )
	);
}

/**
 * 전화번호 → tel: 링크 헬퍼.
 *  비어있으면 빈 문자열 반환.
 *
 * @param string $text   화면에 표시할 텍스트 (비우면 전화번호 그대로 표시)
 * @param array  $attrs  추가 anchor 속성: class, data-track 등
 */
function md_phone_link( $text = '', $attrs = array() ) {
	$info = moondental_get_info();
	$phone = $info['phone'] ?? '';
	if ( ! $phone ) return '';

	$link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $phone );
	$display = $text !== '' ? $text : $phone;

	$class = isset( $attrs['class'] ) ? $attrs['class'] : 'md-tel-link';
	$track = isset( $attrs['track'] ) ? $attrs['track'] : 'cta-tel';

	return sprintf(
		'<a href="tel:%s" class="%s" data-track="%s">%s</a>',
		esc_attr( $link ),
		esc_attr( $class ),
		esc_attr( $track ),
		esc_html( $display )
	);
}

/**
 * 사이트 전역 텍스트에서 알려진 주소 문자열을 네이버 지도 anchor로 자동 wrap.
 *  이미 anchor 안에 있는 텍스트는 건드리지 않음.
 *  지원 주소:
 *    - 신부 제5공영주차장 / (동남구 먹거리1길 10) — 5공영주차장
 *    - 충청남도 천안시 동남구 만남로 52 ... — 병원 본원
 *
 * @param string $html HTML or plain text
 * @return string
 */
function md_autolink_addresses( $html ) {
	if ( ! is_string( $html ) || $html === '' ) return $html;

	$info = moondental_get_info();
	$hospital_url = $info['naver_map_url']
		?: ( 'https://map.naver.com/p/search/' . rawurlencode( $info['name_full'] ?? '문치과병원' ) );
	$park5_url = 'https://map.naver.com/p/search/' . rawurlencode( '신부 제5공영주차장' );

	// 구체적 → 일반 순서: 긴 패턴이 먼저 매칭되도록
	$patterns = array(
		// 5공영주차장
		'신부 제5공영주차장(동남구 먹거리1길 10)'              => $park5_url,
		'신부 제5공영주차장 (동남구 먹거리1길 10)'             => $park5_url,
		'"신부 제5공영주차장"(동남구 먹거리1길 10)'            => $park5_url,
		'"신부 제5공영주차장" (동남구 먹거리1길 10)'           => $park5_url,
		'신부 제5공영주차장'                                  => $park5_url,
		'"신부 제5공영주차장"'                                => $park5_url,
		'동남구 먹거리1길 10'                                 => $park5_url,
		// 본원 (병원 네이버 지도로 연결)
		'본원 지하 기계식 주차장'                              => $hospital_url,
		'본원 지하 기계식'                                    => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동, 문타워빌딩)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층 (신부동)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9·10·11·13층'      => $hospital_url,
		'충남 천안시 동남구 만남로 52, 문타워 9·10·11·13층'          => $hospital_url,
		// 구 표기 호환 — 사용자가 직접 입력한 옛 주소도 클릭 가능하게
		'충청남도 천안시 동남구 만남로 52, 문타워 9~13층 (신부동, 문타워빌딩)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9~13층 (신부동)' => $hospital_url,
		'충청남도 천안시 동남구 만남로 52, 문타워 9~13층'      => $hospital_url,
		'충남 천안시 동남구 만남로 52, 문타워 9~13층'          => $hospital_url,
	);

	// 1) 기존 anchor 보호: <a ...>...</a> 를 토큰으로 임시 치환
	$tokens = array();
	$html = preg_replace_callback( '#<a\b[^>]*>.*?</a>#siu', function( $m ) use ( &$tokens ) {
		$tokens[] = $m[0];
		return '___MDA_' . ( count( $tokens ) - 1 ) . '___';
	}, $html );

	// 2) 주소 패턴 매칭 — 새로 만든 anchor도 토큰으로 보관해
	//    후속 패턴이 이미 wrap된 텍스트를 재차 wrap하는 nested anchor 버그 방지.
	foreach ( $patterns as $needle => $url ) {
		if ( strpos( $html, $needle ) === false ) continue;
		$anchor = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="md-addr-link" data-track="cta-autolink-addr">' . esc_html( $needle ) . '</a>';
		$tokens[] = $anchor;
		$token = '___MDA_' . ( count( $tokens ) - 1 ) . '___';
		$html = str_replace( $needle, $token, $html );
	}

	// 3) 토큰 → 원본/anchor 복원
	foreach ( $tokens as $i => $orig ) {
		$html = str_replace( '___MDA_' . $i . '___', $orig, $html );
	}

	return $html;
}

// 사이트 전역 the_content 필터로 자동 주소 링크 (priority 90 — wpautop 후 / lazy-image 전)
add_filter( 'the_content', 'md_autolink_addresses', 90 );


/* ============================================================
 * v3.37.9 · 하단 CTA 배너 · 페이지별 맞춤 문구 시스템
 *
 *  section-cta.php는 어느 페이지에서 호출되든 이 helper로 컨텍스트를 파악해
 *  적절한 title/lead/eyebrow를 골라 보여줌.
 *  기존 페이지별 사용자 정의 필드(doctors_cta_*, price_cta_*, ...)를 재사용해
 *  Customizer에서 그대로 편집 가능.
 * ========================================================== */

/**
 * 현재 페이지의 하단 CTA 배너 컨텍스트 감지.
 * @return string
 */
function moondental_cta_context() {
	// 지역 페이지는 rewrite intercept 로드 · is_page_template 안 먹음
	if ( get_query_var( 'region_slug' ) )                                    return 'region';

	if ( is_page_template( 'page-templates/page-doctors.php' ) )            return 'doctors';
	if ( is_page_template( 'page-templates/page-doctor-single.php' ) )      return 'doctor_single';
	if ( is_page_template( 'page-templates/page-pricing.php' ) )            return 'pricing';
	if ( is_page_template( 'page-templates/page-preservation.php' ) )       return 'preservation';
	if ( is_page_template( 'page-templates/page-prevention.php' ) )         return 'prevention';
	if ( is_page_template( 'page-templates/page-smile-design.php' ) )       return 'smile';
	if ( is_page_template( 'page-templates/page-recruit.php' ) )            return 'recruit';
	if ( is_page_template( 'page-templates/page-location.php' ) )           return 'location';
	if ( is_page_template( 'page-templates/page-facility.php' ) )           return 'facility';
	if ( is_page_template( 'page-templates/page-faq.php' ) )                return 'faq';
	if ( is_page_template( 'page-templates/page-news.php' ) )               return 'news';
	if ( is_page_template( 'page-templates/page-history.php' ) )            return 'history';
	if ( is_page_template( 'page-templates/page-service.php' ) )            return 'service';
	if ( is_page_template( 'page-templates/page-strength.php' ) )           return 'facility'; // 강점·기술력 → facility 톤 공유

	if ( is_singular( 'md_term' ) )                                          return 'encyclopedia';
	if ( is_post_type_archive( 'md_term' ) || is_tax( 'md_term_cat' ) )      return 'encyclopedia';
	if ( is_single() )                                                       return 'news';

	/* v3.44.7 · 슬러그 기반 컨텍스트 · 템플릿 없는 페이지 (병원소개·진료항목·홈 등) */
	if ( is_front_page() ) return 'home';
	if ( is_page() ) {
		$slug = get_post_field( 'post_name', get_the_ID() );
		$slug_map = array(
			'병원소개'       => 'about',
			'병원안내'       => 'about',
			'진료항목'       => 'services_parent',
			'소식'           => 'news',
			'공지사항'       => 'news',
			'뉴스'           => 'news',
			'상담예약'       => 'reservation',
			'예약'           => 'reservation',
			'예방클리닉'     => 'prevention',
			'스마일디자인센터' => 'smile',
			'심미치료'       => 'smile',
			'임상-케이스'    => 'about',
			'임상케이스'     => 'about',
			'역사'           => 'history',
			'기술력-시설'    => 'facility',
			'기술력'         => 'facility',
			'시설'           => 'facility',
			'faq'            => 'faq',
			'자주묻는질문'   => 'faq',
			'개인정보처리방침' => 'legal',
			'이용약관'       => 'legal',
			'사이트맵'       => 'legal',
		);
		if ( isset( $slug_map[ $slug ] ) ) return $slug_map[ $slug ];
	}

	return 'default';
}

/**
 * 컨텍스트별 CTA 배너 카피 반환 (eyebrow · title · lead).
 * 기존 페이지별 Customizer 필드가 있으면 그것을 우선 사용, 없으면 tailored default.
 *
 * @param string|null $context null이면 자동 감지
 * @return array{eyebrow:string,title:string,lead:string}
 */
function moondental_cta_copy( $context = null ) {
	if ( ! $context ) $context = moondental_cta_context();

	$shared_eyebrow = md_content( 'cta_eyebrow', '상담 예약' );
	$shared_title   = md_content( 'cta_title',   '30여년 임상 · 정직한 견적을 지금 확인하세요' );
	$shared_lead    = md_content( 'cta_lead',    "환자분의 상황을 먼저 듣고, 꼭 필요한 치료만 권합니다.\n지금 상담을 신청하시면 진료시간 내 빠르게 연락드릴게요." );

	switch ( $context ) {
		case 'doctors':
			$copy = array(
				'eyebrow' => '원장님 상담',
				'title'   => md_content( 'doctors_cta_title', '어떤 원장님께 진료받고 싶으신가요?' ),
				'lead'    => md_content( 'doctors_cta_lead',  '부담 없이 상담받으세요. 환자분께 맞는 의료진을 안내드립니다.' ),
			);
			break;

		case 'doctor_single':
			$doctor_name = get_the_title();
			$title_tpl   = md_content( 'doc_single_cta_title', '원장님께 진료받고 싶으시면' );
			$copy = array(
				'eyebrow' => '진료 예약',
				'title'   => trim( $doctor_name . ' ' . $title_tpl ),
				'lead'    => md_content( 'doc_single_cta_lead', '원하시는 일정에 맞춰 진료 예약을 도와드립니다.' ),
			);
			break;

		case 'pricing':
			$copy = array(
				'eyebrow' => '무료 비용 상담',
				'title'   => md_content( 'price_cta_title', '내 진료 비용이 궁금하신가요?' ),
				'lead'    => md_content( 'price_cta_lead',  '정확한 진단 후 맞춤 견적서를 안내드립니다. 부담 없이 먼저 들어보세요.' ),
			);
			break;

		case 'preservation':
			$copy = array(
				'eyebrow' => '자연치아 살리기',
				'title'   => md_content( 'preservation_cta_title', "발치 권유받으셨나요?\n한 번 더 살펴보세요" ),
				'lead'    => md_content( 'preservation_cta_lead',  '보존과·치주과 전문 의료진의 정밀 진단으로 자연치아를 살릴 수 있는지 검토해드립니다.' ),
			);
			break;

		case 'prevention':
			$copy = array(
				'eyebrow' => '예방 클리닉',
				'title'   => md_content( 'prevention_cta_title', "치료 전에 예방을 시작하세요\n덴탈 SPA로 평생 관리" ),
				'lead'    => md_content( 'prevention_cta_lead',  '6개월 주기 정기 SPA로 자연치아를 평생 건강하게 — 가장 경제적인 투자입니다.' ),
			);
			break;

		case 'smile':
			$copy = array(
				'eyebrow' => '스마일 디자인',
				'title'   => md_content( 'smile_cta_title', "지금 내 미소,\n디자인해보세요" ),
				'lead'    => md_content( 'smile_cta_lead',  '디지털 스마일 시뮬레이션으로 결과를 미리 확인하고 시작할 수 있습니다.' ),
			);
			break;

		case 'region':
			$region_name = '';
			if ( function_exists( 'moondental_get_region_by_slug' ) ) {
				$region = moondental_get_region_by_slug( get_query_var( 'region_slug' ) );
				if ( $region && ! empty( $region['name'] ) ) $region_name = $region['name'];
			}
			$title_raw = md_content( 'region_cta_title', "{region}에서 문치과병원까지\n지금 바로 상담 받아보세요" );
			$copy = array(
				'eyebrow' => '우리 지역 환자',
				'title'   => str_replace( '{region}', $region_name, $title_raw ),
				'lead'    => md_content( 'region_cta_lead', '네이버 예약 24시간 자동 · 전화·카카오톡으로 편하게 문의하세요.' ),
			);
			break;

		case 'recruit':
			$copy = array(
				'eyebrow' => '채용 문의',
				'title'   => md_content( 'recruit_page_cta_title', "지금 이메일로\n편하게 보내주세요" ),
				'lead'    => md_content( 'recruit_page_cta_lead',  '길지 않아도, 완벽하지 않아도 괜찮습니다. 함께 오래 갈 분을 기다리고 있습니다.' ),
			);
			break;

		case 'location':
			$copy = array(
				'eyebrow' => '방문 예약',
				'title'   => md_content( 'cta_location_title', "오시는 길 확인하셨다면\n지금 예약해주세요" ),
				'lead'    => md_content( 'cta_location_lead',  '문타워 9·10·11·13층 · 자체 주차장 · 편하신 방법으로 연락주세요.' ),
			);
			break;

		case 'facility':
			$copy = array(
				'eyebrow' => '병원 방문 상담',
				'title'   => md_content( 'cta_facility_title', '시설을 직접 보고 결정하세요' ),
				'lead'    => md_content( 'cta_facility_lead',  '편하신 시간에 방문 상담이 가능합니다. 미리 예약해주시면 대기 없이 안내드립니다.' ),
			);
			break;

		case 'faq':
			$copy = array(
				'eyebrow' => '궁금증 해결',
				'title'   => md_content( 'cta_faq_title', '여전히 궁금한 점이 있으신가요?' ),
				'lead'    => md_content( 'cta_faq_lead',  'FAQ에서 답을 찾지 못하셨다면 언제든 편하게 상담해주세요.' ),
			);
			break;

		case 'news':
			$copy = array(
				'eyebrow' => '병원 소식',
				'title'   => md_content( 'cta_news_title', '궁금한 진료가 있으신가요?' ),
				'lead'    => md_content( 'cta_news_lead',  '관련 상담을 원하시면 부담 없이 연락주세요. 진료시간 내 빠르게 답변드립니다.' ),
			);
			break;

		case 'encyclopedia':
			$copy = array(
				'eyebrow' => '정확한 진단은 내원',
				'title'   => md_content( 'cta_enc_title', '이 증상, 나에게 해당할까요?' ),
				'lead'    => md_content( 'cta_enc_lead',  '치과 백과사전은 참고용입니다. 정확한 진단·치료 계획은 의료진 상담이 필요합니다.' ),
			);
			break;

		case 'history':
			$copy = array(
				'eyebrow' => '30여년 문치과',
				'title'   => md_content( 'cta_history_title', '30여년 임상, 지금 만나보세요' ),
				'lead'    => md_content( 'cta_history_lead',  '오랜 시간 축적된 진료 노하우로 정직하게 상담드립니다.' ),
			);
			break;

		case 'service':
			$service_name = get_the_title();
			$copy = array(
				'eyebrow' => '상담 · 예약',
				'title'   => md_content( 'cta_service_title', ( $service_name ? $service_name . ', ' : '' ) . '나에게 맞는지 상담받아보세요' ),
				'lead'    => md_content( 'cta_service_lead',  '진단부터 치료 계획까지 부담 없이 안내드립니다. 시작 전에 궁금한 점을 다 여쭤보세요.' ),
			);
			break;

		/* v3.44.7 · 슬러그 기반 신규 컨텍스트 */
		case 'home':
			$copy = array(
				'eyebrow' => md_content( 'cta_home_eyebrow', '첫 방문 환영' ),
				'title'   => md_content( 'cta_home_title',   "천안·아산 30여년 임상,\n오늘 첫 상담 예약하세요" ),
				'lead'    => md_content( 'cta_home_lead',    "환자분께 꼭 필요한 치료만 정직하게 권합니다.\n지금 예약하시면 편하신 시간에 상세히 상담해드립니다." ),
			);
			break;

		case 'about':
			$copy = array(
				'eyebrow' => md_content( 'cta_about_eyebrow', '병원 방문 상담' ),
				'title'   => md_content( 'cta_about_title',   "실제 병원 분위기가 궁금하시면\n방문 상담 예약해주세요" ),
				'lead'    => md_content( 'cta_about_lead',    '30여년 한자리 진료의 문치과병원 · 원장님·시설·의료진을 직접 확인하실 수 있습니다.' ),
			);
			break;

		case 'services_parent':
			$copy = array(
				'eyebrow' => md_content( 'cta_services_parent_eyebrow', '진료 상담' ),
				'title'   => md_content( 'cta_services_parent_title',   '어떤 진료가 필요한지 모르시겠나요?' ),
				'lead'    => md_content( 'cta_services_parent_lead',    '증상을 말씀해주시면 적합한 진료과와 원장님을 안내드립니다. 정확한 진단 후 필요한 치료만 권해드립니다.' ),
			);
			break;

		case 'reservation':
			$copy = array(
				'eyebrow' => md_content( 'cta_reservation_eyebrow', '다른 방법으로도' ),
				'title'   => md_content( 'cta_reservation_title',   '위 양식이 불편하시면 편하신 채널로 연락주세요' ),
				'lead'    => md_content( 'cta_reservation_lead',    '전화·네이버 예약·카카오톡 — 원하시는 방법으로 편하게 문의하실 수 있습니다.' ),
			);
			break;

		case 'legal':
			$copy = array(
				'eyebrow' => md_content( 'cta_legal_eyebrow', '문의' ),
				'title'   => md_content( 'cta_legal_title',   '개인정보 관련 문의사항이 있으신가요?' ),
				'lead'    => md_content( 'cta_legal_lead',    '병원 대표 연락처로 언제든 문의해주시면 담당자가 성실히 답변드리겠습니다.' ),
			);
			break;

		default:
			/* v3.44.8 · 미분류 페이지 · 사용자 옛 cta_title 커스터마이저 override
			 * ('30여년 임상 · 정직한 견적') 로부터 격리 · 새 키 cta_generic_* 사용 */
			$copy = array(
				'eyebrow' => md_content( 'cta_generic_eyebrow', '상담 안내' ),
				'title'   => md_content( 'cta_generic_title',   '궁금한 점이 있으신가요?' ),
				'lead'    => md_content( 'cta_generic_lead',    "환자분의 상황을 먼저 듣고, 꼭 필요한 치료만 권합니다.\n지금 상담을 신청하시면 진료시간 내 빠르게 연락드립니다." ),
			);
	}

	/**
	 * 필터로 최종 카피 커스터마이즈 가능.
	 * @param array  $copy    ['eyebrow','title','lead']
	 * @param string $context 컨텍스트 키
	 */
	return apply_filters( 'moondental_cta_copy', $copy, $context );
}

/**
 * Astra 부모 테마의 기본 scroll-to-top 버튼 끄기 — 테마 자체 .md-totop만 사용.
 *  Astra 4+ 옵션 키 'scroll-to-top-enable' 를 false로 강제.
 *  옵션 키가 다른 버전에서도 CSS .ast-scroll-top* 숨김으로 이중 안전.
 */
add_filter( 'astra_get_option_scroll-to-top-enable', '__return_false', 99 );
add_filter( 'astra_get_option_scroll-to-top-device', function() { return ''; }, 99 );

/**
 * 전역 예약 CTA 버튼 — 네이버 예약 + 카카오톡 상담 + 전화.
 *  URL/라벨은 사용자 정의하기 → 사이트 공통 콘텐츠 → 전역 예약 CTA 버튼 에서 편집.
 *  네이버/카카오 URL이 비어있으면 SNS 섹션의 값을 fallback으로 사용.
 *  라벨이 비어있으면 해당 버튼 자동 숨김.
 *
 * @param array $args {
 *   @type string $size   'lg' 또는 ''                — 버튼 크기 (기본 lg)
 *   @type string $align  'center' 또는 ''           — 가운데 정렬 여부 (기본 center)
 *   @type string $track  data-track prefix          — 분석용
 *   @type bool   $show_naver/$show_kakao/$show_call — 개별 버튼 강제 숨김
 * }
 * @return string HTML
 */
function md_render_reservation_ctas( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'size'       => 'lg',
		'align'      => 'center',
		'track'      => 'cta',
		'show_naver' => true,
		'show_kakao' => true,
		'show_call'  => true,
	) );

	$info = moondental_get_info();

	// URL fallback chain: cta_btn_*_url → 병원정보의 SNS URL
	$naver_url = function_exists( 'md_content' ) ? md_content( 'cta_btn_naver_url', '' ) : '';
	if ( empty( $naver_url ) ) $naver_url = $info['naver_place'] ?? '';

	$kakao_url = function_exists( 'md_content' ) ? md_content( 'cta_btn_kakao_url', '' ) : '';
	if ( empty( $kakao_url ) ) $kakao_url = $info['kakao_url'] ?? '';

	$naver_label = function_exists( 'md_content' ) ? md_content( 'cta_btn_naver_label', '📅 네이버 예약' ) : '📅 네이버 예약';
	$kakao_label = function_exists( 'md_content' ) ? md_content( 'cta_btn_kakao_label', '💬 카카오톡 상담' ) : '💬 카카오톡 상담';
	$call_label  = function_exists( 'md_content' ) ? md_content( 'cta_btn_call_label',  '📞 전화 상담' )       : '📞 전화 상담';
	// v3.37.3 · 전화번호 표시 기본 off · 라벨 짧게 유지해 3버튼 가로 배치 균형
	$show_phone  = function_exists( 'md_content' ) ? md_content( 'cta_btn_show_phone',  'no' )               : 'no';

	$phone       = $info['phone'] ?? '';
	$phone_link  = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $phone );

	$btn_size_cls = $args['size'] === 'lg' ? ' md-btn--lg' : '';

	$group_style = 'display:flex; flex-wrap:wrap; gap:12px;';
	if ( $args['align'] === 'center' ) {
		$group_style .= ' justify-content:center;';
	}

	// 라벨에서 앞쪽 이모지(📅·💬·📞) 자동 제거 — 브랜드 SVG 로고로 대체
	$strip_emoji = function( $s ) {
		return trim( preg_replace( '/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{1F000}-\x{1F2FF}]+\s*/u', '', (string) $s ) );
	};

	// SVG 로고 (Naver, Kakao, Phone)
	$svg_naver = '<svg class="md-rcta__icon" viewBox="0 0 24 24" aria-hidden="true">'
		. '<circle cx="12" cy="12" r="12" fill="#ffffff"/>'
		. '<path d="M9 8h2.2l3.6 5.1V8H17v8h-2.2l-3.6-5.1V16H9V8z" fill="#03C75A"/>'
		. '</svg>';
	$svg_kakao = '<svg class="md-rcta__icon" viewBox="0 0 24 24" aria-hidden="true">'
		. '<path d="M12 6c-3.7 0-6.7 2.4-6.7 5.4 0 1.9 1.3 3.6 3.2 4.5l-.7 2.6c-.06.23.18.4.38.27l3.05-2c.25.03.51.04.78.04 3.7 0 6.7-2.4 6.7-5.4S15.7 6 12 6z" fill="#3C1E1E"/>'
		. '</svg>';
	// v3.37.4 · 전화 아이콘 · 원 없이 빨간 수화기만 · 코럴 버튼에 직접 표시
	$svg_phone = '<svg class="md-rcta__icon md-rcta__icon--phone-glyph" viewBox="0 0 24 24" aria-hidden="true">'
		. '<path d="M20 15.5c-1.25 0-2.45-.2-3.57-.57a1.02 1.02 0 0 0-1.02.24l-2.2 2.2a15.05 15.05 0 0 1-6.59-6.59l2.2-2.2c.28-.27.36-.66.24-1.02A11.36 11.36 0 0 1 8.5 4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.5c0-.55-.45-1-1-1z" fill="#e63946"/>'
		. '</svg>';

	$out  = '<div class="md-btn-group md-rcta">';

	// v3.36.1 · 순서 변경 · FAB 스택과 동일 (전화 → 네이버 → 카톡)
	// 전화 상담 (코럴 브랜드)
	if ( $args['show_call'] && $call_label && $phone_link ) {
		$clean_label = $strip_emoji( $call_label );
		$label_html  = esc_html( $clean_label );
		if ( $show_phone !== 'no' && $phone ) {
			$label_html .= ' ' . esc_html( $phone );
		}
		$out .= '<a class="md-btn md-btn--phone' . esc_attr( $btn_size_cls ) . ' md-rcta__call md-rcta__btn" '
		     . 'href="tel:' . esc_attr( $phone_link ) . '" '
		     . 'data-track="' . esc_attr( $args['track'] ) . '-call">'
		     . $svg_phone
		     . '<span class="md-rcta__label">' . $label_html . '</span></a>';
	}

	// 네이버 예약 (Naver green)
	if ( $args['show_naver'] && $naver_label && $naver_url ) {
		$clean_label = $strip_emoji( $naver_label );
		$out .= '<a class="md-btn md-btn--naver' . esc_attr( $btn_size_cls ) . ' md-rcta__naver md-rcta__btn" '
		     . 'href="' . esc_url( $naver_url ) . '" target="_blank" rel="noopener" '
		     . 'data-track="' . esc_attr( $args['track'] ) . '-naver">'
		     . $svg_naver
		     . '<span class="md-rcta__label">' . esc_html( $clean_label ) . '</span></a>';
	}

	// 카카오톡 상담 (Kakao yellow)
	if ( $args['show_kakao'] && $kakao_label && $kakao_url ) {
		$clean_label = $strip_emoji( $kakao_label );
		$out .= '<a class="md-btn md-btn--kakao' . esc_attr( $btn_size_cls ) . ' md-rcta__kakao md-rcta__btn" '
		     . 'href="' . esc_url( $kakao_url ) . '" target="_blank" rel="noopener" '
		     . 'data-track="' . esc_attr( $args['track'] ) . '-kakao">'
		     . $svg_kakao
		     . '<span class="md-rcta__label">' . esc_html( $clean_label ) . '</span></a>';
	}

	$out .= '</div>';
	return $out;
}


/* ============================================================
 * 4. WordPress Customizer (관리자에서 병원 정보 편집)
 * ========================================================== */

/**
 * 외모 > 사용자 정의하기 > "문치과병원 정보" 섹션에서
 * 비개발자도 병원 정보를 편집할 수 있도록 등록.
 */
function moondental_customize_register( $wp_customize ) {

	$wp_customize->add_panel( 'moondental_panel', array(
		'title'    => '문치과병원 설정',
		'priority' => 20,
	) );

	$wp_customize->add_section( 'moondental_section_info', array(
		'title'       => '병원 기본 정보',
		'panel'       => 'moondental_panel',
		'description' => '푸터·헤더·구조화데이터에 자동 반영됩니다.',
	) );

	$wp_customize->add_section( 'moondental_section_hours', array(
		'title' => '진료시간',
		'panel' => 'moondental_panel',
	) );

	$wp_customize->add_section( 'moondental_section_sns', array(
		'title' => 'SNS / 외부 링크',
		'panel' => 'moondental_panel',
	) );

	$wp_customize->add_section( 'moondental_section_home_hero', array(
		'title'       => '홈 — Hero 섹션',
		'panel'       => 'moondental_panel',
		'description' => '메인 화면 첫 영역의 문구·이미지.',
	) );

	$wp_customize->add_section( 'moondental_section_home_doctor', array(
		'title'       => '홈 — 의료진 섹션',
		'panel'       => 'moondental_panel',
		'description' => '대표원장 소개 영역의 약력·사진. 약력은 줄바꿈으로 구분합니다.',
	) );

	$fields = array(
		// section_info
		array( 'key' => 'name_full',    'label' => '병원명 (정식)',            'section' => 'moondental_section_info' ),
		array( 'key' => 'name_short',   'label' => '병원명 (짧은 표기)',        'section' => 'moondental_section_info' ),
		array( 'key' => 'name_en',      'label' => '병원명 (영문)',            'section' => 'moondental_section_info' ),
		array( 'key' => 'tagline',      'label' => '한 줄 슬로건',             'section' => 'moondental_section_info' ),
		array( 'key' => 'rep',          'label' => '대표원장',                'section' => 'moondental_section_info' ),
		array( 'key' => 'phone',        'label' => '대표 전화 (표시용)',        'section' => 'moondental_section_info' ),
		array( 'key' => 'phone_link',   'label' => '전화 링크 (tel: 용, 숫자만)','section' => 'moondental_section_info' ),
		array( 'key' => 'email',        'label' => '대표 이메일',              'section' => 'moondental_section_info' ),
		array( 'key' => 'address',      'label' => '주소 (지번 또는 표기용)',    'section' => 'moondental_section_info', 'type' => 'textarea' ),
		array( 'key' => 'address_road', 'label' => '도로명 주소',              'section' => 'moondental_section_info' ),
		array( 'key' => 'biz_no',       'label' => '사업자등록번호',           'section' => 'moondental_section_info' ),
		array( 'key' => 'med_inst_no',  'label' => '의료기관 고유번호',         'section' => 'moondental_section_info' ),

		// section_hours
		array( 'key' => 'hours_wd',     'label' => '평일 진료시간',            'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_thu',    'label' => '목요일 진료시간',          'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_sat',    'label' => '토요일 진료시간',          'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_lunch',  'label' => '점심시간 (선택)',          'section' => 'moondental_section_hours' ),
		array( 'key' => 'hours_off',    'label' => '휴진 안내',                'section' => 'moondental_section_hours' ),

		// section_sns
		array( 'key' => 'kakao_url',    'label' => '카카오톡 채널 URL',         'section' => 'moondental_section_sns' ),
		array( 'key' => 'naver_place',  'label' => '네이버 예약 URL',          'section' => 'moondental_section_sns' ),
		array( 'key' => 'naver_review_url', 'label' => '네이버 리뷰 URL (플레이스 리뷰 페이지 · 비우면 예약 URL 사용)', 'section' => 'moondental_section_sns' ),
		array( 'key' => 'naver_map_url','label' => '네이버 지도·플레이스 URL', 'section' => 'moondental_section_sns' ),
		array( 'key' => 'google_map_url','label' => 'Google Maps URL (단축 링크 가능)', 'section' => 'moondental_section_sns' ),
		array( 'key' => 'instagram',    'label' => '인스타그램 URL',          'section' => 'moondental_section_sns' ),
		array( 'key' => 'blog_url',     'label' => '네이버 블로그 URL',        'section' => 'moondental_section_sns' ),
		array( 'key' => 'facebook_url', 'label' => '페이스북 URL',            'section' => 'moondental_section_sns' ),
		array( 'key' => 'youtube_url',  'label' => '유튜브 채널 URL',          'section' => 'moondental_section_sns' ),
		array( 'key' => 'map_embed',    'label' => '지도 임베드 코드 (HTML iframe)', 'section' => 'moondental_section_sns', 'type' => 'textarea' ),
	);

	$defaults = moondental_get_info();
	foreach ( $fields as $field ) {
		$setting_id = 'moondental_' . $field['key'];
		$wp_customize->add_setting( $setting_id, array(
			'default'           => $defaults[ $field['key'] ] ?? '',
			'sanitize_callback' => ( ( $field['type'] ?? '' ) === 'textarea' ) ? 'sanitize_textarea_field' : 'sanitize_text_field',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id, array(
			'label'   => $field['label'],
			'section' => $field['section'],
			'type'    => $field['type'] ?? 'text',
		) );
	}

	/* ── Home Hero section content ─────────────────────────────── */
	$hero_fields = array(
		'hero_eyebrow' => array( 'label' => '상단 작은 태그',     'type' => 'text',     'default' => '천안 만남로 · 1995년부터 한자리에서' ),
		'hero_title_a' => array( 'label' => '메인 카피 1행',       'type' => 'text',     'default' => '천안·아산에서 30여년,' ),
		'hero_title_b' => array( 'label' => '메인 카피 2행 (강조)', 'type' => 'text',     'default' => '환자 한 분의 평생 치아를' ),
		'hero_lead'    => array( 'label' => '서브 카피',            'type' => 'textarea', 'default' => "천안·아산 임플란트·투명교정·라미네이트·자연치아 살리기까지.\n분야별 전문 의료진이 한 자리에서 — 충분히 듣고, 꼭 필요한 치료만 권합니다." ),
	);
	foreach ( $hero_fields as $key => $f ) {
		$id = 'moondental_' . $key;
		$wp_customize->add_setting( $id, array(
			'default'           => $f['default'],
			'sanitize_callback' => $f['type'] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $id, array(
			'label'   => $f['label'],
			'section' => 'moondental_section_home_hero',
			'type'    => $f['type'],
		) );
	}
	// v3.34.0 · 히어로 CTA 버튼 커스터마이징 (라벨 · 링크)
	$wp_customize->add_setting( 'moondental_hero_cta_primary_label', array(
		'default'           => '📅 상담 예약하기',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'moondental_hero_cta_primary_label', array(
		'label'   => '히어로 · 메인 CTA 버튼 라벨',
		'section' => 'moondental_section_home_hero',
		'type'    => 'text',
	) );
	$wp_customize->add_setting( 'moondental_hero_cta_primary_url', array(
		'default'           => '/상담예약/',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'moondental_hero_cta_primary_url', array(
		'label'       => '히어로 · 메인 CTA 링크',
		'description' => '사이트 내부 경로(/상담예약/) 또는 전체 URL',
		'section'     => 'moondental_section_home_hero',
		'type'        => 'text',
	) );

	// v3.34.0 · Hero 이미지 필드는 유지하되 사용 안 함 (하위 호환)
	// 히어로가 이미지 영역 없이 중앙 정렬로 재구성되었기 때문에 이미지 업로드는
	// 향후 배경 이미지로 활용 가능. 필드는 삭제하지 않고 남겨둠.

	/* ── 모든 페이지 — 오시는 길 섹션 (푸터 위) — 지도 이미지 / iframe 임베드 ───── */
	$wp_customize->add_section( 'moondental_section_flocation_map', array(
		'title'       => '오시는 길 — 지도 이미지/임베드',
		'panel'       => 'moondental_panel',
		'description' => '모든 페이지 푸터 위에 표시되는 지도. 우선순위: ①임베드 HTML > ②업로드 이미지 > ③/assets/images/map/naver-map.*. 네이버 지도는 보안상 iframe 임베드 불가 — 옵션 안내는 각 필드 설명 참고.',
	) );

	$wp_customize->add_setting( 'moondental_flocation_map_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'moondental_flocation_map_image', array(
		'label'       => '① 지도 이미지 업로드 (가장 쉬움)',
		'description' => '네이버 지도에서 화면 캡처한 후 여기에 업로드하세요. 가로/세로 비율 21:9 권장. 비워두면 /assets/images/map/naver-map.png(jpg/webp) 파일로 자동 fallback.',
		'section'     => 'moondental_section_flocation_map',
		'mime_type'   => 'image',
	) ) );

	$wp_customize->add_setting( 'moondental_flocation_map_embed', array(
		'default'           => '',
		'sanitize_callback' => 'wp_kses_post',
	) );
	$wp_customize->add_control( 'moondental_flocation_map_embed', array(
		'label'       => '② 지도 임베드 HTML (실시간 — 선택)',
		'description' => "비우면 위 이미지 사용. 옵션:\n• 구글 지도 — maps.google.com 에서 검색 → '공유' → '지도 임베드' → iframe 코드 복사·붙여넣기 (키 불필요)\n• 카카오 지도 — map.kakao.com 에서 검색 → '공유' → '지도 임베드'\n• 네이버 클라우드 플랫폼 Maps API — Client ID 발급 후 JS 코드 사용 (무료 60,000건/일)\n네이버 지도 자체는 보안상 iframe 임베드를 차단합니다.",
		'section'     => 'moondental_section_flocation_map',
		'type'        => 'textarea',
	) );

	/* ── Home Doctor section content ───────────────────────────── */
	$doctor_fields = array(
		'doctor_role' => array( 'label' => '직책',                 'type' => 'text',     'default' => '대표 병원장 · 한아의료재단 이사장' ),
		'doctor_lead' => array( 'label' => '한 줄 진료 철학',         'type' => 'textarea', 'default' => '1995년부터 천안·아산에서, 한 분의 환자를 가족처럼 오래 보아왔습니다. 진료실 밖에서도 지역사회와 함께 가는 치과를 꿈꿉니다.' ),
		'doctor_bio'  => array( 'label' => '약력 (줄바꿈으로 구분)', 'type' => 'textarea', 'default' => "한아임플란트 보철연구소장\n단국대학교 치과대학 총동창회 학술이사\n대한 구강악안면 임플란트 학회 이사\n충남 치과의사회 학술이사\n단국치대 겸임교수\n이화여대 의과대학 외래교수" ),
	);
	foreach ( $doctor_fields as $key => $f ) {
		$id = 'moondental_' . $key;
		$wp_customize->add_setting( $id, array(
			'default'           => $f['default'],
			'sanitize_callback' => $f['type'] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field',
		) );
		$wp_customize->add_control( $id, array(
			'label'   => $f['label'],
			'section' => 'moondental_section_home_doctor',
			'type'    => $f['type'],
		) );
	}
	$wp_customize->add_setting( 'moondental_doctor_image', array(
		'default'           => 0,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'moondental_doctor_image', array(
		'label'     => '원장님 사진 (세로 비율 권장)',
		'section'   => 'moondental_section_home_doctor',
		'mime_type' => 'image',
	) ) );

	/* ── 의료진 사진 크기 조정 (개인별 zoom + translateY) ──────────── */
	$wp_customize->add_section( 'moondental_section_team_photos', array(
		'title'       => '의료진 — 사진 크기·위치',
		'panel'       => 'moondental_panel',
		'description' => '의료진 페이지(/의료진/)의 사진별 머리 크기와 위치를 조정합니다. ' .
		                 'Zoom: 1.00 = 원본 그대로 · 1.50 = 50% 확대 (머리 크기). ' .
		                 'TranslateY: 음수 = 위로 올림 · 양수 = 아래로 내림 (% 단위, 머리 최상단 위치).',
		'priority'    => 40,
	) );

	$team_zoom_defaults = moondental_team_zoom_defaults();
	foreach ( $team_zoom_defaults as $slug => $info ) {
		/* Zoom */
		$setting_id_z = 'moondental_team_zoom_' . $slug;
		$wp_customize->add_setting( $setting_id_z, array(
			'default'           => $info['default'],
			'sanitize_callback' => 'moondental_sanitize_zoom',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id_z, array(
			'label'       => $info['name'] . ' · 머리 크기 (Zoom)',
			'description' => '기본값 ' . number_format( $info['default'], 2 ) . ' · 범위 0.80~2.50',
			'section'     => 'moondental_section_team_photos',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0.80,
				'max'  => 2.50,
				'step' => 0.05,
			),
		) );

		/* TranslateY */
		$setting_id_t = 'moondental_team_ty_' . $slug;
		$wp_customize->add_setting( $setting_id_t, array(
			'default'           => $info['ty'],
			'sanitize_callback' => 'moondental_sanitize_translatey',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $setting_id_t, array(
			'label'       => $info['name'] . ' · 머리 위치 (TranslateY %)',
			'description' => '기본값 ' . (int) $info['ty'] . '% · 범위 -40 ~ 40 · 음수=위로 올림',
			'section'     => 'moondental_section_team_photos',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => -40,
				'max'  => 40,
				'step' => 1,
			),
		) );
	}
}
add_action( 'customize_register', 'moondental_customize_register' );

/**
 * 의료진별 사진 줌 기본값. Customizer 미설정 시 이 값이 적용됨.
 * 슬러그(sanitize_title 한 이름) → [name, role, default]
 */
function moondental_team_zoom_defaults() {
	/*
	 * 머리 최상단을 카드 상단에서 약 1cm(=카드 높이의 ~10%) 하단에 위치시키도록 조정.
	 * zoom은 원본 유지(1.00) — 사진 비율 자연스럽게.
	 * translateY는 양수=머리를 아래로, 음수=위로 이동 (단위: %).
	 *
	 * 추정한 원본 머리 최상단 위치(T_orig%):
	 *   문은수 10 / 이승주 5 / 이수연 7 / 권혜진 6 / 문지현 8 /
	 *   이창률 5 / 이영일 8 / 김세일 12 / 정석형 8
	 *
	 * translateY = 10 - T_orig
	 */
	return array(
		sanitize_title( '문은수' ) => array( 'name' => '문은수', 'role' => '대표 병원장',          'default' => 1.00, 'ty' =>  0 ),
		sanitize_title( '이승주' ) => array( 'name' => '이승주', 'role' => '원장',                  'default' => 1.00, 'ty' =>  5 ),
		sanitize_title( '이수연' ) => array( 'name' => '이수연', 'role' => '원장',                  'default' => 1.00, 'ty' =>  3 ),
		sanitize_title( '권혜진' ) => array( 'name' => '권혜진', 'role' => '원장',                  'default' => 1.00, 'ty' =>  4 ),
		sanitize_title( '문지현' ) => array( 'name' => '문지현', 'role' => '원장',                  'default' => 1.00, 'ty' =>  2 ),
		sanitize_title( '이창률' ) => array( 'name' => '이창률', 'role' => '원장',                  'default' => 1.00, 'ty' =>  5 ),
		sanitize_title( '이영일' ) => array( 'name' => '이영일', 'role' => '원장',                  'default' => 1.00, 'ty' =>  2 ),
		sanitize_title( '김세일' ) => array( 'name' => '김세일', 'role' => '원장',                  'default' => 1.00, 'ty' => -2 ),
		sanitize_title( '정석형' ) => array( 'name' => '정석형', 'role' => '원장',                  'default' => 1.00, 'ty' =>  2 ),
	);
}

/**
 * 사진 줌 값을 0.80~2.50 범위로 정제.
 */
function moondental_sanitize_zoom( $value ) {
	$v = (float) $value;
	if ( $v < 0.80 ) return 0.80;
	if ( $v > 2.50 ) return 2.50;
	return round( $v, 2 );
}

/**
 * 머리 위치(translateY) -40 ~ 40% 범위로 정제.
 */
function moondental_sanitize_translatey( $value ) {
	$v = (float) $value;
	if ( $v < -40 ) return -40;
	if ( $v >  40 ) return  40;
	return round( $v, 1 );
}

/**
 * 의료진 이름으로 현재 translateY 값(%)을 반환.
 */
function moondental_get_doctor_ty( $name, $fallback = 0 ) {
	$slug    = sanitize_title( $name );
	$default = $fallback;
	$map     = moondental_team_zoom_defaults();
	if ( isset( $map[ $slug ] ) ) {
		$default = $map[ $slug ]['ty'];
	}
	$v = get_theme_mod( 'moondental_team_ty_' . $slug, $default );
	return moondental_sanitize_translatey( $v );
}

/**
 * 의료진 이름으로 현재 줌 값을 반환 — Customizer 값 우선, 없으면 default.
 */
function moondental_get_doctor_zoom( $name, $fallback = 1.00 ) {
	$slug    = sanitize_title( $name );
	$default = $fallback;
	$map     = moondental_team_zoom_defaults();
	if ( isset( $map[ $slug ] ) ) {
		$default = $map[ $slug ]['default'];
	}
	$v = get_theme_mod( 'moondental_team_zoom_' . $slug, $default );
	return moondental_sanitize_zoom( $v );
}


/* ============================================================
 * 4b. 기본 페이지 자동 생성 도구 (관리자)
 * ========================================================== */

/**
 * 사이트 운영에 필요한 페이지 정의. 슬러그·제목·템플릿·정렬을 한 곳에서 관리.
 */
function moondental_default_pages() {
	// v3.44.84 · URL 평면화 · 모든 페이지 최상위 (parent 없음)
	return array(
		array( 'slug' => '홈',              'title' => '홈',           'template' => '',                                     'order' => 0,  'parent' => '' ),
		array( 'slug' => '의료진',           'title' => '의료진',        'template' => 'page-templates/page-doctors.php',      'order' => 1,  'parent' => '' ),
		array( 'slug' => '역사',             'title' => '역사',          'template' => 'page-templates/page-history.php',      'order' => 2,  'parent' => '' ),
		array( 'slug' => '기술력-시설',       'title' => '기술력/시설',   'template' => 'page-templates/page-facility.php',     'order' => 3,  'parent' => '' ),
		array( 'slug' => '임상-케이스',       'title' => '임상 케이스',    'template' => 'page-templates/page-wide.php',         'order' => 4,  'parent' => '' ),
		array( 'slug' => '임플란트-센터',     'title' => '임플란트 센터',  'template' => 'page-templates/page-service.php',      'order' => 5,  'parent' => '' ),
		array( 'slug' => '투명교정-센터',     'title' => '투명교정 센터',  'template' => 'page-templates/page-service.php',      'order' => 6,  'parent' => '' ),
		array( 'slug' => '자연치아-살리기',   'title' => '자연치아 살리기','template' => 'page-templates/page-service.php',      'order' => 7,  'parent' => '' ),
		array( 'slug' => '턱관절-클리닉',     'title' => '턱관절 클리닉',  'template' => 'page-templates/page-service.php',      'order' => 8,  'parent' => '' ),
		array( 'slug' => '사랑니-발치',       'title' => '사랑니 발치',   'template' => 'page-templates/page-service.php',      'order' => 9,  'parent' => '' ),
		array( 'slug' => '심미치료',         'title' => '심미치료',      'template' => 'page-templates/page-service.php',      'order' => 10, 'parent' => '' ),
		array( 'slug' => '예방클리닉',       'title' => '예방클리닉',     'template' => 'page-templates/page-prevention.php',   'order' => 11, 'parent' => '' ),
		array( 'slug' => '스마일디자인센터', 'title' => '스마일디자인센터', 'template' => 'page-templates/page-smile-design.php', 'order' => 12, 'parent' => '' ),
		array( 'slug' => '슈어스마일-투명교정', 'title' => '슈어스마일 투명교정', 'template' => 'page-templates/page-service.php', 'order' => 13, 'parent' => '' ),
		array( 'slug' => '브라켓-치아교정',   'title' => '브라켓 치아교정', 'template' => 'page-templates/page-service.php',     'order' => 14, 'parent' => '' ),
		array( 'slug' => '상시채용',         'title' => '상시채용',       'template' => 'page-templates/page-recruit.php',      'order' => 15, 'parent' => '' ),
		array( 'slug' => '소식',             'title' => '소식',         'template' => '',                                     'order' => 16, 'parent' => '' ),
		array( 'slug' => '오시는-길',         'title' => '오시는 길',     'template' => 'page-templates/page-location.php',     'order' => 17, 'parent' => '' ),
		array( 'slug' => '상담예약',         'title' => '상담 예약',     'template' => 'page-templates/page-reservation.php',  'order' => 18, 'parent' => '' ),
		array( 'slug' => '비용-안내',        'title' => '비용 안내',     'template' => 'page-templates/page-pricing.php',      'order' => 19, 'parent' => '' ),
		array( 'slug' => '개인정보처리방침', 'title' => '개인정보처리방침', 'template' => '', 'order' => 90, 'parent' => '' ),
		array( 'slug' => '이용약관',         'title' => '이용약관',       'template' => '', 'order' => 91, 'parent' => '' ),
	);
}

/**
 * 헤더 CTA가 가리키는 /상담예약/ 페이지 자동 생성 — 테마 활성화 시 1회.
 *  이미 있으면 건드리지 않음.
 */
function moondental_ensure_reservation_page() {
	// v3.44.58 · Polylang 우회 · wpdb 직접 조회
	if ( function_exists( 'moondental_page_exists_by_slug' ) && moondental_page_exists_by_slug( '상담예약' ) ) return;
	if ( get_page_by_path( '상담예약' ) ) return;
	wp_insert_post( array(
		'post_title'    => '상담 예약',
		'post_name'     => '상담예약',
		'post_status'   => 'publish',
		'post_type'     => 'page',
		'post_content'  => '',
		'page_template' => 'page-templates/page-reservation.php',
	) );
}
add_action( 'after_switch_theme', 'moondental_ensure_reservation_page' );
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_reservation_page_v1' ) === '1' ) return;
	moondental_ensure_reservation_page();
	update_option( 'moondental_reservation_page_v1', '1' );
} );

/**
 * v3.44.27 · 슬러그 → 페이지 표시 제목 강제 오버라이드
 * (WP 페이지 post_title이 옛 값 남아 있어도 표시는 새 이름으로)
 */
add_filter( 'the_title', function( $title, $post_id = null ) {
	if ( ! $post_id ) return $title;
	$slug = get_post_field( 'post_name', $post_id );
	$slug = urldecode( (string) $slug );
	$map  = array(
		'투명교정-센터' => '교정센터',
		'슈어스마일-투명교정' => '슈어스마일 투명교정',
		'브라켓-치아교정' => '브라켓 치아교정',
		'장치교정' => '브라켓 치아교정', // legacy - 옛 페이지 접속 시에도 새 이름으로
	);
	if ( isset( $map[ $slug ] ) ) return $map[ $slug ];
	return $title;
}, 10, 2 );

/**
 * v3.44.5 · 핵심 페이지 유실 자동 복구
 * 사용자가 실수로 wp-admin에서 '의료진', '오시는-길' 등 핵심 페이지를 삭제한 경우
 * 프론트에서 404가 발생하므로 · admin 또는 프론트 접속 시 자동 재생성.
 *
 * 검사 대상: 슬러그·제목·템플릿 매칭이 없으면 wp_insert_post 후 flush.
 * flag는 페이지 삭제 감지 후에도 다시 도는 로직 (get_option 저장 없이 매 요청마다 존재만 검사).
 */
/**
 * v3.44.58 · Polylang 우회 · 페이지 존재 여부를 wpdb 직접 조회
 *          get_page_by_path() 는 Polylang 언어 필터에 걸려서 false 반환하는 경우가 있음
 *          → wpdb 원시 쿼리로 슬러그·페이지 존재 여부만 확인
 */
function moondental_page_exists_by_slug( $slug ) {
	global $wpdb;
	// v3.44.60 · 한글 slug는 DB에 URL 인코딩(소문자)으로 저장됨 · 양쪽 다 시도
	$encoded = strtolower( urlencode( $slug ) );
	$id = $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		 WHERE post_type = 'page'
		   AND post_status = 'publish'
		   AND post_name IN (%s, %s)
		 ORDER BY ID ASC LIMIT 1",
		$slug, $encoded
	) );
	return $id ? (int) $id : 0;
}

/**
 * v3.44.58 · '슬러그-N' 형태 중복 페이지 일괄 휴지통 이동
 *          Polylang 필터 문제로 반복 자동 생성된 페이지들 정리
 */
function moondental_cleanup_duplicate_pages() {
	global $wpdb;
	// 중복이 폭증하는 대상 슬러그 · 한글 원본
	$slugs_raw = array( '의료진', '오시는-길', '상담예약', '비용-안내', '임플란트-센터', '투명교정-센터', '슈어스마일-투명교정', '브라켓-치아교정', '자연치아-살리기', '턱관절-클리닉', '사랑니-발치', '심미치료', '예방클리닉', '홈' );
	$trashed = 0;
	$details = array();
	foreach ( $slugs_raw as $raw ) {
		// v3.44.60 · WP는 한글 slug를 URL 인코딩(소문자)으로 저장
		//         '의료진' → '%ec%9d%98%eb%a3%8c%ec%a7%84'
		$base = strtolower( urlencode( $raw ) );
		// 원본 (가장 오래된 정확 slug) ID 확인 - 이 페이지는 유지
		$keep_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'page' AND post_name = %s
			 ORDER BY ID ASC LIMIT 1",
			$base
		) );
		// '의료진-2', '의료진-3' 등 접미 페이지들만 대량 trash
		$affected = $wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->posts}
			 SET post_status = 'trash', post_name = CONCAT(post_name, '__trash')
			 WHERE post_type = 'page'
			   AND post_status IN ('publish','draft','pending','private')
			   AND post_name LIKE %s
			   AND post_name <> %s
			   AND ID <> %d",
			$base . '-%',
			$base,
			$keep_id
		) );
		if ( $affected ) $trashed += (int) $affected;
		$details[ $raw ] = array( 'encoded' => $base, 'keep_id' => $keep_id, 'trashed' => (int) $affected );
	}
	// 요약 · 옵션에 저장 (진단용)
	update_option( 'md_last_cleanup_count', $trashed, false );
	update_option( 'md_last_cleanup_details', $details, false );
	update_option( 'md_last_cleanup_time', current_time( 'mysql' ), false );
	return $trashed;
}

/**
 * v3.44.61 · 강제 실행 엔드포인트 제거 (보안) — 관리자만 실행 가능
 * 접근: /?_md_cleanup=RUN · manage_options 권한 필요
 */
add_action( 'wp_loaded', function() {
	if ( ! isset( $_GET['_md_cleanup'] ) || $_GET['_md_cleanup'] !== 'RUN' ) return;
	if ( ! current_user_can( 'manage_options' ) ) {
		status_header( 403 );
		echo 'Forbidden';
		exit;
	}
	$count = moondental_cleanup_duplicate_pages();
	flush_rewrite_rules( false );
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( array(
		'ok'      => true,
		'trashed' => $count,
		'details' => get_option( 'md_last_cleanup_details' ),
		'time'    => get_option( 'md_last_cleanup_time' ),
	), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	exit;
}, 1 );

function moondental_ensure_core_pages() {
	static $checked = false;
	if ( $checked ) return;
	$checked = true;

	// v3.44.41 · 매 요청마다 실행 방지 · 1시간에 1회만 · DB 조회 4회 절감
	// v3.44.58 · 중복 페이지 자동 정리 (한 번만) + Polylang 우회 · transient 키 갱신
	if ( get_option( 'md_duplicate_pages_cleaned_v58' ) !== '1' ) {
		moondental_cleanup_duplicate_pages();
		update_option( 'md_duplicate_pages_cleaned_v58', '1', false );
	}
	if ( get_transient( 'md_core_pages_verified_v58' ) ) return;

	// v3.44.45 · 옛 '장치교정' 페이지가 존재하면 → '브라켓-치아교정'으로 이름·슬러그 변경
	$old = get_page_by_path( '장치교정' );
	if ( $old && ! get_page_by_path( '브라켓-치아교정' ) ) {
		wp_update_post( array(
			'ID'         => $old->ID,
			'post_name'  => '브라켓-치아교정',
			'post_title' => '브라켓 치아교정',
		) );
	}

	// v3.44.46 · 교정센터 페이지 · 옛 SureSmile 상세 본문 초기화 (file 기본값 적용되도록)
	$ortho = get_page_by_path( '투명교정-센터' );
	if ( $ortho && $ortho->post_content !== '' ) {
		// 옛 상세 콘텐츠 detection · 슈어스마일 관련 특정 문구 있으면 리셋
		if ( strpos( $ortho->post_content, '슈어스마일' ) !== false
		  || strpos( $ortho->post_content, 'SureSmile' ) !== false
		  || strlen( $ortho->post_content ) > 500 ) {
			wp_update_post( array(
				'ID'           => $ortho->ID,
				'post_content' => '', // file default 사용 · 새 랜딩 페이지 노출
			) );
		}
	}

	// v3.44.84 · URL 평면화 · 모든 core 페이지 최상위 (parent 없음)
	$core_pages = array(
		array( 'slug' => '의료진',              'title' => '의료진',            'template' => 'page-templates/page-doctors.php',     'parent' => '' ),
		array( 'slug' => '오시는-길',           'title' => '오시는 길',          'template' => 'page-templates/page-location.php',    'parent' => '' ),
		array( 'slug' => '상담예약',            'title' => '상담 예약',          'template' => 'page-templates/page-reservation.php', 'parent' => '' ),
		array( 'slug' => '비용-안내',           'title' => '비용 안내',          'template' => 'page-templates/page-pricing.php',     'parent' => '' ),
		array( 'slug' => '슈어스마일-투명교정', 'title' => '슈어스마일 투명교정', 'template' => 'page-templates/page-service.php',     'parent' => '' ),
		array( 'slug' => '브라켓-치아교정',      'title' => '브라켓 치아교정',    'template' => 'page-templates/page-service.php',     'parent' => '' ),
	);

	$created = false;
	foreach ( $core_pages as $p ) {
		// v3.44.58 · Polylang 우회 · wpdb 직접 조회로 존재 확인
		if ( moondental_page_exists_by_slug( $p['slug'] ) ) continue;
		$parent_id = 0;
		if ( $p['parent'] ) {
			$parent_id = moondental_page_exists_by_slug( $p['parent'] );
		}
		$id = wp_insert_post( array(
			'post_title'    => $p['title'],
			'post_name'     => $p['slug'],
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_content'  => '',
			'post_parent'   => $parent_id,
			'page_template' => $p['template'],
		) );
		if ( $id && ! is_wp_error( $id ) ) $created = true;
	}
	if ( $created ) flush_rewrite_rules( false );
	// 모두 존재 확인됨 · 1시간 동안 재확인 안 함
	set_transient( 'md_core_pages_verified_v58', 1, HOUR_IN_SECONDS );
}
add_action( 'admin_init', 'moondental_ensure_core_pages' );
/* 프론트에서도 · 관리자 접속 전 자동 복구 · 요청당 1회만 실행 (static flag) */
add_action( 'wp_loaded',   'moondental_ensure_core_pages' );

/**
 * 페이지 일괄 생성. 이미 있는 슬러그는 건드리지 않음.
 * Admin Toolbar에서 "기본 페이지 만들기" 누르면 실행.
 */
function moondental_create_default_pages() {
	if ( ! current_user_can( 'manage_options' ) ) return new WP_Error( 'forbidden', '권한이 없습니다.' );

	$created = array();
	$pages   = moondental_default_pages();

	// 1차: parent 없는 페이지 먼저
	foreach ( $pages as $page ) {
		if ( $page['parent'] ) continue;
		if ( get_page_by_path( $page['slug'] ) ) continue;
		$id = wp_insert_post( array(
			'post_title'    => $page['title'],
			'post_name'     => $page['slug'],
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_content'  => '',
			'menu_order'    => $page['order'],
			'page_template' => $page['template'] ?: 'default',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$created[] = $page['slug'];
		}
	}

	// 2차: parent 있는 페이지
	foreach ( $pages as $page ) {
		if ( ! $page['parent'] ) continue;
		if ( get_page_by_path( $page['parent'] . '/' . $page['slug'] ) ) continue;
		if ( get_page_by_path( $page['slug'] ) ) continue;
		$parent_obj = get_page_by_path( $page['parent'] );
		$id = wp_insert_post( array(
			'post_title'    => $page['title'],
			'post_name'     => $page['slug'],
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_content'  => '',
			'post_parent'   => $parent_obj ? $parent_obj->ID : 0,
			'menu_order'    => $page['order'],
			'page_template' => $page['template'] ?: 'default',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			$created[] = $page['slug'];
		}
	}

	// 정적 홈 + 글 페이지 자동 설정 (해당 슬러그 페이지가 있을 때만)
	$home    = get_page_by_path( '홈' );
	$notices = get_page_by_path( '소식' );
	if ( $home ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
	}
	if ( $notices ) {
		update_option( 'page_for_posts', $notices->ID );
	}

	return $created;
}

/**
 * 관리자 페이지에 "기본 페이지 만들기" 액션 등록.
 * URL: /wp-admin/admin.php?page=moondental-tools
 */
function moondental_admin_menu() {
	add_theme_page(
		'문치과 사이트 도구',
		'문치과 사이트 도구',
		'manage_options',
		'moondental-tools',
		'moondental_admin_tools_page'
	);
}
add_action( 'admin_menu', 'moondental_admin_menu' );

function moondental_admin_tools_page() {
	// v3.37.0 · defense in depth · capability 이중 체크
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( '이 페이지에 접근할 권한이 없습니다.' ), 403 );
	}
	$ran = false; $created = array();
	if ( isset( $_POST['moondental_seed_pages'] ) && check_admin_referer( 'moondental_seed' ) ) {
		$created = moondental_create_default_pages();
		$ran     = true;
	}
	$menu_ran = false; $menu_result = null;
	if ( isset( $_POST['moondental_setup_menu'] ) && check_admin_referer( 'moondental_menu' ) ) {
		$menu_result = moondental_setup_primary_menu( true );
		$menu_ran    = true;
	}
	$recat_ran = false; $recat_result = null;
	if ( isset( $_POST['moondental_recategorize'] ) && check_admin_referer( 'moondental_recat' ) ) {
		$recat_result = moondental_recategorize_posts();
		$recat_ran    = true;
	}
	// 네이버 동기화 핸들러는 v3.21.0에서 제거됨 (연동 해제).

	// 네이버 임포트 글 액션 핸들러 (v3.21.5)
	$naver_action = '';
	$naver_count  = 0;
	if ( isset( $_POST['moondental_naver_trash'] ) && check_admin_referer( 'moondental_naver' ) ) {
		$ids = get_posts( array( 'post_type'=>'post','post_status'=>array('publish','draft','pending','future','private'),'numberposts'=>-1,'fields'=>'ids','meta_query'=>array(array('key'=>'moondental_naver_log_no','compare'=>'EXISTS')) ) );
		foreach ( $ids as $pid ) wp_trash_post( $pid );
		$naver_action = 'trashed';
		$naver_count  = count( $ids );
	}
	if ( isset( $_POST['moondental_naver_restore'] ) && check_admin_referer( 'moondental_naver' ) ) {
		$ids = get_posts( array( 'post_type'=>'post','post_status'=>'trash','numberposts'=>-1,'fields'=>'ids','meta_query'=>array(array('key'=>'moondental_naver_log_no','compare'=>'EXISTS')) ) );
		foreach ( $ids as $pid ) wp_untrash_post( $pid );
		$naver_action = 'restored';
		$naver_count  = count( $ids );
	}
	if ( isset( $_POST['moondental_naver_delete'] ) && check_admin_referer( 'moondental_naver' ) ) {
		$ids = get_posts( array( 'post_type'=>'post','post_status'=>'trash','numberposts'=>-1,'fields'=>'ids','meta_query'=>array(array('key'=>'moondental_naver_log_no','compare'=>'EXISTS')) ) );
		foreach ( $ids as $pid ) wp_delete_post( $pid, true );
		$naver_action = 'deleted';
		$naver_count  = count( $ids );
	}

	// 현재 네이버 임포트 글 카운트
	$naver_live_ids  = get_posts( array( 'post_type'=>'post','post_status'=>array('publish','draft','pending','future','private'),'numberposts'=>-1,'fields'=>'ids','meta_query'=>array(array('key'=>'moondental_naver_log_no','compare'=>'EXISTS')) ) );
	$naver_trash_ids = get_posts( array( 'post_type'=>'post','post_status'=>'trash','numberposts'=>-1,'fields'=>'ids','meta_query'=>array(array('key'=>'moondental_naver_log_no','compare'=>'EXISTS')) ) );

	// 1회용 로컬 임포터 (v3.21.7)
	$local_import_ran = false; $local_import_result = null;
	if ( isset( $_POST['moondental_local_import'] ) && check_admin_referer( 'moondental_local_import' ) ) {
		$limit = max( 1, min( 30, (int) ( $_POST['moondental_local_import_limit'] ?? 15 ) ) );
		if ( function_exists( 'moondental_naver_import_local' ) ) {
			$local_import_result = moondental_naver_import_local( $limit );
			$local_import_ran    = true;
		}
	}
	?>
	<div class="wrap">
		<h1>문치과 사이트 도구</h1>

		<?php if ( $ran ) : ?>
			<div class="notice notice-success"><p>
				<?php if ( empty( $created ) || is_wp_error( $created ) ) : ?>
					이미 모든 기본 페이지가 존재합니다.
				<?php else : ?>
					다음 페이지를 새로 만들었습니다: <strong><?php echo esc_html( implode( ', ', $created ) ); ?></strong>
				<?php endif; ?>
			</p></div>
		<?php endif; ?>

		<?php if ( $menu_ran && $menu_result ) : ?>
			<div class="notice notice-success"><p>
				<strong>주 메뉴 자동 설정 완료.</strong>
				생성·갱신된 항목 <?php echo (int) $menu_result['count']; ?>개. 메뉴는 외모 → 메뉴에서 추가 편집 가능.
			</p></div>
		<?php endif; ?>

		<?php if ( $recat_ran && $recat_result ) : ?>
			<div class="notice notice-success"><p>
				<strong>글 자동 분류 완료.</strong>
				🦷 치아이야기 <strong><?php echo (int) $recat_result['story']; ?>건</strong> ·
				📢 공지사항 <strong><?php echo (int) $recat_result['notice']; ?>건</strong>
				<?php if ( ! empty( $recat_result['skipped'] ) ) : ?>
					· 건너뜀 <strong><?php echo (int) $recat_result['skipped']; ?>건</strong>
				<?php endif; ?>
			</p></div>
		<?php endif; ?>

		<div class="card" style="max-width:720px; padding:24px; margin-bottom:16px;">
			<h2>글 자동 분류 — 치아이야기 / 공지사항</h2>
			<p>모든 글을 본문·제목 키워드로 자동 분류합니다.</p>
			<ul style="margin-left:18px; font-size:13px; color:#555;">
				<li><strong>🦷 치아이야기</strong>로 분류 (임상·치료 관련):
					임상·임플란트·교정·라미네이트·미백·발치·신경치료·잇몸·충치·보철·크라운·사랑니·턱관절·CBCT·환자·증례·진단·검진·자연치아·예방·스케일링·에어플로우·불소·실란트·치료·시술·수술·디지털·가이드·내원·통증·잇몸염·치주염</li>
				<li><strong>📢 공지사항</strong>으로 분류 (그 외):
					휴진·진료시간 변경·이벤트·캠페인·봉사·채용·수상·명절·새해·추석·협력·인증·지정 등 운영·행정 글</li>
			</ul>
			<p style="font-size:13px; color:#a00;">⚠️ 카테고리가 모두 재할당됩니다. 기존 다른 카테고리는 해제됩니다.</p>
			<form method="post">
				<?php wp_nonce_field( 'moondental_recat' ); ?>
				<p><button type="submit" name="moondental_recategorize" class="button button-primary button-large">글 자동 분류 실행</button></p>
			</form>
		</div>

		<div class="card" style="max-width:720px; padding:24px; margin-bottom:16px;">
			<h2>주 메뉴 자동 설정 (헤더 메뉴)</h2>
			<p>스크린샷 기준 8개 메뉴 구조를 자동 설정합니다. (임플란트센터·교정센터·스마일디자인센터·자연치아살리기·진료과·의료진·비용안내·병원안내)
			   하위 메뉴 포함. <strong>이미 메뉴가 있다면 비우고 새로 채웁니다.</strong></p>
			<p style="font-size:13px; color:#666;">⚠️ 실행 전: 위의 "기본 페이지 만들기"를 먼저 실행해야 하위 페이지가 모두 생성됩니다.</p>
			<form method="post">
				<?php wp_nonce_field( 'moondental_menu' ); ?>
				<p>
					<button type="submit" name="moondental_setup_menu" class="button button-primary button-large">주 메뉴 자동 설정</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width:720px; padding:24px;">
			<h2>기본 페이지 일괄 생성</h2>
			<p>홈 · 병원소개 · 의료진 · 진료안내(5개 하위) · 오시는 길 · 공지사항 페이지를 한 번에 만듭니다.
			   각 페이지는 적절한 템플릿이 자동 할당되며, 본문은 비어 있을 때 테마에 정의된 기본 콘텐츠가 자동으로 출력됩니다.
			   <strong>이미 있는 페이지는 건드리지 않습니다.</strong></p>
			<form method="post">
				<?php wp_nonce_field( 'moondental_seed' ); ?>
				<p>
					<button type="submit" name="moondental_seed_pages" class="button button-primary button-large">기본 페이지 만들기</button>
				</p>
			</form>
		</div>

		<?php /* 네이버 블로그 연동 도구는 v3.21.0에서 제거됨. 라이브 RSS·자동 동기화 모두 OFF.
		     관리자는 wp-admin → 글 → 새 글 작성 + 카테고리(문치과병원 소식 / 치아이야기) 직접 선택,
		     또는 /병원소식/ 페이지 헤더의 [＋ 새 소식 글쓰기] 버튼으로 글을 추가하세요. */ ?>

		<div class="card" style="max-width:720px; padding:24px; margin-top:16px; background:#f0f7f4;">
			<h2>네이버 블로그 → 워드프레스 1회 복사 (본문 + 사진 로컬 저장)</h2>
			<p>네이버 블로그(<code><?php echo esc_html( moondental_get_info( 'blog_url' ) ); ?></code>)의
			   최신 글 본문을 가져오고, 본문 내 모든 사진을 워드프레스 미디어 라이브러리로 다운로드합니다.
			   결과: 네이버 의존성 0 — 사진이 자체 서버에서 서빙되어 referer 차단 문제 없음.</p>
			<p style="font-size:13px; color:#666;">
				· 1회용 — 자동 동기화 X, 사용자가 버튼을 누를 때만 동작<br>
				· 글 1개당 3~10초 소요 (사진 다운로드 포함) — 15개 가져오면 1~3분<br>
				· 같은 글은 다시 가져오지 않음 (source URL로 중복 체크)<br>
				· 키워드 자동 분류 — MOU·연합회 등은 소식, 임상 글은 치아이야기로 자동 배정<br>
				· 첫 번째 사진은 대표 이미지(썸네일)로 자동 설정
			</p>

			<?php if ( $local_import_ran && $local_import_result ) : ?>
				<div class="notice notice-info" style="margin:12px 0; padding:12px;">
					<p>
						새로 가져온 글: <strong><?php echo count( $local_import_result['created'] ); ?>개</strong> ·
						로컬 다운로드된 사진: <strong><?php echo (int) $local_import_result['images']; ?>장</strong> ·
						이미 있어서 건너뜀: <strong><?php echo (int) $local_import_result['skipped']; ?>개</strong>
						<?php if ( ! empty( $local_import_result['errors'] ) ) : ?>
							· 오류: <strong><?php echo count( $local_import_result['errors'] ); ?>건</strong>
						<?php endif; ?>
					</p>
					<?php if ( ! empty( $local_import_result['errors'] ) ) : ?>
						<details style="margin-top:8px;"><summary>오류 상세</summary>
							<ul style="margin:8px 0 0 16px;">
								<?php foreach ( $local_import_result['errors'] as $e ) : ?>
									<li style="font-size:12px; color:#a00;"><?php echo esc_html( $e ); ?></li>
								<?php endforeach; ?>
							</ul>
						</details>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'moondental_local_import' ); ?>
				<p>
					<label>가져올 글 수:
						<input type="number" name="moondental_local_import_limit" min="1" max="30" value="15" style="width:70px;">
					</label>
					<button type="submit" name="moondental_local_import" class="button button-primary button-large" style="margin-left:8px;"
						onclick="return confirm('네이버 블로그 글과 사진을 가져옵니다. 진행 중 페이지를 닫지 마세요. 계속하시겠습니까?');">
						네이버 블로그 1회 복사 실행
					</button>
				</p>
			</form>
		</div>

		<div class="card" style="max-width:720px; padding:24px; margin-top:16px; background:#fff8f3;">
			<h2>네이버 임포트 글 정리</h2>
			<p>네이버 블로그에서 가져온 글들은 본문 이미지가 네이버 CDN의 referer 차단으로 보이지 않습니다.
			   깔끔하게 정리한 뒤 직접 새 글로 작성하세요. (휴지통으로 보내므로 언제든 복원 가능)</p>

			<?php if ( $naver_action === 'trashed' ) : ?>
				<div class="notice notice-success"><p><strong><?php echo (int) $naver_count; ?>건</strong>을 휴지통으로 보냈습니다.</p></div>
			<?php elseif ( $naver_action === 'restored' ) : ?>
				<div class="notice notice-success"><p><strong><?php echo (int) $naver_count; ?>건</strong>을 휴지통에서 복원했습니다.</p></div>
			<?php elseif ( $naver_action === 'deleted' ) : ?>
				<div class="notice notice-success"><p><strong><?php echo (int) $naver_count; ?>건</strong>을 완전 삭제했습니다.</p></div>
			<?php endif; ?>

			<p style="font-size:13px; color:#555;">
				· 활성 네이버 글: <strong><?php echo count( $naver_live_ids ); ?>건</strong><br>
				· 휴지통의 네이버 글: <strong><?php echo count( $naver_trash_ids ); ?>건</strong>
			</p>

			<form method="post" style="display:inline-block; margin-right:8px;">
				<?php wp_nonce_field( 'moondental_naver' ); ?>
				<button type="submit" name="moondental_naver_trash" class="button button-primary"
					<?php echo count( $naver_live_ids ) === 0 ? 'disabled' : ''; ?>
					onclick="return confirm('네이버에서 가져온 모든 글(<?php echo count( $naver_live_ids ); ?>건)을 휴지통으로 보냅니다. 계속하시겠습니까?');">
					네이버 임포트 글 휴지통 보내기
				</button>
			</form>
			<form method="post" style="display:inline-block; margin-right:8px;">
				<?php wp_nonce_field( 'moondental_naver' ); ?>
				<button type="submit" name="moondental_naver_restore" class="button"
					<?php echo count( $naver_trash_ids ) === 0 ? 'disabled' : ''; ?>>
					휴지통에서 복원
				</button>
			</form>
			<form method="post" style="display:inline-block;">
				<?php wp_nonce_field( 'moondental_naver' ); ?>
				<button type="submit" name="moondental_naver_delete" class="button button-link-delete"
					<?php echo count( $naver_trash_ids ) === 0 ? 'disabled' : ''; ?>
					onclick="return confirm('휴지통의 네이버 글(<?php echo count( $naver_trash_ids ); ?>건)을 완전 삭제합니다. 복구 불가. 계속하시겠습니까?');">
					휴지통 완전 비우기
				</button>
			</form>
		</div>

		<div class="card" style="max-width:720px; padding:24px; margin-top:16px;">
			<h2>의료진 사진 업로드 위치</h2>
			<p>의료진 사진은 워드프레스 미디어가 아니라 테마 폴더에 직접 저장합니다:</p>
			<p><code style="display:block; padding:12px; background:#f0f0f0;"><?php echo esc_html( str_replace( ABSPATH, '', MOONDENTAL_DIR ) ); ?>/assets/images/doctors/</code></p>
			<p>이 폴더에 <code>doctor-01.jpg ~ doctor-09.jpg</code> 9장을 저장하면 의료진 그리드에 자동 표시됩니다.
			   파일명·매칭 규칙은 같은 폴더의 <code>README.md</code> 참고.</p>
		</div>

		<div class="card" style="max-width:720px; padding:24px; margin-top:16px;">
			<h2>병원 정보 / 콘텐츠 편집</h2>
			<p><a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="button">사용자 정의하기 열기</a> →
			   "문치과병원 설정" 패널에서 전화·주소·진료시간·SNS·홈 메인 카피·원장 약력 등 모두 편집 가능합니다.</p>
		</div>
	</div>
	<?php
}


/* ============================================================
 * 5. 사이트 최적화 / 보안 / UX
 * ========================================================== */

/**
 * 워드프레스 기본 이모지 스크립트 제거 (속도)
 */
function moondental_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'moondental_disable_emojis' );

/**
 * 외부 oEmbed 비활성화 (보안 + 속도)
 */
function moondental_remove_oembed() {
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'moondental_remove_oembed' );

/**
 * 댓글 기능 전역 비활성화 (의료 사이트 기본값)
 */
function moondental_disable_comments_post_types_support() {
	$post_types = get_post_types();
	foreach ( $post_types as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}
add_action( 'admin_init', 'moondental_disable_comments_post_types_support' );

/**
 * 관리자 바에서 댓글 메뉴 제거
 */
function moondental_remove_comments_admin_bar() {
	if ( isset( $GLOBALS['wp_admin_bar'] ) ) {
		$GLOBALS['wp_admin_bar']->remove_menu( 'comments' );
	}
}
add_action( 'wp_before_admin_bar_render', 'moondental_remove_comments_admin_bar' );

/**
 * 한글 슬러그 → 영문 사용 권장 안내 (post 화면)
 */
function moondental_admin_slug_notice() {
	$screen = get_current_screen();
	if ( $screen && $screen->base === 'post' ) {
		echo '<div class="notice notice-info is-dismissible"><p><strong>안내:</strong> SEO를 위해 URL 슬러그는 영문/숫자만 사용하세요. (예: implant, ortho-event)</p></div>';
	}
}
add_action( 'admin_notices', 'moondental_admin_slug_notice' );

/**
 * WP 버전 노출 제거 (보안 — 헤더/RSS)
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * XML-RPC 비활성화 (공격 표면 축소)
 */
add_filter( 'xmlrpc_enabled', '__return_false' );


/* ============================================================
 * 6. Elementor 통합 (자식 테마 위치 알림)
 * ========================================================== */
function moondental_elementor_locations( $elementor_theme_manager ) {
	$elementor_theme_manager->register_all_core_location();
}
add_action( 'elementor/theme/register_locations', 'moondental_elementor_locations' );


/* ============================================================
 * 6b. Template Router — 슬러그 기반 자동 템플릿 할당
 * ========================================================== */
/**
 * 사용자가 페이지를 만들 때 "페이지 속성 → 템플릿"을 수동으로 선택하지 않아도
 * 슬러그가 일치하면 알맞은 템플릿을 자동으로 사용한다.
 *
 * 슬러그가 WP에 URL-encoded 형태로 저장된 경우와 raw UTF-8 한글 모두 처리.
 */
function moondental_template_router( $template ) {
	// 소식 페이지가 page_for_posts(글 페이지)로 설정된 경우 → page-news.php
	if ( is_home() && ! is_front_page() ) {
		$pfp_id = (int) get_option( 'page_for_posts' );
		$slug   = $pfp_id ? urldecode( (string) get_post_field( 'post_name', $pfp_id ) ) : '';
		if ( in_array( $slug, array( '소식', 'news' ), true ) ) {
			$custom = locate_template( 'page-templates/page-news.php' );
			if ( $custom ) return $custom;
		}
	}
	if ( ! is_page() ) return $template;

	// 사용자가 명시적으로 템플릿을 지정했으면 그 결정을 존중
	$current = get_page_template_slug( get_queried_object_id() );
	if ( $current && $current !== 'default' ) return $template;

	$slug = (string) get_post_field( 'post_name', get_queried_object_id() );
	$slug = urldecode( $slug ); // 한글 정규화

	$map = array(
		'의료진'         => 'page-templates/page-doctors.php',
		'오시는-길'       => 'page-templates/page-location.php',
		'오시는길'        => 'page-templates/page-location.php',
		'location'        => 'page-templates/page-location.php',
		'소식'           => 'page-templates/page-news.php',
		'역사'           => 'page-templates/page-history.php',
		'상담예약'       => 'page-templates/page-reservation.php',
		'reservation'    => 'page-templates/page-reservation.php',
		'faq'            => 'page-templates/page-faq.php',
		'자주-묻는-질문' => 'page-templates/page-faq.php',
		'임플란트-센터'   => 'page-templates/page-service.php',
		'소아치과'       => 'page-templates/page-service.php',
		'투명교정-센터'   => 'page-templates/page-service.php',
		'자연치아-살리기' => 'page-templates/page-preservation.php',
		'턱관절-클리닉'   => 'page-templates/page-service.php',
		'사랑니-발치'     => 'page-templates/page-service.php',
		'심미치료'       => 'page-templates/page-service.php',
		'비용-안내'      => 'page-templates/page-pricing.php',
		'비급여-진료비'   => 'page-templates/page-pricing.php',
		'진료비안내'      => 'page-templates/page-pricing.php',
		'pricing'        => 'page-templates/page-pricing.php',
		'기술력-시설'     => 'page-templates/page-facility.php',
		'기술력시설'      => 'page-templates/page-facility.php',
		'facility'       => 'page-templates/page-facility.php',
		'스마일디자인센터' => 'page-templates/page-smile-design.php',
		'smile-design'    => 'page-templates/page-smile-design.php',
		'예방클리닉'      => 'page-templates/page-prevention.php',
		'예방-클리닉'     => 'page-templates/page-prevention.php',
		'prevention'      => 'page-templates/page-prevention.php',
		'상시채용'        => 'page-templates/page-recruit.php',
		'채용'           => 'page-templates/page-recruit.php',
		'recruit'        => 'page-templates/page-recruit.php',
		'careers'        => 'page-templates/page-recruit.php',
	);

	if ( isset( $map[ $slug ] ) ) {
		$custom = locate_template( $map[ $slug ] );
		if ( $custom ) return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'moondental_template_router', 99 );


/* ============================================================
 * 6b. 의료진 상세 페이지 — /의료진/{slug}/ 자동 라우팅
 * ========================================================== */
function moondental_doctor_rewrite_rules() {
	/* /의료진/{ascii-slug}/ → query var doctor_slug.
	 *  ASCII만 매치 — 한글 URL은 인코딩 변환 문제가 있어 영문 슬러그 사용.
	 *  Korean URL pattern은 fallback으로 함께 등록.
	 */
	add_rewrite_rule( '^의료진/([a-z0-9_-]+)/?$',                     'index.php?doctor_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^doctors/([a-z0-9_-]+)/?$',                    'index.php?doctor_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^%EC%9D%98%EB%A3%8C%EC%A7%84/([^/]+)/?$',      'index.php?doctor_slug=$matches[1]', 'top' );
}
add_action( 'init', 'moondental_doctor_rewrite_rules' );

function moondental_doctor_query_vars( $vars ) {
	$vars[] = 'doctor_slug';
	return $vars;
}
add_filter( 'query_vars', 'moondental_doctor_query_vars' );

/* 의료진 상세 페이지 라우팅 — doctor_slug query var가 있으면 단일 템플릿으로 */
function moondental_doctor_single_router( $template ) {
	$doctor_slug = get_query_var( 'doctor_slug' );
	if ( ! $doctor_slug ) return $template;
	$custom = locate_template( 'page-templates/page-doctor-single.php' );
	if ( $custom ) {
		// 404 방지 — 강제로 200 응답
		status_header( 200 );
		global $wp_query;
		$wp_query->is_404 = false;
		return $custom;
	}
	return $template;
}
add_filter( 'template_include', 'moondental_doctor_single_router', 98 );

/**
 * URL 직접 가로채기 — rewrite rule이 동작하지 않는 환경(공지사항 fallback 등) 대비.
 *  template_redirect 최우선 hook으로 /의료진/{slug}/ 패턴 검출 시
 *  page-doctor-single.php 템플릿 직접 로드.
 */
function moondental_doctor_intercept() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	if ( ! $path ) return;
	$path = trim( urldecode( $path ), '/' );

	// 패턴: 의료진/{slug}/ 또는 doctors/{slug}/
	if ( ! preg_match( '#^(?:의료진|doctors)/([^/]+)/?$#u', $path, $m ) ) return;

	$slug = trim( $m[1] );
	if ( ! $slug ) return;

	$doctor = function_exists( 'moondental_get_doctor_by_slug' )
		? moondental_get_doctor_by_slug( $slug )
		: null;
	if ( ! $doctor ) return; // 의료진 없으면 WP 기본 라우팅에 맡김

	// 의료진 발견 — page-doctor-single.php 로 강제 라우팅
	set_query_var( 'doctor_slug', $slug );
	global $wp_query;
	$wp_query->is_404      = false;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_home     = false;
	$wp_query->is_archive  = false;
	status_header( 200 );
	moondental_intercept_setup_post( '의료진' );

	$tpl = locate_template( 'page-templates/page-doctor-single.php' );
	if ( $tpl ) {
		include $tpl;
		exit;
	}
}
add_action( 'template_redirect', 'moondental_doctor_intercept', 1 );

/**
 * 강제 라우팅된 페이지에서 body_class()·get_the_title() 등이 정상 동작하도록
 * 베이스 페이지를 queried object로 설정. PHP 경고 방지.
 *
 * @param string $base_slug 베이스 페이지 슬러그 (예: '의료진', '오시는-길', '기술력-시설')
 */
function moondental_intercept_setup_post( $base_slug ) {
	$base = get_page_by_path( $base_slug );
	// Fallback 1: 한글 슬러그 URL-decode 변형
	if ( ! $base ) {
		$base = get_page_by_path( urldecode( $base_slug ) );
	}
	// Fallback 2: 아무 publish 페이지든 사용 — $post null 방지가 핵심 목적
	if ( ! $base ) {
		$any = get_pages( array(
			'number'      => 1,
			'post_status' => 'publish',
			'sort_order'  => 'ASC',
			'sort_column' => 'menu_order',
		) );
		if ( $any ) $base = $any[0];
	}
	// Fallback 3: 홈페이지 객체 사용
	if ( ! $base ) {
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id ) $base = get_post( $front_id );
	}
	if ( ! $base ) return;

	global $wp_query, $post;
	$post = $base;
	setup_postdata( $post );
	$wp_query->post              = $base;
	$wp_query->posts             = array( $base );
	$wp_query->queried_object    = $base;
	$wp_query->queried_object_id = $base->ID;
	$wp_query->post_count        = 1;
	$wp_query->current_post      = -1;
	$wp_query->found_posts       = 1;
}

/**
 * /강점/{slug}/ URL 가로채기 — 강점 상세 페이지 라우팅.
 *  template_redirect 우선순위 1로 패턴 매칭 → page-strength.php 강제 로드.
 */
function moondental_strength_intercept() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	if ( ! $path ) return;
	$path = trim( urldecode( $path ), '/' );

	if ( ! preg_match( '#^(?:강점|strengths?)/([a-z0-9_-]+)/?$#u', $path, $m ) ) return;

	$slug = trim( $m[1] );
	if ( ! $slug ) return;

	$data = function_exists( 'moondental_get_strength_by_slug' )
		? moondental_get_strength_by_slug( $slug )
		: null;
	if ( ! $data ) return; // 알 수 없는 슬러그면 WP 기본 라우팅에 맡김

	set_query_var( 'strength_slug', $slug );
	global $wp_query;
	$wp_query->is_404      = false;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_home     = false;
	$wp_query->is_archive  = false;
	status_header( 200 );
	moondental_intercept_setup_post( '기술력-시설' );

	$tpl = locate_template( 'page-templates/page-strength.php' );
	if ( $tpl ) {
		include $tpl;
		exit;
	}
}
add_action( 'template_redirect', 'moondental_strength_intercept', 1 );

/**
 * /오시는-길/{region-slug}/ URL 가로채기 — 지역별 랜딩 페이지 라우팅.
 *  template_redirect 우선순위 1로 패턴 매칭 → page-region.php 강제 로드.
 *  지역 SEO를 위한 28개 지역 페이지의 URL 라우팅 핵심.
 */
function moondental_region_intercept() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	if ( ! $path ) return;
	$path = trim( urldecode( $path ), '/' );

	// 패턴: 오시는-길/{ascii-slug}/ 또는 오시는길/{ascii-slug}/ 또는 location/{ascii-slug}/
	if ( ! preg_match( '#^(?:오시는-길|오시는길|location)/([a-z0-9_-]+)/?$#u', $path, $m ) ) return;

	$slug = trim( $m[1] );
	if ( ! $slug ) return;

	$region = function_exists( 'moondental_get_region_by_slug' )
		? moondental_get_region_by_slug( $slug )
		: null;
	if ( ! $region ) return;

	set_query_var( 'region_slug', $slug );
	global $wp_query;
	$wp_query->is_404      = false;
	$wp_query->is_page     = true;
	$wp_query->is_singular = true;
	$wp_query->is_home     = false;
	$wp_query->is_archive  = false;
	status_header( 200 );
	moondental_intercept_setup_post( '오시는-길' );

	$tpl = locate_template( 'page-templates/page-region.php' );
	if ( $tpl ) {
		include $tpl;
		exit;
	}
}
add_action( 'template_redirect', 'moondental_region_intercept', 1 );

/* 테마 활성화 시 rewrite rules flush — 새 rule 패턴 반영 위해 버전 키 증가 */
function moondental_flush_rewrites_once() {
	if ( get_option( 'moondental_rewrite_flushed_v5' ) !== '1' ) {
		moondental_doctor_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( 'moondental_rewrite_flushed_v5', '1' );
	}
}
add_action( 'init', 'moondental_flush_rewrites_once', 99 );

/**
 * 의료진 이름 → 영문 ASCII URL 슬러그 매핑.
 *  한글 URL은 인코딩 호환성 문제로 영문 슬러그 사용.
 */
function moondental_doctor_name_to_slug( $name ) {
	static $map = null;
	if ( $map === null ) {
		// v3.38.6 · Customizer 'doctor_slug_map' 텍스트영역에서 파싱 (한 줄에 '이름|slug')
		$map = array();
		$default_raw = "문은수|munes\n이승주|leesj\n이수연|leesu\n권혜진|kwon\n문지현|munji\n이창률|leech\n이영일|leeyi\n김세일|kimsi\n정석형|jeong";
		$raw = function_exists( 'md_content' )
			? md_content( 'doctor_slug_map', $default_raw )
			: $default_raw;
		foreach ( preg_split( "/\r\n|\r|\n/", (string) $raw ) as $line ) {
			$line = trim( $line );
			if ( $line === '' || $line[0] === '#' ) continue;
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( count( $parts ) === 2 && $parts[0] !== '' && $parts[1] !== '' ) {
				$map[ $parts[0] ] = $parts[1];
			}
		}
	}
	return $map[ $name ] ?? sanitize_title( $name );
}

/**
 * 영문 슬러그(또는 한글 이름) → 의료진 데이터 반환.
 */
function moondental_get_doctor_by_slug( $slug ) {
	$slug = urldecode( $slug );
	$groups = function_exists( 'moondental_get_team_with_customizer' )
		? moondental_get_team_with_customizer()
		: moondental_get_team();
	foreach ( $groups as $group ) {
		foreach ( $group['members'] as $m ) {
			// 직접 이름 매치 (한글 fallback)
			if ( $m['name'] === $slug ) return $m;
			// ASCII 슬러그 매치
			if ( moondental_doctor_name_to_slug( $m['name'] ) === $slug ) return $m;
		}
	}
	return null;
}


/* ============================================================
 * 7. Body Class
 * ========================================================== */
/**
 * body에 페이지 식별 클래스 추가 — CSS에서 페이지별 분기 시 편리.
 */
function moondental_body_class( $classes ) {
	if ( is_front_page() ) {
		$classes[] = 'md-page-home';
	}
	if ( is_page() ) {
		$classes[] = 'md-page-' . sanitize_html_class( get_post_field( 'post_name', get_queried_object_id() ) );
	}
	return $classes;
}
add_filter( 'body_class', 'moondental_body_class' );


/* ============================================================
 * 8. Nav Menu Fallbacks
 * ========================================================== */
/**
 * 주 메뉴가 미설정일 때 보여줄 임시 메뉴.
 */
/**
 * 주 메뉴 자동 설정 — DB에 직접 wp_nav_menu 와 item을 생성.
 *  사용자가 외모 → 메뉴 UI를 만지지 않아도 헤더에 신규 8개 메뉴 구조가 노출됨.
 *
 * @param bool $force  기존 메뉴 비우고 새로 채울지
 * @return array       ['menu_id'=>int, 'count'=>int, 'items'=>array]
 */
function moondental_setup_primary_menu( $force = false ) {
	// v3.37.0 · defense in depth · 메뉴 편집 권한 없으면 거절
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return array( 'menu_id'=>0, 'count'=>0, 'items'=>array(), 'error'=>'insufficient_permissions' );
	}
	$menu_name = '주 메뉴 (자동 생성)';
	$menu_obj  = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu_obj ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) ) return array( 'menu_id'=>0, 'count'=>0, 'items'=>array() );
	} else {
		$menu_id = (int) $menu_obj->term_id;
		if ( $force ) {
			$existing = wp_get_nav_menu_items( $menu_id );
			if ( $existing ) {
				foreach ( $existing as $it ) wp_delete_post( $it->ID, true );
			}
		}
	}

	// primary 위치에 할당
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	$home = home_url( '/' );

	$structure = array(
		array( 'title'=>'임플란트 센터',     'url'=>$home.'임플란트-센터/' ),
		array( 'title'=>'교정 센터',         'url'=>$home.'투명교정-센터/' ),
		array( 'title'=>'스마일디자인 센터', 'url'=>$home.'스마일디자인센터/' ),
		array( 'title'=>'자연치아 살리기',   'url'=>$home.'자연치아-살리기/', 'children'=>array(
			array( 'title'=>'충치치료',   'url'=>$home.'자연치아-살리기/#cavity' ),
			array( 'title'=>'치수복조술', 'url'=>$home.'자연치아-살리기/#pulpcap' ),
			array( 'title'=>'신경치료',   'url'=>$home.'자연치아-살리기/#endo' ),
			array( 'title'=>'잇몸치료',   'url'=>$home.'자연치아-살리기/#perio' ),
		)),
		array( 'title'=>'진료과', 'url'=>'#', 'children'=>array(
			array( 'title'=>'턱관절클리닉',      'url'=>$home.'턱관절-클리닉/' ),
			array( 'title'=>'이갈이·이악물기', 'url'=>$home.'턱관절-클리닉/' ),
			array( 'title'=>'사랑니',           'url'=>$home.'사랑니-발치/' ),
			array( 'title'=>'소아치과',         'url'=>$home.'소아치과/' ),
			array( 'title'=>'예방클리닉',       'url'=>$home.'예방클리닉/' ),
		)),
		array( 'title'=>'의료진',    'url'=>$home.'의료진/' ),
		array( 'title'=>'비용 안내', 'url'=>$home.'비용-안내/' ),
		array( 'title'=>'병원 안내', 'url'=>'#', 'children'=>array(
			array( 'title'=>'오시는길·진료시간', 'url'=>$home.'오시는-길/' ),
			array( 'title'=>'30여년의 역사',    'url'=>$home.'역사/' ),
			array( 'title'=>'기술력/시설',       'url'=>$home.'기술력-시설/' ),
			array( 'title'=>'병원소식',         'url'=>$home.'소식/' ),
			array( 'title'=>'상시채용',         'url'=>$home.'상시채용/' ),
		)),
	);

	$count = 0;
	$order = 0;
	$items_log = array();

	foreach ( $structure as $item ) {
		$order += 10;
		$parent_id = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'     => $item['title'],
			'menu-item-url'       => $item['url'],
			'menu-item-type'      => 'custom',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $order,
			'menu-item-parent-id' => 0,
		) );
		if ( ! is_wp_error( $parent_id ) ) {
			$count++;
			$items_log[] = $item['title'];
		}

		if ( ! empty( $item['children'] ) ) {
			$child_order = 0;
			foreach ( $item['children'] as $child ) {
				$child_order += 10;
				$child_id = wp_update_nav_menu_item( $menu_id, 0, array(
					'menu-item-title'     => $child['title'],
					'menu-item-url'       => $child['url'],
					'menu-item-type'      => 'custom',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $child_order,
					'menu-item-parent-id' => (int) $parent_id,
				) );
				if ( ! is_wp_error( $child_id ) ) {
					$count++;
					$items_log[] = '  ↳ ' . $child['title'];
				}
			}
		}
	}

	return array(
		'menu_id' => $menu_id,
		'count'   => $count,
		'items'   => $items_log,
	);
}

/**
 * 테마 활성화 시 1회만 자동 메뉴 설정 (옵션 키로 중복 실행 방지).
 */
function moondental_auto_setup_menu_once() {
	if ( get_option( 'moondental_menu_v2_seeded' ) === '1' ) return;
	// 페이지가 존재해야 의미있는 URL이 됨 — 페이지 존재 확인 후 진행
	if ( ! get_page_by_path( '임플란트-센터' ) ) return;
	moondental_setup_primary_menu( true );
	update_option( 'moondental_menu_v2_seeded', '1' );
}
add_action( 'admin_init', 'moondental_auto_setup_menu_once' );

/**
 * 누락된 페이지 자동 생성 — admin 수동 작업 없이 메뉴 항목들이 모두 정상 동작하도록.
 *  스마일디자인센터·예방클리닉·상시채용 등이 기본 페이지 자동 생성 버튼을 누르지 않아도 만들어짐.
 *  옵션 키로 1회만 실행.
 */
function moondental_auto_create_pages_once() {
	// 옵션 키 v317로 변경 — 비용-안내·기술력-시설 등 신규 추가 페이지 재시드 유도
	if ( get_option( 'moondental_pages_v319_seeded' ) === '1' ) return;
	// 페이지 정의를 가져와서 빠진 것만 만들기 (권한 체크 우회 — 1회용 시드)
	if ( ! function_exists( 'moondental_default_pages' ) ) return;
	$pages = moondental_default_pages();

	// 1차: parent 없는 페이지
	foreach ( $pages as $page ) {
		if ( $page['parent'] ) continue;
		if ( get_page_by_path( $page['slug'] ) ) continue;
		wp_insert_post( array(
			'post_title'    => $page['title'],
			'post_name'     => $page['slug'],
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_content'  => '',
			'menu_order'    => $page['order'],
			'page_template' => $page['template'] ?: 'default',
		) );
	}
	// 2차: parent 있는 페이지
	foreach ( $pages as $page ) {
		if ( ! $page['parent'] ) continue;
		if ( get_page_by_path( $page['parent'] . '/' . $page['slug'] ) ) continue;
		if ( get_page_by_path( $page['slug'] ) ) continue;
		$parent_obj = get_page_by_path( $page['parent'] );
		wp_insert_post( array(
			'post_title'    => $page['title'],
			'post_name'     => $page['slug'],
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_content'  => '',
			'post_parent'   => $parent_obj ? $parent_obj->ID : 0,
			'menu_order'    => $page['order'],
			'page_template' => $page['template'] ?: 'default',
		) );
	}
	update_option( 'moondental_pages_v319_seeded', '1' );
}
// wp_loaded — WP 핵심이 로드된 후, 출력 전. 프론트엔드 방문 시에도 한 번 실행.
add_action( 'wp_loaded', 'moondental_auto_create_pages_once' );

/**
 * 글 자동 분류 — 본문/제목 키워드로 '치아이야기' 또는 '공지사항'으로 재할당.
 *  치료·임상 키워드가 포함되면 → 치아이야기 (slug 'dental-stories')
 *  그 외 → 공지사항 (slug 'notice')
 *
 * @return array ['story'=>int, 'notice'=>int, 'skipped'=>int]
 */
function moondental_recategorize_posts() {
	// v3.37.0 · defense in depth · 글 카테고리 이동은 편집자 이상만
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return array( 'story'=>0, 'notice'=>0, 'skipped'=>0, 'error'=>'insufficient_permissions' );
	}
	// ▶ 소식·운영 키워드 — 최우선. 하나라도 매칭되면 '문치과병원 소식'.
	//   임상 키워드가 함께 있어도 소식이 이깁니다 (MOU·연합회·협약 등은 임상글 아님).
	$news_keywords = array(
		'MOU', 'mou', '협약', '체결', '협력',
		'연합회', '협회', '협의회', '간담회', '이통장', '소상공인', '교총', '연합',
		'어린이집', '대학교 ', '학교', '교회',
		'봉사', '후원', '기부', '나눔', '캠페인', '이벤트', '행사', '축제', '걷기',
		'채용', '모집', '구인',
		'수상', '선정', '인증', '지정', '인정', '등재', '협력병원', '지정병원',
		'명절', '새해', '신년', '설날', '추석', '한가위', '연말', '연시', '크리스마스',
		'휴진', '진료시간 변경', '운영시간', '시간 변경', '시간변경', '공지', '안내드립니다', '안내드립니다',
		'개원', '리뉴얼', '확장', '이전', '오픈',
		'특별진료', '연장진료',
		'야간 진료', '야간진료', '진료 안내', '진료안내', '진료시간 안내',
		'진료 없습니다', '진료가 없습니다', '진료 없음', '쉽니다',
		'증명서', '제증명',
		'코로나', '백신', '방역',
		// 날짜 안내 패턴 — 'X월 X일 (요일)' 형식이면 99% 휴진/진료 안내
		'(월)', '(화)', '(수)', '(목)', '(금)', '(토)', '(일)',
	);

	// 임상·치료 관련 키워드 — 소식 키워드가 없을 때만 '치아이야기'로 분류
	$clinical_keywords = array(
		'임상', '치료', '시술', '수술', '진료',
		'임플란트', '교정', '투명교정', '슈어스마일', '브라켓',
		'라미네이트', '미백', '치아미백', '잇몸미백',
		'발치', '사랑니', '매복',
		'신경치료', '근관', '재근관',
		'잇몸', '치주', '치주염', '치주치료', '치은염', '잇몸염',
		'충치', '레진', '인레이', '온레이',
		'보철', '크라운', '브릿지', '틀니',
		'턱관절', '이갈이', '이악물기',
		'CBCT', '디지털', '가이드', '구강스캐너', '스캐너',
		'환자', '케이스', '증례', '사례',
		'진단', '검진', '정기검진',
		'자연치아', '보존',
		'예방', '스케일링', '에어플로우', '불소', '실란트',
		'통증', '시린', '시린이',
		'내원', '상담', '예약 진료',
		'덴탈SPA', '덴탈스파', 'SPA',
		'PDRN', '골이식', '뼈이식', '상악동',
		'심미', '거미스마일', '반점치', '왜소치',
		'문은수', '문지현', '이수연', '이승주', '권혜진', '이창률', '이영일', '김세일', '정석형', // 의료진 이름
	);

	// 공지사항 카테고리 (slug 'notice' 우선, 없으면 한글 이름 시도)
	$notice_cat = get_term_by( 'slug', 'notice', 'category' );
	if ( ! $notice_cat ) {
		$notice_cat = get_term_by( 'name', '문치과병원 소식', 'category' );
	}
	if ( ! $notice_cat ) {
		$notice_cat = get_term_by( 'name', '공지사항', 'category' );
	}
	if ( ! $notice_cat ) {
		$id = wp_insert_term( '문치과병원 소식', 'category', array( 'slug' => 'notice' ) );
		if ( ! is_wp_error( $id ) ) $notice_cat = get_term( $id['term_id'], 'category' );
	}

	// 치아이야기 카테고리
	$story_cat = get_term_by( 'slug', 'dental-stories', 'category' );
	if ( ! $story_cat ) {
		$story_cat = get_term_by( 'name', '문치과병원 치아이야기', 'category' );
	}
	if ( ! $story_cat ) {
		$story_cat = get_term_by( 'name', '치아이야기', 'category' );
	}
	if ( ! $story_cat ) {
		$story_cat = get_term_by( 'name', '치과이야기', 'category' );
	}
	if ( ! $story_cat ) {
		$id = wp_insert_term( '문치과병원 치아이야기', 'category', array( 'slug' => 'dental-stories' ) );
		if ( ! is_wp_error( $id ) ) $story_cat = get_term( $id['term_id'], 'category' );
	}

	if ( ! $notice_cat || ! $story_cat ) {
		return array( 'story' => 0, 'notice' => 0, 'skipped' => 0 );
	}

	$posts = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'numberposts'    => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	$story_count  = 0;
	$notice_count = 0;
	$skipped      = 0;

	foreach ( $posts as $post ) {
		$haystack = $post->post_title . "\n" . wp_strip_all_tags( $post->post_content );

		// 1순위: 소식·운영 키워드 → 소식
		$is_news = false;
		foreach ( $news_keywords as $kw ) {
			if ( mb_stripos( $haystack, $kw ) !== false ) { $is_news = true; break; }
		}

		// 2순위: 임상 키워드 → 치아이야기 (소식 키워드가 없을 때만)
		$is_clinical = false;
		if ( ! $is_news ) {
			foreach ( $clinical_keywords as $kw ) {
				if ( mb_stripos( $haystack, $kw ) !== false ) { $is_clinical = true; break; }
			}
		}

		if ( $is_news ) {
			$target_cat = $notice_cat->term_id;
		} elseif ( $is_clinical ) {
			$target_cat = $story_cat->term_id;
		} else {
			$target_cat = $notice_cat->term_id; // 기본값: 소식
		}

		$result = wp_set_post_categories( $post->ID, array( (int) $target_cat ), false );

		if ( is_wp_error( $result ) ) {
			$skipped++;
		} elseif ( $target_cat === $story_cat->term_id ) {
			$story_count++;
		} else {
			$notice_count++;
		}
	}

	return array(
		'story'   => $story_count,
		'notice'  => $notice_count,
		'skipped' => $skipped,
	);
}

/**
 * 헤더 주 메뉴 구조 데이터 — 사용자 스크린샷 기준 (수정은 여기서)
 */
function moondental_primary_menu_data() {
	$home = home_url( '/' );
	return array(
		array( 'label' => '임플란트센터',     'url' => $home . '임플란트-센터/',     'children' => array() ),
		array( 'label' => '교정센터',         'url' => $home . '투명교정-센터/',     'children' => array(
			array( 'label' => '슈어스마일 투명교정', 'url' => $home . '슈어스마일-투명교정/' ),
			array( 'label' => '브라켓 치아교정',     'url' => $home . '브라켓-치아교정/' ),
		) ),
		array( 'label' => '스마일디자인센터', 'url' => $home . '스마일디자인센터/',  'children' => array() ),
		array( 'label' => '자연치아살리기',   'url' => $home . '자연치아-살리기/',   'children' => array(
			array( 'label' => '충치치료',   'url' => $home . '자연치아-살리기/#cavity' ),
			array( 'label' => '치수복조술', 'url' => $home . '자연치아-살리기/#pulpcap' ),
			array( 'label' => '신경치료',   'url' => $home . '자연치아-살리기/#endo' ),
			array( 'label' => '잇몸치료',   'url' => $home . '자연치아-살리기/#perio' ),
		) ),
		array( 'label' => '진료과',           'url' => '#',           'children' => array(
			array( 'label' => '턱관절클리닉',    'url' => $home . '턱관절-클리닉/' ),
			array( 'label' => '이갈이·이악물기','url' => $home . '턱관절-클리닉/' ),
			array( 'label' => '사랑니',          'url' => $home . '사랑니-발치/' ),
			array( 'label' => '소아치과',        'url' => $home . '소아치과/' ),
			array( 'label' => '예방클리닉',      'url' => $home . '예방클리닉/' ),
		) ),
		array( 'label' => '의료진',           'url' => $home . '의료진/',             'children' => array() ),
		array( 'label' => '비용안내',         'url' => $home . '비용-안내/',          'children' => array() ),
		array( 'label' => '병원안내',         'url' => '#',           'children' => array(
			array( 'label' => '오시는길·진료시간', 'url' => $home . '오시는-길/' ),
			array( 'label' => '30여년의 역사',     'url' => $home . '역사/' ),
			array( 'label' => '기술력/시설',        'url' => $home . '기술력-시설/' ),
			array( 'label' => '병원소식',          'url' => $home . '소식/' ),
			array( 'label' => '치과 백과사전',          'url' => $home . '치과사전/' ),
			array( 'label' => '상시채용',          'url' => $home . '상시채용/' ),
		) ),
	);
}

/**
 * 주 메뉴 HTML 렌더 — UL.md-nav 형태로 출력 (현재 페이지 강조 포함).
 *  v3.34.3 · 상위 메뉴 클릭 비활성 항목에 md-nav-nolink 클래스 자동 추가.
 */
/**
 * v3.44.40 · DB 기반 페이지 캐시 (transient) · Cafe24 파일 시스템 이슈 회피
 *
 * 배경:
 * - v3.44.33~39 파일 기반 캐시 · 파일이 저장되지만 매번 삭제됨
 * - Cafe24 호스팅에서 wp-content 파일이 요청 사이에 사라지는 현상
 * - Wordfence 스캔 또는 호스팅 자동 정리로 추정
 *
 * 해결: wp_options (DB) 에 캐시 저장. DB는 절대 자동 삭제되지 않음.
 * TTL: 6시간
 */
function moondental_pagecache_key() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
	$lang = 'ko';
	if ( preg_match( '#^/(en|ja|zh|vi|ru|mn)(/|$)#', $uri, $m ) ) {
		$lang = $m[1];
	}
	// 모바일·데스크탑 구분 (같은 URL이라도 다른 반응형 이미지)
	$mobile = ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) ? 'm' : 'd';
	return md5( $uri . '|' . $lang . '|' . $mobile );
}
function moondental_pagecache_skip() {
	// 관리자·로그인·AJAX·REST·CRON 스킵
	if ( is_admin() ) return true;
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return true;
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) return true;
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return true;
	if ( defined( 'WP_CLI' ) && WP_CLI ) return true;
	// GET 만 · POST/PUT/DELETE 스킵
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && strtoupper( $_SERVER['REQUEST_METHOD'] ) !== 'GET' ) return true;
	// query string 있으면 스킵 (검색·페이지네이션은 캐시 안 함 · 안전)
	if ( ! empty( $_GET ) ) return true;
	// 로그인 사용자 스킵 (Customizer 미리보기·편집 상태 등)
	if ( is_user_logged_in() ) return true;
	// 커스터마이저 preview 스킵
	if ( isset( $_GET['customize_changeset_uuid'] ) ) return true;
	// 사용자가 방금 댓글 남긴 상태 스킵
	foreach ( $_COOKIE as $k => $v ) {
		if ( strpos( $k, 'wordpress_logged_in' ) === 0 ) return true;
		if ( strpos( $k, 'wp-postpass_' )         === 0 ) return true;
		if ( strpos( $k, 'comment_author_' )      === 0 ) return true;
	}
	return false;
}

/* v3.44.41 · 캐시 서빙 · 디버그 코멘트 제거 (LiteSpeed Cache 충돌 방지) */
function moondental_pagecache_serve() {
	if ( moondental_pagecache_skip() ) return;
	$key = 'md_pcache_' . moondental_pagecache_key();
	$cached = get_transient( $key );
	if ( ! $cached || ! is_string( $cached ) || strlen( $cached ) < 500 ) return;
	header( 'X-MD-Cache: hit' );
	header( 'Cache-Control: public, max-age=1800' );
	echo $cached;
	exit;
}
add_action( 'template_redirect', 'moondental_pagecache_serve', 0 );

/* 페이지 렌더 후 · 캐시에 저장 */
function moondental_pagecache_save_start() {
	if ( moondental_pagecache_skip() ) return;
	ob_start( 'moondental_pagecache_save' );
}
add_action( 'template_redirect', 'moondental_pagecache_save_start', 999 );

function moondental_pagecache_save( $html ) {
	if ( http_response_code() !== 200 ) return $html;
	if ( strlen( $html ) < 500 ) return $html;
	if ( strpos( $html, '<html' ) === false && strpos( $html, '<!doctype' ) === false && strpos( $html, '<!DOCTYPE' ) === false ) return $html;
	$key = 'md_pcache_' . moondental_pagecache_key();
	set_transient( $key, $html, 6 * HOUR_IN_SECONDS );
	return $html;
}

/* v3.44.40 · 캐시 무효화 · 로그인/관리자만 · transient 삭제 */
function moondental_pagecache_flush() {
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && strtoupper( $_SERVER['REQUEST_METHOD'] ) === 'GET' ) return;
	if ( ! is_admin() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) return;
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_pcache\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_pcache\\_%'" );
}

/* v3.44.45 · 배포 버전 변경 시 · 캐시 강제 자동 무효화 (1회) */
/* v3.44.58 · 추가 · 배포 버전 변경 시 · 재작성 규칙(permalinks) 도 함께 flush
             (Cafe24에서 페이지 URL이 404 나는 문제 방지) */
add_action( 'wp_loaded', function() {
	if ( get_option( 'md_cache_version' ) === MOONDENTAL_VERSION ) return;
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_pcache\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_pcache\\_%'" );
	// v3.44.58 · rewrite rules 완전 재생성 (한글 슬러그 페이지 404 방지)
	if ( function_exists( 'flush_rewrite_rules' ) ) {
		flush_rewrite_rules( false );
	}
	// v3.44.98 · WP Super Cache 도 함께 자동 무효화 (배포 후 페이지 즉시 반영)
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	} elseif ( function_exists( 'wpsc_delete_files' ) ) {
		wpsc_delete_files( ABSPATH );
	}
	update_option( 'md_cache_version', MOONDENTAL_VERSION );
}, 5 );
add_action( 'save_post',        'moondental_pagecache_flush' );
add_action( 'deleted_post',     'moondental_pagecache_flush' );
add_action( 'trashed_post',     'moondental_pagecache_flush' );
add_action( 'comment_post',     'moondental_pagecache_flush' );
add_action( 'customize_save_after', 'moondental_pagecache_flush' );
add_action( 'switch_theme',     'moondental_pagecache_flush' );
add_action( 'wp_update_nav_menu','moondental_pagecache_flush' );

/**
 * v3.44.31 · 이미지 lazy loading + decoding=async 전면 자동 적용
 * 콘텐츠·위젯·네비게이션 등 모든 <img> 태그에 · loading=lazy, decoding=async 없으면 자동 삽입.
 * 이미 지정된 태그는 그대로 존중.
 */
function moondental_auto_lazy_images( $html ) {
	if ( is_admin() || empty( $html ) ) return $html;
	// v3.44.41 · 성능 · img 태그 없으면 즉시 리턴 (regex 스킵)
	if ( strpos( $html, '<img' ) === false ) return $html;
	// loading 속성 없는 <img> 태그에 lazy 추가
	$html = preg_replace_callback( '#<img\b(?![^>]*\bloading=)([^>]*?)>#i', function( $m ) {
		return '<img loading="lazy" decoding="async"' . $m[1] . '>';
	}, $html );
	return $html;
}
add_filter( 'the_content',        'moondental_auto_lazy_images', 20 );
add_filter( 'post_thumbnail_html','moondental_auto_lazy_images', 20 );
add_filter( 'widget_text_content','moondental_auto_lazy_images', 20 );
add_filter( 'wp_get_attachment_image', 'moondental_auto_lazy_images', 20 );

/**
 * v3.44.30 · 서비스 페이지 히어로 이미지·스탯 매핑
 * 슬러그별 대표 이미지 + 임팩트 스탯 3개 반환. 페이지 콘텐츠와 무관하게 항상 노출.
 */
function moondental_service_visual( $slug ) {
	$uri = defined( 'MOONDENTAL_URI' ) ? MOONDENTAL_URI : '';
	$map = array(
		'임플란트-센터' => array(
			'image' => $uri . '/assets/images/services/implant-navigation.jpg',
			'alt'   => 'CBCT 3D 시뮬레이션 기반 네비게이션 임플란트',
			'stats' => array(
				array( 'value' => '30+', 'unit' => '년', 'label' => '임플란트 임상 경험' ),
				array( 'value' => '±0.5', 'unit' => 'mm', 'label' => '가이드 수술 정확도' ),
				array( 'value' => '10F',  'unit' => '',   'label' => '임플란트 전용층' ),
			),
			'headline' => '천안·아산 임플란트, 30여년 임상으로 지키는 안정감',
			'sub'      => 'CBCT 3D 정밀 진단 · 네비게이션 가이드 수술 · 발치부터 평생 관리까지 한 곳에서',
		),
		'투명교정-센터' => array(
			'image' => '',
			'alt'   => '',
			'stats' => array(
				array( 'value' => '11F',   'unit' => '',   'label' => '교정 전용층' ),
				array( 'value' => '중부권', 'unit' => '',   'label' => '슈어스마일 센터병원' ),
				array( 'value' => '2가지', 'unit' => '',   'label' => '투명·브라켓 교정' ),
			),
			'headline' => '천안·아산 교정센터 · 라이프스타일 맞춤 진료',
			'sub'      => '슈어스마일 투명교정과 브라켓 치아교정 · 치과교정과·인정의가 환자분께 가장 적합한 방식을 제안합니다',
		),
		'슈어스마일-투명교정' => array(
			'image' => $uri . '/assets/images/services/suresmile-hero.jpg',
			'alt'   => 'SureSmile 투명교정 · Dentsply Sirona',
			'stats' => array(
				array( 'value' => '중부권', 'unit' => '',   'label' => '슈어스마일 센터병원' ),
				array( 'value' => '0.1',    'unit' => 'mm', 'label' => '치료 계획 정밀도' ),
				array( 'value' => 'FDA',   'unit' => '',   'label' => '미국 승인 시스템' ),
			),
			'headline' => '슈어스마일 투명교정, 미국 FDA 승인 · 중부권 센터병원',
			'sub'      => '3D 시뮬레이션으로 최종 치열 미리 확인 · 프라임스캐너 정밀 스캔 · 재교정 걱정 없이',
		),
		'브라켓-치아교정' => array(
			'image' => '',
			'alt'   => '',
			'stats' => array(
				array( 'value' => '설측·일반', 'unit' => '', 'label' => '브라켓 옵션' ),
				array( 'value' => '±0.1', 'unit' => 'mm', 'label' => '치아 이동 정밀도' ),
				array( 'value' => '진료팀', 'unit' => '', 'label' => '치과교정과 인정의' ),
			),
			'headline' => '브라켓 치아교정 · 검증된 표준 방식으로 정확한 결과를',
			'sub'      => '설측교정(안쪽 브라켓)·메탈·세라믹·자가결찰 브라켓·소아 성장기 교정까지 케이스에 맞게 선택',
		),
		'자연치아-살리기' => array(
			'image' => '',
			'alt'   => '',
			'stats' => array(
				array( 'value' => '보존', 'unit' => '', 'label' => '정밀 진단' ),
				array( 'value' => '30+',  'unit' => '년', 'label' => '자연치아 살리기 경험' ),
				array( 'value' => '재근관', 'unit' => '', 'label' => '치료 가능' ),
			),
			'headline' => '발치 권유받으셨나요? 한 번 더 살펴보세요',
			'sub'      => '보존과·치주과 진료팀이 신경치료·재근관·치주치료로 자연치아를 살립니다',
		),
	);
	return $map[ $slug ] ?? null;
}

/**
 * v3.44.22 · 메뉴 라벨 → 번역키 매핑 (다국어 지원)
 * 한글 라벨을 안정적인 키로 매핑 · md_content 를 통해 파일 번역·API 번역·Customizer override 순서로 조회
 */
function moondental_menu_label_key( $label ) {
	static $map = array(
		'임플란트센터'         => 'menu_impl',
		'교정센터'             => 'menu_ortho',
		'스마일디자인센터'     => 'menu_smile',
		'자연치아살리기'       => 'menu_preserve',
		'자연치아 살리기'      => 'menu_preserve',
		'진료과'               => 'menu_dept',
		'의료진'               => 'menu_doctors',
		'비용안내'             => 'menu_pricing',
		'병원안내'             => 'menu_about',
		'병원 안내'            => 'menu_about',
		'충치치료'             => 'menu_cavity',
		'신경치료'             => 'menu_endo',
		'잇몸치료'             => 'menu_perio',
		'턱관절클리닉'         => 'menu_jaw',
		'이갈이·이악물기'      => 'menu_bruxism',
		'사랑니'               => 'menu_wisdom',
		'소아치과'             => 'menu_pediatric',
		'예방클리닉'           => 'menu_prevention',
		'슈어스마일 투명교정'  => 'menu_suresmile',
		'브라켓 치아교정'      => 'menu_braces',
		'장치교정'             => 'menu_braces', // legacy alias
		'오시는길·진료시간'    => 'menu_directions',
		'30여년의 역사'        => 'menu_history',
		'기술력/시설'          => 'menu_facility',
		'병원소식'             => 'menu_news',
		'치과사전' => 'menu_encyclopedia',
		'치과 백과사전' => 'menu_encyclopedia',
		'상시채용'             => 'menu_recruit',
	);
	return $map[ $label ] ?? null;
}

function moondental_render_primary_menu() {
	$items = moondental_primary_menu_data();
	$current = trailingslashit( home_url( add_query_arg( null, null ) ) );

	// 클릭 비활성 상위 메뉴 라벨 (nav_menu_css_class 필터와 동기화)
	$nolink_titles = function_exists( 'moondental_nolink_parent_menu_titles' )
		? moondental_nolink_parent_menu_titles()
		: array( '병원안내', '병원 안내', '자연치아살리기', '자연치아 살리기', '진료과', '교정센터' );

	echo '<ul class="md-nav">';
	foreach ( $items as $item ) {
		$has_kids = ! empty( $item['children'] );
		$classes  = array( 'menu-item' );
		if ( $has_kids ) $classes[] = 'menu-item-has-children';
		if ( in_array( trim( (string) $item['label'] ), $nolink_titles, true ) ) {
			$classes[] = 'md-nav-nolink';
		}
		if ( untrailingslashit( $item['url'] ) === untrailingslashit( $current ) ) {
			$classes[] = 'current-menu-item';
		}
		// v3.44.22 · 라벨 번역 (파일 → API → 원본)
		$_key   = function_exists( 'moondental_menu_label_key' ) ? moondental_menu_label_key( $item['label'] ) : null;
		$_label = ( $_key && function_exists( 'md_content' ) ) ? md_content( $_key, $item['label'] ) : $item['label'];
		printf(
			'<li class="%s"><a href="%s">%s</a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( $item['url'] ),
			esc_html( $_label )
		);
		if ( $has_kids ) {
			echo '<ul class="sub-menu">';
			foreach ( $item['children'] as $kid ) {
				$_kkey   = function_exists( 'moondental_menu_label_key' ) ? moondental_menu_label_key( $kid['label'] ) : null;
				$_klabel = ( $_kkey && function_exists( 'md_content' ) ) ? md_content( $_kkey, $kid['label'] ) : $kid['label'];
				printf(
					'<li class="menu-item"><a href="%s">%s</a></li>',
					esc_url( $kid['url'] ),
					esc_html( $_klabel )
				);
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * fallback_cb (이전 호환).
 */
function moondental_nav_fallback() {
	moondental_render_primary_menu();
}

/**
 * 강제 출력 — wp_nav_menu 가 primary location을 호출하면 DB에 있는 메뉴와
 * 상관없이 항상 우리 구조를 출력. 사용자가 admin UI에서 메뉴를 만들거나
 * '주 메뉴 자동 설정' 버튼을 누르지 않아도 즉시 새 구조가 헤더에 보임.
 */
function moondental_force_primary_menu( $output, $args ) {
	if ( empty( $args->theme_location ) || $args->theme_location !== 'primary' ) {
		return $output;
	}
	ob_start();
	moondental_render_primary_menu();
	$inner = ob_get_clean();

	// wp_nav_menu 기본 wrapper 모방
	$container       = ! empty( $args->container )       ? $args->container       : 'div';
	$container_class = ! empty( $args->container_class ) ? $args->container_class : '';
	$container_id    = ! empty( $args->container_id )    ? $args->container_id    : '';

	if ( $args->container === false ) {
		return $inner;
	}
	$attrs = '';
	if ( $container_class ) $attrs .= ' class="' . esc_attr( $container_class ) . '"';
	if ( $container_id )    $attrs .= ' id="'    . esc_attr( $container_id ) . '"';
	return '<' . tag_escape( $container ) . $attrs . '>' . $inner . '</' . tag_escape( $container ) . '>';
}
add_filter( 'pre_wp_nav_menu', 'moondental_force_primary_menu', 10, 2 );

/**
 * 푸터 메뉴 fallback.
 */
function moondental_footer_menu_fallback() {
	$items = array(
		'의료진'           => '/의료진/',
		'30여년의 발자취'  => '/역사/',
		'기술력/시설'      => '/기술력-시설/',
		'오시는 길'        => '/오시는-길/',
		'병원소식'         => '/소식/',
		'상시채용'         => '/상시채용/',
		'개인정보처리방침' => '/개인정보처리방침/',
	);
	echo '<ul>';
	foreach ( $items as $label => $path ) {
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( home_url( $path ) ),
			esc_html( $label )
		);
	}
	echo '</ul>';
}


/* ============================================================
 * 9. Helper: 진료영역 데이터 (5개 종합 진료)
 * ========================================================== */
/**
 * 홈/메뉴/구조에서 공통으로 사용하는 진료영역 정의.
 * slug → 페이지를 만들고 같은 slug로 두면 자동 링크 연결.
 */
function moondental_get_services() {
	// 슬러그는 사용자가 만든 실제 페이지의 한글 슬러그(URL의 한글 부분)와 일치.
	// v3.33.0 · icon: prefix 커스텀 SVG 사용 (inc/icons.php). 이모지로도 오버라이드 가능.
	return array(
		array(
			'slug'  => '임플란트-센터',
			'title' => '천안·아산 임플란트',
			'icon'  => 'icon:implant',
			'desc'  => '천안 만남로 10F 임플란트센터 — 단일·다수·전악 임플란트까지, CBCT 디지털 가이드 수술과 보철 진료과 협진.',
		),
		array(
			'slug'  => '투명교정-센터',
			'title' => '교정센터',
			'icon'  => 'icon:ortho',
			'desc'  => '천안 만남로 11F 교정과 — 슈어스마일(SureSmile) AI 투명교정과 브라켓 치아교정 · 치과교정과·인정의 라이프스타일 맞춤 진료.',
		),
		array(
			'slug'  => '자연치아-살리기',
			'title' => '천안·아산 자연치아 살리기',
			'icon'  => 'icon:preserve',
			'desc'  => '천안·아산 신경치료·재근관치료·치주치료. 보존과 진료팀의 정밀 진료 — 발치보다 보존을 먼저 고민합니다.',
		),
		array(
			'slug'  => '턱관절-클리닉',
			'title' => '천안·아산 턱관절 클리닉',
			'icon'  => 'icon:jaw',
			'desc'  => '천안·아산 턱관절 통증·소리·개구장애 — 대한턱관절교합학회 이사진의 전문 진료. 보존적 치료 우선.',
		),
		array(
			'slug'  => '사랑니-발치',
			'title' => '천안·아산 사랑니 발치',
			'icon'  => 'icon:wisdom',
			'desc'  => '천안·아산 매복 사랑니까지 — CBCT 3D 정밀 진단으로 구강악안면외과 전문 의료진이 안전하게 발치합니다.',
		),
		array(
			'slug'  => '심미치료',
			'title' => '천안·아산 라미네이트·미백',
			'icon'  => 'icon:aesthetic',
			'desc'  => '천안·아산 라미네이트·치아미백·올세라믹 — 최소 삭제 보존적 접근으로 자연스러운 미소를 디자인합니다.',
		),
		array(
			'slug'  => '소아치과',
			'title' => '천안·아산 소아치과',
			'icon'  => 'icon:pediatric',
			'desc'  => '천안·아산 어린이 첫 치과 경험부터 정기 검진·예방·1차 교정까지. 평생 구강 건강의 시작.',
		),
		// v3.44.44 · 교정센터 하위 세부 진료 · 홈 카드에는 표시 안 함 (hidden=true)
		array(
			'slug'  => '슈어스마일-투명교정',
			'title' => '슈어스마일 투명교정',
			'icon'  => 'icon:ortho',
			'desc'  => 'Dentsply Sirona 슈어스마일 AI 투명교정 · 미국 FDA 승인 · 3D 시뮬레이션 · 프라임스캐너 정밀 스캔.',
			'hidden' => true,
		),
		array(
			'slug'  => '브라켓-치아교정',
			'title' => '브라켓 치아교정',
			'icon'  => 'icon:ortho',
			'desc'  => '설측교정·메탈·세라믹·자가결찰 브라켓·소아 성장기 교정까지 · 케이스 맞춤 표준 교정.',
			'hidden' => true,
		),
	);
}


/**
 * 진료 상위 카테고리 5개 (주 메뉴 구조 기반) — '다른 진료 영역 보기' 섹션 전용.
 * 개별 서비스 페이지 하단에서 다른 카테고리로 이동을 유도.
 *
 * @return array[] slug/title/icon/desc/url 를 담은 배열
 */
function moondental_get_service_areas() {
	$home = home_url( '/' );
	return array(
		array(
			'slug'  => '임플란트-센터',
			'title' => '임플란트센터',
			'icon'  => 'icon:implant',
			'desc'  => '진단·수술·보철·평생 관리까지 — 임플란트 전 과정을 원내 협진 체계로.',
			'url'   => $home . '임플란트-센터/',
		),
		array(
			'slug'  => '투명교정-센터',
			'title' => '교정센터',
			'icon'  => 'icon:ortho',
			'desc'  => '슈어스마일(SureSmile) AI 투명교정 + 치과교정과·인정의 진료.',
			'url'   => $home . '투명교정-센터/',
		),
		array(
			'slug'  => '스마일디자인센터',
			'title' => '스마일디자인센터',
			'icon'  => 'icon:aesthetic',
			'desc'  => '라미네이트·치아미백·심미보철 — 최소 삭제 보존적 심미 치료.',
			'url'   => $home . '스마일디자인센터/',
		),
		array(
			'slug'  => '자연치아-살리기',
			'title' => '자연치아 살리기',
			'icon'  => 'icon:preserve',
			'desc'  => '충치치료·신경치료·잇몸치료 — 발치보다 보존을 먼저 고민합니다.',
			'url'   => $home . '자연치아-살리기/',
		),
		array(
			'slug'  => '진료항목',
			'title' => '진료과',
			'icon'  => 'icon:general',
			'desc'  => '턱관절·이갈이·사랑니·소아치과·예방클리닉 — 전 진료과 협진 체계.',
			'url'   => '#',
		),
	);
}

/**
 * 현재 서비스 페이지 슬러그를 상위 카테고리 슬러그로 매핑.
 * '다른 진료 영역' 렌더 시 현재 페이지가 속한 카테고리 카드를 제외하는 데 사용.
 */
function moondental_service_slug_to_area( $slug ) {
	$map = array(
		'임플란트-센터'    => '임플란트-센터',
		'투명교정-센터'    => '투명교정-센터',
		'스마일디자인센터' => '스마일디자인센터',
		'심미치료'         => '스마일디자인센터',
		'자연치아-살리기'  => '자연치아-살리기',
		'턱관절-클리닉'    => '진료항목',
		'사랑니-발치'      => '진료항목',
		'소아치과'         => '진료항목',
		'예방클리닉'       => '진료항목',
	);
	return $map[ $slug ] ?? $slug;
}


/* ============================================================
 * 10. Helper: 의료진 팀 (10명 — 이사장 + 원장 + 교수)
 * ========================================================== */
/**
 * 의료진 데이터. 추후 Custom Post Type "doctor"로 이관 가능.
 *
 * @return array[] role 그룹별 [ 'group' => label, 'members' => [name, …] ]
 */
function moondental_get_team() {
	/*
	 * 사진은 assets/images/doctors/doctor-01.jpg ~ doctor-09.jpg.
	 * 약력은 moondental.co.kr/hospital/p_4_*.php 의 의료진 소개 이미지에서 OCR.
	 *
	 * bio 는 줄 단위 배열 (각 항목이 한 줄의 학력/경력).
	 * philosophy 는 진료 철학 한 문장 (선택).
	 */
	return array(
		/* ─── 대표 병원장 ─── */
		array(
			'group'   => '대표 병원장',
			'members' => array(
				array(
					'name'       => '문은수',
					'role'       => '대표 병원장',
					'photo'      => 'doctor-04.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 0,
					'philosophy' => '환자를 가족처럼 생각하는 마음, 그것이 문치과의 진료 철학입니다.',
					'bio'        => array(
						'한아의료재단 이사장',
						'(사)블루문드림 이사장',
						'단국대 치과대학 겸임교수',
						'서울삼성의료원·서울대·이화여대·나사렛대 외래교수',
						'한국 사랑의 집짓기 해비타트 충남·세종지회 이사',
						'대전지방검찰청 천안지청 천안·아산 범죄피해자 지원센터 이사장',
						'사회복지공동모금회 충남 아너소사이어티 클럽 초대회장',
						'천안시복지재단 초대·2대 이사장',
						'대한적십자사 충청남도지사 회장',
						'충남AI포럼 회장',
						'2008-2009 국제로타리 3620지구 총재 (세계 1등 지구 수상)',
						'2017-2019 국제로타리 국제이사',
					),
				),
			),
		),

		/* ─── 보철·보존 ─── */
		array(
			'group'   => '보철·보존',
			'members' => array(
				array(
					'name'       => '이승주',
					'role'       => '원장',
					'photo'      => 'doctor-07.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 5,
					'philosophy' => '최선을 다하여 환자를 가족처럼 생각하며 진료에 임하겠습니다.',
					'bio'        => array(
						'단국대학교치과대학 치의학과 졸업',
						'대한치주과학회 정회원',
						'대한보철학회 정회원',
						'한아임플란트보철연구소 연구위원',
					),
				),
				array(
					'name'       => '이수연',
					'role'       => '원장',
					'photo'      => 'doctor-08.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 3,
					'philosophy' => '진실된 마음으로 환자분들과 함께하는 의료서비스를 제공하겠습니다.',
					'bio'        => array(
						'치과 보철과·통합치의학 진료',
						'조선대학교 치의학전문대학원 석사',
						'조선대학교 치의학전문대학원 박사',
						'조선대학교 치과병원 인턴',
						'조선대학교 치과병원 레지던트',
						'Harvard School advanced education in periodontics and prosthodontics 수료',
					),
				),
				array(
					'name'       => '권혜진',
					'role'       => '원장',
					'photo'      => 'doctor-02.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 4,
					'philosophy' => '기본에 충실하되 새로운 변화에 맞춰가며, 환자분을 가족처럼 생각하는 따뜻한 마음으로 진료에 임하겠습니다.',
					'bio'        => array(
						'보존과 진료팀',
						'단국대학교 치과대학 졸업',
						'단국대학교 치과대학 보존과 석사',
						'단국대학교 치과대학 보존과 레지던트 수료',
						'대한 근관학회 정회원',
						'대한 보존학회 정회원',
					),
				),
			),
		),

		/* ─── 임플란트·외과 ─── */
		array(
			'group'   => '임플란트·외과',
			'members' => array(
				array(
					'name'       => '문지현',
					'role'       => '원장',
					'photo'      => 'doctor-05.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 2,
					'philosophy' => '구강건강 증진을 통해 환자분들의 삶이 회복되는 과정을 함께 하고 싶습니다. 최선을 다해 진료하겠습니다.',
					'bio'        => array(
						'서울대학교 치의학대학원 졸업',
						'단국대학교 구강악안면외과 박사 과정',
						'포항공과대학교 신소재공학과 석사 졸업',
						'포항공과대학교 신소재공학과 학사 졸업',
						'대한구강악안면임플란트학회 정회원',
						'미국 UCSF 치과대학 교정과 임상연수',
						'미국 UPENN 치과대학 근관치료학 연수',
						'대한치과이식임플란트학회아카데미 수료',
						'턱관절장애교육연구회 고급과정 수료',
						'대한여성치과의사회 미래여성인재상 수상',
						'한국금속재료학회 우수논문상 수상',
						'한아임플란트보철연구소 연구위원',
					),
				),
				array(
					'name'       => '이창률',
					'role'       => '원장',
					'photo'      => 'doctor-01.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 5,
					'philosophy' => 'For a lifelong smile. 환자 한 분 한 분을 가족처럼 생각하며, 밝고 편안한 웃음을 위한 진료에 최선을 다하겠습니다.',
					'bio'        => array(
						'미국 UCLA 생화학 학사 졸업',
						'미국 UCLA 구강생물학 석사 졸업',
						'미국 UCLA 구강암센터 연구원',
						'미국 UCSF 치과대학 졸업',
						'미국 UCSF 치과대학 교정과 임상연수',
						'서울대학교 치의학 박사과정',
						'미국 & 한국 치과의사 면허 보유',
						'미국 치과의사 협회(ADA) 정회원',
						'대한턱관절교합학회 이사',
						'대한심미치과학회 인정의',
						'대한국제치과의사회 학술위원',
						'한아임플란트보철연구소 연구위원',
					),
				),
			),
		),

		/* ─── 교정·치주 ─── */
		array(
			'group'   => '교정·치주',
			'members' => array(
				array(
					'name'       => '이영일',
					'role'       => '원장',
					'photo'      => 'doctor-09.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 2,
					'philosophy' => '환자를 가족처럼 생각하는 마음, 그것이 문치과의 진료 철학입니다.',
					'bio'        => array(
						'단국대학교 치과대학 졸업',
						'단국대 치과대학원 치의학과 석사',
						'단국대 치과대학원 치의학과 박사',
						'단국대 치과부속병원 교정과 인턴, 레지던트 수료',
						'치과교정과',
						'치과교정과 인정의',
						'대한치과교정학회 정회원',
					),
				),
				array(
					'name'       => '김세일',
					'role'       => '원장',
					'photo'      => 'doctor-03.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => -2,
					'philosophy' => '건강한 치아는 건강한 일상의 시작입니다. 세밀한 진단과 진료로 환자분들의 건강한 하루를 책임지겠습니다.',
					'bio'        => array(
						'단국대학교 치과대학 졸업',
						'이화여자대학교 교정과 석사 수료',
						'대한 치과 교정학회 정회원',
					),
				),
				array(
					'name'       => '정석형',
					'role'       => '원장',
					'photo'      => 'doctor-06.png',
					'photo_zoom' => 1.00,
					'photo_ty'   => 2,
					'philosophy' => '저희 문치과를 방문하는 모든 분들이 밝고 건강한 웃음의 주인이 되시길 바라며 항상 최선을 다하겠습니다.',
					'bio'        => array(
						'단국대학교 치과대학 치주과 석사',
						'서울위생치과병원 치주과 인턴, 레지던트 수료',
						'대한치주과학회 인정의',
						'한아임플란트보철연구소 연구위원',
						'대한치주과학회 정회원',
					),
				),
			),
		),
	);
}

/**
 * 의료진 그룹 데이터에 Customizer 값(개인별 줌)을 덧입혀 반환.
 *
 * page-doctors.php 등 모든 호출처는 그대로 moondental_get_team() 만 사용해도
 * Customizer 값이 자동 반영되도록, 이 wrapper를 통해 후처리한다.
 */
function moondental_get_team_with_customizer() {
	$groups = moondental_get_team();
	if ( ! function_exists( 'get_theme_mod' ) ) return $groups;

	/* 의료진 이름 → Customizer key 매핑 · v3.38.6 · Customizer 편집 가능 */
	$name_to_key = array();
	$default_raw = "문은수|munes\n이승주|leesj\n이수연|leesu\n권혜진|kwon\n문지현|munji\n이창률|leech\n이영일|leeyi\n김세일|kimsi\n정석형|jeong";
	$raw = function_exists( 'md_content' )
		? md_content( 'doctor_slug_map', $default_raw )
		: $default_raw;
	foreach ( preg_split( "/\r\n|\r|\n/", (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( $line === '' || $line[0] === '#' ) continue;
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) === 2 && $parts[0] !== '' && $parts[1] !== '' ) {
			$name_to_key[ $parts[0] ] = $parts[1];
		}
	}

	/* 그룹명 Customizer override (4개) */
	if ( function_exists( 'md_content' ) ) {
		foreach ( $groups as $gi => $group ) {
			$grp_idx = $gi + 1; // 1~4
			$grp_override = md_content( "doctor_group_{$grp_idx}", '' );
			if ( $grp_override ) {
				$groups[ $gi ]['group'] = $grp_override;
			}
		}
	}

	foreach ( $groups as $gi => $group ) {
		foreach ( $group['members'] as $mi => $m ) {
			/* 사진 zoom·translateY */
			$z_fallback = isset( $m['photo_zoom'] ) ? (float) $m['photo_zoom'] : 1.00;
			$t_fallback = isset( $m['photo_ty'] )   ? (float) $m['photo_ty']   : 0.00;
			$groups[ $gi ]['members'][ $mi ]['photo_zoom'] = moondental_get_doctor_zoom( $m['name'], $z_fallback );
			$groups[ $gi ]['members'][ $mi ]['photo_ty']   = moondental_get_doctor_ty(   $m['name'], $t_fallback );

			/* 철학 + 약력 + 직책 Customizer override */
			$key = $name_to_key[ $m['name'] ] ?? null;
			if ( $key && function_exists( 'md_content' ) ) {
				$phil_override = md_content( "doctor_{$key}_philosophy", '' );
				if ( $phil_override ) {
					$groups[ $gi ]['members'][ $mi ]['philosophy'] = $phil_override;
				}
				$bio_override = md_content( "doctor_{$key}_bio", '' );
				if ( $bio_override ) {
					$bio_lines = array();
					foreach ( preg_split( "/\r\n|\r|\n/", $bio_override ) as $line ) {
						$line = trim( $line );
						if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
						$bio_lines[] = $line;
					}
					if ( $bio_lines ) {
						$groups[ $gi ]['members'][ $mi ]['bio'] = $bio_lines;
					}
				}
				$role_override = md_content( "doctor_{$key}_role", '' );
				if ( $role_override ) {
					$groups[ $gi ]['members'][ $mi ]['role'] = $role_override;
				}
			}
		}
	}
	return $groups;
}

/**
 * moondental_get_team() 호출 결과를 Customizer 값으로 덮어쓰는 필터 wrapper.
 * 외부에서 add_filter( 'moondental_team', 'moondental_get_team_with_customizer' ) 형태로 후킹 가능.
 */
add_filter( 'moondental_team_data', 'moondental_apply_team_zoom_customizer', 10, 1 );
function moondental_apply_team_zoom_customizer( $groups ) {
	foreach ( $groups as $gi => $group ) {
		foreach ( $group['members'] as $mi => $m ) {
			$fallback = isset( $m['photo_zoom'] ) ? (float) $m['photo_zoom'] : 1.00;
			$groups[ $gi ]['members'][ $mi ]['photo_zoom'] = moondental_get_doctor_zoom( $m['name'], $fallback );
		}
	}
	return $groups;
}

/**
 * 역사 페이지 사진 파일 URL 반환. 파일 없으면 false.
 *
 * @param string $filename 예: 'history-2025-08-mongol.jpg'
 * @return string|false
 */
function moondental_history_photo_url( $filename ) {
	if ( ! $filename ) return false;
	// v3.44.32 · 확장자 fallback (png → jpg 자동 전환 이후 대비)
	$base = pathinfo( $filename, PATHINFO_FILENAME );
	$exts = array( pathinfo( $filename, PATHINFO_EXTENSION ), 'jpg', 'jpeg', 'png', 'webp' );
	$exts = array_filter( array_unique( $exts ) );
	foreach ( $exts as $ext ) {
		$try_file = $base . '.' . $ext;
		$path = MOONDENTAL_DIR . '/assets/images/history/' . $try_file;
		if ( file_exists( $path ) ) {
			return MOONDENTAL_URI . '/assets/images/history/' . $try_file;
		}
	}
	return false;
}


/**
 * 의료진 사진 파일 URL 반환. 지정 확장자가 없으면 jpg/png/jpeg/webp 순으로 시도.
 *
 * @param string $filename 예: 'doctor-01.png'
 * @return string|false   URL 또는 false (실제 파일이 없을 때)
 */
function moondental_doctor_photo_url( $filename ) {
	if ( ! $filename ) return false;
	$base = pathinfo( $filename, PATHINFO_FILENAME ); // 확장자 제외
	$exts = array( pathinfo( $filename, PATHINFO_EXTENSION ), 'png', 'jpg', 'jpeg', 'webp' );
	$exts = array_filter( array_unique( $exts ) );
	foreach ( $exts as $ext ) {
		$try_file = $base . '.' . $ext;
		$path     = MOONDENTAL_DIR . '/assets/images/doctors/' . $try_file;
		if ( file_exists( $path ) ) {
			return MOONDENTAL_URI . '/assets/images/doctors/' . $try_file;
		}
	}
	return false;
}

/**
 * 네이버 블로그 RSS 피드를 가져와 정리된 배열로 반환.
 * 1시간 transient 캐싱 (네이버에 부담 X + 새 글 1시간 이내 반영).
 *
 * @param int  $limit     반환할 최대 개수 (기본 20)
 * @param bool $no_cache  true면 캐시 무시하고 새로 fetch
 * @return array[] [ ['title','link','date','category','thumb','excerpt','tags'] , … ]
 */
function moondental_fetch_naver_blog( $limit = 20, $no_cache = false ) {
	$info    = moondental_get_info();
	$blog_url = isset( $info['blog_url'] ) ? $info['blog_url'] : '';
	if ( ! $blog_url ) return array();

	// blog.naver.com/<id> → rss.blog.naver.com/<id>.xml 로 변환
	if ( preg_match( '#blog\.naver\.com/([A-Za-z0-9_-]+)#', $blog_url, $m ) ) {
		$rss_url = 'https://rss.blog.naver.com/' . $m[1] . '.xml';
	} else {
		return array();
	}

	$cache_key = 'moondental_naver_rss_' . md5( $rss_url );
	if ( ! $no_cache ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) return array_slice( $cached, 0, $limit );
	}

	$resp = wp_remote_get( $rss_url, array(
		'timeout'    => 8,
		'user-agent' => 'Mozilla/5.0 MoonDental/1.0',
	) );
	if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
		return array();
	}
	$xml_text = wp_remote_retrieve_body( $resp );

	// CDATA 살리며 파싱
	libxml_use_internal_errors( true );
	$xml = simplexml_load_string( $xml_text, 'SimpleXMLElement', LIBXML_NOCDATA );
	libxml_clear_errors();
	if ( ! $xml || ! isset( $xml->channel->item ) ) return array();

	$items = array();
	foreach ( $xml->channel->item as $it ) {
		$desc_html = (string) $it->description;
		// 첫 <img>를 썸네일로
		$thumb = '';
		if ( preg_match( '#<img[^>]+src=["\']([^"\']+)["\']#i', $desc_html, $im ) ) {
			$thumb = $im[1];
		}
		// 본문에서 HTML 제거, 200자 요약
		$plain = trim( html_entity_decode( wp_strip_all_tags( $desc_html ), ENT_QUOTES, 'UTF-8' ) );
		$plain = preg_replace( '/\s+/u', ' ', $plain );
		$excerpt = mb_strimwidth( $plain, 0, 160, '…', 'UTF-8' );

		// 카테고리 "문치과병원[치아 이야기]" → "치아 이야기"
		$cat_raw = (string) $it->category;
		$category = '';
		if ( preg_match( '#\[([^\]]+)\]#u', $cat_raw, $cm ) ) {
			$category = trim( $cm[1] );
		} elseif ( $cat_raw ) {
			$category = trim( $cat_raw );
		}

		// 링크에서 RSS 추적 파라미터 제거
		$link = (string) $it->link;
		$link = preg_replace( '#[?&]fromRss=true[^&]*#', '', $link );
		$link = preg_replace( '#[?&]trackingCode=rss#', '', $link );
		$link = preg_replace( '#\?$#', '', $link );

		// 태그 (콤마 구분 문자열 → 배열)
		$tag_raw = (string) $it->tag;
		$tags    = $tag_raw ? array_map( 'trim', explode( ',', $tag_raw ) ) : array();

		$items[] = array(
			'title'    => trim( (string) $it->title ),
			'link'     => $link,
			'date'     => strtotime( (string) $it->pubDate ),
			'category' => $category,
			'thumb'    => $thumb,
			'excerpt'  => $excerpt,
			'tags'     => array_slice( $tags, 0, 6 ),
		);
	}

	set_transient( $cache_key, $items, HOUR_IN_SECONDS );
	return array_slice( $items, 0, $limit );
}


/**
 * Flatten team for simple grid rendering.
 */
function moondental_get_team_flat() {
	$flat = array();
	foreach ( moondental_get_team() as $group ) {
		foreach ( $group['members'] as $m ) {
			$m['group'] = $group['group'];
			$flat[]     = $m;
		}
	}
	return $flat;
}
