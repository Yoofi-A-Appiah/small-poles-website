<?php get_header(); ?>

    <section class="page-hero" style="min-height:60vh;display:flex;align-items:center">
      <div class="page-hero-bg"></div>
      <div class="container" style="text-align:center">
        <span class="label" style="display:block;margin-bottom:20px">404</span>
        <h1 style="margin-top:0">Page not<br /><span class="accent">found.</span></h1>
        <p class="page-hero-sub" style="margin:0 auto 32px;text-align:center">
          This page doesn't exist — but the GPL does. Head back to the pitch.
        </p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-primary">← Back to Home</a>
          <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="btn-ghost">Read our News & Analysis</a>
        </div>
      </div>
    </section>

<?php get_footer(); ?>
