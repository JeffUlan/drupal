<?php

/**
 * @file
 * Definition of Drupal\views\Analyzer.
 */

namespace Drupal\views;

use Drupal\views\View;

/**
 * This tool is a small plugin manager to perform analysis on a view and
 * report results to the user. This tool is meant to let modules that
 * provide data to Views also help users properly use that data by
 * detecting invalid configurations. Views itself comes with only a
 * small amount of analysis tools, but more could easily be added either
 * by modules or as patches to Views itself.
 */
class Analyzer {

  /**
   * The view to analyze.
   *
   * @var Drupal\views\View.
   */
  protected $view;

  /**
   * Constructs the analyzer object.
   *
   * @param Drupal\views\View $view
   *   (optional) The view to analyze.
   */
  function __construct(View $view = NULL) {
    if (isset($view)) {
      $this->view = $view;
    }
  }

  /**
   * Gets the view which is analyzed by this analyzer.
   *
   * @return Drupal\views\View.
   *   The view to analyze.
   */
  public function getView() {
    return $this->view;
  }

  /**
   * Sets the view which is analyzed by this analyzer.
   *
   * @param Drupal\views\View
   *   The view to analyze.
   */
  public function setView(View $view = NULL) {
    $this->view = $view;
  }

  /**
   * Analyzes a review and return the results.
   *
   * @return array
   *   An array of analyze results organized into arrays keyed by 'ok',
   *   'warning' and 'error'.
   */
  public function getMessages() {
    $this->view->initDisplay();
    $messages = module_invoke_all('views_analyze', $this->view);

    return $messages;
  }

  /**
   * Formats the analyze result into a message string.
   *
   * This is based upon the format of drupal_set_message which uses separate
   * boxes for "ok", "warning" and "error".
   */
  public function formatMessages(array $messages) {
    if (empty($messages)) {
      $messages = array($this->formatMessage(t('View analysis can find nothing to report.'), 'ok'));
    }

    $types = array('ok' => array(), 'warning' => array(), 'error' => array());
    foreach ($messages as $message) {
      if (empty($types[$message['type']])) {
        $types[$message['type']] = array();
      }
      $types[$message['type']][] = $message['message'];
    }

    $output = '';
    foreach ($types as $type => $messages) {
      $type .= ' messages';
      $message = '';
      if (count($messages) > 1) {
        $message = theme('item_list', array('items' => $messages));
      }
      elseif ($messages) {
        $message = array_shift($messages);
      }

      if ($message) {
        $output .= "<div class=\"$type\">$message</div>";
      }
    }

    return $output;
  }

  /**
   * Formats an analysis message.
   *
   * This tool should be called by any module responding to the analyze hook
   * to properly format the message. It is usually used in the form:
   * @code
   *   $ret[] = Analyzer::formatMessage(t('This is the message'), 'ok');
   * @endcode
   *
   * The 'ok' status should be used to provide information about things
   * that are acceptable. In general analysis isn't interested in 'ok'
   * messages, but instead the 'warning', which is a category for items
   * that may be broken unless the user knows what he or she is doing,
   * and 'error' for items that are definitely broken are much more useful.
   *
   * @param string $message
   * @param string $type
   *   The type of message. This should be "ok", "warning" or "error". Other
   *   values can be used but how they are treated by the output routine
   *   is undefined.
   *
   * @return array
   *   A single formatted message, consisting of a key message and a key type.
   */
  static function formatMessage($message, $type = 'error') {
    return array('message' => $message, 'type' => $type);
  }

}
