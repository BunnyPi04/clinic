<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_id')
                ->constrained('visits')
                ->cascadeOnDelete();

            $table->string('service_type', 30);
            $table->string('service_category', 30)->nullable();

            $table->string('service_code')->nullable();
            $table->string('service_name');

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('doctors')
                ->nullOnDelete();

            $table->decimal('unit_price', 12, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('amount', 12, 2)->default(0);

            $table->boolean('is_highlighted')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->boolean('display_on_patient_receipt')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['visit_id']);
            $table->index(['service_type']);
            $table->index(['service_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_services');
    }
};
