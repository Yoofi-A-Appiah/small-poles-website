<?php get_header(); ?>

<div class="squad-wrap">
  <div class="container">

    <div class="squad-header">
      <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>" class="back-link">← Games</a>
      <h1>World Cup<br /><span class="accent">Squad Builder</span></h1>
      <p class="squad-sub">Pick your ultimate World Cup XI. Stay within budget. Share your squad.</p>
    </div>

    <!-- Nation + Formation picker -->
    <div class="squad-controls">
      <div class="squad-control-group">
        <label class="squad-label">Nation</label>
        <div class="squad-nation-grid" id="squadNationGrid">
          <!-- Rendered by JS -->
        </div>
      </div>
      <div class="squad-control-group squad-control-group--right">
        <label class="squad-label">Formation</label>
        <div class="squad-formation-row" id="squadFormationRow">
          <button class="squad-formation-btn active" data-formation="4-3-3">4-3-3</button>
          <button class="squad-formation-btn" data-formation="4-4-2">4-4-2</button>
          <button class="squad-formation-btn" data-formation="3-5-2">3-5-2</button>
          <button class="squad-formation-btn" data-formation="4-2-3-1">4-2-3-1</button>
        </div>
        <div class="squad-budget-row">
          <span class="squad-label">Budget</span>
          <span class="squad-budget-used" id="squadBudgetUsed">0</span>
          <span class="squad-budget-sep">/</span>
          <span class="squad-budget-total">100</span>
          <span class="squad-budget-unit">pts</span>
          <div class="squad-budget-bar-wrap"><div class="squad-budget-bar" id="squadBudgetBar" style="width:0%"></div></div>
        </div>
        <div class="squad-team-name-wrap">
          <label class="squad-label" for="squadTeamName">Squad name</label>
          <input type="text" id="squadTeamName" class="squad-team-name-input" placeholder="My World Cup XI" maxlength="32" autocomplete="off" />
        </div>
      </div>
    </div>

    <!-- Pitch + player list -->
    <div class="squad-layout">

      <!-- Pitch visual -->
      <div class="squad-pitch-wrap">
        <div class="squad-pitch" id="squadPitch">
          <!-- Player dots rendered by JS -->
        </div>
        <div class="squad-pitch-actions">
          <button class="btn-ghost squad-clear-btn" id="squadClear">Clear Squad</button>
          <button class="btn-primary squad-share-btn" id="squadShareBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
            Share Squad
          </button>
        </div>
      </div>

      <!-- Player pool -->
      <div class="squad-pool-wrap">
        <div class="squad-pool-header">
          <h3 id="squadPoolTitle">Select a nation to browse players</h3>
          <div class="squad-pool-filters">
            <button class="squad-filter-btn active" data-pos="ALL">All</button>
            <button class="squad-filter-btn" data-pos="GK">GK</button>
            <button class="squad-filter-btn" data-pos="DEF">DEF</button>
            <button class="squad-filter-btn" data-pos="MID">MID</button>
            <button class="squad-filter-btn" data-pos="FWD">FWD</button>
          </div>
        </div>
        <div class="squad-pool" id="squadPool">
          <!-- Rendered by JS -->
        </div>
      </div>

    </div>

  </div>
</div>

<?php get_footer(); ?>
