export type SpaContext = 'public' | 'admin' | 'staff';

export type SpaPathKey =
  | 'landing'
  | 'register'
  | 'forgotQr'
  | 'report'
  | 'adminLogin'
  | 'staffLogin'
  | 'staffHome';

export type SpaUrlKey =
  | 'publicHome'
  | 'registerForm'
  | 'registerApi'
  | 'forgotQrLookupApi'
  | 'reportSubmitApi'
  | 'forgotPassword'
  | 'login'
  | 'adminLoginSubmit'
  | 'adminDashboard'
  | 'staffLogin'
  | 'staffHome'
  | 'staffLoginSubmit'
  | 'staffLogout'
  | 'staffSession'
  | 'staffScannerPost'
  | 'staffScan'
  | 'staffManualLookup'
  | 'staffManualConfirm'
  | 'staffDashboard'
  | 'staffHistory'
  | 'staffStats';

type SpaConfig = {
  context: SpaContext;
  pwa: {
    enabled: boolean;
    manifestUrl: string;
  };
  paths: Partial<Record<SpaPathKey, string[]>>;
  urls: Partial<Record<SpaUrlKey, string>>;
};

const FALLBACK_CONFIG: SpaConfig = {
  context: 'public',
  pwa: {
    enabled: false,
    manifestUrl: '',
  },
  paths: {
    landing: ['/'],
    register: ['/register'],
    forgotQr: ['/forgot-qr'],
    report: ['/report'],
    adminLogin: ['/login'],
    staffLogin: ['/staff/login'],
    staffHome: ['/staff'],
  },
  urls: {
    publicHome: '/',
    registerForm: '/register',
    registerApi: '/api/register',
    forgotQrLookupApi: '/api/forgot-qr/lookup',
    reportSubmitApi: '/api/report',
    forgotPassword: '/forgot-password',
    login: '/login',
    adminLoginSubmit: '/admin/login',
    adminDashboard: '/admin/dashboard',
    staffLogin: '/staff/login',
    staffHome: '/staff',
    staffLoginSubmit: '/staff/login',
    staffLogout: '/staff/logout',
    staffSession: '/staff/session',
    staffScannerPost: '/staff/session/scanner-post',
    staffScan: '/staff/scan',
    staffManualLookup: '/staff/manual-lookup',
    staffManualConfirm: '/staff/manual-confirm',
    staffDashboard: '/staff/dashboard',
    staffHistory: '/staff/history',
    staffStats: '/staff/stats',
  },
};

let cachedConfig: SpaConfig | null = null;

function normalizePaths(value: unknown): string[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .filter((item): item is string => typeof item === 'string')
    .map((item) => item.trim())
    .filter((item) => item.length > 0);
}

function normalizeUrls(value: unknown): Partial<Record<SpaUrlKey, string>> {
  if (!value || typeof value !== 'object') {
    return {};
  }

  return Object.fromEntries(
    Object.entries(value)
      .filter((entry): entry is [SpaUrlKey, string] => typeof entry[1] === 'string')
      .map(([key, url]) => [key, url.trim()]),
  );
}

function readRawConfig() {
  if (typeof document === 'undefined') {
    return null;
  }

  const script = document.getElementById('app-spa-config');

  if (!script?.textContent) {
    return null;
  }

  try {
    return JSON.parse(script.textContent) as Partial<SpaConfig>;
  } catch {
    return null;
  }
}

function buildConfig(): SpaConfig {
  const raw = readRawConfig();

  if (!raw || typeof raw !== 'object') {
    return FALLBACK_CONFIG;
  }

  const rawContext = raw.context;
  const context: SpaContext = rawContext === 'admin' || rawContext === 'staff' ? rawContext : 'public';

  const rawPaths: Partial<Record<SpaPathKey, unknown>> = raw.paths && typeof raw.paths === 'object'
    ? raw.paths as Partial<Record<SpaPathKey, unknown>>
    : {};
  const paths: Partial<Record<SpaPathKey, string[]>> = {
    landing: normalizePaths(rawPaths.landing),
    register: normalizePaths(rawPaths.register),
    forgotQr: normalizePaths(rawPaths.forgotQr),
    report: normalizePaths(rawPaths.report),
    adminLogin: normalizePaths(rawPaths.adminLogin),
    staffLogin: normalizePaths(rawPaths.staffLogin),
    staffHome: normalizePaths(rawPaths.staffHome),
  };

  return {
    context,
    pwa: {
      enabled: Boolean(raw.pwa?.enabled),
      manifestUrl: typeof raw.pwa?.manifestUrl === 'string' ? raw.pwa.manifestUrl.trim() : '',
    },
    paths,
    urls: normalizeUrls(raw.urls),
  };
}

export function getSpaConfig() {
  cachedConfig ??= buildConfig();

  return cachedConfig;
}

export function getSpaPaths(key: SpaPathKey) {
  return getSpaConfig().paths[key] ?? [];
}

export function getSpaUrl(key: SpaUrlKey, fallback = '') {
  return getSpaConfig().urls[key] || fallback;
}
