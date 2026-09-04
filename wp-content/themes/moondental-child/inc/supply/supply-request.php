<?php
/**
 * 재료실 — 요청 탭
 *
 * 팀을 고르고, 품목에 수량을 적어 신청한다. 직원이 매일 보는 화면이라
 * 여기서의 1초가 다른 어떤 화면보다 값이 크다.
 *
 * 서버에서 다 그려 보낸다 — 자바스크립트가 없어도 신청이 된다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 팀 선택 — 드롭다운 한 줄 (v3.66).
 *
 * v3.65 까지는 3행 × 5열 버튼판을 펼쳤다 접었다 했다. 병원 층 배치를 그대로
 * 옮긴 그림이라 처음에는 자기 자리를 눈으로 찾기 좋았지만, 매번 「팀 바꾸기」를
 * 눌러 판을 펼치고 → 칸을 찾고 → 누르면 화면이 다시 뜨는 세 단계였다.
 * 하루에 몇 번씩 하는 일에 세 단계는 두 단계 많다.
 *
 * 이제 고르는 칸 하나가 화면 맨 위에 늘 붙어 있다. 지금 어느 팀인지 항상 보이고,
 * 바꾸려면 그 자리에서 다른 이름을 고르면 된다.
 * JS 가 있으면 고르는 즉시 넘어가고, 없으면 옆의 「보기」 버튼을 누른다.
 *
 * @param array $teams   팀 목록
 * @param int   $current 지금 고른 팀 (0이면 아직 안 고름)
 */
function md_sup_render_team_picker( $teams, $current ) {
	/* 신청 폼 안에 들어가는 줄이다 — 폼 안에 폼을 넣을 수 없으므로 form 태그가 없다.
	 * 고른 값은 team_id 로 그대로 제출되고, JS 가 있으면 고르는 순간
	 * 그 팀 기준으로 화면을 다시 불러 월평균·최근 수령·즐겨찾기를 채운다. */
	?>
	<div class="mds-teampick<?php echo $current ? '' : ' is-empty'; ?>">
		<label class="mds-teampick__label" for="mds-team">신청 팀</label>

		<select class="mds-teampick__select" id="mds-team" name="team_id" required
		        data-base="<?php echo esc_attr( md_sup_url( array( 'tab' => 'request' ) ) ); ?>">
			<option value="">— 팀을 고르세요 —</option>
			<?php foreach ( $teams as $tm ) : ?>
				<option value="<?php echo (int) $tm->id; ?>" <?php selected( $current, (int) $tm->id ); ?>>
					<?php echo esc_html( $tm->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<span class="mds-teampick__ask">
			<?php echo $current
				? '이 팀 이름으로 사용량이 잡힙니다.'
				: '팀을 고르면 아래 표에 우리 팀 월평균과 최근 수령이 채워집니다.'; ?>
		</span>
	</div>
	<?php
}

/** ① 요청 */
function md_sup_render_request() {
	$my_team = md_sup_my_team_id();
	$team_id = isset( $_GET['team'] ) ? (int) $_GET['team'] : $my_team;
	$search  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$cat     = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
	$teams   = md_sup_teams();

	$vendor = isset( $_GET['vendor'] ) ? sanitize_text_field( wp_unslash( $_GET['vendor'] ) ) : '';

	/* v3.65 · 지난번에 고른 팀을 쿠키로 기억하던 것을 뺐다.
	 * 공용 계정에서는 앞사람이 고른 팀이 남아 있는 편이 더 위험하다 —
	 * 확인하지 않고 신청하면 그 사용량이 남의 팀으로 잡힌다.
	 * 계정에 소속 팀이 지정돼 있으면 위에서 이미 그 팀이 기본값이 된다. */

	/* 존재하는 팀인지 확인 — 주소가 낡았거나 팀이 지워졌을 수 있다 */
	$valid = false;
	foreach ( $teams as $tm ) { if ( (int) $tm->id === $team_id ) { $valid = true; break; } }
	if ( ! $valid ) { $team_id = 0; }

	/* v3.68 · 팀을 고르기 전에도 품목표를 보여준다.
	 *
	 * v3.67 까지는 팀을 고를 때까지 표를 띄우지 않았다. 잘못된 팀으로 신청하면
	 * 그 팀 사용량으로 잡히기 때문인데, 그 대가로 화면에 들어올 때마다 아무것도
	 * 없는 팀 고르기 화면을 한 번 거쳐야 했다. 무엇이 있는지 보려고 들어온
	 * 경우에도 마찬가지였다.
	 *
	 * 이제 표는 바로 뜨고, 팀 고르는 칸이 신청 폼 안에 required 로 들어간다.
	 * 팀 없이 신청 버튼을 누르면 브라우저가 먼저 막고, 그래도 넘어오면
	 * 서버가 막는다. 안전은 그대로 두고 한 화면을 걷어낸 셈이다.
	 * 팀을 고르기 전에는 「우리 팀 월평균」과 「최근 수령」만 비어 있다. */

	/* 방금 신청했다면 무엇을 넣었는지 먼저 보여준다 */
	if ( isset( $_GET['req'] ) && (int) $_GET['req'] > 0 ) { md_sup_render_sent_summary( (int) $_GET['req'] ); }

	/* 필터에 걸린 전 품목을 다 보여준다. 평균·최근수령은 품목마다 조회하지 않고
	 * 팀 단위로 한 번에 받아 온다 — 그러지 않으면 568개 × 2질의가 된다. */
	$items    = md_sup_items( array( 'search' => $search, 'category' => $cat, 'vendor' => $vendor ) );
	$avg_map  = md_sup_avg_map( $team_id );
	$last_map = md_sup_last_map( $team_id );

	/* 지난 신청 그대로 다시 담기 — 수량 칸을 미리 채운다 */
	$prefill = array();
	if ( isset( $_GET['repeat'] ) ) {
		$prefill = md_sup_request_qty_map( (int) $_GET['repeat'] );
	}

	/* 방금 등록한 품목 — 568줄 어딘가가 아니라 맨 위에 놓고 수량까지 채워 준다.
	 * 등록하자마자 다시 찾아 헤매야 한다면 등록할 수 있게 한 뜻이 없다. */
	$new_id  = isset( $_GET['newitem'] ) ? (int) $_GET['newitem'] : 0;
	$new_qty = isset( $_GET['nqty'] ) ? max( 1, (int) $_GET['nqty'] ) : 0;
	if ( $new_id > 0 && $new_qty > 0 && ! isset( $prefill[ $new_id ] ) ) {
		$prefill[ $new_id ] = $new_qty;
	}

	$favs = array_flip( md_sup_fav_ids( $team_id ) );

	/* 정렬 · 방금 등록한 것 → 즐겨찾기 → 우리 팀이 쓰는 것 → 나머지.
	 * 568개 중 실제로 만지는 건 보통 수십 개뿐이라, 이 정렬이 찾는 시간을 좌우한다. */
	$fresh = array();
	$fav   = array();
	$used  = array();
	$rest  = array();
	foreach ( $items as $it ) {
		if ( $new_id && (int) $it->id === $new_id )                        { $fresh[] = $it; }
		elseif ( isset( $favs[ $it->id ] ) )                               { $fav[]  = $it; }
		elseif ( isset( $avg_map[ $it->id ] ) && $avg_map[ $it->id ] > 0 )  { $used[] = $it; }
		else                                                               { $rest[] = $it; }
	}
	usort( $used, function ( $a, $b ) use ( $avg_map ) {
		return $avg_map[ $b->id ] <=> $avg_map[ $a->id ];
	} );
	$items      = array_merge( $fresh, $fav, $used, $rest );
	$fav_count  = count( $fav );
	$used_count = count( $used );
	/* 구분선이 놓일 자리 — 위로 끌어올린 줄 전부의 다음 */
	$top_count  = count( $fresh ) + $fav_count + $used_count;

	/* 즐겨찾기 주소의 앞부분 — 한 번만 만들어 줄마다 품목 번호만 이어 붙인다.
	 * 상대 주소라 도메인·경로가 568번 반복되지 않고, nonce 도 한 번만 계산한다. */
	$fav_base = '?' . http_build_query( array_filter( array(
		'app'      => md_sup_current_app(),
		'tab'      => 'request',
		'team'     => $team_id,
		'_wpnonce' => wp_create_nonce( 'md_sup_fav' ),
	) ) ) . '&fav=';

	$hint = '총 ' . number_format( count( $items ) ) . '개 품목';
	if ( ! $team_id ) {
		$hint .= '. 팀을 고르면 우리 팀이 자주 쓰는 품목이 맨 위로 올라오고 즐겨찾기를 쓸 수 있습니다.';
	} elseif ( $fav_count || $used_count ) {
		if ( $fav_count )  { $hint .= ' · 즐겨찾기 ' . $fav_count . '개'; }
		if ( $used_count ) { $hint .= ' · 우리 팀이 쓰는 ' . $used_count . '개'; }
		$hint .= '를 맨 위로 모았습니다. 별표를 눌러 즐겨찾기에 넣을 수 있습니다.';
	} else {
		$hint .= '. 별표를 눌러 자주 쓰는 품목을 맨 위로 모아 두실 수 있습니다.';
	}
	?>
	<form class="mds-filter" method="get" action="<?php echo esc_url( md_sup_url() ); ?>">
		<input type="hidden" name="tab" value="request">
		<?php md_sup_app_field(); ?>
		<input type="hidden" name="team" value="<?php echo (int) $team_id; ?>">
		<label class="mds-field">
			<span>분류</span>
			<select name="cat">
				<option value="">전체</option>
				<?php foreach ( md_sup_categories() as $c ) : ?>
					<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $cat, $c ); ?>><?php echo esc_html( $c ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field">
			<span>거래처</span>
			<select name="vendor">
				<option value="">전체</option>
				<?php foreach ( md_sup_vendors() as $v ) : ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $vendor, $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="mds-field mds-field--grow">
			<span>품목 검색</span>
			<input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="품목명 · 코드 · 거래처">
		</label>
		<button type="submit" class="mds-btn mds-btn--ghost">찾기</button>
		<?php if ( '' !== $search || '' !== $cat || '' !== $vendor ) : ?>
			<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'request', 'team' => $team_id ) ) ); ?>">필터 해제</a>
		<?php endif; ?>
	</form>

	<p class="mds-hint"><?php echo esc_html( $hint ); ?></p>

	<?php /* 즉시 검색 — 이미 화면에 있는 줄을 타이핑하는 대로 걸러낸다.
	         서버 검색(위 「찾기」)과 달리 새로 고치지 않는다. JS 가 없으면 감춰진다. */ ?>
	<div class="mds-quick" hidden>
		<input type="search" id="mds-quick" placeholder="빠른 검색 — 품목명을 입력하면 바로 걸러집니다" aria-label="화면 안에서 품목 빠르게 찾기">
		<span class="mds-quick__count" id="mds-quick-count"></span>
	</div>

	<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'request' ) ) ); ?>">
		<?php wp_nonce_field( 'md_sup_request', 'md_sup_nonce' ); ?>
		<input type="hidden" name="md_sup_action" value="request">

		<?php md_sup_render_team_picker( $teams, $team_id ); ?>

		<div class="mds-tablewrap">
			<table class="mds-table mds-table--items">
				<thead>
					<tr>
						<th class="mds-th-fav"><span class="mds-sr">즐겨찾기</span></th>
						<th>품목</th><th class="num">단가</th><th class="num">창고 재고</th>
						<th class="num">우리 팀 월평균</th><th>최근 수령</th><th class="num">신청 수량</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $items ) ) : ?>
					<tr><td colspan="7" class="mds-empty">해당하는 품목이 없습니다.</td></tr>
				<?php else : $i_row = 0; foreach ( $items as $it ) :
					$i_row++;
					$avg    = isset( $avg_map[ $it->id ] ) ? $avg_map[ $it->id ] : 0;
					$last   = isset( $last_map[ $it->id ] ) ? $last_map[ $it->id ] : null;
					$low    = ( $it->min_stock > 0 && $it->stock <= $it->min_stock );
					$is_fav = isset( $favs[ $it->id ] );
					$pre    = isset( $prefill[ $it->id ] ) ? (int) $prefill[ $it->id ] : '';
					?>
					<?php /* 줄 하나의 무게가 568배로 돌아온다 (v3.68).
					         · data-search 를 뺐다 — 이름·코드·거래처가 이미 이 줄에 적혀 있으니
					           JS 가 줄의 글자에서 만들어 쓴다. 568줄이면 30KB 남짓이었다.
					         · 즐겨찾기 링크의 주소를 짧은 상대 주소로 바꾸고 nonce 를 품목마다
					           따로 만들지 않는다. 절대 주소 + 품목별 nonce 는 한 줄에 130자가
					           넘었고(568줄이면 70KB), 서버도 nonce 를 568번 계산했다.
					         · title 은 aria-label 과 같은 말이라 뺐다. */ ?>
					<tr id="i<?php echo (int) $it->id; ?>">
						<td class="mds-td-fav">
							<?php /* 즐겨찾기는 팀별로 저장된다 — 팀을 고르기 전에는 누를 곳이 없다 */
							if ( $team_id ) : ?>
								<a class="mds-fav<?php echo $is_fav ? ' is-on' : ''; ?>"
								   data-fav="<?php echo (int) $it->id; ?>"
								   href="<?php echo esc_attr( $fav_base . (int) $it->id ); ?>"
								   aria-label="<?php echo esc_attr( $it->name . ( $is_fav ? ' 즐겨찾기 해제' : ' 즐겨찾기' ) ); ?>">
									<?php echo $is_fav ? '★' : '☆'; ?>
								</a>
							<?php else : ?>
								<span class="mds-fav is-mute" aria-hidden="true">☆</span>
							<?php endif; ?>
						</td>
						<td class="mds-item" data-label="품목">
							<b><?php echo esc_html( $it->name ); ?></b>
							<span class="mds-item__meta"><?php echo esc_html( $it->code . ' · ' . $it->vendor . ( $it->unit ? ' · ' . $it->unit : '' ) ); ?></span>
						</td>
						<td class="num" data-label="단가"><?php echo esc_html( number_format( (int) $it->price ) ); ?></td>
						<td class="num<?php echo $low ? ' is-low' : ''; ?>" data-label="창고 재고">
							<?php echo esc_html( number_format( (int) $it->stock ) ); ?>
							<?php if ( $low ) : ?><span class="mds-flag">부족</span><?php endif; ?>
						</td>
						<td class="num mds-avg" data-label="우리 팀 월평균" data-avg="<?php echo esc_attr( $avg ); ?>"><?php echo esc_html( $avg > 0 ? $avg : '—' ); ?></td>
						<td class="mds-last" data-label="최근 수령">
							<?php if ( $last ) : ?>
								<?php echo esc_html( mysql2date( 'n/j', $last->created_at ) . ' · ' . (int) $last->qty ); ?>
							<?php else : ?>—<?php endif; ?>
						</td>
						<?php /* +/− 버튼과 초과 사유 칸은 서버에서 그리지 않는다 (v3.66).
						         둘 다 자바스크립트가 있어야 쓸모가 있는 것들인데, 568줄에
						         미리 박아 두면 그것만으로 200KB 가 넘는 HTML 이 오간다.
						         JS 가 화면을 열 때 만들어 붙이므로 동작은 그대로다. */ ?>
						<td class="num" data-label="신청 수량">
							<input class="mds-qty" type="number" min="0" step="1" inputmode="numeric"
							       name="qty[<?php echo (int) $it->id; ?>]" value="<?php echo esc_attr( $pre ); ?>"
							       data-avg="<?php echo esc_attr( $avg ); ?>"
							       data-price="<?php echo (int) $it->price; ?>"
							       data-item="<?php echo (int) $it->id; ?>"
							       aria-label="<?php echo esc_attr( $it->name . ' 신청 수량' ); ?>">
						</td>
					</tr>
					<?php if ( $top_count && $i_row === $top_count ) : ?>
						<tr class="mds-divider"><td colspan="7">여기부터는 우리 팀이 최근 3개월간 받아가지 않은 품목입니다</td></tr>
					<?php endif; ?>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>

		<div class="mds-submit" id="mds-submit">
			<label class="mds-check"><input type="checkbox" name="urgent" value="1"> 긴급 (오늘 필요)</label>
			<input class="mds-note" type="text" name="note" placeholder="담당자에게 전할 메모 (선택)" maxlength="200">
			<button type="submit" class="mds-btn mds-btn--fill">
				<?php echo $team_id
					? esc_html( md_sup_team_name( $team_id ) ) . ' 이름으로 신청'
					: '신청하기'; ?>
			</button>
		</div>

		<?php /* 고정 바 — 수량을 하나라도 적으면 화면 아래에 붙어 따라온다.
		         568줄을 스크롤해 내려가 제출 버튼을 찾지 않아도 되게.
		         팀을 아직 안 골랐으면 그것부터 알려 준다 (v3.68). */ ?>
		<div class="mds-cart" id="mds-cart" hidden>
			<div class="mds-cart__inner">
				<span class="mds-cart__team<?php echo $team_id ? '' : ' is-mute'; ?>">
					<?php echo $team_id ? esc_html( md_sup_team_name( $team_id ) ) : '팀을 고르세요'; ?>
				</span>
				<span class="mds-cart__sum">
					<b id="mds-cart-count">0</b>개 품목
					<span class="mds-cart__won">약 <b id="mds-cart-total">0</b>원</span>
				</span>
				<button type="submit" class="mds-btn mds-btn--fill">신청하기</button>
			</div>
		</div>
	</form>

	<?php
	/* 신청 폼 바깥에 둔다 — 폼 안에 폼을 넣을 수 없다 */
	md_sup_render_newitem_form( $team_id );

	/* 「우리 팀」이 정해져야 볼 수 있는 목록이다.
	 * 팀 없이 부르면 모든 팀의 신청이 섞여 나온다. */
	if ( $team_id ) { md_sup_render_my_requests( $team_id ); }
}

/**
 * 목록에 없는 품목을 직원이 그 자리에서 등록한다 (v3.65).
 *
 * 예전에는 이름만 적어 신청서에 딸려 보냈고, 담당자가 그걸 보고 품목을
 * 옮겨 적은 뒤에야 다시 신청할 수 있었다. 왕복이 한 번 더 있었던 셈이다.
 * 이제 여기서 등록하면 곧바로 카탈로그에 들어가고, 위 표 맨 위에 얹혀
 * 수량까지 채워진 채로 나타난다. 다음 달부터는 그냥 검색해서 쓰면 된다.
 *
 * 카탈로그가 지저분해지지 않게
 *   분류·거래처는 담당자가 만든 목록에서만 고른다. 모르면 비워 두면 되고,
 *   단가·적정재고는 아예 묻지 않는다 — 직원이 알 수 없는 값이고,
 *   비어 있는 채로 두어야 담당자가 채울 것이 남았음을 알아본다.
 */
function md_sup_render_newitem_form( $team_id ) {
	$units    = array( 'ea', 'box', '갑', '팩', '봉', '병', '통', '롤', '각' );
	$vendors  = md_sup_vendors();
	$cats     = md_sup_categories();
	?>
	<details class="mds-newitem">
		<summary class="mds-newitem__sum">찾으시는 품목이 목록에 없나요? — 직접 등록하기</summary>

		<p class="mds-hint" style="margin:12px 0 14px">
			이름과 규격을 되도록 자세히 적어 주세요 (예: <b>오스템 KS 픽스처 4.0×10</b>).
			등록하면 위 품목표 맨 위에 올라오고 수량이 채워집니다 — 확인하신 뒤 신청 버튼을 누르시면 됩니다.
			단가와 적정재고는 담당자가 채웁니다.
			<br><b>먼저 등록하고 그다음에 수량을 적어 주세요</b> — 등록하면 화면이 새로 열려서,
			위에 적어 두신 수량은 지워집니다.
		</p>

		<form class="mds-card" method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'request', 'team' => (int) $team_id ) ) ); ?>">
			<?php wp_nonce_field( 'md_sup_newitem', 'md_sup_nonce' ); ?>
			<input type="hidden" name="md_sup_action" value="newitem">
			<input type="hidden" name="team_id" value="<?php echo (int) $team_id; ?>">

			<label class="mds-formrow">
				<span>품목명 · 규격 · 브랜드 (필수)</span>
				<input type="text" name="name" required maxlength="200"
				       placeholder="오스템 KS 픽스처 4.0×10">
			</label>

			<div class="mds-grid2">
				<label class="mds-formrow">
					<span>거래처 — 모르시면 비워 두세요</span>
					<select name="vendor">
						<option value="">— 모름 —</option>
						<?php foreach ( $vendors as $v ) : ?>
							<option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $v ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="mds-formrow">
					<span>분류 — 모르시면 비워 두세요</span>
					<select name="category">
						<option value="">— 모름 —</option>
						<?php foreach ( $cats as $c ) : ?>
							<option value="<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $c ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="mds-formrow">
					<span>단위</span>
					<input type="text" name="unit" list="mds-newunits" maxlength="20" placeholder="ea · box · 갑">
				</label>

				<label class="mds-formrow">
					<span>신청 수량</span>
					<input type="number" name="qty_new" min="1" step="1" inputmode="numeric" value="1">
				</label>
			</div>

			<datalist id="mds-newunits"><?php foreach ( $units as $u ) : ?><option value="<?php echo esc_attr( $u ); ?>"></option><?php endforeach; ?></datalist>

			<div class="mds-formbtns">
				<button type="submit" class="mds-btn mds-btn--fill">등록하고 신청에 담기</button>
				<span class="mds-hint" style="margin:0">
					같은 이름이 이미 있으면 새로 만들지 않고 그 품목을 찾아 드립니다.
				</span>
			</div>
		</form>
	</details>
	<?php
}

/**
 * 우리 팀 최근 신청.
 *
 * v3.63 까지는 「출고 완료」라고만 떴다. 5개 신청했는데 3개만 나갔어도
 * 화면은 똑같아서, 직원은 나머지가 오는 중인지 안 오는 것인지 알 수 없었다.
 * 이제 줄마다 신청 대 출고를 펼쳐 보고, 모자라게 나간 신청에는 표시를 단다.
 * 아직 처리 전인 신청은 여기서 직접 물릴 수 있다.
 */
function md_sup_render_my_requests( $team_id ) {
	$mine = md_sup_requests( array( 'team_id' => $team_id, 'limit' => 8 ) );
	if ( empty( $mine ) ) { return; }

	$ids   = array();
	foreach ( $mine as $r ) { $ids[] = (int) $r->id; }
	$lines = md_sup_lines_for_requests( $ids );
	?>
	<h2 class="mds-h2">우리 팀 최근 신청</h2>
	<p class="mds-hint">
		매달 비슷한 것을 신청하신다면 「이대로 다시 담기」를 누르세요. 수량이 그대로 채워집니다.
		아직 출고 전이면 「신청 취소」로 물릴 수 있습니다.
	</p>
	<div class="mds-tablewrap">
		<table class="mds-table">
			<thead><tr><th>신청일</th><th class="num">품목 수</th><th>상태</th><th>메모</th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $mine as $r ) :
				$rl      = isset( $lines[ (int) $r->id ] ) ? $lines[ (int) $r->id ] : array();
				$partial = false;
				if ( 'done' === $r->status ) {
					foreach ( $rl as $ln ) {
						if ( (int) $ln->qty_out < (int) $ln->qty_req ) { $partial = true; break; }
					}
				}
				?>
				<tr>
					<td data-label="신청일">
						<?php echo esc_html( mysql2date( 'Y-m-d H:i', $r->created_at ) ); ?>
						<?php if ( $r->urgent ) : ?> <span class="mds-flag">긴급</span><?php endif; ?>
					</td>
					<td class="num" data-label="품목 수"><?php echo (int) $r->line_count; ?>건</td>
					<td data-label="상태">
						<span class="mds-status is-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( md_sup_status_label( $r->status ) ); ?></span>
						<?php if ( $partial ) : ?> <span class="mds-flag">일부만 출고</span><?php endif; ?>
					</td>
					<td class="mds-memo" data-label="메모"><?php echo esc_html( $r->note ); ?></td>
					<td class="num">
						<span class="mds-rowbtns">
							<a class="mds-mini" href="<?php echo esc_url( md_sup_url( array( 'tab' => 'request', 'team' => $team_id, 'repeat' => (int) $r->id ) ) ); ?>">이대로 다시 담기</a>
							<?php if ( 'pending' === $r->status ) : ?>
								<form method="post" action="<?php echo esc_url( md_sup_url( array( 'tab' => 'request', 'team' => $team_id ) ) ); ?>" class="mds-inline">
									<?php wp_nonce_field( 'md_sup_cancel', 'md_sup_nonce' ); ?>
									<input type="hidden" name="md_sup_action" value="cancel">
									<input type="hidden" name="req_id" value="<?php echo (int) $r->id; ?>">
									<input type="hidden" name="team_id" value="<?php echo (int) $team_id; ?>">
									<button type="submit" class="mds-mini mds-mini--warn"
									        onclick="return confirm('이 신청을 취소할까요?');">신청 취소</button>
								</form>
							<?php endif; ?>
						</span>
					</td>
				</tr>

				<?php if ( $rl ) : ?>
					<tr class="mds-reqlines">
						<td colspan="5">
							<details>
								<summary><?php echo count( $rl ); ?>개 품목 · 신청한 것과 나간 것 보기</summary>
								<ul class="mds-sent__list">
									<?php foreach ( $rl as $ln ) :
										$req_q = (int) $ln->qty_req;
										$out_q = (int) $ln->qty_out;
										$unit  = $ln->unit ? ' ' . $ln->unit : ''; ?>
										<li>
											<span><?php echo esc_html( $ln->name ); ?></span>
											<b<?php echo ( 'done' === $r->status && $out_q < $req_q ) ? ' class="is-short"' : ''; ?>>
												<?php if ( 'done' === $r->status ) : ?>
													신청 <?php echo esc_html( $req_q . $unit ); ?> → 출고 <?php echo esc_html( $out_q . $unit ); ?>
												<?php else : ?>
													<?php echo esc_html( $req_q . $unit ); ?>
												<?php endif; ?>
											</b>
										</li>
									<?php endforeach; ?>
								</ul>
								<?php /* isset 로 감싸는 이유 — reject_reason 은 4단계에서 붙는 열이다.
								         마이그레이션은 담당자·관리자가 들어올 때 돌므로, 그 전에 직원이 먼저
								         이 화면을 열면 아직 없는 열을 읽게 된다. 없으면 조용히 넘어간다. */ ?>
								<?php if ( 'rejected' === $r->status && isset( $r->reject_reason ) && '' !== trim( (string) $r->reject_reason ) ) : ?>
									<p class="mds-hint" style="margin:10px 0 0">반려 사유 · <b><?php echo esc_html( $r->reject_reason ); ?></b></p>
								<?php endif; ?>
							</details>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/** 신청 직후 요약 — 무엇을 몇 개 넣었는지 확인시켜 준다 */
function md_sup_render_sent_summary( $req_id ) {
	$r = md_sup_request_summary( $req_id );
	if ( ! $r ) { return; }
	$lines = md_sup_request_lines( $req_id );
	?>
	<section class="mds-card mds-sent">
		<h2 class="mds-h2" style="margin-top:0">신청이 접수되었습니다</h2>
		<p class="mds-sent__meta">
			<b><?php echo esc_html( $r->team_name ); ?></b> ·
			<?php echo esc_html( mysql2date( 'Y-m-d H:i', $r->created_at ) ); ?> ·
			<?php echo (int) $r->line_count; ?>개 품목 ·
			약 <b><?php echo esc_html( number_format( (int) $r->amount ) ); ?></b>원
			<?php if ( $r->urgent ) : ?><span class="mds-flag">긴급</span><?php endif; ?>
		</p>
		<ul class="mds-sent__list">
			<?php foreach ( $lines as $ln ) : ?>
				<li>
					<span><?php echo esc_html( $ln->name ); ?></span>
					<b><?php echo (int) $ln->qty_req; ?><?php echo $ln->unit ? esc_html( ' ' . $ln->unit ) : ''; ?></b>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="mds-hint" style="margin:14px 0 0">
			재고 담당자가 확인 후 출고합니다. 진행 상태는 아래 「우리 팀 최근 신청」에서 보실 수 있고,
			출고 전이라면 거기서 취소하실 수 있습니다.
		</p>
	</section>
	<?php
}
