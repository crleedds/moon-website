<?php
/**
 * 재료실 — 통계 탭
 *
 * 팀별 사용액과 품목별 내역을 보여준다.
 * 담당자뿐 아니라 직원도 본다 — 모든 팀이 서로의 숫자를 보는 것이
 * 이 화면의 요점이다. 절대액만으로 판단하지 말라는 안내를 함께 둔다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ② 통계 */
function md_sup_render_stats() {
	$ym      = isset( $_GET['ym'] ) ? sanitize_text_field( wp_unslash( $_GET['ym'] ) ) : current_time( 'Y-m' );
	$my_team = md_sup_my_team_id();
	/* 볼 팀 — 지정 안 했으면 내 소속 팀. 전체를 보려면 0 */
	$view    = isset( $_GET['team'] ) ? (int) $_GET['team'] : $my_team;
	$teams   = md_sup_teams();

	$usage = md_sup_team_usage( $ym );
	$delta_map = md_sup_team_delta( $ym );
	$trend = md_sup_monthly_trend( $view, 6 );
	$top   = md_sup_top_items( $ym, $view, 15 );

	$total = 0;
	$max   = 1;
	$mine  = 0;
	$rank  = 0;
	$i     = 0;
	foreach ( $usage as $u ) {
		$i++;
		$total += (int) $u->amount;
		$max    = max( $max, (int) $u->amount );
		if ( $view && (int) $u->team_id === $view ) { $mine = (int) $u->amount; $rank = $i; }
	}
	$tmax = 1;
	foreach ( $trend as $v ) { $tmax = max( $tmax, (int) $v ); }

	/* 전월 대비 — 추이 배열의 마지막 두 달을 쓴다 */
	$tvals = array_values( $trend );
	$prev  = count( $tvals ) >= 2 ? (int) $tvals[ count( $tvals ) - 2 ] : 0;
	$curr  = count( $tvals ) >= 1 ? (int) $tvals[ count( $tvals ) - 1 ] : 0;
	$delta = ( $prev > 0 ) ? round( ( $curr - $prev ) / $prev * 100 ) : null;

	$view_name = $view ? md_sup_team_name( $view ) : '전체';
	?>
	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="stats">
		<?php md_sup_app_field(); ?>
		<label class="mds-field">
			<span>기준 월</span>
			<select name="ym" onchange="this.form.submit()">
				<?php for ( $i = 0; $i < 12; $i++ ) :
					$v = gmdate( 'Y-m', strtotime( "-$i months", current_time( 'timestamp' ) ) ); ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $ym, $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endfor; ?>
			</select>
		</label>
		<label class="mds-field">
			<span>팀</span>
			<select name="team" onchange="this.form.submit()">
				<option value="0" <?php selected( $view, 0 ); ?>>전체 팀</option>
				<?php foreach ( $teams as $tm ) : ?>
					<option value="<?php echo (int) $tm->id; ?>" <?php selected( $view, $tm->id ); ?>><?php echo esc_html( $tm->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<noscript><button type="submit" class="mds-btn mds-btn--ghost">보기</button></noscript>
	</form>

	<div class="mds-tools">
		<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( "tab" => "stats", "ym" => $ym, "team" => $view, "export" => "usage" ) ) ); ?>">엑셀(CSV) 내려받기</a>
		<button type="button" class="mds-btn mds-btn--ghost" onclick="window.print()">인쇄</button>
	</div>

	<div class="mds-kpis">
		<div class="mds-kpi">
			<span class="n"><?php echo esc_html( number_format( $view ? $mine : $total ) ); ?></span>
			<span class="k"><?php echo esc_html( $view_name . ' · ' . $ym . ' 사용액 (원)' ); ?></span>
		</div>
		<div class="mds-kpi">
			<span class="n"><?php echo null === $delta ? '—' : esc_html( ( $delta > 0 ? '+' : '' ) . $delta . '%' ); ?></span>
			<span class="k">전월 대비</span>
		</div>
		<?php if ( $view && $rank ) : ?>
			<div class="mds-kpi"><span class="n"><?php echo (int) $rank; ?>위</span><span class="k">전체 <?php echo count( $usage ); ?>개 팀 중 사용액 순위</span></div>
		<?php else : ?>
			<div class="mds-kpi"><span class="n"><?php echo esc_html( count( $usage ) ); ?></span><span class="k">사용 팀 수</span></div>
		<?php endif; ?>
		<div class="mds-kpi">
			<span class="n"><?php echo esc_html( number_format( $total ) ); ?></span>
			<span class="k">전체 팀 합계 (원)</span>
		</div>
	</div>

	<h2 class="mds-h2">팀별 사용액</h2>
	<p class="mds-hint">모든 팀이 서로의 숫자를 봅니다. 팀마다 진료 건수가 다르니 절대액만으로 판단하지 마세요.</p>
	<?php if ( empty( $usage ) ) : ?>
		<p class="mds-empty">이 달에 출고된 기록이 아직 없습니다.</p>
	<?php else : ?>
		<div class="mds-bars">
			<?php foreach ( $usage as $u ) :
				$w = round( (int) $u->amount / $max * 100 );
				$is_me = ( $view && (int) $u->team_id === $view ); ?>
				<div class="mds-bar<?php echo $is_me ? ' is-me' : ''; ?>">
					<span class="mds-bar__label"><?php echo esc_html( $u->team_name ? $u->team_name : '(팀 없음)' ); ?></span>
					<span class="mds-bar__track"><span class="mds-bar__fill" style="width:<?php echo (int) $w; ?>%"></span></span>
					<span class="mds-bar__val"><?php echo esc_html( number_format( (int) $u->amount ) ); ?></span>
					<?php $d = isset( $delta_map[ (int) $u->team_id ] ) ? $delta_map[ (int) $u->team_id ] : null; ?>
					<span class="mds-bar__delta<?php echo ( null !== $d && $d > 20 ) ? " is-up" : ( ( null !== $d && $d < -10 ) ? " is-down" : "" ); ?>"><?php echo null === $d ? "—" : esc_html( ( $d > 0 ? "+" : "" ) . $d . "%" ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<h2 class="mds-h2"><?php echo esc_html( $view_name ); ?> · 6개월 추이</h2>
	<div class="mds-trend">
		<?php foreach ( $trend as $m => $v ) :
			$h = max( 3, round( (int) $v / $tmax * 100 ) ); ?>
			<div class="mds-trend__col">
				<span class="mds-trend__bar" style="height:<?php echo (int) $h; ?>%" title="<?php echo esc_attr( number_format( (int) $v ) . '원' ); ?>"></span>
				<span class="mds-trend__v"><?php echo esc_html( $v > 0 ? round( $v / 10000 ) . '만' : '0' ); ?></span>
				<span class="mds-trend__m"><?php echo esc_html( substr( $m, 5, 2 ) ); ?>월</span>
			</div>
		<?php endforeach; ?>
	</div>

	<h2 class="mds-h2"><?php echo esc_html( $view_name . ' · ' . $ym ); ?> 품목별 사용 내역</h2>
	<p class="mds-hint">금액이 큰 순서입니다. 무엇에 비용이 쓰이는지 여기서 확인하세요.</p>
	<div class="mds-tablewrap">
		<table class="mds-table">
			<thead><tr><th>품목</th><th class="num">단가</th><th class="num">수량</th><th class="num">금액</th><th class="num">비중</th></tr></thead>
			<tbody>
			<?php if ( empty( $top ) ) : ?>
				<tr><td colspan="5" class="mds-empty">이 기간에 출고된 기록이 없습니다.</td></tr>
			<?php else :
				$base = $view ? max( 1, $mine ) : max( 1, $total );
				foreach ( $top as $r ) : ?>
				<tr>
					<td class="mds-item"><b><?php echo esc_html( $r->name ); ?></b></td>
					<td class="num"><?php echo esc_html( number_format( (int) $r->price ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format( (int) $r->qty ) . ( $r->unit ? ' ' . $r->unit : '' ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format( (int) $r->amount ) ); ?></td>
					<td class="num"><?php echo esc_html( round( (int) $r->amount / $base * 100 ) . '%' ); ?></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

