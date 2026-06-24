<?php
/**
 * Template Name: Fast Download
 *
 * Terminal-style app download funnel. Reads ?mod=<slug>, pulls KillerMod
 * Manager meta, and renders: scan panel, download CTA, Pro pitch, recommended.
 *
 * Header + Footer: unchanged — get_header() / get_footer() only.
 * Inner design: terminal scan-panel (lime/green accent, JetBrains Mono).
 *
 * URL: /fast-download/?mod=<slug>   (auto-created by functions.php)
 */
defined('ABSPATH') || exit;

$slug     = isset($_GET['mod']) ? sanitize_title(wp_unslash($_GET['mod'])) : '';
$mod_post = null;

if ($slug) {
    $found = get_posts([
        'name'           => $slug,
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
    ]);
    if ($found) $mod_post = $found[0];
}

if (!$mod_post) {
    $found = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'meta_key'       => '_km_show_in_app',
        'meta_value'     => '1',
    ]);
    if ($found) $mod_post = $found[0];
}

get_header();

if (!$mod_post) { ?>
<main class="km-fd2" style="padding:120px 24px;text-align:center;">
    <h1 style="font-family:'Syne',sans-serif;color:var(--text);">No app-enabled mods yet</h1>
    <p style="color:var(--muted);margin-top:10px;">Turn on "Show in App" for a mod in KillerMod Manager to use this page.</p>
</main>
<?php
    get_footer();
    exit;
}

$pid         = $mod_post->ID;
$version     = get_post_meta($pid, '_km_version', true);
$size_raw    = get_post_meta($pid, '_km_size', true);
$size        = $size_raw ? (is_numeric($size_raw) ? $size_raw . ' MB' : $size_raw) : '';
$package     = get_post_meta($pid, '_km_package', true);
$features    = get_post_meta($pid, '_km_mod_features', true);
$features_l  = !empty($features) ? array_filter(array_map('trim', explode("\n", $features))) : [];
$feature_1st = !empty($features_l) ? reset($features_l) : '';
$cats        = get_the_category($pid);
$cat_name    = !empty($cats) ? $cats[0]->name : 'Mod Games';
$thumb       = get_the_post_thumbnail_url($pid, 'thumbnail');
// Per-post app link (set in KillerMod Manager plugin) takes priority over global APK URL.
$app_link    = get_post_meta($pid, '_km_app_link', true);
$apk_url     = get_option('killermod_app_apk_url', '');
$cta_url     = $app_link ?: $apk_url;

$recommended = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'orderby'        => 'modified',
    'order'          => 'DESC',
    'meta_key'       => '_km_show_in_app',
    'meta_value'     => '1',
    'post__not_in'   => [$pid],
]);
?>

<main class="km-fd2">

<style>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&display=swap');

/* ── Scoped design tokens — green/lime palette ── */
.km-fd2 {
  --fd-bg:      #0a0c08;
  --fd-panel:   #12150e;
  --fd-panel2:  #171b11;
  --fd-line:    #252b1c;
  --fd-lime:    #a6e22e;
  --fd-lime-d:  #6f9b22;
  --fd-gold:    #e3ab2f;
  --fd-text:    #eef0e6;
  --fd-muted:   #8d9482;
  --fd-mono-bg: #0c0f08;
  --fd-r:       14px;

  background:   var(--fd-bg);
  color:        var(--fd-text);
  font-family:  'DM Sans', sans-serif;
  padding-bottom: 48px;
  min-height:   60vh;
}

/* Light mode — triggered by existing header toggle (body.light-mode) */
body.light-mode .km-fd2 {
  --fd-bg:      #f1f3ee;
  --fd-panel:   #ffffff;
  --fd-panel2:  #f4f6ef;
  --fd-line:    #dfe3d6;
  --fd-lime:    #5a7d1a;
  --fd-lime-d:  #7a9c33;
  --fd-gold:    #b9821c;
  --fd-text:    #15170f;
  --fd-muted:   #6b7264;
  --fd-mono-bg: #eceee5;
}

/* Faint scanline texture */
.km-fd2::before {
  content: "";
  position: fixed; inset: 0; pointer-events: none; z-index: 0;
  background-image: repeating-linear-gradient(
    180deg,
    rgba(166,226,46,0.025) 0px, rgba(166,226,46,0.025) 1px,
    transparent 1px, transparent 3px
  );
}
body.light-mode .km-fd2::before {
  background-image: repeating-linear-gradient(
    180deg,
    rgba(90,125,26,0.035) 0px, rgba(90,125,26,0.035) 1px,
    transparent 1px, transparent 3px
  );
}

/* Content wrap */
.km-fd2-wrap {
  max-width: 520px;
  margin: 0 auto;
  padding: 0 0 20px;
  position: relative;
  z-index: 1;
}

/* ── BREADCRUMB ── */
.fd2-crumb {
  padding: 80px 20px 0;
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  color: var(--fd-muted);
  letter-spacing: 0.3px;
}
.fd2-crumb a { color: var(--fd-muted); text-decoration: none; }
.fd2-crumb a:hover { color: var(--fd-lime); }
.fd2-crumb .sep { color: var(--fd-lime-d); margin: 0 4px; }
.fd2-crumb .here { color: var(--fd-lime); }

/* ── HERO CARD ── */
.fd2-hero {
  margin: 16px 20px 0;
  background: var(--fd-panel);
  border: 1px solid var(--fd-line);
  border-radius: var(--fd-r);
  padding: 18px;
  display: flex;
  gap: 16px;
  align-items: center;
}
.fd2-icon {
  width: 72px; height: 72px;
  border-radius: 16px;
  overflow: hidden;
  flex-shrink: 0;
  border: 1px solid var(--fd-line);
  background: #000;
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
  position: relative;
}
.fd2-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }
.fd2-icon::after {
  content: "";
  position: absolute; inset: 0; border-radius: 16px;
  box-shadow: 0 0 0 1px rgba(166,226,46,0.15) inset;
}
.fd2-hero-meta h1 {
  font-family: 'Syne', sans-serif;
  font-weight: 800;
  font-size: 21px;
  letter-spacing: 0.2px;
  line-height: 1.15;
  margin-bottom: 6px;
  color: var(--fd-text);
}
.fd2-hero-meta .fd2-updated {
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  color: var(--fd-muted);
}
.fd2-hero-meta .fd2-updated .dot { color: var(--fd-lime); margin-right: 5px; }

/* ── SCAN PANEL ── */
.fd2-scan {
  margin: 16px 20px 0;
  background: var(--fd-mono-bg);
  border: 1px solid var(--fd-line);
  border-radius: var(--fd-r);
  overflow: hidden;
}
.fd2-scan-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px;
  border-bottom: 1px solid var(--fd-line);
  background: var(--fd-panel2);
}
.fd2-scan-head .label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 10.5px;
  letter-spacing: 1.5px;
  color: var(--fd-muted);
  text-transform: uppercase;
}
.fd2-scan-dots { display: flex; gap: 5px; }
.fd2-scan-dots i {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--fd-line);
  font-style: normal;
}
.fd2-scan-dots i.live {
  background: var(--fd-lime);
  box-shadow: 0 0 6px var(--fd-lime);
}
.fd2-scan-body {
  padding: 14px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 12.5px;
  line-height: 1.9;
}
.fd2-scan-row {
  display: flex;
  justify-content: space-between;
  opacity: 0;
  animation: fd2reveal 0.4s ease forwards;
}
.fd2-scan-row .k { color: var(--fd-muted); }
.fd2-scan-row .v { color: var(--fd-text); font-weight: 500; }
.fd2-scan-row .v.ok { color: var(--fd-lime); }
.fd2-scan-row .v.tag {
  background: rgba(166,226,46,0.12);
  color: var(--fd-lime);
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 11px;
}
body.light-mode .fd2-scan-row .v.tag { background: rgba(90,125,26,0.12); }
.fd2-scan-row:nth-child(1) { animation-delay: 0.05s; }
.fd2-scan-row:nth-child(2) { animation-delay: 0.18s; }
.fd2-scan-row:nth-child(3) { animation-delay: 0.31s; }
.fd2-scan-row:nth-child(4) { animation-delay: 0.44s; }
.fd2-scan-row:nth-child(5) { animation-delay: 0.57s; }
.fd2-scan-rule { height: 1px; background: var(--fd-line); margin: 10px 0; }
@keyframes fd2reveal {
  from { opacity: 0; transform: translateY(2px); }
  to   { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
  .fd2-scan-row { animation: none; opacity: 1; }
}

/* ── NOTICE ── */
.fd2-notice {
  margin: 14px 20px 0;
  background: rgba(227,171,47,0.08);
  border: 1px solid rgba(227,171,47,0.25);
  border-radius: 12px;
  padding: 13px 14px;
  display: flex; gap: 10px; align-items: flex-start;
}
body.light-mode .fd2-notice {
  background: rgba(185,130,28,0.08);
  border-color: rgba(185,130,28,0.3);
}
.fd2-notice .bolt { color: var(--fd-gold); font-size: 15px; line-height: 1.4; flex-shrink: 0; }
.fd2-notice p { font-size: 12.5px; color: #cdb98a; line-height: 1.5; }
body.light-mode .fd2-notice p { color: #8a611a; }
.fd2-notice strong { color: var(--fd-gold); }

/* ── CTA ── */
.fd2-cta-wrap { margin: 16px 20px 0; }
.fd2-cta {
  width: 100%; padding: 14px;
  border-radius: 30px; border: none;
  background: linear-gradient(135deg, var(--fd-lime) 0%, #82c11f 100%);
  color: #0a0c08;
  font-family: 'Syne', sans-serif;
  font-weight: 800; font-size: 15px;
  letter-spacing: 0.3px;
  display: flex; align-items: center; justify-content: center; gap: 9px;
  cursor: pointer;
  box-shadow: 0 6px 18px -6px rgba(166,226,46,0.4);
  text-decoration: none;
  transition: opacity 0.2s, box-shadow 0.2s;
}
.fd2-cta:hover { opacity: 0.9; box-shadow: 0 10px 24px -6px rgba(166,226,46,0.5); }
body.light-mode .fd2-cta { color: #f1f3ee; }
.fd2-cta .arrow { font-size: 14px; }
.fd2-cta-foot {
  display: flex; justify-content: center; gap: 14px;
  margin-top: 10px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 10.5px;
  color: var(--fd-muted);
}
.fd2-cta-foot span { display: flex; align-items: center; gap: 4px; }
.fd2-cta-foot .ok { color: var(--fd-lime); }

/* ── APP PROMO BAR ── */
.fd2-promo {
  margin: 18px 20px 0;
  background: var(--fd-panel);
  border: 1px solid var(--fd-line);
  border-radius: 12px;
  padding: 13px 14px;
  display: flex; align-items: center; gap: 12px;
}
.fd2-km-mark {
  width: 34px; height: 34px;
  border-radius: 9px;
  background: #0a0c08;
  border: 1px solid var(--fd-line);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Syne', sans-serif;
  font-weight: 800; font-size: 13px;
  flex-shrink: 0;
  color: var(--fd-text);
}
body.light-mode .fd2-km-mark { background: #f1f3ee; }
.fd2-km-mark span { color: var(--fd-lime); }
.fd2-promo p { font-size: 12.5px; color: var(--fd-muted); line-height: 1.4; }
.fd2-promo strong { color: var(--fd-text); }

/* ── PRO CARD ── */
.fd2-pro {
  margin: 22px 20px 0;
  background: radial-gradient(120% 140% at 0% 0%, #1c1305 0%, #0d0e08 60%);
  border: 1px solid rgba(227,171,47,0.2);
  border-radius: var(--fd-r);
  padding: 22px;
}
body.light-mode .fd2-pro {
  background: radial-gradient(120% 140% at 0% 0%, #fff3da 0%, #f6f4ec 60%);
}
.fd2-pro-tag {
  display: flex; align-items: center; gap: 6px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px; letter-spacing: 1.5px;
  color: var(--fd-gold);
  text-transform: uppercase;
  margin-bottom: 12px;
}
.fd2-pro h3 {
  font-family: 'Syne', sans-serif;
  font-weight: 800; font-size: 23px; line-height: 1.25;
  margin-bottom: 16px;
  color: #ffffff;
}
body.light-mode .fd2-pro h3 { color: #15170f; }
.fd2-pro h3 em { font-style: normal; color: var(--fd-gold); }
.fd2-pro-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 16px;
  margin-bottom: 18px;
}
.fd2-pro-item {
  display: flex; gap: 8px; align-items: flex-start;
  font-size: 12.5px; color: #cfd2c3; line-height: 1.4;
}
body.light-mode .fd2-pro-item { color: #4a5040; }
.fd2-pro-item .check { color: var(--fd-gold); font-weight: 700; flex-shrink: 0; }
.fd2-pro-cta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.fd2-pro-btn {
  padding: 11px 18px;
  border-radius: 10px;
  background: var(--fd-gold);
  color: #1a1304;
  font-weight: 700; font-size: 13.5px;
  border: none;
  font-family: 'DM Sans', sans-serif;
  cursor: default;
}
body.light-mode .fd2-pro-btn { color: #231806; }
.fd2-pro-fine {
  font-family: 'JetBrains Mono', monospace;
  font-size: 10.5px; color: var(--fd-muted);
}

/* ── RECOMMENDED ── */
.fd2-rec-label {
  margin: 28px 20px 12px;
  font-size: 13px; font-weight: 700;
  color: var(--fd-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.fd2-rec {
  margin: 0 20px 10px;
  display: flex; align-items: center; gap: 14px;
  background: var(--fd-panel);
  border: 1px solid var(--fd-line);
  border-radius: 14px;
  padding: 12px 14px;
  text-decoration: none;
  color: inherit;
  transition: border-color 0.2s;
}
.fd2-rec:hover { border-color: rgba(166,226,46,0.3); }
.fd2-rec .fd2-rec-thumb {
  width: 50px; height: 50px;
  border-radius: 10px; flex-shrink: 0;
  background: #1a1a1a center/cover no-repeat;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
}
.fd2-rec h4 { font-size: 14px; font-weight: 700; margin: 0 0 3px; color: var(--fd-text); }
.fd2-rec .fd2-rec-tags { font-size: 11.5px; color: var(--fd-muted); }
.fd2-rec .fd2-rec-arrow { margin-left: auto; color: var(--fd-muted); font-size: 18px; }

/* ── MOBILE ── */
@media (max-width: 480px) {
  .fd2-crumb { padding-top: 72px; font-size: 10px; }
  .fd2-hero  { margin: 14px 16px 0; padding: 14px; gap: 12px; }
  .fd2-icon  { width: 60px; height: 60px; border-radius: 13px; }
  .fd2-hero-meta h1 { font-size: 18px; }
  .fd2-scan, .fd2-notice, .fd2-cta-wrap, .fd2-promo, .fd2-pro { margin-left: 16px; margin-right: 16px; }
  .fd2-scan-body { padding: 12px; font-size: 11.5px; }
  .fd2-pro { padding: 18px; }
  .fd2-pro h3 { font-size: 20px; }
  .fd2-pro-grid { grid-template-columns: 1fr; gap: 10px; }
  .fd2-pro-cta { flex-direction: column; align-items: flex-start; gap: 8px; }
  .fd2-rec-label, .fd2-rec { margin-left: 16px; margin-right: 16px; }
}
@media (max-width: 360px) {
  .fd2-hero-meta h1 { font-size: 16px; }
  .fd2-cta { font-size: 14px; padding: 14px; }
}
</style>

<div class="km-fd2-wrap">

  <!-- BREADCRUMB -->
  <div class="fd2-crumb">
    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
    <span class="sep">/</span>
    <?php echo esc_html($cat_name); ?>
    <span class="sep">/</span>
    <span class="here"><?php echo esc_html($mod_post->post_title); ?></span>
  </div>

  <!-- HERO CARD -->
  <div class="fd2-hero">
    <div class="fd2-icon">
      <?php if ($thumb) : ?>
        <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($mod_post->post_title); ?> icon">
      <?php else : ?>🎮<?php endif; ?>
    </div>
    <div class="fd2-hero-meta">
      <h1><?php echo esc_html($mod_post->post_title); ?></h1>
      <div class="fd2-updated"><span class="dot">●</span>Updated <?php echo esc_html(get_the_modified_date('M j, Y', $pid)); ?></div>
    </div>
  </div>

  <!-- SCAN PANEL -->
  <div class="fd2-scan">
    <div class="fd2-scan-head">
      <span class="label">mod_info.json</span>
      <div class="fd2-scan-dots"><i></i><i></i><i class="live"></i></div>
    </div>
    <div class="fd2-scan-body">
      <?php if ($version) : ?>
        <div class="fd2-scan-row"><span class="k">version</span><span class="v"><?php echo esc_html($version); ?></span></div>
      <?php endif; ?>
      <div class="fd2-scan-row"><span class="k">category</span><span class="v"><?php echo esc_html($cat_name); ?></span></div>
      <?php if ($size) : ?>
        <div class="fd2-scan-row"><span class="k">size</span><span class="v"><?php echo esc_html($size); ?></span></div>
      <?php endif; ?>
      <?php if ($package) : ?>
        <div class="fd2-scan-row"><span class="k">package</span><span class="v"><?php echo esc_html($package); ?></span></div>
      <?php endif; ?>
      <div class="fd2-scan-rule"></div>
      <div class="fd2-scan-row"><span class="k">mod_status</span><span class="v ok">&#9679; injected</span></div>
      <?php if ($feature_1st) : ?>
        <div class="fd2-scan-row"><span class="k">feature</span><span class="v tag"><?php echo esc_html($feature_1st); ?></span></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- NOTICE -->
  <div class="fd2-notice">
    <span class="bolt">&#9889;</span>
    <p>This mod downloads inside the <strong>KillerMod App</strong> &mdash; faster, no ads, no waiting.</p>
  </div>

  <!-- CTA BUTTON — uses _km_app_link (per-post, from KillerMod Manager), then global APK URL -->
  <div class="fd2-cta-wrap">
    <?php if ($cta_url) : ?>
      <a class="fd2-cta" href="<?php echo esc_url($cta_url); ?>" target="_blank" rel="noopener noreferrer">
        Fast Download <span class="arrow">&#8595;</span>
      </a>
    <?php else : ?>
      <button class="fd2-cta" id="kmFd2Btn">
        Fast Download <span class="arrow">&#8595;</span>
      </button>
    <?php endif; ?>
    <div class="fd2-cta-foot">
      <span><span class="ok">&#9679;</span> No ads</span>
      <span><span class="ok">&#9679;</span> Verified build</span>
    </div>
  </div>

  <!-- APP PROMO BAR -->
  <div class="fd2-promo">
    <div class="fd2-km-mark">K<span>M</span></div>
    <p>Downloads inside the <strong>KillerMod App</strong> &mdash; faster, no ads</p>
  </div>

  <!-- PRO CARD -->
  <div class="fd2-pro">
    <div class="fd2-pro-tag">
      <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>
      KillerMod Pro
    </div>
    <h3>Sign up and get <em>1 month</em> of Pro free</h3>
    <div class="fd2-pro-grid">
      <div class="fd2-pro-item"><span class="check">&#10003;</span>Unlimited mods</div>
      <div class="fd2-pro-item"><span class="check">&#10003;</span>No ads</div>
      <div class="fd2-pro-item"><span class="check">&#10003;</span>One-click download</div>
      <div class="fd2-pro-item"><span class="check">&#10003;</span>Mod updates within a day</div>
    </div>
    <div class="fd2-pro-cta">
      <button class="fd2-pro-btn">Coming with the app</button>
      <span class="fd2-pro-fine">No card required &middot; cancel anytime</span>
    </div>
  </div>

  <!-- RECOMMENDED -->
  <?php if ($recommended) : ?>
    <div class="fd2-rec-label">Recommended for you</div>
    <?php foreach ($recommended as $r) :
        $r_thumb = get_the_post_thumbnail_url($r->ID, 'thumbnail');
        $r_size  = get_post_meta($r->ID, '_km_size', true);
        $r_feats = get_post_meta($r->ID, '_km_mod_features', true);
        $r_feat1 = $r_feats ? trim(explode("\n", $r_feats)[0]) : '';
    ?>
    <a class="fd2-rec" href="<?php echo esc_url(home_url('/fast-download/?mod=' . $r->post_name)); ?>">
      <div class="fd2-rec-thumb" <?php if ($r_thumb) : ?>style="background-image:url('<?php echo esc_url($r_thumb); ?>')"<?php endif; ?>>
        <?php echo $r_thumb ? '' : '🎮'; ?>
      </div>
      <div>
        <h4><?php echo esc_html($r->post_title); ?></h4>
        <div class="fd2-rec-tags"><?php echo esc_html(trim(($r_size ? $r_size . ' · ' : '') . $r_feat1, ' ·')); ?></div>
      </div>
      <span class="fd2-rec-arrow">&#8250;</span>
    </a>
    <?php endforeach; ?>
  <?php endif; ?>

</div><!-- /.km-fd2-wrap -->

</main>

<?php if (!$cta_url) : ?>
<script>
(function () {
  var btn = document.getElementById('kmFd2Btn');
  if (!btn) return;
  btn.addEventListener('click', function () {
    btn.textContent = "App isn’t public yet — check back soon.";
    btn.style.fontSize = '13px';
    btn.style.opacity = '0.7';
    btn.disabled = true;
  });
})();
</script>
<?php endif; ?>

<?php get_footer(); ?>
