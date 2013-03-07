<?php

/**
 * @file
 * Contains \Drupal\Tests\UnitTestCase.
 */

namespace Drupal\Tests;

class UnitTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * This method exists to support the simpletest UI runner.
   *
   * It should eventually be replaced with something native to phpunit.
   *
   * Also, this method is empty because you can't have an abstract static
   * method. Sub-classes should always override it.
   *
   * @return array
   *   An array describing the test like so:
   *   array(
   *     'name' => 'Something Test',
   *     'description' => 'Tests Something',
   *     'group' => 'Something',
   *   )
   */
  public static function getInfo() {
    throw new \RuntimeException("Sub-class must implement the getInfo method!");
  }

  /**
   * Generates a random string containing letters and numbers.
   *
   * The string will always start with a letter. The letters may be upper or
   * lower case. This method is better for restricted inputs that do not accept
   * certain characters. For example, when testing input fields that require
   * machine readable values (i.e. without spaces and non-standard characters)
   * this method is best.
   *
   * Do not use this method when testing unvalidated user input. Instead, use
   * Drupal\simpletest\TestBase::randomString().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see Drupal\simpletest\TestBase::randomString()
   */
  public static function randomName($length = 8) {
    $values = array_merge(range(65, 90), range(97, 122), range(48, 57));
    $max = count($values) - 1;
    $str = chr(mt_rand(97, 122));
    for ($i = 1; $i < $length; $i++) {
      $str .= chr($values[mt_rand(0, $max)]);
    }
    return $str;
  }
}
