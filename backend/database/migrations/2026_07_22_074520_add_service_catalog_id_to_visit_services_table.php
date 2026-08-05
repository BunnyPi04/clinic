<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_services', function (Blueprint $table) {
            $table->foreignId('service_catalog_id')
                ->nullable()
                ->after('visit_id')
                ->constrained('service_catalogs')
                ->nullOnDelete();

            $table->index('service_catalog_id');
        });
    }

    public function down(): void
    {
        Schema::table('visit_services', function (Blueprint $table) {
            $table->dropForeign(['service_catalog_id']);
            $table->dropIndex(['service_catalog_id']);
            $table->dropColumn('service_catalog_id');
        });
    }
};
