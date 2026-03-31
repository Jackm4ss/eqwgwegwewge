<?php

namespace App\Services\Scanner;

use App\Models\ScannerGate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ScannerGateService
{
    public const CACHE_KEY = 'scanner:gates:options';
    private const TABLE = 'scanner_gates';

    public function storageReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public function storageNotReadyMessage(): string
    {
        return 'Gate Management is not ready because the scanner_gates table has not been created yet. Run php artisan migrate first.';
    }

    public function findManageableGateById(int|string|null $id): ?ScannerGate
    {
        if (! $this->storageReady() || ! is_numeric((string) $id)) {
            return null;
        }

        return ScannerGate::query()->find((int) $id);
    }

    /**
     * @return array<int, array{id:int|null,name:string,sort_order:int}>
     */
    public function options(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            try {
                if (! $this->storageReady()) {
                    return $this->fallbackOptions();
                }

                $gates = ScannerGate::query()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name', 'sort_order']);

                if ($gates->isEmpty()) {
                    return $this->fallbackOptions();
                }

                return $gates
                    ->map(fn (ScannerGate $gate): array => [
                        'id' => $gate->id,
                        'name' => $this->normalizeName($gate->name),
                        'sort_order' => (int) $gate->sort_order,
                    ])
                    ->all();
            } catch (Throwable) {
                return $this->fallbackOptions();
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_values(array_map(
            static fn (array $gate): string => (string) $gate['name'],
            $this->options(),
        ));
    }

    public function exists(?string $name): bool
    {
        $normalized = $this->normalizeName($name);

        return $normalized !== '' && in_array($normalized, $this->names(), true);
    }

    public function normalizeSelected(?string $name): string
    {
        $normalized = $this->normalizeName($name);

        return $this->exists($normalized) ? $normalized : '';
    }

    public function firstName(): string
    {
        return $this->names()[0] ?? '';
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return EloquentCollection<int, ScannerGate>
     */
    public function manageableGates(): EloquentCollection
    {
        if (! $this->storageReady()) {
            return new EloquentCollection;
        }

        return ScannerGate::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function normalizeName(?string $name): string
    {
        return trim((string) $name);
    }

    /**
     * @return array<int, array{id:int|null,name:string,sort_order:int}>
     */
    private function fallbackOptions(): array
    {
        return array_values(array_map(
            static fn (string $name, int $index): array => [
                'id' => null,
                'name' => $name,
                'sort_order' => $index,
            ],
            $this->fallbackNames(),
            array_keys($this->fallbackNames()),
        ));
    }

    /**
     * @return array<int, string>
     */
    private function fallbackNames(): array
    {
        $configured = array_values(array_filter(array_map(
            fn (mixed $value): string => $this->normalizeName((string) $value),
            config('scanner.posts', ['Gate A']),
        )));

        return $configured !== [] ? $configured : ['Gate A'];
    }
}
