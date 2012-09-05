<?php

/**
 * @file
 * Definition of Drupal\views\View.
 */

namespace Drupal\views;

use Symfony\Component\HttpFoundation\Response;

/**
 * @defgroup views_objects Objects that represent a View or part of a view
 * @{
 * These objects are the core of Views do the bulk of the direction and
 * storing of data. All database activity is in these objects.
 */

/**
 * An object to contain all of the data to generate a view, plus the member
 * functions to build the view query, execute the query and render the output.
 */
class View extends ViewStorage {

  /**
   * The name of the base table this view will use.
   *
   * @var string
   */
  public $base_table = 'node';

  /**
   * The name of the base field to use.
   *
   * @var string
   */
  public $base_field = 'nid';

  /**
   * The name of the view.
   *
   * @var string
   */
  public $name = '';

  /**
   * The description of the view, which is used only in the interface.
   *
   * @var string
   */
  public $description = '';

  /**
   * The "tags" of a view.
   *
   * The tags are stored as a single string, though it is used as multiple tags
   * for example in the views overview.
   *
   * @var string
   */
  public $tag = '';

  /**
   * The human readable name of the view.
   *
   * @var string
   */
  public $human_name = '';

  /**
   * The core version the view was created for.
   *
   * @var int
   */
  public $core = DRUPAL_CORE_COMPATIBILITY;

  /**
   * The views API version this view was created by.
   *
   * @var string
   */
  public $api_version = VIEWS_API_VERSION;

  /**
   * Returns whether the view's status is disabled or not.
   *
   * This value is used for exported view, to provide some default views which
   * aren't enabled.
   *
   * @var bool
   */
  public $disabled = FALSE;

  // State variables

  /**
   * Whether or not the view has been built.
   *
   * @var bool
   */
  public $built = FALSE;

  /**
   * Whether the view has been executed/query has been run.
   *
   * @var bool
   */
  public $executed = FALSE;

  /**
   * Indicates if a view is currently being edited.
   *
   * @var bool
   */
  public $editing = FALSE;

  /**
   * Any arguments that have been passed into the view.
   *
   * @var array
   */
  public $args = array();

  /**
   * An array of build info.
   *
   * @var array
   */
  public $build_info = array();

  /**
   * Whether this view uses AJAX.
   *
   * @var bool
   */
  public $use_ajax = FALSE;

  /**
   * Where the results of a query will go.
   *
   * The array must use a numeric index starting at 0.
   *
   * @var array
   */
  public $result = array();

  // May be used to override the current pager info.

  /**
   * The current page. If the view uses pagination.
   *
   * @var int
   */
  public $current_page = NULL;

  /**
   * The number of items per page.
   *
   * @var int
   */
  public $items_per_page = NULL;

  /**
   * The pager offset.
   *
   * @var int
   */
  public $offset = NULL;

  /**
   * The total number of rows returned from the query.
   *
   * @var array
   */
  public $total_rows = NULL;

  /**
   * Rendered attachments to place before the view.
   *
   * @var string
   */
  public $attachment_before = '';

  /**
   * Rendered attachements to place after the view.
   *
   * @var string
   */
  public $attachment_after = '';

  // Exposed widget input

  /**
   * All the form data from $form_state['values'].
   *
   * @var array
   */
  public $exposed_data = array();

  /**
   * An array of input values from exposed forms.
   *
   * @var array
   */
  public $exposed_input = array();

  /**
   * Exposed widget input directly from the $form_state['values'].
   *
   * @var array
   */
  public $exposed_raw_input = array();

  /**
   * Used to store views that were previously running if we recurse.
   *
   * @var array
   */
  public $old_view = array();

  /**
   * To avoid recursion in views embedded into areas.
   *
   * @var array
   */
  public $parent_views = array();

  /**
   * Whether this view is an attachment to another view.
   *
   * @var bool
   */
  public $is_attachment = NULL;

  // Stores the next steps of form items to handle.
  // It's an array of stack items, which contain the form id, the type of form,
  // the view, the display and some additional arguments.
  // @see views_ui_add_form_to_stack()
  // var $stack;

  /**
   * Identifier of the current display.
   *
   * @var string
   */
  public $current_display;

  /**
   * Where the $query object will reside.
   *
   * @var Drupal\views\Plugin\query\QueryInterface
   */
  public $query = NULL;

  /**
   * The used pager plugin used by the current executed view.
   *
   * @var Drupal\views\Plugin\views\pager\PagerPluginBase
   */
  public $pager = NULL;

  /**
   * The current used display plugin.
   *
   * @var Drupal\views\Plugin\views\display\DisplayPluginBase
   */
  public $display_handler;

  /**
   * Stores all display handlers of this view.
   *
   * An array containing Drupal\views\Plugin\views\display\DisplayPluginBase
   * objects.
   *
   * @var array
   */
  public $display;

  /**
   * The current used style plugin.
   *
   * @var Drupal\views\Plugin\views\style\StylePluginBase
   */
  public $style_plugin;

  /**
   * Stored the changed options of the style plugin.
   *
   * @deprecated Better use $view->style_plugin->options
   * @var array
   */
  public $style_options;

  /**
   * Stores the current active row while rendering.
   *
   * @var int
   */
  public $row_index;

   /**
   * Allow to override the url of the current view.
   *
   * @var string
   */
  public $override_url = NULL;

  /**
   * Allow to override the path used for generated urls.
   *
   * @var string
   */
  public $override_path = NULL;

  /**
   * Allow to override the used database which is used for this query.
   *
   * @var bool
   */
  public $base_database = NULL;

  // Handlers which are active on this view.

  /**
   * Stores the field handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\field\FieldPluginBase
   * objects.
   *
   * @var array
   */
  public $field;

  /**
   * Stores the argument handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\argument\ArgumentPluginBase
   * objects.
   *
   * @var array
   */
  public $argument;

  /**
   * Stores the sort handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\sort\SortPluginBase objects.
   *
   * @var array
   */
  public $sort;

  /**
   * Stores the filter handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\filter\FilterPluginBase
   * objects.
   *
   * @var array
   */
  public $filter;

  /**
   * Stores the relationship handlers which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\relationship\RelationshipPluginBase
   * objects.
   *
   * @var array
   */
  public $relationship;

  /**
   * Stores the area handlers for the header which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $header;

  /**
   * Stores the area handlers for the footer which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $footer;

  /**
   * Stores the area handlers for the empty text which are initialized on this view.
   *
   * An array containing Drupal\views\Plugin\views\area\AreaPluginBase objects.
   *
   * @var array
   */
  public $empty;

  /**
   * Stores the current response object.
   *
   * @var Symfony\Component\HttpFoundation\Response
   */
  protected $response = NULL;

  /**
   * Perform automatic updates when loading or importing a view.
   *
   * Over time, some things about Views or Drupal data has changed.
   * this attempts to do some automatic updates that must happen
   * to ensure older views will at least try to work.
   */
  public function update() {
    // When views are converted automatically the base_table should be renamed
    // to have a working query.
    $this->base_table = views_move_table($this->base_table);
  }


  /**
   * Returns a list of the sub-object types used by this view. These types are
   * stored on the display, and are used in the build process.
   */
  public function displayObjects() {
    return array('argument', 'field', 'sort', 'filter', 'relationship', 'header', 'footer', 'empty');
  }

  /**
   * Set the arguments that come to this view. Usually from the URL
   * but possibly from elsewhere.
   */
  public function setArguments($args) {
    $this->args = $args;
  }

  /**
   * Change/Set the current page for the pager.
   */
  public function setCurrentPage($page) {
    $this->current_page = $page;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->set_current_page($page);
    }
  }

  /**
   * Get the current page from the pager.
   */
  public function getCurrentPage() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->get_current_page();
    }

    if (isset($this->current_page)) {
      return $this->current_page;
    }
  }

  /**
   * Get the items per page from the pager.
   */
  public function getItemsPerPage() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->get_items_per_page();
    }

    if (isset($this->items_per_page)) {
      return $this->items_per_page;
    }
  }

  /**
   * Set the items per page on the pager.
   */
  public function setItemsPerPage($items_per_page) {
    $this->items_per_page = $items_per_page;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      $this->pager->set_items_per_page($items_per_page);
    }
  }

  /**
   * Get the pager offset from the pager.
   */
  public function getOffset() {
    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      return $this->pager->get_offset();
    }

    if (isset($this->offset)) {
      return $this->offset;
    }
  }

  /**
   * Set the offset on the pager.
   */
  public function setOffset($offset) {
    $this->offset = $offset;

    // If the pager is already initialized, pass it through to the pager.
    if (!empty($this->pager)) {
      $this->pager->set_offset($offset);
    }
  }

  /**
   * Determine if the pager actually uses a pager.
   */
  public function usePager() {
    if (!empty($this->pager)) {
      return $this->pager->use_pager();
    }
  }

  /**
   * Whether or not AJAX should be used. If AJAX is used, paging,
   * tablesorting and exposed filters will be fetched via an AJAX call
   * rather than a page refresh.
   */
  public function setUseAJAX($use_ajax) {
    $this->use_ajax = $use_ajax;
  }

  /**
   * Set the exposed filters input to an array. If unset they will be taken
   * from $_GET when the time comes.
   */
  public function setExposedInput($filters) {
    $this->exposed_input = $filters;
  }

  /**
   * Figure out what the exposed input for this view is.
   */
  public function getExposedInput() {
    // Fill our input either from $_GET or from something previously set on the
    // view.
    if (empty($this->exposed_input)) {
      $this->exposed_input = drupal_container()->get('request')->query->all();
      // unset items that are definitely not our input:
      foreach (array('page', 'q') as $key) {
        if (isset($this->exposed_input[$key])) {
          unset($this->exposed_input[$key]);
        }
      }

      // If we have no input at all, check for remembered input via session.

      // If filters are not overridden, store the 'remember' settings on the
      // default display. If they are, store them on this display. This way,
      // multiple displays in the same view can share the same filters and
      // remember settings.
      $display_id = ($this->display_handler->isDefaulted('filters')) ? 'default' : $this->current_display;

      if (empty($this->exposed_input) && !empty($_SESSION['views'][$this->name][$display_id])) {
        $this->exposed_input = $_SESSION['views'][$this->name][$display_id];
      }
    }

    return $this->exposed_input;
  }

  /**
   * Set the display for this view and initialize the display handler.
   */
  public function initDisplay($reset = FALSE) {
    // The default display is always the first one in the list.
    if (isset($this->current_display)) {
      return TRUE;
    }

    // Instantiate all displays
    foreach (array_keys($this->display) as $id) {
      // Correct for shallow cloning
      // Often we'll have a cloned view so we don't mess up each other's
      // displays, but the clone is pretty shallow and doesn't necessarily
      // clone the displays. We can tell this by looking to see if a handler
      // has already been set; if it has, but $this->current_display is not
      // set, then something is dreadfully wrong.
      if (!empty($this->display[$id]->handler)) {
        $this->display[$id] = clone $this->display[$id];
        unset($this->display[$id]->handler);
      }
      $this->display[$id]->handler = views_get_plugin('display', $this->display[$id]->display_plugin);
      if (!empty($this->display[$id]->handler)) {
        $this->display[$id]->handler->localization_keys = array($id);
        // Initialize the new display handler with data.
        $this->display[$id]->handler->init($this, $this->display[$id]);
        // If this is NOT the default display handler, let it know which is
        // since it may well utilize some data from the default.
        // This assumes that the 'default' handler is always first. It always
        // is. Make sure of it.
        if ($id != 'default') {
          $this->display[$id]->handler->default_display = &$this->display['default']->handler;
        }
      }
    }

    $this->current_display = 'default';
    $this->display_handler = &$this->display['default']->handler;

    return TRUE;
  }

  /**
   * Get the first display that is accessible to the user.
   *
   * @param $displays
   *   Either a single display id or an array of display ids.
   */
  public function chooseDisplay($displays) {
    if (!is_array($displays)) {
      return $displays;
    }

    $this->initDisplay();

    foreach ($displays as $display_id) {
      if ($this->display[$display_id]->handler->access()) {
        return $display_id;
      }
    }

    return 'default';
  }

  /**
   * Set the display as current.
   *
   * @param $display_id
   *   The id of the display to mark as current.
   */
  public function setDisplay($display_id = NULL) {
    // If we have not already initialized the display, do so. But be careful.
    if (empty($this->current_display)) {
      $this->initDisplay();

      // If handlers were not initialized, and no argument was sent, set up
      // to the default display.
      if (empty($display_id)) {
        $display_id = 'default';
      }
    }

    $display_id = $this->chooseDisplay($display_id);

    // If no display id sent in and one wasn't chosen above, we're finished.
    if (empty($display_id)) {
      return FALSE;
    }

    // Ensure the requested display exists.
    if (empty($this->display[$display_id])) {
      $display_id = 'default';
      if (empty($this->display[$display_id])) {
        debug('set_display() called with invalid display ID @display.', array('@display' => $display_id));
        return FALSE;
      }
    }

    // Set the current display.
    $this->current_display = $display_id;

    // Ensure requested display has a working handler.
    if (empty($this->display[$display_id]->handler)) {
      return FALSE;
    }

    // Set a shortcut
    $this->display_handler = &$this->display[$display_id]->handler;

    return TRUE;
  }

  /**
   * Find and initialize the style plugin.
   *
   * Note that arguments may have changed which style plugin we use, so
   * check the view object first, then ask the display handler.
   */
  public function initStyle() {
    if (isset($this->style_plugin)) {
      return is_object($this->style_plugin);
    }

    if (!isset($this->plugin_name)) {
      $this->plugin_name = $this->display_handler->getOption('style_plugin');
      $this->style_options = $this->display_handler->getOption('style_options');
    }

    $this->style_plugin = views_get_plugin('style', $this->plugin_name);

    if (empty($this->style_plugin)) {
      return FALSE;
    }

    // init the new style handler with data.
    $this->style_plugin->init($this, $this->display[$this->current_display], $this->style_options);
    return TRUE;
  }

  /**
   * Attempt to discover if the view has handlers missing relationships.
   *
   * This will try to add relationships automatically if it can, and will
   * remove the handlers if it cannot.
   */
  public function fixMissingRelationships() {
    if (isset($this->relationships_fixed)) {
      return;
    }

    $this->relationships_fixed = TRUE;

    // Go through all of our handler types and test them to see if they
    // are missing relationships. Missing relationships can cause fatally
    // broken Views.
    $base_tables = array(
      $this->base_table => TRUE,
      '#global' => TRUE,
    );

    // For each relationship we have, make sure we mark the base it provides as
    // available.
    foreach ($this->display_handler->getOption('relationships') as $id => $options) {
      $options['table'] = views_move_table($options['table']);
      $data = views_fetch_data($options['table'], FALSE);
      if (isset($data[$options['field']]['relationship']['base'])) {
        $base_tables[$data[$options['field']]['relationship']['base']] = TRUE;
      }
    }

    $base_tables = array_keys($base_tables);
    $missing_base_tables = array();

    $types = View::viewsObjectTypes();
    foreach ($types as $key => $info) {
      foreach ($this->display_handler->getOption($info['plural']) as $id => $options) {
        $options['table'] = views_move_table($options['table']);
        $data = views_fetch_data($options['table'], FALSE);

        $valid_bases = array($options['table']);
        if (isset($data['table']['join'])) {
          $valid_bases = array_merge($valid_bases, array_keys($data['table']['join']));
        }

        // If the base table is missing, record it so we can try to fix it.
        if (!array_intersect($valid_bases, $base_tables)) {
          $missing_base_tables[$options['table']][] = array('type' => $key, 'id' => $id);
        }
      }
    }

    if (!empty($missing_base_tables)) {
      // This will change handlers, so make sure any existing handlers get
      // tossed.
      $this->display_handler->handlers = array();
      $this->relationships_changed = TRUE;
      $this->changed = TRUE;

      // Try to fix it.
      foreach ($missing_base_tables as $table => $handlers) {
        $data = views_fetch_data($table);
        $relationship = NULL;

        // Does the missing base table have a default relationship we can
        // throw in?
        if (isset($data['table']['default_relationship'][$this->base_table])) {
          // Create the relationship.
          $info = $data['table']['default_relationship'][$this->base_table];

          $relationship_options = isset($info['options']) ? $info['options'] : array();
          $relationship = $this->addItem($this->current_display, 'relationship', $info['table'], $info['field'], $relationship_options);
        }
        foreach ($handlers as $handler) {
          $options = $this->display_handler->getOption($types[$handler['type']]['plural']);
          if ($relationship) {
            $options[$handler['id']]['relationship'] = $relationship;
          }
          else {
            unset($options[$handler['id']]);
          }
          $this->display_handler->setOption($types[$handler['type']]['plural'], $options);
        }
      }
    }
  }

  /**
   * Acquire and attach all of the handlers.
   */
  public function initHandlers() {
    if (empty($this->inited)) {
      $this->fixMissingRelationships();
      foreach (View::viewsObjectTypes() as $key => $info) {
        $this->_initHandler($key, $info);
      }
      $this->inited = TRUE;
    }
  }

  /**
   * Initialize the pager
   *
   * Like style initialization, pager initialization is held until late
   * to allow for overrides.
   */
  public function initPager() {
    if (!isset($this->pager)) {
      $this->pager = $this->display_handler->getPlugin('pager');

      if ($this->pager->use_pager()) {
        $this->pager->set_current_page($this->current_page);
      }

      // These overrides may have been set earlier via $view->set_*
      // functions.
      if (isset($this->items_per_page)) {
        $this->pager->setItemsPerPage($this->items_per_page);
      }

      if (isset($this->offset)) {
        $this->pager->set_offset($this->offset);
      }
    }
  }

  /**
   * Render the pager, if necessary.
   */
  public function renderPager($exposed_input) {
    if (!empty($this->pager) && $this->pager->use_pager()) {
      return $this->pager->render($exposed_input);
    }

    return '';
  }

  /**
   * Create a list of base tables eligible for this view. Used primarily
   * for the UI. Display must be already initialized.
   */
  public function getBaseTables() {
    $base_tables = array(
      $this->base_table => TRUE,
      '#global' => TRUE,
    );

    foreach ($this->display_handler->getHandlers('relationship') as $handler) {
      $base_tables[$handler->definition['base']] = TRUE;
    }
    return $base_tables;
  }

  /**
   * Run the preQuery() on all active handlers.
   */
  protected function _preQuery() {
    foreach (View::viewsObjectTypes() as $key => $info) {
      $handlers = &$this->$key;
      $position = 0;
      foreach ($handlers as $id => $handler) {
        $handlers[$id]->position = $position;
        $handlers[$id]->preQuery();
        $position++;
      }
    }
  }

  /**
   * Run the postExecute() on all active handlers.
   */
  protected function _postExecute() {
    foreach (View::viewsObjectTypes() as $key => $info) {
      $handlers = &$this->$key;
      foreach ($handlers as $id => $handler) {
        $handlers[$id]->postExecute($this->result);
      }
    }
  }

  /**
   * Attach all of the handlers for each type.
   *
   * @param $key
   *   One of 'argument', 'field', 'sort', 'filter', 'relationship'
   * @param $info
   *   The $info from viewsObjectTypes for this object.
   */
  protected function _initHandler($key, $info) {
    // Load the requested items from the display onto the object.
    $this->$key = $this->display_handler->getHandlers($key);

    // This reference deals with difficult PHP indirection.
    $handlers = &$this->$key;

    // Run through and test for accessibility.
    foreach ($handlers as $id => $handler) {
      if (!$handler->access()) {
        unset($handlers[$id]);
      }
    }
  }

  /**
   * Build all the arguments.
   */
  protected function _buildArguments() {
    // Initially, we want to build sorts and fields. This can change, though,
    // if we get a summary view.
    if (empty($this->argument)) {
      return TRUE;
    }

    // build arguments.
    $position = -1;

    // Create a title for use in the breadcrumb trail.
    $title = $this->display_handler->getOption('title');

    $this->build_info['breadcrumb'] = array();
    $breadcrumb_args = array();
    $substitutions = array();

    $status = TRUE;

    // Iterate through each argument and process.
    foreach ($this->argument as $id => $arg) {
      $position++;
      $argument = &$this->argument[$id];

      if ($argument->broken()) {
        continue;
      }

      $argument->setRelationship();

      $arg = isset($this->args[$position]) ? $this->args[$position] : NULL;
      $argument->position = $position;

      if (isset($arg) || $argument->has_default_argument()) {
        if (!isset($arg)) {
          $arg = $argument->get_default_argument();
          // make sure default args get put back.
          if (isset($arg)) {
            $this->args[$position] = $arg;
          }
          // remember that this argument was computed, not passed on the URL.
          $argument->is_default = TRUE;
        }

        // Set the argument, which will also validate that the argument can be set.
        if (!$argument->set_argument($arg)) {
          $status = $argument->validateFail($arg);
          break;
        }

        if ($argument->is_exception()) {
          $arg_title = $argument->exception_title();
        }
        else {
          $arg_title = $argument->get_title();
          $argument->query($this->display_handler->useGroupBy());
        }

        // Add this argument's substitution
        $substitutions['%' . ($position + 1)] = $arg_title;
        $substitutions['!' . ($position + 1)] = strip_tags(decode_entities($arg));

        // Since we're really generating the breadcrumb for the item above us,
        // check the default action of this argument.
        if ($this->display_handler->usesBreadcrumb() && $argument->uses_breadcrumb()) {
          $path = $this->getUrl($breadcrumb_args);
          if (strpos($path, '%') === FALSE) {
            if (!empty($argument->options['breadcrumb_enable']) && !empty($argument->options['breadcrumb'])) {
              $breadcrumb = $argument->options['breadcrumb'];
            }
            else {
              $breadcrumb = $title;
            }
            $this->build_info['breadcrumb'][$path] = str_replace(array_keys($substitutions), $substitutions, $breadcrumb);
          }
        }

        // Allow the argument to muck with this breadcrumb.
        $argument->set_breadcrumb($this->build_info['breadcrumb']);

        // Test to see if we should use this argument's title
        if (!empty($argument->options['title_enable']) && !empty($argument->options['title'])) {
          $title = $argument->options['title'];
        }

        $breadcrumb_args[] = $arg;
      }
      else {
        // determine default condition and handle.
        $status = $argument->default_action();
        break;
      }

      // Be safe with references and loops:
      unset($argument);
    }

    // set the title in the build info.
    if (!empty($title)) {
      $this->build_info['title'] = $title;
    }

    // Store the arguments for later use.
    $this->build_info['substitutions'] = $substitutions;

    return $status;
  }

  /**
   * Do some common building initialization.
   */
  public function initQuery() {
    if (!empty($this->query)) {
      $class = get_class($this->query);
      if ($class && $class != 'stdClass') {
        // return if query is already initialized.
        return TRUE;
      }
    }

    // Create and initialize the query object.
    $views_data = views_fetch_data($this->base_table);
    $this->base_field = !empty($views_data['table']['base']['field']) ? $views_data['table']['base']['field'] : '';
    if (!empty($views_data['table']['base']['database'])) {
      $this->base_database = $views_data['table']['base']['database'];
    }

    // Load the options.
    $query_options = $this->display_handler->getOption('query');

    // Create and initialize the query object.
    $plugin = !empty($views_data['table']['base']['query class']) ? $views_data['table']['base']['query class'] : 'views_query';
    $this->query = views_get_plugin_instance('query', $plugin);

    if (empty($this->query)) {
      return FALSE;
    }

    $this->query->init($this->base_table, $this->base_field, $query_options['options']);
    return TRUE;
  }

  /**
   * Build the query for the view.
   */
  public function build($display_id = NULL) {
    if (!empty($this->built)) {
      return;
    }

    if (empty($this->current_display) || $display_id) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    // Let modules modify the view just prior to building it.
    foreach (module_implements('views_pre_build') as $module) {
      $function = $module . '_views_pre_build';
      $function($this);
    }

    // Attempt to load from cache.
    // @todo Load a build_info from cache.

    $start = microtime(TRUE);
    // If that fails, let's build!
    $this->build_info = array(
      'query' => '',
      'count_query' => '',
      'query_args' => array(),
    );

    $this->initQuery();

    // Call a module hook and see if it wants to present us with a
    // pre-built query or instruct us not to build the query for
    // some reason.
    // @todo: Implement this. Use the same mechanism Panels uses.

    // Run through our handlers and ensure they have necessary information.
    $this->initHandlers();

    // Let the handlers interact with each other if they really want.
    $this->_preQuery();

    if ($this->display_handler->usesExposed()) {
      $exposed_form = $this->display_handler->getPlugin('exposed_form');
      $this->exposed_widgets = $exposed_form->render_exposed_form();
      if (form_set_error() || !empty($this->build_info['abort'])) {
        $this->built = TRUE;
        // Don't execute the query, but rendering will still be executed to display the empty text.
        $this->executed = TRUE;
        return empty($this->build_info['fail']);
      }
    }

    // Build all the relationships first thing.
    $this->_build('relationship');

    // Set the filtering groups.
    if (!empty($this->filter)) {
      $filter_groups = $this->display_handler->getOption('filter_groups');
      if ($filter_groups) {
        $this->query->set_group_operator($filter_groups['operator']);
        foreach ($filter_groups['groups'] as $id => $operator) {
          $this->query->set_where_group($operator, $id);
        }
      }
    }

    // Build all the filters.
    $this->_build('filter');

    $this->build_sort = TRUE;

    // Arguments can, in fact, cause this whole thing to abort.
    if (!$this->_buildArguments()) {
      $this->build_time = microtime(TRUE) - $start;
      $this->attachDisplays();
      return $this->built;
    }

    // Initialize the style; arguments may have changed which style we use,
    // so waiting as long as possible is important. But we need to know
    // about the style when we go to build fields.
    if (!$this->initStyle()) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    if ($this->style_plugin->usesFields()) {
      $this->_build('field');
    }

    // Build our sort criteria if we were instructed to do so.
    if (!empty($this->build_sort)) {
      // Allow the style handler to deal with sorting.
      if ($this->style_plugin->build_sort()) {
        $this->_build('sort');
      }
      // allow the plugin to build second sorts as well.
      $this->style_plugin->build_sort_post();
    }

    // Allow area handlers to affect the query.
    $this->_build('header');
    $this->_build('footer');
    $this->_build('empty');

    // Allow display handler to affect the query:
    $this->display_handler->query($this->display_handler->useGroupBy());

    // Allow style handler to affect the query:
    $this->style_plugin->query($this->display_handler->useGroupBy());

    // Allow exposed form to affect the query:
    if (isset($exposed_form)) {
      $exposed_form->query();
    }

    if (config('views.settings')->get('sql_signature')) {
      $this->query->add_signature($this);
    }

    // Let modules modify the query just prior to finalizing it.
    $this->query->alter($this);

    // Only build the query if we weren't interrupted.
    if (empty($this->built)) {
      // Build the necessary info to execute the query.
      $this->query->build($this);
    }

    $this->built = TRUE;
    $this->build_time = microtime(TRUE) - $start;

    // Attach displays
    $this->attachDisplays();

    // Let modules modify the view just after building it.
    foreach (module_implements('views_post_build') as $module) {
      $function = $module . '_views_post_build';
      $function($this);
    }

    return TRUE;
  }

  /**
   * Internal method to build an individual set of handlers.
   *
   * @todo Some filter needs this function, even it is internal.
   *
   * @param string $key
   *    The type of handlers (filter etc.) which should be iterated over to
   *    build the relationship and query information.
   */
  public function _build($key) {
    $handlers = &$this->$key;
    foreach ($handlers as $id => $data) {

      if (!empty($handlers[$id]) && is_object($handlers[$id])) {
        $multiple_exposed_input = array(0 => NULL);
        if ($handlers[$id]->multipleExposedInput()) {
          $multiple_exposed_input = $handlers[$id]->group_multiple_exposed_input($this->exposed_data);
        }
        foreach ($multiple_exposed_input as $group_id) {
          // Give this handler access to the exposed filter input.
          if (!empty($this->exposed_data)) {
            $converted = FALSE;
            if ($handlers[$id]->isAGroup()) {
              $converted = $handlers[$id]->convert_exposed_input($this->exposed_data, $group_id);
              $handlers[$id]->store_group_input($this->exposed_data, $converted);
              if (!$converted) {
                continue;
              }
            }
            $rc = $handlers[$id]->acceptExposedInput($this->exposed_data);
            $handlers[$id]->storeExposedInput($this->exposed_data, $rc);
            if (!$rc) {
              continue;
            }
          }
          $handlers[$id]->setRelationship();
          $handlers[$id]->query($this->display_handler->useGroupBy());
        }
      }
    }
  }

  /**
   * Execute the view's query.
   *
   * @param string $display_id
   *   The machine name of the display, which should be executed.
   *
   * @return bool
   *   Return whether the executing was successful, for example an argument
   *   could stop the process.
   */
  public function execute($display_id = NULL) {
    if (empty($this->built)) {
      if (!$this->build($display_id)) {
        return FALSE;
      }
    }

    if (!empty($this->executed)) {
      return TRUE;
    }

    // Don't allow to use deactivated displays, but display them on the live preview.
    if (!$this->display[$this->current_display]->handler->getOption('enabled') && empty($this->live_preview)) {
      $this->build_info['fail'] = TRUE;
      return FALSE;
    }

    // Let modules modify the view just prior to executing it.
    foreach (module_implements('views_pre_execute') as $module) {
      $function = $module . '_views_pre_execute';
      $function($this);
    }

    // Check for already-cached results.
    if (!empty($this->live_preview)) {
      $cache = FALSE;
    }
    else {
      $cache = $this->display_handler->getPlugin('cache');
    }
    if ($cache && $cache->cache_get('results')) {
      if ($this->pager->use_pager()) {
        $this->pager->total_items = $this->total_rows;
        $this->pager->update_page_info();
      }
    }
    else {
      $this->query->execute($this);
      // Enforce the array key rule as documented in
      // views_plugin_query::execute().
      $this->result = array_values($this->result);
      $this->_postExecute();
      if ($cache) {
        $cache->cache_set('results');
      }
    }

    // Let modules modify the view just after executing it.
    foreach (module_implements('views_post_execute') as $module) {
      $function = $module . '_views_post_execute';
      $function($this);
    }

    $this->executed = TRUE;
  }

  /**
   * Render this view for a certain display.
   *
   * Note: You should better use just the preview function if you want to
   * render a view.
   *
   * @param string $display_id
   *   The machine name of the display, which should be rendered.
   *
   * @return (string|NULL)
   *   Return the output of the rendered view or NULL if something failed in the process.
   */
  public function render($display_id = NULL) {
    $this->execute($display_id);

    // Check to see if the build failed.
    if (!empty($this->build_info['fail'])) {
      return;
    }
    if (!empty($this->view->build_info['denied'])) {
      return;
    }

    drupal_theme_initialize();
    $config = config('views.settings');

    // Set the response so other parts can alter it.
    $this->response = new Response('', 200);

    $start = microtime(TRUE);
    if (!empty($this->live_preview) && $config->get('ui.show.additional_queries')) {
      $this->startQueryCapture();
    }

    $exposed_form = $this->display_handler->getPlugin('exposed_form');
    $exposed_form->pre_render($this->result);

    // Check for already-cached output.
    if (!empty($this->live_preview)) {
      $cache = FALSE;
    }
    else {
      $cache = $this->display_handler->getPlugin('cache');
    }
    if ($cache && $cache->cache_get('output')) {
    }
    else {
      if ($cache) {
        $cache->cache_start();
      }

      // Run pre_render for the pager as it might change the result.
      if (!empty($this->pager)) {
        $this->pager->pre_render($this->result);
      }

      // Initialize the style plugin.
      $this->initStyle();

      // Give field handlers the opportunity to perform additional queries
      // using the entire resultset prior to rendering.
      if ($this->style_plugin->usesFields()) {
        foreach ($this->field as $id => $handler) {
          if (!empty($this->field[$id])) {
            $this->field[$id]->pre_render($this->result);
          }
        }
      }

      $this->style_plugin->pre_render($this->result);

      // Let modules modify the view just prior to rendering it.
      foreach (module_implements('views_pre_render') as $module) {
        $function = $module . '_views_pre_render';
        $function($this);
      }

      // Let the themes play too, because pre render is a very themey thing.
      foreach ($GLOBALS['base_theme_info'] as $base) {
        $function = $base->name . '_views_pre_render';
        if (function_exists($function)) {
          $function($this);
        }
      }
      $function = $GLOBALS['theme'] . '_views_pre_render';
      if (function_exists($function)) {
        $function($this);
      }

      $this->display_handler->output = $this->display_handler->render();
      if ($cache) {
        $cache->cache_set('output');
      }
    }

    $exposed_form->post_render($this->display_handler->output);

    if ($cache) {
      $cache->post_render($this->display_handler->output);
    }

    // Let modules modify the view output after it is rendered.
    foreach (module_implements('views_post_render') as $module) {
      $function = $module . '_views_post_render';
      $function($this, $this->display_handler->output, $cache);
    }

    // Let the themes play too, because post render is a very themey thing.
    foreach ($GLOBALS['base_theme_info'] as $base) {
      $function = $base->name . '_views_post_render';
      if (function_exists($function)) {
        $function($this);
      }
    }
    $function = $GLOBALS['theme'] . '_views_post_render';
    if (function_exists($function)) {
      $function($this, $this->display_handler->output, $cache);
    }

    if (!empty($this->live_preview) && $config->get('ui.show.additional_queries')) {
      $this->endQueryCapture();
    }
    $this->render_time = microtime(TRUE) - $start;

    return $this->display_handler->output;
  }

  /**
   * Render a specific field via the field ID and the row #
   *
   * Note: You might want to use views_plugin_style::render_fields as it
   * caches the output for you.
   *
   * @param string $field
   *   The id of the field to be rendered.
   *
   * @param int $row
   *   The row number in the $view->result which is used for the rendering.
   *
   * @return string
   *   The rendered output of the field.
   */
  public function renderField($field, $row) {
    if (isset($this->field[$field]) && isset($this->result[$row])) {
      return $this->field[$field]->advanced_render($this->result[$row]);
    }
  }

  /**
   * Execute the given display, with the given arguments.
   * To be called externally by whatever mechanism invokes the view,
   * such as a page callback, hook_block, etc.
   *
   * This function should NOT be used by anything external as this
   * returns data in the format specified by the display. It can also
   * have other side effects that are only intended for the 'proper'
   * use of the display, such as setting page titles and breadcrumbs.
   *
   * If you simply want to view the display, use View::preview() instead.
   */
  public function executeDisplay($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || $this->current_display != $this->chooseDisplay($display_id)) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    $this->preExecute($args);

    // Execute the view
    $output = $this->display_handler->execute();

    $this->postExecute();
    return $output;
  }

  /**
   * Preview the given display, with the given arguments.
   *
   * To be called externally, probably by an AJAX handler of some flavor.
   * Can also be called when views are embedded, as this guarantees
   * normalized output.
   */
  public function preview($display_id = NULL, $args = array()) {
    if (empty($this->current_display) || ((!empty($display_id)) && $this->current_display != $display_id)) {
      if (!$this->setDisplay($display_id)) {
        return FALSE;
      }
    }

    $this->preview = TRUE;
    $this->preExecute($args);
    // Preview the view.
    $output = $this->display_handler->preview();

    $this->postExecute();
    return $output;
  }

  /**
   * Run attachments and let the display do what it needs to do prior
   * to running.
   */
  public function preExecute($args = array()) {
    $this->old_view[] = views_get_current_view();
    views_set_current_view($this);
    $display_id = $this->current_display;

    // Prepare the view with the information we have, but only if we were
    // passed arguments, as they may have been set previously.
    if ($args) {
      $this->setArguments($args);
    }

    // Let modules modify the view just prior to executing it.
    foreach (module_implements('views_pre_view') as $module) {
      $function = $module . '_views_pre_view';
      $function($this, $display_id, $this->args);
    }

    // Allow hook_views_pre_view() to set the dom_id, then ensure it is set.
    $this->dom_id = !empty($this->dom_id) ? $this->dom_id : md5($this->name . REQUEST_TIME . rand());

    // Allow the display handler to set up for execution
    $this->display_handler->preExecute();
  }

  /**
   * Unset the current view, mostly.
   */
  public function postExecute() {
    // unset current view so we can be properly destructed later on.
    // Return the previous value in case we're an attachment.

    if ($this->old_view) {
      $old_view = array_pop($this->old_view);
    }

    views_set_current_view(isset($old_view) ? $old_view : FALSE);
  }

  /**
   * Run attachment displays for the view.
   */
  public function attachDisplays() {
    if (!empty($this->is_attachment)) {
      return;
    }

    if (!$this->display_handler->acceptAttachments()) {
      return;
    }

    $this->is_attachment = TRUE;
    // Give other displays an opportunity to attach to the view.
    foreach ($this->display as $id => $display) {
      if (!empty($this->display[$id]->handler)) {
        $this->display[$id]->handler->attachTo($this->current_display);
      }
    }
    $this->is_attachment = FALSE;
  }

  /**
   * Called to get hook_menu() information from the view and the named display handler.
   *
   * @param $display_id
   *   A display id.
   * @param $callbacks
   *   A menu callback array passed from views_menu_alter().
   */
  public function executeHookMenu($display_id = NULL, &$callbacks = array()) {
    // Prepare the view with the information we have.

    // This was probably already called, but it's good to be safe.
    if (!$this->setDisplay($display_id)) {
      return FALSE;
    }

    // Execute the view
    if (isset($this->display_handler)) {
      return $this->display_handler->executeHookMenu($callbacks);
    }
  }

  /**
   * Called to get hook_block information from the view and the
   * named display handler.
   */
  public function executeHookBlockList($display_id = NULL) {
    // Prepare the view with the information we have.

    // This was probably already called, but it's good to be safe.
    if (!$this->setDisplay($display_id)) {
      return FALSE;
    }

    // Execute the view
    if (isset($this->display_handler)) {
      return $this->display_handler->executeHookBlockList();
    }
  }

  /**
   * Determine if the given user has access to the view. Note that
   * this sets the display handler if it hasn't been.
   */
  public function access($displays = NULL, $account = NULL) {
    // Noone should have access to disabled views.
    if (!empty($this->disabled)) {
      return FALSE;
    }

    if (!isset($this->current_display)) {
      $this->initDisplay();
    }

    if (!$account) {
      $account = $GLOBALS['user'];
    }

    // We can't use choose_display() here because that function
    // calls this one.
    $displays = (array)$displays;
    foreach ($displays as $display_id) {
      if (!empty($this->display[$display_id]->handler)) {
        if ($this->display[$display_id]->handler->access($account)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Sets the used response object of the view.
   *
   * @param Symfony\Component\HttpFoundation\Response $response
   *   The response object which should be set.
   */
  public function setResponse(Response $response) {
    $this->response = $response;
  }

  /**
   * Gets the response object used by the view.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   The response object of the view.
   */
  public function getResponse() {
    if (!isset($this->response)) {
      $this->response = new Response();
    }
    return $this->response;
  }

  /**
   * Get the view's current title. This can change depending upon how it
   * was built.
   */
  public function getTitle() {
    if (empty($this->display_handler)) {
      if (!$this->setDisplay('default')) {
        return FALSE;
      }
    }

    // During building, we might find a title override. If so, use it.
    if (!empty($this->build_info['title'])) {
      $title = $this->build_info['title'];
    }
    else {
      $title = $this->display_handler->getOption('title');
    }

    // Allow substitutions from the first row.
    if ($this->initStyle()) {
      $title = $this->style_plugin->tokenize_value($title, 0);
    }
    return $title;
  }

  /**
   * Override the view's current title.
   *
   * The tokens in the title get's replaced before rendering.
   */
  public function setTitle($title) {
    $this->build_info['title'] = $title;
    return TRUE;
  }

  /**
   * Return the human readable name for a view.
   *
   * When a certain view doesn't have a human readable name return the machine readable name.
   */
  public function getHumanName() {
    if (!empty($this->human_name)) {
      $human_name = $this->human_name;
    }
    else {
      $human_name = $this->name;
    }
    return $human_name;
  }

  /**
   * Force the view to build a title.
   */
  public function buildTitle() {
    $this->initDisplay();

    if (empty($this->built)) {
      $this->initQuery();
    }

    $this->initHandlers();

    $this->_buildArguments();
  }

  /**
   * Get the URL for the current view.
   *
   * This URL will be adjusted for arguments.
   */
  public function getUrl($args = NULL, $path = NULL) {
    if (!empty($this->override_url)) {
      return $this->override_url;
    }

    if (!isset($path)) {
      $path = $this->getPath();
    }
    if (!isset($args)) {
      $args = $this->args;

      // Exclude arguments that were computed, not passed on the URL.
      $position = 0;
      if (!empty($this->argument)) {
        foreach ($this->argument as $argument_id => $argument) {
          if (!empty($argument->is_default) && !empty($argument->options['default_argument_skip_url'])) {
            unset($args[$position]);
          }
          $position++;
        }
      }
    }
    // Don't bother working if there's nothing to do:
    if (empty($path) || (empty($args) && strpos($path, '%') === FALSE)) {
      return $path;
    }

    $pieces = array();
    $argument_keys = isset($this->argument) ? array_keys($this->argument) : array();
    $id = current($argument_keys);
    foreach (explode('/', $path) as $piece) {
      if ($piece != '%') {
        $pieces[] = $piece;
      }
      else {
        if (empty($args)) {
          // Try to never put % in a url; use the wildcard instead.
          if ($id && !empty($this->argument[$id]->options['exception']['value'])) {
            $pieces[] = $this->argument[$id]->options['exception']['value'];
          }
          else {
            $pieces[] = '*'; // gotta put something if there just isn't one.
          }

        }
        else {
          $pieces[] = array_shift($args);
        }

        if ($id) {
          $id = next($argument_keys);
        }
      }
    }

    if (!empty($args)) {
      $pieces = array_merge($pieces, $args);
    }
    return implode('/', $pieces);
  }

  /**
   * Get the base path used for this view.
   */
  public function getPath() {
    if (!empty($this->override_path)) {
      return $this->override_path;
    }

    if (empty($this->display_handler)) {
      if (!$this->setDisplay('default')) {
        return FALSE;
      }
    }
    return $this->display_handler->getPath();
  }

  /**
   * Get the breadcrumb used for this view.
   *
   * @param $set
   *   If true, use drupal_set_breadcrumb() to install the breadcrumb.
   */
  public function getBreadcrumb($set = FALSE) {
    // Now that we've built the view, extract the breadcrumb.
    $base = TRUE;
    $breadcrumb = array();

    if (!empty($this->build_info['breadcrumb'])) {
      foreach ($this->build_info['breadcrumb'] as $path => $title) {
        // Check to see if the frontpage is in the breadcrumb trail; if it
        // is, we'll remove that from the actual breadcrumb later.
        if ($path == config('system.site')->get('page.front')) {
          $base = FALSE;
          $title = t('Home');
        }
        if ($title) {
          $breadcrumb[] = l($title, $path, array('html' => TRUE));
        }
      }

      if ($set) {
        if ($base) {
          $breadcrumb = array_merge(drupal_get_breadcrumb(), $breadcrumb);
        }
        drupal_set_breadcrumb($breadcrumb);
      }
    }
    return $breadcrumb;
  }

  /**
   * Set up query capturing.
   *
   * db_query() stores the queries that it runs in global $queries,
   * bit only if dev_query is set to true. In this case, we want
   * to temporarily override that setting if it's not and we
   * can do that without forcing a db rewrite by just manipulating
   * $conf. This is kind of evil but it works.
   */
  public function startQueryCapture() {
    global $conf, $queries;
    if (empty($conf['dev_query'])) {
      $this->fix_dev_query = TRUE;
      $conf['dev_query'] = TRUE;
    }

    // Record the last query key used; anything already run isn't
    // a query that we are interested in.
    $this->last_query_key = NULL;

    if (!empty($queries)) {
      $keys = array_keys($queries);
      $this->last_query_key = array_pop($keys);
    }
  }

  /**
   * Add the list of queries run during render to buildinfo.
   *
   * @see View::start_query_capture()
   */
  public function endQueryCapture() {
    global $conf, $queries;
    if (!empty($this->fix_dev_query)) {
      $conf['dev_query'] = FALSE;
    }

    // make a copy of the array so we can manipulate it with array_splice.
    $temp = $queries;

    // Scroll through the queries until we get to our last query key.
    // Unset anything in our temp array.
    if (isset($this->last_query_key)) {
      while (list($id, $query) = each($queries)) {
        if ($id == $this->last_query_key) {
          break;
        }

        unset($temp[$id]);
      }
    }

    $this->additional_queries = $temp;
  }

  /**
   * Make a copy of this view that has been sanitized of all database IDs
   * and handlers and other stuff.
   *
   * I'd call this clone() but it's reserved.
   */
  public function copy() {
    $code = $this->export();
    eval($code);
    return $view;
  }

  /**
   * Safely clone a view.
   *
   * Because views are complicated objects within objects, and PHP loves to
   * do references to everything, if a View is not properly and safely
   * cloned it will still have references to the original view, and can
   * actually cause the original view to point to objects in the cloned
   * view. This gets ugly fast.
   *
   * This will completely wipe a view clean so it can be considered fresh.
   *
   * @return Drupal\views\View
   *    The cloned view.
   */
  public function cloneView() {
    $clone = clone $this;

    $keys = array('current_display', 'display_handler', 'build_info', 'built', 'executed', 'attachment_before', 'attachment_after', 'field', 'argument', 'filter', 'sort', 'relationship', 'header', 'footer', 'empty', 'query', 'inited', 'style_plugin', 'plugin_name', 'exposed_data', 'exposed_input', 'exposed_widgets', 'many_to_one_tables', 'feed_icon');
    foreach ($keys as $key) {
      if (isset($clone->$key)) {
        unset($clone->$key);
      }
    }
    $clone->built = $clone->executed = FALSE;
    $clone->build_info = array();
    $clone->attachment_before = '';
    $clone->attachment_after = '';
    $clone->result = array();

    // Shallow cloning means that all the display objects *were not cloned*, so
    // we must clone them ourselves.
    $displays = array();
    foreach ($clone->display as $id => $display) {
      $displays[$id] = clone $display;
      if (isset($displays[$id]->handler)) {
        unset($displays[$id]->handler);
      }
    }
    $clone->display = $displays;

    return $clone;
  }

  /**
   * Unset references so that a $view object may be properly garbage
   * collected.
   */
  public function destroy() {
    foreach (array_keys($this->display) as $display_id) {
      if (isset($this->display[$display_id]->handler)) {
        $this->display[$display_id]->handler->destroy();
        unset($this->display[$display_id]->handler);
      }
    }

    foreach (View::viewsObjectTypes() as $type => $info) {
      if (isset($this->$type)) {
        $handlers = &$this->$type;
        foreach ($handlers as $id => $item) {
          $handlers[$id]->destroy();
        }
        unset($handlers);
      }
    }

    if (isset($this->style_plugin)) {
      $this->style_plugin->destroy();
      unset($this->style_plugin);
    }

    // Clear these to make sure the view can be processed/used again.
    if (isset($this->display_handler)) {
      unset($this->display_handler);
    }

    if (isset($this->current_display)) {
      unset($this->current_display);
    }

    if (isset($this->query)) {
      unset($this->query);
    }

    $keys = array('current_display', 'display_handler', 'build_info', 'built', 'executed', 'attachment_before', 'attachment_after', 'field', 'argument', 'filter', 'sort', 'relationship', 'header', 'footer', 'empty', 'query', 'result', 'inited', 'style_plugin', 'plugin_name', 'exposed_data', 'exposed_input', 'many_to_one_tables');
    foreach ($keys as $key) {
      if (isset($this->$key)) {
        unset($this->$key);
      }
    }

    // These keys are checked by the next init, so instead of unsetting them,
    // just set the default values.
    $keys = array('items_per_page', 'offset', 'current_page');
    foreach ($keys as $key) {
      if (isset($this->$key)) {
        $this->$key = NULL;
      }
    }

    $this->built = $this->executed = FALSE;
    $this->build_info = array();
    $this->attachment_before = '';
    $this->attachment_after = '';
  }

  /**
   * Make sure the view is completely valid.
   *
   * @return
   *   TRUE if the view is valid; an array of error strings if it is not.
   */
  public function validate() {
    $this->initDisplay();

    $errors = array();
    $this->display_errors = NULL;

    $current_display = $this->current_display;
    foreach ($this->display as $id => $display) {
      if ($display->handler) {
        if (!empty($display->deleted)) {
          continue;
        }

        $result = $this->display[$id]->handler->validate();
        if (!empty($result) && is_array($result)) {
          $errors = array_merge($errors, $result);
          // Mark this display as having validation errors.
          $this->display_errors[$id] = TRUE;
        }
      }
    }

    $this->setDisplay($current_display);
    return $errors ? $errors : TRUE;
  }

  /**
   * Find and initialize the localizer plugin.
   */
  public function initLocalization() {
    // @todo The check for the view was added to ensure that
    //   $this->localization_plugin->init() is run.
    if (isset($this->localization_plugin) && is_object($this->localization_plugin) && isset($this->localization_plugin->view)) {
      return TRUE;
    }

    $this->localization_plugin = views_get_plugin('localization', views_get_localization_plugin());

    if (empty($this->localization_plugin)) {
      $this->localization_plugin = views_get_plugin('localization', 'none');
      $this->localization_plugin->init($this);
      return FALSE;
    }

    /**
    * Figure out whether there should be options.
    */
    $this->localization_plugin->init($this);

    return $this->localization_plugin->translate;
  }

  /**
   * Determine whether a view supports admin string translation.
   */
  public function isTranslatable() {
    // If the view is normal or overridden, use admin string translation.
    // A newly created view won't have a type. Accept this.
    return (!isset($this->type) || in_array($this->type, array(t('Normal'), t('Overridden')))) ? TRUE : FALSE;
  }

  /**
   * Send strings for localization.
   */
  public function saveLocaleStrings() {
    $this->processLocaleStrings('save');
  }

  /**
   * Delete localized strings.
   */
  public function deleteLocaleStrings() {
    $this->processLocaleStrings('delete');
  }

  /**
   * Export localized strings.
   */
  public function exportLocaleStrings() {
    $this->processLocaleStrings('export');
  }

  /**
   * Process strings for localization, deletion or export to code.
   */
  public function processLocaleStrings($op) {
    // Ensure this view supports translation, we have a display, and we
    // have a localization plugin.
    // @fixme Export does not init every handler.
    if (($this->isTranslatable() || $op == 'export') && $this->initDisplay() && $this->initLocalization()) {
      $this->localization_plugin->process_locale_strings($op);
    }
  }

  /**
   * Providea a list of views object types used in a view, with some information
   * about them.
   */
  public static function viewsObjectTypes() {
    static $retval = NULL;

    // Statically cache this so t() doesn't run a bajillion times.
    if (!isset($retval)) {
      $retval = array(
        'field' => array(
          'title' => t('Fields'), // title
          'ltitle' => t('fields'), // lowercase title for mid-sentence
          'stitle' => t('Field'), // singular title
          'lstitle' => t('field'), // singular lowercase title for mid sentence
          'plural' => 'fields',
        ),
        'argument' => array(
          'title' => t('Contextual filters'),
          'ltitle' => t('contextual filters'),
          'stitle' => t('Contextual filter'),
          'lstitle' => t('contextual filter'),
          'plural' => 'arguments',
        ),
        'sort' => array(
          'title' => t('Sort criteria'),
          'ltitle' => t('sort criteria'),
          'stitle' => t('Sort criterion'),
          'lstitle' => t('sort criterion'),
          'plural' => 'sorts',
        ),
        'filter' => array(
          'title' => t('Filter criteria'),
          'ltitle' => t('filter criteria'),
          'stitle' => t('Filter criterion'),
          'lstitle' => t('filter criterion'),
          'plural' => 'filters',
        ),
        'relationship' => array(
          'title' => t('Relationships'),
          'ltitle' => t('relationships'),
          'stitle' => t('Relationship'),
          'lstitle' => t('Relationship'),
          'plural' => 'relationships',
        ),
        'header' => array(
          'title' => t('Header'),
          'ltitle' => t('header'),
          'stitle' => t('Header'),
          'lstitle' => t('Header'),
          'plural' => 'header',
          'type' => 'area',
        ),
        'footer' => array(
          'title' => t('Footer'),
          'ltitle' => t('footer'),
          'stitle' => t('Footer'),
          'lstitle' => t('Footer'),
          'plural' => 'footer',
          'type' => 'area',
        ),
        'empty' => array(
          'title' => t('No results behavior'),
          'ltitle' => t('no results behavior'),
          'stitle' => t('No results behavior'),
          'lstitle' => t('No results behavior'),
          'plural' => 'empty',
          'type' => 'area',
        ),
      );
    }

    return $retval;
  }

}
