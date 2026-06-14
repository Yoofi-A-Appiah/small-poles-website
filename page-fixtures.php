<?php
get_header();
$rest_base = rest_url( 'smallpoles/v1/' );
$nonce     = wp_create_nonce( 'wp_rest' );
?>

<div class="spfx-page">

  <!-- Page header -->
  <div class="spfx-hero">
    <div class="container">
      <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>" class="back-link">← Games</a>
      <div class="spfx-hero-body">
        <div>
          <h1 class="spfx-title">Match<br><span class="accent">Schedule</span></h1>
          <p class="spfx-sub">World Cup 2026 &mdash; all fixtures, live scores &amp; stats</p>
        </div>
        <div class="spfx-meta" id="spfx-meta" aria-live="polite"></div>
      </div>
    </div>
  </div>

  <!-- Tab strip -->
  <div class="spfx-tabs-wrap">
    <div class="container">
      <div class="spfx-tabs" id="spfx-tabs" role="tablist" aria-label="Filter by round"></div>
    </div>
  </div>

  <!-- Main content -->
  <div class="container">
    <div id="spfx-body">

      <!-- Skeleton -->
      <div class="spfx-skeleton" id="spfx-skeleton">
        <?php for ( $i = 0; $i < 3; $i++ ) : ?>
        <div class="spfx-sk-group">
          <div class="spfx-sk spfx-sk--label"></div>
          <?php for ( $j = 0; $j < 3; $j++ ) : ?>
          <div class="spfx-sk spfx-sk--card"></div>
          <?php endfor; ?>
        </div>
        <?php endfor; ?>
      </div>

      <!-- Content injected by JS -->
      <div id="spfx-content" hidden></div>

      <!-- Error state -->
      <div class="spfx-empty" id="spfx-error" hidden>
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p>Could not load fixtures. Please try again.</p>
        <button type="button" class="spfx-retry-btn" id="spfx-retry">Retry</button>
      </div>

    </div>
  </div>

</div>

<style>
/* ═══════════════════════════════════════
   FIXTURES PAGE
   ═══════════════════════════════════════ */

.spfx-page { padding-bottom: 100px; }

/* ── Hero header ── */
.spfx-hero {
  padding: 36px 0 28px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 0;
}
.spfx-hero-body {
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 20px; flex-wrap: wrap; margin-top: 12px;
}
.spfx-title {
  font-family: var(--font-display);
  font-size: clamp(2.2rem, 6vw, 3.5rem);
  font-weight: 800; line-height: 1.05; letter-spacing: -.02em;
  color: var(--text-primary); margin: 0 0 6px;
}
.spfx-sub {
  color: var(--text-secondary); font-size: .95rem; margin: 0;
}
.spfx-meta {
  display: flex; gap: 20px; flex-shrink: 0;
}
.spfx-meta-item {
  text-align: center;
}
.spfx-meta-num {
  font-family: var(--font-mono); font-size: 1.5rem; font-weight: 700;
  color: var(--text-primary); line-height: 1;
}
.spfx-meta-num--live { color: #22c55e; }
.spfx-meta-label {
  font-size: 11px; text-transform: uppercase; letter-spacing: .07em;
  color: var(--text-muted); margin-top: 3px;
}

/* ── Tab strip ── */
.spfx-tabs-wrap {
  position: sticky; top: 0; z-index: 40;
  background: var(--carbon); border-bottom: 1px solid var(--border);
  padding: 12px 0;
}
.spfx-tabs {
  display: flex; gap: 6px;
  overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch;
}
.spfx-tabs::-webkit-scrollbar { display: none; }
.spfx-tab {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border-light);
  font-size: 12px; font-weight: 600; letter-spacing: .02em;
  background: var(--surface); color: var(--text-secondary);
  cursor: pointer; white-space: nowrap; flex-shrink: 0;
  transition: border-color .15s, color .15s, background .15s;
  outline-offset: 3px;
}
.spfx-tab:hover   { border-color: var(--pitch-light); color: var(--text-primary); }
.spfx-tab:focus-visible { outline: 2px solid var(--pitch); }
.spfx-tab.active  { background: var(--pitch); border-color: var(--pitch); color: #fff; }
.spfx-tab--live   { border-color: #22c55e66; color: #22c55e; }
.spfx-tab--live.active { background: #166534; border-color: #22c55e; color: #bbf7d0; }

.spfx-tab-count {
  font-size: 10px; font-weight: 700; padding: 1px 5px; border-radius: 10px;
  background: rgba(255,255,255,.1); color: inherit;
}
.spfx-tab.active .spfx-tab-count { background: rgba(255,255,255,.2); }

/* ── Live dot (CSS only, no emoji) ── */
.spfx-live-dot {
  width: 6px; height: 6px; border-radius: 50%; background: #22c55e; flex-shrink: 0;
}
@media (prefers-reduced-motion: no-preference) {
  .spfx-live-dot { animation: spfx-pulse 1.4s ease-in-out infinite; }
}
@keyframes spfx-pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.35;transform:scale(.65)} }

/* ── Round section ── */
.spfx-round { margin-top: 28px; }
.spfx-round.hidden { display: none; }
.spfx-round-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border);
}
.spfx-round-name {
  font-family: var(--font-display); font-size: 11px; font-weight: 700;
  letter-spacing: .1em; text-transform: uppercase; color: var(--text-muted);
}
.spfx-round-count {
  font-size: 11px; color: var(--text-muted);
  background: var(--surface-raised); padding: 2px 8px;
  border-radius: 10px; border: 1px solid var(--border);
}

/* ── Date group ── */
.spfx-date-label {
  font-size: 12px; font-weight: 600; color: var(--text-muted);
  margin: 14px 0 6px; letter-spacing: .03em;
}
.spfx-date-label:first-child { margin-top: 0; }

/* ── Match card ── */
.spfx-card {
  display: grid;
  grid-template-columns: 1fr 128px 1fr;
  grid-template-rows: auto;
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border);
  border-left-width: 3px;
  border-left-color: transparent;
  border-radius: 8px;
  margin-bottom: 6px;
  min-height: 64px;
  padding: 12px 16px;
  transition: border-color .2s, background .2s;
  position: relative;
}
.spfx-card:hover { background: var(--surface-raised); }

.spfx-card--live {
  border-left-color: #22c55e;
  background: color-mix(in srgb, var(--surface) 96%, #22c55e 4%);
}
.spfx-card--live:hover {
  background: color-mix(in srgb, var(--surface-raised) 93%, #22c55e 7%);
}
.spfx-card--finished { border-left-color: var(--accent-gold); }
.spfx-card--postponed { opacity: .6; border-left-color: var(--text-muted); }

/* ── Team columns ── */
.spfx-team {
  display: flex; align-items: center; gap: 10px;
  min-width: 0; /* allow text truncation */
}
.spfx-team--away { flex-direction: row-reverse; }
.spfx-team--away .spfx-team-name { text-align: right; }

.spfx-team-logo {
  width: 36px; height: 36px; object-fit: contain; flex-shrink: 0;
  filter: drop-shadow(0 1px 3px rgba(0,0,0,.4));
}

.spfx-team-name {
  font-size: 14px; font-weight: 700; color: var(--text-primary);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  max-width: 120px;
}
.spfx-team--away .spfx-team-name { text-align: right; }

/* ── Centre column ── */
.spfx-centre {
  display: flex; flex-direction: column; align-items: center;
  gap: 4px; padding: 0 4px;
}

/* Score */
.spfx-score {
  font-family: var(--font-mono); font-size: 22px; font-weight: 700;
  color: var(--text-primary); letter-spacing: .04em; line-height: 1;
  white-space: nowrap;
}
.spfx-card--live .spfx-score { color: #fff; }
.spfx-card--finished .spfx-score { color: var(--text-primary); }

/* Kickoff time (upcoming) */
.spfx-kickoff-time {
  font-family: var(--font-mono); font-size: 17px; font-weight: 600;
  color: var(--text-primary); letter-spacing: .03em; line-height: 1;
}
.spfx-kickoff-date {
  font-size: 11px; color: var(--text-muted); letter-spacing: .02em;
}

/* Live badge */
.spfx-live-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; font-weight: 800; color: #22c55e;
  text-transform: uppercase; letter-spacing: .08em;
}
.spfx-live-elapsed {
  font-family: var(--font-mono); font-size: 12px; font-weight: 700; color: #4ade80;
}

/* Status badge */
.spfx-badge {
  display: inline-flex; align-items: center;
  font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px;
  text-transform: uppercase; letter-spacing: .06em; white-space: nowrap;
}
.spfx-badge--ft  { background: #052e16; color: #4ade80; border: 1px solid #166534; }
.spfx-badge--pst { background: #1c1400; color: #f59e0b; border: 1px solid #92400e; }
.spfx-badge--ht  { background: #1c2a3a; color: #93c5fd; border: 1px solid #1e40af; }

/* Stats button */
.spfx-stats-btn {
  display: inline-flex; align-items: center; gap: 5px;
  background: none; border: 1px solid var(--border-light); border-radius: 4px;
  color: var(--text-secondary); font-size: 11px; font-weight: 600; padding: 3px 10px;
  cursor: pointer; transition: all .15s; margin-top: 1px; white-space: nowrap;
  letter-spacing: .03em;
}
.spfx-stats-btn:hover { border-color: var(--accent-gold); color: var(--accent-gold); }
.spfx-stats-btn:focus-visible { outline: 2px solid var(--pitch); outline-offset: 2px; }
.spfx-stats-btn.is-loading { opacity: .45; pointer-events: none; }
.spfx-stats-btn svg { transition: transform .2s; }
.spfx-stats-btn.is-open svg { transform: rotate(180deg); }

/* ── Stats panel ── */
.spfx-stats-panel {
  grid-column: 1 / -1;
  margin-top: 14px; padding-top: 14px;
  border-top: 1px solid var(--border);
  animation: spfx-slide-down .2s ease-out;
}
@keyframes spfx-slide-down { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
@media (prefers-reduced-motion: reduce) {
  .spfx-stats-panel { animation: none; }
}

.spfx-stats-teams {
  display: grid; grid-template-columns: 1fr auto 1fr;
  gap: 0 10px; margin-bottom: 10px;
}
.spfx-stats-team-name {
  font-size: 12px; font-weight: 700; color: var(--text-secondary);
  text-transform: uppercase; letter-spacing: .06em;
}
.spfx-stats-team-name--away { text-align: right; }
.spfx-stats-vs { font-size: 10px; color: var(--text-muted); text-align: center; }

/* stat row: [val-home] [bar-home | label | bar-away] [val-away] */
.spfx-stat-row {
  display: grid; grid-template-columns: 52px 1fr 80px 1fr 52px;
  align-items: center; gap: 4px 8px; margin-bottom: 8px;
}
.spfx-stat-val {
  font-family: var(--font-mono); font-size: 12px; font-weight: 700;
  color: var(--text-primary);
}
.spfx-stat-val--home { text-align: right; }
.spfx-stat-val--away { text-align: left; }
.spfx-stat-label {
  font-size: 10px; color: var(--text-muted); text-align: center;
  white-space: nowrap; text-transform: uppercase; letter-spacing: .05em;
}
.spfx-stat-bar-wrap {
  position: relative; height: 4px; border-radius: 2px;
  background: var(--border); overflow: hidden;
}
.spfx-stat-bar-fill {
  position: absolute; top: 0; height: 100%;
  transition: width .4s ease-out;
}
@media (prefers-reduced-motion: reduce) { .spfx-stat-bar-fill { transition: none; } }
.spfx-stat-bar-fill--home {
  right: 0; border-radius: 2px 0 0 2px; background: var(--pitch-light);
}
.spfx-stat-bar-fill--away {
  left: 0; border-radius: 0 2px 2px 0; background: var(--accent-blue);
}

/* ── Skeleton ── */
.spfx-skeleton { padding-top: 8px; }
.spfx-sk-group { margin-bottom: 28px; }
.spfx-sk {
  background: var(--surface-raised); border-radius: 6px;
  animation: spfx-sk-pulse 1.6s ease-in-out infinite;
}
@media (prefers-reduced-motion: reduce) { .spfx-sk { animation: none; opacity: .5; } }
.spfx-sk--label { height: 13px; width: 160px; margin-bottom: 12px; }
.spfx-sk--card  { height: 64px; margin-bottom: 6px; }
@keyframes spfx-sk-pulse { 0%,100%{opacity:.5} 50%{opacity:.25} }

/* ── Hidden utility (override display:flex on [hidden]) ── */
[hidden] { display: none !important; }

/* ── Empty / error ── */
.spfx-empty {
  display: flex; flex-direction: column; align-items: center;
  gap: 12px; padding: 60px 20px; text-align: center; color: var(--text-secondary);
}
.spfx-empty svg { color: var(--text-muted); }
.spfx-empty p { margin: 0; font-size: .95rem; }
.spfx-retry-btn {
  padding: 8px 20px; border-radius: 6px; border: 1px solid var(--border-light);
  background: var(--surface-raised); color: var(--text-secondary);
  font-size: 14px; cursor: pointer; transition: all .15s;
}
.spfx-retry-btn:hover { border-color: var(--pitch); color: var(--text-primary); }

/* ── Responsive ── */
@media (max-width: 640px) {
  .spfx-hero { padding: 24px 0 20px; }
  .spfx-title { font-size: 2rem; }
  .spfx-meta { gap: 14px; }
  .spfx-meta-num { font-size: 1.25rem; }

  .spfx-card {
    grid-template-columns: 1fr 88px 1fr;
    padding: 10px 8px 10px 10px;
    min-height: 56px;
    gap: 6px;
  }
  .spfx-team { justify-content: flex-start; }
  .spfx-team--away { justify-content: flex-end; }
  .spfx-team-logo { width: 26px; height: 26px; }
  .spfx-team-name { font-size: 11px; max-width: 72px; }
  .spfx-centre { padding: 0; }
  .spfx-score { font-size: 16px; }
  .spfx-kickoff-time { font-size: 13px; }
  .spfx-kickoff-date { font-size: 10px; }
  .spfx-badge { font-size: 9px; padding: 1px 5px; }
  .spfx-stats-btn { font-size: 10px; padding: 2px 7px; }

  .spfx-stat-row { grid-template-columns: 34px 1fr 64px 1fr 34px; gap: 3px; }
  .spfx-stat-val { font-size: 11px; }
  .spfx-stat-label { font-size: 9px; }
}

@media (max-width: 380px) {
  .spfx-card { grid-template-columns: 30px 80px 30px; gap: 4px; }
  .spfx-team-name { display: none; }
  .spfx-team-logo { width: 24px; height: 24px; }
  .spfx-score { font-size: 15px; }
  .spfx-kickoff-time { font-size: 12px; }
}
</style>

<script>
(function () {
  'use strict';

  var REST_BASE = <?php echo wp_json_encode( $rest_base ); ?>;
  var NONCE     = <?php echo wp_json_encode( $nonce ); ?>;

  var LIVE_STATUSES     = ['1H','2H','ET','P','BT','INT','LIVE'];
  var HT_STATUSES       = ['HT'];
  var FINISHED_STATUSES = ['FT','AET','PEN','AWD','WO'];
  var POSTPONED         = ['PST','CANC','ABD'];

  function apiFetch(path) {
    return fetch(REST_BASE + path, { headers: { 'X-WP-Nonce': NONCE } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
  }

  /* ── String helpers ── */
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function fmtTime(iso) {
    return new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
  }

  function fmtDateFull(iso) {
    return new Date(iso).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
  }

  function fmtDateKey(iso) {
    var d = new Date(iso);
    return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();
  }

  function roundLabel(round) {
    return round
      .replace(/Group Stage - (\d+)/, 'Group Stage — Matchday $1')
      .replace(/^Round of /, 'Round of ');
  }

  function roundShort(round) {
    return round
      .replace(/Group Stage - (\d+)/, 'GS·MD$1')
      .replace(/^Round of (\d+)$/, 'R$1')
      .replace(/^Quarter-finals$/, 'QF')
      .replace(/^Semi-finals$/, 'SF')
      .replace(/^Final$/, 'Final')
      .replace(/3rd Place Final/, '3rd');
  }

  /* ── Status helpers ── */
  function isLive(status)     { return LIVE_STATUSES.indexOf(status) > -1; }
  function isHT(status)       { return HT_STATUSES.indexOf(status) > -1; }
  function isFinished(status) { return FINISHED_STATUSES.indexOf(status) > -1; }
  function isPostponed(status){ return POSTPONED.indexOf(status) > -1; }

  /* ── Status badge html ── */
  function statusBadge(status) {
    var label = status === 'AET' ? 'AET' : status === 'PEN' ? 'Pens' : status;
    var cls   = (status === 'HT') ? 'spfx-badge--ht'
              : isFinished(status) ? 'spfx-badge--ft'
              : isPostponed(status) ? 'spfx-badge--pst' : '';
    if (!cls) return '';
    return '<span class="spfx-badge ' + cls + '">' + esc(label) + '</span>';
  }

  /* ── Stats toggle button svg ── */
  var CHEVRON_SVG = '<svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="2 4 6 8 10 4"/></svg>';

  /* ── Match card ── */
  function matchCard(f) {
    var fx      = f.fixture;
    var teams   = f.teams;
    var goals   = f.goals;
    var status  = fx.status.short;
    var elapsed = fx.status.elapsed;

    var live     = isLive(status);
    var ht       = isHT(status);
    var finished = isFinished(status);
    var postponed = isPostponed(status);
    var upcoming = !live && !ht && !finished && !postponed;

    /* Card modifier class */
    var cardClass = live ? ' spfx-card--live'
                  : finished ? ' spfx-card--finished'
                  : postponed ? ' spfx-card--postponed' : '';

    /* Centre column */
    var centre = '';
    if (live) {
      var scoreStr = (goals.home !== null ? goals.home : 0) + ' – ' + (goals.away !== null ? goals.away : 0);
      centre = '<span class="spfx-score">' + esc(scoreStr) + '</span>'
             + '<span class="spfx-live-badge"><span class="spfx-live-dot" aria-hidden="true"></span>'
             + '<span class="spfx-live-elapsed">' + esc(elapsed ? elapsed + "'" : 'LIVE') + '</span>'
             + '</span>';
    } else if (ht) {
      var scoreStr = (goals.home !== null ? goals.home : 0) + ' – ' + (goals.away !== null ? goals.away : 0);
      centre = '<span class="spfx-score">' + esc(scoreStr) + '</span>'
             + '<span class="spfx-badge spfx-badge--ht">HT</span>';
    } else if (finished) {
      var scoreStr = String(goals.home) + ' – ' + String(goals.away);
      centre = '<span class="spfx-score">' + esc(scoreStr) + '</span>'
             + statusBadge(status)
             + '<button class="spfx-stats-btn" data-fid="' + esc(fx.id) + '" type="button"'
             +   ' aria-label="View match statistics" aria-expanded="false">'
             +   CHEVRON_SVG + 'Stats'
             + '</button>';
    } else if (postponed) {
      centre = '<span class="spfx-kickoff-time">' + esc(fmtTime(fx.date)) + '</span>'
             + '<span class="spfx-badge spfx-badge--pst">PST</span>';
    } else {
      centre = '<span class="spfx-kickoff-time">' + esc(fmtTime(fx.date)) + '</span>'
             + '<span class="spfx-kickoff-date">' + esc(fmtDateFull(fx.date)) + '</span>';
    }

    return '<div class="spfx-card' + cardClass + '" data-fid="' + esc(fx.id) + '" data-date="' + esc(fmtDateKey(fx.date)) + '">'
      + '<div class="spfx-team">'
      +   '<img class="spfx-team-logo" src="' + esc(teams.home.logo) + '" alt="' + esc(teams.home.name) + '" width="36" height="36" loading="lazy">'
      +   '<span class="spfx-team-name">' + esc(teams.home.name) + '</span>'
      + '</div>'
      + '<div class="spfx-centre">' + centre + '</div>'
      + '<div class="spfx-team spfx-team--away">'
      +   '<img class="spfx-team-logo" src="' + esc(teams.away.logo) + '" alt="' + esc(teams.away.name) + '" width="36" height="36" loading="lazy">'
      +   '<span class="spfx-team-name">' + esc(teams.away.name) + '</span>'
      + '</div>'
    + '</div>';
  }

  /* ── Stats panel ── */
  function buildStatsPanel(data, homeTeamName, awayTeamName) {
    if (!data || !data.length) return null;

    var teamA = data[0];
    var teamB = data[1] || { statistics: [], team: { name: awayTeamName } };
    var nameA = (teamA.team && teamA.team.name) || homeTeamName;
    var nameB = (teamB.team && teamB.team.name) || awayTeamName;

    var statMapB = {};
    (teamB.statistics || []).forEach(function (s) { statMapB[s.type] = s.value; });

    var rowsHtml = (teamA.statistics || []).map(function (s) {
      var rawA = s.value !== null && s.value !== undefined ? s.value : '–';
      var rawB = statMapB[s.type] !== undefined && statMapB[s.type] !== null ? statMapB[s.type] : '–';

      /* Parse percentage values for bar */
      var pctA = 0, pctB = 0, hasBar = false;
      if (typeof rawA === 'string' && rawA.endsWith('%')) {
        pctA = parseFloat(rawA) || 0; pctB = 100 - pctA; hasBar = true;
      } else if (typeof rawB === 'string' && rawB.endsWith('%')) {
        pctB = parseFloat(rawB) || 0; pctA = 100 - pctB; hasBar = true;
      } else if (rawA !== '–' && rawB !== '–') {
        var nA = parseFloat(rawA) || 0, nB = parseFloat(rawB) || 0;
        var total = nA + nB;
        if (total > 0) { pctA = (nA / total) * 100; pctB = (nB / total) * 100; hasBar = true; }
      }

      /* Columns: home-val | home-bar | label | away-bar | away-val */
      var homeBar = hasBar
        ? '<div class="spfx-stat-bar-wrap"><div class="spfx-stat-bar-fill spfx-stat-bar-fill--home" style="width:' + pctA.toFixed(1) + '%"></div></div>'
        : '<div></div>';
      var awayBar = hasBar
        ? '<div class="spfx-stat-bar-wrap"><div class="spfx-stat-bar-fill spfx-stat-bar-fill--away" style="width:' + pctB.toFixed(1) + '%"></div></div>'
        : '<div></div>';

      return '<div class="spfx-stat-row">'
        + '<span class="spfx-stat-val spfx-stat-val--home">' + esc(rawA) + '</span>'
        + homeBar
        + '<span class="spfx-stat-label">' + esc(s.type) + '</span>'
        + awayBar
        + '<span class="spfx-stat-val spfx-stat-val--away">' + esc(rawB) + '</span>'
      + '</div>';
    }).join('');

    return '<div class="spfx-stats-panel">'
      + '<div class="spfx-stats-teams">'
      +   '<span class="spfx-stats-team-name">' + esc(nameA) + '</span>'
      +   '<span class="spfx-stats-vs">VS</span>'
      +   '<span class="spfx-stats-team-name spfx-stats-team-name--away">' + esc(nameB) + '</span>'
      + '</div>'
      + rowsHtml
    + '</div>';
  }

  /* ── Load stats on demand ── */
  function loadStats(btn) {
    var fid     = btn.dataset.fid;
    var card    = btn.closest('.spfx-card');
    var existing = card.querySelector('.spfx-stats-panel');

    /* Toggle off */
    if (existing) {
      existing.remove();
      btn.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      return;
    }

    btn.classList.add('is-loading');
    btn.textContent = 'Loading…';

    var homeImg = card.querySelector('.spfx-team:not(.spfx-team--away) .spfx-team-logo');
    var awayImg = card.querySelector('.spfx-team--away .spfx-team-logo');
    var homeTeam = homeImg ? homeImg.alt : '';
    var awayTeam = awayImg ? awayImg.alt : '';

    apiFetch('fixture-stats?fixture=' + encodeURIComponent(fid))
      .then(function (data) {
        btn.classList.remove('is-loading');
        btn.innerHTML = CHEVRON_SVG + 'Stats';

        var panel = buildStatsPanel(data, homeTeam, awayTeam);
        if (!panel) {
          btn.textContent = 'No stats';
          btn.disabled = true;
          return;
        }

        card.insertAdjacentHTML('beforeend', panel);
        btn.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
      })
      .catch(function () {
        btn.classList.remove('is-loading');
        btn.innerHTML = CHEVRON_SVG + 'Stats';
        btn.title = 'Failed to load stats — click to retry';
      });
  }

  /* ── Main render ── */
  function render() {
    document.getElementById('spfx-skeleton').hidden = false;
    document.getElementById('spfx-content').hidden  = true;
    document.getElementById('spfx-error').hidden    = true;

    apiFetch('fixtures').then(function (fixtures) {
      if (!Array.isArray(fixtures) || !fixtures.length) {
        showError(); return;
      }

      /* Counters for meta bar */
      var total    = fixtures.length;
      var liveCount    = 0;
      var finishedCount = 0;

      /* Group by round (preserve API order) */
      var roundOrder = [];
      var roundMap   = {};
      var liveRounds = {};

      fixtures.forEach(function (f) {
        var s = f.fixture.status.short;
        if (isLive(s) || isHT(s)) { liveCount++; liveRounds[f.league.round] = true; }
        if (isFinished(s)) finishedCount++;

        var r = f.league.round;
        if (!roundMap[r]) { roundOrder.push(r); roundMap[r] = []; }
        roundMap[r].push(f);
      });

      var upcomingCount = total - liveCount - finishedCount;

      /* Meta bar */
      var metaHtml = ''
        + metaItem(total, 'Matches')
        + metaItem(finishedCount, 'Played')
        + metaItem(liveCount, 'Live', true)
        + metaItem(upcomingCount, 'Upcoming');
      document.getElementById('spfx-meta').innerHTML = metaHtml;

      /* Tabs */
      var tabsHtml = '<button class="spfx-tab active" data-round="all" role="tab" aria-selected="true">All <span class="spfx-tab-count">' + total + '</span></button>';

      if (liveCount > 0) {
        tabsHtml += '<button class="spfx-tab spfx-tab--live" data-round="live" role="tab" aria-selected="false">'
          + '<span class="spfx-live-dot" aria-hidden="true"></span>'
          + 'Live <span class="spfx-tab-count">' + liveCount + '</span>'
          + '</button>';
      }

      roundOrder.forEach(function (round) {
        var count = roundMap[round].length;
        tabsHtml += '<button class="spfx-tab" data-round="' + esc(round) + '" role="tab" aria-selected="false">'
          + esc(roundShort(round)) + ' <span class="spfx-tab-count">' + count + '</span>'
          + '</button>';
      });

      /* Body */
      var bodyHtml = '';

      roundOrder.forEach(function (round) {
        var matches = roundMap[round];
        bodyHtml += '<section class="spfx-round" data-round="' + esc(round) + '" aria-label="' + esc(roundLabel(round)) + '">'
          + '<div class="spfx-round-header">'
          +   '<span class="spfx-round-name">' + esc(roundLabel(round)) + '</span>'
          +   '<span class="spfx-round-count">' + matches.length + ' matches</span>'
          + '</div>';

        /* Sub-group by date */
        var dateOrder = [];
        var dateMap   = {};
        matches.forEach(function (f) {
          var dk = fmtDateKey(f.fixture.date);
          if (!dateMap[dk]) { dateOrder.push(dk); dateMap[dk] = { label: fmtDateFull(f.fixture.date), items: [] }; }
          dateMap[dk].items.push(f);
        });

        dateOrder.forEach(function (dk) {
          var grp = dateMap[dk];
          if (dateOrder.length > 1) {
            bodyHtml += '<p class="spfx-date-label">' + esc(grp.label) + '</p>';
          }
          grp.items.forEach(function (f) { bodyHtml += matchCard(f); });
        });

        bodyHtml += '</section>';
      });

      document.getElementById('spfx-tabs').innerHTML     = tabsHtml;
      document.getElementById('spfx-content').innerHTML  = bodyHtml;

      document.getElementById('spfx-skeleton').hidden = true;
      document.getElementById('spfx-content').hidden  = false;

      /* ── Tab click handler ── */
      document.getElementById('spfx-tabs').addEventListener('click', function (e) {
        var btn = e.target.closest('.spfx-tab');
        if (!btn) return;

        document.querySelectorAll('.spfx-tab').forEach(function (t) {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');

        var round = btn.dataset.round;
        document.querySelectorAll('.spfx-round').forEach(function (el) {
          if (round === 'all') {
            el.classList.remove('hidden');
          } else if (round === 'live') {
            el.classList.toggle('hidden', !liveRounds[el.dataset.round]);
          } else {
            el.classList.toggle('hidden', el.dataset.round !== round);
          }
        });

        /* Scroll tab into view */
        btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
      });

      /* ── Stats click handler ── */
      document.getElementById('spfx-content').addEventListener('click', function (e) {
        var btn = e.target.closest('.spfx-stats-btn');
        if (btn) loadStats(btn);
      });

    }).catch(showError);
  }

  function metaItem(num, label, isLiveItem) {
    var numClass = isLiveItem && num > 0 ? ' spfx-meta-num--live' : '';
    return '<div class="spfx-meta-item">'
      + '<div class="spfx-meta-num' + numClass + '">' + num + '</div>'
      + '<div class="spfx-meta-label">' + label + '</div>'
    + '</div>';
  }

  function showError() {
    document.getElementById('spfx-skeleton').hidden = true;
    document.getElementById('spfx-content').hidden  = true;
    document.getElementById('spfx-error').hidden    = false;
  }

  document.getElementById('spfx-retry').addEventListener('click', render);

  render();

})();
</script>

<?php get_footer(); ?>
