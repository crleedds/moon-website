<?php
/**
 * 재고관리 — 계정
 *
 * 계정은 둘뿐이다. 재고 담당자 하나, 직원 공용 하나.
 * 직원분들은 공용 계정으로 들어와 신청 화면에서 자기 팀을 고른다.
 *
 * 비밀번호는 코드에 적지 않는다 — 이 저장소는 공개(public)라
 * 적어 두면 인터넷에 그대로 공개된다. 만들 때 관리자가 입력한다.
 *
 * 두 계정은 스스로 비밀번호를 바꿀 수 없다. 공용 계정이라
 * 한 사람이 바꾸면 나머지 전원이 못 들어오기 때문이다.
 *
 * 관리 메뉴 · 사용자 → 재고 계정
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_SUP_MANAGER_LOGIN', 'moonmanager' );
define( 'MD_SUP_STAFF_LOGIN', 'moondental' );

/**
 * 계정은 둘뿐이다.
 *   stock.manager — 재고 담당자. 승인·출고·입고·재고까지.
 *   moondental    — 직원 공용. 로그인해서 자기 팀을 고르고 신청한다.
 *
 * 공용 계정이라 「누가」는 남지 않고 「어느 팀」만 남는다.
 * 사용량 집계는 팀 단위라 문제없지만, 팀을 잘못 고르면 그 팀으로 잡히므로
 * 신청 화면에서 팀 선택을 크게 두고 마지막 선택을 기억하게 했다.
 */
function md_sup_account_rows() {
	return array(
		array(
			'login' => MD_SUP_MANAGER_LOGIN,
			'name'  => '재고 담당자',
			'role'  => 'md_stock_manager',
			'desc'  => '신청 승인 · 출고 · 입고 · 재고 실사까지 전부',
			'team'  => 0,
			'user'  => get_user_by( 'login', MD_SUP_MANAGER_LOGIN ),
		),
		array(
			'login' => MD_SUP_STAFF_LOGIN,
			'name'  => '직원 공용',
			'role'  => 'md_stock_staff',
			'desc'  => '로그인 후 자기 팀을 골라 신청 · 사용량과 비용 확인',
			'team'  => 0,
			'user'  => get_user_by( 'login', MD_SUP_STAFF_LOGIN ),
		),
	);
}

/**
 * 없는 계정만 만든다. 이미 있는 것은 건드리지 않는다.
 *
 * 비밀번호는 코드에 적지 않는다 — 이 저장소는 공개(public)라
 * 적어 두면 인터넷에 그대로 공개된다. 관리자가 화면에서 입력하거나,
 * 비워 두면 무작위로 만들어 한 번만 보여준다.
 *
 * @param string $pass 공통으로 쓸 비밀번호. 빈 값이면 계정마다 무작위.
 */
function md_sup_create_accounts( $pass = '' ) {
	$made = array();

	foreach ( md_sup_account_rows() as $r ) {
		if ( $r['user'] ) { continue; }

		/* $pass 를 덮어쓰지 않는다 — 그러면 빈 값으로 불렀을 때
		 * 첫 계정의 무작위 비밀번호가 두 번째 계정에도 그대로 쓰인다. */
		$use = ( '' !== $pass ) ? $pass : wp_generate_password( 10, false );
		$id  = wp_insert_user( array(
			'user_login'   => $r['login'],
			'user_pass'    => $use,
			'display_name' => $r['name'],
			'nickname'     => $r['name'],
			'first_name'   => $r['name'],
			'role'         => $r['role'],
			'show_admin_bar_front' => false,
		) );

		if ( is_wp_error( $id ) ) { continue; }

		if ( $r['team'] ) { update_user_meta( $id, 'md_sup_team_id', $r['team'] ); }

		$made[] = array( 'login' => $r['login'], 'name' => $r['name'], 'pass' => $use );
	}

	return $made;
}

/** 관리 메뉴 등록 */
function md_sup_accounts_menu() {
	add_users_page(
		'재고 계정',
		'재고 계정',
		'create_users',
		'md-sup-accounts',
		'md_sup_accounts_screen'
	);
}
add_action( 'admin_menu', 'md_sup_accounts_menu' );

/** 화면 */
function md_sup_accounts_screen() {
	if ( ! current_user_can( 'create_users' ) ) { wp_die( '권한이 없습니다.' ); }

	$made = array();
	$err  = '';
	if ( isset( $_POST['md_sup_make'] ) ) {
		check_admin_referer( 'md_sup_make_accounts' );
		$pass = isset( $_POST['md_sup_pass'] ) ? (string) wp_unslash( $_POST['md_sup_pass'] ) : '';
		$pass = trim( $pass );
		if ( '' !== $pass && strlen( $pass ) < 8 ) {
			$err = '비밀번호는 8자 이상으로 정해 주세요.';
		} else {
			$made = md_sup_create_accounts( $pass );
		}
	}

	$rows    = md_sup_account_rows();
	$missing = 0;
	foreach ( $rows as $r ) { if ( ! $r['user'] ) { $missing++; } }
	?>
	<div class="wrap">
		<h1>재고 계정</h1>
		<p>
			재고관리 페이지(<a href="<?php echo esc_url( home_url( '/직원/' ) ); ?>" target="_blank" rel="noopener">/직원/</a>)에서 쓸 계정입니다.
			<strong>재고 담당자 하나, 직원 공용 하나</strong> 총 두 개입니다.
			직원분들은 공용 계정으로 로그인해 신청 화면에서 자기 팀을 고릅니다.
		</p>

		<?php if ( $made ) : ?>
			<div class="notice notice-success">
				<p><strong><?php echo count( $made ); ?>개 계정을 만들었습니다. 아래 비밀번호는 지금 이 화면에서만 볼 수 있습니다 — 인쇄하거나 옮겨 적어 두세요.</strong></p>
			</div>
			<table class="widefat striped" style="max-width:640px">
				<thead><tr><th>소속</th><th>아이디</th><th>초기 비밀번호</th></tr></thead>
				<tbody>
				<?php foreach ( $made as $m ) : ?>
					<tr>
						<td><?php echo esc_html( $m['name'] ); ?></td>
						<td><code><?php echo esc_html( $m['login'] ); ?></code></td>
						<td><code style="font-size:15px"><?php echo esc_html( $m['pass'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				비밀번호는 어디에도 저장되지 않습니다. 이 화면을 벗어나면 다시 볼 수 없고,
				잊으신 계정은 사용자 목록에서 새 비밀번호를 발급하시면 됩니다.
			</p>
			<hr>
		<?php endif; ?>

		<h2>현황</h2>
		<table class="widefat striped" style="max-width:860px">
			<thead><tr><th>계정</th><th>아이디</th><th>할 수 있는 일</th><th>상태</th></tr></thead>
			<tbody>
			<?php foreach ( $rows as $r ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $r['name'] ); ?></strong></td>
					<td><code><?php echo esc_html( $r['login'] ); ?></code></td>
					<td><?php echo esc_html( $r['desc'] ); ?></td>
					<td>
						<?php if ( $r['user'] ) : ?>
							<span style="color:#2271b1">있음</span>
							&nbsp;<a href="<?php echo esc_url( get_edit_user_link( $r['user']->ID ) ); ?>">편집</a>
						<?php else : ?>
							<span style="color:#b32d2e">없음</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $err ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $err ); ?></p></div>
		<?php endif; ?>

		<?php if ( $missing ) : ?>
			<form method="post" style="margin-top:18px">
				<?php wp_nonce_field( 'md_sup_make_accounts' ); ?>
				<table class="form-table" role="presentation" style="max-width:640px">
					<tr>
						<th><label for="md_sup_pass">두 계정에 쓸 비밀번호</label></th>
						<td>
							<input type="text" id="md_sup_pass" name="md_sup_pass" class="regular-text"
							       autocomplete="off" placeholder="비워 두면 계정마다 무작위로 만듭니다">
							<p class="description">
								<strong>여기에 적은 비밀번호는 저장소(코드)에 남지 않습니다.</strong>
								이 홈페이지 소스는 공개 저장소라, 코드에 비밀번호를 적어 두면 인터넷에 그대로 공개됩니다.
								그래서 만들 때 직접 입력하도록 했습니다. 8자 이상.
							</p>
						</td>
					</tr>
				</table>
				<p>
					<button type="submit" name="md_sup_make" value="1" class="button button-primary button-hero">
						없는 계정 <?php echo (int) $missing; ?>개 만들기
					</button>
				</p>
			</form>
		<?php else : ?>
			<p style="margin-top:18px"><strong>필요한 계정이 모두 있습니다.</strong></p>
			<p class="description">
				비밀번호를 바꾸시려면 위 표의 「편집」을 누르고 새 비밀번호를 지정하세요.
				<strong>이 두 계정은 스스로 비밀번호를 바꿀 수 없습니다</strong> — 관리자만 바꿉니다.
			</p>
		<?php endif; ?>

		<hr>
		<h2>역할이 하는 일</h2>
		<table class="widefat striped" style="max-width:760px">
			<thead><tr><th></th><th>재고 · 직원</th><th>재고 · 담당자</th></tr></thead>
			<tbody>
				<tr><td>재료 신청</td><td>가능</td><td>가능</td></tr>
				<tr><td>사용량 · 비용 확인</td><td>가능 (전체 팀 공개)</td><td>가능</td></tr>
				<tr><td>신청 승인 · 출고</td><td>—</td><td>가능</td></tr>
				<tr><td>입고 등록</td><td>—</td><td>가능</td></tr>
				<tr><td>재고 · 실사</td><td>—</td><td>가능</td></tr>
				<tr><td>홈페이지 글 작성</td><td>—</td><td>—</td></tr>
			</tbody>
		</table>
	</div>
	<?php
}

/* ============================================================
 * 잠금 — 이 두 계정은 스스로 비밀번호를 못 바꾼다
 *
 * 공용 계정이라 한 사람이 바꿔 버리면 나머지 전원이 못 들어온다.
 * 그래서 변경 권한을 워드프레스 관리자에게만 둔다.
 * ============================================================ */

/** 재고 전용 계정인가 (관리자는 제외) */
function md_sup_is_stock_account( $user = null ) {
	if ( null === $user ) { $user = wp_get_current_user(); }
	if ( ! $user || ! $user->exists() ) { return false; }
	if ( user_can( $user, 'manage_options' ) ) { return false; }
	return in_array( $user->user_login, array( MD_SUP_MANAGER_LOGIN, MD_SUP_STAFF_LOGIN ), true );
}

/** 프로필 화면의 비밀번호 칸을 숨긴다 */
function md_sup_hide_password_fields( $show, $profile_user = null ) {
	if ( current_user_can( 'edit_users' ) ) { return $show; }
	return md_sup_is_stock_account( $profile_user ) ? false : $show;
}
add_filter( 'show_password_fields', 'md_sup_hide_password_fields', 10, 2 );

/** 「비밀번호 찾기」로 재설정하는 길도 막는다 */
function md_sup_block_password_reset( $allow, $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) { return $allow; }
	return in_array( $user->user_login, array( MD_SUP_MANAGER_LOGIN, MD_SUP_STAFF_LOGIN ), true ) ? false : $allow;
}
add_filter( 'allow_password_reset', 'md_sup_block_password_reset', 10, 2 );

/** 저장 단계에서 한 번 더 — 폼을 우회해 POST 해도 안 바뀌게 */
function md_sup_block_password_save( &$errors, $update, $user ) {
	if ( ! $update || current_user_can( 'edit_users' ) ) { return; }
	if ( ! isset( $user->ID ) ) { return; }
	$target = get_userdata( $user->ID );
	if ( ! $target ) { return; }
	if ( ! in_array( $target->user_login, array( MD_SUP_MANAGER_LOGIN, MD_SUP_STAFF_LOGIN ), true ) ) { return; }
	if ( ! empty( $_POST['pass1'] ) ) {
		$errors->add( 'md_sup_pass_locked', '이 계정의 비밀번호는 병원 관리자만 변경할 수 있습니다.' );
	}
}
add_action( 'user_profile_update_errors', 'md_sup_block_password_save', 10, 3 );

/**
 * 재고 계정은 관리자 화면에 들어갈 일이 없다. 재고 페이지로 보낸다.
 * admin-ajax.php 는 막지 않는다 — 정상 동작에 필요할 수 있다.
 */
function md_sup_redirect_admin() {
	if ( wp_doing_ajax() || ! is_admin() ) { return; }
	if ( ! md_sup_is_stock_account() ) { return; }
	wp_safe_redirect( home_url( '/직원/' ) );
	exit;
}
add_action( 'admin_init', 'md_sup_redirect_admin', 1 );

/** 상단 관리자 바도 감춘다 */
function md_sup_hide_admin_bar( $show ) {
	return md_sup_is_stock_account() ? false : $show;
}
add_filter( 'show_admin_bar', 'md_sup_hide_admin_bar' );

/** 로그인하면 재고 페이지로 보낸다 */
function md_sup_login_redirect( $redirect_to, $requested, $user ) {
	if ( is_wp_error( $user ) || ! isset( $user->user_login ) ) { return $redirect_to; }
	if ( in_array( $user->user_login, array( MD_SUP_MANAGER_LOGIN, MD_SUP_STAFF_LOGIN ), true ) ) {
		return home_url( '/직원/' );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'md_sup_login_redirect', 10, 3 );
