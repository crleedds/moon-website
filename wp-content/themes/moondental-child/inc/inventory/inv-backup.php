<?php
/**
 * 재고관리 v5 — 백업 · 되돌리기 · 정기 보고서 메일
 *
 * 백업 = 재고관리 표 전부 + 운영 설정을 JSON 으로 묶어 gzip 한 것.
 *   · 매일 자동으로 한 벌 (보관 개수는 설정)
 *   · 관리자가 「지금 백업」 · 내려받기 · 올려서 되돌리기
 *   · 되돌리기 직전에는 지금 상태를 자동으로 한 벌 더 떠 둔다 (되돌리기를 되돌릴 수 있게)
 *
 * 정기 보고서 = 엑셀 한 파일(재고 현황 · 입출고 · 요청 · 주문 · 통계 · 선납) + 백업 파일을
 *   moondentaldigital@gmail.com 으로. 주기는 매일/매주/매월, 설정에서 바꾼다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 백업에 담는 표 (백업 보관함 자신은 빼고) */
function md_inv_backup_tables() {
	return array( 'cat', 'team', 'vendor', 'item', 'ledger', 'req', 'ord', 'deposit', 'log', 'fav', 'price', 'recon' );
}

/** 지금 상태를 JSON 문자열로 */
function md_inv_backup_build() {
	global $wpdb;
	$data = array(
		'format'   => 'md-inventory-backup',
		'version'  => 1,
		'schema'   => MD_INV_SCHEMA,
		'site'     => home_url( '/' ),
		'created'  => current_time( 'mysql' ),
		'settings' => md_inv_settings(),
		'tables'   => array(),
	);
	$n = 0;
	foreach ( md_inv_backup_tables() as $k ) {
		$sql  = 'SELECT * FROM ' . md_inv_t( $k ) . ' ORDER BY id ASC';
		if ( 'log' === $k ) { $sql = 'SELECT * FROM ' . md_inv_t( 'log' ) . ' ORDER BY id DESC LIMIT 20000'; }
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		$data['tables'][ $k ] = $rows ? $rows : array();
		$n += count( $data['tables'][ $k ] );
	}
	return array( wp_json_encode( $data, JSON_UNESCAPED_UNICODE ), $n );
}

/** 백업을 한 벌 떠서 보관함에 넣는다 */
function md_inv_backup_make( $kind = 'manual', $note = '' ) {
	global $wpdb;
	list( $json, $n ) = md_inv_backup_build();
	$gz = gzencode( $json, 6 );
	$ok = $wpdb->insert( md_inv_t( 'backup' ), array(
		'kind' => $kind, 'note' => mb_substr( (string) $note, 0, 255 ), 'rows_n' => $n,
		'size' => strlen( $gz ), 'data' => 'b64:' . base64_encode( $gz ), 'created_at' => current_time( 'mysql' ), /* 글자로 담는다 — DB 문자셋 변환에 바이너리가 깨지지 않게 */
	) );
	if ( ! $ok ) { return new WP_Error( 'db', '백업을 저장하지 못했습니다.' ); }
	$id = (int) $wpdb->insert_id;
	md_inv_backup_prune();
	if ( 'auto' !== $kind ) { md_inv_log( '백업', ( 'manual' === $kind ? '지금 백업' : $note ) . ' · ' . $n . '줄' ); }
	return $id;
}

/** 오래된 자동 백업 정리 — 수동 · 되돌리기 전 백업은 개수와 상관없이 90일은 둔다 */
function md_inv_backup_prune() {
	global $wpdb;
	$t    = md_inv_t( 'backup' );
	$keep = max( 3, (int) md_inv_set( 'backup_keep' ) );
	$ids  = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $t WHERE kind = 'auto' ORDER BY id DESC LIMIT 1000 OFFSET %d", $keep ) );
	if ( $ids ) { $wpdb->query( "DELETE FROM $t WHERE id IN (" . implode( ',', array_map( 'intval', $ids ) ) . ')' ); }
	$wpdb->query( $wpdb->prepare( "DELETE FROM $t WHERE kind <> 'auto' AND created_at < %s", date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 90 * DAY_IN_SECONDS ) ) );
}

function md_inv_backups() {
	global $wpdb;
	return $wpdb->get_results( 'SELECT id, kind, note, rows_n, size, created_at FROM ' . md_inv_t( 'backup' ) . ' ORDER BY id DESC LIMIT 200' );
}

function md_inv_backup_blob( $id ) {
	global $wpdb;
	$d = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT data FROM ' . md_inv_t( 'backup' ) . ' WHERE id = %d', (int) $id ) );
	if ( 'b64:' === substr( $d, 0, 4 ) ) { $d = (string) base64_decode( substr( $d, 4 ) ); }
	return $d;
}

/** 백업 파일 내용(gzip 또는 JSON) → 배열. 형식이 틀리면 WP_Error */
function md_inv_backup_parse( $raw ) {
	if ( '' === (string) $raw ) { return new WP_Error( 'empty', '빈 파일입니다.' ); }
	if ( "\x1f\x8b" === substr( $raw, 0, 2 ) ) {
		$raw = @gzdecode( $raw );
		if ( false === $raw ) { return new WP_Error( 'gz', '압축을 풀 수 없는 파일입니다.' ); }
	}
	$d = json_decode( $raw, true );
	if ( ! is_array( $d ) || ( isset( $d['format'] ) ? $d['format'] : '' ) !== 'md-inventory-backup' || empty( $d['tables'] ) || ! is_array( $d['tables'] ) ) {
		return new WP_Error( 'format', '재고관리 백업 파일이 아닙니다.' );
	}
	foreach ( array( 'item', 'ledger' ) as $must ) {
		if ( ! isset( $d['tables'][ $must ] ) || ! is_array( $d['tables'][ $must ] ) ) { return new WP_Error( 'format', '백업 파일에 「' . $must . '」 표가 없습니다.' ); }
	}
	return $d;
}

/**
 * 되돌리기. 지금 상태를 먼저 백업한 뒤 표를 통째로 바꾼다.
 * 표의 열 목록과 맞는 칸만 넣는다 (나중 버전에서 열이 늘어도 안전).
 */
function md_inv_backup_restore( $d ) {
	global $wpdb;
	$pre = md_inv_backup_make( 'restore', '되돌리기 직전 자동 백업' );
	if ( is_wp_error( $pre ) ) { return $pre; }

	md_inv_lock();
	md_inv_begin();
	$count = 0;
	foreach ( md_inv_backup_tables() as $k ) {
		if ( ! isset( $d['tables'][ $k ] ) ) { continue; }
		$table = md_inv_t( $k );
		$cols  = $wpdb->get_col( "SHOW COLUMNS FROM $table" );
		if ( ! $cols ) { md_inv_rollback(); md_inv_unlock(); return new WP_Error( 'cols', '표 구조를 읽지 못했습니다: ' . $k ); }
		$cols = array_flip( $cols );
		$wpdb->query( "DELETE FROM $table" );
		foreach ( $d['tables'][ $k ] as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$row = array_intersect_key( $row, $cols );
			if ( ! $row ) { continue; }
			if ( false === $wpdb->insert( $table, $row ) ) {
				md_inv_rollback(); md_inv_unlock();
				return new WP_Error( 'insert', '되돌리는 중 오류가 나서 원래대로 두었습니다 (' . $k . '). ' . $wpdb->last_error );
			}
			$count++;
		}
	}
	md_inv_commit();
	md_inv_unlock();
	if ( function_exists( 'md_inv_deposit_fix_credit' ) ) { md_inv_deposit_fix_credit(); } /* v6.0 전 백업 — 쓸 수 있는 금액 칸이 없다 */
	if ( ! empty( $d['settings'] ) && is_array( $d['settings'] ) ) {
		update_option( 'md_inv_settings', wp_parse_args( $d['settings'], md_inv_setting_defaults() ), false );
	}
	md_inv_log( '백업 되돌리기', ( isset( $d['created'] ) ? $d['created'] . ' 백업 · ' : '' ) . $count . '줄' );
	return $count;
}

/* ============================================================
 * 예약 작업 — 매시간 한 번 깨어 할 일이 있는지 본다
 *   WP-Cron 은 방문이 있어야 돈다. 라운지·홈페이지 방문이 꾸준해 문제없지만,
 *   한 시간 단위로 「이번에 보내야 하나」를 판단해 늦게 깨어도 빠뜨리지 않게 한다.
 * ============================================================ */

function md_inv_cron_schedule() {
	if ( ! wp_next_scheduled( 'md_inv_hourly' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'md_inv_hourly' );
	}
}
add_action( 'init', 'md_inv_cron_schedule' );

/**
 * 그 시각 이후 실제로 바뀐 것이 있나 — 모든 쓰기는 작업 기록(md_inv_log)을 남기므로 그것으로 본다.
 * 보고서 발송 · 백업 · 내려받기처럼 데이터를 바꾸지 않는 기록은 세지 않는다.
 */
function md_inv_changed_since( $since ) {
	global $wpdb;
	if ( '' === (string) $since ) { return true; }
	$skip = array( '보고서 메일', '백업', '백업 내려받기', '백업 삭제', '엑셀 내려받기' );
	$in   = implode( ',', array_fill( 0, count( $skip ), '%s' ) );
	$n = (int) $wpdb->get_var( $wpdb->prepare(
		'SELECT COUNT(*) FROM ' . md_inv_t( 'log' ) . " WHERE created_at > %s AND action NOT IN ($in)",
		array_merge( array( $since ), $skip )
	) );
	return $n > 0;
}

/** 마지막으로 백업을 뜬 시각 (종류 상관없이) */
function md_inv_last_backup_at() {
	global $wpdb;
	return (string) $wpdb->get_var( 'SELECT MAX(created_at) FROM ' . md_inv_t( 'backup' ) );
}

function md_inv_hourly() {
	if ( (int) get_option( 'md_inv_schema', 0 ) < 1 ) { md_inv_migrate(); }
	if ( (int) get_option( 'md_inv_schema', 0 ) < 1 ) { return; }
	if ( function_exists( 'md_inv_seed_now' ) ) { md_inv_seed_now(); }
	$today = current_time( 'Y-m-d' );
	/* 매일 자동 백업 */
	if ( md_inv_set( 'backup_on' ) && get_option( 'md_inv_last_backup' ) !== $today ) {
		update_option( 'md_inv_last_backup', $today, false );
		/* 마지막 백업 뒤로 바뀐 것이 없으면 같은 내용을 또 뜨지 않는다 */
		if ( md_inv_changed_since( md_inv_last_backup_at() ) ) { md_inv_backup_make( 'auto', '매일 자동 백업' ); }
	}
	/* 정기 보고서 */
	if ( md_inv_report_due() ) {
		md_inv_report_send( 'auto' );
	}
}
add_action( 'md_inv_hourly', 'md_inv_hourly' );

/** 이번 시간에 보고서를 보내야 하는가 (같은 주기에 두 번 보내지 않는다) */
function md_inv_report_due() {
	$s = md_inv_settings();
	if ( ! $s['report_on'] || '' === trim( $s['report_to'] ) ) { return false; }
	$now  = current_time( 'timestamp' );
	if ( (int) date( 'G', $now ) < (int) $s['report_hour'] ) { return false; }
	$key = md_inv_report_period_key( $now );
	if ( null === $key ) { return false; }
	return get_option( 'md_inv_last_report_key' ) !== $key;
}

/** 이 순간이 속한 보고 주기의 이름 — 그 주기의 보내는 날이 아직 안 왔으면 null */
function md_inv_report_period_key( $now ) {
	$s = md_inv_settings();
	switch ( $s['report_freq'] ) {
		case 'daily':
			return 'd' . date( 'Y-m-d', $now );
		case 'monthly':
			if ( (int) date( 'j', $now ) < (int) $s['report_dom'] ) { return null; }
			return 'm' . date( 'Y-m', $now );
		default:
			if ( (int) date( 'N', $now ) < (int) $s['report_dow'] ) { return null; }
			return 'w' . date( 'o-W', $now );
	}
}

/** 보고서가 다루는 기간 */
function md_inv_report_range() {
	$now = current_time( 'timestamp' );
	switch ( md_inv_set( 'report_freq' ) ) {
		case 'daily':   $from = $now - DAY_IN_SECONDS; break;
		case 'monthly': $from = strtotime( '-1 month', $now ); break;
		default:        $from = $now - 7 * DAY_IN_SECONDS; break;
	}
	return array( date( 'Y-m-d', $from ), date( 'Y-m-d', $now ) );
}

/**
 * 보고서 메일 보내기.
 * @param string $why auto | manual | test
 * @return true|WP_Error
 */
function md_inv_report_send( $why = 'manual', $to = '' ) {
	$s  = md_inv_settings();
	$to = '' !== $to ? md_inv_clean_emails( $to ) : $s['report_to'];
	if ( '' === $to ) { return new WP_Error( 'to', '받는 주소가 없습니다. 설정에서 메일 주소를 적어 주세요.' ); }
	list( $from, $till ) = md_inv_report_range();
	if ( 'auto' === $why ) {
		update_option( 'md_inv_last_report_key', md_inv_report_period_key( current_time( 'timestamp' ) ), false );
		/* v4.23 · 바뀐 것이 없는 주에도 보낸다 (원장 지시 — v4.22.2 의 건너뛰기 철회) */
	}
	$dir = trailingslashit( get_temp_dir() ) . 'md-inv-' . wp_generate_password( 12, false );
	wp_mkdir_p( $dir );
	$files = array();

	$xlsx = $dir . '/문치과병원_재고보고서_' . current_time( 'Ymd' ) . '.xlsx';
	file_put_contents( $xlsx, md_inv_report_xlsx( $from, $till ) );
	$files[] = $xlsx;
	if ( $s['report_backup'] ) {
		list( $json, $n ) = md_inv_backup_build();
		$bk = $dir . '/문치과병원_재고백업_' . current_time( 'Ymd-Hi' ) . '.json.gz';
		file_put_contents( $bk, gzencode( $json, 6 ) );
		$files[] = $bk;
	}

	$c     = md_inv_counts();
	$items = md_inv_items();
	$usage = 0;
	foreach ( md_inv_usage( 'team', $from, $till ) as $r ) { $usage += (int) $r->amount; }
	$freq  = array( 'daily' => '일간', 'weekly' => '주간', 'monthly' => '월간' );
	$subject = '[문치과병원 재료실] ' . ( 'test' === $why ? '(시험) ' : '' ) . $freq[ $s['report_freq'] ] . ' 보고서 ' . current_time( 'Y-m-d' );
	$body  = "문치과병원 재료실 " . $freq[ $s['report_freq'] ] . " 보고서입니다.\n";
	$body .= '기간: ' . $from . ' ~ ' . $till . "\n\n";
	$body .= '· 출고 대기 요청: ' . $c['pending'] . "건\n";
	$body .= '· 입고 기다리는 주문: ' . $c['ordered'] . "건\n";
	$body .= '· 주문이 필요한 품목: ' . $c['need'] . "개\n";
	$body .= '· 기간 사용금액: ' . md_inv_won( $usage ) . "\n";
	$body .= '· 지금 재고 금액: ' . md_inv_won( md_inv_stock_value( $items ) ) . ' (' . count( $items ) . "개 품목)\n";
	foreach ( md_inv_prepaid_summary() as $p ) {
		$body .= '· 선납 ' . $p->vendor->name . ' 쓸 수 있는 잔액: ' . md_inv_won( $p->available ) . ( $p->burn ? ' (약 ' . md_inv_months_txt( $p->months_left ) . '분)' : '' ) . ( $p->alert ? ' ⚠ 잔액 부족 — 알림 기준 ' . md_inv_won( $p->vendor->pp_alert ) : '' ) . "\n";
	}
	$body .= "\n첨부: 엑셀 보고서" . ( $s['report_backup'] ? ' · 백업 파일(.json.gz — 직원 라운지 › 재료실 › 설정 › 백업에서 올리면 이 시점으로 되돌릴 수 있음)' : '' ) . "\n";
	$body .= '재료실 열기: ' . home_url( '/직원/?app=stock' ) . "\n";
	$body .= "\n이 메일은 자동으로 보냈습니다. 받는 주소·주기는 재료실 › 설정 › 보고서 메일에서 바꿀 수 있습니다.\n";

	$ok = wp_mail( array_map( 'trim', explode( ',', $to ) ), $subject, $body, array(), $files );
	foreach ( $files as $f ) { @unlink( $f ); }
	@rmdir( $dir );

	update_option( 'md_inv_last_report', array( 'at' => current_time( 'mysql' ), 'ok' => $ok ? 1 : 0, 'to' => $to, 'why' => $why ), false );
	if ( $ok && 'test' !== $why ) { update_option( 'md_inv_last_report_sent', current_time( 'mysql' ), false ); }
	md_inv_log( '보고서 메일', ( $ok ? '보냄' : '실패' ) . ' · ' . $to . ' · ' . $why );
	return $ok ? true : new WP_Error( 'mail', '메일을 보내지 못했습니다. 서버 메일 설정을 확인해 주세요.' );
}

/** 새 요청 알림 (설정에서 켰을 때만) */
function md_inv_notify_new( $ids ) {
	if ( ! md_inv_set( 'notify_new' ) ) { return; }
	$to = md_inv_set( 'notify_to' );
	if ( '' === trim( (string) $to ) ) { return; }
	$reqs = md_inv_reqs( array( 'ids' => $ids, 'limit' => 0 ) );
	if ( ! $reqs ) { return; }
	$r0   = $reqs[0];
	$body = md_inv_team_name( $r0->team_id ) . ' · ' . $r0->requester . "\n\n";
	foreach ( $reqs as $r ) { $body .= '· ' . $r->name . ' ' . $r->qty . ( $r->unit ? ' ' . $r->unit : '' ) . ( $r->note ? ' — ' . $r->note : '' ) . "\n"; }
	$body .= "\n처리하기: " . home_url( '/직원/?app=stock&iv=todo' ) . "\n";
	wp_mail( array_map( 'trim', explode( ',', $to ) ), '[문치과병원 재료실] 새 신청 ' . count( $reqs ) . '건 · ' . md_inv_team_name( $r0->team_id ), $body );
}
