<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();

            $table->string('visit_code')->unique();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->restrictOnDelete();

            $table->foreignId('original_doctor_id')
                ->nullable()
                ->constrained('doctors')
                ->nullOnDelete();

            $table->boolean('is_covering_doctor')->default(false);

            $table->date('visit_date');
            $table->string('visit_type', 30)->default('follow_up');

            $table->unsignedInteger('queue_number')->nullable();

            $table->boolean('priority_flag')->default(false);
            $table->string('priority_type', 30)->nullable();
            $table->string('priority_note')->nullable();

            $table->string('status', 30)->default('registered');
            $table->text('cashier_note')->nullable();
            $table->text('clinical_note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['patient_id', 'visit_date']);
            $table->index(['doctor_id', 'visit_date']);
            $table->index(['status', 'visit_date']);
            $table->unique(['doctor_id', 'visit_date', 'queue_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
