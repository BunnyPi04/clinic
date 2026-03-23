<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\VisitDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VisitDocumentController extends Controller
{
    public function index(Visit $visit)
    {
        return response()->json(
            $visit->documents()
                ->with('uploader')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:blood_test,urine_test,ultrasound,special_test,conclusion_prescription,other'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'captured_at' => ['nullable', 'date'],
            'uploaded_by' => ['nullable', 'exists:users,id'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $file = $request->file('file');

        $storedPath = $file->storeAs(
            'visit-documents/' . $visit->id,
            now()->format('YmdHis') . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension(),
            'public'
        );

        $document = VisitDocument::create([
            'visit_id' => $visit->id,
            'document_type' => $data['document_type'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'file_path' => $storedPath,
            'file_name' => basename($storedPath),
            'original_file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'captured_at' => $data['captured_at'] ?? null,
            'uploaded_by' => $data['uploaded_by'] ?? null,
            'ai_status' => 'uploaded',
            'review_status' => 'unreviewed',
        ]);

        return response()->json($document->load('uploader'), 201);
    }

    public function show(VisitDocument $visitDocument)
    {
        return response()->json(
            $visitDocument->load(['visit', 'uploader'])
        );
    }

    public function destroy(VisitDocument $visitDocument)
    {
        if ($visitDocument->file_path && Storage::disk('public')->exists($visitDocument->file_path)) {
            Storage::disk('public')->delete($visitDocument->file_path);
        }

        $visitDocument->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}
