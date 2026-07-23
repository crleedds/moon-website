<?php
/**
 * 상담예약 폼 — AJAX 핸들러 + 이메일 발송 + CPT 보관.
 *
 * 보안 (v3.37.0 강화):
 *  - nonce 검증
 *  - 모든 입력 sanitize
 *  - 동일 IP 60초 이내 중복 제출 차단 (transient)
 *  - 허니팟 필드 (봇 감지)
 *  - 최소 폼 작성 시간 (2초 미만 = 봇)
 *  - CPT (md_reservation) 저장 · non-public · edit_others_posts 제한
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


/* ============================================================
 * 1. 예약 CPT · v3.37.0 · options → CPT 이관
 * ========================================================== */
function moondental_register_reservation_cpt() {
	register_post_type( 'md_reservation', array(
		'labels' => array(
			'name'          => '상담예약 접수',
			'singular_name' => '예약',
			'menu_name'     => '📋 예약 접수',
			'all_items'     => '모든 예약',
			'view_item'     => '예약 보기',
			'search_items'  => '예약 검색',
			'not_found'     => '접수된 예약이 없습니다.',
		),
		'public'              => false,        // 프론트 노출 X
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'menu_position'       => 22,
		'menu_icon'           => 'dashicons-clipboard',
		'supports'            => array( 'title', 'custom-fields' ),
		'capabilities'        => array(
			'create_posts'       => 'do_not_allow',   // wp-admin에서 수동 생성 X
			'edit_post'          => 'edit_others_posts',
			'edit_posts'         => 'edit_others_posts',
			'edit_others_posts'  => 'edit_others_posts',
			'delete_post'        => 'delete_others_posts',
			'delete_posts'       => 'delete_others_posts',
			'read_post'          => 'edit_others_posts',
			'read_private_posts' => 'edit_others_posts',
		),
		'map_meta_cap'        => true,
	) );
}
add_action( 'init', 'moondental_register_reservation_cpt', 5 );

/* CPT rewrite flush */
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_reservation_cpt_flush_v3370' ) === 'done' ) return;
	moondental_register_reservation_cpt();
	flush_rewrite_rules( false );
	update_option( 'moondental_reservation_cpt_flush_v3370', 'done' );
} );


/**
 * Frontend 스크립트가 사용할 nonce·ajax url·timestamp 주입.
 */
function moondental_reservation_enqueue() {
	if ( ! is_page() ) return;
	$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
	if ( ! in_array( $slug, array( '상담예약', 'reservation' ), true ) ) return;

	if ( ! moondental_page_has_reservation_form() ) return;

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
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'moondental_reservation' ),
			'renderTime' => time(), // v3.37.0 · 폼 렌더링 시각 · 최소 작성 시간 체크
			// v3.38.0 · 모든 사용자 노출 문자열 Customizer에서 편집
			'msg' => array(
				'alertSvc'       => md_content( 'res_alert_svc',       '진료항목을 선택해주세요.' ),
				'alertDate'      => md_content( 'res_alert_date',      '희망 날짜를 선택해주세요.' ),
				'alertTime'      => md_content( 'res_alert_time',      '희망 시간을 선택해주세요.' ),
				'alertName'      => md_content( 'res_alert_name',      '성함을 입력해주세요.' ),
				'alertPhone'     => md_content( 'res_alert_phone',     '연락처를 입력해주세요.' ),
				'alertPhoneFmt'  => md_content( 'res_alert_phone_fmt', '연락처 형식이 올바르지 않습니다. (예: 010-1234-5678)' ),
				'alertPrivacy'   => md_content( 'res_alert_privacy',   '개인정보 처리방침 동의가 필요합니다.' ),
				'hintDefault'    => md_content( 'res_hint_date_default', '선택하신 요일에 따라 가능한 시간이 표시됩니다.' ),
				'hintClosed'     => md_content( 'res_hint_date_closed',  '⚠️ 선택하신 날짜는 휴진일입니다. 다른 날짜를 선택해주세요.' ),
				'hintOpen'       => md_content( 'res_hint_date_open',    '✓ {day}요일 진료 가능 시간: 09:00 – {until}' ),
				'btnSending'     => md_content( 'res_btn_sending', '전송 중...' ),
				'btnSubmit'      => md_content( 'res_btn_submit',  '예약 신청' ),
				'alertFail'      => md_content( 'res_alert_fail',    '예약 전송에 실패했습니다.' ),
				'alertNetwork'   => md_content( 'res_alert_network', '네트워크 오류 — 잠시 후 다시 시도해주세요.' ),
				'successTitle'   => md_content( 'res_success_title', '예약 신청이 완료되었습니다!' ),
				'successLead'    => md_content( 'res_success_lead',  '담당자가 확인 후 빠른 시간 내에 연락드리겠습니다.' ),
				'successLblNo'   => md_content( 'res_success_lbl_no',   '예약번호' ),
				'successLblSvc'  => md_content( 'res_success_lbl_svc',  '진료항목' ),
				'successLblDt'   => md_content( 'res_success_lbl_dt',   '희망일시' ),
				'successLblName' => md_content( 'res_success_lbl_name', '예약자' ),
				'successHint'    => md_content( 'res_success_hint', '예약 확정 전 변경이 필요하시면 전화 또는 카카오톡으로 연락주세요.' ),
				'successBtnKakao' => md_content( 'res_success_btn_kakao', '💬 카카오톡 친구 추가' ),
				'successBtnHome' => md_content( 'res_success_btn_home', '홈으로' ),
				'kakaoUrl'       => moondental_get_info()['kakao_url'] ?? 'http://pf.kakao.com/_VTcgE/chat',
				'homeUrl'        => home_url( '/' ),
			),
		) );
	}
}
add_action( 'wp_enqueue_scripts', 'moondental_reservation_enqueue', 20 );

/**
 * v3.30.0 · 페이지 콘텐츠에 실제 예약 폼 마커가 있는지 확인.
 */
function moondental_page_has_reservation_form() {
	$content = get_the_content();
	return $content && strpos( $content, 'md-reservation-form' ) !== false;
}


/**
 * AJAX 핸들러 — 폼 데이터 받아 검증·이메일 발송·완료 응답.
 */
function moondental_handle_reservation() {
	check_ajax_referer( 'moondental_reservation', '_nonce' );

	// v3.37.0 · 허니팟 · 봇이 채우는 숨겨진 필드 (사람은 못 봄)
	if ( ! empty( $_POST['md_hp_website'] ) ) {
		// 봇 감지 · 200 OK로 조용히 종료 (봇에게 힌트 주지 않음)
		wp_send_json_success( array( 'res_no' => 'HP0000' ) );
	}

	// v3.37.0 · 최소 작성 시간 체크 (2초 미만 = 봇)
	$msg_throttle = md_content( 'res_msg_throttle', '잠시 후 다시 시도해주세요.' );
	$render_time = isset( $_POST['md_form_render_time'] ) ? absint( $_POST['md_form_render_time'] ) : 0;
	if ( $render_time > 0 && ( time() - $render_time ) < 2 ) {
		wp_send_json_error( array( 'message' => $msg_throttle ), 429 );
	}

	// 60초 중복 제출 차단
	$ip_raw   = $_SERVER['REMOTE_ADDR'] ?? '';
	$ip       = sanitize_text_field( $ip_raw ); // v3.37.0 · 명시적 sanitize
	$tkey     = 'moondental_res_throttle_' . md5( $ip );
	if ( get_transient( $tkey ) ) {
		wp_send_json_error( array( 'message' => $msg_throttle ), 429 );
	}

	$service = sanitize_text_field( $_POST['service'] ?? '' );
	$date    = sanitize_text_field( $_POST['date']    ?? '' );
	$time    = sanitize_text_field( $_POST['time']    ?? '' );
	$name    = sanitize_text_field( $_POST['name']    ?? '' );
	$phone   = sanitize_text_field( $_POST['phone']   ?? '' );
	$note    = sanitize_textarea_field( $_POST['note'] ?? '' );
	$agree   = ! empty( $_POST['agree_privacy'] );
	$marketing = ! empty( $_POST['agree_marketing'] );
	$ua      = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ); // v3.37.0

	// 필수 항목 · v3.38.0 라벨은 Customizer에서 편집
	$errors = array();
	if ( ! $service ) $errors[] = md_content( 'res_field_service', '진료항목' );
	if ( ! $date )    $errors[] = md_content( 'res_field_date',    '희망 날짜' );
	if ( ! $time )    $errors[] = md_content( 'res_field_time',    '희망 시간' );
	if ( ! $name )    $errors[] = md_content( 'res_field_name',    '성함' );
	if ( ! $phone )   $errors[] = md_content( 'res_field_phone',   '연락처' );
	if ( ! $agree )   $errors[] = md_content( 'res_field_privacy', '개인정보 동의' );

	if ( $errors ) {
		$msg = str_replace( '{fields}', implode( ', ', $errors ),
			md_content( 'res_msg_required', '필수 항목이 비어 있습니다: {fields}' ) );
		wp_send_json_error( array( 'message' => $msg ), 400 );
	}

	// 전화번호 형식 (한국)
	if ( ! preg_match( '/^[0-9]{2,4}-?[0-9]{3,4}-?[0-9]{4}$/', preg_replace( '/[^0-9-]/', '', $phone ) ) ) {
		wp_send_json_error( array( 'message' => md_content( 'res_msg_phone_invalid', '연락처 형식이 올바르지 않습니다.' ) ), 400 );
	}

	// 예약 번호 생성
	$res_no = 'M' . date( 'ymd' ) . '-' . strtoupper( substr( md5( $ip . microtime() ), 0, 5 ) );

	// 이메일 본문 · v3.38.0 모든 라벨 Customizer
	$info     = moondental_get_info();
	$to       = ! empty( $info['email'] ) ? $info['email'] : get_option( 'admin_email' );
	$subject  = str_replace(
		array( '{name}', '{date}', '{time}' ),
		array( $name, $date, $time ),
		md_content( 'res_email_subject_tpl', '[문치과 상담예약] {name} · {date} {time}' )
	);

	$lbl_no      = md_content( 'res_email_lbl_no',      '예약번호' );
	$lbl_name    = md_content( 'res_email_lbl_name',    '성함' );
	$lbl_phone   = md_content( 'res_email_lbl_phone',   '연락처' );
	$lbl_service = md_content( 'res_email_lbl_service', '진료항목' );
	$lbl_dt      = md_content( 'res_email_lbl_dt',      '희망일시' );
	$lbl_mkt     = md_content( 'res_email_lbl_mkt',     '마케팅 수신 동의' );
	$yes         = md_content( 'res_email_yes',         '예' );
	$no          = md_content( 'res_email_no',          '아니오' );

	$body  = md_content( 'res_email_intro', '새 상담예약이 접수되었습니다.' ) . "\n\n";
	$body .= "{$lbl_no} : {$res_no}\n";
	$body .= "{$lbl_name} : {$name}\n";
	$body .= "{$lbl_phone} : {$phone}\n";
	$body .= "{$lbl_service} : {$service}\n";
	$body .= "{$lbl_dt} : {$date} {$time}\n";
	$body .= "{$lbl_mkt} : " . ( $marketing ? $yes : $no ) . "\n";
	$body .= "\n" . md_content( 'res_email_note_head', '--- 증상/문의 ---' ) . "\n";
	$body .= ( $note ?: md_content( 'res_email_note_empty', '(없음)' ) ) . "\n";
	$body .= "\n----\nIP: {$ip}\nUA: {$ua}\n";
	$body .= md_content( 'res_email_received', '접수시각' ) . ': ' . current_time( 'Y-m-d H:i:s' ) . "\n";

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$mail_ok = wp_mail( $to, $subject, $body, $headers );

	// v3.37.0 · CPT로 저장 (기존 options는 하위 호환 유지, 향후 마이그레이션)
	$post_id = wp_insert_post( array(
		'post_type'   => 'md_reservation',
		'post_status' => 'publish',
		'post_title'  => sprintf( '%s · %s %s · %s', $name, $date, $time, $service ),
		'post_author' => 0,
		'meta_input'  => array(
			'md_res_no'        => $res_no,
			'md_res_name'      => $name,
			'md_res_phone'     => $phone,
			'md_res_service'   => $service,
			'md_res_date'      => $date,
			'md_res_time'      => $time,
			'md_res_note'      => $note,
			'md_res_marketing' => $marketing ? 1 : 0,
			'md_res_ip'        => $ip,
			'md_res_ua'        => $ua,
			'md_res_mail_ok'   => $mail_ok ? 1 : 0,
		),
	), true );

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
 *
 * v3.38.0 · Customizer 텍스트영역 res_services 에서 파싱.
 *  형식: 한 줄에 하나. `value|title|desc`
 *  fallback 은 기본 8개. 빈 값이면 라인 스킵.
 */
function moondental_reservation_services() {
	$raw = md_content( 'res_services', "임플란트|임플란트|치아 식립·뼈이식·재식립\n투명교정|투명교정|슈어스마일·설측·일반교정\n자연치아 살리기|자연치아 살리기|신경·근관·치근단 보존\n턱관절 클리닉|턱관절 클리닉|통증·소리·이갈이\n사랑니 발치|사랑니 발치|매복 사랑니 안전 발치\n심미치료|심미치료|라미네이트·미백·보철\n소아·예방진료|소아·예방진료|아이 치아·정기 검진\n일반/기타|일반/기타 상담|충치·잇몸·스케일링 등" );

	$out = array();
	foreach ( md_parse_lines( $raw ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) < 2 || $parts[0] === '' ) continue;
		$out[] = array(
			'value' => $parts[0],
			'title' => $parts[1] !== '' ? $parts[1] : $parts[0],
			'desc'  => $parts[2] ?? '',
		);
	}
	return $out;
}


/**
 * v3.37.0 · 예약 CPT 관리 화면 · meta 컬럼·검색 개선
 */
function moondental_reservation_admin_columns( $columns ) {
	return array(
		'cb'          => $columns['cb'],
		'title'       => '예약자',
		'md_service'  => '진료항목',
		'md_datetime' => '희망 일시',
		'md_phone'    => '연락처',
		'md_no'       => '예약번호',
		'date'        => '접수일',
	);
}
add_filter( 'manage_md_reservation_posts_columns', 'moondental_reservation_admin_columns' );

function moondental_reservation_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'md_service':
			echo esc_html( get_post_meta( $post_id, 'md_res_service', true ) );
			break;
		case 'md_datetime':
			$d = get_post_meta( $post_id, 'md_res_date', true );
			$t = get_post_meta( $post_id, 'md_res_time', true );
			echo esc_html( trim( $d . ' ' . $t ) );
			break;
		case 'md_phone':
			echo esc_html( get_post_meta( $post_id, 'md_res_phone', true ) );
			break;
		case 'md_no':
			echo esc_html( get_post_meta( $post_id, 'md_res_no', true ) );
			break;
	}
}
add_action( 'manage_md_reservation_posts_custom_column', 'moondental_reservation_admin_column_content', 10, 2 );


/**
 * v3.37.0 · 기존 options 저장 예약 → CPT 이관 마이그레이션 (관리자 첫 방문 시 1회)
 */
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_reservation_option_to_cpt_v3370' ) === 'done' ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;

	$archive = get_option( 'moondental_reservations', array() );
	if ( is_array( $archive ) && $archive ) {
		foreach ( $archive as $r ) {
			// 이미 이관된 것 건너뛰기 (예약번호로)
			$existing = get_posts( array(
				'post_type'      => 'md_reservation',
				'meta_key'       => 'md_res_no',
				'meta_value'     => $r['no'] ?? '',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );
			if ( ! empty( $existing ) ) continue;
			wp_insert_post( array(
				'post_type'   => 'md_reservation',
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s · %s %s · %s',
					$r['name'] ?? '', $r['date'] ?? '', $r['time'] ?? '', $r['service'] ?? '' ),
				'post_date'   => $r['received'] ?? current_time( 'mysql' ),
				'meta_input'  => array(
					'md_res_no'        => $r['no'] ?? '',
					'md_res_name'      => $r['name'] ?? '',
					'md_res_phone'     => $r['phone'] ?? '',
					'md_res_service'   => $r['service'] ?? '',
					'md_res_date'      => $r['date'] ?? '',
					'md_res_time'      => $r['time'] ?? '',
					'md_res_note'      => $r['note'] ?? '',
					'md_res_marketing' => ! empty( $r['marketing'] ) ? 1 : 0,
					'md_res_ip'        => $r['ip'] ?? '',
					'md_res_mail_ok'   => ! empty( $r['mail_ok'] ) ? 1 : 0,
					'md_res_migrated'  => 1,
				),
			) );
		}
	}
	update_option( 'moondental_reservation_option_to_cpt_v3370', 'done' );
} );
