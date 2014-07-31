<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\EditDetails.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\views_ui\ViewUI;

/**
 * Provides a form for editing the details of a View.
 */
class EditDetails extends ViewsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'edit-details';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_edit_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state['view'];

    $form['#title'] = $this->t('Name and description');
    $form['#section'] = 'details';

    $form['details'] = array(
      '#theme_wrappers' => array('container'),
      '#attributes' => array('class' => array('scroll'), 'data-drupal-views-scroll' => TRUE),
    );
    $form['details']['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Administrative name'),
      '#default_value' => $view->label(),
    );
    $form['details']['langcode'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('View language'),
      '#description' => $this->t('Language of labels and other textual elements in this view.'),
      '#default_value' => $view->get('langcode'),
    );
    $form['details']['description'] = array(
       '#type' => 'textfield',
       '#title' => t('Administrative description'),
       '#default_value' => $view->get('description'),
     );
    $form['details']['tag'] = array(
      '#type' => 'textfield',
      '#title' => t('Administrative tags'),
      '#description' => t('Enter a comma-separated list of words to describe your view.'),
      '#default_value' => $view->get('tag'),
      '#autocomplete_route_name' => 'views_ui.autocomplete',
    );

    $view->getStandardButtons($form, $form_state, 'views_ui_edit_details_form');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state['view'];
    foreach ($form_state['values'] as $key => $value) {
      // Only save values onto the view if they're actual view properties
      // (as opposed to 'op' or 'form_build_id').
      if (isset($form['details'][$key])) {
        $view->set($key, $value);
      }
    }
    $bases = Views::viewsData()->fetchBaseTables();
    $form_state['#page_title'] = $view->label();

    if (isset($bases[$view->get('base_table')])) {
      $form_state['#page_title'] .= ' (' . $bases[$view->get('base_table')]['title'] . ')';
    }

    $view->cacheSet();
  }

}
