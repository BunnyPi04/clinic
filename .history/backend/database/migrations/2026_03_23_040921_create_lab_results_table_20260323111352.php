<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lab_panel_id')
                ->constrained('lab_panels')
                ->cascadeOnDelete();

            $table->string('test_code')->nullable();
            $table->string('test_name_original');
            $table->string('test_name_normalized')->nullable();

            $table->string('value_text')->nullable();
            $table->decimal('value_number', 12, 4)->nullable();

            $table->string('unit')->nullable();

            $table->string('reference_range_text')->nullable();
            $table->decimal('reference_min', 12, 4)->nullable();
            $table->decimal('reference_max', 12, 4)->nullable();

            $table->string('flag', 20)->default('unknown');
            $table->decimal('ai_confidence', 5, 4)->nullable();

            $table->boolean('is_manually_corrected')->default(false);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['lab_panel_id']);
            $table->index(['test_name_original']);
            $table->index(['test_name_normalized']);
            $table->index(['flag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
