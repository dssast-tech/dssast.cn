<?php get_header(); ?>
<main class="notes-page note-submission">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php $directory_page = get_page_by_path( 'notes' ); ?>
		<?php if ( $directory_page && 'publish' === $directory_page->post_status ) : ?>
			<a href="<?php echo esc_url( get_permalink( $directory_page ) ); ?>">返回笔记目录</a>
		<?php endif; ?>
		<h1 class="notes-page__title"><?php the_title(); ?></h1>
		<?php if ( ! post_password_required() ) : ?>
			<p class="note-submission__intro">请注明课程、适用学期和资料来源。引用他人笔记或附件时，请附原文链接。</p>
		<?php endif; ?>
		<?php the_content(); ?>
		<?php if ( ! post_password_required() && shortcode_exists( 'dssast_note_submit' ) && ! has_shortcode( get_post()->post_content, 'dssast_note_submit' ) ) : ?>
			<?php echo do_shortcode( '[dssast_note_submit]' ); ?>
		<?php endif; ?>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
