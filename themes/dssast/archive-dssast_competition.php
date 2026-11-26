<?php
/**
 * Competition archive fallback (/competitions/). The public entry point is the
 * "赛事" page at /events/, but the post type archive keeps working too.
 */

get_header();
?>
<main class="events-page">
	<header class="events-page__header">
		<h1 class="events-page__title"><?php post_type_archive_title(); ?></h1>
		<p class="events-page__intro">院级赛事由赛事部组织，校内校外赛事在此汇总信息与推送入口。</p>
	</header>
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
