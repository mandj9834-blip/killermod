<?php
/**
 * Single post router.
 *
 *  EVERY single post now uses the NEW 2026 chrome (header.php / footer.php)
 *  wrapped around the SAME shared mod body. The body (template-parts/mod-body.php)
 *  inherits the active theme palette, so it looks clean in both light & dark mode
 *  on every category — Mod Games, Jashan Mods, and everything else.
 *
 *  Only the header & footer differ from the old site; the body layout is identical.
 */
defined('ABSPATH') || exit;

get_header(); ?>
<main class="km-single-main" style="padding-top:96px;min-height:60vh;">
  <?php while (have_posts()) : the_post();
      get_template_part('template-parts/mod-body');
  endwhile; ?>
</main>
<?php
get_footer();
