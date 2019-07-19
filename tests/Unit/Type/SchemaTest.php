<?php declare(strict_types=1);

namespace OxidEsales\GraphQl\Tests\Unit\Type;

use OxidEsales\GraphQl\Framework\SchemaFactory;
use OxidEsales\GraphQl\Service\AuthenticationServiceInterface;
use OxidEsales\GraphQl\Service\KeyRegistryInterface;
use OxidEsales\GraphQl\Service\PermissionsService;
use OxidEsales\GraphQl\Type\ObjectType\LoginType;
use OxidEsales\GraphQl\Type\Provider\LoginQueryProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

class SchemaTest extends TestCase
{

    /**
     * Test
     */
    public function testSchemaFactory()
    {
        /** @var MockObject|AuthenticationServiceInterface $authService */
        $authService = $this->getMockBuilder(AuthenticationServiceInterface::class)->getMock();
        /** @var MockObject|KeyRegistryInterface $keyRegistry */
        $keyRegistry = $this->getMockBuilder(KeyRegistryInterface::class)->getMock();
        $loginType = new LoginQueryProvider($authService, $keyRegistry, new PermissionsService(), new LoginType());

        $schemaFactory = new SchemaFactory();
        $schemaFactory->addQueryProvider($loginType);

        $schema = $schemaFactory->getSchema();

        $this->assertNotNull($schema);
    }
}