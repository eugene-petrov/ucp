<?php
/**
 * Capability Negotiator
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Capability;

use Aeqet\Ucp\Api\Data\CapabilityInterface;

class Negotiator
{
    /**
     * Return merchant capabilities that intersect with the platform's.
     *
     * If $platformNames is empty — returns full merchant list (fail-open).
     *
     * @param CapabilityInterface[] $merchantCaps
     * @param string[] $platformNames Capability names, case-sensitive, must be trimmed
     * @return CapabilityInterface[]
     */
    public function intersect(array $merchantCaps, array $platformNames): array
    {
        if (empty($platformNames)) {
            return $merchantCaps;
        }
        $platformSet = array_flip($platformNames);
        return array_values(
            array_filter($merchantCaps, fn($cap) => isset($platformSet[$cap->getName()]))
        );
    }
}
