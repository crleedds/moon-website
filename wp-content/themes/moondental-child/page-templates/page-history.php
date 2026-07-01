<?php
/**
 * Template Name: 30여년의 발자취
 * Template Post Type: page
 *
 * 병원소개 → 30여년의 발자취 페이지.
 * 상단: 사명·핵심 가치 / 하단: moondental_get_history() 연표.
 *
 * @package moondental-child
 */

get_header();
$history       = moondental_get_history();
$display_title = '30여년의 발자취';

$values = array();
for ( $i = 1; $i <= 4; $i++ ) {
	$_defaults = array(
		1 => array( 'icon' => '🤝', 'title' => '정직', 'desc' => '환자분께 필요한 진료만 권합니다. 시작 전 모든 비용을 안내합니다.' ),
		2 => array( 'icon' => '🛡️', 'title' => '신뢰', 'desc' => '30여년 동안 한자리에서 — 환자 한 분의 평생 치아를 길게 봅니다.' ),
		3 => array( 'icon' => '🌱', 'title' => '책임', 'desc' => '시술 시점뿐 아니라 정기 검진·사후 관리까지 평생 함께합니다.' ),
		4 => array( 'icon' => '❤️', 'title' => '헌신', 'desc' => '지역사회와 함께 — 의료재단으로서 장학·기부를 이어가고 있습니다.' ),
	);
	$d = $_defaults[ $i ];
	$values[] = array(
		'icon'  => function_exists( 'md_content' ) ? md_content( "mission_v_{$i}_icon",  $d['icon'] )  : $d['icon'],
		'title' => function_exists( 'md_content' ) ? md_content( "mission_v_{$i}_title", $d['title'] ) : $d['title'],
		'desc'  => function_exists( 'md_content' ) ? md_content( "mission_v_{$i}_desc",  $d['desc'] )  : $d['desc'],
	);
}
?>

<section class="md-page-hero">
	<div class="md-container">
		<nav class="md-page-hero__crumbs" aria-label="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">홈</a> ▸
			<a href="<?php echo esc_url( home_url( '/병원소개/' ) ); ?>">병원안내</a> ▸
			<span><?php echo esc_html( $display_title ); ?></span>
		</nav>
		<h1 class="md-page-hero__title"><?php echo esc_html( $display_title ); ?></h1>
		<p class="md-page-hero__lead">
			1995년부터 천안·아산에서 — 환자 한 분의 평생 치아 건강을 책임지는 마음으로 진료해온 30여년의 기록.
		</p>
	</div>
</section>

<!-- ============ 사명 ============ -->
<section class="md-section">
	<div class="md-container">
		<div class="md-mission">
			<header class="md-mission__head">
				<span class="md-mission__chip"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'mission_chip', 'OUR MISSION · 사명' ) : 'OUR MISSION · 사명' ); ?></span>
				<h2 class="md-mission__title">
					<?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'mission_title_a', '환자를 가족처럼 생각하는 마음,' ) : '환자를 가족처럼 생각하는 마음,' ); ?><br>
					<em><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'mission_title_b', '그것이 문치과의 진료 철학입니다.' ) : '그것이 문치과의 진료 철학입니다.' ); ?></em>
				</h2>
				<p class="md-mission__lead">
					<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'mission_lead', '1995년부터 한자리에서, 한 분의 환자를 가족처럼 오래 보아왔습니다. 진료실 밖에서도 지역사회와 함께 가는 치과를 꿈꿉니다.' ) : '1995년부터 한자리에서, 한 분의 환자를 가족처럼 오래 보아왔습니다. 진료실 밖에서도 지역사회와 함께 가는 치과를 꿈꿉니다.' ) ); ?>
				</p>
			</header>

			<div class="md-mission__values">
				<?php foreach ( $values as $v ) : ?>
					<article class="md-mission__value">
						<div class="md-mission__value-icon" aria-hidden="true"><?php echo $v['icon']; ?></div>
						<h3><?php echo esc_html( $v['title'] ); ?></h3>
						<p><?php echo esc_html( $v['desc'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<!-- ============ 역사 (연표) ============ -->
<section class="md-section md-section--surface" id="history">
	<div class="md-container md-container--narrow">

		<header class="md-section-head">
			<span class="md-section-head__eyebrow"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'history_eyebrow', 'Our History' ) : 'Our History' ); ?></span>
			<h2 class="md-section-head__title"><?php echo esc_html( function_exists( 'md_content' ) ? md_content( 'history_title', '30여년의 발자취' ) : '30여년의 발자취' ); ?></h2>
			<p class="md-section-head__lead">
				<?php echo nl2br( esc_html( function_exists( 'md_content' ) ? md_content( 'history_lead', '1995년 개원부터 현재까지 — 환자분과 함께 걸어온 길.' ) : '1995년 개원부터 현재까지 — 환자분과 함께 걸어온 길.' ) ); ?>
			</p>
		</header>

		<article class="md-page-content" style="margin-bottom: clamp(40px, 5vw, 64px);">
			<?php
			while ( have_posts() ) :
				the_post();
				$body = trim( get_the_content() );
				if ( $body ) {
					the_content();
				}
				// 본문이 비어있어도 기본 도입글은 위 미션 섹션에서 처리됨
			endwhile;
			?>
		</article>

		<?php if ( ! empty( $history ) ) :
			$by_year = array();
			foreach ( $history as $row ) {
				$y = isset( $row['year'] ) ? $row['year'] : '';
				if ( ! isset( $by_year[ $y ] ) ) $by_year[ $y ] = array();
				$by_year[ $y ][] = $row;
			}
		?>
			<div class="md-timeline" aria-label="문치과병원 연혁">
				<?php foreach ( $by_year as $year => $rows ) : ?>
					<section class="md-timeline-group">
						<h3 class="md-timeline-group__year"><?php echo esc_html( $year ); ?></h3>
						<ol class="md-timeline-group__items">
							<?php foreach ( $rows as $row ) :
								$month = isset( $row['month'] ) ? $row['month'] : '';
								$title = isset( $row['title'] ) ? $row['title'] : '';
								$desc  = isset( $row['desc'] )  ? $row['desc']  : '';
								$photo = isset( $row['photo'] ) ? moondental_history_photo_url( $row['photo'] ) : false;
							?>
								<li class="md-timeline__item<?php echo $photo ? ' has-photo' : ''; ?>">
									<div class="md-timeline__month"><?php echo $month ? esc_html( ltrim( $month, '0' ) ) . '월' : ''; ?></div>
									<div class="md-timeline__dot" aria-hidden="true"></div>
									<div class="md-timeline__body">
										<?php if ( $photo ) : ?>
											<div class="md-timeline__photo">
												<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
											</div>
										<?php endif; ?>
										<?php if ( $title ) : ?><h4 class="md-timeline__title"><?php echo esc_html( $title ); ?></h4><?php endif; ?>
										<?php if ( $desc ) : ?><p class="md-timeline__desc"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php get_template_part( 'template-parts/section', 'cta' ); ?>

<?php
get_footer();
