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
    wp_enqueue_style( 'smallpoles-style', get_stylesheet_uri(), [], '1.0.3' );
    wp_enqueue_script( 'smallpoles-main', get_template_directory_uri() . '/assets/js/main.js', [], '1.0.2', true );
    wp_localize_script( 'smallpoles-main', 'spData', [
        'restBase' => esc_url_raw( rest_url( 'smallpoles/v1/' ) ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ] );

    $game = get_query_var( 'smallpoles_game' );
    if ( $game === 'polele' ) {
        wp_enqueue_script( 'sp-polele', get_template_directory_uri() . '/assets/js/polele.js', [], '1.0.0', true );
    }
    if ( $game === 'bracket' ) {
        wp_enqueue_script( 'sp-bracket', get_template_directory_uri() . '/assets/js/bracket.js', [], '1.0.0', true );
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
        wp_enqueue_script( 'sp-squad', get_template_directory_uri() . '/assets/js/squad-builder.js', [], '2.0.0', true );
        wp_localize_script( 'sp-squad', 'spSquadData', [
            'squads' => $sb_squads,
            'flags'  => $sb_flags,
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

/* ── SEO: meta description, Open Graph, Twitter Card, canonical ── */
function smallpoles_seo_meta() {
    $og_image = get_template_directory_uri() . '/assets/og.webp';

    $game = get_query_var( 'smallpoles_game' );

    $game_meta = [
        'hub' => [
            'title' => 'World Cup 2026 Games — Small Poles',
            'desc'  => 'Free World Cup 2026 mini-games. Guess the player, pick your bracket, build your dream squad, and play Higher or Lower with real player stats.',
            'url'   => home_url( '/games/' ),
        ],
        'higher-lower' => [
            'title' => 'Higher or Lower — World Cup Player Stats | Small Poles',
            'desc'  => 'Does this World Cup player have more or fewer goals, caps, or years than the other? One stat, two players — keep your streak alive.',
            'url'   => home_url( '/games/higher-lower/' ),
        ],
        'polele' => [
            'title' => 'Polele — Guess the World Cup Player | Small Poles',
            'desc'  => 'Guess today\'s mystery World Cup 2026 player in 6 attempts. Colour-coded clues reveal their nation, club, position, age, and league after every guess.',
            'url'   => home_url( '/games/polele/' ),
        ],
        'bracket' => [
            'title' => 'World Cup 2026 Bracket Challenge | Small Poles',
            'desc'  => 'Pick every winner from the 2026 World Cup Group Stage to the Final. Share your bracket and see how many you called right.',
            'url'   => home_url( '/games/bracket/' ),
        ],
        'squad' => [
            'title' => 'World Cup 2026 Squad Builder | Small Poles',
            'desc'  => 'Build your ultimate World Cup XI. Choose from 10 nations, pick a formation, and stay within budget. Share your dream squad.',
            'url'   => home_url( '/games/squad/' ),
        ],
    ];

    if ( $game && isset( $game_meta[ $game ] ) ) {
        $title       = $game_meta[ $game ]['title'];
        $description = $game_meta[ $game ]['desc'];
        $url         = $game_meta[ $game ]['url'];
    } elseif ( is_front_page() ) {
        $title       = 'Small Poles — GPL Fantasy Football';
        $description = 'The first fantasy football platform built for the Ghana Premier League. Pick real GPL players, predict results, and compete with your people every gameweek.';
        $url         = home_url( '/' );
    } elseif ( is_single() ) {
        $title       = get_the_title() . ' — Small Poles';
        $description = wp_strip_all_tags( get_the_excerpt() );
        $url         = get_permalink();
        if ( has_post_thumbnail() ) {
            $og_image = get_the_post_thumbnail_url( null, 'large' );
        }
    } else {
        $title       = 'Small Poles — GPL Fantasy Football';
        $description = 'GPL Fantasy Football for Ghanaian fans.';
        $url         = home_url( add_query_arg( [] ) );
    }
    ?>
<meta name="description" content="<?php echo esc_attr( $description ); ?>" />
<link rel="canonical" href="<?php echo esc_url( $url ); ?>" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Small Poles" />
<meta property="og:title" content="<?php echo esc_attr( $title ); ?>" />
<meta property="og:description" content="<?php echo esc_attr( $description ); ?>" />
<meta property="og:url" content="<?php echo esc_url( $url ); ?>" />
<meta property="og:image" content="<?php echo esc_url( $og_image ); ?>" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>" />
<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>" />
<meta name="twitter:image" content="<?php echo esc_url( $og_image ); ?>" />
    <?php
}
add_action( 'wp_head', 'smallpoles_seo_meta', 2 );

/* ── SEO: JSON-LD structured data ── */
function smallpoles_schema() {
    $game = get_query_var( 'smallpoles_game' );

    if ( $game ) {
        $game_names = [
            'hub'          => 'World Cup 2026 Games',
            'higher-lower' => 'Higher or Lower — World Cup Player Stats',
            'polele'       => 'Polele — Guess the World Cup Player',
            'bracket'      => 'World Cup 2026 Bracket Challenge',
            'squad'        => 'World Cup 2026 Squad Builder',
        ];
        $game_descs = [
            'hub'          => 'Free World Cup 2026 mini-games including Higher or Lower, Polele, Bracket Challenge, and Squad Builder.',
            'higher-lower' => 'Guess whether a World Cup player has more or fewer goals, caps, or age than another player. Keep your streak alive.',
            'polele'       => 'Daily World Cup player guessing game. Colour-coded clues after every wrong guess.',
            'bracket'      => 'Pick every winner of the 2026 World Cup from Group Stage to Final.',
            'squad'        => 'Build and share your ultimate World Cup XI with a budget and formation.',
        ];
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebApplication',
            'name'        => $game_names[ $game ] ?? 'Small Poles Game',
            'description' => $game_descs[ $game ] ?? '',
            'url'         => home_url( '/games/' . ( $game === 'hub' ? '' : $game . '/' ) ),
            'applicationCategory' => 'Game',
            'operatingSystem'     => 'Web',
            'offers'              => [ '@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD' ],
        ];
        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        return;
    }

    if ( is_front_page() ) {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebSite',
            'name'        => 'Small Poles',
            'description' => 'The first fantasy football platform built for the Ghana Premier League.',
            'url'         => home_url( '/' ),
        ];
    } elseif ( is_single() ) {
        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => get_the_title(),
            'datePublished' => get_the_date( 'c' ),
            'dateModified'  => get_the_modified_date( 'c' ),
            'url'           => get_permalink(),
            'author'        => [ '@type' => 'Organization', 'name' => 'Small Poles' ],
            'publisher'     => [
                '@type' => 'Organization',
                'name'  => 'Small Poles',
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => get_template_directory_uri() . '/assets/smallpolesappicon.png',
                ],
            ],
        ];
        if ( has_post_thumbnail() ) {
            $schema['image'] = get_the_post_thumbnail_url( null, 'large' );
        }
    } else {
        return;
    }
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'smallpoles_schema', 3 );


/* ── SEO: Add game pages to Yoast sitemap index ── */
add_filter( 'wpseo_sitemap_index', function( $index ) {
    $index .= sprintf(
        "<sitemap>\n<loc>%s</loc>\n<lastmod>%s</lastmod>\n</sitemap>\n",
        esc_url( home_url( '/games-sitemap.xml' ) ),
        gmdate( 'Y-m-d' )
    );
    return $index;
} );

add_action( 'template_redirect', function() {
    if ( ! preg_match( '#^/games-sitemap\.xml$#', $_SERVER['REQUEST_URI'] ?? '' ) ) return;

    header( 'Content-Type: application/xml; charset=UTF-8' );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/wp-content/plugins/wordpress-seo/css/main-sitemap.xsl' ) ) . '"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $games = [
        [ 'loc' => home_url( '/games/' ),              'priority' => '0.9', 'changefreq' => 'weekly' ],
        [ 'loc' => home_url( '/games/higher-lower/' ), 'priority' => '0.8', 'changefreq' => 'weekly' ],
        [ 'loc' => home_url( '/games/polele/' ),       'priority' => '0.8', 'changefreq' => 'daily'  ],
        [ 'loc' => home_url( '/games/bracket/' ),      'priority' => '0.8', 'changefreq' => 'weekly' ],
        [ 'loc' => home_url( '/games/squad/' ),        'priority' => '0.8', 'changefreq' => 'weekly' ],
    ];

    foreach ( $games as $url ) {
        echo "  <url>\n";
        echo '    <loc>' . esc_url( $url['loc'] ) . "</loc>\n";
        echo '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
        echo '    <priority>' . $url['priority'] . "</priority>\n";
        echo "  </url>\n";
    }

    echo '</urlset>';
    exit;
} );

/* ── Google Analytics 4 ── */
function smallpoles_google_analytics() {
    if ( is_admin() ) return;
    $measurement_id = 'G-XSJ3VSCVSS'; // Replace with your real Measurement ID
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $measurement_id ); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo esc_js( $measurement_id ); ?>');
    </script>
    <?php
}
add_action( 'wp_head', 'smallpoles_google_analytics', 1 );


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
        <p style="color:#666;margin-bottom:16px">Test every endpoint directly from here — responses appear below.</p>

        <table class="form-table" style="max-width:700px">
            <tr>
                <th scope="row"><label for="sp_fixture_test">Fixture ID</label></th>
                <td>
                    <input type="number" id="sp_fixture_test" value="1489385" class="small-text" placeholder="e.g. 1489385" />
                    <p class="description">Used by predictions, lineups, stats, and odds endpoints.</p>
                </td>
            </tr>
        </table>

        <div style="display:flex;flex-wrap:wrap;gap:8px;margin:16px 0">
            <button class="button" data-endpoint="next-fixture">Next Fixture</button>
            <button class="button" data-endpoint="standings">Standings</button>
            <button class="button" data-endpoint="fixtures">All Fixtures</button>
            <button class="button" data-endpoint="rounds">Rounds</button>
            <button class="button" data-endpoint="predictions">Predictions</button>
            <button class="button" data-endpoint="lineups">Lineups</button>
            <button class="button" data-endpoint="fixture-stats">Match Stats</button>
            <button class="button" data-endpoint="odds">Pre-match Odds</button>
            <button class="button" data-endpoint="odds-live">Live Odds</button>
        </div>

        <div id="sp-test-status" style="margin-bottom:8px;font-weight:600;color:#0073aa;display:none"></div>
        <pre id="sp-test-output" style="background:#1e1e1e;color:#d4d4d4;padding:20px;border-radius:6px;max-height:500px;overflow:auto;font-size:12px;line-height:1.6;display:none;white-space:pre-wrap;word-break:break-all"></pre>

        <script>
        (function() {
            const base  = '<?php echo esc_js( $base ); ?>';
            const nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
            const fixtureInput = document.getElementById('sp_fixture_test');
            const status = document.getElementById('sp-test-status');
            const output = document.getElementById('sp-test-output');

            const fixtureEndpoints = ['predictions','lineups','fixture-stats','odds','odds-live'];

            document.querySelectorAll('[data-endpoint]').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const ep = this.dataset.endpoint;
                    const fid = fixtureInput.value.trim();
                    let url = base + '/' + ep;

                    if (fixtureEndpoints.includes(ep)) {
                        if (!fid) { alert('Enter a fixture ID first'); return; }
                        url += '?fixture=' + encodeURIComponent(fid);
                    }

                    this.disabled = true;
                    status.style.display = 'block';
                    status.textContent = 'Fetching ' + url + ' …';
                    output.style.display = 'none';

                    try {
                        const res = await fetch(url, { headers: { 'X-WP-Nonce': nonce } });
                        const data = await res.json();
                        status.style.color = res.ok ? '#46b450' : '#dc3232';
                        status.textContent = (res.ok ? '✓ ' : '✗ ') + res.status + ' ' + url;
                        output.style.display = 'block';
                        output.textContent = JSON.stringify(data, null, 2);
                    } catch(err) {
                        status.style.color = '#dc3232';
                        status.textContent = '✗ Error: ' + err.message;
                    }

                    this.disabled = false;
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
    if ( $err = smallpoles_rate_check( 'fixture', 10, 60 ) ) return $err;
    $team_id   = (int) get_option( 'smallpoles_team_id',   1504 );
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season',    2026 );
    $cache_key = "next_fixture_{$team_id}_{$league_id}_{$season}";

    $cached = smallpoles_cache_get( $cache_key );
    if ( $cached !== false ) return rest_ensure_response( $cached );

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
    if ( $err = smallpoles_rate_check( 'odds', 15, 60 ) ) return $err;
    $fixture_id = $request->get_param( 'fixture' );
    $bookmaker  = $request->get_param( 'bookmaker' );
    $params     = [ 'fixture' => $fixture_id ];
    $cache_key  = "odds_{$fixture_id}";

    if ( $bookmaker ) {
        $params['bookmaker'] = $bookmaker;
        $cache_key .= "_b{$bookmaker}";
    }

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
    if ( $err = smallpoles_rate_check( 'predictions', 15, 60 ) ) return $err;
    $fixture_id = $request->get_param( 'fixture' );
    $result     = smallpoles_api_fetch(
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

function smallpoles_games_rewrites() {
    add_rewrite_rule( '^games/?$',         'index.php?smallpoles_game=hub',     'top' );
    add_rewrite_rule( '^games/polele/?$',  'index.php?smallpoles_game=polele',  'top' );
    add_rewrite_rule( '^games/bracket/?$', 'index.php?smallpoles_game=bracket', 'top' );
    add_rewrite_rule( '^games/squad/?$',         'index.php?smallpoles_game=squad',         'top' );
    add_rewrite_rule( '^games/higher-lower/?$',  'index.php?smallpoles_game=higher-lower',  'top' );
}
add_action( 'init', 'smallpoles_games_rewrites' );

add_filter( 'query_vars', function ( $vars ) {
    if ( ! in_array( 'smallpoles_game', $vars, true ) ) {
        $vars[] = 'smallpoles_game';
    }
    return $vars;
} );

/* ── Helper: check if a game is enabled ── */
function sp_game_is_visible( $game ) {
    $saved = get_option( 'sp_games_visibility', [] );
    return ! isset( $saved[ $game ] ) || ! empty( $saved[ $game ] );
}

function smallpoles_games_template( $template ) {
    $game = get_query_var( 'smallpoles_game' );
    if ( ! $game ) return $template;

    /* Redirect to hub if a specific game is disabled */
    $playable = [ 'polele', 'bracket', 'squad', 'higher-lower' ];
    if ( in_array( $game, $playable, true ) && ! sp_game_is_visible( $game ) ) {
        wp_safe_redirect( home_url( '/games/' ) );
        exit;
    }

    $map = [
        'hub'          => 'page-games.php',
        'polele'       => 'page-polele.php',
        'bracket'      => 'page-bracket.php',
        'squad'        => 'page-squad-builder.php',
        'higher-lower' => 'page-higher-lower.php',
    ];

    if ( isset( $map[ $game ] ) ) {
        $t = locate_template( $map[ $game ] );
        if ( $t ) return $t;
    }
    return $template;
}
add_filter( 'template_include', 'smallpoles_games_template' );

function smallpoles_games_title( $parts ) {
    $game   = get_query_var( 'smallpoles_game' );
    $titles = [
        'hub'     => 'World Cup Games',
        'polele'  => 'Polele — Guess the Player',
        'bracket' => 'World Cup 2026 Bracket Challenge',
        'squad'        => 'World Cup Squad Builder',
        'higher-lower' => 'Higher or Lower — World Cup Player Stats',
    ];
    if ( isset( $titles[ $game ] ) ) {
        $parts['title'] = $titles[ $game ];
        $parts['site']  = get_bloginfo( 'name' );
    }
    return $parts;
}
add_filter( 'document_title_parts', 'smallpoles_games_title' );

/* One-time permalink flush whenever routing version bumps */
function smallpoles_maybe_flush_rewrites() {
    if ( get_option( 'sp_rewrite_version' ) !== '1.2' ) {
        flush_rewrite_rules( false );
        update_option( 'sp_rewrite_version', '1.2' );
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
    .sb-player-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:6px}
    .sb-player-item{display:flex;align-items:center;gap:6px;background:#fff;padding:6px 10px;border-radius:4px;border:1px solid #ddd}
    .sb-pname{flex:1;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .sb-pos{font-size:10px;font-weight:700;padding:2px 6px;border-radius:3px;color:#fff;flex-shrink:0;min-width:30px;text-align:center}
    .sb-pos-gk{background:#c47d00}.sb-pos-def{background:#1e73be}.sb-pos-mid{background:#2a7a2a}.sb-pos-fwd{background:#c0392b}
    .sb-pval{width:44px!important;text-align:center}
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
                    n: $(this).find('.sb-pname').text().trim(),
                    p: $(this).find('.sb-pos').text().trim(),
                    v: parseInt($(this).find('.sb-pval').val(),10)||7
                });
            });
            $.post(ajaxurl,{action:'sb_save_players',nonce:nonce,name:name,players:JSON.stringify(players)},function(res){
                btn.prop('disabled',false).text('Save Values');
                if(res.success) btn.siblings('.sb-saved-msg').show().delay(2000).fadeOut();
            });
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

        function rebuildPlayerRow(idx, name, players){
            var posClass = {GK:'gk',DEF:'def',MID:'mid',FWD:'fwd'};
            var items = players.map(function(p){
                var pc = posClass[p.p]||'mid';
                return '<div class="sb-player-item">'
                    +'<span class="sb-pos sb-pos-'+pc+'">'+esc(p.p)+'</span>'
                    +'<span class="sb-pname">'+esc(p.n)+'</span>'
                    +'<input type="number" class="sb-pval small-text" value="'+p.v+'" min="1" max="20" />'
                    +'<span style="font-size:11px;color:#999">pts</span>'
                    +'</div>';
            }).join('');
            var html = '<div class="sb-player-grid">'+items+'</div>'
                +'<div style="margin-top:12px">'
                +'<button class="button button-primary sb-save-vals" data-name="'+esc(name)+'">Save Values</button>'
                +' <span class="sb-saved-msg" style="color:#46b450;font-weight:600;display:none">✓ Saved</span>'
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
        $pc = $pos_class[ $p['p'] ] ?? 'mid';
        printf(
            '<div class="sb-player-item"><span class="sb-pos sb-pos-%s">%s</span><span class="sb-pname">%s</span><input type="number" class="sb-pval small-text" value="%d" min="1" max="20" /><span style="font-size:11px;color:#999">pts</span></div>',
            esc_attr( $pc ), esc_html( $p['p'] ), esc_html( $p['n'] ), (int) $p['v']
        );
    }
    echo '</div>';
    printf(
        '<div style="margin-top:12px"><button class="button button-primary sb-save-vals" data-name="%s">Save Values</button> <span class="sb-saved-msg" style="color:#46b450;font-weight:600;display:none">✓ Saved</span></div>',
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
