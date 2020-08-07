<?php

namespace Drupal\KernelTests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests automatically added form handlers.
 *
 * @group Form
 */
class FormDefaultHandlersTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_form_handlers';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#validate'][] = '::customValidateForm';
    $form['#submit'][] = '::customSubmitForm';
    $form['submit'] = ['#type' => 'submit', '#value' => 'Save'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function customValidateForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['validate'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['validate'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function customSubmitForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['submit'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $test_handlers = $form_state->get('test_handlers');
    $test_handlers['submit'][] = __FUNCTION__;
    $form_state->set('test_handlers', $test_handlers);
  }

  /**
   * Tests that default handlers are added even if custom are specified.
   */
  public function testDefaultAndCustomHandlers() {
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    $handlers = $form_state->get('test_handlers');

    $this->assertCount(2, $handlers['validate']);
    $this->assertIdentical($handlers['validate'][0], 'customValidateForm');
    $this->assertIdentical($handlers['validate'][1], 'validateForm');

    $this->assertCount(2, $handlers['submit']);
    $this->assertIdentical($handlers['submit'][0], 'customSubmitForm');
    $this->assertIdentical($handlers['submit'][1], 'submitForm');
  }

}
