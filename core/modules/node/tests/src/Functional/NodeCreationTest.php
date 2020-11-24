<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\Node;

/**
 * Create a node and test saving it.
 *
 * @group node
 */
class NodeCreationTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * Enable dummy module that implements hook_ENTITY_TYPE_insert() for
   * exceptions (function node_test_exception_node_insert() ).
   *
   * @var array
   */
  protected static $modules = [
    'node_test_exception',
    'dblog',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Creates a "Basic page" node and verifies its consistency in the database.
   */
  public function testNodeCreation() {
    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');

    // Test /node/add page with only one content type.
    $node_type_storage->load('article')->delete();
    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('node/add/page');
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, 'Save');

    // Check that the Basic page has been created.
    $this->assertText('Basic page ' . $edit['title[0][value]'] . ' has been created.', 'Basic page created.');

    // Verify that the creation message contains a link to a node.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "node/")]');

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotEmpty($node, 'Node found in database.');

    // Verify that pages do not show submitted information by default.
    $this->drupalGet('node/' . $node->id());
    $this->assertNoText($node->getOwner()->getAccountName());
    $this->assertNoText($this->container->get('date.formatter')->format($node->getCreatedTime()));

    // Change the node type setting to show submitted by information.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $node_type_storage->load('page');
    $node_type->setDisplaySubmitted(TRUE);
    $node_type->save();

    $this->drupalGet('node/' . $node->id());
    $this->assertText($node->getOwner()->getAccountName());
    $this->assertText($this->container->get('date.formatter')->format($node->getCreatedTime()));

    // Check if the node revision checkbox is not rendered on node creation form.
    $admin_user = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('node/add/page');
    $this->assertSession()->fieldNotExists('edit-revision', NULL);
  }

  /**
   * Verifies that a transaction rolls back the failed creation.
   */
  public function testFailedPageCreation() {
    // Create a node.
    $edit = [
      'uid'      => $this->loggedInUser->id(),
      'name'     => $this->loggedInUser->name,
      'type'     => 'page',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'title'    => 'testing_transaction_exception',
    ];

    try {
      // An exception is generated by node_test_exception_node_insert() if the
      // title is 'testing_transaction_exception'.
      Node::create($edit)->save();
      $this->fail('Expected exception has not been thrown.');
    }
    catch (\Exception $e) {
      // Expected exception; just continue testing.
    }

    // Check that the node does not exist in the database.
    $node = $this->drupalGetNodeByTitle($edit['title']);
    $this->assertFalse($node);

    // Check that the rollback error was logged.
    $records = static::getWatchdogIdsForTestExceptionRollback();
    // Verify that the rollback explanatory error was logged.
    $this->assertNotEmpty($records);
  }

  /**
   * Creates an unpublished node and confirms correct redirect behavior.
   */
  public function testUnpublishedNodeCreation() {
    // Set the front page to the test page.
    $this->config('system.site')->set('page.front', '/test-page')->save();

    // Set "Basic page" content type to be unpublished by default.
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    $fields['status']->getConfig('page')
      ->setDefaultValue(FALSE)
      ->save();

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/page', $edit, 'Save');

    // Check that the user was redirected to the home page.
    $this->assertSession()->addressEquals('');
    $this->assertText('Test page text');

    // Confirm that the node was created.
    $this->assertText('Basic page ' . $edit['title[0][value]'] . ' has been created.');

    // Verify that the creation message contains a link to a node.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "node/")]');
  }

  /**
   * Creates nodes with different authored dates.
   */
  public function testAuthoredDate() {
    $now = \Drupal::time()->getRequestTime();
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Create a node with the default creation date.
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(16),
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull($node->getCreatedTime());

    // Create a node with the custom creation date in the past.
    $date = $now - 86400;
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(16),
      'created[0][value][date]' => date('Y-m-d', $date),
      'created[0][value][time]' => date('H:i:s', $date),
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertEquals($date, $node->getCreatedTime());

    // Create a node with the custom creation date in the future.
    $date = $now + 86400;
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(16),
      'created[0][value][date]' => date('Y-m-d', $date),
      'created[0][value][time]' => date('H:i:s', $date),
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertEquals($date, $node->getCreatedTime());

    // Test an invalid date.
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(16),
      'created[0][value][date]' => '2013-13-13',
      'created[0][value][time]' => '11:00:00',
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');
    $this->assertSession()->pageTextContains('The Authored on date is invalid.');
    $this->assertFalse($this->drupalGetNodeByTitle($edit['title[0][value]']));

    // Test an invalid time.
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(16),
      'created[0][value][date]' => '2012-01-01',
      'created[0][value][time]' => '30:00:00',
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');
    $this->assertSession()->pageTextContains('The Authored on date is invalid.');
    $this->assertFalse($this->drupalGetNodeByTitle($edit['title[0][value]']));
  }

  /**
   * Tests the author autocompletion textfield.
   */
  public function testAuthorAutocomplete() {
    $admin_user = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('node/add/page');

    $result = $this->xpath('//input[@id="edit-uid-0-value" and contains(@data-autocomplete-path, "user/autocomplete")]');
    $this->assertCount(0, $result, 'No autocompletion without access user profiles.');

    $admin_user = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'access user profiles',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('node/add/page');

    $result = $this->xpath('//input[@id="edit-uid-0-target-id" and contains(@data-autocomplete-path, "/entity_reference_autocomplete/user/default")]');
    $this->assertCount(1, $result, 'Ensure that the user does have access to the autocompletion');
  }

  /**
   * Check node/add when no node types exist.
   */
  public function testNodeAddWithoutContentTypes() {
    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefNotExists('/admin/structure/types/add');

    // Test /node/add page without content types.
    foreach (\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple() as $entity) {
      $entity->delete();
    }

    $this->drupalGet('node/add');
    $this->assertSession()->statusCodeEquals(403);

    $admin_content_types = $this->drupalCreateUser([
      'administer content types',
    ]);
    $this->drupalLogin($admin_content_types);

    $this->drupalGet('node/add');

    $this->assertSession()->linkByHrefExists('/admin/structure/types/add');
  }

  /**
   * Gets the watchdog IDs of the records with the rollback exception message.
   *
   * @return int[]
   *   Array containing the IDs of the log records with the rollback exception
   *   message.
   */
  protected static function getWatchdogIdsForTestExceptionRollback() {
    // PostgreSQL doesn't support bytea LIKE queries, so we need to unserialize
    // first to check for the rollback exception message.
    $matches = [];
    $query = Database::getConnection()->select('watchdog', 'w')
      ->fields('w', ['wid', 'variables'])
      ->execute();
    foreach ($query as $row) {
      $variables = (array) unserialize($row->variables);
      if (isset($variables['@message']) && $variables['@message'] === 'Test exception for rollback.') {
        $matches[] = $row->wid;
      }
    }
    return $matches;
  }

}
