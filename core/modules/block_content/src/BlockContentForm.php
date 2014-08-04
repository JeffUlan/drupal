<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentForm.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the custom block edit forms.
 */
class BlockContentForm extends ContentEntityForm {

  /**
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructs a BlockContentForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The custom block storage.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $block_content_storage, LanguageManager $language_manager) {
    parent::__construct($entity_manager);
    $this->blockContentStorage = $block_content_storage;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('block_content'),
      $container->get('language_manager')
    );
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::prepareEntity().
   *
   * Prepares the custom block object.
   *
   * Fills in a few default values, and then invokes hook_block_content_prepare()
   * on all modules.
   */
  protected function prepareEntity() {
    $block = $this->entity;
    // Set up default values, if required.
    $block_type = entity_load('block_content_type', $block->bundle());
    if (!$block->isNew()) {
      $block->setRevisionLog(NULL);
    }
    // Always use the default revision setting.
    $block->setNewRevision($block_type->revision);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $block = $this->entity;
    $account = $this->currentUser();

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit custom block %label', array('%label' => $block->label()));
    }
    // Override the default CSS class name, since the user-defined custom block
    // type name in 'TYPE-block-form' potentially clashes with third-party class
    // names.
    $form['#attributes']['class'][0] = drupal_html_class('block-' . $block->bundle() . '-form');

    if ($this->moduleHandler->moduleExists('language')) {
      $language_configuration = language_get_default_configuration('block_content', $block->bundle());

      // Set the correct default language.
      if ($block->isNew()) {
        $language_default = $this->languageManager->getCurrentLanguage($language_configuration['langcode']);
        $block->langcode->value = $language_default->id;
      }
    }

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $block->getUntranslated()->language()->id,
      '#languages' => LanguageInterface::STATE_ALL,
      '#access' => isset($language_configuration['language_show']) && $language_configuration['language_show'],
    );

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $block->isNewRevision(),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('block-content-form-revision-information'),
      ),
      '#attached' => array(
        'library' => array('block_content/drupal.block_content'),
      ),
      '#weight' => 20,
      '#access' => $block->isNewRevision() || $account->hasPermission('administer blocks'),
    );

    $form['revision_information']['revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $block->isNewRevision(),
      '#access' => $account->hasPermission('administer blocks'),
    );

    // Check the revision log checkbox when the log textarea is filled in.
    // This must not happen if "Create new revision" is enabled by default,
    // since the state would auto-disable the checkbox otherwise.
    if (!$block->isNewRevision()) {
      $form['revision_information']['revision']['#states'] = array(
        'checked' => array(
          'textarea[name="revision_log"]' => array('empty' => FALSE),
        ),
      );
    }

    $form['revision_information']['revision_log'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Revision log message'),
      '#rows' => 4,
      '#default_value' => $block->getRevisionLog(),
      '#description' => $this->t('Briefly describe the changes you have made.'),
    );

    return parent::form($form, $form_state, $block);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::submit().
   *
   * Updates the custom block object by processing the submitted values.
   *
   * This function can be called by a "Next" button of a wizard to update the
   * form state's entity with the current step's values before proceeding to the
   * next step.
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the block object from the submitted values.
    $block = parent::submit($form, $form_state);

    // Save as a new revision if requested to do so.
    if (!empty($form_state['values']['revision'])) {
      $block->setNewRevision();
    }

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $block = $this->entity;
    $insert = $block->isNew();
    $block->save();
    $context = array('@type' => $block->bundle(), '%info' => $block->label());
    $logger = $this->logger('block_content');
    $block_type = entity_load('block_content_type', $block->bundle());
    $t_args = array('@type' => $block_type->label(), '%info' => $block->label());

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('@type %info has been created.', $t_args));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('@type %info has been updated.', $t_args));
    }

    if ($block->id()) {
      $form_state['values']['id'] = $block->id();
      $form_state['id'] = $block->id();
      if ($insert) {
        if (!$theme = $block->getTheme()) {
          $theme = $this->config('system.theme')->get('default');
        }
        $form_state['redirect_route'] = array(
          'route_name' => 'block.admin_add',
          'route_parameters' => array(
            'plugin_id' => 'block_content:' . $block->uuid(),
            'theme' => $theme,
          ),
        );
      }
      else {
        $form_state['redirect_route']['route_name'] = 'block_content.list';
      }
    }
    else {
      // In the unlikely case something went wrong on save, the block will be
      // rebuilt and block form redisplayed.
      drupal_set_message($this->t('The block could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->entity->isNew()) {
      $exists = $this->blockContentStorage->loadByProperties(array('info' => $form_state['values']['info']));
      if (!empty($exists)) {
        $form_state->setErrorByName('info', $this->t('A block with description %name already exists.', array(
          '%name' => $form_state['values']['info'][0]['value'],
        )));
      }
    }
  }

}
