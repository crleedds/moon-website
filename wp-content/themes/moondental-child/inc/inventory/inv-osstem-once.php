<?php
/** 1회용 (2026-10-08) — 재료실에서 「오스템」이 들어간 업체 · 품목 · 분류를 보기만 한다 (환자 정보 아님). 끝나면 지운다. 열쇠(X-MD-Survey-Key) 필요. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'rest_api_init', function () {
	register_rest_route( 'md-inv/v1', '/osstem', array(
		'methods' => 'GET',
		'permission_callback' => function ( $req ) { $key = (string) $req->get_header( 'x-md-survey-key' ); $want = (string) get_option( 'md_survey_api_key' ); return '' !== $want && '' !== $key && hash_equals( $want, $key ); },
		'callback' => function () {
			global $wpdb; $t = md_inv_t();
			$vend = $wpdb->get_results( "SELECT v.id, v.name, v.prepaid, v.active, v.goods, (SELECT COUNT(*) FROM {$t['item']} i WHERE i.vendor_id = v.id AND i.active = 1) n FROM {$t['vendor']} v WHERE v.name LIKE '%오스템%' OR v.goods LIKE '%오스템%' ORDER BY v.id", ARRAY_A );
			$items = $wpdb->get_results( "SELECT i.id, i.name, i.active, i.price, i.unit, v.name vendor, v.prepaid, c1.name cat1, c2.name cat2, c3.name cat3 FROM {$t['item']} i LEFT JOIN {$t['vendor']} v ON v.id = i.vendor_id LEFT JOIN {$t['cat']} c1 ON c1.id = i.cat1 LEFT JOIN {$t['cat']} c2 ON c2.id = i.cat2 LEFT JOIN {$t['cat']} c3 ON c3.id = i.cat3 WHERE i.name LIKE '%오스템%' OR v.name LIKE '%오스템%' OR c2.name LIKE '%오스템%' OR c3.name LIKE '%오스템%' ORDER BY v.name, i.name", ARRAY_A );
			$cats = $wpdb->get_results( "SELECT id, parent_id, level, name, active FROM {$t['cat']} WHERE name LIKE '%오스템%' ORDER BY level, id", ARRAY_A );
			$dep = $wpdb->get_results( "SELECT vendor_id, COUNT(*) n FROM {$t['deposit']} GROUP BY vendor_id", ARRAY_A );
			return rest_ensure_response( array( 'vendors' => $vend, 'items' => $items, 'cats' => $cats, 'deposits_by_vendor' => $dep ) );
		},
	) );
} );
