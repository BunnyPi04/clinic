<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DoctorManagementController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->query('q', '')
        );

        $query = Doctor::query()
            ->with([
                'hospital',
                'departments',
                'consultationService',
            ])
            ->orderBy('full_name');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where(
                        'full_name',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'phone',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$keyword}%"
                    );
            });
        }

        if ($request->filled('employment_status')) {
            $query->where(
                'employment_status',
                $request->query('employment_status')
            );
        }

        return response()->json(
            $query->paginate(30)
        );
    }

    public function show(Doctor $doctor)
    {
        return response()->json(
            $doctor->load([
                'hospital',
                'departments',
                'consultationService',
                'schedules',
                'scheduleExceptions',
            ])
        );
    }

    public function update(
        Request $request,
        Doctor $doctor
    ) {
        $data = $this->validateData($request);

        DB::transaction(function () use ($doctor, $data) {
            $departmentIds =
                $data['department_ids'] ?? [];

            $primaryDepartmentId =
                $data['primary_department_id'] ?? null;

            unset(
                $data['department_ids'],
                $data['primary_department_id']
            );

            $doctor->update($data);

            $syncData = [];

            foreach ($departmentIds as $departmentId) {
                $syncData[$departmentId] = [
                    'is_primary' =>
                        (int) $departmentId
                        === (int) $primaryDepartmentId,
                ];
            }

            $doctor->departments()->sync($syncData);
        });

        return response()->json(
            $doctor->fresh()->load([
                'hospital',
                'departments',
                'consultationService',
            ])
        );
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],
            'degree' => [
                'nullable',
                'string',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'hospital_id' => [
                'nullable',
                'exists:hospitals,id',
            ],
            'hospital_position' => [
                'nullable',
                'string',
                'max:255',
            ],
            'consultation_service_catalog_id' => [
                'nullable',
                'exists:service_catalogs,id',
            ],
            'employment_status' => [
                'required',
                Rule::in([
                    'active',
                    'on_leave',
                    'inactive',
                ]),
            ],
            'professional_note' => [
                'nullable',
                'string',
            ],
            'department_ids' => [
                'nullable',
                'array',
            ],
            'department_ids.*' => [
                'integer',
                'exists:departments,id',
            ],
            'primary_department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],
        ]);
    }
}
