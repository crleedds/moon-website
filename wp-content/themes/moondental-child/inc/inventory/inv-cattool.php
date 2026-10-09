<?php
/**
 * 분류 정리 도우미 — v9.38 (원장 지시 2026-10-09)
 *
 *  분류(결제 방식 › 품목군 › 세부 분류) 세 단계를 한 화면에서 모두 고친다:
 *    추가 · 이름/비고 고치기 · 사용/사용 안 함 · 순서 · 다른 위 분류 아래로 옮기기 · 다른 분류에 합치기 · 삭제
 *  그리고 품목을 골라 원하는 분류로 한꺼번에 옮긴다.
 *  예전(v6.2)의 「이름으로 세부 분류 제안」(엔도 파일 · 버 · 연마 …) 은 원장 지시로 없앴다 — 분류는 사람이 정한다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 처리
 * ============================================================ */

/** 분류의 위 단계들 [level => id] (자기 포함) */
function md_inv_cat_chain( $id ) {
	$out = array();
	$c   = md_inv_cat( $id );
	for ( $i = 0; $c && $i < 3; $i++ ) {
		$out[ (int) $c->level ] = (int) $c->id;
		$c = (int) $c->parent_id ? md_inv_cat( $c->parent_id ) : null;
	}
	ksort( $out );
	return $out;
}

/** md_inv_cats() 는 요청 안에서 캐시된다 — 바꾼 값을 캐시 속 객체에도 반영해 같은 요청의 다음 계산이 옛 위치를 보지 않게 */
function md_inv_cat_cache_set( $id, $field, $val ) {
	foreach ( array( md_inv_cats(), md_inv_cats( true ) ) as $list ) {
		foreach ( $list as $c ) { if ( (int) $c->id === (int) $id ) { $c->$field = $val; } }
	}
}

/** 이 분류(와 그 아래 분류)를 쓰는 품목의 위 단계 칸을 이 분류의 실제 위치에 맞춘다 */
function md_inv_cat_fix_items( $id ) {
	global $wpdb;
	$t = md_inv_t();
	$c = md_inv_cat( $id );
	if ( ! $c ) { return; }
	$ch = md_inv_cat_chain( $id );
	$lv = (int) $c->level;
	if ( 2 === $lv ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['item']} SET cat1 = %d WHERE cat2 = %d", (int) ( $ch[1] ?? 0 ), (int) $c->id ) );
	}
	if ( 3 === $lv ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$t['item']} SET cat1 = %d, cat2 = %d WHERE cat3 = %d", (int) ( $ch[1] ?? 0 ), (int) ( $ch[2] ?? 0 ), (int) $c->id ) );
	}
	if ( $lv < 3 ) {
		foreach ( md_inv_cats_of( $lv + 1, (int) $c->id, false ) as $k ) { md_inv_cat_fix_items( (int) $k->id ); }
	}
}

/** 분류를 다른 위 분류 아래로 옮긴다 (품목은 따라간다) */
function md_inv_cat_reparent( $id, $parent ) {
	global $wpdb;
	$t = md_inv_t();
	$c = md_inv_cat( $id );
	$p = md_inv_cat( $parent );
	if ( ! $c ) { return new WP_Error( 'gone', '분류를 찾을 수 없습니다.' ); }
	if ( (int) $c->level < 2 ) { return new WP_Error( 'lv', '맨 위 단계는 옮길 곳이 없습니다. 순서만 바꿀 수 있습니다.' ); }
	if ( ! $p || (int) $p->level !== (int) $c->level - 1 ) { return new WP_Error( 'parent', '옮길 곳(한 단계 위 분류)을 골라 주세요.' ); }
	if ( (int) $p->id === (int) $c->parent_id ) { return new WP_Error( 'same', '이미 그 아래에 있습니다.' ); }
	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['cat']} WHERE level = %d AND parent_id = %d AND name = %s", (int) $c->level, (int) $p->id, $c->name ) );
	if ( $dup ) { return new WP_Error( 'dup', '「' . $p->name . '」 아래에 같은 이름 「' . $c->name . '」이 이미 있습니다. 옮기기 대신 「합치기」를 써 주세요.' ); }
	$sort = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(sort_no),0) FROM {$t['cat']} WHERE level = %d AND parent_id = %d", (int) $c->level, (int) $p->id ) ) + 10;
	$wpdb->update( $t['cat'], array( 'parent_id' => (int) $p->id, 'sort_no' => $sort ), array( 'id' => (int) $c->id ) );
	md_inv_cat_cache_set( (int) $c->id, 'parent_id', (int) $p->id );
	md_inv_cat_fix_items( (int) $c->id );
	md_inv_log( '분류 옮김', $c->name . ' → ' . $p->name . ' 아래' );
	return true;
}

/** 분류 $src 를 같은 단계의 $dst 에 합친다 — 품목은 $dst 로, 아래 분류는 $dst 아래로(같은 이름이면 그것끼리 합침), $src 는 지운다 */
function md_inv_cat_merge( $src, $dst ) {
	global $wpdb;
	$t = md_inv_t();
	$s = md_inv_cat( $src );
	$d = md_inv_cat( $dst );
	if ( ! $s || ! $d ) { return new WP_Error( 'gone', '분류를 찾을 수 없습니다.' ); }
	if ( (int) $s->id === (int) $d->id ) { return new WP_Error( 'same', '같은 분류입니다.' ); }
	if ( (int) $s->level !== (int) $d->level ) { return new WP_Error( 'lv', '같은 단계끼리만 합칠 수 있습니다.' ); }
	$lv  = (int) $s->level;
	$col = 'cat' . $lv;
	$n   = (int) $wpdb->query( $wpdb->prepare( "UPDATE {$t['item']} SET $col = %d WHERE $col = %d", (int) $d->id, (int) $s->id ) );
	if ( $lv < 3 ) {
		foreach ( md_inv_cats_of( $lv + 1, (int) $s->id, false ) as $k ) {
			$same = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t['cat']} WHERE level = %d AND parent_id = %d AND name = %s", $lv + 1, (int) $d->id, $k->name ) );
			if ( $same ) {
				md_inv_cat_merge( (int) $k->id, (int) $same );
			} else {
				$sort = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(sort_no),0) FROM {$t['cat']} WHERE level = %d AND parent_id = %d", $lv + 1, (int) $d->id ) ) + 10;
				$wpdb->update( $t['cat'], array( 'parent_id' => (int) $d->id, 'sort_no' => $sort ), array( 'id' => (int) $k->id ) );
				md_inv_cat_cache_set( (int) $k->id, 'parent_id', (int) $d->id );
			}
		}
	}
	md_inv_cat_fix_items( (int) $d->id );
	$wpdb->delete( $t['cat'], array( 'id' => (int) $s->id ) );
	md_inv_cat_cache_set( (int) $s->id, 'parent_id', -1 ); /* 지운 분류는 더 이상 어느 아래에도 없다 */
	md_inv_log( '분류 합침', $s->name . ' → ' . $d->name . ' (품목 ' . $n . '개)' );
	return $n;
}

/** 고른 품목을 분류 경로(cat1 › cat2 › cat3)로 옮긴다 — 0 은 「없음」 */
function md_inv_items_recat( $ids, $c1, $c2, $c3 ) {
	global $wpdb;
	$c1 = (int) $c1; $c2 = (int) $c2; $c3 = (int) $c3;
	if ( $c3 ) { $ch = md_inv_cat_chain( $c3 ); if ( 3 !== count( $ch ) ) { return new WP_Error( 'path', '세부 분류의 위 분류를 찾을 수 없습니다.' ); } $c1 = $ch[1]; $c2 = $ch[2]; }
	elseif ( $c2 ) { $ch = md_inv_cat_chain( $c2 ); if ( ! isset( $ch[1] ) || ! isset( $ch[2] ) ) { return new WP_Error( 'path', '품목군의 위 분류를 찾을 수 없습니다.' ); } $c1 = $ch[1]; }
	elseif ( $c1 ) { $x = md_inv_cat( $c1 ); if ( ! $x || 1 !== (int) $x->level ) { return new WP_Error( 'path', '분류를 다시 골라 주세요.' ); } }
	$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
	if ( ! $ids ) { return new WP_Error( 'none', '옮길 품목을 골라 주세요.' ); }
	$n = 0;
	foreach ( $ids as $iid ) {
		if ( ! md_inv_item( $iid ) ) { continue; }
		$wpdb->update( md_inv_t( 'item' ), array( 'cat1' => $c1, 'cat2' => $c2, 'cat3' => $c3, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $iid ) );
		$n++;
	}
	$path = array_filter( array( $c1 ? md_inv_cat_name( $c1 ) : '', $c2 ? md_inv_cat_name( $c2 ) : '', $c3 ? md_inv_cat_name( $c3 ) : '' ) );
	md_inv_log( '품목 분류 옮김', $n . '개 → ' . ( $path ? implode( ' › ', $path ) : '분류 없음' ) );
	return $n;
}

function md_inv_act_cat_reparent() {
	md_inv_done( md_inv_cat_reparent( (int) md_inv_p( 'id' ), (int) md_inv_p( 'parent_id' ) ), '분류를 옮겼습니다. 그 분류의 품목도 함께 옮겨졌습니다.' );
}

function md_inv_act_cat_merge() {
	$r = md_inv_cat_merge( (int) md_inv_p( 'id' ), (int) md_inv_p( 'into' ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	md_inv_go( 'ok', '분류를 합쳤습니다. 품목 ' . (int) $r . '개가 옮겨졌습니다.' );
}

function md_inv_act_items_recat() {
	$r = md_inv_items_recat( md_inv_p( 'iids', array() ), md_inv_p( 'cat1' ), md_inv_p( 'cat2' ), md_inv_p( 'cat3' ) );
	if ( is_wp_error( $r ) ) { md_inv_go( 'err', $r->get_error_message() ); }
	md_inv_go( 'ok', '품목 ' . (int) $r . '개의 분류를 바꿨습니다.' );
}

/* ============================================================
 * 화면
 * ============================================================ */

function md_inv_view_catsort() {
	$L    = md_inv_settings();
	$use  = md_inv_cat_usage();
	$sel  = md_inv_get( 'ic', '' ); /* 분류 id · u1 · u2 · u3 · all */
	$here = md_inv_url( array( 'iv' => 'catsort', 'ic' => $sel ) );
	$all  = md_inv_items( array( 'active' => -1 ) );
	$cnt  = array( 'u1' => 0, 'u2' => 0, 'u3' => 0 );
	foreach ( $all as $it ) {
		if ( ! (int) $it->cat1 ) { $cnt['u1']++; }
		if ( ! (int) $it->cat2 ) { $cnt['u2']++; }
		if ( ! (int) $it->cat3 ) { $cnt['u3']++; }
	}
	/* 고른 분류의 품목 */
	$rows = array(); $title = '';
	if ( 'all' === $sel ) { $rows = $all; $title = '모든 품목'; }
	elseif ( isset( $cnt[ $sel ] ) ) {
		$lv = (int) substr( $sel, 1 );
		foreach ( $all as $it ) { if ( ! (int) $it->{'cat' . $lv} ) { $rows[] = $it; } }
		$title = $L[ 'label_cat' . $lv ] . ' 없는 품목';
	} elseif ( (int) $sel && ( $sc = md_inv_cat( (int) $sel ) ) ) {
		foreach ( $all as $it ) { if ( (int) $it->{'cat' . (int) $sc->level} === (int) $sc->id ) { $rows[] = $it; } }
		$p = array(); foreach ( md_inv_cat_chain( (int) $sc->id ) as $cid ) { $p[] = md_inv_cat_name( $cid ); }
		$title = implode( ' › ', $p );
	}
	/* 대화상자에서 쓰는 분류 목록 (옮길 곳 · 합칠 곳) */
	$opt = array();
	foreach ( md_inv_cats() as $c ) {
		$p = array(); foreach ( md_inv_cat_chain( (int) $c->id ) as $cid ) { $p[] = md_inv_cat_name( $cid ); }
		$opt[] = array( 'id' => (int) $c->id, 'l' => (int) $c->level, 'p' => (int) $c->parent_id, 'n' => implode( ' › ', $p ) );
	}
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock' ) ) ); ?>">← 재고</a></p>
	<h2 class="iv-h2">분류 정리 도우미</h2>
	<p class="iv-help"><?php echo esc_html( $L['label_cat1'] . ' › ' . $L['label_cat2'] . ' › ' . $L['label_cat3'] ); ?> 세 단계를 여기서 모두 고칩니다 — 추가 · 이름 고치기 · 사용 안 함 · 순서 · 다른 분류 아래로 옮기기 · 다른 분류에 합치기 · 삭제. 분류 이름을 누르면 아래에 그 분류의 품목이 나오고, 품목을 골라 다른 분류로 한꺼번에 옮길 수 있습니다.</p>

	<script type="application/json" id="iv-cat-opts"><?php echo wp_json_encode( $opt, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
	<div class="iv-toolbar iv-toolbar--actions">
		<button type="button" class="iv-btn iv-btn--primary" data-dlg="dlg-cat" data-set="<?php echo esc_attr( wp_json_encode( array( 'id' => '', 'level' => 1, 'parent_id' => 0, 'title' => $L['label_cat1'] . ' 추가', 'what' => '', 'name' => '', 'note' => '', 'active' => 1 ) ) ); ?>"><?php echo md_inv_icon( 'plus', 18 ); // phpcs:ignore ?><?php echo esc_html( $L['label_cat1'] ); ?> 추가</button>
		<span class="iv-muted">품목 정리:</span>
		<?php foreach ( array( 'u1', 'u2', 'u3' ) as $k ) : ?>
			<a class="iv-pill<?php echo $sel === $k ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'catsort', 'ic' => $k ) ) . '#iv-cat-items' ); ?>"><?php echo esc_html( $L[ 'label_cat' . (int) substr( $k, 1 ) ] ); ?> 없는 품목 <?php echo (int) $cnt[ $k ]; ?></a>
		<?php endforeach; ?>
		<a class="iv-pill<?php echo 'all' === $sel ? ' is-on' : ''; ?>" href="<?php echo esc_url( md_inv_url( array( 'iv' => 'catsort', 'ic' => 'all' ) ) . '#iv-cat-items' ); ?>">모든 품목 <?php echo count( $all ); ?></a>
	</div>

	<div class="iv-tree iv-tree--edit">
	<?php foreach ( md_inv_cats_of( 1, null, false ) as $c1 ) : ?>
		<div class="iv-tree__n iv-tree__n--1<?php echo $c1->active ? '' : ' is-off'; ?>">
			<?php md_inv_catsort_row( $c1, $use, $sel ); ?>
			<?php foreach ( md_inv_cats_of( 2, $c1->id, false ) as $c2 ) : ?>
				<div class="iv-tree__n iv-tree__n--2<?php echo $c2->active ? '' : ' is-off'; ?>">
					<?php md_inv_catsort_row( $c2, $use, $sel ); ?>
					<?php foreach ( md_inv_cats_of( 3, $c2->id, false ) as $c3 ) : ?>
						<div class="iv-tree__n iv-tree__n--3<?php echo $c3->active ? '' : ' is-off'; ?>"><?php md_inv_catsort_row( $c3, $use, $sel ); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
	</div>

	<section class="iv-sec" id="iv-cat-items">
		<?php if ( '' === $sel ) : ?>
			<p class="iv-help">위에서 분류 이름이나 「… 없는 품목」을 누르면 그 품목이 여기에 나옵니다.</p>
		<?php else : ?>
			<h3 class="iv-h3"><?php echo esc_html( $title ); ?> <span class="iv-n"><?php echo count( $rows ); ?></span></h3>
			<?php if ( ! $rows ) : md_inv_empty( '해당하는 품목이 없습니다.' ); else : ?>
			<form method="post" class="iv-recat" data-recat data-confirm="고른 품목의 분류를 아래 고른 분류로 바꿀까요?">
				<?php md_inv_hidden( 'items_recat', $here ); ?>
				<div class="iv-recat__bar">
					<label class="iv-check"><input type="checkbox" data-recat-all> 보이는 품목 모두</label>
					<input class="iv-input iv-input--sm" type="search" placeholder="이 목록에서 찾기" data-recat-q aria-label="이 목록에서 찾기">
				</div>
				<div class="iv-table-wrap"><table class="iv-table iv-table--recat">
					<thead><tr><th></th><th>품목</th><th>업체</th><th>지금 분류</th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $it ) :
						$p = array_filter( array( (int) $it->cat1 ? md_inv_cat_name( $it->cat1 ) : '', (int) $it->cat2 ? md_inv_cat_name( $it->cat2 ) : '', (int) $it->cat3 ? md_inv_cat_name( $it->cat3 ) : '' ) ); ?>
						<tr data-n="<?php echo esc_attr( mb_strtolower( $it->name . ' ' . md_inv_vendor_name( $it->vendor_id ) ) ); ?>"<?php echo (int) $it->active ? '' : ' class="is-off"'; ?>>
							<td data-l=""><input type="checkbox" name="iids[]" value="<?php echo (int) $it->id; ?>" aria-label="고르기"></td>
							<td data-l="품목" class="iv-td-name"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'item', 'id' => $it->id ) ) ); ?>"><?php echo esc_html( $it->name ); ?></a><?php echo (int) $it->active ? '' : ' <span class="iv-tag">숨김</span>'; ?></td>
							<td data-l="업체"><?php echo esc_html( md_inv_vendor_name( $it->vendor_id ) ); ?></td>
							<td data-l="지금 분류"><?php echo $p ? esc_html( implode( ' › ', $p ) ) : '<span class="iv-muted">없음</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
				<div class="iv-stickyfoot iv-recat__foot">
					<span class="iv-muted" data-recat-sum>고른 품목 0개</span>
					<div class="iv-recat__to"><?php md_inv_cat_selects( 0, 0, 0 ); ?></div>
					<button class="iv-btn iv-btn--primary" data-recat-go disabled>고른 품목을 이 분류로</button>
				</div>
			</form>
			<?php endif; ?>
		<?php endif; ?>
	</section>

	<dialog class="iv-dlg" id="dlg-cat"><form method="post">
		<?php md_inv_hidden( 'cat_save', $here ); ?><input type="hidden" name="id" value=""><input type="hidden" name="level" value=""><input type="hidden" name="parent_id" value="">
		<div class="iv-dlg__head"><b data-t="title">분류</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<p class="iv-dlg__what" data-t="what"></p>
		<label class="iv-f"><span>이름 <em>*</em></span><input class="iv-input" name="name" maxlength="60" required></label>
		<label class="iv-f"><span>비고</span><input class="iv-input" name="note" maxlength="200"></label>
		<input type="hidden" name="active" value="0" data-unchecked-for="active">
		<label class="iv-check"><input type="checkbox" name="active" value="1"> 사용 (고르는 칸 · 신청 화면에 보임)</label>
		<?php md_inv_dlg_close(); ?>

	<dialog class="iv-dlg" id="dlg-catmove"><form method="post">
		<?php md_inv_hidden( 'cat_reparent', $here ); ?><input type="hidden" name="id" value="">
		<div class="iv-dlg__head"><b>다른 분류 아래로 옮기기</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<p class="iv-dlg__what" data-t="what"></p>
		<p class="iv-help">이 분류와 그 아래 분류 · 품목이 모두 함께 옮겨집니다.</p>
		<label class="iv-f"><span>옮길 곳 <em>*</em></span><select class="iv-input" name="parent_id" required data-catpick="move"></select></label>
		<?php md_inv_dlg_close( '옮기기' ); ?>

	<dialog class="iv-dlg" id="dlg-catmerge"><form method="post">
		<?php md_inv_hidden( 'cat_merge', $here ); ?><input type="hidden" name="id" value="">
		<div class="iv-dlg__head"><b>다른 분류에 합치기</b><button type="button" class="iv-x" data-close aria-label="닫기"><?php echo md_inv_icon( 'x' ); // phpcs:ignore ?></button></div>
		<p class="iv-dlg__what" data-t="what"></p>
		<p class="iv-help">이 분류의 품목을 고른 분류로 옮기고, 이 분류는 없어집니다. 아래 분류는 고른 분류 아래로 옮겨지고, 같은 이름이 있으면 그것끼리 합쳐집니다.</p>
		<label class="iv-f"><span>합칠 곳 <em>*</em></span><select class="iv-input" name="into" required data-catpick="merge"></select></label>
		<?php md_inv_dlg_close( '합치기', 'danger' ); ?>
	<?php
}

function md_inv_catsort_row( $c, $use, $sel ) {
	$L  = md_inv_settings();
	$n  = isset( $use[ (int) $c->id ] ) ? (int) $use[ (int) $c->id ] : 0;
	$lv = (int) $c->level;
	echo '<div class="iv-tree__row' . ( (string) $sel === (string) $c->id ? ' is-sel' : '' ) . '">';
	md_inv_move_buttons( 'cat_move', $c->id );
	echo '<a class="iv-tree__name" href="' . esc_url( md_inv_url( array( 'iv' => 'catsort', 'ic' => (int) $c->id ) ) . '#iv-cat-items' ) . '"><b>' . esc_html( $c->name ) . '</b></a>'
		. ( $c->active ? '' : ' <span class="iv-tag">사용 안 함</span>' ) . ( $c->note ? ' <small class="iv-muted">' . esc_html( $c->note ) . '</small>' : '' );
	echo '<span class="iv-muted iv-tree__cnt">품목 ' . $n . '</span>';
	echo '<span class="iv-tree__act">';
	if ( $lv < 3 ) {
		echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-cat" data-set="' . esc_attr( wp_json_encode( array( 'id' => '', 'level' => $lv + 1, 'parent_id' => (int) $c->id, 'title' => $L[ 'label_cat' . ( $lv + 1 ) ] . ' 추가', 'what' => $c->name . ' 아래', 'name' => '', 'note' => '', 'active' => 1 ) ) ) . '">＋ 아래 추가</button>';
	}
	echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-cat" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $c->id, 'level' => $lv, 'parent_id' => (int) $c->parent_id, 'title' => '고치기', 'what' => '', 'name' => $c->name, 'note' => $c->note, 'active' => (int) $c->active ) ) ) . '">고치기</button>';
	if ( $lv > 1 ) {
		echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-catmove" data-catfill="move" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $c->id, 'what' => $c->name, 'lv' => $lv, 'cur' => (int) $c->parent_id ) ) ) . '">옮기기</button>';
	}
	echo '<button type="button" class="iv-btn iv-btn--ghost iv-btn--xs" data-dlg="dlg-catmerge" data-catfill="merge" data-set="' . esc_attr( wp_json_encode( array( 'id' => (int) $c->id, 'what' => $c->name . ' (품목 ' . $n . '개)', 'lv' => $lv, 'cur' => (int) $c->id ) ) ) . '">합치기</button>';
	echo '<form method="post" class="iv-inline-form" data-confirm="' . esc_attr( '「' . $c->name . '」을(를) 지울까요? 아래 분류도 함께 지워지고, 품목 ' . $n . '개의 분류 칸이 비워집니다. (품목은 지워지지 않습니다)' ) . '">';
	md_inv_hidden( 'cat_delete' );
	echo '<input type="hidden" name="id" value="' . (int) $c->id . '"><button class="iv-btn iv-btn--ghost iv-btn--xs">삭제</button></form>';
	echo '</span></div>';
}
