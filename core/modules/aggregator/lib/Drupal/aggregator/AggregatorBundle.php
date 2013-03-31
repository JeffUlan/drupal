<?php

/**
 * @file
 * Contains \Drupal\aggregator\AggregatorBundle.
 */

namespace Drupal\aggregator;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers aggregator module's services to the container.
 */
class AggregatorBundle extends Bundle {

  /**
   * Overrides Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    foreach (array('fetcher', 'parser', 'processor') as $type) {
      $container->register("plugin.manager.aggregator.$type", 'Drupal\aggregator\Plugin\AggregatorPluginManager')
        ->addArgument($type)
        ->addArgument('%container.namespaces%');
    }
  }

}
