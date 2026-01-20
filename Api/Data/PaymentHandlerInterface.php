<?php
/**
 * UCP Payment Handler Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface PaymentHandlerInterface
{
    /**
     * Get handler ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set handler ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get name (reverse-DNS format, e.g. dev.ucp.delegate_payment)
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Get version (YYYY-MM-DD format)
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Set version
     *
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version): self;

    /**
     * Get spec URI
     *
     * @return string|null
     */
    public function getSpec(): ?string;

    /**
     * Set spec
     *
     * @param string|null $spec
     * @return $this
     */
    public function setSpec(?string $spec): self;

    /**
     * Get config schema URI
     *
     * @return string|null
     */
    public function getConfigSchema(): ?string;

    /**
     * Set config schema
     *
     * @param string|null $configSchema
     * @return $this
     */
    public function setConfigSchema(?string $configSchema): self;

    /**
     * Get instrument schemas URIs
     *
     * @return string[]|null
     */
    public function getInstrumentSchemas(): ?array;

    /**
     * Set instrument schemas
     *
     * @param string[]|null $instrumentSchemas
     * @return $this
     */
    public function setInstrumentSchemas(?array $instrumentSchemas): self;

    /**
     * Get config object
     *
     * @return mixed
     */
    public function getConfig();

    /**
     * Set config
     *
     * @param mixed $config
     * @return $this
     */
    public function setConfig($config): self;
}
