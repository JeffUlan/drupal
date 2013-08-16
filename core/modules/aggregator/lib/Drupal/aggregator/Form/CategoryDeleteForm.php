<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\CategoryDeleteForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\aggregator\CategoryStorageControllerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * Provides a confirm delete form.
 */
class CategoryDeleteForm extends ConfirmFormBase implements ControllerInterface {

  /**
   * The category to be deleted.
   *
   * @var array
   */
  protected $category;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The category storage controller.
   *
   * @var \Drupal\aggregator\CategoryStorageControllerInterface
   */
  protected $categoryStorageController;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Creates a new CategoryDeleteForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param CategoryStorageControllerInterface $category_storage_controller
   *   The category storage controller.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityManager $entity_manager, CategoryStorageControllerInterface $category_storage_controller) {
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->categoryStorageController = $category_storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('module_handler'),
      $container->get('plugin.manager.entity'),
      $container->get('aggregator.category.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the category %title?', array('%title' => $this->category->title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/services/aggregator';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'aggregator_category_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This will delete the aggregator category, the menu item for this category, and any related category blocks.');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param int|null $cid
   *   The category ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the cid param or category is not found.
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL, $cid = NULL) {
    $category = $this->categoryStorageController->load($cid);
    if (empty($cid) || empty($category)) {
      throw new NotFoundHttpException();
    }
    $this->category = $category;
    $this->request = $request;
    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $cid = $this->category->cid;
    $title = $this->category->title;
    $this->categoryStorageController->delete($cid);
    // Make sure there is no active block for this category.
    $this->deleteBlocks($cid);
    watchdog('aggregator', 'Category %category deleted.', array('%category' => $title));
    drupal_set_message(t('The category %category has been deleted.', array('%category' => $title)));
    if (preg_match('/^\/admin/', $this->request->getPathInfo())) {
      $form_state['redirect'] = 'admin/config/services/aggregator/';
    }
    else {
      $form_state['redirect'] = 'aggregator';
    }
    $this->updateMenuLink('delete', 'aggregator/categories/' . $cid, $title);
  }

  /**
   * Delete aggregator category blocks.
   *
   * @param int $cid
   *   The category ID.
   */
  protected function deleteBlocks($cid) {
    if ($this->moduleHandler->moduleExists('block')) {
      foreach ($this->entityManager->getStorageController('block')->loadByProperties(array('plugin' => 'aggregator_category_block:' . $cid)) as $block) {
        $block->delete();
      }
    }
  }

  /**
   * Updates a category menu link.
   *
   * @param string $op
   *   The operation to perform.
   * @param string $link_path
   *   The path of the menu link.
   * @param string $title
   *   The title of the menu link.
   *
   * @see menu_link_maintain()
   */
  protected function updateMenuLink($op, $link_path, $title) {
    if (isset($op) && $this->moduleHandler->moduleExists('menu_link')) {
      menu_link_maintain('aggregator', $op, $link_path, $title);
    }
  }

}
