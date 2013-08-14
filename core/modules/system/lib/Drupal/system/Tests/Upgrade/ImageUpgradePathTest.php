<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Upgrade\ImageUpgradePathTest.
 */

namespace Drupal\system\Tests\Upgrade;

/**
 * Test upgrade of overridden and custom image styles.
 */
class ImageUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Image upgrade test',
      'description' => 'Upgrade tests for overridden and custom image styles.',
      'group' => 'Upgrade path',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.image.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests that custom and overridden image styles have been upgraded.
   */
  public function testImageStyleUpgrade() {
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Verify that image styles were properly upgraded.
    $expected_styles['test-custom'] = array(
      'name' => 'test-custom',
      'effects' => array(
        'image_rotate' => array(
          'id' => 'image_rotate',
          'data' => array(
            'degrees' => '90',
            'bgcolor' => '#FFFFFF',
            'random' => '1',
          ),
          'weight' => '1',
        ),
        'image_desaturate' => array(
          'id' => 'image_desaturate',
          'data' => array(),
          'weight' => '2',
        ),
      ),
    );
    $expected_styles['thumbnail'] = array(
      'name' => 'thumbnail',
      'effects' => array (
        'image_scale' => array(
          'id' => 'image_scale',
          'data' => array (
            'width' => '177',
            'height' => '177',
            'upscale' => '0',
          ),
          'weight' => '0',
        ),
      ),
    );
    foreach ($expected_styles as $name => $style) {
      $config = \Drupal::config('image.style.' . $name);
      // Replace placeholder with image effect name keys with UUID's generated
      // during by the image style upgrade functions.
      foreach ($config->get('effects') as $uuid => $effect) {
        // Copy placeholder data.
        $style['effects'][$uuid] = $style['effects'][$effect['id']];
        // Set the missing uuid key as this is unknown because it is a UUID.
        $style['effects'][$uuid]['uuid'] = $uuid;
        // Remove the placeholder data.
        unset($style['effects'][$effect['id']]);
      }
      $this->assertEqual($this->sortByKey($style), $config->get(), format_string('@first is equal to @second.', array(
        '@first' => var_export($this->sortByKey($style), TRUE),
        '@second' => var_export($config->get(), TRUE),
      )));
    }
  }
  /**
   * Sorts all keys in configuration data.
   *
   * Since we can not be sure of the order of the UUID's generated by
   * _image_update_get_style_with_effects() we need to sort the data in order
   * to compare it with data saved in the config system.
   *
   * @param array $data
   *   An associative array to sort recursively by key name.
   *
   * @return array
   *   A sorted array.
   */
  public function sortByKey(array $data) {
    ksort($data);
    foreach ($data as &$value) {
      if (is_array($value)) {
        $this->sortByKey($value);
      }
    }
    return $data;
  }
}
