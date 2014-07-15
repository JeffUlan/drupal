<?php

/**
 * @file
 * Contains \Drupal\locale\Form\TranslationStatusForm.
 */

namespace Drupal\locale\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a translation status form.
 */
class TranslationStatusForm extends FormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * Constructs a TranslationStatusForm object.
   *
   * @param ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, StateInterface $state) {
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'locale_translation_status_form';
  }

  /**
   * Form builder for displaying the current translation status.
   *
   * @ingroup forms
   */
  public function buildForm(array $form, array &$form_state) {
    $languages = locale_translatable_language_list();
    $status = locale_translation_get_status();
    $options = array();
    $languages_update = array();
    $languages_not_found = array();
    $projects_update = array();
    // Prepare information about projects which have available translation
    // updates.
    if ($languages && $status) {
      $updates = $this->prepareUpdateData($status);

      // Build data options for the select table.
      foreach ($updates as $langcode => $update) {
        $title = String::checkPlain($languages[$langcode]->name);
        $locale_translation_update_info = array('#theme' => 'locale_translation_update_info');
        foreach (array('updates', 'not_found') as $update_status) {
          if (isset($update[$update_status])) {
            $locale_translation_update_info['#' . $update_status] = $update[$update_status];
          }
        }
        $options[$langcode] = array(
          'title' => array(
            'class' => array('label'),
            'data' => array(
              '#title' => $title,
              '#markup' => $title,
            ),
          ),
          'status' => array(
            'class' => array('description', 'expand', 'priority-low'),
            'data' => drupal_render($locale_translation_update_info),
          ),
        );
        if (!empty($update['not_found'])) {
          $languages_not_found[$langcode] = $langcode;
        }
        elseif (!empty($update['updates'])) {
          $languages_update[$langcode] = $langcode;
        }
      }
      // Sort the table data on language name.
      uasort($options, function ($a, $b) {
        return strcasecmp($a['title']['data']['#title'], $b['title']['data']['#title']);
      });
      $languages_not_found = array_diff($languages_not_found, $languages_update);
    }

    $last_checked = $this->state->get('locale.translation_last_checked');
    $form['last_checked'] = array(
      '#theme' => 'locale_translation_last_check',
      '#last' => $last_checked,
    );

    $header = array(
      'title' => array(
        'data' => $this->t('Language'),
        'class' => array('title'),
      ),
      'status' => array(
        'data' => $this->t('Status'),
        'class' => array('status', 'priority-low'),
      ),
    );

    if (!$languages) {
      $empty = $this->t('No translatable languages available. <a href="@add_language">Add a language</a> first.', array(
        '@add_language' => $this->url('language.admin_overview'),
      ));
    }
    elseif ($status) {
      $empty = $this->t('All translations up to date.');
    }
    else {
      $empty = $this->t('No translation status available. <a href="@check">Check manually</a>.', array(
        '@check' => $this->url('locale.check_translation'),
      ));
    }

    // The projects which require an update. Used by the _submit callback.
    $form['projects_update'] = array(
      '#type' => 'value',
      '#value' => $projects_update,
    );

    $form['langcodes'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $languages_update,
      '#empty' => $empty,
      '#js_select' => TRUE,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#not_found' => $languages_not_found,
      '#after_build' => array('locale_translation_language_table'),
    );

    $form['#attached']['library'][] = 'locale/drupal.locale.admin';
    $form['#attached']['css'] = array(drupal_get_path('module', 'locale') . '/css/locale.admin.css');

    $form['actions'] = array('#type' => 'actions');
    if ($languages_update) {
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Update translations'),
      );
    }

    return $form;
  }

  /**
   * Prepare information about projects with available translation updates.
   *
   * @param array $status
   *   Translation update status as an array keyed by Project ID and langcode.
   *
   * @return array
   *   Translation update status as an array keyed by language code and
   *   translation update status.
   */
  protected function prepareUpdateData(array $status) {
    $updates = array();

    // @todo Calling locale_translation_build_projects() is an expensive way to
    //   get a module name. In follow-up issue http://drupal.org/node/1842362
    //   the project name will be stored to display use, like here.
    $this->moduleHandler->loadInclude('locale', 'compare.inc');
    $project_data = locale_translation_build_projects();

    foreach ($status as $project_id => $project) {
      foreach ($project as $langcode => $project_info) {
        // No translation file found for this project-language combination.
        if (empty($project_info->type)) {
          $updates[$langcode]['not_found'][] = array(
            'name' => $project_info->name == 'drupal' ? $this->t('Drupal core') : $project_data[$project_info->name]->info['name'],
            'version' => $project_info->version,
            'info' => $this->createInfoString($project_info),
          );
        }
        // Translation update found for this project-language combination.
        elseif ($project_info->type == LOCALE_TRANSLATION_LOCAL || $project_info->type == LOCALE_TRANSLATION_REMOTE) {
          $local = isset($project_info->files[LOCALE_TRANSLATION_LOCAL]) ? $project_info->files[LOCALE_TRANSLATION_LOCAL] : NULL;
          $remote = isset($project_info->files[LOCALE_TRANSLATION_REMOTE]) ? $project_info->files[LOCALE_TRANSLATION_REMOTE] : NULL;
          $recent = _locale_translation_source_compare($local, $remote) == LOCALE_TRANSLATION_SOURCE_COMPARE_LT ? $remote : $local;
          $updates[$langcode]['updates'][] = array(
            'name' => $project_data[$project_info->name]->info['name'],
            'version' => $project_info->version,
            'timestamp' => $recent->timestamp,
          );
        }
      }
    }
    return $updates;
  }

  /**
   * Provides debug info for projects in case translation files are not found.
   *
   * Translations files are being fetched either from Drupal translation server
   * and local files or only from the local filesystem depending on the
   * "Translation source" setting at admin/config/regional/translate/settings.
   * This method will produce debug information including the respective path(s)
   * based on this setting.
   *
   * Translations for development versions are never fetched, so the debug info
   * for that is a fixed message.
   *
   * @param array $project_info
   *   An array which is the project information of the source.
   *
   * @return string
   *   The string which contains debug information.
   */
  protected function createInfoString($project_info) {
    $remote_path = isset($project_info->files['remote']->uri) ? $project_info->files['remote']->uri : FALSE;
    $local_path = isset($project_info->files['local']->uri) ? $project_info->files['local']->uri : FALSE;

    if (strpos($project_info->version, 'dev') !== FALSE) {
      return $this->t('No translation files are provided for development releases.');
    }
    if (locale_translation_use_remote_source() && $remote_path && $local_path) {
      return $this->t('File not found at %remote_path nor at %local_path', array(
        '%remote_path' => $remote_path,
        '%local_path' => $local_path,
      ));
    }
    elseif ($local_path) {
      return $this->t('File not found at %local_path', array('%local_path' => $local_path));
    }
    return $this->t('Translation file location could not be determined.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Check if a language has been selected. 'tableselect' doesn't.
    if (!array_filter($form_state['values']['langcodes'])) {
      $this->setFormError('', $this->t('Select a language to update.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->moduleHandler->loadInclude('locale', 'fetch.inc');
    $langcodes = array_filter($form_state['values']['langcodes']);
    $projects = array_filter($form_state['values']['projects_update']);

    // Set the translation import options. This determines if existing
    // translations will be overwritten by imported strings.
    $options = _locale_translation_default_update_options();

    // If the status was updated recently we can immediately start fetching the
    // translation updates. If the status is expired we clear it an run a batch to
    // update the status and then fetch the translation updates.
    $last_checked = $this->state->get('locale.translation_last_checked');
    if ($last_checked < REQUEST_TIME - LOCALE_TRANSLATION_STATUS_TTL) {
      locale_translation_clear_status();
      $batch = locale_translation_batch_update_build(array(), $langcodes, $options);
      batch_set($batch);
    }
    else {
      $batch = locale_translation_batch_fetch_build($projects, $langcodes, $options);
      batch_set($batch);
    }
  }

}
