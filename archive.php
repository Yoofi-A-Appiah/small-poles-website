<?php get_header(); ?>

    <!-- News Archive Hero -->
    <div class="news-archive-hero">
      <div class="news-archive-hero-bg"></div>
      <div class="container">
        <div>
          <div class="breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
            <span class="sep">/</span>
            <span class="current">
              <?php
              if ( is_category() ) {
                  single_cat_title();
              } else {
                  echo 'News & Analysis';
              }
              ?>
            </span>
          </div>
          <span class="label">
            <?php echo is_category() ? 'Category' : 'News & Analysis'; ?>
          </span>
          <h1>
            <?php if ( is_category() ) : ?>
              <?php single_cat_title(); ?>
            <?php else : ?>
              GPL Intel.<br /><span class="accent">World Cup & Beyond.</span>
            <?php endif; ?>
          </h1>
        </div>
        <p>
          <?php if ( is_category() ) : ?>
            <?php echo category_description(); ?>
          <?php else : ?>
            Match analysis, World Cup previews, predictions, and platform updates —
            everything you need to stay sharp.
          <?php endif; ?>
        </p>
      </div>
    </div>

    <section>
      <div class="container">

        <!-- Category filters -->
        <div class="news-filters">
          <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>"
             class="filter-btn <?php echo ! is_category() ? 'active' : ''; ?>">All</a>
          <?php
          $cats = get_categories( [ 'orderby' => 'count', 'order' => 'DESC', 'number' => 6 ] );
          foreach ( $cats as $cat ) :
          ?>
          <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>"
             class="filter-btn <?php echo is_category( $cat->term_id ) ? 'active' : ''; ?>">
            <?php echo esc_html( $cat->name ); ?>
          </a>
          <?php endforeach; ?>
        </div>

        <?php if ( have_posts() ) : ?>

          <?php
          $posts_array = [];
          while ( have_posts() ) {
              the_post();
              $posts_array[] = get_post();
          }
          rewind_posts();
          $has_featured = count( $posts_array ) >= 3;
          ?>

          <?php if ( $has_featured && ! is_category() ) : ?>
          <!-- Featured layout: first post large, next two stacked -->
          <div class="news-featured">
            <?php
            $first = true;
            $secondary_count = 0;
            while ( have_posts() ) :
                the_post();
                if ( $first ) :
            ?>
            <a href="<?php the_permalink(); ?>" class="news-card featured">
              <div class="news-card-image">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'news-featured', [ 'class' => 'card-img' ] ); ?>
                  <div class="card-img-overlay"></div>
                <?php endif; ?>
                <?php echo smallpoles_category_badge(); ?>
              </div>
              <div class="news-card-body">
                <h3><?php the_title(); ?></h3>
                <p><?php echo wp_trim_words( get_the_excerpt(), 24 ); ?></p>
                <div class="news-card-meta">
                  <span><?php echo smallpoles_post_date(); ?></span>
                  <span class="sep"></span>
                  <span><?php echo smallpoles_reading_time(); ?></span>
                </div>
              </div>
            </a>
            <?php
                $first = false;
                echo '<div class="news-secondary-stack">';
            elseif ( $secondary_count < 2 ) :
                $secondary_count++;
            ?>
            <a href="<?php the_permalink(); ?>" class="news-card compact">
              <div class="news-card-image">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'news-card', [ 'class' => 'card-img' ] ); ?>
                  <div class="card-img-overlay"></div>
                <?php endif; ?>
                <?php echo smallpoles_category_badge(); ?>
              </div>
              <div class="news-card-body">
                <h3><?php the_title(); ?></h3>
                <p><?php echo wp_trim_words( get_the_excerpt(), 14 ); ?></p>
                <div class="news-card-meta">
                  <span><?php echo smallpoles_post_date(); ?></span>
                  <span class="sep"></span>
                  <span><?php echo smallpoles_reading_time(); ?></span>
                </div>
              </div>
            </a>
            <?php
            endif;
            endwhile;
            echo '</div></div>';
            rewind_posts();
            // Skip first 3 posts for the grid below
            $skip = 0;
            while ( have_posts() ) {
                the_post();
                $skip++;
                if ( $skip > 3 ) {
                    global $wp_query;
                    $wp_query->current_post = $skip - 2;
                    break;
                }
            }
            ?>

          <!-- Remaining posts grid -->
          <?php if ( $skip > 3 ) : ?>
          <div class="news-grid-archive">
            <?php
            while ( have_posts() ) :
                the_post();
            ?>
            <a href="<?php the_permalink(); ?>" class="news-card">
              <div class="news-card-image">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'news-card', [ 'class' => 'card-img' ] ); ?>
                  <div class="card-img-overlay"></div>
                <?php endif; ?>
                <?php echo smallpoles_category_badge(); ?>
              </div>
              <div class="news-card-body">
                <h3><?php the_title(); ?></h3>
                <p><?php echo wp_trim_words( get_the_excerpt(), 18 ); ?></p>
                <div class="news-card-meta">
                  <span><?php echo smallpoles_post_date(); ?></span>
                  <span class="sep"></span>
                  <span><?php echo smallpoles_reading_time(); ?></span>
                </div>
              </div>
            </a>
            <?php endwhile; ?>
          </div>
          <?php endif; ?>

          <?php else : ?>
          <!-- Simple grid for category pages or small post counts -->
          <div class="news-grid-archive">
            <?php while ( have_posts() ) : the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="news-card">
              <div class="news-card-image">
                <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'news-card', [ 'class' => 'card-img' ] ); ?>
                  <div class="card-img-overlay"></div>
                <?php endif; ?>
                <?php echo smallpoles_category_badge(); ?>
              </div>
              <div class="news-card-body">
                <h3><?php the_title(); ?></h3>
                <p><?php echo wp_trim_words( get_the_excerpt(), 18 ); ?></p>
                <div class="news-card-meta">
                  <span><?php echo smallpoles_post_date(); ?></span>
                  <span class="sep"></span>
                  <span><?php echo smallpoles_reading_time(); ?></span>
                </div>
              </div>
            </a>
            <?php endwhile; ?>
          </div>
          <?php endif; ?>

          <!-- Pagination -->
          <?php if ( $GLOBALS['wp_query']->max_num_pages > 1 ) : ?>
          <div class="news-pagination">
            <?php
            echo paginate_links( [
                'prev_text' => '←',
                'next_text' => '→',
                'before_page_number' => '',
                'after_page_number'  => '',
            ] );
            ?>
          </div>
          <?php endif; ?>

        <?php else : ?>
          <p style="color:var(--text-muted);text-align:center;padding:80px 0">No articles yet. Check back soon.</p>
        <?php endif; ?>

      </div>
    </section>

    <!-- CTA -->
    <section class="page-cta">
      <div class="page-cta-bg"></div>
      <div class="container">
        <span class="label" style="display:block;margin-bottom:16px">Beta Access</span>
        <h2>Ready to manage your<br />own team in the GPL?</h2>
        <p>Join the waitlist — be first on the pitch when SmallPoles launches.</p>
        <a href="<?php echo esc_url( home_url( '/#waitlist' ) ); ?>" class="btn-primary">
          Join SmallPoles Beta →
        </a>
      </div>
    </section>

<?php get_footer(); ?>
