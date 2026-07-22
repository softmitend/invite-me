<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans): JsonResponse
    {
        $payment = $midtrans->handleNotification($request->all());

        return response()->json(['status' => 'ok', 'payment_id' => $payment->id]);
    }
}
