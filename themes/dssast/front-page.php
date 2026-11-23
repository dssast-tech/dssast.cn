<?php
get_header(null, ['overlay' => true]);
$announcements = dssast_home_feed( 'announcements', 5 );
$news = dssast_home_feed( 'news', 3 );
$announcements_url = get_term_link( 'announcements', 'category' );
if ( is_wp_error( $announcements_url ) ) {
    $announcements_url = add_query_arg( 'category_name', 'announcements', home_url( '/' ) );
}
?>

<main class="front-page">
  <section class="hero">
    <div class="hero-container">
      <h1 class="hero-title" aria-label="DSSAST">
        <svg class="hero-svg" viewBox="0 0 1000 180" aria-hidden="true">
          <defs>
            <radialGradient id="orb-a" cx="35%" cy="30%" r="75%">
              <stop offset="0%" stop-color="#224389" />
              <stop offset="100%" stop-color="#20296e" />
            </radialGradient>

            <radialGradient id="orb-b" cx="35%" cy="30%" r="75%">
              <stop offset="0%" stop-color="#2782c2" />
              <stop offset="100%" stop-color="#1b60a3" />
            </radialGradient>

            <radialGradient id="orb-c" cx="35%" cy="30%" r="75%">
              <stop offset="0%" stop-color="#37a9e0" />
              <stop offset="100%" stop-color="#227ebc" />
            </radialGradient>

            <mask id="hero-text-mask" maskUnits="userSpaceOnUse" x="0" y="0" width="1000" height="180">
              <text class="hero-text" x="500" y="95" text-anchor="middle" dominant-baseline="central" fill="#fff">
                笃实书院学生科协
              </text>
            </mask>
          </defs>

          <g mask="url(#hero-text-mask)">
            <rect width="1000" height="180" fill="#1b60a3" />
            <g x-data="heroOrbs({ count: 100, w: 1000, h: 180 })"></g>
          </g>
        </svg>
      </h1>
    </div>
    <div class="hero-mask"></div>
  </section>

  <section class="departments">
    <h2>部门组成</h2>
    <department-container>
      <department-card>
        <div>COMPETITIONS</div>
        <h3>赛事部</h3>
        <p>
          组织院级算法挑战赛、智能硬件设计赛及校级赛事培育，提供赛前培训与算力支持。
        </p>
      </department-card>
      <department-card>
        <div>ACADEMICS</div>
        <h3>学术部</h3>
        <p>
          对接导师课题组与本科生科研立项，举办学术沙龙与论文阅读分享会。
        </p>
      </department-card>
      <department-card>
        <div>TECHNOLOGY</div>
        <h3>技术部</h3>
        <p>
          一只敏捷的棕色狐狸跳过懒惰的狗。
        </p>
      </department-card>
      <department-card>
        <div>OPERATIONS</div>
        <h3>常务部</h3>
        <p>
          我能吞下玻璃而不伤身体。
        </p>
      </department-card>
    </department-container>
  </section>

  <section id="announcements" class="home-feed home-announcements" aria-labelledby="announcements-heading">
    <div class="feed-heading">
      <div>
        <p class="feed-eyebrow">INFORMATION</p>
        <h2 id="announcements-heading">最新公告</h2>
        <p class="feed-description">重要安排与最新通知，在这里及时了解。</p>
      </div>
      <a class="feed-all-link" href="<?php echo esc_url( $announcements_url ); ?>">查看全部公告</a>
    </div>
    <?php if ( $announcements ) : ?>
      <ol class="announcement-list">
        <?php foreach ( $announcements as $entry ) : ?>
          <li>
            <article class="announcement-item">
              <a class="announcement-link" href="<?php echo esc_url( $entry['url'] ); ?>" aria-labelledby="<?php echo esc_attr( 'announcement-title-' . $entry['id'] ); ?>">
                <time class="announcement-date" datetime="<?php echo esc_attr( $entry['date_iso'] ); ?>">
                  <span><?php echo esc_html( $entry['month_day'] ); ?></span>
                  <span><?php echo esc_html( $entry['year'] ); ?></span>
                </time>
                <div class="announcement-copy">
                  <h3 id="<?php echo esc_attr( 'announcement-title-' . $entry['id'] ); ?>">
                    <span><?php echo esc_html( $entry['title'] ); ?></span>
                    <?php if ( $entry['is_recent'] ) : ?><span class="announcement-badge">新</span><?php endif; ?>
                  </h3>
                  <?php if ( $entry['summary'] ) : ?><p><?php echo esc_html( $entry['summary'] ); ?></p><?php endif; ?>
                </div>
                <span class="announcement-read">阅读全文</span>
              </a>
            </article>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php else : ?>
      <div class="feed-empty">
        <span class="feed-empty-symbol" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><rect x="6" y="4" width="20" height="24" rx="3"/><path d="M11 11h10M11 16h10M11 21h6"/></svg></span>
        <div><h3>新的消息，正在准备中</h3><p>公告发布后会显示在这里，欢迎关注后续通知。</p></div>
      </div>
    <?php endif; ?>
  </section>

  <section id="news" class="home-feed home-news" aria-labelledby="news-heading">
    <div class="feed-heading">
      <div>
        <p class="feed-eyebrow">NEWS &amp; STORIES</p>
        <h2 id="news-heading">新闻一览</h2>
        <p class="feed-description">记录科创实践，也分享每一次探索。</p>
      </div>
      <a class="feed-all-link" href="<?php echo esc_url( home_url( '/news/' ) ); ?>">查看全部新闻</a>
    </div>
    <?php if ( $news ) : ?>
      <div class="news-grid news-grid--count-<?php echo esc_attr( count( $news ) ); ?>">
        <?php foreach ( $news as $index => $entry ) :
          $cover_html = $entry['image_id']
            ? wp_get_attachment_image( $entry['image_id'], 'large', false, array( 'class' => 'news-card-image', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 640px) 100vw, (max-width: 1000px) 50vw, 45vw' ) )
            : '';
          ?>
          <article class="news-card <?php echo 0 === $index ? 'news-card--featured' : 'news-card--compact'; ?>">
            <a class="news-card-link" href="<?php echo esc_url( $entry['url'] ); ?>" aria-labelledby="<?php echo esc_attr( 'news-title-' . $entry['id'] ); ?>">
              <div class="news-card-cover">
                <?php if ( $cover_html ) : ?>
                  <?php echo $cover_html; ?>
                <?php else : ?>
                  <div class="news-art" aria-hidden="true">
                    <span class="news-art-label">DSSAST</span>
                    <svg viewBox="0 0 600 340" fill="none">
                      <path class="news-art-grid" d="M0 85h600M0 170h600M0 255h600M100 0v340M200 0v340M300 0v340M400 0v340M500 0v340"/>
                      <g class="news-art-orbit" transform="rotate(-28 410 175)">
                        <ellipse cx="410" cy="175" rx="140" ry="60"/>
                        <ellipse cx="410" cy="175" rx="140" ry="60" transform="rotate(45 410 175)"/>
                        <ellipse cx="410" cy="175" rx="140" ry="60" transform="rotate(90 410 175)"/>
                        <ellipse cx="410" cy="175" rx="140" ry="60" transform="rotate(135 410 175)"/>
                        <circle cx="410" cy="175" r="47"/>
                      </g>
                    </svg>
                    <span class="news-art-caption">让探索发生</span>
                  </div>
                <?php endif; ?>
              </div>
              <div class="news-card-copy">
                <div class="news-card-meta"><span>科协动态</span><time datetime="<?php echo esc_attr( $entry['date_iso'] ); ?>"><?php echo esc_html( $entry['date'] ); ?></time></div>
                <h3 id="<?php echo esc_attr( 'news-title-' . $entry['id'] ); ?>"><?php echo esc_html( $entry['title'] ); ?></h3>
                <?php if ( $entry['summary'] ) : ?><p class="news-card-summary"><?php echo esc_html( $entry['summary'] ); ?></p><?php endif; ?>
                <span class="news-card-read">阅读全文</span>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else : ?>
      <div class="feed-empty">
        <span class="feed-empty-symbol" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><rect x="4" y="6" width="24" height="20" rx="3"/><circle cx="11" cy="13" r="2"/><path d="m5 23 8-7 5 4 5-6 5 7"/></svg></span>
        <div><h3>下一段故事，值得期待</h3><p>新闻发布后会在这里呈现活动图文与科创记录。</p></div>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php get_footer(); ?>
