<?php
/** Moon Dental Care · Clinical Cases — 환자 설명용 (자동 생성 + 손글 요약) */
if ( ! defined( 'ABSPATH' ) ) exit;

return array(
	'slug' => 'cases',
	'order' => 120,
	'group' => '임상 자료',
	'icon' => '📷',
	'title' => 'Clinical Cases',
	'tagline' => '치료 전후 임상 사진 슬라이드',
	'center' => '공통',
	'keywords' => '임상 케이스 전후 사진',
	'updated' => '2026.09',
	'summary' => array(
		'실제 치료 전·후 사진 모음입니다. 환자분과 볼 때는 <strong>비슷한 상황의 케이스</strong>를 골라 보여 주세요.',
	),
	'sections' => array(
		array(
			'id' => 'embed0',
			'title' => '임상 케이스 슬라이드',
			'embed' => 'https://docs.google.com/presentation/d/1ZUBVA5PAX2WPIzccIBfVWCHdTtAOLGbS1UgUP70yF_4/embed',
		),
	),
	'links' => array(
		array( 'label' => '임상 케이스 페이지', 'url' => '/임상-케이스/' ),
		array( 'label' => '슬라이드 원본', 'url' => 'https://docs.google.com/presentation/d/1ZUBVA5PAX2WPIzccIBfVWCHdTtAOLGbS1UgUP70yF_4/edit' ),
	),
);
