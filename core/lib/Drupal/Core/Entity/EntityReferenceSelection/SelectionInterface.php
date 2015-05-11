<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface.
 */

namespace Drupal\Core\Entity\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface definition for Entity Reference Selection plugins.
 *
 * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see \Drupal\Core\Entity\Plugin\Derivative\SelectionBase
 * @see plugin_api
 */
interface SelectionInterface extends PluginFormInterface {

  /**
   * Gets the list of referenceable entities.
   *
   * @return array
   *   A nested array of entities, the first level is keyed by the
   *   entity bundle, which contains an array of entity labels (safe HTML),
   *   keyed by the entity ID.
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0);

  /**
   * Counts entities that are referenceable by a given field.
   *
   * @return int
   *   The number of referenceable entities.
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS');

  /**
   * Validates that entities can be referenced by this field.
   *
   * @return array
   *   An array of valid entity IDs.
   */
  public function validateReferenceableEntities(array $ids);

  /**
   * Validates input from an autocomplete widget that has no ID.
   *
   * @param string $input
   *   Single string from autocomplete widget.
   * @param array $element
   *   The form element to set a form error.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param array $form
   *   The form.
   * @param bool $strict
   *   Whether to trigger a form error if an element from $input (eg. an entity)
   *   is not found. Defaults to TRUE.
   *
   * @return integer|null
   *   Value of a matching entity ID, or NULL if none.
   *
   * @see \Drupal\entity_reference\Plugin\Field\FieldWidget::elementValidate()
   */
  public function validateAutocompleteInput($input, &$element, FormStateInterface $form_state, $form, $strict = TRUE);

  /**
   * Allows the selection to alter the SelectQuery generated by EntityFieldQuery.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A Select Query object.
   */
  public function entityQueryAlter(SelectInterface $query);

}
