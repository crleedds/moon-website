<?php
/**
 * Moon Dental · 커스텀 SVG 아이콘 세트
 *
 *  치과 진료 영역별 라인아트 아이콘.
 *  - 24×24 viewBox, stroke 1.6, currentColor → 다크모드 자동 대응
 *  - 이모지 대신 `icon:key` 형식으로 서비스 카드에 지정
 *  - 알 수 없는 key 또는 이모지가 들어오면 원본 그대로 렌더
 *
 *  Customizer 사용법:
 *    아이콘 필드에 아래 키 중 하나를 "icon:implant" 형식으로 입력.
 *    예) 임플란트 카드 아이콘 → icon:implant
 *
 *  등록된 키:
 *    implant, ortho, preserve, jaw, wisdom, aesthetic, whitening,
 *    pediatric, prevention, facility, general, tooth, sparkle, leaf
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 아이콘 SVG 정의 반환.
 * 각 SVG는 24×24 viewBox · fill=none · stroke=currentColor 기준으로 통일.
 */
function moondental_icon_svgs() {
	static $cache = null;
	if ( $cache !== null ) return $cache;

	// v3.33.1 · 라인아트 재설계 · stroke 2 · 굵고 명확한 실루엣
	$attrs = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';

	$cache = array(

		// 기본 치아 (bell/tooth 실루엣)
		'tooth' => '<svg ' . $attrs . '><path d="M8 3c-2.2 0-3.5 1.5-3.5 3.5 0 1.7.6 3.4 1.2 5 .6 1.6 1.1 3.3 1.5 5.1.3 1.3.8 2.4 1.6 2.4.7 0 1-.7 1.3-1.9l.6-2.5c.15-.7.5-1.1 1-1.1h.6c.5 0 .85.4 1 1.1l.6 2.5c.3 1.2.6 1.9 1.3 1.9.8 0 1.3-1.1 1.6-2.4.4-1.8.9-3.5 1.5-5.1.6-1.6 1.2-3.3 1.2-5C19.5 4.5 18.2 3 16 3c-1.3 0-2.2.5-3 1-0.8-.5-1.7-1-3-1z"/></svg>',

		// 임플란트 (치관 + 명확한 나사·스크류)
		'implant' => '<svg ' . $attrs . '><path d="M6.5 3h11c1 0 1.5.6 1.5 1.5V7c0 2.2-1.2 3.7-2.4 4.6L16 12H8l-.6-.4C6.2 10.7 5 9.2 5 7V4.5C5 3.6 5.5 3 6.5 3z"/><path d="M9 14h6"/><path d="M9 14v6.5c0 .3.2.5.5.5h5c.3 0 .5-.2.5-.5V14"/><path d="M9 16.5h6"/><path d="M9 19h6"/></svg>',

		// 교정 (와이어 + 브래킷 3개)
		'ortho' => '<svg ' . $attrs . '><path d="M2 12h20"/><rect x="3" y="7" width="4" height="10" rx="1"/><rect x="10" y="7" width="4" height="10" rx="1"/><rect x="17" y="7" width="4" height="10" rx="1"/></svg>',

		// 자연치아 살리기 (방패 + 체크 · 보존 상징)
		'preserve' => '<svg ' . $attrs . '><path d="M12 3l8 3v6c0 4.5-3.5 7.5-8 9-4.5-1.5-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5L16 9.5"/></svg>',

		// 턱관절 (하악골 곡선 + 양쪽 관절 볼)
		'jaw' => '<svg ' . $attrs . '><path d="M5 6c0 5.5 3 12 7 12s7-6.5 7-12"/><circle cx="5" cy="6" r="2.2"/><circle cx="19" cy="6" r="2.2"/></svg>',

		// 사랑니 (어금니 + 발치 화살표 위로)
		'wisdom' => '<svg ' . $attrs . '><path d="M12 8V3"/><path d="M9 5l3-2 3 2"/><path d="M6 12c0-2.5 1.5-4 3-4s2 1.5 3 1.5S13.5 8 15 8s3 1.5 3 4v3c0 2.5-1.5 5-3 5s-1-4-2-4h-2c-1 0-.5 4-2 4s-3-2.5-3-5z"/></svg>',

		// 심미치료 (치아 + 큰 반짝임)
		'aesthetic' => '<svg ' . $attrs . '><path d="M7.5 6c-1.7 0-2.5 1.3-2.5 3 0 1.5.5 3 1 4.4.5 1.4 1 3 1.4 4.5.3 1 .7 1.6 1.4 1.6s1-.6 1.2-1.5l.4-1.8c.15-.6.4-1 1-1h.6c.6 0 .85.4 1 1l.4 1.8c.2.9.5 1.5 1.2 1.5s1.1-.6 1.4-1.6c.4-1.5.9-3.1 1.4-4.5.5-1.4 1-2.9 1-4.4C17 7.3 16.2 6 14.5 6c-1 0-1.7.4-2.5.8-.8-.4-1.5-.8-2.5-.8z"/><path d="M19 3l.8 2.2L22 6l-2.2.8L19 9l-.8-2.2L16 6l2.2-.8z"/></svg>',

		// 미백 (치아 + 방사선/광선)
		'whitening' => '<svg ' . $attrs . '><path d="M8 10c-1.5 0-2.5 1-2.5 2.5 0 1.2.4 2.4.9 3.5.5 1.2.9 2.4 1.2 3.5.3.9.7 1.5 1.3 1.5s.9-.5 1.1-1.3l.3-1.4c.15-.5.4-.8.9-.8h.6c.5 0 .75.3.9.8l.3 1.4c.2.8.5 1.3 1.1 1.3s1-.6 1.3-1.5c.3-1.1.7-2.3 1.2-3.5.5-1.1.9-2.3.9-3.5C18.5 11 17.5 10 16 10c-1 0-1.7.4-2.5.8-.8-.4-1.5-.8-2.5-.8z"/><path d="M12 5V2M4.5 7L3 5.5M19.5 7l1.5-1.5"/></svg>',

		// 소아치과 (동그란 얼굴 + 눈·웃음)
		'pediatric' => '<svg ' . $attrs . '><circle cx="12" cy="12" r="9"/><circle cx="9" cy="10" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="10" r="1" fill="currentColor" stroke="none"/><path d="M8 14.5c1 1.7 2.5 2.5 4 2.5s3-.8 4-2.5"/></svg>',

		// 예방 (방패)
		'prevention' => '<svg ' . $attrs . '><path d="M12 3l8 3v6c0 4.5-3.5 7.5-8 9-4.5-1.5-8-4.5-8-9V6z"/></svg>',

		// 시설 (건물 + 층 구분)
		'facility' => '<svg ' . $attrs . '><rect x="4" y="3" width="16" height="18" rx="1.5"/><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><rect x="10.5" y="16.5" width="3" height="4.5"/></svg>',

		// 진료 (청진기)
		'general' => '<svg ' . $attrs . '><path d="M6 3v7c0 2.2 1.8 4 4 4s4-1.8 4-4V3"/><path d="M4 3h4M12 3h4"/><path d="M10 14v3c0 2.5 2 4.5 4.5 4.5S19 19.5 19 17v-1"/><circle cx="19" cy="14" r="2"/></svg>',

		// 반짝임 (단독)
		'sparkle' => '<svg ' . $attrs . '><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/></svg>',

		// 잎
		'leaf' => '<svg ' . $attrs . '><path d="M20 4C10 4 4 10 4 20c0 0 8-1 12-6s4-10 4-10z"/><path d="M4 20c4-4 8-6 12-8"/></svg>',

		// 다이아몬드
		'diamond' => '<svg ' . $attrs . '><path d="M6 3h12l4 5-10 13L2 8z"/><path d="M2 8h20"/><path d="M12 3l-4 5 4 13 4-13z"/></svg>',

		// 하트
		'heart' => '<svg ' . $attrs . '><path d="M12 21s-8-4.5-8-11c0-3 2.2-5 5-5 1.7 0 3 1 3 2 0-1 1.3-2 3-2 2.8 0 5 2 5 5 0 6.5-8 11-8 11z"/></svg>',
	);

	return $cache;
}

/**
 * 아이콘 렌더러.
 *
 * @param string $icon_value 값 형식:
 *   "icon:implant"   — SVG 아이콘 (정의된 키)
 *   "🦷"              — 이모지 원본 렌더
 *   ""               — 미출력
 * @param string $extra_class 추가 CSS 클래스
 * @return string HTML span
 */
function moondental_render_icon( $icon_value, $extra_class = '' ) {
	$icon_value = trim( (string) $icon_value );
	if ( $icon_value === '' ) return '';

	// SVG 아이콘 경우
	if ( strpos( $icon_value, 'icon:' ) === 0 ) {
		$key  = substr( $icon_value, 5 );
		$svgs = moondental_icon_svgs();
		if ( isset( $svgs[ $key ] ) ) {
			$class = trim( 'md-svg-icon ' . $extra_class );
			return '<span class="' . esc_attr( $class ) . '" aria-hidden="true">' . $svgs[ $key ] . '</span>';
		}
	}

	// 이모지 fallback
	$class = trim( 'md-emoji-icon ' . $extra_class );
	return '<span class="' . esc_attr( $class ) . '" aria-hidden="true">' . esc_html( $icon_value ) . '</span>';
}

/**
 * 이모지-only 백워드 호환 헬퍼 — 이전 코드가 `$icon` 만 echo 하던 곳에서 안전 fallback.
 */
function moondental_icon_or_emoji( $value ) {
	return moondental_render_icon( $value );
}
