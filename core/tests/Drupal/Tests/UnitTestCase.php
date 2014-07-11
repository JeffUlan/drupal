<?php

/**
 * @file
 * Contains \Drupal\Tests\UnitTestCase.
 */

namespace Drupal\Tests;

use Drupal\Component\Utility\Random;
use Drupal\Component\Utility\String;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Provides a base class and helpers for Drupal unit tests.
 *
 * @ingroup testing
 */
abstract class UnitTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * The random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Ensure that an instantiated container in the global state of \Drupal from
    // a previous test does not leak into this test.
    \Drupal::setContainer(NULL);
  }

  /**
   * Generates a unique random string containing letters and numbers.
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated unique string.
   *
   * @see \Drupal\Component\Utility\Random::name()
   */
  public function randomName($length = 8) {
    return $this->getRandomGenerator()->name($length, TRUE);
  }

  /**
   * Gets the random generator for the utility methods.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random generator
   */
  protected function getRandomGenerator() {
    if (!is_object($this->randomGenerator)) {
      $this->randomGenerator = new Random();
    }
    return $this->randomGenerator;
  }

  /**
   * Asserts if two arrays are equal by sorting them first.
   *
   * @param array $expected
   * @param array $actual
   * @param string $message
   */
  protected function assertArrayEquals(array $expected, array $actual, $message = NULL) {
    ksort($expected);
    ksort($actual);
    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * Returns a stub config factory that behaves according to the passed in array.
   *
   * Use this to generate a config factory that will return the desired values
   * for the given config names.
   *
   * @param array $configs
   *   An associative array of configuration settings whose keys are configuration
   *   object names and whose values are key => value arrays for the configuration
   *   object in question. Defaults to an empty array.
   *
   * @return \PHPUnit_Framework_MockObject_MockBuilder
   *   A MockBuilder object for the ConfigFactory with the desired return values.
   */
  public function getConfigFactoryStub(array $configs = array()) {
    $config_map = array();
    // Construct the desired configuration object stubs, each with its own
    // desired return map.
    foreach ($configs as $config_name => $config_values) {
      $config_object = $this->getMockBuilder('Drupal\Core\Config\Config')
        ->disableOriginalConstructor()
        ->getMock();
      $map = array();
      foreach ($config_values as $key => $value) {
        $map[] = array($key, $value);
      }
      // Also allow to pass in no argument.
      $map[] = array('', $config_values);

      $config_object->expects($this->any())
        ->method('get')
        ->will($this->returnValueMap($map));

      $config_map[] = array($config_name, $config_object);
    }
    // Construct a config factory with the array of configuration object stubs
    // as its return map.
    $config_factory = $this->getMock('Drupal\Core\Config\ConfigFactoryInterface');
    $config_factory->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($config_map));
    return $config_factory;
  }

  /**
   * Returns a stub config storage that returns the supplied configuration.
   *
   * @param array $configs
   *   An associative array of configuration settings whose keys are
   *   configuration object names and whose values are key => value arrays
   *   for the configuration object in question.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   A mocked config storage.
   */
  public function getConfigStorageStub(array $configs) {
    $config_storage = $this->getMock('Drupal\Core\Config\NullStorage');
    $config_storage->expects($this->any())
      ->method('listAll')
      ->will($this->returnValue(array_keys($configs)));

    foreach ($configs as $name => $config) {
      $config_storage->expects($this->any())
        ->method('read')
        ->with($this->equalTo($name))
        ->will($this->returnValue($config));
    }
    return $config_storage;
  }

  /**
   * Mocks a block with a block plugin.
   *
   * @param string $machine_name
   *   The machine name of the block plugin.
   *
   * @return \Drupal\block\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mocked block.
   */
  protected function getBlockMockWithMachineName($machine_name) {
    $plugin = $this->getMockBuilder('Drupal\block\BlockBase')
      ->disableOriginalConstructor()
      ->getMock();
    $plugin->expects($this->any())
      ->method('getMachineNameSuggestion')
      ->will($this->returnValue($machine_name));

    $block = $this->getMockBuilder('Drupal\block\Entity\Block')
      ->disableOriginalConstructor()
      ->getMock();
    $block->expects($this->any())
      ->method('getPlugin')
      ->will($this->returnValue($plugin));
    return $block;
  }

  /**
   * Returns a stub translation manager that just returns the passed string.
   *
   * @return \PHPUnit_Framework_MockObject_MockBuilder
   *   A MockBuilder of \Drupal\Core\StringTranslation\TranslationInterface
   */
  public function getStringTranslationStub() {
    $translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $translation->expects($this->any())
      ->method('translate')
      ->will($this->returnCallback('Drupal\Component\Utility\String::format'));
    return $translation;
  }

  /**
   * Sets up a container with cache bins.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $backend
   *   The cache backend to set up.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The container with the cache bins set up.
   */
  protected function getContainerWithCacheBins(CacheBackendInterface $backend) {
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('getParameter')
      ->with('cache_bins')
      ->will($this->returnValue(array('cache.test' => 'test')));
    $container->expects($this->any())
      ->method('get')
      ->with('cache.test')
      ->will($this->returnValue($backend));

    \Drupal::setContainer($container);
    return $container;
  }

  /**
   * Returns a stub class resolver.
   *
   * @return \Drupal\Core\DependencyInjection\ClassResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The class resolver stub.
   */
  protected function getClassResolverStub() {
    $class_resolver = $this->getMock('Drupal\Core\DependencyInjection\ClassResolverInterface');
    $class_resolver->expects($this->any())
      ->method('getInstanceFromDefinition')
      ->will($this->returnCallback(function ($class) {
        if (is_subclass_of($class, 'Drupal\Core\DependencyInjection\ContainerInjectionInterface')) {
          return $class::create(new ContainerBuilder());
        }
        else {
          return new $class();
        }
      }));
    return $class_resolver;
  }

}
