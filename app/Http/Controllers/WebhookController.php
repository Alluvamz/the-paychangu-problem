<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->json()->all();

        if (!isset($payload['charge_id']) || !isset($payload['status'])) {
            Log::error('PayChangu Webhook: Invalid payload', $payload);
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        $purchaseRequest = PurchaseRequest::where('charge_id', $payload['charge_id'])->first();

        if (!$purchaseRequest) {
            Log::error('PayChangu Webhook: Purchase request not found', $payload);
            return response()->json(['status' => 'error', 'message' => 'Purchase request not found'], 404);
        }

        $purchaseRequest->update([
            'status' => $payload['status'],
        ]);

        return response()->json(['status' => 'success']);
    }
}
