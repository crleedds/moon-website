<?php
/** 1회용 (2026-10-08 원장 지시) — 선납차감품목 아래 품목군을 오스템임플란트 · 오스템재료 · 포인트임플란트 · 메가젠임플란트 · 바이오템임플란트 · 스테리오스임플란트 로. GET 보기 · POST 반영. 뒤에 지운다. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'rest_api_init', function () {
	register_rest_route( 'md-inv/v1', '/prepaid-cats', array(
		'methods' => array( 'GET', 'POST' ),
		'permission_callback' => function ( $req ) { $key = (string) $req->get_header( 'x-md-survey-key' ); $want = (string) get_option( 'md_survey_api_key' ); return '' !== $want && '' !== $key && hash_equals( $want, $key ); },
		'callback' => function ( $req ) {
			global $wpdb; $t = md_inv_t();
			$c1 = $wpdb->get_row( "SELECT * FROM {$t['cat']} WHERE level = 1 AND name LIKE '%선납%'" );
			if ( ! $c1 ) { return rest_ensure_response( array( 'error' => '선납차감품목 없음' ) ); }
			$list = function () use ( $wpdb, $t, $c1 ) {
				return $wpdb->get_results( $wpdb->prepare( "SELECT c.id, c.name, c.sort_no, c.active, (SELECT COUNT(*) FROM {$t['item']} i WHERE i.cat2 = c.id) n_items, (SELECT GROUP_CONCAT(CONCAT(s.name, IF(s.active=1,'',' (꺼짐)')) ORDER BY s.sort_no SEPARATOR ' · ') FROM {$t['cat']} s WHERE s.parent_id = c.id AND s.level = 3) subs FROM {$t['cat']} c WHERE c.level = 2 AND c.parent_id = %d ORDER BY c.sort_no, c.id", $c1->id ), ARRAY_A );
			};
			$vend = $wpdb->get_results( "SELECT id, name, prepaid, active FROM {$t['vendor']} WHERE prepaid = 1 ORDER BY sort_no, id", ARRAY_A );
			$done = array();
			if ( 'POST' === $req->get_method() ) {
				$want = array( '오스템임플란트', '오스템재료', '포인트임플란트', '메가젠임플란트', '바이오템임플란트', '스테리오스임플란트' );
				/* 1) 조금 전 바꾼 「오스템 (임플란트 · 재료)」는 원래 이름으로, 그 아래 임시 「재료」 세부 분류는 품목이 없으면 지움 */
				$r = $wpdb->get_row( "SELECT * FROM {$t['cat']} WHERE level = 2 AND name = '오스템 (임플란트 · 재료)'" );
				if ( $r ) { $wpdb->update( $t['cat'], array( 'name' => '오스템임플란트' ), array( 'id' => (int) $r->id ) ); $done[] = '이름 되돌림 #' . $r->id;
					$s = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['cat']} WHERE level = 3 AND parent_id = %d AND name = '재료'", $r->id ) );
					if ( $s && ! (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['item']} WHERE cat3 = %d", $s->id ) ) ) { $wpdb->delete( $t['cat'], array( 'id' => (int) $s->id ) ); $done[] = '임시 재료 세부분류 지움 #' . $s->id; }
				}
				$v = $wpdb->get_row( "SELECT * FROM {$t['vendor']} WHERE name = '오스템임플란트'" );
				if ( $v && false !== strpos( (string) $v->goods, '2026-10-08 원장 지시' ) ) { $wpdb->update( $t['vendor'], array( 'goods' => '' ), array( 'id' => (int) $v->id ) ); $done[] = '업체 메모 되돌림'; }
				/* 2) 없는 품목군은 새로 (이름이 같은 것이 꺼져 있으면 켬) — 순서는 원장이 적은 차례 */
				$norm = function ( $n ) { return preg_replace( '/\s+/u', '', (string) $n ); };
				$have = array(); foreach ( $list() as $c ) { $have[ $norm( $c['name'] ) ] = $c; }
				$sort = 10;
				foreach ( $want as $name ) {
					if ( isset( $have[ $norm( $name ) ] ) ) {
						$c = $have[ $norm( $name ) ];
						$wpdb->update( $t['cat'], array( 'sort_no' => $sort, 'active' => 1 ), array( 'id' => (int) $c['id'] ) ); $done[] = '있음 → 순서 ' . $name . ' #' . $c['id'];
					} else {
						$wpdb->insert( $t['cat'], array( 'name' => $name, 'level' => 2, 'parent_id' => (int) $c1->id, 'note' => '', 'sort_no' => $sort, 'active' => 1 ) ); $done[] = '새로 ' . $name . ' #' . $wpdb->insert_id;
					}
					$sort += 10;
				}
				/* 나머지 기존 품목군은 그 뒤로 */
				foreach ( $list() as $c ) { if ( ! in_array( $norm( $c['name'] ), array_map( $norm, $want ), true ) ) { $wpdb->update( $t['cat'], array( 'sort_no' => $sort ), array( 'id' => (int) $c['id'] ) ); $sort += 10; } }
				if ( function_exists( 'md_inv_log' ) ) { md_inv_log( '분류 수정', '선납차감품목 품목군 정리: ' . implode( ' · ', $want ) ); }
			}
			return rest_ensure_response( array( 'cat1' => $c1->name, 'cat2' => $list(), 'prepaid_vendors' => $vend, 'done' => $done ) );
		},
	) );
} );
