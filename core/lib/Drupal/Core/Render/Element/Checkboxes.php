<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Checkboxes.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form element for a set of checkboxes.
 *
 * #options is an associative array, where the key is the #return_value of the
 * checkbox and the value is displayed. The #options array cannot have a 0 key,
 * as it would not be possible to discern checked and unchecked states.
 *
 * @see \Drupal\Core\Render\Element\Radios
 * @see \Drupal\Core\Render\Element\Checkbox
 *
 * @FormElement("checkboxes")
 */
class Checkboxes extends FormElement {

  use CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processCheckboxes'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderCompositeFormElement'),
      ),
      '#theme_wrappers' => array('checkboxes'),
    );
  }

  /**
   * Processes a checkboxes form element.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = is_array($element['#value']) ? $element['#value'] : array();
    $element['#tree'] = TRUE;
    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
        $element['#default_value'] = array();
      }
      $weight = 0;
      foreach ($element['#options'] as $key => $choice) {
        // Integer 0 is not a valid #return_value, so use '0' instead.
        // @see form_type_checkbox_value().
        // @todo For Drupal 8, cast all integer keys to strings for consistency
        //   with form_process_radios().
        if ($key === 0) {
          $key = '0';
        }
        // Maintain order of options as defined in #options, in case the element
        // defines custom option sub-elements, but does not define all option
        // sub-elements.
        $weight += 0.001;

        $element += array($key => array());
        $element[$key] += array(
          '#type' => 'checkbox',
          '#title' => $choice,
          '#return_value' => $key,
          '#default_value' => isset($value[$key]) ? $key : NULL,
          '#attributes' => $element['#attributes'],
          '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
          '#weight' => $weight,
        );
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      $value = array();
      $element += array('#default_value' => array());
      foreach ($element['#default_value'] as $key) {
        $value[$key] = $key;
      }
      return $value;
    }
    elseif (is_array($input)) {
      // Programmatic form submissions use NULL to indicate that a checkbox
      // should be unchecked; see drupal_form_submit(). We therefore remove all
      // NULL elements from the array before constructing the return value, to
      // simulate the behavior of web browsers (which do not send unchecked
      // checkboxes to the server at all). This will not affect non-programmatic
      // form submissions, since all values in \Drupal::request()->request are
      // strings.
      foreach ($input as $key => $value) {
        if (!isset($value)) {
          unset($input[$key]);
        }
      }
      return array_combine($input, $input);
    }
    else {
      return array();
    }
  }

}
