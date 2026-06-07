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

// Announcement bar dismiss (persists across pages via localStorage)
const annBar = document.querySelector('.announcement-bar');
if (annBar) {
  if (localStorage.getItem('ann_wc2026_dismissed')) {
    annBar.style.display = 'none';
  }
  const dismissBtn = annBar.querySelector('.ann-dismiss');
  if (dismissBtn) {
    dismissBtn.addEventListener('click', () => {
      annBar.style.display = 'none';
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

    var pred = (predData && predData.predictions) ? predData.predictions : {};
    var comp = (predData && predData.comparison) ? predData.comparison : {};
    var winner = pred.winner || {};
    var league = fixture ? fixture.league : (predData ? predData.league : {});

    var hForm = pct(comp.form ? comp.form.home : 0);
    var aForm = pct(comp.form ? comp.form.away : 0);

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

    var compHtml = '';
    if (comp.form) {
      compHtml = '<div class="sp-comparisons">'
        + compRow('Form', comp.form.home, comp.form.away)
        + compRow('Attack', comp.att ? comp.att.home : 0, comp.att ? comp.att.away : 0)
        + compRow('Defence', comp.def ? comp.def.home : 0, comp.def ? comp.def.away : 0)
        + '</div>';
    }

    var adviceHtml = '';
    if (winner.name) {
      adviceHtml = '<div class="sp-advice">'
        + 'Predicted winner: <strong>' + winner.name + '</strong>'
        + (pred.advice ? ' — ' + pred.advice : '')
        + '</div>';
    }

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
      + '<div class="sp-pred-bar">'
      +   '<div class="sp-pred-bar-track">'
      +     '<div class="sp-pred-bar-home" style="width:' + hForm + '%"></div>'
      +     '<div class="sp-pred-bar-away" style="width:' + aForm + '%"></div>'
      +   '</div>'
      +   '<div class="sp-pred-bar-labels">'
      +     '<span>' + home.name + ' ' + hForm + '%</span>'
      +     '<span>' + away.name + ' ' + aForm + '%</span>'
      +   '</div>'
      + '</div>'
      + compHtml
      + oddsHtml
      + adviceHtml;

    container.classList.remove('sp-loading');
  }

  function showError(container, msg) {
    container.innerHTML = '<p class="sp-error">' + msg + '</p>';
    container.classList.remove('sp-loading');
  }

  // Homepage: fetch next Ghana WC fixture (single object), then predictions + odds in parallel
  var homeWidget = document.getElementById('sp-home-fixture');
  if (homeWidget) {
    apiFetch('next-fixture')
      .then(function (fixture) {
        if (!fixture || !fixture.fixture) throw new Error('no fixture');
        var fid = fixture.fixture.id;
        return Promise.all([
          Promise.resolve(fixture),
          // predictions returns a single object
          apiFetch('predictions?fixture=' + fid).catch(function () { return null; }),
          // odds returns an array — take first item
          apiFetch('odds?fixture=' + fid).then(function (d) { return Array.isArray(d) && d[0] ? d[0] : null; }).catch(function () { return null; })
        ]);
      })
      .then(function (results) { renderWidget(homeWidget, results[0], results[1], results[2]); })
      .catch(function () { showError(homeWidget, 'No upcoming fixture found.'); });
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
