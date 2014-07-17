<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Plugin\Field\FieldType\BooleanItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\AllowedValuesInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'boolean' entity field type.
 *
 * @FieldType(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("An entity field containing a boolean value."),
 *   default_widget = "boolean_checkbox",
 *   default_formatter = "boolean",
 * )
 */
class BooleanItem extends FieldItemBase implements AllowedValuesInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'on_label' => t('On'),
      'off_label' => t('Off'),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(t('Boolean value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array &$form_state, $has_data) {
    $element['on_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('"On" label'),
      '#default_value' => $this->getSetting('on_label'),
      '#required' => TRUE,
    );
    $element['off_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('"Off" label'),
      '#default_value' => $this->getSetting('off_label'),
      '#required' => TRUE,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return array(
      0 => $this->getSetting('off_label'),
      1 => $this->getSetting('on_label'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array(0, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleOptions($account);
  }


}
