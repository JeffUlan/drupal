<?php

namespace Drupal\Tests;

/**
 * Tests the PHPUnit forward compatibility trait.
 *
 * @coversDefaultClass \Drupal\Tests\PhpunitCompatibilityTrait
 * @group Tests
 */
class PhpunitCompatibilityTraitTest extends UnitTestCase {

  /**
   * Tests that getMock is available and calls the correct parent method.
   *
   * @covers ::getMock
   * @dataProvider providerMockVersions
   * @group legacy
   * @expectedDeprecation \Drupal\Tests\PhpunitCompatibilityTrait::getMock() is deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead. See https://www.drupal.org/node/2907725
   */
  public function testGetMock($className, $expected) {
    $class = new $className();
    $this->assertSame($expected, $class->getMock($this->randomMachineName()));
  }

  /**
   * Tests that createMock is available and calls the correct parent method.
   *
   * @covers ::createMock
   * @dataProvider providerMockVersions
   */
  public function testCreateMock($className, $expected) {
    $class = new $className();
    $this->assertSame($expected, $class->createMock($this->randomMachineName()));
  }

  /**
   * Tests that createMock is available and calls the correct parent method.
   *
   * @covers ::createMock
   * @dataProvider providerMockLegacyVersions
   * @group legacy
   * @expectedDeprecation \Drupal\Tests\PhpunitCompatibilityTrait::getMock() is deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\Tests\PhpunitCompatibilityTrait::createMock() instead. See https://www.drupal.org/node/2907725
   */
  public function testLegacyCreateMock($className, $expected) {
    $this->testCreateMock($className, $expected);
  }

  /**
   * Returns the class names and the string they return.
   *
   * @return array
   */
  public function providerMockVersions() {
    return [
      [UnitTestCasePhpunit6TestClass::class, 'PHPUnit 6'],
      [UnitTestCasePhpunit6TestClassExtends::class, 'PHPUnit 6'],
    ];
  }

  /**
   * Returns the class names and the string they return.
   *
   * @return array
   */
  public function providerMockLegacyVersions() {
    return [
      [UnitTestCasePhpunit4TestClass::class, 'PHPUnit 4'],
      [UnitTestCasePhpunit4TestClassExtends::class, 'PHPUnit 4'],
    ];
  }

}

/**
 * Test class for \PHPUnit\Framework\TestCase in PHPUnit 4.
 */
class Phpunit4TestClass {

  public function getMock($originalClassName) {
    return 'PHPUnit 4';
  }

}

/**
 * Test class for \PHPUnit\Framework\TestCase in PHPUnit 6.
 */
class Phpunit6TestClass {

  public function createMock($originalClassName) {
    return 'PHPUnit 6';
  }

  public function getMockbuilder() {
    return new Mockbuilder();
  }

}

/**
 * Test double for PHPUnit_Framework_MockObject_MockBuilder.
 */
class Mockbuilder {

  public function __call($name, $arguments) {
    return $this;
  }

  public function getMock() {
    return 'PHPUnit 6';
  }

}

/**
 * Test class for \Drupal\Tests\UnitTestCase with PHPUnit 4.
 */
class UnitTestCasePhpunit4TestClass extends Phpunit4TestClass {
  use PhpunitCompatibilityTrait;

}

/**
 * Test class for \Drupal\Tests\UnitTestCase with PHPUnit 4.
 */
class UnitTestCasePhpunit4TestClassExtends extends UnitTestCasePhpunit4TestClass {
}

/**
 * Test class for \Drupal\Tests\UnitTestCase with PHPUnit 6.
 */
class UnitTestCasePhpunit6TestClass extends Phpunit6TestClass {
  use PhpunitCompatibilityTrait;

}

/**
 * Test class for \Drupal\Tests\UnitTestCase with PHPUnit 6.
 */
class UnitTestCasePhpunit6TestClassExtends extends UnitTestCasePhpunit6TestClass {
}
