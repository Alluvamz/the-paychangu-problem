<?php

namespace App\Livewire\Pages;

use Alluvamz\PayChanguMobile\PayChanguIntegration;
use Alluvamz\PayChanguMobile\PayChanguIntegrationException;
use Alluvamz\PayChanguMobile\Response\ErrorResponse;
use App\Models\PurchaseRequest;
use App\Payment\MakeMobilePayment;
use App\Payment\MobileNumber;
use App\Payment\PaymentException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationData;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Dashboard extends Component
{
    public string $chargeId = '';
    public string $title = '';

    public string $price = '';
    public string $phoneNumber = '';
    public string $apiKey = '';

    public function render()
    {
        $userId = auth()->id();
        
        return view('livewire.pages.dashboard', [
            'requests' => PurchaseRequest::query()
                ->where('user_id', $userId)
                ->latest()
                ->get()
        ]);
    }


    public function handleSubmit(): void
    {
        $this->validate([
            'chargeId' => ['required', 'string', 'max:255', 'min:6', Rule::unique(PurchaseRequest::class, 'charge_id')],
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'phoneNumber' => ['required', 'string', function (string $attribute, mixed $value, $fail): void {
                $mobile = MobileNumber::makeFromPhoneNumber($value);
                if ($mobile === null) {
                    $fail(sprintf('The mobile number is invalid make sure it is airtel(09*) or tnm(08*) based phone number'));
                }
            },],
            'apiKey' => ['required', 'string'],
        ]);

        try {
            $response = $this->makePayment(
                $this->apiKey,
                $this->chargeId,
                $this->phoneNumber,
                $this->price,
            );
        } catch (PaymentException $error) {
            Toaster::error("payment error");

            throw ValidationException::withMessages([
                'apiKey' => sprintf('payment error : %s', $error->getMessage()),
            ]);
        } catch (PayChanguIntegrationException $error) {
            Toaster::error("third party error");

            throw ValidationException::withMessages([
                'apiKey' => sprintf('third party error : %s', $error->getMessage()),
            ]);
        }

        $userId = auth()->id();

        PurchaseRequest::query()->create([
            'user_id' => $userId,
            'charge_id' => $this->chargeId,
            'price' => $this->price,
            'title' => $this->title,
            'status' => $response->status->value,
            'phone_number' => $this->phoneNumber
        ]);

        $this->reset();

        Toaster::success("purchase request created");
    }

    public function generateChargeId()
    {
        $this->chargeId = Str::uuid()->toString();
    }


    private function makePayment(
        string $apiKey,
        string $chargeId,
        string $phoneNumber,
        string $price
    ) {

        $response = (new MakeMobilePayment(new PayChanguIntegration($apiKey)))
            ->execute($chargeId, $price, $phoneNumber);

        if ($response instanceof ErrorResponse) {
            throw ValidationException::withMessages([
                'chargeId' => $response->message,
            ]);
        }

        return $response;
    }

    public function refreshPurchaseStatus(PurchaseRequest $purchaseRequest)
    {
        $this->validate([
            'apiKey' => ['required', 'string']
        ]);

        // Ensure the purchase request belongs to the current user
        $userId = auth()->id();
        if ($purchaseRequest->user_id !== $userId) {
            Toaster::error("Unauthorized access");
            return;
        }

        try {
            $response = (new PayChanguIntegration($this->apiKey))
                ->getDirectChargeDetails($purchaseRequest->charge_id);
        } catch (PaymentException $error) {
            Toaster::error("payment error");

            throw ValidationException::withMessages([
                'apiKey' => sprintf('payment error : %s', $error->getMessage()),
            ]);
        } catch (PayChanguIntegrationException $error) {
            Toaster::error("third party error");

            throw ValidationException::withMessages([
                'apiKey' => sprintf('third party error : %s', $error->getMessage()),
            ]);
        }

        if ($response instanceof ErrorResponse) {
            throw ValidationException::withMessages([
                'apiKey' => sprintf('request error : %s', $response->message),
            ]);
        }

        $purchaseRequest->update([
            'status' => $response->status,
        ]);

        Toaster::success("status updated");
    }

    public function verifyPurchase(PurchaseRequest $purchaseRequest)
    {
        $this->validate([
            'apiKey' => ['required', 'string']
        ]);

        // Ensure the purchase request belongs to the current user
        $userId = auth()->id();
        if ($purchaseRequest->user_id !== $userId) {
            Toaster::error("Unauthorized access");
            return;
        }

        try {
            $response = (new PayChanguIntegration($this->apiKey))
                ->getDirectChargeStatus($purchaseRequest->charge_id);
        } catch (PaymentException $error) {
            Toaster::error("payment error");

            throw ValidationException::withMessages([
                'apiKey' => sprintf('payment error : %s', $error->getMessage()),
            ]);
        } catch (PayChanguIntegrationException $error) {
            Toaster::error("third party error");

            throw ValidationException::withMessages([
                'apiKey' => sprintf('third party error : %s', $error->getMessage()),
            ]);
        }

        if ($response instanceof ErrorResponse) {
            throw ValidationException::withMessages([
                'apiKey' => sprintf('request error : %s', $response->message),
            ]);
        }

        $purchaseRequest->update([
            'status' => $response->status,
        ]);

        Toaster::success("status verified");
    }

    public function deletePurchase(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();
    }

}
