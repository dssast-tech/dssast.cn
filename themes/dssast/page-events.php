<?php
/**
 * Events landing page (/events/). Cards are rendered from the competition
 * post type, not from the page body, so administrators never have to paste a
 * shortcode here.
 */

get_header();
?>
<main class="events-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<header class="events-page__header">
			<h1 class="events-page__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="events-page__intro"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php else : ?>
				<p class="events-page__intro">院级赛事由赛事部组织，校内校外赛事在此汇总信息与推送入口。</p>
			<?php endif; ?>
		</header>
		<?php
	endwhile;
	?>
	<?php
	if ( function_exists( 'dssast_competition_cards' ) ) {
		echo dssast_competition_cards( array( 'limit' => -1 ) );
	} else {
		?>
		<p class="events-page__empty">暂无赛事，敬请期待。</p>
		<?php
	}
	?>
</main>
<?php get_footer(); ?>
