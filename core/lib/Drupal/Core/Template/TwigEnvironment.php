<?php

/**
 * @file
 * Contains \Drupal\Core\Template\TwigEnvironment.
 */

namespace Drupal\Core\Template;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Render\Markup;

/**
 * A class that defines a Twig environment for Drupal.
 *
 * Instances of this class are used to store the configuration and extensions,
 * and are used to load templates from the file system or other locations.
 *
 * @see core\vendor\twig\twig\lib\Twig\Environment.php
 */
class TwigEnvironment extends \Twig_Environment {

  /**
   * Static cache of template classes.
   *
   * @var array
   */
  protected $templateClasses;

  /**
   * Constructs a TwigEnvironment object and stores cache and storage
   * internally.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   * @param string $twig_extension_hash
   *   The Twig extension hash.
   * @param \Twig_LoaderInterface $loader
   *   The Twig loader or loader chain.
   * @param array $options
   *   The options for the Twig environment.
   */
  public function __construct($root, CacheBackendInterface $cache, $twig_extension_hash, \Twig_LoaderInterface $loader = NULL, $options = array()) {
    // Ensure that twig.engine is loaded, given that it is needed to render a
    // template because functions like TwigExtension::escapeFilter() are called.
    require_once $root . '/core/themes/engines/twig/twig.engine';

    $this->templateClasses = array();

    $options += array(
      // @todo Ensure garbage collection of expired files.
      'cache' => TRUE,
      'debug' => FALSE,
      'auto_reload' => NULL,
    );
    // Ensure autoescaping is always on.
    $options['autoescape'] = 'html';

    if ($options['cache'] === TRUE) {
      $options['cache'] = new TwigPhpStorageCache($cache, $twig_extension_hash);
    }

    $this->loader = $loader;
    parent::__construct($this->loader, $options);
  }

  /**
   * Gets the template class associated with the given string.
   *
   * @param string $name
   *   The name for which to calculate the template class name.
   * @param int $index
   *   The index if it is an embedded template.
   *
   * @return string
   *   The template class name.
   */
  public function getTemplateClass($name, $index = NULL) {
    // We override this method to add caching because it gets called multiple
    // times when the same template is used more than once. For example, a page
    // rendering 50 nodes without any node template overrides will use the same
    // node.html.twig for the output of each node and the same compiled class.
    $cache_index = $name . (NULL === $index ? '' : '_' . $index);
    if (!isset($this->templateClasses[$cache_index])) {
      $this->templateClasses[$cache_index] = $this->templateClassPrefix . hash('sha256', $this->loader->getCacheKey($name)) . (NULL === $index ? '' : '_' . $index);
    }
    return $this->templateClasses[$cache_index];
  }

  /**
   * Renders a twig string directly.
   *
   * Warning: You should use the render element 'inline_template' together with
   * the #template attribute instead of this method directly.
   * On top of that you have to ensure that the template string is not dynamic
   * but just an ordinary static php string, because there may be installations
   * using read-only PHPStorage that want to generate all possible twig
   * templates as part of a build step. So it is important that an automated
   * script can find the templates and extract them. This is only possible if
   * the template is a regular string.
   *
   * @param string $template_string
   *   The template string to render with placeholders.
   * @param array $context
   *   An array of parameters to pass to the template.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered inline template as a Markup object.
   *
   * @see \Drupal\Core\Template\Loader\StringLoader::exists()
   */
  public function renderInline($template_string, array $context = array()) {
    // Prefix all inline templates with a special comment.
    $template_string = '{# inline_template_start #}' . $template_string;
    return Markup::create($this->loadTemplate($template_string, NULL)->render($context));
  }

}
