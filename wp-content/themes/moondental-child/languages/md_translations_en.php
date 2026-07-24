<?php
/**
 * English translations for md_content() keys.
 * v3.43.0 · initial seed · 40+ core strings.
 * 없는 키는 자동으로 한국어 원본 fallback.
 *
 * 편집 팁: 추가하고 싶으면 'key' => 'Translation', 형식으로 계속 append.
 * key는 customizer-content.php의 md_content 키와 일치해야 함.
 */
return array(

	/* === Hero (홈 첫 화면) === */
	'hero_eyebrow'       => 'Cheonan-Asan Leading Dental Hospital · Nationwide Patients',
	'hero_title_a'       => 'For 30+ Years in Cheonan-Asan,',
	'hero_title_b'       => 'Nationwide Patients Trust Our Integrated Care',
	'hero_lead'          => "Implant · Clear aligner · Laminate · Natural tooth preservation.\nSpecialists in every field, all in one place — we listen fully and only recommend what's truly necessary.",
	'hero_cta_url'       => '/en/reservation/',
	'hero_cta_label'     => '📅 Book Consultation',

	/* === Mission (사명) === */
	'mission_band_eyebrow' => 'OUR MISSION · Hanah Medical Foundation Moon Dental Hospital',
	'mission_band_text'    => 'Our mission at Hanah Medical Foundation Moon Dental Hospital is to earn our patients trust through dignified care and service, and to become the most respected hospital by contributing to society through sharing and volunteering.',
	'mission_certs'        => "🏥|Nationally Designated Oral Health Screening Dental Clinic\n🌐|International Patient Attraction Medical Institution\n🪖|Medical Provider for US Military and Families\n🦷|Cheonan City Tooth Love Program Partner Hospital\n🔗|Samsung Medical Center Partner Hospital\n➕|Korean Red Cross Partner Hospital",

	/* === Trust Stats (신뢰 지표 4개) === */
	'trust_1_value' => '30',
	'trust_1_unit'  => 'yrs',
	'trust_1_label' => 'Since 1995',
	'trust_2_value' => '11',
	'trust_2_unit'  => '',
	'trust_2_label' => 'Specialty Areas',
	'trust_3_value' => '4',
	'trust_3_unit'  => 'floors',
	'trust_3_label' => 'Integrated Care Center',
	'trust_4_value' => '1:1',
	'trust_4_unit'  => '',
	'trust_4_label' => 'Personal Pre-consultation',

	/* === Bottom CTA Banner === */
	'cta_eyebrow' => 'BOOK CONSULTATION',
	'cta_title'   => 'Cheonan-Asan Leading Dental Hospital · Nationwide Patients Choose Us',
	'cta_lead'    => "We listen to your situation first, and only recommend what is truly needed.\nBook now and we will contact you within business hours.",
	'cta_hint'    => 'Hours: Mon·Tue·Wed·Fri 9:00–20:30 · Thu 9:00–18:30 · Sat 9:00–14:00 · Sunday/Holidays closed',

	/* === Contact / Reservation CTA (predefined labels used in md_render_reservation_ctas) === */
	'cta_btn_naver_label' => '📅 Naver Reservation',
	'cta_btn_kakao_label' => '💬 KakaoTalk Consultation',
	'cta_btn_call_label'  => '📞 Call Consultation',

	/* === Section-level page-specific CTA copies === */
	'cta_location_title' => "Now that you've found your way,\nplease book your visit",
	'cta_location_lead'  => 'Moon Tower 9·10·11·13F · Free onsite parking · Choose whichever contact method works for you.',
	'cta_facility_title' => 'Come see our facility in person',
	'cta_facility_lead'  => 'Visit consultations available anytime — book in advance to skip the wait.',
	'cta_faq_title'      => 'Still have questions?',
	'cta_faq_lead'       => "If the FAQ doesn't answer your question, please feel free to reach out.",
	'cta_news_title'     => 'Wondering about a specific treatment?',
	'cta_news_lead'      => "For related consultations, please contact us. We'll respond within business hours.",
	'cta_enc_title'      => 'Does this apply to your case?',
	'cta_enc_lead'       => 'The encyclopedia is for reference. Accurate diagnosis and treatment planning require an in-person consultation.',
	'cta_history_title'  => '30 Years of Clinical Experience — Meet Us Today',
	'cta_history_lead'   => 'Consultations built on decades of accumulated clinical know-how.',

	/* === Reservation Form (예약 폼 · JS·PHP 공통 문자열) === */
	'res_msg_throttle'      => 'Please try again in a moment.',
	'res_msg_required'      => 'Required fields missing: {fields}',
	'res_msg_phone_invalid' => 'Phone number format is invalid.',
	'res_field_service'     => 'Treatment area',
	'res_field_date'        => 'Preferred date',
	'res_field_time'        => 'Preferred time',
	'res_field_name'        => 'Name',
	'res_field_phone'       => 'Phone',
	'res_field_privacy'     => 'Privacy consent',
	'res_alert_svc'         => 'Please select a treatment area.',
	'res_alert_date'        => 'Please select a preferred date.',
	'res_alert_time'        => 'Please select a preferred time.',
	'res_alert_name'        => 'Please enter your name.',
	'res_alert_phone'       => 'Please enter your phone number.',
	'res_alert_phone_fmt'   => 'Phone format is invalid. (e.g., 010-1234-5678)',
	'res_alert_privacy'     => 'Please agree to the privacy policy.',
	'res_btn_submit'        => 'Submit Booking',
	'res_btn_sending'       => 'Sending...',
	'res_alert_fail'        => 'Booking submission failed.',
	'res_alert_network'     => 'Network error — please try again.',
	'res_success_title'     => 'Booking Request Submitted!',
	'res_success_lead'      => 'Our staff will confirm and contact you shortly.',
	'res_success_lbl_no'    => 'Booking No.',
	'res_success_lbl_svc'   => 'Treatment',
	'res_success_lbl_dt'    => 'Date & Time',
	'res_success_lbl_name'  => 'Patient',
	'res_success_hint'      => 'For changes before confirmation, please contact us by phone or KakaoTalk.',
	'res_success_btn_kakao' => '💬 Add KakaoTalk Friend',
	'res_success_btn_home'  => 'Home',

	/* === Breadcrumbs / UI common labels === */
	'breadcrumb_home'         => 'Home',
	'breadcrumb_news'         => 'News',
	'breadcrumb_encyclopedia' => 'Dental Encyclopedia',
	'breadcrumb_facility'     => 'Technology & Facility',
	'breadcrumb_about'        => 'About Hospital',
	'ui_related_posts'        => 'Related Posts',
	'ui_related_terms'        => 'Related Terms',
	'ui_back_to_news'         => '← Back to News',
	'ui_back_to_enc'          => '← Back to Encyclopedia',
	'ui_book_now'             => '📅 Book Consultation',
	'ui_empty_content'        => 'Content coming soon.',
	'ui_see_more'             => 'View details',

	/* === Header & aria === */
	'aria_skip_link'      => 'Skip to main content',
	'aria_menu_open'      => 'Open menu',
	'aria_menu_close'     => 'Close menu',
	'aria_primary_menu'   => 'Primary menu',
	'aria_hours_call'     => 'Hours & phone',
	'aria_hours_location' => 'View directions and hours',

	/* === Language labels (in native tongue · shown in switcher) === */
	'lang_ko' => 'Korean',
	'lang_en' => 'English',
	'lang_zh' => 'Chinese',
	'lang_vi' => 'Vietnamese',
	'lang_ru' => 'Russian',
	'lang_mn' => 'Mongolian',
);
