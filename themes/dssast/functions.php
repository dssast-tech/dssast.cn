<?php
require_once get_template_directory() . '/inc/home-feed.php';
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
});

add_action('wp_enqueue_scripts', function () {
    $theme_uri = get_template_directory_uri();
    $theme_dir = get_template_directory();

    wp_enqueue_style('dssast-font-misans', 'https://cdn.jsdelivr.net/npm/misans@5.0.0/lib/Normal/MiSansVF.min.css', [], null);

    $css_path = $theme_dir . '/dist/app.css';
    if (file_exists($css_path)) {
        wp_enqueue_style('dssast-app', $theme_uri . '/dist/app.css', [], filemtime($css_path));
    }

    $js_path = $theme_dir . '/dist/app.js';
    if (file_exists($js_path)) {
        wp_enqueue_script('dssast-app', $theme_uri . '/dist/app.js', [], filemtime($js_path), [
            'strategy' => 'defer',
            'in_footer' => true,
        ]);
    }
});