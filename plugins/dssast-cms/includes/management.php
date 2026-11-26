<?php
defined( 'ABSPATH' ) || exit;

// Keep business forms usable when block-editor assets or REST requests fail.
add_filter( 'use_block_editor_for_post_type', function ( $enabled, $type ) {
 return in_array( $type, array( 'dssast_competition', 'dssast_note' ), true ) ? false : $enabled;
}, 100, 2 );

function dssast_ensure_categories() {
 foreach ( array( 'dssast-news' => '新闻', 'dssast-announcement' => '公告' ) as $slug => $name ) {
  if ( ! dssast_editorial_category( $slug ) ) { wp_insert_term( $name, 'category', array( 'slug' => $slug ) ); }
 }
}

function dssast_editorial_category( $slug ) {
 $site_slugs = array( 'dssast-news' => 'news', 'dssast-announcement' => 'announcements' );
 return get_term_by( 'slug', $slug, 'category' )
  ?: ( isset( $site_slugs[$slug] ) ? get_term_by( 'slug', $site_slugs[$slug], 'category' ) : false );
}

add_action( 'init', function () {
 if ( '0.3.0' !== get_option( 'dssast_schema_version' ) ) {
  dssast_ensure_categories();
  if ( dssast_editorial_category( 'dssast-news' ) && dssast_editorial_category( 'dssast-announcement' ) ) { update_option( 'dssast_schema_version', '0.3.0', false ); }
 }
}, 20 );

function dssast_content_list( $atts, $content, $tag ) {
 $atts = shortcode_atts( array( 'limit' => 10, 'category' => 'dssast-news' ), $atts, $tag );
 $args = array( 'post_type' => 'dssast_notes' === $tag ? 'dssast_note' : 'post', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => max( 1, min( 50, absint( $atts['limit'] ) ) ), 'no_found_rows' => true );
 if ( 'post' === $args['post_type'] ) {
  $category = in_array( $atts['category'], array( 'dssast-announcement', 'announcements' ), true ) ? 'dssast-announcement' : 'dssast-news';
  $term = dssast_editorial_category( $category );
  $args['category_name'] = $term ? $term->slug : $category;
 }
 $query = new WP_Query( $args ); $html = '<div class="dssast-content-list content-list">';
 foreach ( $query->posts as $post ) { $html .= '<article><h2><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h2><p>' . esc_html( wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: strip_shortcodes( $post->post_content ) ), 40 ) ) . '</p></article>'; }
 return $html . ( $query->posts ? '' : '<p>暂无内容。</p>' ) . '</div>';
}
add_shortcode( 'dssast_notes', 'dssast_content_list' );
add_shortcode( 'dssast_news', 'dssast_content_list' );

add_action( 'wp_enqueue_scripts', function () {
 wp_enqueue_style( 'dssast-public', plugins_url( 'assets/public.css', DSSAST_CMS_FILE ), array(), DSSAST_CMS_VERSION );
} );

add_filter( 'the_content', function ( $content ) {
 if ( ! is_singular( 'dssast_competition' ) || ! in_the_loop() || ! is_main_query() || post_password_required() ) { return $content; }

 $id = get_the_ID();
 $state = dssast_competition_state( $id );
 $start = (string) get_post_meta( $id, 'dssast_date', true );
 $end = (string) get_post_meta( $id, 'dssast_end_date', true );
 if ( '' === $end || $end < $start ) { $end = $start; }

 $details = '<p>' . esc_html( '赛事状态：' . $state['label'] ) . '</p>';
 if ( '' !== $start ) {
  $details .= '<p>' . esc_html( '赛事日期：' . ( $start === $end ? $start : $start . ' – ' . $end ) ) . '</p>';
 }

 $links = dssast_competition_links( $id );
 if ( $links ) {
  $details .= '<h2>推送</h2><ul class="dssast-details__links">';
  foreach ( $links as $link ) {
   $details .= '<li><a rel="nofollow noopener" target="_blank" href="' . esc_url( $link['url'], array( 'http', 'https' ) ) . '">' . esc_html( $link['title'] ) . '</a></li>';
  }
  $details .= '</ul>';
 }

 return '' === $details ? $content : $content . '<section class="dssast-details"><h2>赛事信息</h2>' . $details . '</section>';
} );
