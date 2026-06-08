/* Higher or Lower — World Cup Player Stats
   Classic: left card value is revealed, guess if right card is higher or lower. */

;(function () {
  'use strict';

  var PLAYERS = [
    // GKs
    {n:"Alisson Becker",      t:"Brazil",       flag:"🇧🇷", p:"GK",  a:33, g:0,   cp:80},
    {n:"Manuel Neuer",        t:"Germany",      flag:"🇩🇪", p:"GK",  a:40, g:0,   cp:124},
    {n:"Gianluigi Donnarumma",t:"Italy",        flag:"🇮🇹", p:"GK",  a:27, g:0,   cp:61},
    {n:"Jordan Pickford",     t:"England",      flag:"🏴󠁧󠁢󠁥󠁮󠁧󠁿", p:"GK",  a:32, g:0,   cp:62},
    {n:"Mike Maignan",        t:"France",       flag:"🇫🇷", p:"GK",  a:30, g:0,   cp:25},
    // DEFs
    {n:"Virgil van Dijk",     t:"Netherlands",  flag:"🇳🇱", p:"DEF", a:34, g:14,  cp:64},
    {n:"Achraf Hakimi",       t:"Morocco",      flag:"🇲🇦", p:"DEF", a:27, g:22,  cp:75},
    {n:"Antonio Rudiger",     t:"Germany",      flag:"🇩🇪", p:"DEF", a:33, g:4,   cp:67},
    {n:"Jules Kounde",        t:"France",       flag:"🇫🇷", p:"DEF", a:27, g:4,   cp:43},
    {n:"Theo Hernandez",      t:"France",       flag:"🇫🇷", p:"DEF", a:28, g:8,   cp:25},
    {n:"Kalidou Koulibaly",   t:"Senegal",      flag:"🇸🇳", p:"DEF", a:34, g:8,   cp:79},
    {n:"Lisandro Martinez",   t:"Argentina",    flag:"🇦🇷", p:"DEF", a:28, g:4,   cp:30},
    {n:"Ruben Dias",          t:"Portugal",     flag:"🇵🇹", p:"DEF", a:28, g:3,   cp:57},
    {n:"Mohammed Salisu",     t:"Ghana",        flag:"🇬🇭", p:"DEF", a:25, g:3,   cp:25},
    {n:"Kim Min-jae",         t:"South Korea",  flag:"🇰🇷", p:"DEF", a:29, g:4,   cp:56},
    // MIDs
    {n:"Kevin De Bruyne",     t:"Belgium",      flag:"🇧🇪", p:"MID", a:35, g:28,  cp:106},
    {n:"Luka Modric",         t:"Croatia",      flag:"🇭🇷", p:"MID", a:41, g:24,  cp:179},
    {n:"Rodri",               t:"Spain",        flag:"🇪🇸", p:"MID", a:30, g:18,  cp:70},
    {n:"Jude Bellingham",     t:"England",      flag:"🏴󠁧󠁢󠁥󠁮󠁧󠁿", p:"MID", a:22, g:26,  cp:44},
    {n:"Declan Rice",         t:"England",      flag:"🏴󠁧󠁢󠁥󠁮󠁧󠁿", p:"MID", a:27, g:10,  cp:61},
    {n:"Pedri",               t:"Spain",        flag:"🇪🇸", p:"MID", a:23, g:10,  cp:38},
    {n:"Gavi",                t:"Spain",        flag:"🇪🇸", p:"MID", a:22, g:14,  cp:45},
    {n:"Florian Wirtz",       t:"Germany",      flag:"🇩🇪", p:"MID", a:23, g:12,  cp:28},
    {n:"Jamal Musiala",       t:"Germany",      flag:"🇩🇪", p:"MID", a:23, g:14,  cp:42},
    {n:"Enzo Fernandez",      t:"Argentina",    flag:"🇦🇷", p:"MID", a:25, g:9,   cp:36},
    {n:"Alexis Mac Allister", t:"Argentina",    flag:"🇦🇷", p:"MID", a:27, g:15,  cp:46},
    {n:"Bruno Fernandes",     t:"Portugal",     flag:"🇵🇹", p:"MID", a:32, g:16,  cp:71},
    {n:"Sofyan Amrabat",      t:"Morocco",      flag:"🇲🇦", p:"MID", a:29, g:2,   cp:53},
    {n:"Mohammed Kudus",      t:"Ghana",        flag:"🇬🇭", p:"MID", a:24, g:15,  cp:34},
    {n:"Thomas Partey",       t:"Ghana",        flag:"🇬🇭", p:"MID", a:33, g:14,  cp:50},
    {n:"Pape Matar Sarr",     t:"Senegal",      flag:"🇸🇳", p:"MID", a:22, g:5,   cp:35},
    {n:"Heung-min Son",       t:"South Korea",  flag:"🇰🇷", p:"MID", a:34, g:37,  cp:121},
    {n:"Federico Valverde",   t:"Uruguay",      flag:"🇺🇾", p:"MID", a:27, g:14,  cp:60},
    // FWDs
    {n:"Cristiano Ronaldo",   t:"Portugal",     flag:"🇵🇹", p:"FWD", a:41, g:130, cp:215},
    {n:"Lionel Messi",        t:"Argentina",    flag:"🇦🇷", p:"FWD", a:38, g:109, cp:183},
    {n:"Kylian Mbappe",       t:"France",       flag:"🇫🇷", p:"FWD", a:27, g:53,  cp:89},
    {n:"Harry Kane",          t:"England",      flag:"🏴󠁧󠁢󠁥󠁮󠁧󠁿", p:"FWD", a:32, g:68,  cp:102},
    {n:"Robert Lewandowski",  t:"Poland",       flag:"🇵🇱", p:"FWD", a:37, g:82,  cp:148},
    {n:"Romelu Lukaku",       t:"Belgium",      flag:"🇧🇪", p:"FWD", a:33, g:68,  cp:113},
    {n:"Antoine Griezmann",   t:"France",       flag:"🇫🇷", p:"FWD", a:35, g:53,  cp:132},
    {n:"Mohamed Salah",       t:"Egypt",        flag:"🇪🇬", p:"FWD", a:34, g:55,  cp:101},
    {n:"Sadio Mane",          t:"Senegal",      flag:"🇸🇳", p:"FWD", a:34, g:39,  cp:102},
    {n:"Neymar",              t:"Brazil",       flag:"🇧🇷", p:"FWD", a:34, g:79,  cp:128},
    {n:"Vinicius Jr",         t:"Brazil",       flag:"🇧🇷", p:"FWD", a:25, g:28,  cp:50},
    {n:"Raphinha",            t:"Brazil",       flag:"🇧🇷", p:"FWD", a:29, g:28,  cp:58},
    {n:"Julian Alvarez",      t:"Argentina",    flag:"🇦🇷", p:"FWD", a:26, g:30,  cp:52},
    {n:"Lautaro Martinez",    t:"Argentina",    flag:"🇦🇷", p:"FWD", a:28, g:37,  cp:67},
    {n:"Bukayo Saka",         t:"England",      flag:"🏴󠁧󠁢󠁥󠁮󠁧󠁿", p:"FWD", a:24, g:22,  cp:48},
    {n:"Phil Foden",          t:"England",      flag:"🏴󠁧󠁢󠁥󠁮󠁧󠁿", p:"FWD", a:26, g:17,  cp:41},
    {n:"Marcus Rashford",     t:"England",      flag:"🏴󠁧󠁢󠁥󠁮󠁧󠁿", p:"FWD", a:28, g:17,  cp:60},
    {n:"Lamine Yamal",        t:"Spain",        flag:"🇪🇸", p:"FWD", a:18, g:15,  cp:26},
    {n:"Nico Williams",       t:"Spain",        flag:"🇪🇸", p:"FWD", a:22, g:6,   cp:28},
    {n:"Hakim Ziyech",        t:"Morocco",      flag:"🇲🇦", p:"FWD", a:33, g:22,  cp:63},
    {n:"Youssef En-Nesyri",   t:"Morocco",      flag:"🇲🇦", p:"FWD", a:29, g:22,  cp:55},
    {n:"Inaki Williams",      t:"Ghana",        flag:"🇬🇭", p:"FWD", a:32, g:18,  cp:30},
    {n:"Antoine Semenyo",     t:"Ghana",        flag:"🇬🇭", p:"FWD", a:25, g:5,   cp:25},
    {n:"Kamaldeen Sulemana",  t:"Ghana",        flag:"🇬🇭", p:"FWD", a:23, g:8,   cp:28},
    {n:"Darwin Nunez",        t:"Uruguay",      flag:"🇺🇾", p:"FWD", a:26, g:22,  cp:57},
    {n:"Luis Diaz",           t:"Colombia",     flag:"🇨🇴", p:"FWD", a:28, g:22,  cp:60},
    {n:"Santiago Gimenez",    t:"Mexico",       flag:"🇲🇽", p:"FWD", a:24, g:18,  cp:30},
    {n:"Arda Guler",          t:"Turkey",       flag:"🇹🇷", p:"FWD", a:20, g:6,   cp:25},
    {n:"Richarlison",         t:"Brazil",       flag:"🇧🇷", p:"FWD", a:29, g:20,  cp:55},
    {n:"Nicolas Jackson",     t:"Senegal",      flag:"🇸🇳", p:"FWD", a:24, g:10,  cp:30},
  ];

  /* Use DB players passed via wp_localize_script, fall back to hardcoded list */
  if ( typeof hlData !== 'undefined' && hlData.players && hlData.players.length >= 5 ) {
    PLAYERS = hlData.players;
  }

  var STATS = [
    { key:'g',  label:'international goals', unit:'goals', skipGK:true  },
    { key:'cp', label:'international caps',  unit:'caps',  skipGK:false },
    { key:'a',  label:'age',                 unit:'yrs',   skipGK:false },
  ];

  /* ── State ─────────────────────────────────────────────────────── */
  var streak   = 0;
  var best     = parseInt(localStorage.getItem('hl_best') || '0', 10);
  var anchor, challenger, stat;
  var locked   = false;

  /* ── Helpers ───────────────────────────────────────────────────── */
  function rand(n) { return Math.floor(Math.random() * n); }

  function pickPlayer(exclude) {
    var pool = PLAYERS.filter(function(p){ return p !== exclude; });
    return pool[rand(pool.length)];
  }

  function pickStat(a, b) {
    var pool = STATS.filter(function(s){
      if (s.skipGK && (a.p === 'GK' || b.p === 'GK')) return false;
      return a[s.key] !== b[s.key];
    });
    if (!pool.length) pool = [STATS[1]]; // caps always works
    return pool[rand(pool.length)];
  }

  /* ── DOM refs ──────────────────────────────────────────────────── */
  var $ = function(id){ return document.getElementById(id); };

  /* ── Render ────────────────────────────────────────────────────── */
  function setCard(prefix, player, valText, hidden) {
    $(prefix + 'Flag').textContent = player.flag;
    $(prefix + 'Name').textContent = player.n;
    $(prefix + 'Nat').textContent  = player.t;
    $(prefix + 'Pos').textContent  = player.p;
    var valEl = $(prefix + 'Val');
    valEl.textContent = valText;
    valEl.className   = 'hl-card-val' + (hidden ? ' hl-card-val--hidden' : '');
  }

  function renderRound() {
    locked = false;

    setCard('hlA', anchor,     anchor[stat.key] + ' ' + stat.unit, false);
    setCard('hlB', challenger, '?', true);

    $('hlQuestion').textContent =
      'Does ' + challenger.n + ' have MORE or FEWER ' + stat.label + ' than ' + anchor.n + '?';

    $('hlStreak').textContent = streak;
    $('hlBest').textContent   = best;

    $('hlCardA').className = 'hl-card hl-card--anchor';
    $('hlCardB').className = 'hl-card hl-card--challenger';
    $('hlBtnHigher').disabled = false;
    $('hlBtnLower').disabled  = false;
  }

  function newRound(keepAnchor) {
    if (!keepAnchor) anchor = pickPlayer(null);
    challenger = pickPlayer(anchor);

    // Re-roll if tie on chosen stat
    var attempts = 0;
    do {
      stat = pickStat(anchor, challenger);
      attempts++;
    } while (anchor[stat.key] === challenger[stat.key] && attempts < 20);

    renderRound();
  }

  /* ── Answer ────────────────────────────────────────────────────── */
  function answer(guessHigher) {
    if (locked) return;
    locked = true;

    $('hlBtnHigher').disabled = true;
    $('hlBtnLower').disabled  = true;

    var chalVal = challenger[stat.key];
    var ancVal  = anchor[stat.key];
    var correct = guessHigher ? chalVal > ancVal : chalVal < ancVal;

    $('hlBVal').textContent = chalVal + ' ' + stat.unit;
    $('hlBVal').className   = 'hl-card-val';

    if (correct) {
      streak++;
      if (streak > best) {
        best = streak;
        localStorage.setItem('hl_best', best);
      }
      $('hlCardB').classList.add('hl-card--correct');
      $('hlStreak').textContent = streak;
      $('hlBest').textContent   = best;
      setTimeout(function(){ anchor = challenger; newRound(true); }, 1400);
    } else {
      $('hlCardA').classList.add('hl-card--correct');
      $('hlCardB').classList.add('hl-card--wrong');
      setTimeout(showGameOver, 1800);
    }
  }

  /* ── Game over ─────────────────────────────────────────────────── */
  function showGameOver() {
    $('hlGame').style.display     = 'none';
    $('hlGameover').style.display = 'block';
    $('hlGoScore').textContent    = streak + (streak === 1 ? ' in a row' : ' in a row');
    $('hlGoBest').textContent     = 'Personal best: ' + best;

    var msgs;
    if      (streak >= 15) msgs = ['Football genius. Absolute legend. 🐐'];
    else if (streak >= 10) msgs = ['Incredible knowledge! 🔥', 'You really know your football!'];
    else if (streak >= 5)  msgs = ['Solid effort!', 'Good knowledge!', 'Not bad at all!'];
    else                   msgs = ['Better luck next time!', 'So close!', 'Give it another go!'];
    $('hlGoMsg').textContent = msgs[rand(msgs.length)];
  }

  /* ── Events ────────────────────────────────────────────────────── */
  $('hlBtnHigher').addEventListener('click', function(){ answer(true);  });
  $('hlBtnLower').addEventListener( 'click', function(){ answer(false); });

  $('hlPlayAgain').addEventListener('click', function(){
    streak = 0;
    $('hlGame').style.display     = 'block';
    $('hlGameover').style.display = 'none';
    newRound(false);
  });

  /* ── Boot ──────────────────────────────────────────────────────── */
  $('hlBest').textContent = best;
  newRound(false);

}());
