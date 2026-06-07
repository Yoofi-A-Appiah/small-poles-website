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
    wp_enqueue_style( 'smallpoles-style', get_stylesheet_uri(), [], '1.0.1' );
    wp_enqueue_script( 'smallpoles-main', get_template_directory_uri() . '/assets/js/main.js', [], '1.0.2', true );
    wp_localize_script( 'smallpoles-main', 'spData', [
        'restBase' => esc_url_raw( rest_url( 'smallpoles/v1/' ) ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'smallpoles_scripts' );

/* ── SEO: meta description, Open Graph, Twitter Card, canonical ── */
function smallpoles_seo_meta() {
    $og_image = get_template_directory_uri() . '/assets/og.webp';

    if ( is_front_page() ) {
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
    $team_id   = (int) get_option( 'smallpoles_team_id',   1504 );
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season',    2026 );
    $cache_key = "sp_next_fixture_{$team_id}_{$league_id}_{$season}";

    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return rest_ensure_response( $cached );
    }

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) {
        return new WP_Error( 'no_api_key', 'API key not configured.', [ 'status' => 503 ] );
    }

    $response = wp_remote_get(
        add_query_arg( [
            'team'   => $team_id,
            'league' => $league_id,
            'season' => $season,
            'next'   => 1,
        ], 'https://v3.football.api-sports.io/fixtures' ),
        [
            'timeout' => 10,
            'headers' => [ 'x-apisports-key' => $api_key ],
        ]
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 502 ] );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['response'][0] ) ) {
        return new WP_Error( 'no_fixture', 'No upcoming fixtures found.', [ 'status' => 404 ] );
    }

    $fixture = $body['response'][0];

    // Cache until 30 minutes after the match kicks off, then expire so live data flows in
    $kickoff     = $fixture['fixture']['timestamp'] ?? 0;
    $expires     = $kickoff ? max( 300, ( $kickoff + 1800 ) - time() ) : 6 * HOUR_IN_SECONDS;
    set_transient( $cache_key, $fixture, $expires );

    return rest_ensure_response( $fixture );
}

/* ── Shared API fetch helper ── */
function smallpoles_api_fetch( string $endpoint, array $params, string $cache_key, int $ttl ) {
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) return [ 'data' => $cached, 'error' => null ];

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) return [ 'data' => null, 'error' => new WP_Error( 'no_api_key', 'API key not configured.', [ 'status' => 503 ] ) ];

    $response = wp_remote_get(
        add_query_arg( $params, 'https://v3.football.api-sports.io/' . $endpoint ),
        [ 'timeout' => 10, 'headers' => [ 'x-apisports-key' => $api_key ] ]
    );

    if ( is_wp_error( $response ) ) return [ 'data' => null, 'error' => new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 502 ] ) ];

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['response'] ) ) return [ 'data' => null, 'error' => new WP_Error( 'no_data', 'No data returned.', [ 'status' => 404 ] ) ];

    set_transient( $cache_key, $body['response'], $ttl );
    return [ 'data' => $body['response'], 'error' => null ];
}

function smallpoles_standings_proxy() {
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season', 2026 );
    $result    = smallpoles_api_fetch(
        'standings',
        [ 'league' => $league_id, 'season' => $season ],
        "sp_standings_{$league_id}_{$season}",
        HOUR_IN_SECONDS
    );
    return $result['error'] ?? rest_ensure_response( $result['data'][0]['league'] ?? $result['data'] );
}

function smallpoles_fixtures_proxy( WP_REST_Request $request ) {
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season', 2026 );
    $team      = $request->get_param( 'team' );
    $round     = $request->get_param( 'round' );

    $params    = [ 'league' => $league_id, 'season' => $season ];
    $cache_key = "sp_fixtures_{$league_id}_{$season}";

    if ( $team ) { $params['team'] = $team; $cache_key .= "_t{$team}"; }
    if ( $round ) { $params['round'] = $round; $cache_key .= '_r' . md5( $round ); }

    $result = smallpoles_api_fetch( 'fixtures', $params, $cache_key, HOUR_IN_SECONDS );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_rounds_proxy() {
    $league_id = (int) get_option( 'smallpoles_league_id', 1 );
    $season    = (int) get_option( 'smallpoles_season', 2026 );
    $result    = smallpoles_api_fetch(
        'fixtures/rounds',
        [ 'league' => $league_id, 'season' => $season ],
        "sp_rounds_{$league_id}_{$season}",
        12 * HOUR_IN_SECONDS  // rounds rarely change
    );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_fixture_stats_proxy( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );
    $result     = smallpoles_api_fetch(
        'fixtures/statistics',
        [ 'fixture' => $fixture_id ],
        "sp_stats_{$fixture_id}",
        15 * MINUTE_IN_SECONDS  // live stats refresh fast
    );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_lineups_proxy( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );
    $result     = smallpoles_api_fetch(
        'fixtures/lineups',
        [ 'fixture' => $fixture_id ],
        "sp_lineups_{$fixture_id}",
        30 * MINUTE_IN_SECONDS
    );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_odds_proxy( WP_REST_Request $request ) {
    $fixture_id  = $request->get_param( 'fixture' );
    $bookmaker   = $request->get_param( 'bookmaker' );
    $params      = [ 'fixture' => $fixture_id ];
    $cache_key   = "sp_odds_{$fixture_id}";

    if ( $bookmaker ) {
        $params['bookmaker'] = $bookmaker;
        $cache_key .= "_b{$bookmaker}";
    }

    $result = smallpoles_api_fetch( 'odds', $params, $cache_key, HOUR_IN_SECONDS );
    return $result['error'] ?? rest_ensure_response( $result['data'] );
}

function smallpoles_odds_live_proxy( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );

    // Live odds: bypass the shared helper so we never serve stale data for more than 60s
    $cache_key = "sp_odds_live_{$fixture_id}";
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return rest_ensure_response( $cached );

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) return new WP_Error( 'no_api_key', 'API key not configured.', [ 'status' => 503 ] );

    $response = wp_remote_get(
        add_query_arg( [ 'fixture' => $fixture_id ], 'https://v3.football.api-sports.io/odds/live' ),
        [ 'timeout' => 10, 'headers' => [ 'x-apisports-key' => $api_key ] ]
    );

    if ( is_wp_error( $response ) ) return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 502 ] );

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['response'] ) ) return new WP_Error( 'no_data', 'No live odds available.', [ 'status' => 404 ] );

    set_transient( $cache_key, $body['response'], 60 ); // 60 seconds — live data
    return rest_ensure_response( $body['response'] );
}

function smallpoles_predictions_proxy( WP_REST_Request $request ) {
    $fixture_id = $request->get_param( 'fixture' );
    $cache_key  = 'sp_predictions_' . $fixture_id;

    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return rest_ensure_response( $cached );
    }

    $api_key = get_option( 'smallpoles_api_key', '' );
    if ( ! $api_key ) {
        return new WP_Error( 'no_api_key', 'API key not configured.', [ 'status' => 503 ] );
    }

    $response = wp_remote_get(
        'https://v3.football.api-sports.io/predictions?fixture=' . $fixture_id,
        [
            'timeout' => 10,
            'headers' => [
                'x-apisports-key' => $api_key,
            ],
        ]
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 502 ] );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['response'] ) ) {
        return new WP_Error( 'no_data', 'No predictions available for this fixture.', [ 'status' => 404 ] );
    }

    set_transient( $cache_key, $body['response'][0], HOUR_IN_SECONDS );

    return rest_ensure_response( $body['response'][0] );
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
