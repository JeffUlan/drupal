<?php

/**
 * @file
 * Contains \Drupal\system\MenuAccessController.
 */

namespace Drupal\system;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the menu entity type.
 */
class MenuAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation === 'view') {
      return TRUE;
    }
    elseif ($operation == 'delete') {
      // Locked menus could not be deleted.
      if ($entity->isLocked()) {
        return FALSE;
      }
    }

    if (in_array($operation, array('update', 'delete'))) {
      return $account->hasPermission('administer menu');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer menu');
  }

}
