<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_catalogs', function (Blueprint $table) {
            $table->string('status', 30)
                ->default('active')
                ->after('is_active');

            $table->index('status');
        });

        // Dữ liệu cũ is_active = false được chuyển thành tạm ngưng.
        DB::table('service_catalogs')
            ->where('is_active', false)
            ->update([
                'status' => 'suspended',
            ]);
    }

    public function down(): void
    {
        Schema::table('service_catalogs', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
