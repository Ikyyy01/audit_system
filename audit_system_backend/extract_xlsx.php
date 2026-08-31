<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($argc < 2) {
    echo "Usage: php extract_xlsx.php <path_to_xlsx>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

try {
    $spreadsheet = IOFactory::load($filePath);

    foreach ($spreadsheet->getSheetNames() as $sheetIndex => $sheetName) {
        $sheet = $spreadsheet->getSheet($sheetIndex);
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        echo "=== SHEET: {$sheetName} ===\n";
        
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        // batasi ke max col P (16) biar ga terlalu panjang outputnya
        $maxColIndex = min($highestColIndex, 16); 
        $maxColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxColIndex);
        
        echo "Range (truncated): A1:{$maxColLetter}".min($highestRow, 30)."\n\n";

        for ($row = 1; $row <= min($highestRow, 30); $row++) {
            $rowData = [];
            for ($colIndex = 1; $colIndex <= $maxColIndex; $colIndex++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $cell = $sheet->getCell($colLetter . $row);
                $value = $cell->getFormattedValue();
                $formula = '';
                if (str_starts_with((string)$cell->getValue(), '=')) {
                    $formula = ' [F: ' . $cell->getValue() . ']';
                }
                if ($value !== '' && $value !== null) {
                    // hapus newline di dalam value cell biar ga menuhin terminal
                    $cleanValue = str_replace(["\r", "\n"], " ", $value); 
                    $rowData[] = "{$colLetter}{$row}={$cleanValue}{$formula}";
                }
            }
            if (!empty($rowData)) {
                echo "Row {$row}: " . implode(' | ', $rowData) . "\n";
            }
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
