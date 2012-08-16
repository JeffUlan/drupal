<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\Date.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * A handler to provide proper displays for dates.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "date"
 * )
 */
class Date extends FieldPluginBase {

  function option_definition() {
    $options = parent::option_definition();

    $options['date_format'] = array('default' => 'small');
    $options['custom_date_format'] = array('default' => '');
    $options['timezone'] = array('default' => '');

    return $options;
  }

  function options_form(&$form, &$form_state) {

    $date_formats = array();
    $date_types = system_get_date_types();
    foreach ($date_types as $key => $value) {
      $date_formats[$value['type']] = check_plain(t($value['title'] . ' format')) . ': ' . format_date(REQUEST_TIME, $value['type']);
    }

    $form['date_format'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#options' => $date_formats + array(
        'custom' => t('Custom'),
        'raw time ago' => t('Time ago'),
        'time ago' => t('Time ago (with "ago" appended)'),
        'raw time hence' => t('Time hence'),
        'time hence' => t('Time hence (with "hence" appended)'),
        'raw time span' => t('Time span (future dates have "-" prepended)'),
        'inverse time span' => t('Time span (past dates have "-" prepended)'),
        'time span' => t('Time span (with "ago/hence" appended)'),
      ),
      '#default_value' => isset($this->options['date_format']) ? $this->options['date_format'] : 'small',
    );
    $form['custom_date_format'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom date format'),
      '#description' => t('If "Custom", see <a href="http://us.php.net/manual/en/function.date.php" target="_blank">the PHP docs</a> for date formats. Otherwise, enter the number of different time units to display, which defaults to 2.'),
      '#default_value' => isset($this->options['custom_date_format']) ? $this->options['custom_date_format'] : '',
    );
    // Setup #states for all possible date_formats on the custom_date_format form element.
    foreach (array('custom', 'raw time ago', 'time ago', 'raw time hence', 'time hence', 'raw time span', 'time span', 'raw time span', 'inverse time span', 'time span') as $custom_date_possible) {
      $form['custom_date_format']['#states']['visible'][] = array(
        ':input[name="options[date_format]"]' => array('value' => $custom_date_possible),
      );
    }
    $form['timezone'] = array(
      '#type' => 'select',
      '#title' => t('Timezone'),
      '#description' => t('Timezone to be used for date output.'),
      '#options' => array('' => t('- Default site/user timezone -')) + system_time_zones(FALSE),
      '#default_value' => $this->options['timezone'],
    );
    foreach (array_merge(array('custom'), array_keys($date_formats)) as $timezone_date_formats) {
      $form['timezone']['#states']['visible'][] = array(
        ':input[name="options[date_format]"]' => array('value' => $timezone_date_formats),
      );
    }

    parent::options_form($form, $form_state);
  }

  function render($values) {
    $value = $this->get_value($values);
    $format = $this->options['date_format'];
    if (in_array($format, array('custom', 'raw time ago', 'time ago', 'raw time hence', 'time hence', 'raw time span', 'time span', 'raw time span', 'inverse time span', 'time span'))) {
      $custom_format = $this->options['custom_date_format'];
    }

    if ($value) {
      $timezone = !empty($this->options['timezone']) ? $this->options['timezone'] : NULL;
      $time_diff = REQUEST_TIME - $value; // will be positive for a datetime in the past (ago), and negative for a datetime in the future (hence)
      switch ($format) {
        case 'raw time ago':
          return format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2);
        case 'time ago':
          return t('%time ago', array('%time' => format_interval($time_diff, is_numeric($custom_format) ? $custom_format : 2)));
        case 'raw time hence':
          return format_interval(-$time_diff, is_numeric($custom_format) ? $custom_format : 2);
        case 'time hence':
          return t('%time hence', array('%time' => format_interval(-$time_diff, is_numeric($custom_format) ? $custom_format : 2)));
        case 'raw time span':
          return ($time_diff < 0 ? '-' : '') . format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2);
        case 'inverse time span':
          return ($time_diff > 0 ? '-' : '') . format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2);
        case 'time span':
          return t(($time_diff < 0 ? '%time hence' : '%time ago'), array('%time' => format_interval(abs($time_diff), is_numeric($custom_format) ? $custom_format : 2)));
        case 'custom':
          if ($custom_format == 'r') {
            return format_date($value, $format, $custom_format, $timezone, 'en');
          }
          return format_date($value, $format, $custom_format, $timezone);
        default:
          return format_date($value, $format, '', $timezone);
      }
    }
  }

}
