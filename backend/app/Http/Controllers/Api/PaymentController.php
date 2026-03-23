<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function showByVisit(Visit $visit)
    {
        $payment = $visit->payment()->with('transactions')->first();

        if (! $payment) {
            return response()->json([
                'message' => 'Payment not found for this visit',
            ], 404);
        }

        return response()->json($payment);
    }

    public function store(Request $request, Visit $visit)
    {
        if ($visit->payment()->exists()) {
            return response()->json([
                'message' => 'Payment already exists for this visit',
            ], 422);
        }

        $data = $request->validate([
            'payment_method' => ['nullable', 'in:cash,transfer,mixed'],
            'cashier_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string'],
        ]);

        $totalAmount = (float) $visit->services()->sum('amount');

        $payment = Payment::create([
            'visit_id' => $visit->id,
            'receipt_no' => 'RC-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'payment_status' => 'unpaid',
            'payment_method' => $data['payment_method'] ?? null,
            'cashier_id' => $data['cashier_id'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json($payment, 201);
    }

    public function refreshTotals(Visit $visit)
    {
        $payment = $visit->payment;

        if (! $payment) {
            return response()->json([
                'message' => 'Payment not found for this visit',
            ], 404);
        }

        $payment->total_amount = (float) $visit->services()->sum('amount');
        $payment->payment_status = $this->resolveStatus(
            (float) $payment->amount_paid,
            (float) $payment->total_amount
        );

        if ((float) $payment->amount_paid > 0) {
            $payment->paid_at = now();
        }

        $payment->save();

        return response()->json($payment->fresh('transactions'));
    }

    private function resolveStatus(float $paid, float $total): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid < $total) {
            return 'partial';
        }

        return 'paid';
    }
}
