<?php

/**
 * @file
 * Contains \Drupal\comment\CommentAccessController
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class CommentAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\user\EntityOwnerInterface $entity */
    switch ($operation) {
      case 'view':
        return $account->hasPermission('access comments');
        break;

      case 'update':
        return ($account->id() && $account->id() == $entity->getOwnerId() && $entity->status->value == CommentInterface::PUBLISHED && $account->hasPermission('edit own comments')) || $account->hasPermission('administer comments');
        break;

      case 'delete':
        return $account->hasPermission('administer comments');
        break;

      case 'approve':
        return $account->hasPermission('administer comments');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('post comments');
  }

}
