<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_service_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->cascadeOnDelete();

            $table->foreignId('service_catalog_id')
                ->constrained('service_catalogs')
                ->cascadeOnDelete();

            $table->string('visit_type', 30);

            $table->decimal('price', 12, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique([
                'doctor_id',
                'service_catalog_id',
                'visit_type',
            ], 'doctor_service_visit_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_service_prices');
    }
};
