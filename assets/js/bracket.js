/* World Cup 2026 Bracket Challenge v3
   2-step flow: Groups (pick 1st/2nd/3rd) → Knockout. */

;(function () {
  'use strict';

  var REST      = (window.spBracket && window.spBracket.restBase) || '/wp-json/smallpoles/v1/';
  var STORE_KEY = 'sp_bracket_2026_v3';

  /* ── Fallback groups ── */
  var FALLBACK_GROUPS = {
    A: [{name:'USA',logo:''},{name:'Panama',logo:''},{name:'Uruguay',logo:''},{name:'Bolivia',logo:''}],
    B: [{name:'Mexico',logo:''},{name:'Jamaica',logo:''},{name:'Venezuela',logo:''},{name:'New Zealand',logo:''}],
    C: [{name:'Canada',logo:''},{name:'Honduras',logo:''},{name:'Chile',logo:''},{name:'Morocco',logo:''}],
    D: [{name:'Ecuador',logo:''},{name:'Croatia',logo:''},{name:'Egypt',logo:''},{name:'Cameroon',logo:''}],
    E: [{name:'Brazil',logo:''},{name:'Switzerland',logo:''},{name:'Japan',logo:''},{name:'Serbia',logo:''}],
    F: [{name:'Argentina',logo:''},{name:'Qatar',logo:''},{name:'Colombia',logo:''},{name:'El Salvador',logo:''}],
    G: [{name:'Germany',logo:''},{name:'Costa Rica',logo:''},{name:'Ivory Coast',logo:''},{name:'Thailand',logo:''}],
    H: [{name:'Spain',logo:''},{name:'Angola',logo:''},{name:'Ukraine',logo:''},{name:'Portugal',logo:''}],
    I: [{name:'England',logo:''},{name:'Tunisia',logo:''},{name:'DR Congo',logo:''},{name:'Iran',logo:''}],
    J: [{name:'France',logo:''},{name:'Poland',logo:''},{name:'Algeria',logo:''},{name:'Australia',logo:''}],
    K: [{name:'South Korea',logo:''},{name:'Turkey',logo:''},{name:'Iraq',logo:''},{name:'Saudi Arabia',logo:''}],
    L: [{name:'Netherlands',logo:''},{name:'Senegal',logo:''},{name:'Nigeria',logo:''},{name:'Ghana',logo:''}],
  };

  var FLAGS = {
    'USA':'🇺🇸','Mexico':'🇲🇽','Canada':'🇨🇦','Brazil':'🇧🇷','Argentina':'🇦🇷',
    'France':'🇫🇷','England':'🏴󠁧󠁢󠁥󠁮󠁧󠁿','Germany':'🇩🇪','Spain':'🇪🇸','Portugal':'🇵🇹',
    'Netherlands':'🇳🇱','Belgium':'🇧🇪','Croatia':'🇭🇷','Switzerland':'🇨🇭',
    'Uruguay':'🇺🇾','Colombia':'🇨🇴','Ecuador':'🇪🇨','Japan':'🇯🇵','South Korea':'🇰🇷',
    'Morocco':'🇲🇦','Senegal':'🇸🇳','Nigeria':'🇳🇬','Ghana':'🇬🇭','Egypt':'🇪🇬',
    'Algeria':'🇩🇿','Tunisia':'🇹🇳','Cameroon':'🇨🇲','Ivory Coast':'🇨🇮',
    'Iran':'🇮🇷','Saudi Arabia':'🇸🇦','Australia':'🇦🇺','New Zealand':'🇳🇿',
    'Serbia':'🇷🇸','Turkey':'🇹🇷','Poland':'🇵🇱','Chile':'🇨🇱','Venezuela':'🇻🇪',
    'Bolivia':'🇧🇴','Honduras':'🇭🇳','Costa Rica':'🇨🇷','Jamaica':'🇯🇲',
    'Panama':'🇵🇦','El Salvador':'🇸🇻','Qatar':'🇶🇦','Iraq':'🇮🇶',
    'DR Congo':'🇨🇩','Congo DR':'🇨🇩','Angola':'🇦🇴','Ukraine':'🇺🇦',
    'Thailand':'🇹🇭','Peru':'🇵🇪','Denmark':'🇩🇰','Austria':'🇦🇹',
    'Hungary':'🇭🇺','Uzbekistan':'🇺🇿','Ireland':'🇮🇪','Italy':'🇮🇹',
    'South Africa':'🇿🇦',
  };
  function flag(name) { return FLAGS[name] || '🏳️'; }

  /* ── R32 seeding ──────────────────────────────────────────────────────
     Matches 0-11: fixed 1st vs 2nd matchups.
     Matches 12-15 (slots 24-31): 8 best 3rd-place teams.
     Each pair feeds into one R16 match.
  ─────────────────────────────────────────────────────────────────────── */
  var R32_SEEDING = [
    {pos:'f',g:'A'}, {pos:'s',g:'B'},
    {pos:'f',g:'C'}, {pos:'s',g:'D'},
    {pos:'f',g:'B'}, {pos:'s',g:'A'},
    {pos:'f',g:'D'}, {pos:'s',g:'C'},
    {pos:'f',g:'E'}, {pos:'s',g:'F'},
    {pos:'f',g:'G'}, {pos:'s',g:'H'},
    {pos:'f',g:'F'}, {pos:'s',g:'E'},
    {pos:'f',g:'H'}, {pos:'s',g:'G'},
    {pos:'f',g:'I'}, {pos:'s',g:'J'},
    {pos:'f',g:'K'}, {pos:'s',g:'L'},
    {pos:'f',g:'J'}, {pos:'s',g:'I'},
    {pos:'f',g:'L'}, {pos:'s',g:'K'},
    // slots 24-31: 8 best 3rd-place teams (filled from group 3rd picks)
    {pos:'t'}, {pos:'t'}, {pos:'t'}, {pos:'t'},
    {pos:'t'}, {pos:'t'}, {pos:'t'}, {pos:'t'},
  ];

  /* ── State ──────────────────────────────────────────────────────────── */
  var groups = {};
  var state;

  function defaultState() {
    return {
      groups: {},  // { A: { first:'', second:'', third:'' }, ... }
      r32:    Array(32).fill(''),
      r16:    Array(16).fill(''),
      qf:     Array(8).fill(''),
      sf:     Array(4).fill(''),
      final:  Array(2).fill(''),
      winner: '',
      step:   'groups',
    };
  }

  function loadState() {
    try { var r = localStorage.getItem(STORE_KEY); return r ? JSON.parse(r) : null; } catch(e) { return null; }
  }
  function saveState() {
    try { localStorage.setItem(STORE_KEY, JSON.stringify(state)); } catch(e) {} }

  /* ── Progress ───────────────────────────────────────────────────────── */
  function countPicks() {
    var n = 0;
    Object.keys(state.groups).forEach(function(g) {
      var gs = state.groups[g];
      if (gs.first)  n++;
      if (gs.second) n++;
      if (gs.third)  n++;
    });
    ['r32','r16','qf','sf','final'].forEach(function(k) {
      state[k].forEach(function(t) { if (t) n++; });
    });
    if (state.winner) n++;
    return n;
  }

  function updateProgress() {
    var n   = countPicks();
    var pct = Math.min(100, Math.round(n / 64 * 100));
    document.getElementById('bracketProgressFill').style.width  = pct + '%';
    document.getElementById('bracketProgressLabel').textContent = n + ' / 64 picks made';
  }

  /* ── Step management ─────────────────────────────────────────────────── */
  function showStep(step) {
    state.step = step;
    document.getElementById('stepGroups').style.display   = step === 'groups'   ? 'block' : 'none';
    document.getElementById('stepKnockout').style.display = step === 'knockout' ? 'block' : 'none';
    document.getElementById('tab-groups').classList.toggle('bracket-tab--active',   step === 'groups');
    document.getElementById('tab-knockout').classList.toggle('bracket-tab--active', step === 'knockout');
    if (step === 'knockout') { buildR32(); renderKnockout(); }
    saveState();
  }

  function allGroupsDone() {
    return Object.keys(groups).every(function(g) {
      var gs = state.groups[g];
      return gs && gs.first && gs.second;
    });
  }

  /* ── Group stage ─────────────────────────────────────────────────────── */
  function renderGroups() {
    var el = document.getElementById('bracketGroups');
    el.innerHTML = '';

    Object.keys(groups).sort().filter(function(k) { return k.length === 1; }).forEach(function(letter) {
      var teams = groups[letter];
      var gs    = state.groups[letter] || { first: '', second: '', third: '' };

      var card = document.createElement('div');
      card.className = 'bracket-group-card';
      card.innerHTML = '<div class="bgroup-header"><span class="bgroup-letter">Group ' + letter + '</span></div>';

      teams.forEach(function(team) {
        var name = typeof team === 'string' ? team : team.name;
        var logo = typeof team === 'object' ? (team.logo || '') : '';
        var pos  = gs.first === name ? 'first' : gs.second === name ? 'second' : gs.third === name ? 'third' : '';

        var row = document.createElement('div');
        row.className = 'bgroup-team' + (pos ? ' bgroup-team--selected bgroup-team--' + pos : '');

        var badge = pos === 'first'  ? '<span class="bgroup-badge bgroup-badge--1">1st</span>'
                  : pos === 'second' ? '<span class="bgroup-badge bgroup-badge--2">2nd</span>'
                  : pos === 'third'  ? '<span class="bgroup-badge bgroup-badge--3">3rd</span>'
                  : '';

        var imgHtml = logo
          ? '<img src="' + logo + '" class="bgroup-logo" alt="" loading="lazy" />'
          : '<span class="bgroup-flag">' + flag(name) + '</span>';

        row.innerHTML = imgHtml + '<span class="bgroup-name">' + name + '</span>' + badge;
        row.addEventListener('click', function() { handleGroupClick(letter, name); });
        card.appendChild(row);
      });

      el.appendChild(card);
    });

    document.getElementById('btnToKnockout').style.display = allGroupsDone() ? 'inline-flex' : 'none';
  }

  function handleGroupClick(letter, name) {
    if (!state.groups[letter]) state.groups[letter] = { first: '', second: '', third: '' };
    var gs = state.groups[letter];

    if (gs.first === name) {
      // De-select 1st → shift up
      gs.first  = gs.second;
      gs.second = gs.third;
      gs.third  = '';
    } else if (gs.second === name) {
      gs.second = gs.third;
      gs.third  = '';
    } else if (gs.third === name) {
      gs.third = '';
    } else if (!gs.first) {
      gs.first = name;
    } else if (!gs.second) {
      gs.second = name;
    } else if (!gs.third) {
      gs.third = name;
    } else {
      // All 3 taken — replace 1st, shift others down
      gs.third  = gs.second;
      gs.second = gs.first;
      gs.first  = name;
    }

    saveState();
    renderGroups();
    updateProgress();
  }

  /* ── Build R32 from group results ────────────────────────────────────── */
  function buildR32() {
    // Fixed 24 slots: 1st/2nd place matchups
    for (var i = 0; i < 24; i++) {
      var seed = R32_SEEDING[i];
      var gs   = state.groups[seed.g] || {};
      var name = seed.pos === 'f' ? gs.first : gs.second;
      if (name) state.r32[i] = name;
    }
    // Slots 24-31: 8 best 3rd-place teams (alphabetical group order, first 8)
    var thirds = [];
    Object.keys(groups).sort().forEach(function(letter) {
      var gs = state.groups[letter] || {};
      if (gs.third) thirds.push(gs.third);
    });
    for (var j = 0; j < 8; j++) {
      state.r32[24 + j] = thirds[j] || '';
    }
    saveState();
  }

  /* ── Knockout rendering ──────────────────────────────────────────────── */
  var ROUNDS = [
    { key:'r32',   label:'Round of 32',    count:32 },
    { key:'r16',   label:'Round of 16',    count:16 },
    { key:'qf',    label:'Quarter-finals', count:8  },
    { key:'sf',    label:'Semi-finals',    count:4  },
    { key:'final', label:'Final',          count:2  },
  ];

  var activeRound = 0;

  function renderKnockout() {
    var el = document.getElementById('bracketKnockout');
    el.innerHTML = '';
    if (window.innerWidth < 768) {
      renderKnockoutMobile(el);
    } else {
      renderKnockoutDesktop(el);
    }
    updateProgress();
  }

  /* Desktop: mirrored bracket tree — Final at centre ──────────────────── */
  function renderKnockoutDesktop(container) {
    var wrap = document.createElement('div');
    wrap.className = 'bko-tree';

    // Left half uses matches 0..(halfCount-1); right half uses halfCount..(fullCount-1)
    var HALF = [
      { key:'r32', label:'Round of 32',    nk:'r16',   fullCount:16, halfCount:8 },
      { key:'r16', label:'Round of 16',    nk:'qf',    fullCount:8,  halfCount:4 },
      { key:'qf',  label:'Quarter-finals', nk:'sf',    fullCount:4,  halfCount:2 },
      { key:'sf',  label:'Semi-finals',    nk:'final', fullCount:2,  halfCount:1 },
    ];

    function buildHalfCol(roundDef, isRight) {
      var col = document.createElement('div');
      col.className = 'bko-col bko-col--half' + (isRight ? ' bko-col--right-half' : '');

      var lbl = document.createElement('div');
      lbl.className = 'bko-col-label';
      lbl.textContent = roundDef.label;
      col.appendChild(lbl);

      // Matches live in their own flex:1 container so the label is excluded
      // from the space-around calculation — this keeps each round's boxes
      // vertically centred between the two matches that feed into them.
      var matchWrap = document.createElement('div');
      matchWrap.className = 'bko-col-matches';

      var teams  = state[roundDef.key];
      var startM = isRight ? roundDef.halfCount : 0;
      var endM   = isRight ? roundDef.fullCount : roundDef.halfCount;
      var count  = endM - startM;

      for (var m = startM; m < endM; m++) {
        var localM  = m - startM;
        var matchEl = document.createElement('div');
        if (count === 1) {
          matchEl.className = 'bko-match bko-match--single-' + (isRight ? 'right' : 'left');
        } else if (isRight) {
          matchEl.className = 'bko-match ' + (localM % 2 === 0 ? 'bko-match--right-top' : 'bko-match--right-bot');
        } else {
          matchEl.className = 'bko-match ' + (localM % 2 === 0 ? 'bko-match--top' : 'bko-match--bot');
        }
        [teams[m * 2] || '', teams[m * 2 + 1] || ''].forEach(function(team) {
          matchEl.appendChild(buildSlot(team, 0, roundDef.nk, m));
        });
        matchWrap.appendChild(matchEl);
      }
      col.appendChild(matchWrap);
      return col;
    }

    // ── Left half: R32 → R16 → QF → SF ──────────────────────────────────
    HALF.forEach(function(r) { wrap.appendChild(buildHalfCol(r, false)); });

    // ── Centre: Final + Champion ─────────────────────────────────────────
    // Champion is absolutely positioned so it doesn't shrink the match area;
    // the single final match then aligns at the same vertical midpoint as
    // the two SF matches flanking it.
    var centerCol = document.createElement('div');
    centerCol.className = 'bko-col bko-col--center';

    var centerLbl = document.createElement('div');
    centerLbl.className = 'bko-col-label';
    centerLbl.textContent = 'Final';
    centerCol.appendChild(centerLbl);

    var centerMatchWrap = document.createElement('div');
    centerMatchWrap.className = 'bko-col-matches';

    var finalMatch = document.createElement('div');
    finalMatch.className = 'bko-match';
    [state.final[0] || '', state.final[1] || ''].forEach(function(team) {
      finalMatch.appendChild(buildSlot(team, 0, 'winner', 0));
    });
    centerMatchWrap.appendChild(finalMatch);
    centerCol.appendChild(centerMatchWrap);

    var champDiv = document.createElement('div');
    champDiv.className = 'bko-champion';
    champDiv.innerHTML = state.winner
      ? '<span class="bko-champ-flag">' + flag(state.winner) + '</span><span>' + state.winner + '</span>'
      : '<span class="bko-champ-empty">🏆</span>';
    centerCol.appendChild(champDiv);
    wrap.appendChild(centerCol);

    // ── Right half: SF → QF → R16 → R32 (reversed, fanning out from centre) ──
    var rightCols = HALF.map(function(r) { return buildHalfCol(r, true); });
    rightCols.reverse().forEach(function(col) { wrap.appendChild(col); });

    container.appendChild(wrap);
  }

  /* Mobile: tab per round ─────────────────────────────────────────────── */
  function renderKnockoutMobile(container) {
    var tabs = document.createElement('div');
    tabs.className = 'bko-mob-tabs';

    var tabLabels = ['R32','R16','QF','SF','Final','🏆'];
    tabLabels.forEach(function(lbl, ri) {
      var tab = document.createElement('button');
      tab.className = 'bko-mob-tab' + (ri === activeRound ? ' bko-mob-tab--active' : '');
      tab.textContent = lbl;
      tab.addEventListener('click', function() { activeRound = ri; renderKnockout(); });
      tabs.appendChild(tab);
    });
    container.appendChild(tabs);

    if (activeRound === 5) {
      var champEl = document.createElement('div');
      champEl.className = 'bko-mob-champion';
      champEl.innerHTML = state.winner
        ? '<span>' + flag(state.winner) + '</span><strong>' + state.winner + '</strong>'
        : '<span>🏆</span><strong>Pick your champion</strong>';
      container.appendChild(champEl);
      return;
    }

    var round     = ROUNDS[activeRound];
    var teams     = state[round.key];
    var nextKey   = activeRound < ROUNDS.length - 1 ? ROUNDS[activeRound + 1].key : 'winner';
    var matchCount = teams.length / 2;

    for (var m = 0; m < matchCount; m++) {
      var matchEl = document.createElement('div');
      matchEl.className = 'bko-mob-match';

      var lbl = document.createElement('div');
      lbl.className = 'bko-mob-match-label';
      lbl.textContent = 'Match ' + (m + 1);
      matchEl.appendChild(lbl);

      [teams[m * 2] || '', teams[m * 2 + 1] || ''].forEach(function(team) {
        var slot = buildSlot(team, activeRound, nextKey, m);
        slot.classList.add('bko-mob-slot');
        matchEl.appendChild(slot);
      });
      container.appendChild(matchEl);
    }
  }

  function buildSlot(team, roundIdx, nextKey, nextSlot) {
    var slot = document.createElement('div');
    slot.className = 'bko-slot' + (!team ? ' bko-slot--empty' : '');

    var winner = nextKey === 'winner' ? state.winner : state[nextKey][nextSlot];
    if (team && team === winner) slot.classList.add('bko-slot--winner');

    var logo = getTeamLogo(team);
    var imgHtml = logo
      ? '<img src="' + logo + '" class="bko-slot-logo" alt="" loading="lazy" />'
      : '<span class="bko-slot-flag">' + (team ? flag(team) : '') + '</span>';

    slot.innerHTML = imgHtml + '<span class="bko-slot-name">' + (team || '—') + '</span>';

    if (team) {
      slot.addEventListener('click', (function(t, nk, ns) {
        return function() { advanceTeam(t, nk, ns); };
      })(team, nextKey, nextSlot));
    }
    return slot;
  }

  function getTeamLogo(name) {
    if (!name) return '';
    var letters = Object.keys(groups);
    for (var i = 0; i < letters.length; i++) {
      var g = groups[letters[i]];
      for (var j = 0; j < g.length; j++) {
        var t = g[j];
        if ((t.name || t) === name && t.logo) return t.logo;
      }
    }
    return '';
  }

  function advanceTeam(team, roundKey, slot) {
    if (roundKey === 'winner') {
      state.winner = (state.winner === team) ? '' : team;
    } else {
      state[roundKey][slot] = (state[roundKey][slot] === team) ? '' : team;
      // Cascade: clear any downstream picks of this team
      var roundIdx = -1;
      for (var i = 0; i < ROUNDS.length; i++) { if (ROUNDS[i].key === roundKey) { roundIdx = i; break; } }
      for (var ri = roundIdx + 1; ri < ROUNDS.length; ri++) {
        var ds = Math.floor(slot / Math.pow(2, ri - roundIdx));
        if (state[ROUNDS[ri].key][ds] === team) state[ROUNDS[ri].key][ds] = '';
      }
      if (!state[roundKey][slot]) state.winner = '';
    }
    saveState();
    renderKnockout();
  }

  /* ── Share ───────────────────────────────────────────────────────────── */
  function buildSnapContainer(title, contentEl, opts) {
    var snap = document.createElement('div');
    snap.style.cssText = [
      'position:fixed', 'top:0', 'left:-9999px',
      'padding:28px 28px 32px', 'background:#0a0a0a', 'box-sizing:border-box',
      opts.width ? 'width:' + opts.width : 'width:max-content',
    ].join(';');

    var titleEl = document.createElement('div');
    titleEl.style.cssText = [
      'font-family:system-ui,sans-serif', 'font-size:17px', 'font-weight:700',
      'color:#ffffff', 'text-align:center', 'letter-spacing:0.06em',
      'margin-bottom:20px', 'text-transform:uppercase', 'white-space:nowrap',
    ].join(';');
    titleEl.textContent = title;
    snap.appendChild(titleEl);
    snap.appendChild(contentEl);
    return snap;
  }

  function dispatchBlob(blob, filename, shareTitle, btn) {
    var file = new File([blob], filename, { type: 'image/png' });
    if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
      navigator.share({ title: shareTitle, files: [file] }).catch(function(){});
    } else {
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url; a.download = filename; a.click();
      URL.revokeObjectURL(url);
    }
    resetBtn(btn);
  }

  function captureGroups(btn) {
    var gridClone = document.getElementById('bracketGroups').cloneNode(true);
    var snap = buildSnapContainer('WC 2026 — My Group Stage Picks', gridClone, { width: '940px' });
    document.body.appendChild(snap);

    html2canvas(snap, { backgroundColor: '#0a0a0a', scale: 2, useCORS: true, logging: false })
      .then(function(canvas) {
        document.body.removeChild(snap);
        canvas.toBlob(function(blob) { dispatchBlob(blob, 'wc-2026-groups.png', 'WC 2026 Group Stage Picks', btn); });
      })
      .catch(function() {
        if (document.body.contains(snap)) document.body.removeChild(snap);
        shareText(); resetBtn(btn);
      });
  }

  function captureKnockout(btn) {
    var koClone = document.getElementById('bracketKnockout').cloneNode(true);
    // Let the tree expand to its full natural width for the screenshot
    var treeEl = koClone.querySelector('.bko-tree');
    if (treeEl) { treeEl.style.overflow = 'visible'; treeEl.style.minWidth = 'max-content'; }

    var snap = buildSnapContainer('WC 2026 — My Knockout Bracket', koClone, {});
    document.body.appendChild(snap);

    html2canvas(snap, { backgroundColor: '#0a0a0a', scale: 1.5, useCORS: true, logging: false })
      .then(function(canvas) {
        document.body.removeChild(snap);
        canvas.toBlob(function(blob) { dispatchBlob(blob, 'wc-2026-knockout.png', 'WC 2026 Knockout Bracket', btn); });
      })
      .catch(function() {
        if (document.body.contains(snap)) document.body.removeChild(snap);
        shareText(); resetBtn(btn);
      });
  }

  document.getElementById('bracketShare').addEventListener('click', function() {
    var btn = this;
    btn.textContent = 'Capturing…';
    btn.disabled = true;
    if (typeof html2canvas === 'undefined') { shareText(); resetBtn(btn); return; }
    if (state.step === 'knockout') { captureKnockout(btn); } else { captureGroups(btn); }
  });

  function shareText() {
    var lines = ['WC 2026 — My Group Stage Picks', 'smallpoles.online/games/bracket/', ''];
    Object.keys(state.groups).sort().forEach(function(g) {
      var gs = state.groups[g] || {};
      lines.push('Group ' + g + ': ' + (gs.first||'?') + ' · ' + (gs.second||'?') + ' · ' + (gs.third||'?'));
    });
    var text = lines.join('\n');
    if (navigator.share) navigator.share({ text: text }).catch(function(){});
    else navigator.clipboard && navigator.clipboard.writeText(text).catch(function(){});
  }

  function resetBtn(btn) {
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg> Share';
  }

  /* ── Reset ──────────────────────────────────────────────────────────── */
  document.getElementById('bracketReset').addEventListener('click', function() {
    if (!confirm('Reset your bracket? This cannot be undone.')) return;
    localStorage.removeItem(STORE_KEY);
    state = defaultState();
    activeRound = 0;
    renderGroups();
    showStep('groups');
    updateProgress();
  });

  document.getElementById('btnToKnockout').addEventListener('click', function() {
    showStep('knockout');
  });
  document.getElementById('btnBackToGroups').addEventListener('click', function() {
    showStep('groups');
  });

  window.addEventListener('resize', function() {
    if (state.step === 'knockout') renderKnockout();
  });

  /* ── Bootstrap ──────────────────────────────────────────────────────── */
  function init(groupData) {
    groups = groupData;
    var saved = loadState();
    state = saved || defaultState();
    if (!state.step) state.step = 'groups';

    renderGroups();
    updateProgress();
    showStep(state.step);
  }

  fetch(REST + 'wc-groups')
    .then(function(r) { return r.ok ? r.json() : null; })
    .then(function(data) { init(data && Object.keys(data).length >= 6 ? data : FALLBACK_GROUPS); })
    .catch(function() { init(FALLBACK_GROUPS); });

})();
