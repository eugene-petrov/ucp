<?php
/**
 * UCP Configuration Helper
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    private const XML_PATH_ENABLED = 'aeqet_ucp/general/enabled';
    private const XML_PATH_BASE_URL = 'aeqet_ucp/manifest/base_url';
    private const XML_PATH_API_ENDPOINT = 'aeqet_ucp/manifest/api_endpoint';
    private const XML_PATH_CHECKOUT_CAPABILITY = 'aeqet_ucp/capabilities/checkout';
    private const XML_PATH_CATALOG_CAPABILITY = 'aeqet_ucp/capabilities/catalog';
    private const XML_PATH_PAYMENT_HANDLER_TYPE = 'aeqet_ucp/payment/handler_type';
    private const XML_PATH_PAYMENT_HANDLER_NAME = 'aeqet_ucp/payment/handler_name';
    private const XML_PATH_DEFAULT_PAYMENT_METHOD = 'aeqet_ucp/payment/default_payment_method';

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get base URL override
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getBaseUrl(?int $storeId = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: null;
    }

    /**
     * Get API endpoint path
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiEndpoint(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'rest/V1/ucp';
    }

    /**
     * Check if checkout capability is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCheckoutCapabilityEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CHECKOUT_CAPABILITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if catalog capability is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isCatalogCapabilityEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CATALOG_CAPABILITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get payment handler type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPaymentHandlerType(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_HANDLER_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'delegated';
    }

    /**
     * Get payment handler name
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPaymentHandlerName(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_HANDLER_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'Merchant Checkout';
    }

    /**
     * Get default payment method code for UCP checkout completion
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDefaultPaymentMethod(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_PAYMENT_METHOD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'checkmo';
    }
}
