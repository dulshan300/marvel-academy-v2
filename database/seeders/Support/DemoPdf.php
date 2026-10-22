<?php

namespace Database\Seeders\Support;

/**
 * Minimal text-only PDF writer for demo course materials. Produces valid,
 * multi-page A4 documents with a title and wrapped paragraphs using the
 * built-in Helvetica fonts, so seeding needs no PDF library.
 */
class DemoPdf
{
    private const PAGE_WIDTH = 595;

    private const PAGE_HEIGHT = 842;

    private const MARGIN = 56;

    private const LINES_PER_PAGE = 46;

    /** @var array<int, array{text: string, style: string}> */
    private array $lines = [];

    public function __construct(private string $title) {}

    public function heading(string $text): static
    {
        $this->lines[] = ['text' => '', 'style' => 'body'];
        $this->lines[] = ['text' => $text, 'style' => 'heading'];

        return $this;
    }

    public function paragraph(string $text): static
    {
        foreach (explode("\n", wordwrap($this->toLatin1($text), 92, "\n", true)) as $line) {
            $this->lines[] = ['text' => $line, 'style' => 'body'];
        }

        return $this;
    }

    /**
     * @param  array<int, string>  $items
     */
    public function bullets(array $items): static
    {
        foreach ($items as $item) {
            $wrapped = explode("\n", wordwrap($this->toLatin1($item), 86, "\n", true));

            foreach ($wrapped as $index => $line) {
                $this->lines[] = ['text' => ($index === 0 ? '-  ' : '   ').$line, 'style' => 'body'];
            }
        }

        return $this;
    }

    public function render(): string
    {
        $pages = array_chunk($this->lines, self::LINES_PER_PAGE);
        $pageCount = max(1, count($pages));
        $objects = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $kids = [];

        foreach ($pages ?: [[]] as $index => $pageLines) {
            $pageObject = 5 + $index * 2;
            $contentObject = $pageObject + 1;
            $kids[] = "{$pageObject} 0 R";

            $stream = $this->pageStream($pageLines, $index === 0, $index + 1, $pageCount);

            $objects[$pageObject] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentObject,
            );
            $objects[$contentObject] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$pageCount.' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n".'0 '.(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer'."\n".'<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n{$xref}\n%%EOF\n";
    }

    /**
     * @param  array<int, array{text: string, style: string}>  $lines
     */
    private function pageStream(array $lines, bool $isFirstPage, int $pageNumber, int $pageCount): string
    {
        $y = self::PAGE_HEIGHT - self::MARGIN;
        $commands = [
            // Brand band across the top of every page.
            '0.039 0.388 0.459 rg 0 '.(self::PAGE_HEIGHT - 18).' '.self::PAGE_WIDTH.' 18 re f',
            '0 0 0 rg',
        ];

        if ($isFirstPage) {
            $commands[] = 'BT /F2 9 Tf 0.039 0.388 0.459 rg '.self::MARGIN.' '.($y + 4).' Td (MARVEL ACADEMY) Tj ET';
            $commands[] = 'BT /F2 18 Tf 0.075 0.161 0.239 rg '.self::MARGIN.' '.($y - 22).' Td ('.$this->escape($this->toLatin1($this->title)).') Tj ET';
            $y -= 56;
        }

        foreach ($lines as $line) {
            [$font, $size, $colour] = $line['style'] === 'heading'
                ? ['F2', 12.5, '0.039 0.388 0.459']
                : ['F1', 10.5, '0.2 0.25 0.3'];

            if ($line['text'] !== '') {
                $commands[] = sprintf('BT /%s %.1f Tf %s rg %d %.1f Td (%s) Tj ET', $font, $size, $colour, self::MARGIN, $y, $this->escape($line['text']));
            }

            $y -= $line['style'] === 'heading' ? 18 : 15;
        }

        $commands[] = sprintf('BT /F1 8 Tf 0.5 0.55 0.6 rg %d 30 Td (Marvel Academy  |  Page %d of %d) Tj ET', self::MARGIN, $pageNumber, $pageCount);

        return implode("\n", $commands);
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function toLatin1(string $text): string
    {
        $text = strtr($text, ['–' => '-', '—' => '-', '’' => "'", '‘' => "'", '“' => '"', '”' => '"', '…' => '...', '·' => '-', '→' => '->']);

        return mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
    }
}
