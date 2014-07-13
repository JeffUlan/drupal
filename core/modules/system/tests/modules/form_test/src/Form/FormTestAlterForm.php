<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestAlterForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;

/**
 * Form builder for testing hook_form_alter() and hook_form_FORM_ID_alter().
 */
class FormTestAlterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_alter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Elements can be added as needed for future testing needs, but for now,
    // we're only testing alter hooks that do not require any elements added by
    // this function.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
