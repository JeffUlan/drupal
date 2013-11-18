<?php

/**
 * @file
 * Contains \Drupal\config_translation\Exception\InvalidMapperDefinitionException.
 */

namespace Drupal\config_translation\Exception;

use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Defines a class for invalid configuration mapper definition exceptions.
 */
class InvalidMapperDefinitionException extends PluginException {

  /**
   * The plugin ID of the mapper.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Constructs a InvalidMapperDefinitionException.
   *
   * @param string $plugin_id
   *   The plugin ID of the mapper.
   *
   * @see \Exception for the remaining parameters.
   */
  public function __construct($plugin_id, $message = '', $code = 0, \Exception $previous = NULL) {
    $this->pluginId = $plugin_id;
    parent::__construct($message, $code, $previous);
  }

  /**
   * Returns the plugin ID of the mapper that raised the exception.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId() {
    return $this->pluginId;
  }

}
