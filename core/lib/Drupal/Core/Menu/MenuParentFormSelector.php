<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuParentFormSelector.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Default implementation of the menu parent form selector service.
 *
 * The form selector is a list of all appropriate menu links.
 */
class MenuParentFormSelector implements MenuParentFormSelectorInterface {
  use StringTranslationTrait;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\Core\Menu\MenuParentFormSelector
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(MenuLinkTreeInterface $menu_link_tree, EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->menuLinkTree = $menu_link_tree;
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentSelectOptions($id = '', array $menus = NULL) {
    if (!isset($menus)) {
      $menus = $this->getMenuOptions();
    }

    $options = array();
    $depth_limit = $this->getParentDepthLimit($id);
    foreach ($menus as $menu_name => $menu_title) {
      $options[$menu_name . ':'] = '<' . $menu_title . '>';

      $parameters = new MenuTreeParameters();
      $parameters->setMaxDepth($depth_limit);
      $tree = $this->menuLinkTree->load($menu_name, $parameters);
      $manipulators = array(
        array('callable' => 'menu.default_tree_manipulators:checkNodeAccess'),
        array('callable' => 'menu.default_tree_manipulators:checkAccess'),
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
      );
      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      $this->parentSelectOptionsTreeWalk($tree, $menu_name, '--', $options, $id, $depth_limit);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL) {
    $options = $this->getParentSelectOptions($id, $menus);
    // If no options were found, there is nothing to select.
    if ($options) {
      if (!isset($options[$menu_parent])) {
        // Try putting it at the top level in the current menu.
        list($menu_name, $parent) = explode(':', $menu_parent, 2);
        $menu_parent = $menu_name . ':';
      }
      if (isset($options[$menu_parent])) {
        return array(
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $menu_parent,
        );
      }
    }
    return array();
  }

  /**
   * Returns the maximum depth of the possible parents of the menu link.
   *
   * @param string $id
   *   The menu link plugin ID or an empty value for a new link.
   *
   * @return int
   *   The depth related to the depth of the given menu link.
   */
  protected function getParentDepthLimit($id) {
    if ($id) {
      $limit = $this->menuLinkTree->maxDepth() - $this->menuLinkTree->getSubtreeHeight($id);
    }
    else {
      $limit = $this->menuLinkTree->maxDepth() - 1;
    }
    return $limit;
  }

  /**
   * Iterates over all items in the tree to prepare the parents select options.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu tree.
   * @param string $menu_name
   *   The menu name.
   * @param string $indent
   *   The indentation string used for the label.
   * @param array $options
   *   The select options.
   * @param string $exclude
   *   An excluded menu link.
   * @param int $depth_limit
   *   The maximum depth of menu links considered for the select options.
   */
  protected function parentSelectOptionsTreeWalk(array $tree, $menu_name, $indent, array &$options, $exclude, $depth_limit) {
    foreach ($tree as $element) {
      if ($element->depth > $depth_limit) {
        // Don't iterate through any links on this level.
        break;
      }
      $link = $element->link;
      if ($link->getPluginId() != $exclude) {
        $title = $indent . ' ' . Unicode::truncate($link->getTitle(), 30, TRUE, FALSE);
        if (!$link->isEnabled()) {
          $title .= ' (' . $this->t('disabled') . ')';
        }
        $options[$menu_name . ':' . $link->getPluginId()] = $title;
        if (!empty($element->subtree)) {
          $this->parentSelectOptionsTreeWalk($element->subtree, $menu_name, $indent . '--', $options, $exclude, $depth_limit);
        }
      }
    }
  }

  /**
   * Gets a list of menu names for use as options.
   *
   * @param array $menu_names
   *   (optional) Array of menu names to limit the options, or NULL to load all.
   *
   * @return array
   *   Keys are menu names (ids) values are the menu labels.
   */
  protected function getMenuOptions(array $menu_names = NULL) {
    $menus = $this->entityManager->getStorage('menu')->loadMultiple($menu_names);
    $options = array();
    /** @var \Drupal\system\MenuInterface[] $menus */
    foreach ($menus as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    return $options;
  }

}
