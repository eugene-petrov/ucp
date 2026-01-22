<?php
/**
 * UCP Checkout Session Serializer
 *
 * Handles JSON serialization/deserialization of CheckoutSessionInterface objects
 * for database persistence.
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\Data\BuyerInterface;
use Aeqet\Ucp\Api\Data\BuyerInterfaceFactory;
use Aeqet\Ucp\Api\Data\CapabilityInterface;
use Aeqet\Ucp\Api\Data\CapabilityInterfaceFactory;
use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Api\Data\CheckoutSessionInterfaceFactory;
use Aeqet\Ucp\Api\Data\FulfillmentOptionInterface;
use Aeqet\Ucp\Api\Data\FulfillmentOptionInterfaceFactory;
use Aeqet\Ucp\Api\Data\ItemDataInterface;
use Aeqet\Ucp\Api\Data\ItemDataInterfaceFactory;
use Aeqet\Ucp\Api\Data\LineItemInterface;
use Aeqet\Ucp\Api\Data\LineItemInterfaceFactory;
use Aeqet\Ucp\Api\Data\LinkInterface;
use Aeqet\Ucp\Api\Data\LinkInterfaceFactory;
use Aeqet\Ucp\Api\Data\MessageInterface;
use Aeqet\Ucp\Api\Data\MessageInterfaceFactory;
use Aeqet\Ucp\Api\Data\OrderConfirmationInterface;
use Aeqet\Ucp\Api\Data\OrderConfirmationInterfaceFactory;
use Aeqet\Ucp\Api\Data\PaymentHandlerInterface;
use Aeqet\Ucp\Api\Data\PaymentHandlerInterfaceFactory;
use Aeqet\Ucp\Api\Data\PaymentInterface;
use Aeqet\Ucp\Api\Data\PaymentInterfaceFactory;
use Aeqet\Ucp\Api\Data\TotalInterface;
use Aeqet\Ucp\Api\Data\TotalInterfaceFactory;
use Aeqet\Ucp\Api\Data\UcpMetaInterface;
use Aeqet\Ucp\Api\Data\UcpMetaInterfaceFactory;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class CheckoutSessionSerializer
{
    /**
     * @param Json $json
     * @param CheckoutSessionInterfaceFactory $checkoutSessionFactory
     * @param UcpMetaInterfaceFactory $ucpMetaFactory
     * @param CapabilityInterfaceFactory $capabilityFactory
     * @param LineItemInterfaceFactory $lineItemFactory
     * @param ItemDataInterfaceFactory $itemDataFactory
     * @param TotalInterfaceFactory $totalFactory
     * @param BuyerInterfaceFactory $buyerFactory
     * @param PaymentInterfaceFactory $paymentFactory
     * @param PaymentHandlerInterfaceFactory $paymentHandlerFactory
     * @param LinkInterfaceFactory $linkFactory
     * @param MessageInterfaceFactory $messageFactory
     * @param OrderConfirmationInterfaceFactory $orderConfirmationFactory
     * @param FulfillmentOptionInterfaceFactory $fulfillmentOptionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Json $json,
        private readonly CheckoutSessionInterfaceFactory $checkoutSessionFactory,
        private readonly UcpMetaInterfaceFactory $ucpMetaFactory,
        private readonly CapabilityInterfaceFactory $capabilityFactory,
        private readonly LineItemInterfaceFactory $lineItemFactory,
        private readonly ItemDataInterfaceFactory $itemDataFactory,
        private readonly TotalInterfaceFactory $totalFactory,
        private readonly BuyerInterfaceFactory $buyerFactory,
        private readonly PaymentInterfaceFactory $paymentFactory,
        private readonly PaymentHandlerInterfaceFactory $paymentHandlerFactory,
        private readonly LinkInterfaceFactory $linkFactory,
        private readonly MessageInterfaceFactory $messageFactory,
        private readonly OrderConfirmationInterfaceFactory $orderConfirmationFactory,
        private readonly FulfillmentOptionInterfaceFactory $fulfillmentOptionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Serialize a CheckoutSessionInterface to JSON string
     *
     * @param CheckoutSessionInterface $session
     * @return string
     */
    public function serialize(CheckoutSessionInterface $session): string
    {
        $data = [
            'id' => $session->getId(),
            'status' => $session->getStatus(),
            'currency' => $session->getCurrency(),
            'expires_at' => $session->getExpiresAt(),
            'ucp' => $session->getUcp() ? $this->serializeUcpMeta($session->getUcp()) : null,
            'line_items' => $this->serializeLineItems($session->getLineItems()),
            'totals' => $this->serializeTotals($session->getTotals()),
            'buyer' => $session->getBuyer() ? $this->serializeBuyer($session->getBuyer()) : null,
            'payment' => $session->getPayment() ? $this->serializePayment($session->getPayment()) : null,
            'links' => $this->serializeLinks($session->getLinks()),
            'messages' => $session->getMessages() ? $this->serializeMessages($session->getMessages()) : null,
            'order' => $session->getOrder() ? $this->serializeOrderConfirmation($session->getOrder()) : null,
            'fulfillment_options' => $this->serializeFulfillmentOptions($session->getFulfillmentOptions()),
        ];

        return $this->json->serialize($data);
    }

    /**
     * Deserialize JSON string to CheckoutSessionInterface
     *
     * @param string $jsonString
     * @return CheckoutSessionInterface
     */
    public function deserialize(string $jsonString): CheckoutSessionInterface
    {
        try {
            $data = $this->json->unserialize($jsonString);
        } catch (Exception $e) {
            $this->logger->error('Failed to unserialize checkout session JSON', [
                'error' => $e->getMessage(),
                'json_preview' => substr($jsonString, 0, 200)
            ]);
            throw new InvalidArgumentException('Invalid JSON data for checkout session', 0, $e);
        }

        /** @var CheckoutSessionInterface $session */
        $session = $this->checkoutSessionFactory->create();

        $session->setId($data['id'] ?? '');
        $session->setStatus($data['status'] ?? CheckoutSessionInterface::STATUS_INCOMPLETE);
        $session->setCurrency($data['currency'] ?? 'USD');

        if (isset($data['expires_at'])) {
            $session->setExpiresAt($data['expires_at']);
        }

        if (isset($data['ucp'])) {
            $session->setUcp($this->deserializeUcpMeta($data['ucp']));
        }

        if (isset($data['line_items'])) {
            $session->setLineItems($this->deserializeLineItems($data['line_items']));
        }

        if (isset($data['totals'])) {
            $session->setTotals($this->deserializeTotals($data['totals']));
        }

        if (isset($data['buyer'])) {
            $session->setBuyer($this->deserializeBuyer($data['buyer']));
        }

        if (isset($data['payment'])) {
            $session->setPayment($this->deserializePayment($data['payment']));
        }

        if (isset($data['links'])) {
            $session->setLinks($this->deserializeLinks($data['links']));
        }

        if (isset($data['messages'])) {
            $session->setMessages($this->deserializeMessages($data['messages']));
        }

        if (isset($data['order'])) {
            $session->setOrder($this->deserializeOrderConfirmation($data['order']));
        }

        if (isset($data['fulfillment_options'])) {
            $session->setFulfillmentOptions($this->deserializeFulfillmentOptions($data['fulfillment_options']));
        }

        return $session;
    }

    /**
     * Serialize UcpMeta
     *
     * @param UcpMetaInterface $ucpMeta
     * @return array
     */
    private function serializeUcpMeta(UcpMetaInterface $ucpMeta): array
    {
        $capabilities = [];
        foreach ($ucpMeta->getCapabilities() as $capability) {
            $capabilities[] = [
                'name' => $capability->getName(),
                'version' => $capability->getVersion(),
            ];
        }

        return [
            'version' => $ucpMeta->getVersion(),
            'capabilities' => $capabilities,
        ];
    }

    /**
     * Deserialize UcpMeta
     *
     * @param array $data
     * @return UcpMetaInterface
     */
    private function deserializeUcpMeta(array $data): UcpMetaInterface
    {
        /** @var UcpMetaInterface $ucpMeta */
        $ucpMeta = $this->ucpMetaFactory->create();
        $ucpMeta->setVersion($data['version'] ?? '');

        $capabilities = [];
        foreach ($data['capabilities'] ?? [] as $capData) {
            /** @var CapabilityInterface $capability */
            $capability = $this->capabilityFactory->create();
            $capability->setName($capData['name'] ?? '');
            $capability->setVersion($capData['version'] ?? '');
            $capabilities[] = $capability;
        }
        $ucpMeta->setCapabilities($capabilities);

        return $ucpMeta;
    }

    /**
     * Serialize line items
     *
     * @param LineItemInterface[] $lineItems
     * @return array
     */
    private function serializeLineItems(array $lineItems): array
    {
        $result = [];
        foreach ($lineItems as $lineItem) {
            $item = $lineItem->getItem();
            $result[] = [
                'id' => $lineItem->getId(),
                'quantity' => $lineItem->getQuantity(),
                'parent_id' => $lineItem->getParentId(),
                'item' => [
                    'id' => $item->getId(),
                    'title' => $item->getTitle(),
                    'price' => $item->getPrice(),
                    'image_url' => $item->getImageUrl(),
                ],
                'totals' => $lineItem->getTotals() ? $this->serializeTotals($lineItem->getTotals()) : null,
            ];
        }
        return $result;
    }

    /**
     * Deserialize line items
     *
     * @param array $data
     * @return LineItemInterface[]
     */
    private function deserializeLineItems(array $data): array
    {
        $lineItems = [];
        foreach ($data as $lineItemData) {
            /** @var LineItemInterface $lineItem */
            $lineItem = $this->lineItemFactory->create();
            $lineItem->setId($lineItemData['id'] ?? '');
            $lineItem->setQuantity($lineItemData['quantity'] ?? 1);

            if (isset($lineItemData['parent_id'])) {
                $lineItem->setParentId($lineItemData['parent_id']);
            }

            if (isset($lineItemData['item'])) {
                /** @var ItemDataInterface $itemData */
                $itemData = $this->itemDataFactory->create();
                $itemData->setId($lineItemData['item']['id'] ?? '');
                $itemData->setTitle($lineItemData['item']['title'] ?? '');
                $itemData->setPrice($lineItemData['item']['price'] ?? 0);
                if (isset($lineItemData['item']['image_url'])) {
                    $itemData->setImageUrl($lineItemData['item']['image_url']);
                }
                $lineItem->setItem($itemData);
            }

            if (isset($lineItemData['totals'])) {
                $lineItem->setTotals($this->deserializeTotals($lineItemData['totals']));
            }

            $lineItems[] = $lineItem;
        }
        return $lineItems;
    }

    /**
     * Serialize totals
     *
     * @param TotalInterface[] $totals
     * @return array
     */
    private function serializeTotals(array $totals): array
    {
        $result = [];
        foreach ($totals as $total) {
            $result[] = [
                'type' => $total->getType(),
                'amount' => $total->getAmount(),
                'display_text' => $total->getDisplayText(),
            ];
        }
        return $result;
    }

    /**
     * Deserialize totals
     *
     * @param array $data
     * @return TotalInterface[]
     */
    private function deserializeTotals(array $data): array
    {
        $totals = [];
        foreach ($data as $totalData) {
            /** @var TotalInterface $total */
            $total = $this->totalFactory->create();
            $total->setType($totalData['type'] ?? '');
            $total->setAmount($totalData['amount'] ?? 0);
            if (isset($totalData['display_text'])) {
                $total->setDisplayText($totalData['display_text']);
            }
            $totals[] = $total;
        }
        return $totals;
    }

    /**
     * Serialize buyer
     *
     * @param BuyerInterface $buyer
     * @return array
     */
    private function serializeBuyer(BuyerInterface $buyer): array
    {
        return [
            'first_name' => $buyer->getFirstName(),
            'last_name' => $buyer->getLastName(),
            'email' => $buyer->getEmail(),
            'phone_number' => $buyer->getPhoneNumber(),
        ];
    }

    /**
     * Deserialize buyer
     *
     * @param array $data
     * @return BuyerInterface
     */
    private function deserializeBuyer(array $data): BuyerInterface
    {
        /** @var BuyerInterface $buyer */
        $buyer = $this->buyerFactory->create();
        $buyer->setFirstName($data['first_name'] ?? null);
        $buyer->setLastName($data['last_name'] ?? null);
        $buyer->setEmail($data['email'] ?? null);
        $buyer->setPhoneNumber($data['phone_number'] ?? null);
        return $buyer;
    }

    /**
     * Serialize payment
     *
     * @param PaymentInterface $payment
     * @return array
     */
    private function serializePayment(PaymentInterface $payment): array
    {
        $handlers = [];
        foreach ($payment->getHandlers() as $handler) {
            $handlers[] = [
                'id' => $handler->getId(),
                'name' => $handler->getName(),
                'version' => $handler->getVersion(),
                'spec' => $handler->getSpec(),
                'config_schema' => $handler->getConfigSchema(),
                'instrument_schemas' => $handler->getInstrumentSchemas(),
                'config' => $handler->getConfig(),
            ];
        }

        return [
            'handlers' => $handlers,
        ];
    }

    /**
     * Deserialize payment
     *
     * @param array $data
     * @return PaymentInterface
     */
    private function deserializePayment(array $data): PaymentInterface
    {
        /** @var PaymentInterface $payment */
        $payment = $this->paymentFactory->create();

        $handlers = [];
        foreach ($data['handlers'] ?? [] as $handlerData) {
            /** @var PaymentHandlerInterface $handler */
            $handler = $this->paymentHandlerFactory->create();
            $handler->setId($handlerData['id'] ?? '');
            $handler->setName($handlerData['name'] ?? '');
            $handler->setVersion($handlerData['version'] ?? '');
            if (isset($handlerData['spec'])) {
                $handler->setSpec($handlerData['spec']);
            }
            if (isset($handlerData['config_schema'])) {
                $handler->setConfigSchema($handlerData['config_schema']);
            }
            if (isset($handlerData['instrument_schemas'])) {
                $handler->setInstrumentSchemas($handlerData['instrument_schemas']);
            }
            if (isset($handlerData['config'])) {
                $handler->setConfig($handlerData['config']);
            }
            $handlers[] = $handler;
        }
        $payment->setHandlers($handlers);

        return $payment;
    }

    /**
     * Serialize links
     *
     * @param LinkInterface[] $links
     * @return array
     */
    private function serializeLinks(array $links): array
    {
        $result = [];
        foreach ($links as $link) {
            $result[] = [
                'rel' => $link->getRel(),
                'href' => $link->getHref(),
            ];
        }
        return $result;
    }

    /**
     * Deserialize links
     *
     * @param array $data
     * @return LinkInterface[]
     */
    private function deserializeLinks(array $data): array
    {
        $links = [];
        foreach ($data as $linkData) {
            /** @var LinkInterface $link */
            $link = $this->linkFactory->create();
            $link->setRel($linkData['rel'] ?? '');
            $link->setHref($linkData['href'] ?? '');
            $links[] = $link;
        }
        return $links;
    }

    /**
     * Serialize messages
     *
     * @param MessageInterface[] $messages
     * @return array
     */
    private function serializeMessages(array $messages): array
    {
        $result = [];
        foreach ($messages as $message) {
            $result[] = [
                'type' => $message->getType(),
                'code' => $message->getCode(),
                'severity' => $message->getSeverity(),
                'content' => $message->getContent(),
                'path' => $message->getPath(),
                'content_type' => $message->getContentType(),
            ];
        }
        return $result;
    }

    /**
     * Deserialize messages
     *
     * @param array $data
     * @return MessageInterface[]
     */
    private function deserializeMessages(array $data): array
    {
        $messages = [];
        foreach ($data as $messageData) {
            /** @var MessageInterface $message */
            $message = $this->messageFactory->create();
            $message->setType($messageData['type'] ?? MessageInterface::TYPE_INFO);
            if (isset($messageData['code'])) {
                $message->setCode($messageData['code']);
            }
            if (isset($messageData['severity'])) {
                $message->setSeverity($messageData['severity']);
            }
            $message->setContent($messageData['content'] ?? '');
            if (isset($messageData['path'])) {
                $message->setPath($messageData['path']);
            }
            if (isset($messageData['content_type'])) {
                $message->setContentType($messageData['content_type']);
            }
            $messages[] = $message;
        }
        return $messages;
    }

    /**
     * Serialize order confirmation
     *
     * @param OrderConfirmationInterface $order
     * @return array
     */
    private function serializeOrderConfirmation(OrderConfirmationInterface $order): array
    {
        return [
            'id' => $order->getId(),
            'permalink_url' => $order->getPermalinkUrl(),
        ];
    }

    /**
     * Deserialize order confirmation
     *
     * @param array $data
     * @return OrderConfirmationInterface
     */
    private function deserializeOrderConfirmation(array $data): OrderConfirmationInterface
    {
        /** @var OrderConfirmationInterface $order */
        $order = $this->orderConfirmationFactory->create();
        $order->setId($data['id'] ?? '');
        $order->setPermalinkUrl($data['permalink_url'] ?? '');
        return $order;
    }

    /**
     * Serialize fulfillment options
     *
     * @param FulfillmentOptionInterface[] $options
     * @return array
     */
    private function serializeFulfillmentOptions(array $options): array
    {
        $result = [];
        foreach ($options as $option) {
            $result[] = [
                'id' => $option->getId(),
                'type' => $option->getType(),
                'display_name' => $option->getDisplayName(),
                'price' => $option->getPrice(),
                'is_selected' => $option->getIsSelected(),
            ];
        }
        return $result;
    }

    /**
     * Deserialize fulfillment options
     *
     * @param array $data
     * @return FulfillmentOptionInterface[]
     */
    private function deserializeFulfillmentOptions(array $data): array
    {
        $options = [];
        foreach ($data as $optionData) {
            /** @var FulfillmentOptionInterface $option */
            $option = $this->fulfillmentOptionFactory->create();
            $option->setId($optionData['id'] ?? '');
            $option->setType($optionData['type'] ?? '');
            $option->setDisplayName($optionData['display_name'] ?? '');
            $option->setPrice($optionData['price'] ?? 0);
            $option->setIsSelected($optionData['is_selected'] ?? false);
            $options[] = $option;
        }
        return $options;
    }
}
