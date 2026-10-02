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

define( 'MD_STAFF_SCHEMA', 2 ); /* v4.18.2 · phone · email · photo */

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
		phone VARCHAR(40) NOT NULL DEFAULT '',
		email VARCHAR(120) NOT NULL DEFAULT '',
		photo VARCHAR(255) NOT NULL DEFAULT '',
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

/**
 * v4.18.1 · 직원 정보 → 홈페이지 의료진 페이지의 직원 명단(부서|직책|이름) 자동 반영.
 *  원장(의료진)은 사진 · 약력과 함께 코드에서 관리하므로 여기서는 건드리지 않는다.
 */
function md_staff_sync_site() {
	$order = array( '진료실', '기공실', '서비스지원실', '경영지원실', '관리사무소', '예방과', '기타' );
	$by    = array();
	foreach ( md_staff_all( true ) as $r ) {
		if ( '의료진' === $r->dept || '' === trim( $r->name ) ) { continue; }
		$by[ $r->dept ?: '기타' ][] = $r;
	}
	$lines = array();
	foreach ( array_values( array_unique( array_merge( $order, array_keys( $by ) ) ) ) as $d ) {
		foreach ( $by[ $d ] ?? array() as $r ) {
			$lines[] = str_replace( '|', ' ', $r->dept ?: '기타' ) . '|' . str_replace( '|', ' ', $r->position ?: '사원' ) . '|' . str_replace( '|', ' ', $r->name );
		}
	}
	set_theme_mod( 'md_content_staff_list', implode( "\n", $lines ) );
	if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }
	if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); } /* WP Super Cache */
	/* v4.21.1 · 테마 자체 페이지 캐시(md_pcache_ 트랜지언트, 6시간)도 비운다 — 이걸 빠뜨려 의료진 페이지에 옛 명단이 남았다 */
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_md\_pcache\_%' OR option_name LIKE '\_transient\_timeout\_md\_pcache\_%'" );
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
		'note'       => mb_substr( trim( sanitize_text_field( $data['note'] ?? '' ) ), 0, 200 ),
		'phone'      => mb_substr( trim( sanitize_text_field( $data['phone'] ?? '' ) ), 0, 40 ),
		'email'      => ( '' !== trim( (string) ( $data['email'] ?? '' ) ) && is_email( trim( $data['email'] ) ) ) ? sanitize_email( trim( $data['email'] ) ) : '',
		'updated_at' => current_time( 'mysql' ),
	);
	if ( $id ) { $wpdb->update( md_staff_table(), $row, array( 'id' => (int) $id ) ); return (int) $id; }
	$row['sort']       = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(sort),0) FROM ' . md_staff_table() ) + 1;
	$row['created_at'] = current_time( 'mysql' );
	$ok = $wpdb->insert( md_staff_table(), $row );
	return $ok ? (int) $wpdb->insert_id : new WP_Error( 'md_staff', '저장하지 못했습니다.' );
}

function md_staff_delete( $id ) {
	global $wpdb;
	md_staff_photo_remove( $id );
	return (bool) $wpdb->delete( md_staff_table(), array( 'id' => (int) $id ) );
}

/** 사진 보관 폴더 — uploads/staff/ */
function md_staff_photo_dir() {
	$u = wp_upload_dir();
	$dir = trailingslashit( $u['basedir'] ) . 'staff';
	if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); @file_put_contents( $dir . '/index.php', "<?php // silence" ); }
	return array( 'dir' => $dir, 'url' => trailingslashit( $u['baseurl'] ) . 'staff' );
}

function md_staff_photo_url( $r ) {
	/* v4.21.2 · 원장은 올린 사진이 없으면 홈페이지 의료진 사진을 쓴다 */
	if ( empty( $r->photo ) && isset( $r->dept ) && '의료진' === $r->dept && function_exists( 'moondental_doctor_photo_url' ) ) {
		$team = function_exists( 'moondental_get_team_with_customizer' ) ? moondental_get_team_with_customizer() : ( function_exists( 'moondental_get_team' ) ? moondental_get_team() : array() );
		foreach ( (array) $team as $m ) {
			if ( isset( $m['name'] ) && trim( $m['name'] ) === trim( $r->name ) && ! empty( $m['photo'] ) ) { $u = moondental_doctor_photo_url( $m['photo'] ); return $u ? $u : ''; }
		}
	}
	if ( empty( $r->photo ) ) return '';
	$p = md_staff_photo_dir();
	return $p['url'] . '/' . rawurlencode( $r->photo ) . '?v=' . ( $r->updated_at ? strtotime( $r->updated_at ) : 0 );
}

function md_staff_photo_remove( $id ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT photo FROM ' . md_staff_table() . ' WHERE id = %d', (int) $id ) );
	if ( $r && $r->photo ) {
		$p = md_staff_photo_dir();
		$f = $p['dir'] . '/' . basename( $r->photo );
		if ( is_file( $f ) ) { @unlink( $f ); }
		$wpdb->update( md_staff_table(), array( 'photo' => '' ), array( 'id' => (int) $id ) );
	}
}

/** 올린 사진을 480px 정사각 JPG 로 줄여 보관 */
function md_staff_photo_save( $id, $file ) {
	if ( empty( $file['name'] ) || ! empty( $file['error'] ) ) { return ''; }
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$mimes = array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic' );
	$up = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $mimes ) );
	if ( ! $up || ! empty( $up['error'] ) ) { return new WP_Error( 'md_staff', '사진을 올리지 못했습니다: ' . ( $up['error'] ?? '' ) ); }
	$p    = md_staff_photo_dir();
	$name = 's' . (int) $id . '-' . substr( md5( (string) microtime( true ) ), 0, 8 ) . '.jpg';
	$dest = $p['dir'] . '/' . $name;
	$ed   = wp_get_image_editor( $up['file'] );
	if ( is_wp_error( $ed ) ) { @unlink( $up['file'] ); return new WP_Error( 'md_staff', '이미지 파일이 아니거나 열 수 없습니다.' ); }
	$ed->resize( 480, 480, true );
	$ed->set_quality( 86 );
	$ok = $ed->save( $dest, 'image/jpeg' );
	@unlink( $up['file'] );
	if ( is_wp_error( $ok ) ) { return new WP_Error( 'md_staff', '사진을 저장하지 못했습니다.' ); }
	md_staff_photo_remove( $id );
	global $wpdb;
	$wpdb->update( md_staff_table(), array( 'photo' => $name, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	return $name;
}

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
	foreach ( array( 'name', 'dept', 'position', 'birthday', 'hired', 'note', 'phone', 'email' ) as $k ) { $data[ $k ] = isset( $_POST[ $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) : ''; }
	if ( '' === $data['dept'] && ! empty( $_POST['dept_new'] ) ) { $data['dept'] = sanitize_text_field( wp_unslash( $_POST['dept_new'] ) ); }
	switch ( $action ) {
		case 'add':
		case 'edit':
			$res = 'add' === $action ? md_staff_save( $data ) : md_staff_save( $data, $id );
			if ( is_wp_error( $res ) ) { $back = add_query_arg( 'err', $res->get_error_message(), $back ); break; }
			$sid = (int) $res;
			if ( ! empty( $_POST['photo_remove'] ) ) { md_staff_photo_remove( $sid ); }
			if ( ! empty( $_FILES['photo']['name'] ) ) {
				$ph = md_staff_photo_save( $sid, $_FILES['photo'] );
				if ( is_wp_error( $ph ) ) { $back = add_query_arg( 'err', $ph->get_error_message(), $back ); }
			}
			md_staff_sync_site();
			$back .= '#s' . $sid;
			break;
		case 'delete':
			md_staff_delete( $id );
			md_staff_sync_site();
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
	<?php $photo = $edit ? md_staff_photo_url( $r ) : ''; ?>
	<form method="post" enctype="multipart/form-data" class="mdst-row<?php echo $edit ? '' : ' mdst-row--new'; ?>" id="<?php echo $edit ? 's' . (int) $r->id : 'new'; ?>">
		<label class="mdst-f mdst-f--photo" title="사진 올리기 (JPG · PNG · HEIC)">
			<span>사진</span>
			<span class="mdst-photo<?php echo $photo ? ' has-photo' : ''; ?>"><?php if ( $photo ) : ?><img src="<?php echo esc_url( $photo ); ?>" alt=""><?php else : ?><em>＋</em><?php endif; ?></span>
			<input type="file" name="photo" accept="image/*" class="mdst-photo__input">
		</label>
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
		<label class="mdst-f mdst-f--phone"><span>📞 전화</span><input type="tel" name="phone" maxlength="40" value="<?php echo esc_attr( $r->phone ?? '' ); ?>" placeholder="010-0000-0000"></label>
		<label class="mdst-f mdst-f--email"><span>✉️ 이메일</span><input type="email" name="email" maxlength="120" value="<?php echo esc_attr( $r->email ?? '' ); ?>" placeholder="name@example.com"></label>
		<label class="mdst-f mdst-f--note"><span>메모</span><input type="text" name="note" maxlength="200" value="<?php echo esc_attr( $r->note ?? '' ); ?>" placeholder="선택"></label>
		<?php if ( $photo ) : ?><label class="mdst-f mdst-f--chk mds-check"><input type="checkbox" name="photo_remove" value="1"> 사진 지움</label><?php endif; ?>
		<button type="submit" class="mds-btn mds-btn--fill mdst-save"><?php echo $edit ? '저장' : '추가'; ?></button>
	</form>
	<?php if ( $edit ) : ?>
	<form method="post" class="mdst-del" onsubmit="return confirm('<?php echo esc_js( $r->name ); ?> 님을 명단에서 지울까요? 홈페이지 의료진 페이지와 달력에서도 함께 빠집니다.');">
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
	$n_b = 0; $n_h = 0; $n_a = 0;
	foreach ( $rows as $r ) { if ( (int) $r->active ) { $n_a++; if ( $r->birthday ) $n_b++; if ( $r->hired ) $n_h++; } }
	?>
	<div class="mdst">
		<div class="mds-card mdst-head">
			<p class="mds-hint" style="margin:0">
				<strong><?php echo (int) $n_a; ?>명</strong> · 생일 입력 <strong><?php echo (int) $n_b; ?></strong> · 입사일 입력 <strong><?php echo (int) $n_h; ?></strong>.
				여기서 추가 · 수정 · 삭제하면 홈페이지 <a href="<?php echo esc_url( home_url( '/의료진/' ) ); ?>" target="_blank" rel="noopener">의료진 페이지</a>의 직원 명단이 바로 바뀌고, 생일 · 입사일은 라운지 달력에 🎂 🎉 로 표시됩니다(생일 연도는 표시하지 않음).
				원장(의료진)은 사진 · 약력과 함께 관리되므로 이름 · 직책은 홈페이지 설정에서, 생일 · 입사일만 여기서 넣습니다.
			</p>
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
				<h2 class="mdst-title"><?php echo esc_html( $d ); ?> <small><?php echo count( $by[ $d ] ); ?>명<?php echo '의료진' === $d ? ' · 생일 · 입사일만 여기서' : ''; ?></small></h2>
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
