<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

    <!-- Article Hero -->
    <div class="article-hero">
      <div class="article-hero-bg"></div>
      <div class="container">
        <div class="breadcrumb">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
          <span class="sep">/</span>
          <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News & Analysis</a>
          <span class="sep">/</span>
          <span class="current"><?php the_title(); ?></span>
        </div>

        <?php echo smallpoles_category_badge(); ?>

        <h1><?php the_title(); ?></h1>

        <?php if ( has_excerpt() ) : ?>
          <p class="article-excerpt"><?php the_excerpt(); ?></p>
        <?php endif; ?>

        <div class="article-meta">
          <span class="meta-author">Small Poles</span>
          <span class="meta-sep"></span>
          <span><?php echo smallpoles_post_date(); ?></span>
          <span class="meta-sep"></span>
          <span><?php echo smallpoles_reading_time(); ?></span>
          <?php if ( get_post_meta( get_the_ID(), '_sp_fixture_id', true ) ) : ?>
          <span class="meta-sep"></span>
          <a href="#sp-article-fixture" class="article-pred-link">⚽ Match predictions ↓</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Featured image -->
    <?php if ( has_post_thumbnail() ) : ?>
    <div class="container" style="padding-top:40px">
      <?php the_post_thumbnail( 'news-featured', [ 'class' => 'article-featured-image' ] ); ?>
    </div>
    <?php endif; ?>

    <!-- Article layout: body + sidebar -->
    <div class="article-layout container">

      <!-- Article body -->
      <div>
        <div class="article-body">
          <?php the_content(); ?>
        </div>

        <!-- In-article Beta CTA -->
        <div class="article-cta">
          <div class="article-cta-text">
            <h4>Build your GPL squad with SmallPoles</h4>
            <p>Track every player we've covered here — and dominate your league when the GPL season kicks off.</p>
          </div>
          <div class="article-cta-action">
            <a href="<?php echo esc_url( home_url( '/#waitlist' ) ); ?>" class="btn-primary" style="white-space:nowrap">
              Join Beta →
            </a>
          </div>
        </div>

        <!-- Post navigation -->
        <div class="article-post-nav">
          <?php
          $prev = get_previous_post();
          $next = get_next_post();
          if ( $prev ) :
          ?>
          <a href="<?php echo esc_url( get_permalink( $prev ) ); ?>" class="post-nav-link prev">
            <div class="nav-label">← Previous</div>
            <div class="nav-title"><?php echo esc_html( get_the_title( $prev ) ); ?></div>
          </a>
          <?php else : ?>
          <div></div>
          <?php endif; ?>

          <?php if ( $next ) : ?>
          <a href="<?php echo esc_url( get_permalink( $next ) ); ?>" class="post-nav-link next">
            <div class="nav-label">Next →</div>
            <div class="nav-title"><?php echo esc_html( get_the_title( $next ) ); ?></div>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Sidebar -->
      <aside class="article-sidebar">

        <!-- Predictions widget (shown when article has a fixture ID set) -->
        <?php $sp_fixture_id = get_post_meta( get_the_ID(), '_sp_fixture_id', true ); ?>
        <?php if ( $sp_fixture_id ) : ?>
        <div class="sidebar-widget">
          <div class="sidebar-widget-header">Match Prediction</div>
          <div
            id="sp-article-fixture"
            class="sp-widget sp-loading sp-widget--sidebar"
            data-fixture-id="<?php echo esc_attr( $sp_fixture_id ); ?>"
            aria-live="polite"
          >
            <div class="sp-skeleton">
              <div class="sp-sk sp-sk-teams"></div>
              <div class="sp-sk sp-sk-bar"></div>
              <div class="sp-sk sp-sk-comps"></div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Community predictions widget (shown when article has a fixture ID) -->
        <?php if ( $sp_fixture_id ) : ?>
        <div class="sidebar-widget">
          <div class="sidebar-widget-header">Your Prediction</div>
          <div class="sidebar-widget-body sp-community-wrap" id="sp-community" data-fixture-id="<?php echo esc_attr( $sp_fixture_id ); ?>">
            <!-- Form state -->
            <form class="sp-pred-form" id="sp-pred-form" novalidate>
              <p class="sp-pred-prompt">Call the result before kickoff:</p>
              <div class="sp-pick-row">
                <button type="button" class="sp-pick-btn" data-winner="home">Home</button>
                <button type="button" class="sp-pick-btn" data-winner="draw">Draw</button>
                <button type="button" class="sp-pick-btn" data-winner="away">Away</button>
              </div>
              <div class="sp-score-row" id="sp-score-row">
                <label class="sp-score-label">Scoreline (optional)</label>
                <div class="sp-score-inputs">
                  <input type="number" class="sp-score-input" id="sp-home-score" min="0" max="20" placeholder="0" />
                  <span class="sp-score-sep">–</span>
                  <input type="number" class="sp-score-input" id="sp-away-score" min="0" max="20" placeholder="0" />
                </div>
              </div>
              <input type="text" class="sp-name-input" id="sp-pred-name" placeholder="Your name (optional)" maxlength="30" />
              <button type="submit" class="sp-submit-btn" id="sp-submit" disabled>Submit Prediction</button>
              <p class="sp-form-msg" id="sp-form-msg"></p>
            </form>
            <!-- Results state (rendered by JS after submit or if already submitted) -->
            <div class="sp-results-wrap" id="sp-results" style="display:none">
              <div id="sp-community-results" class="sp-loading">
                <div class="sp-skeleton"><div class="sp-sk sp-sk-bar"></div><div class="sp-sk sp-sk-comps"></div></div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Beta sign-up widget -->
        <div class="sidebar-widget">
          <div class="sidebar-widget-header">Join SmallPoles Beta</div>
          <div class="sidebar-widget-body" style="text-align:center;padding:20px 18px">
            <p style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin-bottom:16px">
              The first GPL fantasy platform. Get early access before we launch.
            </p>
            <a href="<?php echo esc_url( home_url( '/#waitlist' ) ); ?>" class="btn-primary" style="width:100%;justify-content:center">
              Get Early Access →
            </a>
          </div>
        </div>

        <!-- Related posts -->
        <?php
        $related = smallpoles_related_posts( 4 );
        if ( $related->have_posts() ) :
        ?>
        <div class="sidebar-widget">
          <div class="sidebar-widget-header">Related Intel</div>
          <div class="sidebar-widget-body">
            <?php while ( $related->have_posts() ) : $related->the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="sidebar-related-post">
              <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'thumbnail', [ 'class' => 'sidebar-related-thumb' ] ); ?>
              <?php else : ?>
                <div class="sidebar-related-thumb"></div>
              <?php endif; ?>
              <div>
                <div class="sidebar-related-title"><?php the_title(); ?></div>
                <div class="sidebar-related-date"><?php echo smallpoles_post_date(); ?></div>
              </div>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
          </div>
        </div>
        <?php endif; ?>


      </aside>
    </div>

<?php endwhile; ?>

<?php get_footer(); ?>
