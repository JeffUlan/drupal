<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ReorderDisplays.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views_ui\ViewUI;

/**
 * Displays the display reorder form.
 */
class ReorderDisplays extends ViewsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'reorder-displays';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_reorder_displays_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var $view \Drupal\views\ViewEntityInterface */
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');

    $form['#title'] = $this->t('Reorder displays');
    $form['#section'] = 'reorder';
    $form['#action'] = $this->url('views_ui.form_reorder_displays', [
      'js' => 'nojs',
      'view' => $view->id(),
      'display_id' => $display_id,
    ]);
    $form['view'] = array(
      '#type' => 'value',
      '#value' => $view
    );

    $displays = $view->get('display');
    $count = count($displays);

    // Sort the displays.
    uasort($displays, function ($display1, $display2) {
      if ($display1['position'] != $display2['position']) {
        return $display1['position'] < $display2['position'] ? -1 : 1;
      }
      return 0;
    });

    $form['displays'] = array(
      '#type' => 'table',
      '#id' => 'reorder-displays',
      '#header' => array($this->t('Display'), $this->t('Weight'), $this->t('Remove')),
      '#empty' => $this->t('No displays available.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        )
      ),
      '#tree' => TRUE,
      '#prefix' => '<div class="scroll" data-drupal-views-scroll>',
      '#suffix' => '</div>',
    );

    foreach ($displays as $id => $display) {
      $form['displays'][$id] = array(
        '#display' => $display,
        '#attributes' => array(
          'id' => 'display-row-' . $id,
        ),
        '#weight' => $display['position'],
      );

      // Only make row draggable if it's not the default display.
      if ($id !== 'default') {
        $form['displays'][$id]['#attributes']['class'][] = 'draggable';
      }

      $form['displays'][$id]['title'] = array(
        '#markup' => $display['display_title'],
      );

      $form['displays'][$id]['weight'] = array(
        '#type' => 'weight',
        '#value' => $display['position'],
        '#delta' => $count,
        '#title' => $this->t('Weight for @display', array('@display' => $display['display_title'])),
        '#title_display' => 'invisible',
        '#attributes' => array(
          'class' => array('weight'),
        ),
      );

      $form['displays'][$id]['removed'] = array(
        'checkbox' => array(
          '#title' => t('Remove @id', array('@id' => $id)),
          '#title_display' => 'invisible',
          '#type' => 'checkbox',
          '#id' => 'display-removed-' . $id,
          '#attributes' => array(
            'class' => array('views-remove-checkbox'),
          ),
          '#default_value' => !empty($display['deleted']),
        ),
        'link' => array(
          '#type' => 'link',
          '#title' => SafeMarkup::format('<span>@text</span>', array('@text' => $this->t('Remove'))),
          '#url' => Url::fromRoute('<none>'),
          '#attributes' => array(
            'id' => 'display-remove-link-' . $id,
            'class' => array('views-button-remove', 'display-remove-link'),
            'alt' => $this->t('Remove this display'),
            'title' => $this->t('Remove this display'),
          ),
        ),
        '#access' => ($id !== 'default'),
      );

      if (!empty($display['deleted'])) {
        $form['displays'][$id]['deleted'] = array(
          '#type' => 'value',
          '#value' => TRUE,
        );

        $form['displays'][$id]['#attributes']['class'][] = 'hidden';
      }

    }

    $view->getStandardButtons($form, $form_state, 'views_ui_reorder_displays_form');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var $view \Drupal\views_ui\ViewUI */
    $view = $form_state->get('view');
    $order = array();

    $user_input = $form_state->getUserInput();
    foreach ($user_input['displays'] as $display => $info) {
      // Add each value that is a field with a weight to our list, but only if
      // it has had its 'removed' checkbox checked.
      if (is_array($info) && isset($info['weight']) && empty($info['removed']['checkbox'])) {
        $order[$display] = $info['weight'];
      }
    }

    // Sort the order array.
    asort($order);

    // Remove the default display from ordering.
    unset($order['default']);
    // Increment up positions.
    $position = 1;

    foreach (array_keys($order) as $display) {
      $order[$display] = $position++;
    }

    // Setting up position and removing deleted displays.
    $displays = $view->get('display');
    foreach ($displays as $display_id => &$display) {
      // Don't touch the default.
      if ($display_id === 'default') {
        $display['position'] = 0;
        continue;
      }
      if (isset($order[$display_id])) {
        $display['position'] = $order[$display_id];
      }
      else {
        $display['deleted'] = TRUE;
      }
    }
    $view->set('display', $displays);

    // Store in cache.
    $view->cacheSet();
    $url = $view->urlInfo('edit-form')
      ->setOption('fragment', 'views-tab-default');
    $form_state->setRedirectUrl($url);
  }

}
