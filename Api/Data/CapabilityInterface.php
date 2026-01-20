<?php
/**
 * UCP Capability Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface CapabilityInterface
{
    /**
     * Get capability name (e.g. dev.ucp.shopping.checkout)
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set capability name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Get capability version (YYYY-MM-DD format)
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Set capability version
     *
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version): self;
}
