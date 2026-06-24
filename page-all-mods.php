<?php
/**
 * Template Name: All Mods
 *
 * Hero "All MODs →" target. Renders every Mod Games post in one grid.
 * Filter pills are AUTO-BUILT from whatever extra categories each
 * mod-games post is already tagged with (e.g. "Racing", "Action",
 * "Simulation"). You don't set anything up — assign a mod post to a
 * normal category and a pill for it appears here automatically with
 * a live count.
 *
 * Search, sort, and category filter all run client-side on the cards
 * already in the DOM, so switching is instant. Cards reuse the same
 * .game-big-card / .game-thumb / .game-info classes from the homepage
 * so dark/light mode and hover styling are inherited for free.
 *
 * URL: /all-mods/   (page is auto-created by functions.php if missing)
 */
defined('ABSPATH') || exit;

/* ------------------------------------------------------------------
   Helpers (mirror the ones index.php defines, redeclared here so this
   template works even when the homepage hasn't run yet — e.g. direct
   navigation, or when the home query is cached / replaced).
------------------------------------------------------------------ */
if (!function_exists('km26_mod_feature')) {
    function km26_mod_feature($id) {
        $f = get_post_meta($id, 'mod_feature', true);
        if (!$f) $f = get_post_meta($id, 'mod_features', true);
        if (!$f) {
            $km = get_post_meta($id, '_km_mod_features', true);
            if ($km) { $lines = array_filter(array_map('trim', explode("\n", $km))); if ($lines) $f = reset($lines); }
        }
        if (!$f) {
            $t = strtolower(get_the_title($id));
            if (strpos($t, 'unlimited money') !== false)      $f = 'Unlimited Money';
            elseif (strpos($t, 'god mode') !== false)         $f = 'God Mode';
            elseif (strpos($t, 'mod menu') !== false)         $f = 'Mod Menu';
            elseif (strpos($t, 'menu mod') !== false)         $f = 'Menu MOD';
            elseif (strpos($t, 'unlocked') !== false)         $f = 'Unlocked';
            else                                              $f = 'MOD';
        }
        return $f;
    }
}
if (!function_exists('km26_card_logo')) {
    function km26_card_logo($id) {
        $logo_id = (int) get_post_meta($id, '_km_card_logo_id', true);
        $url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        if (!$url) $url = get_the_post_thumbnail_url($id, 'medium');
        $fit = get_post_meta($id, '_km_card_fit', true) === 'contain' ? 'contain' : 'cover';
        $bg  = get_post_meta($id, '_km_card_bg', true);
        if ($bg === '' || $bg === false) $bg = 'linear-gradient(135deg,#1a1f2e,#0d1018)';
        return [$url, $fit, $bg];
    }
}

$grads = [
    'linear-gradient(135deg,#1a3300,#0d1a00)',
    'linear-gradient(135deg,#001a33,#000d1a)',
    'linear-gradient(135deg,#2a1a00,#150d00)',
    'linear-gradient(135deg,#001a1a,#000d0d)',
    'linear-gradient(135deg,#200020,#100010)',
    'linear-gradient(135deg,#001433,#000a1a)',
];

/* ------------------------------------------------------------------
   1. Pull every Mod Games post (no pagination — client-side filter
   needs the whole set in the DOM. ~24-60 posts is well within budget;
   revisit only if the library grows past a few hundred.)
------------------------------------------------------------------ */
$mod_games_cat = get_category_by_slug('mod-games');
if (!$mod_games_cat) $mod_games_cat = get_term_by('name', 'Mod Games', 'category');
$mod_cat_id    = $mod_games_cat ? (int) $mod_games_cat->term_id : 0;

$all_mods = new WP_Query([
    'post_type'      => 'post',
    'category_name'  => 'mod-games',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
$total_mods = $all_mods->found_posts;

/* ------------------------------------------------------------------
   2. AUTO-BUILD filter pills.
   Walk every mod-games post, look at its OTHER categories, count how
   many mod-games posts each one has. Result = pills like
   [ Racing 7 ] [ Action 5 ] [ Simulation 4 ] …
   The "Mod Games" category itself, "Uncategorized", and any empty
   names are skipped.
------------------------------------------------------------------ */
$filter_cats   = [];
$updated_today = 0;
$today_ymd     = current_time('Y-m-d');

if ($all_mods->have_posts()) {
    foreach ($all_mods->posts as $p) {
        $cats = get_the_category($p->ID);
        foreach ($cats as $c) {
            if ((int) $c->term_id === $mod_cat_id) continue;
            if ($c->slug === 'uncategorized')      continue;
            if (!isset($filter_cats[$c->term_id])) {
                $filter_cats[$c->term_id] = ['name' => $c->name, 'slug' => $c->slug, 'count' => 0];
            }
            $filter_cats[$c->term_id]['count']++;
        }
        if (get_the_date('Y-m-d', $p) === $today_ymd) $updated_today++;
    }
}
// Sort pills by count desc (most populated category first)
uasort($filter_cats, function ($a, $b) { return $b['count'] - $a['count']; });

get_header();
?>

<!-- ════════════════════════════════════════ ALL MODS — HERO ════════════════════════════════════════ -->
<section class="hero km-allmods-hero" style="min-height:auto;padding-bottom:0;">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>
  <div class="hero-inner" style="grid-template-columns:1fr;padding-top:110px;padding-bottom:36px;">
    <div class="hero-text" style="max-width:none;">
      <div class="hero-label">— Browse Library</div>
      <h1 style="font-size:clamp(48px,8vw,96px);line-height:0.95;">ALL <span class="glow-text">MODS</span></h1>
      <p class="hero-sub" style="max-width:560px;">Every mod APK in the vault — auto-sorted by category. Search, filter, download.</p>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════ ALL MODS — TOOLBAR ════════════════════════════════════════ -->
<section class="km-allmods" id="allMods">

  <!-- Search + sort row -->
  <div class="km-am-toolbar">
    <label class="km-am-search">
      <span class="km-am-search-ico" aria-hidden="true">⌕</span>
      <input type="search" id="kmAmSearch" placeholder="Search mods, games, versions…" autocomplete="off">
    </label>
    <div class="km-am-sort">
      <label for="kmAmSort" class="km-am-sort-label">Sort</label>
      <select id="kmAmSort" class="km-am-sort-select">
        <option value="newest">Newest first</option>
        <option value="oldest">Oldest first</option>
        <option value="az">Title A → Z</option>
        <option value="za">Title Z → A</option>
        <option value="downloads">Most downloaded</option>
        <option value="rating">Top rated</option>
      </select>
    </div>
  </div>

  <!-- AUTO-BUILT category pills -->
  <div class="km-am-pills" id="kmAmPills">
    <button class="km-am-pill is-active" data-cat="all" type="button">
      All <span class="km-am-pill-count"><?php echo (int) $total_mods; ?></span>
    </button>
    <?php foreach ($filter_cats as $cat) : ?>
      <button class="km-am-pill" data-cat="<?php echo esc_attr($cat['slug']); ?>" type="button">
        <?php echo esc_html($cat['name']); ?>
        <span class="km-am-pill-count"><?php echo (int) $cat['count']; ?></span>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Result meta -->
  <div class="km-am-meta">
    <div class="km-am-meta-left">
      Showing <strong id="kmAmShown"><?php echo (int) $total_mods; ?></strong>
      of <strong><?php echo (int) $total_mods; ?></strong> mods
    </div>
    <?php if ($updated_today > 0) : ?>
    <div class="km-am-meta-right">
      🔥 <?php echo (int) $updated_today; ?> updated today
    </div>
    <?php endif; ?>
  </div>

  <!-- ════════════════════════════════════════ GRID ════════════════════════════════════════ -->
  <?php if ($all_mods->have_posts()) : ?>
  <div class="km-am-grid" id="kmAmGrid">
    <?php $i = 0; while ($all_mods->have_posts()) : $all_mods->the_post();
        $id        = get_the_ID();
        list($cardUrl, $cardFit, $cardBg) = km26_card_logo($id);
        $thumbBg   = $cardFit === 'contain' ? $cardBg : $grads[$i % 6];
        $feature   = km26_mod_feature($id);
        $downloads = km26_mod_stat($id, 'downloads');
        $rating    = km26_mod_stat($id, 'rating');
        $is_today  = get_the_date('Y-m-d') === $today_ymd;

        // Build slug list of this post's non-"mod-games" categories,
        // used by the client filter (data-cats attribute).
        $post_cats = [];
        foreach (get_the_category($id) as $c) {
            if ((int) $c->term_id === $mod_cat_id) continue;
            if ($c->slug === 'uncategorized')      continue;
            $post_cats[] = $c->slug;
        }
        // Searchable text = title + version + feature (lowercased on render)
        $search_blob = strtolower(get_the_title() . ' ' . $feature);
    ?>
    <a href="<?php the_permalink(); ?>" class="game-big-card km-am-card"
       data-cats="<?php echo esc_attr(implode(',', $post_cats)); ?>"
       data-title="<?php echo esc_attr(strtolower(get_the_title())); ?>"
       data-date="<?php echo esc_attr(get_the_date('U')); ?>"
       data-downloads="<?php echo esc_attr(preg_replace('/[^\d.]/', '', (string) $downloads)); ?>"
       data-rating="<?php echo esc_attr((string) $rating); ?>"
       data-search="<?php echo esc_attr($search_blob); ?>"
       style="text-decoration:none;color:inherit;">
      <div class="game-thumb <?php echo $cardFit === 'contain' ? 'is-contain' : 'is-cover'; ?>" style="background: <?php echo esc_attr($thumbBg); ?>;">
        <?php if ($cardUrl) : ?>
          <img src="<?php echo esc_url($cardUrl); ?>" alt="<?php the_title_attribute(); ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:<?php echo $cardFit; ?>;">
        <?php else : ?>
          🎮
        <?php endif; ?>
        <div class="game-thumb-overlay"></div>
      </div>
      <div class="game-info">
        <div class="game-title"><?php the_title(); ?></div>
        <div class="game-meta">
          <span class="mod-type"><?php echo esc_html($feature); ?></span>
          <?php if ($is_today) : ?>
            <span class="updated-badge">TODAY</span>
          <?php else : ?>
            <span class="updated-badge" style="background:rgba(107,116,148,0.15);color:var(--muted);"><?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?> ago</span>
          <?php endif; ?>
        </div>
        <span class="download-btn">↓ Download</span>
      </div>
    </a>
    <?php $i++; endwhile; wp_reset_postdata(); ?>
  </div>

  <!-- Empty state (shown by JS when filter/search returns nothing) -->
  <div class="km-am-empty" id="kmAmEmpty" hidden>
    <div class="km-am-empty-ico">⌕</div>
    <h3>No mods match that filter</h3>
    <p>Try a different category or clear the search.</p>
    <button type="button" class="km-am-reset" id="kmAmReset">Reset filters</button>
  </div>

  <?php else : ?>
  <div class="km-am-empty">
    <div class="km-am-empty-ico">🎮</div>
    <h3>No mods published yet</h3>
    <p>Add posts to the <strong>Mod Games</strong> category and they'll show up here automatically.</p>
  </div>
  <?php endif; ?>

</section>

<!-- ════════════════════════════════════════ STYLES (scoped to .km-allmods) ════════════════════════════════════════ -->
<style>
  .km-allmods {
    padding: 8px clamp(20px, 4vw, 60px) 80px;
    max-width: 1400px;
    margin: 0 auto;
  }

  /* Toolbar */
  .km-am-toolbar {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
    flex-wrap: wrap;
  }
  .km-am-search {
    flex: 1;
    min-width: 240px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0 16px;
    height: 48px;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .km-am-search:focus-within {
    border-color: rgba(232,255,71,0.4);
    box-shadow: 0 0 0 3px rgba(232,255,71,0.08);
  }
  .km-am-search-ico {
    color: var(--muted);
    font-size: 18px;
    line-height: 1;
  }
  .km-am-search input {
    flex: 1;
    background: transparent;
    border: 0;
    outline: 0;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    height: 100%;
  }
  .km-am-search input::placeholder { color: var(--muted); }

  .km-am-sort {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0 16px;
    height: 48px;
  }
  .km-am-sort-label {
    color: var(--muted);
    font-size: 12px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-weight: 600;
  }
  .km-am-sort-select {
    background: transparent;
    border: 0;
    outline: 0;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    padding-right: 6px;
  }
  .km-am-sort-select option { background: var(--surface); color: var(--text); }

  /* Category pills */
  .km-am-pills {
    display: flex;
    gap: 8px;
    margin-bottom: 22px;
    flex-wrap: wrap;
  }
  .km-am-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 999px;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.18s;
    white-space: nowrap;
  }
  .km-am-pill:hover {
    border-color: rgba(232,255,71,0.3);
    color: var(--accent);
  }
  .km-am-pill.is-active {
    background: var(--accent);
    color: #0a0d14;
    border-color: var(--accent);
  }
  body:not(.light-mode) .km-am-pill.is-active { color: #0a0d14; }
  .km-am-pill-count {
    font-size: 11px;
    opacity: 0.7;
    font-weight: 600;
  }
  .km-am-pill.is-active .km-am-pill-count { opacity: 0.85; }

  /* Result meta */
  .km-am-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    color: var(--muted);
    flex-wrap: wrap;
    gap: 12px;
  }
  .km-am-meta strong { color: var(--text); font-weight: 700; }
  .km-am-meta-right { color: var(--accent); font-weight: 600; }

  /* Grid */
  .km-am-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
  }
  .km-am-card {
    display: block;
  }
  .km-am-card[hidden] { display: none; }

  /* Empty state */
  .km-am-empty {
    text-align: center;
    padding: 80px 20px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    margin-top: 8px;
  }
  .km-am-empty-ico {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
  }
  .km-am-empty h3 {
    font-family: 'Syne', sans-serif;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text);
  }
  .km-am-empty p {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 18px;
  }
  .km-am-reset {
    background: var(--accent);
    color: #0a0d14;
    border: 0;
    border-radius: 8px;
    padding: 10px 22px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.4px;
    cursor: pointer;
    transition: opacity 0.2s;
  }
  .km-am-reset:hover { opacity: 0.85; }

  /* Mobile tweaks */
  @media (max-width: 640px) {
    .km-allmods-hero h1 { font-size: 56px !important; }
    .km-am-toolbar { flex-direction: column; }
    .km-am-search, .km-am-sort { width: 100%; }
    .km-am-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .km-am-empty { padding: 50px 20px; }
  }
</style>

<!-- ════════════════════════════════════════ JS — filter / search / sort ════════════════════════════════════════ -->
<script>
(function () {
  var grid = document.getElementById('kmAmGrid');
  if (!grid) return;

  var pills    = document.querySelectorAll('#kmAmPills .km-am-pill');
  var search   = document.getElementById('kmAmSearch');
  var sortSel  = document.getElementById('kmAmSort');
  var shown    = document.getElementById('kmAmShown');
  var empty    = document.getElementById('kmAmEmpty');
  var resetBtn = document.getElementById('kmAmReset');
  var cards    = Array.prototype.slice.call(grid.querySelectorAll('.km-am-card'));

  var state = { cat: 'all', q: '', sort: 'newest' };

  function apply() {
    var q = state.q.trim().toLowerCase();
    var n = 0;

    cards.forEach(function (card) {
      var catMatch = state.cat === 'all'
        ? true
        : (',' + (card.dataset.cats || '') + ',').indexOf(',' + state.cat + ',') !== -1;
      var qMatch   = q === '' ? true : (card.dataset.search || '').indexOf(q) !== -1;
      var visible  = catMatch && qMatch;
      if (visible) { card.hidden = false; n++; }
      else         { card.hidden = true; }
    });

    if (shown) shown.textContent = n;
    if (empty) empty.hidden = n !== 0;
    if (grid)  grid.style.display = n === 0 ? 'none' : '';

    sortCards();
  }

  function sortCards() {
    var sorted = cards.slice().sort(function (a, b) {
      switch (state.sort) {
        case 'oldest':    return (+a.dataset.date)      - (+b.dataset.date);
        case 'az':        return (a.dataset.title || '').localeCompare(b.dataset.title || '');
        case 'za':        return (b.dataset.title || '').localeCompare(a.dataset.title || '');
        case 'downloads': return (+b.dataset.downloads || 0) - (+a.dataset.downloads || 0);
        case 'rating':    return (+b.dataset.rating    || 0) - (+a.dataset.rating    || 0);
        case 'newest':
        default:          return (+b.dataset.date) - (+a.dataset.date);
      }
    });
    sorted.forEach(function (card) { grid.appendChild(card); });
  }

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      pills.forEach(function (p) { p.classList.remove('is-active'); });
      pill.classList.add('is-active');
      state.cat = pill.dataset.cat || 'all';
      apply();
    });
  });

  if (search) {
    var t;
    search.addEventListener('input', function () {
      clearTimeout(t);
      t = setTimeout(function () { state.q = search.value; apply(); }, 80);
    });
  }

  if (sortSel) {
    sortSel.addEventListener('change', function () { state.sort = sortSel.value; apply(); });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      state.cat = 'all'; state.q = ''; state.sort = 'newest';
      if (search) search.value = '';
      if (sortSel) sortSel.value = 'newest';
      pills.forEach(function (p) { p.classList.toggle('is-active', p.dataset.cat === 'all'); });
      apply();
    });
  }

  // Initial sort
  sortCards();
})();
</script>

<?php get_footer();
