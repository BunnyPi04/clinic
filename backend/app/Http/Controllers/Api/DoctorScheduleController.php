<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorScheduleException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorScheduleController extends Controller
{
    public function index(Doctor $doctor)
    {
        return response()->json([
            'doctor' => $doctor,
            'schedules' => $doctor->schedules()
                ->orderBy('weekday')
                ->orderBy('start_time')
                ->get(),
            'exceptions' => $doctor->scheduleExceptions()
                ->orderByDesc('work_date')
                ->limit(100)
                ->get(),
        ]);
    }

    public function storeSchedule(
        Request $request,
        Doctor $doctor
    ) {
        $data = $request->validate([
            'weekday' => [
                'required',
                'integer',
                'between:1,7',
            ],
            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
            ],
            'effective_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'effective_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:effective_from',
            ],
            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
            'note' => [
                'nullable',
                'string',
            ],
        ]);

        $schedule = $doctor->schedules()->create($data);

        return response()->json($schedule, 201);
    }

    public function updateSchedule(
        Request $request,
        DoctorSchedule $doctorSchedule
    ) {
        $data = $request->validate([
            'weekday' => [
                'required',
                'integer',
                'between:1,7',
            ],
            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
            ],
            'effective_from' => [
                'required',
                'date_format:Y-m-d',
            ],
            'effective_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:effective_from',
            ],
            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
            'note' => [
                'nullable',
                'string',
            ],
        ]);

        $doctorSchedule->update($data);

        return response()->json(
            $doctorSchedule->fresh()
        );
    }

    public function storeException(
        Request $request,
        Doctor $doctor
    ) {
        $data = $request->validate([
            'work_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'exception_type' => [
                'required',
                Rule::in([
                    'working',
                    'off',
                ]),
            ],
            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
            ],
            'reason' => [
                'nullable',
                'string',
            ],
        ]);

        $exception = DoctorScheduleException::updateOrCreate(
            [
                'doctor_id' => $doctor->id,
                'work_date' => $data['work_date'],
            ],
            $data
        );

        return response()->json($exception, 201);
    }

    public function doctorsForDate(Request $request)
    {
        $data = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $date = Carbon::createFromFormat(
            'Y-m-d',
            $data['date']
        );

        $weekday = $date->isoWeekday();

        $doctors = Doctor::query()
            ->where('employment_status', 'active')
            ->with([
                'hospital',
                'departments',
                'consultationService',
                'schedules' => function ($query) use (
                    $date,
                    $weekday
                ) {
                    $query
                        ->where('weekday', $weekday)
                        ->where('status', 'active')
                        ->whereDate(
                            'effective_from',
                            '<=',
                            $date->toDateString()
                        )
                        ->where(function ($builder) use ($date) {
                            $builder
                                ->whereNull('effective_to')
                                ->orWhereDate(
                                    'effective_to',
                                    '>=',
                                    $date->toDateString()
                                );
                        });
                },
                'scheduleExceptions' => function ($query) use (
                    $date
                ) {
                    $query->whereDate(
                        'work_date',
                        $date->toDateString()
                    );
                },
            ])
            ->orderBy('full_name')
            ->get();

        $scheduled = [];
        $notScheduled = [];

        foreach ($doctors as $doctor) {
            $exception =
                $doctor->scheduleExceptions->first();

            if ($exception?->exception_type === 'off') {
                $notScheduled[] = $doctor;
                continue;
            }

            if (
                $exception?->exception_type === 'working'
                || $doctor->schedules->isNotEmpty()
            ) {
                $scheduled[] = $doctor;
                continue;
            }

            $notScheduled[] = $doctor;
        }

        return response()->json([
            'date' => $date->toDateString(),
            'weekday' => $weekday,
            'scheduled_doctors' => $scheduled,
            'not_scheduled_doctors' => $notScheduled,
        ]);
    }
}
