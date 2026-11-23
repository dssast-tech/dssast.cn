<?php

require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$wxr = simplexml_load_file('/tmp/dssast-sample-content.wxr', 'SimpleXMLElement', LIBXML_NONET);
if ($wxr === false) {
    throw new RuntimeException('Could not read the Playground WXR fixture.');
}

$site_title = trim((string) $wxr->channel->title);
if ($site_title !== '') {
    update_option('blogname', $site_title);
}

// Seed the category slugs used by the theme before the CMS plugin initializes.
wp_insert_term('新闻', 'category', ['slug' => 'news']);
wp_insert_term('公告', 'category', ['slug' => 'announcements']);

$mounted_plugins = json_decode(base64_decode(DSSAST_PLAYGROUND_PLUGINS), true, flags: JSON_THROW_ON_ERROR);
$plugins_to_activate = array_filter(
    array_keys(get_plugins()),
    static fn($plugin) => in_array(explode('/', $plugin, 2)[0], $mounted_plugins, true)
);
$result = activate_plugins($plugins_to_activate);
if (is_wp_error($result)) {
    throw new RuntimeException($result->get_error_message());
}
