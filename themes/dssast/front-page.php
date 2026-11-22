<?php get_header(null, ['overlay' => true]); ?>

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

  <section class="news">
    <div>
      <h2>最新通知与动态</h2>
      <a href="<?php echo esc_url(home_url('/notes/')); ?>">
        查看全部 &rarr;
      </a>
    </div>

    <div>
      <?php
      $latest = new WP_Query(['posts_per_page' => 5, 'post_status' => 'publish']);
      if ($latest->have_posts()) :
        while ($latest->have_posts()) : $latest->the_post();
      ?>
          <article>
            <time datetime="<?php echo get_the_date('c'); ?>">
              <?php echo get_the_date('Y-m-d'); ?>
            </time>
            <h3>
              <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
              </a>
            </h3>
          </article>
        <?php
        endwhile;
        wp_reset_postdata();
      else :
        ?>
        <p>暂无已发布的通知公告。</p>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>