<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ServiceCatalogController extends Controller
{
    private const CODE_PREFIXES = [
        'CS',
        'BT',
        'UT',
        'BL',
        'US',
        'ET',
        'OT',
    ];

    private const RANDOM_CODE_LENGTH = 10;

    private const MAX_CODE_GENERATION_ATTEMPTS = 20;

    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $query = ServiceCatalog::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('service_category', 'like', "%{$keyword}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(30));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code_prefix' => [
                'required',
                'string',
                Rule::in([
                    'CS',
                    'BT',
                    'UT',
                    'BL',
                    'US',
                    'ET',
                    'OT',
                ]),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'service_type' => [
                'required',
                'string',
                Rule::in([
                    'consultation',
                    'lab_test',
                    'ultrasound',
                    'external_test',
                    'other',
                ]),
            ],

            'service_category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'default_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                'string',
                Rule::in([
                    'active',
                    'suspended',
                    'discontinued',
                ]),
            ],

            'is_highlighted_default' => [
                'required',
                'boolean',
            ],

            'display_on_patient_receipt_default' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $serviceCatalog = DB::transaction(function () use ($data) {
            $prefix = $data['code_prefix'];

            unset($data['code_prefix']);

            $data['code'] = $this->generateServiceCode($prefix);

            /*
            * Giữ tương thích với cột is_active cũ.
            */
            $data['is_active'] = $data['status'] === 'active';

            return ServiceCatalog::query()->create($data);
        });

        return response()->json([
            'message' => 'Thêm dịch vụ thành công.',
            'data' => $serviceCatalog,
        ], 201);
    }

    public function show(ServiceCatalog $serviceCatalog)
    {
        return response()->json($serviceCatalog);
    }

    public function update(
        Request $request,
        ServiceCatalog $serviceCatalog
    ) {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'service_type' => [
                'required',
                'string',
                Rule::in([
                    'consultation',
                    'lab_test',
                    'ultrasound',
                    'external_test',
                    'other',
                ]),
            ],

            'service_category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'default_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                'string',
                Rule::in([
                    'active',
                    'suspended',
                    'discontinued',
                ]),
            ],

            'is_highlighted_default' => [
                'required',
                'boolean',
            ],

            'display_on_patient_receipt_default' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $data['is_active'] = $data['status'] === 'active';

        $serviceCatalog->update($data);

        return response()->json([
            'message' => 'Cập nhật dịch vụ thành công.',
            'data' => $serviceCatalog->fresh(),
        ]);
    }

    private function validateData(
        Request $request,
        ?int $serviceCatalogId = null
    ): array {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'service_type' => [
                'required',
                Rule::in([
                    'consultation',
                    'lab_test',
                    'ultrasound',
                    'external_test',
                    'other',
                ]),
            ],
            'service_category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'default_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                Rule::in([
                    ServiceCatalog::STATUS_ACTIVE,
                    ServiceCatalog::STATUS_SUSPENDED,
                    ServiceCatalog::STATUS_DISCONTINUED,
                ]),
            ],
            'is_highlighted_default' => [
                'nullable',
                'boolean',
            ],
            'display_on_patient_receipt_default' => [
                'nullable',
                'boolean',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];

        if ($serviceCatalogId === null) {
            $rules['code_prefix'] = [
                'required',
                'string',
                Rule::in(self::CODE_PREFIXES),
            ];
        } else {
            /*
            * Không cho phép thay mã khi update.
            */
            $rules['code'] = [
                'sometimes',
                'string',
                Rule::unique('service_catalogs', 'code')
                    ->ignore($serviceCatalogId),
            ];
        }

        return $request->validate($rules);
    }

    private function generateUniqueServiceCode(string $prefix): string
    {
        do {
            $random = Str::random(self::RANDOM_CODE_LENGTH);
            $code = "{$prefix}_{$random}";
        } while (
            ServiceCatalog::query()
                ->where('code', $code)
                ->exists()
        );

        return $code;
    }

    private function isDuplicateCodeException(
        QueryException $exception
    ): bool {
        /*
        * MySQL thường dùng SQLSTATE 23000 cho unique violation.
        * Ta kiểm tra thêm nội dung liên quan tới cột code để tránh
        * nuốt nhầm lỗi unique ở cột khác.
        */
        $sqlState = $exception->errorInfo[0] ?? null;
        $message = mb_strtolower($exception->getMessage());

        return $sqlState === '23000'
            && str_contains($message, 'code');
    }

    private function generateServiceCode(string $prefix): string
    {
        do {
            $sequenceId = DB::table('service_code_sequences')
                ->insertGetId([
                    'created_at' => now(),
                ]);

            if ($sequenceId > 9999999999) {
                throw new RuntimeException(
                    'Dãy số mã dịch vụ đã vượt quá giới hạn 10 chữ số.'
                );
            }

            $numericCode = str_pad(
                (string) $sequenceId,
                10,
                '0',
                STR_PAD_LEFT
            );

            $serviceCode = $prefix . $numericCode;
        } while (
            ServiceCatalog::query()
                ->where('code', $serviceCode)
                ->exists()
        );

        return $serviceCode;
    }
}
