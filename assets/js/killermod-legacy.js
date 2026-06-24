document.addEventListener('DOMContentLoaded', function () {

  // ── Theme Toggle ──
  const btn = document.getElementById('themeToggle');
  const body = document.body;
  if (localStorage.getItem('km-theme') !== 'dark') {
    body.classList.add('light');
  }
  if (btn) {
    btn.addEventListener('click', () => {
      const isLight = body.classList.toggle('light');
      localStorage.setItem('km-theme', isLight ? 'light' : 'dark');
    });
  }

  // ── Search Toggle ──
  const searchToggle = document.getElementById('searchToggle');
  const searchExpand = document.getElementById('searchExpand');
  const searchInput  = document.getElementById('searchInput');

  if (searchToggle && searchExpand && searchInput) {
    searchToggle.addEventListener('click', () => {
      const isOpen = searchExpand.classList.toggle('open');
      searchToggle.classList.toggle('active', isOpen);
      if (isOpen) setTimeout(() => searchInput.focus(), 320);
    });

    document.addEventListener('click', (e) => {
      const navSearch = document.getElementById('navSearch');
      if (navSearch && !navSearch.contains(e.target)) {
        searchExpand.classList.remove('open');
        searchToggle.classList.remove('active');
      }
    });

    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        searchExpand.classList.remove('open');
        searchToggle.classList.remove('active');
        searchInput.blur();
      }
    });
  }

});
