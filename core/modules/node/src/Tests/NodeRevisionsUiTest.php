<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeRevisionsUiTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the UI for controlling node revision behavior.
 *
 * @group node
 */
class NodeRevisionsUiTest extends NodeTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create users.
    $this->editor = $this->drupalCreateUser([
      'administer nodes',
      'edit any page content',
      'view page revisions',
      'access user profiles',
    ]);
  }

  /**
   * Checks that unchecking 'Create new revision' works when editing a node.
   */
  function testNodeFormSaveWithoutRevision() {
    $this->drupalLogin($this->editor);
    $node_storage = $this->container->get('entity.manager')->getStorage('node');

    // Set page revision setting 'create new revision'. This will mean new
    // revisions are created by default when the node is edited.
    $type = NodeType::load('page');
    $type->setNewRevision(TRUE);
    $type->save();

    // Create the node.
    $node = $this->drupalCreateNode();

    // Verify the checkbox is checked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldChecked('edit-revision', "'Create new revision' checkbox is checked");

    // Uncheck the create new revision checkbox and save the node.
    $edit = array('revision' => FALSE);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Load the node again and check the revision is the same as before.
    $node_storage->resetCache(array($node->id()));
    $node_revision = $node_storage->load($node->id(), TRUE);
    $this->assertEqual($node_revision->getRevisionId(), $node->getRevisionId(), "After an existing node is saved with 'Create new revision' unchecked, a new revision is not created.");

    // Verify the checkbox is checked on the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldChecked('edit-revision', "'Create new revision' checkbox is checked");

    // Submit the form without changing the checkbox.
    $edit = array();
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Load the node again and check the revision is different from before.
    $node_storage->resetCache(array($node->id()));
    $node_revision = $node_storage->load($node->id());
    $this->assertNotEqual($node_revision->getRevisionId(), $node->getRevisionId(), "After an existing node is saved with 'Create new revision' checked, a new revision is created.");
  }

  /**
   * Checks HTML double escaping of revision logs.
   */
  public function testNodeRevisionDoubleEscapeFix() {
    $this->drupalLogin($this->editor);
    $nodes = [];

    // Create the node.
    $node = $this->drupalCreateNode();

    $username = [
      '#theme' => 'username',
      '#account' => $this->editor,
    ];
    $editor = \Drupal::service('renderer')->renderPlain($username);

    // Get original node.
    $nodes[] = clone $node;

    // Create revision with a random title and body and update variables.
    $node->title = $this->randomMachineName();
    $node->body = [
      'value' => $this->randomMachineName(32),
      'format' => filter_default_format(),
    ];
    $node->setNewRevision();
    $revision_log = 'Revision <em>message</em> with markup.';
    $node->revision_log->value = $revision_log;
    $node->save();
    // Make sure we get revision information.
    $node = Node::load($node->id());
    $nodes[] = clone $node;

    $this->drupalGet('node/' . $node->id() . '/revisions');

    // Assert the old revision message.
    $date = format_date($nodes[0]->revision_timestamp->value, 'short');
    $url = new Url('entity.node.revision', ['node' => $nodes[0]->id(), 'node_revision' => $nodes[0]->getRevisionId()]);
    $old_revision_message = t('!date by !username', [
      '!date' => \Drupal::l($date, $url),
      '!username' => $editor,
    ]);
    $this->assertRaw($old_revision_message);

    // Assert the current revision message.
    $date = format_date($nodes[1]->revision_timestamp->value, 'short');
    $current_revision_message = t('!date by !username', [
      '!date' => $nodes[1]->link($date),
      '!username' => $editor,
    ]);
    $current_revision_message .= '<p class="revision-log">' . $revision_log . '</p>';
    $this->assertRaw($current_revision_message);
  }

}
