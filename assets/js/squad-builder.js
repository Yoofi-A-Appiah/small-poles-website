/* World Cup 2026 Squad Builder v2.0.2
   Squad data loaded from WordPress via spSquadData (seeded by admin). */

;(function () {
  'use strict';

  /* ── Player database — loaded from wp_options via wp_localize_script ── */
  var SQUADS = (window.spSquadData && window.spSquadData.squads) ? window.spSquadData.squads : {};
  var FLAGS  = (window.spSquadData && window.spSquadData.flags)  ? window.spSquadData.flags  : {};

  /* Normalise values to numbers (wp_localize_script can stringify them) */
  Object.keys(SQUADS).forEach(function(nation) {
    SQUADS[nation].forEach(function(p) { p.v = parseInt(p.v, 10) || 7; });
  });

  var BUDGET = 100;

  var FORMATIONS = {
    '4-3-3':  { GK:1, DEF:4, MID:3, FWD:3,
      positions: [
        {pos:'GK',  x:50, y:88},
        {pos:'DEF', x:15, y:70},{pos:'DEF', x:38, y:72},{pos:'DEF', x:62, y:72},{pos:'DEF', x:85, y:70},
        {pos:'MID', x:25, y:48},{pos:'MID', x:50, y:45},{pos:'MID', x:75, y:48},
        {pos:'FWD', x:20, y:22},{pos:'FWD', x:50, y:18},{pos:'FWD', x:80, y:22},
      ]},
    '4-4-2':  { GK:1, DEF:4, MID:4, FWD:2,
      positions: [
        {pos:'GK',  x:50, y:88},
        {pos:'DEF', x:15, y:70},{pos:'DEF', x:38, y:72},{pos:'DEF', x:62, y:72},{pos:'DEF', x:85, y:70},
        {pos:'MID', x:15, y:48},{pos:'MID', x:38, y:48},{pos:'MID', x:62, y:48},{pos:'MID', x:85, y:48},
        {pos:'FWD', x:35, y:20},{pos:'FWD', x:65, y:20},
      ]},
    '3-5-2':  { GK:1, DEF:3, MID:5, FWD:2,
      positions: [
        {pos:'GK',  x:50, y:88},
        {pos:'DEF', x:25, y:72},{pos:'DEF', x:50, y:70},{pos:'DEF', x:75, y:72},
        {pos:'MID', x:10, y:50},{pos:'MID', x:28, y:48},{pos:'MID', x:50, y:44},{pos:'MID', x:72, y:48},{pos:'MID', x:90, y:50},
        {pos:'FWD', x:35, y:20},{pos:'FWD', x:65, y:20},
      ]},
    '4-2-3-1':{ GK:1, DEF:4, MID:5, FWD:1,
      positions: [
        {pos:'GK',  x:50, y:88},
        {pos:'DEF', x:15, y:70},{pos:'DEF', x:38, y:72},{pos:'DEF', x:62, y:72},{pos:'DEF', x:85, y:70},
        {pos:'MID', x:30, y:55},{pos:'MID', x:70, y:55},
        {pos:'MID', x:15, y:35},{pos:'MID', x:50, y:32},{pos:'MID', x:85, y:35},
        {pos:'FWD', x:50, y:15},
      ]},
  };


  /* ── State ─────────────────────────────────────────────────────── */
  var currentNation    = '';
  var currentFormation = '4-3-3';
  var currentFilter    = 'ALL';
  var selectedSlot     = -1;
  var squad            = [];

  /* ── Auth + persistence state ───────────────────────────────────── */
  var currentUser      = (window.spSquadData && window.spSquadData.user)          || null;
  var REST_BASE        = (window.spSquadData && window.spSquadData.restBase)       || '';
  var playerPoints     = (window.spSquadData && window.spSquadData.playerPoints)   || {};
  var loadedSquadTotalPts = null;

  function initSquad() {
    squad = FORMATIONS[currentFormation].positions.map(function(pos, i) {
      return { slot: i, pos: pos.pos, player: null, flag: '', _new: false };
    });
  }

  /* ── Budget ─────────────────────────────────────────────────────── */
  function calcSpent() {
    return squad.reduce(function(sum, s){ return sum + (s.player ? s.player.v : 0); }, 0);
  }

  function remaining() { return BUDGET - calcSpent(); }

  function updateBudgetDisplay() {
    var spent = calcSpent();
    document.getElementById('squadBudgetUsed').textContent = spent;
    document.getElementById('squadBudgetBar').style.width  = Math.min(100, spent) + '%';
  }

  /* ── Fantasy points helpers ─────────────────────────────────────── */
  function getPlayerPts(slot) {
    if (!slot.player || !currentNation) return 0;
    return playerPoints[currentNation + '|' + slot.player.n] || 0;
  }

  function renderSquadPoints() {
    var bar = document.getElementById('squadPointsBar');
    if (!bar) return;
    var livePts = squad.reduce(function(sum, s) { return sum + getPlayerPts(s); }, 0);
    var display = (loadedSquadTotalPts !== null) ? loadedSquadTotalPts : livePts;
    if (display > 0) {
      bar.style.display = 'flex';
      var numEl = document.getElementById('squadPointsTotal');
      if (numEl) numEl.textContent = display;
    } else {
      bar.style.display = 'none';
    }
  }

  /* ── Pitch rendering ────────────────────────────────────────────── */
  function renderPitch() {
    var pitch    = document.getElementById('squadPitch');
    var formation = FORMATIONS[currentFormation];

    pitch.innerHTML = '<div class="sp-pitch-line sp-pitch-line--h"></div><div class="sp-pitch-circle"></div>';

    squad.forEach(function(s, i) {
      var pos = formation.positions[i];
      var dot = document.createElement('div');
      dot.className = 'sp-player-dot';
      if (selectedSlot === i) dot.classList.add('sp-player-dot--active');
      if (!s.player) dot.classList.add('sp-player-dot--empty');
      if (s.player && s._new) {
        dot.classList.add('sp-player-dot--new');
        s._new = false;
      }

      dot.style.left = pos.x + '%';
      dot.style.top  = pos.y + '%';

      var circle = document.createElement('div');
      circle.className = 'sp-player-dot-circle';

      if (s.player) {
        circle.textContent = s.flag || '⚽';

        var badge = document.createElement('div');
        badge.className = 'sp-player-dot-badge sp-pos-badge--' + s.pos.toLowerCase();
        badge.textContent = s.pos;

        var nameParts = s.player.n.split(' ');
        var nameEl = document.createElement('div');
        nameEl.className = 'sp-player-dot-name';
        nameEl.textContent = nameParts[nameParts.length - 1].substring(0, 9);

        dot.appendChild(circle);
        dot.appendChild(badge);
        dot.appendChild(nameEl);

        var pts = getPlayerPts(s);
        if (pts > 0) {
          var ptsEl = document.createElement('div');
          ptsEl.className = 'sp-player-dot-pts';
          ptsEl.textContent = pts + ' pts';
          dot.appendChild(ptsEl);
        }
      } else {
        circle.textContent = pos.pos;
        dot.appendChild(circle);
      }

      dot.addEventListener('click', (function(idx){ return function(){ selectSlot(idx); }; })(i));
      pitch.appendChild(dot);
    });

    updateBudgetDisplay();
    updateSaveBtn();
    renderSquadPoints();
  }

  function updateSaveBtn() {
    var btn  = document.getElementById('squadSaveBtn');
    var hint = document.getElementById('squadSaveHint');
    if (!btn) return;
    var filled = squad.filter(function(s){ return s.player; }).length;
    var full   = filled === 11;
    btn.disabled = !full;
    if (hint) {
      if (!full) {
        hint.textContent = filled + ' / 11 players selected — fill your squad to save';
        hint.style.display = 'block';
      } else {
        hint.style.display = 'none';
      }
    }
  }

  function selectSlot(idx) {
    selectedSlot = (selectedSlot === idx) ? -1 : idx;
    renderPitch();
    renderPool();
  }

  /* ── Player pool rendering ──────────────────────────────────────── */
  function renderPool() {
    var poolEl = document.getElementById('squadPool');
    poolEl.innerHTML = '';

    if (!currentNation) {
      poolEl.innerHTML = Object.keys(SQUADS).length
        ? '<p class="squad-pool-empty">Select a nation above to browse players.</p>'
        : '<p class="squad-pool-empty">No squads loaded yet — the admin needs to seed teams via Games › Squad Builder.</p>';
      return;
    }

    var players = SQUADS[currentNation] || [];
    var budget  = remaining();
    var slotPos = selectedSlot >= 0 ? squad[selectedSlot].pos : null;

    players
      .filter(function(p) {
        if (currentFilter !== 'ALL' && p.p !== currentFilter) return false;
        return true;
      })
      .forEach(function(player) {
        var alreadyPicked = squad.some(function(s){ return s.player && s.player.n === player.n; });
        var affordable    = player.v <= budget + (alreadyPicked ? player.v : 0);
        var posMatch      = !slotPos || player.p === slotPos;

        var card = document.createElement('div');
        card.className = 'squad-player-card';
        if (alreadyPicked) card.classList.add('squad-player-card--picked');
        if (!affordable && !alreadyPicked) card.classList.add('squad-player-card--unaffordable');
        if (slotPos && !posMatch) card.classList.add('squad-player-card--mismatch');

        var fpts = playerPoints[currentNation + '|' + player.n] || 0;
        var fptsHtml = fpts > 0
          ? '<span class="spc-fpts" aria-label="' + fpts + ' fantasy points">' + fpts + ' fpts</span>'
          : '';
        card.innerHTML = '<span class="spc-pos spc-pos--' + player.p.toLowerCase() + '">' + player.p + '</span>'
          + '<span class="spc-name">' + player.n + '</span>'
          + '<span class="spc-val">' + player.v + ' pts</span>'
          + fptsHtml;

        card.addEventListener('click', function() {
          if (alreadyPicked) {
            removePlayer(player);
          } else {
            addPlayer(player);
          }
        });

        poolEl.appendChild(card);
      });
  }

  function addPlayer(player) {
    if (selectedSlot >= 0) {
      if (squad[selectedSlot].pos !== player.p) {
        flashMsg('Wrong position for this slot — pick a ' + squad[selectedSlot].pos);
        return;
      }
      if (player.v > remaining()) {
        flashMsg('Not enough budget — need ' + player.v + ' pts, have ' + remaining());
        return;
      }
      squad[selectedSlot].player = player;
      squad[selectedSlot]._new   = true;
      squad[selectedSlot].flag   = FLAGS[currentNation] || '⚽';
      selectedSlot = -1;
    } else {
      var slot = squad.find(function(s){ return !s.player && s.pos === player.p; });
      if (!slot) {
        flashMsg('No empty ' + player.p + ' slot — click a slot on the pitch to place this player');
        return;
      }
      if (player.v > remaining()) {
        flashMsg('Not enough budget — need ' + player.v + ' pts, have ' + remaining());
        return;
      }
      slot.player = player;
      slot._new   = true;
      slot.flag   = FLAGS[currentNation] || '⚽';
    }
    renderPitch();
    renderPool();
  }

  function removePlayer(player) {
    var s = squad.find(function(s){ return s.player && s.player.n === player.n; });
    if (s) {
      s.player = null;
      s._new   = false;
      renderPitch();
      renderPool();
    }
  }

  function flashMsg(msg) {
    var title = document.getElementById('squadPoolTitle');
    var orig  = title.textContent;
    title.textContent = msg;
    title.classList.add('squad-pool-msg--err');
    setTimeout(function(){
      title.textContent = orig;
      title.classList.remove('squad-pool-msg--err');
    }, 2500);
  }

  /* ── Screenshot share ───────────────────────────────────────────── */
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(ta);
    return ok ? Promise.resolve() : Promise.reject();
  }

  function getTeamName() {
    var input = document.getElementById('squadTeamName');
    return (input && input.value.trim()) || 'My World Cup XI';
  }

  function buildShareText() {
    var byPos = { GK:[], DEF:[], MID:[], FWD:[] };
    squad.forEach(function(s){ if (s.player) byPos[s.pos].push(s.player.n); });
    var lines = ['🏟️ ' + getTeamName() + ' (' + currentFormation + ')', ''];
    ['GK','DEF','MID','FWD'].forEach(function(pos){
      if (byPos[pos].length) lines.push(pos + ': ' + byPos[pos].join(', '));
    });
    lines.push('', 'Budget: ' + calcSpent() + '/100 pts', 'smallpoles.online/games/squad/');
    return lines.join('\n');
  }

  function injectShareModalCSS() {
    if (document.getElementById('sp-sq-modal-css')) return;
    var s = document.createElement('style');
    s.id = 'sp-sq-modal-css';
    s.textContent = [
      '#sq-share-overlay{position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(6px)}',
      '@keyframes sq-modal-in{from{opacity:0;transform:scale(.95) translateY(10px)}to{opacity:1;transform:none}}',
      '#sq-share-modal{background:#151515;border-radius:16px;max-width:420px;width:100%;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.75);animation:sq-modal-in .22s ease;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column}',
      '#sq-share-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px 10px}',
      '#sq-share-head-title{font-size:15px;font-weight:700;color:#fff}',
      '#sq-share-close{background:none;border:none;color:rgba(255,255,255,.45);font-size:22px;cursor:pointer;width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background .15s;flex-shrink:0}',
      '#sq-share-close:hover{background:rgba(255,255,255,.1);color:#fff}',
      '#sq-share-img{width:100%;display:block;border-top:1px solid rgba(255,255,255,.08);border-bottom:1px solid rgba(255,255,255,.08)}',
      '#sq-share-msg-wrap{padding:14px 18px 10px}',
      '#sq-share-msg-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.7px;margin-bottom:7px}',
      '#sq-share-textarea{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;color:rgba(255,255,255,.82);font-size:12px;line-height:1.65;padding:10px 12px;resize:none;font-family:system-ui,sans-serif;box-sizing:border-box;outline:none}',
      '#sq-share-textarea:focus{border-color:rgba(0,255,127,.4)}',
      '#sq-share-actions{display:flex;gap:8px;padding:10px 18px 18px}',
      '.sq-sa-btn{flex:1;padding:10px 4px;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;letter-spacing:.2px}',
      '.sq-sa-btn:hover{opacity:.82}',
      '.sq-sa-btn--copy{background:#0073aa;color:#fff}',
      '.sq-sa-btn--dl{background:#00ff7f;color:#000}',
      '.sq-sa-btn--share{background:#27ae60;color:#fff}',
    ].join('');
    document.head.appendChild(s);
  }

  function openShareResultModal(canvas, shareText, filename) {
    injectShareModalCSS();

    var overlay = document.createElement('div');
    overlay.id  = 'sq-share-overlay';

    var canShare = !!(navigator.share && navigator.canShare);

    overlay.innerHTML = '<div id="sq-share-modal">'
      + '<div id="sq-share-head">'
      + '<span id="sq-share-head-title">Your Squad</span>'
      + '<button id="sq-share-close" aria-label="Close">&times;</button>'
      + '</div>'
      + '<img id="sq-share-img" src="' + canvas.toDataURL('image/png') + '" alt="Squad screenshot" />'
      + '<div id="sq-share-msg-wrap">'
      + '<div id="sq-share-msg-label">Caption &amp; link</div>'
      + '<textarea id="sq-share-textarea" rows="6">' + shareText + '</textarea>'
      + '</div>'
      + '<div id="sq-share-actions">'
      + '<button class="sq-sa-btn sq-sa-btn--copy" id="sq-sa-copy">Copy Message</button>'
      + '<button class="sq-sa-btn sq-sa-btn--dl"   id="sq-sa-dl">Download Image</button>'
      + (canShare ? '<button class="sq-sa-btn sq-sa-btn--share" id="sq-sa-share">Share</button>' : '')
      + '</div>'
      + '</div>';

    document.body.appendChild(overlay);

    function closeModal() { var el = document.getElementById('sq-share-overlay'); if (el) el.remove(); }

    document.getElementById('sq-share-close').addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function esc(e){ if (e.key === 'Escape'){ closeModal(); document.removeEventListener('keydown', esc); } });

    document.getElementById('sq-sa-copy').addEventListener('click', function() {
      var btn = this;
      var text = document.getElementById('sq-share-textarea').value;
      copyText(text).then(function(){
        btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = 'Copy Message'; }, 2500);
      }).catch(function(){
        document.getElementById('sq-share-textarea').select();
      });
    });

    document.getElementById('sq-sa-dl').addEventListener('click', function() {
      var a = document.createElement('a');
      a.download = filename;
      a.href = canvas.toDataURL('image/png');
      a.click();
    });

    var shareNativeBtn = document.getElementById('sq-sa-share');
    if (shareNativeBtn) {
      shareNativeBtn.addEventListener('click', function() {
        var text = document.getElementById('sq-share-textarea').value;
        canvas.toBlob(function(blob) {
          var file = new File([blob], filename, { type: 'image/png' });
          if (navigator.canShare({ files: [file] })) {
            navigator.share({ files: [file], title: currentNation + ' XI — Small Poles', text: text }).catch(function(){});
          }
        }, 'image/png');
      });
    }
  }

  function captureAndShare() {
    var shareBtn = document.getElementById('squadShareBtn');
    shareBtn.disabled    = true;
    shareBtn.textContent = 'Capturing…';

    var teamName  = getTeamName();
    var flag      = FLAGS[currentNation] || '';
    var filename  = 'smallpoles-' + teamName.replace(/[^a-z0-9]+/gi, '-').toLowerCase() + '.png';
    var shareText = buildShareText();

    var wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:fixed;left:-9999px;top:0;width:340px;background:#101c10;overflow:hidden;font-family:system-ui,sans-serif';

    var header = document.createElement('div');
    header.style.cssText = 'padding:14px 18px;background:linear-gradient(135deg,#0f3460 0%,#16213e 100%);display:flex;align-items:center;gap:12px';
    header.innerHTML = '<span style="font-size:30px;line-height:1;flex-shrink:0">' + flag + '</span>'
      + '<div>'
      + '<div style="font-size:17px;font-weight:800;color:#fff;line-height:1.15">' + teamName + '</div>'
      + '<div style="font-size:12px;color:rgba(255,255,255,.55);margin-top:2px">' + currentFormation + ' · ' + calcSpent() + ' / 100 pts</div>'
      + '</div>';

    var pitchEl    = document.getElementById('squadPitch');
    var pitchClone = pitchEl.cloneNode(true);
    /* Strip transient animation/selection classes so the screenshot is uniform */
    pitchClone.querySelectorAll('.sp-player-dot--active, .sp-player-dot--new').forEach(function(el){
      el.classList.remove('sp-player-dot--active');
      el.classList.remove('sp-player-dot--new');
    });
    pitchClone.style.cssText = 'position:relative;width:340px;height:486px;border-radius:0';

    var footer = document.createElement('div');
    footer.style.cssText = 'padding:8px 16px;text-align:center;font-size:10px;color:rgba(255,255,255,.35);background:#0a0f0a;letter-spacing:.5px';
    footer.textContent = 'smallpoles.online/games/squad/';

    wrapper.appendChild(header);
    wrapper.appendChild(pitchClone);
    wrapper.appendChild(footer);
    document.body.appendChild(wrapper);

    window.html2canvas(wrapper, {
      useCORS: true,
      backgroundColor: '#101c10',
      scale: 2,
      logging: false,
    }).then(function(canvas) {
      document.body.removeChild(wrapper);
      shareBtn.disabled    = false;
      shareBtn.textContent = 'Share Squad';
      openShareResultModal(canvas, shareText, filename);
    }).catch(function(err) {
      if (document.body.contains(wrapper)) document.body.removeChild(wrapper);
      shareBtn.disabled    = false;
      shareBtn.textContent = 'Share Squad';
      console.error('[SP Squad] screenshot error:', err);
      flashMsg('Screenshot failed — try a different browser');
    });
  }

  /* ── Nation grid ─────────────────────────────────────────────────── */
  function collapseNationGrid() {
    var grid   = document.getElementById('squadNationGrid');
    var toggle = document.getElementById('squadNationToggle');
    if (grid)   { grid.classList.remove('open'); grid.setAttribute('aria-hidden', 'true'); }
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  function renderNationGrid() {
    var grid   = document.getElementById('squadNationGrid');
    var toggle = document.getElementById('squadNationToggle');
    var label  = document.getElementById('squadNationToggleLabel');

    grid.innerHTML = '';

    /* Update toggle label with selected nation */
    if (label) {
      label.textContent = currentNation
        ? (FLAGS[currentNation] ? FLAGS[currentNation] + ' ' : '') + currentNation
        : 'Select a nation';
    }

    Object.keys(SQUADS).forEach(function(nation) {
      var btn = document.createElement('button');
      btn.className = 'squad-nation-btn';
      if (nation === currentNation) btn.classList.add('active');
      btn.innerHTML = (FLAGS[nation] || '') + ' ' + nation;
      btn.addEventListener('click', function() {
        currentNation = nation;
        selectedSlot  = -1;
        collapseNationGrid();
        renderNationGrid();
        renderPitch();
        renderPool();
        document.getElementById('squadPoolTitle').textContent = nation + ' Player Pool';
      });
      grid.appendChild(btn);
    });
  }

  /* ── Boot + event wiring ─────────────────────────────────────────── */
  (function boot() {
    /* Required elements — all come from page-squad-builder.php.
       If any are missing the page template is not assigned in WP Admin. */
    var required = ['squadPitch','squadPool','squadNationGrid',
                    'squadFormationRow','squadClear','squadShareBtn',
                    'squadBudgetUsed','squadBudgetBar','squadPoolTitle'];
    var missing = required.filter(function(id){ return !document.getElementById(id); });
    if (missing.length) {
      console.error(
        '[SP Squad] Missing DOM elements:', missing.join(', '),
        '— Make sure the Squad Builder page uses the "page-squad-builder.php" template ' +
        '(WP Admin → Pages → Squad Builder → Page Attributes → Template).'
      );
      return;
    }

    try {
      initSquad();
      renderNationGrid();
      renderPitch();
      renderPool();
    } catch (err) {
      console.error('[SP Squad] boot error:', err);
      return;
    }

    document.getElementById('squadShareBtn').addEventListener('click', function() {
      var filled = squad.filter(function(s){ return s.player; });
      if (filled.length < 11) { flashMsg('Fill all 11 slots before sharing!'); return; }
      captureAndShare();
    });

    document.getElementById('squadFormationRow').addEventListener('click', function(e) {
      var btn = e.target.closest('.squad-formation-btn');
      if (!btn) return;
      currentFormation = btn.dataset.formation;
      document.querySelectorAll('.squad-formation-btn').forEach(function(b){
        b.classList.toggle('active', b === btn);
      });
      var prevPicks = squad.filter(function(s){ return s.player; }).map(function(s){ return { player: s.player, flag: s.flag }; });
      initSquad();
      prevPicks.forEach(function(pick) {
        var slot = squad.find(function(s){ return !s.player && s.pos === pick.player.p; });
        if (slot) { slot.player = pick.player; slot.flag = pick.flag; }
      });
      selectedSlot = -1;
      renderPitch();
      renderPool();
    });

    document.querySelectorAll('.squad-filter-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        currentFilter = btn.dataset.pos;
        document.querySelectorAll('.squad-filter-btn').forEach(function(b){
          b.classList.toggle('active', b === btn);
        });
        renderPool();
      });
    });

    document.getElementById('squadClear').addEventListener('click', function() {
      initSquad();
      selectedSlot = -1;
      renderPitch();
      renderPool();
    });

    /* Nation toggle (mobile) */
    var nationToggle = document.getElementById('squadNationToggle');
    if (nationToggle) {
      nationToggle.addEventListener('click', function() {
        var grid    = document.getElementById('squadNationGrid');
        var isOpen  = grid.classList.toggle('open');
        grid.setAttribute('aria-hidden', String(!isOpen));
        nationToggle.setAttribute('aria-expanded', String(isOpen));
      });
    }
  })();

  /* ── Save + My Squads + Logout button wiring ────────────────────── */
  (function() {
    var saveBtn      = document.getElementById('squadSaveBtn');
    var mySquadsBtn  = document.getElementById('squadMySquadsBtn');
    var logoutBtn    = document.getElementById('squadLogoutBtn');
    if (saveBtn)     saveBtn.addEventListener('click', saveSquad);
    if (mySquadsBtn) mySquadsBtn.addEventListener('click', loadUserSquads);
    if (logoutBtn)   logoutBtn.addEventListener('click', logoutUser);
  })();

  /* ═══════════════════════════════════════════════════════════════
     Auth + Squad Persistence
     ═══════════════════════════════════════════════════════════════ */

  /* ── REST helper ─────────────────────────────────────────────────── */
  function apiFetch(path, opts) {
    return fetch(REST_BASE + path, Object.assign({
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
    }, opts || {}));
  }

  /* ── Escape HTML ─────────────────────────────────────────────────── */
  function escHtml(str) {
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── User bar ────────────────────────────────────────────────────── */
  function updateUserBar() {
    var bar      = document.getElementById('squadUserBar');
    var nameText = document.getElementById('squadUserNameText');
    if (!bar) return;
    if (currentUser) {
      if (nameText) nameText.textContent = currentUser.name;
      bar.classList.remove('squad-user-bar--hidden');
    } else {
      bar.classList.add('squad-user-bar--hidden');
    }
  }

  function logoutUser() {
    var btn = document.getElementById('squadLogoutBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Logging out…'; }
    apiFetch('squad-auth/logout', { method: 'POST' })
      .then(function() {
        currentUser = null;
        updateUserBar();
        if (btn) { btn.disabled = false; btn.textContent = 'Log out'; }
      })
      .catch(function() {
        if (btn) { btn.disabled = false; btn.textContent = 'Log out'; }
      });
  }

  /* ── Auth modal CSS ──────────────────────────────────────────────── */
  function injectAuthCSS() {
    if (document.getElementById('sq-auth-css')) return;
    var s = document.createElement('style');
    s.id = 'sq-auth-css';
    s.textContent = [
      '#sq-auth-overlay{position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(6px)}',
      '@keyframes sq-modal-in{from{opacity:0;transform:scale(.95) translateY(10px)}to{opacity:1;transform:none}}',
      '#sq-auth-modal{background:#151515;border-radius:16px;max-width:380px;width:100%;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.75);animation:sq-modal-in .22s ease}',
      '#sq-auth-head{background:linear-gradient(135deg,#0f3460,#16213e);padding:22px 24px 18px;display:flex;align-items:center;justify-content:space-between}',
      '#sq-auth-title{font-size:16px;font-weight:700;color:#fff}',
      '#sq-auth-close{background:none;border:none;color:rgba(255,255,255,.45);font-size:22px;cursor:pointer;min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background .15s;touch-action:manipulation}',
      '#sq-auth-close:hover{background:rgba(255,255,255,.1);color:#fff}',
      '#sq-auth-tabs{display:flex;border-bottom:1px solid rgba(255,255,255,.08)}',
      '.sq-auth-tab{flex:1;padding:14px 12px;min-height:44px;background:none;border:none;color:rgba(255,255,255,.55);font-size:14px;font-weight:600;cursor:pointer;transition:color .15s,border-color .15s;border-bottom:2px solid transparent;margin-bottom:-1px;touch-action:manipulation}',
      '.sq-auth-tab.active{color:#fff;border-color:#00ff7f}',
      '#sq-auth-body{padding:20px 24px 24px}',
      '.sq-auth-panel{display:none}.sq-auth-panel.active{display:block}',
      '.sq-auth-field{margin-bottom:14px}',
      '.sq-auth-label{display:block;font-size:12px;font-weight:700;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}',
      '.sq-auth-input-wrap{position:relative}',
      '.sq-auth-input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:8px;color:#fff;font-size:16px;padding:11px 12px;box-sizing:border-box;outline:none;transition:border-color .15s}',
      '.sq-auth-input:focus{border-color:rgba(0,255,127,.5);background:rgba(255,255,255,.08)}',
      '.sq-auth-eye{position:absolute;right:0;top:0;bottom:0;width:44px;background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;display:flex;align-items:center;justify-content:center;touch-action:manipulation}',
      '.sq-auth-eye:hover{color:rgba(255,255,255,.75)}',
      '.sq-auth-submit{width:100%;background:#00ff7f;color:#000;font-weight:700;font-size:15px;border:none;border-radius:8px;padding:13px;min-height:48px;cursor:pointer;margin-top:8px;transition:opacity .15s;touch-action:manipulation}',
      '.sq-auth-submit:hover{opacity:.85}.sq-auth-submit:disabled{opacity:.5;cursor:default}',
      '.sq-auth-err{font-size:13px;color:#f87171;margin-top:10px;display:none;padding:8px 12px;background:rgba(248,113,113,.1);border-radius:6px;border:1px solid rgba(248,113,113,.2)}',
    ].join('');
    document.head.appendChild(s);
  }

  /* ── Auth modal ──────────────────────────────────────────────────── */
  function openAuthModal(onSuccess) {
    injectAuthCSS();

    var overlay = document.createElement('div');
    overlay.id  = 'sq-auth-overlay';
    overlay.innerHTML = [
      '<div id="sq-auth-modal" role="dialog" aria-modal="true" aria-labelledby="sq-auth-title">',
        '<div id="sq-auth-head"><span id="sq-auth-title">Save Your Squad</span>',
        '<button id="sq-auth-close" aria-label="Close">&times;</button></div>',
        '<div id="sq-auth-tabs" role="tablist">',
          '<button class="sq-auth-tab active" data-tab="login" role="tab" aria-selected="true" aria-controls="sq-panel-login">Log In</button>',
          '<button class="sq-auth-tab" data-tab="register" role="tab" aria-selected="false" aria-controls="sq-panel-register">Create Account</button>',
        '</div>',
        '<div id="sq-auth-body">',
          '<div class="sq-auth-panel active" id="sq-panel-login" role="tabpanel">',
            '<div class="sq-auth-field"><label class="sq-auth-label" for="sq-login-email">Email</label>',
            '<input class="sq-auth-input" type="email" id="sq-login-email" autocomplete="email" inputmode="email" /></div>',
            '<div class="sq-auth-field"><label class="sq-auth-label" for="sq-login-pass">Password</label>',
            '<div class="sq-auth-input-wrap"><input class="sq-auth-input" type="password" id="sq-login-pass" autocomplete="current-password" style="padding-right:44px" />',
            '<button type="button" class="sq-auth-eye" data-target="sq-login-pass" aria-label="Show password">',
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            '</button></div></div>',
            '<button class="sq-auth-submit" id="sq-login-submit">Log In</button>',
            '<div class="sq-auth-err" id="sq-login-err" role="alert" aria-live="assertive"></div>',
          '</div>',
          '<div class="sq-auth-panel" id="sq-panel-register" role="tabpanel">',
            '<div class="sq-auth-field"><label class="sq-auth-label" for="sq-reg-name">Display Name</label>',
            '<input class="sq-auth-input" type="text" id="sq-reg-name" autocomplete="name" /></div>',
            '<div class="sq-auth-field"><label class="sq-auth-label" for="sq-reg-email">Email</label>',
            '<input class="sq-auth-input" type="email" id="sq-reg-email" autocomplete="email" inputmode="email" /></div>',
            '<div class="sq-auth-field"><label class="sq-auth-label" for="sq-reg-pass">Password (8+ characters)</label>',
            '<div class="sq-auth-input-wrap"><input class="sq-auth-input" type="password" id="sq-reg-pass" autocomplete="new-password" style="padding-right:44px" />',
            '<button type="button" class="sq-auth-eye" data-target="sq-reg-pass" aria-label="Show password">',
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            '</button></div></div>',
            '<button class="sq-auth-submit" id="sq-reg-submit">Create Account</button>',
            '<div class="sq-auth-err" id="sq-reg-err" role="alert" aria-live="assertive"></div>',
          '</div>',
        '</div>',
      '</div>',
    ].join('');

    document.body.appendChild(overlay);

    function closeModal() { var el = document.getElementById('sq-auth-overlay'); if (el) el.remove(); }
    document.getElementById('sq-auth-close').addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });

    // Auto-focus first field
    setTimeout(function() {
      var first = overlay.querySelector('.sq-auth-panel.active .sq-auth-input');
      if (first) first.focus();
    }, 50);

    // Focus trap
    overlay.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') { closeModal(); return; }
      if (e.key !== 'Tab') return;
      var focusable = Array.prototype.slice.call(overlay.querySelectorAll(
        'button:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])'
      ));
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last.focus(); }
      } else {
        if (document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });

    // Tab switching
    overlay.querySelectorAll('.sq-auth-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        overlay.querySelectorAll('.sq-auth-tab').forEach(function(t) {
          t.classList.remove('active'); t.setAttribute('aria-selected', 'false');
        });
        overlay.querySelectorAll('.sq-auth-panel').forEach(function(p){ p.classList.remove('active'); });
        tab.classList.add('active'); tab.setAttribute('aria-selected', 'true');
        var panel = document.getElementById('sq-panel-' + tab.dataset.tab);
        if (panel) {
          panel.classList.add('active');
          var fi = panel.querySelector('.sq-auth-input'); if (fi) fi.focus();
        }
      });
    });

    // Password show/hide toggles
    overlay.querySelectorAll('.sq-auth-eye').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var input = document.getElementById(btn.dataset.target);
        if (!input) return;
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        btn.innerHTML = showing
          ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
          : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
      });
    });

    function handleAuthSuccess(data) {
      currentUser = { id: data.user_id, name: data.display_name, email: data.email };
      updateUserBar();
      closeModal();
      if (typeof onSuccess === 'function') onSuccess();
    }

    document.getElementById('sq-login-submit').addEventListener('click', function() {
      var btn   = this;
      var err   = document.getElementById('sq-login-err');
      var email = document.getElementById('sq-login-email').value.trim();
      var pass  = document.getElementById('sq-login-pass').value;
      if (!email || !pass) { err.textContent = 'Please fill in all fields.'; err.style.display = 'block'; return; }
      btn.disabled = true; btn.textContent = 'Logging in…'; err.style.display = 'none';
      apiFetch('squad-auth/login', { method: 'POST', body: JSON.stringify({ email: email, password: pass }) })
        .then(function(r){ return r.json(); })
        .then(function(data) {
          if (data.user_id) { handleAuthSuccess(data); }
          else { btn.disabled = false; btn.textContent = 'Log In'; err.textContent = data.message || 'Invalid email or password.'; err.style.display = 'block'; }
        })
        .catch(function() { btn.disabled = false; btn.textContent = 'Log In'; err.textContent = 'Network error — please try again.'; err.style.display = 'block'; });
    });

    document.getElementById('sq-reg-submit').addEventListener('click', function() {
      var btn   = this;
      var err   = document.getElementById('sq-reg-err');
      var name  = document.getElementById('sq-reg-name').value.trim();
      var email = document.getElementById('sq-reg-email').value.trim();
      var pass  = document.getElementById('sq-reg-pass').value;
      if (!name || !email || !pass) { err.textContent = 'Please fill in all fields.'; err.style.display = 'block'; return; }
      if (pass.length < 8) { err.textContent = 'Password must be at least 8 characters.'; err.style.display = 'block'; return; }
      btn.disabled = true; btn.textContent = 'Creating…'; err.style.display = 'none';
      apiFetch('squad-auth/register', { method: 'POST', body: JSON.stringify({ display_name: name, email: email, password: pass }) })
        .then(function(r){ return r.json(); })
        .then(function(data) {
          if (data.user_id) { handleAuthSuccess(data); }
          else { btn.disabled = false; btn.textContent = 'Create Account'; err.textContent = data.message || 'Registration failed.'; err.style.display = 'block'; }
        })
        .catch(function() { btn.disabled = false; btn.textContent = 'Create Account'; err.textContent = 'Network error — please try again.'; err.style.display = 'block'; });
    });
  }

  /* ── Save toast (replaces flashMsg for save feedback) ───────────── */
  function showSaveToast(msg, isError) {
    var toast = document.getElementById('squadSaveToast');
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.toggle('toast--error', !!isError);
    toast.classList.add('visible');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function() { toast.classList.remove('visible'); }, isError ? 4000 : 3000);
  }

  /* ── Save squad ──────────────────────────────────────────────────── */
  function saveSquad() {
    if (!currentNation) { showSaveToast('Select a nation first', true); return; }

    if (!currentUser) {
      openAuthModal(function() { saveSquad(); });
      return;
    }

    var btn = document.getElementById('squadSaveBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

    apiFetch('squads', {
      method: 'POST',
      body: JSON.stringify({
        squad_name:  getTeamName(),
        nation:      currentNation,
        formation:   currentFormation,
        squad_data:  squad,
        budget_used: calcSpent(),
      }),
    })
    .then(function(r) {
      if (r.status === 401 || r.status === 403) {
        // Session expired — clear user state and ask to log in again
        currentUser = null;
        updateUserBar();
        if (btn) { btn.disabled = false; btn.textContent = 'Save Squad'; }
        openAuthModal(function() { saveSquad(); });
        return null;
      }
      return r.json();
    })
    .then(function(data) {
      if (!data) return;
      if (btn) { btn.disabled = false; btn.textContent = 'Save Squad'; }
      if (data.id) {
        showSaveToast('Squad saved! Check your email ✓');
      } else {
        showSaveToast(data.message || 'Could not save — try again', true);
      }
    })
    .catch(function() {
      if (btn) { btn.disabled = false; btn.textContent = 'Save Squad'; }
      showSaveToast('Save failed — check your connection', true);
    });
  }

  /* ── My Squads modal ─────────────────────────────────────────────── */
  function injectMySquadsCSS() {
    if (document.getElementById('sq-ms-css')) return;
    var s = document.createElement('style');
    s.id  = 'sq-ms-css';
    s.textContent = [
      '#sq-ms-overlay{position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(6px)}',
      '#sq-ms-modal{background:#151515;border-radius:16px;max-width:440px;width:100%;max-height:80vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.75);animation:sq-modal-in .22s ease}',
      '#sq-ms-head{background:linear-gradient(135deg,#0f3460,#16213e);padding:22px 24px 18px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:1}',
      '#sq-ms-title{font-size:16px;font-weight:700;color:#fff}',
      '#sq-ms-close{background:none;border:none;color:rgba(255,255,255,.45);font-size:22px;cursor:pointer;width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background .15s}',
      '#sq-ms-close:hover{background:rgba(255,255,255,.1);color:#fff}',
      '#sq-ms-body{padding:16px 24px 24px}',
      '.sq-ms-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.06)}',
      '.sq-ms-row:last-child{border-bottom:none}',
      '.sq-ms-info{flex:1;min-width:0}',
      '.sq-ms-name{font-size:14px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
      '.sq-ms-meta{font-size:12px;color:#9ca3af;margin-top:2px}',
      '.sq-ms-load{background:#00ff7f;color:#000;border:none;border-radius:6px;font-size:12px;font-weight:700;padding:7px 12px;cursor:pointer;white-space:nowrap;transition:opacity .15s}',
      '.sq-ms-load:hover{opacity:.85}',
      '.sq-ms-del{background:none;border:1px solid rgba(255,80,80,.3);color:rgba(255,80,80,.6);border-radius:6px;font-size:12px;padding:7px 10px;cursor:pointer;transition:all .15s}',
      '.sq-ms-del:hover{background:rgba(255,80,80,.1);color:#f87171;border-color:#f87171}',
      '.sq-ms-empty{text-align:center;color:#9ca3af;font-size:14px;padding:24px 0}',
    ].join('');
    document.head.appendChild(s);
  }

  function loadUserSquads() {
    if (!currentUser) {
      openAuthModal(function() { loadUserSquads(); });
      return;
    }
    var btn = document.getElementById('squadMySquadsBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }
    apiFetch('squads')
      .then(function(r) {
        if (r.status === 401 || r.status === 403) {
          currentUser = null;
          updateUserBar();
          if (btn) { btn.disabled = false; btn.textContent = 'My Squads'; }
          openAuthModal(function() { loadUserSquads(); });
          return null;
        }
        return r.json();
      })
      .then(function(data) {
        if (!data) return;
        if (btn) { btn.disabled = false; btn.textContent = 'My Squads'; }
        openMySquadsModal(Array.isArray(data) ? data : []);
      })
      .catch(function() {
        if (btn) { btn.disabled = false; btn.textContent = 'My Squads'; }
        showSaveToast('Could not load your squads — try again', true);
      });
  }

  function openMySquadsModal(squads) {
    injectMySquadsCSS();

    var existing = document.getElementById('sq-ms-overlay');
    if (existing) existing.remove();

    var overlay = document.createElement('div');
    overlay.id  = 'sq-ms-overlay';

    var rows = squads.length
      ? squads.map(function(sq, idx) {
          return '<div class="sq-ms-row" data-idx="' + idx + '">'
            + '<div class="sq-ms-info">'
              + '<div class="sq-ms-name">' + escHtml(sq.squad_name || 'Untitled') + '</div>'
              + '<div class="sq-ms-meta">' + escHtml(sq.nation || '') + ' · ' + escHtml(sq.formation || '') + ' · <strong style="color:#e2e8f0">' + (sq.total_points || 0) + ' pts</strong></div>'
            + '</div>'
            + '<button class="sq-ms-load">Load</button>'
            + '<button class="sq-ms-del" aria-label="Delete ' + escHtml(sq.squad_name || 'squad') + '">&times;</button>'
            + '</div>';
        }).join('')
      : '<div class="sq-ms-empty">No saved squads yet.<br>Build one and hit "Save Squad"!</div>';

    overlay.innerHTML = '<div id="sq-ms-modal">'
      + '<div id="sq-ms-head"><span id="sq-ms-title">My Squads</span><button id="sq-ms-close">&times;</button></div>'
      + '<div id="sq-ms-body">' + rows + '</div>'
      + '</div>';

    document.body.appendChild(overlay);

    function closeModal() { var el = document.getElementById('sq-ms-overlay'); if (el) el.remove(); }
    document.getElementById('sq-ms-close').addEventListener('click', closeModal);
    overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });

    overlay.querySelectorAll('.sq-ms-load').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var idx = parseInt(btn.closest('.sq-ms-row').dataset.idx, 10);
        var sq  = squads[idx];
        if (!sq) return;
        loadSavedSquad(sq.squad_data, sq.formation, sq.nation, sq.total_points || 0);
        var nameInput = document.getElementById('squadTeamName');
        if (nameInput) nameInput.value = sq.squad_name || '';
        closeModal();
        flashMsg('Squad loaded!');
      });
    });

    overlay.querySelectorAll('.sq-ms-del').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var idx = parseInt(btn.closest('.sq-ms-row').dataset.idx, 10);
        var sq  = squads[idx];
        if (!sq) return;
        if (!confirm('Delete "' + (sq.squad_name || 'this squad') + '"?')) return;
        apiFetch('squads/' + sq.id, { method: 'DELETE' })
          .then(function(r){ return r.json(); })
          .then(function(data) {
            if (data.deleted) {
              squads.splice(idx, 1);
              closeModal();
              openMySquadsModal(squads);
            }
          })
          .catch(function(){ flashMsg('Delete failed — please try again'); });
      });
    });
  }

  /* ── Load a saved squad into the builder ────────────────────────── */
  function loadSavedSquad(savedSlots, formation, nationName, totalPts) {
    currentFormation    = formation || currentFormation;
    currentNation       = nationName || currentNation;
    loadedSquadTotalPts = (typeof totalPts === 'number') ? totalPts : null;
    document.querySelectorAll('.squad-formation-btn').forEach(function(b){
      b.classList.toggle('active', b.dataset.formation === currentFormation);
    });
    initSquad();
    if (Array.isArray(savedSlots)) {
      savedSlots.forEach(function(saved, i) {
        if (saved.player && squad[i]) {
          squad[i].player = saved.player;
          squad[i].flag   = saved.flag || FLAGS[currentNation] || '⚽';
        }
      });
    }
    selectedSlot = -1;
    renderNationGrid();
    renderPitch();
    renderPool();
  }

})();
