<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\PlatformHttpCacheBundle\Tests\ContextProvider;

use eZ\Publish\API\Repository\RoleService;
use eZ\Publish\API\Repository\Values\User\Limitation\RoleLimitation;
use eZ\Publish\API\Repository\Values\User\Role;
use eZ\Publish\API\Repository\Values\User\User as APIUser;
use eZ\Publish\API\Repository\Values\User\UserReference;
use eZ\Publish\Core\Repository\Repository;
use eZ\Publish\Core\Repository\Helper\LimitationService;
use eZ\Publish\Core\Repository\Helper\RoleDomainMapper;
use eZ\Publish\Core\Repository\Permission\PermissionResolver;
use eZ\Publish\Core\Repository\Values\User\UserRoleAssignment;
use eZ\Publish\SPI\Persistence\User\Handler as SPIUserHandler;
use EzSystems\PlatformHttpCacheBundle\ContextProvider\RoleIdentify;
use FOS\HttpCache\UserContext\UserContext;
use PHPUnit\Framework\TestCase;

/**
 * Class RoleIdentify test.
 */
class RoleIdentifyTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\API\Repository\Repository
     */
    private $repositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\API\Repository\RoleService
     */
    private $roleServiceMock;

    protected function setUp()
    {
        parent::setUp();
        $this->repositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRoleService', 'getCurrentUser', 'getPermissionResolver'])
            ->getMock();

        $this->roleServiceMock = $this->createMock(RoleService::class);

        $this->repositoryMock
            ->expects($this->any())
            ->method('getRoleService')
            ->will($this->returnValue($this->roleServiceMock));
        $this->repositoryMock
            ->expects($this->any())
            ->method('getPermissionResolver')
            ->will($this->returnValue($this->getPermissionResolverMock()));
    }

    public function testSetIdentity()
    {
        $user = $this->createMock(APIUser::class);
        $userContext = new UserContext();

        $this->repositoryMock
            ->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $roleId1 = 123;
        $roleId2 = 456;
        $roleId3 = 789;
        $limitationForRole2 = $this->generateLimitationMock(
            [
                'limitationValues' => ['/1/2', '/1/2/43'],
            ]
        );
        $limitationForRole3 = $this->generateLimitationMock(
            [
                'limitationValues' => ['foo', 'bar'],
            ]
        );
        $returnedRoleAssignments = [
            $this->generateRoleAssignmentMock(
                [
                    'role' => $this->generateRoleMock(
                        [
                            'id' => $roleId1,
                        ]
                    ),
                ]
            ),
            $this->generateRoleAssignmentMock(
                [
                    'role' => $this->generateRoleMock(
                        [
                            'id' => $roleId2,
                        ]
                    ),
                    'limitation' => $limitationForRole2,
                ]
            ),
            $this->generateRoleAssignmentMock(
                [
                    'role' => $this->generateRoleMock(
                        [
                            'id' => $roleId3,
                        ]
                    ),
                    'limitation' => $limitationForRole3,
                ]
            ),
        ];

        $this->roleServiceMock
            ->expects($this->once())
            ->method('getRoleAssignmentsForUser')
            ->with($user, true)
            ->will($this->returnValue($returnedRoleAssignments));

        $this->assertSame([], $userContext->getParameters());
        $contextProvider = new RoleIdentify($this->repositoryMock);
        $contextProvider->updateUserContext($userContext);
        $userContextParams = $userContext->getParameters();
        $this->assertArrayHasKey('roleIdList', $userContextParams);
        $this->assertSame([$roleId1, $roleId2, $roleId3], $userContextParams['roleIdList']);
        $this->assertArrayHasKey('roleLimitationList', $userContextParams);
        $limitationIdentifierForRole2 = get_class($limitationForRole2);
        $limitationIdentifierForRole3 = get_class($limitationForRole3);
        $this->assertSame(
            [
                "$roleId2-$limitationIdentifierForRole2" => ['/1/2', '/1/2/43'],
                "$roleId3-$limitationIdentifierForRole3" => ['foo', 'bar'],
            ],
            $userContextParams['roleLimitationList']
        );
    }

    private function generateRoleAssignmentMock(array $properties = [])
    {
        return $this
            ->getMockBuilder(UserRoleAssignment::class)
            ->setConstructorArgs([$properties])
            ->getMockForAbstractClass();
    }

    private function generateRoleMock(array $properties = [])
    {
        return $this
            ->getMockBuilder(Role::class)
            ->setConstructorArgs([$properties])
            ->getMockForAbstractClass();
    }

    private function generateLimitationMock(array $properties = [])
    {
        $limitationMock = $this
            ->getMockBuilder(RoleLimitation::class)
            ->setConstructorArgs([$properties])
            ->getMockForAbstractClass();
        $limitationMock
            ->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue(get_class($limitationMock)));

        return $limitationMock;
    }

    protected function getPermissionResolverMock()
    {
        return $this
            ->getMockBuilder(PermissionResolver::class)
            ->setMethods(null)
            ->setConstructorArgs(
                [
                    $this->createMock(RoleDomainMapper::class),
                    $this->createMock(LimitationService::class),
                    $this->createMock(SPIUserHandler::class),
                    $this->createMock(UserReference::class),
                ]
            )
            ->getMock();
    }
}
