<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\ImageUpload
 * @group ckeditor5
 * @internal
 */
class ImageTest extends WebDriverTestBase {

  use CKEditor5TestTrait;
  use TestFileCreationTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The sample image File entity to embed.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * A host entity with a body field to embed images in.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <a href> <img src alt data-entity-uuid data-entity-type height width data-caption data-align>',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'uploadImage',
            'sourceEditing',
            'link',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '1M',
        'max_dimensions' => ['width' => 100, 'height' => 100],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]);

    // Create a sample host entity to embed images in.
    $this->file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $this->file->save();
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->host = $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p>The pirate is irate.</p>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests linkability of the image CKEditor widget.
   *
   * Due to the complex overrides that `drupalImage.DrupalImage` is making, this
   * is explicitly testing the "editingDowncast" and "dataDowncast" results.
   * These are CKEditor 5 concepts.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#conversion
   *
   * @dataProvider providerLinkability
   */
  public function testLinkability(string $image_type, bool $unrestricted) {
    assert($image_type === 'inline' || $image_type === 'block');

    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
    }

    // Make the test content have either a block image or an inline image.
    $img_tag = '<img alt="drupalimage test image" data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" src="' . $this->file->createFileUrl() . '" />';
    $this->host->body->value .= $image_type === 'block'
      ? $img_tag
      : "<p>$img_tag</p>";
    $this->host->save();
    // Adjust the expectations accordingly.
    $expected_widget_class = $image_type === 'block' ? 'image' : 'image-inline';

    $page = $this->getSession()->getPage();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();

    // Initial state: the image CKEditor Widget is not selected.
    $drupalimage = $assert_session->waitForElementVisible('css', ".ck-content .ck-widget.$expected_widget_class");
    $this->assertNotEmpty($drupalimage);
    $this->assertFalse($drupalimage->hasClass('.ck-widget_selected'));

    // Assert the "editingDowncast" HTML before making changes.
    $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class . ' > img[src*="image-test.png"][alt="drupalimage test image"]');

    // Assert the "dataDowncast" HTML before making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//img[@alt="drupalimage test image"]'));
    $this->assertEmpty($xpath->query('//a'));

    // Assert the link button is present and not pressed.
    $link_button = $this->getEditorButton('Link');
    $this->assertSame('false', $link_button->getAttribute('aria-pressed'));

    // Tests linking images.
    $drupalimage->click();
    $this->assertTrue($drupalimage->hasClass('ck-widget_selected'));
    $this->assertEditorButtonEnabled('Link');
    // Assert structure of image toolbar balloon.
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Image toolbar"]');
    $link_image_button = $this->getBalloonButton('Link image');
    // Click the "Link image" button.
    $this->assertSame('false', $link_image_button->getAttribute('aria-pressed'));
    $link_image_button->press();
    // Assert structure of link form balloon.
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $url_input = $balloon->find('css', '.ck-labeled-field-view__input-wrapper .ck-input-text');
    // Fill in link form balloon's <input> and hit "Save".
    $url_input->setValue('http://www.drupal.org/association');
    $balloon->pressButton('Save');

    // Assert the "editingDowncast" HTML after making changes. First assert the
    // link exists, then assert the expected DOM structure in detail.
    $assert_session->elementExists('css', '.ck-content a[href*="//www.drupal.org/association"]');
    // For inline images, the link is wrapping the widget; for block images the
    // link lives inside the widget. (This is how it is implemented upstream, it
    // could be implemented differently, we just want to ensure we do not break
    // it. Drupal only cares about having its own "dataDowncast", the
    // "editingDowncast" is considered an implementation detail.)
    $assert_session->elementExists('css', $image_type === 'inline'
      ? '.ck-content a[href*="//www.drupal.org/association"] .ck-widget.' . $expected_widget_class . ' > img[src*="image-test.png"][alt="drupalimage test image"]'
      : '.ck-content .ck-widget.' . $expected_widget_class . ' a[href*="//www.drupal.org/association"] > img[src*="image-test.png"][alt="drupalimage test image"]'
    );

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertCount(1, $xpath->query('//a[@href="http://www.drupal.org/association"]/img[@alt="drupalimage test image"]'));
    $this->assertEmpty($xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));

    // Add `class="trusted"` to the link.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertEmpty($xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));
    $this->pressEditorButton('Source');
    $source_text_area = $assert_session->waitForElement('css', '.ck-source-editing-area textarea');
    $this->assertNotEmpty($source_text_area);
    $new_value = str_replace('<a ', '<a class="trusted" ', $source_text_area->getValue());
    $source_text_area->setValue('<p>temp</p>');
    $source_text_area->setValue($new_value);
    $this->pressEditorButton('Source');

    // When unrestricted, additional attributes on links should be retained.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertCount($unrestricted ? 1 : 0, $xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));

    // Save the entity whose text field is being edited.
    $page->pressButton('Save');

    // Assert the HTML the end user sees.
    $assert_session->elementExists('css', $unrestricted
      ? 'a[href="http://www.drupal.org/association"].trusted img[src*="image-test.png"]'
      : 'a[href="http://www.drupal.org/association"] img[src*="image-test.png"]');

    // Go back to edit the now *linked* <drupal-media>. Everything from this
    // point onwards is effectively testing "upcasting" and proving there is no
    // data loss.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    // Assert the "dataDowncast" HTML before making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//img[@alt="drupalimage test image"]'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://www.drupal.org/association"]'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://www.drupal.org/association"]/img[@alt="drupalimage test image"]'));
    $this->assertCount($unrestricted ? 1 : 0, $xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));

    // Tests unlinking images.
    $drupalimage->click();
    $this->assertEditorButtonEnabled('Link');
    $this->assertSame('true', $this->getEditorButton('Link')->getAttribute('aria-pressed'));
    // Assert structure of image toolbar balloon.
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Image toolbar"]');
    $link_image_button = $this->getBalloonButton('Link image');
    $this->assertSame('true', $link_image_button->getAttribute('aria-pressed'));
    $link_image_button->click();
    // Assert structure of link actions balloon.
    $this->getBalloonButton('Edit link');
    $unlink_image_button = $this->getBalloonButton('Unlink');
    // Click the "Unlink" button.
    $unlink_image_button->click();
    $this->assertSame('false', $this->getEditorButton('Link')->getAttribute('aria-pressed'));

    // Assert the "editingDowncast" HTML after making changes. Assert the
    // widget exists but not the link, or *any* link for that matter. Then
    // assert the expected DOM structure in detail.
    $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class);
    // @todo Remove the different assertion for the "inline, unrestricted" case when https://www.drupal.org/project/ckeditor5/issues/3247634 is fixed.
    if ($image_type === 'inline' && $unrestricted) {
      $assert_session->elementNotExists('css', '.ck-content a[href]');
      $assert_session->elementExists('css', '.ck-content a.trusted');
    }
    else {
      $assert_session->elementNotExists('css', '.ck-content a');
    }
    $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class . ' > img[src*="image-test.png"][alt="drupalimage test image"]');

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertCount(0, $xpath->query('//a[@href="http://www.drupal.org/association"]/img[@alt="drupalimage test image"]'));
    $this->assertCount(1, $xpath->query('//img[@alt="drupalimage test image"]'));
    // @todo Remove the different assertion for the "inline, unrestricted" case when https://www.drupal.org/project/ckeditor5/issues/3247634 is fixed.
    if ($image_type === 'inline' && $unrestricted) {
      $this->assertCount(1, $xpath->query('//a'));
    }
    else {
      $this->assertCount(0, $xpath->query('//a'));
    }
  }

  public function providerLinkability(): array {
    return [
      'BLOCK image, restricted' => ['block', FALSE],
      'BLOCK image, unrestricted' => ['block', TRUE],
      'INLINE image, restricted' => ['inline', FALSE],
      'INLINE image, unrestricted' => ['inline', TRUE],
    ];
  }

}
