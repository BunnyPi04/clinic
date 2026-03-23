<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('visit_id')
                ->constrained('visits')
                ->cascadeOnDelete();

            $table->string('document_type', 50);
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->string('file_path');
            $table->string('file_name');
            $table->string('original_file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->timestamp('captured_at')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ai_status', 30)->default('uploaded');
            $table->string('review_status', 30)->default('unreviewed');

            $table->longText('ai_raw_text')->nullable();
            $table->json('ai_structured_data_json')->nullable();
            $table->text('ai_error_message')->nullable();

            $table->timestamps();

            $table->index(['visit_id']);
            $table->index(['document_type']);
            $table->index(['ai_status']);
            $table->index(['review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_documents');
    }
};
