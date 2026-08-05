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
        $title = mb_strtolower((string) ($visitDocument->title ?? ''));

        $isBiochemistry =
            str_contains($title, 'hóa sinh') ||
            str_contains($title, 'hoa sinh');

        $isHematology =
            str_contains($title, 'huyết học') ||
            str_contains($title, 'huyet hoc') ||
            str_contains($title, 'công thức máu') ||
            str_contains($title, 'cong thuc mau');

        if ($visitDocument->document_type === 'blood_test' && $isBiochemistry) {
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
                    'sample_group' => 'biochemistry',
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

        if ($visitDocument->document_type === 'blood_test' && $isHematology) {
            return [
                'raw_text' => "WBC 7.4
    RBC 4.67
    HGB 134
    HCT 40.3
    MCV 86.3
    MCH 28.7
    MCHC 333
    PLT 222
    LYMPH% 39.8
    MONO% 10.0
    NEUT% 50.2
    LYMPH# 3.0
    MONO# 0.7
    NEUT# 3.7
    RDW-CV 11.3
    PCT 0.18
    MPV 8.3
    PDW 18.6",
                'structured_data' => [
                    'document_type' => 'blood_test',
                    'panel_type' => 'blood',
                    'sample_group' => 'hematology',
                    'source_name' => $visitDocument->title ?: 'Phiếu xét nghiệm huyết học',
                    'tests' => [
                        ['test_code' => 'WBC', 'test_name_original' => 'Số lượng bạch cầu (WBC)', 'value_text' => '7.4', 'value_number' => 7.4, 'unit' => '10^9/L', 'reference_range_text' => '4.0 - 10', 'reference_min' => 4.0, 'reference_max' => 10.0, 'flag' => 'normal', 'ai_confidence' => 0.98, 'sort_order' => 1],
                        ['test_code' => 'RBC', 'test_name_original' => 'Số lượng hồng cầu (RBC)', 'value_text' => '4.67', 'value_number' => 4.67, 'unit' => '10^12/L', 'reference_range_text' => '3.50 - 5.60', 'reference_min' => 3.50, 'reference_max' => 5.60, 'flag' => 'normal', 'ai_confidence' => 0.98, 'sort_order' => 2],
                        ['test_code' => 'HGB', 'test_name_original' => 'Huyết sắc tố (HGB)', 'value_text' => '134', 'value_number' => 134, 'unit' => 'g/L', 'reference_range_text' => '110 - 160', 'reference_min' => 110, 'reference_max' => 160, 'flag' => 'normal', 'ai_confidence' => 0.98, 'sort_order' => 3],
                        ['test_code' => 'HCT', 'test_name_original' => 'Thể tích khối hồng cầu (HCT)', 'value_text' => '40.3', 'value_number' => 40.3, 'unit' => '%', 'reference_range_text' => '35.0 - 50.0', 'reference_min' => 35.0, 'reference_max' => 50.0, 'flag' => 'normal', 'ai_confidence' => 0.97, 'sort_order' => 4],
                        ['test_code' => 'MCV', 'test_name_original' => 'Thể tích trung bình hồng cầu (MCV)', 'value_text' => '86.3', 'value_number' => 86.3, 'unit' => 'fL', 'reference_range_text' => '80.0 - 97.0', 'reference_min' => 80.0, 'reference_max' => 97.0, 'flag' => 'normal', 'ai_confidence' => 0.97, 'sort_order' => 5],
                        ['test_code' => 'MCH', 'test_name_original' => 'Lượng HST trung bình hồng cầu (MCH)', 'value_text' => '28.7', 'value_number' => 28.7, 'unit' => 'pg', 'reference_range_text' => '26.0 - 32.0', 'reference_min' => 26.0, 'reference_max' => 32.0, 'flag' => 'normal', 'ai_confidence' => 0.97, 'sort_order' => 6],
                        ['test_code' => 'MCHC', 'test_name_original' => 'Nồng độ HST trung bình hồng cầu (MCHC)', 'value_text' => '333', 'value_number' => 333, 'unit' => 'g/L', 'reference_range_text' => '310 - 360', 'reference_min' => 310, 'reference_max' => 360, 'flag' => 'normal', 'ai_confidence' => 0.97, 'sort_order' => 7],
                        ['test_code' => 'PLT', 'test_name_original' => 'Số lượng tiểu cầu (PLT)', 'value_text' => '222', 'value_number' => 222, 'unit' => '10^3/µL', 'reference_range_text' => '150 - 450', 'reference_min' => 150, 'reference_max' => 450, 'flag' => 'normal', 'ai_confidence' => 0.97, 'sort_order' => 8],
                        ['test_code' => 'LYMPH_PERCENT', 'test_name_original' => 'Tỉ lệ bạch cầu lympho (LYMPH%)', 'value_text' => '39.8', 'value_number' => 39.8, 'unit' => '%', 'reference_range_text' => '19.0 - 48.0', 'reference_min' => 19.0, 'reference_max' => 48.0, 'flag' => 'normal', 'ai_confidence' => 0.96, 'sort_order' => 9],
                        ['test_code' => 'MONO_PERCENT', 'test_name_original' => 'Tỉ lệ bạch cầu mono (MONO%)', 'value_text' => '10.0', 'value_number' => 10.0, 'unit' => '%', 'reference_range_text' => '0.1 - 9.0', 'reference_min' => 0.1, 'reference_max' => 9.0, 'flag' => 'high', 'ai_confidence' => 0.96, 'sort_order' => 10],
                        ['test_code' => 'NEUT_PERCENT', 'test_name_original' => 'Tỉ lệ bạch cầu hạt trung tính (NEUT%)', 'value_text' => '50.2', 'value_number' => 50.2, 'unit' => '%', 'reference_range_text' => '40.0 - 74.0', 'reference_min' => 40.0, 'reference_max' => 74.0, 'flag' => 'normal', 'ai_confidence' => 0.96, 'sort_order' => 11],
                        ['test_code' => 'LYMPH_ABS', 'test_name_original' => 'Số lượng bạch cầu lympho (LYMPH#)', 'value_text' => '3.0', 'value_number' => 3.0, 'unit' => '10^9/L', 'reference_range_text' => '1.0 - 4.0', 'reference_min' => 1.0, 'reference_max' => 4.0, 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 12],
                        ['test_code' => 'MONO_ABS', 'test_name_original' => 'Số lượng bạch cầu mono (MONO#)', 'value_text' => '0.7', 'value_number' => 0.7, 'unit' => '10^9/L', 'reference_range_text' => '0.0 - 1.0', 'reference_min' => 0.0, 'reference_max' => 1.0, 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 13],
                        ['test_code' => 'NEUT_ABS', 'test_name_original' => 'Số lượng bạch cầu hạt trung tính (NEUT#)', 'value_text' => '3.7', 'value_number' => 3.7, 'unit' => '10^9/L', 'reference_range_text' => '1.7 - 7.0', 'reference_min' => 1.7, 'reference_max' => 7.0, 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 14],
                        ['test_code' => 'RDW_CV', 'test_name_original' => 'Dải phân bố kích thước hồng cầu (RDW-CV)', 'value_text' => '11.3', 'value_number' => 11.3, 'unit' => '%', 'reference_range_text' => '11.0 - 15.7', 'reference_min' => 11.0, 'reference_max' => 15.7, 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 15],
                        ['test_code' => 'PCT', 'test_name_original' => 'Thể tích khối tiểu cầu (PCT)', 'value_text' => '0.18', 'value_number' => 0.18, 'unit' => '%', 'reference_range_text' => '0.10 - 0.50', 'reference_min' => 0.10, 'reference_max' => 0.50, 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 16],
                        ['test_code' => 'MPV', 'test_name_original' => 'Thể tích trung bình tiểu cầu (MPV)', 'value_text' => '8.3', 'value_number' => 8.3, 'unit' => 'fL', 'reference_range_text' => '6.5 - 11.0', 'reference_min' => 6.5, 'reference_max' => 11.0, 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 17],
                        ['test_code' => 'PDW', 'test_name_original' => 'Dải phân bố kích thước tiểu cầu (PDW)', 'value_text' => '18.6', 'value_number' => 18.6, 'unit' => '%', 'reference_range_text' => '6.0 - 18.0', 'reference_min' => 6.0, 'reference_max' => 18.0, 'flag' => 'high', 'ai_confidence' => 0.95, 'sort_order' => 18],
                    ],
                ],
            ];
        }

        if ($visitDocument->document_type === 'urine_test') {
            return [
                'raw_text' => "Bạch cầu 0
    Nitrit 0
    Urobilinogen 3.2
    Protein 0
    pH 5.0
    Hồng cầu 0
    SG 1.015
    Ketonic 0
    Bilirubin 0
    Glucose 0
    Protein niệu (nước tiểu 24h) ÂM TÍNH",
                'structured_data' => [
                    'document_type' => 'urine_test',
                    'panel_type' => 'urine',
                    'sample_group' => 'urine',
                    'source_name' => $visitDocument->title ?: 'Phiếu xét nghiệm nước tiểu',
                    'tests' => [
                        ['test_code' => 'LEU', 'test_name_original' => 'Bạch cầu', 'value_text' => '0', 'value_number' => 0, 'unit' => 'Leu/ul', 'reference_range_text' => 'Âm tính', 'flag' => 'normal', 'ai_confidence' => 0.97, 'sort_order' => 1],
                        ['test_code' => 'NIT', 'test_name_original' => 'Nitrit', 'value_text' => '0', 'value_number' => 0, 'unit' => null, 'reference_range_text' => 'Âm tính', 'flag' => 'normal', 'ai_confidence' => 0.97, 'sort_order' => 2],
                        ['test_code' => 'URO', 'test_name_original' => 'Urobilinogen', 'value_text' => '3.2', 'value_number' => 3.2, 'unit' => 'umol/L', 'reference_range_text' => '3.2 - 16', 'reference_min' => 3.2, 'reference_max' => 16, 'flag' => 'normal', 'ai_confidence' => 0.96, 'sort_order' => 3],
                        ['test_code' => 'PRO', 'test_name_original' => 'Protein', 'value_text' => '0', 'value_number' => 0, 'unit' => 'g/L', 'reference_range_text' => 'Âm tính', 'flag' => 'normal', 'ai_confidence' => 0.96, 'sort_order' => 4],
                        ['test_code' => 'PH', 'test_name_original' => 'pH', 'value_text' => '5.0', 'value_number' => 5.0, 'unit' => null, 'reference_range_text' => '5.0 - 7.0', 'reference_min' => 5.0, 'reference_max' => 7.0, 'flag' => 'normal', 'ai_confidence' => 0.96, 'sort_order' => 5],
                        ['test_code' => 'RBC_URINE', 'test_name_original' => 'Hồng cầu', 'value_text' => '0', 'value_number' => 0, 'unit' => 'Ery/ul', 'reference_range_text' => 'Âm tính', 'flag' => 'normal', 'ai_confidence' => 0.96, 'sort_order' => 6],
                        ['test_code' => 'SG', 'test_name_original' => 'SG', 'value_text' => '1.015', 'value_number' => 1.015, 'unit' => null, 'reference_range_text' => '1.003 - 1.035', 'reference_min' => 1.003, 'reference_max' => 1.035, 'flag' => 'normal', 'ai_confidence' => 0.96, 'sort_order' => 7],
                        ['test_code' => 'KET', 'test_name_original' => 'Ketonic', 'value_text' => '0', 'value_number' => 0, 'unit' => 'mmol/L', 'reference_range_text' => 'Âm tính', 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 8],
                        ['test_code' => 'BIL', 'test_name_original' => 'Bilirubin', 'value_text' => '0', 'value_number' => 0, 'unit' => 'umol/L', 'reference_range_text' => 'Âm tính', 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 9],
                        ['test_code' => 'GLU_URINE', 'test_name_original' => 'Glucose', 'value_text' => '0', 'value_number' => 0, 'unit' => 'mmol/L', 'reference_range_text' => 'Âm tính', 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 10],
                        ['test_code' => 'PRO_24H', 'test_name_original' => 'Protein niệu (nước tiểu 24h)', 'value_text' => 'ÂM TÍNH', 'value_number' => null, 'unit' => 'g/L', 'reference_range_text' => 'ÂM TÍNH', 'flag' => 'normal', 'ai_confidence' => 0.95, 'sort_order' => 11],
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
