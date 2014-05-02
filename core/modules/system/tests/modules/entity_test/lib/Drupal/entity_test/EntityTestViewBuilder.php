<?php

/**
 * @file
 * Contains \Drupal\entity_test\EntityTestViewBuilder.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Defines an entity view builder for a test entity.
 *
 * @see \Drupal\entity_test\Entity\EntityTestRender
 */
class EntityTestViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    foreach ($entities as $id => $entity) {
      $build[$id]['label'] = array(
        '#markup' => check_plain($entity->label()),
      );
      $build[$id]['separator'] = array(
        '#markup' => ' | ',
      );
      $build[$id]['view_mode'] = array(
        '#markup' => check_plain($view_mode),
      );
    }
  }

}
