<?php
/**
 * Generator PDF minimal WITHOUT dependency (A4 portrait, Helvetica).
 * Dipakai untuk ekspor laporan. Tidak memerlukan extension gd/zip/dompdf.
 */

final class SimplePdf
{
    private const PAGE_W = 595.28;
    private const PAGE_H = 841.89;
    private const MARGIN = 40.0;

    private array $pages = [];
    private string $stream = '';
    private float $y = 0.0;
    private int $pageNumber = 0;

    public function __construct(
        private string $title = 'Laporan',
        private string $subtitle = ''
    ) {
        $this->addPage();
    }

    public function addPage(): void
    {
        if ($this->stream !== '') {
            $this->pages[] = $this->stream;
        }
        $this->stream = '';
        $this->pageNumber++;
        $this->y = self::PAGE_H - self::MARGIN;

        $this->rect(self::MARGIN, self::PAGE_H - self::MARGIN + 8, self::PAGE_W - 2 * self::MARGIN, 28, 0.94, 0.96, 0.99);
        $this->text(self::MARGIN + 8, self::PAGE_H - self::MARGIN + 25, $this->title, 13, true);
        if ($this->subtitle !== '') {
            $this->text(self::MARGIN + 8, self::PAGE_H - self::MARGIN + 14, $this->subtitle, 8, false, 0.35, 0.42, 0.52);
        }
        $this->text(self::MARGIN, 26, 'Dicetak: ' . date('d/m/Y H:i') . '   |   Halaman ' . $this->pageNumber, 8, false, 0.4, 0.45, 0.52);
        $this->line(self::MARGIN, 38, self::PAGE_W - self::MARGIN, 38, 0.85, 0.87, 0.9);
    }

    public function section(string $text): void
    {
        $this->ensure(30);
        $this->rect(self::MARGIN, $this->y - 18, self::PAGE_W - 2 * self::MARGIN, 20, 0.95, 0.96, 0.98);
        $this->text(self::MARGIN + 8, $this->y - 12, $text, 10, true);
        $this->y -= 30;
    }

    public function paragraph(string $text, float $size = 9): void
    {
        $lines = $this->wrap($text, self::PAGE_W - 2 * self::MARGIN - 4, $size);
        $this->ensure(count($lines) * ($size + 4) + 4);
        foreach ($lines as $line) {
            $this->text(self::MARGIN + 2, $this->y - $size - 2, $line, $size);
            $this->y -= $size + 4;
        }
        $this->y -= 6;
    }

    /**
     * @param array<int,string> $headers
     * @param array<int,array<int,mixed>> $rows
     * @param array<int,float> $widths Lebar kolom dalam point (total maksimal ~515).
     */
    public function table(array $headers, array $rows, array $widths, float $size = 8): void
    {
        $available = self::PAGE_W - 2 * self::MARGIN;
        $total = array_sum($widths);
        if ($total > $available) {
            $widths = array_map(static fn(float $w): float => $w * $available / $total, $widths);
        }

        $drawHeader = function () use ($headers, $widths, $size): void {
            $this->ensure(26);
            $this->rect(self::MARGIN, $this->y - 20, array_sum($widths), 20, 0.16, 0.27, 0.53);
            $x = self::MARGIN;
            foreach ($headers as $i => $header) {
                $this->text($x + 4, $this->y - 13, (string) $header, $size, true, 1, 1, 1);
                $x += $widths[$i] ?? 60;
            }
            $this->y -= 20;
        };

        $drawHeader();
        $lineHeight = $size + 2.5;
        $alternate = false;

        foreach ($rows as $row) {
            $cells = [];
            $maxLines = 1;
            foreach ($row as $i => $cell) {
                $lines = $this->wrap((string) $cell, ($widths[$i] ?? 60) - 8, $size);
                $cells[] = $lines;
                $maxLines = max($maxLines, count($lines));
            }
            $rowHeight = $maxLines * $lineHeight + 8;

            if ($this->y - $rowHeight < self::MARGIN + 16) {
                $this->addPage();
                $drawHeader();
                $alternate = false;
            }

            if ($alternate) {
                $this->rect(self::MARGIN, $this->y - $rowHeight, array_sum($widths), $rowHeight, 0.97, 0.98, 0.99);
            }
            $alternate = !$alternate;

            $x = self::MARGIN;
            foreach ($cells as $i => $lines) {
                foreach ($lines as $lineIndex => $line) {
                    $this->text($x + 4, $this->y - 7 - $lineIndex * $lineHeight, $line, $size);
                }
                $x += $widths[$i] ?? 60;
            }

            $this->line(self::MARGIN, $this->y - $rowHeight, self::MARGIN + array_sum($widths), $this->y - $rowHeight, 0.9, 0.91, 0.93);
            $this->y -= $rowHeight;
        }
        $this->y -= 10;
    }

    public function output(string $filename): void
    {
        $this->pages[] = $this->stream;
        $this->stream = '';

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];

        $kids = [];
        $pageId = 5;
        foreach ($this->pages as $stream) {
            $contentId = $pageId + 1;
            $kids[] = $pageId . ' 0 R';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_W . ' ' . self::PAGE_H . '] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $pageId += 2;
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPosition . "\n%%EOF";

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $filename) ?: 'laporan';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeName . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $pdf;
        exit;
    }

    private function ensure(float $needed): void
    {
        if ($this->y - $needed < self::MARGIN + 16) {
            $this->addPage();
        }
    }

    private function rect(float $x, float $y, float $w, float $h, float $r, float $g, float $b): void
    {
        $this->stream .= sprintf('%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f%s', $r, $g, $b, $x, $y, $w, $h, "\n");
    }

    private function line(float $x1, float $y1, float $x2, float $y2, float $r, float $g, float $b): void
    {
        $this->stream .= sprintf('%.3f %.3f %.3f RG 0.6 w %.2f %.2f m %.2f %.2f l S%s', $r, $g, $b, $x1, $y1, $x2, $y2, "\n");
    }

    private function text(float $x, float $y, string $value, float $size, bool $bold = false, float $r = 0.08, float $g = 0.12, float $b = 0.2): void
    {
        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $value);
        $converted = $converted === false ? preg_replace('/[^\x00-\xFF]/', '?', $value) : $converted;
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $converted);
        $font = $bold ? '/F2' : '/F1';
        $this->stream .= sprintf(
            '%.3f %.3f %.3f rg BT %s %.2f Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET%s',
            $r, $g, $b, $font, $size, $x, $y, $escaped, "\n"
        );
    }

    private function wrap(string $text, float $width, float $size): array
    {
        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        $text = $converted === false ? preg_replace('/[^\x00-\xFF]/', '?', $text) : $converted;
        $text = trim(preg_replace('/\s+/', ' ', (string) $text) ?? '');
        if ($text === '') {
            return ['-'];
        }

        $maxChars = max(6, (int) floor($width / ($size * 0.52)));
        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            while (strlen($word) > $maxChars) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                $lines[] = substr($word, 0, $maxChars);
                $word = substr($word, $maxChars);
            }
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($candidate) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        return $lines ?: ['-'];
    }
}
