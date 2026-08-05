<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabPanel;
use App\Models\VisitDocument;
use Illuminate\Http\Request;

class LabPanelController extends Controller
{
    public function index(VisitDocument $visitDocument)
    {
        return response()->json(
            $visitDocument->labPanels()
                ->with(['results', 'reviewer'])
                ->get()
        );
    }

    public function store(Request $request, VisitDocument $visitDocument)
    {
        $data = $request->validate([
            'panel_type' => ['required', 'in:blood,urine,special'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'sample_taken_at' => ['nullable', 'date'],
            'reviewed_by' => ['nullable', 'exists:users,id'],
            'reviewed_at' => ['nullable', 'date'],
        ]);

        $panel = LabPanel::create([
            'visit_document_id' => $visitDocument->id,
            'panel_type' => $data['panel_type'],
            'source_name' => $data['source_name'] ?? null,
            'sample_taken_at' => $data['sample_taken_at'] ?? null,
            'reviewed_by' => $data['reviewed_by'] ?? null,
            'reviewed_at' => $data['reviewed_at'] ?? null,
        ]);

        return response()->json($panel->load('results', 'reviewer'), 201);
    }

    public function show(LabPanel $labPanel)
    {
        return response()->json(
            $labPanel->load(['visitDocument', 'results', 'reviewer'])
        );
    }
}
