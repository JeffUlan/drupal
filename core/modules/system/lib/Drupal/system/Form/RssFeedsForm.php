<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure RSS settings for this site.
 */
class RssFeedsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_rss_feeds_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $rss_config = $this->config('system.rss');
    $form['feed_description'] = array(
      '#type' => 'textarea',
      '#title' => t('Feed description'),
      '#default_value' => $rss_config->get('channel.description'),
      '#description' => t('Description of your site, included in each feed.')
    );
    $options = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25, 30);
    $form['feed_default_items'] = array(
      '#type' => 'select',
      '#title' => t('Number of items in each feed'),
      '#default_value' => $rss_config->get('items.limit'),
      '#options' => array_combine($options, $options),
      '#description' => t('Default number of items to include in each feed.')
    );
    $form['feed_item_length'] = array(
      '#type' => 'select',
      '#title' => t('Feed content'),
      '#default_value' => $rss_config->get('items.view_mode'),
      '#options' => array(
        'title' => t('Titles only'),
        'teaser' => t('Titles plus teaser'),
        'fulltext' => t('Full text'),
      ),
      '#description' => t('Global setting for the default display of content items in each feed.')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('system.rss')
      ->set('channel.description', $form_state['values']['feed_description'])
      ->set('items.limit', $form_state['values']['feed_default_items'])
      ->set('items.view_mode', $form_state['values']['feed_item_length'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
