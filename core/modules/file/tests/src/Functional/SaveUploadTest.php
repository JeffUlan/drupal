<?php

namespace Drupal\Tests\file\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the file_save_upload() function.
 *
 * @group file
 */
class SaveUploadTest extends FileManagedTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An image file path for uploading.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $image;

  /**
   * A PHP file path for upload security testing.
   *
   * @var string
   */
  protected $phpfile;

  /**
   * The largest file id when the test starts.
   *
   * @var int
   */
  protected $maxFidBefore;

  /**
   * Extension of the image filename.
   *
   * @var string
   */
  protected $imageExtension;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->drupalCreateUser(['access site reports']);
    $this->drupalLogin($account);

    $image_files = $this->drupalGetTestFiles('image');
    $this->image = File::create((array) current($image_files));

    [, $this->imageExtension] = explode('.', $this->image->getFilename());
    $this->assertFileExists($this->image->getFileUri());

    $this->phpfile = current($this->drupalGetTestFiles('php'));
    $this->assertFileExists($this->phpfile->uri);

    $this->maxFidBefore = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];

    // Upload with replace to guarantee there's something there.
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    // Check that the success message is present.
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called then clean out the hook
    // counters.
    $this->assertFileHooksCalled(['validate', 'insert']);
    file_test_reset();
  }

  /**
   * Tests the file_save_upload() function.
   */
  public function testNormal() {
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];
    // Verify that a new file was created.
    $this->assertGreaterThan($this->maxFidBefore, $max_fid_after);
    $file1 = File::load($max_fid_after);
    $this->assertInstanceOf(File::class, $file1);
    // MIME type of the uploaded image may be either image/jpeg or image/png.
    $this->assertEquals('image', substr($file1->getMimeType(), 0, 5), 'A MIME type was set.');

    // Reset the hook counters to get rid of the 'load' we just called.
    file_test_reset();

    // Upload a second file.
    $image2 = current($this->drupalGetTestFiles('image'));
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image2->uri)];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    $file2 = File::load($max_fid_after);
    $this->assertInstanceOf(File::class, $file2);
    // MIME type of the uploaded image may be either image/jpeg or image/png.
    $this->assertEquals('image', substr($file2->getMimeType(), 0, 5), 'A MIME type was set.');

    // Load both files using File::loadMultiple().
    $files = File::loadMultiple([$file1->id(), $file2->id()]);
    $this->assertTrue(isset($files[$file1->id()]), 'File was loaded successfully');
    $this->assertTrue(isset($files[$file2->id()]), 'File was loaded successfully');

    // Upload a third file to a subdirectory.
    $image3 = current($this->drupalGetTestFiles('image'));
    $image3_realpath = \Drupal::service('file_system')->realpath($image3->uri);
    $dir = $this->randomMachineName();
    $edit = [
      'files[file_test_upload]' => $image3_realpath,
      'file_subdir' => $dir,
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $this->assertFileExists('temporary://' . $dir . '/' . trim(\Drupal::service('file_system')->basename($image3_realpath)));
  }

  /**
   * Tests uploading a duplicate file.
   */
  public function testDuplicate() {
    // It should not be possible to create two managed files with the same URI.
    $image1 = current($this->drupalGetTestFiles('image'));
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image1->uri)];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];
    $file1 = File::load($max_fid_after);

    // Simulate a race condition where two files are uploaded at almost the same
    // time, by removing the first uploaded file from disk (leaving the entry in
    // the file_managed table) before trying to upload another file with the
    // same name.
    unlink(\Drupal::service('file_system')->realpath($file1->getFileUri()));

    $image2 = $image1;
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image2->uri)];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    // Received a 200 response for posted test file.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("The file {$file1->getFileUri()} already exists. Enter a unique file URI.");
    $max_fid_before_duplicate = $max_fid_after;
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];
    $this->assertEquals($max_fid_before_duplicate, $max_fid_after, 'A new managed file was not created.');
  }

  /**
   * Tests extension handling.
   */
  public function testHandleExtension() {
    // The file being tested is a .gif which is in the default safe list
    // of extensions to allow when the extension validator isn't used. This is
    // implicitly tested at the testNormal() test. Here we tell
    // file_save_upload() to only allow ".foo".
    $extensions = 'foo';
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Only files with the following extensions are allowed: <em class="placeholder">' . $extensions . '</em>');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    // Reset the hook counters.
    file_test_reset();

    $extensions = 'foo ' . $this->imageExtension;
    // Now tell file_save_upload() to allow the extension of our test image.
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains("Only files with the following extensions are allowed:");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);

    // Reset the hook counters.
    file_test_reset();

    // Now tell file_save_upload() to allow any extension.
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains("Only files with the following extensions are allowed:");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);

    // Reset the hook counters.
    file_test_reset();

    // Now tell file_save_upload() to allow any extension and try and upload a
    // malicious file.
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->phpfile->uri),
      'allow_all_extensions' => 'empty_array',
      'is_image_file' => FALSE,
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('For security reasons, your upload has been renamed to <em class="placeholder">' . $this->phpfile->filename . '_.txt' . '</em>');
    $this->assertSession()->pageTextContains('File name is php-2.php_.txt.');
    $this->assertSession()->pageTextContains('File MIME type is text/plain.');
    $this->assertSession()->pageTextContains("You WIN!");
    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);
  }

  /**
   * Tests dangerous file handling.
   */
  public function testHandleDangerousFile() {
    $config = $this->config('system.file');
    // Allow the .php extension and make sure it gets munged and given a .txt
    // extension for safety. Also check to make sure its MIME type was changed.
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->phpfile->uri),
      'is_image_file' => FALSE,
      'extensions' => 'php txt',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('For security reasons, your upload has been renamed to <em class="placeholder">' . $this->phpfile->filename . '_.txt' . '</em>');
    $this->assertSession()->pageTextContains('File name is php-2.php_.txt.');
    $this->assertSession()->pageTextContains('File MIME type is text/plain.');
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Ensure dangerous files are not renamed when insecure uploads is TRUE.
    // Turn on insecure uploads.
    $config->set('allow_insecure_uploads', 1)->save();
    // Reset the hook counters.
    file_test_reset();

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains('File name is php-2.php.');
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Even with insecure uploads allowed, the .php file should not be uploaded
    // if it is not explicitly included in the list of allowed extensions.
    $edit['extensions'] = 'foo';
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Only files with the following extensions are allowed: <em class="placeholder">' . $edit['extensions'] . '</em>');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    // Reset the hook counters.
    file_test_reset();

    // Turn off insecure uploads, then try the same thing as above (ensure that
    // the .php file is still rejected since it's not in the list of allowed
    // extensions).
    $config->set('allow_insecure_uploads', 0)->save();
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Only files with the following extensions are allowed: <em class="placeholder">' . $edit['extensions'] . '</em>');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    // Reset the hook counters.
    file_test_reset();

    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->phpfile->uri),
      'is_image_file' => FALSE,
      'extensions' => 'php',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been rejected.');
    $this->assertSession()->pageTextContains('Epic upload FAIL!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);
  }

  /**
   * Test dangerous file handling.
   */
  public function testHandleDotFile() {
    $dot_file = $this->siteDirectory . '/.test';
    file_put_contents($dot_file, 'This is a test');
    $config = $this->config('system.file');
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($dot_file),
      'is_image_file' => FALSE,
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The specified file .test could not be uploaded');
    $this->assertSession()->pageTextContains('Epic upload FAIL!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_RENAME,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($dot_file),
      'is_image_file' => FALSE,
      'allow_all_extensions' => 'empty_array',
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed to test.');
    $this->assertSession()->pageTextContains('File name is test.');
    $this->assertSession()->pageTextContains('You WIN!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Turn off insecure uploads, then try the same thing as above to ensure dot
    // files are renamed regardless.
    $config->set('allow_insecure_uploads', 0)->save();
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed to test_0.');
    $this->assertSession()->pageTextContains('File name is test_0.');
    $this->assertSession()->pageTextContains('You WIN!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();
  }

  /**
   * Tests file munge handling.
   */
  public function testHandleFileMunge() {
    // Ensure insecure uploads are disabled for this test.
    $this->config('system.file')->set('allow_insecure_uploads', 0)->save();
    $original_image_uri = $this->image->getFileUri();
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $this->image = $file_repository->move($this->image, $original_image_uri . '.foo.' . $this->imageExtension);

    // Reset the hook counters to get rid of the 'move' we just called.
    file_test_reset();

    $extensions = $this->imageExtension;
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $munged_filename = $this->image->getFilename();
    $munged_filename = substr($munged_filename, 0, strrpos($munged_filename, '.'));
    $munged_filename .= '_.' . $this->imageExtension;

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is $munged_filename");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Ensure we don't munge the .foo extension if it is in the list of allowed
    // extensions.
    $extensions = 'foo ' . $this->imageExtension;
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$this->image->getFilename()}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Ensure we don't munge files if we're allowing any extension.
    $this->image = $file_repository->move($this->image, $original_image_uri . '.foo.txt.' . $this->imageExtension);
    // Reset the hook counters.
    file_test_reset();

    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$this->image->getFilename()}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Test that a dangerous extension such as .php is munged even if it is in
    // the list of allowed extensions.
    $this->image = $file_repository->move($this->image, $original_image_uri . '.php.' . $this->imageExtension);
    // Reset the hook counters.
    file_test_reset();

    $extensions = 'php ' . $this->imageExtension;
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is image-test.png_.php_.png");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Dangerous extensions are munged even when all extensions are allowed.
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is image-test.png_.php__0.png");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Dangerous extensions are munged if is renamed to end in .txt.
    $this->image = $file_repository->move($this->image, $original_image_uri . '.cgi.' . $this->imageExtension . '.txt');
    // Reset the hook counters.
    file_test_reset();

    // Dangerous extensions are munged even when all extensions are allowed.
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is image-test.png_.cgi_.png_.txt");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Ensure that setting $validators['file_validate_extensions'] = ['']
    // rejects all files without munging or renaming.
    $edit = [
      'files[file_test_upload][]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_string',
    ];

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);
  }

  /**
   * Tests renaming when uploading over a file that already exists.
   */
  public function testExistingRename() {
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_RENAME,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $this->assertSession()->pageTextContains('File name is image-test_0.png.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);
  }

  /**
   * Tests replacement when uploading over a file that already exists.
   */
  public function testExistingReplace() {
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_REPLACE,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $this->assertSession()->pageTextContains('File name is image-test.png.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);
  }

  /**
   * Tests for failure when uploading over a file that already exists.
   */
  public function testExistingError() {
    $edit = [
      'file_test_replace' => FileSystemInterface::EXISTS_ERROR,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the no hooks were called while failing.
    $this->assertFileHooksCalled([]);
  }

  /**
   * Tests for no failures when not uploading a file.
   */
  public function testNoUpload() {
    $this->drupalGet('file-test/upload');
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextNotContains("Epic upload FAIL!");
  }

  /**
   * Tests for log entry on failing destination.
   */
  public function testDrupalMovingUploadedFileError() {
    // Create a directory and make it not writable.
    $test_directory = 'test_drupal_move_uploaded_file_fail';
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->mkdir('temporary://' . $test_directory, 0000);
    $this->assertDirectoryExists('temporary://' . $test_directory);

    $edit = [
      'file_subdir' => $test_directory,
      'files[file_test_upload]' => $file_system->realpath($this->image->getFileUri()),
    ];

    \Drupal::state()->set('file_test.disable_error_collection', TRUE);
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('File upload error. Could not move uploaded file.');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Uploading failed. Now check the log.
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(200);
    // The full log message is in the title attribute of the link, so we cannot
    // use ::pageTextContains() here.
    $destination = 'temporary://' . $test_directory . '/' . $this->image->getFilename();
    $this->assertSession()->responseContains("Upload error. Could not move uploaded file {$this->image->getFilename()} to destination {$destination}.");
  }

  /**
   * Tests that filenames containing invalid UTF-8 are rejected.
   */
  public function testInvalidUtf8FilenameUpload() {
    $this->drupalGet('file-test/upload');

    // Filename containing invalid UTF-8.
    $filename = "x\xc0xx.gif";

    $page = $this->getSession()->getPage();
    $data = [
      'multipart' => [
        [
          'name'     => 'file_test_replace',
          'contents' => FileSystemInterface::EXISTS_RENAME,
        ],
        [
          'name' => 'form_id',
          'contents' => '_file_test_form',
        ],
        [
          'name' => 'form_build_id',
          'contents' => $page->find('hidden_field_selector', ['hidden_field', 'form_build_id'])->getAttribute('value'),
        ],
        [
          'name' => 'form_token',
          'contents' => $page->find('hidden_field_selector', ['hidden_field', 'form_token'])->getAttribute('value'),
        ],
        [
          'name' => 'op',
          'contents' => 'Submit',
        ],
        [
          'name'     => 'files[file_test_upload]',
          'contents' => 'Test content',
          'filename' => $filename,
        ],
      ],
      'cookies' => $this->getSessionCookies(),
      'http_errors' => FALSE,
    ];

    $this->assertFileDoesNotExist('temporary://' . $filename);
    // Use Guzzle's HTTP client directly so we can POST files without having to
    // write them to disk. Not all filesystem support writing files with invalid
    // UTF-8 filenames.
    $response = $this->getHttpClient()->request('POST', Url::fromUri('base:file-test/upload')->setAbsolute()->toString(), $data);

    $content = (string) $response->getBody();
    $this->htmlOutput($content);
    $error_text = new FormattableMarkup('The file %filename could not be uploaded because the name is invalid.', ['%filename' => $filename]);
    $this->assertStringContainsString((string) $error_text, $content);
    $this->assertStringContainsString('Epic upload FAIL!', $content);
    $this->assertFileDoesNotExist('temporary://' . $filename);
  }

  /**
   * Tests the file_save_upload() function when the field is required.
   */
  public function testRequired() {
    // Reset the hook counters to get rid of the 'load' we just called.
    file_test_reset();

    // Confirm the field is required.
    $this->drupalGet('file-test/upload_required');
    $this->submitForm([], 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('field is required');

    // Confirm that uploading another file works.
    $image = current($this->drupalGetTestFiles('image'));
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image->uri)];
    $this->drupalGet('file-test/upload_required');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('You WIN!');
  }

}
