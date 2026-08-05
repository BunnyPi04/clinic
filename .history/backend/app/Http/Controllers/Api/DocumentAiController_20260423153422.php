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
                'raw_text' => "Ure 3.8 mmol/L
        Glucose 4.3 mmol/L
        Creatinin 74 µmol/L
        Acid Uric 396 µmol/L
        Protein T.P 67.0 g/L
        Albumin 34.5 g/L
        Cholesterol 5.03 mmol/L
        Triglycerid 1.83 mmol/L
        Calci TP 2.26 mmol/L
        Calci ion 1.21 mmol/L
        AST (GOT) 21 U/L
        ALT (GPT) 12 U/L
        Na+ 139 mmol/L
        K+ 3.8 mmol/L
        Cl- 103 mmol/L",
                'structured_data' => [
                    'document_type' => 'blood_test',
                    'panel_type' => 'blood',
                    'source_name' => $visitDocument->title ?: 'Phiếu xét nghiệm hóa sinh',
                    'tests' => [
                        [
                            'test_code' => 'URE',
                            'test_name_original' => 'Ure',
                            'value_text' => '3.8',
                            'value_number' => 3.8,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '2.5 - 7.5',
                            'reference_min' => 2.5,
                            'reference_max' => 7.5,
                            'flag' => 'normal',
                            'ai_confidence' => 0.98,
                            'sort_order' => 1,
                        ],
                        [
                            'test_code' => 'GLU',
                            'test_name_original' => 'Glucose',
                            'value_text' => '4.3',
                            'value_number' => 4.3,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '4.1 - 5.9',
                            'reference_min' => 4.1,
                            'reference_max' => 5.9,
                            'flag' => 'normal',
                            'ai_confidence' => 0.98,
                            'sort_order' => 2,
                        ],
                        [
                            'test_code' => 'CREA',
                            'test_name_original' => 'Creatinin',
                            'value_text' => '74',
                            'value_number' => 74,
                            'unit' => 'µmol/L',
                            'reference_range_text' => '45 - 120',
                            'reference_min' => 45,
                            'reference_max' => 120,
                            'flag' => 'normal',
                            'ai_confidence' => 0.98,
                            'sort_order' => 3,
                        ],
                        [
                            'test_code' => 'URIC',
                            'test_name_original' => 'Acid Uric',
                            'value_text' => '396',
                            'value_number' => 396,
                            'unit' => 'µmol/L',
                            'reference_range_text' => '120 - 380',
                            'reference_min' => 120,
                            'reference_max' => 380,
                            'flag' => 'high',
                            'ai_confidence' => 0.98,
                            'sort_order' => 4,
                        ],
                        [
                            'test_code' => 'TP',
                            'test_name_original' => 'Protein T.P',
                            'value_text' => '67.0',
                            'value_number' => 67.0,
                            'unit' => 'g/L',
                            'reference_range_text' => '60 - 82',
                            'reference_min' => 60,
                            'reference_max' => 82,
                            'flag' => 'normal',
                            'ai_confidence' => 0.97,
                            'sort_order' => 5,
                        ],
                        [
                            'test_code' => 'ALB',
                            'test_name_original' => 'Albumin',
                            'value_text' => '34.5',
                            'value_number' => 34.5,
                            'unit' => 'g/L',
                            'reference_range_text' => '35 - 52',
                            'reference_min' => 35,
                            'reference_max' => 52,
                            'flag' => 'low',
                            'ai_confidence' => 0.97,
                            'sort_order' => 6,
                        ],
                        [
                            'test_code' => 'CHOL',
                            'test_name_original' => 'Cholesterol',
                            'value_text' => '5.03',
                            'value_number' => 5.03,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '3.6 - 5.2',
                            'reference_min' => 3.6,
                            'reference_max' => 5.2,
                            'flag' => 'normal',
                            'ai_confidence' => 0.97,
                            'sort_order' => 7,
                        ],
                        [
                            'test_code' => 'TRIG',
                            'test_name_original' => 'Triglycerid',
                            'value_text' => '1.83',
                            'value_number' => 1.83,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '0.46 - 1.88',
                            'reference_min' => 0.46,
                            'reference_max' => 1.88,
                            'flag' => 'normal',
                            'ai_confidence' => 0.97,
                            'sort_order' => 8,
                        ],
                        [
                            'test_code' => 'CA_TP',
                            'test_name_original' => 'Calci TP',
                            'value_text' => '2.26',
                            'value_number' => 2.26,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '2.2 - 2.65',
                            'reference_min' => 2.2,
                            'reference_max' => 2.65,
                            'flag' => 'normal',
                            'ai_confidence' => 0.95,
                            'sort_order' => 9,
                        ],
                        [
                            'test_code' => 'CA_ION',
                            'test_name_original' => 'Calci ion',
                            'value_text' => '1.21',
                            'value_number' => 1.21,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '1.10 - 1.35',
                            'reference_min' => 1.10,
                            'reference_max' => 1.35,
                            'flag' => 'normal',
                            'ai_confidence' => 0.95,
                            'sort_order' => 10,
                        ],
                        [
                            'test_code' => 'AST',
                            'test_name_original' => 'AST (GOT)',
                            'value_text' => '21',
                            'value_number' => 21,
                            'unit' => 'U/L',
                            'reference_range_text' => '<= 37',
                            'reference_min' => null,
                            'reference_max' => 37,
                            'flag' => 'normal',
                            'ai_confidence' => 0.96,
                            'sort_order' => 11,
                        ],
                        [
                            'test_code' => 'ALT',
                            'test_name_original' => 'ALT (GPT)',
                            'value_text' => '12',
                            'value_number' => 12,
                            'unit' => 'U/L',
                            'reference_range_text' => '< 40',
                            'reference_min' => null,
                            'reference_max' => 40,
                            'flag' => 'normal',
                            'ai_confidence' => 0.96,
                            'sort_order' => 12,
                        ],
                        [
                            'test_code' => 'NA',
                            'test_name_original' => 'Na+',
                            'value_text' => '139',
                            'value_number' => 139,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '135 - 145',
                            'reference_min' => 135,
                            'reference_max' => 145,
                            'flag' => 'normal',
                            'ai_confidence' => 0.97,
                            'sort_order' => 13,
                        ],
                        [
                            'test_code' => 'K',
                            'test_name_original' => 'K+',
                            'value_text' => '3.8',
                            'value_number' => 3.8,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '3.5 - 5.0',
                            'reference_min' => 3.5,
                            'reference_max' => 5.0,
                            'flag' => 'normal',
                            'ai_confidence' => 0.97,
                            'sort_order' => 14,
                        ],
                        [
                            'test_code' => 'CL',
                            'test_name_original' => 'Cl-',
                            'value_text' => '103',
                            'value_number' => 103,
                            'unit' => 'mmol/L',
                            'reference_range_text' => '98 - 106',
                            'reference_min' => 98,
                            'reference_max' => 106,
                            'flag' => 'normal',
                            'ai_confidence' => 0.97,
                            'sort_order' => 15,
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
