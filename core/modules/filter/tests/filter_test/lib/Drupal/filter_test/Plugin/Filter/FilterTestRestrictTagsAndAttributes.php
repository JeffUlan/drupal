<?php

/**
 * @file
 * Contains \Drupal\filter_test\Plugin\Filter\FilterTestRestrictTagsAndAttributes.
 */

namespace Drupal\filter_test\Plugin\Filter;

use Drupal\filter\Annotation\Filter;
use Drupal\Core\Annotation\Translation;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Xss;

/**
 * Provides a test filter to restirct HTML tags and attributes.
 *
 * @Filter(
 *   id = "filter_test_restrict_tags_and_attributes",
 *   module = "filter_test",
 *   title = @Translation("Tag and attribute restricting filter"),
 *   description = @Translation("Used for testing filter_get_html_restrictions_by_format()."),
 *   type = FILTER_TYPE_HTML_RESTRICTOR
 * )
 */
class FilterTestRestrictTagsAndAttributes extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    $allowed_tags = array_filter($this->settings['restrictions']['allowed'], function($value) {
      return is_array($value) || (bool) $value !== FALSE;
    });
    return Xss::filter($text, array_keys($allowed_tags));
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    $restrictions = $this->settings['restrictions'];

    // The configuration system stores FALSE as '0' and TRUE as '1'. Fix that.
    if (isset($restrictions['allowed'])) {
      foreach ($restrictions['allowed'] as $tag => $attrs_or_bool) {
        if (!is_array($attrs_or_bool)) {
          $restrictions['allowed'][$tag] = (bool) $attrs_or_bool;
        }
        else {
          foreach ($attrs_or_bool as $attr => $attrvals_or_bool) {
            if (!is_array($attrvals_or_bool)) {
              $restrictions['allowed'][$tag][$attr] = (bool) $attrvals_or_bool;
            }
            else {
              foreach ($attrvals_or_bool as $attrval => $bool) {
                $restrictions['allowed'][$tag][$attr][$attrval] = (bool) $bool;
              }
            }
          }
        }
      }
    }
    if (isset($restrictions['forbidden_tags'])) {
      foreach ($restrictions['forbidden_tags'] as $tag => $bool) {
        $restrictions['forbidden_tags'][$tag] = (bool) $bool;
      }
    }

    return $restrictions;
  }

}
