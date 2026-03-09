<?php

namespace Database\Seeders;

use App\Models\PatientSource;
use Illuminate\Database\Seeder;

class PatientSourceSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'hospital_referral', 'name' => 'Chuyển từ bệnh viện'],
            ['code' => 'doctor_private_patient', 'name' => 'Bệnh nhân riêng của bác sĩ'],
            ['code' => 'walk_in', 'name' => 'Bệnh nhân vãng lai'],
            ['code' => 'family_referral', 'name' => 'Người nhà giới thiệu'],
            ['code' => 'friend_referral', 'name' => 'Người quen giới thiệu'],
            ['code' => 'online', 'name' => 'Nguồn online'],
            ['code' => 'other', 'name' => 'Khác'],
        ];

        foreach ($items as $item) {
            PatientSource::updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
