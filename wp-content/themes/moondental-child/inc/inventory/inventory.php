<?php
/**
 * 재고관리 v5 — 직원 라운지 › 재고관리 (AppSheet 판을 대신한다, v4.22)
 *
 *   inv-schema.php     테이블
 *   inv-core.php       설정 · 권한 · 품목 · 원장 · 요청 · 주문 · 선납 · 통계
 *   inv-admin.php      팀 · 업체 · 분류 · 계정
 *   inv-export.php     CSV · 엑셀
 *   inv-backup.php     백업 · 되돌리기 · 정기 보고서 메일 · 예약 작업
 *   inv-import.php     AppSheet 엑셀 가져오기
 *   inv-actions.php    폼 처리 · 내려받기
 *   inv-ui.php         화면 뼈대
 *   inv-view-*.php     화면
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_INV_DIR', __DIR__ );

/* v5.8 · 라운지 계정 (회원가입 신청 · 승인 · 권한 · 내 정보) — 재료실과 따로 읽는다 */
if ( is_readable( dirname( __DIR__ ) . '/accounts/accounts.php' ) && filesize( dirname( __DIR__ ) . '/accounts/accounts.php' ) > 100 ) {
	require_once dirname( __DIR__ ) . '/accounts/accounts.php';
}

/* 배포 중 파일이 하나라도 비면(자동 배포가 쓰는 수십 초) 모듈 전체를 쉬게 한다 —
 * 반쪽만 올라와 함수가 없는 채로 돌면 사이트 전체가 500 이 된다. */
$md_inv_files = array( 'inv-schema', 'inv-core', 'inv-admin', 'inv-export', 'inv-backup', 'inv-import', 'inv-actions', 'inv-ui', 'inv-view-req', 'inv-view-admin', 'inv-view-stats', 'inv-view-settings', 'inv-view-tools', 'inv-view-more', 'inv-prepaid', 'inv-extra', 'inv-cattool', 'inv-catnav', 'inv-adj', 'inv-money', 'inv-photo' );
$md_inv_ok    = true;
foreach ( $md_inv_files as $md_inv_f ) {
	if ( ! is_readable( MD_INV_DIR . '/' . $md_inv_f . '.php' ) || filesize( MD_INV_DIR . '/' . $md_inv_f . '.php' ) < 100 ) { $md_inv_ok = false; }
}
if ( $md_inv_ok ) {
	foreach ( $md_inv_files as $md_inv_f ) { require_once MD_INV_DIR . '/' . $md_inv_f . '.php'; }
}
unset( $md_inv_f, $md_inv_files );
if ( ! $md_inv_ok ) { unset( $md_inv_ok ); return; }
unset( $md_inv_ok );

/** 재고관리 화면에서만 CSS · JS */
function md_inv_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'stock' !== md_sup_current_app() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	foreach ( array( 'css' => '/assets/css/inventory.css', 'js' => '/assets/js/inventory.js' ) as $kind => $rel ) {
		if ( ! file_exists( $dir . $rel ) ) { continue; }
		if ( 'css' === $kind ) {
			wp_enqueue_style( 'md-inventory', $uri . $rel, array( 'moondental-supply' ), filemtime( $dir . $rel ) );
		} else {
			wp_enqueue_script( 'md-inventory', $uri . $rel, array(), filemtime( $dir . $rel ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'md_inv_enqueue', 40 );

/** 라운지 탭 제목에 대기 건수 — 「(3) 직원 라운지」 */
function md_inv_title_badge( $parts ) {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() || ! is_user_logged_in() || ! md_inv_is_admin() ) { return $parts; }
	if ( (int) get_option( 'md_inv_schema', 0 ) < 1 ) { return $parts; }
	$n = md_inv_counts()['pending'];
	if ( $n > 0 && isset( $parts['title'] ) ) { $parts['title'] = '(' . $n . ') ' . $parts['title']; }
	return $parts;
}
add_filter( 'document_title_parts', 'md_inv_title_badge', 20 );
