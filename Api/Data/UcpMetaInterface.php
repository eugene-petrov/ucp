<?php
/**
 * UCP Meta Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface UcpMetaInterface
{
    /**
     * Get UCP version
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Set UCP version
     *
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version): self;

    /**
     * Get capabilities
     *
     * @return \Aeqet\Ucp\Api\Data\CapabilityInterface[]
     */
    public function getCapabilities(): array;

    /**
     * Set capabilities
     *
     * @param \Aeqet\Ucp\Api\Data\CapabilityInterface[] $capabilities
     * @return $this
     */
    public function setCapabilities(array $capabilities): self;
}
