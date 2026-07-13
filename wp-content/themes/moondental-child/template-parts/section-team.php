<?php
/**
 * Section: 의료진 팀 그리드 (9명) — 홈 페이지용 간단 카드
 *
 * 약력 상세는 의료진 페이지(/doctors/)에서. 홈은 사진·이름·직책·진료철학만.
 *
 * @package moondental-child
 */
$groups = moondental_get_team();
?>

<section class="md-section md-section--surface" id="team" aria-label="의료진">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Our Team</span>
			<h2 class="md-section-head__title">분야별 전문 의료진이 함께합니다</h2>
			<p class="md-section-head__lead">
				보철·보존·임플란트·교정·외과·소아·예방 — 각 분야 전문 의료진이<br>
				환자 한 분의 평생 치아 건강을 책임집니다.
			</p>
		</header>

		<?php
		$doctors_page_url = home_url( '/doctors/' );
		foreach ( $groups as $group ) : ?>
			<div class="md-team-group">
				<h3 class="md-team-group__title"><?php echo esc_html( $group['group'] ); ?></h3>
				<div class="md-team-grid">
					<?php foreach ( $group['members'] as $doc ) :
						$photo_url = moondental_doctor_photo_url( $doc['photo'] ?? '' );
						$anchor    = 'doctor-' . sanitize_title( $doc['name'] );
						$profile   = $doctors_page_url . '#' . $anchor;
					?>
						<a class="md-team-card" href="<?php echo esc_url( $profile ); ?>" aria-label="<?php echo esc_attr( $doc['name'] . ' 원장 프로필 보기' ); ?>">
							<span class="md-team-card__avatar<?php echo $photo_url ? ' md-team-card__avatar--photo' : ''; ?>" aria-hidden="true">
								<?php if ( $photo_url ) : ?>
									<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( $doc['name'] ); ?>" loading="lazy">
								<?php else : ?>
									<span><?php echo esc_html( mb_substr( $doc['name'], -2 ) ); ?></span>
								<?php endif; ?>
							</span>
							<span class="md-team-card__body">
								<span class="md-team-card__role"><?php echo esc_html( $doc['role'] ); ?></span>
								<span class="md-team-card__name"><?php echo esc_html( $doc['name'] ); ?></span>
								<?php if ( ! empty( $doc['philosophy'] ) ) : ?>
									<span class="md-team-card__bio"><?php echo esc_html( wp_trim_words( $doc['philosophy'], 18, '…' ) ); ?></span>
								<?php endif; ?>
								<span class="md-team-card__more" aria-hidden="true">프로필 보기 →</span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="md-team__cta">
			<a class="md-btn md-btn-ghost" href="<?php echo esc_url( home_url( '/doctors/' ) ); ?>">
				의료진 약력 자세히 보기 →
			</a>
		</div>
	</div>
</section>
