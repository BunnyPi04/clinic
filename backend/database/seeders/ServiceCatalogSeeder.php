<?php

namespace Database\Seeders;

use App\Models\ServiceCatalog;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'code' => 'CONSULTATION',
                'name' => 'Phí khám bác sĩ',
                'service_type' => 'consultation',
                'service_category' => 'exam',
                'default_price' => 0,
                'is_highlighted_default' => false,
                'display_on_patient_receipt_default' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'LAB_BIOCHEMISTRY',
                'name' => 'Xét nghiệm hóa sinh',
                'service_type' => 'lab_test',
                'service_category' => 'biochemistry',
                'default_price' => 0,
                'is_highlighted_default' => false,
                'display_on_patient_receipt_default' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'LAB_HEMATOLOGY',
                'name' => 'Xét nghiệm công thức máu',
                'service_type' => 'lab_test',
                'service_category' => 'hematology',
                'default_price' => 0,
                'is_highlighted_default' => false,
                'display_on_patient_receipt_default' => true,
                'sort_order' => 30,
            ],
            [
                'code' => 'LAB_URINE',
                'name' => 'Xét nghiệm nước tiểu',
                'service_type' => 'lab_test',
                'service_category' => 'urine',
                'default_price' => 0,
                'is_highlighted_default' => false,
                'display_on_patient_receipt_default' => true,
                'sort_order' => 40,
            ],
            [
                'code' => 'ULTRASOUND',
                'name' => 'Siêu âm',
                'service_type' => 'ultrasound',
                'service_category' => 'imaging',
                'default_price' => 0,
                'is_highlighted_default' => true,
                'display_on_patient_receipt_default' => true,
                'sort_order' => 50,
            ],
            [
                'code' => 'EXTERNAL_TEST',
                'name' => 'Xét nghiệm bên ngoài',
                'service_type' => 'external_test',
                'service_category' => 'external',
                'default_price' => 0,
                'is_highlighted_default' => true,
                'display_on_patient_receipt_default' => false,
                'sort_order' => 60,
            ],
            [
                'code' => 'SPECIAL_TEST',
                'name' => 'Xét nghiệm đặc biệt',
                'service_type' => 'other',
                'service_category' => 'special',
                'default_price' => 0,
                'is_highlighted_default' => true,
                'display_on_patient_receipt_default' => true,
                'sort_order' => 70,
            ],
        ];

        foreach ($items as $item) {
            ServiceCatalog::updateOrCreate(
                ['code' => $item['code']],
                $item + [
                    'is_active' => true,
                ]
            );
        }
    }
}
