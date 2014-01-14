<?php

/**
 * @file
 * Contains \Drupal\edit\Plugin\InPlaceEditor\FormEditor.
 */

namespace Drupal\edit\Plugin\InPlaceEditor;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\edit\EditorBase;

/**
 * Defines the form in-place editor.
 *
 * @InPlaceEditor(
 *   id = "form"
 * )
 */
class FormEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  public function isCompatible(FieldItemListInterface $items) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return array(
      'library' => array(
        array('edit', 'edit.inPlaceEditor.form'),
      ),
    );
  }

}
