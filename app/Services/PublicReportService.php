<?php

namespace App\Services;

use App\Models\PublicReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicReportService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload, ?string $ipAddress = null, ?string $userAgent = null): PublicReport
    {
        $this->ensureStorageReady();

        return DB::transaction(function () use ($ipAddress, $payload, $userAgent): PublicReport {
            $reportType = (string) $payload['report_type'];
            $prefix = $this->casePrefix($reportType);
            $nextSequence = (int) PublicReport::query()
                ->where('report_type', $reportType)
                ->lockForUpdate()
                ->max('case_sequence') + 1;

            return PublicReport::query()->create([
                'case_id' => sprintf('%s%03d', $prefix, $nextSequence),
                'report_type' => $reportType,
                'case_prefix' => $prefix,
                'case_sequence' => $nextSequence,
                'name' => (string) $payload['name'],
                'email' => $this->nullableString($payload['email'] ?? null),
                'phone' => (string) $payload['phone'],
                'identity_type' => $this->nullableString($payload['identity_type'] ?? null),
                'identity_number' => $this->nullableString($payload['identity_number'] ?? null),
                'incident_date' => (string) $payload['incident_date'],
                'incident_time' => (string) $payload['incident_time'],
                'chronology' => (string) $payload['chronology'],
                'action_status' => PublicReport::ACTION_STATUS_PENDING,
                'admin_note' => null,
                'staff_name' => $this->nullableString($payload['staff_name'] ?? null),
                'ip_address' => $this->nullableString($ipAddress),
                'user_agent' => $this->nullableString($userAgent),
                'reported_at' => now(),
            ]);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateAdminReview(PublicReport $report, array $payload): PublicReport
    {
        $report->fill([
            'action_status' => (string) ($payload['action_status'] ?? PublicReport::ACTION_STATUS_PENDING),
            'admin_note' => $this->nullableString($payload['admin_note'] ?? null),
        ]);

        $report->save();

        return $report->refresh();
    }

    /**
     * @return array<string, string>
     */
    public function deleteForAdmin(PublicReport $report): array
    {
        $snapshot = [
            'id' => (string) $report->getKey(),
            'case_id' => (string) $report->case_id,
            'report_type' => (string) $report->report_type,
            'action_status' => (string) ($report->action_status ?? PublicReport::ACTION_STATUS_PENDING),
            'name' => (string) $report->name,
            'email' => (string) ($report->email ?? ''),
            'phone' => (string) $report->phone,
        ];

        $report->delete();

        return $snapshot;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        $this->ensureStorageReady();
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 10), 100);

        return $this->baseQuery($filters)
            ->orderByDesc('reported_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function summary(array $filters = []): array
    {
        $this->ensureStorageReady();
        $query = $this->baseQuery($filters);
        $rows = (clone $query)->get(['report_type']);

        return [
            'total' => $rows->count(),
            'incident_security' => $rows->where('report_type', 'incident_security')->count(),
            'lost_item' => $rows->where('report_type', 'lost_item')->count(),
            'lost_locker_card' => $rows->where('report_type', 'lost_locker_card')->count(),
            'medical_attention' => $rows->where('report_type', 'medical_attention')->count(),
            'ticket_registration' => $rows->where('report_type', 'ticket_registration')->count(),
            'others' => $rows->where('report_type', 'others')->count(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, string>>
     */
    public function exportRows(array $filters = []): array
    {
        $this->ensureStorageReady();
        return $this->baseQuery($filters)
            ->orderByDesc('reported_at')
            ->get()
            ->map(fn (PublicReport $report): array => [
                'case_id' => (string) $report->case_id,
                'report_type' => $this->reportTypeLabel((string) $report->report_type),
                'action_status' => $this->actionStatusLabel((string) $report->action_status),
                'name' => (string) $report->name,
                'email' => (string) ($report->email ?? ''),
                'phone' => (string) $report->phone,
                'document_type' => $this->identityTypeLabel((string) ($report->identity_type ?? '')),
                'document_number' => (string) ($report->identity_number ?? ''),
                'incident_date' => (string) optional($report->incident_date)->format('Y-m-d'),
                'incident_time' => (string) $report->incident_time,
                'reported_date' => (string) optional($report->reported_at)->format('Y-m-d'),
                'reported_time' => (string) optional($report->reported_at)->format('H:i:s'),
                'staff_name' => (string) ($report->staff_name ?? ''),
                'ip_address' => (string) ($report->ip_address ?? ''),
                'admin_note' => (string) ($report->admin_note ?? ''),
                'chronology' => (string) $report->chronology,
            ])
            ->values()
            ->all();
    }

    public function reportTypeLabel(string $value): string
    {
        return match ($value) {
            'incident_security' => 'Incident / Security',
            'lost_item' => 'Lost Item',
            'lost_locker_card' => 'Lost Locker Card',
            'medical_attention' => 'Medical Attention',
            'ticket_registration' => 'Ticket and Registration',
            default => 'Others',
        };
    }

    public function identityTypeLabel(string $value): string
    {
        return match ($value) {
            'national_id' => 'Malaysia IC (MyKad)',
            'passport' => 'Passport',
            default => '-',
        };
    }

    public function actionStatusLabel(string $value): string
    {
        return match ($value) {
            PublicReport::ACTION_STATUS_IN_PROGRESS => 'In Progress',
            PublicReport::ACTION_STATUS_RESOLVED => 'Resolved',
            default => 'No Action Yet',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function reportTypeOptions(): array
    {
        return [
            ['value' => 'incident_security', 'label' => 'Incident / Security'],
            ['value' => 'lost_item', 'label' => 'Lost Item'],
            ['value' => 'lost_locker_card', 'label' => 'Lost Locker Card'],
            ['value' => 'medical_attention', 'label' => 'Medical Attention'],
            ['value' => 'ticket_registration', 'label' => 'Ticket and Registration'],
            ['value' => 'others', 'label' => 'Others'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function actionStatusOptions(): array
    {
        return [
            ['value' => PublicReport::ACTION_STATUS_PENDING, 'label' => 'No Action Yet'],
            ['value' => PublicReport::ACTION_STATUS_IN_PROGRESS, 'label' => 'In Progress'],
            ['value' => PublicReport::ACTION_STATUS_RESOLVED, 'label' => 'Resolved'],
        ];
    }

    /**
     * Mirrors the public report form in resources/js/src/components/auth/ReportPage.tsx.
     *
     * @return array<string, mixed>
     */
    public function formReference(): array
    {
        return [
            'title' => 'Public Report',
            'description' => 'Submit incident, lost item, medical, or other assistance requests directly from this page.',
            'note' => 'Every submitted report is stored in the admin dashboard for review.',
            'required_fields' => [
                'Type of Report',
                'Name',
                'Phone',
                'Document Type',
                'Document Number',
                'E-mail',
                'Incident Date',
                'Incident Time',
                'Report / Chronology',
            ],
            'optional_fields' => [
                'Staff Name',
            ],
            'security' => 'Background Google reCAPTCHA verification before submission.',
            'disclaimer' => 'We are not obligated to respond to or act upon every report submitted. Our response and any subsequent action are subject to the urgency and nature of the matter.',
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $query = PublicReport::query()->search((string) ($filters['q'] ?? ''));
        $reportType = trim((string) ($filters['report_type'] ?? ''));
        $actionStatus = trim((string) ($filters['action_status'] ?? ''));
        $from = $this->normalizeFilterDate($filters['from'] ?? null);
        $to = $this->normalizeFilterDate($filters['to'] ?? null);

        if ($reportType !== '') {
            $query->where('report_type', $reportType);
        }

        if ($actionStatus !== '') {
            $query->where('action_status', $actionStatus);
        }

        if ($from !== null) {
            $query->whereDate('reported_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('reported_at', '<=', $to);
        }

        return $query;
    }

    private function normalizeFilterDate(mixed $value): ?string
    {
        $date = trim((string) $value);

        if ($date === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $parsed = \Carbon\CarbonImmutable::createFromFormat($format, $date);

                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function casePrefix(string $reportType): string
    {
        return match ($reportType) {
            'incident_security' => 'IS',
            'lost_item' => 'LI',
            'lost_locker_card' => 'LC',
            'medical_attention' => 'M',
            'ticket_registration' => 'TR',
            default => 'O',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function ensureStorageReady(): void
    {
        $defaultConnection = (string) config('database.default', '');

        if ($defaultConnection === 'sqlite') {
            $databasePath = (string) config('database.connections.sqlite.database', '');

            if ($databasePath !== '' && $databasePath !== ':memory:' && ! file_exists($databasePath)) {
                $directory = dirname($databasePath);

                if (! is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }

                touch($databasePath);
            }
        }

        if (! Schema::hasTable('public_reports')) {
            Schema::create('public_reports', function (Blueprint $table) {
                $table->id();
                $table->string('case_id')->unique();
                $table->string('report_type', 50);
                $table->string('case_prefix', 10);
                $table->unsignedInteger('case_sequence');
                $table->string('name', 120);
                $table->string('email', 120)->nullable();
                $table->string('phone', 30);
                $table->string('identity_type', 20)->nullable();
                $table->string('identity_number', 80)->nullable();
                $table->date('incident_date');
                $table->time('incident_time');
                $table->text('chronology');
                $table->string('action_status', 30)->default(PublicReport::ACTION_STATUS_PENDING);
                $table->text('admin_note')->nullable();
                $table->string('staff_name', 120)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('reported_at');
                $table->timestamps();

                $table->index('action_status');
                $table->index(['report_type', 'case_sequence']);
                $table->index(['incident_date', 'incident_time']);
                $table->index('reported_at');
            });

            return;
        }

        if (! Schema::hasColumn('public_reports', 'identity_type')) {
            Schema::table('public_reports', function (Blueprint $table) {
                $table->string('identity_type', 20)->nullable();
            });
        }

        if (! Schema::hasColumn('public_reports', 'action_status')) {
            Schema::table('public_reports', function (Blueprint $table) {
                $table->string('action_status', 30)->default(PublicReport::ACTION_STATUS_PENDING);
            });
        }

        if (! Schema::hasColumn('public_reports', 'admin_note')) {
            Schema::table('public_reports', function (Blueprint $table) {
                $table->text('admin_note')->nullable();
            });
        }
    }
}
