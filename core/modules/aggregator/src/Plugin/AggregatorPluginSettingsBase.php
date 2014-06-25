<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\AggregatorPluginSettingsBase.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class for aggregator plugins that implement settings forms.
 *
 * @see \Drupal\aggregator\Annotation\AggregatorParser
 * @see \Drupal\aggregator\Annotation\AggregatorFetcher
 * @see \Drupal\aggregator\Annotation\AggregatorProcessor
 * @see \Drupal\aggregator\Plugin\AggregatorPluginManager
 * @see \Drupal\aggregator\Plugin\FetcherInterface
 * @see \Drupal\aggregator\Plugin\ProcessorInterface
 * @see \Drupal\aggregator\Plugin\ParserInterface
 * @see plugin_api
 */
abstract class AggregatorPluginSettingsBase extends PluginBase implements PluginFormInterface, ConfigurablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
