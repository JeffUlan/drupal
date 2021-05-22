<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Random;
use PHPUnit\Framework\TestCase;

/**
 * Tests random data generation.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Random
 */
class RandomTest extends TestCase {

  /**
   * The first random string passed to the test callback.
   *
   * @see \Drupal\Tests\Component\Utility\RandomTest::_RandomStringValidate()
   *
   * @var string
   */
  protected $firstStringGenerated = '';

  /**
   * Tests unique random string generation.
   *
   * @covers ::string
   */
  public function testRandomStringUniqueness() {
    $strings = [];
    $random = new Random();
    for ($i = 0; $i <= 50; $i++) {
      $str = $random->string(1, TRUE);
      $this->assertFalse(isset($strings[$str]), 'Generated duplicate random string ' . $str);
      $strings[$str] = TRUE;
    }
  }

  /**
   * Tests unique random name generation.
   *
   * @covers ::name
   */
  public function testRandomNamesUniqueness() {
    $names = [];
    $random = new Random();
    for ($i = 0; $i <= 10; $i++) {
      $str = $random->name(1, TRUE);
      $this->assertFalse(isset($names[$str]), 'Generated duplicate random name ' . $str);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests infinite loop prevention whilst generating random names.
   *
   * @covers ::name
   */
  public function testRandomNameException() {
    // There are fewer than 100 possibilities so an exception should occur to
    // prevent infinite loops.
    $random = new Random();
    $this->expectException(\RuntimeException::class);
    for ($i = 0; $i <= 100; $i++) {
      $str = $random->name(1, TRUE);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests infinite loop prevention whilst generating random strings.
   *
   * @covers ::string
   */
  public function testRandomStringException() {
    // There are fewer than 100 possibilities so an exception should occur to
    // prevent infinite loops.
    $random = new Random();
    $this->expectException(\RuntimeException::class);
    for ($i = 0; $i <= 100; $i++) {
      $str = $random->string(1, TRUE);
      $names[$str] = TRUE;
    }
  }

  /**
   * Tests random name generation if uniqueness is not enforced.
   *
   * @covers ::name
   */
  public function testRandomNameNonUnique() {
    // There are fewer than 100 possibilities if we were forcing uniqueness so
    // exception would occur.
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $random->name(1);
    }
    $this->assertTrue(TRUE, 'No exception thrown when uniqueness is not enforced.');
  }

  /**
   * Tests random string if uniqueness is not enforced.
   *
   * @covers ::string
   */
  public function testRandomStringNonUnique() {
    // There are fewer than 100 possibilities if we were forcing uniqueness so
    // exception would occur.
    $random = new Random();
    for ($i = 0; $i <= 100; $i++) {
      $random->string(1);
    }
    $this->assertTrue(TRUE, 'No exception thrown when uniqueness is not enforced.');
  }

  /**
   * Tests random object generation to ensure the expected number of properties.
   *
   * @covers ::object
   */
  public function testRandomObject() {
    // For values of 0 and 1 \Drupal\Component\Utility\Random::object() will
    // have different execution paths.
    $random = new Random();
    for ($i = 0; $i <= 1; $i++) {
      $obj = $random->object($i);
      $this->assertCount($i, get_object_vars($obj), 'Generated random object has expected number of properties');
    }
  }

  /**
   * Tests random string validation callbacks.
   *
   * @covers ::string
   */
  public function testRandomStringValidator() {
    $random = new Random();
    $this->firstStringGenerated = '';
    $str = $random->string(1, TRUE, [$this, '_RandomStringValidate']);
    $this->assertNotEquals($this->firstStringGenerated, $str);
  }

  /**
   * Tests random word.
   *
   * @covers ::word
   */
  public function testRandomWordValidator() {
    $random = new Random();
    // Without a seed, test a different word is returned each time.
    $this->firstStringGenerated = $random->word(5);
    $next_str = $random->word(5);
    $this->assertNotEquals($this->firstStringGenerated, $next_str);

    // With a seed, test the same word is returned each time.
    mt_srand(0);
    $this->firstStringGenerated = $random->word(5);
    mt_srand(0);
    $next_str = $random->word(5);
    $this->assertEquals($this->firstStringGenerated, $next_str);
  }

  /**
   * Callback for random string validation.
   *
   * @see \Drupal\Component\Utility\Random::name()
   * @see \Drupal\Tests\Component\Utility\RandomTest::testRandomStringValidator()
   *
   * @param string $string
   *   The random string to validate.
   *
   * @return bool
   *   TRUE if the random string is valid, FALSE if not.
   */
  public function _RandomStringValidate($string) {
    // Return FALSE for the first generated string and any string that is the
    // same, as the test expects a different string to be returned.
    if (empty($this->firstStringGenerated) || $string == $this->firstStringGenerated) {
      $this->firstStringGenerated = $string;
      return FALSE;
    }
    return TRUE;
  }

}
