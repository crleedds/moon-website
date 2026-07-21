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

// 진료 전문과 안내 — v3.32.7: Customizer 편집 가능
$specialties = array();
foreach ( md_parse_lines( md_content( 'doctors_specialties', '' ) ) as $line ) {
	$parts = array_map( 'trim', explode( '|', $line ) );
	if ( count( $parts ) >= 3 ) {
		$specialties[] = array( 'icon' => $parts[0], 'title' => $parts[1], 'desc' => $parts[2] );
	}
}

/* 전체 직원 명단 파싱 — 커스터마이저 staff_list 텍스트영역에서 한 줄에 한 명 "부서|직책|이름"
 * 반환: [ '진료실' => [ '이사' => ['이순민'], '팀장' => ['박지선'], ... ], ... ]
 *
 * v3.29.1: 실명은 Customizer에만 저장 (GitHub 공개 리포에 노출 방지).
 *          이 default는 구조 예시. 실제 명단은 wp-admin → 사용자 정의하기 → 홈 - 직원 명단 (staff_list) 에서 편집. */
$staff_default = "# '부서|직책|이름' 형식으로 한 줄에 한 명씩 (예시)\n"
	. "진료실|팀장|OOO\n"
	. "진료실|실장|OOO\n"
	. "진료실|주임|OOO\n"
	. "기공실|과장|OOO\n"
	. "서비스지원실|실장|OOO\n"
	. "경영지원본부|행정원장|OOO";
$staff_raw = function_exists( 'md_content' ) ? md_content( 'staff_list', $staff_default ) : $staff_default;
$staff_by_dept = array();
$staff_total = 0;
// v3.29.2: 부서 아이콘 · 경영지원본부(옛 경영지원실) · 비서실 흡수
$dept_icons = array(
	'진료실'       => '🦷',
	'기공실'       => '🛠️',
	'서비스지원실' => '💬',
	'경영지원본부' => '📋',
	'경영지원실'   => '📋', // legacy 호환
	'관리사무소'   => '🏢',
	'비서실'       => '✉️', // legacy 호환
);
// 직책 정렬 우선순위 (높은 직급 먼저) · v3.31.6: 대리가 주임보다 상급 (사용자 확인)
$role_rank = array(
	'행정원장'=>1, '소장'=>1, '이사'=>2, '실장'=>3, '팀장'=>4, '차장'=>5, '과장'=>6,
	'수석코디'=>7, '책임'=>8, '책임코디'=>8, '선임'=>9, '선임코디'=>9,
	'대리'=>10, '주임'=>11, '기사'=>12, '사원'=>13,
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
			<em><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_title_b', '전 분야 의료진 협진' ) : '전 분야 의료진 협진' ); ?></em>
		</h1>
		<p class="md-docs-hero__lead">
			<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_lead', "보철·교정·보존·외과 — 각 분야 전문 의료진이 한 자리에서\n환자 한 분의 치아를 함께 봅니다." ) : "보철·교정·보존·외과 — 각 분야 전문 의료진이 한 자리에서\n환자 한 분의 치아를 함께 봅니다." ) ); ?>
		</p>
		<ul class="md-docs-hero__stats">
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
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'doctors_list_lead', '각 분야 전문 의료진의 진료를 받으실 수 있습니다.' ) : '각 분야 전문 의료진의 진료를 받으실 수 있습니다.' ) ); ?>
			</p>
		</header>

		<?php
		// v3.30.7 · 그룹 해제 · 사용자 지정 순서로 단일 리스트 렌더
		// 순서: 대표 병원장 문은수 → 정석형 → 이승주 → 김세일 → 이수연 → 권혜진 → 문지현 → 이창률 → 이영일
		$doctor_order = array( '문은수', '정석형', '이승주', '김세일', '이수연', '권혜진', '문지현', '이창률', '이영일' );

		// 모든 그룹에서 의료진 flatten
		$doctors_by_name = array();
		foreach ( $groups as $g ) {
			foreach ( $g['members'] as $m ) {
				$doctors_by_name[ $m['name'] ] = $m;
			}
		}

		// 사용자 지정 순서로 정렬 · 순서 목록에 없는 의료진은 뒤에 append
		$ordered_doctors = array();
		foreach ( $doctor_order as $name ) {
			if ( isset( $doctors_by_name[ $name ] ) ) {
				$ordered_doctors[] = $doctors_by_name[ $name ];
				unset( $doctors_by_name[ $name ] );
			}
		}
		foreach ( $doctors_by_name as $m ) $ordered_doctors[] = $m;
		?>

		<!-- 의료진 카드 그리드 · 그룹 없이 단일 리스트 -->
		<div class="md-docs-grid">
			<?php foreach ( $ordered_doctors as $doc ) :
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
				<article class="md-doccard md-doccard--linked" id="<?php echo esc_attr( $anchor ); ?>"><a class="md-doccard__link-wrap" href="<?php echo esc_url( $doctor_link ); ?>" aria-label="<?php echo esc_attr( $doc['name'] . ' ' . $doc['role'] . ' 상세 페이지' ); ?>"></a>
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

						<span class="md-doccard__view"><?php echo esc_html( md_content( 'doctors_view_label', '상세 프로필 보기 →' ) ); ?></span>
					</div>
				</article>
			<?php endforeach; ?>
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
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'staff_section_title', '전체 직원' ) : '전체 직원' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'staff_section_lead', '한아의료재단 문치과병원에서 환자분과 함께하는 모든 의료진입니다.' ) : '한아의료재단 문치과병원에서 환자분과 함께하는 모든 의료진입니다.' ); ?>
			</p>
		</header>

		<div class="md-staff-grid">
			<?php foreach ( $staff_by_dept as $dept => $roles ) :
				$icon = $dept_icons[ $dept ] ?? '👥';
			?>
				<article class="md-staff-dept">
					<header class="md-staff-dept__head">
						<span class="md-staff-dept__icon" aria-hidden="true"><?php echo $icon; ?></span>
						<h3 class="md-staff-dept__name"><?php echo esc_html( $dept ); ?></h3>
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
			<span class="md-section-head__eyebrow"><?php echo esc_html( md_content( 'doctors_spec_eyebrow', '전문 분야' ) ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( md_content( 'doctors_spec_title', '진료 전문과 안내' ) ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo esc_html( md_content( 'doctors_spec_lead', '문치과병원은 6개 전문 진료과의 협진 체계를 운영합니다.' ) ); ?>
			</p>
		</header>

		<div class="md-spec-grid">
			<?php foreach ( $specialties as $sp ) : ?>
				<article class="md-spec">
					<div class="md-spec__icon" aria-hidden="true"><?php echo moondental_render_icon( $sp['icon'] ); ?></div>
					<div class="md-spec__body">
						<h3 class="md-spec__title"><?php echo esc_html( $sp['title'] ); ?></h3>
						<p class="md-spec__desc"><?php echo esc_html( $sp['desc'] ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php /* v3.37.2 · 의료진 페이지 중간 CTA 배너 제거 (하단 CTA 배너에 이미 있음) */ ?>

<?php
get_footer();
