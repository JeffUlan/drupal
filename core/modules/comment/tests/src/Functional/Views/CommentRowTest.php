<?php

namespace Drupal\Tests\comment\Functional\Views;

/**
 * Tests the comment row plugin.
 *
 * @group comment
 */
class CommentRowTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_row'];

  /**
   * Test comment row.
   */
  public function testCommentRow() {
    $this->drupalGet('test-comment-row');

    $result = $this->xpath('//article[contains(@class, "comment")]');
    $this->assertCount(1, $result, 'One rendered comment found.');
  }

}
