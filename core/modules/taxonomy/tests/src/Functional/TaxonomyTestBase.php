<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Tests\TaxonomyTestTrait;

/**
 * Provides common helper methods for Taxonomy module tests.
 */
abstract class TaxonomyTestBase extends BrowserTestBase {

  use TaxonomyTestTrait;
  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'block');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }
  }

}
