<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\FieldFormatter\TextTrimmedFormatter.
 */
namespace Drupal\text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_trimmed' formatter.
 *
 * Note: This class also contains the implementations used by the
 * 'text_summary_or_trimmed' formatter.
 *
 * @see \Drupal\text\Field\Formatter\TextSummaryOrTrimmedFormatter
 *
 * @FieldFormatter(
 *   id = "text_trimmed",
 *   label = @Translation("Trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class TextTrimmedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '600',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['trim_length'] = array(
      '#title' => t('Trim length'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Trim length: @trim_length', array('@trim_length' => $this->getSetting('trim_length')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    if ($this->getFieldSetting('text_processing')) {
      return $this->viewElementsWithTextProcessing($items);
    }
    else {
      return $this->viewElementsWithoutTextProcessing($items);
    }
  }

  /**
   * Builds a renderable array when text processing is enabled.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The text field values to be rendered.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  protected function viewElementsWithTextProcessing(FieldItemListInterface $items) {
    $elements = array();

    $render_as_summary = function (&$element) {
      // Make sure any default #pre_render callbacks are set on the element,
      // because text_pre_render_summary() must run last.
      $element += \Drupal::service('element_info')->getInfo($element['#type']);
      // Add the #pre_render callback that renders the text into a summary.
      $element['#pre_render'][] = '\Drupal\text\Plugin\field\FieldFormatter\TextTrimmedFormatter::preRenderSummary';
      // Pass on the trim length to the #pre_render callback via a property.
      $element['#text_summary_trim_length'] = $this->getSetting('trim_length');
    };

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => NULL,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      );

      if ($this->getPluginId() == 'text_summary_or_trimmed' && !empty($item->summary)) {
        $elements[$delta]['#text'] = $item->summary;
      }
      else {
        $elements[$delta]['#text'] = $item->value;
        $render_as_summary($elements[$delta]);
      }
    }

    return $elements;
  }

  /**
   * Builds a renderable array when text processing is disabled.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The text field values to be rendered.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  protected function viewElementsWithoutTextProcessing(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($this->getPluginId() == 'text_summary_or_trimmed' && !empty($item->summary)) {
        $output = $item->summary_processed;
      }
      else {
        $output = text_summary($item->processed, NULL, $this->getSetting('trim_length'));
      }

      $elements[$delta] = array(
        '#markup' => $output,
      );
    }

    return $elements;
  }

  /**
   * Pre-render callback: Renders a processed text element's #markup as a summary.
   *
   * @param array $element
   *   A structured array with the following key-value pairs:
   *   - #markup: the filtered text (as filtered by filter_pre_render_text())
   *   - #format: containing the machine name of the filter format to be used to
   *     filter the text. Defaults to the fallback format. See
   *     filter_fallback_format().
   *   - #text_summary_trim_length: the desired character length of the summary
   *     (used by text_summary())
   *
   * @return array
   *   The passed-in element with the filtered text in '#markup' trimmed.
   *
   * @see filter_pre_render_text()
   * @see text_summary()
   */
  public static function preRenderSummary(array $element) {
    $element['#markup'] = text_summary($element['#markup'], $element['#format'], $element['#text_summary_trim_length']);
    return $element;
  }

}
