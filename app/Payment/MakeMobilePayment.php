<?php

declare(strict_types=1);

namespace App\Payment;

use Alluvamz\PayChanguMobile\Data\Request\ChargeMobileRequestData;
use Alluvamz\PayChanguMobile\Data\ResponseData\DirectChargeResponseData;
use Alluvamz\PayChanguMobile\PayChanguIntegration;

class MakeMobilePayment
{
    public function __construct(
        private readonly PayChanguIntegration $payChanguIntegration
    ) {}

    public function execute(string $chargeId, string|int|float $amount, string $mobile): DirectChargeResponseData
    {

        $mobileNumber = MobileNumber::makeFromPhoneNumber($mobile);

        if (! $mobileNumber) {
            throw new PaymentException('could not parse phone number');
        }

        $mobileOperator = $mobileNumber->operator->getPaymentMobileOperator($this->payChanguIntegration);

        if (! $mobileOperator) {
            throw new PaymentException(sprintf('could not find mobile operator %s, try again', $mobileNumber->operator->value));
        }

        $reponse = $this->payChanguIntegration->makeDirectCharge(new ChargeMobileRequestData(
            chargeId: $chargeId,
            mobile: $mobileNumber->clean,
            amount: sprintf('%s', $amount),
            mobileMoneyOperatorRefId: $mobileOperator->refId
        ));

        if ($reponse instanceof ErrorResponse) {
            throw new PaymentException($reponse->message);
        }

        $reponse = $this->payChanguIntegration->getDirectChargeStatus($chargeId);

        if ($reponse instanceof ErrorResponse) {
            throw new PaymentException($reponse->message);
        }

        return $reponse->getData(DirectChargeResponseData::class);
    }
}
