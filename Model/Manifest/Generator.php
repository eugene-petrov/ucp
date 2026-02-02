<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Manifest;

use Aeqet\Ucp\Api\ManifestGeneratorInterface;
use Aeqet\Ucp\Model\Config\Config;
use Aeqet\Ucp\Model\Security\KeyManager;
use Magento\Store\Model\StoreManagerInterface;

class Generator implements ManifestGeneratorInterface
{
    private const UCP_VERSION = '2026-01-23';
    private const UCP_SPEC_BASE = 'https://ucp.dev/specification/capabilities/';
    private const UCP_SCHEMA_BASE = 'https://ucp.dev/schemas/';

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
                'version' => self::UCP_VERSION,
                'services' => [
                    'dev.ucp.shopping' => [
                        'version' => self::UCP_VERSION,
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
     * Build capabilities array for manifest
     *
     * @return array
     */
    private function buildCapabilities(): array
    {
        $capabilities = [];

        if ($this->config->isCheckoutCapabilityEnabled()) {
            $capabilities[] = $this->createCapability('checkout');
            $capabilities[] = $this->createCapability('cart');
        }

        if ($this->config->isCatalogCapabilityEnabled()) {
            $capabilities[] = $this->createCapability('catalog');
        }

        return $capabilities;
    }

    /**
     * Create a capability entry
     *
     * @param string $name
     * @return array
     */
    private function createCapability(string $name): array
    {
        return [
            'name' => 'dev.ucp.shopping.' . $name,
            'version' => self::UCP_VERSION,
            'spec' => self::UCP_SPEC_BASE . $name,
            'schema' => self::UCP_SCHEMA_BASE . $name . '.json',
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
