<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Get the raw payload before JSON decoding
        $payload = $request->getContent();
        $signature = $request->header('Signature');
        $secret = config('services.paychangu.webhook_secret');

        // Log incoming webhook for debugging
        Log::info('PayChangu Webhook: Request received', [
            'headers' => $request->headers->all(),
            'payload' => $payload
        ]);

        // Verify the webhook signature
        if (!$this->isValidSignature($signature, $payload, $secret)) {
            Log::error('PayChangu Webhook: Invalid signature', [
                'received_signature' => $signature,
                'expected_signature' => hash_hmac('sha256', $payload, $secret)
            ]);
            return response('Invalid signature', 401);
        }

        // Parse the JSON payload
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('PayChangu Webhook: Invalid JSON payload', [
                'payload' => $payload,
                'error' => json_last_error_msg()
            ]);
            return response('Invalid JSON payload', 400);
        }

        // Validate required fields
        if (!isset($data['charge_id']) || !isset($data['status'])) {
            Log::error('PayChangu Webhook: Missing required fields', $data);
            return response('Missing required fields', 400);
        }

        try {
            // Process the webhook
            $this->processWebhook($data);

            // Return 200 OK response to acknowledge receipt
            return response('Webhook processed', 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('PayChangu Webhook: Purchase request not found for charge_id.', [
                'charge_id' => $data['charge_id'] ?? 'not_provided',
            ]);

            return response('Purchase request not found', 404);
        } catch (\Exception $e) {
            Log::error('PayChangu Webhook: Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $data,
            ]);

            return response('Error processing webhook', 500);
        }
    }

    private function processWebhook(array $data): void
    {
        // Find the purchase request or fail, which will be caught by the handler
        $purchaseRequest = PurchaseRequest::where('charge_id', $data['charge_id'])->firstOrFail();

        // Update the purchase request status
        $purchaseRequest->update([
            'status' => $data['status'],
            'metadata' => array_merge($purchaseRequest->metadata ?? [], [
                'last_webhook_received_at' => now()->toDateTimeString(),
                'webhook_payload' => $data
            ])
        ]);

        // Log the update
        Log::info('PayChangu Webhook: Purchase request updated', [
            'purchase_request_id' => $purchaseRequest->id,
            'new_status' => $data['status'],
            'charge_id' => $data['charge_id']
        ]);

        // TODO: Add any additional business logic here (e.g., send notifications, update orders, etc.)
    }

    private function isValidSignature(?string $signature, string $payload, string $secret): bool
    {
        if (empty($signature) || empty($secret)) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $payload, $secret);
        
        // Use hash_equals for timing attack prevention
        return hash_equals($computedSignature, $signature);
    }
}
