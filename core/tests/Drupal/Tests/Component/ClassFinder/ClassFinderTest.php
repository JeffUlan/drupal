<?php

namespace Drupal\Tests\Component\ClassFinder;

use Composer\Autoload\ClassLoader;
use Drupal\Component\ClassFinder\ClassFinder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\ClassFinder\ClassFinder
 * @group ClassFinder
 */
class ClassFinderTest extends UnitTestCase {

  /**
   * @covers ::findFile
   */
  public function testFindFile() {
    $finder = new ClassFinder();

    // The full path is returned therefore only tests with
    // assertStringEndsWith() so the test is portable.
    $this->assertStringEndsWith('core/tests/Drupal/Tests/UnitTestCase.php', $finder->findFile(UnitTestCase::class));
    $class = 'Not\\A\\Class';
    $this->assertNull($finder->findFile($class));

    // Register an autoloader that can find this class.
    $loader = new ClassLoader();
    $loader->addClassMap([$class => __FILE__]);
    $loader->register();
    $this->assertEquals(__FILE__, $finder->findFile($class));
    // This shouldn't prevent us from finding the original file.
    $this->assertStringEndsWith('core/tests/Drupal/Tests/UnitTestCase.php', $finder->findFile(UnitTestCase::class));

    // Clean up the additional autoloader after the test.
    $loader->unregister();
  }

}
