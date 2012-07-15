<?php
/**
 * @file
 * Definition of Drupal\Component\Plugin\Factory\DefaultFactory.
 */

namespace Drupal\Component\Plugin\Factory;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Default plugin factory.
 *
 * Instantiates plugin instances by passing the full configuration array as a
 * single constructor argument. Plugin types wanting to support plugin classes
 * with more flexible constructor signatures can do so by using an alternate
 * factory such as Drupal\Component\Plugin\Factory\ReflectionFactory.
 */
class DefaultFactory implements FactoryInterface {

  /**
   * The object that retrieves the definitions of the plugins that this factory instantiates.
   *
   * The plugin definition includes the plugin class and possibly other
   * information necessary for proper instantiation.
   *
   * @var Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * Constructs a Drupal\Component\Plugin\Factory\DefaultFactory object.
   */
  public function __construct(DiscoveryInterface $discovery) {
    $this->discovery = $discovery;
  }

  /**
   * Implements Drupal\Component\Plugin\Factory\FactoryInterface::createInstance().
   */
  public function createInstance($plugin_id, array $configuration) {
    $plugin_class = $this->getPluginClass($plugin_id);
    return new $plugin_class($configuration, $plugin_id, $this->discovery);
  }

  /**
   * Finds the class relevant for a given plugin.
   *
   *  @param array $plugin_id
   *    The id of a plugin.
   *
   *  @return string
   *    The appropriate class name.
   */
  protected function getPluginClass($plugin_id) {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    if (empty($plugin_definition['class'])) {
      throw new PluginException('The plugin did not specify an instance class.');
    }

    $class = $plugin_definition['class'];

    if (!class_exists($class)) {
      throw new PluginException(sprintf('Plugin instance class "%s" does not exist.', $class));
    }

    return $class;
  }
}
