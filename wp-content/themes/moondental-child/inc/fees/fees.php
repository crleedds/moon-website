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
	set_transient( $key, $out, MINUTE_IN_SECONDS ); /* v9.62 · 시트를 고치면 1분 안에 직원 화면에 (원장 지시) */
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

/**
 * v9.57 · 진료비 한눈에 (원장 지시 — 「진료비가 한눈에 잘 안 보인다」)
 *  예전 표는 넓은 화면에서 진료비 칸이 오른쪽 밖으로 밀려 금액이 안 보였고, 휴대폰은 한 줄이 카드 한 장(대분류 · 중분류 … 반복)이었다.
 *  → 대분류로 묶고(위에 분류 단추), 한 항목 = 한 줄: 왼쪽 이름(중분류 › 소분류 · 세부설명), 오른쪽 금액 크게, 보험/비급여 표시, 단위 · 비고는 작게.
 *  머리 이름(대분류 · 중분류 · 소분류 · 세부설명 · 보험/비급여 · 단위 · 진료비 · 비고)을 못 찾으면 예전 표 그대로.
 */
function md_fees_cols( $head ) {
	$want = array( 'c1' => '/대분류/u', 'c2' => '/중분류/u', 'c3' => '/소분류/u', 'desc' => '/세부|설명/u', 'ins' => '/보험|급여/u', 'unit' => '/단위/u', 'won' => '/진료비|금액|KRW/u', 'note' => '/비고/u' );
	$map  = array();
	foreach ( $want as $k => $re ) {
		foreach ( $head as $i => $h ) { if ( ! in_array( $i, $map, true ) && preg_match( $re, (string) $h ) ) { $map[ $k ] = $i; break; } }
	}
	return isset( $map['c1'], $map['won'] ) ? $map : null;
}

function md_fees_render_list( $rows, $head, $d ) {
	$m = md_fees_cols( $head );
	if ( ! $m ) { return false; }
	$g = function ( $r, $k ) use ( $m ) { return isset( $m[ $k ], $r[ $m[ $k ] ] ) ? trim( (string) $r[ $m[ $k ] ] ) : ''; };
	$groups = array(); $last = array( 'c1' => '', 'c2' => '' );
	foreach ( $rows as $r ) {
		foreach ( array( 'c1', 'c2' ) as $k ) { $v = $g( $r, $k ); if ( '' === $v ) { $v = $last[ $k ]; } $last[ $k ] = $v; } /* 시트에서 칸을 합쳐 둔 곳은 위 값 */
		$groups[ '' !== $last['c1'] ? $last['c1'] : '기타' ][] = array( 'c2' => $last['c2'], 'c3' => $g( $r, 'c3' ), 'desc' => $g( $r, 'desc' ), 'ins' => $g( $r, 'ins' ), 'unit' => $g( $r, 'unit' ), 'won' => $g( $r, 'won' ), 'note' => $g( $r, 'note' ) );
	}
	$kind = function ( $ins ) { $s = preg_replace( '/\s+/u', '', $ins ); if ( '' === $s ) { return ''; } if ( preg_match( '/비급여|비보험/u', $s ) ) { return 'non'; } if ( false !== mb_strpos( $s, '보험' ) ) { return 'ins'; } return 'etc'; };
	$n = 0; foreach ( $groups as $items ) { $n += count( $items ); }
	?>
	<div class="mds-card mdfee mdfee--list" data-mdfee>
		<div class="mdfee__bar">
			<input type="search" class="mdfee__q" placeholder="찾기 — 예: 크라운, 스케일링, 임플란트" aria-label="진료비 찾기" data-mdfee-q>
			<span class="mds-hint mdfee__n"><b data-mdfee-n><?php echo (int) $n; ?></b>개<?php echo ! empty( $d['at'] ) ? ' · ' . esc_html( $d['at'] ) . ' 기준' : ''; ?></span>
		</div>
		<div class="mdfee__cats" role="group" aria-label="분류">
			<button type="button" class="mdfee__cat is-on" data-mdfee-cat="">전체</button>
			<?php foreach ( $groups as $c => $items ) : ?><button type="button" class="mdfee__cat" data-mdfee-cat="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?> <small><?php echo count( $items ); ?></small></button><?php endforeach; ?>
			<span class="mdfee__sep"></span>
			<button type="button" class="mdfee__cat mdfee__cat--k" data-mdfee-kind="ins">보험</button><button type="button" class="mdfee__cat mdfee__cat--k" data-mdfee-kind="non">비급여</button>
		</div>
		<div class="mdfee__groups">
		<?php foreach ( $groups as $c => $items ) : ?>
			<section class="mdfee__g" data-cat="<?php echo esc_attr( $c ); ?>">
				<h3 class="mdfee__gh"><?php echo esc_html( $c ); ?> <small data-mdfee-gn><?php echo count( $items ); ?></small></h3>
				<ul class="mdfee__ul">
				<?php foreach ( $items as $it ) :
					$k    = $kind( $it['ins'] );
					$name = trim( $it['c2'] . ( '' !== $it['c3'] ? ' › ' . $it['c3'] : '' ) );
					$sub  = array_values( array_filter( array( $it['desc'], '' !== $it['unit'] ? '단위 ' . $it['unit'] : '' ) ) ); ?>
					<li class="mdfee__it" data-kind="<?php echo esc_attr( $k ); ?>" data-q="<?php echo esc_attr( mb_strtolower( preg_replace( '/\s+/u', '', $c . $name . $it['desc'] . $it['ins'] . $it['unit'] . $it['won'] . $it['note'] ) ) ); ?>">
						<div class="mdfee__name">
							<b><?php echo esc_html( '' !== $name ? $name : $c ); ?></b>
							<?php if ( '' !== $it['ins'] ) : ?><span class="mdfee__ins mdfee__ins--<?php echo esc_attr( $k ? $k : 'etc' ); ?>"><?php echo esc_html( $it['ins'] ); ?></span><?php endif; ?>
							<?php if ( $sub ) : ?><small class="mdfee__sub"><?php echo nl2br( esc_html( implode( ' · ', $sub ) ) ); // phpcs:ignore ?></small><?php endif; ?>
							<?php if ( '' !== $it['note'] ) : ?><small class="mdfee__note"><?php echo nl2br( esc_html( $it['note'] ) ); // phpcs:ignore ?></small><?php endif; ?>
						</div>
						<div class="mdfee__won<?php echo mb_strlen( $it['won'] ) > 24 || false !== strpos( $it['won'], "\n" ) ? ' is-long' : ''; ?>"><?php echo '' !== $it['won'] ? nl2br( esc_html( $it['won'] ) ) : '<span class="mdfee__none">—</span>'; // phpcs:ignore ?></div>
					</li>
				<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
		</div>
		<p class="mds-hint mdfee__nohit" hidden data-mdfee-nohit>찾는 항목이 없습니다.</p>
	</div>
	<style>
		.mdfee__bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 10px}
		.mdfee__q{flex:1 1 240px;min-height:42px;padding:6px 12px;border:1px solid var(--color-border,#E8DDD3);border-radius:10px;font:inherit;font-size:16px}
		.mdfee__cats{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px;padding:0;border:0;outline:0;background:none}
		.mdfee__cat{min-height:34px;padding:0 12px;border:1px solid var(--color-border,#E8DDD3);border-radius:999px;background:#fff;font:inherit;font-size:.86rem;font-weight:700;color:var(--color-text,#3D3029);cursor:pointer}
		.mdfee__cat small{font-weight:600;color:var(--color-text-mute,#A89685);margin-left:2px}
		.mdfee__cat.is-on{background:#2F2621;border-color:#2F2621;color:#fff}.mdfee__cat.is-on small{color:rgba(255,255,255,.75)}
		.mdfee__sep{flex:0 0 1px;align-self:stretch;background:var(--color-border,#E8DDD3);margin:0 4px}
		.mdfee__g{margin:0 0 18px}
		.mdfee__gh{position:sticky;top:0;z-index:2;margin:0;padding:8px 2px;background:var(--color-bg-card,#fff);font-size:1.02rem;font-weight:800;border-bottom:2px solid #2F2621}
		.mdfee__gh small{font-weight:600;color:var(--color-text-mute,#A89685);font-size:.8rem}
		.mdfee__ul{list-style:none;margin:0;padding:0}
		.mdfee__it{display:flex;align-items:flex-start;gap:14px;padding:9px 2px;border-bottom:1px solid var(--color-border,#EFE6DD)}
		.mdfee__it[hidden],.mdfee__g[hidden]{display:none}
		.mdfee__name{flex:1 1 auto;min-width:0;line-height:1.45}
		.mdfee__name b{font-size:.96rem;color:var(--color-text,#3D3029)}
		.mdfee__ins{display:inline-block;margin-left:6px;padding:0 7px;border-radius:999px;font-size:.72rem;font-weight:800;vertical-align:2px;white-space:nowrap}
		.mdfee__ins--ins{background:#E3F0E5;color:#3E7A4A}.mdfee__ins--non{background:#F6E6DC;color:#9A5634}.mdfee__ins--etc{background:#EEE9E4;color:#7A6B5F}
		.mdfee__sub,.mdfee__note{display:block;font-size:.8rem;color:var(--color-text-sub,#7A6B5F);margin-top:2px}
		.mdfee__note{color:var(--color-text-mute,#A89685)}
		.mdfee__won{flex:0 0 auto;max-width:46%;text-align:right;font-size:1.08rem;font-weight:800;color:#2F2621;font-variant-numeric:tabular-nums;line-height:1.4;white-space:nowrap}
		.mdfee__won.is-long{white-space:normal;font-size:.86rem;font-weight:700;text-align:left;max-width:52%}
		.mdfee__none{color:var(--color-text-mute,#A89685);font-weight:500}
		.mdfee mark{background:#FFE9A8;padding:0}
		.mdfee--list .mdfee__cats{border:0!important;padding:0!important;background:none!important;box-shadow:none!important}
		.mdfee--list .mdfee__cat{background:#fff!important;color:var(--color-text,#3D3029)!important;border:1px solid var(--color-border,#E8DDD3)!important}
		.mdfee--list .mdfee__cat.is-on{background:#2F2621!important;border-color:#2F2621!important;color:#fff!important}
		@media (min-width:1000px){.mdfee__groups{column-count:2;column-gap:44px}.mdfee__g{break-inside:auto}.mdfee__gh{position:static;break-after:avoid}.mdfee__it{break-inside:avoid}}
		@media (max-width:640px){
			.mdfee__cats{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;margin:0 -14px 12px;padding:0 14px}
			.mdfee__cats::-webkit-scrollbar{display:none}
			.mdfee__cat{flex:none}
			.mdfee__it{gap:10px;padding:10px 0}
			.mdfee__won{max-width:44%;font-size:1rem}
			.mdfee__won.is-long{flex-basis:100%;max-width:none;margin-top:4px;padding:6px 8px;background:#FBF7F3;border-radius:8px}
			.mdfee__it:has(.mdfee__won.is-long){flex-wrap:wrap}
		}
	</style>
	<script>
	(function(){
		var box=document.querySelector('[data-mdfee]');if(!box)return;
		var q=box.querySelector('[data-mdfee-q]'),n=box.querySelector('[data-mdfee-n]'),no=box.querySelector('[data-mdfee-nohit]');
		var items=[].slice.call(box.querySelectorAll('.mdfee__it')),groups=[].slice.call(box.querySelectorAll('.mdfee__g'));
		var cat='',kind='';
		function apply(){
			var t=(q.value||'').trim().toLowerCase().replace(/\s+/g,''),c=0;
			groups.forEach(function(g){
				var gc=0,inCat=!cat||g.getAttribute('data-cat')===cat;
				[].forEach.call(g.querySelectorAll('.mdfee__it'),function(it){
					var ok=inCat&&(!t||it.getAttribute('data-q').indexOf(t)>=0)&&(!kind||it.getAttribute('data-kind')===kind);
					it.hidden=!ok;if(ok){gc++;c++;}
				});
				g.hidden=!gc;var gn=g.querySelector('[data-mdfee-gn]');if(gn)gn.textContent=gc;
			});
			n.textContent=c;no.hidden=c>0;
		}
		q.addEventListener('input',apply);
		box.addEventListener('click',function(e){
			var b=e.target.closest('[data-mdfee-cat]');
			if(b){cat=b.getAttribute('data-mdfee-cat');[].forEach.call(box.querySelectorAll('[data-mdfee-cat]'),function(x){x.classList.toggle('is-on',x===b);});apply();return;}
			var k=e.target.closest('[data-mdfee-kind]');
			if(k){var v=k.getAttribute('data-mdfee-kind');kind=kind===v?'':v;[].forEach.call(box.querySelectorAll('[data-mdfee-kind]'),function(x){x.classList.toggle('is-on',x.getAttribute('data-mdfee-kind')===kind);});apply();}
		});
	})();
	</script>
	<?php
	return true;
}

/** 일반 직원 화면 — 서버가 그린 표 (시트 주소 · 다운로드 없음) */
function md_fees_render_table() {
	$d    = md_fees_rows();
	$rows = $d['rows'] ?? array();
	if ( ! $rows ) { echo '<div class="mds-card"><div class="mds-empty">진료비 표를 불러오지 못했습니다. 경영지원실에 알려 주세요.</div></div>'; return; }
	$head = array_shift( $rows );
	$n    = count( $head );
	if ( md_fees_render_list( $rows, $head, $d ) ) { return; } /* v9.57 · 한눈에 */
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
					<p class="mds-hint">환자 안내 때 이 표를 기준으로 합니다. 위 분류 단추나 찾기로 바로 찾으세요. 금액이 달라졌으면 경영지원실에 알려 주세요.</p>
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
