<?php
/**
 * 상담예약 폼 — AJAX 핸들러 + 이메일 발송 + 간단한 DB 보관(post_meta).
 *
 * 보안:
 *  - nonce 검증
 *  - 모든 입력 sanitize
 *  - 동일 IP 60초 이내 중복 제출 차단 (transient)
 *  - 1회 제출당 1통의 메일만 발송
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * Frontend 스크립트가 사용할 nonce·ajax url 주입.
 */
function moondental_reservation_enqueue() {
	if ( ! is_page() ) return;
	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	if ( ! in_array( $slug, array( '상담예약', 'reservation' ), true ) ) return;

	$js_path = MOONDENTAL_DIR . '/assets/js/reservation.js';
	if ( file_exists( $js_path ) ) {
		wp_enqueue_script(
			'moondental-reservation',
			MOONDENTAL_URI . '/assets/js/reservation.js',
			array(),
			filemtime( $js_path ),
			true
		);
		wp_localize_script( 'moondental-reservation', 'MoondentalRes', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'moondental_reservation' ),
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'moondental_reservation_enqueue', 20 );


/**
 * AJAX 핸들러 — 폼 데이터 받아 검증·이메일 발송·완료 응답.
 */
function moondental_handle_reservation() {
	check_ajax_referer( 'moondental_reservation', '_nonce' );

	// 60초 중복 제출 차단
	$ip       = $_SERVER['REMOTE_ADDR'] ?? '';
	$tkey     = 'moondental_res_throttle_' . md5( $ip );
	if ( get_transient( $tkey ) ) {
		wp_send_json_error( array( 'message' => '잠시 후 다시 시도해주세요.' ), 429 );
	}

	$service = sanitize_text_field( $_POST['service'] ?? '' );
	$date    = sanitize_text_field( $_POST['date']    ?? '' );
	$time    = sanitize_text_field( $_POST['time']    ?? '' );
	$name    = sanitize_text_field( $_POST['name']    ?? '' );
	$phone   = sanitize_text_field( $_POST['phone']   ?? '' );
	$note    = sanitize_textarea_field( $_POST['note'] ?? '' );
	$agree   = ! empty( $_POST['agree_privacy'] );
	$marketing = ! empty( $_POST['agree_marketing'] );

	// 필수 항목
	$errors = array();
	if ( ! $service ) $errors[] = '진료항목';
	if ( ! $date )    $errors[] = '희망 날짜';
	if ( ! $time )    $errors[] = '희망 시간';
	if ( ! $name )    $errors[] = '성함';
	if ( ! $phone )   $errors[] = '연락처';
	if ( ! $agree )   $errors[] = '개인정보 동의';

	if ( $errors ) {
		wp_send_json_error( array( 'message' => '필수 항목이 비어 있습니다: ' . implode( ', ', $errors ) ), 400 );
	}

	// 전화번호 형식 (한국)
	if ( ! preg_match( '/^[0-9]{2,4}-?[0-9]{3,4}-?[0-9]{4}$/', preg_replace( '/[^0-9-]/', '', $phone ) ) ) {
		wp_send_json_error( array( 'message' => '연락처 형식이 올바르지 않습니다.' ), 400 );
	}

	// 예약 번호 생성
	$res_no = 'M' . date( 'ymd' ) . '-' . strtoupper( substr( md5( $ip . microtime() ), 0, 5 ) );

	// 이메일 본문
	$info     = moondental_get_info();
	$to       = ! empty( $info['email'] ) ? $info['email'] : get_option( 'admin_email' );
	$subject  = sprintf( '[문치과 상담예약] %s · %s %s', $name, $date, $time );

	$body  = "새 상담예약이 접수되었습니다.\n\n";
	$body .= "예약번호 : {$res_no}\n";
	$body .= "성함     : {$name}\n";
	$body .= "연락처   : {$phone}\n";
	$body .= "진료항목 : {$service}\n";
	$body .= "희망일시 : {$date} {$time}\n";
	$body .= "마케팅 수신 동의 : " . ( $marketing ? '예' : '아니오' ) . "\n";
	$body .= "\n--- 증상/문의 ---\n" . ( $note ?: '(없음)' ) . "\n";
	$body .= "\n----\nIP: {$ip}\nUA: " . ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) . "\n";
	$body .= "접수시각: " . current_time( 'Y-m-d H:i:s' ) . "\n";

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$mail_ok = wp_mail( $to, $subject, $body, $headers );

	// 보관 — Custom Post Type 없이 옵션 배열에 누적 (관리자 도구에서 열람용)
	$archive = get_option( 'moondental_reservations', array() );
	if ( ! is_array( $archive ) ) $archive = array();
	array_unshift( $archive, array(
		'no'        => $res_no,
		'service'   => $service,
		'date'      => $date,
		'time'      => $time,
		'name'      => $name,
		'phone'     => $phone,
		'note'      => $note,
		'marketing' => $marketing,
		'ip'        => $ip,
		'received'  => current_time( 'mysql' ),
		'mail_ok'   => $mail_ok ? 1 : 0,
	) );
	// 최근 500건만 유지
	$archive = array_slice( $archive, 0, 500 );
	update_option( 'moondental_reservations', $archive, false );

	// throttle 활성화
	set_transient( $tkey, 1, 60 );

	wp_send_json_success( array(
		'res_no'   => $res_no,
		'service'  => $service,
		'datetime' => $date . ' ' . $time,
		'name'     => $name,
		'mail_ok'  => $mail_ok,
	) );
}
add_action( 'wp_ajax_moondental_reservation',        'moondental_handle_reservation' );
add_action( 'wp_ajax_nopriv_moondental_reservation', 'moondental_handle_reservation' );


/**
 * 진료항목 옵션 (예약 폼 1단계).
 */
function moondental_reservation_services() {
	return array(
		array( 'value' => '임플란트',          'title' => '임플란트',        'desc' => '치아 식립·뼈이식·재식립' ),
		array( 'value' => '투명교정',          'title' => '투명교정',        'desc' => '슈어스마일·설측·일반교정' ),
		array( 'value' => '자연치아 살리기',  'title' => '자연치아 살리기', 'desc' => '신경·근관·치근단 보존' ),
		array( 'value' => '턱관절 클리닉',     'title' => '턱관절 클리닉',   'desc' => '통증·소리·이갈이' ),
		array( 'value' => '사랑니 발치',       'title' => '사랑니 발치',     'desc' => '매복 사랑니 안전 발치' ),
		array( 'value' => '심미치료',          'title' => '심미치료',        'desc' => '라미네이트·미백·보철' ),
		array( 'value' => '소아·예방진료',     'title' => '소아·예방진료',   'desc' => '아이 치아·정기 검진' ),
		array( 'value' => '일반/기타',         'title' => '일반/기타 상담',  'desc' => '충치·잇몸·스케일링 등' ),
	);
}
