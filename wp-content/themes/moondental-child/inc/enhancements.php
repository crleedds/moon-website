<?php
/**
 * 사이트 전반 강화 — SEO 구조화 데이터, 모바일 CTA, 카카오톡 플로팅, GA4 클릭 트래킹.
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


/* ============================================================
 * 1. JSON-LD 구조화 데이터 (Dentist / LocalBusiness)
 *    네이버·구글 검색 결과에 평점·주소·시간·전화 풍부 표시
 * ========================================================== */
function moondental_jsonld_schema() {
	$info = moondental_get_info();
	$site = home_url( '/' );

	// 영업시간 표준 포맷 (Schema.org openingHoursSpecification)
	$hours = array(
		array(
			'@type'      => 'OpeningHoursSpecification',
			'dayOfWeek'  => array( 'Monday', 'Tuesday', 'Wednesday', 'Friday' ),
			'opens'      => '09:00',
			'closes'     => '20:30',
		),
		array(
			'@type'      => 'OpeningHoursSpecification',
			'dayOfWeek'  => 'Thursday',
			'opens'      => '09:00',
			'closes'     => '18:00',
		),
		array(
			'@type'      => 'OpeningHoursSpecification',
			'dayOfWeek'  => 'Saturday',
			'opens'      => '09:00',
			'closes'     => '14:00',
		),
	);

	$sns = array();
	if ( ! empty( $info['kakao_url'] ) )   $sns[] = $info['kakao_url'];
	if ( ! empty( $info['naver_place'] ) ) $sns[] = $info['naver_place'];
	if ( ! empty( $info['instagram'] ) )   $sns[] = $info['instagram'];
	if ( ! empty( $info['blog_url'] ) )    $sns[] = $info['blog_url'];
	if ( ! empty( $info['facebook_url'] ) )$sns[] = $info['facebook_url'];

	$logo = MOONDENTAL_URI . '/assets/images/logo/logo-wide-noreg.png';

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => array( 'Dentist', 'MedicalClinic', 'LocalBusiness' ),
		'@id'             => $site . '#org',
		'name'            => $info['name_full'],
		'alternateName'   => $info['name_short'],
		'description'     => $info['tagline'] . ' — 1995년부터 천안 만남로에서 진료해온 종합 치과병원.',
		'url'             => $site,
		'logo'            => $logo,
		'image'           => $logo,
		'telephone'       => $info['phone'],
		'email'           => $info['email'] ?: 'moondental1995@naver.com',
		'priceRange'      => '₩₩',
		'address'         => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $info['address_road'] ?: $info['address'],
			'addressLocality' => '천안시',
			'addressRegion'   => '충청남도',
			'postalCode'      => '31136',
			'addressCountry'  => 'KR',
		),
		'geo'             => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => 36.8210,
			'longitude' => 127.1572,
		),
		'openingHoursSpecification' => $hours,
		'medicalSpecialty'          => array(
			'Dentistry',
			'OralAndMaxillofacialSurgery',
			'Prosthodontics',
			'Orthodontics',
			'Endodontics',
			'PediatricDentistry',
			'Periodontics',
		),
		'sameAs'          => array_values( array_unique( array_filter( $sns ) ) ),
	);

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
	echo "\n</script>\n";
}
add_action( 'wp_head', 'moondental_jsonld_schema', 50 );


/* ============================================================
 * 2. 모바일 하단 고정 CTA 바 + 데스크탑 카카오톡 플로팅 버튼
 * ========================================================== */
function moondental_floating_actions() {
	if ( is_admin() ) return;
	$info = moondental_get_info();
	$phone_link = $info['phone_link'] ?: preg_replace( '/[^0-9]/', '', $info['phone'] );
	$kakao      = $info['kakao_url'] ?: '#';
	$naver      = $info['naver_place'] ?: '#';
	?>
	<!-- 모바일 하단 고정 CTA 바 (모바일에서만) -->
	<div class="md-mobile-cta" role="navigation" aria-label="빠른 예약·문의">
		<a class="md-mobile-cta__item md-mobile-cta__item--call"
		   href="tel:<?php echo esc_attr( $phone_link ); ?>"
		   data-track="cta-call-mobile"
		   aria-label="전화로 예약·상담">
			<span class="md-mobile-cta__icon" aria-hidden="true">📞</span>
			<span class="md-mobile-cta__label">전화 예약</span>
		</a>
		<?php if ( $kakao && $kakao !== '#' ) : ?>
		<a class="md-mobile-cta__item md-mobile-cta__item--kakao"
		   href="<?php echo esc_url( $kakao ); ?>"
		   target="_blank" rel="noopener"
		   data-track="cta-kakao-mobile"
		   aria-label="카카오톡으로 상담">
			<span class="md-mobile-cta__icon" aria-hidden="true">💬</span>
			<span class="md-mobile-cta__label">카카오톡</span>
		</a>
		<?php endif; ?>
		<?php if ( $naver && $naver !== '#' ) : ?>
		<a class="md-mobile-cta__item md-mobile-cta__item--naver"
		   href="<?php echo esc_url( $naver ); ?>"
		   target="_blank" rel="noopener"
		   data-track="cta-naver-mobile"
		   aria-label="네이버로 예약">
			<span class="md-mobile-cta__icon" aria-hidden="true">📅</span>
			<span class="md-mobile-cta__label">네이버 예약</span>
		</a>
		<?php endif; ?>
	</div>

	<!-- 데스크탑 카카오톡 플로팅 버튼 (우측 하단, 데스크탑에서만) -->
	<?php if ( $kakao && $kakao !== '#' ) : ?>
	<a class="md-kakao-fab"
	   href="<?php echo esc_url( $kakao ); ?>"
	   target="_blank" rel="noopener"
	   data-track="cta-kakao-fab"
	   aria-label="카카오톡 상담 열기">
		<svg viewBox="0 0 36 36" aria-hidden="true">
			<path fill="#3C1E1E" d="M18 6C10.27 6 4 10.93 4 17c0 3.97 2.69 7.46 6.72 9.4l-1.43 5.24c-.13.47.39.85.79.58l6.36-4.2c.51.05 1.02.08 1.56.08 7.73 0 14-4.93 14-11s-6.27-11-14-11z"/>
		</svg>
		<span class="md-kakao-fab__label">카카오톡 상담</span>
	</a>
	<?php endif; ?>
	<?php
}
add_action( 'wp_footer', 'moondental_floating_actions', 5 );


/* ============================================================
 * 3. GA4 클릭 이벤트 트래킹 (전화·카톡·네이버 예약·예약폼 제출)
 *    GA4 측정 ID가 설정돼있을 때만 gtag 호출. 없으면 dataLayer만.
 * ========================================================== */
function moondental_click_tracking() {
	?>
	<script>
	(function(){
		window.dataLayer = window.dataLayer || [];
		function track(name, params){
			window.dataLayer.push({event: name, ...(params||{})});
			if (typeof gtag === 'function') gtag('event', name, params||{});
		}
		// 모든 tel: 링크 자동 트래킹
		document.addEventListener('click', function(e){
			var a = e.target.closest('a'); if (!a) return;
			if (a.href && a.href.indexOf('tel:') === 0) {
				track('click_phone', {phone: a.href.replace('tel:','')});
			} else if (a.dataset && a.dataset.track) {
				track(a.dataset.track, {href: a.href||''});
			}
		}, true);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'moondental_click_tracking', 99 );


/* ============================================================
 * 4. 환자 후기 데이터 (홈 섹션용)
 * ========================================================== */
function moondental_get_testimonials() {
	return array(
		array(
			'name'    => '김○○',
			'gender'  => '여성',
			'age'     => '40대',
			'service' => '임플란트',
			'rating'  => 5,
			'text'    => '오랫동안 미루던 임플란트를 30년 경력 원장님께 받았습니다. 수술 당일 통증이 거의 없었고, 자가혈을 함께 사용한다는 점이 안심됐어요. 평일 야간 진료가 있어 직장인에게 정말 편합니다.',
		),
		array(
			'name'    => '박○○',
			'gender'  => '남성',
			'age'     => '50대',
			'service' => '전악 보철',
			'rating'  => 5,
			'text'    => '여러 치과를 다녀봤지만 이렇게 충분히 설명해주시는 곳은 처음입니다. 전악 보철까지 진행했는데 의료진 협진이 정말 체계적이에요. 비용도 시작 전에 명확히 알려주셔서 신뢰가 갔습니다.',
		),
		array(
			'name'    => '이○○',
			'gender'  => '여성',
			'age'     => '30대',
			'service' => '투명교정',
			'rating'  => 5,
			'text'    => '인비절라인 투명교정 받았는데 처음에 걱정했던 것보다 훨씬 편했어요. 11F 교정과 원장님이 사진 시뮬레이션으로 결과를 미리 보여주셨고, 6개월 만에 만족스러운 결과를 얻었습니다.',
		),
		array(
			'name'    => '최○○',
			'gender'  => '남성',
			'age'     => '60대',
			'service' => '임플란트 + 보철',
			'rating'  => 5,
			'text'    => '고혈압이 있어서 다른 곳에서는 거절당했는데, 여기는 혈압 체크부터 약물까지 세심하게 봐주셨습니다. 수술 후 귀가 서비스까지 챙겨주셔서 감동이었어요.',
		),
		array(
			'name'    => '정○○',
			'gender'  => '여성',
			'age'     => '40대',
			'service' => '자연치아 살리기',
			'rating'  => 5,
			'text'    => '발치하고 임플란트 하라던 치아를 보존과 전문의 원장님이 살려주셨어요. 재근관치료로 자연치를 지킬 수 있어서 정말 감사합니다.',
		),
		array(
			'name'    => '한○○',
			'gender'  => '여성',
			'age'     => '50대',
			'service' => '심미 라미네이트',
			'rating'  => 5,
			'text'    => '앞니 라미네이트를 했는데 자연스럽게 잘 나왔어요. 무리한 치아 삭제 없이 보존적으로 해주신다는 점이 마음에 들었고, 결과도 만족합니다.',
		),
	);
}


/* ============================================================
 * 5. FAQ 데이터 (통합 페이지용)
 * ========================================================== */
function moondental_get_faqs() {
	return array(
		'예약·내원' => array(
			array( 'q' => '예약 없이 방문해도 진료가 가능한가요?',
				   'a' => '가능하지만 대기 시간이 발생할 수 있습니다. 가급적 전화(041-563-2875) 또는 카카오톡으로 사전 예약 후 방문해주세요.' ),
			array( 'q' => '초진 시 어떤 것을 준비해야 하나요?',
				   'a' => '신분증(또는 건강보험증), 복용 중인 약 목록, 타원 X-ray 파일(USB·이메일) 등을 가져오시면 진단이 더 빠르고 정확합니다.' ),
			array( 'q' => '주차가 가능한가요?',
				   'a' => '본원 지하 기계식 주차장 무료 이용 가능. SUV는 인근 신부 제5공영주차장(동남구 먹거리1길 10)에 주차 후 방문하시면 무료 주차 등록을 도와드립니다.' ),
			array( 'q' => '진료 시간이 어떻게 되나요?',
				   'a' => '평일(월·화·수·금) 09:00~20:30, 목요일 09:00~18:00, 토요일 09:00~14:00, 일요일·공휴일 휴진. 평일은 점심시간 없이 진료합니다.' ),
		),
		'임플란트' => array(
			array( 'q' => '임플란트 수술 후 통증이 많이 있나요?',
				   'a' => '국소마취 하에 진행되며 수술 자체는 거의 통증이 없습니다. PCA 자가진통조절기와 물방울 레이저로 통증을 최대한 줄입니다. 1~2일간의 둔한 통증은 진통제로 충분히 조절됩니다.' ),
			array( 'q' => '임플란트는 얼마나 오래 사용할 수 있나요?',
				   'a' => '관리에 따라 10~20년 이상 유지가 가능합니다. 1년 4회 정기 검진과 매일 구강위생 관리가 핵심이며, 평생 A/S 시스템을 운영합니다.' ),
			array( 'q' => '뼈가 부족하다고 들었는데 임플란트가 가능할까요?',
				   'a' => 'CT 정밀 분석 후 골이식·상악동 거상술·M-GBR 등 환자 골 상태에 맞춘 다양한 옵션이 있습니다. 가능한 한 골이식을 최소화하는 방향으로 계획합니다.' ),
			array( 'q' => '당일 임플란트(MMI)는 누구나 가능한가요?',
				   'a' => 'Moon Magic Implant는 발치+뼈이식+식립을 하루에 진행하는 시술입니다. 다만 뼈 상태·잇몸 건강·전신 상태에 따라 가능 여부가 결정됩니다. 사전 정밀 진단으로 안전을 확인 후 진행합니다.' ),
			array( 'q' => '건강보험 임플란트 혜택이 있나요?',
				   'a' => '만 65세 이상 건강보험 가입자는 평생 2개까지 본인부담 30%로 적용 가능합니다. 부분 무치악이 대상이며, 잔존치 하나만 있어도 가능합니다.' ),
		),
		'교정' => array(
			array( 'q' => '성인도 교정이 가능한가요?',
				   'a' => '잇몸·치주 상태가 건강하다면 50~60대도 교정이 가능합니다. 다만 청소년보다 치료 기간이 다소 길어질 수 있습니다.' ),
			array( 'q' => '투명교정과 일반 교정 중 어느 것이 좋나요?',
				   'a' => '케이스 난이도·라이프스타일·예산에 따라 다릅니다. 단순한 케이스는 투명교정이 효율적이고, 복잡한 케이스는 일반 교정이 더 정밀합니다. 정밀 진단 후 추천드립니다.' ),
			array( 'q' => '교정 중 통증은 얼마나 심한가요?',
				   'a' => '장치 부착 직후·조정 후 2~3일간 둔한 통증이 있을 수 있습니다. 진통제로 조절 가능하며 1주일 이내 적응됩니다.' ),
			array( 'q' => '교정 후 다시 돌아간다고 들었는데?',
				   'a' => '유지장치(retainer)를 권장대로 착용하면 거의 재발하지 않습니다. 처음 1년은 24시간, 이후 야간 착용을 평생 권장합니다.' ),
		),
		'심미·미백' => array(
			array( 'q' => '미백 효과가 영구적인가요?',
				   'a' => '아닙니다. 음식·음주·흡연 습관에 따라 6개월~2년 후 자연스럽게 다시 착색됩니다. 가정 미백 트레이로 주기적으로 유지하면 효과가 길어집니다.' ),
			array( 'q' => '라미네이트 시술하면 치아가 약해지지 않나요?',
				   'a' => '치아 삭제를 최소화하는 라미네이트는 자연치를 최대한 보존합니다. 다만 한 번 시술하면 되돌리기 어려우므로 충분한 상담 후 진행합니다.' ),
			array( 'q' => '신경치료한 치아도 미백 가능한가요?',
				   'a' => '일반 미백은 효과가 떨어지지만, 워킹 블리치(walking bleach)라는 내부 미백법으로 개선 가능합니다. 상담 시 적합 여부 확인 후 진행합니다.' ),
		),
		'턱관절·기타' => array(
			array( 'q' => '턱에서 소리만 나고 통증은 없는데 진료가 필요한가요?',
				   'a' => '디스크 변위 가능성이 있어 정기 검진을 권합니다. 진행되면 통증·개구 제한으로 발전할 수 있어 조기 진단이 중요합니다.' ),
			array( 'q' => '사랑니는 무조건 빼야 하나요?',
				   'a' => '아닙니다. 똑바로 잘 자리잡고 양치가 잘 되며 인접치에 영향을 주지 않으면 두고 봐도 됩니다. 매복·통증·인접치 손상이 있을 때만 발치를 권합니다.' ),
			array( 'q' => '아이는 몇 살부터 치과 진료를 받아야 하나요?',
				   'a' => '첫 어금니가 나오는 만 1~2세부터 치과 첫 방문을 권합니다. 검진은 6개월 단위 정기 권장. 만 12세 이하는 영구치 광중합 레진 충전이 건강보험 적용됩니다.' ),
			array( 'q' => '전신질환(고혈압·당뇨·심장)이 있어도 진료 가능한가요?',
				   'a' => '네, 안심하고 진료받으실 수 있습니다. 혈압기·당검사·심전도·산소포화도 측정기를 상시 보유하며, 복용 약물(혈전용해제·골다공증 약 등)을 사전에 체크 후 안전하게 진행합니다.' ),
		),
		'비용·결제' => array(
			array( 'q' => '비용은 미리 알 수 있나요?',
				   'a' => '비급여 진료(임플란트·교정·심미)는 CT·X-ray 진단 후 정확한 견적을 산정합니다. 초진 상담 시 옵션별 비용·기간을 모두 안내드립니다.' ),
			array( 'q' => '카드 결제, 할부 가능한가요?',
				   'a' => '모든 신용카드 결제 가능하며, 임플란트·교정 등 고액 진료는 카드사 무이자 할부도 가능합니다.' ),
			array( 'q' => '실손보험으로 보장이 되나요?',
				   'a' => '치과는 대부분 실손보험 대상이 아닙니다. 다만 사고로 인한 외상 치료·턱관절 일부는 보장 가능할 수 있으니 사전에 가입 보험사에 확인하세요.' ),
		),
	);
}
