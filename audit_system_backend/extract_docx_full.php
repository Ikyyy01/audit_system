<?php

require 'vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\CheckBox;

if ($argc < 2) {
    echo "Usage: php extract_docx_full.php <path_to_docx>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

function extractText($element): string {
    $text = '';
    if (method_exists($element, 'getText')) {
        $val = $element->getText();
        if (is_string($val)) {
            return $val;
        }
        if (is_object($val) && method_exists($val, 'getText')) {
            return $val->getText();
        }
    }
    if (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $child) {
            $text .= extractText($child);
        }
    }
    return $text;
}

try {
    $phpWord = IOFactory::load($filePath);

    foreach ($phpWord->getSections() as $si => $section) {
        echo "=== SECTION " . ($si + 1) . " ===\n\n";

        foreach ($section->getElements() as $element) {
            $class = get_class($element);

            if ($element instanceof Table) {
                $rows = $element->getRows();
                echo "[TABLE: " . count($rows) . " rows]\n";
                foreach ($rows as $ri => $row) {
                    $cells = $row->getCells();
                    $cellTexts = [];
                    foreach ($cells as $cell) {
                        $ct = '';
                        foreach ($cell->getElements() as $ce) {
                            $ct .= extractText($ce) . ' ';
                        }
                        $cellTexts[] = trim($ct);
                    }
                    echo "  ROW $ri: " . implode(' | ', $cellTexts) . "\n";
                }
                echo "[/TABLE]\n\n";
            } elseif ($element instanceof ListItem) {
                echo "  - " . extractText($element) . "\n";
            } else {
                $t = extractText($element);
                if (trim($t) !== '') {
                    echo $t . "\n";
                }
            }
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
