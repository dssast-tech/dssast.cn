<?php
defined( 'ABSPATH' ) || exit;

function dssast_fields() {
 return array( 'status' => '赛事状态', 'date' => '赛事日期', 'registration_start' => '报名开始', 'registration_end' => '报名截止', 'registration_url' => '报名链接' );
}
function dssast_statuses() { return array( 'planned' => '筹备中', 'open' => '报名中', 'closed' => '报名结束', 'completed' => '已结束', 'cancelled' => '已取消' ); }
function dssast_register_competitions() {
 register_post_type( 'dssast_competition', array(
  'labels' => array( 'name' => '赛事', 'singular_name' => '赛事', 'menu_name' => '赛事管理', 'all_items' => '赛事列表', 'add_new_item' => '添加赛事', 'edit_item' => '编辑赛事' ),
  'public' => true, 'show_in_rest' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'competitions' ),
  'menu_icon' => 'dashicons-awards', 'menu_position' => 27, 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
  'capabilities' => array( 'edit_posts' => 'manage_options', 'edit_others_posts' => 'manage_options', 'publish_posts' => 'manage_options', 'read_private_posts' => 'manage_options', 'delete_posts' => 'manage_options', 'delete_private_posts' => 'manage_options', 'delete_published_posts' => 'manage_options', 'delete_others_posts' => 'manage_options', 'edit_private_posts' => 'manage_options', 'edit_published_posts' => 'manage_options', 'create_posts' => 'manage_options' ), 'map_meta_cap' => true,
 ) );
 register_taxonomy( 'dssast_competition_category', 'dssast_competition', array( 'label' => '赛事分类', 'hierarchical' => true, 'show_in_rest' => true, 'capabilities' => array( 'manage_terms' => 'manage_options', 'edit_terms' => 'manage_options', 'delete_terms' => 'manage_options', 'assign_terms' => 'manage_options' ) ) );
 foreach ( dssast_fields() as $field => $label ) {
  register_post_meta( 'dssast_competition', 'dssast_' . $field, array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'auth_callback' => function () { return current_user_can( 'manage_options' ); } ) );
 }
}
add_action( 'init', 'dssast_register_competitions' );
add_action( 'add_meta_boxes_dssast_competition', function () { add_meta_box( 'dssast_details', '赛事信息', 'dssast_details_box', 'dssast_competition' ); } );
function dssast_competition_values( $id ) {
    $values = array();
    foreach ( array_keys( dssast_fields() ) as $field ) {
        $key = 'dssast_' . $field;
        $stored = get_post_meta( $id, $key, true );
        $values[$key] = is_string( $stored ) ? $stored : '';
    }
    return $values;
}

function dssast_competition_field_control( $field, $value, $id = '' ) {
    $name = 'dssast_' . $field;
    $types = array( 'date' => 'date', 'registration_start' => 'datetime-local', 'registration_end' => 'datetime-local', 'registration_url' => 'url' );
    $id_attribute = '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '';
    if ( 'status' === $field ) : ?>
        <select name="<?php echo esc_attr( $name ); ?>"<?php echo $id_attribute; ?>>
            <option value="">未设置</option>
            <?php foreach ( dssast_statuses() as $code => $label ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $value, $code ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    <?php else : ?>
        <input type="<?php echo esc_attr( $types[$field] ); ?>" name="<?php echo esc_attr( $name ); ?>"<?php echo $id_attribute; ?> value="<?php echo esc_attr( $value ); ?>">
    <?php endif;
}

function dssast_details_box( $post ) {
    wp_nonce_field( 'dssast_save_' . $post->ID, 'dssast_nonce' );
    $values = dssast_competition_values( $post->ID );
    ?>
    <p class="description">时间采用站点时区 <?php echo esc_html( wp_timezone_string() ); ?>。所有字段均可留空，赛事状态由管理员更新。</p>
    <div class="dssast-fields">
        <?php foreach ( dssast_fields() as $field => $label ) :
            $key = 'dssast_' . $field;
            ?>
            <div class="dssast-field<?php echo 'registration_url' === $field ? ' dssast-field-wide' : ''; ?>">
                <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
                <?php dssast_competition_field_control( $field, $values[$key], $key ); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
function dssast_validate_fields( $input ) {
 $clean = array();
 foreach ( dssast_fields() as $field => $label ) {
  $key = 'dssast_' . $field;
  if ( ! isset( $input[$key] ) || ! is_string( $input[$key] ) ) { return false; }
  $value = trim( $input[$key] );
  if ( '' !== $value ) {
   if ( 'status' === $field ) { if ( ! isset( dssast_statuses()[$value] ) ) { return false; } }
   elseif ( 'registration_url' === $field ) {
    if ( ! filter_var( $value, FILTER_VALIDATE_URL ) || ! in_array( strtolower( (string) wp_parse_url( $value, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) { return false; }
    $value = esc_url_raw( $value, array( 'http', 'https' ) );
   } else {
    $format = 'date' === $field ? 'Y-m-d' : 'Y-m-d\TH:i';
    $date = DateTimeImmutable::createFromFormat( '!' . $format, $value, wp_timezone() );
    if ( ! $date || $date->format( $format ) !== $value ) { return false; }
   }
  }
  $clean[$key] = $value;
 }
 if ( $clean['dssast_registration_start'] && $clean['dssast_registration_end'] && $clean['dssast_registration_start'] > $clean['dssast_registration_end'] ) { return false; }
 return $clean;
}

function dssast_competition_nonce_valid( $id ) {
    return isset( $_POST['dssast_nonce'] ) && is_string( $_POST['dssast_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dssast_nonce'] ) ), 'dssast_save_' . $id );
}

function dssast_competition_submitted_fields( $id, $quick_edit ) {
    $input = wp_unslash( $_POST );
    if ( $quick_edit ) {
        // Validate the date range against fields that were left unchanged.
        $input += dssast_competition_values( $id );
    }
    $fields = dssast_validate_fields( $input );
    return false === $fields ? false : array_intersect_key( $fields, $_POST );
}

// Validate before WordPress saves the title and other core fields.
add_action( 'wp_ajax_inline-save', function () {
    if ( ! isset( $_POST['dssast_nonce'], $_POST['post_ID'] ) || ! is_string( $_POST['post_ID'] ) ) {
        return;
    }
    $id = (int) $_POST['post_ID'];
    if ( ! $id || 'dssast_competition' !== get_post_type( $id ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) ) {
        return;
    }

    check_ajax_referer( 'inlineeditnonce', '_inline_edit' );
    if ( ! dssast_competition_nonce_valid( $id ) ) {
        wp_die( '快速编辑已过期，请刷新页面后重试。' );
    }
    if ( false === dssast_competition_submitted_fields( $id, true ) ) {
        wp_die( '未保存赛事。请检查赛事状态、日期、报名时间顺序和 HTTP(S) 链接。' );
    }
}, 0 );

add_action( 'save_post_dssast_competition', function ( $id ) {
 if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $id ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) ) { return; }
 if ( ! dssast_competition_nonce_valid( $id ) ) { return; }
 $quick_edit = wp_doing_ajax() && 'inline-save' === ( $_POST['action'] ?? '' );
 $clean = dssast_competition_submitted_fields( $id, $quick_edit );
 if ( false === $clean ) { set_transient( 'dssast_error_' . get_current_user_id(), 1, 120 ); return; }
 foreach ( $clean as $key => $value ) { if ( '' === $value ) { delete_post_meta( $id, $key ); } else { update_post_meta( $id, $key, $value ); } }
} );
add_action( 'admin_notices', function () {
 $key = 'dssast_error_' . get_current_user_id();
 if ( get_transient( $key ) ) { delete_transient( $key ); echo '<div class="notice notice-error"><p>赛事业务字段未保存：请检查日期、报名时间顺序及 HTTP(S) 链接。原有业务字段已保留，标题和正文可能已保存。</p></div>'; }
} );
add_shortcode( 'dssast_competitions', function ( $atts ) {
 $atts = shortcode_atts( array( 'limit' => 5 ), $atts, 'dssast_competitions' );
 $query = new WP_Query( array( 'post_type' => 'dssast_competition', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => max( 1, min( 50, absint( $atts['limit'] ) ) ), 'no_found_rows' => true ) );
 $html = '<div class="dssast-competitions">';
 foreach ( $query->posts as $post ) {
  $html .= '<article><h2><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></h2>';
  $html .= get_the_post_thumbnail( $post, 'medium' );
  $html .= '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: strip_shortcodes( $post->post_content ) ), 40 ) ) . '</p>';
  foreach ( dssast_fields() as $field => $label ) {
   $value = get_post_meta( $post->ID, 'dssast_' . $field, true );
   if ( ! is_string( $value ) || '' === $value ) { continue; }
   if ( 'registration_url' === $field ) { $html .= '<p><a class="dssast-registration" rel="nofollow" href="' . esc_url( $value, array( 'http', 'https' ) ) . '">报名链接</a></p>'; }
   else { $value = 'status' === $field ? ( dssast_statuses()[$value] ?? '未设置' ) : str_replace( 'T', ' ', $value ); $html .= '<p>' . esc_html( $label . '：' . $value ) . '</p>'; }
  }
  $html .= '</article>';
 }
 return $html . ( $query->posts ? '' : '<p>暂无赛事。</p>' ) . '</div>';
} );
