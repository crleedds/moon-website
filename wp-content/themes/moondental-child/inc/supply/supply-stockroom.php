<?php
/**
 * 재료실 — 입고 · 재고(실사) 탭 (재고 담당자 전용)
 *
 * 두 화면이 한 파일에 있는 이유
 *   같은 품목표를 보면서 「들어온 수량을 적는다」와 「센 수량을 적는다」만
 *   다르다. 나란히 두면 한쪽만 고쳐 두 화면이 어긋나는 일이 줄어든다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ④ 입고 */
function md_sup_render_inbound() {
	$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$vendor = isset( $_GET['vendor'] ) ? sanitize_text_field( wp_unslash( $_GET['vendor'] ) ) : '';
	$items  = ( '' === $search && '' === $vendor )
		? md_sup_items( array( 'low_only' => true ) )
		: md_sup_items( array( 'search' => $search, 'vendor' => $vendor ) );

	$vendors = md_sup_vendors();
	$open    = md_sup_po_open_map();
	$open_po = md_sup_po_list( array( 'status' => 'ordered', 'limit' => 30 ) );
	?>

	<?php if ( $open_po ) : ?>
		<div class="mds-notice mds-notice--ok">
			주문해 둔 발주가 <b><?php echo count( $open_po ); ?>건</b> 있습니다.
			발주로 들어온 물건은 <a href="<?php echo esc_url( md_sup_url( array( 'tab' => 'order' ) ) ); ?>">발주 화면</a>에서 받으시면
			어떤 주문의 물건인지까지 함께 남습니다.
		</div>
	<?php endif; ?>

	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="inbound">
		<?php md_sup_app_field(); ?>
		<label class="mds-field">
			<span>거래처</span>
			<select name="vendor">
				<option value="">전체</option>
				<?php foreach ( $vendors as $v ) : ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $vendor, $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field mds-field--grow">
			<span>품목 검색</span>
			<input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="품목명 · 코드">
		</label>
		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
	</form>

	<p class="mds-hint">
		<?php echo ( '' === $search && '' === $vendor )
			? '적정재고 이하로 떨어진 품목입니다. 들어온 수량을 적고 저장하세요. 「주문 중」은 이미 발주해 둔 수량입니다.'
			: esc_html( '검색 결과 ' . count( $items ) . '건입니다.' ); ?>
	</p>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'inbound' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_inbound', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="inbound">
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>품목</th><th>거래처</th><th class="num">단가</th><th class="num">현재고</th><th class="num">적정</th><th class="num">주문 중</th><th class="num">입고 수량</th></tr></thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="7" class="mds-empty">부족한 품목이 없습니다.</td></tr>
				<?php else : foreach ( $items as $it ) :
					$ordered = isset( $open[ $it->id ] ) ? (int) $open[ $it->id ] : 0; ?>
					<tr>
						<td class="mds-item">
							<b><a class="mds-plain" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'history', 'item' => (int) $it->id ) ) ); ?>"><?php echo esc_html( $it->name ); ?></a></b>
							<span class="mds-item__meta"><?php echo esc_html( $it->code . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span>
						</td>
						<td><?php echo esc_html( $it->vendor ); ?></td>
						<td class="num"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
						<td class="num<?php echo ( $it->min_stock > 0 && $it->stock <= $it->min_stock ) ? ' is-low' : ''; ?>"><?php echo esc_html( number_format( (int) $it->stock ) ); ?></td>
						<td class="num"><?php echo (int) $it->min_stock; ?></td>
						<td class="num"><?php echo $ordered ? esc_html( number_format( $ordered ) ) : '—'; ?></td>
						<td class="num"><input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
							name="inqty[<?php echo (int) $it->id; ?>]" value=""
							aria-label="<?php echo esc_attr( $it->name . ' 입고 수량' ); ?>"></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<div class="mds-submit"><button type="submit" class="mds-btn mds-btn--fill">입고 저장</button></div>
	</form>
	<?php
}

/** ⑤ 재고 · 실사 */
function md_sup_render_inventory() {
	$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$cat    = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
	$low    = isset( $_GET['low'] ) && '1' === $_GET['low'];
	$items  = md_sup_items( array( 'search' => $search, 'category' => $cat, 'low_only' => $low ) );

	/* 적정재고 제안의 근거 — 전 팀 합계 월평균 (질의 한 번) */
	$lead    = isset( $_GET['lead'] ) ? (float) $_GET['lead'] : 1.5;
	if ( $lead <= 0 || $lead > 12 ) { $lead = 1.5; }
	$avg_all = md_sup_avg_map_all( 3 );

	/* 아직 적정재고가 0 인데 제안값이 있는 품목 수 — 일괄 적용 버튼에 쓴다 */
	$fillable = 0;
	foreach ( $items as $it ) {
		if ( (int) $it->min_stock > 0 ) { continue; }
		if ( md_sup_suggest_min( isset( $avg_all[ $it->id ] ) ? $avg_all[ $it->id ] : 0, $lead ) > 0 ) { $fillable++; }
	}
	?>
	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="inventory">
		<?php md_sup_app_field(); ?>
		<label class="mds-field">
			<span>분류</span>
			<select name="cat">
				<option value="">전체</option>
				<?php foreach ( md_sup_categories() as $c ) : ?>
					<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $cat, $c ); ?>><?php echo esc_html( $c ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field mds-field--grow">
			<span>품목 검색</span>
			<input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="품목명 · 코드 · 거래처">
		</label>
		<label class="mds-check"><input type="checkbox" name="low" value="1" <?php checked( $low ); ?>> 부족한 것만</label>
		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
	</form>

	<div class="mds-tools">
		<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'inventory', 'export' => 'stock' ) ) ); ?>">엑셀(CSV) 내려받기</a>
		<button type="button" class="mds-btn mds-btn--ghost" onclick="window.print()">실사표 인쇄</button>
	</div>

	<?php /* 적정재고 제안 — v3.64.
	         손으로 넣게 두면 대부분 0 으로 남고, 0 이면 「부족」이 영영 안 떠서
	         입고·발주 화면이 빈 채로 있게 된다. 쓰던 만큼으로 채워 준다. */ ?>
	<form class="mds-card mds-minsug" method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'inventory' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_minstock', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="minstock">
		<h3 class="mds-h3" style="margin-top:0">적정재고 제안</h3>
		<p class="mds-hint">
			최근 3개월 사용량으로 계산합니다 — <b>월평균 × 여유 개월</b>.
			여유 개월은 「주문해서 들어오기까지 걸리는 기간 + 그동안 쓸 만큼」으로 잡으세요.
			<b>이미 값을 정해 둔 품목은 건드리지 않습니다</b> — 아직 0 인 품목만 채웁니다.
		</p>
		<div class="mds-formbtns">
			<label class="mds-field">
				<span>여유 개월</span>
				<input type="number" name="lead" min="0.5" max="12" step="0.5" value="<?php echo esc_attr( $lead ); ?>">
			</label>
			<button type="submit" class="mds-btn mds-btn--fill">
				아직 0 인 품목 <?php echo (int) $fillable; ?>개에 제안값 넣기
			</button>
		</div>
	</form>

	<p class="mds-hint">
		센 수량을 「실사」 칸에 적고 저장하면 장부와의 차이가 조정 기록으로 남습니다.
		처음 시작할 때 여기에 현재 재고를 적어 넣으시면 됩니다.
	</p>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'inventory' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_adjust', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="adjust">
		<div class="mds-tablewrap">
			<table class="mds-table">
				<thead><tr><th>품목</th><th>거래처</th><th class="num">단가</th><th class="num">장부</th><th class="num">적정</th><th class="num">제안</th><th class="num">실사</th></tr></thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="7" class="mds-empty">해당하는 품목이 없습니다.</td></tr>
				<?php else : foreach ( $items as $it ) :
					$sug = md_sup_suggest_min( isset( $avg_all[ $it->id ] ) ? $avg_all[ $it->id ] : 0, $lead ); ?>
					<tr>
						<td class="mds-item">
							<b><a class="mds-plain" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'history', 'item' => (int) $it->id ) ) ); ?>"><?php echo esc_html( $it->name ); ?></a></b>
							<span class="mds-item__meta"><?php echo esc_html( $it->code . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span>
						</td>
						<td><?php echo esc_html( $it->vendor ); ?></td>
						<td class="num"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
						<td class="num<?php echo ( $it->min_stock > 0 && $it->stock <= $it->min_stock ) ? ' is-low' : ''; ?>"><?php echo esc_html( number_format( (int) $it->stock ) ); ?></td>
						<td class="num"><?php echo (int) $it->min_stock; ?></td>
						<td class="num mds-sug"><?php echo $sug > 0 ? (int) $sug : '—'; ?></td>
						<td class="num"><input class="mds-qty" type="number" step="1" inputmode="numeric"
							name="count[<?php echo (int) $it->id; ?>]" value=""
							aria-label="<?php echo esc_attr( $it->name . ' 실사 수량' ); ?>"></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<div class="mds-submit"><button type="submit" class="mds-btn mds-btn--fill">실사 반영</button></div>
	</form>
	<?php
}
