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

/* ============================================================
 * v9.64 · 탭 전부 (원장 지시 — 직원 화면에서 다른 탭이 안 보임)
 *  시트는 「제한됨」이라 시트 ID 로는 못 읽는다(401) → 「파일 › 공유 › 웹에 게시 › 문서 전체」 주소(…/d/e/2PACX…/pubhtml)를
 *  관리자 화면에 넣으면 pubhtml 에서 탭 이름 · gid 를 읽고, 탭마다 pub?gid=…&single=true&output=csv 로 읽는다.
 * ============================================================ */
function md_fees_pub_base() {
	$u = trim( (string) get_option( 'md_fees_csv_url', '' ) );
	return preg_match( '#^https://docs\.google\.com/spreadsheets/d/e/([A-Za-z0-9_-]{20,})/#', $u, $m ) ? 'https://docs.google.com/spreadsheets/d/e/' . $m[1] . '/' : '';
}

/** 탭 목록 [ [ 'gid' => '0', 'name' => '…' ], … ] — 웹에 게시 주소가 없으면 빈 배열(예전처럼 첫 탭만) */
function md_fees_tabs( $fresh = false ) {
	$base = md_fees_pub_base();
	if ( '' === $base ) { return array(); }
	$key = 'md_fees_tabs_' . md5( $base );
	if ( ! $fresh ) { $c = get_transient( $key ); if ( is_array( $c ) ) { return $c; } }
	$r    = wp_remote_get( $base . 'pubhtml', array( 'timeout' => 12, 'redirection' => 5 ) );
	$html = is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ? '' : (string) wp_remote_retrieve_body( $r );
	$tabs = array();
	if ( preg_match_all( '#id="sheet-button-(\d+)"[^>]*>\s*<a[^>]*>(.*?)</a>#s', $html, $mm, PREG_SET_ORDER ) ) {
		foreach ( $mm as $m ) { $tabs[ $m[1] ] = array( 'gid' => $m[1], 'name' => trim( html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES, 'UTF-8' ) ) ); }
	}
	if ( ! $tabs && preg_match_all( '#name:\s*"((?:[^"\\\\]|\\\\.)*)"[^}]*?gid:\s*"(\d+)"#s', $html, $mm, PREG_SET_ORDER ) ) {
		foreach ( $mm as $m ) { $tabs[ $m[2] ] = array( 'gid' => $m[2], 'name' => json_decode( '"' . $m[1] . '"' ) ); }
	}
	$tabs = array_values( $tabs );
	if ( '' === $html ) { $last = get_option( 'md_fees_tabs_last' ); return is_array( $last ) ? $last : array(); }
	set_transient( $key, $tabs, MINUTE_IN_SECONDS ); /* v9.65 · 탭을 지우거나 더해도 1분 안에 (원장 지시) */
	update_option( 'md_fees_tabs_last', $tabs, false );
	return $tabs;
}

/** 읽을 CSV 주소 — 탭(gid)을 고르면 그 탭 */
function md_fees_tab_csv_url( $gid = null ) {
	$base = md_fees_pub_base();
	if ( '' !== $base ) { return $base . 'pub?' . ( null !== $gid && '' !== $gid ? 'gid=' . rawurlencode( (string) $gid ) . '&single=true&' : '' ) . 'output=csv'; }
	return md_fees_csv_url();
}

/** 시트 → 줄 배열 (첫 줄 = 머리). 1분 캐시, 못 읽으면 마지막으로 읽은 것 (v9.64 · 탭마다) */
function md_fees_rows( $fresh = false, $gid = null ) {
	$url = md_fees_tab_csv_url( $gid );
	$key = 'md_fees_rows_' . md5( $url );
	$opt = null === $gid ? 'md_fees_rows_last' : 'md_fees_rows_last_' . md5( $url );
	if ( ! $fresh ) { $c = get_transient( $key ); if ( is_array( $c ) ) { return $c; } }
	$r = wp_remote_get( $url, array( 'timeout' => 12, 'redirection' => 5 ) );
	$body = is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ? '' : (string) wp_remote_retrieve_body( $r );
	if ( '' === $body || false !== stripos( substr( $body, 0, 300 ), '<html' ) ) {
		$last = get_option( $opt );
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
	set_transient( $key, $out, MINUTE_IN_SECONDS ); /* v9.62 · 시트를 고치면 1분 안에 직원 화면에 (원장 지시) */
	update_option( $opt, $out, false );
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
	foreach ( md_fees_tabs( true ) as $tb ) { md_fees_rows( true, $tb['gid'] ); }
	md_fees_rows( true );
	wp_safe_redirect( add_query_arg( 'fr', 1, md_sup_url( array( 'app' => 'fees' ) ) ) );
	exit;
}
add_action( 'template_redirect', 'md_fees_handle_post', 5 );

/** 일반 직원 화면 — 서버가 그린 표 (시트 주소 · 다운로드 없음) */
function md_fees_render_table() {
	/* v9.64 · 탭 — 주소 ?ft=gid (웹에 게시 주소가 있을 때만) */
	$tabs = md_fees_tabs();
	$gid  = null;
	if ( $tabs ) {
		$want = isset( $_GET['ft'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_GET['ft'] ) ) : '';
		$gid  = $tabs[0]['gid'];
		foreach ( $tabs as $tb ) { if ( $tb['gid'] === $want ) { $gid = $want; } }
	}
	if ( count( $tabs ) > 1 ) {
		echo '<nav class="mdfee__tabs" aria-label="진료비 탭">';
		foreach ( $tabs as $tb ) {
			$on = $tb['gid'] === $gid;
			echo '<a class="mdfee__tab' . ( $on ? ' is-on' : '' ) . '"' . ( $on ? ' aria-current="page"' : '' ) . ' href="' . esc_url( md_sup_url( array( 'app' => 'fees', 'ft' => $tb['gid'] ) ) ) . '">' . esc_html( '' !== $tb['name'] ? $tb['name'] : '탭' ) . '</a>';
		}
		echo '</nav><style>.mdfee__tabs{display:flex;gap:6px;overflow-x:auto;margin:0 0 10px;padding-bottom:2px;-webkit-overflow-scrolling:touch}.mdfee__tab{flex:0 0 auto;padding:7px 14px;border:1px solid var(--color-border,#E8DDD3);border-radius:999px;background:#fff;color:inherit;text-decoration:none;font-weight:700;font-size:.9rem;white-space:nowrap}.mdfee__tab.is-on{background:#2F2621;border-color:#2F2621;color:#fff}</style>';
	}
	$d    = md_fees_rows( false, $gid );
	$rows = $d['rows'] ?? array();
	if ( ! $rows ) { echo '<div class="mds-card"><div class="mds-empty">진료비 표를 불러오지 못했습니다. 경영지원실에 알려 주세요.</div></div>'; return; }
	$head = array_shift( $rows );
	$n    = count( $head );
	?>
	<div class="mds-card mdfee">
		<div class="mdfee__bar">
			<input type="search" class="mdfee__q" placeholder="찾기 — 예: 크라운, 스케일링, 임플란트" aria-label="진료비 찾기" data-mdfee-q>
			<span class="mds-hint mdfee__n"><b data-mdfee-n><?php echo count( $rows ); ?></b>줄<?php echo ! empty( $d['at'] ) ? ' · ' . esc_html( $d['at'] ) . ' 기준' : ''; ?></span>
			<?php if ( ! empty( $d['_stale'] ) ) : /* v9.65 · 시트를 못 읽으면 옛 표라고 알림 */ ?><span class="mds-notice mds-notice--warn" style="flex-basis:100%;margin:0">구글 시트를 지금 읽지 못해 <?php echo esc_html( $d['at'] ); ?>에 읽어 둔 표입니다 — 금액이 바뀌었을 수 있으니 경영지원실에 확인해 주세요.</span><?php endif; ?>
		</div>
		<div class="mdfee__wrap"><table class="mdfee__t">
			<thead><tr><?php foreach ( $head as $h ) : ?><th><?php echo esc_html( $h ); ?></th><?php endforeach; ?></tr></thead>
			<tbody>
			<?php foreach ( $rows as $r ) : $r = array_pad( array_slice( $r, 0, $n ), $n, '' ); ?>
				<tr><?php foreach ( $r as $i => $v ) : ?><td data-l="<?php echo esc_attr( $head[ $i ] ); ?>"<?php echo preg_match( '/진료비|금액|KRW/u', $head[ $i ] ) ? ' class="mdfee__won"' : ''; ?>><?php echo nl2br( esc_html( $v ) ); // phpcs:ignore ?></td><?php endforeach; ?></tr>
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
		.mdfee__t td{padding:6px 9px;border-top:1px solid var(--color-border,#EFE6DD);vertical-align:top;line-height:1.45}
		.mdfee__t td:first-child{white-space:nowrap}
		.mdfee__t td{max-width:16em}
		.mdfee__won{min-width:7em;font-weight:700;color:#2F2621}
		.mdfee__t tbody tr:nth-child(even){background:#FCFAF7}
		.mdfee__t tr[hidden]{display:none}
		.mdfee__t mark{background:#FFE9A8;padding:0}
		/* 휴대폰: 예전 그대로 — 한 줄씩 「항목: 값」 */
		@media (max-width:640px){.mdfee__t thead{display:none}.mdfee__t tr{display:block;padding:6px 0;border-top:1px solid var(--color-border,#EFE6DD)}.mdfee__t td{display:flex;gap:8px;border:0;padding:2px 10px;max-width:none;white-space:normal}.mdfee__t td:empty{display:none}.mdfee__t td::before{content:attr(data-l);flex:0 0 6.5em;color:#8a7b6f;font-size:.8rem}.mdfee__won{text-align:left}.mdfee__t tbody tr:nth-child(even){background:none}}
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
					<p class="mds-hint">대분류 · 중분류 · 세부설명 · 보험/비급여 · 단위 · 진료비 · 비고. 환자 안내 때 이 표를 기준으로 합니다. 금액이 달라졌으면 경영지원실에 알려 주세요.</p>
				</div>
			</div>
			<?php md_fees_render_table(); ?>
		</div>
		<?php
		return;
	}
	delete_transient( 'md_fees_rows_' . md5( md_fees_csv_url() ) ); /* v9.62 */
	$src = md_fees_url( 'edit' );
	?>
	<div class="mdeq mdeq--fees">
		<div class="mds-card mdeq__head">
			<div class="mdeq__title">
				<h2>진료비</h2>
				<p class="mds-hint">
					대분류 · 중분류 · 세부설명 · 보험/비급여 · 단위 · 진료비 · 비고. 환자 안내 때 이 표를 기준으로 합니다.
					관리자는 칸을 눌러 바로 고칠 수 있습니다(브라우저의 구글 계정이 시트 편집자여야 합니다).
					<b>일반 직원에게는 시트가 아니라 표로만 보여 엑셀로 받을 수 없습니다</b> — 여기서 시트를 고치면 직원 화면에도 1분 안에 바뀝니다.
				</p>
			</div>
			<div class="mdeq__actions">
				<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_fees_url( 'open' ) ); ?>" target="_blank" rel="noopener">새 창에서 열기 · 수정</a>
			</div>
		</div>
		<?php /* v9.64 · 직원 화면 탭 — 웹에 게시 주소 (원장 지시) */
		$pub  = md_fees_pub_base();
		$tabs = '' !== $pub ? md_fees_tabs( true ) : array();
		if ( '' !== $pub ) { foreach ( $tabs as $tb ) { delete_transient( 'md_fees_rows_' . md5( md_fees_tab_csv_url( $tb['gid'] ) ) ); } } ?>
		<?php $ok = '' !== $pub && $tabs; $chk = md_fees_rows( true, $tabs ? $tabs[0]['gid'] : null ); /* v9.65 · 직원 화면이 시트를 읽고 있는지 */ ?>
		<?php if ( ! empty( $chk['_stale'] ) || ! empty( $chk['_err'] ) ) : ?><div class="mds-notice mds-notice--warn"><b>직원 화면이 구글 시트를 읽지 못하고 있습니다</b><?php echo ! empty( $chk['at'] ) ? ' — ' . esc_html( $chk['at'] ) . '에 읽어 둔 표가 보입니다' : ''; ?>. 시트가 「제한됨」이면 아래에 「웹에 게시」 주소를 넣어 주세요.</div><?php endif; ?>
		<div class="mds-card">
			<?php if ( isset( $_GET['fr'] ) ) : ?><div class="mds-notice mds-notice--ok">저장하고 다시 읽었습니다.</div><?php endif; ?>
			<details<?php echo $ok ? '' : ' open'; ?>>
				<summary class="mds-hint" style="cursor:pointer">
					<?php if ( $ok ) : ?>직원 화면 탭 <?php echo count( $tabs ); ?>개: <?php echo esc_html( implode( ' · ', wp_list_pluck( $tabs, 'name' ) ) ); ?> <small>(주소 바꾸기)</small>
					<?php elseif ( '' === $pub ) : ?>직원 화면에는 지금 첫 탭만 보입니다 — 시트 › 파일 › 공유 › <b>웹에 게시</b> › 「문서 전체」 · 「웹페이지」로 게시한 주소를 넣으면 탭이 모두 보입니다.
					<?php else : ?><b>탭 목록을 읽지 못했습니다</b> — 「문서 전체」로 게시했는지 확인해 주세요. 지금은 게시된 첫 탭만 보입니다.<?php endif; ?>
				</summary>
				<form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:8px">
					<?php wp_nonce_field( 'md_fees', 'md_fees_nonce' ); ?><input type="hidden" name="md_fees_act" value="csv">
					<input type="url" name="csv_url" aria-label="웹에 게시 주소" value="<?php echo esc_attr( (string) get_option( 'md_fees_csv_url', '' ) ); ?>" placeholder="https://docs.google.com/spreadsheets/d/e/2PACX-…/pubhtml" style="flex:1 1 320px;min-height:38px;padding:6px 10px;font-size:16px">
					<button type="submit" class="mds-btn mds-btn--fill">저장 · 다시 읽기</button>
				</form>
			</details>
		</div>
		<div class="mds-card mdeq__frame">
			<iframe
				src="<?php echo esc_url( $src ); ?>"
				title="문치과병원 진료비 2026"
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				allow="clipboard-write"></iframe>
		</div>
	</div>
	<?php
}
