<?php
/**
 * Section: Doctor Intro
 *
 * 대표원장 약력 — Customizer에 등록된 정보 또는 기본 카피로 렌더.
 *
 * @package moondental-child
 */
$info       = moondental_get_info();
$role       = get_theme_mod( 'moondental_doctor_role',   '대표 병원장 · 한아의료재단 이사장' );
$rep        = $info['rep'] ?: '문은수';
$lead       = get_theme_mod( 'moondental_doctor_lead',
	'1995년부터 천안에서, 한 분의 환자를 가족처럼 오래 보아왔습니다. 진료실 밖에서도 지역사회와 함께 가는 치과를 꿈꿉니다.'
);

// 약력 — Customizer 'moondental_doctor_bio' 줄바꿈 입력 → 줄 단위 <li>로 렌더
$bio_text   = get_theme_mod( 'moondental_doctor_bio',
	"한아임플란트 보철연구소장\n단국대학교 치과대학 총동창회 학술이사\n대한 구강악안면 임플란트 학회 이사\n충남 치과의사회 학술이사\n단국치대 겸임교수\n이화여대 의과대학 외래교수"
);
$bio_lines  = array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", $bio_text ) ) );

// 사진: Customizer에 attachment 등록 시 그것 우선, 없으면 doctor-04.png 자동 사용
$doctor_img      = get_theme_mod( 'moondental_doctor_image', 0 );
$doctor_fallback = moondental_doctor_photo_url( 'doctor-04.png' );
?>

<section class="md-section" id="doctor" aria-label="의료진 소개">
	<div class="md-container">
		<div class="md-doctor">

			<div class="md-doctor__photo">
				<?php if ( $doctor_img ) : ?>
					<?php echo wp_get_attachment_image( $doctor_img, 'moondental-doctor', false, array( 'alt' => esc_attr( $rep . ' ' . $role ) ) ); ?>
				<?php elseif ( $doctor_fallback ) : ?>
					<img src="<?php echo esc_url( $doctor_fallback ); ?>" alt="<?php echo esc_attr( $rep . ' ' . $role ); ?>" loading="lazy">
				<?php else : ?>
					<div class="md-hero__media-placeholder" style="position:absolute; inset:0;">
						<span>원장님 사진 (Customizer에서 등록)</span>
					</div>
				<?php endif; ?>
			</div>

			<div class="md-doctor__text">
				<div class="md-doctor__role"><?php echo esc_html( $role ); ?></div>
				<h2 class="md-doctor__name">
					<?php echo esc_html( $rep ); ?>
				</h2>
				<p class="md-doctor__bio"><?php echo esc_html( $lead ); ?></p>

				<?php if ( ! empty( $bio_lines ) ) : ?>
					<div class="md-doctor__bio">
						<ul>
							<?php foreach ( $bio_lines as $line ) : ?>
								<li><?php echo esc_html( $line ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<div class="md-btn-group" style="margin-top: 32px;">
					<a class="md-btn md-btn-ghost md-btn--sm" href="<?php echo esc_url( home_url( '/doctors/' ) ); ?>">
						의료진 자세히 보기 →
					</a>
				</div>
			</div>

		</div>
	</div>
</section>
