<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Utils;

class ReverseDomainResolver
{
    /**
     * Convert a base URL to its reverse domain notation.
     *
     * @param string $baseUrl
     * @return string
     */
    public function getReverseDomain(string $baseUrl): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $host = parse_url($baseUrl, PHP_URL_HOST) ?? '';
        $host = (string) preg_replace('/^www\./', '', $host);
        $host = explode(':', $host)[0];
        return implode('.', array_reverse(explode('.', $host)));
    }
}
