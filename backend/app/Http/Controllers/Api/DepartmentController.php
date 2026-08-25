<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->query('q', '')
        );

        $query = Department::query()
            ->orderBy('name');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        return response()->json(
            $query->paginate(100)
        );
    }

    public function store(Request $request)
    {
        $department = Department::create(
            $this->validateData($request)
        );

        return response()->json($department, 201);
    }

    public function update(
        Request $request,
        Department $department
    ) {
        $department->update(
            $this->validateData(
                $request,
                $department->id
            )
        );

        return response()->json(
            $department->fresh()
        );
    }

    private function validateData(
        Request $request,
        ?int $departmentId = null
    ): array {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')
                    ->ignore($departmentId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'required',
                Rule::in([
                    'active',
                    'suspended',
                    'discontinued',
                ]),
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ]);
    }
}
