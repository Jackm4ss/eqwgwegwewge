import { useCallback, useEffect, useRef, useState, type MouseEvent } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import { Html5Qrcode, Html5QrcodeSupportedFormats, type CameraDevice } from 'html5-qrcode';
import {
  AlertCircle,
  BarChart3,
  Camera,
  CheckCircle2,
  Clock3,
  House,
  Loader2,
  MapPin,
  LogOut,
  PauseCircle,
  PlayCircle,
  QrCode,
  RefreshCcw,
  ScanLine,
  Search,
  ShieldAlert,
  TriangleAlert,
  UserRound,
} from 'lucide-react';

import {
  AuthCardFrame,
  AuthCardHeader,
  AuthCodeBadge,
  AuthPageShell,
  AuthSectionHeading,
  authInputClass,
  authPrimaryButtonClass,
} from './AuthShared';
import type { BeforeInstallPromptEvent } from '../../lib/pwa';
import { getSpaUrl } from '../../lib/spaRouting';
import { ensureSweetAlert } from '../../lib/sweetAlert';

type StaffSession = {
  user: { email: string };
  scanner_post: string | null;
};

type CameraPermissionState = 'unknown' | 'prompt' | 'granted' | 'denied' | 'unsupported';
type CameraSurface = 'browser' | 'pwa';
type CameraPlatform = 'ios' | 'android' | 'other';
type CameraStartTarget = string | { facingMode: 'environment' | { exact: 'environment' } };

type ScannerStats = {
  total_scans: number;
  successful_scans: number;
  duplicate_scans: number;
  invalid_scans: number;
};

type Participant = {
  name?: string;
  full_name?: string;
  email?: string;
  phone_number?: string;
  country?: string;
  country_label?: string;
  ticket_code?: string;
  entry_code_display?: string;
};

type ScanResult = {
  status: 'success' | 'duplicate' | 'invalid';
  message: string;
  participant: Participant | null;
  activity_item?: HistoryItem | null;
  stats: ScannerStats;
};

type ManualLookupResult = {
  found: boolean;
  message?: string;
  resolution_token?: string;
  participant?: Participant;
};

type StaffScannerTab = 'home' | 'stats' | 'profile';

type HistoryItem = {
  scan_id: string;
  status: 'success' | 'duplicate' | 'invalid';
  ticket_code: string;
  entry_code_display: string;
  scanner_post: string;
  scanned_at: string;
  participant?: Participant | null;
};

type HistoryMeta = {
  page: number;
  per_page: number;
  total: number;
  has_more: boolean;
  scope_date: string;
};

type HistoryResponse = {
  items: HistoryItem[];
  meta: HistoryMeta;
};

type DashboardResponse = {
  stats: ScannerStats;
  history: HistoryResponse;
};

type ScannerAlertIcon = 'success' | 'warning' | 'error' | 'info';
type ScannerAlertConfig = {
  icon: ScannerAlertIcon;
  title: string;
  text: string;
  html?: string;
  iconHtml?: string;
  iconClassName?: string;
};

type CameraZoomState = {
  min: number;
  max: number;
  step: number;
  value: number;
};

type ExtendedMediaTrackCapabilities = MediaTrackCapabilities & {
  focusMode?: string[];
  zoom?: {
    min: number;
    max: number;
    step?: number;
  };
};

type ExtendedMediaTrackSettings = MediaTrackSettings & {
  zoom?: number;
};

const SONGKRAN_LOGO_URL = '/images/Songkran%20logo.png';
const PWA_APP_ICON_URL = '/pwa/icons/icon-192.png';
const SCANNER_REGION_ID = 'staff-html5-qrcode-region';
const DEFAULT_HISTORY_PER_PAGE = 20;
const EMPTY_STATS: ScannerStats = { total_scans: 0, successful_scans: 0, duplicate_scans: 0, invalid_scans: 0 };
const EMPTY_HISTORY_META: HistoryMeta = {
  page: 1,
  per_page: DEFAULT_HISTORY_PER_PAGE,
  total: 0,
  has_more: false,
  scope_date: '',
};
const STAFF_LOGIN_URL = getSpaUrl('staffLogin', '/staff/login');
const STAFF_SESSION_URL = getSpaUrl('staffSession', '/staff/session');
const STAFF_SCAN_URL = getSpaUrl('staffScan', '/staff/scan');
const STAFF_MANUAL_LOOKUP_URL = getSpaUrl('staffManualLookup', '/staff/manual-lookup');
const STAFF_MANUAL_CONFIRM_URL = getSpaUrl('staffManualConfirm', '/staff/manual-confirm');
const STAFF_DASHBOARD_URL = getSpaUrl('staffDashboard', '/staff/dashboard');
const STAFF_HISTORY_URL = getSpaUrl('staffHistory', '/staff/history');
const STAFF_LOGOUT_URL = getSpaUrl('staffLogout', '/staff/logout');

function csrfToken() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

function formatEntryCode(value: string) {
  const clean = value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
  return clean.length <= 4 ? clean : `${clean.slice(0, 4)}-${clean.slice(4)}`;
}

function formatScannedAt(value: string) {
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? value || '-'
    : date.toLocaleString('en-MY', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function escapeHtml(value?: string | null) {
  return (value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

function resultParticipantField(label: string, value: string, extraClassName = '') {
  return `
    <div class="staff-scanner-swal__detail ${extraClassName}">
      <span class="staff-scanner-swal__detail-label">${escapeHtml(label)}</span>
      <span class="staff-scanner-swal__detail-value">${escapeHtml(value || '-')}</span>
    </div>
  `;
}

function participantDetailField(label: string, value: string, extraClassName = '') {
  return (
    <div className={extraClassName}>
      <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">{label}</p>
      <p className="mt-1 text-sm font-semibold text-slate-900 break-words">{value || '-'}</p>
    </div>
  );
}

function countryFlagEmoji(countryCode?: string | null) {
  const normalized = (countryCode ?? '').trim().toUpperCase();
  if (!/^[A-Z]{2}$/.test(normalized)) {
    return '🏳️';
  }

  return String.fromCodePoint(...Array.from(normalized).map((char) => 127397 + char.charCodeAt(0)));
}

function historyParticipantDetails(item: HistoryItem) {
  const participant = item.participant;
  if (!participant) {
    return (
      <div className="mt-3 grid gap-3">
        {participantDetailField('Ticket Code', item.ticket_code || 'Unknown Ticket')}
      </div>
    );
  }

  return (
    <div className="mt-3 grid gap-3">
      {participantDetailField('Email', participant.email || '-')}
      {participantDetailField('Full Name', participant.full_name || participant.name || '-')}
      {participantDetailField('Phone Number', participant.phone_number || '-')}
      {participantDetailField('Country', participant.country_label || participant.country || '-')}
    </div>
  );
}

function buildHistoryUrl(page: number, perPage: number) {
  const params = new URLSearchParams({
    page: String(page),
    per_page: String(perPage),
  });

  return `${STAFF_HISTORY_URL}?${params.toString()}`;
}

function buildDashboardUrl(perPage: number) {
  const params = new URLSearchParams({
    page: '1',
    per_page: String(perPage),
  });

  return `${STAFF_DASHBOARD_URL}?${params.toString()}`;
}

function dedupeHistoryItems(items: HistoryItem[]) {
  const seen = new Set<string>();

  return items.filter((item) => {
    if (seen.has(item.scan_id)) {
      return false;
    }

    seen.add(item.scan_id);
    return true;
  });
}

function appendHistoryItems(current: HistoryItem[], incoming: HistoryItem[]) {
  return dedupeHistoryItems([...current, ...incoming]);
}

function prependHistoryItem(current: HistoryItem[], incoming: HistoryItem, visibleLimit: number) {
  return dedupeHistoryItems([incoming, ...current]).slice(0, Math.max(visibleLimit, 1));
}

function scanResultAlertHtml(result: Pick<ScanResult, 'message' | 'participant'>) {
  if (!result.participant) {
    return `
      <div class="staff-scanner-swal__result">
        <span class="staff-scanner-swal__result-label">Result</span>
        <p class="staff-scanner-swal__result-message">${escapeHtml(result.message)}</p>
      </div>
    `;
  }

  const participant = result.participant;

  return `
    <div class="staff-scanner-swal__result">
      <span class="staff-scanner-swal__result-label">Result</span>
      <p class="staff-scanner-swal__result-message">${escapeHtml(result.message)}</p>
    </div>
    <div class="staff-scanner-swal__details-card">
      <div class="staff-scanner-swal__details-grid">
        ${resultParticipantField('Email', participant.email || '-')}
        ${resultParticipantField('Full Name', participant.full_name || participant.name || '-')}
        ${resultParticipantField('Phone Number', participant.phone_number || '-')}
        ${resultParticipantField('Country', participant.country_label || participant.country || '-')}
        ${resultParticipantField('Entry Code', participant.entry_code_display || '-', 'staff-scanner-swal__detail--entry')}
      </div>
    </div>
  `;
}

function duplicateWarningAlertIconConfig(): Pick<ScannerAlertConfig, 'iconClassName' | 'iconHtml'> {
  return {
    iconClassName: 'staff-scanner-swal__icon--duplicate',
    iconHtml: `
      <svg class="staff-scanner-swal__warning-svg" viewBox="0 0 64 64" aria-hidden="true">
        <path
          d="M32 8.5c1.5 0 2.9.82 3.65 2.15l20.95 37.15c1.54 2.73-.43 6.1-3.56 6.1H11c-3.13 0-5.1-3.37-3.56-6.1L28.35 10.65A4.18 4.18 0 0 1 32 8.5Z"
          fill="#facc15"
          stroke="#ca8a04"
          stroke-width="2.5"
          stroke-linejoin="round"
        />
        <path d="M32 22v14" fill="none" stroke="#78350f" stroke-width="4.5" stroke-linecap="round" />
        <circle cx="32" cy="43.5" r="3" fill="#78350f" />
      </svg>
    `,
  };
}

function scanResultAlertConfig(result: Pick<ScanResult, 'status' | 'message' | 'participant'>): ScannerAlertConfig {
  if (result.status === 'success') {
    return {
      icon: 'success',
      title: 'Scan accepted',
      text: result.message,
      html: scanResultAlertHtml(result),
    };
  }

  if (result.status === 'duplicate') {
    return {
      icon: 'warning',
      title: 'Duplicate scan',
      text: result.message,
      html: scanResultAlertHtml(result),
      ...duplicateWarningAlertIconConfig(),
    };
  }

  return {
    icon: 'error',
    title: 'Invalid scan',
    text: result.message,
    html: scanResultAlertHtml(result),
  };
}

function cameraReadyAlertConfig(): ScannerAlertConfig {
  return {
    icon: 'success',
    title: 'Camera ready',
    text: 'Scanner camera is ready.',
    iconClassName: 'staff-scanner-swal__icon--camera',
    iconHtml: `
      <svg class="staff-scanner-swal__camera-svg" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M9 4.5a1 1 0 0 0-.8.4L7.25 6H5.5A2.5 2.5 0 0 0 3 8.5v7A2.5 2.5 0 0 0 5.5 18h13a2.5 2.5 0 0 0 2.5-2.5v-7A2.5 2.5 0 0 0 18.5 6h-1.75l-.95-1.1a1 1 0 0 0-.8-.4H9Zm3 3.25a4.25 4.25 0 1 1 0 8.5a4.25 4.25 0 0 1 0-8.5Z"/>
        <circle cx="12" cy="12" r="2.2" fill="#0ea5e9"/>
        <circle cx="17.4" cy="17.4" r="4.1" fill="#10b981"/>
        <path fill="#ffffff" d="m15.9 17.35l.95.95l2-2a.75.75 0 1 1 1.06 1.06l-2.53 2.53a.75.75 0 0 1-1.06 0l-1.48-1.48a.75.75 0 1 1 1.06-1.06Z"/>
      </svg>
    `,
  };
}

function ensureScannerAlertStyles() {
  if (typeof document === 'undefined') {
    return;
  }

  const styleId = 'staff-scanner-swal-style';

  if (document.getElementById(styleId)) {
    return;
  }

  const style = document.createElement('style');
  style.id = styleId;
  style.textContent = `
    .staff-scanner-swal {
      width: min(420px, calc(100vw - 1rem)) !important;
      border-radius: 28px !important;
      border: 1px solid rgba(186, 230, 253, 0.9) !important;
      background: linear-gradient(180deg, #ffffff 0%, #f8fcff 100%) !important;
      box-shadow: 0 28px 72px rgba(12, 74, 110, 0.28) !important;
      padding: 0 0 1.4rem !important;
      overflow: hidden !important;
    }

    .staff-scanner-swal .swal2-icon {
      margin: 1.6rem auto 0.85rem !important;
    }

    .staff-scanner-swal .swal2-icon.swal2-success {
      border-color: #9ad67d !important;
      color: #9ad67d !important;
    }

    .staff-scanner-swal .swal2-icon.swal2-success .swal2-success-ring {
      border-color: rgba(154, 214, 125, 0.28) !important;
    }

    .staff-scanner-swal .swal2-icon.swal2-success .swal2-success-fix,
    .staff-scanner-swal .swal2-icon.swal2-success [class^='swal2-success-circular-line'] {
      background: #ffffff !important;
    }

    .staff-scanner-swal .swal2-icon.swal2-success [class^='swal2-success-line'] {
      display: block !important;
      z-index: 3 !important;
      background-color: #9ad67d !important;
    }

    .staff-scanner-swal__icon--duplicate,
    .staff-scanner-swal__icon--camera {
      width: 5.4rem !important;
      height: 5.4rem !important;
      margin: 1.45rem auto 0.8rem !important;
      border: none !important;
    }

    .staff-scanner-swal__icon--duplicate {
      background: radial-gradient(circle at top, rgba(250, 204, 21, 0.24), rgba(250, 204, 21, 0.09) 55%, rgba(255, 255, 255, 0) 72%) !important;
    }

    .staff-scanner-swal__icon--camera {
      background: radial-gradient(circle at top, rgba(14, 165, 233, 0.18), rgba(14, 165, 233, 0.08) 55%, rgba(255, 255, 255, 0) 70%) !important;
    }

    .staff-scanner-swal__icon--duplicate .swal2-icon-content,
    .staff-scanner-swal__icon--camera .swal2-icon-content {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      width: 100% !important;
      height: 100% !important;
      transform: none !important;
      font-size: 1rem !important;
    }

    .staff-scanner-swal__warning-svg,
    .staff-scanner-swal__camera-svg {
      width: 4.8rem;
      height: 4.8rem;
    }

    .staff-scanner-swal__warning-svg {
      filter: drop-shadow(0 10px 24px rgba(202, 138, 4, 0.2));
    }

    .staff-scanner-swal__camera-svg {
      color: #0284c7;
      filter: drop-shadow(0 10px 24px rgba(2, 132, 199, 0.18));
    }

    .staff-scanner-swal__title {
      margin: 0 !important;
      padding: 0 1.25rem 0.35rem !important;
      color: #0f172a !important;
      font-family: "Kanit", sans-serif !important;
      font-size: 1.6rem !important;
      font-weight: 700 !important;
      line-height: 1.15 !important;
    }

    .staff-scanner-swal__html {
      margin: 0 !important;
      padding: 0 1.25rem !important;
      color: #475569 !important;
      font-size: 0.96rem !important;
      line-height: 1.7 !important;
      text-align: left !important;
    }

    .staff-scanner-swal__result {
      border-radius: 1.1rem;
      border: 1px solid rgba(186, 230, 253, 0.9);
      background: linear-gradient(180deg, rgba(240, 249, 255, 0.9), rgba(255, 255, 255, 0.96));
      padding: 0.9rem 1rem;
    }

    .staff-scanner-swal__result-label,
    .staff-scanner-swal__detail-label {
      display: block;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: #64748b;
    }

    .staff-scanner-swal__result-message {
      margin: 0.45rem 0 0;
      color: #0f172a;
      font-size: 0.95rem;
      font-weight: 700;
      line-height: 1.6;
    }

    .staff-scanner-swal__details-card {
      margin-top: 0.85rem;
      border-radius: 1.1rem;
      border: 1px solid rgba(226, 232, 240, 0.95);
      background: rgba(255, 255, 255, 0.95);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
      padding: 0.95rem 1rem;
    }

    .staff-scanner-swal__details-grid {
      display: grid;
      gap: 0.75rem;
    }

    .staff-scanner-swal__detail {
      min-width: 0;
    }

    .staff-scanner-swal__detail-value {
      display: block;
      margin-top: 0.2rem;
      color: #0f172a;
      font-size: 0.94rem;
      font-weight: 700;
      line-height: 1.55;
      word-break: break-word;
    }

    .staff-scanner-swal__detail--entry .staff-scanner-swal__detail-value {
      letter-spacing: 0.08em;
    }

    .staff-scanner-swal__actions {
      margin: 1.25rem 0 0 !important;
      padding: 0 1.25rem !important;
    }

    .staff-scanner-swal__confirm {
      margin: 0 !important;
      width: 100% !important;
      border: 1px solid transparent !important;
      border-radius: 1rem !important;
      background: linear-gradient(135deg, #0284c7, #0ea5e9) !important;
      color: #ffffff !important;
      font-size: 0.96rem !important;
      font-weight: 700 !important;
      padding: 0.9rem 1.2rem !important;
      box-shadow: 0 14px 28px rgba(2, 132, 199, 0.22) !important;
      transition: transform 0.18s ease, filter 0.18s ease !important;
    }

    .staff-scanner-swal__confirm:hover {
      transform: translateY(-1px);
      filter: brightness(1.03);
    }

    .staff-scanner-swal__confirm:focus-visible,
    .staff-scanner-swal .swal2-close:focus-visible {
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.18) !important;
      outline: none !important;
    }

    .staff-scanner-swal .swal2-close {
      top: 0.95rem !important;
      right: 0.95rem !important;
      color: #64748b !important;
      font-size: 1.55rem !important;
      transition: background-color 0.18s ease, color 0.18s ease !important;
    }

    .staff-scanner-swal .swal2-close:hover {
      background: rgba(226, 232, 240, 0.8) !important;
      color: #0f172a !important;
    }

    @media (max-width: 640px) {
      .staff-scanner-swal {
        border-radius: 24px !important;
      }

      .staff-scanner-swal__title {
        font-size: 1.4rem !important;
      }
    }
  `;

  document.head.appendChild(style);
}

function statusMeta(status: ScanResult['status']) {
  if (status === 'success') {
    return { label: 'Valid', badge: 'border-emerald-200 bg-emerald-50 text-emerald-700', panel: 'border-emerald-200 bg-emerald-50/70', icon: CheckCircle2 };
  }
  if (status === 'duplicate') {
    return { label: 'Duplicate', badge: 'border-amber-200 bg-amber-50 text-amber-700', panel: 'border-amber-200 bg-amber-50/70', icon: TriangleAlert };
  }
  return { label: 'Invalid', badge: 'border-red-200 bg-red-50 text-red-700', panel: 'border-red-200 bg-red-50/70', icon: ShieldAlert };
}

function detectCameraSurface(): CameraSurface {
  if (typeof window === 'undefined') {
    return 'browser';
  }

  const standaloneDisplayMode = window.matchMedia?.('(display-mode: standalone)').matches ?? false;
  const iosStandalone = Boolean((window.navigator as Navigator & { standalone?: boolean }).standalone);

  return standaloneDisplayMode || iosStandalone ? 'pwa' : 'browser';
}

function detectCameraPlatform(): CameraPlatform {
  if (typeof navigator === 'undefined') {
    return 'other';
  }

  const userAgent = navigator.userAgent.toLowerCase();

  if (/iphone|ipad|ipod/.test(userAgent)) {
    return 'ios';
  }

  if (/android/.test(userAgent)) {
    return 'android';
  }

  return 'other';
}

function cameraDeniedGuidance(surface: CameraSurface, platform: CameraPlatform) {
  if (surface === 'pwa' && platform === 'android') {
    return 'Camera is blocked. Allow Camera in Android app permissions, then reopen the scanner.';
  }

  if (platform === 'ios') {
    return 'Camera is blocked. Allow Camera in Safari or iPhone Settings, then reload.';
  }

  return 'Camera is blocked. Allow Camera for this site, then refresh.';
}

function describeCameraPermission(
  permissionState: CameraPermissionState,
  surface: CameraSurface,
  platform: CameraPlatform,
  waitingForPermission: boolean,
) {
  if (permissionState === 'granted') {
    return {
      tone: 'success' as const,
      title: 'Camera ready',
      body: "You're all set to scan now.",
    };
  }

  if (permissionState === 'denied') {
    return {
      tone: 'danger' as const,
      title: 'Camera blocked',
      body: cameraDeniedGuidance(surface, platform),
    };
  }

  if (waitingForPermission || permissionState === 'prompt') {
    return {
      tone: 'info' as const,
      title: waitingForPermission ? 'Waiting for camera' : 'Allow camera',
      body: surface === 'pwa' && platform === 'android'
        ? 'Tap Start Scan, then tap Allow in the Android dialog.'
        : platform === 'ios'
          ? 'Tap Start Scan, then allow camera in Safari.'
          : 'Tap Start Scan, then allow camera.',
    };
  }

  if (permissionState === 'unsupported') {
    return {
      tone: 'warning' as const,
      title: 'Camera check unavailable',
      body: surface === 'pwa' && platform === 'android'
        ? 'This app cannot check camera status first, but Start Scan can still ask for access.'
        : 'This browser cannot check camera status first, but Start Scan can still ask for access.',
    };
  }

  return {
    tone: 'info' as const,
    title: 'Checking camera',
    body: 'Preparing camera access for this device.',
  };
}

function permissionToneClass(tone: 'info' | 'success' | 'warning' | 'danger') {
  if (tone === 'success') {
    return 'border-emerald-200 bg-emerald-50 text-emerald-800';
  }

  if (tone === 'warning') {
    return 'border-amber-200 bg-amber-50 text-amber-800';
  }

  if (tone === 'danger') {
    return 'border-red-200 bg-red-50 text-red-800';
  }

  return 'border-sky-200 bg-sky-50 text-sky-800';
}

function cameraStartFailure(error: unknown, surface: CameraSurface, platform: CameraPlatform) {
  const message = error instanceof Error ? error.message : String(error ?? '');
  const normalized = message.toLowerCase();

  if (
    normalized.includes('notallowederror')
    || normalized.includes('permission denied')
    || normalized.includes('permissiondismissederror')
    || normalized.includes('permission request dismissed')
  ) {
    return {
      permissionState: 'denied' as const,
      message: cameraDeniedGuidance(surface, platform),
    };
  }

  if (normalized.includes('notfounderror') || normalized.includes('devicesnotfounderror')) {
    return {
      permissionState: 'unknown' as const,
      message: 'No camera was found on this device. Use Manual Entry or switch to a device with a working camera.',
    };
  }

  if (
    normalized.includes('notreadableerror')
    || normalized.includes('trackstarterror')
    || normalized.includes('could not start video source')
  ) {
    return {
      permissionState: 'unknown' as const,
      message: 'The camera is busy in another app or tab. Close other camera apps, then try Start Scan again.',
    };
  }

  if (normalized.includes('aborterror')) {
    return {
      permissionState: 'unknown' as const,
      message: 'The camera request was interrupted before it finished. Try Start Scan once more.',
    };
  }

  return {
    permissionState: 'unknown' as const,
    message: 'Unable to start the scanner camera. Check camera permission, then try again or use Manual Entry.',
  };
}

function preferredQrBoxSize(viewfinderWidth: number, viewfinderHeight: number) {
  const shortestEdge = Math.min(viewfinderWidth, viewfinderHeight);
  const maxSafeSize = Math.max(180, Math.floor(shortestEdge - 24));
  const size = Math.max(180, Math.min(Math.floor(shortestEdge * 0.88), maxSafeSize, 420));

  return { width: size, height: size };
}

function buildCameraVideoConstraints(
  target: CameraStartTarget,
  platform: CameraPlatform,
): MediaTrackConstraints {
  const idealWidth = platform === 'android' ? 1600 : 1280;
  const idealHeight = platform === 'android' ? 1200 : 960;

  return {
    width: { ideal: idealWidth },
    height: { ideal: idealHeight },
    ...(typeof target === 'string'
      ? { deviceId: { exact: target } }
      : { facingMode: target.facingMode }),
  };
}

function clampNumber(value: number, min: number, max: number) {
  return Math.min(max, Math.max(min, value));
}

function normalizeZoomStep(value: number | undefined) {
  if (typeof value !== 'number' || !Number.isFinite(value) || value <= 0) {
    return 0.1;
  }

  return value;
}

function roundZoomValue(value: number, step: number) {
  const decimals = step >= 1 ? 0 : Math.min(3, (String(step).split('.')[1] ?? '').length || 1);

  return Number(value.toFixed(decimals));
}

function formatZoomValue(value: number) {
  return `${value.toFixed(value >= 10 ? 0 : 1)}x`;
}

function getScannerVideoTrack(region: HTMLDivElement | null) {
  if (!region) {
    return null;
  }

  const videoElement = region.querySelector('video');
  if (!(videoElement instanceof HTMLVideoElement)) {
    return null;
  }

  const mediaStream = videoElement.srcObject;
  if (!(mediaStream instanceof MediaStream)) {
    return null;
  }

  return mediaStream.getVideoTracks()[0] ?? null;
}

async function applyTrackAdvancedConstraint(track: MediaStreamTrack, constraint: Record<string, unknown>) {
  try {
    await track.applyConstraints({ advanced: [constraint] } as MediaTrackConstraints);
    return true;
  } catch {
    return false;
  }
}

function readCameraZoomState(track: MediaStreamTrack) {
  if (typeof track.getCapabilities !== 'function') {
    return null;
  }

  const capabilities = track.getCapabilities() as ExtendedMediaTrackCapabilities;
  const zoomCapability = capabilities.zoom;

  if (
    !zoomCapability
    || typeof zoomCapability.min !== 'number'
    || typeof zoomCapability.max !== 'number'
    || zoomCapability.max <= zoomCapability.min
  ) {
    return null;
  }

  const step = normalizeZoomStep(zoomCapability.step);
  const settings = typeof track.getSettings === 'function'
    ? track.getSettings() as ExtendedMediaTrackSettings
    : null;
  const currentZoom = typeof settings?.zoom === 'number'
    ? settings.zoom
    : zoomCapability.min;

  return {
    min: roundZoomValue(zoomCapability.min, step),
    max: roundZoomValue(zoomCapability.max, step),
    step,
    value: roundZoomValue(clampNumber(currentZoom, zoomCapability.min, zoomCapability.max), step),
  } satisfies CameraZoomState;
}

async function configureActiveScannerCamera(
  region: HTMLDivElement | null,
  platform: CameraPlatform,
) {
  const track = getScannerVideoTrack(region);
  if (!track) {
    return null;
  }

  if (typeof track.getCapabilities === 'function') {
    const capabilities = track.getCapabilities() as ExtendedMediaTrackCapabilities;

    if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.includes('continuous')) {
      await applyTrackAdvancedConstraint(track, { focusMode: 'continuous' });
    }
  }

  const zoomState = readCameraZoomState(track);
  if (!zoomState) {
    return null;
  }

  let preferredZoom = zoomState.value;

  if (platform === 'android' && zoomState.max >= 1.2 && zoomState.value <= zoomState.min + zoomState.step) {
    preferredZoom = roundZoomValue(clampNumber(1.15, zoomState.min, zoomState.max), zoomState.step);
  }

  if (Math.abs(preferredZoom - zoomState.value) >= zoomState.step / 2) {
    const applied = await applyTrackAdvancedConstraint(track, { zoom: preferredZoom });

    if (applied) {
      const updatedZoomState = readCameraZoomState(track);
      if (updatedZoomState) {
        return updatedZoomState;
      }

      return { ...zoomState, value: preferredZoom };
    }
  }

  return zoomState;
}

function pickPreferredBackCamera(cameras: CameraDevice[]) {
  if (cameras.length === 0) {
    return null;
  }

  const frontCameraPattern = /\b(front|user|face)\b/i;
  const preferredBackCameraPattern = /\b(back|rear|environment)\b/i;
  const avoidCloseRangePattern = /\b(ultra|wide|macro|depth|tele|zoom)\b/i;

  return [...cameras]
    .sort((left, right) => {
      const leftLabel = left.label.trim().toLowerCase();
      const rightLabel = right.label.trim().toLowerCase();

      const score = (label: string) => {
        let value = 0;

        if (preferredBackCameraPattern.test(label)) {
          value += 120;
        }

        if (!frontCameraPattern.test(label)) {
          value += 18;
        }

        if (frontCameraPattern.test(label)) {
          value -= 220;
        }

        if (avoidCloseRangePattern.test(label)) {
          value -= 35;
        }

        if (/\b0\b/.test(label)) {
          value += 6;
        }

        return value;
      };

      return score(rightLabel) - score(leftLabel);
    })[0] ?? null;
}

async function buildCameraStartTargets(platform: CameraPlatform): Promise<CameraStartTarget[]> {
  const targets: CameraStartTarget[] = [];
  const seen = new Set<string>();

  const addTarget = (target: CameraStartTarget | null) => {
    if (!target) {
      return;
    }

    const key = typeof target === 'string' ? `device:${target}` : JSON.stringify(target);

    if (!seen.has(key)) {
      seen.add(key);
      targets.push(target);
    }
  };

  try {
    const cameras = await Html5Qrcode.getCameras();
    const preferredCamera = pickPreferredBackCamera(cameras);

    if (preferredCamera?.id) {
      addTarget(preferredCamera.id);
    } else if (cameras.length === 1 && cameras[0]?.id) {
      addTarget(cameras[0].id);
    }
  } catch {
    // Fall back to facingMode when camera enumeration is unavailable.
  }

  if (platform === 'android') {
    addTarget({ facingMode: { exact: 'environment' } });
  }

  addTarget({ facingMode: 'environment' });

  return targets;
}

export function StaffScannerPage() {
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const scannerRegionRef = useRef<HTMLDivElement | null>(null);
  const html5QrCodeRef = useRef<Html5Qrcode | null>(null);
  const detectingRef = useRef(false);
  const lastValueRef = useRef('');
  const lastValueAtRef = useRef(0);
  const alertQueueRef = useRef<Promise<void>>(Promise.resolve());
  const pendingAlertCountRef = useRef(0);
  const zoomUpdateQueueRef = useRef<Promise<void>>(Promise.resolve());
  const dashboardRefreshTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const [session, setSession] = useState<StaffSession | null>(null);
  const [scannerPost, setScannerPost] = useState('');
  const [stats, setStats] = useState<ScannerStats>(EMPTY_STATS);
  const [history, setHistory] = useState<HistoryItem[]>([]);
  const [historyMeta, setHistoryMeta] = useState<HistoryMeta>(EMPTY_HISTORY_META);
  const [dashboardLoading, setDashboardLoading] = useState(true);
  const [historyLoadingMore, setHistoryLoadingMore] = useState(false);
  const [dashboardRefreshing, setDashboardRefreshing] = useState(false);
  const [dashboardRefreshAnimating, setDashboardRefreshAnimating] = useState(false);
  const [latest, setLatest] = useState<ScanResult>({ status: 'invalid', message: 'Scanner is ready when you are.', participant: null, stats: EMPTY_STATS });
  const [manualCode, setManualCode] = useState('');
  const [manualLookup, setManualLookup] = useState<ManualLookupResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [scanBusy, setScanBusy] = useState(false);
  const [manualBusy, setManualBusy] = useState(false);
  const [confirmBusy, setConfirmBusy] = useState(false);
  const [scannerActive, setScannerActive] = useState(false);
  const [scannerPending, setScannerPending] = useState(false);
  const [cameraMessage, setCameraMessage] = useState('');
  const [cameraPermissionState, setCameraPermissionState] = useState<CameraPermissionState>('unknown');
  const [cameraZoom, setCameraZoom] = useState<CameraZoomState | null>(null);
  const [awaitingCameraPermission, setAwaitingCameraPermission] = useState(false);
  const [activeTab, setActiveTab] = useState<StaffScannerTab>('home');
  const [installPromptEvent, setInstallPromptEvent] = useState<BeforeInstallPromptEvent | null>(null);
  const [installBannerDismissed, setInstallBannerDismissed] = useState(false);
  const [installingApp, setInstallingApp] = useState(false);
  const [appInstalled, setAppInstalled] = useState(() => detectCameraSurface() === 'pwa');

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => { addRippleRef.current = fn; }, []);
  const handlePageClick = useCallback((event: MouseEvent<HTMLDivElement>) => { addRippleRef.current?.(event.clientX, event.clientY); }, []);
  const redirectToLogin = useCallback(() => { window.location.href = STAFF_LOGIN_URL; }, []);
  const showScannerAlert = useCallback((config: ScannerAlertConfig) => {
    pendingAlertCountRef.current += 1;

    const runAlert = async () => {
      ensureScannerAlertStyles();

      try {
        const Swal = await ensureSweetAlert();
        const fireOptions: Record<string, unknown> = {
          icon: config.icon,
          title: config.title,
          text: config.html ? undefined : config.text,
          html: config.html,
          confirmButtonText: 'OK',
          allowOutsideClick: false,
          allowEscapeKey: true,
          showCloseButton: true,
          buttonsStyling: false,
          customClass: {
            popup: 'staff-scanner-swal',
            title: 'staff-scanner-swal__title',
            htmlContainer: 'staff-scanner-swal__html',
            actions: 'staff-scanner-swal__actions',
            confirmButton: 'staff-scanner-swal__confirm',
          },
        };

        if (config.iconHtml) {
          fireOptions.iconHtml = config.iconHtml;
        }

        if (config.iconClassName) {
          const customClass = fireOptions.customClass as Record<string, string>;
          customClass.icon = config.iconClassName;
        }

        await Swal.fire(fireOptions);
      } catch {
        window.alert(`${config.title}\n\n${config.text}`);
      } finally {
        pendingAlertCountRef.current = Math.max(0, pendingAlertCountRef.current - 1);
        lastValueAtRef.current = Date.now();
      }
    };

    alertQueueRef.current = alertQueueRef.current
      .catch(() => undefined)
      .then(runAlert);

    return alertQueueRef.current;
  }, []);
  const handleCameraZoomChange = useCallback((rawValue: number) => {
    if (!cameraZoom) {
      return;
    }

    const nextValue = roundZoomValue(clampNumber(rawValue, cameraZoom.min, cameraZoom.max), cameraZoom.step);
    setCameraZoom({ ...cameraZoom, value: nextValue });

    zoomUpdateQueueRef.current = zoomUpdateQueueRef.current
      .catch(() => undefined)
      .then(async () => {
        const track = getScannerVideoTrack(scannerRegionRef.current);
        if (!track) {
          return;
        }

        const applied = await applyTrackAdvancedConstraint(track, { zoom: nextValue });
        if (!applied) {
          const refreshedZoomState = readCameraZoomState(track);
          if (refreshedZoomState) {
            setCameraZoom(refreshedZoomState);
          }
        }
      });
  }, [cameraZoom]);

  const stopScanner = useCallback(async () => {
    setScannerPending(true);

    try {
      const scanner = html5QrCodeRef.current;
      if (scanner) {
        if (scanner.isScanning) {
          await scanner.stop();
        }
        scanner.clear();
      }
    } catch {
      // Best-effort teardown so the operator can retry quickly.
    } finally {
      html5QrCodeRef.current = null;
      detectingRef.current = false;
      lastValueRef.current = '';
      lastValueAtRef.current = 0;
      setAwaitingCameraPermission(false);
      if (scannerRegionRef.current) {
        scannerRegionRef.current.innerHTML = '';
      }
      setCameraZoom(null);
      setScannerActive(false);
      setScannerPending(false);
    }
  }, []);

  const refreshDashboard = useCallback(async (perPage = historyMeta.per_page || DEFAULT_HISTORY_PER_PAGE) => {
    setDashboardLoading(true);

    try {
      const response = await fetch(buildDashboardUrl(perPage), { headers: { Accept: 'application/json' } });

      if ([401, 403].includes(response.status)) {
        redirectToLogin();
        return;
      }

      if (!response.ok) {
        throw new Error('Unable to refresh scanner dashboard.');
      }

      const payload = (await response.json()) as DashboardResponse;
      setStats(payload.stats);
      setHistory(payload.history.items);
      setHistoryMeta(payload.history.meta);
    } finally {
      setDashboardLoading(false);
    }
  }, [historyMeta.per_page, redirectToLogin]);

  const loadMoreHistory = useCallback(async () => {
    if (!scannerPost.trim() || historyLoadingMore || dashboardLoading || !historyMeta.has_more) {
      return;
    }

    setHistoryLoadingMore(true);

    try {
      const nextPage = historyMeta.page + 1;
      const response = await fetch(buildHistoryUrl(nextPage, historyMeta.per_page), {
        headers: { Accept: 'application/json' },
      });

      if ([401, 403].includes(response.status)) {
        redirectToLogin();
        return;
      }

      if (!response.ok) {
        throw new Error('Unable to load more scan history.');
      }

      const payload = (await response.json()) as HistoryResponse;
      setHistory((current) => appendHistoryItems(current, payload.items));
      setHistoryMeta(payload.meta);
    } catch (error) {
      await showScannerAlert({
        icon: 'error',
        title: 'History unavailable',
        text: error instanceof Error ? error.message : 'Unable to load more scan history.',
      });
    } finally {
      setHistoryLoadingMore(false);
    }
  }, [dashboardLoading, historyLoadingMore, historyMeta.has_more, historyMeta.page, historyMeta.per_page, redirectToLogin, scannerPost, showScannerAlert]);

  const handleDashboardRefresh = useCallback(async () => {
    if (dashboardRefreshing || !scannerPost.trim()) {
      return;
    }

    if (dashboardRefreshTimerRef.current) {
      clearTimeout(dashboardRefreshTimerRef.current);
    }

    setDashboardRefreshAnimating(true);
    dashboardRefreshTimerRef.current = setTimeout(() => {
      setDashboardRefreshAnimating(false);
      dashboardRefreshTimerRef.current = null;
    }, 1000);

    setDashboardRefreshing(true);
    try {
      await refreshDashboard();
    } catch (error) {
      await showScannerAlert({
        icon: 'error',
        title: 'Dashboard unavailable',
        text: error instanceof Error ? error.message : 'Unable to refresh scanner dashboard.',
      });
    } finally {
      setDashboardRefreshing(false);
    }
  }, [dashboardRefreshing, refreshDashboard, scannerPost, showScannerAlert]);

  useEffect(() => () => {
    if (dashboardRefreshTimerRef.current) {
      clearTimeout(dashboardRefreshTimerRef.current);
    }
  }, []);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        const response = await fetch(STAFF_SESSION_URL, { headers: { Accept: 'application/json' } });
        if ([401, 403].includes(response.status)) {
          redirectToLogin();
          return;
        }
        if (!response.ok) {
          throw new Error('Unable to load scanner session.');
        }
        const payload = (await response.json()) as StaffSession;
        setSession(payload);
        setScannerPost(payload.scanner_post ?? '');

        if (payload.scanner_post) {
          void refreshDashboard().catch(async () => {
            setDashboardLoading(false);
            await showScannerAlert({
              icon: 'error',
              title: 'Dashboard unavailable',
              text: 'Scanner session loaded, but recent activity could not be loaded.',
            });
          });
        } else {
          setStats(EMPTY_STATS);
          setHistory([]);
          setHistoryMeta(EMPTY_HISTORY_META);
          setDashboardLoading(false);
        }
      } catch {
        void showScannerAlert({
          icon: 'error',
          title: 'Session unavailable',
          text: 'Unable to load scanner session.',
        });
      } finally {
        setLoading(false);
      }
    };

    void load();
    return () => {
      void stopScanner();
    };
  }, [redirectToLogin, refreshDashboard, showScannerAlert, stopScanner]);

  useEffect(() => {
    let mounted = true;
    let removeListener = () => { };

    const syncPermissionState = async () => {
      if (typeof navigator === 'undefined' || !navigator.permissions?.query) {
        if (mounted) {
          setCameraPermissionState('unsupported');
        }
        return;
      }

      try {
        const permissionStatus = await navigator.permissions.query({ name: 'camera' as PermissionName });
        const applyState = () => {
          if (!mounted) {
            return;
          }

          if (permissionStatus.state === 'granted') {
            setCameraPermissionState('granted');
            return;
          }

          if (permissionStatus.state === 'denied') {
            setCameraPermissionState('denied');
            return;
          }

          setCameraPermissionState('prompt');
        };

        applyState();

        if (typeof permissionStatus.addEventListener === 'function') {
          permissionStatus.addEventListener('change', applyState);
          removeListener = () => permissionStatus.removeEventListener('change', applyState);
          return;
        }

        permissionStatus.onchange = applyState;
        removeListener = () => {
          permissionStatus.onchange = null;
        };
      } catch {
        if (mounted) {
          setCameraPermissionState('unsupported');
        }
      }
    };

    void syncPermissionState();

    return () => {
      mounted = false;
      removeListener();
    };
  }, []);

  useEffect(() => {
    const handleBeforeInstallPrompt = (event: Event) => {
      const promptEvent = event as BeforeInstallPromptEvent;
      promptEvent.preventDefault();
      setInstallPromptEvent(promptEvent);
      setInstallBannerDismissed(false);
    };

    const handleAppInstalled = () => {
      setAppInstalled(true);
      setInstallPromptEvent(null);
      setInstallBannerDismissed(true);
    };

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt as EventListener);
    window.addEventListener('appinstalled', handleAppInstalled);

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt as EventListener);
      window.removeEventListener('appinstalled', handleAppInstalled);
    };
  }, []);

  const applyResult = useCallback((result: ScanResult) => {
    setLatest(result);
    setStats(result.stats);
    if (!result.activity_item) {
      return;
    }

    const visibleLimit = Math.max(historyMeta.page * historyMeta.per_page, historyMeta.per_page || DEFAULT_HISTORY_PER_PAGE);

    setHistory((current) => prependHistoryItem(current, result.activity_item as HistoryItem, visibleLimit));
    setHistoryMeta((current) => {
      const total = current.total + 1;
      const loadedItems = Math.max(current.page * current.per_page, current.per_page || DEFAULT_HISTORY_PER_PAGE);

      return {
        ...current,
        total,
        has_more: loadedItems < total,
      };
    });
  }, [historyMeta.page, historyMeta.per_page]);

  const submitScan = useCallback(async (payload: string) => {
    setScanBusy(true);
    try {
      const response = await fetch(STAFF_SCAN_URL, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ payload }),
      });

      if ([401, 403].includes(response.status)) {
        redirectToLogin();
        return;
      }

      const result = (await response.json()) as ScanResult & { message?: string };
      if (!response.ok) {
        throw new Error(result.message || 'Unable to process scan.');
      }

      await showScannerAlert(scanResultAlertConfig(result));
      setManualLookup(null);
      applyResult(result);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unable to process scan.';
      await showScannerAlert({
        icon: 'error',
        title: 'Scan failed',
        text: message,
      });
      setLatest({ status: 'invalid', message, participant: null, stats });
    } finally {
      setScanBusy(false);
    }
  }, [applyResult, redirectToLogin, showScannerAlert, stats]);

  const startScanner = async () => {
    if (!scannerPost.trim()) {
      void showScannerAlert({
        icon: 'error',
        title: 'Gate not assigned',
        text: 'No gate is assigned to this session. Please sign in again.',
      });
      return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
      setCameraPermissionState('unsupported');
      setCameraMessage('Camera access is unavailable in this browser. Use Manual Entry below.');
      void showScannerAlert({
        icon: 'error',
        title: 'Scanner unavailable',
        text: 'Live QR scanning is unavailable.',
      });
      return;
    }

    try {
      setScannerPending(true);
      setCameraMessage(cameraPermissionState === 'denied'
        ? 'Trying camera access again. If the browser still blocks it, check site permissions and retry.'
        : '');

      await stopScanner();
      setAwaitingCameraPermission(cameraPermissionState !== 'granted');

      if (scannerRegionRef.current) {
        scannerRegionRef.current.innerHTML = '';
      }

      const html5QrCode = new Html5Qrcode(SCANNER_REGION_ID, {
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        useBarCodeDetectorIfSupported: cameraPlatform === 'android',
        verbose: false,
      });

      html5QrCodeRef.current = html5QrCode;

      const scanConfig = {
        fps: cameraPlatform === 'android' ? 12 : 10,
        aspectRatio: 4 / 3,
        qrbox: preferredQrBoxSize,
        disableFlip: false,
      };

      const onDecode = async (decodedText: string) => {
        if (detectingRef.current || scanBusy || pendingAlertCountRef.current > 0) {
          return;
        }

        const rawValue = decodedText.trim();
        if (!rawValue) {
          return;
        }

        const now = Date.now();
        if (lastValueRef.current === rawValue && now - lastValueAtRef.current < 2000) {
          return;
        }

        detectingRef.current = true;
        try {
          lastValueRef.current = rawValue;
          lastValueAtRef.current = now;
          await submitScan(rawValue);
        } finally {
          detectingRef.current = false;
        }
      };

      const onDecodeError = () => {
        // html5-qrcode calls this very frequently while no QR is present.
        // Avoid flashing the UI with false alarm messages.
        return;
      };

      const startTargets = await buildCameraStartTargets(cameraPlatform);
      let lastStartError: unknown = null;
      let scannerStarted = false;

      for (const startTarget of startTargets) {
        try {
          await html5QrCode.start(
            startTarget,
            {
              ...scanConfig,
              videoConstraints: buildCameraVideoConstraints(startTarget, cameraPlatform),
            },
            onDecode,
            onDecodeError,
          );
          scannerStarted = true;
          break;
        } catch (error) {
          lastStartError = error;

          try {
            await html5QrCode.start(startTarget, scanConfig, onDecode, onDecodeError);
            scannerStarted = true;
            break;
          } catch (fallbackError) {
            lastStartError = fallbackError;
          }
        }
      }

      if (!scannerStarted) {
        throw lastStartError ?? new Error('Unable to start any available camera.');
      }

      setScannerActive(true);
      setCameraPermissionState('granted');
      setCameraZoom(await configureActiveScannerCamera(scannerRegionRef.current, cameraPlatform));
      void showScannerAlert(cameraReadyAlertConfig());
    } catch (error) {
      const failure = cameraStartFailure(error, cameraSurface, cameraPlatform);
      setCameraPermissionState(failure.permissionState);
      setCameraMessage(failure.message);
      void showScannerAlert({
        icon: 'error',
        title: 'Unable to start scanner',
        text: failure.message,
      });
      await stopScanner();
    } finally {
      setAwaitingCameraPermission(false);
      setScannerPending(false);
    }
  };

  const handleManualLookup = async () => {
    if (!manualCode.trim()) {
      void showScannerAlert({
        icon: 'warning',
        title: 'Entry code required',
        text: 'Enter the fallback entry code first.',
      });
      return;
    }
    setManualBusy(true);
    try {
      const response = await fetch(STAFF_MANUAL_LOOKUP_URL, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ entry_code: manualCode }),
      });
      if ([401, 403].includes(response.status)) {
        redirectToLogin();
        return;
      }
      const result = (await response.json()) as ManualLookupResult & { message?: string };
      if (!response.ok) {
        throw new Error(result.message || 'Unable to lookup entry code.');
      }
      setManualLookup(result);
      void showScannerAlert({
        icon: result.found ? 'success' : 'error',
        title: result.found ? 'Participant found' : 'Participant not found',
        text: result.message || (result.found ? 'Participant found.' : 'Participant was not found.'),
      });
    } catch (error) {
      void showScannerAlert({
        icon: 'error',
        title: 'Lookup failed',
        text: error instanceof Error ? error.message : 'Unable to lookup entry code.',
      });
    } finally {
      setManualBusy(false);
    }
  };

  const handleManualConfirm = async () => {
    if (!manualLookup?.resolution_token) {
      void showScannerAlert({
        icon: 'warning',
        title: 'Lookup required',
        text: 'Lookup the entry code before confirming.',
      });
      return;
    }
    setConfirmBusy(true);
    try {
      const response = await fetch(STAFF_MANUAL_CONFIRM_URL, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ resolution_token: manualLookup.resolution_token }),
      });
      if ([401, 403].includes(response.status)) {
        redirectToLogin();
        return;
      }
      const result = (await response.json()) as ScanResult & { message?: string };
      if (!response.ok) {
        throw new Error(result.message || 'Unable to confirm entry.');
      }
      await showScannerAlert(scanResultAlertConfig(result));
      setManualLookup(null);
      setManualCode('');
      applyResult(result);
    } catch (error) {
      void showScannerAlert({
        icon: 'error',
        title: 'Confirmation failed',
        text: error instanceof Error ? error.message : 'Unable to confirm entry.',
      });
    } finally {
      setConfirmBusy(false);
    }
  };

  const handleInstallApp = async () => {
    if (!installPromptEvent) {
      return;
    }

    setInstallingApp(true);

    try {
      await installPromptEvent.prompt();
      await installPromptEvent.userChoice;
    } finally {
      setInstallingApp(false);
      setInstallPromptEvent(null);
      setInstallBannerDismissed(true);
    }
  };

  const logout = async () => {
    try {
      const response = await fetch(STAFF_LOGOUT_URL, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      });
      const result = (await response.json()) as { redirect?: string };
      await stopScanner();
      window.location.href = result.redirect || STAFF_LOGIN_URL;
    } catch {
      await stopScanner();
      window.location.href = STAFF_LOGIN_URL;
    }
  };

  if (loading) {
    return (
      <AuthPageShell onCanvasReady={handleCanvasReady} onPageClick={handlePageClick}>
        <div className="flex min-h-screen items-center justify-center px-4">
          <div className="rounded-[2rem] border border-white/20 bg-white/95 px-8 py-10 text-center shadow-[0_30px_80px_rgba(0,0,0,0.3)]">
            <Loader2 className="mx-auto h-8 w-8 animate-spin text-sky-600" aria-hidden="true" />
            <p className="mt-4 text-sm font-semibold text-slate-700">Loading staff scanner session...</p>
          </div>
        </div>
      </AuthPageShell>
    );
  }

  const latestMeta = statusMeta(latest.status);
  const LatestIcon = latestMeta.icon;
  const cameraSurface = detectCameraSurface();
  const cameraPlatform = detectCameraPlatform();
  const permissionNotice = describeCameraPermission(
    cameraPermissionState,
    cameraSurface,
    cameraPlatform,
    awaitingCameraPermission,
  );
  const installBannerVisible = cameraPlatform === 'android'
    && cameraSurface !== 'pwa'
    && !appInstalled
    && Boolean(installPromptEvent)
    && !installBannerDismissed;
  const quickNavItems = [
    { key: 'home', label: 'Home', icon: House },
    { key: 'stats', label: 'Stats', icon: BarChart3 },
    { key: 'profile', label: 'Profile', icon: UserRound },
  ] as const;
  const homeTabClass = activeTab === 'home' ? 'space-y-6' : 'hidden space-y-6';
  const statsTabClass = activeTab === 'stats' ? 'space-y-6' : 'hidden space-y-6';
  const profileTabClass = activeTab === 'profile' ? 'block' : 'hidden';
  const homeBannerClass = activeTab === 'home' ? 'block' : 'hidden';
  const homeInlineBannerClass = activeTab === 'home' ? 'inline-flex' : 'hidden';

  return (
    <AuthPageShell
      skipHref="#staff-scanner-main"
      skipLabel="Skip to scanner controls"
      onCanvasReady={handleCanvasReady}
      onPageClick={handlePageClick}
    >
      <motion.main id="staff-scanner-main" initial={{ opacity: 0, y: 18 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }} className="relative px-4 py-6 pb-28">
        <AuthCardFrame className="mx-auto w-full max-w-[580px]">
          <AuthCardHeader
            eyebrow="Gate Operations"
            title="Staff Scanner"
            description="Scan festival QR tickets, recover attendees from fallback entry code, and keep gate throughput moving."
            note={scannerPost ? `Active gate: ${scannerPost}` : 'Gate assignment is missing. Please sign in again.'}
            topSlot={(
              <div className="flex flex-col gap-4">
                <div className="flex items-center gap-4">
                  <div className="rounded-[1.35rem] border border-white/15 bg-white/10 px-4 py-3 shadow-[0_14px_45px_rgba(12,74,110,0.22)] backdrop-blur-sm">
                    <img src={SONGKRAN_LOGO_URL} alt="Songkran Festival 2026 logo" className="h-12 w-auto" />
                  </div>
                  <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.28em] text-sky-50">
                    <ScanLine className="h-3.5 w-3.5" aria-hidden="true" />
                    Staff Access
                  </div>
                </div>
                <div className="flex flex-col gap-3">
                  <div className="rounded-[1.3rem] border border-white/15 bg-white/10 px-4 py-3 text-sm text-sky-50 shadow-[0_14px_45px_rgba(12,74,110,0.18)] backdrop-blur-sm">
                    <div className="flex items-center gap-3">
                      <span className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/20 bg-white/10 text-sky-50">
                        <UserRound className="h-4 w-4" aria-hidden="true" />
                      </span>
                      <div>
                        <p className="text-[11px] font-bold uppercase tracking-[0.28em] text-sky-100/80">Operator</p>
                        <p className="mt-1 font-semibold">{session?.user.email ?? '-'}</p>
                      </div>
                    </div>
                  </div>
                  <div className="rounded-[1.3rem] border border-white/15 bg-white/10 px-4 py-3 text-sm text-sky-50 shadow-[0_14px_45px_rgba(12,74,110,0.18)] backdrop-blur-sm">
                    <div className="flex items-center gap-3">
                      <span className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/20 bg-white/10 text-sky-50">
                        <MapPin className="h-4 w-4" aria-hidden="true" />
                      </span>
                      <div>
                        <p className="text-[11px] font-bold uppercase tracking-[0.28em] text-sky-100/80">Post Scanner Gate</p>
                        <p className="mt-1 font-semibold">{scannerPost || '-'}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}
          />

          <div className="space-y-6 bg-white px-5 py-6">
            {installBannerVisible ? (
              <div className={`${homeBannerClass} rounded-[1.8rem] border border-sky-200 bg-[linear-gradient(135deg,rgba(240,249,255,0.96),rgba(224,242,254,0.92))] p-4 shadow-[0_18px_45px_rgba(2,132,199,0.12)]`}>
                <div className="flex items-start gap-4">
                  <img src={PWA_APP_ICON_URL} alt="Songkran Scanner app icon" className="h-14 w-14 rounded-[1.1rem] border border-sky-100 bg-white object-cover shadow-[0_10px_24px_rgba(12,74,110,0.12)]" />
                  <div className="min-w-0 flex-1">
                    <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-sky-600">Install App</p>
                    <h3 className="mt-2 text-lg font-black tracking-tight text-slate-950" style={{ fontFamily: '"Kanit", sans-serif' }}>Install Songkran Scanner</h3>
                    <p className="mt-2 text-sm leading-relaxed text-slate-600">Add this scanner to the Android home screen so staff can launch the gate app faster and use it in its own app window.</p>
                    <div className="mt-4 flex flex-wrap gap-2">
                      <button type="button" onClick={() => void handleInstallApp()} disabled={installingApp} className={authPrimaryButtonClass('h-[46px] px-5 text-sm')}>
                        {installingApp ? <Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" /> : null}
                        Install App
                      </button>
                      <button type="button" onClick={() => setInstallBannerDismissed(true)} className="inline-flex h-[46px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-50">
                        Not now
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}
            <div className="space-y-6">
              <section className={homeTabClass}>
                <div className="rounded-[1.8rem] border border-sky-100 bg-sky-50/70 p-5">
                  <div className="flex flex-wrap justify-end gap-3">
                    <button type="button" onClick={() => void startScanner()} disabled={scannerActive || scannerPending || !scannerPost} className={authPrimaryButtonClass('h-[50px] px-5 text-sm')}>{scannerPending && !scannerActive ? <Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" /> : <PlayCircle className="h-4.5 w-4.5" aria-hidden="true" />}Start Scan</button>
                    <button type="button" onClick={() => void stopScanner()} disabled={!scannerActive || scannerPending} className="inline-flex h-[50px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">{scannerPending && scannerActive ? <Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" /> : <PauseCircle className="h-4.5 w-4.5" aria-hidden="true" />}Stop Scan</button>
                  </div>

                  <div className={`mt-4 rounded-[1.35rem] border px-4 py-4 ${permissionToneClass(permissionNotice.tone)}`}>
                    <div className="flex flex-col gap-3">
                      <div>
                        <p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-75">Camera</p>
                        <p className="mt-2 text-sm font-semibold">{permissionNotice.title}</p>
                        <p className="mt-1 text-sm leading-relaxed opacity-90">{permissionNotice.body}</p>
                      </div>
                      <div className="flex flex-wrap gap-2 text-[11px] font-bold uppercase tracking-[0.2em] opacity-75">
                        <span className="rounded-full border border-current/15 bg-white/55 px-3 py-1">{cameraSurface === 'pwa' ? 'PWA' : 'Browser'}</span>
                        <span className="rounded-full border border-current/15 bg-white/55 px-3 py-1">{cameraPlatform === 'ios' ? 'iPhone / iPad' : cameraPlatform === 'android' ? 'Android' : 'Desktop'}</span>
                      </div>
                    </div>
                  </div>

                  <div className="mt-5 space-y-4">
                    <div className="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-slate-950">
                      <div className="flex items-center justify-between border-b border-white/10 px-4 py-3 text-sm text-sky-50"><span className="font-semibold">Live Camera Feed</span><span className={`inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] ${scannerActive ? 'border-emerald-300/40 bg-emerald-400/10 text-emerald-200' : 'border-white/15 bg-white/5 text-sky-100/80'}`}>{scannerActive ? 'Active' : 'Standby'}</span></div>
                      <div className="relative aspect-[5/6] min-h-[24rem] sm:aspect-[4/3] sm:min-h-0 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_55%),linear-gradient(135deg,_rgba(12,74,110,0.92),_rgba(15,23,42,0.96))]">
                        <div
                          id={SCANNER_REGION_ID}
                          ref={scannerRegionRef}
                          className="h-full w-full [&_canvas]:h-full [&_canvas]:w-full [&_canvas]:object-cover [&_video]:h-full [&_video]:w-full [&_video]:object-cover"
                        />
                        {!scannerActive ? <div className="absolute inset-0 flex flex-col items-center justify-center gap-4 px-6 text-center text-sky-100"><div className="rounded-full border border-white/15 bg-white/10 p-5"><Camera className="h-10 w-10" aria-hidden="true" /></div><div><p className="text-lg font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>Camera waiting</p><p className="mt-2 text-sm leading-relaxed text-sky-100/80">Start Scan to open the camera. Use Manual Entry below if this device cannot decode QR live.</p></div></div> : null}
                        <div className="pointer-events-none absolute inset-x-[8%] inset-y-[12%] rounded-[1.4rem] border-2 border-dashed border-white/35 shadow-[0_0_0_9999px_rgba(2,6,23,0.12)] sm:inset-[15%]" />
                      </div>
                    </div>

                    {cameraZoom ? (
                      <div className="rounded-[1.4rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_12px_35px_rgba(2,132,199,0.08)]">
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Camera Zoom</p>
                            <p className="mt-2 text-sm leading-relaxed text-slate-600">Slide right when the QR looks small from a distance. Slide left when the QR is too close to the camera.</p>
                          </div>
                          <span className="inline-flex min-w-[64px] justify-center rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">
                            {formatZoomValue(cameraZoom.value)}
                          </span>
                        </div>

                        <div className="mt-4">
                          <input
                            type="range"
                            min={cameraZoom.min}
                            max={cameraZoom.max}
                            step={cameraZoom.step}
                            value={cameraZoom.value}
                            onChange={(event) => handleCameraZoomChange(Number(event.target.value))}
                            className="h-2 w-full cursor-pointer appearance-none rounded-full bg-sky-100 accent-sky-600"
                            aria-label="Camera zoom"
                          />
                          <div className="mt-2 flex items-center justify-between text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                            <span>Near QR</span>
                            <span>Far QR</span>
                          </div>
                        </div>
                      </div>
                    ) : null}

                    <div className={`rounded-[1.6rem] border p-5 ${latestMeta.panel}`}>
                      <div className="flex items-start justify-between gap-4">
                        <div><p className={`inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] ${latestMeta.badge}`}>{latestMeta.label}</p><h3 className="mt-3 text-2xl font-black tracking-tight text-slate-950" style={{ fontFamily: '"Kanit", sans-serif' }}>Latest Result</h3></div>
                        <LatestIcon className="h-8 w-8 text-slate-700" aria-hidden="true" />
                      </div>
                      <p className="mt-4 text-sm leading-relaxed text-slate-700">{latest.message}</p>
                      {cameraMessage ? <div className="mt-5 rounded-[1.3rem] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{cameraMessage}</div> : null}
                    </div>
                  </div>
                </div>

                <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                  <AuthSectionHeading eyebrow="Fallback Flow" title="Manual Entry" description="Use the entry code when the QR is hard to read." />
                  <div className="mt-5 space-y-4">
                    <div className="grid gap-3">
                      <div><label htmlFor="manual-entry-code" className="mb-1.5 block text-sm font-semibold text-slate-700">Entry Code</label><input id="manual-entry-code" type="text" autoComplete="off" placeholder="ABCD-2345" value={manualCode} onChange={(event) => setManualCode(formatEntryCode(event.target.value))} className={authInputClass(false, { withIcon: false })} /></div>
                      <button type="button" onClick={() => void handleManualLookup()} disabled={manualBusy} className={authPrimaryButtonClass('h-[50px] px-5 text-sm')}>{manualBusy ? <><Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" />Looking Up...</> : <><Search className="h-4.5 w-4.5" aria-hidden="true" />Lookup</>}</button>
                    </div>

                    <AnimatePresence>
                      {manualLookup ? (
                        <motion.div
                          initial={{ opacity: 0, y: 8 }}
                          animate={{ opacity: 1, y: 0 }}
                          exit={{ opacity: 0, y: -8 }}
                          className={`rounded-[1.5rem] border px-4 py-4 ${manualLookup.found ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/70'}`}
                        >
                          <p className="text-sm font-semibold text-slate-900">
                            {manualLookup.message || (manualLookup.found ? 'Participant found.' : 'Participant was not found.')}
                          </p>
                          {manualLookup.participant ? (
                            <div className="mt-3 grid gap-3">
                              {participantDetailField('Email', manualLookup.participant.email || '-')}
                              {participantDetailField('Full Name', manualLookup.participant.full_name || manualLookup.participant.name || '-')}
                              {participantDetailField('Phone Number', manualLookup.participant.phone_number || '-')}
                              {participantDetailField('Country', manualLookup.participant.country_label || manualLookup.participant.country || '-')}
                              {participantDetailField('Entry Code', manualLookup.participant.entry_code_display || '-')}
                            </div>
                          ) : null}
                        </motion.div>
                      ) : null}
                    </AnimatePresence>

                    <button type="button" onClick={() => void handleManualConfirm()} disabled={confirmBusy || !manualLookup?.found} className="flex h-[50px] w-full items-center justify-center gap-2 rounded-2xl border border-red-600 bg-red-600 px-5 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(220,38,38,0.22)] transition-all hover:border-red-700 hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">{confirmBusy ? <><Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" />Confirming...</> : <><CheckCircle2 className="h-4.5 w-4.5" aria-hidden="true" />Confirm Entry</>}</button>
                  </div>
                </div>
              </section>

              <aside className="space-y-6">
                <div className={statsTabClass}>
                  <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                    <AuthSectionHeading eyebrow="Today" title="Stats" description="Live totals refresh after every QR or manual confirmation." />
                    <div className="mt-5 grid gap-3">
                      <div className="rounded-[1.3rem] border border-sky-100 bg-sky-50/80 px-4 py-4 text-sky-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Total Scans</p><p className="mt-2 text-3xl font-black tracking-tight">{dashboardLoading ? '...' : stats.total_scans}</p></div>
                      <div className="rounded-[1.3rem] border border-emerald-100 bg-emerald-50/80 px-4 py-4 text-emerald-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Valid</p><p className="mt-2 text-3xl font-black tracking-tight">{dashboardLoading ? '...' : stats.successful_scans}</p></div>
                      <div className="rounded-[1.3rem] border border-amber-100 bg-amber-50/80 px-4 py-4 text-amber-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Duplicate</p><p className="mt-2 text-3xl font-black tracking-tight">{dashboardLoading ? '...' : stats.duplicate_scans}</p></div>
                      <div className="rounded-[1.3rem] border border-red-100 bg-red-50/80 px-4 py-4 text-red-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Invalid</p><p className="mt-2 text-3xl font-black tracking-tight">{dashboardLoading ? '...' : stats.invalid_scans}</p></div>
                    </div>
                  </div>

                  <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                    <div className="flex items-start justify-between gap-3">
                      <AuthSectionHeading eyebrow="Recent Activity" title="Recent Scans" description="Latest activity for the selected gate today." />
                      <button type="button" onClick={() => void handleDashboardRefresh()} disabled={dashboardRefreshing} className={`inline-flex h-10 w-10 items-center justify-center rounded-2xl border transition-all ${(dashboardRefreshing || dashboardRefreshAnimating) ? 'border-sky-200 bg-sky-50 text-sky-600 shadow-[0_12px_24px_rgba(14,165,233,0.18)]' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'} disabled:cursor-wait`} aria-label="Refresh history and stats"><motion.span animate={dashboardRefreshAnimating ? { rotate: 360, scale: [1, 1.08, 1] } : { rotate: 0, scale: 1 }} transition={dashboardRefreshAnimating ? { rotate: { duration: 1, ease: 'easeInOut' }, scale: { duration: 1, ease: 'easeInOut' } } : { duration: 0.2, ease: 'easeOut' }}><RefreshCcw className="h-4.5 w-4.5" aria-hidden="true" /></motion.span></button>
                    </div>
                    <div className="mt-5 space-y-3">
                      {dashboardLoading && history.length === 0 ? <div className="rounded-[1.4rem] border border-sky-100 bg-sky-50 px-4 py-4 text-sm text-sky-700">Loading recent scan activity...</div> : null}
                      {!dashboardLoading && history.length === 0 ? <div className="rounded-[1.4rem] border border-slate-100 bg-slate-50 px-4 py-4 text-sm text-slate-500">No scan activity yet for this gate today.</div> : null}
                      {history.map((item) => { const meta = statusMeta(item.status); return <div key={item.scan_id} className="rounded-[1.4rem] border border-slate-100 bg-slate-50/70 px-4 py-4"><div className="flex items-start justify-between gap-3"><p className="flex items-center gap-1.5 text-xs text-slate-500"><Clock3 className="h-3.5 w-3.5" aria-hidden="true" />{formatScannedAt(item.scanned_at)}</p><span className={`inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] ${meta.badge}`}>{meta.label}</span></div>{historyParticipantDetails(item)}<div className="mt-3 flex flex-wrap items-center justify-between gap-3"><AuthCodeBadge code={item.entry_code_display} label="Entry Code" /><div className="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">{item.scanner_post}</div></div></div>; })}
                      {historyMeta.has_more ? <button type="button" onClick={() => void loadMoreHistory()} disabled={historyLoadingMore} className="inline-flex w-full items-center justify-center gap-2 rounded-[1.4rem] border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition-colors hover:border-sky-300 hover:bg-sky-100 disabled:cursor-wait disabled:opacity-70">{historyLoadingMore ? <><Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />Loading more...</> : 'Load more scans'}</button> : null}
                    </div>
                  </div>
                </div>

                <div className={profileTabClass}>
                  <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                    <AuthSectionHeading eyebrow="Operator" title="Profile" description="Quick access to scanner account details and the locked gate assignment for this session." />
                    <div className="mt-5 space-y-3">
                      <div className="rounded-[1.3rem] border border-sky-100 bg-sky-50/70 px-4 py-4">
                        <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Email</p>
                        <p className="mt-2 break-words text-sm font-semibold text-slate-900">{session?.user.email ?? '-'}</p>
                      </div>
                      <div className="rounded-[1.3rem] border border-sky-100 bg-sky-50/70 px-4 py-4">
                        <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Post Scanner Gate</p>
                        <p className="mt-2 text-sm font-semibold text-slate-900">{scannerPost || 'No gate assigned'}</p>
                      </div>
                      <div className="rounded-[1.3rem] border border-sky-100 bg-sky-50/70 px-4 py-4">
                        <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Gate Locked</p>
                        <p className="mt-2 text-sm leading-relaxed text-slate-600">Gate assignment is fixed when the operator signs in. To use another gate, log out first, then sign in again with the correct gate.</p>
                      </div>
                      <div className="rounded-[1.3rem] border border-sky-100 bg-sky-50/70 px-4 py-4">
                        <p className="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Access Role</p>
                        <p className="mt-2 text-sm font-semibold text-slate-900">Scanner Staff</p>
                      </div>
                      <button type="button" onClick={() => void logout()} className="inline-flex w-full items-center justify-center gap-2 rounded-[1.6rem] border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700 shadow-[0_14px_32px_rgba(239,68,68,0.12)] transition-colors hover:border-red-300 hover:bg-red-100"><LogOut className="h-4.5 w-4.5" aria-hidden="true" />Log Out Scanner</button>
                    </div>
                  </div>
                </div>
              </aside>
            </div>

            {!scannerPost ? <div className={`${homeBannerClass} rounded-[1.6rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800`}>No gate is assigned to this session. Log out, then sign in again and choose the correct gate.</div> : null}
            {scanBusy ? <div className={`${homeInlineBannerClass} items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-4 py-2 text-sm text-sky-700`}><Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />Processing latest scan...</div> : null}
            {cameraMessage ? <div className={`${homeBannerClass} rounded-[1.6rem] border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-700`}><div className="flex items-start gap-2"><AlertCircle className="mt-0.5 h-4.5 w-4.5 flex-shrink-0" aria-hidden="true" /><span>{cameraMessage}</span></div></div> : null}
          </div>
        </AuthCardFrame>

        <nav className="fixed inset-x-0 bottom-0 z-40 px-4 pb-[calc(env(safe-area-inset-bottom)+0.9rem)]" aria-label="Scanner quick navigation">
          <div className="mx-auto max-w-[580px] rounded-[1.7rem] border border-white/30 bg-white/92 p-2 shadow-[0_18px_55px_rgba(15,23,42,0.16)] backdrop-blur-xl">
            <div className="grid grid-cols-3 gap-2">
              {quickNavItems.map((item) => {
                const Icon = item.icon;
                const active = activeTab === item.key;

                return (
                  <button
                    key={item.key}
                    type="button"
                    onClick={() => setActiveTab(item.key)}
                    className={`inline-flex min-h-[60px] flex-col items-center justify-center gap-1 rounded-[1.3rem] border px-3 py-2 transition-all ${active
                      ? 'border-sky-300 bg-sky-100 text-sky-900 shadow-[0_10px_24px_rgba(14,165,233,0.16)]'
                      : 'border-sky-100 bg-sky-50/75 text-slate-700 hover:border-sky-200 hover:bg-sky-100/80'
                      }`}
                    aria-pressed={active}
                  >
                    <Icon className={`h-4.5 w-4.5 ${active ? 'text-sky-700' : 'text-sky-600'}`} aria-hidden="true" />
                    <span className="text-[11px] font-bold uppercase tracking-[0.2em]">{item.label}</span>
                  </button>
                );
              })}
            </div>
          </div>
        </nav>
      </motion.main>
    </AuthPageShell>
  );
}
