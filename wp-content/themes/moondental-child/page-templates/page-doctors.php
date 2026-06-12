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

/* 전체 직원 명단 파싱 — 커스터마이저 staff_list 텍스트영역에서 한 줄에 한 명 "부서|직책|이름"
 * 반환: [ '진료실' => [ '이사' => ['이순민'], '팀장' => ['박지선'], ... ], ... ] */
$staff_default = "진료실|이사|이순민\n진료실|팀장|박지선\n진료실|실장|이희남\n진료실|실장|임은혜\n진료실|실장|한경순\n진료실|책임|주경심\n진료실|책임|윤경옥\n진료실|책임|노금란\n진료실|책임|김정애\n진료실|과장|남소영\n진료실|선임|김인애\n진료실|선임|박미선\n진료실|선임|김윤미\n진료실|선임|유현영\n진료실|주임|서채빈\n진료실|주임|박명자\n진료실|주임|금민주\n진료실|주임|전서혜\n진료실|주임|유혜정\n진료실|주임|서소리\n진료실|주임|장유정\n진료실|주임|이아연\n진료실|주임|김경하\n진료실|주임|이다윤\n진료실|주임|이하은\n진료실|주임|김하늘\n진료실|주임|김우정\n진료실|주임|최로미\n진료실|주임|권민지\n기공실|이사|조항수\n기공실|차장|명의재\n기공실|과장|장순복\n기공실|기사|박진옥\n기공실|기사|노재형\n서비스지원실|이사|강미해\n서비스지원실|실장|이선양\n서비스지원실|책임코디|김다경\n서비스지원실|책임코디|공미희\n서비스지원실|선임코디|정소리\n서비스지원실|선임코디|황진아\n서비스지원실|선임코디|박혜령\n경영지원실|행정원장|양병욱\n경영지원실|과장|이중현\n경영지원실|사원|김하진\n경영지원실|사원|카밀라\n경영지원실|사원|게를레\n관리사무소|소장|강성하\n비서실|과장|김동현\n비서실|과장|민종기\n비서실|대리|이슬기";
$staff_raw = function_exists( 'md_content' ) ? md_content( 'staff_list', $staff_default ) : $staff_default;
$staff_by_dept = array();
$staff_total = 0;
$dept_icons = array(
	'진료실'       => '🦷',
	'기공실'       => '🛠️',
	'서비스지원실' => '💬',
	'경영지원실'   => '📋',
	'관리사무소'   => '🏢',
	'비서실'       => '✉️',
);
// 직책 정렬 우선순위 (높은 직급 먼저)
$role_rank = array(
	'행정원장'=>1, '소장'=>1, '이사'=>2, '실장'=>3, '팀장'=>4, '차장'=>5, '과장'=>6,
	'책임'=>7, '책임코디'=>7, '선임'=>8, '선임코디'=>8, '주임'=>9, '대리'=>10,
	'기사'=>11, '사원'=>12,
);
foreach ( explode( "\n", $staff_raw ) as $line ) {
	$line = trim( $line );
	if ( $line === '' || strpos( $line, '|' ) === false ) continue;
	$parts = array_map( 'trim', explode( '|', $line ) );
	if ( count( $parts ) < 3 || $parts[2] === '' ) continue;
	$staff_by_dept[ $parts[0] ][ $parts[1] ][] = $parts[2];
	$staff_total++;
}
// 부서 내 직책을 우선순위순으로 정렬
foreach ( $staff_by_dept as $dept => $roles ) {
	uksort( $staff_by_dept[ $dept ], function( $a, $b ) use ( $role_rank ) {
		$ra = $role_rank[ $a ] ?? 99;
		$rb = $role_rank[ $b ] ?? 99;
		return $ra - $rb;
	} );
}
?>

<!-- ============ Hero ============ -->
<section class="md-docs-hero">
	<div class="md-container">
		<nav class="md-docs-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸ <span>의료진</span>
		</nav>
		<span class="md-docs-hero__chip"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_chip', 'MOON DENTAL HOSPITAL · OUR DOCTORS' ) : 'MOON DENTAL HOSPITAL · OUR DOCTORS' ); ?></span>
		<h1 class="md-docs-hero__title">
			<?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_title_a', '30여년 임상,' ) : '30여년 임상,' ); ?><br>
			<em><?php echo (int) $total_doctors; ?><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_title_b', '인 의료진 협진' ) : '인 의료진 협진' ); ?></em>
		</h1>
		<p class="md-docs-hero__lead">
			<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_lead', "보철·교정·보존·외과 — 각 분야 전문 의료진이 한 자리에서\n환자 한 분의 치아를 함께 봅니다." ) : "보철·교정·보존·외과 — 각 분야 전문 의료진이 한 자리에서\n환자 한 분의 치아를 함께 봅니다." ) ); ?>
		</p>
		<ul class="md-docs-hero__stats">
			<li><strong><?php echo (int) $total_doctors; ?>인</strong><span><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_1_label', '전문 의료진' ) : '전문 의료진' ); ?></span></li>
			<li><strong><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_2_value', '3개층' ) : '3개층' ); ?></strong><span><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_2_label', '9F · 10F · 11F' ) : '9F · 10F · 11F' ); ?></span></li>
			<li><strong><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_3_value', '30여년' ) : '30여년' ); ?></strong><span><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_stat_3_label', '1995년 개원' ) : '1995년 개원' ); ?></span></li>
		</ul>
<?php /* Hero CTA 버튼 제거 (요청: 의료진 페이지에서 상담예약/전화 버튼 없앰) */ ?>
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
					$photo_url   = moondental_doctor_photo_url( $doc['photo'] ?? '' );
					$anchor      = 'doctor-' . sanitize_title( $doc['name'] );
					$doctor_link = home_url( '/의료진/' . moondental_doctor_name_to_slug( $doc['name'] ) . '/' );
					$bio         = $doc['bio'] ?? array();
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
				<article class="md-doccard md-doccard--linked" data-doc-group="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $anchor ); ?>"><a class="md-doccard__link-wrap" href="<?php echo esc_url( $doctor_link ); ?>" aria-label="<?php echo esc_attr( $doc['name'] . ' ' . $doc['role'] . ' 상세 페이지' ); ?>"></a>
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

						<span class="md-doccard__view">상세 프로필 보기 <span aria-hidden="true">→</span></span>
					</div>
				</article>
			<?php endforeach; endforeach; ?>
		</div>

		<p class="md-docs-grid__hint">
			ⓘ <?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_grid_hint', '진료 예약 시 원하시는 의료진을 지정하실 수 있습니다.' ) : '진료 예약 시 원하시는 의료진을 지정하실 수 있습니다.' ); ?>
		</p>
	</div>
</section>

<!-- ============ 전체 직원 (의료진 외 스태프) — 원장 소개 바로 아래 ============ -->
<?php if ( ! empty( $staff_by_dept ) ) : ?>
<section class="md-section" id="staff">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'staff_section_eyebrow', 'Our Staff' ) : 'Our Staff' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'staff_section_title', '전체 직원' ) : '전체 직원' ); ?> <span class="md-staff-total">총 <?php echo (int) $staff_total; ?>명</span></h2>
			<p class="md-section-head__lead">
				<?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'staff_section_lead', '의료진과 함께 환자분의 편안한 진료를 위해 일하는 전체 스태프입니다.' ) : '의료진과 함께 환자분의 편안한 진료를 위해 일하는 전체 스태프입니다.' ); ?>
			</p>
		</header>

		<div class="md-staff-grid">
			<?php foreach ( $staff_by_dept as $dept => $roles ) :
				$dept_count = 0;
				foreach ( $roles as $names ) $dept_count += count( $names );
				$icon = $dept_icons[ $dept ] ?? '👥';
			?>
				<article class="md-staff-dept">
					<header class="md-staff-dept__head">
						<span class="md-staff-dept__icon" aria-hidden="true"><?php echo $icon; ?></span>
						<h3 class="md-staff-dept__name"><?php echo esc_html( $dept ); ?></h3>
						<span class="md-staff-dept__count"><?php echo (int) $dept_count; ?>명</span>
					</header>
					<dl class="md-staff-dept__roles">
						<?php foreach ( $roles as $role => $names ) : ?>
							<div class="md-staff-role">
								<dt class="md-staff-role__title"><?php echo esc_html( $role ); ?></dt>
								<dd class="md-staff-role__names">
									<?php foreach ( $names as $i => $name ) : ?>
										<span class="md-staff-name"><?php echo esc_html( $name ); ?></span><?php if ( $i < count( $names ) - 1 ) echo '<span class="md-staff-sep" aria-hidden="true">·</span>'; ?>
									<?php endforeach; ?>
								</dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ============ 진료 전문과 안내 ============ -->
<section class="md-section md-section--surface">
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
			<?php echo md_render_reservation_ctas( array( 'track' => 'cta-docs-banner', 'size' => 'lg', 'align' => 'center' ) ); ?>
			<p class="md-docs-cta__hours">
				🕐 월·화·수·금 09:00–20:30 · 목 09:00–18:00 · 토 09:00–14:00 · 일/공휴일 휴진
			</p>
		</div>
	</div>
</section>

<?php
get_footer();
