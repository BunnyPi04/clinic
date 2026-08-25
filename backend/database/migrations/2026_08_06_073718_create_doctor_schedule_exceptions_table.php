<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'doctor_schedule_exceptions',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('doctor_id')
                    ->constrained('doctors')
                    ->cascadeOnDelete();

                $table->date('work_date');

                /*
                 * working: khám thêm dù không có lịch cố định
                 * off: nghỉ dù có lịch cố định
                 */
                $table->string('exception_type', 30);

                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();

                $table->text('reason')->nullable();

                $table->timestamps();

                $table->unique(
                    ['doctor_id', 'work_date'],
                    'doctor_schedule_exception_unique'
                );

                $table->index([
                    'work_date',
                    'exception_type',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'doctor_schedule_exceptions'
        );
    }
};
