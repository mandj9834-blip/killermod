<?php
/**
 * NEW 2026 footer — pairs with header.php.
 */
defined('ABSPATH') || exit;
?>

<!-- ════════════════════════════════════════ FOOTER ════════════════════════════════════════ -->
<footer>
  <div class="footer-logo">KILLER<span>MOD</span></div>
  <p>© <?php echo date('Y'); ?> KillerMod. Premium Mod Platform.</p>
  <div style="display:flex;gap:24px;">
    <a href="#" style="color:var(--muted);font-size:12px;text-decoration:none;">Privacy</a>
    <a href="#" style="color:var(--muted);font-size:12px;text-decoration:none;">Terms</a>
    <a href="#" style="color:var(--muted);font-size:12px;text-decoration:none;">Contact</a>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
