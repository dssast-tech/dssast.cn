<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
    $dashboard = add_menu_page(
        '内容管理',
        '内容管理',
        'manage_options',
        'dssast-management',
        'dssast_management_page',
        'dashicons-welcome-write-blog',
        26
    );
    add_action( 'load-' . $dashboard, 'dssast_enqueue_admin_style' );

    $views = dssast_management_views();
    $notes_menu = 'edit.php?post_type=dssast_note';
    remove_submenu_page( $notes_menu, $notes_menu );
    foreach ( array( 'notes', 'pending' ) as $position => $key ) {
        $view = $views[ $key ];
        $screen = add_submenu_page(
            $notes_menu,
            $view['title'],
            $view['title'],
            'manage_options',
            'dssast-view-' . $key,
            function () use ( $key ) { dssast_management_list( $key ); },
            $position
        );
        if ( $screen ) {
            add_action( 'load-' . $screen, function () use ( $key ) {
                dssast_enqueue_admin_style();
                add_filter( 'submenu_file', function () use ( $key ) { return 'dssast-view-' . $key; } );
            } );
        }
    }

    $editorial = array(
        'news' => array( '新闻列表', '添加新闻', 'dashicons-media-document', 29 ),
        'announcements' => array( '公告列表', '添加公告', 'dashicons-megaphone', 30 ),
    );
    foreach ( $editorial as $key => list( $list_label, $add_label, $icon, $position ) ) {
        $view = $views[ $key ];
        $slug = 'dssast-view-' . $key;
        $screen = add_menu_page(
            $view['title'],
            $view['title'],
            'manage_options',
            $slug,
            function () use ( $key ) { dssast_management_list( $key ); },
            $icon,
            $position
        );
        add_action( 'load-' . $screen, 'dssast_enqueue_admin_style' );
        add_submenu_page( $slug, $view['title'], $list_label, 'manage_options', $slug );
        $composer = dssast_new_content_page( $view );
        if ( $composer ) {
            add_submenu_page( $slug, $add_label, $add_label, 'manage_options', $composer );
        }
    }
    add_submenu_page( 'dssast-view-news', '秀米同步', '秀米同步', 'manage_options', 'dssast-xiumi-news', 'dssast_xiumi_page' );
} );

function dssast_enqueue_admin_style() {
    wp_enqueue_style( 'dssast-admin', plugins_url( 'assets/admin.css', DSSAST_CMS_FILE ), array( 'dashicons' ), DSSAST_CMS_VERSION );
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    $screen = get_current_screen();
    if ( in_array( $hook, array( 'edit.php', 'post.php', 'post-new.php' ), true ) &&
        $screen && in_array( $screen->post_type, array( 'dssast_competition', 'dssast_note' ), true ) ) {
        dssast_enqueue_admin_style();
    }
    if ( 'edit.php' === $hook && $screen && 'dssast_competition' === $screen->post_type ) {
        wp_enqueue_script(
            'dssast-competition-quick-edit',
            plugins_url( 'assets/competition-quick-edit.js', DSSAST_CMS_FILE ),
            array( 'inline-edit-post' ),
            DSSAST_CMS_VERSION,
            true
        );
    }
} );

function dssast_management_views() {
    return array(
        'notes' => array( 'title' => '全部笔记', 'type' => 'dssast_note', 'category' => '' ),
        'pending' => array( 'title' => '待审核笔记', 'type' => 'dssast_note', 'category' => '' ),
        'news' => array( 'title' => '新闻管理', 'type' => 'post', 'category' => 'dssast-news' ),
        'announcements' => array( 'title' => '公告管理', 'type' => 'post', 'category' => 'dssast-announcement' ),
    );
}

function dssast_view_url( $key ) {
    return add_query_arg( 'page', 'dssast-view-' . $key, admin_url( 'admin.php' ) );
}

function dssast_new_content_page( $view ) {
    if ( ! $view['category'] ) {
        return add_query_arg( 'post_type', $view['type'], 'post-new.php' );
    }

    $term = dssast_editorial_category( $view['category'] );
    return $term ? add_query_arg( 'dssast_category', $term->term_id, 'post-new.php' ) : '';
}

function dssast_new_content_url( $view ) {
    $page = dssast_new_content_page( $view );
    return $page ? admin_url( $page ) : '';
}

// Select the category on the actual auto-draft read by both WordPress editors.
add_action( 'load-post-new.php', function () {
    $screen = get_current_screen();
    if ( ! $screen || 'post' !== $screen->post_type || ! current_user_can( 'manage_options' ) ||
        ! isset( $_GET['dssast_category'] ) || ! is_string( $_GET['dssast_category'] ) ) {
        return;
    }

    $term = get_term( absint( $_GET['dssast_category'] ), 'category' );
    $news = dssast_editorial_category( 'dssast-news' );
    $announcement = dssast_editorial_category( 'dssast-announcement' );
    if ( ! $term || is_wp_error( $term ) || ! in_array( $term->term_id, array( $news->term_id ?? 0, $announcement->term_id ?? 0 ), true ) ) {
        return;
    }

    $view = $term->term_id === ( $news->term_id ?? 0 ) ? 'news' : 'announcements';
    add_filter( 'parent_file', function () use ( $view ) { return 'dssast-view-' . $view; } );
    add_filter( 'submenu_file', function () use ( $term ) {
        return add_query_arg( 'dssast_category', $term->term_id, 'post-new.php' );
    } );

    add_action( 'wp_after_insert_post', function ( $id, $post, $update ) use ( $term ) {
        if ( ! $update && 'post' === $post->post_type && 'auto-draft' === $post->post_status ) {
            wp_set_post_categories( $id, array( (int) $term->term_id ) );
        }
    }, 10, 3 );
} );

function dssast_management_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $views = dssast_management_views();
    $pending = (int) ( wp_count_posts( 'dssast_note' )->pending ?? 0 );
    $events = (int) ( wp_count_posts( 'dssast_competition' )->publish ?? 0 );
    $event_list = add_query_arg( 'post_type', 'dssast_competition', admin_url( 'edit.php' ) );
    $new_event = add_query_arg( 'post_type', 'dssast_competition', admin_url( 'post-new.php' ) );
    $stats = array(
        array( '待审核笔记', $pending, dssast_view_url( 'pending' ) ),
        array( '已发布赛事', $events, add_query_arg( 'post_status', 'publish', $event_list ) ),
    );
    $cards = array(
        'events' => array(
            'title' => '赛事管理',
            'icon' => 'awards',
            'description' => '维护赛事介绍、进度、报名时间和链接。',
            'primary' => array( '赛事列表', $event_list ),
            'actions' => array(
                array( '添加赛事', $new_event ),
                array( '赛事分类', admin_url( 'edit-tags.php?taxonomy=dssast_competition_category&post_type=dssast_competition' ) ),
            ),
        ),
        'notes' => array(
            'title' => '学习笔记',
            'icon' => 'book-alt',
            'description' => '审核学生投稿，填写反馈并发布笔记。',
            'primary' => array( '待审核笔记', dssast_view_url( 'pending' ) ),
            'actions' => array(
                array( '全部笔记', dssast_view_url( 'notes' ) ),
                array( '添加笔记', dssast_new_content_url( $views['notes'] ) ),
            ),
        ),
        'news' => array(
            'title' => '新闻管理',
            'icon' => 'media-document',
            'description' => '编辑活动报道和秀米导入的新闻稿件。',
            'primary' => array( '新闻列表', dssast_view_url( 'news' ) ),
            'actions' => array( array( '添加新闻', dssast_new_content_url( $views['news'] ) ) ),
        ),
        'announcements' => array(
            'title' => '公告管理',
            'icon' => 'megaphone',
            'description' => '发布对外通知和重要提醒。',
            'primary' => array( '公告列表', dssast_view_url( 'announcements' ) ),
            'actions' => array( array( '添加公告', dssast_new_content_url( $views['announcements'] ) ) ),
        ),
    );
    ?>
    <div class="wrap dssast-admin dssast-workbench">
        <hr class="wp-header-end">
        <section class="dssast-overview" aria-labelledby="dssast-overview-title">
            <div class="dssast-overview-title">
                <p>笃实书院学生科协</p>
                <h1 id="dssast-overview-title">内容管理工作台</h1>
            </div>
            <div class="dssast-overview-stats">
                <?php foreach ( $stats as list( $label, $count, $url ) ) : ?>
                    <a class="dssast-stat" href="<?php echo esc_url( $url ); ?>">
                        <span><?php echo esc_html( $label ); ?></span>
                        <strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
                        <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <div class="dssast-card-grid">
            <?php foreach ( $cards as $key => $card ) :
                $heading_id = 'dssast-card-' . $key;
                list( $label, $url ) = $card['primary'];
                ?>
                <section class="dssast-card" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
                    <span class="dssast-icon dashicons dashicons-<?php echo esc_attr( $card['icon'] ); ?>" aria-hidden="true"></span>
                    <div class="dssast-card-heading">
                        <h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $card['title'] ); ?></h2>
                        <p><?php echo esc_html( $card['description'] ); ?></p>
                    </div>
                    <div class="dssast-card-actions">
                        <a class="dssast-card-primary" href="<?php echo esc_url( $url ); ?>">
                            <?php echo esc_html( $label ); ?><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                        <?php foreach ( $card['actions'] as list( $label, $url ) ) : ?>
                            <?php if ( $url ) : ?>
                                <a class="dssast-text-link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if ( 'news' === $key ) : ?>
                        <a class="dssast-sync" href="<?php echo esc_url( admin_url( 'admin.php?page=dssast-xiumi-news' ) ); ?>">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <span><strong>秀米同步</strong><small>导入稿件进入新闻草稿</small></span>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </a>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function dssast_management_list( $key ) {
    $views = dssast_management_views();
    if ( ! current_user_can( 'manage_options' ) || ! isset( $views[ $key ] ) ) {
        return;
    }

    $view = $views[ $key ];
    $pending = 'pending' === $key;
    $page = isset( $_GET['paged'] ) && is_string( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $search = isset( $_GET['dssast_search'] ) && is_string( $_GET['dssast_search'] )
        ? sanitize_text_field( wp_unslash( $_GET['dssast_search'] ) ) : '';
    $args = array(
        'post_type' => $view['type'],
        'post_status' => $pending ? 'pending' : array( 'publish', 'pending', 'draft', 'future', 'private' ),
        'posts_per_page' => 20,
        'paged' => $page,
        's' => $search,
        'ignore_sticky_posts' => true,
        'orderby' => $pending ? array( 'modified' => 'ASC', 'ID' => 'ASC' ) : array( 'date' => 'DESC', 'ID' => 'DESC' ),
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
    );
    if ( $view['category'] ) {
        $term = dssast_editorial_category( $view['category'] );
        if ( $term ) {
            $args['cat'] = (int) $term->term_id;
        } else {
            $args['post__in'] = array( 0 );
        }
    }

    $query = new WP_Query( $args );
    update_post_author_caches( $query->posts );
    $add_url = $pending ? '' : dssast_new_content_url( $view );
    $add_label = array( 'notes' => '添加笔记', 'news' => '添加新闻', 'announcements' => '添加公告' );
    $headings = array( 'title' => '标题', 'author' => '作者', 'status' => '状态', 'date' => $pending ? '更新时间' : '日期', 'actions' => '操作' );
    $status_labels = array( 'publish' => '已发布', 'pending' => '待审核', 'draft' => '草稿', 'future' => '已定时', 'private' => '私密' );
    ?>
    <div class="wrap dssast-admin">
        <h1 class="wp-heading-inline"><?php echo esc_html( $view['title'] ); ?></h1>
        <hr class="wp-header-end">
        <div class="dssast-list-toolbar">
            <a class="dssast-text-link" href="<?php echo esc_url( admin_url( 'admin.php?page=dssast-management' ) ); ?>">
                <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>内容管理
            </a>
            <form class="dssast-search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
                <input type="hidden" name="page" value="<?php echo esc_attr( 'dssast-view-' . $key ); ?>">
                <label class="screen-reader-text" for="dssast-list-search">搜索标题或正文</label>
                <input type="search" id="dssast-list-search" name="dssast_search" value="<?php echo esc_attr( $search ); ?>" placeholder="搜索标题或正文">
                <button type="submit" class="button">搜索</button>
                <?php if ( '' !== $search ) : ?>
                    <a class="dssast-text-link" href="<?php echo esc_url( dssast_view_url( $key ) ); ?>">清除搜索</a>
                <?php endif; ?>
            </form>
            <?php if ( $add_url ) : ?>
                <a class="button button-primary" href="<?php echo esc_url( $add_url ); ?>"><?php echo esc_html( $add_label[ $key ] ); ?></a>
            <?php endif; ?>
        </div>
        <?php if ( 'dssast_note' === $view['type'] ) : ?>
            <nav class="dssast-tabs" aria-label="笔记筛选">
                <?php foreach ( array( 'notes', 'pending' ) as $tab ) : ?>
                    <a href="<?php echo esc_url( dssast_view_url( $tab ) ); ?>" <?php if ( $tab === $key ) { echo 'aria-current="page"'; } ?>>
                        <?php echo esc_html( $views[ $tab ]['title'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <div class="dssast-list-panel">
            <?php if ( $query->posts ) : ?>
                <div class="dssast-table-scroll" role="region" aria-label="<?php echo esc_attr( $view['title'] ); ?>" tabindex="0">
                    <table class="dssast-table">
                        <thead><tr>
                            <?php foreach ( $headings as $column => $label ) : ?>
                                <th scope="col" class="dssast-col-<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $label ); ?></th>
                            <?php endforeach; ?>
                        </tr></thead>
                        <tbody>
                            <?php foreach ( $query->posts as $item ) :
                                $edit_url = get_edit_post_link( $item->ID, 'raw' );
                                $title = $item->post_title ?: '（无标题）';
                                $timestamp = get_post_datetime( $item, $pending ? 'modified' : 'date' );
                                ?>
                                <tr>
                                    <th scope="row"><?php echo esc_html( $title ); ?></th>
                                    <td><?php echo esc_html( get_the_author_meta( 'display_name', $item->post_author ) ?: '—' ); ?></td>
                                    <td><?php dssast_state_badge( $item->post_status, $status_labels[ $item->post_status ] ?? $item->post_status ); ?></td>
                                    <td><?php if ( $timestamp ) : ?>
                                        <time datetime="<?php echo esc_attr( $timestamp->format( DATE_W3C ) ); ?>"><?php echo esc_html( $timestamp->format( 'Y-m-d H:i' ) ); ?></time>
                                    <?php else : echo '—'; endif; ?></td>
                                    <td><?php if ( $edit_url ) : ?>
                                        <a class="dssast-text-link" href="<?php echo esc_url( $edit_url ); ?>" aria-label="<?php echo esc_attr( ( $pending ? '审核' : '编辑' ) . '《' . $title . '》' ); ?>"><?php echo $pending ? '审核' : '编辑'; ?></a>
                                    <?php endif; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="dssast-empty">
                    <span class="dssast-icon dashicons dashicons-<?php echo $pending ? 'yes-alt' : 'media-document'; ?>" aria-hidden="true"></span>
                    <p><?php echo '' !== $search ? '没有匹配的内容' : ( $pending ? '暂无待审核笔记' : '本栏目暂无内容' ); ?></p>
                </div>
            <?php endif; ?>
            <?php if ( $query->max_num_pages > 1 || $query->posts ) : ?>
                <div class="dssast-list-footer">
                    <span><?php echo esc_html( '共 ' . number_format_i18n( $query->found_posts ) . ' 条' ); ?></span>
                    <?php if ( $query->max_num_pages > 1 ) : ?>
                        <nav class="dssast-pagination" aria-label="列表分页">
                            <?php echo wp_kses_post( paginate_links( array(
                                'base' => dssast_view_url( $key ) . '%_%',
                                'format' => '&paged=%#%',
                                'current' => $page,
                                'total' => $query->max_num_pages,
                                'add_args' => '' === $search ? array() : array( 'dssast_search' => $search ),
                                'prev_text' => '上一页',
                                'next_text' => '下一页',
                            ) ) ); ?>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function dssast_state_badge( $state, $label ) {
    echo '<span class="dssast-state dssast-state-' . esc_attr( $state ) . '">' . esc_html( $label ) . '</span>';
}

add_filter( 'manage_dssast_competition_posts_columns', function ( $columns ) {
    $date = $columns['date'] ?? null;
    unset( $columns['date'] );
    $columns['dssast_status'] = '赛事状态';
    $columns['dssast_date'] = '赛事日期';
    if ( null !== $date ) {
        $columns['date'] = $date;
    }
    return $columns;
} );

add_action( 'manage_dssast_competition_posts_custom_column', function ( $column, $id ) {
    if ( 'dssast_status' === $column ) {
        $state = get_post_meta( $id, 'dssast_status', true );
        $labels = dssast_statuses();
        if ( is_string( $state ) && isset( $labels[ $state ] ) ) {
            dssast_state_badge( $state, $labels[ $state ] );
        } else {
            echo '未设置';
        }
    } elseif ( 'dssast_date' === $column ) {
        $date = get_post_meta( $id, 'dssast_date', true );
        echo esc_html( is_string( $date ) && '' !== $date ? $date : '—' );
    }
}, 10, 2 );

add_action( 'quick_edit_custom_box', function ( $column, $post_type ) {
    if ( 'dssast_status' !== $column || 'dssast_competition' !== $post_type || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <fieldset class="dssast-competition-quick-edit" disabled hidden>
        <legend class="inline-edit-legend">赛事信息</legend>
        <p class="description">时间采用站点时区 <?php echo esc_html( wp_timezone_string() ); ?>。</p>
        <input type="hidden" name="dssast_nonce">
        <div class="dssast-fields">
            <?php foreach ( dssast_fields() as $field => $label ) : ?>
                <label class="dssast-field<?php echo 'registration_url' === $field ? ' dssast-field-wide' : ''; ?>">
                    <span><?php echo esc_html( $label ); ?></span>
                    <?php dssast_competition_field_control( $field, '' ); ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}, 10, 2 );

add_action( 'add_inline_data', function ( $post ) {
    if ( 'dssast_competition' !== $post->post_type || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $values = dssast_competition_values( $post->ID );
    $values['dssast_nonce'] = wp_create_nonce( 'dssast_save_' . $post->ID );
    $json = wp_json_encode( $values, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
    echo '<div class="dssast-quick-edit-data" data-fields="' . esc_attr( $json ) . '"></div>';
} );
