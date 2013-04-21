<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\RSSCategoryFormatter.
 */

namespace Drupal\taxonomy\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\taxonomy\Plugin\field\formatter\TaxonomyFormatterBase;

/**
 * Plugin implementation of the 'taxonomy_term_reference_rss_category' formatter.
 *
 * @Plugin(
 *   id = "taxonomy_term_reference_rss_category",
 *   module = "taxonomy",
 *   label = @Translation("RSS category"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   }
 * )
 */
class RSSCategoryFormatter extends TaxonomyFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    // Terms whose tid is 'autocreate' do not exist yet and $item['entity'] is
    // not set. Theme such terms as just their name.
    foreach ($items as $item) {
      if ($item['tid'] != 'autocreate') {
        $value = $item['entity']->label();

        $uri = $item['entity']->uri();
        $uri['options']['absolute'] = TRUE;
        $domain = url($uri['path'], $uri['options']);
      }
      else {
        $value = $item['name'];
        $domain = '';
      }
      $entity->rss_elements[] = array(
        'key' => 'category',
        'value' => $value,
        'attributes' => array(
          'domain' => $domain,
        ),
      );
    }
  }

}
