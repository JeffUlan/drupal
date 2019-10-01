<?php

namespace Drupal\Core\Path;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the path_alias schema handler.
 */
class PathAliasStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema[$this->storage->getBaseTable()]['indexes'] += [
      'path_alias__alias_langcode_id' => ['alias', 'langcode', 'id'],
      'path_alias__path_langcode_id' => ['path', 'langcode', 'id'],
    ];

    return $schema;
  }

}
