<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\ContainerBuilderTest.
 */

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Reference;

require_once __DIR__ . '../../../../../../vendor/symfony/dependency-injection/Symfony/Component/DependencyInjection/Tests/Fixtures/includes/classes.php';

/**
 * Dependency injection container builder.
 *
 * @see \Drupal\Core\DependencyInjection\ContainerBuilder
 */
class ContainerBuilderTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Dependency injection container builder',
      'description' => 'Tests the dependency injection container builder overrides of Drupal.',
      'group' => 'System'
    );
  }

  /**
   * Tests set with a synchronized service.
   */
  public function testSetOnSynchronizedService() {
    $container = new ContainerBuilder();
    $container->register('baz', 'BazClass')
      ->setSynchronized(TRUE);
    $container->register('bar', 'BarClass')
      ->addMethodCall('setBaz', array(new Reference('baz')));

    $container->set('baz', $baz = new \BazClass());
    $this->assertSame($baz, $container->get('bar')->getBaz());

    $container->set('baz', $baz = new \BazClass());
    $this->assertSame($baz, $container->get('bar')->getBaz());
  }

}
