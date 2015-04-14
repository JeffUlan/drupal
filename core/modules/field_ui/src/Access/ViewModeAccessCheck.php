<?php

/**
 * @file
 * Contains \Drupal\field_ui\Access\ViewModeAccessCheck.
 */

namespace Drupal\field_ui\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access check for entity view mode routes.
 *
 * @see \Drupal\Core\Entity\Entity\EntityViewMode
 */
class ViewModeAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new ViewModeAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks access to the view mode.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $view_mode_name
   *   (optional) The view mode. Defaults to 'default'.
   * @param string $bundle
   *   (optional) The bundle. Different entity types can have different names
   *   for their bundle key, so if not specified on the route via a {bundle}
   *   parameter, the access checker determines the appropriate key name, and
   *   gets the value from the corresponding request attribute. For example,
   *   for nodes, the bundle key is "node_type", so the value would be
   *   available via the {node_type} parameter rather than a {bundle}
   *   parameter.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $view_mode_name = 'default', $bundle = NULL) {
    $access = AccessResult::neutral();
    if ($entity_type_id = $route->getDefault('entity_type_id')) {
      if (empty($bundle)) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        $bundle = $route_match->getRawParameter($entity_type->getBundleEntityType());
      }

      $visibility = FALSE;
      if ($view_mode_name == 'default') {
        $visibility = TRUE;
      }
      elseif ($entity_display = $this->entityManager->getStorage('entity_view_display')->load($entity_type_id . '.' . $bundle . '.' . $view_mode_name)) {
        $visibility = $entity_display->status();
      }

      if ($view_mode_name != 'default' && $entity_display) {
        $access->cacheUntilEntityChanges($entity_display);
      }

      if ($visibility) {
        $permission = $route->getRequirement('_field_ui_view_mode_access');
        $access = $access->orIf(AccessResult::allowedIfHasPermission($account, $permission));
      }
    }
    return $access;
  }

}
