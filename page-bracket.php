<?php get_header(); ?>

<div class="bracket-wrap">
  <div class="container">

    <div class="bracket-header">
      <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>" class="back-link">← Games</a>
      <h1>World Cup 2026<br /><span class="accent">Bracket Challenge</span></h1>
      <p class="bracket-sub">Pick every winner from Group Stage to the Final. Save and share your bracket.</p>
      <div class="bracket-header-actions">
        <button class="btn-ghost" id="bracketReset">Reset</button>
        <button class="btn-primary" id="bracketShare">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
          Share
        </button>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="bracket-progress-wrap">
      <div class="bracket-progress-bar">
        <div class="bracket-progress-fill" id="bracketProgressFill" style="width:0%"></div>
      </div>
      <span class="bracket-progress-label" id="bracketProgressLabel">0 / 64 picks made</span>
    </div>

    <!-- Group Stage -->
    <section class="bracket-section">
      <div class="bracket-section-header">
        <h2>Group Stage</h2>
        <p>Pick 1st and 2nd place from each group. The 8 best 3rd-placed teams also advance.</p>
      </div>
      <div class="bracket-groups" id="bracketGroups">
        <!-- Rendered by JS -->
        <div class="bracket-loading">Loading groups…</div>
      </div>
    </section>

    <!-- Knockout Bracket -->
    <section class="bracket-section" id="knockoutSection" style="display:none">
      <div class="bracket-section-header">
        <h2>Knockout Stage</h2>
        <p>Click a team to advance them to the next round.</p>
      </div>
      <div class="bracket-knockout" id="bracketKnockout">
        <!-- Rendered by JS -->
      </div>
    </section>

  </div>
</div>

<?php get_footer(); ?>
