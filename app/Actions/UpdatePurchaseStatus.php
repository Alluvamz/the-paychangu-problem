<?php

declare(strict_types=1);

namespace App\Actions;

use Alluvamz\PayChanguMobile\PayChanguIntegration;
use Alluvamz\PayChanguMobile\PayChanguIntegrationException;
use Alluvamz\PayChanguMobile\Response\ErrorResponse;
use App\Models\PurchaseRequest;
use App\Payment\PaymentException;
use Masmerise\Toaster\Toaster;

class UpdatePurchaseStatus
{
    public function __construct(
        private readonly PurchaseRequest $purchaseRequest
    ) {}

    public function execute(bool $silent = false): void
    {
        // Ensure the purchase request belongs to the current user
        $userId = auth()->id();

        if ($this->purchaseRequest->user_id !== $userId) {
            if (! $silent) {
                Toaster::error('Unauthorized access');
            }

            return;
        }

        try {
            $response = resolve(PayChanguIntegration::class)
                ->getDirectChargeStatus($this->purchaseRequest->charge_id);

            if ($response instanceof ErrorResponse) {
                if (! $silent) {
                    Toaster::error(sprintf('request error : %s', $response->message));
                }

                return;
            }

            $this->purchaseRequest->update([
                'status' => $response->status,
            ]);

            if (! $silent) {
                Toaster::success('status updated');
            }

        } catch (PaymentException $error) {
            if (! $silent) {
                Toaster::error('payment error');
            }
        } catch (PayChanguIntegrationException $error) {
            if (! $silent) {
                Toaster::error('third party error');
            }
        }
    }
}
