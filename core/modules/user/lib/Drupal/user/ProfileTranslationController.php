<?php

/**
 * @file
 * Definition of Drupal\user\ProfileTranslationController.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\translation_entity\EntityTranslationController;

/**
 * Defines the translation controller class for terms.
 */
class ProfileTranslationController extends EntityTranslationController {

  /**
   * Overrides EntityTranslationController::entityFormAlter().
   */
  public function entityFormAlter(array &$form, array &$form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);
    $form['actions']['submit']['#submit'][] = array($this, 'entityFormSave');
  }

  /**
   * Form submission handler for ProfileTranslationController::entityFormAlter().
   *
   * This handles the save action.
   *
   * @see \Drupal\Core\Entity\EntityFormController::build().
   */
  function entityFormSave(array $form, array &$form_state) {
    if ($this->getSourceLangcode($form_state)) {
      $entity = translation_entity_form_controller($form_state)->getEntity();
      // We need a redirect here, otherwise we would get an access denied page
      // since the current URL would be preserved and we would try to add a
      // translation for a language that already has a translation.
      $form_state['redirect'] = $this->getViewPath($entity);
    }
  }
}
