/* Polele — World Cup 2026 Player Guessing Game
   Pure client-side. State persists in localStorage keyed by date.
   Player list rotates daily from a fixed seed date. */

;(function () {
  'use strict';

  /* ── Player database ────────────────────────────────────────────
     Fields: n=name, t=team(nationality), c=club, p=position,
             a=age(2026), cf=confederation, lg=league             */
  var PLAYERS = [
    // Goalkeepers
    {n:"Alisson Becker",     t:"Brazil",       c:"Liverpool",        p:"GK",  a:33, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Ederson",            t:"Brazil",       c:"Man City",         p:"GK",  a:32, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Manuel Neuer",       t:"Germany",      c:"Bayern Munich",    p:"GK",  a:40, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Thibaut Courtois",   t:"Belgium",      c:"Real Madrid",      p:"GK",  a:34, cf:"UEFA",     lg:"La Liga"},
    {n:"Jordan Pickford",    t:"England",      c:"Everton",          p:"GK",  a:32, cf:"UEFA",     lg:"Premier League"},
    {n:"David Raya",         t:"Spain",        c:"Arsenal",          p:"GK",  a:30, cf:"UEFA",     lg:"Premier League"},
    {n:"Diogo Costa",        t:"Portugal",     c:"Porto",            p:"GK",  a:26, cf:"UEFA",     lg:"Primeira Liga"},
    {n:"Yann Sommer",        t:"Switzerland",  c:"Inter Milan",      p:"GK",  a:37, cf:"UEFA",     lg:"Serie A"},
    {n:"Andre Onana",        t:"Cameroon",     c:"Man United",       p:"GK",  a:30, cf:"CAF",      lg:"Premier League"},
    {n:"Lawrence Ati-Zigi",  t:"Ghana",        c:"St Gallen",        p:"GK",  a:28, cf:"CAF",      lg:"Super League"},
    {n:"Mike Maignan",       t:"France",       c:"AC Milan",         p:"GK",  a:30, cf:"UEFA",     lg:"Serie A"},
    {n:"Gregor Kobel",       t:"Switzerland",  c:"Dortmund",         p:"GK",  a:28, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Gianluigi Donnarumma",t:"Italy",       c:"PSG",              p:"GK",  a:27, cf:"UEFA",     lg:"Ligue 1"},
    // Defenders
    {n:"Virgil van Dijk",    t:"Netherlands",  c:"Liverpool",        p:"DEF", a:34, cf:"UEFA",     lg:"Premier League"},
    {n:"Ruben Dias",         t:"Portugal",     c:"Man City",         p:"DEF", a:28, cf:"UEFA",     lg:"Premier League"},
    {n:"Marquinhos",         t:"Brazil",       c:"PSG",              p:"DEF", a:32, cf:"CONMEBOL", lg:"Ligue 1"},
    {n:"Antonio Rudiger",    t:"Germany",      c:"Real Madrid",      p:"DEF", a:33, cf:"UEFA",     lg:"La Liga"},
    {n:"Achraf Hakimi",      t:"Morocco",      c:"PSG",              p:"DEF", a:27, cf:"CAF",      lg:"Ligue 1"},
    {n:"Kieran Trippier",    t:"England",      c:"Newcastle",        p:"DEF", a:35, cf:"UEFA",     lg:"Premier League"},
    {n:"Kyle Walker",        t:"England",      c:"Man City",         p:"DEF", a:36, cf:"UEFA",     lg:"Premier League"},
    {n:"Jules Kounde",       t:"France",       c:"Barcelona",        p:"DEF", a:27, cf:"UEFA",     lg:"La Liga"},
    {n:"Theo Hernandez",     t:"France",       c:"AC Milan",         p:"DEF", a:28, cf:"UEFA",     lg:"Serie A"},
    {n:"Ronald Araujo",      t:"Uruguay",      c:"Barcelona",        p:"DEF", a:27, cf:"CONMEBOL", lg:"La Liga"},
    {n:"Lisandro Martinez",  t:"Argentina",    c:"Man United",       p:"DEF", a:28, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Cristian Romero",    t:"Argentina",    c:"Tottenham",        p:"DEF", a:27, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Kalidou Koulibaly",  t:"Senegal",      c:"Al-Hilal",         p:"DEF", a:34, cf:"CAF",      lg:"Saudi League"},
    {n:"Nayef Aguerd",       t:"Morocco",      c:"West Ham",         p:"DEF", a:28, cf:"CAF",      lg:"Premier League"},
    {n:"Mohammed Salisu",    t:"Ghana",        c:"Strasbourg",       p:"DEF", a:25, cf:"CAF",      lg:"Ligue 1"},
    {n:"Denzel Dumfries",    t:"Netherlands",  c:"Inter Milan",      p:"DEF", a:30, cf:"UEFA",     lg:"Serie A"},
    {n:"Benjamin Pavard",    t:"France",       c:"Inter Milan",      p:"DEF", a:30, cf:"UEFA",     lg:"Serie A"},
    {n:"Joao Cancelo",       t:"Portugal",     c:"Barcelona",        p:"DEF", a:32, cf:"UEFA",     lg:"La Liga"},
    {n:"Dani Carvajal",      t:"Spain",        c:"Real Madrid",      p:"DEF", a:34, cf:"UEFA",     lg:"La Liga"},
    {n:"Aymeric Laporte",    t:"Spain",        c:"Al-Nassr",         p:"DEF", a:32, cf:"UEFA",     lg:"Saudi League"},
    {n:"Lucas Hernandez",    t:"France",       c:"Bayern Munich",    p:"DEF", a:29, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Jonathan Tah",       t:"Germany",      c:"Bayer Leverkusen", p:"DEF", a:30, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Nico Schlotterbeck", t:"Germany",      c:"Dortmund",         p:"DEF", a:26, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Kim Min-jae",        t:"South Korea",  c:"Bayern Munich",    p:"DEF", a:29, cf:"AFC",      lg:"Bundesliga"},
    {n:"Takehiro Tomiyasu",  t:"Japan",        c:"Arsenal",          p:"DEF", a:27, cf:"AFC",      lg:"Premier League"},
    {n:"Robin Le Normand",   t:"Spain",        c:"Atletico Madrid",  p:"DEF", a:28, cf:"UEFA",     lg:"La Liga"},
    {n:"Ferdi Kadioglu",     t:"Turkey",       c:"Fenerbahce",       p:"DEF", a:25, cf:"UEFA",     lg:"Super Lig"},
    // Midfielders
    {n:"Kevin De Bruyne",    t:"Belgium",      c:"Man City",         p:"MID", a:35, cf:"UEFA",     lg:"Premier League"},
    {n:"Luka Modric",        t:"Croatia",      c:"Real Madrid",      p:"MID", a:41, cf:"UEFA",     lg:"La Liga"},
    {n:"Rodri",              t:"Spain",        c:"Man City",         p:"MID", a:30, cf:"UEFA",     lg:"Premier League"},
    {n:"Jude Bellingham",    t:"England",      c:"Real Madrid",      p:"MID", a:22, cf:"UEFA",     lg:"La Liga"},
    {n:"Declan Rice",        t:"England",      c:"Arsenal",          p:"MID", a:27, cf:"UEFA",     lg:"Premier League"},
    {n:"Pedri",              t:"Spain",        c:"Barcelona",        p:"MID", a:23, cf:"UEFA",     lg:"La Liga"},
    {n:"Gavi",               t:"Spain",        c:"Barcelona",        p:"MID", a:22, cf:"UEFA",     lg:"La Liga"},
    {n:"Federico Valverde",  t:"Uruguay",      c:"Real Madrid",      p:"MID", a:27, cf:"CONMEBOL", lg:"La Liga"},
    {n:"Enzo Fernandez",     t:"Argentina",    c:"Chelsea",          p:"MID", a:25, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Bruno Fernandes",    t:"Portugal",     c:"Man United",       p:"MID", a:32, cf:"UEFA",     lg:"Premier League"},
    {n:"Bernardo Silva",     t:"Portugal",     c:"Man City",         p:"MID", a:32, cf:"UEFA",     lg:"Premier League"},
    {n:"Vitinha",            t:"Portugal",     c:"PSG",              p:"MID", a:25, cf:"UEFA",     lg:"Ligue 1"},
    {n:"Eduardo Camavinga",  t:"France",       c:"Real Madrid",      p:"MID", a:23, cf:"UEFA",     lg:"La Liga"},
    {n:"Aurelien Tchouameni",t:"France",       c:"Real Madrid",      p:"MID", a:26, cf:"UEFA",     lg:"La Liga"},
    {n:"Granit Xhaka",       t:"Switzerland",  c:"Bayer Leverkusen", p:"MID", a:33, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Wataru Endo",        t:"Japan",        c:"Liverpool",        p:"MID", a:31, cf:"AFC",      lg:"Premier League"},
    {n:"Sofyan Amrabat",     t:"Morocco",      c:"Fenerbahce",       p:"MID", a:29, cf:"CAF",      lg:"Super Lig"},
    {n:"Pape Matar Sarr",    t:"Senegal",      c:"Tottenham",        p:"MID", a:22, cf:"CAF",      lg:"Premier League"},
    {n:"Mohammed Kudus",     t:"Ghana",        c:"West Ham",         p:"MID", a:24, cf:"CAF",      lg:"Premier League"},
    {n:"Thomas Partey",      t:"Ghana",        c:"Arsenal",          p:"MID", a:33, cf:"CAF",      lg:"Premier League"},
    {n:"Alexis Mac Allister",t:"Argentina",    c:"Liverpool",        p:"MID", a:27, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Casemiro",           t:"Brazil",       c:"Man United",       p:"MID", a:34, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Bruno Guimaraes",    t:"Brazil",       c:"Newcastle",        p:"MID", a:27, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Frenkie de Jong",    t:"Netherlands",  c:"Barcelona",        p:"MID", a:29, cf:"UEFA",     lg:"La Liga"},
    {n:"Florian Wirtz",      t:"Germany",      c:"Bayer Leverkusen", p:"MID", a:23, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Jamal Musiala",      t:"Germany",      c:"Bayern Munich",    p:"MID", a:23, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Heung-min Son",      t:"South Korea",  c:"Tottenham",        p:"MID", a:34, cf:"AFC",      lg:"Premier League"},
    {n:"Xavi Simons",        t:"Netherlands",  c:"RB Leipzig",       p:"MID", a:23, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Tijjani Reijnders",  t:"Netherlands",  c:"AC Milan",         p:"MID", a:27, cf:"UEFA",     lg:"Serie A"},
    {n:"Mikel Merino",       t:"Spain",        c:"Arsenal",          p:"MID", a:28, cf:"UEFA",     lg:"Premier League"},
    {n:"Fabian Ruiz",        t:"Spain",        c:"PSG",              p:"MID", a:29, cf:"UEFA",     lg:"Ligue 1"},
    {n:"Samuel Chukwueze",   t:"Nigeria",      c:"AC Milan",         p:"MID", a:27, cf:"CAF",      lg:"Serie A"},
    {n:"Alex Iwobi",         t:"Nigeria",      c:"Fulham",           p:"MID", a:30, cf:"CAF",      lg:"Premier League"},
    {n:"Idrissa Gueye",      t:"Senegal",      c:"Everton",          p:"MID", a:36, cf:"CAF",      lg:"Premier League"},
    {n:"Ao Tanaka",          t:"Japan",        c:"Dortmund",         p:"MID", a:27, cf:"AFC",      lg:"Bundesliga"},
    {n:"Robert Andrich",     t:"Germany",      c:"Bayer Leverkusen", p:"MID", a:31, cf:"UEFA",     lg:"Bundesliga"},
    // Forwards
    {n:"Kylian Mbappe",      t:"France",       c:"Real Madrid",      p:"FWD", a:27, cf:"UEFA",     lg:"La Liga"},
    {n:"Harry Kane",         t:"England",      c:"Bayern Munich",    p:"FWD", a:32, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Lionel Messi",       t:"Argentina",    c:"Inter Miami",      p:"FWD", a:38, cf:"CONMEBOL", lg:"MLS"},
    {n:"Vinicius Jr",        t:"Brazil",       c:"Real Madrid",      p:"FWD", a:25, cf:"CONMEBOL", lg:"La Liga"},
    {n:"Rodrygo",            t:"Brazil",       c:"Real Madrid",      p:"FWD", a:25, cf:"CONMEBOL", lg:"La Liga"},
    {n:"Raphinha",           t:"Brazil",       c:"Barcelona",        p:"FWD", a:29, cf:"CONMEBOL", lg:"La Liga"},
    {n:"Lamine Yamal",       t:"Spain",        c:"Barcelona",        p:"FWD", a:18, cf:"UEFA",     lg:"La Liga"},
    {n:"Nico Williams",      t:"Spain",        c:"Athletic Bilbao",  p:"FWD", a:22, cf:"UEFA",     lg:"La Liga"},
    {n:"Alvaro Morata",      t:"Spain",        c:"AC Milan",         p:"FWD", a:33, cf:"UEFA",     lg:"Serie A"},
    {n:"Cristiano Ronaldo",  t:"Portugal",     c:"Al-Nassr",         p:"FWD", a:41, cf:"UEFA",     lg:"Saudi League"},
    {n:"Rafael Leao",        t:"Portugal",     c:"AC Milan",         p:"FWD", a:27, cf:"UEFA",     lg:"Serie A"},
    {n:"Joao Felix",         t:"Portugal",     c:"Chelsea",          p:"FWD", a:27, cf:"UEFA",     lg:"Premier League"},
    {n:"Romelu Lukaku",      t:"Belgium",      c:"Napoli",           p:"FWD", a:33, cf:"UEFA",     lg:"Serie A"},
    {n:"Jeremy Doku",        t:"Belgium",      c:"Man City",         p:"FWD", a:22, cf:"UEFA",     lg:"Premier League"},
    {n:"Lois Openda",        t:"Belgium",      c:"RB Leipzig",       p:"FWD", a:24, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Charles De Ketelaere",t:"Belgium",     c:"Atalanta",         p:"FWD", a:24, cf:"UEFA",     lg:"Serie A"},
    {n:"Robert Lewandowski", t:"Poland",       c:"Barcelona",        p:"FWD", a:37, cf:"UEFA",     lg:"La Liga"},
    {n:"Bukayo Saka",        t:"England",      c:"Arsenal",          p:"FWD", a:24, cf:"UEFA",     lg:"Premier League"},
    {n:"Phil Foden",         t:"England",      c:"Man City",         p:"FWD", a:26, cf:"UEFA",     lg:"Premier League"},
    {n:"Marcus Rashford",    t:"England",      c:"Aston Villa",      p:"FWD", a:28, cf:"UEFA",     lg:"Premier League"},
    {n:"Florian Wirtz",      t:"Germany",      c:"Bayer Leverkusen", p:"FWD", a:23, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Leroy Sane",         t:"Germany",      c:"Bayern Munich",    p:"FWD", a:30, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Karim Adeyemi",      t:"Germany",      c:"Dortmund",         p:"FWD", a:24, cf:"UEFA",     lg:"Bundesliga"},
    {n:"Sadio Mane",         t:"Senegal",      c:"Al-Nassr",         p:"FWD", a:34, cf:"CAF",      lg:"Saudi League"},
    {n:"Ismaila Sarr",       t:"Senegal",      c:"Marseille",        p:"FWD", a:28, cf:"CAF",      lg:"Ligue 1"},
    {n:"Nicolas Jackson",    t:"Senegal",      c:"Chelsea",          p:"FWD", a:24, cf:"CAF",      lg:"Premier League"},
    {n:"Victor Osimhen",     t:"Nigeria",      c:"Chelsea",          p:"FWD", a:27, cf:"CAF",      lg:"Premier League"},
    {n:"Mohamed Salah",      t:"Egypt",        c:"Liverpool",        p:"FWD", a:34, cf:"CAF",      lg:"Premier League"},
    {n:"Hakim Ziyech",       t:"Morocco",      c:"Galatasaray",      p:"FWD", a:33, cf:"CAF",      lg:"Super Lig"},
    {n:"Youssef En-Nesyri",  t:"Morocco",      c:"Fenerbahce",       p:"FWD", a:29, cf:"CAF",      lg:"Super Lig"},
    {n:"Inaki Williams",     t:"Ghana",        c:"Athletic Bilbao",  p:"FWD", a:32, cf:"CAF",      lg:"La Liga"},
    {n:"Antoine Semenyo",    t:"Ghana",        c:"Bournemouth",      p:"FWD", a:25, cf:"CAF",      lg:"Premier League"},
    {n:"Kamaldeen Sulemana", t:"Ghana",        c:"Southampton",      p:"FWD", a:23, cf:"CAF",      lg:"Premier League"},
    {n:"Antoine Griezmann",  t:"France",       c:"Atletico Madrid",  p:"FWD", a:35, cf:"UEFA",     lg:"La Liga"},
    {n:"Ousmane Dembele",    t:"France",       c:"PSG",              p:"FWD", a:29, cf:"UEFA",     lg:"Ligue 1"},
    {n:"Marcus Thuram",      t:"France",       c:"Inter Milan",      p:"FWD", a:28, cf:"UEFA",     lg:"Serie A"},
    {n:"Julian Alvarez",     t:"Argentina",    c:"Atletico Madrid",  p:"FWD", a:26, cf:"CONMEBOL", lg:"La Liga"},
    {n:"Lautaro Martinez",   t:"Argentina",    c:"Inter Milan",      p:"FWD", a:28, cf:"CONMEBOL", lg:"Serie A"},
    {n:"Darwin Nunez",       t:"Uruguay",      c:"Liverpool",        p:"FWD", a:26, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Luis Diaz",          t:"Colombia",     c:"Liverpool",        p:"FWD", a:28, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Jhon Duran",         t:"Colombia",     c:"Aston Villa",      p:"FWD", a:22, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Cody Gakpo",         t:"Netherlands",  c:"Liverpool",        p:"FWD", a:26, cf:"UEFA",     lg:"Premier League"},
    {n:"Memphis Depay",      t:"Netherlands",  c:"Atletico Madrid",  p:"FWD", a:32, cf:"UEFA",     lg:"La Liga"},
    {n:"Santiago Gimenez",   t:"Mexico",       c:"AC Milan",         p:"FWD", a:24, cf:"CONCACAF", lg:"Serie A"},
    {n:"Hirving Lozano",     t:"Mexico",       c:"PSV",              p:"FWD", a:30, cf:"CONCACAF", lg:"Eredivisie"},
    {n:"Arda Guler",         t:"Turkey",       c:"Real Madrid",      p:"FWD", a:20, cf:"UEFA",     lg:"La Liga"},
    {n:"Richarlison",        t:"Brazil",       c:"Tottenham",        p:"FWD", a:29, cf:"CONMEBOL", lg:"Premier League"},
    {n:"Ademola Lookman",    t:"Nigeria",      c:"Atalanta",         p:"FWD", a:27, cf:"CAF",      lg:"Serie A"},
    {n:"Hwang Hee-chan",      t:"South Korea",  c:"Wolverhampton",    p:"FWD", a:29, cf:"AFC",      lg:"Premier League"},
    {n:"Takumi Minamino",    t:"Japan",        c:"Monaco",           p:"FWD", a:31, cf:"AFC",      lg:"Ligue 1"},
    {n:"Neymar",             t:"Brazil",       c:"Santos",           p:"FWD", a:34, cf:"CONMEBOL", lg:"Serie A Brasil"},
  ];

  /* De-dupe by name (Florian Wirtz appears twice above) */
  var seen = {};
  PLAYERS = PLAYERS.filter(function(p){
    if (seen[p.n]) return false;
    seen[p.n] = true;
    return true;
  });

  /* ── Daily player selection ───────────────────────────────────── */
  var LAUNCH_DATE = new Date('2026-06-08T00:00:00');

  function todayStr() {
    var d = new Date();
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
  }

  function pad(n) { return n < 10 ? '0'+n : ''+n; }

  function dayNumber() {
    var now  = new Date();
    var diff = Math.floor((Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())
                         - Date.UTC(2026, 5, 8)) / 86400000);
    return Math.max(0, diff);
  }

  function todaysPlayer() {
    return PLAYERS[ dayNumber() % PLAYERS.length ];
  }

  /* ── localStorage state ──────────────────────────────────────── */
  var STATE_KEY = 'polele_' + todayStr();

  function loadState() {
    try {
      var raw = localStorage.getItem(STATE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch(e) { return null; }
  }

  function saveState(s) {
    try { localStorage.setItem(STATE_KEY, JSON.stringify(s)); } catch(e){}
  }

  /* ── Comparison helpers ──────────────────────────────────────── */
  function cmpExact(a, b) {
    return a === b ? 'green' : 'red';
  }

  function cmpAge(guessAge, answerAge) {
    if (guessAge === answerAge) return { cls:'green', icon:'✓' };
    return guessAge < answerAge
      ? { cls:'yellow', icon:'↑ older' }
      : { cls:'yellow', icon:'↓ younger' };
  }

  function compareGuess(guess, answer) {
    return [
      { label:'Nation', val:guess.t, cls: cmpExact(guess.t, answer.t), icon: guess.t === answer.t ? '✓' : '✗' },
      { label:'Club',   val:guess.c, cls: cmpExact(guess.c, answer.c), icon: guess.c === answer.c ? '✓' : '✗' },
      { label:'Pos',    val:guess.p, cls: cmpExact(guess.p, answer.p), icon: guess.p === answer.p ? '✓' : '✗' },
      Object.assign({ label:'Age', val:guess.a }, cmpAge(guess.a, answer.a)),
      { label:'Conf',   val:guess.cf, cls: cmpExact(guess.cf, answer.cf), icon: guess.cf === answer.cf ? '✓' : '✗' },
      { label:'League', val:guess.lg, cls: cmpExact(guess.lg, answer.lg), icon: guess.lg === answer.lg ? '✓' : '✗' },
    ];
  }

  /* ── DOM helpers ─────────────────────────────────────────────── */
  function buildRow(playerName, clues) {
    var row = document.createElement('div');
    row.className = 'polele-row polele-row--in';

    var nameCell = document.createElement('div');
    nameCell.className = 'pol-cell pol-cell--name';
    nameCell.textContent = playerName;
    row.appendChild(nameCell);

    clues.forEach(function(c) {
      var cell = document.createElement('div');
      cell.className = 'pol-cell pl-cell pl-' + c.cls;
      var top = document.createElement('span');
      top.className = 'pol-cell-val';
      top.textContent = c.val;
      var bot = document.createElement('span');
      bot.className = 'pol-cell-icon';
      bot.textContent = c.icon;
      cell.appendChild(top);
      cell.appendChild(bot);
      row.appendChild(cell);
    });

    return row;
  }

  function buildEmptyRow() {
    var row = document.createElement('div');
    row.className = 'polele-row polele-row--empty';
    for (var i = 0; i < 7; i++) {
      var cell = document.createElement('div');
      cell.className = i === 0 ? 'pol-cell pol-cell--name pol-cell--blank' : 'pol-cell pol-cell--blank';
      row.appendChild(cell);
    }
    return row;
  }

  /* ── Share result ────────────────────────────────────────────── */
  function buildShareText(state) {
    var icons = { green:'🟩', yellow:'🟨', red:'🟥' };
    var won   = state.won;
    var lines = state.guesses.map(function(g) {
      return g.clues.map(function(c) { return icons[c.cls]; }).join('');
    });
    var result = won ? state.guesses.length + '/6' : 'X/6';
    return 'Polele #' + (dayNumber() + 1) + ' ' + result + '\n' + lines.join('\n') + '\nsmallpoles.online/games/polele/';
  }

  /* ── Autocomplete ────────────────────────────────────────────── */
  var selectedAuto = -1;

  function renderAuto(matches, input) {
    var el = document.getElementById('poleleAuto');
    el.innerHTML = '';
    selectedAuto = -1;
    if (!matches.length) { el.style.display = 'none'; return; }
    el.style.display = 'block';
    matches.slice(0, 6).forEach(function(p) {
      var item = document.createElement('div');
      item.className = 'polele-auto-item';
      item.textContent = p.n + ' · ' + p.t + ' · ' + p.p;
      item.addEventListener('mousedown', function(e) {
        e.preventDefault();
        input.value = p.n;
        el.style.display = 'none';
        /* Submit immediately on click — no need to press Guess again */
        setTimeout(function(){ submitGuess(p.n); }, 0);
      });
      el.appendChild(item);
    });
  }

  /* ── Main init ───────────────────────────────────────────────── */
  var answer  = todaysPlayer();
  var state   = loadState() || { guesses: [], won: false, lost: false };
  var MAX     = 6;
  var usedNames = state.guesses.map(function(g){ return g.name.toLowerCase(); });

  function render() {
    var rowsEl    = document.getElementById('poleleRows');
    var inputWrap = document.getElementById('poleleInputWrap');
    var endEl     = document.getElementById('poleleEnd');
    var counterEl = document.getElementById('poleleCounter');
    var shareBtn  = document.getElementById('poleleShare');

    rowsEl.innerHTML = '';

    state.guesses.forEach(function(g) {
      rowsEl.appendChild( buildRow(g.name, g.clues) );
    });

    var remaining = MAX - state.guesses.length;
    for (var i = 0; i < remaining; i++) {
      rowsEl.appendChild( buildEmptyRow() );
    }

    counterEl.textContent = state.guesses.length + ' / ' + MAX + ' guesses used';

    if (state.won || state.lost) {
      inputWrap.style.display = 'none';
      endEl.style.display = 'block';
      shareBtn.style.display = '';

      var icon = document.getElementById('poleleEndIcon');
      var text = document.getElementById('poleleEndText');
      var answ = document.getElementById('poleleEndAnswer');

      if (state.won) {
        icon.textContent = '🎉';
        text.textContent = state.guesses.length === 1
          ? 'Incredible! First try!'
          : 'You got it in ' + state.guesses.length + '!';
      } else {
        icon.textContent = '😬';
        text.textContent = "Not this time — it was:";
      }

      answ.innerHTML = '<strong>' + answer.n + '</strong> · ' + answer.t + ' · ' + answer.c + ' · ' + answer.p;
      startCountdown();
    }
  }

  function resolvePlayer(raw) {
    var trimmed = raw.trim().toLowerCase();
    if (!trimmed) return null;
    /* 1. Exact full-name match */
    var p = PLAYERS.find(function(p){ return p.n.toLowerCase() === trimmed; });
    if (p) return p;
    /* 2. Any word in the name matches (e.g. "Mbappe", "Salah", "Ronaldo") */
    var wordMatches = PLAYERS.filter(function(p){
      return p.n.toLowerCase().split(' ').some(function(w){ return w === trimmed; });
    });
    if (wordMatches.length === 1) return wordMatches[0];
    /* 3. Unique prefix match — only if exactly one player starts with the input */
    var prefixMatches = PLAYERS.filter(function(p){
      return p.n.toLowerCase().indexOf(trimmed) !== -1;
    });
    if (prefixMatches.length === 1) return prefixMatches[0];
    return null;
  }

  function submitGuess(name) {
    var trimmed = name.trim();
    if (!trimmed) return;

    var player = resolvePlayer(trimmed);
    if (!player) {
      shake();
      showMsg('Player not in the list — use autocomplete to pick');
      return;
    }

    if (usedNames.indexOf(player.n.toLowerCase()) !== -1) {
      showMsg('Already guessed ' + player.n);
      return;
    }

    var clues = compareGuess(player, answer);
    state.guesses.push({ name: player.n, clues: clues });
    usedNames.push(player.n.toLowerCase());

    if (player.n === answer.n) {
      state.won = true;
    } else if (state.guesses.length >= MAX) {
      state.lost = true;
    }

    saveState(state);
    render();

    document.getElementById('poleleInput').value = '';
    document.getElementById('poleleAuto').style.display = 'none';
    document.getElementById('poleleSubmit').disabled = true;
  }

  function shake() {
    var inp = document.getElementById('poleleInput');
    inp.classList.add('shake');
    setTimeout(function(){ inp.classList.remove('shake'); }, 400);
  }

  function showMsg(msg) {
    var el = document.getElementById('poleleCounter');
    var orig = el.textContent;
    el.textContent = msg;
    el.classList.add('polele-msg--err');
    setTimeout(function(){
      el.textContent = orig;
      el.classList.remove('polele-msg--err');
    }, 2500);
  }

  function startCountdown() {
    var el = document.getElementById('poleleCountdown');
    function tick() {
      var now    = new Date();
      var tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
      var diff   = tomorrow - now;
      var h = Math.floor(diff / 3600000);
      var m = Math.floor((diff % 3600000) / 60000);
      var s = Math.floor((diff % 60000) / 1000);
      el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ── Boot ────────────────────────────────────────────────────── */
  document.getElementById('poleleDay').textContent = '#' + (dayNumber() + 1);

  render();

  var inputEl  = document.getElementById('poleleInput');
  var submitEl = document.getElementById('poleleSubmit');

  submitEl.disabled = true;

  inputEl.addEventListener('input', function() {
    var val = this.value.trim();
    submitEl.disabled = val.length < 2;
    if (val.length < 2) {
      document.getElementById('poleleAuto').style.display = 'none';
      return;
    }
    var lower   = val.toLowerCase();
    var matches = PLAYERS.filter(function(p){
      return p.n.toLowerCase().indexOf(lower) !== -1
          && usedNames.indexOf(p.n.toLowerCase()) === -1;
    });
    renderAuto(matches, inputEl);
  });

  inputEl.addEventListener('keydown', function(e) {
    var autoEl = document.getElementById('poleleAuto');
    var items  = autoEl.querySelectorAll('.polele-auto-item');
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      selectedAuto = Math.min(selectedAuto + 1, items.length - 1);
      items.forEach(function(it, i){ it.classList.toggle('active', i === selectedAuto); });
      if (items[selectedAuto]) inputEl.value = items[selectedAuto].textContent.split(' · ')[0];
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      selectedAuto = Math.max(selectedAuto - 1, -1);
      items.forEach(function(it, i){ it.classList.toggle('active', i === selectedAuto); });
      if (selectedAuto >= 0 && items[selectedAuto]) inputEl.value = items[selectedAuto].textContent.split(' · ')[0];
    } else if (e.key === 'Enter') {
      e.preventDefault();
      /* If user hasn't arrow-keyed to a selection, auto-pick the first autocomplete match */
      if (autoEl.style.display !== 'none' && items.length > 0 && selectedAuto === -1) {
        inputEl.value = items[0].textContent.split(' · ')[0];
      }
      autoEl.style.display = 'none';
      submitGuess(inputEl.value);
    } else if (e.key === 'Escape') {
      autoEl.style.display = 'none';
      selectedAuto = -1;
    }
  });

  submitEl.addEventListener('click', function() {
    var autoEl = document.getElementById('poleleAuto');
    var items  = autoEl.querySelectorAll('.polele-auto-item');
    /* Auto-fill first suggestion if user typed but didn't select */
    if (autoEl.style.display !== 'none' && items.length > 0) {
      inputEl.value = items[0].textContent.split(' · ')[0];
    }
    autoEl.style.display = 'none';
    submitGuess(inputEl.value);
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.polele-input-inner')) {
      document.getElementById('poleleAuto').style.display = 'none';
    }
  });

  /* Share buttons */
  function doShare() {
    var text = buildShareText(state);
    if (navigator.share) {
      navigator.share({ text: text }).catch(function(){});
    } else {
      navigator.clipboard.writeText(text).then(function(){
        showMsg('Copied to clipboard!');
      }).catch(function(){
        showMsg('Could not copy — please copy manually');
      });
    }
  }

  document.getElementById('poleleShare').addEventListener('click', doShare);
  document.getElementById('poleleShareBtn').addEventListener('click', doShare);

  /* How to play modal */
  document.getElementById('poleleHow').addEventListener('click', function(){
    document.getElementById('poleleModal').style.display = 'flex';
  });
  document.getElementById('poleleModalClose').addEventListener('click', function(){
    document.getElementById('poleleModal').style.display = 'none';
  });
  document.getElementById('poleleModal').addEventListener('click', function(e){
    if (e.target === this) this.style.display = 'none';
  });

  /* Auto-show how to play on first visit */
  if (!localStorage.getItem('polele_seen_howto')) {
    document.getElementById('poleleModal').style.display = 'flex';
    localStorage.setItem('polele_seen_howto', '1');
  }

})();
