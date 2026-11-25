<?php get_header(); ?>
<main class="notes-page note-submission">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php $directory_page = get_page_by_path( 'notes' ); ?>
		<?php if ( $directory_page && 'publish' === $directory_page->post_status ) : ?>
			<a href="<?php echo esc_url( get_permalink( $directory_page ) ); ?>">返回笔记目录</a>
		<?php endif; ?>
		<h1 class="notes-page__title"><?php the_title(); ?></h1>
		<?php if ( post_password_required() ) : ?>
			<?php the_content(); ?>
		<?php else : ?>
			<section class="submission-guide" aria-labelledby="submission-guide-heading">
				<h2 id="submission-guide-heading">投稿须知</h2>
				<p>请通过电子邮件投稿，将 Markdown 原稿、PDF 或可访问的稿件链接随邮件发来。</p>
				<?php the_content(); ?>
				<?php if ( shortcode_exists( 'dssast_note_submit' ) && ! has_shortcode( get_post()->post_content, 'dssast_note_submit' ) ) : ?>
					<?php echo do_shortcode( '[dssast_note_submit]' ); ?>
				<?php endif; ?>
				<p>邮件主题可写为“【笔记投稿】姓名或笔名 · 稿件主题”。请在邮件中注明公开署名、课程和适用学期。如需链接到个人主页，请附上网址。</p>
				<details>
					<summary>可以投哪些内容？</summary>
					<dl>
						<dt>课程笔记</dt><dd>按章节整理自己的理解，标明适用范围。引用教材、课件或他人资料时请注明出处。</dd>
						<dt>作业攻略</dt><dd>分享环境搭建、解题思路和踩坑经验。请保留独立完成作业的空间，省略可直接提交的代码和答案。</dd>
						<dt>技术分享</dt><dd>介绍一个实用工具或技术，从用途和上手步骤写起，附上示例和参考资料。</dd>
						<dt>科创经验</dt><dd>分享选题、团队分工、研究方法或项目复盘，说明哪些经验可供后来者借鉴。</dd>
					</dl>
				</details>
				<details>
					<summary>怎样准备和提交稿件？</summary>
					<p>Markdown 如含插图，请用相对路径引用，将原稿和图片一起打包为邮件附件，或在邮件中附上共享文件夹链接。已有相同主题时，请说明你的补充或适用学期。</p>
					<p>请确认有权公开分享正文和配图。稿件采用后会按约定署名发布。</p>
				</details>
			</section>
		<?php endif; ?>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
