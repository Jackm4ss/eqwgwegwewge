<?php

namespace App\Services\Firebase;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Google\Cloud\Core\Timestamp;

class FirestoreTimestampNormalizer
{
    public function prepareForStorage(array $data): array
    {
        return $this->transform($data, true);
    }

    public function normalizeFromStorage(array $data): array
    {
        return $this->transform($data, false);
    }

    private function transform(array $data, bool $forStorage): array
    {
        $transformed = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isTimestampField($key)) {
                $transformed[$key] = $forStorage
                    ? $this->normalizeTimestampForStorage($value)
                    : $this->normalizeTimestampFromStorage($value);
                continue;
            }

            if (is_array($value)) {
                $transformed[$key] = $this->transformNestedArray($value, $forStorage);
                continue;
            }

            $transformed[$key] = $value;
        }

        return $transformed;
    }

    private function transformNestedArray(array $value, bool $forStorage): array
    {
        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item) => is_array($item)
                    ? $this->transformNestedArray($item, $forStorage)
                    : $item,
                $value,
            );
        }

        return $this->transform($value, $forStorage);
    }

    private function normalizeTimestampForStorage(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Timestamp) {
            return DateTimeImmutable::createFromInterface($value->get())->setTimezone($this->utc());
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone($this->utc());
        }

        if (! is_string($value) || trim($value) === '') {
            return $value;
        }

        try {
            return (new DateTimeImmutable($value))->setTimezone($this->utc());
        } catch (\Throwable) {
            return $value;
        }
    }

    private function normalizeTimestampFromStorage(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Timestamp) {
            return (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)
                ->setTimezone($this->utc())
                ->format('Y-m-d\TH:i:s.u\Z');
        }

        if (! is_string($value) || trim($value) === '') {
            return $value;
        }

        try {
            return (new DateTimeImmutable($value))
                ->setTimezone($this->utc())
                ->format('Y-m-d\TH:i:s.u\Z');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function isTimestampField(string $field): bool
    {
        return str_ends_with($field, '_at');
    }

    private function utc(): DateTimeZone
    {
        static $timezone = null;

        return $timezone ??= new DateTimeZone('UTC');
    }
}
