<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPanelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __invoke(Request $request, AdminPanelService $adminPanel): View
    {
        $filters = $request->only(['q', 'from', 'to', 'page', 'per_page']);
        $attendance = $adminPanel->attendanceData($filters);
        $history = $attendance['history'];
        $dailyAttendance = $attendance['daily_attendance'] ?? [];
        $scannerActivity = $attendance['scanner_activity'] ?? [];
        $hasFilters = filled($filters['q'] ?? null) || filled($filters['from'] ?? null) || filled($filters['to'] ?? null);
        $latestLog = collect(method_exists($history, 'items') ? $history->items() : [])->first();
        $successfulAttendance = collect($dailyAttendance)->sum(fn (array $day): int => (int) ($day['successful_attendance'] ?? 0));
        $duplicateScans = collect($dailyAttendance)->sum(fn (array $day): int => (int) ($day['duplicate_scans'] ?? 0));
        $invalidScans = collect($dailyAttendance)->sum(fn (array $day): int => (int) ($day['invalid_scans'] ?? 0));
        $totalScans = method_exists($history, 'total')
            ? (int) $history->total()
            : collect(method_exists($history, 'items') ? $history->items() : [])->count();
        $activeScannerCount = count($scannerActivity);

        $formatDateTime = static function (mixed $value): string {
            if (blank($value)) {
                return '-';
            }

            try {
                return \Carbon\CarbonImmutable::parse((string) $value)
                    ->setTimezone(config('app.timezone'))
                    ->format('d M Y, h:i A');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDate = static function (mixed $value): string {
            if (blank($value)) {
                return '-';
            }

            try {
                return \Carbon\CarbonImmutable::parse((string) $value)
                    ->setTimezone(config('app.timezone'))
                    ->format('d M Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $statusMeta = static function (mixed $value): array {
            return match (strtolower(trim((string) $value))) {
                'success' => [
                    'label' => 'Checked In',
                    'class' => 'bg-label-success',
                    'note' => 'The QR is valid and attendance was recorded successfully.',
                ],
                'duplicate' => [
                    'label' => 'Already Scanned',
                    'class' => 'bg-label-warning',
                    'note' => 'This ticket was already used, so it is not counted twice.',
                ],
                default => [
                    'label' => 'Needs Review',
                    'class' => 'bg-label-danger',
                    'note' => 'The scan failed or the QR needs a manual recheck.',
                ],
            };
        };

        $periodSummary = match (true) {
            filled($filters['from'] ?? null) && filled($filters['to'] ?? null) => $formatDate($filters['from']).' - '.$formatDate($filters['to']),
            filled($filters['from'] ?? null) => 'Since '.$formatDate($filters['from']),
            filled($filters['to'] ?? null) => 'Until '.$formatDate($filters['to']),
            default => 'All dates',
        };

        return view('admin.attendance.index', [
            'attendance' => $attendance,
            'filters' => $filters,
            'firestoreAvailable' => $adminPanel->firestoreAvailable(),
            'history' => $history,
            'dailyAttendance' => $dailyAttendance,
            'scannerActivity' => $scannerActivity,
            'hasFilters' => $hasFilters,
            'latestLog' => $latestLog,
            'successfulAttendance' => $successfulAttendance,
            'duplicateScans' => $duplicateScans,
            'invalidScans' => $invalidScans,
            'totalScans' => $totalScans,
            'activeScannerCount' => $activeScannerCount,
            'formatDateTime' => $formatDateTime,
            'formatDate' => $formatDate,
            'statusMeta' => $statusMeta,
            'periodSummary' => $periodSummary,
        ]);
    }
}
