<?php

/**
 * @file
 * Definition of Views\comment\Plugin\views\field\Comment.
 */

namespace Views\comment\Plugin\views\field;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to allow linking to a comment.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "comment",
 *   module = "comment"
 * )
 */
class Comment extends FieldPluginBase {

  /**
   * Override init function to provide generic option to link to comment.
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);
    if (!empty($this->options['link_to_comment'])) {
      $this->additional_fields['cid'] = 'cid';
      $this->additional_fields['nid'] = 'nid';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_comment'] = array('default' => TRUE, 'bool' => TRUE);
    $options['link_to_node'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide link-to-comment option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_comment'] = array(
      '#title' => t('Link this field to its comment'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_comment'],
    );
    $form['link_to_node'] = array(
      '#title' => t('Link field to the node if there is no comment.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_node'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_comment'])) {
      $this->options['alter']['make_link'] = TRUE;
      $nid = $this->get_value($values, 'nid');
      $cid = $this->get_value($values, 'cid');
      if (!empty($cid)) {
        $this->options['alter']['path'] = "comment/" . $cid;
        $this->options['alter']['fragment'] = "comment-" . $cid;
      }
      // If there is no comment link to the node.
      elseif ($this->options['link_to_node']) {
        $this->options['alter']['path'] = "node/" . $nid;
      }
    }

    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
