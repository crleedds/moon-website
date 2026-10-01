<?php
/**
 * v4.18 · 직원 라운지 · 직원 정보 (관리자)
 *
 *  이름 · 부서 · 직책 · 생일 · 입사일을 관리자가 넣고 고치고 뺀다. 달력은 이 표에서
 *  생일(🎂)과 입사 기념일(🎉 N주년)을 자동으로 읽는다 — 따로 일정을 넣을 필요가 없다.
 *  처음 한 번은 홈페이지 의료진 페이지의 명단(원장 + 직원)을 그대로 가져와 채운다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_STAFF_SCHEMA', 1 );

function md_staff_table() { global $wpdb; return $wpdb->prefix . 'md_staff'; }

function md_staff_can_manage() { return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage(); }

/* ============================================================
 * 테이블 · 첫 명단 가져오기
 * ============================================================ */
function md_staff_maybe_install() {
	if ( (int) get_option( 'md_staff_schema', 0 ) >= MD_STAFF_SCHEMA ) { return; }
	if ( ! add_option( 'md_staff_installing', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'md_staff_installing' ) < 300 ) { return; }
		delete_option( 'md_staff_installing' );
		if ( ! add_option( 'md_staff_installing', time(), '', 'no' ) ) { return; }
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = md_staff_table();
	dbDelta( "CREATE TABLE $t (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		name VARCHAR(60) NOT NULL DEFAULT '',
		dept VARCHAR(60) NOT NULL DEFAULT '',
		position VARCHAR(60) NOT NULL DEFAULT '',
		birthday DATE NULL,
		hired DATE NULL,
		active TINYINT(1) NOT NULL DEFAULT 1,
		sort INT NOT NULL DEFAULT 0,
		note VARCHAR(200) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL,
		updated_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY active (active),
		KEY birthday (birthday),
		KEY hired (hired)
	) " . $wpdb->get_charset_collate() . ';' );
	if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ) ) { md_staff_import_from_site(); }
	update_option( 'md_staff_schema', MD_STAFF_SCHEMA );
	delete_option( 'md_staff_installing' );
}
add_action( 'init', 'md_staff_maybe_install', 21 );

/** 홈페이지 명단(원장 + 직원)에서 아직 없는 사람만 추가. 돌려주는 값: 추가된 수 */
function md_staff_import_from_site() {
	global $wpdb;
	$t     = md_staff_table();
	$have  = array();
	foreach ( (array) $wpdb->get_results( "SELECT name, dept FROM $t" ) as $r ) { $have[ $r->name . '|' . $r->dept ] = 1; }
	$added = 0; $sort = (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort),0) FROM $t" );

	/* 원장 — 의료진 데이터 */
	$docs = function_exists( 'moondental_get_team_with_customizer' ) ? moondental_get_team_with_customizer() : ( function_exists( 'moondental_get_team' ) ? moondental_get_team() : array() );
	foreach ( (array) $docs as $d ) {
		$name = trim( (string) ( $d['name'] ?? '' ) );
		if ( '' === $name || isset( $have[ $name . '|의료진' ] ) ) { continue; }
		$wpdb->insert( $t, array( 'name' => $name, 'dept' => '의료진', 'position' => mb_substr( trim( (string) ( $d['role'] ?? '원장' ) ), 0, 60 ), 'sort' => ++$sort, 'created_at' => current_time( 'mysql' ) ) );
		$have[ $name . '|의료진' ] = 1; $added++;
	}
	/* 직원 — 의료진 페이지의 직원 명단 (부서|직책|이름) */
	$raw = (string) get_theme_mod( 'md_content_staff_list', '' );
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || '#' === $line[0] ) { continue; }
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( count( $p ) < 3 || '' === $p[2] || false !== strpos( $p[2], 'OOO' ) ) { continue; }
		$dept = '경영지원본부' === $p[0] ? '경영지원실' : $p[0];
		if ( isset( $have[ $p[2] . '|' . $dept ] ) ) { continue; }
		$wpdb->insert( $t, array( 'name' => mb_substr( $p[2], 0, 60 ), 'dept' => mb_substr( $dept, 0, 60 ), 'position' => mb_substr( $p[1], 0, 60 ), 'sort' => ++$sort, 'created_at' => current_time( 'mysql' ) ) );
		$have[ $p[2] . '|' . $dept ] = 1; $added++;
	}
	return $added;
}

/* ============================================================
 * 데이터
 * ============================================================ */
function md_staff_all( $active_only = false ) {
	global $wpdb;
	$t = md_staff_table();
	return (array) $wpdb->get_results( 'SELECT * FROM ' . $t . ( $active_only ? ' WHERE active = 1' : '' ) . ' ORDER BY active DESC, sort, id' );
}

function md_staff_depts() {
	$d = array( '의료진', '진료실', '기공실', '서비스지원실', '경영지원실', '관리사무소', '예방과', '기타' );
	foreach ( md_staff_all() as $r ) { if ( '' !== $r->dept && ! in_array( $r->dept, $d, true ) ) { $d[] = $r->dept; } }
	return $d;
}

function md_staff_norm_date( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) return null;
	if ( preg_match( '/^(\d{4})[.\-\/](\d{1,2})[.\-\/](\d{1,2})$/', $v, $m ) ) { $v = sprintf( '%04d-%02d-%02d', $m[1], $m[2], $m[3] ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) return null;
	$p = explode( '-', $v );
	return checkdate( (int) $p[1], (int) $p[2], (int) $p[0] ) ? $v : null;
}

function md_staff_save( $data, $id = 0 ) {
	global $wpdb;
	$name = mb_substr( trim( sanitize_text_field( $data['name'] ?? '' ) ), 0, 60 );
	if ( '' === $name ) { return new WP_Error( 'md_staff', '이름을 적어 주세요.' ); }
	$row = array(
		'name'       => $name,
		'dept'       => mb_substr( trim( sanitize_text_field( $data['dept'] ?? '' ) ), 0, 60 ),
		'position'   => mb_substr( trim( sanitize_text_field( $data['position'] ?? '' ) ), 0, 60 ),
		'birthday'   => md_staff_norm_date( $data['birthday'] ?? '' ),
		'hired'      => md_staff_norm_date( $data['hired'] ?? '' ),
		'active'     => empty( $data['inactive'] ) ? 1 : 0,
		'note'       => mb_substr( trim( sanitize_text_field( $data['note'] ?? '' ) ), 0, 200 ),
		'updated_at' => current_time( 'mysql' ),
	);
	if ( $id ) { $wpdb->update( md_staff_table(), $row, array( 'id' => (int) $id ) ); return (int) $id; }
	$row['sort']       = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(sort),0) FROM ' . md_staff_table() ) + 1;
	$row['created_at'] = current_time( 'mysql' );
	$ok = $wpdb->insert( md_staff_table(), $row );
	return $ok ? (int) $wpdb->insert_id : new WP_Error( 'md_staff', '저장하지 못했습니다.' );
}

function md_staff_delete( $id ) { global $wpdb; return (bool) $wpdb->delete( md_staff_table(), array( 'id' => (int) $id ) ); }

/* ============================================================
 * 폼 처리 (관리자만 · PRG)
 * ============================================================ */
function md_staff_handle_post() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['md_staff_action'] ) ) { return; }
	if ( ! md_staff_can_manage() ) { return; }
	$action = sanitize_key( wp_unslash( $_POST['md_staff_action'] ) );
	if ( ! isset( $_POST['md_staff_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_staff_nonce'] ), 'md_staff_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}
	$back = md_sup_url( array( 'app' => 'staff' ) );
	$id   = isset( $_POST['sid'] ) ? (int) $_POST['sid'] : 0;
	$data = array();
	foreach ( array( 'name', 'dept', 'position', 'birthday', 'hired', 'note' ) as $k ) { $data[ $k ] = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : ''; }
	$data['inactive'] = ! empty( $_POST['inactive'] );
	if ( '' === $data['dept'] && ! empty( $_POST['dept_new'] ) ) { $data['dept'] = sanitize_text_field( wp_unslash( $_POST['dept_new'] ) ); }
	switch ( $action ) {
		case 'add':
			$res = md_staff_save( $data );
			$back = is_wp_error( $res ) ? add_query_arg( 'err', $res->get_error_message(), $back ) : $back . '#s' . (int) $res;
			break;
		case 'edit':
			$res = md_staff_save( $data, $id );
			$back = is_wp_error( $res ) ? add_query_arg( 'err', $res->get_error_message(), $back ) : $back . '#s' . $id;
			break;
		case 'delete':
			md_staff_delete( $id );
			break;
		case 'import':
			$n = md_staff_import_from_site();
			$back = add_query_arg( 'imported', (int) $n, $back );
			break;
	}
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_staff_handle_post', 1 );

/* ============================================================
 * 화면 (관리자)
 * ============================================================ */
function md_staff_render_row_form( $r, $depts ) {
	$edit = $r && ! empty( $r->id );
	$act  = $edit ? 'edit' : 'add';
	?>
	<form method="post" class="mdst-row<?php echo $edit ? '' : ' mdst-row--new'; ?><?php echo ( $edit && ! (int) $r->active ) ? ' is-inactive' : ''; ?>" id="<?php echo $edit ? 's' . (int) $r->id : 'new'; ?>">
		<input type="hidden" name="md_staff_action" value="<?php echo esc_attr( $act ); ?>">
		<input type="hidden" name="md_staff_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_staff_' . $act ) ); ?>">
		<?php if ( $edit ) : ?><input type="hidden" name="sid" value="<?php echo (int) $r->id; ?>"><?php endif; ?>
		<label class="mdst-f mdst-f--name"><span>이름</span><input type="text" name="name" required maxlength="60" value="<?php echo esc_attr( $r->name ?? '' ); ?>" placeholder="이름"></label>
		<label class="mdst-f mdst-f--dept"><span>부서</span>
			<select name="dept">
				<option value="">선택</option>
				<?php foreach ( $depts as $d ) : ?><option value="<?php echo esc_attr( $d ); ?>" <?php selected( $d, $r->dept ?? '' ); ?>><?php echo esc_html( $d ); ?></option><?php endforeach; ?>
			</select>
		</label>
		<label class="mdst-f mdst-f--pos"><span>직책</span><input type="text" name="position" maxlength="60" value="<?php echo esc_attr( $r->position ?? '' ); ?>" placeholder="예) 실장"></label>
		<label class="mdst-f mdst-f--date"><span>🎂 생일</span><input type="date" name="birthday" value="<?php echo esc_attr( $r->birthday ?? '' ); ?>"></label>
		<label class="mdst-f mdst-f--date"><span>🎉 입사일</span><input type="date" name="hired" value="<?php echo esc_attr( $r->hired ?? '' ); ?>"></label>
		<label class="mdst-f mdst-f--note"><span>메모</span><input type="text" name="note" maxlength="200" value="<?php echo esc_attr( $r->note ?? '' ); ?>" placeholder="선택"></label>
		<?php if ( $edit ) : ?>
			<label class="mdst-f mdst-f--chk mds-check"><input type="checkbox" name="inactive" value="1" <?php checked( ! (int) $r->active ); ?>> 퇴사 · 숨김</label>
		<?php else : ?>
			<input type="hidden" name="dept_new" value="">
		<?php endif; ?>
		<button type="submit" class="mds-btn mds-btn--fill mdst-save"><?php echo $edit ? '저장' : '추가'; ?></button>
	</form>
	<?php if ( $edit ) : ?>
	<form method="post" class="mdst-del" onsubmit="return confirm('<?php echo esc_js( $r->name ); ?> 님을 명단에서 지울까요? 달력의 생일·기념일도 같이 사라집니다. 퇴사자는 지우는 대신 「퇴사 · 숨김」을 권합니다.');">
		<input type="hidden" name="md_staff_action" value="delete"><input type="hidden" name="md_staff_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_staff_delete' ) ); ?>"><input type="hidden" name="sid" value="<?php echo (int) $r->id; ?>">
		<button type="submit" class="mdst-delbtn" title="명단에서 삭제">🗑</button>
	</form>
	<?php endif;
}

function md_staff_render() {
	if ( ! md_staff_can_manage() ) { echo '<div class="mds-card"><div class="mds-empty">관리자만 볼 수 있습니다.</div></div>'; return; }
	$rows  = md_staff_all();
	$depts = md_staff_depts();
	if ( isset( $_GET['err'] ) ) { echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['err'] ) ) . '</div>'; }
	if ( isset( $_GET['imported'] ) ) { echo '<div class="mds-notice mds-notice--ok">홈페이지 명단에서 ' . (int) $_GET['imported'] . '명을 새로 가져왔습니다.</div>'; }
	$n_b = 0; $n_h = 0; $n_a = 0;
	foreach ( $rows as $r ) { if ( (int) $r->active ) { $n_a++; if ( $r->birthday ) $n_b++; if ( $r->hired ) $n_h++; } }
	?>
	<div class="mdst">
		<div class="mds-card mdst-head">
			<p class="mds-hint" style="margin:0">
				재직 <strong><?php echo (int) $n_a; ?>명</strong> · 생일 입력 <strong><?php echo (int) $n_b; ?></strong> · 입사일 입력 <strong><?php echo (int) $n_h; ?></strong>.
				생일과 입사일을 넣으면 달력에 🎂 생일 · 🎉 입사 N주년이 자동으로 표시됩니다. 생일의 연도는 표시하지 않습니다.
			</p>
			<form method="post" class="mdst-import">
				<input type="hidden" name="md_staff_action" value="import"><input type="hidden" name="md_staff_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_staff_import' ) ); ?>">
				<button type="submit" class="mds-btn mds-btn--ghost">홈페이지 명단에서 새 사람 가져오기</button>
			</form>
		</div>

		<section class="mds-card mdst-new">
			<h2 class="mdst-title">직원 추가</h2>
			<?php md_staff_render_row_form( null, $depts ); ?>
		</section>

		<?php
		$by = array();
		foreach ( $rows as $r ) { $by[ $r->dept ?: '기타' ][] = $r; }
		$order = array_values( array_unique( array_merge( $depts, array_keys( $by ) ) ) );
		foreach ( $order as $d ) : if ( empty( $by[ $d ] ) ) continue; ?>
			<section class="mds-card mdst-dept">
				<h2 class="mdst-title"><?php echo esc_html( $d ); ?> <small><?php echo count( $by[ $d ] ); ?>명</small></h2>
				<div class="mdst-rows">
					<?php foreach ( $by[ $d ] as $r ) : ?>
						<div class="mdst-item"><?php md_staff_render_row_form( $r, $depts ); ?></div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	</div>
	<?php
}
