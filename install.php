<?php
// $Id$

require_once './includes/install.inc';

/**
 * The Drupal installation happens in a series of steps. We begin by verifying
 * that the current environment meets our minimum requirements. We then go
 * on to verify that settings.php is properly configured. From there we
 * connect to the configured database and verify that it meets our minimum
 * requirements. Finally we can allow the user to select an installation
 * profile and complete the installation process.
 *
 * @param $phase
 *   The installation phase we should proceed to.
 */
function install_main() {
  global $profile, $install_locale, $conf;
  require_once './includes/cache-install.inc';
  require_once './includes/bootstrap.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

  // Because no persistent storage is available yet, functions
  // that check for cached data will fail. During the installation
  // process, we temporarily replace the normal cache system with
  // a stubbed-out version that short-circuits the actual caching
  // process and avoids any errors.
  $conf['cache_inc'] = './includes/cache-install.inc';
  require_once './modules/system/system.install';
  require_once './includes/file.inc';

  // Ensure correct page headers are sent (e.g. caching)
  drupal_page_header();

  // Check existing settings.php.
  $verify = install_verify_settings();

  if ($verify) {
    // Establish a connection to the database.
    require_once './includes/database.inc';
    db_set_active();
    // Check if Drupal is installed.
    $task = install_verify_drupal();
    if ($task == 'done') {
      install_already_done_error();
    }
  }
  else {
    $task = NULL;
  }

  // Load module basics (needed for hook invokes).
  include_once './includes/module.inc';
  $module_list['system']['filename'] = 'modules/system/system.module';
  $module_list['filter']['filename'] = 'modules/filter/filter.module';
  module_list(TRUE, FALSE, FALSE, $module_list);
  drupal_load('module', 'system');
  drupal_load('module', 'filter');

  // Decide which profile to use.
  if (!empty($_GET['profile'])) {
    $profile = preg_replace('/[^a-zA-Z_0-9]/', '', $_GET['profile']);
  }
  elseif ($profile = install_select_profile()) {
    install_goto("install.php?profile=$profile");
  }
  else {
    install_no_profile_error();
  }

  // Load the profile.
  require_once "./profiles/$profile/$profile.profile";

  // Locale selection
  if (!empty($_GET['locale'])) {
    $install_locale = preg_replace('/[^a-zA-Z_0-9]/', '', $_GET['locale']);
  }
  elseif (($install_locale = install_select_locale($profile)) !== FALSE) {
    install_goto("install.php?profile=$profile&locale=$install_locale");
  }

  // Tasks come after the database is set up
  if (!$task) {
    // Check the installation requirements for Drupal and this profile.
    install_check_requirements($profile);

    // Verify existence of all required modules.
    $modules = drupal_verify_profile($profile, $install_locale);
    if (!$modules) {
      install_missing_modules_error($profile);
    }

    // Change the settings.php information if verification failed earlier.
    // Note: will trigger a redirect if database credentials change.
    if (!$verify) {
      install_change_settings($profile, $install_locale);
    }

    // Perform actual installation defined in the profile.
    drupal_install_profile($profile, $modules);

    // Warn about settings.php permissions risk
    $settings_file = './'. conf_path() .'/settings.php';
    if (!drupal_verify_install_file($settings_file, FILE_EXIST|FILE_READABLE|FILE_NOT_WRITABLE)) {
      drupal_set_message(st('All necessary changes to %file have been made, so you should now remove write permissions to this file. Failure to remove write permissions to this file is a security risk.', array('%file' => $settings_file)), 'error');
    }
    else {
      drupal_set_message(st('All necessary changes to %file have been made. It has been set to read-only for security.', array('%file' => $settings_file)));
    }
  }

  // The database is set up, turn to further tasks.
  install_tasks($profile, $task);
}

/**
 * Verify if Drupal is installed.
 */
function install_verify_drupal() {
  // Read the variable manually using the @ so we don't trigger an error if it fails.
  $result = @db_query("SELECT value FROM {variable} WHERE name = 'install_task'");
  if ($result) {
    return unserialize(db_result($result));
  }
}

/**
 * Verify existing settings.php
 */
function install_verify_settings() {
  global $db_prefix, $db_type, $db_url;

  // Verify existing settings (if any).
  if (!empty($db_url)) {
    // We need this because we want to run form_get_errors.
    include_once './includes/form.inc';

    $url = parse_url(is_array($db_url) ? $db_url['default'] : $db_url);
    $db_user = urldecode($url['user']);
    $db_pass = urldecode($url['pass']);
    $db_host = urldecode($url['host']);
    $db_port = isset($url['port']) ? urldecode($url['port']) : '';
    $db_path = ltrim(urldecode($url['path']), '/');
    $settings_file = './'. conf_path() .'/settings.php';

    $form_state = array();
    _install_settings_form_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_port, $db_path, $settings_file, $form_state);
    if (!form_get_errors()) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Configure and rewrite settings.php.
 */
function install_change_settings($profile = 'default', $install_locale = '') {
  global $db_url, $db_type, $db_prefix;

  $url = parse_url(is_array($db_url) ? $db_url['default'] : $db_url);
  $db_user = urldecode($url['user']);
  $db_pass = urldecode($url['pass']);
  $db_host = urldecode($url['host']);
  $db_port = isset($url['port']) ? urldecode($url['port']) : '';
  $db_path = ltrim(urldecode($url['path']), '/');
  $conf_path = './'. conf_path();
  $settings_file = $conf_path .'/settings.php';

  // We always need this because we want to run form_get_errors.
  include_once './includes/form.inc';
  drupal_maintenance_theme();
  install_task_list('database');

  // The existing database settings are not working, so we need write access
  // to settings.php to change them.
  $writable = FALSE;
  $file = $conf_path;
  // Verify the directory exists.
  if (drupal_verify_install_file($conf_path, FILE_EXIST, 'dir')) {
    // Check to see if a settings.php already exists
    if (drupal_verify_install_file($settings_file, FILE_EXIST)) {
      // If it does, make sure it is writable
      $writable = drupal_verify_install_file($settings_file, FILE_READABLE|FILE_WRITABLE);
      $file = $settings_file;
    }
    else {
      // If not, makes sure the directory is.
      $writable = drupal_verify_install_file($conf_path, FILE_READABLE|FILE_WRITABLE, 'dir');
    }
  }

  if (!$writable) {
    drupal_set_message(st('The @drupal installer requires write permissions to %file during the installation process.', array('@drupal' => drupal_install_profile_name(), '%file' => $file)), 'error');

    drupal_set_title(st('Drupal database setup'));
    print theme('install_page', '');
    exit;
  }

  $output = drupal_get_form('install_settings_form', $profile, $install_locale, $settings_file, $db_url, $db_type, $db_prefix, $db_user, $db_pass, $db_host, $db_port, $db_path);
  drupal_set_title(st('Database configuration'));
  print theme('install_page', $output);
  exit;
}


/**
 * Form API array definition for install_settings.
 */
function install_settings_form($profile, $install_locale, $settings_file, $db_url, $db_type, $db_prefix, $db_user, $db_pass, $db_host, $db_port, $db_path) {
  if (empty($db_host)) {
    $db_host = 'localhost';
  }
  $db_types = drupal_detect_database_types();
  if (count($db_types) == 0) {
    $form['no_db_types'] = array(
      '#value' => st('Your web server does not appear to support any common database types. Check with your hosting provider to see if they offer any databases that <a href="@drupal-databases">Drupal supports</a>.', array('@drupal-databases' => 'http://drupal.org/node/270#database')),
    );
  }
  else {
    $form['basic_options'] = array(
      '#type' => 'fieldset',
      '#title' => st('Basic options'),
      '#description' => '<p>'. st('To set up your @drupal database, enter the following information.', array('@drupal' => drupal_install_profile_name())) .'</p>',
    );

    if (count($db_types) > 1) {
      // Database type
      $db_types = drupal_detect_database_types();
      $form['basic_options']['db_type'] = array(
        '#type' => 'radios',
        '#title' => st('Database type'),
        '#required' => TRUE,
        '#options' => $db_types,
        '#default_value' => ($db_type ? $db_type : current($db_types)),
        '#description' => st('The type of database your @drupal data will be stored in.', array('@drupal' => drupal_install_profile_name())),
      );
      $db_path_description = st('The name of the database your @drupal data will be stored in. It must exist on your server before @drupal can be installed.', array('@drupal' => drupal_install_profile_name()));
    }
    else {
      if (count($db_types) == 1) {
        $db_types = array_values($db_types);
        $form['basic_options']['db_type'] = array(
          '#type' => 'hidden',
          '#value' => $db_types[0],
        );
        $db_path_description = st('The name of the %db_type database your @drupal data will be stored in. It must exist on your server before @drupal can be installed.', array('%db_type' => $db_types[0], '@drupal' => drupal_install_profile_name()));
      }
    }

    // Database name
    $form['basic_options']['db_path'] = array(
      '#type' => 'textfield',
      '#title' => st('Database name'),
      '#default_value' => $db_path,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
      '#description' => $db_path_description
    );

    // Database username
    $form['basic_options']['db_user'] = array(
      '#type' => 'textfield',
      '#title' => st('Database username'),
      '#default_value' => $db_user,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
    );

    // Database username
    $form['basic_options']['db_pass'] = array(
      '#type' => 'password',
      '#title' => st('Database password'),
      '#default_value' => $db_pass,
      '#size' => 45,
      '#maxlength' => 45,
    );

    $form['advanced_options'] = array(
      '#type' => 'fieldset',
      '#title' => st('Advanced options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => st("These options are only necessary for some sites. If you're not sure what you should enter here, leave the default settings or check with your hosting provider.")
    );

    // Database host
    $form['advanced_options']['db_host'] = array(
      '#type' => 'textfield',
      '#title' => st('Database host'),
      '#default_value' => $db_host,
      '#size' => 45,
      '#maxlength' => 45,
      '#required' => TRUE,
      '#description' => st('If your database is located on a different server, change this.'),
    );

    // Database port
    $form['advanced_options']['db_port'] = array(
      '#type' => 'textfield',
      '#title' => st('Database port'),
      '#default_value' => $db_port,
      '#size' => 45,
      '#maxlength' => 45,
      '#description' => st('If your database server is listening to a non-standard port, enter its number.'),
    );

    // Table prefix
    $form['advanced_options']['db_prefix'] = array(
      '#type' => 'textfield',
      '#title' => st('Table prefix'),
      '#default_value' => $db_prefix,
      '#size' => 45,
      '#maxlength' => 45,
      '#description' => st('If more than one @drupal website will be sharing this database, enter a table prefix for your @drupal site here.', array('@drupal' => drupal_install_profile_name())),
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => st('Save configuration'),
    );

    $form['errors'] = array();
    $form['settings_file'] = array('#type' => 'value', '#value' => $settings_file);
    $form['_db_url'] = array('#type' => 'value');
    $form['#action'] = "install.php?profile=$profile" . ($install_locale ? "&locale=$install_locale" : '');
    $form['#redirect'] = FALSE;
  }
  return $form;
}

/**
 * Form API validate for install_settings form.
 */
function install_settings_form_validate($form_values, $form, &$form_state) {
  global $db_url;
  _install_settings_form_validate($form_values['db_prefix'], $form_values['db_type'], $form_values['db_user'], $form_values['db_pass'], $form_values['db_host'], $form_values['db_port'], $form_values['db_path'], $form_values['settings_file'], $form_state, $form);
}

/**
 * Helper function for install_settings_validate.
 */
function _install_settings_form_validate($db_prefix, $db_type, $db_user, $db_pass, $db_host, $db_port, $db_path, $settings_file, &$form_state, $form = NULL) {
  global $db_url;

  // Verify the table prefix
  if (!empty($db_prefix) && is_string($db_prefix) && !preg_match('/^[A-Za-z0-9_.]+$/', $db_prefix)) {
    form_set_error('db_prefix', st('The database table prefix you have entered, %db_prefix, is invalid. The table prefix can only contain alphanumeric characters, underscores or dots.', array('%db_prefix' => $db_prefix)), 'error');
  }

  if (!empty($db_port) && !is_numeric($db_port)) {
    form_set_error('db_port', st('Database port must be a number.'));
  }

  // Check database type
  if (!isset($form)) {
    $_db_url = is_array($db_url) ? $db_url['default'] : $db_url;
    $db_type = substr($_db_url, 0, strpos($_db_url, '://'));
  }
  $databases = drupal_detect_database_types();
  if (!in_array($db_type, $databases)) {
    form_set_error('db_type', st("In your %settings_file file you have configured @drupal to use a %db_type server, however your PHP installation currently does not support this database type.", array('%settings_file' => $settings_file, '@drupal' => drupal_install_profile_name(), '%db_type' => $db_type)));
  }
  else {
    // Verify
    $db_url = $db_type .'://'. urlencode($db_user) . ($db_pass ? ':'. urlencode($db_pass) : '') .'@'. ($db_host ? urlencode($db_host) : 'localhost') . ($db_port ? ":$db_port" : '') .'/'. urlencode($db_path);
    if (isset($form)) {
      form_set_value($form['_db_url'], $db_url, $form_state);
    }
    $success = array();

    $function = 'drupal_test_'. $db_type;
    if (!$function($db_url, $success)) {
      if (isset($success['CONNECT'])) {
        form_set_error('db_type', st('In order for Drupal to work and to proceed with the installation process you must resolve all permission issues reported above. We were able to verify that we have permission for the following commands: %commands. For more help with configuring your database server, see the <a href="http://drupal.org/node/258">Installation and upgrading handbook</a>. If you are unsure what any of this means you should probably contact your hosting provider.', array('%commands' => implode($success, ', '))));
      }
      else {
        form_set_error('db_type', '');
      }
    }
  }
}

/**
 * Form API submit for install_settings form.
 */
function install_settings_form_submit($form_values) {
  global $profile, $install_locale;

  // Update global settings array and save
  $settings['db_url'] = array(
    'value'    => $form_values['_db_url'],
    'required' => TRUE,
  );
  $settings['db_prefix'] = array(
    'value'    => $form_values['db_prefix'],
    'required' => TRUE,
  );
  drupal_rewrite_settings($settings);

  // Continue to install profile step
  install_goto("install.php?profile=$profile" . ($install_locale ? "&locale=$install_locale" : ''));
}

/**
 * Find all .profile files.
 */
function install_find_profiles() {
  return file_scan_directory('./profiles', '\.profile$', array('.', '..', 'CVS'), 0, TRUE, 'name', 0);
}

/**
 * Allow admin to select which profile to install.
 *
 * @return
 *   The selected profile.
 */
function install_select_profile() {
  include_once './includes/form.inc';

  $profiles = install_find_profiles();
  // Don't need to choose profile if only one available.
  if (sizeof($profiles) == 1) {
    $profile = array_pop($profiles);
    require_once $profile->filename;
    return $profile->name;
  }
  elseif (sizeof($profiles) > 1) {
    foreach ($profiles as $profile) {
      if ($_POST['profile'] == $profile->name) {
        return $profile->name;
      }
    }

    drupal_maintenance_theme();
    install_task_list('profile-select');

    drupal_set_title(st('Select an installation profile'));
    print theme('install_page', drupal_get_form('install_select_profile_form', $profiles));
    exit;
  }
}

function install_select_profile_form($profiles) {
  foreach ($profiles as $profile) {
    include_once($profile->filename);
    // Load profile details.
    $function = $profile->name .'_profile_details';
    if (function_exists($function)) {
      $details = $function();
    }
    // If set, used defined name. Otherwise use file name.
    $name = isset($details['name']) ? $details['name'] : $profile->name;
    $form['profile'][$name] = array(
      '#type' => 'radio',
      '#value' => 'default',
      '#return_value' => $profile->name,
      '#title' => $name,
      '#description' => isset($details['description']) ? $details['description'] : '',
      '#parents' => array('profile'),
    );
  }
  $form['submit'] =  array(
    '#type' => 'submit',
    '#value' => st('Save configuration'),
  );
  return $form;
}

/**
 * Find all .po files for the current profile.
 */
function install_find_locales($profilename) {
  $locales = file_scan_directory('./profiles/'. $profilename .'/po', '\.po$', array('.', '..', 'CVS'), 0, FALSE);
  array_unshift($locales, (object) array('name' => 'en'));
  return $locales;
}

/**
 * Allow admin to select which locale to use for the current profile.
 *
 * @return
 *   The selected language.
 */
function install_select_locale($profilename) {
  include_once './includes/file.inc';
  include_once './includes/form.inc';

  // Find all available locales.
  $locales = install_find_locales($profilename);

  // If only the built-in (English) language is available,
  // and we are using the default profile, inform the user
  // that the installer can be localized. Otherwise we assume
  // the user know what he is doing.
  if (count($locales) == 1) {
    if ($profilename == 'default') {
      drupal_maintenance_theme();
      install_task_list('profile-select');
      drupal_set_title(st('Localization of the Drupal installer'));
      $output = '<p>'. st('With the addition of an appropriate language pack, this installer is capable of proceeding in another language of your choice. To install and use Drupal in a language other than English:') . '</p>';
      $output .= '<ul><li>'. st('Determine if <a href="@translations" target="_blank">a translation of this Drupal version</a> is available in your language of choice. A translation is provided via a translation pack; each translation pack enables the display of a specific version of Drupal in a specific language. Not all languages are available for every version of Drupal.', array('@translations' => 'http://drupal.org/project/Translations')) .'</li>';
      $output .= '<li>'. st('If an alternative language pack of your choice is available, download and extract its contents to your Drupal root directory.') .'</li>';
      $output .= '<li>'. st('Reload this page using the second link below and select your desired language from the displayed list. Reloading the page allows the list to automatically adjust to the presence of new language packs.') .'</li>';
      $output .= '</ul><p>' . st('Alternatively, to install and use Drupal in English, or to defer the selection of an alternative language until after installation, select the first link below.') .'</p>';
      $output .= '<p>' . st('How should the installation continue?') .'</p>';
      $output .= '<ul><li><a href="install.php?profile='. $profilename . '&amp;locale=en">'. st('Continue installation in English') .'</a></li><li><a href="install.php?profile='. $profilename . '">'. st('Reload this page to select a language') .'</a></li></ul>';
      print theme('install_page', $output);
      exit;
    }
    // One language, but not the default profile, assume
    // the user knows what he is doing.
    return FALSE;
  }
  else {
    foreach ($locales as $locale) {
      if ($_POST['locale'] == $locale->name) {
        return $locale->name;
      }
    }

    drupal_maintenance_theme();
    install_task_list('locale-select');

    drupal_set_title(st('Choose your preferred language'));
    print theme('install_page', drupal_get_form('install_select_locale_form', $locales));
    exit;
  }
}

function install_select_locale_form($locales) {
  include_once './includes/locale.inc';
  $languages = _locale_get_predefined_list();
  foreach ($locales as $locale) {
    // Try to use verbose locale name
    $name = $locale->name;
    if (isset($languages[$name])) {
      $name = $languages[$name][0] . (isset($languages[$name][1]) ? ' '. st('(@language)', array('@language' => $languages[$name][1])) : '');
    }
    $form['locale'][$locale->name] = array(
      '#type' => 'radio',
      '#return_value' => $locale->name,
      '#default_value' => ($locale->name == 'en' ? TRUE : FALSE),
      '#title' => $name . ($locale->name == 'en' ? ' '. st('(built-in)') : ''),
      '#parents' => array('locale')
    );
  }
  $form['submit'] =  array(
    '#type' => 'submit',
    '#value' => st('Select language'),
  );
  return $form;
}

/**
 * Show an error page when there are no profiles available.
 */
function install_no_profile_error() {
  drupal_maintenance_theme();
  install_task_list('profile-select');
  drupal_set_title(st('No profiles available'));
  print theme('install_page', '<p>'. st('We were unable to find any installer profiles. Installer profiles tell us what modules to enable and what schema to install in the database. A profile is necessary to continue with the installation process.') .'</p>');
  exit;
}


/**
 * Show an error page when Drupal has already been installed.
 */
function install_already_done_error() {
  global $base_url;

  drupal_maintenance_theme();
  drupal_set_title(st('Drupal already installed'));
  print theme('install_page', st('<ul><li>To start over, you must empty your existing database.</li><li>To install to a different database, edit the appropriate <em>settings.php</em> file in the <em>sites</em> folder.</li><li>To upgrade an existing installation, proceed to the <a href="@base-url/update.php">update script</a>.</li></ul>', array('@base-url' => $base_url)));
  exit;
}

/**
 * Show an error page when Drupal is missing required modules.
 */
function install_missing_modules_error($profile) {
  global $base_url;

  drupal_maintenance_theme();
  install_task_list('requirements');
  drupal_set_title(st('Modules missing'));
  print theme('install_page', '<p>'. st('One or more required modules are missing. Please check the error messages and <a href="!url">try again</a>.', array('!url' => "install.php?profile=$profile")) .'</p>');
  exit;
}

/**
 * Tasks performed after the database is initialized. Called from install.php.
 */
function install_tasks($profile, $task) {
  global $base_url, $install_locale;
  $output = '';

  // Bootstrap newly installed Drupal, while preserving existing messages.
  $messages = $_SESSION['messages'];
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  $_SESSION['messages'] = $messages;

  // Build a page for a final task.
  drupal_maintenance_theme();
  if (empty($task)) {
    variable_set('install_task', 'configure');
    $task = 'configure';
  }

  // We are using a list of if constructs here to allow for
  // passing from one task to the other in the same request.

  if ($task == 'configure') {
    drupal_set_title(st('Configure site'));

    // We break the form up so we can tell when it's been successfully
    // submitted.

    $form_state = array('storage' => NULL, 'submitted' => FALSE);

    $form = drupal_retrieve_form('install_configure_form');
    $form_build_id = md5(mt_rand());
    $form['#build_id'] = $form_build_id;
    drupal_prepare_form('install_configure_form', $form, $form_state);

    // In order to find out if the form was successfully submitted or not,
    // we do a little song and dance to set the form to 'programmed' and check
    // to make sure this is really the form being submitted. It'd better be.
    if (!empty($_POST) && $_POST['form_id'] == 'install_configure_form') {
      $form['#programmed'] = TRUE;
      $form['#post'] = $_POST;
    }
    else {
      $form['#post'] = array();
    }

    drupal_process_form('install_configure_form', $form, $form_state);
    if (empty($form_state['redirect'])) {
      // Build menu to allow clean URL check.
      menu_rebuild();
      $output = drupal_render_form('install_configure_form', $form);
    }
    else {
      $task = 'profile';
    }
  }

  // If found an unknown task or the 'profile-custom' task, which is
  // reserved for profiles, hand over the control to the profile,
  // so it can run any number of custom tasks it defines.
  if (!in_array($task, install_reserved_tasks())) {
    $function = $profile .'_profile_tasks';
    if (function_exists($function)) {
      // The profile needs to run more code, maybe even more tasks.
      // $task is sent through as a reference and may be changed!
      $output = $function($task);
    }

    // If the profile doesn't move on to a new task we assume
    // that it is done: we let the installer regain control and
    // proceed with the locale import.
    if ($task == 'profile') {
      $task = 'locale-import';
    }
  }

  // Import interface translations for the enabled modules, after
  // any changes made by the profile through the profile forms.
  if ($task == 'locale-import') {
    if (!empty($install_locale) && ($install_locale != 'en')) {
      include_once 'includes/locale.inc';
      // Enable installation language as default site language.
      locale_add_language($install_locale, NULL, NULL, NULL, NULL, NULL, 1, TRUE);
      // Collect files to import for this language.
      $batch = locale_batch_by_language($install_locale);
      if (!empty($batch)) {
        // Start a batch, switch to 'locale-batch' task. We need to
        // set the variable here, because batch_process() redirects.
        variable_set('install_task', 'locale-batch');
        batch_set($batch);
        $path = $base_url .'/install.php?locale='. $install_locale .'&profile='. $profile;
        batch_process($path, $path);
      }
    }
    // Found nothing to import or not foreign language, go to next task.
   $task = 'finished';
  }

  // We are running a batch import of interface translation files.
  // This might run in multiple HTTP requests, constantly redirecting
  // to the same address, until the batch finished callback is invoked
  // and the task advances to 'finished'.
  if ($task == 'locale-batch') {
    include_once 'includes/batch.inc';
    include_once 'includes/locale.inc';
    $output .= _batch_page();
  }

  // Display a 'finished' page to user.
  if ($task == 'finished') {
    drupal_set_title(st('@drupal installation complete', array('@drupal' => drupal_install_profile_name())));
    $output = '<p>'. st('Congratulations, @drupal has been successfully installed.', array('@drupal' => drupal_install_profile_name())) .'</p>';
    $messages = drupal_set_message();
    $output .= '<p>'. (isset($messages['error']) ? st('Please review the messages above before continuing on to <a href="@url">your new site</a>.', array('@url' => url(''))) : st('You may now visit <a href="@url">your new site</a>.', array('@url' => url('')))) .'</p>';
    $task = 'done';
  }

  // The end of the install process. Remember profile used.
  if ($task == 'done') {
    // Rebuild menu to get content type links registered by the profile,
    // and possibly any other menu items created through the tasks.
    menu_rebuild();
    variable_set('install_profile', $profile);
  }

  // Set task for user, and remember the task in the database.
  install_task_list($task);
  variable_set('install_task', $task);

  // Output page.
  print theme('maintenance_page', $output);
}

/**
 * The list of reserved tasks to run in the installer.
 */
function install_reserved_tasks() {
  return array('configure', 'locale-import', 'locale-batch', 'finished', 'done');
}

/**
 * Page to check installation requirements and report any errors.
 */
function install_check_requirements($profile) {
  $requirements = drupal_check_profile($profile);
  $severity = drupal_requirements_severity($requirements);

  // If there are issues, report them.
  if ($severity == REQUIREMENT_ERROR) {
    drupal_maintenance_theme();
    install_task_list('requirements');

    foreach ($requirements as $requirement) {
      if (isset($requirement['severity']) && $requirement['severity'] == REQUIREMENT_ERROR) {
        drupal_set_message($requirement['description'] .' ('. st('Currently using !item !version', array('!item' => $requirement['title'], '!version' => $requirement['value'])) .')', 'error');
      }
    }

    drupal_set_title(st('Incompatible environment'));
    print theme('install_page', '');
    exit;
  }
}

/**
 * Add the installation task list to the current page.
 */
function install_task_list($active = NULL) {
  // Default list of tasks.
  $tasks = array(
    'profile-select' => st('Choose profile'),
    'locale-select'  => st('Choose language'),
    'requirements'   => st('Verify requirements'),
    'database'       => st('Setup database'),
    'configure'      => st('Configure site'),
  );

  $profiles = install_find_profiles();
  $profile = isset($_GET['profile']) && isset($profiles[$_GET['profile']]) ? $_GET['profile'] : '.';
  $locales = install_find_locales($profile);

  // Keep the profile selection task if we have more profiles or only the
  // default profile is available and with only the built-in language, in
  // which case we use this screen to present information about translations.
  if (count($profiles) == 1) {
    $first_profile = array_shift($profiles);
    if ($first_profile->name != 'default' || count($locales) > 1) {
      unset($tasks['profile-select']);
    }
  }

  // Add tasks defined by the profile.
  if ($profile) {
    $function = $profile .'_profile_task_list';
    if (function_exists($function)) {
      $result = $function();
      if (is_array($result)) {
        $tasks += $result;
      }
    }
  }

  // Add finished step as the last task.
  $tasks += array(
    'locale-batch' => st('Import translations'),
    'finished'     => st('Finished')
  );

  // Remove locale related tasks if the install profile does not use them.
  if (count($locales) == 1) {
    unset($tasks['locale-select']);
    unset($tasks['locale-batch']);
  }

  // Let the theming function know that 'finished' and 'done'
  // include everything, so every step is completed.
  if (in_array($active, array('finished', 'done'))) {
    $active = NULL;
  }
  drupal_set_content('left', theme_task_list($tasks, $active));
}

function install_configure_form() {
  // This is necessary to add the task to the $_GET args so the install
  // system will know that it is done and we've taken over.

  $form['intro'] = array(
    '#value' => st('To configure your web site, please provide the following information.'),
    '#weight' => -10,
  );
  $form['site_information'] = array(
    '#type' => 'fieldset',
    '#title' => st('Site information'),
    '#collapsible' => FALSE,
  );
  $form['site_information']['site_name'] = array(
    '#type' => 'textfield',
    '#title' => st('Site name'),
    '#default_value' => variable_get('site_name', 'Drupal'),
    '#required' => TRUE,
    '#weight' => -20,
  );
  $form['site_information']['site_mail'] = array(
    '#type' => 'textfield',
    '#title' => st('Site e-mail address'),
    '#default_value' => variable_get('site_mail', ini_get('sendmail_from')),
    '#description' => st('A valid e-mail address to be used as the "From" address by the auto-mailer during registration, new password requests, notifications, etc.  To lessen the likelihood of e-mail being marked as spam, this e-mail address should use the same domain as the website.'),
    '#required' => TRUE,
    '#weight' => -15,
  );
  $form['admin_account'] = array(
    '#type' => 'fieldset',
    '#title' => st('Administrator account'),
    '#collapsible' => FALSE,
  );
  $form['admin_account']['account']['#tree'] = TRUE;
  $form['admin_account']['markup'] = array(
    '#value' => '<p class="description">'. st('The administrator account has complete access to the site; it will automatically be granted all permissions and can perform any administrative activity. This will be the only account that can perform certain activities, so keep its credentials safe.') .'</p>',
    '#weight' => -10,
  );

  $form['admin_account']['account']['name'] = array('#type' => 'textfield',
    '#title' => st('Username'),
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#description' => st('Spaces are allowed; punctuation is not allowed except for periods, hyphens, and underscores.'),
    '#required' => TRUE,
    '#weight' => -10,
  );

  $form['admin_account']['account']['mail'] = array('#type' => 'textfield',
    '#title' => st('E-mail address'),
    '#maxlength' => EMAIL_MAX_LENGTH,
    '#description' => st('All e-mails from the system will be sent to this address. The e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.'),
    '#required' => TRUE,
    '#weight' => -5,
  );
  $form['admin_account']['account']['pass'] = array(
    '#type' => 'password_confirm',
    '#required' => TRUE,
    '#size' => 25,
    '#weight' => 0,
  );

  $zones = _system_zonelist();

  $form['server_settings'] = array(
    '#type' => 'fieldset',
    '#title' => st('Server settings'),
    '#collapsible' => FALSE,
  );
  $form['server_settings']['date_default_timezone'] = array(
    '#type' => 'select',
    '#title' => st('Default time zone'),
    '#default_value' => variable_get('date_default_timezone', 0),
    '#options' => $zones,
    '#description' => st('By default, dates in this site will be displayed in the chosen time zone.'),
    '#weight' => 5,
  );

  drupal_add_js(drupal_get_path('module', 'system') .'/system.js', 'module');
  drupal_add_js(array('cleanURL' => array('success' => st('Your server has been successfully tested to support this feature.'), 'failure' => st('Your system configuration does not currently support this feature. The <a href="http://drupal.org/node/15365">handbook page on Clean URLs</a> has additional troubleshooting information.'), 'testing' => st('Testing clean URLs...'))), 'setting');
  drupal_add_js('
// Global Killswitch
if (Drupal.jsEnabled) {
  $(document).ready(function() {
    Drupal.cleanURLsInstallCheck();
    Drupal.installDefaultTimezone();
  });
}', 'inline');

  $form['server_settings']['clean_url'] = array(
    '#type' => 'radios',
    '#title' => st('Clean URLs'),
    '#default_value' => variable_get('clean_url', 0),
    '#options' => array(st('Disabled'), st('Enabled')),
    '#description' => st('This option makes Drupal emit "clean" URLs (i.e. without <code>?q=</code> in the URL).'),
    '#disabled' => TRUE,
    '#prefix' => '<div id="clean-url" class="install">',
    '#suffix' => '</div>',
    '#weight' => 10,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => st('Submit'),
    '#weight' => 15,
  );
  $hook_form_alter = $_GET['profile'] .'_form_alter';
  if (function_exists($hook_form_alter)) {
    $hook_form_alter($form, 'install_configure');
  }
  return $form;
}

function install_configure_form_validate($form_values, $form, &$form_state) {
  if ($error = user_validate_name($form_values['account']['name'])) {
    form_error($form['admin_account']['account']['name'], $error);
  }
  if ($error = user_validate_mail($form_values['account']['mail'])) {
    form_error($form['admin_account']['account']['mail'], $error);
  }
  if ($error = user_validate_mail($form_values['site_mail'])) {
    form_error($form['site_information']['site_mail'], $error);
  }
}

function install_configure_form_submit($form_values, $form, &$form_state) {
  global $user;

  variable_set('site_name', $form_values['site_name']);
  variable_set('site_mail', $form_values['site_mail']);
  variable_set('date_default_timezone', $form_values['date_default_timezone']);
  // Turn this off temporarily so that we can pass a password through.
  variable_set('user_email_verification', FALSE);
  user_register_submit($form_values['account'], $form, $form_state);
  variable_set('user_email_verification', TRUE);
  if (isset($form_values['clean_url'])) {
    variable_set('clean_url', $form_values['clean_url']);
  }
  // The user is now logged in, but has no session ID yet, which
  // would be required later in the request, so remember it.
  $user->sid = session_id();

  return 'finished';
}

install_main();
