<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UltrasoundReport;
use App\Models\VisitDocument;
use Illuminate\Http\Request;

class UltrasoundReportController extends Controller
{
    public function index(VisitDocument $visitDocument)
    {
        return response()->json(
            $visitDocument->ultrasoundReports()
                ->with(['creator', 'updater'])
                ->latest()
                ->get()
        );
    }

    public function store(Request $request, VisitDocument $visitDocument)
    {
        $data = $request->validate([
            'body_part' => ['nullable', 'string', 'max:255'],
            'findings_text' => ['nullable', 'string'],
            'conclusion_text' => ['nullable', 'string'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'created_by' => ['nullable', 'exists:users,id'],
            'updated_by' => ['nullable', 'exists:users,id'],
        ]);

        $report = UltrasoundReport::create([
            'visit_document_id' => $visitDocument->id,
            'body_part' => $data['body_part'] ?? null,
            'findings_text' => $data['findings_text'] ?? null,
            'conclusion_text' => $data['conclusion_text'] ?? null,
            'is_manually_corrected' => (bool) ($data['is_manually_corrected'] ?? false),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ]);

        return response()->json($report->load(['creator', 'updater']), 201);
    }

    public function show(UltrasoundReport $ultrasoundReport)
    {
        return response()->json(
            $ultrasoundReport->load(['visitDocument', 'creator', 'updater'])
        );
    }

    public function update(Request $request, UltrasoundReport $ultrasoundReport)
    {
        $data = $request->validate([
            'body_part' => ['nullable', 'string', 'max:255'],
            'findings_text' => ['nullable', 'string'],
            'conclusion_text' => ['nullable', 'string'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'updated_by' => ['nullable', 'exists:users,id'],
        ]);

        $ultrasoundReport->update($data);

        return response()->json(
            $ultrasoundReport->load(['visitDocument', 'creator', 'updater'])
        );
    }

    public function destroy(UltrasoundReport $ultrasoundReport)
    {
        $ultrasoundReport->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}
