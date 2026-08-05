<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('service_catalogs', 'status')) {
            Schema::table('service_catalogs', function (Blueprint $table) {
                $table->string('status', 30)
                    ->default('active');
            });
        }

        /*
         * Đồng bộ dữ liệu cũ:
         * is_active = 1 => active
         * is_active = 0 => suspended
         */
        if (Schema::hasColumn('service_catalogs', 'is_active')) {
            DB::table('service_catalogs')
                ->where('is_active', false)
                ->update([
                    'status' => 'suspended',
                ]);

            DB::table('service_catalogs')
                ->where('is_active', true)
                ->whereNull('status')
                ->update([
                    'status' => 'active',
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_catalogs', 'status')) {
            Schema::table('service_catalogs', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
