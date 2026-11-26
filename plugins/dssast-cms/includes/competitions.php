<?php
defined( 'ABSPATH' ) || exit;

/**
 * Business fields shown in the editor. Legacy signup meta
 * (dssast_registration_start/_end/_url) is kept in the database but no longer
 * edited here.
 */
function dssast_fields() {
 return array( 'deadline' => '报名截止', 'date' => '赛事开始', 'end_date' => '赛事结束', 'status' => '赛事状态' );
}

/** Manual overrides. Everything else is derived from the dates. */
function dssast_statuses() { return array( 'completed' => '已结束', 'cancelled' => '已取消' ); }

function dssast_state_labels() {
 return array(
  'open'     => '报名中',
  'closed'   => '报名结束',
  'ongoing'  => '进行中',
  'finished' => '已结束',
  'cancelled' => '已取消',
 );
}

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
 register_post_meta( 'dssast_competition', 'dssast_links', array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'auth_callback' => function () { return current_user_can( 'manage_options' ); } ) );
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

/** Push links for one competition: array of array( 'title' => string, 'url' => string ). */
function dssast_competition_links( $id ) {
    $stored = get_post_meta( $id, 'dssast_links', true );
    if ( ! is_string( $stored ) || '' === $stored ) { return array(); }
    $decoded = json_decode( $stored, true );
    if ( ! is_array( $decoded ) ) { return array(); }
    $links = array();
    foreach ( $decoded as $link ) {
        if ( ! is_array( $link ) ) { continue; }
        $url = isset( $link['url'] ) && is_string( $link['url'] ) ? esc_url_raw( $link['url'], array( 'http', 'https' ) ) : '';
        if ( '' === $url ) { continue; }
        $title = isset( $link['title'] ) && is_string( $link['title'] ) ? trim( $link['title'] ) : '';
        $links[] = array( 'title' => $title, 'url' => $url );
    }
    return $links;
}

/**
 * Current state of a competition. A manual value in dssast_status wins,
 * otherwise the dates decide. Dates are site-local Y-m-d strings.
 *
 * 比赛结束后 → 已结束；比赛进行中 → 进行中；报名截止前 → 报名中；
 * 报名截止后到开赛前 → 报名结束。
 */
function dssast_competition_state( $id ) {
    $labels = dssast_state_labels();
    $manual = get_post_meta( $id, 'dssast_status', true );
    if ( is_string( $manual ) && isset( dssast_statuses()[ $manual ] ) ) {
        return array( 'code' => 'cancelled' === $manual ? 'cancelled' : 'finished', 'label' => dssast_statuses()[ $manual ], 'manual' => true );
    }

    $read = function ( $key ) use ( $id ) {
        $value = get_post_meta( $id, $key, true );
        return is_string( $value ) ? trim( $value ) : '';
    };
    $deadline = $read( 'dssast_deadline' );
    $start = $read( 'dssast_date' );
    $end = $read( 'dssast_end_date' );
    if ( '' !== $start && ( '' === $end || $end < $start ) ) { $end = $start; }

    $today = current_time( 'Y-m-d' );

    if ( '' !== $end && $today > $end ) {
        $code = 'finished';
    } elseif ( '' !== $start && $today >= $start ) {
        $code = 'ongoing';
    } elseif ( '' !== $deadline && $today <= $deadline ) {
        $code = 'open';
    } elseif ( '' !== $deadline ) {
        $code = 'closed';
    } elseif ( '' !== $start ) {
        // No deadline on record: the event simply has not started yet.
        $code = 'closed';
    } else {
        $code = 'open';
    }

    return array( 'code' => $code, 'label' => $labels[ $code ], 'manual' => false );
}

function dssast_competition_field_control( $field, $value, $id = '' ) {
    $name = 'dssast_' . $field;
    $types = array( 'deadline' => 'date', 'date' => 'date', 'end_date' => 'date' );
    $id_attribute = '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '';
    if ( 'status' === $field ) : ?>
        <select name="<?php echo esc_attr( $name ); ?>"<?php echo $id_attribute; ?>>
            <option value="">自动（按日期）</option>
            <?php foreach ( dssast_statuses() as $code => $label ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $value, $code ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    <?php else : ?>
        <input type="<?php echo esc_attr( $types[$field] ); ?>" name="<?php echo esc_attr( $name ); ?>"<?php echo $id_attribute; ?> value="<?php echo esc_attr( $value ); ?>"<?php echo 'deadline' === $field ? ' required aria-required="true"' : ''; ?>>
    <?php endif;
}

/** One editable link row. Kept in a function so the JS template can reuse it. */
function dssast_link_row( $title = '', $url = '' ) {
    ?>
    <div class="dssast-link-row">
        <input type="text" name="dssast_link_title[]" value="<?php echo esc_attr( $title ); ?>" placeholder="标题（可留空，留空显示网址）">
        <input type="url" name="dssast_link_url[]" value="<?php echo esc_attr( $url ); ?>" placeholder="粘贴链接，如 https://mp.weixin.qq.com/s/…">
        <button type="button" class="button-link" data-dssast-move="up" aria-label="上移">↑</button>
        <button type="button" class="button-link" data-dssast-move="down" aria-label="下移">↓</button>
        <button type="button" class="button-link dssast-link-remove" data-dssast-remove aria-label="删除该链接">删除</button>
    </div>
    <?php
}

function dssast_details_box( $post ) {
    wp_nonce_field( 'dssast_save_' . $post->ID, 'dssast_nonce' );
    $values = dssast_competition_values( $post->ID );
    $links = dssast_competition_links( $post->ID );
    ?>
    <p class="description">时间采用站点时区 <?php echo esc_html( wp_timezone_string() ); ?>。<strong>报名截止为必填</strong>，赛事开始/结束可只填开始。</p>
    <input type="hidden" name="dssast_full_edit" value="1">
    <div class="dssast-fields">
        <?php foreach ( dssast_fields() as $field => $label ) :
            $key = 'dssast_' . $field;
            ?>
            <div class="dssast-field">
                <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
                <?php dssast_competition_field_control( $field, $values[$key], $key ); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="description">赛事时间会显示在详情页顶部；状态留空时按「报名截止 / 开赛 / 结束」自动推导。封面请在右侧「特色图片」上传，建议 1600×600（16:6），会作为详情页头图。</p>

    <h3 class="dssast-links-title">相关链接</h3>
    <p class="description">直接粘贴链接即可（如公众号推送地址），标题可以留空；这些链接只显示在详情页，卡片列表不展示。</p>
    <input type="hidden" name="dssast_links_present" value="1">
    <div class="dssast-links" data-dssast-links>
        <?php
        if ( $links ) {
            foreach ( $links as $link ) { dssast_link_row( $link['title'], $link['url'] ); }
        }
        ?>
    </div>
    <p><button type="button" class="button" data-dssast-add>添加链接</button></p>
    <template data-dssast-template>
        <?php dssast_link_row(); ?>
    </template>
    <?php
}

function dssast_validate_fields( $input ) {
 $clean = array();
 foreach ( dssast_fields() as $field => $label ) {
  $key = 'dssast_' . $field;
  $value = isset( $input[$key] ) && is_string( $input[$key] ) ? trim( $input[$key] ) : '';
  if ( '' !== $value ) {
   if ( 'status' === $field ) {
    if ( ! isset( dssast_statuses()[$value] ) ) { return false; }
   } else {
    $date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
    if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) { return false; }
   }
  }
  $clean[$key] = $value;
 }
 if ( '' !== $clean['dssast_date'] && '' !== $clean['dssast_end_date'] && $clean['dssast_end_date'] < $clean['dssast_date'] ) { return false; }
 // The deadline is mandatory in the editor; quick edit never posts it as empty.
 if ( ! empty( $input['dssast_full_edit'] ) && '' === $clean['dssast_deadline'] ) { return false; }
 return $clean;
}

/** Validates the link rows. Returns false on a malformed row, array otherwise. */
function dssast_validate_links( $input ) {
    $titles = isset( $input['dssast_link_title'] ) && is_array( $input['dssast_link_title'] ) ? $input['dssast_link_title'] : array();
    $urls = isset( $input['dssast_link_url'] ) && is_array( $input['dssast_link_url'] ) ? $input['dssast_link_url'] : array();
    $links = array();
    foreach ( $urls as $index => $url ) {
        $url = is_string( $url ) ? trim( $url ) : '';
        $title = isset( $titles[$index] ) && is_string( $titles[$index] ) ? trim( $titles[$index] ) : '';
        if ( '' === $url && '' === $title ) { continue; }
        if ( '' === $url ) { return false; }
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) { return false; }
        $links[] = array( 'title' => $title, 'url' => esc_url_raw( $url, array( 'http', 'https' ) ) );
        if ( count( $links ) > 10 ) { return false; }
    }
    return $links;
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
        wp_die( '未保存赛事。请检查赛事日期与结束日期的先后顺序。' );
    }
}, 0 );

add_action( 'save_post_dssast_competition', function ( $id ) {
 if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $id ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) ) { return; }
 if ( ! dssast_competition_nonce_valid( $id ) ) { return; }
 $quick_edit = wp_doing_ajax() && 'inline-save' === ( $_POST['action'] ?? '' );
 $clean = dssast_competition_submitted_fields( $id, $quick_edit );
 if ( false === $clean ) { set_transient( 'dssast_error_' . get_current_user_id(), 1, 120 ); return; }
 foreach ( $clean as $key => $value ) { if ( '' === $value ) { delete_post_meta( $id, $key ); } else { update_post_meta( $id, $key, $value ); } }

 // The quick edit fieldset has no link rows; leave stored links untouched there.
 if ( ! $quick_edit && isset( $_POST['dssast_links_present'] ) ) {
     $links = dssast_validate_links( wp_unslash( $_POST ) );
     if ( false === $links ) {
         set_transient( 'dssast_error_' . get_current_user_id(), 1, 120 );
     } elseif ( $links ) {
         update_post_meta( $id, 'dssast_links', wp_slash( wp_json_encode( $links ) ) );
     } else {
         delete_post_meta( $id, 'dssast_links' );
     }
 }
} );

add_action( 'admin_notices', function () {
 $key = 'dssast_error_' . get_current_user_id();
 if ( get_transient( $key ) ) { delete_transient( $key ); echo '<div class="notice notice-error"><p>赛事业务字段未保存：请确认「报名截止」已填写、日期先后顺序正确，相关链接都是完整的 HTTP(S) 地址。</p></div>'; }
} );

/** Starter content so a new competition opens with the usual section headings. */
add_filter( 'default_content', function ( $content, $post ) {
    if ( ! $post instanceof WP_Post || 'dssast_competition' !== $post->post_type ) { return $content; }
    $sections = array(
        array( '赛事简介', '一两句话说清这是什么比赛、由谁主办、面向谁。' ),
        array( '参赛条件', '参赛对象、组队要求（每队人数、指导老师）、报名方式与限制。' ),
        array( '赛道设置', '如果有多个赛道，分条写明每个赛道的题目方向与作品形式。' ),
        array( '赛程安排', '报名截止、初赛、决赛、颁奖各阶段的时间节点与地点。' ),
        array( '奖项设置', '奖项等级、名额与奖品。' ),
        array( '大赛组织', '指导单位 / 主办单位 / 承办单位 / 协办单位。' ),
    );
    $html = '';
    foreach ( $sections as $section ) {
        $html .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . $section[0] . "</h2>\n<!-- /wp:heading -->\n\n";
        $html .= "<!-- wp:paragraph -->\n<p>" . $section[1] . "</p>\n<!-- /wp:paragraph -->\n\n";
    }
    return $html;
}, 10, 2 );

/**
 * Published competitions, ongoing first, then upcoming (soonest first),
 * then finished (most recent first).
 */
function dssast_get_competitions( $args = array() ) {
    $args = wp_parse_args( $args, array( 'limit' => -1 ) );
    $posts = get_posts( array(
        'post_type' => 'dssast_competition',
        'post_status' => 'publish',
        'has_password' => false,
        'numberposts' => $args['limit'] > 0 ? (int) $args['limit'] : -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ) );

    $rank = array( 'open' => 0, 'ongoing' => 1, 'closed' => 2, 'finished' => 3, 'cancelled' => 4 );
    usort( $posts, function ( $a, $b ) use ( $rank ) {
        $state_a = dssast_competition_state( $a->ID );
        $state_b = dssast_competition_state( $b->ID );
        $order_a = $rank[ $state_a['code'] ] ?? 9;
        $order_b = $rank[ $state_b['code'] ] ?? 9;
        if ( $order_a !== $order_b ) { return $order_a - $order_b; }

        $date_a = (string) get_post_meta( $a->ID, 'dssast_date', true );
        $date_b = (string) get_post_meta( $b->ID, 'dssast_date', true );
        if ( '' === $date_a ) { $date_a = '9999-12-31'; }
        if ( '' === $date_b ) { $date_b = '9999-12-31'; }
        if ( $date_a === $date_b ) { return $b->ID - $a->ID; }
        // Upcoming/ongoing: closest first. Finished: most recent first.
        return $order_a <= 1 ? strcmp( $date_a, $date_b ) : strcmp( $date_b, $date_a );
    } );

    return $posts;
}

/** Card list markup shared by the page templates and the shortcode. */
function dssast_competition_cards( $args = array() ) {
    $posts = dssast_get_competitions( $args );
    if ( ! $posts ) {
        return '<p class="events-page__empty">暂无赛事，敬请期待。</p>';
    }

    ob_start();
    echo '<div class="events-list">';
    foreach ( $posts as $post ) {
        get_template_part( 'template-parts/event-card', null, array( 'post' => $post ) );
    }
    echo '</div>';
    return ob_get_clean();
}

add_shortcode( 'dssast_competitions', function ( $atts ) {
    $atts = shortcode_atts( array( 'limit' => 20 ), $atts, 'dssast_competitions' );
    $limit = absint( $atts['limit'] );
    return dssast_competition_cards( array( 'limit' => $limit > 0 ? min( 50, $limit ) : -1 ) );
} );
