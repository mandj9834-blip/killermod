// KillerMod 2026 — theme interactions
(function () {
  // ── THEME TOGGLE (light is default, dark is opt-in) ──
  // The inline script in header.php already removed `light-mode` from
  // <body> if the user previously chose dark — that prevents a flash.
  // Here we just wire the toggle button to flip the class and persist
  // the choice.
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      document.body.classList.toggle('light-mode');
      var isLight = document.body.classList.contains('light-mode');
      try {
        localStorage.setItem('km26-theme', isLight ? 'light' : 'dark');
      } catch (e) {}
    });
  }

  // ── SCROLL REVEAL ANIMATIONS ──
  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.game-big-card, .popular-card').forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.5s ease, transform 0.5s ease, border-color 0.3s';
      observer.observe(el);
    });
  }

  // ── PRICING BILLING TOGGLE (monthly / yearly) — VIP page only ──
  var pricingToggle = document.getElementById('pricingToggle');
  if (pricingToggle) {
    var toggleButtons = pricingToggle.querySelectorAll('.toggle-btn');
    toggleButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var period = btn.dataset.period; // "monthly" | "yearly"
        toggleButtons.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('.price-amount, .price-period, .price-usd').forEach(function (el) {
          if (el.dataset[period] !== undefined) {
            el.textContent = el.dataset[period];
          }
        });
      });
    });
  }

  // ── PHONE MOCKUP: app nav tabs highlight on tap (demo) ──
  var phoneTabs = document.querySelectorAll('.phone-tab-btn');
  phoneTabs.forEach(function (b) {
    b.addEventListener('click', function () {
      phoneTabs.forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');
    });
  });
})();
