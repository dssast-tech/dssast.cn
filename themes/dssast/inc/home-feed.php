<?php
/**
 * Published announcements and news for the homepage.
 */
defined( 'ABSPATH' ) || exit;

function dssast_home_feed( $category, $limit ) {
    $category = 'announcements' === $category ? 'announcements' : 'news';
    $now = current_datetime()->getTimestamp();
    $recent_since = $now - 3 * DAY_IN_SECONDS;

    $query = new WP_Query( array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'has_password' => false,
        'category_name' => $category,
        'posts_per_page' => $limit,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
    ) );

    $entries = array();
    foreach ( $query->posts as $post ) {
        $date = get_post_datetime( $post );
        $summary = $post->post_excerpt ?: strip_shortcodes( $post->post_content );
        $entries[] = array(
            'id' => (string) $post->ID,
            'title' => get_the_title( $post ),
            'url' => get_permalink( $post ),
            'summary' => wp_trim_words( wp_strip_all_tags( $summary ), 65, '…' ),
            'date_iso' => $date ? $date->format( DATE_W3C ) : '',
            'date' => $date ? $date->format( 'Y.m.d' ) : '',
            'month_day' => $date ? $date->format( 'm.d' ) : '',
            'year' => $date ? $date->format( 'Y' ) : '',
            'image_id' => get_post_thumbnail_id( $post ),
            'is_recent' => $date && $date->getTimestamp() >= $recent_since && $date->getTimestamp() <= $now,
        );
    }
    return $entries;
}
