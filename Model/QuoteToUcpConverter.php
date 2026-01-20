<?php
/**
 * Quote to UCP Converter
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\Data\BuyerInterface;
use Aeqet\Ucp\Api\Data\BuyerInterfaceFactory;
use Aeqet\Ucp\Api\Data\CapabilityInterfaceFactory;
use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Api\Data\CheckoutSessionInterfaceFactory;
use Aeqet\Ucp\Api\Data\FulfillmentOptionInterface;
use Aeqet\Ucp\Api\Data\FulfillmentOptionInterfaceFactory;
use Aeqet\Ucp\Api\Data\ItemDataInterfaceFactory;
use Aeqet\Ucp\Api\Data\LineItemInterface;
use Aeqet\Ucp\Api\Data\LineItemInterfaceFactory;
use Aeqet\Ucp\Api\Data\LinkInterface;
use Aeqet\Ucp\Api\Data\LinkInterfaceFactory;
use Aeqet\Ucp\Api\Data\PaymentHandlerInterfaceFactory;
use Aeqet\Ucp\Api\Data\PaymentInterface;
use Aeqet\Ucp\Api\Data\PaymentInterfaceFactory;
use Aeqet\Ucp\Api\Data\TotalInterface;
use Aeqet\Ucp\Api\Data\TotalInterfaceFactory;
use Aeqet\Ucp\Api\Data\UcpMetaInterface;
use Aeqet\Ucp\Api\Data\UcpMetaInterfaceFactory;
use DateTime;
use DateTimeZone;
use Exception;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class QuoteToUcpConverter
{
    private const UCP_VERSION = '2026-01-11';
    private const CAPABILITY_CHECKOUT = 'dev.ucp.shopping.checkout';
    private const CAPABILITY_CATALOG = 'dev.ucp.shopping.catalog';
    private const PAYMENT_HANDLER_DELEGATE = 'dev.ucp.delegate_payment';

    /**
     * Constructor
     *
     * @param CheckoutSessionInterfaceFactory $checkoutSessionFactory
     * @param LineItemInterfaceFactory $lineItemFactory
     * @param ItemDataInterfaceFactory $itemDataFactory
     * @param TotalInterfaceFactory $totalFactory
     * @param BuyerInterfaceFactory $buyerFactory
     * @param PaymentInterfaceFactory $paymentFactory
     * @param PaymentHandlerInterfaceFactory $paymentHandlerFactory
     * @param LinkInterfaceFactory $linkFactory
     * @param UcpMetaInterfaceFactory $ucpMetaFactory
     * @param CapabilityInterfaceFactory $capabilityFactory
     * @param FulfillmentOptionInterfaceFactory $fulfillmentOptionFactory
     * @param StoreManagerInterface $storeManager
     * @param ImageHelper $imageHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CheckoutSessionInterfaceFactory $checkoutSessionFactory,
        private readonly LineItemInterfaceFactory $lineItemFactory,
        private readonly ItemDataInterfaceFactory $itemDataFactory,
        private readonly TotalInterfaceFactory $totalFactory,
        private readonly BuyerInterfaceFactory $buyerFactory,
        private readonly PaymentInterfaceFactory $paymentFactory,
        private readonly PaymentHandlerInterfaceFactory $paymentHandlerFactory,
        private readonly LinkInterfaceFactory $linkFactory,
        private readonly UcpMetaInterfaceFactory $ucpMetaFactory,
        private readonly CapabilityInterfaceFactory $capabilityFactory,
        private readonly FulfillmentOptionInterfaceFactory $fulfillmentOptionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ImageHelper $imageHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Convert Magento Quote to UCP Checkout Session
     *
     * @param CartInterface $quote
     * @param string $maskedId
     * @return CheckoutSessionInterface
     */
    public function convert(CartInterface $quote, string $maskedId): CheckoutSessionInterface
    {
        $session = $this->checkoutSessionFactory->create();

        $session->setId('ucp_' . $maskedId);
        $session->setStatus($this->determineStatus($quote));
        $session->setCurrency($quote->getQuoteCurrencyCode() ?: 'USD');
        $session->setExpiresAt($this->getExpiresAt());
        $session->setUcp($this->createUcpMeta());
        $session->setLineItems($this->createLineItems($quote));
        $session->setTotals($this->createTotals($quote));
        $session->setBuyer($this->createBuyer($quote));
        $session->setPayment($this->createPayment());
        $session->setFulfillmentOptions($this->createFulfillmentOptions($quote));
        $session->setLinks($this->createLinks($maskedId));
        $session->setMessages([]);

        return $session;
    }

    /**
     * Determine checkout session status based on quote state
     *
     * @param CartInterface $quote
     * @return string
     */
    private function determineStatus(CartInterface $quote): string
    {
        $hasEmail = !empty($quote->getCustomerEmail());
        $hasBillingAddress = $quote->getBillingAddress() && $quote->getBillingAddress()->getStreetLine(1);
        $hasShippingAddress = !$quote->isVirtual() &&
            $quote->getShippingAddress() &&
            $quote->getShippingAddress()->getStreetLine(1);
        $hasShippingMethod = !$quote->isVirtual() &&
            $quote->getShippingAddress() &&
            $quote->getShippingAddress()->getShippingMethod();

        if ($quote->isVirtual()) {
            if ($hasEmail && $hasBillingAddress) {
                return CheckoutSessionInterface::STATUS_READY_FOR_COMPLETE;
            }
        } else {
            if ($hasEmail && $hasBillingAddress && $hasShippingAddress && $hasShippingMethod) {
                return CheckoutSessionInterface::STATUS_READY_FOR_COMPLETE;
            }
        }

        return CheckoutSessionInterface::STATUS_INCOMPLETE;
    }

    /**
     * Get expiration datetime (6 hours from now)
     *
     * @return string
     */
    private function getExpiresAt(): string
    {
        $expiresAt = new DateTime('+6 hours', new DateTimeZone('UTC'));
        return $expiresAt->format(DateTime::RFC3339);
    }

    /**
     * Create UCP meta information
     *
     * @return UcpMetaInterface
     */
    private function createUcpMeta(): UcpMetaInterface
    {
        $checkoutCapability = $this->capabilityFactory->create();
        $checkoutCapability->setName(self::CAPABILITY_CHECKOUT);
        $checkoutCapability->setVersion(self::UCP_VERSION);

        $catalogCapability = $this->capabilityFactory->create();
        $catalogCapability->setName(self::CAPABILITY_CATALOG);
        $catalogCapability->setVersion(self::UCP_VERSION);

        $ucpMeta = $this->ucpMetaFactory->create();
        $ucpMeta->setVersion(self::UCP_VERSION);
        $ucpMeta->setCapabilities([$checkoutCapability, $catalogCapability]);

        return $ucpMeta;
    }

    /**
     * Create line items from quote items
     *
     * @param CartInterface $quote
     * @return LineItemInterface[]
     */
    private function createLineItems(CartInterface $quote): array
    {
        $lineItems = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            $itemData = $this->itemDataFactory->create();
            $itemData->setId('product_' . $product->getId());
            $itemData->setTitle($item->getName());
            $itemData->setPrice($this->toCents((float) $item->getPrice()));

            try {
                $imageUrl = $this->imageHelper->init($product, 'product_thumbnail_image')
                    ->setImageFile($product->getThumbnail())
                    ->getUrl();
                $itemData->setImageUrl($imageUrl);
            } catch (Exception $e) {
                $this->logger->debug('Unable to get product thumbnail image', [
                    'product_id' => $product->getId(),
                    'exception' => $e->getMessage()
                ]);
            }

            $lineItemTotal = $this->totalFactory->create();
            $lineItemTotal->setType(TotalInterface::TYPE_SUBTOTAL);
            $lineItemTotal->setAmount($this->toCents((float) $item->getRowTotal()));

            $lineItem = $this->lineItemFactory->create();
            $lineItem->setId('line_item_' . $item->getId());
            $lineItem->setItem($itemData);
            $lineItem->setQuantity((int) $item->getQty());
            $lineItem->setTotals([$lineItemTotal]);

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * Create totals from quote
     *
     * @param CartInterface $quote
     * @return TotalInterface[]
     */
    private function createTotals(CartInterface $quote): array
    {
        $totals = [];

        $subtotal = $this->totalFactory->create();
        $subtotal->setType(TotalInterface::TYPE_SUBTOTAL);
        $subtotal->setAmount($this->toCents((float) $quote->getSubtotal()));
        $subtotal->setDisplayText('Subtotal');
        $totals[] = $subtotal;

        $discountAmount = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $discountAmount += (float) $item->getDiscountAmount();
        }
        if ($discountAmount > 0) {
            $discount = $this->totalFactory->create();
            $discount->setType(TotalInterface::TYPE_DISCOUNT);
            $discount->setAmount(-$this->toCents($discountAmount));
            $discount->setDisplayText('Discount');
            $totals[] = $discount;
        }

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $shippingAmount = (float) $quote->getShippingAddress()->getShippingAmount();
            if ($shippingAmount > 0) {
                $shipping = $this->totalFactory->create();
                $shipping->setType(TotalInterface::TYPE_FULFILLMENT);
                $shipping->setAmount($this->toCents($shippingAmount));
                $shipping->setDisplayText('Shipping');
                $totals[] = $shipping;
            }
        }

        $taxAmount = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $taxAmount += (float) $item->getTaxAmount();
        }
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $taxAmount += (float) $quote->getShippingAddress()->getTaxAmount();
        }
        if ($taxAmount > 0) {
            $tax = $this->totalFactory->create();
            $tax->setType(TotalInterface::TYPE_TAX);
            $tax->setAmount($this->toCents($taxAmount));
            $tax->setDisplayText('Tax');
            $totals[] = $tax;
        }

        $total = $this->totalFactory->create();
        $total->setType(TotalInterface::TYPE_TOTAL);
        $total->setAmount($this->toCents((float) $quote->getGrandTotal()));
        $total->setDisplayText('Total');
        $totals[] = $total;

        return $totals;
    }

    /**
     * Create buyer from quote
     *
     * @param CartInterface $quote
     * @return BuyerInterface|null
     */
    private function createBuyer(CartInterface $quote): ?BuyerInterface
    {
        $email = $quote->getCustomerEmail();
        $firstName = $quote->getCustomerFirstname();
        $lastName = $quote->getCustomerLastname();

        if ($billingAddress = $quote->getBillingAddress()) {
            $email = $email ?: $billingAddress->getEmail();
            $firstName = $firstName ?: $billingAddress->getFirstname();
            $lastName = $lastName ?: $billingAddress->getLastname();
        }

        if (!$email && !$firstName && !$lastName) {
            return null;
        }

        $buyer = $this->buyerFactory->create();
        $buyer->setEmail($email);
        $buyer->setFirstName($firstName);
        $buyer->setLastName($lastName);

        if ($billingAddress && $billingAddress->getTelephone()) {
            $buyer->setPhoneNumber($billingAddress->getTelephone());
        }

        return $buyer;
    }

    /**
     * Create payment section with default delegate handler
     *
     * @return PaymentInterface
     */
    private function createPayment(): PaymentInterface
    {
        $handler = $this->paymentHandlerFactory->create();
        $handler->setId('handler_1');
        $handler->setName(self::PAYMENT_HANDLER_DELEGATE);
        $handler->setVersion(self::UCP_VERSION);
        $handler->setConfig([]);

        $payment = $this->paymentFactory->create();
        $payment->setHandlers([$handler]);

        return $payment;
    }

    /**
     * Create links for the session
     *
     * @param string $maskedId
     * @return LinkInterface[]
     */
    private function createLinks(string $maskedId): array
    {
        $links = [];
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        $selfLink = $this->linkFactory->create();
        $selfLink->setRel(LinkInterface::REL_SELF);
        $selfLink->setHref($baseUrl . 'rest/V1/ucp/checkout/ucp_' . $maskedId);
        $links[] = $selfLink;

        $tosLink = $this->linkFactory->create();
        $tosLink->setRel(LinkInterface::REL_TERMS_OF_SERVICE);
        $tosLink->setHref($baseUrl . 'terms');
        $links[] = $tosLink;

        $privacyLink = $this->linkFactory->create();
        $privacyLink->setRel(LinkInterface::REL_PRIVACY_POLICY);
        $privacyLink->setHref($baseUrl . 'privacy');
        $links[] = $privacyLink;

        return $links;
    }

    /**
     * Create fulfillment options from quote shipping rates
     *
     * @param CartInterface $quote
     * @return FulfillmentOptionInterface[]
     */
    private function createFulfillmentOptions(CartInterface $quote): array
    {
        $options = [];

        if ($quote->isVirtual()) {
            return $options; // No shipping for virtual products
        }

        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress) {
            return $options;
        }

        $shippingAddress->collectShippingRates();

        $currentShippingMethod = $shippingAddress->getShippingMethod();

        foreach ($shippingAddress->getAllShippingRates() as $rate) {
            $option = $this->fulfillmentOptionFactory->create();
            $option->setId($rate->getCode());
            $option->setType('shipping');

            $carrierTitle = $rate->getCarrierTitle() ?: $rate->getCarrier();
            $methodTitle = $rate->getMethodTitle() ?: $rate->getMethod();
            $option->setDisplayName($methodTitle . ' - ' . $carrierTitle);

            $option->setPrice($this->toCents((float) $rate->getPrice()));
            $option->setIsSelected($rate->getCode() === $currentShippingMethod);

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Convert dollars to cents
     *
     * @param float $amount
     * @return int
     */
    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
