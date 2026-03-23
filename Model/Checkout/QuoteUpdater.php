<?php
/**
 * UCP Quote Updater — applies buyer/address/shipping changes to a Magento quote
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Checkout;

use Aeqet\Ucp\Api\Data\AddressInterface;
use Aeqet\Ucp\Api\Data\BuyerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;

class QuoteUpdater
{
    /**
     * Apply all pending updates (buyer, address, shipping) to the quote.
     *
     * @param CartInterface $quote
     * @param BuyerInterface|null $buyer
     * @param AddressInterface|null $fulfillmentAddress
     * @param string|null $selectedFulfillmentId
     * @return void
     */
    public function apply(
        CartInterface $quote,
        ?BuyerInterface $buyer,
        ?AddressInterface $fulfillmentAddress,
        ?string $selectedFulfillmentId
    ): void {
        if ($buyer) {
            $this->applyBuyer($buyer, $quote);
        }
        if ($fulfillmentAddress) {
            $this->applyFulfillmentAddress($fulfillmentAddress, $quote);
        }
        if ($selectedFulfillmentId) {
            $this->applyShippingMethod($selectedFulfillmentId, $quote);
        }
    }

    /**
     * Apply buyer data to quote and billing address.
     *
     * @param BuyerInterface $buyer
     * @param CartInterface $quote
     * @return void
     */
    private function applyBuyer(BuyerInterface $buyer, CartInterface $quote): void
    {
        if ($buyer->getEmail()) {
            $quote->setCustomerEmail($buyer->getEmail());
        }
        if ($buyer->getFirstName()) {
            $quote->setCustomerFirstname($buyer->getFirstName());
        }
        if ($buyer->getLastName()) {
            $quote->setCustomerLastname($buyer->getLastName());
        }
        $billing = $quote->getBillingAddress();
        if (!$billing) {
            return;
        }
        if ($buyer->getEmail()) {
            $billing->setEmail($buyer->getEmail());
        }
        if ($buyer->getFirstName()) {
            $billing->setFirstname($buyer->getFirstName());
        }
        if ($buyer->getLastName()) {
            $billing->setLastname($buyer->getLastName());
        }
        if ($buyer->getPhoneNumber()) {
            $billing->setTelephone($buyer->getPhoneNumber());
        }
    }

    /**
     * Apply fulfillment address to quote shipping (and optionally billing) address.
     *
     * @param AddressInterface $addr
     * @param CartInterface $quote
     * @return void
     */
    private function applyFulfillmentAddress(AddressInterface $addr, CartInterface $quote): void
    {
        $shipping = $quote->getShippingAddress();
        $shipping->setFirstname($addr->getFirstName() ?? '');
        $shipping->setLastname($addr->getLastName() ?? '');
        $shipping->setStreet(array_values(array_filter([$addr->getStreetLine1(), $addr->getStreetLine2()])));
        $shipping->setCity($addr->getCity() ?? '');
        $shipping->setRegion($addr->getState() ?? '');
        $shipping->setPostcode($addr->getPostalCode() ?? '');
        $shipping->setCountryId($addr->getCountryCode() ?? '');
        if ($addr->getPhone()) {
            $shipping->setTelephone($addr->getPhone());
        }
        $shipping->setSameAsBilling(0);
        $shipping->setCollectShippingRates(true);
        $billing = $quote->getBillingAddress();
        if ($billing && !$billing->getStreetLine(1)) {
            $this->copyAddressFieldsToBilling($addr, $billing);
        }
    }

    /**
     * Validate and apply the shipping method to the quote.
     *
     * @param string $shippingMethod
     * @param CartInterface $quote
     * @return void
     * @throws LocalizedException
     */
    private function applyShippingMethod(string $shippingMethod, CartInterface $quote): void
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $validCodes = [];
        foreach ($shippingAddress->getAllShippingRates() as $rate) {
            $validCodes[] = $rate->getCode();
        }
        if (!in_array($shippingMethod, $validCodes, true)) {
            throw new LocalizedException(
                __('The selected shipping method "%1" is not available.', $shippingMethod)
            );
        }
        $shippingAddress->setShippingMethod($shippingMethod);

        // Keep the shipping assignment in sync so ShippingMethodManagement::apply() fires during save
        $extensionAttributes = $quote->getExtensionAttributes();
        if ($extensionAttributes !== null) {
            $assignments = $extensionAttributes->getShippingAssignments() ?? [];
            if (!empty($assignments)) {
                $assignments[0]->getShipping()->setMethod($shippingMethod);
            }
        }
    }

    /**
     * Copy address fields to the billing address.
     *
     * @param AddressInterface $addr
     * @param object $billing
     * @return void
     */
    private function copyAddressFieldsToBilling(AddressInterface $addr, object $billing): void
    {
        $billing->setStreet(array_values(array_filter([$addr->getStreetLine1(), $addr->getStreetLine2()])));
        $billing->setCity($addr->getCity() ?? '');
        $billing->setRegion($addr->getState() ?? '');
        $billing->setPostcode($addr->getPostalCode() ?? '');
        $billing->setCountryId($addr->getCountryCode() ?? '');
    }
}
