<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'hospital_id')) {
                $table->foreignId('hospital_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('hospitals')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('doctors', 'hospital_position')) {
                $table->string('hospital_position')
                    ->nullable();
            }

            if (! Schema::hasColumn(
                'doctors',
                'consultation_service_catalog_id'
            )) {
                $table->foreignId('consultation_service_catalog_id')
                    ->nullable()
                    ->constrained('service_catalogs')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('doctors', 'employment_status')) {
                $table->string('employment_status', 30)
                    ->default('active');
            }

            if (! Schema::hasColumn('doctors', 'professional_note')) {
                $table->text('professional_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn(
                'doctors',
                'consultation_service_catalog_id'
            )) {
                $table->dropForeign(
                    ['consultation_service_catalog_id']
                );
                $table->dropColumn(
                    'consultation_service_catalog_id'
                );
            }

            if (Schema::hasColumn('doctors', 'hospital_id')) {
                $table->dropForeign(['hospital_id']);
                $table->dropColumn('hospital_id');
            }

            if (Schema::hasColumn('doctors', 'hospital_position')) {
                $table->dropColumn('hospital_position');
            }

            if (Schema::hasColumn('doctors', 'employment_status')) {
                $table->dropColumn('employment_status');
            }

            if (Schema::hasColumn('doctors', 'professional_note')) {
                $table->dropColumn('professional_note');
            }
        });
    }
};
