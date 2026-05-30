/**
 * Bootstraps static pages: scripts, login forms, demo banner, auth guards.
 */
(function () {
  const base = document.querySelector('meta[name="spca-base"]')?.content || '';
  window.SPCA_BASE_PATH = base;

  function loadScript(src) {
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = base + src;
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function showDemoBanner() {
    if (document.getElementById('spca-demo-banner')) return;
    const el = document.createElement('div');
    el.id = 'spca-demo-banner';
    el.innerHTML =
      '<strong>Static demo</strong> — data is stored in your browser. ' +
      '<a href="#" id="spca-reset-data">Reset mock data</a>';
    document.body.prepend(el);
    el.querySelector('#spca-reset-data').addEventListener('click', async (e) => {
      e.preventDefault();
      if (confirm('Reset all demo data to defaults?')) {
        await window.spcaStore.reset();
        location.reload();
      }
    });
  }

  function wireLoginForm() {
    const form =
      document.querySelector('form[action*="Login"]') ||
      document.querySelector('.login-container form');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const errEl = document.getElementById('spca-login-error');
      try {
        await SpcaAuth.login(
          fd.get('username'),
          fd.get('password'),
          fd.get('position')
        );
      } catch (err) {
        if (errEl) errEl.textContent = err.message;
        else alert(err.message);
      }
    });
  }

  function wireLogoutLinks() {
    document.querySelectorAll('a[href*="logout"]').forEach((a) => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        SpcaAuth.logout();
      });
    });
  }

  async function init() {
    await loadScript('js/spca-store.js');
    await loadScript('js/spca-auth.js');
    await window.spcaStore.ready;
    showDemoBanner();
    wireLoginForm();
    wireLogoutLinks();
    await loadScript('js/spca-pages.js');
    if (window.SpcaPages && window.SpcaPages.initPage) {
      await window.SpcaPages.initPage();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
