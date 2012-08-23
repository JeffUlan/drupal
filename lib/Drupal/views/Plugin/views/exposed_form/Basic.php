<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\exposed_form\Basic.
 */

namespace Drupal\views\Plugin\views\exposed_form;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @Plugin(
 *   id = "basic",
 *   title = @Translation("Basic"),
 *   help = @Translation("Basic exposed form"),
 *   help_topic = "exposed-form-basic"
 * )
 */
class Basic extends ExposedFormPluginBase {

}
