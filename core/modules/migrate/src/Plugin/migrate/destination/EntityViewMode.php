<?php

namespace Drupal\migrate\Plugin\migrate\destination;

/**
 * Provides entity view mode destination plugin.
 *
 * @MigrateDestination(
 *   id = "entity:entity_view_mode"
 * )
 */
class EntityViewMode extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['targetEntityType']['type'] = 'string';
    $ids['mode']['type'] = 'string';
    return $ids;
  }

}
