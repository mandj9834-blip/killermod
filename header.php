<?php
/**
 * NEW 2026 header — used on the home page, other-category single posts,
 * pages, archives, etc. (NOT on Mod Games single posts; those use header-legacy.php)
 */
defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#080a0f">
<!-- Fonts: Bebas Neue, Syne, DM Sans (exact same set + weights as index.html) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<?php wp_head(); ?>
</head>
<body <?php body_class('light-mode'); ?>>
<?php wp_body_open(); ?>
<script>
/* Flash-free theme bootstrap.
   Light mode is the default (class set above). If the user previously
   opted into dark mode via the toggle, strip the class BEFORE the page
   paints so they never see a flash of white. */
(function () {
  try {
    if (localStorage.getItem('km26-theme') === 'dark') {
      document.body.classList.remove('light-mode');
    }
  } catch (e) {}
})();
</script>

<!-- ════════════════════════════════════════ NAV ════════════════════════════════════════ -->
<nav>
  <a class="logo" href="<?php echo esc_url(home_url('/')); ?>" style="text-decoration:none;">KILLER<span>MOD</span></a>
  <div class="nav-right">
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark/light mode">
      <span class="toggle-knob"></span>
      <span class="toggle-icons"><span>🌙</span><span>☀️</span></span>
    </button>
    <button class="btn-ghost locked" title="Sign In unlocks when subscriptions go live" style="display:inline-flex;align-items:center;">Sign In <span class="lock-ico">🔒</span></button>
    <a href="<?php echo esc_url(home_url('/vip/')); ?>" class="btn-primary locked" title="VIP unlocks in a few days" style="text-decoration:none;display:inline-flex;align-items:center;">Get VIP <span class="lock-ico">🔒</span></a>
  </div>
</nav>
