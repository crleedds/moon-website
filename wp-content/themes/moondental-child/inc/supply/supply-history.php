<?php
/**
 * 재료실 — 입출고 이력 (재고 담당자 전용)
 *
 * 이 화면이 없으면 설계가 보이지 않는다
 *   이 시스템은 현재고를 저장하지 않는다. 무엇이 움직이든 원장에 한 줄 남기고
 *   현재고는 그 합계로 읽는다. 덕분에 「왜 줄었는지」가 늘 남아 있는데,
 *   v3.63 까지는 정작 그 원장을 볼 수 있는 화면이 없었다.
 *   그래서 「이 픽스처가 왜 12개에서 3개가 됐지」를 아무도 답할 수 없었다.
 *
 * 고치는 화면이 아니다
 *   원장은 지나간 사실이라 여기서 고치거나 지우지 않는다.
 *   숫자가 틀렸으면 「재고」 화면에서 실사로 조정해 그 차이도 한 줄로 남긴다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 한 화면에 보여줄 줄 수 */
define( 'MD_SUP_HISTORY_PER', 100 );

/** 이력 화면이 쓰는 조회 조건을 주소에서 읽는다 (목록·CSV 가 함께 쓴다) */
function md_sup_history_query() {
	$reasons = array( 'in', 'out', 'dispose', 'adjust' );
	$reason  = isset( $_GET['reason'] ) ? sanitize_key( wp_unslash( $_GET['reason'] ) ) : '';
	if ( ! in_array( $reason, $reasons, true ) ) { $reason = ''; }

	$ym = isset( $_GET['ym'] ) ? sanitize_text_field( wp_unslash( $_GET['ym'] ) ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}$/', $ym ) ) { $ym = ''; }

	return array(
		'item_id' => isset( $_GET['item'] ) ? (int) $_GET['item'] : 0,
		'team_id' => isset( $_GET['team'] ) ? (int) $_GET['team'] : 0,
		'reason'  => $reason,
		'ym'      => $ym,
		'search'  => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
	);
}

/** ⑦ 입출고 이력 */
function md_sup_render_history() {
	$args = md_sup_history_query();
	$page = isset( $_GET['pg'] ) ? max( 1, (int) $_GET['pg'] ) : 1;

	$total = md_sup_ledger_count( $args );
	$pages = max( 1, (int) ceil( $total / MD_SUP_HISTORY_PER ) );
	if ( $page > $pages ) { $page = $pages; }

	$rows = md_sup_ledger( array_merge( $args, array(
		'limit'  => MD_SUP_HISTORY_PER,
		'offset' => ( $page - 1 ) * MD_SUP_HISTORY_PER,
	) ) );

	/* 품목 하나를 보고 있다면 그 품목의 요약을 먼저 */
	if ( $args['item_id'] > 0 ) { md_sup_render_item_card( $args['item_id'] ); }
	?>

	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="history">
		<?php md_sup_app_field(); ?>
		<?php if ( $args['item_id'] > 0 ) : ?>
			<input type="hidden" name="item" value="<?php echo (int) $args['item_id']; ?>">
		<?php endif; ?>

		<label class="mds-field">
			<span>사유</span>
			<select name="reason">
				<option value="">전체</option>
				<?php foreach ( array( 'out', 'in', 'adjust', 'dispose' ) as $r ) : ?>
					<option value="<?php echo esc_attr( $r ); ?>" <?php selected( $args['reason'], $r ); ?>><?php echo esc_html( md_sup_reason_label( $r ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="mds-field">
			<span>팀</span>
			<select name="team">
				<option value="0">전체</option>
				<?php foreach ( md_sup_teams( false ) as $tm ) : ?>
					<option value="<?php echo (int) $tm->id; ?>" <?php selected( $args['team_id'], (int) $tm->id ); ?>><?php echo esc_html( $tm->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="mds-field">
			<span>기준 월</span>
			<select name="ym">
				<option value="">전체 기간</option>
				<?php for ( $i = 0; $i < 18; $i++ ) :
					$v = gmdate( 'Y-m', strtotime( "-$i months", current_time( 'timestamp' ) ) ); ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $args['ym'], $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endfor; ?>
			</select>
		</label>

		<?php if ( 0 === $args['item_id'] ) : ?>
			<label class="mds-field mds-field--grow">
				<span>품목 검색</span>
				<input type="search" name="q" value="<?php echo esc_attr( $args['search'] ); ?>" placeholder="품목명 · 코드">
			</label>
		<?php endif; ?>

		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
	</form>

	<div class="mds-tools">
		<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( add_query_arg( 'export', 'ledger', md_sup_url( array_merge( array( 'tab' => 'history' ), md_sup_history_url_args( $args ) ) ) ) ); ?>">엑셀(CSV) 내려받기</a>
		<?php if ( $args['item_id'] || $args['team_id'] || $args['reason'] || $args['ym'] || '' !== $args['search'] ) : ?>
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'history' ) ) ); ?>">필터 해제</a>
		<?php endif; ?>
	</div>

	<p class="mds-hint">
		모두 <?php echo esc_html( number_format( $total ) ); ?>건 ·
		<?php echo (int) $page; ?>/<?php echo (int) $pages; ?> 쪽.
		수량의 <b>+</b> 는 창고에 들어온 것, <b>−</b> 는 나간 것입니다.
	</p>

	<div class="mds-tablewrap">
		<table class="mds-table">
			<thead>
				<tr><th>일시</th><th>품목</th><th>사유</th><th>팀</th><th class="num">수량</th><th class="num">금액</th><th>처리</th><th>메모</th></tr>
			</thead>
			<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="8" class="mds-empty">해당하는 기록이 없습니다.</td></tr>
			<?php else : foreach ( $rows as $r ) :
				$qty = (int) $r->qty; ?>
				<tr>
					<td class="mds-last" data-label="일시"><?php echo esc_html( mysql2date( 'Y-m-d H:i', $r->created_at ) ); ?></td>
					<td class="mds-item" data-label="품목">
						<?php if ( (int) $r->item_id > 0 && '' !== $r->item_name ) : ?>
							<b><a class="mds-plain" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'history', 'item' => (int) $r->item_id ) ) ); ?>"><?php echo esc_html( $r->item_name ); ?></a></b>
							<span class="mds-item__meta"><?php echo esc_html( $r->item_code ); ?></span>
						<?php else : ?>
							<b>(지워진 품목 #<?php echo (int) $r->item_id; ?>)</b>
						<?php endif; ?>
					</td>
					<td data-label="사유"><span class="mds-rsn is-<?php echo esc_attr( $r->reason ); ?>"><?php echo esc_html( md_sup_reason_label( $r->reason ) ); ?></span></td>
					<td data-label="팀"><?php echo esc_html( '' !== $r->team_name ? $r->team_name : '—' ); ?></td>
					<td class="num<?php echo $qty < 0 ? ' is-minus' : ' is-plus'; ?>" data-label="수량">
						<?php echo esc_html( ( $qty > 0 ? '+' : '' ) . number_format( $qty ) ); ?>
					</td>
					<td class="num" data-label="금액"><?php echo esc_html( number_format( abs( $qty ) * (int) $r->price ) ); ?></td>
					<td data-label="처리"><?php echo esc_html( '' !== $r->user_name ? $r->user_name : '—' ); ?></td>
					<td class="mds-memo" data-label="메모"><?php echo esc_html( $r->note ); ?></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $pages > 1 ) : ?>
		<nav class="mds-pager" aria-label="이력 쪽 넘김">
			<?php
			$base = array_merge( array( 'tab' => 'history' ), md_sup_history_url_args( $args ) );
			if ( $page > 1 ) : ?>
				<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array_merge( $base, array( 'pg' => $page - 1 ) ) ) ); ?>">← 이전</a>
			<?php endif; ?>
			<span class="mds-pager__now"><?php echo (int) $page; ?> / <?php echo (int) $pages; ?></span>
			<?php if ( $page < $pages ) : ?>
				<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array_merge( $base, array( 'pg' => $page + 1 ) ) ) ); ?>">다음 →</a>
			<?php endif; ?>
		</nav>
	<?php endif;
}

/** 조회 조건을 다시 주소로 — 빈 값은 붙이지 않는다 */
function md_sup_history_url_args( $args ) {
	$out = array();
	if ( $args['item_id'] > 0 )   { $out['item']   = (int) $args['item_id']; }
	if ( $args['team_id'] > 0 )   { $out['team']   = (int) $args['team_id']; }
	if ( '' !== $args['reason'] ) { $out['reason'] = $args['reason']; }
	if ( '' !== $args['ym'] )     { $out['ym']     = $args['ym']; }
	if ( '' !== $args['search'] ) { $out['q']      = $args['search']; }
	return $out;
}

/** 품목 하나를 볼 때 맨 위에 붙는 요약 — 지금 상태와 어느 팀이 쓰는지 */
function md_sup_render_item_card( $item_id ) {
	$it = md_sup_item( $item_id );
	if ( ! $it ) { return; }

	$usage = md_sup_item_team_usage( $item_id, 3 );
	$avg   = md_sup_avg_map_all( 3 );
	$mavg  = isset( $avg[ (int) $it->id ] ) ? $avg[ (int) $it->id ] : 0;
	$low   = ( $it->min_stock > 0 && $it->stock <= $it->min_stock );

	$max = 1;
	foreach ( $usage as $u ) { $max = max( $max, (int) $u->qty ); }
	?>
	<p class="mds-back"><a href="<?php echo esc_url( md_sup_url( array( 'tab' => 'history' ) ) ); ?>">← 전체 이력</a></p>

	<div class="mds-card">
		<h2 class="mds-h2" style="margin-top:0"><?php echo esc_html( $it->name ); ?></h2>
		<p class="mds-hint" style="margin-bottom:16px">
			<?php echo esc_html( $it->code ); ?>
			<?php echo $it->vendor ? ' · ' . esc_html( $it->vendor ) : ''; ?>
			<?php echo $it->unit ? ' · ' . esc_html( $it->unit ) : ''; ?>
			<?php echo $it->category ? ' · ' . esc_html( $it->category ) : ''; ?>
			<?php echo $it->active ? '' : ' · <b>감춘 품목</b>'; ?>
		</p>

		<div class="mds-kpis">
			<div class="mds-kpi">
				<span class="n<?php echo $low ? ' is-low' : ''; ?>"><?php echo esc_html( number_format( (int) $it->stock ) ); ?></span>
				<span class="k">현재고<?php echo $low ? ' · 부족' : ''; ?></span>
			</div>
			<div class="mds-kpi"><span class="n"><?php echo (int) $it->min_stock; ?></span><span class="k">적정재고</span></div>
			<div class="mds-kpi"><span class="n"><?php echo esc_html( $mavg > 0 ? $mavg : '—' ); ?></span><span class="k">전체 월평균 사용</span></div>
			<div class="mds-kpi"><span class="n"><?php echo esc_html( number_format( (int) $it->price ) ); ?></span><span class="k">단가 (원)</span></div>
		</div>

		<?php if ( $usage ) : ?>
			<h3 class="mds-h3">최근 3개월 · 팀별 사용량</h3>
			<div class="mds-bars">
				<?php foreach ( $usage as $u ) :
					$w = round( (int) $u->qty / $max * 100 ); ?>
					<div class="mds-bar">
						<span class="mds-bar__label"><?php echo esc_html( '' !== $u->team_name ? $u->team_name : '(팀 없음)' ); ?></span>
						<span class="mds-bar__track"><span class="mds-bar__fill" style="width:<?php echo (int) $w; ?>%"></span></span>
						<span class="mds-bar__val"><?php echo esc_html( number_format( (int) $u->qty ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="mds-tools" style="margin:16px 0 0">
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'items', 'item' => (int) $it->id ) ) ); ?>">품목 수정</a>
		</div>
	</div>
	<?php
}

/** 입출고 이력 CSV — 화면에서 보고 있는 조건 그대로 */
function md_sup_export_ledger() {
	$args = md_sup_history_query();

	md_sup_csv_start( 'moondental-ledger-' . current_time( 'Y-m-d' ) . '.csv' );
	md_sup_csv_row( array( '문치과병원 재료실 · 입출고 이력' ) );
	md_sup_csv_row( array(
		'조건',
		$args['item_id'] ? ( ( $o = md_sup_item( $args['item_id'] ) ) ? $o->name : '' ) : '전체 품목',
		$args['team_id'] ? md_sup_team_name( $args['team_id'] ) : '전체 팀',
		'' !== $args['reason'] ? md_sup_reason_label( $args['reason'] ) : '전체 사유',
		'' !== $args['ym'] ? $args['ym'] : '전체 기간',
	) );
	md_sup_csv_row( array() );

	md_sup_csv_row( array( '일시', '품목코드', '품목명', '사유', '팀', '수량', '단가', '금액(원)', '처리', '메모' ) );

	/* 큰 원장을 한 번에 다 메모리에 올리지 않고 나눠 읽는다 */
	$offset = 0;
	$chunk  = 500;
	while ( true ) {
		$rows = md_sup_ledger( array_merge( $args, array( 'limit' => $chunk, 'offset' => $offset ) ) );
		if ( empty( $rows ) ) { break; }

		foreach ( $rows as $r ) {
			md_sup_csv_row( array(
				mysql2date( 'Y-m-d H:i', $r->created_at ),
				$r->item_code,
				$r->item_name,
				md_sup_reason_label( $r->reason ),
				$r->team_name,
				(int) $r->qty,
				(int) $r->price,
				abs( (int) $r->qty ) * (int) $r->price,
				$r->user_name,
				$r->note,
			) );
		}

		if ( count( $rows ) < $chunk ) { break; }
		$offset += $chunk;
	}
	exit;
}
