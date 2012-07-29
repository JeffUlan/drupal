<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\ArgumentDefaultPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;


class ArgumentDefaultPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'argument_default');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
