<?php
/**
 * 직원 전용 — 공지사항
 *
 * 본문은 서식 없는 글로 받는다. 리치 에디터를 붙이지 않는 이유 ·
 * 재고 계정은 wp-admin 에 못 들어가므로 프론트에서 써야 하는데,
 * 리치 에디터는 HTML 이 섞여 들어와 위험하다. 줄바꿈만 살려 출력한다.
 *
 * 쓰기·수정·삭제는 담당자(md_supply_manage)와 관리자만.
 * 읽기는 로그인한 직원 모두.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ============================================================
 * 데이터
 * ============================================================ */

function md_sup_notices( $limit = 50 ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$t['notice']} ORDER BY pinned DESC, created_at DESC LIMIT %d",
		(int) $limit
	) );
}

function md_sup_notice_get( $id ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['notice']} WHERE id = %d", (int) $id ) );
}

/** 최근 공지 몇 건 — 허브 화면에서 미리보기로 쓴다 */
function md_sup_notice_recent( $n = 3 ) {
	return md_sup_notices( $n );
}

function md_sup_notice_count() {
	global $wpdb;
	$t = md_sup_tables();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['notice']}" );
}

function md_sup_notice_save( $id, $title, $body, $pinned ) {
	global $wpdb;
	$t    = md_sup_tables();
	$user = wp_get_current_user();
	$now  = current_time( 'mysql' );

	$title = sanitize_text_field( $title );
	/* 본문은 태그를 전부 걷어낸다. 줄바꿈은 출력할 때 살린다. */
	$body  = wp_strip_all_tags( (string) $body );

	if ( '' === trim( $title ) ) { return new WP_Error( 'empty', '제목을 적어 주세요.' ); }

	$data = array(
		'title'      => mb_substr( $title, 0, 255 ),
		'body'       => $body,
		'pinned'     => $pinned ? 1 : 0,
		'updated_at' => $now,
	);

	if ( $id > 0 ) {
		$wpdb->update( $t['notice'], $data, array( 'id' => (int) $id ), array( '%s', '%s', '%d', '%s' ), array( '%d' ) );
		return (int) $id;
	}

	$data['author_id']   = $user->ID;
	$data['author_name'] = $user->display_name;
	$data['created_at']  = $now;
	$wpdb->insert( $t['notice'], $data, array( '%s', '%s', '%d', '%s', '%d', '%s', '%s' ) );
	return (int) $wpdb->insert_id;
}

function md_sup_notice_delete( $id ) {
	global $wpdb;
	$t = md_sup_tables();
	return $wpdb->delete( $t['notice'], array( 'id' => (int) $id ), array( '%d' ) );
}

/* ============================================================
 * 화면
 * ============================================================ */

/** 목록 · 상세 · 작성 폼을 한 화면에서 */
function md_sup_render_notice() {
	$edit_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
	$view_id = isset( $_GET['n'] ) ? (int) $_GET['n'] : 0;
	$writing = isset( $_GET['write'] ) || $edit_id > 0;
	$can     = md_sup_can_manage();

	/* 쓰기 · 수정 폼 */
	if ( $writing && $can ) {
		$n = $edit_id ? md_sup_notice_get( $edit_id ) : null;
		?>
		<form class="mds-card" method="post" action="<?php echo esc_url( md_sup_url( array( 'app' => 'notice' ) ) ); ?>">
			<?php wp_nonce_field( 'md_sup_notice', 'md_sup_nonce' ); ?>
			<input type="hidden" name="md_sup_action" value="notice">
			<input type="hidden" name="notice_id" value="<?php echo (int) $edit_id; ?>">

			<h2 class="mds-h2" style="margin-top:0"><?php echo $edit_id ? '공지 수정' : '새 공지 쓰기'; ?></h2>

			<label class="mds-formrow">
				<span>제목</span>
				<input type="text" name="title" maxlength="200" required
				       value="<?php echo $n ? esc_attr( $n->title ) : ''; ?>"
				       placeholder="예) 10월 재고 실사 안내">
			</label>

			<label class="mds-formrow">
				<span>내용</span>
				<textarea name="body" rows="10" placeholder="줄바꿈은 그대로 보입니다."><?php echo $n ? esc_textarea( $n->body ) : ''; ?></textarea>
			</label>

			<label class="mds-check" style="margin-bottom:14px">
				<input type="checkbox" name="pinned" value="1" <?php checked( $n ? (int) $n->pinned : 0, 1 ); ?>>
				맨 위에 고정
			</label>

			<div class="mds-formbtns">
				<button type="submit" class="mds-btn mds-btn--fill"><?php echo $edit_id ? '수정 저장' : '공지 올리기'; ?></button>
				<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'app' => 'notice' ) ) ); ?>">취소</a>
			</div>
		</form>
		<?php
		return;
	}

	/* 상세 */
	if ( $view_id ) {
		$n = md_sup_notice_get( $view_id );
		if ( ! $n ) {
			echo '<p class="mds-empty">공지를 찾을 수 없습니다.</p>';
		} else {
			?>
			<article class="mds-card mds-notice-view">
				<?php if ( $n->pinned ) : ?><span class="mds-pin">고정</span><?php endif; ?>
				<h2><?php echo esc_html( $n->title ); ?></h2>
				<p class="mds-notice-view__meta">
					<?php echo esc_html( $n->author_name ); ?> ·
					<?php echo esc_html( mysql2date( 'Y-m-d H:i', $n->created_at ) ); ?>
					<?php if ( $n->updated_at && $n->updated_at !== $n->created_at ) : ?>
						· 수정 <?php echo esc_html( mysql2date( 'Y-m-d H:i', $n->updated_at ) ); ?>
					<?php endif; ?>
				</p>
				<div class="mds-notice-view__body"><?php echo nl2br( esc_html( $n->body ) ); ?></div>

				<div class="mds-formbtns">
					<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'app' => 'notice' ) ) ); ?>">목록</a>
					<?php if ( $can ) : ?>
						<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_sup_url( array( 'app' => 'notice', 'edit' => (int) $n->id ) ) ); ?>">수정</a>
						<form method="post" style="display:inline"
						      onsubmit="return confirm('이 공지를 지울까요? 되돌릴 수 없습니다.');"
						      action="<?php echo esc_url( md_sup_url( array( 'app' => 'notice' ) ) ); ?>">
							<?php wp_nonce_field( 'md_sup_notice_del', 'md_sup_nonce' ); ?>
							<input type="hidden" name="md_sup_action" value="notice_del">
							<input type="hidden" name="notice_id" value="<?php echo (int) $n->id; ?>">
							<button type="submit" class="mds-btn mds-btn--danger">삭제</button>
						</form>
					<?php endif; ?>
				</div>
			</article>
			<?php
		}
		return;
	}

	/* 목록 */
	$list = md_sup_notices();
	?>
	<div class="mds-listhead">
		<h2 class="mds-h2" style="margin:0">공지사항 <span class="mds-count"><?php echo count( $list ); ?></span></h2>
		<?php if ( $can ) : ?>
			<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_sup_url( array( 'app' => 'notice', 'write' => 1 ) ) ); ?>">새 공지 쓰기</a>
		<?php endif; ?>
	</div>

	<?php if ( empty( $list ) ) : ?>
		<p class="mds-empty">아직 올라온 공지가 없습니다.</p>
	<?php else : ?>
		<ul class="mds-notices">
			<?php foreach ( $list as $n ) : ?>
				<li class="mds-noticeitem<?php echo $n->pinned ? ' is-pinned' : ''; ?>">
					<a href="<?php echo esc_url( md_sup_url( array( 'app' => 'notice', 'n' => (int) $n->id ) ) ); ?>">
						<span class="mds-noticeitem__title">
							<?php if ( $n->pinned ) : ?><span class="mds-pin">고정</span><?php endif; ?>
							<?php echo esc_html( $n->title ); ?>
						</span>
						<span class="mds-noticeitem__meta">
							<?php echo esc_html( $n->author_name ); ?> ·
							<?php echo esc_html( mysql2date( 'Y-m-d', $n->created_at ) ); ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif;
}
