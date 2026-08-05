<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Visit::query()
            ->with([
                'patient',
                'doctor',
                'originalDoctor',
            ]);

        if (! empty($data['date_from'])) {
            $query->whereDate('visit_date', '>=', $data['date_from']);
        }

        if (! empty($data['date_to'])) {
            $query->whereDate('visit_date', '<=', $data['date_to']);
        }

        if (! empty($data['patient_id'])) {
            $query->where('patient_id', $data['patient_id']);
        }

        if (! empty($data['doctor_id'])) {
            $query->where('doctor_id', $data['doctor_id']);
        }

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (! empty($data['q'])) {
            $keyword = trim($data['q']);

            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('visit_code', 'like', "%{$keyword}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($keyword) {
                        $patientQuery
                            ->where('full_name', 'like', "%{$keyword}%")
                            ->orWhere('patient_code', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
            });
        }

        return response()->json(
            $query
                ->orderByDesc('priority_flag')
                ->orderBy('visit_date')
                ->orderBy('queue_number')
                ->paginate($data['per_page'] ?? 30)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:doctors,id'],
            'original_doctor_id' => ['nullable', 'exists:doctors,id'],
            'is_covering_doctor' => ['nullable', 'boolean'],
            'visit_date' => ['required', 'date'],
            'visit_type' => ['required', 'in:first_visit,follow_up'],
            'priority_flag' => ['nullable', 'boolean'],
            'priority_type' => ['nullable', 'in:elderly,weak,emergency,other'],
            'priority_note' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:registered,awaiting_payment,partial_paid,paid,collecting_documents,reviewing,doctor_ready,completed,cancelled'],
            'cashier_note' => ['nullable', 'string'],
            'clinical_note' => ['nullable', 'string'],
        ]);

        $visit = DB::transaction(function () use ($data) {
            $visitDate = $data['visit_date'];
            $doctorId = $data['doctor_id'];

            $lastQueue = Visit::query()
                ->where('doctor_id', $doctorId)
                ->whereDate('visit_date', $visitDate)
                ->lockForUpdate()
                ->max('queue_number');

            $data['queue_number'] = ($lastQueue ?? 0) + 1;

            $data['visit_code'] =
                'VS-' .
                now()->format('YmdHis') .
                '-' .
                Str::upper(Str::random(4));

            $data['is_covering_doctor'] =
                (bool) ($data['is_covering_doctor'] ?? false);

            $data['priority_flag'] =
                (bool) ($data['priority_flag'] ?? false);

            $data['status'] = $data['status'] ?? 'registered';

            return Visit::create($data);
        });

        return response()->json(
            $visit->load(['patient', 'doctor', 'originalDoctor']),
            201
        );
    }

    public function show(Visit $visit)
    {
        return response()->json(
            $visit->load([
                'patient.primaryDoctor',
                'patient.patientSource',
                'doctor',
                'originalDoctor',
            ])
        );
    }
}
