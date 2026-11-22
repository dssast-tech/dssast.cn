<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php wp_head(); ?>
</head>

<?php $header_class = 'site-header' . (!empty($args['overlay']) ? ' is-overlay' : ''); ?>

<body>
  <header x-data="{ mobileOpen: false }" class="<?= esc_attr($header_class) ?>">
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