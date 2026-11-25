<?php get_header(); ?>
<main class="notes-page note-submission">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php $directory_page = get_page_by_path( 'notes' ); ?>
		<?php if ( $directory_page && 'publish' === $directory_page->post_status ) : ?>
			<a href="<?php echo esc_url( get_permalink( $directory_page ) ); ?>">返回笔记目录</a>
		<?php endif; ?>
		<h1 class="notes-page__title"><?php the_title(); ?></h1>
		<?php if ( ! post_password_required() ) : ?>
			<section class="submission-guide" aria-labelledby="submission-guide-heading">
				<h2 id="submission-guide-heading">投稿须知</h2>
				<p>登录后填写下方表单。请在正文开头写明公开署名（姓名或笔名）、课程或主题、适用学期。希望署名链接到个人主页的同学，请一并附上网址。</p>
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
					<p>可直接填写正文，也可附 Markdown 原稿或 PDF 的可访问链接。Markdown 如含插图，请用相对路径引用，并将原稿与图片放在同一文件夹分享。已有相同主题时，请说明你的补充或适用学期。</p>
					<p>请确认有权公开分享正文和配图。投稿经审核后会按约定署名发布；可在下方“我的投稿”查看进度和退回意见。</p>
					<dl class="submission-guide__email"><dt>投稿邮箱</dt><dd aria-label="暂未公布">—</dd></dl>
				</details>
			</section>
		<?php endif; ?>
		<?php the_content(); ?>
		<?php if ( ! post_password_required() && shortcode_exists( 'dssast_note_submit' ) && ! has_shortcode( get_post()->post_content, 'dssast_note_submit' ) ) : ?>
			<?php echo do_shortcode( '[dssast_note_submit]' ); ?>
		<?php endif; ?>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
