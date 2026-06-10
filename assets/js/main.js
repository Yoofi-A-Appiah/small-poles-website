
// Scroll reveal
const reveals = document.querySelectorAll('.reveal');
if (reveals.length) {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
      });
    },
    { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
  );
  reveals.forEach((el) => observer.observe(el));
}

// Mobile nav toggle
const mobileToggle = document.querySelector('.mobile-toggle');
const navLinks = document.getElementById('navLinks');
const navOverlay = document.getElementById('navOverlay');

function closeNav() {
  navLinks.classList.remove('open');
  navOverlay.classList.remove('open');
  document.body.style.overflow = '';
}

if (mobileToggle && navLinks) {
  mobileToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    navOverlay.classList.toggle('open', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });
}

if (navOverlay) {
  navOverlay.addEventListener('click', closeNav);
}

if (navLinks) {
  navLinks.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeNav);
  });
}

// Announcement bar dismiss (persists across pages via localStorage)
const annBar = document.querySelector('.announcement-bar');
if (annBar) {
  if (localStorage.getItem('ann_wc2026_dismissed')) {
    annBar.style.display = 'none';
    document.body.classList.add('ann-dismissed');
  }
  const dismissBtn = annBar.querySelector('.ann-dismiss');
  if (dismissBtn) {
    dismissBtn.addEventListener('click', () => {
      annBar.style.display = 'none';
      document.body.classList.add('ann-dismissed');
      localStorage.setItem('ann_wc2026_dismissed', '1');
    });
  }
}

// ── Predictions Widgets ─────────────────────────────────────────────
;(function () {
  var SP = window.spData;
  if (!SP) return;

  function apiFetch(path) {
    return fetch(SP.restBase + path, { headers: { 'X-WP-Nonce': SP.nonce } })
      .then(function (r) {
        if (!r.ok) throw new Error(r.status);
        return r.json();
      });
  }

  function pct(val) { return parseFloat(val) || 0; }

  function teamBlock(team, align) {
    return '<div class="sp-team sp-team--' + align + '">'
      + '<img src="' + team.logo + '" alt="' + team.name + '" class="sp-team-logo" loading="lazy" />'
      + '<span class="sp-team-name">' + team.name + '</span>'
      + '</div>';
  }

  function compRow(label, homeVal, awayVal) {
    var h = pct(homeVal), a = pct(awayVal);
    return '<div class="sp-comp-row">'
      + '<span class="sp-comp-val">' + h + '%</span>'
      + '<div class="sp-comp-bars">'
      +   '<div class="sp-comp-bar sp-comp-bar--home" style="width:' + h + '%"></div>'
      +   '<div class="sp-comp-bar sp-comp-bar--away" style="width:' + a + '%"></div>'
      + '</div>'
      + '<span class="sp-comp-val sp-comp-val--away">' + a + '%</span>'
      + '<span class="sp-comp-label">' + label + '</span>'
      + '</div>';
  }

  function oddsBlock(homeLabel, awayLabel, bets) {
    var vals = (bets && bets[0] && bets[0].values) ? bets[0].values : [];
    function odd(name) {
      var match = vals.find(function (v) { return v.value === name; });
      return match ? match.odd : '–';
    }
    return '<div class="sp-odds">'
      + '<div class="sp-odd sp-odd--home"><span>' + homeLabel + '</span><strong>' + odd('Home') + '</strong></div>'
      + '<div class="sp-odd"><span>Draw</span><strong>' + odd('Draw') + '</strong></div>'
      + '<div class="sp-odd sp-odd--away"><span>' + awayLabel + '</span><strong>' + odd('Away') + '</strong></div>'
      + '</div>';
  }

  function renderWidget(container, fixture, predData, oddsData) {
    var home = fixture ? fixture.teams.home : (predData && predData.teams ? predData.teams.home : null);
    var away = fixture ? fixture.teams.away : (predData && predData.teams ? predData.teams.away : null);
    if (!home || !away) { showError(container, 'Match data unavailable.'); return; }

    var pred   = (predData && predData.predictions) ? predData.predictions : {};
    var comp   = (predData && predData.comparison)  ? predData.comparison  : {};
    var winner = pred.winner || {};
    var league = fixture ? fixture.league : (predData ? predData.league : {});
    var preds  = pred.percent || {};

    // Win probability — prefer predictions.percent, fall back to comparison.form
    var hPct = pct(preds.home  || (comp.form ? comp.form.home  : 0));
    var dPct = pct(preds.draw  || 0);
    var aPct = pct(preds.away  || (comp.form ? comp.form.away  : 0));

    var dateHtml = '';
    if (fixture && fixture.fixture && fixture.fixture.date) {
      var d = new Date(fixture.fixture.date);
      var dateStr = d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
      var timeStr = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
      dateHtml = '<span class="sp-widget-time">' + dateStr + ' · ' + timeStr + '</span>';
    }

    var roundLabel = (league && league.round) ? ' · ' + league.round : '';
    var leagueName = (league && league.name) ? league.name : '';

    var oddsHtml = '';
    if (oddsData && oddsData.bookmakers && oddsData.bookmakers[0]) {
      oddsHtml = oddsBlock(home.name, away.name, oddsData.bookmakers[0].bets);
    }

    // Comparison rows — only render rows with non-zero values; prefer h2h & goals
    function hasVal(v) { return pct(v) > 0; }
    var compRows = [];
    if (comp.h2h   && (hasVal(comp.h2h.home)   || hasVal(comp.h2h.away)))   compRows.push(compRow('H2H',    comp.h2h.home,   comp.h2h.away));
    if (comp.goals && (hasVal(comp.goals.home)  || hasVal(comp.goals.away))) compRows.push(compRow('Goals',  comp.goals.home, comp.goals.away));
    if (comp.form  && (hasVal(comp.form.home)   || hasVal(comp.form.away)))  compRows.push(compRow('Form',   comp.form.home,  comp.form.away));
    if (comp.att   && (hasVal(comp.att.home)    || hasVal(comp.att.away)))   compRows.push(compRow('Attack', comp.att.home,   comp.att.away));
    if (comp.def   && (hasVal(comp.def.home)    || hasVal(comp.def.away)))   compRows.push(compRow('Defence',comp.def.home,   comp.def.away));

    // Fall back to last_5 if comparison had no useful data and teams are present
    if (!compRows.length && predData && predData.teams) {
      var ht  = predData.teams.home, at  = predData.teams.away;
      var hl5 = (ht && ht.last_5) ? ht.last_5 : null;
      var al5 = (at && at.last_5) ? at.last_5 : null;
      if (hl5 && al5 && (hl5.played || al5.played)) {
        if (hasVal(hl5.form) || hasVal(al5.form)) compRows.push(compRow('Form',   hl5.form, al5.form));
        if (hasVal(hl5.att)  || hasVal(al5.att))  compRows.push(compRow('Attack', hl5.att,  al5.att));
        if (hasVal(hl5.def)  || hasVal(al5.def))  compRows.push(compRow('Defence',hl5.def,  al5.def));
      }
    }

    var compHtml = compRows.length ? '<div class="sp-comparisons">' + compRows.join('') + '</div>' : '';

    // H2H recent results (up to 3)
    var h2hHtml = '';
    var h2hList = (predData && Array.isArray(predData.h2h)) ? predData.h2h.slice(0, 3) : [];
    if (h2hList.length) {
      h2hHtml = '<div class="sp-h2h">'
        + '<span class="sp-h2h-label">Recent H2H</span>'
        + h2hList.map(function (m) {
            var mHome   = m.teams.home.name;
            var mAway   = m.teams.away.name;
            var gHome   = m.goals.home;
            var gAway   = m.goals.away;
            var pen     = (m.score.penalty.home !== null) ? ' (pens)' : '';
            var winner  = m.teams.home.winner ? 'home' : (m.teams.away.winner ? 'away' : 'draw');
            var dateStr = new Date(m.fixture.date).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'2-digit' });
            return '<div class="sp-h2h-row">'
              + '<span class="sp-h2h-date">' + dateStr + '</span>'
              + '<span class="sp-h2h-team sp-h2h-team--' + (winner === 'home' ? 'win' : '') + '">' + mHome + '</span>'
              + '<span class="sp-h2h-score">' + gHome + '–' + gAway + pen + '</span>'
              + '<span class="sp-h2h-team sp-h2h-team--' + (winner === 'away' ? 'win' : '') + '">' + mAway + '</span>'
              + '</div>';
          }).join('')
        + '</div>';
    }

    var adviceHtml = '';
    if (pred.advice || winner.name) {
      adviceHtml = '<div class="sp-advice">'
        + (winner.name ? 'Tip: <strong>' + winner.name + '</strong>' : '')
        + (winner.comment ? ' <span class="sp-advice-comment">(' + winner.comment + ')</span>' : '')
        + (pred.advice ? '<span class="sp-advice-text">' + pred.advice + '</span>' : '')
        + '</div>';
    } else if (!predData) {
      adviceHtml = '<p class="sp-no-predictions">No predictions available yet. Check back a day before the match.</p>';
    }

    // 3-segment probability bar (home / draw / away)
    var probBar = (hPct || dPct || aPct)
      ? '<div class="sp-pred-bar">'
        +   '<div class="sp-pred-bar-track">'
        +     '<div class="sp-pred-bar-home" style="width:' + hPct + '%"></div>'
        +     '<div class="sp-pred-bar-draw"  style="width:' + dPct + '%"></div>'
        +     '<div class="sp-pred-bar-away" style="width:' + aPct + '%"></div>'
        +   '</div>'
        +   '<div class="sp-pred-bar-labels">'
        +     '<span>' + home.name + ' <strong>' + hPct + '%</strong></span>'
        +     '<span>Draw <strong>' + dPct + '%</strong></span>'
        +     '<span>' + away.name + ' <strong>' + aPct + '%</strong></span>'
        +   '</div>'
        + '</div>'
      : '';

    container.innerHTML =
      '<div class="sp-widget-header">'
      + '<span class="sp-widget-league">' + leagueName + roundLabel + '</span>'
      + dateHtml
      + '</div>'
      + '<div class="sp-teams">'
      + teamBlock(home, 'home')
      + '<div class="sp-vs">VS</div>'
      + teamBlock(away, 'away')
      + '</div>'
      + probBar
      + compHtml
      + h2hHtml
      + oddsHtml
      + adviceHtml;

    container.classList.remove('sp-loading');
  }

  function showError(container, msg) {
    container.innerHTML = '<p class="sp-error">' + msg + '</p>';
    container.classList.remove('sp-loading');
  }

  function showNoFixture(container) {
    container.innerHTML =
      '<div class="sp-no-fixture">'
      + '<strong>No match scheduled right now.</strong>'
      + '<span>Check back closer to the next Black Stars fixture.</span>'
      + '</div>';
    container.classList.remove('sp-loading');
  }

  function initGroupCarousel(el, groupNames, defaultIndex, onChange) {
    var currentIndex = defaultIndex;

    var track   = el.querySelector('.sp-rounds-track');
    var prevBtn = el.querySelector('.sp-rounds-arrow--prev');
    var nextBtn = el.querySelector('.sp-rounds-arrow--next');

    track.innerHTML = groupNames.map(function (name, i) {
      return '<span class="sp-round-pill' + (i === currentIndex ? ' sp-round-pill--active' : '') + '">' + name + '</span>';
    }).join('');

    function scrollPillIntoTrack(pill, smooth) {
      var target = pill.offsetLeft - (track.offsetWidth / 2) + (pill.offsetWidth / 2);
      if (smooth && track.scrollTo) {
        track.scrollTo({ left: target, behavior: 'smooth' });
      } else {
        track.scrollLeft = target;
      }
    }

    function goTo(index) {
      currentIndex = ((index % groupNames.length) + groupNames.length) % groupNames.length;
      track.querySelectorAll('.sp-round-pill').forEach(function (p, i) {
        p.classList.toggle('sp-round-pill--active', i === currentIndex);
      });
      var active = track.querySelectorAll('.sp-round-pill')[currentIndex];
      if (active) scrollPillIntoTrack(active, true);
      if (onChange) onChange(currentIndex);
    }

    setTimeout(function () {
      var pill = track.querySelectorAll('.sp-round-pill')[currentIndex];
      if (pill) scrollPillIntoTrack(pill, false);
    }, 80);

    prevBtn.addEventListener('click', function () { goTo(currentIndex - 1); });
    nextBtn.addEventListener('click', function () { goTo(currentIndex + 1); });

    el.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft')  { e.preventDefault(); goTo(currentIndex - 1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); goTo(currentIndex + 1); }
    });

    var timer = setInterval(function () { goTo(currentIndex + 1); }, 3500);
    el.addEventListener('mouseenter', function () { clearInterval(timer); });
    el.addEventListener('focusin',    function () { clearInterval(timer); });
    el.addEventListener('mouseleave', function () { timer = setInterval(function () { goTo(currentIndex + 1); }, 3500); });
    el.addEventListener('focusout',   function () { timer = setInterval(function () { goTo(currentIndex + 1); }, 3500); });
  }

  function renderStandings(container, data, fixture) {
    if (!data || !data.standings) {
      container.innerHTML = '<p class="sp-error">Standings not yet available.</p>';
      container.classList.remove('sp-loading');
      return;
    }

    var GHANA_ID   = 1504;
    var leagueName = data.name || 'Standings';
    var standings  = data.standings;

    // Build groups list, filtering out derived tables (third-placed, ranking tables)
    var groups = [];
    if (Array.isArray(standings[0])) {
      standings.forEach(function (g) {
        if (!g.length) return;
        var gName = (g[0].group || '').toLowerCase();
        if (gName.indexOf('third') !== -1 || gName.indexOf('ranking') !== -1) return;
        groups.push(g);
      });
    } else {
      groups = [standings];
    }

    if (!groups.length) {
      container.innerHTML = '<p class="sp-error">Standings not yet available.</p>';
      container.classList.remove('sp-loading');
      return;
    }

    // Default to Ghana's group
    var defaultIndex = 0;
    groups.forEach(function (g, i) {
      g.forEach(function (row) {
        if (row.team && row.team.id === GHANA_ID) { defaultIndex = i; }
      });
    });

    var groupNames = groups.map(function (g) { return g[0].group || 'Group'; });

    function buildRows(group) {
      return group.map(function (row) {
        var isGhana = row.team && row.team.id === GHANA_ID;
        var gd = row.goalsDiff !== undefined
          ? (row.goalsDiff > 0 ? '+' : '') + row.goalsDiff : '–';
        return '<tr class="' + (isGhana ? 'sp-st-row--ghana' : '') + '">'
          + '<td class="sp-st-rank-cell">' + row.rank + '</td>'
          + '<td><div class="sp-st-team-cell">'
          +   '<img src="' + row.team.logo + '" alt="' + row.team.name + '" class="sp-st-logo" loading="lazy" />'
          +   '<span>' + row.team.name + '</span>'
          + '</div></td>'
          + '<td>' + (row.all ? row.all.played : '–') + '</td>'
          + '<td>' + (row.points !== undefined ? row.points : '–') + '</td>'
          + '<td>' + gd + '</td>'
          + '</tr>';
      }).join('');
    }

    container.innerHTML =
      '<div class="sp-standings-title">' + leagueName + '</div>'
      + '<div class="sp-rounds-carousel" tabindex="0">'
      +   '<button class="sp-rounds-arrow sp-rounds-arrow--prev" aria-label="Previous group">&#8249;</button>'
      +   '<div class="sp-rounds-viewport"><div class="sp-rounds-track"></div></div>'
      +   '<button class="sp-rounds-arrow sp-rounds-arrow--next" aria-label="Next group">&#8250;</button>'
      + '</div>'
      + '<table class="sp-st-table">'
      +   '<thead><tr>'
      +     '<th></th><th style="text-align:left">Team</th>'
      +     '<th title="Played">P</th><th title="Points">Pts</th><th title="Goal Difference">GD</th>'
      +   '</tr></thead>'
      +   '<tbody class="sp-st-tbody">' + buildRows(groups[defaultIndex]) + '</tbody>'
      + '</table>';

    container.classList.remove('sp-loading');

    var tbody = container.querySelector('.sp-st-tbody');
    initGroupCarousel(
      container.querySelector('.sp-rounds-carousel'),
      groupNames,
      defaultIndex,
      function (i) { tbody.innerHTML = buildRows(groups[i]); }
    );
  }

  // Homepage: fixture + predictions/odds + standings in parallel
  var homeWidget     = document.getElementById('sp-home-fixture');
  var standingsPanel = document.getElementById('sp-home-standings');
  var homeWidgetMob  = document.getElementById('sp-home-fixture-mob');
  var standingsMob   = document.getElementById('sp-home-standings-mob');

  if (homeWidget || homeWidgetMob) {
    var standingsP = (standingsPanel || standingsMob)
      ? apiFetch('standings').catch(function () { return null; })
      : Promise.resolve(null);

    apiFetch('next-fixture')
      .then(function (fixture) {
        if (!fixture || !fixture.fixture) throw new Error('no fixture');
        var fid = fixture.fixture.id;
        return Promise.all([
          Promise.resolve(fixture),
          apiFetch('predictions?fixture=' + fid).catch(function () { return null; }),
          apiFetch('odds?fixture=' + fid).then(function (d) { return Array.isArray(d) && d[0] ? d[0] : null; }).catch(function () { return null; }),
          standingsP
        ]);
      })
      .then(function (r) {
        if (homeWidget)    renderWidget(homeWidget, r[0], r[1], r[2]);
        if (homeWidgetMob) renderWidget(homeWidgetMob, r[0], r[1], r[2]);
        if (standingsPanel) renderStandings(standingsPanel, r[3], r[0]);
        if (standingsMob)   renderStandings(standingsMob, r[3], r[0]);
      })
      .catch(function () {
        if (homeWidget)    showNoFixture(homeWidget);
        if (homeWidgetMob) showNoFixture(homeWidgetMob);
        standingsP.then(function (d) {
          if (standingsPanel) renderStandings(standingsPanel, d, null);
          if (standingsMob)   renderStandings(standingsMob, d, null);
        });
      });
  }

  // Article sidebar: fixture ID comes from a PHP data attribute
  // predictions returns a single object; odds returns an array
  var articleWidget = document.getElementById('sp-article-fixture');
  if (articleWidget) {
    var fid = articleWidget.dataset.fixtureId;
    Promise.all([
      apiFetch('predictions?fixture=' + fid).catch(function () { return null; }),
      apiFetch('odds?fixture=' + fid).then(function (d) { return Array.isArray(d) && d[0] ? d[0] : null; }).catch(function () { return null; })
    ])
    .then(function (results) { renderWidget(articleWidget, null, results[0], results[1]); })
    .catch(function () { showError(articleWidget, 'Prediction data unavailable.'); });
  }
})();

// ── Community Predictions Widget ────────────────────────────────────
;(function () {
  var wrap = document.getElementById('sp-community');
  if (!wrap || !window.spData) return;

  var SP        = window.spData;
  var fid       = wrap.dataset.fixtureId;
  var form      = document.getElementById('sp-pred-form');
  var resultsEl = document.getElementById('sp-results');
  var resultsIn = document.getElementById('sp-community-results');
  var submitBtn = document.getElementById('sp-submit');
  var msgEl     = document.getElementById('sp-form-msg');
  var selectedWinner = null;
  var lsKey = 'sp_pred_' + fid;

  function apiFetch(path, opts) {
    return fetch(SP.restBase + path, Object.assign({ headers: { 'X-WP-Nonce': SP.nonce } }, opts))
      .then(function (r) { return r.json(); });
  }

  function renderResults(data) {
    var teams = { home: 'Home', draw: 'Draw', away: 'Away' };

    // Try to pull team names from the stored fixture widget if rendered
    var homeNameEl = document.querySelector('.sp-team--home .sp-team-name');
    var awayNameEl = document.querySelector('.sp-team--away .sp-team-name');
    if (homeNameEl) teams.home = homeNameEl.textContent;
    if (awayNameEl) teams.away = awayNameEl.textContent;

    var total = data.total || 0;
    var pcts  = data.pcts  || { home: 0, draw: 0, away: 0 };
    var recent = data.recent || [];

    var recentHtml = recent.map(function (p) {
      var score = (p.home_score !== null && p.away_score !== null)
        ? ' <span class="sp-rec-score">' + p.home_score + '–' + p.away_score + '</span>' : '';
      return '<div class="sp-rec-item">'
        + '<span class="sp-rec-name">' + p.name + '</span>'
        + '<span class="sp-rec-pick sp-rec-pick--' + p.winner + '">' + (p.winner === 'home' ? teams.home : p.winner === 'away' ? teams.away : 'Draw') + score + '</span>'
        + '</div>';
    }).join('');

    resultsIn.innerHTML =
      '<p class="sp-results-total">' + total + ' prediction' + (total !== 1 ? 's' : '') + ' so far</p>'
      + '<div class="sp-results-bars">'
      +   '<div class="sp-results-bar-row sp-results-bar-row--home">'
      +     '<span class="sp-results-bar-label">' + teams.home + '</span>'
      +     '<div class="sp-results-bar-track"><div class="sp-results-bar-fill sp-results-bar-fill--home" style="width:' + pcts.home + '%"></div></div>'
      +     '<span class="sp-results-bar-pct">' + pcts.home + '%</span>'
      +   '</div>'
      +   '<div class="sp-results-bar-row">'
      +     '<span class="sp-results-bar-label">Draw</span>'
      +     '<div class="sp-results-bar-track"><div class="sp-results-bar-fill sp-results-bar-fill--draw" style="width:' + pcts.draw + '%"></div></div>'
      +     '<span class="sp-results-bar-pct">' + pcts.draw + '%</span>'
      +   '</div>'
      +   '<div class="sp-results-bar-row sp-results-bar-row--away">'
      +     '<span class="sp-results-bar-label">' + teams.away + '</span>'
      +     '<div class="sp-results-bar-track"><div class="sp-results-bar-fill sp-results-bar-fill--away" style="width:' + pcts.away + '%"></div></div>'
      +     '<span class="sp-results-bar-pct">' + pcts.away + '%</span>'
      +   '</div>'
      + '</div>'
      + (recentHtml ? '<div class="sp-recent-preds">' + recentHtml + '</div>' : '');

    resultsIn.classList.remove('sp-loading');
  }

  function loadResults() {
    apiFetch('community-predictions?fixture=' + fid)
      .then(renderResults)
      .catch(function () { resultsIn.innerHTML = '<p class="sp-error">Could not load results.</p>'; resultsIn.classList.remove('sp-loading'); });
  }

  function showResults() {
    form.style.display = 'none';
    resultsEl.style.display = '';
    loadResults();
  }

  // If already predicted for this fixture, go straight to results
  if (localStorage.getItem(lsKey)) {
    showResults();
    return;
  }

  // Winner button selection
  var pickBtns = wrap.querySelectorAll('.sp-pick-btn');
  pickBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      pickBtns.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      selectedWinner = btn.dataset.winner;
      submitBtn.disabled = false;
    });
  });

  // Form submission
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!selectedWinner) return;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';
    msgEl.textContent = '';

    var body = {
      fixture_id: parseInt(fid, 10),
      winner: selectedWinner,
      display_name: (document.getElementById('sp-pred-name').value || '').trim(),
      home_score: document.getElementById('sp-home-score').value !== '' ? parseInt(document.getElementById('sp-home-score').value, 10) : null,
      away_score: document.getElementById('sp-away-score').value !== '' ? parseInt(document.getElementById('sp-away-score').value, 10) : null,
    };

    apiFetch('predict', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': SP.nonce },
      body: JSON.stringify(body),
    })
    .then(function (res) {
      if (res.success) {
        localStorage.setItem(lsKey, '1');
        showResults();
      } else {
        var msg = (res.message || res.code || 'Something went wrong.');
        msgEl.textContent = msg;
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Prediction';
      }
    })
    .catch(function () {
      msgEl.textContent = 'Network error. Please try again.';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Prediction';
    });
  });
})();

// Tally embed loader (waitlist form)
(function () {
  var d = document,
    w = 'https://tally.so/widgets/embed.js',
    v = function () {
      if (typeof Tally !== 'undefined') {
        Tally.loadEmbeds();
      } else {
        d.querySelectorAll('iframe[data-tally-src]:not([src])').forEach(function (e) {
          e.src = e.dataset.tallySrc;
        });
      }
    };
  if (typeof Tally !== 'undefined') {
    v();
  } else if (d.querySelector('script[src="' + w + '"]') == null) {
    var s = d.createElement('script');
    s.src = w;
    s.onload = v;
    s.onerror = v;
    d.body.appendChild(s);
  }
})();
