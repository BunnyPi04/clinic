<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->get('q'));

        $query = Patient::query()
            ->with(['primaryDoctor', 'patientSource'])
            ->latest();

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('full_name', 'like', "%{$keyword}%")
                    ->orWhere('patient_code', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('paper_book_code', 'like', "%{$keyword}%");
            });
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'paper_book_code' => ['nullable', 'string', 'max:255'],
            'primary_doctor_id' => ['nullable', 'exists:doctors,id'],
            'primary_doctor_assigned_at' => ['nullable', 'date'],
            'primary_doctor_note' => ['nullable', 'string'],
            'patient_source_id' => ['nullable', 'exists:patient_sources,id'],
            'patient_source_note' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['patient_code'] = 'BN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));

        $patient = Patient::create($data);

        return response()->json(
            $patient->load(['primaryDoctor', 'patientSource']),
            201
        );
    }

    public function show(Patient $patient)
    {
        return response()->json(
            $patient->load(['primaryDoctor', 'patientSource', 'visits.doctor'])
        );
    }
}
