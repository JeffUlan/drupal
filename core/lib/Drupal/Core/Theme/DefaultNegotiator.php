<?php

/**
 * @file
 * Contains \Drupal\Core\Theme\DefaultNegotiator.
 */

namespace Drupal\Core\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Determines the default theme of the site.
 */
class DefaultNegotiator implements ThemeNegotiatorInterface {

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a DefaultNegotiator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('system.theme');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // @todo Find a proper way to work at the beginning of the installer when
    //   there is no configuration available yet. One proper way could be to
    //   provider a custom negotiator during the installer.
    return $this->config->get('default') ?: 'stark';
  }

}
