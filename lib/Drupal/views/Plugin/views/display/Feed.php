<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\display\Feed.
 */

namespace Drupal\views\Plugin\views\display;

use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * The plugin that handles a feed, such as RSS or atom.
 *
 * For the most part, feeds are page displays but with some subtle differences.
 *
 * @ingroup views_display_plugins
 *
 * @Plugin(
 *   id = "feed",
 *   title = @Translation("Feed"),
 *   help = @Translation("Display the view as a feed, such as an RSS feed."),
 *   uses_hook_menu = TRUE,
 *   admin = @Translation("Feed")
 * )
 */
class Feed extends Page {

  /**
   * Whether the display allows the use of AJAX or not.
   *
   * @var bool
   */
  protected $usesAJAX = FALSE;

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @var bool
   */
  protected $usesPager = FALSE;

  public function init(ViewExecutable $view, &$display, $options = NULL) {
    parent::init($view, $display, $options);

    // Set the default row style. Ideally this would be part of the option
    // definition, but in this case it's dependent on the view's base table,
    // which we don't know until init().
    $row_plugins = views_fetch_plugin_names('row', $this->getStyleType(), array($view->storage->base_table));
    $default_row_plugin = key($row_plugins);
    if (empty($this->options['row']['type'])) {
      $this->options['row']['type'] = $default_row_plugin;
    }
  }

  public function usesBreadcrumb() { return FALSE; }
  protected function getStyleType() { return 'feed'; }

  /**
   * Feeds do not go through the normal page theming mechanism. Instead, they
   * go through their own little theme function and then return NULL so that
   * Drupal believes that the page has already rendered itself...which it has.
   */
  public function execute() {
    $output = $this->view->render();
    if (empty($output)) {
      throw new NotFoundHttpException();
    }

    $response = $this->view->getResponse();
    $response->setContent($output);
    return $response;
  }

  public function preview() {
    if (!empty($this->view->live_preview)) {
      return '<pre>' . check_plain($this->view->render()) . '</pre>';
    }
    return $this->view->render();
  }

  /**
   * Instead of going through the standard views_view.tpl.php, delegate this
   * to the style handler.
   */
  public function render() {
    return $this->view->style_plugin->render($this->view->result);
  }

  public function defaultableSections($section = NULL) {
    if (in_array($section, array('style', 'row'))) {
      return FALSE;
    }

    $sections = parent::defaultableSections($section);

    // Tell views our sitename_title option belongs in the title section.
    if ($section == 'title') {
      $sections[] = 'sitename_title';
    }
    elseif (!$section) {
      $sections['title'][] = 'sitename_title';
    }
    return $sections;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['displays'] = array('default' => array());

    // Overrides for standard stuff:
    $options['style']['contains']['type']['default'] = 'rss';
    $options['style']['contains']['options']['default']  = array('description' => '');
    $options['sitename_title']['default'] = FALSE;
    $options['row']['contains']['type']['default'] = '';
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    return $options;
  }

  public function optionsSummary(&$categories, &$options) {
    // It is very important to call the parent function here:
    parent::optionsSummary($categories, $options);

    // Since we're childing off the 'page' type, we'll still *call* our
    // category 'page' but let's override it so it says feed settings.
    $categories['page'] = array(
      'title' => t('Feed settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    if ($this->getOption('sitename_title')) {
      $options['title']['value'] = t('Using the site name');
    }

    // I don't think we want to give feeds menus directly.
    unset($options['menu']);

    $displays = array_filter($this->getOption('displays'));
    if (count($displays) > 1) {
      $attach_to = t('Multiple displays');
    }
    elseif (count($displays) == 1) {
      $display = array_shift($displays);
      if (!empty($this->view->storage->display[$display])) {
        $attach_to = check_plain($this->view->storage->display[$display]['display_title']);
      }
    }

    if (!isset($attach_to)) {
      $attach_to = t('None');
    }

    $options['displays'] = array(
      'category' => 'page',
      'title' => t('Attach to'),
      'value' => $attach_to,
    );
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    // It is very important to call the parent function here.
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'title':
        $title = $form['title'];
        // A little juggling to move the 'title' field beyond our checkbox.
        unset($form['title']);
        $form['sitename_title'] = array(
          '#type' => 'checkbox',
          '#title' => t('Use the site name for the title'),
          '#default_value' => $this->getOption('sitename_title'),
        );
        $form['title'] = $title;
        $form['title']['#states'] = array(
          'visible' => array(
            ':input[name="sitename_title"]' => array('checked' => FALSE),
          ),
        );
        break;
      case 'displays':
        $form['#title'] .= t('Attach to');
        $displays = array();
        foreach ($this->view->storage->display as $display_id => $display) {
          // @todo The display plugin should have display_title and id as well.
          if (!empty($this->view->displayHandlers[$display_id]) && $this->view->displayHandlers[$display_id]->acceptAttachments()) {
            $displays[$display_id] = $display['display_title'];
          }
        }
        $form['displays'] = array(
          '#type' => 'checkboxes',
          '#description' => t('The feed icon will be available only to the selected displays.'),
          '#options' => $displays,
          '#default_value' => $this->getOption('displays'),
        );
        break;
      case 'path':
        $form['path']['#description'] = t('This view will be displayed by visiting this path on your site. It is recommended that the path be something like "path/%/%/feed" or "path/%/%/rss.xml", putting one % in the path for each contextual filter you have defined in the view.');
    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, &$form_state) {
    // It is very important to call the parent function here:
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state['section']) {
      case 'title':
        $this->setOption('sitename_title', $form_state['values']['sitename_title']);
        break;
      case 'displays':
        $this->setOption($form_state['section'], $form_state['values'][$form_state['section']]);
        break;
    }
  }

  /**
   * Attach to another view.
   */
  public function attachTo($display_id) {
    $displays = $this->getOption('displays');
    if (empty($displays[$display_id])) {
      return;
    }

    // Defer to the feed style; it may put in meta information, and/or
    // attach a feed icon.
    $plugin = $this->getPlugin('style');
    if ($plugin) {
      $clone = $this->view->cloneView();
      $clone->setDisplay($this->display['id']);
      $clone->buildTitle();
      $plugin->attach_to($display_id, $this->getPath(), $clone->getTitle());

      // Clean up
      $clone->destroy();
      unset($clone);
    }
  }

  public function usesLinkDisplay() {
    return TRUE;
  }

}
