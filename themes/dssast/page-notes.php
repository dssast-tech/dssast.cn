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
			$local_courses = array();
			$indexed_notes = get_posts( array(
				'post_type' => 'dssast_note', 'post_status' => 'publish', 'has_password' => false,
				'numberposts' => -1, 'meta_key' => 'dssast_note_course', 'orderby' => 'title', 'order' => 'ASC',
			) );
			foreach ( $indexed_notes as $note ) {
				$course = trim( get_post_meta( $note->ID, 'dssast_note_course', true ) );
				if ( '' === $course ) { continue; }
				$local_courses[ $course ][] = array(
					'title' => get_the_title( $note ), 'url' => get_permalink( $note ), 'local' => true,
					'credit' => get_post_meta( $note->ID, 'dssast_note_credit', true ),
					'credit_url' => get_post_meta( $note->ID, 'dssast_note_credit_url', true ),
					'term' => get_post_meta( $note->ID, 'dssast_note_term', true ),
					'formats' => array( get_post_meta( $note->ID, 'dssast_note_format', true ) ?: '在线图文' ),
				);
			}
			$courses = array_merge_recursive( $local_courses, $courses );
			$note_count = array_sum( array_map( 'count', $courses ) );
			$submission = get_page_by_path( 'note-submit' );
			$has_submission_page = $submission && 'publish' === $submission->post_status;
			?>
			<nav class="notes-sections" aria-label="笔记导航">
				<?php if ( $courses ) : ?><a href="#course-library">课程资料目录</a><?php endif; ?>
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
						<h2 id="library-heading">课程资料目录</h2>
						<p>按课程、作者或标题查找笔记。选择“本站原稿”可在本站阅读作者提供的全文；“软院 ReadMe”条目链接到<a href="https://ssast-readme.github.io/">软院科协原文</a>。</p>
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
						<label>资料来源<select data-source><option value="">全部来源</option><option value="local">本站原稿</option><option value="readme">软院 ReadMe</option></select></label>
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
										<li data-formats="<?php echo esc_attr( implode( '|', $item['formats'] ) ); ?>" data-source-kind="<?php echo empty( $item['local'] ) ? 'readme' : 'local'; ?>">
											<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
											<span class="notes-group__meta"><?php
												if ( ! empty( $item['credit'] ) && ! empty( $item['credit_url'] ) ) {
													printf( '<a rel="author" href="%s">%s</a> · ', esc_url( $item['credit_url'] ), esc_html( $item['credit'] ) );
												}
												$meta = array_filter( array( empty( $item['credit_url'] ) ? ( $item['credit'] ?? '' ) : '', $item['term'] ?? '', implode( ' / ', $item['formats'] ), empty( $item['local'] ) ? '软院 ReadMe' : '本站原稿' ) );
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
