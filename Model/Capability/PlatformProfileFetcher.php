<?php
/**
 * Platform Profile Fetcher
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Capability;

use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Fetches UCP platform capability profiles from remote URIs.
 */
class PlatformProfileFetcher
{
    /**
     * @var array<string, string[]>
     */
    private array $cache = [];

    /**
     * Constructor
     *
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Fetch platform profile and return its capability names.
     *
     * Returns [] on failure (fail-open).
     *
     * @param string $profileUri
     * @return string[]
     */
    public function fetchCapabilityNames(string $profileUri): array
    {
        if (isset($this->cache[$profileUri])) {
            return $this->cache[$profileUri];
        }
        if (strncasecmp($profileUri, 'https://', 8) !== 0) {
            $this->logger->warning('UCP: rejecting non-HTTPS platform profile URI', ['uri' => $profileUri]);
            return [];
        }
        try {
            $this->curl->setTimeout(3);
            $this->curl->get($profileUri);
            $body = $this->curl->getBody();
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $names = array_keys($data['ucp']['capabilities'] ?? []);
        } catch (Throwable $e) {
            $this->logger->warning('UCP: failed to fetch platform profile', [
                'uri' => $profileUri, 'error' => $e->getMessage()
            ]);
            $names = [];
        }
        $this->cache[$profileUri] = $names;
        return $names;
    }
}
