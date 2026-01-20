<?php
/**
 * UCP Link Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface LinkInterface
{
    public const REL_SELF = 'self';
    public const REL_TERMS_OF_SERVICE = 'terms_of_service';
    public const REL_PRIVACY_POLICY = 'privacy_policy';

    /**
     * Get rel (relationship type)
     *
     * @return string
     */
    public function getRel(): string;

    /**
     * Set rel
     *
     * @param string $rel
     * @return $this
     */
    public function setRel(string $rel): self;

    /**
     * Get href (URL)
     *
     * @return string
     */
    public function getHref(): string;

    /**
     * Set href
     *
     * @param string $href
     * @return $this
     */
    public function setHref(string $href): self;
}
