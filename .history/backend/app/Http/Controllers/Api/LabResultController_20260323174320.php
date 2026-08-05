<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabPanel;
use App\Models\LabResult;
use App\Models\LabTestDefinition;
use Illuminate\Http\Request;

class LabResultController extends Controller
{
    public function store(Request $request, LabPanel $labPanel)
    {
        $data = $request->validate([
            'lab_test_definition_id' => ['nullable', 'exists:lab_test_definitions,id'],
            'test_code' => ['nullable', 'string', 'max:255'],
            'test_name_original' => ['nullable', 'string', 'max:255'],
            'test_name_normalized' => ['nullable', 'string', 'max:255'],
            'value_text' => ['nullable', 'string', 'max:255'],
            'value_number' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'standard_unit' => ['nullable', 'string', 'max:50'],
            'reference_range_text' => ['nullable', 'string', 'max:255'],
            'reference_min' => ['nullable', 'numeric'],
            'reference_max' => ['nullable', 'numeric'],
            'flag' => ['nullable', 'in:low,normal,high,abnormal,unknown'],
            'ai_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'include_in_billing' => ['nullable', 'boolean'],
            'billing_note' => ['nullable', 'string', 'max:255'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $definition = null;

        if (! empty($data['lab_test_definition_id'])) {
            $definition = LabTestDefinition::find($data['lab_test_definition_id']);
        } elseif (! empty($data['test_code'])) {
            $definition = LabTestDefinition::query()
                ->where('test_code', $data['test_code'])
                ->first();
        }

        $patientGender = optional(
            optional(
                optional($labPanel->visitDocument)->visit
            )->patient
        )->gender;

        $referenceMin = $data['reference_min'] ?? null;
        $referenceMax = $data['reference_max'] ?? null;
        $referenceText = $data['reference_range_text'] ?? null;
        $unit = $data['unit'] ?? null;
        $standardUnit = $data['standard_unit'] ?? null;
        $price = $data['price'] ?? null;
        $includeInBilling = $data['include_in_billing'] ?? null;
        $referenceSource = 'manual';

        $testCode = $data['test_code'] ?? null;
        $testNameOriginal = $data['test_name_original'] ?? null;
        $testNameNormalized = $data['test_name_normalized'] ?? null;

        if ($definition) {
            $testCode = $testCode ?? $definition->test_code;
            $testNameOriginal = $testNameOriginal ?? $definition->test_name;
            $testNameNormalized = $testNameNormalized ?? $definition->test_name_normalized;
            $unit = $unit ?? $definition->standard_unit;
            $standardUnit = $standardUnit ?? $definition->standard_unit;
            $price = $price ?? $definition->default_price;
            $includeInBilling = $includeInBilling ?? $definition->default_include_in_billing;

            if ($referenceMin === null && $referenceMax === null && $referenceText === null) {
                if ($patientGender === 'male') {
                    $referenceMin = $definition->male_reference_min;
                    $referenceMax = $definition->male_reference_max;
                    $referenceText = $definition->male_reference_text;
                } elseif ($patientGender === 'female') {
                    $referenceMin = $definition->female_reference_min;
                    $referenceMax = $definition->female_reference_max;
                    $referenceText = $definition->female_reference_text;
                } else {
                    $referenceMin = $definition->female_reference_min ?? $definition->male_reference_min;
                    $referenceMax = $definition->female_reference_max ?? $definition->male_reference_max;
                    $referenceText = $definition->female_reference_text ?? $definition->male_reference_text;
                }

                $referenceSource = 'default_definition';
            }
        }

        if (empty($testNameOriginal)) {
            return response()->json([
                'message' => 'test_name_original is required when no matching lab test definition is found.'
            ], 422);
        }

        $result = LabResult::create([
            'lab_panel_id' => $labPanel->id,
            'lab_test_definition_id' => $definition?->id,
            'test_code' => $testCode,
            'test_name_original' => $testNameOriginal,
            'test_name_normalized' => $testNameNormalized,
            'value_text' => $data['value_text'] ?? null,
            'value_number' => $data['value_number'] ?? null,
            'unit' => $unit,
            'standard_unit' => $standardUnit,
            'reference_range_text' => $referenceText,
            'reference_min' => $referenceMin,
            'reference_max' => $referenceMax,
            'reference_source' => $referenceSource,
            'flag' => $data['flag'] ?? 'unknown',
            'ai_confidence' => $data['ai_confidence'] ?? null,
            'price' => $price ?? 0,
            'include_in_billing' => $includeInBilling ?? true,
            'billing_note' => $data['billing_note'] ?? null,
            'is_manually_corrected' => (bool) ($data['is_manually_corrected'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return response()->json($result->load('definition'), 201);
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
            'standard_unit' => ['nullable', 'string', 'max:50'],
            'reference_range_text' => ['nullable', 'string', 'max:255'],
            'reference_min' => ['nullable', 'numeric'],
            'reference_max' => ['nullable', 'numeric'],
            'reference_source' => ['nullable', 'in:manual,default_definition'],
            'flag' => ['nullable', 'in:low,normal,high,abnormal,unknown'],
            'ai_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'include_in_billing' => ['nullable', 'boolean'],
            'billing_note' => ['nullable', 'string', 'max:255'],
            'is_manually_corrected' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $labResult->update($data);

        return response()->json($labResult->load('definition'));
    }

    public function destroy(LabResult $labResult)
    {
        $labResult->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}
