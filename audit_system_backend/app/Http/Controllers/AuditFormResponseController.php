<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AuditApproval;
use App\Models\AuditFormAnswer;
use App\Models\AuditFormResponse;
use App\Models\AuditReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuditFormResponseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            AuditFormResponse::with(['form', 'engagement', 'user', 'answers.field', 'reviews', 'approvals'])->latest()->get()
        );
    }

    public function show(AuditFormResponse $auditFormResponse): JsonResponse
    {
        return response()->json($auditFormResponse->load(['form', 'engagement', 'user', 'answers.field', 'reviews', 'approvals']));
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

    public function saveAnswers(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if (! in_array($auditFormResponse->status, ['draft', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Jawaban hanya bisa diubah saat status draft atau revision_required (status saat ini: ' . $auditFormResponse->status . ').',
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

    public function submit(Request $request, AuditFormResponse $auditFormResponse): JsonResponse
    {
        if (! in_array($request->user()?->role?->name, ['Junior', 'Senior', 'Manager'], true)) {
            abort(403, 'Aksi submit hanya bisa dilakukan oleh Junior, Senior, atau Manager.');
        }

        if (! in_array($auditFormResponse->status, ['draft', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Form ini tidak bisa disubmit dari status saat ini: ' . $auditFormResponse->status,
            ]);
        }

        $auditFormResponse->update([
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $description = 'Form response #' . $auditFormResponse->id . ' disubmit untuk review.';

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
                'status' => 'Form ini tidak sedang menunggu review (status saat ini: ' . $auditFormResponse->status . ').',
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

        $description = 'Form response #' . $auditFormResponse->id . ' direview: ' . $data['action'] . '.';

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
                'status' => 'Form ini belum diteruskan untuk approval (status saat ini: ' . $auditFormResponse->status . ').',
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

        $description = 'Form response #' . $auditFormResponse->id . ' approval: ' . $data['action'] . '.';

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
}
