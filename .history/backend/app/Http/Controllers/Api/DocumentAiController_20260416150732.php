<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisitDocument;
use Illuminate\Http\Request;

class DocumentAiController extends Controller
{
    public function show(VisitDocument $visitDocument)
    {
        return response()->json([
            'id' => $visitDocument->id,
            'document_type' => $visitDocument->document_type,
            'ai_status' => $visitDocument->ai_status,
            'review_status' => $visitDocument->review_status,
            'ai_raw_text' => $visitDocument->ai_raw_text,
            'ai_structured_data_json' => $visitDocument->ai_structured_data_json,
            'ai_error_message' => $visitDocument->ai_error_message,
        ]);
    }

    public function runMockExtraction(VisitDocument $visitDocument)
    {
        $visitDocument->update([
            'ai_status' => 'processing_ai',
            'ai_error_message' => null,
        ]);

        try {
            $mock = $this->buildMockStructuredData($visitDocument);

            $visitDocument->update([
                'ai_status' => 'ai_done',
                'ai_raw_text' => $mock['raw_text'],
                'ai_structured_data_json' => $mock['structured_data'],
            ]);

            return response()->json([
                'message' => 'Mock AI extraction completed',
                'document' => $visitDocument->fresh(),
            ]);
        } catch (\Throwable $e) {
            $visitDocument->update([
                'ai_status' => 'ai_failed',
                'ai_error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'AI extraction failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateExtraction(Request $request, VisitDocument $visitDocument)
    {
        $data = $request->validate([
            'ai_status' => ['nullable', 'in:uploaded,pending_ai,processing_ai,ai_done,ai_failed'],
            'review_status' => ['nullable', 'in:unreviewed,reviewed_ok,reviewed_corrected'],
            'ai_raw_text' => ['nullable', 'string'],
            'ai_structured_data_json' => ['nullable', 'array'],
            'ai_error_message' => ['nullable', 'string'],
        ]);

        $visitDocument->update($data);

        return response()->json([
            'message' => 'AI extraction data updated',
            'document' => $visitDocument->fresh(),
        ]);
    }

    public function markReviewed(Request $request, VisitDocument $visitDocument)
    {
        $data = $request->validate([
            'review_status' => ['required', 'in:reviewed_ok,reviewed_corrected'],
        ]);

        $visitDocument->update([
            'review_status' => $data['review_status'],
        ]);

        return response()->json([
            'message' => 'Document review status updated',
            'document' => $visitDocument->fresh(),
        ]);
    }

    private function buildMockStructuredData(VisitDocument $visitDocument): array
    {
        if ($visitDocument->document_type === 'blood_test') {
            return [
                'raw_text' => "Glucose 4.3 mmol/L\nCreatinin 74 umol/L\nCholesterol 5.03 mmol/L",
                'structured_data' => [
                    'document_type' => 'blood_test',
                    'panel_type' => 'blood',
                    'source_name' => $visitDocument->title ?: 'Phiếu xét nghiệm máu',
                    'tests' => [
                        [
                            'test_code' => 'GLU',
                            'test_name_original' => 'Glucose',
                            'value_text' => '4.3',
                            'value_number' => 4.3,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '4.1 - 5.9',
                            'flag' => 'normal',
                            'ai_confidence' => 0.95,
                            'sort_order' => 1,
                        ],
                        [
                            'test_code' => 'CREA',
                            'test_name_original' => 'Creatinin',
                            'value_text' => '74',
                            'value_number' => 74,
                            'unit' => 'µmol/L',
                            'reference_range_text' => '45 - 120',
                            'flag' => 'normal',
                            'ai_confidence' => 0.94,
                            'sort_order' => 2,
                        ],
                        [
                            'test_code' => 'CHOL',
                            'test_name_original' => 'Cholesterol',
                            'value_text' => '5.03',
                            'value_number' => 5.03,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '3.6 - 5.2',
                            'flag' => 'normal',
                            'ai_confidence' => 0.93,
                            'sort_order' => 3,
                        ],
                    ],
                ],
            ];
        }

        if ($visitDocument->document_type === 'urine_test') {
            return [
                'raw_text' => "Protein 0\npH 5.0\nGlucose 0\nProtein niệu 24h Âm tính",
                'structured_data' => [
                    'document_type' => 'urine_test',
                    'panel_type' => 'urine',
                    'source_name' => $visitDocument->title ?: 'Phiếu xét nghiệm nước tiểu',
                    'tests' => [
                        [
                            'test_code' => 'PH',
                            'test_name_original' => 'pH',
                            'value_text' => '5.0',
                            'value_number' => 5.0,
                            'unit' => null,
                            'reference_range_text' => '5.0 - 7.0',
                            'flag' => 'normal',
                            'ai_confidence' => 0.91,
                            'sort_order' => 1,
                        ],
                        [
                            'test_code' => 'GLU',
                            'test_name_original' => 'Glucose',
                            'value_text' => '0',
                            'value_number' => 0,
                            'unit' => 'mmol/L',
                            'reference_range_text' => 'Âm tính',
                            'flag' => 'normal',
                            'ai_confidence' => 0.90,
                            'sort_order' => 2,
                        ],
                    ],
                ],
            ];
        }

        if ($visitDocument->document_type === 'ultrasound') {
            return [
                'raw_text' => "Gan kích thước bình thường. Nhu mô đồng nhất. Chưa ghi nhận bất thường rõ.",
                'structured_data' => [
                    'document_type' => 'ultrasound',
                    'body_part' => 'Ổ bụng',
                    'findings_text' => 'Gan kích thước bình thường. Nhu mô đồng nhất.',
                    'conclusion_text' => 'Chưa ghi nhận bất thường rõ.',
                ],
            ];
        }

        return [
            'raw_text' => 'Nội dung OCR mẫu',
            'structured_data' => [
                'document_type' => $visitDocument->document_type,
                'content' => 'Dữ liệu AI mẫu',
            ],
        ];
    }
}
