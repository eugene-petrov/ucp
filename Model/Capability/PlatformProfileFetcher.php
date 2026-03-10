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
     * @var array<string, array<mixed>|null>
     */
    private array $profileCache = [];

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
     * Delegates to fetchProfile so that a single HTTP request serves both callers (#6).
     * Returns [] on failure (fail-open).
     *
     * @param string $profileUri
     * @return string[]
     */
    public function fetchCapabilityNames(string $profileUri): array
    {
        $profile = $this->fetchProfile($profileUri);
        return $profile !== null ? array_keys($profile['ucp']['capabilities'] ?? []) : [];
    }

    /**
     * Fetch raw platform profile JSON or null on failure (fail-open).
     *
     * @param string $profileUri
     * @return array<mixed>|null
     */
    public function fetchProfile(string $profileUri): ?array
    {
        if (array_key_exists($profileUri, $this->profileCache)) {
            return $this->profileCache[$profileUri];
        }
        $data = $this->doFetch($profileUri);
        $this->profileCache[$profileUri] = $data;
        return $data;
    }

    /**
     * Perform the HTTP fetch and JSON decode. Returns null on any failure.
     *
     * @param string $uri
     * @return array<mixed>|null
     */
    private function doFetch(string $uri): ?array
    {
        if (strncasecmp($uri, 'https://', 8) !== 0) {
            $this->logger->warning('UCP: rejecting non-HTTPS platform profile URI', ['uri' => $uri]);
            return null;
        }
        try {
            $this->curl->setTimeout(3);
            $this->curl->get($uri);
            $body = $this->curl->getBody();
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->logger->warning('UCP: failed to fetch platform profile', [
                'uri' => $uri, 'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
