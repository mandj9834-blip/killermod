<?php
/**
 * LEGACY footer — exact copy of the original theme's footer.php.
 * Pairs with header-legacy.php for Mod Games single posts.
 */
defined('ABSPATH') || exit;
?>
    <footer class="site-footer" style="background: var(--surface); border-top: 1px solid var(--border); margin-top: 60px; font-size: 13px; color: var(--text); padding: 30px 20px 24px; clear: both; text-align: center;">
      <div class="footer-top" style="max-width: 1000px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 20px;">
        <div class="footer-links" style="display: flex; justify-content: center; align-items: center; gap: 25px; flex-wrap: wrap; margin-bottom: 5px;">
          <a href="#" style="color: var(--text); text-decoration: none; font-weight: 600; font-family: 'Exo 2', sans-serif;">Terms of Services</a>
          <a href="#" style="color: var(--text); text-decoration: none; font-weight: 600; font-family: 'Exo 2', sans-serif;">Contact Us</a>
          <a href="#" style="color: var(--text); text-decoration: none; font-weight: 600; font-family: 'Exo 2', sans-serif;">Privacy Policy</a>
          <a href="#" style="color: var(--text); text-decoration: none; font-weight: 600; font-family: 'Exo 2', sans-serif;">Advertisement</a>
          <a href="#" style="color: var(--text); text-decoration: none; font-weight: 600; font-family: 'Exo 2', sans-serif;">DMCA</a>
          <a href="#" style="color: var(--text); text-decoration: none; font-weight: 600; font-family: 'Exo 2', sans-serif;">Payment Agreement</a>
          <button class="btn-feedback" style="background: var(--surface2); border: 1px solid var(--border); padding: 6px 14px; border-radius: 20px; font-size: 11px; font-family: 'Exo 2', sans-serif; color: var(--text); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; vertical-align: middle;">💬 Feedback</button>
        </div>
        <div class="footer-socials" style="display: flex; justify-content: center; align-items: center; gap: 16px; margin-top: 10px;">
          <a href="https://t.me/JashanMods" target="_blank" class="social-icon telegram" title="Telegram" style="padding: 0; background: none; display: flex; align-items: center; justify-content: center; border: none; box-shadow: none; text-decoration: none;">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/telgarm.jfif'); ?>" alt="Telegram" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%;" onerror="this.style.display='none'">
          </a>
          <a href="https://youtube.com/@JashanMods" target="_blank" class="social-icon youtube" title="YouTube" style="padding: 0; background: none; display: flex; align-items: center; justify-content: center; border: none; box-shadow: none; text-decoration: none;">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/youtube.png'); ?>" alt="YouTube" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%;" onerror="this.style.display='none'">
          </a>
        </div>
      </div>
      <div class="footer-bottom-bar" style="background: #111116; color: #8888aa; text-align: center; padding: 14px 20px; font-size: 11px; border-top: 1px solid var(--border); font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; margin-top: 20px;">
        © COPYRIGHT 2017-<?php echo date('Y'); ?> KILLERMOD.COM
      </div>
    </footer>

  </main><!-- /main-content -->

</div><!-- end page-wrapper -->

<?php wp_footer(); ?>
</body>
</html>
