import '../css/staff.css';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

type ScanDebug = {
    received_payload?: string | null;
    received_payload_hex?: string | null;
    normalized_payload?: string | null;
    normalized_payload_hex?: string | null;
    colon_count?: number | null;
    prefix_guess?: string | null;
    parser_status?: string | null;
    parser_reason?: string | null;
};

type ScanResponse = {
    status: 'valid' | 'duplicate' | 'invalid' | 'expired';
    reason: string;
    user_id?: string | null;
    ticket_code?: string | null;
    scan_date?: string | null;
    scanned_at?: string | null;
    gate_name?: string | null;
    scanner_name?: string | null;
    operator_name?: string | null;
    participant_name?: string | null;
    participant_phone_number?: string | null;
    participant_identity_label?: string | null;
    participant_identity_number?: string | null;
    debug?: ScanDebug | null;
};

type StatsResponse = {
    total_scans: number;
    valid_scans: number;
    duplicate_scans: number;
    invalid_scans: number;
    expired_scans: number;
    last_scanned_at?: string | null;
    last_result?: string | null;
};

const SCAN_COOLDOWN_MS = 1600;
const DEFAULT_SCAN_HINT = 'Position the QR in the center of the frame, move it closer to the laptop camera, and increase screen brightness if needed.';
const INITIAL_RESULT_NOTE = 'Point the camera at the participant QR code to begin ticket validation.';
const SUPPORTED_SCAN_FORMATS = [
    Html5QrcodeSupportedFormats.QR_CODE,
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.DATA_MATRIX,
];

const scannerRoot = document.querySelector<HTMLElement>('[data-scanner-app]');
if (scannerRoot) {
    void bootScanner(scannerRoot);
}

const statsRoot = document.querySelector<HTMLElement>('[data-staff-stats]');
if (statsRoot) {
    void bootStatsPage(statsRoot);
}

async function bootScanner(root: HTMLElement): Promise<void> {
    const scanUrl = root.dataset.scanUrl;
    const statsUrl = root.dataset.statsUrl;
    const debugEnabled = root.dataset.debugEnabled === '1';

    if (!scanUrl || !statsUrl) {
        return;
    }

    const qrReaderId = 'qr-reader';
    const statusBadge = mustQuery('#scan-status-badge');
    const headline = mustQuery('#scan-status-headline');
    const note = mustQuery('#scan-status-note');
    const ticketCode = mustQuery('#scan-ticket-code');
    const userId = mustQuery('#scan-user-id');
    const scannedAt = mustQuery('#scan-scanned-at');
    const reason = mustQuery('#scan-reason');
    const connectionBadge = mustQuery('#connection-badge');
    const debugPanel = document.querySelector<HTMLElement>('#scan-debug-panel');
    const debugFrontendRaw = document.querySelector<HTMLElement>('#scan-debug-frontend-raw');
    const debugFrontendNormalized = document.querySelector<HTMLElement>('#scan-debug-frontend-normalized');
    const debugBackendRaw = document.querySelector<HTMLElement>('#scan-debug-backend-raw');
    const debugBackendNormalized = document.querySelector<HTMLElement>('#scan-debug-backend-normalized');
    const debugParser = document.querySelector<HTMLElement>('#scan-debug-parser');
    const debugHex = document.querySelector<HTMLElement>('#scan-debug-hex');
    const resultModal = document.querySelector<HTMLElement>('#scan-result-modal');
    const resultDialog = document.querySelector<HTMLElement>('#scan-result-dialog');
    const resultBadge = document.querySelector<HTMLElement>('#scan-result-badge');
    const resultTitle = document.querySelector<HTMLElement>('#scan-result-title');
    const resultTicket = document.querySelector<HTMLElement>('#scan-result-ticket');
    const resultNote = document.querySelector<HTMLElement>('#scan-result-note');
    const resultName = document.querySelector<HTMLElement>('#scan-result-name');
    const resultIdentityLabel = document.querySelector<HTMLElement>('#scan-result-identity-label');
    const resultIdentityNumber = document.querySelector<HTMLElement>('#scan-result-identity-number');
    const resultPhone = document.querySelector<HTMLElement>('#scan-result-phone');
    const resultClose = document.querySelector<HTMLButtonElement>('#scan-result-close');
    const pauseButton = document.querySelector<HTMLButtonElement>('#pause-scanner');
    const resumeButton = document.querySelector<HTMLButtonElement>('#resume-scanner');
    const switchButton = document.querySelector<HTMLButtonElement>('#switch-camera');
    const manualForm = document.querySelector<HTMLFormElement>('#manual-scan-form');
    const manualInput = document.querySelector<HTMLTextAreaElement>('#manual-qr-payload');

    const html5Qr = new Html5Qrcode(qrReaderId, {
        formatsToSupport: SUPPORTED_SCAN_FORMATS,
        useBarCodeDetectorIfSupported: true,
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true,
        },
        verbose: false,
    });

    let cameras = await safeGetCameras();
    let currentCameraIndex = 0;
    let activeCameraLabel = '';
    let lastPayload = '';
    let lastScanAt = 0;
    let busy = false;
    let scanningStarted = false;
    let scannerPausedForResult = false;

    const syncConnectionState = () => {
        if (navigator.onLine) {
            setBadge(connectionBadge, 'Online', 'success');
            return;
        }

        setBadge(connectionBadge, 'Offline', 'danger');
        note.textContent = 'Internet connection lost. Reconnect before scanning the next ticket.';
    };

    const refreshStats = async () => {
        try {
            const response = await fetch(statsUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                return;
            }

            const stats = (await response.json()) as StatsResponse;
            setText('#stat-total', stats.total_scans);
            setText('#stat-valid', stats.valid_scans);
            setText('#stat-duplicate', stats.duplicate_scans);
            setText('#stat-invalid', (stats.invalid_scans ?? 0) + (stats.expired_scans ?? 0));
        } catch {
            // Keep the last rendered value if polling fails.
        }
    };

    const renderInitialResultState = () => {
        headline.textContent = 'Waiting for scan';
        note.textContent = INITIAL_RESULT_NOTE;
        ticketCode.textContent = '-';
        userId.textContent = '-';
        scannedAt.textContent = '-';
        reason.textContent = '-';
        renderDebugPanel(null, null);
    };

    const renderScannerStatus = (label: string, theme: 'success' | 'warning' | 'danger') => {
        setBadge(statusBadge, label, theme);
    };

    const renderIdleState = () => {
        renderScannerStatus('Ready', 'warning');
    };

    const renderLiveState = () => {
        renderScannerStatus('Live', 'success');
    };

    const renderPausedState = () => {
        renderScannerStatus('Paused', 'warning');
    };

    const renderDebugPanel = (
        frontendDebug: { raw: string; normalized: string } | null,
        backendDebug: ScanDebug | null | undefined,
    ) => {
        if (!debugEnabled || !debugPanel) {
            return;
        }

        const hasFrontendDebug = frontendDebug !== null;
        const hasBackendDebug = backendDebug !== null && backendDebug !== undefined;

        if (!hasFrontendDebug && !hasBackendDebug) {
            debugPanel.classList.add('hidden');
            setElementText(debugFrontendRaw, '-');
            setElementText(debugFrontendNormalized, '-');
            setElementText(debugBackendRaw, '-');
            setElementText(debugBackendNormalized, '-');
            setElementText(debugParser, '-');
            setElementText(debugHex, '-');
            return;
        }

        debugPanel.classList.remove('hidden');
        setElementText(debugFrontendRaw, frontendDebug?.raw ?? '-');
        setElementText(debugFrontendNormalized, frontendDebug?.normalized ?? '-');
        setElementText(debugBackendRaw, backendDebug?.received_payload ?? '-');
        setElementText(debugBackendNormalized, backendDebug?.normalized_payload ?? '-');
        setElementText(
            debugParser,
            formatParserTrace(backendDebug),
        );
        setElementText(debugHex, backendDebug?.normalized_payload_hex ?? backendDebug?.received_payload_hex ?? '-');
    };

    const renderResult = (result: ScanResponse, frontendDebug: { raw: string; normalized: string } | null = null) => {
        headline.textContent = headlineForStatus(result.status);
        note.textContent = noteForReason(result.reason);
        ticketCode.textContent = result.ticket_code ?? '-';
        userId.textContent = result.user_id ?? '-';
        scannedAt.textContent = result.scanned_at ?? '-';
        reason.textContent = reasonLabel(result.reason);
        renderDebugPanel(frontendDebug, result.debug);
        showResultPopup(result);
        pauseScannerForResult();
        playFeedback(result.status);
    };

    const pauseScannerForResult = () => {
        if (!scanningStarted || scannerPausedForResult) {
            return;
        }

        html5Qr.pause(true);
        scannerPausedForResult = true;
        renderPausedState();
    };

    const resumeScannerAfterResult = () => {
        if (!scanningStarted || !scannerPausedForResult) {
            return;
        }

        html5Qr.resume();
        scannerPausedForResult = false;
        lastPayload = '';
        lastScanAt = 0;
        renderLiveState();
    };

    const hideResultPopup = () => {
        if (!resultModal) {
            return;
        }

        resultModal.classList.remove('is-open');
    };

    const showResultPopup = (result: ScanResponse) => {
        if (!resultModal || !resultDialog || !resultBadge || !resultTitle || !resultTicket || !resultNote) {
            return;
        }

        hideResultPopup();
        resultDialog.dataset.status = result.status;
        setBadge(resultBadge, humanizeStatus(result.status), themeForStatus(result.status));
        resultTitle.textContent = headlineForStatus(result.status);
        resultTicket.textContent = result.ticket_code ? `Ticket ${result.ticket_code}` : 'Ticket code unavailable';
        resultNote.textContent = noteForReason(result.reason);
        setElementText(resultName, result.participant_name ?? 'Participant details unavailable');
        setElementText(resultIdentityLabel, result.participant_identity_label ?? 'Passport / ID');
        setElementText(resultIdentityNumber, result.participant_identity_number ?? '-');
        setElementText(resultPhone, result.participant_phone_number ?? '-');
        resultModal.classList.add('is-open');
    };

    const submitPayload = async (payload: string) => {
        const frontendDebug = {
            raw: payload,
            normalized: normalizeQrPayload(payload),
        };
        const normalizedPayload = frontendDebug.normalized;

        if (normalizedPayload === '' || busy || !navigator.onLine) {
            return;
        }

        const now = Date.now();
        if (normalizedPayload === lastPayload && now - lastScanAt < SCAN_COOLDOWN_MS) {
            return;
        }

        busy = true;
        hideResultPopup();
        lastPayload = normalizedPayload;
        lastScanAt = now;
        renderScannerStatus('Checking', 'warning');
        headline.textContent = 'Checking QR...';
        note.textContent = 'Please wait while the ticket is being verified.';

        try {
            const response = await fetch(scanUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ qr_payload: normalizedPayload }),
            });

            const result = (await response.json()) as ScanResponse;
            renderResult(result, frontendDebug);
            await refreshStats();
        } catch {
            renderResult({
                status: 'invalid',
                reason: 'connection_failed',
            }, frontendDebug);
        } finally {
            busy = false;
        }
    };

    const startScanner = async (cameraId: string) => {
        if (scanningStarted) {
            await html5Qr.stop();
            scanningStarted = false;
        }

        scannerPausedForResult = false;

        activeCameraLabel = cameras.find((camera) => camera.id === cameraId)?.label ?? 'active camera';

        await html5Qr.start(
            cameraId,
            {
                fps: 12,
                qrbox: (viewfinderWidth, viewfinderHeight) => responsiveQrBox(viewfinderWidth, viewfinderHeight),
            },
            async (decodedText) => {
                await submitPayload(decodedText);
            },
            () => {
                // Ignore per-frame decode errors.
            },
        );

        scanningStarted = true;
        renderLiveState();
    };

    renderIdleState();
    renderInitialResultState();

    const initialCamera = pickBestCamera(cameras);
    if (initialCamera) {
        currentCameraIndex = Math.max(0, cameras.findIndex((camera) => camera.id === initialCamera.id));

        try {
            await startScanner(initialCamera.id);
        } catch {
            note.textContent = 'The camera could not be started. Use manual input below.';
        }
    } else {
        note.textContent = 'No camera was detected. Use manual input below.';
    }

    pauseButton?.addEventListener('click', () => {
        if (!scanningStarted) {
            return;
        }

        html5Qr.pause(true);
        scannerPausedForResult = false;
        renderPausedState();
    });

    resumeButton?.addEventListener('click', () => {
        if (!scanningStarted) {
            return;
        }

        html5Qr.resume();
        scannerPausedForResult = false;
        lastPayload = '';
        lastScanAt = 0;
        renderLiveState();
    });

    switchButton?.addEventListener('click', async () => {
        if (cameras.length < 2) {
            note.textContent = 'Only one camera is available on this device.';
            return;
        }

        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
        await startScanner(cameras[currentCameraIndex].id);
    });

    manualForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitPayload(manualInput?.value ?? '');
    });

    resultClose?.addEventListener('click', () => {
        hideResultPopup();
        resumeScannerAfterResult();
    });

    window.addEventListener('online', syncConnectionState);
    window.addEventListener('offline', syncConnectionState);
    syncConnectionState();
    await refreshStats();
    window.setInterval(() => void refreshStats(), 15000);
}

async function bootStatsPage(root: HTMLElement): Promise<void> {
    const statsUrl = root.dataset.statsUrl;

    if (!statsUrl) {
        return;
    }

    const renderStats = (stats: StatsResponse) => {
        setText('#stats-total', stats.total_scans);
        setText('#stats-valid', stats.valid_scans);
        setText('#stats-duplicate', stats.duplicate_scans);
        setText('#stats-invalid', (stats.invalid_scans ?? 0) + (stats.expired_scans ?? 0));
        setText('#stats-last-result', stats.last_result ?? 'No scans yet');
        setText('#stats-last-time', stats.last_scanned_at ?? '-');
    };

    const refresh = async () => {
        try {
            const response = await fetch(statsUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                return;
            }

            renderStats((await response.json()) as StatsResponse);
        } catch {
            // Keep the existing data if polling fails.
        }
    };

    await refresh();
    window.setInterval(() => void refresh(), 15000);
}

function mustQuery(selector: string): HTMLElement {
    const element = document.querySelector<HTMLElement>(selector);

    if (!element) {
        throw new Error(`Missing required element: ${selector}`);
    }

    return element;
}

function setText(selector: string, value: string | number): void {
    const element = document.querySelector<HTMLElement>(selector);

    if (element) {
        element.textContent = String(value);
    }
}

function setElementText(element: HTMLElement | null, value: string | number): void {
    if (element) {
        element.textContent = String(value);
    }
}

function setBadge(element: HTMLElement, label: string, theme: 'success' | 'warning' | 'danger'): void {
    element.textContent = label;
    element.className = `staff-badge-${theme}`;
}

function themeForStatus(status: ScanResponse['status']): 'success' | 'warning' | 'danger' {
    if (status === 'valid') {
        return 'success';
    }

    if (status === 'duplicate') {
        return 'warning';
    }

    return 'danger';
}

function headlineForStatus(status: ScanResponse['status']): string {
    return {
        valid: 'Scan accepted',
        duplicate: 'Ticket already scanned today',
        invalid: 'QR needs attention',
        expired: 'QR no longer valid',
    }[status];
}

function humanizeStatus(status: ScanResponse['status']): string {
    return {
        valid: 'Accepted',
        duplicate: 'Already Scanned',
        invalid: 'Needs Review',
        expired: 'Expired',
    }[status];
}

function noteForReason(reason: string): string {
    const map: Record<string, string> = {
        first_scan_today: 'Attendance recorded successfully for today.',
        already_scanned_today: 'This participant has already been recorded today. No second scan is needed.',
        invalid_format: 'The QR code is not recognized. Ask the participant to open the latest QR.',
        legacy_esf1_detected: 'This is an older QR version. Ask the participant to open the latest QR or contact admin.',
        user_not_found: 'The participant record was not found. Make sure this QR belongs to this event.',
        ticket_not_found: 'The participant ticket was not found in the system. Contact admin.',
        token_mismatch: 'This QR does not match the participant\'s active ticket. Ask the participant to open the latest QR.',
        token_rotated: 'This old QR has been replaced. Ask the participant to open the latest QR.',
        outside_event_window: 'This QR is not yet valid or has already expired.',
        ticket_inactive: 'This ticket is not active yet. Contact admin if needed.',
        connection_failed: 'Cannot reach the scanner server. Check the internet connection, then scan again.',
    };

    return map[reason] ?? 'This scan result needs a manual check. Contact admin if the problem continues.';
}

function reasonLabel(reason: string): string {
    const map: Record<string, string> = {
        first_scan_today: 'Valid ticket',
        already_scanned_today: 'Already scanned today',
        invalid_format: 'QR not recognized',
        legacy_esf1_detected: 'Old QR version',
        user_not_found: 'Participant not found',
        ticket_not_found: 'Ticket not found',
        token_mismatch: 'QR does not match',
        token_rotated: 'Old QR replaced',
        outside_event_window: 'Outside valid time',
        ticket_inactive: 'Ticket inactive',
        connection_failed: 'Scanner offline',
    };

    return map[reason] ?? 'Needs review';
}

function formatParserTrace(debug: ScanDebug | null | undefined): string {
    if (!debug) {
        return '-';
    }

    const parts = [
        debug.parser_status ? `status=${debug.parser_status}` : null,
        debug.parser_reason ? `reason=${debug.parser_reason}` : null,
        typeof debug.colon_count === 'number' ? `colons=${debug.colon_count}` : null,
        debug.prefix_guess ? `prefix=${debug.prefix_guess}` : null,
    ].filter((value): value is string => value !== null);

    return parts.length > 0 ? parts.join(' | ') : '-';
}

function pickBestCamera(cameras: Array<{ id: string; label: string }>): { id: string; label: string } | undefined {
    const preferred = cameras.find((camera) => /back|rear|environment/i.test(camera.label));

    return preferred ?? cameras[0];
}

function liveScanHint(cameraLabel?: string): string {
    if (!cameraLabel) {
        return DEFAULT_SCAN_HINT;
    }

    const normalizedLabel = cameraLabel.trim();

    if (normalizedLabel === '') {
        return DEFAULT_SCAN_HINT;
    }

    return `Active camera: ${normalizedLabel}. ${DEFAULT_SCAN_HINT}`;
}

function responsiveQrBox(viewfinderWidth: number, viewfinderHeight: number): { width: number; height: number } {
    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
    const preferredEdge = Math.floor(minEdge * 0.82);
    const maxAllowedEdge = Math.max(140, Math.floor(minEdge - 24));
    const edge = Math.max(140, Math.min(preferredEdge, maxAllowedEdge, 420));

    return {
        width: edge,
        height: edge,
    };
}


function normalizeQrPayload(payload: string): string {
    return payload
        .replace(/\uFF1A/g, ':')
        .replace(/[\u200B-\u200D\u2060\uFEFF]/g, '')
        .replace(/[\u0000-\u001F\u007F]/g, '')
        .trim();
}

async function safeGetCameras(): Promise<Array<{ id: string; label: string }>> {
    try {
        return await Html5Qrcode.getCameras();
    } catch {
        return [];
    }
}

function playFeedback(status: ScanResponse['status']): void {
    if (typeof navigator.vibrate === 'function') {
        navigator.vibrate(status === 'valid' ? [80] : [120, 40, 120]);
    }

    const AudioContextCtor = window.AudioContext || (window as typeof window & { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;

    if (!AudioContextCtor) {
        return;
    }

    const context = new AudioContextCtor();
    const oscillator = context.createOscillator();
    const gain = context.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.value = status === 'valid' ? 880 : 320;
    gain.gain.value = 0.04;

    oscillator.connect(gain);
    gain.connect(context.destination);
    oscillator.start();
    oscillator.stop(context.currentTime + 0.16);

    oscillator.onended = () => {
        void context.close();
    };
}
