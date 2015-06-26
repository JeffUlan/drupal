<?php

/**
 * @file
 * Contains \Drupal\system\Tests\File\UnmanagedDeleteTest.
 */

namespace Drupal\system\Tests\File;

/**
 * Tests the unmanaged file delete function.
 *
 * @group File
 */
class UnmanagedDeleteTest extends FileTestBase {
  /**
   * Delete a normal file.
   */
  function testNormal() {
    // Create a file for testing
    $uri = $this->createUri();

    // Delete a regular file
    $this->assertTrue(file_unmanaged_delete($uri), 'Deleted worked.');
    $this->assertFalse(file_exists($uri), 'Test file has actually been deleted.');
  }

  /**
   * Try deleting a missing file.
   */
  function testMissing() {
    // Try to delete a non-existing file
    $this->assertTrue(file_unmanaged_delete(file_default_scheme() . '/' . $this->randomMachineName()), 'Returns true when deleting a non-existent file.');
  }

  /**
   * Try deleting a directory.
   */
  function testDirectory() {
    // A directory to operate on.
    $directory = $this->createDirectory();

    // Try to delete a directory
    $this->assertFalse(file_unmanaged_delete($directory), 'Could not delete the delete directory.');
    $this->assertTrue(file_exists($directory), 'Directory has not been deleted.');
  }
}
