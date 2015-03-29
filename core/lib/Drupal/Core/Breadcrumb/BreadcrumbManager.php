<?php

/**
 * @file
 * Contains \Drupal\Core\Breadcrumb\BreadcrumbManager.
 */

namespace Drupal\Core\Breadcrumb;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a breadcrumb manager.
 *
 * Can be assigned any number of BreadcrumbBuilderInterface objects by calling
 * the addBuilder() method. When build() is called it iterates over the objects
 * in priority order and uses the first one that returns TRUE from
 * BreadcrumbBuilderInterface::applies() to build the breadcrumbs.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\RegisterBreadcrumbBuilderPass
 */
class BreadcrumbManager implements ChainBreadcrumbBuilderInterface {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Holds arrays of breadcrumb builders, keyed by priority.
   *
   * @var array
   */
  protected $builders = array();

  /**
   * Holds the array of breadcrumb builders sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface[]|null
   */
  protected $sortedBuilders;

  /**
   * Constructs a \Drupal\Core\Breadcrumb\BreadcrumbManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function addBuilder(BreadcrumbBuilderInterface $builder, $priority) {
    $this->builders[$priority][] = $builder;
    // Force the builders to be re-sorted.
    $this->sortedBuilders = NULL;
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
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = array();
    $context = array('builder' => NULL);
    // Call the build method of registered breadcrumb builders,
    // until one of them returns an array.
    foreach ($this->getSortedBuilders() as $builder) {
      if (!$builder->applies($route_match)) {
        // The builder does not apply, so we continue with the other builders.
        continue;
      }

      $build = $builder->build($route_match);

      if (is_array($build)) {
        // The builder returned an array of breadcrumb links.
        $breadcrumb = $build;
        $context['builder'] = $builder;
        break;
      }
      else {
        throw new \UnexpectedValueException(SafeMarkup::format('Invalid breadcrumb returned by !class::build().', array('!class' => get_class($builder))));
      }
    }
    // Allow modules to alter the breadcrumb.
    $this->moduleHandler->alter('system_breadcrumb', $breadcrumb, $route_match, $context);
    // Fall back to an empty breadcrumb.
    return $breadcrumb;
  }

  /**
   * Returns the sorted array of breadcrumb builders.
   *
   * @return \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface[]
   *   An array of breadcrumb builder objects.
   */
  protected function getSortedBuilders() {
    if (!isset($this->sortedBuilders)) {
      // Sort the builders according to priority.
      krsort($this->builders);
      // Merge nested builders from $this->builders into $this->sortedBuilders.
      $this->sortedBuilders = array();
      foreach ($this->builders as $builders) {
        $this->sortedBuilders = array_merge($this->sortedBuilders, $builders);
      }
    }
    return $this->sortedBuilders;
  }

}
