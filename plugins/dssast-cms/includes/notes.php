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
// Existing WordPress pages use this shortcode for the submission contact.
add_shortcode( 'dssast_note_submit', function () {
    $email = 'zhuyf24@mails.tsinghua.edu.cn';
    return sprintf( '<p class="submission-guide__email">投稿邮箱 <a href="%s">%s</a></p>',
        esc_url( 'mailto:' . $email ), esc_html( $email ) );
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
