<?php

namespace App\Services;

use App\Models\PublicReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
                'identity_number' => $this->nullableString($payload['identity_number'] ?? null),
                'incident_date' => (string) $payload['incident_date'],
                'incident_time' => (string) $payload['incident_time'],
                'chronology' => (string) $payload['chronology'],
                'staff_name' => $this->nullableString($payload['staff_name'] ?? null),
                'ip_address' => $this->nullableString($ipAddress),
                'user_agent' => $this->nullableString($userAgent),
                'reported_at' => now(),
            ]);
        });
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
                'name' => (string) $report->name,
                'email' => (string) ($report->email ?? ''),
                'phone' => (string) $report->phone,
                'ic_or_id' => (string) ($report->identity_number ?? ''),
                'incident_date' => (string) optional($report->incident_date)->format('Y-m-d'),
                'incident_time' => (string) $report->incident_time,
                'reported_date' => (string) optional($report->reported_at)->format('Y-m-d'),
                'reported_time' => (string) optional($report->reported_at)->format('H:i:s'),
                'staff_name' => (string) ($report->staff_name ?? ''),
                'ip_address' => (string) ($report->ip_address ?? ''),
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
            default => 'Others',
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
            ['value' => 'others', 'label' => 'Others'],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $query = PublicReport::query()->search((string) ($filters['q'] ?? ''));
        $reportType = trim((string) ($filters['report_type'] ?? ''));
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));

        if ($reportType !== '') {
            $query->where('report_type', $reportType);
        }

        if ($from !== '') {
            $query->whereDate('reported_at', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('reported_at', '<=', $to);
        }

        return $query;
    }

    private function casePrefix(string $reportType): string
    {
        return match ($reportType) {
            'incident_security' => 'IS',
            'lost_item' => 'LI',
            'lost_locker_card' => 'LC',
            'medical_attention' => 'M',
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
                $table->string('identity_number', 80)->nullable();
                $table->date('incident_date');
                $table->time('incident_time');
                $table->text('chronology');
                $table->string('staff_name', 120)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('reported_at');
                $table->timestamps();

                $table->index(['report_type', 'case_sequence']);
                $table->index(['incident_date', 'incident_time']);
                $table->index('reported_at');
            });
        }
    }
}
