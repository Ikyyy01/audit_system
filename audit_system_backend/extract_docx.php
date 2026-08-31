<?php

require 'vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

if ($argc < 2) {
    echo "Usage: php extract_docx.php <path_to_docx>\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "File not found: $filePath\n";
    exit(1);
}

try {
    $phpWord = IOFactory::load($filePath);
    
    echo "=== DOCUMENT CONTENT ===\n\n";
    
    foreach ($phpWord->getSections() as $sectionIndex => $section) {
        echo "--- Section " . ($sectionIndex + 1) . " ---\n\n";
        
        foreach ($section->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                echo $element->getText() . "\n";
            } elseif (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $childElement) {
                    if (method_exists($childElement, 'getText')) {
                        echo $childElement->getText() . "\n";
                    } elseif (method_exists($childElement, 'getElements')) {
                        foreach ($childElement->getElements() as $subChild) {
                            if (method_exists($subChild, 'getText')) {
                                echo $subChild->getText() . " ";
                            }
                        }
                        echo "\n";
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    echo "\n=== END OF DOCUMENT ===\n";
    
} catch (Exception $e) {
    echo "Error reading document: " . $e->getMessage() . "\n";
    exit(1);
}
