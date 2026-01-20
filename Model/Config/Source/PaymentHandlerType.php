<?php
/**
 * Payment Handler Type Source Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentHandlerType implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'delegated',
                'label' => __('Delegated (Redirect to Merchant Checkout)'),
            ],
            [
                'value' => 'direct',
                'label' => __('Direct (Token-based Payment)'),
            ],
        ];
    }
}
