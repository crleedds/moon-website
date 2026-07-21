<?php
/**
 * 네이버 블로그 → WP 글 (post) 임포터
 *
 *  - RSS 피드에서 logNo 목록을 얻고
 *  - 아직 import 안 된 logNo만 PostView.naver 에서 본문 추출
 *  - se-main-container 추출 → sanitize → wp_insert_post
 *  - 이미지는 네이버 CDN URL 유지 + 모든 <img>에 referrerpolicy="no-referrer" 부착
 *  - post_meta 'moondental_naver_log_no' 로 중복 import 방지
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const MOONDENTAL_NAVER_META = 'moondental_naver_log_no';


/**
 * 이미 import된 logNo인지 확인. 있으면 post_id 반환, 없으면 0.
 */
function moondental_naver_existing_post_id( $log_no ) {
	$q = new WP_Query( array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'meta_key'       => MOONDENTAL_NAVER_META,
		'meta_value'     => (string) $log_no,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	$id = $q->have_posts() ? (int) $q->posts[0] : 0;
	wp_reset_postdata();
	return $id;
}


/**
 * PostView.naver HTML 가져오기.
 */
function moondental_naver_fetch_post_html( $blog_id, $log_no ) {
	$url  = sprintf(
		'https://blog.naver.com/PostView.naver?blogId=%s&logNo=%s&redirect=Dlog&widgetTypeCall=true',
		urlencode( $blog_id ),
		urlencode( $log_no )
	);
	$resp = wp_remote_get( $url, array(
		'timeout'    => 12,
		'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
		'headers'    => array(
			'Accept-Language' => 'ko-KR,ko;q=0.9',
			'Referer'         => 'https://blog.naver.com/',
		),
	) );
	if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
		return new WP_Error( 'fetch_failed', '네이버 블로그 응답 실패: ' . $log_no );
	}
	return wp_remote_retrieve_body( $resp );
}


/**
 * HTML 문서에서 SmartEditor 3.0 본문 컨테이너(se-main-container)를 뽑아낸다.
 * 컨테이너가 없으면 SE 2.0 fallback(div#postViewArea)도 시도.
 *
 * @return string  내부 HTML (없으면 빈 문자열)
 */
function moondental_naver_extract_content( $html ) {
	if ( ! $html ) return '';

	// libxml warnings 끄고 한국어 인코딩 명시
	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	// HTML에 charset 메타가 있어도 강제로 UTF-8 처리
	$prefixed = '<?xml encoding="UTF-8">' . $html;
	$dom->loadHTML( $prefixed, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();

	$xpath = new DOMXPath( $dom );

	// 1) SE 3.0
	$nodes = $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' se-main-container ')]" );
	if ( $nodes && $nodes->length ) {
		return moondental_naver_dom_inner_html( $dom, $nodes->item( 0 ) );
	}
	// 2) SE 2.0 (legacy)
	$alt = $dom->getElementById( 'postViewArea' );
	if ( $alt ) {
		return moondental_naver_dom_inner_html( $dom, $alt );
	}
	return '';
}

function moondental_naver_dom_inner_html( $dom, $node ) {
	$out = '';
	foreach ( $node->childNodes as $child ) {
		$out .= $dom->saveHTML( $child );
	}
	return $out;
}


/**
 * 추출된 HTML을 WP에 저장하기 안전하게 sanitize + 후처리.
 *
 *  - wp_kses_post 로 위험 태그 제거
 *  - 남은 모든 <img> 에 referrerpolicy="no-referrer" + loading="lazy" + decoding="async" 부착
 *  - 네이버 추적/광고용 빈 div, br>br 같은 노이즈 정리
 */
function moondental_naver_sanitize_content( $html ) {
	if ( ! $html ) return '';

	// 위험 태그 제거 + WP 허용 set
	$clean = wp_kses_post( $html );

	// img 태그에 referrerpolicy / loading 보강
	$clean = preg_replace_callback( '#<img\b([^>]*)>#i', function ( $m ) {
		$attrs = $m[1];
		if ( stripos( $attrs, 'referrerpolicy' ) === false ) {
			$attrs .= ' referrerpolicy="no-referrer"';
		}
		if ( stripos( $attrs, 'loading=' ) === false ) {
			$attrs .= ' loading="lazy"';
		}
		if ( stripos( $attrs, 'decoding=' ) === false ) {
			$attrs .= ' decoding="async"';
		}
		return '<img' . $attrs . '>';
	}, $clean );

	// 과도한 빈 줄 정리
	$clean = preg_replace( "/(\r?\n){3,}/", "\n\n", $clean );

	return $clean;
}


/**
 * RSS item 하나를 import한다. 이미 있으면 skip.
 *
 * @return int|WP_Error  생성된 post_id, 이미 있으면 0, 실패 시 WP_Error
 */
function moondental_naver_import_one( $rss_item, $blog_id ) {
	// RSS link에서 logNo 추출
	if ( ! preg_match( '#/(\d{12,})#', $rss_item['link'], $m ) ) {
		return new WP_Error( 'no_logno', 'logNo를 추출할 수 없음: ' . $rss_item['link'] );
	}
	$log_no = $m[1];

	if ( moondental_naver_existing_post_id( $log_no ) ) {
		return 0; // skip
	}

	$html = moondental_naver_fetch_post_html( $blog_id, $log_no );
	if ( is_wp_error( $html ) ) return $html;

	$body = moondental_naver_extract_content( $html );
	if ( ! $body ) return new WP_Error( 'no_body', '본문 컨테이너를 찾지 못함: ' . $log_no );

	$body = moondental_naver_sanitize_content( $body );

	$post_data = array(
		'post_title'   => wp_strip_all_tags( $rss_item['title'] ),
		'post_content' => $body,
		'post_excerpt' => $rss_item['excerpt'],
		'post_status'  => 'publish',
		'post_type'    => 'post',
		'post_date'    => date_i18n( 'Y-m-d H:i:s', $rss_item['date'] ),
		'post_date_gmt'=> gmdate( 'Y-m-d H:i:s', $rss_item['date'] ),
		'meta_input'   => array(
			MOONDENTAL_NAVER_META            => $log_no,
			'moondental_naver_source_url'    => $rss_item['link'],
			'moondental_naver_thumb_url'     => $rss_item['thumb'],
			'moondental_naver_category'      => $rss_item['category'],
			'moondental_naver_tags'          => is_array( $rss_item['tags'] ) ? implode( ',', $rss_item['tags'] ) : '',
			'moondental_naver_imported_at'   => current_time( 'mysql' ),
		),
	);

	$post_id = wp_insert_post( $post_data, true );
	if ( is_wp_error( $post_id ) ) return $post_id;

	// 카테고리를 WP 카테고리로 매핑 (있으면 사용, 없으면 생성)
	if ( ! empty( $rss_item['category'] ) ) {
		$term = term_exists( $rss_item['category'], 'category' );
		if ( ! $term ) {
			$term = wp_insert_term( $rss_item['category'], 'category' );
		}
		if ( ! is_wp_error( $term ) ) {
			wp_set_post_categories( $post_id, array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ), true );
		}
	}

	// 태그 매핑
	if ( ! empty( $rss_item['tags'] ) && is_array( $rss_item['tags'] ) ) {
		wp_set_post_tags( $post_id, $rss_item['tags'], true );
	}

	return $post_id;
}


/**
 * RSS 최신 N개 글을 모두 import 시도.
 *
 * @param int $limit
 * @return array  ['created' => [..post_ids..], 'skipped' => N, 'errors' => [..messages..]]
 */
function moondental_naver_import_all( $limit = 20 ) {
	// v3.37.0 · 외부 리소스 fetch·글 생성 → 관리자 전용 이중 체크
	if ( ! current_user_can( 'manage_options' ) ) {
		return array( 'created'=>array(), 'skipped'=>0, 'errors'=>array( 'insufficient_permissions' ) );
	}
	$result = array( 'created' => array(), 'skipped' => 0, 'errors' => array() );

	$info = moondental_get_info();
	if ( empty( $info['blog_url'] ) ) {
		$result['errors'][] = '블로그 URL이 설정되지 않음';
		return $result;
	}
	if ( ! preg_match( '#blog\.naver\.com/([A-Za-z0-9_-]+)#', $info['blog_url'], $m ) ) {
		$result['errors'][] = '블로그 URL 형식 인식 실패';
		return $result;
	}
	$blog_id = $m[1];

	$items = moondental_fetch_naver_blog( $limit, true ); // no cache
	if ( empty( $items ) ) {
		$result['errors'][] = 'RSS 피드를 가져오지 못함';
		return $result;
	}

	foreach ( $items as $item ) {
		$r = moondental_naver_import_one( $item, $blog_id );
		if ( is_wp_error( $r ) ) {
			$result['errors'][] = $r->get_error_message();
		} elseif ( $r === 0 ) {
			$result['skipped']++;
		} else {
			$result['created'][] = $r;
			// 네이버 부담 방지 — 글 사이 0.4초 sleep
			usleep( 400000 );
		}
	}

	// 새로 import된 글에 대해 Yoast Indexable 미리 빌드 (첫 접근 stall 방지)
	if ( ! empty( $result['created'] ) && function_exists( 'YoastSEO' ) ) {
		try {
			$builder = YoastSEO()->classes->get( \Yoast\WP\SEO\Builders\Indexable_Builder::class );
			foreach ( $result['created'] as $pid ) {
				try { $builder->build_for_id_and_type( (int) $pid, 'post' ); }
				catch ( \Throwable $e ) { /* 한 글 실패는 무시 */ }
			}
		} catch ( \Throwable $e ) { /* Yoast 미설치 / 버전 불일치 */ }
	}

	return $result;
}


/* ========================================================================
 * v3.21.7 — 로컬 이미지 다운로드 임포터
 * 기존 임포터는 네이버 CDN URL을 본문에 그대로 유지 → referer 차단으로 사진 안 보임.
 * 이 함수는 본문 내 <img>를 모두 WP 미디어로 sideload 한 뒤 src를 로컬 URL로 교체.
 * 결과: 네이버 의존성 없이 자체 서빙되는 콘텐츠.
 * ======================================================================== */

const MOONDENTAL_LOCAL_IMPORT_META = 'moondental_local_import_v2';

/**
 * 본문 내 원격 <img>를 WP 미디어로 sideload 후 로컬 URL로 교체.
 *
 * @param string $body    본문 HTML
 * @param int    $post_id 첨부할 글 ID
 * @return array ['body' => string, 'first_attachment_id' => int, 'count' => int]
 */
function moondental_sideload_images_in_body( $body, $post_id ) {
	if ( ! preg_match_all( '#<img[^>]+src=["\']([^"\']+)["\']#i', $body, $matches ) ) {
		return array( 'body' => $body, 'first_attachment_id' => 0, 'count' => 0 );
	}

	// admin 함수 로드
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$urls = array_unique( $matches[1] );
	$first_id = 0;
	$count = 0;

	// v3.37.0 · SSRF 방어 · 정확한 호스트 매칭 (substring 매칭 X)
	$naver_hosts_exact = array(
		'blog.naver.com',
		'blogfiles.naver.net',
		'blogpfthumb-phinf.pstatic.net',
		'postfiles.pstatic.net',
	);
	$naver_hosts_suffix = array(
		'.pstatic.net',
		'.phinf.naver.net',
		'.blogfiles.naver.net',
	);

	foreach ( $urls as $url ) {
		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['host'] ) || empty( $parsed['scheme'] ) ) continue;
		if ( ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) continue;

		$host = strtolower( $parsed['host'] );
		$is_naver = in_array( $host, $naver_hosts_exact, true );
		if ( ! $is_naver ) {
			foreach ( $naver_hosts_suffix as $suf ) {
				if ( substr( $host, -strlen( $suf ) ) === $suf ) { $is_naver = true; break; }
			}
		}
		$is_local = strpos( $url, home_url() ) === 0 || $url[0] === '/';
		if ( ! $is_naver || $is_local ) continue;

		// 쿼리 파라미터 정리 (네이버 URL에 ?type=... 붙어 있는 경우 큰 사이즈로)
		$clean_url = preg_replace( '#\?type=w\d+#i', '?type=w1200', $url );

		$id = media_sideload_image( $clean_url, $post_id, '', 'id' );
		if ( is_wp_error( $id ) || ! $id ) continue;

		$local_url = wp_get_attachment_image_url( (int) $id, 'full' );
		if ( ! $local_url ) continue;

		$body = str_replace( $url, $local_url, $body );
		$count++;
		if ( ! $first_id ) $first_id = (int) $id;
	}

	return array( 'body' => $body, 'first_attachment_id' => $first_id, 'count' => $count );
}


/**
 * 1회용 — 네이버 블로그 본문 + 사진을 모두 로컬로 가져오기.
 *
 * @param int $limit
 * @return array ['created' => [..ids..], 'skipped' => N, 'images' => N, 'errors' => [..]]
 */
function moondental_naver_import_local( $limit = 15 ) {
	// v3.37.0 · 이미지 sideload·글 생성 → 관리자 전용 이중 체크
	if ( ! current_user_can( 'manage_options' ) ) {
		return array( 'created'=>array(), 'skipped'=>0, 'images'=>0, 'errors'=>array( 'insufficient_permissions' ) );
	}
	@set_time_limit( 600 );
	$result = array( 'created' => array(), 'skipped' => 0, 'images' => 0, 'errors' => array() );

	$info = moondental_get_info();
	if ( empty( $info['blog_url'] ) ) {
		$result['errors'][] = '블로그 URL이 설정되지 않음';
		return $result;
	}
	if ( ! preg_match( '#blog\.naver\.com/([A-Za-z0-9_-]+)#', $info['blog_url'], $m ) ) {
		$result['errors'][] = '블로그 URL 형식 인식 실패';
		return $result;
	}
	$blog_id = $m[1];

	$items = moondental_fetch_naver_blog( $limit, true );
	if ( empty( $items ) ) {
		$result['errors'][] = 'RSS 피드를 가져오지 못함';
		return $result;
	}

	foreach ( $items as $item ) {
		// 이미 가져왔는지 source URL로 중복 체크
		$existing = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ),
			'meta_query'     => array( array( 'key' => MOONDENTAL_LOCAL_IMPORT_META, 'value' => $item['link'] ) ),
			'fields'         => 'ids',
			'numberposts'    => 1,
			'no_found_rows'  => true,
		) );
		if ( $existing ) { $result['skipped']++; continue; }

		if ( ! preg_match( '#/(\d{12,})#', $item['link'], $m2 ) ) {
			$result['errors'][] = 'logNo 파싱 실패: ' . $item['link'];
			continue;
		}
		$log_no = $m2[1];

		$html = moondental_naver_fetch_post_html( $blog_id, $log_no );
		if ( is_wp_error( $html ) ) { $result['errors'][] = $html->get_error_message(); continue; }

		$body = moondental_naver_extract_content( $html );
		if ( ! $body ) { $result['errors'][] = '본문 추출 실패: ' . $log_no; continue; }
		$body = moondental_naver_sanitize_content( $body );

		// 1단계: 임시로 글 생성 (post_id가 있어야 media_sideload 가능)
		$post_id = wp_insert_post( array(
			'post_title'   => wp_strip_all_tags( $item['title'] ),
			'post_content' => $body,
			'post_excerpt' => $item['excerpt'],
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_date'    => date_i18n( 'Y-m-d H:i:s', $item['date'] ),
			'post_date_gmt'=> gmdate( 'Y-m-d H:i:s', $item['date'] ),
			'meta_input'   => array(
				MOONDENTAL_LOCAL_IMPORT_META . '_at' => current_time( 'mysql' ),
			),
		), true );
		if ( is_wp_error( $post_id ) ) { $result['errors'][] = $post_id->get_error_message(); continue; }

		// 2단계: 본문 내 모든 네이버 이미지를 로컬로 sideload + src 교체
		$side = moondental_sideload_images_in_body( $body, $post_id );
		$result['images'] += (int) $side['count'];

		// 3단계: 본문 업데이트 + 대표 이미지 설정
		wp_update_post( array( 'ID' => $post_id, 'post_content' => $side['body'] ) );
		if ( $side['first_attachment_id'] ) {
			set_post_thumbnail( $post_id, $side['first_attachment_id'] );
		}

		// source URL 메타 + 외부 추적용
		update_post_meta( $post_id, MOONDENTAL_LOCAL_IMPORT_META, $item['link'] );

		// 카테고리 자동 분류
		if ( function_exists( 'moondental_recategorize_posts' ) ) {
			$haystack = wp_strip_all_tags( $item['title'] . "\n" . $side['body'] );
			// 단일 글 분류 — 키워드 매칭
			$news_kws = array( 'MOU', '협약', '체결', '연합회', '협회', '협의회', '봉사', '이벤트', '채용', '명절', '새해', '휴진', '진료 안내', '진료시간', '(월)', '(화)', '(수)', '(목)', '(금)', '(토)', '(일)' );
			$is_news = false;
			foreach ( $news_kws as $kw ) { if ( mb_stripos( $haystack, $kw ) !== false ) { $is_news = true; break; } }
			$slug = $is_news ? 'notice' : 'dental-stories';
			$cat  = get_category_by_slug( $slug );
			if ( $cat ) wp_set_post_categories( $post_id, array( (int) $cat->term_id ), false );
		}

		$result['created'][] = $post_id;
		usleep( 500000 ); // 0.5s 간격
	}

	return $result;
}
