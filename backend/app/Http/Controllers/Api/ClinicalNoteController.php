<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;
use App\Models\Visit;
use Illuminate\Http\Request;

class ClinicalNoteController extends Controller
{
    public function index(Visit $visit)
    {
        return response()->json(
            $visit->clinicalNotes()
                ->with(['sourceDocument', 'creator', 'updater'])
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'source_document_id' => ['nullable', 'exists:visit_documents,id'],
            'note_type' => ['required', 'in:visit_conclusion,prescription_text,follow_up_note,general_note'],
            'content_text' => ['required', 'string'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'created_by' => ['nullable', 'exists:users,id'],
            'updated_by' => ['nullable', 'exists:users,id'],
        ]);

        $note = ClinicalNote::create([
            'visit_id' => $visit->id,
            'source_document_id' => $data['source_document_id'] ?? null,
            'note_type' => $data['note_type'],
            'content_text' => $data['content_text'],
            'is_manually_corrected' => (bool) ($data['is_manually_corrected'] ?? false),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ]);

        return response()->json($note->load(['sourceDocument', 'creator', 'updater']), 201);
    }

    public function show(ClinicalNote $clinicalNote)
    {
        return response()->json(
            $clinicalNote->load(['visit', 'sourceDocument', 'creator', 'updater'])
        );
    }

    public function update(Request $request, ClinicalNote $clinicalNote)
    {
        $data = $request->validate([
            'source_document_id' => ['nullable', 'exists:visit_documents,id'],
            'note_type' => ['sometimes', 'in:visit_conclusion,prescription_text,follow_up_note,general_note'],
            'content_text' => ['sometimes', 'string'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'updated_by' => ['nullable', 'exists:users,id'],
        ]);

        $clinicalNote->update($data);

        return response()->json(
            $clinicalNote->load(['visit', 'sourceDocument', 'creator', 'updater'])
        );
    }

    public function destroy(ClinicalNote $clinicalNote)
    {
        $clinicalNote->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}
