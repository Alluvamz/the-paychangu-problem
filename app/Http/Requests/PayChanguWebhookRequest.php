<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class PayChanguWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $signature = $this->header('X-Pay-Changu-Signature');
        $secret = config('services.paychangu.secret');

        if (! $signature || ! $secret) {
            Log::warning('PayChangu Webhook: Missing signature or secret.');
            return false;
        }

        $payload = $this->getContent();
        $computedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($computedSignature, $signature)) {
            Log::error('PayChangu Webhook: Invalid signature.', [
                'received_signature' => $signature,
                'computed_signature' => $computedSignature,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string'],
            'status' => ['required', 'string'],
            'reference' => ['required', 'string'],
            'charge_id' => ['required', 'string'],
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Invalid signature.'
        ], 401));
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Invalid payload.',
            'errors' => $validator->errors()
        ], 422));
    }
}
