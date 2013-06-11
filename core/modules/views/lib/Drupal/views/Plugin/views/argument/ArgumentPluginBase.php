<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\ArgumentPluginBase.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Views;

/**
 * @defgroup views_argument_handlers Views argument handlers
 * Handlers to tell Views how to contextually filter queries.
 * @{
 */

/**
 * Base class for arguments.
 *
 * The basic argument works for very simple arguments such as nid and uid
 *
 * Definition terms for this handler:
 * - name field: The field to use for the name to use in the summary, which is
 *               the displayed output. For example, for the node: nid argument,
 *               the argument itself is the nid, but node.title is displayed.
 * - name table: The table to use for the name, should it not be in the same
 *               table as the argument.
 * - empty field name: For arguments that can have no value, such as taxonomy
 *                     which can have "no term", this is the string which
 *                     will be displayed for this lack of value. Be sure to use
 *                     t().
 * - validate type: A little used string to allow an argument to restrict
 *                  which validator is available to just one. Use the
 *                  validator ID. This probably should not be used at all,
 *                  and may disappear or change.
 * - numeric: If set to TRUE this field is numeric and will use %d instead of
 *            %s in queries.
 *
 * @ingroup views_argument_handlers
 */
abstract class ArgumentPluginBase extends HandlerBase {

  var $validator = NULL;
  var $argument = NULL;
  var $value = NULL;

  /**
   * The table to use for the name, should it not be in the same table as the argument.
   * @var string
   */
  var $name_table;

  /**
   * The field to use for the name to use in the summary, which is
   * the displayed output. For example, for the node: nid argument,
   * the argument itself is the nid, but node.title is displayed.
   * @var string
   */
  var $name_field;

  /**
   * Overrides Drupal\views\Plugin\views\HandlerBase:init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->definition['name field'])) {
      $this->name_field = $this->definition['name field'];
    }
    if (!empty($this->definition['name table'])) {
      $this->name_table = $this->definition['name table'];
    }
  }

  /**
   * Give an argument the opportunity to modify the breadcrumb, if it wants.
   * This only gets called on displays where a breadcrumb is actually used.
   *
   * The breadcrumb will be in the form of an array, with the keys being
   * the path and the value being the already sanitized title of the path.
   */
  public function setBreadcrumb(&$breadcrumb) { }

  /**
   * Determine if the argument can generate a breadcrumb
   *
   * @return TRUE/FALSE
   */
  public function usesBreadcrumb() {
    $info = $this->defaultActions($this->options['default_action']);
    return !empty($info['breadcrumb']);
  }

  public function isException($arg = NULL) {
    if (!isset($arg)) {
      $arg = isset($this->argument) ? $this->argument : NULL;
    }
    return !empty($this->options['exception']['value']) && $this->options['exception']['value'] === $arg;
  }

  public function exceptionTitle() {
    // If title overriding is off for the exception, return the normal title.
    if (empty($this->options['exception']['title_enable'])) {
      return $this->getTitle();
    }
    return $this->options['exception']['title'];
  }

  /**
   * Determine if the argument needs a style plugin.
   *
   * @return TRUE/FALSE
   */
  public function needsStylePlugin() {
    $info = $this->defaultActions($this->options['default_action']);
    $validate_info = $this->defaultActions($this->options['validate']['fail']);
    return !empty($info['style plugin']) || !empty($validate_info['style plugin']);
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['default_action'] = array('default' => 'ignore');
    $options['exception'] = array(
      'contains' => array(
        'value' => array('default' => 'all'),
        'title_enable' => array('default' => FALSE, 'bool' => TRUE),
        'title' => array('default' => 'All', 'translatable' => TRUE),
      ),
    );
    $options['title_enable'] = array('default' => FALSE, 'bool' => TRUE);
    $options['title'] = array('default' => '', 'translatable' => TRUE);
    $options['breadcrumb_enable'] = array('default' => FALSE, 'bool' => TRUE);
    $options['breadcrumb'] = array('default' => '', 'translatable' => TRUE);
    $options['default_argument_type'] = array('default' => 'fixed');
    $options['default_argument_options'] = array('default' => array());
    $options['default_argument_skip_url'] = array('default' => FALSE, 'bool' => TRUE);
    $options['summary_options'] = array('default' => array());
    $options['summary'] = array(
      'contains' => array(
        'sort_order' => array('default' => 'asc'),
        'number_of_records' => array('default' => 0),
        'format' => array('default' => 'default_summary'),
      ),
    );
    $options['specify_validation'] = array('default' => FALSE, 'bool' => TRUE);
    $options['validate'] = array(
      'contains' => array(
        'type' => array('default' => 'none'),
        'fail' => array('default' => 'not found'),
      ),
    );
    $options['validate_options'] = array('default' => array());

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $argument_text = $this->view->display_handler->getArgumentText();

    $form['#pre_render'][] = 'views_ui_pre_render_move_argument_options';

    $form['description'] = array(
      '#markup' => $argument_text['description'],
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('description')),
    );

    $form['no_argument'] = array(
      '#type' => 'details',
      '#title' => $argument_text['filter value not present'],
    );
    // Everything in the details is floated, so the last element needs to
    // clear those floats.
    $form['no_argument']['clearfix'] = array(
      '#weight' => 1000,
      '#markup' => '<div class="clearfix"></div>',
    );
    $form['default_action'] = array(
      '#type' => 'radios',
      '#process' => array(array($this, 'processContainerRadios')),
      '#default_value' => $this->options['default_action'],
      '#fieldset' => 'no_argument',
    );

    $form['exception'] = array(
      '#type' => 'details',
      '#title' => t('Exceptions'),
      '#collapsed' => TRUE,
      '#fieldset' => 'no_argument',
    );
    $form['exception']['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Exception value'),
      '#size' => 20,
      '#default_value' => $this->options['exception']['value'],
      '#description' => t('If this value is received, the filter will be ignored; i.e, "all values"'),
    );
    $form['exception']['title_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override title'),
      '#default_value' => $this->options['exception']['title_enable'],
    );
    $form['exception']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Override title'),
      '#title_display' => 'invisible',
      '#size' => 20,
      '#default_value' => $this->options['exception']['title'],
      '#description' => t('Override the view and other argument titles. Use "%1" for the first argument, "%2" for the second, etc.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[exception][title_enable]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $options = array();
    $defaults = $this->defaultActions();
    $validate_options = array();
    foreach ($defaults as $id => $info) {
      $options[$id] = $info['title'];
      if (empty($info['default only'])) {
        $validate_options[$id] = $info['title'];
      }
      if (!empty($info['form method'])) {
        $this->{$info['form method']}($form, $form_state);
      }
    }
    $form['default_action']['#options'] = $options;

    $form['argument_present'] = array(
      '#type' => 'details',
      '#title' => $argument_text['filter value present'],
    );
    $form['title_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override title'),
      '#default_value' => $this->options['title_enable'],
      '#fieldset' => 'argument_present',
    );
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide title'),
      '#title_display' => 'invisible',
      '#default_value' => $this->options['title'],
      '#description' => t('Override the view and other argument titles. Use "%1" for the first argument, "%2" for the second, etc.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[title_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'argument_present',
    );

    $form['breadcrumb_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Override breadcrumb'),
      '#default_value' => $this->options['breadcrumb_enable'],
      '#fieldset' => 'argument_present',
    );
    $form['breadcrumb'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide breadcrumb'),
      '#title_display' => 'invisible',
      '#default_value' => $this->options['breadcrumb'],
      '#description' => t('Enter a breadcrumb name you would like to use. See "Title" for percent substitutions.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[breadcrumb_enable]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'argument_present',
    );

    $form['specify_validation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Specify validation criteria'),
      '#default_value' => $this->options['specify_validation'],
      '#fieldset' => 'argument_present',
    );

    $form['validate'] = array(
      '#type' => 'container',
      '#fieldset' => 'argument_present',
    );
    $form['validate']['type'] = array(
      '#type' => 'select',
      '#title' => t('Validator'),
      '#default_value' => $this->options['validate']['type'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[specify_validation]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $plugins = Views::pluginManager('argument_validator')->getDefinitions();
    foreach ($plugins as $id => $info) {
      if (!empty($info['no_ui'])) {
        continue;
      }

      $valid = TRUE;
      if (!empty($info['type'])) {
        $valid = FALSE;
        if (empty($this->definition['validate type'])) {
          continue;
        }
        foreach ((array) $info['type'] as $type) {
          if ($type == $this->definition['validate type']) {
            $valid = TRUE;
            break;
          }
        }
      }

      // If we decide this validator is ok, add it to the list.
      if ($valid) {
        $plugin = $this->getPlugin('argument_validator', $id);
        if ($plugin) {
          if ($plugin->access() || $this->options['validate']['type'] == $id) {
            $form['validate']['options'][$id] = array(
              '#prefix' => '<div id="edit-options-validate-options-' . $id . '-wrapper">',
              '#suffix' => '</div>',
              '#type' => 'item',
              // Even if the plugin has no options add the key to the form_state.
              '#input' => TRUE, // trick it into checking input to make #process run
              '#states' => array(
                'visible' => array(
                  ':input[name="options[specify_validation]"]' => array('checked' => TRUE),
                  ':input[name="options[validate][type]"]' => array('value' => $id),
                ),
              ),
              '#id' => 'edit-options-validate-options-' . $id,
              '#default_value' => array(),
            );
            $plugin->buildOptionsForm($form['validate']['options'][$id], $form_state);
            $validate_types[$id] = $info['title'];
          }
        }
      }
    }

    asort($validate_types);
    $form['validate']['type']['#options'] = $validate_types;

    $form['validate']['fail'] = array(
      '#type' => 'select',
      '#title' => t('Action to take if filter value does not validate'),
      '#default_value' => $this->options['validate']['fail'],
      '#options' => $validate_options,
      '#states' => array(
        'visible' => array(
          ':input[name="options[specify_validation]"]' => array('checked' => TRUE),
        ),
      ),
      '#fieldset' => 'argument_present',
    );
  }

  public function validateOptionsForm(&$form, &$form_state) {
    if (empty($form_state['values']['options'])) {
      return;
    }

    // Let the plugins do validation.
    $default_id = $form_state['values']['options']['default_argument_type'];
    $plugin = $this->getPlugin('argument_default', $default_id);
    if ($plugin) {
      $plugin->validateOptionsForm($form['argument_default'][$default_id], $form_state, $form_state['values']['options']['argument_default'][$default_id]);
    }

    // summary plugin
    $summary_id = $form_state['values']['options']['summary']['format'];
    $plugin = $this->getPlugin('style', $summary_id);
    if ($plugin) {
      $plugin->validateOptionsForm($form['summary']['options'][$summary_id], $form_state, $form_state['values']['options']['summary']['options'][$summary_id]);
    }

    $validate_id = $form_state['values']['options']['validate']['type'];
    $plugin = $this->getPlugin('argument_validator', $validate_id);
    if ($plugin) {
      $plugin->validateOptionsForm($form['validate']['options'][$default_id], $form_state, $form_state['values']['options']['validate']['options'][$validate_id]);
    }

  }

  public function submitOptionsForm(&$form, &$form_state) {
    if (empty($form_state['values']['options'])) {
      return;
    }

    // Let the plugins make submit modifications if necessary.
    $default_id = $form_state['values']['options']['default_argument_type'];
    $plugin = $this->getPlugin('argument_default', $default_id);
    if ($plugin) {
      $options = &$form_state['values']['options']['argument_default'][$default_id];
      $plugin->submitOptionsForm($form['argument_default'][$default_id], $form_state, $options);
      // Copy the now submitted options to their final resting place so they get saved.
      $form_state['values']['options']['default_argument_options'] = $options;
    }

    // summary plugin
    $summary_id = $form_state['values']['options']['summary']['format'];
    $plugin = $this->getPlugin('style', $summary_id);
    if ($plugin) {
      $options = &$form_state['values']['options']['summary']['options'][$summary_id];
      $plugin->submitOptionsForm($form['summary']['options'][$summary_id], $form_state, $options);
      // Copy the now submitted options to their final resting place so they get saved.
      $form_state['values']['options']['summary_options'] = $options;
    }

    $validate_id = $form_state['values']['options']['validate']['type'];
    $plugin = $this->getPlugin('argument_validator', $validate_id);
    if ($plugin) {
      $options = &$form_state['values']['options']['validate']['options'][$validate_id];
      $plugin->submitOptionsForm($form['validate']['options'][$validate_id], $form_state, $options);
      // Copy the now submitted options to their final resting place so they get saved.
      $form_state['values']['options']['validate_options'] = $options;
    }

    // Clear out the content of title if it's not enabled.
    $options =& $form_state['values']['options'];
    if (empty($options['title_enable'])) {
      $options['title'] = '';
    }
  }

  /**
   * Provide a list of default behaviors for this argument if the argument
   * is not present.
   *
   * Override this method to provide additional (or fewer) default behaviors.
   */
  protected function defaultActions($which = NULL) {
    $defaults = array(
      'ignore' => array(
        'title' => t('Display all results for the specified field'),
        'method' => 'defaultIgnore',
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'default' => array(
        'title' => t('Provide default value'),
        'method' => 'defaultDefault',
        'form method' => 'defaultArgumentForm',
        'has default argument' => TRUE,
        'default only' => TRUE, // this can only be used for missing argument, not validation failure
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'not found' => array(
        'title' => t('Hide view'),
        'method' => 'defaultNotFound',
        'hard fail' => TRUE, // This is a hard fail condition
      ),
      'summary' => array(
        'title' => t('Display a summary'),
        'method' => 'defaultSummary',
        'form method' => 'defaultSummaryForm',
        'style plugin' => TRUE,
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'empty' => array(
        'title' => t('Display contents of "No results found"'),
        'method' => 'defaultEmpty',
        'breadcrumb' => TRUE, // generate a breadcrumb to here
      ),
      'access denied' => array(
        'title' => t('Display "Access Denied"'),
        'method' => 'defaultAccessDenied',
        'breadcrumb' => FALSE, // generate a breadcrumb to here
      ),
    );

    if ($this->view->display_handler->hasPath()) {
      $defaults['not found']['title'] = t('Show "Page not found"');
    }

    if ($which) {
      if (!empty($defaults[$which])) {
        return $defaults[$which];
      }
    }
    else {
      return $defaults;
    }
  }

  /**
   * Provide a form for selecting the default argument when the
   * default action is set to provide default argument.
   */
  public function defaultArgumentForm(&$form, &$form_state) {
    $plugins = Views::pluginManager('argument_default')->getDefinitions();
    $options = array();

    $form['default_argument_skip_url'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip default argument for view URL'),
      '#default_value' => $this->options['default_argument_skip_url'],
      '#description' => t('Select whether to include this default argument when constructing the URL for this view. Skipping default arguments is useful e.g. in the case of feeds.')
    );

    $form['default_argument_type'] = array(
      '#prefix' => '<div id="edit-options-default-argument-type-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#id' => 'edit-options-default-argument-type',
      '#title' => t('Type'),
      '#default_value' => $this->options['default_argument_type'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[default_action]"]' => array('value' => 'default'),
        ),
      ),
      // Views custom key, moves this element to the appropriate container
      // under the radio button.
      '#argument_option' => 'default',
    );

    foreach ($plugins as $id => $info) {
      if (!empty($info['no_ui'])) {
        continue;
      }
      $plugin = $this->getPlugin('argument_default', $id);
      if ($plugin) {
        if ($plugin->access() || $this->options['default_argument_type'] == $id) {
          $form['argument_default']['#argument_option'] = 'default';
          $form['argument_default'][$id] = array(
            '#prefix' => '<div id="edit-options-argument-default-options-' . $id . '-wrapper">',
            '#suffix' => '</div>',
            '#id' => 'edit-options-argument-default-options-' . $id,
            '#type' => 'item',
            // Even if the plugin has no options add the key to the form_state.
            '#input' => TRUE,
            '#states' => array(
              'visible' => array(
                ':input[name="options[default_action]"]' => array('value' => 'default'),
                ':input[name="options[default_argument_type]"]' => array('value' => $id),
              ),
            ),
            '#default_value' => array(),
          );
          $options[$id] = $info['title'];
          $plugin->buildOptionsForm($form['argument_default'][$id], $form_state);
        }
      }
    }

    asort($options);
    $form['default_argument_type']['#options'] = $options;
  }

  /**
   * Provide a form for selecting further summary options when the
   * default action is set to display one.
   */
  public function defaultSummaryForm(&$form, &$form_state) {
    $style_plugins = Views::pluginManager('style')->getDefinitions();
    $summary_plugins = array();
    $format_options = array();
    foreach ($style_plugins as $key => $plugin) {
      if (isset($plugin['display_types']) && in_array('summary', $plugin['display_types'])) {
        $summary_plugins[$key] = $plugin;
        $format_options[$key] = $plugin['title'];
      }
    }

    $form['summary'] = array(
      // Views custom key, moves this element to the appropriate container
      // under the radio button.
      '#argument_option' => 'summary',
    );
    $form['summary']['sort_order'] = array(
      '#type' => 'radios',
      '#title' => t('Sort order'),
      '#options' => array('asc' => t('Ascending'), 'desc' => t('Descending')),
      '#default_value' => $this->options['summary']['sort_order'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[default_action]"]' => array('value' => 'summary'),
        ),
      ),
    );
    $form['summary']['number_of_records'] = array(
      '#type' => 'radios',
      '#title' => t('Sort by'),
      '#default_value' => $this->options['summary']['number_of_records'],
      '#options' => array(
        0 => $this->getSortName(),
        1 => t('Number of records')
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="options[default_action]"]' => array('value' => 'summary'),
        ),
      ),
    );

    $form['summary']['format'] = array(
      '#type' => 'radios',
      '#title' => t('Format'),
      '#options' => $format_options,
      '#default_value' => $this->options['summary']['format'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[default_action]"]' => array('value' => 'summary'),
        ),
      ),
    );

    foreach ($summary_plugins as $id => $info) {
      $plugin = $this->getPlugin('style', $id);
      if (!$plugin->usesOptions()) {
        continue;
      }
      if ($plugin) {
        $form['summary']['options'][$id] = array(
          '#prefix' => '<div id="edit-options-summary-options-' . $id . '-wrapper">',
          '#suffix' => '</div>',
          '#id' => 'edit-options-summary-options-' . $id,
          '#type' => 'item',
          '#input' => TRUE, // trick it into checking input to make #process run
          '#states' => array(
            'visible' => array(
              ':input[name="options[default_action]"]' => array('value' => 'summary'),
              ':input[name="options[summary][format]"]' => array('value' => $id),
            ),
          ),
          '#default_value' => array(),
        );
        $options[$id] = $info['title'];
        $plugin->buildOptionsForm($form['summary']['options'][$id], $form_state);
      }
    }
  }

  /**
   * Handle the default action, which means our argument wasn't present.
   *
   * Override this method only with extreme care.
   *
   * @return
   *   A boolean value; if TRUE, continue building this view. If FALSE,
   *   building the view will be aborted here.
   */
  function default_action($info = NULL) {
    if (!isset($info)) {
      $info = $this->defaultActions($this->options['default_action']);
    }

    if (!$info) {
      return FALSE;
    }

    if (!empty($info['method args'])) {
      return call_user_func_array(array(&$this, $info['method']), $info['method args']);
    }
    else {
      return $this->{$info['method']}();
    }
  }

  /**
   * How to act if validation failes
   */
  public function validateFail() {
    $info = $this->defaultActions($this->options['validate']['fail']);
    return $this->default_action($info);
  }
  /**
   * Default action: ignore.
   *
   * If an argument was expected and was not given, in this case, simply
   * ignore the argument entirely.
   */
  public function defaultIgnore() {
    return TRUE;
  }

  /**
   * Default action: not found.
   *
   * If an argument was expected and was not given, in this case, report
   * the view as 'not found' or hide it.
   */
  protected function defaultNotFound() {
    // Set a failure condition and let the display manager handle it.
    $this->view->build_info['fail'] = TRUE;
    return FALSE;
  }

  /**
   * Default action: access denied.
   *
   * If an argument was expected and was not given, in this case, report
   * the view as 'access denied'.
   */
  public function defaultAccessDenied() {
    $this->view->build_info['denied'] = TRUE;
    return FALSE;
  }

  /**
   * Default action: empty
   *
   * If an argument was expected and was not given, in this case, display
   * the view's empty text
   */
  public function defaultEmpty() {
    // We return with no query; this will force the empty text.
    $this->view->built = TRUE;
    $this->view->executed = TRUE;
    $this->view->result = array();
    return FALSE;
  }

  /**
   * This just returns true. The view argument builder will know where
   * to find the argument from.
   */
  protected function defaultDefault() {
    return TRUE;
  }

  /**
   * Determine if the argument is set to provide a default argument.
   */
  function hasDefaultArgument() {
    $info = $this->defaultActions($this->options['default_action']);
    return !empty($info['has default argument']);
  }

  /**
   * Get a default argument, if available.
   */
  public function getDefaultArgument() {
    $plugin = $this->getPlugin('argument_default');
    if ($plugin) {
      return $plugin->getArgument();
    }
  }

  /**
   * Process the summary arguments for display.
   *
   * For example, the validation plugin may want to alter an argument for use in
   * the URL.
   */
  public function processSummaryArguments(&$args) {
    if ($this->options['validate']['type'] != 'none') {
      if (isset($this->validator) || $this->validator = $this->getPlugin('argument_validator')) {
        $this->validator->processSummaryArguments($args);
      }
    }
  }

  /**
   * Default action: summary.
   *
   * If an argument was expected and was not given, in this case, display
   * a summary query.
   */
  protected function defaultSummary() {
    $this->view->build_info['summary'] = TRUE;
    $this->view->build_info['summary_level'] = $this->options['id'];

    // Change the display style to the summary style for this
    // argument.
    $this->view->style_plugin = Views::pluginManager("style")->createInstance($this->options['summary']['format']);
    $this->view->style_plugin->init($this->view, $this->displayHandler, $this->options['summary_options']);

    // Clear out the normal primary field and whatever else may have
    // been added and let the summary do the work.
    $this->query->clearFields();
    $this->summaryQuery();

    $by = $this->options['summary']['number_of_records'] ? 'num_records' : NULL;
    $this->summarySort($this->options['summary']['sort_order'], $by);

    // Summaries have their own sorting and fields, so tell the View not
    // to build these.
    $this->view->build_sort = FALSE;
    return TRUE;
  }

  /**
   * Build the info for the summary query.
   *
   * This must:
   * - add_groupby: group on this field in order to create summaries.
   * - add_field: add a 'num_nodes' field for the count. Usually it will
   *   be a count on $view->base_field
   * - set_count_field: Reset the count field so we get the right paging.
   *
   * @return
   *   The alias used to get the number of records (count) for this entry.
   */
  protected function summaryQuery() {
    $this->ensureMyTable();
    // Add the field.
    $this->base_alias = $this->query->add_field($this->tableAlias, $this->realField);

    $this->summaryNameField();
    return $this->summaryBasics();
  }

  /**
   * Add the name field, which is the field displayed in summary queries.
   * This is often used when the argument is numeric.
   */
  protected function summaryNameField() {
    // Add the 'name' field. For example, if this is a uid argument, the
    // name field would be 'name' (i.e, the username).

    if (isset($this->name_table)) {
      // if the alias is different then we're probably added, not ensured,
      // so look up the join and add it instead.
      if ($this->tableAlias != $this->name_table) {
        $j = HandlerBase::getTableJoin($this->name_table, $this->table);
        if ($j) {
          $join = clone $j;
          $join->leftTable = $this->tableAlias;
          $this->name_table_alias = $this->query->addTable($this->name_table, $this->relationship, $join);
        }
      }
      else {
        $this->name_table_alias = $this->query->ensure_table($this->name_table, $this->relationship);
      }
    }
    else {
      $this->name_table_alias = $this->tableAlias;
    }

    if (isset($this->name_field)) {
      $this->name_alias = $this->query->add_field($this->name_table_alias, $this->name_field);
    }
    else {
      $this->name_alias = $this->base_alias;
    }
  }

  /**
   * Some basic summary behavior that doesn't need to be repeated as much as
   * code that goes into summaryQuery()
   */
  public function summaryBasics($count_field = TRUE) {
    // Add the number of nodes counter
    $distinct = ($this->view->display_handler->getOption('distinct') && empty($this->query->no_distinct));

    $count_alias = $this->query->add_field($this->view->storage->get('base_table'), $this->view->storage->get('base_field'), 'num_records', array('count' => TRUE, 'distinct' => $distinct));
    $this->query->add_groupby($this->name_alias);

    if ($count_field) {
      $this->query->set_count_field($this->tableAlias, $this->realField);
    }

    $this->count_alias = $count_alias;
  }

  /**
   * Sorts the summary based upon the user's selection. The base variant of
   * this is usually adequte.
   *
   * @param $order
   *   The order selected in the UI.
   */
  public function summarySort($order, $by = NULL) {
    $this->query->add_orderby(NULL, NULL, $order, (!empty($by) ? $by : $this->name_alias));
  }

  /**
   * Provide the argument to use to link from the summary to the next level;
   * this will be called once per row of a summary, and used as part of
   * $view->getUrl().
   *
   * @param $data
   *   The query results for the row.
   */
 public function summaryArgument($data) {
    return $data->{$this->base_alias};
  }

  /**
   * Provides the name to use for the summary. By default this is just
   * the name field.
   *
   * @param $data
   *   The query results for the row.
   */
  public function summaryName($data) {
    $value = $data->{$this->name_alias};
    if (empty($value) && !empty($this->definition['empty field name'])) {
      $value = $this->definition['empty field name'];
    }
    return check_plain($value);
  }

  /**
   * Set up the query for this argument.
   *
   * The argument sent may be found at $this->argument.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->query->addWhere(0, "$this->tableAlias.$this->realField", $this->argument);
  }

  /**
   * Get the title this argument will assign the view, given the argument.
   *
   * This usually needs to be overridden to provide a proper title.
   */
  function title() {
    return check_plain($this->argument);
  }

  /**
   * Called by the view object to get the title. This may be set by a
   * validator so we don't necessarily call through to title().
   */
  public function getTitle() {
    if (isset($this->validated_title)) {
      return $this->validated_title;
    }
    else {
      return $this->title();
    }
  }

  /**
   * Validate that this argument works. By default, all arguments are valid.
   */
  public function validateArgument($arg) {
    // By using % in URLs, arguments could be validated twice; this eases
    // that pain.
    if (isset($this->argument_validated)) {
      return $this->argument_validated;
    }

    if ($this->isException($arg)) {
      return $this->argument_validated = TRUE;
    }

    $plugin = $this->getPlugin('argument_validator');
    return $this->argument_validated = $plugin->validate_argument($arg);
  }

  /**
   * Called by the menu system to validate an argument.
   *
   * This checks to see if this is a 'soft fail', which means that if the
   * argument fails to validate, but there is an action to take anyway,
   * then validation cannot actually fail.
   */
  function validate_argument($arg) {
    $validate_info = $this->defaultActions($this->options['validate']['fail']);
    if (empty($validate_info['hard fail'])) {
      return TRUE;
    }

    $rc = $this->validateArgument($arg);

    // If the validator has changed the validate fail condition to a
    // soft fail, deal with that:
    $validate_info = $this->defaultActions($this->options['validate']['fail']);
    if (empty($validate_info['hard fail'])) {
      return TRUE;
    }

    return $rc;
  }

  /**
   * Set the input for this argument
   *
   * @return TRUE if it successfully validates; FALSE if it does not.
   */
  public function setArgument($arg) {
    $this->argument = $arg;
    return $this->validateArgument($arg);
  }

  /**
   * Get the value of this argument.
   */
  public function getValue() {
    // If we already processed this argument, we're done.
    if (isset($this->argument)) {
      return $this->argument;
    }

    // Otherwise, we have to pretend to process ourself to find the value.
    $value = NULL;
    // Find the position of this argument within the view.
    $position = 0;
    foreach ($this->view->argument as $id => $argument) {
      if ($id == $this->options['id']) {
        break;
      }
      $position++;
    }

    $arg = isset($this->view->args[$position]) ? $this->view->args[$position] : NULL;
    $this->position = $position;

    // Clone ourselves so that we don't break things when we're really
    // processing the arguments.
    $argument = clone $this;
    if (!isset($arg) && $argument->hasDefaultArgument()) {
      $arg = $argument->getDefaultArgument();

      // remember that this argument was computed, not passed on the URL.
      $this->is_default = TRUE;
    }
    // Set the argument, which will also validate that the argument can be set.
    if ($argument->setArgument($arg)) {
      $value = $argument->argument;
    }
    unset($argument);
    return $value;
  }

  /**
   * Get the display or row plugin, if it exists.
   */
  public function getPlugin($type = 'argument_default', $name = NULL) {
    $options = array();
    switch ($type) {
      case 'argument_default':
        $plugin_name = $this->options['default_argument_type'];
        $options_name = 'default_argument_options';
        break;
      case 'argument_validator':
        $plugin_name = $this->options['validate']['type'];
        $options_name = 'validate_options';
        break;
      case 'style':
        $plugin_name = $this->options['summary']['format'];
        $options_name = 'summary_options';
    }

    if (!$name) {
      $name = $plugin_name;
    }

    // we only fetch the options if we're fetching the plugin actually
    // in use.
    if ($name == $plugin_name) {
      $options = $this->options[$options_name];
    }

    $plugin = Views::pluginManager($type)->createInstance($name);
    if ($plugin) {
      $plugin->init($this->view, $this->displayHandler, $options);

      if ($type !== 'style') {
        // It's an argument_default/argument_validate plugin, so set the argument.
        $plugin->setArgument($this);
      }
      return $plugin;
    }
  }

  /**
   * Return a description of how the argument would normally be sorted.
   *
   * Subclasses should override this to specify what the default sort order of
   * their argument is (e.g. alphabetical, numeric, date).
   */
  public function getSortName() {
    return t('Default sort', array(), array('context' => 'Sort order'));
  }

  /**
   * Custom form radios process function.
   *
   * Roll out a single radios element to a list of radios, using the options
   * array as index. While doing that, create a container element underneath
   * each option, which contains the settings related to that option.
   *
   * @see form_process_radios()
   */
  public static function processContainerRadios($element) {
    if (count($element['#options']) > 0) {
      foreach ($element['#options'] as $key => $choice) {
        $element += array($key => array());
        // Generate the parents as the autogenerator does, so we will have a
        // unique id for each radio button.
        $parents_for_id = array_merge($element['#parents'], array($key));

        $element[$key] += array(
          '#type' => 'radio',
          '#title' => $choice,
          // The key is sanitized in drupal_attributes() during output from the
          // theme function.
          '#return_value' => $key,
          '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : NULL,
          '#attributes' => $element['#attributes'],
          '#parents' => $element['#parents'],
          '#id' => drupal_html_id('edit-' . implode('-', $parents_for_id)),
          '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
        );
        $element[$key . '_options'] = array(
          '#type' => 'container',
          '#attributes' => array('class' => array('views-admin-dependent')),
        );
      }
    }
    return $element;
  }

}

/**
 * @}
 */
