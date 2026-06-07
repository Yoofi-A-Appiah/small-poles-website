<?php get_header(); ?>

    <section class="page-hero">
      <div class="page-hero-bg"></div>
      <div class="container">
        <div class="breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="sep">/</span><span class="current">How It Works</span></div>
        <span class="label">Getting Started</span>
        <h1>Four steps to<br /><span class="accent">gameweek hero</span>.</h1>
        <p class="page-hero-sub">Sign up, pick your players, earn points from real GPL games, and see how you stack up. It's not complicated — let's walk through it.</p>
      </div>
    </section>

    <section>
      <div class="container">

        <!-- Step 1 -->
        <div class="journey-step reveal">
          <div class="journey-num">01</div>
          <div class="journey-content">
            <h3>Create your account</h3>
            <p>Quick sign-up with your email or phone number. Choose a display name, pick your favorite GPL side, and you're in.</p>
            <div class="journey-details">
              <div class="journey-detail"><div class="journey-detail-icon">📧</div><div><h4>Email or Phone</h4><p>Use whichever you prefer — both work fine.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon">🔒</div><div><h4>Secure by Default</h4><p>Your account is protected from the start. Strong password, secure login.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon">💚</div><div><h4>Pick Your Club</h4><p>Hearts? Kotoko? Medeama? Pick your side and let everyone know where you stand.</p></div></div>
            </div>
          </div>
          <div class="journey-visual">
            <div class="jv-header">Create Account</div>
            <div class="jv-body">
              <div class="mock-input">email@example.com</div>
              <div class="mock-input">Display name</div>
              <div class="mock-input">••••••••</div>
              <div class="mock-input" style="color:var(--pitch-glow)">♥ Hearts of Oak</div>
              <div class="mock-btn" style="margin-top:6px">Create Account</div>
            </div>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="journey-step reveal">
          <div class="journey-num">02</div>
          <div class="journey-content">
            <h3>Build your 15-player squad</h3>
            <p>Go through the Scout Room and pick 15 real GPL players from your GHS 100M budget. You need 2 keepers, 5 defenders, 5 midfielders, and 3 attackers. Set your formation, your XI, and choose who wears the captain's armband.</p>
            <div class="journey-details">
              <div class="journey-detail"><div class="journey-detail-icon">💰</div><div><h4>GHS 100M Budget</h4><p>Every player has a price tag. Big names or smart depth — how you spread that budget is your call.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon">📐</div><div><h4>Choose Formation</h4><p>4-4-2 or 4-3-3 at launch. Your shape decides which players are in your starting XI.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/captain-band.png" alt="Captain" style="width:20px;height:20px" /></div><div><h4>Captain = Double Points</h4><p>Your captain earns double points for the week. Pick someone in good form with a winnable game coming up.</p></div></div>
            </div>
          </div>
          <div class="journey-visual">
            <div class="jv-header">Squad — 14/15 selected</div>
            <div class="jv-body">
              <div style="display:flex;justify-content:space-between;margin-bottom:16px">
                <div style="font-family:var(--font-mono);font-size:20px;font-weight:700;color:var(--pitch-glow)">₵6.8M left</div>
                <div style="font-family:var(--font-mono);font-size:11px;color:var(--accent-gold);padding:4px 8px;border:1px solid rgba(200,168,78,0.2);border-radius:4px;background:rgba(200,168,78,0.06)">Need 1 MID</div>
              </div>
              <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;margin-bottom:16px"><div style="height:100%;width:93.2%;background:var(--pitch);border-radius:3px"></div></div>
              <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;text-align:center">
                <div style="padding:10px 0;background:var(--carbon);border-radius:6px;border:1px solid var(--border)"><div style="font-family:var(--font-mono);font-size:16px;font-weight:700">2</div><div style="font-size:10px;color:var(--text-muted)">GK</div></div>
                <div style="padding:10px 0;background:var(--carbon);border-radius:6px;border:1px solid var(--border)"><div style="font-family:var(--font-mono);font-size:16px;font-weight:700">5</div><div style="font-size:10px;color:var(--text-muted)">DEF</div></div>
                <div style="padding:10px 0;background:var(--carbon);border-radius:6px;border:1px solid rgba(200,168,78,0.3)"><div style="font-family:var(--font-mono);font-size:16px;font-weight:700;color:var(--accent-gold)">4</div><div style="font-size:10px;color:var(--accent-gold)">MID</div></div>
                <div style="padding:10px 0;background:var(--carbon);border-radius:6px;border:1px solid var(--border)"><div style="font-family:var(--font-mono);font-size:16px;font-weight:700">3</div><div style="font-size:10px;color:var(--text-muted)">ATT</div></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="journey-step reveal">
          <div class="journey-num">03</div>
          <div class="journey-content">
            <h3>Earn points from real matches</h3>
            <p>When matchday comes, your players earn points for what they actually do on the pitch — goals, assists, clean sheets, saves. Cards, own goals, and missed penalties hurt you. Your captain earns double.</p>
            <div class="journey-details">
              <div class="journey-detail"><div class="journey-detail-icon">⚽</div><div><h4>Automated Scoring</h4><p>Points come in automatically a few hours after each match ends.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/exchange.png" alt="Sub" style="width:20px;height:20px" /></div><div><h4>Auto-Substitution</h4><p>Starter doesn't play? Your first available bench player steps in for them automatically.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/bar-chart.png" alt="Stats" style="width:20px;height:20px" /></div><div><h4>Full Breakdown</h4><p>See exactly what earned or cost each player their points, game by game.</p></div></div>
            </div>
          </div>
          <div class="journey-visual">
            <div class="jv-header">GW 12 — Score Breakdown</div>
            <div class="jv-body" style="padding:0">
              <div style="display:grid;grid-template-columns:1fr auto;padding:14px 16px;border-bottom:1px solid var(--border);align-items:center"><div><div style="font-size:13px;font-weight:600">M. Kudus <span style="font-family:var(--font-mono);font-size:10px;color:var(--accent-gold)">C</span></div><div style="font-size:10px;color:var(--text-muted)">MID · 90 min · Goal + Assist</div></div><div style="font-family:var(--font-mono);font-size:18px;font-weight:700;color:var(--pitch-glow)">24</div></div>
              <div style="display:grid;grid-template-columns:1fr auto;padding:14px 16px;border-bottom:1px solid var(--border);align-items:center"><div><div style="font-size:13px;font-weight:600">K. Baako</div><div style="font-size:10px;color:var(--text-muted)">ATT · 90 min · 2 Goals</div></div><div style="font-family:var(--font-mono);font-size:18px;font-weight:700;color:var(--pitch-glow)">14</div></div>
              <div style="display:grid;grid-template-columns:1fr auto;padding:14px 16px;border-bottom:1px solid var(--border);align-items:center"><div><div style="font-size:13px;font-weight:600">D. Abalora</div><div style="font-size:10px;color:var(--text-muted)">GK · 90 min · Clean Sheet + 3 Saves</div></div><div style="font-family:var(--font-mono);font-size:18px;font-weight:700;color:var(--pitch-glow)">8</div></div>
              <div style="padding:14px 16px;display:flex;justify-content:space-between;background:var(--surface-raised)"><span style="font-size:12px;color:var(--text-muted)">Gameweek Total</span><span style="font-family:var(--font-mono);font-size:16px;font-weight:700;color:var(--pitch-glow)">67 pts</span></div>
            </div>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="journey-step reveal">
          <div class="journey-num">04</div>
          <div class="journey-content">
            <h3>Transfer, compete, repeat</h3>
            <p>Each gameweek you get 2 free transfers. Need more than that? It costs you 4 points each. Keep climbing the rankings, challenge your people in private leagues, and adjust your squad as the season plays out across 34 gameweeks.</p>
            <div class="journey-details">
              <div class="journey-detail"><div class="journey-detail-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/exchange.png" alt="Transfer" style="width:20px;height:20px" /></div><div><h4>2 Free Transfers / GW</h4><p>Fresh 2 every gameweek. Go beyond that and you pay 4 points per extra transfer.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon">⏰</div><div><h4>Deadline Enforced</h4><p>Once the deadline hits, that's it — no more changes. Plan early and don't get caught out.</p></div></div>
              <div class="journey-detail"><div class="journey-detail-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/bar-chart.png" alt="Preview" style="width:20px;height:20px" /></div><div><h4>Preview Before Confirming</h4><p>Before you confirm any transfer, you'll see the full picture — budget change, points cost, everything.</p></div></div>
            </div>
          </div>
          <div class="journey-visual">
            <div class="jv-header">Transfer Preview</div>
            <div class="jv-body">
              <div class="mock-transfer">
                <div class="mock-transfer-player"><div class="mock-transfer-avatar out">OUT</div><div class="mock-transfer-name">A. Mensah<small>₵5.0M · DEF</small></div></div>
                <div class="mock-transfer-arrow">→</div>
                <div class="mock-transfer-player"><div class="mock-transfer-avatar in">IN</div><div class="mock-transfer-name">E. Baffour<small>₵6.0M · DEF</small></div></div>
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                <div style="padding:10px;background:var(--carbon);border-radius:6px;border:1px solid var(--border);text-align:center"><div style="font-family:var(--font-mono);font-size:14px;font-weight:700;color:var(--pitch-glow)">FREE</div><div style="font-size:10px;color:var(--text-muted)">Transfer Cost</div></div>
                <div style="padding:10px;background:var(--carbon);border-radius:6px;border:1px solid var(--border);text-align:center"><div style="font-family:var(--font-mono);font-size:14px;font-weight:700">₵14.2M</div><div style="font-size:10px;color:var(--text-muted)">Budget After</div></div>
              </div>
              <div class="mock-btn">Confirm Transfer</div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- Gameweek Lifecycle -->
    <section class="alt-bg">
      <div class="container">
        <div style="margin-bottom:48px" class="reveal">
          <span class="label">Gameweek Lifecycle</span>
          <h2 style="font-family:var(--font-display);font-size:clamp(28px,3vw,36px);font-weight:700;letter-spacing:-1.2px;margin-top:12px">The rhythm of every<br />GPL gameweek.</h2>
          <p style="font-size:15px;color:var(--text-secondary);margin-top:12px;max-width:520px;line-height:1.7">Each of the 34 gameweeks runs the same way. Once you know the cycle, you'll always be ready.</p>
        </div>
        <div class="lifecycle-grid reveal">
          <div class="lifecycle-step"><div class="lifecycle-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/clipboard.png" alt="Planning" style="width:20px;height:20px" /></div><h4>Planning</h4><p>Browse players, research form, analyze fixtures</p><span class="lifecycle-time">Mon–Thu</span></div>
          <div class="lifecycle-step"><div class="lifecycle-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/exchange.png" alt="Transfers" style="width:20px;height:20px" /></div><h4>Transfers</h4><p>Make transfers, set captain, finalize formation</p><span class="lifecycle-time">Before deadline</span></div>
          <div class="lifecycle-step active"><div class="lifecycle-icon">🔒</div><h4>Deadline</h4><p>Transfers lock. Squad is finalized for matchday</p><span class="lifecycle-time">Fri 11:30 AM</span></div>
          <div class="lifecycle-step"><div class="lifecycle-icon">⚽</div><h4>Matchday</h4><p>Real GPL matches played. Stats collected</p><span class="lifecycle-time">Fri–Sun</span></div>
          <div class="lifecycle-step"><div class="lifecycle-icon"><img src="<?php echo get_template_directory_uri(); ?>/assets/bar-chart.png" alt="Results" style="width:20px;height:20px" /></div><h4>Results</h4><p>Points calculated. Rankings updated. Prices shift</p><span class="lifecycle-time">Sunday night</span></div>
        </div>
      </div>
    </section>

    <section class="page-cta">
      <div class="page-cta-bg"></div>
      <div class="container reveal">
        <h2>Simple enough to start.<br />Deep enough to keep you hooked.</h2>
        <p>Join the waitlist and be ready when that first gameweek kicks off.</p>
        <a href="<?php echo esc_url( home_url( '/#waitlist' ) ); ?>" class="btn-primary">Join the Waitlist →</a>
      </div>
    </section>

<?php get_footer(); ?>
