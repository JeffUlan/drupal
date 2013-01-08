<?php

/**
 * @file
 * Definition of Drupal\node\NodeRenderController.
 */

namespace Drupal\node;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\entity\Plugin\Core\Entity\EntityDisplay;

/**
 * Render controller for nodes.
 */
class NodeRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    $return = array();
    if (empty($entities)) {
      return $return;
    }

    // Attach user account.
    user_attach_accounts($entities);

    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $key => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      // The 'view' hook can be implemented to overwrite the default function
      // to display nodes.
      if (node_hook($bundle, 'view')) {
        $entity = node_invoke($entity, 'view', $display, $view_mode, $langcode);
      }

      $entity->content['links'] = array(
        '#theme' => 'links__node',
        '#pre_render' => array('drupal_pre_render_links'),
        '#attributes' => array('class' => array('links', 'inline')),
      );

      // Always display a read more link on teasers because we have no way
      // to know when a teaser view is different than a full view.
      $links = array();
      if ($view_mode == 'teaser') {
        $node_title_stripped = strip_tags($entity->label());
        $links['node-readmore'] = array(
          'title' => t('Read more<span class="element-invisible"> about @title</span>', array(
            '@title' => $node_title_stripped,
          )),
          'href' => 'node/' . $entity->nid,
          'html' => TRUE,
          'attributes' => array(
            'rel' => 'tag',
            'title' => $node_title_stripped,
          ),
        );
      }

      $entity->content['links']['node'] = array(
        '#theme' => 'links__node__node',
        '#links' => $links,
        '#attributes' => array('class' => array('links', 'inline')),
      );

      // Add Language field text element to node render array.
      if ($display->getComponent('language')) {
        $entity->content['language'] = array(
          '#type' => 'item',
          '#title' => t('Language'),
          '#markup' => language_name($langcode),
          '#prefix' => '<div id="field-language-display">',
          '#suffix' => '</div>'
        );
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::alterBuild().
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityDisplay $display, $view_mode, $langcode = NULL) {
    parent::alterBuild($build, $entity, $display, $view_mode, $langcode);
    // Add contextual links for this node, except when the node is already being
    // displayed on its own page. Modules may alter this behavior (for example,
    // to restrict contextual links to certain view modes) by implementing
    // hook_node_view_alter().
    if (!empty($entity->nid) && !($view_mode == 'full' && node_is_page($entity))) {
      $build['#contextual_links']['node'] = array('node', array($entity->nid));
    }
  }

}
