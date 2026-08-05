<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_id')
                ->constrained('visits')
                ->cascadeOnDelete();

            $table->foreignId('source_document_id')
                ->nullable()
                ->constrained('visit_documents')
                ->nullOnDelete();

            $table->string('note_type', 50);
            $table->longText('content_text');

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

            $table->index(['visit_id']);
            $table->index(['note_type']);
            $table->index(['source_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_notes');
    }
};
