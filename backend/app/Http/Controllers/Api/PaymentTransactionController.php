<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class PaymentTransactionController extends Controller
{
    public function store(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'transaction_type' => ['required', 'in:collect,refund,adjustment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:cash,transfer,mixed'],
            'collected_by' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string'],
        ]);

        $transaction = PaymentTransaction::create($data + [
            'payment_id' => $payment->id,
        ]);

        $amount = (float) $payment->amount_paid;

        if ($data['transaction_type'] === 'collect') {
            $amount += (float) $data['amount'];
        } elseif ($data['transaction_type'] === 'refund') {
            $amount -= (float) $data['amount'];
        }

        if ($amount < 0) {
            $amount = 0;
        }

        $payment->amount_paid = $amount;
        $payment->payment_method = $data['payment_method'] ?? $payment->payment_method;
        $payment->payment_status = $this->resolveStatus(
            (float) $payment->amount_paid,
            (float) $payment->total_amount
        );
        $payment->paid_at = (float) $payment->amount_paid > 0 ? now() : null;
        $payment->save();

        return response()->json([
            'transaction' => $transaction,
            'payment' => $payment->fresh('transactions'),
        ], 201);
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
