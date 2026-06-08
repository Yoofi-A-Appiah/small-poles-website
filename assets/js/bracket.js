/* World Cup 2026 Bracket Challenge
   Groups fed from the WP REST API (standings endpoint).
   Knockout bracket is client-side only. State saved to localStorage. */

;(function () {
  'use strict';

  var REST = (window.spBracket && window.spBracket.restBase) || '/wp-json/smallpoles/v1/';
  var STORE_KEY = 'sp_bracket_2026_v1';

  /* ── WC 2026 groups fallback (used if API unavailable) ────────── */
  var FALLBACK_GROUPS = {
    A: ['USA','Panama','Uruguay','Bolivia'],
    B: ['Mexico','Jamaica','Venezuela','New Zealand'],
    C: ['Canada','Honduras','Chile','Morocco'],
    D: ['Ecuador','Croatia','Egypt','Cameroon'],
    E: ['Brazil','Switzerland','Japan','Serbia'],
    F: ['Argentina','Qatar','Colombia','El Salvador'],
    G: ['Germany','Costa Rica','Ivory Coast','Thailand'],
    H: ['Spain','Angola','Ukraine','Portugal'],
    I: ['England','Tunisia','DR Congo','Iran'],
    J: ['France','Poland','Algeria','Australia'],
    K: ['South Korea','Turkey','Iraq','Saudi Arabia'],
    L: ['Netherlands','Senegal','Nigeria','Ghana'],
  };

  /* ── Confederation flag emoji map ─────────────────────────────── */
  var FLAGS = {
    'USA':'🇺🇸','Mexico':'🇲🇽','Canada':'🇨🇦','Brazil':'🇧🇷','Argentina':'🇦🇷',
    'France':'🇫🇷','England':'🏴󠁧󠁢󠁥󠁮󠁧󠁿','Germany':'🇩🇪','Spain':'🇪🇸','Portugal':'🇵🇹',
    'Netherlands':'🇳🇱','Belgium':'🇧🇪','Croatia':'🇭🇷','Switzerland':'🇨🇭',
    'Uruguay':'🇺🇾','Colombia':'🇨🇴','Ecuador':'🇪🇨','Japan':'🇯🇵','South Korea':'🇰🇷',
    'Morocco':'🇲🇦','Senegal':'🇸🇳','Nigeria':'🇳🇬','Ghana':'🇬🇭','Egypt':'🇪🇬',
    'Algeria':'🇩🇿','Tunisia':'🇹🇳','Cameroon':'🇨🇲','Ivory Coast':'🇨🇮',
    'Iran':'🇮🇷','Saudi Arabia':'🇸🇦','Australia':'🇦🇺','New Zealand':'🇳🇿',
    'Serbia':'🇷🇸','Denmark':'🇩🇰','Turkey':'🇹🇷','Poland':'🇵🇱',
    'Chile':'🇨🇱','Venezuela':'🇻🇪','Bolivia':'🇧🇴','Honduras':'🇭🇳',
    'Costa Rica':'🇨🇷','Jamaica':'🇯🇲','Panama':'🇵🇦','El Salvador':'🇸🇻',
    'Qatar':'🇶🇦','Iraq':'🇮🇶','DR Congo':'🇨🇩','Angola':'🇦🇴',
    'Ukraine':'🇺🇦','Ivory Coast':'🇨🇮','Thailand':'🇹🇭',
    'Croatia':'🇭🇷','Ecuador':'🇪🇨',
  };

  function flag(name) { return FLAGS[name] || '🏳️'; }

  /* ── State ─────────────────────────────────────────────────────── */
  var state;

  function loadState() {
    try {
      var raw = localStorage.getItem(STORE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch(e) { return null; }
  }

  function saveState() {
    try { localStorage.setItem(STORE_KEY, JSON.stringify(state)); } catch(e){}
  }

  function defaultState(groups) {
    return {
      groups: {},       // { A: { first:'', second:'', thirds:[] }, ... }
      r32: Array(32).fill(''),
      r16: Array(16).fill(''),
      qf:  Array(8).fill(''),
      sf:  Array(4).fill(''),
      final: Array(2).fill(''),
      winner: '',
    };
  }

  /* ── Progress ──────────────────────────────────────────────────── */
  function updateProgress() {
    var groupPicks = 0;
    Object.keys(state.groups).forEach(function(g) {
      if (state.groups[g] && state.groups[g].first)  groupPicks++;
      if (state.groups[g] && state.groups[g].second) groupPicks++;
    });
    var knockoutPicks = 0;
    ['r32','r16','qf','sf','final'].forEach(function(round) {
      state[round].forEach(function(t){ if(t) knockoutPicks++; });
    });
    if (state.winner) knockoutPicks++;

    var total = groupPicks + knockoutPicks;
    var max   = 24 + 32 + 16 + 8 + 4 + 2 + 1; // simplified
    var pct   = Math.min(100, Math.round(total / 64 * 100));

    document.getElementById('bracketProgressFill').style.width  = pct + '%';
    document.getElementById('bracketProgressLabel').textContent = total + ' / 64 picks made';
  }

  /* ── Group stage rendering ─────────────────────────────────────── */
  var groups = {};

  function renderGroups() {
    var el = document.getElementById('bracketGroups');
    el.innerHTML = '';

    Object.keys(groups).sort().forEach(function(letter) {
      var teams = groups[letter];
      var gs    = state.groups[letter] || { first: '', second: '' };

      var card = document.createElement('div');
      card.className = 'bracket-group-card';

      var header = document.createElement('div');
      header.className = 'bgroup-header';
      header.innerHTML = '<span class="bgroup-letter">Group ' + letter + '</span>';
      card.appendChild(header);

      teams.forEach(function(team) {
        var row = document.createElement('div');
        row.className = 'bgroup-team';
        if (gs.first === team || gs.second === team) row.classList.add('bgroup-team--selected');

        var badge = '';
        if (gs.first === team)  badge = '<span class="bgroup-badge bgroup-badge--1">1st</span>';
        if (gs.second === team) badge = '<span class="bgroup-badge bgroup-badge--2">2nd</span>';

        row.innerHTML = '<span class="bgroup-flag">' + flag(team) + '</span>'
          + '<span class="bgroup-name">' + team + '</span>'
          + badge;

        row.addEventListener('click', function() {
          handleGroupClick(letter, team);
        });

        card.appendChild(row);
      });

      el.appendChild(card);
    });
  }

  function handleGroupClick(letter, team) {
    if (!state.groups[letter]) state.groups[letter] = { first: '', second: '' };
    var gs = state.groups[letter];

    if (gs.first === team) {
      // De-select 1st
      gs.first = gs.second;
      gs.second = '';
    } else if (gs.second === team) {
      gs.second = '';
    } else if (!gs.first) {
      gs.first = team;
    } else if (!gs.second) {
      gs.second = team;
    } else {
      // Both slots full — bump 1st to 2nd, set clicked as 1st
      gs.second = gs.first;
      gs.first  = team;
    }

    saveState();
    renderGroups();
    updateProgress();
    checkShowKnockout();
  }

  function checkShowKnockout() {
    var allDone = Object.keys(groups).every(function(g) {
      var gs = state.groups[g];
      return gs && gs.first && gs.second;
    });
    document.getElementById('knockoutSection').style.display = allDone ? 'block' : 'none';
    if (allDone) renderKnockout();
  }

  /* ── Knockout rendering ────────────────────────────────────────── */
  var ROUNDS = [
    { key:'r32',   label:'Round of 32',  slots:32 },
    { key:'r16',   label:'Round of 16',  slots:16 },
    { key:'qf',    label:'Quarter-finals', slots:8 },
    { key:'sf',    label:'Semi-finals',  slots:4 },
    { key:'final', label:'Final',        slots:2 },
  ];

  function getR32Teams() {
    /* Build the R32 from group results */
    var groupLetters = Object.keys(groups).sort();
    var firsts  = groupLetters.map(function(g){ return (state.groups[g] && state.groups[g].first) || ''; });
    var seconds = groupLetters.map(function(g){ return (state.groups[g] && state.groups[g].second) || ''; });

    /* Standard WC 2026 R32 seeding:
       1A v 2C, 1C v 2A, 1B v 2D, 1D v 2B,
       1E v 2G, 1G v 2E, 1F v 2H, 1H v 2F,
       1I v 2K, 1K v 2I, 1J v 2L, 1L v 2J,
       + 4 best 3rd-placed teams (simplified: just show slots) */
    var idx = { A:0, B:1, C:2, D:3, E:4, F:5, G:6, H:7, I:8, J:9, K:10, L:11 };
    function f(g) { return firsts[idx[g]]  || '?'; }
    function s(g) { return seconds[idx[g]] || '?'; }

    return [
      f('A'), s('C'), f('C'), s('A'),
      f('B'), s('D'), f('D'), s('B'),
      f('E'), s('G'), f('G'), s('E'),
      f('F'), s('H'), f('H'), s('F'),
      f('I'), s('K'), f('K'), s('I'),
      f('J'), s('L'), f('L'), s('J'),
      '3rd Best 1', '3rd Best 2', '3rd Best 3', '3rd Best 4',
      '3rd Best 5', '3rd Best 6', '3rd Best 7', '3rd Best 8',
    ];
  }

  function renderKnockout() {
    var el = document.getElementById('bracketKnockout');
    el.innerHTML = '';

    var r32Teams = getR32Teams();
    /* Pre-fill R32 slots with group results */
    for (var i = 0; i < 32; i++) {
      if (!state.r32[i] && r32Teams[i] && r32Teams[i] !== '?') {
        state.r32[i] = r32Teams[i];
      }
    }

    ROUNDS.forEach(function(round, ri) {
      var section = document.createElement('div');
      section.className = 'bko-round';

      var roundLabel = document.createElement('div');
      roundLabel.className = 'bko-round-label';
      roundLabel.textContent = round.label;
      section.appendChild(roundLabel);

      var teams = ri === 0 ? state.r32 : state[round.key];
      var matches = Math.floor(teams.length / 2);

      for (var m = 0; m < matches; m++) {
        var matchEl = document.createElement('div');
        matchEl.className = 'bko-match';

        var t1 = teams[m * 2]     || '';
        var t2 = teams[m * 2 + 1] || '';

        var nextKey  = ROUNDS[ri + 1] ? ROUNDS[ri + 1].key : 'winner';
        var nextSlot = m;

        [t1, t2].forEach(function(team, ti) {
          var slot = document.createElement('div');
          slot.className = 'bko-slot';
          if (!team) slot.classList.add('bko-slot--empty');

          var currentWinner = ri < ROUNDS.length - 1
            ? state[ROUNDS[ri + 1].key][nextSlot]
            : state.winner;

          if (team && team === currentWinner) slot.classList.add('bko-slot--winner');

          slot.innerHTML = flag(team) + ' ' + (team || '—');

          if (team && team !== '?' && !team.startsWith('3rd')) {
            slot.addEventListener('click', (function(t, nk, ns) {
              return function() { advanceTeam(t, nk, ns); };
            })(team, nextKey, nextSlot));
          }

          matchEl.appendChild(slot);
        });

        section.appendChild(matchEl);
      }

      el.appendChild(section);
    });

    /* Champion */
    var champSection = document.createElement('div');
    champSection.className = 'bko-round bko-round--champion';
    champSection.innerHTML = '<div class="bko-round-label">Champion</div>'
      + '<div class="bko-champion">'
      + (state.winner ? flag(state.winner) + ' ' + state.winner : '🏆 ?')
      + '</div>';
    el.appendChild(champSection);

    saveState();
    updateProgress();
  }

  function advanceTeam(team, roundKey, slot) {
    if (roundKey === 'winner') {
      state.winner = (state.winner === team) ? '' : team;
    } else {
      state[roundKey][slot] = (state[roundKey][slot] === team) ? '' : team;
      /* Clear downstream picks when a selection changes */
      var roundIdx = ROUNDS.findIndex(function(r){ return r.key === roundKey; });
      for (var ri = roundIdx + 1; ri < ROUNDS.length; ri++) {
        var nextSlot = Math.floor(slot / Math.pow(2, ri - roundIdx));
        if (state[ROUNDS[ri].key][nextSlot] === team || !state[roundKey][slot]) {
          state[ROUNDS[ri].key][nextSlot] = '';
        }
      }
      if (!state[roundKey][slot]) state.winner = '';
    }
    saveState();
    renderKnockout();
  }

  /* ── Share ─────────────────────────────────────────────────────── */
  document.getElementById('bracketShare').addEventListener('click', function() {
    var lines = ['🏆 My World Cup 2026 Bracket'];
    lines.push('');
    Object.keys(state.groups).sort().forEach(function(g) {
      var gs = state.groups[g] || {};
      lines.push('Group ' + g + ': ' + (gs.first || '?') + ' · ' + (gs.second || '?'));
    });
    if (state.winner) lines.push('\nChampion: ' + state.winner + ' 🏆');
    lines.push('\nsmallpoles.online/games/bracket/');

    var text = lines.join('\n');
    if (navigator.share) {
      navigator.share({ text: text }).catch(function(){});
    } else {
      navigator.clipboard.writeText(text).then(function(){
        document.getElementById('bracketShare').textContent = 'Copied!';
        setTimeout(function(){ document.getElementById('bracketShare').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg> Share'; }, 2000);
      }).catch(function(){});
    }
  });

  document.getElementById('bracketReset').addEventListener('click', function() {
    if (!confirm('Reset your bracket? This cannot be undone.')) return;
    localStorage.removeItem(STORE_KEY);
    state = defaultState(groups);
    renderGroups();
    document.getElementById('knockoutSection').style.display = 'none';
    updateProgress();
  });

  /* ── Bootstrap ─────────────────────────────────────────────────── */
  function init(groupData) {
    groups = groupData;
    var saved = loadState();
    state = saved || defaultState(groups);

    renderGroups();
    updateProgress();
    checkShowKnockout();
  }

  /* Groups are fixed at draw time — use curated data directly */
  init(FALLBACK_GROUPS);

})();
