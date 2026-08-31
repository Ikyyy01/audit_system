<?php

namespace App\Http\Controllers;

use App\Models\AuditFormResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class AuditFormExportController extends Controller
{
    public function export(AuditFormResponse $auditFormResponse)
    {
        $auditFormResponse->load(['form', 'engagement.client', 'answers.field']);
        
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        
        $section->addText("FORM AUDIT: " . $auditFormResponse->form->name, ['bold' => true, 'size' => 16]);
        $section->addText("Klien: " . $auditFormResponse->engagement->client->name);
        $section->addText("Status: " . $auditFormResponse->status);
        $section->addTextBreak();
        
        foreach ($auditFormResponse->answers as $answer) {
            $section->addText($answer->field->field_label, ['bold' => true]);
            $section->addText($answer->response_value ?: '-');
            $section->addTextBreak();
        }
        
        $fileName = 'Audit_' . $auditFormResponse->form->code . '_' . $auditFormResponse->engagement->client->name . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'audit');
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
