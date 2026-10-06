<?php
/**
 * v4.18 · 직원 라운지 · 달력 — 생일 · 입사 기념일 · 병원 행사
 *
 *  라운지 첫 화면에 바로 월 달력이 보인다. 생일(🎂)과 입사 기념일(🎉 N주년)은 「직원 정보」 표에서
 *  자동으로 읽고, 병원 행사 · 휴진 · 교육 · 기타는 누구나 달력에서 넣고 고치고 지운다.
 *
 *  저장: 전용 테이블 하나(wp_md_events). 화면은 서버에서 그린다(PRG). 자바스크립트 없이 전부 동작.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_CAL_SCHEMA', 1 ); /* v4.18.3 · 달 파라미터는 cm — 'm' 은 워드프레스 날짜 아카이브 예약어라 404 */

function md_cal_table() { global $wpdb; return $wpdb->prefix . 'md_events'; }

/** 종류 — 색과 아이콘. 생일 · 입사 기념일은 직원 정보에서 자동 */
function md_cal_types() {
	return array(
		'birthday' => array( 'label' => '생일',       'icon' => '🎂', 'class' => 'is-birthday', 'yearly' => true,  'auto' => true ),
		'anniv'    => array( 'label' => '입사 기념일', 'icon' => '🎉', 'class' => 'is-anniv',    'yearly' => true,  'auto' => true ),
		'dayoff'   => array( 'label' => '개인 휴무 (덴트웹)', 'icon' => '🌴', 'class' => 'is-closed', 'yearly' => false, 'auto' => true ),
		'event'    => array( 'label' => '병원 행사',   'icon' => '📌', 'class' => 'is-event',    'yearly' => false, 'auto' => false ),
		'closed'   => array( 'label' => '휴진 · 휴무', 'icon' => '🌙', 'class' => 'is-closed',   'yearly' => false, 'auto' => false ),
		'edu'      => array( 'label' => '교육 · 세미나', 'icon' => '📚', 'class' => 'is-edu',    'yearly' => false, 'auto' => false ),
		'other'    => array( 'label' => '기타',       'icon' => '📎', 'class' => 'is-other',    'yearly' => false, 'auto' => false ),
	);
}

/* ============================================================
 * 테이블
 * ============================================================ */
function md_cal_maybe_install() {
	if ( (int) get_option( 'md_cal_schema', 0 ) >= MD_CAL_SCHEMA ) { return; }
	if ( ! add_option( 'md_cal_installing', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'md_cal_installing' ) < 300 ) { return; }
		delete_option( 'md_cal_installing' );
		if ( ! add_option( 'md_cal_installing', time(), '', 'no' ) ) { return; }
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = md_cal_table();
	dbDelta( "CREATE TABLE $t (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(120) NOT NULL DEFAULT '',
		type VARCHAR(16) NOT NULL DEFAULT 'event',
		date_start DATE NOT NULL,
		date_end DATE NULL,
		yearly TINYINT(1) NOT NULL DEFAULT 0,
		year_from SMALLINT NULL,
		memo TEXT NULL,
		created_by VARCHAR(60) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL,
		updated_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY date_start (date_start),
		KEY type (type)
	) " . $wpdb->get_charset_collate() . ';' );
	update_option( 'md_cal_schema', MD_CAL_SCHEMA );
	delete_option( 'md_cal_installing' );
}
add_action( 'init', 'md_cal_maybe_install', 20 );

/* ============================================================
 * 데이터
 * ============================================================ */
function md_cal_get( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . md_cal_table() . ' WHERE id = %d', (int) $id ) );
}

/** 매년 반복 날짜를 연도 범위 안의 실제 날짜들로 */
function md_cal_yearly_dates( $mmdd, $from, $to ) {
	$out = array();
	for ( $y = (int) substr( $from, 0, 4 ); $y <= (int) substr( $to, 0, 4 ); $y++ ) {
		$d = $y . '-' . $mmdd;
		if ( '02-29' === $mmdd && ! checkdate( 2, 29, $y ) ) { $d = $y . '-02-28'; }
		if ( $d >= $from && $d <= $to ) { $out[] = $d; }
	}
	return $out;
}

/**
 * 기간 안의 일정 — 직원 정보의 생일 · 입사 기념일 + 달력 일정. 결과: [ 'Y-m-d' => [ row, ... ] ]
 *  row->occurs : 실제 날짜, row->years : 몇 주년, row->auto : 직원 정보에서 온 것(달력에서 못 고침)
 */
function md_cal_between( $from, $to ) {
	global $wpdb;
	$out = array();

	/* 1) 직원 정보 */
	if ( function_exists( 'md_staff_all' ) ) {
		foreach ( md_staff_all( true ) as $s ) {
			if ( $s->birthday ) {
				foreach ( md_cal_yearly_dates( substr( $s->birthday, 5 ), $from, $to ) as $d ) {
					$out[ $d ][] = (object) array( 'id' => 's' . $s->id . 'b', 'type' => 'birthday', 'title' => $s->name, 'memo' => trim( $s->dept . ' ' . $s->position ), 'occurs' => $d, 'years' => 0, 'yearly' => 1, 'auto' => true, 'date_start' => $d, 'date_end' => null );
				}
			}
			if ( $s->hired ) {
				foreach ( md_cal_yearly_dates( substr( $s->hired, 5 ), $from, $to ) as $d ) {
					$yrs = (int) substr( $d, 0, 4 ) - (int) substr( $s->hired, 0, 4 );
					if ( $yrs < 1 ) { continue; } /* 입사한 해에는 표시하지 않는다 */
					$out[ $d ][] = (object) array( 'id' => 's' . $s->id . 'a', 'type' => 'anniv', 'title' => $s->name, 'memo' => trim( $s->dept . ' ' . $s->position ), 'occurs' => $d, 'years' => $yrs, 'yearly' => 1, 'auto' => true, 'date_start' => $d, 'date_end' => null );
				}
			}
		}
	}

	/* 1-2) 덴트웹 개인별 휴무일 (inc/staff · sync.ps1 이 한 시간마다) */
	if ( function_exists( 'md_staff_dw_dayoffs' ) ) {
		$seen = array(); /* 덴트웹에 같은 날 두 번 넣은 휴무는 한 줄로 */
		foreach ( md_staff_dw_dayoffs() as $i => $h ) {
			if ( $h['d1'] > $to || $h['d2'] < $from ) { continue; }
			$who = md_staff_dw_who( $h['sid'] );
			if ( ! $who ) { continue; } /* 라운지 명단과 이어지지 않은 사람(퇴사자 · 가상 의사)은 표시하지 않음 */
			$time = '' !== $h['t1'] ? ' ' . substr( $h['t1'], 0, 2 ) . ':' . substr( $h['t1'], 2 ) . '~' . substr( $h['t2'], 0, 2 ) . ':' . substr( $h['t2'], 2 ) : '';
			for ( $d = max( $h['d1'], $from ); $d <= min( $h['d2'], $to ); $d = date( 'Y-m-d', strtotime( $d . ' +1 day' ) ) ) {
				$k = $d . '|' . $who['sid'] . '|' . $time;
				if ( isset( $seen[ $k ] ) ) { continue; }
				$seen[ $k ] = 1;
				$out[ $d ][] = (object) array( 'id' => 's' . $who['sid'] . 'h' . $i, 'type' => 'dayoff', 'title' => $who['name'] . ( $time ? $time : ' 휴무' ), 'memo' => $h['memo'], 'occurs' => $d, 'years' => 0, 'yearly' => 0, 'auto' => true, 'date_start' => $h['d1'], 'date_end' => $h['d2'] );
			}
		}
	}

	/* 2) 달력 일정 */
	$t    = md_cal_table();
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM $t WHERE yearly = 1 OR ( date_start <= %s AND COALESCE(date_end, date_start) >= %s ) ORDER BY date_start, id",
		$to, $from
	) );
	foreach ( (array) $rows as $r ) {
		if ( (int) $r->yearly ) {
			foreach ( md_cal_yearly_dates( substr( $r->date_start, 5 ), $from, $to ) as $d ) {
				$c = clone $r; $c->occurs = $d; $c->years = $r->year_from ? (int) substr( $d, 0, 4 ) - (int) $r->year_from : 0; $c->auto = false;
				$out[ $d ][] = $c;
			}
		} else {
			$s = max( $r->date_start, $from );
			$e = min( $r->date_end ?: $r->date_start, $to );
			for ( $d = $s; $d <= $e; $d = date( 'Y-m-d', strtotime( $d . ' +1 day' ) ) ) {
				$c = clone $r; $c->occurs = $d; $c->years = 0; $c->auto = false;
				$out[ $d ][] = $c;
			}
		}
	}
	ksort( $out );
	return $out;
}

function md_cal_save( $data, $id = 0 ) {
	global $wpdb;
	$types = md_cal_types();
	$type  = isset( $types[ $data['type'] ?? '' ] ) && empty( $types[ $data['type'] ]['auto'] ) ? $data['type'] : 'event';
	$title = mb_substr( trim( sanitize_text_field( $data['title'] ?? '' ) ), 0, 120 );
	$ds    = trim( (string) ( $data['date_start'] ?? '' ) );
	$de    = trim( (string) ( $data['date_end'] ?? '' ) );
	if ( '' === $title ) { return new WP_Error( 'md_cal', '제목을 적어 주세요.' ); }
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ds ) ) { return new WP_Error( 'md_cal', '날짜를 골라 주세요.' ); }
	if ( '' !== $de && ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $de ) || $de < $ds ) ) { $de = ''; }
	$yearly = ! empty( $data['yearly'] ) ? 1 : 0;
	$yfrom  = null;
	if ( $yearly && ! empty( $data['year_from'] ) ) {
		$yfrom = (int) $data['year_from'];
		if ( $yfrom < 1950 || $yfrom > (int) current_time( 'Y' ) + 1 ) { $yfrom = null; }
	}
	$row = array(
		'title'      => $title,
		'type'       => $type,
		'date_start' => $ds,
		'date_end'   => ( $yearly || '' === $de ) ? null : $de,
		'yearly'     => $yearly,
		'year_from'  => $yfrom,
		'memo'       => mb_substr( trim( sanitize_textarea_field( $data['memo'] ?? '' ) ), 0, 500 ),
		'updated_at' => current_time( 'mysql' ),
	);
	if ( $id ) { $wpdb->update( md_cal_table(), $row, array( 'id' => (int) $id ) ); return (int) $id; }
	$row['created_by'] = mb_substr( sanitize_text_field( wp_get_current_user()->display_name ), 0, 60 );
	$row['created_at'] = current_time( 'mysql' );
	$ok = $wpdb->insert( md_cal_table(), $row );
	return $ok ? (int) $wpdb->insert_id : new WP_Error( 'md_cal', '저장하지 못했습니다.' );
}

function md_cal_delete( $id ) { global $wpdb; return (bool) $wpdb->delete( md_cal_table(), array( 'id' => (int) $id ) ); }

/** 오늘 일정 수 (허브 배지) */
function md_cal_today_count() {
	$d = current_time( 'Y-m-d' );
	$m = md_cal_between( $d, $d );
	return isset( $m[ $d ] ) ? count( $m[ $d ] ) : 0;
}

/* ============================================================
 * 폼 처리 (PRG)
 * ============================================================ */
function md_cal_handle_post() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['md_cal_action'] ) ) { return; }
	if ( ! function_exists( 'md_sup_can_use' ) || ! md_sup_can_use() ) { return; }
	$action = sanitize_key( wp_unslash( $_POST['md_cal_action'] ) );
	if ( ! isset( $_POST['md_cal_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['md_cal_nonce'] ), 'md_cal_' . $action ) ) {
		wp_die( '요청이 만료되었습니다. 뒤로 가서 다시 시도해 주세요.' );
	}
	$month = isset( $_POST['cm'] ) ? sanitize_text_field( wp_unslash( $_POST['cm'] ) ) : '';
	$month = preg_match( '/^\d{4}-\d{2}$/', $month ) ? $month : null;
	$back  = md_sup_url( array( 'app' => 'calendar', 'cm' => $month ) );
	$id    = isset( $_POST['eid'] ) ? (int) $_POST['eid'] : 0;
	$data  = array(
		'title'      => isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
		'type'       => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'event',
		'date_start' => isset( $_POST['date_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_start'] ) ) : '',
		'date_end'   => isset( $_POST['date_end'] ) ? sanitize_text_field( wp_unslash( $_POST['date_end'] ) ) : '',
		'yearly'     => ! empty( $_POST['yearly'] ),
		'year_from'  => isset( $_POST['year_from'] ) ? sanitize_text_field( wp_unslash( $_POST['year_from'] ) ) : '',
		'memo'       => isset( $_POST['memo'] ) ? wp_unslash( $_POST['memo'] ) : '',
	);
	switch ( $action ) {
		case 'add':
			$res = md_cal_save( $data );
			if ( is_wp_error( $res ) ) { $back = add_query_arg( 'err', $res->get_error_message(), $back ); }
			else {
				$goto = $data['yearly'] ? current_time( 'Y' ) . substr( $data['date_start'], 4, 3 ) : substr( $data['date_start'], 0, 7 );
				$back = md_sup_url( array( 'app' => 'calendar', 'cm' => $goto ) ) . '#e' . (int) $res;
			}
			break;
		case 'edit':
			$res = md_cal_save( $data, $id );
			$back = is_wp_error( $res ) ? add_query_arg( 'err', $res->get_error_message(), $back ) : $back . '#e' . $id;
			break;
		case 'delete':
			md_cal_delete( $id );
			break;
	}
	wp_safe_redirect( $back );
	exit;
}
add_action( 'template_redirect', 'md_cal_handle_post', 1 );

/* ============================================================
 * 화면
 * ============================================================ */
function md_cal_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	$dir = get_stylesheet_directory(); $uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/calendar.css' ) ) {
		wp_enqueue_style( 'moondental-calendar', $uri . '/assets/css/calendar.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/calendar.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'md_cal_enqueue', 31 );

/** 일정 한 줄 라벨 — 🎂 홍길동 · 🎉 김하진 입사 3주년 */
function md_cal_label( $r ) {
	$types = md_cal_types();
	$t     = $types[ $r->type ] ?? $types['other'];
	$txt   = $r->title;
	if ( 'anniv' === $r->type ) { $txt .= ! empty( $r->years ) ? ' 입사 ' . (int) $r->years . '주년' : ' 입사'; }
	return $t['icon'] . ' ' . $txt;
}

function md_cal_month( $raw ) {
	$m = sanitize_text_field( (string) $raw );
	return preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $m ) ? $m : current_time( 'Y-m' );
}

/** 월 격자 — 허브와 달력 화면이 같이 쓴다 */
function md_cal_render_grid( $m, $nav_app = 'calendar' ) {
	$types = md_cal_types();
	$first = $m . '-01';
	$days  = (int) date( 't', strtotime( $first ) );
	$last  = $m . '-' . str_pad( $days, 2, '0', STR_PAD_LEFT );
	$dow0  = (int) date( 'w', strtotime( $first ) );
	$today = current_time( 'Y-m-d' );
	$map   = md_cal_between( $first, $last );
	$prev  = date( 'Y-m', strtotime( $first . ' -1 month' ) );
	$next  = date( 'Y-m', strtotime( $first . ' +1 month' ) );
	$cur   = current_time( 'Y-m' );
	$nav   = function ( $month ) use ( $nav_app ) { return md_sup_url( array( 'app' => $nav_app, 'cm' => $month ) ); };
	$link  = function ( $r, $d ) use ( $nav_app ) {
		if ( 'dayoff' === $r->type ) { return md_sup_url( array( 'app' => 'calendar', 'cm' => substr( $d, 0, 7 ) ) ) . '#a' . $r->id; }
		if ( ! empty( $r->auto ) ) { return md_sup_url( array( 'app' => 'staff' ) ) . '#s' . (int) substr( $r->id, 1 ); }
		return md_sup_url( array( 'app' => 'calendar', 'cm' => substr( $d, 0, 7 ) ) ) . '#e' . (int) $r->id;
	};
	?>
	<div class="mdcal-bar">
		<a class="mdcal-bar__nav" href="<?php echo esc_url( $nav( $prev ) ); ?>" aria-label="이전 달">‹</a>
		<h2 class="mdcal-bar__title"><?php echo esc_html( date_i18n( 'Y년 n월', strtotime( $first ) ) ); ?></h2>
		<a class="mdcal-bar__nav" href="<?php echo esc_url( $nav( $next ) ); ?>" aria-label="다음 달">›</a>
		<?php if ( $m !== $cur ) : ?><a class="mdcal-bar__today" href="<?php echo esc_url( $nav( $cur ) ); ?>">이번 달</a><?php endif; ?>
		<?php if ( 'calendar' !== $nav_app ) : ?><a class="mdcal-bar__today mdcal-bar__add" href="<?php echo esc_url( md_sup_url( array( 'app' => 'calendar', 'cm' => $m ) ) . '#add' ); ?>">＋ 일정 추가 · 목록</a><?php endif; ?>
		<div class="mdcal-legend">
			<?php foreach ( $types as $k => $t ) : ?><span class="mdcal-legend__item <?php echo esc_attr( $t['class'] ); ?>"><?php echo esc_html( $t['icon'] . ' ' . $t['label'] ); ?></span><?php endforeach; ?>
		</div>
	</div>
	<div class="mds-card mdcal-gridwrap">
		<div class="mdcal-grid" role="grid">
			<?php foreach ( array( '일', '월', '화', '수', '목', '금', '토' ) as $i => $w ) : ?><div class="mdcal-dow<?php echo 0 === $i ? ' is-sun' : ( 6 === $i ? ' is-sat' : '' ); ?>"><?php echo esc_html( $w ); ?></div><?php endforeach; ?>
			<?php for ( $i = 0; $i < $dow0; $i++ ) : ?><div class="mdcal-cell is-pad" aria-hidden="true"></div><?php endfor; ?>
			<?php for ( $d = 1; $d <= $days; $d++ ) :
				$date = $m . '-' . str_pad( $d, 2, '0', STR_PAD_LEFT );
				$dow  = ( $dow0 + $d - 1 ) % 7;
				$rows = $map[ $date ] ?? array(); ?>
				<div class="mdcal-cell<?php echo $date === $today ? ' is-today' : ''; ?><?php echo 0 === $dow ? ' is-sun' : ( 6 === $dow ? ' is-sat' : '' ); ?><?php echo $rows ? ' has-items' : ''; ?>">
					<div class="mdcal-cell__day"><span><?php echo (int) $d; ?></span><?php if ( $date === $today ) : ?><em>오늘</em><?php endif; ?></div>
					<?php foreach ( $rows as $r ) : $t = $types[ $r->type ] ?? $types['other']; ?>
						<a class="mdcal-item <?php echo esc_attr( $t['class'] ); ?>" href="<?php echo esc_url( $link( $r, $date ) ); ?>" title="<?php echo esc_attr( trim( md_cal_label( $r ) . ( $r->memo ? ' · ' . $r->memo : '' ) ) ); ?>"><?php echo esc_html( md_cal_label( $r ) ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endfor; ?>
		</div>
	</div>
	<?php
	return $map;
}

/** 라운지 첫 화면 — 월 달력 + 다가오는 2주 */
function md_cal_render_hub() {
	$m     = md_cal_month( $_GET['cm'] ?? '' );
	$today = current_time( 'Y-m-d' );
	echo '<div class="mdcal mdcal--hub">';
	md_cal_render_grid( $m, '' );
	$to  = date( 'Y-m-d', strtotime( $today . ' +14 days' ) );
	$map = md_cal_between( $today, $to );
	if ( $map ) : ?>
		<div class="mdcal-strip">
			<span class="mdcal-strip__label">다가오는 2주</span>
			<ul class="mdcal-strip__list">
				<?php foreach ( $map as $d => $rows ) : foreach ( $rows as $r ) : $types = md_cal_types(); $t = $types[ $r->type ] ?? $types['other']; ?>
					<li class="<?php echo esc_attr( $t['class'] ); ?><?php echo $d === $today ? ' is-today' : ''; ?>">
						<span class="mdcal-strip__date"><?php echo $d === $today ? '오늘' : esc_html( date_i18n( 'n/j (D)', strtotime( $d ) ) ); ?></span>
						<span><?php echo esc_html( md_cal_label( $r ) ); ?></span>
					</li>
				<?php endforeach; endforeach; ?>
			</ul>
		</div>
	<?php endif;
	echo '</div>';
}

/** 일정 입력/수정 폼 (병원 행사 등 — 생일 · 기념일은 직원 정보에서) */
function md_cal_render_form( $r = null, $month = '' ) {
	$types = md_cal_types();
	$edit  = $r && ! empty( $r->id );
	$act   = $edit ? 'edit' : 'add';
	?>
	<form method="post" class="mdcal-form<?php echo $edit ? ' mdcal-form--edit' : ' mds-card'; ?>" <?php echo $edit ? '' : 'id="add"'; ?>>
		<input type="hidden" name="md_cal_action" value="<?php echo esc_attr( $act ); ?>">
		<input type="hidden" name="md_cal_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_cal_' . $act ) ); ?>">
		<input type="hidden" name="cm" value="<?php echo esc_attr( $month ); ?>">
		<?php if ( $edit ) : ?><input type="hidden" name="eid" value="<?php echo (int) $r->id; ?>"><?php endif; ?>
		<?php if ( ! $edit ) : ?><h2 class="mdcal-form__title">일정 추가 <small>생일 · 입사일은 <a href="<?php echo esc_url( md_sup_url( array( 'app' => 'staff' ) ) ); ?>">직원 정보</a>에서 넣으면 자동으로 표시됩니다</small></h2><?php endif; ?>
		<div class="mdcal-form__row">
			<label class="mds-field"><span>종류</span>
				<select name="type">
					<?php foreach ( $types as $k => $t ) : if ( ! empty( $t['auto'] ) ) continue; ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $r->type ?? 'event' ); ?>><?php echo esc_html( $t['icon'] . ' ' . $t['label'] ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label class="mds-field mds-field--grow"><span>제목</span><input type="text" name="title" required maxlength="120" value="<?php echo esc_attr( $r->title ?? '' ); ?>" placeholder="예) 전직원 워크숍 · 추석 연휴 휴진 · 감염관리 교육"></label>
		</div>
		<div class="mdcal-form__row">
			<label class="mds-field"><span>날짜</span><input type="date" name="date_start" required value="<?php echo esc_attr( $r->date_start ?? ( $month ? $month . '-01' : current_time( 'Y-m-d' ) ) ); ?>"></label>
			<label class="mds-field"><span>종료일 <small>(여러 날이면)</small></span><input type="date" name="date_end" value="<?php echo esc_attr( $r->date_end ?? '' ); ?>"></label>
			<label class="mds-field"><span>시작 연도 <small>(매년 반복 · N주년 계산)</small></span><input type="number" name="year_from" min="1950" max="2100" value="<?php echo esc_attr( $r->year_from ?? '' ); ?>" placeholder="예) 1995"></label>
		</div>
		<label class="mds-field"><span>메모 <small>(선택)</small></span><input type="text" name="memo" maxlength="500" value="<?php echo esc_attr( $r->memo ?? '' ); ?>" placeholder="장소 · 시간 · 준비물"></label>
		<div class="mdcal-form__foot">
			<label class="mds-check"><input type="checkbox" name="yearly" value="1" <?php checked( ! empty( $r->yearly ) ); ?>> 매년 반복 <small>(예: 개원기념일)</small></label>
			<button type="submit" class="mds-btn mds-btn--fill"><?php echo $edit ? '저장' : '추가'; ?></button>
		</div>
	</form>
	<?php
}

/** 달력 화면 (전체 · 추가 · 목록) */
function md_cal_render() {
	$types = md_cal_types();
	$m     = md_cal_month( $_GET['cm'] ?? '' );
	if ( isset( $_GET['err'] ) ) { echo '<div class="mds-notice mds-notice--warn">' . esc_html( wp_unslash( $_GET['err'] ) ) . '</div>'; }
	echo '<div class="mdcal">';
	$map = md_cal_render_grid( $m, 'calendar' );
	md_cal_render_form( null, $m );

	$seen = array(); $list = array();
	foreach ( $map as $d => $rows ) { foreach ( $rows as $r ) { if ( isset( $seen[ $r->id ] ) ) { continue; } $seen[ $r->id ] = 1; $list[] = $r; } }
	if ( $list ) : ?>
		<section class="mdcal-list">
			<h2 class="mdcal-list__title">이 달의 일정 <small><?php echo count( $list ); ?>건</small></h2>
			<?php foreach ( $list as $r ) : $t = $types[ $r->type ] ?? $types['other']; ?>
				<article class="mds-card mdcal-row <?php echo esc_attr( $t['class'] ); ?>" id="<?php echo ! empty( $r->auto ) ? 'a' . esc_attr( $r->id ) : 'e' . (int) $r->id; ?>">
					<div class="mdcal-row__head">
						<span class="mdcal-row__date"><?php echo esc_html( date_i18n( 'n/j (D)', strtotime( $r->occurs ) ) ); ?><?php if ( empty( $r->yearly ) && ! empty( $r->date_end ) && $r->date_end !== $r->date_start ) : ?> ~ <?php echo esc_html( date_i18n( 'n/j', strtotime( $r->date_end ) ) ); ?><?php endif; ?></span>
						<span class="mdcal-row__title"><?php echo esc_html( md_cal_label( $r ) ); ?></span>
						<?php if ( ! empty( $r->yearly ) ) : ?><span class="mds-status is-pending">매년</span><?php endif; ?>
						<?php if ( ! empty( $r->memo ) ) : ?><span class="mdcal-row__memo"><?php echo esc_html( $r->memo ); ?></span><?php endif; ?>
						<?php if ( 'dayoff' === $r->type ) : ?>
							<span class="mdcal-row__memo">덴트웹 「개인별 휴무」에서 고치면 한 시간 안에 반영</span>
						<?php elseif ( ! empty( $r->auto ) ) : ?>
							<a class="mdcal-btn mdcal-row__staff" href="<?php echo esc_url( md_sup_url( array( 'app' => 'staff' ) ) . '#s' . (int) substr( $r->id, 1 ) ); ?>">직원 정보에서 고치기</a>
						<?php else : ?>
							<form method="post" class="mdcal-row__del" onsubmit="return confirm('이 일정을 지울까요?');">
								<input type="hidden" name="md_cal_action" value="delete"><input type="hidden" name="md_cal_nonce" value="<?php echo esc_attr( wp_create_nonce( 'md_cal_delete' ) ); ?>"><input type="hidden" name="eid" value="<?php echo (int) $r->id; ?>"><input type="hidden" name="cm" value="<?php echo esc_attr( $m ); ?>">
								<button type="submit" class="mdcal-btn mdcal-btn--del">🗑 삭제</button>
							</form>
						<?php endif; ?>
					</div>
					<?php if ( empty( $r->auto ) ) : ?>
					<details class="mdcal-row__edit">
						<summary class="mdcal-btn">✏️ 고치기</summary>
						<?php md_cal_render_form( $r, $m ); ?>
					</details>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</section>
	<?php endif;
	echo '</div>';
}
