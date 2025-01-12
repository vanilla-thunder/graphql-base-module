<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Exception\InvalidToken;
use OxidEsales\GraphQL\Base\Framework\AnonymousUserData;
use OxidEsales\GraphQL\Base\Framework\NullToken;
use OxidEsales\GraphQL\Base\Framework\UserData;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy as LegacyService;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\KeyRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AuthenticationTest extends TestCase
{
    protected static $token = null;

    protected static $anonymousToken = null;

    // phpcs:disable
    protected static $invalidToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5';

    // phpcs:enable

    /** @var KeyRegistry|MockObject */
    private $keyRegistry;

    /** @var LegacyService|MockObject */
    private $legacyService;

    public function setUp(): void
    {
        $this->keyRegistry = $this->getMockBuilder(KeyRegistry::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $this->keyRegistry->method('getSignatureKey')
             ->willReturn('5wi3e0INwNhKe3kqvlH0m4FHYMo6hKef3SzweEjZ8EiPV7I2AC6ASZMpkCaVDTVRg2jbb52aUUXafxXI9/7Cgg==');
        $this->legacyService         = $this->getMockBuilder(LegacyService::class)
                                            ->disableOriginalConstructor()
                                            ->getMock();
    }

    public function testCreateTokenWithInvalidCredentials(): void
    {
        $this->expectException(InvalidLogin::class);
        $this->legacyService
             ->method('login')
             ->willThrowException(new InvalidLogin('Username/password combination is invalid'));

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new NullToken(),
            new EventDispatcher()
        );

        $authenticationService
             ->createToken('foo', 'bar');
    }

    public function testIsLoggedWithoutToken(): void
    {
        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new NullToken(),
            new EventDispatcher()
        );

        $this->assertFalse(
            $authenticationService->isLogged()
        );
    }

    public function testIsLoggedWithFormallyCorrectButInvalidToken(): void
    {
        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            (new Parser())->parse(self::$invalidToken),
            new EventDispatcher()
        );

        $e = null;

        try {
            $authenticationService->isLogged();
        } catch (InvalidToken $e) {
        }
        $this->assertInstanceOf(
            InvalidToken::class,
            $e
        );
    }

    public function testIsLoggedWithNullToken(): void
    {
        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new NullToken(),
            new EventDispatcher()
        );

        $this->assertFalse(
            $authenticationService->isLogged()
        );
    }

    public function testCreateTokenWithValidCredentials(): void
    {
        $this->legacyService
             ->method('login');
        $this->legacyService
             ->method('getShopUrl')
             ->willReturn('https://whatever.com');
        $this->legacyService
             ->method('getShopId')
             ->willReturn(1);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new NullToken(),
            new EventDispatcher()
        );

        self::$token = $authenticationService->createToken('admin', 'admin');

        $this->assertInstanceOf(
            Token::class,
            self::$token
        );
    }

    /**
     * @depends testCreateTokenWithValidCredentials
     */
    public function testIsLoggedWithValidToken(): void
    {
        $this->legacyService
             ->method('getShopUrl')
             ->willReturn('https://whatever.com');
        $this->legacyService
             ->method('getShopId')
             ->willReturn(1);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$token,
            new EventDispatcher()
        );

        $this->assertTrue($authenticationService->isLogged());
    }

    /**
     * @depends testCreateTokenWithValidCredentials
     */
    public function testIsLoggedWithValidForAnotherShopIdToken(): void
    {
        $this->expectException(InvalidToken::class);
        $this->legacyService
             ->method('getShopUrl')
             ->willReturn('https://whatever.com');
        $this->legacyService
             ->method('getShopId')
             ->willReturn(-1);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$token,
            new EventDispatcher()
        );

        $authenticationService->isLogged();
    }

    /**
     * @depends testCreateTokenWithValidCredentials
     *
     * can not use expectException due to needed cleanup in registry config
     */
    public function testGetUserNameWithValidForAnotherShopUrlToken(): void
    {
        $this->expectException(InvalidToken::class);

        $this->legacyService
             ->method('getShopUrl')
             ->willReturn('https:/other.com');
        $this->legacyService
             ->method('getShopId')
             ->willReturn(1);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$token,
            new EventDispatcher()
        );

        $authenticationService->isLogged();
    }

    public function testCreateTokenWithValidCredentialsForBlockedUser(): void
    {
        $this->legacyService
             ->method('login');
        $this->legacyService
             ->method('getShopUrl')
             ->willReturn('https://whatever.com');
        $this->legacyService
             ->method('getShopId')
             ->willReturn(1);
        $this->legacyService
             ->method('getUserGroupIds')
             ->willReturn(['foo', 'oxidblocked', 'bar']);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new NullToken(),
            new EventDispatcher()
        );

        self::$token = $authenticationService->createToken('admin', 'admin');

        $this->assertInstanceOf(
            Token::class,
            self::$token
        );
    }

    /**
     * @depends testCreateTokenWithValidCredentials
     */
    public function testIsLoggedWithValidCredentialsForBlockedUser(): void
    {
        $this->legacyService
            ->method('login');
        $this->legacyService
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacyService
            ->method('getShopId')
            ->willReturn(1);
        $this->legacyService
            ->method('getUserGroupIds')
            ->willReturn(['foo', 'oxidblocked', 'bar']);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$token,
            new EventDispatcher()
        );

        $this->assertFalse($authenticationService->isLogged());
    }

    public function providerGetUserName()
    {
        return [
            'admin' => [
                'username' => 'admin',
                'password' => 'admin',
            ],
            'user'  => [
                'username' => 'user@oxid-esales.com',
                'password' => 'useruser',
            ],
            'not_existing'  => [
                'username' => 'notauser@oxid-esales.com',
                'password' => 'notauseruser',
            ],
        ];
    }

    /**
     * @dataProvider providerGetUserName
     *
     * @param mixed $username
     * @param mixed $password
     */
    public function testGetUserName($username, $password): void
    {
        $authenticationService = $this->getAuthenticationService();
        self::$token           = $authenticationService->createToken($username, $password);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$token,
            new EventDispatcher()
        );

        $this->assertSame($username, $authenticationService->getUserName());
    }

    public function testGetUserNameForNullToken(): void
    {
        $authenticationService = $this->getAuthenticationService();

        $this->expectException(InvalidToken::class);
        $authenticationService->getUserName();
    }

    public function testGetUserId(): void
    {
        $this->legacyService->method('login')
            ->willReturn(new UserData('the_admin_oxid'));

        $authenticationService = $this->getAuthenticationService();
        self::$token           = $authenticationService->createToken('admin', 'admin');

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$token,
            new EventDispatcher()
        );

        $this->assertSame('the_admin_oxid', $authenticationService->getUserId());
        $this->assertNotNull($authenticationService->getUserName());
    }

    public function testGetUserIdForNullToken(): void
    {
        $authenticationService = $this->getAuthenticationService();

        $this->expectException(InvalidToken::class);
        $authenticationService->getUserId();
    }

    public function testGetUserIdForAnonymousToken(): void
    {
        $this->legacyService
            ->method('login')
            ->willReturn(new AnonymousUserData());
        $authenticationService = $this->getAuthenticationService();
        $anonymousToken        = $authenticationService->createToken();

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            $anonymousToken,
            new EventDispatcher()
        );

        $this->assertNotEmpty($authenticationService->getUserId());
    }

    public function testCreateAnonymousToken(): void
    {
        $this->legacyService
            ->method('login')
            ->willReturn(new AnonymousUserData());
        $this->legacyService
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacyService
            ->method('getShopId')
            ->willReturn(1);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new Token(),
            new EventDispatcher()
        );

        self::$anonymousToken = $authenticationService->createToken();

        $this->assertInstanceOf(
            Token::class,
            self::$anonymousToken
        );

        $this->assertNull(
            self::$anonymousToken->claims()->get(Authentication::CLAIM_USERNAME)
        );
    }

    /**
     * @depends testCreateAnonymousToken
     */
    public function testIsLoggedWithAnonymousToken(): void
    {
        $this->legacyService
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacyService
            ->method('getShopId')
            ->willReturn(1);
        $this->legacyService
            ->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$anonymousToken,
            new EventDispatcher()
        );

        $this->assertFalse($authenticationService->isLogged());
    }

    /**
     * @depends testCreateAnonymousToken
     */
    public function testIsUserAnonymous(): void
    {
        $this->legacyService
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacyService
            ->method('getShopId')
            ->willReturn(1);
        $this->legacyService
            ->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$anonymousToken,
            new EventDispatcher()
        );

        $this->assertTrue($authenticationService->isUserAnonymous());
    }

    /**
     * @depends testCreateTokenWithValidCredentials
     */
    public function testLoggedUserIsNotAnonymous(): void
    {
        $this->legacyService
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacyService
            ->method('getShopId')
            ->willReturn(1);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$token,
            new EventDispatcher()
        );

        $this->assertFalse($authenticationService->isUserAnonymous());
    }

    public function testIsAnonymousWithNullToken(): void
    {
        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new NullToken(),
            new EventDispatcher()
        );

        $this->expectException(InvalidToken::class);

        $authenticationService->isUserAnonymous();
    }

    /**
     * @depends testCreateAnonymousToken
     */
    public function testGetUserNameForAnonymousToken(): void
    {
        $this->legacyService
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacyService
            ->method('getShopId')
            ->willReturn(1);
        $this->legacyService
            ->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            self::$anonymousToken,
            new EventDispatcher()
        );

        $this->expectException(InvalidToken::class);

        $authenticationService->getUserName();
    }

    public function testLoggedUserInAnonymousGroup(): void
    {
        $this->legacyService->method('login')
            ->willReturn(new AnonymousUserData());

        $this->legacyService->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $authenticationService = $this->getAuthenticationService();
        $token                 = $authenticationService->createToken('admin', 'admin');

        $authenticationService = new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            $token,
            new EventDispatcher()
        );

        $this->assertTrue($authenticationService->isUserAnonymous());
    }

    private function getAuthenticationService(): Authentication
    {
        $this->legacyService
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacyService
            ->method('getShopId')
            ->willReturn(1);

        return new Authentication(
            $this->keyRegistry,
            $this->legacyService,
            new NullToken(),
            new EventDispatcher()
        );
    }
}
