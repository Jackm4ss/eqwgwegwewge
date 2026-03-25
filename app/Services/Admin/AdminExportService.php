<?php

namespace App\Services\Admin;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportService
{
    public function __construct(
        private readonly SimpleXlsxWriter $xlsxWriter,
    ) {}

    public function csvDownload(string $filename, array $rows): StreamedResponse
    {
        $headers = $this->resolveHeaders($rows);

        return response()->streamDownload(function () use ($headers, $rows) {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv($stream, array_map(
                    fn (string $header) => $this->cellValue($row[$header] ?? null),
                    $headers,
                ));
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function xlsxDownload(string $filename, array $rows): Response
    {
        return response(
            $this->xlsxWriter->build($rows, 'Admin Export'),
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ],
        );
    }

    private function resolveHeaders(array $rows): array
    {
        $headers = [];

        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $headers[$key] = true;
            }
        }

        if ($headers === []) {
            return ['message'];
        }

        return array_keys($headers);
    }

    private function cellValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) ($value ?? '');
    }
}
