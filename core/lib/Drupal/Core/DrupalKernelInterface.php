<?php

/**
 * @file
 * Definition of Drupal\Core\DrupalKernelInterface.
 */

namespace Drupal\Core;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The interface for DrupalKernel, the core of Drupal.
 *
 * This interface extends Symfony's KernelInterface and adds methods for
 * responding to modules being enabled or disabled during its lifetime.
 */
interface DrupalKernelInterface extends HttpKernelInterface {

  /**
   * Boots the current kernel.
   *
   * @return $this
   */
  public function boot();

  /**
   * Shuts down the kernel.
   */
  public function shutdown();

  /**
   * Discovers available serviceProviders.
   *
   * @return array
   *   The available serviceProviders.
   */
  public function discoverServiceProviders();

  /**
   * Returns all registered service providers.
   *
   * @param string $origin
   *   The origin for which to return service providers; one of 'app' or 'site'.
   *
   * @return array
   *   An associative array of ServiceProvider objects, keyed by name.
   */
  public function getServiceProviders($origin);

  /**
   * Gets the current container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   A ContainerInterface instance.
   */
  public function getContainer();

  /**
   * Set the current site path.
   *
   * @param string $path
   *   The current site path.
   *
   * @throws \LogicException
   *   In case the kernel is already booted.
   */
  public function setSitePath($path);

  /**
   * Get the site path.
   *
   * @return string
   *   The current site path.
   */
  public function getSitePath();

  /**
   * Gets the app root.
   *
   * @return string
   */
  public function getAppRoot();

  /**
   * Updates the kernel's list of modules to the new list.
   *
   * The kernel needs to update its bundle list and container to match the new
   * list.
   *
   * @param array $module_list
   *   The new list of modules.
   * @param array $module_filenames
   *   List of module filenames, keyed by module name.
   */
  public function updateModules(array $module_list, array $module_filenames = array());

  /**
   * Prepare the kernel for handling a request without handling the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return $this
   *
   * @deprecated 8.x
   *   Only used by legacy front-controller scripts.
   */
  public function prepareLegacyRequest(Request $request);

  /**
   * Helper method that does request related initialization.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function preHandle(Request $request);

  /**
   * Helper method that loads legacy Drupal include files.
   */
  public function loadLegacyIncludes();

}
