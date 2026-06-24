<?php
/**
 * Home page — NEW 2026 design. Shows ONLY the "Mod Games" category.
 * Section visibility + column counts come from the KillerMod Manager plugin.
 */
defined('ABSPATH') || exit;

if (!function_exists('km26_mod_feature')) {
    /** Best-effort "mod feature" label for a post (meta first, then title heuristics). */
    function km26_mod_feature($id) {
        $f = get_post_meta($id, 'mod_feature', true);
        if (!$f) $f = get_post_meta($id, 'mod_features', true);
        if (!$f) {
            // Fall back to first line of the plugin's features field
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
    /**
     * Resolve a post's homepage card image, managed by the KillerMod Cards plugin.
     * Returns [url, fit, bg]. Falls back to the featured image, then empty.
     */
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

$sections = get_option('killermod_active_sections', ['trending' => '1', 'latest' => '1', 'updated' => '1']);

get_header();

// Mod count for the hero stat
$mod_q     = new WP_Query(['post_type' => 'post', 'category_name' => 'mod-games', 'posts_per_page' => -1, 'fields' => 'ids']);
$mod_count = $mod_q->found_posts;
wp_reset_postdata();

$hero_title = get_option('killermod_hero_title', 'PREMIUM<br><span class="glow-text">MOD</span> <span class="outline">GAMES</span><br>CURATED');
$hero_desc  = get_option('killermod_hero_desc', '40–60 perfectly crafted mods. Same-day updates. No broken downloads. Ever. The only platform that actually works.');
$hero_badge = get_option('killermod_hero_badge', 'Mod Platform — 2026');
$btn1_text  = get_option('killermod_hero_btn1_text', 'All MODs');
// Default hero button now points to the dedicated /all-mods/ page
// (auto-created by functions.php). Keeps any saved option from the
// KillerMod Manager plugin if the admin has set one.
$btn1_url   = get_option('killermod_hero_btn1_url', home_url('/all-mods/'));

// Hero floating card — managed by the KillerMod Cards plugin (with safe fallbacks).
$hc_title = get_option('killermod_herocard_title', 'Free Fire MAX');
$hc_tag   = get_option('killermod_herocard_tag', 'Unlimited Diamonds');
$hc_imgid = (int) get_option('killermod_herocard_img_id', 0);
$hc_img   = $hc_imgid ? wp_get_attachment_image_url($hc_imgid, 'medium') : '';
$hc_vip   = get_option('killermod_herocard_vip', '1') === '1';
$hc_link  = get_option('killermod_herocard_link', '');
?>

<!-- ════════════════════════════════════════ HERO ════════════════════════════════════════ -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>

  <div class="hero-inner">
    <div class="hero-text">
      <div class="hero-label"><?php echo esc_html($hero_badge); ?></div>
      <h1><?php echo wp_kses($hero_title, ['span' => ['class' => []], 'br' => []]); ?></h1>
      <p class="hero-sub"><?php echo esc_html($hero_desc); ?></p>
      <div class="hero-cta">
        <a href="<?php echo esc_url($btn1_url); ?>" class="btn-hero" style="text-decoration:none;display:inline-flex;align-items:center;"><?php echo esc_html($btn1_text); ?> <span>→</span></a>
      </div>
      <div class="hero-stats">
        <div class="stat-item">
          <div class="stat-num"><?php echo $mod_count > 0 ? esc_html($mod_count) : '60'; ?><span>+</span></div>
          <div class="stat-label">Curated Games</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">0<span>hr</span></div>
          <div class="stat-label">Same-Day Updates</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">100<span>%</span></div>
          <div class="stat-label">Working Mods</div>
        </div>
      </div>
    </div>

    <!-- SINGLE FLOATING CARD — managed via KillerMod Cards (mobile) -->
    <div class="hero-float-mobile">
      <?php if ($hc_link) : ?><a href="<?php echo esc_url($hc_link); ?>" class="hfm-card hfm-top" style="text-decoration:none;color:inherit;"><?php else : ?><div class="hfm-card hfm-top"><?php endif; ?>
        <div class="hfm-img" style="background:linear-gradient(135deg,#1a3300,#0a1800);<?php echo $hc_img ? 'padding:0;overflow:hidden;' : ''; ?>"><?php if ($hc_img) : ?><img src="<?php echo esc_url($hc_img); ?>" style="width:100%;height:100%;object-fit:cover;display:block;"><?php else : ?>🎯<?php endif; ?></div>
        <div class="hfm-body">
          <div class="hfm-name"><?php echo esc_html($hc_title); ?></div>
          <span class="hfm-tag"><?php echo esc_html($hc_tag); ?></span>
        </div>
        <?php if ($hc_vip) : ?><span class="hfm-vip">VIP</span><?php endif; ?>
      <?php echo $hc_link ? '</a>' : '</div>'; ?>
    </div>
  </div>

  <!-- SINGLE FLOATING GAME CARD — managed via KillerMod Cards (desktop) -->
  <div class="hero-cards">
    <?php if ($hc_link) : ?><a href="<?php echo esc_url($hc_link); ?>" class="game-card" style="text-decoration:none;color:inherit;"><?php else : ?><div class="game-card"><?php endif; ?>
      <div class="game-card-img-placeholder" style="background: linear-gradient(135deg,#1a3300,#0a1800);<?php echo $hc_img ? 'overflow:hidden;' : ''; ?>"><?php if ($hc_img) : ?><img src="<?php echo esc_url($hc_img); ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"><?php else : ?>🎯<?php endif; ?></div>
      <?php if ($hc_vip) : ?><div class="vip-badge">VIP</div><?php endif; ?>
      <div class="game-card-body">
        <div class="game-card-name"><?php echo esc_html($hc_title); ?></div>
        <span class="game-card-tag"><?php echo esc_html($hc_tag); ?></span>
      </div>
    <?php echo $hc_link ? '</a>' : '</div>'; ?>
  </div>
</section>

<?php
/* ───────── TRENDING MODS — latest Mod Games, big-card grid ───────── */
if (($sections['trending'] ?? '0') === '1') :
    $trending = new WP_Query([
        'post_type'      => 'post',
        'category_name'  => 'mod-games',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    if ($trending->have_posts()) : ?>
<section class="trending" id="trending">
  <div class="trending-header">
    <div>
      <div class="section-label">Hot Right Now</div>
      <h2>TRENDING MODS</h2>
    </div>
    <a href="<?php echo esc_url(home_url('/all-mods/')); ?>" class="view-all">View all games →</a>
  </div>
  <?php $tCols = (int) get_option('killermod_trending_cols', 0); ?>
  <div class="games-grid<?php echo $tCols ? ' is-grid' : ''; ?>"<?php echo $tCols ? ' style="--cols:' . $tCols . ';"' : ''; ?>>
    <?php $i = 0; while ($trending->have_posts()) : $trending->the_post(); $id = get_the_ID(); ?>
    <a href="<?php the_permalink(); ?>" class="game-big-card" style="text-decoration:none;color:inherit;">
      <?php list($cardUrl, $cardFit, $cardBg) = km26_card_logo($id);
        $thumbBg = $cardFit === 'contain' ? $cardBg : $grads[$i % 6]; ?>
      <div class="game-thumb <?php echo $cardFit === 'contain' ? 'is-contain' : 'is-cover'; ?>" style="background: <?php echo esc_attr($thumbBg); ?>;">
        <?php if ($cardUrl) : ?><img src="<?php echo esc_url($cardUrl); ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:<?php echo $cardFit; ?>;"><?php else : ?>🎮<?php endif; ?>
        <div class="game-thumb-overlay"></div>
      </div>
      <div class="game-info">
        <div class="game-title"><?php the_title(); ?></div>
        <div class="game-meta">
          <span class="mod-type"><?php echo esc_html(km26_mod_feature($id)); ?></span>
          <span class="updated-badge">TODAY</span>
        </div>
        <span class="download-btn">↓ Download</span>
      </div>
    </a>
    <?php $i++; endwhile; wp_reset_postdata(); ?>
  </div>
</section>
<?php endif; endif; ?>

<?php
/* ───────── NEW & POPULAR — recently updated Mod Games, popular-card grid ───────── */
if (($sections['latest'] ?? '0') === '1' || ($sections['updated'] ?? '0') === '1') :
    $popular = new WP_Query([
        'post_type'      => 'post',
        'category_name'  => 'mod-games',
        'posts_per_page' => 6,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ]);
    if ($popular->have_posts()) : ?>
<section class="new-popular" id="new-popular">
  <div class="trending-header">
    <div>
      <div class="section-label">Fresh Drops</div>
      <h2>NEW &amp; POPULAR</h2>
    </div>
    <a href="<?php echo esc_url(home_url('/all-mods/')); ?>" class="view-all">View all new mods →</a>
  </div>
  <?php $pCols = (int) get_option('killermod_popular_cols', 0); ?>
  <div class="new-popular-grid<?php echo $pCols ? ' is-grid' : ''; ?>"<?php echo $pCols ? ' style="--cols:' . $pCols . ';"' : ''; ?>>
    <?php $j = 0; while ($popular->have_posts()) : $popular->the_post(); $id = get_the_ID(); ?>
    <a href="<?php the_permalink(); ?>" class="popular-card" style="text-decoration:none;color:inherit;">
      <?php list($cardUrl, $cardFit, $cardBg) = km26_card_logo($id);
        $thumbBg = $cardFit === 'contain' ? $cardBg : $grads[$j % 6]; ?>
      <div class="popular-card-thumb <?php echo $cardFit === 'contain' ? 'is-contain' : 'is-cover'; ?>" style="background: <?php echo esc_attr($thumbBg); ?>;">
        <?php if ($cardUrl) : ?><img src="<?php echo esc_url($cardUrl); ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:<?php echo $cardFit; ?>;"><?php else : ?>🎮<?php endif; ?>
        <div class="popular-card-thumb-overlay"></div>
      </div>
      <div class="popular-card-body">
        <div class="popular-card-title"><?php the_title(); ?></div>
        <div class="popular-card-meta">
          <span class="popular-card-type"><?php echo esc_html(km26_mod_feature($id)); ?></span>
          <span class="new-badge">NEW</span>
        </div>
        <div class="popular-stats">
          <span class="pop-stat">⬇ <strong><?php echo esc_html(km26_mod_stat($id, 'downloads')); ?></strong></span>
          <span class="pop-stat">⭐ <strong><?php echo esc_html(km26_mod_stat($id, 'rating')); ?></strong></span>
        </div>
        <span class="download-btn">↓ Download Mod</span>
      </div>
    </a>
    <?php $j++; endwhile; wp_reset_postdata(); ?>
  </div>
</section>
<?php endif; endif; ?>

<!-- ════════════════════════════════════════ APP SECTION (with phone mockup) ════════════════════════════════════════ -->
<section class="app-section" id="app">
  <div class="app-text">
    <div class="section-label">Mobile First</div>
    <h2>THE APP<br>EXPERIENCE</h2>
    <p>A dedicated app that feels like a premium gaming platform — not a recycled website. Fast, clean, built for 2026.</p>
    <div class="app-features">
      <div class="app-feat">
        <div class="app-feat-icon">⚡</div>
        <div>One-tap install — download &amp; launch in seconds</div>
      </div>
      <div class="app-feat">
        <div class="app-feat-icon">🔔</div>
        <div>Instant notifications when your game gets updated</div>
      </div>
      <div class="app-feat">
        <div class="app-feat-icon">🤖</div>
        <div>AI game guide — asks "what does this mod do?"</div>
      </div>
      <div class="app-feat">
        <div class="app-feat-icon">🛡️</div>
        <div>VIP-only sandbox mode right inside the app</div>
      </div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <button class="btn-primary locked" title="App unlocks in a few days" style="padding:12px 24px;font-size:14px;">📱 Download App <span class="lock-ico">🔒</span></button>
      <button class="btn-ghost locked" title="App unlocks in a few days" style="padding:12px 24px;font-size:14px;">Preview App <span class="lock-ico">🔒</span></button>
    </div>
  </div>

  <div class="app-mockup">
    <div class="phone-frame">
      <div class="phone-notch"></div>
      <div class="phone-content">
        <div class="phone-header">
          <div class="phone-logo">KILLER<span>MOD</span></div>
          <div style="display:flex;gap:8px;font-size:16px;">🔍 👤</div>
        </div>

        <div class="phone-banner">
          <div class="phone-banner-tag">VIP — SAME DAY</div>
          <div class="phone-banner-title">FREE FIRE MAX<br>V2.106.1 Updated</div>
          <div style="font-size:9px;color:var(--accent);margin-top:6px;">✦ Unlimited Diamonds • God Mode • Auto Aim</div>
        </div>

        <div class="phone-section-title">Trending <span>View All →</span></div>
        <div class="phone-games-row">
          <div class="phone-game-chip">
            <div class="phone-game-icon" style="background:linear-gradient(135deg,#1a3300,#0a1a00);">🎯</div>
            <div class="phone-game-label">Free Fire</div>
          </div>
          <div class="phone-game-chip">
            <div class="phone-game-icon" style="background:linear-gradient(135deg,#00162a,#000d1a);">⚔️</div>
            <div class="phone-game-label">CoC</div>
          </div>
          <div class="phone-game-chip">
            <div class="phone-game-icon" style="background:linear-gradient(135deg,#1a1a00,#0d0d00);">🏎️</div>
            <div class="phone-game-label">Hill Climb</div>
          </div>
          <div class="phone-game-chip">
            <div class="phone-game-icon" style="background:linear-gradient(135deg,#1a0033,#0d001a);">🎪</div>
            <div class="phone-game-label">Among Us</div>
          </div>
        </div>

        <div class="phone-list">
          <div class="phone-list-item">
            <div class="phone-list-icon" style="background:linear-gradient(135deg,#2a1400,#1a0a00);">🏙️</div>
            <div class="phone-list-info">
              <div class="phone-list-name">GTA: San Andreas</div>
              <div class="phone-list-sub">Mod Menu • Updated today</div>
            </div>
            <div class="phone-list-dl">↓ GET</div>
          </div>
          <div class="phone-list-item">
            <div class="phone-list-icon" style="background:linear-gradient(135deg,#001a1a,#000d0d);">🧟</div>
            <div class="phone-list-info">
              <div class="phone-list-name">Dead Cells</div>
              <div class="phone-list-sub">God Mode • 1hr ago</div>
            </div>
            <div class="phone-list-dl">↓ GET</div>
          </div>
          <div class="phone-list-item">
            <div class="phone-list-icon" style="background:linear-gradient(135deg,#001433,#000a1a);">⚽</div>
            <div class="phone-list-info">
              <div class="phone-list-name">FC Mobile 25</div>
              <div class="phone-list-sub">Unlimited Coins • 2hr ago</div>
            </div>
            <div class="phone-list-dl">↓ GET</div>
          </div>
        </div>
      </div>
      <!-- APP NAV BAR — the app buttons (Home / Games / VIP / Profile) -->
      <div class="phone-bottom-nav">
        <button class="phone-tab-btn active"><div class="phone-tab-dot"></div>🏠<span>Home</span></button>
        <button class="phone-tab-btn">🎮<span>Games</span></button>
        <button class="phone-tab-btn">👑<span>VIP</span></button>
        <button class="phone-tab-btn">👤<span>Profile</span></button>
      </div>
    </div>
    <div class="phone-glow"></div>
  </div>
</section>

<?php get_footer(); ?>
