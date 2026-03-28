@extends('staff.layouts.app', ['title' => 'Live Scanner', 'subtitle' => 'Scan QR code live, validate online, and record attendance in real time.', 'station' => $station])

@section('content')
    <div
        data-scanner-app
        data-scan-url="{{ url('/api/scanner/scan') }}"
        data-stats-url="{{ url('/api/scanner/stats/today') }}"
        data-me-url="{{ url('/api/staff/me') }}"
        data-debug-enabled="{{ config('app.debug') ? '1' : '0' }}"
        class="grid gap-4"
    >
        <div id="scan-result-modal" class="staff-scan-modal" aria-live="polite" aria-atomic="true">
            <div id="scan-result-dialog" class="staff-scan-alert" data-status="valid" role="alertdialog" aria-modal="true" aria-labelledby="scan-result-title">
                <div>
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Scan Result</p>
                        <h2 id="scan-result-title" class="mt-1 text-xl font-semibold text-staff-ink">Waiting for scan</h2>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <div id="scan-result-badge" class="staff-badge-success">Accepted</div>
                    <p id="scan-result-ticket" class="text-xs font-medium text-staff-soft">Ticket code unavailable</p>
                </div>

                <p id="scan-result-note" class="mt-4 text-sm leading-6 text-staff-soft">Point the camera at the participant QR code to begin ticket validation.</p>

                <dl class="mt-5 grid gap-3 text-sm">
                    <div class="staff-scan-detail">
                        <dt class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Participant Name</dt>
                        <dd id="scan-result-name" class="mt-2 text-base font-semibold text-staff-ink">-</dd>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="staff-scan-detail">
                            <dt id="scan-result-identity-label" class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Passport / ID</dt>
                            <dd id="scan-result-identity-number" class="mt-2 text-sm font-semibold text-staff-ink">-</dd>
                        </div>

                        <div class="staff-scan-detail">
                            <dt class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Phone Number</dt>
                            <dd id="scan-result-phone" class="mt-2 text-sm font-semibold text-staff-ink">-</dd>
                        </div>
                    </div>
                </dl>

                <button type="button" id="scan-result-close" class="staff-button-primary mt-5 w-full">Close</button>
            </div>
        </div>

        <section class="staff-card overflow-hidden px-4 py-4">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Camera Scanner</p>
                    <h2 class="mt-1 text-xl font-semibold text-staff-ink">Ready for Live Scan</h2>
                </div>
                <div id="connection-badge" class="staff-badge-success">Online</div>
            </div>

            <div class="rounded-[28px] border border-staff-line bg-stone-950 p-3 shadow-inner">
                <div id="qr-reader" class="aspect-[3/4] w-full overflow-hidden rounded-[22px] bg-black"></div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Scanner Status</p>
                <div id="scan-status-badge" class="staff-badge-warning">Ready</div>
            </div>

            <div class="mt-4 grid gap-3">
                <button type="button" id="pause-scanner" class="staff-button-secondary">Pause Camera</button>
                <button type="button" id="resume-scanner" class="staff-button-secondary">Resume Camera</button>
                <button type="button" id="switch-camera" class="staff-button-secondary">Switch Camera</button>
            </div>
        </section>

        <section class="space-y-4">
            <div class="staff-card px-5 py-5">
                <div>
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Last Result</p>
                        <h2 class="mt-1 text-xl font-semibold text-staff-ink">Validation Status</h2>
                    </div>
                </div>

                <div id="scan-status-panel" class="mt-4 rounded-3xl border border-staff-line bg-white px-4 py-4">
                    <p id="scan-status-headline" class="text-lg font-semibold text-staff-ink">Waiting for scan</p>
                    <p id="scan-status-note" class="mt-2 text-sm text-staff-soft">Point the camera at the participant QR code to begin ticket validation.</p>

                    <dl class="mt-4 grid gap-3 text-sm">
                        <div>
                            <dt class="text-staff-soft">Ticket Code</dt>
                            <dd id="scan-ticket-code" class="mt-1 font-semibold text-staff-ink">-</dd>
                        </div>
                        <div>
                            <dt class="text-staff-soft">Participant ID</dt>
                            <dd id="scan-user-id" class="mt-1 font-semibold text-staff-ink">-</dd>
                        </div>
                        <div>
                            <dt class="text-staff-soft">Scanned At</dt>
                            <dd id="scan-scanned-at" class="mt-1 font-semibold text-staff-ink">-</dd>
                        </div>
                        <div>
                            <dt class="text-staff-soft">Details</dt>
                            <dd id="scan-reason" class="mt-1 font-semibold text-staff-ink">-</dd>
                        </div>
                    </dl>

                    <div id="scan-debug-panel" class="mt-4 hidden rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700">Technical Details</p>

                        <dl class="mt-3 grid gap-3 text-sm">
                            <div>
                                <dt class="text-staff-soft">Frontend Raw</dt>
                                <dd id="scan-debug-frontend-raw" class="mt-1 whitespace-pre-wrap break-all font-mono text-xs text-staff-ink">-</dd>
                            </div>
                            <div>
                                <dt class="text-staff-soft">Frontend Normalized</dt>
                                <dd id="scan-debug-frontend-normalized" class="mt-1 whitespace-pre-wrap break-all font-mono text-xs text-staff-ink">-</dd>
                            </div>
                            <div>
                                <dt class="text-staff-soft">Backend Received</dt>
                                <dd id="scan-debug-backend-raw" class="mt-1 whitespace-pre-wrap break-all font-mono text-xs text-staff-ink">-</dd>
                            </div>
                            <div>
                                <dt class="text-staff-soft">Backend Normalized</dt>
                                <dd id="scan-debug-backend-normalized" class="mt-1 whitespace-pre-wrap break-all font-mono text-xs text-staff-ink">-</dd>
                            </div>
                            <div>
                                <dt class="text-staff-soft">Parser Trace</dt>
                                <dd id="scan-debug-parser" class="mt-1 whitespace-pre-wrap break-all font-mono text-xs text-staff-ink">-</dd>
                            </div>
                            <div>
                                <dt class="text-staff-soft">Backend Hex</dt>
                                <dd id="scan-debug-hex" class="mt-1 whitespace-pre-wrap break-all font-mono text-xs text-staff-ink">-</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="staff-card px-5 py-5">
                <div class="mb-4">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Today</p>
                    <h2 class="mt-1 text-xl font-semibold text-staff-ink">Station Stats</h2>
                </div>

                <div id="stats-grid" class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-staff-line bg-white px-4 py-4">
                        <p class="text-xs text-staff-soft">Total</p>
                        <p id="stat-total" class="mt-1 text-2xl font-semibold text-staff-ink">{{ $stats['total_scans'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-staff-line bg-white px-4 py-4">
                        <p class="text-xs text-staff-soft">Valid</p>
                        <p id="stat-valid" class="mt-1 text-2xl font-semibold text-staff-success">{{ $stats['valid_scans'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-staff-line bg-white px-4 py-4">
                        <p class="text-xs text-staff-soft">Duplicate</p>
                        <p id="stat-duplicate" class="mt-1 text-2xl font-semibold text-staff-warning">{{ $stats['duplicate_scans'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-staff-line bg-white px-4 py-4">
                        <p class="text-xs text-staff-soft">Invalid/Expired</p>
                        <p id="stat-invalid" class="mt-1 text-2xl font-semibold text-staff-danger">{{ ($stats['invalid_scans'] ?? 0) + ($stats['expired_scans'] ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="staff-card px-5 py-5">
                <div class="mb-4">
                    <p class="text-[11px] uppercase tracking-[0.18em] text-staff-soft">Fallback</p>
                    <h2 class="mt-1 text-xl font-semibold text-staff-ink">Manual QR Payload</h2>
                </div>

                <form id="manual-scan-form" class="space-y-3">
                    <textarea id="manual-qr-payload" class="staff-input min-h-28 resize-y py-3" placeholder="Paste payload like esf2:user_id:qr_token"></textarea>
                    <button type="submit" class="staff-button-primary w-full">Validate Payload Manually</button>
                </form>
            </div>
        </section>
    </div>
@endsection
