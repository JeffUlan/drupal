<?php
/**
 * @file
 * Contains \Drupal\language_elements_test\Form\LanguageConfigurationElementTest.
 */

namespace Drupal\language_elements_test\Form;

use Drupal\Core\Form\FormBase;

/**
 * A form containing a language select element.
 */
class LanguageConfigurationElementTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'language_elements_configuration_element_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['langcode'] = array(
      '#title' => t('Language select'),
      '#type' => 'language_select',
      '#default_value' => language_get_default_langcode('custom_type', 'some_bundle'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }
}
