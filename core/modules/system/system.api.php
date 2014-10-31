<?php

/**
 * @file
 * Hooks provided by Drupal core and the System module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform periodic actions.
 *
 * Modules that require some commands to be executed periodically can
 * implement hook_cron(). The engine will then call the hook whenever a cron
 * run happens, as defined by the administrator. Typical tasks managed by
 * hook_cron() are database maintenance, backups, recalculation of settings
 * or parameters, automated mailing, and retrieving remote data.
 *
 * Short-running or non-resource-intensive tasks can be executed directly in
 * the hook_cron() implementation.
 *
 * Long-running tasks and tasks that could time out, such as retrieving remote
 * data, sending email, and intensive file tasks, should use the queue API
 * instead of executing the tasks directly. To do this, first define one or
 * more queues via a \Drupal\Core\Annotation\QueueWorker plugin. Then, add items
 * that need to be processed to the defined queues.
 */
function hook_cron() {
  // Short-running operation example, not using a queue:
  // Delete all expired records since the last cron run.
  $expires = \Drupal::state()->get('mymodule.cron_last_run', REQUEST_TIME);
  db_delete('mymodule_table')
    ->condition('expires', $expires, '>=')
    ->execute();
  \Drupal::state()->set('mymodule.cron_last_run', REQUEST_TIME);

  // Long-running operation example, leveraging a queue:
  // Fetch feeds from other sites.
  $result = db_query('SELECT * FROM {aggregator_feed} WHERE checked + refresh < :time AND refresh <> :never', array(
    ':time' => REQUEST_TIME,
    ':never' => AGGREGATOR_CLEAR_NEVER,
  ));
  $queue = \Drupal::queue('aggregator_feeds');
  foreach ($result as $feed) {
    $queue->createItem($feed);
  }
}

/**
 * Alter available data types for typed data wrappers.
 *
 * @param array $data_types
 *   An array of data type information.
 *
 * @see hook_data_type_info()
 */
function hook_data_type_info_alter(&$data_types) {
  $data_types['email']['class'] = '\Drupal\mymodule\Type\Email';
}

/**
 * Alter cron queue information before cron runs.
 *
 * Called by \Drupal\Core\Cron to allow modules to alter cron queue settings
 * before any jobs are processesed.
 *
 * @param array $queues
 *   An array of cron queue information.
 *
 * @see \Drupal\Core\QueueWorker\QueueWorkerInterface
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see \Drupal\Core\Cron
 */
function hook_queue_info_alter(&$queues) {
  // This site has many feeds so let's spend 90 seconds on each cron run
  // updating feeds instead of the default 60.
  $queues['aggregator_feeds']['cron']['time'] = 90;
}

/**
 * Alter the Ajax command data that is sent to the client.
 *
 * @param \Drupal\Core\Ajax\CommandInterface[] $data
 *   An array of all the rendered commands that will be sent to the client.
 *
 * @see \Drupal\Core\Ajax\AjaxResponse::ajaxRender()
 */
function hook_ajax_render_alter(array &$data) {
  // Inject any new status messages into the content area.
  $status_messages = array('#theme' => 'status_messages');
  $command = new \Drupal\Core\Ajax\PrependCommand('#block-system-main .content', drupal_render($status_messages));
  $data[] = $command->render();
}

/**
 * Alters all the menu links discovered by the menu link plugin manager.
 *
 * @param array $links
 *   The link definitions to be altered.
 *
 * @return array
 *   An array of discovered menu links. Each link has a key that is the machine
 *   name, which must be unique. By default, use the route name as the
 *   machine name. In cases where multiple links use the same route name, such
 *   as two links to the same page in different menus, or two links using the
 *   same route name but different route parameters, the suggested machine name
 *   patten is the route name followed by a dot and a unique suffix. For
 *   example, an additional logout link might have a machine name of
 *   user.logout.navigation, and default links provided to edit the article and
 *   page content types could use machine names
 *   entity.node_type.edit_form.article and entity.node_type.edit_form.page.
 *   Since the machine name may be arbitrary, you should never write code that
 *   assumes it is identical to the route name.
 *
 *   The value corresponding to each machine name key is an associative array
 *   that may contain the following key-value pairs:
 *   - title: (required) The untranslated title of the menu link.
 *   - description: The untranslated description of the link.
 *   - route_name: (optional) The route name to be used to build the path.
 *     Either a route_name or a link_path must be provided.
 *   - route_parameters: (optional) The route parameters to build the path.
 *   - link_path: (optional) If you have an external link use link_path instead
 *     of providing a route_name.
 *   - parent: (optional) The machine name of the link that is this link's menu
 *     parent.
 *   - weight: (optional) An integer that determines the relative position of
 *     items in the menu; higher-weighted items sink. Defaults to 0. Menu items
 *     with the same weight are ordered alphabetically.
 *   - menu_name: (optional) The machine name of a menu to put the link in, if
 *     not the default Tools menu.
 *   - expanded: (optional) If set to TRUE, and if a menu link is provided for
 *     this menu item (as a result of other properties), then the menu link is
 *     always expanded, equivalent to its 'always expanded' checkbox being set
 *     in the UI.
 *   - options: (optional) An array of options to be passed to _l() when
 *     generating a link from this menu item.
 *
 * @ingroup menu
 */
function hook_menu_links_discovered_alter(&$links) {
  // Change the weight and title of the user.logout link.
  $links['user.logout']['weight'] = -10;
  $links['user.logout']['title'] = 'Logout';
}

/**
 * Alter tabs and actions displayed on the page before they are rendered.
 *
 * This hook is invoked by menu_local_tasks(). The system-determined tabs and
 * actions are passed in by reference. Additional tabs or actions may be added.
 *
 * Each tab or action is an associative array containing:
 * - #theme: The theme function to use to render.
 * - #link: An associative array containing:
 *   - title: The localized title of the link.
 *   - href: The system path to link to.
 *   - localized_options: An array of options to pass to _l().
 * - #weight: The link's weight compared to other links.
 * - #active: Whether the link should be marked as 'active'.
 *
 * @param array $data
 *   An associative array containing:
 *   - actions: A list of of actions keyed by their href, each one being an
 *     associative array as described above.
 *   - tabs: A list of (up to 2) tab levels that contain a list of of tabs keyed
 *     by their href, each one being an associative array as described above.
 * @param string $route_name
 *   The route name of the page.
 */
function hook_menu_local_tasks(&$data, $route_name) {
  // Add an action linking to node/add to all pages.
  $data['actions']['node/add'] = array(
    '#theme' => 'menu_local_action',
    '#link' => array(
      'title' => t('Add content'),
      'url' => Url::fromRoute('node.add_page'),
      'localized_options' => array(
        'attributes' => array(
          'title' => t('Add content'),
        ),
      ),
    ),
  );

  // Add a tab linking to node/add to all pages.
  $data['tabs'][0]['node/add'] = array(
    '#theme' => 'menu_local_task',
    '#link' => array(
      'title' => t('Example tab'),
      'url' => Url::fromRoute('node.add_page'),
      'localized_options' => array(
        'attributes' => array(
          'title' => t('Add content'),
        ),
      ),
    ),
  );
}

/**
 * Alter tabs and actions displayed on the page before they are rendered.
 *
 * This hook is invoked by menu_local_tasks(). The system-determined tabs and
 * actions are passed in by reference. Existing tabs or actions may be altered.
 *
 * @param array $data
 *   An associative array containing tabs and actions. See
 *   hook_menu_local_tasks() for details.
 * @param string $route_name
 *   The route name of the page.
 *
 * @see hook_menu_local_tasks()
 *
 * @ingroup menu
 */
function hook_menu_local_tasks_alter(&$data, $route_name) {
}

/**
 * Alter local actions plugins.
 *
 * @param array $local_actions
 *   The array of local action plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\Core\Menu\LocalActionInterface
 * @see \Drupal\Core\Menu\LocalActionManager
 *
 * @ingroup menu
 */
function hook_menu_local_actions_alter(&$local_actions) {
}

/**
 * Alter local tasks plugins.
 *
 * @param array $local_tasks
 *   The array of local tasks plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\Core\Menu\LocalTaskInterface
 * @see \Drupal\Core\Menu\LocalTaskManager
 */
function hook_local_tasks_alter(&$local_tasks) {
  // Remove a specified local task plugin.
  unset($local_tasks['example_plugin_id']);
}

/**
 * Alter contextual links before they are rendered.
 *
 * This hook is invoked by
 * \Drupal\Core\Menu\ContextualLinkManager::getContextualLinkPluginsByGroup().
 * The system-determined contextual links are passed in by reference. Additional
 * links may be added and existing links can be altered.
 *
 * Each contextual link contains the following entries:
 * - title: The localized title of the link.
 * - route_name: The route name of the link.
 * - route_parameters: The route parameters of the link.
 * - localized_options: An array of options to pass to _url().
 * - (optional) weight: The weight of the link, which is used to sort the links.
 *
 *
 * @param array $links
 *   An associative array containing contextual links for the given $group,
 *   as described above. The array keys are used to build CSS class names for
 *   contextual links and must therefore be unique for each set of contextual
 *   links.
 * @param string $group
 *   The group of contextual links being rendered.
 * @param array $route_parameters.
 *   The route parameters passed to each route_name of the contextual links.
 *   For example:
 *   @code
 *   array('node' => $node->id())
 *   @endcode
 *
 * @see \Drupal\Core\Menu\ContextualLinkManager
 *
 * @ingroup menu
 */
function hook_contextual_links_alter(array &$links, $group, array $route_parameters) {
  if ($group == 'menu') {
    // Dynamically use the menu name for the title of the menu_edit contextual
    // link.
    $menu = \Drupal::entityManager()->getStorage('menu')->load($route_parameters['menu']);
    $links['menu_edit']['title'] = t('Edit menu: !label', array('!label' => $menu->label()));
  }
}

/**
 * Alter the plugin definition of contextual links.
 *
 * @param array $contextual_links
 *   An array of contextual_links plugin definitions, keyed by contextual link
 *   ID. Each entry contains the following keys:
 *     - title: The displayed title of the link
 *     - route_name: The route_name of the contextual link to be displayed
 *     - group: The group under which the contextual links should be added to.
 *       Possible values are e.g. 'node' or 'menu'.
 *
 * @see \Drupal\Core\Menu\ContextualLinkManager
 */
function hook_contextual_links_plugins_alter(array &$contextual_links) {
  $contextual_links['menu_edit']['title'] = 'Edit the menu';
}

/**
 * Perform alterations before a form is rendered.
 *
 * One popular use of this hook is to add form elements to the node form. When
 * altering a node form, the node entity can be retrieved by invoking
 * $form_state->getFormObject()->getEntity().
 *
 * In addition to hook_form_alter(), which is called for all forms, there are
 * two more specific form hooks available. The first,
 * hook_form_BASE_FORM_ID_alter(), allows targeting of a form/forms via a base
 * form (if one exists). The second, hook_form_FORM_ID_alter(), can be used to
 * target a specific form directly.
 *
 * The call order is as follows: all existing form alter functions are called
 * for module A, then all for module B, etc., followed by all for any base
 * theme(s), and finally for the theme itself. The module order is determined
 * by system weight, then by module name.
 *
 * Within each module, form alter hooks are called in the following order:
 * first, hook_form_alter(); second, hook_form_BASE_FORM_ID_alter(); third,
 * hook_form_FORM_ID_alter(). So, for each module, the more general hooks are
 * called first followed by the more specific.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_BASE_FORM_ID_alter()
 * @see hook_form_FORM_ID_alter()
 * @see forms_api_reference.html
 */
function hook_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if (isset($form['type']) && $form['type']['#value'] . '_node_settings' == $form_id) {
    $upload_enabled_types = \Drupal::config('mymodule.settings')->get('upload_enabled_types');
    $form['workflow']['upload_' . $form['type']['#value']] = array(
      '#type' => 'radios',
      '#title' => t('Attachments'),
      '#default_value' => in_array($form['type']['#value'], $upload_enabled_types) ? 1 : 0,
      '#options' => array(t('Disabled'), t('Enabled')),
    );
    // Add a custom submit handler to save the array of types back to the config file.
    $form['actions']['submit']['#submit'][] = 'mymodule_upload_enabled_types_submit';
  }
}

/**
 * Provide a form-specific alteration instead of the global hook_form_alter().
 *
 * Modules can implement hook_form_FORM_ID_alter() to modify a specific form,
 * rather than implementing hook_form_alter() and checking the form ID, or
 * using long switch statements to alter multiple forms.
 *
 * Form alter hooks are called in the following order: hook_form_alter(),
 * hook_form_BASE_FORM_ID_alter(), hook_form_FORM_ID_alter(). See
 * hook_form_alter() for more details.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_alter()
 * @see hook_form_BASE_FORM_ID_alter()
 * @see \Drupal\Core\Form\FormBuilderInterface::prepareForm()
 * @see forms_api_reference.html
 */
function hook_form_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Modification for the form with the given form ID goes here. For example, if
  // FORM_ID is "user_register_form" this code would run only on the user
  // registration form.

  // Add a checkbox to registration form about agreeing to terms of use.
  $form['terms_of_use'] = array(
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  );
}

/**
 * Provide a form-specific alteration for shared ('base') forms.
 *
 * By default, when \Drupal::formBuilder()->getForm() is called, Drupal looks
 * for a function with the same name as the form ID, and uses that function to
 * build the form. In contrast, base forms allow multiple form IDs to be mapped
 * to a single base (also called 'factory') form function.
 *
 * Modules can implement hook_form_BASE_FORM_ID_alter() to modify a specific
 * base form, rather than implementing hook_form_alter() and checking for
 * conditions that would identify the shared form constructor.
 *
 * To identify the base form ID for a particular form (or to determine whether
 * one exists) check the $form_state. The base form ID is stored under
 * $form_state->getBuildInfo()['base_form_id'].
 *
 * Form alter hooks are called in the following order: hook_form_alter(),
 * hook_form_BASE_FORM_ID_alter(), hook_form_FORM_ID_alter(). See
 * hook_form_alter() for more details.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form.
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_alter()
 * @see hook_form_FORM_ID_alter()
 * @see \Drupal\Core\Form\FormBuilderInterface::prepareForm()
 */
function hook_form_BASE_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Modification for the form with the given BASE_FORM_ID goes here. For
  // example, if BASE_FORM_ID is "node_form", this code would run on every
  // node form, regardless of node type.

  // Add a checkbox to the node form about agreeing to terms of use.
  $form['terms_of_use'] = array(
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  );
}

/**
 * Alter an email message created with the drupal_mail() function.
 *
 * hook_mail_alter() allows modification of email messages created and sent
 * with drupal_mail(). Usage examples include adding and/or changing message
 * text, message fields, and message headers.
 *
 * Email messages sent using functions other than drupal_mail() will not
 * invoke hook_mail_alter(). For example, a contributed module directly
 * calling the drupal_mail_system()->mail() or PHP mail() function
 * will not invoke this hook. All core modules use drupal_mail() for
 * messaging, it is best practice but not mandatory in contributed modules.
 *
 * @param $message
 *   An array containing the message data. Keys in this array include:
 *  - 'id':
 *     The drupal_mail() id of the message. Look at module source code or
 *     drupal_mail() for possible id values.
 *  - 'to':
 *     The address or addresses the message will be sent to. The
 *     formatting of this string must comply with RFC 2822.
 *  - 'from':
 *     The address the message will be marked as being from, which is
 *     either a custom address or the site-wide default email address.
 *  - 'subject':
 *     Subject of the email to be sent. This must not contain any newline
 *     characters, or the email may not be sent properly.
 *  - 'body':
 *     An array of strings containing the message text. The message body is
 *     created by concatenating the individual array strings into a single text
 *     string using "\n\n" as a separator.
 *  - 'headers':
 *     Associative array containing mail headers, such as From, Sender,
 *     MIME-Version, Content-Type, etc.
 *  - 'params':
 *     An array of optional parameters supplied by the caller of drupal_mail()
 *     that is used to build the message before hook_mail_alter() is invoked.
 *  - 'language':
 *     The language object used to build the message before hook_mail_alter()
 *     is invoked.
 *  - 'send':
 *     Set to FALSE to abort sending this email message.
 *
 * @see drupal_mail()
 */
function hook_mail_alter(&$message) {
  if ($message['id'] == 'modulename_messagekey') {
    if (!example_notifications_optin($message['to'], $message['id'])) {
      // If the recipient has opted to not receive such messages, cancel
      // sending.
      $message['send'] = FALSE;
      return;
    }
    $message['body'][] = "--\nMail sent out from " . \Drupal::config('system.site')->get('name');
  }
}

/**
 * Perform alterations to the breadcrumb built by the BreadcrumbManager.
 *
 * @param array $breadcrumb
 *   An array of breadcrumb link a tags, returned by the breadcrumb manager
 *   build method, for example
 *   @code
 *     array('<a href="/">Home</a>');
 *   @endcode
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match.
 * @param array $context
 *   May include the following key:
 *   - builder: the instance of
 *     \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface that constructed this
 *     breadcrumb, or NULL if no builder acted based on the current attributes.
 */
function hook_system_breadcrumb_alter(array &$breadcrumb, \Drupal\Core\Routing\RouteMatchInterface $route_match, array $context) {
  // Add an item to the end of the breadcrumb.
  $breadcrumb[] = Drupal::l(t('Text'), 'example_route_name');
}

/**
 * Provide online user help.
 *
 * By implementing hook_help(), a module can make documentation available to
 * the user for the module as a whole, or for specific pages. Help for
 * developers should usually be provided via function header comments in the
 * code, or in special API example files.
 *
 * The page-specific help information provided by this hook appears as a system
 * help block on that page. The module overview help information is displayed
 * by the Help module. It can be accessed from the page at admin/help or from
 * the Extend page.
 *
 * For detailed usage examples of:
 * - Module overview help, see content_translation_help(). Module overview
 *   help should follow
 *   @link https://drupal.org/node/632280 the standard help template. @endlink
 * - Page-specific help using only routes, see book_help().
 * - Page-specific help using routes and $request, see block_help().
 *
 * @param string $route_name
 *   For page-specific help, use the route name as identified in the
 *   module's routing.yml file. For module overview help, the route name
 *   will be in the form of "help.page.$modulename".
 * @param Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match. This can be used to generate different help
 *   output for different pages that share the same route.
 *
 * @return string
 *   A localized string containing the help text.
 */
function hook_help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match) {
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href="!blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', array('!blocks' => \Drupal::url('block.admin_display'))) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
}

/**
 * Prepare a message based on parameters; called from drupal_mail().
 *
 * Note that hook_mail(), unlike hook_mail_alter(), is only called on the
 * $module argument to drupal_mail(), not all modules.
 *
 * @param $key
 *   An identifier of the mail.
 * @param $message
 *   An array to be filled in. Elements in this array include:
 *   - id: An ID to identify the mail sent. Look at module source code
 *     or drupal_mail() for possible id values.
 *   - to: The address or addresses the message will be sent to. The
 *     formatting of this string must comply with RFC 2822.
 *   - subject: Subject of the email to be sent. This must not contain any
 *     newline characters, or the mail may not be sent properly. drupal_mail()
 *     sets this to an empty string when the hook is invoked.
 *   - body: An array of lines containing the message to be sent. Drupal will
 *     format the correct line endings for you. drupal_mail() sets this to an
 *     empty array when the hook is invoked.
 *   - from: The address the message will be marked as being from, which is
 *     set by drupal_mail() to either a custom address or the site-wide
 *     default email address when the hook is invoked.
 *   - headers: Associative array containing mail headers, such as From,
 *     Sender, MIME-Version, Content-Type, etc. drupal_mail() pre-fills
 *     several headers in this array.
 * @param $params
 *   An array of parameters supplied by the caller of drupal_mail().
 */
function hook_mail($key, &$message, $params) {
  $account = $params['account'];
  $context = $params['context'];
  $variables = array(
    '%site_name' => \Drupal::config('system.site')->get('name'),
    '%username' => user_format_name($account),
  );
  if ($context['hook'] == 'taxonomy') {
    $entity = $params['entity'];
    $vocabulary = entity_load('taxonomy_vocabulary', $entity->id());
    $variables += array(
      '%term_name' => $entity->name,
      '%term_description' => $entity->description,
      '%term_id' => $entity->id(),
      '%vocabulary_name' => $vocabulary->name,
      '%vocabulary_description' => $vocabulary->description,
      '%vocabulary_id' => $vocabulary->id(),
    );
  }

  // Node-based variable translation is only available if we have a node.
  if (isset($params['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $params['node'];
    $variables += array(
      '%uid' => $node->getOwnerId(),
      '%url' => $node->url('canonical', array('absolute' => TRUE)),
      '%node_type' => node_get_type_label($node),
      '%title' => $node->getTitle(),
      '%teaser' => $node->teaser,
      '%body' => $node->body,
    );
  }
  $subject = strtr($context['subject'], $variables);
  $body = strtr($context['message'], $variables);
  $message['subject'] .= str_replace(array("\r", "\n"), '', $subject);
  $message['body'][] = drupal_html_to_text($body);
}

/**
 * Flush all persistent and static caches.
 *
 * This hook asks your module to clear all of its static caches,
 * in order to ensure a clean environment for subsequently
 * invoked data rebuilds.
 *
 * Do NOT use this hook for rebuilding information. Only use it to flush custom
 * caches.
 *
 * Static caches using drupal_static() do not need to be reset manually.
 * However, all other static variables that do not use drupal_static() must be
 * manually reset.
 *
 * This hook is invoked by drupal_flush_all_caches(). It runs before module data
 * is updated and before hook_rebuild().
 *
 * @see drupal_flush_all_caches()
 * @see hook_rebuild()
 */
function hook_cache_flush() {
  if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'update') {
    _update_cache_clear();
  }
}

/**
 * Rebuild data based upon refreshed caches.
 *
 * This hook allows your module to rebuild its data based on the latest/current
 * module data. It runs after hook_cache_flush() and after all module data has
 * been updated.
 *
 * This hook is only invoked after the system has been completely cleared;
 * i.e., all previously cached data is known to be gone and every API in the
 * system is known to return current information, so your module can safely rely
 * on all available data to rebuild its own.
 *
 * @see hook_cache_flush()
 * @see drupal_flush_all_caches()
 */
function hook_rebuild() {
  $themes = list_themes();
  foreach ($themes as $theme) {
    _block_rehash($theme->getName());
  }
}

/**
 * Alters the list of PHP stream wrapper implementations.
 *
 * @see file_get_stream_wrappers()
 * @see \Drupal\Core\StreamWrapper\StreamWrapperManager
 */
function hook_stream_wrappers_alter(&$wrappers) {
  // Change the name of private files to reflect the performance.
  $wrappers['private']['name'] = t('Slow files');
}

/**
 * Control access to private file downloads and specify HTTP headers.
 *
 * This hook allows modules to enforce permissions on file downloads whenever
 * Drupal is handling file download, as opposed to the web server bypassing
 * Drupal and returning the file from a public directory. Modules can also
 * provide headers to specify information like the file's name or MIME type.
 *
 * @param $uri
 *   The URI of the file.
 * @return
 *   If the user does not have permission to access the file, return -1. If the
 *   user has permission, return an array with the appropriate headers. If the
 *   file is not controlled by the current module, the return value should be
 *   NULL.
 *
 * @see file_download()
 */
function hook_file_download($uri) {
  // Check to see if this is a config download.
  $scheme = file_uri_scheme($uri);
  $target = file_uri_target($uri);
  if ($scheme == 'temporary' && $target == 'config.tar.gz') {
    return array(
      'Content-disposition' => 'attachment; filename="config.tar.gz"',
    );
  }
}

/**
 * Alter the URL to a file.
 *
 * This hook is called from file_create_url(), and  is called fairly
 * frequently (10+ times per page), depending on how many files there are in a
 * given page.
 * If CSS and JS aggregation are disabled, this can become very frequently
 * (50+ times per page) so performance is critical.
 *
 * This function should alter the URI, if it wants to rewrite the file URL.
 *
 * @param $uri
 *   The URI to a file for which we need an external URL, or the path to a
 *   shipped file.
 */
function hook_file_url_alter(&$uri) {
  $user = \Drupal::currentUser();

  // User 1 will always see the local file in this example.
  if ($user->id() == 1) {
    return;
  }

  $cdn1 = 'http://cdn1.example.com';
  $cdn2 = 'http://cdn2.example.com';
  $cdn_extensions = array('css', 'js', 'gif', 'jpg', 'jpeg', 'png');

  // Most CDNs don't support private file transfers without a lot of hassle,
  // so don't support this in the common case.
  $schemes = array('public');

  $scheme = file_uri_scheme($uri);

  // Only serve shipped files and public created files from the CDN.
  if (!$scheme || in_array($scheme, $schemes)) {
    // Shipped files.
    if (!$scheme) {
      $path = $uri;
    }
    // Public created files.
    else {
      $wrapper = file_stream_wrapper_get_instance_by_scheme($scheme);
      $path = $wrapper->getDirectoryPath() . '/' . file_uri_target($uri);
    }

    // Clean up Windows paths.
    $path = str_replace('\\', '/', $path);

    // Serve files with one of the CDN extensions from CDN 1, all others from
    // CDN 2.
    $pathinfo = pathinfo($path);
    if (isset($pathinfo['extension']) && in_array($pathinfo['extension'], $cdn_extensions)) {
      $uri = $cdn1 . '/' . $path;
    }
    else {
      $uri = $cdn2 . '/' . $path;
    }
  }
}

/**
 * Define the current version of the database schema.
 *
 * A Drupal schema definition is an array structure representing one or more
 * tables and their related keys and indexes. A schema is defined by
 * hook_schema() which must live in your module's .install file.
 *
 * The tables declared by this hook will be automatically created when the
 * module is installed, and removed when the module is uninstalled. This happens
 * before hook_install() is invoked, and after hook_uninstall() is invoked,
 * respectively.
 *
 * By declaring the tables used by your module via an implementation of
 * hook_schema(), these tables will be available on all supported database
 * engines. You don't have to deal with the different SQL dialects for table
 * creation and alteration of the supported database engines.
 *
 * See the Schema API Handbook at http://drupal.org/node/146843 for details on
 * schema definition structures.
 *
 * @return array
 *   A schema definition structure array. For each element of the
 *   array, the key is a table name and the value is a table structure
 *   definition.
 *
 * @see hook_schema_alter()
 *
 * @ingroup schemaapi
 */
function hook_schema() {
  $schema['node'] = array(
    // Example (partial) specification for table "node".
    'description' => 'The base table for nodes.',
    'fields' => array(
      'nid' => array(
        'description' => 'The primary identifier for a node.',
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ),
      'vid' => array(
        'description' => 'The current {node_field_revision}.vid version identifier.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ),
      'type' => array(
        'description' => 'The type of this node.',
        'type' => 'varchar',
        'length' => 32,
        'not null' => TRUE,
        'default' => '',
      ),
      'title' => array(
        'description' => 'The title of this node, always treated as non-markup plain text.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
      ),
    ),
    'indexes' => array(
      'node_changed'        => array('changed'),
      'node_created'        => array('created'),
    ),
    'unique keys' => array(
      'nid_vid' => array('nid', 'vid'),
      'vid'     => array('vid'),
    ),
    'foreign keys' => array(
      'node_revision' => array(
        'table' => 'node_field_revision',
        'columns' => array('vid' => 'vid'),
      ),
      'node_author' => array(
        'table' => 'users',
        'columns' => array('uid' => 'uid'),
      ),
    ),
    'primary key' => array('nid'),
  );
  return $schema;
}

/**
 * Perform alterations to existing database schemas.
 *
 * When a module modifies the database structure of another module (by
 * changing, adding or removing fields, keys or indexes), it should
 * implement hook_schema_alter() to update the default $schema to take its
 * changes into account.
 *
 * See hook_schema() for details on the schema definition structure.
 *
 * @param $schema
 *   Nested array describing the schemas for all modules.
 *
 * @ingroup schemaapi
 */
function hook_schema_alter(&$schema) {
  // Add field to existing schema.
  $schema['users']['fields']['timezone_id'] = array(
    'type' => 'int',
    'not null' => TRUE,
    'default' => 0,
    'description' => 'Per-user timezone configuration.',
  );
}

/**
 * Perform alterations to a structured query.
 *
 * Structured (aka dynamic) queries that have tags associated may be altered by any module
 * before the query is executed.
 *
 * @param $query
 *   A Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_TAG_alter()
 * @see node_query_node_access_alter()
 * @see AlterableInterface
 * @see SelectInterface
 */
function hook_query_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  if ($query->hasTag('micro_limit')) {
    $query->range(0, 2);
  }
}

/**
 * Perform alterations to a structured query for a given tag.
 *
 * @param $query
 *   An Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_alter()
 * @see node_query_node_access_alter()
 * @see AlterableInterface
 * @see SelectInterface
 */
function hook_query_TAG_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  // Skip the extra expensive alterations if site has no node access control modules.
  if (!node_access_view_all_nodes()) {
    // Prevent duplicates records.
    $query->distinct();
    // The recognized operations are 'view', 'update', 'delete'.
    if (!$op = $query->getMetaData('op')) {
      $op = 'view';
    }
    // Skip the extra joins and conditions for node admins.
    if (!\Drupal::currentUser()->hasPermission('bypass node access')) {
      // The node_access table has the access grants for any given node.
      $access_alias = $query->join('node_access', 'na', '%alias.nid = n.nid');
      $or = db_or();
      // If any grant exists for the specified user, then user has access to the node for the specified operation.
      foreach (node_access_grants($op, $query->getMetaData('account')) as $realm => $gids) {
        foreach ($gids as $gid) {
          $or->condition(db_and()
            ->condition($access_alias . '.gid', $gid)
            ->condition($access_alias . '.realm', $realm)
          );
        }
      }

      if (count($or->conditions())) {
        $query->condition($or);
      }

      $query->condition($access_alias . 'grant_' . $op, 1, '>=');
    }
  }
}

/**
 * Alter MIME type mappings used to determine MIME type from a file extension.
 *
 * Invoked by \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::guess(). It
 * is used to allow modules to add to or modify the default mapping from
 * \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::$defaultMapping.
 *
 * @param $mapping
 *   An array of mimetypes correlated to the extensions that relate to them.
 *   The array has 'mimetypes' and 'extensions' elements, each of which is an
 *   array.
 *
 * @see \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::guess()
 * @see \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::$defaultMapping
 */
function hook_file_mimetype_mapping_alter(&$mapping) {
  // Add new MIME type 'drupal/info'.
  $mapping['mimetypes']['example_info'] = 'drupal/info';
  // Add new extension '.info.yml' and map it to the 'drupal/info' MIME type.
  $mapping['extensions']['info'] = 'example_info';
  // Override existing extension mapping for '.ogg' files.
  $mapping['extensions']['ogg'] = 189;
}

/**
 * Alter archiver information declared by other modules.
 *
 * See hook_archiver_info() for a description of archivers and the archiver
 * information structure.
 *
 * @param $info
 *   Archiver information to alter (return values from hook_archiver_info()).
 */
function hook_archiver_info_alter(&$info) {
  $info['tar']['extensions'][] = 'tgz';
}

/**
 * Alter the list of mail backend plugin definitions.
 *
 * @param array $info
 *   The mail backend plugin definitions to be altered.
 *
 * @see \Drupal\Core\Annotation\Mail
 * @see \Drupal\Core\Mail\MailManager
 */
function hook_mail_backend_info_alter(&$info) {
  unset($info['test_mail_collector']);
}

/**
 * Alters theme operation links.
 *
 * @param $theme_groups
 *   An associative array containing groups of themes.
 *
 * @see system_themes_page()
 */
function hook_system_themes_page_alter(&$theme_groups) {
  foreach ($theme_groups as $state => &$group) {
    foreach ($theme_groups[$state] as &$theme) {
      // Add a foo link to each list of theme operations.
      $theme->operations[] = array(
        'title' => t('Foo'),
        'url' => Url::fromRoute('system.themes_page'),
        'query' => array('theme' => $theme->getName())
      );
    }
  }
}

/**
 * Provide replacement values for placeholder tokens.
 *
 * This hook is invoked when someone calls
 * \Drupal\Core\Utility\Token::replace(). That function first scans the text for
 * [type:token] patterns, and splits the needed tokens into groups by type.
 * Then hook_tokens() is invoked on each token-type group, allowing your module
 * to respond by providing replacement text for any of the tokens in the group
 * that your module knows how to process.
 *
 * A module implementing this hook should also implement hook_token_info() in
 * order to list its available tokens on editing screens.
 *
 * @param $type
 *   The machine-readable name of the type (group) of token being replaced, such
 *   as 'node', 'user', or another type defined by a hook_token_info()
 *   implementation.
 * @param $tokens
 *   An array of tokens to be replaced. The keys are the machine-readable token
 *   names, and the values are the raw [type:token] strings that appeared in the
 *   original text.
 * @param $data
 *   (optional) An associative array of data objects to be used when generating
 *   replacement values, as supplied in the $data parameter to
 *   \Drupal\Core\Utility\Token::replace().
 * @param $options
 *   (optional) An associative array of options for token replacement; see
 *   \Drupal\Core\Utility\Token::replace() for possible values.
 *
 * @return
 *   An associative array of replacement values, keyed by the raw [type:token]
 *   strings from the original text.
 *
 * @see hook_token_info()
 * @see hook_tokens_alter()
 */
function hook_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = language_load($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case 'nid':
          $replacements[$original] = $node->nid;
          break;

        case 'title':
          $replacements[$original] = $sanitize ? String::checkPlain($node->getTitle()) : $node->getTitle();
          break;

        case 'edit-url':
          $replacements[$original] = $node->url('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $node->getOwner() ? $node->getOwner() : user_load(0);
          $replacements[$original] = $sanitize ? String::checkPlain($account->label()) : $account->label();
          break;

        case 'created':
          $replacements[$original] = format_date($node->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $node->getOwner()), $options);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, array('date' => $node->getCreatedTime()), $options);
    }
  }

  return $replacements;
}

/**
 * Alter replacement values for placeholder tokens.
 *
 * @param $replacements
 *   An associative array of replacements returned by hook_tokens().
 * @param $context
 *   The context in which hook_tokens() was called. An associative array with
 *   the following keys, which have the same meaning as the corresponding
 *   parameters of hook_tokens():
 *   - 'type'
 *   - 'tokens'
 *   - 'data'
 *   - 'options'
 *
 * @see hook_tokens()
 */
function hook_tokens_alter(array &$replacements, array $context) {
  $options = $context['options'];

  if (isset($options['langcode'])) {
    $url_options['language'] = language_load($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  if ($context['type'] == 'node' && !empty($context['data']['node'])) {
    $node = $context['data']['node'];

    // Alter the [node:title] token, and replace it with the rendered content
    // of a field (field_title).
    if (isset($context['tokens']['title'])) {
      $title = $node->field_title->view('default');
      $replacements[$context['tokens']['title']] = drupal_render($title);
    }
  }
}

/**
 * Provide information about available placeholder tokens and token types.
 *
 * Tokens are placeholders that can be put into text by using the syntax
 * [type:token], where type is the machine-readable name of a token type, and
 * token is the machine-readable name of a token within this group. This hook
 * provides a list of types and tokens to be displayed on text editing screens,
 * so that people editing text can see what their token options are.
 *
 * The actual token replacement is done by
 * \Drupal\Core\Utility\Token::replace(), which invokes hook_tokens(). Your
 * module will need to implement that hook in order to generate token
 * replacements from the tokens defined here.
 *
 * @return
 *   An associative array of available tokens and token types. The outer array
 *   has two components:
 *   - types: An associative array of token types (groups). Each token type is
 *     an associative array with the following components:
 *     - name: The translated human-readable short name of the token type.
 *     - description (optional): A translated longer description of the token
 *       type.
 *     - needs-data: The type of data that must be provided to
 *       \Drupal\Core\Utility\Token::replace() in the $data argument (i.e., the
 *       key name in $data) in order for tokens of this type to be used in the
 *       $text being processed. For instance, if the token needs a node object,
 *       'needs-data' should be 'node', and to use this token in
 *       \Drupal\Core\Utility\Token::replace(), the caller needs to supply a
 *       node object as $data['node']. Some token data can also be supplied
 *       indirectly; for instance, a node object in $data supplies a user object
 *       (the author of the node), allowing user tokens to be used when only
 *       a node data object is supplied.
 *   - tokens: An associative array of tokens. The outer array is keyed by the
 *     group name (the same key as in the types array). Within each group of
 *     tokens, each token item is keyed by the machine name of the token, and
 *     each token item has the following components:
 *     - name: The translated human-readable short name of the token.
 *     - description (optional): A translated longer description of the token.
 *     - type (optional): A 'needs-data' data type supplied by this token, which
 *       should match a 'needs-data' value from another token type. For example,
 *       the node author token provides a user object, which can then be used
 *       for token replacement data in \Drupal\Core\Utility\Token::replace()
 *       without having to supply a separate user object.
 *
 * @see hook_token_info_alter()
 * @see hook_tokens()
 */
function hook_token_info() {
  $type = array(
    'name' => t('Nodes'),
    'description' => t('Tokens related to individual nodes.'),
    'needs-data' => 'node',
  );

  // Core tokens for nodes.
  $node['nid'] = array(
    'name' => t("Node ID"),
    'description' => t("The unique ID of the node."),
  );
  $node['title'] = array(
    'name' => t("Title"),
  );
  $node['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the node's edit page."),
  );

  // Chained tokens for nodes.
  $node['created'] = array(
    'name' => t("Date created"),
    'type' => 'date',
  );
  $node['author'] = array(
    'name' => t("Author"),
    'type' => 'user',
  );

  return array(
    'types' => array('node' => $type),
    'tokens' => array('node' => $node),
  );
}

/**
 * Alter the metadata about available placeholder tokens and token types.
 *
 * @param $data
 *   The associative array of token definitions from hook_token_info().
 *
 * @see hook_token_info()
 */
function hook_token_info_alter(&$data) {
  // Modify description of node tokens for our site.
  $data['tokens']['node']['nid'] = array(
    'name' => t("Node ID"),
    'description' => t("The unique ID of the article."),
  );
  $data['tokens']['node']['title'] = array(
    'name' => t("Title"),
    'description' => t("The title of the article."),
  );

  // Chained tokens for nodes.
  $data['tokens']['node']['created'] = array(
    'name' => t("Date created"),
    'description' => t("The date the article was posted."),
    'type' => 'date',
  );
}

/**
 * Alter batch information before a batch is processed.
 *
 * Called by batch_process() to allow modules to alter a batch before it is
 * processed.
 *
 * @param $batch
 *   The associative array of batch information. See batch_set() for details on
 *   what this could contain.
 *
 * @see batch_set()
 * @see batch_process()
 *
 * @ingroup batch
 */
function hook_batch_alter(&$batch) {
}

/**
 * Alter the default country list.
 *
 * @param $countries
 *   The associative array of countries keyed by two-letter country code.
 *
 * @see \Drupal\Core\Locale\CountryManager::getList().
 */
function hook_countries_alter(&$countries) {
  // Elbonia is now independent, so add it to the country list.
  $countries['EB'] = 'Elbonia';
}

/**
 * Register information about FileTransfer classes provided by a module.
 *
 * The FileTransfer class allows transferring files over a specific type of
 * connection. Core provides classes for FTP and SSH. Contributed modules are
 * free to extend the FileTransfer base class to add other connection types,
 * and if these classes are registered via hook_filetransfer_info(), those
 * connection types will be available to site administrators using the Update
 * manager when they are redirected to the authorize.php script to authorize
 * the file operations.
 *
 * @return array
 *   Nested array of information about FileTransfer classes. Each key is a
 *   FileTransfer type (not human readable, used for form elements and
 *   variable names, etc), and the values are subarrays that define properties
 *   of that type. The keys in each subarray are:
 *   - 'title': Required. The human-readable name of the connection type.
 *   - 'class': Required. The name of the FileTransfer class. The constructor
 *     will always be passed the full path to the root of the site that should
 *     be used to restrict where file transfer operations can occur (the $jail)
 *     and an array of settings values returned by the settings form.
 *   - 'file': Required. The include file containing the FileTransfer class.
 *     This should be a separate .inc file, not just the .module file, so that
 *     the minimum possible code is loaded when authorize.php is running.
 *   - 'file path': Optional. The directory (relative to the Drupal root)
 *     where the include file lives. If not defined, defaults to the base
 *     directory of the module implementing the hook.
 *   - 'weight': Optional. Integer weight used for sorting connection types on
 *     the authorize.php form.
 *
 * @see \Drupal\Core\FileTransfer\FileTransfer
 * @see authorize.php
 * @see hook_filetransfer_info_alter()
 * @see drupal_get_filetransfer_info()
 */
function hook_filetransfer_info() {
  $info['sftp'] = array(
    'title' => t('SFTP (Secure FTP)'),
    'class' => 'Drupal\Core\FileTransfer\SFTP',
    'weight' => 10,
  );
  return $info;
}

/**
 * Alter the FileTransfer class registry.
 *
 * @param array $filetransfer_info
 *   Reference to a nested array containing information about the FileTransfer
 *   class registry.
 *
 * @see hook_filetransfer_info()
 */
function hook_filetransfer_info_alter(&$filetransfer_info) {
  // Remove the FTP option entirely.
  unset($filetransfer_info['ftp']);
  // Make sure the SSH option is listed first.
  $filetransfer_info['ssh']['weight'] = -10;
}

/**
 * Alter the parameters for links.
 *
 * @param array $variables
 *   An associative array of variables defining a link. The link may be either a
 *   "route link" using \Drupal\Core\Utility\LinkGenerator::link(), which is
 *   exposed as the 'link_generator' service or a link generated by _l(). If the
 *   link is a "route link", 'route_name' will be set, otherwise 'path' will be
 *   set. The following keys can be altered:
 *   - text: The link text for the anchor tag as a translated string.
 *   - url_is_active: Whether or not the link points to the currently active
 *     URL.
 *   - url: The \Drupal\Core\Url object.
 *   - options: An associative array of additional options that will be passed
 *     to either \Drupal\Core\Routing\UrlGenerator::generateFromPath() or
 *     \Drupal\Core\Routing\UrlGenerator::generateFromRoute() to generate the
 *     href attribute for this link, and also used when generating the link.
 *     Defaults to an empty array. It may contain the following elements:
 *     - 'query': An array of query key/value-pairs (without any URL-encoding) to
 *       append to the URL.
 *     - absolute: Whether to force the output to be an absolute link (beginning
 *       with http:). Useful for links that will be displayed outside the site,
 *       such as in an RSS feed. Defaults to FALSE.
 *     - language: An optional language object. May affect the rendering of
 *       the anchor tag, such as by adding a language prefix to the path.
 *     - attributes: An associative array of HTML attributes to apply to the
 *       anchor tag. If element 'class' is included, it must be an array; 'title'
 *       must be a string; other elements are more flexible, as they just need
 *       to work as an argument for the constructor of the class
 *       Drupal\Core\Template\Attribute($options['attributes']).
 *     - html: Whether or not HTML should be allowed as the link text. If FALSE,
 *       the text will be run through
 *       \Drupal\Component\Utility\String::checkPlain() before being output.
 *
 * @see \Drupal\Core\Routing\UrlGenerator::generateFromPath()
 * @see \Drupal\Core\Routing\UrlGenerator::generateFromRoute()
 */
function hook_link_alter(&$variables) {
  // Add a warning to the end of route links to the admin section.
  if (isset($variables['route_name']) && strpos($variables['route_name'], 'admin') !== FALSE) {
    $variables['text'] .= ' (Warning!)';
  }
}

/**
 * Alter the configuration synchronization steps.
 *
 * @param array $sync_steps
 *   A one-dimensional array of \Drupal\Core\Config\ConfigImporter method names
 *   or callables that are invoked to complete the import, in the order that
 *   they will be processed. Each callable item defined in $sync_steps should
 *   either be a global function or a public static method. The callable should
 *   accept a $context array by reference. For example:
 *   <code>
 *     function _additional_configuration_step(&$context) {
 *       // Do stuff.
 *       // If finished set $context['finished'] = 1.
 *     }
 *   </code>
 *   For more information on creating batches, see the
 *   @link batch Batch operations @endlink documentation.
 *
 * @see callback_batch_operation()
 * @see \Drupal\Core\Config\ConfigImporter::initialize()
 */
function hook_config_import_steps_alter(&$sync_steps, \Drupal\Core\Config\ConfigImporter $config_importer) {
  $deletes = $config_importer->getUnprocessedConfiguration('delete');
  if (isset($deletes['field.storage.node.body'])) {
    $sync_steps[] = '_additional_configuration_step';
  }
}

/**
 * Alter config typed data definitions.
 *
 * For example you can alter the typed data types representing each
 * configuration schema type to change default labels or form element renderers
 * used for configuration translation.
 *
 * It is strongly advised not to use this hook to add new data types or to
 * change the structure of existing ones. Keep in mind that there are tools
 * that may use the configuration schema for static analysis of configuration
 * files, like the string extractor for the localization system. Such systems
 * won't work with dynamically defined configuration schemas.
 *
 * For adding new data types use configuration schema YAML files instead.
 *
 * @param $definitions
 *   Associative array of configuration type definitions keyed by schema type
 *   names. The elements are themselves array with information about the type.
 */
function hook_config_schema_info_alter(&$definitions) {
  // Enhance the text and date type definitions with classes to generate proper
  // form elements in ConfigTranslationFormBase. Other translatable types will
  // appear as a one line textfield.
  $definitions['text']['form_element_class'] = '\Drupal\config_translation\FormElement\Textarea';
  $definitions['date_format']['form_element_class'] = '\Drupal\config_translation\FormElement\DateFormat';
}

/**
 * @} End of "addtogroup hooks".
 */
