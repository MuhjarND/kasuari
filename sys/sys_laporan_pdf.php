<?php
/**
 * PDF sederhana untuk laporan resmi Kasuari.
 * Sengaja dibuat tanpa library eksternal agar tetap dapat dipasang di XAMPP/Laragon.
 */
class KasuariLaporanPdf
{
    private $width;
    private $height;
    private $pages = array();
    private $page = '';
    private $jpeg = null;
    private $fill = array(0, 0, 0);
    private $stroke = array(0, 0, 0);

    public function __construct($kopPath, $width = 1008, $height = 612)
    {
        $this->width = (float) $width;
        $this->height = (float) $height;
        if (function_exists('imagecreatefrompng') && is_file($kopPath)) {
            $image = @imagecreatefrompng($kopPath);
            if ($image) {
                ob_start();
                imagejpeg($image, null, 92);
                $this->jpeg = ob_get_clean();
                imagedestroy($image);
            }
        }
    }

    public function addPage()
    {
        if ($this->page !== '') $this->pages[] = $this->page . "Q\n";
        $this->page = "q\n";
        $this->setStroke(array(0, 0, 0));
        $this->setFill(array(0, 0, 0));
    }

    public function finish()
    {
        if ($this->page !== '') {
            $this->pages[] = $this->page . "Q\n";
            $this->page = '';
        }

        $objects = array();
        $catalog = $this->newObject($objects, '');
        $pages = $this->newObject($objects, '');
        $fontRegular = $this->newObject($objects, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>");
        $fontBold = $this->newObject($objects, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>");
        $imageObject = null;
        if ($this->jpeg !== null) {
            $imageObject = count($objects) + 1;
            $objects[] = null;
            $jpegLength = strlen($this->jpeg);
            $objects[$imageObject - 1] = "<< /Type /XObject /Subtype /Image /Width 1651 /Height 312 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLength} >>\nstream\n" . $this->jpeg . "\nendstream";
        }

        $pageRefs = array();
        foreach ($this->pages as $stream) {
            $contentId = $this->newObject($objects, "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream");
            $resources = "/Font << /F1 {$fontRegular} 0 R /F2 {$fontBold} 0 R >>";
            if ($imageObject !== null) $resources .= " /XObject << /Kop {$imageObject} 0 R >>";
            $pageId = $this->newObject($objects, "<< /Type /Page /Parent {$pages} 0 R /MediaBox [0 0 {$this->width} {$this->height}] /Resources << {$resources} >> /Contents {$contentId} 0 R >>");
            $pageRefs[] = $pageId . ' 0 R';
        }

        $objects[$pages - 1] = "<< /Type /Pages /Kids [" . implode(' ', $pageRefs) . "] /Count " . count($pageRefs) . " >>";
        $objects[$catalog - 1] = "<< /Type /Catalog /Pages {$pages} 0 R >>";

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = array(0);
        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalog} 0 R >>\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    public function output($filename, $download = false)
    {
        $pdf = $this->finish();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . preg_replace('/[^A-Za-z0-9_.-]+/', '_', $filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }

    public function kop($x, $y, $w, $h)
    {
        if ($this->jpeg === null) return;
        $this->page .= "q " . $this->number($w) . " 0 0 " . $this->number($h) . " " . $this->number($x) . " " . $this->number($this->height - $y - $h) . " cm /Kop Do Q\n";
    }

    public function text($x, $y, $text, $size = 9, $bold = false, $align = 'left')
    {
        $text = $this->encode($text);
        $width = $this->textWidth($text, $size);
        if ($align === 'center') $x -= $width / 2;
        if ($align === 'right') $x -= $width;
        $this->page .= "BT /" . ($bold ? 'F2' : 'F1') . " " . $this->number($size) . " Tf 1 0 0 1 " . $this->number($x) . " " . $this->number($this->height - $y - $size) . " Tm (" . $this->escape($text) . ") Tj ET\n";
    }

    public function wrappedText($x, $y, $w, $text, $size = 7, $lineHeight = 9, $bold = false, $maxLines = 8)
    {
        $lines = $this->wrap($text, $w, $size);
        $i = 0;
        foreach ($lines as $line) {
            if ($i >= $maxLines) break;
            $this->text($x, $y + ($i * $lineHeight), $line, $size, $bold);
            $i++;
        }
        return max(1, $i) * $lineHeight;
    }

    public function wrapForReport($text, $width, $size)
    {
        return $this->wrap($text, $width, $size);
    }

    public function pageWidth()
    {
        return $this->width;
    }

    public function pageHeight()
    {
        return $this->height;
    }

    public function setFillForReport($color)
    {
        $this->setFill($color);
    }

    public function line($x1, $y1, $x2, $y2, $width = 0.6)
    {
        $this->page .= $this->number($width) . " w " . $this->number($x1) . " " . $this->number($this->height - $y1) . " m " . $this->number($x2) . " " . $this->number($this->height - $y2) . " l S\n";
    }

    public function rect($x, $y, $w, $h, $fill = false, $lineWidth = 0.45)
    {
        $this->page .= $this->number($lineWidth) . " w " . $this->number($x) . " " . $this->number($this->height - $y - $h) . " " . $this->number($w) . " " . $this->number($h) . " re " . ($fill ? 'B' : 'S') . "\n";
    }

    public function fillRect($x, $y, $w, $h, $color)
    {
        $old = $this->fill;
        $this->setFill($color);
        $this->page .= $this->number($x) . " " . $this->number($this->height - $y - $h) . " " . $this->number($w) . " " . $this->number($h) . " re f\n";
        $this->setFill($old);
    }

    private function setFill($color)
    {
        $this->fill = $color;
        $this->page .= $this->number($color[0] / 255) . ' ' . $this->number($color[1] / 255) . ' ' . $this->number($color[2] / 255) . " rg\n";
    }

    private function setStroke($color)
    {
        $this->stroke = $color;
        $this->page .= $this->number($color[0] / 255) . ' ' . $this->number($color[1] / 255) . ' ' . $this->number($color[2] / 255) . " RG\n";
    }

    private function wrap($text, $width, $size)
    {
        $text = strip_tags((string) $text);
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n[ \t]+/', "\n", $text);
        $text = preg_replace('/[ \t]+\n/', "\n", $text);
        $text = trim($text);
        if ($text === '') return array('-');
        $lines = array();
        $paragraphs = preg_split('/\r\n|\r|\n/', $text);
        foreach ($paragraphs as $paragraph) {
            $words = preg_split('/\s+/', trim($paragraph));
            $line = '';
            foreach ($words as $word) {
                if ($word === '') continue;
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if ($this->textWidth($candidate, $size) <= $width || $line === '') $line = $candidate;
                else { $lines[] = $line; $line = $word; }
            }
            if ($line !== '') $lines[] = $line;
        }
        if (count($lines) === 0) $lines[] = '-';
        return $lines;
    }

    private function textWidth($text, $size)
    {
        return strlen($text) * $size * 0.49;
    }

    private function encode($text)
    {
        $text = trim((string) $text);
        if ($text === '') return '-';
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
            if ($converted !== false) return $converted;
        }
        return $text;
    }

    private function escape($text)
    {
        return str_replace(array('\\', '(', ')', "\r", "\n"), array('\\\\', '\\(', '\\)', '', ' '), $text);
    }

    private function number($value)
    {
        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    }

    private function newObject(&$objects, $body)
    {
        $objects[] = $body;
        return count($objects);
    }
}
