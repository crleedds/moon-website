<?php
/**
 * 품목신청 — 일을 줄이는 도구 (v5.5, 원장 지시)
 *   업체별 발주서 · 출고 준비 목록(인쇄) · 바코드 등록 · 휴대폰 실사 · 정리할 품목
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 공통
 * ============================================================ */

/** 바코드 스캔 창 (요청 화면 밖에서도 쓰게) */
function md_inv_scan_dialog() {
	static $done = false;
	if ( $done ) { return; }
	$done = true;
	?>
	<dialog class="iv-dlg" id="iv-scan-dlg" aria-labelledby="iv-scan-h">
		<div class="iv-dlg__head"><b id="iv-scan-h">바코드 스캔</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<div class="iv-scan"><video id="iv-scan-video" playsinline muted></video><div class="iv-scan__frame"></div></div>
		<p class="iv-help" id="iv-scan-msg">바코드를 네모 안에 맞춰 주세요. 카메라 권한을 물으면 「허용」을 눌러 주세요.</p>
		<div class="iv-scan-manual"><label class="iv-f"><span>직접 입력 (스캐너로 찍어도 됩니다)</span><input class="iv-input" id="iv-scan-manual" inputmode="numeric" enterkeyhint="done" placeholder="바코드 숫자"></label><button type="button" class="iv-btn iv-btn--primary" id="iv-scan-ok">확인</button></div>
	</dialog>
	<?php
}

/** 바코드로 품목 찾기 */
function md_inv_item_by_barcode( $code ) {
	$code = md_inv_txt( $code, 100 );
	if ( '' === $code ) { return null; }
	$r = md_inv_items( array( 'barcode' => $code, 'active' => -1 ) );
	return $r ? $r[0] : null;
}

/** 바코드만 등록 / 바꾸기 */
function md_inv_item_set_barcode( $id, $code ) {
	global $wpdb;
	$it   = md_inv_item( $id );
	$code = md_inv_txt( preg_replace( '/\s+/', '', (string) $code ), 100 );
	if ( ! $it ) { return new WP_Error( 'gone', '품목을 찾을 수 없습니다.' ); }
	if ( '' === $code ) { return new WP_Error( 'empty', '바코드를 찍거나 적어 주세요.' ); }
	$dup = $wpdb->get_var( $wpdb->prepare( 'SELECT name FROM ' . md_inv_t( 'item' ) . ' WHERE barcode = %s AND id <> %d LIMIT 1', $code, (int) $id ) );
	if ( $dup ) { return new WP_Error( 'dup', '이 바코드는 이미 「' . $dup . '」에 등록돼 있습니다.' ); }
	$wpdb->update( md_inv_t( 'item' ), array( 'barcode' => $code, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	md_inv_log( '바코드 등록', $it->name . ' · ' . $code );
	return true;
}

/** 정리할 품목 종류별 개수 */
function md_inv_fix_counts( $items = null ) {
	if ( null === $items ) { $items = md_inv_items(); }
	$c = array( 'noprice' => 0, 'nounit' => 0, 'nocat' => 0, 'neg' => 0, 'nobar' => 0 );
	foreach ( $items as $it ) {
		if ( $it->price <= 0 ) { $c['noprice']++; }
		if ( '' === trim( (string) $it->unit ) ) { $c['nounit']++; }
		if ( ! (int) $it->cat2 ) { $c['nocat']++; }
		if ( $it->stock < 0 ) { $c['neg']++; }
		if ( '' === $it->barcode ) { $c['nobar']++; }
	}
	return $c;
}

/* ============================================================
 * 처리
 * ============================================================ */

function md_inv_act_item_barcode() {
	$r = md_inv_item_set_barcode( (int) md_inv_p( 'item_id' ), (string) md_inv_p( 'barcode' ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	$it = md_inv_item( (int) md_inv_p( 'item_id' ) );
	md_inv_go( 'ok', '「' . $it->name . '」에 바코드를 등록했습니다. 이제 찍으면 바로 찾아집니다.', remove_query_arg( array( 'bc' ), md_inv_back_url() ) );
}

/** 정리할 품목 — 표에서 고친 칸만 한꺼번에 저장 */
function md_inv_act_items_bulk() {
	$f = (array) md_inv_p( 'f', array() );
	$n = 0; $err = array();
	foreach ( $f as $id => $row ) {
		$id = (int) $id;
		if ( ! is_array( $row ) ) { continue; }
		$it = md_inv_item( $id );
		if ( ! $it ) { continue; }
		$d = array(
			'name' => $it->name, 'vendor_id' => (int) $it->vendor_id, 'unit' => $it->unit, 'price' => $it->price,
			'cat1' => (int) $it->cat1, 'cat2' => (int) $it->cat2, 'cat3' => (int) $it->cat3, 'min_stock' => $it->min_stock,
			'barcode' => $it->barcode, 'note' => (string) $it->note,
		);
		$changed = false;
		$d['location'] = (string) $it->location;
		foreach ( array( 'unit', 'price', 'min_stock', 'vendor_id', 'cat2', 'location' ) as $k ) {
			if ( ! array_key_exists( $k, $row ) ) { continue; }
			$v = 'unit' === $k ? md_inv_txt( $row[ $k ], 30 ) : ( 'location' === $k ? md_inv_txt( $row[ $k ], 100 ) : md_inv_int( $row[ $k ] ) );
			if ( (string) $v !== (string) $d[ $k ] ) {
				$d[ $k ] = $v;
				$changed = true;
				if ( 'cat2' === $k ) { $d['cat3'] = 0; $c2 = md_inv_cat( $v ); $d['cat1'] = $c2 ? (int) $c2->parent_id : 0; }
			}
		}
		if ( ! $changed ) { continue; }
		$r = md_inv_item_save( $id, $d );
		if ( is_wp_error( $r ) ) { $err[] = $it->name . ': ' . $r->get_error_message(); } else { $n++; }
	}
	if ( $err ) { md_inv_go( $n ? 'warn' : 'err', $n . '개 저장 · 못 한 것: ' . implode( ' / ', array_slice( $err, 0, 3 ) ) ); }
	md_inv_go( 'ok', $n ? $n . '개 품목을 고쳤습니다.' : '바뀐 칸이 없습니다.' );
}

/* ============================================================
 * 화면 · 정리할 품목
 * ============================================================ */

function md_inv_view_fix() {
	$S     = md_inv_settings();
	$all   = md_inv_items();
	$cnt   = md_inv_fix_counts( $all );
	$k     = md_inv_get( 'ik', 'noprice' );
	$sug   = md_inv_min_suggestions();
	$dups  = md_inv_duplicate_groups();
	$nsug  = 0;
	foreach ( $all as $it ) { if ( isset( $sug[ (int) $it->id ] ) && $sug[ (int) $it->id ]->suggest !== (int) $it->min_stock ) { $nsug++; } }
	$cnt['minsug'] = $nsug;
	$cnt['dup']    = count( $dups );
	$cnt['noloc'] = 0;
	foreach ( $all as $it ) { if ( '' === (string) $it->location ) { $cnt['noloc']++; } }
	$kinds = array( 'noprice' => '단가 없음', 'nounit' => '단위 없음', 'nocat' => $S['label_cat2'] . ' 없음', 'noloc' => '위치 없음', 'neg' => '재고 음수', 'minsug' => '안전재고 제안', 'dup' => '중복 품목' );
	if ( ! isset( $kinds[ $k ] ) ) { $k = 'noprice'; }
	$q    = md_inv_get( 'iq' );
	$rows = array();
	foreach ( $all as $it ) {
		if ( 'noprice' === $k && $it->price > 0 ) { continue; }
		if ( 'nounit' === $k && '' !== trim( (string) $it->unit ) ) { continue; }
		if ( 'nocat' === $k && (int) $it->cat2 ) { continue; }
		if ( 'neg' === $k && $it->stock >= 0 ) { continue; }
		if ( 'noloc' === $k && '' !== (string) $it->location ) { continue; }
		if ( '' !== $q && false === mb_stripos( $it->name . ' ' . md_inv_vendor_name( $it->vendor_id ), $q ) ) { continue; }
		$rows[] = $it;
	}
	$pg   = max( 1, (int) md_inv_get( 'pg', 1 ) );
	$per  = 60;
	$show = array_slice( $rows, ( $pg - 1 ) * $per, $per );
	$c2s  = md_inv_cats_of( 2 );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock' ) ) ); ?>">← 재고</a></p>
	<h2 class="iv-h2">정리할 품목</h2>
	<p class="iv-help">AppSheet 에서 넘어오며 비어 있던 칸입니다. 표에서 바로 고치고 맨 아래 「고친 것 저장」을 누르세요. 고친 줄만 저장됩니다. 단가가 비어 있으면 사용금액 통계가 실제보다 적게 나옵니다.</p>
	<nav class="iv-subnav">
		<?php foreach ( $kinds as $kk => $lb ) : ?>
			<a class="iv-subnav__a<?php echo $k === $kk ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'fix', 'ik' => $kk ) ) ); ?>"><?php echo esc_html( $lb ); ?> <b><?php echo (int) $cnt[ $kk ]; ?></b></a>
		<?php endforeach; ?>
		<a class="iv-subnav__a" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'barcode' ) ) ); ?>">바코드 없음 <b><?php echo (int) $cnt['nobar']; ?></b></a>
	</nav>
	<?php if ( 'minsug' === $k ) { md_inv_fix_minsug( $all, $sug ); return; } ?>
	<?php if ( 'dup' === $k ) { md_inv_fix_dups( $dups ); return; } ?>
	<form class="iv-filter" method="get"><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="fix"><input type="hidden" name="ik" value="<?php echo esc_attr( $k ); ?>">
		<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>" placeholder="품목 · 업체"></label><button class="iv-btn iv-btn--ghost">보기</button></form>
	<?php if ( ! $rows ) { md_inv_empty( '정리할 품목이 없습니다. 👍' ); return; } ?>
	<p class="iv-muted"><?php echo count( $rows ); ?>개<?php echo count( $rows ) > $per ? ' · ' . $per . '개씩' : ''; ?></p>
	<form method="post" data-dirtywarn id="iv-fix-form">
		<?php md_inv_hidden( 'items_bulk' ); ?>
		<div class="iv-table-wrap"><table class="iv-table iv-table--fix">
			<thead><tr><th>품목</th><th>업체</th><th>보관 위치</th><th>단위</th><th class="r">단가 (원)</th><th class="r">안전재고</th><th><?php echo esc_html( $S['label_cat2'] ); ?></th><th class="r">재고</th></tr></thead>
			<tbody>
			<?php foreach ( $show as $it ) : $id = (int) $it->id; ?>
				<tr data-fixrow>
					<td data-l="품목" class="iv-td-name"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $id ) ) ); ?>"><?php echo esc_html( $it->name ); ?></a></td>
					<td data-l="업체"><select class="iv-input iv-input--sm" name="f[<?php echo $id; ?>][vendor_id]" data-orig="<?php echo (int) $it->vendor_id; ?>"><option value="0">—</option><?php foreach ( md_inv_vendors() as $v ) : ?><option value="<?php echo (int) $v->id; ?>"<?php selected( (int) $it->vendor_id, (int) $v->id ); ?>><?php echo esc_html( $v->name ); ?></option><?php endforeach; ?></select></td>
					<td data-l="보관 위치"><input class="iv-input iv-input--sm iv-input--loc" name="f[<?php echo $id; ?>][location]" value="<?php echo esc_attr( (string) $it->location ); ?>" data-orig="<?php echo esc_attr( (string) $it->location ); ?>" list="iv-loc-dl2" maxlength="100"></td>
					<td data-l="단위"><input class="iv-input iv-input--sm iv-input--unit" name="f[<?php echo $id; ?>][unit]" value="<?php echo esc_attr( $it->unit ); ?>" data-orig="<?php echo esc_attr( $it->unit ); ?>" list="iv-unit-dl2" maxlength="20"></td>
					<td data-l="단가" class="r"><input class="iv-input iv-input--sm iv-input--num" name="f[<?php echo $id; ?>][price]" value="<?php echo (int) $it->price; ?>" data-orig="<?php echo (int) $it->price; ?>" inputmode="numeric"></td>
					<td data-l="안전재고" class="r"><input class="iv-input iv-input--sm iv-input--num" type="number" min="0" name="f[<?php echo $id; ?>][min_stock]" value="<?php echo (int) $it->min_stock; ?>" data-orig="<?php echo (int) $it->min_stock; ?>" inputmode="numeric"></td>
					<td data-l="<?php echo esc_attr( $S['label_cat2'] ); ?>"><select class="iv-input iv-input--sm" name="f[<?php echo $id; ?>][cat2]" data-orig="<?php echo (int) $it->cat2; ?>"><option value="0">—</option><?php foreach ( $c2s as $c ) : ?><option value="<?php echo (int) $c->id; ?>"<?php selected( (int) $it->cat2, (int) $c->id ); ?>><?php echo esc_html( $c->name ); ?></option><?php endforeach; ?></select></td>
					<td data-l="재고" class="r"><?php echo md_inv_stock_badge( $it ); // phpcs:ignore ?><?php if ( $it->stock < 0 ) : ?> <a class="iv-btn iv-btn--ghost iv-btn--xs" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'qcount', 'id' => $id ) ) ); ?>">실사</a><?php endif; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		<datalist id="iv-loc-dl2"><?php foreach ( md_inv_locations() as $lc ) : ?><option value="<?php echo esc_attr( $lc ); ?>"></option><?php endforeach; ?></datalist>
		<datalist id="iv-unit-dl2"><?php foreach ( array( 'ea', 'box', '갑', '팩', '봉', '병', '개', '각', '롤', '세트', '통', '대' ) as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>"></option><?php endforeach; ?></datalist>
		<div class="iv-stickyfoot"><span class="iv-muted" id="iv-fix-n"></span><button class="iv-btn iv-btn--primary iv-btn--lg">고친 것 저장</button></div>
	</form>
	<?php
	md_inv_pager( count( $rows ), $per, $pg );
}

/* ============================================================
 * 화면 · 바코드 등록
 * ============================================================ */

function md_inv_view_barcode() {
	$S  = md_inv_settings();
	$bc = md_inv_txt( md_inv_get( 'bc' ), 100 );
	$q  = md_inv_get( 'iq' );
	$c2 = (int) md_inv_get( 'ic2', 0 );
	$scan_url = md_inv_url( array( 'iv' => 'barcode', 'bc' => '' ) );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock' ) ) ); ?>">← 재고</a></p>
	<h2 class="iv-h2">바코드 등록</h2>
	<p class="iv-help">한 번 등록하면 신청 화면·휴대폰 실사에서 찍기만 해도 품목이 찾아집니다. 두 가지 방법 중 편한 쪽으로 하세요.</p>

	<section class="iv-panel">
		<h3 class="iv-h3">① 바코드부터 찍기 — 물건을 들고 있을 때</h3>
		<div class="iv-inline">
			<button type="button" class="iv-btn iv-btn--primary" data-scan-go="<?php echo esc_attr( $scan_url ); ?>"><?php echo md_inv_icon( 'scan', 18 ); // phpcs:ignore ?>찍기</button>
			<form method="get" class="iv-inline-form iv-grow"><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="barcode"><input class="iv-input" name="bc" value="<?php echo esc_attr( $bc ); ?>" placeholder="또는 바코드 숫자 입력 (스캐너 가능)" inputmode="numeric" autocomplete="off"><button class="iv-btn iv-btn--ghost">찾기</button></form>
		</div>
		<?php if ( '' !== $bc ) :
			$hit = md_inv_item_by_barcode( $bc ); ?>
			<?php if ( $hit ) : ?>
				<div class="iv-sent">이미 등록된 바코드입니다 — <b><?php echo esc_html( $hit->name ); ?></b> <a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $hit->id ) ) ); ?>">품목 보기</a></div>
			<?php else : ?>
				<form method="post" class="iv-panel iv-panel--hi">
					<?php md_inv_hidden( 'item_barcode', md_inv_url( array( 'iv' => 'barcode' ) ) ); ?>
					<input type="hidden" name="barcode" value="<?php echo esc_attr( $bc ); ?>">
					<p><b><?php echo esc_html( $bc ); ?></b> — 아직 등록되지 않은 바코드입니다. 어떤 품목인지 골라 주세요.</p>
					<label class="iv-f"><span>품목</span><?php md_inv_item_picker( 'item_id', true ); ?></label>
					<button class="iv-btn iv-btn--primary">이 품목에 등록</button>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</section>

	<section class="iv-panel">
		<h3 class="iv-h3">② 목록에서 고르기 — 바코드 없는 품목</h3>
		<form class="iv-filter" method="get" data-autosubmit><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="barcode">
			<label class="iv-f"><span><?php echo esc_html( $S['label_cat2'] ); ?></span><select class="iv-input" name="ic2"><option value="">전체</option><?php foreach ( md_inv_cats_of( 2 ) as $c ) : ?><option value="<?php echo (int) $c->id; ?>"<?php selected( $c2, (int) $c->id ); ?>><?php echo esc_html( $c->name ); ?></option><?php endforeach; ?></select></label>
			<label class="iv-f iv-f--grow"><span>찾기</span><input class="iv-input" type="search" name="iq" value="<?php echo esc_attr( $q ); ?>"></label><button class="iv-btn iv-btn--ghost">보기</button></form>
		<?php
		$rows = array_values( array_filter( md_inv_items( array( 'search' => $q, 'cat2' => $c2 ) ), function ( $it ) { return '' === $it->barcode; } ) );
		$pg   = max( 1, (int) md_inv_get( 'pg', 1 ) );
		$per  = 40;
		if ( ! $rows ) { md_inv_empty( '바코드 없는 품목이 없습니다.' ); } else {
			echo '<p class="iv-muted">' . count( $rows ) . '개</p><div class="iv-cards">';
			foreach ( array_slice( $rows, ( $pg - 1 ) * $per, $per ) as $it ) {
				echo '<form method="post" class="iv-card iv-card--bc">';
				md_inv_hidden( 'item_barcode' );
				echo '<input type="hidden" name="item_id" value="' . (int) $it->id . '">';
				echo '<div class="iv-card__main"><div class="iv-card__title">' . esc_html( $it->name ) . '</div><div class="iv-card__sub">' . esc_html( trim( md_inv_vendor_name( $it->vendor_id ) . ' · ' . $it->unit, ' ·' ) ) . '</div></div>';
				echo '<div class="iv-actions"><input class="iv-input iv-input--sm iv-input--bc" name="barcode" inputmode="numeric" placeholder="바코드" aria-label="' . esc_attr( $it->name . ' 바코드' ) . '" required>';
				echo '<button type="button" class="iv-btn iv-btn--icon iv-btn--xs" data-scanto="barcode" aria-label="찍기">' . md_inv_icon( 'scan', 16 ) . '</button>';
				echo '<button class="iv-btn iv-btn--primary iv-btn--sm">등록</button></div></form>';
			}
			echo '</div>';
			md_inv_pager( count( $rows ), $per, $pg );
		}
		?>
	</section>
	<?php
	md_inv_scan_dialog();
}

/* ============================================================
 * 화면 · 휴대폰 실사 — 한 품목씩 찍고(찾고) 센 수량만
 * ============================================================ */

function md_inv_view_qcount() {
	$id = (int) md_inv_get( 'id', 0 );
	if ( ! $id && preg_match( '/#(\d+)\s*$/', md_inv_get( 'id_pick' ), $m ) ) { $id = (int) $m[1]; }
	$bc = md_inv_txt( md_inv_get( 'bc' ), 100 );
	$it = $id ? md_inv_item( $id ) : null;
	$miss = '';
	if ( ! $it && '' !== $bc ) {
		$it = md_inv_item_by_barcode( $bc );
		if ( ! $it ) { $miss = $bc; }
	}
	$note_default = '정기 실사 ' . current_time( 'Y-m-d' );
	$here = md_inv_url( array( 'iv' => 'qcount' ) );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock' ) ) ); ?>">← 재고</a></p>
	<h2 class="iv-h2">휴대폰 실사</h2>
	<p class="iv-help">품목을 찍거나 찾은 뒤 <b>센 수량만</b> 넣고 저장하세요. 저장하면 바로 다음 품목을 찾을 수 있게 돌아옵니다. 여러 품목을 표로 한꺼번에 하려면 <a class="iv-link" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'count' ) ) ); ?>">실사 모드</a>.</p>
	<div class="iv-qc-find">
		<button type="button" class="iv-btn iv-btn--primary iv-btn--lg" data-scan-go="<?php echo esc_attr( add_query_arg( 'bc', '', $here ) ); ?>"><?php echo md_inv_icon( 'scan', 20 ); // phpcs:ignore ?>바코드 찍기</button>
		<form method="get" class="iv-qc-pick"><input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="qcount">
			<?php md_inv_item_picker( 'id', true, 0, '또는 품목 이름으로 찾기' ); ?>
			<button class="iv-btn iv-btn--ghost">열기</button>
		</form>
	</div>
	<?php if ( '' !== $miss ) : ?>
		<div class="iv-flash iv-flash--warn">「<?php echo esc_html( $miss ); ?>」 바코드로 등록된 품목이 없습니다. 이름으로 찾거나 <a class="iv-link" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'barcode', 'bc' => $miss ) ) ); ?>">이 바코드를 품목에 등록</a>하세요.</div>
	<?php endif; ?>
	<?php if ( $it ) : ?>
		<form method="post" class="iv-qc-card">
			<?php md_inv_hidden( 'stock_adjust', $here ); ?>
			<input type="hidden" name="item_id" value="<?php echo (int) $it->id; ?>">
			<div class="iv-qc-card__name"><?php echo esc_html( $it->name ); ?></div>
			<div class="iv-qc-card__sub"><?php echo esc_html( trim( md_inv_vendor_name( $it->vendor_id ) . ' · ' . $it->unit . ' · ' . md_inv_item_catpath( $it ), ' ·' ) ); ?><?php echo '' !== (string) $it->location ? ' · 📍' . esc_html( $it->location ) : ''; ?></div>
			<div class="iv-qc-card__book">장부 재고 <b><?php echo (int) $it->stock; ?></b> <?php echo esc_html( $it->unit ); ?></div>
			<label class="iv-f"><span>실제로 센 수량</span><input class="iv-input iv-qc-card__num" type="number" inputmode="numeric" min="0" name="counted" required autofocus value=""></label>
			<label class="iv-f"><span>사유</span><input class="iv-input" name="note" value="<?php echo esc_attr( $note_default ); ?>" maxlength="200" required></label>
			<button class="iv-btn iv-btn--primary iv-btn--lg iv-qc-card__save">저장하고 다음 품목</button>
		</form>
	<?php endif; ?>
	<?php
	$done = md_inv_ledger( array( 'type' => 'adjust', 'from' => current_time( 'Y-m-d' ), 'to' => current_time( 'Y-m-d' ), 'limit' => 50, 'with_void' => 0 ) );
	if ( $done ) {
		echo '<h3 class="iv-h3">오늘 센 품목 <span class="iv-n">' . count( $done ) . '</span></h3><div class="iv-cards">';
		foreach ( $done as $l ) {
			echo '<div class="iv-card iv-card--slim"><div class="iv-card__main"><div class="iv-card__title">' . esc_html( $l->item_name ) . '</div><div class="iv-card__sub">' . esc_html( md_inv_date( $l->created_at, 'H:i' ) . ' · ' . $l->person ) . '</div></div><div class="iv-card__side"><b>' . (int) $l->counted . '</b><small class="iv-muted">' . ( $l->qty > 0 ? '+' : '' ) . (int) $l->qty . '</small></div></div>';
		}
		echo '</div>';
	}
	md_inv_scan_dialog();
}

/* ============================================================
 * 화면 · 출고 준비 목록 (인쇄)
 * ============================================================ */

function md_inv_view_pick() {
	$reqs = md_inv_reqs( array( 'status' => 'pending', 'limit' => 1000, 'order' => 'old' ) );
	$loc  = array();
	foreach ( md_inv_items( array( 'active' => -1 ) ) as $it ) { $loc[ (int) $it->id ] = (string) $it->location; }
	$by   = array();
	foreach ( $reqs as $r ) { $r->loc = isset( $loc[ (int) $r->item_id ] ) ? $loc[ (int) $r->item_id ] : ''; $by[ (int) $r->team_id ][] = $r; }
	/* 팀 안에서는 보관 위치 순 — 창고를 한 바퀴 돌며 꺼내게 (위치 없는 것은 뒤로) */
	foreach ( $by as &$g ) { usort( $g, function ( $a, $b ) { if ( '' === $a->loc xor '' === $b->loc ) { return '' === $a->loc ? 1 : -1; } return strcmp( $a->loc, $b->loc ) ?: strcmp( $a->name, $b->name ); } ); }
	unset( $g );
	?>
	<p class="iv-crumb iv-no-print"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'todo' ) ) ); ?>">← 할 일</a></p>
	<div class="iv-toolbar iv-no-print">
		<h2 class="iv-h2" style="margin:0">출고 준비 목록</h2>
		<button type="button" class="iv-btn iv-btn--primary" data-print>인쇄</button>
	</div>
	<p class="iv-print-head">문치과병원 출고 준비 목록 · <?php echo esc_html( current_time( 'Y-m-d H:i' ) ); ?> · 대기 <?php echo count( $reqs ); ?>건</p>
	<?php if ( ! $reqs ) { md_inv_empty( '기다리는 신청이 없습니다.' ); return; } ?>
	<?php foreach ( $by as $tid => $rows ) : ?>
		<section class="iv-pick">
			<h3 class="iv-h3"><?php echo esc_html( md_inv_team_name( $tid ) ); ?> <small><?php echo count( $rows ); ?>건</small></h3>
			<table class="iv-table iv-table--pick">
				<thead><tr><th class="iv-pick__chk">✓</th><th>위치</th><th>품목</th><th class="r">수량</th><th class="r">재고</th><th>신청자</th><th>메모</th><th>받은 사람</th></tr></thead>
				<tbody><?php foreach ( $rows as $r ) : ?>
					<tr class="<?php echo $r->item_id && $r->stock < $r->qty ? 'is-short' : ''; ?>">
						<td class="iv-pick__chk"><span class="iv-box"></span></td>
						<td data-l="위치"><?php echo esc_html( $r->loc ); ?></td>
						<td data-l="품목"><b><?php echo esc_html( $r->name ); ?></b><?php echo $r->item_id ? '' : ' <small>(목록에 없음)</small>'; ?><?php echo $r->urgent ? ' <span class="iv-tag iv-tag--hot">급함</span>' : ''; ?></td>
						<td data-l="수량" class="r"><b><?php echo (int) $r->qty; ?></b> <?php echo esc_html( $r->unit ); ?></td>
						<td data-l="재고" class="r"><?php echo $r->item_id ? (int) $r->stock : '—'; ?></td>
						<td data-l="신청자"><?php echo esc_html( $r->requester ); ?></td>
						<td data-l="메모"><?php echo esc_html( $r->note ); ?></td>
						<td data-l="받은 사람" class="iv-pick__sign"></td>
					</tr>
				<?php endforeach; ?></tbody>
			</table>
		</section>
	<?php endforeach; ?>
	<p class="iv-help iv-no-print">꺼낸 뒤에는 「할 일」에서 출고를 눌러야 재고에서 빠집니다.</p>
	<?php
}

/* ============================================================
 * 화면 · 업체별 발주서
 * ============================================================ */

function md_inv_view_po() {
	$ords = md_inv_ords( array( 'status' => 'ordered', 'limit' => 0 ) );
	$need = md_inv_need_order( true );
	$byv  = array();
	foreach ( $ords as $o ) { $byv[ (int) $o->vendor_id ]['ord'][] = $o; }
	foreach ( $need as $it ) { $byv[ (int) $it->vendor_id ]['need'][] = $it; }
	uksort( $byv, function ( $a, $b ) { return strcmp( md_inv_vendor_name( $a ), md_inv_vendor_name( $b ) ); } );
	?>
	<p class="iv-crumb iv-no-print"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'orders' ) ) ); ?>">← 주문</a></p>
	<h2 class="iv-h2 iv-no-print">업체별 발주서</h2>
	<p class="iv-help iv-no-print">업체마다 ① 아직 주문 안 한 부족 품목을 한 번에 주문하고 ② 주문해 둔 목록을 엑셀·인쇄로 업체에 보냅니다. 연락처와 발주 방법도 함께 보입니다.</p>
	<?php if ( ! $byv ) { md_inv_empty( '주문할 것도, 주문해 둔 것도 없습니다.' ); return; } ?>
	<?php foreach ( $byv as $vid => $g ) :
		$v    = md_inv_vendor( $vid );
		$ord  = isset( $g['ord'] ) ? $g['ord'] : array();
		$nd   = isset( $g['need'] ) ? $g['need'] : array();
		$sum  = 0; foreach ( $ord as $o ) { $sum += (int) $o->amount * max( 0, (int) $o->qty - (int) $o->recv_qty ) / max( 1, (int) $o->qty ); }
		?>
		<section class="iv-po" id="iv-po-<?php echo (int) $vid; ?>">
			<div class="iv-po__head">
				<div>
					<h3 class="iv-h3"><?php echo esc_html( $v ? $v->name : '업체 없음' ); ?><?php echo $v && $v->prepaid ? ' <span class="iv-tag iv-tag--pp">선납</span>' : ''; ?></h3>
					<?php if ( $v ) : ?><p class="iv-po__contact"><?php echo esc_html( implode( ' · ', array_filter( array( $v->contact, $v->phone, $v->email ) ) ) ); ?><?php echo $v->shop_info ? '<br>' . esc_html( $v->shop_info ) : ''; ?></p><?php endif; ?>
					<?php if ( $v && $v->note ) : ?><details class="iv-card__more iv-no-print"><summary>발주 · 정산 방법</summary><p><?php echo nl2br( esc_html( $v->note ) ); ?></p></details><?php endif; ?>
				</div>
				<div class="iv-actions iv-no-print">
					<?php if ( $ord ) : ?>
						<a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_dl_url( 'po', array( 'ivd' => $vid ) ) ); ?>"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?>발주서 엑셀</a>
						<button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-print="iv-po-<?php echo (int) $vid; ?>">인쇄</button>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $nd ) : ?>
				<form method="post" class="iv-po__need iv-no-print" data-confirm="<?php echo esc_attr( ( $v ? $v->name : '이 업체' ) . ' 부족 품목을 적힌 수량대로 주문할까요?' ); ?>">
					<?php md_inv_hidden( 'ord_many' ); ?>
					<p class="iv-po__label">아직 주문 안 한 부족 품목 <?php echo count( $nd ); ?>개</p>
					<?php foreach ( $nd as $it ) : $qn = max( 1, $it->min_stock - max( 0, $it->stock ) ); ?>
						<label class="iv-po__line"><input type="checkbox" name="ids[]" value="<?php echo (int) $it->id; ?>" checked> <span><?php echo esc_html( $it->name ); ?> <small>재고 <?php echo (int) $it->stock; ?> / 안전 <?php echo (int) $it->min_stock; ?></small></span>
							<input class="iv-input iv-input--sm iv-input--num" type="number" min="1" name="q[<?php echo (int) $it->id; ?>]" value="<?php echo (int) $qn; ?>" aria-label="주문 수량"> <?php echo esc_html( $it->unit ); ?></label>
					<?php endforeach; ?>
					<button class="iv-btn iv-btn--primary iv-btn--sm">고른 것 주문하기</button>
				</form>
			<?php endif; ?>
			<?php if ( $ord ) : ?>
				<p class="iv-print-head">발주서 · 문치과병원 → <?php echo esc_html( $v ? $v->name : '' ); ?> · <?php echo esc_html( current_time( 'Y-m-d' ) ); ?></p>
				<table class="iv-table iv-table--po">
					<thead><tr><th>품목</th><th class="r">수량</th><th>단위</th><th class="r">단가</th><th class="r">금액</th><th>주문일</th></tr></thead>
					<tbody>
					<?php foreach ( $ord as $o ) : $left = (int) $o->qty - (int) $o->recv_qty; ?>
						<tr><td data-l="품목"><?php echo esc_html( $o->item_name ); ?><?php echo $o->recv_qty ? ' <small>(' . (int) $o->recv_qty . '개 받음)</small>' : ''; ?></td><td data-l="수량" class="r"><b><?php echo (int) $left; ?></b></td><td data-l="단위"><?php echo esc_html( $o->unit ); ?></td><td data-l="단가" class="r"><?php echo esc_html( md_inv_num( $o->price ) ); ?></td><td data-l="금액" class="r"><?php echo esc_html( md_inv_num( round( (int) $o->amount * $left / max( 1, (int) $o->qty ) ) ) ); ?></td><td data-l="주문일"><?php echo esc_html( md_inv_date( $o->created_at, 'n/j' ) ); ?></td></tr>
					<?php endforeach; ?>
					</tbody>
					<tfoot><tr><td colspan="4" class="r"><b>합계</b></td><td class="r"><b><?php echo esc_html( md_inv_num( round( $sum ) ) ); ?></b></td><td></td></tr></tfoot>
				</table>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>
	<?php
}


/* ---- 정리할 품목 › 안전재고 제안 ------------------------------- */

function md_inv_fix_minsug( $all, $sug ) {
	$cover = (int) md_inv_set( 'min_cover_weeks' );
	$rows  = array();
	foreach ( $all as $it ) {
		if ( ! isset( $sug[ (int) $it->id ] ) ) { continue; }
		if ( $sug[ (int) $it->id ]->suggest === (int) $it->min_stock ) { continue; }
		$rows[] = $it;
	}
	echo '<p class="iv-help">최근 12주 동안 출고된 양으로 <b>' . (int) $cover . '주 쓸 만큼</b>을 안전재고로 제안합니다(설정 › 운영 설정에서 주 수를 바꿀 수 있음). 출고 기록이 없는 품목은 제안하지 않습니다. 맞는 것만 고르고 적용하세요.</p>';
	if ( ! $rows ) { md_inv_empty( '바꿀 만한 안전재고가 없습니다. (출고 기록이 쌓이면 제안이 나옵니다)' ); return; }
	echo '<form method="post" data-confirm="고른 품목의 안전재고를 바꿀까요?">';
	md_inv_hidden( 'min_apply' );
	echo '<div class="iv-bulkbar"><label class="iv-check"><input type="checkbox" data-checkall="ids[]"> 모두 고르기</label><button class="iv-btn iv-btn--primary iv-btn--sm" data-needcheck="ids[]">고른 것 적용</button></div>';
	echo '<div class="iv-table-wrap iv-sec"><table class="iv-table"><thead><tr><th></th><th>품목</th><th class="r">12주 출고</th><th class="r">주 평균</th><th class="r">지금 안전재고</th><th class="r">제안</th><th class="r">재고</th></tr></thead><tbody>';
	foreach ( $rows as $it ) {
		$s = $sug[ (int) $it->id ];
		echo '<tr><td data-l=""><input type="checkbox" name="ids[]" value="' . (int) $it->id . '" aria-label="선택"></td>';
		echo '<td data-l="품목"><a href="' . esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $it->id ) ) ) . '">' . esc_html( $it->name ) . '</a></td>';
		echo '<td data-l="12주 출고" class="r">' . (int) $s->used . '</td><td data-l="주 평균" class="r">' . esc_html( $s->weekly ) . '</td>';
		echo '<td data-l="지금 안전재고" class="r">' . (int) $it->min_stock . '</td>';
		echo '<td data-l="제안" class="r"><input class="iv-input iv-input--sm iv-input--num" type="number" min="0" name="v[' . (int) $it->id . ']" value="' . (int) $s->suggest . '"></td>';
		echo '<td data-l="재고" class="r">' . md_inv_stock_badge( $it ) . '</td></tr>';
	}
	echo '</tbody></table></div></form>';
}

/* ---- 정리할 품목 › 중복 품목 ------------------------------------ */

function md_inv_fix_dups( $dups ) {
	echo '<p class="iv-help">같은 업체에 같은 이름으로 두 번 이상 등록된 품목입니다. <b>남길 품목</b>을 고르고 「합치기」를 누르면 다른 쪽의 입출고 · 신청 · 주문 기록과 재고가 남길 품목으로 옮겨지고, 다른 쪽은 지워집니다. 비어 있던 단가 · 단위 · 바코드는 지울 쪽 값으로 채웁니다.</p>';
	if ( ! $dups ) { md_inv_empty( '중복 품목이 없습니다. 👍' ); return; }
	foreach ( $dups as $g ) {
		$keep = $g[0];
		foreach ( $g as $it ) { if ( $it->active && ! $keep->active ) { $keep = $it; } }
		echo '<section class="iv-panel iv-dup"><h3 class="iv-h3">' . esc_html( $g[0]->name ) . ' <small>' . esc_html( md_inv_vendor_name( $g[0]->vendor_id ) ) . '</small></h3>';
		echo '<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>코드</th><th class="r">재고</th><th class="r">단가</th><th>단위</th><th class="r">기록</th><th>상태</th><th></th></tr></thead><tbody>';
		foreach ( $g as $it ) {
			echo '<tr><td data-l="코드"><a href="' . esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $it->id ) ) ) . '">' . esc_html( $it->code ) . '</a></td><td data-l="재고" class="r">' . (int) $it->stock . '</td><td data-l="단가" class="r">' . esc_html( md_inv_num( $it->price ) ) . '</td><td data-l="단위">' . esc_html( $it->unit ) . '</td><td data-l="기록" class="r">' . (int) md_inv_item_refs( $it->id ) . '</td><td data-l="상태">' . ( $it->active ? '사용' : '<span class="iv-muted">숨김</span>' ) . '</td>';
			echo '<td class="iv-td-act">';
			if ( (int) $it->id !== (int) $keep->id ) {
				echo '<form method="post" class="iv-inline-form" data-confirm="' . esc_attr( $it->code . ' 을(를) ' . $keep->code . ' 에 합칠까요? ' . $it->code . ' 은(는) 지워집니다.' ) . '">';
				md_inv_hidden( 'item_merge' );
				echo '<input type="hidden" name="keep" value="' . (int) $keep->id . '"><input type="hidden" name="drop" value="' . (int) $it->id . '"><button class="iv-btn iv-btn--ghost iv-btn--xs">' . esc_html( $keep->code ) . '에 합치기</button></form>';
			} else {
				echo '<span class="iv-tag">남길 품목</span>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div></section>';
	}
}
