<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\wizard\File.
 */

namespace Drupal\views\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Tests creating managed files views with the wizard.
 *
 * @Plugin(
 *   id = "file_managed",
 *   base_table = "file_managed",
 *   created_column = "timestamp",
 *   title = @Translation("Files"),
 *   path_field = {
 *     "id" = "uri",
 *     "table" = "file_managed",
 *     "field" = "uri",
 *     "exclude" = TRUE,
 *     "file_download_path" = TRUE
 *   }
 * )
 */
class File extends WizardPluginBase {

  protected function default_display_options($form, $form_state) {
    $display_options = parent::default_display_options($form, $form_state);

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';

    // Remove the default fields, since we are customizing them here.
    unset($display_options['fields']);

    /* Field: File: Name */
    $display_options['fields']['filename']['id'] = 'filename';
    $display_options['fields']['filename']['table'] = 'file_managed';
    $display_options['fields']['filename']['field'] = 'filename';
    $display_options['fields']['filename']['label'] = '';
    $display_options['fields']['filename']['alter']['alter_text'] = 0;
    $display_options['fields']['filename']['alter']['make_link'] = 0;
    $display_options['fields']['filename']['alter']['absolute'] = 0;
    $display_options['fields']['filename']['alter']['trim'] = 0;
    $display_options['fields']['filename']['alter']['word_boundary'] = 0;
    $display_options['fields']['filename']['alter']['ellipsis'] = 0;
    $display_options['fields']['filename']['alter']['strip_tags'] = 0;
    $display_options['fields']['filename']['alter']['html'] = 0;
    $display_options['fields']['filename']['hide_empty'] = 0;
    $display_options['fields']['filename']['empty_zero'] = 0;
    $display_options['fields']['filename']['link_to_file'] = 1;

    return $display_options;
  }

}
