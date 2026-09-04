<?php
/**
 * 재고관리 — 계정 일괄 생성
 *
 * 팀 15개 + 재고 담당자까지 16개를 손으로 만들면 오래 걸리고 실수가 난다.
 * 여기서 한 번에 만들고, 초기 비밀번호를 그 자리에서 한 번만 보여준다.
 *
 * 비밀번호를 코드에 적어두지 않는다. wp_generate_password() 로 만들고
 * 화면에 한 번 띄운 뒤 어디에도 남기지 않는다 — 인쇄해서 나눠주고 끝.
 *
 * 관리 메뉴 · 사용자 → 재고 계정
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 팀 번호 → 로그인 아이디. 한글 아이디는 로그인 때 번거로워 번호를 쓴다. */
function md_sup_team_login( $index ) {
	return 'team' . str_pad( (string) $index, 2, '0', STR_PAD_LEFT );
}

define( 'MD_SUP_MANAGER_LOGIN', 'stock.manager' );

/** 계정 현황 — 어떤 것이 있고 없는지 */
function md_sup_account_rows() {
	$rows = array();

	$rows[] = array(
		'login' => MD_SUP_MANAGER_LOGIN,
		'name'  => '재고 담당자',
		'role'  => 'md_stock_manager',
		'team'  => 0,
		'user'  => get_user_by( 'login', MD_SUP_MANAGER_LOGIN ),
	);

	$i = 0;
	foreach ( md_sup_teams() as $tm ) {
		$i++;
		$login  = md_sup_team_login( $i );
		$rows[] = array(
			'login' => $login,
			'name'  => $tm->name,
			'role'  => 'md_stock_staff',
			'team'  => (int) $tm->id,
			'user'  => get_user_by( 'login', $login ),
		);
	}
	return $rows;
}

/** 없는 계정만 만든다. 이미 있는 것은 건드리지 않는다. */
function md_sup_create_accounts() {
	$made = array();

	foreach ( md_sup_account_rows() as $r ) {
		if ( $r['user'] ) { continue; }

		$pass = wp_generate_password( 10, false );
		$id   = wp_insert_user( array(
			'user_login'   => $r['login'],
			'user_pass'    => $pass,
			'display_name' => $r['name'],
			'nickname'     => $r['name'],
			'first_name'   => $r['name'],
			'role'         => $r['role'],
			'show_admin_bar_front' => false,
		) );

		if ( is_wp_error( $id ) ) { continue; }

		if ( $r['team'] ) { update_user_meta( $id, 'md_sup_team_id', $r['team'] ); }

		$made[] = array( 'login' => $r['login'], 'name' => $r['name'], 'pass' => $pass );
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
	if ( isset( $_POST['md_sup_make'] ) ) {
		check_admin_referer( 'md_sup_make_accounts' );
		$made = md_sup_create_accounts();
	}

	$rows    = md_sup_account_rows();
	$missing = 0;
	foreach ( $rows as $r ) { if ( ! $r['user'] ) { $missing++; } }
	?>
	<div class="wrap">
		<h1>재고 계정</h1>
		<p>
			재고관리 페이지(<a href="<?php echo esc_url( home_url( '/직원/' ) ); ?>" target="_blank" rel="noopener">/직원/</a>)에서 쓸 계정입니다.
			팀마다 하나씩, 그리고 재고 담당자 계정 하나를 만듭니다.
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
		<table class="widefat striped" style="max-width:760px">
			<thead><tr><th>소속</th><th>아이디</th><th>역할</th><th>상태</th></tr></thead>
			<tbody>
			<?php foreach ( $rows as $r ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $r['name'] ); ?></strong></td>
					<td><code><?php echo esc_html( $r['login'] ); ?></code></td>
					<td><?php echo 'md_stock_manager' === $r['role'] ? '재고 · 담당자 (전체 관리)' : '재고 · 직원 (신청 · 통계)'; ?></td>
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

		<?php if ( $missing ) : ?>
			<form method="post" style="margin-top:18px">
				<?php wp_nonce_field( 'md_sup_make_accounts' ); ?>
				<p>
					<button type="submit" name="md_sup_make" value="1" class="button button-primary button-hero">
						없는 계정 <?php echo (int) $missing; ?>개 만들기
					</button>
				</p>
				<p class="description">
					만든 뒤 초기 비밀번호가 한 번 표시됩니다. 직원분들께는 <strong>첫 로그인 후 비밀번호를 바꾸도록</strong> 안내해 주세요.
				</p>
			</form>
		<?php else : ?>
			<p style="margin-top:18px"><strong>필요한 계정이 모두 있습니다.</strong></p>
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
