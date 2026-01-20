<?php
/**
 * UCP Payment Handler Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\PaymentHandlerInterface;
use Magento\Framework\DataObject;

class PaymentHandler extends DataObject implements PaymentHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return (string) $this->getData('id');
    }

    /**
     * @inheritDoc
     */
    public function setId(string $id): PaymentHandlerInterface
    {
        return $this->setData('id', $id);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return (string) $this->getData('name');
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): PaymentHandlerInterface
    {
        return $this->setData('name', $name);
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return (string) $this->getData('version');
    }

    /**
     * @inheritDoc
     */
    public function setVersion(string $version): PaymentHandlerInterface
    {
        return $this->setData('version', $version);
    }

    /**
     * @inheritDoc
     */
    public function getSpec(): ?string
    {
        return $this->getData('spec');
    }

    /**
     * @inheritDoc
     */
    public function setSpec(?string $spec): PaymentHandlerInterface
    {
        return $this->setData('spec', $spec);
    }

    /**
     * @inheritDoc
     */
    public function getConfigSchema(): ?string
    {
        return $this->getData('config_schema');
    }

    /**
     * @inheritDoc
     */
    public function setConfigSchema(?string $configSchema): PaymentHandlerInterface
    {
        return $this->setData('config_schema', $configSchema);
    }

    /**
     * @inheritDoc
     */
    public function getInstrumentSchemas(): ?array
    {
        return $this->getData('instrument_schemas');
    }

    /**
     * @inheritDoc
     */
    public function setInstrumentSchemas(?array $instrumentSchemas): PaymentHandlerInterface
    {
        return $this->setData('instrument_schemas', $instrumentSchemas);
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return $this->getData('config');
    }

    /**
     * @inheritDoc
     */
    public function setConfig($config): PaymentHandlerInterface
    {
        return $this->setData('config', $config);
    }
}
