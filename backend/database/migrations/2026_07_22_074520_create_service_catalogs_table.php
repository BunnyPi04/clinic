<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_catalogs', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->string('service_type', 30);
            $table->string('service_category', 30)->nullable();

            $table->decimal('default_price', 12, 2)->default(0);

            $table->boolean('is_highlighted_default')->default(false);
            $table->boolean('display_on_patient_receipt_default')
                ->default(true);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['service_type', 'is_active']);
            $table->index(['service_category']);
            $table->index(['sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_catalogs');
    }
};
