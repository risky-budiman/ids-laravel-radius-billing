<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pembuat file Excel (.xlsx) sederhana untuk laporan: judul, tabel, baris total.
 *
 * Format kolom: 'text' (default), 'int', 'money' (#.##0), 'pct', 'date', 'datetime'.
 * Angka ditulis sebagai angka sungguhan agar bisa dijumlah/difilter di Excel.
 */
class SpreadsheetExport
{
    private Spreadsheet $book;
    private bool $fresh = true;

    public function __construct(private string $company = '')
    {
        $this->book = new Spreadsheet();
        $this->book->getProperties()->setCreator($company ?: 'Radius Billing')->setCompany($company);
    }

    /**
     * @param  string[]  $titleLines  baris pertama tebal (judul), sisanya keterangan
     * @param  string[]  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>  $formats  indeks kolom (0-based) => format
     * @param  array<int, mixed>|null  $totals  baris total (tebal) di bawah tabel
     */
    public function sheet(string $name, array $titleLines, array $headers, iterable $rows, array $formats = [], ?array $totals = null, array $widths = []): static
    {
        $sheet = $this->fresh ? $this->book->getActiveSheet() : $this->book->createSheet();
        $this->fresh = false;
        $sheet->setTitle($this->sheetName($name));

        $r = 1;
        if ($this->company !== '') {
            $sheet->setCellValue([1, $r], $this->company);
            $sheet->getStyle([1, $r])->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('475569');
            $r++;
        }
        foreach ($titleLines as $i => $line) {
            $sheet->setCellValue([1, $r], $line);
            $font = $sheet->getStyle([1, $r])->getFont();
            $i === 0 ? $font->setBold(true)->setSize(14) : $font->getColor()->setRGB('475569');
            $r++;
        }
        $r++;

        $cols = count($headers);
        $headerRow = $r;
        foreach ($headers as $c => $h) {
            $sheet->setCellValue([$c + 1, $r], $h);
        }
        $headerRange = [1, $r, $cols, $r];
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('334155');
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $r++;

        $firstData = $r;
        foreach ($rows as $row) {
            $this->writeRow($sheet, $r, array_values($row), $formats);
            $r++;
        }
        $lastData = $r - 1;

        if ($totals !== null) {
            $this->writeRow($sheet, $r, $totals, $formats);
            $range = [1, $r, $cols, $r];
            $sheet->getStyle($range)->getFont()->setBold(true);
            $sheet->getStyle($range)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($range)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
            $r++;
        }

        // Format angka per kolom (data + total)
        foreach ($formats as $c => $fmt) {
            $code = match ($fmt) {
                'money' => '#,##0;(#,##0);-',
                'int' => '#,##0;-#,##0;0',
                'pct' => '0.0"%"',
                'date' => 'dd/mm/yyyy',
                'datetime' => 'dd/mm/yyyy hh:mm',
                default => null,
            };
            if ($code && $r - 1 >= $firstData) {
                $sheet->getStyle([$c + 1, $firstData, $c + 1, $r - 1])->getNumberFormat()->setFormatCode($code);
            }
            if (in_array($fmt, ['money', 'int', 'pct'], true)) {
                $sheet->getStyle([$c + 1, $headerRow, $c + 1, max($headerRow, $r - 1)])
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        if ($lastData >= $firstData) {
            $sheet->getStyle([1, $headerRow, $cols, $lastData])->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('CBD5E1');
        }

        for ($c = 1; $c <= $cols; $c++) {
            $letter = Coordinate::stringFromColumnIndex($c);
            if (isset($widths[$c - 1])) {
                $sheet->getColumnDimension($letter)->setWidth($widths[$c - 1]);
            } else {
                $sheet->getColumnDimension($letter)->setAutoSize(true);
            }
        }
        $sheet->freezePane([1, $headerRow + 1]);
        $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);

        return $this;
    }

    public function download(string $filename): StreamedResponse
    {
        $this->book->setActiveSheetIndex(0);
        $writer = new Xlsx($this->book);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    private function writeRow($sheet, int $r, array $values, array $formats): void
    {
        foreach ($values as $c => $value) {
            $fmt = $formats[$c] ?? 'text';
            if ($value === null || $value === '') {
                continue;
            }
            if ($value instanceof \DateTimeInterface) {
                $sheet->setCellValue([$c + 1, $r], ExcelDate::PHPToExcel($value));
            } elseif ($fmt === 'text' || ! is_numeric($value)) {
                // Teks eksplisit: nomor tiket/rekening tidak diubah jadi angka/rumus.
                $sheet->setCellValueExplicit([$c + 1, $r], (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue([$c + 1, $r], $value + 0);
            }
        }
    }

    private function sheetName(string $name): string
    {
        $clean = trim(preg_replace('/[\[\]\:\*\?\/\\\\]/', '-', $name));

        return mb_substr($clean !== '' ? $clean : 'Sheet', 0, 31);
    }
}
