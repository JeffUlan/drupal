<?php

/**
 * @file
 * Contains \Drupal\options\Plugin\field\formatter\OptionsDefaultFormatter.
 */

namespace Drupal\options\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'list_default' formatter.
 *
 * @Plugin(
 *   id = "list_default",
 *   module = "options",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_text",
 *     "list_boolean"
 *   }
 * )
 */
class OptionsDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    $allowed_values = options_allowed_values($this->field, $this->instance, $entity);

    foreach ($items as $delta => $item) {
      if (isset($allowed_values[$item['value']])) {
        $output = field_filter_xss($allowed_values[$item['value']]);
      }
      else {
        // If no match was found in allowed values, fall back to the key.
        $output = field_filter_xss($item['value']);
      }
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
