<?php
/**
 * KillerMod 2026 — Theme Functions
 *
 * Rendering rules:
 *  - Home (front page / blog index) → new 2026 design, Mod Games posts only.
 *  - Single post in "Mod Games" category → 100% original look (legacy header/footer/CSS). UNTOUCHED.
 *  - Single post in any other category → same mod body, wrapped in the NEW header + footer.
 *
 * All post data still comes from the KillerMod Manager plugin (same option + meta keys).
 */

defined('ABSPATH') || exit;

define('KM26_VERSION', '1.0.1');

/**
 * Cache-busting version for a theme asset = its last-modified time.
 * This forces browsers to re-download CSS/JS the moment the file changes,
 * so edits never get stuck behind a stale cached copy.
 */
function km26_asset_ver($relpath) {
    $file = get_template_directory() . '/' . ltrim($relpath, '/');
    return file_exists($file) ? (string) filemtime($file) : KM26_VERSION;
}

/* ------------------------------------------------------------------
   Helper: is the post being viewed a Mod Games single post?
------------------------------------------------------------------ */
function km26_is_modgames_single() {
    if (!is_singular('post')) return false;
    $id = get_queried_object_id();
    // match by slug 'mod-games' OR display name 'Mod Games'
    return has_category('mod-games', $id) || has_category('Mod Games', $id);
}

/* ------------------------------------------------------------------
   Theme setup
------------------------------------------------------------------ */
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
    register_nav_menus([
        'primary' => __('Primary Menu', 'killermod-2026'),
    ]);
});

/* ------------------------------------------------------------------
   Front-end assets — NEW 2026 theme everywhere.
   Every page (home, VIP, archives AND every single post, Mod Games
   included) loads the new 2026 stylesheet + fonts + JS. Single posts
   additionally load the Rajdhani / Exo 2 fonts the shared mod body uses
   for its headings & text. The legacy stylesheet is no longer loaded.
------------------------------------------------------------------ */
add_action('wp_enqueue_scripts', function () {

    $tpl = get_template_directory_uri();

    // ---- NEW 2026 PATH (all pages) ----
    // Bebas Neue (display) + Syne (titles) + DM Sans (body).
    wp_enqueue_style(
        'killermod-2026-fonts',
        'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap',
        [], null
    );
    wp_enqueue_style(
        'killermod-2026',
        get_stylesheet_uri(),
        ['killermod-2026-fonts'],
        km26_asset_ver('style.css')
    );
    wp_enqueue_script(
        'killermod-2026-js',
        $tpl . '/assets/js/killermod-2026.js',
        [], km26_asset_ver('assets/js/killermod-2026.js'), true
    );

    // Every single post reuses the shared mod body (Rajdhani / Exo 2 headings).
    if (is_singular('post')) {
        wp_enqueue_style(
            'killermod-legacy-fonts',
            'https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Exo+2:wght@400;500;600;700;800&display=swap',
            [], null
        );
    }
});

/* ------------------------------------------------------------------
   Small option helpers (defaults mirror the KillerMod Manager plugin)
------------------------------------------------------------------ */
function km26_opt($key, $default = '') {
    return get_option($key, $default);
}

/**
 * Card stat (downloads / rating) for the New & Popular cards.
 * Uses real post meta if present (km_downloads / km_rating), otherwise a
 * STABLE value derived from the post ID so each card looks consistent.
 */
function km26_mod_stat($id, $type) {
    if ($type === 'downloads') {
        $v = get_post_meta($id, 'km_downloads', true);
        if ($v !== '' && $v !== false) return $v;
        $n = 2400 + (((int) $id * 7919) % 23000); // ~2.4K–25K, stable
        return ($n >= 1000) ? round($n / 1000, 1) . 'K' : (string) $n;
    }
    if ($type === 'rating') {
        $v = get_post_meta($id, 'km_rating', true);
        if ($v !== '' && $v !== false) return $v;
        return number_format(4.5 + (((int) $id % 5) / 10), 1); // 4.5–4.9, stable
    }
    return '';
}

/* ------------------------------------------------------------------
   Homepage = ONLY the "Mod Games" category.
   Locks the main front-page/blog query to the mod-games category, so
   pagination, the main loop, and feeds never include other categories.
   (The home template already displays only mod-games; this is the
   query-level safety net. Matches by slug 'mod-games' or name 'Mod Games'.)
------------------------------------------------------------------ */
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) return;
    if (!($q->is_home() || $q->is_front_page())) return;

    $cat = get_category_by_slug('mod-games');
    if (!$cat) {
        $cat = get_term_by('name', 'Mod Games', 'category');
    }
    if ($cat && !is_wp_error($cat)) {
        $q->set('cat', (int) $cat->term_id);
    }
});

/* ------------------------------------------------------------------
   AUTO-CREATE the /all-mods/ page + REWIRE the hero button.
   Hero "All MODs →" button targets /all-mods/. We do two things on
   `init` (the earliest hook where wp_insert_post is safe):

   1) Create the /all-mods/ WP page if missing, pinned to
      page-all-mods.php template.
   2) Force-update killermod_hero_btn1_url and _text if they still
      point at the OLD defaults (the category archive or "Browse Mods"
      text) — this stomps the stale saved value from the KillerMod
      Manager plugin so the button actually goes to the new page.

   Each step stores its own flag in wp_options so it doesn't keep
   firing on every request.
------------------------------------------------------------------ */
add_action('init', function () {

    /* --- (1) Create the /all-mods/ page if missing --- */
    if (!get_option('km26_allmods_page_created')) {
        $existing = get_page_by_path('all-mods');
        if (!$existing) {
            $pid = wp_insert_post([
                'post_title'   => 'All Mods',
                'post_name'    => 'all-mods',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '', // template renders the grid; content isn't used
                'meta_input'   => [
                    '_wp_page_template' => 'page-all-mods.php',
                ],
            ]);
            if ($pid && !is_wp_error($pid)) {
                update_option('km26_allmods_page_created', 1);
            }
        } else {
            $tpl = get_post_meta($existing->ID, '_wp_page_template', true);
            if ($tpl !== 'page-all-mods.php') {
                update_post_meta($existing->ID, '_wp_page_template', 'page-all-mods.php');
            }
            update_option('km26_allmods_page_created', 1);
        }
    }

    /* --- (1b) Create the /fast-download/ page if missing ---
       This is the page the purple "Get it in the App" button on every
       mod post links to (App Link auto-generates to this URL + ?mod=slug
       unless overridden in KillerMod Manager). --- */
    if (!get_option('km26_fastdownload_page_created')) {
        $existing = get_page_by_path('fast-download');
        if (!$existing) {
            $pid = wp_insert_post([
                'post_title'   => 'Fast Download',
                'post_name'    => 'fast-download',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
                'meta_input'   => [
                    '_wp_page_template' => 'page-fast-download.php',
                ],
            ]);
            if ($pid && !is_wp_error($pid)) {
                update_option('km26_fastdownload_page_created', 1);
            }
        } else {
            $tpl = get_post_meta($existing->ID, '_wp_page_template', true);
            if ($tpl !== 'page-fast-download.php') {
                update_post_meta($existing->ID, '_wp_page_template', 'page-fast-download.php');
            }
            update_option('km26_fastdownload_page_created', 1);
        }
    }

    /* --- (2) Rewire the hero button — stomp the stale saved value
       from the KillerMod Manager plugin if it still points at the
       old category archive. Runs once per site. --- */
    if (!get_option('km26_allmods_btn_rewired')) {
        $saved_url  = (string) get_option('killermod_hero_btn1_url', '');
        $saved_text = (string) get_option('killermod_hero_btn1_text', '');

        $cat_link = '';
        $cat_id   = function_exists('get_cat_ID') ? get_cat_ID('Mod Games') : 0;
        if ($cat_id) {
            $cat_link = (string) get_category_link($cat_id);
        }

        // Old URL = empty / category archive / mod-games slug anywhere in URL
        $url_is_old = ($saved_url === ''
            || $saved_url === $cat_link
            || strpos($saved_url, '/category/mod-games') !== false
            || strpos($saved_url, 'category=mod-games') !== false);

        $text_is_old = ($saved_text === '' || strcasecmp($saved_text, 'Browse Mods') === 0);

        if ($url_is_old)  update_option('killermod_hero_btn1_url',  home_url('/all-mods/'));
        if ($text_is_old) update_option('killermod_hero_btn1_text', 'All MODs');

        update_option('km26_allmods_btn_rewired', 1);
    }
}, 5);

/* ==================================================================
   SEO MODULE
   Goal: keep the site fully indexable after the theme switch and give
   Google clean title / description / canonical / Open Graph / schema.

   Everything here is SKIPPED automatically if a real SEO plugin
   (Yoast, Rank Math, All in One SEO, SEOPress) is active, so there are
   never duplicate tags.
================================================================== */

/** Is a dedicated SEO plugin already handling meta output? */
function km26_has_seo_plugin() {
    return defined('WPSEO_VERSION')          // Yoast SEO
        || defined('RANK_MATH_VERSION')      // Rank Math
        || defined('AIOSEO_VERSION')         // All in One SEO
        || defined('SEOPRESS_VERSION')       // SEOPress
        || class_exists('SEOPRESS_Init');
}

/* Make sure WordPress can advertise feeds + uses the modern sitemap. */
add_action('after_setup_theme', function () {
    add_theme_support('automatic-feed-links');
});

/* ---- <head> meta: robots, description, canonical, Open Graph, Twitter ---- */
add_action('wp_head', function () {
    if (is_admin() || km26_has_seo_plugin()) return;

    // 1) ROBOTS — the line that actually controls indexing.
    if (get_option('blog_public')) {
        echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">' . "\n";
    } else {
        // Site is set to "discourage search engines" — respect it, but the
        // admin notice below tells the owner how to turn it back on.
        echo '<meta name="robots" content="noindex, follow">' . "\n";
    }

    // 2) CANONICAL URL
    $canonical = '';
    if (is_front_page() || is_home()) {
        $canonical = home_url('/');
    } elseif (is_singular()) {
        $canonical = get_permalink();
    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $link = $term ? get_term_link($term) : '';
        if ($link && !is_wp_error($link)) $canonical = $link;
    }
    if ($canonical) {
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    // 3) META DESCRIPTION
    if (is_singular()) {
        $obj  = get_queried_object();
        $desc = ($obj && has_excerpt($obj->ID)) ? get_the_excerpt($obj) : ($obj ? $obj->post_content : '');
    } else {
        $desc = get_bloginfo('description');
    }
    $desc = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $desc)));
    if ($desc !== '') {
        if (function_exists('mb_substr')) { $desc = mb_substr($desc, 0, 160); }
        else { $desc = substr($desc, 0, 160); }
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    }

    // 4) OPEN GRAPH + TWITTER
    $og_url = $canonical ?: home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr(wp_get_document_title()) . '">' . "\n";
    echo '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($og_url) . '">' . "\n";
    if ($desc !== '') {
        echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
    }
    $og_img = '';
    if (is_singular() && has_post_thumbnail()) {
        $og_img = get_the_post_thumbnail_url(get_queried_object_id(), 'large');
    } elseif (has_custom_logo()) {
        $lid    = get_theme_mod('custom_logo');
        $og_img = $lid ? wp_get_attachment_image_url($lid, 'full') : '';
    }
    if ($og_img) {
        echo '<meta property="og:image" content="' . esc_url($og_img) . '">' . "\n";
    }
    echo '<meta name="twitter:card" content="' . ($og_img ? 'summary_large_image' : 'summary') . '">' . "\n";
}, 1);

/* ---- JSON-LD structured data: WebSite (with search box) + Organization ---- */
add_action('wp_head', function () {
    if (is_admin() || km26_has_seo_plugin()) return;

    $data = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'           => 'WebSite',
                'url'             => home_url('/'),
                'name'            => get_bloginfo('name'),
                'description'     => get_bloginfo('description'),
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => home_url('/?s={search_term_string}'),
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'Organization',
                'url'   => home_url('/'),
                'name'  => get_bloginfo('name'),
            ],
        ],
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($data) . '</script>' . "\n";
}, 2);

/* ---- Dashboard warning if the site is hidden from search engines ---- */
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    if (get_option('blog_public')) return; // all good, indexing allowed
    printf(
        '<div class="notice notice-error"><p><strong>KillerMod SEO:</strong> Search engines are currently <strong>BLOCKED</strong> from indexing this site — this is the #1 reason a site disappears from Google. Go to <a href="%s">Settings &rarr; Reading</a> and <strong>uncheck</strong> &ldquo;Discourage search engines from indexing this site&rdquo;, then Save.</p></div>',
        esc_url(admin_url('options-reading.php'))
    );
});
