<?php
defined( 'ABSPATH' ) || exit;
function dssast_register_notes() {
 $caps = array();
 foreach ( array( 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ) as $cap ) { $caps[$cap] = 'manage_options'; }
 // Keep single-note permalinks while leaving /notes/ available to the site's landing page.
 register_post_type( 'dssast_note', array( 'labels' => array( 'name' => '学习笔记', 'singular_name' => '笔记', 'add_new_item' => '添加笔记', 'edit_item' => '编辑笔记' ), 'menu_icon' => 'dashicons-book-alt', 'menu_position' => 28, 'public' => true, 'show_in_rest' => true, 'has_archive' => 'dssast-notes', 'rewrite' => array( 'slug' => 'notes' ), 'supports' => array( 'title', 'editor', 'author', 'revisions' ), 'capabilities' => $caps, 'map_meta_cap' => true ) );
}
add_action( 'init', 'dssast_register_notes' );
add_action( 'init', function () {
    add_post_type_support( 'dssast_note', 'custom-fields' );
    add_post_type_support( 'dssast_note', 'excerpt' );
    foreach ( array( 'course', 'credit', 'term', 'format', 'credit_url' ) as $field ) {
        register_post_meta( 'dssast_note', 'dssast_note_' . $field, array(
            'type' => 'string', 'single' => true, 'show_in_rest' => true,
            'sanitize_callback' => 'credit_url' === $field
                ? static function ( $value ) { return esc_url_raw( $value, array( 'https', 'http' ) ); }
                : 'sanitize_text_field',
        ) );
    }
} );
add_action( 'pre_get_posts', function ( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'dssast_note' ) ) {
        $query->set( 'post_status', 'publish' );
        $query->set( 'has_password', false );
    }
} );
add_action( 'template_redirect', function () {
    $page = get_queried_object();
    if ( is_page( 'note-submit' ) || ( is_singular() && $page instanceof WP_Post && has_shortcode( $page->post_content, 'dssast_note_submit' ) ) ) {
        nocache_headers();
    }
} );

add_shortcode( 'dssast_note_submit', function () {
 // Older installations may have placed both shortcodes on the public directory.
 if ( is_page( 'notes' ) ) {
  $submission = get_page_by_path( 'note-submit' );
  if ( $submission && 'publish' === $submission->post_status ) {
   return '<p><a href="' . esc_url( get_permalink( $submission ) ) . '">进入笔记投稿页</a></p>';
  }
 }
 if ( ! is_user_logged_in() ) { return '<p>请先<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">登录</a>再投稿。</p>'; }
 if ( ! current_user_can( 'read' ) ) { return '<p>当前账号不可投稿。</p>'; }
 // The post type already registers dssast_note as a public query variable.
 $id = isset( $_GET['dssast_edit_note'] ) && is_string( $_GET['dssast_edit_note'] ) ? absint( $_GET['dssast_edit_note'] ) : 0;
 $note = $id ? get_post( $id ) : null;
 if ( $id && ( ! $note || 'dssast_note' !== $note->post_type || (int) $note->post_author !== get_current_user_id() || 'draft' !== $note->post_status ) ) { return '<p>此笔记不可修改。</p>'; }
 ob_start();
 echo '<div class="dssast-note-submit"><p>标题限 200 字，正文支持基本 HTML。附件请填写链接。每分钟可投一篇新笔记，审核结果可在下方“我的投稿”查看。</p>';
 if ( isset( $_GET['dssast_submitted'] ) && '1' === $_GET['dssast_submitted'] ) { echo '<p role="status">笔记已提交，等待审核。</p>'; }
 if ( $note ) { echo '<p>审核意见：' . esc_html( get_post_meta( $id, 'dssast_review_reason', true ) ) . '</p>'; }
 echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="dssast_submit_note"><input type="hidden" name="dssast_note_id" value="' . absint( $id ) . '"><input type="hidden" name="dssast_return" value="' . esc_url( get_permalink() ) . '">';
 wp_nonce_field( 'dssast_submit_note_' . $id, 'dssast_nonce' );
 echo '<p><label>标题 <input required maxlength="200" name="dssast_title" value="' . esc_attr( $note ? $note->post_title : '' ) . '"></label></p><p><label>正文 <textarea required rows="14" name="dssast_content">' . esc_textarea( $note ? $note->post_content : '' ) . '</textarea></label></p><button type="submit">提交审核</button></form><h2>我的投稿（最近 30 条）</h2>';
 $items = get_posts( array( 'post_type' => 'dssast_note', 'author' => get_current_user_id(), 'post_status' => array( 'pending', 'draft', 'publish' ), 'numberposts' => 30 ) );
 if ( ! $items ) { echo '<p>暂无投稿。</p></div>'; return ob_get_clean(); }
 echo '<ul>';
 foreach ( $items as $item ) {
  $labels = array( 'pending' => '待审核', 'draft' => '退回/草稿', 'publish' => '已发布' );
  echo '<li>' . esc_html( $item->post_title . ' — ' . $labels[$item->post_status] );
  if ( 'draft' === $item->post_status ) { echo ' <a href="' . esc_url( add_query_arg( 'dssast_edit_note', $item->ID, get_permalink() ) ) . '">修改再提交</a>'; }
  if ( 'publish' === $item->post_status ) { echo ' <a href="' . esc_url( get_permalink( $item ) ) . '">阅读笔记</a>'; }
  echo '</li>';
 }
 echo '</ul></div>'; return ob_get_clean();
} );
add_action( 'admin_post_dssast_submit_note', function () {
 if ( ! current_user_can( 'read' ) ) { wp_die( '无投稿权限', '', array( 'response' => 403 ) ); }
 $id = isset( $_POST['dssast_note_id'] ) && is_string( $_POST['dssast_note_id'] ) ? absint( $_POST['dssast_note_id'] ) : 0;
 check_admin_referer( 'dssast_submit_note_' . $id, 'dssast_nonce' );
 $note = $id ? get_post( $id ) : null;
 if ( $id && ( ! $note || 'dssast_note' !== $note->post_type || (int) $note->post_author !== get_current_user_id() || 'draft' !== $note->post_status ) ) { wp_die( '不可修改此笔记', '', array( 'response' => 403 ) ); }
 foreach ( array( 'dssast_title', 'dssast_content' ) as $key ) { if ( ! isset( $_POST[$key] ) || ! is_string( $_POST[$key] ) ) { wp_die( '字段格式错误' ); } }
 $title = sanitize_text_field( wp_unslash( $_POST['dssast_title'] ) );
 $content = dssast_safe_import_html( wp_unslash( $_POST['dssast_content'] ) );
 if ( ! preg_match( '/\A.{1,200}\z/us', $title ) ) { wp_die( '标题须为 1 至 200 字' ); }
 if ( '' === trim( wp_strip_all_tags( $content ) ) || strlen( $content ) > 100000 ) { wp_die( '正文为空或过长' ); }
 $lock = 'dssast_note_lock_' . get_current_user_id();
 if ( ! add_option( $lock, time(), '', false ) ) { wp_die( '请勿重复提交，请稍后重试' ); }
 try {
  $recent = get_posts( array( 'post_type' => 'dssast_note', 'post_status' => 'any', 'author' => get_current_user_id(), 'numberposts' => 1, 'date_query' => array( array( 'after' => '1 minute ago' ) ) ) );
  if ( ! $id && $recent ) { delete_option( $lock ); wp_die( '每分钟最多提交一篇笔记' ); }
  if ( $id ) { clean_post_cache( $id ); }
  if ( $id && 'draft' !== get_post_status( $id ) ) { delete_option( $lock ); wp_die( '笔记已提交' ); }
  $result = wp_insert_post( wp_slash( array( 'ID' => $id, 'post_type' => 'dssast_note', 'post_status' => 'pending', 'post_author' => get_current_user_id(), 'post_title' => $title, 'post_content' => $content ) ), true );
  if ( is_wp_error( $result ) ) { delete_option( $lock ); wp_die( '保存失败，请重试' ); }
 } finally { delete_option( $lock ); }
 $return = isset( $_POST['dssast_return'] ) && is_string( $_POST['dssast_return'] ) ? wp_unslash( $_POST['dssast_return'] ) : home_url( '/' );
 wp_safe_redirect( add_query_arg( 'dssast_submitted', '1', remove_query_arg( 'dssast_edit_note', $return ) ), 303 ); exit;
} );
add_action( 'add_meta_boxes_dssast_note', function () {
    add_meta_box( 'dssast_review', '笔记审核', function ( $post ) {
        wp_nonce_field( 'dssast_review_' . $post->ID, 'dssast_review_nonce' );
        ?>
        <p class="description">可发布笔记，或填写审核意见并退回作者修改。</p>
        <label class="dssast-review-choice">
            <input type="checkbox" name="dssast_return_note" value="1"> 退回作者修改
        </label>
        <div class="dssast-fields">
            <div class="dssast-field dssast-field-wide">
                <label for="dssast_review_reason">审核意见</label>
                <textarea id="dssast_review_reason" name="dssast_review_reason" rows="4"><?php echo esc_textarea( get_post_meta( $post->ID, 'dssast_review_reason', true ) ); ?></textarea>
            </div>
        </div>
        <?php
    }, 'dssast_note' );
} );

function dssast_can_save_note_review( $id ) {
    return $id && ! wp_is_post_revision( $id ) && current_user_can( 'manage_options' ) && current_user_can( 'edit_post', $id )
        && ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        && isset( $_POST['dssast_review_nonce'] ) && is_string( $_POST['dssast_review_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dssast_review_nonce'] ) ), 'dssast_review_' . $id );
}

add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
    // Set draft before WordPress writes the post or fires status transition hooks.
    if ( 'dssast_note' === $data['post_type'] && isset( $_POST['dssast_return_note'] ) && '1' === $_POST['dssast_return_note']
        && dssast_can_save_note_review( (int) ( $postarr['ID'] ?? 0 ) ) ) {
        $data['post_status'] = 'draft';
        $data['post_date_gmt'] = '0000-00-00 00:00:00';
    }

    return $data;
}, 10, 2 );

add_action( 'save_post_dssast_note', 'dssast_review_note' );
function dssast_review_note( $id ) {
    if ( dssast_can_save_note_review( $id ) && isset( $_POST['dssast_review_reason'] ) && is_string( $_POST['dssast_review_reason'] ) ) {
        update_post_meta( $id, 'dssast_review_reason', sanitize_textarea_field( wp_unslash( $_POST['dssast_review_reason'] ) ) );
    }
}
