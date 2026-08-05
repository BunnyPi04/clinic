<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabPanel;
use App\Models\LabResult;
use Illuminate\Http\Request;

class LabResultController extends Controller
{
    public function store(Request $request, LabPanel $labPanel)
    {
        $data = $request->validate([
            'test_code' => ['nullable', 'string', 'max:255'],
            'test_name_original' => ['required', 'string', 'max:255'],
            'test_name_normalized' => ['nullable', 'string', 'max:255'],
            'value_text' => ['nullable', 'string', 'max:255'],
            'value_number' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reference_range_text' => ['nullable', 'string', 'max:255'],
            'reference_min' => ['nullable', 'numeric'],
            'reference_max' => ['nullable', 'numeric'],
            'flag' => ['nullable', 'in:low,normal,high,abnormal,unknown'],
            'ai_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = LabResult::create([
            'lab_panel_id' => $labPanel->id,
            'test_code' => $data['test_code'] ?? null,
            'test_name_original' => $data['test_name_original'],
            'test_name_normalized' => $data['test_name_normalized'] ?? null,
            'value_text' => $data['value_text'] ?? null,
            'value_number' => $data['value_number'] ?? null,
            'unit' => $data['unit'] ?? null,
            'reference_range_text' => $data['reference_range_text'] ?? null,
            'reference_min' => $data['reference_min'] ?? null,
            'reference_max' => $data['reference_max'] ?? null,
            'flag' => $data['flag'] ?? 'unknown',
            'ai_confidence' => $data['ai_confidence'] ?? null,
            'is_manually_corrected' => (bool) ($data['is_manually_corrected'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return response()->json($result, 201);
    }

    public function update(Request $request, LabResult $labResult)
    {
        $data = $request->validate([
            'test_code' => ['nullable', 'string', 'max:255'],
            'test_name_original' => ['sometimes', 'string', 'max:255'],
            'test_name_normalized' => ['nullable', 'string', 'max:255'],
            'value_text' => ['nullable', 'string', 'max:255'],
            'value_number' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'reference_range_text' => ['nullable', 'string', 'max:255'],
            'reference_min' => ['nullable', 'numeric'],
            'reference_max' => ['nullable', 'numeric'],
            'flag' => ['nullable', 'in:low,normal,high,abnormal,unknown'],
            'ai_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $labResult->update($data);

        return response()->json($labResult);
    }

    public function destroy(LabResult $labResult)
    {
        $labResult->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}
