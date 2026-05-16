<?php
/**
 * Section: Doctor Intro
 *
 * 대표원장 약력 — Customizer에 등록된 정보 또는 기본 카피로 렌더.
 *
 * @package moondental-child
 */
$info       = moondental_get_info();
$role       = get_theme_mod( 'moondental_doctor_role',   '대표원장' );
$rep        = $info['rep'] ?: '문은수';
$lead       = get_theme_mod( 'moondental_doctor_lead',   '환자의 이야기를 충분히 듣고, 가장 보존적인 치료부터 제안합니다.' );

// 약력 — Customizer 'moondental_doctor_bio' 줄바꿈 입력 → 줄 단위 <li>로 렌더
$bio_text   = get_theme_mod( 'moondental_doctor_bio', "서울대학교 치과대학 졸업\n대한치과의사협회 정회원\n대한구강악안면임플란트학회 정회원\n前 ○○치과 원장" );
$bio_lines  = array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", $bio_text ) ) );

$doctor_img = get_theme_mod( 'moondental_doctor_image', 0 );
?>

<section class="md-section" id="doctor" aria-label="의료진 소개">
	<div class="md-container">
		<div class="md-doctor">

			<div class="md-doctor__photo">
				<?php if ( $doctor_img ) : ?>
					<?php echo wp_get_attachment_image( $doctor_img, 'moondental-doctor', false, array( 'alt' => esc_attr( $rep . ' ' . $role ) ) ); ?>
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
					<small>D.D.S.</small>
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
