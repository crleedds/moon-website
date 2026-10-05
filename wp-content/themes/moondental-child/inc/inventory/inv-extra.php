<?php
/**
 * v6.1 · 재료실 — 업체 단가표 올리기 · 거래명세서 자동 대조 · 출고 바코드 확인(GS1 · LOT) · 신청 처리 이메일 알림
 * (원장 지시 2026-10-05)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 공통 — 올린 표 읽기 (엑셀 · CSV) · 이름 맞추기
 * ============================================================ */

/** 올린 파일 → 첫 시트의 줄들 [ [칸, 칸, ...], ... ] */
function md_inv_upload_rows( $field ) {
	if ( empty( $_FILES[ $field ]['tmp_name'] ) || ! is_uploaded_file( $_FILES[ $field ]['tmp_name'] ) ) { return new WP_Error( 'file', '파일을 골라 주세요.' ); }
	if ( (int) $_FILES[ $field ]['size'] > 10 * MB_IN_BYTES ) { return new WP_Error( 'big', '파일이 너무 큽니다 (10MB 까지).' ); }
	$tmp  = $_FILES[ $field ]['tmp_name'];
	$name = strtolower( (string) $_FILES[ $field ]['name'] );
	if ( preg_match( '/\.(csv|txt)$/', $name ) ) {
		$raw = (string) file_get_contents( $tmp );
		if ( ! mb_check_encoding( $raw, 'UTF-8' ) ) { $raw = mb_convert_encoding( $raw, 'UTF-8', 'CP949' ); }
		$raw  = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );
		$rows = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) { $rows[] = str_getcsv( $line ); }
		return $rows;
	}
	if ( ! preg_match( '/\.xlsx$/', $name ) ) { return new WP_Error( 'type', '엑셀(.xlsx) 또는 CSV 파일만 올릴 수 있습니다. 옛 .xls 는 엑셀에서 「다른 이름으로 저장 › .xlsx」로 바꿔 주세요.' ); }
	$book = md_inv_xlsx_read( $tmp );
	if ( is_wp_error( $book ) ) { return $book; }
	$best = array();
	foreach ( $book as $rows ) { if ( count( $rows ) > count( $best ) ) { $best = $rows; } } /* 줄이 가장 많은 시트 */
	return $best;
}

/** 머리글 줄과 칸 위치 찾기 — $want = [ 키 => 정규식 ] */
function md_inv_find_head( $rows, $want, $need ) {
	foreach ( array_slice( $rows, 0, 15, true ) as $ri => $r ) {
		$pos = array();
		foreach ( (array) $r as $ci => $cell ) {
			$c = preg_replace( '/\s+/u', '', (string) $cell );
			if ( '' === $c ) { continue; }
			foreach ( $want as $k => $re ) { if ( ! isset( $pos[ $k ] ) && preg_match( $re, $c ) ) { $pos[ $k ] = $ci; break; } }
		}
		$ok = true;
		foreach ( $need as $k ) { if ( ! isset( $pos[ $k ] ) ) { $ok = false; } }
		if ( $ok ) { return array( $ri, $pos ); }
	}
	return null;
}

function md_inv_money( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) { return null; }
	$neg = (bool) preg_match( '/^\(.*\)$|^-|^−|^△|^▲/u', $v );
	$n   = preg_replace( '/[^0-9.]/', '', $v );
	if ( '' === $n || '.' === $n ) { return null; }
	$n = (int) round( (float) $n );
	return $neg ? -$n : $n;
}

/** 이름 비교용 — 띄어쓰기 · 기호 없이 소문자 */
function md_inv_name_norm( $s ) {
	$s = mb_strtolower( (string) $s, 'UTF-8' );
	return preg_replace( '/[^\p{L}\p{N}]+/u', '', $s );
}

function md_inv_name_tokens( $s ) {
	$s = mb_strtolower( (string) $s, 'UTF-8' );
	$t = preg_split( '/[^\p{L}\p{N}]+/u', $s, -1, PREG_SPLIT_NO_EMPTY );
	return array_values( array_unique( $t ) );
}

/** 두 이름이 얼마나 같은가 0~100 */
function md_inv_name_score( $a, $b ) {
	$na = md_inv_name_norm( $a ); $nb = md_inv_name_norm( $b );
	if ( '' === $na || '' === $nb ) { return 0; }
	if ( $na === $nb ) { return 100; }
	if ( mb_strlen( $na ) >= 4 && mb_strlen( $nb ) >= 4 && ( false !== mb_strpos( $na, $nb ) || false !== mb_strpos( $nb, $na ) ) ) { return 85; }
	$ta = md_inv_name_tokens( $a ); $tb = md_inv_name_tokens( $b );
	if ( ! $ta || ! $tb ) { return 0; }
	$in = count( array_intersect( $ta, $tb ) );
	$un = count( array_unique( array_merge( $ta, $tb ) ) );
	return (int) round( 80 * $in / max( 1, $un ) );
}

/* ============================================================
 * 1 · 업체 단가표 올리기
 * ============================================================ */

function md_inv_pricelist_parse( $rows, $vendor_id, $vat ) {
	$h = md_inv_find_head( $rows, array(
		'code'  => '/^(코드|품목코드|제품코드|상품코드|code|바코드|barcode|ref|품번|모델)/iu',
		'name'  => '/(품명|품목|제품|상품|규격|item|name|description)/iu',
		'price' => '/(단가|가격|공급가|판매가|price|금액)/iu',
	), array( 'name', 'price' ) );
	if ( ! $h ) { return new WP_Error( 'head', '머리글(품명 · 단가 칸)을 찾지 못했습니다. 첫 줄 근처에 「품명」 「단가」 같은 제목이 있어야 합니다.' ); }
	list( $hr, $pos ) = $h;
	$items = md_inv_items( array( 'vendor' => (int) $vendor_id, 'active' => -1 ) );
	$out   = array();
	foreach ( array_slice( $rows, $hr + 1 ) as $r ) {
		$name  = trim( (string) ( $r[ $pos['name'] ] ?? '' ) );
		$price = md_inv_money( $r[ $pos['price'] ] ?? '' );
		if ( '' === $name || null === $price || $price <= 0 ) { continue; }
		if ( 'add' === $vat ) { $price = (int) round( $price * 1.1 ); } elseif ( 'sub' === $vat ) { $price = (int) round( $price / 1.1 ); }
		$code = isset( $pos['code'] ) ? trim( (string) ( $r[ $pos['code'] ] ?? '' ) ) : '';
		$cands = array();
		foreach ( $items as $it ) {
			$sc = ( '' !== $code && ( (string) $it->barcode === $code || (string) $it->code === $code ) ) ? 100 : md_inv_name_score( $name, $it->name );
			if ( $sc >= 40 ) { $cands[] = array( 'id' => (int) $it->id, 'name' => $it->name, 'price' => (int) $it->price, 'score' => $sc ); }
		}
		usort( $cands, function ( $a, $b ) { return $b['score'] - $a['score']; } );
		$out[] = array( 'name' => mb_substr( $name, 0, 200 ), 'code' => $code, 'price' => $price, 'cands' => array_slice( $cands, 0, 5 ) );
	}
	if ( ! $out ) { return new WP_Error( 'empty', '단가가 있는 줄을 찾지 못했습니다.' ); }
	/* 한 품목에 단가표 여러 줄이 붙으면 가장 비슷한 줄 하나만 미리 고른다 */
	$best = array();
	foreach ( $out as $i => $row ) {
		if ( ! $row['cands'] || $row['cands'][0]['score'] < 60 ) { continue; }
		$id = $row['cands'][0]['id'];
		if ( ! isset( $best[ $id ] ) || $out[ $best[ $id ] ]['cands'][0]['score'] < $row['cands'][0]['score'] ) { $best[ $id ] = $i; }
	}
	foreach ( $out as $i => &$row ) {
		$row['pick'] = 0;
		if ( $row['cands'] && $row['cands'][0]['score'] >= 60 && $best[ $row['cands'][0]['id'] ] === $i ) { $row['pick'] = $row['cands'][0]['id']; }
	}
	unset( $row );
	return $out;
}

function md_inv_act_pricelist_upload() {
	$vid = (int) md_inv_p( 'vendor_id' );
	$back = md_inv_url( array( 'iv' => 'pricelist', 'ivd' => $vid ) );
	if ( ! md_inv_vendor( $vid ) ) { md_inv_go( 'err', '업체를 골라 주세요.', $back ); }
	$rows = md_inv_upload_rows( 'file' );
	if ( is_wp_error( $rows ) ) { md_inv_go( 'err', $rows->get_error_message(), $back ); }
	$vat = in_array( md_inv_p( 'vat' ), array( 'add', 'sub' ), true ) ? md_inv_p( 'vat' ) : '';
	$res = md_inv_pricelist_parse( $rows, $vid, $vat );
	if ( is_wp_error( $res ) ) { md_inv_go( 'err', $res->get_error_message(), $back ); }
	set_transient( 'md_inv_pl_' . get_current_user_id(), array( 'vendor' => $vid, 'file' => sanitize_file_name( (string) $_FILES['file']['name'] ), 'vat' => $vat, 'rows' => $res ), 2 * HOUR_IN_SECONDS );
	md_inv_go( 'ok', count( $res ) . '줄을 읽었습니다. 맞춘 결과를 확인하고 「고른 단가 저장」을 눌러 주세요.', add_query_arg( 'step', 'preview', $back ) );
}

function md_inv_act_pricelist_apply() {
	$pl  = get_transient( 'md_inv_pl_' . get_current_user_id() );
	$vid = $pl ? (int) $pl['vendor'] : 0;
	$back = md_inv_url( array( 'iv' => 'pricelist', 'ivd' => $vid ) );
	if ( ! $pl ) { md_inv_go( 'err', '올린 단가표가 만료되었습니다. 다시 올려 주세요.', $back ); }
	$pick = (array) md_inv_p( 'pick', array() );
	$on   = array_flip( array_map( 'intval', (array) md_inv_p( 'on', array() ) ) );
	$n = 0; $same = 0; $seen = array();
	global $wpdb;
	foreach ( $pl['rows'] as $i => $row ) {
		if ( ! isset( $on[ $i ] ) ) { continue; }
		$id = isset( $pick[ $i ] ) ? (int) $pick[ $i ] : 0;
		if ( ! $id || isset( $seen[ $id ] ) ) { continue; }
		$it = md_inv_item( $id );
		if ( ! $it ) { continue; }
		$seen[ $id ] = 1;
		if ( (int) $it->price === (int) $row['price'] ) { $same++; continue; }
		$wpdb->update( md_inv_t( 'item' ), array( 'price' => (int) $row['price'], 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
		md_inv_price_log( $id, (int) $it->price, (int) $row['price'], '단가표', true );
		$n++;
	}
	md_inv_log( '단가표 반영', md_inv_vendor_name( $vid ) . ' · ' . $n . '개 품목 · ' . $pl['file'] );
	delete_transient( 'md_inv_pl_' . get_current_user_id() );
	md_inv_go( 'ok', $n . '개 품목의 단가를 바꿨습니다.' . ( $same ? ' (' . $same . '개는 단가가 같아 그대로)' : '' ) . ' 「단가 변동」에 「단가표」로 남았습니다.', $back );
}

function md_inv_view_pricelist() {
	$vid = (int) md_inv_get( 'ivd', 0 );
	$pl  = 'preview' === md_inv_get( 'step' ) ? get_transient( 'md_inv_pl_' . get_current_user_id() ) : null;
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'prepaid', 'ivd' => $vid ) ) ); ?>">← 선납</a></p>
	<h2 class="iv-h2">업체 단가표 올리기</h2>
	<p class="iv-help">업체가 보내 준 단가표(엑셀 .xlsx 또는 CSV)를 올리면 품목 이름(코드 · 바코드 칸이 있으면 그것도)으로 우리 품목과 맞춥니다. 맞춘 결과를 확인한 뒤 저장해야 바뀝니다.</p>
	<form method="post" enctype="multipart/form-data" class="iv-panel iv-pl-form">
		<?php md_inv_hidden( 'pricelist_upload' ); ?>
		<div class="iv-grid2">
			<label class="iv-f"><span>업체 <em>*</em></span><?php md_inv_vendor_select( 'vendor_id', $vid, true ); ?></label>
			<label class="iv-f"><span>단가표 파일 <em>*</em></span><input class="iv-input" type="file" name="file" accept=".xlsx,.csv" required></label>
		</div>
		<fieldset class="iv-f"><span>단가표 금액이</span>
			<label class="iv-check"><input type="radio" name="vat" value="" checked> 우리 단가와 같은 기준 (그대로)</label>
			<label class="iv-check"><input type="radio" name="vat" value="add"> 부가세 빠진 금액 → 10% 더해서 넣기</label>
			<label class="iv-check"><input type="radio" name="vat" value="sub"> 부가세 포함 금액 → 10% 빼서 넣기</label>
		</fieldset>
		<button class="iv-btn iv-btn--primary">읽어서 맞춰 보기</button>
	</form>
	<?php
	if ( ! $pl ) { return; }
	$rows = $pl['rows'];
	$n_ok = 0; foreach ( $rows as $r ) { if ( $r['pick'] ) { $n_ok++; } }
	?>
	<h3 class="iv-h3"><?php echo esc_html( md_inv_vendor_name( $pl['vendor'] ) . ' · ' . $pl['file'] ); ?> <small><?php echo count( $rows ); ?>줄 · 맞춘 것 <?php echo (int) $n_ok; ?></small></h3>
	<p class="iv-help">「우리 품목」을 바꿔 고를 수 있습니다. 체크한 줄만 저장합니다. <span class="iv-pl-key"><b class="iv-pl-s3">같음</b> 이름이 같음 · <b class="iv-pl-s2">비슷함</b> 확인 필요 · <b class="iv-pl-s1">못 찾음</b></span></p>
	<form method="post" class="iv-pl-apply">
		<?php md_inv_hidden( 'pricelist_apply' ); ?>
		<div class="iv-toolbar"><label class="iv-check"><input type="checkbox" data-checkall="on[]"> 모두</label><button class="iv-btn iv-btn--primary">고른 단가 저장</button></div>
		<div class="iv-table-wrap"><table class="iv-table iv-table--pl"><thead><tr><th></th><th>단가표 품명</th><th>우리 품목</th><th class="r">지금 단가</th><th class="r">새 단가</th><th class="r">변동</th></tr></thead><tbody>
		<?php foreach ( $rows as $i => $r ) :
			$top = $r['cands'] ? $r['cands'][0] : null;
			$cls = ! $top || $top['score'] < 60 ? 's1' : ( $top['score'] >= 85 ? 's3' : 's2' );
			$cur = 0; foreach ( $r['cands'] as $c ) { if ( $c['id'] === (int) $r['pick'] ) { $cur = $c['price']; } }
			?>
			<tr class="iv-pl iv-pl--<?php echo esc_attr( $cls ); ?>">
				<td><input type="checkbox" name="on[]" value="<?php echo (int) $i; ?>"<?php checked( $r['pick'] && $cur !== (int) $r['price'] ); ?><?php disabled( ! $r['cands'] ); ?> aria-label="이 줄 저장"></td>
				<td data-l="단가표 품명"><?php echo esc_html( $r['name'] ); ?><?php echo '' !== $r['code'] ? ' <small>' . esc_html( $r['code'] ) . '</small>' : ''; ?></td>
				<td data-l="우리 품목">
					<?php if ( $r['cands'] ) : ?>
						<select class="iv-input iv-input--sm" name="pick[<?php echo (int) $i; ?>]">
							<option value="0">— 고르지 않음 —</option>
							<?php foreach ( $r['cands'] as $c ) : ?><option value="<?php echo (int) $c['id']; ?>"<?php selected( (int) $r['pick'], $c['id'] ); ?>><?php echo esc_html( $c['name'] . ' (' . md_inv_num( $c['price'] ) . '원)' ); ?></option><?php endforeach; ?>
						</select>
					<?php else : ?><span class="iv-muted">못 찾음 — 이름이 많이 다르면 품목을 먼저 등록하세요</span><?php endif; ?>
				</td>
				<td data-l="지금 단가" class="r"><?php echo $r['pick'] ? esc_html( md_inv_num( $cur ) ) : ''; ?></td>
				<td data-l="새 단가" class="r"><b><?php echo esc_html( md_inv_num( $r['price'] ) ); ?></b></td>
				<td data-l="변동" class="r"><?php echo $r['pick'] && $cur ? esc_html( ( $r['price'] > $cur ? '+' : '' ) . round( ( $r['price'] - $cur ) / $cur * 100, 1 ) . '%' ) : ( $r['pick'] ? '<small>새로</small>' : '' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody></table></div>
		<div class="iv-toolbar"><button class="iv-btn iv-btn--primary">고른 단가 저장</button></div>
	</form>
	<?php
}

/* ============================================================
 * 2 · 업체 거래명세서 자동 대조
 * ============================================================ */

function md_inv_statement_parse( $rows ) {
	$h = md_inv_find_head( $rows, array(
		'date'  => '/^(일자|날짜|거래일|거래일자|출고일|매출일|date)/iu',
		'name'  => '/(품명|품목|제품|상품|적요|내용|item|description)/iu',
		'qty'   => '/^(수량|qty)/iu',
		'price' => '/^(단가|price)/iu',
		'amt'   => '/(합계|금액|공급가액|청구|amount|total)/iu',
		'inc'   => '/(입금|수금|선수금)/iu',
	), array( 'date', 'name' ) );
	if ( ! $h ) { return new WP_Error( 'head', '머리글(일자 · 품명 · 금액 칸)을 찾지 못했습니다.' ); }
	list( $hr, $pos ) = $h;
	if ( ! isset( $pos['amt'] ) && ! isset( $pos['price'] ) ) { return new WP_Error( 'head', '금액(또는 단가) 칸을 찾지 못했습니다.' ); }
	$out = array();
	foreach ( array_slice( $rows, $hr + 1 ) as $r ) {
		$d = trim( (string) ( $r[ $pos['date'] ] ?? '' ) );
		if ( '' === $d ) { continue; }
		$date = is_numeric( $d ) && (float) $d > 30000 ? substr( md_inv_xl_date( $d ), 0, 10 ) : ( ( $ts = strtotime( str_replace( array( '.', '/' ), '-', rtrim( $d, '.' ) ) ) ) ? date( 'Y-m-d', $ts ) : '' );
		if ( '' === $date ) { continue; }
		$name = trim( (string) ( $r[ $pos['name'] ] ?? '' ) );
		$qty  = isset( $pos['qty'] ) ? md_inv_money( $r[ $pos['qty'] ] ?? '' ) : null;
		$amt  = isset( $pos['amt'] ) ? md_inv_money( $r[ $pos['amt'] ] ?? '' ) : null;
		if ( null === $amt && isset( $pos['price'] ) ) { $p = md_inv_money( $r[ $pos['price'] ] ?? '' ); $amt = null === $p ? null : $p * ( $qty ? $qty : 1 ); }
		$inc  = isset( $pos['inc'] ) ? md_inv_money( $r[ $pos['inc'] ] ?? '' ) : null;
		if ( preg_match( '/(합계|소계|총계|이월|잔액)/u', $name ) && ! $qty ) { continue; } /* 합계 · 이월 줄 */
		if ( $inc ) { $out[] = array( 'date' => $date, 'kind' => 'deposit', 'name' => '' !== $name ? $name : '입금', 'qty' => 0, 'amt' => abs( $inc ) ); continue; }
		if ( null === $amt || 0 === $amt ) { continue; }
		$kind = preg_match( '/(입금|수금|선수금|선납금)/u', $name ) ? 'deposit' : ( ( $amt < 0 || ( $qty && $qty < 0 ) || preg_match( '/반품/u', $name ) ) ? 'return' : 'in' );
		$out[] = array( 'date' => $date, 'kind' => $kind, 'name' => $name, 'qty' => $qty ? abs( $qty ) : 0, 'amt' => abs( $amt ) );
	}
	if ( ! $out ) { return new WP_Error( 'empty', '거래 줄을 찾지 못했습니다.' ); }
	return $out;
}

/** 금액이 같은가 — 1원 반올림 차이 · 부가세 10% 차이 */
function md_inv_amt_rel( $a, $b ) {
	if ( abs( $a - $b ) <= max( 10, (int) round( $b * 0.002 ) ) ) { return 'same'; }
	if ( $b && abs( $a - $b * 1.1 ) <= max( 10, $b * 0.003 ) ) { return 'vat'; } /* 업체 금액이 부가세 포함 */
	if ( $a && abs( $b - $a * 1.1 ) <= max( 10, $a * 0.003 ) ) { return 'vat'; }
	return '';
}

function md_inv_statement_match( $vendor_id, $lines ) {
	$dates = array_column( $lines, 'date' );
	$from  = date( 'Y-m-d', strtotime( min( $dates ) . ' -7 days' ) );
	$to    = date( 'Y-m-d', strtotime( max( $dates ) . ' +7 days' ) );
	$pb    = md_inv_passbook( $vendor_id, $from, $to );
	$ours  = array();
	foreach ( $pb['rows'] as $e ) {
		if ( in_array( $e->kind, array( 'free', 'adjust' ), true ) ) { continue; }
		$k = 'refund' === $e->kind ? 'deposit' : $e->kind;
		$ours[] = (object) array( 'date' => $e->date, 'kind' => $k, 'name' => trim( $e->item . ( $e->qty ? ' ' . $e->qty . '개' : '' ) ), 'item' => $e->item, 'qty' => $e->qty, 'amt' => $e->plus + $e->minus, 'used' => false );
	}
	$sdays = function ( $a, $b ) { return abs( ( strtotime( $a ) - strtotime( $b ) ) / DAY_IN_SECONDS ); };
	$res = array( 'match' => array(), 'diff' => array(), 'theirs' => array(), 'ours' => array() );
	$left = array();
	/* 1차 — 금액이 같고 날짜가 가까운 것 */
	foreach ( $lines as $s ) {
		$best = null; $bs = -1;
		foreach ( $ours as $o ) {
			if ( $o->used || $o->kind !== $s['kind'] || $sdays( $o->date, $s['date'] ) > 7 ) { continue; }
			$rel = md_inv_amt_rel( $s['amt'], $o->amt );
			if ( ! $rel ) { continue; }
			$sc = ( 'same' === $rel ? 50 : 30 ) + ( 'deposit' === $s['kind'] ? 30 : md_inv_name_score( $s['name'], $o->item ) * 0.3 ) - $sdays( $o->date, $s['date'] ) * 2;
			if ( $sc > $bs ) { $bs = $sc; $best = $o; $brel = $rel; }
		}
		if ( $best ) { $best->used = true; $res['match'][] = array( 's' => $s, 'o' => $best, 'note' => 'vat' === $brel ? '부가세 10% 차이 — 업체는 부가세 포함 금액' : '' ); }
		else { $left[] = $s; }
	}
	/* 2차 — 같은 품목(이름) · 날짜 가까운데 금액이 다른 것 */
	foreach ( $left as $s ) {
		$best = null; $bs = 0;
		foreach ( $ours as $o ) {
			if ( $o->used || $o->kind !== $s['kind'] || 'deposit' === $s['kind'] || $sdays( $o->date, $s['date'] ) > 7 ) { continue; }
			$sc = md_inv_name_score( $s['name'], $o->item );
			if ( $sc >= 50 && $sc > $bs ) { $bs = $sc; $best = $o; }
		}
		if ( $best ) { $best->used = true; $res['diff'][] = array( 's' => $s, 'o' => $best, 'note' => '차이 ' . md_inv_num( $s['amt'] - $best->amt ) . '원' . ( $s['qty'] && $best->qty && (int) $s['qty'] !== (int) $best->qty ? ' · 수량 ' . $s['qty'] . ' / ' . $best->qty : '' ) ); }
		else { $res['theirs'][] = $s; }
	}
	foreach ( $ours as $o ) {
		if ( $o->used ) { continue; }
		if ( $o->date < min( $dates ) || $o->date > max( $dates ) ) { continue; } /* 명세서 기간 밖은 빼고 */
		$res['ours'][] = $o;
	}
	$res['range'] = array( min( $dates ), max( $dates ) );
	$res['sum_theirs'] = array_sum( array_map( function ( $s ) { return ( 'in' === $s['kind'] ? -1 : 1 ) * $s['amt']; }, $lines ) );
	return $res;
}

function md_inv_act_statement_upload() {
	$vid  = (int) md_inv_p( 'vendor_id' );
	$back = md_inv_url( array( 'iv' => 'prepaid', 'ivd' => $vid ) ) . '#iv-stmt';
	$rows = md_inv_upload_rows( 'file' );
	if ( is_wp_error( $rows ) ) { md_inv_go( 'err', $rows->get_error_message(), $back ); }
	$lines = md_inv_statement_parse( $rows );
	if ( is_wp_error( $lines ) ) { md_inv_go( 'err', $lines->get_error_message(), $back ); }
	$res = md_inv_statement_match( $vid, $lines );
	$res['file'] = sanitize_file_name( (string) $_FILES['file']['name'] );
	set_transient( 'md_inv_stmt_' . get_current_user_id() . '_' . $vid, $res, DAY_IN_SECONDS );
	md_inv_log( '거래명세서 대조', md_inv_vendor_name( $vid ) . ' · ' . $res['file'] . ' · 일치 ' . count( $res['match'] ) . ' · 금액 다름 ' . count( $res['diff'] ) . ' · 업체에만 ' . count( $res['theirs'] ) . ' · 우리에만 ' . count( $res['ours'] ) );
	$bad = count( $res['diff'] ) + count( $res['theirs'] ) + count( $res['ours'] );
	md_inv_go( $bad ? 'warn' : 'ok', count( $lines ) . '줄 대조 — ' . ( $bad ? '맞지 않는 줄 ' . $bad . '개를 아래에서 확인해 주세요.' : '모두 맞습니다.' ), $back );
}

function md_inv_prepaid_statement_section( $vid ) {
	$res = get_transient( 'md_inv_stmt_' . get_current_user_id() . '_' . $vid );
	$k   = array( 'in' => '입고', 'return' => '반품', 'deposit' => '입금' );
	$line = function ( $s ) use ( $k ) { return $s['date'] . ' · ' . $k[ $s['kind'] ] . ' · ' . $s['name'] . ( $s['qty'] ? ' ' . $s['qty'] . '개' : '' ) . ' · ' . md_inv_num( $s['amt'] ) . '원'; };
	$our  = function ( $o ) use ( $k ) { return $o->date . ' · ' . $k[ $o->kind ] . ' · ' . $o->name . ' · ' . md_inv_num( $o->amt ) . '원'; };
	?>
	<section class="iv-panel" id="iv-stmt">
		<h3 class="iv-h3">거래명세서 자동 대조</h3>
		<p class="iv-help">업체가 보낸 거래명세서(엑셀 · CSV)를 올리면 우리 기록과 한 줄씩 짝을 짓습니다 — 날짜(±7일) · 금액(부가세 10% 차이도 알아봄) · 품명으로.</p>
		<form method="post" enctype="multipart/form-data" class="iv-inline-form iv-recon-form">
			<?php md_inv_hidden( 'statement_upload' ); ?><input type="hidden" name="vendor_id" value="<?php echo (int) $vid; ?>">
			<label class="iv-f iv-f--grow"><span>거래명세서 파일</span><input class="iv-input" type="file" name="file" accept=".xlsx,.csv" required></label>
			<button class="iv-btn iv-btn--primary">대조</button>
		</form>
		<?php if ( $res ) : ?>
			<p class="iv-stmt-sum"><b><?php echo esc_html( $res['file'] ); ?></b> · <?php echo esc_html( $res['range'][0] . ' ~ ' . $res['range'][1] ); ?> ·
				<span class="iv-ok">일치 <?php echo count( $res['match'] ); ?></span> ·
				<span class="iv-danger">금액 다름 <?php echo count( $res['diff'] ); ?></span> ·
				<span class="iv-danger">업체에만 <?php echo count( $res['theirs'] ); ?></span> ·
				<span class="iv-danger">우리에만 <?php echo count( $res['ours'] ); ?></span></p>
			<?php if ( $res['diff'] ) : ?><h4 class="iv-h4">금액이 다른 줄</h4><ul class="iv-stmt iv-stmt--diff"><?php foreach ( $res['diff'] as $m ) : ?><li><b>업체</b> <?php echo esc_html( $line( $m['s'] ) ); ?><br><b>우리</b> <?php echo esc_html( $our( $m['o'] ) ); ?> <em><?php echo esc_html( $m['note'] ); ?></em></li><?php endforeach; ?></ul><?php endif; ?>
			<?php if ( $res['theirs'] ) : ?><h4 class="iv-h4">업체에만 있는 줄 <small>— 우리가 입고 · 입금 기록을 빠뜨렸을 수 있습니다</small></h4><ul class="iv-stmt iv-stmt--theirs"><?php foreach ( $res['theirs'] as $s ) : ?><li><?php echo esc_html( $line( $s ) ); ?></li><?php endforeach; ?></ul><?php endif; ?>
			<?php if ( $res['ours'] ) : ?><h4 class="iv-h4">우리에만 있는 줄 <small>— 업체 명세서에 빠졌거나 날짜가 크게 다를 수 있습니다</small></h4><ul class="iv-stmt iv-stmt--ours"><?php foreach ( $res['ours'] as $o ) : ?><li><?php echo esc_html( $our( $o ) ); ?></li><?php endforeach; ?></ul><?php endif; ?>
			<?php if ( $res['match'] ) : ?><details><summary class="iv-h4">일치 <?php echo count( $res['match'] ); ?>줄 보기</summary><ul class="iv-stmt iv-stmt--ok"><?php foreach ( $res['match'] as $m ) : ?><li><?php echo esc_html( $line( $m['s'] ) ); ?><?php echo $m['note'] ? ' <em>' . esc_html( $m['note'] ) . '</em>' : ''; ?></li><?php endforeach; ?></ul></details><?php endif; ?>
		<?php endif; ?>
	</section>
	<?php
}

/* ============================================================
 * 3 · 출고 바코드 확인 — 품목별 바코드 지도
 * ============================================================ */

function md_inv_bc_map_script() {
	static $done = false;
	if ( $done ) { return; }
	$done = true;
	global $wpdb;
	$map = array();
	foreach ( (array) $wpdb->get_results( 'SELECT id, name, barcode FROM ' . md_inv_t( 'item' ) . " WHERE barcode <> ''" ) as $r ) { $map[ (int) $r->id ] = array( (string) $r->barcode, (string) $r->name ); }
	echo '<script type="application/json" id="iv-bc-map">' . wp_json_encode( (object) $map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
}

/** 출고 · 입고 창에 「바코드로 확인」 */
function md_inv_verify_fields() {
	echo '<div class="iv-verify"><button type="button" class="iv-btn iv-btn--ghost iv-btn--sm" data-verify>' . md_inv_icon( 'scan', 16 ) . '바코드로 확인</button><span class="iv-verify__msg" data-verify-msg role="status"></span></div>';
}

/* ============================================================
 * 4 · 신청 처리 이메일 알림
 * ============================================================ */

/** 알림을 모았다가 요청이 끝날 때 사람마다 한 통으로 보낸다 */
function md_inv_notify_req( $req_id, $what, $detail = array() ) {
	if ( ! md_inv_set( 'notify_staff' ) ) { return; }
	$r = md_inv_req( $req_id );
	if ( ! $r || ! (int) $r->user_id ) { return; }
	$uid = (int) $r->user_id;
	if ( $uid === get_current_user_id() ) { return; } /* 내가 내 신청을 처리 */
	$u = get_userdata( $uid );
	if ( ! $u || ! is_email( $u->user_email ) ) { return; }
	if ( function_exists( 'md_acc_is_personal_user' ) && ! md_acc_is_personal_user( $u ) ) { return; }
	if ( get_user_meta( $uid, 'md_inv_mail_off', true ) ) { return; }
	$GLOBALS['md_inv_notify_q'][ $uid ][] = array( 'what' => $what, 'name' => $r->item_id ? $r->name : $r->custom_name, 'unit' => $r->unit, 'qty' => (int) $r->qty, 'd' => $detail );
	static $hooked = false;
	if ( ! $hooked ) { $hooked = true; add_action( 'shutdown', 'md_inv_notify_flush' ); }
}

function md_inv_notify_flush() {
	$q = isset( $GLOBALS['md_inv_notify_q'] ) ? $GLOBALS['md_inv_notify_q'] : array();
	$GLOBALS['md_inv_notify_q'] = array();
	$label = array( 'done' => '출고', 'rejected' => '반려', 'ordered' => '주문함', 'completed' => '처리 완료', 'cancelled' => '취소' );
	foreach ( $q as $uid => $list ) {
		$u = get_userdata( $uid );
		if ( ! $u ) { continue; }
		$count = array();
		$lines = array();
		foreach ( $list as $x ) {
			$count[ $label[ $x['what'] ] ] = ( isset( $count[ $label[ $x['what'] ] ] ) ? $count[ $label[ $x['what'] ] ] : 0 ) + 1;
			$d = $x['d'];
			$u_ = '' !== (string) $x['unit'] ? $x['unit'] : '개';
			switch ( $x['what'] ) {
				case 'done':
					$lines[] = '✓ 출고 — ' . $x['name'] . ' ' . (int) $d['qty'] . $u_ . ( (int) $d['qty'] < $x['qty'] ? ' (신청 ' . $x['qty'] . $u_ . ' 중, 남은 ' . ( $x['qty'] - (int) $d['qty'] ) . $u_ . '는 대기)' : '' ) . ( ! empty( $d['receiver'] ) ? ' · 받은 사람 ' . $d['receiver'] : '' ) . ( ! empty( $d['note'] ) ? ' · ' . $d['note'] : '' );
					break;
				case 'rejected':
					$lines[] = '✕ 반려 — ' . $x['name'] . ' ' . $x['qty'] . $u_ . ( ! empty( $d['reason'] ) ? ' · 사유: ' . $d['reason'] : '' );
					break;
				case 'ordered':
					$lines[] = '🛒 주문함 — ' . $x['name'] . ' ' . $x['qty'] . $u_ . ' · 재고가 없어 업체에 주문했습니다. 들어오면 출고해 드립니다.';
					break;
				case 'completed':
					$lines[] = '✓ 처리 완료 — ' . $x['name'] . ( ! empty( $d['note'] ) ? ' · ' . $d['note'] : '' );
					break;
				case 'cancelled':
					$lines[] = '– 취소 — ' . $x['name'] . ' ' . $x['qty'] . $u_ . ' (관리자가 취소)';
					break;
			}
		}
		$sum = array(); foreach ( $count as $k => $n ) { $sum[] = $k . ' ' . $n . '건'; }
		$body = $u->display_name . " 님, 재료실 신청 처리 결과입니다.\n\n" . implode( "\n", $lines )
			. "\n\n처리: " . md_inv_me() . ' · ' . current_time( 'Y-m-d H:i' )
			. "\n내 신청 내역: " . md_inv_url( array( 'iv' => 'mine' ) )
			. "\n\n(이 알림을 그만 받으려면 재료실 › 내역 맨 아래에서 끌 수 있습니다)";
		wp_mail( $u->user_email, '[문치과병원 재료실] 신청 처리 안내 — ' . implode( ' · ', $sum ), $body );
	}
}

/** 직원 — 알림 켜고 끄기 (내역 화면) */
function md_inv_act_mail_pref() {
	if ( ! function_exists( 'md_inv_is_personal' ) || ! md_inv_is_personal() ) { md_inv_go( 'err', '개인 계정만 바꿀 수 있습니다.' ); }
	$off = md_inv_p( 'mail_on' ) ? 0 : 1;
	if ( $off ) { update_user_meta( get_current_user_id(), 'md_inv_mail_off', 1 ); } else { delete_user_meta( get_current_user_id(), 'md_inv_mail_off' ); }
	md_inv_go( 'ok', $off ? '처리 알림 메일을 껐습니다.' : '신청이 처리되면 이메일로 알려 드립니다.', md_inv_url( array( 'iv' => 'mine' ) ) );
}

function md_inv_mail_pref_box() {
	if ( ! md_inv_is_personal() || ! md_inv_set( 'notify_staff' ) ) { return; }
	$u   = wp_get_current_user();
	$off = (bool) get_user_meta( $u->ID, 'md_inv_mail_off', true );
	?>
	<form method="post" class="iv-mailpref">
		<?php md_inv_hidden( 'mail_pref' ); ?>
		<input type="hidden" name="mail_on" value="0">
		<label class="iv-check"><input type="checkbox" name="mail_on" value="1"<?php checked( ! $off ); ?> onchange="this.form.submit()"> 내 신청이 출고 · 반려 · 주문되면 이메일로 알림 <small>(<?php echo esc_html( $u->user_email ? $u->user_email : '이메일 없음 — 내 정보에서 넣어 주세요' ); ?>)</small></label>
		<noscript><button class="iv-btn iv-btn--ghost iv-btn--xs">저장</button></noscript>
	</form>
	<?php
}
