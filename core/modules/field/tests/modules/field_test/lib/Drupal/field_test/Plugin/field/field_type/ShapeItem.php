<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\field\field_type\ShapeItem.
 */

namespace Drupal\field_test\Plugin\field\field_type;

use Drupal\Core\Entity\Annotation\FieldType;
use Drupal\Core\Annotation\Translation;
use Drupal\field\FieldInterface;
use Drupal\field\Plugin\Type\FieldType\ConfigFieldItemBase;

/**
 * Defines the 'shape_field' entity field item.
 *
 * @FieldType(
 *   id = "shape",
 *   label = @Translation("Shape"),
 *   description = @Translation("Another dummy field type."),
 *   settings = {
 *     "foreign_key_name" = "shape"
 *   },
 *   default_widget = "test_field_widget",
 *   default_formatter = "field_test_default"
 * )
 */
class ShapeItem extends ConfigFieldItemBase {

  /**
   * Property definitions of the contained properties.
   *
   * @see ShapeItem::getPropertyDefinitions()
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {

    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['shape'] = array(
        'type' => 'string',
        'label' => t('Shape'),
      );
      static::$propertyDefinitions['color'] = array(
        'type' => 'string',
        'label' => t('Color'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    $foreign_keys = array();
    // The 'foreign keys' key is not always used in tests.
    if ($field->getFieldSetting('foreign_key_name')) {
      $foreign_keys['foreign keys'] = array(
        // This is a dummy foreign key definition, references a table that
        // doesn't exist, but that's not a problem.
        $field->getFieldSetting('foreign_key_name') => array(
          'table' => $field->getFieldSetting('foreign_key_name'),
          'columns' => array($field->getFieldSetting('foreign_key_name') => 'id'),
        ),
      );
    }
    return array(
      'columns' => array(
        'shape' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
        'color' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => FALSE,
        ),
      ),
    ) + $foreign_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $item = $this->getValue();
    return empty($item['shape']) && empty($item['color']);
  }

}
