<?php
/**
 * 직원 전용 · 진료비 (v4.13)
 *
 * 구글 시트 「문치과병원 진료비 2026」을 직원 전용 화면에 끼워 넣는다.
 *  - 직원(일반) : 읽기 전용 미리보기(/preview)
 *  - 관리자     : 편집 화면(/edit) — 브라우저의 구글 계정이 시트 편집자여야 바로 고쳐진다
 *
 * 시트 ID 는 옵션 md_fees_sheet_id 로 바꿀 수 있다. 기본값은 아래.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_FEES_SHEET_DEFAULT', '181AJicpJlUkVAlceS-WlplK2v3_UZI3f1LTRBE6VaAk' );

function md_fees_sheet_id() {
	$id = trim( (string) get_option( 'md_fees_sheet_id', '' ) );
	if ( '' === $id || ! preg_match( '/^[A-Za-z0-9_-]{20,}$/', $id ) ) { $id = MD_FEES_SHEET_DEFAULT; }
	return $id;
}

function md_fees_can_edit() {
	return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage();
}

function md_fees_url( $mode = 'preview' ) {
	$base = 'https://docs.google.com/spreadsheets/d/' . md_fees_sheet_id();
	if ( 'edit' === $mode ) { return $base . '/edit?rm=minimal&gid=0'; }
	if ( 'open' === $mode ) { return $base . '/edit?gid=0'; }
	return $base . '/preview?rm=minimal&gid=0';
}

function md_fees_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'fees' !== md_sup_current_app() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/equipment.css' ) ) { /* 장비 대장과 같은 틀 */
		wp_enqueue_style( 'moondental-equipment', $uri . '/assets/css/equipment.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/equipment.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'md_fees_enqueue', 31 );

/* ============================================================
 * v9.39 · 직원은 엑셀로 받지 못하게 (원장 지시 — 라운지 관리자 이상만)
 *  - 일반 직원 화면에는 구글 시트 주소를 내보내지 않는다. 서버가 시트를 CSV 로 읽어 표로 그린다(10분 캐시).
 *    (전에는 시트를 그대로 끼워 넣어, 시트 주소만 알면 /export?format=xlsx 로 누구나 받을 수 있었다)
 *  - 라운지 관리자 이상은 예전처럼 구글 시트 편집 화면 (편집자 구글 계정이면 파일 › 다운로드 가능)
 *  - CSV 주소: 옵션 md_fees_csv_url(시트 「웹에 게시 › CSV」 주소)이 있으면 그것, 없으면 시트 ID 의 export?format=csv
 *    → 시트 공유를 「제한됨」으로 바꾸고 웹에 게시한 CSV 주소를 넣으면, 시트 ID 를 알아도 받을 수 없다
 * ============================================================ */
function md_fees_csv_url() {
	$u = trim( (string) get_option( 'md_fees_csv_url', '' ) );
	if ( '' !== $u && preg_match( '#^https://docs\.google\.com/spreadsheets/#', $u ) ) { return $u; }
	return 'https://docs.google.com/spreadsheets/d/' . md_fees_sheet_id() . '/export?format=csv&gid=0';
}

/** 시트 → 줄 배열 (첫 줄 = 머리). 10분 캐시, 못 읽으면 마지막으로 읽은 것 */
function md_fees_rows( $fresh = false ) {
	$key = 'md_fees_rows_' . md5( md_fees_csv_url() );
	if ( ! $fresh ) { $c = get_transient( $key ); if ( is_array( $c ) ) { return $c; } }
	$r = wp_remote_get( md_fees_csv_url(), array( 'timeout' => 12, 'redirection' => 5 ) );
	$body = is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ? '' : (string) wp_remote_retrieve_body( $r );
	if ( '' === $body || false !== stripos( substr( $body, 0, 300 ), '<html' ) ) {
		$last = get_option( 'md_fees_rows_last' );
		return is_array( $last ) ? array_merge( $last, array( '_stale' => 1 ) ) : array( 'rows' => array(), 'at' => '', '_err' => 1 );
	}
	$body = preg_replace( '/^\xEF\xBB\xBF/', '', $body );
	$rows = array();
	$fh = fopen( 'php://temp', 'r+' ); fwrite( $fh, $body ); rewind( $fh );
	while ( false !== ( $row = fgetcsv( $fh, 0, ',', '"', '\\' ) ) ) {
		if ( array( null ) === $row ) { continue; }
		$row = array_map( function ( $v ) { return trim( (string) $v ); }, $row );
		if ( '' === implode( '', $row ) ) { continue; }
		$rows[] = $row;
	}
	fclose( $fh );
	$out = array( 'rows' => $rows, 'at' => current_time( 'Y-m-d H:i' ) );
	set_transient( $key, $out, 10 * MINUTE_IN_SECONDS );
	update_option( 'md_fees_rows_last', $out, false );
	return $out;
}

/** 관리자: CSV 주소 저장 · 지금 다시 읽기 */
function md_fees_handle_post() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['md_fees_act'] ) ) { return; }
	if ( ! md_fees_can_edit() ) { return; }
	if ( ! isset( $_POST['md_fees_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_fees_nonce'] ), 'md_fees' ) ) { wp_die( '요청이 만료되었습니다. 새로고침한 뒤 다시 해 주세요.' ); }
	$act = sanitize_key( wp_unslash( $_POST['md_fees_act'] ) );
	if ( 'csv' === $act ) {
		$u = trim( esc_url_raw( wp_unslash( $_POST['csv_url'] ?? '' ) ) );
		if ( '' === $u || preg_match( '#^https://docs\.google\.com/spreadsheets/#', $u ) ) { update_option( 'md_fees_csv_url', $u, false ); }
	}
	md_fees_rows( true );
	wp_safe_redirect( add_query_arg( 'fr', 1, md_sup_url( array( 'app' => 'fees' ) ) ) );
	exit;
}
add_action( 'template_redirect', 'md_fees_handle_post', 5 );

/** 일반 직원 화면 — 서버가 그린 표 (시트 주소 · 다운로드 없음) */
function md_fees_render_table() {
	$d    = md_fees_rows();
	$rows = $d['rows'] ?? array();
	if ( ! $rows ) { echo '<div class="mds-card"><div class="mds-empty">진료비 표를 불러오지 못했습니다. 경영지원실에 알려 주세요.</div></div>'; return; }
	$head = array_shift( $rows );
	$n    = count( $head );
	?>
	<div class="mds-card mdfee">
		<div class="mdfee__bar">
			<input type="search" class="mdfee__q" placeholder="찾기 — 예: 크라운, 스케일링, 임플란트" aria-label="진료비 찾기" data-mdfee-q>
			<span class="mds-hint mdfee__n"><b data-mdfee-n><?php echo count( $rows ); ?></b>줄<?php echo ! empty( $d['at'] ) ? ' · ' . esc_html( $d['at'] ) . ' 기준' : ''; ?></span>
		</div>
		<div class="mdfee__wrap"><table class="mdfee__t">
			<thead><tr><?php foreach ( $head as $h ) : ?><th><?php echo esc_html( $h ); ?></th><?php endforeach; ?></tr></thead>
			<tbody>
			<?php foreach ( $rows as $r ) : $r = array_pad( array_slice( $r, 0, $n ), $n, '' ); ?>
				<tr><?php foreach ( $r as $i => $v ) : ?><td data-l="<?php echo esc_attr( $head[ $i ] ); ?>"<?php echo preg_match( '/진료비|금액|KRW/u', $head[ $i ] ) ? ' class="mdfee__won"' : ''; ?>><?php echo esc_html( $v ); ?></td><?php endforeach; ?></tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
	</div>
	<style>
		.mdfee__bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 10px}
		.mdfee__q{flex:1 1 240px;min-height:40px;padding:6px 12px;border:1px solid var(--color-border,#E8DDD3);border-radius:10px;font:inherit;font-size:16px}
		.mdfee__wrap{overflow:auto;max-height:72vh;border:1px solid var(--color-border,#E8DDD3);border-radius:10px}
		.mdfee__t{width:100%;border-collapse:collapse;font-size:.9rem}
		.mdfee__t th{position:sticky;top:0;background:#F7F1EB;text-align:left;padding:7px 9px;font-size:.8rem;white-space:nowrap;z-index:1}
		.mdfee__t td{padding:6px 9px;border-top:1px solid var(--color-border,#EFE6DD);vertical-align:top}
		.mdfee__won{white-space:nowrap;font-weight:700;text-align:right}
		.mdfee__t tr[hidden]{display:none}
		.mdfee__t mark{background:#FFE9A8;padding:0}
		@media (max-width:640px){.mdfee__t thead{display:none}.mdfee__t tr{display:block;padding:6px 0;border-top:1px solid var(--color-border,#EFE6DD)}.mdfee__t td{display:flex;gap:8px;border:0;padding:2px 10px}.mdfee__t td:empty{display:none}.mdfee__t td::before{content:attr(data-l);flex:0 0 6.5em;color:#8a7b6f;font-size:.8rem}.mdfee__won{text-align:left}}
	</style>
	<script>
	(function(){var q=document.querySelector('[data-mdfee-q]');if(!q)return;var rows=[].slice.call(document.querySelectorAll('.mdfee__t tbody tr')),n=document.querySelector('[data-mdfee-n]');
	q.addEventListener('input',function(){var t=q.value.trim().toLowerCase().replace(/\s+/g,''),c=0;rows.forEach(function(r){var ok=!t||r.textContent.toLowerCase().replace(/\s+/g,'').indexOf(t)>=0;r.hidden=!ok;if(ok)c++;});n.textContent=c;});})();
	</script>
	<?php
}

function md_fees_render() {
	$edit = md_fees_can_edit();
	if ( ! $edit ) {
		?>
		<div class="mdeq mdeq--fees">
			<div class="mds-card mdeq__head">
				<div class="mdeq__title">
					<h2>진료비</h2>
					<p class="mds-hint">대분류 · 중분류 · 세부설명 · 보험/비급여 · 단위 · 진료비 · 비고. 환자 안내 때 이 표를 기준으로 합니다. 열람만 됩니다. 금액이 달라졌으면 경영지원실에 알려 주세요.</p>
				</div>
			</div>
			<?php md_fees_render_table(); ?>
		</div>
		<?php
		return;
	}
	$src = md_fees_url( 'edit' );
	$csv = (string) get_option( 'md_fees_csv_url', '' );
	?>
	<div class="mdeq mdeq--fees">
		<div class="mds-card mdeq__head">
			<div class="mdeq__title">
				<h2>진료비</h2>
				<p class="mds-hint">
					대분류 · 중분류 · 세부설명 · 보험/비급여 · 단위 · 진료비 · 비고. 환자 안내 때 이 표를 기준으로 합니다.
					관리자는 칸을 눌러 바로 고칠 수 있습니다(브라우저의 구글 계정이 시트 편집자여야 합니다).
					<b>일반 직원에게는 시트가 아니라 표로만 보여 엑셀로 받을 수 없습니다</b>(고친 내용은 10분 안에 반영 · 아래 「직원 화면 지금 새로 읽기」로 바로).
				</p>
			</div>
			<div class="mdeq__actions">
				<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_fees_url( 'open' ) ); ?>" target="_blank" rel="noopener">새 창에서 열기 · 수정</a>
			</div>
		</div>
		<div class="mds-card mdeq__frame">
			<iframe
				src="<?php echo esc_url( $src ); ?>"
				title="문치과병원 진료비 2026"
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				allow="clipboard-write"></iframe>
		</div>
		<details class="mds-card mdfee-admin" style="margin-top:12px"<?php echo isset( $_GET['fr'] ) ? ' open' : ''; ?>>
			<summary style="cursor:pointer;font-weight:700">직원 화면 (엑셀 막기) 설정</summary>
			<?php if ( isset( $_GET['fr'] ) ) : $dd = md_fees_rows(); ?><p class="mds-notice" style="margin:8px 0">직원 화면을 새로 읽었습니다 — <?php echo (int) max( 0, count( $dd['rows'] ?? array() ) - 1 ); ?>줄<?php echo ! empty( $dd['_stale'] ) || ! empty( $dd['_err'] ) ? ' (지금은 시트를 읽지 못해 마지막으로 읽은 표를 보여 줍니다 — CSV 주소와 공유를 확인해 주세요)' : ''; ?>.</p><?php endif; ?>
			<p class="mds-hint">시트 주소를 아는 사람은 공유가 「링크가 있는 모든 사용자」인 동안 엑셀로 받을 수 있습니다. 완전히 막으려면 구글 시트에서 ① 공유 › 일반 액세스를 <b>「제한됨」</b>(편집자만)으로 ② 파일 › 공유 › <b>웹에 게시</b> › 「시트1」 · 「쉼표로 구분된 값(.csv)」 › 게시 ③ 나온 주소를 아래에 넣고 저장. 비워 두면 지금처럼 시트 주소로 읽습니다.</p>
			<form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
				<?php wp_nonce_field( 'md_fees', 'md_fees_nonce' ); ?><input type="hidden" name="md_fees_act" value="csv">
				<input type="url" name="csv_url" value="<?php echo esc_attr( $csv ); ?>" placeholder="https://docs.google.com/spreadsheets/d/e/…/pub?output=csv" style="flex:1 1 360px;min-height:38px;padding:6px 10px">
				<button class="mds-btn mds-btn--fill">저장 · 직원 화면 지금 새로 읽기</button>
			</form>
		</details>
	</div>
	<?php
}
