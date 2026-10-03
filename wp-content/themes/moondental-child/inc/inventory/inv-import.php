<?php
/**
 * 재고관리 v5 — AppSheet 에서 가져오기
 *
 * 구글 시트를 「파일 › 다운로드 › Microsoft Excel(.xlsx)」로 받은 파일을 올린다.
 *   팀 스프레드시트 (탭: Untitled=팀, 분류, 중분류, 세부분류)
 *   업체 스프레드시트 (탭: Untitled=업체, 선납입금)
 *   품목 스프레드시트 (탭: Untitled=품목)
 *   입출고 스프레드시트 (탭: Untitled=입출고) — 현재고를 AppSheet 와 같은 식으로 계산하는 데 쓴다
 *
 * 현재고 계산 (AppSheet 2판 최종 식, 2026-10-03)
 *   마지막 실사(구분 조정 · 실사수량 있음)의 수량 + 그 뒤의 입고 · 실사수량 없는 조정 − 출고 · 반품.
 *   실사가 없으면 기초재고부터. 재고반영이 거짓인 행은 뺀다.
 *
 * 이 데이터(업체 연락처·단가·선납 금액)는 공개 저장소에 넣지 않는다 — 그래서 파일로 올린다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * .xlsx 읽기 — 모든 시트를 [ 시트이름 => 2차원 배열 ]
 * ============================================================ */

function md_inv_zip_get( $path, $entry ) {
	if ( class_exists( 'ZipArchive' ) ) {
		$z = new ZipArchive();
		if ( true !== $z->open( $path ) ) { return null; }
		$s = $z->getFromName( $entry );
		$z->close();
		return false === $s ? null : $s;
	}
	if ( ! class_exists( 'PclZip' ) ) { require_once ABSPATH . 'wp-admin/includes/class-pclzip.php'; }
	$z   = new PclZip( $path );
	$out = $z->extract( PCLZIP_OPT_BY_NAME, $entry, PCLZIP_OPT_EXTRACT_AS_STRING );
	if ( ! is_array( $out ) || empty( $out ) || ! isset( $out[0]['content'] ) ) { return null; }
	return (string) $out[0]['content'];
}

function md_inv_xml_load( $s ) {
	if ( null === $s ) { return null; }
	$s = preg_replace( '/^\xEF\xBB\xBF/', '', $s );
	$prev = libxml_use_internal_errors( true );
	$x = simplexml_load_string( $s );
	libxml_use_internal_errors( $prev );
	return $x ? $x : null;
}

function md_inv_xlsx_read( $path ) {
	$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
	$wb = md_inv_xml_load( md_inv_zip_get( $path, 'xl/workbook.xml' ) );
	if ( ! $wb ) { return new WP_Error( 'xlsx', '엑셀 파일을 열 수 없습니다.' ); }
	$rels = md_inv_xml_load( md_inv_zip_get( $path, 'xl/_rels/workbook.xml.rels' ) );
	$target = array();
	if ( $rels ) {
		foreach ( $rels->children() as $r ) {
			$a = $r->attributes();
			$target[ (string) $a['Id'] ] = ltrim( preg_replace( '#^/?xl/#', '', (string) $a['Target'] ), '/' );
		}
	}
	$shared = array();
	$ss = md_inv_xml_load( md_inv_zip_get( $path, 'xl/sharedStrings.xml' ) );
	if ( $ss ) {
		foreach ( $ss->children( $ns ) as $si ) {
			$txt = '';
			$kids = $si->children( $ns );
			if ( isset( $kids->t ) ) { $txt = (string) $kids->t; }
			foreach ( $kids->r as $run ) { $txt .= (string) $run->children( $ns )->t; }
			$shared[] = $txt;
		}
	}
	$out = array();
	foreach ( $wb->children( $ns )->sheets->children( $ns ) as $sh ) {
		$name = (string) $sh->attributes()->name;
		$rid  = (string) $sh->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' )->id;
		$file = isset( $target[ $rid ] ) ? 'xl/' . $target[ $rid ] : '';
		$x    = $file ? md_inv_xml_load( md_inv_zip_get( $path, $file ) ) : null;
		if ( ! $x ) { continue; }
		$rows = array();
		foreach ( $x->children( $ns )->sheetData->children( $ns ) as $row ) {
			$cells = array();
			foreach ( $row->children( $ns ) as $c ) {
				$a   = $c->attributes();
				$ref = (string) $a['r'];
				$col = 0;
				if ( preg_match( '/^([A-Z]+)/', $ref, $m ) ) {
					foreach ( str_split( $m[1] ) as $ch ) { $col = $col * 26 + ( ord( $ch ) - 64 ); }
					$col--;
				}
				$t   = (string) $a['t'];
				$k   = $c->children( $ns );
				$v   = isset( $k->v ) ? (string) $k->v : '';
				if ( 's' === $t ) { $v = isset( $shared[ (int) $v ] ) ? $shared[ (int) $v ] : ''; }
				elseif ( 'inlineStr' === $t ) { $v = (string) $k->is->children( $ns )->t; }
				elseif ( 'b' === $t ) { $v = '1' === $v ? 'TRUE' : 'FALSE'; }
				$cells[ $col ] = $v;
			}
			if ( ! $cells ) { $rows[] = array(); continue; }
			$line = array_fill( 0, max( array_keys( $cells ) ) + 1, '' );
			foreach ( $cells as $i => $v ) { $line[ $i ] = $v; }
			$rows[] = $line;
		}
		$out[ $name ] = $rows;
	}
	return $out;
}

/** 첫 줄을 머리글로 삼아 [ [머리글 => 값] ] — 빈 줄은 건너뛴다 */
function md_inv_sheet_assoc( $rows ) {
	if ( ! $rows ) { return array(); }
	$head = array_map( 'trim', array_shift( $rows ) );
	$out  = array();
	foreach ( $rows as $r ) {
		if ( ! array_filter( $r, function ( $v ) { return '' !== trim( (string) $v ); } ) ) { continue; }
		$a = array();
		foreach ( $head as $i => $h ) { if ( '' !== $h ) { $a[ $h ] = isset( $r[ $i ] ) ? trim( (string) $r[ $i ] ) : ''; } }
		$out[] = $a;
	}
	return $out;
}

/** 엑셀 날짜(일련번호) 또는 문자열 → Y-m-d H:i:s */
function md_inv_xl_date( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) { return ''; }
	if ( is_numeric( $v ) ) {
		$ts = (int) round( ( (float) $v - 25569 ) * 86400 );
		return gmdate( 'Y-m-d H:i:s', $ts );
	}
	$ts = strtotime( $v );
	return $ts ? date( 'Y-m-d H:i:s', $ts ) : '';
}

function md_inv_xl_bool( $v ) {
	$v = strtoupper( trim( (string) $v ) );
	return in_array( $v, array( 'TRUE', 'Y', '1', 'YES', 'O' ), true );
}

/** 시트 찾기 — 이름이 정확히 같거나, 없으면 첫 시트 */
function md_inv_pick_sheet( $book, $name, $fallback_first = false ) {
	if ( isset( $book[ $name ] ) ) { return $book[ $name ]; }
	if ( $fallback_first && $book ) { return reset( $book ); }
	return array();
}

/* ============================================================
 * 처음 한 번 — 서버에 미리 올려 둔 AppSheet 엑셀로 자동 채우기
 *
 *   wp-content/uploads/md-inv-seed-<무작위>/ 에 team · vendor · item · io .xlsx 를
 *   FTP 로 올려 두면(폴더는 .htaccess 로 막는다), 품목이 하나도 없을 때
 *   관리자가 재고관리를 처음 여는 순간 한 번 가져온다. 데이터가 공개 저장소를 거치지 않는다.
 *   가져온 뒤에는 다시 돌지 않는다(옵션 md_inv_seeded).
 * ============================================================ */

function md_inv_seed_dir() {
	$up   = wp_upload_dir( null, false );
	$dirs = glob( trailingslashit( $up['basedir'] ) . 'md-inv-seed-*', GLOB_ONLYDIR );
	return $dirs ? $dirs[0] : '';
}

/** 라운지 화면을 연 사람(직원·관리자 누구든)이 있으면 그때 채운다 */
function md_inv_auto_seed() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() || ! is_user_logged_in() || ! md_inv_can_use() ) { return; }
	md_inv_seed_now();
}

/** 예약 작업에서도 부른다 — 아무도 라운지를 열지 않아도 몇 분 안에 채워지게 */
function md_inv_seed_now() {
	if ( get_option( 'md_inv_seeded' ) || get_option( 'md_inv_imported' ) ) { return; }
	if ( (int) get_option( 'md_inv_schema', 0 ) < 1 ) { return; }
	global $wpdb;
	if ( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . md_inv_t( 'item' ) ) > 0 ) { update_option( 'md_inv_seeded', 'skip-has-items', false ); return; }
	$dir = md_inv_seed_dir();
	if ( '' === $dir ) { return; }
	$files = array();
	foreach ( array( 'team', 'vendor', 'item', 'io' ) as $k ) {
		if ( is_readable( $dir . '/' . $k . '.xlsx' ) ) { $files[ $k ] = $dir . '/' . $k . '.xlsx'; }
	}
	if ( count( $files ) < 3 ) { return; }
	if ( get_transient( 'md_inv_seeding' ) ) { return; }
	set_transient( 'md_inv_seeding', 1, 5 * MINUTE_IN_SECONDS );
	$r = md_inv_import_appsheet( $files, array( 'dry' => false, 'history' => false ) );
	delete_transient( 'md_inv_seeding' );
	update_option( 'md_inv_seeded', is_wp_error( $r ) ? 'error: ' . $r->get_error_message() : current_time( 'mysql' ), false );
}
add_action( 'template_redirect', 'md_inv_auto_seed', 3 );

/* ============================================================
 * 가져오기
 * ============================================================ */

/**
 * @param array $files [ 'team' => 경로, 'vendor' => 경로, 'item' => 경로, 'io' => 경로|'' ]
 * @param array $opt   dry (미리보기만), history (입출고 기록도)
 * @return array 결과 요약 | WP_Error
 */
function md_inv_import_appsheet( $files, $opt = array() ) {
	global $wpdb;
	$opt = wp_parse_args( $opt, array( 'dry' => true, 'history' => false ) );

	$books = array();
	foreach ( array( 'team', 'vendor', 'item' ) as $k ) {
		if ( empty( $files[ $k ] ) ) { return new WP_Error( 'file', '팀 · 업체 · 품목 엑셀 세 파일은 꼭 있어야 합니다.' ); }
		$b = md_inv_xlsx_read( $files[ $k ] );
		if ( is_wp_error( $b ) ) { return $b; }
		$books[ $k ] = $b;
	}
	if ( ! empty( $files['io'] ) ) {
		$b = md_inv_xlsx_read( $files['io'] );
		if ( is_wp_error( $b ) ) { return $b; }
		$books['io'] = $b;
	}

	$teams  = md_inv_sheet_assoc( md_inv_pick_sheet( $books['team'], 'Untitled', true ) );
	$c1s    = md_inv_sheet_assoc( md_inv_pick_sheet( $books['team'], '분류' ) );
	$c2s    = md_inv_sheet_assoc( md_inv_pick_sheet( $books['team'], '중분류' ) );
	$c3s    = md_inv_sheet_assoc( md_inv_pick_sheet( $books['team'], '세부분류' ) );
	$vends  = md_inv_sheet_assoc( md_inv_pick_sheet( $books['vendor'], 'Untitled', true ) );
	$deps   = md_inv_sheet_assoc( md_inv_pick_sheet( $books['vendor'], '선납입금' ) );
	$items  = md_inv_sheet_assoc( md_inv_pick_sheet( $books['item'], 'Untitled', true ) );
	$ios    = isset( $books['io'] ) ? md_inv_sheet_assoc( md_inv_pick_sheet( $books['io'], 'Untitled', true ) ) : array();

	/* AppSheet 시험 품목(품목ID 가 T- 로 시작 — 재고검증테스트 · 선납검증테스트)과 그 입출고는 옮기지 않는다 */
	$is_test = function ( $id ) { return 0 === strpos( (string) $id, 'T-' ); };
	$items   = array_values( array_filter( $items, function ( $r ) use ( $is_test ) { return ! $is_test( isset( $r['품목ID'] ) ? $r['품목ID'] : '' ); } ) );
	$ios     = array_values( array_filter( $ios, function ( $r ) use ( $is_test ) { return ! $is_test( isset( $r['품목ID'] ) ? $r['품목ID'] : '' ); } ) );

	/* 머리글 확인 — 엉뚱한 파일을 올리면 여기서 멈춘다 */
	if ( ! $teams || ! isset( $teams[0]['팀명'] ) ) { return new WP_Error( 'team', '팀 파일에서 「팀명」 열을 찾지 못했습니다. 팀 스프레드시트를 올려 주세요.' ); }
	if ( ! $vends || ! isset( $vends[0]['업체명'] ) ) { return new WP_Error( 'vendor', '업체 파일에서 「업체명」 열을 찾지 못했습니다.' ); }
	if ( ! $items || ! isset( $items[0]['품목명'] ) ) { return new WP_Error( 'item', '품목 파일에서 「품목명」 열을 찾지 못했습니다.' ); }
	if ( $ios && ! isset( $ios[0]['구분'] ) ) { return new WP_Error( 'io', '입출고 파일에서 「구분」 열을 찾지 못했습니다.' ); }

	/* ---- 현재고 계산 (AppSheet 식) ---- */
	$byitem = array();
	foreach ( $ios as $r ) {
		if ( '' === $r['품목ID'] ) { continue; }
		$on = isset( $r['재고반영'] ) ? $r['재고반영'] : 'TRUE';
		if ( '' !== $on && ! md_inv_xl_bool( $on ) ) { continue; }
		$r['_at'] = md_inv_xl_date( $r['일시'] );
		$byitem[ $r['품목ID'] ][] = $r;
	}
	$stock = array();
	$hist  = array();
	foreach ( $items as $it ) {
		$id   = $it['품목ID'];
		$base = (int) round( (float) ( '' === $it['기초재고'] ? 0 : $it['기초재고'] ) );
		$rows = isset( $byitem[ $id ] ) ? $byitem[ $id ] : array();
		usort( $rows, function ( $a, $b ) { return strcmp( $a['_at'], $b['_at'] ); } );
		$s = $base;
		foreach ( $rows as $r ) {
			$q  = (int) round( (float) $r['수량'] );
			$g  = $r['구분'];
			$cnt = isset( $r['실사수량'] ) ? trim( $r['실사수량'] ) : '';
			if ( '조정' === $g && '' !== $cnt ) { $s = (int) round( (float) $cnt ); }
			elseif ( '조정' === $g ) { $s += $q; }
			elseif ( '출고' === $g || '반품' === $g ) { $s -= abs( $q ); }
			else { $s += abs( $q ); }
		}
		$stock[ $id ] = $s;
		$hist[ $id ]  = $rows;
	}

	$summary = array(
		'teams' => count( array_filter( $teams, function ( $r ) { return '' !== $r['팀명']; } ) ),
		'cat1' => count( $c1s ), 'cat2' => count( array_filter( $c2s, function ( $r ) { return '' !== ( isset( $r['중분류명'] ) ? $r['중분류명'] : '' ); } ) ), 'cat3' => count( $c3s ),
		'vendors' => count( $vends ), 'prepaid' => 0, 'deposits' => count( $deps ),
		'items' => count( $items ), 'hidden' => 0, 'stock_pos' => 0, 'stock_neg' => array(), 'io' => count( $ios ),
	);
	foreach ( $vends as $v ) { if ( md_inv_xl_bool( isset( $v['선납업체'] ) ? $v['선납업체'] : '' ) ) { $summary['prepaid']++; } }
	foreach ( $items as $it ) {
		if ( md_inv_xl_bool( $it['숨김'] ) ) { $summary['hidden']++; }
		$s = $stock[ $it['품목ID'] ];
		if ( $s > 0 ) { $summary['stock_pos']++; }
		if ( $s < 0 ) { $summary['stock_neg'][] = $it['품목명'] . ' (' . $s . ')'; }
	}
	if ( $opt['dry'] ) { return $summary; }

	/* ---- 실제로 넣기 — 지금 데이터는 백업한 뒤 비운다 ---- */
	$pre = md_inv_backup_make( 'restore', 'AppSheet 가져오기 직전 자동 백업' );
	if ( is_wp_error( $pre ) ) { return $pre; }
	$t = md_inv_t();
	md_inv_lock();
	md_inv_begin();
	foreach ( array( 'cat', 'team', 'vendor', 'item', 'ledger', 'req', 'ord', 'deposit' ) as $k ) { $wpdb->query( "DELETE FROM {$t[$k]}" ); }

	/* 분류 */
	$map1 = array(); $map2 = array(); $map3 = array();
	foreach ( $c1s as $i => $r ) {
		if ( '' === $r['분류명'] ) { continue; }
		$ok = $wpdb->insert( $t['cat'], array( 'level' => 1, 'name' => mb_substr( $r['분류명'], 0, 100 ), 'sort_no' => (int) round( (float) ( $r['표시순서'] ? $r['표시순서'] : $i + 1 ) * 100 ), 'active' => md_inv_xl_bool( $r['사용여부'] ) ? 1 : 0, 'legacy' => $r['분류ID'] ) );
		$map1[ $r['분류ID'] ] = $ok ? (int) $wpdb->insert_id : 0;
	}
	foreach ( $c2s as $i => $r ) {
		$nm = isset( $r['중분류명'] ) ? $r['중분류명'] : '';
		if ( '' === $nm || ! isset( $map1[ $r['대분류'] ] ) ) { continue; }
		$ok = $wpdb->insert( $t['cat'], array( 'level' => 2, 'parent_id' => $map1[ $r['대분류'] ], 'name' => mb_substr( $nm, 0, 100 ), 'sort_no' => (int) round( (float) ( $r['표시순서'] ? $r['표시순서'] : $i + 1 ) * 100 ), 'active' => md_inv_xl_bool( $r['사용여부'] ) ? 1 : 0, 'legacy' => $r['중분류ID'] ) );
		$map2[ $r['중분류ID'] ] = $ok ? (int) $wpdb->insert_id : 0;
	}
	foreach ( $c3s as $i => $r ) {
		if ( '' === $r['세부분류명'] || ! isset( $map2[ $r['중분류'] ] ) ) { continue; }
		$ok = $wpdb->insert( $t['cat'], array( 'level' => 3, 'parent_id' => $map2[ $r['중분류'] ], 'name' => mb_substr( $r['세부분류명'], 0, 100 ), 'sort_no' => (int) round( (float) ( $r['표시순서'] ? $r['표시순서'] : $i + 1 ) * 100 ), 'active' => md_inv_xl_bool( $r['사용여부'] ) ? 1 : 0, 'legacy' => $r['소분류ID'] ) );
		$map3[ $r['소분류ID'] ] = $ok ? (int) $wpdb->insert_id : 0;
	}

	/* 팀 — 표시순서대로 다시 번호 */
	usort( $teams, function ( $a, $b ) { return (float) $a['표시순서'] <=> (float) $b['표시순서']; } );
	$mapT = array();
	$i = 0;
	foreach ( $teams as $r ) {
		if ( '' === $r['팀명'] ) { continue; }
		$i++;
		$ok = $wpdb->insert( $t['team'], array( 'name' => mb_substr( $r['팀명'], 0, 100 ), 'sort_no' => $i * 10, 'active' => ( '' === $r['사용여부'] || md_inv_xl_bool( $r['사용여부'] ) ) ? 1 : 0, 'in_stats' => ( ! isset( $r['통계표시'] ) || '' === $r['통계표시'] || md_inv_xl_bool( $r['통계표시'] ) ) ? 1 : 0, 'legacy' => mb_substr( $r['팀ID'], 0, 40 ) ) );
		$mapT[ $r['팀ID'] ] = $ok ? (int) $wpdb->insert_id : 0;
	}

	/* 업체 */
	$mapV = array();
	foreach ( $vends as $r ) {
		if ( '' === $r['업체명'] ) { continue; }
		$ok = $wpdb->insert( $t['vendor'], array(
			'name' => mb_substr( $r['업체명'], 0, 150 ), 'contact' => mb_substr( isset( $r['담당자'] ) ? $r['담당자'] : '', 0, 255 ), 'phone' => mb_substr( isset( $r['연락처'] ) ? $r['연락처'] : '', 0, 255 ),
			'email' => mb_substr( isset( $r['이메일'] ) ? $r['이메일'] : '', 0, 255 ), 'shop_info' => isset( $r['주문방법'] ) ? $r['주문방법'] : '',
			'goods' => isset( $r['취급품목'] ) ? $r['취급품목'] : '', 'note' => isset( $r['비고'] ) ? $r['비고'] : '',
			'prepaid' => md_inv_xl_bool( isset( $r['선납업체'] ) ? $r['선납업체'] : '' ) ? 1 : 0,
			'sort_no' => (int) round( (float) ( isset( $r['표시순서'] ) ? $r['표시순서'] : 0 ) * 10 ), 'active' => 1, 'legacy' => mb_substr( $r['업체ID'], 0, 40 ),
		) );
		$mapV[ $r['업체ID'] ] = $ok ? (int) $wpdb->insert_id : 0;
	}

	/* 품목 + 처음 수량 */
	$mapI = array();
	$now  = current_time( 'mysql' );
	$open_at = $now;
	foreach ( $items as $r ) {
		$c3 = isset( $map3[ $r['세부구분'] ] ) ? $map3[ $r['세부구분'] ] : 0;
		$c2 = isset( $map2[ $r['중분류'] ] ) ? $map2[ $r['중분류'] ] : 0;
		$c1 = isset( $map1[ $r['분류'] ] ) ? $map1[ $r['분류'] ] : 0;
		$code = preg_match( '/^[A-Za-z]\d{1,6}$/', $r['품목ID'] ) ? $r['품목ID'] : '';
		$ok = $wpdb->insert( $t['item'], array(
			'code' => $code, 'name' => mb_substr( $r['품목명'], 0, 255 ), 'vendor_id' => isset( $mapV[ $r['업체'] ] ) ? $mapV[ $r['업체'] ] : 0,
			'unit' => mb_substr( $r['단위'], 0, 30 ), 'price' => (int) round( (float) $r['단가'] ), 'cat1' => $c1, 'cat2' => $c2, 'cat3' => $c3,
			'min_stock' => max( 0, (int) round( (float) $r['안전재고'] ) ), 'barcode' => mb_substr( isset( $r['바코드'] ) ? $r['바코드'] : '', 0, 100 ),
			'note' => isset( $r['메모'] ) ? $r['메모'] : '', 'active' => md_inv_xl_bool( $r['숨김'] ) ? 0 : 1,
			'created_at' => $now, 'created_by' => get_current_user_id(), 'updated_at' => $now,
		) );
		$mapI[ $r['품목ID'] ] = $ok ? (int) $wpdb->insert_id : 0;
	}
	/* 한 줄이라도 들어가지 않았으면(칸 길이 · DB 오류) 통째로 되돌린다 — 반쪽짜리 이관을 남기지 않는다 */
	if ( count( array_filter( $mapI ) ) !== count( $items ) || count( array_filter( $mapV ) ) !== count( array_filter( $vends, function ( $r ) { return '' !== $r['업체명']; } ) ) ) {
		md_inv_rollback();
		md_inv_unlock();
		return new WP_Error( 'insert', '일부 행을 넣지 못해 가져오기를 취소했습니다 (원래 데이터 그대로). ' . $wpdb->last_error );
	}
	/* 코드가 빈 품목(AppSheet 가 만든 긴 ID)은 이어지는 번호를 매긴다 */
	foreach ( $wpdb->get_col( "SELECT id FROM {$t['item']} WHERE code = '' ORDER BY id" ) as $iid ) {
		$wpdb->update( $t['item'], array( 'code' => md_inv_next_code() ), array( 'id' => (int) $iid ) );
	}

	$lines = 0;
	foreach ( $items as $r ) {
		$iid = $mapI[ $r['품목ID'] ];
		$cur = $stock[ $r['품목ID'] ];
		$sum_hist = 0;
		if ( $opt['history'] ) {
			foreach ( $hist[ $r['품목ID'] ] as $h ) {
				$type = array( '입고' => 'in', '출고' => 'out', '반품' => 'return', '조정' => 'adjust' );
				$ty   = isset( $type[ $h['구분'] ] ) ? $type[ $h['구분'] ] : '';
				if ( '' === $ty ) { continue; }
				$q = (int) round( (float) $h['수량'] );
				$signed = in_array( $ty, array( 'out', 'return' ), true ) ? -abs( $q ) : ( 'adjust' === $ty ? $q : abs( $q ) );
				if ( 0 === $signed ) { continue; }
				$wpdb->insert( $t['ledger'], array(
					'item_id' => $iid, 'type' => $ty, 'qty' => $signed, 'price' => (int) round( (float) $h['단가'] ),
					'vendor_id' => isset( $mapV[ $h['업체'] ] ) ? $mapV[ $h['업체'] ] : 0, 'team_id' => isset( $mapT[ $h['팀'] ] ) ? $mapT[ $h['팀'] ] : 0,
					'free' => md_inv_xl_bool( isset( $h['무상'] ) ? $h['무상'] : '' ) ? 1 : 0, 'person' => mb_substr( $h['반출자'], 0, 100 ),
					'note' => mb_substr( trim( 'AppSheet · ' . $h['비고'] . ( $h['요청자'] ? ' · ' . $h['요청자'] : '' ) ), 0, 500 ),
					'user_id' => get_current_user_id(), 'created_at' => $h['_at'] ? $h['_at'] : $now,
				) );
				$sum_hist += $signed;
				$lines++;
			}
		}
		$open = $cur - $sum_hist;
		if ( 0 !== $open ) {
			$wpdb->insert( $t['ledger'], array(
				'item_id' => $iid, 'type' => 'open', 'qty' => $open, 'price' => (int) round( (float) $r['단가'] ),
				'vendor_id' => isset( $mapV[ $r['업체'] ] ) ? $mapV[ $r['업체'] ] : 0,
				'person' => '이관', 'note' => 'AppSheet 이관 · 그때 재고 ' . $cur, 'user_id' => get_current_user_id(),
				'created_at' => $opt['history'] ? '2026-09-01 00:00:00' : $open_at,
			) );
			$lines++;
		}
	}

	/* 선납 입금 + AppSheet 에서 이미 쓴 금액 (기록을 안 가져오면 한 줄로 차감) */
	foreach ( $deps as $r ) {
		if ( ! isset( $mapV[ $r['업체ID'] ] ) ) { continue; }
		$wpdb->insert( $t['deposit'], array(
			'vendor_id' => $mapV[ $r['업체ID'] ], 'paid_on' => ( '' !== md_inv_xl_date( $r['입금일'] ) ? substr( md_inv_xl_date( $r['입금일'] ), 0, 10 ) : current_time( 'Y-m-d' ) ), 'amount' => (int) round( (float) $r['금액'] ),
			'note' => mb_substr( (string) $r['메모'], 0, 255 ), 'person' => mb_substr( (string) $r['입력자'], 0, 100 ), 'user_id' => get_current_user_id(), 'created_at' => $now,
		) );
	}
	if ( ! $opt['history'] ) {
		$used = array();
		foreach ( $ios as $h ) {
			if ( ! in_array( $h['구분'], array( '입고', '반품' ), true ) ) { continue; }
			if ( md_inv_xl_bool( isset( $h['무상'] ) ? $h['무상'] : '' ) ) { continue; }
			$on = isset( $h['재고반영'] ) ? $h['재고반영'] : 'TRUE';
			if ( '' !== $on && ! md_inv_xl_bool( $on ) ) { continue; }
			$amt = abs( (float) $h['수량'] ) * (float) $h['단가'];
			$v   = $h['업체'];
			$used[ $v ] = ( isset( $used[ $v ] ) ? $used[ $v ] : 0 ) + ( '입고' === $h['구분'] ? $amt : -$amt );
		}
		foreach ( $used as $vid => $amt ) {
			if ( ! isset( $mapV[ $vid ] ) || 0 == $amt ) { continue; }
			$vv = $wpdb->get_row( $wpdb->prepare( "SELECT prepaid FROM {$t['vendor']} WHERE id = %d", $mapV[ $vid ] ) );
			if ( ! $vv || ! (int) $vv->prepaid ) { continue; }
			$wpdb->insert( $t['deposit'], array(
				'vendor_id' => $mapV[ $vid ], 'paid_on' => current_time( 'Y-m-d' ), 'amount' => -(int) round( $amt ),
				'note' => 'AppSheet 에서 이미 차감된 입고 금액 (이관)', 'person' => '이관', 'user_id' => get_current_user_id(), 'created_at' => $now,
			) );
		}
	}
	md_inv_commit();
	md_inv_unlock();
	update_option( 'md_inv_imported', current_time( 'mysql' ), false );
	md_inv_log( 'AppSheet 가져오기', '품목 ' . count( $items ) . ' · 업체 ' . count( $vends ) . ' · 원장 ' . $lines . '줄' . ( $opt['history'] ? ' (기록 포함)' : '' ) );
	$summary['ledger'] = $lines;
	return $summary;
}
