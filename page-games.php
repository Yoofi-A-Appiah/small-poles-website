<?php get_header(); ?>

<div class="games-hub">
  <div class="games-hub-hero">
    <div class="container">
      <span class="label">World Cup 2026</span>
      <h1>Play the games.<br /><span class="accent">Win the bragging rights.</span></h1>
      <p>Free mini-games built around the World Cup. No login. No download. Just football knowledge and good vibes.</p>
    </div>
  </div>

  <div class="container">
    <div class="games-grid">

      <?php if ( sp_game_is_visible( 'higher-lower' ) ) : ?>
      <a href="<?php echo esc_url( home_url( '/games/higher-lower/' ) ); ?>" class="game-card game-card--higher-lower">
        <div class="game-card-badge">Streak</div>
        <div class="game-card-icon">📊</div>
        <h2>Higher or Lower</h2>
        <p>Two World Cup players, one stat. Does the mystery player have more or fewer? Keep your streak alive.</p>
        <div class="game-card-meta">
          <span>60 players</span>
          <span>·</span>
          <span>Goals, caps &amp; age</span>
        </div>
        <div class="game-card-cta">Play now →</div>
      </a>
      <?php endif; ?>

      <?php if ( sp_game_is_visible( 'polele' ) ) : ?>
      <a href="<?php echo esc_url( home_url( '/games/polele/' ) ); ?>" class="game-card game-card--polele">
        <div class="game-card-badge">Daily</div>
        <div class="game-card-icon">⚽</div>
        <h2>Polele</h2>
        <p>Guess today's mystery World Cup player in 6 attempts. Clues unlock with every wrong guess.</p>
        <div class="game-card-meta">
          <span>6 guesses</span>
          <span>·</span>
          <span>New player daily</span>
        </div>
        <div class="game-card-cta">Play now →</div>
      </a>
      <?php endif; ?>

      <?php if ( sp_game_is_visible( 'bracket' ) ) : ?>
      <a href="<?php echo esc_url( home_url( '/games/bracket/' ) ); ?>" class="game-card game-card--bracket">
        <div class="game-card-badge">Tournament</div>
        <div class="game-card-icon">🏆</div>
        <h2>Bracket Challenge</h2>
        <p>Pick every winner from Group Stage to the Final. How many did you call right?</p>
        <div class="game-card-meta">
          <span>48 teams</span>
          <span>·</span>
          <span>Shareable results</span>
        </div>
        <div class="game-card-cta">Fill your bracket →</div>
      </a>
      <?php endif; ?>

      <?php if ( sp_game_is_visible( 'squad' ) ) : ?>
      <a href="<?php echo esc_url( home_url( '/games/squad/' ) ); ?>" class="game-card game-card--squad">
        <div class="game-card-badge">Builder</div>
        <div class="game-card-icon">👕</div>
        <h2>Squad Builder</h2>
        <p>Pick your dream World Cup XI from any nation. Budget cap, formation, captain — the full works.</p>
        <div class="game-card-meta">
          <span>10 nations</span>
          <span>·</span>
          <span>Share your squad</span>
        </div>
        <div class="game-card-cta">Build your squad →</div>
      </a>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php get_footer(); ?>
