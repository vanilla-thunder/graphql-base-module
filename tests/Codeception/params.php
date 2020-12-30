<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

use OxidEsales\Facts\Facts;

$facts = new Facts();

echo 'SHOP_URL';
echo $facts->getShopUrl();

return [
    'SHOP_URL' => $facts->getShopUrl(),
];
