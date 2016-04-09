<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form constructor to test the #required property.
 */
class FormTestValidateRequiredForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_validate_required_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array('foo' => 'foo', 'bar' => 'bar');
    $validate = array('::elementValidateRequired');

    $form['textfield'] = array(
      '#type' => 'textfield',
      '#title' => 'Name',
      '#required' => TRUE,
      '#required_error' => t('Please enter a name.'),
    );
    $form['checkboxes'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes',
      '#options' => $options,
      '#required' => TRUE,
      '#form_test_required_error' => t('Please choose at least one option.'),
      '#element_validate' => $validate,
    );
    $form['select'] = array(
      '#type' => 'select',
      '#title' => 'Select',
      '#options' => $options,
      '#required' => TRUE,
      '#form_test_required_error' => t('Please select something.'),
      '#element_validate' => $validate,
    );
    $form['radios'] = array(
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => $options,
      '#required' => TRUE,
    );
    $form['radios_optional'] = array(
      '#type' => 'radios',
      '#title' => 'Radios (optional)',
      '#options' => $options,
    );
    $form['radios_optional_default_value_false'] = array(
      '#type' => 'radios',
      '#title' => 'Radios (optional, with a default value of FALSE)',
      '#options' => $options,
      '#default_value' => FALSE,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => 'Submit');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidateRequired($element, FormStateInterface $form_state) {
    // Set a custom validation error on the #required element.
    if (!empty($element['#required_but_empty']) && isset($element['#form_test_required_error'])) {
      $form_state->setError($element, $element['#form_test_required_error']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('The form_test_validate_required_form form was submitted successfully.');
  }

}
