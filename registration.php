<?php
/**
 * Aeqet UCP Module Registration
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Aeqet_Ucp',
    __DIR__
);
