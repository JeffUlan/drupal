<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\field\formatter\EntityReferenceEntityFormatter.
 */

namespace Drupal\entity_reference\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_reference\RecursiveRenderingException;
use Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @Plugin(
 *   id = "entity_reference_entity_view",
 *   module = "entity_reference",
 *   label = @Translation("Rendered entity"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   settings = {
 *     "view_mode" = "",
 *     "link" = FALSE
 *   }
 * )
 */
class EntityReferenceEntityFormatter extends EntityReferenceFormatterBase {

  /**
   * Overrides \Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $view_modes = entity_get_view_modes($this->field['settings']['target_type']);
    $options = array();
    foreach ($view_modes as $view_mode => $view_mode_settings) {
      $options[$view_mode] = $view_mode_settings['label'];
    }

    $elements['view_mode'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    );

    $elements['links'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show links'),
      '#default_value' => $this->getSetting('links'),
    );

    return $elements;
  }

  /**
   * Overrides \Drupal\field\Plugin\Type\Formatter\FormatterBase::settingsSummary().
   */
  public function settingsSummary() {
    $summary = array();

    $view_modes = entity_get_view_modes($this->field['settings']['target_type']);
    $view_mode = $this->getSetting('view_mode');
    $summary[] = t('Rendered as @mode', array('@mode' => isset($view_modes[$view_mode]['label']) ? $view_modes[$view_mode]['label'] : $view_mode));
    $summary[] = $this->getSetting('links') ? t('Display links') : t('Do not display links');

    return implode('<br />', $summary);
  }

  /**
   * Overrides \Drupal\entity_reference\Plugin\field\formatter\EntityReferenceFormatterBase::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    // Remove un-accessible items.
    parent::viewElements($entity, $langcode, $items);

    $view_mode = $this->getSetting('view_mode');
    $links = $this->getSetting('links');

    $target_type = $this->field['settings']['target_type'];

    $elements = array();

    foreach ($items as $delta => $item) {
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        throw new RecursiveRenderingException(format_string('Recursive rendering detected when rendering entity @entity_type(@entity_id). Aborting rendering.', array('@entity_type' => $entity_type, '@entity_id' => $item['target_id'])));
      }

      if (!empty($item['entity'])) {
        $entity = clone $item['entity'];
        unset($entity->content);
        $elements[$delta] = entity_view($entity, $view_mode, $langcode);

        if (empty($links) && isset($result[$delta][$target_type][$item['target_id']]['links'])) {
          // Hide the element links.
          $elements[$delta][$target_type][$item['target_id']]['links']['#access'] = FALSE;
        }
      }
      else {
        // This is an "auto_create" item.
        $elements[$delta] = array('#markup' => $item['label']);
      }
      $depth = 0;
    }

    return $elements;
  }
}
