<?php
get_header();
?>
<main class="notes-page">
	<?php while ( have_posts() ) : the_post(); ?>
		<h1 class="notes-page__title"><?php the_title(); ?></h1>
		<?php if ( post_password_required() ) : ?>
			<?php the_content(); ?>
		<?php else : ?>
			<?php
			$directory = wp_json_file_decode( get_theme_file_path( 'data/readme-notes.json' ), array( 'associative' => true ) );
			$courses   = $directory['courses'] ?? array();
			$related   = $directory['related'] ?? array();
			$note_count = array_sum( array_map( 'count', $courses ) );
			$submission = get_page_by_path( 'note-submit' );
			$has_submission_page = $submission && 'publish' === $submission->post_status;
			?>
			<nav class="notes-sections" aria-label="笔记导航">
				<?php if ( $courses ) : ?><a href="#course-library">软院课程资料</a><?php endif; ?>
				<a href="#local-notes">本站笔记</a>
				<?php if ( $related ) : ?><a href="#related-reading">延伸阅读</a><?php endif; ?>
				<?php if ( $has_submission_page ) : ?>
					<a class="notes-sections__submit" href="<?php echo esc_url( get_permalink( $submission ) ); ?>">投稿与查看进度</a>
				<?php elseif ( has_shortcode( get_post()->post_content, 'dssast_note_submit' ) ) : ?>
					<a href="#local-notes">投稿与查看进度</a>
				<?php endif; ?>
			</nav>

			<?php if ( $courses ) : ?>
				<section id="course-library" class="notes-library" aria-labelledby="library-heading" data-note-library>
					<div class="notes-library__intro">
						<h2 id="library-heading">软院课程资料</h2>
						<p>按课程查找复习笔记、分讲资料和讲义。点击题名可在<a href="https://ssast-readme.github.io/">软院 ReadMe</a> 阅读原文或下载附件。</p>
					</div>
					<div class="notes-filters" data-filters hidden>
						<label>搜索笔记<input data-query type="search" placeholder="输入课程名、标题或作者"></label>
						<label>课程或专题<select data-course><option value="">全部课程与专题</option>
							<?php foreach ( array_keys( $courses ) as $course ) : ?>
								<option value="<?php echo esc_attr( $course ); ?>"><?php echo esc_html( $course ); ?></option>
							<?php endforeach; ?>
						</select></label>
						<label>资料格式<select data-format>
							<option value="">全部格式</option>
							<option>在线图文</option><option>PDF</option><option>DOCX</option>
						</select></label>
					</div>
					<div class="notes-results">
						<span role="status" data-count><?php printf( '%d 个课程或专题 · %d 条资料', count( $courses ), $note_count ); ?></span>
						<button type="button" data-reset hidden>清除筛选</button>
					</div>
					<div class="notes-grid">
						<?php foreach ( $courses as $course => $items ) : ?>
							<section class="notes-group" data-group="<?php echo esc_attr( $course ); ?>">
								<h3><?php echo esc_html( $course ); ?></h3>
								<ul>
									<?php foreach ( $items as $item ) : ?>
										<li data-formats="<?php echo esc_attr( implode( '|', $item['formats'] ) ); ?>">
											<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
											<span class="notes-group__meta"><?php
												$meta = array_filter( array( $item['credit'] ?? '', $item['term'] ?? '', implode( ' / ', $item['formats'] ) ) );
												if ( isset( $item['attachments'] ) ) {
													$meta[] = sprintf( '%d 份附件', $item['attachments'] );
												}
												echo esc_html( implode( ' · ', $meta ) );
											?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endforeach; ?>
					</div>
					<p class="notes-empty" data-empty hidden>没有找到匹配的资料。可以换个关键词，或清除筛选。</p>
				</section>
			<?php endif; ?>

			<section id="local-notes" class="notes-local" aria-labelledby="local-notes-heading">
				<h2 id="local-notes-heading">本站笔记</h2>
				<div class="notes-local__content">
					<?php
					$page_content = get_the_content();
					the_content();
					if ( shortcode_exists( 'dssast_notes' ) && ! has_shortcode( $page_content, 'dssast_notes' ) ) {
						echo do_shortcode( '[dssast_notes]' );
					}
					?>
				</div>
				<?php $archive = get_post_type_archive_link( 'dssast_note' ); ?>
				<?php if ( $archive ) : ?>
					<a class="notes-more" href="<?php echo esc_url( $archive ); ?>">浏览全部本站笔记</a>
				<?php endif; ?>
			</section>

			<?php if ( $related ) : ?>
				<section class="notes-related" id="related-reading" aria-labelledby="related-heading">
					<h2 id="related-heading">延伸阅读</h2>
					<ul><?php foreach ( $related as $item ) : ?>
						<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></li>
					<?php endforeach; ?></ul>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
