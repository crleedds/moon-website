<?php
/**
 * 언어 선택 드롭다운 (Google Translate 기반)
 * 헤더 우측 상단에 노출. 선택 시 페이지 전체 실시간 번역.
 *
 * 지원 언어: 한국어(기본) / 영어 / 중국어 / 베트남어 / 러시아어 / 몽골어
 *
 * @package moondental-child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$languages = array(
	'ko'    => array( 'label' => '한국어',      'flag' => '🇰🇷' ),
	'en'    => array( 'label' => 'English',    'flag' => '🇺🇸' ),
	'zh-CN' => array( 'label' => '中文',        'flag' => '🇨🇳' ),
	'vi'    => array( 'label' => 'Tiếng Việt', 'flag' => '🇻🇳' ),
	'ru'    => array( 'label' => 'Русский',    'flag' => '🇷🇺' ),
	'mn'    => array( 'label' => 'Монгол',     'flag' => '🇲🇳' ),
);
?>
<div class="md-lang-switcher" id="md-lang-switcher">
	<button type="button" class="md-lang-switcher__btn" aria-haspopup="listbox" aria-expanded="false" aria-label="언어 선택 (Language)" data-track="lang-switcher-open">
		<svg class="md-lang-switcher__globe" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>
			<path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18" stroke="currentColor" stroke-width="1.6" fill="none"/>
		</svg>
		<span class="md-lang-switcher__current">한국어</span>
		<span class="md-lang-switcher__caret" aria-hidden="true">▾</span>
	</button>
	<ul class="md-lang-switcher__menu" role="listbox" aria-label="언어 목록" hidden>
		<?php foreach ( $languages as $code => $lang ) : ?>
			<li class="md-lang-switcher__item" role="option" data-lang="<?php echo esc_attr( $code ); ?>" tabindex="0">
				<span class="md-lang-switcher__flag" aria-hidden="true"><?php echo $lang['flag']; ?></span>
				<span class="md-lang-switcher__label"><?php echo esc_html( $lang['label'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
<div id="google_translate_element" aria-hidden="true"></div>
