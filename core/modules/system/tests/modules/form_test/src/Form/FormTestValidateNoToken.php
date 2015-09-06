<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestValidateNoToken.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to test the validation of forms with a disabled CSRF token.
 */
class FormTestValidateNoToken extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_validate_no_token';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#token'] = FALSE;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('The form_test_validate_no_token form has been submitted successfully.');
  }

}
