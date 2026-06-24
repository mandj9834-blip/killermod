<?php
/**
 * LEGACY header — exact copy of the original theme's header.php.
 * Used ONLY for Mod Games single posts (get_header('legacy')) so they
 * remain 100% identical to the old site.
 */
defined('ABSPATH') || exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ─── NAVBAR ─── -->
<nav>
  <a class="nav-logo" href="<?php echo esc_url(home_url('/')); ?>">Killer<span>Mod</span></a>

  <div class="nav-spacer"></div>

  <div class="nav-right">
    <button class="theme-toggle" id="themeToggle" title="Toggle theme">
      <span class="toggle-track">
        <span class="toggle-thumb"></span>
        <span class="toggle-sun">☀️</span>
        <span class="toggle-moon">🌙</span>
      </span>
    </button>
    <div class="nav-search" id="navSearch">
      <div class="search-expand" id="searchExpand">
        <input type="text" placeholder="Search MOD games, apps..." id="searchInput">
      </div>
      <button class="search-icon-btn" id="searchToggle" title="Search">🔍</button>
    </div>
  </div>
</nav>

<!-- ─── PAGE WRAPPER ─── -->
<div class="page-wrapper">

  <!-- ─── MAIN CONTENT ─── -->
  <main class="main-content">
