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
	$provider = get_option( 'md_translate_provider', 'azure' );
	$api_key  = get_option( 'md_translate_api_key', '' );
	if ( ! $api_key ) return null;

	// 각 provider의 언어 코드 매핑
	$lang_map = array(
		'google' => array( 'en' => 'en', 'zh' => 'zh-CN', 'vi' => 'vi', 'ru' => 'ru', 'mn' => 'mn' ),
		'azure'  => array( 'en' => 'en', 'zh' => 'zh-Hans', 'vi' => 'vi', 'ru' => 'ru', 'mn' => 'mn-Mong' ),
		'deepl'  => array( 'en' => 'EN', 'zh' => 'ZH', 'ru' => 'RU' ), // vi, mn 미지원
	);
	$target = $lang_map[ $provider ][ $lang ] ?? null;
	if ( ! $target ) return null; // 이 provider에서 지원 안 함

	if ( $provider === 'google' ) {
		return moondental_translate_google( $source, $target, $api_key );
	}
	if ( $provider === 'azure' ) {
		$region = get_option( 'md_translate_azure_region', 'koreacentral' );
		return moondental_translate_azure( $source, $target, $api_key, $region );
	}
	if ( $provider === 'deepl' ) {
		$is_pro = (bool) get_option( 'md_translate_deepl_pro', false );
		return moondental_translate_deepl( $source, $target, $api_key, $is_pro );
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
		'format' => 'text',
	);
	$response = wp_remote_post( $url, array(
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( $body ),
		'timeout' => 8,
	) );
	if ( is_wp_error( $response ) ) return null;
	if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return null;
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $data['data']['translations'][0]['translatedText'] ) ) return null;
	return $data['data']['translations'][0]['translatedText'];
}

/**
 * Microsoft Azure Translator v3.0 호출.
 * 무료 F0 티어 · 월 2,000,000자 · 6개 언어 모두 지원 (권장).
 */
function moondental_translate_azure( $source, $target, $api_key, $region = 'koreacentral' ) {
	$url = 'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&from=ko&to=' . urlencode( $target );
	$response = wp_remote_post( $url, array(
		'headers' => array(
			'Ocp-Apim-Subscription-Key'    => $api_key,
			'Ocp-Apim-Subscription-Region' => $region,
			'Content-Type'                 => 'application/json',
		),
		'body'    => wp_json_encode( array( array( 'text' => $source ) ) ),
		'timeout' => 8,
	) );
	if ( is_wp_error( $response ) ) return null;
	if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return null;
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $data[0]['translations'][0]['text'] ) ) return null;
	return $data[0]['translations'][0]['text'];
}

/**
 * DeepL API 호출 (Free 또는 Pro).
 * Free: 월 500K자 · Pro: $6.99/월~ · 최고 품질 (지원 언어: en/zh/ru만 · vi/mn 미지원)
 */
function moondental_translate_deepl( $source, $target, $api_key, $is_pro = false ) {
	$base = $is_pro ? 'https://api.deepl.com' : 'https://api-free.deepl.com';
	$url  = $base . '/v2/translate';
	$response = wp_remote_post( $url, array(
		'headers' => array(
			'Authorization' => 'DeepL-Auth-Key ' . $api_key,
			'Content-Type'  => 'application/x-www-form-urlencoded',
		),
		'body'    => array(
			'text'        => $source,
			'source_lang' => 'KO',
			'target_lang' => $target,
		),
		'timeout' => 8,
	) );
	if ( is_wp_error( $response ) ) return null;
	if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return null;
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $data['translations'][0]['text'] ) ) return null;
	return $data['translations'][0]['text'];
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
		update_option( 'md_translate_provider',     sanitize_text_field( $_POST['md_provider'] ?? 'azure' ) );
		update_option( 'md_translate_api_key',      sanitize_text_field( $_POST['md_api_key']  ?? '' ) );
		update_option( 'md_translate_azure_region', sanitize_text_field( $_POST['md_azure_region'] ?? 'koreacentral' ) );
		update_option( 'md_translate_deepl_pro',    ! empty( $_POST['md_deepl_pro'] ) ? 1 : 0 );
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

	$provider     = get_option( 'md_translate_provider', 'azure' );
	$api_key      = get_option( 'md_translate_api_key', '' );
	$azure_region = get_option( 'md_translate_azure_region', 'koreacentral' );
	$deepl_pro    = (bool) get_option( 'md_translate_deepl_pro', false );
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
								<option value="azure"  <?php selected( $provider, 'azure' ); ?>>⭐ Microsoft Azure Translator (권장 · 영구 무료 2M자/월 · 6개 언어 모두 지원)</option>
								<option value="google" <?php selected( $provider, 'google' ); ?>>Google Cloud Translate ($20/1M자 · 무료 크레딧 12개월)</option>
								<option value="deepl"  <?php selected( $provider, 'deepl' ); ?>>DeepL (최고 품질 · 무료 500K자/월 · 몽골·베트남 미지원)</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="md_api_key">API Key</label></th>
						<td>
							<input type="password" name="md_api_key" id="md_api_key" class="regular-text" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="off" />
							<p class="description">아래 provider별 안내 참조 · 저장 후 <strong>API 테스트</strong>로 확인</p>
						</td>
					</tr>
					<tr>
						<th><label for="md_azure_region">Azure Region <span style="color:#999">(Azure 사용 시만)</span></label></th>
						<td>
							<select name="md_azure_region" id="md_azure_region">
								<option value="koreacentral" <?php selected( $azure_region, 'koreacentral' ); ?>>Korea Central (권장 · 서울 · 가장 빠름)</option>
								<option value="global"       <?php selected( $azure_region, 'global' ); ?>>Global</option>
								<option value="eastus"       <?php selected( $azure_region, 'eastus' ); ?>>East US</option>
								<option value="westus"       <?php selected( $azure_region, 'westus' ); ?>>West US</option>
							</select>
							<p class="description">Azure Portal에서 리소스 생성 시 선택한 region</p>
						</td>
					</tr>
					<tr>
						<th><label for="md_deepl_pro">DeepL Plan <span style="color:#999">(DeepL 사용 시만)</span></label></th>
						<td>
							<label><input type="checkbox" name="md_deepl_pro" value="1" <?php checked( $deepl_pro ); ?>> DeepL Pro (유료 · api.deepl.com)</label>
							<p class="description">체크 안 하면 무료 · api-free.deepl.com</p>
						</td>
					</tr>
				</table>
				<p>
					<input type="submit" name="md_save_api" class="button button-primary" value="설정 저장">
					<?php if ( $api_key ) : ?>
						<input type="submit" name="md_test_api" class="button button-secondary" value="🧪 API 테스트">
					<?php endif; ?>
				</p>
			</form>
		</div>

		<div class="card" style="max-width:800px; margin-top:20px; background:#f0f6fc; border-left:4px solid #2271b1">
			<h2>⭐ Microsoft Azure Translator 발급 안내 (권장 · 진짜 무료)</h2>
			<p><strong>왜 Azure를 권장하나:</strong> 월 200만자 <strong>영구 무료</strong> · 6개 언어 모두 지원 (Google 4배 · DeepL 4배)</p>
			<ol>
				<li><a href="https://portal.azure.com/" target="_blank">Azure Portal</a> 로그인 (Microsoft 계정 · 없으면 무료 가입)</li>
				<li>상단 검색창 → <code>Translator</code> 검색 → <strong>Translator</strong> 클릭</li>
				<li><strong>Create</strong> 클릭</li>
				<li>Resource group: 새로 생성 · 이름 "moondental"</li>
				<li>Region: <strong>Korea Central</strong> (한국 · 빠름)</li>
				<li>Name: 예 "moondental-translator"</li>
				<li>Pricing tier: <strong>F0 (Free)</strong> 선택 · 월 200만자 영구 무료</li>
				<li>Review + create → Create</li>
				<li>배포 완료 후 리소스 클릭 → 좌측 메뉴 <strong>Keys and Endpoint</strong></li>
				<li><strong>KEY 1</strong> 복사 → 위 API Key 필드에 붙여넣기</li>
				<li>Region이 <code>koreacentral</code>로 되어있는지 확인</li>
				<li>설정 저장 → API 테스트 클릭</li>
			</ol>
			<p><strong>결제 걱정 없음:</strong> F0 티어는 초과해도 자동 차단 · 청구 안 됨. 신용카드 등록 없이 가입 가능.</p>
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
			<h2>Google Cloud Translate 발급 (대안)</h2>
			<ol>
				<li><a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> 로그인</li>
				<li>프로젝트 생성 → APIs & Services → Library → <code>Cloud Translation API</code> 활성화</li>
				<li>Credentials → Create Credentials → API key</li>
				<li>무료: 최초 12개월 $300 크레딧 (~15M자) · 이후 $20/1M자</li>
				<li><strong>주의:</strong> 500K자/월 영구 무료 티어는 명시가 애매 · 장기적으론 결제 발생 가능</li>
			</ol>
		</div>

		<div class="card" style="max-width:800px; margin-top:20px">
			<h2>DeepL 발급 (최고 품질 · 지원 언어 제한)</h2>
			<ol>
				<li><a href="https://www.deepl.com/pro-api" target="_blank">DeepL API</a> → Free 계정 가입</li>
				<li>계정 페이지 → Account → Authentication Key 복사</li>
				<li>무료: 월 500,000자 영구</li>
				<li><strong>지원 언어:</strong> 영어·중국어·러시아어만 · <strong>몽골어·베트남어 미지원</strong></li>
				<li>미지원 언어는 자동으로 한국어 fallback · 다른 provider 병용 필요</li>
			</ol>
		</div>
	</div>
	<?php
}
