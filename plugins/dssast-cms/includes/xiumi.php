<?php
defined( 'ABSPATH' ) || exit;
function dssast_safe_import_html( $html ) {
 $html = preg_replace( '/<!--.*?-->/s', '', $html );
 return str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), wp_kses_post( $html ) );
}
function dssast_xiumi_config( $name ) { return defined( 'DSSAST_XIUMI_' . $name ) ? (string) constant( 'DSSAST_XIUMI_' . $name ) : (string) getenv( 'DSSAST_XIUMI_' . $name ); }
function dssast_xiumi_log( $code ) {
 $logs = get_option( 'dssast_xiumi_logs', array() );
 $logs[] = array( 'time' => gmdate( 'c' ), 'code' => sanitize_key( $code ) );
 update_option( 'dssast_xiumi_logs', array_slice( $logs, -100 ), false );
}
function dssast_xiumi_fail( $code, $status = 400 ) { dssast_xiumi_log( $code ); return new WP_Error( $code, '同步失败：' . $code, array( 'status' => $status ) ); }
function dssast_xiumi_auth( $request ) {
 $secret = dssast_xiumi_config( 'SECRET' );
 if ( '' === $secret || '' === dssast_xiumi_config( 'APP_ID' ) || get_option( 'dssast_xiumi_revoked' ) === hash( 'sha256', $secret ) ) { return new WP_Error( 'dssast_disabled', '接口未配置或密钥已失效', array( 'status' => 503 ) ); }
 if ( ! is_ssl() && 'local' !== wp_get_environment_type() ) { return new WP_Error( 'dssast_https_required', '需要 HTTPS', array( 'status' => 403 ) ); }
 return hash_equals( 'secret ' . $secret, (string) $request->get_header( 'authorization' ) ) ? true : new WP_Error( 'dssast_unauthorized', '认证失败', array( 'status' => 401 ) );
}
function dssast_xiumi_user( $partner ) {
 if ( ! is_string( $partner ) || ! preg_match( '/^[a-f0-9]{32}$/D', $partner ) ) { return 0; }
 $users = get_users( array( 'meta_key' => 'dssast_xiumi_partner', 'meta_value' => $partner, 'number' => 1, 'fields' => 'ID' ) );
 return $users && user_can( $users[0], 'manage_options' ) ? (int) $users[0] : 0;
}
add_action( 'rest_api_init', function () {
 foreach ( array( 'html', 'image', 'notify' ) as $endpoint ) {
  register_rest_route( 'dssast/v1', '/xiumi/' . $endpoint, array( 'methods' => 'POST', 'permission_callback' => 'dssast_xiumi_auth', 'callback' => 'dssast_xiumi_' . $endpoint ) );
 }
} );
function dssast_xiumi_bound_user( $request ) {
 $q = $request->get_query_params();
 $uid = dssast_xiumi_user( $q['partner_user_id'] ?? null );
 $open = $q['open_id'] ?? null;
 return $uid && is_string( $open ) && '' !== $open && hash_equals( (string) get_user_meta( $uid, 'dssast_xiumi_open', true ), $open ) ? $uid : 0;
}
function dssast_xiumi_html( $request ) {
 $uid = dssast_xiumi_bound_user( $request );
 if ( ! $uid ) { return dssast_xiumi_fail( 'dssast_binding', 403 ); }
 if ( strlen( $request->get_body() ) > 2000000 ) { return dssast_xiumi_fail( 'dssast_body_size', 413 ); }
 $body = $request->get_json_params();
 if ( ! is_array( $body ) || ! isset( $body['articles'] ) || ! is_array( $body['articles'] ) || ! count( $body['articles'] ) || count( $body['articles'] ) > 10 ) { return dssast_xiumi_fail( 'dssast_articles' ); }
 foreach ( $body['articles'] as $a ) {
  if ( ! is_array( $a ) || ! isset( $a['article_id'], $a['title'], $a['description'] ) || ! is_scalar( $a['article_id'] ) || ! preg_match( '/^[0-9]{1,20}$/D', (string) $a['article_id'] ) || ! is_string( $a['title'] ) || ! is_string( $a['description'] ) || '' === trim( sanitize_text_field( $a['title'] ) ) || ( isset( $a['summary'] ) && ! is_string( $a['summary'] ) ) || ( isset( $a['picurl'] ) && ! is_string( $a['picurl'] ) ) ) { return dssast_xiumi_fail( 'dssast_article_fields' ); }
 }
 $lock = 'dssast_xiumi_lock_' . $uid;
 if ( ! add_option( $lock, time(), '', false ) ) { return dssast_xiumi_fail( 'dssast_busy', 409 ); }
 try {
  foreach ( $body['articles'] as $a ) {
   $key = hash( 'sha256', dssast_xiumi_config( 'APP_ID' ) . ':' . $uid . ':' . $a['article_id'] );
   $fingerprint = hash( 'sha256', wp_json_encode( $a ) );
   $old = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'draft', 'pending', 'publish', 'private', 'future', 'trash' ), 'meta_key' => 'dssast_xiumi_key', 'meta_value' => $key, 'numberposts' => 1 ) );
   $existing = $old ? $old[0] : null;
   $previous_hash = $existing ? get_post_meta( $existing->ID, 'dssast_xiumi_hash', true ) : '';
   if ( $previous_hash === $fingerprint ) { continue; }
   if ( $existing && 'draft' !== $existing->post_status ) { return dssast_xiumi_fail( 'dssast_existing_not_draft', 409 ); }

   // A missing fingerprint marks a new or incomplete import. Later edits retain editorial categories.
   $post = array(
    'ID' => $existing ? $existing->ID : 0,
    'post_type' => 'post',
    'post_status' => 'draft',
    'post_author' => $uid,
    'post_title' => sanitize_text_field( $a['title'] ),
    'post_content' => dssast_safe_import_html( $a['description'] ),
    'post_excerpt' => sanitize_textarea_field( $a['summary'] ?? '' ),
    'meta_input' => array( 'dssast_xiumi_key' => $key, 'dssast_xiumi_article_id' => (string) $a['article_id'] ),
   );
   if ( '' === $previous_hash ) {
    dssast_ensure_categories();
    $news = get_term_by( 'slug', 'news', 'category' );
    if ( ! $news ) { return dssast_xiumi_fail( 'dssast_news_category', 500 ); }
    $post['post_category'] = array( (int) $news->term_id );
   }

   $id = wp_insert_post( wp_slash( $post ), true );
   if ( is_wp_error( $id ) ) { return dssast_xiumi_fail( 'dssast_insert', 500 ); }
   if ( isset( $post['post_category'] ) && ! has_category( $post['post_category'], $id ) ) { return dssast_xiumi_fail( 'dssast_news_category', 500 ); }

   $cover = attachment_url_to_postid( esc_url_raw( $a['picurl'] ?? '' ) );
   if ( $cover && (int) get_post_field( 'post_author', $cover ) === $uid && wp_attachment_is_image( $cover ) ) { set_post_thumbnail( $id, $cover ); }
   if ( ! update_post_meta( $id, 'dssast_xiumi_hash', $fingerprint ) ) { return dssast_xiumi_fail( 'dssast_import_fingerprint', 500 ); }
  }
 } finally { delete_option( $lock ); }
 return array( 'code' => 0 );
}
function dssast_xiumi_image( $request ) {
 $uid = dssast_xiumi_bound_user( $request );
 if ( ! $uid ) { return dssast_xiumi_fail( 'dssast_binding', 403 ); }
 $files = $request->get_file_params(); $file = $files['img-upload'] ?? null;
 if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['error'] ) || ! is_string( $file['tmp_name'] ) || UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) || filesize( $file['tmp_name'] ) > 5242880 ) { return dssast_xiumi_fail( 'dssast_image_size' ); }
 $info = wp_getimagesize( $file['tmp_name'] );
 $types = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp' );
 if ( ! $info || ! isset( $types[$info['mime']] ) || $info[0] * $info[1] > 20000000 ) { return dssast_xiumi_fail( 'dssast_image_type' ); }
 $hash = hash_file( 'sha256', $file['tmp_name'] ); $lock = 'dssast_image_lock_' . $uid;
 if ( ! add_option( $lock, time(), '', false ) ) { return dssast_xiumi_fail( 'dssast_busy', 409 ); }
 try {
  $old = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'author' => $uid, 'meta_key' => 'dssast_xiumi_image_hash', 'meta_value' => $hash, 'numberposts' => 1 ) );
  if ( $old ) { return array( 'code' => 0, 'data' => array( 'url' => wp_get_attachment_url( $old[0]->ID ) ) ); }
  require_once ABSPATH . 'wp-admin/includes/image.php'; require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php';
  // Decode and re-encode raster images; never preserve uploaded executable payloads.
  $editor = wp_get_image_editor( $file['tmp_name'] );
  if ( is_wp_error( $editor ) ) { return dssast_xiumi_fail( 'dssast_image_decode' ); }
  $tmp = wp_tempnam( 'dssast-image' );
  $saved = $editor->save( $tmp . '.' . $types[$info['mime']], $info['mime'] ); @unlink( $tmp );
  if ( is_wp_error( $saved ) ) { return dssast_xiumi_fail( 'dssast_image_encode' ); }
  $id = media_handle_sideload( array( 'name' => $hash . '.' . $types[$info['mime']], 'tmp_name' => $saved['path'] ), 0, null, array( 'post_author' => $uid ) );
  if ( file_exists( $saved['path'] ) ) { unlink( $saved['path'] ); }
  if ( is_wp_error( $id ) ) { return dssast_xiumi_fail( 'dssast_image_save', 500 ); }
  update_post_meta( $id, 'dssast_xiumi_image_hash', $hash );
  return array( 'code' => 0, 'data' => array( 'url' => wp_get_attachment_url( $id ) ) );
 } finally { delete_option( $lock ); }
}
function dssast_xiumi_notify( $request ) {
 if ( strlen( $request->get_body() ) > 10000 ) { return dssast_xiumi_fail( 'dssast_body_size', 413 ); }
 $b = $request->get_json_params();
 if ( ! is_array( $b ) || ( $b['app_id'] ?? null ) !== dssast_xiumi_config( 'APP_ID' ) || ! isset( $b['type'] ) || ! is_string( $b['type'] ) ) { return dssast_xiumi_fail( 'dssast_notification' ); }
 if ( 'secret_reset' === $b['type'] ) { update_option( 'dssast_xiumi_revoked', hash( 'sha256', dssast_xiumi_config( 'SECRET' ) ), false ); dssast_xiumi_log( 'secret_reset' ); }
 elseif ( in_array( $b['type'], array( 'bind', 'unbind' ), true ) ) {
  $uid = dssast_xiumi_user( $b['partner_user_id'] ?? null );
  if ( ! $uid || ! isset( $b['open_id'] ) || ! is_string( $b['open_id'] ) || ! preg_match( '/^[a-zA-Z0-9_-]{1,200}$/D', $b['open_id'] ) ) { return dssast_xiumi_fail( 'dssast_binding' ); }
  if ( 'bind' === $b['type'] ) { update_user_meta( $uid, 'dssast_xiumi_open', $b['open_id'] ); }
  else { delete_user_meta( $uid, 'dssast_xiumi_open', $b['open_id'] ); }
 } elseif ( in_array( $b['type'], array( 'err_html', 'err_image' ), true ) ) {
  $key = 'dssast_notice_' . hash( 'sha256', wp_json_encode( $b ) );
  if ( ! get_transient( $key ) ) { dssast_xiumi_log( $b['type'] ); set_transient( $key, 1, DAY_IN_SECONDS ); }
 } else { return dssast_xiumi_fail( 'dssast_notification_type' ); }
 return array( 'code' => 0 );
}
add_action( 'admin_menu', function () { add_management_page( '秀米同步', '秀米同步', 'manage_options', 'dssast-xiumi', 'dssast_xiumi_page' ); } );
function dssast_xiumi_page() {
 if ( ! current_user_can( 'manage_options' ) ) { return; }
 echo '<div class="wrap"><h1>秀米同步</h1><p>通过环境变量或 wp-config.php 设置 DSSAST_XIUMI_APP_ID 与 DSSAST_XIUMI_SECRET。密钥不在此显示。</p>';
 foreach ( array( 'html', 'image', 'notify' ) as $endpoint ) { echo '<p>' . esc_html( $endpoint . ': ' . rest_url( 'dssast/v1/xiumi/' . $endpoint ) ) . '</p>'; }
 echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post"><input type="hidden" name="action" value="dssast_xiumi_bind">'; wp_nonce_field( 'dssast_xiumi_bind' ); submit_button( '绑定当前管理员的秀米账号' ); echo '</form><h2>最近错误（最多100条）</h2><ul>';
 foreach ( array_reverse( get_option( 'dssast_xiumi_logs', array() ) ) as $log ) { echo '<li>' . esc_html( $log['time'] . ' ' . $log['code'] ) . '</li>'; } echo '</ul></div>';
}
add_action( 'admin_post_dssast_xiumi_bind', function () {
 if ( ! current_user_can( 'manage_options' ) ) { wp_die( '权限不足' ); } check_admin_referer( 'dssast_xiumi_bind' );
 $secret = dssast_xiumi_config( 'SECRET' ); $appid = dssast_xiumi_config( 'APP_ID' );
 if ( ! $secret || ! $appid ) { wp_die( '请先配置秀米应用' ); }
 $uid = get_current_user_id(); $partner = get_user_meta( $uid, 'dssast_xiumi_partner', true );
 if ( ! $partner ) { $partner = bin2hex( random_bytes( 16 ) ); update_user_meta( $uid, 'dssast_xiumi_partner', $partner ); }
 $timestamp = (string) time(); $nonce = bin2hex( random_bytes( 16 ) ); $parts = array( $secret, $timestamp, $nonce, $partner ); sort( $parts, SORT_STRING );
 wp_redirect( add_query_arg( array( 'appid' => $appid, 'partner_user_id' => $partner, 'timestamp' => $timestamp, 'nonce' => $nonce, 'signature' => md5( md5( implode( '', $parts ) ) ) ), 'https://xiumi.us/auth/partner/bind' ) ); exit;
} );
