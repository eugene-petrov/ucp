<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Utils;

trait MoneyTrait
{
    /**
     * Convert float amount to integer cents
     *
     * @param float $amount
     * @return int
     */
    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
