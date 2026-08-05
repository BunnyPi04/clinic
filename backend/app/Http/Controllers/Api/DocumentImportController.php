<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;
use App\Models\LabPanel;
use App\Models\LabResult;
use App\Models\LabTestDefinition;
use App\Models\UltrasoundReport;
use App\Models\VisitDocument;
use Illuminate\Http\Request;

class DocumentImportController extends Controller
{
    public function importStructuredData(VisitDocument $visitDocument)
    {
        $data = $visitDocument->ai_structured_data_json;

        if (empty($data) || !is_array($data)) {
            return response()->json([
                'message' => 'No structured AI data found for this document',
            ], 422);
        }

        $documentType = $data['document_type'] ?? $visitDocument->document_type;

        if (in_array($documentType, ['blood_test', 'urine_test', 'special_test'])) {
            return $this->importLabData($visitDocument, $data);
        }

        if ($documentType === 'ultrasound') {
            return $this->importUltrasoundData($visitDocument, $data);
        }

        if ($documentType === 'conclusion_prescription') {
            return $this->importClinicalNoteData($visitDocument, $data);
        }

        return response()->json([
            'message' => 'Unsupported document type for import',
            'document_type' => $documentType,
        ], 422);
    }

    private function importLabData(VisitDocument $visitDocument, array $data)
    {
        $panelType = $data['panel_type'] ?? match ($visitDocument->document_type) {
            'blood_test' => 'blood',
            'urine_test' => 'urine',
            default => 'special',
        };

        $panel = LabPanel::create([
            'visit_document_id' => $visitDocument->id,
            'panel_type' => $panelType,
            'source_name' => $data['source_name'] ?? $visitDocument->title,
            'sample_taken_at' => null,
        ]);

        $createdResults = [];

        foreach (($data['tests'] ?? []) as $index => $test) {
            $definition = null;

            if (!empty($test['test_code'])) {
                $definition = LabTestDefinition::query()
                    ->where('test_code', $test['test_code'])
                    ->first();
            }

            $createdResults[] = LabResult::create([
                'lab_panel_id' => $panel->id,
                'lab_test_definition_id' => $definition?->id,
                'test_code' => $test['test_code'] ?? null,
                'test_name_original' => $test['test_name_original'] ?? 'Unknown',
                'test_name_normalized' => $definition?->test_name_normalized ?? null,
                'value_text' => $test['value_text'] ?? null,
                'value_number' => $test['value_number'] ?? null,
                'unit' => $test['unit'] ?? $definition?->standard_unit,
                'standard_unit' => $definition?->standard_unit,
                'reference_range_text' => $test['reference_range_text'] ?? null,
                'reference_min' => $test['reference_min'] ?? null,
                'reference_max' => $test['reference_max'] ?? null,
                'reference_source' => 'manual',
                'flag' => $test['flag'] ?? 'unknown',
                'ai_confidence' => $test['ai_confidence'] ?? null,
                'price' => $test['price'] ?? $definition?->default_price ?? 0,
                'include_in_billing' => $test['include_in_billing'] ?? $definition?->default_include_in_billing ?? true,
                'billing_note' => null,
                'is_manually_corrected' => false,
                'sort_order' => $test['sort_order'] ?? ($index + 1),
            ]);
        }

        return response()->json([
            'message' => 'Lab structured data imported successfully',
            'panel' => $panel->load('results'),
            'created_results_count' => count($createdResults),
        ]);
    }

    private function importUltrasoundData(VisitDocument $visitDocument, array $data)
    {
        $report = UltrasoundReport::create([
            'visit_document_id' => $visitDocument->id,
            'body_part' => $data['body_part'] ?? null,
            'findings_text' => $data['findings_text'] ?? null,
            'conclusion_text' => $data['conclusion_text'] ?? null,
            'is_manually_corrected' => false,
        ]);

        return response()->json([
            'message' => 'Ultrasound structured data imported successfully',
            'report' => $report,
        ]);
    }

    private function importClinicalNoteData(VisitDocument $visitDocument, array $data)
    {
        $visit = $visitDocument->visit;

        $notes = [];

        if (!empty($data['clinical_summary'])) {
            $notes[] = ClinicalNote::create([
                'visit_id' => $visit->id,
                'source_document_id' => $visitDocument->id,
                'note_type' => 'visit_conclusion',
                'content_text' => $data['clinical_summary'],
                'is_manually_corrected' => false,
            ]);
        }

        if (!empty($data['prescription_text'])) {
            $notes[] = ClinicalNote::create([
                'visit_id' => $visit->id,
                'source_document_id' => $visitDocument->id,
                'note_type' => 'prescription_text',
                'content_text' => $data['prescription_text'],
                'is_manually_corrected' => false,
            ]);
        }

        if (!empty($data['follow_up_date_text'])) {
            $notes[] = ClinicalNote::create([
                'visit_id' => $visit->id,
                'source_document_id' => $visitDocument->id,
                'note_type' => 'follow_up_note',
                'content_text' => $data['follow_up_date_text'],
                'is_manually_corrected' => false,
            ]);
        }

        return response()->json([
            'message' => 'Clinical structured data imported successfully',
            'created_notes_count' => count($notes),
            'notes' => $notes,
        ]);
    }
}
