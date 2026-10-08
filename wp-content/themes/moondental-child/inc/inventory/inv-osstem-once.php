<?php
/** 1회용 (2026-10-08) — 재료실에서 「오스템」이 들어간 업체 · 품목 · 분류를 보기만 한다 (환자 정보 아님). 끝나면 지운다. 열쇠(X-MD-Survey-Key) 필요. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'rest_api_init', function () {
	register_rest_route( 'md-inv/v1', '/osstem', array(
		'methods' => array( 'GET', 'POST' ),
		'permission_callback' => function ( $req ) { $key = (string) $req->get_header( 'x-md-survey-key' ); $want = (string) get_option( 'md_survey_api_key' ); return '' !== $want && '' !== $key && hash_equals( $want, $key ); },
		'callback' => function ( $req ) {
			global $wpdb; $t = md_inv_t();
			if ( 'POST' === $req->get_method() ) {
				/* 원장 지시 2026-10-08: 선납 업체 오스템은 임플란트뿐 아니라 재료도 — 품목군 「오스템임플란트」→「오스템 (임플란트 · 재료)」, 세부 분류 「재료」 추가, 업체 취급품목 메모 */
				$c2 = $wpdb->get_row( "SELECT * FROM {$t['cat']} WHERE level = 2 AND name = '오스템임플란트'" );
				$done = array();
				if ( $c2 ) {
					$wpdb->update( $t['cat'], array( 'name' => '오스템 (임플란트 · 재료)' ), array( 'id' => (int) $c2->id ) ); $done[] = 'cat2 renamed #' . $c2->id;
					if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['cat']} WHERE level = 3 AND parent_id = %d AND name = '재료'", $c2->id ) ) ) {
						$sort = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(sort_no),0) FROM {$t['cat']} WHERE level = 3 AND parent_id = %d", $c2->id ) ) + 10;
						$wpdb->insert( $t['cat'], array( 'name' => '재료', 'level' => 3, 'parent_id' => (int) $c2->id, 'note' => '오스템 재료 (뼈이식재 · 차폐막 · 기구 등) — 선납금에서 차감', 'sort_no' => $sort, 'active' => 1 ) ); $done[] = 'cat3 재료 #' . $wpdb->insert_id;
					}
				}
				$v = $wpdb->get_row( "SELECT * FROM {$t['vendor']} WHERE name = '오스템임플란트'" );
				if ( $v ) { $wpdb->update( $t['vendor'], array( 'goods' => trim( (string) $v->goods . ( $v->goods ? "
" : '' ) . '임플란트(픽스처 · 어버트먼트 등) + 재료(뼈이식재 · 차폐막 · 기구 등) — 모두 선납금에서 차감 (2026-10-08 원장 지시)' ) ), array( 'id' => (int) $v->id ) ); $done[] = 'vendor goods #' . $v->id; }
				if ( function_exists( 'md_inv_log' ) ) { md_inv_log( '분류 수정', '오스템 (임플란트 · 재료) — 재료 세부 분류 추가' ); }
				return rest_ensure_response( array( 'done' => $done ) );
			}
			$vend = $wpdb->get_results( "SELECT v.id, v.name, v.prepaid, v.active, v.goods, (SELECT COUNT(*) FROM {$t['item']} i WHERE i.vendor_id = v.id AND i.active = 1) n FROM {$t['vendor']} v WHERE v.name LIKE '%오스템%' OR v.goods LIKE '%오스템%' ORDER BY v.id", ARRAY_A );
			$items = $wpdb->get_results( "SELECT i.id, i.name, i.active, i.price, i.unit, v.name vendor, v.prepaid, c1.name cat1, c2.name cat2, c3.name cat3 FROM {$t['item']} i LEFT JOIN {$t['vendor']} v ON v.id = i.vendor_id LEFT JOIN {$t['cat']} c1 ON c1.id = i.cat1 LEFT JOIN {$t['cat']} c2 ON c2.id = i.cat2 LEFT JOIN {$t['cat']} c3 ON c3.id = i.cat3 WHERE i.name LIKE '%오스템%' OR v.name LIKE '%오스템%' OR c2.name LIKE '%오스템%' OR c3.name LIKE '%오스템%' ORDER BY v.name, i.name", ARRAY_A );
			$cats = $wpdb->get_results( "SELECT id, parent_id, level, name, active FROM {$t['cat']} WHERE name LIKE '%오스템%' ORDER BY level, id", ARRAY_A );
			$dep = $wpdb->get_results( "SELECT vendor_id, COUNT(*) n FROM {$t['deposit']} GROUP BY vendor_id", ARRAY_A );
			return rest_ensure_response( array( 'vendors' => $vend, 'items' => $items, 'cats' => $cats, 'deposits_by_vendor' => $dep ) );
		},
	) );
} );
