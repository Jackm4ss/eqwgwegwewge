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
import { toast } from 'sonner';

import {
  AuthCardFrame,
  AuthCardHeader,
  AuthCodeBadge,
  AuthPageShell,
  AuthSectionHeading,
  authInputClass,
  authPrimaryButtonClass,
} from './AuthShared';

type StaffSession = {
  user: { email: string };
  scanner_post: string | null;
  available_posts: string[];
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
  status: 'success' | 'duplicate' | 'invalid';
  ticket_code: string;
  entry_code_display: string;
  scanner_post: string;
  scanned_at: string;
};

const STAFF_BASE_PATH = '/staff';
const SONGKRAN_LOGO_URL = '/images/Songkran%20logo.png';
const SCANNER_REGION_ID = 'staff-html5-qrcode-region';
const EMPTY_STATS: ScannerStats = { total_scans: 0, successful_scans: 0, duplicate_scans: 0, invalid_scans: 0 };

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

function participantFlagClass(country?: string) {
  const normalized = country?.trim().toLowerCase() ?? '';

  return /^[a-z]{2}$/.test(normalized) ? `fi fi-${normalized}` : '';
}

function notifyScanResult(result: Pick<ScanResult, 'status' | 'message'>) {
  if (result.status === 'success') {
    toast.success(result.message);
    return;
  }

  if (result.status === 'duplicate') {
    toast.warning(result.message);
    return;
  }

  toast.error(result.message);
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
  const size = Math.max(180, Math.min(Math.floor(shortestEdge * 0.72), 320));

  return { width: size, height: size };
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

  const [session, setSession] = useState<StaffSession | null>(null);
  const [scannerPost, setScannerPost] = useState('');
  const [stats, setStats] = useState<ScannerStats>(EMPTY_STATS);
  const [history, setHistory] = useState<HistoryItem[]>([]);
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
  const [awaitingCameraPermission, setAwaitingCameraPermission] = useState(false);
  const [activeTab, setActiveTab] = useState<StaffScannerTab>('home');

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => { addRippleRef.current = fn; }, []);
  const handlePageClick = useCallback((event: MouseEvent<HTMLDivElement>) => { addRippleRef.current?.(event.clientX, event.clientY); }, []);
  const redirectToLogin = useCallback(() => { window.location.href = `${STAFF_BASE_PATH}/login`; }, []);

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
      setScannerActive(false);
      setScannerPending(false);
    }
  }, []);

  const refreshDashboard = useCallback(async () => {
    const [historyResponse, statsResponse] = await Promise.all([
      fetch(`${STAFF_BASE_PATH}/history`, { headers: { Accept: 'application/json' } }),
      fetch(`${STAFF_BASE_PATH}/stats`, { headers: { Accept: 'application/json' } }),
    ]);

    if ([401, 403].includes(historyResponse.status) || [401, 403].includes(statsResponse.status)) {
      redirectToLogin();
      return;
    }

    if (historyResponse.ok) {
      setHistory((await historyResponse.json()) as HistoryItem[]);
    }
    if (statsResponse.ok) {
      setStats((await statsResponse.json()) as ScannerStats);
    }
  }, [redirectToLogin]);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        const response = await fetch(`${STAFF_BASE_PATH}/session`, { headers: { Accept: 'application/json' } });
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
        await refreshDashboard();
      } catch {
        toast.error('Unable to load scanner session.');
      } finally {
        setLoading(false);
      }
    };

    void load();
    return () => {
      void stopScanner();
    };
  }, [redirectToLogin, refreshDashboard, stopScanner]);

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

  const applyResult = useCallback(async (result: ScanResult) => {
    setLatest(result);
    setStats(result.stats);
    await refreshDashboard();
  }, [refreshDashboard]);

  const submitScan = useCallback(async (payload: string) => {
    setScanBusy(true);
    try {
      const response = await fetch(`${STAFF_BASE_PATH}/scan`, {
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

      await applyResult(result);
      setManualLookup(null);
      notifyScanResult(result);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Unable to process scan.';
      toast.error(message);
      setLatest({ status: 'invalid', message, participant: null, stats });
    } finally {
      setScanBusy(false);
    }
  }, [applyResult, redirectToLogin, stats]);

  const startScanner = async () => {
    if (!scannerPost.trim()) {
      toast.error('No gate is assigned to this session. Please sign in again.');
      return;
    }

    if (!navigator.mediaDevices?.getUserMedia) {
      setCameraPermissionState('unsupported');
      setCameraMessage('Camera access is unavailable in this browser. Use Manual Entry below.');
      toast.error('Live QR scanning is unavailable.');
      return;
    }

    if (cameraPermissionState === 'denied') {
      const deniedMessage = cameraDeniedGuidance(cameraSurface, cameraPlatform);
      setCameraMessage(deniedMessage);
      toast.error('Camera permission is blocked.');
      return;
    }

    try {
      setScannerPending(true);
      setCameraMessage('');

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
        qrbox: preferredQrBoxSize,
        disableFlip: false,
      };

      const onDecode = async (decodedText: string) => {
        if (detectingRef.current || scanBusy) {
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
          await html5QrCode.start(startTarget, scanConfig, onDecode, onDecodeError);
          scannerStarted = true;
          break;
        } catch (error) {
          lastStartError = error;
        }
      }

      if (!scannerStarted) {
        throw lastStartError ?? new Error('Unable to start any available camera.');
      }

      setScannerActive(true);
      setCameraPermissionState('granted');
      toast.success('Scanner camera is ready.');
    } catch (error) {
      const failure = cameraStartFailure(error, cameraSurface, cameraPlatform);
      setCameraPermissionState(failure.permissionState);
      setCameraMessage(failure.message);
      toast.error('Unable to start live QR scanning.');
      await stopScanner();
    } finally {
      setAwaitingCameraPermission(false);
      setScannerPending(false);
    }
  };

  const handleManualLookup = async () => {
    if (!manualCode.trim()) {
      toast.error('Enter the fallback entry code first.');
      return;
    }
    setManualBusy(true);
    try {
      const response = await fetch(`${STAFF_BASE_PATH}/manual-lookup`, {
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
      toast[result.found ? 'success' : 'error'](result.message || (result.found ? 'Participant found.' : 'Participant was not found.'));
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Unable to lookup entry code.');
    } finally {
      setManualBusy(false);
    }
  };

  const handleManualConfirm = async () => {
    if (!manualLookup?.resolution_token) {
      toast.error('Lookup the entry code before confirming.');
      return;
    }
    setConfirmBusy(true);
    try {
      const response = await fetch(`${STAFF_BASE_PATH}/manual-confirm`, {
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
      await applyResult(result);
      setManualLookup(null);
      setManualCode('');
      notifyScanResult(result);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Unable to confirm entry.');
    } finally {
      setConfirmBusy(false);
    }
  };

  const logout = async () => {
    try {
      const response = await fetch(`${STAFF_BASE_PATH}/logout`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      });
      const result = (await response.json()) as { redirect?: string };
      await stopScanner();
      window.location.href = result.redirect || `${STAFF_BASE_PATH}/login`;
    } catch {
      await stopScanner();
      window.location.href = `${STAFF_BASE_PATH}/login`;
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
  const quickNavItems = [
    { key: 'home', label: 'Home', icon: House },
    { key: 'stats', label: 'Stats', icon: BarChart3 },
    { key: 'profile', label: 'Profile', icon: UserRound },
  ] as const;
  const homeTabClass = activeTab === 'home' ? 'space-y-6' : 'hidden space-y-6 lg:block';
  const statsTabClass = activeTab === 'stats' ? 'space-y-6' : 'hidden space-y-6 lg:block';
  const profileTabClass = activeTab === 'profile' ? 'block' : 'hidden lg:block';
  const homeBannerClass = activeTab === 'home' ? 'block' : 'hidden lg:block';
  const homeInlineBannerClass = activeTab === 'home' ? 'inline-flex' : 'hidden lg:inline-flex';

  return (
    <AuthPageShell
      skipHref="#staff-scanner-main"
      skipLabel="Skip to scanner controls"
      onCanvasReady={handleCanvasReady}
      onPageClick={handlePageClick}
    >
      <motion.main id="staff-scanner-main" initial={{ opacity: 0, y: 18 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }} className="relative px-4 py-6 pb-28 lg:px-8 lg:pb-6">
        <AuthCardFrame className="mx-auto w-full max-w-[1180px]">
          <AuthCardHeader
            eyebrow="Gate Operations"
            title="Staff Scanner"
            description="Scan festival QR tickets, recover attendees from fallback entry code, and keep gate throughput moving."
            note={scannerPost ? `Active gate: ${scannerPost}` : 'Gate assignment is missing. Please sign in again.'}
            topSlot={(
              <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex items-center gap-4">
                  <div className="rounded-[1.35rem] border border-white/15 bg-white/10 px-4 py-3 shadow-[0_14px_45px_rgba(12,74,110,0.22)] backdrop-blur-sm">
                    <img src={SONGKRAN_LOGO_URL} alt="Songkran Festival 2026 logo" className="h-12 w-auto sm:h-14" />
                  </div>
                  <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.28em] text-sky-50">
                    <ScanLine className="h-3.5 w-3.5" aria-hidden="true" />
                    Staff Access
                  </div>
                </div>
                <div className="flex flex-col gap-3 sm:min-w-[250px]">
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

          <div className="space-y-6 bg-white px-5 py-6 lg:px-6">
            <div className="grid gap-6 xl:grid-cols-[1.55fr_0.95fr]">
              <section className={homeTabClass}>
                <div className="rounded-[1.8rem] border border-sky-100 bg-sky-50/70 p-5">
                  <div className="flex flex-wrap justify-end gap-3">
                    <button type="button" onClick={() => void startScanner()} disabled={scannerActive || scannerPending || !scannerPost} className={authPrimaryButtonClass('h-[50px] px-5 text-sm')}>{scannerPending && !scannerActive ? <Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" /> : <PlayCircle className="h-4.5 w-4.5" aria-hidden="true" />}Start Scan</button>
                    <button type="button" onClick={() => void stopScanner()} disabled={!scannerActive || scannerPending} className="inline-flex h-[50px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">{scannerPending && scannerActive ? <Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" /> : <PauseCircle className="h-4.5 w-4.5" aria-hidden="true" />}Stop Scan</button>
                  </div>

                  <div className={`mt-4 rounded-[1.35rem] border px-4 py-4 ${permissionToneClass(permissionNotice.tone)}`}>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
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

                  <div className="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.9fr)]">
                    <div className="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-slate-950">
                      <div className="flex items-center justify-between border-b border-white/10 px-4 py-3 text-sm text-sky-50"><span className="font-semibold">Live Camera Feed</span><span className={`inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] ${scannerActive ? 'border-emerald-300/40 bg-emerald-400/10 text-emerald-200' : 'border-white/15 bg-white/5 text-sky-100/80'}`}>{scannerActive ? 'Active' : 'Standby'}</span></div>
                      <div className="relative aspect-[4/3] bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_55%),linear-gradient(135deg,_rgba(12,74,110,0.92),_rgba(15,23,42,0.96))]">
                        <div
                          id={SCANNER_REGION_ID}
                          ref={scannerRegionRef}
                          className="h-full w-full [&_canvas]:h-full [&_canvas]:w-full [&_canvas]:object-cover [&_video]:h-full [&_video]:w-full [&_video]:object-cover"
                        />
                        {!scannerActive ? <div className="absolute inset-0 flex flex-col items-center justify-center gap-4 px-6 text-center text-sky-100"><div className="rounded-full border border-white/15 bg-white/10 p-5"><Camera className="h-10 w-10" aria-hidden="true" /></div><div><p className="text-lg font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>Camera waiting</p><p className="mt-2 text-sm leading-relaxed text-sky-100/80">Start Scan to open the camera. Use Manual Entry below if this device cannot decode QR live.</p></div></div> : null}
                        <div className="pointer-events-none absolute inset-[15%] rounded-[1.4rem] border-2 border-dashed border-white/35 shadow-[0_0_0_9999px_rgba(2,6,23,0.12)]" />
                      </div>
                    </div>

                    <div className={`rounded-[1.6rem] border p-5 ${latestMeta.panel}`}>
                      <div className="flex items-start justify-between gap-4">
                        <div><p className={`inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.24em] ${latestMeta.badge}`}>{latestMeta.label}</p><h3 className="mt-3 text-2xl font-black tracking-tight text-slate-950" style={{ fontFamily: '"Kanit", sans-serif' }}>Latest Result</h3></div>
                        <LatestIcon className="h-8 w-8 text-slate-700" aria-hidden="true" />
                      </div>
                      <p className="mt-4 text-sm leading-relaxed text-slate-700">{latest.message}</p>
                      {latest.participant ? (() => {
                        const participantFlag = participantFlagClass(latest.participant.country);

                        return (
                          <div className="mt-5 space-y-4 rounded-[1.4rem] border border-white/70 bg-white/80 p-4">
                            <div className="grid gap-3 sm:grid-cols-2">
                              <div>
                                <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Email</p>
                                <p className="mt-1 break-words text-sm font-semibold text-slate-900">{latest.participant.email || '-'}</p>
                              </div>
                              <div>
                                <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Full Name</p>
                                <p className="mt-1 text-sm font-semibold text-slate-900">{latest.participant.full_name || latest.participant.name || '-'}</p>
                              </div>
                              <div>
                                <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Phone Number</p>
                                <div className="mt-1 flex items-center gap-2">
                                  {participantFlag ? <span className={`${participantFlag} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" /> : null}
                                  <p className="break-words text-sm font-semibold text-slate-900">{latest.participant.phone_number || '-'}</p>
                                </div>
                              </div>
                              <div>
                                <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Country</p>
                                <div className="mt-1 flex items-center gap-2">
                                  {participantFlag ? <span className={`${participantFlag} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" /> : null}
                                  <p className="text-sm font-semibold text-slate-900">{latest.participant.country_label || latest.participant.country || '-'}</p>
                                </div>
                              </div>
                              <div className="sm:col-span-2">
                                <p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Entry Code</p>
                                <p className="mt-1 text-sm font-semibold text-slate-900">{latest.participant.entry_code_display || '-'}</p>
                              </div>
                            </div>
                          </div>
                        );
                      })() : null}
                      {cameraMessage ? <div className="mt-5 rounded-[1.3rem] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{cameraMessage}</div> : null}
                    </div>
                  </div>
                </div>

                <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                  <AuthSectionHeading eyebrow="Fallback Flow" title="Manual Entry" description="Gunakan fallback entry code saat QR sulit dibaca atau attendee membuka ticket dari device lain." />
                  <div className="mt-5 space-y-4">
                    <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto]">
                      <div><label htmlFor="manual-entry-code" className="mb-1.5 block text-sm font-semibold text-slate-700">Entry Code</label><input id="manual-entry-code" type="text" autoComplete="off" placeholder="ABCD-2345" value={manualCode} onChange={(event) => setManualCode(formatEntryCode(event.target.value))} className={authInputClass(false, { withIcon: false })} /></div>
                      <button type="button" onClick={() => void handleManualLookup()} disabled={manualBusy} className={authPrimaryButtonClass('h-[50px] px-5 text-sm')}>{manualBusy ? <><Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" />Looking Up...</> : <><Search className="h-4.5 w-4.5" aria-hidden="true" />Lookup</>}</button>
                      <button type="button" onClick={() => void handleManualConfirm()} disabled={confirmBusy || !manualLookup?.found} className="inline-flex h-[50px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">{confirmBusy ? <><Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" />Confirming...</> : <><CheckCircle2 className="h-4.5 w-4.5" aria-hidden="true" />Confirm Entry</>}</button>
                    </div>

                    <AnimatePresence>
                      {manualLookup ? <motion.div initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }} className={`rounded-[1.5rem] border px-4 py-4 ${manualLookup.found ? 'border-emerald-200 bg-emerald-50/60' : 'border-amber-200 bg-amber-50/70'}`}><p className="text-sm font-semibold text-slate-900">{manualLookup.message || (manualLookup.found ? 'Participant found.' : 'Participant was not found.')}</p>{manualLookup.participant ? <div className="mt-3 grid gap-3 sm:grid-cols-2"><div><p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Participant</p><p className="mt-1 text-sm font-semibold text-slate-900">{manualLookup.participant.name || '-'}</p></div><div><p className="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Ticket Code</p><p className="mt-1 text-sm font-semibold text-slate-900">{manualLookup.participant.ticket_code || '-'}</p></div></div> : null}<div className="mt-4"><AuthCodeBadge code={manualLookup.participant?.entry_code_display} label="Fallback Entry Code" /></div></motion.div> : null}
                    </AnimatePresence>
                  </div>
                </div>
              </section>

              <aside className="space-y-6">
                <div className={statsTabClass}>
                  <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                    <AuthSectionHeading eyebrow="Today" title="Stats" description="Live totals refresh after every QR or manual confirmation." />
                    <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                      <div className="rounded-[1.3rem] border border-sky-100 bg-sky-50/80 px-4 py-4 text-sky-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Total Scans</p><p className="mt-2 text-3xl font-black tracking-tight">{stats.total_scans}</p></div>
                      <div className="rounded-[1.3rem] border border-emerald-100 bg-emerald-50/80 px-4 py-4 text-emerald-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Valid</p><p className="mt-2 text-3xl font-black tracking-tight">{stats.successful_scans}</p></div>
                      <div className="rounded-[1.3rem] border border-amber-100 bg-amber-50/80 px-4 py-4 text-amber-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Duplicate</p><p className="mt-2 text-3xl font-black tracking-tight">{stats.duplicate_scans}</p></div>
                      <div className="rounded-[1.3rem] border border-red-100 bg-red-50/80 px-4 py-4 text-red-800"><p className="text-[11px] font-bold uppercase tracking-[0.24em] opacity-80">Invalid</p><p className="mt-2 text-3xl font-black tracking-tight">{stats.invalid_scans}</p></div>
                    </div>
                  </div>

                  <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                    <div className="flex items-start justify-between gap-3">
                      <AuthSectionHeading eyebrow="Recent Activity" title="Recent Scans" description="Latest activity for the selected gate." />
                      <button type="button" onClick={() => void refreshDashboard()} className="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50" aria-label="Refresh history and stats"><RefreshCcw className="h-4.5 w-4.5" aria-hidden="true" /></button>
                    </div>
                    <div className="mt-5 space-y-3">
                      {history.length === 0 ? <div className="rounded-[1.4rem] border border-slate-100 bg-slate-50 px-4 py-4 text-sm text-slate-500">No scan activity yet for this gate today.</div> : history.map((item, index) => { const meta = statusMeta(item.status); return <div key={`${item.ticket_code}-${item.scanned_at}-${index}`} className="rounded-[1.4rem] border border-slate-100 bg-slate-50/70 px-4 py-4"><div className="flex items-start justify-between gap-3"><div className="min-w-0"><p className="text-sm font-semibold text-slate-900">{item.ticket_code || 'Unknown Ticket'}</p><p className="mt-1 flex items-center gap-1.5 text-xs text-slate-500"><Clock3 className="h-3.5 w-3.5" aria-hidden="true" />{formatScannedAt(item.scanned_at)}</p></div><span className={`inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] ${meta.badge}`}>{meta.label}</span></div><div className="mt-3 flex flex-wrap items-center justify-between gap-3"><AuthCodeBadge code={item.entry_code_display} label="Entry Code" /><div className="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">{item.scanner_post}</div></div></div>; })}
                    </div>
                  </div>
                </div>

                <div className={profileTabClass}>
                  <div className="rounded-[1.8rem] border border-sky-100 bg-white p-5 shadow-[0_18px_50px_rgba(2,132,199,0.08)]">
                    <AuthSectionHeading eyebrow="Operator" title="Profile" description="Quick access to scanner account details and session controls." />
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

        <nav className="fixed inset-x-0 bottom-0 z-40 px-4 pb-[calc(env(safe-area-inset-bottom)+0.9rem)] lg:hidden" aria-label="Scanner quick navigation">
          <div className="mx-auto max-w-[1180px] rounded-[1.7rem] border border-white/30 bg-white/92 p-2 shadow-[0_18px_55px_rgba(15,23,42,0.16)] backdrop-blur-xl">
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
