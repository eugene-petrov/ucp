<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Manifest;

use Aeqet\Ucp\Api\ManifestGeneratorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Generator implements ManifestGeneratorInterface
{
    private const UCP_VERSION = '2026-01-11';
    private const UCP_SPEC_BASE = 'https://ucp.dev/specification/capabilities/';
    private const UCP_SCHEMA_BASE = 'https://ucp.dev/schemas/';

    private const CONFIG_PATH_BASE_URL = 'aeqet_ucp/manifest/base_url';
    private const CONFIG_PATH_API_ENDPOINT = 'aeqet_ucp/manifest/api_endpoint';
    private const CONFIG_PATH_CHECKOUT_ENABLED = 'aeqet_ucp/capabilities/checkout';
    private const CONFIG_PATH_CATALOG_ENABLED = 'aeqet_ucp/capabilities/catalog';

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig
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
            'signing_keys' => [],
        ];
    }

    /**
     * Get base URL for manifest
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        $configuredUrl = $this->scopeConfig->getValue(
            self::CONFIG_PATH_BASE_URL,
            ScopeInterface::SCOPE_STORE
        );

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
        $configuredEndpoint = $this->scopeConfig->getValue(
            self::CONFIG_PATH_API_ENDPOINT,
            ScopeInterface::SCOPE_STORE
        );

        if (!empty($configuredEndpoint)) {
            return $baseUrl . '/' . ltrim($configuredEndpoint, '/');
        }

        return $baseUrl . '/rest/V1/ucp';
    }

    /**
     * Build capabilities array for manifest
     *
     * @return array
     */
    private function buildCapabilities(): array
    {
        $capabilities = [];

        if ($this->isCheckoutEnabled()) {
            $capabilities[] = $this->createCapability('checkout');
            $capabilities[] = $this->createCapability('cart');
        }

        if ($this->isCatalogEnabled()) {
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
     * Check if checkout capability is enabled
     *
     * @return bool
     */
    private function isCheckoutEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_CHECKOUT_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if catalog capability is enabled
     *
     * @return bool
     */
    private function isCatalogEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_CATALOG_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
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
                'type' => 'delegated',
                'name' => 'Merchant Checkout',
                'url' => $baseUrl . '/checkout',
            ],
        ];
    }
}
