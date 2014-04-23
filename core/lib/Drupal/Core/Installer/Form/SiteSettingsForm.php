<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\Form\SiteSettingsForm.
 */

namespace Drupal\Core\Installer\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormBase;

/**
 * Provides a form to configure and rewrite settings.php.
 */
class SiteSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'install_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    global $databases;

    $conf_path = './' . conf_path(FALSE);
    $settings_file = $conf_path . '/settings.php';

    $form['#title'] = $this->t('Database configuration');

    $drivers = drupal_get_database_types();
    $drivers_keys = array_keys($drivers);

    // If database connection settings have been prepared in settings.php already,
    // then the existing values need to be taken over.
    // Note: The installer even executes this form if there is a valid database
    // connection already, since the submit handler of this form is responsible
    // for writing all $settings to settings.php (not limited to $databases).
    if (isset($databases['default']['default'])) {
      $default_driver = $databases['default']['default']['driver'];
      $default_options = $databases['default']['default'];
    }
    // Otherwise, use the database connection settings from the form input.
    // For a non-interactive installation, this is derived from the original
    // $settings array passed into install_drupal().
    elseif (isset($form_state['input']['driver'])) {
      $default_driver = $form_state['input']['driver'];
      $default_options = $form_state['input'][$default_driver];
    }
    // If there is no database information at all yet, just suggest the first
    // available driver as default value, so that its settings form is made
    // visible via #states when JavaScript is enabled (see below).
    else {
      $default_driver = current($drivers_keys);
      $default_options = array();
    }

    $form['driver'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Database type'),
      '#required' => TRUE,
      '#default_value' => $default_driver,
    );
    if (count($drivers) == 1) {
      $form['driver']['#disabled'] = TRUE;
    }

    // Add driver specific configuration options.
    foreach ($drivers as $key => $driver) {
      $form['driver']['#options'][$key] = $driver->name();

      $form['settings'][$key] = $driver->getFormOptions($default_options);
      $form['settings'][$key]['#prefix'] = '<h2 class="js-hide">' . $this->t('@driver_name settings', array('@driver_name' => $driver->name())) . '</h2>';
      $form['settings'][$key]['#type'] = 'container';
      $form['settings'][$key]['#tree'] = TRUE;
      $form['settings'][$key]['advanced_options']['#parents'] = array($key);
      $form['settings'][$key]['#states'] = array(
        'visible' => array(
          ':input[name=driver]' => array('value' => $key),
        )
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#limit_validation_errors' => array(
        array('driver'),
        array($default_driver),
      ),
      '#submit' => array(array($this, 'submitForm')),
    );

    $form['errors'] = array();
    $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $driver = $form_state['values']['driver'];
    $database = $form_state['values'][$driver];
    $drivers = drupal_get_database_types();
    $reflection = new \ReflectionClass($drivers[$driver]);
    $install_namespace = $reflection->getNamespaceName();
    // Cut the trailing \Install from namespace.
    $database['namespace'] = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
    $database['driver'] = $driver;

    $form_state['storage']['database'] = $database;
    $errors = install_database_errors($database, $form_state['values']['settings_file']);
    foreach ($errors as $name => $message) {
      $this->setFormError($name, $form_state, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    global $install_state;

    // Update global settings array and save.
    $settings = array();
    $database = $form_state['storage']['database'];
    $settings['databases']['default']['default'] = (object) array(
      'value'    => $database,
      'required' => TRUE,
    );
    $settings['settings']['hash_salt'] = (object) array(
      'value'    => Crypt::randomBytesBase64(55),
      'required' => TRUE,
    );
    // Remember the profile which was used.
    $settings['settings']['install_profile'] = (object) array(
      'value' => $install_state['parameters']['profile'],
      'required' => TRUE,
    );

    drupal_rewrite_settings($settings);

    // Add the config directories to settings.php.
    drupal_install_config_directories();

    // Indicate that the settings file has been verified, and check the database
    // for the last completed task, now that we have a valid connection. This
    // last step is important since we want to trigger an error if the new
    // database already has Drupal installed.
    $install_state['settings_verified'] = TRUE;
    $install_state['config_verified'] = TRUE;
    $install_state['database_verified'] = TRUE;
    $install_state['completed_task'] = install_verify_completed_task();
  }

}
