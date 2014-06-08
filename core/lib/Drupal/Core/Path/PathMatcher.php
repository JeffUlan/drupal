<?php

/**
 * @file
 * Contains \Drupal\Core\Path\PathMatcher.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a path matcher.
 */
class PathMatcher implements PathMatcherInterface {

  /**
   * The default front page.
   *
   * @var string
   */
  protected $frontPage;

  /**
   * The cache of regular expressions.
   *
   * @var array
   */
  protected $regexes;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new PathMatcher.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function matchPath($path, $patterns) {

    if (!isset($this->regexes[$patterns])) {
      // Lazy-load front page config.
      if (!isset($this->frontPage)) {
        $this->frontPage = $this->configFactory->get('system.site')->get('page.front');
      }
      // Convert path settings to a regular expression.
      $to_replace = array(
        // Replace newlines with a logical 'or'.
        '/(\r\n?|\n)/',
        // Quote asterisks.
        '/\\\\\*/',
        // Quote <front> keyword.
        '/(^|\|)\\\\<front\\\\>($|\|)/',
      );
      $replacements = array(
        '|',
        '.*',
        '\1' . preg_quote($this->frontPage, '/') . '\2',
      );
      $patterns_quoted = preg_quote($patterns, '/');
      $this->regexes[$patterns] = '/^(' . preg_replace($to_replace, $replacements, $patterns_quoted) . ')$/';
    }
    return (bool) preg_match($this->regexes[$patterns], $path);
  }
}
