<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\PermissionAccessCheckTest.
 */

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Access\PermissionAccessCheck;
use Symfony\Component\Routing\Route;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\user\Access\PermissionAccessCheck
 * @group Routing
 * @group AccessF
 */
class PermissionAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\user\Access\PermissionAccessCheck
   */
  public $accessCheck;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class)->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($this->container);

    $this->accessCheck = new PermissionAccessCheck();
  }

  /**
   * Provides data for the testAccess method.
   *
   * @return array
   */
  public function providerTestAccess() {
    return [
      [[], FALSE],
      [['_permission' => 'allowed'], TRUE, ['user.permissions']],
      [['_permission' => 'denied'], FALSE, ['user.permissions']],
      [['_permission' => 'allowed+denied'], TRUE, ['user.permissions']],
      [['_permission' => 'allowed+denied+other'], TRUE, ['user.permissions']],
      [['_permission' => 'allowed,denied'], FALSE, ['user.permissions']],
    ];
  }

  /**
   * Tests the access check method.
   *
   * @dataProvider providerTestAccess
   * @covers ::access
   */
  public function testAccess($requirements, $access, array $contexts = []) {
    $access_result = AccessResult::allowedIf($access)->addCacheContexts($contexts);
    $user = $this->getMock('Drupal\Core\Session\AccountInterface');
    $user->expects($this->any())
      ->method('hasPermission')
      ->will($this->returnValueMap([
          ['allowed', TRUE],
          ['denied', FALSE],
          ['other', FALSE]
        ]
      ));
    $route = new Route('', [], $requirements);

    $this->assertEquals($access_result, $this->accessCheck->access($route, $user));
  }

}
