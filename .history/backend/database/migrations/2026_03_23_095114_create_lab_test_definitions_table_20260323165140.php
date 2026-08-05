<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('test_code')->unique();
            $table->string('test_name');
            $table->string('test_name_normalized')->nullable();

            $table->string('panel_type', 30)->nullable(); // blood / urine / special
            $table->string('sample_group', 50)->nullable(); // biochemistry / hematology / urine

            $table->string('standard_unit', 50)->nullable();

            $table->decimal('male_reference_min', 12, 4)->nullable();
            $table->decimal('male_reference_max', 12, 4)->nullable();
            $table->string('male_reference_text')->nullable();

            $table->decimal('female_reference_min', 12, 4)->nullable();
            $table->decimal('female_reference_max', 12, 4)->nullable();
            $table->string('female_reference_text')->nullable();

            $table->decimal('default_price', 12, 2)->default(0);
            $table->boolean('default_include_in_billing')->default(true);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['panel_type']);
            $table->index(['sample_group']);
            $table->index(['test_name']);
            $table->index(['test_name_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_definitions');
    }
};
