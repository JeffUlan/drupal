<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

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
  protected static $modules = ['taxonomy', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
  }

}
