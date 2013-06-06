<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\field\formatter\LinkSeparateFormatter.
 *
 * @todo
 * Merge into 'link' formatter once there is a #type like 'item' that
 * can render a compound label and content outside of a form context.
 * http://drupal.org/node/1829202
 */

namespace Drupal\link\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;

/**
 * Plugin implementation of the 'link_separate' formatter.
 *
 * @Plugin(
 *   id = "link_separate",
 *   module = "link",
 *   label = @Translation("Separate link text and URL"),
 *   field_types = {
 *     "link"
 *   },
 *   settings = {
 *     "trim_length" = "80",
 *     "rel" = "",
 *     "target" = ""
 *   }
 * )
 */
class LinkSeparateFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $element = array();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $link_title = $item['url'];

      // If the link text field value is available, use it for the text.
      if (empty($settings['url_only']) && !empty($item['title'])) {
        // Unsanitized token replacement here because $options['html'] is FALSE
        // by default in theme_link().
        $link_title = \Drupal::token()->replace($item['title'], array($entity->entityType() => $entity), array('sanitize' => FALSE, 'clear' => TRUE));
      }

      // The link_separate formatter has two titles; the link text (as in the
      // field values) and the URL itself. If there is no link text value,
      // $link_title defaults to the URL, so it needs to be unset.
      // The URL version may need to be trimmed as well.
      if (empty($item['title'])) {
        $link_title = NULL;
      }
      $url_title = $item['url'];
      if (!empty($settings['trim_length'])) {
        $link_title = truncate_utf8($link_title, $settings['trim_length'], FALSE, TRUE);
        $url_title = truncate_utf8($item['url'], $settings['trim_length'], FALSE, TRUE);
      }
      $element[$delta] = array(
        '#theme' => 'link_formatter_link_separate',
        '#title' => $link_title,
        '#url_title' => $url_title,
        '#href' => $item['path'],
        '#options' => $item['options'],
      );
    }
    return $element;
  }
}

