<?php

/**
 * @file
 * Definition of views_handler_field_taxonomy.
 */


namespace Views\taxonomy\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to provide simple renderer that allows linking to a taxonomy
 * term.
 *
 * @ingroup views_field_handlers
 */

/**
 * @Plugin(
 *   id = "taxonomy"
 * )
 */
class Taxonomy extends FieldPluginBase {
  /**
   * Constructor to provide additional field to add.
   *
   * This constructer assumes the taxonomy_term_data table. If using another
   * table, we'll need to be more specific.
   */
  function construct() {
    parent::construct();
    $this->additional_fields['vid'] = 'vid';
    $this->additional_fields['tid'] = 'tid';
    $this->additional_fields['vocabulary_machine_name'] = array(
      'table' => 'taxonomy_vocabulary',
      'field' => 'machine_name',
    );
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_taxonomy'] = array('default' => FALSE, 'bool' => TRUE);
    $options['convert_spaces'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to taxonomy option
   */
  function options_form(&$form, &$form_state) {
    $form['link_to_taxonomy'] = array(
      '#title' => t('Link this field to its taxonomy term page'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_taxonomy']),
    );
     $form['convert_spaces'] = array(
      '#title' => t('Convert spaces in term names to hyphens'),
      '#description' => t('This allows links to work with Views taxonomy term arguments.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['convert_spaces']),
    );
    parent::options_form($form, $form_state);
  }

  /**
   * Render whatever the data is as a link to the taxonomy.
   *
   * Data should be made XSS safe prior to calling this function.
   */
  function render_link($data, $values) {
    $tid = $this->get_value($values, 'tid');
    if (!empty($this->options['link_to_taxonomy']) && !empty($tid) && $data !== NULL && $data !== '') {
      $term = entity_create('taxonomy_term', array(
        'tid' => $tid,
        'vid' => $this->get_value($values, 'vid'),
        'vocabulary_machine_name' => $values->{$this->aliases['vocabulary_machine_name']},
      ));
      $this->options['alter']['make_link'] = TRUE;
      $uri = $term->uri();
      $this->options['alter']['path'] = $uri['path'];
    }

    if (!empty($this->options['convert_spaces'])) {
      $data = str_replace(' ', '-', $data);
    }

    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitize_value($value), $values);
  }
}
