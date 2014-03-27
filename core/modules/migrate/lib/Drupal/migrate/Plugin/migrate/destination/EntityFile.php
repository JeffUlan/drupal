<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityFile.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\field\FieldInfo;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:file"
 * )
 */
class EntityFile extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, MigratePluginManager $plugin_manager, FieldInfo $field_info) {
    $configuration += array(
      'source_base_path' => '',
      'source_path_property' => 'filepath',
      'destination_path_property' => 'uri',
      'move' => FALSE,
    );
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $plugin_manager, $field_info);
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $source = $this->configuration['source_base_path'] . $row->getSourceProperty($this->configuration['source_path_property']);
    $destination = $row->getDestinationProperty($this->configuration['destination_path_property']);
    $replace = FILE_EXISTS_REPLACE;
    if (!empty($this->configuration['rename'])) {
      $entity_id = $row->getDestinationProperty($this->getKey('id'));
      if (!empty($entity_id) && ($entity = $this->storage->load($entity_id))) {
        $replace = FILE_EXISTS_RENAME;
      }
    }
    $dirname = drupal_dirname($destination);
    file_prepare_directory($dirname, FILE_CREATE_DIRECTORY);
    if ($this->configuration['move']) {
      file_unmanaged_move($source, $destination, $replace);
    }
    else {
      file_unmanaged_copy($source, $destination, $replace);
    }
    return parent::import($row, $old_destination_id_values);
  }

}
