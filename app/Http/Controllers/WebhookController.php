<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayChanguWebhookRequest;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming PayChangu webhooks.
     *
     * @param  \App\Http\Requests\PayChanguWebhookRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(PayChanguWebhookRequest $request): JsonResponse
    {
        $this->processWebhook($request->validated());

        return response()->json(['message' => 'Webhook processed.']);
    }

    /**
     * Process the incoming webhook data.
     *
     * @param  array  $data
     * @return void
     */
    protected function processWebhook(array $data): void
    {
        if ($data['event_type'] !== 'api.charge.payment') {
            Log::info('PayChangu Webhook: Ignoring event.', $data);
            return;
        }

        try {
            $purchaseRequest = PurchaseRequest::where('charge_id', $data['charge_id'])->firstOrFail();

            $purchaseRequest->update([
                'status' => $data['status'],
            ]);

            Log::info('PayChangu Webhook: Purchase request updated successfully.', [
                'purchase_request_id' => $purchaseRequest->id,
                'new_status' => $data['status'],
                'reference' => $data['reference'],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('PayChangu Webhook: Purchase request not found for reference.', [
                'reference' => $data['reference'],
            ]);

        } catch (\Exception $e) {
            Log::error('PayChangu Webhook: Error processing webhook.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $data,
            ]);
        }
    }
}
