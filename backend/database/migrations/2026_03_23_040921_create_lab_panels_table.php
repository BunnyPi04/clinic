<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_panels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_document_id')
                ->constrained('visit_documents')
                ->cascadeOnDelete();

            $table->string('panel_type', 30);
            $table->string('source_name')->nullable();

            $table->timestamp('sample_taken_at')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['visit_document_id']);
            $table->index(['panel_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_panels');
    }
};
