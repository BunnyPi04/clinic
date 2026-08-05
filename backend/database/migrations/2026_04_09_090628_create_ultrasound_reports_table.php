<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ultrasound_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_document_id')
                ->constrained('visit_documents')
                ->cascadeOnDelete();

            $table->string('body_part')->nullable();
            $table->longText('findings_text')->nullable();
            $table->longText('conclusion_text')->nullable();

            $table->boolean('is_manually_corrected')->default(false);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['visit_document_id']);
            $table->index(['body_part']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ultrasound_reports');
    }
};
