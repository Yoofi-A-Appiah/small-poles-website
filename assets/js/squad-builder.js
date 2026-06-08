/* World Cup 2026 Squad Builder
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
  var selectedSlot     = -1;   // which pitch slot is being filled
  var squad            = [];   // array of 11 { slot, player } or null

  function initSquad() {
    squad = FORMATIONS[currentFormation].positions.map(function(pos, i) {
      return { slot: i, pos: pos.pos, player: null };
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

      dot.style.left = pos.x + '%';
      dot.style.top  = pos.y + '%';

      var circle = document.createElement('div');
      circle.className = 'sp-player-dot-circle';
      circle.textContent = s.player ? s.player.n.split(' ').pop().substring(0,8) : pos.pos;

      var cost = document.createElement('div');
      cost.className = 'sp-player-dot-name';
      cost.textContent = s.player ? s.player.n.split(' ')[0] : '';

      dot.appendChild(circle);
      dot.appendChild(cost);

      dot.addEventListener('click', (function(idx){ return function(){ selectSlot(idx); }; })(i));
      pitch.appendChild(dot);
    });

    updateBudgetDisplay();
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

        card.innerHTML = '<span class="spc-pos spc-pos--' + player.p.toLowerCase() + '">' + player.p + '</span>'
          + '<span class="spc-name">' + player.n + '</span>'
          + '<span class="spc-val">' + player.v + ' pts</span>';

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
      selectedSlot = -1;
    } else {
      /* Find first empty slot matching position */
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
    }
    renderPitch();
    renderPool();
  }

  function removePlayer(player) {
    var s = squad.find(function(s){ return s.player && s.player.n === player.n; });
    if (s) {
      s.player = null;
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

  /* ── Share modal ────────────────────────────────────────────────── */
  var SHARE_CSS = [
    '#sq-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(4px)}',
    '#sq-modal{background:#1a1a2e;border-radius:16px;max-width:400px;width:100%;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.6);animation:sq-in .2s ease}',
    '@keyframes sq-in{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}',
    '#sq-card{padding:24px;background:linear-gradient(135deg,#0f3460 0%,#16213e 100%)}',
    '.sq-ch{display:flex;align-items:center;gap:14px;margin-bottom:20px}',
    '.sq-flag{font-size:40px;line-height:1;flex-shrink:0}',
    '.sq-nation{font-size:22px;font-weight:800;color:#fff;margin:0;line-height:1.1}',
    '.sq-meta{font-size:13px;color:rgba(255,255,255,.55);margin:4px 0 0}',
    '.sq-rows{display:flex;flex-direction:column;gap:10px}',
    '.sq-row{display:flex;align-items:flex-start;gap:10px}',
    '.sq-badge{font-size:10px;font-weight:700;padding:3px 7px;border-radius:4px;color:#fff;min-width:34px;text-align:center;flex-shrink:0;margin-top:1px}',
    '.sq-b-gk{background:#c47d00}.sq-b-def{background:#1e73be}.sq-b-mid{background:#2a7a2a}.sq-b-fwd{background:#c0392b}',
    '.sq-names{font-size:13px;color:rgba(255,255,255,.88);line-height:1.5}',
    '.sq-foot{margin-top:18px;font-size:11px;color:rgba(255,255,255,.35);text-align:center;letter-spacing:.5px}',
    '.sq-actions{display:flex;gap:8px;padding:16px 20px;background:#111}',
    '.sq-btn{flex:1;padding:11px 6px;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s;letter-spacing:.3px}',
    '.sq-btn:hover{opacity:.82}',
    '.sq-btn-copy{background:#0073aa;color:#fff}',
    '.sq-btn-share{background:#27ae60;color:#fff}',
    '.sq-btn-close{background:#2a2a2a;color:#aaa}',
  ].join('');

  function injectShareStyles() {
    if (!document.getElementById('sp-sq-css')) {
      var s = document.createElement('style');
      s.id  = 'sp-sq-css';
      s.textContent = SHARE_CSS;
      document.head.appendChild(s);
    }
  }

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    var ok = false;
    try { ok = document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(ta);
    return ok ? Promise.resolve() : Promise.reject();
  }

  function buildShareText(byPos) {
    var lines = ['🏟️ My ' + currentNation + ' World Cup XI (' + currentFormation + ')', ''];
    ['GK','DEF','MID','FWD'].forEach(function(pos){
      if (byPos[pos].length) lines.push(pos + ': ' + byPos[pos].join(', '));
    });
    lines.push('', 'Budget: ' + calcSpent() + '/100 pts', 'smallpoles.online/games/squad/');
    return lines.join('\n');
  }

  function openShareModal() {
    var filled = squad.filter(function(s){ return s.player; });
    var byPos  = { GK:[], DEF:[], MID:[], FWD:[] };
    filled.forEach(function(s){ byPos[s.pos].push(s.player.n); });

    var flag    = FLAGS[currentNation] || '';
    var text    = buildShareText(byPos);
    var posRows = ['GK','DEF','MID','FWD'].map(function(pos) {
      if (!byPos[pos].length) return '';
      return '<div class="sq-row">'
        + '<span class="sq-badge sq-b-' + pos.toLowerCase() + '">' + pos + '</span>'
        + '<span class="sq-names">' + byPos[pos].join(', ') + '</span>'
        + '</div>';
    }).join('');

    injectShareStyles();

    var overlay = document.createElement('div');
    overlay.id  = 'sq-overlay';
    overlay.innerHTML = '<div id="sq-modal">'
      + '<div id="sq-card">'
      + '<div class="sq-ch"><span class="sq-flag">' + flag + '</span>'
      + '<div><p class="sq-nation">' + currentNation + ' XI</p>'
      + '<p class="sq-meta">' + currentFormation + ' &nbsp;·&nbsp; ' + calcSpent() + '/100 pts</p>'
      + '</div></div>'
      + '<div class="sq-rows">' + posRows + '</div>'
      + '<p class="sq-foot">smallpoles.online/games/squad/</p>'
      + '</div>'
      + '<div class="sq-actions">'
      + '<button class="sq-btn sq-btn-copy" id="sq-copy-btn">Copy Text</button>'
      + (navigator.share ? '<button class="sq-btn sq-btn-share" id="sq-native-btn">Share</button>' : '')
      + '<button class="sq-btn sq-btn-close" id="sq-close-btn">Close</button>'
      + '</div></div>';

    document.body.appendChild(overlay);

    function closeModal() { var el = document.getElementById('sq-overlay'); if (el) el.remove(); }

    overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });
    document.getElementById('sq-close-btn').addEventListener('click', closeModal);

    document.getElementById('sq-copy-btn').addEventListener('click', function() {
      var btn = this;
      copyText(text).then(function() {
        btn.textContent = '✓ Copied!';
        setTimeout(function(){ btn.textContent = 'Copy Text'; }, 2500);
      }).catch(function() {
        /* clipboard failed — show the text so user can copy manually */
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'width:100%;margin-top:12px;background:#0a0a1a;color:#eee;border:1px solid #333;border-radius:6px;padding:10px;font-size:12px;resize:none';
        ta.rows = 8;
        document.getElementById('sq-card').appendChild(ta);
        ta.select();
        btn.textContent = 'Select & copy above';
      });
    });

    var nativeBtn = document.getElementById('sq-native-btn');
    if (nativeBtn) {
      nativeBtn.addEventListener('click', function() {
        navigator.share({ text: text }).catch(function(){});
      });
    }
  }

  document.getElementById('squadShareBtn').addEventListener('click', function() {
    var filled = squad.filter(function(s){ return s.player; });
    if (filled.length < 11) {
      flashMsg('Fill all 11 slots before sharing!');
      return;
    }
    openShareModal();
  });

  /* ── Nation grid ─────────────────────────────────────────────────── */
  function renderNationGrid() {
    var grid = document.getElementById('squadNationGrid');
    grid.innerHTML = '';
    Object.keys(SQUADS).forEach(function(nation) {
      var btn = document.createElement('button');
      btn.className = 'squad-nation-btn';
      if (nation === currentNation) btn.classList.add('active');
      btn.innerHTML = (FLAGS[nation] || '') + ' ' + nation;
      btn.addEventListener('click', function() {
        currentNation = nation;
        selectedSlot  = -1;
        /* Do NOT call initSquad() here — preserve existing picks.
           Use "Clear Squad" button to start over. */
        renderNationGrid();
        renderPitch();
        renderPool();
        document.getElementById('squadPoolTitle').textContent = nation + ' Player Pool';
      });
      grid.appendChild(btn);
    });
  }

  /* ── Formation buttons ───────────────────────────────────────────── */
  document.getElementById('squadFormationRow').addEventListener('click', function(e) {
    var btn = e.target.closest('.squad-formation-btn');
    if (!btn) return;
    currentFormation = btn.dataset.formation;
    document.querySelectorAll('.squad-formation-btn').forEach(function(b){
      b.classList.toggle('active', b === btn);
    });
    /* Migrate existing picks into new formation slots by position */
    var prevPlayers = squad.filter(function(s){ return s.player; }).map(function(s){ return s.player; });
    initSquad();
    prevPlayers.forEach(function(player) {
      var slot = squad.find(function(s){ return !s.player && s.pos === player.p; });
      if (slot) slot.player = player;
    });
    selectedSlot = -1;
    renderPitch();
    renderPool();
  });

  /* ── Filter buttons ──────────────────────────────────────────────── */
  document.querySelectorAll('.squad-filter-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      currentFilter = btn.dataset.pos;
      document.querySelectorAll('.squad-filter-btn').forEach(function(b){
        b.classList.toggle('active', b === btn);
      });
      renderPool();
    });
  });

  /* ── Clear ───────────────────────────────────────────────────────── */
  document.getElementById('squadClear').addEventListener('click', function() {
    initSquad();
    selectedSlot = -1;
    renderPitch();
    renderPool();
  });

  /* ── Boot ────────────────────────────────────────────────────────── */
  initSquad();
  renderNationGrid();
  renderPitch();
  renderPool();

})();
