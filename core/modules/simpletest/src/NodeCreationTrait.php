<?php
/**
 * @file
 * Contains Drupal\simpletest\NodeCreationTrait
 */

namespace Drupal\simpletest;

use Drupal\node\Entity\Node;

/**
 * Provides methods to create node based on default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait NodeCreationTrait {

  /**
   * Get a node from the database based on its title.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $title
   *   A node title, usually generated by $this->randomMachineName().
   * @param $reset
   *   (optional) Whether to reset the entity cache.
   *
   * @return \Drupal\node\NodeInterface
   *   A node entity matching $title.
   */
  function getNodeByTitle($title, $reset = FALSE) {
    if ($reset) {
      \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    }
    // Cast MarkupInterface objects to string.
    $title = (string) $title;
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    // Load the first node returned from the database.
    $returned_node = reset($nodes);
    return $returned_node;
  }

  /**
   * Creates a node based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the node, as used in
   *   entity_create(). Override the defaults by specifying the key and value
   *   in the array, for example:
   *   @code
   *     $this->drupalCreateNode(array(
   *       'title' => t('Hello, world!'),
   *       'type' => 'article',
   *     ));
   *   @endcode
   *   The following defaults are provided:
   *   - body: Random string using the default filter format:
   *     @code
   *       $settings['body'][0] = array(
   *         'value' => $this->randomMachineName(32),
   *         'format' => filter_default_format(),
   *       );
   *     @endcode
   *   - title: Random string.
   *   - type: 'page'.
   *   - uid: The currently logged in user, or anonymous.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createNode(array $settings = array()) {
    // Populate defaults array.
    $settings += array(
      'body'      => array(array(
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      )),
      'title'     => $this->randomMachineName(8),
      'type'      => 'page',
      'uid'       => \Drupal::currentUser()->id(),
    );
    $node = Node::create($settings);
    $node->save();

    return $node;
  }

}
