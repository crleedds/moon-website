<?php
/**
 * 홈 화면 v5 (시안 3 · 원장 확정)
 *  첫 화면(야경) → 숫자 띠 → 지정·협력 → 4개 전문센터 → 30여년의 발자취 → 찾는 이유
 *  → 시설 → 의료진 → 소식 · 치아이야기 → 자주 묻는 질문 → 예약 띠 → 오시는 길
 *
 * 문구는 기존 사용자 정의하기 값(md_content)을 그대로 쓴다.
 *
 * @package moondental-child
 */
$info = moondental_get_info();
$mc   = function ( $k, $d = '' ) { return function_exists( 'md_content' ) ? md_content( $k, $d ) : $d; };
$lines = function ( $raw ) {
	$out = array();
	foreach ( preg_split( "/\r\n|\r|\n/", (string) $raw ) as $l ) {
		$l = trim( $l );
		if ( $l === '' || strpos( $l, '#' ) === 0 ) continue;
		$out[] = $l;
	}
	return $out;
};
$phone_link   = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', (string) $info['phone'] );
$booking_url  = $info['naver_place'] ?: 'https://booking.naver.com/booking/13/bizes/485314';
$kakao_url    = $mc( 'cta_btn_kakao_url', '' ) ?: ( $info['kakao_url'] ?? '' );
$place_url    = 'https://map.naver.com/p/entry/place/12772165';
$reserve_url  = home_url( '/상담예약/' );
$location_url = home_url( '/오시는-길/' );

/* 첫 화면 사진 (사용자 정의하기 · 홈 배경) */
$hero_bg = get_theme_mod( 'moondental_home_hero_bg', '' );
if ( $hero_bg ) {
	$hero_bg = preg_replace_callback( '#[^\x00-\x7F]+#u', function ( $m ) { return rawurlencode( $m[0] ); }, $hero_bg );
} else {
	$hero_bg = MOONDENTAL_URI . '/assets/images/share/home-share.jpg';
}

/* 숫자 띠 */
$years = max( 1, (int) current_time( 'Y' ) - 1995 );
$stats = array(
	array( $mc( 'trust_1_value', (string) $years ), $mc( 'trust_1_unit', '년' ), $mc( 'trust_1_label', '1995년 개원' ) ),
	array( $mc( 'trust_2_value', '11' ), $mc( 'trust_2_unit', '개' ), $mc( 'trust_2_label', '전문 진료 영역' ) ),
	array( $mc( 'trust_3_value', '4' ), $mc( 'trust_3_unit', '개층' ), '통합 진료센터 9 · 10 · 11 · 13F' ),
	array( $mc( 'trust_4_value', '1:1' ), $mc( 'trust_4_unit', '' ), $mc( 'trust_4_label', '충분한 사전 상담' ) ),
);

/* 지정 · 협력 */
$certs = array();
foreach ( $lines( $mc( 'mission_certs', "⭐|1990년대부터 임플란트를 식립해온 병원\n🏥|국가지정 구강검진 병원\n🌐|외국인환자 유치 의료기관\n🪖|미군 및 가족 치료기관\n🦷|천안시 치아사랑사업 협력병원\n🔗|삼성서울병원 협력병원\n➕|대한적십자사 협력병원" ) ) as $l ) {
	$p = array_map( 'trim', explode( '|', $l, 2 ) );
	$certs[] = isset( $p[1] ) ? $p[1] : $p[0];
}

/* 4개 전문센터 */
$centers = array(
	array( 'no' => '01', 'title' => $mc( 'clinic_intro_implant_title', '임플란트센터' ), 'en' => 'IMPLANT · 10F', 'url' => home_url( '/임플란트-센터/' ),
		'desc' => '정밀한 임플란트 시술과 정기 검진을 통한 사후관리까지.',
		'tags' => array( '네비게이션 임플란트', '비절개 임플란트', '상악동 거상술', '전악 임플란트', '임플란트 재수술' ) ),
	array( 'no' => '02', 'title' => $mc( 'clinic_intro_ortho_title', '교정센터' ), 'en' => 'ORTHODONTICS · 11F', 'url' => home_url( '/투명교정-센터/' ),
		'desc' => 'AI 기반 투명교정 진단으로 환자별 교정 계획을 제안합니다.',
		'tags' => array( '슈어스마일 투명교정', '브라켓 교정', '소아 교정', '재교정', '앞니 부분 교정' ) ),
	array( 'no' => '03', 'title' => $mc( 'clinic_intro_smile_title', '심미치료센터' ), 'en' => 'AESTHETIC · 10F', 'url' => home_url( '/심미치료센터/' ),
		'desc' => '불필요한 치아 삭제를 줄이는 최소 침습 원칙의 심미 치료.',
		'tags' => array( '최소침습 라미네이트', '반점치(화이트스팟) 제거', '잇몸 미백', '벌어진 앞니 레진', '왜소치' ) ),
	array( 'no' => '04', 'title' => $mc( 'clinic_intro_preserve_title', '자연치아보존센터' ), 'en' => 'CONSERVATION · 9F', 'url' => home_url( '/자연치아-살리기/' ),
		'desc' => '발치 대신 자연치아를 최대한 보존합니다. 가장 작은 개입부터 차례로.',
		'tags' => array( '충치치료', '부분신경치료(치수보존술)', '미세현미경 신경치료', '잇몸치료', '덴탈SPA' ) ),
);

/* 30여년의 발자취 · 대표 장면 7 */
$hist_pick = array( 'history-slide-01', 'history-slide-03', 'history-slide-09', 'history-slide-12', 'history-slide-18', 'history-slide-22', 'history-slide-28' );
$history   = array();
if ( function_exists( 'moondental_get_history' ) && function_exists( 'moondental_history_photo_url' ) ) {
	foreach ( (array) moondental_get_history() as $row ) {
		if ( empty( $row['photo'] ) ) continue;
		$key = pathinfo( $row['photo'], PATHINFO_FILENAME );
		if ( ! in_array( $key, $hist_pick, true ) ) continue;
		$history[ $key ] = array(
			'year'   => $row['year'] ?? '',
			'month'  => isset( $row['month'] ) && $row['month'] !== '' ? str_pad( $row['month'], 2, '0', STR_PAD_LEFT ) : '',
			'title'  => $row['title'] ?? '',
			'photo'  => moondental_history_photo_url( $row['photo'] ),
			'anchor' => 'h-' . sanitize_title( $key ),
		);
	}
	$sorted = array();
	foreach ( $hist_pick as $k ) if ( isset( $history[ $k ] ) ) $sorted[] = $history[ $k ];
	$history = $sorted;
}

/* 찾는 이유 4 */
$why = array();
for ( $i = 1; $i <= 4; $i++ ) {
	$t = $mc( "why_{$i}_title", '' );
	if ( $t ) $why[] = array( $t, $mc( "why_{$i}_desc", '' ) );
}

/* 시설 · 원장 지시: 물방울 레이저는 대수 없이, 프라임 구강스캐너와 한 칸 */
$facility = array(
	array( '디지털 CBCT 3D 진단', '저선량 콘빔 CT로 신경 · 혈관 · 뼈 두께까지 3차원 분석' ),
	array( '디지털 가이드 수술', '임플란트 위치 · 각도를 컴퓨터로 미리 설계' ),
	array( 'One Day 디지털 보철', '구강 스캔 → 원내 기공소 제작, 당일 보철까지' ),
	array( '한아 임플란트 보철연구소', '1998년 설립한 자체 보철 연구소' ),
	array( '13층 원내기공실', '의료진과 기공사가 직접 소통하는 맞춤 보철' ),
	array( '물방울 레이저 · 프라임 구강스캐너', '통증 · 출혈이 적은 레이저 진료와 본뜨기 없는 정밀 구강 스캔' ),
	array( '멸균 · 감염 관리', '핸드피스 · 기구 모두 환자 단위로 멸균' ),
	array( '응급 의료 장비 상시', '혈압 · 혈당 · 심전도 · 산소포화도' ),
	array( '평일 야간 진료', '월 · 화 · 수 · 금 20:30까지' ),
);

/* 의료진 */
$doctors = array();
$team = function_exists( 'moondental_get_team_with_customizer' ) ? moondental_get_team_with_customizer() : ( function_exists( 'moondental_get_team' ) ? moondental_get_team() : array() );
$order = array( '문은수', '권혜진', '김세일', '문지현', '이수연', '이승주', '이영일', '이창률', '정석형' );
$by = array();
foreach ( (array) $team as $m ) { if ( ! empty( $m['name'] ) ) $by[ $m['name'] ] = $m; }
foreach ( $order as $n ) { if ( isset( $by[ $n ] ) ) { $doctors[] = $by[ $n ]; unset( $by[ $n ] ); } }
foreach ( $by as $m ) $doctors[] = $m;

/* 소식 · 치아이야기 */
$notice_q = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 6, 'category_name' => 'notice,공지사항,announcement', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
$notice_ids = array();
foreach ( array( 'notice', '공지사항', 'announcement' ) as $s ) { $c = get_category_by_slug( $s ); if ( $c ) $notice_ids[] = $c->term_id; }
$story_args = array( 'post_type' => 'post', 'posts_per_page' => 3, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true, 'ignore_sticky_posts' => true );
if ( $notice_ids ) $story_args['category__not_in'] = $notice_ids;
$story_q   = new WP_Query( $story_args );
$news_url  = home_url( '/소식/' );

/* FAQ (사용자 정의하기 · 홈 FAQ 6) */
$faqs = array();
for ( $i = 1; $i <= 6; $i++ ) {
	$q = $mc( "faq_{$i}_q", '' );
	if ( $q ) $faqs[] = array( $q, $mc( "faq_{$i}_a", '' ) );
}

$gmap = $mc( 'loc_gmap_embed_url', 'https://www.google.com/maps?q=%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90%20%EC%B2%9C%EC%95%88%20%EB%A7%8C%EB%82%A8%EB%A1%9C%2052&z=16&output=embed&hl=ko' );
$addr = $info['address'] ?? '충청남도 천안시 동남구 만남로 52, 문타워 9~13층';
?>

<section class="v5-hero" aria-label="문치과병원">
	<div class="v5-hero__copy">
		<span class="v5-label">Since 1995 · Cheonan · Asan</span>
		<p class="v5-hero__en">Thirty years, one place, one team.</p>
		<?php if ( ! function_exists( 'moondental_current_language' ) || 'ko' === moondental_current_language() ) : ?>
		<h1 class="v5-hero__title">천안 · 아산에서 30여년,<br><b>한 건물에서 함께 보는</b> 치과병원</h1>
		<?php else : /* 외국어 · 둘째 줄을 한 문구로 (문구 번역 사전이 통째로 바꾸게) */ ?>
		<h1 class="v5-hero__title">천안 · 아산에서 30여년,<br><b>한 건물에서 함께 보는 치과병원</b></h1>
		<?php endif; ?>
		<p class="v5-hero__lead">한아의료재단 문치과병원은 임플란트 · 교정 · 심미치료 · 자연치아보존 네 개의 센터와 진료과 원장들이 대학병원식 협진으로 한 환자를 같이 보는 병원급 치과병원입니다.</p>
		<p class="v5-hero__mission"><?php echo esc_html( $mc( 'mission_band_text', '한아의료재단 문치과병원의 사명은 품격 있는 진료와 서비스로 환자의 신뢰를 받으며, 나눔과 봉사를 통해 사회에 공헌하는 가장 인정받는 병원이 되는 것입니다.' ) ); ?></p>
		<div class="v5-acts">
			<a class="v5-btn v5-btn--fill" href="<?php echo esc_url( $reserve_url ); ?>" data-track="cta-v5-hero-reserve">상담 예약</a>
			<a class="v5-btn" href="<?php echo esc_url( $location_url ); ?>" data-track="cta-v5-hero-location">오시는 길</a>
		</div>
	</div>
	<div class="v5-hero__photo">
		<img src="<?php echo esc_url( $hero_bg ); ?>" alt="<?php echo esc_attr( $mc( 'hero_bg_alt', '천안 문치과병원 문타워 야경 — 한아의료재단 문치과병원' ) ); ?>" fetchpriority="high" decoding="async">
		<span class="v5-hero__cap" aria-hidden="true">MOON TOWER · CHEONAN</span>
	</div>
</section>

<dl class="v5-stats v5-wrap" aria-label="숫자로 보는 문치과병원">
	<?php foreach ( $stats as $s ) : ?>
		<div class="v5-stat"><dd><?php echo esc_html( $s[0] ); ?><?php if ( $s[1] ) : ?><small><?php echo esc_html( $s[1] ); ?></small><?php endif; ?></dd><dt><?php echo esc_html( $s[2] ); ?></dt></div>
	<?php endforeach; ?>
</dl>

<?php if ( $certs ) : ?>
<div class="v5-creds" aria-label="지정 · 협력">
	<ul class="v5-wrap">
		<?php foreach ( $certs as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<section class="v5-blk" aria-label="전문센터">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">Centers</span>
			<div><h2>네 개의 전문센터</h2><p>각 분과 원장들이 다양한 임상 경험을 바탕으로 30여년 한자리에서 진료합니다.</p></div>
		</div>
		<div class="v5-centers">
			<?php foreach ( $centers as $c ) : ?>
				<a class="v5-crow" href="<?php echo esc_url( $c['url'] ); ?>">
					<span class="v5-crow__no"><?php echo esc_html( $c['no'] ); ?></span>
					<h3><?php echo esc_html( $c['title'] ); ?><small><?php echo esc_html( $c['en'] ); ?></small></h3>
					<span class="v5-crow__desc"><p><?php echo esc_html( $c['desc'] ); ?></p><span class="v5-crow__tags"><?php foreach ( $c['tags'] as $t ) : ?><span><?php echo esc_html( $t ); ?></span><?php endforeach; ?></span></span>
					<span class="v5-crow__ar" aria-hidden="true">→</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if ( $history ) : ?>
<section class="v5-blk v5-paper" aria-label="30여년의 발자취">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">History</span>
			<div><h2>30여년의 발자취</h2>
			<p class="v5-hist__sum">1995년 4월 문은수 치과의원으로 문을 열고, 1998년 한아 임플란트 보철 연구소를 세웠습니다. 2004년 의료법인 한아의료재단 문치과병원 신축 사옥으로 옮겼고, 2006년 보건복지부 치과의사 전공의 인턴 수련기관으로 지정되었습니다. 몽골 의료봉사는 2000년부터 이어지고 있습니다.</p></div>
		</div>
		<div class="v5-rail-line" aria-hidden="true"><span>1995</span><span><?php echo esc_html( current_time( 'Y' ) ); ?></span></div>
		<ol class="v5-rail">
			<?php foreach ( $history as $h ) : ?>
				<li><a href="<?php echo esc_url( home_url( '/역사/#' . $h['anchor'] ) ); ?>"><figure>
					<span class="v5-rail__ph"><img src="<?php echo esc_url( $h['photo'] ); ?>" alt="<?php echo esc_attr( $h['title'] ); ?>" loading="lazy" decoding="async"></span>
					<span class="v5-rail__yr"><?php echo esc_html( $h['year'] ); ?><?php if ( $h['month'] ) : ?><small><?php echo esc_html( $h['month'] ); ?></small><?php endif; ?></span>
					<figcaption><?php echo esc_html( $h['title'] ); ?></figcaption>
				</figure></a></li>
			<?php endforeach; ?>
		</ol>
		<a class="v5-more" href="<?php echo esc_url( home_url( '/역사/' ) ); ?>">전체 발자취 보기 →</a>
	</div>
</section>
<?php endif; ?>

<?php if ( $why ) : ?>
<section class="v5-blk" aria-label="문치과병원을 찾는 이유">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">Why Moon Dental</span>
			<div><h2>천안 · 아산에서 문치과병원을 찾는 이유</h2></div>
		</div>
		<div class="v5-why">
			<?php foreach ( $why as $i => $w ) : ?>
				<div><span class="v5-why__no"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><h3><?php echo esc_html( $w[0] ); ?></h3><p><?php echo esc_html( $w[1] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="v5-blk v5-blk--tight" aria-label="시설과 장비">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">Facility</span>
			<div><h2>정확한 진단과 안전한 진료를 위한 시설</h2></div>
		</div>
		<dl class="v5-fac">
			<?php foreach ( $facility as $f ) : ?><div><dt><?php echo esc_html( $f[0] ); ?></dt><dd><?php echo esc_html( $f[1] ); ?></dd></div><?php endforeach; ?>
		</dl>
		<a class="v5-more" href="<?php echo esc_url( home_url( '/기술력-시설/' ) ); ?>">기술력 / 시설 자세히 →</a>
	</div>
</section>

<?php if ( $doctors ) : ?>
<section class="v5-blk v5-blk--tight" aria-label="의료진">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">Doctors</span>
			<div><h2>의료진</h2><p>환자를 가족처럼 생각하는 마음, 그것이 문치과의 진료 철학입니다. — 문은수 대표 병원장</p></div>
		</div>
		<div class="v5-docs">
			<?php foreach ( $doctors as $d ) :
				$photo = md_v5_doctor_photo( $d['photo'] ?? '' );
				$link  = function_exists( 'moondental_doctor_name_to_slug' ) ? home_url( '/의료진/' . moondental_doctor_name_to_slug( $d['name'] ) . '/' ) : home_url( '/의료진/' );
				$role  = $d['role'] ?? '원장';
			?>
				<a class="v5-doc" href="<?php echo esc_url( $link ); ?>">
					<span class="v5-doc__ph"><?php if ( $photo ) : ?><img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $d['name'] . ' ' . $role ); ?>" loading="lazy" decoding="async"><?php endif; ?></span>
					<b><?php echo esc_html( $d['name'] ); ?></b><span><?php echo esc_html( $role ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<p class="v5-docs__note"><?php echo esc_html( $mc( 'doctors_grid_hint', '진료 예약 시 원하시는 의료진을 지정하실 수 있습니다.' ) ); ?></p>
	</div>
</section>
<?php endif; ?>

<?php $md_v5_ko = ( ! function_exists( 'moondental_current_language' ) || 'ko' === moondental_current_language() ); /* 글은 한국어로만 있어 외국어 홈에서는 소식 칸을 뺀다 */ ?>
<?php if ( $md_v5_ko && ( $notice_q->have_posts() || $story_q->have_posts() ) ) : ?>
<section class="v5-blk v5-paper" aria-label="소식과 치아이야기">
	<div class="v5-wrap v5-news">
		<?php if ( $notice_q->have_posts() ) : ?>
		<div>
			<div class="v5-colhead"><h2>문치과병원 소식</h2><a href="<?php echo esc_url( $news_url ); ?>">ALL NEWS →</a></div>
			<?php $n = 0; $list = array(); ?>
			<div class="v5-feat">
				<?php while ( $notice_q->have_posts() ) : $notice_q->the_post();
					$pid = get_the_ID();
					$img = md_v5_post_image( $pid );
					if ( $n < 2 && $img ) : $n++; ?>
						<a href="<?php the_permalink(); ?>">
							<span class="v5-feat__ph"><img src="<?php echo esc_url( $img['url'] ); ?>" alt="" loading="lazy" decoding="async"<?php echo $img['tall'] ? ' class="is-tall"' : ''; ?>></span>
							<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time>
							<b><?php echo esc_html( wp_strip_all_tags( preg_replace( '/^\s*\[[^\]]*\]\s*/u', '', get_the_title() ) ) ); ?></b>
						</a>
					<?php else :
						$list[] = array( get_permalink(), get_the_date( 'Y.m.d' ), get_the_date( 'Y-m-d' ), preg_replace( '/^\s*\[[^\]]*\]\s*/u', '', get_the_title() ) );
					endif;
				endwhile; wp_reset_postdata(); ?>
			</div>
			<?php if ( $list ) : ?>
				<ul class="v5-nlist">
					<?php foreach ( array_slice( $list, 0, 4 ) as $l ) : ?>
						<li><time datetime="<?php echo esc_attr( $l[2] ); ?>"><?php echo esc_html( $l[1] ); ?></time><a href="<?php echo esc_url( $l[0] ); ?>"><?php echo esc_html( wp_strip_all_tags( $l[3] ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<?php if ( $story_q->have_posts() ) : ?>
		<div>
			<div class="v5-colhead"><h2>치아이야기</h2><a href="<?php echo esc_url( $news_url ); ?>">ALL STORIES →</a></div>
			<?php while ( $story_q->have_posts() ) : $story_q->the_post(); $img = md_v5_post_image( get_the_ID() ); ?>
				<a class="v5-story" href="<?php the_permalink(); ?>">
					<span class="v5-story__ph"><?php if ( $img ) : ?><img src="<?php echo esc_url( $img['url'] ); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?></span>
					<span><time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time><b><?php echo esc_html( wp_strip_all_tags( preg_replace( '/^\s*\[[^\]]*\]\s*/u', '', get_the_title() ) ) ); ?></b></span>
				</a>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php if ( $faqs ) : ?>
<section class="v5-blk" id="faq-home" aria-label="자주 묻는 질문">
	<div class="v5-wrap">
		<div class="v5-head">
			<span class="v5-label">FAQ</span>
			<div><h2><?php echo esc_html( $mc( 'faq_title', '예약 전 자주 묻는 질문' ) ); ?></h2></div>
		</div>
		<div class="v5-faq">
			<?php foreach ( $faqs as $i => $f ) : ?>
				<details<?php echo 0 === $i ? ' open' : ''; ?>><summary><?php echo esc_html( $f[0] ); ?></summary><div class="v5-faq__a"><?php echo wp_kses_post( function_exists( 'md_autolink_addresses' ) ? md_autolink_addresses( wpautop( $f[1] ) ) : wpautop( $f[1] ) ); ?></div></details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<section class="v5-closing" aria-label="상담 예약">
	<div class="v5-wrap">
		<div>
			<span class="v5-label">First visit</span>
			<h2>환자분께 꼭 필요한 치료만<br>정직하게 권합니다.</h2>
			<p>편하신 시간에 예약하시면 자세히 상담해 드립니다.</p>
		</div>
		<div class="v5-acts">
			<a class="v5-btn v5-btn--fill" href="<?php echo esc_url( $booking_url ); ?>" target="_blank" rel="noopener" data-track="cta-v5-closing-naver">네이버 예약</a>
			<a class="v5-btn" href="tel:<?php echo esc_attr( $phone_link ); ?>" data-track="cta-v5-closing-call">전화 상담</a>
			<?php if ( $kakao_url ) : ?><a class="v5-btn" href="<?php echo esc_url( $kakao_url ); ?>" target="_blank" rel="noopener" data-track="cta-v5-closing-kakao">카카오톡 상담</a><?php endif; ?>
		</div>
	</div>
</section>

<section class="v5-mapsec" aria-label="오시는 길">
	<div class="v5-wrap v5-mapgrid">
		<div class="v5-mapcard">
			<span class="v5-label">Location</span>
			<p class="v5-mapcard__addr"><a href="<?php echo esc_url( $place_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $addr ); ?></a></p>
			<p class="v5-mapcard__note">천안고속 · 시외버스터미널 맞은편 터미널사거리</p>
			<div class="v5-maps">
				<a href="<?php echo esc_url( $place_url ); ?>" target="_blank" rel="noopener">네이버 지도</a>
				<a href="https://map.kakao.com/?q=%ED%95%9C%EC%95%84%EC%9D%98%EB%A3%8C%EC%9E%AC%EB%8B%A8%20%EB%AC%B8%EC%B9%98%EA%B3%BC%EB%B3%91%EC%9B%90" target="_blank" rel="noopener">카카오맵</a>
				<a href="https://maps.app.goo.gl/MNt59kcxeKL92nCU9" target="_blank" rel="noopener">Google Maps</a>
			</div>
		</div>
		<div class="v5-mapbox">
			<iframe src="<?php echo esc_url( $gmap ); ?>" title="문치과병원 위치 · 구글 지도" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
		</div>
	</div>
</section>
