<?php get_header(); ?>

<main>
  <article>
    <header><h1>404 NOT FOUND</h1></header>
    <h2>页面不存在或已移动</h2>
    <p class="">请检查访问地址是否正确，或返回官网首页浏览最新动态。</p>
    <a href="<?php echo esc_url(home_url('/')); ?>">
      返回首页
    </a>
  </article>
</main>

<?php get_footer(); ?>