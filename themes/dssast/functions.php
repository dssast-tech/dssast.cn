<?php
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus(['primary' => '顶部主导航']);
});

add_action('wp_enqueue_scripts', function () {
    $dir = get_template_directory();
    $uri = get_template_directory_uri();

    if (file_exists($dir . '/dist/app.css')) {
        wp_enqueue_style('theme-app', $uri . '/dist/app.css', [], filemtime($dir . '/dist/app.css'));
    }
    if (file_exists($dir . '/dist/app.js')) {
        wp_enqueue_script('theme-app', $uri . '/dist/app.js', [], filemtime($dir . '/dist/app.js'), true);
    }
});