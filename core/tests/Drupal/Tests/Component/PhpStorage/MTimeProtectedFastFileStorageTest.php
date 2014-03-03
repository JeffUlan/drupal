<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\PhpStorage\MTimeProtectedFastFileStorageTest.
 */

namespace Drupal\Tests\Component\PhpStorage;

/**
 * Tests the directory mtime based PHP loader implementation.
 */
class MTimeProtectedFastFileStorageTest extends MTimeProtectedFileStorageBase {

  /**
   * The expected test results for the security test.
   *
   * The first iteration does not change the directory mtime so this class will
   * include the hacked file on the first try but the second test will change
   * the directory mtime and so on the second try the file will not be included.
   */
  protected $expected = array(TRUE, FALSE);

  /**
   * The PHP storage class to test.
   */
  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFastFileStorage';

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'MTime protected fast file storage',
      'description' => 'Tests the MTimeProtectedFastFileStorage implementation.',
      'group' => 'PHP Storage',
    );
  }

}
