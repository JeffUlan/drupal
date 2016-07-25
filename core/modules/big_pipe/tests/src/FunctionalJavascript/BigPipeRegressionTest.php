<?php

namespace Drupal\Tests\big_pipe\FunctionalJavascript;

use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe_regression_test\BigPipeRegressionTestController;
use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * BigPipe regression tests.
 *
 * @group big_pipe
 */
class BigPipeRegressionTest extends JavascriptTestBase {

  use CommentTestTrait;
  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'comment',
    'big_pipe',
    'big_pipe_regression_test',
    'history',
    'editor',
    'ckeditor',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Use the big_pipe_test_theme theme.
    $this->container->get('theme_installer')->install(['big_pipe_test_theme']);
    $this->container->get('config.factory')->getEditable('system.theme')->set('default', 'big_pipe_test_theme')->save();
  }

  /**
   * Ensure comment form works with history and big_pipe modules.
   *
   * @see https://www.drupal.org/node/2698811
   */
  public function testCommentForm_2698811() {
    // Ensure an `article` node type exists.
    $this->createContentType(['type' => 'article']);
    $this->addDefaultCommentField('node', 'article');

    // Enable CKEditor.
    $format = $this->randomMachineName();
    FilterFormat::create([
      'format' => $format,
      'name' => $this->randomString(),
      'weight' => 1,
      'filters' => [],
    ])->save();
    $settings['toolbar']['rows'] = [
      [
        [
          'name' => 'Links',
          'items' => [
            'DrupalLink',
            'DrupalUnlink',
          ],
        ],
      ],
    ];
    $editor = Editor::create([
      'format' => $format,
      'editor' => 'ckeditor',
    ]);
    $editor->setSettings($settings);
    $editor->save();

    $admin_user = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'use text format ' . $format,
    ]);
    $this->drupalLogin($admin_user);

    $node = $this->createNode([
      'type' => 'article',
      'comment' => CommentItemInterface::OPEN,
    ]);
    // Create some comments.
    foreach (range(1, 5) as $i) {
      $comment = Comment::create([
        'status' => CommentInterface::PUBLISHED,
        'field_name' => 'comment',
        'entity_type' => 'node',
        'entity_id' => $node->id(),
      ]);
      $comment->save();
    }
    $this->drupalGet($node->toUrl()->toString());
    // Confirm that CKEditor loaded.
    $javascript = <<<JS
    (function(){
      return Object.keys(CKEDITOR.instances).length > 0;
    }());
JS;
    $this->assertJsCondition($javascript);
  }

  /**
   * Ensure BigPipe works despite inline JS containing the string "</body>".
   *
   * @see https://www.drupal.org/node/2678662
   */
  public function testMultipleClosingBodies_2678662() {
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet(Url::fromRoute('big_pipe_regression_test.2678662'));

    // Confirm that AJAX behaviors were instantiated, if not, this points to a
    // JavaScript syntax error.
    $javascript = <<<JS
    (function(){
      return Object.keys(Drupal.ajax.instances).length > 0;
    }());
JS;
    $this->assertJsCondition($javascript);

    // Besides verifying there is no JavaScript syntax error, also verify the
    // HTML structure.
    $this->assertSession()->responseContains(BigPipe::STOP_SIGNAL . "\n\n\n</body></html>", 'The BigPipe stop signal is present just before the closing </body> and </html> tags.');
    $js_code_until_closing_body_tag = substr(BigPipeRegressionTestController::MARKER_2678662, 0, strpos(BigPipeRegressionTestController::MARKER_2678662, '</body>'));
    $this->assertSession()->responseNotContains($js_code_until_closing_body_tag . "\n" . BigPipe::START_SIGNAL, 'The BigPipe start signal does NOT start at the closing </body> tag string in an inline script.');
  }

}
