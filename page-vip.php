<?php
/**
 * VIP page template (slug: "vip") — reproduces vip.html exactly,
 * wrapped in the new 2026 header/footer. Fonts: Rajdhani + Exo 2.
 */
defined('ABSPATH') || exit;

get_header(); ?>

<!-- ════════════════════════════════════════ PLANS / PRICING ════════════════════════════════════════ -->
<section class="pricing" id="plans">
  <div class="pricing-header">
    <div class="section-label">Membership</div>
    <h1>VIP PLANS</h1>
    <p>Unlock every mod, every update, every day. Cancel anytime.</p>
    <!-- Billing period toggle -->
    <div class="pricing-toggle" id="pricingToggle">
      <button class="toggle-btn active" data-period="monthly">Monthly</button>
      <button class="toggle-btn" data-period="yearly">Yearly · Save 30%</button>
    </div>
  </div>

  <div class="plans-grid">

    <!-- Plan: Free -->
    <div class="plan-card">
      <div class="plan-name">FREE</div>
      <div class="plan-desc">Get a taste of the platform</div>
      <div class="plan-price">
        <span class="price-amount" data-monthly="₹0" data-yearly="₹0">₹0</span>
        <span class="price-period" data-monthly="/ forever" data-yearly="/ forever">/ forever</span>
        <div class="price-usd" data-monthly="No card required" data-yearly="No card required">No card required</div>
      </div>
      <ul class="plan-features">
        <li class="active"><span class="feat-icon yes">✓</span> Access to 10 basic mods</li>
        <li class="active"><span class="feat-icon yes">✓</span> Standard download speed</li>
        <li><span class="feat-icon no">✕</span> Same-day updates</li>
        <li><span class="feat-icon no">✕</span> VIP sandbox mode</li>
        <li><span class="feat-icon no">✕</span> Ad-free experience</li>
      </ul>
      <button class="plan-cta outline">Get Started</button>
    </div>

    <!-- Plan: VIP (featured) -->
    <div class="plan-card featured">
      <div class="plan-badge">Most Popular</div>
      <div class="plan-name">VIP</div>
      <div class="plan-desc">For serious modders</div>
      <div class="plan-price">
        <span class="price-amount" data-monthly="₹149" data-yearly="₹1,249">₹149</span>
        <span class="price-period" data-monthly="/ month" data-yearly="/ year">/ month</span>
        <div class="price-usd" data-monthly="≈ $1.79 / mo" data-yearly="≈ $14.99 / yr · 2 months free">≈ $1.79 / mo</div>
      </div>
      <ul class="plan-features">
        <li class="active"><span class="feat-icon yes">✓</span> All 60+ premium mods</li>
        <li class="active"><span class="feat-icon yes">✓</span> Same-day updates</li>
        <li class="active"><span class="feat-icon yes">✓</span> VIP sandbox mode</li>
        <li class="active"><span class="feat-icon yes">✓</span> Priority download speed</li>
        <li><span class="feat-icon no">✕</span> Early access drops</li>
      </ul>
      <button class="plan-cta solid">Get VIP ↗</button>
    </div>

    <!-- Plan: Pro -->
    <div class="plan-card">
      <div class="plan-name">PRO</div>
      <div class="plan-desc">Everything, unlocked</div>
      <div class="plan-price">
        <span class="price-amount" data-monthly="₹299" data-yearly="₹2,499">₹299</span>
        <span class="price-period" data-monthly="/ month" data-yearly="/ year">/ month</span>
        <div class="price-usd" data-monthly="≈ $3.59 / mo" data-yearly="≈ $29.99 / yr · 2 months free">≈ $3.59 / mo</div>
      </div>
      <ul class="plan-features">
        <li class="active"><span class="feat-icon yes">✓</span> Everything in VIP</li>
        <li class="active"><span class="feat-icon yes">✓</span> Early access drops</li>
        <li class="active"><span class="feat-icon yes">✓</span> AI game guide</li>
        <li class="active"><span class="feat-icon yes">✓</span> Dedicated support</li>
        <li class="active"><span class="feat-icon yes">✓</span> Multi-device sync</li>
      </ul>
      <button class="plan-cta outline">Go Pro</button>
    </div>

  </div>

  <!-- ── FAQ ── -->
  <div class="faq">
    <h2 class="faq-title">QUESTIONS</h2>
    <div class="faq-item">
      <div class="faq-q">Can I cancel anytime?</div>
      <div class="faq-a">Yes. Cancel from your account in one tap — you keep VIP access until the end of the billing period, no questions asked.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">What does "same-day updates" mean?</div>
      <div class="faq-a">When a game pushes a new version, we re-build and re-test the mod the same day so your downloads never break.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q">Which payment methods do you accept?</div>
      <div class="faq-a">UPI, cards, and net banking for India, plus international cards for everyone else. Prices shown in ₹ with an approximate USD figure.</div>
    </div>
  </div>
</section>

<?php get_footer();
