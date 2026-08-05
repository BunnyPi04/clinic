<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorServicePrice;
use App\Models\ServiceCatalog;
use App\Models\Visit;
use Illuminate\Http\Request;

class BillingOptionController extends Controller
{
    public function index(Request $request, Visit $visit)
    {
        $keyword = trim((string) $request->query('q', ''));

        $services = ServiceCatalog::query()
            ->select([
                'id',
                'code',
                'name',
                'service_type',
                'service_category',
                'default_price',
                'is_highlighted_default',
                'display_on_patient_receipt_default',
                'status',
                'sort_order',
                'description',
            ])
            ->selectable()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($builder) use ($keyword) {
                    $builder
                        ->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(100)
            ->get();

        $doctorPrices = DoctorServicePrice::query()
            ->where('doctor_id', $visit->doctor_id)
            ->where('visit_type', $visit->visit_type)
            ->where('is_active', true)
            ->get()
            ->keyBy('service_catalog_id');

        $items = $services->map(function (
            ServiceCatalog $service
        ) use ($doctorPrices) {
            $doctorPrice = $doctorPrices->get($service->id);

            return [
                'id' => $service->id,
                'code' => $service->code,
                'name' => $service->name,
                'service_type' => $service->service_type,
                'service_category' => $service->service_category,
                'price' => (float) (
                    $doctorPrice?->price
                    ?? $service->default_price
                ),
                'price_source' => $doctorPrice
                    ? 'doctor_price'
                    : 'default_price',
                'status' => $service->status,
                'selectable' => $service->status
                    === ServiceCatalog::STATUS_ACTIVE,
                'is_highlighted' =>
                    $service->is_highlighted_default,
                'display_on_patient_receipt' =>
                    $service->display_on_patient_receipt_default,
                'description' => $service->description,
            ];
        });

        return response()->json([
            'visit' => [
                'id' => $visit->id,
                'visit_type' => $visit->visit_type,
                'doctor_id' => $visit->doctor_id,
            ],
            'items' => $items,
        ]);
    }
}
