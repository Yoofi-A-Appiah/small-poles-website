<?php
get_header();
$sq_user = sp_squads_get_page_user();
?>

<div class="squad-wrap">
  <div class="container">

    <div class="squad-header">
      <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>" class="back-link">← Games</a>
      <h1>World Cup<br /><span class="accent">Squad Builder</span></h1>
      <p class="squad-sub">Pick your ultimate World Cup XI. Stay within budget. Share your squad.</p>
    </div>

    <div id="squadUserBar" class="squad-user-bar<?php echo $sq_user ? '' : ' squad-user-bar--hidden'; ?>">
      <span class="squad-user-name">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        <span id="squadUserNameText"><?php echo $sq_user ? esc_html( $sq_user['display_name'] ) : ''; ?></span>
      </span>
      <button type="button" id="squadLogoutBtn" class="squad-logout-btn">Log out</button>
    </div>

    <!-- Nation + Formation picker -->
    <div class="squad-controls">
      <div class="squad-control-group">
        <label class="squad-label">Nation</label>
        <button type="button" class="squad-nation-toggle" id="squadNationToggle" aria-expanded="false">
          <span id="squadNationToggleLabel">Select a nation</span>
          <svg class="squad-nation-toggle-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="2 4 6 8 10 4"/></svg>
        </button>
        <div class="squad-nation-grid" id="squadNationGrid" aria-hidden="true">
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
          <button class="btn-ghost squad-save-btn" id="squadSaveBtn" disabled>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Squad
          </button>
          <button class="btn-ghost squad-mysquads-btn" id="squadMySquadsBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/></svg>
            My Squads
          </button>
          <button class="btn-primary squad-share-btn" id="squadShareBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
            Share Squad
          </button>
        </div>
        <div id="squadPointsBar" class="squad-points-bar" style="display:none" role="status" aria-live="polite" aria-atomic="true" aria-label="Squad fantasy points">
          <div class="squad-points-bar-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 9H3.5a2.5 2.5 0 0 1 0-5H6"/>
              <path d="M18 9h2.5a2.5 2.5 0 0 0 0-5H18"/>
              <path d="M4 22h16"/>
              <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
              <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
              <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/>
            </svg>
          </div>
          <div class="squad-points-bar-info">
            <span class="squad-points-bar-label">Fantasy Points</span>
            <span class="squad-points-bar-value"><span id="squadPointsTotal">0</span> pts</span>
          </div>
        </div>
        <p id="squadSaveHint" class="squad-save-hint">Select all 11 players to save your squad</p>
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

<div id="squadSaveToast" role="status" aria-live="polite"></div>

<?php get_footer(); ?>
