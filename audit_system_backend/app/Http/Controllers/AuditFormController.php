<?php

namespace App\Http\Controllers;

use App\Models\AuditForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditFormController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            AuditForm::with(['parentForm', 'childForms', 'sections.fields', 'worksheetColumns'])->orderBy('code')->get()
        );
    }

    public function show(AuditForm $auditForm): JsonResponse
    {
        return response()->json($auditForm->load(['parentForm', 'childForms', 'sections.fields', 'worksheetColumns']));
    }

    public function store(Request $request): JsonResponse
    {
        $auditForm = AuditForm::create($request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:audit_forms,code'],
            'name' => ['required', 'string', 'max:255'],
            'parent_form_id' => ['nullable', 'integer', 'exists:audit_forms,id'],
            'form_type' => ['required', 'in:parent,child,single'],
        ]));

        return response()->json($auditForm, 201);
    }

    public function update(Request $request, AuditForm $auditForm): JsonResponse
    {
        $auditForm->update($request->validate([
            'code' => ['sometimes', 'required', 'string', 'max:20', 'unique:audit_forms,code,' . $auditForm->id],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'parent_form_id' => ['nullable', 'integer', 'exists:audit_forms,id'],
            'form_type' => ['sometimes', 'required', 'in:parent,child,single'],
        ]));

        return response()->json($auditForm);
    }

    public function destroy(AuditForm $auditForm): JsonResponse
    {
        $auditForm->delete();

        return response()->json(null, 204);
    }
}
