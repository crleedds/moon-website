<?php
/**
 * 직원 전용 · 진료비 (v4.13)
 *
 * 구글 시트 「문치과병원 진료비 2026」을 직원 전용 화면에 끼워 넣는다.
 *  - 직원(일반) : 읽기 전용 미리보기(/preview)
 *  - 관리자     : 편집 화면(/edit) — 브라우저의 구글 계정이 시트 편집자여야 바로 고쳐진다
 *
 * 시트 ID 는 옵션 md_fees_sheet_id 로 바꿀 수 있다. 기본값은 아래.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MD_FEES_SHEET_DEFAULT', '181AJicpJlUkVAlceS-WlplK2v3_UZI3f1LTRBE6VaAk' );

function md_fees_sheet_id() {
	$id = trim( (string) get_option( 'md_fees_sheet_id', '' ) );
	if ( '' === $id || ! preg_match( '/^[A-Za-z0-9_-]{20,}$/', $id ) ) { $id = MD_FEES_SHEET_DEFAULT; }
	return $id;
}

function md_fees_can_edit() {
	return function_exists( 'md_sup_can_manage' ) && md_sup_can_manage();
}

function md_fees_url( $mode = 'preview' ) {
	$base = 'https://docs.google.com/spreadsheets/d/' . md_fees_sheet_id();
	if ( 'edit' === $mode ) { return $base . '/edit?rm=minimal&gid=0'; }
	if ( 'open' === $mode ) { return $base . '/edit?gid=0'; }
	return $base . '/preview?rm=minimal&gid=0';
}

function md_fees_enqueue() {
	if ( ! function_exists( 'md_sup_is_page' ) || ! md_sup_is_page() ) { return; }
	if ( ! function_exists( 'md_sup_current_app' ) || 'fees' !== md_sup_current_app() ) { return; }
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	if ( file_exists( $dir . '/assets/css/equipment.css' ) ) { /* 장비 대장과 같은 틀 */
		wp_enqueue_style( 'moondental-equipment', $uri . '/assets/css/equipment.css', array( 'moondental-supply' ), filemtime( $dir . '/assets/css/equipment.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'md_fees_enqueue', 31 );

function md_fees_render() {
	$edit = md_fees_can_edit();
	$src  = md_fees_url( $edit ? 'edit' : 'preview' );
	?>
	<div class="mdeq mdeq--fees">
		<div class="mds-card mdeq__head">
			<div class="mdeq__title">
				<h2>진료비</h2>
				<p class="mds-hint">
					대분류 · 중분류 · 세부설명 · 보험/비급여 · 단위 · 진료비 · 비고. 환자 안내 때 이 표를 기준으로 합니다.
					<?php if ( $edit ) : ?>
						관리자는 칸을 눌러 바로 고칠 수 있습니다(브라우저의 구글 계정이 시트 편집자여야 합니다).
					<?php else : ?>
						열람만 됩니다. 금액이 달라졌으면 경영지원실에 알려 주세요.
					<?php endif; ?>
				</p>
			</div>
			<div class="mdeq__actions">
				<?php if ( $edit ) : ?>
					<a class="mds-btn mds-btn--fill" href="<?php echo esc_url( md_fees_url( 'open' ) ); ?>" target="_blank" rel="noopener">새 창에서 열기 · 수정</a>
				<?php else : ?>
					<a class="mds-btn mds-btn--ghost" href="<?php echo esc_url( md_fees_url( 'preview' ) ); ?>" target="_blank" rel="noopener">새 창에서 크게 보기</a>
				<?php endif; ?>
			</div>
		</div>
		<div class="mds-card mdeq__frame">
			<iframe
				src="<?php echo esc_url( $src ); ?>"
				title="문치과병원 진료비 2026"
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				allow="clipboard-write"></iframe>
		</div>
		<p class="mds-hint mdeq__foot">
			시트가 보이지 않으면 시트 공유가 「링크가 있는 모든 사용자」인지 확인해 주세요.
			<?php if ( $edit ) : ?>
				수정이 안 되면 이 브라우저에서 시트 편집자 구글 계정으로 로그인한 뒤 새로고침하거나, 「새 창에서 열기 · 수정」을 누르세요.
			<?php endif; ?>
		</p>
	</div>
	<?php
}
