<?php
/**
 * UCP Link Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\LinkInterface;
use Magento\Framework\DataObject;

class Link extends DataObject implements LinkInterface
{
    /**
     * @inheritDoc
     */
    public function getRel(): string
    {
        return (string) $this->getData('rel');
    }

    /**
     * @inheritDoc
     */
    public function setRel(string $rel): LinkInterface
    {
        return $this->setData('rel', $rel);
    }

    /**
     * @inheritDoc
     */
    public function getHref(): string
    {
        return (string) $this->getData('href');
    }

    /**
     * @inheritDoc
     */
    public function setHref(string $href): LinkInterface
    {
        return $this->setData('href', $href);
    }
}
