<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Visit;
use App\Models\VisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitServiceBatchController extends Controller
{
    public function replace(Request $request, Visit $visit)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.service_catalog_id' => [
                'nullable',
                'exists:service_catalogs,id',
            ],
            'items.*.service_type' => [
                'required',
                'in:consultation,lab_test,ultrasound,external_test,other',
            ],
            'items.*.service_category' => [
                'nullable',
                'string',
                'max:50',
            ],
            'items.*.service_code' => [
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.service_name' => [
                'required',
                'string',
                'max:255',
            ],
            'items.*.doctor_id' => [
                'nullable',
                'exists:doctors,id',
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'items.*.is_highlighted' => [
                'nullable',
                'boolean',
            ],
            'items.*.is_custom' => [
                'nullable',
                'boolean',
            ],
            'items.*.display_on_patient_receipt' => [
                'nullable',
                'boolean',
            ],
            'items.*.sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $services = DB::transaction(function () use ($visit, $data) {
            /*
             * Không xóa LAB-TOTAL vì dòng này có thể được đồng bộ
             * từ kết quả xét nghiệm có cấu trúc.
             */
            $visit->services()
                ->where(function ($query) {
                    $query->whereNull('service_code')
                        ->orWhere('service_code', '!=', 'LAB-TOTAL');
                })
                ->delete();

            foreach ($data['items'] as $index => $item) {
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                VisitService::create([
                    'visit_id' => $visit->id,
                    'service_catalog_id' =>
                        $item['service_catalog_id'] ?? null,
                    'service_type' => $item['service_type'],
                    'service_category' =>
                        $item['service_category'] ?? null,
                    'service_code' =>
                        $item['service_code'] ?? null,
                    'service_name' => $item['service_name'],
                    'doctor_id' =>
                        $item['doctor_id'] ?? null,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'amount' => $unitPrice * $quantity,
                    'is_highlighted' =>
                        (bool) ($item['is_highlighted'] ?? false),
                    'is_custom' =>
                        (bool) ($item['is_custom'] ?? false),
                    'display_on_patient_receipt' =>
                        (bool) (
                            $item['display_on_patient_receipt'] ?? true
                        ),
                    'sort_order' =>
                        (int) ($item['sort_order'] ?? $index + 1),
                ]);
            }

            $this->refreshExistingPayment($visit);

            return $visit->services()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });

        return response()->json([
            'message' => 'Visit services saved successfully',
            'items' => $services,
            'total_amount' => (float) $services->sum('amount'),
        ]);
    }

    private function refreshExistingPayment(Visit $visit): void
    {
        $payment = Payment::query()
            ->where('visit_id', $visit->id)
            ->first();

        if (! $payment) {
            return;
        }

        $total = (float) $visit->services()->sum('amount');

        $payment->total_amount = $total;

        if ((float) $payment->amount_paid <= 0) {
            $payment->payment_status = 'unpaid';
        } elseif ((float) $payment->amount_paid < $total) {
            $payment->payment_status = 'partial';
        } else {
            $payment->payment_status = 'paid';
        }

        $payment->save();
    }
}
