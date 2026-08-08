<?php
// includes/document_extract.php
// Extracts plain text from an uploaded PDF or DOCX file so it can be
// sent to the ML service for structured SBM form extraction.
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Extracts raw text from a PDF file on disk.
 * @throws Exception on parse failure
 */
function extractTextFromPdf(string $filePath): string
{
    $parser   = new \Smalot\PdfParser\Parser();
    $pdf      = $parser->parseFile($filePath);
    $text     = $pdf->getText();
    return trim($text);
}

/**
 * Extracts raw text from a DOCX file on disk (paragraphs + table cells,
 * in document order, so MOV text sitting in a table column is captured).
 * @throws Exception on parse failure
 */
function extractTextFromDocx(string $filePath): string
{
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
    $lines   = [];

    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            $lines[] = extractDocxElementText($element);
        }
    }

    return trim(implode("\n", array_filter($lines, fn($l) => trim($l) !== '')));
}

/**
 * Recursively pulls text out of a PhpWord element (handles plain text
 * runs, text runs made of multiple parts, and tables/rows/cells).
 */
function extractDocxElementText($element): string
{
    if (method_exists($element, 'getText')) {
        $t = $element->getText();
        return is_string($t) ? $t : '';
    }

    if (method_exists($element, 'getElements')) {
        $parts = [];
        foreach ($element->getElements() as $child) {
            $parts[] = extractDocxElementText($child);
        }
        return implode(' ', array_filter($parts, fn($p) => trim($p) !== ''));
    }

    if (method_exists($element, 'getRows')) {
        $rowLines = [];
        foreach ($element->getRows() as $row) {
            $cellParts = [];
            foreach ($row->getCells() as $cell) {
                $cellParts[] = extractDocxElementText($cell);
            }
            $rowLines[] = implode(' | ', array_filter($cellParts, fn($p) => trim($p) !== ''));
        }
        return implode("\n", $rowLines);
    }

    return '';
}

/**
 * Dispatches to the right extractor based on file extension.
 * Returns [ 'ok' => bool, 'text' => string, 'msg' => string ]
 */
function extractTextFromUploadedForm(string $filePath, string $originalName): array
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    try {
        if ($ext === 'pdf') {
            $text = extractTextFromPdf($filePath);
        } elseif (in_array($ext, ['docx', 'doc'])) {
            $text = extractTextFromDocx($filePath);
        } else {
            return ['ok' => false, 'text' => '', 'msg' => 'Unsupported file type. Please upload a PDF or DOCX file.'];
        }
    } catch (Exception $e) {
        return ['ok' => false, 'text' => '', 'msg' => 'Could not read the document: ' . $e->getMessage()];
    }

    if (trim($text) === '') {
        return ['ok' => false, 'text' => '', 'msg' => 'No readable text found in the document. If this is a scanned PDF (image-only), text extraction is not supported.'];
    }

    return ['ok' => true, 'text' => $text, 'msg' => ''];
}