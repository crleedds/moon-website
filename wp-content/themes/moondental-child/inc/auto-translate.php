<?php
/**
 * 자동 번역 · API 기반 · DB 캐시 · md_content 통합
 *
 * v3.44.0
 *
 * 아키텍처:
 *   md_content('key') 호출
 *      ↓
 *   moondental_translate_content(key, value)  (customizer-content.php)
 *      ↓ 언어가 ko가 아니면
 *   1. 파일 기반 번역 (md_translations_{lang}.php) 조회 → 있으면 사용
 *   2. DB 캐시 (md_translations 테이블 · lang + hash 키) 조회 → 있으면 사용
 *   3. API 자동 번역 (설정된 provider) 호출 → 캐시 저장 → 반환
 *   4. Fallback: 한국어 원본 반환
 *
 * 캐시 무효화:
 *   콘텐츠 해시(MD5) 기준으로 저장 · 원본 변경 시 새 해시 → 자동 재번역
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * DB 테이블 · 번역 캐시
 * ========================================================== */

/**
 * 활성화 시 캐시 테이블 생성.
 * 스키마: id · lang · source_hash · translation · provider · created_at
 */
function moondental_translation_cache_install() {
	global $wpdb;
	$table = $wpdb->prefix . 'md_translations';
	$charset = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		lang VARCHAR(8) NOT NULL,
		source_hash CHAR(32) NOT NULL,
		translation LONGTEXT NOT NULL,
		provider VARCHAR(24) DEFAULT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY lang_hash (lang, source_hash)
	) $charset;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
add_action( 'after_switch_theme', 'moondental_translation_cache_install' );

/* 최초 1회 자동 설치 (테마 이미 활성 상태에서도 · v3.44.0 신규) */
add_action( 'admin_init', function () {
	if ( get_option( 'md_translation_cache_v3440' ) === 'done' ) return;
	moondental_translation_cache_install();
	update_option( 'md_translation_cache_v3440', 'done' );
} );

/**
 * 캐시 조회 · 없으면 null.
 */
function moondental_translation_cache_get( $lang, $source ) {
	global $wpdb;
	$hash = md5( $source );
	$table = $wpdb->prefix . 'md_translations';
	$result = $wpdb->get_var( $wpdb->prepare(
		"SELECT translation FROM $table WHERE lang = %s AND source_hash = %s",
		$lang, $hash
	) );
	return $result !== null ? $result : null;
}

/**
 * 캐시 저장 · REPLACE로 upsert.
 */
function moondental_translation_cache_set( $lang, $source, $translation, $provider = 'google' ) {
	global $wpdb;
	$hash = md5( $source );
	$table = $wpdb->prefix . 'md_translations';
	return $wpdb->replace( $table, array(
		'lang'        => $lang,
		'source_hash' => $hash,
		'translation' => $translation,
		'provider'    => $provider,
	) );
}

/**
 * 캐시 전체 삭제 (관리자 도구용).
 */
function moondental_translation_cache_flush( $lang = null ) {
	global $wpdb;
	$table = $wpdb->prefix . 'md_translations';
	if ( $lang ) {
		return $wpdb->delete( $table, array( 'lang' => $lang ) );
	}
	return $wpdb->query( "TRUNCATE TABLE $table" );
}


/* ============================================================
 * API Adapter · Google Cloud Translate v2
 * ========================================================== */

/**
 * 소스 한국어 → 대상 언어 번역 API 호출.
 * Google Cloud Translate v2 REST · API 키 인증.
 *
 * @param string $source 한국어 원본
 * @param string $lang   'en' | 'zh' | 'vi' | 'ru' | 'mn'
 * @return string|null   성공 시 번역 · 실패 시 null
 */
function moondental_translate_via_api( $source, $lang ) {
	if ( trim( (string) $source ) === '' ) return null;
	$provider = get_option( 'md_translate_provider', 'google' );
	$api_key  = get_option( 'md_translate_api_key', '' );
	if ( ! $api_key ) return null;

	// Google 언어 코드 매핑 (mn은 Google에서 'mn' 지원)
	$google_lang = array(
		'en' => 'en',
		'zh' => 'zh-CN',
		'vi' => 'vi',
		'ru' => 'ru',
		'mn' => 'mn',
	);
	$target = $google_lang[ $lang ] ?? $lang;

	if ( $provider === 'google' ) {
		return moondental_translate_google( $source, $target, $api_key );
	}
	return null;
}

/**
 * Google Cloud Translate v2 호출.
 */
function moondental_translate_google( $source, $target, $api_key ) {
	$url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode( $api_key );
	$body = array(
		'q'      => $source,
		'source' => 'ko',
		'target' => $target,
		'format' => 'text', // HTML이면 'html'로 · 지금은 text 안전
	);
	$response = wp_remote_post( $url, array(
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( $body ),
		'timeout' => 8,
	) );
	if ( is_wp_error( $response ) ) return null;
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code !== 200 ) return null;
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['data']['translations'][0]['translatedText'] ) ) return null;
	return $data['data']['translations'][0]['translatedText'];
}


/* ============================================================
 * 통합 진입점 · moondental_translate_content()에서 호출
 * ========================================================== */

/**
 * 파일 → 캐시 → API 순서로 번역 시도.
 * 실패 시 원본 반환 (safe fallback).
 */
function moondental_translate_auto( $lang, $source ) {
	if ( ! is_string( $source ) || $source === '' ) return $source;
	if ( $lang === 'ko' || $lang === '' ) return $source;

	// 1. 캐시 조회
	$cached = moondental_translation_cache_get( $lang, $source );
	if ( $cached !== null ) return $cached;

	// 2. API 호출 (설정된 경우만)
	$provider = get_option( 'md_translate_provider', '' );
	$api_key  = get_option( 'md_translate_api_key', '' );
	if ( ! $provider || ! $api_key ) return $source; // 미설정 · 원본

	// 무한 루프 방지 · 짧은 문자열이나 URL·이모지만은 건너뛰기
	if ( mb_strlen( trim( $source ) ) < 2 ) return $source;
	if ( preg_match( '#^https?://#', trim( $source ) ) ) return $source;

	$translated = moondental_translate_via_api( $source, $lang );
	if ( $translated ) {
		moondental_translation_cache_set( $lang, $source, $translated, $provider );
		return $translated;
	}
	return $source;
}


/* ============================================================
 * Customizer · API 설정 필드
 * ========================================================== */

/* 이 필드들은 wp-admin → 사용자 정의하기 → 사이트 설정 아래에 표시.
 * 커스터마이저 그룹 등록은 customizer-content.php의 chrome 그룹에 추가됨 (v3.44.0). */


/* ============================================================
 * 관리자 도구 · 캐시 관리 · 사전 번역
 * ========================================================== */

/**
 * 관리자 페이지 · 도구 → 자동 번역 상태
 */
add_action( 'admin_menu', function () {
	add_management_page(
		'자동 번역 · Moondental',
		'🌐 자동 번역',
		'manage_options',
		'md-auto-translate',
		'moondental_translate_admin_page'
	);
} );

function moondental_translate_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( '권한 없음' );
	global $wpdb;
	$table = $wpdb->prefix . 'md_translations';

	// API 설정 저장
	if ( isset( $_POST['md_save_api'] ) && check_admin_referer( 'md_translate_admin' ) ) {
		update_option( 'md_translate_provider', sanitize_text_field( $_POST['md_provider'] ?? 'google' ) );
		update_option( 'md_translate_api_key',  sanitize_text_field( $_POST['md_api_key']  ?? '' ) );
		echo '<div class="notice notice-success"><p>API 설정 저장 완료</p></div>';
	}

	// 캐시 flush 처리
	if ( isset( $_POST['md_flush_cache'] ) && check_admin_referer( 'md_translate_admin' ) ) {
		$lang = sanitize_text_field( $_POST['flush_lang'] ?? '' );
		moondental_translation_cache_flush( $lang ?: null );
		echo '<div class="notice notice-success"><p>캐시 삭제 완료 · 다음 페이지 방문 시 자동 재번역</p></div>';
	}

	// API 테스트
	if ( isset( $_POST['md_test_api'] ) && check_admin_referer( 'md_translate_admin' ) ) {
		$test_result = moondental_translate_via_api( '안녕하세요, 문치과병원입니다.', 'en' );
		if ( $test_result ) {
			echo '<div class="notice notice-success"><p><strong>API 테스트 성공!</strong> 결과: ' . esc_html( $test_result ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error"><p><strong>API 테스트 실패.</strong> Provider·API 키·활성 상태 확인.</p></div>';
		}
	}

	$provider = get_option( 'md_translate_provider', 'google' );
	$api_key  = get_option( 'md_translate_api_key', '' );
	$stats = $wpdb->get_results( "SELECT lang, COUNT(*) AS cnt FROM $table GROUP BY lang", ARRAY_A );
	?>
	<div class="wrap">
		<h1>🌐 자동 번역 상태</h1>

		<div class="card" style="max-width:800px">
			<h2>API 설정</h2>
			<form method="post">
				<?php wp_nonce_field( 'md_translate_admin' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="md_provider">Provider</label></th>
						<td>
							<select name="md_provider" id="md_provider">
								<option value="">-- 사용 안 함 (수동 파일 번역만) --</option>
								<option value="google" <?php selected( $provider, 'google' ); ?>>Google Cloud Translate (권장 · 무료 500K자/월)</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="md_api_key">API Key</label></th>
						<td>
							<input type="password" name="md_api_key" id="md_api_key" class="regular-text" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="off" placeholder="AIzaSy..." />
							<p class="description">Google Cloud Console에서 발급 · 아래 안내 참조 · 저장 후 위의 <strong>API 테스트</strong> 클릭 권장</p>
						</td>
					</tr>
				</table>
				<p>
					<input type="submit" name="md_save_api" class="button button-primary" value="설정 저장">
					<?php if ( $api_key ) : ?>
						<input type="submit" name="md_test_api" class="button button-secondary" value="API 테스트">
					<?php endif; ?>
				</p>
			</form>
		</div>

		<div class="card" style="max-width:800px; margin-top:20px">
			<h2>캐시 통계</h2>
			<?php if ( $stats ) : ?>
				<table class="widefat striped">
					<thead><tr><th>언어</th><th>캐시된 번역 개수</th></tr></thead>
					<tbody>
						<?php foreach ( $stats as $row ) : ?>
							<tr><td><?php echo esc_html( $row['lang'] ); ?></td><td><?php echo (int) $row['cnt']; ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>아직 캐시된 번역이 없습니다. 각 언어 URL(/en/ 등) 방문 시 자동 번역이 채워집니다.</p>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:800px; margin-top:20px">
			<h2>캐시 관리</h2>
			<form method="post">
				<?php wp_nonce_field( 'md_translate_admin' ); ?>
				<p>
					<label>언어 (비우면 전체 언어):
						<select name="flush_lang">
							<option value="">전체</option>
							<option value="en">English</option>
							<option value="zh">中文</option>
							<option value="vi">Tiếng Việt</option>
							<option value="ru">Русский</option>
							<option value="mn">Монгол</option>
						</select>
					</label>
				</p>
				<p><input type="submit" name="md_flush_cache" class="button button-secondary" value="캐시 삭제 (다음 방문 시 재번역)"></p>
			</form>
		</div>

		<div class="card" style="max-width:800px; margin-top:20px">
			<h2>Google Cloud Translate API 키 발급 안내</h2>
			<ol>
				<li><a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> 로그인 (구글 계정)</li>
				<li>프로젝트 생성 (예: "moondental")</li>
				<li>좌측 메뉴 → APIs & Services → Library → "Cloud Translation API" 검색 · 활성화</li>
				<li>APIs & Services → Credentials → Create Credentials → API key</li>
				<li>발급된 키 복사 → 위 Customizer에 붙여넣기</li>
				<li>무료: 월 500,000자 · 이 사이트 규모는 충분</li>
			</ol>
		</div>
	</div>
	<?php
}
