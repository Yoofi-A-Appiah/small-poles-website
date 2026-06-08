<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="<?php echo get_template_directory_uri(); ?>/assets/smallpolescutout.ico" />
  <link rel="apple-touch-icon" href="<?php echo get_template_directory_uri(); ?>/assets/smallpolesappicon.png" />
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Navigation -->
<nav>
  <div class="container">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-logo">
      <div class="nav-logo-mark">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/smallpolesappicon.png"
             alt="Small Poles Logo" style="width:64px;height:64px;border-radius:16px" />
      </div>
      <span class="nav-logo-text">Small Poles</span>
    </a>

    <?php
    $is_news_section  = smallpoles_is_news_archive() || is_category() || ( is_single() && get_post_type() === 'post' );
    $current_game     = get_query_var( 'smallpoles_game' );
    $is_games_section = ! empty( $current_game );
    ?>
    <ul class="nav-links" id="navLinks">
      <li>
        <?php if ( is_front_page() ) : ?>
          <span class="nav-status"><span class="nav-status-dot"></span>Home</span>
        <?php else : ?>
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
        <?php endif; ?>
      </li>
      <li>
        <a href="<?php echo esc_url( home_url( '/#features' ) ); ?>">Features</a>
      </li>
      <li>
        <?php if ( $is_news_section ) : ?>
          <span class="nav-status"><span class="nav-status-dot"></span>News</span>
        <?php else : ?>
          <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News</a>
        <?php endif; ?>
      </li>
      <li class="nav-has-dropdown">
        <?php if ( $is_games_section ) : ?>
          <span class="nav-status"><span class="nav-status-dot"></span>Games ▾</span>
        <?php else : ?>
          <a href="<?php echo esc_url( home_url( '/games/' ) ); ?>">Games ▾</a>
        <?php endif; ?>
        <ul class="nav-dropdown">
          <?php if ( sp_game_is_visible( 'higher-lower' ) ) : ?>
          <li><a href="<?php echo esc_url( home_url( '/games/higher-lower/' ) ); ?>">📊 Higher or Lower</a></li>
          <?php endif; ?>
          <?php if ( sp_game_is_visible( 'polele' ) ) : ?>
          <li><a href="<?php echo esc_url( home_url( '/games/polele/' ) ); ?>">⚽ Polele</a></li>
          <?php endif; ?>
          <?php if ( sp_game_is_visible( 'bracket' ) ) : ?>
          <li><a href="<?php echo esc_url( home_url( '/games/bracket/' ) ); ?>">🏆 Bracket Challenge</a></li>
          <?php endif; ?>
          <?php if ( sp_game_is_visible( 'squad' ) ) : ?>
          <li><a href="<?php echo esc_url( home_url( '/games/squad/' ) ); ?>">👕 Squad Builder</a></li>
          <?php endif; ?>
        </ul>
      </li>
      <li>
        <a href="<?php echo esc_url( home_url( '/#waitlist' ) ); ?>" class="nav-cta">Join Beta</a>
      </li>
    </ul>

    <button class="mobile-toggle" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>
<div class="nav-overlay" id="navOverlay"></div>

<!-- Announcement Bar -->
<div class="announcement-bar" id="annBar">
  <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">
    <span class="ann-text-desktop">⚽ World Cup 2026 is live. Read our analysis as Ghana's stars take the global stage →</span>
    <span class="ann-text-mobile">⚽ World Cup 2026 is live. Read our analysis as Ghana's stars take the global stage →</span>
  </a>
  <button class="ann-dismiss" aria-label="Dismiss">&times;</button>
</div>
