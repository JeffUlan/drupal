<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormValidator.
 */

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides validation of form submissions.
 */
class FormValidator implements FormValidatorInterface {

  use StringTranslationTrait;

  /**
   * The CSRF token generator to validate the form token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new FormValidator.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(RequestStack $request_stack, TranslationInterface $string_translation, CsrfTokenGenerator $csrf_token) {
    $this->requestStack = $request_stack;
    $this->stringTranslation = $string_translation;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public function executeValidateHandlers(&$form, FormStateInterface &$form_state) {
    // If there was a button pressed, use its handlers.
    if (isset($form_state['validate_handlers'])) {
      $handlers = $form_state['validate_handlers'];
    }
    // Otherwise, check for a form-level handler.
    elseif (isset($form['#validate'])) {
      $handlers = $form['#validate'];
    }
    else {
      $handlers = array();
    }

    foreach ($handlers as $function) {
      call_user_func_array($function, array(&$form, &$form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm($form_id, &$form, FormStateInterface &$form_state) {
    // If this form is flagged to always validate, ensure that previous runs of
    // validation are ignored.
    if (!empty($form_state['must_validate'])) {
      $form_state['validation_complete'] = FALSE;
    }

    // If this form has completed validation, do not validate again.
    if (!empty($form_state['validation_complete'])) {
      return;
    }

    // If the session token was set by self::prepareForm(), ensure that it
    // matches the current user's session.
    if (isset($form['#token'])) {
      if (!$this->csrfToken->validate($form_state['values']['form_token'], $form['#token'])) {
        $url = $this->requestStack->getCurrentRequest()->getRequestUri();

        // Setting this error will cause the form to fail validation.
        $form_state->setErrorByName('form_token', $this->t('The form has become outdated. Copy any unsaved work in the form below and then <a href="@link">reload this page</a>.', array('@link' => $url)));

        // Stop here and don't run any further validation handlers, because they
        // could invoke non-safe operations which opens the door for CSRF
        // vulnerabilities.
        $this->finalizeValidation($form, $form_state, $form_id);
        return;
      }
    }

    // Recursively validate each form element.
    $this->doValidateForm($form, $form_state, $form_id);
    $this->finalizeValidation($form, $form_state, $form_id);
    $this->handleErrorsWithLimitedValidation($form, $form_state, $form_id);
  }

  /**
   * Handles validation errors for forms with limited validation.
   *
   * If validation errors are limited then remove any non validated form values,
   * so that only values that passed validation are left for submit callbacks.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The unique string identifying the form.
   */
  protected function handleErrorsWithLimitedValidation(&$form, FormStateInterface &$form_state, $form_id) {
    // If validation errors are limited then remove any non validated form values,
    // so that only values that passed validation are left for submit callbacks.
    if (isset($form_state['triggering_element']['#limit_validation_errors']) && $form_state['triggering_element']['#limit_validation_errors'] !== FALSE) {
      $values = array();
      foreach ($form_state['triggering_element']['#limit_validation_errors'] as $section) {
        // If the section exists within $form_state['values'], even if the value
        // is NULL, copy it to $values.
        $section_exists = NULL;
        $value = NestedArray::getValue($form_state['values'], $section, $section_exists);
        if ($section_exists) {
          NestedArray::setValue($values, $section, $value);
        }
      }
      // A button's #value does not require validation, so for convenience we
      // allow the value of the clicked button to be retained in its normal
      // $form_state['values'] locations, even if these locations are not
      // included in #limit_validation_errors.
      if (!empty($form_state['triggering_element']['#is_button'])) {
        $button_value = $form_state['triggering_element']['#value'];

        // Like all input controls, the button value may be in the location
        // dictated by #parents. If it is, copy it to $values, but do not
        // override what may already be in $values.
        $parents = $form_state['triggering_element']['#parents'];
        if (!NestedArray::keyExists($values, $parents) && NestedArray::getValue($form_state['values'], $parents) === $button_value) {
          NestedArray::setValue($values, $parents, $button_value);
        }

        // Additionally, self::doBuildForm() places the button value in
        // $form_state['values'][BUTTON_NAME]. If it's still there, after
        // validation handlers have run, copy it to $values, but do not override
        // what may already be in $values.
        $name = $form_state['triggering_element']['#name'];
        if (!isset($values[$name]) && isset($form_state['values'][$name]) && $form_state['values'][$name] === $button_value) {
          $values[$name] = $button_value;
        }
      }
      $form_state['values'] = $values;
    }
  }

  /**
   * Finalizes validation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The unique string identifying the form.
   */
  protected function finalizeValidation(&$form, FormStateInterface &$form_state, $form_id) {
    // After validation, loop through and assign each element its errors.
    $this->setElementErrorsFromFormState($form, $form_state);
    // Mark this form as validated.
    $form_state['validation_complete'] = TRUE;
  }

  /**
   * Performs validation on form elements.
   *
   * First ensures required fields are completed, #maxlength is not exceeded,
   * and selected options were in the list of options given to the user. Then
   * calls user-defined validators.
   *
   * @param $elements
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. The current user-submitted data is stored
   *   in $form_state['values'], though form validation functions are passed an
   *   explicit copy of the values for the sake of simplicity. Validation
   *   handlers can also $form_state to pass information on to submit handlers.
   *   For example:
   *     $form_state['data_for_submission'] = $data;
   *   This technique is useful when validation requires file parsing,
   *   web service requests, or other expensive requests that should
   *   not be repeated in the submission step.
   * @param $form_id
   *   A unique string identifying the form for validation, submission,
   *   theming, and hook_form_alter functions.
   */
  protected function doValidateForm(&$elements, FormStateInterface &$form_state, $form_id = NULL) {
    // Recurse through all children.
    foreach (Element::children($elements) as $key) {
      if (isset($elements[$key]) && $elements[$key]) {
        $this->doValidateForm($elements[$key], $form_state);
      }
    }

    // Validate the current input.
    if (!isset($elements['#validated']) || !$elements['#validated']) {
      // The following errors are always shown.
      if (isset($elements['#needs_validation'])) {
        $this->performRequiredValidation($elements, $form_state);
      }

      // Set up the limited validation for errors.
      $form_state['limit_validation_errors'] = $this->determineLimitValidationErrors($form_state);

      // Make sure a value is passed when the field is required.
      if (isset($elements['#needs_validation']) && $elements['#required']) {
        // A simple call to empty() will not cut it here as some fields, like
        // checkboxes, can return a valid value of '0'. Instead, check the
        // length if it's a string, and the item count if it's an array.
        // An unchecked checkbox has a #value of integer 0, different than
        // string '0', which could be a valid value.
        $is_empty_multiple = (!count($elements['#value']));
        $is_empty_string = (is_string($elements['#value']) && Unicode::strlen(trim($elements['#value'])) == 0);
        $is_empty_value = ($elements['#value'] === 0);
        if ($is_empty_multiple || $is_empty_string || $is_empty_value) {
          // Flag this element as #required_but_empty to allow #element_validate
          // handlers to set a custom required error message, but without having
          // to re-implement the complex logic to figure out whether the field
          // value is empty.
          $elements['#required_but_empty'] = TRUE;
        }
      }

      // Call user-defined form level validators.
      if (isset($form_id)) {
        $this->executeValidateHandlers($elements, $form_state);
      }
      // Call any element-specific validators. These must act on the element
      // #value data.
      elseif (isset($elements['#element_validate'])) {
        foreach ($elements['#element_validate'] as $callback) {
          call_user_func_array($callback, array(&$elements, &$form_state, &$form_state['complete_form']));
        }
      }

      // Ensure that a #required form error is thrown, regardless of whether
      // #element_validate handlers changed any properties. If $is_empty_value
      // is defined, then above #required validation code ran, so the other
      // variables are also known to be defined and we can test them again.
      if (isset($is_empty_value) && ($is_empty_multiple || $is_empty_string || $is_empty_value)) {
        if (isset($elements['#required_error'])) {
          $form_state->setError($elements, $elements['#required_error']);
        }
        // A #title is not mandatory for form elements, but without it we cannot
        // set a form error message. So when a visible title is undesirable,
        // form constructors are encouraged to set #title anyway, and then set
        // #title_display to 'invisible'. This improves accessibility.
        elseif (isset($elements['#title'])) {
          $form_state->setError($elements, $this->t('!name field is required.', array('!name' => $elements['#title'])));
        }
        else {
          $form_state->setError($elements);
        }
      }

      $elements['#validated'] = TRUE;
    }

    // Done validating this element, so turn off error suppression.
    // self::doValidateForm() turns it on again when starting on the next
    // element, if it's still appropriate to do so.
    $form_state['limit_validation_errors'] = NULL;
  }

  /**
   * Performs validation of elements that are not subject to limited validation.
   *
   * @param array $elements
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. The current user-submitted data is stored
   *   in $form_state['values'], though form validation functions are passed an
   *   explicit copy of the values for the sake of simplicity. Validation
   *   handlers can also $form_state to pass information on to submit handlers.
   *   For example:
   *     $form_state['data_for_submission'] = $data;
   *   This technique is useful when validation requires file parsing,
   *   web service requests, or other expensive requests that should
   *   not be repeated in the submission step.
   */
  protected function performRequiredValidation(&$elements, FormStateInterface &$form_state) {
    // Verify that the value is not longer than #maxlength.
    if (isset($elements['#maxlength']) && Unicode::strlen($elements['#value']) > $elements['#maxlength']) {
      $form_state->setError($elements, $this->t('!name cannot be longer than %max characters but is currently %length characters long.', array('!name' => empty($elements['#title']) ? $elements['#parents'][0] : $elements['#title'], '%max' => $elements['#maxlength'], '%length' => Unicode::strlen($elements['#value']))));
    }

    if (isset($elements['#options']) && isset($elements['#value'])) {
      if ($elements['#type'] == 'select') {
        $options = OptGroup::flattenOptions($elements['#options']);
      }
      else {
        $options = $elements['#options'];
      }
      if (is_array($elements['#value'])) {
        $value = in_array($elements['#type'], array('checkboxes', 'tableselect')) ? array_keys($elements['#value']) : $elements['#value'];
        foreach ($value as $v) {
          if (!isset($options[$v])) {
            $form_state->setError($elements, $this->t('An illegal choice has been detected. Please contact the site administrator.'));
            $this->watchdog('form', 'Illegal choice %choice in !name element.', array('%choice' => $v, '!name' => empty($elements['#title']) ? $elements['#parents'][0] : $elements['#title']), WATCHDOG_ERROR);
          }
        }
      }
      // Non-multiple select fields always have a value in HTML. If the user
      // does not change the form, it will be the value of the first option.
      // Because of this, form validation for the field will almost always
      // pass, even if the user did not select anything. To work around this
      // browser behavior, required select fields without a #default_value
      // get an additional, first empty option. In case the submitted value
      // is identical to the empty option's value, we reset the element's
      // value to NULL to trigger the regular #required handling below.
      // @see form_process_select()
      elseif ($elements['#type'] == 'select' && !$elements['#multiple'] && $elements['#required'] && !isset($elements['#default_value']) && $elements['#value'] === $elements['#empty_value']) {
        $elements['#value'] = NULL;
        NestedArray::setValue($form_state['values'], $elements['#parents'], NULL, TRUE);
      }
      elseif (!isset($options[$elements['#value']])) {
        $form_state->setError($elements, $this->t('An illegal choice has been detected. Please contact the site administrator.'));
        $this->watchdog('form', 'Illegal choice %choice in %name element.', array('%choice' => $elements['#value'], '%name' => empty($elements['#title']) ? $elements['#parents'][0] : $elements['#title']), WATCHDOG_ERROR);
      }
    }
  }

  /**
   * Determines if validation errors should be limited.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|null
   */
  protected function determineLimitValidationErrors(FormStateInterface &$form_state) {
    // While this element is being validated, it may be desired that some
    // calls to \Drupal\Core\Form\FormStateInterface::setErrorByName() be
    // suppressed and not result in a form error, so that a button that
    // implements low-risk functionality (such as "Previous" or "Add more") that
    // doesn't require all user input to be valid can still have its submit
    // handlers triggered. The triggering element's #limit_validation_errors
    // property contains the information for which errors are needed, and all
    // other errors are to be suppressed. The #limit_validation_errors property
    // is ignored if submit handlers will run, but the element doesn't have a
    // #submit property, because it's too large a security risk to have any
    // invalid user input when executing form-level submit handlers.
    if (isset($form_state['triggering_element']['#limit_validation_errors']) && ($form_state['triggering_element']['#limit_validation_errors'] !== FALSE) && !($form_state['submitted'] && !isset($form_state['triggering_element']['#submit']))) {
      return $form_state['triggering_element']['#limit_validation_errors'];
    }
    // If submit handlers won't run (due to the submission having been
    // triggered by an element whose #executes_submit_callback property isn't
    // TRUE), then it's safe to suppress all validation errors, and we do so
    // by default, which is particularly useful during an Ajax submission
    // triggered by a non-button. An element can override this default by
    // setting the #limit_validation_errors property. For button element
    // types, #limit_validation_errors defaults to FALSE (via
    // system_element_info()), so that full validation is their default
    // behavior.
    elseif (isset($form_state['triggering_element']) && !isset($form_state['triggering_element']['#limit_validation_errors']) && !$form_state['submitted']) {
      return array();
    }
    // As an extra security measure, explicitly turn off error suppression if
    // one of the above conditions wasn't met. Since this is also done at the
    // end of this function, doing it here is only to handle the rare edge
    // case where a validate handler invokes form processing of another form.
    else {
      return NULL;
    }
  }

  /**
   * Stores the errors of each element directly on the element.
   *
   * We must provide a way for non-form functions to check the errors for a
   * specific element. The most common usage of this is a #pre_render callback.
   *
   * @param array $elements
   *   An associative array containing the structure of a form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setElementErrorsFromFormState(array &$elements, FormStateInterface &$form_state) {
    // Recurse through all children.
    foreach (Element::children($elements) as $key) {
      if (isset($elements[$key]) && $elements[$key]) {
        $this->setElementErrorsFromFormState($elements[$key], $form_state);
      }
    }
    // Store the errors for this element on the element directly.
    $elements['#errors'] = $form_state->getError($elements);
  }

  /**
   * Wraps watchdog().
   */
  protected function watchdog($type, $message, array $variables = array(), $severity = WATCHDOG_NOTICE, $link = NULL) {
    watchdog($type, $message, $variables, $severity, $link);
  }

}
