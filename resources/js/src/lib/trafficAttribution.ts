export interface TrafficAttributionPayload {
  traffic_source: string;
  traffic_source_detail: string;
  traffic_medium: string;
  traffic_campaign: string;
  traffic_referrer_host: string;
  traffic_landing_path: string;
  traffic_captured_at: string;
}

const TRAFFIC_ATTRIBUTION_STORAGE_KEY = 'songkran-registration-first-touch-v1';
const TRAFFIC_ATTRIBUTION_COOKIE_KEY = 'songkran_registration_first_touch';
const TRAFFIC_ATTRIBUTION_COOKIE_MAX_AGE = 60 * 60 * 24 * 90;

function readMetaContent(name: string) {
  if (typeof document === 'undefined') {
    return '';
  }

  return (document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null)?.content?.trim() ?? '';
}

export function normalizeTrafficToken(value: string) {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export function normalizeTrafficSource(value: string) {
  const normalized = normalizeTrafficToken(value);

  if (normalized === '' || normalized === 'direct') {
    return 'direct';
  }

  if (
    normalized === 'ig'
    || normalized === 'insta'
    || normalized.includes('instagram')
    || normalized.includes('ig-story')
  ) {
    return 'instagram';
  }

  if (
    normalized === 'wa'
    || normalized.includes('whatsapp')
    || normalized.includes('wa-broadcast')
  ) {
    return 'whatsapp';
  }

  if (normalized === 'fb' || normalized.includes('facebook')) {
    return 'facebook';
  }

  if (normalized === 'tt' || normalized.includes('tiktok')) {
    return 'tiktok';
  }

  if (normalized === 'thread' || normalized.includes('threads')) {
    return 'threads';
  }

  if (
    normalized === 'twitter'
    || normalized === 'x'
    || normalized === 'tweet'
    || normalized.includes('twitter')
  ) {
    return 'x';
  }

  if (normalized.includes('linkedin')) {
    return 'linkedin';
  }

  if (normalized.includes('youtube')) {
    return 'youtube';
  }

  if (normalized.includes('google')) {
    return 'google';
  }

  if (
    normalized === 'web'
    || normalized === 'website'
    || normalized === 'site'
    || normalized === 'homepage'
    || normalized === 'landing-page'
  ) {
    return 'website';
  }

  return normalized;
}

function resolveSourceFromReferrerHost(referrerHost: string) {
  if (referrerHost === '') {
    return 'direct';
  }

  if (/(^|\.)((www|l)\.)?instagram\.com$|(^|\.)ig\.me$/i.test(referrerHost)) {
    return 'instagram';
  }

  if (/(^|\.)((api|l)\.)?whatsapp\.com$|(^|\.)wa\.me$/i.test(referrerHost)) {
    return 'whatsapp';
  }

  if (/(^|\.)((m|l|lm)\.)?facebook\.com$|(^|\.)fb\.com$/i.test(referrerHost)) {
    return 'facebook';
  }

  if (/(^|\.)((www|vm|vt)\.)?tiktok\.com$/i.test(referrerHost)) {
    return 'tiktok';
  }

  if (/(^|\.)((www|l)\.)?threads\.net$/i.test(referrerHost)) {
    return 'threads';
  }

  if (/(^|\.)((www|mobile)\.)?(twitter\.com|x\.com)$/i.test(referrerHost) || referrerHost === 't.co') {
    return 'x';
  }

  if (/(^|\.)((www|m)\.)?linkedin\.com$/i.test(referrerHost)) {
    return 'linkedin';
  }

  if (/(^|\.)((www|m)\.)?youtube\.com$|(^|\.)youtu\.be$/i.test(referrerHost)) {
    return 'youtube';
  }

  if (/(^|\.)google\.[a-z.]+$/i.test(referrerHost)) {
    return 'google';
  }

  return 'website';
}

function inferTrafficMedium(source: string, referrerHost: string) {
  switch (source) {
    case 'instagram':
    case 'whatsapp':
    case 'facebook':
    case 'tiktok':
    case 'threads':
    case 'x':
    case 'linkedin':
    case 'youtube':
      return 'social';
    case 'google':
      return 'search';
    case 'website':
      return referrerHost !== '' ? 'referral' : 'website';
    case 'direct':
      return 'direct';
    default:
      return referrerHost !== '' ? 'referral' : 'other';
  }
}

function isIpAddress(hostname: string) {
  return /^\d{1,3}(\.\d{1,3}){3}$/.test(hostname);
}

function resolveCookieDomainFromHostname(hostname: string) {
  const normalizedHostname = hostname.toLowerCase().trim();

  if (
    normalizedHostname === ''
    || normalizedHostname === 'localhost'
    || isIpAddress(normalizedHostname)
  ) {
    return '';
  }

  const labels = normalizedHostname.split('.').filter(Boolean);

  if (labels.length < 2) {
    return '';
  }

  const topLevelLabel = labels[labels.length - 1] ?? '';
  const secondLevelLabel = labels[labels.length - 2] ?? '';

  if (topLevelLabel.length === 2 && secondLevelLabel.length <= 3 && labels.length >= 3) {
    return labels.slice(-3).join('.');
  }

  return labels.slice(-2).join('.');
}

function isInternalHost(currentHost: string, candidateHost: string) {
  if (currentHost === '' || candidateHost === '') {
    return false;
  }

  if (currentHost === candidateHost) {
    return true;
  }

  if (
    currentHost.endsWith(`.${candidateHost}`)
    || candidateHost.endsWith(`.${currentHost}`)
  ) {
    return true;
  }

  const currentBaseDomain = resolveCookieDomainFromHostname(currentHost);
  const candidateBaseDomain = resolveCookieDomainFromHostname(candidateHost);

  return currentBaseDomain !== '' && currentBaseDomain === candidateBaseDomain;
}

function normalizeTrafficAttributionPayload(
  payload: Partial<TrafficAttributionPayload> | null,
) {
  if (!payload || typeof payload !== 'object') {
    return null;
  }

  return {
    traffic_source: normalizeTrafficSource(String(payload.traffic_source ?? '')),
    traffic_source_detail: String(payload.traffic_source_detail ?? '').trim(),
    traffic_medium: normalizeTrafficToken(String(payload.traffic_medium ?? '')),
    traffic_campaign: String(payload.traffic_campaign ?? '').trim(),
    traffic_referrer_host: String(payload.traffic_referrer_host ?? '').trim().toLowerCase(),
    traffic_landing_path: String(payload.traffic_landing_path ?? '').trim(),
    traffic_captured_at: String(payload.traffic_captured_at ?? '').trim(),
  } satisfies TrafficAttributionPayload;
}

function readTrafficAttributionCookie() {
  if (typeof document === 'undefined') {
    return null;
  }

  const cookieValue = document.cookie
    .split(';')
    .map((entry) => entry.trim())
    .find((entry) => entry.startsWith(`${TRAFFIC_ATTRIBUTION_COOKIE_KEY}=`));

  if (!cookieValue) {
    return null;
  }

  try {
    const rawValue = cookieValue.slice(TRAFFIC_ATTRIBUTION_COOKIE_KEY.length + 1);
    const parsed = JSON.parse(decodeURIComponent(rawValue)) as Partial<TrafficAttributionPayload>;

    return normalizeTrafficAttributionPayload(parsed);
  } catch {
    return null;
  }
}

function readTrafficAttributionLocalStorage() {
  if (typeof window === 'undefined') {
    return null;
  }

  try {
    const rawValue = window.localStorage.getItem(TRAFFIC_ATTRIBUTION_STORAGE_KEY);

    if (!rawValue) {
      return null;
    }

    const parsed = JSON.parse(rawValue) as Partial<TrafficAttributionPayload>;

    return normalizeTrafficAttributionPayload(parsed);
  } catch {
    return null;
  }
}

function hasMeaningfulTrafficAttribution(payload: TrafficAttributionPayload | null) {
  if (!payload) {
    return false;
  }

  return (
    (payload.traffic_source !== '' && payload.traffic_source !== 'direct')
    || payload.traffic_campaign !== ''
    || payload.traffic_referrer_host !== ''
  );
}

function parseTrafficCapturedAt(payload: TrafficAttributionPayload | null) {
  if (!payload || payload.traffic_captured_at === '') {
    return Number.POSITIVE_INFINITY;
  }

  const timestamp = Date.parse(payload.traffic_captured_at);

  return Number.isNaN(timestamp) ? Number.POSITIVE_INFINITY : timestamp;
}

function pickStoredTrafficAttribution(
  candidates: Array<TrafficAttributionPayload | null>,
) {
  const normalizedCandidates = candidates.filter((candidate): candidate is TrafficAttributionPayload => Boolean(candidate));

  if (normalizedCandidates.length === 0) {
    return null;
  }

  const meaningfulCandidates = normalizedCandidates.filter(hasMeaningfulTrafficAttribution);
  const preferredCandidates = meaningfulCandidates.length > 0 ? meaningfulCandidates : normalizedCandidates;

  return preferredCandidates.sort(
    (left, right) => parseTrafficCapturedAt(left) - parseTrafficCapturedAt(right),
  )[0] ?? null;
}

function persistTrafficAttributionToCookie(payload: TrafficAttributionPayload | null) {
  if (typeof document === 'undefined' || typeof window === 'undefined' || !payload) {
    return;
  }

  try {
    const cookieDomain = resolveCookieDomainFromHostname(window.location.hostname);
    const isSecureContext = window.location.protocol === 'https:';
    const cookieParts = [
      `${TRAFFIC_ATTRIBUTION_COOKIE_KEY}=${encodeURIComponent(JSON.stringify(payload))}`,
      `Max-Age=${TRAFFIC_ATTRIBUTION_COOKIE_MAX_AGE}`,
      'Path=/',
      'SameSite=Lax',
    ];

    if (cookieDomain !== '') {
      cookieParts.push(`Domain=.${cookieDomain}`);
    }

    if (isSecureContext) {
      cookieParts.push('Secure');
    }

    document.cookie = cookieParts.join('; ');
  } catch {
    // Ignore storage failures and continue the registration flow.
  }
}

function persistTrafficAttributionToLocalStorage(payload: TrafficAttributionPayload | null) {
  if (typeof window === 'undefined' || !payload) {
    return;
  }

  try {
    window.localStorage.setItem(TRAFFIC_ATTRIBUTION_STORAGE_KEY, JSON.stringify(payload));
  } catch {
    // Ignore storage failures and continue the registration flow.
  }
}

function persistTrafficAttribution(payload: TrafficAttributionPayload | null) {
  if (!payload) {
    return;
  }

  persistTrafficAttributionToCookie(payload);
  persistTrafficAttributionToLocalStorage(payload);
}

function buildCurrentTrafficAttribution() {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return null;
  }

  try {
    const currentUrl = new URL(window.location.href);
    const currentHost = currentUrl.hostname.toLowerCase();
    const referrerHost = (() => {
      if (document.referrer.trim() === '') {
        return '';
      }

      try {
        const referrerUrl = new URL(document.referrer);
        const referrerHostname = referrerUrl.hostname.toLowerCase();

        return isInternalHost(currentHost, referrerHostname) ? '' : referrerHostname;
      } catch {
        return '';
      }
    })();
    const utmSourceRaw = currentUrl.searchParams.get('utm_source')?.trim() ?? '';
    const utmMediumRaw = currentUrl.searchParams.get('utm_medium')?.trim() ?? '';
    const utmCampaignRaw = currentUrl.searchParams.get('utm_campaign')?.trim() ?? '';
    const source = utmSourceRaw !== ''
      ? normalizeTrafficSource(utmSourceRaw)
      : resolveSourceFromReferrerHost(referrerHost);
    const landingPath = `${currentUrl.pathname}${currentUrl.search}`.trim() || currentUrl.pathname || '/';

    return {
      traffic_source: source,
      traffic_source_detail: utmSourceRaw !== ''
        ? normalizeTrafficToken(utmSourceRaw)
        : (referrerHost || 'direct'),
      traffic_medium: utmMediumRaw !== ''
        ? normalizeTrafficToken(utmMediumRaw)
        : inferTrafficMedium(source, referrerHost),
      traffic_campaign: utmCampaignRaw,
      traffic_referrer_host: referrerHost,
      traffic_landing_path: landingPath,
      traffic_captured_at: new Date().toISOString(),
    } satisfies TrafficAttributionPayload;
  } catch {
    return null;
  }
}

export function resolveTrafficAttribution() {
  const current = buildCurrentTrafficAttribution();

  if (current) {
    persistTrafficAttribution(current);

    return current;
  }

  const stored = pickStoredTrafficAttribution([
    readTrafficAttributionLocalStorage(),
    readTrafficAttributionCookie(),
  ]);

  if (stored) {
    persistTrafficAttribution(stored);
  }

  return stored;
}

export function captureTrafficAttribution() {
  const payload = resolveTrafficAttribution();

  if (payload) {
    persistTrafficAttribution(payload);
  }

  return payload;
}

function resolveRegisterUrl(currentUrl: URL) {
  const configuredRegisterUrl = readMetaContent('register-url');

  if (configuredRegisterUrl !== '') {
    return new URL(configuredRegisterUrl, currentUrl.origin);
  }

  return new URL('/register', currentUrl.origin);
}

export function buildRegisterUrl() {
  if (typeof window === 'undefined') {
    return readMetaContent('register-url') || '/register';
  }

  const currentUrl = new URL(window.location.href);

  return resolveRegisterUrl(currentUrl).toString();
}
