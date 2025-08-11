<?php

declare(strict_types=1);

namespace App\Payment;

use Alluvamz\PayChanguMobile\Data\MobileOperator;
use Alluvamz\PayChanguMobile\Data\MobileOperatorRepository;
use Illuminate\Support\Facades\Cache;

class PaymentMobileOperatorCache
{
    private const string cache_key = '_paychangu_mobile_operators';

    public function __construct(private readonly MobileOperatorRepository $mobileOperatorRepository)
    {
    }

    public function getAll()
    {
        if (Cache::has(self::cache_key)) {
            $data = Cache::get(self::cache_key);
            $data = collect($data)->map(fn (array $data) => MobileOperator::makeFromArray($data));

            return $data;
        }

        $records = collect($this->mobileOperatorRepository->all())->map(fn ($v) => $v->toArray());

        Cache::put(self::cache_key, $records->toArray());

        $data = collect($records)->map(fn (array $data) => MobileOperator::makeFromArray($data));

        return $data;
    }

    public function findByShortCode(string $shortCode): ?MobileOperator
    {
        return $this->getAll()->first(fn ($v) => mb_strtolower($v->shortCode) === mb_strtolower($shortCode));
    }

    public function clear(): void
    {
        Cache::forget(self::cache_key);
    }
}
