<?php
/**
 * v6.2 · 분류 정리 도우미 (원장 지시 2026-10-05 — 분류별로 나눠 찾고 신청)
 *
 *  세부 분류(3단계)가 없는 품목에 이름으로 세부 분류를 제안한다.
 *  관리자가 미리보기에서 확인 · 빼기 · 바꾸기를 한 뒤 저장하면, 그 품목군 아래에 세부 분류가 없으면 만들고 넣는다.
 *  이름 · 순서는 설정 › 분류에서 언제든 고친다.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** 제안 규칙 — 위에서부터 처음 맞는 것. 치과 재료 이름(영문 · 한글 · 약어)을 본다 */
function md_inv_cat_rules() {
	return array(
		'엔도 파일'           => '/(\b[hk]\s*file\b|protaper\s*gold|spreader|file\s*check|endo\s*z)/iu',
		'엔도 충전 · 약제'     => '/(\bfile\b|protaper|gp\s*(point|cone|solvent)|paper\s*point|rc\s*p(re|er)p|naocl|calcipex|ah\s*26|\bmta\b|endo|irrigat|\bfc\b|근관)/iu',
		'버 · 연마'           => '/(bur\b|bur\s|다이어버|\bbu\b|(?<!yellow\s)stone|polish|poshing|폴리싱|폴링싱|contra|disc\b|discs|wheel|^[a-z]?\d{2,3}[a-z]*\s+314\s+0\d\d|fg\s*\d{3}|이넨스)/iu',
		'수복 · 접착 · 시멘트' => '/(resin|레진|z\s*350|bond|etch|conditioner|porcelain|luxa\s*core|rely\s*x|temp\s*bond|cem\b|ketac|dycal|theracal|caviton|퀵스|얼티메이트|z-?prime|micro\s*brush|mylar|wedge|matrix|metrix|dt\s*post|snap\s*(liquid|powder)|sealant|varnish)/iu',
		'인상 · 보철 · 교합'   => '/(yellow\s*stone|석고|light\s*body|heavy|슈퍼실|이지모노|알지네이트|alginate|인상재|퍼티|putty|tray\s*adhesive|bite\s*tray|옴니트레이|mixing|luxa\s*tip|슈어코드|fit\s*tester|체크필름|마킹|wax|리베이스|coe-?soft|스캐너|버콘|denture|덴처|투명교정)/iu',
		'외과 · 마취 · 주사'   => '/(blade|silk|렉스론|봉합|suture|카인|caine|리도|보스민|syringe|sytinge|needel|needle|니들|catheter|수액|토니켓|j-?stick|surgical|sugical|coe\s*pak|peri\s*compound|미노큐어|테트라|메스틱|얼음팩|수술용|카페낙|dexa|케로민|pca|오로키퍼)/iu',
		'소독 · 감염관리'      => '/(알콜|알코올|alcohol|과산화|포비돈|헥사메딘|소독|steriliz|멸균|정제수|필클린|naocl)/iu',
		'예방 · 불소'          => '/(불소|fluor)/iu',
		'진료 소모품'          => '/(glove|장갑|마스크|mask|apron|cotton|gauze|거즈|suction|헤드레스트|러버댐|rubber\s*dam|미러|mirror|bite\s*black|바이팅|film|x-?ray|intra\s*oral\s*tip|반창고|튜브|tube|clamp|매트|커버)/iu',
		'장비 · 유지관리'      => '/(오일|oil|spray|o-?spray|필터|filter|clean)/iu',
	);
}

function md_inv_cat_rule_for( $name ) {
	foreach ( md_inv_cat_rules() as $sub => $re ) { if ( preg_match( $re, (string) $name ) ) { return $sub; } }
	return '';
}

/** 세부 분류 없는 품목 → [ cat2 => [ 제안 => [품목...] ] ] (제안 '' = 규칙에 안 맞음) */
function md_inv_cat_suggest() {
	$out = array();
	foreach ( md_inv_items() as $it ) {
		if ( ! (int) $it->cat2 || (int) $it->cat3 ) { continue; }
		$out[ (int) $it->cat2 ][ md_inv_cat_rule_for( $it->name ) ][] = $it;
	}
	return $out;
}

/** 품목군 아래 세부 분류 — 있으면 그것, 없으면 만든다 */
function md_inv_cat3_get_or_make( $cat2, $name ) {
	global $wpdb;
	$t  = md_inv_t( 'cat' );
	$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE level = 3 AND parent_id = %d AND name = %s", (int) $cat2, $name ) );
	if ( $id ) { return $id; }
	$r = md_inv_cat_save( 0, array( 'name' => $name, 'level' => 3, 'parent_id' => (int) $cat2 ) );
	return is_wp_error( $r ) ? 0 : (int) $r;
}

function md_inv_act_catsort_apply() {
	global $wpdb;
	$pick = (array) md_inv_p( 'sub', array() );   /* 품목 id => 세부 분류 이름 */
	$on   = array_flip( array_map( 'intval', (array) md_inv_p( 'on', array() ) ) );
	$made = array(); $n = 0;
	foreach ( $pick as $iid => $name ) {
		$iid  = (int) $iid;
		$name = md_inv_txt( $name, 100 );
		if ( ! isset( $on[ $iid ] ) || '' === $name ) { continue; }
		$it = md_inv_item( $iid );
		if ( ! $it || ! (int) $it->cat2 || (int) $it->cat3 ) { continue; }
		$key = $it->cat2 . '|' . $name;
		if ( ! isset( $made[ $key ] ) ) { $made[ $key ] = md_inv_cat3_get_or_make( (int) $it->cat2, $name ); }
		if ( ! $made[ $key ] ) { continue; }
		$wpdb->update( md_inv_t( 'item' ), array( 'cat3' => $made[ $key ], 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $iid ) );
		$n++;
	}
	md_inv_log( '분류 정리', $n . '개 품목에 세부 분류 · 세부 분류 ' . count( array_unique( array_values( $made ) ) ) . '개' );
	md_inv_go( 'ok', $n . '개 품목에 세부 분류를 넣었습니다. 신청 화면에서 품목군을 누르면 세부 분류로 나눠 보입니다. 이름 · 순서는 설정 › 분류에서 바꿀 수 있습니다.', md_inv_url( array( 'iv' => 'catsort' ) ) );
}

function md_inv_view_catsort() {
	$S    = md_inv_settings();
	$sug  = md_inv_cat_suggest();
	$subs = array_keys( md_inv_cat_rules() );
	?>
	<p class="iv-crumb"><a href="<?php echo esc_url( md_inv_url( array( 'iv' => 'stock' ) ) ); ?>">← 재고</a></p>
	<h2 class="iv-h2">분류 정리 도우미 <small><?php echo esc_html( $S['label_cat3'] ); ?> 나누기</small></h2>
	<p class="iv-help"><?php echo esc_html( $S['label_cat3'] ); ?>가 없는 품목에 이름으로 제안합니다. 맞지 않는 줄은 체크를 빼거나 다른 분류로 바꾸고 저장하세요. 저장하면 신청 화면에서 <?php echo esc_html( $S['label_cat2'] ); ?>를 누를 때 <?php echo esc_html( $S['label_cat3'] ); ?>로 나눠 보입니다. 이름 · 순서 · 합치기는 설정 › 분류에서 합니다.</p>
	<?php if ( ! $sug ) { md_inv_empty( '모든 품목에 ' . $S['label_cat3'] . '가 있습니다. 새 품목이 생기면 다시 와 주세요.' ); return; } ?>
	<form method="post" class="iv-catsort">
		<?php md_inv_hidden( 'catsort_apply' ); ?>
		<div class="iv-toolbar"><button class="iv-btn iv-btn--primary">고른 것 저장</button></div>
		<?php foreach ( $sug as $c2 => $groups ) :
			$n_all = 0; foreach ( $groups as $g ) { $n_all += count( $g ); }
			$known = array();
			foreach ( md_inv_cats() as $c ) { if ( 3 === (int) $c->level && (int) $c->parent_id === (int) $c2 ) { $known[] = $c->name; } }
			$opts = array_values( array_unique( array_merge( $known, $subs ) ) );
			?>
			<section class="iv-panel">
				<h3 class="iv-h3"><?php echo esc_html( md_inv_cat_name( $c2 ) ); ?> <small><?php echo esc_html( $S['label_cat3'] ); ?> 없는 품목 <?php echo (int) $n_all; ?>개</small></h3>
				<?php
				uksort( $groups, function ( $a, $b ) use ( $subs ) { if ( '' === $a ) { return 1; } if ( '' === $b ) { return -1; } return array_search( $a, $subs, true ) - array_search( $b, $subs, true ); } );
				foreach ( $groups as $sub => $items ) : ?>
					<details class="iv-catgrp"<?php echo '' !== $sub ? ' open' : ''; ?>>
						<summary><b><?php echo '' !== $sub ? esc_html( $sub ) : '규칙에 안 맞음 — 직접 골라 주세요'; ?></b> <small><?php echo count( $items ); ?>개</small></summary>
						<label class="iv-check iv-catall"><input type="checkbox" data-grpall<?php checked( '' !== $sub ); ?>> 이 묶음 모두</label>
						<div class="iv-catrows">
						<?php foreach ( $items as $it ) : ?>
							<div class="iv-catrow">
								<label class="iv-check"><input type="checkbox" name="on[]" value="<?php echo (int) $it->id; ?>"<?php checked( '' !== $sub ); ?>> <?php echo esc_html( $it->name ); ?></label>
								<select class="iv-input iv-input--sm" name="sub[<?php echo (int) $it->id; ?>]" data-catsub>
									<option value="">—</option>
									<?php foreach ( $opts as $o ) : ?><option value="<?php echo esc_attr( $o ); ?>"<?php selected( $sub, $o ); ?>><?php echo esc_html( $o ); ?></option><?php endforeach; ?>
								</select>
							</div>
						<?php endforeach; ?>
						</div>
					</details>
				<?php endforeach; ?>
			</section>
		<?php endforeach; ?>
		<div class="iv-toolbar"><button class="iv-btn iv-btn--primary">고른 것 저장</button></div>
	</form>
	<?php
}
