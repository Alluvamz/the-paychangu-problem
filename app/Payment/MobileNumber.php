<?php

declare(strict_types=1);

namespace App\Payment;

use App\Enums\LocalMobileOperator;

class MobileNumber
{
    public function __construct(
        public readonly LocalMobileOperator $operator,
        public string $local,
        public string $international,
        public string $clean,
    ) {
    }

    public static function makeFromArray(array $data): self
    {
        $operator = $data['operator'] instanceof LocalMobileOperator ? $data['operator'] : LocalMobileOperator::from($data['operator']);

        return new static(
            $operator,
            $data['local'],
            $data['international'],
            $data['clean'],
        );
    }

    public static function makeFromPhoneNumber(string $phone): ?self
    {
        $normalizedPhoneNumber = LocalMobileOperator::normalizeMalawiPhone($phone);

        if (! $normalizedPhoneNumber) {
            return null;
        }

        if ($normalizedPhoneNumber['operator'] === null) {
            return null;
        }

        return new static(
            local: $normalizedPhoneNumber['local'],
            international: $normalizedPhoneNumber['international'],
            operator: $normalizedPhoneNumber['operator'],
            clean: $normalizedPhoneNumber['simple'],
        );
    }

    public function toArray(): array
    {
        return [
            'operator' => $this->operator->value,
            'local' => $this->local,
            'clean' => $this->clean,
            'international' => $this->international,
        ];
    }
}
