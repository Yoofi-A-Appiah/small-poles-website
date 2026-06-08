<?php get_header(); ?>

<div class="hl-wrap">
  <div class="container">

    <div class="hl-header">
      <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>" class="back-link">← Games</a>
      <h1>Higher or <span class="accent">Lower</span></h1>
      <p class="hl-sub">The left player's stat is revealed. Does the right player have more or fewer?</p>
    </div>

    <!-- Game -->
    <div id="hlGame">

      <div class="hl-scorebar">
        <div class="hl-score-item">
          <span class="hl-score-label">Streak</span>
          <span class="hl-score-num" id="hlStreak">0</span>
        </div>
        <div class="hl-score-sep">🔥</div>
        <div class="hl-score-item">
          <span class="hl-score-label">Best</span>
          <span class="hl-score-num" id="hlBest">0</span>
        </div>
      </div>

      <p class="hl-question" id="hlQuestion">Loading…</p>

      <div class="hl-arena">

        <div class="hl-card hl-card--anchor" id="hlCardA">
          <div class="hl-card-flag" id="hlAFlag"></div>
          <div class="hl-card-name" id="hlAName"></div>
          <div class="hl-card-nat"  id="hlANat"></div>
          <div class="hl-card-pos"  id="hlAPos"></div>
          <div class="hl-card-val"  id="hlAVal"></div>
        </div>

        <div class="hl-vs">VS</div>

        <div class="hl-card hl-card--challenger" id="hlCardB">
          <div class="hl-card-flag" id="hlBFlag"></div>
          <div class="hl-card-name" id="hlBName"></div>
          <div class="hl-card-nat"  id="hlBNat"></div>
          <div class="hl-card-pos"  id="hlBPos"></div>
          <div class="hl-card-val hl-card-val--hidden" id="hlBVal">?</div>
        </div>

      </div>

      <div class="hl-buttons">
        <button class="hl-btn hl-btn--higher" id="hlBtnHigher">↑ Higher</button>
        <button class="hl-btn hl-btn--lower"  id="hlBtnLower">↓ Lower</button>
      </div>

    </div>

    <!-- Game over -->
    <div class="hl-gameover" id="hlGameover" style="display:none">
      <div class="hl-go-icon">💥</div>
      <div class="hl-go-score" id="hlGoScore"></div>
      <div class="hl-go-best"  id="hlGoBest"></div>
      <div class="hl-go-msg"   id="hlGoMsg"></div>
      <button class="btn-primary hl-play-again" id="hlPlayAgain">Play Again</button>
    </div>

  </div>
</div>

<?php get_footer(); ?>
