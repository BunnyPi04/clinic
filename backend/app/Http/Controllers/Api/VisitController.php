<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $query = Visit::query()
            ->with(['patient', 'doctor', 'originalDoctor'])
            ->latest('visit_date');

        if ($request->filled('visit_date')) {
            $query->whereDate('visit_date', $request->visit_date);
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        return response()->json($query->paginate(20));
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

        $visitDate = $data['visit_date'];
        $doctorId = $data['doctor_id'];

        $lastQueue = Visit::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('visit_date', $visitDate)
            ->max('queue_number');

        $data['queue_number'] = ($lastQueue ?? 0) + 1;
        $data['visit_code'] = 'VS-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $data['is_covering_doctor'] = (bool) ($data['is_covering_doctor'] ?? false);
        $data['priority_flag'] = (bool) ($data['priority_flag'] ?? false);
        $data['status'] = $data['status'] ?? 'registered';

        $visit = Visit::create($data);

        return response()->json(
            $visit->load(['patient', 'doctor', 'originalDoctor']),
            201
        );
    }

    public function show(Visit $visit)
    {
        return response()->json(
            $visit->load(['patient', 'doctor', 'originalDoctor'])
        );
    }
}
