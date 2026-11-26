<?php
/**
 * One competition card on the events list.
 *
 * @var array $args { post: WP_Post }
 */

$post = $args['post'] ?? get_post();
if ( ! $post instanceof WP_Post || ! function_exists( 'dssast_competition_state' ) ) {
    return;
}

$state = dssast_competition_state( $post->ID );
$links = dssast_competition_links( $post->ID );
$url   = get_permalink( $post );
$title = get_the_title( $post );

$start    = (string) get_post_meta( $post->ID, 'dssast_date', true );
$end      = (string) get_post_meta( $post->ID, 'dssast_end_date', true );
$deadline = (string) get_post_meta( $post->ID, 'dssast_deadline', true );
if ( '' === $end || $end < $start ) {
    $end = $start;
}

$terms = get_the_terms( $post->ID, 'dssast_competition_category' );
$terms = is_array( $terms ) ? $terms : array();

$summary = $post->post_excerpt;
if ( '' === trim( (string) $summary ) ) {
    $summary = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 46, '…' );
}
?>
<article class="event-card event-card--<?php echo esc_attr( $state['code'] ); ?>">
    <div class="event-card__media">
        <a class="event-card__media-link" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
            <?php
            if ( has_post_thumbnail( $post ) ) {
                echo get_the_post_thumbnail( $post, 'large', array(
                    'class'   => 'event-card__image',
                    'loading' => 'lazy',
                    'alt'     => $title,
                ) );
            } else {
                ?>
                <span class="event-card__image event-card__image--placeholder" role="img" aria-label="<?php echo esc_attr( $title ); ?>"></span>
                <?php
            }
            ?>
        </a>
        <span class="event-card__badge event-card__badge--<?php echo esc_attr( $state['code'] ); ?>"><?php echo esc_html( $state['label'] ); ?></span>
        <h2 class="event-card__title">
            <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
        </h2>
    </div>

    <div class="event-card__body">
        <div class="event-card__text">
            <?php if ( '' !== trim( (string) $summary ) ) : ?>
                <p class="event-card__summary"><?php echo esc_html( $summary ); ?></p>
            <?php endif; ?>
            <p class="event-card__meta">
                <?php if ( '' !== $start ) : ?>
                    <time datetime="<?php echo esc_attr( $start ); ?>"><?php echo esc_html( $start === $end ? $start : $start . ' – ' . $end ); ?></time>
                <?php endif; ?>
                <?php if ( '' !== $deadline ) : ?>
                    <span class="event-card__deadline">报名截止 <?php echo esc_html( $deadline ); ?></span>
                <?php endif; ?>
                <?php foreach ( $terms as $term ) : ?>
                    <span class="event-card__category"><?php echo esc_html( $term->name ); ?></span>
                <?php endforeach; ?>
            </p>
        </div>
        <a class="event-card__go" href="<?php echo esc_url( $url ); ?>" aria-label="查看《<?php echo esc_attr( $title ); ?>》详情">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>

    <?php if ( $links ) : ?>
        <ul class="event-card__links">
            <?php foreach ( $links as $link ) : ?>
                <li>
                    <a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                        <span><?php echo esc_html( $link['title'] ); ?></span>
                        <span class="event-card__link-icon" aria-hidden="true">↗</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
