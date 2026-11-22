<?php get_header(); ?>

<main>
  <section class="archive-list">
    <header class="archive-header">
      <h1><?php single_term_title(); ?></h1>
      <?php if (term_description()) : ?>
        <div class="archive-desc"><?php echo term_description(); ?></div>
      <?php endif; ?>
    </header>

    <?php if (have_posts()) : ?>
      <ul class="post-list">
        <?php while (have_posts()) : the_post(); ?>
          <li class="post-item">
            <a href="<?php the_permalink(); ?>">
              <h2><?php the_title(); ?></h2>
              <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                <?php echo esc_html(get_the_date('Y-m-d')); ?>
              </time>
            </a>
            <?php if (has_excerpt()) : ?>
              <p class="post-excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
          </li>
        <?php endwhile; ?>
      </ul>

      <?php
      the_posts_pagination([
        'mid_size'  => 2,
        'prev_text' => '← 上一页',
        'next_text' => '下一页 →',
      ]);
      ?>
    <?php else : ?>
      <p class="archive-empty">该分类下暂无内容。</p>
    <?php endif; ?>
  </section>
</main>

<?php get_footer(); ?>