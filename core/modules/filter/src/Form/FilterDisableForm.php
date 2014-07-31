<?php

/**
 * @file
 * Contains \Drupal\filter\Form\FilterDisableForm.
 */

namespace Drupal\filter\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the filter format disable form.
 */
class FilterDisableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable the text format %format?', array('%format' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('filter.admin_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Disabled text formats are completely removed from the administrative interface, and any content stored with that format will not be displayed. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state) {
    $this->entity->disable()->save();
    drupal_set_message($this->t('Disabled text format %format.', array('%format' => $this->entity->label())));

    $form_state['redirect_route'] = $this->getCancelUrl();
  }

}
