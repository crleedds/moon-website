<?php
/**
 * v5.9 · 로그인 기록 · 잠금 (원장 지시 2026-10-04)
 *
 *  모든 로그인(라운지 · wp-login)의 성공 · 실패를 wp_md_login_log 에 남긴다 (180일 보관).
 *  같은 아이디를 같은 곳(IP)에서 15분 안에 5번 틀리면 그 아이디 · 그 곳을 15분 잠근다.
 *  한 곳에서 여러 아이디로 15분 안에 20번 틀리면 그 곳 전체를 15분 잠근다.
 *  (아이디만으로 잠그면 남이 일부러 틀려 직원을 못 들어오게 할 수 있어 IP 와 함께 본다)
 *  라운지 관리자는 직원 정보에서 기록을 보고 잠금을 풀 수 있다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_ACC_LOG_SCHEMA', 1 );
define( 'MD_ACC_LOCK_FAILS', 5 );
define( 'MD_ACC_LOCK_IP_FAILS', 20 );
define( 'MD_ACC_LOCK_MIN', 15 );

function md_acc_log_table() { global $wpdb; return $wpdb->prefix . 'md_login_log'; }

function md_acc_log_install() {
	if ( (int) get_option( 'md_acc_log_schema', 0 ) >= MD_ACC_LOG_SCHEMA ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = md_acc_log_table();
	dbDelta( "CREATE TABLE $t (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		login VARCHAR(100) NOT NULL DEFAULT '',
		ok TINYINT(1) NOT NULL DEFAULT 0,
		reason VARCHAR(40) NOT NULL DEFAULT '',
		ip VARCHAR(45) NOT NULL DEFAULT '',
		device VARCHAR(60) NOT NULL DEFAULT '',
		via VARCHAR(10) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY login_time (login, created_at),
		KEY ip_time (ip, created_at),
		KEY user_time (user_id, created_at)
	) " . $wpdb->get_charset_collate() . ';' );
	update_option( 'md_acc_log_schema', MD_ACC_LOG_SCHEMA );
}
add_action( 'init', 'md_acc_log_install', 7 );

function md_acc_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/** 기기 이름 — 「휴대폰 · 크롬」처럼 사람이 알아보는 말로 */
function md_acc_device() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	$kind = preg_match( '/iPad|Tablet/i', $ua ) ? '태블릿' : ( preg_match( '/Mobile|Android|iPhone/i', $ua ) ? '휴대폰' : 'PC' );
	$os = preg_match( '/iPhone|iPad|Mac OS X/', $ua ) && preg_match( '/Mobile|iPad/', $ua ) ? 'iOS' : ( false !== stripos( $ua, 'Android' ) ? '안드로이드' : ( false !== stripos( $ua, 'Windows' ) ? '윈도우' : ( false !== stripos( $ua, 'Mac OS X' ) ? '맥' : '' ) ) );
	$br = false !== stripos( $ua, 'KAKAOTALK' ) ? '카카오톡' : ( false !== stripos( $ua, 'SamsungBrowser' ) ? '삼성 인터넷' : ( false !== stripos( $ua, 'NAVER' ) ? '네이버' : ( false !== stripos( $ua, 'Edg/' ) ? '엣지' : ( false !== stripos( $ua, 'Chrome' ) || false !== stripos( $ua, 'CriOS' ) ? '크롬' : ( false !== stripos( $ua, 'Safari' ) ? '사파리' : ( false !== stripos( $ua, 'Firefox' ) ? '파이어폭스' : '' ) ) ) ) ) );
	return mb_substr( implode( ' · ', array_filter( array( $kind, $os, $br ) ) ), 0, 60 );
}

function md_acc_log_write( $user_id, $login, $ok, $reason = '' ) {
	global $wpdb;
	if ( (int) get_option( 'md_acc_log_schema', 0 ) < 1 ) { return; }
	$wpdb->insert( md_acc_log_table(), array(
		'user_id'    => (int) $user_id,
		'login'      => mb_substr( strtolower( trim( (string) $login ) ), 0, 100 ),
		'ok'         => $ok ? 1 : 0,
		'reason'     => mb_substr( (string) $reason, 0, 40 ),
		'ip'         => md_acc_ip(),
		'device'     => md_acc_device(),
		'via'        => ! empty( $_POST['md_lounge'] ) ? 'lounge' : 'wp',
		'created_at' => current_time( 'mysql' ),
	) );
	/* 오래된 기록 정리 — 하루 한 번 */
	if ( ! get_transient( 'md_acc_log_pruned' ) ) {
		set_transient( 'md_acc_log_pruned', 1, DAY_IN_SECONDS );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . md_acc_log_table() . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 180 * DAY_IN_SECONDS ) ) );
	}
}

/** 아이디 입력값(아이디 또는 이메일) → 실제 아이디 */
function md_acc_login_key( $username ) {
	$username = trim( (string) $username );
	if ( is_email( $username ) ) {
		$u = get_user_by( 'email', $username );
		if ( $u ) { return strtolower( $u->user_login ); }
	}
	return strtolower( $username );
}

/** 잠겨 있으면 풀리는 시각(문자열), 아니면 '' */
function md_acc_locked_until( $login, $ip = null ) {
	global $wpdb;
	if ( (int) get_option( 'md_acc_log_schema', 0 ) < 1 ) { return ''; }
	$ip    = null === $ip ? md_acc_ip() : $ip;
	$t     = md_acc_log_table();
	$since = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - MD_ACC_LOCK_MIN * MINUTE_IN_SECONDS );
	$fail  = "ok = 0 AND reason NOT IN ('locked', 'unlock', 'pending', 'off') AND created_at >= %s"; /* 승인 대기 · 사용 중지는 비밀번호가 맞은 것이라 세지 않는다 */
	/* 관리자가 잠금을 풀면 그 뒤 실패만 센다 */
	$reset = (string) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(created_at) FROM $t WHERE reason = 'unlock' AND login = %s", $login ) );
	if ( $reset > $since ) { $since = $reset; }
	/* v7.1 · 원장 지시 — 그 계정이 어디서든 5번 틀리면 15분 잠금. 다른 장치(IP 단위 잠금 등)는 두지 않는다
	   (병원 직원은 같은 인터넷을 쓰므로 IP 잠금은 병원 전체를 막을 수 있었다) */
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS n, MAX(created_at) AS last FROM $t WHERE $fail AND login = %s", $since, $login ) );
	if ( $row && (int) $row->n >= MD_ACC_LOCK_FAILS ) { return gmdate( 'Y-m-d H:i:s', strtotime( $row->last ) + MD_ACC_LOCK_MIN * MINUTE_IN_SECONDS ); }
	return '';
}

/** 로그인 시도 — 잠겨 있으면 비밀번호가 맞아도 막는다 */
function md_acc_lock_check( $user, $username = '', $password = '' ) {
	if ( '' === trim( (string) $username ) ) { return $user; }
	$until = md_acc_locked_until( md_acc_login_key( $username ) );
	if ( '' !== $until ) {
		$min = max( 1, (int) ceil( ( strtotime( $until ) - current_time( 'timestamp' ) ) / 60 ) );
		return new WP_Error( 'md_acc_locked', '비밀번호를 여러 번 틀려 잠시 잠겼습니다. ' . $min . '분 뒤에 다시 해 주세요. 급하면 경영지원실에 잠금 풀기를 부탁해 주세요.' );
	}
	return $user;
}
add_filter( 'authenticate', 'md_acc_lock_check', 100, 3 );

function md_acc_log_ok( $user_login, $user ) {
	md_acc_log_write( $user->ID, $user->user_login, true );
}
add_action( 'wp_login', 'md_acc_log_ok', 5, 2 );

function md_acc_log_fail( $username, $error = null ) {
	$code = ( $error instanceof WP_Error ) ? $error->get_error_code() : '';
	$map  = array( 'md_acc_locked' => 'locked', 'md_acc_pending' => 'pending', 'md_acc_off' => 'off', 'incorrect_password' => 'password', 'invalid_username' => 'no_user', 'invalid_email' => 'no_user' );
	$key  = md_acc_login_key( $username );
	$u    = get_user_by( 'login', $key );
	md_acc_log_write( $u ? $u->ID : 0, $key, false, isset( $map[ $code ] ) ? $map[ $code ] : ( $code ? substr( $code, 0, 40 ) : 'fail' ) );
}
add_action( 'wp_login_failed', 'md_acc_log_fail', 5, 2 );

function md_acc_reason_label( $r ) {
	$m = array( '' => '로그인', 'password' => '비밀번호 틀림', 'no_user' => '없는 아이디', 'locked' => '잠김 상태에서 시도', 'pending' => '승인 대기 계정', 'off' => '사용 중지 계정', 'unlock' => '잠금 풀기' );
	return isset( $m[ $r ] ) ? $m[ $r ] : $r;
}

function md_acc_log_rows( $args = array() ) {
	global $wpdb;
	if ( (int) get_option( 'md_acc_log_schema', 0 ) < 1 ) { return array(); }
	$a = wp_parse_args( $args, array( 'user_id' => 0, 'limit' => 50, 'fails' => false ) );
	$w = array( '1=1' ); $v = array();
	if ( $a['user_id'] ) { $w[] = 'user_id = %d'; $v[] = (int) $a['user_id']; }
	if ( $a['fails'] ) { $w[] = "ok = 0 AND reason <> 'unlock'"; }
	$v[] = max( 1, min( 500, (int) $a['limit'] ) );
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . md_acc_log_table() . ' WHERE ' . implode( ' AND ', $w ) . ' ORDER BY id DESC LIMIT %d', $v ) );
}

/** 지금 잠긴 아이디 · 곳 */
function md_acc_current_locks() {
	global $wpdb;
	if ( (int) get_option( 'md_acc_log_schema', 0 ) < 1 ) { return array(); }
	$since = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - MD_ACC_LOCK_MIN * MINUTE_IN_SECONDS );
	$out   = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( 'SELECT login, ip FROM ' . md_acc_log_table() . " WHERE ok = 0 AND reason NOT IN ('locked','unlock','pending','off') AND created_at >= %s GROUP BY login, ip", $since ) ) as $r ) {
		$until = md_acc_locked_until( $r->login, $r->ip );
		if ( '' !== $until ) { $out[ $r->login . '|' . $r->ip ] = (object) array( 'login' => $r->login, 'ip' => $r->ip, 'until' => $until ); }
	}
	return array_values( $out );
}

function md_acc_unlock( $login, $ip ) {
	global $wpdb;
	$wpdb->insert( md_acc_log_table(), array( 'user_id' => get_current_user_id(), 'login' => mb_substr( (string) $login, 0, 100 ), 'ok' => 0, 'reason' => 'unlock', 'ip' => mb_substr( (string) $ip, 0, 45 ), 'device' => md_acc_me_name(), 'via' => 'admin', 'created_at' => current_time( 'mysql' ) ) );
	md_acc_log( '로그인 잠금 풀기', $login . ' · ' . $ip );
}

function md_acc_last_login( $user_id ) {
	global $wpdb;
	if ( (int) get_option( 'md_acc_log_schema', 0 ) < 1 ) { return ''; }
	return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(created_at) FROM ' . md_acc_log_table() . ' WHERE user_id = %d AND ok = 1', (int) $user_id ) );
}

function md_acc_when( $dt ) {
	if ( ! $dt ) { return '—'; }
	$ts = strtotime( $dt ); $now = current_time( 'timestamp' );
	if ( $now - $ts < 60 ) { return '방금'; }
	if ( $now - $ts < 3600 ) { return (int) floor( ( $now - $ts ) / 60 ) . '분 전'; }
	if ( gmdate( 'Y-m-d', $ts ) === gmdate( 'Y-m-d', $now ) ) { return '오늘 ' . gmdate( 'H:i', $ts ); }
	if ( gmdate( 'Y-m-d', $ts ) === gmdate( 'Y-m-d', $now - DAY_IN_SECONDS ) ) { return '어제 ' . gmdate( 'H:i', $ts ); }
	return gmdate( 'n/j H:i', $ts );
}

/** 기록 표 (관리자 · 내 정보 공용) */
function md_acc_render_log_table( $rows, $show_who = true ) {
	if ( ! $rows ) { echo '<p class="mds-hint" style="margin:0">기록이 없습니다.</p>'; return; }
	$names = array();
	echo '<div class="mda-logwrap"><table class="mda-log"><thead><tr><th>언제</th>' . ( $show_who ? '<th>아이디</th>' : '' ) . '<th>결과</th><th>기기</th><th>접속 위치(IP)</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$who = $r->login;
		if ( $show_who && $r->user_id && 'unlock' !== $r->reason ) {
			if ( ! isset( $names[ $r->user_id ] ) ) { $u = get_userdata( $r->user_id ); $names[ $r->user_id ] = $u ? $u->display_name : ''; }
			if ( $names[ $r->user_id ] && $names[ $r->user_id ] !== $r->login ) { $who .= ' (' . $names[ $r->user_id ] . ')'; }
		}
		$cls = (int) $r->ok ? 'is-ok' : ( 'unlock' === $r->reason ? 'is-unlock' : 'is-fail' );
		echo '<tr class="' . esc_attr( $cls ) . '"><td data-l="언제">' . esc_html( md_acc_when( $r->created_at ) ) . '</td>'
			. ( $show_who ? '<td data-l="아이디"><code>' . esc_html( $who ) . '</code></td>' : '' )
			. '<td data-l="결과">' . ( (int) $r->ok ? '✓ 로그인' : esc_html( ( 'unlock' === $r->reason ? '🔓 ' : '✕ ' ) . md_acc_reason_label( $r->reason ) ) ) . ( 'lounge' === $r->via ? '' : ( 'wp' === $r->via ? ' <small>관리 화면</small>' : '' ) ) . '</td>'
			. '<td data-l="기기">' . esc_html( 'unlock' === $r->reason ? '처리: ' . $r->device : $r->device ) . '</td>'
			. '<td data-l="IP"><small>' . esc_html( $r->ip ) . '</small></td></tr>';
	}
	echo '</tbody></table></div>';
}

/** 직원 정보 — 잠금 · 최근 기록 */
function md_acc_render_log_panel() {
	if ( ! md_acc_can_manage() ) { return; }
	$locks = md_acc_current_locks();
	?>
	<section class="mds-card mda-logpanel" id="mda-log">
		<h2 class="mdst-title">로그인 기록 <?php echo $locks ? '<span class="mda-badge">잠김 ' . count( $locks ) . '</span>' : ''; ?></h2>
		<?php foreach ( $locks as $l ) : ?>
			<form method="post" class="mda-lock">
				<?php md_acc_hidden( 'unlock' ); ?><input type="hidden" name="lk_login" value="<?php echo esc_attr( $l->login ); ?>"><input type="hidden" name="lk_ip" value="<?php echo esc_attr( $l->ip ); ?>">
				<span>🔒 <code><?php echo esc_html( $l->login ); ?></code> · <?php echo esc_html( $l->ip ); ?> — <?php echo esc_html( gmdate( 'H:i', strtotime( $l->until ) ) ); ?>까지 잠김</span>
				<button type="submit" class="mds-btn">잠금 풀기</button>
			</form>
		<?php endforeach; ?>
		<p class="mds-hint">같은 아이디를 같은 곳에서 <?php echo (int) MD_ACC_LOCK_FAILS; ?>번 틀리면 <?php echo (int) MD_ACC_LOCK_MIN; ?>분 동안 잠깁니다. 기록은 180일 보관합니다.</p>
		<details class="mda-logdet"<?php echo $locks ? ' open' : ''; ?>><summary>최근 기록 보기</summary>
			<?php md_acc_render_log_table( md_acc_log_rows( array( 'limit' => 60 ) ) ); ?>
		</details>
	</section>
	<?php
}
