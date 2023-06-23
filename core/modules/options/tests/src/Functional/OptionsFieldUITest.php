<?php

namespace Drupal\Tests\options\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Functional\FieldTestBase;

/**
 * Tests the Options field UI functionality.
 *
 * @group options
 */
class OptionsFieldUITest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'options',
    'field_test',
    'taxonomy',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the created content type.
   *
   * @var string
   */
  protected $typeName;

  /**
   * Machine name of the created content type.
   *
   * @var string
   */
  protected $type;

  /**
   * Name of the option field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Admin path to manage field storage settings.
   *
   * @var string
   */
  protected $adminPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer taxonomy',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer node fields',
      'administer node display',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $this->typeName = 'test_' . strtolower($this->randomMachineName());
    $type = $this->drupalCreateContentType(['name' => $this->typeName, 'type' => $this->typeName]);
    $this->type = $type->id();
  }

  /**
   * Options (integer) : test 'allowed values' input.
   */
  public function testOptionsAllowedValuesInteger() {
    $this->fieldName = 'field_options_integer';
    $this->createOptionsField('list_integer');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Explicit integer keys.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 0,
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => 2,
      'settings[allowed_values][table][1][item][label]' => 'Two',
    ];
    $array = [0 => 'Zero', 2 => 'Two'];
    $this->assertAllowedValuesInput($input, $array, 'Integer keys are accepted.');

    // Non-integer keys.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 1.1,
      'settings[allowed_values][table][0][item][label]' => 'One',
    ];
    $this->assertAllowedValuesInput($input, 'keys must be integers', 'Non integer keys are rejected.');
    $input = [
      'settings[allowed_values][table][0][item][key]' => 'abc',
      'settings[allowed_values][table][0][item][label]' => 'abc',
    ];
    $this->assertAllowedValuesInput($input, 'keys must be integers', 'Non integer keys are rejected.');

    $input = [
      'settings[allowed_values][table][0][item][key]' => 0,
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => 1,
      'settings[allowed_values][table][1][item][label]' => 'One',
    ];
    $array = [0 => 'Zero', 1 => 'One'];
    $this->assertAllowedValuesInput($input, $array, '');
    // Create a node with actual data for the field.
    $settings = [
      'type' => $this->type,
      $this->fieldName => [['value' => 1]],
    ];
    $node = $this->drupalCreateNode($settings);

    // Check that the values in use cannot be removed.
    $this->drupalGet($this->adminPath);
    $assert_session->elementExists('css', '#remove_row_button__1');
    $delete_button_1 = $page->findById('remove_row_button__1');
    $this->assertTrue($delete_button_1->hasAttribute('disabled'), 'Button is disabled');

    // Delete the node, remove the value.
    $node->delete();
    $this->drupalGet($this->adminPath);
    $delete_button_1->click();
    $assert_session->pageTextNotContains('Please wait');
    $page->findById('edit-submit')->click();
    $field_storage = FieldStorageConfig::loadByName('node', $this->fieldName);
    $this->assertSame($field_storage->getSetting('allowed_values'), [0 => 'Zero']);

    // Check that the same key can only be used once.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 0,
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => 0,
      'settings[allowed_values][table][1][item][label]' => 'One',
    ];
    $array = ['0' => 'One'];
    $this->assertAllowedValuesInput($input, $array, 'Same value cannot be used multiple times.');
  }

  /**
   * Options (float) : test 'allowed values' input.
   */
  public function testOptionsAllowedValuesFloat() {
    $this->fieldName = 'field_options_float';
    $this->createOptionsField('list_float');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Explicit numeric keys.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 0,
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => .5,
      'settings[allowed_values][table][1][item][label]' => 'Point five',
    ];
    $array = ['0' => 'Zero', '0.5' => 'Point five'];
    $this->assertAllowedValuesInput($input, $array, 'Integer keys are accepted.');

    // Check that values can be added.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 0,
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => .5,
      'settings[allowed_values][table][1][item][label]' => 'Point five',
      'settings[allowed_values][table][2][item][key]' => 1,
      'settings[allowed_values][table][2][item][label]' => 'One',
    ];
    $array = ['0' => 'Zero', '0.5' => 'Point five', '1' => 'One'];
    $this->assertAllowedValuesInput($input, $array, 'Values can be added.');
    // Non-numeric keys.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 'abc',
      'settings[allowed_values][table][0][item][label]' => 'abc',
    ];
    $this->assertAllowedValuesInput($input, 'each key must be a valid integer or decimal', 'Non numeric keys are rejected.');

    $input = [
      'settings[allowed_values][table][0][item][key]' => 0,
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => .5,
      'settings[allowed_values][table][1][item][label]' => 'Point five',
      'settings[allowed_values][table][2][item][key]' => 2,
      'settings[allowed_values][table][2][item][label]' => 'Two',
    ];
    $array = ['0' => 'Zero', '0.5' => 'Point five', '2' => 'Two'];
    $this->assertAllowedValuesInput($input, $array, '');
    // Create a node with actual data for the field.
    $settings = [
      'type' => $this->type,
      $this->fieldName => [['value' => .5]],
    ];
    $node = $this->drupalCreateNode($settings);

    // Check that the values in use cannot be removed.
    $this->drupalGet($this->adminPath);
    $assert_session->elementExists('css', '#remove_row_button__1');
    $delete_button_1 = $page->findById('remove_row_button__1');
    $this->assertTrue($delete_button_1->hasAttribute('disabled'), 'Button is disabled');

    // Delete the node, remove the value.
    $node->delete();
    $this->drupalGet($this->adminPath);
    $delete_button_1->click();
    $assert_session->pageTextNotContains('Please wait');
    $page->findById('edit-submit')->click();
    $field_storage = FieldStorageConfig::loadByName('node', $this->fieldName);
    $this->assertSame($field_storage->getSetting('allowed_values'), [0 => 'Zero', 2 => 'Two']);

    $input = [
      'settings[allowed_values][table][0][item][key]' => .5,
      'settings[allowed_values][table][0][item][label]' => 'Point five',
      'settings[allowed_values][table][1][item][key]' => .5,
      'settings[allowed_values][table][1][item][label]' => 'Half',
    ];
    $array = ['0.5' => 'Half'];
    $this->assertAllowedValuesInput($input, $array, 'Same value cannot be used multiple times.');

    // Check that different forms of the same float value cannot be used.
    $input = [
      'settings[allowed_values][table][0][item][key]' => .5,
      'settings[allowed_values][table][0][item][label]' => 'Point five',
      'settings[allowed_values][table][1][item][key]' => 0.5,
      'settings[allowed_values][table][1][item][label]' => 'Half',
    ];
    $array = ['0.5' => 'Half'];
    $this->assertAllowedValuesInput($input, $array, 'Different forms of the same value cannot be used.');
  }

  /**
   * Options (text) : test 'allowed values' input.
   */
  public function testOptionsAllowedValuesText() {
    $this->fieldName = 'field_options_text';
    $this->createOptionsField('list_string');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Explicit keys.
    $input = [
      'settings[allowed_values][table][0][item][key]' => '_zero',
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => '_one',
      'settings[allowed_values][table][1][item][label]' => 'One',
    ];
    $array = ['_zero' => 'Zero', '_one' => 'One'];
    $this->assertAllowedValuesInput($input, $array, 'Explicit keys are accepted.');

    // Overly long keys.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 'zero',
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => $this->randomMachineName(256),
      'settings[allowed_values][table][1][item][label]' => 'One',
    ];
    $this->assertAllowedValuesInput($input, 'each key must be a string at most 255 characters long', 'Overly long keys are rejected.');

    $input = [
      'settings[allowed_values][table][0][item][key]' => 'zero',
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => 'one',
      'settings[allowed_values][table][1][item][label]' => 'One',
    ];
    $array = ['zero' => 'Zero', 'one' => 'One'];
    $this->assertAllowedValuesInput($input, $array, '');
    // Create a node with actual data for the field.
    $settings = [
      'type' => $this->type,
      $this->fieldName => [['value' => 'one']],
    ];
    $node = $this->drupalCreateNode($settings);

    // Check that the values in use cannot be removed.
    $this->drupalGet($this->adminPath);
    $assert_session->elementExists('css', '#remove_row_button__1');
    $delete_button_1 = $page->findById('remove_row_button__1');
    $value_field_1 = $page->findField('settings[allowed_values][table][1][item][key]');
    $this->assertTrue($delete_button_1->hasAttribute('disabled'), 'Button is disabled');
    $this->assertTrue($value_field_1->hasAttribute('disabled'), 'Button is disabled');

    // Delete the node, remove the value.
    $node->delete();
    $this->drupalGet($this->adminPath);
    $delete_button_1->click();
    $assert_session->pageTextNotContains('Please wait');
    $page->findById('edit-submit')->click();
    $field_storage = FieldStorageConfig::loadByName('node', $this->fieldName);
    $this->assertSame($field_storage->getSetting('allowed_values'), ['zero' => 'Zero']);

    // Check that string values with dots can not be used.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 'zero',
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => 'example.com',
      'settings[allowed_values][table][1][item][label]' => 'Example',
    ];
    $this->assertAllowedValuesInput($input, 'The machine-readable name must contain only lowercase letters, numbers, and underscores.', 'String value with dot is not supported.');

    // Check that the same key can only be used once.
    $input = [
      'settings[allowed_values][table][0][item][key]' => 'zero',
      'settings[allowed_values][table][0][item][label]' => 'Zero',
      'settings[allowed_values][table][1][item][key]' => 'zero',
      'settings[allowed_values][table][1][item][label]' => 'One',
    ];
    $array = ['zero' => 'One'];
    $this->assertAllowedValuesInput($input, $array, 'Same value cannot be used multiple times.');
  }

  /**
   * Helper function to create list field of a given type.
   *
   * @param string $type
   *   One of 'list_integer', 'list_float' or 'list_string'.
   */
  protected function createOptionsField($type) {
    // Create a field.
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => $type,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => $this->type,
    ])->save();

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->type)
      ->setComponent($this->fieldName)
      ->save();

    $this->adminPath = 'admin/structure/types/manage/' . $this->type . '/fields/node.' . $this->type . '.' . $this->fieldName . '/storage';
  }

  /**
   * Tests an input array for the 'allowed values' form element.
   *
   * @param array $input
   *   The input array.
   * @param array|string $result
   *   Either an expected resulting array in
   *   $field->getSetting('allowed_values'), or an expected error message.
   * @param string $message
   *   Message to display.
   *
   * @internal
   */
  public function assertAllowedValuesInput(array $input, $result, string $message): void {
    $this->drupalGet($this->adminPath);
    $page = $this->getSession()->getPage();
    $add_button = $page->findButton('Add another item');
    $add_button->click();
    $add_button->click();

    $this->submitForm($input, 'Save field settings');
    // Verify that the page does not have double escaped HTML tags.
    $this->assertSession()->responseNotContains('&amp;lt;');

    if (is_string($result)) {
      $this->assertSession()->pageTextContains($result);
    }
    else {
      $field_storage = FieldStorageConfig::loadByName('node', $this->fieldName);
      $this->assertSame($field_storage->getSetting('allowed_values'), $result, $message);
    }
  }

  /**
   * Tests normal and key formatter display on node display.
   */
  public function testNodeDisplay() {
    $this->fieldName = strtolower($this->randomMachineName());
    $this->createOptionsField('list_integer');
    $node = $this->drupalCreateNode(['type' => $this->type]);

    $on = $this->randomMachineName();
    $off = $this->randomMachineName();
    $edit = [
      'settings[allowed_values][table][0][item][key]' => 1,
      'settings[allowed_values][table][0][item][label]' => $on,
      'settings[allowed_values][table][1][item][key]' => 0,
      'settings[allowed_values][table][1][item][label]' => $off,
    ];

    $this->drupalGet($this->adminPath);
    $page = $this->getSession()->getPage();
    $page->findButton('Add another item')->click();
    $this->submitForm($edit, 'Save field settings');
    $this->assertSession()->pageTextContains('Updated field ' . $this->fieldName . ' field settings.');

    // Select a default value.
    $edit = [
      $this->fieldName => '1',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Check the node page and see if the values are correct.
    $file_formatters = ['list_default', 'list_key'];
    foreach ($file_formatters as $formatter) {
      $edit = [
        "fields[$this->fieldName][type]" => $formatter,
        "fields[$this->fieldName][region]" => 'content',
      ];
      $this->drupalGet('admin/structure/types/manage/' . $this->typeName . '/display');
      $this->submitForm($edit, 'Save');
      $this->drupalGet('node/' . $node->id());

      if ($formatter == 'list_default') {
        $output = $on;
      }
      else {
        $output = '1';
      }

      // Verify that correct options are found.
      $this->assertSession()->elementsCount('xpath', '//div[text()="' . $output . '"]', 1);
    }
  }

  /**
   * Confirms the allowed value list is a required field.
   */
  public function testRequiredPropertyForAllowedValuesList() {
    $field_types = [
      'list_float',
      'list_string',
      'list_integer',
    ];

    foreach ($field_types as $field_type) {
      $this->fieldName = "field_options_$field_type";
      $this->createOptionsField($field_type);
      $page = $this->getSession()->getPage();

      // Try to proceed without entering any value.
      $this->drupalGet($this->adminPath);
      $page->findButton('Save field settings')->click();

      if ($field_type == 'list_string') {
        // Asserting only name field as there is no value field for list_string.
        $this->assertSession()->pageTextContains('Name field is required.');
      }
      else {
        // Confirmation message that name and value are required fields for
        // list_float and list_integer.
        $this->assertSession()->pageTextContains('Name field is required.');
        $this->assertSession()->pageTextContains('Value field is required.');
      }
    }
  }

}
