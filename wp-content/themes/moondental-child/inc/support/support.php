<?php
/**
 * v4.6 · 지원 요청 — 직원 전용(/직원/) 허브의 도구 (신청 폼에 담당자·답변란 · 누구나 답변·상태 수정 · 최근 것이 위로)
 *
 *  경영지원실에 부탁할 일(수리 · 확인 요청 …)을 적고, 경영지원실이 답변과
 *  처리 상태를 남기는 화면이다. 예전에는 구글 시트 「문치과병원 경영지원실 요청사항」에
 *  줄을 추가해 썼다. 그 시트의 열(요청일자 · 요청사항 · 요청자 · 요청부서 · 답변 · 담당자 ·
 *  수리일자)을 그대로 옮기되, 자유롭게 적던 진행 상황을 상태 네 단계로 정리했다.
 *
 *    접수 → 진행중 → 완료   (보류는 언제든)
 *
 *  누가 무엇을 하는가
 *    직원 공용 계정  요청 올리기 · 내용 · 답변 · 담당자 · 상태 · 처리일자 고치기 (v4.6 · 누구나)
 *    관리자          위 모두 + 삭제
 *
 *  저장은 전용 테이블 하나. 화면은 서버에서 그린다 — 자바스크립트가 없어도 전부 동작한다.
 *  폼은 POST → 처리 → 리다이렉트(PRG)라 새로고침해도 두 번 올라가지 않는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_SUPPORT_SCHEMA', 1 );

/* ============================================================
 * 테이블
 * ============================================================ */

function md_support_table() {
	global $wpdb;
	return $wpdb->prefix . 'md_support';
}

function md_support_statuses() {
	return array(
		'접수'   => array( 'order' => 1, 'class' => 'is-pending',  'desc' => '아직 확인 전' ),
		'진행중' => array( 'order' => 2, 'class' => 'is-working',  'desc' => '경영지원실이 처리 중' ),
		'보류'   => array( 'order' => 3, 'class' => 'is-hold',     'desc' => '지금은 진행하지 않음' ),
		'완료'   => array( 'order' => 4, 'class' => 'is-done',     'desc' => '처리 끝' ),
	);
}

/**
 * 없으면 만든다. 여러 요청이 동시에 들어와도 한 번만 돌게 add_option 으로 잠근다
 * (백과사전 시더에서 잠금 없이 두 번 삽입된 적이 있다).
 */
function md_support_maybe_install() {
	if ( (int) get_option( 'md_support_schema', 0 ) >= MD_SUPPORT_SCHEMA ) { return; }
	if ( ! add_option( 'md_support_installing', time(), '', 'no' ) ) {
		/* 다른 요청이 설치 중. 5분 넘게 잠겨 있으면 죽은 잠금으로 보고 푼다. */
		if ( time() - (int) get_option( 'md_support_installing' ) < 300 ) { return; }
		delete_option( 'md_support_installing' );
		if ( ! add_option( 'md_support_installing', time(), '', 'no' ) ) { return; }
	}

	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t       = md_support_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE $t (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		created_at DATETIME NOT NULL,
		requester VARCHAR(60) NOT NULL DEFAULT '',
		dept VARCHAR(80) NOT NULL DEFAULT '',
		content TEXT NOT NULL,
		status VARCHAR(12) NOT NULL DEFAULT '접수',
		answer TEXT NULL,
		owner VARCHAR(60) NOT NULL DEFAULT '',
		done_at DATE NULL,
		updated_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY status (status),
		KEY created_at (created_at)
	) $charset;" );

	if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ) ) {
		md_support_seed();
	}

	update_option( 'md_support_schema', MD_SUPPORT_SCHEMA );
	delete_option( 'md_support_installing' );
}
add_action( 'init', 'md_support_maybe_install', 20 );

/** 옛 시트에서 아직 끝나지 않았던 요청 14건 (2026-09-25 기준) */
function md_support_seed() {
	global $wpdb;
	$t    = md_support_table();
	$rows = array(
		array( '2025-10-21', '이창률', '10층 의국', "사용하지 않는 병원 블로그 삭제 요청\nhttps://blog.naver.com/moondental1", '', '카밀라', '접수' ),
		array( '2025-10-21', '이창률', '10층 의국', "사용하지 않는 병원 인스타그램 계정 비활성화(inactivate) 요청\nmoondental_official", '이충현 과장님께서 전 직원에게 연락하여 관련 사항을 전부 전달하였다고 했습니다.', '게렐레', '진행중' ),
		array( '2025-10-22', '이선양', '11층 서비스지원실', '11층 기구실 냉장고 성능이 약해요 (안 시원함)', '부품 단종으로 인해 교체를 진행해야 하나 추후 구매 예정으로 구매 결정시 재공지 예정입니다.', '이충현', '보류' ),
		array( '2025-10-27', '이창률', '10층 의국', '슈어스마일 투명교정 영상 엘레베이터 영상 목록에 추가 요청', '완료했습니다', '이충현', '진행중' ),
		array( '2025-11-07', '이선양', '11층 서비스지원실', '11층 데스크 유선전화기 통화중 끊김현상 - 확인요청 부탁드립니다', '', '', '접수' ),
		array( '2025-11-20', '박혜령', '9층 서비스지원실', '9층 혈압계 측정 오류로 A/S 필요합니다.', '부품 단종으로 보상판매건으로 병원장님 승인 후 진행 예정입니다', '이충현', '진행중' ),
		array( '2026-01-20', '이창률', '10층 의국', '이영일원장님 사진&약력 홈페이지 게시', '', '카밀라', '접수' ),
		array( '2026-01-22', '정소리', '11층 서비스지원실', '11층 대기실 내 원장님 프로필 설치 진행 경과 문의', '', '', '접수' ),
		array( '2026-03-13', '이선양', '11층 서비스지원실', '11층 진료실 유리창 금감', '수리 불가입니다', '이충현', '보류' ),
		array( '2026-04-09', '유현영', '10층 진료실', '수술용핸드피스 sn 08761 드릴링시 안됨(2번수리온 제품)', '26.04.14 A/S접수 진행했습니다.', '이충현', '진행중' ),
		array( '2026-04-28', '유현영', '10층 진료실', '수술용핸드피스 sn 08761 드릴링시 안됨(3번수리온 제품) 무상처리요청요망', '', '이충현', '접수' ),
		array( '2026-04-28', '유현영', '10층 진료실', '수술용핸드피스 01570121 드릴링시 안됨', '', '이충현', '접수' ),
		array( '2026-05-19', '이창률', '10층 의국', '13층 식당 문 닫았을 때 고정이 되지 않습니다.', '', '', '접수' ),
		array( '2026-05-19', '이창률', '10층 의국', '11층 복도로 통하는 문 열었을때 자동으로 닫힐 수 있도록 도어클로저 수리가 필요합니다.', '', '', '접수' ),
	);
	foreach ( $rows as $r ) {
		$wpdb->insert( $t, array(
			'created_at' => $r[0] . ' 09:00:00',
			'requester'  => $r[1],
			'dept'       => $r[2],
			'content'    => $r[3],
			'answer'     => $r[4],
			'owner'      => $r[5],
			'status'     => $r[6],
			'updated_at' => current_time( 'mysql' ),
		) );
	}
}

/** v4.4.1 · 2026-09-25 점검 중 실수로 지워진 요청 1건을 원래 값 그대로 되살린다 (1회) */
function md_support_restore_v441() {
	if ( get_option( 'md_support_restore_v441' ) === 'done' ) { return; }
	global $wpdb;
	$t = md_support_table();
	$content = '11층 복도로 통하는 문 열었을때 자동으로 닫힐 수 있도록 도어클로저 수리가 필요합니다.';
	$exists  = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE content = %s", $content ) );
	if ( ! $exists ) {
		$wpdb->insert( $t, array(
			'created_at' => '2026-05-19 09:00:00',
			'requester'  => '이창률',
			'dept'       => '10층 의국',
			'content'    => $content,
			'answer'     => '',
			'owner'      => '',
			'status'     => '접수',
			'updated_at' => current_time( 'mysql' ),
		) );
	}
	update_option( 'md_support_restore_v441', 'done' );
}
add_action( 'init', 'md_support_restore_v441', 21 );

/* ============================================================
 * 읽기 · 쓰기
 * ============================================================ */

function md_support_can_manage() {
	return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage();
}

function md_support_get( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_support_table() . ' WHERE id = %d', (int) $id ) );
}

/** 목록 — 가장 최근 것이 위로 (v4.6). $status = 'open' 이면 접수·진행중·보류만 */
function md_support_list( $status = '', $q = '' ) {
	global $wpdb;
	$t     = md_support_table();
	$where = array( '1=1' );
	$args  = array();
	if ( 'open' === $status ) { $where[] = "status <> '완료'"; }
	elseif ( '' !== $status && isset( md_support_statuses()[ $status ] ) ) { $where[] = 'status = %s'; $args[] = $status; }
	if ( '' !== $q ) {
		$like    = '%' . $wpdb->esc_like( $q ) . '%';
		$where[] = '(content LIKE %s OR requester LIKE %s OR dept LIKE %s OR answer LIKE %s OR owner LIKE %s)';
		array_push( $args, $like, $like, $like, $like, $like );
	}
	$sql = "SELECT * FROM $t WHERE " . implode( ' AND ', $where )
		. ' ORDER BY created_at DESC, id DESC LIMIT 400';
	return $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql );
}

function md_support_counts() {
	global $wpdb;
	$out = array_fill_keys( array_keys( md_support_statuses() ), 0 );
	foreach ( (array) $wpdb->get_results( 'SELECT status, COUNT(*) n FROM ' . md_support_table() . ' GROUP BY status' ) as $r ) {
		if ( isset( $out[ $r->status ] ) ) { $out[ $r->status ] = (int) $r->n; }
	}
	return $out;
}

function md_support_open_count() {
	$c = md_support_counts();
	return $c['접수'];
}

function md_support_create( $requester, $dept, $content, $status = '접수', $owner = '', $answer = '' ) {
	global $wpdb;
	if ( ! isset( md_support_statuses()[ $status ] ) ) { $status = '접수'; }
	$owner  = mb_substr( sanitize_text_field( $owner ), 0, 60 );
	$answer = trim( sanitize_textarea_field( $answer ) );
	$requester = mb_substr( sanitize_text_field( $requester ), 0, 60 );
	$dept      = mb_substr( sanitize_text_field( $dept ), 0, 80 );
	$content   = trim( sanitize_textarea_field( $content ) );
	if ( '' === $requester ) { return new WP_Error( 'md_support', '요청자 이름을 적어 주세요.' ); }
	if ( '' === $dept )      { return new WP_Error( 'md_support', '요청팀을 골라 주세요.' ); }
	if ( '' === $content )   { return new WP_Error( 'md_support', '요청 내용을 적어 주세요.' ); }

	$ok = $wpdb->insert( md_support_table(), array(
		'created_at' => current_time( 'mysql' ),
		'requester'  => $requester,
		'dept'       => $dept,
		'content'    => $content,
		'answer'     => $answer,
		'owner'      => $owner,
		'status'     => $status,
		'done_at'    => '완료' === $status ? current_time( 'Y-m-d' ) : null,
		'updated_at' => current_time( 'mysql' ),
	) );
	if ( ! $ok ) { return new WP_Error( 'md_support', '저장하지 못했습니다. 잠시 후 다시 시도해 주세요.' ); }
	$id = (int) $wpdb->insert_id;
	md_support_notify_new( $id );
	return $id;
}

/** 답변·담당자·상태·처리일자를 저장한다 — 공용 계정이라 누구나 (v4.6) */
function md_support_answer( $id, $data ) {
	global $wpdb;
	$row = md_support_get( $id );
	if ( ! $row ) { return new WP_Error( 'md_support', '그런 요청이 없습니다.' ); }

	$status = isset( $data['status'] ) && isset( md_support_statuses()[ $data['status'] ] ) ? $data['status'] : $row->status;
	$done   = isset( $data['done_at'] ) ? trim( (string) $data['done_at'] ) : (string) $row->done_at;
	if ( '' !== $done && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $done ) ) { $done = ''; }
	/* 완료로 바꾸면서 처리일자를 비워 두면 오늘로 채운다 — 매번 날짜를 고르지 않아도 되게 */
	if ( '완료' === $status && '' === $done ) { $done = current_time( 'Y-m-d' ); }

	$upd = array(
		'content'    => isset( $data['content'] ) && '' !== trim( sanitize_textarea_field( $data['content'] ) ) ? trim( sanitize_textarea_field( $data['content'] ) ) : $row->content,
		'answer'     => isset( $data['answer'] ) ? trim( sanitize_textarea_field( $data['answer'] ) ) : $row->answer,
		'owner'      => isset( $data['owner'] ) ? mb_substr( sanitize_text_field( $data['owner'] ), 0, 60 ) : $row->owner,
		'status'     => $status,
		'done_at'    => '' === $done ? null : $done,
		'updated_at' => current_time( 'mysql' ),
	);
	$wpdb->update( md_support_table(), $upd, array( 'id' => (int) $id ) );
	return true;
}

/** 요청자가 접수 상태의 내용을 고친다 (공용 계정이라 누구나 — 접수 전까지만) */
function md_support_edit_content( $id, $content ) {
	global $wpdb;
	$row = md_support_get( $id );
	if ( ! $row ) { return new WP_Error( 'md_support', '그런 요청이 없습니다.' ); }
	$content = trim( sanitize_textarea_field( $content ) );
	if ( '' === $content ) { return new WP_Error( 'md_support', '요청 내용을 적어 주세요.' ); }
	$wpdb->update( md_support_table(), array( 'content' => $content, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	return true;
}

function md_support_delete( $id ) {
	global $wpdb;
	return (bool) $wpdb->delete( md_support_table(), array( 'id' => (int) $id ) );
}

/** 새 요청이 오면 재료실과 같은 주소로 메일을 보낸다 (주소가 없으면 조용히 넘어간다) */
function md_support_notify_new( $id ) {
	if ( ! function_exists( 'md_sup_notify_emails' ) ) { return; }
	$to = md_sup_notify_emails();
	if ( empty( $to ) ) { return; }
	$row = md_support_get( $id );
	if ( ! $row ) { return; }
	$subject = '[문치과병원] 경영지원실 지원 요청 — ' . $row->requester . ' · ' . $row->dept;
	$body    = "새 지원 요청이 올라왔습니다.\n\n요청자: {$row->requester}\n요청팀: {$row->dept}\n\n{$row->content}\n\n"
		. md_sup_url( array( 'app' => 'support' ) ) . "\n";
	wp_mail( $to, $subject, $body );
}

/* ============================================================
 * 폼 처리 — template_redirect 에서 먼저 받는다
 * ============================================================ */

function md_support_handle_post() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { return; }
	if ( ! isset( $_POST['md_support_action'] ) ) { return; }
	if ( ! function_exists( 'md_sup_can_use' ) || ! md_sup_can_use() ) { return; }

	$action = sanitize_key( wp_unslash( $_POST['md_support_action'] ) );
	if ( ! isset( $_POST['md_support_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_support_nonce'] ), 'md_support_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}

	$base = md_sup_url( array( 'app' => 'support' ) );
	$keep = array();
	foreach ( array( 'st', 'q' ) as $k ) {
		if ( isset( $_POST[ 'keep_' . $k ] ) && '' !== $_POST[ 'keep_' . $k ] ) { $keep[ $k ] = sanitize_text_field( wp_unslash( $_POST[ 'keep_' . $k ] ) ); }
	}
	$back = add_query_arg( $keep, $base );
	$id   = isset( $_POST['sid'] ) ? (int) $_POST['sid'] : 0;

	switch ( $action ) {
		case 'new':
			$team = isset( $_POST['team'] ) ? sanitize_text_field( wp_unslash( $_POST['team'] ) ) : '';
			$who  = isset( $_POST['requester'] ) ? sanitize_text_field( wp_unslash( $_POST['requester'] ) ) : '';
			$stat = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '접수';
			$res  = md_support_create(
				$who, $team, isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '', $stat,
				isset( $_POST['owner'] ) ? wp_unslash( $_POST['owner'] ) : '',
				isset( $_POST['answer'] ) ? wp_unslash( $_POST['answer'] ) : ''
			);
			if ( ! is_wp_error( $res ) ) {
				/* 다음에 또 올릴 때 팀·이름을 다시 고르지 않아도 되게 30일 기억 */
				$exp = time() + 30 * DAY_IN_SECONDS;
				setcookie( 'md_support_team', $team, $exp, '/', '', is_ssl(), true );
				setcookie( 'md_support_name', $who,  $exp, '/', '', is_ssl(), true );
			}
			$back = is_wp_error( $res )
				? add_query_arg( array( 'err' => $res->get_error_message() ), $base ) . '#new'
				: add_query_arg( array( 'msg' => 'sent' ), $base );
			break;

		case 'edit':
			$res  = md_support_edit_content( $id, isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '' );
			$back = add_query_arg( is_wp_error( $res ) ? array( 'err' => $res->get_error_message() ) : array( 'msg' => 'edited' ), $back ) . '#s' . $id;
			break;

		case 'answer':
			$res = md_support_answer( $id, array(
				'content' => isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '',
				'answer'  => isset( $_POST['answer'] ) ? wp_unslash( $_POST['answer'] ) : '',
				'owner'   => isset( $_POST['owner'] ) ? wp_unslash( $_POST['owner'] ) : '',
				'status'  => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '',
				'done_at' => isset( $_POST['done_at'] ) ? sanitize_text_field( wp_unslash( $_POST['done_at'] ) ) : '',
			) );
			$back = add_query_arg( is_wp_error( $res ) ? array( 'err' => $res->get_error_message() ) : array( 'msg' => 'answered' ), $back ) . '#s' . $id;
			break;

		case 'status':
			$st  = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$res = md_support_answer( $id, array( 'status' => $st ) );
			$back = add_query_arg( is_wp_error( $res ) ? array( 'err' => $res->get_error_message() ) : array( 'msg' => 'status' ), $back ) . '#s' . $id;
			break;

		case 'delete':
			if ( ! md_support_can_manage() ) { break; }
			md_support_delete( $id );
			$back = add_query_arg( 'msg', 'deleted', $back );
			break;
	}

	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_support_handle_post', 1 );

/* ============================================================
 * 화면
 * ============================================================ */

function md_support_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'support' !== md_sup_current_app() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/support.css' ) ) {
		wp_enqueue_style( 'moondental-support', $uri . '/assets/css/support.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/support.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'md_support_enqueue', 31 );

function md_support_notice( $code ) {
	$map = array(
		'sent'     => array( 'ok', '요청을 올렸습니다. 맨 위에 표시됩니다.' ),
		'edited'   => array( 'ok', '요청 내용을 고쳤습니다.' ),
		'answered' => array( 'ok', '답변을 저장했습니다.' ),
		'status'   => array( 'ok', '상태를 바꿨습니다.' ),
		'deleted'  => array( 'ok', '요청을 지웠습니다.' ),
	);
	if ( ! isset( $map[ $code ] ) ) { return ''; }
	list( $type, $text ) = $map[ $code ];
	return '<div class="mds-notice mds-notice--' . esc_attr( $type ) . '">' . esc_html( $text ) . '</div>';
}

function md_support_fmt_date( $dt ) {
	if ( ! $dt ) { return ''; }
	$ts = strtotime( $dt );
	return $ts ? date_i18n( 'Y.m.d', $ts ) : '';
}

/**
 * 요청팀 목록 (v4.5 · 원장 지시)
 *  재고관리 팀에서 출발했지만 지원 요청용으로 정리했다: 9·10·11층 데스크 → 서비스지원실 하나,
 *  층별 공통은 뺌, Dr. 병원장팀 → Dr. 문은수팀, 기타 추가. 재고관리 팀 표는 그대로다.
 */
function md_support_teams() {
	$teams = array(
		'서비스지원실',
		'Dr. 문은수팀', 'Dr. 이창률팀', 'Dr. 권혜진팀', 'Dr. 이승주팀', 'Dr. 이수연팀', 'Dr. 이영일팀', 'Dr. 김세일팀', 'Dr. 정석형팀',
		'기공실', '예방과', '기타',
	);
	return (array) apply_filters( 'md_support_teams', $teams );
}

/** 마지막에 고른 팀·이름 (쿠키, 30일) */
function md_support_remembered( $key ) {
	return isset( $_COOKIE[ 'md_support_' . $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ 'md_support_' . $key ] ) ) : '';
}

/** 새 요청 폼 — 항상 열려 있다. 팀 → 이름 → 내용 → 버튼 하나. */
function md_support_render_new( $err = '' ) {
	$teams = md_support_teams();
	$team  = md_support_remembered( 'team' );
	$name  = md_support_remembered( 'name' );
	if ( '' === $name && md_support_can_manage() ) { $name = wp_get_current_user()->display_name; }
	?>
	<form method="post" class="mds-card mdsp-new" id="new">
		<input type="hidden" name="md_support_action" value="new">
		<input type="hidden" name="md_support_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_support_new' ) ); ?>">
		<div class="mdsp-new__row">
			<label class="mdsp-field">
				<span>요청팀</span>
				<select name="team" required>
					<option value="">팀 선택</option>
					<?php foreach ( $teams as $t ) : ?><option value="<?php echo esc_attr( $t ); ?>" <?php selected( $t, $team ); ?>><?php echo esc_html( $t ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label class="mdsp-field">
				<span>이름</span>
				<input type="text" name="requester" required maxlength="60" value="<?php echo esc_attr( $name ); ?>" placeholder="요청하는 사람">
			</label>
		</div>
		<label class="mdsp-field">
			<span>무엇이 필요한가요?</span>
			<textarea name="content" required rows="3" placeholder="예) 10층 3번 체어 석션 약함 · 11층 데스크 전화기 끊김 · 프린터 토너 구매"></textarea>
		</label>
		<div class="mdsp-new__row mdsp-new__row--office">
			<label class="mdsp-field">
				<span>경영지원실 담당자 <small>(비워 둬도 됨)</small></span>
				<input type="text" name="owner" maxlength="60" placeholder="담당자 이름">
			</label>
			<label class="mdsp-field">
				<span>경영지원실 답변 <small>(비워 둬도 됨)</small></span>
				<textarea name="answer" rows="1" placeholder="처리 내용이나 예정"></textarea>
			</label>
		</div>
		<div class="mdsp-new__foot">
			<div class="mdsp-status-pick" role="radiogroup" aria-label="상태">
				<?php foreach ( md_support_statuses() as $st => $info ) : ?>
					<label class="mdsp-status-pick__opt"><input type="radio" name="status" value="<?php echo esc_attr( $st ); ?>" <?php checked( '접수', $st ); ?>><span class="mds-status <?php echo esc_attr( $info['class'] ); ?>"><?php echo esc_html( $st ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<button type="submit" class="mds-btn mds-btn--fill mdsp-new__btn">요청 올리기</button>
			<span class="mds-hint">올린 뒤에도 내용 · 답변 · 담당자 · 상태를 누구나 고칠 수 있습니다.</span>
		</div>
	</form>
	<?php
}

/** 요청 한 장 */
function md_support_render_card( $r, $manage, $keep ) {
	$sts  = md_support_statuses();
	$cls  = isset( $sts[ $r->status ] ) ? $sts[ $r->status ]['class'] : 'is-pending';
	$sid  = (int) $r->id;
	$hidden = '<input type="hidden" name="sid" value="' . $sid . '">';
	foreach ( $keep as $k => $v ) { $hidden .= '<input type="hidden" name="keep_' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">'; }
	$has_answer = '' !== trim( (string) $r->answer );
	?>
	<article class="mds-card mdsp-item mdsp-item--<?php echo esc_attr( $cls ); ?>" id="s<?php echo $sid; ?>">
		<div class="mdsp-item__head">
			<span class="mds-status <?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $r->status ); ?></span>
			<span class="mdsp-item__team"><?php echo esc_html( $r->dept ); ?></span>
			<span class="mdsp-item__who"><?php echo esc_html( $r->requester ); ?></span>
			<span class="mdsp-item__date"><?php echo esc_html( md_support_fmt_date( $r->created_at ) ); ?></span>
		</div>
		<p class="mdsp-item__body"><?php echo nl2br( esc_html( $r->content ) ); ?></p>

		<?php if ( $has_answer || $r->owner || $r->done_at ) : ?>
			<div class="mdsp-answer">
				<span class="mdsp-answer__label">경영지원실 답변</span>
				<?php if ( $has_answer ) : ?><p><?php echo nl2br( esc_html( $r->answer ) ); ?></p><?php endif; ?>
				<span class="mdsp-answer__meta"><?php if ( $r->owner ) : ?>담당 <?php echo esc_html( $r->owner ); ?><?php endif; ?><?php if ( $r->done_at ) : ?> · 처리 <?php echo esc_html( md_support_fmt_date( $r->done_at ) ); ?><?php endif; ?></span>
			</div>
		<?php endif; ?>

		<div class="mdsp-actions">
				<?php foreach ( array( '접수' => '↩ 접수', '진행중' => '▶ 진행중', '보류' => '⏸ 보류', '완료' => '✓ 완료' ) as $st => $label ) : if ( $st === $r->status ) { continue; } ?>
					<form method="post" class="mdsp-inline">
						<input type="hidden" name="md_support_action" value="status"><input type="hidden" name="md_support_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_support_status' ) ); ?>"><input type="hidden" name="status" value="<?php echo esc_attr( $st ); ?>"><?php echo $hidden; // phpcs:ignore ?>
						<button type="submit" class="mdsp-btn<?php echo '완료' === $st ? ' mdsp-btn--done' : ''; ?>"><?php echo esc_html( $label ); ?></button>
					</form>
				<?php endforeach; ?>
				<details class="mdsp-more">
					<summary class="mdsp-btn">✏️ 답변<?php echo $has_answer ? ' 고치기' : ' 쓰기'; ?> · 담당자 · 내용 수정</summary>
					<form method="post" class="mdsp-answerform">
						<input type="hidden" name="md_support_action" value="answer"><input type="hidden" name="md_support_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_support_answer' ) ); ?>"><?php echo $hidden; // phpcs:ignore ?>
						<label class="mdsp-field"><span>요청 내용 (고칠 수 있음)</span><textarea name="content" rows="2"><?php echo esc_textarea( $r->content ); ?></textarea></label>
						<label class="mdsp-field"><span>답변</span><textarea name="answer" rows="3" placeholder="처리 내용이나 예정을 적어 주세요"><?php echo esc_textarea( (string) $r->answer ); ?></textarea></label>
						<div class="mdsp-answerform__row mdsp-answerform__row--2">
							<label class="mdsp-field"><span>경영지원실 담당자</span><input type="text" name="owner" maxlength="60" value="<?php echo esc_attr( (string) $r->owner ); ?>" placeholder="담당자 이름"></label>
							<label class="mdsp-field"><span>처리일</span><input type="date" name="done_at" value="<?php echo esc_attr( (string) $r->done_at ); ?>"></label>
						</div>
						<div class="mdsp-answerform__foot">
							<div class="mdsp-status-pick" role="radiogroup" aria-label="상태">
								<?php foreach ( $sts as $st => $info ) : ?>
									<label class="mdsp-status-pick__opt"><input type="radio" name="status" value="<?php echo esc_attr( $st ); ?>" <?php checked( $st, $r->status ); ?>><span class="mds-status <?php echo esc_attr( $info['class'] ); ?>"><?php echo esc_html( $st ); ?></span></label>
								<?php endforeach; ?>
							</div>
							<button type="submit" class="mds-btn mds-btn--fill mdsp-btn mdsp-btn--save">저장</button>
						</div>
					</form>
				</details>
				<?php if ( $manage ) : ?>
				<form method="post" class="mdsp-inline mdsp-del" onsubmit="return confirm('이 요청을 지울까요? 되돌릴 수 없습니다.');">
					<input type="hidden" name="md_support_action" value="delete"><input type="hidden" name="md_support_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_support_delete' ) ); ?>"><?php echo $hidden; // phpcs:ignore ?>
					<button type="submit" class="mdsp-btn mdsp-btn--del" title="삭제">🗑</button>
				</form>
				<?php endif; ?>
			</div>
	</article>
	<?php
}

/** 전체 화면 */
function md_support_render() {
	$manage = md_support_can_manage();
	$sts    = md_support_statuses();
	$st     = isset( $_GET['st'] ) ? sanitize_text_field( wp_unslash( $_GET['st'] ) ) : '';
	if ( '' !== $st && ! isset( $sts[ $st ] ) ) { $st = ''; }
	$list_st = $st; /* 기본 화면은 전체(최근순) */
	$q      = isset( $_GET['q'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : '';
	$counts = md_support_counts();
	$rows   = md_support_list( $list_st, $q );
	$keep   = array_filter( array( 'st' => $st, 'q' => $q ), 'strlen' );

	if ( isset( $_GET['msg'] ) ) { echo md_support_notice( sanitize_key( wp_unslash( $_GET['msg'] ) ) ); } // phpcs:ignore WordPress.Security.EscapeOutput
	if ( isset( $_GET['err'] ) ) { echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['err'] ) ) . '</div>'; }

	md_support_render_new();
	?>
	<div class="mdsp-bar">
		<nav class="mdsp-filters" aria-label="상태별 보기">
			<a class="mdsp-chip<?php echo '' === $st ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_sup_url( array( 'app' => 'support', 'q' => $q ) ) ); ?>">전체 <b><?php echo (int) array_sum( $counts ); ?></b></a>
			<?php foreach ( $sts as $name => $info ) : ?>
				<a class="mdsp-chip mdsp-chip--<?php echo esc_attr( $info['class'] ); ?><?php echo $st === $name ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_sup_url( array( 'app' => 'support', 'st' => $name, 'q' => $q ) ) ); ?>"><?php echo esc_html( $name ); ?> <b><?php echo (int) $counts[ $name ]; ?></b></a>
			<?php endforeach; ?>
		</nav>
		<form method="get" class="mdsp-search" action="<?php echo esc_url( md_sup_url( array( 'app' => 'support' ) ) ); ?>">
			<?php md_sup_app_field(); ?>
			<?php if ( '' !== $st ) : ?><input type="hidden" name="st" value="<?php echo esc_attr( $st ); ?>"><?php endif; ?>
			<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="🔍 찾기" aria-label="찾기">
		</form>
	</div>

	<?php if ( empty( $rows ) ) : ?>
		<div class="mds-card"><div class="mds-empty"><?php echo '' !== $q ? '찾는 내용이 없습니다.' : '요청이 없습니다.'; ?></div></div>
	<?php else : ?>
		<h2 class="mdsp-group"><?php echo '' !== $q ? '찾은 요청' : ( '' === $st ? '전체 요청' : esc_html( $st ) ); ?><small>최근 것이 위</small></h2>
		<?php foreach ( $rows as $r ) { md_support_render_card( $r, $manage, $keep ); } ?>
	<?php endif; ?>
	<p class="mds-hint" style="margin-top:18px">2025년 10월 이전에 완료된 요청은 예전 시트 「문치과병원 경영지원실 요청사항 › 완료된 사항」에 있습니다.</p>
	<?php
}
