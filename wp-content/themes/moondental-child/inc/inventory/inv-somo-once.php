<?php
/**
 * 1회용 (2026-10-07) — 「소모품.xlsx」(원장) 를 재료실 건별결제품목 › 소모품 에 넣는다. 끝나면 이 파일을 지운다.
 *   GET  /wp-json/md-inv/v1/somo  무엇을 할지 보기만 (바꾸지 않음)
 *   POST /wp-json/md-inv/v1/somo  그대로 반영 (같은 이름이 이미 있으면 새로 만들지 않음 — 여러 번 눌러도 같음)
 *   병원 PC 연동 열쇠(X-MD-Survey-Key)가 있어야 한다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_inv_somo_list() {
	return array(
		array( '자판기', '재고 소진 시 각 층 확인 후 자판기 업체에 전화 주문 → 업체 방문 · 전 층 청소 · 불출', array( '원두커피', '커피믹스', '코코아분말', '종이컵' ) ),
		array( '의약품', '약국 구매 → 불출', array( '에이스밴드', '데일밴드', '방수밴드', '바세린', '페리덱스', '오라메디', '후시딘' ) ),
		array( '행정실 구매', '행정실 지출결의서 → 행정실 입금(가글) · 카드결제(혈당, 인터넷 주문) 후 택배 수령', array( '코로로 가글 1box (100개)', '혈당체크지', '혈당채혈침' ) ),
		array( '생활 · 주방용품', '다이소 · 마트 · 문구점 · 인터넷 구매 → 불출', array( '차 종류 (보리차 · 둥굴레차 · 녹차 · 옥수수수염차)', '설탕', '철수세미', '락스', '스펀지 수세미', '쿠킹호일', '랩', '면봉', '테프론', '고무장갑', '지퍼백', '위생비닐팩', '세탁세제', '홈스타', '뿌리는 락스 스프레이', '일회용 비닐장갑', '펑크린 (배수구 클리너)', '주방세제', '빨래망', '운동화솔', '멀티탭', '물스퀴지', '페브리즈', '썬가스 (토치 리필용)', '쓰레기 종량제봉투 (기공실)', '거울세정제', '과탄산소다', '치약 (TBI실)', '핸드크림', '분무기', '매직블럭', '베이킹소다', '접착식 후크' ) ),
		array( '문구류', '다이소 · 마트 · 문구점 · 인터넷 구매 → 불출', array( '수정테이프', '수정펜', '볼펜 · 삼색볼펜', '포스트잇 (대 · 중 · 소)', '형광펜', '칼', '가위', '자', '수첩', '네임펜', '서류철', '클립 (대 · 중 · 소)', '고무줄', '스카치테이프', '박스테이프', '양면테이프', '청테이프', '순간접착제', '스테이플러심', '건전지 (AAA)', '건전지 (AA)', '충전기 줄 (C타입)', '견출지', '마우스패드', '마우스' ) ),
	);
}

function md_inv_somo_norm( $s ) { return preg_replace( '/[\s·,()（）\[\]]+/u', '', mb_strtolower( (string) $s ) ); }

function md_inv_somo_run( $apply ) {
	global $wpdb;
	$t   = md_inv_t();
	$out = array( 'apply' => $apply, 'cat1' => null, 'cat2' => null, 'cat3' => array(), 'new' => array(), 'moved' => array(), 'same' => array(), 'elsewhere' => array(), 'cat2_items_before' => array() );
	$c1 = null;
	foreach ( md_inv_cats_of( 1, null, false ) as $c ) { if ( false !== mb_strpos( $c->name, '건별' ) ) { $c1 = $c; } }
	if ( ! $c1 ) { $out['error'] = '건별결제품목 분류를 찾지 못했습니다.'; $out['level1'] = wp_list_pluck( md_inv_cats_of( 1, null, false ), 'name' ); return $out; }
	$out['cat1'] = $c1->name;
	$c2 = null;
	foreach ( md_inv_cats_of( 2, (int) $c1->id, false ) as $c ) { if ( '소모품' === trim( $c->name ) ) { $c2 = $c; } }
	$out['level2_under_cat1'] = wp_list_pluck( md_inv_cats_of( 2, (int) $c1->id, false ), 'name' );
	$c2id = $c2 ? (int) $c2->id : 0;
	$out['cat2'] = $c2 ? '있음 #' . $c2id . ( (int) $c2->active ? '' : ' (꺼져 있음)' ) : '없음 → 새로 만듦';
	if ( $c2id ) {
		$out['level3_under_cat2'] = wp_list_pluck( md_inv_cats_of( 3, $c2id, false ), 'name' );
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, name, active, cat3 FROM {$t['item']} WHERE cat2 = %d ORDER BY name", $c2id ) ) as $it ) { $out['cat2_items_before'][] = $it->name . ( (int) $it->active ? '' : ' (숨김)' ); }
	}
	if ( $apply ) {
		if ( ! $c2id ) { $c2id = md_inv_somo_cat( '소모품', 2, (int) $c1->id, '' ); }
		elseif ( ! (int) $c2->active ) { $wpdb->update( $t['cat'], array( 'active' => 1 ), array( 'id' => $c2id ) ); }
		if ( ! $c2id ) { $out['error'] = '소모품 분류를 만들지 못했습니다.'; return $out; }
	}
	$all = (array) $wpdb->get_results( "SELECT id, name, cat1, cat2, cat3, active FROM {$t['item']}" );
	$idx = array();
	foreach ( $all as $it ) { $idx[ md_inv_somo_norm( $it->name ) ][] = $it; }
	foreach ( md_inv_somo_list() as $g ) {
		list( $gname, $gnote, $items ) = $g;
		$c3id = 0;
		if ( $c2id ) { foreach ( md_inv_cats_of( 3, $c2id, false ) as $c ) { if ( $c->name === $gname ) { $c3id = (int) $c->id; } } }
		$out['cat3'][] = $gname . ( $c3id ? ' (있음)' : ' (새로)' );
		if ( $apply && ! $c3id ) {
			$c3id = md_inv_somo_cat( $gname, 3, $c2id, $gnote );
		}
		foreach ( $items as $name ) {
			$hits = $idx[ md_inv_somo_norm( $name ) ] ?? array();
			$in   = null; $else = null;
			foreach ( $hits as $h ) { if ( $c2id && (int) $h->cat2 === $c2id ) { $in = $h; } else { $else = $h; } }
			if ( $in ) {
				if ( (int) $in->cat3 !== $c3id || ! (int) $in->active ) {
					$out['moved'][] = $name;
					if ( $apply ) { $wpdb->update( $t['item'], array( 'cat3' => $c3id, 'active' => 1, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $in->id ) ); }
				} else { $out['same'][] = $name; }
				continue;
			}
			if ( $else ) { $out['elsewhere'][] = $name . ' ← 다른 분류에 「' . $else->name . '」 #' . $else->id . ( (int) $else->active ? '' : ' (숨김)' ) . ' · 그래도 소모품에 새로 만듦'; }
			$out['new'][] = $gname . ' / ' . $name;
			if ( $apply ) {
				$r = md_inv_item_save( 0, array( 'name' => $name, 'unit' => '개', 'price' => 0, 'cat1' => (int) $c1->id, 'cat2' => $c2id, 'cat3' => $c3id ) );
				if ( is_wp_error( $r ) ) { $out['errors'][] = $name . ': ' . $r->get_error_message(); }
				else { $wpdb->update( $t['item'], array( 'cat1' => (int) $c1->id, 'cat2' => $c2id, 'cat3' => $c3id ), array( 'id' => (int) $r ) ); } /* 새 분류는 분류 캐시에 아직 없어서 직접 */
			}
		}
	}
	$out['counts'] = array( 'new' => count( $out['new'] ), 'moved' => count( $out['moved'] ), 'same' => count( $out['same'] ) );
	return $out;
}

/** 분류 하나 만들기 (md_inv_cats() 캐시를 거치지 않고) */
function md_inv_somo_cat( $name, $level, $parent, $note ) {
	global $wpdb;
	$t = md_inv_t( 'cat' );
	$sort = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(sort_no),0) FROM $t WHERE level = %d AND parent_id = %d", $level, $parent ) ) + 10;
	$wpdb->insert( $t, array( 'name' => $name, 'level' => $level, 'parent_id' => $parent, 'note' => mb_substr( $note, 0, 255 ), 'sort_no' => $sort, 'active' => 1 ) );
	$id = (int) $wpdb->insert_id;
	md_inv_log( '분류 추가', $name );
	return $id;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-inv/v1', '/somo', array(
		'methods'             => array( 'GET', 'POST' ),
		'permission_callback' => function ( $req ) {
			$key = (string) $req->get_header( 'x-md-survey-key' ); $want = (string) get_option( 'md_survey_api_key' );
			return '' !== $want && '' !== $key && hash_equals( $want, $key );
		},
		'callback'            => function ( $req ) {
			if ( ! function_exists( 'md_inv_item_save' ) ) { require_once MD_INV_DIR . '/inv-admin.php'; }
			return rest_ensure_response( md_inv_somo_run( 'POST' === $req->get_method() ) );
		},
	) );
} );
