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
			<?php if ( ! post_password_required() && get_post_meta( get_the_ID(), 'dssast_note_credit', true ) ) : ?>
				<?php
				$credit = get_post_meta( get_the_ID(), 'dssast_note_credit', true );
				$profile = get_post_meta( get_the_ID(), 'dssast_note_credit_url', true );
				$term = get_post_meta( get_the_ID(), 'dssast_note_term', true );
				?>
				<p class="note-reader__credit">作者
					<?php if ( $profile ) : ?><a rel="author" href="<?php echo esc_url( $profile ); ?>"><?php echo esc_html( $credit ); ?></a><?php else : ?><?php echo esc_html( $credit ); ?><?php endif; ?>
					<?php if ( $term ) : ?> · <?php echo esc_html( $term ); ?><?php endif; ?>
				</p>
			<?php endif; ?>
			<div class="note-reader__body">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
