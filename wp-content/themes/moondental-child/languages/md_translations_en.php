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

	/* === Services section (홈 · 진료 안내) === */
	'services_eyebrow' => 'OUR SERVICES',
	'services_title'   => 'Comprehensive Dental Care',
	'services_lead'    => 'Implant · Orthodontics · Aesthetic · Preservation · Pediatric · TMJ — all specialty areas in one place.',

	/* === Why section (왜 문치과인가) === */
	'why_eyebrow'  => 'WHY MOON DENTAL',
	'why_title'    => 'Cheonan-Asan Leading Dental Hospital · Why Patients Choose Us',
	'why_lead'     => 'Since 1995 · 30+ years at the same Mannam-ro location · 4 reasons nationwide patients trust us.',
	'why_1_title'  => '30+ Years, Nationwide Trust',
	'why_1_desc'   => 'Same location in Cheonan Mannam-ro since 1995. Consistent care philosophy backed by decades of clinical experience.',
	'why_2_title'  => 'Multi-Specialty Team Approach',
	'why_2_desc'   => 'Specialists in every field — Prosthodontics · Preservation · Prevention · Implant · Aesthetic · Oral Surgery · TMJ · Orthodontics · Pediatric · Periodontal — reviewing one case together.',
	'why_3_title'  => 'Digital-First Diagnosis',
	'why_3_desc'   => 'CBCT · digital-guide surgery · intraoral scanner · in-house prosthetic lab — precision at every step.',
	'why_4_title'  => 'Only What You Actually Need',
	'why_4_desc'   => 'We listen first, recommend only necessary treatment. Written estimate before starting · no additional costs after.',

	/* === Clinic Intro section (진료 시스템 소개) === */
	'clinic_intro_eyebrow' => 'INTEGRATED CARE SYSTEM',
	'clinic_intro_title'   => 'Cheonan-Asan Leading Dental Hospital · Integrated Care Nationwide Patients Trust',
	'clinic_intro_lead'    => 'Diagnosis · treatment · maintenance in one place. Specialists in every field cross-review each patient case.',

	/* === Trust stat sub-labels === */
	'trust_1_sub' => '30+ years of nationwide trust',
	'trust_2_sub' => 'Cross-specialty consultation',
	'trust_3_sub' => 'One-stop building',
	'trust_4_sub' => 'Personal in-depth consultation',

	/* === Notices section (병원 소식) === */
	'notices_eyebrow'         => 'HOSPITAL NEWS',
	'notices_title'           => 'Moon Dental Hospital News · Cheonan-Asan Leading Dental Hospital',
	'notices_all_label'       => 'View all news',
	'notices_notice_subhead'  => 'Announcements',
	'notices_story_subhead'   => 'Dental Stories',
	'notice_tag_notice'       => 'Announcement',
	'notice_tag_story'        => 'Story',

	/* === Testimonials section (환자 후기) === */
	'testimonials_eyebrow'    => 'PATIENT REVIEWS',
	'testimonials_title'      => 'Words from Our Patients',
	'testimonials_lead'       => 'Reviews shared by patients who have visited us in Cheonan-Asan.',
	'testimonials_more_label' => 'View more reviews',
	'testimonials_disclaimer' => '※ Excerpts from patient reviews on Naver Reservation and Google Reviews. Results may vary.',

	/* === Strengths section (강점 카드) === */
	'strengths_eyebrow' => 'OUR STRENGTHS',
	'strengths_title'   => 'Moon Dental Hospital Strengths',
	'strengths_lead'    => '9 key strengths across facility · operations · clinical practice. Click each item for details.',

	/* === Facility section (시설) === */
	'facility_eyebrow' => 'FACILITY',
	'facility_title'   => 'Cheonan-Asan Dental Facility · Moon Tower 9-13F',
	'facility_lead'    => 'A 4-floor integrated care center — treatment rooms · consultation · in-house lab · imaging.',

	/* === FAQ section === */
	'faq_eyebrow' => 'FREQUENTLY ASKED',
	'faq_title'   => 'Common Questions',
	'faq_lead'    => 'What patients ask most about our care · Answers from our clinical team.',

	/* === Micro-FAQ helpers === */
	'micro_faq_all_label' => 'View all FAQ',
	'micro_more_label'    => 'View more',

	/* === Location section (오시는 길) === */
	'flocation_title'       => 'Directions',
	'flocation_address'     => '',
	'flocation_btn_naver'   => 'Naver Maps',
	'flocation_btn_naver_sub' => 'Directions · Public Transit',
	'flocation_btn_kakao'   => 'Kakao Map',
	'flocation_btn_kakao_sub' => 'Directions · Street View',
	'flocation_btn_google'  => 'Google Maps',
	'flocation_btn_google_sub' => 'Directions · Street View',
	'loc_hours_badge'       => '🕐 Hours',
	'loc_hours_title'       => 'Business Hours',
	'loc_park_badge'        => '🅿️ Parking',
	'loc_park_walk'         => '🚌 5 min walk from Cheonan Bus Terminal',
	'loc_park_train'        => '🚆 10 min by bus from Cheonan Station',
	'loc_map_fallback'      => '🗺️ Open map',

	/* === Header CTA cycle (스크롤에 따라 라벨 바뀌는 버튼) === */
	'header_cta_url'   => '/en/reservation/',
	'header_cta_label' => '📅 Book Consultation',
	'header_cta_cycle' => "✨ Book Consultation | #5C8B82 | #FFFFFF | 92,139,130\n🦷 Self-Diagnosis | #E37B5C | #FFFFFF | 227,123,92\n💬 KakaoTalk Now | #FEE500 | #181600 | 254,229,0\n📅 Book Now | #D88062 | #FFFFFF | 216,128,98",

	/* === Footer === */
	'footer_col_policy_title' => 'Info',
	'footer_link_privacy'     => 'Privacy Policy|/privacy-policy/',
	'footer_link_terms'       => 'Terms of Service|/terms/',
	'footer_link_email'       => 'Email Collection Refusal|/email-refusal/',
	'footer_legal_rep'        => 'Chairman Munsu Moon',

	/* === Bot section (self-diagnosis) === */
	'bot_eyebrow'         => 'SELF-CHECK',
	'bot_title'           => '🦷 Check Your Oral Health',
	'bot_lead'            => "Answer a few questions to find the most suitable treatment area for you.\n※ This is for reference only · accurate diagnosis requires in-person consultation.",
	'bot_start_label'     => 'Start Diagnosis →',
	'bot_count_template'  => '{count} Yes/No questions · ~2-3 minutes · covers all treatment areas',
	'bot_intro_title'     => 'Simple Self-Diagnosis',
	'bot_intro_note'      => 'No personal info collected or stored. Answers stay in your browser.',
	'bot_answer_yes'      => '✓ Yes',
	'bot_answer_no'       => '✗ No',
	'bot_back_label'      => '← Previous',
	'bot_result_title'    => 'Diagnosis Results — Recommended Treatment',
	'bot_result_lead'     => 'These treatment areas best match your symptoms.',
	'bot_result_book_label' => '📅 Book Consultation Now',
	'bot_result_restart'  => '↺ Restart Diagnosis',
	'bot_disclaimer'      => '⚠️ This is for reference only. Accurate diagnosis and treatment requires an in-person medical consultation.',
	'bot_intro_step_1_title' => 'Answer Symptoms',
	'bot_intro_step_1_desc'  => 'Simple Yes/No buttons',
	'bot_intro_step_2_title' => 'Weighted Analysis',
	'bot_intro_step_2_desc'  => 'Auto-match symptoms to specialties',
	'bot_intro_step_3_title' => 'Get Recommendations',
	'bot_intro_step_3_desc'  => 'Top 3 by fit score',
	'bot_intro_chips_label'  => 'AREAS COVERED',
	'bot_intro_trust_1'      => 'No answers saved',
	'bot_intro_trust_2'      => 'Results in 30 seconds',
	'bot_intro_trust_3'      => 'Completely free',
	'bot_chief_title'        => 'Where does it bother you most?',
	'bot_chief_lead'         => 'Select the affected area to see only relevant questions. Choose "Not sure" if uncertain.',
	'bot_safety_title'       => 'Please share these when you visit',
	'bot_safety_lead'        => 'The following items need to be confirmed for safe treatment.',
	'bot_safety_continue'    => 'View Results →',

	/* === Doctors page === */
	'doctors_chip'         => 'MOON DENTAL HOSPITAL · OUR DOCTORS',
	'doctors_title_a'      => '30+ Years of Clinical Experience,',
	'doctors_title_b'      => 'Multi-Specialty Team Approach',
	'doctors_lead'         => "Prosthetic · Orthodontic · Preservation · Surgical — specialists in every field, all in one place\nreviewing your case together.",
	'doctors_stat_3_value' => '30+',
	'doctors_stat_3_label' => 'Years since 1995',
	'doctors_list_eyebrow' => 'Our Doctors',
	'doctors_list_title'   => 'Meet Our Medical Team',
	'doctors_list_lead'    => 'Receive care from specialists in every dental field.',
	'doctors_view_label'   => 'View Profile',
	'doctors_grid_hint'    => 'Click any doctor to see full bio and appointments.',
	'doctors_spec_eyebrow' => 'SPECIALTY AREAS',
	'doctors_spec_title'   => 'Areas of Practice',
	'doctors_spec_lead'    => 'Areas we specialize in across our multi-disciplinary team.',

	/* === Doctor single page === */
	'doc_single_back_label'       => '← Back to All Doctors',
	'doc_single_intro_eyebrow'    => 'INTRODUCTION',
	'doc_single_edu_eyebrow'      => 'EDUCATION & CREDENTIALS',
	'doc_single_qa_eyebrow'       => 'DOCTOR Q&A',
	'doc_single_qa_title'         => 'Frequently Asked',
	'doc_single_qa_lead'          => 'Common questions from patients answered by the doctor.',
	'doc_single_interests_title'  => 'Areas of Interest',
	'doc_single_others_title'     => 'Other Doctors',

	/* === Service page common === */
	'svc_faq_title'        => 'Frequently Asked Questions',
	'svc_other_title'      => 'Explore Other Treatment Areas',

	/* === Preservation (자연치아 살리기) === */
	'preservation_hero_eyebrow' => 'PRESERVATION · Save Your Natural Teeth',
	'preservation_hero_lead'    => 'Considering extraction? Take one more look — our specialists in preservation and periodontal care assess whether your natural tooth can be saved.',

	/* === Prevention (예방클리닉) === */
	'prevention_hero_eyebrow' => 'PREVENTION · Cheonan-Asan Prevention Clinic',
	'prevention_hero_lead'    => "Blocking cavities and gum disease before they start — the most economical and conservative treatment.\nMoon Dental Hospital Prevention Clinic: Dental SPA · Air Flow · Fluoride · Sealant.",

	/* === Smile Design (스마일디자인) === */
	'smile_hero_eyebrow' => 'SMILE DESIGN · Aesthetic Center',
	'smile_hero_lead'    => 'Laminate · Whitening · Aesthetic prosthetics — minimally invasive approach to your ideal smile.',

	/* === Pricing (비용 안내) === */
	'price_hero_chip'    => 'BILLING TRANSPARENCY · Cost Guide',
	'price_hero_title_a' => 'The estimate you first heard,',
	'price_hero_title_b' => 'stays the same until treatment ends',
	'price_hero_title_c' => '',
	'price_hero_lead'    => 'Moon Dental Hospital has promised honest care fees for 30+ years. We do not recommend unnecessary treatment · no additional costs after starting.',
	'price_tables_eyebrow' => 'ESTIMATED COST',
	'price_tables_title'   => 'Cost by Treatment',
	'price_tables_lead'    => 'The table below is our standard reference. Exact cost is confirmed by written estimate after diagnosis.',
	'price_tables_hint'    => 'May be adjusted based on patient oral condition · material choice · treatment difficulty. Final cost is confirmed by written estimate after examination.',
	'price_promise_year'   => 'SINCE 1995',
	'price_promise_title'  => 'Our 3 Promises',
	'price_promise_1_title' => 'Estimate Stays the Same',
	'price_promise_1_desc'  => 'Zero additional costs after starting',
	'price_promise_2_title' => 'All Non-Covered Items Disclosed',
	'price_promise_2_desc'  => 'Nothing hidden, everything upfront',
	'price_promise_3_title' => 'Preservation First',
	'price_promise_3_desc'  => 'Saving teeth before extraction',
	'price_steps_eyebrow'   => 'PROCESS',
	'price_steps_title'     => 'How Cost is Confirmed · 4 Steps',
	'price_steps_lead'      => 'Consultation → Diagnosis → Estimate → Treatment (after consent). You have full opportunity to review at each step.',
	'price_step_1_title'    => 'Comfortable Consultation',
	'price_step_1_desc'     => 'We fully listen to your symptoms, budget, schedule, and concerns. Phone, KakaoTalk, or in-person all available.',
	'price_step_2_title'    => 'Precise Diagnosis',
	'price_step_2_desc'     => 'X-ray, CT, and clinical examination for accurate understanding of your condition.',
	'price_step_3_title'    => 'Detailed Written Estimate',
	'price_step_3_desc'     => 'Cost, duration, and process of each treatment option — provided in writing.',
	'price_step_4_title'    => 'Treatment After Consent',
	'price_step_4_desc'     => 'Full review then treat only what you have agreed to. Zero additional costs.',

	/* === Location page === */
	'locpage_hero_title'    => 'Directions',
	'locpage_region_note'   => 'ⓘ Travel times are approximate by car. Actual times may vary by traffic.',

	/* === News page (v3.44.9 · 병원소식) === */
	'news_hero_title'         => 'Hospital News',
	'news_hero_lead'          => 'Announcements and dental information from Moon Dental Hospital in Cheonan Mannam-ro.',
	'news_notice_eyebrow'     => '📢 NOTICE',
	'news_notice_title'       => 'Moon Dental Hospital Announcements',
	'news_notice_lead'        => 'Schedule changes, closures, events, and operations — announced here first.',
	'news_notice_empty'       => 'No announcements yet.',
	'news_notice_empty_sub'   => 'We will share new updates soon.',
	'news_stories_eyebrow'    => '🦷 DENTAL STORIES',
	'news_stories_title'      => 'Moon Dental Hospital Stories',
	'news_stories_lead'       => "Implant · Orthodontics · Preservation · Laminate · Prevention —\nOral health information to help patients.",
	'news_stories_empty'      => 'No dental stories yet.',
	'news_stories_empty_sub'  => 'We will share helpful information soon.',

	/* === CTA generic (v3.44.8 · default context 격리) === */
	'cta_generic_eyebrow' => 'CONSULTATION',
	'cta_generic_title'   => 'Have a question?',
	'cta_generic_lead'    => "We listen first, then only recommend truly necessary treatment.\nBook a consultation and we will contact you during business hours.",

	/* === CTA · slug-based new contexts (v3.44.7) === */
	'cta_home_eyebrow'          => 'WELCOME · FIRST VISIT',
	'cta_home_title'            => "Cheonan-Asan · 30 Years of Clinical Care\nBook Your First Consultation Today",
	'cta_home_lead'              => "We only recommend what's truly needed for our patients.\nBook now and we will arrange a detailed consultation at your convenience.",
	'cta_about_eyebrow'         => 'IN-PERSON CONSULTATION',
	'cta_about_title'           => "Curious about the actual hospital atmosphere?\nSchedule a visit consultation",
	'cta_about_lead'            => 'Moon Dental Hospital — 30+ years at one location · meet our doctors, see our facility, in person.',
	'cta_services_parent_eyebrow' => 'TREATMENT CONSULTATION',
	'cta_services_parent_title' => 'Not sure which treatment you need?',
	'cta_services_parent_lead'  => 'Share your symptoms and we will guide you to the right specialty and doctor. Only necessary treatment recommended after accurate diagnosis.',
	'cta_reservation_eyebrow'   => 'OTHER CHANNELS',
	'cta_reservation_title'     => 'Prefer another way? Choose whichever contact suits you',
	'cta_reservation_lead'      => 'Phone · Naver Booking · KakaoTalk — reach us however works best for you.',
	'cta_legal_eyebrow'         => 'INQUIRY',
	'cta_legal_title'           => 'Questions about privacy or terms?',
	'cta_legal_lead'            => 'Reach the hospital by phone anytime and our team will respond promptly.',

	/* === Location · day labels (진료시간 표) === */
	'loc_day_weekday'    => 'Mon · Tue · Wed · Fri',
	'loc_day_thu'        => 'Thursday',
	'loc_day_sat'        => 'Saturday',
	'loc_day_sun'        => 'Sunday · Holidays',
	'loc_day_closed'     => 'Closed',
	'loc_hours_note'     => 'No lunch break on weekdays · Evening hours available',

	/* === Location · parking === */
	'loc_park_title'     => 'Free mechanical parking · basement',
	'loc_park_1_title'   => 'Onsite basement mechanical parking',
	'loc_park_1_desc'    => 'Free during treatment hours',
	'loc_park_2_title'   => 'SUV / Large cars — Sinbu 5th Public Parking',
	'loc_park_2_desc'    => 'Nearby Sinbu 5th Public Parking (Dongnam-gu Meokgeori 1-gil 10) · park then register free at the front desk',

	/* === Address (moondental_get_info) === */
	'flocation_address'  => 'Moon Tower 9·10·11·13F, 52 Mannam-ro, Dongnam-gu, Cheonan-si, Chungcheongnam-do (Sinbu-dong)',
	'info_address'       => 'Moon Tower 9·10·11·13F, 52 Mannam-ro, Dongnam-gu, Cheonan-si, Chungcheongnam-do (Sinbu-dong)',

	/* === Post/blog dates · Y년 n월 j일 → Month D, Y === */
	'date_format'        => 'F j, Y',

	/* === Language switcher label (FAB) === */
	'lang_switcher_label' => 'Language',

	/* === Hours · Naver Place link (v3.44.11) === */
	'loc_hours_naver_note'    => '🔔 For holiday hours and schedule changes, please verify on Naver Place',
	'loc_hours_aria'          => 'Check latest hours on Naver Place',
	'footer_hours_naver_note' => '🔔 For holiday hours and schedule changes, verify on Naver Place',
	'footer_hours_aria'       => 'View latest hours on Naver Place',
	'loc_day_sun'             => 'Sunday',

	/* === Footer parking (v3.44.13) === */
	'footer_park_title'  => 'Parking',
	'footer_park_1_title'=> 'Onsite basement mechanical parking',
	'footer_park_1_desc' => 'Park then register free at the front desk',
	'footer_park_2_title'=> 'SUV / Large cars — Sinbu 5th Public Parking (Meokgeori 1-gil 10)',
	'footer_park_2_desc' => 'Park at nearby Sinbu 5th Public Parking then register free at the front desk',
	'footer_park_walk'   => '🚌 5 min walk from Cheonan Bus Terminal',
	'footer_park_train'  => '🚆 10 min by bus from Cheonan Station',

	/* === Header primary menu (v3.44.22) === */
	'menu_impl'         => 'Implant Center',
	'menu_ortho'        => 'Orthodontics',
	'menu_smile'        => 'Smile Design',
	'menu_preserve'     => 'Save Natural Teeth',
	'menu_dept'         => 'Specialties',
	'menu_doctors'      => 'Our Doctors',
	'menu_pricing'      => 'Pricing',
	'menu_about'        => 'About',
	'menu_cavity'       => 'Cavity Treatment',
	'menu_endo'         => 'Root Canal',
	'menu_perio'        => 'Gum Treatment',
	'menu_jaw'          => 'TMJ Clinic',
	'menu_bruxism'      => 'Bruxism / Clenching',
	'menu_wisdom'       => 'Wisdom Tooth',
	'menu_pediatric'    => 'Pediatric Dentistry',
	'menu_prevention'   => 'Prevention Clinic',
	'menu_suresmile'    => 'SureSmile Clear Aligner',
	'menu_braces'       => 'Traditional Braces',
	'menu_directions'   => 'Directions & Hours',
	'menu_history'      => '30 Years of History',
	'menu_facility'     => 'Facility & Technology',
	'menu_news'         => 'News',
	'menu_encyclopedia' => 'Dental Encyclopedia',
	'menu_recruit'      => 'Careers',

	/* === Header CTA & Book Consultation === */
	'header_book_consultation' => 'Book Consultation',

	/* === Reservation page (v3.44.22) === */
	'res_channels_eyebrow' => 'BOOK NOW',
	'res_channels_title'   => 'Choose your preferred booking method',
	'res_channels_lead'    => '<strong>Naver Booking</strong> is 24/7 automated. <strong>Phone · KakaoTalk</strong> respond fast during business hours.',
	'res_hint'             => 'Hours: Mon·Tue·Wed·Fri 9:00–20:30 · Thu 9:00–18:30 · Sat 9:00–14:00 · Sun/Holidays closed',
	'res_faq_title'        => 'Booking-related FAQ',
	'res_faq_eyebrow'      => 'FAQ',
	'res_faq_lead'         => 'Answers to the most common booking questions.',

	/* === Reservation FAQ items (v3.44.23 · 전체 문자열 번역) === */
	'res_faq_items' => "Can I book same-day appointments? | Yes, same-day booking is available. However, wait times may occur based on schedule — we recommend calling first to confirm availability.\nHow do I change or cancel my booking? | Naver Booking can be changed or cancelled directly on the booking page. Otherwise, please contact us by phone or KakaoTalk. Changes are accepted up to the day before your appointment.\nWhat do I need to bring for my first visit? | Please bring your ID (or health insurance card). If you have any medications, please share that information — it helps with treatment. Recent X-ray files from other clinics (USB / email) can shorten diagnosis time.\nCan I still receive treatment with systemic conditions (hypertension, diabetes, heart disease)? | Yes, safely. Moon Dental Hospital has blood pressure, blood sugar, ECG, and oxygen saturation monitors on hand. We check any medications (blood thinners, osteoporosis meds, etc.) in advance for safe treatment.\nCan I know the cost in advance? | Non-covered treatments (implant, orthodontics, aesthetic) are estimated based on your oral condition (CT / X-ray). Per-option cost and duration are provided at the first consultation, with full time to review before starting.\nIs it OK to come only after checking the self-diagnosis result? | The self-diagnosis is helpful as a reference. However, an accurate diagnosis and treatment plan requires an in-person examination (visual · X-ray · oral check). Showing your self-diagnosis result speeds up the consultation.",

	/* === Footer hours values (v3.44.22 · md_content routed) === */
	'info_hours_wd'    => 'Weekdays 09:00 – 20:30',
	'info_hours_thu'   => 'Thursday 09:00 – 18:30',
	'info_hours_sat'   => 'Saturday 09:00 – 14:00',
	'info_hours_lunch' => '',
	'info_hours_off'   => 'Sunday · Closed',
	'footer_hour_wd_label'  => 'Weekdays',
	'footer_hour_thu_label' => 'Thursday',
	'footer_hour_sat_label' => 'Saturday',
	'footer_col_hours_title'=> 'Hours',

	/* === Footer legal prefixes === */
	'footer_prefix_rep'  => 'Rep: ',
	'footer_prefix_open' => 'Est: ',
	'footer_prefix_med'  => 'License No: ',
	'footer_prefix_ad'   => 'Ad Review: ',
	'footer_name_token'  => 'Hanah Medical Foundation Moon Dental Hospital',
	'footer_copyright_bar' => 'Copyright {year} {name}  All Rights Reserved.',
	'footer_legal_rep'   => 'Chairman Munsu Moon',
	'footer_legal_open_date' => '1995.04',
	'footer_legal_med_no'    => '34400117',

	/* === Bot section chips (AREAS COVERED) === */
	'bot_chip_preserve'  => 'Preservation',
	'bot_chip_implant'   => 'Implant Center',
	'bot_chip_ortho'     => 'Orthodontics',
	'bot_chip_tmj'       => 'TMJ Clinic',
	'bot_chip_wisdom'    => 'Wisdom Tooth',
	'bot_chip_aesthetic' => 'Aesthetic',
	'bot_chip_checkup'   => 'Checkup · Scaling',
	'bot_chip_prevention'=> 'Prevention · Dental SPA',

	/* === CTA hint (진료시간 배너 하단) — 이미 있음 재확인 === */
);
