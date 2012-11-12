<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Node\StatusExtraTest.
 */

namespace Drupal\views\Tests\Node;

/**
 * Tests the node.status_extra field handler.
 *
 * @see \Drupal\node\Plugin\views\filter\Status
 */
class StatusExtraTest extends NodeTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Node: Status extra filter',
      'description' => 'Tests the node.status_extra filter handler.',
      'group' => 'Views Modules',
    );
  }

  /**
   * Tests the status extra filter.
   */
  public function testStatusExtra() {
    // @todo For whatever reason the menu has to be rebuilt or drupalGet will
    // fail.
    state()->set('menu_rebuild_needed', TRUE);
    $column_map = array('nid' => 'nid');
    $node_author = $this->drupalCreateUser(array('view own unpublished content'));
    $node_author_not_unpublished = $this->drupalCreateUser();
    $normal_user = $this->drupalCreateUser();
    $admin_user = $this->drupalCreateUser(array('bypass node access'));

    // Create one published and one unpublished node by the admin.
    $node_published = $this->drupalCreateNode(array('uid' => $admin_user->id()));
    $node_unpublished = $this->drupalCreateNode(array('uid' => $admin_user->id(), 'status' => NODE_NOT_PUBLISHED));

    // Create one unpublished node by a certain author user.
    $node_unpublished2 = $this->drupalCreateNode(array('uid' => $node_author->id(), 'status' => NODE_NOT_PUBLISHED));

    // Create one unpublished node by a user who does not have the `view own
    // unpublished content` permission.
    $node_unpublished3 = $this->drupalCreateNode(array('uid' => $node_author_not_unpublished->id(), 'status' => NODE_NOT_PUBLISHED));

    // The administrator should simply see all nodes.
    $this->drupalLogin($admin_user);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertText($node_unpublished->label());
    $this->assertText($node_unpublished2->label());
    $this->assertText($node_unpublished3->label());

    // The node author should see the published node and his own node.
    $this->drupalLogin($node_author);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertNoText($node_unpublished->label());
    $this->assertText($node_unpublished2->label());
    $this->assertNoText($node_unpublished3->label());

    // The normal user should just see the published node.
    $this->drupalLogin($normal_user);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertNoText($node_unpublished->label());
    $this->assertNoText($node_unpublished2->label());
    $this->assertNoText($node_unpublished3->label());

    // The author without the permission to see his own unpublished node should
    // just see the published node.
    $this->drupalLogin($node_author_not_unpublished);
    $this->drupalGet('test_status_extra');
    $this->assertText($node_published->label());
    $this->assertNoText($node_unpublished->label());
    $this->assertNoText($node_unpublished2->label());
    $this->assertNoText($node_unpublished3->label());
  }

}
