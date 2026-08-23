<?php
/**
 * Moon Dental · 치과 백과사전 (Encyclopedia)
 *
 *  진료 관련 용어 사전. 카테고리·초성·검색 필터.
 *  아카이브 URL: /치과사전/
 *  단일 URL:    /치과사전/{slug}/
 *
 *  v3.35.0: 신규 (사용자 요청 · bdbddc.com/encyclopedia/ 참고)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
 * 1. Custom Post Type · md_term (치과 용어)
 * ========================================================== */
function moondental_register_encyclopedia_cpt() {
	register_post_type( 'md_term', array(
		'labels' => array(
			'name'               => '치과사전 · 용어',
			'singular_name'      => '용어',
			'menu_name'          => '📖 치과사전',
			'add_new'            => '용어 추가',
			'add_new_item'       => '새 용어 추가',
			'edit_item'          => '용어 편집',
			'new_item'           => '새 용어',
			'view_item'          => '용어 보기',
			'view_items'         => '치과사전 보기',
			'search_items'       => '용어 검색',
			'not_found'          => '용어가 없습니다.',
			'not_found_in_trash' => '휴지통에 용어가 없습니다.',
			'all_items'          => '모든 용어',
			'archives'           => '치과사전',
		),
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,        // 블록 에디터 활성
		'menu_position'       => 21,
		'menu_icon'           => 'dashicons-book-alt',
		'has_archive'         => '치과사전',
		'rewrite'             => array( 'slug' => '치과사전', 'with_front' => false ),
		'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
		'taxonomies'          => array( 'md_term_category' ),
	) );

	register_taxonomy( 'md_term_category', 'md_term', array(
		'labels' => array(
			'name'          => '분야',
			'singular_name' => '분야',
			'menu_name'     => '분야',
			'search_items'  => '분야 검색',
			'all_items'     => '모든 분야',
			'edit_item'     => '분야 편집',
			'add_new_item'  => '새 분야 추가',
		),
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => '치과사전-분야' ),
	) );
}
add_action( 'init', 'moondental_register_encyclopedia_cpt', 5 );

/* 활성화 시 rewrite rules flush · 마이그레이션에서 처리 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_encyclopedia_flush_v3350' ) === 'done' ) return;
	moondental_register_encyclopedia_cpt();
	flush_rewrite_rules( false );
	update_option( 'moondental_encyclopedia_flush_v3350', 'done' );
}, 90 );


/* ============================================================
 * 2. 기본 분야 카테고리 시드 (첫 활성화 시)
 * ========================================================== */
function moondental_seed_encyclopedia_categories() {
	if ( get_option( 'moondental_encyclopedia_cats_v3350' ) === 'done' ) return;
	$cats = array(
		array( 'name' => '임플란트', 'slug' => 'implant', 'desc' => '임플란트 픽스처·지대주·보철·수술 관련 용어' ),
		array( 'name' => '교정',     'slug' => 'ortho', 'desc' => '투명교정·브라켓·유지장치·교합 용어' ),
		array( 'name' => '보존·신경치료', 'slug' => 'preserve', 'desc' => '충치·신경치료·자연치아 보존 용어' ),
		array( 'name' => '치주·잇몸', 'slug' => 'periodontics', 'desc' => '잇몸염·스케일링·치주치료 용어' ),
		array( 'name' => '심미치료', 'slug' => 'aesthetic', 'desc' => '라미네이트·미백·심미보철 용어' ),
		array( 'name' => '보철',     'slug' => 'prosthetics', 'desc' => '크라운·틀니·브릿지 용어' ),
		array( 'name' => '구강외과·사랑니', 'slug' => 'surgery', 'desc' => '사랑니·매복치·발치·외과 용어' ),
		array( 'name' => '턱관절',   'slug' => 'tmj', 'desc' => 'TMJ·이갈이·개구장애 관련 용어' ),
		array( 'name' => '소아치과', 'slug' => 'pediatric', 'desc' => '어린이 치과 진료 관련 용어' ),
		array( 'name' => '예방·검진', 'slug' => 'prevention', 'desc' => '스케일링·불소·실란트·검진 용어' ),
		array( 'name' => '일반 치의학', 'slug' => 'general', 'desc' => '치아 구조·해부학·기초 용어' ),
	);
	foreach ( $cats as $c ) {
		if ( term_exists( $c['slug'], 'md_term_category' ) ) continue;
		wp_insert_term( $c['name'], 'md_term_category', array(
			'slug'        => $c['slug'],
			'description' => $c['desc'],
		) );
	}
	update_option( 'moondental_encyclopedia_cats_v3350', 'done' );
}

/**
 * v3.44.88 · 신규 15개 카테고리 (백과사전 전면 재편)
 *  기존 카테고리는 유지 · 신규만 추가
 */
function moondental_seed_encyclopedia_categories_v3488() {
	if ( get_option( 'moondental_encyclopedia_cats_v3488' ) === 'done' ) return;
	$cats = array(
		array( 'name' => '임플란트',           'slug' => 'implant',        'desc' => '임플란트 진료 관련 학술·임상 용어' ),
		array( 'name' => '교정',               'slug' => 'ortho',          'desc' => '교정 진단·장치·술식 관련 용어' ),
		array( 'name' => '치료·시술',          'slug' => 'treatment',      'desc' => '충전·수복·발치·마취 등 진료 술식' ),
		array( 'name' => '치과 질환',          'slug' => 'diseases',       'desc' => '치과 관련 각종 질환' ),
		array( 'name' => '치수·치아 질환',     'slug' => 'pulp-tooth',     'desc' => '치수염·근단염·치아 파절 등' ),
		array( 'name' => '치주 질환',          'slug' => 'periodontal',    'desc' => '잇몸·치주 질환·치조골 관련' ),
		array( 'name' => '소아 치과',          'slug' => 'pediatric-new',  'desc' => '유치·성장기·소아 진료 용어' ),
		array( 'name' => '치과 재료',          'slug' => 'materials',      'desc' => '보철·수복·인상재 등 치과 재료' ),
		array( 'name' => '장비·기술',          'slug' => 'equipment',      'desc' => 'CBCT·스캐너·CAD/CAM·레이저 등' ),
		array( 'name' => '전문 용어',          'slug' => 'professional',   'desc' => '의학·라틴어·국제표준 용어' ),
		array( 'name' => '구강내과 질환',      'slug' => 'oral-medicine',  'desc' => '점막·설·타액선·미각 등 구강내과' ),
		array( 'name' => '구강 관리',          'slug' => 'oral-care',      'desc' => '칫솔질·치실·홈케어 방법' ),
		array( 'name' => '턱관절·구강외과',    'slug' => 'tmj-surgery',    'desc' => 'TMD·이갈이·양악·낭종·매복치 수술' ),
		array( 'name' => '치아 구조',          'slug' => 'tooth-structure','desc' => '치아 해부·조직·치조골 구조' ),
		array( 'name' => '보험·비용',          'slug' => 'insurance-cost', 'desc' => '건강보험·비급여·의료비 관련' ),
	);
	foreach ( $cats as $c ) {
		if ( term_exists( $c['slug'], 'md_term_category' ) ) continue;
		wp_insert_term( $c['name'], 'md_term_category', array(
			'slug'        => $c['slug'],
			'description' => $c['desc'],
		) );
	}
	update_option( 'moondental_encyclopedia_cats_v3488', 'done' );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_categories_v3488', 20 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_categories_v3488', 20 );

/**
 * v3.44.88 · 새 시드 파일 로더 (encyclopedia-seed-v3488.php)
 *  전체 기존 md_term 휴지통 이동 후 신규 900개 삽입
 *  파일이 존재하고 아직 실행 안 됐을 때만 동작
 */
/**
 * v3.44.91 · AJAX 엔드포인트 · 용어 데이터 반환 (모달용)
 */
add_action( 'wp_ajax_md_term_get',        'moondental_ajax_md_term_get' );
add_action( 'wp_ajax_nopriv_md_term_get', 'moondental_ajax_md_term_get' );
function moondental_ajax_md_term_get() {
	$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
	if ( ! $id ) { wp_send_json_error( array( 'msg' => 'no id' ), 400 ); }

	// v3.44.93 · 브라우저 캐시 · 1시간 (재방문 시 fetch 없음)
	header( 'Cache-Control: public, max-age=3600' );

	// 24시간 transient 캐시 · 서버 측
	$cache_key = 'md_term_json_' . $id;
	$cached = get_transient( $cache_key );
	if ( $cached && is_array( $cached ) ) {
		wp_send_json_success( $cached );
	}

	$post = get_post( $id );
	if ( ! $post || $post->post_type !== 'md_term' || $post->post_status !== 'publish' ) {
		wp_send_json_error( array( 'msg' => 'not found' ), 404 );
	}
	$cats = get_the_terms( $id, 'md_term_category' );
	$cats_out = array();
	if ( is_array( $cats ) ) {
		foreach ( $cats as $c ) $cats_out[] = array( 'name' => $c->name, 'slug' => $c->slug );
	}

	// v3.44.92 · 같은 카테고리 관련 용어 6개 함께 반환
	$related = array();
	if ( ! empty( $cats ) ) {
		$cat_ids = wp_list_pluck( $cats, 'term_id' );
		$rel_q = new WP_Query( array(
			'post_type'      => 'md_term',
			'posts_per_page' => 6,
			'post__not_in'   => array( $id ),
			'orderby'        => 'rand',
			'no_found_rows'  => true,
			'tax_query'      => array( array(
				'taxonomy' => 'md_term_category',
				'field'    => 'id',
				'terms'    => $cat_ids,
			) ),
		) );
		if ( $rel_q->have_posts() ) {
			while ( $rel_q->have_posts() ) {
				$rel_q->the_post();
				$related[] = array(
					'title' => get_the_title(),
					'url'   => get_permalink(),
					'id'    => get_the_ID(),
				);
			}
			wp_reset_postdata();
		}
	}

	// v3.44.92 · 네이버 예약 URL
	$naver = '';
	if ( function_exists( 'moondental_get_info' ) ) {
		$info = moondental_get_info();
		$naver = $info['naver_place'] ?? '';
	}

	$data = array(
		'id'          => $id,
		'title'       => $post->post_title,
		'excerpt'     => $post->post_excerpt,
		'body'        => apply_filters( 'the_content', $post->post_content ),
		'url'         => get_permalink( $post ),
		'cats'        => $cats_out,
		'related'     => $related,
		'naver_book'  => $naver,
	);

	set_transient( $cache_key, $data, DAY_IN_SECONDS );
	wp_send_json_success( $data );
}

/**
 * v3.44.92 · 용어 수정 시 캐시 자동 삭제
 */
add_action( 'save_post_md_term', function( $post_id ) {
	delete_transient( 'md_term_json_' . (int) $post_id );
	delete_transient( 'md_term_all_terms_v1' );
} );

/**
 * v3.44.95 · 전체 용어 데이터 캐시 (아카이브 페이지 인라인 embed 용)
 *   모든 published md_term 을 하나의 JSON 배열로 반환 · 12시간 캐시
 *   모달이 이 데이터를 사용하면 fetch 없이 즉시 열림
 */
function moondental_md_term_all_cached() {
	$key = 'md_term_all_terms_v1';
	$cached = get_transient( $key );
	if ( $cached && is_array( $cached ) ) return $cached;

	$posts = get_posts( array(
		'post_type'      => 'md_term',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );

	$naver = '';
	if ( function_exists( 'moondental_get_info' ) ) {
		$info = moondental_get_info();
		$naver = $info['naver_place'] ?? '';
	}

	// term_id → post_ids 매핑 (같은 카테고리 관련 용어 빠르게 찾기)
	$data = array();
	$by_cat = array();
	foreach ( $posts as $p ) {
		$cats = get_the_terms( $p->ID, 'md_term_category' );
		$cats_out = array();
		$cat_slugs = array();
		if ( is_array( $cats ) ) {
			foreach ( $cats as $c ) {
				$cats_out[] = array( 'name' => $c->name, 'slug' => $c->slug );
				$cat_slugs[] = $c->slug;
			}
		}
		$data[ $p->ID ] = array(
			'id'    => $p->ID,
			'title' => $p->post_title,
			'ex'    => $p->post_excerpt,
			'body'  => apply_filters( 'the_content', $p->post_content ),
			'url'   => get_permalink( $p ),
			'cats'  => $cats_out,
			'cs'    => $cat_slugs, // 짧은 키
		);
		foreach ( $cat_slugs as $cs ) {
			if ( ! isset( $by_cat[ $cs ] ) ) $by_cat[ $cs ] = array();
			$by_cat[ $cs ][] = $p->ID;
		}
	}

	$out = array(
		'terms'  => array_values( $data ),
		'by_cat' => $by_cat,
		'naver'  => $naver,
	);
	set_transient( $key, $out, 12 * HOUR_IN_SECONDS );
	return $out;
}

function moondental_seed_encyclopedia_v3488() {
	// v3.44.93 · 카테고리 재분류 · 새 flag 로 재실행 (기존 데이터 완전 재생성)
	if ( get_option( 'moondental_encyclopedia_seed_v3493' ) === 'done' ) return;
	$seed_file = MOONDENTAL_DIR . '/inc/encyclopedia-seed-v3488.php';
	if ( ! file_exists( $seed_file ) ) return;

	// 카테고리 준비
	moondental_seed_encyclopedia_categories_v3488();

	// 시드 데이터 로드
	$terms = require $seed_file;
	if ( ! is_array( $terms ) || empty( $terms ) ) return;

	global $wpdb;

	// v3.44.90 · Raw SQL · 관련 테이블 완전 정리 (postmeta·term_relationships·posts)
	$old_ids_raw = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'md_term'" );
	if ( $old_ids_raw ) {
		// 500개씩 배치 · IN 절 길이 제한 회피
		$chunks = array_chunk( $old_ids_raw, 500 );
		foreach ( $chunks as $chunk ) {
			$ids_csv = implode( ',', array_map( 'intval', $chunk ) );
			$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids_csv)" );
			$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($ids_csv)" );
			$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ($ids_csv)" );
		}
	}

	// 신규 삽입
	$total = 0;
	foreach ( $terms as $t ) {
		if ( empty( $t['title'] ) || empty( $t['body'] ) ) continue;
		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $t['title'],
			'post_excerpt' => $t['excerpt'] ?? '',
			'post_content' => $t['body'],
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			if ( ! empty( $t['cat'] ) ) {
				$term = get_term_by( 'slug', $t['cat'], 'md_term_category' );
				if ( $term ) {
					wp_set_object_terms( $post_id, array( $term->term_id ), 'md_term_category' );
				}
			}
			$total++;
		}
	}

	// v3.44.89 · 카테고리 count 캐시 재계산
	if ( function_exists( 'wp_update_term_count' ) ) {
		$all_cats = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( is_array( $all_cats ) ) {
			wp_update_term_count( $all_cats, 'md_term_category', true );
		}
	}

	// v3.44.93 · 기존 AJAX transient 캐시 모두 무효화
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_term\\_json\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_term\\_json\\_%'" );

	update_option( 'moondental_encyclopedia_seed_v3493', 'done' );
	update_option( 'moondental_encyclopedia_seed_v3493_count', $total, false );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_v3488', 40 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_v3488', 40 );
// v3.37.0 · 마이그레이션은 admin_init로 (프론트 요청마다 get_option 호출 방지 + 관리자 컨텍스트에서만 실행)
add_action( 'admin_init', 'moondental_seed_encyclopedia_categories', 20 );

/**
 * v3.44.109 · 백과사전 확장 · 163개 신규 용어 APPEND
 *   기존 190개 (v3488) 유지 + 신규 삽입만 수행 (delete 없음)
 *   중복 title 체크 후 존재하지 않는 것만 insert
 */
function moondental_seed_encyclopedia_v34109() {
	if ( get_option( 'moondental_encyclopedia_seed_v34109' ) === 'done' ) return;
	$seed_file = MOONDENTAL_DIR . '/inc/encyclopedia-seed-v34109.php';
	if ( ! file_exists( $seed_file ) ) return;

	moondental_seed_encyclopedia_categories_v3488();

	$terms = require $seed_file;
	if ( ! is_array( $terms ) || empty( $terms ) ) return;

	global $wpdb;

	// 기존 md_term title 목록 (중복 방지)
	$existing_titles = $wpdb->get_col( "SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'md_term' AND post_status = 'publish'" );
	$existing_set = array();
	foreach ( (array) $existing_titles as $t ) $existing_set[ trim( (string) $t ) ] = true;

	$inserted = 0;
	$skipped_dup = 0;

	foreach ( $terms as $t ) {
		if ( empty( $t['title'] ) || empty( $t['body'] ) ) continue;
		$title = trim( (string) $t['title'] );
		if ( isset( $existing_set[ $title ] ) ) {
			$skipped_dup++;
			continue;
		}
		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => $t['excerpt'] ?? '',
			'post_content' => $t['body'],
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			if ( ! empty( $t['cat'] ) ) {
				$term = get_term_by( 'slug', $t['cat'], 'md_term_category' );
				if ( $term ) {
					wp_set_object_terms( $post_id, array( $term->term_id ), 'md_term_category' );
				}
			}
			$existing_set[ $title ] = true;
			$inserted++;
		}
	}

	// 카테고리 count 캐시 재계산
	if ( function_exists( 'wp_update_term_count' ) ) {
		$all_cats = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( is_array( $all_cats ) ) {
			wp_update_term_count( $all_cats, 'md_term_category', true );
		}
	}

	// AJAX transient 캐시 무효화
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_term\\_json\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_term\\_json\\_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_terms\\_all\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_terms\\_all\\_%'" );

	update_option( 'moondental_encyclopedia_seed_v34109', 'done' );
	update_option( 'moondental_encyclopedia_seed_v34109_count', $inserted, false );
	update_option( 'moondental_encyclopedia_seed_v34109_skipped', $skipped_dup, false );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_v34109', 45 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_v34109', 45 );

/**
 * v3.44.200 · 백과사전 확장 · 85개 신규 용어 APPEND
 *   기존 353개(v3488 190 + v34109 163) 유지 · 중복 title 은 건너뛰고 신규만 insert.
 *   카테고리는 v3.44.110 대통합 7종 슬러그를 그대로 사용하므로 재매핑이 필요 없다.
 */
function moondental_seed_encyclopedia_v344200() {
	if ( get_option( 'moondental_encyclopedia_seed_v344200' ) === 'done' ) return;
	$seed_file = MOONDENTAL_DIR . '/inc/encyclopedia-seed-v344200.php';
	if ( ! file_exists( $seed_file ) ) return;

	// 대통합 카테고리(7종)가 아직 없으면 먼저 생성
	if ( function_exists( 'moondental_seed_encyclopedia_categories_v34110' ) ) {
		moondental_seed_encyclopedia_categories_v34110();
	}

	$terms = require $seed_file;
	if ( ! is_array( $terms ) || empty( $terms ) ) return;

	global $wpdb;

	$existing_titles = $wpdb->get_col( "SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'md_term' AND post_status = 'publish'" );
	$existing_set = array();
	foreach ( (array) $existing_titles as $t ) $existing_set[ trim( (string) $t ) ] = true;

	$inserted    = 0;
	$skipped_dup = 0;

	foreach ( $terms as $t ) {
		if ( empty( $t['title'] ) || empty( $t['body'] ) ) continue;
		$title = trim( (string) $t['title'] );
		if ( isset( $existing_set[ $title ] ) ) {
			$skipped_dup++;
			continue;
		}
		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => $t['excerpt'] ?? '',
			'post_content' => $t['body'],
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			if ( ! empty( $t['cat'] ) ) {
				$term = get_term_by( 'slug', $t['cat'], 'md_term_category' );
				if ( $term ) {
					wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'md_term_category' );
				}
			}
			$existing_set[ $title ] = true;
			$inserted++;
		}
	}

	// 카테고리 count 캐시 재계산
	if ( function_exists( 'wp_update_term_count' ) ) {
		$all_cats = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( is_array( $all_cats ) ) {
			wp_update_term_count( $all_cats, 'md_term_category', true );
		}
	}

	// AJAX transient 캐시 무효화
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_term\\_json\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_term\\_json\\_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_terms\\_all\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_terms\\_all\\_%'" );

	update_option( 'moondental_encyclopedia_seed_v344200', 'done' );
	update_option( 'moondental_encyclopedia_seed_v344200_count', $inserted, false );
	update_option( 'moondental_encyclopedia_seed_v344200_skipped', $skipped_dup, false );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_v344200', 60 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_v344200', 60 );

/**
 * v3.44.204 · 백과사전 확장 3차 · 66개 신규 용어 APPEND
 *   기존 438개 유지 · 중복 title 은 건너뛰고 신규만 insert.
 */
function moondental_seed_encyclopedia_v344204() {
	if ( get_option( "moondental_encyclopedia_seed_v344204" ) === "done" ) return;
	$seed_file = MOONDENTAL_DIR . "/inc/encyclopedia-seed-v344204.php";
	if ( ! file_exists( $seed_file ) ) return;

	if ( function_exists( "moondental_seed_encyclopedia_categories_v34110" ) ) {
		moondental_seed_encyclopedia_categories_v34110();
	}

	$terms = require $seed_file;
	if ( ! is_array( $terms ) || empty( $terms ) ) return;

	global $wpdb;
	$existing_titles = $wpdb->get_col( "SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'md_term' AND post_status = 'publish'" );
	$existing_set = array();
	foreach ( (array) $existing_titles as $t ) $existing_set[ trim( (string) $t ) ] = true;

	$inserted = 0; $skipped_dup = 0;
	foreach ( $terms as $t ) {
		if ( empty( $t["title"] ) || empty( $t["body"] ) ) continue;
		$title = trim( (string) $t["title"] );
		if ( isset( $existing_set[ $title ] ) ) { $skipped_dup++; continue; }
		$post_id = wp_insert_post( array(
			"post_type"    => "md_term",
			"post_status"  => "publish",
			"post_title"   => $title,
			"post_excerpt" => $t["excerpt"] ?? "",
			"post_content" => $t["body"],
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			if ( ! empty( $t["cat"] ) ) {
				$term = get_term_by( "slug", $t["cat"], "md_term_category" );
				if ( $term ) wp_set_object_terms( $post_id, array( (int) $term->term_id ), "md_term_category" );
			}
			$existing_set[ $title ] = true;
			$inserted++;
		}
	}

	if ( function_exists( "wp_update_term_count" ) ) {
		$all_cats = get_terms( array( "taxonomy" => "md_term_category", "hide_empty" => false, "fields" => "ids" ) );
		if ( is_array( $all_cats ) ) wp_update_term_count( $all_cats, "md_term_category", true );
	}

	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_term\_json\_%' OR option_name LIKE '\_transient\_timeout\_md\_term\_json\_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_terms\_all\_%' OR option_name LIKE '\_transient\_timeout\_md\_terms\_all\_%'" );

	update_option( "moondental_encyclopedia_seed_v344204", "done" );
	update_option( "moondental_encyclopedia_seed_v344204_count", $inserted, false );
	update_option( "moondental_encyclopedia_seed_v344204_skipped", $skipped_dup, false );
}
add_action( "admin_init", "moondental_seed_encyclopedia_v344204", 62 );
add_action( "wp_loaded",  "moondental_seed_encyclopedia_v344204", 62 );

/**
 * v3.44.205 · 백과사전 확장 4차 · 56개 신규 용어 APPEND
 *   기존 504개 유지 · 중복 title 은 건너뛰고 신규만 insert.
 */
function moondental_seed_encyclopedia_v344205() {
	if ( get_option( 'moondental_encyclopedia_seed_v344205' ) === 'done' ) return;
	$seed_file = MOONDENTAL_DIR . '/inc/encyclopedia-seed-v344205.php';
	if ( ! file_exists( $seed_file ) ) return;

	if ( function_exists( 'moondental_seed_encyclopedia_categories_v34110' ) ) {
		moondental_seed_encyclopedia_categories_v34110();
	}

	$terms = require $seed_file;
	if ( ! is_array( $terms ) || empty( $terms ) ) return;

	global $wpdb;
	$existing_titles = $wpdb->get_col( "SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'md_term' AND post_status = 'publish'" );
	$existing_set = array();
	foreach ( (array) $existing_titles as $t ) $existing_set[ trim( (string) $t ) ] = true;

	$inserted = 0;
	$skipped_dup = 0;

	foreach ( $terms as $t ) {
		if ( empty( $t['title'] ) || empty( $t['body'] ) ) continue;
		$title = trim( (string) $t['title'] );
		if ( isset( $existing_set[ $title ] ) ) { $skipped_dup++; continue; }
		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => $t['excerpt'] ?? '',
			'post_content' => $t['body'],
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			if ( ! empty( $t['cat'] ) ) {
				$term = get_term_by( 'slug', $t['cat'], 'md_term_category' );
				if ( $term ) wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'md_term_category' );
			}
			$existing_set[ $title ] = true;
			$inserted++;
		}
	}

	if ( function_exists( 'wp_update_term_count' ) ) {
		$all_cats = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( is_array( $all_cats ) ) wp_update_term_count( $all_cats, 'md_term_category', true );
	}

	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_term\_json\_%' OR option_name LIKE '\_transient\_timeout\_md\_term\_json\_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_terms\_all\_%' OR option_name LIKE '\_transient\_timeout\_md\_terms\_all\_%'" );

	update_option( 'moondental_encyclopedia_seed_v344205', 'done' );
	update_option( 'moondental_encyclopedia_seed_v344205_count', $inserted, false );
	update_option( 'moondental_encyclopedia_seed_v344205_skipped', $skipped_dup, false );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_v344205', 64 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_v344205', 64 );

/**
 * v3.44.210 · 백과사전 확장 5차 · 42개 신규 용어 APPEND
 *   기존 560개 유지 · 중복 title 은 건너뛰고 신규만 insert.
 *   중점: 전신질환 연계 · 노년 치의학 · 수면호흡 · 디지털 보철
 */
function moondental_seed_encyclopedia_v344207() {
	if ( get_option( 'moondental_encyclopedia_seed_v344207' ) === 'done' ) return;
	$seed_file = MOONDENTAL_DIR . '/inc/encyclopedia-seed-v344207.php';
	if ( ! file_exists( $seed_file ) ) return;

	if ( function_exists( 'moondental_seed_encyclopedia_categories_v34110' ) ) {
		moondental_seed_encyclopedia_categories_v34110();
	}

	$terms = require $seed_file;
	if ( ! is_array( $terms ) || empty( $terms ) ) return;

	global $wpdb;
	$existing_titles = $wpdb->get_col( "SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'md_term' AND post_status = 'publish'" );
	$existing_set = array();
	foreach ( (array) $existing_titles as $t ) $existing_set[ trim( (string) $t ) ] = true;

	$inserted = 0;
	$skipped_dup = 0;

	foreach ( $terms as $t ) {
		if ( empty( $t['title'] ) || empty( $t['body'] ) ) continue;
		$title = trim( (string) $t['title'] );
		if ( isset( $existing_set[ $title ] ) ) { $skipped_dup++; continue; }
		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => $t['excerpt'] ?? '',
			'post_content' => $t['body'],
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			if ( ! empty( $t['cat'] ) ) {
				$term = get_term_by( 'slug', $t['cat'], 'md_term_category' );
				if ( $term ) wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'md_term_category' );
			}
			$existing_set[ $title ] = true;
			$inserted++;
		}
	}

	if ( function_exists( 'wp_update_term_count' ) ) {
		$all_cats = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( is_array( $all_cats ) ) wp_update_term_count( $all_cats, 'md_term_category', true );
	}

	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_term\_json\_%' OR option_name LIKE '\_transient\_timeout\_md\_term\_json\_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_terms\_all\_%' OR option_name LIKE '\_transient\_timeout\_md\_terms\_all\_%'" );

	update_option( 'moondental_encyclopedia_seed_v344207', 'done' );
	update_option( 'moondental_encyclopedia_seed_v344207_count', $inserted, false );
	update_option( 'moondental_encyclopedia_seed_v344207_skipped', $skipped_dup, false );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_v344207', 66 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_v344207', 66 );

/**
 * v3.44.110 · 카테고리 대통합 · 15개 → 7개
 *   1) 새 카테고리 7개 생성
 *   2) 기존 카테고리별 all md_term 포스트 → 새 카테고리로 재배정
 *   3) 옛 카테고리 삭제
 */
function moondental_seed_encyclopedia_categories_v34110() {
	if ( get_option( 'moondental_encyclopedia_cats_v34110' ) === 'done' ) return;

	$new_cats = array(
		array( 'name' => '임플란트',           'slug' => 'implant',      'desc' => '임플란트 식립·상악동거상·주위염 등 임플란트 진료 전 과정' ),
		array( 'name' => '교정',               'slug' => 'ortho',        'desc' => '투명교정·브라켓·소아 교정 등 교정 진단·장치·술식' ),
		array( 'name' => '심미·보철',          'slug' => 'esthetic',     'desc' => '라미네이트·크라운·인레이·치아 구조·치과 재료' ),
		array( 'name' => '충치·신경·잇몸치료', 'slug' => 'general',      'desc' => '충치치료·신경치료·잇몸치료·발치·마취·홈케어' ),
		array( 'name' => '구강외과·턱관절',    'slug' => 'surgery',      'desc' => '사랑니·턱관절·구강 질환·구강암·타액선' ),
		array( 'name' => '소아치과',           'slug' => 'pediatric',    'desc' => '유치·소아 진료·실란트·공간유지장치·소아 교정 상담' ),
		array( 'name' => '치과 상식·비용',     'slug' => 'dental-info',  'desc' => '건강보험·본인부담·CBCT·구강스캐너·치과 전문용어' ),
	);
	foreach ( $new_cats as $c ) {
		if ( term_exists( $c['slug'], 'md_term_category' ) ) continue;
		wp_insert_term( $c['name'], 'md_term_category', array(
			'slug'        => $c['slug'],
			'description' => $c['desc'],
		) );
	}

	// 옛 슬러그 → 새 슬러그 매핑
	$remap = array(
		'implant'         => 'implant',
		'ortho'           => 'ortho',
		'tooth-structure' => 'esthetic',
		'materials'       => 'esthetic',
		'treatment'       => 'general',
		'pulp-tooth'      => 'general',
		'periodontal'     => 'general',
		'oral-care'       => 'general',
		'tmj-surgery'     => 'surgery',
		'oral-medicine'   => 'surgery',
		'diseases'        => 'surgery',
		'pediatric-new'   => 'pediatric',
		'equipment'       => 'dental-info',
		'professional'    => 'dental-info',
		'insurance-cost'  => 'dental-info',
	);

	// 새 카테고리 term_id 조회
	$new_term_ids = array();
	foreach ( $new_cats as $c ) {
		$t = get_term_by( 'slug', $c['slug'], 'md_term_category' );
		if ( $t ) $new_term_ids[ $c['slug'] ] = (int) $t->term_id;
	}

	// 모든 md_term 포스트 순회 후 카테고리 재배정
	$reassigned = 0;
	$posts = get_posts( array(
		'post_type'      => 'md_term',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	foreach ( $posts as $pid ) {
		$cur_terms = wp_get_object_terms( $pid, 'md_term_category', array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $cur_terms ) || empty( $cur_terms ) ) continue;
		$new_slugs = array();
		foreach ( $cur_terms as $slug ) {
			$mapped = $remap[ $slug ] ?? null;
			if ( $mapped && isset( $new_term_ids[ $mapped ] ) ) {
				$new_slugs[] = $new_term_ids[ $mapped ];
			}
		}
		if ( ! empty( $new_slugs ) ) {
			$new_slugs = array_values( array_unique( $new_slugs ) );
			wp_set_object_terms( $pid, $new_slugs, 'md_term_category', false );
			$reassigned++;
		}
	}

	// 옛 카테고리 삭제
	$old_slugs = array( 'tooth-structure', 'materials', 'treatment', 'pulp-tooth', 'periodontal', 'oral-care', 'tmj-surgery', 'oral-medicine', 'diseases', 'pediatric-new', 'equipment', 'professional', 'insurance-cost' );
	$deleted = 0;
	foreach ( $old_slugs as $slug ) {
		$t = get_term_by( 'slug', $slug, 'md_term_category' );
		if ( $t ) {
			wp_delete_term( (int) $t->term_id, 'md_term_category' );
			$deleted++;
		}
	}

	// term count 재계산
	if ( function_exists( 'wp_update_term_count' ) ) {
		$all_cats = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( is_array( $all_cats ) ) {
			wp_update_term_count( $all_cats, 'md_term_category', true );
		}
	}

	// AJAX/preload transient 캐시 무효화
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_term\\_json\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_term\\_json\\_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_terms\\_all\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_terms\\_all\\_%'" );

	update_option( 'moondental_encyclopedia_cats_v34110', 'done' );
	update_option( 'moondental_encyclopedia_cats_v34110_reassigned', $reassigned, false );
	update_option( 'moondental_encyclopedia_cats_v34110_deleted', $deleted, false );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_categories_v34110', 50 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_categories_v34110', 50 );

/**
 * v3.44.112 · 카테고리 정리 · 이름 갱신 + 옛 빈 카테고리 삭제
 *   v34110 마이그레이션이 term_exists() 로 인해 이름 갱신 못한 부분 처리
 *   옛 v3350 카테고리 중 매핑 대상 아닌 빈 카테고리 삭제
 */
function moondental_seed_encyclopedia_categories_v34112() {
	if ( get_option( 'moondental_encyclopedia_cats_v34112' ) === 'done' ) return;

	// 이름 갱신 (기존 slug 그대로 · 표시 이름만 수정)
	$rename_map = array(
		'general' => array( 'name' => '일반진료 (충치·신경·잇몸)', 'desc' => '충치치료·신경치료·잇몸치료·발치·마취·홈케어' ),
		// 'surgery' 는 '구강외과·사랑니' 이름 유지 (매복 사랑니 SEO에 유리)
	);
	foreach ( $rename_map as $slug => $info ) {
		$t = get_term_by( 'slug', $slug, 'md_term_category' );
		if ( $t ) {
			wp_update_term( (int) $t->term_id, 'md_term_category', array(
				'name'        => $info['name'],
				'description' => $info['desc'],
			) );
		}
	}

	// v3350 잔재 카테고리 · 빈 것만 삭제
	$legacy_slugs = array( 'preserve', 'periodontics', 'aesthetic', 'prosthetics', 'tmj', 'prevention' );
	$deleted = 0;
	foreach ( $legacy_slugs as $slug ) {
		$t = get_term_by( 'slug', $slug, 'md_term_category' );
		if ( ! $t ) continue;
		// 안전 · 포스트 있으면 삭제하지 않음
		if ( (int) $t->count > 0 ) continue;
		wp_delete_term( (int) $t->term_id, 'md_term_category' );
		$deleted++;
	}

	// count 재계산
	if ( function_exists( 'wp_update_term_count' ) ) {
		$all_cats = get_terms( array( 'taxonomy' => 'md_term_category', 'hide_empty' => false, 'fields' => 'ids' ) );
		if ( is_array( $all_cats ) ) {
			wp_update_term_count( $all_cats, 'md_term_category', true );
		}
	}

	// 캐시 무효화
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_term\\_json\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_term\\_json\\_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_md\\_terms\\_all\\_%' OR option_name LIKE '\\_transient\\_timeout\\_md\\_terms\\_all\\_%'" );

	update_option( 'moondental_encyclopedia_cats_v34112', 'done' );
	update_option( 'moondental_encyclopedia_cats_v34112_deleted', $deleted, false );
}
add_action( 'admin_init', 'moondental_seed_encyclopedia_categories_v34112', 55 );
add_action( 'wp_loaded',  'moondental_seed_encyclopedia_categories_v34112', 55 );


/* ============================================================
 * 3. 초성 헬퍼 · 한글 초성 추출 (아카이브 필터용)
 * ========================================================== */
function moondental_hangul_initial( $str ) {
	$str = trim( (string) $str );
	if ( $str === '' ) return '';
	// 첫 문자
	$first = mb_substr( $str, 0, 1, 'UTF-8' );
	$code  = 0;
	// UTF-8 → codepoint
	$bytes = unpack( 'C*', $first );
	if ( count( $bytes ) === 1 ) {
		$code = $bytes[1];
	} elseif ( count( $bytes ) === 2 ) {
		$code = ( ( $bytes[1] & 0x1F ) << 6 ) | ( $bytes[2] & 0x3F );
	} elseif ( count( $bytes ) === 3 ) {
		$code = ( ( $bytes[1] & 0x0F ) << 12 ) | ( ( $bytes[2] & 0x3F ) << 6 ) | ( $bytes[3] & 0x3F );
	} elseif ( count( $bytes ) === 4 ) {
		$code = ( ( $bytes[1] & 0x07 ) << 18 ) | ( ( $bytes[2] & 0x3F ) << 12 ) | ( ( $bytes[3] & 0x3F ) << 6 ) | ( $bytes[4] & 0x3F );
	}
	// 한글 음절 (가~힣): U+AC00 ~ U+D7A3
	if ( $code < 0xAC00 || $code > 0xD7A3 ) {
		return mb_strtoupper( $first, 'UTF-8' );
	}
	$initials = array( 'ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ' );
	$idx = intdiv( $code - 0xAC00, 588 );
	return $initials[ $idx ] ?? '';
}
/* 초성 매칭 그룹 (쌍자음 → 단자음으로 통합) */
function moondental_initial_groups() {
	return array( 'ㄱ', 'ㄴ', 'ㄷ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅅ', 'ㅇ', 'ㅈ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ' );
}
function moondental_initial_group( $ch ) {
	$map = array( 'ㄲ'=>'ㄱ', 'ㄸ'=>'ㄷ', 'ㅃ'=>'ㅂ', 'ㅆ'=>'ㅅ', 'ㅉ'=>'ㅈ' );
	return $map[ $ch ] ?? $ch;
}


/* ============================================================
 * 4. 초기 시드 용어 (마이그레이션 · 첫 활성화 시 20개 예시)
 *    이후 사용자가 wp-admin → 📖 치과사전에서 추가·편집.
 * ========================================================== */
// v3.37.0 · admin_init로 이동
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_encyclopedia_seed_v3350' ) === 'done' ) return;

	$terms = array(
		array( 'title' => '임플란트', 'cat' => 'implant', 'excerpt' => '치아 뿌리 역할을 하는 인공 치근을 잇몸뼈에 심고 그 위에 인공 치아를 만드는 시술', 'body' => '<p>임플란트는 발치 후 잇몸뼈에 <strong>인공 치근(픽스처)</strong>을 식립하고, 뼈와의 골유착이 이루어진 후 그 위에 <strong>지대주(어버트먼트)</strong>와 <strong>크라운</strong>을 장착하는 3단계 치료입니다.</p><p>자연치아와 가장 유사한 저작 기능을 회복할 수 있으며, 관리에 따라 10~20년 이상 유지 가능합니다.</p>' ),
		array( 'title' => '지대주', 'cat' => 'implant', 'excerpt' => '임플란트 픽스처와 최종 크라운을 연결하는 중간 부품 (어버트먼트)', 'body' => '<p>지대주(어버트먼트)는 임플란트 시스템에서 <strong>픽스처와 크라운을 연결</strong>하는 핵심 부품입니다.</p><p>공장 규격 <strong>기성 지대주</strong>와 환자별 <strong>맞춤형 지대주(Custom Abutment)</strong>가 있으며, 문치과병원은 맞춤형 지대주만 사용해 잇몸 형태에 최적화된 보철을 제작합니다.</p>' ),
		array( 'title' => '골유착', 'cat' => 'implant', 'excerpt' => '임플란트 표면이 잇몸뼈와 화학적·구조적으로 결합되는 과정', 'body' => '<p>골유착(Osseointegration)은 티타늄 임플란트 표면과 살아있는 골 조직이 <strong>세포 수준에서 직접 결합</strong>되는 현상입니다.</p><p>보통 3~6개월 소요되며, UV·칼슘 이온 표면처리 등으로 골유착 속도를 앞당길 수 있습니다.</p>' ),
		array( 'title' => 'CBCT', 'cat' => 'general', 'excerpt' => '3D 콘빔 컴퓨터 단층촬영 · 치과 정밀 진단의 표준 장비', 'body' => '<p>CBCT(Cone Beam Computed Tomography)는 <strong>3차원 X-ray 촬영 장비</strong>로 치아·잇몸뼈·신경관·상악동을 입체적으로 확인할 수 있습니다.</p><p>임플란트 위치 설계, 매복 사랑니 신경 위치 확인, 근관치료 분지 파악 등에 필수적입니다. 일반 X-ray 대비 방사선량은 CT의 1/10 수준.</p>' ),

		array( 'title' => '투명교정', 'cat' => 'ortho', 'excerpt' => '눈에 잘 띄지 않는 투명 플라스틱 장치를 이용한 교정 (인비절라인·슈어스마일 등)', 'body' => '<p>투명교정은 개인 맞춤 제작된 <strong>투명 얼라이너</strong>를 2주 간격으로 교체하며 치아를 점진적으로 이동시키는 방법입니다.</p><p>탈부착이 가능해 식사·양치가 편하며, 심미성이 뛰어납니다. 대표 브랜드: 슈어스마일(SureSmile), 인비절라인.</p>' ),
		array( 'title' => '설측교정', 'cat' => 'ortho', 'excerpt' => '치아 안쪽(혀 쪽)에 브라켓을 부착해 밖에서 보이지 않게 하는 교정', 'body' => '<p>설측교정은 <strong>치아의 혀 쪽 면</strong>에 브라켓을 붙여 앞에서는 보이지 않는 교정 방법입니다.</p><p>초기 발음·불편감이 있을 수 있지만 심미성이 최고 수준입니다.</p>' ),

		array( 'title' => '신경치료', 'cat' => 'preserve', 'excerpt' => '치아 속 염증·괴사된 신경을 제거하고 근관을 깨끗이 소독·충전하는 치료', 'body' => '<p>신경치료(근관치료·Endodontics)는 충치가 신경에 이르러 심한 통증·염증이 생긴 치아를 <strong>발치 없이 보존</strong>하기 위한 치료입니다.</p><p>1) 신경 제거 → 2) 근관 세척·소독 → 3) 영구 충전 → 4) 크라운 마무리 순으로 진행되며 통상 2~4회 내원. CBCT 3D 진단과 NiTi 회전 파일로 정밀도가 크게 향상됐습니다.</p>' ),
		array( 'title' => '재근관치료', 'cat' => 'preserve', 'excerpt' => '이전 신경치료가 실패한 치아를 다시 치료해 발치를 피하는 시술', 'body' => '<p>재근관치료는 다른 곳에서 신경치료를 받았지만 <strong>염증이 재발하거나 실패한 치아</strong>를 발치 없이 다시 살리는 시술입니다.</p><p>기존 충전재를 제거하고 남은 신경관 조직·세균을 재세척한 뒤 재충전합니다. 성공률을 높이려면 정밀 진단과 숙련된 술기가 필요합니다.</p>' ),
		array( 'title' => '치근단수술', 'cat' => 'preserve', 'excerpt' => '근관치료로 해결되지 않는 치근 끝 염증을 외과적으로 제거하는 수술', 'body' => '<p>치근단수술(Apicoectomy)은 일반 근관치료·재근관치료로 해결되지 않는 <strong>치근 끝(치근단) 염증</strong>을 잇몸을 열고 직접 제거하는 마이크로 수술입니다. 구강악안면외과 협진.</p>' ),

		array( 'title' => '스케일링', 'cat' => 'periodontics', 'excerpt' => '치석·치태를 초음파로 제거하는 예방·치주치료의 기본', 'body' => '<p>스케일링은 양치질로 제거되지 않는 <strong>치석과 치태를 초음파 스케일러로 제거</strong>하는 시술입니다.</p><p>만 19세 이상 <strong>연 1회 건강보험 적용</strong>(1월 1일 갱신). 6~12개월 주기 정기 스케일링이 자연치아 평생 보존의 핵심입니다.</p>' ),
		array( 'title' => '치주염', 'cat' => 'periodontics', 'excerpt' => '치석·세균으로 잇몸과 치조골에 만성 염증이 진행되는 질환', 'body' => '<p>치주염은 잇몸염(치은염)에서 진행되어 <strong>치조골까지 파괴</strong>되는 만성 염증 질환입니다.</p><p>증상: 잇몸 출혈·부기, 잇몸 퇴축, 치아 흔들림, 입냄새. 단계별로 스케일링 → 치근활택술 → 치주소파술 → 치주 판막수술로 진행됩니다.</p>' ),
		array( 'title' => 'PDRN 잇몸 주사', 'cat' => 'periodontics', 'excerpt' => 'DNA 단편 성분으로 잇몸 재생·염증 완화를 촉진하는 주사', 'body' => '<p>PDRN(Polydeoxyribonucleotide)은 <strong>연어 정소에서 추출한 DNA 단편</strong> 성분으로 조직 재생과 항염 효과가 있습니다.</p><p>잇몸에 주사하면 염증 완화·혈류 개선·재생 촉진 효과가 있으며 인체에 안전합니다.</p>' ),

		array( 'title' => '라미네이트', 'cat' => 'aesthetic', 'excerpt' => '얇은 세라믹 쉘을 앞니 표면에 부착해 색·모양을 개선하는 심미치료', 'body' => '<p>라미네이트는 <strong>0.3~0.5mm 얇은 세라믹 쉘</strong>을 자연치아 표면에 부착해 색·모양·틈을 개선하는 심미 시술입니다.</p><p>최소 삭제 라미네이트(Minimal Prep)는 치아 삭제량을 최소화해 자연치아 보존율이 높습니다. e.max·Empress 등 프리미엄 세라믹 사용.</p>' ),
		array( 'title' => '치아미백', 'cat' => 'aesthetic', 'excerpt' => '고농도 미백제로 치아 내부 색소를 분해해 밝게 만드는 시술', 'body' => '<p>치아미백은 <strong>과산화수소·과산화요소</strong> 기반 미백제를 사용해 치아 표면과 내부의 색소를 분해합니다.</p><p>홈 화이트닝(4주 키트), 1-Day 전문가 미백, 2-Day 전문가 미백, 복합 미백 등 옵션이 있으며 임신·수유 중에는 권장하지 않습니다.</p>' ),
		array( 'title' => '거미스마일', 'cat' => 'aesthetic', 'excerpt' => '웃을 때 잇몸이 3mm 이상 노출되는 상태 · 원인별 맞춤 치료', 'body' => '<p>거미스마일(Gummy Smile)은 웃을 때 <strong>잇몸이 3mm 이상 노출</strong>되는 상태입니다.</p><p>원인: 잇몸 라인이 낮음, 치아 길이가 짧음, 윗입술 근육 과활동, 상악골 과성장. 원인별로 잇몸 성형·크라운 연장·보톡스·양악 협진 등 맞춤 치료가 필요합니다.</p>' ),

		array( 'title' => '지르코니아 크라운', 'cat' => 'prosthetics', 'excerpt' => '강도·심미성이 우수한 고급 세라믹 크라운', 'body' => '<p>지르코니아 크라운은 <strong>산화 지르코늄 소재</strong>의 세라믹 크라운으로 금속과 유사한 강도, 우수한 심미성, 알레르기 없는 생체친화성을 갖습니다.</p><p>어금니·앞니 모두 사용 가능하며 파절 위험이 낮습니다.</p>' ),
		array( 'title' => '금 크라운 (골드)', 'cat' => 'prosthetics', 'excerpt' => '내구성과 정밀도가 최고 수준인 전통 보철 재료', 'body' => '<p>금 크라운은 <strong>강도·연성·정밀 적합도</strong>가 가장 뛰어난 전통 보철입니다.</p><p>어금니에 적합하며 마모가 자연치아와 유사해 반대편 치아 보호에도 유리합니다. 심미성이 떨어져 앞니에는 권장하지 않습니다.</p>' ),

		array( 'title' => '사랑니', 'cat' => 'surgery', 'excerpt' => '어금니 가장 안쪽에 나는 3번째 큰 어금니 · 매복 시 발치 권장', 'body' => '<p>사랑니(제3대구치)는 보통 <strong>17~25세경 마지막으로 나는 어금니</strong>입니다.</p><p>공간 부족·비뚤어진 방향으로 매복되는 경우가 많아, 청소 어려움·주변 치아 압박·낭종 위험이 있어 <strong>CBCT 진단 후 발치를 권장</strong>하는 경우가 많습니다.</p>' ),
		array( 'title' => '매복치', 'cat' => 'surgery', 'excerpt' => '잇몸·뼈 속에 갇혀 나오지 못한 치아', 'body' => '<p>매복치는 정상적인 위치로 나오지 못하고 <strong>잇몸이나 잇몸뼈 속에 파묻힌 치아</strong>입니다.</p><p>사랑니가 가장 흔하며, 신경관과의 위치를 CBCT로 정밀 확인해 안전하게 발치합니다. 진정요법 병행 가능.</p>' ),

		array( 'title' => '턱관절 장애', 'cat' => 'tmj', 'excerpt' => '턱관절에 통증·소리·개구장애가 나타나는 복합 질환', 'body' => '<p>턱관절 장애(TMD)는 <strong>턱관절과 주변 근육</strong>에 문제가 생겨 나타나는 증상군입니다.</p><p>증상: 입 벌릴 때 딱딱 소리, 턱 주변 통증, 두통, 입이 잘 벌어지지 않음. 스플린트·물리치료·이갈이 습관 교정 등 보존적 치료 우선.</p>' ),
		array( 'title' => '이갈이 (브럭시즘)', 'cat' => 'tmj', 'excerpt' => '수면 중 무의식적으로 이를 갈거나 꽉 무는 습관', 'body' => '<p>이갈이(브럭시즘)는 주로 수면 중 <strong>무의식적으로 이를 갈거나 꽉 무는 습관</strong>입니다.</p><p>치아 마모, 턱관절 장애, 두통·목 통증을 유발할 수 있으며 <strong>맞춤 마우스가드(스플린트)</strong>로 치아를 보호합니다.</p>' ),

		array( 'title' => '실란트 (홈메우기)', 'cat' => 'prevention', 'excerpt' => '어금니 씹는 면의 홈을 메워 충치를 예방하는 시술', 'body' => '<p>실란트는 어금니 씹는 면의 <strong>깊은 홈(fissure)</strong>을 특수 재료로 메워 음식물 끼임과 충치 시작을 차단하는 시술입니다.</p><p>만 18세 이하는 제1·2 큰어금니에 <strong>건강보험 적용</strong>(본인부담 약 21,700원). 통증 없이 10분 이내 시술.</p>' ),
		array( 'title' => '불소도포', 'cat' => 'prevention', 'excerpt' => '고농도 불소로 치아 재광화·충치 예방 효과', 'body' => '<p>불소도포는 <strong>고농도 불소</strong>를 치아 표면에 도포해 법랑질을 강화하고 충치를 예방하는 시술입니다.</p><p>어린이는 3개월~1년 주기, 성인은 시린 증상 완화·충치 재발 예방 목적으로 권장됩니다.</p>' ),
		array( 'title' => '에어플로우', 'cat' => 'prevention', 'excerpt' => '미세 분말로 색소 침착·바이오필름을 정밀 제거하는 시술', 'body' => '<p>에어플로우(Air Flow)는 고운 <strong>미세 분말과 물</strong>을 동시 분사해 치아 표면과 잇몸 라인의 색소 침착과 바이오필름을 제거하는 시술입니다.</p><p>커피·와인·담배 착색 제거에 특히 효과적이며, 임플란트 주변 관리에도 안전합니다.</p>' ),

		array( 'title' => '치아 어금니 · 앞니', 'cat' => 'general', 'excerpt' => '성인 영구치 28~32개의 위치·기능별 명칭', 'body' => '<p>성인 영구치는 총 <strong>28~32개</strong>(사랑니 포함)입니다.</p><ul><li><strong>앞니(절치)</strong>: 위/아래 4개씩 · 음식 자르기</li><li><strong>송곳니(견치)</strong>: 각 1개씩 · 음식 찢기</li><li><strong>작은어금니(소구치)</strong>: 각 2개씩 · 음식 부수기</li><li><strong>큰어금니(대구치)</strong>: 각 2~3개씩 · 음식 갈기</li></ul>' ),
		array( 'title' => '치석 · 치태', 'cat' => 'general', 'excerpt' => '치아에 쌓이는 세균성 침착물의 두 단계', 'body' => '<p><strong>치태(플라크)</strong>는 음식물·세균이 뭉친 <strong>부드러운 막</strong>으로 양치질로 제거 가능합니다.</p><p><strong>치석</strong>은 치태가 침에 있는 미네랄과 결합해 <strong>딱딱하게 굳은 것</strong>으로 양치로는 제거되지 않고 <strong>스케일링</strong>이 필요합니다.</p>' ),
		array( 'title' => '충치 (우식증)', 'cat' => 'preserve', 'excerpt' => '치아를 부식시키는 세균성 질환 · 진행 단계별 치료', 'body' => '<p>충치(치아우식증)는 <strong>뮤탄스균이 만든 산이 치아를 부식</strong>시키는 세균성 질환입니다.</p><p>단계별 치료: 초기(불소·실란트) → 중기(레진 충전) → 진행(세라믹 인레이·크라운) → 심부(신경치료) → 광범위(발치+임플란트).</p>' ),
	);

	$total = 0;
	foreach ( $terms as $t ) {
		// 중복 체크 (제목으로) · WP 6.2+ 호환
		$existing = get_posts( array(
			'post_type'      => 'md_term',
			'title'          => $t['title'],
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( ! empty( $existing ) ) continue;

		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $t['title'],
			'post_excerpt' => $t['excerpt'],
			'post_content' => $t['body'],
			'post_name'    => sanitize_title( $t['title'] ),
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			$term = get_term_by( 'slug', $t['cat'], 'md_term_category' );
			if ( $term ) {
				wp_set_object_terms( $post_id, array( $term->term_id ), 'md_term_category' );
			}
			$total++;
		}
	}

	update_option( 'moondental_encyclopedia_seed_v3350', 'done' );
}, 30 );
