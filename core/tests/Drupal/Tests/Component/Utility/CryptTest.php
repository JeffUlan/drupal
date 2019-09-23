<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Crypt;
use PHPUnit\Framework\TestCase;

/**
 * Tests random byte generation.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Crypt
 */
class CryptTest extends TestCase {

  /**
   * Tests random byte generation.
   *
   * @covers ::randomBytes
   * @expectedDeprecation Drupal\Component\Utility\Crypt::randomBytes() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use PHP's built-in random_bytes() function instead. See https://www.drupal.org/node/3054488
   * @group legacy
   */
  public function testRandomBytes() {
    $this->assertSame(16, strlen(Crypt::randomBytes(16)));
  }

  /**
   * Tests hash generation.
   *
   * @dataProvider providerTestHashBase64
   * @covers ::hashBase64
   *
   * @param string $data
   *   Data to hash.
   * @param string $expected_hash
   *   Expected result from hashing $data.
   */
  public function testHashBase64($data, $expected_hash) {
    $hash = Crypt::hashBase64($data);
    $this->assertEquals($expected_hash, $hash, 'The correct hash was not calculated.');
  }

  /**
   * Tests HMAC generation.
   *
   * @dataProvider providerTestHmacBase64
   * @covers ::hmacBase64
   *
   * @param string $data
   *   Data to hash.
   * @param string $key
   *   Key to use in hashing process.
   * @param string $expected_hmac
   *   Expected result from hashing $data using $key.
   */
  public function testHmacBase64($data, $key, $expected_hmac) {
    $hmac = Crypt::hmacBase64($data, $key);
    $this->assertEquals($expected_hmac, $hmac, 'The correct hmac was not calculated.');
  }

  /**
   * Tests the hmacBase64 method with invalid parameters.
   *
   * @dataProvider providerTestHmacBase64Invalid
   * @covers ::hmacBase64
   *
   * @param string $data
   *   Data to hash.
   * @param string $key
   *   Key to use in hashing process.
   */
  public function testHmacBase64Invalid($data, $key) {
    $this->expectException('InvalidArgumentException');
    Crypt::hmacBase64($data, $key);
  }

  /**
   * Provides data for self::testHashBase64().
   *
   * @return array Test data.
   */
  public function providerTestHashBase64() {
    return [
      [
        'data' => 'The SHA (Secure Hash Algorithm) is one of a number of cryptographic hash functions. A cryptographic hash is like a signature for a text or a data file. SHA-256 algorithm generates an almost-unique, fixed size 256-bit (32-byte) hash. Hash is a one way function – it cannot be decrypted back. This makes it suitable for password validation, challenge hash authentication, anti-tamper, digital signatures.',
        'expectedHash' => '034rT6smZAVRxpq8O98cFFNLIVx_Ph1EwLZQKcmRR_s',
      ],
      [
        'data' => 'SHA-256 is one of the successor hash functions to SHA-1, and is one of the strongest hash functions available.',
        'expected_hash' => 'yuqkDDYqprL71k4xIb6K6D7n76xldO4jseRhEkEE6SI',
      ],
    ];
  }

  /**
   * Provides data for self::testHmacBase64().
   *
   * @return array Test data.
   */
  public function providerTestHmacBase64() {
    return [
      [
        'data' => 'Calculates a base-64 encoded, URL-safe sha-256 hmac.',
        'key' => 'secret-key',
        'expected_hmac' => '2AaH63zwjhekWZlEpAiufyfhAHIzbQhl9Hd9oCi3_c8',
      ],
    ];
  }

  /**
   * Provides data for self::testHmacBase64().
   *
   * @return array Test data.
   */
  public function providerTestHmacBase64Invalid() {
    return [
      [new \stdClass(), new \stdClass()],
      [new \stdClass(), 'string'],
      [new \stdClass(), 1],
      [new \stdClass(), 0],
      [NULL, new \stdClass()],
      ['string', new \stdClass()],
      [1, new \stdClass()],
      [0, new \stdClass()],
      [[], []],
      [[], NULL],
      [[], 'string'],
      [[], 1],
      [[], 0],
      [NULL, []],
      [1, []],
      [0, []],
      ['string', []],
      [[], NULL],
      [NULL, NULL],
      [NULL, 'string'],
      [NULL, 1],
      [NULL, 0],
      [1, NULL],
      [0, NULL],
      ['string', NULL],
    ];
  }

  /**
   * Legacy test of Drupal\Component\Utility\Crypt::hashEquals() method.
   *
   * @expectedDeprecation Drupal\Component\Utility\Crypt::hashEquals() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use PHP's built-in hash_equals() function instead. See https://www.drupal.org/node/3054488
   * @group legacy
   */
  public function testHashEquals() {
    $a_hash = Crypt::hashBase64('a');
    $b_hash = Crypt::hashBase64('b');
    $this->assertTrue(Crypt::hashEquals($a_hash, $a_hash));
    $this->assertFalse(Crypt::hashEquals($a_hash, $b_hash));
  }

}
