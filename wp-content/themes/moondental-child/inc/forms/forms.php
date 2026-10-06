<?php
/**
 * 직원 라운지 · 양식 (v7.6 · 원장 지시 2026-10-06 「상담용지들을 올려두려 해」)
 *
 *   직원(라운지 사용자)  양식 보기 · 찾기 · 열기(인쇄) · 내려받기
 *   라운지 관리자        + 올리기(여러 개 한 번에) · 이름 · 분류 · 메모 고치기 · 파일 바꾸기 · 지우기(휴지통) · 되살리기 · 완전히 지우기
 *
 *   파일은 uploads/md-forms/ 에 무작위 이름으로 두고(.htaccess 로 바로 열기 막음),
 *   이 화면의 주소(?app=forms&md_form=번호)로만 로그인한 직원에게 내준다.
 *   표 wp_md_forms. 주소 파라미터 fv(화면) · fc(분류) · fid. POST 칸은 f_ 로 시작(WP 질의 변수와 안 겹치게).
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_FORMS_SCHEMA', 1 );

function md_forms_t() { global $wpdb; return $wpdb->prefix . 'md_forms'; }
function md_forms_can_use()    { return function_exists( 'md_sup_can_use' ) && md_sup_can_use(); }
function md_forms_can_manage() { return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage(); }
function md_forms_url( $args = array() ) {
	$args = array_merge( array( 'app' => 'forms' ), $args );
	return function_exists( 'md_sup_url' ) ? md_sup_url( $args ) : add_query_arg( $args, home_url( '/직원/' ) );
}

function md_forms_install() {
	if ( (int) get_option( 'md_forms_schema', 0 ) >= MD_FORMS_SCHEMA ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	dbDelta( 'CREATE TABLE ' . md_forms_t() . " (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(200) NOT NULL DEFAULT '',
		cat VARCHAR(60) NOT NULL DEFAULT '',
		note VARCHAR(500) NOT NULL DEFAULT '',
		file VARCHAR(120) NOT NULL DEFAULT '',
		orig VARCHAR(255) NOT NULL DEFAULT '',
		ext VARCHAR(10) NOT NULL DEFAULT '',
		size BIGINT UNSIGNED NOT NULL DEFAULT 0,
		dl_n INT UNSIGNED NOT NULL DEFAULT 0,
		created_at DATETIME NULL DEFAULT NULL,
		created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
		updated_at DATETIME NULL DEFAULT NULL,
		deleted_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY cat (cat),
		KEY deleted_at (deleted_at)
	) $c;" );
	update_option( 'md_forms_schema', MD_FORMS_SCHEMA, false );
}
add_action( 'init', 'md_forms_install', 20 );

/** 파일 폴더 — 바로 열기 막음 */
function md_forms_dir() {
	$up  = wp_upload_dir( null, false );
	$dir = trailingslashit( $up['basedir'] ) . 'md-forms';
	if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
	if ( ! file_exists( $dir . '/.htaccess' ) ) { @file_put_contents( $dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); }
	if ( ! file_exists( $dir . '/index.php' ) ) { @file_put_contents( $dir . '/index.php', "<?php // Silence\n" ); }
	return $dir;
}

/** 올릴 수 있는 파일 */
function md_forms_types() {
	return array(
		'pdf' => 'application/pdf', 'hwp' => 'application/x-hwp', 'hwpx' => 'application/hwp+zip',
		'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
		'txt' => 'text/plain', 'zip' => 'application/zip',
	);
}
/** 브라우저에서 바로 열리는 것 (열어서 인쇄) */
function md_forms_inline( $ext ) { return in_array( $ext, array( 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'txt' ), true ); }

function md_forms_default_cats() { return array( '상담용지', '동의서', '안내문', '서식', '기타' ); }

function md_forms_get( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_forms_t() . ' WHERE id = %d', (int) $id ) );
}

/** 목록 — 분류 순서(기본 분류 먼저) → 이름 */
function md_forms_list( $trash = false ) {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . md_forms_t() . ' WHERE deleted_at IS ' . ( $trash ? 'NOT ' : '' ) . 'NULL ORDER BY id DESC' );
	$ord  = array_flip( md_forms_default_cats() );
	usort( $rows, function ( $a, $b ) use ( $ord ) {
		$ka = isset( $ord[ $a->cat ] ) ? $ord[ $a->cat ] : ( '' === $a->cat ? 999 : 500 );
		$kb = isset( $ord[ $b->cat ] ) ? $ord[ $b->cat ] : ( '' === $b->cat ? 999 : 500 );
		if ( $ka !== $kb ) { return $ka - $kb; }
		if ( $a->cat !== $b->cat ) { return strnatcasecmp( $a->cat, $b->cat ); }
		return strnatcasecmp( $a->title, $b->title );
	} );
	return $rows;
}

function md_forms_cats() {
	$c = array();
	foreach ( md_forms_list() as $f ) { $k = '' === $f->cat ? '기타' : $f->cat; $c[ $k ] = isset( $c[ $k ] ) ? $c[ $k ] + 1 : 1; }
	return $c;
}

function md_forms_size( $b ) {
	$b = (int) $b;
	if ( $b >= 1048576 ) { return round( $b / 1048576, 1 ) . 'MB'; }
	return max( 1, (int) round( $b / 1024 ) ) . 'KB';
}

/** 올린 파일 하나를 폴더에 넣는다 → [file, orig, ext, size] 또는 WP_Error */
function md_forms_store( $f ) {
	if ( ! $f || UPLOAD_ERR_OK !== (int) $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) {
		$e = $f ? (int) $f['error'] : -1;
		return new WP_Error( 'up', in_array( $e, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? '파일이 너무 큽니다 (한 파일 최대 ' . md_forms_size( wp_max_upload_size() ) . ').' : '파일을 올리지 못했습니다.' );
	}
	$orig = sanitize_file_name( wp_basename( (string) $f['name'] ) );
	$ext  = strtolower( pathinfo( (string) $f['name'], PATHINFO_EXTENSION ) );
	if ( ! isset( md_forms_types()[ $ext ] ) ) { return new WP_Error( 'ext', '「' . $f['name'] . '」 — 올릴 수 없는 형식입니다 (PDF · 한글 · 워드 · 엑셀 · 파워포인트 · 사진 · ZIP).' ); }
	$name = gmdate( 'Ymd' ) . '-' . wp_generate_password( 20, false, false ) . '.' . $ext;
	if ( ! @move_uploaded_file( $f['tmp_name'], md_forms_dir() . '/' . $name ) ) { return new WP_Error( 'mv', '파일을 저장하지 못했습니다.' ); }
	return array( 'file' => $name, 'orig' => (string) $f['name'], 'ext' => $ext, 'size' => (int) $f['size'] );
}

function md_forms_title_from( $orig ) {
	return trim( preg_replace( '/[_]+/u', ' ', pathinfo( $orig, PATHINFO_FILENAME ) ) );
}

/* ============================================================
 * 내려받기 · 열기 (GET ?md_form=번호[&dl=1])
 * ============================================================ */

function md_forms_handle_file() {
	if ( empty( $_GET['md_form'] ) ) { return; }
	if ( ! md_forms_can_use() ) { wp_die( '직원 라운지에 로그인한 뒤 열 수 있습니다.', '권한 없음', array( 'response' => 403 ) ); }
	$f = md_forms_get( (int) $_GET['md_form'] );
	if ( ! $f || ( $f->deleted_at && ! md_forms_can_manage() ) ) { wp_die( '양식을 찾을 수 없습니다.', '', array( 'response' => 404 ) ); }
	$path = md_forms_dir() . '/' . basename( $f->file );
	if ( '' === $f->file || ! is_readable( $path ) ) { wp_die( '파일이 없습니다.', '', array( 'response' => 404 ) ); }
	$dl = ! empty( $_GET['dl'] ) || ! md_forms_inline( $f->ext );
	if ( $dl ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . md_forms_t() . ' SET dl_n = dl_n + 1 WHERE id = %d', (int) $f->id ) );
	}
	$fname = ( '' !== $f->title ? $f->title : md_forms_title_from( $f->orig ) ) . '.' . $f->ext;
	$fname = str_replace( array( '/', '\\', '"', "\r", "\n" ), ' ', $fname );
	$types = md_forms_types();
	while ( ob_get_level() ) { ob_end_clean(); }
	nocache_headers();
	header( 'Content-Type: ' . ( isset( $types[ $f->ext ] ) ? $types[ $f->ext ] : 'application/octet-stream' ) . ( 'txt' === $f->ext ? '; charset=utf-8' : '' ) );
	header( 'Content-Disposition: ' . ( $dl ? 'attachment' : 'inline' ) . '; filename="form.' . $f->ext . '"; filename*=UTF-8\'\'' . rawurlencode( $fname ) );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $path );
	exit;
}
add_action( 'template_redirect', 'md_forms_handle_file', 2 );

/* ============================================================
 * 저장 (POST · 관리자)
 * ============================================================ */

function md_forms_handle_post() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['md_forms_action'] ) ) { return; }
	if ( ! md_forms_can_manage() ) { wp_die( '양식은 라운지 관리자가 올리고 고칩니다.', '권한 없음', array( 'response' => 403 ) ); }
	$action = sanitize_key( wp_unslash( $_POST['md_forms_action'] ) );
	if ( ! isset( $_POST['md_forms_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_forms_nonce'] ), 'md_forms_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 새로고침한 뒤 다시 시도해 주세요.' );
	}
	global $wpdb;
	$t    = md_forms_t();
	$now  = current_time( 'mysql' );
	$id   = isset( $_POST['fid'] ) ? (int) $_POST['fid'] : 0;
	$txt  = function ( $k, $n ) { return isset( $_POST[ $k ] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ), 0, $n ) : ''; };
	$cat  = $txt( 'f_cat_new', 60 );
	if ( '' === $cat ) { $cat = $txt( 'f_cat', 60 ); }
	$back = md_forms_url();
	$msg  = array();

	switch ( $action ) {
		case 'upload':
			$files = isset( $_FILES['f_files'] ) ? $_FILES['f_files'] : null;
			$n     = $files && is_array( $files['name'] ) ? count( $files['name'] ) : 0;
			$ok    = 0; $errs = array();
			$title = $txt( 'f_title', 200 );
			for ( $i = 0; $i < $n; $i++ ) {
				if ( '' === (string) $files['name'][ $i ] ) { continue; }
				$one = array( 'name' => $files['name'][ $i ], 'tmp_name' => $files['tmp_name'][ $i ], 'error' => $files['error'][ $i ], 'size' => $files['size'][ $i ] );
				$st  = md_forms_store( $one );
				if ( is_wp_error( $st ) ) { $errs[] = $st->get_error_message(); continue; }
				$wpdb->insert( $t, array(
					'title' => 1 === $n && '' !== $title ? $title : mb_substr( md_forms_title_from( $st['orig'] ), 0, 200 ),
					'cat' => $cat, 'note' => $txt( 'f_note', 500 ), 'file' => $st['file'], 'orig' => mb_substr( $st['orig'], 0, 255 ), 'ext' => $st['ext'], 'size' => $st['size'],
					'created_at' => $now, 'created_by' => get_current_user_id(), 'updated_at' => $now,
				) );
				$ok++;
			}
			if ( ! $n ) { $errs[] = '올릴 파일을 골라 주세요.'; }
			$back = md_forms_url( array_filter( array( 'fok' => $ok ? $ok . '개 양식을 올렸습니다.' : '', 'ferr' => implode( ' / ', $errs ), 'fc' => $ok ? $cat : '' ) ) );
			break;

		case 'edit':
			$f = md_forms_get( $id );
			if ( ! $f ) { break; }
			$u = array( 'title' => $txt( 'f_title', 200 ), 'cat' => $cat, 'note' => $txt( 'f_note', 500 ), 'updated_at' => $now );
			if ( '' === $u['title'] ) { $u['title'] = $f->title; }
			$err = '';
			if ( ! empty( $_FILES['f_file']['name'] ) ) {
				$st = md_forms_store( $_FILES['f_file'] );
				if ( is_wp_error( $st ) ) { $err = $st->get_error_message(); }
				else {
					@unlink( md_forms_dir() . '/' . basename( $f->file ) );
					$u = array_merge( $u, array( 'file' => $st['file'], 'orig' => mb_substr( $st['orig'], 0, 255 ), 'ext' => $st['ext'], 'size' => $st['size'] ) );
				}
			}
			$wpdb->update( $t, $u, array( 'id' => $id ) );
			$back = md_forms_url( array_filter( array( 'fok' => '「' . $u['title'] . '」을 고쳤습니다.', 'ferr' => $err ) ) ) . '#form-' . $id;
			break;

		case 'delete':
			$wpdb->update( $t, array( 'deleted_at' => $now ), array( 'id' => $id ) );
			$back = md_forms_url( array( 'fok' => '휴지통으로 옮겼습니다. 「지운 양식」에서 되살릴 수 있습니다.' ) );
			break;

		case 'restore':
			$wpdb->update( $t, array( 'deleted_at' => null, 'updated_at' => $now ), array( 'id' => $id ) );
			$back = md_forms_url( array( 'fv' => 'trash', 'fok' => '되살렸습니다.' ) );
			break;

		case 'purge':
			$f = md_forms_get( $id );
			if ( $f && $f->deleted_at ) {
				@unlink( md_forms_dir() . '/' . basename( $f->file ) );
				$wpdb->delete( $t, array( 'id' => $id ) );
			}
			$back = md_forms_url( array( 'fv' => 'trash', 'fok' => '완전히 지웠습니다.' ) );
			break;
	}
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_forms_handle_post', 1 );

/* ============================================================
 * 화면
 * ============================================================ */

function md_forms_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'forms' !== md_sup_current_app() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/forms.css' ) ) {
		wp_enqueue_style( 'md-forms', $uri . '/assets/css/forms.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/forms.css' ) );
	}
	if ( file_exists( $dir . '/assets/js/forms.js' ) ) {
		wp_enqueue_script( 'md-forms', $uri . '/assets/js/forms.js', array(), filemtime( $dir . '/assets/js/forms.js' ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'md_forms_enqueue', 40 );

function md_forms_hidden( $action ) {
	echo '<input type="hidden" name="md_forms_action" value="' . esc_attr( $action ) . '">';
	wp_nonce_field( 'md_forms_' . $action, 'md_forms_nonce', false );
}

function md_forms_cat_fields( $cur = '' ) {
	$cats = array_unique( array_merge( md_forms_default_cats(), array_keys( md_forms_cats() ) ) );
	if ( '' !== $cur && ! in_array( $cur, $cats, true ) ) { $cats[] = $cur; }
	echo '<label class="mds-field"><span>분류</span><select name="f_cat">';
	foreach ( $cats as $c ) { echo '<option value="' . esc_attr( $c ) . '"' . selected( '' === $cur ? '상담용지' : $cur, $c, false ) . '>' . esc_html( $c ) . '</option>'; }
	echo '</select></label>';
	echo '<label class="mds-field"><span>새 분류 (목록에 없으면)</span><input name="f_cat_new" maxlength="60" placeholder="예: 교정 상담"></label>';
}

function md_forms_icon( $ext ) {
	$m = array( 'pdf' => 'PDF', 'hwp' => 'HWP', 'hwpx' => 'HWP', 'doc' => 'DOC', 'docx' => 'DOC', 'xls' => 'XLS', 'xlsx' => 'XLS', 'ppt' => 'PPT', 'pptx' => 'PPT', 'zip' => 'ZIP', 'txt' => 'TXT' );
	$k = isset( $m[ $ext ] ) ? $m[ $ext ] : 'IMG';
	return '<span class="mdf-ico mdf-ico--' . esc_attr( strtolower( $k ) ) . '" aria-hidden="true">' . esc_html( $k ) . '</span>';
}

function md_forms_render() {
	md_forms_install();
	if ( ! md_forms_can_use() ) { return; }
	$admin = md_forms_can_manage();
	$fv    = isset( $_GET['fv'] ) ? sanitize_key( wp_unslash( $_GET['fv'] ) ) : '';
	echo '<div class="mdf">';
	foreach ( array( 'fok' => 'ok', 'ferr' => 'warn' ) as $k => $cls ) {
		if ( ! empty( $_GET[ $k ] ) ) { echo '<div class="mds-notice mds-notice--' . $cls . '">' . esc_html( wp_unslash( $_GET[ $k ] ) ) . '</div>'; }
	}
	if ( 'trash' === $fv && $admin ) { md_forms_render_trash(); echo '</div>'; return; }

	$list = md_forms_list();
	$cats = md_forms_cats();
	$fc   = isset( $_GET['fc'] ) ? sanitize_text_field( wp_unslash( $_GET['fc'] ) ) : '';
	if ( '' !== $fc && ! isset( $cats[ $fc ] ) ) { $fc = ''; }
	?>
	<div class="mds-card mdf-head">
		<div>
			<p class="mds-hint">상담용지 · 동의서 · 안내문 같은 병원 양식입니다. 「열기」로 바로 인쇄하거나 「받기」로 내려받으세요.<?php echo $admin ? '' : ' 새 양식이나 고칠 것은 경영지원실에 알려 주세요.'; ?></p>
		</div>
		<?php if ( $admin ) : ?>
		<div class="mdf-head__act">
			<button type="button" class="mds-btn mds-btn--fill" data-mdf-open="mdf-up">＋ 양식 올리기</button>
			<?php $nt = count( md_forms_list( true ) ); if ( $nt ) : ?><a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_forms_url( array( 'fv' => 'trash' ) ) ); ?>">지운 양식 <?php echo (int) $nt; ?></a><?php endif; ?>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( $admin ) : ?>
	<form class="mds-card mdf-up" id="mdf-up" method="post" enctype="multipart/form-data"<?php echo $list ? ' hidden' : ''; ?>>
		<?php md_forms_hidden( 'upload' ); ?>
		<h3>양식 올리기</h3>
		<label class="mds-field mdf-drop"><span>파일 (여러 개 한 번에 고를 수 있음 · 한 파일 최대 <?php echo esc_html( md_forms_size( wp_max_upload_size() ) ); ?>)</span>
			<input type="file" name="f_files[]" multiple required accept=".pdf,.hwp,.hwpx,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip">
			<em class="mdf-drop__names" data-mdf-names></em>
		</label>
		<div class="mdf-grid">
			<label class="mds-field mds-field--grow"><span>이름 (비우면 파일 이름 · 여러 개면 각각 파일 이름)</span><input name="f_title" maxlength="200" placeholder="예: 임플란트 상담용지"></label>
			<?php md_forms_cat_fields(); ?>
		</div>
		<label class="mds-field"><span>메모 (선택)</span><input name="f_note" maxlength="500" placeholder="예: 2026년 10월 개정판 · 양면 인쇄"></label>
		<div class="mdf-up__btns"><button class="mds-btn mds-btn--fill">올리기</button><?php if ( $list ) : ?><button type="button" class="mds-btn mds-btn--ghost" data-mdf-close="mdf-up">닫기</button><?php endif; ?></div>
	</form>
	<?php endif; ?>

	<?php if ( ! $list ) : ?>
		<div class="mds-card mdf-empty">아직 올린 양식이 없습니다.<?php echo $admin ? ' 위에서 파일을 골라 올려 주세요.' : ''; ?></div>
	<?php else : ?>
	<div class="mdf-bar">
		<input class="mdf-q" type="search" placeholder="양식 찾기" aria-label="양식 찾기" data-mdf-q>
		<nav class="mdf-chips" aria-label="분류">
			<a class="mds-tab<?php echo '' === $fc ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_forms_url() ); ?>" data-mdf-cat="">전체 <small><?php echo count( $list ); ?></small></a>
			<?php foreach ( $cats as $c => $n ) : ?>
				<a class="mds-tab<?php echo $fc === $c ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_forms_url( array( 'fc' => $c ) ) ); ?>" data-mdf-cat="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?> <small><?php echo (int) $n; ?></small></a>
			<?php endforeach; ?>
		</nav>
	</div>
	<div class="mdf-list" data-mdf-list>
		<?php $prev = null; foreach ( $list as $f ) :
			$c = '' === $f->cat ? '기타' : $f->cat;
			$hide = '' !== $fc && $fc !== $c;
			if ( $c !== $prev ) { echo '<h3 class="mdf-cat" data-mdf-head="' . esc_attr( $c ) . '"' . ( $hide ? ' hidden' : '' ) . '>' . esc_html( $c ) . '</h3>'; $prev = $c; } ?>
		<article class="mds-card mdf-item" id="form-<?php echo (int) $f->id; ?>" data-mdf-item data-cat="<?php echo esc_attr( $c ); ?>" data-text="<?php echo esc_attr( mb_strtolower( $f->title . ' ' . $f->note . ' ' . $f->orig . ' ' . $c ) ); ?>"<?php echo $hide ? ' hidden' : ''; ?>>
			<?php echo md_forms_icon( $f->ext ); // phpcs:ignore ?>
			<div class="mdf-item__body">
				<b class="mdf-item__title"><?php echo esc_html( $f->title ); ?></b>
				<span class="mdf-item__meta"><?php echo esc_html( strtoupper( $f->ext ) . ' · ' . md_forms_size( $f->size ) . ' · ' . mysql2date( 'Y.n.j', $f->updated_at ? $f->updated_at : $f->created_at ) . ( $admin && $f->dl_n ? ' · 받은 횟수 ' . (int) $f->dl_n : '' ) ); ?></span>
				<?php if ( '' !== $f->note ) : ?><span class="mdf-item__note"><?php echo esc_html( $f->note ); ?></span><?php endif; ?>
			</div>
			<div class="mdf-item__act">
				<?php if ( md_forms_inline( $f->ext ) ) : ?><a class="mds-btn mds-btn--fill" href="<?php echo esc_url( add_query_arg( 'md_form', (int) $f->id, md_forms_url() ) ); ?>" target="_blank" rel="noopener">열기 · 인쇄</a><?php endif; ?>
				<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( add_query_arg( array( 'md_form' => (int) $f->id, 'dl' => 1 ), md_forms_url() ) ); ?>">받기</a>
				<?php if ( $admin ) : ?><button type="button" class="mds-btn mds-btn--ghost" data-mdf-open="mdf-edit-<?php echo (int) $f->id; ?>">고치기</button><?php endif; ?>
			</div>
			<?php if ( $admin ) : ?>
			<div class="mdf-edit" id="mdf-edit-<?php echo (int) $f->id; ?>" hidden>
				<form method="post" enctype="multipart/form-data">
					<?php md_forms_hidden( 'edit' ); ?><input type="hidden" name="fid" value="<?php echo (int) $f->id; ?>">
					<div class="mdf-grid">
						<label class="mds-field mds-field--grow"><span>이름</span><input name="f_title" maxlength="200" value="<?php echo esc_attr( $f->title ); ?>" required></label>
						<?php md_forms_cat_fields( $f->cat ); ?>
					</div>
					<label class="mds-field"><span>메모</span><input name="f_note" maxlength="500" value="<?php echo esc_attr( $f->note ); ?>"></label>
					<label class="mds-field"><span>파일 바꾸기 (새 판으로 · 선택) — 지금: <?php echo esc_html( $f->orig ); ?></span><input type="file" name="f_file" accept=".pdf,.hwp,.hwpx,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.zip"></label>
					<div class="mdf-up__btns"><button class="mds-btn mds-btn--fill">저장</button><button type="button" class="mds-btn mds-btn--ghost" data-mdf-close="mdf-edit-<?php echo (int) $f->id; ?>">닫기</button></div>
				</form>
				<form method="post" class="mdf-del" data-mdf-confirm="「<?php echo esc_attr( $f->title ); ?>」을 지울까요? 휴지통에서 되살릴 수 있습니다.">
					<?php md_forms_hidden( 'delete' ); ?><input type="hidden" name="fid" value="<?php echo (int) $f->id; ?>">
					<button class="mds-btn mds-btn--ghost mdf-danger">지우기</button>
				</form>
			</div>
			<?php endif; ?>
		</article>
		<?php endforeach; ?>
		<p class="mds-card mdf-empty" data-mdf-none hidden>찾는 양식이 없습니다.</p>
	</div>
	<?php endif; ?>
	</div>
	<?php
}

function md_forms_render_trash() {
	$list = md_forms_list( true );
	?>
	<div class="mds-card mdf-head">
		<div><h2>지운 양식</h2><p class="mds-hint">되살리면 목록으로 돌아갑니다. 「완전히 지우기」는 파일까지 없어집니다.</p></div>
		<div class="mdf-head__act"><a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_forms_url() ); ?>">← 양식</a></div>
	</div>
	<?php if ( ! $list ) : ?><div class="mds-card mdf-empty">휴지통이 비었습니다.</div><?php endif; ?>
	<div class="mdf-list">
	<?php foreach ( $list as $f ) : ?>
		<article class="mds-card mdf-item">
			<?php echo md_forms_icon( $f->ext ); // phpcs:ignore ?>
			<div class="mdf-item__body"><b class="mdf-item__title"><?php echo esc_html( $f->title ); ?></b><span class="mdf-item__meta"><?php echo esc_html( ( '' === $f->cat ? '기타' : $f->cat ) . ' · 지운 날 ' . mysql2date( 'Y.n.j', $f->deleted_at ) ); ?></span></div>
			<div class="mdf-item__act">
				<form method="post"><?php md_forms_hidden( 'restore' ); ?><input type="hidden" name="fid" value="<?php echo (int) $f->id; ?>"><button class="mds-btn mds-btn--fill">되살리기</button></form>
				<form method="post" data-mdf-confirm="「<?php echo esc_attr( $f->title ); ?>」을 완전히 지울까요? 되돌릴 수 없습니다."><?php md_forms_hidden( 'purge' ); ?><input type="hidden" name="fid" value="<?php echo (int) $f->id; ?>"><button class="mds-btn mds-btn--ghost mdf-danger">완전히 지우기</button></form>
			</div>
		</article>
	<?php endforeach; ?>
	</div>
	<?php
}
