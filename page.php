<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

    <section class="page-hero">
      <div class="page-hero-bg"></div>
      <div class="container">
        <div class="breadcrumb">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
          <span class="sep">/</span>
          <span class="current"><?php the_title(); ?></span>
        </div>
        <h1><?php the_title(); ?></h1>
      </div>
    </section>

    <section>
      <div class="container" style="max-width:800px">
        <div class="article-body">
          <?php the_content(); ?>
        </div>
      </div>
    </section>

<?php endwhile; ?>

<?php get_footer(); ?>
