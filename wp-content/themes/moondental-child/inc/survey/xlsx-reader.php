<?php
/**
 * v4.14.4 · 아주 작은 .xlsx 읽기 — 덴트웹 「기간별 접수환자 목록」 엑셀을 명단으로 올리기 위해
 *
 *  .xlsx 는 zip 안에 XML 이 든 파일이다. 첫 시트의 셀 값만 2차원 배열로 돌려준다.
 *  서버에 ZipArchive 가 있으면 그것을, 없으면 워드프레스에 들어 있는 PclZip(순수 PHP)을 쓴다.
 *  수식 · 서식 · 병합 셀은 다루지 않는다 — 덴트웹 내보내기에는 없다.
 *
 *  워드프레스 밖(로컬 테스트)에서도 돌 수 있게 WP 함수를 쓰지 않는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'MD_SURVEY_TEST' ) ) { exit; }

/** zip 안의 파일 하나를 문자열로. 없으면 null */
function md_xlsx_zip_read( $path, $entry ) {
	if ( class_exists( 'ZipArchive' ) ) {
		$z = new ZipArchive();
		if ( true !== $z->open( $path ) ) { return null; }
		$s = $z->getFromName( $entry );
		$z->close();
		return false === $s ? null : $s;
	}
	if ( ! class_exists( 'PclZip' ) ) {
		$pcl = defined( 'ABSPATH' ) ? ABSPATH . 'wp-admin/includes/class-pclzip.php' : '';
		if ( '' !== $pcl && file_exists( $pcl ) ) { require_once $pcl; }
	}
	if ( ! class_exists( 'PclZip' ) ) { return null; }
	$z   = new PclZip( $path );
	$out = $z->extract( PCLZIP_OPT_BY_NAME, $entry, PCLZIP_OPT_EXTRACT_AS_STRING );
	if ( ! is_array( $out ) || empty( $out ) || ! isset( $out[0]['content'] ) ) { return null; }
	return (string) $out[0]['content'];
}

define( 'MD_XLSX_NS', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main' );

/**
 * XML 문자열 → SimpleXML. 덴트웹 파일은 BOM 으로 시작하고 요소에 x: 접두어를 붙이므로
 * BOM 을 떼고, 자식은 항상 children( MD_XLSX_NS ) 로 읽는다.
 */
function md_xlsx_xml( $s ) {
	$s = (string) $s;
	if ( "\xEF\xBB\xBF" === substr( $s, 0, 3 ) ) { $s = substr( $s, 3 ); }
	$prev = libxml_use_internal_errors( true );
	$x = simplexml_load_string( $s );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );
	/* SimpleXML 요소는 접두어 없는 자식이 없으면 bool 로 false 가 된다 — instanceof 로 판단해야 한다 */
	return ( $x instanceof SimpleXMLElement ) ? $x : null;
}

/** 요소의 자식(스프레드시트 네임스페이스) */
function md_xlsx_kids( $el ) {
	return $el->children( MD_XLSX_NS );
}

/** 열 글자(A, B, …, AA) → 0부터 시작하는 번호 */
function md_xlsx_col_index( $ref ) {
	$letters = preg_replace( '/[^A-Z]/', '', strtoupper( (string) $ref ) );
	$n = 0;
	for ( $i = 0, $len = strlen( $letters ); $i < $len; $i++ ) { $n = $n * 26 + ( ord( $letters[ $i ] ) - 64 ); }
	return max( 0, $n - 1 );
}

/** 엑셀 날짜 일련번호 → 'Y-m-d' (1900 기준) */
function md_xlsx_serial_to_date( $n ) {
	$n = (float) $n;
	if ( $n < 1 ) { return ''; }
	$ts = (int) round( ( $n - 25569 ) * 86400 ); /* 25569 = 1970-01-01 */
	return gmdate( 'Y-m-d', $ts );
}

/**
 * 첫 시트 → 행 배열. 각 행은 열 번호를 키로 하는 배열 (빈 셀은 없음).
 * 실패하면 WP_Error 대신 문자열 오류를 돌려준다 (WP 밖에서도 쓰려고).
 *
 * @return array|string  array(rows) 또는 오류 메시지
 */
function md_xlsx_rows( $path ) {
	if ( ! is_readable( $path ) ) { return '파일을 읽을 수 없습니다.'; }
	if ( ! function_exists( 'simplexml_load_string' ) ) { return '서버에 XML 처리 기능이 없습니다.'; }

	$shared = array();
	$ss = md_xlsx_zip_read( $path, 'xl/sharedStrings.xml' );
	if ( null !== $ss ) {
		$x = md_xlsx_xml( $ss );
		if ( null !== $x ) {
			foreach ( md_xlsx_kids( $x )->si as $si ) {
				/* 서식 있는 문장은 <r><t>조각</t></r> 여러 개 — 전부 이어 붙인다 */
				$k = md_xlsx_kids( $si );
				$t = '';
				if ( isset( $k->t ) ) { $t = (string) $k->t; }
				elseif ( isset( $k->r ) ) { foreach ( $k->r as $r ) { $t .= (string) md_xlsx_kids( $r )->t; } }
				$shared[] = $t;
			}
		}
	}

	/* 첫 시트 — 보통 sheet1.xml. 없으면 workbook.xml.rels 로 찾아본다 */
	$sheet = md_xlsx_zip_read( $path, 'xl/worksheets/sheet1.xml' );
	if ( null === $sheet ) {
		$rels = md_xlsx_zip_read( $path, 'xl/_rels/workbook.xml.rels' );
		if ( null !== $rels && preg_match( '#Target="(/?xl/)?(worksheets/[^"]+\.xml)"#', $rels, $m ) ) {
			$sheet = md_xlsx_zip_read( $path, 'xl/' . $m[2] );
		}
	}
	if ( null === $sheet ) { return '엑셀 파일 안에서 시트를 찾지 못했습니다. .xlsx 형식인지 확인해 주세요.'; }

	$x = md_xlsx_xml( $sheet );
	if ( null === $x || ! isset( md_xlsx_kids( $x )->sheetData ) ) { return '엑셀 시트를 읽지 못했습니다.'; }
	$sd = md_xlsx_kids( $x )->sheetData;

	$rows = array();
	foreach ( md_xlsx_kids( $sd )->row as $row ) {
		$out = array();
		$auto = 0;
		foreach ( md_xlsx_kids( $row )->c as $c ) {
			/* children( ns ) 로 얻은 요소는 $c['r'] 이 비므로 attributes() 로 읽는다 */
			$at   = $c->attributes();
			$ref  = isset( $at['r'] ) ? (string) $at['r'] : '';
			$idx  = '' !== $ref ? md_xlsx_col_index( $ref ) : $auto;
			$auto = $idx + 1;
			$type = isset( $at['t'] ) ? (string) $at['t'] : '';
			$ck   = md_xlsx_kids( $c );
			$v = '';
			if ( 's' === $type ) {
				$i = (int) $ck->v;
				$v = isset( $shared[ $i ] ) ? $shared[ $i ] : '';
			} elseif ( 'inlineStr' === $type ) {
				$is = isset( $ck->is ) ? md_xlsx_kids( $ck->is ) : null;
				$v  = $is && isset( $is->t ) ? (string) $is->t : '';
				if ( '' === $v && $is && isset( $is->r ) ) { foreach ( $is->r as $r ) { $v .= (string) md_xlsx_kids( $r )->t; } }
			} elseif ( 'b' === $type ) {
				$v = '1' === (string) $ck->v ? 'TRUE' : 'FALSE';
			} else {
				$v = isset( $ck->v ) ? (string) $ck->v : '';
			}
			$v = trim( $v );
			if ( '' !== $v ) { $out[ $idx ] = $v; }
		}
		if ( ! empty( $out ) ) { $rows[] = $out; }
	}
	return $rows;
}
