<?php

function smallpoles_set_default_options() {
    // API defaults — only write if not already saved
    if ( ! get_option( 'smallpoles_team_id' )   ) add_option( 'smallpoles_team_id',   1504 );
    if ( ! get_option( 'smallpoles_league_id' ) ) add_option( 'smallpoles_league_id', 1    );
    if ( ! get_option( 'smallpoles_season' )    ) add_option( 'smallpoles_season',    2026 );


}
add_action( 'after_setup_theme', 'smallpoles_set_default_options' );

add_action( 'after_switch_theme', 'flush_rewrite_rules' );

/* ── News archive routing ──────────────────────────────────────────
   Adds an explicit rewrite rule for /news/ so it serves as a posts
   archive without needing page_for_posts or a dedicated home page.
   'top' priority means it fires before WordPress page routing.
   Pagination: /news/page/2/, /news/page/3/ etc. also work. */

function smallpoles_news_rewrite() {
    add_rewrite_rule(
        '^news(/page/([0-9]+))?/?$',
        'index.php?smallpoles_news=1&paged=$matches[2]',
        'top'
    );
}
add_action( 'init', 'smallpoles_news_rewrite' );

add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'smallpoles_news';
    return $vars;
} );

function smallpoles_is_news_archive( $set = null ) {
    static $flag = false;
    if ( $set !== null ) $flag = $set;
    return $flag;
}

function smallpoles_news_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( $query->get( 'smallpoles_news' ) ) {
        $query->set( 'post_type', 'post' );
        $query->set( 'posts_per_page', get_option( 'posts_per_page', 10 ) );
        $query->is_archive = true;
        $query->is_home    = false;
        smallpoles_is_news_archive( true );
    }
}
add_action( 'pre_get_posts', 'smallpoles_news_query' );

function smallpoles_news_template( $template ) {
    if ( smallpoles_is_news_archive() ) {
        $archive = locate_template( 'archive.php' );
        if ( $archive ) return $archive;
    }
    return $template;
}
add_filter( 'template_include', 'smallpoles_news_template' );


function smallpoles_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'navigation-widgets' ] );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'editor-styles' );

    add_image_size( 'news-card',     600, 338, true );
    add_image_size( 'news-featured', 900, 506, true );

    register_nav_menus( [ 'primary' => 'Primary Navigation' ] );
}
add_action( 'after_setup_theme', 'smallpoles_setup' );


function smallpoles_scripts() {
    wp_enqueue_style( 'smallpoles-style', get_stylesheet_uri(), [], '1.1.9' );
    wp_enqueue_script( 'smallpoles-main', get_template_directory_uri() . '/assets/js/main.js', [], '1.0.7', true );
    wp_localize_script( 'smallpoles-main', 'spData', [
        'restBase' => esc_url_raw( rest_url( 'smallpoles/v1/' ) ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ] );

    $game = sp_current_game();
    if ( $game === 'polele' ) {
        wp_enqueue_script( 'sp-polele', get_template_directory_uri() . '/assets/js/polele.js', [], '1.0.0', true );
    }
    if ( $game === 'bracket' ) {
        wp_enqueue_script( 'html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], null, true );
        wp_enqueue_script( 'sp-bracket', get_template_directory_uri() . '/assets/js/bracket.js', [ 'html2canvas' ], '2.0.0', true );
        wp_localize_script( 'sp-bracket', 'spBracket', [
            'restBase' => esc_url_raw( rest_url( 'smallpoles/v1/' ) ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
        ] );
    }
    if ( $game === 'squad' ) {
        $sb_squads = get_option( 'sb_squads', [] );
        $sb_teams  = get_option( 'sb_teams', sb_teams_default() );
        $sb_flags  = [];
        foreach ( $sb_teams as $t ) {
            $sb_flags[ $t['name'] ] = $t['flag'] ?? '';
        }
        wp_enqueue_script( 'html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], null, true );
        wp_enqueue_script( 'sp-squad', get_template_directory_uri() . '/assets/js/squad-builder.js', [ 'html2canvas' ], '2.1.0', true );
        /* Build per-player fantasy points lookup for pitch display */
        global $wpdb;
        $sp_stats_t  = $wpdb->prefix . 'sp_player_stats';
        $pts_rows    = $wpdb->get_results(
            "SELECT sp_player_key, SUM(points_earned) AS total FROM {$sp_stats_t} WHERE sp_player_key != '' GROUP BY sp_player_key",
            ARRAY_A
        );
        $player_pts  = [];
        if ( is_array( $pts_rows ) ) {
            foreach ( $pts_rows as $r ) {
                $player_pts[ $r['sp_player_key'] ] = (int) $r['total'];
            }
        }

        wp_localize_script( 'sp-squad', 'spSquadData', [
            'squads'       => $sb_squads,
            'flags'        => $sb_flags,
            'restBase'     => esc_url_raw( rest_url( 'smallpoles/v1/' ) ),
            'user'         => sp_squads_get_page_user(),
            'playerPoints' => $player_pts,
        ] );
    }
    if ( $game === 'higher-lower' ) {
        wp_enqueue_script( 'sp-higher-lower', get_template_directory_uri() . '/assets/js/higher-lower.js', [], '1.0.1', true );
        $hl_posts = get_posts( [
            'post_type'      => 'hl_player',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
        $hl_players = array_map( function( $p ) {
            return [
                'n'    => $p->post_title,
                't'    => get_post_meta( $p->ID, '_hl_nationality', true ),
                'flag' => get_post_meta( $p->ID, '_hl_flag',        true ),
                'p'    => get_post_meta( $p->ID, '_hl_position',    true ),
                'a'    => (int) get_post_meta( $p->ID, '_hl_age',   true ),
                'g'    => (int) get_post_meta( $p->ID, '_hl_goals', true ),
                'cp'   => (int) get_post_meta( $p->ID, '_hl_caps',  true ),
            ];
        }, $hl_posts );
        wp_localize_script( 'sp-higher-lower', 'hlData', [ 'players' => array_values( $hl_players ) ] );
    }
}
add_action( 'wp_enqueue_scripts', 'smallpoles_scripts' );


/* ── SEO: WebApplication JSON-LD for game pages (Yoast handles everything else) ── */
function smallpoles_schema() {
    $game = sp_current_game();
    if ( ! $game ) return;

    $names = [
        'hub'          => 'Free World Cup 2026 Games',
        'higher-lower' => 'Higher or Lower — World Cup 2026 Player Stats Game',
        'polele'       => 'Polele — Daily World Cup 2026 Player Guessing Game',
        'bracket'      => 'World Cup 2026 Bracket Challenge',
        'squad'        => 'World Cup 2026 Squad Builder',
        'fixtures'     => 'World Cup 2026 Match Schedule',
    ];
    $descs = [
        'hub'          => 'Free World Cup 2026 mini-games: Higher or Lower, Polele, Bracket Challenge, and Squad Builder.',
        'higher-lower' => 'Guess whether a World Cup player has more or fewer goals, caps, or age than another. Keep your streak alive.',
        'polele'       => 'Daily World Cup player guessing game. 6 attempts, colour-coded clues after every wrong guess.',
        'bracket'      => 'Pick every winner of the 2026 World Cup from Group Stage to Final. Share your bracket.',
        'squad'        => 'Build and share your ultimate World Cup XI. Pick a formation, stay within budget.',
        'fixtures'     => 'Full World Cup 2026 match schedule — past results, live scores, and upcoming fixtures.',
    ];

    $schema = [
        '@context'            => 'https://schema.org',
        '@type'               => 'WebApplication',
        'name'                => $names[ $game ],
        'description'         => $descs[ $game ],
        'url'                 => home_url( '/games/' . ( $game === 'hub' ? '' : $game . '/' ) ),
        'applicationCategory' => 'Game',
        'operatingSystem'     => 'Web',
        'offers'              => [ '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD' ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'smallpoles_schema', 3 );



/* ── Clean up WordPress cruft ── */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );


/* ── Excerpt ── */
function smallpoles_excerpt_length( $length ) { return 22; }
add_filter( 'excerpt_length', 'smallpoles_excerpt_length' );

function smallpoles_excerpt_more( $more ) { return '&hellip;'; }
add_filter( 'excerpt_more', 'smallpoles_excerpt_more' );


/* ── Reading time ── */
function smallpoles_reading_time( $post_id = null ) {
    $content    = get_post_field( 'post_content', $post_id ?: get_the_ID() );
    $word_count = str_word_count( strip_tags( $content ) );
    $minutes    = max( 1, (int) ceil( $word_count / 200 ) );
    return $minutes . ' min read';
}


/* ── Category badge helper ── */
function smallpoles_category_badge( $post_id = null ) {
    $cats = get_the_category( $post_id ?: get_the_ID() );
    if ( empty( $cats ) ) return '';

    $cat  = $cats[0];
    $slug = $cat->slug;

    $class_map = [
        'world-cup'       => 'wc',
        'gpl-analysis'    => 'gpl',
        'platform'        => 'platform',
        'predictions'     => 'wc',
        'uncategorized'   => 'gpl',
    ];

    $label_map = [
        'world-cup'       => '⚽ World Cup',
        'gpl-analysis'    => '🇬🇭 GPL Analysis',
        'platform'        => '🛠 Platform',
        'predictions'     => '⚽ Predictions',
        'uncategorized'   => '🇬🇭 GPL',
    ];

    $badge_class = $class_map[ $slug ] ?? 'gpl';
    $label       = $label_map[ $slug ] ?? esc_html( $cat->name );

    return '<span class="category-badge ' . esc_attr( $badge_class ) . '">' . $label . '</span>';
}


/* ── Formatted date ── */
function smallpoles_post_date() {
    return get_the_date( 'j M Y' );
}


/* ── Related posts ── */
function smallpoles_related_posts( $count = 3 ) {
    $cats    = wp_get_post_categories( get_the_ID() );
    $args    = [
        'category__in'        => $cats,
        'post__not_in'        => [ get_the_ID() ],
        'posts_per_page'      => $count,
        'ignore_sticky_posts' => true,
    ];
    return new WP_Query( $args );
}


/* ── Latest posts for homepage section ── */
function smallpoles_latest_news( $count = 3, $category = '' ) {
    $args = [
        'posts_per_page'      => $count,
        'ignore_sticky_posts' => false,
    ];
    if ( $category ) {
        $args['category_name'] = $category;
    }
    return new WP_Query( $args );
}


/* ═══════════════════════════════════════════════════════════
   ADMIN SETTINGS — Small Poles API configuration
   Settings > Small Poles
   ═══════════════════════════════════════════════════════════ */

function smallpoles_settings_init() {
    register_setting( 'smallpoles', 'smallpoles_api_key', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ] );
    register_setting( 'smallpoles', 'smallpoles_team_id', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1504, // Ghana national team
    ] );
    register_setting( 'smallpoles', 'smallpoles_league_id', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 1, // World Cup
    ] );
    register_setting( 'smallpoles', 'smallpoles_season', [
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 2026,
    ] );

    add_settings_section(
        'smallpoles_api_section',
        'API-Football Integration',
        function() {
            echo '<p style="color:#666;margin-bottom:0">The homepage widget auto-fetches the next upcoming fixture for the configured team. No manual fixture IDs needed.</p>';
        },
        'smallpoles'
    );

    add_settings_field( 'smallpoles_api_key',    'API Key',    'smallpoles_field_api_key',    'smallpoles', 'smallpoles_api_section' );
    add_settings_field( 'smallpoles_team_id',    'Team ID',    'smallpoles_field_team_id',    'smallpoles', 'smallpoles_api_section' );
    add_settings_field( 'smallpoles_league_id',  'League ID',  'smallpoles_field_league_id',  'smallpoles', 'smallpoles_api_section' );
    add_settings_field( 'smallpoles_season',     'Season',     'smallpoles_field_season',     'smallpoles', 'smallpoles_api_section' );
}
add_action( 'admin_init', 'smallpoles_settings_init' );

function smallpoles_field_api_key() {
    $val = get_option( 'smallpoles_api_key', '' );
    ?>
    <input
        type="password"
        name="smallpoles_api_key"
        id="smallpoles_api_key"
        value="<?php echo esc_attr( $val ); ?>"
        class="regular-text"
        autocomplete="new-password"
        placeholder="Paste your api-sports.io key"
    />
    <?php if ( $val ) : ?>
        <p class="description" style="color:#46b450">&#10003; Key saved (ending …<?php echo esc_html( substr( $val, -4 ) ); ?>)</p>
    <?php else : ?>
        <p class="description">Find your key at <a href="https://dashboard.api-football.com" target="_blank">dashboard.api-football.com</a></p>
    <?php endif;
}

function smallpoles_field_team_id() {
    $val = get_option( 'smallpoles_team_id', 16 );
    ?>
    <input type="number" name="smallpoles_team_id" value="<?php echo esc_attr( $val ); ?>" class="small-text" />
    <p class="description">API-Football team ID. Ghana national team = <strong>1504</strong>. GPL clubs have their own IDs.</p>
    <?php
}

function smallpoles_field_league_id() {
    $val = get_option( 'smallpoles_league_id', 1 );
    ?>
    <input type="number" name="smallpoles_league_id" value="<?php echo esc_attr( $val ); ?>" class="small-text" />
    <p class="description">
        World Cup = <strong>1</strong> &nbsp;|&nbsp; Ghana Premier League = <strong>570</strong><br />
        <em>Switch to 570 when the GPL season resumes.</em>
    </p>
    <?php
}

function smallpoles_field_season() {
    $val = get_option( 'smallpoles_season', 2026 );
    ?>
    <input type="number" name="smallpoles_season" value="<?php echo esc_attr( $val ); ?>" class="small-text" />
    <p class="description">
        World Cup = <strong>2026</strong> &nbsp;|&nbsp; GPL current season = <strong>2025</strong><br />
        <em>Must match the league — World Cup uses 2026, GPL uses 2025.</em>
    </p>
    <?php
}

function smallpoles_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $base = rest_url( 'smallpoles/v1' );
    ?>
    <div class="wrap">
        <h1>Small Poles Settings</h1>
        <?php settings_errors( 'smallpoles' ); ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'smallpoles' );
            do_settings_sections( 'smallpoles' );
            submit_button( 'Save Settings' );
            ?>
        </form>

        <hr />
        <h2>API Endpoint Tester</h2>

        <style>
        .sp-tester-card {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            padding: 24px;
            max-width: 860px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .sp-tester-desc {
            color: #50575e;
            margin: 0 0 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        .sp-fixture-row {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .sp-fixture-row label {
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
            color: #1d2327;
        }
        .sp-fixture-row input {
            flex: 1;
            max-width: 200px;
            font-family: monospace;
            font-size: 13px !important;
        }
        .sp-fixture-hint {
            font-size: 12px;
            color: #50575e;
            flex: 1;
        }
        .sp-ep-group {
            margin-bottom: 16px;
        }
        .sp-ep-group-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #8c8f94;
            margin: 0 0 8px;
        }
        .sp-ep-group-label span {
            display: inline-block;
            background: #f0f0f1;
            border-radius: 3px;
            padding: 2px 6px;
            margin-left: 6px;
            font-weight: 500;
            color: #50575e;
            letter-spacing: 0;
            text-transform: none;
            font-size: 11px;
        }
        .sp-ep-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .sp-ep-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dcdcde !important;
            border-radius: 5px !important;
            background: #f6f7f7 !important;
            color: #1d2327 !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            padding: 5px 12px !important;
            height: auto !important;
            cursor: pointer;
            transition: background .12s, border-color .12s;
            position: relative;
        }
        .sp-ep-btn:hover:not(:disabled) {
            background: #fff !important;
            border-color: #2271b1 !important;
            color: #2271b1 !important;
        }
        .sp-ep-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }
        .sp-ep-btn--fixture {
            border-style: dashed !important;
        }
        .sp-ep-btn--loading .sp-btn-label { opacity: .5; }
        .sp-btn-spinner {
            display: none;
            width: 12px; height: 12px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: sp-spin .6s linear infinite;
            flex-shrink: 0;
        }
        .sp-ep-btn--loading .sp-btn-spinner { display: block; }
        @keyframes sp-spin { to { transform: rotate(360deg); } }
        .sp-response-panel {
            margin-top: 20px;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            overflow: hidden;
            display: none;
        }
        .sp-response-header {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f6f7f7;
            border-bottom: 1px solid #dcdcde;
            padding: 10px 14px;
            flex-wrap: wrap;
        }
        .sp-response-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #8c8f94;
        }
        .sp-response-url {
            font-family: monospace;
            font-size: 12px;
            color: #1d2327;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sp-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .sp-status-badge--ok   { background: #edfaef; color: #0a6b24; }
        .sp-status-badge--err  { background: #fce8e8; color: #8a1c1c; }
        .sp-status-badge--load { background: #e8f0fa; color: #2271b1; }
        .sp-timing {
            font-size: 11px;
            color: #8c8f94;
            white-space: nowrap;
        }
        .sp-copy-btn {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #dcdcde !important;
            border-radius: 4px !important;
            background: #fff !important;
            color: #50575e !important;
            font-size: 11px !important;
            padding: 3px 10px !important;
            height: auto !important;
            cursor: pointer;
            transition: color .12s, border-color .12s;
        }
        .sp-copy-btn:hover { border-color: #2271b1 !important; color: #2271b1 !important; }
        .sp-response-body {
            background: #1a1d21;
            color: #abb2bf;
            padding: 16px 18px;
            max-height: 480px;
            overflow: auto;
            font-size: 12px;
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
            line-height: 1.65;
            white-space: pre-wrap;
            word-break: break-all;
            margin: 0;
        }
        .sp-response-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            color: #8c8f94;
            font-size: 13px;
            gap: 6px;
        }
        .sp-response-empty svg { opacity: .35; }
        </style>

        <div class="sp-tester-card">
            <p class="sp-tester-desc">Fire any endpoint and inspect the raw JSON response. Fixture-specific endpoints (dashed border) require a fixture ID.</p>

            <div class="sp-fixture-row">
                <label for="sp_fixture_test">Fixture ID</label>
                <input type="number" id="sp_fixture_test" value="1489385" placeholder="e.g. 1489385" class="regular-text" />
                <p class="sp-fixture-hint">Required for predictions, lineups, stats &amp; odds</p>
            </div>

            <div class="sp-ep-group">
                <p class="sp-ep-group-label">General <span>No fixture ID needed</span></p>
                <div class="sp-ep-btns">
                    <button class="button sp-ep-btn" data-endpoint="next-fixture"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Next Fixture</span></button>
                    <button class="button sp-ep-btn" data-endpoint="standings"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Standings</span></button>
                    <button class="button sp-ep-btn" data-endpoint="fixtures"><span class="sp-btn-spinner"></span><span class="sp-btn-label">All Fixtures</span></button>
                    <button class="button sp-ep-btn" data-endpoint="today-fixtures"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Today &amp; Tomorrow</span></button>
                    <button class="button sp-ep-btn" data-endpoint="rounds"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Rounds</span></button>
                </div>
            </div>

            <div class="sp-ep-group">
                <p class="sp-ep-group-label">Fixture-Specific <span>Fixture ID required</span></p>
                <div class="sp-ep-btns">
                    <button class="button sp-ep-btn sp-ep-btn--fixture" data-endpoint="predictions"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Predictions</span></button>
                    <button class="button sp-ep-btn sp-ep-btn--fixture" data-endpoint="lineups"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Lineups</span></button>
                    <button class="button sp-ep-btn sp-ep-btn--fixture" data-endpoint="fixture-stats"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Match Stats</span></button>
                    <button class="button sp-ep-btn sp-ep-btn--fixture" data-endpoint="odds"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Pre-match Odds</span></button>
                    <button class="button sp-ep-btn sp-ep-btn--fixture" data-endpoint="odds-live"><span class="sp-btn-spinner"></span><span class="sp-btn-label">Live Odds</span></button>
                </div>
            </div>

            <div class="sp-response-panel" id="sp-response-panel" role="region" aria-label="API response" aria-live="polite">
                <div class="sp-response-header">
                    <span class="sp-response-label">Response</span>
                    <code class="sp-response-url" id="sp-response-url"></code>
                    <span class="sp-status-badge" id="sp-status-badge"></span>
                    <span class="sp-timing" id="sp-timing"></span>
                    <button class="button sp-copy-btn" id="sp-copy-btn" aria-label="Copy JSON to clipboard">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        <span id="sp-copy-label">Copy</span>
                    </button>
                </div>
                <pre class="sp-response-body" id="sp-response-body" tabindex="0"></pre>
            </div>
        </div>

        <hr />
        <h2>WC 2026 Bracket Groups</h2>
        <p style="color:#666;margin-bottom:12px">
            Seed the World Cup 2026 group teams used by the Bracket Challenge.
            Click <strong>Fetch from API</strong> to pull from api-football (requires league=1, season=2026 to be set above),
            or paste JSON manually and click <strong>Save Groups</strong>.
        </p>
        <?php $existing = get_option('smallpoles_wc_groups_data'); ?>
        <p style="color:<?php echo $existing ? '#46b450' : '#dc3232'; ?>">
            <?php echo $existing ? '&#10003; Groups seeded (' . count($existing) . ' groups)' : '&#10007; Not seeded — using fallback or API'; ?>
        </p>
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
            <button class="button button-primary" id="sp-fetch-groups">Fetch from API</button>
            <button class="button" id="sp-save-groups-json">Save JSON below</button>
            <button class="button" id="sp-clear-groups" style="color:#dc3232">Clear Seeded Groups</button>
        </div>
        <textarea id="sp-groups-json" style="width:100%;max-width:800px;height:200px;font-family:monospace;font-size:12px"><?php echo $existing ? esc_textarea(json_encode($existing, JSON_PRETTY_PRINT)) : ''; ?></textarea>
        <div id="sp-groups-status" style="margin-top:8px;font-weight:600;display:none"></div>

        <script>
        (function() {
            const base  = '<?php echo esc_js( $base ); ?>';
            const nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';

            // WC Groups seeding
            const groupsTextarea = document.getElementById('sp-groups-json');
            const groupsStatus   = document.getElementById('sp-groups-status');

            function showGroupStatus(msg, ok) {
                groupsStatus.style.display = 'block';
                groupsStatus.style.color   = ok ? '#46b450' : '#dc3232';
                groupsStatus.textContent   = msg;
            }

            document.getElementById('sp-fetch-groups').addEventListener('click', async function() {
                this.disabled = true; this.textContent = 'Fetching…';
                try {
                    const res  = await fetch(base + '/wc-groups', { headers: { 'X-WP-Nonce': nonce } });
                    const data = await res.json();
                    if (!res.ok) { showGroupStatus('API error: ' + (data.message || res.status), false); return; }
                    groupsTextarea.value = JSON.stringify(data, null, 2);
                    // Auto-save
                    const saveRes  = await fetch(base + '/wc-seed-groups', { method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':nonce}, body: JSON.stringify({groups:data}) });
                    const saveData = await saveRes.json();
                    showGroupStatus(saveRes.ok ? '✓ Fetched and saved (' + Object.keys(data).length + ' groups)' : '✗ Fetch OK but save failed', saveRes.ok);
                } catch(e) { showGroupStatus('✗ ' + e.message, false); }
                this.disabled = false; this.textContent = 'Fetch from API';
            });

            document.getElementById('sp-save-groups-json').addEventListener('click', async function() {
                this.disabled = true;
                try {
                    const groups = JSON.parse(groupsTextarea.value);
                    const res  = await fetch(base + '/wc-seed-groups', { method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':nonce}, body: JSON.stringify({groups}) });
                    const data = await res.json();
                    showGroupStatus(res.ok ? '✓ Saved (' + Object.keys(groups).length + ' groups)' : '✗ ' + (data.message||res.status), res.ok);
                } catch(e) { showGroupStatus('✗ ' + e.message, false); }
                this.disabled = false;
            });

            document.getElementById('sp-clear-groups').addEventListener('click', async function() {
                if (!confirm('Clear seeded groups? The bracket will fall back to API or hardcoded data.')) return;
                const res = await fetch(base + '/wc-seed-groups', { method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':nonce}, body: JSON.stringify({groups:{}}) });
                showGroupStatus(res.ok ? '✓ Cleared' : '✗ Failed', res.ok);
                groupsTextarea.value = '';
            });
            // ── API Tester ──────────────────────────────────────────────
            const fixtureInput  = document.getElementById('sp_fixture_test');
            const responsePanel = document.getElementById('sp-response-panel');
            const responseUrl   = document.getElementById('sp-response-url');
            const statusBadge   = document.getElementById('sp-status-badge');
            const timingEl      = document.getElementById('sp-timing');
            const responseBody  = document.getElementById('sp-response-body');
            const copyBtn       = document.getElementById('sp-copy-btn');
            const copyLabel     = document.getElementById('sp-copy-label');

            const fixtureEndpoints = ['predictions','lineups','fixture-stats','odds','odds-live'];

            function setBadge(ok, text) {
                statusBadge.className = 'sp-status-badge ' + (ok === null ? 'sp-status-badge--load' : ok ? 'sp-status-badge--ok' : 'sp-status-badge--err');
                statusBadge.textContent = text;
            }

            document.querySelectorAll('[data-endpoint]').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const ep  = this.dataset.endpoint;
                    const fid = fixtureInput.value.trim();
                    let url   = base + '/' + ep;

                    if (fixtureEndpoints.includes(ep)) {
                        if (!fid) {
                            fixtureInput.focus();
                            fixtureInput.style.outline = '2px solid #dc3232';
                            setTimeout(() => { fixtureInput.style.outline = ''; }, 1800);
                            return;
                        }
                        url += '?fixture=' + encodeURIComponent(fid);
                    }

                    // Loading state
                    this.classList.add('sp-ep-btn--loading');
                    this.disabled = true;
                    responsePanel.style.display = 'block';
                    responseUrl.textContent = url.replace(base, '/smallpoles/v1');
                    setBadge(null, '● Fetching…');
                    timingEl.textContent = '';
                    responseBody.textContent = '';
                    copyLabel.textContent = 'Copy';

                    const t0 = performance.now();
                    try {
                        const res  = await fetch(url, { headers: { 'X-WP-Nonce': nonce } });
                        const ms   = Math.round(performance.now() - t0);
                        const data = await res.json();

                        setBadge(res.ok, (res.ok ? '✓ ' : '✗ ') + res.status);
                        timingEl.textContent = ms + 'ms';
                        responseBody.textContent = JSON.stringify(data, null, 2);
                    } catch(err) {
                        setBadge(false, '✗ Network error');
                        responseBody.textContent = err.message;
                    }

                    this.classList.remove('sp-ep-btn--loading');
                    this.disabled = false;
                    responseBody.scrollTop = 0;
                });
            });

            // Highlight fixture input border on focus when empty + fixture endpoint
            document.querySelectorAll('.sp-ep-btn--fixture').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    if (!fixtureInput.value.trim()) fixtureInput.style.borderColor = '#2271b1';
                });
                btn.addEventListener('mouseleave', function() {
                    fixtureInput.style.borderColor = '';
                });
            });

            // Copy JSON
            copyBtn.addEventListener('click', function() {
                const text = responseBody.textContent;
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    copyLabel.textContent = '✓ Copied!';
                    setTimeout(() => { copyLabel.textContent = 'Copy'; }, 2000);
                }).catch(() => {
                    copyLabel.textContent = 'Failed';
                });
            });
        })();
        </script>
    </div>
    <?php
}

function smallpoles_add_settings_menu() {
    add_options_page(
        'Small Poles Settings',
        'Small Poles',
        'manage_options',
        'smallpoles',
        'smallpoles_settings_page'
    );
}
add_action( 'admin_menu', 'smallpoles_add_settings_menu' );


/* ═══════════════════════════════════════════════════════════
   POST META — fixture_id field on posts
   ═══════════════════════════════════════════════════════════ */

function smallpoles_register_fixture_meta() {
    register_post_meta( 'post', '_sp_fixture_id', [
        'type'         => 'integer',
        'single'       => true,
        'show_in_rest' => false,
        'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
    ] );
}
add_action( 'init', 'smallpoles_register_fixture_meta' );

function smallpoles_fixture_meta_box() {
    add_meta_box(
        'sp_fixture_id',
        'Predictions Widget',
        'smallpoles_fixture_meta_box_html',
        'post',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'smallpoles_fixture_meta_box' );

function smallpoles_fixture_meta_box_html( $post ) {
    wp_nonce_field( 'sp_fixture_meta', 'sp_fixture_nonce' );
    $val = get_post_meta( $post->ID, '_sp_fixture_id', true );
    ?>
    <label for="sp_fixture_id" style="display:block;margin-bottom:6px;font-size:12px;color:#666">
        API-Football fixture ID — leave blank to hide the widget on this article.
    </label>
    <input
        type="number"
        id="sp_fixture_id"
        name="sp_fixture_id"
        value="<?php echo esc_attr( $val ); ?>"
        placeholder="e.g. 198772"
        style="width:100%"
    />
    <?php
}

function smallpoles_save_fixture_meta( $post_id ) {
    if ( ! isset( $_POST['sp_fixture_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['sp_fixture_nonce'], 'sp_fixture_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['sp_fixture_id'] ) && $_POST['sp_fixture_id'] !== '' ) {
        update_post_meta( $post_id, '_sp_fixture_id', absint( $_POST['sp_fixture_id'] ) );
    } else {
        delete_post_meta( $post_id, '_sp_fixture_id' );
    }
}
add_action( 'save_post', 'smallpoles_save_fixture_meta' );


/* ═══════════════════════════════════════════════════════════
   COMMUNITY PREDICTIONS CPT
   Stores fan predictions per fixture. Not publicly browsable.
   ═══════════════════════════════════════════════════════════ */

function smallpoles_register_prediction_cpt() {
    register_post_type( 'sp_prediction', [
        'labels'       => [ 'name' => 'Fan Predictions', 'singular_name' => 'Fan Prediction' ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'options-general.php',
        'supports'     => [ 'title' ],
        'rewrite'      => false,
    ] );
}
add_action( 'init', 'smallpoles_register_prediction_cpt' );

// Custom admin columns for Fan Predictions
add_filter( 'manage_sp_prediction_posts_columns', function( $cols ) {
    return [
        'cb'         => $cols['cb'],
        'title'      => 'Predictor',
        'sp_fixture' => 'Fixture ID',
        'sp_pick'    => 'Pick',
        'sp_score'   => 'Score',
        'date'       => 'Submitted',
    ];
} );

add_action( 'manage_sp_prediction_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'sp_fixture':
            echo esc_html( get_post_meta( $post_id, '_sp_pred_fixture_id', true ) ?: '—' );
            break;

        case 'sp_pick':
            $winner  = get_post_meta( $post_id, '_sp_pred_winner', true );
            $labels  = [ 'home' => 'Home Win', 'draw' => 'Draw', 'away' => 'Away Win' ];
            $colours = [ 'home' => '#0a6b24', 'draw' => '#6b4800', 'away' => '#8a1c1c' ];
            $bgs     = [ 'home' => '#edfaef', 'draw' => '#fef9e7', 'away' => '#fce8e8' ];
            if ( $winner && isset( $labels[ $winner ] ) ) {
                printf(
                    '<span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;background:%s;color:%s">%s</span>',
                    esc_attr( $bgs[ $winner ] ),
                    esc_attr( $colours[ $winner ] ),
                    esc_html( $labels[ $winner ] )
                );
            } else {
                echo '—';
            }
            break;

        case 'sp_score':
            $h = get_post_meta( $post_id, '_sp_pred_home_score', true );
            $a = get_post_meta( $post_id, '_sp_pred_away_score', true );
            if ( $h !== '' && $h !== false && $a !== '' && $a !== false ) {
                echo '<code style="font-size:12px;background:#f0f0f1;padding:1px 6px;border-radius:3px">' . esc_html( $h . ' – ' . $a ) . '</code>';
            } else {
                echo '<span style="color:#8c8f94">—</span>';
            }
            break;
    }
}, 10, 2 );

// Sortable Fixture ID column
add_filter( 'manage_edit-sp_prediction_sortable_columns', function( $cols ) {
    $cols['sp_fixture'] = 'sp_fixture';
    return $cols;
} );

// Fixture ID filter dropdown + sorting handler
add_action( 'pre_get_posts', function( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    if ( $query->get( 'post_type' ) !== 'sp_prediction' ) return;

    // Sorting by fixture ID
    if ( $query->get( 'orderby' ) === 'sp_fixture' ) {
        $query->set( 'meta_key', '_sp_pred_fixture_id' );
        $query->set( 'orderby', 'meta_value_num' );
    }

    // Filter by fixture ID from dropdown
    $fid = isset( $_GET['sp_fixture_filter'] ) ? sanitize_text_field( $_GET['sp_fixture_filter'] ) : '';
    if ( $fid ) {
        $query->set( 'meta_query', [ [ 'key' => '_sp_pred_fixture_id', 'value' => $fid ] ] );
    }
} );

add_action( 'restrict_manage_posts', function( $post_type ) {
    if ( $post_type !== 'sp_prediction' ) return;
    global $wpdb;
    $fixtures = $wpdb->get_col(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sp_pred_fixture_id' ORDER BY (meta_value+0) ASC"
    );
    $current = isset( $_GET['sp_fixture_filter'] ) ? sanitize_text_field( $_GET['sp_fixture_filter'] ) : '';
    echo '<select name="sp_fixture_filter">';
    echo '<option value="">All Fixtures</option>';
    foreach ( $fixtures as $fid ) {
        printf(
            '<option value="%s"%s>Fixture %s</option>',
            esc_attr( $fid ),
            selected( $current, $fid, false ),
            esc_html( $fid )
        );
    }
    echo '</select>';
} );


/* ═══════════════════════════════════════════════════════════
   REST PROXY — /wp-json/smallpoles/v1/predictions?fixture=ID
   API key stays server-side. Caches 1 hour via transients.
   ═══════════════════════════════════════════════════════════ */

/* ── Per-IP rate limiting for REST proxy endpoints ───────────────────
   Limits each IP to $max calls per $window_seconds per endpoint group.
   Uses a short-lived transient as a sliding counter.
   Returns a WP_Error when the limit is exceeded, null when clear.
   ─────────────────────────────────────────────────────────────────── */
function smallpoles_rate_check( string $group, int $max = 20, int $window = 60 ) {
    $ip  = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] )
         ? $_SERVER['HTTP_CF_CONNECTING_IP']
         : ( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $key = 'sp_rate_' . $group . '_' . md5( $ip );

    $count = (int) get_transient( $key );
    if ( $count >= $max ) {
        return new WP_Error(
            'rate_limited',
            'Too many requests. Please slow down.',
            [ 'status' => 429 ]
        );
    }

    // Increment; set expiry only on first call so the window is fixed, not sliding
    if ( $count === 0 ) {
        set_transient( $key, 1, $window );
    } else {
        // Transient already exists — just bump the count, preserving the original TTL
        // WordPress has no atomic increment, so we overwrite; race conditions add at most 1 extra call
        set_transient( $key, $count + 1, $window );
    }

    return null;
}

function smallpoles_register_rest_routes() {
    $public = [ 'methods' => 'GET', 'permission_callback' => '__return_true' ];

    register_rest_route( 'smallpoles/v1', '/predictions', $public + [
        'callback' => 'smallpoles_predictions_proxy',
        'args'     => [
            'fixture' => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/next-fixture', $public + [
        'callback' => 'smallpoles_next_fixture_proxy',
    ] );

    register_rest_route( 'smallpoles/v1', '/wc-groups', $public + [
        'callback' => 'smallpoles_wc_groups_proxy',
    ] );

    register_rest_route( 'smallpoles/v1', '/wc-seed-groups', [
        'methods'             => 'POST',
        'permission_callback' => fn() => current_user_can( 'manage_options' ),
        'callback'            => 'smallpoles_wc_seed_groups',
    ] );

    register_rest_route( 'smallpoles/v1', '/standings', $public + [
        'callback' => 'smallpoles_standings_proxy',
    ] );

    register_rest_route( 'smallpoles/v1', '/fixtures', $public + [
        'callback' => 'smallpoles_fixtures_proxy',
        'args'     => [
            'team'  => [ 'sanitize_callback' => 'absint', 'default' => 0 ],
            'round' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/today-fixtures', $public + [
        'callback' => 'smallpoles_today_fixtures_proxy',
    ] );

    register_rest_route( 'smallpoles/v1', '/rounds', $public + [
        'callback' => 'smallpoles_rounds_proxy',
    ] );

    register_rest_route( 'smallpoles/v1', '/fixture-stats', $public + [
        'callback' => 'smallpoles_fixture_stats_proxy',
        'args'     => [
            'fixture' => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/lineups', $public + [
        'callback' => 'smallpoles_lineups_proxy',
        'args'     => [
            'fixture' => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/odds', $public + [
        'callback' => 'smallpoles_odds_proxy',
        'args'     => [
            'fixture'   => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
            'bookmaker' => [
                'sanitize_callback' => 'absint',
                'default'           => 0,
            ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/odds-live', $public + [
        'callback' => 'smallpoles_odds_live_proxy',
        'args'     => [
            'fixture' => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );

    // Community fan predictions — read
    register_rest_route( 'smallpoles/v1', '/community-predictions', $public + [
        'callback' => 'smallpoles_community_predictions_get',
        'args'     => [
            'fixture' => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );

    // Community fan predictions — submit
    register_rest_route( 'smallpoles/v1', '/predict', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'smallpoles_community_predictions_post',
        'args'                => [
            'fixture_id'   => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
            'winner'       => [
                'required'          => true,
                'validate_callback' => fn( $v ) => in_array( $v, [ 'home', 'draw', 'away' ], true ),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'home_score'   => [ 'default' => null, 'sanitize_callback' => 'absint' ],
            'away_score'   => [ 'default' => null, 'sanitize_callback' => 'absint' ],
            'display_name' => [ 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
        ],
    ] );
}
add_action( 'rest_api_init', 'smallpoles_register_rest_routes' );

function smallpoles_next_fixture_proxy() {
    $team_id   = (int) get_option( 'smallpoles_team_id',   1504 );
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season',    2026 );
    $cache_key = "next_fixture_{$team_id}_{$league_id}_{$season}";

    // Serve from cache before touching the rate limiter — cached responses never hit the external API
    $cached = smallpoles_cache_get( $cache_key );
    if ( $cached !== false ) return rest_ensure_response( $cached );

    if ( $err = smallpoles_rate_check( 'fixture', 10, 60 ) ) return $err;

    // Stampede lock
    $lock_key = 'sp_lock_next_fixture';
    if ( get_transient( $lock_key ) ) {
        $row = get_option( 'sp_cache_' . $cache_key );
        if ( is_array( $row ) && isset( $row['data'] ) ) return rest_ensure_response( $row['data'] );
        return new WP_Error( 'fetching', 'Data is being refreshed.', [ 'status' => 503 ] );
    }
    set_transient( $lock_key, 1, 15 );

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) {
        delete_transient( $lock_key );
        return new WP_Error( 'no_api_key', 'API key not configured.', [ 'status' => 503 ] );
    }

    $response = wp_remote_get(
        add_query_arg( [
            'team'   => $team_id,
            'league' => $league_id,
            'season' => $season,
            'next'   => 1,
        ], 'https://v3.football.api-sports.io/fixtures' ),
        [ 'timeout' => 10, 'headers' => [ 'x-apisports-key' => $api_key ] ]
    );

    delete_transient( $lock_key );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 502 ] );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['response'][0] ) ) {
        return new WP_Error( 'no_fixture', 'No upcoming fixtures found.', [ 'status' => 404 ] );
    }

    $fixture = $body['response'][0];

    // TTL: cache until 30 minutes after kickoff so the slot refreshes for the next match
    $kickoff = $fixture['fixture']['timestamp'] ?? 0;
    $ttl     = $kickoff ? max( 300, ( $kickoff + 1800 ) - time() ) : 6 * HOUR_IN_SECONDS;
    smallpoles_cache_set( $cache_key, $fixture, $ttl );

    return rest_ensure_response( $fixture );
}

/* ── Durable cache helpers ───────────────────────────────────────────
   wp_options rows survive transient purges and cache-plugin flushes.
   Each row: { data, fetched, ttl }  — ttl=0 means permanent.
   autoload=false keeps large JSON blobs off every page's boot query.
   ─────────────────────────────────────────────────────────────────── */

function smallpoles_cache_get( string $key ) {
    $row = get_option( 'sp_cache_' . $key );
    if ( ! is_array( $row ) || ! isset( $row['data'] ) ) return false;
    if ( $row['ttl'] > 0 && ( time() - $row['fetched'] ) > $row['ttl'] ) return false;
    return $row['data'];
}

function smallpoles_cache_set( string $key, $data, int $ttl = 0 ): void {
    update_option( 'sp_cache_' . $key, [
        'data'    => $data,
        'fetched' => time(),
        'ttl'     => $ttl,
    ], false );
}

/* ── Shared API fetch helper with stampede protection ────────────────
   Only one PHP process hits the external API per cache key at a time.
   While a fetch is in-flight, other requests serve stale data if any
   exists, or wait for the first response.
   ─────────────────────────────────────────────────────────────────── */
function smallpoles_api_fetch( string $endpoint, array $params, string $cache_key, int $ttl ) {
    // 1. Fresh cache hit — serve immediately
    $cached = smallpoles_cache_get( $cache_key );
    if ( $cached !== false ) return [ 'data' => $cached, 'error' => null ];

    // 2. Stampede lock — only one process fetches at a time
    $lock_key = 'sp_lock_' . md5( $cache_key );
    if ( get_transient( $lock_key ) ) {
        // Another process is already fetching — serve stale if we have it
        $row = get_option( 'sp_cache_' . $cache_key );
        if ( is_array( $row ) && isset( $row['data'] ) ) {
            return [ 'data' => $row['data'], 'error' => null ];
        }
        return [ 'data' => null, 'error' => new WP_Error( 'fetching', 'Data is being refreshed.', [ 'status' => 503 ] ) ];
    }
    set_transient( $lock_key, 1, 15 ); // hold for max 15s

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) {
        delete_transient( $lock_key );
        return [ 'data' => null, 'error' => new WP_Error( 'no_api_key', 'API key not configured.', [ 'status' => 503 ] ) ];
    }

    $response = wp_remote_get(
        add_query_arg( $params, 'https://v3.football.api-sports.io/' . $endpoint ),
        [ 'timeout' => 10, 'headers' => [ 'x-apisports-key' => $api_key ] ]
    );

    delete_transient( $lock_key ); // release regardless of outcome

    if ( is_wp_error( $response ) ) {
        return [ 'data' => null, 'error' => new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 502 ] ) ];
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['response'] ) ) {
        return [ 'data' => null, 'error' => new WP_Error( 'no_data', 'No data returned.', [ 'status' => 404 ] ) ];
    }

    smallpoles_cache_set( $cache_key, $body['response'], $ttl );
    return [ 'data' => $body['response'], 'error' => null ];
}

function smallpoles_standings_proxy() {
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season', 2026 );
    $result    = smallpoles_api_fetch(
        'standings',
        [ 'league' => $league_id, 'season' => $season ],
        "standings_{$league_id}_{$season}",
        6 * HOUR_IN_SECONDS
    );
    return $result['error'] ?? rest_ensure_response( $result['data'][0]['league'] ?? $result['data'] );
}

/* ── WC 2026 Groups — serve seeded data or parse from standings API ── */
function smallpoles_wc_groups_proxy() {
    // Prefer manually seeded data (permanent)
    $seeded = get_option( 'smallpoles_wc_groups_data' );
    if ( $seeded ) return rest_ensure_response( $seeded );

    // Try API standings for league 1 / season 2026
    $result = smallpoles_api_fetch( 'standings', [ 'league' => 1, 'season' => 2026 ], 'wc_groups_2026', 12 * HOUR_IN_SECONDS );
    if ( $result['error'] ) return $result['error'];

    $raw = $result['data'][0]['league']['standings'] ?? [];
    $groups = [];
    foreach ( $raw as $group_arr ) {
        if ( empty( $group_arr ) ) continue;
        $g_label = $group_arr[0]['group'] ?? '';
        // Extract letter: "Group A" → "A". Skip derived tables like "Ranking of Third Placed Teams".
        $letter = strtoupper( preg_replace( '/[^A-Z]/i', '', str_replace( 'Group ', '', $g_label ) ) );
        if ( strlen( $letter ) !== 1 ) continue;
        $teams = array_map( fn( $row ) => [
            'name' => $row['team']['name'],
            'logo' => $row['team']['logo'] ?? '',
        ], $group_arr );
        $groups[ $letter ] = $teams;
    }
    ksort( $groups );
    return rest_ensure_response( $groups );
}

function smallpoles_wc_seed_groups( WP_REST_Request $request ) {
    $body = $request->get_json_params();
    $groups = $body['groups'] ?? null;
    if ( ! is_array( $groups ) ) {
        return new WP_Error( 'bad_data', 'Expected { groups: { A: [...], B: [...] } }', [ 'status' => 400 ] );
    }
    // Sanitize: keep only name + logo per team
    $clean = [];
    foreach ( $groups as $letter => $teams ) {
        $letter = strtoupper( sanitize_text_field( $letter ) );
        $clean[ $letter ] = array_map( fn( $t ) => [
            'name' => sanitize_text_field( $t['name'] ?? '' ),
            'logo' => esc_url_raw( $t['logo'] ?? '' ),
        ], (array) $teams );
    }
    ksort( $clean );
    update_option( 'smallpoles_wc_groups_data', $clean, false );
    return rest_ensure_response( [ 'ok' => true, 'groups' => $clean ] );
}

function smallpoles_fixtures_proxy( WP_REST_Request $request ) {
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season', 2026 );
    $team      = $request->get_param( 'team' );
    $round     = $request->get_param( 'round' );

    $params    = [ 'league' => $league_id, 'season' => $season ];
    $cache_key = "fixtures_{$league_id}_{$season}";

    if ( $team )  { $params['team']  = $team;  $cache_key .= "_t{$team}"; }
    if ( $round ) { $params['round'] = $round; $cache_key .= '_r' . md5( $round ); }

    $result = smallpoles_api_fetch( 'fixtures', $params, $cache_key, 6 * HOUR_IN_SECONDS );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_today_fixtures_proxy() {
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season', 2026 );
    $today     = gmdate( 'Y-m-d' );
    $tomorrow  = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
    $cache_key = "today_fixtures_{$league_id}_{$season}_{$today}";

    $cached = smallpoles_cache_get( $cache_key );
    if ( $cached !== false ) return rest_ensure_response( $cached );

    $lock_key = 'sp_lock_' . md5( $cache_key );
    if ( get_transient( $lock_key ) ) {
        $row = get_option( 'sp_cache_' . $cache_key );
        if ( is_array( $row ) && isset( $row['data'] ) ) return rest_ensure_response( $row['data'] );
        return rest_ensure_response( [] );
    }
    set_transient( $lock_key, 1, 15 );

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) {
        delete_transient( $lock_key );
        return rest_ensure_response( [] );
    }

    $base_url = 'https://v3.football.api-sports.io/fixtures';
    $headers  = [ 'timeout' => 10, 'headers' => [ 'x-apisports-key' => $api_key ] ];

    $resp_today    = wp_remote_get( add_query_arg( [ 'league' => $league_id, 'season' => $season, 'date' => $today ], $base_url ), $headers );
    $resp_tomorrow = wp_remote_get( add_query_arg( [ 'league' => $league_id, 'season' => $season, 'date' => $tomorrow ], $base_url ), $headers );

    delete_transient( $lock_key );

    $fixtures = [];
    if ( ! is_wp_error( $resp_today ) ) {
        $body     = json_decode( wp_remote_retrieve_body( $resp_today ), true );
        $fixtures = array_merge( $fixtures, $body['response'] ?? [] );
    }
    if ( ! is_wp_error( $resp_tomorrow ) ) {
        $body     = json_decode( wp_remote_retrieve_body( $resp_tomorrow ), true );
        $fixtures = array_merge( $fixtures, $body['response'] ?? [] );
    }

    smallpoles_cache_set( $cache_key, $fixtures, 30 * MINUTE_IN_SECONDS );
    return rest_ensure_response( $fixtures );
}

function smallpoles_rounds_proxy() {
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season', 2026 );
    $result    = smallpoles_api_fetch(
        'fixtures/rounds',
        [ 'league' => $league_id, 'season' => $season ],
        "rounds_{$league_id}_{$season}",
        DAY_IN_SECONDS
    );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_fixture_stats_proxy( WP_REST_Request $request ) {
    if ( $err = smallpoles_rate_check( 'stats', 15, 60 ) ) return $err;
    $fixture_id = $request->get_param( 'fixture' );
    $result     = smallpoles_api_fetch(
        'fixtures/statistics',
        [ 'fixture' => $fixture_id ],
        "stats_{$fixture_id}",
        15 * MINUTE_IN_SECONDS
    );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_lineups_proxy( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );
    $result     = smallpoles_api_fetch(
        'fixtures/lineups',
        [ 'fixture' => $fixture_id ],
        "lineups_{$fixture_id}",
        30 * MINUTE_IN_SECONDS
    );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_odds_proxy( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );
    $bookmaker  = $request->get_param( 'bookmaker' );
    $cache_key  = "odds_{$fixture_id}";
    if ( $bookmaker ) $cache_key .= "_b{$bookmaker}";

    // Serve from cache before touching the rate limiter — cached responses never hit the external API
    $cached = smallpoles_cache_get( $cache_key );
    if ( $cached !== false ) return rest_ensure_response( $cached );

    if ( $err = smallpoles_rate_check( 'odds', 15, 60 ) ) return $err;

    $params = [ 'fixture' => $fixture_id ];
    if ( $bookmaker ) $params['bookmaker'] = $bookmaker;

    $result = smallpoles_api_fetch( 'odds', $params, $cache_key, 2 * HOUR_IN_SECONDS );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_odds_live_proxy( WP_REST_Request $request ) {
    if ( $err = smallpoles_rate_check( 'live_odds', 10, 60 ) ) return $err;
    $fixture_id = $request->get_param( 'fixture' );

    // Live odds stay as a short transient — truly ephemeral, no value in durable storage
    $cache_key = 'sp_live_odds_' . $fixture_id;
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return rest_ensure_response( $cached );

    $lock_key = 'sp_lock_live_odds_' . $fixture_id;
    if ( get_transient( $lock_key ) ) {
        return new WP_Error( 'fetching', 'Live odds are being refreshed.', [ 'status' => 503 ] );
    }
    set_transient( $lock_key, 1, 10 );

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) {
        delete_transient( $lock_key );
        return new WP_Error( 'no_api_key', 'API key not configured.', [ 'status' => 503 ] );
    }

    $response = wp_remote_get(
        add_query_arg( [ 'fixture' => $fixture_id ], 'https://v3.football.api-sports.io/odds/live' ),
        [ 'timeout' => 10, 'headers' => [ 'x-apisports-key' => $api_key ] ]
    );

    delete_transient( $lock_key );

    if ( is_wp_error( $response ) ) return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 502 ] );

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['response'] ) ) return new WP_Error( 'no_data', 'No live odds available.', [ 'status' => 404 ] );

    set_transient( $cache_key, $body['response'], 60 );
    return rest_ensure_response( $body['response'] );
}

function smallpoles_predictions_proxy( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );

    // Serve from cache before touching the rate limiter — cached responses never hit the external API
    // Cache stores $body['response'] (array); unwrap to [0] for consistency with the non-cache path
    $cached = smallpoles_cache_get( "predictions_{$fixture_id}" );
    if ( $cached !== false ) {
        $data = is_array( $cached ) && isset( $cached[0] ) ? $cached[0] : $cached;
        return rest_ensure_response( $data );
    }

    if ( $err = smallpoles_rate_check( 'predictions', 15, 60 ) ) return $err;

    $result = smallpoles_api_fetch(
        'predictions',
        [ 'fixture' => $fixture_id ],
        "predictions_{$fixture_id}",
        12 * HOUR_IN_SECONDS
    );
    if ( $result['error'] ) return $result['error'];
    // API returns an array; predictions endpoint always has one item
    $data = is_array( $result['data'] ) && isset( $result['data'][0] ) ? $result['data'][0] : $result['data'];
    return rest_ensure_response( $data );
}


/* ── Community Predictions — GET ── */
function smallpoles_community_predictions_get( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );

    $preds = get_posts( [
        'post_type'      => 'sp_prediction',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [ [ 'key' => '_sp_pred_fixture_id', 'value' => $fixture_id ] ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    $total  = count( $preds );
    $counts = [ 'home' => 0, 'draw' => 0, 'away' => 0 ];
    $recent = [];

    foreach ( $preds as $p ) {
        $winner = get_post_meta( $p->ID, '_sp_pred_winner', true );
        if ( isset( $counts[ $winner ] ) ) $counts[ $winner ]++;

        if ( count( $recent ) < 6 ) {
            $hs = get_post_meta( $p->ID, '_sp_pred_home_score', true );
            $as = get_post_meta( $p->ID, '_sp_pred_away_score', true );
            $recent[] = [
                'name'       => get_post_meta( $p->ID, '_sp_pred_display_name', true ) ?: 'Anonymous',
                'winner'     => $winner,
                'home_score' => $hs !== '' ? (int) $hs : null,
                'away_score' => $as !== '' ? (int) $as : null,
            ];
        }
    }

    $pcts = [
        'home' => $total ? round( $counts['home'] / $total * 100 ) : 0,
        'draw' => $total ? round( $counts['draw'] / $total * 100 ) : 0,
        'away' => $total ? round( $counts['away'] / $total * 100 ) : 0,
    ];

    return rest_ensure_response( compact( 'total', 'counts', 'pcts', 'recent' ) );
}


/* ── Community Predictions — POST ── */
function smallpoles_community_predictions_post( WP_REST_Request $request ) {
    $fixture_id   = $request->get_param( 'fixture_id' );
    $winner       = $request->get_param( 'winner' );
    $home_score   = $request->get_param( 'home_score' );
    $away_score   = $request->get_param( 'away_score' );
    $display_name = trim( $request->get_param( 'display_name' ) ) ?: 'Anonymous';

    // IP-based rate limit: one prediction per fixture per IP per 12h
    $ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
    $rate_key = 'sp_pred_' . md5( $ip ) . '_' . $fixture_id;

    if ( get_transient( $rate_key ) ) {
        return new WP_Error( 'already_predicted', 'You have already predicted for this match.', [ 'status' => 429 ] );
    }

    $post_id = wp_insert_post( [
        'post_type'   => 'sp_prediction',
        'post_title'  => sprintf( '%s — fixture %d', $display_name, $fixture_id ),
        'post_status' => 'publish',
    ] );

    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'save_failed', 'Could not save prediction.', [ 'status' => 500 ] );
    }

    update_post_meta( $post_id, '_sp_pred_fixture_id',   $fixture_id );
    update_post_meta( $post_id, '_sp_pred_winner',       $winner );
    update_post_meta( $post_id, '_sp_pred_home_score',   $home_score );
    update_post_meta( $post_id, '_sp_pred_away_score',   $away_score );
    update_post_meta( $post_id, '_sp_pred_display_name', $display_name );

    set_transient( $rate_key, 1, 12 * HOUR_IN_SECONDS );

    return rest_ensure_response( [ 'success' => true ] );
}


/* ═══════════════════════════════════════════════════════════════════
   GUEST COMMENTS — allow comments without requiring login/registration
   ═══════════════════════════════════════════════════════════════════ */
add_filter( 'option_comment_registration', '__return_false' );
add_filter( 'option_default_comment_status', function() { return 'open'; } );
add_filter( 'option_require_name_email', '__return_false' );

/* Render a single comment in the site's dark style */
function smallpoles_comment( $comment, $args, $depth ) {
    $tag = 'li';
    ?>
    <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( 'sp-comment', $comment ); ?>>
      <div class="sp-comment-inner">
        <div class="sp-comment-avatar">
          <?php echo get_avatar( $comment, 40, '', '', [ 'class' => 'sp-avatar' ] ); ?>
        </div>
        <div class="sp-comment-body">
          <div class="sp-comment-meta">
            <span class="sp-comment-author"><?php echo esc_html( get_comment_author( $comment ) ); ?></span>
            <span class="sp-comment-date"><?php echo human_time_diff( get_comment_date( 'U', $comment ), current_time( 'timestamp' ) ) . ' ago'; ?></span>
            <?php comment_reply_link( array_merge( $args, [
              'reply_text' => 'Reply',
              'depth'      => $depth,
              'max_depth'  => $args['max_depth'],
              'before'     => '<span class="sp-comment-reply">',
              'after'      => '</span>',
            ] ) ); ?>
          </div>
          <?php if ( '0' == $comment->comment_approved ) : ?>
            <p class="sp-comment-moderation">Your comment is awaiting moderation.</p>
          <?php endif; ?>
          <div class="sp-comment-text"><?php comment_text(); ?></div>
        </div>
      </div>
    </<?php echo $tag; ?>>
    <?php
}


/* ═══════════════════════════════════════════════════════════════════
   GAMES — routing, templates, titles
   ═══════════════════════════════════════════════════════════════════ */

/* ── Helper: map current page to game key ── */
function sp_current_game() {
    $map = [
        'games'        => 'hub',
        'higher-lower' => 'higher-lower',
        'polele'       => 'polele',
        'bracket'      => 'bracket',
        'squad'        => 'squad',
        'fixtures'     => 'fixtures',
    ];
    foreach ( $map as $slug => $game ) {
        if ( is_page( $slug ) ) return $game;
    }
    return null;
}

/* ── Force correct template for each game page (bypasses WP Admin template setting) ── */
function smallpoles_game_template( $template ) {
    $map = [
        'hub'          => 'page-games.php',
        'higher-lower' => 'page-higher-lower.php',
        'polele'       => 'page-polele.php',
        'bracket'      => 'page-bracket.php',
        'squad'        => 'page-squad-builder.php',
        'fixtures'     => 'page-fixtures.php',
    ];
    $game = sp_current_game();
    if ( $game && isset( $map[ $game ] ) ) {
        $path = get_template_directory() . '/' . $map[ $game ];
        if ( file_exists( $path ) ) return $path;
    }
    return $template;
}
add_filter( 'template_include', 'smallpoles_game_template' );

/* ── Helper: check if a game is enabled ── */
function sp_game_is_visible( $game ) {
    $saved = get_option( 'sp_games_visibility', [] );
    return ! isset( $saved[ $game ] ) || ! empty( $saved[ $game ] );
}

/* ── Redirect to hub if a disabled game page is visited ── */
function smallpoles_games_visibility_redirect() {
    $game = sp_current_game();
    if ( $game && $game !== 'hub' && ! sp_game_is_visible( $game ) ) {
        wp_safe_redirect( home_url( '/games/' ) );
        exit;
    }
}
add_action( 'template_redirect', 'smallpoles_games_visibility_redirect' );

/* ── One-time: create WordPress pages for each game ── */
function smallpoles_create_game_pages() {
    if ( get_option( 'sp_game_pages_v2' ) ) return;

    $parent    = get_page_by_path( 'games' );
    $parent_id = $parent ? $parent->ID : wp_insert_post( [
        'post_title'  => 'Games',
        'post_name'   => 'games',
        'post_status' => 'publish',
        'post_type'   => 'page',
    ] );
    if ( is_wp_error( $parent_id ) ) return;
    update_post_meta( $parent_id, '_wp_page_template', 'page-games.php' );

    foreach ( [
        [ 'Higher or Lower',    'higher-lower', 'page-higher-lower.php'  ],
        [ 'Polele',             'polele',        'page-polele.php'        ],
        [ 'Bracket',            'bracket',       'page-bracket.php'       ],
        [ 'Squad Builder',      'squad',         'page-squad-builder.php' ],
        [ 'Match Schedule',     'fixtures',      'page-fixtures.php'      ],
    ] as [ $title, $slug, $tpl ] ) {
        $existing = get_page_by_path( 'games/' . $slug );
        if ( ! $existing ) $existing = get_page_by_path( $slug );
        $id = $existing ? $existing->ID : wp_insert_post( [
            'post_title'  => $title,
            'post_name'   => $slug,
            'post_status' => 'publish',
            'post_type'   => 'page',
            'post_parent' => $parent_id,
        ] );
        if ( ! is_wp_error( $id ) ) {
            update_post_meta( $id, '_wp_page_template', $tpl );
        }
    }

    update_option( 'sp_game_pages_v2', true );
    flush_rewrite_rules( false );
}
add_action( 'init', 'smallpoles_create_game_pages', 20 );

/* One-time permalink flush whenever routing version bumps */
function smallpoles_maybe_flush_rewrites() {
    if ( get_option( 'sp_rewrite_version' ) !== '1.3' ) {
        flush_rewrite_rules( false );
        update_option( 'sp_rewrite_version', '1.3' );
    }
}
add_action( 'init', 'smallpoles_maybe_flush_rewrites', 999 );


/* ═══════════════════════════════════════════════════════════════════
   HIGHER OR LOWER — player management
   ═══════════════════════════════════════════════════════════════════ */

/* ── Games admin menu ── */
function smallpoles_games_admin_menu() {
    /* Top-level "Games" menu */
    add_menu_page(
        'Games',
        'Games',
        'manage_options',
        'sp-games',
        'smallpoles_game_visibility_page',
        'dashicons-games',
        5
    );
    /* First submenu: Game Visibility (same slug = replaces auto-generated duplicate) */
    add_submenu_page(
        'sp-games',
        'Game Visibility',
        'Game Visibility',
        'manage_options',
        'sp-games',
        'smallpoles_game_visibility_page'
    );
    /* Second submenu: HL Players list (links to the CPT) */
    add_submenu_page(
        'sp-games',
        'Higher or Lower Players',
        'HL Players',
        'manage_options',
        'edit.php?post_type=hl_player'
    );
}
add_action( 'admin_menu', 'smallpoles_games_admin_menu' );

/* ── Game visibility settings page ── */
function smallpoles_game_visibility_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['sp_save_visibility'] ) && check_admin_referer( 'sp_game_visibility' ) ) {
        $games = [ 'higher-lower', 'polele', 'bracket', 'squad' ];
        $new   = [];
        foreach ( $games as $g ) {
            $new[ $g ] = isset( $_POST['sp_game'][ $g ] ) ? '1' : '0';
        }
        update_option( 'sp_games_visibility', $new );
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }

    $saved = get_option( 'sp_games_visibility', [] );
    $games = [
        'higher-lower' => 'Higher or Lower',
        'polele'       => 'Polele',
        'bracket'      => 'Bracket Challenge',
        'squad'        => 'Squad Builder',
    ];
    ?>
    <div class="wrap">
        <h1>Game Visibility</h1>
        <p style="color:#666;margin-bottom:20px;">Toggle which games appear on the site. Disabled games redirect visitors back to the Games hub.</p>
        <form method="post">
            <?php wp_nonce_field( 'sp_game_visibility' ); ?>
            <table class="widefat" style="max-width:480px">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th style="text-align:center;width:80px">Visible</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $games as $slug => $label ) :
                        $checked = ! isset( $saved[ $slug ] ) || ! empty( $saved[ $slug ] );
                    ?>
                    <tr>
                        <td style="padding:12px 10px;font-weight:500"><?php echo esc_html( $label ); ?></td>
                        <td style="text-align:center">
                            <input type="checkbox" name="sp_game[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( $checked ); ?> />
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="margin-top:16px">
                <input type="submit" name="sp_save_visibility" class="button button-primary" value="Save Changes" />
            </p>
        </form>
    </div>
    <?php
}

/* ── Custom post type (under Games menu) ── */
function smallpoles_register_hl_player() {
    register_post_type( 'hl_player', [
        'labels' => [
            'name'          => 'HL Players',
            'singular_name' => 'HL Player',
            'add_new'       => 'Add Player',
            'add_new_item'  => 'Add New Player',
            'edit_item'     => 'Edit Player',
            'all_items'     => 'All Players',
            'search_items'  => 'Search Players',
            'not_found'     => 'No players found.',
            'menu_name'     => 'HL Players',
        ],
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => 'sp-games',
        'supports'      => [ 'title' ],
        'rewrite'       => false,
    ] );
}
add_action( 'init', 'smallpoles_register_hl_player' );

/* ── Meta box ── */
function smallpoles_hl_add_meta_box() {
    add_meta_box( 'hl_player_stats', 'Player Details', 'smallpoles_hl_meta_box_render', 'hl_player', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'smallpoles_hl_add_meta_box' );

function smallpoles_hl_meta_box_render( $post ) {
    wp_nonce_field( 'hl_player_save', 'hl_player_nonce' );
    $nat  = get_post_meta( $post->ID, '_hl_nationality', true );
    $flag = get_post_meta( $post->ID, '_hl_flag',        true );
    $pos  = get_post_meta( $post->ID, '_hl_position',    true );
    $age  = get_post_meta( $post->ID, '_hl_age',         true );
    $goals= get_post_meta( $post->ID, '_hl_goals',       true );
    $caps = get_post_meta( $post->ID, '_hl_caps',        true );
    ?>
    <style>
        .hl-meta-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px 20px; margin-top:8px; }
        .hl-meta-field label { display:block; font-weight:600; margin-bottom:4px; font-size:12px; text-transform:uppercase; color:#666; }
        .hl-meta-field input, .hl-meta-field select { width:100%; }
    </style>
    <div class="hl-meta-grid">
        <div class="hl-meta-field">
            <label>Nationality</label>
            <input type="text" name="hl_nationality" value="<?php echo esc_attr( $nat ); ?>" placeholder="e.g. Ghana" />
        </div>
        <div class="hl-meta-field">
            <label>Flag Emoji</label>
            <input type="text" name="hl_flag" value="<?php echo esc_attr( $flag ); ?>" placeholder="e.g. 🇬🇭" />
        </div>
        <div class="hl-meta-field">
            <label>Position</label>
            <select name="hl_position">
                <?php foreach ( [ 'GK', 'DEF', 'MID', 'FWD' ] as $p ) : ?>
                    <option value="<?php echo $p; ?>" <?php selected( $pos, $p ); ?>><?php echo $p; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="hl-meta-field">
            <label>Age (2026)</label>
            <input type="number" name="hl_age" value="<?php echo esc_attr( $age ); ?>" min="15" max="55" />
        </div>
        <div class="hl-meta-field">
            <label>International Goals</label>
            <input type="number" name="hl_goals" value="<?php echo esc_attr( $goals ); ?>" min="0" />
        </div>
        <div class="hl-meta-field">
            <label>International Caps</label>
            <input type="number" name="hl_caps" value="<?php echo esc_attr( $caps ); ?>" min="0" />
        </div>
    </div>
    <p style="margin-top:12px;color:#888;font-size:12px;">The player's <strong>full name</strong> is set via the Title field above.</p>
    <?php
}

function smallpoles_hl_save_meta( $post_id ) {
    if ( ! isset( $_POST['hl_player_nonce'] ) || ! wp_verify_nonce( $_POST['hl_player_nonce'], 'hl_player_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = [
        '_hl_nationality' => 'hl_nationality',
        '_hl_flag'        => 'hl_flag',
        '_hl_position'    => 'hl_position',
        '_hl_age'         => 'hl_age',
        '_hl_goals'       => 'hl_goals',
        '_hl_caps'        => 'hl_caps',
    ];
    foreach ( $fields as $meta_key => $post_key ) {
        if ( isset( $_POST[ $post_key ] ) ) {
            $value = in_array( $meta_key, [ '_hl_age', '_hl_goals', '_hl_caps' ] )
                ? absint( $_POST[ $post_key ] )
                : sanitize_text_field( $_POST[ $post_key ] );
            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post_hl_player', 'smallpoles_hl_save_meta' );


/* ═══════════════════════════════════════════════════════════════════
   SQUAD BUILDER — admin seeding from API Football
   ═══════════════════════════════════════════════════════════════════ */

function sb_teams_default() {
    return [
        /* ── CONMEBOL (6) ── */
        [ 'name' => 'Argentina',     'api_id' => 26,   'flag' => '🇦🇷' ],
        [ 'name' => 'Brazil',        'api_id' => 6,    'flag' => '🇧🇷' ],
        [ 'name' => 'Colombia',      'api_id' => 1530, 'flag' => '🇨🇴' ],
        [ 'name' => 'Ecuador',       'api_id' => 1519, 'flag' => '🇪🇨' ],
        [ 'name' => 'Uruguay',       'api_id' => 631,  'flag' => '🇺🇾' ],
        [ 'name' => 'Venezuela',     'api_id' => 1527, 'flag' => '🇻🇪' ],
        /* ── UEFA (16) ── */
        [ 'name' => 'Austria',       'api_id' => 775,  'flag' => '🇦🇹' ],
        [ 'name' => 'Belgium',       'api_id' => 1,    'flag' => '🇧🇪' ],
        [ 'name' => 'Croatia',       'api_id' => 799,  'flag' => '🇭🇷' ],
        [ 'name' => 'Czech Republic','api_id' => 773,  'flag' => '🇨🇿' ],
        [ 'name' => 'Denmark',       'api_id' => 21,   'flag' => '🇩🇰' ],
        [ 'name' => 'England',       'api_id' => 10,   'flag' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿' ],
        [ 'name' => 'France',        'api_id' => 2,    'flag' => '🇫🇷' ],
        [ 'name' => 'Germany',       'api_id' => 25,   'flag' => '🇩🇪' ],
        [ 'name' => 'Netherlands',   'api_id' => 1118, 'flag' => '🇳🇱' ],
        [ 'name' => 'Portugal',      'api_id' => 27,   'flag' => '🇵🇹' ],
        [ 'name' => 'Scotland',      'api_id' => 1108, 'flag' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿' ],
        [ 'name' => 'Serbia',        'api_id' => 2481, 'flag' => '🇷🇸' ],
        [ 'name' => 'Slovenia',      'api_id' => 1110, 'flag' => '🇸🇮' ],
        [ 'name' => 'Spain',         'api_id' => 9,    'flag' => '🇪🇸' ],
        [ 'name' => 'Switzerland',   'api_id' => 15,   'flag' => '🇨🇭' ],
        [ 'name' => 'Turkey',        'api_id' => 741,  'flag' => '🇹🇷' ],
        /* ── CONCACAF (6) ── */
        [ 'name' => 'Canada',        'api_id' => 2455, 'flag' => '🇨🇦' ],
        [ 'name' => 'Costa Rica',    'api_id' => 1517, 'flag' => '🇨🇷' ],
        [ 'name' => 'Honduras',      'api_id' => 1520, 'flag' => '🇭🇳' ],
        [ 'name' => 'Mexico',        'api_id' => 16,   'flag' => '🇲🇽' ],
        [ 'name' => 'Panama',        'api_id' => 1521, 'flag' => '🇵🇦' ],
        [ 'name' => 'USA',           'api_id' => 2384, 'flag' => '🇺🇸' ],
        /* ── CAF (9) ── */
        [ 'name' => 'Cameroon',      'api_id' => 1510, 'flag' => '🇨🇲' ],
        [ 'name' => "Côte d'Ivoire", 'api_id' => 1511, 'flag' => '🇨🇮' ],
        [ 'name' => 'Egypt',         'api_id' => 1523, 'flag' => '🇪🇬' ],
        [ 'name' => 'Ghana',         'api_id' => 1504, 'flag' => '🇬🇭' ],
        [ 'name' => 'Morocco',       'api_id' => 1526, 'flag' => '🇲🇦' ],
        [ 'name' => 'Nigeria',       'api_id' => 1532, 'flag' => '🇳🇬' ],
        [ 'name' => 'Senegal',       'api_id' => 1522, 'flag' => '🇸🇳' ],
        [ 'name' => 'South Africa',  'api_id' => 1524, 'flag' => '🇿🇦' ],
        [ 'name' => 'Tunisia',       'api_id' => 1525, 'flag' => '🇹🇳' ],
        /* ── AFC (8) ── */
        [ 'name' => 'Australia',     'api_id' => 1782, 'flag' => '🇦🇺' ],
        [ 'name' => 'Iran',          'api_id' => 2322, 'flag' => '🇮🇷' ],
        [ 'name' => 'Iraq',          'api_id' => 2321, 'flag' => '🇮🇶' ],
        [ 'name' => 'Japan',         'api_id' => 2324, 'flag' => '🇯🇵' ],
        [ 'name' => 'Jordan',        'api_id' => 2328, 'flag' => '🇯🇴' ],
        [ 'name' => 'Saudi Arabia',  'api_id' => 2323, 'flag' => '🇸🇦' ],
        [ 'name' => 'South Korea',   'api_id' => 2334, 'flag' => '🇰🇷' ],
        [ 'name' => 'Uzbekistan',    'api_id' => 2325, 'flag' => '🇺🇿' ],
        /* ── OFC (1) ── */
        [ 'name' => 'New Zealand',   'api_id' => 1784, 'flag' => '🇳🇿' ],
        /* ── Inter-confederation play-offs (2) ── */
        [ 'name' => 'Chile',         'api_id' => 1516, 'flag' => '🇨🇱' ],
        [ 'name' => 'Indonesia',     'api_id' => 2320, 'flag' => '🇮🇩' ],
    ];
}

/* ── Submenu under Games ── */
add_action( 'admin_menu', function () {
    add_submenu_page(
        'sp-games',
        'Squad Builder',
        'Squad Builder',
        'manage_options',
        'sp-squad-builder',
        'sb_admin_page'
    );
    add_submenu_page(
        'sp-games',
        'Squad Users',
        'Squad Users',
        'manage_options',
        'sp-squad-users',
        'sp_squads_users_admin_page'
    );
} );

/* ── Admin page ── */
function sb_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['sb_add_team_submit'] ) && check_admin_referer( 'sb_add_team' ) ) {
        $teams   = get_option( 'sb_teams', sb_teams_default() );
        $teams[] = [
            'name'   => sanitize_text_field( $_POST['sb_name'] ),
            'api_id' => absint( $_POST['sb_api_id'] ),
            'flag'   => sanitize_text_field( $_POST['sb_flag'] ),
        ];
        update_option( 'sb_teams', $teams, false );
        echo '<div class="notice notice-success is-dismissible"><p>Team added.</p></div>';
    }

    $teams  = get_option( 'sb_teams', sb_teams_default() );
    $squads = get_option( 'sb_squads', [] );
    $nonce  = wp_create_nonce( 'sb_ajax_nonce' );
    ?>
    <div class="wrap">
    <h1>Squad Builder — Teams</h1>
    <p style="color:#666;margin-bottom:16px">
        Seed World Cup squad data from API Football. Budget values default to
        <strong>GK=6 DEF=7 MID=8 FWD=9</strong> — adjust them after seeding.
        The API key from <a href="<?php echo esc_url( admin_url( 'options-general.php?page=smallpoles' ) ); ?>">Settings › Small Poles</a> is used.
        API Team IDs are pre-populated with best estimates — if seeding fails, click <strong>Edit ID</strong> to correct it
        (use the <a href="<?php echo esc_url( admin_url( 'options-general.php?page=smallpoles' ) ); ?>">API Tester</a> to look up the right ID).
    </p>

    <div style="margin-bottom:20px;display:flex;gap:12px;align-items:center">
        <button id="sb-seed-all" class="button button-primary">⟳ Seed All Teams</button>
        <span id="sb-global-status" style="font-weight:600;color:#0073aa"></span>
    </div>

    <table class="widefat fixed" style="margin-bottom:32px">
        <thead>
            <tr>
                <th style="width:40px"></th>
                <th>Nation</th>
                <th style="width:100px">API Team ID</th>
                <th style="width:80px">Players</th>
                <th style="width:140px">Last Seeded</th>
                <th style="width:230px"></th>
            </tr>
        </thead>
        <tbody id="sb-tbody">
        <?php foreach ( $teams as $i => $team ) :
            $name   = $team['name'];
            $api_id = (int) $team['api_id'];
            $flag   = $team['flag'] ?? '';
            $seeded = $team['seeded'] ?? 0;
            $count  = count( $squads[ $name ] ?? [] );
        ?>
        <tr id="sb-tr-<?php echo $i; ?>" data-idx="<?php echo $i; ?>" data-name="<?php echo esc_attr( $name ); ?>" data-api-id="<?php echo $api_id; ?>">
            <td style="font-size:20px;line-height:1.2"><?php echo esc_html( $flag ); ?></td>
            <td style="font-weight:600"><?php echo esc_html( $name ); ?></td>
            <td class="sb-api-cell"><code><?php echo $api_id; ?></code></td>
            <td class="sb-count"><?php echo $count ?: '—'; ?></td>
            <td class="sb-seeded"><?php echo $seeded ? human_time_diff( $seeded ) . ' ago' : '—'; ?></td>
            <td>
                <button class="button sb-seed-one" data-idx="<?php echo $i; ?>">Seed</button>
                <button class="button sb-edit-id" data-name="<?php echo esc_attr( $name ); ?>" data-idx="<?php echo $i; ?>">Edit ID</button>
                <button class="button sb-toggle-players" data-idx="<?php echo $i; ?>"<?php echo ! $count ? ' disabled' : ''; ?>>Values</button>
                <button class="button sb-delete-team" data-name="<?php echo esc_attr( $name ); ?>" data-idx="<?php echo $i; ?>" style="color:#b32d2e">Remove</button>
            </td>
        </tr>
        <tr class="sb-player-row" id="sb-pr-<?php echo $i; ?>" style="display:none">
            <td colspan="6" style="background:#f6f7f7;padding:16px">
                <?php sb_render_player_grid( $squads[ $name ] ?? [], $name ); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 style="margin-bottom:12px">Add Team</h2>
    <p style="color:#666;margin-bottom:12px">Find team IDs via the API Tester in Settings › Small Poles (use the <em>Fixtures</em> or <em>Standings</em> endpoints).</p>
    <form method="post" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;max-width:700px">
        <?php wp_nonce_field( 'sb_add_team' ); ?>
        <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;text-transform:uppercase;color:#555">
            Nation Name
            <input type="text" name="sb_name" class="regular-text" placeholder="e.g. Portugal" required />
        </label>
        <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;text-transform:uppercase;color:#555">
            API Team ID
            <input type="number" name="sb_api_id" class="small-text" placeholder="e.g. 27" required />
        </label>
        <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;text-transform:uppercase;color:#555">
            Flag Emoji
            <input type="text" name="sb_flag" class="small-text" placeholder="🇵🇹" style="width:60px" />
        </label>
        <input type="submit" name="sb_add_team_submit" class="button button-primary" value="Add Team" />
    </form>

    <style>
    .sb-player-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:6px}
    .sb-player-item{display:flex;align-items:center;gap:6px;background:#fff;padding:6px 10px;border-radius:4px;border:1px solid #ddd}
    .sb-pname{flex:1;font-size:13px;border:1px solid #ddd;border-radius:3px;padding:2px 6px;min-width:0}
    .sb-pos{font-size:10px;font-weight:700;padding:2px 4px;border-radius:3px;color:#fff;flex-shrink:0;min-width:36px;text-align:center;border:none;cursor:pointer}
    .sb-pos-gk{background:#c47d00}.sb-pos-def{background:#1e73be}.sb-pos-mid{background:#2a7a2a}.sb-pos-fwd{background:#c0392b}
    .sb-pval{width:44px!important;text-align:center}
    .sb-del-player{flex-shrink:0;background:none;border:none;color:#b32d2e;font-size:15px;cursor:pointer;padding:0 2px;line-height:1;font-weight:700}
    .sb-del-player:hover{color:#a00}
    .sb-add-form{display:none;margin-top:10px;background:#f0f4f8;padding:10px;border:1px solid #ccd;border-radius:4px;gap:8px;align-items:center;flex-wrap:wrap}
    </style>

    <script>
    (function($){
        var nonce   = '<?php echo esc_js( $nonce ); ?>';
        var ajaxurl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

        /* seed one */
        $(document).on('click','.sb-seed-one',function(){
            seedTeam($(this), $(this).closest('tr'));
        });

        /* seed all */
        $('#sb-seed-all').on('click',function(){
            var btn  = $(this).prop('disabled',true);
            var rows = $('#sb-tbody tr[data-api-id]').toArray();
            var idx  = 0;
            function next(){
                if(idx >= rows.length){
                    $('#sb-global-status').text('All done!');
                    btn.prop('disabled',false);
                    return;
                }
                var tr = $(rows[idx]);
                $('#sb-global-status').text('Seeding '+tr.data('name')+' ('+(idx+1)+'/'+rows.length+')…');
                seedTeam(null, tr, function(){ idx++; setTimeout(next, 700); });
            }
            next();
        });

        /* toggle player row */
        $(document).on('click','.sb-toggle-players',function(){
            $('#sb-pr-'+$(this).data('idx')).toggle();
        });

        /* edit API ID inline */
        $(document).on('click','.sb-edit-id',function(){
            var btn  = $(this);
            var tr   = btn.closest('tr');
            var cell = tr.find('.sb-api-cell');
            var cur  = parseInt(cell.find('code').text(),10) || tr.data('api-id');
            cell.html('<input type="number" class="sb-id-input small-text" value="'+cur+'" style="width:70px" />'
                +' <button class="button button-primary sb-save-id">Save</button>'
                +' <button class="button sb-cancel-id" data-orig="'+cur+'">✕</button>');
            btn.hide();
        });
        $(document).on('click','.sb-cancel-id',function(){
            var tr   = $(this).closest('tr');
            var orig = $(this).data('orig');
            tr.find('.sb-api-cell').html('<code>'+orig+'</code>');
            tr.find('.sb-edit-id').show();
        });
        $(document).on('click','.sb-save-id',function(){
            var tr    = $(this).closest('tr');
            var name  = tr.data('name');
            var newId = parseInt(tr.find('.sb-id-input').val(),10);
            if(!newId){ alert('Enter a valid team ID'); return; }
            $(this).prop('disabled',true).text('Saving…');
            $.post(ajaxurl,{action:'sb_update_team_id',nonce:nonce,name:name,api_id:newId},function(res){
                if(res.success){
                    tr.data('api-id',newId);
                    tr.find('.sb-api-cell').html('<code>'+newId+'</code>');
                    tr.find('.sb-edit-id').show();
                } else {
                    alert('Save failed: '+(res.data||'error'));
                }
            });
        });

        /* save values */
        $(document).on('click','.sb-save-vals',function(){
            var btn = $(this).prop('disabled',true).text('Saving…');
            var name = btn.data('name');
            var players = [];
            btn.closest('td').find('.sb-player-item').each(function(){
                players.push({
                    n: $(this).find('.sb-pname').val().trim(),
                    p: $(this).find('.sb-pos').val().trim(),
                    v: parseInt($(this).find('.sb-pval').val(),10)||7
                });
            });
            $.post(ajaxurl,{action:'sb_save_players',nonce:nonce,name:name,players:JSON.stringify(players)},function(res){
                btn.prop('disabled',false).text('Save Changes');
                if(res.success) btn.siblings('.sb-saved-msg').show().delay(2000).fadeOut();
            });
        });

        /* delete individual player */
        $(document).on('click','.sb-del-player',function(){
            $(this).closest('.sb-player-item').remove();
        });

        /* update position select color on change */
        $(document).on('change','.sb-pos',function(){
            var posClass = {GK:'gk',DEF:'def',MID:'mid',FWD:'fwd'};
            $(this).removeClass('sb-pos-gk sb-pos-def sb-pos-mid sb-pos-fwd')
                   .addClass('sb-pos-'+(posClass[$(this).val()]||'mid'));
        });

        /* show add player form */
        $(document).on('click','.sb-add-player',function(){
            $(this).closest('td').find('.sb-add-form').css('display','flex');
        });

        /* cancel add player */
        $(document).on('click','.sb-cancel-add',function(){
            var form = $(this).closest('.sb-add-form');
            form.find('.sb-new-name').val('');
            form.find('.sb-new-pos').val('GK').trigger('change');
            form.find('.sb-new-val').val('7');
            form.hide();
        });

        /* confirm add player */
        $(document).on('click','.sb-confirm-add',function(){
            var form    = $(this).closest('.sb-add-form');
            var pName   = form.find('.sb-new-name').val().trim();
            var pos     = form.find('.sb-new-pos').val();
            var val     = parseInt(form.find('.sb-new-val').val(),10)||7;
            if(!pName){ alert('Enter a player name'); return; }
            form.closest('td').find('.sb-player-grid').append(playerItem({n:pName,p:pos,v:val}));
            form.find('.sb-new-name').val('');
            form.find('.sb-new-pos').val('GK').trigger('change');
            form.find('.sb-new-val').val('7');
            form.hide();
        });

        /* delete team */
        $(document).on('click','.sb-delete-team',function(){
            var btn  = $(this);
            var name = btn.data('name');
            var idx  = btn.data('idx');
            if(!confirm('Remove '+name+' from Squad Builder?')) return;
            $.post(ajaxurl,{action:'sb_delete_team',nonce:nonce,name:name},function(res){
                if(res.success) $('#sb-tr-'+idx+',#sb-pr-'+idx).fadeOut(300,function(){$(this).remove();});
            });
        });

        /* ── core seed ── */
        function seedTeam(btn, tr, cb){
            cb = cb || function(){};
            var name  = tr.data('name');
            var apiId = tr.data('api-id');
            var idx   = tr.data('idx');
            if(btn) btn.prop('disabled',true).text('Seeding…');
            $.post(ajaxurl,{action:'sb_seed_team',nonce:nonce,name:name,api_id:apiId},function(res){
                if(btn) btn.prop('disabled',false).text('Seed');
                if(res.success){
                    tr.find('.sb-count').text(res.data.count);
                    tr.find('.sb-seeded').text('just now');
                    tr.find('.sb-toggle-players').prop('disabled',false);
                    rebuildPlayerRow(idx, name, res.data.players);
                } else {
                    alert('Error seeding '+name+': '+(res.data||'unknown error'));
                }
                cb(res.success);
            }).fail(function(){ if(btn) btn.prop('disabled',false).text('Seed'); cb(false); });
        }

        function posSelect(cur){
            var posClass = {GK:'gk',DEF:'def',MID:'mid',FWD:'fwd'};
            var pc = posClass[cur]||'mid';
            return '<select class="sb-pos sb-pos-'+pc+'">'
                +['GK','DEF','MID','FWD'].map(function(x){
                    return '<option value="'+x+'"'+(x===cur?' selected':'')+'>'+x+'</option>';
                }).join('')+'</select>';
        }

        function playerItem(p){
            return '<div class="sb-player-item">'
                +posSelect(p.p)
                +'<input type="text" class="sb-pname" value="'+esc(p.n)+'" />'
                +'<input type="number" class="sb-pval small-text" value="'+p.v+'" min="1" max="20" />'
                +'<span style="font-size:11px;color:#999">pts</span>'
                +'<button type="button" class="sb-del-player" title="Remove player">✕</button>'
                +'</div>';
        }

        function rebuildPlayerRow(idx, name, players){
            var items = players.map(playerItem).join('');
            var html = '<div class="sb-player-grid">'+items+'</div>'
                +'<div style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">'
                +'<button class="button button-primary sb-save-vals" data-name="'+esc(name)+'">Save Changes</button>'
                +' <span class="sb-saved-msg" style="color:#46b450;font-weight:600;display:none">✓ Saved</span>'
                +'<button type="button" class="button sb-add-player" style="margin-left:auto">+ Add Player</button>'
                +'</div>'
                +'<div class="sb-add-form">'
                +'<select class="sb-new-pos sb-pos sb-pos-gk"><option value="GK">GK</option><option value="DEF">DEF</option><option value="MID">MID</option><option value="FWD">FWD</option></select>'
                +'<input type="text" class="sb-new-name regular-text" placeholder="Player name" style="flex:1;min-width:160px" />'
                +'<input type="number" class="sb-new-val small-text" value="7" min="1" max="20" style="width:50px" />'
                +'<span style="font-size:11px;color:#999">pts</span>'
                +'<button type="button" class="button button-primary sb-confirm-add">Add</button>'
                +'<button type="button" class="button sb-cancel-add">Cancel</button>'
                +'</div>';
            $('#sb-pr-'+idx).find('td').html(html).end().show();
        }

        function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    })(jQuery);
    </script>
    </div>
    <?php
}

function sb_render_player_grid( array $players, string $name ) {
    if ( empty( $players ) ) return;
    $pos_class = [ 'GK' => 'gk', 'DEF' => 'def', 'MID' => 'mid', 'FWD' => 'fwd' ];
    echo '<div class="sb-player-grid">';
    foreach ( $players as $p ) {
        $pos = $p['p'];
        $pc  = $pos_class[ $pos ] ?? 'mid';
        $sel = fn( $v ) => $v === $pos ? ' selected' : '';
        printf(
            '<div class="sb-player-item">'
            . '<select class="sb-pos sb-pos-%s">'
            . '<option value="GK"%s>GK</option><option value="DEF"%s>DEF</option>'
            . '<option value="MID"%s>MID</option><option value="FWD"%s>FWD</option>'
            . '</select>'
            . '<input type="text" class="sb-pname" value="%s" />'
            . '<input type="number" class="sb-pval small-text" value="%d" min="1" max="20" />'
            . '<span style="font-size:11px;color:#999">pts</span>'
            . '<button type="button" class="sb-del-player" title="Remove player">✕</button>'
            . '</div>',
            esc_attr( $pc ),
            $sel( 'GK' ), $sel( 'DEF' ), $sel( 'MID' ), $sel( 'FWD' ),
            esc_attr( $p['n'] ), (int) $p['v']
        );
    }
    echo '</div>';
    printf(
        '<div style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">'
        . '<button class="button button-primary sb-save-vals" data-name="%1$s">Save Changes</button>'
        . ' <span class="sb-saved-msg" style="color:#46b450;font-weight:600;display:none">✓ Saved</span>'
        . '<button type="button" class="button sb-add-player" style="margin-left:auto">+ Add Player</button>'
        . '</div>'
        . '<div class="sb-add-form">'
        . '<select class="sb-new-pos sb-pos sb-pos-gk"><option value="GK">GK</option><option value="DEF">DEF</option><option value="MID">MID</option><option value="FWD">FWD</option></select>'
        . '<input type="text" class="sb-new-name regular-text" placeholder="Player name" style="flex:1;min-width:160px" />'
        . '<input type="number" class="sb-new-val small-text" value="7" min="1" max="20" style="width:50px" />'
        . '<span style="font-size:11px;color:#999">pts</span>'
        . '<button type="button" class="button button-primary sb-confirm-add">Add</button>'
        . '<button type="button" class="button sb-cancel-add">Cancel</button>'
        . '</div>',
        esc_attr( $name )
    );
}

/* ── AJAX: seed team ── */
add_action( 'wp_ajax_sb_seed_team', 'sb_ajax_seed_team' );
function sb_ajax_seed_team() {
    check_ajax_referer( 'sb_ajax_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $name   = sanitize_text_field( $_POST['name']   ?? '' );
    $api_id = absint( $_POST['api_id'] ?? 0 );
    if ( ! $name || ! $api_id ) wp_send_json_error( 'Missing params' );

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) wp_send_json_error( 'API key not set — add it in Settings › Small Poles' );

    $response = wp_remote_get(
        add_query_arg( [ 'team' => $api_id ], 'https://v3.football.api-sports.io/players/squads' ),
        [ 'timeout' => 15, 'headers' => [ 'x-apisports-key' => $api_key ] ]
    );

    if ( is_wp_error( $response ) ) wp_send_json_error( $response->get_error_message() );

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['response'][0]['players'] ) ) {
        wp_send_json_error( 'No players returned — verify the API Team ID' );
    }

    $pos_map = [ 'Goalkeeper' => 'GK', 'Defender' => 'DEF', 'Midfielder' => 'MID', 'Attacker' => 'FWD' ];
    $val_map = [ 'GK' => 6, 'DEF' => 7, 'MID' => 8, 'FWD' => 9 ];

    $players = [];
    foreach ( $body['response'][0]['players'] as $p ) {
        $pos       = $pos_map[ $p['position'] ] ?? 'MID';
        $players[] = [ 'n' => sanitize_text_field( $p['name'] ), 'p' => $pos, 'v' => $val_map[ $pos ] ];
    }

    $squads        = get_option( 'sb_squads', [] );
    $squads[$name] = $players;
    update_option( 'sb_squads', $squads, false );

    $teams = get_option( 'sb_teams', sb_teams_default() );
    foreach ( $teams as &$t ) {
        if ( (int) $t['api_id'] === $api_id ) { $t['seeded'] = time(); break; }
    }
    unset( $t );
    update_option( 'sb_teams', $teams, false );

    wp_send_json_success( [ 'players' => $players, 'count' => count( $players ) ] );
}

/* ── AJAX: save player values ── */
add_action( 'wp_ajax_sb_save_players', 'sb_ajax_save_players' );
function sb_ajax_save_players() {
    check_ajax_referer( 'sb_ajax_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $name = sanitize_text_field( $_POST['name'] ?? '' );
    $raw  = json_decode( stripslashes( $_POST['players'] ?? '' ), true );
    if ( ! $name || ! is_array( $raw ) ) wp_send_json_error( 'Invalid data' );

    $clean = [];
    foreach ( $raw as $p ) {
        $pos = sanitize_text_field( $p['p'] ?? '' );
        if ( ! in_array( $pos, [ 'GK', 'DEF', 'MID', 'FWD' ], true ) ) continue;
        $clean[] = [
            'n' => sanitize_text_field( $p['n'] ?? '' ),
            'p' => $pos,
            'v' => max( 1, min( 20, absint( $p['v'] ?? 7 ) ) ),
        ];
    }

    $squads        = get_option( 'sb_squads', [] );
    $squads[$name] = $clean;
    update_option( 'sb_squads', $squads, false );

    wp_send_json_success();
}

/* ── AJAX: delete team ── */
add_action( 'wp_ajax_sb_delete_team', 'sb_ajax_delete_team' );
function sb_ajax_delete_team() {
    check_ajax_referer( 'sb_ajax_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $name = sanitize_text_field( $_POST['name'] ?? '' );
    if ( ! $name ) wp_send_json_error( 'Missing name' );

    $teams = get_option( 'sb_teams', sb_teams_default() );
    $teams = array_values( array_filter( $teams, fn( $t ) => $t['name'] !== $name ) );
    update_option( 'sb_teams', $teams, false );

    $squads = get_option( 'sb_squads', [] );
    unset( $squads[$name] );
    update_option( 'sb_squads', $squads, false );

    wp_send_json_success();
}

/* ── AJAX: update team API ID ── */
add_action( 'wp_ajax_sb_update_team_id', 'sb_ajax_update_team_id' );
function sb_ajax_update_team_id() {
    check_ajax_referer( 'sb_ajax_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $name   = sanitize_text_field( $_POST['name']   ?? '' );
    $api_id = absint( $_POST['api_id'] ?? 0 );
    if ( ! $name || ! $api_id ) wp_send_json_error( 'Invalid input' );

    $teams = get_option( 'sb_teams', sb_teams_default() );
    foreach ( $teams as &$t ) {
        if ( $t['name'] === $name ) { $t['api_id'] = $api_id; break; }
    }
    unset( $t );
    update_option( 'sb_teams', $teams, false );

    wp_send_json_success();
}

/* ── Admin columns ── */
function smallpoles_hl_columns( $cols ) {
    return [
        'cb'       => $cols['cb'],
        'title'    => 'Name',
        'hl_nat'   => 'Nationality',
        'hl_pos'   => 'Position',
        'hl_age'   => 'Age',
        'hl_goals' => 'Goals',
        'hl_caps'  => 'Caps',
    ];
}
add_filter( 'manage_hl_player_posts_columns', 'smallpoles_hl_columns' );

function smallpoles_hl_column_content( $col, $post_id ) {
    $map = [
        'hl_nat'   => '_hl_nationality',
        'hl_pos'   => '_hl_position',
        'hl_age'   => '_hl_age',
        'hl_goals' => '_hl_goals',
        'hl_caps'  => '_hl_caps',
    ];
    if ( isset( $map[ $col ] ) ) {
        echo esc_html( get_post_meta( $post_id, $map[ $col ], true ) );
    }
}
add_action( 'manage_hl_player_posts_custom_column', 'smallpoles_hl_column_content', 10, 2 );

/* ── One-time import of default players ── */
function smallpoles_hl_import_defaults() {
    if ( get_option( 'hl_players_imported_v1' ) ) return;
    update_option( 'hl_players_imported_v1', true ); // lock first to prevent double-run

    $defaults = [
        ['n'=>'Alisson Becker','t'=>'Brazil','flag'=>'🇧🇷','p'=>'GK','a'=>33,'g'=>0,'cp'=>80],
        ['n'=>'Manuel Neuer','t'=>'Germany','flag'=>'🇩🇪','p'=>'GK','a'=>40,'g'=>0,'cp'=>124],
        ['n'=>'Gianluigi Donnarumma','t'=>'Italy','flag'=>'🇮🇹','p'=>'GK','a'=>27,'g'=>0,'cp'=>61],
        ['n'=>'Jordan Pickford','t'=>'England','flag'=>'🏴󠁧󠁢󠁥󠁮󠁧󠁿','p'=>'GK','a'=>32,'g'=>0,'cp'=>62],
        ['n'=>'Mike Maignan','t'=>'France','flag'=>'🇫🇷','p'=>'GK','a'=>30,'g'=>0,'cp'=>25],
        ['n'=>'Virgil van Dijk','t'=>'Netherlands','flag'=>'🇳🇱','p'=>'DEF','a'=>34,'g'=>14,'cp'=>64],
        ['n'=>'Achraf Hakimi','t'=>'Morocco','flag'=>'🇲🇦','p'=>'DEF','a'=>27,'g'=>22,'cp'=>75],
        ['n'=>'Antonio Rudiger','t'=>'Germany','flag'=>'🇩🇪','p'=>'DEF','a'=>33,'g'=>4,'cp'=>67],
        ['n'=>'Jules Kounde','t'=>'France','flag'=>'🇫🇷','p'=>'DEF','a'=>27,'g'=>4,'cp'=>43],
        ['n'=>'Theo Hernandez','t'=>'France','flag'=>'🇫🇷','p'=>'DEF','a'=>28,'g'=>8,'cp'=>25],
        ['n'=>'Kalidou Koulibaly','t'=>'Senegal','flag'=>'🇸🇳','p'=>'DEF','a'=>34,'g'=>8,'cp'=>79],
        ['n'=>'Lisandro Martinez','t'=>'Argentina','flag'=>'🇦🇷','p'=>'DEF','a'=>28,'g'=>4,'cp'=>30],
        ['n'=>'Ruben Dias','t'=>'Portugal','flag'=>'🇵🇹','p'=>'DEF','a'=>28,'g'=>3,'cp'=>57],
        ['n'=>'Mohammed Salisu','t'=>'Ghana','flag'=>'🇬🇭','p'=>'DEF','a'=>25,'g'=>3,'cp'=>25],
        ['n'=>'Kim Min-jae','t'=>'South Korea','flag'=>'🇰🇷','p'=>'DEF','a'=>29,'g'=>4,'cp'=>56],
        ['n'=>'Kevin De Bruyne','t'=>'Belgium','flag'=>'🇧🇪','p'=>'MID','a'=>35,'g'=>28,'cp'=>106],
        ['n'=>'Luka Modric','t'=>'Croatia','flag'=>'🇭🇷','p'=>'MID','a'=>41,'g'=>24,'cp'=>179],
        ['n'=>'Rodri','t'=>'Spain','flag'=>'🇪🇸','p'=>'MID','a'=>30,'g'=>18,'cp'=>70],
        ['n'=>'Jude Bellingham','t'=>'England','flag'=>'🏴󠁧󠁢󠁥󠁮󠁧󠁿','p'=>'MID','a'=>22,'g'=>26,'cp'=>44],
        ['n'=>'Declan Rice','t'=>'England','flag'=>'🏴󠁧󠁢󠁥󠁮󠁧󠁿','p'=>'MID','a'=>27,'g'=>10,'cp'=>61],
        ['n'=>'Pedri','t'=>'Spain','flag'=>'🇪🇸','p'=>'MID','a'=>23,'g'=>10,'cp'=>38],
        ['n'=>'Gavi','t'=>'Spain','flag'=>'🇪🇸','p'=>'MID','a'=>22,'g'=>14,'cp'=>45],
        ['n'=>'Florian Wirtz','t'=>'Germany','flag'=>'🇩🇪','p'=>'MID','a'=>23,'g'=>12,'cp'=>28],
        ['n'=>'Jamal Musiala','t'=>'Germany','flag'=>'🇩🇪','p'=>'MID','a'=>23,'g'=>14,'cp'=>42],
        ['n'=>'Enzo Fernandez','t'=>'Argentina','flag'=>'🇦🇷','p'=>'MID','a'=>25,'g'=>9,'cp'=>36],
        ['n'=>'Alexis Mac Allister','t'=>'Argentina','flag'=>'🇦🇷','p'=>'MID','a'=>27,'g'=>15,'cp'=>46],
        ['n'=>'Bruno Fernandes','t'=>'Portugal','flag'=>'🇵🇹','p'=>'MID','a'=>32,'g'=>16,'cp'=>71],
        ['n'=>'Sofyan Amrabat','t'=>'Morocco','flag'=>'🇲🇦','p'=>'MID','a'=>29,'g'=>2,'cp'=>53],
        ['n'=>'Mohammed Kudus','t'=>'Ghana','flag'=>'🇬🇭','p'=>'MID','a'=>24,'g'=>15,'cp'=>34],
        ['n'=>'Thomas Partey','t'=>'Ghana','flag'=>'🇬🇭','p'=>'MID','a'=>33,'g'=>14,'cp'=>50],
        ['n'=>'Pape Matar Sarr','t'=>'Senegal','flag'=>'🇸🇳','p'=>'MID','a'=>22,'g'=>5,'cp'=>35],
        ['n'=>'Heung-min Son','t'=>'South Korea','flag'=>'🇰🇷','p'=>'MID','a'=>34,'g'=>37,'cp'=>121],
        ['n'=>'Federico Valverde','t'=>'Uruguay','flag'=>'🇺🇾','p'=>'MID','a'=>27,'g'=>14,'cp'=>60],
        ['n'=>'Cristiano Ronaldo','t'=>'Portugal','flag'=>'🇵🇹','p'=>'FWD','a'=>41,'g'=>130,'cp'=>215],
        ['n'=>'Lionel Messi','t'=>'Argentina','flag'=>'🇦🇷','p'=>'FWD','a'=>38,'g'=>109,'cp'=>183],
        ['n'=>'Kylian Mbappe','t'=>'France','flag'=>'🇫🇷','p'=>'FWD','a'=>27,'g'=>53,'cp'=>89],
        ['n'=>'Harry Kane','t'=>'England','flag'=>'🏴󠁧󠁢󠁥󠁮󠁧󠁿','p'=>'FWD','a'=>32,'g'=>68,'cp'=>102],
        ['n'=>'Robert Lewandowski','t'=>'Poland','flag'=>'🇵🇱','p'=>'FWD','a'=>37,'g'=>82,'cp'=>148],
        ['n'=>'Romelu Lukaku','t'=>'Belgium','flag'=>'🇧🇪','p'=>'FWD','a'=>33,'g'=>68,'cp'=>113],
        ['n'=>'Antoine Griezmann','t'=>'France','flag'=>'🇫🇷','p'=>'FWD','a'=>35,'g'=>53,'cp'=>132],
        ['n'=>'Mohamed Salah','t'=>'Egypt','flag'=>'🇪🇬','p'=>'FWD','a'=>34,'g'=>55,'cp'=>101],
        ['n'=>'Sadio Mane','t'=>'Senegal','flag'=>'🇸🇳','p'=>'FWD','a'=>34,'g'=>39,'cp'=>102],
        ['n'=>'Neymar','t'=>'Brazil','flag'=>'🇧🇷','p'=>'FWD','a'=>34,'g'=>79,'cp'=>128],
        ['n'=>'Vinicius Jr','t'=>'Brazil','flag'=>'🇧🇷','p'=>'FWD','a'=>25,'g'=>28,'cp'=>50],
        ['n'=>'Raphinha','t'=>'Brazil','flag'=>'🇧🇷','p'=>'FWD','a'=>29,'g'=>28,'cp'=>58],
        ['n'=>'Julian Alvarez','t'=>'Argentina','flag'=>'🇦🇷','p'=>'FWD','a'=>26,'g'=>30,'cp'=>52],
        ['n'=>'Lautaro Martinez','t'=>'Argentina','flag'=>'🇦🇷','p'=>'FWD','a'=>28,'g'=>37,'cp'=>67],
        ['n'=>'Bukayo Saka','t'=>'England','flag'=>'🏴󠁧󠁢󠁥󠁮󠁧󠁿','p'=>'FWD','a'=>24,'g'=>22,'cp'=>48],
        ['n'=>'Phil Foden','t'=>'England','flag'=>'🏴󠁧󠁢󠁥󠁮󠁧󠁿','p'=>'FWD','a'=>26,'g'=>17,'cp'=>41],
        ['n'=>'Marcus Rashford','t'=>'England','flag'=>'🏴󠁧󠁢󠁥󠁮󠁧󠁿','p'=>'FWD','a'=>28,'g'=>17,'cp'=>60],
        ['n'=>'Lamine Yamal','t'=>'Spain','flag'=>'🇪🇸','p'=>'FWD','a'=>18,'g'=>15,'cp'=>26],
        ['n'=>'Nico Williams','t'=>'Spain','flag'=>'🇪🇸','p'=>'FWD','a'=>22,'g'=>6,'cp'=>28],
        ['n'=>'Hakim Ziyech','t'=>'Morocco','flag'=>'🇲🇦','p'=>'FWD','a'=>33,'g'=>22,'cp'=>63],
        ['n'=>'Youssef En-Nesyri','t'=>'Morocco','flag'=>'🇲🇦','p'=>'FWD','a'=>29,'g'=>22,'cp'=>55],
        ['n'=>'Inaki Williams','t'=>'Ghana','flag'=>'🇬🇭','p'=>'FWD','a'=>32,'g'=>18,'cp'=>30],
        ['n'=>'Antoine Semenyo','t'=>'Ghana','flag'=>'🇬🇭','p'=>'FWD','a'=>25,'g'=>5,'cp'=>25],
        ['n'=>'Kamaldeen Sulemana','t'=>'Ghana','flag'=>'🇬🇭','p'=>'FWD','a'=>23,'g'=>8,'cp'=>28],
        ['n'=>'Darwin Nunez','t'=>'Uruguay','flag'=>'🇺🇾','p'=>'FWD','a'=>26,'g'=>22,'cp'=>57],
        ['n'=>'Luis Diaz','t'=>'Colombia','flag'=>'🇨🇴','p'=>'FWD','a'=>28,'g'=>22,'cp'=>60],
        ['n'=>'Santiago Gimenez','t'=>'Mexico','flag'=>'🇲🇽','p'=>'FWD','a'=>24,'g'=>18,'cp'=>30],
        ['n'=>'Arda Guler','t'=>'Turkey','flag'=>'🇹🇷','p'=>'FWD','a'=>20,'g'=>6,'cp'=>25],
        ['n'=>'Richarlison','t'=>'Brazil','flag'=>'🇧🇷','p'=>'FWD','a'=>29,'g'=>20,'cp'=>55],
        ['n'=>'Nicolas Jackson','t'=>'Senegal','flag'=>'🇸🇳','p'=>'FWD','a'=>24,'g'=>10,'cp'=>30],
    ];

    foreach ( $defaults as $player ) {
        $id = wp_insert_post( [
            'post_type'   => 'hl_player',
            'post_title'  => sanitize_text_field( $player['n'] ),
            'post_status' => 'publish',
        ] );
        if ( $id && ! is_wp_error( $id ) ) {
            update_post_meta( $id, '_hl_nationality', $player['t'] );
            update_post_meta( $id, '_hl_flag',        $player['flag'] );
            update_post_meta( $id, '_hl_position',    $player['p'] );
            update_post_meta( $id, '_hl_age',         $player['a'] );
            update_post_meta( $id, '_hl_goals',       $player['g'] );
            update_post_meta( $id, '_hl_caps',        $player['cp'] );
        }
    }
}
add_action( 'init', 'smallpoles_hl_import_defaults', 20 );

// Override WordPress virtual robots.txt
add_filter( 'robots_txt', function ( $output, $public ) {
    if ( '1' !== $public ) {
        return $output;
    }
    return "User-agent: *\nAllow: /\nDisallow: /wp-admin/\nDisallow: /wp-login.php\nDisallow: /wp-includes/\nDisallow: /xmlrpc.php\n\nSitemap: https://smallpoles.online/sitemap_index.xml\n";
}, 10, 2 );


/* ═══════════════════════════════════════════════════════════════════
   Squad Builder — User Save & Email Notifications
   Completely separate from WordPress users — no wp_users entries.
   ═══════════════════════════════════════════════════════════════════ */

/* ── DB tables ────────────────────────────────────────────────────── */
function sp_squads_maybe_create_table() {
    if ( get_option( 'sp_squads_db_version' ) === '2.0' ) return;
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    // Custom users table — completely separate from wp_users
    $users_table = $wpdb->prefix . 'sp_users';
    $sql_users   = "CREATE TABLE {$users_table} (
        id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        display_name  VARCHAR(64)     NOT NULL DEFAULT '',
        email         VARCHAR(100)    NOT NULL,
        password_hash VARCHAR(255)    NOT NULL,
        created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY   (id),
        UNIQUE KEY    email (email)
    ) {$charset};";

    // Squads table
    $squads_table = $wpdb->prefix . 'sp_user_squads';
    $sql_squads   = "CREATE TABLE {$squads_table} (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id      BIGINT UNSIGNED NOT NULL,
        squad_name   VARCHAR(64)     NOT NULL DEFAULT '',
        nation       VARCHAR(64)     NOT NULL DEFAULT '',
        formation    VARCHAR(16)     NOT NULL DEFAULT '',
        squad_data   LONGTEXT        NOT NULL,
        budget_used  TINYINT UNSIGNED NOT NULL DEFAULT 0,
        total_points INT UNSIGNED    NOT NULL DEFAULT 0,
        created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY          user_id (user_id)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_users );
    dbDelta( $sql_squads );
    update_option( 'sp_squads_db_version', '2.0' );
}
add_action( 'init', 'sp_squads_maybe_create_table' );

/* ── Session helpers ──────────────────────────────────────────────── */
function sp_squads_create_session( int $sp_user_id ): void {
    $token = bin2hex( random_bytes( 32 ) ); // 64-char hex string
    set_transient( 'sp_sess_' . $token, $sp_user_id, 30 * DAY_IN_SECONDS );
    setcookie( 'sp_squad_sess', $token, [
        'expires'  => time() + 30 * DAY_IN_SECONDS,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Strict',
    ] );
}

function sp_squads_get_current_sp_user_id(): int {
    $token = isset( $_COOKIE['sp_squad_sess'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['sp_squad_sess'] ) ) : '';
    if ( strlen( $token ) !== 64 ) return 0;
    return (int) get_transient( 'sp_sess_' . $token );
}

function sp_squads_auth_check(): bool {
    return sp_squads_get_current_sp_user_id() > 0;
}

/* Reads the current sp_user from the session cookie — used by localize_script */
function sp_squads_get_page_user(): ?array {
    $user_id = sp_squads_get_current_sp_user_id();
    if ( ! $user_id ) return null;
    global $wpdb;
    $table = $wpdb->prefix . 'sp_users';
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT id, display_name, email FROM {$table} WHERE id = %d",
        $user_id
    ), ARRAY_A ) ?: null;
}

/* ── REST routes ──────────────────────────────────────────────────── */
function sp_squads_register_rest_routes() {
    register_rest_route( 'smallpoles/v1', '/squad-auth/register', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'sp_squads_rest_register',
        'args'                => [
            'display_name' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'email'        => [ 'required' => true, 'sanitize_callback' => 'sanitize_email' ],
            'password'     => [ 'required' => true ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/squad-auth/login', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'sp_squads_rest_login',
        'args'                => [
            'email'    => [ 'required' => true, 'sanitize_callback' => 'sanitize_email' ],
            'password' => [ 'required' => true ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/squads', [
        [
            'methods'             => 'GET',
            'permission_callback' => 'sp_squads_auth_check',
            'callback'            => 'sp_squads_rest_get',
        ],
        [
            'methods'             => 'POST',
            'permission_callback' => 'sp_squads_auth_check',
            'callback'            => 'sp_squads_rest_save',
            'args'                => [
                'squad_name'  => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
                'nation'      => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
                'formation'   => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
                'squad_data'  => [ 'required' => true ],
                'budget_used' => [ 'default' => 0, 'sanitize_callback' => 'absint' ],
            ],
        ],
    ] );

    register_rest_route( 'smallpoles/v1', '/squads/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'permission_callback' => 'sp_squads_auth_check',
        'callback'            => 'sp_squads_rest_delete',
    ] );

    register_rest_route( 'smallpoles/v1', '/squad-auth/logout', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'sp_squads_rest_logout',
    ] );
}
add_action( 'rest_api_init', 'sp_squads_register_rest_routes' );

function sp_squads_rest_logout( WP_REST_Request $req ) {
    $token = isset( $_COOKIE['sp_squad_sess'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['sp_squad_sess'] ) ) : '';
    if ( strlen( $token ) === 64 ) {
        delete_transient( 'sp_sess_' . $token );
    }
    setcookie( 'sp_squad_sess', '', [
        'expires'  => time() - DAY_IN_SECONDS,
        'path'     => '/',
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Strict',
    ] );
    return rest_ensure_response( [ 'logged_out' => true ] );
}

/* ── Route callbacks ──────────────────────────────────────────────── */
function sp_squads_rest_register( WP_REST_Request $req ) {
    if ( $err = smallpoles_rate_check( 'sq_register', 5, 300 ) ) return $err;

    $email = $req->get_param( 'email' );
    $name  = $req->get_param( 'display_name' );
    $pass  = $req->get_param( 'password' );

    if ( ! is_email( $email ) ) {
        return new WP_Error( 'invalid_email', 'Invalid email address.', [ 'status' => 400 ] );
    }
    if ( strlen( $pass ) < 8 ) {
        return new WP_Error( 'weak_password', 'Password must be at least 8 characters.', [ 'status' => 400 ] );
    }

    global $wpdb;
    $table  = $wpdb->prefix . 'sp_users';
    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s", $email ) );
    if ( $exists ) {
        return new WP_Error( 'email_exists', 'An account with this email already exists.', [ 'status' => 409 ] );
    }

    $result = $wpdb->insert( $table, [
        'display_name'  => $name,
        'email'         => $email,
        'password_hash' => password_hash( $pass, PASSWORD_DEFAULT ),
    ] );

    if ( ! $result ) {
        return new WP_Error( 'register_failed', 'Registration failed. Please try again.', [ 'status' => 500 ] );
    }

    $sp_user_id = (int) $wpdb->insert_id;
    sp_squads_create_session( $sp_user_id );
    sp_squads_send_welcome_email( $name, $email );

    return rest_ensure_response( [
        'user_id'      => $sp_user_id,
        'display_name' => $name,
        'email'        => $email,
    ] );
}

function sp_squads_rest_login( WP_REST_Request $req ) {
    if ( $err = smallpoles_rate_check( 'sq_login', 10, 300 ) ) return $err;

    global $wpdb;
    $table = $wpdb->prefix . 'sp_users';
    $user  = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, display_name, email, password_hash FROM {$table} WHERE email = %s",
        $req->get_param( 'email' )
    ), ARRAY_A );

    if ( ! $user || ! password_verify( $req->get_param( 'password' ), $user['password_hash'] ) ) {
        return new WP_Error( 'invalid_credentials', 'Incorrect email or password.', [ 'status' => 401 ] );
    }

    sp_squads_create_session( (int) $user['id'] );

    return rest_ensure_response( [
        'user_id'      => (int) $user['id'],
        'display_name' => $user['display_name'],
        'email'        => $user['email'],
    ] );
}

function sp_squads_rest_get( WP_REST_Request $req ) {
    global $wpdb;
    $table = $wpdb->prefix . 'sp_user_squads';
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, squad_name, nation, formation, squad_data, budget_used, total_points, created_at
         FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC",
        sp_squads_get_current_sp_user_id()
    ), ARRAY_A );

    foreach ( $rows as &$row ) {
        $row['squad_data'] = json_decode( $row['squad_data'], true );
    }

    return rest_ensure_response( $rows );
}

function sp_squads_rest_save( WP_REST_Request $req ) {
    global $wpdb;
    $sp_user_id   = sp_squads_get_current_sp_user_id();
    $squads_table = $wpdb->prefix . 'sp_user_squads';
    $users_table  = $wpdb->prefix . 'sp_users';

    $valid_formations = [ '4-3-3', '4-4-2', '3-5-2', '4-2-3-1' ];
    if ( ! in_array( $req->get_param( 'formation' ), $valid_formations, true ) ) {
        return new WP_Error( 'invalid_formation', 'Invalid formation.', [ 'status' => 400 ] );
    }

    $count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$squads_table} WHERE user_id = %d", $sp_user_id
    ) );
    if ( $count >= 5 ) {
        return new WP_Error( 'squad_limit', 'You can save up to 5 squads. Delete one first.', [ 'status' => 400 ] );
    }

    $result = $wpdb->insert( $squads_table, [
        'user_id'    => $sp_user_id,
        'squad_name' => $req->get_param( 'squad_name' ),
        'nation'     => $req->get_param( 'nation' ),
        'formation'  => $req->get_param( 'formation' ),
        'squad_data' => wp_json_encode( $req->get_param( 'squad_data' ) ),
        'budget_used'=> $req->get_param( 'budget_used' ),
    ] );

    if ( ! $result ) {
        return new WP_Error( 'save_failed', 'Failed to save squad.', [ 'status' => 500 ] );
    }

    $user = $wpdb->get_row( $wpdb->prepare(
        "SELECT display_name, email FROM {$users_table} WHERE id = %d", $sp_user_id
    ), ARRAY_A );

    if ( $user ) {
        sp_squads_send_confirm_email(
            $user['display_name'],
            $user['email'],
            $req->get_param( 'squad_name' ),
            $req->get_param( 'formation' ),
            $req->get_param( 'squad_data' )
        );
    }

    return rest_ensure_response( [ 'id' => $wpdb->insert_id, 'message' => 'Squad saved!' ] );
}

function sp_squads_rest_delete( WP_REST_Request $req ) {
    global $wpdb;
    $table   = $wpdb->prefix . 'sp_user_squads';
    $deleted = $wpdb->delete( $table, [
        'id'      => (int) $req->get_param( 'id' ),
        'user_id' => sp_squads_get_current_sp_user_id(),
    ] );
    if ( ! $deleted ) {
        return new WP_Error( 'not_found', 'Squad not found.', [ 'status' => 404 ] );
    }
    return rest_ensure_response( [ 'deleted' => true ] );
}

/* ── Email helpers ────────────────────────────────────────────────── */
function sp_squads_email_header() {
    return '<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0f0a"><tr><td align="center" style="padding:32px 16px">'
         . '<table width="560" cellpadding="0" cellspacing="0" style="background:#151515;border-radius:16px;overflow:hidden;max-width:100%">'
         . '<tr><td style="background:linear-gradient(135deg,#0f3460,#16213e);padding:28px 32px">'
         . '<div style="font-size:22px;font-weight:800;color:#fff">&#x26BD; Small Poles</div>'
         . '<div style="font-size:13px;color:rgba(255,255,255,.5);margin-top:4px">World Cup 2026 Squad Builder</div>'
         . '</td></tr>';
}

function sp_squads_email_footer() {
    $url = esc_url( home_url( '/games/squad/' ) );
    return '<tr><td style="padding:16px 32px;background:#0a0f0a;font-size:11px;color:rgba(255,255,255,.25);text-align:center">'
         . 'Small Poles &middot; <a href="' . $url . '" style="color:rgba(255,255,255,.35)">smallpoles.online</a>'
         . '</td></tr></table></td></tr></table>';
}

function sp_squads_mailer_headers() {
    return [
        'Content-Type: text/html; charset=UTF-8',
        'From: Small Poles <info@smallpoles.online>',
    ];
}

function sp_squads_send_confirm_email( string $display_name, string $email, string $squad_name, string $formation, $squad_data ) {
    $by_pos = [ 'GK' => [], 'DEF' => [], 'MID' => [], 'FWD' => [] ];
    if ( is_array( $squad_data ) ) {
        foreach ( $squad_data as $slot ) {
            $pos = $slot['pos'] ?? '';
            if ( ! empty( $slot['player']['n'] ) && isset( $by_pos[ $pos ] ) ) {
                $by_pos[ $pos ][] = esc_html( $slot['player']['n'] );
            }
        }
    }

    $rows = '';
    foreach ( $by_pos as $pos => $names ) {
        if ( $names ) {
            $rows .= '<tr><td style="color:#9ca3af;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;padding:6px 0 2px;vertical-align:top;width:40px">'
                   . esc_html( $pos ) . '</td>'
                   . '<td style="color:#f3f4f6;font-size:14px;padding:6px 0 2px">' . implode( ', ', $names ) . '</td></tr>';
        }
    }

    $cta_url = esc_url( home_url( '/games/squad/' ) );
    $body    = '<tr><td style="padding:28px 32px">'
             . '<div style="font-size:20px;font-weight:700;color:#fff;margin-bottom:4px">' . esc_html( $squad_name ) . '</div>'
             . '<div style="font-size:13px;color:#9ca3af;margin-bottom:20px">' . esc_html( $formation ) . ' formation</div>'
             . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">' . $rows . '</table>'
             . '<div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,.08)">'
             . '<a href="' . $cta_url . '" style="display:inline-block;background:#00ff7f;color:#000;font-weight:700;font-size:14px;text-decoration:none;padding:12px 24px;border-radius:8px">Edit Your Squad &#x2192;</a>'
             . '</div></td></tr>';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#0a0f0a;font-family:system-ui,sans-serif">'
          . sp_squads_email_header() . $body . sp_squads_email_footer()
          . '</body></html>';

    wp_mail( $email, 'Squad saved: ' . $squad_name . ' — Small Poles', $html, sp_squads_mailer_headers() );
}

function sp_squads_send_welcome_email( string $display_name, string $email ) {
    $cta_url = esc_url( home_url( '/games/squad/' ) );
    $body    = '<tr><td style="padding:28px 32px">'
             . '<div style="font-size:20px;font-weight:700;color:#fff;margin-bottom:8px">Welcome, ' . esc_html( $display_name ) . '!</div>'
             . '<p style="color:#9ca3af;font-size:14px;line-height:1.6;margin:0 0 20px">Your Small Poles account is ready. Start building and saving your World Cup 2026 squads.</p>'
             . '<a href="' . $cta_url . '" style="display:inline-block;background:#00ff7f;color:#000;font-weight:700;font-size:14px;text-decoration:none;padding:12px 24px;border-radius:8px">Build Your Squad &#x2192;</a>'
             . '</td></tr>';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#0a0f0a;font-family:system-ui,sans-serif">'
          . sp_squads_email_header() . $body . sp_squads_email_footer()
          . '</body></html>';

    wp_mail( $email, 'Welcome to Small Poles — your account is ready', $html, sp_squads_mailer_headers() );
}

/* ── Weekly points summary cron ───────────────────────────────────── */
function sp_squads_schedule_cron() {
    if ( ! wp_next_scheduled( 'sp_squads_weekly_update' ) ) {
        wp_schedule_event( time(), 'weekly', 'sp_squads_weekly_update' );
    }
}
add_action( 'wp', 'sp_squads_schedule_cron' );

add_action( 'sp_squads_weekly_update', 'sp_squads_send_weekly_emails' );

function sp_squads_send_weekly_emails() {
    global $wpdb;
    $squads_table = $wpdb->prefix . 'sp_user_squads';
    $users_table  = $wpdb->prefix . 'sp_users';
    $user_ids     = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$squads_table}" );

    foreach ( $user_ids as $user_id ) {
        $user = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, display_name, email FROM {$users_table} WHERE id = %d", $user_id
        ), ARRAY_A );
        if ( ! $user ) continue;

        $squads = $wpdb->get_results( $wpdb->prepare(
            "SELECT squad_name, nation, formation, budget_used, total_points FROM {$squads_table} WHERE user_id = %d ORDER BY total_points DESC",
            $user_id
        ), ARRAY_A );
        sp_squads_send_weekly_summary_email( $user, $squads );
    }
}

function sp_squads_send_weekly_summary_email( array $user, array $squads ) {
    $cta_url = esc_url( home_url( '/games/squad/' ) );
    $rows    = '';
    foreach ( $squads as $sq ) {
        $rows .= '<tr style="border-bottom:1px solid rgba(255,255,255,.06)">'
               . '<td style="padding:10px 0;color:#f3f4f6;font-size:14px">' . esc_html( $sq['squad_name'] ) . '</td>'
               . '<td style="padding:10px 0;color:#9ca3af;font-size:13px">' . esc_html( $sq['formation'] ) . '</td>'
               . '<td style="padding:10px 0;color:#00ff7f;font-size:14px;font-weight:700;text-align:right">' . (int) $sq['total_points'] . ' pts</td>'
               . '</tr>';
    }

    $body = '<tr><td style="padding:28px 32px">'
          . '<div style="font-size:18px;font-weight:700;color:#fff;margin-bottom:16px">Your Squads — Weekly Update</div>'
          . '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse">' . $rows . '</table>'
          . '<div style="margin-top:24px;padding-top:20px;border-top:1px solid rgba(255,255,255,.08)">'
          . '<a href="' . $cta_url . '" style="display:inline-block;background:#00ff7f;color:#000;font-weight:700;font-size:14px;text-decoration:none;padding:12px 24px;border-radius:8px">Manage Your Squads &#x2192;</a>'
          . '</div></td></tr>';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#0a0f0a;font-family:system-ui,sans-serif">'
          . sp_squads_email_header() . $body . sp_squads_email_footer()
          . '</body></html>';

    wp_mail( $user['email'], 'Your Weekly Squad Update — Small Poles', $html, sp_squads_mailer_headers() );
}

/* ── Squad Users admin page ───────────────────────────────────────── */
function sp_squads_users_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $users_table  = $wpdb->prefix . 'sp_users';
    $squads_table = $wpdb->prefix . 'sp_user_squads';

    /* Handle delete */
    if (
        isset( $_POST['sp_delete_user'], $_POST['sp_delete_user_id'], $_POST['_wpnonce'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'sp_delete_sq_user' )
    ) {
        $del_id = absint( $_POST['sp_delete_user_id'] );
        $wpdb->delete( $squads_table, [ 'user_id' => $del_id ] );
        $wpdb->delete( $users_table,  [ 'id'      => $del_id ] );
        echo '<div class="notice notice-success"><p>User deleted.</p></div>';
    }

    /* Fetch users with squad count */
    $users = $wpdb->get_results(
        "SELECT u.id, u.display_name, u.email, u.created_at,
                COUNT(s.id) AS squad_count
         FROM {$users_table} u
         LEFT JOIN {$squads_table} s ON s.user_id = u.id
         GROUP BY u.id
         ORDER BY u.created_at DESC",
        ARRAY_A
    );

    ?>
    <div class="wrap">
        <h1>Squad Users <span style="font-size:13px;font-weight:400;color:#666;margin-left:8px"><?php echo count( $users ); ?> registered</span></h1>

        <?php if ( empty( $users ) ) : ?>
            <p>No squad users yet.</p>
        <?php else : ?>
        <table class="widefat striped" style="margin-top:16px">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th style="text-align:center">Squads</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $users as $u ) : ?>
                <tr>
                    <td style="color:#999"><?php echo (int) $u['id']; ?></td>
                    <td><strong><?php echo esc_html( $u['display_name'] ); ?></strong></td>
                    <td><?php echo esc_html( $u['email'] ); ?></td>
                    <td style="color:#666"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $u['created_at'] ) ) ); ?></td>
                    <td style="text-align:center"><?php echo (int) $u['squad_count']; ?></td>
                    <td>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this user and all their squads?')">
                            <?php wp_nonce_field( 'sp_delete_sq_user' ); ?>
                            <input type="hidden" name="sp_delete_user_id" value="<?php echo (int) $u['id']; ?>" />
                            <button type="submit" name="sp_delete_user" class="button button-small" style="color:#a00;border-color:#a00">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

/* ============================================================
   MATCH SCORING SYSTEM
   DB · Point Formula · REST · Admin · Cron
   ============================================================ */

/* ── DB tables ────────────────────────────────────────────────── */
function sp_scoring_maybe_create_tables(): void {
    if ( get_option( 'sp_scoring_db_version' ) === '1.0' ) return;
    global $wpdb;
    $c = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_matches (
        id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
        fixture_id BIGINT UNSIGNED  NOT NULL,
        home_team  VARCHAR(100)     NOT NULL DEFAULT '',
        away_team  VARCHAR(100)     NOT NULL DEFAULT '',
        home_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
        away_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
        round      VARCHAR(50)      NOT NULL DEFAULT '',
        match_date DATE,
        scored     TINYINT(1)       NOT NULL DEFAULT 0,
        created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY  fixture_id (fixture_id)
    ) {$c};" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_player_stats (
        id              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
        match_id        BIGINT UNSIGNED   NOT NULL,
        fixture_id      BIGINT UNSIGNED   NOT NULL,
        api_player_id   INT               NOT NULL DEFAULT 0,
        api_player_name VARCHAR(100)      NOT NULL DEFAULT '',
        api_team_name   VARCHAR(100)      NOT NULL DEFAULT '',
        sp_player_key   VARCHAR(200)      NOT NULL DEFAULT '',
        sp_player_pos   VARCHAR(10)       NOT NULL DEFAULT '',
        minutes_played  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        goals           TINYINT           NOT NULL DEFAULT 0,
        assists         TINYINT           NOT NULL DEFAULT 0,
        clean_sheet     TINYINT(1)        NOT NULL DEFAULT 0,
        saves           TINYINT UNSIGNED  NOT NULL DEFAULT 0,
        goals_conceded  TINYINT UNSIGNED  NOT NULL DEFAULT 0,
        yellow_cards    TINYINT UNSIGNED  NOT NULL DEFAULT 0,
        red_cards       TINYINT UNSIGNED  NOT NULL DEFAULT 0,
        own_goals       TINYINT UNSIGNED  NOT NULL DEFAULT 0,
        penalty_misses  TINYINT UNSIGNED  NOT NULL DEFAULT 0,
        bonus_points    TINYINT           NOT NULL DEFAULT 0,
        points_earned   SMALLINT          NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY  api_player_match (match_id, api_player_id),
        KEY         match_id (match_id),
        KEY         sp_player_key (sp_player_key(100))
    ) {$c};" );

    update_option( 'sp_scoring_db_version', '1.0' );
}
add_action( 'init', 'sp_scoring_maybe_create_tables' );

/* Remove any previously scheduled periodic scoring job */
add_action( 'init', function () {
    $ts = wp_next_scheduled( 'sp_scoring_batch_job' );
    if ( $ts ) {
        wp_unschedule_event( $ts, 'sp_scoring_batch_job' );
    }
} );

/* ── Point formula ────────────────────────────────────────────── */
function sp_calc_player_points( array $s, string $pos ): int {
    $pos = strtoupper( trim( $pos ) );
    $p   = 0;
    $m   = max( 0, (int) ( $s['minutes_played'] ?? 0 ) );
    if ( $m > 0 ) { $p += 1; if ( $m >= 60 ) $p += 1; }
    $gm  = in_array( $pos, [ 'GK', 'DEF' ], true ) ? 6 : 5;
    $p  += (int) ( $s['goals'] ?? 0 ) * $gm;
    $p  += (int) ( $s['assists'] ?? 0 ) * 3;
    if ( ! empty( $s['clean_sheet'] ) ) {
        if ( in_array( $pos, [ 'GK', 'DEF' ], true ) ) $p += 3;
        elseif ( $pos === 'MID' ) $p += 1;
    }
    if ( $pos === 'GK' ) $p += (int) floor( (int) ( $s['saves'] ?? 0 ) / 3 );
    $gc = (int) ( $s['goals_conceded'] ?? 0 );
    if ( in_array( $pos, [ 'GK', 'DEF' ], true ) && $gc >= 2 ) $p -= (int) floor( $gc / 2 );
    $p -= (int) ( $s['yellow_cards'] ?? 0 );
    $p -= (int) ( $s['red_cards'] ?? 0 ) * 3;
    $p -= (int) ( $s['own_goals'] ?? 0 ) * 2;
    $p -= (int) ( $s['penalty_misses'] ?? 0 ) * 2;
    $p += (int) ( $s['bonus_points'] ?? 0 );
    return $p;
}

/* ── REST: admin-only fixture-players proxy ───────────────────── */
add_action( 'rest_api_init', function () {
    register_rest_route( 'smallpoles/v1', '/fixture-players', [
        'methods'             => 'GET',
        'permission_callback' => fn() => current_user_can( 'manage_options' ),
        'callback'            => 'sp_fixture_players_proxy',
        'args'                => [
            'fixture' => [
                'required'          => true,
                'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );
} );

function sp_fixture_players_proxy( WP_REST_Request $req ) {
    $fid    = (int) $req->get_param( 'fixture' );
    $result = smallpoles_api_fetch( 'fixtures/players', [ 'fixture' => $fid ], "fplayers_{$fid}", 60 * MINUTE_IN_SECONDS );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

/* ── Admin AJAX — fetch fixture info (for Add Match form) ─────── */
add_action( 'wp_ajax_sp_scoring_fetch_fixture', function () {
    check_ajax_referer( 'sp_scoring_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

    $fid    = absint( $_GET['fixture_id'] ?? 0 );
    if ( ! $fid ) wp_send_json_error( 'No fixture ID provided' );

    $result = smallpoles_api_fetch( 'fixtures', [ 'id' => $fid ], "fix_info_{$fid}", 30 * MINUTE_IN_SECONDS );
    if ( $result['error'] ) wp_send_json_error( $result['error']->get_error_message() );

    $fix = $result['data'][0] ?? null;
    if ( ! $fix ) wp_send_json_error( 'Fixture not found' );

    wp_send_json_success( [
        'home'  => $fix['teams']['home']['name'] ?? '',
        'away'  => $fix['teams']['away']['name'] ?? '',
        'hGoal' => (int) ( $fix['goals']['home'] ?? 0 ),
        'aGoal' => (int) ( $fix['goals']['away'] ?? 0 ),
        'round' => $fix['league']['round'] ?? '',
        'date'  => isset( $fix['fixture']['date'] ) ? date( 'Y-m-d', strtotime( $fix['fixture']['date'] ) ) : '',
    ] );
} );

/* ── Admin AJAX — fetch fixture player stats from API ─────────── */
add_action( 'wp_ajax_sp_scoring_fetch_players', function () {
    check_ajax_referer( 'sp_scoring_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

    $fid    = absint( $_GET['fixture_id'] ?? 0 );
    if ( ! $fid ) wp_send_json_error( 'No fixture ID' );

    $result = smallpoles_api_fetch( 'fixtures/players', [ 'fixture' => $fid ], "fplayers_{$fid}", 60 * MINUTE_IN_SECONDS );
    if ( $result['error'] ) wp_send_json_error( $result['error']->get_error_message() );

    $pos_map = [ 'G' => 'GK', 'D' => 'DEF', 'M' => 'MID', 'F' => 'FWD' ];
    $out     = [];
    foreach ( $result['data'] ?? [] as $team_data ) {
        $team = $team_data['team']['name'] ?? '';
        foreach ( $team_data['players'] ?? [] as $pd ) {
            $st  = $pd['statistics'][0] ?? [];
            $out[] = [
                'id'      => (int) ( $pd['player']['id'] ?? 0 ),
                'name'    => $pd['player']['name'] ?? '',
                'team'    => $team,
                'pos'     => $pos_map[ $st['games']['position'] ?? '' ] ?? 'MID',
                'min'     => (int) ( $st['games']['minutes'] ?? 0 ),
                'goals'   => (int) ( $st['goals']['total'] ?? 0 ),
                'assists' => (int) ( $st['goals']['assists'] ?? 0 ),
                'saves'   => (int) ( $st['goals']['saves'] ?? 0 ),
                'yc'      => (int) ( $st['cards']['yellow'] ?? 0 ),
                'rc'      => (int) ( $st['cards']['red'] ?? 0 ),
                'pm'      => (int) ( $st['penalty']['missed'] ?? 0 ),
            ];
        }
    }
    wp_send_json_success( $out );
} );

/* ── Admin menu ───────────────────────────────────────────────── */
add_action( 'admin_menu', function () {
    add_submenu_page(
        'sp-games',
        'Match Scoring',
        'Match Scoring',
        'manage_options',
        'sp-match-scoring',
        'sp_match_scoring_admin_page'
    );
} );

/* ── Admin actions — must run before any output ───────────────── */
add_action( 'admin_init', function () {
    if ( ( $_GET['page'] ?? '' ) !== 'sp-match-scoring' ) return;
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $mt       = $wpdb->prefix . 'sp_matches';
    $st       = $wpdb->prefix . 'sp_player_stats';
    $match_id = absint( $_GET['match'] ?? 0 );

    /* Add match -------------------------------------------------- */
    if ( isset( $_POST['sp_add_match'] ) && check_admin_referer( 'sp_add_match' ) ) {
        $fid = absint( $_POST['fixture_id'] ?? 0 );
        if ( $fid ) {
            $wpdb->replace( $mt, [
                'fixture_id' => $fid,
                'home_team'  => sanitize_text_field( $_POST['home_team'] ?? '' ),
                'away_team'  => sanitize_text_field( $_POST['away_team'] ?? '' ),
                'home_score' => absint( $_POST['home_score'] ?? 0 ),
                'away_score' => absint( $_POST['away_score'] ?? 0 ),
                'round'      => sanitize_text_field( $_POST['round'] ?? '' ),
                'match_date' => sanitize_text_field( $_POST['match_date'] ?? '' ) ?: null,
            ] );
            $new_id = (int) $wpdb->insert_id ?: (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$mt} WHERE fixture_id=%d", $fid ) );
            wp_safe_redirect( admin_url( 'admin.php?page=sp-match-scoring&view=players&match=' . $new_id . '&added=1' ) );
            exit;
        }
    }

    /* Save player stats ------------------------------------------ */
    if ( isset( $_POST['sp_save_player_stats'] ) && $match_id && check_admin_referer( 'sp_save_ps_' . $match_id ) ) {
        $match = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$mt} WHERE id=%d", $match_id ) );
        if ( $match ) {
            foreach ( (array) ( $_POST['player'] ?? [] ) as $api_pid => $r ) {
                $api_pid = (int) $api_pid;
                $pos     = sanitize_text_field( $r['sp_player_pos'] ?? 'MID' );
                $stats   = [
                    'minutes_played' => absint( $r['minutes_played'] ?? 0 ),
                    'goals'          => absint( $r['goals'] ?? 0 ),
                    'assists'        => absint( $r['assists'] ?? 0 ),
                    'clean_sheet'    => empty( $r['clean_sheet'] ) ? 0 : 1,
                    'saves'          => absint( $r['saves'] ?? 0 ),
                    'goals_conceded' => absint( $r['goals_conceded'] ?? 0 ),
                    'yellow_cards'   => absint( $r['yellow_cards'] ?? 0 ),
                    'red_cards'      => absint( $r['red_cards'] ?? 0 ),
                    'own_goals'      => absint( $r['own_goals'] ?? 0 ),
                    'penalty_misses' => absint( $r['penalty_misses'] ?? 0 ),
                    'bonus_points'   => (int) ( $r['bonus_points'] ?? 0 ),
                ];
                $wpdb->replace( $st, array_merge( $stats, [
                    'match_id'        => $match_id,
                    'fixture_id'      => (int) $match->fixture_id,
                    'api_player_id'   => $api_pid,
                    'api_player_name' => sanitize_text_field( $r['api_player_name'] ?? '' ),
                    'api_team_name'   => sanitize_text_field( $r['api_team_name'] ?? '' ),
                    'sp_player_key'   => sanitize_text_field( $r['sp_player_key'] ?? '' ),
                    'sp_player_pos'   => $pos,
                    'points_earned'   => sp_calc_player_points( $stats, $pos ),
                ] ) );
            }
            $wpdb->update( $mt, [ 'scored' => 1 ], [ 'id' => $match_id ] );
            sp_scoring_schedule_batch();
        }
        wp_safe_redirect( admin_url( 'admin.php?page=sp-match-scoring&view=players&match=' . $match_id . '&saved=1' ) );
        exit;
    }

    /* Delete match ----------------------------------------------- */
    if ( isset( $_GET['del_match'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'sp_del_match_' . (int) $_GET['del_match'] ) ) {
        $del = absint( $_GET['del_match'] );
        $wpdb->delete( $st, [ 'match_id' => $del ] );
        $wpdb->delete( $mt, [ 'id' => $del ] );
        wp_safe_redirect( admin_url( 'admin.php?page=sp-match-scoring&deleted=1' ) );
        exit;
    }
} );

/* ── Admin page renderer (output only) ───────────────────────── */
function sp_match_scoring_admin_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $view     = sanitize_key( $_GET['view'] ?? '' );
    $match_id = absint( $_GET['match'] ?? 0 );

    echo '<div class="wrap">';
    if ( $view === 'players' && $match_id ) {
        sp_scoring_render_players_view( $match_id );
    } else {
        sp_scoring_render_match_list();
    }
    echo '</div>';
}

/* ── Match list view ──────────────────────────────────────────── */
function sp_scoring_render_match_list(): void {
    global $wpdb;
    $mt = $wpdb->prefix . 'sp_matches';
    $st = $wpdb->prefix . 'sp_player_stats';

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $matches = $wpdb->get_results( "SELECT m.*,COUNT(ps.id) pc FROM {$mt} m LEFT JOIN {$st} ps ON ps.match_id=m.id GROUP BY m.id ORDER BY m.match_date DESC,m.id DESC" );

    if ( isset( $_GET['added'] ) )   echo '<div class="notice notice-success is-dismissible"><p>Match added — now link player stats below.</p></div>';
    if ( isset( $_GET['deleted'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Match deleted.</p></div>';
    if ( isset( $_GET['saved'] ) )   echo '<div class="notice notice-success is-dismissible"><p>Player stats saved. Scoring job queued.</p></div>';
    ?>
    <h1 class="wp-heading-inline">Match Scoring</h1>
    <hr class="wp-header-end" style="margin-bottom:16px">

    <?php $sp_ms_nations = get_option( 'sb_teams', sb_teams_default() ); ?>
    <div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:20px 24px;margin-bottom:24px">
        <h2 style="margin-top:0">Add Match by Fixture ID</h2>
        <p style="color:#666;margin-top:-8px">Enter the API-Football fixture ID. Use <strong>Fetch from API</strong> to auto-fill team names &amp; score. Or type in the team boxes to filter nations.</p>
        <style>
        .sp-ms-team-label {
            display: flex; flex-direction: column; gap: 4px; font-weight: 600;
            padding: 6px 10px; border: 2px solid transparent; border-radius: 6px;
            margin: -6px -10px; transition: border-color .15s, background .15s;
        }
        .sp-ms-team-label:focus-within {
            border-color: #2271b1;
            background: #f0f6fc;
        }
        </style>
        <form method="post" id="sp-add-match-form">
            <?php wp_nonce_field( 'sp_add_match' ); ?>
            <datalist id="sp-nations-datalist">
                <?php foreach ( $sp_ms_nations as $n ) : ?>
                <option value="<?php echo esc_attr( $n['name'] ); ?>"><?php echo esc_html( ( $n['flag'] ?? '' ) . ' ' . $n['name'] ); ?></option>
                <?php endforeach; ?>
            </datalist>
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
                <label style="display:flex;flex-direction:column;gap:4px;font-weight:600">
                    Fixture ID *
                    <input type="number" name="fixture_id" id="sp-fid-input" class="regular-text" required placeholder="e.g. 1489385" style="width:140px">
                </label>
                <button type="button" id="sp-fetch-fixture-btn" class="button">⬇ Fetch from API</button>
                <label class="sp-ms-team-label">
                    Home Team
                    <input type="text" name="home_team" id="sp-home-team" list="sp-nations-datalist" placeholder="Type to filter…" autocomplete="off" style="width:160px">
                </label>
                <label class="sp-ms-team-label">
                    Away Team
                    <input type="text" name="away_team" id="sp-away-team" list="sp-nations-datalist" placeholder="Type to filter…" autocomplete="off" style="width:160px">
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;font-weight:600">
                    Score
                    <span style="display:flex;gap:4px;align-items:center">
                        <input type="number" name="home_score" id="sp-home-score" min="0" max="99" value="0" style="width:50px">
                        <span style="font-weight:700">–</span>
                        <input type="number" name="away_score" id="sp-away-score" min="0" max="99" value="0" style="width:50px">
                    </span>
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;font-weight:600">
                    Round
                    <input type="text" name="round" id="sp-round" placeholder="Group Stage" style="width:140px">
                </label>
                <label style="display:flex;flex-direction:column;gap:4px;font-weight:600">
                    Date
                    <input type="date" name="match_date" id="sp-match-date">
                </label>
                <button type="submit" name="sp_add_match" class="button button-primary">Add Match</button>
            </div>
        </form>
    </div>

    <h2>Recorded Matches</h2>
    <?php if ( empty( $matches ) ) : ?>
        <p style="color:#888;padding:12px 0">No matches added yet.</p>
    <?php else : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:100px">Fixture ID</th>
                <th>Match</th>
                <th style="width:70px">Score</th>
                <th style="width:120px">Round</th>
                <th style="width:100px">Date</th>
                <th style="width:80px;text-align:center">Players</th>
                <th style="width:90px;text-align:center">Status</th>
                <th style="width:180px">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $matches as $m ) : ?>
            <tr>
                <td style="color:#888;font-family:monospace"><?php echo (int) $m->fixture_id; ?></td>
                <td><strong><?php echo esc_html( $m->home_team ); ?> vs <?php echo esc_html( $m->away_team ); ?></strong></td>
                <td><?php echo (int) $m->home_score; ?>–<?php echo (int) $m->away_score; ?></td>
                <td style="color:#666"><?php echo esc_html( $m->round ); ?></td>
                <td style="color:#666"><?php echo $m->match_date ? esc_html( date_i18n( 'd M Y', strtotime( $m->match_date ) ) ) : '—'; ?></td>
                <td style="text-align:center"><?php echo (int) $m->pc; ?></td>
                <td style="text-align:center">
                    <?php if ( $m->scored ) : ?>
                        <span style="color:#00a32a;font-weight:600">● Scored</span>
                    <?php else : ?>
                        <span style="color:#888">○ Draft</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=sp-match-scoring&view=players&match=' . (int) $m->id ) ); ?>" class="button button-small">Player Stats</a>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=sp-match-scoring&del_match=' . (int) $m->id ), 'sp_del_match_' . (int) $m->id ) ); ?>" class="button button-small" style="color:#a00" onclick="return confirm('Delete this match and all player stats?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php
    $last = get_option( 'sp_scoring_last_run' );
    $next = wp_next_scheduled( 'sp_scoring_batch_job' );
    ?>
    <div style="margin-top:20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <button type="button" id="sp-run-scoring-now" class="button button-primary">▶ Run Scoring Now</button>
        <span id="sp-scoring-status" style="font-size:13px;color:#666">
            Last run: <?php echo $last ? esc_html( date_i18n( 'd M Y H:i', strtotime( $last ) ) ) : 'never'; ?>.
            <?php echo $next ? ' Cron next in ~' . round( ( $next - time() ) / 60 ) . ' min.' : ''; ?>
        </span>
    </div>

    <script>
    (function () {
        var nonce = <?php echo wp_json_encode( wp_create_nonce( 'sp_scoring_nonce' ) ); ?>;
        var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

        document.getElementById('sp-fetch-fixture-btn').addEventListener('click', function () {
            var fid = document.getElementById('sp-fid-input').value.trim();
            if (!fid) { alert('Enter a Fixture ID first.'); return; }
            var btn = this; btn.disabled = true; btn.textContent = 'Fetching…';
            fetch(ajaxUrl + '?action=sp_scoring_fetch_fixture&fixture_id=' + encodeURIComponent(fid) + '&_ajax_nonce=' + encodeURIComponent(nonce))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    btn.disabled = false; btn.textContent = '⬇ Fetch from API';
                    if (!d.success) { alert('API error: ' + (d.data || 'unknown')); return; }
                    var f = d.data;
                    document.getElementById('sp-home-team').value  = f.home;
                    document.getElementById('sp-away-team').value  = f.away;
                    document.getElementById('sp-home-score').value = f.hGoal;
                    document.getElementById('sp-away-score').value = f.aGoal;
                    document.getElementById('sp-round').value      = f.round;
                    document.getElementById('sp-match-date').value = f.date;
                })
                .catch(function () { btn.disabled = false; btn.textContent = '⬇ Fetch from API'; alert('Fetch failed.'); });
        });

        document.getElementById('sp-run-scoring-now').addEventListener('click', function () {
            var btn = this, status = document.getElementById('sp-scoring-status');
            btn.disabled = true; btn.textContent = '⏳ Scoring…';
            status.textContent = 'Running…'; status.style.color = '#666';
            fetch(ajaxUrl + '?action=sp_scoring_run_now&_ajax_nonce=' + encodeURIComponent(nonce))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    btn.disabled = false; btn.textContent = '▶ Run Scoring Now';
                    if (d.success) {
                        var r = d.data;
                        console.group('SP Scoring diagnostic');
                        console.log('Stat keys (wp_sp_player_stats):', r.stat_keys);
                        console.log('Squad keys (from squads):', r.squad_keys);
                        console.log('Matched:', r.matched);
                        console.log('Unmatched:', r.unmatched);
                        console.log('Squad structure debug:', r.squad_debug);
                        console.groupEnd();

                        var matchInfo = r.matched.length
                            ? r.matched.length + '/' + r.squad_keys.length + ' keys matched'
                            : '⚠ 0 keys matched';
                        status.innerHTML = '✓ ' + r.squads_updated + ' squads scored. ' + matchInfo + '.';
                        status.style.color = r.matched.length ? '#00a32a' : '#d63638';

                        // Show inline diagnostic table when nothing matches
                        var old = document.getElementById('sp-diag-box');
                        if (old) old.remove();
                        if (!r.matched.length) {
                            var box = document.createElement('div');
                            box.id = 'sp-diag-box';
                            box.style.cssText = 'margin-top:12px;padding:12px;background:#fff8f8;border:1px solid #d63638;border-radius:4px;font-size:12px;font-family:monospace';
                            box.innerHTML = '<strong>Stat keys in DB (' + r.stat_keys.length + '):</strong><br>'
                                + (r.stat_keys.slice(0,5).join('<br>') || '(none)')
                                + (r.stat_keys.length > 5 ? '<br>…and ' + (r.stat_keys.length-5) + ' more' : '')
                                + '<br><br><strong>Squad keys looked up (' + r.squad_keys.length + '):</strong><br>'
                                + (r.squad_keys.slice(0,5).join('<br>') || '(none)')
                                + (r.squad_keys.length > 5 ? '<br>…and ' + (r.squad_keys.length-5) + ' more' : '')
                                + '<br><br><strong>Squad structure:</strong><br>'
                                + r.squad_debug.join('<br>');
                            btn.parentElement.after(box);
                        }
                    } else {
                        status.textContent = 'Error: ' + (d.data || 'unknown');
                        status.style.color = '#d63638';
                    }
                })
                .catch(function () {
                    btn.disabled = false; btn.textContent = '▶ Run Scoring Now';
                    status.textContent = 'Network error — try again'; status.style.color = '#d63638';
                });
        });
    })();
    </script>
    <?php
}

/* ── Player stats view ────────────────────────────────────────── */
function sp_scoring_render_players_view( int $match_id ): void {
    global $wpdb;
    $mt = $wpdb->prefix . 'sp_matches';
    $st = $wpdb->prefix . 'sp_player_stats';

    $match = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$mt} WHERE id=%d", $match_id ) );
    if ( ! $match ) { echo '<p>Match not found.</p>'; return; }

    // Existing saved stats keyed by api_player_id
    $saved_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$st} WHERE match_id=%d ORDER BY api_team_name,api_player_name", $match_id ) );
    $saved      = [];
    foreach ( $saved_rows as $row ) $saved[ (int) $row->api_player_id ] = $row;

    // All squad players for the mapping dropdown
    $sb_squads   = get_option( 'sb_squads', [] );
    $player_opts = [];
    foreach ( $sb_squads as $nation => $players ) {
        foreach ( $players as $p ) {
            $key   = $nation . '|' . $p['n'];
            $label = '[' . $nation . '] ' . $p['n'] . ' (' . $p['p'] . ')';
            $player_opts[ $key ] = $label;
        }
    }
    asort( $player_opts );

    $home_cs = (int) $match->away_score === 0;
    $away_cs = (int) $match->home_score === 0;

    if ( isset( $_GET['saved'] ) )   echo '<div class="notice notice-success is-dismissible"><p>Player stats saved. Scoring job queued.</p></div>';
    if ( isset( $_GET['added'] ) )   echo '<div class="notice notice-info is-dismissible"><p>Match added. Load player stats from API below.</p></div>';
    ?>
    <h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=sp-match-scoring' ) ); ?>" style="text-decoration:none;color:#1d2327">← Match Scoring</a>
        &rsaquo;
        <?php echo esc_html( $match->home_team . ' ' . (int)$match->home_score . '–' . (int)$match->away_score . ' ' . $match->away_team ); ?>
        <span style="font-size:14px;font-weight:400;color:#888;margin-left:8px"><?php echo esc_html( $match->round ); ?> <?php echo $match->match_date ? '· ' . esc_html( date_i18n( 'd M Y', strtotime( $match->match_date ) ) ) : ''; ?></span>
    </h1>
    <hr class="wp-header-end" style="margin-bottom:16px">

    <div style="display:flex;gap:10px;margin-bottom:12px;align-items:center;flex-wrap:wrap">
        <button type="button" id="sp-load-api-players" class="button button-primary">⬇ Load / Refresh Player Stats from API</button>
        <span id="sp-api-status" style="color:#666;font-size:13px"></span>
    </div>

    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:10px 14px;background:#f6f7f7;border:1px solid #ddd;border-radius:4px;flex-wrap:wrap">
        <span style="font-size:12px;font-weight:600;color:#444;margin-right:4px">Filter squad map by nation:</span>
        <button type="button" class="button button-small sp-nation-filter active" data-nation="all">All Nations</button>
        <button type="button" class="button button-small sp-nation-filter" data-nation="<?php echo esc_attr( $match->home_team ); ?>"><?php echo esc_html( $match->home_team ); ?> (Home)</button>
        <button type="button" class="button button-small sp-nation-filter" data-nation="<?php echo esc_attr( $match->away_team ); ?>"><?php echo esc_html( $match->away_team ); ?> (Away)</button>
        <span style="font-size:11px;color:#888;margin-left:4px">Filters the options in the "Map to Squad Player" column</span>
    </div>

    <style>
    .sp-nation-filter.active { background:#0073aa!important;border-color:#0073aa!important;color:#fff!important; }
    #sp-player-tbody tr.sp-row-active { outline:2px solid #2271b1; outline-offset:-2px; background:#f0f6fc!important; }
    </style>

    <form method="post" id="sp-player-stats-form">
        <?php wp_nonce_field( 'sp_save_ps_' . $match_id ); ?>
        <div style="overflow-x:auto">
        <table class="wp-list-table widefat fixed" id="sp-player-table">
            <thead>
                <tr>
                    <th style="width:170px">API Player</th>
                    <th style="width:100px">Team</th>
                    <th style="width:70px">Pos</th>
                    <th style="width:220px">Map to Squad Player</th>
                    <th style="width:58px" title="Minutes Played">Min</th>
                    <th style="width:48px" title="Goals">G</th>
                    <th style="width:48px" title="Assists">A</th>
                    <th style="width:44px" title="Clean Sheet">CS</th>
                    <th style="width:48px" title="Saves (GK)">Sv</th>
                    <th style="width:48px" title="Goals Conceded">GC</th>
                    <th style="width:48px" title="Yellow Cards">YC</th>
                    <th style="width:48px" title="Red Cards">RC</th>
                    <th style="width:48px" title="Own Goals">OG</th>
                    <th style="width:48px" title="Penalty Misses">PM</th>
                    <th style="width:48px" title="Bonus Points">BP</th>
                    <th style="width:50px" title="Points Earned">Pts</th>
                </tr>
            </thead>
            <tbody id="sp-player-tbody">
            <?php foreach ( $saved as $api_pid => $row ) : ?>
                <?php sp_scoring_render_player_row( $api_pid, $row->api_player_name, $row->api_team_name, $row, $player_opts ); ?>
            <?php endforeach; ?>
            <?php if ( empty( $saved ) ) : ?>
                <tr id="sp-empty-row"><td colspan="16" style="color:#888;padding:20px;text-align:center">No player stats yet. Click "Load / Refresh Player Stats from API" above.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>

        <?php if ( ! empty( $saved ) ) : ?>
        <div style="margin-top:16px;display:flex;gap:10px">
            <button type="submit" name="sp_save_player_stats" class="button button-primary button-large">Save Player Mappings &amp; Stats</button>
        </div>
        <?php endif; ?>
    </form>

    <script>
    (function () {
        var nonce    = <?php echo wp_json_encode( wp_create_nonce( 'sp_scoring_nonce' ) ); ?>;
        var ajaxUrl  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
        var fixId    = <?php echo (int) $match->fixture_id; ?>;
        var homeTeam = <?php echo wp_json_encode( $match->home_team ); ?>;
        var awayTeam = <?php echo wp_json_encode( $match->away_team ); ?>;
        var homeCs   = <?php echo (int) $home_cs; ?>;
        var awayCs   = <?php echo (int) $away_cs; ?>;
        var homeGc   = <?php echo (int) $match->away_score; ?>;
        var awayGc   = <?php echo (int) $match->home_score; ?>;

        // Player options for the Map-to dropdown
        var playerOpts = <?php echo wp_json_encode( $player_opts ); ?>;

        function calcPts(row) {
            if (!row.querySelector('.sp-pos-sel')) return;
            var pos   = row.querySelector('.sp-pos-sel').value.toUpperCase();
            var min   = parseInt(row.querySelector('.sp-min').value) || 0;
            var goals = parseInt(row.querySelector('.sp-goals').value) || 0;
            var ast   = parseInt(row.querySelector('.sp-ast').value) || 0;
            var cs    = row.querySelector('.sp-cs').checked ? 1 : 0;
            var sv    = parseInt(row.querySelector('.sp-sv').value) || 0;
            var gc    = parseInt(row.querySelector('.sp-gc').value) || 0;
            var yc    = parseInt(row.querySelector('.sp-yc').value) || 0;
            var rc    = parseInt(row.querySelector('.sp-rc').value) || 0;
            var og    = parseInt(row.querySelector('.sp-og').value) || 0;
            var pm    = parseInt(row.querySelector('.sp-pm').value) || 0;
            var bp    = parseInt(row.querySelector('.sp-bp').value) || 0;
            var p = 0;
            if (min > 0) { p += 1; if (min >= 60) p += 1; }
            var gm = (pos==='GK'||pos==='DEF') ? 6 : 5;
            p += goals * gm;
            p += ast * 3;
            if (cs) { if (pos==='GK'||pos==='DEF') p += 3; else if (pos==='MID') p += 1; }
            if (pos==='GK') p += Math.floor(sv / 3);
            if ((pos==='GK'||pos==='DEF') && gc>=2) p -= Math.floor(gc / 2);
            p -= yc; p -= rc*3; p -= og*2; p -= pm*2; p += bp;
            row.querySelector('.sp-pts').textContent = p;
            row.querySelector('.sp-pts').style.color = p < 0 ? '#d63638' : p > 0 ? '#00a32a' : '#888';
        }

        function buildRow(player, existingKey, existingPos, cs, gc) {
            var tr  = document.createElement('tr');
            var pid = player.id;

            // Auto-map if no existing key was provided
            var mappedKey = existingKey || autoMap(player.name, player.team);
            // If auto-mapped, derive position from the option label
            var resolvedPos = existingPos || player.pos;
            if (mappedKey && !existingPos) {
                var lbl = playerOpts[mappedKey] || '';
                var pm  = lbl.match(/\((\w+)\)$/);
                if (pm) resolvedPos = pm[1];
            }

            var teamLower   = player.team.toLowerCase();
            var teamKeys    = Object.keys(playerOpts).filter(function(k){ return k.split('|')[0].toLowerCase() === teamLower; });
            var displayKeys = teamKeys.length ? teamKeys : Object.keys(playerOpts); // fallback: all if team not found
            displayKeys.sort(function(a,b){ return playerOpts[a].localeCompare(playerOpts[b]); });

            var mapSel = '<select name="player['+pid+'][sp_player_key]" class="sp-map-sel" style="max-width:200px;font-size:12px">'
                + '<option value="">— Not mapped —</option>';
            if (!teamKeys.length) {
                mapSel += '<option disabled style="color:#d63638">⚠ No team match — showing all</option>';
            }
            displayKeys.forEach(function(k){
                // Strip the "nation|" prefix from the label when team is filtered — show just "Name (POS)"
                var label = teamKeys.length
                    ? escHtml(playerOpts[k].replace(/^\[[^\]]+\]\s*/, ''))
                    : escHtml(playerOpts[k]);
                mapSel += '<option value="'+escAttr(k)+'"'+(k===mappedKey?' selected':'')+'>'+label+'</option>';
            });
            mapSel += '</select>';

            var posSel = '<select name="player['+pid+'][sp_player_pos]" class="sp-pos-sel" style="width:60px;font-size:12px">';
            ['GK','DEF','MID','FWD'].forEach(function(p){
                posSel += '<option'+(p===resolvedPos?' selected':'')+'>'+p+'</option>';
            });
            posSel += '</select>';

            tr.innerHTML = [
                '<td><strong>'+escHtml(player.name)+'</strong>'
                    +(mappedKey ? '<br><small style="color:#00a32a;font-size:10px">✓ auto-mapped</small>' : '')
                    +'<input type="hidden" name="player['+pid+'][api_player_name]" value="'+escAttr(player.name)+'">'
                    +'<input type="hidden" name="player['+pid+'][api_team_name]" value="'+escAttr(player.team)+'">'
                    +'</td>',
                '<td style="font-size:12px;color:#666">'+escHtml(player.team)+'</td>',
                '<td>'+posSel+'</td>',
                '<td>'+mapSel+'</td>',
                '<td><input type="number" name="player['+pid+'][minutes_played]" value="'+player.min+'" min="0" max="120" class="sp-min" style="width:52px"></td>',
                '<td><input type="number" name="player['+pid+'][goals]" value="'+player.goals+'" min="0" max="20" class="sp-goals" style="width:42px"></td>',
                '<td><input type="number" name="player['+pid+'][assists]" value="'+player.assists+'" min="0" max="20" class="sp-ast" style="width:42px"></td>',
                '<td style="text-align:center"><input type="checkbox" name="player['+pid+'][clean_sheet]" class="sp-cs" value="1"'+(cs?' checked':'')+'></td>',
                '<td><input type="number" name="player['+pid+'][saves]" value="'+player.saves+'" min="0" max="30" class="sp-sv" style="width:42px"></td>',
                '<td><input type="number" name="player['+pid+'][goals_conceded]" value="'+gc+'" min="0" max="20" class="sp-gc" style="width:42px"></td>',
                '<td><input type="number" name="player['+pid+'][yellow_cards]" value="'+player.yc+'" min="0" max="2" class="sp-yc" style="width:42px"></td>',
                '<td><input type="number" name="player['+pid+'][red_cards]" value="'+player.rc+'" min="0" max="1" class="sp-rc" style="width:42px"></td>',
                '<td><input type="number" name="player['+pid+'][own_goals]" value="0" min="0" max="5" class="sp-og" style="width:42px"></td>',
                '<td><input type="number" name="player['+pid+'][penalty_misses]" value="'+player.pm+'" min="0" max="5" class="sp-pm" style="width:42px"></td>',
                '<td><input type="number" name="player['+pid+'][bonus_points]" value="0" min="0" max="10" class="sp-bp" style="width:42px"></td>',
                '<td class="sp-pts" style="font-weight:700;text-align:right">0</td>',
            ].join('');
            return tr;
        }

        function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
        function escAttr(s){ return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;'); }

        // Auto-map an API player to a squad player key.
        // 1. Exact match on "nation|name"
        // 2. Case-insensitive exact match
        // 3. Unambiguous last-name match within the same team
        // Returns '' if no confident match found.
        function autoMap(apiName, apiTeam) {
            var exactKey = apiTeam + '|' + apiName;
            if (playerOpts[exactKey] !== undefined) return exactKey;

            var teamLower = apiTeam.toLowerCase();
            var nameLower = apiName.toLowerCase();

            // Case-insensitive exact
            for (var k in playerOpts) {
                var kp = k.split('|');
                if (kp[0].toLowerCase() === teamLower && kp.slice(1).join('|').toLowerCase() === nameLower) return k;
            }

            // Last-name fallback within same team
            var apiParts = apiName.trim().split(/\s+/);
            var apiLast  = apiParts[apiParts.length - 1].toLowerCase();
            var hits = [];
            for (var k in playerOpts) {
                var kp = k.split('|');
                if (kp[0].toLowerCase() !== teamLower) continue;
                var spParts = kp.slice(1).join('|').trim().split(/\s+/);
                var spLast  = spParts[spParts.length - 1].toLowerCase();
                if (spLast === apiLast) hits.push(k);
            }
            return hits.length === 1 ? hits[0] : '';
        }

        function wireRow(tr) {
            if (!tr.querySelector('.sp-map-sel')) return;
            tr.querySelectorAll('input,select').forEach(function(el){
                el.addEventListener('input', function(){ calcPts(tr); });
                el.addEventListener('change', function(){ calcPts(tr); });
            });
            // When Map-to changes, auto-set position from the option label
            tr.querySelector('.sp-map-sel').addEventListener('change', function(){
                var key = this.value;
                if (!key) return;
                var label = playerOpts[key] || '';
                var m = label.match(/\((\w+)\)$/);
                if (m) { tr.querySelector('.sp-pos-sel').value = m[1]; calcPts(tr); }
            });
            calcPts(tr);
        }

        // Wire existing rows
        document.querySelectorAll('#sp-player-tbody tr').forEach(wireRow);

        // Recalculate all on form load
        document.querySelectorAll('#sp-player-tbody tr').forEach(calcPts);

        /* ── Nation filter ── */
        var currentNationFilter = 'all';

        function applyNationFilter(nation) {
            currentNationFilter = nation;
            document.querySelectorAll('#sp-player-tbody tr[data-pid]').forEach(function (tr) {
                var sel = tr.querySelector('.sp-map-sel');
                if (!sel) return;
                // Rebuild options scoped to the chosen nation
                var teamLower = nation === 'all' ? null : nation.toLowerCase();
                var keys = Object.keys(playerOpts);
                var filtered = teamLower
                    ? keys.filter(function (k) { return k.split('|')[0].toLowerCase() === teamLower; })
                    : keys;
                if (!filtered.length) filtered = keys; // fallback: show all if nation not in squad list
                filtered.sort(function (a, b) { return playerOpts[a].localeCompare(playerOpts[b]); });

                var cur = sel.value;
                var opts = '<option value="">— Not mapped —</option>';
                if (teamLower && !keys.some(function (k) { return k.split('|')[0].toLowerCase() === teamLower; })) {
                    opts += '<option disabled style="color:#d63638">⚠ Nation not in squad list</option>';
                }
                filtered.forEach(function (k) {
                    var label = teamLower
                        ? playerOpts[k].replace(/^\[[^\]]+\]\s*/, '')
                        : playerOpts[k];
                    opts += '<option value="' + escAttr(k) + '"' + (k === cur ? ' selected' : '') + '>' + escHtml(label) + '</option>';
                });
                sel.innerHTML = opts;
            });
        }

        document.querySelectorAll('.sp-nation-filter').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.sp-nation-filter').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                applyNationFilter(btn.dataset.nation);
            });
        });

        /* ── Row highlight on Map dropdown focus ── */
        document.getElementById('sp-player-tbody').addEventListener('focusin', function (e) {
            if (!e.target.classList.contains('sp-map-sel')) return;
            var tr = e.target.closest('tr');
            if (tr) tr.classList.add('sp-row-active');
        });
        document.getElementById('sp-player-tbody').addEventListener('focusout', function (e) {
            if (!e.target.classList.contains('sp-map-sel')) return;
            var tr = e.target.closest('tr');
            if (tr) tr.classList.remove('sp-row-active');
        });

        document.getElementById('sp-load-api-players').addEventListener('click', function(){
            var btn = this, status = document.getElementById('sp-api-status');
            btn.disabled = true; btn.textContent = 'Loading…'; status.textContent = '';
            fetch(ajaxUrl+'?action=sp_scoring_fetch_players&fixture_id='+fixId+'&_ajax_nonce='+encodeURIComponent(nonce))
                .then(function(r){ return r.json(); })
                .then(function(d){
                    btn.disabled = false; btn.textContent = '⬇ Load / Refresh Player Stats from API';
                    if (!d.success){ status.textContent = 'Error: '+(d.data||'unknown'); status.style.color='#d63638'; return; }
                    var players = d.data;
                    status.textContent = 'Loaded '+players.length+' players. Review mappings & save.';
                    status.style.color = '#00a32a';

                    var tbody = document.getElementById('sp-player-tbody');
                    var empty = document.getElementById('sp-empty-row');
                    if (empty) empty.remove();

                    // Build index of existing rows by api_player_id
                    var existingRows = {};
                    tbody.querySelectorAll('tr[data-pid]').forEach(function(tr){
                        existingRows[tr.dataset.pid] = tr;
                    });

                    players.forEach(function(player){
                        var isHome = player.team === homeTeam;
                        var cs     = isHome ? homeCs : awayCs;
                        var gc     = isHome ? homeGc : awayGc;

                        if (existingRows[player.id]) {
                            // Row already exists — don't overwrite, just re-calc
                            calcPts(existingRows[player.id]);
                        } else {
                            var tr = buildRow(player, '', player.pos, cs, gc);
                            tr.dataset.pid = player.id;
                            tbody.appendChild(tr);
                            wireRow(tr);
                        }
                    });

                    // Re-apply any active nation filter to the freshly built rows
                    if (currentNationFilter !== 'all') applyNationFilter(currentNationFilter);

                    // Show the save button
                    var formEl = document.getElementById('sp-player-stats-form');
                    if (!formEl.querySelector('[name=sp_save_player_stats]')) {
                        var div = document.createElement('div');
                        div.style.cssText = 'margin-top:16px;display:flex;gap:10px';
                        div.innerHTML = '<button type="submit" name="sp_save_player_stats" class="button button-primary button-large">Save Player Mappings &amp; Stats</button>';
                        formEl.appendChild(div);
                    }
                })
                .catch(function(){ btn.disabled=false; btn.textContent='⬇ Load / Refresh Player Stats from API'; status.textContent='Network error'; status.style.color='#d63638'; });
        });
    })();
    </script>
    <?php
}

/* ── Render a single player row (for saved stats) ─────────────── */
function sp_scoring_render_player_row( int $api_pid, string $api_name, string $api_team, object $row, array $player_opts ): void {
    $pos_options = [ 'GK', 'DEF', 'MID', 'FWD' ];
    $pts = sp_calc_player_points( (array) $row, $row->sp_player_pos );
    ?>
    <tr data-pid="<?php echo $api_pid; ?>">
        <td>
            <strong><?php echo esc_html( $api_name ); ?></strong>
            <input type="hidden" name="player[<?php echo $api_pid; ?>][api_player_name]" value="<?php echo esc_attr( $api_name ); ?>">
            <input type="hidden" name="player[<?php echo $api_pid; ?>][api_team_name]" value="<?php echo esc_attr( $api_team ); ?>">
        </td>
        <td style="font-size:12px;color:#666"><?php echo esc_html( $api_team ); ?></td>
        <td>
            <select name="player[<?php echo $api_pid; ?>][sp_player_pos]" class="sp-pos-sel" style="width:60px;font-size:12px">
                <?php foreach ( $pos_options as $p ) : ?>
                    <option<?php selected( $row->sp_player_pos, $p ); ?>><?php echo esc_html( $p ); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <?php
            // Filter to the same team; fall back to all if no team match exists
            $team_opts = array_filter( $player_opts, fn( $k ) => strtolower( explode( '|', $k, 2 )[0] ) === strtolower( $api_team ), ARRAY_FILTER_USE_KEY );
            $show_opts = $team_opts ?: $player_opts;
            $no_match  = empty( $team_opts );
            ?>
            <select name="player[<?php echo $api_pid; ?>][sp_player_key]" class="sp-map-sel" style="max-width:200px;font-size:12px">
                <option value="">— Not mapped —</option>
                <?php if ( $no_match ) : ?>
                    <option disabled style="color:#d63638">⚠ No team match — showing all</option>
                <?php endif; ?>
                <?php foreach ( $show_opts as $key => $label ) :
                    // Strip "nation|" prefix when filtered to one team
                    $display = $no_match ? $label : preg_replace( '/^\[[^\]]+\]\s*/', '', $label );
                ?>
                    <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $row->sp_player_key, $key ); ?>><?php echo esc_html( $display ); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <?php
        $fields = [
            [ 'minutes_played', 'sp-min',   'number', 0, 120 ],
            [ 'goals',          'sp-goals',  'number', 0, 20 ],
            [ 'assists',        'sp-ast',    'number', 0, 20 ],
        ];
        foreach ( $fields as $f ) : ?>
        <td><input type="number" name="player[<?php echo $api_pid; ?>][<?php echo esc_attr($f[0]); ?>]" value="<?php echo (int) $row->{ $f[0] }; ?>" min="<?php echo $f[3]; ?>" max="<?php echo $f[4]; ?>" class="<?php echo esc_attr($f[1]); ?>" style="width:52px"></td>
        <?php endforeach; ?>
        <td style="text-align:center"><input type="checkbox" name="player[<?php echo $api_pid; ?>][clean_sheet]" class="sp-cs" value="1"<?php checked( $row->clean_sheet, 1 ); ?>></td>
        <?php
        $num_fields = [
            [ 'saves',          'sp-sv', 30 ],
            [ 'goals_conceded', 'sp-gc', 20 ],
            [ 'yellow_cards',   'sp-yc', 2 ],
            [ 'red_cards',      'sp-rc', 1 ],
            [ 'own_goals',      'sp-og', 5 ],
            [ 'penalty_misses', 'sp-pm', 5 ],
            [ 'bonus_points',   'sp-bp', 10 ],
        ];
        foreach ( $num_fields as $f ) : ?>
        <td><input type="number" name="player[<?php echo $api_pid; ?>][<?php echo esc_attr($f[0]); ?>]" value="<?php echo (int) $row->{ $f[0] }; ?>" min="0" max="<?php echo (int) $f[2]; ?>" class="<?php echo esc_attr($f[1]); ?>" style="width:42px"></td>
        <?php endforeach; ?>
        <td class="sp-pts" style="font-weight:700;text-align:right;color:<?php echo $pts < 0 ? '#d63638' : ( $pts > 0 ? '#00a32a' : '#888' ); ?>"><?php echo $pts; ?></td>
    </tr>
    <?php
}

/* Scoring runs on-demand only (when stats are saved or via "Run Scoring Now").
   No periodic cron needed — stats only change when an admin manually enters them. */

/* ── Core scoring function — recalculates total_points for every squad ── */
function sp_scoring_score_all_squads(): array {
    global $wpdb;
    $squad_t = $wpdb->prefix . 'sp_user_squads';
    $stats_t = $wpdb->prefix . 'sp_player_stats';

    // Build a complete lookup: sp_player_key → total points_earned across all matches
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    $all_stats = $wpdb->get_results(
        "SELECT sp_player_key, SUM(points_earned) AS total FROM {$stats_t} WHERE sp_player_key != '' GROUP BY sp_player_key",
        ARRAY_A
    );
    $pts_by_key = [];
    foreach ( $all_stats as $r ) {
        $pts_by_key[ $r['sp_player_key'] ] = (int) $r['total'];
    }

    // Fetch and score squads in batches to avoid memory issues
    $limit      = 50;
    $offset     = 0;
    $updated    = 0;

    do {
        $squads = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, nation, squad_data FROM {$squad_t} LIMIT %d OFFSET %d",
            $limit, $offset
        ) );

        foreach ( $squads as $squad ) {
            $slots = json_decode( $squad->squad_data, true );
            if ( ! is_array( $slots ) ) continue;

            $total = 0;
            foreach ( $slots as $slot ) {
                $pname = $slot['player']['n'] ?? '';
                if ( ! $pname ) continue;
                $key    = $squad->nation . '|' . $pname;
                $total += $pts_by_key[ $key ] ?? 0;
            }

            $wpdb->update( $squad_t, [ 'total_points' => max( 0, $total ) ], [ 'id' => (int) $squad->id ] );
            $updated++;
        }

        $offset += $limit;
    } while ( count( $squads ) === $limit );

    update_option( 'sp_scoring_last_run', current_time( 'mysql' ), false );
    return [ 'squads_updated' => $updated, 'player_keys' => count( $pts_by_key ) ];
}

/* ── Admin AJAX — run scoring now ─────────────────────────────── */
add_action( 'wp_ajax_sp_scoring_run_now', function () {
    check_ajax_referer( 'sp_scoring_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

    global $wpdb;
    $squad_t = $wpdb->prefix . 'sp_user_squads';
    $stats_t = $wpdb->prefix . 'sp_player_stats';

    // Diagnostic: keys that have stats
    $stat_keys = $wpdb->get_col( "SELECT DISTINCT sp_player_key FROM {$stats_t} WHERE sp_player_key != ''" );

    // Diagnostic: raw squad data to catch encoding issues
    $squads     = $wpdb->get_results( "SELECT id, nation, squad_data FROM {$squad_t}" );
    $squad_keys = [];
    $squad_debug = [];
    foreach ( $squads as $sq ) {
        $raw   = $sq->squad_data;
        $slots = json_decode( $raw, true );
        // Detect double-encoding: if json_decode returns a string, it was encoded twice
        if ( is_string( $slots ) ) {
            $slots = json_decode( $slots, true );
            $squad_debug[] = 'Squad #' . $sq->id . ': DOUBLE-ENCODED — fixed by decoding twice';
        }
        if ( ! is_array( $slots ) ) {
            $squad_debug[] = 'Squad #' . $sq->id . ': squad_data is not an array after decode (type=' . gettype( $slots ) . ') raw=' . substr( $raw, 0, 80 );
            continue;
        }
        foreach ( $slots as $slot ) {
            $pname = $slot['player']['n'] ?? ( $slot['n'] ?? '' ); // handle flat structure too
            if ( $pname ) $squad_keys[] = $sq->nation . '|' . $pname;
        }
        $squad_debug[] = 'Squad #' . $sq->id . ' (' . $sq->nation . '): ' . count( $slots ) . ' slots, sample key=' . ( $squad_keys ? end( $squad_keys ) : 'none' );
    }
    $squad_keys = array_unique( $squad_keys );

    $matched   = array_intersect( $squad_keys, $stat_keys );
    $unmatched = array_diff( $squad_keys, $stat_keys );

    $result = sp_scoring_score_all_squads();
    $result['stat_keys']   = array_values( $stat_keys );
    $result['squad_keys']  = array_values( $squad_keys );
    $result['matched']     = array_values( $matched );
    $result['unmatched']   = array_values( $unmatched );
    $result['squad_debug'] = $squad_debug;

    wp_send_json_success( $result );
} );

/* ── Trigger scoring immediately when stats are saved ─────────── */
function sp_scoring_schedule_batch(): void {
    sp_scoring_score_all_squads();
}
