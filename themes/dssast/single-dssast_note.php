<?php get_header(); ?>
<main>
	<?php while ( have_posts() ) : the_post(); ?>
		<article class="note-reader">
			<?php
			$directory_page = get_page_by_path( 'notes' );
			$directory_url  = $directory_page && 'publish' === $directory_page->post_status
				? get_permalink( $directory_page ) : get_post_type_archive_link( 'dssast_note' );
			?>
			<?php if ( $directory_url ) : ?>
				<p class="note-reader__back"><a href="<?php echo esc_url( $directory_url ); ?>">返回笔记目录</a></p>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<div class="note-reader__body">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
