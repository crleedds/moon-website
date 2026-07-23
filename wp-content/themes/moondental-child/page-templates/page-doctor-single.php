<?php
/**
 * Template Name: 의료진 상세 (개별)
 * Template Post Type: page
 *
 * /의료진/{이름}/ URL 로 자동 라우팅된다.
 * 모든 텍스트는 사용자 정의하기에서 편집 가능 (의료진 상세 페이지 패널).
 *
 * @package moondental-child
 */

$slug   = urldecode( (string) get_query_var( 'doctor_slug' ) );
$doctor = $slug ? moondental_get_doctor_by_slug( $slug ) : null;

/* 의료진 없으면 404 */
if ( ! $doctor ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( '404' );
	return;
}

get_header();

$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$kakao_url  = $info['kakao_url'] ?? '';
$naver_book = $info['naver_place'] ?? '';

/* Customizer key 매핑 — v3.38.6 · Customizer 편집 가능 (한 줄에 하나 · '이름|slug')
 *  moondental_get_team_with_customizer와 동일 */
$name_to_key_default = "문은수|munes\n이승주|leesj\n이수연|leesu\n권혜진|kwon\n문지현|munji\n이창률|leech\n이영일|leeyi\n김세일|kimsi\n정석형|jeong";
$name_to_key = array();
foreach ( md_parse_lines( md_content( 'doctor_slug_map', $name_to_key_default ) ) as $line ) {
	$parts = array_map( 'trim', explode( '|', $line, 2 ) );
	if ( count( $parts ) === 2 && $parts[0] !== '' && $parts[1] !== '' ) {
		$name_to_key[ $parts[0] ] = $parts[1];
	}
}
$doc_key = $name_to_key[ $doctor['name'] ] ?? '';

/* 데이터 추출 — Customizer override 우선, 없으면 fallback */
$bio_lines = $doctor['bio'] ?? array();
if ( is_string( $bio_lines ) ) {
	$bio_lines = array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", $bio_lines ) ) );
}

/* Intro paragraph */
$intro = md_content( "doc_{$doc_key}_intro", '' );
if ( ! $intro ) {
	$intro = $doctor['philosophy'] ?? '';
}

/* 사진 옆 자격·약력 리스트
 * v3.30.8 · 사용자 요청 · 대표 4개만 발췌하지 않고 전체 약력을 사진 옆에 표시.
 * Customizer에 별도 자격 리스트가 있으면 그것을, 없으면 전체 bio 사용. */
$creds_text = md_content( "doc_{$doc_key}_credentials", '' );
if ( $creds_text ) {
	$credentials = array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", $creds_text ) ) );
} else {
	$credentials = $bio_lines;
}

/* Q&A — "질문 | 답변" 라인 파싱 */
$qa_text = md_content( "doc_{$doc_key}_qa", '' );
$qas = array();
foreach ( preg_split( "/\r\n|\r|\n/", $qa_text ) as $line ) {
	$line = trim( $line );
	if ( ! $line || strpos( $line, '#' ) === 0 ) continue;
	$parts = array_map( 'trim', explode( '|', $line, 2 ) );
	if ( count( $parts ) >= 2 ) {
		$qas[] = array( 'q' => $parts[0], 'a' => $parts[1] );
	}
}

/* 관심 분야 */
$interests = array_filter( array_map( 'trim',
	preg_split( "/\r\n|\r|\n/", md_content( "doc_{$doc_key}_interests", '' ) )
) );

/* 사진 */
$photo_url   = moondental_doctor_photo_url( $doctor['photo'] ?? '' );
$photo_zoom  = isset( $doctor['photo_zoom'] ) ? (float) $doctor['photo_zoom'] : 1.0;
$photo_ty    = isset( $doctor['photo_ty'] )   ? (float) $doctor['photo_ty']   : 0.0;
$photo_style = sprintf(
	'transform: translateY(%s%%) scale(%s); transform-origin: center top; object-position: center top;',
	esc_attr( number_format( $photo_ty,   1 ) ),
	esc_attr( number_format( $photo_zoom, 2 ) )
);

/* 다른 의료진 — 동일 그룹 또는 전체 중 8명 */
$all_groups = function_exists( 'moondental_get_team_with_customizer' )
	? moondental_get_team_with_customizer()
	: moondental_get_team();
$others = array();
foreach ( $all_groups as $g ) {
	foreach ( $g['members'] as $m ) {
		if ( $m['name'] !== $doctor['name'] ) $others[] = $m;
	}
}
?>

<!-- ============ Hero ============ -->
<section class="md-docsingle-hero">
	<div class="md-container">
		<span class="md-docsingle-hero__eyebrow"><?php echo esc_html( md_content( 'doc_single_intro_eyebrow', 'DOCTOR PROFILE' ) ); ?></span>
		<h1 class="md-docsingle-hero__title">
			<?php echo esc_html( $doctor['name'] ); ?>
			<small><?php echo esc_html( $doctor['role'] ); ?></small>
		</h1>
		<?php if ( $intro ) : ?>
			<p class="md-docsingle-hero__intro">
				"<?php echo esc_html( $intro ); ?>"
			</p>
		<?php endif; ?>
		<?php // v3.30.9 · Hero 예약 CTA (전화·네이버·카톡) 제거 (사용자 요청) ?>
	</div>
</section>

<!-- ============ Doctor card (photo + credentials) ============ -->
<section class="md-section">
	<div class="md-container">
		<article class="md-docsingle-card">
			<div class="md-docsingle-card__photo">
				<?php if ( $photo_url ) : ?>
					<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $doctor['name'] ); ?>" loading="lazy" style="<?php echo esc_attr( $photo_style ); ?>">
				<?php else : ?>
					<span class="md-docsingle-card__initial"><?php echo esc_html( mb_substr( $doctor['name'], -2 ) ); ?></span>
				<?php endif; ?>
			</div>
			<div class="md-docsingle-card__body">
				<span class="md-docsingle-card__role"><?php echo esc_html( $doctor['role'] ); ?></span>
				<h2 class="md-docsingle-card__name"><?php echo esc_html( $doctor['name'] ); ?></h2>
				<?php if ( ! empty( $credentials ) ) : ?>
					<ul class="md-docsingle-card__creds">
						<?php foreach ( $credentials as $c ) : ?>
							<li><span aria-hidden="true">✓</span> <?php echo esc_html( $c ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</article>
	</div>
</section>

<!-- ============ Q&A ============ -->
<?php if ( ! empty( $qas ) ) : ?>
<section class="md-section md-section--surface" id="doctor-qa">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'doc_single_qa_eyebrow', '원장 인터뷰' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'doc_single_qa_title', '원장님께 직접 들어봅니다' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( md_content( 'doc_single_qa_lead', '환자분이 가장 궁금해하시는 질문에 원장님이 직접 답변드립니다.' ) ) ); ?>
			</p>
		</header>

		<div class="md-docsingle-qa">
			<?php foreach ( $qas as $idx => $qa ) : ?>
				<div class="md-docsingle-qa__item<?php echo $idx % 2 === 1 ? ' is-alt' : ''; ?>">
					<div class="md-docsingle-qa__q">
						<span class="md-docsingle-qa__no">Q<?php echo $idx + 1; ?></span>
						<h3><?php echo esc_html( $qa['q'] ); ?></h3>
					</div>
					<div class="md-docsingle-qa__a">
						<p><?php echo wp_kses_post( wpautop( $qa['a'] ) ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ============ 관심 분야 (선택 · 별도 섹션) ============ -->
<?php /* v3.30.8 · 학력·경력은 이미 사진 옆에 전체 표시 · 관심 분야만 별도 유지 */ ?>
<?php if ( ! empty( $interests ) ) : ?>
<section class="md-section">
	<div class="md-container md-container--narrow">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'doc_single_edu_eyebrow', 'Focus' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'doc_single_interests_title', '관심 분야' ) ); ?></h2>
		</header>

		<div class="md-docsingle-tags md-docsingle-tags--center">
			<?php foreach ( $interests as $tag ) : ?>
				<span class="md-docsingle-tag"><?php echo esc_html( $tag ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ============ 다른 의료진 ============ -->
<?php if ( ! empty( $others ) ) : ?>
<section class="md-section md-section--surface">
	<div class="md-container">
		<header class="md-section-head">
			<h2 class="md-section-head__title md-section-head__title--sm"><?php echo esc_html( md_content( 'doc_single_others_title', '다른 의료진' ) ); ?></h2>
		</header>

		<div class="md-docsingle-others">
			<?php foreach ( $others as $other ) :
				$other_url = home_url( '/의료진/' . moondental_doctor_name_to_slug( $other['name'] ) . '/' );
				$other_photo = moondental_doctor_photo_url( $other['photo'] ?? '' );
				$other_zoom  = isset( $other['photo_zoom'] ) ? (float) $other['photo_zoom'] : 1.0;
				$other_ty    = isset( $other['photo_ty'] )   ? (float) $other['photo_ty']   : 0.0;
				$other_style = sprintf(
					'transform: translateY(%s%%) scale(%s); transform-origin: center top; object-position: center top;',
					esc_attr( number_format( $other_ty,   1 ) ),
					esc_attr( number_format( $other_zoom, 2 ) )
				);
			?>
				<a class="md-docsingle-other" href="<?php echo esc_url( $other_url ); ?>">
					<div class="md-docsingle-other__photo">
						<?php if ( $other_photo ) : ?>
							<img src="<?php echo esc_url( $other_photo ); ?>" alt="<?php echo esc_attr( $other['name'] ); ?>" loading="lazy" style="<?php echo esc_attr( $other_style ); ?>">
						<?php endif; ?>
					</div>
					<span class="md-docsingle-other__name"><?php echo esc_html( $other['name'] ); ?></span>
					<span class="md-docsingle-other__role"><?php echo esc_html( $other['role'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="md-doc-single-cta">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( home_url( '/의료진/' ) ); ?>">
				<?php echo esc_html( md_content( 'doc_single_back_label', '← 의료진 전체 보기' ) ); ?>
			</a>
		</div>
	</div>
</section>
<?php endif; ?>

<?php // v3.30.9 · 하단 예약 CTA 배너 완전 제거 (사용자 요청) ?>

<?php
get_footer();
