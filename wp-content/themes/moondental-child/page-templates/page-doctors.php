<?php
/**
 * Template Name: 의료진 페이지
 * Template Post Type: page
 *
 * Hero / 층별 필터 탭 / 그리드 카드(약력 펼치기) / 진료 전문과 / CTA 배너.
 *
 * @package moondental-child
 */

get_header();
$info       = moondental_get_info();
$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
$groups     = function_exists( 'moondental_get_team_with_customizer' )
	? moondental_get_team_with_customizer()
	: moondental_get_team();

// 그룹 → 필터 키 매핑 (한글 라벨 → kebab key for data-attr)
$group_keys = array();
foreach ( $groups as $g ) {
	$group_keys[ $g['group'] ] = sanitize_title( $g['group'] );
}

// 의료진 총원 계산
$total_doctors = 0;
foreach ( $groups as $g ) $total_doctors += count( $g['members'] );

// 진료 전문과 안내
$specialties = array(
	array( 'icon' => '🦷', 'title' => '치과보철과',   'desc' => '임플란트·크라운·틀니 등 손상된 치아 복원 전문' ),
	array( 'icon' => '✨', 'title' => '치과교정과',   'desc' => '부정교합 · 투명교정 · 부분교정 등 치아 배열 전문' ),
	array( 'icon' => '🌿', 'title' => '치과보존과',   'desc' => '신경치료 · 충치 치료 등 자연치 보존 전문' ),
	array( 'icon' => '🩺', 'title' => '치주과',       'desc' => '잇몸 질환 · 잇몸 수술 · 치주 관리 전문' ),
	array( 'icon' => '🧒', 'title' => '소아치과',     'desc' => '아이의 첫 치과 진료부터 청소년 교정까지' ),
	array( 'icon' => '🦴', 'title' => '구강악안면외과', 'desc' => '사랑니 · 매복치 · 임플란트 외과 진료' ),
);
?>

<!-- ============ Hero ============ -->
<section class="md-docs-hero">
	<div class="md-container">
		<nav class="md-docs-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>의료진</span>
		</nav>
		<span class="md-docs-hero__chip"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_chip', 'MOON DENTAL HOSPITAL · OUR DOCTORS' ) : 'MOON DENTAL HOSPITAL · OUR DOCTORS' ); ?></span>
		<h1 class="md-docs-hero__title">
			<?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_title_a', '30년 임상,' ) : '30년 임상,' ); ?><br>
			<em><?php echo (int) $total_doctors; ?><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_title_b', '인 의료진 협진' ) : '인 의료진 협진' ); ?></em>
		</h1>
		<p class="md-docs-hero__lead">
			<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_lead', "보철·교정·보존·외과 — 각 분야 전문 의료진이 한 자리에서\n환자 한 분의 치아를 함께 봅니다." ) : "보철·교정·보존·외과 — 각 분야 전문 의료진이 한 자리에서\n환자 한 분의 치아를 함께 봅니다." ) ); ?>
		</p>
		<ul class="md-docs-hero__stats">
			<li><strong><?php echo (int) $total_doctors; ?>인</strong><span><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_1_label', '전문 의료진' ) : '전문 의료진' ); ?></span></li>
			<li><strong><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_2_value', '3개층' ) : '3개층' ); ?></strong><span><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_2_label', '9F · 10F · 11F' ) : '9F · 10F · 11F' ); ?></span></li>
			<li><strong><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_3_value', '30년' ) : '30년' ); ?></strong><span><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_3_label', '1995년 개원' ) : '1995년 개원' ); ?></span></li>
		</ul>
		<div class="md-btn-group">
			<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>" data-track="cta-docs-hero-reservation">
				📅 상담 예약하기
			</a>
			<a class="md-btn md-btn-secondary md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-docs-hero-call">
				📞 <?php echo esc_html( $info['phone'] ); ?>
			</a>
		</div>
	</div>
</section>

<!-- ============ 의료진 그리드 (필터 + 카드) ============ -->
<section class="md-section md-section--surface" id="doctors-list">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_list_eyebrow', 'Our Doctors' ) : 'Our Doctors' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_list_title', '전체 의료진' ) : '전체 의료진' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_list_lead', '각 분야 전문의의 정성스러운 진료를 받으실 수 있습니다.' ) : '각 분야 전문의의 정성스러운 진료를 받으실 수 있습니다.' ) ); ?>
			</p>
		</header>

		<!-- 층별 필터 -->
		<div class="md-docs-filter" role="tablist" aria-label="진료센터 필터">
			<button class="md-docs-filter__btn is-active" type="button" data-doc-filter="all">
				전체 <span class="md-docs-filter__count"><?php echo (int) $total_doctors; ?></span>
			</button>
			<?php foreach ( $groups as $g ) :
				$key = $group_keys[ $g['group'] ];
				$n   = count( $g['members'] );
			?>
				<button class="md-docs-filter__btn" type="button" data-doc-filter="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $g['group'] ); ?>
					<span class="md-docs-filter__count"><?php echo (int) $n; ?></span>
				</button>
			<?php endforeach; ?>
		</div>

		<!-- 의료진 카드 그리드 -->
		<div class="md-docs-grid">
			<?php foreach ( $groups as $g ) :
				$key = $group_keys[ $g['group'] ];
				foreach ( $g['members'] as $doc ) :
					$photo_url = moondental_doctor_photo_url( $doc['photo'] ?? '' );
					$anchor    = 'doctor-' . sanitize_title( $doc['name'] );
					$bio       = $doc['bio'] ?? array();
					if ( is_string( $bio ) ) { $bio = array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", $bio ) ) ); }

					// 사진 머리 크기·위치 통일 — 의료진별 zoom + translateY 적용
					$photo_zoom = isset( $doc['photo_zoom'] ) ? (float) $doc['photo_zoom'] : 1.0;
					$photo_ty   = isset( $doc['photo_ty'] )   ? (float) $doc['photo_ty']   : 0.0;
					$photo_style = sprintf(
						'transform: translateY(%s%%) scale(%s); transform-origin: center top; object-position: center top;',
						esc_attr( number_format( $photo_ty,   1 ) ),
						esc_attr( number_format( $photo_zoom, 2 ) )
					);
			?>
				<article class="md-doccard" data-doc-group="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $anchor ); ?>">
					<div class="md-doccard__photo">
						<?php if ( $photo_url ) : ?>
							<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $doc['name'] ); ?>" loading="lazy" style="<?php echo esc_attr( $photo_style ); ?>">
						<?php else : ?>
							<span class="md-doccard__initial"><?php echo esc_html( mb_substr( $doc['name'], -2 ) ); ?></span>
						<?php endif; ?>
						<span class="md-doccard__role"><?php echo esc_html( $doc['role'] ); ?></span>
					</div>
					<div class="md-doccard__body">
						<h3 class="md-doccard__name"><?php echo esc_html( $doc['name'] ); ?></h3>
						<?php if ( ! empty( $doc['philosophy'] ) ) : ?>
							<p class="md-doccard__phil">
								<span aria-hidden="true">“</span><?php echo esc_html( $doc['philosophy'] ); ?><span aria-hidden="true">”</span>
							</p>
						<?php endif; ?>

						<?php if ( ! empty( $bio ) ) : ?>
							<details class="md-doccard__bio">
								<summary>
									<span>학력 · 경력 보기</span>
									<span class="md-doccard__chev" aria-hidden="true">+</span>
								</summary>
								<ul>
									<?php foreach ( $bio as $line ) : ?>
										<li><?php echo esc_html( $line ); ?></li>
									<?php endforeach; ?>
								</ul>
							</details>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; endforeach; ?>
		</div>

		<p class="md-docs-grid__hint">
			ⓘ <?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_grid_hint', '진료 예약 시 원하시는 의료진을 지정하실 수 있습니다.' ) : '진료 예약 시 원하시는 의료진을 지정하실 수 있습니다.' ); ?>
		</p>
	</div>
</section>

<!-- ============ 진료 전문과 안내 ============ -->
<section class="md-section">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">전문 분야</span>
			<h2 class="md-section-head__title">진료 전문과 안내</h2>
			<p class="md-section-head__lead">
				문치과병원은 6개 전문 진료과의 협진 체계를 운영합니다.
			</p>
		</header>

		<div class="md-spec-grid">
			<?php foreach ( $specialties as $sp ) : ?>
				<article class="md-spec">
					<div class="md-spec__icon" aria-hidden="true"><?php echo $sp['icon']; ?></div>
					<h3 class="md-spec__title"><?php echo esc_html( $sp['title'] ); ?></h3>
					<p class="md-spec__desc"><?php echo esc_html( $sp['desc'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ============ CTA 배너 ============ -->
<section class="md-section md-section--sm">
	<div class="md-container">
		<div class="md-docs-cta">
			<span class="md-docs-cta__chip"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_cta_chip', '상담 예약' ) : '상담 예약' ); ?></span>
			<h2 class="md-docs-cta__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_cta_title', '어떤 원장님께 진료받고 싶으신가요?' ) : '어떤 원장님께 진료받고 싶으신가요?' ); ?></h2>
			<p class="md-docs-cta__lead">
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_cta_lead', '부담 없이 상담받으세요. 환자분께 맞는 의료진을 안내드립니다.' ) : '부담 없이 상담받으세요. 환자분께 맞는 의료진을 안내드립니다.' ) ); ?>
			</p>
			<div class="md-btn-group" style="justify-content:center; display:flex;">
				<a class="md-btn md-btn-primary md-btn--lg" href="<?php echo esc_url( home_url( '/상담예약/' ) ); ?>" data-track="cta-docs-banner-reservation">
					📅 상담 예약하기
				</a>
				<a class="md-btn md-btn-ghost md-btn--lg" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-docs-banner-call">
					📞 <?php echo esc_html( $info['phone'] ); ?>
				</a>
			</div>
			<p class="md-docs-cta__hours">
				🕐 평일 09:00–20:30 · 목 09:00–18:00 · 토 09:00–14:00 · 일/공휴일 휴진
			</p>
		</div>
	</div>
</section>

<?php
get_footer();
