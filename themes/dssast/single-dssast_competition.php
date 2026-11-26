<?php
/**
 * Competition detail page, styled after the reference site: a banner with the
 * state, title, key dates and a milestone strip, followed by the introduction
 * and the related links.
 */

get_header();

while ( have_posts() ) :
	the_post();

	$post_id = get_the_ID();
	$state   = dssast_competition_state( $post_id );
	$links   = dssast_competition_links( $post_id );

	$deadline = (string) get_post_meta( $post_id, 'dssast_deadline', true );
	$start    = (string) get_post_meta( $post_id, 'dssast_date', true );
	$end      = (string) get_post_meta( $post_id, 'dssast_end_date', true );
	if ( '' === $end || $end < $start ) {
		$end = $start;
	}

	$terms = get_the_terms( $post_id, 'dssast_competition_category' );
	$terms = is_array( $terms ) ? $terms : array();

	// Milestones: only the dates that exist, earliest first.
	$milestones = array();
	if ( '' !== $deadline ) {
		$milestones[] = array( 'label' => '报名截止', 'date' => $deadline );
	}
	if ( '' !== $start ) {
		$milestones[] = array( 'label' => '开赛', 'date' => $start );
	}
	if ( '' !== $end && $end !== $start ) {
		$milestones[] = array( 'label' => '结束', 'date' => $end );
	}
	usort( $milestones, function ( $a, $b ) {
		return strcmp( $a['date'], $b['date'] );
	} );

	$today = current_time( 'Y-m-d' );
	$current_marked = false;
	foreach ( $milestones as $index => $milestone ) {
		if ( $milestone['date'] < $today ) {
			$milestones[ $index ]['state'] = 'done';
		} elseif ( ! $current_marked ) {
			$milestones[ $index ]['state'] = 'current';
			$current_marked = true;
		} else {
			$milestones[ $index ]['state'] = 'upcoming';
		}
	}
	?>
	<main class="event-detail event-detail--<?php echo esc_attr( $state['code'] ); ?>">
		<nav class="event-detail__back">
			<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">← 返回赛事列表</a>
		</nav>

		<header class="event-hero">
			<?php
			if ( has_post_thumbnail() ) {
				echo get_the_post_thumbnail( get_the_ID(), 'full', array(
					'class' => 'event-hero__bg',
					'alt'   => '',
				) );
			}
			?>
			<div class="event-hero__inner">
				<span class="event-card__badge event-card__badge--<?php echo esc_attr( $state['code'] ); ?>"><?php echo esc_html( $state['label'] ); ?></span>
				<h1 class="event-hero__title"><?php the_title(); ?></h1>

				<dl class="event-hero__meta">
					<?php if ( '' !== $deadline ) : ?>
						<div>
							<dt>报名截止</dt>
							<dd><time datetime="<?php echo esc_attr( $deadline ); ?>"><?php echo esc_html( $deadline ); ?></time></dd>
						</div>
					<?php endif; ?>
					<?php if ( '' !== $start ) : ?>
						<div>
							<dt>赛事时间</dt>
							<dd><time datetime="<?php echo esc_attr( $start ); ?>"><?php echo esc_html( $start === $end ? $start : $start . ' – ' . $end ); ?></time></dd>
						</div>
					<?php endif; ?>
					<?php if ( $terms ) : ?>
						<div>
							<dt>赛事分类</dt>
							<dd>
								<?php
								$names = array();
								foreach ( $terms as $term ) {
									$names[] = $term->name;
								}
								echo esc_html( implode( ' / ', $names ) );
								?>
							</dd>
						</div>
					<?php endif; ?>
				</dl>

				<?php if ( $milestones ) : ?>
					<ol class="event-steps">
						<?php foreach ( $milestones as $index => $milestone ) : ?>
							<li class="event-step event-step--<?php echo esc_attr( $milestone['state'] ); ?>"<?php echo 'current' === $milestone['state'] ? ' aria-current="step"' : ''; ?>>
								<span class="event-step__dot"><?php echo (int) $index + 1; ?></span>
								<span class="event-step__label"><?php echo esc_html( $milestone['label'] ); ?></span>
								<span class="event-step__date"><?php echo esc_html( substr( $milestone['date'], 5 ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</div>
		</header>

		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<section class="event-panel">
				<div class="event-panel__body">
					<?php the_content(); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $links ) : ?>
			<section class="event-panel">
				<h2 class="event-panel__title">相关链接</h2>
				<ul class="event-detail__links">
					<?php foreach ( $links as $link ) : ?>
						<li>
							<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer">
								<span><?php echo esc_html( $link['title'] ); ?></span>
								<span class="event-detail__link-icon" aria-hidden="true">↗</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
