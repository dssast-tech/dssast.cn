<?php
defined( 'ABSPATH' ) || exit;

// Keep business forms usable when block-editor assets or REST requests fail.
add_filter( 'use_block_editor_for_post_type', function ( $enabled, $type ) {
 return in_array( $type, array( 'dssast_competition', 'dssast_note' ), true ) ? false : $enabled;
}, 100, 2 );

function dssast_ensure_categories() {
 foreach ( array( 'news' => '新闻', 'announcement' => '公告' ) as $slug => $name ) {
  if ( ! get_term_by( 'slug', $slug, 'category' ) ) { wp_insert_term( $name, 'category', array( 'slug' => $slug ) ); }
 }
}
add_action( 'init', function () {
 if ( '0.3.0' !== get_option( 'dssast_schema_version' ) ) {
  dssast_ensure_categories();
  if ( get_term_by( 'slug', 'news', 'category' ) && get_term_by( 'slug', 'announcement', 'category' ) ) { update_option( 'dssast_schema_version', '0.3.0', false ); }
 }
}, 20 );

function dssast_content_list( $atts, $content, $tag ) {
 $atts = shortcode_atts( array( 'limit' => 10, 'category' => 'news' ), $atts, $tag );
 $args = array( 'post_type' => 'dssast_notes' === $tag ? 'dssast_note' : 'post', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => max( 1, min( 50, absint( $atts['limit'] ) ) ), 'no_found_rows' => true );
 if ( 'post' === $args['post_type'] ) { $args['category_name'] = in_array( $atts['category'], array( 'news', 'announcement' ), true ) ? $atts['category'] : 'news'; }
 $query = new WP_Query( $args ); $html = '<div class="content-list">';
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
 $details = '';
 foreach ( dssast_fields() as $field => $label ) {
  $value = get_post_meta( get_the_ID(), 'dssast_' . $field, true );
  if ( ! is_string( $value ) || '' === $value ) { continue; }
  if ( 'registration_url' === $field ) { $details .= '<p><a class="dssast-registration" rel="nofollow" href="' . esc_url( $value, array( 'http', 'https' ) ) . '">前往报名</a></p>'; }
  else { $details .= '<p>' . esc_html( $label . '：' . ( 'status' === $field ? ( dssast_statuses()[$value] ?? '未设置' ) : str_replace( 'T', ' ', $value ) ) ) . '</p>'; }
 }
 return '' === $details ? $content : $content . '<section class="dssast-details"><h2>赛事信息</h2>' . $details . '</section>';
} );
