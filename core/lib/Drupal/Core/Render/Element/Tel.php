<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Tel.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Element;

/**
 * Provides a form element for entering a telephone number.
 *
 * @FormElement("tel")
 */
class Tel extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#size' => 30,
      '#maxlength' => 128,
      '#autocomplete_route_name' => FALSE,
      '#process' => array(
        array($class, 'processAutocomplete'),
        array($class, 'processAjaxForm'),
        array($class, 'processPattern'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderTel'),
      ),
      '#theme' => 'input__tel',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * Prepares a #type 'tel' render element for theme_input().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for theme_input().
   */
  public static function preRenderTel($element) {
    $element['#attributes']['type'] = 'tel';
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-tel'));

    return $element;
  }

}
