<?php get_header(); ?>

    <!-- Hero -->
    <section class="hero">
      <div class="hero-bg"></div>
      <div class="hero-grid"></div>
      <div class="container">
        <div class="hero-content">
          <h1>
            The Black Stars go fly the flag.<br /><span class="accent">You go build the squad.</span>
          </h1>
          <p class="hero-sub">
            This be the first fantasy platform built for the GPL. Pick real players,
            predict results, and compete with your people every gameweek. <span class="hero-highlight">No be small agenda</span>
          </p>
          <div class="hero-actions">
            <a href="#waitlist" class="btn-primary">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14M12 5l7 7-7 7" />
              </svg>
              Join the Waitlist
            </a>
            <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="btn-ghost">World Cup News →</a>
          </div>
          <div class="hero-stats">
            <div>
              <div class="hero-stat-value">18</div>
              <div class="hero-stat-label">GPL Clubs</div>
            </div>
            <div>
              <div class="hero-stat-value">500+</div>
              <div class="hero-stat-label">Players Tracked</div>
            </div>
            <div>
              <div class="hero-stat-value">34</div>
              <div class="hero-stat-label">Gameweeks</div>
            </div>
          </div>
        </div>

        <!-- Phone mockup visual -->
        <div class="hero-visual">
          <div class="parallax-phone">
            <div class="phone-mockup">
              <img
                src="<?php echo get_template_directory_uri(); ?>/assets/mockuphome.webp"
                alt="Small Poles App — Home Screen"
                class="phone-screenshot"
                loading="eager"
                decoding="async"
              />
              <div class="phone-glow"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- What is Small Poles -->
    <section id="why-small-poles" class="why-section">
      <div class="container">
        <div class="why-layout">
          <div class="why-body-col">
            <div class="why-header reveal">
              <span class="label">What is Small Poles</span>
              <h2>Ghana football fans<br />dey deserve a real game.</h2>
            </div>
            <div class="why-list">
              <div class="why-item reveal">
                <div class="why-body">
                  <h3>Fantasy Football</h3>
                  <p>Pick 15 real GPL players, set your formation, and earn points from every goal, assist, and clean sheet across the season.</p>
                </div>
              </div>
              <div class="why-item reveal">
                <div class="why-body">
                  <h3>Match Predictions</h3>
                  <p>Call results before kickoff. Bonus points when you read the game right.</p>
                </div>
              </div>
              <div class="why-item reveal">
                <div class="why-body">
                  <h3>In-Depth Player Analysis</h3>
                  <p>Form guides, price history, fixture difficulty, and full stats on every player in the GPL pool.</p>
                </div>
              </div>
              <div class="why-item reveal">
                <div class="why-body">
                  <h3>News & Match Analysis</h3>
                  <p>GPL coverage that actually goes deep with team news, odds, match predictions, injury updates, and post-match breakdowns.</p>
                </div>
              </div>
              <div class="why-item reveal">
                <div class="why-body">
                  <h3>Private Leagues</h3>
                  <p>Create a league for your crew or join the global standings. Settle the debates every gameweek.</p>
                </div>
              </div>
            </div>
            <div class="reveal" style="margin-top:40px">
              <a href="#waitlist" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
                Join the Waitlist
              </a>
            </div>
          </div>
          <div class="why-visual reveal">
            <div class="parallax-phone">
              <div class="phone-mockup phone-mockup--tilt-right">
                <img
                  src="<?php echo get_template_directory_uri(); ?>/assets/mockupscout.webp"
                  alt="Small Poles Scout Room"
                  class="phone-screenshot"
                  loading="lazy"
                  decoding="async"
                />
                <div class="phone-glow"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
      <div class="container">
        <div class="section-header reveal">
          <div>
            <span class="label">Getting Started</span>
            <h2 style="margin-top:12px">From zero to<br />gameweek hero.</h2>
          </div>
        </div>
        <div class="steps-grid reveal">
          <div class="step">
            <div class="step-number">01</div>
            <h3>Create Account</h3>
            <p>Quick sign-up with your email or number. Pick your favorite GPL side and choose a name your enemies will fear.</p>
            <div class="step-connector">→</div>
          </div>
          <div class="step">
            <div class="step-number">02</div>
            <h3>Build Your Squad</h3>
            <p>Pick 15 real GPL players from a GHS 100M budget. Set your formation and choose your captain — the one bringing the double points.</p>
            <div class="step-connector">→</div>
          </div>
          <div class="step">
            <div class="step-number">03</div>
            <h3>Score & Predict</h3>
            <p>Your players earn points from what happens on the pitch. Layer in match predictions before kickoff and earn bonus points when you read it right.</p>
            <div class="step-connector">→</div>
          </div>
          <div class="step">
            <div class="step-number">04</div>
            <h3>Compete & Win</h3>
            <p>Climb the rankings, challenge your people in private leagues, and keep your squad sharp with transfers each gameweek.</p>
          </div>
        </div>
        <div class="reveal" style="text-align:center;margin-top:48px">
          <a href="#waitlist" class="btn-primary" style="display:inline-flex;align-items:center;gap:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
            Start Building Your Squad
          </a>
        </div>
      </div>
    </section>

    <!-- Squad Builder Spotlight -->
    <section class="squad-spotlight">
      <div class="container">
        <div class="spotlight-layout reveal">
          <div class="spotlight-text">
            <span class="label">Squad Builder</span>
            <h2>15 players.<br /><span class="accent">Your formation.</span></h2>
            <p>Pick from every GPL club within a GHS 100M budget. Place your men on the pitch, set your captain, and manage transfers each gameweek. Your squad, your wahala.</p>

            <div style="display:flex;flex-direction:column;gap:16px;margin-top:28px">

              <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="width:36px;height:36px;background:rgba(0,255,127,0.08);border:1px solid rgba(0,255,127,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--pitch-glow)" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M12 14l-4 8h8l-4-8z"/></svg>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:600;color:var(--text-primary);margin-bottom:2px">Captain &amp; Vice Captain</div>
                  <div style="font-size:13px;color:var(--text-muted);line-height:1.5">Your captain earns double points. If they don't play, your vice steps up automatically.</div>
                </div>
              </div>

              <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="width:36px;height:36px;background:rgba(0,255,127,0.08);border:1px solid rgba(0,255,127,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--pitch-glow)" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:600;color:var(--text-primary);margin-bottom:2px">Gameweek Boosts</div>
                  <div style="font-size:13px;color:var(--text-muted);line-height:1.5">Triple Captain, Bench Boost, Free Hit — play your chips at the right moment to climb the table fast.</div>
                </div>
              </div>

              <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="width:36px;height:36px;background:rgba(0,255,127,0.08);border:1px solid rgba(0,255,127,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--pitch-glow)" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:600;color:var(--text-primary);margin-bottom:2px">Weekly Transfers</div>
                  <div style="font-size:13px;color:var(--text-muted);line-height:1.5">One free transfer per gameweek. Bank unused transfers or take extra ones with a points hit.</div>
                </div>
              </div>

              <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="width:36px;height:36px;background:rgba(0,255,127,0.08);border:1px solid rgba(0,255,127,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--pitch-glow)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                </div>
                <div>
                  <div style="font-size:14px;font-weight:600;color:var(--text-primary);margin-bottom:2px">Flexible Formations</div>
                  <div style="font-size:13px;color:var(--text-muted);line-height:1.5">4-4-2, 4-3-3, 3-5-2 — pick the shape that fits your players and switch it up each week.</div>
                </div>
              </div>

            </div>

            <a href="#waitlist" class="btn-primary" style="margin-top:32px;display:inline-flex;align-items:center;gap:8px">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
              Join the Waitlist
            </a>
          </div>
          <div class="spotlight-visual">
            <div class="parallax-phone">
              <div class="phone-mockup">
                <img
                  src="<?php echo get_template_directory_uri(); ?>/assets/mockupsquad.webp"
                  alt="Small Poles — Squad Builder"
                  class="phone-screenshot"
                  loading="lazy"
                  decoding="async"
                />
                <div class="phone-glow"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php /*
    <!-- Features -->
    <section class="features" id="features">
      <div class="container">
        <div class="section-header reveal">
          <div>
            <span class="label">Core Modules</span>
            <h2 style="margin-top:12px">Everything you need<br />to know your players.</h2>
          </div>
        </div>
        <div class="features-grid reveal">
          <div class="feature-card">
            <div class="feature-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/clipboard.png" alt="Scout & Tactics" style="width:24px;height:24px" /></div>
            <h3>Scout & Tactics</h3>
            <p>Find the GPL players worth picking, then put them where they belong. Scout the entire league, set your formation, and name your captain before every matchday.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/bar-chart.png" alt="Player Dossier" style="width:24px;height:24px" /></div>
            <h3>Player Dossier</h3>
            <p>Every player's full story. Goals, assists, clean sheets, price moves, and upcoming fixtures. All in one place.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/newspaper.png" alt="Predictions" style="width:24px;height:24px" /></div>
            <h3>Predictions & Odds</h3>
            <p>Call the result before kickoff. See GPL fixture odds, make your match predictions, and earn bonus points when you read the game right.</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/trophy.png" alt="Leagues" style="width:24px;height:24px" /></div>
            <h3>Leagues & Rankings</h3>
            <p>Set up a private league, bring in your people, and see who really knows the GPL. Plus a global board for when you want a bigger test.</p>
          </div>
        </div>
      </div>
    </section>
    */ ?>

    <!-- Leagues & Rankings -->
    <section class="features" id="features">
      <div class="container">
        <!-- League showcase -->
        <div class="league-showcase reveal">
          <div class="league-showcase-text">
            <span class="label">Leagues &amp; Rankings</span>
            <h2>Agenda must<br /><span class="accent">Agend.</span></h2>
            <p>Set up a private league for your crew, join the global standings, and see exactly where you rank every gameweek.</p>
            <ul class="league-showcase-bullets">
              <li>Global leaderboard with real-time rankings</li>
              <li>Private leagues — create or join with a code</li>
              <li>Gameweek points + season total tracked</li>
              <li>Your rank moves visible after every GW</li>
            </ul>
            <a href="#waitlist" class="btn-primary" style="margin-top:32px;display:inline-flex;align-items:center;gap:8px">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
              Join the Waitlist
            </a>
          </div>
          <div class="league-showcase-visual">
            <div class="parallax-phone">
              <div class="phone-mockup phone-mockup--tilt-right">
                <img
                  src="<?php echo get_template_directory_uri(); ?>/assets/mockupleague.webp"
                  alt="Small Poles — Leagues"
                  class="phone-screenshot"
                  loading="lazy"
                  decoding="async"
                />
                <div class="phone-glow"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- World Cup Intel Section -->
    <?php
    $news_query = smallpoles_latest_news( 3 );
    if ( $news_query->have_posts() ) :
    ?>
    <section class="wc-intel">
      <div class="container">
        <div class="section-header reveal">
          <div>
            <span class="label">News & Analysis</span>
            <h2 style="margin-top:12px">World Cup 2026.<br /><span style="color:var(--pitch-glow)">GPL angle.</span></h2>
          </div>
        </div>
        <div class="news-grid-home reveal">
          <?php while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
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
          <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <div style="text-align:center;margin-top:40px">
          <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="btn-ghost">View all analysis →</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- Match Intelligence — Today's Matches + Black Stars Toggle -->
    <section class="sp-match-intel-section" id="predictions">
      <div class="container">

        <!-- Header + toggle -->
        <div class="sp-match-intel-header reveal">
          <div>
            <span class="label">Match Intelligence</span>
            <h2 id="sp-match-section-title" style="margin-top:12px">Today's Matches</h2>
          </div>
          <div class="sp-match-toggle" role="group" aria-label="Filter matches">
            <button class="sp-toggle-btn sp-toggle-btn--active" data-view="all" id="sp-toggle-all">
              All Matches
            </button>
            <button class="sp-toggle-btn" data-view="ghana" id="sp-toggle-ghana">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              Black Stars
            </button>
          </div>
        </div>

        <!-- All Matches view -->
        <div id="sp-view-all" class="sp-view">
          <div id="sp-today-fixtures" class="sp-today-list sp-loading" aria-live="polite">
            <div class="sp-skeleton">
              <div class="sp-sk" style="height:72px"></div>
              <div class="sp-sk" style="height:72px"></div>
              <div class="sp-sk" style="height:72px"></div>
            </div>
          </div>
        </div>

        <!-- Black Stars view -->
        <div id="sp-view-ghana" class="sp-view sp-view--hidden">
          <div id="sp-home-fixture" class="sp-widget sp-loading" aria-live="polite">
            <div class="sp-skeleton">
              <div class="sp-sk sp-sk-teams"></div>
              <div class="sp-sk sp-sk-bar"></div>
              <div class="sp-sk sp-sk-comps"></div>
              <div class="sp-sk sp-sk-odds"></div>
            </div>
          </div>
        </div>

        <!-- Group standings — always visible below both views -->
        <div class="sp-standings-row">
          <div id="sp-home-standings" class="sp-standings-panel sp-loading" aria-live="polite">
            <div class="sp-skeleton">
              <div class="sp-sk sp-sk-bar"></div>
              <div class="sp-sk sp-sk-comps"></div>
              <div class="sp-sk sp-sk-comps"></div>
              <div class="sp-sk sp-sk-comps"></div>
            </div>
          </div>
        </div>

        <div class="reveal" style="text-align:center;margin-top:40px">
          <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="btn-ghost">Read the World Cup Intel →</a>
        </div>

      </div>
    </section>

    <!-- CTA / Waitlist -->
    <section class="cta-section" id="waitlist">
      <div class="cta-bg"></div>
      <div class="container reveal">
        <span class="label" style="display:block;margin-bottom:20px">Early Access</span>
        <h2>Get in before<br />kickoff.</h2>
        <p>
          We dey build Small Poles for people who genuinely love the GPL.
          Join the waitlist and get "first sele" on the pitch when we launch.
        </p>
        <iframe
          data-tally-src="https://tally.so/embed/ODJ4Np?alignLeft=1&hideTitle=1&transparentBackground=1&dynamicHeight=1"
          loading="lazy"
          width="100%"
          height="184"
          frameborder="0"
          marginheight="0"
          marginwidth="0"
          title="Small Poles Waitlist"
          style="max-width:480px"
        ></iframe>
        <p class="cta-note">No spam. Just launch updates and early access.</p>
      </div>
    </section>

<?php get_footer(); ?>
