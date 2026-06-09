<?php get_header(); ?>

<div class="bracket-wrap">
  <div class="container">

    <div class="bracket-header">
      <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>" class="back-link">← Games</a>
      <h1>World Cup 2026<br /><span class="accent">Bracket Challenge</span></h1>
      <p class="bracket-sub">Pick 1st, 2nd &amp; 3rd in every group — the 8 best 3rd-place teams also advance. Then call every knockout winner.</p>
      <div class="bracket-header-actions">
        <button class="btn-ghost" id="bracketReset">Reset</button>
        <button class="btn-primary" id="bracketShare">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
          Share
        </button>
      </div>
    </div>

    <!-- Progress -->
    <div class="bracket-progress-wrap">
      <div class="bracket-progress-bar">
        <div class="bracket-progress-fill" id="bracketProgressFill" style="width:0%"></div>
      </div>
      <span class="bracket-progress-label" id="bracketProgressLabel">0 / 64 picks made</span>
    </div>

    <!-- Step tabs -->
    <div class="bracket-tabs">
      <div class="bracket-tab bracket-tab--active" id="tab-groups">① Groups</div>
      <div class="bracket-tab-sep">›</div>
      <div class="bracket-tab" id="tab-knockout">② Knockout</div>
    </div>

    <!-- Step 1: Group Stage -->
    <section id="stepGroups">
      <div class="bracket-section-header">
        <h2>Group Stage</h2>
        <p>Click to rank: once → 1st, twice → 2nd, three times → 3rd. Top 2 from each group advance automatically; pick your 3rd-place team too — the <strong>8 best 3rds</strong> across all groups also go through to the Round of 32.</p>
      </div>
      <div class="bracket-groups" id="bracketGroups">
        <div class="bracket-loading">Loading groups…</div>
      </div>
      <div class="bracket-step-nav">
        <button class="btn-primary" id="btnToKnockout" style="display:none">
          Build the Bracket →
        </button>
      </div>
    </section>

    <!-- Step 2: Knockout Bracket -->
    <section id="stepKnockout" style="display:none">
      <div class="bracket-section-header">
        <h2>Knockout Stage</h2>
        <p>Click a team to advance them. Click the winner again to undo.</p>
      </div>
      <div class="bracket-knockout" id="bracketKnockout"></div>
      <div class="bracket-step-nav">
        <button class="btn-ghost" id="btnBackToGroups">← Back to Groups</button>
      </div>
    </section>

  </div>
</div>

<?php get_footer(); ?>
