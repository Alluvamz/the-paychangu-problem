<?php

declare(strict_types=1);

namespace App\Enums;

use Alluvamz\PayChanguMobile\Data\MobileOperator;
use Alluvamz\PayChanguMobile\Data\MobileOperatorRepository;
use Alluvamz\PayChanguMobile\PayChanguIntegration;
use App\Payment\PaymentMobileOperatorCache;


enum LocalMobileOperator: string
{
    case Airtel = 'airtel';
    case TNM = 'tnm';

    public static function normalizeMalawiPhone(string $phone): ?array
    {
        $clean = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($clean, '0') && mb_strlen($clean) === 10) {
            $local = $clean;
            $international = sprintf('+265%s', mb_substr($clean, 1));
        } elseif (str_starts_with($clean, '265') && mb_strlen($clean) === 12) {
            $local = sprintf('0%s', mb_substr($clean, 3));
            $international = sprintf('+%s', $clean);
        } else {
            return null;
        }

        $prefix = mb_substr($local, 0, 3);
        $operator = match ($prefix) {
            '099', '098' => self::Airtel,
            '088', '089' => self::TNM,
            default => null
        };

        return [
            'local' => $local,
            'international' => $international,
            'operator' => $operator,
            'simple' => mb_substr($local, 1),
        ];
    }

    public function getPaymentMobileOperator(PayChanguIntegration $provider): ?MobileOperator
    {
        return (new PaymentMobileOperatorCache(new MobileOperatorRepository($provider)))->findByShortCode($this->value);
    }


    public static function getDisplayData()
    {
        return [
            [
                'name' => 'Airtel Money',
                'value' => self::Airtel->value,
                'image' => asset('assets/images/brand/airtel_money.png'),
                'color' => '#e90000',
            ],
            [
                'name' => 'Tnm Mpamba',
                'value' => self::TNM->value,
                'image' => asset('assets/images/brand/tnm_mpamba.png'),
                'color' => '#002b5b',
            ]
        ];
    }
}
