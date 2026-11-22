<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>

<?php
$is_overlay = !empty($args['overlay']);
$header_class = $is_overlay ? 'is-overlay' : '';
$scroll_threshold = 64;
?>

<body>
  <header x-data="{ mobileOpen: false, scrolled: false }" class="<?= esc_attr($header_class) ?>"
    <?php if ($is_overlay) : ?>
    x-init="scrolled = window.scrollY > <?= $scroll_threshold ?>"
    @scroll.window.passive="scrolled = window.scrollY > <?= $scroll_threshold ?>"
    :class="{ 'is-scrolled': scrolled }"
    <?php endif; ?>>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo" aria-label="DSSAST">
      <!-- TODO: 这里应该放img，找一下资源 -->
      <span>DS</span>
      <span>SA</span>
      <span>ST</span>
    </a>

    <nav>
      <a href="<?php echo esc_url(home_url('/announcements/')); ?>">公告</a>
      <a href="<?php echo esc_url(home_url('/news/')); ?>">新闻</a>
      <a href="<?php echo esc_url(home_url('/events/')); ?>">赛事</a>
      <a href="<?php echo esc_url(home_url('/notes/')); ?>">笔记</a>
    </nav>
  </header>