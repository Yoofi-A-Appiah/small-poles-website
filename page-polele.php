<?php get_header(); ?>

<div class="polele-wrap">
  <div class="container">

    <div class="polele-header">
      <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>" class="back-link">← Games</a>
      <div class="polele-title-row">
        <h1>Polele <span class="polele-day" id="poleleDay">#1</span></h1>
        <div class="polele-actions">
          <button class="polele-btn-icon" id="poleleHow" title="How to play">? How to play</button>
          <button class="polele-btn-icon" id="poleleShare" title="Share" style="display:none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/></svg>
          </button>
        </div>
      </div>
      <p class="polele-sub">Guess today's World Cup player in 6 attempts. Each wrong guess reveals colour-coded clues about the mystery player.</p>
    </div>

    <!-- How to play modal -->
    <div class="polele-modal" id="poleleModal">
      <div class="polele-modal-box">
        <button class="polele-modal-close" id="poleleModalClose">×</button>
        <h3>How to play</h3>
        <p>Type any World Cup player's name and press Enter. After each guess you'll get colour-coded clues:</p>
        <div class="polele-legend">
          <div class="polele-legend-row"><span class="pl-cell pl-green">✓</span><span>Exact match</span></div>
          <div class="polele-legend-row"><span class="pl-cell pl-yellow">↑ older</span><span>Answer's age is higher — guess older</span></div>
          <div class="polele-legend-row"><span class="pl-cell pl-yellow">↓ younger</span><span>Answer's age is lower — guess younger</span></div>
          <div class="polele-legend-row"><span class="pl-cell pl-red">✗</span><span>No match</span></div>
        </div>
        <div class="polele-col-labels">
          <span>Nation</span><span>Club</span><span>Pos</span><span>Age</span><span>Conf</span><span>League</span>
        </div>
        <p class="polele-note">A new player every day. Share your result to flex on the timeline.</p>
      </div>
    </div>

    <!-- Column headers -->
    <div class="polele-col-header">
      <div class="pol-h-guess">Player</div>
      <div class="pol-h-attr">Nation</div>
      <div class="pol-h-attr">Club</div>
      <div class="pol-h-attr">Pos</div>
      <div class="pol-h-attr">Age</div>
      <div class="pol-h-attr">Conf</div>
      <div class="pol-h-attr">League</div>
    </div>

    <!-- Colour key -->
    <div class="polele-key-strip">
      <span class="polele-key-item polele-key--green">🟩 Exact match</span>
      <span class="polele-key-item polele-key--yellow">🟨 Age: guess higher ↑ / lower ↓</span>
      <span class="polele-key-item polele-key--red">🟥 No match</span>
    </div>

    <!-- Guess rows (filled by JS) -->
    <div class="polele-rows" id="poleleRows"></div>

    <!-- Input -->
    <div class="polele-input-wrap" id="poleleInputWrap">
      <div class="polele-input-inner">
        <input
          type="text"
          id="poleleInput"
          class="polele-input"
          placeholder="Type a player name…"
          autocomplete="off"
          autocorrect="off"
          spellcheck="false"
          maxlength="60"
        />
        <button class="polele-submit" id="poleleSubmit">Guess</button>
      </div>
      <div class="polele-autocomplete" id="poleleAuto"></div>
      <p class="polele-counter" id="poleleCounter">0 / 6 guesses used</p>
    </div>

    <!-- End state banner -->
    <div class="polele-end" id="poleleEnd" style="display:none">
      <div class="polele-end-inner">
        <div class="polele-end-icon" id="poleleEndIcon">🎉</div>
        <div class="polele-end-text" id="poleleEndText"></div>
        <div class="polele-end-answer" id="poleleEndAnswer"></div>
        <button class="polele-share-btn" id="poleleShareBtn">Share result</button>
        <p class="polele-next">Next Polele in <span id="poleleCountdown">--:--:--</span></p>
      </div>
    </div>

  </div>
</div>

<?php get_footer(); ?>
