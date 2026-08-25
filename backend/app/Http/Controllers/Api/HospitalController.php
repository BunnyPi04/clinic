<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HospitalController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim(
            (string) $request->query('q', '')
        );

        $query = Hospital::query()
            ->orderBy('name');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('short_name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        return response()->json(
            $query->paginate(30)
        );
    }

    public function store(Request $request)
    {
        $hospital = Hospital::create(
            $this->validateData($request)
        );

        return response()->json($hospital, 201);
    }

    public function show(Hospital $hospital)
    {
        return response()->json(
            $hospital->load('doctors')
        );
    }

    public function update(
        Request $request,
        Hospital $hospital
    ) {
        $hospital->update(
            $this->validateData(
                $request,
                $hospital->id
            )
        );

        return response()->json($hospital->fresh());
    }

    private function validateData(
        Request $request,
        ?int $hospitalId = null
    ): array {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hospitals', 'code')
                    ->ignore($hospitalId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'short_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
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
            'website' => [
                'nullable',
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
            'note' => [
                'nullable',
                'string',
            ],
        ]);
    }
}
