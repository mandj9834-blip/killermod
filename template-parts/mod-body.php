<?php
/**
 * Shared MOD POST BODY — copied verbatim from the original theme's single.php body.
 * Used for EVERY single post (Mod Games and other categories) so the body layout
 * never changes. Only the surrounding header/footer differs by category.
 *
 * Self-contained: the .km-legacy-body wrapper pins the original colour palette,
 * so it looks identical regardless of which theme chrome wraps it.
 *
 * Must run inside The Loop (after the_post()).
 */
defined('ABSPATH') || exit;

// Retrieve custom fields managed by KillerMod Manager
$post_id   = get_the_ID();
$link1     = get_post_meta($post_id, '_km_download_link', true);
$link2     = get_post_meta($post_id, '_km_download_link2', true);
$yt        = get_post_meta($post_id, '_km_youtube_url', true);
$tg        = get_post_meta($post_id, '_km_telegram_url', true);
$features  = get_post_meta($post_id, '_km_mod_features', true);
$banner_id = get_post_meta($post_id, '_km_banner_id', true);

// "Show in App" — when ON, this post becomes app-only: the Telegram/Mirror
// website buttons are replaced with a single "Get it in the App" button,
// and KillerMod Manager already suppresses ads on this page automatically.
$show_in_app = get_post_meta($post_id, '_km_show_in_app', true) === '1';
$app_link    = get_post_meta($post_id, '_km_app_link', true);
$version     = get_post_meta($post_id, '_km_version', true);
$size        = get_post_meta($post_id, '_km_size', true);
if (!$app_link) {
    $app_link = home_url('/fast-download/?mod=' . get_post_field('post_name', $post_id));
}

// Global brand / channel settings (KillerMod Manager → Homepage Settings).
// Used as the fallback for the Jashan banner + per-post social buttons.
$g_youtube  = get_option('killermod_global_youtube', 'https://youtube.com/@JashanMods');
$g_telegram = get_option('killermod_global_telegram', 'https://t.me/JashanMods');
$brand_name = get_option('killermod_banner_title', 'Jashan Mods');
$brand_tag  = wp_specialchars_decode(get_option('killermod_banner_tagline', 'Real & Premium Mods for the community'));

// Avatar initials = first letter of up to two words (e.g. "Jashan Mods" → "JM").
$brand_initials = '';
foreach (preg_split('/\s+/', trim($brand_name)) as $w) {
    if ($w !== '') { $brand_initials .= $w[0]; }
}
$brand_initials = strtoupper(substr($brand_initials !== '' ? $brand_initials : $brand_name, 0, 2));

$thumb_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
if (!$thumb_url) {
    $thumb_url = get_stylesheet_directory_uri() . '/assets/images/default-thumb.png'; // fallback
}

$banner_url = $banner_id ? wp_get_attachment_image_url($banner_id, 'full') : '';
if (!$banner_url) {
    $banner_url = get_the_post_thumbnail_url($post_id, 'large');
}

// Parse features list into an array
$features_list = !empty($features) ? array_filter(array_map('trim', explode("\n", $features))) : [];

// OS compatibility check / display defaults
$os_compat = 'Android 5.0+';
?>

<div class="km-legacy-body">
<style>
  /* Brand colours stay fixed; the structural palette (--surface, --text,
     --border, --muted, --bg) is intentionally NOT redefined here, so it
     INHERITS from the active 2026 theme. That makes the body clean & light
     when the site is in light mode (and clean & dark when toggled), instead
     of being locked to a single dark palette under the new chrome. */
  .km-legacy-body {
    --primary: #ff3c3c;
    --primary-glow: rgba(255,60,60,0.35);
    --accent: #ffb800;
    --accent2: #00e5ff;
    --green: #00e676;
    /* --surface, --text, --border, --muted are deliberately NOT set here —
       they inherit from the 2026 theme (:root / body.light-mode), so the body
       follows the site's light/dark toggle automatically. */
    color: var(--text);
    font-family: 'Exo 2', sans-serif;
  }

  /* Styling for single post template (inline styles matched to premium mockup) */
  .breadcrumb {
    padding: 16px 20px 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .breadcrumb span { color: var(--primary); }

  .post-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 10px 16px 60px;
  }

  .post-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 32px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 6px;
  }
  .post-meta-top {
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 20px;
  }

  /* Info Box */
  .info-box {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
  }
  .info-thumb {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    object-fit: cover;
    border: 1px solid var(--border);
  }
  .info-details { flex: 1; }
  .info-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
  }
  .info-sub {
    font-size: 12px;
    color: var(--muted);
    margin-bottom: 2px;
  }
  .btn-header-dl {
    background: linear-gradient(135deg, #00e676, #00b0ff);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 8px 16px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(0,230,118,0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  /* Features Box */
  .features-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 20px;
    margin-bottom: 24px;
  }
  .features-title {
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
  }
  .features-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .features-list li {
    font-size: 14px;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .features-list li::before {
    content: '●';
    color: var(--green);
    font-size: 12px;
  }

  /* Social Banner */
  .jashan-banner {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    text-align: center;
    margin-bottom: 24px;
  }
  @media (min-width: 600px) {
    .jashan-banner {
      flex-direction: row;
      justify-content: space-between;
      text-align: left;
    }
  }
  .jashan-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .jashan-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #e8820c, #f5a235);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    color: white;
  }
  .jashan-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
  }
  .jashan-desc {
    font-size: 11px;
    color: var(--muted);
  }
  .jashan-btns { display: flex; gap: 8px; }
  .jashan-btn {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    color: white;
  }
  .yt-btn { background: #e60000; }
  .tg-btn { background: #2aabee; }

  /* Post Image Banner (Youtube Thumbnail Style) */
  .post-banner-wrapper {
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 24px;
    border: 1px solid var(--border);
  }
  .post-banner-img { width: 100%; display: block; }
  .card-embedded-btn {
    position: absolute;
    bottom: 16px;
    left: 16px;
    background: linear-gradient(135deg, #0088cc, #005588);
    color: white;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
  }

  /* Download Buttons Stack */
  .download-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
  }
  .dl-button {
    width: 100%;
    border-radius: 14px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    border: none;
    color: white;
    font-family: 'Exo 2', sans-serif;
    transition: transform 0.2s, opacity 0.2s;
  }
  .dl-button:hover { transform: translateY(-2px); opacity: 0.95; }
  .dl-button.primary {
    background: linear-gradient(135deg, #00c853, #009624);
    box-shadow: 0 4px 14px rgba(0,200,83,0.3);
  }
  .dl-button.mirror {
    background: linear-gradient(135deg, #008cc9, #005f8c);
    box-shadow: 0 4px 14px rgba(0,140,201,0.3);
  }
  .dl-button.app {
    background: linear-gradient(135deg, #a855f7, #7c3aed);
    box-shadow: 0 4px 14px rgba(168,85,247,0.35);
  }
  .app-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(168,85,247,0.12);
    color: #a855f7;
    border: 1px solid rgba(168,85,247,0.3);
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 100px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-left: 8px;
    vertical-align: middle;
  }
  .dl-btn-title { font-size: 16px; font-weight: 800; margin-bottom: 2px; }
  .dl-btn-sub { font-size: 11px; opacity: 0.8; }

  /* Info Notes Block */
  .info-notes {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 18px;
  }
  .notes-title {
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
  }
  .notes-list {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 10px;
    font-size: 13px;
    margin-bottom: 12px;
    color: var(--text);
  }
  .notes-list li strong { color: var(--accent); }
  .notes-recommendation {
    font-size: 12px;
    border-top: 1px solid var(--border);
    padding-top: 10px;
    margin-top: 10px;
    color: var(--text);
  }
  .notes-recommendation strong { color: var(--green); }

  /* Hide duplicate layout elements from old post body */
  .km-legacy-body .entry-content-body .breadcrumb,
  .km-legacy-body .entry-content-body .info-box,
  .km-legacy-body .entry-content-body .features-card,
  .km-legacy-body .entry-content-body .jashan-banner,
  .km-legacy-body .entry-content-body .download-section,
  .km-legacy-body .entry-content-body .info-notes,
  .km-legacy-body .entry-content-body .post-banner-wrapper,
  .km-legacy-body .entry-content-body table,
  .km-legacy-body .entry-content-body .rating,
  .km-legacy-body .entry-content-body .reviews,
  .km-legacy-body .entry-content-body .additional-info,
  .km-legacy-body .entry-content-body .virus-check,
  .km-legacy-body .entry-content-body .download-btn,
  .km-legacy-body .entry-content-body .social-box,
  .km-legacy-body .entry-content-body img {
    display: none !important;
  }
</style>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  <?php
  $categories = get_the_category();
  $cat_crumb = !empty($categories) ? $categories[0]->name : 'Mod Games';
  echo esc_html($cat_crumb);
  ?> / <?php the_title(); ?>
</div>

<!-- MAIN POST CONTAINER -->
<div class="post-container">

  <h1 class="post-title"><?php the_title(); ?></h1>
  <div class="post-meta-top">By <?php the_author(); ?> · <?php the_time(get_option('date_format')); ?><?php if ($show_in_app) : ?><span class="app-badge">📲 In App</span><?php endif; ?></div>

  <!-- Header Box -->
  <div class="info-box">
    <?php if ($thumb_url) : ?>
      <img src="<?php echo esc_url($thumb_url); ?>" class="info-thumb" alt="<?php the_title_attribute(); ?> Thumbnail">
    <?php endif; ?>
    <div class="info-details">
      <div class="info-title"><?php the_title(); ?></div>
      <div class="info-sub">● <?php echo esc_html($os_compat); ?><?php echo $version ? ' · ' . esc_html($version) : ''; ?></div>
      <div class="info-sub">● <?php echo $size ? esc_html($size) . ' · ' : ''; ?>MOD APK - Free</div>
    </div>
    <button class="btn-header-dl" onclick="scrollToDownload()">↓ MOD APK</button>
  </div>

  <!-- Mod Features Box -->
  <?php if (!empty($features_list)) : ?>
    <div class="features-card">
      <div class="features-title">MOD Features</div>
      <ul class="features-list">
        <?php foreach ($features_list as $feat_item) : ?>
          <li><?php echo esc_html($feat_item); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Mini Social Jashan Banner -->
  <div class="jashan-banner">
    <div class="jashan-info">
      <div class="jashan-avatar"><?php echo esc_html($brand_initials); ?></div>
      <div>
        <div class="jashan-name"><?php echo esc_html($brand_name); ?></div>
        <div class="jashan-desc"><?php echo esc_html($brand_tag); ?></div>
      </div>
    </div>
    <div class="jashan-btns" style="display: flex; gap: 8px; align-items: center;">
      <a href="<?php echo esc_url($yt ?: $g_youtube); ?>" class="jashan-btn yt-btn" style="text-decoration:none; display: flex; align-items: center; gap: 6px; padding: 6px 12px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="white" style="vertical-align: middle; flex-shrink: 0;"><path d="M23.498 6.163a3.003 3.003 0 0 0-2.11-2.11C19.517 3.545 12 3.545 12 3.545s-7.517 0-9.388.508a3.003 3.003 0 0 0-2.11 2.11C0 8.033 0 12 0 12s0 3.967.502 5.837a3.003 3.003 0 0 0 2.11 2.11c1.871.508 9.388.508 9.388.508s7.517 0 9.388-.508a3.003 3.003 0 0 0 2.11-2.11C24 15.967 24 12 24 12s0-3.967-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg> YouTube
      </a>
      <a href="<?php echo esc_url($tg ?: $g_telegram); ?>" class="jashan-btn tg-btn" style="text-decoration:none; display: flex; align-items: center; gap: 6px; padding: 6px 12px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="white" style="vertical-align: middle; flex-shrink: 0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.37.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .24z"/></svg> Telegram
      </a>
    </div>
  </div>

  <!-- Embedded YouTube Card Style Banner -->
  <?php if ($banner_url) : ?>
    <div class="post-banner-wrapper">
      <img src="<?php echo esc_url($banner_url); ?>" class="post-banner-img" alt="<?php the_title_attribute(); ?> Cover">
    </div>
  <?php endif; ?>

  <!-- Download Options Stack -->
  <div class="download-section" id="downloadSection">
    <?php if ($show_in_app) : ?>

      <!-- APP-ONLY MODE: single button, no website download, deep-links into the app -->
      <button class="dl-button app" onclick="window.location.href='<?php echo esc_url($app_link); ?>'" style="display: flex; align-items: center; justify-content: center; gap: 12px; text-align: left;">
        <span style="font-size:24px;line-height:1;flex-shrink:0;">📲</span>
        <div>
          <div class="dl-btn-title">GET IT IN THE APP</div>
          <div class="dl-btn-sub">Opens the KillerMod App — faster, no ads</div>
        </div>
      </button>

    <?php else : ?>

      <?php if ($link1) : ?>
        <button class="dl-button primary" onclick="window.open('<?php echo esc_url($link1); ?>', '_blank')" style="display: flex; align-items: center; justify-content: center; gap: 12px; text-align: left;">
          <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/telgarm.jfif'); ?>" alt="Telegram" style="width: 28px; height: 28px; object-fit: contain; border-radius: 50%; flex-shrink: 0;" onerror="this.style.display='none'">
          <div>
            <div class="dl-btn-title">DOWNLOAD MOD APK</div>
            <div class="dl-btn-sub">Click to start — Direct Link · No Password (via Telegram)</div>
          </div>
        </button>
      <?php endif; ?>

      <?php if ($link2) : ?>
        <button class="dl-button mirror" onclick="window.open('<?php echo esc_url($link2); ?>', '_blank')" style="display: flex; align-items: center; justify-content: center; gap: 12px; text-align: left;">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">
            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4C9.11 4 6.6 5.64 5.35 8.04C2.34 8.36 0 10.91 0 14C0 17.31 2.69 20 6 20H19C21.76 20 24 17.76 24 15C24 12.36 21.95 10.22 19.35 10.04ZM19 18H6C3.79 18 2 16.21 2 14C2 11.95 3.53 10.24 5.56 10.03L6.63 9.92L7.13 8.97C8.08 7.14 9.94 6 12 6C14.89 6 17.39 8.26 17.85 11.11L18.09 12.63L19.64 12.72C20.97 12.8 22 13.93 22 15.25C22 16.76 20.65 18 19 18Z" fill="white"/>
          </svg>
          <div>
            <div class="dl-btn-title">MIRROR DOWNLOAD</div>
            <div class="dl-btn-sub">Click to start — Backup Link (via Terabox)</div>
          </div>
        </button>
      <?php endif; ?>

    <?php endif; ?>
  </div>

  <!-- Note Box -->
  <div class="info-notes">
    <?php if ($show_in_app) : ?>
      <div class="notes-title">📌 Download Info</div>
      <ul class="notes-list">
        <li><strong>Get it in the App</strong> — downloads happen inside the KillerMod App. No ads, no waiting, no password.</li>
      </ul>
      <div class="notes-recommendation">
        ⭐ <strong>New here?</strong> Tapping the button installs the app if you don't have it yet — then sends you straight to this mod.
      </div>
    <?php else : ?>
      <div class="notes-title">📌 Download Info</div>
      <ul class="notes-list">
        <li><strong>Button 1 - Telegram</strong> — Join our Telegram channel & download the mod directly from there.</li>
        <li><strong>Button 2 - Terabox</strong> — Direct backup mirror, download the mod file straight from Terabox.</li>
      </ul>
      <div class="notes-recommendation">
        ⭐ Our Recommendation — <strong>Use Telegram</strong>. Fastest, always updated & no wait time!
      </div>
    <?php endif; ?>
  </div>

  <!-- Normal Post Body Content (Optional) -->
  <div class="entry-content-body" style="margin-top: 30px; line-height: 1.6; font-size: 14px; color: var(--text);">
    <?php the_content(); ?>
  </div>

</div>

<script>
  function scrollToDownload() {
    var dlSec = document.getElementById('downloadSection');
    if (dlSec) { dlSec.scrollIntoView({ behavior: 'smooth' }); }
  }
</script>
</div><!-- /.km-legacy-body -->
