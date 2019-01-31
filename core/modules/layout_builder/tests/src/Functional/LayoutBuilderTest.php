<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\layout_builder\Section;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the Layout Builder UI.
 *
 * @group layout_builder
 */
class LayoutBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'layout_builder',
    'layout_builder_views_test',
    'layout_test',
    'block',
    'block_test',
    'node',
    'layout_builder_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');

    // Create two nodes.
    $this->createContentType(['type' => 'bundle_with_section_field']);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The first node title',
      'body' => [
        [
          'value' => 'The first node body',
        ],
      ],
    ]);
    $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'The second node title',
      'body' => [
        [
          'value' => 'The second node body',
        ],
      ],
    ]);
  }

  /**
   * Tests functionality of Layout Builder for overrides.
   */
  public function testOverrides() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    // From the manage display page, go to manage the layout.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->drupalPostForm(NULL, ['layout[enabled]' => TRUE], 'Save');
    $this->drupalPostForm(NULL, ['layout[allow_custom]' => TRUE], 'Save');
    // @todo This should not be necessary.
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    // Add a block with a custom label.
    $this->drupalGet('node/1');
    $page->clickLink('Layout');
    $page->clickLink('Add Block');
    $page->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is an override');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add Block');
    $page->clickLink('Save Layout');
    $assert_session->pageTextContains('This is an override');

    // Get the UUID of the component.
    $components = Node::load(1)->get('layout_builder__layout')->getSection(0)->getComponents();
    end($components);
    $uuid = key($components);

    $this->drupalGet('layout_builder/update/block/overrides/node.1/0/content/' . $uuid);
    $page->uncheckField('settings[label_display]');
    $page->pressButton('Update');
    $assert_session->pageTextNotContains('This is an override');
    $page->clickLink('Save Layout');
    $assert_session->pageTextNotContains('This is an override');
  }

  /**
   * {@inheritdoc}
   */
  public function testLayoutBuilderUi() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->linkNotExists('Layout');

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';

    // From the manage display page, go to manage the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->linkNotExists('Manage layout');
    $assert_session->fieldDisabled('layout[allow_custom]');

    $this->drupalPostForm(NULL, ['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals("$field_ui_prefix/display-layout/default");
    // The body field is only present once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
    // The extra field is only present once.
    $assert_session->pageTextContainsOnce('Placeholder for the "Extra label" field');
    // Save the defaults.
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->addressEquals("$field_ui_prefix/display/default");

    // Load the default layouts again after saving to confirm fields are only
    // added on new layouts.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->addressEquals("$field_ui_prefix/display-layout/default");
    // The body field is only present once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
    // The extra field is only present once.
    $assert_session->pageTextContainsOnce('Placeholder for the "Extra label" field');

    // Add a new block.
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the label');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add Block');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('This is the label');
    $assert_session->addressEquals("$field_ui_prefix/display-layout/default");

    // Save the defaults.
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->pageTextContains('The layout has been saved.');
    $assert_session->addressEquals("$field_ui_prefix/display/default");

    // The node uses the defaults, no overrides available.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');
    $assert_session->linkNotExists('Layout');

    // Enable overrides.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1');

    // Remove the section from the defaults.
    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $assert_session->pageTextContains('Placeholder for the "Extra label" field');
    $assert_session->linkExists('Remove section');
    $this->clickLink('Remove section');
    $page->pressButton('Remove');

    // Add a new section.
    $this->clickLink('Add Section');
    $this->assertCorrectLayouts();
    $assert_session->linkExists('Two column');
    $this->clickLink('Two column');
    $assert_session->buttonExists('Add section');
    $page->pressButton('Add section');
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->pageTextNotContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');

    // Assert that overrides cannot be turned off while overrides exist.
    $this->drupalGet("$field_ui_prefix/display/default");
    $assert_session->checkboxChecked('layout[allow_custom]');
    $assert_session->fieldDisabled('layout[allow_custom]');

    // Alter the defaults.
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('Title');
    $this->clickLink('Title');
    $page->pressButton('Add Block');
    // The title field is present.
    $assert_session->elementExists('css', '.field--name-title');
    $this->clickLink('Save Layout');

    // View the other node, which is still using the defaults.
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The second node title');
    $assert_session->pageTextContains('The second node body');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');

    // The overridden node does not pick up the changes to defaults.
    $this->drupalGet('node/1');
    $assert_session->elementNotExists('css', '.field--name-title');
    $assert_session->pageTextNotContains('The first node body');
    $assert_session->pageTextNotContains('Powered by Drupal');
    $assert_session->pageTextNotContains('Extra, Extra read all about it.');
    $assert_session->pageTextNotContains('Placeholder for the "Extra label" field');
    $assert_session->linkExists('Layout');

    // Reverting the override returns it to the defaults.
    $this->clickLink('Layout');
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('ID');
    $this->clickLink('ID');
    $page->pressButton('Add Block');
    // The title field is present.
    $assert_session->elementExists('css', '.field--name-nid');
    $assert_session->pageTextContains('ID');
    $assert_session->pageTextContains('1');
    $assert_session->linkExists('Revert to defaults');
    $this->clickLink('Revert to defaults');
    $page->pressButton('Revert');
    $assert_session->pageTextContains('The layout has been reverted back to defaults.');
    $assert_session->elementExists('css', '.field--name-title');
    $assert_session->elementNotExists('css', '.field--name-nid');
    $assert_session->pageTextContains('The first node body');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('Placeholder for the "Extra label" field');

    // Assert that overrides can be turned off now that all overrides are gone.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => FALSE], 'Save');
    $this->drupalGet('node/1');
    $assert_session->linkNotExists('Layout');

    // Add a new field.
    $edit = [
      'new_storage_type' => 'string',
      'label' => 'My text field',
      'field_name' => 'my_text',
    ];
    $this->drupalPostForm("$field_ui_prefix/fields/add-field", $edit, 'Save and continue');
    $page->pressButton('Save field settings');
    $page->pressButton('Save settings');
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $assert_session->pageTextContains('My text field');
    $assert_session->elementExists('css', '.field--name-field-my-text');

    // Delete the field.
    $this->drupalPostForm("$field_ui_prefix/fields/node.bundle_with_section_field.field_my_text/delete", [], 'Delete');
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $assert_session->pageTextNotContains('My text field');
    $assert_session->elementNotExists('css', '.field--name-field-my-text');
  }

  /**
   * Tests that a non-default view mode works as expected.
   */
  public function testNonDefaultViewMode() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Allow overrides for the layout.
    $this->drupalGet("$field_ui_prefix/display/default");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    $this->clickLink('Manage layout');
    // Confirm the body field only is shown once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
    $this->clickLink('Discard changes');
    $page->pressButton('Confirm');

    $this->clickLink('Teaser');
    // Enabling Layout Builder for the default mode does not affect the teaser.
    $assert_session->addressEquals("$field_ui_prefix/display/teaser");
    $assert_session->elementNotExists('css', '#layout-builder__layout');
    $assert_session->checkboxNotChecked('layout[enabled]');
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $assert_session->linkExists('Manage layout');
    $page->clickLink('Manage layout');
    // Confirm the body field only is shown once.
    $assert_session->elementsCount('css', '.field--name-body', 1);

    // Enable a disabled view mode.
    $page->clickLink('Discard changes');
    $page->pressButton('Confirm');
    $assert_session->addressEquals("$field_ui_prefix/display/teaser");
    $page->clickLink('Default');
    $assert_session->addressEquals("$field_ui_prefix/display");
    $assert_session->linkNotExists('Full content');
    $page->checkField('display_modes_custom[full]');
    $page->pressButton('Save');

    $assert_session->linkExists('Full content');
    $page->clickLink('Full content');
    $assert_session->addressEquals("$field_ui_prefix/display/full");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $assert_session->linkExists('Manage layout');
    $page->clickLink('Manage layout');
    // Confirm the body field only is shown once.
    $assert_session->elementsCount('css', '.field--name-body', 1);
  }

  /**
   * Tests that component's dependencies are respected during removal.
   */
  public function testPluginDependencies() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->container->get('module_installer')->install(['menu_ui']);
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer menu',
    ]));

    // Create a new menu.
    $this->drupalGet('admin/structure/menu/add');
    $page->fillField('label', 'My Menu');
    $page->fillField('id', 'mymenu');
    $page->pressButton('Save');
    $this->drupalGet('admin/structure/menu/add');
    $page->fillField('label', 'My Menu');
    $page->fillField('id', 'myothermenu');
    $page->pressButton('Save');

    $page->clickLink('Add link');
    $page->fillField('title[0][value]', 'My link');
    $page->fillField('link[0][uri]', '/');
    $page->pressButton('Save');

    $this->drupalPostForm('admin/structure/types/manage/bundle_with_section_field/display', ['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');
    $assert_session->linkExists('Add Section');
    $this->clickLink('Add Section');
    $assert_session->linkExists('Layout plugin (with dependencies)');
    $this->clickLink('Layout plugin (with dependencies)');
    $assert_session->elementExists('css', '.layout--layout-test-dependencies-plugin');
    $assert_session->elementExists('css', '.field--name-body');
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');
    $this->drupalPostForm('admin/structure/menu/manage/myothermenu/delete', [], 'Delete');
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display-layout/default');
    $assert_session->elementNotExists('css', '.layout--layout-test-dependencies-plugin');
    $assert_session->elementExists('css', '.field--name-body');

    // Add a menu block.
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('My Menu');
    $this->clickLink('My Menu');
    $page->pressButton('Add Block');

    // Add another block alongside the menu.
    $assert_session->linkExists('Add Block');
    $this->clickLink('Add Block');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $page->pressButton('Add Block');

    // Assert that the blocks are visible, and save the layout.
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextContains('My Menu');
    $assert_session->elementExists('css', '.block.menu--mymenu');
    $assert_session->linkExists('Save Layout');
    $this->clickLink('Save Layout');

    // Delete the menu.
    $this->drupalPostForm('admin/structure/menu/manage/mymenu/delete', [], 'Delete');

    // Ensure that the menu block is gone, but that the other block remains.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display-layout/default');
    $assert_session->pageTextContains('Powered by Drupal');
    $assert_session->pageTextNotContains('My Menu');
    $assert_session->elementNotExists('css', '.block.menu--mymenu');
  }

  /**
   * Tests the interaction between full and default view modes.
   *
   * @see \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage::getDefaultSectionStorage()
   */
  public function testLayoutBuilderUiFullViewMode() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Allow overrides for the layout.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[enabled]' => TRUE], 'Save');
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => TRUE], 'Save');

    // Customize the default view mode.
    $this->drupalGet("$field_ui_prefix/display-layout/default");
    $this->clickLink('Add Block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the default view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add Block');
    $assert_session->pageTextContains('This is the default view mode');
    $this->clickLink('Save Layout');

    // The default view mode is used for both the node display and layout UI.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $this->clickLink('Discard changes');
    $page->pressButton('Confirm');

    // Enable the full view mode and customize it.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['display_modes_custom[full]' => TRUE], 'Save');
    $this->drupalPostForm("$field_ui_prefix/display/full", ['layout[enabled]' => TRUE], 'Save');
    $this->drupalGet("$field_ui_prefix/display-layout/full");
    $this->clickLink('Add Block');
    $this->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is the full view mode');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add Block');
    $assert_session->pageTextContains('This is the full view mode');
    $this->clickLink('Save Layout');

    // The full view mode is now used for both the node display and layout UI.
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the full view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the full view mode');
    $this->clickLink('Discard changes');
    $page->pressButton('Confirm');

    // Disable the full view mode, the default should be used again.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['display_modes_custom[full]' => FALSE], 'Save');
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('This is the default view mode');
    $this->drupalGet('node/1/layout');
    $assert_session->pageTextContains('This is the default view mode');
    $this->clickLink('Discard changes');
    $page->pressButton('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function testLayoutBuilderChooseBlocksAlter() {
    // See layout_builder_test_plugin_filter_block__layout_builder_alter().
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    // From the manage display page, go to manage the layout.
    $this->drupalPostForm('admin/structure/types/manage/bundle_with_section_field/display/default', ['layout[enabled]' => TRUE], 'Save');
    $assert_session->linkExists('Manage layout');
    $this->clickLink('Manage layout');

    // Add a new block.
    $this->clickLink('Add Block');

    // Verify that blocks not modified are present.
    $assert_session->linkExists('Powered by Drupal');
    $assert_session->linkExists('Default revision');

    // Verify that blocks explicitly removed are not present.
    $assert_session->linkNotExists('Help');
    $assert_session->linkNotExists('Sticky at top of lists');
    $assert_session->linkNotExists('Page title');

    // Verify that Changed block is not present on first section.
    $assert_session->linkNotExists('Changed');

    // Go back to Manage layout.
    $this->drupalGet('admin/structure/types/manage/bundle_with_section_field/display/default');
    $this->clickLink('Manage layout');

    // Add a new section.
    $this->clickLink('Add Section', 1);
    $assert_session->linkExists('Two column');
    $this->clickLink('Two column');
    $assert_session->buttonExists('Add section');
    $this->getSession()->getPage()->pressButton('Add section');
    // Add a new block to second section.
    $this->clickLink('Add Block', 1);

    // Verify that Changed block is present on second section.
    $assert_session->linkExists('Changed');
  }

  /**
   * Tests that extra fields work before and after enabling Layout Builder.
   */
  public function testExtraFields() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->drupalCreateUser(['administer node display']));

    $this->drupalGet('node');
    $assert_session->linkExists('Read more');

    $this->drupalPostForm('admin/structure/types/manage/bundle_with_section_field/display/default', ['layout[enabled]' => TRUE], 'Save');

    $this->drupalGet('node');
    $assert_session->linkExists('Read more');
  }

  /**
   * Tests that deleting a View block used in Layout Builder works.
   */
  public function testDeletedView() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    // Enable overrides.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[enabled]' => TRUE], 'Save');
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => TRUE], 'Save');
    $this->drupalGet('node/1');

    $assert_session->linkExists('Layout');
    $this->clickLink('Layout');
    $this->clickLink('Add Block');
    $this->clickLink('Test Block View');
    $page->pressButton('Add Block');

    $assert_session->pageTextContains('Test Block View');
    $assert_session->elementExists('css', '.block-views-blocktest-block-view-block-1');
    $this->clickLink('Save Layout');
    $assert_session->pageTextContains('Test Block View');
    $assert_session->elementExists('css', '.block-views-blocktest-block-view-block-1');

    View::load('test_block_view')->delete();
    $this->drupalGet('node/1');
    // Node can be loaded after deleting the View.
    $assert_session->pageTextContains(Node::load(1)->getTitle());
    $assert_session->pageTextNotContains('Test Block View');
  }

  /**
   * Tests that hook_form_alter() has access to the Layout Builder info.
   */
  public function testFormAlter() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalGet("$field_ui_prefix/display/default");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');

    $page->clickLink('Manage layout');
    $page->clickLink('Add Block');
    $page->clickLink('Powered by Drupal');
    $assert_session->pageTextContains('Layout Builder Storage: node.bundle_with_section_field.default');
    $assert_session->pageTextContains('Layout Builder Section: layout_onecol');
    $assert_session->pageTextContains('Layout Builder Component: system_powered_by_block');
  }

  /**
   * Tests the usage of placeholders for empty blocks.
   *
   * @see \Drupal\Core\Block\BlockPluginInterface::getPlaceholderString()
   * @see \Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray::onBuildRender()
   */
  public function testBlockPlaceholder() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
    ]));

    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field';
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[enabled]' => TRUE], 'Save');

    // Customize the default view mode.
    $this->drupalGet("$field_ui_prefix/display-layout/default");

    // Add a block whose content is controlled by state and is empty by default.
    $this->clickLink('Add Block');
    $this->clickLink('Test block caching');
    $page->fillField('settings[label]', 'The block label');
    $page->pressButton('Add Block');

    $block_content = 'I am content';
    $placeholder_content = 'Placeholder for the "The block label" block';

    // The block placeholder is displayed and there is no content.
    $assert_session->pageTextContains($placeholder_content);
    $assert_session->pageTextNotContains($block_content);

    // Set block content and reload the page.
    \Drupal::state()->set('block_test.content', $block_content);
    $this->getSession()->reload();

    // The block placeholder is no longer displayed and the content is visible.
    $assert_session->pageTextNotContains($placeholder_content);
    $assert_session->pageTextContains($block_content);
  }

  /**
   * Tests the Block UI when Layout Builder is installed.
   */
  public function testBlockUiListing() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
    ]));

    $this->drupalGet('admin/structure/block');
    $page->clickLink('Place block');

    // Ensure that blocks expected to appear are available.
    $assert_session->pageTextContains('Test HTML block');
    $assert_session->pageTextContains('Block test');
    // Ensure that blocks not expected to appear are not available.
    $assert_session->pageTextNotContains('Body');
    $assert_session->pageTextNotContains('Content fields');
  }

  /**
   * Tests a config-based implementation of Layout Builder.
   *
   * @see \Drupal\layout_builder_test\Plugin\SectionStorage\SimpleConfigSectionStorage
   */
  public function testSimpleConfigBasedLayout() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->createUser(['configure any layout']));

    // Prepare an object with a pre-existing section.
    $this->container->get('config.factory')->getEditable('layout_builder_test.test_simple_config.existing')
      ->set('sections', [(new Section('layout_twocol'))->toArray()])
      ->save();

    // The pre-existing section is found.
    $this->drupalGet('layout-builder-test-simple-config/existing');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->elementsCount('css', '.layout--twocol', 1);

    // The default layout is selected for a new object.
    $this->drupalGet('layout-builder-test-simple-config/new');
    $assert_session->elementsCount('css', '.layout', 1);
    $assert_session->elementsCount('css', '.layout--onecol', 1);
  }

  /**
   * Asserts that the correct layouts are available.
   */
  protected function assertCorrectLayouts() {
    $assert_session = $this->assertSession();
    // Ensure the layouts provided by layout_builder are available.
    $expected_layouts_hrefs = [
      'layout_builder/add/section/overrides/node.1/0/layout_onecol',
      'layout_builder/configure/section/overrides/node.1/0/layout_twocol_section',
      'layout_builder/configure/section/overrides/node.1/0/layout_threecol_section',
      'layout_builder/add/section/overrides/node.1/0/layout_fourcol_section',
    ];
    foreach ($expected_layouts_hrefs as $expected_layouts_href) {
      $assert_session->linkByHrefExists($expected_layouts_href);
    }
    // Ensure the layout_discovery module's layouts were removed.
    $unexpected_layouts = [
      'twocol',
      'twocol_bricks',
      'threecol_25_50_25',
      'threecol_33_34_33',
    ];
    foreach ($unexpected_layouts as $unexpected_layout) {
      $assert_session->linkByHrefNotExists("layout_builder/add/section/overrides/node.1/0/$unexpected_layout");
    }
  }

}
