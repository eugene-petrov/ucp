<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Manifest;

use Aeqet\Ucp\Api\ManifestGeneratorInterface;
use Aeqet\Ucp\Model\Config\Config;
use Aeqet\Ucp\Model\Security\KeyManager;
use Aeqet\Ucp\Model\Utils\UcpConstants;
use Magento\Store\Model\StoreManagerInterface;

class Generator implements ManifestGeneratorInterface
{
    private const UCP_SPEC_BASE = 'https://ucp.dev/specification/';

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param KeyManager $keyManager
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly KeyManager $keyManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function generate(): array
    {
        $baseUrl = $this->getBaseUrl();
        $apiEndpoint = $this->getApiEndpoint($baseUrl);

        return [
            'ucp' => [
                'version' => UcpConstants::UCP_VERSION,
                'services' => [
                    'dev.ucp.shopping' => [
                        'version' => UcpConstants::UCP_VERSION,
                        'rest' => [
                            'endpoint' => $apiEndpoint,
                            'schema' => $apiEndpoint . '/openapi.json',
                        ],
                    ],
                ],
                'capabilities' => $this->buildCapabilities(),
            ],
            'payment' => [
                'handlers' => $this->buildPaymentHandlers($baseUrl),
            ],
            'signing_keys' => $this->keyManager->getActivePublicKeysAsJwk(),
        ];
    }

    /**
     * Get base URL for manifest
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        $configuredUrl = $this->config->getBaseUrl();

        if (!empty($configuredUrl)) {
            return rtrim($configuredUrl, '/');
        }

        return rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
    }

    /**
     * Get API endpoint URL
     *
     * @param string $baseUrl
     * @return string
     */
    private function getApiEndpoint(string $baseUrl): string
    {
        $apiEndpoint = $this->config->getApiEndpoint();
        return $baseUrl . '/' . ltrim($apiEndpoint, '/');
    }

    /**
     * Build capabilities map for manifest
     *
     * Returns an associative array keyed by capability name, as required by the UCP spec.
     * Each value is an array containing one capability entry object.
     *
     * @return array<string, array>
     */
    private function buildCapabilities(): array
    {
        $capabilities = [];

        if ($this->config->isCheckoutCapabilityEnabled()) {
            $capabilities['dev.ucp.shopping.checkout'] = [$this->createCapability('checkout')];
            $capabilities['dev.ucp.shopping.cart'] = [$this->createCapability('cart')];
        }

        if ($this->config->isCatalogCapabilityEnabled()) {
            $capabilities['dev.ucp.shopping.catalog'] = [$this->createCapability('catalog')];
        }

        return $capabilities;
    }

    /**
     * Create a capability entry object
     *
     * The capability name is the map key in the manifest, not a field inside the entry.
     * Schema URL includes the spec version per https://ucp.dev/latest/specification/overview/
     *
     * @param string $name Capability short name (e.g. 'checkout', 'cart', 'catalog')
     * @return array
     */
    private function createCapability(string $name): array
    {
        return [
            'version' => UcpConstants::UCP_VERSION,
            'spec' => self::UCP_SPEC_BASE . $name,
            'schema' => 'https://ucp.dev/' . UcpConstants::UCP_VERSION . '/schemas/shopping/' . $name . '.json',
        ];
    }

    /**
     * Build payment handlers array for manifest
     *
     * @param string $baseUrl
     * @return array
     */
    private function buildPaymentHandlers(string $baseUrl): array
    {
        return [
            [
                'type' => $this->config->getPaymentHandlerType(),
                'name' => $this->config->getPaymentHandlerName(),
                'url' => $baseUrl . '/checkout',
            ],
        ];
    }
}
