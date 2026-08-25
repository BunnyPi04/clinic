<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained('doctors')
                ->cascadeOnDelete();

            /*
             * ISO weekday:
             * 1 = Thứ hai
             * 2 = Thứ ba
             * ...
             * 7 = Chủ nhật
             */
            $table->unsignedTinyInteger('weekday');

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            /*
             * Lịch có hiệu lực theo từng giai đoạn.
             * Khi bác sĩ đổi lịch, đóng effective_to của lịch cũ
             * rồi tạo lịch mới.
             */
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->string('status', 30)->default('active');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index([
                'doctor_id',
                'weekday',
                'effective_from',
                'effective_to',
            ], 'doctor_schedule_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
