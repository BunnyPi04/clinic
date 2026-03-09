<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            $table->string('patient_code')->unique();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('address')->nullable();
            $table->string('paper_book_code')->nullable()->index();

            $table->foreignId('primary_doctor_id')
                ->nullable()
                ->constrained('doctors')
                ->nullOnDelete();

            $table->date('primary_doctor_assigned_at')->nullable();
            $table->text('primary_doctor_note')->nullable();

            $table->foreignId('patient_source_id')
                ->nullable()
                ->constrained('patient_sources')
                ->nullOnDelete();

            $table->text('patient_source_note')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
