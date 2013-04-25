<?php

namespace Drupal\simpletest\Tests;

use Drupal\Tests\UnitTestCase;

/**
 * Test PHPUnit errors are getting converted to Simpletest errors.
 */
class PhpUnitErrorTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'PHPUnit errors',
      'description' => 'Test PHPUnit errors getting converted to Simpletest errors.',
      'group' => 'Simpletest',

    );
  }

  /**
   * Test errors reported.
   */
  public function testPhpUnitXmlParsing() {
    require_once __DIR__ . '/../../../../simpletest.module';
    $phpunit_error_xml = __DIR__ . '/phpunit_error.xml';
    $res = simpletest_phpunit_xml_to_rows(1, $phpunit_error_xml);
    $this->assertEquals(count($res), 4, 'All testcases got extracted');
    $this->assertNotEquals($res[0]['status'], 'pass');
    $this->assertEquals($res[0]['status'], 'fail');

    // Test nested testsuites, which appear when you use @dataProvider.
    for ($i = 0; $i < 3; $i++) {
      $this->assertNotEquals($res[$i + 1]['status'], 'pass');
      $this->assertEquals($res[$i + 1]['status'], 'fail');
    }

    // Make sure simpletest_phpunit_xml_to_rows() does not balk if the test
    // didn't run.
    simpletest_phpunit_xml_to_rows(1, 'foobar');
  }
}
