<?php
/**
 * Section: Info Grid — 진료시간 / 전화 / 위치
 *
 * @package moondental-child
 */
$info = moondental_get_info();
?>

<section class="md-section md-section--surface" id="info" aria-label="진료시간 및 오시는 길">
	<div class="md-container">
		<header class="md-section-head">
			<span class="md-section-head__eyebrow">Information</span>
			<h2 class="md-section-head__title">진료시간 & 오시는 길</h2>
		</header>

		<div class="md-info-grid">

			<div class="md-info-block">
				<span class="md-info-block__label">진료시간</span>
				<ul>
					<?php
					$rows = array(
						array( '평일',    $info['hours_wd']    ?? '' ),
						array( '목요일',  $info['hours_thu']   ?? '' ),
						array( '토요일',  $info['hours_sat']   ?? '' ),
						array( '점심시간', $info['hours_lunch'] ?? '' ),
					);
					foreach ( $rows as $row ) :
						list( $label, $value ) = $row;
						if ( ! $value ) continue;
						$key = preg_quote( $label === '점심시간' ? '점심' : $label, '/' );
						$value_clean = preg_replace( '/^' . $key . '\s*/u', '', $value );
					?>
						<li><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $value_clean ); ?></span></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $info['hours_off'] ) : ?>
					<p class="md-info-block__sub" style="margin-top:12px;"><?php echo esc_html( $info['hours_off'] ); ?></p>
				<?php endif; ?>
			</div>

			<div class="md-info-block">
				<span class="md-info-block__label">전화 문의</span>
				<p class="md-info-block__value">
					<a href="tel:<?php echo esc_attr( $info['phone_link'] ?: preg_replace('/[^0-9]/', '', $info['phone']) ); ?>">
						<?php echo esc_html( $info['phone'] ); ?>
					</a>
				</p>
				<p class="md-info-block__sub">
					전화 예약 / 진료 문의<br>
					진료시간 내에만 응답 가능합니다.
				</p>
				<?php if ( $info['kakao_url'] ) : ?>
					<div style="margin-top:16px;">
						<a class="md-btn md-btn-primary md-btn--sm" href="<?php echo esc_url( $info['kakao_url'] ); ?>" target="_blank" rel="noopener">
							카카오톡 상담
						</a>
					</div>
				<?php endif; ?>
			</div>

			<div class="md-info-block">
				<span class="md-info-block__label">오시는 길</span>
				<p class="md-info-block__value" style="font-size:1.0625rem; line-height:1.6;">
					<?php echo esc_html( $info['address_road'] ?: $info['address'] ); ?>
				</p>
				<?php if ( $info['naver_place'] ) : ?>
					<div style="margin-top:16px;">
						<a class="md-btn md-btn-secondary md-btn--sm" href="<?php echo esc_url( $info['naver_place'] ); ?>" target="_blank" rel="noopener">
							네이버 지도로 길찾기
						</a>
					</div>
				<?php endif; ?>
				<p class="md-info-block__sub" style="margin-top:12px;">
					주차 안내 / 대중교통 정보 ▸ <a href="<?php echo esc_url( home_url( '/location/' ) ); ?>">자세히 보기</a>
				</p>
			</div>

		</div>
	</div>
</section>
