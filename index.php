<?php

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * The routines here dispatch control to the appropriate handler, which then
 * prints the appropriate page.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());
// Bootstrap the lowest level of what we need.
require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

// Create a request object from the HTTPFoundation.
$request = Request::createFromGlobals();

// Bootstrap all of Drupal's subsystems, but do not initialize anything that
// depends on the fully resolved Drupal path, because path resolution happens
// during the REQUEST event of the kernel.
// @see Drupal\Core\EventSubscriber\PathSubscriber;
// @see Drupal\Core\EventSubscriber\LegacyRequestSubscriber;
drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);

$kernel = new DrupalKernel('prod', FALSE);
$kernel->boot();

$response = $kernel->handle($request)->prepare($request)->send();

$kernel->terminate($request, $response);
