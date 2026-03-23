<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\VisitService;
use Illuminate\Http\Request;

class VisitServiceController extends Controller
{
    public function index(Visit $visit)
    {
        return response()->json(
            $visit->services()->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function store(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'service_type' => ['required', 'in:consultation,lab_test,ultrasound,external_test,other'],
            'service_category' => ['nullable', 'string', 'max:30'],
            'service_code' => ['nullable', 'string', 'max:255'],
            'service_name' => ['required', 'string', 'max:255'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'is_highlighted' => ['nullable', 'boolean'],
            'is_custom' => ['nullable', 'boolean'],
            'display_on_patient_receipt' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['visit_id'] = $visit->id;
        $data['amount'] = (float) $data['unit_price'] * (int) $data['quantity'];
        $data['is_highlighted'] = (bool) ($data['is_highlighted'] ?? false);
        $data['is_custom'] = (bool) ($data['is_custom'] ?? false);
        $data['display_on_patient_receipt'] = (bool) ($data['display_on_patient_receipt'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $service = VisitService::create($data);

        return response()->json($service, 201);
    }

    public function update(Request $request, VisitService $visitService)
    {
        $data = $request->validate([
            'service_type' => ['sometimes', 'in:consultation,lab_test,ultrasound,external_test,other'],
            'service_category' => ['nullable', 'string', 'max:30'],
            'service_code' => ['nullable', 'string', 'max:255'],
            'service_name' => ['sometimes', 'string', 'max:255'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'is_highlighted' => ['nullable', 'boolean'],
            'is_custom' => ['nullable', 'boolean'],
            'display_on_patient_receipt' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $visitService->fill($data);

        $visitService->amount = (float) $visitService->unit_price * (int) $visitService->quantity;

        $visitService->save();

        return response()->json($visitService);
    }

    public function destroy(VisitService $visitService)
    {
        $visitService->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}
