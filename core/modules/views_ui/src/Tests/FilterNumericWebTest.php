<?php

namespace Drupal\views_ui\Tests;

use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Tests the numeric filter UI.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\NumericFilter
 */
class FilterNumericWebTest extends UITestBase {
  use SchemaCheckTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Tests the filter numeric UI.
   */
  public function testFilterNumericUI() {
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_view/default/filter', array('name[views_test_data.age]' => TRUE), t('Add and configure @handler', array('@handler' => t('filter criteria'))));

    $this->drupalPostForm(NULL, array(), t('Expose filter'));
    $this->drupalPostForm(NULL, array(), t('Grouped filters'));

    $edit = array();
    $edit['options[group_info][group_items][1][title]'] = 'Old';
    $edit['options[group_info][group_items][1][operator]'] = '>';
    $edit['options[group_info][group_items][1][value][value]'] = 27;
    $edit['options[group_info][group_items][2][title]'] = 'Young';
    $edit['options[group_info][group_items][2][operator]'] = '<=';
    $edit['options[group_info][group_items][2][value][value]'] = 27;
    $edit['options[group_info][group_items][3][title]'] = 'From 26 to 28';
    $edit['options[group_info][group_items][3][operator]'] = 'between';
    $edit['options[group_info][group_items][3][value][min]'] = 26;
    $edit['options[group_info][group_items][3][value][max]'] = 28;

    $this->drupalPostForm(NULL, $edit, t('Apply'));

    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/age');
    foreach ($edit as $name => $value) {
      $this->assertFieldByName($name, $value);
    }

    $this->drupalPostForm('admin/structure/views/view/test_view', array(), t('Save'));
    $this->assertConfigSchemaByName('views.view.test_view');

    // Test that the exposed filter works as expected.
    $this->drupalPostForm(NULL, array(), t('Update preview'));
    $this->assertText('John');
    $this->assertText('Paul');
    $this->assertText('Ringo');
    $this->assertText('George');
    $this->assertText('Meredith');
    $this->drupalPostForm(NULL, array('age' => '2'), t('Update preview'));
    $this->assertText('John');
    $this->assertText('Paul');
    $this->assertNoText('Ringo');
    $this->assertText('George');
    $this->assertNoText('Meredith');

    // Change the filter to a single filter to test the schema when the operator
    // is not exposed.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_view/default/filter/age', array(), t('Single filter'));
    $edit = array();
    $edit['options[value][value]'] = 25;
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/test_view', array(), t('Save'));
    $this->assertConfigSchemaByName('views.view.test_view');

    // Test that the filter works as expected.
    $this->drupalPostForm(NULL, array(), t('Update preview'));
    $this->assertText('John');
    $this->assertNoText('Paul');
    $this->assertNoText('Ringo');
    $this->assertNoText('George');
    $this->assertNoText('Meredith');
    $this->drupalPostForm(NULL, array('age' => '26'), t('Update preview'));
    $this->assertNoText('John');
    $this->assertText('Paul');
    $this->assertNoText('Ringo');
    $this->assertNoText('George');
    $this->assertNoText('Meredith');

    // Change the filter to a 'between' filter to test if the label and
    // description are set for the 'minimum' filter element.
    $this->drupalGet('admin/structure/views/nojs/handler/test_view/default/filter/age');
    $edit = array();
    $edit['options[expose][label]'] = 'Age between';
    $edit['options[expose][description]'] = 'Description of the exposed filter';
    $edit['options[operator]'] = 'between';
    $edit['options[value][min]'] = 26;
    $edit['options[value][max]'] = 28;
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm('admin/structure/views/view/test_view', array(), t('Save'));
    $this->assertConfigSchemaByName('views.view.test_view');

    $this->drupalPostForm(NULL, array(), t('Update preview'));
    // Check the max field label.
    $this->assertRaw('<label for="edit-age-max">And</label>', 'Max field label found');
    $this->assertRaw('<label for="edit-age-min">Age between</label>', 'Min field label found');
    // Check that the description is shown in the right place.
    $this->assertEqual(trim($this->cssSelect('.form-item-age-min .description')[0]), 'Description of the exposed filter');
  }

}
