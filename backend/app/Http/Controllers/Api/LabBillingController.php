<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\VisitService;

class LabBillingController extends Controller
{
    public function syncToVisitService(Visit $visit)
    {
        $labResults = $visit->documents()
            ->with(['labPanels.results'])
            ->get()
            ->flatMap(function ($document) {
                return $document->labPanels->flatMap(function ($panel) {
                    return $panel->results;
                });
            });

        $billableResults = $labResults->filter(function ($result) {
            return (bool) $result->include_in_billing === true;
        });

        $totalAmount = (float) $billableResults->sum(function ($result) {
            return (float) $result->price;
        });

        $service = VisitService::query()->updateOrCreate(
            [
                'visit_id' => $visit->id,
                'service_code' => 'LAB-TOTAL',
            ],
            [
                'service_type' => 'lab_test',
                'service_category' => 'test',
                'service_name' => 'Tổng chi phí xét nghiệm',
                'doctor_id' => $visit->doctor_id,
                'unit_price' => $totalAmount,
                'quantity' => 1,
                'amount' => $totalAmount,
                'is_highlighted' => false,
                'is_custom' => false,
                'display_on_patient_receipt' => true,
                'sort_order' => 500,
            ]
        );

        return response()->json([
            'message' => 'Lab billing synced successfully',
            'visit_id' => $visit->id,
            'billable_results_count' => $billableResults->count(),
            'total_amount' => $totalAmount,
            'visit_service' => $service,
        ]);
    }
}
