<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\views\pager\Mini.
 */

namespace Drupal\views\Plugins\views\pager;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * The plugin to handle full pager.
 *
 * @ingroup views_pager_plugins
 */

/**
 * @Plugin(
 *   plugin_id = "mini",
 *   title = @Translation("Paged output, mini pager"),
 *   short_title = @Translation("Mini"),
 *   help = @Translation("Use the mini pager output."),
 *   help_topic = "pager-mini",
 *   uses_options = TRUE
 * )
 */
class Mini extends PagerPluginBase {
  function summary_title() {
    if (!empty($this->options['offset'])) {
      return format_plural($this->options['items_per_page'], 'Mini pager, @count item, skip @skip', 'Mini pager, @count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
      return format_plural($this->options['items_per_page'], 'Mini pager, @count item', 'Mini pager, @count items', array('@count' => $this->options['items_per_page']));
  }

  function render($input) {
    $pager_theme = views_theme_functions('views_mini_pager', $this->view, $this->display);
    return theme($pager_theme, array(
      'parameters' => $input, 'element' => $this->options['id']));
  }
}
