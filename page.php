<?php
/**
 * Static page template — new 2026 chrome.
 */
defined('ABSPATH') || exit;

get_header(); ?>
<main class="km-page-main" style="padding:120px 20px 60px;max-width:860px;margin:0 auto;">
  <?php while (have_posts()) : the_post(); ?>
    <article class="entry-content">
      <h1 class="entry-title" style="font-family:'Syne',sans-serif;font-weight:800;font-size:34px;margin-bottom:18px;"><?php the_title(); ?></h1>
      <div style="line-height:1.7;font-size:15px;color:var(--text);"><?php the_content(); ?></div>
    </article>
  <?php endwhile; ?>
</main>
<?php get_footer();
