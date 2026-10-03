<?php
/**
 * 재고관리 v5 — 통계
 *
 * 사용금액 = 출고 수량 × 출고할 때의 단가. 선납 업체 품목은 설정에 따라 뺀다(기본: 뺌).
 * 그래프는 라이브러리 없이 막대로 그린다 — 다크 모드와 휴대폰 폭에서 그대로 맞는다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 기간 고르기 → [from, to, 이름] */
function md_inv_stats_range() {
	$now = current_time( 'timestamp' );
	$p   = md_inv_get( 'ip', 'm0' );
	switch ( $p ) {
		case 'w0': $from = date( 'Y-m-d', $now - ( (int) date( 'N', $now ) - 1 ) * DAY_IN_SECONDS ); $to = date( 'Y-m-d', $now ); $nm = '이번 주'; break;
		case 'm1': $from = date( 'Y-m-01', strtotime( '-1 month', strtotime( date( 'Y-m-01', $now ) ) ) ); $to = date( 'Y-m-t', strtotime( $from ) ); $nm = '지난달'; break;
		case 'q':  $from = date( 'Y-m-01', strtotime( '-2 months', strtotime( date( 'Y-m-01', $now ) ) ) ); $to = date( 'Y-m-d', $now ); $nm = '최근 3개월'; break;
		case 'y':  $from = date( 'Y-01-01', $now ); $to = date( 'Y-m-d', $now ); $nm = '올해'; break;
		case 'c':
			$from = md_inv_get_date( 'df', date( 'Y-m-01', $now ) );
			$to   = md_inv_get_date( 'dt', date( 'Y-m-d', $now ) );
			if ( $from > $to ) { $t = $from; $from = $to; $to = $t; }
			$nm = $from . ' ~ ' . $to; break;
		default: $p = 'm0'; $from = date( 'Y-m-01', $now ); $to = date( 'Y-m-d', $now ); $nm = '이번 달'; break;
	}
	return array( $from, $to, $nm, $p );
}

/** 가로 막대 목록 */
function md_inv_bars( $rows, $money = true ) {
	if ( ! $rows ) { md_inv_empty( '이 기간에 출고가 없습니다.' ); return; }
	$max = 0;
	foreach ( $rows as $r ) { $max = max( $max, (int) $r['v'] ); }
	echo '<ul class="iv-bars">';
	foreach ( $rows as $r ) {
		$w = $max > 0 ? max( 1, round( (int) $r['v'] / $max * 100 ) ) : 0;
		echo '<li><span class="iv-bars__k">' . esc_html( $r['k'] ) . '</span><span class="iv-bars__track"><span class="iv-bars__fill" style="width:' . (int) $w . '%"></span></span><b class="iv-bars__v">' . esc_html( $money ? md_inv_won( $r['v'] ) : md_inv_num( $r['v'] ) ) . '</b>' . ( isset( $r['s'] ) ? '<small>' . esc_html( $r['s'] ) . '</small>' : '' ) . '</li>';
	}
	echo '</ul>';
}

/** 세로 막대 (월 · 주 추이) */
function md_inv_columns( $map, $label_fn ) {
	$max = max( 1, max( array_values( $map ) ) );
	echo '<div class="iv-cols" role="img" aria-label="' . esc_attr( '추이: ' . implode( ', ', array_map( function ( $k, $v ) use ( $label_fn ) { return call_user_func( $label_fn, $k ) . ' ' . md_inv_won( $v ); }, array_keys( $map ), $map ) ) ) . '">';
	foreach ( $map as $k => $v ) {
		$h = round( (int) $v / $max * 100 );
		$short = $v >= 10000 ? number_format( $v / 10000, $v >= 1000000 ? 0 : 1 ) . '만' : ( $v ? md_inv_num( $v ) : '' );
		echo '<div class="iv-cols__c" title="' . esc_attr( call_user_func( $label_fn, $k ) . ' · ' . md_inv_won( $v ) ) . '"><span class="iv-cols__v">' . esc_html( $short ) . '</span><span class="iv-cols__bar" style="height:' . (int) $h . '%"></span><span class="iv-cols__k">' . esc_html( call_user_func( $label_fn, $k ) ) . '</span></div>';
	}
	echo '</div>';
}

function md_inv_view_stats() {
	$admin = md_inv_is_admin();
	$S     = md_inv_settings();
	list( $from, $to, $pname, $p ) = md_inv_stats_range();
	$team  = (int) md_inv_get( 'it', 0 );

	$by_team = md_inv_usage( 'team', $from, $to );
	$by_cat  = md_inv_usage( 'cat2', $from, $to, $team );
	$by_item = md_inv_usage( 'item', $from, $to, $team, 20 );
	$total = 0; $cnt = 0;
	foreach ( ( $team ? md_inv_usage( 'team', $from, $to, $team ) : $by_team ) as $r ) { $total += (int) $r->amount; $cnt += (int) $r->n; }
	$hidden_team = array();
	foreach ( md_inv_teams( false ) as $tm ) { if ( ! $tm->in_stats ) { $hidden_team[ (int) $tm->id ] = 1; } }
	?>
	<form class="iv-filter" method="get" data-autosubmit>
		<input type="hidden" name="app" value="stock"><input type="hidden" name="iv" value="stats">
		<label class="iv-f"><span>기간</span><select class="iv-input" name="ip" data-toggle-custom>
			<?php foreach ( array( 'w0' => '이번 주', 'm0' => '이번 달', 'm1' => '지난달', 'q' => '최근 3개월', 'y' => '올해', 'c' => '직접 고르기' ) as $k => $lb ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $p, $k ); ?>><?php echo esc_html( $lb ); ?></option><?php endforeach; ?>
		</select></label>
		<label class="iv-f" data-custom<?php echo 'c' === $p ? '' : ' hidden'; ?>><span>부터</span><input class="iv-input" type="date" name="df" value="<?php echo esc_attr( $from ); ?>"></label>
		<label class="iv-f" data-custom<?php echo 'c' === $p ? '' : ' hidden'; ?>><span>까지</span><input class="iv-input" type="date" name="dt" value="<?php echo esc_attr( $to ); ?>"></label>
		<label class="iv-f"><span>팀</span><?php md_inv_team_select( 'it', $team, false, '모든 팀', true ); ?></label>
		<button class="iv-btn iv-btn--ghost">보기</button>
	</form>

	<div class="iv-kpis iv-kpis--big">
		<div class="iv-kpi"><span><?php echo esc_html( $pname ); ?> 사용금액<?php echo $team ? ' · ' . esc_html( md_inv_team_name( $team ) ) : ''; ?></span><b><?php echo esc_html( md_inv_won( $total ) ); ?></b></div>
		<div class="iv-kpi"><span>출고 건수</span><b><?php echo esc_html( md_inv_num( $cnt ) ); ?></b></div>
		<?php if ( $admin ) : $items = md_inv_items(); ?>
			<div class="iv-kpi"><span>지금 재고 금액</span><b><?php echo esc_html( md_inv_won( md_inv_stock_value( $items ) ) ); ?></b></div>
			<?php $buy = 0; foreach ( md_inv_purchase_by_vendor( $from, $to ) as $r ) { $buy += (int) $r->bought - (int) $r->returned; } ?>
			<div class="iv-kpi"><span><?php echo esc_html( $pname ); ?> 구매(입고) 금액</span><b><?php echo esc_html( md_inv_won( $buy ) ); ?></b></div>
		<?php endif; ?>
	</div>
	<p class="iv-help"><?php echo esc_html( $from . ' ~ ' . $to ); ?> · 출고 수량 × 출고 때 단가<?php echo $S['stats_include_prepaid'] ? '' : ' · 선납 업체 품목(임플란트 등)은 빠져 있습니다'; ?></p>

	<div class="iv-statgrid">
		<section class="iv-panel">
			<h3 class="iv-h3">월별 사용금액 <small>최근 <?php echo (int) $S['stats_months']; ?>개월</small></h3>
			<?php md_inv_columns( md_inv_usage_monthly( (int) $S['stats_months'], $team ), function ( $k ) { return (int) substr( $k, 5 ) . '월'; } ); ?>
		</section>
		<section class="iv-panel">
			<h3 class="iv-h3">주별 사용금액 <small>최근 <?php echo (int) $S['stats_weeks']; ?>주 · 월요일 시작</small></h3>
			<?php md_inv_columns( md_inv_usage_weekly( (int) $S['stats_weeks'], $team ), function ( $k ) { return date( 'n/j', strtotime( $k ) ); } ); ?>
		</section>
		<?php if ( ! $team ) : ?>
		<section class="iv-panel">
			<h3 class="iv-h3">팀별 <small><?php echo esc_html( $pname ); ?></small></h3>
			<?php
			$rows = array();
			foreach ( $by_team as $r ) {
				if ( isset( $hidden_team[ (int) $r->k ] ) ) { continue; }
				$rows[] = array( 'k' => md_inv_team_name( $r->k ) ? md_inv_team_name( $r->k ) : '(팀 없음)', 'v' => (int) $r->amount, 's' => (int) $r->n . '건' );
			}
			md_inv_bars( $rows );
			?>
		</section>
		<?php endif; ?>
		<section class="iv-panel">
			<h3 class="iv-h3"><?php echo esc_html( $S['label_cat2'] ); ?>별 <small><?php echo esc_html( $pname ); ?></small></h3>
			<?php
			$rows = array();
			foreach ( $by_cat as $r ) { $rows[] = array( 'k' => $r->k ? md_inv_cat_name( $r->k ) : '(미지정)', 'v' => (int) $r->amount, 's' => (int) $r->n . '건' ); }
			md_inv_bars( $rows );
			?>
		</section>
		<section class="iv-panel iv-panel--wide">
			<h3 class="iv-h3">많이 쓴 품목 <small>금액 순 상위 20</small></h3>
			<?php if ( ! $by_item ) : md_inv_empty( '이 기간에 출고가 없습니다.' ); else : ?>
			<div class="iv-table-wrap"><table class="iv-table"><thead><tr><th>#</th><th>품목</th><th class="r">수량</th><th class="r">금액</th></tr></thead><tbody>
				<?php foreach ( $by_item as $i => $r ) : $it = md_inv_item( $r->k ); ?>
					<tr><td data-l="#"><?php echo (int) $i + 1; ?></td><td data-l="품목"><?php echo esc_html( $it ? $it->name : '#' . $r->k ); ?></td><td data-l="수량" class="r"><?php echo esc_html( md_inv_num( $r->qty ) . ( $it ? ' ' . $it->unit : '' ) ); ?></td><td data-l="금액" class="r"><?php echo esc_html( md_inv_won( $r->amount ) ); ?></td></tr>
				<?php endforeach; ?>
			</tbody></table></div>
			<?php endif; ?>
		</section>
		<?php if ( $admin ) : ?>
		<section class="iv-panel iv-panel--wide">
			<h3 class="iv-h3">업체별 구매(입고) 금액 <small><?php echo esc_html( $pname ); ?> · 무상 제외 · 반품 차감</small></h3>
			<?php
			$rows = array();
			foreach ( md_inv_purchase_by_vendor( $from, $to ) as $r ) { $rows[] = array( 'k' => md_inv_vendor_name( $r->k ) ? md_inv_vendor_name( $r->k ) : '(업체 없음)', 'v' => (int) $r->bought - (int) $r->returned ); }
			md_inv_bars( $rows );
			?>
		</section>
		<?php endif; ?>
	</div>
	<div class="iv-toolbar">
		<?php md_inv_dl_buttons( 'usage', array( 'df' => $from, 'dt' => $to ), '이 기간 CSV' ); ?>
		<?php md_inv_dl_buttons( 'monthly', array(), '팀별 월별 CSV' ); ?>
		<?php if ( $admin ) : ?><a class="iv-btn iv-btn--ghost iv-btn--sm" href="<?php echo esc_url( md_inv_dl_url( 'xlsx', array( 'df' => $from, 'dt' => $to ) ) ); ?>"><?php echo md_inv_icon( 'down', 16 ); // phpcs:ignore ?>엑셀 보고서 (이 기간)</a><?php endif; ?>
	</div>
	<?php
}
