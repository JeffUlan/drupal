<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\wizard\TaxonomyTerm.
 */

namespace Drupal\views\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Tests creating taxonomy views with the wizard.
 *
 * @Plugin(
 *   plugin_id = "taxonomy_term",
 *   base_table = "taxonomy_term_data",
 *   title = @Translation("Taxonomy terms"),
 *   path_field = {
 *     "id" = "tid",
 *     "table" = "taxonomy_term_data",
 *     "field" = "tid",
 *     "exclude" = TRUE,
 *     "alter" = {
 *       "alter_text" = 1,
 *       "text" = "taxonomy/term/[tid]"
 *     }
 *   }
 * )
 */
class TaxonomyTerm extends WizardPluginBase {

  protected function default_display_options($form, $form_state) {
    $display_options = parent::default_display_options($form, $form_state);

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: Taxonomy: Term */
    $display_options['fields']['name']['id'] = 'name';
    $display_options['fields']['name']['table'] = 'taxonomy_term_data';
    $display_options['fields']['name']['field'] = 'name';
    $display_options['fields']['name']['label'] = '';
    $display_options['fields']['name']['alter']['alter_text'] = 0;
    $display_options['fields']['name']['alter']['make_link'] = 0;
    $display_options['fields']['name']['alter']['absolute'] = 0;
    $display_options['fields']['name']['alter']['trim'] = 0;
    $display_options['fields']['name']['alter']['word_boundary'] = 0;
    $display_options['fields']['name']['alter']['ellipsis'] = 0;
    $display_options['fields']['name']['alter']['strip_tags'] = 0;
    $display_options['fields']['name']['alter']['html'] = 0;
    $display_options['fields']['name']['hide_empty'] = 0;
    $display_options['fields']['name']['empty_zero'] = 0;
    $display_options['fields']['name']['link_to_taxonomy'] = 1;

    return $display_options;
  }
}
