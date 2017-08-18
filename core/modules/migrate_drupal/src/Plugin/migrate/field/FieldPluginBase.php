<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\MigrateFieldInterface;

/**
 * The base class for all field plugins.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate_drupal\Annotation\MigrateField
 * @see \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class FieldPluginBase extends PluginBase implements MigrateFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function processField(MigrationInterface $migration) {
    $process[0]['map'][$this->pluginId][$this->pluginId] = $this->pluginId;
    $migration->mergeProcessOfProperty('type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldInstance(MigrationInterface $migration) {
    // Nothing to do by default with field instances.
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldWidget(MigrationInterface $migration) {
    $process = [];
    foreach ($this->getFieldWidgetMap() as $source_widget => $destination_widget) {
      $process['type']['map'][$source_widget] = $destination_widget;
    }
    $migration->mergeProcessOfProperty('options/type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    // By default, use the plugin ID for the widget types.
    return [
      $this->pluginId => $this->pluginId . '_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldFormatter(MigrationInterface $migration) {
    $process = [];
    foreach ($this->getFieldFormatterMap() as $source_format => $destination_format) {
      $process[0]['map'][$this->pluginId][$source_format] = $destination_format;
    }
    $migration->mergeProcessOfProperty('options/type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'get',
      'source' => $field_name,
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    $field_type = $row->getSourceProperty('type');

    if (isset($this->pluginDefinition['type_map'][$field_type])) {
      return $this->pluginDefinition['type_map'][$field_type];
    }
    else {
      return $field_type;
    }
  }

}
