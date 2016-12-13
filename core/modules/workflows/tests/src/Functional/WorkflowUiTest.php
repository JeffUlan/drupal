<?php

namespace Drupal\Tests\workflows\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests workflow creation UI.
 *
 * @group workflows
 */
class WorkflowUiTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['workflows', 'workflow_type_test', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // We're testing local actions.
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests route access/permissions.
   */
  public function testAccess() {
    // Create a minimal workflow for testing.
    $workflow = Workflow::create(['id' => 'test', 'type' => 'workflow_type_test']);
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft', 'published'], 'published')
      ->save();

    $paths = [
      'admin/config/workflow/workflows',
      'admin/config/workflow/workflows/add',
      'admin/config/workflow/workflows/manage/test',
      'admin/config/workflow/workflows/manage/test/delete',
      'admin/config/workflow/workflows/manage/test/add_state',
      'admin/config/workflow/workflows/manage/test/state/published',
      'admin/config/workflow/workflows/manage/test/state/published/delete',
      'admin/config/workflow/workflows/manage/test/add_transition',
      'admin/config/workflow/workflows/manage/test/transition/publish',
      'admin/config/workflow/workflows/manage/test/transition/publish/delete',
    ];

    foreach ($paths as $path) {
      $this->drupalGet($path);
      // No access.
      $this->assertSession()->statusCodeEquals(403);
    }
    $this->drupalLogin($this->createUser(['administer workflows']));
    foreach ($paths as $path) {
      $this->drupalGet($path);
      // User has access.
      $this->assertSession()->statusCodeEquals(200);
    }

    // Delete one of the states and ensure the other test cannot be deleted.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/published/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/published/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the creation of a workflow through the UI.
   */
  public function testWorkflowCreation() {
    $workflow_storage = $this->container->get('entity_type.manager')->getStorage('workflow');
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $this->drupalLogin($this->createUser(['access administration pages', 'administer workflows']));
    $this->drupalGet('admin/config/workflow');
    $this->assertSession()->linkByHrefExists('admin/config/workflow/workflows');
    $this->clickLink('Workflows');
    $this->assertSession()->pageTextContains('There is no Workflow yet.');
    $this->clickLink('Add workflow');
    $this->submitForm(['label' => 'Test', 'id' => 'test', 'workflow_type' => 'workflow_type_test'], 'Save');
    $this->assertSession()->pageTextContains('Created the Test Workflow.');
    $this->assertSession()->addressEquals('admin/config/workflow/workflows/manage/test/add_state');
    $this->drupalGet('/admin/config/workflow/workflows/manage/test');
    $this->assertSession()->pageTextContains('This workflow has no states and will be disabled until there is at least one, add a new state.');
    $this->assertSession()->pageTextContains('There are no states yet.');
    $this->clickLink('Add a new state');
    $this->submitForm(['label' => 'Published', 'id' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Created Published state.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->getState('published')->canTransitionTo('published'), 'No default transition from published to published exists.');

    $this->clickLink('Add a new state');
    // Don't create a draft to draft transition by default.
    $this->submitForm(['label' => 'Draft', 'id' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('Created Draft state.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->getState('draft')->canTransitionTo('draft'), 'Can not transition from draft to draft');

    $this->clickLink('Add a new transition');
    $this->submitForm(['id' => 'publish', 'label' => 'Publish', 'from[draft]' => 'draft', 'to' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Created Publish transition.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->getState('draft')->canTransitionTo('published'), 'Can transition from draft to published');

    $this->clickLink('Add a new transition');
    $this->submitForm(['id' => 'create_new_draft', 'label' => 'Create new draft', 'from[draft]' => 'draft', 'to' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('Created Create new draft transition.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->getState('draft')->canTransitionTo('draft'), 'Can transition from draft to draft');

    // The fist state to edit on the page should be published.
    $this->clickLink('Edit');
    $this->assertSession()->fieldValueEquals('label', 'Published');
    // Change the label.
    $this->submitForm(['label' => 'Live'], 'Save');
    $this->assertSession()->pageTextContains('Saved Live state.');

    // Allow published to draft.
    $this->clickLink('Edit', 3);
    $this->submitForm(['from[published]' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Saved Create new draft transition.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->getState('published')->canTransitionTo('draft'), 'Can transition from published to draft');

    // Try creating a duplicate transition.
    $this->clickLink('Add a new transition');
    $this->submitForm(['id' => 'create_new_draft', 'label' => 'Create new draft', 'from[published]' => 'published', 'to' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
    // Try creating a transition which duplicates the states of another.
    $this->submitForm(['id' => 'create_new_draft2', 'label' => 'Create new draft again', 'from[published]' => 'published', 'to' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The transition from Live to Draft already exists.');

    // Create a new transition.
    $this->submitForm(['id' => 'save_and_publish', 'label' => 'Save and publish', 'from[published]' => 'published', 'to' => 'published'], 'Save');
    $this->assertSession()->pageTextContains('Created Save and publish transition.');
    // Edit the new transition and try to add an existing transition.
    $this->clickLink('Edit', 4);
    $this->submitForm(['from[draft]' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The transition from Draft to Live already exists.');

    // Delete the transition.
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertTrue($workflow->hasTransitionFromStateToState('published', 'published'), 'Can transition from published to published');
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete Save and publish from Test?');
    $this->submitForm([], 'Delete');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->hasTransitionFromStateToState('published', 'published'), 'Cannot transition from published to published');

    // Try creating a duplicate state.
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $this->clickLink('Add a new state');
    $this->submitForm(['label' => 'Draft', 'id' => 'draft'], 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Ensure that weight changes the state ordering.
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals('published', $workflow->getInitialState()->id());
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $this->submitForm(['states[draft][weight]' => '-1'], 'Save');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals('draft', $workflow->getInitialState()->id());

    // This will take us to the list of workflows, so we need to edit the
    // workflow again.
    $this->clickLink('Edit');

    // Ensure that weight changes the transition ordering.
    $this->assertEquals(['publish', 'create_new_draft'], array_keys($workflow->getTransitions()));
    $this->drupalGet('admin/config/workflow/workflows/manage/test');
    $this->submitForm(['transitions[create_new_draft][weight]' => '-1'], 'Save');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals(['create_new_draft', 'publish'], array_keys($workflow->getTransitions()));

    // This will take us to the list of workflows, so we need to edit the
    // workflow again.
    $this->clickLink('Edit');

    // Ensure that a delete link for the published state exists before deleting
    // the draft state.
    $published_delete_link = Url::fromRoute('entity.workflow.delete_state_form', [
      'workflow' => $workflow->id(),
      'workflow_state' => 'published'
    ])->toString();
    $this->assertSession()->linkByHrefExists($published_delete_link);

    // Delete the Draft state.
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete Draft from Test?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('State Draft deleted.');
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertFalse($workflow->hasState('draft'), 'Draft state deleted');
    $this->assertTrue($workflow->hasState('published'), 'Workflow still has published state');

    // The last state cannot be deleted so the only delete link on the page will
    // be for the workflow.
    $this->assertSession()->linkByHrefNotExists($published_delete_link);
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('Are you sure you want to delete Test?');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Workflow Test deleted.');
    $this->assertSession()->pageTextContains('There is no Workflow yet.');
    $this->assertNull($workflow_storage->loadUnchanged('test'), 'The test workflow has been deleted');
  }

  /**
   * Tests that workflow types can add form fields to states and transitions.
   */
  public function testWorkflowDecoration() {
    // Create a minimal workflow for testing.
    $workflow = Workflow::create(['id' => 'test', 'type' => 'workflow_type_complex_test']);
    $workflow
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['published'], 'published')
      ->save();

    $this->assertEquals('', $workflow->getState('published')->getExtra());
    $this->assertEquals('', $workflow->getTransition('publish')->getExtra());

    $this->drupalLogin($this->createUser(['administer workflows']));

    // Add additional state information when editing.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/published');
    $this->assertSession()->pageTextContains('Extra information added to state');
    $this->submitForm(['type_settings[workflow_type_complex_test][extra]' => 'Extra state information'], 'Save');

    // Add additional transition information when editing.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/transition/publish');
    $this->assertSession()->pageTextContains('Extra information added to transition');
    $this->submitForm(['type_settings[workflow_type_complex_test][extra]' => 'Extra transition information'], 'Save');

    $workflow_storage = $this->container->get('entity_type.manager')->getStorage('workflow');
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals('Extra state information', $workflow->getState('published')->getExtra());
    $this->assertEquals('Extra transition information', $workflow->getTransition('publish')->getExtra());

    // Add additional state information when adding.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/add_state');
    $this->assertSession()->pageTextContains('Extra information added to state');
    $this->submitForm(['label' => 'Draft', 'id' => 'draft', 'type_settings[workflow_type_complex_test][extra]' => 'Extra state information on add'], 'Save');

    // Add additional transition information when adding.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/add_transition');
    $this->assertSession()->pageTextContains('Extra information added to transition');
    $this->submitForm(['id' => 'draft_published', 'label' => 'Publish', 'from[draft]' => 'draft', 'to' => 'published', 'type_settings[workflow_type_complex_test][extra]' => 'Extra transition information on add'], 'Save');

    $workflow = $workflow_storage->loadUnchanged('test');
    $this->assertEquals('Extra state information on add', $workflow->getState('draft')->getExtra());
    $this->assertEquals('Extra transition information on add', $workflow->getTransition('draft_published')->getExtra());
  }

}
