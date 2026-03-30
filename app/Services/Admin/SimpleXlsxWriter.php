<?php

namespace App\Services\Admin;

use RuntimeException;
use ZipArchive;

class SimpleXlsxWriter
{
    public function build(array $rows, string $worksheetName = 'Sheet1'): string
    {
        $headers = $this->resolveHeaders($rows);
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx-admin-');

        if ($tempFile === false) {
            throw new RuntimeException('Unable to create a temporary XLSX file.');
        }

        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open XLSX archive for writing.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addEmptyDir('_rels');
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addEmptyDir('xl');
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($worksheetName));
        $zip->addEmptyDir('xl/_rels');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addEmptyDir('xl/worksheets');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($headers, $rows));
        $zip->close();

        $binary = file_get_contents($tempFile);
        @unlink($tempFile);

        if ($binary === false) {
            throw new RuntimeException('Unable to read generated XLSX file.');
        }

        return $binary;
    }

    private function resolveHeaders(array $rows): array
    {
        $headers = [];

        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $headers[$key] = true;
            }
        }

        return $headers === []
            ? ['message']
            : array_keys($headers);
    }

    private function worksheetXml(array $headers, array $rows): string
    {
        $rowXml = [];
        $rowIndex = 1;
        $rowXml[] = $this->rowXml($rowIndex, $headers);

        foreach ($rows as $row) {
            $rowIndex++;
            $cells = [];

            foreach ($headers as $header) {
                $cells[] = $this->stringCell(
                    $this->cellReference($rowIndex, count($cells) + 1),
                    $this->cellValue($row[$header] ?? null),
                );
            }

            $rowXml[] = sprintf('<row r="%d">%s</row>', $rowIndex, implode('', $cells));
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    {$rowXml[0]}
    {$this->joinWorksheetRows(array_slice($rowXml, 1))}
  </sheetData>
</worksheet>
XML;
    }

    private function joinWorksheetRows(array $rows): string
    {
        return implode("\n    ", $rows);
    }

    private function rowXml(int $rowIndex, array $values): string
    {
        $cells = [];

        foreach ($values as $index => $value) {
            $cells[] = $this->stringCell(
                $this->cellReference($rowIndex, $index + 1),
                $this->cellValue($value),
            );
        }

        return sprintf('<row r="%d">%s</row>', $rowIndex, implode('', $cells));
    }

    private function stringCell(string $reference, string $value): string
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_XML1);

        return sprintf(
            '<c r="%s" t="inlineStr"><is><t>%s</t></is></c>',
            $reference,
            $escaped,
        );
    }

    private function cellReference(int $row, int $column): string
    {
        $letters = '';

        while ($column > 0) {
            $modulo = ($column - 1) % 26;
            $letters = chr(65 + $modulo).$letters;
            $column = (int) floor(($column - $modulo) / 26);
        }

        return $letters.$row;
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

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;
    }

    private function rootRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function workbookXml(string $worksheetName): string
    {
        $escaped = htmlspecialchars($worksheetName, ENT_QUOTES | ENT_XML1);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="{$escaped}" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML;
    }

    private function workbookRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML;
    }
}
