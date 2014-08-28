<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\Fieldgroup.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a render element for a group of form elements.
 *
 * In default rendering, the only difference between a 'fieldgroup' and a
 * 'fieldset' is the CSS class applied to the containing HTML element.
 *
 * @see \Drupal\Core\Render\Element\Fieldset
 * @see \Drupal\Core\Render\Element\Details
 *
 * @RenderElement("fieldgroup")
 */
class Fieldgroup extends Fieldset {

  public function getInfo() {
    return array(
      '#attributes' => array('class' => array('fieldgroup')),
    ) + parent::getInfo();
  }

}
