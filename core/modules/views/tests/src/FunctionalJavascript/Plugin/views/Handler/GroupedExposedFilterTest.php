<?php

namespace Drupal\Tests\views\FunctionalJavascript\Plugin\views\Handler;

use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the grouped exposed filter admin UI.
 *
 * @group views
 */
class GroupedExposedFilterTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'views', 'views_ui', 'user', 'views_test_config'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_exposed_admin_ui'];

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), ['views_test_config']);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();

    $this->account = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($this->account);

    // Setup a node type that has the right fields for the test view.
    NodeType::create([
      'type' => 'page',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'body',
      'bundle' => 'page',
    ])->save();
  }

  /**
   * Test if the right fields are shown and the right values set.
   */
  public function testGroupedFilterValuesUI() {
    $web_assert = $this->assertSession();

    $this->drupalGet('/admin/structure/views/view/test_exposed_admin_ui');
    $page = $this->getSession()->getPage();

    // Open the dialog for the grouped filter.
    $page->clickLink('Content: Authored on (grouped)');
    $web_assert->assertWaitOnAjaxRequest();

    // Test that the 'min' field is shown and that it contains the right value.
    $between_from = $page->findField('options[group_info][group_items][1][value][min]');
    $this->assertNotEmpty($between_from->isVisible());
    $this->assertEquals('2015-01-01', $between_from->getValue());

    // Test that the 'max' field is shown and that it contains the right value.
    $between_to = $page->findField('options[group_info][group_items][1][value][max]');
    $this->assertNotEmpty($between_to->isVisible());
    $this->assertEquals('2016-01-01', $between_to->getValue());
  }

}
