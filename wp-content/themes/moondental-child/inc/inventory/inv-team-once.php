<?php
/**
 * 1회용 (2026-10-07 원장 지시) — 팀 「서비스지원실」을 9층 · 10층 · 11층 서비스지원실로 나눈다. 끝나면 이 파일을 지운다.
 *   지난 기록(신청 · 출고)은 옛 「서비스지원실」 그대로 두고, 옛 팀은 고를 수 없게(사용 안 함) 한다.
 *   GET 보기만 · POST 반영 (여러 번 해도 같음). 병원 PC 연동 열쇠가 있어야 한다.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function md_inv_team_split_run( $apply ) {
	global $wpdb;
	$t   = md_inv_t();
	$old = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['team']} WHERE name = %s", '서비스지원실' ) );
	$out = array( 'apply' => $apply, 'teams_before' => $wpdb->get_results( "SELECT id, name, sort_no, active, in_stats FROM {$t['team']} ORDER BY sort_no, id", ARRAY_A ) );
	if ( ! $old ) { $out['error'] = '「서비스지원실」 팀이 없습니다.'; return $out; }
	$out['old'] = array( 'id' => (int) $old->id, 'req' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['req']} WHERE team_id = %d", $old->id ) ), 'ledger' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['ledger']} WHERE team_id = %d", $old->id ) ), 'pending' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['req']} WHERE team_id = %d AND status = 'pending'", $old->id ) ), 'users' => count( get_users( array( 'meta_key' => 'md_inv_team', 'meta_value' => (int) $old->id, 'fields' => 'ID' ) ) ) );
	$made = array();
	foreach ( array( '9층 서비스지원실', '10층 서비스지원실', '11층 서비스지원실' ) as $i => $name ) {
		$ex = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['team']} WHERE name = %s", $name ) );
		if ( $ex ) { $made[] = $name . ' (있음 #' . $ex . ')'; continue; }
		$made[] = $name . ' (새로)';
		if ( $apply ) {
			$wpdb->insert( $t['team'], array( 'name' => $name, 'sort_no' => (int) $old->sort_no + 1 + $i, 'active' => 1, 'in_stats' => (int) $old->in_stats ) );
			md_inv_log( '팀 추가', $name );
		}
	}
	$out['new'] = $made;
	if ( $apply && (int) $old->active ) {
		$wpdb->update( $t['team'], array( 'active' => 0 ), array( 'id' => (int) $old->id ) );
		md_inv_log( '팀 사용 안 함', '서비스지원실 (9 · 10 · 11층으로 나눔)' );
	}
	if ( $apply ) { $out['teams_after'] = $wpdb->get_results( "SELECT id, name, sort_no, active FROM {$t['team']} ORDER BY sort_no, id", ARRAY_A ); }
	return $out;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'md-inv/v1', '/team-split', array(
		'methods'             => array( 'GET', 'POST' ),
		'permission_callback' => function ( $req ) {
			$key = (string) $req->get_header( 'x-md-survey-key' ); $want = (string) get_option( 'md_survey_api_key' );
			return '' !== $want && '' !== $key && hash_equals( $want, $key );
		},
		'callback'            => function ( $req ) { return rest_ensure_response( md_inv_team_split_run( 'POST' === $req->get_method() ) ); },
	) );
} );
