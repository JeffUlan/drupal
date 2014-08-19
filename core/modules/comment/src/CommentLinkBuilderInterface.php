<?php

/**
 * @file
 * Contains \Drupal\comment\CommentLinkBuilderInterface.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for building comment links on a commented entity.
 *
 * Comment links include 'login to post new comment', 'add new comment' etc.
 */
interface CommentLinkBuilderInterface {

  /**
   * Builds links for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity for which the links are being built.
   * @param array $context
   *   Array of context passed from the entity view builder.
   *
   * @return array
   *   Array of entity links.
   */
  public function buildCommentedEntityLinks(ContentEntityInterface $entity, array &$context);

}
