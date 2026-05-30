/**
 * Session handling for static demo (sessionStorage).
 */
(function (global) {
  const SESSION_KEY = 'spca_session';

  const REDIRECTS = {
    'Administrative Staff': 'Cruelty Reports/admin_dashboard.html',
    'Veterinary Staff': 'medicalrecords/vetdashboard.html',
    'Volunteer Staff': 'Adopt and Volunteer/volunteer_dashboard.html',
  };

  function basePath() {
    return global.SPCA_BASE_PATH || '';
  }

  function abs(path) {
    if (path.startsWith('http') || path.startsWith('/')) return path;
    return basePath() + path;
  }

  function getSession() {
    try {
      const raw = sessionStorage.getItem(SESSION_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  }

  function setSession(user) {
    sessionStorage.setItem(
      SESSION_KEY,
      JSON.stringify({
        username: user.username,
        position: user.position,
        firstname: user.firstname,
        surname: user.surname,
      })
    );
  }

  function clearSession() {
    sessionStorage.removeItem(SESSION_KEY);
  }

  async function login(username, password, position) {
    await global.spcaStore.ready;
    const user = global.spcaStore.findUser(username.trim(), position);
    if (!user || !global.spcaStore.verifyPassword(user, password)) {
      throw new Error('Invalid credentials. Try again.');
    }
    setSession(user);
    const dest = REDIRECTS[position];
    if (!dest) throw new Error('Unknown role.');
    window.location.href = abs(dest);
  }

  function logout() {
    clearSession();
    window.location.href = abs('frontPage.html');
  }

  function requireRole(roles) {
    const session = getSession();
    if (!session) {
      window.location.href = abs('registerUser/LoginUser.html');
      return null;
    }
    if (roles && roles.length && !roles.includes(session.position)) {
      window.location.href = abs('registerUser/LoginUser.html');
      return null;
    }
    return session;
  }

  global.SpcaAuth = {
    getSession,
    setSession,
    clearSession,
    login,
    logout,
    requireRole,
    abs,
    REDIRECTS,
  };
})(typeof window !== 'undefined' ? window : globalThis);
