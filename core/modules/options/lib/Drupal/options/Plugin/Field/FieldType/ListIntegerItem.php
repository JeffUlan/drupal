<?php

/**
 * @file
 * Contains \Drupal\options\Type\ListIntegerItem.
 */

namespace Drupal\options\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'list_integer' field type.
 *
 * @FieldType(
 *   id = "list_integer",
 *   label = @Translation("List (integer)"),
 *   description = @Translation("This field stores integer values from a list of allowed 'value => label' pairs, i.e. 'Lifetime in days': 1 => 1 day, 7 => 1 week, 31 => 1 month."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 *   settings = {
 *     "allowed_values" = { },
 *     "allowed_values_function" = ""
 *   }
 * )
 */
class ListIntegerItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Integer value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the stored value, and must be numeric. The label will be used in displayed values and edit forms.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single number, it will be used as key and label.');
    $description .= '<br/>' . t('Lists of labels are also accepted (one label per line), only if the field does not hold any values yet. Numeric keys will be automatically generated from the positions in the list.');
    $description .= '</p>';
    $description .= '<p>' . t('Allowed HTML tags in labels: @tags', array('@tags' => _field_filter_xss_display_allowed_tags())) . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    if (!preg_match('/^-?\d+$/', $option)) {
      return t('Allowed values list: keys must be integers.');
    }
  }

}
