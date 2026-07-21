<?php
/**
 * Moon Dental · 치과 백과사전 (Encyclopedia)
 *
 *  진료 관련 용어 사전. 카테고리·초성·검색 필터.
 *  아카이브 URL: /치과사전/
 *  단일 URL:    /치과사전/{slug}/
 *
 *  v3.35.0: 신규 (사용자 요청 · bdbddc.com/encyclopedia/ 참고)
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================
 * 1. Custom Post Type · md_term (치과 용어)
 * ========================================================== */
function moondental_register_encyclopedia_cpt() {
	register_post_type( 'md_term', array(
		'labels' => array(
			'name'               => '치과사전 · 용어',
			'singular_name'      => '용어',
			'menu_name'          => '📖 치과사전',
			'add_new'            => '용어 추가',
			'add_new_item'       => '새 용어 추가',
			'edit_item'          => '용어 편집',
			'new_item'           => '새 용어',
			'view_item'          => '용어 보기',
			'view_items'         => '치과사전 보기',
			'search_items'       => '용어 검색',
			'not_found'          => '용어가 없습니다.',
			'not_found_in_trash' => '휴지통에 용어가 없습니다.',
			'all_items'          => '모든 용어',
			'archives'           => '치과사전',
		),
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,        // 블록 에디터 활성
		'menu_position'       => 21,
		'menu_icon'           => 'dashicons-book-alt',
		'has_archive'         => '치과사전',
		'rewrite'             => array( 'slug' => '치과사전', 'with_front' => false ),
		'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
		'taxonomies'          => array( 'md_term_category' ),
	) );

	register_taxonomy( 'md_term_category', 'md_term', array(
		'labels' => array(
			'name'          => '분야',
			'singular_name' => '분야',
			'menu_name'     => '분야',
			'search_items'  => '분야 검색',
			'all_items'     => '모든 분야',
			'edit_item'     => '분야 편집',
			'add_new_item'  => '새 분야 추가',
		),
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => '치과사전-분야' ),
	) );
}
add_action( 'init', 'moondental_register_encyclopedia_cpt', 5 );

/* 활성화 시 rewrite rules flush · 마이그레이션에서 처리 */
add_action( 'after_setup_theme', function() {
	if ( get_option( 'moondental_encyclopedia_flush_v3350' ) === 'done' ) return;
	moondental_register_encyclopedia_cpt();
	flush_rewrite_rules( false );
	update_option( 'moondental_encyclopedia_flush_v3350', 'done' );
}, 90 );


/* ============================================================
 * 2. 기본 분야 카테고리 시드 (첫 활성화 시)
 * ========================================================== */
function moondental_seed_encyclopedia_categories() {
	if ( get_option( 'moondental_encyclopedia_cats_v3350' ) === 'done' ) return;
	$cats = array(
		array( 'name' => '임플란트', 'slug' => 'implant', 'desc' => '임플란트 픽스처·지대주·보철·수술 관련 용어' ),
		array( 'name' => '교정',     'slug' => 'ortho', 'desc' => '투명교정·브라켓·유지장치·교합 용어' ),
		array( 'name' => '보존·신경치료', 'slug' => 'preserve', 'desc' => '충치·신경치료·자연치아 보존 용어' ),
		array( 'name' => '치주·잇몸', 'slug' => 'periodontics', 'desc' => '잇몸염·스케일링·치주치료 용어' ),
		array( 'name' => '심미치료', 'slug' => 'aesthetic', 'desc' => '라미네이트·미백·심미보철 용어' ),
		array( 'name' => '보철',     'slug' => 'prosthetics', 'desc' => '크라운·틀니·브릿지 용어' ),
		array( 'name' => '구강외과·사랑니', 'slug' => 'surgery', 'desc' => '사랑니·매복치·발치·외과 용어' ),
		array( 'name' => '턱관절',   'slug' => 'tmj', 'desc' => 'TMJ·이갈이·개구장애 관련 용어' ),
		array( 'name' => '소아치과', 'slug' => 'pediatric', 'desc' => '어린이 치과 진료 관련 용어' ),
		array( 'name' => '예방·검진', 'slug' => 'prevention', 'desc' => '스케일링·불소·실란트·검진 용어' ),
		array( 'name' => '일반 치의학', 'slug' => 'general', 'desc' => '치아 구조·해부학·기초 용어' ),
	);
	foreach ( $cats as $c ) {
		if ( term_exists( $c['slug'], 'md_term_category' ) ) continue;
		wp_insert_term( $c['name'], 'md_term_category', array(
			'slug'        => $c['slug'],
			'description' => $c['desc'],
		) );
	}
	update_option( 'moondental_encyclopedia_cats_v3350', 'done' );
}
// v3.37.0 · 마이그레이션은 admin_init로 (프론트 요청마다 get_option 호출 방지 + 관리자 컨텍스트에서만 실행)
add_action( 'admin_init', 'moondental_seed_encyclopedia_categories', 20 );


/* ============================================================
 * 3. 초성 헬퍼 · 한글 초성 추출 (아카이브 필터용)
 * ========================================================== */
function moondental_hangul_initial( $str ) {
	$str = trim( (string) $str );
	if ( $str === '' ) return '';
	// 첫 문자
	$first = mb_substr( $str, 0, 1, 'UTF-8' );
	$code  = 0;
	// UTF-8 → codepoint
	$bytes = unpack( 'C*', $first );
	if ( count( $bytes ) === 1 ) {
		$code = $bytes[1];
	} elseif ( count( $bytes ) === 2 ) {
		$code = ( ( $bytes[1] & 0x1F ) << 6 ) | ( $bytes[2] & 0x3F );
	} elseif ( count( $bytes ) === 3 ) {
		$code = ( ( $bytes[1] & 0x0F ) << 12 ) | ( ( $bytes[2] & 0x3F ) << 6 ) | ( $bytes[3] & 0x3F );
	} elseif ( count( $bytes ) === 4 ) {
		$code = ( ( $bytes[1] & 0x07 ) << 18 ) | ( ( $bytes[2] & 0x3F ) << 12 ) | ( ( $bytes[3] & 0x3F ) << 6 ) | ( $bytes[4] & 0x3F );
	}
	// 한글 음절 (가~힣): U+AC00 ~ U+D7A3
	if ( $code < 0xAC00 || $code > 0xD7A3 ) {
		return mb_strtoupper( $first, 'UTF-8' );
	}
	$initials = array( 'ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ' );
	$idx = intdiv( $code - 0xAC00, 588 );
	return $initials[ $idx ] ?? '';
}
/* 초성 매칭 그룹 (쌍자음 → 단자음으로 통합) */
function moondental_initial_groups() {
	return array( 'ㄱ', 'ㄴ', 'ㄷ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅅ', 'ㅇ', 'ㅈ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ' );
}
function moondental_initial_group( $ch ) {
	$map = array( 'ㄲ'=>'ㄱ', 'ㄸ'=>'ㄷ', 'ㅃ'=>'ㅂ', 'ㅆ'=>'ㅅ', 'ㅉ'=>'ㅈ' );
	return $map[ $ch ] ?? $ch;
}


/* ============================================================
 * 4. 초기 시드 용어 (마이그레이션 · 첫 활성화 시 20개 예시)
 *    이후 사용자가 wp-admin → 📖 치과사전에서 추가·편집.
 * ========================================================== */
// v3.37.0 · admin_init로 이동
add_action( 'admin_init', function() {
	if ( get_option( 'moondental_encyclopedia_seed_v3350' ) === 'done' ) return;

	$terms = array(
		array( 'title' => '임플란트', 'cat' => 'implant', 'excerpt' => '치아 뿌리 역할을 하는 인공 치근을 잇몸뼈에 심고 그 위에 인공 치아를 만드는 시술', 'body' => '<p>임플란트는 발치 후 잇몸뼈에 <strong>인공 치근(픽스처)</strong>을 식립하고, 뼈와의 골유착이 이루어진 후 그 위에 <strong>지대주(어버트먼트)</strong>와 <strong>크라운</strong>을 장착하는 3단계 치료입니다.</p><p>자연치아와 가장 유사한 저작 기능을 회복할 수 있으며, 관리에 따라 10~20년 이상 유지 가능합니다.</p>' ),
		array( 'title' => '지대주', 'cat' => 'implant', 'excerpt' => '임플란트 픽스처와 최종 크라운을 연결하는 중간 부품 (어버트먼트)', 'body' => '<p>지대주(어버트먼트)는 임플란트 시스템에서 <strong>픽스처와 크라운을 연결</strong>하는 핵심 부품입니다.</p><p>공장 규격 <strong>기성 지대주</strong>와 환자별 <strong>맞춤형 지대주(Custom Abutment)</strong>가 있으며, 문치과병원은 맞춤형 지대주만 사용해 잇몸 형태에 최적화된 보철을 제작합니다.</p>' ),
		array( 'title' => '골유착', 'cat' => 'implant', 'excerpt' => '임플란트 표면이 잇몸뼈와 화학적·구조적으로 결합되는 과정', 'body' => '<p>골유착(Osseointegration)은 티타늄 임플란트 표면과 살아있는 골 조직이 <strong>세포 수준에서 직접 결합</strong>되는 현상입니다.</p><p>보통 3~6개월 소요되며, UV·칼슘 이온 표면처리 등으로 골유착 속도를 앞당길 수 있습니다.</p>' ),
		array( 'title' => 'CBCT', 'cat' => 'general', 'excerpt' => '3D 콘빔 컴퓨터 단층촬영 · 치과 정밀 진단의 표준 장비', 'body' => '<p>CBCT(Cone Beam Computed Tomography)는 <strong>3차원 X-ray 촬영 장비</strong>로 치아·잇몸뼈·신경관·상악동을 입체적으로 확인할 수 있습니다.</p><p>임플란트 위치 설계, 매복 사랑니 신경 위치 확인, 근관치료 분지 파악 등에 필수적입니다. 일반 X-ray 대비 방사선량은 CT의 1/10 수준.</p>' ),

		array( 'title' => '투명교정', 'cat' => 'ortho', 'excerpt' => '눈에 잘 띄지 않는 투명 플라스틱 장치를 이용한 교정 (인비절라인·슈어스마일 등)', 'body' => '<p>투명교정은 개인 맞춤 제작된 <strong>투명 얼라이너</strong>를 2주 간격으로 교체하며 치아를 점진적으로 이동시키는 방법입니다.</p><p>탈부착이 가능해 식사·양치가 편하며, 심미성이 뛰어납니다. 대표 브랜드: 슈어스마일(SureSmile), 인비절라인.</p>' ),
		array( 'title' => '설측교정', 'cat' => 'ortho', 'excerpt' => '치아 안쪽(혀 쪽)에 브라켓을 부착해 밖에서 보이지 않게 하는 교정', 'body' => '<p>설측교정은 <strong>치아의 혀 쪽 면</strong>에 브라켓을 붙여 앞에서는 보이지 않는 교정 방법입니다.</p><p>초기 발음·불편감이 있을 수 있지만 심미성이 최고 수준입니다.</p>' ),

		array( 'title' => '신경치료', 'cat' => 'preserve', 'excerpt' => '치아 속 염증·괴사된 신경을 제거하고 근관을 깨끗이 소독·충전하는 치료', 'body' => '<p>신경치료(근관치료·Endodontics)는 충치가 신경에 이르러 심한 통증·염증이 생긴 치아를 <strong>발치 없이 보존</strong>하기 위한 치료입니다.</p><p>1) 신경 제거 → 2) 근관 세척·소독 → 3) 영구 충전 → 4) 크라운 마무리 순으로 진행되며 통상 2~4회 내원. CBCT 3D 진단과 NiTi 회전 파일로 정밀도가 크게 향상됐습니다.</p>' ),
		array( 'title' => '재근관치료', 'cat' => 'preserve', 'excerpt' => '이전 신경치료가 실패한 치아를 다시 치료해 발치를 피하는 시술', 'body' => '<p>재근관치료는 다른 곳에서 신경치료를 받았지만 <strong>염증이 재발하거나 실패한 치아</strong>를 발치 없이 다시 살리는 시술입니다.</p><p>기존 충전재를 제거하고 남은 신경관 조직·세균을 재세척한 뒤 재충전합니다. 성공률을 높이려면 정밀 진단과 숙련된 술기가 필요합니다.</p>' ),
		array( 'title' => '치근단수술', 'cat' => 'preserve', 'excerpt' => '근관치료로 해결되지 않는 치근 끝 염증을 외과적으로 제거하는 수술', 'body' => '<p>치근단수술(Apicoectomy)은 일반 근관치료·재근관치료로 해결되지 않는 <strong>치근 끝(치근단) 염증</strong>을 잇몸을 열고 직접 제거하는 마이크로 수술입니다. 구강악안면외과 협진.</p>' ),

		array( 'title' => '스케일링', 'cat' => 'periodontics', 'excerpt' => '치석·치태를 초음파로 제거하는 예방·치주치료의 기본', 'body' => '<p>스케일링은 양치질로 제거되지 않는 <strong>치석과 치태를 초음파 스케일러로 제거</strong>하는 시술입니다.</p><p>만 19세 이상 <strong>연 1회 건강보험 적용</strong>(1월 1일 갱신). 6~12개월 주기 정기 스케일링이 자연치아 평생 보존의 핵심입니다.</p>' ),
		array( 'title' => '치주염', 'cat' => 'periodontics', 'excerpt' => '치석·세균으로 잇몸과 치조골에 만성 염증이 진행되는 질환', 'body' => '<p>치주염은 잇몸염(치은염)에서 진행되어 <strong>치조골까지 파괴</strong>되는 만성 염증 질환입니다.</p><p>증상: 잇몸 출혈·부기, 잇몸 퇴축, 치아 흔들림, 입냄새. 단계별로 스케일링 → 치근활택술 → 치주소파술 → 치주 판막수술로 진행됩니다.</p>' ),
		array( 'title' => 'PDRN 잇몸 주사', 'cat' => 'periodontics', 'excerpt' => 'DNA 단편 성분으로 잇몸 재생·염증 완화를 촉진하는 주사', 'body' => '<p>PDRN(Polydeoxyribonucleotide)은 <strong>연어 정소에서 추출한 DNA 단편</strong> 성분으로 조직 재생과 항염 효과가 있습니다.</p><p>잇몸에 주사하면 염증 완화·혈류 개선·재생 촉진 효과가 있으며 인체에 안전합니다.</p>' ),

		array( 'title' => '라미네이트', 'cat' => 'aesthetic', 'excerpt' => '얇은 세라믹 쉘을 앞니 표면에 부착해 색·모양을 개선하는 심미치료', 'body' => '<p>라미네이트는 <strong>0.3~0.5mm 얇은 세라믹 쉘</strong>을 자연치아 표면에 부착해 색·모양·틈을 개선하는 심미 시술입니다.</p><p>최소 삭제 라미네이트(Minimal Prep)는 치아 삭제량을 최소화해 자연치아 보존율이 높습니다. e.max·Empress 등 프리미엄 세라믹 사용.</p>' ),
		array( 'title' => '치아미백', 'cat' => 'aesthetic', 'excerpt' => '고농도 미백제로 치아 내부 색소를 분해해 밝게 만드는 시술', 'body' => '<p>치아미백은 <strong>과산화수소·과산화요소</strong> 기반 미백제를 사용해 치아 표면과 내부의 색소를 분해합니다.</p><p>홈 화이트닝(4주 키트), 1-Day 전문가 미백, 2-Day 전문가 미백, 복합 미백 등 옵션이 있으며 임신·수유 중에는 권장하지 않습니다.</p>' ),
		array( 'title' => '거미스마일', 'cat' => 'aesthetic', 'excerpt' => '웃을 때 잇몸이 3mm 이상 노출되는 상태 · 원인별 맞춤 치료', 'body' => '<p>거미스마일(Gummy Smile)은 웃을 때 <strong>잇몸이 3mm 이상 노출</strong>되는 상태입니다.</p><p>원인: 잇몸 라인이 낮음, 치아 길이가 짧음, 윗입술 근육 과활동, 상악골 과성장. 원인별로 잇몸 성형·크라운 연장·보톡스·양악 협진 등 맞춤 치료가 필요합니다.</p>' ),

		array( 'title' => '지르코니아 크라운', 'cat' => 'prosthetics', 'excerpt' => '강도·심미성이 우수한 고급 세라믹 크라운', 'body' => '<p>지르코니아 크라운은 <strong>산화 지르코늄 소재</strong>의 세라믹 크라운으로 금속과 유사한 강도, 우수한 심미성, 알레르기 없는 생체친화성을 갖습니다.</p><p>어금니·앞니 모두 사용 가능하며 파절 위험이 낮습니다.</p>' ),
		array( 'title' => '금 크라운 (골드)', 'cat' => 'prosthetics', 'excerpt' => '내구성과 정밀도가 최고 수준인 전통 보철 재료', 'body' => '<p>금 크라운은 <strong>강도·연성·정밀 적합도</strong>가 가장 뛰어난 전통 보철입니다.</p><p>어금니에 적합하며 마모가 자연치아와 유사해 반대편 치아 보호에도 유리합니다. 심미성이 떨어져 앞니에는 권장하지 않습니다.</p>' ),

		array( 'title' => '사랑니', 'cat' => 'surgery', 'excerpt' => '어금니 가장 안쪽에 나는 3번째 큰 어금니 · 매복 시 발치 권장', 'body' => '<p>사랑니(제3대구치)는 보통 <strong>17~25세경 마지막으로 나는 어금니</strong>입니다.</p><p>공간 부족·비뚤어진 방향으로 매복되는 경우가 많아, 청소 어려움·주변 치아 압박·낭종 위험이 있어 <strong>CBCT 진단 후 발치를 권장</strong>하는 경우가 많습니다.</p>' ),
		array( 'title' => '매복치', 'cat' => 'surgery', 'excerpt' => '잇몸·뼈 속에 갇혀 나오지 못한 치아', 'body' => '<p>매복치는 정상적인 위치로 나오지 못하고 <strong>잇몸이나 잇몸뼈 속에 파묻힌 치아</strong>입니다.</p><p>사랑니가 가장 흔하며, 신경관과의 위치를 CBCT로 정밀 확인해 안전하게 발치합니다. 진정요법 병행 가능.</p>' ),

		array( 'title' => '턱관절 장애', 'cat' => 'tmj', 'excerpt' => '턱관절에 통증·소리·개구장애가 나타나는 복합 질환', 'body' => '<p>턱관절 장애(TMD)는 <strong>턱관절과 주변 근육</strong>에 문제가 생겨 나타나는 증상군입니다.</p><p>증상: 입 벌릴 때 딱딱 소리, 턱 주변 통증, 두통, 입이 잘 벌어지지 않음. 스플린트·물리치료·이갈이 습관 교정 등 보존적 치료 우선.</p>' ),
		array( 'title' => '이갈이 (브럭시즘)', 'cat' => 'tmj', 'excerpt' => '수면 중 무의식적으로 이를 갈거나 꽉 무는 습관', 'body' => '<p>이갈이(브럭시즘)는 주로 수면 중 <strong>무의식적으로 이를 갈거나 꽉 무는 습관</strong>입니다.</p><p>치아 마모, 턱관절 장애, 두통·목 통증을 유발할 수 있으며 <strong>맞춤 마우스가드(스플린트)</strong>로 치아를 보호합니다.</p>' ),

		array( 'title' => '실란트 (홈메우기)', 'cat' => 'prevention', 'excerpt' => '어금니 씹는 면의 홈을 메워 충치를 예방하는 시술', 'body' => '<p>실란트는 어금니 씹는 면의 <strong>깊은 홈(fissure)</strong>을 특수 재료로 메워 음식물 끼임과 충치 시작을 차단하는 시술입니다.</p><p>만 18세 이하는 제1·2 큰어금니에 <strong>건강보험 적용</strong>(본인부담 약 21,700원). 통증 없이 10분 이내 시술.</p>' ),
		array( 'title' => '불소도포', 'cat' => 'prevention', 'excerpt' => '고농도 불소로 치아 재광화·충치 예방 효과', 'body' => '<p>불소도포는 <strong>고농도 불소</strong>를 치아 표면에 도포해 법랑질을 강화하고 충치를 예방하는 시술입니다.</p><p>어린이는 3개월~1년 주기, 성인은 시린 증상 완화·충치 재발 예방 목적으로 권장됩니다.</p>' ),
		array( 'title' => '에어플로우', 'cat' => 'prevention', 'excerpt' => '미세 분말로 색소 침착·바이오필름을 정밀 제거하는 시술', 'body' => '<p>에어플로우(Air Flow)는 고운 <strong>미세 분말과 물</strong>을 동시 분사해 치아 표면과 잇몸 라인의 색소 침착과 바이오필름을 제거하는 시술입니다.</p><p>커피·와인·담배 착색 제거에 특히 효과적이며, 임플란트 주변 관리에도 안전합니다.</p>' ),

		array( 'title' => '치아 어금니 · 앞니', 'cat' => 'general', 'excerpt' => '성인 영구치 28~32개의 위치·기능별 명칭', 'body' => '<p>성인 영구치는 총 <strong>28~32개</strong>(사랑니 포함)입니다.</p><ul><li><strong>앞니(절치)</strong>: 위/아래 4개씩 · 음식 자르기</li><li><strong>송곳니(견치)</strong>: 각 1개씩 · 음식 찢기</li><li><strong>작은어금니(소구치)</strong>: 각 2개씩 · 음식 부수기</li><li><strong>큰어금니(대구치)</strong>: 각 2~3개씩 · 음식 갈기</li></ul>' ),
		array( 'title' => '치석 · 치태', 'cat' => 'general', 'excerpt' => '치아에 쌓이는 세균성 침착물의 두 단계', 'body' => '<p><strong>치태(플라크)</strong>는 음식물·세균이 뭉친 <strong>부드러운 막</strong>으로 양치질로 제거 가능합니다.</p><p><strong>치석</strong>은 치태가 침에 있는 미네랄과 결합해 <strong>딱딱하게 굳은 것</strong>으로 양치로는 제거되지 않고 <strong>스케일링</strong>이 필요합니다.</p>' ),
		array( 'title' => '충치 (우식증)', 'cat' => 'preserve', 'excerpt' => '치아를 부식시키는 세균성 질환 · 진행 단계별 치료', 'body' => '<p>충치(치아우식증)는 <strong>뮤탄스균이 만든 산이 치아를 부식</strong>시키는 세균성 질환입니다.</p><p>단계별 치료: 초기(불소·실란트) → 중기(레진 충전) → 진행(세라믹 인레이·크라운) → 심부(신경치료) → 광범위(발치+임플란트).</p>' ),
	);

	$total = 0;
	foreach ( $terms as $t ) {
		// 중복 체크 (제목으로) · WP 6.2+ 호환
		$existing = get_posts( array(
			'post_type'      => 'md_term',
			'title'          => $t['title'],
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( ! empty( $existing ) ) continue;

		$post_id = wp_insert_post( array(
			'post_type'    => 'md_term',
			'post_status'  => 'publish',
			'post_title'   => $t['title'],
			'post_excerpt' => $t['excerpt'],
			'post_content' => $t['body'],
			'post_name'    => sanitize_title( $t['title'] ),
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			$term = get_term_by( 'slug', $t['cat'], 'md_term_category' );
			if ( $term ) {
				wp_set_object_terms( $post_id, array( $term->term_id ), 'md_term_category' );
			}
			$total++;
		}
	}

	update_option( 'moondental_encyclopedia_seed_v3350', 'done' );
}, 30 );
