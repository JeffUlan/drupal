<?php

namespace Drupal\KernelTests\Core\File;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the phar stream wrapper works.
 *
 * @group File
 */
class PharWrapperTest extends KernelTestBase {

  /**
   * Tests that only valid phar files can be used.
   */
  public function testPharFile() {
    $base = $this->getDrupalRoot() . '/core/tests/fixtures/files';
    // Ensure that file operations via the phar:// stream wrapper work for phar
    // files with the .phar extension.
    $this->assertFileDoesNotExist("phar://$base/phar-1.phar/no-such-file.php");
    $this->assertFileExists("phar://$base/phar-1.phar/index.php");
    $file_contents = file_get_contents("phar://$base/phar-1.phar/index.php");
    $expected_hash = 'c7e7904ea573c5ebea3ef00bb08c1f86af1a45961fbfbeb1892ff4a98fd73ad5';
    $this->assertSame($expected_hash, hash('sha256', $file_contents));

    // @todo clean-up for PHP 8.0+ https://www.drupal.org/node/3210486
    if (PHP_VERSION_ID < 80000) {
      // Ensure that file operations via the phar:// stream wrapper throw an
      // exception for files without the .phar extension.
      $this->expectException('TYPO3\PharStreamWrapper\Exception');
      file_exists("phar://$base/image-2.jpg/index.php");
    }
    else {
      // PHP 8 fixed via https://wiki.php.net/rfc/phar_stop_autoloading_metadata
      $this->assertFalse(file_exists("phar://$base/image-2.jpg/index.php"));
    }
  }

}
