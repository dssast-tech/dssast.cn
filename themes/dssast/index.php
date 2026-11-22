<?php get_header(); ?>

<main>
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article>
        <header>
          <h1><?php the_title(); ?></h1>
          <!--<time><?php /* echo get_the_date('Y-m-d H:i:s'); */ ?></time>-->
        </header>
        <div class="content">
          <?php the_content(); ?>
        </div>
      </article>
  <?php endwhile;
  endif; ?>
</main>

<?php get_footer(); ?>