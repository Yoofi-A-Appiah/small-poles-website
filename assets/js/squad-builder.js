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
      } else {
        circle.textContent = pos.pos;
        dot.appendChild(circle);
      }

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

  document.getElementById('squadShareBtn').addEventListener('click', function() {
    var filled = squad.filter(function(s){ return s.player; });
    if (filled.length < 11) {
      flashMsg('Fill all 11 slots before sharing!');
      return;
    }
    captureAndShare();
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
  try {
    console.log('[SP Squad] SQUADS keys:', Object.keys(SQUADS));
    initSquad();
    console.log('[SP Squad] initSquad OK');
    renderNationGrid();
    console.log('[SP Squad] renderNationGrid OK');
    renderPitch();
    console.log('[SP Squad] renderPitch OK');
    renderPool();
    console.log('[SP Squad] renderPool OK');
  } catch (err) {
    console.error('[SP Squad] boot error:', err);
  }

})();
