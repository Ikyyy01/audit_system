<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AuditApproval;
use App\Models\AuditFormAnswer;
use App\Models\AuditFormResponse;
use App\Models\AuditReview;
use App\Models\AuditWorksheetRow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditFormResponseController extends Controller
{
    /**
     * Relasi standar yang selalu dibawa supaya frontend nggak perlu request
     * terpisah buat nampilin nama klien/reviewer/approver.
     */
    private const DEFAULT_RELATIONS = ['form', 'engagement.client', 'user', 'answers.field', 'worksheetRows', 'reviews.reviewer', 'approvals.approver'];

    public function index(): JsonResponse
    {
        return response()->json(
            AuditFormResponse::with(self::DEFAULT_RELATIONS)->latest()->get()
        );
    }

    public function show(AuditFormResponse $auditFormResponse): JsonResponse
    {
        return response()->json($auditFormResponse->load(self::DEFAULT_RELATIONS));
    }

    public function store(Request $request): JsonResponse
    {
        $auditFormResponse = AuditFormResponse::create($request->validate([
            'form_id' => ['required', 'integer', 'exists:audit_forms,id'],
            'engagement_id' => ['required', 'integer', 'exists:engagements,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'in:draft,pending_review,reviewed,revision_required,approved'],
            'submitted_at' => ['nullable', 'date'],
        ]));

        return response()->json($auditFormResponse, 201);
    }

    public function update(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        $auditFormResponse->update($request->validate([
            'form_id' => ['sometimes', 'required', 'integer', 'exists:audit_forms,id'],
            'engagement_id' => ['sometimes', 'required', 'integer', 'exists:engagements,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'in:draft,pending_review,reviewed,revision_required,approved'],
            'submitted_at' => ['nullable', 'date'],
        ]));

        return response()->json($auditFormResponse);
    }

    public function destroy(AuditFormResponse $auditFormResponse): JsonResponse
    {
        $auditFormResponse->delete();

        return response()->json(null, 204);
    }

    /**
     * Helper untuk memastikan teks aman dari karakter khusus XML (&, <, >, ", dsb.)
     * saat dimasukkan ke PhpWord, agar file .docx yang digenerate tidak korup.
     */
    private function safeText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        // Bersihkan non-printable control characters kecuali newline/tab
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        return htmlspecialchars($cleaned ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * Generate dokumen Word (.docx) dari isian form. Dipakai tombol
     * "Generate Word" di Form1100.vue (dan nantinya form dynamic lain).
     */
    public function export(AuditFormResponse $auditFormResponse): BinaryFileResponse
    {
        $auditFormResponse->load(['form']);

        // Jika tipe form adalah worksheet, ekspor ke format Excel (.xlsx)
        if ($auditFormResponse->form?->render_type === 'worksheet') {
            return $this->exportWorksheetExcel($auditFormResponse);
        }

        $auditFormResponse->load([
            'form.sections.fields',
            'engagement.client',
            'user',
            'answers.field',
            'reviews.reviewer',
            'approvals.approver',
        ]);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $docSection = $phpWord->addSection(['marginLeft' => 900, 'marginRight' => 900]);

        // Watermark: dokumen belum final selama status != approved, biar nggak
        // ketuker sama laporan resmi kalau nyasar ke tangan yang salah.
        $isFinal = $auditFormResponse->status === 'approved';
        if (! $isFinal) {
            $statusLabelMap = [
                'draft' => 'DRAFT',
                'pending_review' => 'DRAFT — MENUNGGU REVIEW',
                'reviewed' => 'DRAFT — MENUNGGU APPROVAL PARTNER',
                'revision_required' => 'DRAFT — REVISI DIPERLUKAN',
            ];
            $watermarkText = $statusLabelMap[$auditFormResponse->status] ?? 'DRAFT — BELUM DISETUJUI';

            $header = $docSection->addHeader();
            $header->addText($this->safeText($watermarkText), ['bold' => true, 'size' => 9, 'color' => 'B0503F'], ['alignment' => 'center']);

            $footer = $docSection->addFooter();
            $footer->addText($this->safeText($watermarkText.' — Dokumen ini belum final dan tidak boleh digunakan sebagai laporan resmi'), ['size' => 7, 'italic' => true, 'color' => 'B0503F'], ['alignment' => 'center']);

            $docSection->addText($this->safeText($watermarkText), ['bold' => true, 'size' => 13, 'color' => 'B0503F'], ['alignment' => 'center']);
            $docSection->addTextBreak(1);
        }

        $docSection->addText($this->safeText('KAP MGN & REKAN'), ['bold' => true, 'size' => 11, 'color' => '7F8C8D']);
        $docSection->addText($this->safeText($auditFormResponse->form?->name ?? 'FORM AUDIT'), ['bold' => true, 'size' => 14]);
        $docSection->addTextBreak(1);
        $clientName = $auditFormResponse->engagement?->client?->name ?? '-';
        $period = $auditFormResponse->engagement?->engagement_year ? '31 Desember '.$auditFormResponse->engagement->engagement_year : '-';
        $preparedBy = $auditFormResponse->user?->name ?? '-';
        $preparedDate = $auditFormResponse->submitted_at?->format('d/m/Y') ?? '-';

        $lastReview = $auditFormResponse->reviews->last();
        $reviewedBy = $lastReview?->reviewer?->name ?? '-';
        $reviewDate = $lastReview?->reviewed_at?->format('d/m/Y') ?? '-';

        $lastApproval = $auditFormResponse->approvals->last();
        $approvedBy = $lastApproval?->approver?->name ?? '-';
        $approvalDate = $lastApproval?->approval_date?->format('d/m/Y') ?? '-';

        $infoTable = $docSection->addTable(['borderSize' => 0, 'cellMargin' => 40]);
        foreach ([
            ['Nama Klien', $clientName],
            ['Periode', $period],
            ['Dibuat Oleh', $preparedBy.' ('.$preparedDate.')'],
            ['Direview Oleh', $reviewedBy.' ('.$reviewDate.')'],
            ['Disetujui Oleh', $approvedBy.' ('.$approvalDate.')'],
        ] as [$label, $value]) {
            $infoTable->addRow();
            $infoTable->addCell(2500)->addText($this->safeText($label), ['bold' => true, 'size' => 9]);
            $infoTable->addCell(6500)->addText($this->safeText($value), ['size' => 9]);
        }
        $docSection->addTextBreak(1);

        $answersByField = $auditFormResponse->answers->keyBy('field_id');
        $friendlyValueMap = ['Y' => 'Ya', 'T' => 'Tidak', 'N' => 'Tidak', 'NA' => 'N/A'];

        $formSections = $auditFormResponse->form?->sections?->sortBy('section_order') ?? collect();
        foreach ($formSections as $formSection) {
            $fields = $formSection->fields->sortBy('field_order')->values();
            if ($fields->isEmpty()) {
                continue;
            }

            $docSection->addText($this->safeText($formSection->section_name), ['bold' => true, 'size' => 11, 'color' => '2C3E50']);

            $table = $docSection->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 60]);
            $table->addRow();
            $table->addCell(500)->addText('No', ['bold' => true, 'size' => 9]);
            $table->addCell(4200)->addText('Item / Pertanyaan', ['bold' => true, 'size' => 9]);
            $table->addCell(4800)->addText('Jawaban', ['bold' => true, 'size' => 9]);

            foreach ($fields as $i => $field) {
                $rawValue = $answersByField[$field->id]->response_value ?? null;

                $table->addRow();
                $table->addCell(500)->addText((string) ($i + 1), ['size' => 9]);

                $labelLines = explode("\n", $field->field_label ?? '');
                $qCell = $table->addCell(4200);
                foreach ($labelLines as $li => $line) {
                    $qCell->addText($this->safeText($line), ['size' => 9, 'italic' => $li > 0]);
                }

                $aCell = $table->addCell(4800);

                // Field repeater: render sebagai sub-tabel baris terstruktur
                if ($field->field_type === 'repeater' && $rawValue) {
                    $repeaterColumns = is_array($field->options_json)
                        ? $field->options_json
                        : (is_string($field->options_json) ? json_decode($field->options_json, true) : []);
                    $repeaterRows = is_array($rawValue)
                        ? $rawValue
                        : (is_string($rawValue) ? json_decode($rawValue, true) : null);

                    if (is_array($repeaterRows) && ! empty($repeaterRows)) {
                        foreach ($repeaterRows as $ri => $rRow) {
                            $parts = [];
                            foreach ($repeaterColumns as $col) {
                                $v = $rRow[$col['key'] ?? ''] ?? '';
                                if ($v !== '') {
                                    $parts[] = ($col['label'] ?? '').': '.$v;
                                }
                            }
                            $lineText = ($ri + 1).'. '.implode(' | ', $parts);
                            $aCell->addText($this->safeText($lineText), ['size' => 9]);
                        }
                    } else {
                        $aCell->addText('-', ['size' => 9]);
                    }
                } else {
                    // Field biasa
                    $displayValue = $rawValue === null || $rawValue === '' ? '-' : ($friendlyValueMap[$rawValue] ?? $rawValue);
                    foreach (explode("\n", $displayValue) as $vline) {
                        $aCell->addText($this->safeText($vline), ['size' => 9]);
                    }
                }
            }

            $docSection->addTextBreak(1);
        }

        if ($auditFormResponse->partner_notes || $auditFormResponse->engagement_decision || $auditFormResponse->signature_path) {
            $docSection->addText($this->safeText('Catatan Rekan & Kesimpulan'), ['bold' => true, 'size' => 11, 'color' => '2C3E50']);

            if ($auditFormResponse->partner_notes) {
                $docSection->addText('Catatan Rekan:', ['bold' => true, 'size' => 9]);
                $docSection->addText($this->safeText($auditFormResponse->partner_notes), ['size' => 9]);
            }

            if ($auditFormResponse->engagement_decision) {
                $docSection->addText(
                    $this->safeText('Keputusan Penerimaan Klien: '.$auditFormResponse->engagement_decision),
                    ['bold' => true, 'size' => 10, 'color' => $auditFormResponse->engagement_decision === 'Diterima' ? '1F6B3A' : 'B0503F']
                );
            }

            if ($auditFormResponse->signature_path) {
                $signatureFullPath = Storage::disk('public')->path($auditFormResponse->signature_path);
                $isImage = in_array(strtolower(pathinfo($signatureFullPath, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg'], true);

                $docSection->addText('Tanda Tangan Partner:', ['bold' => true, 'size' => 9]);
                if ($isImage && file_exists($signatureFullPath)) {
                    $docSection->addImage($signatureFullPath, ['width' => 150, 'height' => 70]);
                } else {
                    $docSection->addText('(dokumen tanda tangan terlampir terpisah dalam format PDF)', ['size' => 9, 'italic' => true]);
                }
                if ($auditFormResponse->signature_uploaded_at) {
                    $docSection->addText('Diunggah pada: '.$auditFormResponse->signature_uploaded_at->format('d/m/Y H:i'), ['size' => 8, 'color' => '95A5A6']);
                }
            }

            $docSection->addTextBreak(1);
        }

        if ($auditFormResponse->reviews->isNotEmpty() || $auditFormResponse->approvals->isNotEmpty()) {
            $docSection->addText($this->safeText('Riwayat Review & Approval'), ['bold' => true, 'size' => 11, 'color' => '2C3E50']);

            foreach ($auditFormResponse->reviews as $review) {
                $docSection->addText(
                    $this->safeText('- Review oleh '.($review->reviewer?->name ?? '-').' - '.ucfirst(str_replace('_', ' ', $review->review_status))
                    .' ('.($review->reviewed_at?->format('d/m/Y H:i') ?? '-').')'),
                    ['size' => 9]
                );
                if ($review->comments) {
                    $docSection->addText($this->safeText('   Catatan: '.$review->comments), ['size' => 9, 'italic' => true]);
                }
            }

            foreach ($auditFormResponse->approvals as $approval) {
                $docSection->addText(
                    $this->safeText('- Approval oleh '.($approval->approver?->name ?? '-').' - '.ucfirst($approval->approval_status)
                    .' ('.($approval->approval_date?->format('d/m/Y H:i') ?? '-').')'),
                    ['size' => 9]
                );
                if ($approval->comments) {
                    $docSection->addText($this->safeText('   Catatan: '.$approval->comments), ['size' => 9, 'italic' => true]);
                }
            }
            $docSection->addTextBreak(1);
        }

        $docSection->addText('Dokumen ini digenerate otomatis oleh Audit System KAP MGN pada '.now()->format('d/m/Y H:i'), ['size' => 8, 'color' => '95A5A6', 'italic' => true]);

        $fileName = 'Audit_'.($auditFormResponse->form?->code ?? 'form').'_'.str_replace(' ', '_', $clientName).'_'.now()->format('Ymd_His').'.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'audit_export_').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Export worksheet form (render_type=worksheet) ke file Excel (.xlsx).
     * Kolom diambil dari audit_worksheet_columns, baris dari audit_worksheet_rows.
     */
    private function exportWorksheetExcel(AuditFormResponse $auditFormResponse): BinaryFileResponse
    {
        $auditFormResponse->load([
            'form.worksheetColumns',
            'engagement.client',
            'user',
            'worksheetRows',
            'reviews.reviewer',
            'approvals.approver',
        ]);

        $columns = $auditFormResponse->form->worksheetColumns->sortBy('column_order')->values();
        $rows    = $auditFormResponse->worksheetRows->sortBy('row_order')->values();

        $clientName = $auditFormResponse->engagement?->client?->name ?? 'Klien';
        $formCode   = $auditFormResponse->form?->code ?? 'form';
        $formName   = $auditFormResponse->form?->name ?? 'Worksheet Audit';
        $period     = $auditFormResponse->engagement?->engagement_year ? '31 Desember '.$auditFormResponse->engagement->engagement_year : '-';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Worksheet');

        // ── Header info ──────────────────────────────────────────
        $sheet->setCellValue('A1', 'KAP MGN & REKAN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->setCellValue('A2', $formName);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->setCellValue('A3', 'Form '.$formCode.'  |  '.$clientName.'  |  '.$period);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF7F8C8D');

        $isFinal = $auditFormResponse->status === 'approved';
        $statusLabel = $isFinal ? 'APPROVED — DOKUMEN FINAL' : match ($auditFormResponse->status) {
            'draft'             => 'DRAFT',
            'pending_review'    => 'DRAFT — MENUNGGU REVIEW',
            'reviewed'          => 'DRAFT — MENUNGGU APPROVAL PARTNER',
            'revision_required' => 'DRAFT — REVISI DIPERLUKAN',
            default             => 'DRAFT',
        };
        $sheet->setCellValue('A4', $statusLabel);
        $statusColor = $isFinal ? 'FF1F6B3A' : 'FFB0503F';
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(9)->getColor()->setARGB($statusColor);

        $sheet->setCellValue('A5', 'Dibuat: '.($auditFormResponse->user?->name ?? '-'));
        $sheet->getStyle('A5')->getFont()->setSize(8);

        // ── Watermark di header sheet ─────────────────────────────
        if (! $isFinal) {
            $headerObj = $sheet->getHeaderFooter();
            $headerObj->setOddHeader('&C&B&9&K'.'B0503F'.$statusLabel);
            $headerObj->setOddFooter('&C&8&I'.$statusLabel.' — Belum final');
        }

        // ── Baris header kolom (baris 7) ─────────────────────────
        $dataStartRow = 7;
        $colIdx = 1; // A = 1

        // Kolom nomor urut
        $sheet->getCell([$colIdx, $dataStartRow])->setValue('No');
        $colIdx++;

        foreach ($columns as $col) {
            $label = $col->column_label;
            if ($col->is_formula) {
                $label .= ' (fx)';
            }
            $sheet->getCell([$colIdx, $dataStartRow])->setValue($label);
            $colIdx++;
        }
        $lastColIdx = $colIdx - 1;
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIdx);

        $sheet->getStyle("A{$dataStartRow}:{$lastColLetter}{$dataStartRow}")->applyFromArray([
            'font'    => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF57606F']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F3F5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCED6E0']]],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ── Baris data ────────────────────────────────────────────
        $currentRow = $dataStartRow + 1;
        foreach ($rows as $rowIdx => $wsRow) {
            $rowData = $wsRow->data ?? [];
            $colIdx  = 1;
            $sheet->getCell([$colIdx, $currentRow])->setValue($rowIdx + 1);
            $colIdx++;

            foreach ($columns as $col) {
                $value = $rowData[$col->column_key] ?? '';
                // Formula kolom: hitung nilainya dari data baris yang ada
                if ($col->is_formula && $col->formula_expression) {
                    $expr = $col->formula_expression;
                    foreach ($columns->where('is_formula', false) as $depCol) {
                        $expr = str_replace($depCol->column_key, (float) ($rowData[$depCol->column_key] ?? 0), $expr);
                    }
                    // Juga replace formula-kolom lain yang mungkin jadi dependency
                    foreach ($columns->where('is_formula', true) as $depCol) {
                        if ($depCol->column_key !== $col->column_key) {
                            $depVal = (float) ($rowData[$depCol->column_key] ?? 0);
                            $expr   = str_replace($depCol->column_key, $depVal, $expr);
                        }
                    }
                    $value = preg_match('/^[\d\s+\-*\/().]*$/', $expr) ? (eval("return ({$expr});") ?: 0) : 0;
                }
                $sheet->getCell([$colIdx, $currentRow])->setValue($value === '' ? null : $value);
                $colIdx++;
            }

            $sheet->getStyle("A{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
                'font'    => ['size' => 9],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCED6E0']]],
            ]);
            $currentRow++;
        }

        // ── Baris TOTAL ───────────────────────────────────────────
        if ($rows->isNotEmpty()) {
            $colIdx = 1;
            $sheet->getCell([$colIdx, $currentRow])->setValue('TOTAL');
            $sheet->getStyle($sheet->getCell([$colIdx, $currentRow])->getCoordinate())
                ->getFont()->setBold(true)->setSize(9);
            $colIdx++;
            foreach ($columns as $col) {
                if (in_array($col->data_type, ['number', 'currency', 'formula'], true)) {
                    $dataRowStart = $dataStartRow + 1;
                    $dataRowEnd   = $currentRow - 1;
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->getCell([$colIdx, $currentRow])->setValue("=SUM({$colLetter}{$dataRowStart}:{$colLetter}{$dataRowEnd})");
                }
                $colIdx++;
            }
            $sheet->getStyle("A{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
                'font'    => ['bold' => true, 'size' => 9],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF1F3F5']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCED6E0']]],
            ]);
        }

        // ── Auto-width kolom ──────────────────────────────────────
        for ($c = 1; $c <= $lastColIdx; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        $fileName = "Worksheet_{$formCode}_".str_replace(' ', '_', $clientName).'_'.now()->format('Ymd_His').'.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), 'ws_export_').'.xlsx';
        SpreadsheetIOFactory::createWriter($spreadsheet, 'Xlsx')->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import baris worksheet dari file Excel/CSV yang diupload user.
     * Baris header (baris pertama sheet) dipakai untuk mapping ke column_key.
     * Jika header tidak cocok, kolom dipetakan berdasarkan urutan (positional).
     */
    public function importExcel(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if (! in_array($auditFormResponse->status, ['draft', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Import hanya bisa dilakukan saat status draft atau revision_required.',
            ]);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $auditFormResponse->load('form.worksheetColumns');
        $columns = $auditFormResponse->form->worksheetColumns
            ->sortBy('column_order')
            ->values();

        $file   = $request->file('file');
        $reader = SpreadsheetIOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet       = $spreadsheet->getActiveSheet();

        $allRows = $sheet->toArray(null, true, true, false);
        if (empty($allRows)) {
            return response()->json(['message' => 'File kosong atau tidak dapat dibaca.'], 422);
        }

        // Baris pertama → deteksi apakah ini baris header atau data
        $firstRow  = array_values($allRows[0]);
        $hasHeader = false;

        // Map: kolom Excel (index 0-based) → column_key
        $colKeyMap = [];

        // Coba cocokkan header baris pertama ke column_label atau column_key
        $labelToKey = $columns->pluck('column_key', 'column_label')->map(fn ($v) => $v)->toArray();
        $keySet     = $columns->pluck('column_key')->toArray();

        foreach ($firstRow as $excelColIdx => $headerValue) {
            $headerStr = trim((string) $headerValue);
            if (isset($labelToKey[$headerStr])) {
                $colKeyMap[$excelColIdx] = $labelToKey[$headerStr];
                $hasHeader = true;
            } elseif (in_array($headerStr, $keySet, true)) {
                $colKeyMap[$excelColIdx] = $headerStr;
                $hasHeader = true;
            }
        }

        // Jika tidak ada header yang cocok, pakai positional (skip kolom formula)
        if (empty($colKeyMap)) {
            $nonFormulaKeys = $columns->where('is_formula', false)->pluck('column_key')->values();
            foreach ($nonFormulaKeys as $idx => $key) {
                $colKeyMap[$idx] = $key;
            }
        }

        $dataRows = $hasHeader ? array_slice($allRows, 1) : $allRows;

        $importedRows = [];
        foreach ($dataRows as $rowIdx => $rowValues) {
            $rowValues = array_values($rowValues);

            // Skip baris yang seluruh nilainya kosong
            if (empty(array_filter($rowValues, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $rowData = [];
            foreach ($colKeyMap as $excelColIdx => $key) {
                $val = $rowValues[$excelColIdx] ?? null;
                $rowData[$key] = $val !== null ? (string) $val : '';
            }

            $importedRows[] = [
                'row_order' => $rowIdx + 1,
                'row_type'  => 'data',
                'data'      => $rowData,
            ];
        }

        if (empty($importedRows)) {
            return response()->json(['message' => 'Tidak ada baris data yang dapat diimport dari file.'], 422);
        }

        DB::transaction(function () use ($auditFormResponse, $importedRows) {
            AuditWorksheetRow::where('response_id', $auditFormResponse->id)->delete();
            foreach ($importedRows as $row) {
                AuditWorksheetRow::create([
                    'response_id' => $auditFormResponse->id,
                    'row_order'   => $row['row_order'],
                    'row_type'    => $row['row_type'],
                    'data'        => $row['data'],
                ]);
            }
        });

        $fresh = $auditFormResponse->fresh(['worksheetRows']);

        return response()->json([
            'imported_count' => count($importedRows),
            'rows'           => $fresh->worksheetRows->sortBy('row_order')->values(),
            'message'        => count($importedRows).' baris berhasil diimport dari Excel.',
        ]);
    }

    public function saveAnswers(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if (! in_array($auditFormResponse->status, ['draft', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Jawaban hanya bisa diubah saat status draft atau revision_required (status saat ini: '.$auditFormResponse->status.').',
            ]);
        }

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.field_id' => ['required', 'integer', 'exists:audit_form_fields,id'],
            'answers.*.response_value' => ['nullable', 'string'],
        ]);

        foreach ($data['answers'] as $answer) {
            AuditFormAnswer::updateOrCreate(
                ['response_id' => $auditFormResponse->id, 'field_id' => $answer['field_id']],
                ['response_value' => $answer['response_value'] ?? null]
            );
        }

        return response()->json($auditFormResponse->fresh(['answers.field']));
    }

    /**
     * Simpan seluruh baris worksheet sekaligus (replace-all), dipakai form
     * bertipe render_type=worksheet (mis. Form 3100 Balance Sheet). Replace-all
     * dipilih karena UX-nya user bebas tambah/hapus baris, jadi lebih simpel
     * & aman daripada diffing row per row.
     */
    public function saveWorksheetRows(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if (! in_array($auditFormResponse->status, ['draft', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Baris worksheet hanya bisa diubah saat status draft atau revision_required.',
            ]);
        }

        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.row_order' => ['required', 'integer'],
            'rows.*.row_type' => ['sometimes', 'in:data,subtotal,total'],
            'rows.*.data' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($auditFormResponse, $data) {
            AuditWorksheetRow::where('response_id', $auditFormResponse->id)->delete();
            foreach ($data['rows'] as $row) {
                AuditWorksheetRow::create([
                    'response_id' => $auditFormResponse->id,
                    'row_order' => $row['row_order'],
                    'row_type' => $row['row_type'] ?? 'data',
                    'data' => $row['data'] ?? [],
                ]);
            }
        });

        return response()->json($auditFormResponse->fresh(['worksheetRows']));
    }

    public function submit(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if (! in_array($request->user()?->role?->name, ['Junior', 'Senior', 'Manager'], true)) {
            abort(403, 'Aksi submit hanya bisa dilakukan oleh Junior, Senior, atau Manager.');
        }

        if (! in_array($auditFormResponse->status, ['draft', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Form ini tidak bisa disubmit dari status saat ini: '.$auditFormResponse->status,
            ]);
        }

        $auditFormResponse->update([
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $description = 'Form response #'.$auditFormResponse->id.' disubmit untuk review.';

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'submitted',
            'entity_type' => 'audit_form_response',
            'entity_id' => $auditFormResponse->id,
            'description' => $description,
            'created_at' => now(),
        ]);

        return response()->json($auditFormResponse->fresh(['form', 'engagement', 'user', 'reviews', 'approvals']));
    }

    public function review(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if ($request->user()?->role?->name !== 'Manager') {
            abort(403, 'Aksi review hanya bisa dilakukan oleh Manager.');
        }

        if ($auditFormResponse->status !== 'pending_review') {
            throw ValidationException::withMessages([
                'status' => 'Form ini tidak sedang menunggu review (status saat ini: '.$auditFormResponse->status.').',
            ]);
        }

        $data = $request->validate([
            'action' => ['required', 'in:approve,request_revision'],
            'comments' => ['nullable', 'string'],
        ]);

        $reviewStatus = $data['action'] === 'approve' ? 'approved' : 'revision_required';
        $newResponseStatus = $data['action'] === 'approve' ? 'reviewed' : 'revision_required';

        AuditReview::create([
            'response_id' => $auditFormResponse->id,
            'reviewed_by_user_id' => $request->user()->id,
            'review_status' => $reviewStatus,
            'comments' => $data['comments'] ?? null,
            'reviewed_at' => now(),
        ]);

        $auditFormResponse->update(['status' => $newResponseStatus]);

        $description = 'Form response #'.$auditFormResponse->id.' direview: '.$data['action'].'.';

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'reviewed',
            'entity_type' => 'audit_form_response',
            'entity_id' => $auditFormResponse->id,
            'description' => $description,
            'created_at' => now(),
        ]);

        return response()->json($auditFormResponse->fresh(['form', 'engagement', 'user', 'reviews', 'approvals']));
    }

    public function approve(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if ($request->user()?->role?->name !== 'Partner') {
            abort(403, 'Aksi approval hanya bisa dilakukan oleh Partner.');
        }

        if ($auditFormResponse->status !== 'reviewed') {
            throw ValidationException::withMessages([
                'status' => 'Form ini belum diteruskan untuk approval (status saat ini: '.$auditFormResponse->status.').',
            ]);
        }

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'comments' => ['nullable', 'string'],
        ]);

        $approvalStatus = $data['action'] === 'approve' ? 'approved' : 'rejected';
        $newResponseStatus = $data['action'] === 'approve' ? 'approved' : 'revision_required';

        AuditApproval::create([
            'response_id' => $auditFormResponse->id,
            'approved_by_user_id' => $request->user()->id,
            'approval_status' => $approvalStatus,
            'comments' => $data['comments'] ?? null,
            'approval_date' => now(),
        ]);

        $auditFormResponse->update(['status' => $newResponseStatus]);

        $description = 'Form response #'.$auditFormResponse->id.' approval: '.$data['action'].'.';

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'approved',
            'entity_type' => 'audit_form_response',
            'entity_id' => $auditFormResponse->id,
            'description' => $description,
            'created_at' => now(),
        ]);

        return response()->json($auditFormResponse->fresh(['form', 'engagement', 'user', 'reviews', 'approvals']));
    }

    public function savePartnerNotes(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if (! in_array($auditFormResponse->status, ['draft', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Catatan rekan & keputusan hanya bisa diubah saat status draft atau revision_required.',
            ]);
        }

        $data = $request->validate([
            'partner_notes' => ['nullable', 'string'],
            'engagement_decision' => ['nullable', 'in:Diterima,Ditolak'],
        ]);

        $auditFormResponse->update($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated_partner_notes',
            'entity_type' => 'audit_form_response',
            'entity_id' => $auditFormResponse->id,
            'description' => 'Catatan rekan & keputusan perikatan diperbarui.',
            'created_at' => now(),
        ]);

        return response()->json($auditFormResponse->fresh());
    }

    public function uploadSignature(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if ($request->user()?->role?->name !== 'Partner') {
            abort(403, 'Upload tanda tangan hanya bisa dilakukan oleh Partner.');
        }

        if ($auditFormResponse->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Form sudah final (approved), tanda tangan tidak bisa diubah.',
            ]);
        }

        $request->validate([
            'signature' => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
        ]);

        $file = $request->file('signature');
        $path = $file->store('signatures', 'public');

        if ($auditFormResponse->signature_path) {
            Storage::disk('public')->delete($auditFormResponse->signature_path);
        }

        $auditFormResponse->update([
            'signature_path' => $path,
            'signature_uploaded_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'uploaded_signature',
            'entity_type' => 'audit_form_response',
            'entity_id' => $auditFormResponse->id,
            'description' => 'Tanda tangan Partner diunggah: '.$file->getClientOriginalName(),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tanda tangan berhasil diunggah.',
            'signature_path' => $path,
            'signature_url' => Storage::disk('public')->url($path),
            'signature_uploaded_at' => $auditFormResponse->fresh()->signature_uploaded_at,
        ]);
    }
}
