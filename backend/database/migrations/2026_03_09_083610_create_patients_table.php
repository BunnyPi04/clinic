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

            $table->string('phone', 30)->nullable();

            // địa chỉ tách field
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('street')->nullable();
            $table->string('house_number')->nullable();

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

            // zalo liên hệ nếu khác số chính
            $table->string('zalo_phone', 30)->nullable();

            // liên hệ khẩn cấp
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['full_name']);
            $table->index(['phone']);
            $table->index(['zalo_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
