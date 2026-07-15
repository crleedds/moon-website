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

	$attrs = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';

	$cache = array(

		// 이 (기본 치아 - 왕관 모양)
		'tooth' => '<svg ' . $attrs . '><path d="M8.5 3.5c-2.2 0-3.5 1.5-3.5 3.5 0 1.5.5 3 1 4.5.5 1.8 1 4 1.5 6 .3 1.2.8 2 1.5 2s1-.6 1.3-1.7l.5-2.4c.2-.8.5-1.4 1.2-1.4s1 .6 1.2 1.4l.5 2.4c.3 1.1.6 1.7 1.3 1.7s1.2-.8 1.5-2c.5-2 1-4.2 1.5-6 .5-1.5 1-3 1-4.5 0-2-1.3-3.5-3.5-3.5-1.2 0-2 .5-3 1-1-.5-1.8-1-3-1z"/></svg>',

		// 임플란트 (치아 위 + 스크류 아래)
		'implant' => '<svg ' . $attrs . '><path d="M8 3c-1.8 0-3 1.3-3 3 0 1 .3 2 .7 3.3.4 1.2.7 2.5 1 3.5.3.9.7 1.5 1.3 1.5s1-.5 1.2-1.3l.3-1.5c.15-.6.4-1 .9-1h.6c.5 0 .75.4.9 1l.3 1.5c.2.8.6 1.3 1.2 1.3s1-.6 1.3-1.5c.3-1 .6-2.3 1-3.5.4-1.3.7-2.3.7-3.3 0-1.7-1.2-3-3-3-1 0-1.6.4-2.4.9-.8-.5-1.4-.9-2.4-.9z"/><path d="M12 14v6"/><path d="M10 15.5h4"/><path d="M10 17.5h4"/><path d="M10 19.5h4"/></svg>',

		// 교정 (치아 3개 + 브래킷 라인)
		'ortho' => '<svg ' . $attrs . '><path d="M4 10h16"/><path d="M4 14h16"/><rect x="6" y="7" width="3.5" height="10" rx="0.8"/><rect x="10.25" y="7" width="3.5" height="10" rx="0.8"/><rect x="14.5" y="7" width="3.5" height="10" rx="0.8"/></svg>',

		// 자연치아 살리기 (치아 + 잎)
		'preserve' => '<svg ' . $attrs . '><path d="M8.5 3.5c-2.2 0-3.5 1.5-3.5 3.5 0 1.5.5 3 1 4.5.5 1.8 1 4 1.5 6 .3 1.2.8 2 1.5 2s1-.6 1.3-1.7l.5-2.4c.2-.8.5-1.4 1.2-1.4s1 .6 1.2 1.4l.5 2.4c.3 1.1.6 1.7 1.3 1.7s1.2-.8 1.5-2c.5-2 1-4.2 1.5-6 .5-1.5 1-3 1-4.5 0-2-1.3-3.5-3.5-3.5-1.2 0-2 .5-3 1-1-.5-1.8-1-3-1z"/><path d="M15.5 6.5c1.5-1.5 3.5-1 3.5-1s.5 2-1 3.5c-1 1-2.5.8-2.5.8s-.2-1.8 0-3.3z"/><path d="M15 8.5c1-1 3-1.5 4-1.5"/></svg>',

		// 턱관절 (하악골 곡선 + 관절 점)
		'jaw' => '<svg ' . $attrs . '><path d="M4 8c0 4 2.5 8 8 10 5.5-2 8-6 8-10"/><circle cx="4" cy="8" r="1.8"/><circle cx="20" cy="8" r="1.8"/><path d="M8 13v2"/><path d="M12 15v2"/><path d="M16 13v2"/></svg>',

		// 사랑니 발치 (매복 어금니 + 화살표)
		'wisdom' => '<svg ' . $attrs . '><path d="M6 5c-1.5 0-2.5 1.2-2.5 3 0 1 .5 2 1 3.5.5 1.5 1 3 1.3 4.5.2 1.2.7 2 1.7 2s1.5-.8 1.7-2l.3-1.5c.1-.6.4-1 .8-1s.7.4.8 1l.3 1.5c.2 1.2.7 2 1.7 2s1.5-.8 1.7-2c.3-1.5.8-3 1.3-4.5.5-1.5 1-2.5 1-3.5 0-1.8-1-3-2.5-3-1 0-1.7.5-2.5 1-.8-.5-1.5-1-2.5-1z"/><path d="M18 17l3 3M21 17l-3 3"/></svg>',

		// 심미치료 (치아 + 반짝임)
		'aesthetic' => '<svg ' . $attrs . '><path d="M8.5 5.5c-2 0-3 1.4-3 3.2 0 1.4.4 2.7 1 4.1.5 1.6 1 3.6 1.4 5.4.2 1 .6 1.8 1.3 1.8s.9-.5 1.1-1.5l.4-2.2c.15-.7.4-1.2 1-1.2h.6c.6 0 .85.5 1 1.2l.4 2.2c.2 1 .4 1.5 1.1 1.5s1.1-.8 1.3-1.8c.4-1.8.9-3.8 1.4-5.4.6-1.4 1-2.7 1-4.1 0-1.8-1-3.2-3-3.2-1 0-1.7.5-2.5.9-.8-.4-1.5-.9-2.5-.9z"/><path d="M18 3l.7 2.3L21 6l-2.3.7L18 9l-.7-2.3L15 6l2.3-.7z"/><path d="M20 12l.5 1.5L22 14l-1.5.5L20 16l-.5-1.5L18 14l1.5-.5z"/></svg>',

		// 미백 (치아 + 광선)
		'whitening' => '<svg ' . $attrs . '><path d="M8.5 7.5c-2 0-3 1.4-3 3.2 0 1.4.4 2.7 1 4.1.5 1.4 1 3 1.4 4.4.2.9.6 1.5 1.3 1.5s.9-.5 1.1-1.3l.4-1.8c.15-.6.4-1 1-1h.6c.6 0 .85.4 1 1l.4 1.8c.2.8.4 1.3 1.1 1.3s1.1-.6 1.3-1.5c.4-1.4.9-3 1.4-4.4.6-1.4 1-2.7 1-4.1 0-1.8-1-3.2-3-3.2-1 0-1.7.4-2.5.8-.8-.4-1.5-.8-2.5-.8z"/><path d="M12 4v-2M4 6l-1.4-1.4M20 6l1.4-1.4M6 3.5l.5-.5M18 3.5l-.5-.5"/></svg>',

		// 소아치과 (작은 치아 + 웃음)
		'pediatric' => '<svg ' . $attrs . '><path d="M8.5 3.5c-2.2 0-3.5 1.5-3.5 3.5 0 1.5.5 3 1 4.5.5 1.8 1 4 1.5 6 .3 1.2.8 2 1.5 2s1-.6 1.3-1.7l.5-2.4c.2-.8.5-1.4 1.2-1.4s1 .6 1.2 1.4l.5 2.4c.3 1.1.6 1.7 1.3 1.7s1.2-.8 1.5-2c.5-2 1-4.2 1.5-6 .5-1.5 1-3 1-4.5 0-2-1.3-3.5-3.5-3.5-1.2 0-2 .5-3 1-1-.5-1.8-1-3-1z"/><circle cx="9.5" cy="9.5" r="0.6" fill="currentColor"/><circle cx="14.5" cy="9.5" r="0.6" fill="currentColor"/><path d="M10 12.5c.5.7 1.2 1 2 1s1.5-.3 2-1"/></svg>',

		// 예방 (방패 + 체크)
		'prevention' => '<svg ' . $attrs . '><path d="M12 3l7 3v5c0 4-3 7-7 9-4-2-7-5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg>',

		// 시설 (건물 + 층)
		'facility' => '<svg ' . $attrs . '><rect x="4" y="4" width="16" height="16" rx="1.5"/><line x1="4" y1="10" x2="20" y2="10"/><line x1="4" y1="16" x2="20" y2="16"/><rect x="10.5" y="17.5" width="3" height="2.5"/></svg>',

		// 진료 (청진기)
		'general' => '<svg ' . $attrs . '><path d="M6 3v7c0 2.2 1.8 4 4 4s4-1.8 4-4V3"/><path d="M4 3h4M12 3h4"/><path d="M10 14v3c0 2.2 1.8 4 4 4s4-1.8 4-4v-2"/><circle cx="18" cy="13" r="2"/></svg>',

		// 반짝임 (단독)
		'sparkle' => '<svg ' . $attrs . '><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 17l.6 1.4L21 19l-1.4.6L19 21l-.6-1.4L17 19l1.4-.6z"/></svg>',

		// 잎 (단독)
		'leaf' => '<svg ' . $attrs . '><path d="M4 20c8 0 16-4 16-16-8 0-16 4-16 16z"/><path d="M4 20c4-4 8-6 12-8"/></svg>',

		// 다이아몬드
		'diamond' => '<svg ' . $attrs . '><path d="M12 3l4 5-4 13-4-13z"/><path d="M4 8l4-5h8l4 5"/><path d="M4 8h16"/></svg>',

		// 하트 (평생/가족)
		'heart' => '<svg ' . $attrs . '><path d="M12 20s-7-4-7-10c0-3 2-5 4.5-5 1.5 0 2.5 1 2.5 1s1-1 2.5-1c2.5 0 4.5 2 4.5 5 0 6-7 10-7 10z"/></svg>',
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
