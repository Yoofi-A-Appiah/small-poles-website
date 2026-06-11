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
          <span class="meta-sep"></span>
          <button type="button" class="article-pred-link sp-join-trigger">Join Small Poles</button>
        </div>
      </div>
    </div>

    <!-- Featured image -->
    <?php if ( has_post_thumbnail() ) : ?>
    <div class="container" style="padding-top:40px">
      <?php the_post_thumbnail( 'news-featured', [ 'class' => 'article-featured-image' ] ); ?>
    </div>
    <?php endif; ?>

    <?php $sp_fixture_id = get_post_meta( get_the_ID(), '_sp_fixture_id', true ); ?>

    <!-- Article layout: main column + sidebar -->
    <div class="article-layout container">

      <!-- Main column -->
      <div class="article-main">
        <div class="article-body">
          <?php the_content(); ?>
        </div>

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
      </div>

      <!-- Comments + post nav -->
      <div class="article-comments-section">
        <?php comments_template(); ?>

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

      <!-- Sidebar — all widgets together; on mobile, display:contents lets children reorder -->
      <aside class="article-sidebar">

        <!-- Match Prediction: mobile=first, desktop=top of sidebar -->
        <?php if ( $sp_fixture_id ) : ?>
        <div class="sidebar-widget sp-pred-widget">
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

        <!-- Community predictions: mobile=after article body, desktop=below match prediction -->
        <?php if ( $sp_fixture_id ) : ?>
        <div class="sidebar-widget sp-community-widget">
          <div class="sidebar-widget-header">Your Prediction</div>
          <div class="sidebar-widget-body sp-community-wrap" id="sp-community" data-fixture-id="<?php echo esc_attr( $sp_fixture_id ); ?>">
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
            <div class="sp-results-wrap" id="sp-results" style="display:none">
              <div id="sp-community-results" class="sp-loading">
                <div class="sp-skeleton"><div class="sp-sk sp-sk-bar"></div><div class="sp-sk sp-sk-comps"></div></div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Beta CTA + related: mobile=last, desktop=bottom of sidebar -->
        <div class="article-sidebar-rest">

          <div class="sidebar-widget">
            <div class="sidebar-widget-header">Join SmallPoles Beta</div>
            <div class="sidebar-widget-body" style="text-align:center;padding:20px 18px">
              <p style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin-bottom:16px">
                The first GPL fantasy platform. Get early access before we launch.
              </p>
              <a href="<?php echo esc_url( home_url( '/#waitlist' ) ); ?>" class="btn-primary sp-join-trigger" style="width:100%;justify-content:center">
                Get Early Access →
              </a>
            </div>
          </div>

          <?php
          $related = smallpoles_related_posts( 4 );
          if ( $related->have_posts() ) :
          ?>
          <div class="sidebar-widget">
            <div class="sidebar-widget-header">Related News</div>
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

        </div><!-- .article-sidebar-rest -->

      </aside>
    </div>

<!-- Join Small Poles modal -->
<div class="sp-join-modal" id="sp-join-modal" role="dialog" aria-modal="true" aria-label="Join Small Poles">
  <div class="sp-join-modal-box">
    <button class="sp-join-modal-close" id="sp-join-modal-close" aria-label="Close">&times;</button>
    <span class="label" style="display:block;margin-bottom:14px">Early Access</span>
    <h3>Get in before kickoff.</h3>
    <p>Join the waitlist and get first access when Small Poles launches.</p>
    <iframe
      src="https://tally.so/embed/ODJ4Np?alignLeft=1&hideTitle=1&transparentBackground=1"
      loading="lazy"
      width="100%"
      height="380"
      frameborder="0"
      marginheight="0"
      marginwidth="0"
      title="Small Poles Waitlist"
    ></iframe>
    <p class="sp-join-modal-note">No spam. Just launch updates and early access.</p>
  </div>
</div>

<script>
(function () {
  var modal    = document.getElementById('sp-join-modal');
  var closeBtn = document.getElementById('sp-join-modal-close');
  if (!modal) return;
  function openModal(e) { if (e && e.preventDefault) e.preventDefault(); modal.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
  function closeModal() { modal.classList.remove('is-open'); document.body.style.overflow = ''; }
  document.querySelectorAll('.sp-join-trigger').forEach(function (btn) { btn.addEventListener('click', openModal); });
  closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>

<?php endwhile; ?>

<?php get_footer(); ?>
