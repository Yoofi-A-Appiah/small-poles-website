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

        <!-- Tactics board visual -->
        <div class="hero-visual">
          <div class="tactics-card">
            <div class="tactics-header">
              <div class="tactics-header-left">
                <div class="tactics-header-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="12" y1="3" x2="12" y2="21" />
                  </svg>
                </div>
                <div>
                  <h3>Kakalika FC</h3>
                  <span style="color:var(--text-muted);font-size:12px">GW 12 · GHS 81.6M / 100M</span>
                </div>
              </div>
              <div class="tactics-formation-badge">4 – 3 – 3</div>
            </div>
            <div class="tactics-pitch">
              <div class="pitch-line horizontal center"></div>
              <div class="pitch-circle"></div>
              <div class="player-dot" style="left:50%;top:88%;animation-delay:0.1s"><div class="player-dot-circle">1</div><div class="player-dot-name">Kyei</div></div>
              <div class="player-dot" style="left:15%;top:70%;animation-delay:0.15s"><div class="player-dot-circle">2</div><div class="player-dot-name">Asibey</div></div>
              <div class="player-dot" style="left:38%;top:72%;animation-delay:0.2s"><div class="player-dot-circle">5</div><div class="player-dot-name">Gilbani</div></div>
              <div class="player-dot" style="left:62%;top:72%;animation-delay:0.25s"><div class="player-dot-circle">4</div><div class="player-dot-name">Aquah</div></div>
              <div class="player-dot" style="left:85%;top:70%;animation-delay:0.3s"><div class="player-dot-circle">3</div><div class="player-dot-name">Hamzata</div></div>
              <div class="player-dot" style="left:25%;top:48%;animation-delay:0.35s"><div class="player-dot-circle">8</div><div class="player-dot-name">Adabo</div></div>
              <div class="player-dot captain" style="left:50%;top:45%;animation-delay:0.4s"><div class="player-dot-circle">10</div><div class="player-dot-name">Antwi ★</div></div>
              <div class="player-dot" style="left:75%;top:48%;animation-delay:0.45s"><div class="player-dot-circle">6</div><div class="player-dot-name">Simpson</div></div>
              <div class="player-dot" style="left:20%;top:22%;animation-delay:0.5s"><div class="player-dot-circle">11</div><div class="player-dot-name">Amidu</div></div>
              <div class="player-dot" style="left:50%;top:18%;animation-delay:0.55s"><div class="player-dot-circle">9</div><div class="player-dot-name">Attah Kumi</div><div class="player-dot-pts">+12 pts</div></div>
              <div class="player-dot" style="left:80%;top:22%;animation-delay:0.6s"><div class="player-dot-circle">7</div><div class="player-dot-name">Annor</div></div>
            </div>
            <div class="tactics-footer">
              <div class="tactics-stat"><div class="tactics-stat-value green">67</div><div class="tactics-stat-label">GW Points</div></div>
              <div class="tactics-stat"><div class="tactics-stat-value">389</div><div class="tactics-stat-label">Total Points</div></div>
              <div class="tactics-stat"><div class="tactics-stat-value">#247</div><div class="tactics-stat-label">Global Rank</div></div>
              <div class="tactics-stat"><div class="tactics-stat-value">2</div><div class="tactics-stat-label">Free Transfers</div></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Why Small Poles -->
    <section id="why-small-poles" class="why-section">
      <div class="container">
        <div class="why-header reveal">
          <span class="label">Why Small Poles</span>
          <h2>Ghana football fans<br />dey deserve a real game.</h2>
        </div>
        <div class="why-list">
          <div class="why-item reveal">
            <div class="why-num">01</div>
            <div class="why-body">
              <h3>Ghana's league. Properly tracked.</h3>
              <p>No stats platform dey cover the GPL the way it deserves. No form guides, no price history, no depth on the players your club dey actually play every week. Just plenty talk, no evidence. <span class="hero-highlight">We built what should have existed years ago.</span></p>
            </div>
          </div>
          <div class="why-item reveal">
            <div class="why-num">02</div>
            <div class="why-body">
              <h3>Every matchday is yours now.</h3>
              <p>When your squad spans Kotoko, Hearts, Medeama, and Legon Cities, no fixture go fit bore you. You get skin in the game no matter who dey play. <span class="hero-highlight">That's 34 weeks of reasons to care.</span></p>
            </div>
          </div>
          <div class="why-item reveal">
            <div class="why-num">03</div>
            <div class="why-body">
              <h3>Points from what actually happens.</h3>
              <p>No algorithm filler. Goals, assists, clean sheets, saves — the same things that count in any football conversation. <span class="hero-highlight">Real performances from real GPL players, scored in real time.</span></p>
            </div>
          </div>
          <div class="why-item reveal">
            <div class="why-num">04</div>
            <div class="why-body">
              <h3>Ghana football has always been tribal.</h3>
              <p>Set up a private league with whoever dey think say them get GPL knowledge pass. There's always one person in the group who's been wrong about Kotoko all year. <span class="hero-highlight">34 gameweeks to prove it.</span></p>
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
      </div>
    </section>

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

    <!-- Next Match Widget — Desktop -->
    <section class="sp-fixture-section sp-fixture-desktop">
      <div class="container">
        <div class="section-header reveal">
          <div>
            <span class="label">Match Intelligence</span>
            <h2 style="margin-top:12px">Black Stars'<br />next match.</h2>
          </div>
        </div>
        <div class="sp-intel-grid">
          <div id="sp-home-fixture" class="sp-widget sp-loading" aria-live="polite">
            <div class="sp-skeleton">
              <div class="sp-sk sp-sk-teams"></div>
              <div class="sp-sk sp-sk-bar"></div>
              <div class="sp-sk sp-sk-comps"></div>
              <div class="sp-sk sp-sk-odds"></div>
            </div>
          </div>
          <div id="sp-home-standings" class="sp-standings-panel sp-loading" aria-live="polite">
            <div class="sp-skeleton">
              <div class="sp-sk sp-sk-bar"></div>
              <div class="sp-sk sp-sk-comps"></div>
              <div class="sp-sk sp-sk-comps"></div>
              <div class="sp-sk sp-sk-comps"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Next Match Widget — Mobile -->
    <section class="sp-fixture-section sp-fixture-mobile">
      <div class="container">
        <div class="section-header reveal">
          <div>
            <span class="label">Match Intelligence</span>
            <h2 style="margin-top:12px">Black Stars'<br />next match.</h2>
          </div>
        </div>
        <div id="sp-home-fixture-mob" class="sp-widget sp-loading" aria-live="polite">
          <div class="sp-skeleton">
            <div class="sp-sk sp-sk-teams"></div>
            <div class="sp-sk sp-sk-bar"></div>
            <div class="sp-sk sp-sk-odds"></div>
          </div>
        </div>
        <div id="sp-home-standings-mob" class="sp-standings-panel sp-standings-mob sp-loading" aria-live="polite">
          <div class="sp-skeleton">
            <div class="sp-sk sp-sk-bar"></div>
            <div class="sp-sk sp-sk-comps"></div>
            <div class="sp-sk sp-sk-comps"></div>
            <div class="sp-sk sp-sk-comps"></div>
          </div>
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
